/**
 * Locale Switcher Component
 * 
 * Provides locale switching functionality without page reload for table components.
 * Integrates with Alpine.js for reactive UI updates.
 * 
 * @version 1.0.0
 */

/**
 * Create locale switcher Alpine.js component.
 * 
 * @param {Object} config - Configuration object
 * @param {string} config.currentLocale - Current locale code
 * @param {Object} config.availableLocales - Available locales with metadata
 * @param {string} config.switchUrl - URL endpoint for locale switching
 * @param {Function} config.onLocaleChanged - Callback after locale change
 * @returns {Object} Alpine.js component data
 */
export function localeSwitcher(config = {}) {
    return {
        // State
        currentLocale: config.currentLocale || 'en',
        availableLocales: config.availableLocales || {},
        switchUrl: config.switchUrl || '/locale/switch',
        isOpen: false,
        isSwitching: false,
        error: null,

        // Initialization
        init() {
            // Listen for locale change events
            window.addEventListener('locale:changed', (event) => {
                this.handleLocaleChanged(event.detail);
            });

            // Listen for keyboard shortcuts
            this.setupKeyboardShortcuts();
        },

        // Get current locale info
        get currentLocaleInfo() {
            return this.availableLocales[this.currentLocale] || {};
        },

        // Get current locale flag
        get currentFlag() {
            return this.currentLocaleInfo.flag || '🌐';
        },

        // Get current locale native name
        get currentNativeName() {
            return this.currentLocaleInfo.native || this.currentLocale.toUpperCase();
        },

        // Check if locale is RTL
        isRtl(locale = null) {
            const targetLocale = locale || this.currentLocale;
            const rtlLocales = ['ar', 'he', 'fa', 'ur'];
            return rtlLocales.includes(targetLocale);
        },

        // Switch locale
        async switchLocale(locale) {
            if (locale === this.currentLocale) {
                this.isOpen = false;
                return;
            }

            this.isSwitching = true;
            this.error = null;

            try {
                // Send AJAX request to switch locale
                const response = await fetch(this.switchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ locale }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Update current locale
                    const previousLocale = this.currentLocale;
                    this.currentLocale = locale;

                    // Update HTML attributes
                    this.updateHtmlAttributes(locale);

                    // Update translations
                    await this.updateTranslations(locale);

                    // Dispatch locale changed event
                    window.dispatchEvent(new CustomEvent('locale:changed', {
                        detail: {
                            locale,
                            previousLocale,
                            translations: data.translations || {},
                        },
                    }));

                    // Call callback if provided
                    if (config.onLocaleChanged) {
                        config.onLocaleChanged(locale, previousLocale);
                    }

                    // Close dropdown
                    this.isOpen = false;
                } else {
                    throw new Error(data.message || 'Failed to switch locale');
                }
            } catch (error) {
                console.error('Locale switch error:', error);
                this.error = error.message;
            } finally {
                this.isSwitching = false;
            }
        },

        // Update HTML attributes (lang, dir)
        updateHtmlAttributes(locale) {
            const html = document.documentElement;
            html.setAttribute('lang', locale);
            html.setAttribute('dir', this.isRtl(locale) ? 'rtl' : 'ltr');

            // Update body class for RTL
            if (this.isRtl(locale)) {
                document.body.classList.add('rtl');
            } else {
                document.body.classList.remove('rtl');
            }
        },

        // Update translations in the page
        async updateTranslations(locale) {
            // Find all elements with data-i18n attribute
            const elements = document.querySelectorAll('[data-i18n]');

            elements.forEach(element => {
                const key = element.getAttribute('data-i18n');
                const translation = this.getTranslation(key, locale);

                if (translation) {
                    element.textContent = translation;
                }
            });

            // Update placeholder texts
            const placeholders = document.querySelectorAll('[data-i18n-placeholder]');

            placeholders.forEach(element => {
                const key = element.getAttribute('data-i18n-placeholder');
                const translation = this.getTranslation(key, locale);

                if (translation) {
                    element.setAttribute('placeholder', translation);
                }
            });

            // Update title attributes
            const titles = document.querySelectorAll('[data-i18n-title]');

            titles.forEach(element => {
                const key = element.getAttribute('data-i18n-title');
                const translation = this.getTranslation(key, locale);

                if (translation) {
                    element.setAttribute('title', translation);
                }
            });
        },

        // Get translation for key
        getTranslation(key, locale) {
            // This would typically fetch from a translations object
            // For now, return null to indicate translation should be fetched from server
            return null;
        },

        // Setup keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (event) => {
                // Alt+L to toggle locale switcher
                if (event.altKey && event.key === 'l') {
                    event.preventDefault();
                    this.isOpen = !this.isOpen;
                }

                // Escape to close
                if (event.key === 'Escape' && this.isOpen) {
                    this.isOpen = false;
                }
            });
        },

        // Toggle dropdown
        toggle() {
            this.isOpen = !this.isOpen;
        },

        // Close dropdown
        close() {
            this.isOpen = false;
        },

        // Handle locale changed event
        handleLocaleChanged(detail) {
            // Reload table data with new locale
            if (window.Alpine && window.Alpine.store('table')) {
                window.Alpine.store('table').reload();
            }

            // Show success message
            if (detail.locale) {
                const localeInfo = this.availableLocales[detail.locale];
                const message = `Locale changed to ${localeInfo?.native || detail.locale}`;
                
                // Dispatch notification event
                window.dispatchEvent(new CustomEvent('notification:show', {
                    detail: {
                        type: 'success',
                        message,
                    },
                }));
            }
        },
    };
}

/**
 * Initialize locale switcher on page load.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Auto-initialize locale switchers with data-locale-switcher attribute
    const switchers = document.querySelectorAll('[data-locale-switcher]');

    switchers.forEach(element => {
        const config = {
            currentLocale: element.dataset.currentLocale || 'en',
            availableLocales: JSON.parse(element.dataset.availableLocales || '{}'),
            switchUrl: element.dataset.switchUrl || '/locale/switch',
        };

        // Initialize Alpine.js component if Alpine is available
        if (window.Alpine) {
            element.setAttribute('x-data', `localeSwitcher(${JSON.stringify(config)})`);
        }
    });
});

// Export for use in other modules
export default localeSwitcher;

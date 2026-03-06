/**
 * Dark Mode Detection and Management
 * 
 * This module provides dark mode detection, toggle functionality, and persistence
 * for TanStack Table components following CanvaStack standards.
 * 
 * Requirements Validated:
 * - 15.4: Sync with system dark mode preference
 * - 15.4: Add dark mode toggle button (optional)
 * - 15.4: Persist dark mode preference
 * 
 * @package CanvaStack
 * @subpackage Components\Table
 * @version 1.0.0
 */

/**
 * Dark Mode Manager
 * 
 * Handles dark mode detection, toggling, and persistence
 */
export class DarkModeManager {
    /**
     * Constructor
     */
    constructor() {
        this.storageKey = 'canvastack_dark_mode';
        this.darkClass = 'dark';
        this.initialized = false;
    }

    /**
     * Initialize dark mode
     * 
     * This method:
     * 1. Checks localStorage for saved preference
     * 2. Falls back to system preference if no saved preference
     * 3. Applies the dark mode class to document
     * 4. Sets up system preference change listener
     */
    init() {
        if (this.initialized) {
            return;
        }

        // Get saved preference or system preference
        const darkMode = this.getDarkModePreference();
        
        // Apply dark mode
        this.setDarkMode(darkMode, false); // false = don't save to storage (already loaded)
        
        // Listen for system preference changes
        this.watchSystemPreference();
        
        this.initialized = true;
    }

    /**
     * Get dark mode preference
     * 
     * Priority:
     * 1. localStorage (user explicitly set)
     * 2. System preference (prefers-color-scheme)
     * 3. Default to light mode
     * 
     * @returns {boolean} True if dark mode should be enabled
     */
    getDarkModePreference() {
        // Check localStorage first
        const saved = localStorage.getItem(this.storageKey);
        
        if (saved !== null) {
            return saved === 'true';
        }
        
        // Fall back to system preference
        return this.getSystemPreference();
    }

    /**
     * Get system dark mode preference
     * 
     * @returns {boolean} True if system prefers dark mode
     */
    getSystemPreference() {
        if (window.matchMedia) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        
        return false;
    }

    /**
     * Check if dark mode is currently enabled
     * 
     * @returns {boolean} True if dark mode is enabled
     */
    isDarkMode() {
        return document.documentElement.classList.contains(this.darkClass);
    }

    /**
     * Set dark mode
     * 
     * @param {boolean} enabled - True to enable dark mode
     * @param {boolean} persist - True to save to localStorage (default: true)
     */
    setDarkMode(enabled, persist = true) {
        if (enabled) {
            document.documentElement.classList.add(this.darkClass);
        } else {
            document.documentElement.classList.remove(this.darkClass);
        }
        
        // Save to localStorage
        if (persist) {
            localStorage.setItem(this.storageKey, enabled.toString());
        }
        
        // Dispatch custom event for other components
        this.dispatchDarkModeEvent(enabled);
    }

    /**
     * Toggle dark mode
     * 
     * @returns {boolean} New dark mode state
     */
    toggle() {
        const newState = !this.isDarkMode();
        this.setDarkMode(newState);
        return newState;
    }

    /**
     * Watch for system preference changes
     * 
     * When system preference changes, update dark mode if user hasn't
     * explicitly set a preference (no localStorage value)
     */
    watchSystemPreference() {
        if (!window.matchMedia) {
            return;
        }
        
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Modern browsers
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', (e) => {
                this.handleSystemPreferenceChange(e.matches);
            });
        }
        // Legacy browsers
        else if (mediaQuery.addListener) {
            mediaQuery.addListener((e) => {
                this.handleSystemPreferenceChange(e.matches);
            });
        }
    }

    /**
     * Handle system preference change
     * 
     * Only update if user hasn't explicitly set a preference
     * 
     * @param {boolean} prefersDark - True if system prefers dark mode
     */
    handleSystemPreferenceChange(prefersDark) {
        // Only sync if user hasn't explicitly set a preference
        const saved = localStorage.getItem(this.storageKey);
        
        if (saved === null) {
            this.setDarkMode(prefersDark, false); // Don't save to storage
        }
    }

    /**
     * Clear saved preference
     * 
     * This will cause the system to fall back to system preference
     */
    clearPreference() {
        localStorage.removeItem(this.storageKey);
        
        // Revert to system preference
        const systemPreference = this.getSystemPreference();
        this.setDarkMode(systemPreference, false);
    }

    /**
     * Dispatch dark mode change event
     * 
     * @param {boolean} enabled - True if dark mode is enabled
     */
    dispatchDarkModeEvent(enabled) {
        const event = new CustomEvent('darkModeChange', {
            detail: { enabled },
            bubbles: true,
            cancelable: false
        });
        
        document.dispatchEvent(event);
    }
}

/**
 * Create Alpine.js component for dark mode toggle
 * 
 * Usage in Blade:
 * <div x-data="darkModeToggle()">
 *     <button @click="toggle()" class="...">
 *         <i x-show="!isDark" data-lucide="moon"></i>
 *         <i x-show="isDark" data-lucide="sun"></i>
 *     </button>
 * </div>
 */
export function darkModeToggle() {
    return {
        isDark: false,
        manager: null,
        
        init() {
            // Create manager instance
            this.manager = new DarkModeManager();
            this.manager.init();
            
            // Set initial state
            this.isDark = this.manager.isDarkMode();
            
            // Listen for dark mode changes from other sources
            document.addEventListener('darkModeChange', (e) => {
                this.isDark = e.detail.enabled;
            });
        },
        
        toggle() {
            this.isDark = this.manager.toggle();
        },
        
        enable() {
            this.manager.setDarkMode(true);
            this.isDark = true;
        },
        
        disable() {
            this.manager.setDarkMode(false);
            this.isDark = false;
        },
        
        clearPreference() {
            this.manager.clearPreference();
            this.isDark = this.manager.isDarkMode();
        }
    };
}

/**
 * Initialize dark mode on page load
 * 
 * This runs automatically when the module is imported
 */
if (typeof window !== 'undefined') {
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const manager = new DarkModeManager();
            manager.init();
        });
    } else {
        // DOM already loaded
        const manager = new DarkModeManager();
        manager.init();
    }
}

// Export for use in other modules
export default DarkModeManager;

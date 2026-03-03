/**
 * CanvaStack Theme Switcher
 * 
 * Provides global theme switching functionality with localStorage and database persistence.
 * 
 * @module theme-switcher
 * @version 1.0.0
 */

(function(window) {
    'use strict';
    
    /**
     * Theme Switcher Configuration
     */
    const config = {
        storageKey: 'canvastack_theme',
        apiEndpoint: '/api/user/preferences/theme',
        cssVariablePrefix: '--cs-',
        transitionDuration: 300,
        enableTransitions: true,
        enablePersistence: true,
        enableDatabaseSync: true,
    };
    
    /**
     * Current active theme
     * @type {string}
     */
    let currentTheme = null;
    
    /**
     * Available themes registry
     * @type {Object.<string, Object>}
     */
    const themesRegistry = {};
    
    /**
     * Initialize the theme switcher
     * 
     * @param {Object} options - Configuration options
     * @param {string} options.defaultTheme - Default theme name
     * @param {Object} options.themes - Available themes
     * @param {Object} options.config - Additional configuration
     */
    function init(options = {}) {
        // Merge configuration
        Object.assign(config, options.config || {});
        
        // Register themes
        if (options.themes) {
            Object.entries(options.themes).forEach(([name, theme]) => {
                registerTheme(name, theme);
            });
        }
        
        // Load saved theme or use default
        const savedTheme = loadThemeFromStorage();
        const themeToApply = savedTheme || options.defaultTheme || 'gradient';
        
        // Apply theme
        switchTheme(themeToApply, false);
        
        console.log('[CanvaStack] Theme switcher initialized:', themeToApply);
    }
    
    /**
     * Register a theme
     * 
     * @param {string} name - Theme name
     * @param {Object} theme - Theme configuration
     */
    function registerTheme(name, theme) {
        themesRegistry[name] = {
            name: theme.name || name,
            displayName: theme.display_name || theme.displayName || name,
            colors: theme.colors || {},
            fonts: theme.fonts || {},
            gradient: theme.gradient || {},
            ...theme
        };
    }
    
    /**
     * Get a registered theme
     * 
     * @param {string} name - Theme name
     * @returns {Object|null} Theme configuration or null
     */
    function getTheme(name) {
        return themesRegistry[name] || null;
    }
    
    /**
     * Get all registered themes
     * 
     * @returns {Object.<string, Object>} All themes
     */
    function getAllThemes() {
        return { ...themesRegistry };
    }
    
    /**
     * Get current theme name
     * 
     * @returns {string} Current theme name
     */
    function getCurrentTheme() {
        return currentTheme;
    }
    
    /**
     * Switch to a different theme
     * 
     * @param {string} themeName - Theme name to switch to
     * @param {boolean} persist - Whether to persist the theme (default: true)
     * @returns {boolean} Success status
     */
    function switchTheme(themeName, persist = true) {
        const theme = getTheme(themeName);
        
        if (!theme) {
            console.error(`[CanvaStack] Theme not found: ${themeName}`);
            return false;
        }
        
        // Skip if already active
        if (currentTheme === themeName) {
            return true;
        }
        
        // Add transition class
        if (config.enableTransitions) {
            document.documentElement.classList.add('theme-transitioning');
        }
        
        // Apply CSS variables
        applyCssVariables(theme);
        
        // Update current theme
        const previousTheme = currentTheme;
        currentTheme = themeName;
        
        // Update data attribute
        document.documentElement.setAttribute('data-theme', themeName);
        
        // Persist theme
        if (persist && config.enablePersistence) {
            saveThemeToStorage(themeName);
            
            if (config.enableDatabaseSync) {
                syncThemeToDatabase(themeName);
            }
        }
        
        // Dispatch event
        dispatchThemeChangedEvent(themeName, theme, previousTheme);
        
        // Remove transition class after animation
        if (config.enableTransitions) {
            setTimeout(() => {
                document.documentElement.classList.remove('theme-transitioning');
            }, config.transitionDuration);
        }
        
        console.log(`[CanvaStack] Theme switched: ${previousTheme || 'none'} → ${themeName}`);
        
        return true;
    }
    
    /**
     * Apply CSS variables from theme
     * 
     * @param {Object} theme - Theme configuration
     */
    function applyCssVariables(theme) {
        const root = document.documentElement;
        
        // Apply color variables
        if (theme.colors) {
            applyColorVariables(root, theme.colors);
        }
        
        // Apply font variables
        if (theme.fonts) {
            applyFontVariables(root, theme.fonts);
        }
        
        // Apply gradient variables
        if (theme.gradient) {
            applyGradientVariables(root, theme.gradient);
        }
        
        // Apply layout variables
        if (theme.layout) {
            applyLayoutVariables(root, theme.layout);
        }
    }
    
    /**
     * Apply color CSS variables
     * 
     * @param {HTMLElement} root - Root element
     * @param {Object} colors - Color configuration
     */
    function applyColorVariables(root, colors) {
        Object.entries(colors).forEach(([colorName, colorValue]) => {
            if (typeof colorValue === 'object') {
                // Color palette (e.g., primary: { 50: '#...', 100: '#...', ... })
                Object.entries(colorValue).forEach(([shade, value]) => {
                    root.style.setProperty(`${config.cssVariablePrefix}${colorName}-${shade}`, value);
                });
            } else {
                // Single color value
                root.style.setProperty(`${config.cssVariablePrefix}${colorName}`, colorValue);
            }
        });
    }
    
    /**
     * Apply font CSS variables
     * 
     * @param {HTMLElement} root - Root element
     * @param {Object} fonts - Font configuration
     */
    function applyFontVariables(root, fonts) {
        Object.entries(fonts).forEach(([fontName, fontValue]) => {
            root.style.setProperty(`${config.cssVariablePrefix}font-${fontName}`, fontValue);
        });
    }
    
    /**
     * Apply gradient CSS variables
     * 
     * @param {HTMLElement} root - Root element
     * @param {Object} gradients - Gradient configuration
     */
    function applyGradientVariables(root, gradients) {
        Object.entries(gradients).forEach(([gradientName, gradientValue]) => {
            root.style.setProperty(`${config.cssVariablePrefix}gradient-${gradientName}`, gradientValue);
        });
    }
    
    /**
     * Apply layout CSS variables
     * 
     * @param {HTMLElement} root - Root element
     * @param {Object} layout - Layout configuration
     */
    function applyLayoutVariables(root, layout) {
        Object.entries(layout).forEach(([key, value]) => {
            if (typeof value === 'object') {
                Object.entries(value).forEach(([subKey, subValue]) => {
                    root.style.setProperty(`${config.cssVariablePrefix}${key}-${subKey}`, subValue);
                });
            } else {
                root.style.setProperty(`${config.cssVariablePrefix}${key}`, value);
            }
        });
    }
    
    /**
     * Load theme from localStorage
     * 
     * @returns {string|null} Saved theme name or null
     */
    function loadThemeFromStorage() {
        try {
            return localStorage.getItem(config.storageKey);
        } catch (error) {
            console.warn('[CanvaStack] Failed to load theme from localStorage:', error);
            return null;
        }
    }
    
    /**
     * Save theme to localStorage
     * 
     * @param {string} themeName - Theme name to save
     */
    function saveThemeToStorage(themeName) {
        try {
            localStorage.setItem(config.storageKey, themeName);
        } catch (error) {
            console.warn('[CanvaStack] Failed to save theme to localStorage:', error);
        }
    }
    
    /**
     * Sync theme to database via API
     * 
     * @param {string} themeName - Theme name to sync
     */
    async function syncThemeToDatabase(themeName) {
        try {
            const response = await fetch(config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ theme: themeName }),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('[CanvaStack] Theme synced to database:', data);
        } catch (error) {
            console.warn('[CanvaStack] Failed to sync theme to database:', error);
        }
    }
    
    /**
     * Dispatch theme changed event
     * 
     * @param {string} themeName - New theme name
     * @param {Object} theme - Theme configuration
     * @param {string|null} previousTheme - Previous theme name
     */
    function dispatchThemeChangedEvent(themeName, theme, previousTheme) {
        // Custom event for Alpine.js and other listeners
        window.dispatchEvent(new CustomEvent('theme:changed', {
            detail: {
                theme: themeName,
                themeName: theme.displayName,
                themeConfig: theme,
                previousTheme: previousTheme,
                timestamp: Date.now(),
            }
        }));
        
        // Also dispatch on document for compatibility
        document.dispatchEvent(new CustomEvent('theme:changed', {
            detail: {
                theme: themeName,
                themeName: theme.displayName,
                themeConfig: theme,
                previousTheme: previousTheme,
                timestamp: Date.now(),
            }
        }));
    }
    
    /**
     * Preload a theme (useful for smooth transitions)
     * 
     * @param {string} themeName - Theme name to preload
     */
    function preloadTheme(themeName) {
        const theme = getTheme(themeName);
        if (theme) {
            console.log(`[CanvaStack] Theme preloaded: ${themeName}`);
        }
    }
    
    /**
     * Export theme configuration
     * 
     * @param {string} themeName - Theme name to export
     * @param {string} format - Export format ('json' or 'css')
     * @returns {string} Exported theme
     */
    function exportTheme(themeName, format = 'json') {
        const theme = getTheme(themeName);
        
        if (!theme) {
            console.error(`[CanvaStack] Theme not found: ${themeName}`);
            return null;
        }
        
        if (format === 'json') {
            return JSON.stringify(theme, null, 2);
        } else if (format === 'css') {
            return generateCssFromTheme(theme);
        }
        
        return null;
    }
    
    /**
     * Generate CSS from theme configuration
     * 
     * @param {Object} theme - Theme configuration
     * @returns {string} CSS string
     */
    function generateCssFromTheme(theme) {
        let css = ':root {\n';
        
        // Colors
        if (theme.colors) {
            Object.entries(theme.colors).forEach(([colorName, colorValue]) => {
                if (typeof colorValue === 'object') {
                    Object.entries(colorValue).forEach(([shade, value]) => {
                        css += `  ${config.cssVariablePrefix}${colorName}-${shade}: ${value};\n`;
                    });
                } else {
                    css += `  ${config.cssVariablePrefix}${colorName}: ${colorValue};\n`;
                }
            });
        }
        
        // Fonts
        if (theme.fonts) {
            Object.entries(theme.fonts).forEach(([fontName, fontValue]) => {
                css += `  ${config.cssVariablePrefix}font-${fontName}: ${fontValue};\n`;
            });
        }
        
        // Gradients
        if (theme.gradient) {
            Object.entries(theme.gradient).forEach(([gradientName, gradientValue]) => {
                css += `  ${config.cssVariablePrefix}gradient-${gradientName}: ${gradientValue};\n`;
            });
        }
        
        css += '}\n';
        
        return css;
    }
    
    // Expose public API
    window.CanvastackTheme = {
        init,
        switchTheme,
        getCurrentTheme,
        getTheme,
        getAllThemes,
        registerTheme,
        preloadTheme,
        exportTheme,
        config,
    };
    
    // Alias for convenience
    window.switchTheme = switchTheme;
    
})(window);


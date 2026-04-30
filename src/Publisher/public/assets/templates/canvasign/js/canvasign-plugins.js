/**
 * Canvasign Form Plugin Integration
 *
 * Initialises Flatpickr (date / datetime / daterange) and Choices.js
 * (enhanced selects) for the canvasign Bootstrap 5 template.
 *
 * Features:
 *  - Auto-init via CSS class selectors on DOMContentLoaded
 *  - Theme-aware: re-applies dark/light skin on `themechange` event
 *  - Graceful degradation when libraries are not loaded
 *
 * Depends on (loaded via config bottom.first.js or CDN):
 *  - flatpickr  (https://cdn.jsdelivr.net/npm/flatpickr)
 *  - Choices.js (https://cdn.jsdelivr.net/npm/choices.js/…)
 *
 * @author  wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /** Returns the current theme string ('dark' | 'light'). */
    function currentTheme() {
        return document.documentElement.getAttribute('data-bs-theme') || 'dark';
    }

    /* ------------------------------------------------------------------ */
    /* 1. Flatpickr — date / datetime / daterange                         */
    /* ------------------------------------------------------------------ */

    var flatpickrInstances = [];

    /**
     * Initialise Flatpickr on all matching elements.
     *
     * @param {string}  selector  CSS selector
     * @param {object}  options   Flatpickr options
     */
    function initFlatpickr(selector, options) {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll(selector).forEach(function (el) {
            /* Skip if already initialised */
            if (el._flatpickr) return;

            var instance = flatpickr(el, options);
            flatpickrInstances.push(instance);
        });
    }

    /** Apply the correct Flatpickr theme class to the calendar element. */
    function applyFlatpickrTheme(theme) {
        /* Flatpickr renders its calendar outside the normal DOM tree.
           We toggle a class on the calendar container to match the theme. */
        document.querySelectorAll('.flatpickr-calendar').forEach(function (cal) {
            if (theme === 'dark') {
                cal.classList.add('flatpickr-dark');
                cal.classList.remove('flatpickr-light');
            } else {
                cal.classList.add('flatpickr-light');
                cal.classList.remove('flatpickr-dark');
            }
        });
    }

    function initAllFlatpickr() {
        var theme = currentTheme();

        /* Date picker */
        initFlatpickr('.date-picker', {
            dateFormat:  'Y-m-d',
            allowInput:  true,
            disableMobile: false,
            onReady: function () { applyFlatpickrTheme(theme); },
        });

        /* Datetime picker */
        initFlatpickr('.datetime-picker', {
            dateFormat:  'Y-m-d H:i:S',
            enableTime:  true,
            time_24hr:   true,
            allowInput:  true,
            onReady: function () { applyFlatpickrTheme(theme); },
        });

        /* Date range picker */
        initFlatpickr('.daterange-picker', {
            mode:        'range',
            dateFormat:  'Y-m-d',
            allowInput:  true,
            onReady: function () { applyFlatpickrTheme(theme); },
        });
    }

    /* ------------------------------------------------------------------ */
    /* 2. Choices.js — enhanced select elements                           */
    /* ------------------------------------------------------------------ */

    var choicesInstances = [];

    /**
     * Initialise Choices.js on all matching select elements.
     *
     * @param {string} selector  CSS selector
     * @param {object} options   Choices.js options
     */
    function initChoices(selector, options) {
        if (typeof Choices === 'undefined') {
            console.warn('⚠️ Choices.js not loaded, skipping initialization for:', selector);
            return;
        }

        var elements = document.querySelectorAll(selector);
        console.log('🔍 initChoices called for selector:', selector, 'Found:', elements.length, 'elements');

        elements.forEach(function (el, index) {
            /* Skip if already initialised */
            if (el.classList.contains('choices__input')) {
                console.log('⏭️ Skipping element', index, '- already has choices__input class');
                return;
            }
            if (el.closest('.choices')) {
                console.log('⏭️ Skipping element', index, '- already wrapped in .choices container');
                return;
            }

            try {
                console.log('🎯 Initializing Choices.js for element', index, ':', el.id, el.className);
                var instance = new Choices(el, Object.assign({}, options, {
                    // Dispatch native 'change' event on the original <select>
                    // so that jQuery .on('change') listeners (cascading filter) work
                    callbackOnChange: function(value) {
                        // Dispatch native change event
                        var event = new Event('change', { bubbles: true });
                        el.dispatchEvent(event);
                        
                        // Also trigger jQuery change event for legacy code
                        $(el).trigger('change');
                    }
                }));
                choicesInstances.push(instance);
                
                // Store instance reference on element for easy access
                el._choices = instance;
                el.choicesInstance = instance;
                $(el).data('choices', instance);
                
                // Store instance on wrapper for alternative access
                setTimeout(function() {
                    var wrapper = el.closest('.choices');
                    if (wrapper) {
                        wrapper._choices = instance;
                    }
                }, 100);
                
                console.log('✅ Successfully initialized Choices.js for:', el.id);
                
            } catch (e) {
                console.error('❌ Choices.js init failed for element', index, ':', el.id, e);
            }
        });
    }

    /** Apply Bootstrap 5 / canvasign theme classes to Choices.js containers. */
    function applyChoicesTheme(theme) {
        document.querySelectorAll('.choices').forEach(function (container) {
            if (theme === 'dark') {
                container.classList.add('choices--dark');
                container.classList.remove('choices--light');
            } else {
                container.classList.add('choices--light');
                container.classList.remove('choices--dark');
            }
        });
    }

    function initAllChoices() {
        var theme = currentTheme();
        
        console.log('🔧 initAllChoices() called');

        // Single select options (simplified - no custom classNames to avoid whitespace issues)
        var singleSelectOptions = {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            removeItemButton: false,
            // CRITICAL: Prevent auto-selection of first option
            placeholderValue: '',
            searchPlaceholderValue: 'Type to search...'
        };

        // Multi-select options (simplified - no custom classNames to avoid whitespace issues)
        var multiSelectOptions = {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            removeItemButton: true,  // CRITICAL for multi-select
            // CRITICAL: Prevent auto-selection of first option
            placeholderValue: '',
            searchPlaceholderValue: 'Type to search...'
        };

        // Inisialisasi sesuai design referensi canvasign
        initChoices('#choices-single', singleSelectOptions);
        initChoices('#choices-multi', multiSelectOptions);
        
        // Selector tambahan untuk kompatibilitas dengan CanvaStack
        initChoices('select.choices-select', singleSelectOptions);
        initChoices('select[data-choices]', singleSelectOptions);
        initChoices('select[data-choices][multiple]', multiSelectOptions);
        
        // CRITICAL: Initialize mapping page field_value multi-select
        // Use attribute selector because class may not have space before field_value
        console.log('🔍 Looking for select[class*="field_value"][multiple]:', document.querySelectorAll('select[class*="field_value"][multiple]').length);
        console.log('🔍 Looking for select[class*="field_name"]:', document.querySelectorAll('select[class*="field_name"]').length);
        initChoices('select[class*="field_value"][multiple]', multiSelectOptions);
        initChoices('select[class*="field_name"]', singleSelectOptions);

        applyChoicesTheme(theme);
    }

    /* ------------------------------------------------------------------ */
    /* 3. Theme change handler                                             */
    /* ------------------------------------------------------------------ */

    window.addEventListener('themechange', function (e) {
        var theme = (e.detail && e.detail.theme) ? e.detail.theme : currentTheme();
        applyFlatpickrTheme(theme);
        applyChoicesTheme(theme);
    });

    /* ------------------------------------------------------------------ */
    /* 4. Bootstrap 5 form validation helper                              */
    /* ------------------------------------------------------------------ */

    function initFormValidation() {
        document.querySelectorAll('.needs-validation').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    /* ------------------------------------------------------------------ */
    /* 5. Bootstrap 5 tooltip initialisation                              */
    /* ------------------------------------------------------------------ */

    function initTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    /* ------------------------------------------------------------------ */
    /* 6. Bootstrap 5 popover initialisation                              */
    /* ------------------------------------------------------------------ */

    function initPopovers() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el);
        });
    }

    /* ------------------------------------------------------------------ */
    /* 7. DOMContentLoaded — run all initialisers                         */
    /* ------------------------------------------------------------------ */

    document.addEventListener('DOMContentLoaded', function () {
        initAllFlatpickr();
        initAllChoices();
        initFormValidation();
        initTooltips();
        initPopovers();
    });
    
    // Re-initialize Choices.js when Bootstrap tabs are shown
    document.addEventListener('shown.bs.tab', function(e) {
        console.log('🔄 Tab shown, re-initializing Choices.js');
        initAllChoices();
    });
    
    // Also listen for Bootstrap 4 tab events (for compatibility)
    $(document).on('shown.bs.tab', 'a[data-toggle="tab"], a[data-bs-toggle="tab"]', function(e) {
        console.log('🔄 Tab shown (jQuery), re-initializing Choices.js');
        setTimeout(function() {
            initAllChoices();
        }, 100);
    });

    /* ------------------------------------------------------------------ */
    /* 8. Chosen Compatibility Layer (for legacy code)                    */
    /* ------------------------------------------------------------------ */

    /**
     * Provide a minimal Chosen API compatibility layer that delegates to Choices.js
     * This allows legacy code that calls .chosen() to work without modification
     */
    if (typeof $.fn.chosen === 'undefined') {
        $.fn.chosen = function(action) {
            return this.each(function() {
                var $el = $(this);
                
                if (action === 'destroy') {
                    // Destroy Choices.js instance if exists
                    if (this.choicesInstance) {
                        this.choicesInstance.destroy();
                        this.choicesInstance = null;
                    }
                } else if (typeof action === 'object' || action === undefined) {
                    // Initialize Choices.js if not already initialized
                    if (!this.choicesInstance && typeof Choices !== 'undefined') {
                        // Merge default options with passed options
                        var defaultOptions = {
                            searchEnabled: true,
                            itemSelectText: '',
                            shouldSort: false,
                            removeItemButton: true,  // CRITICAL: Enable for multi-select
                            // CRITICAL: Prevent auto-selection of first option
                            placeholderValue: '',
                            searchPlaceholderValue: 'Type to search...'
                        };
                        var options = Object.assign({}, defaultOptions, action || {});
                        
                        this.choicesInstance = new Choices(this, options);
                        
                        // Store instance for easy access
                        $el.data('choices', this.choicesInstance);
                        
                        // CRITICAL: Explicitly clear value after initialization to prevent auto-selection
                        var self = this;
                        setTimeout(function() {
                            if (self.choicesInstance && !$el.attr('multiple')) {
                                // Only clear for single select
                                self.choicesInstance.setChoiceByValue('');
                                console.log('✅ Cleared auto-selection for:', $el.attr('id'));
                            }
                        }, 100);
                    }
                }
            });
        };
    }

    /* ------------------------------------------------------------------ */
    /* 9. Public API (for manual re-init after dynamic content load)      */
    /* ------------------------------------------------------------------ */

    window.CanvasignPlugins = {
        choicesInstances: choicesInstances,
        flatpickrInstances: flatpickrInstances,
        initFlatpickr:    initAllFlatpickr,
        initChoices:      initAllChoices,
        initTooltips:     initTooltips,
        initPopovers:     initPopovers,
        applyTheme: function (theme) {
            applyFlatpickrTheme(theme || currentTheme());
            applyChoicesTheme(theme || currentTheme());
        },
        // CRITICAL: Add manual re-init function for dynamic content
        reinitChoices: function() {
            console.log('🔄 Manual Choices.js re-initialization');
            initAllChoices();
        }
    };

})();

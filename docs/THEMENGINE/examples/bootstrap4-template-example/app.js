/**
 * app.js — Bootstrap 4 Template Application Script Example
 * =========================================================
 * Template: default (Bootstrap 4)
 * Location: public/assets/templates/default/js/app.js
 *
 * This file initializes all Bootstrap 4 plugins and custom UI behavior.
 * It is loaded via config/canvastack.templates.php:
 *   default.position.bottom.last.js
 *
 * Load order (this file loads after):
 *   1. jQuery (top.js)
 *   2. Bootstrap 4 bundle (top.js)
 *   3. Chosen.js (bottom.first.js)
 *   4. Flatpickr (bottom.first.js)
 *   5. MetisMenu (bottom.first.js)
 *   6. canvastack-modal-adapter.js (bottom.last.js — before this file)
 *   7. canvastack-tooltip-adapter.js (bottom.last.js — before this file)
 *   8. THIS FILE (bottom.last.js)
 *
 * Bootstrap 4 specific:
 *   - Uses data-toggle="tooltip" (NOT data-bs-toggle)
 *   - Uses data-toggle="popover" (NOT data-bs-toggle)
 *   - Uses $.fn.modal() API (NOT bootstrap.Modal)
 *   - Uses $.fn.tooltip() API (NOT bootstrap.Tooltip)
 */

(function ($) {
    'use strict';

    // ── App namespace ──────────────────────────────────────────────────────
    var App = {

        /**
         * Initialize all components.
         * Called on DOM ready.
         */
        init: function () {
            this.initTooltips();
            this.initPopovers();
            this.initChosenSelects();
            this.initDatePickers();
            this.initBackToTop();
            this.initFullscreen();
            this.initCopyrightYear();
            this.initPreloader();
        },

        // ── Bootstrap 4 Tooltips ───────────────────────────────────────────

        /**
         * Initialize Bootstrap 4 tooltips.
         *
         * Bootstrap 4: data-toggle="tooltip"
         * Bootstrap 5: data-bs-toggle="tooltip"
         *
         * The canvastack-tooltip-adapter.js handles this automatically
         * based on the active template. This is a manual fallback.
         */
        initTooltips: function () {
            // Bootstrap 4 tooltip initialization
            // Selector: elements with data-toggle="tooltip"
            $('[data-toggle="tooltip"]').tooltip({
                trigger: 'hover focus', // show on hover and keyboard focus
                placement: 'top',       // default placement
                container: 'body'       // append to body to avoid z-index issues
            });

            if (window.APP_DEBUG) {
                console.log('[App] Tooltips initialized:', $('[data-toggle="tooltip"]').length);
            }
        },

        // ── Bootstrap 4 Popovers ──────────────────────────────────────────

        /**
         * Initialize Bootstrap 4 popovers.
         *
         * Bootstrap 4: data-toggle="popover"
         * Bootstrap 5: data-bs-toggle="popover"
         */
        initPopovers: function () {
            $('[data-toggle="popover"]').popover({
                trigger: 'click',
                html: true,     // allow HTML content in popover body
                container: 'body'
            });

            // Close popover when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('[data-toggle="popover"]').length) {
                    $('[data-toggle="popover"]').popover('hide');
                }
            });
        },

        // ── Chosen.js Select Enhancement ──────────────────────────────────

        /**
         * Initialize Chosen.js for select elements.
         *
         * Bootstrap 4 uses Chosen.js for select enhancement.
         * Bootstrap 5 uses Choices.js instead.
         * TailwindCSS uses native select or Tom Select.
         *
         * DefaultAdapter::getSelectBoxClass() returns:
         *   'chosen-select-deselect chosen-selectbox'
         */
        initChosenSelects: function () {
            // Check if Chosen.js is loaded
            if (typeof $.fn.chosen === 'undefined') {
                if (window.APP_DEBUG) {
                    console.warn('[App] Chosen.js not loaded — skipping select initialization');
                }
                return;
            }

            // Single select with deselect option
            $('.chosen-select-deselect').chosen({
                allow_single_deselect: true,  // show × to clear selection
                width: '100%',                // responsive width
                search_contains: true,        // search matches anywhere in text
                no_results_text: 'No results found for'
            });

            // Multi-select
            $('.chosen-select').chosen({
                width: '100%',
                search_contains: true,
                no_results_text: 'No results found for'
            });

            if (window.APP_DEBUG) {
                console.log('[App] Chosen.js initialized:', $('.chosen-select-deselect, .chosen-select').length, 'selects');
            }
        },

        // ── Flatpickr Date Pickers ─────────────────────────────────────────

        /**
         * Initialize Flatpickr date/time pickers.
         * Flatpickr is framework-agnostic — works with Bootstrap 4, 5, and Tailwind.
         */
        initDatePickers: function () {
            // Check if Flatpickr is loaded
            if (typeof flatpickr === 'undefined') {
                return;
            }

            // Date picker (date only)
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: { firstDayOfWeek: 1 } // Monday first
            });

            // Date-time picker
            flatpickr('.datetime-picker', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
                allowInput: true,
                time_24hr: true
            });

            // Date range picker
            flatpickr('.daterange-picker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        },

        // ── Back to Top Button ─────────────────────────────────────────────

        /**
         * Show/hide back-to-top button based on scroll position.
         */
        initBackToTop: function () {
            var $backTop = $('#back-top');

            // Show button after scrolling 300px
            $(window).on('scroll', function () {
                if ($(this).scrollTop() > 300) {
                    $backTop.addClass('show');
                } else {
                    $backTop.removeClass('show');
                }
            });

            // Smooth scroll to top on click
            $backTop.on('click keypress', function (e) {
                if (e.type === 'click' || e.which === 13) {
                    $('html, body').animate({ scrollTop: 0 }, 400);
                }
            });
        },

        // ── Fullscreen Toggle ──────────────────────────────────────────────

        /**
         * Toggle browser fullscreen mode.
         */
        initFullscreen: function () {
            $('#full-view').on('click', function () {
                var el = document.documentElement;
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                } else if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                }
                $('#full-view').hide();
                $('#full-view-exit').show();
            });

            $('#full-view-exit').on('click', function () {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
                $('#full-view-exit').hide();
                $('#full-view').show();
            });
        },

        // ── Copyright Year ─────────────────────────────────────────────────

        /**
         * Set the current year in the footer copyright.
         * The #copyright span is in footer.blade.php.
         */
        initCopyrightYear: function () {
            var year = new Date().getFullYear();
            $('#copyright').text(year);
        },

        // ── Page Preloader ─────────────────────────────────────────────────

        /**
         * Hide the page preloader after DOM is ready.
         * The #preloader div is in layout.blade.php.
         */
        initPreloader: function () {
            $('#preloader').fadeOut(500, function () {
                $(this).remove();
            });
        }

    };

    // ── Initialize on DOM ready ────────────────────────────────────────────
    $(document).ready(function () {
        App.init();

        if (window.APP_DEBUG) {
            console.log('[App] Bootstrap 4 template initialized');
            console.log('[App] Active template: default');
            console.log('[App] jQuery version:', $.fn.jquery);
            console.log('[App] Bootstrap version:', $.fn.tooltip.Constructor.VERSION);
        }
    });

    // ── Re-initialize after AJAX content load ─────────────────────────────
    // Call this after loading new content via AJAX
    window.CanvaStackApp = {
        reinitialize: function () {
            App.initTooltips();
            App.initChosenSelects();
            App.initDatePickers();
        }
    };

})(jQuery);

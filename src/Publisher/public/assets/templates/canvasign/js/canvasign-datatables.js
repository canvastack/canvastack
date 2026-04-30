/**
 * Canvasign DataTables Bootstrap 5 Integration
 *
 * Extends the base CanvastackDataTables module with Bootstrap 5 styling,
 * theme-aware rendering, and responsive behaviour for the canvasign template.
 *
 * Depends on:
 *   - canvastack-datatables.js  (base module)
 *   - DataTables with Bootstrap 5 bundle (CDN)
 *   - theme.js (sets data-bs-theme on <html>)
 *
 * @author  wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* 1. Bootstrap 5 default DataTables options                           */
    /* ------------------------------------------------------------------ */

    /**
     * Returns a DataTables defaults object tuned for Bootstrap 5 and the
     * canvasign theme.  Merge this into your per-table config as needed.
     *
     * @returns {object}
     */
    function getBootstrap5Defaults() {
        return {
            /* Bootstrap 5 table classes */
            classes: {
                sTable:      'table table-hover table-bordered align-middle canvastack-table',
                sWrapper:    'dataTables_wrapper dt-bootstrap5',
                sFilter:     'dataTables_filter',
                sInfo:       'dataTables_info',
                sPaging:     'dataTables_paginate',
                sLength:     'dataTables_length',
                sProcessing: 'dataTables_processing',
            },

            /* Export buttons with Bootstrap 5 styling */
            buttons: [
                { extend: 'copy',    className: 'btn btn-soft btn-sm' },
                { extend: 'csv',     className: 'btn btn-soft btn-sm' },
                { extend: 'excel',   className: 'btn btn-soft btn-sm' },
                { extend: 'pdf',     className: 'btn btn-soft btn-sm' },
                { extend: 'print',   className: 'btn btn-soft btn-sm' },
                { extend: 'colvis',  className: 'btn btn-soft btn-sm', text: 'Columns' },
            ],

            /* Responsive mode */
            responsive: true,

            /* DOM layout: length, buttons, filter, table, info, pagination */
            dom: "<'row align-items-center mb-2'<'col-sm-6'l><'col-sm-6 text-end'B>>" +
                 "<'row'<'col-12'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row align-items-center mt-2'<'col-sm-5'i><'col-sm-7'p>>",

            /* Bootstrap 5 pagination */
            pagingType: 'full_numbers',

            /* Language */
            language: {
                search:         '',
                searchPlaceholder: 'Search…',
                lengthMenu:     '_MENU_ per page',
                info:           'Showing _START_–_END_ of _TOTAL_',
                infoEmpty:      'No records',
                infoFiltered:   '(filtered from _MAX_)',
                paginate: {
                    first:    '«',
                    previous: '‹',
                    next:     '›',
                    last:     '»',
                },
            },
        };
    }

    /* ------------------------------------------------------------------ */
    /* 2. Apply Bootstrap 5 classes after DataTable initialises            */
    /* ------------------------------------------------------------------ */

    /**
     * Applies Bootstrap 5 classes to a DataTables wrapper element.
     *
     * @param {jQuery} $wrapper
     */
    function applyBootstrap5Classes($wrapper) {
        /* Pagination buttons */
        $wrapper.find('.paginate_button').addClass('page-link');
        $wrapper.find('.paginate_button.current').closest('li').addClass('active');
        $wrapper.find('.paginate_button.disabled').closest('li').addClass('disabled');

        /* Search input */
        $wrapper.find('.dataTables_filter input[type="search"]')
            .addClass('form-control form-control-sm')
            .attr('placeholder', 'Search…');

        /* Length select */
        $wrapper.find('.dataTables_length select').addClass('form-select form-select-sm');

        /* Export buttons */
        $wrapper.find('.dt-buttons .btn').addClass('btn-sm');
    }

    /* ------------------------------------------------------------------ */
    /* 3. Theme-aware re-styling                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Re-applies theme-sensitive styles to all DataTables on the page
     * when the canvasign theme changes.
     */
    function onThemeChange() {
        var theme = document.documentElement.getAttribute('data-bs-theme') || 'dark';

        /* DataTables uses Bootstrap's table classes which already respond to
           data-bs-theme, so a simple redraw is enough to pick up the new
           CSS custom-property values. */
        if (typeof $.fn.dataTable !== 'undefined') {
            $.fn.dataTable.tables({ visible: true, api: true }).draw(false);
        }
    }

    /* ------------------------------------------------------------------ */
    /* 4. Canvasign-specific initialise helper                             */
    /* ------------------------------------------------------------------ */

    /**
     * Initialise a DataTable with Bootstrap 5 defaults merged in.
     * Falls back gracefully if CanvastackDataTables is not loaded.
     *
     * @param {string} tableId
     * @param {object} config   - Same shape as CanvastackDataTables.initialize()
     * @returns {object|null}   DataTables API instance
     */
    window.canvasignInitDataTable = function (tableId, config) {
        config = config || {};
        config.datatableConfig = config.datatableConfig || {};

        /* Merge Bootstrap 5 defaults (user config wins) */
        var defaults = getBootstrap5Defaults();
        config.datatableConfig = Object.assign({}, defaults, config.datatableConfig);

        /* Preserve user-supplied buttons if provided */
        if (config.datatableConfig.buttonsJs || Array.isArray(config.datatableConfig.buttons)) {
            /* keep as-is */
        } else {
            config.datatableConfig.buttons = defaults.buttons;
        }

        var dtApi = null;

        if (typeof CanvastackDataTables !== 'undefined') {
            dtApi = CanvastackDataTables.initialize(tableId, config);
        } else if (typeof $.fn.dataTable !== 'undefined') {
            /* Fallback: plain DataTables init */
            dtApi = $('#' + tableId).DataTable(config.datatableConfig);
        } else {
            console.error('canvasignInitDataTable: DataTables library not loaded.');
            return null;
        }

        if (dtApi) {
            var $wrapper = $('#' + tableId).closest('.dataTables_wrapper');
            applyBootstrap5Classes($wrapper);

            /* Re-apply on every draw (pagination, search, sort) */
            $('#' + tableId).on('draw.dt', function () {
                applyBootstrap5Classes($('#' + tableId).closest('.dataTables_wrapper'));
            });
        }

        return dtApi;
    };

    /* ------------------------------------------------------------------ */
    /* 5. Listen for theme changes                                         */
    /* ------------------------------------------------------------------ */

    window.addEventListener('themechange', onThemeChange);

    /* ------------------------------------------------------------------ */
    /* 6. Auto-init tables marked with data-canvasign-datatable            */
    /* ------------------------------------------------------------------ */

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table[data-canvasign-datatable]').forEach(function (el) {
            var tableId = el.id;
            if (!tableId) return;

            var configAttr = el.getAttribute('data-canvasign-datatable');
            var config = {};
            if (configAttr && configAttr !== 'true') {
                try { config = JSON.parse(configAttr); } catch (e) { /* ignore */ }
            }

            window.canvasignInitDataTable(tableId, config);
        });
    });

})();

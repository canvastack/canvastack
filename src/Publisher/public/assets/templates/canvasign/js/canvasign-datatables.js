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

    /**
     * Wraps top and bottom controls in proper containers for better layout control.
     * This fixes the issue where pagination appears next to top controls.
     *
     * @param {jQuery} $wrapper
     */
    function wrapDataTableControls($wrapper) {
        // Check if already wrapped
        if ($wrapper.find('.canvastack-dt-top-controls').length > 0) {
            // Already wrapped, just reorganize bottom controls
            reorganizeBottomControls($wrapper);
            syncScrollBehavior($wrapper);
            return;
        }

        // Wrap top controls (Show entries, Buttons, Search)
        // Find all top control elements
        var $length = $wrapper.children('.dataTables_length');
        var $buttons = $wrapper.children('.dt-buttons');
        var $filter = $wrapper.children('.dataTables_filter');
        
        if ($length.length > 0 || $buttons.length > 0 || $filter.length > 0) {
            // Create wrapper
            var $topWrapper = $('<div class="canvastack-dt-top-controls"></div>');
            
            // Insert wrapper before first element
            if ($length.length > 0) {
                $length.before($topWrapper);
                $topWrapper.append($length);
            } else if ($buttons.length > 0) {
                $buttons.before($topWrapper);
            } else if ($filter.length > 0) {
                $filter.before($topWrapper);
            }
            
            // Append other elements
            if ($buttons.length > 0) $topWrapper.append($buttons);
            if ($filter.length > 0) $topWrapper.append($filter);
        }

        // Wrap table (if not already wrapped)
        // Check for FixedColumns - if present, wrap the entire scroll container
        var $scrollContainer = $wrapper.children('.dataTables_scroll');
        var $tableWrapper = $wrapper.children('.canvastack-wrapper-table');
        
        if ($scrollContainer.length > 0) {
            // FixedColumns creates a scroll container - wrap that instead
            if (!$scrollContainer.parent().hasClass('canvastack-dt-table-container')) {
                $scrollContainer.wrap('<div class="canvastack-dt-table-container"></div>');
            }
        } else if ($tableWrapper.length > 0) {
            // Normal table wrapper
            if (!$tableWrapper.parent().hasClass('canvastack-dt-table-container')) {
                $tableWrapper.wrap('<div class="canvastack-dt-table-container"></div>');
            }
        }

        // Reorganize bottom controls
        reorganizeBottomControls($wrapper);
        
        // Sync scroll behavior for DataTables scroll structure
        syncScrollBehavior($wrapper);
    }
    
    /**
     * Synchronize scroll between scrollHead and scrollBody
     * 
     * @param {jQuery} $wrapper
     */
    function syncScrollBehavior($wrapper) {
        var $scrollBody = $wrapper.find('.dataTables_scrollBody');
        var $scrollHead = $wrapper.find('.dataTables_scrollHead');
        
        if ($scrollBody.length > 0 && $scrollHead.length > 0) {
            // Remove any existing scroll listeners to avoid duplicates
            $scrollBody.off('scroll.dtSync');
            
            // Sync horizontal scroll from body to head
            $scrollBody.on('scroll.dtSync', function() {
                $scrollHead.scrollLeft($(this).scrollLeft());
            });
        }
    }
    
    /**
     * Adjust FixedColumns positioning after table is fully rendered
     * This fixes the issue where FixedColumns doesn't work properly on initial load
     * 
     * @param {jQuery} $wrapper
     */
    function adjustFixedColumns($wrapper) {
        var $table = $wrapper.find('table.dataTable');
        if ($table.length === 0) return;
        
        // Check if table has FixedColumns active
        if (!$.fn.DataTable.isDataTable($table)) return;
        
        try {
            var dtApi = $table.DataTable();
            var settings = dtApi.settings()[0];
            
            // Check if FixedColumns extension is active
            if (!settings._fixedColumns) return;
            
            console.log('Adjusting FixedColumns for table:', $table.attr('id'));
            
            // Method 1: Try to use FixedColumns API directly (safest)
            if (dtApi.fixedColumns && typeof dtApi.fixedColumns === 'function') {
                try {
                    var fc = dtApi.fixedColumns();
                    if (fc && typeof fc.adjust === 'function') {
                        fc.adjust();
                        console.log('FixedColumns adjusted via API');
                        return; // Success, exit
                    }
                } catch (e) {
                    console.log('FixedColumns API adjust failed, trying columns.adjust()');
                }
            }
            
            // Method 2: Try columns.adjust() (safer than resize)
            try {
                dtApi.columns.adjust();
                console.log('FixedColumns adjusted via columns.adjust()');
            } catch (e) {
                console.log('columns.adjust() failed:', e.message);
            }
            
        } catch (e) {
            // Silently fail - don't break the page
            console.log('adjustFixedColumns error:', e.message);
        }
    }
    
    /**
     * Adjust error message width to match visible scroll container
     * This ensures error message doesn't overflow beyond visible area
     * 
     * @param {jQuery} $wrapper
     */
    function adjustErrorMessageWidth($wrapper) {
        var $errorRow = $wrapper.find('.canvastack-table-error-row');
        if ($errorRow.length === 0) return;
        
        var $errorContainer = $errorRow.find('.canvastack-error-container');
        if ($errorContainer.length === 0) return;
        
        console.log('=== ADJUST ERROR MESSAGE WIDTH ===');
        
        // Find the scroll container or table container
        var $scrollBody = $wrapper.find('.dataTables_scrollBody');
        var $tableContainer = $wrapper.find('.canvastack-dt-table-container');
        
        console.log('ScrollBody exists:', $scrollBody.length);
        console.log('TableContainer exists:', $tableContainer.length);
        console.log('Wrapper width:', $wrapper.width());
        
        var containerWidth = 0;
        
        if ($scrollBody.length > 0) {
            // Use scroll body width (visible area)
            containerWidth = $scrollBody.width();
            console.log('Using ScrollBody width:', containerWidth);
        } else if ($tableContainer.length > 0) {
            // Use table container width
            containerWidth = $tableContainer.width();
            console.log('Using TableContainer width:', containerWidth);
        } else {
            // Fallback to wrapper width
            containerWidth = $wrapper.width();
            console.log('Using Wrapper width (fallback):', containerWidth);
        }
        
        if (containerWidth > 0) {
            // Set max-width to container width minus padding
            var maxWidth = containerWidth - 80; // 40px padding on each side
            $errorContainer.css('max-width', maxWidth + 'px');
            console.log('Error message max-width set to:', maxWidth + 'px');
            console.log('Error container actual width:', $errorContainer.width());
        } else {
            console.log('ERROR: containerWidth is 0, cannot adjust');
        }
        
        console.log('=== END ADJUST ===');
    }
    
    /**
     * Reorganize bottom controls - move dynamic info to bottom
     * 
     * @param {jQuery} $wrapper
     */
    function reorganizeBottomControls($wrapper) {
        // Find ALL info and pagination elements
        var $allInfo = $wrapper.find('.dataTables_info');
        var $allPaginate = $wrapper.find('.dataTables_paginate');
        
        // Find or create bottom controls wrapper
        var $bottomWrapper = $wrapper.find('.canvastack-dt-bottom-controls');
        
        if ($bottomWrapper.length === 0) {
            // Create bottom wrapper at the end
            $bottomWrapper = $('<div class="canvastack-dt-bottom-controls"></div>');
            $wrapper.append($bottomWrapper);
        }
        
        // Move ALL info elements to bottom wrapper (the dynamic one will be the correct one)
        $allInfo.each(function() {
            var $info = $(this);
            // Only move if not already in bottom wrapper
            if (!$info.parent().hasClass('canvastack-dt-bottom-controls')) {
                $bottomWrapper.append($info);
            }
        });
        
        // Move ALL pagination elements to bottom wrapper
        $allPaginate.each(function() {
            var $paginate = $(this);
            // Only move if not already in bottom wrapper
            if (!$paginate.parent().hasClass('canvastack-dt-bottom-controls')) {
                $bottomWrapper.append($paginate);
            }
        });
        
        // Now remove duplicates - keep only the LAST one (most recent/dynamic)
        var $infosInBottom = $bottomWrapper.find('.dataTables_info');
        if ($infosInBottom.length > 1) {
            $infosInBottom.not(':last').remove();
        }
        
        var $paginatesInBottom = $bottomWrapper.find('.dataTables_paginate');
        if ($paginatesInBottom.length > 1) {
            $paginatesInBottom.not(':last').remove();
        }
        
        // Sync info text from sr-only to dataTables_info
        syncInfoText($wrapper);
        
        // Clean up orphans
        cleanupOrphans($wrapper);
    }
    
    /**
     * Sync info text from sr-only pagination-status to dataTables_info
     * 
     * @param {jQuery} $wrapper
     */
    function syncInfoText($wrapper) {
        // Find the sr-only pagination status (the dynamic one)
        var $paginationStatus = $wrapper.closest('.canvastack-table-box-' + $wrapper.find('table').attr('id'))
            .find('.table-pagination-status');
        
        // If not found, try alternative selector
        if ($paginationStatus.length === 0) {
            $paginationStatus = $wrapper.parent().find('.table-pagination-status');
        }
        
        // Find the dataTables_info element
        var $info = $wrapper.find('.dataTables_info');
        
        // Sync text if both exist
        if ($paginationStatus.length > 0 && $info.length > 0) {
            var dynamicText = $paginationStatus.text().trim();
            if (dynamicText && dynamicText !== '') {
                $info.text(dynamicText);
            }
        }
    }
    
    /**
     * Clean up orphan elements
     * 
     * @param {jQuery} $wrapper
     */
    function cleanupOrphans($wrapper) {
        // Hide caption "Loading records..." after table is loaded
        var $table = $wrapper.find('table.dataTable');
        if ($table.length > 0) {
            var $caption = $table.find('caption');
            if ($caption.length > 0) {
                // Check if table has data
                var hasData = $table.find('tbody tr').length > 0;
                if (hasData) {
                    // Hide the loading message span
                    $caption.find('.table-record-count').hide();
                    // Or hide entire caption if you want
                    // $caption.hide();
                }
            }
        }
        
        // Hide any processing messages
        $wrapper.find('.dataTables_processing').hide();
        
        // Remove any orphan text nodes
        $wrapper.contents().filter(function() {
            return this.nodeType === 3 && $.trim(this.nodeValue) !== '';
        }).remove();
        
        // Remove empty divs (except our wrappers)
        $wrapper.children('div:empty').not('.canvastack-dt-top-controls, .canvastack-dt-table-container, .canvastack-dt-bottom-controls').remove();
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
            
            // Wrap controls after a short delay to ensure DOM is ready
            setTimeout(function() {
                wrapDataTableControls($wrapper);
                applyBootstrap5Classes($wrapper);
            }, 100);

            /* Re-apply on every draw (pagination, search, sort) */
            $('#' + tableId).on('draw.dt', function () {
                setTimeout(function() {
                    var $w = $('#' + tableId).closest('.dataTables_wrapper');
                    wrapDataTableControls($w);
                    applyBootstrap5Classes($w);
                }, 50);
            });
        }

        return dtApi;
    };

    /**
     * Expose adjustErrorMessageWidth to global scope
     * So it can be called from canvastack-datatables.js after error is displayed
     */
    window.canvasignAdjustErrorMessageWidth = function(tableId) {
        var $table = $('#' + tableId);
        if ($table.length === 0) {
            console.log('Table not found:', tableId);
            return;
        }
        
        var $wrapper = $table.closest('.dataTables_wrapper');
        if ($wrapper.length === 0) {
            console.log('Wrapper not found for table:', tableId);
            return;
        }
        
        adjustErrorMessageWidth($wrapper);
    };

    /* ------------------------------------------------------------------ */
    /* 5. Listen for theme changes                                         */
    /* ------------------------------------------------------------------ */

    window.addEventListener('themechange', onThemeChange);

    /* ------------------------------------------------------------------ */
    /* 6. Global DataTables init event listener                            */
    /* ------------------------------------------------------------------ */

    // Listen for DataTables initialization
    $(document).on('init.dt', function(e, settings) {
        var $wrapper = $(settings.nTable).closest('.dataTables_wrapper');
        setTimeout(function() {
            wrapDataTableControls($wrapper);
            applyBootstrap5Classes($wrapper);
            adjustErrorMessageWidth($wrapper);
            
            // Adjust FixedColumns after initial render
            // Use longer delay to ensure fonts and CSS are fully loaded
            setTimeout(function() {
                adjustFixedColumns($wrapper);
                adjustErrorMessageWidth($wrapper);
            }, 300);
        }, 100);
    });

    // Listen for DataTables draw event
    $(document).on('draw.dt', function(e, settings) {
        var $wrapper = $(settings.nTable).closest('.dataTables_wrapper');
        setTimeout(function() {
            wrapDataTableControls($wrapper);
            applyBootstrap5Classes($wrapper);
            adjustErrorMessageWidth($wrapper);
            
            // Adjust FixedColumns after redraw
            adjustFixedColumns($wrapper);
        }, 50);
    });

    /* ------------------------------------------------------------------ */
    /* 7. Auto-init tables marked with data-canvasign-datatable            */
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

    /* ------------------------------------------------------------------ */
    /* 8. Window load event - Adjust FixedColumns after all resources loaded */
    /* ------------------------------------------------------------------ */

    // Adjust all FixedColumns tables after window fully loaded
    // This ensures fonts, CSS, and images are loaded before calculation
    $(window).on('load', function() {
        setTimeout(function() {
            $('.dataTables_wrapper').each(function() {
                adjustFixedColumns($(this));
                adjustErrorMessageWidth($(this));
            });
        }, 500); // Extra delay to ensure everything is settled
    });

    /* ------------------------------------------------------------------ */
    /* 9. Window resize event - Adjust error message width on viewport change */
    /* ------------------------------------------------------------------ */

    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            $('.dataTables_wrapper').each(function() {
                adjustErrorMessageWidth($(this));
            });
        }, 250); // Debounce resize events
    });

})();

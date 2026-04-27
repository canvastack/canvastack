/**
 * Canvastack DataTables JavaScript Module
 * 
 * This module handles all DataTables initialization, ARIA attributes,
 * keyboard navigation, and accessibility features.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

/**
 * Suppress console output when APP_DEBUG is false
 */
(function() {
    if (typeof window.APP_DEBUG === 'undefined' || window.APP_DEBUG === false) {
        var noop = function() {};
        window.console = {
            log:   noop,
            warn:  noop,
            error: noop,
            info:  noop,
            debug: noop,
            group: noop,
            groupEnd: noop,
            groupCollapsed: noop,
            time:  noop,
            timeEnd: noop,
            table: noop,
            trace: noop,
            dir:   noop
        };
    }
})();

/**
 * Setup CSRF token for all AJAX requests
 * This must be called before any AJAX requests are made
 */
(function($) {
    'use strict';
    
    // Function to setup CSRF token
    function setupCsrfToken() {
        // Get CSRF token from meta tag
        var token = $('meta[name="csrf-token"]').attr('content');
        
        if (token) {
            // Setup AJAX to include CSRF token in all requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token
                }
            });
            
            console.log('CSRF token configured for AJAX requests');
            console.log('Token:', token.substring(0, 10) + '...');
            return true;
        } else {
            console.warn('CSRF token not found. Please add <meta name="csrf-token"> to your layout.');
            return false;
        }
    }
    
    // Try to setup immediately
    if (document.readyState === 'loading') {
        // DOM is still loading, wait for DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            setupCsrfToken();
        });
    } else {
        // DOM is already loaded
        setupCsrfToken();
    }
    
    // Also setup on jQuery ready (double safety)
    $(document).ready(function() {
        setupCsrfToken();
    });
})(jQuery);

var CanvastackDataTables = (function($) {
    'use strict';
    
    var module = {};
    
    // Initialize global filter storage
    if (!window.canvastackDataTableFilters) {
        window.canvastackDataTableFilters = {};
        console.log('CanvastackDataTables: Initialized global filter storage');
    }
    
    /**
     * Initialize DataTable with configuration
     * 
     * @param {string} tableId - Table element ID
     * @param {object} config - DataTable configuration object
     * @returns {object} DataTable API instance
     */
    module.initialize = function(tableId, config) {
        // Ensure jQuery and DataTables are loaded
        if (typeof $ === 'undefined' || typeof $.fn.dataTable === 'undefined') {
            console.error('jQuery or DataTables not loaded');
            return null;
        }
        
        // Check if table element exists
        var $table = $('#' + tableId);
        if ($table.length === 0) {
            console.error('CanvastackDataTables: Table element not found with ID:', tableId);
            return null;
        }
        

        
        // Setup prototypes before initialization
        module.setupAriaAttributesPrototype();
        module.setupKeyboardNavigationPrototype();
        module.setupHelpModal();
        
        // Process createdRow JavaScript if provided
        if (config.datatableConfig.createdRowJs) {
            var createdRowJs = config.datatableConfig.createdRowJs;
            // Evaluate the JavaScript string to create the function
            // This is safe because createdRowJs comes from trusted PHP backend
            try {
                config.datatableConfig.createdRow = new Function('row', 'data', 'dataIndex', 'cells', createdRowJs);
            } catch (e) {
                console.error('CanvastackDataTables: Error parsing createdRow function:', e);
            }
            delete config.datatableConfig.createdRowJs;
        }
        
        // Process initComplete configuration
        if (config.datatableConfig.initComplete) {
            var initConfig = config.datatableConfig.initComplete;
            config.datatableConfig.initComplete = function(settings, json) {
                var api = this.api();
                
                // Delete tfoot if configured
                if (initConfig.deleteTFoot) {
                    var tableEl = document.getElementById(tableId);
                    if (tableEl && tableEl.deleteTFoot) {
                        tableEl.deleteTFoot();
                    }
                }
                
                // Apply ARIA attributes and keyboard navigation
                api.addAriaAttributes().setupAriaBusy().setupKeyboardNavigation();
                
                // Setup column search if configured
                if (initConfig.columnSearch) {
                    api.columns().every(function(n) {
                        if (n > 1) {
                            var column = this;
                            var input = document.createElement("input");
                            $(input).attr({
                                'class': 'form-control',
                                'placeholder': 'search'
                            }).appendTo($(column[initConfig.location]()).empty()).on('change', function() {
                                column.search($(this).val(), false, false, true).draw();
                            });
                        }
                    });
                }
            };
        }
        
        // CRITICAL FIX: Handle buttonsJs - evaluate JavaScript string instead of using JSON
        if (config.datatableConfig.buttonsJs) {
            try {
                // Evaluate the JavaScript string to get the actual buttons array
                config.datatableConfig.buttons = eval('(' + config.datatableConfig.buttonsJs + ')');

                delete config.datatableConfig.buttonsJs;
            } catch (e) {
                console.error('CanvastackDataTables: Error evaluating buttons:', e);
                config.datatableConfig.buttons = [];
                delete config.datatableConfig.buttonsJs;
            }
        }
        
        // Process AJAX data filter if configured
        if (config.datatableConfig.ajax && config.datatableConfig.ajax.dataFilter) {
            var filterFunc = config.datatableConfig.ajax.dataFilter;
            var filterParams = config.datatableConfig.ajax.dataFilterParams;
            
            config.datatableConfig.ajax.data = function(data) {
                var varName = filterParams.varName;
                window[varName] = data;
                if (typeof window[filterFunc] === 'function') {
                    window[filterFunc](window[varName], filterParams.strictColumns === 'true');
                }
            };
            
            delete config.datatableConfig.ajax.dataFilter;
            delete config.datatableConfig.ajax.dataFilterParams;
        }
        

        
        // Initialize DataTable with the provided configuration
        // DOM layout is controlled by config.datatableConfig.dom (e.g., "lBfrtip")
        try {
            // Add error handler for AJAX requests
            if (config.datatableConfig.ajax) {
                var originalAjax = config.datatableConfig.ajax;
                
                config.datatableConfig.ajax = function(data, callback, settings) {
                    var ajaxConfig = typeof originalAjax === 'string' ? { url: originalAjax } : originalAjax;
                    
                    // CRITICAL FIX: Merge filters from global storage into request data
                    var storedFilters = window.canvastackDataTableFilters[tableId] || {};
                    if (Object.keys(storedFilters).length > 0) {
                        console.log('CanvastackDataTables: Merging stored filters into AJAX request:', storedFilters);
                        $.extend(data, storedFilters);
                        data.filters = true;
                    }
                    
                    // Get CSRF token from meta tag
                    var csrfToken = $('meta[name="csrf-token"]').attr('content');
                    
                    // Prepare headers
                    var headers = ajaxConfig.headers || {};
                    if (csrfToken) {
                        headers['X-CSRF-TOKEN'] = csrfToken;
                        console.log('DataTables AJAX: CSRF token added to headers');
                    } else {
                        console.error('DataTables AJAX: CSRF token not found!');
                    }
                    
                    // Debug: Log request details
                    console.log('DataTables AJAX Request:', {
                        url: ajaxConfig.url,
                        type: ajaxConfig.type || 'GET',
                        hasToken: !!csrfToken,
                        tokenPreview: csrfToken ? csrfToken.substring(0, 10) + '...' : 'none',
                        hasFilters: Object.keys(storedFilters).length > 0,
                        filterCount: Object.keys(storedFilters).length
                    });
                    
                    $.ajax({
                        url: ajaxConfig.url,
                        type: ajaxConfig.type || 'GET',
                        data: typeof ajaxConfig.data === 'function' ? ajaxConfig.data(data) : data,
                        headers: headers,
                        dataType: 'json',
                        success: function(json) {
                            // Check if response contains error
                            if (json.error) {
                                module.showErrorMessage(tableId, json.error, data);
                                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                            } else {
                                callback(json);
                            }
                        },
                        error: function(xhr, error, thrown) {
                            // Debug: Log error details
                            console.error('DataTables AJAX Error:', {
                                status: xhr.status,
                                statusText: xhr.statusText,
                                error: error,
                                thrown: thrown,
                                responseJSON: xhr.responseJSON
                            });
                            
                            // Return empty data first to let DataTables render
                            callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                            
                            // Then show error message after a short delay to ensure table is rendered
                            setTimeout(function() {
                                // Check if it's a CSRF token mismatch error (HTTP 419)
                                if (xhr.status === 419 || (xhr.responseJSON && xhr.responseJSON.message && 
                                    xhr.responseJSON.message.indexOf('CSRF token mismatch') !== -1)) {
                                    
                                    console.error('CSRF Token Mismatch detected!');
                                    console.error('Request headers:', xhr.getAllResponseHeaders());
                                    
                                    var errorMessage = {
                                        message: 'CSRF Token Mismatch - Session may have expired',
                                        details: 'Please refresh the page to get a new security token',
                                        status: 419,
                                        statusText: 'CSRF Token Mismatch'
                                    };
                                    
                                    module.showErrorMessage(tableId, errorMessage, data);
                                    
                                    // Announce to screen reader
                                    module.announceToScreenReader(
                                        'csrf-error-' + tableId,
                                        'Security token expired. Please refresh the page.'
                                    );
                                } else {
                                    // Handle other errors normally
                                    var errorMessage = module.parseAjaxError(xhr, error, thrown);
                                    module.showErrorMessage(tableId, errorMessage, data);
                                }
                            }, 100); // Small delay to ensure DataTables has rendered
                        }
                    });
                };
            }
            
            // FIX: Add columnDefs to handle extra columns from backend gracefully
            // This prevents "aDataSort" error when backend sends more columns than config defines
            if (!config.datatableConfig.columnDefs) {
                config.datatableConfig.columnDefs = [];
            }
            
            // Add defaultContent for all columns to handle missing data
            config.datatableConfig.columnDefs.push({
                targets: '_all',
                defaultContent: '-'
            });
            
            console.log('CanvastackDataTables: Added columnDefs for graceful column mismatch handling');
            
            var dtApi = $table.DataTable(config.datatableConfig);
            
            // Handle click actions if configured
            if (config.clickAction) {
                module.setupClickAction(tableId, config.clickAction, dtApi);
            }
            
            // Handle filter button if configured
            if (config.filterButton) {
                module.setupFilterButton(tableId, config.filterButton);
            }
            
            // Apply search enhancements if configured
            if (config.searchConfig) {
                module.setupSearchEnhancement(tableId, config.searchConfig, dtApi);
            }
            
            return dtApi;
        } catch (error) {
            console.error('CanvastackDataTables: Error initializing DataTable:', error);
            module.showErrorMessage(tableId, 'Failed to initialize table: ' + error.message, null);
            return null;
        }
    };
    
    /**
     * Setup ARIA attributes prototype for DataTables API
     */
    module.setupAriaAttributesPrototype = function() {
        if ($.fn.dataTable.Api.prototype.addAriaAttributes) {
            return; // Already defined
        }
        
        $.fn.dataTable.Api.prototype.addAriaAttributes = function() {
            var table = this.table().node();
            var wrapper = $(table).closest('.dataTables_wrapper');
            
            // Task 4.4.3: Add role="row" to tbody rows and role="cell" to tbody cells
            // This provides context for screen readers to understand table structure
            $(table).find('tbody tr').attr('role', 'row');
            $(table).find('tbody td').attr('role', 'cell');
            
            // Add aria-label to action buttons
            $(table).find('tbody td a[title]').each(function() {
                var title = $(this).attr('title');
                if (title) { $(this).attr('aria-label', title); }
            });
            
            $(table).find('tbody td button[data-original-title]').each(function() {
                var title = $(this).attr('data-original-title');
                if (title) { $(this).attr('aria-label', title); }
            });
            
            // Add aria-label to pagination controls
            wrapper.find('.dataTables_paginate').attr('aria-label', 'Table pagination');
            wrapper.find('.paginate_button.previous').attr('aria-label', 'Previous page');
            wrapper.find('.paginate_button.next').attr('aria-label', 'Next page');
            wrapper.find('.paginate_button.first').attr('aria-label', 'First page');
            wrapper.find('.paginate_button.last').attr('aria-label', 'Last page');
            
            // Add aria-current to current page
            wrapper.find('.paginate_button').each(function() {
                if ($(this).hasClass('current')) {
                    $(this).attr('aria-current', 'page');
                    var pageNum = $(this).text();
                    $(this).attr('aria-label', 'Page ' + pageNum + ' (current)');
                } else if (!$(this).hasClass('previous') && !$(this).hasClass('next') && !$(this).hasClass('first') && !$(this).hasClass('last')) {
                    var pageNum = $(this).text();
                    $(this).attr('aria-label', 'Go to page ' + pageNum);
                }
            });
            
            // Add aria-live region for status updates
            if (wrapper.find('.dataTables_info').length > 0) {
                wrapper.find('.dataTables_info').attr({'aria-live': 'polite', 'aria-atomic': 'true', 'role': 'status'});
            }
            
            // Add aria-label to length menu
            wrapper.find('.dataTables_length select').attr('aria-label', 'Number of rows per page');
            
            // Add aria-label to search input
            wrapper.find('.dataTables_filter input').attr('aria-label', 'Search table');
            
            return this;
        };
        
        $.fn.dataTable.Api.prototype.setupAriaBusy = function() {
            var table = this.table().node();
            var api = this;
            var tableId = $(table).attr('id');
            
            // Set aria-busy on processing start
            $(table).on('processing.dt', function(e, settings, processing) {
                $(table).attr('aria-busy', processing ? 'true' : 'false');
                
                // Task 4.4.8: Announce loading status to screen readers
                var loadingStatusEl = document.getElementById(tableId + '-loading-status');
                if (loadingStatusEl) {
                    if (processing) {
                        loadingStatusEl.textContent = 'Loading table data, please wait...';
                    } else {
                        loadingStatusEl.textContent = 'Table data loaded successfully.';
                        // Clear the message after 2 seconds
                        setTimeout(function() {
                            loadingStatusEl.textContent = '';
                        }, 2000);
                    }
                }
            });
            
            // Initialize with false
            $(table).attr('aria-busy', 'false');
            
            // Update pagination ARIA attributes on draw
            $(table).on('draw.dt', function() {
                api.addAriaAttributes();
                
                // Task 4.4.5: Announce pagination info to screen readers
                module.announcePaginationStatus(api, tableId);
            });
            
            // Task 4.4.7: Announce sort direction on column sort
            $(table).on('order.dt', function() {
                module.announceSortStatus(api, tableId);
            });
            
            return this;
        };
    };
    
    /**
     * Setup keyboard navigation prototype for DataTables API
     */
    module.setupKeyboardNavigationPrototype = function() {
        if ($.fn.dataTable.Api.prototype.setupKeyboardNavigation) {
            return; // Already defined
        }
        
        $.fn.dataTable.Api.prototype.setupKeyboardNavigation = function() {
            var table = this.table().node();
            var api = this;
            var wrapper = $(table).closest('.dataTables_wrapper');
            
            // Ensure all sortable headers have proper attributes and class
            $(table).find('thead th[role="button"], thead th[tabindex="0"]').each(function() {
                $(this).addClass('canvastack-keyboard-focus');
                if (!$(this).attr('tabindex')) $(this).attr('tabindex', '0');
                if (!$(this).attr('role')) $(this).attr('role', 'button');
            });
            
            // Keyboard sorting on column headers (Enter/Space)
            $(table).find('thead th[role="button"]').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
            
            // Keyboard pagination (Arrow keys)
            wrapper.on('keydown', function(e) {
                var activeElement = document.activeElement;
                var isPaginationFocused = $(activeElement).closest('.dataTables_paginate').length > 0;
                
                // Arrow Left: Previous page
                if (e.key === 'ArrowLeft' && isPaginationFocused) {
                    e.preventDefault();
                    var prevButton = wrapper.find('.paginate_button.previous:not(.disabled)');
                    if (prevButton.length > 0) {
                        prevButton.click();
                        prevButton.focus();
                    }
                }
                
                // Arrow Right: Next page
                if (e.key === 'ArrowRight' && isPaginationFocused) {
                    e.preventDefault();
                    var nextButton = wrapper.find('.paginate_button.next:not(.disabled)');
                    if (nextButton.length > 0) {
                        nextButton.click();
                        nextButton.focus();
                    }
                }
            });
            
            // Keyboard shortcuts: Ctrl+F to focus search
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && e.key === 'f' && wrapper.is(':visible')) {
                    var searchInput = wrapper.find('.dataTables_filter input');
                    if (searchInput.length > 0) {
                        e.preventDefault();
                        searchInput.focus();
                    }
                }
            });
            
            // Add visible focus indicators via class (CSS in canvastacks.css)
            $(table).find('thead th[role="button"]').addClass('canvastack-keyboard-focus');
            wrapper.find('.paginate_button').addClass('canvastack-keyboard-focus');
            
            // Enhance action button keyboard accessibility
            $(table).find('tbody td a, tbody td button').each(function() {
                if (!$(this).attr('tabindex')) {
                    $(this).attr('tabindex', '0');
                }
                $(this).addClass('canvastack-keyboard-focus');
            });
            
            // Update keyboard navigation on table redraw
            $(table).on('draw.dt', function() {
                // Reapply class and attributes to sortable headers
                $(table).find('thead th[role="button"], thead th[tabindex="0"]').each(function() {
                    $(this).addClass('canvastack-keyboard-focus');
                    if (!$(this).attr('tabindex')) $(this).attr('tabindex', '0');
                    if (!$(this).attr('role')) $(this).attr('role', 'button');
                });
                
                // Reattach keyboard event handlers
                $(table).find('thead th[role="button"]').off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).click();
                    }
                });
                
                // Reapply to action buttons
                $(table).find('tbody td a, tbody td button').each(function() {
                    if (!$(this).attr('tabindex')) {
                        $(this).attr('tabindex', '0');
                    }
                    $(this).addClass('canvastack-keyboard-focus');
                });
            });
            
            // Add tooltips to pagination controls
            wrapper.find('.paginate_button.previous').attr('title', 'Previous page (Arrow Left)');
            wrapper.find('.paginate_button.next').attr('title', 'Next page (Arrow Right)');
            
            // Add tooltip to search input
            wrapper.find('.dataTables_filter input').attr('title', 'Press Ctrl+F to focus here');
            
            // Add keyboard help button next to search filter
            if (!wrapper.find('.canvastack-keyboard-help-btn').length) {
                var helpBtn = $('<button>', {
                    'class': 'btn btn-sm btn-info canvastack-keyboard-help-btn',
                    'type': 'button',
                    'title': 'Keyboard shortcuts (Ctrl+Shift+H)',
                    'html': '<i class="fa fa-keyboard-o"></i> <span style="margin-left:4px;">Help</span>',
                    'css': {
                        'margin-left': '10px',
                        'vertical-align': 'middle'
                    }
                }).on('click', function(e) {
                    e.preventDefault();
                    $('#canvastack-keyboard-help-modal').fadeIn(200);
                });
                
                wrapper.find('.dataTables_filter label').append(helpBtn);
            }
            
            return this;
        };
    };
    
    /**
     * Setup keyboard help modal (only once per page)
     */
    module.setupHelpModal = function() {
        if (document.getElementById('canvastack-keyboard-help-modal')) {
            return; // Already created
        }
        
        // Helper function to create keyboard key display
        function createKbdKey(text) {
            return '<span style="background:#f8f9fa; padding:4px 8px; border:1px solid #ccc; border-radius:4px; font-family:monospace; font-size:13px; display:inline-block; margin:0 2px; box-shadow:0 2px 0 rgba(0,0,0,0.1);">' + text + '</span>';
        }
        
        var modalHtml = 
            '<div id="canvastack-keyboard-help-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">' +
            '<div style="background-color:#fff; margin:5% auto; padding:20px; border-radius:8px; width:80%; max-width:600px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">' +
            '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid #007bff; padding-bottom:10px;">' +
            '<h3 style="margin:0; color:#007bff;"><i class="fa fa-keyboard-o"></i> Keyboard Shortcuts</h3>' +
            '<button id="canvastack-help-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#666;" title="Close (Escape)">&times;</button>' +
            '</div>' +
            '<table style="width:100%; border-collapse:collapse;">' +
            '<thead><tr style="background-color:#f8f9fa;"><th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">Action</th><th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">Keyboard Shortcut</th></tr></thead>' +
            '<tbody>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Sort column</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Enter') + ' or ' + createKbdKey('Space') + ' on column header</td></tr>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Navigate pages</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Arrow Left') + ' / ' + createKbdKey('Arrow Right') + ' when pagination focused</td></tr>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Focus search</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Ctrl') + ' + ' + createKbdKey('F') + '</td></tr>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Navigate elements</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Tab') + ' / ' + createKbdKey('Shift+Tab') + '</td></tr>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Activate button/link</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Enter') + '</td></tr>' +
            '<tr><td style="padding:10px; border-bottom:1px solid #dee2e6;">Close modal</td><td style="padding:10px; border-bottom:1px solid #dee2e6;">' + createKbdKey('Escape') + '</td></tr>' +
            '<tr><td style="padding:10px;">Show this help</td><td style="padding:10px;">' + createKbdKey('Ctrl') + ' + ' + createKbdKey('Shift') + ' + ' + createKbdKey('H') + '</td></tr>' +
            '</tbody></table>' +
            '<div style="margin-top:20px; padding:10px; background-color:#fff3e0; border-left:4px solid #ff6b00; border-radius:4px;">' +
            '<small><i class="fa fa-info-circle"></i> <strong>Tip:</strong> All interactive elements show an <strong style="color:#ff6b00;">orange outline</strong> when focused via keyboard.</small>' +
            '</div>' +
            '</div></div>';
        
        $('body').append(modalHtml);
        
        // Close button handler
        $('#canvastack-help-close').on('click', function() {
            $('#canvastack-keyboard-help-modal').fadeOut(200);
        });
        
        // Click outside to close
        $('#canvastack-keyboard-help-modal').on('click', function(e) {
            if (e.target.id === 'canvastack-keyboard-help-modal') {
                $('#canvastack-keyboard-help-modal').fadeOut(200);
            }
        });
        
        // Escape key to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#canvastack-keyboard-help-modal').is(':visible')) {
                $('#canvastack-keyboard-help-modal').fadeOut(200);
            }
        });
        
        // Ctrl+Shift+H to show help modal
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'H') {
                e.preventDefault();
                $('#canvastack-keyboard-help-modal').fadeIn(200);
            }
        });
    };
    
    /**
     * Setup click action for table rows
     * 
     * @param {string} tableId - Table element ID
     * @param {object} clickConfig - Click configuration
     * @param {object} dtApi - DataTable API instance
     */
    module.setupClickAction = function(tableId, clickConfig, dtApi) {
        $('#' + tableId).on('click', 'td.clickable', function() {
            var getRLP = $(this).parent('tr').attr('rlp');
            if (getRLP != false) {
                var _rlp = parseInt(getRLP.replace(clickConfig.hash, '') - clickConfig.hashDivisor);
                window.location = clickConfig.urlPath + '/' + _rlp + '/edit';
            }
        });
    };
    
    /**
     * Setup filter button with state management
     * 
     * FIXED: 2026-04-27 - Added filter state management to properly send filters to DataTables
     * 
     * @param {string} tableId - Table element ID
     * @param {string} filterClass - Filter class name
     */
    module.setupFilterButton = function(tableId, filterClass) {
        var $wrapper = $('div#' + tableId + '_wrapper>.dt-buttons');
        
        // Append the filter button container (maintains backward compatibility)
        $wrapper.append('<span class="' + filterClass + '"></span>');
        
        // Get DataTable API instance
        var dtApi = $('#' + tableId).DataTable();
        if (!dtApi) {
            console.error('CanvastackDataTables: Cannot setup filter button - DataTable not initialized');
            return;
        }
        
        // Store filter modal ID
        var filterModalId = tableId + '_CanvaStackFILTER';
        var filterFormId = tableId + '_CanvaStackFILTERForm';
        
        // Check if filter modal exists
        if ($('#' + filterModalId).length === 0) {
            console.warn('CanvastackDataTables: Filter modal not found:', filterModalId);
            return;
        }
        
        // Handle filter form submit
        $('#' + filterFormId).off('submit.canvastack-filter').on('submit.canvastack-filter', function(e) {
            e.preventDefault();
            
            console.log('CanvastackDataTables: Filter form submitted');
            
            // Show processing indicator
            $('#' + tableId + '_CanvaStackProcessing').show();
            
            // Collect filter values (exclude empty values and placeholders)
            var filters = {};
            var filterDescription = [];
            
            $(this).find('select, input[type="text"], input[type="hidden"]').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                
                // Skip reserved parameters and empty values
                var reservedParams = ['renderDataTables', 'difta', 'filters', '_token'];
                var placeholders = ['____-__-__', '____-__-__ __:__:__', '____-__-__%20__%3A__%3A__'];
                
                if (name && 
                    value && 
                    value !== '' && 
                    !reservedParams.includes(name) &&
                    !placeholders.includes(value)) {
                    
                    filters[name] = value;
                    
                    // Build description for screen reader
                    var label = $(this).closest('.form-group').find('label').text().trim();
                    if (!label) {
                        label = name.replace(/_/g, ' ');
                    }
                    filterDescription.push(label + ': ' + value);
                }
            });
            
            console.log('CanvastackDataTables: Collected filters:', filters);
            
            // Get AJAX settings
            var ajaxSettings = dtApi.settings()[0].ajax;
            
            // Store original data function if exists
            if (!ajaxSettings.originalDataFn) {
                ajaxSettings.originalDataFn = ajaxSettings.data;
            }
            
            // CRITICAL FIX: Store filters in a global variable that ajax.data can access
            // DataTables ajax.data function needs to access filters from closure
            if (!window.canvastackDataTableFilters) {
                window.canvastackDataTableFilters = {};
            }
            window.canvastackDataTableFilters[tableId] = filters;
            
            console.log('CanvastackDataTables: Stored filters globally for table:', tableId);
            console.log('CanvastackDataTables: Filter values:', filters);
            
            // Update data function to include filters from global storage
            ajaxSettings.data = function(d) {
                console.log('CanvastackDataTables: ajax.data function called!');
                
                // Call original data function if exists
                var data = d;
                if (typeof ajaxSettings.originalDataFn === 'function') {
                    data = ajaxSettings.originalDataFn(d) || d;
                }
                
                // Get filters from global storage
                var storedFilters = window.canvastackDataTableFilters[tableId] || {};
                
                // Merge filters into request data
                $.extend(data, storedFilters);
                
                // Add filters flag
                data.filters = true;
                
                console.log('CanvastackDataTables: Sending AJAX data with filters:', data);
                console.log('CanvastackDataTables: Filter values being sent:', storedFilters);
                
                return data;
            };
            
            // CRITICAL FIX: Update DataTables settings to use new data function
            // We need to update the settings object that DataTables is actually using
            var dtSettings = dtApi.settings()[0];
            if (dtSettings && dtSettings.ajax) {
                dtSettings.ajax.data = ajaxSettings.data;
                console.log('CanvastackDataTables: Updated DataTables settings with new data function');
            }
            
            // Reload DataTable with filters
            dtApi.ajax.reload(function(json) {
                console.log('CanvastackDataTables: DataTable reloaded with filters');
                console.log('CanvastackDataTables: Response:', json);
                
                // Hide processing indicator
                $('#' + tableId + '_CanvaStackProcessing').hide();
                
                // Close modal
                $('#' + filterModalId).modal('hide');
                
                // Announce filter status to screen readers
                if (filterDescription.length > 0) {
                    module.announceFilterStatus(tableId, filterDescription.join(', '));
                } else {
                    module.announceFilterStatus(tableId, 'No filters applied');
                }
                
                // Show filter indicator
                module.showFilterIndicator(tableId, filters);
            }, false);
        });
        
        // Add clear filter button if not exists
        if ($('#' + tableId + '_clearFilterBtn').length === 0) {
            var $clearBtn = $('<button>', {
                'id': tableId + '_clearFilterBtn',
                'class': 'btn btn-secondary btn-sm ml-2',
                'type': 'button',
                'html': '<i class="fa fa-times"></i> Clear Filters',
                'title': 'Clear all applied filters',
                'style': 'display: none;' // Hidden by default
            }).on('click', function() {
                module.clearFilters(tableId);
            });
            
            $wrapper.append($clearBtn);
        }
        
        console.log('CanvastackDataTables: Filter button setup completed for table:', tableId);
    };
    
    /**
     * Clear all filters from DataTable
     * 
     * @param {string} tableId - Table element ID
     */
    module.clearFilters = function(tableId) {
        console.log('CanvastackDataTables: Clearing filters for table:', tableId);
        
        var dtApi = $('#' + tableId).DataTable();
        if (!dtApi) {
            console.error('CanvastackDataTables: Cannot clear filters - DataTable not initialized');
            return;
        }
        
        // Show processing indicator
        $('#' + tableId + '_CanvaStackProcessing').show();
        
        // CRITICAL FIX: Clear filters from global storage FIRST
        // so the AJAX wrapper doesn't re-send them on reload
        if (window.canvastackDataTableFilters) {
            window.canvastackDataTableFilters[tableId] = {};
        }
        
        // Reset filter form
        var filterFormId = tableId + '_CanvaStackFILTERForm';
        $('#' + filterFormId)[0].reset();
        
        // Reset chosen selects if using Chosen plugin
        $('#' + filterFormId + ' select').trigger('chosen:updated');
        
        // Get AJAX settings
        var ajaxSettings = dtApi.settings()[0].ajax;
        
        // Restore original data function
        if (ajaxSettings.originalDataFn) {
            ajaxSettings.data = ajaxSettings.originalDataFn;
        } else {
            ajaxSettings.data = function(d) { return d; };
        }
        
        // Reload DataTable without filters
        dtApi.ajax.reload(function() {
            console.log('CanvastackDataTables: Filters cleared, DataTable reloaded');
            
            // Hide processing indicator
            $('#' + tableId + '_CanvaStackProcessing').hide();
            
            // Announce to screen readers
            module.announceFilterStatus(tableId, '');
            
            // Hide filter indicator
            module.hideFilterIndicator(tableId);
        }, false);
    };
    
    /**
     * Show filter indicator
     * 
     * @param {string} tableId - Table element ID
     * @param {object} filters - Applied filters
     */
    module.showFilterIndicator = function(tableId, filters) {
        var filterCount = Object.keys(filters).length;
        
        if (filterCount > 0) {
            // Show clear button
            $('#' + tableId + '_clearFilterBtn').show();
            
            // Add filter badge to filter button if not exists
            var $filterBtn = $('.' + tableId + '_CanvaStackFILTERButton');
            var $badge = $filterBtn.find('.filter-badge');
            
            if ($badge.length === 0) {
                $badge = $('<span>', {
                    'class': 'badge badge-primary filter-badge ml-1',
                    'style': 'font-size: 10px; vertical-align: super;'
                });
                $filterBtn.append($badge);
            }
            
            $badge.text(filterCount).show();
        }
    };
    
    /**
     * Hide filter indicator
     * 
     * @param {string} tableId - Table element ID
     */
    module.hideFilterIndicator = function(tableId) {
        // Hide clear button
        $('#' + tableId + '_clearFilterBtn').hide();
        
        // Remove filter badge
        $('.' + tableId + '_CanvaStackFILTERButton .filter-badge').remove();
    };
    
    /**
     * Announce pagination status to screen readers
     * Task 4.4.5: Announce pagination info (current page, total pages)
     * 
     * @param {object} api - DataTable API instance
     * @param {string} tableId - Table element ID
     */
    module.announcePaginationStatus = function(api, tableId) {
        var info = api.page.info();
        var paginationStatusEl = document.getElementById(tableId + '-pagination-status');
        
        if (paginationStatusEl && info) {
            var currentPage = info.page + 1; // DataTables uses 0-based indexing
            var totalPages = info.pages;
            var start = info.start + 1;
            var end = info.end;
            var total = info.recordsDisplay;
            
            var message = 'Showing ' + start + ' to ' + end + ' of ' + total + ' entries. ' +
                         'Page ' + currentPage + ' of ' + totalPages + '.';
            
            paginationStatusEl.textContent = message;
        }
    };
    
    /**
     * Announce sort status to screen readers
     * Task 4.4.7: Announce sort direction when sorting is applied
     * 
     * @param {object} api - DataTable API instance
     * @param {string} tableId - Table element ID
     */
    module.announceSortStatus = function(api, tableId) {
        var sortStatusEl = document.getElementById(tableId + '-sort-status');
        
        if (sortStatusEl) {
            var order = api.order();
            
            if (order && order.length > 0) {
                var columnIndex = order[0][0];
                var direction = order[0][1]; // 'asc' or 'desc'
                var columnName = api.column(columnIndex).header().textContent.trim();
                
                var directionText = direction === 'asc' ? 'ascending' : 'descending';
                var message = 'Table sorted by ' + columnName + ' in ' + directionText + ' order.';
                
                sortStatusEl.textContent = message;
                
                // Update aria-sort attribute on column headers
                api.columns().every(function(idx) {
                    var header = this.header();
                    if (idx === columnIndex) {
                        $(header).attr('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
                    } else {
                        $(header).attr('aria-sort', 'none');
                    }
                });
            }
        }
    };
    
    /**
     * Announce filter status to screen readers
     * Task 4.4.6: Announce filter status when filters are applied
     * 
     * This function should be called when filters are applied to the table.
     * It can be triggered from external filter controls.
     * 
     * @param {string} tableId - Table element ID
     * @param {string} filterDescription - Description of applied filters
     */
    module.announceFilterStatus = function(tableId, filterDescription) {
        var filterStatusEl = document.getElementById(tableId + '-filter-status');
        
        if (filterStatusEl) {
            if (filterDescription && filterDescription.trim() !== '') {
                var message = 'Filter applied: ' + filterDescription;
                filterStatusEl.textContent = message;
            } else {
                filterStatusEl.textContent = 'All filters cleared.';
            }
            
            // Clear the message after 3 seconds
            setTimeout(function() {
                filterStatusEl.textContent = '';
            }, 3000);
        }
    };
    
    /**
     * Setup search enhancement with debounce, min length, and highlighting
     * 
     * @param {string} tableId - Table element ID
     * @param {object} config - Search configuration
     * @param {object} dtApi - DataTable API instance
     */
    module.setupSearchEnhancement = function(tableId, config, dtApi) {
        var debounceDelay = config.debounceDelay || 300;
        var minSearchLength = config.minSearchLength || 1;
        var highlightResults = config.highlightResults || false;
        
        var searchTimer = null;
        var $searchInput = $('#' + tableId + '_filter input');
        
        if ($searchInput.length === 0) {
            return;
        }
        
        // Remove default DataTables search handler
        $searchInput.off('keyup.DT search.DT');
        
        // Add debounced search handler
        $searchInput.on('keyup', function() {
            var searchTerm = $(this).val();
            
            // Clear previous timer
            clearTimeout(searchTimer);
            
            // Check minimum length
            if (searchTerm.length > 0 && searchTerm.length < minSearchLength) {
                // Show hint to user
                module.announceToScreenReader(
                    'search-hint-' + tableId,
                    'Please enter at least ' + minSearchLength + ' characters to search'
                );
                return;
            }
            
            // Debounce search
            searchTimer = setTimeout(function() {
                // Perform search
                dtApi.search(searchTerm).draw();
                
                // Announce results
                var info = dtApi.page.info();
                var message = searchTerm.length > 0
                    ? 'Search results: ' + info.recordsDisplay + ' records found'
                    : 'Showing all ' + info.recordsTotal + ' records';
                
                module.announceToScreenReader('search-results-' + tableId, message);
                
                // Highlight results
                if (highlightResults && searchTerm.length > 0) {
                    module.highlightSearchTerm(tableId, searchTerm);
                }
            }, debounceDelay);
        });
        
        // Handle Enter key for immediate search
        $searchInput.on('keydown', function(e) {
            if (e.keyCode === 13) { // Enter key
                clearTimeout(searchTimer);
                var searchTerm = $(this).val();
                
                if (searchTerm.length === 0 || searchTerm.length >= minSearchLength) {
                    dtApi.search(searchTerm).draw();
                }
                
                e.preventDefault();
            }
        });
    };
    
    /**
     * Highlight search term in table cells
     * 
     * @param {string} tableId - Table DOM ID
     * @param {string} searchTerm - Term to highlight
     */
    module.highlightSearchTerm = function(tableId, searchTerm) {
        var $table = $('#' + tableId);
        
        // Remove previous highlights
        $table.find('.search-highlight').contents().unwrap();
        
        if (!searchTerm || searchTerm.length === 0) {
            return;
        }
        
        // Escape special regex characters
        var escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var regex = new RegExp('(' + escapedTerm + ')', 'gi');
        
        // Highlight in visible cells
        $table.find('tbody td').each(function() {
            var $cell = $(this);
            var html = $cell.html();
            
            // Skip cells with complex HTML (buttons, images, etc.)
            if (html.indexOf('<') !== -1) {
                return;
            }
            
            // Apply highlight
            var highlighted = html.replace(regex, '<mark class="search-highlight">$1</mark>');
            if (highlighted !== html) {
                $cell.html(highlighted);
            }
        });
    };
    
    /**
     * Announce message to screen readers
     * 
     * @param {string} regionId - ARIA live region ID
     * @param {string} message - Message to announce
     */
    module.announceToScreenReader = function(regionId, message) {
        var $region = $('#' + regionId);
        
        if ($region.length === 0) {
            // Create live region if it doesn't exist
            $region = $('<div>', {
                id: regionId,
                'class': 'sr-only',
                'role': 'status',
                'aria-live': 'polite',
                'aria-atomic': 'true'
            }).appendTo('body');
        }
        
        // Clear and set new message
        $region.text('');
        setTimeout(function() {
            $region.text(message);
        }, 100);
    };
    
    /**
     * Parse AJAX error and return user-friendly message
     * 
     * @param {object} xhr - XMLHttpRequest object
     * @param {string} error - Error type
     * @param {string} thrown - Error message thrown
     * @returns {string} User-friendly error message
     */
    module.parseAjaxError = function(xhr, error, thrown) {
        var errorMessage = '';
        var errorDetails = '';
        
        // Parse response JSON if available
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            if (xhr.responseJSON.exception) {
                errorDetails = xhr.responseJSON.exception;
            }
            if (xhr.responseJSON.file) {
                errorDetails += ' in ' + xhr.responseJSON.file;
            }
            if (xhr.responseJSON.line) {
                errorDetails += ' on line ' + xhr.responseJSON.line;
            }
        } else if (xhr.responseText) {
            // Try to parse as JSON first
            try {
                var jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse.message) {
                    errorMessage = jsonResponse.message;
                }
                if (jsonResponse.exception) {
                    errorDetails = jsonResponse.exception;
                }
            } catch (e) {
                // Not JSON, try to extract error from HTML response
                try {
                    var $response = $(xhr.responseText);
                    var errorText = $response.find('.exception-message').text() || 
                                   $response.find('h1').first().text() ||
                                   xhr.statusText;
                    errorMessage = errorText;
                } catch (e2) {
                    // If jQuery parsing fails, use statusText
                    errorMessage = xhr.statusText || 'Unknown error';
                }
            }
        }
        
        // Fallback to generic error
        if (!errorMessage) {
            errorMessage = 'An error occurred while loading table data';
        }
        
        // Add HTTP status if available
        if (xhr.status) {
            errorMessage = '[HTTP ' + xhr.status + '] ' + errorMessage;
        }
        
        return {
            message: errorMessage,
            details: errorDetails,
            status: xhr.status,
            statusText: xhr.statusText
        };
    };
    
    /**
     * Show error message in table body with refresh button
     * 
     * @param {string} tableId - Table element ID
     * @param {string|object} error - Error message or error object
     * @param {object} requestData - Original request data for retry
     */
    module.showErrorMessage = function(tableId, error, requestData) {
        console.log('showErrorMessage called:', {
            tableId: tableId,
            error: error,
            requestData: requestData
        });
        
        var $table = $('#' + tableId);
        var $wrapper = $table.closest('.dataTables_wrapper');
        var $tbody = $table.find('tbody');
        
        console.log('Table elements found:', {
            table: $table.length,
            wrapper: $wrapper.length,
            tbody: $tbody.length
        });
        
        // Parse error if it's an object
        var errorMessage = typeof error === 'object' ? error.message : error;
        var errorDetails = typeof error === 'object' ? error.details : '';
        var errorStatus = typeof error === 'object' ? error.status : null;
        
        console.log('Error details:', {
            message: errorMessage,
            details: errorDetails,
            status: errorStatus
        });
        
        // Determine error type and icon
        var errorIcon = 'fa-exclamation-triangle';
        var errorColor = '#d9534f';
        var errorTitle = 'Error Loading Table Data';
        
        if (errorStatus === 500) {
            errorIcon = 'fa-server';
            errorTitle = 'Server Error';
        } else if (errorStatus === 419) {
            errorIcon = 'fa-shield';
            errorTitle = 'Security Token Expired';
            errorColor = '#f0ad4e';
        } else if (errorStatus === 404) {
            errorIcon = 'fa-search';
            errorTitle = 'Not Found';
        } else if (errorStatus === 403) {
            errorIcon = 'fa-lock';
            errorTitle = 'Access Denied';
            errorColor = '#f0ad4e';
        } else if (errorStatus === 0 || errorStatus === null) {
            errorIcon = 'fa-plug';
            errorTitle = 'Connection Error';
        }
        
        // Check if error is CSRF token related
        if (errorStatus === 419 || errorMessage.indexOf('CSRF token mismatch') !== -1 || 
            errorMessage.indexOf('CSRF Token Mismatch') !== -1) {
            errorIcon = 'fa-shield';
            errorTitle = 'Security Token Expired';
            errorColor = '#f0ad4e';
        }
        
        // Check if error is database connection related
        if (errorMessage.indexOf('Access denied') !== -1 || 
            errorMessage.indexOf('Connection refused') !== -1 ||
            errorMessage.indexOf('SQLSTATE') !== -1) {
            errorIcon = 'fa-database';
            errorTitle = 'Database Connection Error';
            errorColor = '#d9534f';
        }
        
        // Determine color class
        var colorClass = errorColor === '#f0ad4e' ? 'orange' : 'red';
        
        // Create error message HTML using CSS classes
        var errorHtml = 
            '<tr class="canvastack-table-error-row">' +
            '<td colspan="100">' +
            '<div class="canvastack-error-container">' +
            '<div class="canvastack-error-icon">' +
            '<i class="fa ' + errorIcon + ' fa-4x canvastack-error-icon-' + colorClass + '"></i>' +
            '</div>' +
            '<h4 class="canvastack-error-title canvastack-error-title-' + colorClass + '">' + errorTitle + '</h4>' +
            '<div class="canvastack-error-message-box canvastack-error-message-box-' + colorClass + '">' +
            '<p class="canvastack-error-label">Error Message:</p>' +
            '<p class="canvastack-error-text">' + 
            module.escapeHtml(errorMessage) + 
            '</p>';
        
        // Add error details if available
        if (errorDetails) {
            errorHtml += 
                '<p class="canvastack-error-details-label">Technical Details:</p>' +
                '<p class="canvastack-error-details-text">' + 
                module.escapeHtml(errorDetails) + 
                '</p>';
        }
        
        errorHtml += '</div>';
        
        // Add helpful suggestions based on error type
        if (errorMessage.indexOf('Access denied') !== -1 || errorMessage.indexOf('SQLSTATE') !== -1) {
            errorHtml += 
                '<div class="canvastack-error-suggestions">' +
                '<p class="canvastack-error-suggestions-label"><i class="fa fa-lightbulb-o"></i> <strong>Possible Solutions:</strong></p>' +
                '<ul class="canvastack-error-suggestions-list">' +
                '<li>Check database connection settings in <code>.env</code> file</li>' +
                '<li>Verify database server is running</li>' +
                '<li>Confirm database credentials are correct</li>' +
                '<li>Check if database exists and is accessible</li>' +
                '</ul>' +
                '</div>';
        } else if (errorStatus === 419 || errorMessage.indexOf('CSRF token mismatch') !== -1 || 
                   errorMessage.indexOf('CSRF Token Mismatch') !== -1) {
            errorHtml += 
                '<div class="canvastack-error-suggestions">' +
                '<p class="canvastack-error-suggestions-label"><i class="fa fa-lightbulb-o"></i> <strong>Possible Solutions:</strong></p>' +
                '<ul class="canvastack-error-suggestions-list">' +
                '<li><strong>Refresh the page</strong> to get a new security token</li>' +
                '<li>Your session may have expired - try logging in again</li>' +
                '<li>Clear browser cache and cookies if problem persists</li>' +
                '<li>Contact administrator if you continue to see this error</li>' +
                '</ul>' +
                '</div>';
        } else if (errorStatus === 500) {
            errorHtml += 
                '<div class="canvastack-error-suggestions">' +
                '<p class="canvastack-error-suggestions-label"><i class="fa fa-lightbulb-o"></i> <strong>Possible Solutions:</strong></p>' +
                '<ul class="canvastack-error-suggestions-list">' +
                '<li>Check server logs for detailed error information</li>' +
                '<li>Verify all required services are running</li>' +
                '<li>Contact system administrator if problem persists</li>' +
                '</ul>' +
                '</div>';
        }
        
        // Add refresh button
        errorHtml += 
            '<div class="canvastack-error-actions">' +
            '<button class="btn btn-primary canvastack-table-refresh-btn" data-table-id="' + tableId + '">' +
            '<i class="fa fa-refresh"></i> Refresh Table' +
            '</button>' +
            '</div>' +
            '<div class="canvastack-error-help">' +
            '<small class="canvastack-error-help-text">If the problem persists, please contact support or check the browser console for more details.</small>' +
            '</div>' +
            '</div>' +
            '</td>' +
            '</tr>';
        
        // Clear existing content and show error
        console.log('Setting error HTML to tbody, length:', errorHtml.length);
        $tbody.html(errorHtml);
        console.log('Error HTML set, tbody content:', $tbody.html().substring(0, 100) + '...');
        
        // Hide processing indicator
        $wrapper.find('.dataTables_processing').hide();
        
        // Setup refresh button handler
        $('.canvastack-table-refresh-btn').off('click').on('click', function() {
            var btnTableId = $(this).data('table-id');
            console.log('Refresh button clicked for table:', btnTableId);
            module.refreshTable(btnTableId);
        });
        
        console.log('Error message displayed successfully');
        
        // Announce error to screen readers
        module.announceToScreenReader(
            'table-error-' + tableId,
            'Error loading table data: ' + errorMessage + '. Please use the refresh button to try again.'
        );
    };
    
    /**
     * Refresh table by reloading data
     * 
     * @param {string} tableId - Table element ID
     */
    module.refreshTable = function(tableId) {
        var $table = $('#' + tableId);
        
        // Check if DataTable is initialized
        if ($.fn.DataTable.isDataTable($table)) {
            var dtApi = $table.DataTable();
            
            // Show loading indicator
            var $wrapper = $table.closest('.dataTables_wrapper');
            $wrapper.find('.dataTables_processing').show();
            
            // Clear error row
            $table.find('.canvastack-table-error-row').remove();
            
            // Reload data
            dtApi.ajax.reload(function(json) {
                // Hide loading indicator
                $wrapper.find('.dataTables_processing').hide();
                
                // Announce success
                module.announceToScreenReader(
                    'table-refresh-' + tableId,
                    'Table data refreshed successfully'
                );
            }, false); // false = don't reset paging
        } else {
            // If DataTable not initialized, reload the page
            window.location.reload();
        }
    };
    
    /**
     * Escape HTML to prevent XSS
     * 
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    module.escapeHtml = function(text) {
        if (!text) return '';
        
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    };
    
    return module;
    
})(jQuery);

/**
 * Fix FixedColumns left offset when switching Bootstrap tabs.
 *
 * DataTables calculates sticky `left` values at init time. Tabs that are
 * hidden (display:none) during init get wrong column widths, so the offsets
 * are incorrect. We recalculate on every `shown.bs.tab` event.
 *
 * NOTE: We intentionally avoid api.columns.adjust() because it internally
 * accesses aoColumns which can throw "aDataSort" errors when the backend
 * returns more columns than the frontend config defines. Instead we use
 * a window resize trigger which is safe regardless of column count.
 */
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function() {
    var tabTarget = $(this).attr('href');
    if (!tabTarget) return;

    // Find every DataTable inside the newly-visible tab pane
    $(tabTarget).find('table.dataTable').each(function() {
        if (!$.fn.DataTable.isDataTable(this)) return;

        var $table = $(this);

        // Trigger a window resize event - DataTables listens to this and
        // safely recalculates column widths without touching aoColumns directly.
        // This avoids the "aDataSort" crash caused by column count mismatch.
        $(window).trigger('resize');

        // Manually recalculate sticky `left` for dtfc-fixed-left cells (FixedColumns fallback)
        var $fixedCells = $table.find('thead tr th.dtfc-fixed-left, tbody tr td.dtfc-fixed-left');
        if ($fixedCells.length) {
            var leftOffset = 0;
            $table.find('thead tr th.dtfc-fixed-left').each(function(i) {
                $(this).css('left', leftOffset + 'px');
                var colWidth = $(this).outerWidth();
                $table.find('tbody tr td.dtfc-fixed-left:nth-child(' + (i + 1) + ')').css('left', leftOffset + 'px');
                leftOffset += colWidth;
            });
        }
    });
});

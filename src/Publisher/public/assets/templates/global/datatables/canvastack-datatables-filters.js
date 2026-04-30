/**
 * CanvaStack DataTables Filters
 * 
 * Universal filter functionality for DataTables across all templates.
 * Handles filter forms, AJAX selection, and filter state management.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';
    
    // Placeholder constants
    var PLACEHOLDER_DATE = '____-__-__';
    var PLACEHOLDER_DATETIME = '____-__-__ __:__:__';
    var PLACEHOLDER_DATETIME_ENCODED = '____-__-__%20__%3A__%3A__';
    
    /**
     * Process AJAX selection for dependent dropdowns
     * 
     * @param {jQuery} object - Source select element
     * @param {string} id - Source element ID
     * @param {string} target_id - Target element ID
     * @param {string} url - AJAX endpoint URL
     * @param {Array} data - Additional data
     * @param {string} method - HTTP method (default: 'POST')
     * @param {string} onError - Error message
     */
    window.ajaxSelectionProcess = function(object, id, target_id, url, data, method, onError) {
        data = data || [];
        method = method || 'POST';
        onError = onError || 'Error';
        
        var dataInfo = JSON.parse(data);
        
        // Prepare POST data object
        var postData = {};
        
        // Add encrypted parameters to POST body
        if (typeof dataInfo.labels !== 'undefined') postData.l = dataInfo.labels;
        if (typeof dataInfo.values !== 'undefined') postData.v = dataInfo.values;
        if (typeof dataInfo.selected !== 'undefined') postData.s = dataInfo.selected;
        if (typeof dataInfo.query !== 'undefined') postData[canvastack_random()] = dataInfo.query;
        
        // Merge with form data
        var formData = object.serializeArray();
        formData.forEach(function(item) {
            postData[item.name] = item.value;
        });
        
        var selected = null;
        var pinned = '';
        
        $.ajax({
            type: method,
            url: url,
            data: postData,
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function(response) {
                var result = response.success ? response.data : response;
                selected = result.selected;
                
                loader(target_id, 'show');
                updateSelectChosen('select#' + target_id, true, '');
                
                $.each(result.data, function(value, label) {
                    pinned = (selected === value) ? ' selected' : '';
                    
                    if (value != '') {
                        var optionLabel = null;
                        
                        if (~label.indexOf('_')) {
                            optionLabel = ucwords(label.replaceAll('_', ' '));
                        } else if (~label.indexOf('.')) {
                            optionLabel = ucwords(label.replaceAll('.', ' '));
                        } else {
                            optionLabel = ucwords(label);
                        }
                        
                        $('select#' + target_id).append('<option value="' + value + '"' + pinned + '>' + optionLabel + '</option>');
                    }
                });
                
                updateSelectChosen('select#' + target_id, false, false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Selection Error:', error, xhr.responseText);
            },
            complete: function() {
                loader(target_id, 'fadeOut');
            }
        });
    };
    
    /**
     * Setup AJAX selection box with change handler
     * 
     * @param {string} id - Source element ID
     * @param {string} target_id - Target element ID
     * @param {string} url - AJAX endpoint URL
     * @param {Array} data - Additional data
     * @param {string} method - HTTP method
     * @param {string} onError - Error message
     */
    window._ajaxSelectionBox = function(id, target_id, url, data, method, onError) {
        var object = $('select#' + id);
        
        if (object.val() !== '') {
            ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
        }
        
        object.change(function(e) {
            ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
        });
    };
    
    // Alias for backward compatibility (if wrapper not loaded)
    if (typeof window.ajaxSelectionBox === 'undefined') {
        window.ajaxSelectionBox = window._ajaxSelectionBox;
    }
    
    /**
     * Initialize DataTable filters with proper state management
     * 
     * @param {string} id - Table ID
     * @param {string} url - Base AJAX URL
     * @param {object} obTable - DataTable instance
     */
    window.canvastackDataTableFilters = function(id, url, obTable) {
        $('#canvastack-' + id + '-search-box').appendTo('.CanvaStack_' + id + '_canvastack-dt-filter-box');
        $('.canvastack-dt-search-box').removeClass('hide');
        $('#' + id + '_CanvaStackProcessing').hide();
        
        $('#' + id + '_CanvaStackFILTERForm').on('submit', function(event) {
            event.preventDefault();
            $('#' + id + '_CanvaStackProcessing').show();
            
            // Use serializeArray() for proper handling
            var input = {};
            $.each($(this).serializeArray(), function(i, field) {
                input[field.name] = field.value;
            });
            
            var filterURI = [];
            var filterData = {};
            
            $.each(input, function(index, value) {
                if (index != 'renderDataTables' &&
                    index != 'difta' &&
                    index != 'filters' &&
                    index != '_token' &&
                    value != null &&
                    value != '' &&
                    value != PLACEHOLDER_DATETIME &&
                    value != PLACEHOLDER_DATETIME_ENCODED &&
                    value != PLACEHOLDER_DATE) {
                    
                    if (typeof value === 'string') {
                        filterURI.push(index + '=' + encodeURIComponent(value));
                        filterData[index] = value;
                    } else if (typeof value === 'object') {
                        $.each(value, function(idx, _val) {
                            filterURI.push(index + '[' + idx + ']=' + encodeURIComponent(_val));
                            if (!filterData[index]) filterData[index] = {};
                            filterData[index][idx] = _val;
                        });
                    }
                }
            });
            
            // Check if using POST method
            var formMethod = $(this).attr('method');
            var isPostMethod = formMethod && formMethod.toUpperCase() === 'POST';
            
            if (!isPostMethod) {
                var ajaxSettings = obTable.settings()[0].ajax;
                if (typeof ajaxSettings === 'object' && ajaxSettings.type === 'POST') {
                    isPostMethod = true;
                }
            }
            
            if (isPostMethod) {
                // POST method: Send filters via POST body
                var ajaxSettings = obTable.settings()[0].ajax;
                var originalDataFn = ajaxSettings.data;
                
                if (!ajaxSettings.originalDataFn) {
                    ajaxSettings.originalDataFn = originalDataFn;
                }
                
                ajaxSettings.data = function(d) {
                    if (typeof originalDataFn === 'function') {
                        d = originalDataFn(d) || d;
                    }
                    return $.extend({}, d, filterData, {filters: true});
                };
                
                obTable.ajax.reload(function(json) {
                    $('#' + id + '_CanvaStackProcessing').hide();
                    CanvaStackModal.hide(id + '_CanvaStackFILTER');
                    if (Object.keys(filterData).length > 0) {
                        showFilterIndicator(id, filterData);
                    }
                });
            } else {
                // GET method: Send filters via URL
                obTable.ajax.url(url + '&' + filterURI.join('&') + '&filters=true').load(function() {
                    $('#' + id + '_CanvaStackProcessing').hide();
                    CanvaStackModal.hide(id + '_CanvaStackFILTER');
                    if (Object.keys(filterData).length > 0) {
                        showFilterIndicator(id, filterData);
                    }
                });
            }
        });
    };
    
    /**
     * Show filter indicator badge
     * 
     * @param {string} id - Table ID
     * @param {object} filters - Applied filters
     */
    window.showFilterIndicator = function(id, filters) {
        var filterCount = Object.keys(filters).length;
        
        if (filterCount > 0) {
            $('#' + id + '_clearFilterBtn').show();
            
            var $filterBtn = $('.' + id + '_CanvaStackFILTERButton');
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
     * Clear DataTable filters
     * 
     * @param {string} id - Table ID
     * @param {object} obTable - DataTable instance
     */
    window.clearDataTableFilters = function(id, obTable) {
        $('#' + id + '_CanvaStackProcessing').show();
        
        // Clear filters from global storage
        if (window.canvastackDataTableFilters) {
            Object.keys(window.canvastackDataTableFilters).forEach(function(key) {
                if (key.indexOf(id) !== -1) {
                    window.canvastackDataTableFilters[key] = {};
                }
            });
        }
        
        // Reset filter form
        var filterFormId = id + '_CanvaStackFILTERForm';
        $('#' + filterFormId)[0].reset();
        $('#' + filterFormId + ' select').trigger('chosen:updated');
        
        // Get AJAX settings
        var ajaxSettings = obTable.settings()[0].ajax;
        
        // Restore original data function
        if (ajaxSettings.originalDataFn) {
            ajaxSettings.data = ajaxSettings.originalDataFn;
        } else {
            ajaxSettings.data = function(d) { return d; };
        }
        
        // Reload DataTable without filters
        obTable.ajax.reload(function() {
            $('#' + id + '_CanvaStackProcessing').hide();
            hideFilterIndicator(id);
        }, false);
    };
    
    /**
     * Hide filter indicator badge
     * 
     * @param {string} id - Table ID
     */
    window.hideFilterIndicator = function(id) {
        $('#' + id + '_clearFilterBtn').hide();
        $('.' + id + '_CanvaStackFILTERButton .filter-badge').remove();
    };
    
    /**
     * Soft delete unnecessary DataTable components
     * 
     * @param {object} data - DataTable request data
     */
    window.softDeleteUnnecessaryDatatableComponents = function(data) {
        for (var i = 0, len = data.columns.length; i < len; i++) {
            if (!data.columns[i].search.value) delete data.columns[i].search;
            if (data.columns[i].searchable === true) delete data.columns[i].searchable;
            if (data.columns[i].orderable === true) delete data.columns[i].orderable;
            if (data.columns[i].data === data.columns[i].name) delete data.columns[i].name;
        }
        delete data.search.regex;
    };
    
    /**
     * Delete unnecessary DataTable components
     * 
     * @param {object} data - DataTable request data
     * @param {boolean|string} strict - Strict mode
     */
    window.deleteUnnecessaryDatatableComponents = function(data, strict) {
        strict = strict || false;
        
        if (strict === 'soft') {
            softDeleteUnnecessaryDatatableComponents(data);
            return;
        }
        
        for (var i = 0, len = data.columns.length; i < len; i++) {
            delete data.columns[i].search;
            delete data.columns[i].searchable;
            delete data.columns[i].orderable;
            delete data.columns[i].name;
            if (strict === true) {
                delete data.columns[i].data;
            }
        }
        
        delete data.search.regex;
        delete data.search.value;
        
        if (strict === true) {
            delete data.order[0].column;
            delete data.order[0].dir;
        }
    };
    
    /**
     * Draw DataTable on click column order
     * 
     * @param {string} id - Table ID
     * @param {string} urli - Base URL
     * @param {string} tableID - Table identifier
     */
    window.drawDatatableOnClickColumnOrder = function(id, urli, tableID) {
        $('#' + id + '>thead>tr>th').each(function(n, d) {
            var classAttribute = this.attributes.class.nodeValue;
            
            if (!~classAttribute.indexOf('sorting_disabled') && !~classAttribute.indexOf('hidden-column')) {
                d.addEventListener('click', function() {
                    var idAttributes = $(this).attr('id');
                    var nodeAttribute = 'asc';
                    
                    if (typeof $(this).attr('aria-sort') !== 'undefined') {
                        nodeAttribute = ($(this).attr('aria-sort') === 'descending') ? 'asc' : 'desc';
                    }
                    
                    var urls = [];
                    urls['column'] = encodeURIComponent('columns[' + n + '][data]');
                    urls['order'] = encodeURIComponent('order[0][column]');
                    urls['dir'] = encodeURIComponent('order[0][dir]');
                    
                    var URLi = urli + '&draw=0&' + urls['column'] + '=' + idAttributes + 
                              '&' + urls['order'] + '=' + n + '&' + urls['dir'] + '=' + nodeAttribute;
                }, false);
            }
        });
    };
    
    console.log('CanvaStack DataTables Filters loaded');
    
})(window, jQuery);

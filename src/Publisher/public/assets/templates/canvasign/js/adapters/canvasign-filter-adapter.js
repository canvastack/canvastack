/**
 * Canvasign Filter Adapter
 * 
 * Provides enhanced error handling for DataTables filter AJAX calls
 * to prevent JSON parse errors from breaking the page functionality.
 * 
 * This adapter bridges the gap between backend AJAX responses and
 * frontend expectations, ensuring graceful error handling.
 * 
 * @package CanvaStack
 * @subpackage Canvasign Theme
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Store original functions if they exist
    const originalAjaxSelectionProcess = window.ajaxSelectionProcess;
    const originalAjaxSelectionBox = window.ajaxSelectionBox;

    // Enhanced ajaxSelectionProcess with robust error handling
    window.ajaxSelectionProcess = function(object, id, target_id, url, data = [], method = 'POST', onError = 'Error') {
        var dataInfo;
        
        try {
            dataInfo = JSON.parse(data);
        } catch (e) {
            console.warn('Invalid JSON data for ajaxSelectionProcess:', data);
            handleAjaxError(target_id, 'Invalid configuration data');
            return;
        }
        
        // Build URL parameters safely
        var urlParams = [];
        if (typeof dataInfo.labels !== 'undefined') urlParams.push('l=' + encodeURIComponent(dataInfo.labels));
        if (typeof dataInfo.values !== 'undefined') urlParams.push('v=' + encodeURIComponent(dataInfo.values));
        if (typeof dataInfo.selected !== 'undefined') urlParams.push('s=' + encodeURIComponent(dataInfo.selected));
        if (typeof dataInfo.query !== 'undefined') {
            var randomKey = (typeof canvastack_random === 'function') ? canvastack_random() : 'q';
            urlParams.push(randomKey + '=' + encodeURIComponent(dataInfo.query));
        }
        
        var urls = url + (urlParams.length > 0 ? '&' + urlParams.join('&') : '');
        
        $.ajax({
            type: method,
            url: urls,
            data: object.serialize(),
            dataType: 'json', // Explicitly expect JSON
            timeout: 10000, // 10 second timeout
            success: function(result) {
                try {
                    // Validate response structure
                    if (!result || typeof result !== 'object') {
                        throw new Error('Invalid response format');
                    }
                    
                    var selected = result.selected || null;
                    
                    if (typeof loader === 'function') {
                        loader(target_id, 'show');
                    }
                    
                    if (typeof updateSelectChosen === 'function') {
                        updateSelectChosen('select#' + target_id, true, '');
                    }
                    
                    // Clear existing options
                    $('select#' + target_id).empty();
                    
                    if (result.data && typeof result.data === 'object') {
                        $.each(result.data, function(value, label) {
                            var pinned = (selected === value) ? ' selected' : '';
                            
                            if (value !== '') {
                                var optionLabel = label;
                                
                                if (typeof label === 'string' && label.indexOf('_') !== -1) {
                                    optionLabel = label.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                }
                                
                                $('select#' + target_id).append('<option value="' + escapeHtml(value) + '"' + pinned + '>' + escapeHtml(optionLabel) + '</option>');
                            }
                        });
                    } else {
                        // No data returned
                        $('select#' + target_id).append('<option value="">No options available</option>');
                    }
                    
                    if (typeof loader === 'function') {
                        loader(target_id, 'hide');
                    }
                    
                    if (typeof updateSelectChosen === 'function') {
                        updateSelectChosen('select#' + target_id, false, selected);
                    }
                    
                } catch (e) {
                    console.error('Error processing AJAX response:', e);
                    handleAjaxError(target_id, 'Error processing server response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 200) + '...' : 'No response',
                    url: urls,
                    statusCode: xhr.status
                });
                
                var errorMessage = onError || 'Server error occurred';
                
                // Provide more specific error messages
                if (xhr.status === 404) {
                    errorMessage = 'Endpoint not found';
                } else if (xhr.status === 403) {
                    errorMessage = 'Access denied';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error';
                } else if (status === 'timeout') {
                    errorMessage = 'Request timeout';
                } else if (status === 'parsererror') {
                    errorMessage = 'Invalid server response';
                }
                
                handleAjaxError(target_id, errorMessage);
            }
        });
    };

    // Enhanced ajaxSelectionBox with validation
    window.ajaxSelectionBox = function(id, target_id, url, data = [], method = 'POST', onError = 'Error') {
        var object = $('select#' + id);
        
        if (!object.length) {
            console.warn('Source select element not found:', id);
            return;
        }
        
        if (!$('select#' + target_id).length) {
            console.warn('Target select element not found:', target_id);
            return;
        }
        
        if (object.val() !== '') {
            ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
        }
        
        object.off('change.ajaxSelection').on('change.ajaxSelection', function(e) {
            ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
        });
    };
    
    // Helper function to handle AJAX errors gracefully
    function handleAjaxError(target_id, message) {
        if (typeof loader === 'function') {
            loader(target_id, 'hide');
        }
        
        // Clear the target select and add an error option
        var $target = $('select#' + target_id);
        if ($target.length) {
            $target.empty().append('<option value="">' + escapeHtml(message) + '</option>');
            
            if (typeof updateSelectChosen === 'function') {
                updateSelectChosen('select#' + target_id, false, '');
            }
        }
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Also provide a safer JSON parse function
    window.safeJsonParse = function(jsonString, defaultValue = null) {
        try {
            return JSON.parse(jsonString);
        } catch (e) {
            console.warn('JSON parse error:', e.message, 'Input:', jsonString ? jsonString.substring(0, 100) + '...' : 'null');
            return defaultValue;
        }
    };

    // Override the original success handler in filter.js to prevent JSON parse errors
    $(document).ready(function() {
        // Monkey patch jQuery AJAX to handle JSON parse errors globally for this template
        var originalAjax = $.ajax;
        $.ajax = function(options) {
            if (options.success && typeof options.success === 'function') {
                var originalSuccess = options.success;
                options.success = function(data, textStatus, jqXHR) {
                    // If the response looks like HTML instead of JSON, handle it gracefully
                    if (typeof data === 'string' && data.trim().startsWith('<')) {
                        console.warn('Received HTML response instead of JSON:', data.substring(0, 100) + '...');
                        if (options.error) {
                            options.error(jqXHR, 'parsererror', 'Invalid JSON response');
                        }
                        return;
                    }
                    
                    // Call original success handler
                    originalSuccess.call(this, data, textStatus, jqXHR);
                };
            }
            
            return originalAjax.call(this, options);
        };
    });
    
    console.log('Canvasign Filter Adapter loaded - Enhanced error handling active');
    
})(jQuery);
/**
 * Canvasign Debug Helper
 * 
 * Provides debugging utilities for the canvasign template
 * to help identify and resolve issues.
 */

(function($) {
    'use strict';

    // Debug mode flag (set to false in production)
    const DEBUG_MODE = true;

    if (!DEBUG_MODE) return;

    // Log all AJAX requests for debugging
    $(document).ajaxSend(function(event, xhr, settings) {
        console.log('AJAX Request:', {
            url: settings.url,
            method: settings.type,
            data: settings.data
        });
    });

    // Log all AJAX responses
    $(document).ajaxComplete(function(event, xhr, settings) {
        const contentType = xhr.getResponseHeader('Content-Type') || '';
        const isJson = contentType.includes('application/json');
        const responseText = xhr.responseText;
        
        console.log('AJAX Response:', {
            url: settings.url,
            status: xhr.status,
            contentType: contentType,
            isJson: isJson,
            responseLength: responseText ? responseText.length : 0,
            responsePreview: responseText ? responseText.substring(0, 200) + '...' : 'No response'
        });

        // Warn about potential JSON issues
        if (!isJson && responseText && responseText.trim().startsWith('<')) {
            console.warn('⚠️ Received HTML response instead of JSON:', {
                url: settings.url,
                htmlPreview: responseText.substring(0, 300) + '...'
            });
        }
    });

    // Log AJAX errors
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.error('AJAX Error:', {
            url: settings.url,
            status: xhr.status,
            statusText: xhr.statusText,
            error: thrownError,
            responseText: xhr.responseText ? xhr.responseText.substring(0, 500) + '...' : 'No response'
        });
    });

    // Smart JSON.parse override to handle Bootstrap 5 data attribute parsing
    const originalParse = JSON.parse;
    JSON.parse = function(text) {
        try {
            return originalParse.call(this, text);
        } catch (e) {
            // Check if this is a Bootstrap data attribute parsing error
            const stack = e.stack || '';
            const isBootstrapError = stack.includes('bootstrap') || 
                                    stack.includes('getDataAttributes');
            
            // Check if input is a simple non-JSON string
            const isSimpleString = typeof text === 'string' && 
                                  text.trim().length > 0 &&
                                  !text.trim().startsWith('{') && 
                                  !text.trim().startsWith('[') &&
                                  !text.trim().startsWith('"');
            
            if (isBootstrapError && isSimpleString) {
                // Bootstrap is trying to parse a simple string as JSON
                // This is likely a data attribute value that's not meant to be JSON
                console.warn('⚠️ Bootstrap tried to parse non-JSON data attribute:', {
                    value: text.substring(0, 50),
                    hint: 'This is usually safe to ignore'
                });
                
                // Return the string as-is to prevent error
                return text;
            }
            
            // For other errors, log details and re-throw
            if (DEBUG_MODE) {
                console.error('JSON Parse Error:', {
                    error: e.message,
                    input: text ? text.substring(0, 200) + '...' : 'null/undefined',
                    stack: e.stack
                });
            }
            
            throw e;
        }
    };

    // Check for missing functions that might cause errors
    $(document).ready(function() {
        const requiredFunctions = [
            'canvastack_random',
            'loader',
            'updateSelectChosen',
            'ajaxSelectionProcess',
            'ajaxSelectionBox'
        ];

        requiredFunctions.forEach(function(funcName) {
            if (typeof window[funcName] !== 'function') {
                console.warn('⚠️ Missing function:', funcName);
            } else {
                console.log('✅ Function available:', funcName);
            }
        });

        // Check for required jQuery plugins
        const requiredPlugins = [
            'DataTable',
            'metisMenu',
            'slicknav'
        ];

        requiredPlugins.forEach(function(pluginName) {
            if (typeof $.fn[pluginName] !== 'function') {
                console.warn('⚠️ Missing jQuery plugin:', pluginName);
            } else {
                console.log('✅ jQuery plugin available:', pluginName);
            }
        });

        // Check for Bootstrap components
        if (typeof bootstrap !== 'undefined') {
            console.log('✅ Bootstrap 5 loaded');
            const bootstrapComponents = ['Modal', 'Tooltip', 'Popover', 'Dropdown'];
            bootstrapComponents.forEach(function(component) {
                if (bootstrap[component]) {
                    console.log('✅ Bootstrap component available:', component);
                } else {
                    console.warn('⚠️ Missing Bootstrap component:', component);
                }
            });
        } else {
            console.warn('⚠️ Bootstrap not loaded');
        }

        console.log('🔍 Canvasign Debug Helper loaded - Monitoring active');
    });

})(jQuery);
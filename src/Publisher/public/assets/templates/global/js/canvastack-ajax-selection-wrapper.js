/**
 * CanvaStack Ajax Selection Wrapper
 *
 * Provides a safe wrapper for ajaxSelectionBox calls that may execute
 * before the actual function is loaded. Queues calls and executes them
 * once the function becomes available.
 *
 * This solves the timing issue where inline PHP-generated scripts
 * call ajaxSelectionBox() before canvastack-datatables-filters.js loads.
 *
 * @package CanvaStack
 * @subpackage Global JS
 */
(function() {
    'use strict';

    var queue = [];
    var checkInterval;
    var maxRetries = 50; // 50 * 100ms = 5 seconds max wait
    var retries = 0;

    /**
     * Safe wrapper for ajaxSelectionBox
     * Queues the call if function not available yet
     */
    window.ajaxSelectionBox = function(id, target_id, url, data, method, onError) {
        // If real function is available, call it directly
        if (typeof window._ajaxSelectionBox === 'function') {
            return window._ajaxSelectionBox(id, target_id, url, data, method, onError);
        }

        // Otherwise, queue the call
        queue.push({ id, target_id, url, data, method, onError });

        // Start checking for real function
        if (!checkInterval) {
            checkInterval = setInterval(function() {
                retries++;

                // Check if real function is now available
                if (typeof window._ajaxSelectionBox === 'function') {
                    clearInterval(checkInterval);
                    checkInterval = null;

                    // Execute all queued calls
                    while (queue.length > 0) {
                        var call = queue.shift();
                        window._ajaxSelectionBox(
                            call.id,
                            call.target_id,
                            call.url,
                            call.data,
                            call.method,
                            call.onError
                        );
                    }
                    retries = 0;
                }

                // Give up after max retries
                if (retries >= maxRetries) {
                    clearInterval(checkInterval);
                    checkInterval = null;
                    console.error('ajaxSelectionBox: Real function never loaded. Queued calls:', queue.length);
                    queue = [];
                    retries = 0;
                }
            }, 100);
        }
    };

})();

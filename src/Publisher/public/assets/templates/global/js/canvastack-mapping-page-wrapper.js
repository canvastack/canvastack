/**
 * CanvaStack Mapping Page Wrapper
 *
 * Provides safe wrappers for mapping page functions that may be called
 * before the actual functions are loaded. Queues calls and executes them
 * once the functions become available.
 *
 * This solves the timing issue where inline PHP-generated scripts
 * call mapping page functions before mapping-page-handlers.js loads.
 *
 * @package CanvaStack
 * @subpackage Global JS
 */
(function() {
    'use strict';

    var queue = {
        mappingPageTableFieldname: [],
        rowButtonRemovalMapRoles: [],
        mappingPageFieldnameValues: [],
        firstResetRowButton: [],
        mappingPageButtonManipulation: [],
        setAjaxSelectionBox: []
    };
    
    var checkInterval;
    var maxRetries = 50; // 50 * 100ms = 5 seconds max wait
    var retries = 0;

    /**
     * Check if all real functions are loaded
     */
    function allFunctionsLoaded() {
        return typeof window._mappingPageTableFieldname === 'function' &&
               typeof window._rowButtonRemovalMapRoles === 'function' &&
               typeof window._mappingPageFieldnameValues === 'function' &&
               typeof window._firstResetRowButton === 'function' &&
               typeof window._mappingPageButtonManipulation === 'function' &&
               typeof window._setAjaxSelectionBox === 'function';
    }

    /**
     * Execute all queued calls
     */
    function executeQueue() {
        console.log('🚀 Executing queued calls:', {
            mappingPageTableFieldname: queue.mappingPageTableFieldname.length,
            rowButtonRemovalMapRoles: queue.rowButtonRemovalMapRoles.length,
            mappingPageFieldnameValues: queue.mappingPageFieldnameValues.length,
            firstResetRowButton: queue.firstResetRowButton.length,
            mappingPageButtonManipulation: queue.mappingPageButtonManipulation.length,
            setAjaxSelectionBox: queue.setAjaxSelectionBox.length
        });
        
        // Execute mappingPageTableFieldname calls
        while (queue.mappingPageTableFieldname.length > 0) {
            var call = queue.mappingPageTableFieldname.shift();
            console.log('📞 Executing mappingPageTableFieldname:', call.args[0]);
            window._mappingPageTableFieldname.apply(null, call.args);
        }
        
        // Execute rowButtonRemovalMapRoles calls
        while (queue.rowButtonRemovalMapRoles.length > 0) {
            var call = queue.rowButtonRemovalMapRoles.shift();
            window._rowButtonRemovalMapRoles.apply(null, call.args);
        }
        
        // Execute mappingPageFieldnameValues calls
        while (queue.mappingPageFieldnameValues.length > 0) {
            var call = queue.mappingPageFieldnameValues.shift();
            window._mappingPageFieldnameValues.apply(null, call.args);
        }
        
        // Execute firstResetRowButton calls
        while (queue.firstResetRowButton.length > 0) {
            var call = queue.firstResetRowButton.shift();
            window._firstResetRowButton.apply(null, call.args);
        }
        
        // Execute mappingPageButtonManipulation calls
        while (queue.mappingPageButtonManipulation.length > 0) {
            var call = queue.mappingPageButtonManipulation.shift();
            window._mappingPageButtonManipulation.apply(null, call.args);
        }
        
        // Execute setAjaxSelectionBox calls
        while (queue.setAjaxSelectionBox.length > 0) {
            var call = queue.setAjaxSelectionBox.shift();
            window._setAjaxSelectionBox.apply(null, call.args);
        }
    }

    /**
     * Start checking for real functions
     */
    function startChecking() {
        if (checkInterval) return; // Already checking
        
        checkInterval = setInterval(function() {
            retries++;

            // Check if real functions are now available
            if (allFunctionsLoaded()) {
                clearInterval(checkInterval);
                checkInterval = null;
                executeQueue();
                retries = 0;
                console.log('✅ Mapping page functions loaded, executed queued calls');
            }

            // Give up after max retries
            if (retries >= maxRetries) {
                clearInterval(checkInterval);
                checkInterval = null;
                console.error('❌ Mapping page functions never loaded. Queued calls:', 
                    Object.keys(queue).reduce(function(sum, key) { return sum + queue[key].length; }, 0));
                retries = 0;
            }
        }, 100);
    }

    /**
     * Safe wrapper for mappingPageTableFieldname
     */
    window.mappingPageTableFieldname = function(id, target_id, url, target_opt, nodebtn, nodemodel, method, onError) {
        console.log('🔍 mappingPageTableFieldname called:', { id, target_id, url, nodemodel });
        
        if (typeof window._mappingPageTableFieldname === 'function') {
            console.log('✅ Real function available, calling directly');
            return window._mappingPageTableFieldname(id, target_id, url, target_opt, nodebtn, nodemodel, method, onError);
        }
        
        console.log('⏳ Real function not available yet, queueing call');
        queue.mappingPageTableFieldname.push({ args: arguments });
        startChecking();
    };

    /**
     * Safe wrapper for rowButtonRemovalMapRoles
     */
    window.rowButtonRemovalMapRoles = function(id, target_id, url) {
        if (typeof window._rowButtonRemovalMapRoles === 'function') {
            return window._rowButtonRemovalMapRoles(id, target_id, url);
        }
        
        queue.rowButtonRemovalMapRoles.push({ args: arguments });
        startChecking();
    };

    /**
     * Safe wrapper for mappingPageFieldnameValues
     */
    window.mappingPageFieldnameValues = function(id, target_id, url, method, onError) {
        if (typeof window._mappingPageFieldnameValues === 'function') {
            return window._mappingPageFieldnameValues(id, target_id, url, method, onError);
        }
        
        queue.mappingPageFieldnameValues.push({ args: arguments });
        startChecking();
    };

    /**
     * Safe wrapper for firstResetRowButton
     */
    window.firstResetRowButton = function(id, target_id, second_target, url, method, onError, withAction) {
        if (typeof window._firstResetRowButton === 'function') {
            return window._firstResetRowButton(id, target_id, second_target, url, method, onError, withAction);
        }
        
        queue.firstResetRowButton.push({ args: arguments });
        startChecking();
    };

    /**
     * Safe wrapper for mappingPageButtonManipulation
     */
    window.mappingPageButtonManipulation = function(node_btn, id, target_id, second_target, url, method, onError) {
        if (typeof window._mappingPageButtonManipulation === 'function') {
            return window._mappingPageButtonManipulation(node_btn, id, target_id, second_target, url, method, onError);
        }
        
        queue.mappingPageButtonManipulation.push({ args: arguments });
        startChecking();
    };

    /**
     * Safe wrapper for setAjaxSelectionBox
     */
    window.setAjaxSelectionBox = function(object, id, target_id, url, method, onError) {
        if (typeof window._setAjaxSelectionBox === 'function') {
            return window._setAjaxSelectionBox(object, id, target_id, url, method, onError);
        }
        
        queue.setAjaxSelectionBox.push({ args: arguments });
        startChecking();
    };

    console.log('🔧 Mapping page wrapper loaded');

})();

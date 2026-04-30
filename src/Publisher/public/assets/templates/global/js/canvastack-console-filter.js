/**
 * CanvaStack Console Filter
 *
 * Suppresses known, intentional third-party console warnings that are
 * informational only and do not indicate real errors in this application.
 *
 * Must be loaded BEFORE the libraries that produce these warnings.
 *
 * Currently filtered:
 *  - CKEditor 4.x "version not secure" notice (we intentionally use 4.16.2,
 *    a stable free version without LTS paywall)
 */
(function () {
    'use strict';

    // Store original console methods
    var _warn = console.warn.bind(console);
    var _log = console.log.bind(console);

    // Override console.warn
    console.warn = function () {
        var msg = '';
        
        // Handle different argument types
        if (arguments.length > 0) {
            if (typeof arguments[0] === 'string') {
                msg = arguments[0];
            } else if (arguments[0] && arguments[0].toString) {
                msg = arguments[0].toString();
            }
        }

        // Suppress CKEditor version warnings (both "not secure" and "version check")
        if (msg.indexOf('CKEditor') !== -1 && 
            (msg.indexOf('not secure') !== -1 || msg.indexOf('version') !== -1)) {
            return; // Suppress
        }

        // Pass through all other warnings
        _warn.apply(console, arguments);
    };

    // Also override console.log as fallback (some CKEditor versions use log instead of warn)
    console.log = function () {
        var msg = '';
        
        if (arguments.length > 0) {
            if (typeof arguments[0] === 'string') {
                msg = arguments[0];
            } else if (arguments[0] && arguments[0].toString) {
                msg = arguments[0].toString();
            }
        }

        // Suppress CKEditor version messages
        if (msg.indexOf('CKEditor') !== -1 && 
            (msg.indexOf('not secure') !== -1 || msg.indexOf('version') !== -1)) {
            return; // Suppress
        }

        // Pass through all other logs
        _log.apply(console, arguments);
    };

})();

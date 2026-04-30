/**
 * CKEditor Warning Suppressor
 * 
 * This file must be loaded IMMEDIATELY AFTER ckeditor.js
 * to suppress version check warnings before any editor instance is created.
 */
(function() {
    'use strict';
    
    if (typeof CKEDITOR === 'undefined') return;
    
    // Disable version check at the earliest possible moment
    CKEDITOR.config.versionCheck = false;
    
    // Override the notification system to block version warnings
    if (CKEDITOR.plugins && CKEDITOR.plugins.notification) {
        var originalShow = CKEDITOR.plugins.notification.prototype.show;
        CKEDITOR.plugins.notification.prototype.show = function() {
            // Block if message contains version warning
            if (this.message && 
                (this.message.indexOf('not secure') > -1 || 
                 this.message.indexOf('version') > -1)) {
                return;
            }
            return originalShow.apply(this, arguments);
        };
    }
    
    // Also block console.warn calls from CKEditor
    var originalWarn = console.warn;
    console.warn = function() {
        var msg = arguments[0] || '';
        if (typeof msg === 'string' && 
            msg.indexOf('CKEditor') > -1 && 
            msg.indexOf('not secure') > -1) {
            return; // Block this specific warning
        }
        return originalWarn.apply(console, arguments);
    };
    
})();

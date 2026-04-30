/* jQuery slimScroll - Synchronous CDN Loading with Fallback */
(function($) {
    'use strict';
    
    // Check if slimScroll is already loaded
    if (typeof $.fn.slimScroll !== 'undefined') {
        return;
    }
    
    // Create a promise-based loader
    window.slimScrollReady = new Promise(function(resolve, reject) {
        // Try to load from CDN
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js';
        script.async = false;
        
        script.onload = function() {
            console.log('slimScroll loaded successfully from CDN');
            resolve();
        };
        
        script.onerror = function() {
            console.warn('Failed to load slimScroll from CDN, providing fallback');
            // Provide a minimal fallback implementation
            $.fn.slimScroll = function(options) {
                return this.each(function() {
                    const $this = $(this);
                    if (options && options.height) {
                        $this.css({
                            'height': options.height,
                            'overflow-y': 'auto'
                        });
                    }
                });
            };
            resolve();
        };
        
        document.head.appendChild(script);
    });
    
    // Ensure slimScroll is available before any usage
    const originalSlimScroll = $.fn.slimScroll;
    $.fn.slimScroll = function(options) {
        const $elements = this;
        
        if (window.slimScrollReady) {
            return window.slimScrollReady.then(function() {
                if (typeof $.fn.slimScroll === 'function' && $.fn.slimScroll !== arguments.callee) {
                    return $.fn.slimScroll.call($elements, options);
                } else {
                    // Fallback implementation
                    return $elements.each(function() {
                        const $this = $(this);
                        if (options && options.height) {
                            $this.css({
                                'height': options.height,
                                'overflow-y': 'auto'
                            });
                        }
                    });
                }
            });
        }
        
        return $elements;
    };
    
})(jQuery);
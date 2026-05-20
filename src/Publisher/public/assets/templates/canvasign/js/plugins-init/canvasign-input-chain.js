/**
 * Canvasign Input Chain Initialization
 * 
 * @version 1.0.0
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize Input Chain plugin
        if (typeof CanvastackInputChain !== 'undefined') {
            CanvastackInputChain.init();
        }
    });
    
})(jQuery);

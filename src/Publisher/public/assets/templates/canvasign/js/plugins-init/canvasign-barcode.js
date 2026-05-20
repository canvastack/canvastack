/**
 * Canvasign Barcode Initialization
 * Handles barcode field initialization and events
 * 
 * @version 1.0.0
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof JsBarcode !== 'undefined') {
            CanvastackBarcode.init();
        } else {
            console.warn('JsBarcode library not loaded');
        }
    });
    
})(jQuery);

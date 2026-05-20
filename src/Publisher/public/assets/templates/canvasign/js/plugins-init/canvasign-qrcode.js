/**
 * Canvasign QR Code Initialization
 * Handles QR code field initialization and events
 * 
 * @version 1.0.0
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof QRCode !== 'undefined') {
            CanvastackQRCode.init();
        } else {
            console.warn('QRCode library not loaded');
        }
    });
    
})(jQuery);

/**
 * CanvaStack Modal Adapter
 * 
 * Provides a unified API for showing/hiding modals across different CSS frameworks.
 * Supports Bootstrap 4 (default), Bootstrap 5 (canvasign), and TailwindCSS (canvas).
 * 
 * Usage:
 *   CanvaStackModal.show('myModalId');
 *   CanvaStackModal.hide('myModalId');
 * 
 * Template Detection:
 *   Set window.canvastackTemplate to 'default', 'canvasign', or 'canvas'
 *   Defaults to 'default' if not set
 */

var CanvaStackModal = (function() {
    'use strict';
    
    /**
     * Get the current active template
     * @returns {string} Template name ('default', 'canvasign', or 'canvas')
     */
    function getTemplate() {
        return window.canvastackTemplate || 'default';
    }
    
    /**
     * Show a modal by ID
     * @param {string} modalId - The ID of the modal element (without '#' prefix)
     */
    function show(modalId) {
        var template = getTemplate();
        var $modal = $('#' + modalId);
        
        if ($modal.length === 0) {
            console.warn('CanvaStackModal.show: Modal not found with ID:', modalId);
            return;
        }
        
        switch (template) {
            case 'canvas':
                // TailwindCSS: Remove 'hidden' class to show modal
                $modal.removeClass('hidden');
                
                // Add backdrop if it doesn't exist
                if ($('.modal-backdrop').length === 0) {
                    $('body').append('<div class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 z-40"></div>');
                }
                
                // Prevent body scroll
                $('body').addClass('overflow-hidden');
                break;
                
            case 'canvasign':
                // Bootstrap 5: Use Bootstrap 5 modal API
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modalInstance = bootstrap.Modal.getOrCreateInstance($modal[0]);
                    modalInstance.show();
                } else if (typeof $modal.modal === 'function') {
                    // Fallback to jQuery if Bootstrap 5 JS not loaded
                    $modal.modal('show');
                } else {
                    console.error('CanvaStackModal.show: Bootstrap 5 modal API not available');
                }
                break;
                
            case 'default':
            default:
                // Bootstrap 4: Use Bootstrap 4 modal API
                if (typeof $modal.modal === 'function') {
                    $modal.modal('show');
                } else {
                    console.error('CanvaStackModal.show: Bootstrap 4 modal API not available');
                }
                break;
        }
    }
    
    /**
     * Hide a modal by ID
     * @param {string} modalId - The ID of the modal element (without '#' prefix)
     */
    function hide(modalId) {
        var template = getTemplate();
        var $modal = $('#' + modalId);
        
        if ($modal.length === 0) {
            console.warn('CanvaStackModal.hide: Modal not found with ID:', modalId);
            return;
        }
        
        switch (template) {
            case 'canvas':
                // TailwindCSS: Add 'hidden' class to hide modal
                $modal.addClass('hidden');
                
                // Remove backdrop
                $('.modal-backdrop').remove();
                
                // Restore body scroll
                $('body').removeClass('overflow-hidden');
                break;
                
            case 'canvasign':
                // Bootstrap 5: Use Bootstrap 5 modal API
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modalInstance = bootstrap.Modal.getInstance($modal[0]);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        // Fallback: create instance and hide
                        modalInstance = new bootstrap.Modal($modal[0]);
                        modalInstance.hide();
                    }
                } else if (typeof $modal.modal === 'function') {
                    // Fallback to jQuery if Bootstrap 5 JS not loaded
                    $modal.modal('hide');
                } else {
                    console.error('CanvaStackModal.hide: Bootstrap 5 modal API not available');
                }
                break;
                
            case 'default':
            default:
                // Bootstrap 4: Use Bootstrap 4 modal API
                if (typeof $modal.modal === 'function') {
                    $modal.modal('hide');
                } else {
                    console.error('CanvaStackModal.hide: Bootstrap 4 modal API not available');
                }
                break;
        }
    }
    
    /**
     * Check if a modal is currently visible
     * @param {string} modalId - The ID of the modal element (without '#' prefix)
     * @returns {boolean} True if modal is visible, false otherwise
     */
    function isVisible(modalId) {
        var template = getTemplate();
        var $modal = $('#' + modalId);
        
        if ($modal.length === 0) {
            return false;
        }
        
        switch (template) {
            case 'canvas':
                // TailwindCSS: Check if 'hidden' class is absent
                return !$modal.hasClass('hidden');
                
            case 'canvasign':
                // Bootstrap 5: Check if modal has 'show' class
                return $modal.hasClass('show');
                
            case 'default':
            default:
                // Bootstrap 4: Check if modal has 'in' or 'show' class
                return $modal.hasClass('in') || $modal.hasClass('show');
        }
    }
    
    /**
     * Toggle a modal's visibility
     * @param {string} modalId - The ID of the modal element (without '#' prefix)
     */
    function toggle(modalId) {
        if (isVisible(modalId)) {
            hide(modalId);
        } else {
            show(modalId);
        }
    }
    
    // Public API
    return {
        show: show,
        hide: hide,
        isVisible: isVisible,
        toggle: toggle,
        getTemplate: getTemplate
    };
})();

// Export for CommonJS/Node.js environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CanvaStackModal;
}

// Export for AMD/RequireJS environments
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return CanvaStackModal;
    });
}

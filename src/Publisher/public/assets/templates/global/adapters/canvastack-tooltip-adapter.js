/**
 * CanvaStack Tooltip Adapter
 * 
 * Provides a unified API for initializing tooltips and popovers across different CSS frameworks.
 * Supports Bootstrap 4 (default), Bootstrap 5 (canvasign), and TailwindCSS (canvas).
 * 
 * Usage:
 *   CanvaStackTooltip.init();
 * 
 * Template Detection:
 *   Set window.canvastackTemplate to 'default', 'canvasign', or 'canvas'
 *   Defaults to 'default' if not set
 */

var CanvaStackTooltip = (function() {
    'use strict';
    
    /**
     * Get the current active template
     * @returns {string} Template name ('default', 'canvasign', or 'canvas')
     */
    function getTemplate() {
        return window.canvastackTemplate || 'default';
    }
    
    /**
     * Initialize tooltips and popovers based on the active template
     */
    function init() {
        var template = getTemplate();
        
        switch (template) {
            case 'canvas':
                // TailwindCSS: Use Tippy.js if available, otherwise native title tooltips
                initTailwindTooltips();
                break;
                
            case 'canvasign':
                // Bootstrap 5: Initialize tooltips with data-bs-toggle attribute
                initBootstrap5Tooltips();
                break;
                
            case 'default':
            default:
                // Bootstrap 4: Initialize tooltips with data-toggle attribute
                initBootstrap4Tooltips();
                break;
        }
    }
    
    /**
     * Initialize Bootstrap 4 tooltips and popovers
     */
    function initBootstrap4Tooltips() {
        // Initialize tooltips
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        } else {
            console.warn('CanvaStackTooltip.init: Bootstrap 4 tooltip plugin not available');
        }
        
        // Initialize popovers
        if (typeof $.fn.popover === 'function') {
            $('[data-toggle="popover"]').popover();
        } else {
            console.warn('CanvaStackTooltip.init: Bootstrap 4 popover plugin not available');
        }
    }
    
    /**
     * Initialize Bootstrap 5 tooltips and popovers
     */
    function initBootstrap5Tooltips() {
        // Initialize tooltips using Bootstrap 5 API
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } else if (typeof $.fn.tooltip === 'function') {
            // Fallback to jQuery if Bootstrap 5 JS not loaded
            $('[data-bs-toggle="tooltip"]').tooltip();
        } else {
            console.warn('CanvaStackTooltip.init: Bootstrap 5 tooltip API not available');
        }
        
        // Initialize popovers using Bootstrap 5 API
        if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        } else if (typeof $.fn.popover === 'function') {
            // Fallback to jQuery if Bootstrap 5 JS not loaded
            $('[data-bs-toggle="popover"]').popover();
        } else {
            console.warn('CanvaStackTooltip.init: Bootstrap 5 popover API not available');
        }
    }
    
    /**
     * Initialize Tailwind tooltips using Tippy.js or native title attribute
     */
    function initTailwindTooltips() {
        // Check if Tippy.js is available
        if (typeof tippy !== 'undefined') {
            // Initialize Tippy.js for elements with data-toggle="tooltip"
            var tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');
            if (tooltipElements.length > 0) {
                tippy(tooltipElements, {
                    content: function(reference) {
                        return reference.getAttribute('title') || reference.getAttribute('data-original-title');
                    },
                    placement: function(reference) {
                        return reference.getAttribute('data-placement') || 'top';
                    },
                    arrow: true,
                    animation: 'fade',
                    theme: 'light-border'
                });
            }
            
            // Initialize Tippy.js for popovers
            var popoverElements = document.querySelectorAll('[data-toggle="popover"]');
            if (popoverElements.length > 0) {
                tippy(popoverElements, {
                    content: function(reference) {
                        return reference.getAttribute('data-content') || '';
                    },
                    placement: function(reference) {
                        return reference.getAttribute('data-placement') || 'top';
                    },
                    arrow: true,
                    animation: 'fade',
                    theme: 'light-border',
                    interactive: true,
                    allowHTML: true
                });
            }
        } else {
            // Fallback: Use native title tooltips
            console.info('CanvaStackTooltip.init: Tippy.js not available, using native title tooltips for Tailwind template');
            
            // For native tooltips, ensure title attribute is set
            var tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');
            tooltipElements.forEach(function(element) {
                if (!element.hasAttribute('title') && element.hasAttribute('data-original-title')) {
                    element.setAttribute('title', element.getAttribute('data-original-title'));
                }
            });
        }
    }
    
    /**
     * Destroy all tooltips (useful for cleanup or re-initialization)
     */
    function destroy() {
        var template = getTemplate();
        
        switch (template) {
            case 'canvas':
                // Destroy Tippy.js instances if available
                if (typeof tippy !== 'undefined') {
                    var tooltipElements = document.querySelectorAll('[data-toggle="tooltip"], [data-toggle="popover"]');
                    tooltipElements.forEach(function(element) {
                        if (element._tippy) {
                            element._tippy.destroy();
                        }
                    });
                }
                break;
                
            case 'canvasign':
                // Destroy Bootstrap 5 tooltips and popovers
                if (typeof bootstrap !== 'undefined') {
                    if (bootstrap.Tooltip) {
                        var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltips.forEach(function(element) {
                            var instance = bootstrap.Tooltip.getInstance(element);
                            if (instance) {
                                instance.dispose();
                            }
                        });
                    }
                    if (bootstrap.Popover) {
                        var popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
                        popovers.forEach(function(element) {
                            var instance = bootstrap.Popover.getInstance(element);
                            if (instance) {
                                instance.dispose();
                            }
                        });
                    }
                } else if (typeof $.fn.tooltip === 'function') {
                    $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                    $('[data-bs-toggle="popover"]').popover('dispose');
                }
                break;
                
            case 'default':
            default:
                // Destroy Bootstrap 4 tooltips and popovers
                if (typeof $.fn.tooltip === 'function') {
                    $('[data-toggle="tooltip"]').tooltip('dispose');
                }
                if (typeof $.fn.popover === 'function') {
                    $('[data-toggle="popover"]').popover('dispose');
                }
                break;
        }
    }
    
    // Public API
    return {
        init: init,
        destroy: destroy,
        getTemplate: getTemplate
    };
})();

// Export for CommonJS/Node.js environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CanvaStackTooltip;
}

// Export for AMD/RequireJS environments
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return CanvaStackTooltip;
    });
}

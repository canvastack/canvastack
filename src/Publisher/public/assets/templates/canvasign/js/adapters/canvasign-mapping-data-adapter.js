/**
 * Canvasign Mapping Page - Data Adapter
 * 
 * Fixes Choices.js initialization for saved data in mapping page privileges.
 * This adapter bridges the gap between server-rendered HTML and Choices.js expectations.
 * 
 * PROBLEM:
 * - Server renders class attributes WITHOUT SPACES between classes
 * - Example: class="role__...field_value form-select" (no space before field_value)
 * - Choices.js fails to initialize because selector doesn't match
 * - Result: Saved data shows as plain <select> without Choices.js wrapper
 * 
 * SOLUTION:
 * - Pre-process all select elements before Choices.js initialization
 * - Fix malformed class attributes by adding proper spacing
 * - Force initialize Choices.js for all mapping page selects
 * 
 * @package CanvaStack
 * @subpackage Canvasign Theme
 * @version 1.0.0
 * @author CanvaStack
 */

(function() {
    'use strict';
    
    console.log('🔧 Canvasign Mapping Data Adapter loaded');
    
    /**
     * Fix malformed class attributes by adding proper spacing
     * 
     * @param {HTMLElement} element - Select element to fix
     */
    function fixClassAttribute(element) {
        var classAttr = element.getAttribute('class');
        if (!classAttr) return;
        
        // Check if class attribute has malformed spacing
        // Pattern: ...field_value (no space before field_value)
        // Pattern: ...field_name (no space before field_name)
        var needsFix = false;
        var fixed = classAttr;
        
        // Fix field_value without space before it
        if (/[^\s]field_value/.test(classAttr)) {
            fixed = fixed.replace(/([^\s])(field_value)/g, '$1 $2');
            needsFix = true;
        }
        
        // Fix field_name without space before it
        if (/[^\s]field_name/.test(classAttr)) {
            fixed = fixed.replace(/([^\s])(field_name)/g, '$1 $2');
            needsFix = true;
        }
        
        // Fix form-select without space before it
        if (/[^\s]form-select/.test(classAttr)) {
            fixed = fixed.replace(/([^\s])(form-select)/g, '$1 $2');
            needsFix = true;
        }
        
        if (needsFix) {
            console.log('🔧 Fixed class attribute:', {
                before: classAttr,
                after: fixed,
                element: element.id
            });
            element.setAttribute('class', fixed);
        }
    }
    
    /**
     * Initialize Choices.js for a specific select element
     * 
     * @param {HTMLElement} element - Select element
     * @param {boolean} isMultiple - Whether it's a multi-select
     */
    function initializeChoicesForElement(element, isMultiple) {
        // Skip if already initialized
        if (element.classList.contains('choices__input') || 
            element.closest('.choices') ||
            element.choicesInstance) {
            console.log('⏭️ Skipping - already initialized:', element.id);
            return;
        }
        
        // Skip if element has no options (will be populated by AJAX)
        var optionCount = element.querySelectorAll('option').length;
        if (optionCount === 0) {
            console.log('⏭️ Skipping - no options yet (will be populated by AJAX):', element.id);
            return;
        }
        
        try {
            var options = {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                removeItemButton: isMultiple,  // Enable remove button for multi-select
                callbackOnChange: function(value) {
                    // Dispatch native change event
                    var event = new Event('change', { bubbles: true });
                    element.dispatchEvent(event);
                    
                    // Also trigger jQuery change event for legacy code
                    if (typeof $ !== 'undefined') {
                        $(element).trigger('change');
                    }
                }
            };
            
            var instance = new Choices(element, options);
            
            // Store instance reference
            element.choicesInstance = instance;
            element._choices = instance;
            
            if (typeof $ !== 'undefined') {
                $(element).data('choices', instance);
            }
            
            // Store instance on wrapper
            setTimeout(function() {
                var wrapper = element.closest('.choices');
                if (wrapper) {
                    wrapper._choices = instance;
                }
            }, 100);
            
            console.log('✅ Initialized Choices.js for saved data:', element.id, 'Multiple:', isMultiple, 'Options:', optionCount);
            
        } catch (e) {
            console.error('❌ Failed to initialize Choices.js:', element.id, e);
        }
    }
    
    /**
     * Process all mapping page select elements
     */
    function processMappingPageSelects() {
        console.log('🔍 Processing mapping page selects...');
        
        // Find all select elements in mapping page privileges tab
        var mappingTab = document.getElementById('mapping-page-privileges');
        if (!mappingTab) {
            console.warn('⚠️ Mapping page privileges tab not found');
            return;
        }
        
        // Find all select elements with field_value or field_name in class
        var selects = mappingTab.querySelectorAll('select[class*="field_value"], select[class*="field_name"]');
        console.log('🔍 Found', selects.length, 'mapping page selects');
        
        selects.forEach(function(select) {
            // Fix class attribute first
            fixClassAttribute(select);
            
            // Check if it's a multi-select
            var isMultiple = select.hasAttribute('multiple');
            
            // Initialize Choices.js
            initializeChoicesForElement(select, isMultiple);
        });
    }
    
    /**
     * Initialize on DOM ready
     */
    function init() {
        if (typeof Choices === 'undefined') {
            console.warn('⚠️ Choices.js not loaded, skipping saved data fix');
            return;
        }
        
        // Process immediately if DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Wait a bit for other scripts to load
                setTimeout(processMappingPageSelects, 500);
            });
        } else {
            // DOM already loaded
            setTimeout(processMappingPageSelects, 500);
        }
        
        // Also process when tab is shown
        document.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('href') === '#mapping-page-privileges') {
                console.log('🔄 Mapping page tab shown, processing selects...');
                setTimeout(processMappingPageSelects, 200);
            }
        });
        
        // jQuery tab event (for compatibility)
        if (typeof $ !== 'undefined') {
            $(document).on('shown.bs.tab', 'a[href="#mapping-page-privileges"]', function() {
                console.log('🔄 Mapping page tab shown (jQuery), processing selects...');
                setTimeout(processMappingPageSelects, 200);
            });
        }
    }
    
    // Export to global scope
    window.CanvasignMappingDataAdapter = {
        fixClassAttribute: fixClassAttribute,
        initializeChoicesForElement: initializeChoicesForElement,
        processMappingPageSelects: processMappingPageSelects
    };
    
    // Initialize
    init();
    
})();

/**
 * CanvaStack Universal Select Handler
 * 
 * Universal select plugin handler that works with:
 * - Chosen.js (Bootstrap 4 / default template)
 * - Choices.js (Bootstrap 5 / canvasign template)
 * - Select2 (alternative plugin)
 * - Native select (TailwindCSS / canvas template)
 * 
 * This allows the same code to work across all templates without modification.
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';
    
    /**
     * Find Choices.js instance for a given element
     * 
     * @param {HTMLElement} element - Select element
     * @returns {Object|null} Choices.js instance or null
     */
    window.findChoicesInstance = function(element) {
        if (!element || typeof Choices === 'undefined') return null;
        
        // Method 1: Check global CanvasignPlugins instances
        if (window.CanvasignPlugins && window.CanvasignPlugins.choicesInstances) {
            var instance = window.CanvasignPlugins.choicesInstances.find(function(inst) {
                return inst.passedElement && inst.passedElement.element === element;
            });
            if (instance) return instance;
        }
        
        // Method 2: Check if element has choices wrapper with stored instance
        var choicesWrapper = element.closest ? element.closest('.choices') : $(element).closest('.choices')[0];
        if (choicesWrapper && choicesWrapper._choices) {
            return choicesWrapper._choices;
        }
        
        // Method 3: Check element itself for stored instance
        if (element._choices) {
            return element._choices;
        }
        
        // Method 4: Check jQuery data
        var $element = $(element);
        if ($element.data('choices')) {
            return $element.data('choices');
        }
        
        return null;
    };
    
    /**
     * Universal select update function
     * 
     * Automatically detects and updates the appropriate select plugin:
     * - Choices.js (Bootstrap 5)
     * - Chosen.js (Bootstrap 4)
     * - Select2 (alternative)
     * - Native select (fallback)
     * 
     * @param {string} target - jQuery selector for select element
     * @param {boolean} reset - Clear existing options (default: true)
     * @param {string|boolean} optstring - Default option text or false to skip (default: 'Select an Option')
     * 
     * @example
     * // Clear and add default option
     * updateSelectChosen('#mySelect', true, 'Choose an option');
     * 
     * // Just refresh without clearing
     * updateSelectChosen('#mySelect', false, false);
     * 
     * // Add options then refresh
     * $('#mySelect').append('<option value="1">Option 1</option>');
     * updateSelectChosen('#mySelect', false, false);
     */
    window.updateSelectChosen = function(target, reset, optstring) {
        reset = (typeof reset !== 'undefined') ? reset : true;
        optstring = (typeof optstring !== 'undefined') ? optstring : 'Select an Option';
        
        var chosenTarget = $(target);
        
        if (!chosenTarget.length) {
            console.warn('updateSelectChosen: Target element not found:', target);
            return;
        }
        
        // Reset options if requested
        if (reset === true) {
            chosenTarget.find('option').remove().end();
        }
        
        // Add default option if provided
        if (optstring !== false) {
            chosenTarget.append('<option value="">' + optstring + '</option>');
        }
        
        // Detect and update the appropriate select plugin
        
        // 1. Check for Choices.js (Bootstrap 5 / canvasign template)
        var choicesInstance = window.findChoicesInstance(chosenTarget[0]);
        if (choicesInstance) {
            try {
                // Clear existing choices
                choicesInstance.clearStore();
                
                // Rebuild choices from current options
                var choices = [];
                chosenTarget.find('option').each(function() {
                    var option = $(this);
                    choices.push({
                        value: option.val(),
                        label: option.text(),
                        selected: option.prop('selected'),
                        disabled: option.prop('disabled')
                    });
                });
                
                // Set new choices
                choicesInstance.setChoices(choices, 'value', 'label', true);
                return; // Exit early if Choices.js handled
            } catch (e) {
                console.warn('Choices.js update failed, falling back to native:', e);
            }
        }
        
        // 2. Check for Chosen.js (Bootstrap 4 / default template)
        if (typeof $.fn.chosen !== 'undefined' && chosenTarget.data('chosen')) {
            try {
                chosenTarget.trigger('chosen:updated');
                return; // Exit early if Chosen.js handled
            } catch (e) {
                console.warn('Chosen.js update failed, falling back to native:', e);
            }
        }
        
        // 3. Check for Select2 (alternative plugin)
        if (typeof $.fn.select2 !== 'undefined' && chosenTarget.data('select2')) {
            try {
                chosenTarget.trigger('change.select2');
                return; // Exit early if Select2 handled
            } catch (e) {
                console.warn('Select2 update failed, falling back to native:', e);
            }
        }
        
        // 4. Fallback: Native select (TailwindCSS / canvas template or no plugin)
        // Just trigger change event for native selects
        chosenTarget.trigger('change');
    };
    
    /**
     * Initialize select plugin based on template
     * 
     * @param {string} selector - jQuery selector for select elements
     * @param {object} options - Plugin-specific options
     * 
     * @example
     * initSelectPlugin('.my-select', { searchEnabled: true });
     */
    window.initSelectPlugin = function(selector, options) {
        options = options || {};
        
        var $selects = $(selector);
        
        if (!$selects.length) {
            return;
        }
        
        // Detect template and initialize appropriate plugin
        var template = window.canvastackTemplate || 'default';
        
        switch (template) {
            case 'canvasign':
                // Bootstrap 5: Use Choices.js
                if (typeof Choices !== 'undefined') {
                    $selects.each(function() {
                        if (!this._choices && !$(this).closest('.choices').length) {
                            var instance = new Choices(this, options);
                            this._choices = instance;
                            $(this).data('choices', instance);
                        }
                    });
                }
                break;
                
            case 'canvas':
                // TailwindCSS: Use native or Choices.js if available
                if (typeof Choices !== 'undefined') {
                    $selects.each(function() {
                        if (!this._choices && !$(this).closest('.choices').length) {
                            var instance = new Choices(this, options);
                            this._choices = instance;
                            $(this).data('choices', instance);
                        }
                    });
                }
                break;
                
            case 'default':
            default:
                // Bootstrap 4: Use Chosen.js
                if (typeof $.fn.chosen !== 'undefined') {
                    $selects.chosen(options);
                }
                break;
        }
    };
    
    console.log('CanvaStack Universal Select Handler loaded');
    
})(window, jQuery);

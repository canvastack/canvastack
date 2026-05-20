/**
 * Canvastack Input Chain Plugin
 * Auto-generates field value from multiple source fields
 * 
 * @version 1.0.0
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    window.CanvastackInputChain = {
        instances: {},
        
        /**
         * Parse JSON attribute from data attribute
         */
        parseJsonAttribute: function(value, defaultValue) {
            if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
                return value;
            }
            
            if (typeof value === 'string') {
                try {
                    return JSON.parse(value);
                } catch (e) {
                    return value;
                }
            }
            
            return value !== undefined && value !== null ? value : defaultValue;
        },
        
        /**
         * Initialize all input chain fields
         */
        init: function() {
            var self = this;
            
            $('[data-chain-field="true"]').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name') || $input.attr('id');
                
                if (!fieldName || self.instances[fieldName]) {
                    return;
                }
                
                self.createInstance($input);
            });
        },
        
        /**
         * Create input chain instance for a field
         */
        createInstance: function($input) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            var config = {
                sources: self.parseJsonAttribute($input.data('chain-sources'), []),
                separator: $input.data('chain-separator') || '-',
                transform: $input.data('chain-transform') || 'uppercase',
                prefix: $input.data('chain-prefix') || '',
                suffix: $input.data('chain-suffix') || '',
                maxWords: parseInt($input.data('chain-max-words')) || 2,
                format: $input.data('chain-format') || 'default',
                debounce: parseInt($input.data('chain-debounce')) || 300,
                autoUpdate: $input.data('chain-auto-update') !== 'false',
                skipEmpty: $input.data('chain-skip-empty') !== 'false',
                trimSpaces: $input.data('chain-trim-spaces') !== 'false',
                removeSpecial: $input.data('chain-remove-special') !== 'false',
                wordSeparator: $input.data('chain-word-separator') || '-'
            };
            
            self.instances[fieldName] = {
                $input: $input,
                config: config,
                debounceTimer: null
            };
            
            // Bind events
            self.bindEvents($input, config);
            
            // ⭐ FIX: Don't auto-generate on init - only generate when user types or clicks button
            // Initial generation removed to prevent unwanted values on page load
        },
        
        /**
         * Bind events to source fields
         */
        bindEvents: function($input, config) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            var instance = self.instances[fieldName];
            
            if (!instance) return;
            
            // Bind to manual generate button
            $('[data-chain-generate="' + fieldName + '"]').on('click', function() {
                self.generateValue($input, config);
            });
            
            // Bind to source fields (if auto-update enabled)
            if (config.autoUpdate) {
                config.sources.forEach(function(sourceName) {
                    var $sourceField = $('[name="' + sourceName + '"]');
                    
                    if ($sourceField.length > 0) {
                        $sourceField.on('input change', function() {
                            // Clear previous timer
                            if (instance.debounceTimer) {
                                clearTimeout(instance.debounceTimer);
                            }
                            
                            // Set new timer
                            instance.debounceTimer = setTimeout(function() {
                                self.generateValue($input, config);
                            }, config.debounce);
                        });
                    }
                });
            }
        },
        
        /**
         * Generate value from source fields
         */
        generateValue: function($input, config) {
            var self = this;
            var values = [];
            
            // Collect values from source fields
            config.sources.forEach(function(sourceName) {
                var $sourceField = $('[name="' + sourceName + '"]');
                
                if ($sourceField.length > 0) {
                    var value = $sourceField.val();
                    
                    // Skip empty values if configured
                    if (!value && config.skipEmpty) {
                        return;
                    }
                    
                    // Trim spaces if configured
                    if (config.trimSpaces && typeof value === 'string') {
                        value = value.trim();
                    }
                    
                    // Transform value
                    var transformed = self.transformValue(value, config, sourceName);
                    
                    if (transformed) {
                        values.push(transformed);
                    }
                }
            });
            
            // Generate final value
            var finalValue = '';
            
            if (values.length > 0) {
                finalValue = config.prefix + values.join(config.separator) + config.suffix;
            }
            
            // Update input field
            $input.val(finalValue);
        },
        
        /**
         * Transform value based on configuration
         */
        transformValue: function(value, config, sourceName) {
            var self = this;
            
            if (!value) return '';
            
            value = String(value);
            
            // Remove special characters if configured
            if (config.removeSpecial) {
                value = value.replace(/[^a-zA-Z0-9\s-]/g, '');
            }
            
            // Apply format-specific transformations
            switch (config.format) {
                case 'slug':
                    return self.transformSlug(value, config);
                    
                case 'code':
                    return self.transformCode(value, config);
                    
                default:
                    return self.transformDefault(value, config);
            }
        },
        
        /**
         * Transform to default format (uppercase with max words)
         */
        transformDefault: function(value, config) {
            // Split into words
            var words = value.split(/\s+/).filter(function(word) {
                return word.length > 0;
            });
            
            // Take max words
            words = words.slice(0, config.maxWords);
            
            // Join with word separator
            var result = words.join(config.wordSeparator);
            
            // Apply transform
            switch (config.transform) {
                case 'uppercase':
                    return result.toUpperCase();
                    
                case 'lowercase':
                    return result.toLowerCase();
                    
                case 'title':
                    return result.replace(/\b\w/g, function(char) {
                        return char.toUpperCase();
                    });
                    
                default:
                    return result.toUpperCase();
            }
        },
        
        /**
         * Transform to slug format (lowercase with hyphens)
         */
        transformSlug: function(value, config) {
            return value
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        },
        
        /**
         * Transform to code format (abbreviation)
         */
        transformCode: function(value, config) {
            // Split into words
            var words = value.split(/\s+/).filter(function(word) {
                return word.length > 0;
            });
            
            // Take max words
            words = words.slice(0, config.maxWords);
            
            // Get first letter of each word
            var abbreviation = words.map(function(word) {
                return word.charAt(0).toUpperCase();
            }).join('');
            
            return abbreviation;
        }
    };
    
})(jQuery);

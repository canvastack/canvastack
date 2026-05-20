/**
 * Canvastack QR Code Plugin
 * Handles QR code generation, scanning, and preview
 * 
 * @version 1.0.0
 * @requires QRCode.js, Html5Qrcode
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    window.CanvastackQRCode = {
        instances: {},
        scannerActive: false,
        html5QrCode: null,
        
        /**
         * Parse JSON attribute from data attribute
         * jQuery .data() doesn't auto-parse JSON strings, so we need to do it manually
         */
        parseJsonAttribute: function(value, defaultValue) {
            // If already parsed (array or object), return as-is
            if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
                return value;
            }
            
            // If string, try to parse as JSON
            if (typeof value === 'string') {
                try {
                    var parsed = JSON.parse(value);
                    return parsed;
                } catch (e) {
                    // Not valid JSON, return as-is (might be 'all' or other string value)
                    return value;
                }
            }
            
            // If undefined or null, return default
            if (value === undefined || value === null) {
                return defaultValue;
            }
            
            return value;
        },
        
        /**
         * Initialize all QR code fields
         */
        init: function() {
            var self = this;
            
            $('[data-qrcode-field="true"]').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name') || $input.attr('id');
                
                if (!fieldName || self.instances[fieldName]) {
                    return;
                }
                
                self.createInstance($input);
            });
        },
        
        /**
         * Create QR code instance for a field
         */
        createInstance: function($input) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            var config = {
                format: $input.data('qrcode-format') || 'text',
                size: parseInt($input.data('qrcode-size')) || 200,
                errorCorrection: $input.data('qrcode-error-correction') || 'M',
                previewPosition: $input.data('qrcode-preview-position') || 'top',
                validate: $input.data('qrcode-validate') === true,
                autoGenerate: $input.data('qrcode-auto-generate') === true,
                autoSource: $input.data('qrcode-auto-source'),
                autoSeparator: $input.data('qrcode-auto-separator') || '-',
                autoPrefix: $input.data('qrcode-auto-prefix') || '',
                autoFormat: $input.data('qrcode-auto-format') || 'text',
                autoUpdateFromForm: $input.data('qrcode-auto-update-form') === true,
                autoUpdateDelay: parseInt($input.data('qrcode-auto-update-delay')) || 500,
                autoClearEmpty: $input.data('qrcode-auto-clear-empty') === true,
                formFields: self.parseJsonAttribute($input.data('qrcode-form-fields'), 'all'),
                formFormat: $input.data('qrcode-form-format') || 'json',
                formExclude: self.parseJsonAttribute($input.data('qrcode-form-exclude'), ['_token', '_method', 'image', 'file']),
                formUrlBase: $input.data('qrcode-form-url-base') || window.location.origin
            };
            
            self.instances[fieldName] = {
                $input: $input,
                config: config,
                qrcode: null
            };
            
            // Bind events
            self.bindEvents($input, config);
            
            // Initial preview if value exists
            if ($input.val()) {
                self.updatePreview($input, $input.val(), config);
            }
            
            // Auto-generate if enabled
            if (config.autoGenerate) {
                self.setupAutoGenerate($input, config);
            }
            
            // Auto-update from form if enabled
            if (config.autoUpdateFromForm) {
                self.setupAutoUpdateFromForm($input, config);
            }
        },
        
        /**
         * Bind input events
         */
        bindEvents: function($input, config) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            // Update preview on input
            $input.on('input change', function() {
                var value = $(this).val();
                if (value) {
                    self.updatePreview($input, value, config);
                } else {
                    self.clearPreview($input);
                }
            });
            
            // Scanner button
            $('[data-qrcode-scanner="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.startScanner($input, config);
            });
            
            // Generate button
            $('[data-qrcode-generate="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.generateQRCode($input, config);
            });
            
            // Generate from form button
            $('[data-qrcode-generate-form="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.generateFromForm($input, config);
            });
        },
        
        /**
         * Update QR code preview with loading skeleton
         */
        updatePreview: function($input, value, config) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            var $preview = $('[data-qrcode-preview="' + fieldName + '"]');
            
            if ($preview.length === 0) return;
            
            // Show loading skeleton
            self.showLoadingSkeleton($preview, config);
            
            // Add loading state to input
            $input.addClass('qrcode-input-loading').prop('disabled', true);
            
            // Simulate async operation for smooth UX
            setTimeout(function() {
                try {
                    // Clear skeleton
                    $preview.empty();
                    
                    // Create QR code
                    var qrcode = new QRCode($preview[0], {
                        text: value,
                        width: config.size,
                        height: config.size,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel[config.errorCorrection]
                    });
                    
                    // Store instance
                    if (self.instances[fieldName]) {
                        self.instances[fieldName].qrcode = qrcode;
                    }
                    
                    // Add fade-in animation
                    $preview.addClass('qrcode-fade-enter');
                    setTimeout(function() {
                        $preview.addClass('qrcode-fade-enter-active');
                    }, 10);
                    
                    // Show preview
                    $preview.show();
                    
                    // Remove loading state from input
                    $input.removeClass('qrcode-input-loading').prop('disabled', false);
                    
                } catch (error) {
                    console.error('QR Code generation error:', error);
                    $preview.hide();
                    $input.removeClass('qrcode-input-loading').prop('disabled', false);
                }
            }, 300); // 300ms delay for smooth skeleton animation
        },
        
        /**
         * Show loading skeleton
         */
        showLoadingSkeleton: function($preview, config) {
            $preview.empty();
            
            var $skeleton = $('<div class="qrcode-skeleton"></div>');
            $skeleton.css({
                width: config.size + 'px',
                height: config.size + 'px'
            });
            
            $preview.append($skeleton).show();
        },
        
        /**
         * Clear QR code preview
         */
        clearPreview: function($input) {
            var fieldName = $input.attr('name') || $input.attr('id');
            var $preview = $('[data-qrcode-preview="' + fieldName + '"]');
            
            $preview.empty().hide();
            
            if (this.instances[fieldName]) {
                this.instances[fieldName].qrcode = null;
            }
        },
        
        /**
         * Setup auto-generate functionality
         */
        setupAutoGenerate: function($input, config) {
            var self = this;
            
            // Watch source fields
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            
            sources.forEach(function(source) {
                var $sourceField = $('[name="' + source + '"]');
                
                $sourceField.on('change', function() {
                    self.generateQRCode($input, config);
                });
            });
        },
        
        /**
         * Setup auto-update from form (real-time)
         * Watches all form fields and updates QR code automatically
         */
        setupAutoUpdateFromForm: function($input, config) {
            var self = this;
            var $form = $input.closest('form');
            
            if ($form.length === 0) return;
            
            // Debounce function to avoid too many updates
            var debounceTimer;
            var debounceDelay = config.autoUpdateDelay || 500; // 500ms default
            
            // Watch all form fields for changes
            $form.on('input change', 'input, select, textarea', function() {
                var $changedField = $(this);
                
                // Skip if it's the QR code field itself
                if ($changedField.is($input)) return;
                
                // Skip file inputs
                if ($changedField.attr('type') === 'file') return;
                
                // Clear previous timer
                clearTimeout(debounceTimer);
                
                // Set new timer for debounced update
                debounceTimer = setTimeout(function() {
                    self.autoUpdateFromForm($input, config);
                }, debounceDelay);
            });
        },
        
        /**
         * Auto-update QR code from form data (called by debounced event)
         */
        autoUpdateFromForm: function($input, config) {
            var self = this;
            var $form = $input.closest('form');
            
            if ($form.length === 0) return;
            
            var formFields = config.formFields || 'all'; // ⭐ Get formFields config
            var formFormat = config.formFormat || 'json';
            var formExclude = config.formExclude || ['_token', '_method', 'image', 'file'];
            var formData = {};
            var hasData = false;
            
            $form.find('input, select, textarea').not('[type="file"]').each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name');
                var fieldValue = $field.val();
                
                if (!fieldName || formExclude.indexOf(fieldName) !== -1 || !fieldValue) {
                    return;
                }
                
                // Skip the QR code field itself
                if ($field.is($input)) return;
                
                // Skip all QR code and barcode fields to prevent recursive inclusion
                if ($field.attr('data-qrcode-field') === 'true' || $field.attr('data-barcode-field') === 'true') {
                    return;
                }
                
                // ⭐ NEW: If specific fields are requested, only include those
                if (Array.isArray(formFields) && formFields.length > 0) {
                    // Only include if field name is in the formFields array
                    if (formFields.indexOf(fieldName) === -1) {
                        return; // Skip this field
                    }
                }
                // If formFields is 'all' or not an array, include all fields (default behavior)
                
                var fieldLabel = self.getFieldLabel($field);
                
                if ($field.is('select')) {
                    var selectedText = $field.find('option:selected').text();
                    if (selectedText && selectedText !== 'Select...') {
                        // Decode HTML entities in select text
                        formData[fieldLabel || fieldName] = self.decodeHtmlEntities(selectedText);
                        hasData = true;
                    }
                } else {
                    formData[fieldLabel || fieldName] = fieldValue;
                    hasData = true;
                }
            });
            
            // Only update if there's data
            if (!hasData) {
                // Clear QR code if no data and autoClearEmpty is enabled
                if (config.autoClearEmpty) {
                    $input.val('').trigger('input');
                }
                return;
            }
            
            var qrData = '';
            
            switch (formFormat) {
                case 'json':
                    qrData = JSON.stringify(formData, null, 2);
                    break;
                case 'url':
                    var params = [];
                    for (var key in formData) {
                        params.push(encodeURIComponent(key) + '=' + encodeURIComponent(formData[key]));
                    }
                    qrData = (config.formUrlBase || window.location.origin) + '?' + params.join('&');
                    break;
                case 'text':
                default:
                    var lines = [];
                    for (var key in formData) {
                        lines.push(key + ': ' + formData[key]);
                    }
                    qrData = lines.join('\n');
                    break;
            }
            
            // Update input value and trigger preview
            $input.val(qrData).trigger('input');
        },
        
        /**
         * Generate QR code from all form data
         */
        generateFromForm: function($input, config) {
            var self = this;
            var $form = $input.closest('form');
            var fieldName = $input.attr('name') || $input.attr('id');
            var $button = $('[data-qrcode-generate-form="' + fieldName + '"]');
            
            if ($form.length === 0) {
                alert('Form not found');
                return;
            }
            
            // Add loading state to button
            $button.addClass('loading').prop('disabled', true);
            
            // Get form data configuration
            var formFields = config.formFields || 'all'; // 'all' or array of field names
            var formFormat = config.formFormat || 'json'; // 'json', 'text', 'url'
            var formExclude = config.formExclude || ['_token', '_method', 'image', 'file']; // Fields to exclude
            
            // Debug: Log configuration
            console.log('QR Code Generate from Form - Config:', {
                formFields: formFields,
                formFormat: formFormat,
                formExclude: formExclude,
                isArray: Array.isArray(formFields),
                fieldsLength: Array.isArray(formFields) ? formFields.length : 'N/A',
                rawDataAttr: $input.attr('data-qrcode-form-fields') // ⭐ Show raw HTML attribute
            });
            
            var formData = {};
            var $formInputs = $form.find('input, select, textarea').not('[type="file"]');
            
            $formInputs.each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name');
                var fieldValue = $field.val();
                
                // Skip the QR code field itself to prevent recursive inclusion
                if ($field.is($input)) {
                    return;
                }
                
                // Skip all QR code and barcode fields to prevent recursive inclusion
                if ($field.attr('data-qrcode-field') === 'true' || $field.attr('data-barcode-field') === 'true') {
                    return;
                }
                
                // Skip if no name or excluded
                if (!fieldName || formExclude.indexOf(fieldName) !== -1) {
                    return;
                }
                
                // ✅ NEW: If specific fields are requested, only include those
                if (Array.isArray(formFields) && formFields.length > 0) {
                    // Only include if field name is in the formFields array
                    console.log('Checking field:', fieldName, 'in', formFields, '=', formFields.indexOf(fieldName) !== -1);
                    if (formFields.indexOf(fieldName) === -1) {
                        return; // Skip this field
                    }
                }
                // If formFields is 'all' or not an array, include all fields (default behavior)
                
                // Skip empty values (optional)
                if (!fieldValue || fieldValue === '') {
                    return;
                }
                
                // Get field label
                var fieldLabel = self.getFieldLabel($field);
                
                // Handle select boxes - get text instead of value
                if ($field.is('select')) {
                    var selectedText = $field.find('option:selected').text();
                    if (selectedText && selectedText !== 'Select...') {
                        // Decode HTML entities in select text
                        formData[fieldLabel || fieldName] = self.decodeHtmlEntities(selectedText);
                    }
                } else {
                    formData[fieldLabel || fieldName] = fieldValue;
                }
            });
            
            if (Object.keys(formData).length === 0) {
                $button.removeClass('loading').prop('disabled', false);
                alert('No form data available. Please fill in the form first.');
                return;
            }
            
            var qrData = '';
            
            // Format based on type
            switch (formFormat) {
                case 'json':
                    qrData = JSON.stringify(formData, null, 2);
                    break;
                    
                case 'url':
                    var params = [];
                    for (var key in formData) {
                        params.push(encodeURIComponent(key) + '=' + encodeURIComponent(formData[key]));
                    }
                    qrData = (config.formUrlBase || window.location.origin) + '?' + params.join('&');
                    break;
                    
                case 'text':
                default:
                    var lines = [];
                    for (var key in formData) {
                        lines.push(key + ': ' + formData[key]);
                    }
                    qrData = lines.join('\n');
                    break;
            }
            
            // Set value and trigger preview (with delay for loading animation)
            setTimeout(function() {
                $input.val(qrData).trigger('input');
                
                // Remove loading state after QR code is generated
                setTimeout(function() {
                    $button.removeClass('loading').prop('disabled', false);
                    self.showMessage($input, 'QR Code generated from form data', 'success');
                }, 400); // Wait for QR code generation
            }, 200); // Small delay for loading animation
        },
        
        /**
         * Get field label (clean from required indicators)
         */
        getFieldLabel: function($field) {
            var fieldId = $field.attr('id');
            var fieldName = $field.attr('name');
            
            // Try to find label by for attribute
            if (fieldId) {
                var $label = $('label[for="' + fieldId + '"]');
                if ($label.length > 0) {
                    return this.cleanLabel($label.text());
                }
            }
            
            // Try to find label in same form-group
            var $formGroup = $field.closest('.form-group, .mb-3, .row');
            if ($formGroup.length > 0) {
                var $label = $formGroup.find('label').first();
                if ($label.length > 0) {
                    return this.cleanLabel($label.text());
                }
            }
            
            // Fallback to field name (convert snake_case to Title Case)
            return fieldName ? fieldName.replace(/_/g, ' ').replace(/\b\w/g, function(l) { 
                return l.toUpperCase(); 
            }) : '';
        },
        
        /**
         * Clean label text from required indicators and special characters
         */
        cleanLabel: function(labelText) {
            if (!labelText) return '';
            
            // Remove common required indicators
            labelText = labelText
                .replace(/\(\*\)/g, '')      // Remove (*)
                .replace(/\*\s*$/g, '')      // Remove trailing *
                .replace(/\s*\*\s*/g, ' ')   // Remove * with spaces
                .replace(/\(\)/g, '')        // Remove empty ()
                .replace(/\[\*\]/g, '')      // Remove [*]
                .replace(/\s+/g, ' ')        // Normalize spaces
                .trim();                     // Trim whitespace
            
            // Decode HTML entities
            labelText = this.decodeHtmlEntities(labelText);
            
            return labelText;
        },
        
        /**
         * Decode HTML entities
         */
        decodeHtmlEntities: function(text) {
            var textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        },
        
        /**
         * Show message
         */
        showMessage: function($input, message, type) {
            var fieldName = $input.attr('name') || $input.attr('id');
            var $error = $('[data-qrcode-error="' + fieldName + '"]');
            
            if ($error.length === 0) {
                // Create message element if not exists
                $error = $('<div class="qrcode-message mt-2"></div>');
                $input.closest('.input-group').after($error);
            }
            
            $error.removeClass('text-danger text-success text-info')
                  .addClass('text-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'))
                  .text(message)
                  .show();
            
            // Auto hide after 3 seconds
            setTimeout(function() {
                $error.fadeOut();
            }, 3000);
        },
        
        /**
         * Generate QR code from source fields
         */
        generateQRCode: function($input, config) {
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            var values = [];
            
            sources.forEach(function(source) {
                var $sourceField = $('[name="' + source + '"]');
                var value = $sourceField.val();
                
                if (value) {
                    values.push(value);
                }
            });
            
            if (values.length === 0) {
                alert('Please fill in the required fields first');
                return;
            }
            
            var qrData = '';
            
            // Format based on type
            switch (config.autoFormat) {
                case 'url':
                    qrData = config.autoPrefix + values.join(config.autoSeparator);
                    break;
                    
                case 'vcard':
                    qrData = this.generateVCard(config);
                    break;
                    
                case 'wifi':
                    qrData = this.generateWiFi(config);
                    break;
                    
                case 'text':
                default:
                    qrData = config.autoPrefix + values.join(config.autoSeparator);
                    break;
            }
            
            $input.val(qrData).trigger('input');
        },
        
        /**
         * Generate vCard format
         */
        generateVCard: function(config) {
            var vcard = 'BEGIN:VCARD\nVERSION:3.0\n';
            
            if (config.vcardName) vcard += 'FN:' + config.vcardName + '\n';
            if (config.vcardPhone) vcard += 'TEL:' + config.vcardPhone + '\n';
            if (config.vcardEmail) vcard += 'EMAIL:' + config.vcardEmail + '\n';
            if (config.vcardOrg) vcard += 'ORG:' + config.vcardOrg + '\n';
            if (config.vcardUrl) vcard += 'URL:' + config.vcardUrl + '\n';
            
            vcard += 'END:VCARD';
            
            return vcard;
        },
        
        /**
         * Generate WiFi format
         */
        generateWiFi: function(config) {
            var wifi = 'WIFI:';
            wifi += 'T:' + (config.wifiType || 'WPA') + ';';
            wifi += 'S:' + (config.wifiSSID || '') + ';';
            wifi += 'P:' + (config.wifiPassword || '') + ';';
            wifi += 'H:' + (config.wifiHidden ? 'true' : 'false') + ';';
            wifi += ';';
            
            return wifi;
        },
        
        /**
         * Start webcam scanner
         */
        startScanner: function($input, config) {
            var self = this;
            
            if (typeof Html5Qrcode === 'undefined') {
                alert('Scanner library not loaded. Please include Html5Qrcode.');
                return;
            }
            
            if (self.scannerActive) {
                console.warn('Scanner already active');
                return;
            }
            
            // Create scanner modal
            var modalId = 'qrcodeScannerModal_' + ($input.attr('name') || $input.attr('id'));
            var $modal = $('#' + modalId);
            
            if ($modal.length === 0) {
                $modal = self.createScannerModal(modalId);
                $('body').append($modal);
            }
            
            // Show modal
            var modal = new bootstrap.Modal($modal[0]);
            modal.show();
            
            // Initialize scanner
            var scannerId = 'qrcodeScannerReader';
            self.html5QrCode = new Html5Qrcode(scannerId);
            
            self.html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                function(decodedText, decodedResult) {
                    // Success callback
                    $input.val(decodedText).trigger('input');
                    
                    // Stop scanner and close modal
                    self.stopScanner();
                    modal.hide();
                },
                function(errorMessage) {
                    // Error callback (can be ignored for continuous scanning)
                }
            ).then(function() {
                self.scannerActive = true;
            }).catch(function(err) {
                console.error('Scanner start error:', err);
                alert('Failed to start camera: ' + err);
                modal.hide();
            });
            
            // Handle modal close
            $modal.on('hidden.bs.modal', function() {
                self.stopScanner();
            });
        },
        
        /**
         * Stop scanner
         */
        stopScanner: function() {
            var self = this;
            
            if (self.html5QrCode && self.scannerActive) {
                self.html5QrCode.stop().then(function() {
                    self.scannerActive = false;
                    self.html5QrCode = null;
                }).catch(function(err) {
                    console.error('Scanner stop error:', err);
                    self.scannerActive = false;
                    self.html5QrCode = null;
                });
            }
        },
        
        /**
         * Create scanner modal
         */
        createScannerModal: function(modalId) {
            return $(`
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Scan QR Code</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="qrcodeScannerReader" style="width: 100%;"></div>
                                <p class="mt-3 text-muted">Position the QR code within the camera view</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
    };
    
})(jQuery);

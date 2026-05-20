/**
 * Canvastack QR Code Plugin (Default Theme)
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
        
        parseJsonAttribute: function(value, defaultValue) {
            if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
                return value;
            }
            
            if (typeof value === 'string') {
                try {
                    var parsed = JSON.parse(value);
                    return parsed;
                } catch (e) {
                    return value;
                }
            }
            
            if (value === undefined || value === null) {
                return defaultValue;
            }
            
            return value;
        },
        
        init: function() {
            var self = this;
            
            $('[data-qrcode-field="true"]').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name') || $input.attr('id');
                
                if (!fieldName || self.instances[fieldName]) return;
                
                self.createInstance($input);
            });
        },
        
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
            
            self.bindEvents($input, config);
            
            if ($input.val()) {
                self.updatePreview($input, $input.val(), config);
            }
            
            if (config.autoGenerate) {
                self.setupAutoGenerate($input, config);
            }
            
            if (config.autoUpdateFromForm) {
                self.setupAutoUpdateFromForm($input, config);
            }
        },
        
        bindEvents: function($input, config) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            $input.on('input change', function() {
                var value = $(this).val();
                if (value) {
                    self.updatePreview($input, value, config);
                } else {
                    self.clearPreview($input);
                }
            });
            
            $('[data-qrcode-scanner="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.startScanner($input, config);
            });
            
            $('[data-qrcode-generate="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.generateQRCode($input, config);
            });
            
            $('[data-qrcode-generate-form="' + fieldName + '"]').on('click', function(e) {
                e.preventDefault();
                self.generateFromForm($input, config);
            });
        },
        
        updatePreview: function($input, value, config) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            var $preview = $('[data-qrcode-preview="' + fieldName + '"]');
            
            if ($preview.length === 0) return;
            
            self.showLoadingSkeleton($preview, config);
            $input.addClass('qrcode-input-loading').prop('disabled', true);
            
            setTimeout(function() {
                try {
                    $preview.empty();
                    
                    var qrcode = new QRCode($preview[0], {
                        text: value,
                        width: config.size,
                        height: config.size,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel[config.errorCorrection]
                    });
                    
                    if (self.instances[fieldName]) {
                        self.instances[fieldName].qrcode = qrcode;
                    }
                    
                    $preview.addClass('qrcode-fade-enter');
                    setTimeout(function() {
                        $preview.addClass('qrcode-fade-enter-active');
                    }, 10);
                    
                    $preview.show();
                    $input.removeClass('qrcode-input-loading').prop('disabled', false);
                    
                } catch (error) {
                    console.error('QR Code generation error:', error);
                    $preview.hide();
                    $input.removeClass('qrcode-input-loading').prop('disabled', false);
                }
            }, 300);
        },
        
        showLoadingSkeleton: function($preview, config) {
            $preview.empty();
            
            var $skeleton = $('<div class="qrcode-skeleton"></div>');
            $skeleton.css({
                width: config.size + 'px',
                height: config.size + 'px'
            });
            
            $preview.append($skeleton).show();
        },
        
        clearPreview: function($input) {
            var fieldName = $input.attr('name') || $input.attr('id');
            var $preview = $('[data-qrcode-preview="' + fieldName + '"]');
            
            $preview.empty().hide();
            
            if (this.instances[fieldName]) {
                this.instances[fieldName].qrcode = null;
            }
        },
        
        setupAutoGenerate: function($input, config) {
            var self = this;
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            
            sources.forEach(function(source) {
                $('[name="' + source + '"]').on('change', function() {
                    self.generateQRCode($input, config);
                });
            });
        },
        
        setupAutoUpdateFromForm: function($input, config) {
            var self = this;
            var $form = $input.closest('form');
            
            if ($form.length === 0) return;
            
            var debounceTimer;
            var debounceDelay = config.autoUpdateDelay || 500;
            
            $form.on('input change', 'input, select, textarea', function() {
                var $changedField = $(this);
                
                if ($changedField.is($input)) return;
                if ($changedField.attr('type') === 'file') return;
                
                clearTimeout(debounceTimer);
                
                debounceTimer = setTimeout(function() {
                    self.autoUpdateFromForm($input, config);
                }, debounceDelay);
            });
        },
        
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
                        formData[fieldLabel || fieldName] = self.decodeHtmlEntities(selectedText);
                        hasData = true;
                    }
                } else {
                    formData[fieldLabel || fieldName] = fieldValue;
                    hasData = true;
                }
            });
            
            if (!hasData) {
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
            
            $input.val(qrData).trigger('input');
        },
        
        generateQRCode: function($input, config) {
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            var values = [];
            
            sources.forEach(function(source) {
                var value = $('[name="' + source + '"]').val();
                if (value) values.push(value);
            });
            
            if (values.length === 0) {
                alert('Please fill in the required fields first');
                return;
            }
            
            var qrData = config.autoPrefix + values.join(config.autoSeparator);
            $input.val(qrData).trigger('input');
        },
        
        generateFromForm: function($input, config) {
            var self = this;
            var $form = $input.closest('form');
            var fieldName = $input.attr('name') || $input.attr('id');
            var $button = $('[data-qrcode-generate-form="' + fieldName + '"]');
            
            if ($form.length === 0) {
                alert('Form not found');
                return;
            }
            
            $button.addClass('loading').prop('disabled', true);
            
            var formFields = config.formFields || 'all';
            var formFormat = config.formFormat || 'json';
            var formExclude = config.formExclude || ['_token', '_method', 'image', 'file'];
            var formData = {};
            
            // Debug: Log configuration
            console.log('QR Code Generate from Form - Config:', {
                formFields: formFields,
                formFormat: formFormat,
                formExclude: formExclude,
                isArray: Array.isArray(formFields),
                fieldsLength: Array.isArray(formFields) ? formFields.length : 'N/A',
                rawDataAttr: $input.attr('data-qrcode-form-fields')
            });
            
            $form.find('input, select, textarea').not('[type="file"]').each(function() {
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
                
                if (!fieldName || formExclude.indexOf(fieldName) !== -1) {
                    return;
                }
                
                // If specific fields are requested, only include those
                if (Array.isArray(formFields) && formFields.length > 0) {
                    console.log('Checking field:', fieldName, 'in', formFields, '=', formFields.indexOf(fieldName) !== -1);
                    if (formFields.indexOf(fieldName) === -1) {
                        return;
                    }
                }
                
                if (!fieldValue) {
                    return;
                }
                
                var fieldLabel = self.getFieldLabel($field);
                
                if ($field.is('select')) {
                    var selectedText = $field.find('option:selected').text();
                    if (selectedText && selectedText !== 'Select...') {
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
            
            setTimeout(function() {
                $input.val(qrData).trigger('input');
                
                setTimeout(function() {
                    $button.removeClass('loading').prop('disabled', false);
                    alert('QR Code generated from form data');
                }, 400);
            }, 200);
        },
        
        getFieldLabel: function($field) {
            var fieldId = $field.attr('id');
            
            if (fieldId) {
                var $label = $('label[for="' + fieldId + '"]');
                if ($label.length > 0) {
                    return this.cleanLabel($label.text());
                }
            }
            
            var $formGroup = $field.closest('.form-group, .row');
            if ($formGroup.length > 0) {
                var $label = $formGroup.find('label').first();
                if ($label.length > 0) {
                    return this.cleanLabel($label.text());
                }
            }
            
            var fieldName = $field.attr('name');
            return fieldName ? fieldName.replace(/_/g, ' ').replace(/\b\w/g, function(l) { 
                return l.toUpperCase(); 
            }) : '';
        },
        
        cleanLabel: function(labelText) {
            if (!labelText) return '';
            
            labelText = labelText
                .replace(/\(\*\)/g, '')
                .replace(/\*\s*$/g, '')
                .replace(/\s*\*\s*/g, ' ')
                .replace(/\(\)/g, '')
                .replace(/\[\*\]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
            
            labelText = this.decodeHtmlEntities(labelText);
            
            return labelText;
        },
        
        decodeHtmlEntities: function(text) {
            var textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        },
        
        startScanner: function($input, config) {
            var self = this;
            
            if (typeof Html5Qrcode === 'undefined') {
                alert('Scanner library not loaded');
                return;
            }
            
            if (self.scannerActive) return;
            
            var modalId = 'qrcodeScannerModal_' + ($input.attr('name') || $input.attr('id'));
            var $modal = $('#' + modalId);
            
            if ($modal.length === 0) {
                $modal = self.createScannerModal(modalId);
                $('body').append($modal);
            }
            
            $modal.modal('show');
            
            var scannerId = 'qrcodeScannerReader';
            self.html5QrCode = new Html5Qrcode(scannerId);
            
            self.html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                function(decodedText) {
                    $input.val(decodedText).trigger('input');
                    self.stopScanner();
                    $modal.modal('hide');
                }
            ).then(function() {
                self.scannerActive = true;
            }).catch(function(err) {
                alert('Failed to start camera: ' + err);
                $modal.modal('hide');
            });
            
            $modal.on('hidden.bs.modal', function() {
                self.stopScanner();
            });
        },
        
        stopScanner: function() {
            var self = this;
            if (self.html5QrCode && self.scannerActive) {
                self.html5QrCode.stop().then(function() {
                    self.scannerActive = false;
                    self.html5QrCode = null;
                });
            }
        },
        
        createScannerModal: function(modalId) {
            return $(`
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Scan QR Code</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="qrcodeScannerReader"></div>
                                <p class="mt-3 text-muted">Position the QR code within the camera view</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
    };
    
    $(document).ready(function() {
        if (typeof QRCode !== 'undefined') {
            CanvastackQRCode.init();
        }
    });
    
})(jQuery);

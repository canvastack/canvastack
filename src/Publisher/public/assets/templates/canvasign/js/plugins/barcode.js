/**
 * Barcode Plugin Wrapper - Canvasign Theme
 * Bootstrap 5 compatible barcode generation with scanner support
 * 
 * Features:
 * - Multiple barcode formats (CODE128, EAN13, UPC, etc.)
 * - Webcam scanner (QuaggaJS)
 * - Auto-generate from source field(s) - supports array
 * - Format validation
 * - Configurable preview position (top/bottom/left/right)
 * - Help modal with usage guide
 * 
 * @version 1.0.0
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    window.CanvastackBarcode = {
        instances: {},
        scannerActive: false,
        
        /**
         * Initialize all barcode fields
         */
        init: function() {
            var self = this;
            
            $('[data-barcode-field]').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name') || $input.attr('id');
                
                if (!fieldName || self.instances[fieldName]) {
                    return; // Skip if already initialized
                }
                
                self.instances[fieldName] = self.createInstance($input);
            });
        },
        
        /**
         * Create barcode instance for a field
         */
        createInstance: function($input) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            // Get configuration from data attributes
            var config = {
                format: $input.data('barcode-format') || 'CODE128',
                validate: $input.data('barcode-validate') === true,
                autoGenerate: $input.data('barcode-auto-generate') === true,
                autoSource: $input.data('barcode-auto-source') || 'id',
                autoSeparator: $input.data('barcode-auto-separator') || '-',
                autoPrefix: $input.data('barcode-auto-prefix') || '',
                autoLength: $input.data('barcode-auto-length') || 13,
                entityType: $input.data('barcode-entity-type') || 'product',  // ⭐ NEW
                previewPosition: $input.data('barcode-preview-position') || 'top',
                previewWidth: $input.data('barcode-preview-width') || 2,
                previewHeight: $input.data('barcode-preview-height') || 60
            };
            
            // Parse autoSource if it's JSON array
            if (typeof config.autoSource === 'string' && config.autoSource.startsWith('[')) {
                try {
                    config.autoSource = JSON.parse(config.autoSource);
                } catch(e) {
                    console.warn('Failed to parse auto_generate_source array:', e);
                }
            }
            
            // Find or create preview container
            var $preview = $('[data-barcode-preview="' + fieldName + '"]');
            if ($preview.length === 0) {
                $preview = self.createPreview(fieldName, config.previewPosition);
                self.positionPreview($input, $preview, config.previewPosition);
            }
            
            var $canvas = $preview.find('.barcode-canvas');
            
            // Handle format selector
            var $formatSelector = $('[data-barcode-format-selector="' + fieldName + '"]');
            if ($formatSelector.length > 0) {
                $formatSelector.on('change', function() {
                    config.format = $(this).val();
                    $input.data('barcode-format', config.format);
                    self.generateBarcode($input, $canvas, config);
                });
            }
            
            // Handle scanner button
            var $scannerBtn = $('[data-barcode-scanner="' + fieldName + '"]');
            if ($scannerBtn.length > 0) {
                $scannerBtn.on('click', function() {
                    self.startScanner($input, config);
                });
            }
            
            // Handle auto-generate button
            var $generateBtn = $('[data-barcode-generate="' + fieldName + '"]');
            if ($generateBtn.length > 0) {
                $generateBtn.on('click', function() {
                    self.autoGenerate($input, config);
                });
            }
            
            // Handle help button
            var $helpBtn = $('[data-barcode-help="' + fieldName + '"]');
            if ($helpBtn.length > 0) {
                $helpBtn.on('click', function() {
                    self.showHelp(fieldName);
                });
            }
            
            // Generate barcode on input change
            $input.on('input', function() {
                self.generateBarcode($input, $canvas, config);
            });
            
            // Auto-generate on page load if enabled and field is empty
            if (config.autoGenerate && !$input.val()) {
                setTimeout(function() {
                    self.autoGenerate($input, config);
                }, 100);
            }
            
            // Trigger initial generation if value exists
            if ($input.val()) {
                self.generateBarcode($input, $canvas, config);
            }
            
            return {
                input: $input,
                preview: $preview,
                canvas: $canvas,
                config: config
            };
        },
        
        /**
         * Create preview container
         */
        createPreview: function(fieldName, position) {
            var $preview = $('<div class="barcode-preview-container" data-barcode-preview="' + fieldName + '" data-position="' + position + '" style="display:none;"></div>');
            var $canvas = $('<canvas class="barcode-canvas"></canvas>');
            $preview.append($canvas);
            return $preview;
        },
        
        /**
         * Position preview relative to input
         */
        positionPreview: function($input, $preview, position) {
            var $container = $input.closest('.form-group, .mb-3, .input-group').parent();
            
            switch(position) {
                case 'top':
                    $preview.insertBefore($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.addClass('mb-2');
                    break;
                case 'bottom':
                    $preview.insertAfter($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.addClass('mt-2');
                    break;
                case 'left':
                    $preview.insertBefore($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.addClass('me-2 d-inline-block');
                    break;
                case 'right':
                    $preview.insertAfter($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.addClass('ms-2 d-inline-block');
                    break;
            }
        },
        
        /**
         * Generate barcode preview
         */
        generateBarcode: function($input, $canvas, config) {
            var value = $input.val();
            var $preview = $canvas.closest('.barcode-preview-container');
            var $error = $('[data-barcode-error="' + ($input.attr('name') || $input.attr('id')) + '"]');
            
            if (!value || value.length === 0) {
                $preview.hide();
                $input.removeClass('is-invalid');
                $error.text('');
                return;
            }
            
            try {
                // Validate format if enabled
                if (config.validate) {
                    var isValid = this.validateFormat(value, config.format);
                    if (!isValid) {
                        throw new Error('Invalid ' + config.format + ' format');
                    }
                }
                
                // Generate barcode
                JsBarcode($canvas[0], value, {
                    format: config.format,
                    width: config.previewWidth,
                    height: config.previewHeight,
                    displayValue: true,
                    fontSize: 14,
                    margin: 10,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
                
                $preview.show();
                $input.removeClass('is-invalid');
                $error.text('');
                
            } catch(e) {
                console.warn('Barcode generation error:', e.message);
                $preview.hide();
                
                if (config.validate) {
                    $input.addClass('is-invalid');
                    $error.text(e.message || 'Invalid barcode format');
                }
            }
        },
        
        /**
         * Validate barcode format
         */
        validateFormat: function(value, format) {
            switch(format) {
                case 'EAN13':
                    return /^\d{13}$/.test(value);
                case 'EAN8':
                    return /^\d{8}$/.test(value);
                case 'UPC':
                case 'UPCA':
                    return /^\d{12}$/.test(value);
                case 'UPCE':
                    return /^\d{8}$/.test(value);
                case 'CODE39':
                    return /^[0-9A-Z\-\.\ \$\/\+\%]+$/.test(value);
                case 'CODE128':
                    return value.length > 0; // CODE128 accepts any ASCII
                case 'ITF14':
                    return /^\d{14}$/.test(value);
                case 'MSI':
                    return /^\d+$/.test(value);
                case 'pharmacode':
                    return /^\d{1,6}$/.test(value);
                default:
                    return true; // No validation for unknown formats
            }
        },
        
        /**
         * Auto-generate barcode from source field(s) OR from API
         */
        autoGenerate: function($input, config) {
            var self = this;
            
            // ⭐ NEW: If autoSource is 'api', call API endpoint
            if (config.autoSource === 'api') {
                self.generateFromApi($input, config);
                return;
            }
            
            // ⭐ OLD: Generate from source fields (legacy support)
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            var parts = [];
            
            // Collect values from all source fields
            for (var i = 0; i < sources.length; i++) {
                var sourceField = sources[i];
                
                // ⭐ FIX: Try both name and id selectors
                var $source = $('[name="' + sourceField + '"]');
                if ($source.length === 0) {
                    $source = $('#' + sourceField);
                }
                
                if ($source.length === 0 || !$source.val()) {
                    console.warn('Auto-generate source field not found or empty:', sourceField);
                    continue;
                }
                
                var value = $source.val().toString();
                
                // Clean value (remove spaces, special chars for barcode compatibility)
                value = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                
                parts.push(value);
            }
            
            if (parts.length === 0) {
                console.warn('No valid source values found for auto-generate');
                return;
            }
            
            // Join parts with separator (if multiple sources)
            var sourceValue = parts.join(config.autoSeparator);
            
            // Build barcode: prefix + sourceValue
            var barcode = config.autoPrefix + sourceValue;
            
            // Pad or trim to exact length if specified
            if (config.autoLength > 0) {
                if (barcode.length < config.autoLength) {
                    // Pad with zeros after prefix
                    var padding = config.autoLength - barcode.length;
                    barcode = config.autoPrefix + '0'.repeat(padding) + sourceValue;
                }
                barcode = barcode.substring(0, config.autoLength);
            }
            
            $input.val(barcode).trigger('input');
        },
        
        /**
         * Generate barcode from API (Sequential Counter)
         */
        generateFromApi: function($input, config) {
            var self = this;
            var entityType = config.entityType || 'product';
            
            // Show loading state
            var $generateBtn = $('[data-barcode-generate="' + ($input.attr('name') || $input.attr('id')) + '"]');
            var originalHtml = $generateBtn.html();
            $generateBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
            
            // Extract base URL up to /public/
            var pathParts = window.location.pathname.split('/');
            var publicIndex = pathParts.indexOf('public');
            var baseUrl;
            
            if (publicIndex > -1) {
                // Build URL: origin + path up to /public/
                baseUrl = window.location.origin + pathParts.slice(0, publicIndex + 1).join('/');
            } else {
                // Fallback to origin if 'public' not found
                baseUrl = window.location.origin;
            }
            
            var apiUrl = baseUrl + '/api/barcode/generate';
            
            // Call API
            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: {
                    entity_type: entityType
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        $input.val(response.barcode).trigger('input');
                        console.log('✅ Barcode generated from API:', response.barcode);
                        
                        // Show success toast (optional)
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Barcode generated: ' + response.barcode);
                        }
                    } else {
                        var errorMsg = response.message || 'Failed to generate barcode';
                        console.error('❌ Barcode generation failed:', errorMsg);
                        self.showError(errorMsg);
                    }
                },
                error: function(xhr) {
                    var message = 'Failed to generate barcode';
                    
                    if (xhr.status === 404) {
                        message = 'Barcode API endpoint not found. Please check your routes.';
                    } else if (xhr.status === 401) {
                        message = 'Unauthorized. Please login first.';
                    } else if (xhr.status === 500) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            // Check if sequence not found
                            if (xhr.responseJSON.message.includes('not found')) {
                                message = 'Barcode sequence for "' + entityType + '" not found. Please create it in System → Config → Barcode Sequences.';
                            } else {
                                message = xhr.responseJSON.message;
                            }
                        } else {
                            message = 'Server error. Please try again.';
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    
                    console.error('❌ Barcode API error:', {
                        status: xhr.status,
                        message: message,
                        response: xhr.responseJSON,
                        url: apiUrl
                    });
                    
                    self.showError(message);
                },
                complete: function() {
                    // Restore button state
                    $generateBtn.prop('disabled', false).html(originalHtml);
                }
            });
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            // Try toastr first
            if (typeof toastr !== 'undefined') {
                toastr.error(message, 'Barcode Generation Error');
                return;
            }
            
            // Fallback to alert
            alert('Barcode Generation Error:\n\n' + message);
        },
        
        /**
         * Start webcam scanner
         */
        startScanner: function($input, config) {
            var self = this;
            
            if (typeof Quagga === 'undefined') {
                alert('Scanner library not loaded. Please include QuaggaJS.');
                return;
            }
            
            if (self.scannerActive) {
                console.warn('Scanner already active');
                return;
            }
            
            // Create scanner modal
            var modalId = 'barcodeScannerModal_' + ($input.attr('name') || $input.attr('id'));
            var $modal = $('#' + modalId);
            
            if ($modal.length === 0) {
                $modal = self.createScannerModal(modalId);
                $('body').append($modal);
            }
            
            var $video = $modal.find('#barcodeScannerVideo');
            
            // Show modal
            var modal = new bootstrap.Modal($modal[0]);
            modal.show();
            
            // Initialize Quagga
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: $video[0],
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: [
                        "code_128_reader",  // CODE128
                        "ean_reader",       // EAN-13, EAN-8
                        "ean_8_reader",
                        "code_39_reader",   // CODE39
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",       // UPC-A, UPC-E
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                }
            }, function(err) {
                if (err) {
                    console.error('Quagga initialization error:', err);
                    alert('Failed to start camera: ' + err.message);
                    modal.hide();
                    return;
                }
                
                self.scannerActive = true;
                Quagga.start();
            });
            
            // Handle barcode detection
            Quagga.onDetected(function(result) {
                var code = result.codeResult.code;
                $input.val(code).trigger('input');
                
                // Stop scanner and close modal
                Quagga.stop();
                self.scannerActive = false;
                modal.hide();
            });
            
            // Handle modal close
            $modal.on('hidden.bs.modal', function() {
                if (self.scannerActive) {
                    Quagga.stop();
                    self.scannerActive = false;
                }
            });
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
                                <h5 class="modal-title">Scan Barcode</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="barcodeScannerVideo" style="width: 100%; height: 400px; background: #000;"></div>
                                <p class="mt-3 text-muted">Position the barcode within the camera view</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        },
        
        /**
         * Show help modal
         */
        showHelp: function(fieldName) {
            var modalId = 'barcodeHelpModal_' + fieldName;
            var $modal = $('#' + modalId);
            
            if ($modal.length > 0) {
                var modal = new bootstrap.Modal($modal[0]);
                modal.show();
            }
        }
    };
    
    // Auto-init on document ready
    $(document).ready(function() {
        if (typeof JsBarcode !== 'undefined') {
            CanvastackBarcode.init();
        } else {
            console.warn('JsBarcode library not loaded');
        }
    });
    
    // Re-init on AJAX content load (for dynamic forms)
    $(document).on('canvastack:form:loaded', function() {
        CanvastackBarcode.init();
    });
    
})(jQuery);

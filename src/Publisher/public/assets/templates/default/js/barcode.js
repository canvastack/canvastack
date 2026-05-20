/**
 * Barcode Plugin Wrapper - Default Theme
 * Same functionality as Canvasign, adapted for default theme
 * 
 * @version 1.0.0
 * @author Canvastack
 */
(function($) {
    'use strict';
    
    window.CanvastackBarcode = {
        instances: {},
        scannerActive: false,
        
        init: function() {
            var self = this;
            
            $('[data-barcode-field]').each(function() {
                var $input = $(this);
                var fieldName = $input.attr('name') || $input.attr('id');
                
                if (!fieldName || self.instances[fieldName]) {
                    return;
                }
                
                self.instances[fieldName] = self.createInstance($input);
            });
        },
        
        createInstance: function($input) {
            var self = this;
            var fieldName = $input.attr('name') || $input.attr('id');
            
            var config = {
                format: $input.data('barcode-format') || 'CODE128',
                validate: $input.data('barcode-validate') === true,
                autoGenerate: $input.data('barcode-auto-generate') === true,
                autoSource: $input.data('barcode-auto-source') || 'id',
                autoSeparator: $input.data('barcode-auto-separator') || '-',
                autoPrefix: $input.data('barcode-auto-prefix') || '',
                autoLength: $input.data('barcode-auto-length') || 13,
                previewPosition: $input.data('barcode-preview-position') || 'top',
                previewWidth: $input.data('barcode-preview-width') || 2,
                previewHeight: $input.data('barcode-preview-height') || 60
            };
            
            if (typeof config.autoSource === 'string' && config.autoSource.startsWith('[')) {
                try {
                    config.autoSource = JSON.parse(config.autoSource);
                } catch(e) {
                    console.warn('Failed to parse auto_generate_source array:', e);
                }
            }
            
            var $preview = $('[data-barcode-preview="' + fieldName + '"]');
            if ($preview.length === 0) {
                $preview = self.createPreview(fieldName, config.previewPosition);
                self.positionPreview($input, $preview, config.previewPosition);
            }
            
            var $canvas = $preview.find('.barcode-canvas');
            
            var $formatSelector = $('[data-barcode-format-selector="' + fieldName + '"]');
            if ($formatSelector.length > 0) {
                $formatSelector.on('change', function() {
                    config.format = $(this).val();
                    $input.data('barcode-format', config.format);
                    self.generateBarcode($input, $canvas, config);
                });
            }
            
            var $scannerBtn = $('[data-barcode-scanner="' + fieldName + '"]');
            if ($scannerBtn.length > 0) {
                $scannerBtn.on('click', function() {
                    self.startScanner($input, config);
                });
            }
            
            var $generateBtn = $('[data-barcode-generate="' + fieldName + '"]');
            if ($generateBtn.length > 0) {
                $generateBtn.on('click', function() {
                    self.autoGenerate($input, config);
                });
            }
            
            var $helpBtn = $('[data-barcode-help="' + fieldName + '"]');
            if ($helpBtn.length > 0) {
                $helpBtn.on('click', function() {
                    self.showHelp(fieldName);
                });
            }
            
            $input.on('input', function() {
                self.generateBarcode($input, $canvas, config);
            });
            
            if (config.autoGenerate && !$input.val()) {
                setTimeout(function() {
                    self.autoGenerate($input, config);
                }, 100);
            }
            
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
        
        createPreview: function(fieldName, position) {
            var $preview = $('<div class="barcode-preview-container" data-barcode-preview="' + fieldName + '" data-position="' + position + '" style="display:none;"></div>');
            var $canvas = $('<canvas class="barcode-canvas"></canvas>');
            $preview.append($canvas);
            return $preview;
        },
        
        positionPreview: function($input, $preview, position) {
            switch(position) {
                case 'top':
                    $preview.insertBefore($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    break;
                case 'bottom':
                    $preview.insertAfter($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    break;
                case 'left':
                    $preview.insertBefore($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.css('display', 'inline-block');
                    break;
                case 'right':
                    $preview.insertAfter($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
                    $preview.css('display', 'inline-block');
                    break;
            }
        },
        
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
                if (config.validate) {
                    var isValid = this.validateFormat(value, config.format);
                    if (!isValid) {
                        throw new Error('Invalid ' + config.format + ' format');
                    }
                }
                
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
                    return value.length > 0;
                case 'ITF14':
                    return /^\d{14}$/.test(value);
                case 'MSI':
                    return /^\d+$/.test(value);
                case 'pharmacode':
                    return /^\d{1,6}$/.test(value);
                default:
                    return true;
            }
        },
        
        autoGenerate: function($input, config) {
            var sources = Array.isArray(config.autoSource) ? config.autoSource : [config.autoSource];
            var parts = [];
            
            for (var i = 0; i < sources.length; i++) {
                var sourceField = sources[i];
                var $source = $('#' + sourceField);
                
                if ($source.length === 0 || !$source.val()) {
                    console.warn('Auto-generate source field not found or empty:', sourceField);
                    continue;
                }
                
                var value = $source.val().toString();
                value = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                parts.push(value);
            }
            
            if (parts.length === 0) {
                console.warn('No valid source values found for auto-generate');
                return;
            }
            
            var sourceValue = parts.join(config.autoSeparator);
            var barcode = config.autoPrefix + sourceValue;
            
            if (config.autoLength > 0) {
                if (barcode.length < config.autoLength) {
                    var padding = config.autoLength - barcode.length;
                    barcode = config.autoPrefix + '0'.repeat(padding) + sourceValue;
                }
                barcode = barcode.substring(0, config.autoLength);
            }
            
            $input.val(barcode).trigger('input');
        },
        
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
            
            var modalId = 'barcodeScannerModal_' + ($input.attr('name') || $input.attr('id'));
            var $modal = $('#' + modalId);
            
            if ($modal.length === 0) {
                $modal = self.createScannerModal(modalId);
                $('body').append($modal);
            }
            
            var $video = $modal.find('#barcodeScannerVideo');
            $modal.modal('show');
            
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
                    $modal.modal('hide');
                    return;
                }
                
                self.scannerActive = true;
                Quagga.start();
            });
            
            Quagga.onDetected(function(result) {
                var code = result.codeResult.code;
                $input.val(code).trigger('input');
                
                Quagga.stop();
                self.scannerActive = false;
                $modal.modal('hide');
            });
            
            $modal.on('hidden.bs.modal', function() {
                if (self.scannerActive) {
                    Quagga.stop();
                    self.scannerActive = false;
                }
            });
        },
        
        createScannerModal: function(modalId) {
            return $(`
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Scan Barcode</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
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
        
        showHelp: function(fieldName) {
            var modalId = 'barcodeHelpModal_' + fieldName;
            var $modal = $('#' + modalId);
            
            if ($modal.length > 0) {
                $modal.modal('show');
            }
        }
    };
    
    $(document).ready(function() {
        if (typeof JsBarcode !== 'undefined') {
            CanvastackBarcode.init();
        } else {
            console.warn('JsBarcode library not loaded');
        }
    });
    
})(jQuery);

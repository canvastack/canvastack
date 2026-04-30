/**
 * CanvaStack Cascading Filter
 * 
 * Handles cascading select dropdowns where selecting a value in one field
 * triggers an AJAX call to populate the next field with dependent data.
 * 
 * Features:
 * - Automatic AJAX loading with loading indicators
 * - Cascading field clearing when parent value changes
 * - Debouncing support to reduce server load
 * - Universal select plugin support (Chosen/Choices/Select2/Native)
 * - Prevents infinite loops from plugin events
 * 
 * @package CanvaStack
 * @subpackage Global Forms
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window) {
	'use strict';

	/**
	 * Initialize cascading filter for a select field
	 * 
	 * @param {Object} config Configuration object
	 * @param {string} config.node - Node identifier
	 * @param {string} config.identity - Field name
	 * @param {string} config.uniqueId - Unique element ID
	 * @param {string} config.firstNode - First node class
	 * @param {string} config.iNode - Identity node
	 * @param {string} config.nextTarget - Next field name
	 * @param {string} config.nextTargetUniqueId - Next field unique ID
	 * @param {string} config.nextNode - Next node class
	 * @param {string} config.ajaxUrl - AJAX endpoint URL
	 * @param {Object} config.ajaxDataConfig - AJAX data configuration object
	 * @param {string} config.prevScript - Previous script for building AJAX data
	 * @param {number} config.debounceDelay - Debounce delay in milliseconds
	 * @param {string} config.nestScript - Script to disable next fields when value is empty
	 * @param {string} config.clearingLogic - Script to clear dependent fields
	 */
	function canvastackCascadingFilter(config) {
		jQuery(function($) {
			// Debounce helper function
			function debounce(func, delay) {
				var timeout;
				return function() {
					var context = this;
					var args = arguments;
					clearTimeout(timeout);
					timeout = setTimeout(function() {
						func.apply(context, args);
					}, delay);
				};
			}

			// Find the source select element — works even after Choices.js wraps it
			function findSourceSelect() {
				// Try direct ID selector first (most reliable)
				var $direct = $('select#' + config.uniqueId);
				if ($direct.length) return $direct;

				// Fallback: search inside the modal node
				var $inNode = $('#' + config.node).find('select#' + config.uniqueId);
				if ($inNode.length) return $inNode;

				// Fallback: search by class only
				var $byClass = $('#' + config.node).find('select.' + config.firstNode);
				if ($byClass.length) return $byClass;

				return $();
			}

			// Attach event listener — handles both native select and Choices.js
			function attachChangeListener($elem, handler) {
				// Native change event (works for native select and Choices.js dispatches it)
				$elem.on('change.canvastack-cascade', handler);

				// Choices.js specific: also listen on the choices container
				var choicesContainer = $elem.closest('.choices');
				if (choicesContainer.length) {
					// Choices.js fires 'choice' event on the container
					choicesContainer.on('choice.canvastack-cascade', function(e) {
						// Trigger on the underlying select after Choices updates it
						setTimeout(function() {
							handler.call($elem[0]);
						}, 50);
					});
				}

				// Also listen for Choices.js internal events via the element
				var nativeEl = $elem[0];
				if (nativeEl) {
					nativeEl.addEventListener('change', function() {
						// Already handled by jQuery .on('change') above
					});
				}
			}

			// Main init — retry up to 10 times with 300ms intervals
			var attempts = 0;
			var maxAttempts = 10;

			function tryInit() {
				attempts++;

				// Initialize loader for next target field
				if (config.nextTarget && config.nextTargetUniqueId) {
					if (typeof loader === 'function') loader(config.nextTargetUniqueId);
				}

				var $elem = findSourceSelect();

				if ($elem.length === 0) {
					if (attempts < maxAttempts) {
						setTimeout(tryInit, 300);
					} else {
						console.warn('[CascadeFilter] Element not found after ' + maxAttempts + ' attempts:', config.uniqueId, 'node:', config.node);
					}
					return;
				}

				// Remove any previous listener to avoid duplicates on re-init
				$elem.off('change.canvastack-cascade');
				$elem.closest('.choices').off('choice.canvastack-cascade');

				// Processing flag to prevent infinite loops
				var _processing = false;

				var changeHandler = function() {
					if (_processing) return;
					_processing = true;

					var _val = $('select#' + config.uniqueId).val();

					if (_val && _val !== '0' && _val !== '') {
						// Execute clearing logic
						if (config.clearingLogic) {
							try { eval(config.clearingLogic); } catch(e) { console.error('clearingLogic error:', e); }
						}

						// Build AJAX data
						var ajaxData = {};
						var dataConfig = config.ajaxDataConfig;
						ajaxData[dataConfig.identity] = _val;

						var _prevS = '';
						if (config.prevScript) {
							try { _prevS = eval(config.prevScript); } catch(e) { _prevS = ''; }
						}

						ajaxData['_fita']    = dataConfig.token + '::' + dataConfig.table + '::' + dataConfig.next_target + '::' + dataConfig.prev + '#' + _prevS + '::' + dataConfig.nest;
						ajaxData['_token']   = dataConfig.token;
						ajaxData['_n']       = dataConfig.nest;
						ajaxData['_forKeys'] = dataConfig.forKeys;

						if (dataConfig.connection) ajaxData['grabCanvaStackC'] = dataConfig.connection;
						if (dataConfig.filters && Object.keys(dataConfig.filters).length > 0) ajaxData['_canvastackF'] = dataConfig.filters;

						$.ajax({
							type: 'POST',
							url: config.ajaxUrl,
							data: ajaxData,
							dataType: 'json',
							beforeSend: function() {
								if (config.nextTargetUniqueId) $('#CanvaStackInpLdr' + config.nextTargetUniqueId).show();
							},
							success: function(data) {
								if (data && config.nextTarget && config.nextTargetUniqueId) {
									var $nextSelect = $('select#' + config.nextTargetUniqueId);
									$nextSelect.removeAttr('disabled').empty();

									var label = config.nextTarget.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase(); });
									$nextSelect.append('<option value="">Select ' + label + '</option>');

									$.each(data, function(key, value) {
										$nextSelect.append('<option value="' + value[config.nextTarget] + '">' + value[config.nextTarget] + '</option>');
									});

									if (typeof updateSelectChosen === 'function') {
										updateSelectChosen('#' + config.nextTargetUniqueId, false, false);
									}
								}
								setTimeout(function() { _processing = false; }, 300);
							},
							error: function(xhr, status, error) {
								console.error('[CascadeFilter] AJAX error:', status, error, 'target:', config.nextTarget);
								if (config.nextTargetUniqueId) {
									var $nextSelect = $('select#' + config.nextTargetUniqueId);
									$nextSelect.empty().append('<option value="">Error loading options</option>').prop('disabled', true);
									if (typeof updateSelectChosen === 'function') updateSelectChosen('#' + config.nextTargetUniqueId, false, false);
								}
								_processing = false;
							},
							complete: function() {
								if (config.nextTargetUniqueId) $('#CanvaStackInpLdr' + config.nextTargetUniqueId).hide();
							}
						});

					} else {
						// Empty value — disable downstream fields
						if (config.nestScript) {
							try { eval(config.nestScript); } catch(e) { console.error('nestScript error:', e); }
						}
						setTimeout(function() { _processing = false; }, 300);
					}
				};

				var finalHandler = config.debounceDelay > 0 ? debounce(changeHandler, config.debounceDelay) : changeHandler;

				attachChangeListener($elem, finalHandler);

				console.log('[CascadeFilter] Initialized:', config.uniqueId, '→', config.nextTarget, '(attempt ' + attempts + ')');
			}

			// Start with a short delay to let Choices.js initialize
			setTimeout(tryInit, 600);
		});
	}

	// Export to global scope
	window.canvastackCascadingFilter = canvastackCascadingFilter;

})(window);

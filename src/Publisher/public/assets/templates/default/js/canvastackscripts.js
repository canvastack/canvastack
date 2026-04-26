/* [ START ] MAPPING PAGE FUNCTION */
function setAjaxSelectionBox(object, id, target_id, url, method = 'POST', onError = 'Error') {
	var qtarget     = null;
	var idsplit     = id.split('__node__');
	var inputSource = $('input#qmod-' + idsplit[0] + '.' + idsplit[2]);
	var infoClass   = inputSource.attr('class');
	
	var roleNode    = 'rolePages';
	var prefixNode  = {'module':'module','field_name':'field_name','field_value':'field_value'};
	if (roleNode) {
		prefixNode  = {
			'module'     : roleNode + '[module]',
			'field_name' : roleNode + '[field_name]',
			'field_value': roleNode + '[field_value]'
		};
	}
	
	$.ajax({
		type    : method,
		url     : url,
		data    : object.serialize(),
		success : function(d) {
			sourcebox = $('select#' + id);
			qtarget   = sourcebox.val();
			
			if (~$('select#' + target_id).attr('class').indexOf('field_name')) {
				$('input#qmod-' + idsplit[0] + '.' + infoClass).attr({'name': prefixNode.module + '[' + infoClass.replaceAll('-', '.') + ']'});
				$('select#' + target_id).attr({'name': prefixNode.field_name + '[' + infoClass.replaceAll('-', '.') + '][' + idsplit[0] + '][]'});
			}
			
			if (~$('select#' + target_id).attr('class').indexOf('field_value')) {
				var targetClass = $('select#' + target_id).attr('class').split('__');
				if (typeof infoClass === 'undefined') infoClass = targetClass[1].replaceAll('-', '.');
				
				$('select#' + target_id).attr({'name': prefixNode.field_value + '[' + infoClass.replaceAll('-', '.') + '][' + idsplit[0] + '][' + qtarget + '][]'});
			}
			
			loader(target_id, 'show');
			updateSelectChosen('select#' + target_id, true, '');
			
			$.each(JSON.parse(d), function(index, item) {
				if (item != '') {
					var optValue = null;
					
					if (typeof myVar == 'string') {
						if (~item.indexOf('_')) {
							optValue = ucwords(item.replaceAll('_', ' '));
						} else if (~item.indexOf('.')) {
							optValue = ucwords(item.replaceAll('.', ' '));
						} else {
							optValue = ucwords(item);
						}
					} else {
						optValue = item;
					}
					
					$('select#' + target_id).append('<option value=\"' + item + '\">' + optValue + '</option>');
				}
			});
			
			updateSelectChosen('select#' + target_id, false, '');
		},
		error: function() {
			alert(onError);
		},
		complete: function() {
			loader(target_id, 'fadeOut');
		}
	});
}

function mappingPageTableFieldname(id, target_id, url, target_opt = null, nodebtn = null, nodemodel = null, method = 'POST', onError = 'Error') {
	var node_add    = 'role-add-' + target_id;	
	var node_btn    = $('#' + nodebtn);
	var firstRemove = $('span#remove-row' + target_id);
	var nodestring  = '__node__';
	
	node_btn.hide();
	if ($('#' + id).is(':checked')) {
		node_btn.fadeIn(1800);
	}
	
	var classInfo = id + nodestring + nodemodel;
	$('#' + id + '.' + classInfo).change(function(e) {
		if ($(this).is(':checked')) {
			node_btn.fadeIn(1800);
			infoID = classInfo;
			setAjaxSelectionBox($(this), infoID, target_id, url, method, onError);
		} else {
			/*
			var idsplit = id.split(nodestring);
			$('input#qmod-' + idsplit[0]).removeAttr('name');
			*/
			var idsplit = $(this).attr('class').split(nodestring);
			$('input#qmod-' + idsplit[0] + '.' + idsplit[2]).removeAttr('name');
			
			loader(target_id, 'show');
			loader(target_id, 'fadeOut');
			updateSelectChosen('select#' + target_id, true, '');
			
			if (null != target_opt) {
				loader(target_opt, 'show');
				loader(target_opt, 'fadeOut');
				updateSelectChosen('select#' + target_opt, true, '');
			}
			
			firstRemove.fadeOut(1000);
			
			node_btn.fadeOut(1800);
			$('#reset' + nodebtn).fadeOut(500);
			$('.' + node_add).chosen('destroy').fadeOut(500, function() { $(this).remove(); });
		}
	});
}

function rowButtonRemovalMapRoles(id, target_id, url = null) {
	$('span#remove-row' + id).click(function(e) {
		$('tr#row-box-' + id).fadeOut(300, function() { $(this).remove(); });
	});
}

function mappingPageFieldnameValues(id, target_id, url = null, method = 'POST', onError = 'Error') {
	var firstRemove = $('span#remove-row' + id);
	
	$('#' + id).change(function(e) {
		if ($(this).val() !== '') {
			setAjaxSelectionBox($(this), id, target_id, url, method, onError);
			
			firstRemove.fadeIn(1000);
		} else {
			loader(target_id, 'show');
			loader(target_id, 'fadeOut');
			updateSelectChosen('select#' + target_id, true, '');
			firstRemove.fadeOut(1000);
		}
	});
}

function firstResetRowButton(id, target_id, second_target, url, method = 'POST', onError = 'Error', withAction = true) {
	var firstRemove = $('span#remove-row' + target_id);
	
	if (true === withAction) {
		firstRemove.click(function(e) {
			setAjaxSelectionBox($('#' + id), id, target_id, url.replace('field_name', 'table_name'), method, onError);
			mappingPageFieldnameValues(target_id, second_target, url, method, onError);
			updateSelectChosen('select#' + second_target, true, '');
			$(this).fadeOut(1000);
		});
		
	} else {
		setAjaxSelectionBox($('#' + id), id, target_id, url.replace('field_name', 'table_name'), method, onError);
		mappingPageFieldnameValues(target_id, second_target, url, method, onError);
		updateSelectChosen('select#' + second_target, true, '');
		firstRemove.fadeOut();
	}
}

function mappingPageButtonManipulation(node_btn, id, target_id, second_target, url, method = 'POST', onError = 'Error') {
	var node_add      = 'role-add-' + target_id;
	var baserowbox    = $('tr#row-box-' + target_id);
	var tablesource   = baserowbox.parent('tbody').parent('table');
	
	var firstRemove   = $('span#remove-row' + target_id);
	var fieldnamebox  = $('select#' + target_id);
	var fieldvaluebox = $('select#' + second_target);
		
	$('#reset' + node_btn).hide();	
	$('#plusn' + node_btn).click(function(e) {
		$('span.inputloader').removeAttr('style').hide();
		
		if (firstRemove.attr('style').trim()) {
			firstRemove.attr({'style': ''}).fadeIn();
		}
		
		var random_target_id     = target_id     + canvastack_random();
		var random_second_target = second_target + canvastack_random();
		var node_row             = 'remove-row'  + random_target_id;
		var nextcloneid          = 'row-box-'    + random_target_id;
		var clonerowbox          = baserowbox.clone().attr({'id': nextcloneid, 'class': baserowbox.attr('class') + ' ' + node_add});
		
		clonerowbox.find('td').each(function(x, n) {
			if (~$(this).attr('class').indexOf("field-name-box")) {
				$(this).children('div.chosen-container').remove();				
				$(this).children('select').attr({'id': random_target_id}).prop('selectedIndex', -1).chosen();
			}
			
			if (~$(this).attr('class').indexOf("field-value-box")) {
				$(this).children('div.chosen-container').remove();
				$(this).children('select').attr({'id': random_second_target, 'name': ''}).find('option').remove().end().chosen();
				$(this).children('span#remove-row' + target_id)
					.removeAttr('id').attr({'id': node_row})
					.find('.fa')
					.attr({'class': 'fa fa-minus-circle danger'});
			}
		});
		clonerowbox.appendTo(tablesource);
		mappingPageFieldnameValues(random_target_id, random_second_target, url, method, onError);
		
		if (clonerowbox.length >= 1) {
			firstRemove.fadeIn();
			$('#reset' + node_btn).fadeIn();
		} else {
			$('#reset' + node_btn).fadeOut();
		}
		
		$('span#' + node_row).click(function(x) {
			$('tr#row-box-' + random_target_id).fadeOut(300, function() { $(this).remove(); });
		});
	});
	
	tablesource.each(function(x, n) {
		var tr = $(this).children('tbody').children('tr').length;
		if (tr > 1) {
			$('#reset' + node_btn).fadeIn();
		}
	});
	
	$('#reset' + node_btn).click(function(e) {
		$('.'  + node_add).chosen('destroy').fadeOut(500, function() { $(this).remove(); });
		$('#reset' + node_btn).fadeOut(500);
		firstResetRowButton(id, target_id, second_target, url, method, onError, false);
	});
	
	firstResetRowButton(id, target_id, second_target, url, method, onError);
}
/* [ CLOSED ] MAPPING PAGE FUNCTION */


/* [ START ] CASCADING FILTER FUNCTION */

/**
 * Initialize cascading filter for a select field
 * 
 * This function handles cascading select dropdowns where selecting a value in one field
 * triggers an AJAX call to populate the next field with dependent data.
 * 
 * Features:
 * - Automatic AJAX loading with loading indicators
 * - Cascading field clearing when parent value changes
 * - Debouncing support to reduce server load
 * - Chosen plugin integration
 * - Prevents infinite loops from Chosen events
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

		// Delay execution to ensure Chosen plugin is initialized
		setTimeout(function() {
			// Initialize loader for next target field (if exists)
			if (config.nextTarget && config.nextTargetUniqueId) {
				loader(config.nextTargetUniqueId);
			}
			
			// Find and attach event handler to the select element
			$('#' + config.node).children('div.form-group').each(function() {
				var $elem = $(this).find('select#' + config.uniqueId + '.' + config.firstNode);
				if ($elem.length === 0) return;
				
				// Processing flag to prevent infinite loops from Chosen events
				var _processing = false;
				
				// Define change handler
				var changeHandler = function() {
					// Block if already processing to prevent infinite loops
					if (_processing) { return; }
					_processing = true;
					
					var _val = $(this).val();
					var _prevVal = $(this).data('prevValue') || '';
					$(this).data('prevValue', _val);
					
					if (_val != '0' && _val != null && _val != '') {
						// Value is valid - execute clearing logic and AJAX call
						
						// Execute field clearing logic (clears fields that come after current in cascade)
						if (config.clearingLogic) {
							try {
								eval(config.clearingLogic);
							} catch (e) {
								console.error('Error executing clearing logic:', e);
							}
						}
						
						// Build AJAX data object from config
						var ajaxData = {};
						var dataConfig = config.ajaxDataConfig;
						
						// Add field value
						ajaxData[dataConfig.identity] = _val;
						
						// Build _prevS value by executing prevScript
						var _prevS = '';
						if (config.prevScript) {
							try {
								_prevS = eval(config.prevScript);
							} catch (e) {
								console.error('Error executing prevScript:', e);
								_prevS = '';
							}
						}
						
						// Build _fita value
						var fitaValue = dataConfig.token + '::' + dataConfig.table + '::' + 
							dataConfig.next_target + '::' + dataConfig.prev + '#' + _prevS + '::' + dataConfig.nest;
						ajaxData['_fita'] = fitaValue;
						ajaxData['_token'] = dataConfig.token;
						ajaxData['_n'] = dataConfig.nest;
						ajaxData['_forKeys'] = dataConfig.forKeys;
						
						// Add connection if present
						if (dataConfig.connection) {
							ajaxData['grabCanvaStackC'] = dataConfig.connection;
						}
						
						// Add filters if present
						if (dataConfig.filters && Object.keys(dataConfig.filters).length > 0) {
							ajaxData['_canvastackF'] = dataConfig.filters;
						}
						
						// Execute AJAX call to populate next field
						$.ajax({
							type: 'POST',
							url: config.ajaxUrl,
							data: ajaxData,
							dataType: 'json',
							beforeSend: function() {
								// Show loading indicator
								$('#CanvaStackInpLdr' + config.nextTargetUniqueId).show();
							},
							success: function(data) {
								if (data) {
									if (config.nextTarget && config.nextTargetUniqueId) {
										var $nextSelect = $('select#' + config.nextTargetUniqueId + '.' + config.nextNode);
										$nextSelect.removeAttr('disabled');
										$nextSelect.empty();
										
										// Format next target name for display
										var nextTargetLabel = config.nextTarget.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase(); });
										$nextSelect.append('<option value="">Select ' + nextTargetLabel + '</option>');
										
										$.each(data, function(key, value) {
											$nextSelect.append('<option value="'+ value[config.nextTarget] +'">' + value[config.nextTarget] + '</option>');
										});
										
										// IMPORTANT: Only trigger chosen:updated ONCE after all options are added
										$nextSelect.trigger('chosen:updated');
										
										// Reset processing flag after Chosen update completes
										setTimeout(function() {
											_processing = false;
										}, 500);
									}
								}
							},
							error: function(xhr, status, error) {
								console.error('Search filter load failed:', {status: status, error: error, target: config.nextTarget, xhr: xhr});
								var errorMsg = 'Failed to load ' + config.nextTarget.replace(/_/g, ' ') + ' options. ';
								if (xhr.status === 404) { errorMsg += 'Endpoint not found.'; }
								else if (xhr.status === 500) { errorMsg += 'Server error.'; }
								else if (xhr.status === 0) { errorMsg += 'Network error.'; }
								else { errorMsg += 'Please try again.'; }
								
								var $nextSelect = $('select#' + config.nextTargetUniqueId + '.' + config.nextNode);
								$nextSelect.empty()
									.append('<option value="">Error: ' + errorMsg + '</option>')
									.prop('disabled', true);
								$nextSelect.trigger('chosen:updated');
								
								_processing = false;
							},
							complete: function() {
								// Hide loading indicator
								$('#CanvaStackInpLdr' + config.nextTargetUniqueId).hide();
							}
						});
						
					} else {
						// Value is empty - disable all next fields
						// IMPORTANT: Current field remains enabled with its options intact
						
						// Execute nest script to disable next fields
						if (config.nestScript) {
							try {
								eval(config.nestScript);
							} catch (e) {
								console.error('Error executing nest script:', e);
							}
						}
						
						// Reset processing flag after disabling fields
						setTimeout(function() {
							_processing = false;
						}, 500);
					}
				};
				
				// Attach event handler (with or without debounce)
				if (config.debounceDelay > 0) {
					var debouncedHandler = debounce(changeHandler, config.debounceDelay);
					$elem.on('change', debouncedHandler);
				} else {
					$elem.on('change', changeHandler);
					// Reset flag after handler completes (for non-debounced version)
					setTimeout(function() {
						_processing = false;
					}, 200);
				}
			});
		}, 500); // Wait for Chosen initialization
	});
}

/* [ CLOSED ] CASCADING FILTER FUNCTION */


/**
 * Privilege Table - Cell Click Handler
 * 
 * Makes the entire checkbox cell clickable for better UX.
 * User can click anywhere in the cell to toggle the checkbox.
 * 
 * @author Canvastack
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    /**
     * Initialize privilege table cell click handlers
     */
    function initPrivilegeTableCellClick() {
        // Find all privilege checkbox cells
        const checkboxCells = document.querySelectorAll('.privilege-checkbox-cell');
        
        if (checkboxCells.length === 0) {
            return; // No privilege table on this page
        }
        
        checkboxCells.forEach(function(cell) {
            // Find checkbox inside this cell
            const checkbox = cell.querySelector('input[type="checkbox"]');
            
            if (!checkbox) {
                return; // No checkbox found
            }
            
            // Add click handler to cell
            cell.addEventListener('click', function(e) {
                // Prevent double-toggle if checkbox itself was clicked
                if (e.target === checkbox) {
                    return;
                }
                
                // Toggle checkbox
                checkbox.checked = !checkbox.checked;
                
                // Update aria-checked attribute for accessibility
                checkbox.setAttribute('aria-checked', checkbox.checked);
                
                // Trigger change event for any listeners
                const event = new Event('change', { bubbles: true });
                checkbox.dispatchEvent(event);
                
                // Visual feedback
                cell.classList.add('clicking');
                setTimeout(function() {
                    cell.classList.remove('clicking');
                }, 150);
            });
            
            // Add keyboard support (Space/Enter to toggle)
            cell.addEventListener('keydown', function(e) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Toggle checkbox
                    checkbox.checked = !checkbox.checked;
                    
                    // Update aria-checked attribute
                    checkbox.setAttribute('aria-checked', checkbox.checked);
                    
                    // Trigger change event
                    const event = new Event('change', { bubbles: true });
                    checkbox.dispatchEvent(event);
                    
                    // Visual feedback
                    cell.classList.add('clicking');
                    setTimeout(function() {
                        cell.classList.remove('clicking');
                    }, 150);
                }
            });
            
            // Make cell focusable for keyboard navigation
            if (!cell.hasAttribute('tabindex')) {
                cell.setAttribute('tabindex', '0');
            }
            
            // Add role for accessibility
            cell.setAttribute('role', 'checkbox');
            cell.setAttribute('aria-checked', checkbox.checked);
            
            // Update aria-checked when checkbox changes
            checkbox.addEventListener('change', function() {
                cell.setAttribute('aria-checked', checkbox.checked);
            });
        });
        
        console.log('Privilege table cell click handlers initialized:', checkboxCells.length, 'cells');
    }
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPrivilegeTableCellClick);
    } else {
        // DOM already loaded
        initPrivilegeTableCellClick();
    }
    
    /**
     * Re-initialize if table is dynamically loaded
     * (e.g., via AJAX or modal)
     */
    window.reinitPrivilegeTableCellClick = initPrivilegeTableCellClick;
    
})();

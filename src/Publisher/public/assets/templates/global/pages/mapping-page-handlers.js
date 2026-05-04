/**
 * CanvaStack Mapping Page Handlers
 * 
 * Handles all functionality specific to mapping pages (role mapping, field mapping, etc.)
 * 
 * Features:
 * - Dynamic field mapping with AJAX
 * - Row addition and removal
 * - Field name and value cascading
 * - Reset functionality
 * - Universal select plugin support
 * 
 * @package CanvaStack
 * @subpackage Global Pages
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window) {
	'use strict';
	
	console.log('🔧 Mapping Page Handlers loaded');

	/**
	 * Set AJAX selection box for mapping page
	 * 
	 * @param {jQuery} object - Form object
	 * @param {string} id - Source element ID
	 * @param {string} target_id - Target element ID
	 * @param {string} url - AJAX URL
	 * @param {string} method - HTTP method (default: POST)
	 * @param {string} onError - Error message
	 */
	function setAjaxSelectionBox(object, id, target_id, url, method, onError) {
		method = method || 'POST';
		onError = onError || 'Error';
		
		console.log('🌐 setAjaxSelectionBox called:', {
			id: id,
			target_id: target_id,
			url: url,
			method: method
		});
		
		var qtarget     = null;
		var idsplit     = id.split('__node__');
		var inputSource = $('input#qmod-' + idsplit[0] + '.' + idsplit[2]);
		var infoClass   = inputSource.attr('class');
		
		// FALLBACK: If input source not found (e.g., in cloned rows), try to find from original checkbox
		if (inputSource.length === 0 || typeof infoClass === 'undefined') {
			console.warn('⚠️ Input source not found, trying to find from checkbox...');
			
			// Try to find checkbox with same table name
			var tableName = idsplit[0];  // e.g., "base_module", "base_group"
			var $checkbox = $('input[type="checkbox"][id^="' + tableName + '__node__"]').first();
			
			if ($checkbox.length > 0) {
				var checkboxClass = $checkbox.attr('class');
				var checkboxId = $checkbox.attr('id');
				var checkboxSplit = checkboxId.split('__node__');
				
				// Try to find hidden input from checkbox
				inputSource = $('input#qmod-' + checkboxSplit[0] + '.' + checkboxSplit[2]);
				infoClass = inputSource.attr('class');
				
				console.log('🔄 Found checkbox:', {
					checkbox: checkboxId,
					class: checkboxClass,
					hiddenInput: inputSource.length,
					infoClass: infoClass
				});
				
				// CRITICAL: If still undefined, try to extract from checkbox ID
				if (typeof infoClass === 'undefined' && checkboxSplit.length >= 3) {
					// Extract from checkbox ID: base_preference__node__qoyMkKRp__node__system-config-group
					// infoClass should be: system-config-group
					infoClass = checkboxSplit[2];
					console.log('🔧 Extracted infoClass from checkbox ID:', infoClass);
				}
			}
			
			// LAST RESORT: Extract from id parameter if still undefined
			if (typeof infoClass === 'undefined' && idsplit.length >= 3) {
				infoClass = idsplit[2];
				console.log('🔧 Extracted infoClass from id parameter:', infoClass);
			}
		}
		
		console.log('🔍 Input source:', {
			selector: 'input#qmod-' + idsplit[0] + '.' + idsplit[2],
			exists: inputSource.length,
			class: infoClass
		});
		
		var roleNode    = 'rolePages';
		var prefixNode  = {'module':'module','field_name':'field_name','field_value':'field_value'};
		if (roleNode) {
			prefixNode  = {
				'module'     : roleNode + '[module]',
				'field_name' : roleNode + '[field_name]',
				'field_value': roleNode + '[field_value]'
			};
		}
		
		var serializedData = object.serialize();
		
		console.log('🔍 ORIGINAL serialized data from form:', serializedData);
		
		// CRITICAL: If serializedData is empty (e.g., from cloned row), build data manually
		// Server expects parameter KEY to MATCH usein parameter EXACTLY
		if (!serializedData || serializedData === '') {
			console.warn('⚠️ Serialized data is empty, building data manually...');
			
			// Extract table name from id
			var tableName = idsplit[0];  // e.g., "base_module"
			
			// CRITICAL UNDERSTANDING:
			// - usein=field_name → Server returns list of FIELD NAMES from a TABLE
			// - usein=field_value → Server returns list of FIELD VALUES from a FIELD
			// - Data parameter tells server: "Get field names from THIS TABLE"
			
			if (url.indexOf('usein=field_name') > -1) {
				// Request: Get list of field names
				// Server expects: rolePages[field_name][] = table_name
				// KEY must match usein ("field_name"), VALUE is what we query FROM (table name)
				serializedData = 'rolePages[field_name][]=' + encodeURIComponent(tableName);
				
				console.log('🔍 Field name request - KEY=field_name, VALUE=table_name:', {
					tableName: tableName,
					data: serializedData
				});
			} else if (url.indexOf('usein=field_value') > -1) {
				// Request: Get list of field values
				// Server expects: rolePages[field_value][] = field_name
				// KEY must match usein ("field_value"), VALUE is field name
				
				// Get value from Field Name select
				var sourceId = id;
				var doubleNodeIndex = id.indexOf('__node__');
				if (doubleNodeIndex > -1) {
					var secondNodeIndex = id.indexOf('__node__', doubleNodeIndex + 8);
					if (secondNodeIndex > -1) {
						sourceId = id.substring(0, secondNodeIndex);
					}
				}
				
				var $sourceSelect = $('select#' + sourceId);
				var paramValue;
				if ($sourceSelect.length > 0 && $sourceSelect.val()) {
					paramValue = $sourceSelect.val();
					console.log('✅ Found field name value from select:', paramValue);
				} else {
					console.error('❌ Cannot find field name value or value is empty, aborting AJAX');
					loader(target_id, 'fadeOut');
					return;  // Abort - cannot proceed without field name
				}
				
				// CRITICAL: Validate paramValue is not empty
				if (!paramValue || paramValue === '' || paramValue === null) {
					console.error('❌ Field name value is empty, aborting AJAX');
					loader(target_id, 'fadeOut');
					return;
				}
				
				// Build data with field_value parameter (KEY matches usein!)
				serializedData = 'rolePages[field_value][]=' + encodeURIComponent(paramValue);
				
				console.log('🔧 Built manual data (field_value):', {
					tableName: tableName,
					paramValue: paramValue,
					data: serializedData
				});
			} else {
				// For table_name request (default - checkbox click)
				serializedData = 'rolePages[table_name][]=' + encodeURIComponent(tableName);
				
				console.log('🔧 Built manual data (table_name):', {
					tableName: tableName,
					data: serializedData
				});
			}
		} else {
			// CRITICAL: Even if serializedData exists, filter it to only rolePages parameters
			// This prevents sending unnecessary form data that causes server errors
			console.log('🔍 Serialized data exists, filtering to rolePages only...');
			
			var filteredData = serializedData.split('&').filter(function(param) {
				// Only keep rolePages parameters
				return param.indexOf('rolePages') === 0;
			}).join('&');
			
			if (filteredData) {
				serializedData = filteredData;
				console.log('🔧 Filtered data (rolePages only):', serializedData);
			}
		}
		
		console.log('📤 AJAX Request:', {
			url: url,
			method: method,
			data: serializedData
		});
		
		$.ajax({
			type    : method,
			url     : url,
			data    : serializedData,
			success : function(d) {
				console.log('✅ AJAX Success! Raw response:', d);
				console.log('📊 Response type:', typeof d);
				console.log('📊 Response length:', d ? d.length : 0);
				
				// CRITICAL: Find source box - use simple ID without double __node__
				// The id parameter might be: "base_group__node__hpMjbxTb__node__system-config-group"
				// But the actual select ID is: "base_group__node__hpMjbxTb"
				var sourceId = id;
				
				// If id contains double __node__, extract the first part
				var doubleNodeIndex = id.indexOf('__node__');
				if (doubleNodeIndex > -1) {
					var secondNodeIndex = id.indexOf('__node__', doubleNodeIndex + 8);
					if (secondNodeIndex > -1) {
						// Double __node__ found, use only first part
						sourceId = id.substring(0, secondNodeIndex);
						console.log('🔧 Corrected source ID:', { original: id, corrected: sourceId });
					}
				}
				
				var sourcebox = $('select#' + sourceId);
				qtarget   = sourcebox.val();
				
				console.log('🎯 Source box:', {
					selector: 'select#' + sourceId,
					exists: sourcebox.length,
					value: qtarget
				});
				
				// Safe check for target select existence and class attribute
				var $targetSelect = $('select#' + target_id);
				if ($targetSelect.length === 0) {
					console.error('❌ Target select not found:', target_id);
					return;
				}
				
				var targetClass = $targetSelect.attr('class');
				if (!targetClass) {
					console.warn('⚠️ Target select has no class attribute:', target_id);
					targetClass = '';
				}
				
				if (~targetClass.indexOf('field_name')) {
					// CRITICAL: Extract infoClass if undefined
					if (typeof infoClass === 'undefined' || !infoClass) {
						// Try to extract from target class
						// Class format: role__system-config-module__base_modulebase_module__node__XXXfield_name
						var targetClassParts = targetClass.split('__');
						if (targetClassParts.length > 1 && targetClassParts[1]) {
							infoClass = targetClassParts[1];
							console.log('🔧 Extracted infoClass from target class:', infoClass);
						} else {
							console.warn('⚠️ Cannot extract infoClass from target class:', targetClass);
						}
					}
					
					// CRITICAL: Safe check for infoClass before using replaceAll
					if (typeof infoClass !== 'undefined' && infoClass) {
						$('input#qmod-' + idsplit[0] + '.' + infoClass).attr({'name': prefixNode.module + '[' + infoClass.replaceAll('-', '.') + ']'});
						$targetSelect.attr({'name': prefixNode.field_name + '[' + infoClass.replaceAll('-', '.') + '][' + idsplit[0] + '][]'});
						console.log('✅ Set field_name attribute with infoClass:', {
							infoClass: infoClass,
							name: prefixNode.field_name + '[' + infoClass.replaceAll('-', '.') + '][' + idsplit[0] + '][]'
						});
					} else {
						console.error('❌ infoClass still undefined, cannot set proper name attribute!');
						// Last resort fallback
						$targetSelect.attr({'name': prefixNode.field_name + '[' + idsplit[0] + '][]'});
					}
				}
				
				if (~targetClass.indexOf('field_value')) {
					var targetClassParts = targetClass.split('__');
					if (typeof infoClass === 'undefined' || !infoClass) {
						// Try to extract from target class
						if (targetClassParts.length > 1 && targetClassParts[1]) {
							infoClass = targetClassParts[1].replaceAll('-', '.');
						} else {
							console.warn('⚠️ Cannot determine infoClass for field_value');
							infoClass = '';
						}
					}
					
					// CRITICAL: Safe check before using replaceAll
					if (infoClass) {
						$targetSelect.attr({'name': prefixNode.field_value + '[' + infoClass.replaceAll('-', '.') + '][' + idsplit[0] + '][' + qtarget + '][]'});
					} else {
						console.warn('⚠️ infoClass is empty, using fallback name for field_value');
						$targetSelect.attr({'name': prefixNode.field_value + '[' + idsplit[0] + '][' + qtarget + '][]'});
					}
				}
				
				loader(target_id, 'show');
				updateSelectChosen('select#' + target_id, true, '');
				
				console.log('🔄 Parsing JSON and appending options...');
				var parsedData;
				try {
					parsedData = JSON.parse(d);
					console.log('✅ JSON parsed successfully:', parsedData);
					console.log('📊 Parsed data type:', Array.isArray(parsedData) ? 'Array' : 'Object');
					console.log('📊 Parsed data length/keys:', Array.isArray(parsedData) ? parsedData.length : Object.keys(parsedData).length);
				} catch(e) {
					console.error('❌ JSON parse error:', e);
					console.error('❌ Raw data:', d);
					return;
				}
				
				// Convert object to array if needed
				var dataArray;
				if (Array.isArray(parsedData)) {
					// Already an array
					dataArray = parsedData;
					console.log('✅ Data is already an array');
				} else if (typeof parsedData === 'object' && parsedData !== null) {
					// Convert object keys to array
					dataArray = Object.keys(parsedData);
					console.log('🔄 Converted object keys to array:', dataArray);
				} else {
					console.error('❌ Unexpected data type:', typeof parsedData);
					return;
				}
				
				// CRITICAL: Add empty option first to prevent auto-selection
				if (!$targetSelect.attr('multiple')) {
					// For single select, prepend empty option
					$targetSelect.prepend('<option value=""></option>');
					console.log('✅ Added empty option to prevent auto-selection');
				}
				
				var optionsAdded = 0;
				$.each(dataArray, function(index, item) {
					if (item != '' && item != null) {
						var optValue = null;
						
						if (typeof item == 'string') {
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
						
						$targetSelect.append('<option value="' + item + '">' + optValue + '</option>');
						optionsAdded++;
						console.log('➕ Added option:', { value: item, label: optValue });
					}
				});
				
				console.log('✅ Total options added:', optionsAdded);
				console.log('📊 Select element now has', $targetSelect.find('option').length, 'options');
				console.log('📊 Target select ID:', target_id);
				console.log('📊 Target select element:', $targetSelect[0]);
				
				// CRITICAL: For Canvasign theme, we need to reinitialize Choices.js properly
				// First, destroy any existing Choices.js instance
				if ($targetSelect[0] && $targetSelect[0].choicesInstance) {
					console.log('🔄 Destroying existing Choices.js instance');
					try {
						$targetSelect[0].choicesInstance.destroy();
					} catch(e) {
						console.warn('⚠️ Failed to destroy Choices.js:', e);
					}
				}
				
				// Reinitialize plugin with proper config based on multiple attribute
				if ($targetSelect.attr('multiple')) {
					// For multiple select, destroy and reinitialize with proper config
					try {
						console.log('🔄 Reinitializing multiple select:', target_id);
						$targetSelect.chosen('destroy').chosen({
							allow_single_deselect: true,
							width: '100%',
							placeholder_text_multiple: 'Select options...',
							removeItemButton: true  // CRITICAL: Enable remove button for Choices.js
						});
						console.log('✅ Reinitialized plugin for multiple select:', target_id);
					} catch(e) {
						console.error('❌ Failed to reinitialize multiple select:', e);
					}
				} else {
					// For single select, use standard update
					console.log('🔄 Updating single select:', target_id);
					
					// CRITICAL: For single select, also destroy and reinitialize
					try {
						$targetSelect.chosen('destroy').chosen({
							allow_single_deselect: true,
							width: '100%',
							placeholder_text_single: 'Select an option'
						});
						
						// CRITICAL: Explicitly set value to empty AFTER reinitialize to prevent auto-selection
						setTimeout(function() {
							$targetSelect.val('').trigger('change');
							console.log('✅ Explicitly cleared value after reinitialize');
						}, 50);
						
						console.log('✅ Reinitialized plugin for single select:', target_id);
					} catch(e) {
						console.error('❌ Failed to reinitialize single select:', e);
						// Fallback to updateSelectChosen
						updateSelectChosen('select#' + target_id, false, '');
					}
				}
				
				// CRITICAL: Re-initialize Choices.js for Canvasign theme
				if (typeof window.CanvasignPlugins !== 'undefined' && window.CanvasignPlugins.reinitChoices) {
					setTimeout(function() {
						console.log('🔄 Calling CanvasignPlugins.reinitChoices() after', optionsAdded, 'options added');
						window.CanvasignPlugins.reinitChoices();
					}, 300);  // Increased delay to ensure options are rendered
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ AJAX Error:', {
					status: status,
					error: error,
					xhr: xhr
				});
				alert(onError);
			},
			complete: function() {
				loader(target_id, 'fadeOut');
			}
		});
	}

	/**
	 * Initialize mapping page table field name handler
	 */
	function mappingPageTableFieldname(id, target_id, url, target_opt, nodebtn, nodemodel, method, onError) {
		console.log('🎯 mappingPageTableFieldname REAL function called:', { id, target_id, url, nodemodel });
		
		target_opt = target_opt || null;
		nodebtn = nodebtn || null;
		nodemodel = nodemodel || null;
		method = method || 'POST';
		onError = onError || 'Error';
		
		var node_add    = 'role-add-' + target_id;	
		var node_btn    = $('#' + nodebtn);
		var firstRemove = $('span#remove-row' + target_id);
		var nodestring  = '__node__';
		
		node_btn.hide();
		if ($('#' + id).is(':checked')) {
			node_btn.fadeIn(1800);
		}
		
		var classInfo = id + nodestring + nodemodel;
		
		// CRITICAL: Store configuration for this checkbox in a global registry
		// This allows us to look up the config when ANY checkbox is clicked
		if (typeof window.mappingPageCheckboxRegistry === 'undefined') {
			window.mappingPageCheckboxRegistry = {};
		}
		
		window.mappingPageCheckboxRegistry[id] = {
			target_id: target_id,
			url: url,
			target_opt: target_opt,
			nodebtn: nodebtn,
			nodemodel: nodemodel,
			method: method,
			onError: onError,
			classInfo: classInfo,
			node_add: node_add,
			node_btn: node_btn,
			firstRemove: firstRemove
		};
		
		console.log('📝 Registered checkbox config:', id, window.mappingPageCheckboxRegistry[id]);
		
		// CRITICAL: Bind event handler directly to this specific checkbox
		// Use .off() first to prevent duplicate bindings
		$('#' + id).off('change.mappingPage').on('change.mappingPage', function(e) {
			var checkboxId = $(this).attr('id');
			var config = window.mappingPageCheckboxRegistry[checkboxId];
			
			if (!config) {
				console.error('❌ No config found for checkbox:', checkboxId);
				return;
			}
			
			var isChecked = $(this).is(':checked');
			console.log('🔘 Checkbox changed!', { 
				id: checkboxId,
				checked: isChecked,
				config: config
			});
			
			if (isChecked) {
				console.log('✅ Checkbox CHECKED - showing add button and loading Field Name options');
				config.node_btn.fadeIn(1800);
				// DON'T show recycle button here - only show after adding rows
				setAjaxSelectionBox($(this), config.classInfo, config.target_id, config.url, config.method, config.onError);
			} else {
				console.log('❌ Checkbox UNCHECKED - clearing all data and hiding buttons');
				
				var actualClass = $(this).attr('class');
				var idsplit = actualClass.split(nodestring);
				$('input#qmod-' + idsplit[0] + '.' + idsplit[2]).removeAttr('name');
				
				// Clear Field Name select
				console.log('🧹 Clearing Field Name select:', config.target_id);
				loader(config.target_id, 'show');
				loader(config.target_id, 'fadeOut');
				updateSelectChosen('select#' + config.target_id, true, '');
				
				// Clear Field Value select
				if (null != config.target_opt) {
					console.log('🧹 Clearing Field Value select:', config.target_opt);
					
					var $fieldValueSelect = $('select#' + config.target_opt);
					
					// Properly clear Field Value
					$fieldValueSelect.val(null).html('');
					
					// Destroy + reinitialize plugin
					try {
						if ($fieldValueSelect[0] && $fieldValueSelect[0].choicesInstance) {
							$fieldValueSelect[0].choicesInstance.destroy();
						}
						$fieldValueSelect.chosen('destroy');
					} catch(e) {
						// Ignore
					}
					
					try {
						$fieldValueSelect.chosen({
							allow_single_deselect: true,
							width: '100%',
							placeholder_text_multiple: 'Select options...'
						});
					} catch(e) {
						console.error('❌ Failed to reinitialize plugin:', e);
					}
					
					loader(config.target_opt, 'show');
					loader(config.target_opt, 'fadeOut');
					updateSelectChosen('select#' + config.target_opt, true, '');
				}
				
				// Hide buttons
				config.firstRemove.fadeOut(1000);
				config.node_btn.fadeOut(1800);
				$('#reset' + config.nodebtn).fadeOut(500);
				
				// Remove added rows
				console.log('🧹 Removing added rows with class:', config.node_add);
				var addedRowsCount = $('.' + config.node_add).length;
				console.log('📊 Found', addedRowsCount, 'added rows to remove');
				
				$('.' + config.node_add).chosen('destroy').fadeOut(500, function() { 
					$(this).remove(); 
					console.log('✅ Added row removed');
				});
				
				console.log('✅ Checkbox UNCHECKED completed');
			}
		});
		
		console.log('✅ Event handler bound to checkbox:', id);
	}

	/**
	 * Row button removal handler for mapping roles
	 */
	function rowButtonRemovalMapRoles(id, target_id, url) {
		url = url || null;
		
		$('span#remove-row' + id).click(function(e) {
			$('tr#row-box-' + id).fadeOut(300, function() { $(this).remove(); });
		});
	}

	/**
	 * Mapping page field name values handler
	 * 
	 * Handles Field Name select change event to load Field Value options
	 * 
	 * CRITICAL FIX: Use .off() to prevent duplicate event binding
	 */
	function mappingPageFieldnameValues(id, target_id, url, method, onError) {
		url = url || null;
		method = method || 'POST';
		onError = onError || 'Error';
		
		var firstRemove = $('span#remove-row' + id);
		var $fieldNameSelect = $('#' + id);
		
		console.log('🎯 mappingPageFieldnameValues bound to:', {
			fieldNameId: id,
			fieldValueId: target_id,
			url: url,
			elementExists: $fieldNameSelect.length
		});
		
		// CRITICAL FIX: Unbind previous change handlers to prevent duplicate AJAX calls
		$fieldNameSelect.off('change.fieldNameHandler');
		
		// Bind with namespace for easy unbinding
		$fieldNameSelect.on('change.fieldNameHandler', function(e) {
			var selectedValue = $(this).val();
			console.log('🔄 Field name changed:', { 
				id: id, 
				value: selectedValue,
				target: target_id
			});
			
			if (selectedValue !== '' && selectedValue !== null) {
				console.log('✅ Value is valid, calling AJAX to load Field Value options');
				setAjaxSelectionBox($(this), id, target_id, url, method, onError);
				
				// DON'T auto-show recycle button here
				// It should only show when there are added rows (handled in mappingPageButtonManipulation)
			} else {
				console.log('⚠️ Value is empty, skipping AJAX and clearing Field Value');
				loader(target_id, 'show');
				loader(target_id, 'fadeOut');
				updateSelectChosen('select#' + target_id, true, '');
				firstRemove.fadeOut(1000);
			}
		});
		
		console.log('✅ Event handler bound with namespace: change.fieldNameHandler');
	}

	/**
	 * First reset row button handler
	 * 
	 * CRITICAL FIX: Properly clear Field Value select to prevent:
	 * 1. Value remaining after recycle
	 * 2. Server error when changing Field Name after recycle
	 */
	function firstResetRowButton(id, target_id, second_target, url, method, onError, withAction) {
		method = method || 'POST';
		onError = onError || 'Error';
		withAction = (typeof withAction !== 'undefined') ? withAction : true;
		
		var firstRemove = $('span#remove-row' + target_id);
		
		console.log('🔄 firstResetRowButton initialized:', {
			id: id,
			target_id: target_id,
			second_target: second_target,
			withAction: withAction
		});
		
		if (true === withAction) {
			firstRemove.click(function(e) {
				console.log('🔵 Recycle button (first row) clicked!', {
					target_id: target_id,
					second_target: second_target
				});
				
				// CRITICAL FIX: Properly clear Field Value select
				var $fieldValueSelect = $('select#' + second_target);
				
				console.log('🧹 Clearing Field Value select:', {
					id: second_target,
					exists: $fieldValueSelect.length,
					currentValue: $fieldValueSelect.val(),
					optionsCount: $fieldValueSelect.find('option').length
				});
				
				// 1. Clear value
				$fieldValueSelect.val(null);
				
				// 2. Clear all options
				$fieldValueSelect.html('');
				
				// 3. Destroy plugin instance
				try {
					if ($fieldValueSelect[0] && $fieldValueSelect[0].choicesInstance) {
						$fieldValueSelect[0].choicesInstance.destroy();
						console.log('✅ Destroyed Choices.js instance');
					}
					$fieldValueSelect.chosen('destroy');
					console.log('✅ Destroyed Chosen instance');
				} catch(e) {
					console.warn('⚠️ Plugin destroy failed (may not exist):', e.message);
				}
				
				// 4. Reinitialize plugin
				try {
					$fieldValueSelect.chosen({
						allow_single_deselect: true,
						width: '100%',
						placeholder_text_multiple: 'Select options...'
					});
					console.log('✅ Reinitialized Chosen plugin');
				} catch(e) {
					console.error('❌ Failed to reinitialize plugin:', e);
				}
				
				// 5. Reload Field Name options
				setAjaxSelectionBox($('#' + id), id, target_id, url.replace('field_name', 'table_name'), method, onError);
				
				// 6. Re-bind Field Name change handler
				mappingPageFieldnameValues(target_id, second_target, url, method, onError);
				
				// 7. Clear Field Name select (updateSelectChosen is OK for this)
				updateSelectChosen('select#' + second_target, true, '');
				
				// 8. Hide recycle button
				$(this).fadeOut(1000);
				
				console.log('✅ Recycle button (first row) completed');
			});
			
		} else {
			console.log('🔄 firstResetRowButton called without action (programmatic reset)');
			
			// Same clearing logic for programmatic reset
			var $fieldValueSelect = $('select#' + second_target);
			
			console.log('🧹 Clearing Field Value select (programmatic):', {
				id: second_target,
				exists: $fieldValueSelect.length
			});
			
			// Clear value and options
			$fieldValueSelect.val(null).html('');
			
			// Destroy + reinitialize plugin
			try {
				if ($fieldValueSelect[0] && $fieldValueSelect[0].choicesInstance) {
					$fieldValueSelect[0].choicesInstance.destroy();
				}
				$fieldValueSelect.chosen('destroy');
			} catch(e) {
				// Ignore
			}
			
			try {
				$fieldValueSelect.chosen({
					allow_single_deselect: true,
					width: '100%',
					placeholder_text_multiple: 'Select options...'
				});
			} catch(e) {
				console.error('❌ Failed to reinitialize plugin:', e);
			}
			
			setAjaxSelectionBox($('#' + id), id, target_id, url.replace('field_name', 'table_name'), method, onError);
			mappingPageFieldnameValues(target_id, second_target, url, method, onError);
			updateSelectChosen('select#' + second_target, true, '');
			firstRemove.fadeOut();
			
			console.log('✅ Programmatic reset completed');
		}
	}

	/**
	 * Mapping page button manipulation handler
	 */
	function mappingPageButtonManipulation(node_btn, id, target_id, second_target, url, method, onError) {
		method = method || 'POST';
		onError = onError || 'Error';
		
		var node_add      = 'role-add-' + target_id;
		var baserowbox    = $('tr#row-box-' + target_id);
		var tablesource   = baserowbox.parent('tbody').parent('table');
		
		var firstRemove   = $('span#remove-row' + target_id);
		var fieldnamebox  = $('select#' + target_id);
		var fieldvaluebox = $('select#' + second_target);
			
		$('#reset' + node_btn).hide();
		
		// CRITICAL: Do NOT explicitly hide firstRemove here
		// Let it rely on natural state so attr('style') check works correctly
		
		$('#plusn' + node_btn).click(function(e) {
			console.log('🔵 Add button clicked');
			$('span.inputloader').removeAttr('style').hide();
			
			// CRITICAL: Only show recycle button if it was previously hidden
			if (firstRemove.attr('style') && firstRemove.attr('style').trim()) {
				firstRemove.attr({'style': ''}).fadeIn();
				console.log('✅ Showing recycle button (first row)');
			}
			
			var random_target_id     = target_id     + canvastack_random();
			var random_second_target = second_target + canvastack_random();
			var node_row             = 'remove-row'  + random_target_id;
			var nextcloneid          = 'row-box-'    + random_target_id;
			
			// CRITICAL FIX: Clone the ORIGINAL select elements BEFORE Choices.js wraps them
			// Find the original select elements (not wrapped by Choices.js)
			var $originalFieldName = baserowbox.find('select[class*="field_name"]').first();
			var $originalFieldValue = baserowbox.find('select[class*="field_value"]').first();
			
			// Clone the base row
			var clonerowbox = baserowbox.clone().attr({'id': nextcloneid, 'class': baserowbox.attr('class') + ' ' + node_add});
			
			clonerowbox.find('td').each(function(x, n) {
				if (~$(this).attr('class').indexOf("field-name-box")) {
					console.log('🔧 Processing field-name-box');
					
					// Remove ALL Choices.js wrappers and containers
					$(this).find('div.choices').remove();
					$(this).find('div.chosen-container').remove();
					
					// Find or recreate the select element
					var $fieldNameSelect = $(this).find('select').first();
					
					if ($fieldNameSelect.length === 0) {
						console.warn('⚠️ Select not found after clone, recreating from original');
						// Recreate from original
						$fieldNameSelect = $originalFieldName.clone();
						$(this).append($fieldNameSelect);
					}
					
					// Update ID and clear state
					$fieldNameSelect
						.attr('id', random_target_id)
						.val('')  // Clear value
						.prop('selectedIndex', -1)  // Reset to no selection
						.removeAttr('data-chosen data-choices data-choice');
					
					// Remove all 'selected' attributes from ALL options
					$fieldNameSelect.find('option').removeAttr('selected');
					
					// CRITICAL: Set first option as placeholder (empty value)
					var $firstOption = $fieldNameSelect.find('option').first();
					if ($firstOption.length > 0 && $firstOption.val() !== '') {
						// If first option has value, prepend empty option
						$fieldNameSelect.prepend('<option value=""></option>');
					}
					
					// Reinitialize Choices.js
					try {
						$fieldNameSelect.chosen({
							allow_single_deselect: true,
							width: '100%',
							placeholder_text_single: 'Select an option'
						});
					} catch(e) {
						console.error('❌ Failed to reinitialize field name:', e);
					}
					
					console.log('✅ Field name reset:', random_target_id, 'Value:', $fieldNameSelect.val(), 'Exists:', $fieldNameSelect.length);
				}
				
				if (~$(this).attr('class').indexOf("field-value-box")) {
					console.log('🔧 Processing field-value-box');
					
					// Remove ALL Choices.js wrappers and containers
					$(this).find('div.choices').remove();
					$(this).find('div.chosen-container').remove();
					
					// Find or recreate the select element
					var $fieldValueSelect = $(this).find('select').first();
					
					if ($fieldValueSelect.length === 0) {
						console.warn('⚠️ Select not found after clone, recreating from original');
						// Recreate from original
						$fieldValueSelect = $originalFieldValue.clone();
						$(this).append($fieldValueSelect);
					}
					
					// Update ID, clear state, and ensure multiple attribute
					$fieldValueSelect
						.attr('id', random_second_target)
						.attr('name', '')
						.val(null)
						.prop('selectedIndex', -1)
						.removeAttr('data-chosen data-choices data-choice');
					
					// Ensure multiple attribute is set
					if (!$fieldValueSelect.attr('multiple')) {
						$fieldValueSelect.attr('multiple', 'multiple');
					}
					
					// Clear all options
					$fieldValueSelect.html('');
					
					// Reinitialize Choices.js
					try {
						$fieldValueSelect.chosen({
							allow_single_deselect: true,
							width: '100%',
							placeholder_text_multiple: 'Select options...'
						});
					} catch(e) {
						console.error('❌ Failed to reinitialize field value:', e);
					}
					
					console.log('✅ Field value reset:', random_second_target, 'Options:', $fieldValueSelect.find('option').length, 'Exists:', $fieldValueSelect.length);
					
					// Update delete button icon and show it
					$(this).children('span#remove-row' + target_id)
						.removeAttr('id').attr({'id': node_row})
						.css('display', 'flex')  // Show delete button
						.find('.fa, .bi')  // Support both FA and BI icons
						.each(function() {
							// Convert recycle icon to minus/dash icon
							if ($(this).hasClass('fa')) {
								// FontAwesome: fa-recycle → fa-minus-circle
								$(this).attr({'class': 'fa fa-minus-circle danger'});
							} else if ($(this).hasClass('bi')) {
								// Bootstrap Icons: bi-recycle → bi-dash-circle
								$(this).attr({'class': 'bi bi-dash-circle danger'});
							}
						});
				}
			});
			
			// Append cloned row to table
			clonerowbox.appendTo(tablesource);
			
			// Bind change handler for new field name select
			mappingPageFieldnameValues(random_target_id, random_second_target, url, method, onError);
			
			// CRITICAL: Show recycle button based on cloned rows
			if (clonerowbox.length >= 1) {
				console.log('✅ Showing recycle buttons - row added');
				firstRemove.fadeIn();
				$('#reset' + node_btn).fadeIn();
			} else {
				console.log('⚠️ Hiding recycle buttons - no rows');
				$('#reset' + node_btn).fadeOut();
			}
			
			// Bind delete button click handler
			$('span#' + node_row).click(function(x) {
				$('tr#row-box-' + random_target_id).fadeOut(300, function() { 
					$(this).remove(); 
				});
			});
		});
		
		// Check if there are already added rows on page load
		tablesource.each(function(x, n) {
			var tr = $(this).children('tbody').children('tr').length;
			if (tr > 1) {
				$('#reset' + node_btn).fadeIn();
				// Note: Do NOT show firstRemove here - let it be controlled by add/delete logic
			}
		});
		
		// Reset button handler
		$('#reset' + node_btn).click(function(e) {
			console.log('🔄 Reset button (Action column) clicked!', {
				node_btn: node_btn,
				id: id,
				target_id: target_id,
				second_target: second_target
			});
			
			console.log('🧹 Removing added rows with class:', node_add);
			var addedRowsCount = $('.' + node_add).length;
			console.log('📊 Found', addedRowsCount, 'added rows to remove');
			
			$('.'  + node_add).chosen('destroy').fadeOut(500, function() { 
				$(this).remove(); 
				console.log('✅ Added row removed');
			});
			
			$('#reset' + node_btn).fadeOut(500);
			// Note: Do NOT explicitly hide firstRemove - let firstResetRowButton handle it
			
			console.log('🔄 Calling firstResetRowButton (programmatic)...');
			firstResetRowButton(id, target_id, second_target, url, method, onError, false);
			
			console.log('✅ Reset button (Action column) completed');
		});
		
		firstResetRowButton(id, target_id, second_target, url, method, onError);
	}

	// Export to global scope with underscore prefix (real implementations)
	window._setAjaxSelectionBox = setAjaxSelectionBox;
	window._mappingPageTableFieldname = mappingPageTableFieldname;
	window._rowButtonRemovalMapRoles = rowButtonRemovalMapRoles;
	window._mappingPageFieldnameValues = mappingPageFieldnameValues;
	window._firstResetRowButton = firstResetRowButton;
	window._mappingPageButtonManipulation = mappingPageButtonManipulation;
	
	// Also export without underscore if wrapper not loaded (backward compatibility)
	if (typeof window.mappingPageTableFieldname === 'undefined') {
		window.setAjaxSelectionBox = setAjaxSelectionBox;
		window.mappingPageTableFieldname = mappingPageTableFieldname;
		window.rowButtonRemovalMapRoles = rowButtonRemovalMapRoles;
		window.mappingPageFieldnameValues = mappingPageFieldnameValues;
		window.firstResetRowButton = firstResetRowButton;
		window.mappingPageButtonManipulation = mappingPageButtonManipulation;
	}

})(window);

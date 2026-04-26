// These placeholder values are used by date/time pickers and should be filtered out
const PLACEHOLDER_DATE = '____-__-__';
const PLACEHOLDER_DATETIME = '____-__-__ __:__:__';
const PLACEHOLDER_DATETIME_ENCODED = '____-__-__%20__%3A__%3A__';

// Updated: 2026-04-26 18:35 - Fixed filter form method to use POST instead of GET

function ajaxSelectionProcess(object, id, target_id, url, data = [], method = 'POST', onError = 'Error') {
	var dataInfo = JSON.parse(data);
	
	if (typeof dataInfo.labels   != 'undefined') var lURL = 'l=' + dataInfo.labels;
	if (typeof dataInfo.values   != 'undefined') var vURL = 'v=' + dataInfo.values;
	if (typeof dataInfo.selected != 'undefined') var sURL = 's=' + dataInfo.selected;
	if (typeof dataInfo.query    != 'undefined') var qURL = canvastack_random() + '=' + dataInfo.query;
	
	if (typeof dataInfo.labels != 'undefined' && typeof dataInfo.values != 'undefined' && typeof dataInfo.selected != 'undefined' && typeof dataInfo.query != 'undefined') {
		var urls = url + '&' + lURL + '&' + vURL + '&' + sURL + '&' + qURL;
	} else {
		if (typeof dataInfo.selected != 'undefined') {
			var urls = url + '&' + sURL;
		} else {
			var urls = url;
		}
	}
	
	var selected = null;
	var pinned   = '';
	
	$.ajax({
		type    : method,
		url     : urls,
		data    : object.serialize(),
		success : function(d) {
			var result = JSON.parse(d);
			selected   = result.selected;
			
			loader(target_id, 'show');
			updateSelectChosen('select#' + target_id, true, '');
			$.each(result.data, function(value, label) {				
				if (selected === value) {
					pinned = ' selected';
				} else {
					pinned = '';
				}
				
				if (value != '') {
					var optionLabel = null;
					
					if (~label.indexOf('_')) {
						optionLabel = ucwords(label.replaceAll('_', ' '));
					} else if (~label.indexOf('.')) {
						optionLabel = ucwords(label.replaceAll('.', ' '));
					} else {
						optionLabel = ucwords(label);
					}
					
					$('select#' + target_id).append('<option value=\"' + value + '\"' + pinned + '>' + optionLabel + '</option>');
				}
			});
			updateSelectChosen('select#' + target_id, false, false);
		},
		error: function(xhr, status, error) {
			var err = eval("(" + xhr.responseText + ")");
			console.log(xhr);
			alert(xhr);
		},
		complete: function() {
			loader(target_id, 'fadeOut');
		}
	});
}

function ajaxSelectionBox(id, target_id, url, data = [], method = 'POST', onError = 'Error') {
	var object = $('select#' + id);
	if (object.val() !== '') ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
	object.change(function(e) {
		ajaxSelectionProcess(object, id, target_id, url, data, method, onError);
	});
}

function exportFromModal(modalID, exportID, filterID, token, url, link, filter = []) {
	$('#exportFilterButton' + modalID).on('click', function(event) {
		$(this).css({
			'position'  : 'relative',
			'width'     : '138px',
			'text-align': 'left'
		}).append('<span id="loader_'+ modalID +'" class="inputloader loader" style="right:8px;width:20px;height:20px;top:7px;background-size:20px"></span>');
		
		var inputFilters        = $('#' + modalID + ' > .form-group.row > .input-group.col-sm-9 > select.' + exportID);
		var inputData           = [];
		inputData['exportData'] = true;
		inputData['_token']     = token;
		inputFilters.each(function(x, y) {
			inputData[y.name]   = y.value;
		});
		// Only add lurExp if link is not null and not empty string
		if (link != null && link !== '')   inputData['lurExp'] = link;
		if (null != filter) inputData['ftrExp'] = filter;
		
		// Debug logging
		if (window.APP_DEBUG) {
			console.log('Export request:', {
				url: url,
				link: link,
				hasLurExp: inputData.hasOwnProperty('lurExp'),
				inputData: inputData
			});
		}
		
		$.ajax ({
			type    : 'POST',
			data    : canvastack_array_to_object(inputData),
			dataType: 'JSON',
			url     : url,
			success : function(n) {
				if (window.APP_DEBUG) console.log('Export response:', n);
				
				// Check if response is valid and has export path
				if (n && n.canvastackExportStreamPath) {
					window.location.href = n.canvastackExportStreamPath;
				} else if (n && n.error) {
					// Handle error response
					alert('Export failed: ' + (n.message || 'Unknown error'));
					if (window.APP_DEBUG) console.error('Export error:', n);
				} else {
					// Handle unexpected response
					alert('Export failed: Invalid response from server');
					if (window.APP_DEBUG) console.error('Invalid export response:', n);
				}
			},
			error: function(xhr, status, error) {
				alert('Export failed: ' + error);
				if (window.APP_DEBUG) console.error('Export AJAX error:', xhr, status, error);
			},
			complete : function() {
				$('#exportFilterButton' + modalID).removeAttr('style');
				$('#loader_'+ modalID).remove();
				$('#' + filterID).modal('hide');
			}
		});
	});
}

function canvastackDataTableFilters(id, url, obTable) {
	$('#canvastack-' + id + '-search-box').appendTo('.CanvaStack_' + id + '_canvastack-dt-filter-box');
	$('.canvastack-dt-search-box').removeClass('hide');
	$('#' + id + '_CanvaStackProcessing').hide();
	
	$('#' + id + '_CanvaStackFILTERForm').on('submit', function(event) {
		event.preventDefault();
		$('#' + id + '_CanvaStackProcessing').show();
		
		// Use serializeArray() instead of manual parsing
		// This properly handles values with '=' character
		var input = {};
		$.each($(this).serializeArray(), function(i, field) {
			input[field.name] = field.value;
		});
		
		var filterURI = [];
		var filterData = {};
		
		$.each(input, function(index, value) {
			// Use constants instead of magic strings
			if (
				index != 'renderDataTables' &&
				index != 'difta' &&
				index != 'filters' &&
				index != '_token' &&
				null  != value &&
				''    != value &&
				value != PLACEHOLDER_DATETIME &&
				value != PLACEHOLDER_DATETIME_ENCODED &&
				value != PLACEHOLDER_DATE
			) {
				if ('string' === typeof(value)) {
					filterURI.push(index + '=' + encodeURIComponent(value));
					filterData[index] = value;  // Store for POST data
				} else if ('object' === typeof(value)) {
					$.each(value, function(idx, _val) {
						filterURI.push(index + '[' + idx + ']' + '=' + encodeURIComponent(_val));
						if (!filterData[index]) filterData[index] = {};
						filterData[index][idx] = _val;  // Store for POST data
					});
				}
			}
		});
		
		// Check if using POST method - prioritize form method attribute
		var formMethod = $(this).attr('method');
		var isPostMethod = false;
		
		if (window.APP_DEBUG) {
			console.log('Filter form debug:', {
				formId: $(this).attr('id'),
				formMethod: formMethod,
				formAction: $(this).attr('action')
			});
		}
		
		// First check form's method attribute
		if (formMethod && formMethod.toUpperCase() === 'POST') {
			isPostMethod = true;
			if (window.APP_DEBUG) console.log('Using POST method from form attribute');
		} else {
			// Fallback to DataTables AJAX settings
			var ajaxSettings = obTable.settings()[0].ajax;
			if (typeof ajaxSettings === 'object' && ajaxSettings.type === 'POST') {
				isPostMethod = true;
				if (window.APP_DEBUG) console.log('Using POST method from DataTables settings');
			} else {
				if (window.APP_DEBUG) console.log('Using GET method (fallback)');
			}
		}
		
		if (isPostMethod) {
			// POST method: Send filters via POST body
			// Store original data function
			var ajaxSettings = obTable.settings()[0].ajax;
			var originalDataFn = ajaxSettings.data;
			
			// Create new data function that includes filters
			ajaxSettings.data = function(d) {
				// Call original data function if exists
				if (typeof originalDataFn === 'function') {
					d = originalDataFn(d) || d;
				}
				// Merge filter data into POST body
				return $.extend({}, d, filterData, {filters: true});
			};
			
			// Reload without changing URL (preserves POST method)
			obTable.ajax.reload(function() {
				$('#' + id + '_CanvaStackProcessing').hide();
				$('#' + id + '_CanvaStackFILTER').modal('hide');
			});
		} else {
			// GET method: Send filters via URL (original behavior)
			obTable.ajax.url(url + '&' + filterURI.join('&') + '&filters=true').load(function() {
				$('#' + id + '_CanvaStackProcessing').hide();
				$('#' + id + '_CanvaStackFILTER').modal('hide');
			});
		}
	});
}

function softDeleteUnnecessaryDatatableComponents(data) {
	for (var i=0, len=data.columns.length; i<len; i++) { 
		if (!data.columns[i].search.value) delete data.columns[i].search;
		if ( data.columns[i].searchable === true) delete data.columns[i].searchable;
		if ( data.columns[i].orderable === true) delete data.columns[i].orderable;
		if ( data.columns[i].data === data.columns[i].name) delete data.columns[i].name; 
	
	} delete data.search.regex;
}

function deleteUnnecessaryDatatableComponents(data, strict = false) {
	if ('soft' === strict) softDeleteUnnecessaryDatatableComponents(data);
	
	for (var i=0, len=data.columns.length; i<len; i++) {
		delete data.columns[i].search;
		delete data.columns[i].searchable;
		delete data.columns[i].orderable;
		delete data.columns[i].name;
		if (true === strict) {
			delete data.columns[i].data;
		}
	}
	delete data.search.regex;
	delete data.search.value;
	if (true === strict) {
		delete data.order[0].column;
		delete data.order[0].dir;
	}
}

function drawDatatableOnClickColumnOrder(id, urli, tableID) {
	$('#' + id + '>thead>tr>th').each(function (n, d) {
		var classAttribute = this.attributes.class.nodeValue;
		var nodeAttribute  = null;
		if (!~classAttribute.indexOf('sorting_disabled') && !~classAttribute.indexOf('hidden-column')) {
			d.addEventListener('click', function() {
				var idAttributes  = $(this).attr('id');
				
				if ('undefined' === typeof $(this).attr('aria-sort')) {
					nodeAttribute = 'asc';
				} else if ('descending' === $(this).attr('aria-sort')) {
					nodeAttribute = 'asc';
				} else {
					nodeAttribute = 'desc';
				}
				
				var urls       = [];
				urls['column'] = encodeURIComponent('columns['+n+'][data]');
				urls['order']  = encodeURIComponent('order[0][column]');
				urls['dir']    = encodeURIComponent('order[0][dir]');
				var URLi       = urli + '&draw=0&' + urls['column'] + '=' + idAttributes + '&' + urls['order'] + '=' + n + '&' + urls['dir'] + '=' + nodeAttribute;
			}, false);
		}
	});
}
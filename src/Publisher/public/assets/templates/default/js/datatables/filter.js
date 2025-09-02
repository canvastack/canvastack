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
		if (null != link)   inputData['lurExp'] = link;
		if (null != filter) inputData['ftrExp'] = filter;
		
		$.ajax ({
			type    : 'POST',
			data    : canvastack_array_to_object(inputData),
			dataType: 'JSON',
			url     : url,
			success : function(n) {
				window.location.href = n.diyExportStreamPath;
			},
			complete : function() {
				$('#exportFilterButton' + modalID).removeAttr('style');
				$('#loader_'+ modalID).remove();
				$('#' + filterID).modal('hide');
			}
		});
	});
}

function diyDataTableFilters(id, url, obTable) {
	$('#diy-' + id + '-search-box').appendTo('.cody_' + id + '_diy-dt-filter-box');
	$('.diy-dt-search-box').removeClass('hide');
	$('#' + id + '_cdyProcessing').hide();
	
	$('#' + id + '_cdyFILTERForm').on('submit', function(event) {
		event.preventDefault();
		$('#' + id + '_cdyProcessing').show();
		
		var input = {};
		$.each($(this).serialize().split('&'), function(i, d) {
			var urls       = d.split('=');
			input[urls[0]] = urls[1];
		});
		
		var filterURI = [];
		$.each(input, function(index, value) {
			if (
				index != 'renderDataTables'          &&
				index != 'difta'                     &&
				index != 'filters'                   &&
				index != '_token'                    &&
				null  != value                       &&
				''    != value                       &&
				'____-__-__ __:__:__'       != value &&
				'____-__-__%20__%3A__%3A__' != value &&
				'____-__-__'                != value
			) {
				if ('string' === typeof(value)) {
					filterURI.push(index + '=' + encodeURIComponent(value));
				} else if ('object' === typeof(value)) {
					$.each(value, function(idx, _val) {
						filterURI.push(index + '[' + idx + ']' + '=' + encodeURIComponent(_val));
					});
				}
			}
		});
		
		obTable.ajax.url(url + '&' + filterURI.join('&') + '&filters=true').load(function() {
			$('#' + id + '_cdyProcessing').hide();
			$('#' + id + '_cdyFILTER').modal('hide');
		});
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
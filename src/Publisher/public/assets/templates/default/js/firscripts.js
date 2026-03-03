function ucwords(str, force) {
	str=force ? str.toLowerCase() : str;  
	return str.replace(/(\b)([a-zA-Z])/g, function(firstLetter) {
		return firstLetter.toUpperCase();
	});
}

function canvas_random(length = 8) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
	for ( var i = 0; i < length; i++ ) result += characters.charAt(Math.floor(Math.random() * charactersLength));
	
	return result;
}

function canvas_array_to_object(array) {
	return Object.assign({}, array);
}

function updateSelectChosen(target, reset = true, optstring = 'Select an Option') {
	var chosenTarget = $(target);
	if (true === reset) chosenTarget.find('option').remove().end();
	if (false !== optstring) {
		chosenTarget.append('<option value="">' + optstring + '</option>').trigger('chosen:updated');
	} else {
		chosenTarget.trigger('chosen:updated');
	}
}

function loader(target_id, view = 'hide') {
	var _loaderTarget = '#' + target_id;
	var _loaderID     = 'cdyInpLdr' + target_id;
	
	if ('remove' == view) {
		$('span.inputloader').remove();
	} else if ('fadeOut' == view) {
		$('span.inputloader').fadeOut(1800, function() { $(this).remove(); });
	} else {
		$(_loaderTarget).before('<span class="inputloader loader ' + view + '" id="'+ _loaderID + '"></span>');
	}
}

function ajaxSelectionProcess(object, id, target_id, url, data = [], method = 'POST', onError = 'Error') {
	var dataInfo = JSON.parse(data);
	
	if (typeof dataInfo.labels   != 'undefined') var lURL = 'l=' + dataInfo.labels;
	if (typeof dataInfo.values   != 'undefined') var vURL = 'v=' + dataInfo.values;
	if (typeof dataInfo.selected != 'undefined') var sURL = 's=' + dataInfo.selected;
	if (typeof dataInfo.query    != 'undefined') var qURL = canvas_random() + '=' + dataInfo.query;
	
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
			onError = xhr.responseText;
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
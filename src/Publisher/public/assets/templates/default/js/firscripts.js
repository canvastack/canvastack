function ucwords(str, force) {
	str=force ? str.toLowerCase() : str;  
	return str.replace(/(\b)([a-zA-Z])/g, function(firstLetter) {
		return firstLetter.toUpperCase();
	});
}

function canvastack_random(length = 8) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
	for ( var i = 0; i < length; i++ ) result += characters.charAt(Math.floor(Math.random() * charactersLength));
	
	return result;
}

function canvastack_array_to_object(array) {
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
	var _loaderID     = 'CanvaStackInpLdr' + target_id;
	
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
	
	// Prepare POST data object
	var postData = {};
	
	// Add encrypted parameters to POST body (raw, not URL encoded)
	if (typeof dataInfo.labels   != 'undefined') postData.l = dataInfo.labels;
	if (typeof dataInfo.values   != 'undefined') postData.v = dataInfo.values;
	if (typeof dataInfo.selected != 'undefined') postData.s = dataInfo.selected;
	if (typeof dataInfo.query    != 'undefined') postData[canvastack_random()] = dataInfo.query;
	
	// Merge with form data
	var formData = object.serializeArray();
	formData.forEach(function(item) {
		postData[item.name] = item.value;
	});
	
	var selected = null;
	var pinned   = '';
	
	$.ajax({
		type    : method,
		url     : url, // Clean URL without encrypted parameters
		data    : postData,
		dataType: 'json', // Expect JSON response
		contentType: 'application/x-www-form-urlencoded; charset=UTF-8', // Standard form encoding
		success : function(response) {
			// Handle both old format (plain object) and new format (wrapped in success)
			var result = response.success ? response.data : response;
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
			console.error('AJAX Error:', error, xhr.responseText);
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

/**
 * SMTP Test Connection Handler
 * Handles SMTP connection testing on preference page
 */
(function() {
	// Check if all SMTP fields are filled
	function checkSmtpFields() {
		const host = $('#smtp_host').val();
		const port = $('#smtp_port').val();
		const user = $('#smtp_user').val();
		
		if (host && port && user) {
			$('#smtp-test-container').slideDown();
		} else {
			$('#smtp-test-container').slideUp();
			$('#smtp-test-result').html('');
			$('#smtp-test-details').hide();
		}
	}
	
	// Initialize SMTP test functionality
	$(document).ready(function() {
		// Only run on preference page (check if SMTP fields exist)
		if ($('#smtp_host').length === 0) return;
		
		// Get test URL from data attribute
		const testUrl = $('#smtp-test-container').data('test-url');
		if (!testUrl) return;
		
		// Monitor field changes
		$('#smtp_host, #smtp_port, #smtp_secure, #smtp_user, #smtp_password').on('change keyup', checkSmtpFields);
		checkSmtpFields(); // Initial check
		
		// Handle test button click
		$('#test-smtp-btn').on('click', function() {
			const btn = $(this);
			const result = $('#smtp-test-result');
			const details = $('#smtp-test-details');
			
			// Disable button and show loading
			btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing Connection...');
			result.html('');
			details.hide();
			
			// Get CSRF token
			const token = $('input[name="_token"]').val();
			
			// Prepare data
			const data = {
				_token: token,
				smtp_host: $('#smtp_host').val(),
				smtp_port: $('#smtp_port').val(),
				smtp_secure: $('#smtp_secure').val(),
				smtp_user: $('#smtp_user').val(),
				smtp_password: $('#smtp_password').val()
			};
			
			// Send AJAX request
			$.ajax({
				url: testUrl,
				method: 'POST',
				data: data,
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						result.html('<span class="text-success"><i class="fa fa-check-circle"></i> <strong>Connection Successful!</strong></span>');
						details.removeClass('alert-danger').addClass('alert-success').html(
							'<strong>Success:</strong> ' + response.message
						).slideDown();
					} else {
						result.html('<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Connection Failed</strong></span>');
						details.removeClass('alert-success').addClass('alert-danger').html(
							'<strong>Error:</strong> ' + response.message
						).slideDown();
					}
				},
				error: function(xhr) {
					let errorMsg = 'Connection test failed. Please check your settings.';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
					result.html('<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Test Failed</strong></span>');
					details.removeClass('alert-success').addClass('alert-danger').html(
						'<strong>Error:</strong> ' + errorMsg
					).slideDown();
				},
				complete: function() {
					// Re-enable button
					btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Test SMTP Connection');
				}
			});
		});
	});
})();
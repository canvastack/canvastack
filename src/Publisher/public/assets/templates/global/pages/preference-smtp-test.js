/**
 * CanvaStack SMTP Test Connection Handler
 * 
 * Handles SMTP connection testing on preference page.
 * 
 * Features:
 * - Auto-show test button when all SMTP fields are filled
 * - AJAX connection testing
 * - Visual feedback (success/error)
 * - Detailed error messages
 * - Loading states
 * 
 * @package CanvaStack
 * @subpackage Global Pages
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window, $) {
	'use strict';
	
	/**
	 * Check if all SMTP fields are filled
	 */
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
	
	/**
	 * Initialize SMTP test functionality
	 */
	function initSmtpTest() {
		// Only run on preference page (check if SMTP fields exist)
		if ($('#smtp_host').length === 0) {
			return;
		}
		
		// Get test URL from data attribute
		const testUrl = $('#smtp-test-container').data('test-url');
		if (!testUrl) {
			console.warn('SMTP test URL not found');
			return;
		}
		
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
		
		console.log('SMTP test handler initialized');
	}
	
	/**
	 * Initialize on DOM ready
	 */
	$(document).ready(initSmtpTest);
	
	// Export for manual initialization if needed
	window.initSmtpTest = initSmtpTest;
	
})(window, jQuery);

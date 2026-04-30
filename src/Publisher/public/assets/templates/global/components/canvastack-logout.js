/**
 * CanvaStack Logout Component
 * 
 * Handles logout confirmation dialog with sound effects.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize logout confirmation
	 * Shows confirmation dialog before logout
	 */
	function init() {
		$('#logout').on('click', function(e) {
			e.preventDefault();
			
			// Play sound if ion.sound is available
			if (typeof ion !== 'undefined' && ion.sound) {
				ion.sound.play('camera_flashing');
			}

			// Show confirmation dialog
			if (typeof bootbox !== 'undefined') {
				// Use bootbox if available
				bootbox.dialog({
					message: 'Do you want to exit?',
					title: 'Logout',
					className: 'modal-danger modal-center',
					buttons: {
						danger: {
							label: 'No',
							className: 'btn-danger'
						},
						success: {
							label: 'Yes',
							className: 'btn-success',
							callback: function() {
								window.location = $('#logout').data('url');
							}
						}
					}
				});
			} else {
				// Fallback to native confirm
				if (confirm('Do you want to exit?')) {
					window.location = $('#logout').data('url');
				}
			}
		});
		
		console.log('CanvaStack Logout initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackLogout = {
		init: init
	};

})(jQuery, window);

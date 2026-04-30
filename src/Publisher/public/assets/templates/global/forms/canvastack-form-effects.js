/**
 * CanvaStack Form Effects Component
 * 
 * Provides visual effects for form inputs (focus/blur animations).
 * 
 * @package CanvaStack
 * @subpackage Global Forms
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize form focus effects
	 * Adds 'focused' class to parent .form-gp on input focus
	 * Removes class on blur if input is empty
	 */
	function init() {
		// Focus event
		$('.form-gp input').on('focus', function() {
			$(this).parent('.form-gp').addClass('focused');
		});
		
		// Blur event
		$('.form-gp input').on('focusout', function() {
			if ($(this).val().length === 0) {
				$(this).parent('.form-gp').removeClass('focused');
			}
		});
		
		console.log('CanvaStack Form Effects initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackFormEffects = {
		init: init
	};

})(jQuery, window);

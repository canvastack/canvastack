/**
 * CanvaStack Settings Panel Component
 * 
 * Handles settings panel toggle functionality.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize settings panel toggle
	 * Toggles visibility of settings panel
	 */
	function init() {
		$('.settings-btn, .offset-close').on('click', function() {
			$('.offset-area').toggleClass('show_hide');
			$('.settings-btn').toggleClass('active');
		});
		
		console.log('CanvaStack Settings Panel initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackSettingsPanel = {
		init: init
	};

})(jQuery, window);

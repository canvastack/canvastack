/**
 * CanvaStack Copyright Component
 * 
 * Automatically updates copyright year to current year.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize copyright year update
	 * Sets #copyright element text to current year
	 */
	function init() {
		if ($('#copyright').length) {
			var today = new Date();
			$('#copyright').text(today.getFullYear());
			
			console.log('CanvaStack Copyright initialized');
		}
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackCopyright = {
		init: init
	};

})(jQuery, window);

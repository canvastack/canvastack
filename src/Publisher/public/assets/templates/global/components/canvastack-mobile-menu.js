/**
 * CanvaStack Mobile Menu Component
 * 
 * Initializes SlickNav mobile menu plugin.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize SlickNav mobile menu
	 * Converts navigation menu to mobile-friendly dropdown
	 */
	function init() {
		if ($('ul#nav_menu').length && typeof $.fn.slicknav !== 'undefined') {
			$('ul#nav_menu').slicknav({
				prependTo: "#mobile_menu"
			});
			
			console.log('CanvaStack Mobile Menu initialized');
		}
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackMobileMenu = {
		init: init
	};

})(jQuery, window);

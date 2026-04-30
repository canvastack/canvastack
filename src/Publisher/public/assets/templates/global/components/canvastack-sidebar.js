/**
 * CanvaStack Sidebar Component
 * 
 * Handles sidebar toggle functionality for collapsing/expanding sidebar.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize sidebar toggle
	 * Toggles 'sbar_collapsed' class on page container
	 */
	function init() {
		$('.nav-btn').on('click', function() {
			$('.page-container').toggleClass('sbar_collapsed');
		});
		
		console.log('CanvaStack Sidebar initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackSidebar = {
		init: init
	};

})(jQuery, window);

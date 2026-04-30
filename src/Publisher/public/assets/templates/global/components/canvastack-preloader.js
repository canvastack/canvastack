/**
 * CanvaStack Preloader Component
 * 
 * Handles page preloader fade out animation on window load.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize preloader
	 * Fades out and removes preloader element on window load
	 */
	function init() {
		var preloader = $('#preloader');
		
		if (preloader.length) {
			$(window).on('load', function() {
				preloader.fadeOut('slow', function() {
					$(this).remove();
				});
			});
			
			console.log('CanvaStack Preloader initialized');
		}
	}

	// Auto-initialize
	init();

	// Export for manual initialization
	window.CanvaStackPreloader = {
		init: init
	};

})(jQuery, window);

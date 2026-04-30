/**
 * CanvaStack Layout Components
 * 
 * Handles layout-related functionality:
 * - Main content height calculation
 * - Sticky header on scroll
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Calculate and set main content minimum height
	 * Ensures content area fills viewport properly
	 */
	function calculateMainHeight() {
		var windowHeight = (window.innerHeight > 0 ? window.innerHeight : screen.height) - 5;
		windowHeight -= 47;
		
		if (windowHeight < 1) {
			windowHeight = 1;
		}
		
		if (windowHeight > 47) {
			$(".main-content").css("min-height", windowHeight + "px");
		}
	}

	/**
	 * Initialize sticky header on scroll
	 * Adds 'sticky-menu' class when scrolled past threshold
	 */
	function initStickyHeader() {
		$(window).on('scroll', function() {
			var scroll = $(window).scrollTop();

			if (scroll > 1) {
				$("#sticky-header").addClass("sticky-menu");
			} else {
				$("#sticky-header").removeClass("sticky-menu");
			}
		});
	}

	/**
	 * Initialize all layout components
	 */
	function init() {
		// Main content height
		$(window).ready(calculateMainHeight);
		$(window).on("resize", calculateMainHeight);
		
		// Sticky header
		if ($('#sticky-header').length) {
			initStickyHeader();
		}
		
		console.log('CanvaStack Layout initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackLayout = {
		init: init,
		calculateMainHeight: calculateMainHeight,
		initStickyHeader: initStickyHeader
	};

})(jQuery, window);

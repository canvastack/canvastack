/**
 * CanvaStack Back to Top Component
 * 
 * Provides smooth scroll to top functionality with show/hide on scroll.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window) {
	'use strict';

	/**
	 * Initialize back to top button
	 * Shows button when scrolled past threshold, scrolls to top on click
	 */
	function init() {
		var $backTop = $('#back-top');
		
		if (!$backTop.length) {
			return; // No back-to-top button on this page
		}

		// Hide initially
		$backTop.hide();
		
		// Show/hide on scroll
		$(window).scroll(function() {
			if ($(this).scrollTop() > 100) {
				$backTop.addClass('show animated pulse');
			} else {
				$backTop.removeClass('show animated pulse');
			}
		});
		
		// Scroll to top on click
		$backTop.click(function() {
			// Play sound if ion.sound is available
			if (typeof ion !== 'undefined' && ion.sound) {
				ion.sound.play("cd_tray");
			}
			
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			
			return false;
		});
		
		console.log('CanvaStack Back to Top initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackBackToTop = {
		init: init
	};

})(jQuery, window);

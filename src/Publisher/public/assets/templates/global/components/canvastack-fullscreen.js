/**
 * CanvaStack Fullscreen Component
 * 
 * Provides cross-browser fullscreen functionality with enter/exit controls.
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function($, window, document) {
	'use strict';

	/**
	 * Request fullscreen mode (cross-browser)
	 * 
	 * @param {HTMLElement} ele - Element to make fullscreen
	 */
	function requestFullscreen(ele) {
		if (ele.requestFullscreen) {
			ele.requestFullscreen();
		} else if (ele.webkitRequestFullscreen) {
			ele.webkitRequestFullscreen();
		} else if (ele.mozRequestFullScreen) {
			ele.mozRequestFullScreen();
		} else if (ele.msRequestFullscreen) {
			ele.msRequestFullscreen();
		} else {
			console.log('Fullscreen API is not supported.');
		}
	}

	/**
	 * Exit fullscreen mode (cross-browser)
	 */
	function exitFullscreen() {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		} else {
			console.log('Fullscreen API is not supported.');
		}
	}

	/**
	 * Initialize fullscreen controls
	 */
	function init() {
		if (!$('#full-view').length) {
			return; // No fullscreen button on this page
		}

		var fsDocButton = document.getElementById('full-view');
		var fsExitDocButton = document.getElementById('full-view-exit');

		if (fsDocButton) {
			fsDocButton.addEventListener('click', function(e) {
				e.preventDefault();
				requestFullscreen(document.documentElement);
				$('body').addClass('expanded');
			});
		}

		if (fsExitDocButton) {
			fsExitDocButton.addEventListener('click', function(e) {
				e.preventDefault();
				exitFullscreen();
				$('body').removeClass('expanded');
			});
		}
		
		console.log('CanvaStack Fullscreen initialized');
	}

	// Auto-initialize on DOM ready
	$(document).ready(init);

	// Export for manual initialization
	window.CanvaStackFullscreen = {
		init: init,
		request: requestFullscreen,
		exit: exitFullscreen
	};

})(jQuery, window, document);

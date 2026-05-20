/**
 * Default Template Scripts (Bootstrap 4)
 * 
 * Template-specific functionality for the default Bootstrap 4 template.
 * Global/shared functionality has been moved to global/ directory.
 * 
 * This file contains ONLY Bootstrap 4 specific code:
 * - slimScroll initialization (Bootstrap 4 only)
 * - metisMenu initialization (Bootstrap 4 specific)
 * - Chosen.js initialization (Bootstrap 4 select plugin)
 * - Select2 initialization (optional plugin)
 * - Ion Sound initialization (template-specific sounds)
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

(function($) {
	"use strict";
	
	/* ------------------------------------------------------------------ */
	/* Ion Sound Path Configuration                                        */
	/* ------------------------------------------------------------------ */
	
	var splitPath	= window.location.pathname.split('public')[0] + 'public/';
	var soundsPath	= splitPath.split(window.location.pathname.split('/')[1])[1] + 'assets/templates/default/vendor/plugins/nodes/ion-sound/sounds/';

	/* ------------------------------------------------------------------ */
	/* MetisMenu Initialization (Bootstrap 4)                              */
	/* ------------------------------------------------------------------ */
	
	$('#menu').metisMenu();
	
	/* ------------------------------------------------------------------ */
	/* SlimScroll Initialization (Bootstrap 4 only)                        */
	/* ------------------------------------------------------------------ */
	
	var sidebarSubHeight       = $('.sidebar-menu>.relative>.user-panel.light').outerHeight(true);
	var sidebarHeadNSubHeight  = $('.sidebar-header').outerHeight(true) + sidebarSubHeight;
	var slimScrollHeight       = sidebarHeadNSubHeight + 10;
	
	$('.menu-inner').slimScroll({
		height : $(window).height() - slimScrollHeight,
		maxHeight : 'auto'
	});
	
	$('.menu-inner').css("height", $(window).height() - slimScrollHeight);
	$(window).resize(function() {
		$('.menu-inner').css("height", $(window).height() - slimScrollHeight);
	});
	
	$('.slimScrollDiv').css("height", $(window).height() - slimScrollHeight);
	$(window).resize(function() {
		$('.slimScrollDiv').css("height", $(window).height() - slimScrollHeight);
	});
	
	$('.slimScrollBar').css({ 'right': 'unset !important', 'left' : 0, });
	$('.scroolbox').slimScroll({ height: 'auto' });
	$('.chosen-drop').slimScroll({ height: 'auto' });
	$('.nofity-list').slimScroll({ height: '200px' });
	$('.timeline-area').slimScroll({ height: '500px' });
	$('.recent-activity').slimScroll({ height: 'calc(100vh - 114px)' });
	$('.settings-list').slimScroll({ height: 'calc(100vh - 158px)' });

	/* ------------------------------------------------------------------ */
	/* Chosen.js Initialization (Bootstrap 4 select plugin)               */
	/* ------------------------------------------------------------------ */

	if ($(".chosen-select").length || $(".chosen-select-deselect").length) {
		var config = {
			'.chosen-select' : {},
			'.chosen-select-deselect' : {
				allow_single_deselect : true
			},
			'.chosen-select-no-single' : {
				disable_search_threshold : 10
			},
			'.chosen-select-no-results' : {
				no_results_text : 'Oops, nothing found!'
			},
			'.chosen-select-rtl' : {
				rtl : true
			},
			'.chosen-select-width' : {
				width : '95%'
			}
		}
		for ( var selector in config) {
			$(selector).chosen(config[selector]);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Select2 Initialization (optional plugin)                            */
	/* ------------------------------------------------------------------ */

	if ($(".select2").length) {
		$(".select2").select2({
			theme : "bootstrap"
		});
	}

	/* ------------------------------------------------------------------ */
	/* Ion Sound Initialization (template-specific)                        */
	/* ------------------------------------------------------------------ */

	if ($('.page-sound').length && typeof ion !== 'undefined' && ion.sound) {
		ion.sound({
			sounds : [
				{name : "cd_tray", volume : 0.6},
				{name : "water_droplet_3", volume : 0.4},
				{name : "camera_flashing", volume : 0.5}
			],
			path : handleBaseURL() + soundsPath,
			preload : true
		});

		$('.dropdown-toggle').on('click', function() {
			if (ion.sound) ion.sound.play("water_droplet_3");
		});
	}

})(jQuery);

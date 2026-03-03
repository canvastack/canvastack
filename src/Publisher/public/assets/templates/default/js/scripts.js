(function($) {
	"use strict";
	
	function handleBaseURL() {
		var getUrl  = window.location,
			baseUrl = getUrl.protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
			
		return baseUrl;
	}
	
	var splitPath	= window.location.pathname.split('public')[0] + 'public/';
	var soundsPath	= splitPath.split(window.location.pathname.split('/')[1])[1] + 'assets/templates/default/vendor/plugins/nodes/ion-sound/sounds/';

	var preloader	= $('#preloader');
	$(window).on('load', function() {
		preloader.fadeOut('slow', function() {
			$(this).remove();
		});
	});
	
	$('.nav-btn').on('click', function() {
		$('.page-container').toggleClass('sbar_collapsed');
	});
	
	var e = function() {
		var e = (window.innerHeight > 0 ? window.innerHeight : this.screen.height) - 5;
		(e -= 47) < 1 && (e = 1), e > 47 && $(".main-content").css("min-height", e + "px")
	};
	$(window).ready(e), $(window).on("resize", e);
	
	$('#menu').metisMenu();
	var sidebarSubHeight       = $('.sidebar-menu>.relative>.user-panel.light').outerHeight(true);
	var sidebarHeadNSubHeight  = $('.sidebar-header').outerHeight(true) + sidebarSubHeight;
	var slimScrollHeight       = sidebarHeadNSubHeight + 10;
	
	$('.menu-inner').slimScroll({
		height : $(window).height() - slimScrollHeight,
		maxHeight : e + 'px !important'
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
	
	$(window).on('scroll', function() {
		var scroll           = $(window).scrollTop(), 
			mainHeader       = $('#sticky-header'), 
			mainHeaderHeight = mainHeader.innerHeight();

		if (scroll > 1) {
			$("#sticky-header").addClass("sticky-menu");
		} else {
			$("#sticky-header").removeClass("sticky-menu");
		}
	});

	$('[data-toggle="popover"]').popover();
	window.addEventListener('load', function() {
		// Fetch all the forms we want to apply custom Bootstrap validation
		// styles to
		var forms = document.getElementsByClassName('needs-validation');
		// Loop over them and prevent submission
		var validation = Array.prototype.filter.call(forms, function(form) {
			form.addEventListener('submit', function(event) {
				if (form.checkValidity() === false) {
					event.preventDefault();
					event.stopPropagation();
				}
				form.classList.add('was-validated');
			}, false);
		});
	}, false);
	
	if ($('#dataTable').length) {
		$('#dataTable').DataTable({
			responsive : true
		});
	}
	
	$('ul#nav_menu').slicknav({
		prependTo : "#mobile_menu"
	});
	
	$('.form-gp input').on('focus', function() {
		$(this).parent('.form-gp').addClass('focused');
	});
	$('.form-gp input').on('focusout', function() {
		if ($(this).val().length === 0) {
			$(this).parent('.form-gp').removeClass('focused');
		}
	});
	
	$('.settings-btn, .offset-close').on('click', function() {
		$('.offset-area').toggleClass('show_hide');
		$('.settings-btn').toggleClass('active');
	});
	
	if ($('#full-view').length) {

		var requestFullscreen = function(ele) {
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
		};

		var exitFullscreen = function() {
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
		};

		var fsDocButton = document.getElementById('full-view');
		var fsExitDocButton = document.getElementById('full-view-exit');

		fsDocButton.addEventListener('click', function(e) {
			e.preventDefault();
			requestFullscreen(document.documentElement);
			$('body').addClass('expanded');
		});

		fsExitDocButton.addEventListener('click', function(e) {
			e.preventDefault();
			exitFullscreen();
			$('body').removeClass('expanded');
		});
	}

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

	if ($(".select2").length) {
		$(".select2").select2({
			theme : "bootstrap"
		});
	}

	if ($('#copyright').length) {
		var today = new Date();
		$('#copyright').text(today.getFullYear());
	}

	if ($('.page-sound').length) {ion.sound({
			sounds : [
				{name : "cd_tray", volume : 0.6}
			],
			path : handleBaseURL() + soundsPath,
			preload : true
		});

		$('.dropdown-toggle').on('click', function() {
			ion.sound.play("water_droplet_3");
		});
	}
	
	$('#logout').on('click', function() {
		ion.sound.play('camera_flashing');
		bootbox.dialog({
			message : 'Do you want to exit?',
			title : 'Logout',
			className : 'modal-danger modal-center',
			buttons : {
				danger : {
					label : 'No',
					className : 'btn-danger'
				},
				success : {
					label : 'Yes',
					className : 'btn-success',
					callback : function() {
						window.location = $('#logout').data('url');
					}
				}
			}
		});
	});
	
	$('#back-top').hide();
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('#back-top').addClass('show animated pulse');
        } else {
            $('#back-top').removeClass('show animated pulse');
        }
    });
    $('#back-top').click(function () {
        ion.sound.play("cd_tray");
        $('body,html').animate({
            scrollTop: 0
        }, 800);
        return false;
    });
})(jQuery);
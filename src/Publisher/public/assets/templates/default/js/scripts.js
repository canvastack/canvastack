(function ($) {
  "use strict";

  // Cached selectors
  var $win = $(window);
  var $doc = $(document);
  var $stickyHeader = $('#sticky-header');
  var $backTop = $('#back-top');

  // Resolve asset base URL safely (prefer server-provided config)
  function getAssetBase() {
    if (window.AppConfig && typeof window.AppConfig.assetBase === 'string') {
      return window.AppConfig.assetBase;
    }
    var baseEl = document.querySelector('base');
    if (baseEl && baseEl.href) return baseEl.href;
    return window.location.origin + '/';
  }

  var soundsPath = getAssetBase() + 'assets/templates/default/vendor/plugins/nodes/ion-sound/sounds/';

  // Preloader
  var $preloader = $('#preloader');
  $win.on('load', function () {
    $preloader.fadeOut('slow', function () {
      $(this).remove();
    });
  });

  // Sidebar toggle
  $('.nav-btn').on('click', function () {
    $('.page-container').toggleClass('sbar_collapsed');
  });

  // Maintain min-height of main-content
  function setMainContentMinHeight() {
    var h = (window.innerHeight > 0 ? window.innerHeight : screen.height) - 5;
    h = Math.max(1, h);
    if (h > 47) $('.main-content').css('min-height', h + 'px');
  }
  $win.ready(setMainContentMinHeight);

  // Optimized resize with rAF debounce
  var resizeRaf = null;
  function onResize() {
    if (resizeRaf) cancelAnimationFrame(resizeRaf);
    resizeRaf = requestAnimationFrame(function () {
      setMainContentMinHeight();
    });
  }
  window.addEventListener('resize', onResize);

  // Sidebar menu (metisMenu)
  if ($.fn.metisMenu) $('#menu').metisMenu();

  // Slim scroll heights (guard if plugin present)
  (function initSlimScroll() {
    var $userPanel = $('.sidebar-menu>.relative>.user-panel.light');
    var sidebarSubHeight = $userPanel.length ? $userPanel.outerHeight(true) : 0;
    var sidebarHeadNSubHeight = ($('.sidebar-header').outerHeight(true) || 0) + sidebarSubHeight;
    var slimScrollHeight = sidebarHeadNSubHeight + 10;

    if ($.fn.slimScroll) {
      $('.menu-inner').slimScroll({ height: $win.height() - slimScrollHeight });
      $('.menu-inner').css('height', $win.height() - slimScrollHeight);
      window.addEventListener('resize', function () {
        $('.menu-inner').css('height', $win.height() - slimScrollHeight);
      });

      $('.scroolbox').slimScroll({ height: 'auto' });
      $('.chosen-drop').slimScroll({ height: 'auto' });
      $('.nofity-list').slimScroll({ height: '200px' });
      $('.timeline-area').slimScroll({ height: '500px' });
      $('.recent-activity').slimScroll({ height: 'calc(100vh - 114px)' });
      $('.settings-list').slimScroll({ height: 'calc(100vh - 158px)' });
    }
  })();

  // Optimized scroll handling (unify sticky + back-top) with rAF + passive
  var supportsPassive = false;
  try {
    var opts = Object.defineProperty({}, 'passive', {
      get: function () { supportsPassive = true; }
    });
    window.addEventListener('test-passive', null, opts);
    window.removeEventListener('test-passive', null, opts);
  } catch (e) {}

  var lastKnownScrollY = 0;
  var scrollTicking = false;
  function runScrollEffects() {
    var y = lastKnownScrollY;
    if (y > 1) $stickyHeader.addClass('sticky-menu');
    else $stickyHeader.removeClass('sticky-menu');

    if (y > 100) $backTop.addClass('show animated pulse');
    else $backTop.removeClass('show animated pulse');

    scrollTicking = false;
  }
  function onScroll() {
    lastKnownScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
    if (!scrollTicking) {
      scrollTicking = true;
      requestAnimationFrame(runScrollEffects);
    }
  }
  window.addEventListener('scroll', onScroll, supportsPassive ? { passive: true } : false);

  // Bootstrap popover
  if ($.fn.popover) $('[data-toggle="popover"]').popover();

  // Bootstrap validation styles
  window.addEventListener('load', function () {
    var forms = document.getElementsByClassName('needs-validation');
    Array.prototype.forEach.call(forms, function (form) {
      form.addEventListener('submit', function (event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);

  // DataTables init (avoid double init)
  if ($.fn.DataTable && $('#dataTable').length) {
    if (!$.fn.DataTable.isDataTable('#dataTable')) $('#dataTable').DataTable({ responsive: true });
  }

  // Mobile nav
  if ($.fn.slicknav) $('ul#nav_menu').slicknav({ prependTo: '#mobile_menu' });

  // Form group focus styles
  $('.form-gp input')
    .on('focus', function () { $(this).parent('.form-gp').addClass('focused'); })
    .on('focusout', function () { if ($(this).val().length === 0) $(this).parent('.form-gp').removeClass('focused'); });

  // Offset area toggle
  $('.settings-btn, .offset-close').on('click', function () {
    $('.offset-area').toggleClass('show_hide');
    $('.settings-btn').toggleClass('active');
  });

  // Fullscreen controls
  if (document.getElementById('full-view')) {
    var requestFullscreen = function (ele) {
      if (ele.requestFullscreen) ele.requestFullscreen();
      else if (ele.webkitRequestFullscreen) ele.webkitRequestFullscreen();
      else if (ele.mozRequestFullScreen) ele.mozRequestFullScreen();
      else if (ele.msRequestFullscreen) ele.msRequestFullscreen();
      else console.log('Fullscreen API is not supported.');
    };

    var exitFullscreen = function () {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
      else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
      else if (document.msExitFullscreen) document.msExitFullscreen();
      else console.log('Fullscreen API is not supported.');
    };

    var fsDocButton = document.getElementById('full-view');
    var fsExitDocButton = document.getElementById('full-view-exit');

    fsDocButton.addEventListener('click', function (e) {
      e.preventDefault();
      requestFullscreen(document.documentElement);
      $('body').addClass('expanded');
    });

    fsExitDocButton.addEventListener('click', function (e) {
      e.preventDefault();
      exitFullscreen();
      $('body').removeClass('expanded');
    });
  }

  // Chosen
  if ($.fn.chosen) {
    var config = {
      '.chosen-select': {},
      '.chosen-select-deselect': { allow_single_deselect: true },
      '.chosen-select-no-single': { disable_search_threshold: 10 },
      '.chosen-select-no-results': { no_results_text: 'Oops, nothing found!' },
      '.chosen-select-rtl': { rtl: true },
      '.chosen-select-width': { width: '95%' }
    };
    for (var selector in config) {
      if (Object.prototype.hasOwnProperty.call(config, selector)) $(selector).chosen(config[selector]);
    }
  }

  // Select2
  if ($.fn.select2 && $('.select2').length) $('.select2').select2({ theme: 'bootstrap' });

  // Footer copyright year
  if ($('#copyright').length) $('#copyright').text(new Date().getFullYear());

  // Ion.Sound (guard and preload all used sounds)
  if (window.ion && ion.sound && $('.page-sound').length) {
    ion.sound({
      sounds: [
        { name: 'cd_tray', volume: 0.6 },
        { name: 'water_droplet_3' },
        { name: 'camera_flashing' }
      ],
      path: soundsPath,
      preload: true
    });

    $('.dropdown-toggle').on('click', function () {
      ion.sound.play('water_droplet_3');
    });
  }

  // Logout dialog (guard bootbox)
  $('#logout').on('click', function () {
    if (window.ion && ion.sound) { try { ion.sound.play('camera_flashing'); } catch (e) {} }
    if (window.bootbox && bootbox.dialog) {
      bootbox.dialog({
        message: 'Do you want to exit?',
        title: 'Logout',
        className: 'modal-danger modal-center',
        buttons: {
          danger: { label: 'No', className: 'btn-danger' },
          success: { label: 'Yes', className: 'btn-success', callback: function () { window.location = $('#logout').data('url'); } }
        }
      });
    } else {
      var ok = window.confirm('Do you want to exit?');
      if (ok) window.location = $('#logout').data('url');
    }
  });

  // Back to top
  $backTop.hide();
  $backTop.on('click', function () {
    if (window.ion && ion.sound) { try { ion.sound.play('cd_tray'); } catch (e) {} }
    $('body,html').animate({ scrollTop: 0 }, 800);
    return false;
  });
})(jQuery);

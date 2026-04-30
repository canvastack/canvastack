/**
 * Canvasign Template Scripts (Bootstrap 5)
 *
 * Template-specific functionality for the canvasign Bootstrap 5 template.
 * Global/shared functionality has been moved to global/ directory.
 *
 * This file contains ONLY Bootstrap 5 specific code:
 * - MetisMenu initialization (Bootstrap 5 compatible)
 * - Select2 initialization (fallback for non-Choices.js selects)
 * - Ion Sound initialization (template-specific sounds)
 *
 * @author  wisnuwidi@canvastack.com
 * @copyright Canvastack
 */

(function($) {
    'use strict';

    console.log('🔍 Canvasign Scripts Loading...', {
        jQueryVersion: $.fn.jquery,
        metisMenuAvailable: typeof $.fn.metisMenu !== 'undefined',
        menuElement: $('#menu').length,
        menuHTML: $('#menu').length ? $('#menu')[0].outerHTML.substring(0, 200) : 'NOT FOUND'
    });

    /* ------------------------------------------------------------------ */
    /* Ion Sound Path Configuration                                        */
    /* ------------------------------------------------------------------ */

    var splitPath = window.location.pathname.split('public')[0] + 'public/';
    var soundsPath = splitPath.split(window.location.pathname.split('/')[1])[1] + 'assets/templates/default/vendor/plugins/nodes/ion-sound/sounds/';

    /* ------------------------------------------------------------------ */
    /* Menu Initialization - DISABLED                                      */
    /* ------------------------------------------------------------------ */

    // metisMenu COMPLETELY DISABLED - incompatible with Bootstrap 5
    // Using canvasign-menu.js instead (Bootstrap 5 native accordion)
    
    console.log('🚫 metisMenu COMPLETELY DISABLED - using Bootstrap 5 native accordion (canvasign-menu.js)');

    /* ------------------------------------------------------------------ */
    /* Select2 Initialization (fallback for non-Choices.js selects)       */
    /* ------------------------------------------------------------------ */

    if ($(".select2").length && typeof $.fn.select2 !== 'undefined') {
        $(".select2").select2({
            theme: "bootstrap"
        });
    }

    /* ------------------------------------------------------------------ */
    /* Ion Sound Initialization (template-specific)                        */
    /* ------------------------------------------------------------------ */

    if ($('.page-sound').length && typeof ion !== 'undefined' && ion.sound) {
        ion.sound({
            sounds: [
                {name: "cd_tray", volume: 0.6},
                {name: "water_droplet_3", volume: 0.4},
                {name: "camera_flashing", volume: 0.5}
            ],
            path: handleBaseURL() + soundsPath,
            preload: true
        });

        $('.dropdown-toggle').on('click', function() {
            if (ion.sound) ion.sound.play("water_droplet_3");
        });
    }

})(jQuery);

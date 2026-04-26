/**
 * Created on Mar 13, 2018
 * Time Created	: 9:26:50 AM
 * Filename		: form.picker.js
 *
 * @filesource	form.picker.js
 *
 * @author		wisnuwidi@gmail.com - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@gmail.com
 */
 
var JSFormPicker = function () {
    return {

        // =========================================================================
        // CONSTRUCTOR APP
        // =========================================================================
        init: function () {
        	if($('.date-picker').length){
        		JSFormPicker.bootstrapDatepicker();
        	}
        	if($('.datetime-picker').length){
        		JSFormPicker.bootstrapDatetimepicker();
        	}
        	if($('.daterange-picker').length){
	            JSFormPicker.bootstrapDaterangepicker();
        	}
        	if($('.bootstrap-timepicker').length){
	            JSFormPicker.bootstrapTimepicker();
        	}
        	if($('.color-picker').length){
        		JSFormPicker.bootstrapColorpicker();
        	}
        },

        // =========================================================================
        // BOOTSTRAP DATEPICKER
        // =========================================================================
        bootstrapDatepicker: function () {
        	$('.date-picker').datetimepicker({
    			'format'		: "Y-m-d",
    			'timepicker'	: false,
    			'mask'			: true
            });
        //	$('.date-picker').datepicker({format:'yyyy-mm-dd',showDropdowns:true,todayBtn:'linked',drops:'up'});
        },

        // =========================================================================
        // BOOTSTRAP DATETIMEPICKER
        // =========================================================================
        bootstrapDatetimepicker: function () {
    		$('.datetime-picker').datetimepicker({
    			'format' : "Y-m-d H:m:s",
    			'mask' : true
    		});
    	},

        // =========================================================================
        // BOOTSTRAP DATE RANGE PICKER
        // =========================================================================
        bootstrapDaterangepicker: function () {
            // Global call trigger
            $('.daterange-picker').daterangepicker({
            	locale: {
            		format: 'YYYY-MM-DD',
                    separator: ' | '
            	},
                showDropdowns: true,
                showWeekNumbers: true,
                autoApply: true,
                linkedCalendars: false,
                showWeekNumbers: true,
                opens: 'right',
                drops: 'down'
            });

            // Date and Time
            $('.daterange-picker-time').daterangepicker({
                timePicker: true,
                timePickerIncrement: 30,
                locale: {
                    format: 'YYYY-MM-DD h:mm A'
                }
            });

            // Single Date Picker
            $('.daterange-picker-single').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true
                },
                function(start, end, label) {
                    var years = moment().diff(start, 'years');
                });

            // Predefined Ranges
            function cb(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
            cb(moment().subtract(29, 'days'), moment());

            $('#reportrange').daterangepicker({
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);
        },

        // =========================================================================
        // BOOTSTRAP TIMEPICKER
        // =========================================================================
        bootstrapTimepicker: function () {
        	$('.bootstrap-timepicker').timepicker({
                minuteStep: 1,
                template: 'dropdown',
                showSeconds: false,
                timezone: 'WIB',
                showMeridian: false,
                showInputs: true,
                format: 'HH:mm'
            });
        },

        // =========================================================================
        // BOOTSTRAP COLOR PICKER
        // =========================================================================
        bootstrapColorpicker: function () {

            // Trigger colorpicker global
            $('.color-picker').colorpicker();

            // Transparent color support
            $('.color-picker-transparent').colorpicker({
                format: 'rgba' // force this format
            });

            // Horizonal mode
            $('.color-picker-horizontal').colorpicker({
                format: 'rgba', // force this format
                horizontal: true
            });

            // Bootstrap colors
            $('.color-picker-bootstrap').colorpicker({
                colorSelectors: {
                    'default': '#777777',
                    'primary': '#337ab7',
                    'success': '#5cb85c',
                    'info': '#5bc0de',
                    'warning': '#f0ad4e',
                    'danger': '#d9534f'
                }
            });

            // Custom widget size
            $('.color-picker-size').colorpicker({
                customClass: 'colorpicker-2x',
                sliders: {
                    saturation: {
                        maxLeft: 200,
                        maxTop: 200
                    },
                    hue: {
                        maxTop: 200
                    },
                    alpha: {
                        maxTop: 200
                    }
                }
            });

            // Using events
            var bodyStyle = $('.body-content')[0].style;
            $('.colorpicker-event').colorpicker({
                color: bodyStyle.backgroundColor
            }).on('changeColor', function(ev) {
                bodyStyle.backgroundColor = ev.color.toHex();
            });
        }
    };

}();

// Call main app init
JSFormPicker.init();
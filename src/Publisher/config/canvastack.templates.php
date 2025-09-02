<?php
/**
 * Created on Nov 2, 2018
 * Time Created	: 11:51:34 PM
 * Filename		: canvastack.templates.php
 *
 * @filesource	canvastack.templates.php
 *
 * @author		wisnuwidi@gmail.com - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@gmail.com
 */

return [
    'admin' => [
        'default' => [
            'position' => [
                'top' => [
                    'js' => [
                        'vendor/plugins/nodes/jquery/dist/jquery.min.js',
                        'vendor/plugins/nodes/popper.js/dist/umd/popper.min.js',
                        'vendor/plugins/nodes/bootstrap/dist/js/bootstrap.min.js',
                        'vendor/plugins/nodes/ion-sound/js/ion.sound.min.js',
                        'js/sidebar.js',
                        'js/firscripts.js',
                    ],
                    'css' => [
                        'vendor/plugins/nodes/bootstrap/dist/css/bootstrap.css',
                    ],
                ],
                'bottom' => [
                    'first' => [
                        'js' => [
                            'vendor/plugins/jquery-ui/jquery-ui.min.js',
                            'vendor/plugins/jquery-cookie/jquery.cookie.js',
                            'js/metisMenu.min.js',
                            'vendor/plugins/nodes/owl.carousel/dist/owl.carousel.min.js',
                            'vendor/plugins/nodes/jquery-slimscroll/jquery.slimscroll.min.js',
                            'vendor/plugins/nodes/slicknav/dist/jquery.slicknav.min.js',
                            'vendor/plugins/jquery-nicescroll/jquery.nicescroll.min.js',
                        ],
                        'css' => ['css/config.css'],
                    ],
                    'last' => [
                        'js' => [
                            'js/plugins.js',
                            'js/scripts.js',
                            'js/diyscripts.js',
                        ],
                        'css' => ['css/app.css'],
                    ],
                ],
            ],

            /**
             * DATATABLES
             */
            /* LOCAL VERSION */
            'datatable' => [
                'js' => [
                    'vendor/DataTables/js/datatables.min.js',
                    'vendor/DataTables/js/pdfmake.js',
                    'vendor/DataTables/js/vfs_fonts.js',
                    'js/datatables/filter.js',
                ],
                'css' => [
                    'vendor/DataTables/css/datatables.css',
                ],
            ],
            /* LOCAL VERSION */
            /* CDN VERSION *
            'datatable' => [
                'js'	=> [
                    'https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.13.4/af-2.5.3/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/date-1.4.0/fc-4.2.2/fh-3.3.2/kt-2.8.2/r-2.4.1/rg-1.3.1/rr-1.3.3/sc-2.1.1/sb-1.4.2/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js',
                    'js/datatables/filter.js'
                ],
                'css'	=> [
                    'https://cdn.datatables.net/v/bs4/jq-3.6.0/jszip-2.5.0/dt-1.13.4/af-2.5.3/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/date-1.4.0/fc-4.2.2/fh-3.3.2/kt-2.8.2/r-2.4.1/rg-1.3.1/rr-1.3.3/sc-2.1.1/sb-1.4.2/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.css'
                ]
            ],
            /* CDN VERSION */
            /**
             * DATATABLES
             */
            'textarea' => [
                'js' => [
                    'vendor/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                    'js/textarea.js',
                ],
                'css' => [null],
            ],

            'tagsinput' => [
                'js' => ['vendor/plugins/nodes/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js'],
                'css' => ['vendor/plugins/nodes/bootstrap-tagsinput/dist/bootstrap-tagsinput.css'],
            ],

            'file' => [
                'js' => ['vendor/plugins/nodes/jasny-bootstrap/dist/js/jasny-bootstrap.min.js'],
                'css' => ['vendor/plugins/nodes/jasny-bootstrap/dist/css/jasny-bootstrap.min.css'],
            ],

            'select' => [
                'js' => ['vendor/plugins/nodes/chosen-js/chosen.jquery.min.js'],
                'css' => ['vendor/plugins/nodes/chosen-js/chosen.min.css'],
            ],

            'selectMonth' => [
                'js' => ['vendor/plugins/nodes/chosen-js/chosen.jquery.min.js'],
                'css' => ['vendor/plugins/nodes/chosen-js/chosen.min.css'],
            ],

            'date' => [
                'js' => [
                    'vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js',
                    'last:js/form.picker.js',
                ],
                'css' => ['vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css'],
            ],

            'datetime' => [
                'js' => [
                    'vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js',
                    'last:js/form.picker.js',
                ],
                'css' => ['vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css'],
            ],

            'daterange' => [
                'js' => [
                    'vendor/plugins/moment/min/moment.min.js',
                    'vendor/plugins/bootstrap-daterangepicker/daterangepicker.js',
                    'last:js/form.picker.js',
                ],
                'css' => ['vendor/plugins/bootstrap-daterangepicker/daterangepicker.css'],
            ],

            'time' => [
                'js' => [
                    'vendor/plugins/bootstrap-timepicker/js/bootstrap-timepicker.js',
                    'last:js/form.picker.js',
                ],
                'css' => ['vendor/plugins/bootstrap-timepicker/css/timepicker.css'],
            ],

            'highcharts' => [
                'js' => [
                    'vendor/plugins/highcharts/js/highcharts.js',
                    'vendor/plugins/highcharts/js/modules/exporting.js',

                ],
                'css' => [null],
            ],

            'chartjs' => [
                'js' => [
                    'charts/chartjs/Chart.min.js',
                ],
                'css' => [null],
            ],
        ],
    ],
];

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
 * @email		wisnuwidi@gmail.com
 */
 
return [
	'admin' => [
		'default' => [
			'position' => [
				'top' => [
					'js'	=> [
						'vendor/plugins/nodes/jquery/dist/jquery.min.js',
						'vendor/plugins/nodes/popper.js/dist/umd/popper.min.js',
						'vendor/plugins/nodes/bootstrap/dist/js/bootstrap.min.js',
						'vendor/plugins/nodes/ion-sound/js/ion.sound.min.js',
						'js/sidebar.js',
						'../global/js/canvastack-console-filter.js',
						
						// Ajax selection wrapper (load early, after jQuery)
						'../global/js/canvastack-ajax-selection-wrapper.js',
						
						// Mapping page wrapper (load early, after jQuery)
						'../global/js/canvastack-mapping-page-wrapper.js',
						
						'../global/core/canvastack-core-utils.js',
						'../global/core/canvastack-loader.js',
						'../global/core/canvastack-select-handler.js'
					],
					'css'	=> [
    					'vendor/plugins/nodes/bootstrap/dist/css/bootstrap.css'
					]
				],
				'bottom'	=> [
					'first'	=> [
						'js'	=> [
							'vendor/plugins/jquery-ui/jquery-ui.min.js',
							'vendor/plugins/jquery-cookie/jquery.cookie.js',
							'js/metisMenu.min.js',
							'vendor/plugins/nodes/owl.carousel/dist/owl.carousel.min.js',
							'vendor/plugins/nodes/jquery-slimscroll/jquery.slimscroll.min.js',
							'vendor/plugins/nodes/slicknav/dist/jquery.slicknav.min.js',
							'vendor/plugins/jquery-nicescroll/jquery.nicescroll.min.js'
						],
						'css'	=> ['css/config.css']
					],
					'last'	=> [
						'js'	=> [
							'js/plugins.js',
							'js/scripts.js',
							
							// DataTables library (must load before canvastack-datatables.js)
							'vendor/DataTables/js/datatables.min.js',
							
							'../global/adapters/canvastack-modal-adapter.js',
							'../global/adapters/canvastack-tooltip-adapter.js',
							'../global/components/canvastack-preloader.js',
							'../global/components/canvastack-sidebar.js',
							'../global/components/canvastack-layout.js',
							'../global/components/canvastack-mobile-menu.js',
							'../global/components/canvastack-settings-panel.js',
							'../global/components/canvastack-fullscreen.js',
							'../global/components/canvastack-copyright.js',
							'../global/components/canvastack-logout.js',
							'../global/components/canvastack-back-to-top.js',
							'../global/forms/canvastack-form-validation.js',
							'../global/forms/canvastack-form-effects.js',
							'../global/datatables/canvastack-datatables.js',
							'../global/datatables/canvastack-datatables-filters.js', // Moved here - needed for forms too
							'../global/components/canvastack-delete-handler.js',
							'../global/forms/canvastack-cascading-filter.js',
							'../global/components/canvastack-privilege-table.js',
							'../global/components/canvastack-datatable-checkbox.js',
							'../global/pages/mapping-page-handlers.js',
							'../global/pages/preference-smtp-test.js',
							'../global/pages/canvastack-cache-manager.js'
						],
						'css'	=> [
							'css/app.css',
							'css/canvastack.css',
							'css/delete-modal.css'
						]
					]
				]
			],
				
			/**
			 * DATATABLES 
			 */
			/* LOCAL VERSION */
			'datatable' => [
				'js'	=> [
					'vendor/DataTables/js/datatables.min.js',
					'vendor/DataTables/js/pdfmake.js',
					'vendor/DataTables/js/vfs_fonts.js',
					'../global/datatables/canvastack-datatables-filters.js',
					'../global/datatables/canvastack-datatables-export.js'
				],
				'css'	=> [
					'vendor/DataTables/css/datatables.css'
				]
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
			
			'textarea'	=> [
				'js'	=> [
					'vendor/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
					'js/textarea.js'
				],
				'css'	=> [null]
			],
		    
			'tagsinput' => [
				'js'	=> ['vendor/plugins/nodes/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js'],
				'css'	=> ['vendor/plugins/nodes/bootstrap-tagsinput/dist/bootstrap-tagsinput.css']
			],
		    
			'file' => [
				'js'	=> ['vendor/plugins/nodes/jasny-bootstrap/dist/js/jasny-bootstrap.min.js'],
				'css'	=> ['vendor/plugins/nodes/jasny-bootstrap/dist/css/jasny-bootstrap.min.css']
			],
			
			'select' => [
				'js'	=> ['vendor/plugins/nodes/chosen-js/chosen.jquery.min.js'],
				'css'	=> ['vendor/plugins/nodes/chosen-js/chosen.min.css']
			],
			
			'selectMonth' => [
				'js'	=> ['vendor/plugins/nodes/chosen-js/chosen.jquery.min.js'],
				'css'	=> ['vendor/plugins/nodes/chosen-js/chosen.min.css']
			],
		    
			'date' => [
				'js'	=> [
					'vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js',
					'last:../global/forms/canvastack-form-pickers.js'
				],
				'css'	=> ['vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css']
			],
		    
			'datetime'	=> [
				'js'	=> [
					'vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js',
					'last:../global/forms/canvastack-form-pickers.js'
				],
				'css'	=> ['vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css']
			],
		    
			'daterange' => [
				'js'	=> [
					'vendor/plugins/moment/min/moment.min.js',
					'vendor/plugins/bootstrap-daterangepicker/daterangepicker.js',
					'last:../global/forms/canvastack-form-pickers.js'
				],
				'css'	=> ['vendor/plugins/bootstrap-daterangepicker/daterangepicker.css']
			],
		    
			'time' => [
				'js'	=> [
					'vendor/plugins/bootstrap-timepicker/js/bootstrap-timepicker.js',
					'last:../global/forms/canvastack-form-pickers.js'
				],
				'css'	=> ['vendor/plugins/bootstrap-timepicker/css/timepicker.css']
			],
			
			'highcharts' => [
				'js'  => [
					'vendor/plugins/highcharts/js/highcharts.js',
					'vendor/plugins/highcharts/js/modules/exporting.js'
					
				],
				'css' => [null]
			],
			
			'chartjs' => [
				'js'  => [
					'charts/chartjs/Chart.min.js'
				],
				'css' => [null]
			]
		],

		
		'canvasign' => [
			'position' => [
				'top' => [
					/**
					 * CSS and critical JS loaded in head
					 */
					'js'	=> [
						// jQuery - Load first for compatibility
						'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
						
						// DataTables - Load after jQuery
						'https://cdn.datatables.net/v/bs5/jszip-2.5.0/dt-1.13.4/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/r-2.4.1/datatables.min.js',
						'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js',
						'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js',
						
						// Theme JS (must load before body to prevent FOUC)
						'js/core/theme.js',
						
						// Console filter
						'../global/js/canvastack-console-filter.js',
					],
					'css'	=> [
						'css/canvasign-base.css',  // ✅ BASE: Core + Components (loaded on all pages)
					]
				],
				'bottom'	=> [
					'first'	=> [
						/**
						 * Core libraries and plugins loaded first
						 */
						'js'	=> [
							// Bootstrap 5.3.3 JavaScript Bundle (CDN)
							'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
							
							'js/plugins/bootstrap.js',
							'../default/js/owl.carousel.min.js',
							'../default/js/jquery.slicknav.min.js',
							
							// Core utilities (must load after jQuery)
							'../global/core/canvastack-core-utils.js',
							'../global/core/canvastack-loader.js',
							'../global/core/canvastack-select-handler.js',
							
							// Ajax selection wrapper (must load before inline scripts)
							'../global/js/canvastack-ajax-selection-wrapper.js',
							
							// Mapping page wrapper (must load before inline scripts)
							'../global/js/canvastack-mapping-page-wrapper.js',
						],
						'css'	=> []
					],
					'last'	=> [
						'js'	=> [
							// Core framework
							'js/core/app.js',
							'js/core/canvasign-scripts.js',
							
							// UI Components
							'js/components/canvasign-bs5-patch.js',       // Bootstrap 4→5 attribute patch
							'js/components/canvasign-sidebar.js',
							'js/components/canvasign-menu.js',            // Bootstrap 5 native menu
							
							// Plugin Initialization
							'js/plugins-init/canvasign-plugins.js',       // Flatpickr + Choices.js
							'js/plugins-init/canvasign-fileinput.js',     // File input handler
							'js/plugins-init/canvasign-charts.js',        // ECharts integration
							'js/plugins-init/canvasign-datatables.js',    // DataTables BS5 integration
							
							// Icon Utilities
							'js/utilities/icon-adapter.js',               // FA→BI icon conversion
							
							// Global adapters (used in design)
							'../global/adapters/canvastack-modal-adapter.js',
							'../global/adapters/canvastack-tooltip-adapter.js',
							
							// Global forms (used in design)
							'../global/forms/canvastack-form-validation.js',
							'../global/forms/canvastack-cascading-filter.js',
							
							// Global DataTables (CanvaStack specific)
							'../global/datatables/canvastack-datatables.js',
							'../global/datatables/canvastack-datatables-filters.js',
							'../global/datatables/canvastack-datatables-export.js',
							
							// Global components (CanvaStack specific, not in design)
							'../global/components/canvastack-delete-handler.js',
							'../global/components/canvastack-privilege-table.js',
							'../global/components/canvastack-datatable-checkbox.js',
							
							// Global pages (CanvaStack specific)
							'../global/pages/mapping-page-handlers.js',
							'../global/pages/preference-smtp-test.js',
							'../global/pages/canvastack-cache-manager.js',
							
							// Canvasign-specific adapters (CRITICAL for mapping page)
							'js/adapters/canvasign-mapping-icons-adapter.js',  // FA→BI icons for mapping page
							'js/adapters/canvasign-mapping-data-adapter.js',   // Choices.js for saved data
							'js/adapters/canvasign-filter-adapter.js',         // AJAX filter error handling
						],
						'css'	=> []
					]
				]
			],

			/**
			 * DATATABLES — Bootstrap 5 styling via global vendor (includes FixedColumns)
			 */
			'datatable' => [
				'js'	=> [
					'../global/vendor/DataTables/js/datatables.min.js',
					'../global/vendor/DataTables/js/pdfmake.js',
					'../global/vendor/DataTables/js/vfs_fonts.js',
					'../global/datatables/canvastack-datatables-filters.js',
					'../global/datatables/canvastack-datatables-export.js'
				],
				'css'	=> ['css/plugins-datatables.css']  // ✅ Loaded only on DataTables pages
			],

			'textarea'	=> [
				'js'	=> [],
				'css'	=> []
			],

			'tagsinput' => [
				'js'	=> [],
				'css'	=> []
			],

			'file' => [
				'js'	=> [],
				'css'	=> []
			],

			/**
			 * SELECT — Choices.js for enhanced select elements
			 */
			'select' => [
				'js'	=> ['js/plugins/choices.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			'selectMonth' => [
				'js'	=> ['js/plugins/choices.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			/**
			 * DATE / DATETIME / DATERANGE — Flatpickr
			 */
			'date' => [
				'js'	=> ['js/plugins/flatpickr.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			'datetime'	=> [
				'js'	=> ['js/plugins/flatpickr.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			'daterange' => [
				'js'	=> ['js/plugins/flatpickr.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			'time' => [
				'js'	=> ['js/plugins/flatpickr.js'],
				'css'	=> ['css/plugins-forms.css']  // ✅ Loaded only on pages with select/date inputs
			],

			/**
			 * CHART — Apache ECharts
			 */
			'chart' => [
				'js'  => ['js/plugins/echarts.js'],
				'css' => []
			],

			'highcharts' => [
				'js'  => [],
				'css' => []
			],

			'chartjs' => [
				'js'  => [],
				'css' => []
			]
		],

		
		'canvas' => [
			'position' => [
				'top' => [
					'js'	=> [null],
					'css'	=> [null]
				],
				'bottom'	=> [
					'first'	=> [
						'js'	=> [null],
						'css'	=> [null]
					],
					'last'	=> [
						'js'	=> [null],
						'css'	=> [null]
					]
				]
			],
			
			'datatable' => [
				'js'	=> [null],
				'css'	=> [null]
			],
			
			'textarea'	=> [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'tagsinput' => [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'file' => [
				'js'	=> [null],
				'css'	=> [null]
			],
			
			'select' => [
				'js'	=> [null],
				'css'	=> [null]
			],
			
			'selectMonth' => [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'date' => [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'datetime'	=> [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'daterange' => [
				'js'	=> [null],
				'css'	=> [null]
			],
		    
			'time' => [
				'js'	=> [null],
				'css'	=> [null]
			],
			
			'highcharts' => [
				'js'  => [null],
				'css' => [null]
			],
			
			'chartjs' => [
				'js'  => [null],
				'css' => [null]
			]
		]

	]
];
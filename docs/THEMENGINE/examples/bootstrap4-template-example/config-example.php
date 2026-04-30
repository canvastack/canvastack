<?php
/**
 * config-example.php — Bootstrap 4 Template Configuration Example
 * ================================================================
 * File: config/canvastack.templates.php
 *
 * This is a complete configuration example for the Bootstrap 4 (default) template.
 * Copy the relevant sections into your actual config/canvastack.templates.php file.
 *
 * Configuration structure:
 *   'template'         → active template name
 *   '{template_name}'  → template-specific configuration
 *     'position'       → always-loaded assets (every page)
 *       'top'          → loaded in <head>
 *         'css'        → <link rel="stylesheet"> tags
 *         'js'         → <script> tags in <head>
 *       'bottom'       → loaded before </body>
 *         'first'      → loaded before app scripts
 *         'last'       → loaded after app scripts
 *     'datatable'      → loaded only on DataTables pages
 *     'select'         → loaded only on pages with select enhancement
 *     'date'           → loaded only on pages with date pickers
 *     'datetime'       → loaded only on pages with datetime pickers
 *     'daterange'      → loaded only on pages with date range pickers
 *     'chart'          → loaded only on pages with charts
 *
 * Asset path types:
 *   CDN URL:    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'
 *               → rendered as-is into <link href="..."> or <script src="...">
 *
 *   Local path: 'assets/templates/default/css/app.css'
 *               → resolved via asset() helper
 *               → becomes: https://yourapp.com/assets/templates/default/css/app.css
 *
 *   Null:       [null]
 *               → no output for this position
 */

return [

    // ── Active template ────────────────────────────────────────────────────
    // Change this value to switch the active CSS framework.
    // Options: 'default' (Bootstrap 4), 'canvasign' (Bootstrap 5), 'canvas' (Tailwind)
    // Can also be set via environment variable: CANVASTACK_TEMPLATE=canvasign
    'template' => env('CANVASTACK_TEMPLATE', 'default'),

    // ══════════════════════════════════════════════════════════════════════
    // DEFAULT TEMPLATE — Bootstrap 4
    // ══════════════════════════════════════════════════════════════════════
    'default' => [

        // ── Position-based asset loading ───────────────────────────────────
        'position' => [

            // ── top: loaded in <head> ──────────────────────────────────────
            'top' => [

                // CSS files loaded in <head> as <link rel="stylesheet"> tags.
                // Load order: [0] first, [n] last.
                // Keep this minimal — render-blocking resources slow page load.
                'css' => [
                    // Bootstrap 4 core CSS — version-locked CDN
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',

                    // Font Awesome 4 icons
                    'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css',

                    // Themify icons (custom icon set)
                    'assets/templates/default/vendor/themify-icons/themify-icons.css',

                    // Chosen.js select plugin CSS
                    'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css',

                    // Custom app stylesheet (local — relative to public/)
                    // This is loaded in <head> to prevent flash of unstyled content
                    'assets/templates/default/css/app.css',
                ],

                // JavaScript files loaded in <head> as <script> tags.
                // Only put scripts here that MUST be available before body renders.
                // jQuery and Bootstrap are here because some inline scripts need them.
                'js' => [
                    // jQuery 3.6.0 — required by Bootstrap 4, Chosen.js, DataTables
                    'https://code.jquery.com/jquery-3.6.0.min.js',

                    // Bootstrap 4 bundle (includes Popper.js for dropdowns/tooltips)
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
                ],
            ],

            // ── bottom: loaded before </body> ─────────────────────────────
            'bottom' => [

                // ── bottom.first: loaded before app scripts ────────────────
                // Put plugin libraries here. They must load before canvastackscripts.js
                // which initializes them.
                'first' => [
                    'css' => [null], // No additional CSS at this position

                    'js' => [
                        // Chosen.js jQuery plugin — select enhancement
                        'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js',

                        // Flatpickr — lightweight date/time picker
                        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',

                        // MetisMenu — sidebar accordion navigation
                        'assets/templates/default/js/metisMenu.min.js',

                        // SlimScroll — custom scrollbar for sidebar
                        'assets/templates/default/js/jquery.slimscroll.min.js',

                        // SlickNav — responsive mobile navigation
                        'assets/templates/default/js/jquery.slicknav.min.js',

                        // Owl Carousel — for any carousel components
                        'assets/templates/default/js/owl.carousel.min.js',
                    ],
                ],

                // ── bottom.last: loaded after all other scripts ────────────
                // Put your app initialization scripts here.
                // These run after all plugins are loaded and ready.
                'last' => [
                    'css' => [null],

                    'js' => [
                        // Framework-agnostic modal API
                        // Detects active template and routes to correct modal API:
                        // - Bootstrap 4: $('#id').modal('show')
                        // - Bootstrap 5: bootstrap.Modal.getInstance('#id').show()
                        // - Tailwind: custom classList manipulation
                        'assets/templates/default/js/canvastack-modal-adapter.js',

                        // Framework-agnostic tooltip API
                        // Initializes tooltips based on active framework
                        'assets/templates/default/js/canvastack-tooltip-adapter.js',

                        // Sidebar toggle and collapse logic
                        'assets/templates/default/js/sidebar.js',

                        // General UI scripts (back-to-top, fullscreen, etc.)
                        'assets/templates/default/js/scripts.js',

                        // CanvaStack-specific initialization
                        // Initializes Chosen.js, tooltips, popovers, etc.
                        'assets/templates/default/js/canvastackscripts.js',

                        // First-run scripts (run once on initial page load)
                        'assets/templates/default/js/firscripts.js',
                    ],
                ],
            ],
        ],

        // ── Plugin-specific configurations ────────────────────────────────
        // These are loaded on-demand by specific CanvaStack components.
        // They are NOT loaded on every page — only when the component is used.

        // DataTables — loaded on pages that use the Table component
        'datatable' => [
            'js' => [
                // DataTables core
                'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
                // Bootstrap 4 integration (styling)
                'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js',
                // Responsive extension
                'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
                'https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js',
            ],
            'css' => [
                // Bootstrap 4 DataTables CSS
                'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css',
                'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css',
            ],
        ],

        // Select enhancement — Chosen.js for Bootstrap 4
        // 'plugin' key tells the system which plugin is active
        'select' => [
            'plugin' => 'chosen', // Options: 'chosen', 'choices', 'native'
            'js'  => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
        ],

        // Date picker — Flatpickr (framework-agnostic)
        'date' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Date-time picker — Flatpickr with time enabled
        'datetime' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Date range picker — Flatpickr with range mode
        'daterange' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Chart library — ApexCharts
        'chart' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js'],
            'css' => [null], // ApexCharts has no separate CSS file
        ],
    ],

    // ══════════════════════════════════════════════════════════════════════
    // CANVASIGN TEMPLATE — Bootstrap 5
    // (shown here for reference — see BOOTSTRAP5_TEMPLATE_GUIDE.md)
    // ══════════════════════════════════════════════════════════════════════
    'canvasign' => [
        'position' => [
            'top' => [
                'css' => [
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                    'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css',
                    'assets/templates/canvasign/css/app.css',
                ],
                'js' => [
                    'https://code.jquery.com/jquery-3.6.0.min.js',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                ],
            ],
            'bottom' => [
                'first' => [
                    'css' => [null],
                    'js'  => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
                ],
                'last' => [
                    'css' => [null],
                    'js'  => [
                        'assets/templates/canvasign/js/canvastack-modal-adapter.js',
                        'assets/templates/canvasign/js/canvastack-tooltip-adapter.js',
                        'assets/templates/canvasign/js/scripts.js',
                    ],
                ],
            ],
        ],
        'datatable' => [
            'js'  => ['https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'],
            'css' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css'],
        ],
        'select' => [
            'plugin' => 'choices',
            'js'  => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
        ],
        'date'      => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'datetime'  => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'daterange' => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'chart'     => ['js' => ['https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js'], 'css' => [null]],
    ],

    // ══════════════════════════════════════════════════════════════════════
    // CANVAS TEMPLATE — TailwindCSS
    // (shown here for reference — see TAILWIND_TEMPLATE_GUIDE.md)
    // ══════════════════════════════════════════════════════════════════════
    'canvas' => [
        'position' => [
            'top' => [
                'css' => [null],
                // Tailwind Play CDN — for development only
                // In production, use compiled CSS: 'assets/templates/canvas/css/app.css'
                'js'  => ['https://cdn.tailwindcss.com'],
            ],
            'bottom' => [
                'first' => [
                    'css' => ['assets/templates/canvas/css/app.css'],
                    'js'  => [null],
                ],
                'last' => [
                    'css' => [null],
                    'js'  => [
                        'assets/templates/canvas/js/canvastack-modal-adapter.js',
                        'assets/templates/canvas/js/canvastack-tooltip-adapter.js',
                        'assets/templates/canvas/js/canvastack-class-adapter.js',
                        'assets/templates/canvas/js/scripts.js',
                    ],
                ],
            ],
        ],
        'datatable' => [
            'js'  => ['https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js'],
            'css' => ['https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'],
        ],
        'select'    => ['plugin' => 'native', 'js' => [null], 'css' => [null]],
        'date'      => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'datetime'  => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'daterange' => ['js' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'], 'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css']],
        'chart'     => ['js' => ['https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js'], 'css' => [null]],
    ],

];

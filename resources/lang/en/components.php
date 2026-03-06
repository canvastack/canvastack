<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Component Translation Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by various components throughout
    | the application. You are free to modify these language lines according
    | to your application's requirements.
    |
    */

    'table' => [
        // DataTables specific translations
        'show' => 'Show',
        'filter' => 'Filters',
        'search_placeholder' => 'Search...',
        'empty_state' => 'No data available',
        'yes' => 'Yes',
        'no' => 'No',
        
        // DataTables language object
        'datatables' => [
            'processing' => 'Processing...',
            'empty_table' => 'No data available in table',
            'zero_records' => 'No matching records found',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
            'info_empty' => 'Showing 0 to 0 of 0 entries',
            'info_filtered' => '(filtered from _MAX_ total entries)',
            'search' => 'Search:',
            'length_menu' => 'Show _MENU_ entries',
            'ajax_error' => 'Failed to load data',
            'paginate' => [
                'first' => 'First',
                'last' => 'Last',
                'next' => 'Next',
                'previous' => 'Previous',
            ],
        ],
        
        // Search
        'search' => 'Search...',
        'search_table' => 'Search table',
        'search_in' => 'Search in :column',
        'clear_search' => 'Clear search',

        // Sorting
        'sort_asc' => 'Sort ascending',
        'sort_desc' => 'Sort descending',
        'unsorted' => 'Unsorted',
        'sort_active_singular' => 'sort active',
        'sort_active_plural' => 'sorts active',
        'clear_sorting' => 'Clear sorting',
        'sort_hint' => 'Click to sort. Shift+click for multi-column sort.',

        // Pagination
        'showing' => 'Showing :from to :to of :total entries',
        'first' => 'First',
        'previous' => 'Previous',
        'next' => 'Next',
        'last' => 'Last',
        'page' => 'Page',
        'from' => 'from',
        'page_size' => 'Page size',
        'per_page' => 'Per page',
        'of' => 'of',
        'entries' => 'entries',

        // Filtering
        'filters' => 'Filters',
        'filter_by' => 'Filter by',
        'active_filters' => 'Active filters',
        'clear_filters' => 'Clear filters',
        'clear_filter' => 'Clear filter',
        'clear_all' => 'Clear all',
        'apply_filters' => 'Apply filters',
        'no_filters' => 'No active filters',
        'all' => 'All',
        'select_date_range' => 'Select date range',
        'min' => 'Min',
        'max' => 'Max',

        // Selection
        'select_all' => 'Select all',
        'deselect_all' => 'Deselect all',
        'selected_count' => ':count selected',
        'select_row' => 'Select row',
        'row_selected' => 'row selected',
        'rows_selected' => 'rows selected',
        'clear_selection' => 'Clear selection',

        // Pluralization
        'items_count' => '{0} No items|{1} :count item|[2,*] :count items',
        'rows_count' => '{0} No rows|{1} :count row|[2,*] :count rows',
        'entries_count' => '{0} No entries|{1} :count entry|[2,*] :count entries',
        'selected_items' => '{0} No items selected|{1} :count item selected|[2,*] :count items selected',
        'filters_active' => '{0} No filters active|{1} :count filter active|[2,*] :count filters active',
        'columns_hidden' => '{0} No columns hidden|{1} :count column hidden|[2,*] :count columns hidden',
        'results_found' => '{0} No results found|{1} :count result found|[2,*] :count results found',

        // Actions
        'actions' => 'Actions',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure you want to delete this item?',
        'bulk_actions' => 'Bulk actions',
        'bulk_delete' => 'Delete selected',
        'bulk_delete_confirm' => 'Are you sure you want to delete :count items?',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'confirm_action' => 'Confirm Action',
        'no_rows_selected' => 'No rows selected',
        'bulk_action_error' => 'An error occurred while performing the bulk action',

        // Export
        'export' => 'Export',
        'export_excel' => 'Export to Excel',
        'export_csv' => 'Export to CSV',
        'export_pdf' => 'Export to PDF',
        'print' => 'Print',

        // States
        'loading' => 'Loading...',
        'loading_more' => 'Loading more data...',
        'no_data' => 'No data available',
        'error' => 'An error occurred',
        'retry' => 'Retry',
        'empty_title' => 'No data available',
        'empty_description' => 'There are no records to display',
        'all_data_loaded' => 'All data loaded',

        // Column visibility
        'show_columns' => 'Show/Hide Columns',
        'hide_columns' => 'Hide columns',
        'show_all_columns' => 'Show all columns',
        'hide_all_columns' => 'Hide all columns',

        // Column resizing
        'resize_column' => 'Drag to resize. Double-click to auto-fit.',
        'auto_fit' => 'Auto-fit',

        // Mobile card view
        'collapse' => 'Collapse',
        'expand' => 'Expand',
        'show_less' => 'Show less',
        'show_more' => 'Show more',

        // Misc
        'refresh' => 'Refresh',
        'reset' => 'Reset',
        'items' => 'items',
        'total' => 'Total',
        'rows' => 'rows',

        // JavaScript fallbacks
        'library_not_loaded' => 'Table library not loaded',
        'invalid_configuration' => 'Invalid table configuration',
        'network_error' => 'Network error occurred',
        'timeout_error' => 'Request timeout',
    ],

    'form' => [
        // Common labels
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'status' => 'Status',
        'description' => 'Description',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',

        // Buttons
        'submit' => 'Submit',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'reset' => 'Reset',
        'back' => 'Back',

        // Validation
        'required' => 'This field is required',
        'invalid_email' => 'Invalid email address',
        'invalid_format' => 'Invalid format',
        'min_length' => 'Minimum length is :min characters',
        'max_length' => 'Maximum length is :max characters',
    ],

    'chart' => [
        // Chart types
        'line' => 'Line Chart',
        'bar' => 'Bar Chart',
        'pie' => 'Pie Chart',
        'area' => 'Area Chart',
        'donut' => 'Donut Chart',

        // Common labels
        'loading' => 'Loading chart...',
        'no_data' => 'No data available',
        'error' => 'Failed to load chart',
        'retry' => 'Retry',
    ],

    'locale_switcher' => [
        // UI labels
        'toggle' => 'Switch language',
        'current_locale' => 'Current language',
        'select_locale' => 'Select language',
        'keyboard_hint' => 'Press Alt+L to toggle',

        // Messages
        'switch_success' => 'Language changed to :locale',
        'switch_failed' => 'Failed to change language',
        'invalid_locale' => 'Invalid language selected',
        'locale_not_available' => 'This language is not available',

        // Loading states
        'switching' => 'Switching language...',
        'loading' => 'Loading...',
    ],
];

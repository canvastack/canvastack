<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable audit logging for barcode operations.
    | When enabled, all barcode generation, reset, and CRUD operations
    | will be logged for security and tracking purposes.
    |
    */
    'audit_log' => [
        'enabled' => env('BARCODE_AUDIT_LOG', true),
        'log_generation' => true,  // Log every barcode generation
        'log_reset' => true,       // Log counter resets
        'log_crud' => true,        // Log create/update/delete operations
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard Widget
    |--------------------------------------------------------------------------
    |
    | Configure the barcode sequences dashboard widget.
    | Shows current sequence status on the main dashboard.
    |
    */
    'dashboard_widget' => [
        'enabled' => env('BARCODE_DASHBOARD_WIDGET', true),
        'show_count' => 5,         // Number of sequences to display
        'position' => 'side',      // 'top', 'side', or 'custom'
        'refresh_interval' => 0,   // Auto-refresh interval in seconds (0 = disabled)
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for barcode sequence management.
    |
    */
    'reset_confirmation_text' => 'RESET',  // Text required to confirm reset
    'prevent_duplicate' => true,           // Prevent reset to lower values
    
    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for new barcode sequences.
    |
    */
    'defaults' => [
        'padding' => 10,
        'active' => true,
    ],
];

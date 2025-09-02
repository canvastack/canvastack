<?php

// Global helper stubs used by legacy Datatables logic during tests
// These are intentionally minimal and safe for the testing environment.

if (! function_exists('get_object_called_name')) {
    function get_object_called_name($obj)
    {
        // Treat as builder phase before ->get()
        return 'builder';
    }
}

// Stub for Laravel url() helper to avoid container boot during tests
if (! function_exists('url')) {
    function url($path = null)
    {
        // Basic object with current() method
        return new class($path)
        {
            private ?string $path;

            public function __construct($path)
            {
                $this->path = is_string($path) ? $path : null;
            }

            public function current()
            {
                return '/';
            }

            public function asset($p)
            {
                return '/'.ltrim((string) $p, '/');
            }
        };
    }
}

if (! function_exists('canvastack_current_url')) {
    function canvastack_current_url()
    {
        return '/';
    }
}

if (! function_exists('canvastack_table_action_button')) {
    function canvastack_table_action_button($model, $field_target, $current_url, $actions, $removed)
    {
        return '';
    }
}

if (! function_exists('canvastack_get_table_columns')) {
    function canvastack_get_table_columns($table)
    {
        try {
            return \Illuminate\Support\Facades\Schema::getColumnListing($table) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

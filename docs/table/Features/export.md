# Export Functionality

CanvaStack Table provides comprehensive export capabilities supporting multiple formats including Excel, CSV, PDF, and custom formats. The export system includes filtering, formatting, and customization options.

## Table of Contents

- [Basic Export Setup](#basic-export-setup)
- [Export Formats](#export-formats)
- [Export Configuration](#export-configuration)
- [Custom Export Formatting](#custom-export-formatting)
- [Filtered Exports](#filtered-exports)
- [Large Dataset Exports](#large-dataset-exports)
- [Export Security](#export-security)
- [Advanced Export Features](#advanced-export-features)

## Basic Export Setup

### Enable Basic Export

Enable export functionality with default settings:

```php
public function index()
{
    $this->setPage();

    // Enable export with default formats (Excel, CSV, PDF)
    $this->table->exportable();

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email Address',
        'created_at:Registration Date'
    ]);

    return $this->render();
}
```

### Selective Export Formats

Enable specific export formats only:

```php
public function index()
{
    $this->setPage();

    // Enable only Excel and CSV exports
    $this->table->exportable(['excel', 'csv']);

    $this->table->lists('users', [
        'name',
        'email',
        'phone',
        'created_at'
    ]);

    return $this->render();
}
```

## Export Formats

### Excel Export

Configure Excel export with advanced options:

```php
public function index()
{
    $this->setPage();

    $this->table->setExportConfig([
        'excel' => [
            'enabled' => true,
            'filename' => 'users_export_{date}',
            'sheet_name' => 'Users',
            'include_headers' => true,
            'auto_size_columns' => true,
            'freeze_first_row' => true,
            'styling' => [
                'header_background' => '#4CAF50',
                'header_font_color' => '#FFFFFF',
                'header_font_bold' => true,
                'alternate_row_color' => '#F5F5F5'
            ]
        ]
    ]);

    $this->table->exportable(['excel']);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'department.name:Department',
        'salary:Salary',
        'created_at:Registration Date'
    ]);

    return $this->render();
}
```

### CSV Export

Configure CSV export options:

```php
$this->table->setExportConfig([
    'csv' => [
        'enabled' => true,
        'filename' => 'users_{timestamp}',
        'delimiter' => ',',
        'enclosure' => '"',
        'escape_char' => '\\',
        'include_bom' => true, // For UTF-8 compatibility
        'line_ending' => "\r\n"
    ]
]);
```

### PDF Export

Configure PDF export with custom styling:

```php
$this->table->setExportConfig([
    'pdf' => [
        'enabled' => true,
        'filename' => 'users_report_{date}',
        'orientation' => 'landscape', // portrait or landscape
        'paper_size' => 'A4',
        'title' => 'Users Report',
        'subtitle' => 'Generated on {date}',
        'header' => [
            'show' => true,
            'content' => 'Company Name - Users Report'
        ],
        'footer' => [
            'show' => true,
            'content' => 'Page {page} of {total_pages}'
        ],
        'styling' => [
            'font_family' => 'Arial',
            'font_size' => 10,
            'header_font_size' => 12,
            'title_font_size' => 16
        ]
    ]
]);
```

### Custom Export Formats

Add custom export formats:

```php
$this->table->addCustomExportFormat('json', [
    'label' => 'JSON Export',
    'icon' => 'fas fa-code',
    'handler' => function($data, $columns, $config) {
        $filename = 'users_' . date('Y-m-d_H-i-s') . '.json';
        
        $exportData = [];
        foreach ($data as $row) {
            $exportRow = [];
            foreach ($columns as $column => $label) {
                $exportRow[$label] = $row->{$column};
            }
            $exportData[] = $exportRow;
        }
        
        return response()->json($exportData)
                         ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
]);

$this->table->addCustomExportFormat('xml', [
    'label' => 'XML Export',
    'icon' => 'fas fa-file-code',
    'handler' => 'App\Exports\XmlExportHandler'
]);
```

## Export Configuration

### Global Export Settings

Configure global export behavior:

```php
$this->table->setGlobalExportConfig([
    'max_records' => 10000,
    'chunk_size' => 1000,
    'timeout' => 300, // seconds
    'memory_limit' => '512M',
    'include_timestamps' => true,
    'date_format' => 'Y-m-d H:i:s',
    'number_format' => [
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ','
    ]
]);
```

### Column-Specific Export Settings

Configure export behavior for specific columns:

```php
$this->table->setColumnExportConfig([
    'id' => [
        'exportable' => false // Don't include ID in exports
    ],
    'avatar' => [
        'exportable' => false // Don't export image columns
    ],
    'salary' => [
        'format' => function($value) {
            return '$' . number_format($value, 2);
        },
        'excel_format' => '$#,##0.00'
    ],
    'created_at' => [
        'format' => 'd/m/Y H:i',
        'label' => 'Registration Date'
    ],
    'status' => [
        'format' => function($value) {
            return ucfirst($value);
        }
    ]
]);
```

### Export Button Customization

Customize export button appearance and behavior:

```php
$this->table->setExportButtons([
    'position' => 'top-right', // top-left, top-right, bottom-left, bottom-right
    'style' => 'dropdown', // dropdown, buttons, tabs
    'class' => 'btn-group',
    'button_class' => 'btn btn-outline-primary btn-sm',
    'dropdown_class' => 'dropdown-menu-right',
    'icons' => [
        'excel' => 'fas fa-file-excel text-success',
        'csv' => 'fas fa-file-csv text-info',
        'pdf' => 'fas fa-file-pdf text-danger',
        'json' => 'fas fa-code text-warning'
    ],
    'labels' => [
        'excel' => 'Export to Excel',
        'csv' => 'Export to CSV',
        'pdf' => 'Export to PDF',
        'json' => 'Export as JSON'
    ]
]);
```

## Custom Export Formatting

### Data Transformation

Transform data before export:

```php
$this->table->setExportDataTransformer(function($data, $format) {
    return $data->map(function($row) use ($format) {
        // Add computed fields
        $row->full_name = $row->first_name . ' ' . $row->last_name;
        $row->age = $row->birth_date ? $row->birth_date->age : 'N/A';
        
        // Format specific fields based on export format
        if ($format === 'excel') {
            $row->salary = floatval($row->salary);
        } elseif ($format === 'csv') {
            $row->salary = '$' . number_format($row->salary, 2);
        }
        
        // Remove sensitive data
        unset($row->password, $row->remember_token);
        
        return $row;
    });
});
```

### Format-Specific Transformers

Apply different transformations for different formats:

```php
$this->table->setFormatSpecificTransformers([
    'excel' => function($data) {
        return $data->map(function($row) {
            // Excel-specific formatting
            $row->created_at = $row->created_at->format('Y-m-d H:i:s');
            $row->salary = floatval($row->salary);
            return $row;
        });
    },
    'pdf' => function($data) {
        return $data->map(function($row) {
            // PDF-specific formatting (shorter text)
            $row->description = Str::limit($row->description, 100);
            $row->created_at = $row->created_at->format('d/m/Y');
            return $row;
        });
    },
    'csv' => function($data) {
        return $data->map(function($row) {
            // CSV-specific formatting (escape special characters)
            $row->description = str_replace(['"', ',', "\n"], ['""', ';', ' '], $row->description);
            return $row;
        });
    }
]);
```

### Custom Column Formatters

Define custom formatters for specific columns:

```php
$this->table->setExportColumnFormatters([
    'status' => [
        'excel' => function($value) {
            return ucfirst($value);
        },
        'pdf' => function($value) {
            $badges = [
                'active' => '✓ Active',
                'inactive' => '✗ Inactive',
                'pending' => '⏳ Pending'
            ];
            return $badges[$value] ?? $value;
        }
    ],
    'created_at' => [
        'excel' => function($value) {
            return $value->format('Y-m-d H:i:s');
        },
        'csv' => function($value) {
            return $value->format('d/m/Y H:i');
        },
        'pdf' => function($value) {
            return $value->format('M j, Y');
        }
    ]
]);
```

## Filtered Exports

### Export Current Filters

Export data with currently applied filters:

```php
public function index()
{
    $this->setPage();

    $this->table->filterGroups('status', 'selectbox', true)
                ->filterGroups('department', 'selectbox', true)
                ->filterGroups('created_at', 'daterange', true);

    // Export respects current filters
    $this->table->setExportConfig([
        'respect_filters' => true,
        'include_filter_info' => true, // Add filter info to export
        'filter_info_position' => 'top' // top, bottom
    ]);

    $this->table->exportable();

    $this->table->lists('users', [
        'name',
        'email',
        'status',
        'department.name:Department',
        'created_at'
    ]);

    return $this->render();
}
```

### Custom Export Filters

Apply additional filters specifically for exports:

```php
$this->table->setExportFilters([
    'exclude_test_data' => function($query) {
        return $query->where('is_test', false);
    },
    'active_only' => function($query) {
        return $query->where('active', true);
    },
    'recent_data' => function($query) {
        return $query->where('created_at', '>=', now()->subMonths(6));
    }
]);
```

### Export with Custom Queries

Use different queries for exports:

```php
$this->table->setExportQuery(function($baseQuery, $format) {
    // Add additional data for exports
    $query = $baseQuery->with(['department', 'role', 'profile']);
    
    // Format-specific query modifications
    if ($format === 'pdf') {
        // Limit data for PDF to prevent large files
        $query->limit(1000);
    }
    
    if ($format === 'excel') {
        // Include additional calculated fields for Excel
        $query->selectRaw('*, DATEDIFF(CURDATE(), created_at) as days_since_registration');
    }
    
    return $query;
});
```

## Large Dataset Exports

### Chunked Processing

Handle large datasets efficiently:

```php
$this->table->setLargeDatasetExport([
    'enabled' => true,
    'chunk_size' => 1000,
    'max_execution_time' => 300,
    'memory_limit' => '1G',
    'progress_callback' => function($processed, $total) {
        // Update progress indicator
        cache()->put('export_progress_' . auth()->id(), [
            'processed' => $processed,
            'total' => $total,
            'percentage' => round(($processed / $total) * 100, 2)
        ], 300);
    }
]);
```

### Background Export Processing

Process large exports in background jobs:

```php
$this->table->setBackgroundExport([
    'enabled' => true,
    'queue' => 'exports',
    'job_class' => 'App\Jobs\TableExportJob',
    'notification_email' => true,
    'download_link_expiry' => 24 // hours
]);

// Custom export job
class TableExportJob implements ShouldQueue
{
    public function handle()
    {
        // Process export in background
        $exporter = new TableExporter($this->config);
        $filePath = $exporter->export();
        
        // Notify user when complete
        $this->user->notify(new ExportCompleteNotification($filePath));
    }
}
```

### Streaming Exports

Stream large exports directly to browser:

```php
$this->table->setStreamingExport([
    'enabled' => true,
    'formats' => ['csv', 'excel'],
    'buffer_size' => 1024,
    'flush_threshold' => 100 // rows
]);
```

## Export Security

### Access Control

Control who can export data:

```php
$this->table->setExportSecurity([
    'permission' => 'export-users',
    'roles' => ['admin', 'manager'],
    'custom_check' => function($user) {
        return $user->department_id === auth()->user()->department_id;
    }
]);
```

### Data Sanitization

Sanitize sensitive data in exports:

```php
$this->table->setExportSanitization([
    'remove_columns' => ['password', 'remember_token', 'api_token'],
    'mask_columns' => [
        'ssn' => function($value) {
            return 'XXX-XX-' . substr($value, -4);
        },
        'credit_card' => function($value) {
            return '**** **** **** ' . substr($value, -4);
        }
    ],
    'encrypt_columns' => ['salary', 'bonus'],
    'audit_exports' => true
]);
```

### Export Logging

Log all export activities:

```php
$this->table->setExportLogging([
    'enabled' => true,
    'log_channel' => 'exports',
    'include_user_info' => true,
    'include_filters' => true,
    'include_record_count' => true,
    'retention_days' => 90
]);
```

## Advanced Export Features

### Multi-Sheet Excel Exports

Create Excel files with multiple sheets:

```php
$this->table->setMultiSheetExport([
    'users' => [
        'title' => 'Active Users',
        'query' => User::where('active', true),
        'columns' => ['name', 'email', 'created_at']
    ],
    'inactive_users' => [
        'title' => 'Inactive Users',
        'query' => User::where('active', false),
        'columns' => ['name', 'email', 'deactivated_at']
    ],
    'summary' => [
        'title' => 'Summary',
        'data' => [
            ['Metric', 'Value'],
            ['Total Users', User::count()],
            ['Active Users', User::where('active', true)->count()],
            ['Inactive Users', User::where('active', false)->count()]
        ]
    ]
]);
```

### Export Templates

Use predefined export templates:

```php
$this->table->setExportTemplates([
    'basic_report' => [
        'name' => 'Basic User Report',
        'columns' => ['name', 'email', 'created_at'],
        'formats' => ['excel', 'pdf'],
        'styling' => 'corporate'
    ],
    'detailed_report' => [
        'name' => 'Detailed User Report',
        'columns' => ['name', 'email', 'department', 'salary', 'created_at'],
        'formats' => ['excel'],
        'include_charts' => true,
        'styling' => 'detailed'
    ],
    'compliance_report' => [
        'name' => 'Compliance Report',
        'columns' => ['name', 'email', 'last_login', 'status'],
        'formats' => ['pdf'],
        'watermark' => 'CONFIDENTIAL',
        'password_protect' => true
    ]
]);
```

### Export Scheduling

Schedule automatic exports:

```php
$this->table->setScheduledExports([
    'daily_report' => [
        'schedule' => 'daily',
        'time' => '08:00',
        'format' => 'excel',
        'recipients' => ['admin@company.com', 'manager@company.com'],
        'filters' => ['active' => true],
        'filename' => 'daily_users_report_{date}'
    ],
    'weekly_summary' => [
        'schedule' => 'weekly',
        'day' => 'monday',
        'time' => '09:00',
        'format' => 'pdf',
        'template' => 'weekly_summary'
    ]
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic export setup
- [Security Features](../advanced/security.md) - Export security considerations
- [Performance Optimization](../advanced/performance.md) - Optimizing export performance
- [API Reference](../api/objects.md) - Complete export method documentation
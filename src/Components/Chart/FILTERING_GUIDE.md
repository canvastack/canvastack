# Chart Filtering Feature - Complete Guide

## Overview

Chart Component sekarang mendukung filtering data dengan form inputs yang terintegrasi dengan AJAX data loading.

---

## Pertanyaan & Jawaban

### Q1: Tanpa `->ajax()`, apakah chart bisa ditampilkan?

**✅ YA, chart tetap bisa ditampilkan dengan data statis:**

```php
// Static Data - TANPA AJAX
$chart = new ChartBuilder();
$chart->line(
    [['name' => 'Sales', 'data' => [30, 40, 35, 50]]],
    ['Jan', 'Feb', 'Mar', 'Apr']
)->render();

// ✅ Chart langsung render
// ❌ Tidak ada HTTP request
// ❌ Tidak ada method (GET/POST)
```

### Q2: Method default apa?

| Scenario | Method | Keterangan |
|----------|--------|------------|
| **Tanpa `->ajax()`** | Tidak ada | Static rendering, no HTTP |
| **Dengan `->ajax()`** | **POST** | AJAX rendering, default POST |

```php
// Static mode - No HTTP method
$chart->line($data, $labels)->render();

// AJAX mode - POST by default
$chart->ajax('/api/charts/data')->render(); // POST
$chart->ajax('/api/charts/data', 'GET')->render(); // GET
```

### Q3: Apakah ada fitur filtering?

**✅ YA, sudah ada dan fully functional!**

---

## Filter Types

### 1. Select Dropdown

```php
$chart->addFilter('category', 'select', [
    'label' => 'Category',
    'options' => [
        'electronics' => 'Electronics',
        'clothing' => 'Clothing',
        'food' => 'Food & Beverage'
    ]
]);
```

### 2. Date Picker

```php
$chart->addFilter('date', 'date', [
    'label' => 'Date',
    'value' => date('Y-m-d')
]);
```

### 3. Date Range

```php
$chart->addFilter('period', 'daterange', [
    'label' => 'Period'
]);
// Creates: period_start and period_end fields
```

### 4. Text Input

```php
$chart->addFilter('search', 'text', [
    'label' => 'Search',
    'placeholder' => 'Enter keyword...'
]);
```

---

## Usage Examples

### Example 1: Single Filter

```php
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

$chart = new ChartBuilder();
$chart->line([], [])
    ->ajax('/api/charts/sales', 'POST')
    ->addFilter('category', 'select', [
        'label' => 'Category',
        'options' => [
            'all' => 'All Categories',
            'electronics' => 'Electronics',
            'clothing' => 'Clothing'
        ]
    ])
    ->render();
```

### Example 2: Multiple Filters

```php
$chart = new ChartBuilder();
$chart->bar([], [])
    ->ajax('/api/charts/sales', 'POST')
    ->addFilter('category', 'select', [
        'options' => [
            'electronics' => 'Electronics',
            'clothing' => 'Clothing'
        ]
    ])
    ->addFilter('period', 'daterange', [
        'label' => 'Date Range'
    ])
    ->addFilter('region', 'select', [
        'options' => [
            'asia' => 'Asia',
            'europe' => 'Europe',
            'america' => 'America'
        ]
    ])
    ->render();
```

### Example 3: Using withFilters() Method

```php
$chart = new ChartBuilder();
$chart->line([], [])
    ->ajax('/api/charts/sales', 'POST')
    ->withFilters([
        'category' => [
            'type' => 'select',
            'label' => 'Category',
            'options' => [
                'electronics' => 'Electronics',
                'clothing' => 'Clothing'
            ]
        ],
        'start_date' => [
            'type' => 'date',
            'label' => 'Start Date',
            'value' => date('Y-m-01') // First day of month
        ],
        'end_date' => [
            'type' => 'date',
            'label' => 'End Date',
            'value' => date('Y-m-d') // Today
        ]
    ])
    ->render();
```

### Example 4: Complete Configuration

```php
$chart = new ChartBuilder();
$chart->line([], [])
    ->ajax('/api/charts/sales-data', 'POST')
    ->ajaxParams([
        'user_id' => auth()->id() // Base params
    ])
    ->addFilter('category', 'select', [
        'label' => 'Product Category',
        'options' => [
            '' => 'All Categories',
            'electronics' => 'Electronics',
            'clothing' => 'Clothing',
            'food' => 'Food & Beverage'
        ]
    ])
    ->addFilter('period', 'daterange', [
        'label' => 'Sales Period'
    ])
    ->addFilter('status', 'select', [
        'label' => 'Status',
        'options' => [
            '' => 'All Status',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled'
        ]
    ])
    ->realtime(300) // Auto-refresh every 5 minutes
    ->height(450)
    ->render();
```

---

## Laravel Controller Example

### Handling Filter Parameters

```php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Sale;
use Carbon\Carbon;

class ChartController extends Controller
{
    public function salesData(Request $request): JsonResponse
    {
        // Validate filters
        $validated = $request->validate([
            'category' => 'nullable|string',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'status' => 'nullable|string',
            'region' => 'nullable|string',
        ]);

        // Build query with filters
        $query = Sale::query();

        // Apply category filter
        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        // Apply date range filter
        if (!empty($validated['period_start']) && !empty($validated['period_end'])) {
            $query->whereBetween('date', [
                $validated['period_start'],
                $validated['period_end']
            ]);
        }

        // Apply status filter
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Apply region filter
        if (!empty($validated['region'])) {
            $query->where('region', $validated['region']);
        }

        // Get aggregated data
        $sales = $query
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Return chart format
        return response()->json([
            'series' => [
                [
                    'name' => 'Sales',
                    'data' => $sales->pluck('total')->toArray()
                ]
            ],
            'labels' => $sales->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray()
        ]);
    }
}
```

### With Caching

```php
public function salesData(Request $request): JsonResponse
{
    $validated = $request->validate([
        'category' => 'nullable|string',
        'period_start' => 'nullable|date',
        'period_end' => 'nullable|date',
    ]);

    // Create cache key from filters
    $cacheKey = 'chart.sales.' . md5(json_encode($validated));

    // Cache for 5 minutes
    $data = Cache::remember($cacheKey, 300, function () use ($validated) {
        $query = Sale::query();

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (!empty($validated['period_start']) && !empty($validated['period_end'])) {
            $query->whereBetween('date', [
                $validated['period_start'],
                $validated['period_end']
            ]);
        }

        $sales = $query
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'series' => [
                ['name' => 'Sales', 'data' => $sales->pluck('total')->toArray()]
            ],
            'labels' => $sales->pluck('date')->toArray()
        ];
    });

    return response()->json($data);
}
```

---

## Filter Form UI

The filter form is automatically rendered with Tailwind CSS + DaisyUI styling:

```html
<!-- Auto-generated filter form -->
<div class="bg-white dark:bg-gray-900 rounded-2xl border p-6 mb-4">
    <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Filter fields here -->
        <div>
            <label class="block text-sm font-medium mb-2">Category</label>
            <select name="category" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl">
                <option value="">All</option>
                <option value="electronics">Electronics</option>
            </select>
        </div>
        
        <!-- Filter buttons -->
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl">
                Filter
            </button>
            <button type="button" onclick="resetChartFilters('chart-id')" class="px-4 py-2.5 bg-gray-100 rounded-xl">
                Reset
            </button>
        </div>
    </form>
</div>
```

---

## JavaScript API

### Manual Filter Control

```javascript
// Get chart instance
const chart = window.charts['chart-id'];

// Set filters programmatically
chart.filters = {
    category: 'electronics',
    period_start: '2024-01-01',
    period_end: '2024-12-31'
};

// Reload with new filters
chart.loadData();

// Reset filters
chart.filters = {};
chart.loadData();

// Or use global function
resetChartFilters('chart-id');
```

### Listen to Filter Events

```javascript
// Listen to form submit
document.getElementById('filter-chart-id-form').addEventListener('submit', function(e) {
    console.log('Filters applied');
});

// Listen to chart data loaded
document.addEventListener('chart:loaded', function(e) {
    console.log('Chart loaded with filters:', e.detail.data);
});
```

---

## Complete Working Example

### View (Blade)

```blade
@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Sales Dashboard</h1>
    
    {!! $salesChart !!}
</div>
@endsection
```

### Controller

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

class DashboardController extends Controller
{
    public function index()
    {
        $salesChart = (new ChartBuilder())
            ->line([], [])
            ->ajax(route('api.charts.sales'), 'POST')
            ->addFilter('category', 'select', [
                'label' => 'Product Category',
                'options' => [
                    '' => 'All Categories',
                    'electronics' => 'Electronics',
                    'clothing' => 'Clothing',
                    'food' => 'Food & Beverage'
                ]
            ])
            ->addFilter('period', 'daterange', [
                'label' => 'Sales Period'
            ])
            ->addFilter('status', 'select', [
                'label' => 'Order Status',
                'options' => [
                    '' => 'All Status',
                    'completed' => 'Completed',
                    'pending' => 'Pending'
                ]
            ])
            ->realtime(300)
            ->height(450)
            ->options([
                'title' => [
                    'text' => 'Sales Overview',
                    'align' => 'left'
                ]
            ])
            ->render();

        return view('admin.dashboard', compact('salesChart'));
    }
}
```

### API Controller

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Sale;

class ChartController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        $query = Sale::query();

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (!empty($validated['period_start']) && !empty($validated['period_end'])) {
            $query->whereBetween('date', [
                $validated['period_start'],
                $validated['period_end']
            ]);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $sales = $query
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'series' => [
                [
                    'name' => 'Sales',
                    'data' => $sales->pluck('total')->toArray()
                ]
            ],
            'labels' => $sales->pluck('date')->toArray()
        ]);
    }
}
```

### Routes

```php
// routes/web.php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('charts')->group(function () {
    Route::post('/sales', [ChartController::class, 'sales'])->name('api.charts.sales');
});
```

---

## API Reference

### Filter Methods

| Method | Parameters | Description |
|--------|------------|-------------|
| `addFilter($name, $type, $options)` | name, type, options | Add single filter |
| `withFilters($filters, $containerId)` | filters array, container ID | Add multiple filters |
| `getFilters()` | - | Get all filters |
| `hasFilters()` | - | Check if filters enabled |
| `getFilterContainerId()` | - | Get filter container ID |

### Filter Types

| Type | Description | Fields Created |
|------|-------------|----------------|
| `select` | Dropdown select | Single field |
| `date` | Date picker | Single date field |
| `daterange` | Date range picker | `{name}_start` and `{name}_end` |
| `text` | Text input | Single text field |

### Filter Options

| Option | Type | Description | Default |
|--------|------|-------------|---------|
| `label` | string | Field label | Ucfirst(name) |
| `placeholder` | string | Placeholder text | '' |
| `value` | mixed | Default value | '' |
| `options` | array | Options for select | [] |

---

## Summary

### ✅ Fitur yang Tersedia

1. **Static Data Rendering** - Chart tanpa AJAX
2. **AJAX Data Loading** - Dynamic data dari API
3. **HTTP Methods** - GET, POST (default), PUT, PATCH, DELETE
4. **Data Filtering** - Form filters dengan multiple types
5. **Real-time Updates** - Auto-refresh dengan filters
6. **Filter Types** - Select, Date, Date Range, Text
7. **Auto-generated UI** - Tailwind CSS + DaisyUI styling
8. **JavaScript API** - Manual control via JS
9. **Event System** - Filter and load events

### 🎯 Key Points

- **Tanpa `->ajax()`** = Static rendering, no HTTP, no filters needed
- **Dengan `->ajax()`** = AJAX rendering, POST default, filters optional
- **Filters** = Automatically sent with AJAX requests
- **UI** = Auto-generated, responsive, dark mode support
- **Performance** = Cache-friendly, optimized queries

---

**Version**: 1.0.0  
**Status**: ✅ Fully Implemented  
**Last Updated**: 2026-02-26

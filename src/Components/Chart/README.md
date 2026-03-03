# Chart Component

Modern chart component with performance optimization for CanvaStack.

## Features

- **Multiple Chart Types**: Line, Bar, Pie, Donut, Area, Radar, and more
- **Performance Optimized**: Data caching, optimized transformations (< 100ms target)
- **Dual Rendering**: Admin and Public contexts with different styling
- **Dark Mode Support**: Automatic theme switching
- **Responsive Design**: Mobile-first approach
- **ApexCharts Integration**: Professional-grade charting library
- **Fluent API**: Modern, chainable interface

## Installation

The Chart component is included in the CanvaStack package. No additional installation required.

## Basic Usage

### Line Chart

```php
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

$chart = new ChartBuilder();
$chart->line(
    [
        ['name' => 'Sales', 'data' => [30, 40, 35, 50, 49, 60, 70]],
        ['name' => 'Revenue', 'data' => [20, 30, 25, 40, 39, 50, 60]]
    ],
    ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
);

echo $chart->render();
```

### Bar Chart

```php
$chart = new ChartBuilder();
$chart->bar(
    [
        ['name' => 'Product A', 'data' => [44, 55, 41, 67, 22, 43]],
        ['name' => 'Product B', 'data' => [13, 23, 20, 8, 13, 27]]
    ],
    ['2018', '2019', '2020', '2021', '2022', '2023']
);

echo $chart->render();
```

### Pie Chart

```php
$chart = new ChartBuilder();
$chart->pie(
    [44, 55, 13, 43, 22],
    ['Team A', 'Team B', 'Team C', 'Team D', 'Team E']
);

echo $chart->render();
```

### Donut Chart

```php
$chart = new ChartBuilder();
$chart->donut(
    [44, 55, 41, 17, 15],
    ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other']
);

echo $chart->render();
```

### Area Chart

```php
$chart = new ChartBuilder();
$chart->area(
    [
        ['name' => 'Series 1', 'data' => [31, 40, 28, 51, 42, 109, 100]],
        ['name' => 'Series 2', 'data' => [11, 32, 45, 32, 34, 52, 41]]
    ],
    ['2018', '2019', '2020', '2021', '2022', '2023', '2024']
);

echo $chart->render();
```

## Advanced Usage

### Fluent Interface

```php
$chart = new ChartBuilder();
$chart->type('line')
    ->series([
        ['name' => 'Sales', 'data' => [30, 40, 35, 50, 49, 60, 70]]
    ])
    ->labels(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'])
    ->height(400)
    ->colors(['#6366f1', '#8b5cf6', '#a855f7'])
    ->options([
        'stroke' => ['curve' => 'smooth'],
        'title' => ['text' => 'Monthly Sales']
    ])
    ->render();
```

### Data Caching

```php
// Cache chart data for 60 minutes
$chart = new ChartBuilder();
$chart->line($series, $labels)
    ->cache(60)
    ->render();

// Custom cache key
$chart->cache(60, 'sales-chart-2024')
    ->render();

// Clear cache
$chart->clearCache();
```

### Context Switching

```php
// Admin context (default)
$chart = new ChartBuilder();
$chart->setContext('admin');

// Public context
$chart->setContext('public');
```

### Custom Dimensions

```php
$chart = new ChartBuilder();
$chart->line($series, $labels)
    ->height(500)
    ->width(800)
    ->render();
```

### Custom Colors

```php
$chart = new ChartBuilder();
$chart->colors(['#ff0000', '#00ff00', '#0000ff'])
    ->pie($data, $labels)
    ->render();
```

### Custom Options

```php
$chart = new ChartBuilder();
$chart->line($series, $labels)
    ->options([
        'stroke' => [
            'curve' => 'smooth',
            'width' => 3
        ],
        'markers' => [
            'size' => 5
        ],
        'title' => [
            'text' => 'Sales Overview',
            'align' => 'center'
        ],
        'xaxis' => [
            'title' => ['text' => 'Month']
        ],
        'yaxis' => [
            'title' => ['text' => 'Amount']
        ]
    ])
    ->render();
```

## Data Transformation

The Chart component includes a powerful DataTransformer for converting various data formats:

### From Eloquent Collection

```php
use Canvastack\Canvastack\Components\Chart\DataTransformer;

$users = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();

$transformer = new DataTransformer();
$data = $transformer->aggregate($users, 'date', 'count', 'sum');

$chart = new ChartBuilder();
$chart->line([['name' => 'Users', 'data' => array_values($data)]], array_keys($data))
    ->render();
```

### Time Series Data

```php
$transformer = new DataTransformer();
$timeSeries = $transformer->toTimeSeries($data, 'timestamp', 'value');

$chart = new ChartBuilder();
$chart->line([['name' => 'Time Series', 'data' => $timeSeries]])
    ->render();
```

### Aggregation

```php
$transformer = new DataTransformer();

// Sum aggregation
$aggregated = $transformer->aggregate($data, 'category', 'amount', 'sum');

// Average aggregation
$aggregated = $transformer->aggregate($data, 'category', 'amount', 'avg');

// Count aggregation
$aggregated = $transformer->aggregate($data, 'category', 'amount', 'count');
```

## Caching

The Chart component includes a dedicated caching layer:

```php
use Canvastack\Canvastack\Components\Chart\ChartCache;

$cache = new ChartCache();

// Put data in cache
$cache->put('my-chart', $data, 60);

// Get data from cache
$data = $cache->get('my-chart');

// Remember (get or put)
$data = $cache->remember('my-chart', 60, function() {
    return expensiveDataQuery();
});

// Forget cache
$cache->forget('my-chart');

// Flush all chart caches
$cache->flush();
```

## Performance

The Chart component is optimized for performance:

- **Data Transformation**: < 100ms for typical datasets
- **Caching**: Redis-backed with file fallback
- **Lazy Loading**: Assets loaded on-demand
- **Optimized Rendering**: Minimal DOM operations

## Dark Mode

Charts automatically adapt to dark mode:

```javascript
// Dark mode is detected automatically
// Charts update when theme changes
```

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Dependencies

- ApexCharts 3.45.0+
- Laravel 12.x
- PHP 8.2+

## Examples

See the `tests/` directory for more examples and usage patterns.

## API Reference

### ChartBuilder Methods

- `type(string $type)` - Set chart type
- `data(array $data)` - Set raw data
- `series(array $series)` - Set series data
- `labels(array $labels)` - Set labels
- `options(array $options)` - Set ApexCharts options
- `height(int $height)` - Set height in pixels
- `width(int $width)` - Set width in pixels
- `colors(array $colors)` - Set color palette
- `cache(int $minutes, ?string $key)` - Enable caching
- `setContext(string $context)` - Set rendering context
- `render()` - Render chart HTML

### Convenience Methods

- `line(array $series, array $labels, array $options)` - Create line chart
- `bar(array $series, array $labels, array $options)` - Create bar chart
- `pie(array $data, array $labels, array $options)` - Create pie chart
- `donut(array $data, array $labels, array $options)` - Create donut chart
- `area(array $series, array $labels, array $options)` - Create area chart

## License

Part of the CanvaStack package. See main package LICENSE for details.


## AJAX Data Loading

The Chart component supports dynamic data loading via AJAX requests with configurable HTTP methods.

### Basic AJAX Usage

```php
$chart = new ChartBuilder();
$chart->line([], []) // Empty initial data
    ->ajax('/api/charts/sales-data', 'POST') // URL and HTTP method
    ->render();
```

### HTTP Methods

Supported HTTP methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`

```php
// GET request
$chart->ajax('/api/charts/data', 'GET');

// POST request (default)
$chart->ajax('/api/charts/data', 'POST');

// Or use setMethod() separately
$chart->ajax('/api/charts/data')
    ->setMethod('POST');
```

### AJAX Parameters

Send additional parameters with the request:

```php
$chart->ajax('/api/charts/sales', 'POST')
    ->ajaxParams([
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'category' => 'electronics'
    ]);
```

### Auto-Load Control

By default, charts auto-load data on page load. You can disable this:

```php
$chart->ajax('/api/charts/data', 'POST')
    ->autoLoad(false); // Disable auto-load

// Manually trigger load via JavaScript:
// window.charts['chart-id'].loadData();
```

### Real-Time Updates

Enable automatic data refresh at intervals:

```php
$chart->ajax('/api/charts/live-data', 'POST')
    ->realtime(30); // Update every 30 seconds
```

### Complete AJAX Example

```php
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

$chart = new ChartBuilder();
$chart->line([], []) // Empty initial data
    ->ajax('/api/charts/sales-data', 'POST')
    ->ajaxParams([
        'period' => 'monthly',
        'year' => 2024
    ])
    ->realtime(60) // Update every minute
    ->height(400)
    ->colors(['#6366f1', '#8b5cf6'])
    ->render();
```

### API Response Format

Your API endpoint should return JSON in this format:

```json
{
  "series": [
    {
      "name": "Sales",
      "data": [30, 40, 35, 50, 49, 60, 70]
    },
    {
      "name": "Revenue",
      "data": [20, 30, 25, 40, 39, 50, 60]
    }
  ],
  "labels": ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"]
}
```

Or for pie/donut charts:

```json
{
  "series": [44, 55, 13, 43, 22],
  "labels": ["Team A", "Team B", "Team C", "Team D", "Team E"]
}
```

### Laravel Controller Example

```php
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChartController extends Controller
{
    public function salesData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'category' => 'nullable|string'
        ]);

        // Fetch data from database
        $sales = Sale::whereBetween('date', [$validated['start_date'], $validated['end_date']])
            ->when($validated['category'] ?? null, function($query, $category) {
                $query->where('category', $category);
            })
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        // Transform to chart format
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

### JavaScript Events

Listen to chart events:

```javascript
// Chart loaded successfully
document.addEventListener('chart:loaded', function(e) {
    console.log('Chart loaded:', e.detail.chartId, e.detail.data);
});

// Chart loading error
document.addEventListener('chart:error', function(e) {
    console.error('Chart error:', e.detail.chartId, e.detail.error);
});
```

### Manual Data Loading

Trigger data load manually via JavaScript:

```javascript
// Load data for specific chart
window.charts['chart-id'].loadData();

// Stop real-time updates
clearInterval(window.charts['chart-id'].intervalId);
```

### Security Considerations

1. **CSRF Protection**: The component automatically includes CSRF token from meta tag
2. **Authentication**: Protect your API endpoints with Laravel middleware
3. **Validation**: Always validate incoming parameters
4. **Rate Limiting**: Apply rate limiting to prevent abuse

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/charts/sales-data', [ChartController::class, 'salesData']);
});
```

### Performance Tips

1. **Cache API responses** for frequently accessed data
2. **Use pagination** for large datasets
3. **Implement debouncing** for real-time updates
4. **Optimize database queries** with proper indexing

### Complete Working Example

**Controller:**
```php
namespace App\Http\Controllers\Admin;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function chartData(Request $request): JsonResponse
    {
        $cacheKey = 'chart.sales.' . md5(json_encode($request->all()));

        $data = Cache::remember($cacheKey, 300, function () use ($request) {
            $sales = Sale::selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->whereBetween('created_at', [now()->subDays(30), now()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'series' => [
                    [
                        'name' => 'Sales',
                        'data' => $sales->pluck('total')->toArray()
                    ]
                ],
                'labels' => $sales->pluck('date')->map(fn($d) => 
                    \Carbon\Carbon::parse($d)->format('M d')
                )->toArray()
            ];
        });

        return response()->json($data);
    }
}
```

**View (Blade):**
```blade
@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Sales Dashboard</h1>
    
    {!! $salesChart !!}
</div>
@endsection
```

**Controller Method:**
```php
public function index()
{
    $salesChart = (new ChartBuilder())
        ->line([], [])
        ->ajax(route('admin.chart.sales'), 'POST')
        ->ajaxParams([
            'period' => 'last_30_days'
        ])
        ->realtime(300) // Update every 5 minutes
        ->height(400)
        ->options([
            'title' => [
                'text' => 'Sales Overview (Last 30 Days)',
                'align' => 'left'
            ]
        ])
        ->render();

    return view('admin.dashboard', compact('salesChart'));
}
```

**Route:**
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/chart/sales', [DashboardController::class, 'chartData'])->name('admin.chart.sales');
});
```


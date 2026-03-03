# Chart Data Aggregation Guide

## Overview

The TableBuilder component now supports automatic chart data aggregation, allowing you to create charts directly from your table data with built-in aggregation functions.

**Location**: `Canvastack\Canvastack\Components\Table\TableBuilder`

---

## Basic Usage

### Simple Line Chart with SUM

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function dashboard(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    // Create a line chart showing total sales by month
    $table->chart('line', ['total'], 'sum', 'month');
    
    return view('dashboard', ['table' => $table]);
}
```

### Chart in Tab

```php
public function reports(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    // Open tab
    $table->openTab('Sales Summary');
    
    // Add chart to tab
    $table->chart('line', ['total'], 'sum', 'month');
    
    // Add table below chart
    $table->lists('orders', ['id', 'total', 'month']);
    
    $table->closeTab();
    
    return view('reports', ['table' => $table]);
}
```

---

## Chart Types

### Line Chart
```php
$table->chart('line', ['sales'], 'sum', 'month');
```

### Bar Chart
```php
$table->chart('bar', ['quantity'], 'count', 'category');
```

### Pie Chart
```php
$table->chart('pie', ['revenue'], 'sum', 'region');
```

### Area Chart
```php
$table->chart('area', ['profit'], 'sum', 'quarter');
```

### Donut Chart
```php
$table->chart('donut', ['orders'], 'count', 'status');
```

---

## Aggregation Functions

### SUM - Total Values
```php
// Sum all sales by month
$table->chart('line', ['sales'], 'sum', 'month');

// Example data:
// Month: 2024-01, Sales: [100, 200, 150] → Result: 450
// Month: 2024-02, Sales: [300, 250] → Result: 550
```

### AVG - Average Values
```php
// Average order value by month
$table->chart('line', ['order_value'], 'avg', 'month');

// Example data:
// Month: 2024-01, Values: [100, 200, 150] → Result: 150
// Month: 2024-02, Values: [300, 250] → Result: 275
```

### COUNT - Count Records
```php
// Count orders by status
$table->chart('bar', ['id'], 'count', 'status');

// Example data:
// Status: pending, Records: 5 → Result: 5
// Status: completed, Records: 10 → Result: 10
```

### MIN - Minimum Value
```php
// Minimum price by category
$table->chart('bar', ['price'], 'min', 'category');

// Example data:
// Category: Electronics, Prices: [100, 200, 150] → Result: 100
// Category: Books, Prices: [10, 20, 15] → Result: 10
```

### MAX - Maximum Value
```php
// Maximum temperature by month
$table->chart('line', ['temperature'], 'max', 'month');

// Example data:
// Month: Jan, Temps: [10, 15, 12] → Result: 15
// Month: Feb, Temps: [12, 18, 14] → Result: 18
```

---

## Multiple Series

You can display multiple data series in a single chart:

```php
// Show both sales and profit by month
$table->chart('line', ['sales', 'profit'], 'sum', 'month');

// Result:
// Series 1: Sales - [1000, 1500, 2000]
// Series 2: Profit - [200, 300, 400]
```

---

## Grouping Options

### By Month
```php
$table->chart('line', ['total'], 'sum', 'month');
// Groups data by month field
```

### By Year
```php
$table->chart('bar', ['revenue'], 'sum', 'year');
// Groups data by year field
```

### By Category
```php
$table->chart('pie', ['sales'], 'sum', 'category');
// Groups data by category field
```

### By Custom Field
```php
$table->chart('bar', ['count'], 'count', 'custom_field');
// Groups data by any field in your model
```

---

## Integration with Filters

Charts automatically respect active table filters:

```php
// Add filters
$table->filterGroups('region', 'selectbox');
$table->filterGroups('period', 'selectbox');

// Chart will only show data matching active filters
$table->chart('line', ['sales'], 'sum', 'month');
```

When a user applies filters, the chart data will automatically update to reflect only the filtered data.

---

## Complete Example

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function salesReport(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    // Add filters
    $table->filterGroups('region', 'selectbox');
    $table->filterGroups('year', 'selectbox');
    
    // Create tabs
    $table->openTab('Overview');
    
    // Add multiple charts
    $table->chart('line', ['total', 'profit'], 'sum', 'month');
    $table->chart('pie', ['total'], 'sum', 'category');
    
    // Add data table
    $table->lists('orders', [
        'id:Order ID',
        'total:Total',
        'month:Month',
        'category:Category'
    ]);
    
    $table->closeTab();
    
    // Second tab with different aggregation
    $table->openTab('Statistics');
    
    $table->chart('bar', ['total'], 'avg', 'month');
    $table->chart('bar', ['id'], 'count', 'status');
    
    $table->closeTab();
    
    $table->format();
    
    return view('reports.sales', ['table' => $table]);
}
```

---

## Data Structure

The `buildChartData()` method returns data in this format:

```php
[
    'series' => [
        [
            'name' => 'Total',
            'data' => [300, 400, 500]
        ],
        [
            'name' => 'Profit',
            'data' => [50, 75, 100]
        ]
    ],
    'categories' => ['2024-01', '2024-02', '2024-03'],
    'values' => [300, 400, 500],
    'labels' => ['2024-01', '2024-02', '2024-03']
]
```

---

## Field Name Formatting

Field names are automatically formatted for display:

```php
// Database field: total_sales
// Chart label: Total Sales

// Database field: order_count
// Chart label: Order Count
```

Underscores are replaced with spaces and each word is capitalized.

---

## Error Handling

### No Model Set
```php
// ❌ This will throw an exception
$table->chart('line', ['total'], 'sum', 'month');

// ✅ Always set model first
$table->setModel(new Order());
$table->chart('line', ['total'], 'sum', 'month');
```

### Invalid Chart Type
```php
// ❌ This will throw an exception
$table->chart('invalid', ['total'], 'sum', 'month');

// ✅ Use valid chart types
$table->chart('line', ['total'], 'sum', 'month');
// Valid types: line, bar, pie, area, donut
```

---

## Performance Tips

1. **Limit Data Points**: Use filters to limit the date range
2. **Use Appropriate Aggregation**: COUNT is faster than SUM for large datasets
3. **Index Group Fields**: Ensure your groupBy field is indexed in the database
4. **Cache Results**: Consider caching chart data for frequently accessed reports

---

## Testing

Example test for chart aggregation:

```php
public function test_chart_aggregates_sales_by_month(): void
{
    // Create test data
    Order::factory()->create(['total' => 100, 'month' => '2024-01']);
    Order::factory()->create(['total' => 200, 'month' => '2024-01']);
    Order::factory()->create(['total' => 150, 'month' => '2024-02']);
    
    $table = new TableBuilder();
    $table->setModel(new Order());
    
    // Use reflection to test protected method
    $reflection = new \ReflectionClass($table);
    $method = $reflection->getMethod('buildChartData');
    $method->setAccessible(true);
    
    $data = $method->invoke($table, ['total'], 'sum', 'month');
    
    // Verify aggregation
    $this->assertEquals(['2024-01', '2024-02'], $data['categories']);
    $this->assertEquals([300, 150], $data['series'][0]['data']);
}
```

---

## Related Documentation

- [TableBuilder API Reference](../api/table.md)
- [ChartBuilder API Reference](../api/chart.md)
- [Tab System Guide](./table-tabs.md)
- [Filter System Guide](./table-filters.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Published

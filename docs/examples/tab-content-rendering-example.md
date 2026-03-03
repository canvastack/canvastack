# Tab Content Rendering - Complete Example

This document provides a complete, working example of the tab content rendering system.

## Complete Working Example

### Controller Implementation

```php
<?php

namespace App\Http\Controllers\Admin;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display multi-tab report
     */
    public function index(TableBuilder $table): View
    {
        $table->setContext('admin');
        
        // Tab 1: Summary
        $table->openTab('Summary');
        $table->addTabContent('<p class="text-sm text-gray-600 dark:text-gray-400">Last updated: ' . date('Y-m-d H:i:s') . '</p>');
        $table->lists('report_summary', [
            'metric:Metric',
            'value:Value',
            'change:Change %'
        ], false);
        $table->closeTab();
        
        // Tab 2: Details
        $table->openTab('Details');
        $table->addTabContent('<div class="alert alert-info mb-4">Detailed breakdown of all metrics</div>');
        $table->lists('report_details', [
            'id:ID',
            'name:Name',
            'category:Category',
            'value:Value',
            'status:Status'
        ], false);
        $table->closeTab();
        
        // Tab 3: Monthly Trends
        $table->openTab('Monthly');
        $table->lists('report_monthly', [
            'month:Month',
            'total:Total',
            'average:Average',
            'growth:Growth %'
        ], false);
        $table->closeTab();
        
        // Tab 4: Regional Data
        $table->openTab('Regional');
        $table->addTabContent('<p class="text-sm text-gray-600 dark:text-gray-400">Regional performance breakdown</p>');
        $table->lists('report_regional', [
            'region:Region',
            'sales:Sales',
            'target:Target',
            'achievement:Achievement %'
        ], false);
        $table->closeTab();
        
        $table->format();
        
        return view('admin.reports.index', [
            'table' => $table
        ]);
    }
}
```

### View Implementation

```blade
@extends('canvastack::layouts.admin')

@section('title', 'Sales Report')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Sales Report
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            Comprehensive sales analysis with multiple views
        </p>
    </div>
    
    {{-- Tab Container --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        {!! $table->render() !!}
    </div>
</div>
@endsection
```

## Output Structure

The rendered HTML will have this structure:

```html
<div class="table-tab-container responsive" data-table-id="table-12345">
    <!-- Tab Navigation -->
    <div class="tab-navigation-wrapper">
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" role="tablist">
                <button role="tab" class="border-primary-500 text-primary-600">Summary</button>
                <button role="tab" class="border-transparent text-gray-500">Details</button>
                <button role="tab" class="border-transparent text-gray-500">Monthly</button>
                <button role="tab" class="border-transparent text-gray-500">Regional</button>
            </nav>
        </div>
    </div>
    
    <!-- Tab Content Area -->
    <div class="tab-content-wrapper mt-6">
        <!-- Summary Tab -->
        <div id="tabpanel-summary" role="tabpanel" class="tab-content-panel active">
            <!-- Custom Content -->
            <div class="tab-custom-content mb-6">
                <div class="content-block mb-4">
                    <p class="text-sm text-gray-600">Last updated: 2026-03-02 10:30:00</p>
                </div>
            </div>
            
            <!-- Table -->
            <div class="tab-tables-container space-y-6">
                <div class="table-wrapper">
                    <div class="table-instance-container">
                        <div class="table-content p-6">
                            <table class="dataTable">
                                <!-- Table content -->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Other tabs... -->
    </div>
</div>
```

## Features Demonstrated

### 1. Multiple Tabs

✅ Four tabs with different data sources
✅ Each tab has independent table configuration
✅ Smooth transitions between tabs

### 2. Custom Content

✅ Last updated timestamp in Summary tab
✅ Alert message in Details tab
✅ Description text in Regional tab

### 3. Responsive Design

✅ Mobile: Horizontal scroll for tabs
✅ Tablet: Optimized spacing
✅ Desktop: Full layout

### 4. Dark Mode

✅ All elements support dark mode
✅ Proper color contrast
✅ Smooth theme transitions

### 5. Accessibility

✅ ARIA attributes (role, aria-labelledby, aria-hidden)
✅ Keyboard navigation (Arrow keys, Home, End)
✅ Focus management
✅ Screen reader support

## Advanced Example: Multiple Tables Per Tab

```php
public function dashboard(TableBuilder $table): View
{
    $table->setContext('admin');
    
    // Dashboard tab with multiple tables
    $table->openTab('Dashboard');
    
    // Add overview content
    $table->addTabContent('
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="text-3xl font-bold">1,234</p>
            </div>
            <div class="stat-card">
                <h3>Active Sessions</h3>
                <p class="text-3xl font-bold">567</p>
            </div>
            <div class="stat-card">
                <h3>Revenue</h3>
                <p class="text-3xl font-bold">$89,012</p>
            </div>
        </div>
    ');
    
    // First table: Recent Users
    $table->lists('users', [
        'name:Name',
        'email:Email',
        'created_at:Joined'
    ], false);
    
    // Second table: Recent Orders
    $table->lists('orders', [
        'order_id:Order ID',
        'customer:Customer',
        'total:Total',
        'status:Status'
    ], false);
    
    $table->closeTab();
    
    $table->format();
    
    return view('admin.dashboard', ['table' => $table]);
}
```

## Testing the Implementation

### Unit Test

```php
public function test_multi_tab_rendering(): void
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    
    // Create tabs
    $table->openTab('Tab 1');
    $table->addTabContent('<p>Content 1</p>');
    $table->lists('table1', ['col1:Column 1'], false);
    $table->closeTab();
    
    $table->openTab('Tab 2');
    $table->addTabContent('<p>Content 2</p>');
    $table->lists('table2', ['col2:Column 2'], false);
    $table->closeTab();
    
    $table->format();
    $html = $table->render();
    
    // Assertions
    $this->assertStringContainsString('Tab 1', $html);
    $this->assertStringContainsString('Tab 2', $html);
    $this->assertStringContainsString('Content 1', $html);
    $this->assertStringContainsString('Content 2', $html);
    $this->assertStringContainsString('table-tab-container', $html);
}
```

### Browser Test

```php
public function test_tab_switching_works(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::find(1))
                ->visit('/admin/reports')
                ->assertSee('Summary')
                ->assertSee('Details')
                ->click('@tab-details')
                ->pause(500)
                ->assertVisible('#tabpanel-details')
                ->assertHidden('#tabpanel-summary');
    });
}
```

## Performance Considerations

### 1. Lazy Loading

Tables are only rendered when their tab is active:

```javascript
// DataTables are initialized only when tab becomes visible
this.$watch('activeTab', (newTab) => {
    this.$nextTick(() => {
        this.refreshDataTables(newTab);
    });
});
```

### 2. Content Caching

Custom content is cached to avoid re-rendering:

```php
// Content is stored in TabManager and reused
$tabManager->addContent($content); // Cached
```

### 3. Responsive Images

Images in custom content should use responsive attributes:

```php
$table->addTabContent('
    <img src="chart.png" 
         srcset="chart-sm.png 640w, chart-md.png 1024w, chart-lg.png 1920w"
         sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
         alt="Sales Chart">
');
```

## Troubleshooting

### Issue: Tabs Not Switching

**Solution**: Ensure Alpine.js is loaded:

```blade
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
```

### Issue: Tables Not Rendering

**Solution**: Check that DataTables is initialized:

```javascript
// Ensure DataTables is loaded
if (typeof $.fn.DataTable === 'undefined') {
    console.error('DataTables not loaded');
}
```

### Issue: Dark Mode Not Working

**Solution**: Add dark mode class to HTML:

```blade
<html class="dark">
```

### Issue: Content Not Sanitized

**Solution**: Use sanitization option:

```php
$renderer->renderContentBlock($userContent, ['sanitize' => true]);
```

## Best Practices Summary

1. ✅ Always set context (`admin` or `public`)
2. ✅ Use unique table IDs
3. ✅ Sanitize user-generated content
4. ✅ Validate tabs before rendering
5. ✅ Handle empty states gracefully
6. ✅ Test on mobile devices
7. ✅ Verify accessibility with screen readers
8. ✅ Use semantic HTML
9. ✅ Optimize for performance
10. ✅ Document custom content structure

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete

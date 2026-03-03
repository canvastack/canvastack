# Tab Navigation UI - Usage Example

## Overview

The Tab Navigation UI provides an Alpine.js-powered, accessible, and responsive tab system for TableBuilder. It includes smooth transitions, URL parameter sync, session persistence, and keyboard navigation.

## Basic Usage

### Controller Example

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    
    // Tab 1: Summary
    $table->openTab('Summary');
    $table->addTabContent('<p>Last updated: ' . now()->format('Y-m-d') . '</p>');
    $table->setData($summaryData);
    $table->setFields(['name:Name', 'total:Total', 'status:Status']);
    $table->closeTab();
    
    // Tab 2: Detail
    $table->openTab('Detail');
    $table->addTabContent('<p>Detailed breakdown of all records</p>');
    $table->setData($detailData);
    $table->setFields(['id:ID', 'name:Name', 'description:Description', 'created_at:Created']);
    $table->closeTab();
    
    // Tab 3: Monthly
    $table->openTab('Monthly');
    $table->setData($monthlyData);
    $table->setFields(['month:Month', 'revenue:Revenue', 'expenses:Expenses']);
    $table->closeTab();
    
    return view('reports.index', ['table' => $table]);
}
```

### View Example

```blade
@extends('canvastack::layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Sales Report</h1>
    
    {{-- Render table with tab navigation --}}
    {!! $table->renderWithTabs() !!}
</div>
@endsection
```

## Features

### 1. Tab Switching

Users can switch between tabs by:
- Clicking on tab buttons
- Using keyboard navigation (Arrow keys, Home, End)
- URL parameters (e.g., `?tab=detail`)

### 2. URL Parameter Sync

The active tab is automatically synced to the URL:
- When a tab is clicked, the URL updates with `?tab=<tab-id>`
- When the page loads, the tab from the URL is restored
- Browser back/forward buttons work correctly

### 3. Session Persistence

The active tab is saved to session storage:
- Persists across page reloads
- Unique per table and page
- Falls back to URL parameter if present

### 4. Smooth Transitions

Tab content fades in/out with smooth animations:
- 200ms fade-in with slide-up effect
- 150ms fade-out
- Hardware-accelerated transforms

### 5. Keyboard Navigation

Full keyboard support for accessibility:
- `Arrow Left/Up`: Previous tab
- `Arrow Right/Down`: Next tab
- `Home`: First tab
- `End`: Last tab
- `Tab`: Focus navigation
- Wraps around at edges

### 6. Accessibility

WCAG 2.1 compliant with:
- ARIA attributes (`role`, `aria-selected`, `aria-controls`, `aria-labelledby`)
- Proper focus management
- Keyboard navigation
- Screen reader support

### 7. Dark Mode

Automatic dark mode support:
- Uses Tailwind's `dark:` prefix
- Adapts colors and borders
- Smooth transitions

### 8. Responsive Design

Mobile-friendly with:
- Horizontal scrolling for many tabs
- Touch-friendly tap targets
- Responsive spacing

## Advanced Usage

### Multiple Tables with Tabs

```php
// Table 1
$table1 = app(TableBuilder::class);
$table1->setContext('admin');
$table1->openTab('Users');
$table1->setData($users);
$table1->setFields(['name:Name', 'email:Email']);
$table1->closeTab();

// Table 2
$table2 = app(TableBuilder::class);
$table2->setContext('admin');
$table2->openTab('Products');
$table2->setData($products);
$table2->setFields(['name:Name', 'price:Price']);
$table2->closeTab();

return view('dashboard', [
    'usersTable' => $table1,
    'productsTable' => $table2,
]);
```

### Custom Content in Tabs

```php
$table->openTab('Summary');

// Add multiple content blocks
$table->addTabContent('<div class="alert alert-info">Important notice</div>');
$table->addTabContent('<p>Data as of: ' . now()->format('F j, Y') . '</p>');

$table->setData($data);
$table->setFields(['name:Name']);
$table->closeTab();
```

### Listening to Tab Changes

```javascript
// Listen for tab change events
document.addEventListener('tab-changed', (event) => {
    console.log('Tab changed:', event.detail);
    // event.detail = { tableId, tabId, oldTabId }
    
    // Perform custom actions
    if (event.detail.tabId === 'detail') {
        // Load additional data for detail tab
        loadDetailData();
    }
});
```

### Programmatic Tab Switching

```javascript
// Get the Alpine component
const tabComponent = Alpine.$data(document.querySelector('[data-table-id="table_123"]'));

// Switch to a specific tab
tabComponent.switchTab('detail');

// Get current active tab
console.log(tabComponent.activeTab);

// Get all tabs
console.log(tabComponent.tabs);
```

## Styling Customization

### Custom Tab Colors

```css
/* Override primary color for active tabs */
.border-primary-500 {
    border-color: #your-color !important;
}

.text-primary-600 {
    color: #your-color !important;
}
```

### Custom Transitions

```css
/* Slower transitions */
.tab-content > div {
    transition-duration: 400ms !important;
}

/* Different animation */
[x-transition] {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1) !important;
}
```

## Browser Support

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile browsers: ✅ Full support
- IE11: ❌ Not supported (Alpine.js requires modern browsers)

## Performance

- Lazy rendering: Tab content only renders when visible
- Efficient DOM updates: Alpine.js reactive system
- Minimal JavaScript: ~2KB gzipped
- No jQuery dependency

## Troubleshooting

### Tabs not switching

1. Check Alpine.js is loaded:
   ```html
   <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
   ```

2. Check console for JavaScript errors

3. Verify tab IDs are unique

### URL not updating

1. Check browser supports `window.history.pushState`
2. Verify no JavaScript errors in console
3. Check URL is not blocked by browser security

### Session not persisting

1. Check `sessionStorage` is available
2. Verify browser allows session storage
3. Check for private/incognito mode restrictions

## Testing

Run the test suite:

```bash
cd packages/canvastack/canvastack
./vendor/bin/phpunit tests/Unit/Components/Table/TabNavigationUITest.php
```

All 18 tests should pass:
- ✅ Tab navigation rendering
- ✅ Active tab highlighting
- ✅ ARIA attributes
- ✅ Alpine.js data
- ✅ Transitions
- ✅ JavaScript functions
- ✅ Keyboard navigation
- ✅ URL parameter sync
- ✅ Session storage
- ✅ Custom content
- ✅ Dark mode support
- ✅ Responsive design
- ✅ Unique IDs
- ✅ Focus management
- ✅ Custom events

## Related Documentation

- [Tab System Design](../design/tab-system.md)
- [TableBuilder API](../api/table-builder.md)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Accessibility Guidelines](../guides/accessibility.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete

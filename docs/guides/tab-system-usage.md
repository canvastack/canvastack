# Tab System Usage Guide

Organize multiple tables into tabbed interface with lazy loading for optimal performance.

## 📦 Overview

The TanStack Table Tab System allows you to organize multiple data tables into a tabbed interface with:
- **Backward-compatible API**: Same API as Origin CanvaStack
- **Hybrid rendering**: First tab loads immediately, others load on-demand
- **Multiple tables per tab**: Display multiple related tables in a single tab
- **Custom content**: Add custom HTML content to tabs
- **Lazy loading**: Configurable lazy loading for performance
- **Independent state**: Each table maintains its own state
- **Keyboard navigation**: Full keyboard support for accessibility

This guide shows how to use the tab system to organize multiple tables.

---

## 🎯 Basic Tab Usage

### Simple Example: Three Tabs with One Table Each

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title(__('ui.content.title'))
            ->description(__('ui.content.description'));
        
        // Tab 1: Users
        $table->openTab(__('ui.tabs.users'));
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $table->addAction('edit', route('users.edit', ':id'), 'edit', __('ui.buttons.edit'));
        $table->closeTab();
        
        // Tab 2: Posts
        $table->openTab(__('ui.tabs.posts'));
        $table->setContext('admin');
        $table->setModel(new Post());
        $table->setFields([
            'title:' . __('ui.labels.title'),
            'author.name:' . __('ui.labels.author'),
            'published_at:' . __('ui.labels.published'),
        ]);
        $table->eager(['author']); // Prevent N+1
        $table->addAction('view', route('posts.show', ':id'), 'eye', __('ui.buttons.view'));
        $table->closeTab();
        
        // Tab 3: Comments
        $table->openTab(__('ui.tabs.comments'));
        $table->setContext('admin');
        $table->setModel(new Comment());
        $table->setFields([
            'content:' . __('ui.labels.comment'),
            'user.name:' . __('ui.labels.user'),
            'post.title:' . __('ui.labels.post'),
            'created_at:' . __('ui.labels.posted'),
        ]);
        $table->eager(['user', 'post']); // Prevent N+1
        $table->closeTab();
        
        // Format all tabs
        $table->format();
        
        return view('admin.content.index', [
            'meta' => $meta,
            'table' => $table,
        ]);
    }
}
```

**View** (`resources/views/admin/content/index.blade.php`):
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8" style="font-family: @themeFont('sans')">
        {{ __('ui.content.title') }}
    </h1>
    
    {{-- Render tabbed tables --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
        {!! $table->render() !!}
    </div>
</div>
@endsection
```

**Result**: 
- Three tabs: Users, Posts, Comments
- First tab (Users) loads immediately
- Other tabs load when clicked (lazy loading)
- Each tab has one table
- Tab navigation with DaisyUI styling
- Dark mode support

---

## 🔧 Multiple Tables Per Tab

### Example: Tab with Multiple Related Tables

**Scenario**: Display user information with related posts and comments in one tab.

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title(__('ui.dashboard'))
            ->description(__('ui.dashboard_description'));
        
        // Tab 1: Overview (multiple tables)
        $table->openTab(__('ui.tabs.overview'));
        
        // First table in tab: Recent Users
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.joined'),
        ]);
        $table->orderBy('created_at', 'desc');
        $table->limit(5);
        
        // Second table in tab: Recent Posts
        $table->setContext('admin');
        $table->setModel(new Post());
        $table->setFields([
            'title:' . __('ui.labels.title'),
            'author.name:' . __('ui.labels.author'),
            'published_at:' . __('ui.labels.published'),
        ]);
        $table->eager(['author']);
        $table->orderBy('published_at', 'desc');
        $table->limit(5);
        
        $table->closeTab();
        
        // Tab 2: Users (single table)
        $table->openTab(__('ui.tabs.all_users'));
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'status:' . __('ui.labels.status'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $table->addAction('edit', route('users.edit', ':id'), 'edit', __('ui.buttons.edit'));
        $table->closeTab();
        
        // Tab 3: Posts (single table)
        $table->openTab(__('ui.tabs.all_posts'));
        $table->setContext('admin');
        $table->setModel(new Post());
        $table->setFields([
            'title:' . __('ui.labels.title'),
            'author.name:' . __('ui.labels.author'),
            'status:' . __('ui.labels.status'),
            'published_at:' . __('ui.labels.published'),
        ]);
        $table->eager(['author']);
        $table->addAction('view', route('posts.show', ':id'), 'eye', __('ui.buttons.view'));
        $table->closeTab();
        
        $table->format();
        
        return view('admin.dashboard', [
            'meta' => $meta,
            'table' => $table,
        ]);
    }
}
```

**View**:
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.dashboard') }}</h1>
    
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
        {!! $table->render() !!}
    </div>
</div>
@endsection
```

**Result**:
- Tab 1 (Overview) contains TWO tables: Recent Users + Recent Posts
- Tab 2 (All Users) contains ONE table: All Users
- Tab 3 (All Posts) contains ONE table: All Posts
- Each table has unique ID (no collisions)
- Each table maintains independent state

**Key Points**:
- Call `setModel()` and `setFields()` multiple times within a tab
- Each call creates a new table in that tab
- All tables in a tab render together
- No limit on number of tables per tab

---

## 🎨 Custom Content in Tabs

### Example: Tab with Custom HTML and Tables

**Scenario**: Add custom HTML content (alerts, cards, statistics) to tabs alongside tables.

**Controller**:
```php
public function reports(TableBuilder $table, MetaTags $meta): View
{
    $meta->title(__('ui.reports.title'));
    
    // Tab 1: Sales Report with custom content
    $table->openTab(__('ui.tabs.sales'));
    
    // Add custom HTML content
    $table->addTabContent('
        <div class="alert alert-info mb-6">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>' . __('ui.reports.sales_info') . '</span>
        </div>
    ');
    
    // Add statistics cards
    $totalSales = Sale::sum('amount');
    $table->addTabContent('
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat bg-base-200 rounded-xl">
                <div class="stat-title">' . __('ui.stats.total_sales') . '</div>
                <div class="stat-value">' . number_format($totalSales) . '</div>
            </div>
            <div class="stat bg-base-200 rounded-xl">
                <div class="stat-title">' . __('ui.stats.orders') . '</div>
                <div class="stat-value">' . Sale::count() . '</div>
            </div>
            <div class="stat bg-base-200 rounded-xl">
                <div class="stat-title">' . __('ui.stats.avg_order') . '</div>
                <div class="stat-value">' . number_format(Sale::avg('amount')) . '</div>
            </div>
        </div>
    ');
    
    // Add sales table
    $table->setContext('admin');
    $table->setModel(new Sale());
    $table->setFields([
        'order_id:' . __('ui.labels.order_id'),
        'customer.name:' . __('ui.labels.customer'),
        'amount:' . __('ui.labels.amount'),
        'date:' . __('ui.labels.date'),
    ]);
    $table->eager(['customer']);
    $table->closeTab();
    
    // Tab 2: Inventory (table only)
    $table->openTab(__('ui.tabs.inventory'));
    $table->setContext('admin');
    $table->setModel(new Product());
    $table->setFields([
        'name:' . __('ui.labels.product'),
        'sku:' . __('ui.labels.sku'),
        'stock:' . __('ui.labels.stock'),
        'price:' . __('ui.labels.price'),
    ]);
    $table->closeTab();
    
    $table->format();
    
    return view('admin.reports.index', [
        'meta' => $meta,
        'table' => $table,
    ]);
}
```

**Result**:
- Tab 1 (Sales) contains: Alert + Statistics Cards + Sales Table
- Tab 2 (Inventory) contains: Inventory Table only
- Custom content renders before tables in the tab
- Full HTML/CSS support in custom content

**Key Points**:
- Use `addTabContent()` to add custom HTML
- Call multiple times to add multiple content blocks
- Custom content renders in order added
- Custom content renders before tables
- Supports any HTML/CSS/JavaScript

---

## ⚙️ Lazy Loading Configuration

### Default Behavior (Lazy Loading Enabled)

By default, lazy loading is ENABLED:
- First tab loads immediately on page load
- Other tabs load when user clicks them
- Loaded tabs are cached (no re-fetch on switch)

**Configuration** (`config/canvastack.php`):
```php
'table' => [
    'lazy_load_tabs' => env('CANVASTACK_LAZY_LOAD_TABS', true),
],
```

**Environment Variable** (`.env`):
```env
CANVASTACK_LAZY_LOAD_TABS=true
```

### Disable Lazy Loading

To load ALL tabs immediately:

**Option 1: Environment Variable**
```env
CANVASTACK_LAZY_LOAD_TABS=false
```

**Option 2: Configuration File**
```php
// config/canvastack.php
'table' => [
    'lazy_load_tabs' => false,
],
```

**Option 3: Per-Instance**
```php
$table->setLazyLoading(false);
```

**When to Disable Lazy Loading**:
- Small number of tabs (2-3)
- Small datasets per tab
- Need all data immediately
- SEO requirements (crawlers)
- Print-friendly pages

**When to Keep Lazy Loading Enabled**:
- Many tabs (4+)
- Large datasets per tab
- Performance is critical
- Mobile users
- Slow database queries

---

## 🎯 Complete Example: Multi-Database Tabs

### Scenario: Tabs with Different Database Connections

Display data from multiple databases in different tabs.

**Models**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Main application users (MySQL)
class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
}

// Analytics data (PostgreSQL)
class Analytics extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'analytics';
}

// Legacy data (SQLite)
class LegacyData extends Model
{
    protected $connection = 'sqlite';
    protected $table = 'legacy_data';
}
```

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Analytics;
use App\Models\LegacyData;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class MultiDatabaseController extends Controller
{
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title(__('ui.reports.multi_database'));
        
        // Tab 1: Users (MySQL)
        $table->openTab(__('ui.tabs.users') . ' (MySQL)');
        $table->setContext('admin');
        $table->setModel(new User());
        // Connection auto-detected: mysql
        $table->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $table->cache(300); // Cache for 5 minutes
        $table->closeTab();
        
        // Tab 2: Analytics (PostgreSQL)
        $table->openTab(__('ui.tabs.analytics') . ' (PostgreSQL)');
        $table->setContext('admin');
        $table->setModel(new Analytics());
        // Connection auto-detected: pgsql
        $table->setFields([
            'event:' . __('ui.labels.event'),
            'count:' . __('ui.labels.count'),
            'date:' . __('ui.labels.date'),
        ]);
        $table->orderBy('date', 'desc');
        $table->cache(600); // Cache for 10 minutes
        $table->closeTab();
        
        // Tab 3: Legacy (SQLite)
        $table->openTab(__('ui.tabs.legacy') . ' (SQLite)');
        $table->setContext('admin');
        $table->setModel(new LegacyData());
        // Connection auto-detected: sqlite
        $table->setFields([
            'id:' . __('ui.labels.id'),
            'description:' . __('ui.labels.description'),
            'migrated:' . __('ui.labels.migrated'),
        ]);
        $table->closeTab();
        
        $table->format();
        
        return view('admin.reports.multi-database', [
            'meta' => $meta,
            'table' => $table,
        ]);
    }
}
```

**View**:
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.reports.multi_database') }}</h1>
    
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
        {!! $table->render() !!}
    </div>
</div>
@endsection
```

**Result**:
- Tab 1: Users from MySQL database
- Tab 2: Analytics from PostgreSQL database
- Tab 3: Legacy data from SQLite database
- Each tab auto-detects connection from model
- Each tab loads independently
- Each tab has separate caching

---

## 🎭 Common Patterns

### Pattern 1: Dashboard with Overview + Detail Tabs

```php
public function dashboard(TableBuilder $table, MetaTags $meta): View
{
    $meta->title(__('ui.dashboard'));
    
    // Tab 1: Overview (multiple tables + custom content)
    $table->openTab(__('ui.tabs.overview'));
    
    // Add statistics
    $table->addTabContent('
        <div class="stats stats-vertical lg:stats-horizontal shadow mb-6">
            <div class="stat">
                <div class="stat-title">Total Users</div>
                <div class="stat-value">' . User::count() . '</div>
            </div>
            <div class="stat">
                <div class="stat-title">Total Posts</div>
                <div class="stat-value">' . Post::count() . '</div>
            </div>
            <div class="stat">
                <div class="stat-title">Total Comments</div>
                <div class="stat-value">' . Comment::count() . '</div>
            </div>
        </div>
    ');
    
    // Recent users
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'created_at:Joined']);
    $table->orderBy('created_at', 'desc');
    $table->limit(5);
    $table->disablePagination();
    
    // Recent posts
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setFields(['title:Title', 'published_at:Published']);
    $table->orderBy('published_at', 'desc');
    $table->limit(5);
    $table->disablePagination();
    
    $table->closeTab();
    
    // Tab 2: All Users
    $table->openTab(__('ui.tabs.all_users'));
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status',
        'created_at:Created',
    ]);
    $table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
    $table->closeTab();
    
    // Tab 3: All Posts
    $table->openTab(__('ui.tabs.all_posts'));
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setFields([
        'title:Title',
        'author.name:Author',
        'status:Status',
        'published_at:Published',
    ]);
    $table->eager(['author']);
    $table->addAction('view', route('posts.show', ':id'), 'eye', 'View');
    $table->closeTab();
    
    $table->format();
    
    return view('admin.dashboard', compact('meta', 'table'));
}
```

### Pattern 2: Status-Based Tabs

```php
public function users(TableBuilder $table, MetaTags $meta): View
{
    $meta->title(__('ui.users.title'));
    
    // Tab 1: Active Users
    $table->openTab(__('ui.tabs.active_users'));
    $table->setContext('admin');
    $table->setModel(new User());
    $table->where('status', 'active');
    $table->setFields([
        'name:Name',
        'email:Email',
        'last_login:Last Login',
    ]);
    $table->addAction('view', route('users.show', ':id'), 'eye', 'View');
    $table->closeTab();
    
    // Tab 2: Inactive Users
    $table->openTab(__('ui.tabs.inactive_users'));
    $table->setContext('admin');
    $table->setModel(new User());
    $table->where('status', 'inactive');
    $table->setFields([
        'name:Name',
        'email:Email',
        'deactivated_at:Deactivated',
    ]);
    $table->addAction('activate', route('users.activate', ':id'), 'check', 'Activate');
    $table->closeTab();
    
    // Tab 3: Banned Users
    $table->openTab(__('ui.tabs.banned_users'));
    $table->setContext('admin');
    $table->setModel(new User());
    $table->where('status', 'banned');
    $table->setFields([
        'name:Name',
        'email:Email',
        'banned_at:Banned',
        'ban_reason:Reason',
    ]);
    $table->addAction('unban', route('users.unban', ':id'), 'unlock', 'Unban');
    $table->closeTab();
    
    $table->format();
    
    return view('admin.users.index', compact('meta', 'table'));
}
```

### Pattern 3: Time-Based Tabs

```php
public function reports(TableBuilder $table, MetaTags $meta): View
{
    $meta->title(__('ui.reports.sales'));
    
    // Tab 1: Today
    $table->openTab(__('ui.tabs.today'));
    $table->setContext('admin');
    $table->setModel(new Sale());
    $table->whereDate('created_at', today());
    $table->setFields([
        'order_id:Order',
        'customer.name:Customer',
        'amount:Amount',
        'created_at:Time',
    ]);
    $table->eager(['customer']);
    $table->orderBy('created_at', 'desc');
    $table->closeTab();
    
    // Tab 2: This Week
    $table->openTab(__('ui.tabs.this_week'));
    $table->setContext('admin');
    $table->setModel(new Sale());
    $table->whereBetween('created_at', [now()->startOfWeek(), now()]);
    $table->setFields([
        'order_id:Order',
        'customer.name:Customer',
        'amount:Amount',
        'created_at:Date',
    ]);
    $table->eager(['customer']);
    $table->orderBy('created_at', 'desc');
    $table->closeTab();
    
    // Tab 3: This Month
    $table->openTab(__('ui.tabs.this_month'));
    $table->setContext('admin');
    $table->setModel(new Sale());
    $table->whereMonth('created_at', now()->month);
    $table->setFields([
        'order_id:Order',
        'customer.name:Customer',
        'amount:Amount',
        'created_at:Date',
    ]);
    $table->eager(['customer']);
    $table->orderBy('created_at', 'desc');
    $table->closeTab();
    
    $table->format();
    
    return view('admin.reports.sales', compact('meta', 'table'));
}
```

---

## 💡 Tips & Best Practices

### 1. Tab Naming

**Use descriptive, concise tab names**:
```php
// ✅ GOOD
$table->openTab('Active Users');
$table->openTab('Sales Report');
$table->openTab('Inventory');

// ❌ BAD
$table->openTab('Tab 1');
$table->openTab('Data');
$table->openTab('T');
```

### 2. Tab Order

**Put most important/frequently accessed tab first**:
```php
// ✅ GOOD - Most used tab first
$table->openTab('Overview');      // Loads immediately
$table->openTab('Details');       // Lazy loads
$table->openTab('Archive');       // Lazy loads

// ❌ BAD - Rarely used tab first
$table->openTab('Archive');       // Loads immediately (waste)
$table->openTab('Overview');      // Lazy loads (should be first)
```

### 3. Eager Loading

**Always use eager loading for relationships**:
```php
// ✅ GOOD
$table->setModel(new Post());
$table->eager(['author', 'category']);
$table->setFields([
    'title:Title',
    'author.name:Author',
    'category.name:Category',
]);

// ❌ BAD - N+1 queries
$table->setModel(new Post());
$table->setFields([
    'title:Title',
    'author.name:Author',      // N+1 query
    'category.name:Category',  // N+1 query
]);
```

### 4. Caching

**Enable caching for slow queries**:
```php
// ✅ GOOD - Cache expensive queries
$table->setModel(new Analytics());
$table->cache(600); // 10 minutes
$table->setFields([...]);

// ✅ GOOD - No cache for real-time data
$table->setModel(new LiveFeed());
// No caching for live data
$table->setFields([...]);
```

### 5. Pagination

**Disable pagination for small datasets in tabs**:
```php
// ✅ GOOD - Small dataset, no pagination needed
$table->setModel(new User());
$table->limit(5);
$table->disablePagination();

// ✅ GOOD - Large dataset, keep pagination
$table->setModel(new User());
// Pagination enabled by default
```

### 6. Custom Content

**Use custom content for context and statistics**:
```php
// ✅ GOOD - Add context with custom content
$table->openTab('Sales Report');
$table->addTabContent('
    <div class="alert alert-info mb-4">
        <span>Showing sales data for ' . now()->format('F Y') . '</span>
    </div>
');
$table->setModel(new Sale());
// ... table configuration
$table->closeTab();
```

### 7. Error Handling

**Wrap tab configuration in try-catch for debugging**:
```php
try {
    $table->openTab('Users');
    $table->setModel(new User());
    $table->setFields([...]);
    $table->closeTab();
    
    $table->format();
} catch (\Exception $e) {
    Log::error('Tab configuration error: ' . $e->getMessage());
    return back()->withErrors(['error' => 'Failed to load data']);
}
```

---

## 🔍 Debugging

### View Tab Configuration

```php
$table->openTab('Users');
$table->setModel(new User());
$table->setFields(['name:Name']);
$table->closeTab();

$table->openTab('Posts');
$table->setModel(new Post());
$table->setFields(['title:Title']);
$table->closeTab();

// Debug: View all tabs
dd($table->getTabs());

// Output:
// [
//     [
//         'name' => 'Users',
//         'tables' => [...],
//         'custom_content' => '',
//         'lazy_load' => true,
//         'url' => '/api/canvastack/table/tab/0',
//     ],
//     [
//         'name' => 'Posts',
//         'tables' => [...],
//         'custom_content' => '',
//         'lazy_load' => true,
//         'url' => '/api/canvastack/table/tab/1',
//     ],
// ]
```

### Check if Tabs are Used

```php
$table->openTab('Users');
// ... configuration
$table->closeTab();

if ($table->hasTabNavigation()) {
    echo "Using tabs";
} else {
    echo "Not using tabs";
}
```

### View Unique IDs

```php
$table->openTab('Users');
$table->setModel(new User());
$table->setFields(['name:Name']);
$table->closeTab();

$table->format();

// Each table in each tab has unique ID
foreach ($table->getTabs() as $tab) {
    foreach ($tab['tables'] as $tableConfig) {
        echo "Table ID: " . $tableConfig['id'] . "\n";
    }
}
```

---

## 🧪 Testing

### Feature Test: Tab Rendering

```php
<?php

namespace Tests\Feature\Components\Table;

use App\Models\User;
use App\Models\Post;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

class TabSystemTest extends TestCase
{
    public function test_tabs_render_correctly(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        
        $table->openTab('Users');
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields(['name:Name']);
        $table->closeTab();
        
        $table->openTab('Posts');
        $table->setContext('admin');
        $table->setModel(new Post());
        $table->setFields(['title:Title']);
        $table->closeTab();
        
        $table->format();
        
        // Act
        $html = $table->render();
        
        // Assert
        $this->assertStringContainsString('Users', $html);
        $this->assertStringContainsString('Posts', $html);
        $this->assertStringContainsString('x-data', $html); // Alpine.js
        $this->assertStringContainsString('tabs', $html); // DaisyUI tabs
    }
    
    public function test_first_tab_loads_immediately(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        
        $table->openTab('Tab 1');
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields(['name:Name']);
        $table->closeTab();
        
        $table->openTab('Tab 2');
        $table->setContext('admin');
        $table->setModel(new Post());
        $table->setFields(['title:Title']);
        $table->closeTab();
        
        $table->format();
        
        // Act
        $html = $table->render();
        
        // Assert
        // First tab should have table HTML
        $this->assertStringContainsString('<table', $html);
        
        // Second tab should have placeholder
        $this->assertStringContainsString('data-tab-url', $html);
    }
}
```

---

## 🔗 Related Documentation

- [Multi-Table Usage Guide](multi-table-usage.md) - Multiple tables without tabs
- [TanStack Table API Reference](../api/table-multi-tab.md) - Complete API documentation
- [Connection Detection Guide](connection-detection.md) - Database connection management
- [Performance Optimization Guide](performance-optimization.md) - Caching and optimization

---

## 📚 Resources

- [DaisyUI Tabs Component](https://daisyui.com/components/tab/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [TanStack Table Documentation](https://tanstack.com/table/latest)
- [Laravel Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published

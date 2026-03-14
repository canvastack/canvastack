# Multi-Table Usage Guide

Display multiple independent tables on the same page without tab navigation.

## 📦 Overview

The TanStack Table Multi-Table system allows you to display multiple data tables on a single page, each with its own:
- Unique identifier (no ID collisions)
- Database connection (can differ per table)
- Independent state (sorting, filtering, pagination)
- TanStack Table features (sorting, filtering, pagination)

This guide shows how to use multiple tables WITHOUT the tab system.

---

## 🎯 Basic Usage

### Simple Example: Two Tables on Same Page

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Post;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MetaTags $meta): View
    {
        $meta->title(__('ui.dashboard'))
            ->description(__('ui.dashboard_description'));
        
        // Create first table - Users
        $usersTable = app(TableBuilder::class);
        $usersTable->setContext('admin');
        $usersTable->setModel(new User());
        $usersTable->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $usersTable->addAction('edit', route('users.edit', ':id'), 'edit', __('ui.buttons.edit'));
        $usersTable->format();
        
        // Create second table - Posts
        $postsTable = app(TableBuilder::class);
        $postsTable->setContext('admin');
        $postsTable->setModel(new Post());
        $postsTable->setFields([
            'title:' . __('ui.labels.title'),
            'author:' . __('ui.labels.author'),
            'published_at:' . __('ui.labels.published'),
        ]);
        $postsTable->addAction('view', route('posts.show', ':id'), 'eye', __('ui.buttons.view'));
        $postsTable->format();
        
        return view('admin.dashboard', [
            'meta' => $meta,
            'usersTable' => $usersTable,
            'postsTable' => $postsTable,
        ]);
    }
}
```

**View** (`resources/views/admin/dashboard.blade.php`):
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8" style="font-family: @themeFont('sans')">
        {{ __('ui.dashboard') }}
    </h1>
    
    {{-- Users Table --}}
    <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">{{ __('ui.users.title') }}</h2>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $usersTable->render() !!}
        </div>
    </div>
    
    {{-- Posts Table --}}
    <div class="mb-8">
        <h2 class="text-2xl font-semibold mb-4">{{ __('ui.posts.title') }}</h2>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $postsTable->render() !!}
        </div>
    </div>
</div>
@endsection
```

**Result**: Two independent tables on the same page, each with unique IDs and separate state.

---

## 🔧 Different Database Connections

### Example: Multi-Database Application

**Scenario**: Display users from MySQL and analytics from PostgreSQL on the same page.

**Database Configuration** (`config/database.php`):
```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'app_db'),
        // ... other config
    ],
    
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('ANALYTICS_DB_HOST', '127.0.0.1'),
        'database' => env('ANALYTICS_DB_DATABASE', 'analytics_db'),
        // ... other config
    ],
],
```

**Models**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// User model - MySQL connection
class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
}

// Analytics model - PostgreSQL connection
class Analytics extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'analytics';
}
```

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Analytics;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(MetaTags $meta): View
    {
        $meta->title(__('ui.reports.title'))
            ->description(__('ui.reports.description'));
        
        // Table 1: Users from MySQL
        $usersTable = app(TableBuilder::class);
        $usersTable->setContext('admin');
        $usersTable->setModel(new User());
        // Connection auto-detected from model (mysql)
        $usersTable->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $usersTable->format();
        
        // Table 2: Analytics from PostgreSQL
        $analyticsTable = app(TableBuilder::class);
        $analyticsTable->setContext('admin');
        $analyticsTable->setModel(new Analytics());
        // Connection auto-detected from model (pgsql)
        $analyticsTable->setFields([
            'event:' . __('ui.labels.event'),
            'count:' . __('ui.labels.count'),
            'date:' . __('ui.labels.date'),
        ]);
        $analyticsTable->format();
        
        return view('admin.reports.index', [
            'meta' => $meta,
            'usersTable' => $usersTable,
            'analyticsTable' => $analyticsTable,
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
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.reports.title') }}</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Users Table (MySQL) --}}
        <div>
            <h2 class="text-xl font-semibold mb-4">
                {{ __('ui.users.title') }}
                <span class="text-sm text-gray-500">(MySQL)</span>
            </h2>
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                {!! $usersTable->render() !!}
            </div>
        </div>
        
        {{-- Analytics Table (PostgreSQL) --}}
        <div>
            <h2 class="text-xl font-semibold mb-4">
                {{ __('ui.analytics.title') }}
                <span class="text-sm text-gray-500">(PostgreSQL)</span>
            </h2>
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                {!! $analyticsTable->render() !!}
            </div>
        </div>
    </div>
</div>
@endsection
```

**Key Points**:
- Each table automatically detects its connection from the model
- No manual connection specification needed
- Tables operate independently on different databases
- Each table has unique ID (no collisions)

---

## 🔄 Manual Connection Override

### Example: Override Model Connection

**Scenario**: Model has default connection, but you want to use a different one.

**Controller**:
```php
public function index(MetaTags $meta): View
{
    $meta->title(__('ui.reports.title'));
    
    // Table with manual connection override
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setModel(new User()); // Model uses 'mysql'
    $table->connection('pgsql');  // Override to use 'pgsql'
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    $table->format();
    
    return view('admin.reports.index', [
        'meta' => $meta,
        'table' => $table,
    ]);
}
```

**Warning System**:

When you override a connection that differs from the model's connection, the system will warn you based on configuration:

**Configuration** (`.env`):
```env
# Enable connection override warnings
CANVASTACK_CONNECTION_WARNING=true

# Warning method: log, toast, or both
CANVASTACK_CONNECTION_WARNING_METHOD=both
```

**Log Output** (when method is 'log' or 'both'):
```
[2026-03-09 10:30:45] local.WARNING: Connection override detected:
Model: App\Models\User
Model Connection: mysql
Override Connection: pgsql
This may cause unexpected behavior if the model has connection-specific logic.
```

**Toast Notification** (when method is 'toast' or 'both'):
```
⚠️ Connection Override Warning
User model expects mysql but using pgsql
```

---

## 🎯 State Isolation

### Example: Independent Table State

Each table maintains its own state for sorting, filtering, and pagination.

**Controller**:
```php
public function index(MetaTags $meta): View
{
    $meta->title(__('ui.data.title'));
    
    // Table 1: Active Users
    $activeUsersTable = app(TableBuilder::class);
    $activeUsersTable->setContext('admin');
    $activeUsersTable->setModel(new User());
    $activeUsersTable->where('status', 'active');
    $activeUsersTable->setFields(['name:Name', 'email:Email']);
    $activeUsersTable->orderBy('created_at', 'desc');
    $activeUsersTable->format();
    
    // Table 2: Inactive Users
    $inactiveUsersTable = app(TableBuilder::class);
    $inactiveUsersTable->setContext('admin');
    $inactiveUsersTable->setModel(new User());
    $inactiveUsersTable->where('status', 'inactive');
    $inactiveUsersTable->setFields(['name:Name', 'email:Email', 'deactivated_at:Deactivated']);
    $inactiveUsersTable->orderBy('deactivated_at', 'desc');
    $inactiveUsersTable->format();
    
    return view('admin.users.status', [
        'meta' => $meta,
        'activeUsersTable' => $activeUsersTable,
        'inactiveUsersTable' => $inactiveUsersTable,
    ]);
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
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.users.by_status') }}</h1>
    
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Active Users Table --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">{{ __('ui.users.active') }}</h2>
                <span class="badge badge-success">{{ __('ui.status.active') }}</span>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                {!! $activeUsersTable->render() !!}
            </div>
        </div>
        
        {{-- Inactive Users Table --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">{{ __('ui.users.inactive') }}</h2>
                <span class="badge badge-error">{{ __('ui.status.inactive') }}</span>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                {!! $inactiveUsersTable->render() !!}
            </div>
        </div>
    </div>
</div>
@endsection
```

**Key Features**:
- Each table has independent sorting (Active: by created_at, Inactive: by deactivated_at)
- Each table has independent filtering (Active: status=active, Inactive: status=inactive)
- Each table has independent pagination
- Sorting one table doesn't affect the other
- Filtering one table doesn't affect the other

---

## 🔍 Advanced Example: Three Tables with Different Connections

### Scenario: Multi-Tenant Application

Display data from three different databases on a single dashboard.

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
    public function index(MetaTags $meta): View
    {
        $meta->title(__('ui.reports.multi_database'))
            ->description(__('ui.reports.multi_database_description'));
        
        // Table 1: Users from MySQL
        $usersTable = app(TableBuilder::class);
        $usersTable->setContext('admin');
        $usersTable->setModel(new User());
        // Connection: mysql (auto-detected)
        $usersTable->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.created_at'),
        ]);
        $usersTable->eager(['profile']); // Prevent N+1
        $usersTable->cache(300); // Cache for 5 minutes
        $usersTable->format();
        
        // Table 2: Analytics from PostgreSQL
        $analyticsTable = app(TableBuilder::class);
        $analyticsTable->setContext('admin');
        $analyticsTable->setModel(new Analytics());
        // Connection: pgsql (auto-detected)
        $analyticsTable->setFields([
            'event:' . __('ui.labels.event'),
            'count:' . __('ui.labels.count'),
            'date:' . __('ui.labels.date'),
        ]);
        $analyticsTable->orderBy('date', 'desc');
        $analyticsTable->cache(600); // Cache for 10 minutes
        $analyticsTable->format();
        
        // Table 3: Legacy Data from SQLite
        $legacyTable = app(TableBuilder::class);
        $legacyTable->setContext('admin');
        $legacyTable->setModel(new LegacyData());
        // Connection: sqlite (auto-detected)
        $legacyTable->setFields([
            'id:' . __('ui.labels.id'),
            'description:' . __('ui.labels.description'),
            'migrated:' . __('ui.labels.migrated'),
        ]);
        $legacyTable->format();
        
        return view('admin.reports.multi-database', [
            'meta' => $meta,
            'usersTable' => $usersTable,
            'analyticsTable' => $analyticsTable,
            'legacyTable' => $legacyTable,
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
    
    {{-- Users Table (MySQL) --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-2xl font-semibold">{{ __('ui.users.title') }}</h2>
            <span class="badge badge-info">MySQL</span>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $usersTable->render() !!}
        </div>
    </div>
    
    {{-- Analytics Table (PostgreSQL) --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-2xl font-semibold">{{ __('ui.analytics.title') }}</h2>
            <span class="badge badge-secondary">PostgreSQL</span>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $analyticsTable->render() !!}
        </div>
    </div>
    
    {{-- Legacy Data Table (SQLite) --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-2xl font-semibold">{{ __('ui.legacy.title') }}</h2>
            <span class="badge badge-warning">SQLite</span>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $legacyTable->render() !!}
        </div>
    </div>
</div>
@endsection
```

**Result**: Three tables from different databases, each with:
- Unique identifier
- Independent connection
- Separate state (sorting, filtering, pagination)
- Independent caching

---

## 🎨 Responsive Layouts

### Example: Grid Layout for Multiple Tables

**Controller**:
```php
public function dashboard(MetaTags $meta): View
{
    $meta->title(__('ui.dashboard'));
    
    // Create 4 tables for dashboard widgets
    $tables = [];
    
    // Recent Users
    $tables['users'] = app(TableBuilder::class);
    $tables['users']->setContext('admin');
    $tables['users']->setModel(new User());
    $tables['users']->setFields(['name:Name', 'created_at:Joined']);
    $tables['users']->orderBy('created_at', 'desc');
    $tables['users']->limit(5);
    $tables['users']->format();
    
    // Recent Posts
    $tables['posts'] = app(TableBuilder::class);
    $tables['posts']->setContext('admin');
    $tables['posts']->setModel(new Post());
    $tables['posts']->setFields(['title:Title', 'published_at:Published']);
    $tables['posts']->orderBy('published_at', 'desc');
    $tables['posts']->limit(5);
    $tables['posts']->format();
    
    // Recent Comments
    $tables['comments'] = app(TableBuilder::class);
    $tables['comments']->setContext('admin');
    $tables['comments']->setModel(new Comment());
    $tables['comments']->setFields(['content:Comment', 'created_at:Posted']);
    $tables['comments']->orderBy('created_at', 'desc');
    $tables['comments']->limit(5);
    $tables['comments']->format();
    
    // Top Products
    $tables['products'] = app(TableBuilder::class);
    $tables['products']->setContext('admin');
    $tables['products']->setModel(new Product());
    $tables['products']->setFields(['name:Product', 'sales:Sales']);
    $tables['products']->orderBy('sales', 'desc');
    $tables['products']->limit(5);
    $tables['products']->format();
    
    return view('admin.dashboard', [
        'meta' => $meta,
        'tables' => $tables,
    ]);
}
```

**View** (Responsive Grid):
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.dashboard') }}</h1>
    
    {{-- 2x2 Grid on desktop, stacked on mobile --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Users --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-semibold mb-4">{{ __('ui.users.recent') }}</h2>
            {!! $tables['users']->render() !!}
        </div>
        
        {{-- Recent Posts --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-semibold mb-4">{{ __('ui.posts.recent') }}</h2>
            {!! $tables['posts']->render() !!}
        </div>
        
        {{-- Recent Comments --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-semibold mb-4">{{ __('ui.comments.recent') }}</h2>
            {!! $tables['comments']->render() !!}
        </div>
        
        {{-- Top Products --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-semibold mb-4">{{ __('ui.products.top') }}</h2>
            {!! $tables['products']->render() !!}
        </div>
    </div>
</div>
@endsection
```

**Result**: 
- 2x2 grid on desktop
- Stacked layout on mobile
- Each table independent
- Each table has unique ID
- No state interference

---

## 🔐 Security Considerations

### Unique ID Security

Each table gets a secure, non-predictable unique ID:

```
Format: canvastable_{16-character-hash}
Example: canvastable_a1b2c3d4e5f6g7h8
```

**Security Features**:
- SHA256 hash algorithm
- Cryptographically secure random bytes
- No predictable patterns
- No information disclosure (table name not visible)
- Different ID on every page refresh

**Implementation**:
```php
// Automatic - no manual configuration needed
$table1 = app(TableBuilder::class);
$table1->setModel(new User());
$table1->format();
echo $table1->getUniqueId(); // canvastable_a1b2c3d4e5f6g7h8

$table2 = app(TableBuilder::class);
$table2->setModel(new User()); // Same model
$table2->format();
echo $table2->getUniqueId(); // canvastable_x9y8z7w6v5u4t3s2 (different!)
```

### SQL Injection Prevention

TableBuilder automatically prevents SQL injection:

```php
// ✅ SAFE - Parameterized queries
$table->where('status', $request->input('status'));
$table->search($request->input('search'));

// ✅ SAFE - Validated column names
$table->orderBy($request->input('sort'), $request->input('direction'));
```

### XSS Prevention

TableBuilder automatically escapes output:

```php
// ✅ SAFE - Automatic escaping
$table->setFields(['name:Name', 'email:Email']);

// ✅ SAFE - Custom renderer with manual escaping
$table->setColumnRenderer('name', function($row) {
    return '<strong>' . e($row->name) . '</strong>';
});
```

---

## 💡 Performance Optimization

### Eager Loading (Prevent N+1 Queries)

**Problem**: N+1 queries when displaying related data.

**Solution**: Use eager loading.

```php
// ❌ BAD - N+1 queries
$table->setModel(new Post());
$table->setFields([
    'title:Title',
    'author.name:Author', // Triggers N+1 query
]);

// ✅ GOOD - Eager loading
$table->setModel(new Post());
$table->eager(['author']); // Load all authors in one query
$table->setFields([
    'title:Title',
    'author.name:Author',
]);
```

### Caching

**Enable caching for static or slow-changing data**:

```php
// Cache for 5 minutes
$table->cache(300);

// Cache for 1 hour
$table->cache(3600);

// Disable caching (default)
// $table->cache(0);
```

**Cache Invalidation**:
```php
// In your model's save/delete events
protected static function booted()
{
    static::saved(function ($model) {
        Cache::tags(['users_table'])->flush();
    });
    
    static::deleted(function ($model) {
        Cache::tags(['users_table'])->flush();
    });
}
```

### Pagination

**Limit data transfer with pagination**:

```php
// Default: 10 rows per page
$table->format();

// Custom page size
$table->perPage(25);
$table->format();

// Disable pagination (not recommended for large datasets)
$table->disablePagination();
```

---

## 🎭 Common Patterns

### Pattern 1: Master-Detail View

Display master list and related details side-by-side.

```php
public function show(User $user, MetaTags $meta): View
{
    $meta->title(__('ui.users.profile', ['name' => $user->name]));
    
    // User's posts table
    $postsTable = app(TableBuilder::class);
    $postsTable->setContext('admin');
    $postsTable->setModel(new Post());
    $postsTable->where('user_id', $user->id);
    $postsTable->setFields(['title:Title', 'published_at:Published', 'views:Views']);
    $postsTable->format();
    
    // User's comments table
    $commentsTable = app(TableBuilder::class);
    $commentsTable->setContext('admin');
    $commentsTable->setModel(new Comment());
    $commentsTable->where('user_id', $user->id);
    $commentsTable->setFields(['content:Comment', 'post.title:Post', 'created_at:Posted']);
    $commentsTable->eager(['post']);
    $commentsTable->format();
    
    return view('admin.users.show', [
        'meta' => $meta,
        'user' => $user,
        'postsTable' => $postsTable,
        'commentsTable' => $commentsTable,
    ]);
}
```

### Pattern 2: Comparison View

Display two datasets side-by-side for comparison.

```php
public function compare(MetaTags $meta): View
{
    $meta->title(__('ui.reports.comparison'));
    
    // This month's sales
    $thisMonthTable = app(TableBuilder::class);
    $thisMonthTable->setContext('admin');
    $thisMonthTable->setModel(new Sale());
    $thisMonthTable->whereBetween('date', [now()->startOfMonth(), now()]);
    $thisMonthTable->setFields(['product:Product', 'amount:Amount', 'date:Date']);
    $thisMonthTable->format();
    
    // Last month's sales
    $lastMonthTable = app(TableBuilder::class);
    $lastMonthTable->setContext('admin');
    $lastMonthTable->setModel(new Sale());
    $lastMonthTable->whereBetween('date', [
        now()->subMonth()->startOfMonth(),
        now()->subMonth()->endOfMonth()
    ]);
    $lastMonthTable->setFields(['product:Product', 'amount:Amount', 'date:Date']);
    $lastMonthTable->format();
    
    return view('admin.reports.compare', [
        'meta' => $meta,
        'thisMonthTable' => $thisMonthTable,
        'lastMonthTable' => $lastMonthTable,
    ]);
}
```

### Pattern 3: Dashboard Widgets

Display multiple small tables as dashboard widgets.

```php
public function dashboard(MetaTags $meta): View
{
    $meta->title(__('ui.dashboard'));
    
    $widgets = [];
    
    // Top 5 Users
    $widgets['top_users'] = app(TableBuilder::class);
    $widgets['top_users']->setContext('admin');
    $widgets['top_users']->setModel(new User());
    $widgets['top_users']->setFields(['name:Name', 'posts_count:Posts']);
    $widgets['top_users']->orderBy('posts_count', 'desc');
    $widgets['top_users']->limit(5);
    $widgets['top_users']->disablePagination();
    $widgets['top_users']->format();
    
    // Recent Activity
    $widgets['activity'] = app(TableBuilder::class);
    $widgets['activity']->setContext('admin');
    $widgets['activity']->setModel(new Activity());
    $widgets['activity']->setFields(['description:Activity', 'created_at:Time']);
    $widgets['activity']->orderBy('created_at', 'desc');
    $widgets['activity']->limit(5);
    $widgets['activity']->disablePagination();
    $widgets['activity']->format();
    
    return view('admin.dashboard', [
        'meta' => $meta,
        'widgets' => $widgets,
    ]);
}
```

---

## 🔍 Debugging

### View Unique IDs

Check the unique ID assigned to each table:

```php
$table1 = app(TableBuilder::class);
$table1->setModel(new User());
$table1->format();

$table2 = app(TableBuilder::class);
$table2->setModel(new Post());
$table2->format();

// Debug output
dd([
    'table1_id' => $table1->getUniqueId(),
    'table2_id' => $table2->getUniqueId(),
]);

// Output:
// [
//     'table1_id' => 'canvastable_a1b2c3d4e5f6g7h8',
//     'table2_id' => 'canvastable_x9y8z7w6v5u4t3s2',
// ]
```

### View Connection Detection

Check which connection is being used:

```php
$table = app(TableBuilder::class);
$table->setModel(new User());

// Enable debug logging
config(['app.debug' => true]);
config(['logging.default' => 'daily']);

$table->format();

// Check logs/laravel.log:
// [2026-03-09 10:30:45] local.DEBUG: Connection detected for User: mysql
```

### View Generated HTML

Inspect the rendered HTML to verify unique IDs:

```php
$table = app(TableBuilder::class);
$table->setModel(new User());
$table->setFields(['name:Name']);
$table->format();

$html = $table->render();

// Check for unique ID in HTML
if (preg_match('/id="(canvastable_[a-f0-9]{16})"/', $html, $matches)) {
    echo "Table ID: " . $matches[1];
}
```

---

## 🧪 Testing

### Unit Test: Multiple Tables

```php
<?php

namespace Tests\Unit\Components\Table;

use App\Models\User;
use App\Models\Post;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

class MultiTableTest extends TestCase
{
    public function test_multiple_tables_have_unique_ids(): void
    {
        // Arrange
        $table1 = app(TableBuilder::class);
        $table1->setContext('admin');
        $table1->setModel(new User());
        $table1->setFields(['name:Name']);
        $table1->format();
        
        $table2 = app(TableBuilder::class);
        $table2->setContext('admin');
        $table2->setModel(new Post());
        $table2->setFields(['title:Title']);
        $table2->format();
        
        // Act
        $id1 = $table1->getUniqueId();
        $id2 = $table2->getUniqueId();
        
        // Assert
        $this->assertNotEquals($id1, $id2, 'Tables should have different unique IDs');
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $id1);
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $id2);
    }
    
    public function test_tables_with_same_model_have_different_ids(): void
    {
        // Arrange
        $table1 = app(TableBuilder::class);
        $table1->setContext('admin');
        $table1->setModel(new User());
        $table1->setFields(['name:Name']);
        $table1->format();
        
        $table2 = app(TableBuilder::class);
        $table2->setContext('admin');
        $table2->setModel(new User()); // Same model
        $table2->setFields(['name:Name']); // Same fields
        $table2->format();
        
        // Act
        $id1 = $table1->getUniqueId();
        $id2 = $table2->getUniqueId();
        
        // Assert
        $this->assertNotEquals($id1, $id2, 'Tables with same config should still have different IDs');
    }
    
    public function test_tables_detect_different_connections(): void
    {
        // Arrange
        $userModel = new User();
        $userModel->setConnection('mysql');
        
        $analyticsModel = new Analytics();
        $analyticsModel->setConnection('pgsql');
        
        $table1 = app(TableBuilder::class);
        $table1->setContext('admin');
        $table1->setModel($userModel);
        $table1->format();
        
        $table2 = app(TableBuilder::class);
        $table2->setContext('admin');
        $table2->setModel($analyticsModel);
        $table2->format();
        
        // Act & Assert
        $this->assertNotEquals(
            $table1->getUniqueId(),
            $table2->getUniqueId(),
            'Tables with different connections should have different IDs'
        );
    }
}
```

### Feature Test: Multi-Table Page

```php
<?php

namespace Tests\Feature\Components\Table;

use App\Models\User;
use App\Models\Post;
use Canvastack\Canvastack\Tests\TestCase;

class MultiTableRenderingTest extends TestCase
{
    public function test_page_renders_multiple_tables(): void
    {
        // Arrange
        User::factory()->count(3)->create();
        Post::factory()->count(3)->create();
        
        // Act
        $response = $this->get(route('admin.dashboard'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('canvastable_'); // Has table IDs
        
        // Verify multiple unique IDs exist
        $html = $response->getContent();
        preg_match_all('/id="(canvastable_[a-f0-9]{16})"/', $html, $matches);
        
        $this->assertGreaterThanOrEqual(2, count($matches[1]), 'Should have at least 2 tables');
        $this->assertEquals(
            count($matches[1]),
            count(array_unique($matches[1])),
            'All table IDs should be unique'
        );
    }
}
```

---

## 💡 Tips & Best Practices

### 1. Use Dependency Injection

Always inject TableBuilder via dependency injection:

```php
// ✅ GOOD - Dependency injection
public function index(TableBuilder $table): View
{
    $table->setModel(new User());
    // ...
}

// ❌ BAD - Manual instantiation
public function index(): View
{
    $table = new TableBuilder();
    // ...
}
```

### 2. Create Multiple Instances with app()

When you need multiple tables, use `app()` helper:

```php
// ✅ GOOD - Multiple instances
$table1 = app(TableBuilder::class);
$table2 = app(TableBuilder::class);
$table3 = app(TableBuilder::class);

// ❌ BAD - Reusing same instance
$table = app(TableBuilder::class);
$table->setModel(new User());
$table->format();
$table->setModel(new Post()); // Overwrites previous config!
```

### 3. Set Context for Consistent Styling

Always set context (admin or public):

```php
// Admin context - uses admin theme
$table->setContext('admin');

// Public context - uses public theme
$table->setContext('public');
```

### 4. Use Eager Loading for Relationships

Prevent N+1 queries by eager loading:

```php
$table->setModel(new Post());
$table->eager(['author', 'category', 'tags']);
$table->setFields([
    'title:Title',
    'author.name:Author',
    'category.name:Category',
]);
```

### 5. Enable Caching for Static Data

Cache query results for better performance:

```php
// Static data - cache for 1 hour
$table->cache(3600);

// Frequently changing data - cache for 5 minutes
$table->cache(300);

// Real-time data - no caching
$table->cache(0);
```

### 6. Limit Data for Dashboard Widgets

Use `limit()` for dashboard widgets:

```php
$table->limit(5); // Show only 5 rows
$table->disablePagination(); // No pagination for widgets
```

### 7. Use Descriptive Variable Names

Name your table variables descriptively:

```php
// ✅ GOOD
$usersTable = app(TableBuilder::class);
$postsTable = app(TableBuilder::class);
$commentsTable = app(TableBuilder::class);

// ❌ BAD
$table1 = app(TableBuilder::class);
$table2 = app(TableBuilder::class);
$table3 = app(TableBuilder::class);
```

### 8. Group Related Tables

Organize related tables in arrays:

```php
$tables = [
    'users' => $usersTable,
    'posts' => $postsTable,
    'comments' => $commentsTable,
];

return view('admin.dashboard', compact('meta', 'tables'));
```

### 9. Add Visual Separators

Use cards or sections to separate tables visually:

```blade
{{-- Each table in its own card --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 mb-6">
    <h2 class="text-xl font-semibold mb-4">{{ __('ui.users.title') }}</h2>
    {!! $usersTable->render() !!}
</div>

<div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 mb-6">
    <h2 class="text-xl font-semibold mb-4">{{ __('ui.posts.title') }}</h2>
    {!! $postsTable->render() !!}
</div>
```

### 10. Use Responsive Grid Layouts

Make tables responsive with Tailwind grid:

```blade
{{-- 2 columns on large screens, 1 column on mobile --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div>{!! $table1->render() !!}</div>
    <div>{!! $table2->render() !!}</div>
</div>

{{-- 3 columns on extra large screens --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div>{!! $table1->render() !!}</div>
    <div>{!! $table2->render() !!}</div>
    <div>{!! $table3->render() !!}</div>
</div>
```

---

## 🚨 Common Pitfalls

### Pitfall 1: Reusing Same TableBuilder Instance

```php
// ❌ WRONG - Reusing instance
$table = app(TableBuilder::class);

$table->setModel(new User());
$table->format();
$usersHtml = $table->render();

$table->setModel(new Post()); // Overwrites User config!
$table->format();
$postsHtml = $table->render();

// ✅ CORRECT - Create new instances
$usersTable = app(TableBuilder::class);
$usersTable->setModel(new User());
$usersTable->format();

$postsTable = app(TableBuilder::class);
$postsTable->setModel(new Post());
$postsTable->format();
```

### Pitfall 2: Forgetting to Call format()

```php
// ❌ WRONG - Missing format()
$table->setModel(new User());
$table->setFields(['name:Name']);
// Missing: $table->format();
return view('view', ['table' => $table]);

// ✅ CORRECT - Always call format()
$table->setModel(new User());
$table->setFields(['name:Name']);
$table->format(); // Required!
return view('view', ['table' => $table]);
```

### Pitfall 3: N+1 Queries with Relationships

```php
// ❌ WRONG - N+1 queries
$table->setModel(new Post());
$table->setFields([
    'title:Title',
    'author.name:Author', // Triggers N+1
    'category.name:Category', // Triggers N+1
]);

// ✅ CORRECT - Eager loading
$table->setModel(new Post());
$table->eager(['author', 'category']);
$table->setFields([
    'title:Title',
    'author.name:Author',
    'category.name:Category',
]);
```

### Pitfall 4: Not Setting Context

```php
// ❌ WRONG - No context
$table->setModel(new User());
$table->format();

// ✅ CORRECT - Always set context
$table->setContext('admin'); // or 'public'
$table->setModel(new User());
$table->format();
```

---

## 🔗 Related Documentation

- [Tab System Usage Guide](tab-system-usage.md) - Using tables with tab navigation
- [TableBuilder API Reference](../api/table.md) - Complete API documentation
- [Connection Detection Guide](connection-detection.md) - Database connection management
- [Performance Optimization Guide](performance-optimization.md) - Performance best practices
- [TanStack Table Integration](../features/tanstack-integration.md) - TanStack Table features

---

## 📚 Complete Example: Multi-Database Dashboard

This complete example demonstrates all concepts together.

**Models**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    protected $connection = 'mysql';
    protected $table = 'posts';
    
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

class Analytics extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'analytics';
}
```

**Controller**:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Post;
use App\Models\Analytics;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MetaTags $meta): View
    {
        // Meta tags
        $meta->title(__('ui.dashboard'))
            ->description(__('ui.dashboard_description'))
            ->keywords('dashboard, analytics, users, posts');
        
        // Table 1: Recent Users (MySQL)
        $usersTable = app(TableBuilder::class);
        $usersTable->setContext('admin');
        $usersTable->setModel(new User());
        $usersTable->setFields([
            'name:' . __('ui.labels.name'),
            'email:' . __('ui.labels.email'),
            'created_at:' . __('ui.labels.joined'),
        ]);
        $usersTable->orderBy('created_at', 'desc');
        $usersTable->limit(10);
        $usersTable->cache(300); // Cache for 5 minutes
        $usersTable->addAction('view', route('users.show', ':id'), 'eye', __('ui.buttons.view'));
        $usersTable->addAction('edit', route('users.edit', ':id'), 'edit', __('ui.buttons.edit'));
        $usersTable->format();
        
        // Table 2: Recent Posts (MySQL)
        $postsTable = app(TableBuilder::class);
        $postsTable->setContext('admin');
        $postsTable->setModel(new Post());
        $postsTable->eager(['author']); // Prevent N+1
        $postsTable->setFields([
            'title:' . __('ui.labels.title'),
            'author.name:' . __('ui.labels.author'),
            'published_at:' . __('ui.labels.published'),
            'views:' . __('ui.labels.views'),
        ]);
        $postsTable->orderBy('published_at', 'desc');
        $postsTable->limit(10);
        $postsTable->cache(300);
        $postsTable->addAction('view', route('posts.show', ':id'), 'eye', __('ui.buttons.view'));
        $postsTable->format();
        
        // Table 3: Analytics (PostgreSQL)
        $analyticsTable = app(TableBuilder::class);
        $analyticsTable->setContext('admin');
        $analyticsTable->setModel(new Analytics());
        $analyticsTable->setFields([
            'event:' . __('ui.labels.event'),
            'count:' . __('ui.labels.count'),
            'date:' . __('ui.labels.date'),
        ]);
        $analyticsTable->orderBy('date', 'desc');
        $analyticsTable->limit(10);
        $analyticsTable->cache(600); // Cache for 10 minutes
        $analyticsTable->format();
        
        return view('admin.dashboard', [
            'meta' => $meta,
            'usersTable' => $usersTable,
            'postsTable' => $postsTable,
            'analyticsTable' => $analyticsTable,
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
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold" style="font-family: @themeFont('sans')">
            {{ __('ui.dashboard') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            {{ __('ui.dashboard_subtitle') }}
        </p>
    </div>
    
    {{-- Users Table (MySQL) --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-semibold">{{ __('ui.users.recent') }}</h2>
                <span class="badge badge-info">MySQL</span>
            </div>
            <a href="{{ route('users.index') }}" class="text-sm" style="color: @themeColor('primary')">
                {{ __('ui.buttons.view_all') }} →
            </a>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-sm">
            {!! $usersTable->render() !!}
        </div>
    </div>
    
    {{-- Posts Table (MySQL) --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-semibold">{{ __('ui.posts.recent') }}</h2>
                <span class="badge badge-info">MySQL</span>
            </div>
            <a href="{{ route('posts.index') }}" class="text-sm" style="color: @themeColor('primary')">
                {{ __('ui.buttons.view_all') }} →
            </a>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-sm">
            {!! $postsTable->render() !!}
        </div>
    </div>
    
    {{-- Analytics Table (PostgreSQL) --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-semibold">{{ __('ui.analytics.recent') }}</h2>
                <span class="badge badge-secondary">PostgreSQL</span>
            </div>
            <a href="{{ route('analytics.index') }}" class="text-sm" style="color: @themeColor('primary')">
                {{ __('ui.buttons.view_all') }} →
            </a>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-sm">
            {!! $analyticsTable->render() !!}
        </div>
    </div>
</div>
@endsection
```

**Features Demonstrated**:
- Three tables from two different databases
- Automatic connection detection
- Independent state per table
- Unique IDs (no collisions)
- Caching for performance
- Eager loading to prevent N+1
- Responsive layout
- Dark mode support
- Theme integration
- i18n support

---

## 🎮 Interactive Example: Filtering Multiple Tables

Display multiple tables with independent filtering.

**Controller**:
```php
public function reports(Request $request, MetaTags $meta): View
{
    $meta->title(__('ui.reports.title'));
    
    // Table 1: Users filtered by status
    $usersTable = app(TableBuilder::class);
    $usersTable->setContext('admin');
    $usersTable->setModel(new User());
    
    if ($request->has('user_status')) {
        $usersTable->where('status', $request->input('user_status'));
    }
    
    $usersTable->setFields(['name:Name', 'email:Email', 'status:Status']);
    $usersTable->format();
    
    // Table 2: Posts filtered by category
    $postsTable = app(TableBuilder::class);
    $postsTable->setContext('admin');
    $postsTable->setModel(new Post());
    
    if ($request->has('post_category')) {
        $postsTable->where('category_id', $request->input('post_category'));
    }
    
    $postsTable->setFields(['title:Title', 'category.name:Category', 'published_at:Published']);
    $postsTable->eager(['category']);
    $postsTable->format();
    
    return view('admin.reports.index', [
        'meta' => $meta,
        'usersTable' => $usersTable,
        'postsTable' => $postsTable,
        'userStatuses' => ['active' => 'Active', 'inactive' => 'Inactive'],
        'postCategories' => Category::pluck('name', 'id'),
    ]);
}
```

**View with Filters**:
```blade
@extends('canvastack::layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.reports.title') }}</h1>
    
    {{-- Users Table with Filter --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-semibold">{{ __('ui.users.title') }}</h2>
            
            {{-- User Status Filter --}}
            <form method="GET" class="flex items-center gap-2">
                <select name="user_status" class="px-4 py-2 rounded-xl border" onchange="this.form.submit()">
                    <option value="">{{ __('ui.filters.all_statuses') }}</option>
                    @foreach($userStatuses as $value => $label)
                        <option value="{{ $value }}" @if(request('user_status') === $value) selected @endif>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $usersTable->render() !!}
        </div>
    </div>
    
    {{-- Posts Table with Filter --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-semibold">{{ __('ui.posts.title') }}</h2>
            
            {{-- Post Category Filter --}}
            <form method="GET" class="flex items-center gap-2">
                <select name="post_category" class="px-4 py-2 rounded-xl border" onchange="this.form.submit()">
                    <option value="">{{ __('ui.filters.all_categories') }}</option>
                    @foreach($postCategories as $id => $name)
                        <option value="{{ $id }}" @if(request('post_category') == $id) selected @endif>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            {!! $postsTable->render() !!}
        </div>
    </div>
</div>
@endsection
```

**Result**: Each table has independent filtering without affecting the other.

---

## 📊 Performance Metrics

### Expected Performance

With proper configuration, you should achieve:

| Metric | Target | Notes |
|--------|--------|-------|
| First table render | < 200ms | For tables with < 1K rows |
| Additional tables | < 100ms each | Cached queries |
| Memory per table | < 128MB | With pagination |
| Query count | < 10 per table | With eager loading |
| Cache hit ratio | > 80% | For repeated requests |

### Monitoring Performance

```php
// Enable query logging
DB::enableQueryLog();

// Render tables
$table1->render();
$table2->render();
$table3->render();

// Check queries
$queries = DB::getQueryLog();
dd([
    'total_queries' => count($queries),
    'queries' => $queries,
]);
```

---

## 🎓 Summary

### Key Takeaways

1. **Multiple Instances**: Create separate TableBuilder instances with `app(TableBuilder::class)`
2. **Unique IDs**: Each table automatically gets a unique, secure ID
3. **Connection Detection**: Connections auto-detected from models
4. **State Isolation**: Each table has independent sorting, filtering, pagination
5. **Performance**: Use eager loading and caching for optimal performance
6. **Security**: Automatic SQL injection and XSS prevention
7. **Responsive**: Use Tailwind grid for responsive layouts
8. **Dark Mode**: Automatic dark mode support
9. **i18n**: Use translation functions for all text
10. **Theme**: Use theme colors and fonts

### When to Use Multi-Table Without Tabs

Use this approach when:
- ✅ You want to display related data side-by-side
- ✅ You need to compare datasets
- ✅ You're building a dashboard with multiple widgets
- ✅ You want all data visible at once
- ✅ You have 2-4 tables on the page

Consider using tabs when:
- ❌ You have 5+ tables on the page
- ❌ Tables are not related
- ❌ You want to reduce initial page load time
- ❌ You want to organize data into categories

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published  
**Related**: [Tab System Usage Guide](tab-system-usage.md)

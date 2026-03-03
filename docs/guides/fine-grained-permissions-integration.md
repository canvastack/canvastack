# Fine-Grained Permissions System - Integration Guide

## 📋 Overview

This guide provides step-by-step instructions for integrating the Fine-Grained Permissions System with CanvaStack components. It covers FormBuilder, TableBuilder, and Blade directives with complete examples and best practices.

**Target Audience**: Developers integrating fine-grained permissions into applications  
**Prerequisites**: 
- Basic understanding of CanvaStack RBAC system
- Familiarity with FormBuilder and TableBuilder components
- Knowledge of Blade templating

**Version**: 1.0.0  
**Last Updated**: 2026-02-27

---

## 🎯 Integration Overview

The Fine-Grained Permissions System integrates seamlessly with three main CanvaStack components:

1. **FormBuilder** - Automatically filters form fields based on column and JSON attribute permissions
2. **TableBuilder** - Automatically filters table columns and rows based on permissions
3. **Blade Directives** - Provides conditional rendering in views based on permissions

### Integration Benefits

- ✅ **Automatic Filtering**: Components automatically respect permission rules
- ✅ **Zero Boilerplate**: Minimal code changes required
- ✅ **Consistent UI**: Uniform permission indicators across components
- ✅ **Performance**: Built-in caching for permission checks
- ✅ **Theme Integration**: Permission indicators use theme colors
- ✅ **i18n Support**: All messages are translatable

---

## 📦 Prerequisites

### Step 1: Verify Installation

Ensure fine-grained permissions are installed and configured:

```bash
# Run migrations
php artisan migrate

# Verify tables exist
php artisan tinker
>>> Schema::hasTable('permission_rules')
=> true
>>> Schema::hasTable('user_permission_overrides')
=> true
```

### Step 2: Configure System

Edit `config/canvastack-rbac.php`:

```php
'fine_grained' => [
    'enabled' => true,
    
    'cache' => [
        'enabled' => true,
        'ttl' => [
            'row' => 3600,
            'column' => 3600,
            'json_attribute' => 3600,
            'conditional' => 1800,
        ],
    ],
],
```


### Step 3: Create Permission Rules

Before integrating with components, create the necessary permission rules:

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Example: Column-level rule for post editing
$manager->addColumnRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content', 'excerpt', 'tags'],
    deniedColumns: ['status', 'featured', 'published_at']
);
```

---

## 1️⃣ FormBuilder Integration

### Overview

FormBuilder automatically filters form fields based on column-level and JSON attribute permissions. When a permission is set, FormBuilder will:

1. Check which columns the user can access
2. Hide or disable fields for inaccessible columns
3. Display permission indicators for hidden fields
4. Apply theme colors and i18n messages

### Basic Integration

#### Step 1: Set Permission in Controller

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;

public function edit(Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    
    // Enable permission filtering
    $form->setPermission('posts.edit');
    
    // Define all fields - FormBuilder will filter automatically
    $form->text('title', __('ui.labels.title'))->required();
    $form->textarea('content', __('ui.labels.content'))->required();
    $form->select('status', __('ui.labels.status'), $statuses);
    $form->checkbox('featured', __('ui.labels.featured'));
    
    return view('posts.edit', compact('form', 'post'));
}
```

#### Step 2: Render Form in View

```blade
@extends('canvastack::layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>{{ __('ui.posts.edit') }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('posts.update', $post) }}">
                @csrf
                @method('PUT')
                
                {{-- FormBuilder automatically filters fields --}}
                {!! $form->render() !!}
                
                <button type="submit" class="btn btn-primary">
                    {{ __('ui.buttons.save') }}
                </button>
            </form>
        </div>
    </div>
@endsection
```

### What Happens Automatically

When `setPermission()` is called, FormBuilder:

1. **Checks Column Access**: Queries `PermissionRuleManager` for accessible columns
2. **Filters Fields**: Only renders fields for accessible columns
3. **Shows Indicators**: Displays permission messages for hidden fields
4. **Applies Theme**: Uses theme colors for indicators
5. **Translates Messages**: Uses i18n for all text


### Real-World Example 1: User Management Form

**Scenario**: Regular admins can edit user details but not roles or permissions.

**Permission Rule**:
```php
// In a seeder or migration
$manager->addColumnRule(
    permissionId: $editUsersPermission->id,
    modelClass: User::class,
    allowedColumns: ['name', 'email', 'phone', 'address'],
    deniedColumns: ['role', 'permissions', 'is_active']
);
```

**Controller**:
```php
public function edit(User $user, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($user);
    $form->setPermission('users.edit');
    
    // All fields defined - FormBuilder filters based on permissions
    $form->text('name', __('ui.labels.name'))->required();
    $form->email('email', __('ui.labels.email'))->required();
    $form->text('phone', __('ui.labels.phone'));
    $form->textarea('address', __('ui.labels.address'));
    
    // These fields will be hidden for regular admins
    $form->select('role', __('ui.labels.role'), $roles);
    $form->multiselect('permissions', __('ui.labels.permissions'), $permissions);
    $form->checkbox('is_active', __('ui.labels.active'));
    
    return view('users.edit', compact('form', 'user'));
}
```

**Result for Regular Admin**:
- Shows: name, email, phone, address fields
- Hides: role, permissions, is_active fields
- Displays: "Some fields are hidden due to permissions" message

**Result for Super Admin** (no column restrictions):
- Shows: all fields

### Real-World Example 2: Post Metadata Form

**Scenario**: Content editors can edit social metadata but not SEO settings.

**Permission Rule**:
```php
$manager->addJsonAttributeRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['social.*', 'layout.*'],
    deniedPaths: ['seo.*', 'featured', 'promoted']
);
```

**Controller**:
```php
public function edit(Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    $form->setPermission('posts.edit');
    
    // Basic fields
    $form->text('title', __('ui.labels.title'))->required();
    $form->textarea('content', __('ui.labels.content'))->required();
    
    // JSON fields - FormBuilder filters based on JSON attribute permissions
    $form->openTab(__('ui.tabs.seo'));
    $form->text('metadata[seo][title]', __('ui.labels.seo_title'));
    $form->textarea('metadata[seo][description]', __('ui.labels.seo_description'));
    $form->tags('metadata[seo][keywords]', __('ui.labels.seo_keywords'));
    $form->closeTab();
    
    $form->openTab(__('ui.tabs.social'));
    $form->text('metadata[social][og_title]', __('ui.labels.og_title'));
    $form->textarea('metadata[social][og_description]', __('ui.labels.og_description'));
    $form->text('metadata[social][og_image]', __('ui.labels.og_image'));
    $form->closeTab();
    
    $form->openTab(__('ui.tabs.advanced'));
    $form->checkbox('metadata[featured]', __('ui.labels.featured'));
    $form->checkbox('metadata[promoted]', __('ui.labels.promoted'));
    $form->closeTab();
    
    return view('posts.edit', compact('form', 'post'));
}
```

**Result for Content Editor**:
- Shows: Social tab with all fields
- Hides: SEO tab, featured/promoted checkboxes
- Displays: Permission indicators for hidden tabs


### FormBuilder Configuration Options

#### Option 1: Hide Fields (Default)

Fields are completely hidden from the form:

```php
$form->setPermission('posts.edit');
$form->setFieldVisibility('hidden'); // Default
```

#### Option 2: Disable Fields

Fields are shown but disabled (read-only):

```php
$form->setPermission('posts.edit');
$form->setFieldVisibility('disabled');
```

**Result**:
```html
<div class="form-group">
    <label>Status <i data-lucide="lock" class="w-3 h-3 inline"></i></label>
    <input type="text" value="published" disabled class="form-control" 
           style="background: var(--cs-color-gray-100); cursor: not-allowed;">
    <small class="form-text text-muted">
        This field is read-only due to permissions
    </small>
</div>
```

#### Option 3: Show Indicators

Show permission indicators for hidden fields:

```php
$form->setPermission('posts.edit');
$form->setShowPermissionIndicators(true); // Default
```

**Result**:
```html
<div class="alert alert-info" style="background: var(--cs-color-info-light);">
    <i data-lucide="lock" class="w-4 h-4 inline"></i>
    Some fields are hidden due to permissions
</div>
```

### FormBuilder Permission Methods

```php
// Set permission for filtering
$form->setPermission(string $permission): self

// Set model for permission checks
$form->setModel(object $model): self

// Configure field visibility
$form->setFieldVisibility(string $mode): self
// Options: 'hidden', 'disabled'

// Show/hide permission indicators
$form->setShowPermissionIndicators(bool $show): self

// Get accessible columns (for custom logic)
$form->getAccessibleColumns(): array

// Get accessible JSON paths (for custom logic)
$form->getAccessibleJsonPaths(string $jsonColumn): array
```

### Custom Permission Logic

For advanced scenarios, you can manually check permissions:

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

public function edit(Post $post, FormBuilder $form, PermissionRuleManager $manager)
{
    $form->setContext('admin');
    $form->setModel($post);
    
    // Get accessible columns
    $columns = $manager->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
    
    // Conditionally add fields
    if (in_array('title', $columns)) {
        $form->text('title', __('ui.labels.title'))->required();
    }
    
    if (in_array('status', $columns)) {
        $form->select('status', __('ui.labels.status'), $statuses);
    } else {
        // Show read-only version
        $form->static('status', __('ui.labels.status'), $post->status);
    }
    
    return view('posts.edit', compact('form', 'post'));
}
```

### Validation with FormBuilder

FormBuilder automatically adjusts validation rules based on accessible fields:

```php
public function update(Request $request, Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    $form->setPermission('posts.edit');
    
    // Define form with validation
    $form->text('title', __('ui.labels.title'))->required()->maxLength(255);
    $form->textarea('content', __('ui.labels.content'))->required();
    $form->select('status', __('ui.labels.status'), $statuses)->required();
    
    // FormBuilder automatically filters validation rules
    $validated = $form->validate($request);
    
    // Only accessible fields are in $validated
    $post->update($validated);
    
    return redirect()->route('posts.index')
        ->with('success', __('ui.messages.post_updated'));
}
```


---

## 2️⃣ TableBuilder Integration

### Overview

TableBuilder automatically filters table columns and rows based on permissions. When a permission is set, TableBuilder will:

1. Check which columns the user can access
2. Hide inaccessible columns from the table
3. Apply row-level filtering to the query
4. Display permission indicators for hidden columns
5. Apply theme colors and i18n messages

### Basic Integration

#### Step 1: Set Permission in Controller

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Post());
    
    // Enable permission filtering
    $table->setPermission('posts.view');
    
    // Define all columns - TableBuilder will filter automatically
    $table->setFields([
        'title:' . __('ui.labels.title'),
        'author:' . __('ui.labels.author'),
        'status:' . __('ui.labels.status'),
        'featured:' . __('ui.labels.featured'),
        'created_at:' . __('ui.labels.created')
    ]);
    
    $table->addAction('edit', route('posts.edit', ':id'), 'edit', __('ui.buttons.edit'));
    $table->addAction('delete', route('posts.destroy', ':id'), 'trash', __('ui.buttons.delete'), 'DELETE');
    
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

#### Step 2: Render Table in View

```blade
@extends('canvastack::layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>{{ __('ui.posts.list') }}</h3>
            <a href="{{ route('posts.create') }}" class="btn btn-primary">
                {{ __('ui.buttons.create') }}
            </a>
        </div>
        <div class="card-body">
            {{-- TableBuilder automatically filters columns and rows --}}
            {!! $table->render() !!}
        </div>
    </div>
@endsection
```

### What Happens Automatically

When `setPermission()` is called, TableBuilder:

1. **Checks Column Access**: Queries `PermissionRuleManager` for accessible columns
2. **Filters Columns**: Only renders columns user can access
3. **Applies Row Filtering**: Uses `scopeByPermission()` to filter rows at database level
4. **Shows Indicators**: Displays message when columns are hidden
5. **Applies Theme**: Uses theme colors for indicators
6. **Translates Messages**: Uses i18n for all text

### Real-World Example 1: User List

**Scenario**: Regular admins can view user names and emails, but not roles or permissions.

**Permission Rules**:
```php
// Column-level rule
$manager->addColumnRule(
    permissionId: $viewUsersPermission->id,
    modelClass: User::class,
    allowedColumns: ['name', 'email', 'created_at'],
    deniedColumns: ['role', 'permissions', 'password']
);

// Row-level rule (optional) - only show active users
$manager->addRowRule(
    permissionId: $viewUsersPermission->id,
    modelClass: User::class,
    conditions: ['is_active' => true]
);
```

**Controller**:
```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setPermission('users.view');
    
    // Define all columns
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
        'role:' . __('ui.labels.role'),
        'permissions:' . __('ui.labels.permissions'),
        'created_at:' . __('ui.labels.created')
    ]);
    
    $table->addAction('edit', route('users.edit', ':id'), 'edit', __('ui.buttons.edit'));
    
    $table->format();
    
    return view('users.index', compact('table'));
}
```

**Result for Regular Admin**:
- Shows: name, email, created_at columns
- Hides: role, permissions columns
- Displays: "2 columns hidden due to permissions" message
- Filters: Only shows active users (row-level filtering)


### Real-World Example 2: Financial Reports

**Scenario**: Sales staff can view invoice numbers and totals, but not cost or profit data.

**Permission Rule**:
```php
$manager->addColumnRule(
    permissionId: $viewInvoicesPermission->id,
    modelClass: Invoice::class,
    allowedColumns: ['invoice_number', 'customer_name', 'date', 'total'],
    deniedColumns: ['cost', 'profit', 'commission', 'internal_notes']
);
```

**Controller**:
```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Invoice());
    $table->setPermission('invoices.view');
    
    $table->setFields([
        'invoice_number:' . __('ui.labels.invoice_number'),
        'customer_name:' . __('ui.labels.customer'),
        'date:' . __('ui.labels.date'),
        'total:' . __('ui.labels.total'),
        'cost:' . __('ui.labels.cost'),
        'profit:' . __('ui.labels.profit'),
        'commission:' . __('ui.labels.commission')
    ]);
    
    // Format currency columns
    $table->setRightColumns(['total', 'cost', 'profit', 'commission']);
    
    $table->format();
    
    return view('invoices.index', compact('table'));
}
```

**Result for Sales Staff**:
- Shows: invoice_number, customer_name, date, total
- Hides: cost, profit, commission
- Displays: "3 columns hidden due to permissions" message

### Real-World Example 3: Multi-Tenant Data

**Scenario**: Users can only view data from their organization.

**Permission Rule**:
```php
// Row-level rule for organization isolation
$manager->addRowRule(
    permissionId: $viewCustomersPermission->id,
    modelClass: Customer::class,
    conditions: ['organization_id' => '{{auth.organization}}']
);
```

**Controller**:
```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Customer());
    $table->setPermission('customers.view');
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
        'phone:' . __('ui.labels.phone'),
        'organization:' . __('ui.labels.organization'),
        'created_at:' . __('ui.labels.created')
    ]);
    
    $table->format();
    
    return view('customers.index', compact('table'));
}
```

**Result**:
- Shows: All columns (no column restrictions)
- Filters: Only shows customers from user's organization (row-level filtering)
- Query: Automatically adds `WHERE organization_id = ?` to query

### TableBuilder Configuration Options

#### Option 1: Show Column Indicators (Default)

Display message when columns are hidden:

```php
$table->setPermission('posts.view');
$table->setShowPermissionIndicators(true); // Default
```

**Result**:
```html
<div class="alert alert-info mb-4">
    <i data-lucide="eye-off" class="w-4 h-4 inline"></i>
    2 columns are hidden due to permissions
</div>
```

#### Option 2: Hide Column Indicators

Don't show any permission messages:

```php
$table->setPermission('posts.view');
$table->setShowPermissionIndicators(false);
```

#### Option 3: Custom Row Actions Based on Permissions

Show different actions based on row-level permissions:

```php
$table->setPermission('posts.view');

// Add conditional actions
$table->setActions([
    'edit' => [
        'label' => __('ui.buttons.edit'),
        'icon' => 'edit',
        'url' => fn($row) => route('posts.edit', $row->id),
        'condition' => fn($row) => Gate::canAccessRow(auth()->id(), 'posts.edit', $row),
    ],
    'delete' => [
        'label' => __('ui.buttons.delete'),
        'icon' => 'trash',
        'url' => fn($row) => route('posts.destroy', $row->id),
        'method' => 'DELETE',
        'condition' => fn($row) => Gate::canAccessRow(auth()->id(), 'posts.delete', $row),
    ],
]);
```

**Result**:
- Edit button only shown for rows user can edit
- Delete button only shown for rows user can delete
- Lock icon shown for rows user cannot access


### TableBuilder Permission Methods

```php
// Set permission for filtering
$table->setPermission(string $permission): self

// Show/hide permission indicators
$table->setShowPermissionIndicators(bool $show): self

// Get accessible columns (for custom logic)
$table->getAccessibleColumns(): array

// Apply row-level filtering manually
$table->applyRowFiltering(Builder $query): Builder
```

### Performance Optimization

TableBuilder automatically optimizes queries with permissions:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setPermission('posts.view');
    
    // Eager load relationships (prevents N+1)
    $table->eager(['user', 'category']);
    
    // Enable caching (caches filtered results)
    $table->cache(300); // 5 minutes
    
    // Apply row filtering at database level
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

**Generated Query** (with row-level filtering):
```sql
SELECT posts.* 
FROM posts 
WHERE posts.user_id = ? 
  AND posts.status = 'published'
LIMIT 20 OFFSET 0
```

### Custom Column Rendering with Permissions

```php
public function index(TableBuilder $table, PermissionRuleManager $manager)
{
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setPermission('posts.view');
    
    $table->setFields([
        'title:' . __('ui.labels.title'),
        'status:' . __('ui.labels.status'),
        'featured:' . __('ui.labels.featured'),
    ]);
    
    // Check if user can access 'status' column
    $columns = $manager->getAccessibleColumns(
        auth()->id(),
        'posts.view',
        Post::class
    );
    
    if (in_array('status', $columns)) {
        // Custom renderer for status column
        $table->setColumnRenderer('status', function($row) {
            return match($row->status) {
                'draft' => '<span class="badge badge-warning">Draft</span>',
                'published' => '<span class="badge badge-success">Published</span>',
                'archived' => '<span class="badge badge-error">Archived</span>',
            };
        });
    }
    
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

---

## 3️⃣ Blade Directives Integration

### Overview

Blade directives provide fine-grained permission checks directly in views. Use them for:

- Conditional rendering of UI elements
- Showing/hiding action buttons
- Displaying permission-specific content
- Custom permission indicators

### Available Directives

#### @canAccessRow

Check if user can access a specific row.

**Syntax**:
```blade
@canAccessRow($permission, $model)
    {{-- Content shown if access allowed --}}
@endcanAccessRow
```

**Example**:
```blade
<tr>
    <td>{{ $post->title }}</td>
    <td>{{ $post->author }}</td>
    <td>
        @canAccessRow('posts.edit', $post)
            <a href="{{ route('posts.edit', $post) }}" class="btn btn-sm btn-primary">
                {{ __('ui.buttons.edit') }}
            </a>
        @else
            <span class="text-muted">
                <i data-lucide="lock" class="w-4 h-4 inline"></i>
                {{ __('rbac.fine_grained.no_access') }}
            </span>
        @endcanAccessRow
        
        @canAccessRow('posts.delete', $post)
            <form method="POST" action="{{ route('posts.destroy', $post) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-error">
                    {{ __('ui.buttons.delete') }}
                </button>
            </form>
        @endcanAccessRow
    </td>
</tr>
```


#### @canAccessColumn

Check if user can access a specific column.

**Syntax**:
```blade
@canAccessColumn($permission, $model, $column)
    {{-- Content shown if access allowed --}}
@endcanAccessColumn
```

**Example**:
```blade
<div class="form-group">
    <label>{{ __('ui.labels.title') }}</label>
    <input type="text" name="title" value="{{ $post->title }}" class="form-control">
</div>

@canAccessColumn('posts.edit', $post, 'status')
    <div class="form-group">
        <label>{{ __('ui.labels.status') }}</label>
        <select name="status" class="form-control">
            <option value="draft" {{ $post->status === 'draft' ? 'selected' : '' }}>
                {{ __('ui.status.draft') }}
            </option>
            <option value="published" {{ $post->status === 'published' ? 'selected' : '' }}>
                {{ __('ui.status.published') }}
            </option>
        </select>
    </div>
@else
    <div class="alert alert-info">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.field_readonly', ['field' => __('ui.labels.status')]) }}
    </div>
@endcanAccessColumn

@canAccessColumn('posts.edit', $post, 'featured')
    <div class="form-group">
        <label>
            <input type="checkbox" name="featured" value="1" 
                   {{ $post->featured ? 'checked' : '' }}>
            {{ __('ui.labels.featured') }}
        </label>
    </div>
@endcanAccessColumn
```

#### @canAccessJsonAttribute

Check if user can access a specific JSON attribute.

**Syntax**:
```blade
@canAccessJsonAttribute($permission, $model, $jsonColumn, $path)
    {{-- Content shown if access allowed --}}
@endcanAccessJsonAttribute
```

**Example**:
```blade
{{-- SEO Fields --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <div class="form-group">
        <label>{{ __('ui.labels.seo_title') }}</label>
        <input type="text" 
               name="metadata[seo][title]" 
               value="{{ $post->metadata['seo']['title'] ?? '' }}" 
               class="form-control"
               maxlength="60">
    </div>
@endcanAccessJsonAttribute

@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.description')
    <div class="form-group">
        <label>{{ __('ui.labels.seo_description') }}</label>
        <textarea name="metadata[seo][description]" 
                  class="form-control" 
                  rows="3" 
                  maxlength="160">{{ $post->metadata['seo']['description'] ?? '' }}</textarea>
    </div>
@endcanAccessJsonAttribute

{{-- Social Media Fields --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.og_title')
    <div class="form-group">
        <label>{{ __('ui.labels.og_title') }}</label>
        <input type="text" 
               name="metadata[social][og_title]" 
               value="{{ $post->metadata['social']['og_title'] ?? '' }}" 
               class="form-control">
    </div>
@endcanAccessJsonAttribute

{{-- Featured Flag --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'featured')
    <div class="form-group">
        <label>
            <input type="checkbox" 
                   name="metadata[featured]" 
                   value="1"
                   {{ ($post->metadata['featured'] ?? false) ? 'checked' : '' }}>
            {{ __('ui.labels.featured') }}
        </label>
    </div>
@else
    <div class="alert alert-info">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.featured')]) }}
    </div>
@endcanAccessJsonAttribute
```

### Real-World Example 1: Post Edit Form

Complete form with all directive types:

```blade
@extends('canvastack::layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>{{ __('ui.posts.edit') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('posts.update', $post) }}">
            @csrf
            @method('PUT')
            
            {{-- Basic Fields (always shown) --}}
            <div class="form-group">
                <label>{{ __('ui.labels.title') }}</label>
                <input type="text" name="title" value="{{ $post->title }}" 
                       class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>{{ __('ui.labels.content') }}</label>
                <textarea name="content" class="form-control" rows="10" required>{{ $post->content }}</textarea>
            </div>
            
            {{-- Column-Level Permission Check --}}
            @canAccessColumn('posts.edit', $post, 'status')
                <div class="form-group">
                    <label>{{ __('ui.labels.status') }}</label>
                    <select name="status" class="form-control">
                        <option value="draft">{{ __('ui.status.draft') }}</option>
                        <option value="published">{{ __('ui.status.published') }}</option>
                    </select>
                </div>
            @endcanAccessColumn
            
            @canAccessColumn('posts.edit', $post, 'featured')
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" value="1">
                        {{ __('ui.labels.featured') }}
                    </label>
                </div>
            @endcanAccessColumn
            
            {{-- JSON Attribute Permission Checks --}}
            <h4>{{ __('ui.sections.seo') }}</h4>
            
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
                <div class="form-group">
                    <label>{{ __('ui.labels.seo_title') }}</label>
                    <input type="text" name="metadata[seo][title]" 
                           value="{{ $post->metadata['seo']['title'] ?? '' }}" 
                           class="form-control">
                </div>
            @else
                <div class="alert alert-warning">
                    <i data-lucide="lock" class="w-4 h-4 inline"></i>
                    {{ __('rbac.fine_grained.section_restricted', ['section' => __('ui.sections.seo')]) }}
                </div>
            @endcanAccessJsonAttribute
            
            <button type="submit" class="btn btn-primary">
                {{ __('ui.buttons.save') }}
            </button>
        </form>
    </div>
</div>
@endsection
```


### Real-World Example 2: Data Table with Row Actions

```blade
@extends('canvastack::layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>{{ __('ui.posts.list') }}</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('ui.labels.title') }}</th>
                    <th>{{ __('ui.labels.author') }}</th>
                    <th>{{ __('ui.labels.status') }}</th>
                    <th>{{ __('ui.labels.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                    <tr>
                        <td>{{ $post->title }}</td>
                        <td>{{ $post->author->name }}</td>
                        <td>
                            @canAccessColumn('posts.view', $post, 'status')
                                <span class="badge badge-{{ $post->status === 'published' ? 'success' : 'warning' }}">
                                    {{ __('ui.status.' . $post->status) }}
                                </span>
                            @else
                                <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                            @endcanAccessColumn
                        </td>
                        <td>
                            @canAccessRow('posts.edit', $post)
                                <a href="{{ route('posts.edit', $post) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                    {{ __('ui.buttons.edit') }}
                                </a>
                            @else
                                <button class="btn btn-sm btn-disabled" disabled>
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                    {{ __('ui.buttons.edit') }}
                                </button>
                            @endcanAccessRow
                            
                            @canAccessRow('posts.delete', $post)
                                <form method="POST" 
                                      action="{{ route('posts.destroy', $post) }}" 
                                      class="inline"
                                      onsubmit="return confirm('{{ __('ui.messages.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-error">
                                        <i data-lucide="trash" class="w-4 h-4"></i>
                                        {{ __('ui.buttons.delete') }}
                                    </button>
                                </form>
                            @endcanAccessRow
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
```

### Real-World Example 3: Dashboard with Conditional Widgets

```blade
@extends('canvastack::layouts.admin')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Total Posts Widget (always shown) --}}
    <div class="card">
        <div class="card-body">
            <h3>{{ __('ui.dashboard.total_posts') }}</h3>
            <p class="text-3xl font-bold">{{ $totalPosts }}</p>
        </div>
    </div>
    
    {{-- Revenue Widget (only if user can access financial data) --}}
    @canAccessColumn('dashboard.view', $dashboard, 'revenue')
        <div class="card">
            <div class="card-body">
                <h3>{{ __('ui.dashboard.revenue') }}</h3>
                <p class="text-3xl font-bold">${{ number_format($revenue, 2) }}</p>
            </div>
        </div>
    @endcanAccessColumn
    
    {{-- Profit Widget (only if user can access financial data) --}}
    @canAccessColumn('dashboard.view', $dashboard, 'profit')
        <div class="card">
            <div class="card-body">
                <h3>{{ __('ui.dashboard.profit') }}</h3>
                <p class="text-3xl font-bold">${{ number_format($profit, 2) }}</p>
            </div>
        </div>
    @endcanAccessColumn
</div>

{{-- Recent Activity (with row-level filtering) --}}
<div class="card mt-6">
    <div class="card-header">
        <h3>{{ __('ui.dashboard.recent_activity') }}</h3>
    </div>
    <div class="card-body">
        <ul>
            @foreach($activities as $activity)
                @canAccessRow('activities.view', $activity)
                    <li>
                        {{ $activity->description }}
                        <span class="text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                    </li>
                @endcanAccessRow
            @endforeach
        </ul>
    </div>
</div>
@endsection
```

### Combining Directives with Alpine.js

```blade
<div x-data="{ 
    showAdvanced: false,
    canEditStatus: {{ Gate::canAccessColumn(auth()->id(), 'posts.edit', $post, 'status') ? 'true' : 'false' }}
}">
    {{-- Basic Fields --}}
    <div class="form-group">
        <label>{{ __('ui.labels.title') }}</label>
        <input type="text" name="title" value="{{ $post->title }}" class="form-control">
    </div>
    
    {{-- Advanced Toggle --}}
    <button type="button" 
            @click="showAdvanced = !showAdvanced"
            class="btn btn-secondary">
        {{ __('ui.buttons.show_advanced') }}
    </button>
    
    {{-- Advanced Fields (with permission check) --}}
    <div x-show="showAdvanced" x-cloak>
        @canAccessColumn('posts.edit', $post, 'status')
            <div class="form-group">
                <label>{{ __('ui.labels.status') }}</label>
                <select name="status" class="form-control">
                    <option value="draft">{{ __('ui.status.draft') }}</option>
                    <option value="published">{{ __('ui.status.published') }}</option>
                </select>
            </div>
        @else
            <div class="alert alert-info">
                <i data-lucide="lock" class="w-4 h-4 inline"></i>
                {{ __('rbac.fine_grained.field_readonly', ['field' => __('ui.labels.status')]) }}
            </div>
        @endcanAccessColumn
    </div>
</div>
```


### Performance Considerations

Blade directives make permission checks on every render. For better performance:

#### 1. Cache Permission Checks

```blade
@php
    // Cache permission checks at the top of the view
    $canEditStatus = Gate::canAccessColumn(auth()->id(), 'posts.edit', $post, 'status');
    $canEditFeatured = Gate::canAccessColumn(auth()->id(), 'posts.edit', $post, 'featured');
    $canViewRevenue = Gate::canAccessColumn(auth()->id(), 'dashboard.view', $dashboard, 'revenue');
@endphp

{{-- Use cached results --}}
@if($canEditStatus)
    <div class="form-group">
        <label>{{ __('ui.labels.status') }}</label>
        <select name="status" class="form-control">...</select>
    </div>
@endif

@if($canEditFeatured)
    <div class="form-group">
        <label>{{ __('ui.labels.featured') }}</label>
        <input type="checkbox" name="featured">
    </div>
@endif
```

#### 2. Batch Permission Checks in Controller

```php
public function edit(Post $post, PermissionRuleManager $manager)
{
    // Get all accessible columns once
    $accessibleColumns = $manager->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
    
    // Pass to view
    return view('posts.edit', [
        'post' => $post,
        'accessibleColumns' => $accessibleColumns,
    ]);
}
```

**View**:
```blade
@if(in_array('status', $accessibleColumns))
    <div class="form-group">
        <label>{{ __('ui.labels.status') }}</label>
        <select name="status" class="form-control">...</select>
    </div>
@endif

@if(in_array('featured', $accessibleColumns))
    <div class="form-group">
        <label>{{ __('ui.labels.featured') }}</label>
        <input type="checkbox" name="featured">
    </div>
@endif
```

#### 3. Use View Composers

```php
// In AppServiceProvider or ViewServiceProvider
View::composer('posts.*', function ($view) {
    $post = $view->getData()['post'] ?? null;
    
    if ($post) {
        $manager = app(PermissionRuleManager::class);
        
        $view->with('accessibleColumns', $manager->getAccessibleColumns(
            auth()->id(),
            'posts.edit',
            Post::class
        ));
    }
});
```

---

## 🎨 Theme Integration

All permission indicators automatically use theme colors and support dark mode.

### Permission Indicator Styling

**Hidden Field Indicator**:
```blade
<div class="alert alert-info" 
     style="background: @themeColor('info-light'); 
            color: @themeColor('info-dark'); 
            border: 1px solid @themeColor('info');">
    <i data-lucide="lock" class="w-4 h-4 inline"></i>
    {{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.status')]) }}
</div>
```

**Disabled Field Indicator**:
```blade
<div class="form-group">
    <label class="form-label">
        {{ __('ui.labels.status') }}
        <i data-lucide="lock" class="w-3 h-3 inline text-gray-400"></i>
    </label>
    <input type="text" 
           class="form-control" 
           value="{{ $post->status }}" 
           disabled
           style="background: @themeColor('gray-100'); 
                  cursor: not-allowed;">
    <small class="form-text text-muted">
        {{ __('rbac.fine_grained.field_readonly') }}
    </small>
</div>
```

**Hidden Column Message**:
```blade
<div class="alert alert-info mb-4" 
     style="background: @themeColor('info-light'); 
            color: @themeColor('info-dark');">
    <i data-lucide="eye-off" class="w-4 h-4 inline"></i>
    {{ __('rbac.fine_grained.columns_hidden', ['count' => $hiddenColumnsCount]) }}
</div>
```

### Dark Mode Support

All permission indicators automatically support dark mode:

```blade
<div class="alert alert-info 
            bg-blue-50 dark:bg-blue-900 
            text-blue-900 dark:text-blue-100 
            border-blue-200 dark:border-blue-800">
    <i data-lucide="lock" class="w-4 h-4 inline"></i>
    {{ __('rbac.fine_grained.field_hidden') }}
</div>
```

---

## 🌍 Internationalization (i18n)

All permission messages use i18n for multi-language support.

### Required Translation Keys

Add these keys to `resources/lang/{locale}/rbac.php`:

```php
return [
    'fine_grained' => [
        // Field messages
        'field_hidden' => 'The :field field is hidden due to permissions',
        'field_readonly' => 'This field is read-only due to permissions',
        'fields_hidden' => 'Some fields are hidden due to permissions',
        
        // Column messages
        'column_hidden' => 'This column is hidden due to permissions',
        'columns_hidden' => ':count columns are hidden due to permissions',
        
        // Section messages
        'section_restricted' => 'The :section section is restricted',
        'section_readonly' => 'The :section section is read-only',
        
        // Access messages
        'no_access' => 'No access',
        'limited_access' => 'Limited access',
        'read_only' => 'Read only',
    ],
];
```

### Usage in Views

```blade
{{-- With parameter substitution --}}
<div class="alert alert-info">
    {{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.status')]) }}
</div>

{{-- With count --}}
<div class="alert alert-info">
    {{ __('rbac.fine_grained.columns_hidden', ['count' => 3]) }}
</div>

{{-- Simple message --}}
<div class="alert alert-info">
    {{ __('rbac.fine_grained.no_access') }}
</div>
```


---

## 🧪 Testing Integration

### Testing FormBuilder Integration

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

public function test_form_filters_fields_based_on_column_permissions()
{
    // Create permission rule
    $manager = app(PermissionRuleManager::class);
    $manager->addColumnRule(
        $this->permission->id,
        Post::class,
        ['title', 'content'],
        ['status', 'featured']
    );
    
    // Create form
    $form = app(FormBuilder::class);
    $form->setContext('admin');
    $form->setModel($this->post);
    $form->setPermission('posts.edit');
    
    $form->text('title', 'Title');
    $form->textarea('content', 'Content');
    $form->select('status', 'Status', []);
    $form->checkbox('featured', 'Featured');
    
    // Render form
    $html = $form->render();
    
    // Assert accessible fields are shown
    $this->assertStringContainsString('name="title"', $html);
    $this->assertStringContainsString('name="content"', $html);
    
    // Assert inaccessible fields are hidden
    $this->assertStringNotContainsString('name="status"', $html);
    $this->assertStringNotContainsString('name="featured"', $html);
    
    // Assert permission indicator is shown
    $this->assertStringContainsString('fields are hidden due to permissions', $html);
}
```

### Testing TableBuilder Integration

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function test_table_filters_columns_based_on_permissions()
{
    // Create permission rule
    $manager = app(PermissionRuleManager::class);
    $manager->addColumnRule(
        $this->permission->id,
        Post::class,
        ['title', 'author'],
        ['status', 'featured']
    );
    
    // Create table
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setPermission('posts.view');
    
    $table->setFields([
        'title:Title',
        'author:Author',
        'status:Status',
        'featured:Featured',
    ]);
    
    $table->format();
    
    // Render table
    $html = $table->render();
    
    // Assert accessible columns are shown
    $this->assertStringContainsString('Title', $html);
    $this->assertStringContainsString('Author', $html);
    
    // Assert inaccessible columns are hidden
    $this->assertStringNotContainsString('Status', $html);
    $this->assertStringNotContainsString('Featured', $html);
    
    // Assert permission indicator is shown
    $this->assertStringContainsString('columns are hidden', $html);
}

public function test_table_applies_row_level_filtering()
{
    // Create row-level rule
    $manager = app(PermissionRuleManager::class);
    $manager->addRowRule(
        $this->permission->id,
        Post::class,
        ['user_id' => '{{auth.id}}']
    );
    
    // Create posts
    $userPost = Post::factory()->create(['user_id' => $this->user->id]);
    $otherPost = Post::factory()->create(['user_id' => 999]);
    
    // Create table
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setModel(new Post());
    $table->setPermission('posts.view');
    $table->setFields(['title:Title']);
    $table->format();
    
    // Get filtered data
    $data = $table->getData();
    
    // Assert only user's posts are included
    $this->assertCount(1, $data);
    $this->assertEquals($userPost->id, $data[0]->id);
}
```

### Testing Blade Directives

```php
public function test_can_access_row_directive()
{
    // Create row-level rule
    $manager = app(PermissionRuleManager::class);
    $manager->addRowRule(
        $this->permission->id,
        Post::class,
        ['user_id' => '{{auth.id}}']
    );
    
    // User's own post
    $userPost = Post::factory()->create(['user_id' => $this->user->id]);
    
    // Other user's post
    $otherPost = Post::factory()->create(['user_id' => 999]);
    
    // Test directive with user's post
    $view = view('test-view', ['post' => $userPost])->render();
    $this->assertStringContainsString('Edit Button', $view);
    
    // Test directive with other's post
    $view = view('test-view', ['post' => $otherPost])->render();
    $this->assertStringNotContainsString('Edit Button', $view);
}
```

**Test View** (`resources/views/test-view.blade.php`):
```blade
@canAccessRow('posts.edit', $post)
    <button>Edit Button</button>
@endcanAccessRow
```

---

## 🚀 Best Practices

### 1. Always Set Permissions Early

Set permissions in the controller before defining fields/columns:

```php
// ✅ Good
$form->setPermission('posts.edit');
$form->text('title', 'Title');
$form->select('status', 'Status', []);

// ❌ Bad
$form->text('title', 'Title');
$form->select('status', 'Status', []);
$form->setPermission('posts.edit'); // Too late!
```

### 2. Use Consistent Permission Names

Use consistent naming across your application:

```php
// ✅ Good - consistent pattern
'posts.view'
'posts.edit'
'posts.delete'
'users.view'
'users.edit'

// ❌ Bad - inconsistent
'view-posts'
'editPost'
'delete_posts'
```

### 3. Cache Permission Checks in Views

For views with many permission checks, cache results:

```blade
@php
    $columns = app(PermissionRuleManager::class)->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
@endphp

@if(in_array('status', $columns))
    {{-- Field --}}
@endif
```

### 4. Provide Clear Permission Indicators

Always show users why fields are hidden:

```blade
@canAccessColumn('posts.edit', $post, 'status')
    {{-- Field --}}
@else
    <div class="alert alert-info">
        <i data-lucide="lock"></i>
        {{ __('rbac.fine_grained.field_readonly', ['field' => __('ui.labels.status')]) }}
    </div>
@endcanAccessColumn
```

### 5. Test Permission Integration

Always write tests for permission integration:

```php
public function test_form_respects_column_permissions()
{
    // Setup permissions
    // Create form
    // Assert correct fields shown/hidden
}
```

### 6. Use Theme Colors

Always use theme colors for permission indicators:

```blade
<div style="background: @themeColor('info-light'); color: @themeColor('info-dark');">
    {{ __('rbac.fine_grained.field_hidden') }}
</div>
```

### 7. Support i18n

Always use translation keys for messages:

```blade
{{-- ✅ Good --}}
{{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.status')]) }}

{{-- ❌ Bad --}}
The status field is hidden due to permissions
```


---

## 🔧 Troubleshooting

### Issue 1: Fields Not Being Filtered

**Problem**: FormBuilder shows all fields even with `setPermission()` called.

**Solution**:
1. Verify permission rule exists:
```php
$rules = PermissionRule::where('permission_id', $permissionId)->get();
dd($rules); // Should not be empty
```

2. Verify `setPermission()` is called before defining fields:
```php
// ✅ Correct order
$form->setPermission('posts.edit');
$form->text('title', 'Title');

// ❌ Wrong order
$form->text('title', 'Title');
$form->setPermission('posts.edit');
```

3. Check cache:
```php
app(PermissionRuleManager::class)->clearRuleCache();
```

### Issue 2: Table Shows All Rows

**Problem**: TableBuilder doesn't filter rows based on row-level permissions.

**Solution**:
1. Verify row-level rule exists:
```php
$rules = PermissionRule::where('rule_type', 'row')->get();
dd($rules);
```

2. Check template variable resolution:
```php
$resolver = app(TemplateVariableResolver::class);
$resolved = $resolver->resolve('{{auth.id}}');
dd($resolved); // Should be current user ID
```

3. Verify `setPermission()` is called:
```php
$table->setPermission('posts.view'); // Required!
$table->format();
```

### Issue 3: Blade Directives Not Working

**Problem**: `@canAccessRow` always shows content or never shows content.

**Solution**:
1. Verify directive syntax:
```blade
{{-- ✅ Correct --}}
@canAccessRow('posts.edit', $post)
    Content
@endcanAccessRow

{{-- ❌ Wrong --}}
@canAccessRow($post, 'posts.edit')
    Content
@endcanAccessRow
```

2. Check if basic permission exists:
```php
Gate::allows('posts.edit', auth()->id()); // Should be true
```

3. Clear view cache:
```bash
php artisan view:clear
```

### Issue 4: Permission Indicators Not Showing

**Problem**: No messages shown when fields are hidden.

**Solution**:
1. Enable indicators:
```php
$form->setShowPermissionIndicators(true);
$table->setShowPermissionIndicators(true);
```

2. Verify translation keys exist:
```php
// In resources/lang/en/rbac.php
'fine_grained' => [
    'field_hidden' => 'Field is hidden',
    'columns_hidden' => ':count columns hidden',
],
```

3. Check theme colors are defined:
```php
// In config/canvastack-ui.php
'colors' => [
    'info' => '#3b82f6',
    'info-light' => '#dbeafe',
    'info-dark' => '#1e40af',
],
```

### Issue 5: Performance Issues

**Problem**: Pages load slowly with many permission checks.

**Solution**:
1. Enable caching:
```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'cache' => [
        'enabled' => true,
        'ttl' => [
            'row' => 3600,
            'column' => 3600,
        ],
    ],
],
```

2. Warm up cache on login:
```php
// In LoginController
$manager->warmUpCache(auth()->id(), [
    'posts.view',
    'posts.edit',
    'users.view',
]);
```

3. Use batch permission checks:
```php
// ✅ Good - single call
$columns = $manager->getAccessibleColumns(auth()->id(), 'posts.edit', Post::class);

// ❌ Bad - multiple calls
foreach ($fields as $field) {
    $canAccess = $gate->canAccessColumn(auth()->id(), 'posts.edit', $post, $field);
}
```

### Issue 6: JSON Attribute Permissions Not Working

**Problem**: JSON fields not being filtered.

**Solution**:
1. Verify JSON column exists and is cast:
```php
// In Post model
protected $casts = [
    'metadata' => 'array',
];
```

2. Check path separator configuration:
```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'json_attribute' => [
        'path_separator' => '.', // Should match your paths
    ],
],
```

3. Verify wildcard patterns:
```php
// ✅ Correct patterns
'seo.*'        // Matches seo.title, seo.description
'social.*'     // Matches social.og_title, social.og_image

// ❌ Wrong patterns
'seo*'         // Won't match nested paths
'seo.title*'   // Too specific
```

---

## 📚 Additional Resources

### Documentation
- [Usage Guide](usage-guide.md) - Complete usage examples for all permission types
- [API Reference](api-reference.md) - Complete API documentation
- [Quick Start](quick-start.md) - Quick setup guide

### Related Components
- [FormBuilder Documentation](../../docs/components/form-builder.md)
- [TableBuilder Documentation](../../docs/components/table-builder.md)
- [RBAC System Documentation](../../docs/features/rbac.md)

### Examples
- [Real-World Scenarios](usage-guide.md#real-world-examples)
- [Test Examples](../../tests/Feature/Auth/RBAC/)

---

## 📞 Support

### Questions About Integration

- Check this integration guide
- Review usage guide for examples
- Check API reference for method signatures
- Review test files for integration examples

### Reporting Issues

- Use GitHub issues for integration bugs
- Tag with `integration` label
- Provide code examples
- Include error messages

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Published  
**Author**: CanvaStack Team


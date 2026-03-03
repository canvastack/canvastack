# Fine-Grained Permissions System - Usage Guide

## 📋 Overview

This guide provides comprehensive documentation on how to use the Fine-Grained Permissions System in CanvaStack. It covers all four permission types with real-world examples, best practices, and common patterns.

**Target Audience**: Developers implementing fine-grained access control  
**Prerequisites**: Basic understanding of CanvaStack RBAC system  
**Version**: 1.0.0  
**Last Updated**: 2026-02-27

---

## 🎯 What is Fine-Grained Permissions?

Fine-Grained Permissions extends CanvaStack's basic RBAC system with four types of granular access control:

1. **Row-Level Permissions** - Control access to specific data rows
2. **Column-Level Permissions** - Control access to specific fields
3. **JSON Attribute Permissions** - Control access to nested JSON fields
4. **Conditional Permissions** - Rule-based dynamic access control

### When to Use Fine-Grained Permissions

Use fine-grained permissions when you need:

- ✅ Users to only access their own data
- ✅ Different roles to see different fields
- ✅ Dynamic access based on data state
- ✅ Multi-tenant data isolation
- ✅ Field-level security compliance
- ✅ Complex authorization rules

### When NOT to Use

Don't use fine-grained permissions for:

- ❌ Simple page-level access (use basic RBAC)
- ❌ Public data (no restrictions needed)
- ❌ Performance-critical paths (adds overhead)
- ❌ Simple yes/no authorization

---

## 📦 Installation & Setup

### Step 1: Run Migrations

```bash
php artisan migrate
```

This creates two tables:
- `permission_rules` - Stores fine-grained rules
- `user_permission_overrides` - Stores user-specific exceptions

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

### Step 3: Verify Installation

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);
// Ready to use!
```

---

## 1️⃣ Row-Level Permissions

### Overview

Row-level permissions control access to specific data rows based on conditions. Users can only access rows that match the defined criteria.

### Use Cases

- Users can only edit their own posts
- Managers can only view their department's data
- Sales reps can only access their assigned accounts
- Multi-tenant data isolation

### Basic Usage

#### Creating a Row-Level Rule

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Users can only edit their own posts
$manager->addRowRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    conditions: [
        'user_id' => '{{auth.id}}'
    ],
    operator: 'AND'
);
```

#### Checking Row Access

```php
use Canvastack\Canvastack\Auth\RBAC\Gate;

$gate = app(Gate::class);
$post = Post::find(1);

if ($gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
    // User can edit this post
    $post->update($data);
} else {
    // Access denied
    abort(403);
}
```

#### Using in Controllers

```php
public function edit(Post $post, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        abort(403, 'You cannot edit this post');
    }
    
    return view('posts.edit', compact('post'));
}
```

### Real-World Example 1: Blog System

**Scenario**: Authors can only edit their own posts, but editors can edit any post.

```php
// For authors - only their own posts
$manager->addRowRule(
    permissionId: $authorEditPermission->id,
    modelClass: Post::class,
    conditions: [
        'user_id' => '{{auth.id}}'
    ]
);

// For editors - no row restrictions (handled by basic permission)
// Just grant the permission without row rules
```

**Usage**:
```php
public function update(Request $request, Post $post, Gate $gate)
{
    // Check basic permission first
    if (!$gate->allows('posts.edit', auth()->id())) {
        abort(403, 'No permission to edit posts');
    }
    
    // Check row-level access
    if (!$gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        abort(403, 'You can only edit your own posts');
    }
    
    $post->update($request->validated());
    
    return redirect()->route('posts.index')
        ->with('success', 'Post updated successfully');
}
```

### Real-World Example 2: Multi-Tenant SaaS

**Scenario**: Users can only access data from their organization.

```php
// Add row rule for organization isolation
$manager->addRowRule(
    permissionId: $viewCustomersPermission->id,
    modelClass: Customer::class,
    conditions: [
        'organization_id' => '{{auth.organization}}'
    ]
);
```

**Usage with Query Scopes**:
```php
public function index(PermissionRuleManager $manager)
{
    // Apply row-level filtering to query
    $query = Customer::query();
    
    $customers = $manager->scopeByPermission(
        $query,
        auth()->id(),
        'customers.view'
    )->paginate(20);
    
    return view('customers.index', compact('customers'));
}
```

### Real-World Example 3: Department-Based Access

**Scenario**: Managers can only view employees in their department.

```php
// Add row rule for department access
$manager->addRowRule(
    permissionId: $viewEmployeesPermission->id,
    modelClass: Employee::class,
    conditions: [
        'department_id' => '{{auth.department}}'
    ]
);
```

### Template Variables

Available template variables for row-level rules:

| Variable | Description | Example |
|----------|-------------|---------|
| `{{auth.id}}` | Current user ID | `123` |
| `{{auth.role}}` | Current user role | `'manager'` |
| `{{auth.department}}` | User's department ID | `5` |
| `{{auth.email}}` | User's email | `'user@example.com'` |

**Custom Variables**:
```php
// Register custom template variable
$resolver = app(TemplateVariableResolver::class);

$resolver->register('auth.region', function() {
    return auth()->user()->region_id;
});

// Use in rule
$manager->addRowRule(
    permissionId: $permission->id,
    modelClass: Store::class,
    conditions: [
        'region_id' => '{{auth.region}}'
    ]
);
```

### Multiple Conditions

```php
// Users can edit posts that are:
// 1. Created by them
// 2. In draft status
$manager->addRowRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    conditions: [
        'user_id' => '{{auth.id}}',
        'status' => 'draft'
    ],
    operator: 'AND' // All conditions must match
);
```

### Performance Tips

1. **Use Query Scopes** for list pages:
```php
// ✅ Good - filters at database level
$posts = $manager->scopeByPermission(
    Post::query(),
    auth()->id(),
    'posts.view'
)->get();

// ❌ Bad - loads all then filters
$posts = Post::all()->filter(function($post) use ($gate) {
    return $gate->canAccessRow(auth()->id(), 'posts.view', $post);
});
```

2. **Cache Results** for repeated checks:
```php
// Caching is automatic, but you can warm up cache
$manager->warmUpCache(auth()->id(), ['posts.view', 'posts.edit']);
```

3. **Use Eager Loading** with scopes:
```php
$posts = $manager->scopeByPermission(
    Post::with('user', 'category'),
    auth()->id(),
    'posts.view'
)->get();
```

---

## 2️⃣ Column-Level Permissions

### Overview

Column-level permissions control access to specific fields in a model. Different users can see or edit different columns based on their permissions.

### Use Cases

- Hide sensitive fields from certain roles
- Prevent editing of critical fields
- Show different form fields to different users
- Compliance with data privacy regulations

### Basic Usage

#### Creating a Column-Level Rule

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Editors cannot edit 'status' and 'featured' fields
$manager->addColumnRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content', 'excerpt', 'tags'],
    deniedColumns: ['status', 'featured', 'published_at']
);
```

#### Checking Column Access

```php
use Canvastack\Canvastack\Auth\RBAC\Gate;

$gate = app(Gate::class);
$post = Post::find(1);

if ($gate->canAccessColumn(auth()->id(), 'posts.edit', $post, 'status')) {
    // User can edit status field
    $post->status = 'published';
} else {
    // Cannot edit status
}
```

#### Getting Accessible Columns

```php
$manager = app(PermissionRuleManager::class);

$columns = $manager->getAccessibleColumns(
    auth()->id(),
    'posts.edit',
    Post::class
);

// ['title', 'content', 'excerpt', 'tags']
```

### Real-World Example 1: User Management

**Scenario**: Regular admins can edit user details but not roles or permissions.

```php
// Regular admin - limited columns
$manager->addColumnRule(
    permissionId: $editUsersPermission->id,
    modelClass: User::class,
    allowedColumns: ['name', 'email', 'phone', 'address'],
    deniedColumns: ['role', 'permissions', 'is_active']
);

// Super admin - no column restrictions
// Don't add column rule, or use empty deniedColumns
```

**Usage in Forms**:
```php
public function edit(User $user, FormBuilder $form, PermissionRuleManager $manager)
{
    $form->setContext('admin');
    $form->setModel($user);
    
    // Get accessible columns
    $columns = $manager->getAccessibleColumns(
        auth()->id(),
        'users.edit',
        User::class
    );
    
    // Only show accessible fields
    if (in_array('name', $columns)) {
        $form->text('name', 'Name')->required();
    }
    
    if (in_array('email', $columns)) {
        $form->email('email', 'Email')->required();
    }
    
    if (in_array('role', $columns)) {
        $form->select('role', 'Role', $roles);
    }
    
    return view('users.edit', compact('form', 'user'));
}
```

### Real-World Example 2: Financial Data

**Scenario**: Accountants can view all financial fields, but sales staff can only see basic info.

```php
// Sales staff - basic columns only
$manager->addColumnRule(
    permissionId: $viewInvoicesPermission->id,
    modelClass: Invoice::class,
    allowedColumns: ['invoice_number', 'customer_name', 'date', 'total'],
    deniedColumns: ['cost', 'profit', 'commission', 'internal_notes']
);

// Accountants - all columns (no rule needed)
```

### Real-World Example 3: HR System

**Scenario**: Managers can view employee info but not salary details.

```php
// Manager permission - no salary access
$manager->addColumnRule(
    permissionId: $viewEmployeesPermission->id,
    modelClass: Employee::class,
    allowedColumns: [
        'name', 'email', 'phone', 'position', 
        'department', 'hire_date', 'performance_rating'
    ],
    deniedColumns: ['salary', 'bonus', 'benefits', 'bank_account']
);
```

### FormBuilder Integration

FormBuilder automatically filters fields based on column permissions:

```php
public function edit(Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    
    // Set permission for automatic filtering
    $form->setPermission('posts.edit');
    
    // Define all fields - FormBuilder will filter automatically
    $form->text('title', 'Title')->required();
    $form->textarea('content', 'Content')->required();
    $form->select('status', 'Status', $statuses); // Hidden if not accessible
    $form->checkbox('featured', 'Featured'); // Hidden if not accessible
    
    return view('posts.edit', compact('form', 'post'));
}
```

### TableBuilder Integration

TableBuilder automatically hides columns based on permissions:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Post());
    
    // Set permission for automatic filtering
    $table->setPermission('posts.view');
    
    // Define all columns - TableBuilder will filter automatically
    $table->setFields([
        'title:Title',
        'author:Author',
        'status:Status',        // Hidden if not accessible
        'featured:Featured',    // Hidden if not accessible
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

### Whitelist vs Blacklist Mode

**Whitelist Mode** (Recommended for security):
```php
// Only specified columns are allowed
$manager->addColumnRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content'], // Only these
    deniedColumns: [] // Empty
);
```

**Blacklist Mode**:
```php
// All columns except specified are allowed
$manager->addColumnRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    allowedColumns: [], // Empty = all allowed
    deniedColumns: ['password', 'secret_key'] // Except these
);
```

**Combined Mode**:
```php
// Allowed columns minus denied columns
$manager->addColumnRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content', 'status'],
    deniedColumns: ['status'] // Removes 'status' from allowed
);
// Result: ['title', 'content']
```

### Validation with Column Permissions

```php
public function update(Request $request, Post $post, Gate $gate)
{
    // Get accessible columns
    $columns = app(PermissionRuleManager::class)->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
    
    // Only validate accessible fields
    $rules = [];
    if (in_array('title', $columns)) {
        $rules['title'] = 'required|max:255';
    }
    if (in_array('content', $columns)) {
        $rules['content'] = 'required';
    }
    
    $validated = $request->validate($rules);
    
    // Only update accessible fields
    $post->update(array_intersect_key($validated, array_flip($columns)));
    
    return redirect()->route('posts.index');
}
```

### Performance Tips

1. **Cache Accessible Columns**:
```php
// Caching is automatic, but results are cached for 1 hour
$columns = $manager->getAccessibleColumns(
    auth()->id(),
    'posts.edit',
    Post::class
);
```

2. **Use in Queries** (for API responses):
```php
$columns = $manager->getAccessibleColumns(
    auth()->id(),
    'posts.view',
    Post::class
);

$posts = Post::select($columns)->get();
```

3. **Batch Check** for multiple columns:
```php
// ✅ Good - single call
$columns = $manager->getAccessibleColumns(auth()->id(), 'posts.edit', Post::class);
$canEditTitle = in_array('title', $columns);
$canEditStatus = in_array('status', $columns);

// ❌ Bad - multiple calls
$canEditTitle = $gate->canAccessColumn(auth()->id(), 'posts.edit', $post, 'title');
$canEditStatus = $gate->canAccessColumn(auth()->id(), 'posts.edit', $post, 'status');
```

---

## 3️⃣ JSON Attribute Permissions

### Overview

JSON attribute permissions control access to nested fields within JSON columns. This allows fine-grained control over complex data structures stored as JSON.

### Use Cases

- Control access to nested metadata fields
- Hide sensitive SEO data from certain roles
- Restrict editing of specific configuration options
- Manage access to dynamic form data

### Basic Usage

#### Creating a JSON Attribute Rule

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Editors cannot edit SEO metadata
$manager->addJsonAttributeRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['social.*', 'layout.*'],
    deniedPaths: ['seo.*', 'featured', 'promoted']
);
```

#### Checking JSON Attribute Access

```php
use Canvastack\Canvastack\Auth\RBAC\Gate;

$gate = app(Gate::class);
$post = Post::find(1);

if ($gate->canAccessJsonAttribute(
    auth()->id(), 
    'posts.edit', 
    $post, 
    'metadata', 
    'seo.title'
)) {
    // User can edit metadata.seo.title
    $metadata = $post->metadata;
    $metadata['seo']['title'] = 'New Title';
    $post->metadata = $metadata;
    $post->save();
}
```

#### Getting Accessible JSON Paths

```php
$manager = app(PermissionRuleManager::class);

$paths = $manager->getAccessibleJsonPaths(
    auth()->id(),
    'posts.edit',
    Post::class,
    'metadata'
);

// ['social.*', 'layout.*']
```

### Real-World Example 1: Blog Post Metadata

**Scenario**: Content editors can edit social media metadata but not SEO settings.

**Database Structure**:
```php
// posts table
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->json('metadata'); // JSON column
    $table->timestamps();
});

// Example metadata JSON:
{
    "seo": {
        "title": "SEO Title",
        "description": "SEO Description",
        "keywords": ["keyword1", "keyword2"]
    },
    "social": {
        "og_title": "Social Title",
        "og_description": "Social Description",
        "og_image": "image.jpg"
    },
    "layout": {
        "template": "default",
        "sidebar": "right"
    },
    "featured": true,
    "promoted": false
}
```

**Permission Rule**:
```php
// Content editors - social and layout only
$manager->addJsonAttributeRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['social.*', 'layout.*'],
    deniedPaths: ['seo.*', 'featured', 'promoted']
);

// SEO specialists - SEO only
$manager->addJsonAttributeRule(
    permissionId: $editSeoPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['seo.*'],
    deniedPaths: ['social.*', 'layout.*', 'featured', 'promoted']
);
```

**Usage in Forms**:
```php
public function edit(Post $post, FormBuilder $form, PermissionRuleManager $manager)
{
    $form->setContext('admin');
    $form->setModel($post);
    
    // Get accessible JSON paths
    $paths = $manager->getAccessibleJsonPaths(
        auth()->id(),
        'posts.edit',
        Post::class,
        'metadata'
    );
    
    // Check if user can access social fields
    if ($this->canAccessPath($paths, 'social.*')) {
        $form->text('metadata[social][og_title]', 'Social Title');
        $form->textarea('metadata[social][og_description]', 'Social Description');
        $form->text('metadata[social][og_image]', 'Social Image');
    }
    
    // Check if user can access SEO fields
    if ($this->canAccessPath($paths, 'seo.*')) {
        $form->text('metadata[seo][title]', 'SEO Title');
        $form->textarea('metadata[seo][description]', 'SEO Description');
        $form->tags('metadata[seo][keywords]', 'SEO Keywords');
    }
    
    return view('posts.edit', compact('form', 'post'));
}

private function canAccessPath(array $paths, string $pattern): bool
{
    foreach ($paths as $path) {
        if (fnmatch($path, $pattern)) {
            return true;
        }
    }
    return false;
}
```

### Real-World Example 2: Product Configuration

**Scenario**: Store managers can edit pricing but not cost/profit data.

**Database Structure**:
```php
// products table with config JSON column
{
    "pricing": {
        "retail_price": 99.99,
        "sale_price": 79.99,
        "cost": 50.00,        // Hidden from managers
        "profit_margin": 0.6  // Hidden from managers
    },
    "inventory": {
        "stock": 100,
        "reorder_point": 20,
        "supplier_id": 5      // Hidden from managers
    },
    "display": {
        "featured": true,
        "badge": "Sale"
    }
}
```

**Permission Rule**:
```php
// Store managers - pricing and display only
$manager->addJsonAttributeRule(
    permissionId: $editProductsPermission->id,
    modelClass: Product::class,
    jsonColumn: 'config',
    allowedPaths: [
        'pricing.retail_price',
        'pricing.sale_price',
        'inventory.stock',
        'display.*'
    ],
    deniedPaths: [
        'pricing.cost',
        'pricing.profit_margin',
        'inventory.supplier_id'
    ]
);
```

### Real-World Example 3: User Preferences

**Scenario**: Users can edit their own preferences but not system settings.

**Database Structure**:
```php
// users table with preferences JSON column
{
    "ui": {
        "theme": "dark",
        "language": "en",
        "timezone": "UTC"
    },
    "notifications": {
        "email": true,
        "push": false,
        "frequency": "daily"
    },
    "system": {
        "api_key": "secret",      // Hidden from users
        "rate_limit": 1000,       // Hidden from users
        "admin_notes": "VIP user" // Hidden from users
    }
}
```

**Permission Rule**:
```php
// Regular users - UI and notifications only
$manager->addJsonAttributeRule(
    permissionId: $editPreferencesPermission->id,
    modelClass: User::class,
    jsonColumn: 'preferences',
    allowedPaths: ['ui.*', 'notifications.*'],
    deniedPaths: ['system.*']
);
```

### Wildcard Patterns

JSON attribute rules support wildcard patterns:

```php
// Match all fields under 'seo'
'seo.*'  // Matches: seo.title, seo.description, seo.keywords

// Match specific nested field
'social.og_title'  // Matches only: social.og_title

// Match all top-level fields
'*'  // Matches: featured, promoted, etc.

// Match deeply nested
'config.pricing.*'  // Matches: config.pricing.retail, config.pricing.sale
```

### Path Separator

Default separator is `.` (dot), but can be configured:

```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'json_attribute' => [
        'path_separator' => '.', // or '/' or '->'
    ],
],
```

### FormBuilder Integration

FormBuilder can automatically filter JSON fields:

```php
public function edit(Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    $form->setPermission('posts.edit');
    
    // Define all JSON fields - FormBuilder filters automatically
    $form->text('metadata[seo][title]', 'SEO Title');
    $form->text('metadata[social][og_title]', 'Social Title');
    $form->checkbox('metadata[featured]', 'Featured');
    
    return view('posts.edit', compact('form', 'post'));
}
```

### Validation with JSON Permissions

```php
public function update(Request $request, Post $post, PermissionRuleManager $manager)
{
    // Get accessible JSON paths
    $paths = $manager->getAccessibleJsonPaths(
        auth()->id(),
        'posts.edit',
        Post::class,
        'metadata'
    );
    
    // Build validation rules for accessible paths only
    $rules = [];
    
    if ($this->canAccessPath($paths, 'seo.title')) {
        $rules['metadata.seo.title'] = 'required|max:60';
    }
    
    if ($this->canAccessPath($paths, 'social.og_title')) {
        $rules['metadata.social.og_title'] = 'required|max:100';
    }
    
    $validated = $request->validate($rules);
    
    // Update only accessible fields
    $metadata = $post->metadata;
    foreach ($validated as $key => $value) {
        data_set($metadata, str_replace('metadata.', '', $key), $value);
    }
    $post->metadata = $metadata;
    $post->save();
    
    return redirect()->route('posts.index');
}
```

### Performance Tips

1. **Cache Accessible Paths**:
```php
// Results are cached automatically for 1 hour
$paths = $manager->getAccessibleJsonPaths(
    auth()->id(),
    'posts.edit',
    Post::class,
    'metadata'
);
```

2. **Use Wildcards** for better performance:
```php
// ✅ Good - single wildcard rule
$manager->addJsonAttributeRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['seo.*'], // Covers all SEO fields
    deniedPaths: []
);

// ❌ Bad - multiple specific rules
$manager->addJsonAttributeRule(..., allowedPaths: ['seo.title']);
$manager->addJsonAttributeRule(..., allowedPaths: ['seo.description']);
$manager->addJsonAttributeRule(..., allowedPaths: ['seo.keywords']);
```

3. **Minimize JSON Depth**:
```php
// ✅ Good - shallow structure
{
    "seo_title": "Title",
    "seo_description": "Description"
}

// ❌ Bad - deep nesting (slower to check)
{
    "metadata": {
        "seo": {
            "content": {
                "title": "Title"
            }
        }
    }
}
```

---

## 4️⃣ Conditional Permissions

### Overview

Conditional permissions provide dynamic, rule-based access control. Access is granted or denied based on the current state of the data, not just static conditions.

### Use Cases

- Users can only edit draft posts (not published)
- Managers can approve orders under $10,000
- Users can delete items with no dependencies
- Time-based access (business hours only)
- Status-based workflows

### Basic Usage

#### Creating a Conditional Rule

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Users can only edit posts with status='draft'
$manager->addConditionalRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    condition: "status === 'draft'"
);
```

#### Checking Conditional Access

```php
use Canvastack\Canvastack\Auth\RBAC\Gate;

$gate = app(Gate::class);
$post = Post::find(1);

// Gate automatically evaluates conditional rules
if ($gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
    // User can edit this post (it's in draft status)
    $post->update($data);
}
```

### Real-World Example 1: Post Editing Workflow

**Scenario**: Authors can edit posts only when they're in draft status. Once published, only editors can modify them.

```php
// Authors - can only edit drafts
$manager->addConditionalRule(
    permissionId: $authorEditPermission->id,
    modelClass: Post::class,
    condition: "status === 'draft' AND user_id === {{auth.id}}"
);

// Editors - can edit published posts
$manager->addConditionalRule(
    permissionId: $editorEditPermission->id,
    modelClass: Post::class,
    condition: "status === 'published'"
);
```

**Usage**:
```php
public function edit(Post $post, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        if ($post->status === 'published') {
            abort(403, 'Only editors can edit published posts');
        } else {
            abort(403, 'You can only edit your own draft posts');
        }
    }
    
    return view('posts.edit', compact('post'));
}
```

### Real-World Example 2: Order Approval Limits

**Scenario**: Managers can approve orders up to $10,000. Anything higher requires director approval.

```php
// Managers - orders under $10,000
$manager->addConditionalRule(
    permissionId: $approveOrdersPermission->id,
    modelClass: Order::class,
    condition: "total < 10000"
);

// Directors - all orders
// No conditional rule needed, just grant permission
```

**Usage**:
```php
public function approve(Order $order, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'orders.approve', $order)) {
        abort(403, 'You can only approve orders under $10,000');
    }
    
    $order->update(['status' => 'approved', 'approved_by' => auth()->id()]);
    
    return redirect()->route('orders.index')
        ->with('success', 'Order approved successfully');
}
```

### Real-World Example 3: Delete Protection

**Scenario**: Users can only delete items that have no dependencies (comments, likes, etc.).

```php
// Can only delete posts with no comments
$manager->addConditionalRule(
    permissionId: $deletePostsPermission->id,
    modelClass: Post::class,
    condition: "comments_count === 0"
);
```

**Usage**:
```php
public function destroy(Post $post, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'posts.delete', $post)) {
        abort(403, 'Cannot delete posts with comments');
    }
    
    $post->delete();
    
    return redirect()->route('posts.index')
        ->with('success', 'Post deleted successfully');
}
```

### Supported Operators

#### Comparison Operators

```php
// Equality
"status === 'draft'"
"status !== 'published'"

// Numeric comparison
"total > 1000"
"total < 10000"
"total >= 100"
"total <= 5000"

// In array
"status in ['draft', 'pending']"
"status not_in ['published', 'archived']"
```

#### Logical Operators

```php
// AND
"status === 'draft' AND user_id === {{auth.id}}"

// OR
"status === 'draft' OR status === 'pending'"

// NOT
"NOT (status === 'archived')"

// Complex
"(status === 'draft' OR status === 'pending') AND user_id === {{auth.id}}"
```

#### Relationship Checks

```php
// Count relationships
"comments_count === 0"
"likes_count > 10"

// Check relationship existence
"category_id !== null"
"parent_id === null"
```

### Real-World Example 4: Time-Based Access

**Scenario**: Users can only submit timesheets during business hours.

```php
// Custom template variable for current hour
$resolver = app(TemplateVariableResolver::class);
$resolver->register('time.hour', fn() => now()->hour);

// Business hours only (9 AM - 5 PM)
$manager->addConditionalRule(
    permissionId: $submitTimesheetPermission->id,
    modelClass: Timesheet::class,
    condition: "{{time.hour}} >= 9 AND {{time.hour}} < 17"
);
```

### Real-World Example 5: Multi-Stage Workflow

**Scenario**: Document approval workflow with multiple stages.

```php
// Stage 1: Authors can edit when status is 'draft'
$manager->addConditionalRule(
    permissionId: $authorEditPermission->id,
    modelClass: Document::class,
    condition: "status === 'draft' AND user_id === {{auth.id}}"
);

// Stage 2: Reviewers can edit when status is 'in_review'
$manager->addConditionalRule(
    permissionId: $reviewerEditPermission->id,
    modelClass: Document::class,
    condition: "status === 'in_review'"
);

// Stage 3: Approvers can edit when status is 'pending_approval'
$manager->addConditionalRule(
    permissionId: $approverEditPermission->id,
    modelClass: Document::class,
    condition: "status === 'pending_approval'"
);
```

### Combining with Row-Level Rules

You can combine conditional rules with row-level rules:

```php
// Row-level: Users can only access their department's data
$manager->addRowRule(
    permissionId: $editEmployeesPermission->id,
    modelClass: Employee::class,
    conditions: ['department_id' => '{{auth.department}}']
);

// Conditional: Can only edit employees with status 'active'
$manager->addConditionalRule(
    permissionId: $editEmployeesPermission->id,
    modelClass: Employee::class,
    condition: "status === 'active'"
);

// Both rules must pass for access to be granted
```

### Security Considerations

1. **Prevent Code Injection**:
```php
// ✅ Safe - uses allowed operators only
"status === 'draft'"

// ❌ Dangerous - would be rejected
"eval('malicious code')"
"system('rm -rf /')"
```

The system automatically validates conditions and only allows safe operators.

2. **Validate Condition Syntax**:
```php
try {
    $manager->addConditionalRule(
        permissionId: $permission->id,
        modelClass: Post::class,
        condition: "invalid syntax here"
    );
} catch (InvalidConditionException $e) {
    // Handle invalid condition
    Log::error('Invalid condition: ' . $e->getMessage());
}
```

3. **Configure Allowed Operators**:
```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'conditional' => [
        'allowed_operators' => [
            '===', '!==', '>', '<', '>=', '<=',
            'in', 'not_in', 'AND', 'OR', 'NOT'
        ],
        'allowed_functions' => ['count'], // Limit functions
    ],
],
```

### Performance Tips

1. **Use Simple Conditions**:
```php
// ✅ Good - simple, fast
"status === 'draft'"

// ❌ Bad - complex, slower
"(status === 'draft' OR status === 'pending') AND (user_id === {{auth.id}} OR department_id === {{auth.department}}) AND comments_count > 0"
```

2. **Cache Results**:
```php
// Conditional rule results are cached for 30 minutes by default
// Configure in config/canvastack-rbac.php
'fine_grained' => [
    'cache' => [
        'ttl' => [
            'conditional' => 1800, // 30 minutes
        ],
    ],
],
```

3. **Use Database Indexes**:
```php
// If checking 'status' frequently, add index
Schema::table('posts', function (Blueprint $table) {
    $table->index('status');
});
```

4. **Avoid Relationship Counts** in conditions when possible:
```php
// ✅ Good - uses cached count
"comments_count === 0"  // Assumes you have comments_count column

// ❌ Bad - queries relationship every time
// Don't use: "comments.count() === 0"
```

### Debugging Conditional Rules

Enable audit logging to see why access was denied:

```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'audit' => [
        'enabled' => true,
        'log_denials' => true,
        'log_channel' => 'rbac',
    ],
],
```

Check logs:
```php
// storage/logs/rbac.log
[2026-02-27 10:30:00] rbac.WARNING: Permission denied
{
    "user_id": 123,
    "permission": "posts.edit",
    "reason": "conditional_rule_failed",
    "condition": "status === 'draft'",
    "actual_value": "published"
}
```

---

## 5️⃣ User Permission Overrides

### Overview

User permission overrides allow you to create exceptions to role-based rules for specific users. This provides flexibility for special cases without modifying the base permission rules.

### Use Cases

- Grant temporary access to specific users
- Create exceptions for VIP users
- Allow specific users to access specific records
- Override restrictions for testing/debugging

### Basic Usage

#### Creating a User Override

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$manager = app(PermissionRuleManager::class);

// Allow user #5 to edit post #123 (even if row rules would deny)
$manager->addUserOverride(
    userId: 5,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: 123,
    fieldName: null,
    allowed: true
);
```

#### Removing a User Override

```php
$manager->removeUserOverride(
    userId: 5,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: 123
);
```

#### Getting User Overrides

```php
$overrides = $manager->getUserOverrides(
    userId: 5,
    permissionId: $editPostsPermission->id
);

foreach ($overrides as $override) {
    echo "Model: {$override->model_type}\n";
    echo "Model ID: {$override->model_id}\n";
    echo "Allowed: {$override->allowed}\n";
}
```

### Real-World Example 1: Temporary Access

**Scenario**: Grant a user temporary access to edit a specific post for collaboration.

```php
// Grant access
$manager->addUserOverride(
    userId: $collaborator->id,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: $post->id,
    allowed: true
);

// Later, revoke access
$manager->removeUserOverride(
    userId: $collaborator->id,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: $post->id
);
```

**Usage in Controller**:
```php
public function grantAccess(Request $request, Post $post, PermissionRuleManager $manager)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'expires_at' => 'required|date|after:now',
    ]);
    
    // Grant override
    $manager->addUserOverride(
        userId: $validated['user_id'],
        permissionId: Permission::where('name', 'posts.edit')->first()->id,
        modelType: Post::class,
        modelId: $post->id,
        allowed: true
    );
    
    // Schedule revocation
    RevokeAccessJob::dispatch(
        $validated['user_id'],
        $post->id
    )->delay($validated['expires_at']);
    
    return back()->with('success', 'Access granted until ' . $validated['expires_at']);
}
```

### Real-World Example 2: VIP User Access

**Scenario**: VIP users can access all posts regardless of row-level rules.

```php
// Grant VIP user access to all posts
$manager->addUserOverride(
    userId: $vipUser->id,
    permissionId: $viewPostsPermission->id,
    modelType: Post::class,
    modelId: null, // null = all records
    allowed: true
);
```

### Real-World Example 3: Field-Level Override

**Scenario**: Allow specific user to edit a normally restricted field.

```php
// Regular users cannot edit 'featured' field
$manager->addColumnRule(
    permissionId: $editPostsPermission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content'],
    deniedColumns: ['featured']
);

// But allow user #10 to edit 'featured' field
$manager->addUserOverride(
    userId: 10,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: null,
    fieldName: 'featured',
    allowed: true
);
```

### Real-World Example 4: Deny Override

**Scenario**: Temporarily block a user from accessing specific data.

```php
// Block user from editing their own post (e.g., under investigation)
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $editPostsPermission->id,
    modelType: Post::class,
    modelId: $post->id,
    allowed: false // Deny access
);
```

### Override Scope Levels

#### 1. Global Override (All Records)

```php
// User can access ALL posts
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: null, // null = all records
    fieldName: null,
    allowed: true
);
```

#### 2. Record-Specific Override

```php
// User can access specific post
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: 123, // specific record
    fieldName: null,
    allowed: true
);
```

#### 3. Field-Specific Override

```php
// User can access specific field in all records
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: null,
    fieldName: 'featured', // specific field
    allowed: true
);
```

#### 4. Record + Field Override

```php
// User can access specific field in specific record
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: 123,
    fieldName: 'featured',
    allowed: true
);
```

### Override Priority

User overrides are checked BEFORE role-based rules:

```
1. Check user overrides (highest priority)
   ↓ If no override found
2. Check role-based rules
   ↓ If no rules found
3. Check basic permission
```

Example:
```php
// Role rule: Users can only edit their own posts
$manager->addRowRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    conditions: ['user_id' => '{{auth.id}}']
);

// User override: User #5 can edit post #123 (not their own)
$manager->addUserOverride(
    userId: 5,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: 123,
    allowed: true
);

// Result: User #5 CAN edit post #123 (override takes precedence)
```

### Managing Overrides in UI

**List User Overrides**:
```php
public function showOverrides(User $user, PermissionRuleManager $manager)
{
    $permissions = Permission::all();
    $overrides = [];
    
    foreach ($permissions as $permission) {
        $userOverrides = $manager->getUserOverrides($user->id, $permission->id);
        if ($userOverrides->isNotEmpty()) {
            $overrides[$permission->name] = $userOverrides;
        }
    }
    
    return view('admin.users.overrides', compact('user', 'overrides'));
}
```

**Add Override Form**:
```blade
<form method="POST" action="{{ route('admin.users.overrides.store', $user) }}">
    @csrf
    
    <div class="form-group">
        <label>Permission</label>
        <select name="permission_id" class="form-control" required>
            @foreach($permissions as $permission)
                <option value="{{ $permission->id }}">{{ $permission->display_name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="form-group">
        <label>Model Type</label>
        <select name="model_type" class="form-control" required>
            <option value="App\Models\Post">Post</option>
            <option value="App\Models\User">User</option>
            <option value="App\Models\Order">Order</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Model ID (optional - leave empty for all)</label>
        <input type="number" name="model_id" class="form-control">
    </div>
    
    <div class="form-group">
        <label>Field Name (optional)</label>
        <input type="text" name="field_name" class="form-control">
    </div>
    
    <div class="form-group">
        <label>Access</label>
        <select name="allowed" class="form-control" required>
            <option value="1">Allow</option>
            <option value="0">Deny</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Add Override</button>
</form>
```

**Store Override**:
```php
public function storeOverride(Request $request, User $user, PermissionRuleManager $manager)
{
    $validated = $request->validate([
        'permission_id' => 'required|exists:permissions,id',
        'model_type' => 'required|string',
        'model_id' => 'nullable|integer',
        'field_name' => 'nullable|string',
        'allowed' => 'required|boolean',
    ]);
    
    $manager->addUserOverride(
        userId: $user->id,
        permissionId: $validated['permission_id'],
        modelType: $validated['model_type'],
        modelId: $validated['model_id'],
        fieldName: $validated['field_name'],
        allowed: $validated['allowed']
    );
    
    return back()->with('success', 'Override added successfully');
}
```

### Audit Logging for Overrides

Enable audit logging to track override usage:

```php
// In config/canvastack-rbac.php
'fine_grained' => [
    'audit' => [
        'enabled' => true,
        'log_overrides' => true, // Log when overrides are used
    ],
],
```

Check logs:
```php
// storage/logs/rbac.log
[2026-02-27 10:30:00] rbac.INFO: User override applied
{
    "user_id": 5,
    "permission": "posts.edit",
    "model_type": "App\\Models\\Post",
    "model_id": 123,
    "override_allowed": true,
    "would_be_denied_by_rules": true
}
```

### Performance Tips

1. **Limit Override Scope**:
```php
// ✅ Good - specific record
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: 123
);

// ❌ Bad - global override (slower to check)
$manager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: null
);
```

2. **Clean Up Expired Overrides**:
```php
// Create a scheduled job to remove old overrides
class CleanupExpiredOverrides extends Command
{
    public function handle()
    {
        UserPermissionOverride::where('expires_at', '<', now())
            ->delete();
    }
}
```

3. **Cache Override Checks**:
```php
// Override checks are cached automatically for 1 hour
// Results are invalidated when overrides change
```

### Best Practices

1. **Document Overrides**: Always add a reason when creating overrides
2. **Set Expiration**: Use temporary overrides when possible
3. **Audit Regularly**: Review active overrides periodically
4. **Limit Scope**: Use specific record/field overrides instead of global
5. **Monitor Usage**: Track override usage in logs

---

## 🎨 Blade Directives

### Overview

Blade directives provide a convenient way to check fine-grained permissions directly in your views.

### Available Directives

#### @canAccessRow

Check if user can access a specific row:

```blade
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">
        Edit Post
    </a>
@endcanAccessRow
```

With else:
```blade
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">
        Edit Post
    </a>
@else
    <span class="text-muted">
        <i data-lucide="lock"></i>
        No access
    </span>
@endcanAccessRow
```

#### @canAccessColumn

Check if user can access a specific column:

```blade
@canAccessColumn('posts.edit', $post, 'status')
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
        </select>
    </div>
@endcanAccessColumn
```

#### @canAccessJsonAttribute

Check if user can access a JSON attribute:

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <div class="form-group">
        <label>SEO Title</label>
        <input type="text" name="metadata[seo][title]" class="form-control">
    </div>
@endcanAccessJsonAttribute
```

### Real-World Examples

#### Example 1: Action Buttons

```blade
<div class="btn-group">
    @canAccessRow('posts.view', $post)
        <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-info">
            <i data-lucide="eye"></i> View
        </a>
    @endcanAccessRow
    
    @canAccessRow('posts.edit', $post)
        <a href="{{ route('posts.edit', $post) }}" class="btn btn-sm btn-warning">
            <i data-lucide="edit"></i> Edit
        </a>
    @endcanAccessRow
    
    @canAccessRow('posts.delete', $post)
        <form method="POST" action="{{ route('posts.destroy', $post) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" 
                    onclick="return confirm('Are you sure?')">
                <i data-lucide="trash"></i> Delete
            </button>
        </form>
    @endcanAccessRow
</div>
```

#### Example 2: Conditional Form Fields

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    {{-- Always show title --}}
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ $post->title }}" class="form-control" required>
    </div>
    
    {{-- Show status only if user can access it --}}
    @canAccessColumn('posts.edit', $post, 'status')
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="draft" @selected($post->status === 'draft')>Draft</option>
                <option value="published" @selected($post->status === 'published')>Published</option>
            </select>
        </div>
    @else
        <input type="hidden" name="status" value="{{ $post->status }}">
        <div class="alert alert-info">
            <i data-lucide="lock"></i>
            You cannot change the post status
        </div>
    @endcanAccessColumn
    
    {{-- Show featured checkbox only if accessible --}}
    @canAccessColumn('posts.edit', $post, 'featured')
        <div class="form-check">
            <input type="checkbox" name="featured" value="1" 
                   @checked($post->featured) class="form-check-input">
            <label class="form-check-label">Featured Post</label>
        </div>
    @endcanAccessColumn
    
    <button type="submit" class="btn btn-primary">Update Post</button>
</form>
```

#### Example 3: Table with Conditional Columns

```blade
<table class="table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Author</th>
            @canAccessColumn('posts.view', new App\Models\Post, 'status')
                <th>Status</th>
            @endcanAccessColumn
            @canAccessColumn('posts.view', new App\Models\Post, 'featured')
                <th>Featured</th>
            @endcanAccessColumn
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($posts as $post)
            <tr>
                <td>{{ $post->title }}</td>
                <td>{{ $post->author->name }}</td>
                @canAccessColumn('posts.view', $post, 'status')
                    <td>
                        <span class="badge badge-{{ $post->status === 'published' ? 'success' : 'warning' }}">
                            {{ ucfirst($post->status) }}
                        </span>
                    </td>
                @endcanAccessColumn
                @canAccessColumn('posts.view', $post, 'featured')
                    <td>
                        @if($post->featured)
                            <i data-lucide="star" class="text-warning"></i>
                        @endif
                    </td>
                @endcanAccessColumn
                <td>
                    @canAccessRow('posts.edit', $post)
                        <a href="{{ route('posts.edit', $post) }}" class="btn btn-sm btn-primary">
                            Edit
                        </a>
                    @endcanAccessRow
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

#### Example 4: JSON Metadata Fields

```blade
<div class="card">
    <div class="card-header">
        <h3>Post Metadata</h3>
    </div>
    <div class="card-body">
        @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
            <div class="form-group">
                <label>SEO Title</label>
                <input type="text" name="metadata[seo][title]" 
                       value="{{ $post->metadata['seo']['title'] ?? '' }}" 
                       class="form-control">
            </div>
        @endcanAccessJsonAttribute
        
        @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.description')
            <div class="form-group">
                <label>SEO Description</label>
                <textarea name="metadata[seo][description]" class="form-control" rows="3">
                    {{ $post->metadata['seo']['description'] ?? '' }}
                </textarea>
            </div>
        @endcanAccessJsonAttribute
        
        @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.og_image')
            <div class="form-group">
                <label>Social Media Image</label>
                <input type="text" name="metadata[social][og_image]" 
                       value="{{ $post->metadata['social']['og_image'] ?? '' }}" 
                       class="form-control">
            </div>
        @endcanAccessJsonAttribute
    </div>
</div>
```

### Theme Integration

Blade directives automatically use theme colors:

```blade
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}" 
       class="btn btn-primary"
       style="background: @themeColor('primary')">
        {{ __('ui.buttons.edit') }}
    </a>
@else
    <span class="text-muted" style="color: @themeColor('text-muted')">
        <i data-lucide="lock"></i>
        {{ __('rbac.fine_grained.no_access') }}
    </span>
@endcanAccessRow
```

### i18n Integration

Use translation functions with directives:

```blade
@canAccessRow('posts.delete', $post)
    <button type="submit" class="btn btn-danger">
        {{ __('ui.buttons.delete') }}
    </button>
@else
    <span class="text-muted">
        {{ __('rbac.fine_grained.cannot_delete') }}
    </span>
@endcanAccessRow
```

---

## 🔧 Component Integration

### FormBuilder Integration

FormBuilder automatically respects fine-grained permissions when you set a permission:

```php
public function edit(Post $post, FormBuilder $form)
{
    $form->setContext('admin');
    $form->setModel($post);
    
    // Enable automatic permission filtering
    $form->setPermission('posts.edit');
    
    // Define all fields - FormBuilder will filter based on permissions
    $form->text('title', 'Title')->required();
    $form->textarea('content', 'Content')->required();
    $form->select('status', 'Status', $statuses); // Hidden if not accessible
    $form->checkbox('featured', 'Featured'); // Hidden if not accessible
    
    // JSON fields are also filtered
    $form->text('metadata[seo][title]', 'SEO Title'); // Hidden if not accessible
    
    return view('posts.edit', compact('form', 'post'));
}
```

**Permission Indicators**:

FormBuilder shows messages when fields are hidden:

```blade
{{-- Rendered by FormBuilder when fields are hidden --}}
<div class="alert alert-info" style="background: @themeColor('info-light')">
    <i data-lucide="lock"></i>
    {{ __('rbac.fine_grained.fields_hidden', ['count' => 2]) }}
</div>
```

### TableBuilder Integration

TableBuilder automatically filters rows and columns based on permissions:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Post());
    
    // Enable automatic permission filtering
    $table->setPermission('posts.view');
    
    // Define all columns - TableBuilder will filter based on permissions
    $table->setFields([
        'title:Title',
        'author:Author',
        'status:Status',        // Hidden if not accessible
        'featured:Featured',    // Hidden if not accessible
        'created_at:Created'
    ]);
    
    // Row-level filtering is applied automatically
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

**Permission Indicators**:

TableBuilder shows messages when columns are hidden:

```blade
{{-- Rendered by TableBuilder when columns are hidden --}}
<div class="alert alert-info mb-4" style="background: @themeColor('info-light')">
    <i data-lucide="eye-off"></i>
    {{ __('rbac.fine_grained.columns_hidden', ['count' => 2]) }}
</div>
```

---

## 🚀 Best Practices

### 1. Start Simple, Add Complexity as Needed

```php
// ✅ Start with basic RBAC
Gate::allows('posts.edit', auth()->id());

// ✅ Add row-level when needed
$gate->canAccessRow(auth()->id(), 'posts.edit', $post);

// ✅ Add column-level for sensitive fields
$gate->canAccessColumn(auth()->id(), 'posts.edit', $post, 'status');

// ❌ Don't over-engineer from the start
```

### 2. Use Query Scopes for List Pages

```php
// ✅ Good - filters at database level
$posts = $manager->scopeByPermission(
    Post::query(),
    auth()->id(),
    'posts.view'
)->paginate(20);

// ❌ Bad - loads all then filters
$posts = Post::all()->filter(function($post) use ($gate) {
    return $gate->canAccessRow(auth()->id(), 'posts.view', $post);
});
```

### 3. Cache Aggressively

```php
// Caching is automatic, but you can optimize:

// Warm up cache for frequently used permissions
$manager->warmUpCache(auth()->id(), [
    'posts.view',
    'posts.edit',
    'users.view'
]);

// Configure cache TTL in config
'fine_grained' => [
    'cache' => [
        'ttl' => [
            'row' => 3600,        // 1 hour
            'column' => 3600,     // 1 hour
            'conditional' => 1800, // 30 minutes
        ],
    ],
],
```

### 4. Use Components for Automatic Filtering

```php
// ✅ Good - automatic filtering
$form->setPermission('posts.edit');
$table->setPermission('posts.view');

// ❌ Bad - manual filtering
if ($gate->canAccessColumn(...)) {
    $form->text('field', 'Label');
}
```

### 5. Combine Permission Types Strategically

```php
// Row-level: Users can only access their department's data
$manager->addRowRule(
    permissionId: $permission->id,
    modelClass: Employee::class,
    conditions: ['department_id' => '{{auth.department}}']
);

// Column-level: Hide salary from managers
$manager->addColumnRule(
    permissionId: $permission->id,
    modelClass: Employee::class,
    deniedColumns: ['salary', 'bonus']
);

// Conditional: Can only edit active employees
$manager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Employee::class,
    condition: "status === 'active'"
);

// All three rules must pass for access
```

### 6. Document Your Permission Rules

```php
// ✅ Good - documented
// Rule: Authors can only edit their own draft posts
// Reason: Prevent editing published content
// Added: 2026-02-27
$manager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    condition: "status === 'draft' AND user_id === {{auth.id}}"
);

// ❌ Bad - no documentation
$manager->addConditionalRule(...);
```

### 7. Test Permission Rules Thoroughly

```php
// Unit test for row-level rule
public function test_users_can_only_edit_own_posts()
{
    $user = User::factory()->create();
    $ownPost = Post::factory()->create(['user_id' => $user->id]);
    $otherPost = Post::factory()->create();
    
    $gate = app(Gate::class);
    
    $this->assertTrue($gate->canAccessRow($user->id, 'posts.edit', $ownPost));
    $this->assertFalse($gate->canAccessRow($user->id, 'posts.edit', $otherPost));
}
```

### 8. Monitor Performance

```php
// Enable query logging in development
DB::enableQueryLog();

$posts = $manager->scopeByPermission(
    Post::query(),
    auth()->id(),
    'posts.view'
)->get();

dd(DB::getQueryLog()); // Check for N+1 queries
```

### 9. Use Audit Logging

```php
// Enable in config
'fine_grained' => [
    'audit' => [
        'enabled' => true,
        'log_denials' => true,
        'log_channel' => 'rbac',
    ],
],

// Review logs regularly
tail -f storage/logs/rbac.log
```

### 10. Handle Permission Denials Gracefully

```php
// ✅ Good - user-friendly error
public function edit(Post $post, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        return back()->with('error', __('rbac.fine_grained.cannot_edit_post'));
    }
    
    return view('posts.edit', compact('post'));
}

// ❌ Bad - generic error
public function edit(Post $post, Gate $gate)
{
    if (!$gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        abort(403);
    }
}
```

---

## 🐛 Troubleshooting

### Issue 1: Permission Check Always Returns False

**Symptoms**: All permission checks fail even for valid users.

**Possible Causes**:
1. Fine-grained permissions not enabled in config
2. Permission rules not created
3. Basic permission not granted

**Solution**:
```php
// 1. Check config
config('canvastack-rbac.fine_grained.enabled'); // Should be true

// 2. Check if rules exist
$rules = PermissionRule::where('permission_id', $permissionId)->get();
dd($rules); // Should not be empty

// 3. Check basic permission first
$gate->allows('posts.edit', auth()->id()); // Should be true
```

### Issue 2: Cache Not Invalidating

**Symptoms**: Permission changes don't take effect immediately.

**Solution**:
```php
// Clear permission cache
$manager->clearRuleCache();

// Or clear all cache
Cache::tags(['rbac:rules'])->flush();

// Or restart Redis
redis-cli FLUSHALL
```

### Issue 3: Query Scope Not Filtering

**Symptoms**: scopeByPermission returns all records.

**Solution**:
```php
// Check if row rules exist
$rules = PermissionRule::where('permission_id', $permissionId)
    ->where('rule_type', 'row')
    ->get();

// Verify template variables resolve correctly
$resolver = app(TemplateVariableResolver::class);
$resolved = $resolver->resolve('{{auth.id}}');
dd($resolved); // Should be current user ID

// Enable query logging
DB::enableQueryLog();
$posts = $manager->scopeByPermission(...)->get();
dd(DB::getQueryLog());
```

### Issue 4: FormBuilder Not Hiding Fields

**Symptoms**: All form fields show regardless of permissions.

**Solution**:
```php
// 1. Ensure permission is set
$form->setPermission('posts.edit'); // Required!

// 2. Check if column rules exist
$columns = $manager->getAccessibleColumns(
    auth()->id(),
    'posts.edit',
    Post::class
);
dd($columns); // Should not include restricted fields

// 3. Verify field names match exactly
$form->text('status', 'Status'); // Field name must match column name
```

### Issue 5: Conditional Rule Not Working

**Symptoms**: Conditional rule doesn't prevent access as expected.

**Solution**:
```php
// 1. Check condition syntax
try {
    $manager->addConditionalRule(
        permissionId: $permission->id,
        modelClass: Post::class,
        condition: "status === 'draft'" // Check syntax
    );
} catch (InvalidConditionException $e) {
    dd($e->getMessage());
}

// 2. Verify model has the field
$post = Post::find(1);
dd($post->status); // Should exist

// 3. Enable audit logging to see evaluation
config(['canvastack-rbac.fine_grained.audit.enabled' => true]);
```

### Issue 6: Performance Issues

**Symptoms**: Slow page loads with fine-grained permissions.

**Solution**:
```php
// 1. Enable caching
config(['canvastack-rbac.fine_grained.cache.enabled' => true]);

// 2. Use query scopes instead of individual checks
// ❌ Bad
foreach ($posts as $post) {
    if ($gate->canAccessRow(auth()->id(), 'posts.view', $post)) {
        // ...
    }
}

// ✅ Good
$posts = $manager->scopeByPermission(
    Post::query(),
    auth()->id(),
    'posts.view'
)->get();

// 3. Warm up cache
$manager->warmUpCache(auth()->id(), ['posts.view', 'posts.edit']);

// 4. Add database indexes
Schema::table('posts', function (Blueprint $table) {
    $table->index(['user_id', 'status']);
});
```

---

## 📚 Additional Resources

### Documentation

- [API Reference](./api-reference.md) - Complete API documentation
- [Requirements Document](./requirements.md) - System requirements
- [Design Document](./design.md) - Architecture and design
- [Integration Guide](./integration-guide.md) - Component integration

### Code Examples

- [Unit Tests](../../tests/Unit/Auth/RBAC/) - Test examples
- [Feature Tests](../../tests/Feature/Auth/RBAC/) - Integration examples
- [Performance Tests](../../tests/Performance/Auth/RBAC/) - Performance examples

### Configuration

- [Config File](../../config/canvastack-rbac.php) - Configuration options
- [Migration Files](../../database/migrations/) - Database schema

### Support

- GitHub Issues: Report bugs and request features
- Documentation: Check docs for detailed information
- Team Discussions: Ask questions in team channels

---

## 🎓 Learning Path

### Beginner

1. Read this usage guide
2. Understand the four permission types
3. Try basic examples in a test project
4. Use FormBuilder and TableBuilder integration

### Intermediate

5. Implement row-level permissions
6. Add column-level restrictions
7. Use Blade directives in views
8. Configure caching and performance

### Advanced

9. Implement conditional rules
10. Create user overrides
11. Build custom template variables
12. Optimize for production use

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Published  
**Author**: CanvaStack Team


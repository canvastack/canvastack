# Component Reference

Complete reference for all CanvaStack components.

## 📦 Available Components

### Core Components

1. **[FormBuilder](form-builder.md)** - Dynamic form generation with validation
2. **[TableBuilder](table-builder.md)** - Optimized data tables with caching
3. **[ChartBuilder](chart-builder.md)** - Data visualization and charts
4. **[MetaTags](meta-tags.md)** - SEO and social media meta tags

### UI Components

5. **[Button](ui-components.md#button)** - Styled buttons with variants
6. **[Card](ui-components.md#card)** - Content cards with hover effects
7. **[Badge](ui-components.md#badge)** - Status badges and labels
8. **[Modal](ui-components.md#modal)** - Alpine.js powered modals
9. **[Dropdown](ui-components.md#dropdown)** - Dropdown menus
10. **[Alert](ui-components.md#alert)** - Notification alerts

---

## 🎯 Component Overview

### FormBuilder

**Purpose**: Create dynamic forms with validation, multiple field types, and admin/public rendering.

**Key Features**:
- 20+ field types (text, email, select, textarea, etc.)
- Built-in validation with caching
- File upload with preview
- WYSIWYG editor integration
- Tabs and sections
- Ajax cascading dropdowns
- Admin + Public rendering

**Quick Example**:
```php
$form->text('name', 'Name')->required();
$form->email('email', 'Email')->required();
$form->select('status', 'Status', ['active' => 'Active']);
```

[Full Documentation →](form-builder.md)

---

### TableBuilder

**Purpose**: Display tabular data with sorting, filtering, pagination, and export.

**Key Features**:
- Eloquent model support
- Collection/array support
- Query optimization (eager loading, caching)
- Server-side processing
- Responsive design
- Export (CSV, Excel, PDF)
- Custom column rendering
- Bulk actions

**Quick Example**:
```php
$table->setModel(new User());
$table->setFields(['name:Name', 'email:Email']);
$table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
$table->format();
```

[Full Documentation →](table-builder.md)

---

### ChartBuilder

**Purpose**: Create interactive charts and data visualizations.

**Key Features**:
- Multiple chart types (line, bar, pie, donut, area)
- Real-time data updates
- Responsive design
- Dark mode support
- Data caching
- AJAX data loading
- Custom colors and styling

**Quick Example**:
```php
$chart->line([
    ['name' => 'Sales', 'data' => [10, 20, 30, 40]]
], ['Jan', 'Feb', 'Mar', 'Apr']);
```

[Full Documentation →](chart-builder.md)

---

### MetaTags

**Purpose**: Manage SEO meta tags, Open Graph, Twitter Cards, and JSON-LD.

**Key Features**:
- SEO optimization
- Open Graph for social media
- Twitter Cards
- JSON-LD structured data
- Theme integration
- Automatic generation

**Quick Example**:
```php
$meta->title('Page Title');
$meta->description('Page description for SEO');
$meta->keywords('keyword1, keyword2');
```

[Full Documentation →](meta-tags.md)

---

## 🎨 UI Components

### Button

Styled buttons with multiple variants and sizes.

**Variants**: primary, secondary, outline, ghost  
**Sizes**: sm, md, lg

```blade
<x-canvastack::button variant="primary" size="md">
    Save
</x-canvastack::button>
```

### Card

Content cards with optional hover effects.

```blade
<x-canvastack::card hover="true">
    <x-slot:title>Card Title</x-slot:title>
    Card content here
</x-canvastack::card>
```

### Badge

Status badges with semantic colors.

**Colors**: success, warning, error, info

```blade
<x-canvastack::badge color="success">Active</x-canvastack::badge>
```

### Modal

Alpine.js powered modals with animations.

```blade
<x-canvastack::modal id="myModal" title="Modal Title">
    Modal content here
</x-canvastack::modal>
```

[Full UI Components Documentation →](ui-components.md)

---

## 📖 Usage Patterns

### Pattern 1: CRUD List Page

```php
public function index(TableBuilder $table, MetaTags $meta): View
{
    $meta->title('Users')->description('Manage users');
    
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
    $table->addAction('delete', route('users.destroy', ':id'), 'trash', 'Delete', 'DELETE');
    $table->format();
    
    return view('users.index', compact('table', 'meta'));
}
```

### Pattern 2: Create/Edit Form

```php
public function create(FormBuilder $form, MetaTags $meta): View
{
    $meta->title('Create User');
    
    $form->setContext('admin');
    $form->text('name', 'Name')->required();
    $form->email('email', 'Email')->required();
    $form->password('password', 'Password')->required();
    
    return view('users.create', compact('form', 'meta'));
}
```

### Pattern 3: Dashboard with Charts

```php
public function dashboard(ChartBuilder $chart, MetaTags $meta): View
{
    $meta->title('Dashboard');
    
    $chart->setContext('admin');
    $chart->line([
        ['name' => 'Sales', 'data' => $this->getSalesData()]
    ], $this->getMonths());
    
    return view('dashboard', compact('chart', 'meta'));
}
```

---

## 🔧 Component Configuration

### Global Configuration

Configure components in `config/canvastack.php`:

```php
return [
    'components' => [
        'form' => [
            'default_context' => 'admin',
            'validation_cache' => true,
            'cache_ttl' => 3600,
        ],
        'table' => [
            'default_context' => 'admin',
            'per_page' => 25,
            'cache_enabled' => true,
            'cache_ttl' => 300,
        ],
        'chart' => [
            'default_context' => 'admin',
            'cache_enabled' => true,
            'cache_ttl' => 600,
        ],
    ],
];
```

### Per-Component Configuration

Configure individual components:

```php
// FormBuilder
$form->setConfig([
    'validation_cache' => true,
    'ajax_submit' => true,
]);

// TableBuilder
$table->setConfig([
    'per_page' => 50,
    'cache_ttl' => 600,
]);

// ChartBuilder
$chart->setConfig([
    'height' => 400,
    'colors' => ['#6366f1', '#8b5cf6'],
]);
```

---

## 🎯 Best Practices

### 1. Always Use Dependency Injection

```php
// ✅ Good
public function index(TableBuilder $table, MetaTags $meta): View
{
    // Use components
}

// ❌ Bad
public function index(): View
{
    $table = new TableBuilder();
}
```

### 2. Set Context for All Components

```php
// ✅ Good
$form->setContext('admin');
$table->setContext('public');

// ❌ Bad
// No context set - uses default
```

### 3. Use MetaTags on Every Page

```php
// ✅ Good
$meta->title('Page Title');
$meta->description('Page description');

// ❌ Bad
// No meta tags - poor SEO
```

### 4. Enable Caching for Performance

```php
// ✅ Good
$table->cache(300); // 5 minutes
$chart->cache(600); // 10 minutes

// ❌ Bad
// No caching - slow performance
```

### 5. Use Eager Loading for Relationships

```php
// ✅ Good
$table->eager(['user', 'category']);

// ❌ Bad
// No eager loading - N+1 queries
```

---

## 📚 Component Comparison

| Feature | FormBuilder | TableBuilder | ChartBuilder |
|---------|-------------|--------------|--------------|
| Admin Rendering | ✅ | ✅ | ✅ |
| Public Rendering | ✅ | ✅ | ✅ |
| Caching | ✅ | ✅ | ✅ |
| Dark Mode | ✅ | ✅ | ✅ |
| Responsive | ✅ | ✅ | ✅ |
| AJAX Support | ✅ | ✅ | ✅ |
| Export | ❌ | ✅ | ✅ |
| Real-time | ❌ | ❌ | ✅ |

---

## 🔗 Related Documentation

- [FormBuilder API](../api/form.md)
- [TableBuilder API](../api/table.md)
- [ChartBuilder API](../api/chart.md)
- [Theme System](../features/theming.md)
- [i18n System](../features/i18n.md)
- [RBAC System](../features/rbac.md)

---

## 📞 Support

### Questions About Components

- Check component documentation
- Review examples in `tests/`
- Ask in team discussions

### Reporting Issues

- Use GitHub issues for bugs
- Tag with `component` label
- Provide code examples
- Include expected vs actual output

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

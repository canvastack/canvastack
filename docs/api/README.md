# API Reference

Complete API reference for all CanvaStack components and services.

## 📦 Available APIs

### Core Components

1. **[FormBuilder API](form.md)** - Form generation and validation
2. **[TableBuilder API](table.md)** - Data table management
3. **[ChartBuilder API](chart.md)** - Chart and visualization
4. **[MetaTags API](meta-tags.md)** - SEO and meta tags

### Services

5. **[Theme API](theme-api.md)** - Theme management
6. **[Locale API](locale-api.md)** - Internationalization
7. **[RBAC API](rbac.md)** - Role-based access control
8. **[Cache API](cache.md)** - Caching system

### Utilities

9. **[Helper Functions](helpers.md)** - Global helper functions
10. **[Blade Directives](blade-directives.md)** - Custom Blade directives

---

## 🎯 Quick Reference

### FormBuilder

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;

public function create(FormBuilder $form): View
{
    $form->setContext('admin');
    $form->text('name', 'Name')->required();
    $form->email('email', 'Email')->required();
    $form->select('status', 'Status', $options);
    
    return view('view', ['form' => $form]);
}
```

[Full API Documentation →](form.md)

---

### TableBuilder

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    $table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
    $table->format();
    
    return view('view', ['table' => $table]);
}
```

[Full API Documentation →](table.md)

---

### ChartBuilder

```php
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

public function dashboard(ChartBuilder $chart): View
{
    $chart->setContext('admin');
    $chart->line([
        ['name' => 'Sales', 'data' => [10, 20, 30]]
    ], ['Jan', 'Feb', 'Mar']);
    
    return view('view', ['chart' => $chart]);
}
```

[Full API Documentation →](chart.md)

---

### Theme API

```php
use Canvastack\Canvastack\Facades\Theme;

// Get current theme
$theme = Theme::current();

// Get theme colors
$colors = Theme::colors();

// Switch theme
Theme::setCurrentTheme('ocean');

// Get compiled CSS
$css = Theme::getCompiledCss();
```

[Full API Documentation →](theme-api.md)

---

### Locale API

```php
use Canvastack\Canvastack\Support\Localization\LocaleManager;

$localeManager = app(LocaleManager::class);

// Set locale
$localeManager->setLocale('id');

// Get available locales
$locales = $localeManager->getAvailableLocales();

// Check if RTL
$isRtl = $localeManager->isRtl('ar');
```

[Full API Documentation →](locale-api.md)

---

### RBAC API

```php
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;

$roleManager = app(RoleManager::class);
$permissionManager = app(PermissionManager::class);

// Check permission
if ($user->can('users.edit')) {
    // Allow
}

// Assign role
$roleManager->assignRole($user, 'admin');

// Grant permission
$permissionManager->grantPermission($role, 'users.create');
```

[Full API Documentation →](rbac.md)

---

## 📖 API Conventions

### Method Naming

- **get**: Retrieve data (e.g., `getColors()`)
- **set**: Set data (e.g., `setContext()`)
- **add**: Add item (e.g., `addAction()`)
- **remove**: Remove item (e.g., `removeField()`)
- **has**: Check existence (e.g., `hasTheme()`)
- **is**: Boolean check (e.g., `isRtl()`)

### Parameter Types

- **string**: Text values
- **int**: Integer values
- **bool**: Boolean values
- **array**: Array values
- **object**: Object instances
- **callable**: Callback functions
- **mixed**: Multiple types

### Return Types

- **self**: Fluent interface (chainable)
- **void**: No return value
- **string**: Text return
- **array**: Array return
- **bool**: Boolean return
- **object**: Object return

---

## 🔧 Fluent Interface

Many APIs support method chaining:

```php
// FormBuilder
$form->text('name', 'Name')
    ->placeholder('Enter name')
    ->required()
    ->maxLength(100)
    ->icon('user');

// TableBuilder
$table->setModel(new User())
    ->setFields(['name:Name', 'email:Email'])
    ->cache(300)
    ->eager(['role'])
    ->format();

// ChartBuilder
$chart->line($data, $labels)
    ->height(400)
    ->colors(['#6366f1', '#8b5cf6'])
    ->cache(600);
```

---

## 📚 Common Patterns

### Pattern 1: Dependency Injection

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;

public function index(
    TableBuilder $table,
    MetaTags $meta
): View {
    // Use components
}
```

### Pattern 2: Facade Access

```php
use Canvastack\Canvastack\Facades\Theme;

$theme = Theme::current();
$colors = Theme::colors();
```

### Pattern 3: Helper Functions

```php
// Theme helpers
$color = theme_color('primary');
$font = theme_font('sans');

// Translation helpers
$text = __('ui.welcome');
$plural = trans_choice('ui.items', 5);

// Cache helpers
cache()->remember('key', 3600, fn() => $data);
```

---

## 🎯 Error Handling

### Exceptions

All APIs throw specific exceptions:

```php
use Canvastack\Canvastack\Exceptions\ThemeNotFoundException;
use Canvastack\Canvastack\Exceptions\InvalidContextException;

try {
    Theme::setCurrentTheme('invalid');
} catch (ThemeNotFoundException $e) {
    // Handle theme not found
}

try {
    $form->setContext('invalid');
} catch (InvalidContextException $e) {
    // Handle invalid context
}
```

### Validation

APIs validate input and throw exceptions:

```php
// Invalid field type
$form->invalidType('name', 'Label'); // Throws InvalidFieldTypeException

// Invalid locale
$localeManager->setLocale('invalid'); // Throws InvalidLocaleException

// Invalid permission
$permissionManager->check('invalid'); // Throws PermissionNotFoundException
```

---

## 🔍 Type Hints

All APIs use strict type hints:

```php
// Method signature
public function setContext(string $context): self
{
    // Implementation
}

// Usage
$form->setContext('admin'); // ✅ Valid
$form->setContext(123);     // ❌ TypeError
```

---

## 📊 API Comparison

| Feature | FormBuilder | TableBuilder | ChartBuilder |
|---------|-------------|--------------|--------------|
| Fluent Interface | ✅ | ✅ | ✅ |
| Caching | ✅ | ✅ | ✅ |
| Context Support | ✅ | ✅ | ✅ |
| AJAX Support | ✅ | ✅ | ✅ |
| Event System | ✅ | ✅ | ✅ |
| Validation | ✅ | ❌ | ❌ |
| Export | ❌ | ✅ | ✅ |
| Real-time | ❌ | ❌ | ✅ |

---

## 🔗 Related Documentation

- [Component Reference](../components/)
- [Getting Started](../getting-started/)
- [Guides](../guides/)
- [Architecture](../architecture/)

---

## 📞 Support

### Questions About API

- Check API documentation
- Review examples in `tests/`
- Ask in team discussions

### Reporting Issues

- Use GitHub issues for bugs
- Tag with `api` label
- Provide code examples
- Include error messages

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

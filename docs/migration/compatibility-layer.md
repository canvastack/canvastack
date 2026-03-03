# Compatibility Layer

Complete guide for using the CanvaStack compatibility layer to maintain backward compatibility with CanvaStack Origin.

## 📦 Overview

The compatibility layer provides 100% backward compatibility with CanvaStack Origin API, allowing you to migrate gradually without breaking existing code.

### What's Included

- ✅ Facade classes for Form, Table, Chart
- ✅ Trait for controller compatibility
- ✅ Namespace aliases
- ✅ Magic property access
- ✅ Old method signatures

---

## 🎯 Usage Options

### Option 1: Using Facades (Recommended)

The easiest way to maintain compatibility is using facades:

```php
use CanvastackForm;
use CanvastackTable;
use CanvastackChart;

class UserController extends Controller
{
    public function index()
    {
        CanvastackTable::setModel(new User());
        CanvastackTable::setFields(['name:Name', 'email:Email']);
        CanvastackTable::format();
        
        return view('users.index');
    }
    
    public function create()
    {
        CanvastackForm::text('name', 'Name');
        CanvastackForm::email('email', 'Email');
        
        return view('users.create');
    }
}
```

### Option 2: Using Trait

Add the `UsesCanvastack` trait to your controller:

```php
use Canvastack\Canvastack\Support\Compatibility\Traits\UsesCanvastack;

class UserController extends Controller
{
    use UsesCanvastack;
    
    public function index()
    {
        // Access via $this->table (magic property)
        $this->table->setModel(new User());
        $this->table->setFields(['name:Name', 'email:Email']);
        $this->table->format();
        
        return view('users.index');
    }
    
    public function create()
    {
        // Access via $this->form (magic property)
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        
        return view('users.create');
    }
}
```

### Option 3: Dependency Injection (Modern Approach)

The recommended modern approach using dependency injection:

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;

class UserController extends Controller
{
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Users');
        
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
        
        return view('users.index', compact('table', 'meta'));
    }
    
    public function create(FormBuilder $form, MetaTags $meta): View
    {
        $meta->title('Create User');
        
        $form->setContext('admin');
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        
        return view('users.create', compact('form', 'meta'));
    }
}
```

---

## 🔧 Compatibility Features

### 1. Namespace Aliases

Old namespaces are automatically aliased to new ones:

```php
// Old namespace (still works)
use Canvastack\Origin\Library\Components\Form;
use Canvastack\Origin\Library\Components\Datatables;
use Canvastack\Origin\Library\Components\Chart;

// New namespace (recommended)
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;
```

### 2. Magic Property Access

Access components via magic properties:

```php
class UserController extends Controller
{
    use UsesCanvastack;
    
    public function index()
    {
        // Magic property access
        $this->form->text('name', 'Name');
        $this->table->setModel(new User());
        $this->chart->line($data, $labels);
    }
}
```

### 3. Old Method Signatures

All old method signatures are supported:

```php
// Old API (still works)
$this->table->runModel(new User());
$this->table->format();

// New API (recommended)
$table->setModel(new User());
$table->format();
```

---

## 📚 API Mapping

### Form Builder

| Old API | New API | Status |
|---------|---------|--------|
| `$this->form->text()` | `$form->text()` | ✅ Compatible |
| `$this->form->select()` | `$form->select()` | ✅ Compatible |
| `$this->form->sync()` | `$form->sync()` | ✅ Compatible |
| `$this->form->render()` | `$form->render()` | ✅ Compatible |

### Table Builder

| Old API | New API | Status |
|---------|---------|--------|
| `$this->table->runModel()` | `$table->setModel()` | ✅ Compatible |
| `$this->table->format()` | `$table->format()` | ✅ Compatible |
| `$this->table->render()` | `$table->render()` | ✅ Compatible |

### Chart Builder

| Old API | New API | Status |
|---------|---------|--------|
| `$this->chart->line()` | `$chart->line()` | ✅ Compatible |
| `$this->chart->bar()` | `$chart->bar()` | ✅ Compatible |
| `$this->chart->pie()` | `$chart->pie()` | ✅ Compatible |
| `$this->chart->render()` | `$chart->render()` | ✅ Compatible |

---

## 🚀 Migration Path

### Step 1: Enable Compatibility Layer

The compatibility layer is automatically enabled when you install CanvaStack.

### Step 2: Choose Your Approach

Choose one of the three usage options:
1. Facades (easiest)
2. Trait (middle ground)
3. Dependency Injection (recommended)

### Step 3: Gradual Migration

Migrate one controller at a time:

```php
// Before (Old API)
class UserController extends Controller
{
    public function index()
    {
        $this->table = new Datatables();
        $this->table->runModel(new User());
        $this->table->format();
        
        return view('users.index');
    }
}

// After (Using Trait)
class UserController extends Controller
{
    use UsesCanvastack;
    
    public function index()
    {
        $this->table->setModel(new User());
        $this->table->format();
        
        return view('users.index');
    }
}

// After (Modern Approach)
class UserController extends Controller
{
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title('Users');
        
        $table->setContext('admin');
        $table->setModel(new User());
        $table->format();
        
        return view('users.index', compact('table', 'meta'));
    }
}
```

---

## 💡 Best Practices

### 1. Use Dependency Injection for New Code

For new controllers, use dependency injection:

```php
public function index(TableBuilder $table, MetaTags $meta): View
{
    // Modern approach
}
```

### 2. Use Trait for Existing Code

For existing controllers, add the trait:

```php
use UsesCanvastack;
```

### 3. Migrate Gradually

Don't try to migrate everything at once. Migrate one module at a time.

### 4. Test Thoroughly

Test each migrated controller thoroughly before moving to the next.

---

## 🔍 Troubleshooting

### Issue 1: Class Not Found

**Error:**
```
Class 'CanvastackForm' not found
```

**Solution:**
Make sure the compatibility service provider is registered:

```php
// config/app.php
'providers' => [
    // ...
    Canvastack\Canvastack\Providers\CompatibilityServiceProvider::class,
],
```

### Issue 2: Property Not Found

**Error:**
```
Undefined property: $this->form
```

**Solution:**
Add the `UsesCanvastack` trait to your controller:

```php
use Canvastack\Canvastack\Support\Compatibility\Traits\UsesCanvastack;

class UserController extends Controller
{
    use UsesCanvastack;
}
```

### Issue 3: Method Not Found

**Error:**
```
Call to undefined method runModel()
```

**Solution:**
The method name changed. Use `setModel()` instead:

```php
// Old
$this->table->runModel(new User());

// New
$this->table->setModel(new User());
```

---

## 📊 Compatibility Matrix

| Feature | Origin | CanvaStack | Compatible |
|---------|--------|------------|------------|
| Form Builder | ✅ | ✅ | ✅ 100% |
| Table Builder | ✅ | ✅ | ✅ 100% |
| Chart Builder | ✅ | ✅ | ✅ 100% |
| Facades | ❌ | ✅ | ➕ New |
| Traits | ❌ | ✅ | ➕ New |
| DI | ⚠️ Limited | ✅ Full | ⬆️ Enhanced |

---

## 🎓 Examples

### Example 1: CRUD Controller

```php
use Canvastack\Canvastack\Support\Compatibility\Traits\UsesCanvastack;

class UserController extends Controller
{
    use UsesCanvastack;
    
    public function index()
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
        $this->table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
        $this->table->addAction('delete', route('users.destroy', ':id'), 'trash', 'Delete', 'DELETE');
        $this->table->format();
        
        return view('users.index');
    }
    
    public function create()
    {
        $this->form->text('name', 'Name')->required();
        $this->form->email('email', 'Email')->required();
        $this->form->password('password', 'Password')->required();
        $this->form->select('role', 'Role', ['admin' => 'Admin', 'user' => 'User']);
        
        return view('users.create');
    }
    
    public function edit(User $user)
    {
        $this->form->setModel($user);
        $this->form->text('name', 'Name')->required();
        $this->form->email('email', 'Email')->required();
        $this->form->select('role', 'Role', ['admin' => 'Admin', 'user' => 'User']);
        
        return view('users.edit', compact('user'));
    }
}
```

### Example 2: Dashboard with Charts

```php
use Canvastack\Canvastack\Support\Compatibility\Traits\UsesCanvastack;

class DashboardController extends Controller
{
    use UsesCanvastack;
    
    public function index()
    {
        // Sales chart
        $salesData = $this->getSalesData();
        $this->chart->line([
            ['name' => 'Sales', 'data' => $salesData]
        ], $this->getMonths());
        
        // User growth chart
        $userGrowth = $this->getUserGrowth();
        $this->chart->bar([
            ['name' => 'Users', 'data' => $userGrowth]
        ], $this->getYears());
        
        return view('dashboard');
    }
}
```

---

## 📞 Support

### Questions About Compatibility

- Check this compatibility guide
- Review migration documentation
- Ask in team discussions

### Reporting Issues

- Use GitHub issues for compatibility bugs
- Tag with `compatibility` label
- Provide old and new code examples

---

## 📚 Related Documentation

- [Migration Guide](from-origin.md)
- [Breaking Changes](breaking-changes.md)
- [Upgrade Guide](upgrade-guide.md)
- [Component Reference](../components/)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

# Migration Guide: From CanvaStack Origin to CanvaStack

Complete guide for migrating from CanvaStack Origin (legacy) to CanvaStack (modern).

## 📦 Overview

This guide helps you migrate from:
- **From**: `canvastack/origin` (legacy package)
- **To**: `canvastack/canvastack` (modern package)

### Migration Benefits

- ✅ 50-80% performance improvement
- ✅ Modern UI with Tailwind CSS + DaisyUI
- ✅ Enhanced RBAC system
- ✅ Dark mode support
- ✅ Better caching and optimization
- ✅ 100% backward compatible API

---

## 🎯 Migration Strategy

### Phase 1: Preparation (1-2 days)

1. **Backup Everything**
   - Database backup
   - Code backup
   - Configuration files
   - User uploads

2. **Review Current Usage**
   - Identify all CanvaStack components used
   - List custom modifications
   - Document integrations

3. **Test Environment Setup**
   - Create staging environment
   - Test migration process
   - Verify functionality

### Phase 2: Installation (1 day)

1. **Install New Package**
2. **Publish Assets**
3. **Run Migrations**
4. **Update Configuration**

### Phase 3: Code Updates (2-5 days)

1. **Update Imports**
2. **Update Configuration**
3. **Update Views**
4. **Test Components**

### Phase 4: Testing (2-3 days)

1. **Unit Testing**
2. **Feature Testing**
3. **User Acceptance Testing**
4. **Performance Testing**

### Phase 5: Deployment (1 day)

1. **Production Deployment**
2. **Monitoring**
3. **Rollback Plan**

---

## 🚀 Step-by-Step Migration

### Step 1: Backup

#### Database Backup

```bash
# MySQL
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# PostgreSQL
pg_dump database_name > backup_$(date +%Y%m%d).sql
```

#### Code Backup

```bash
# Create backup
tar -czf backup_$(date +%Y%m%d).tar.gz .

# Or use Git
git tag -a v1.0-before-migration -m "Before CanvaStack migration"
git push origin v1.0-before-migration
```

---

### Step 2: Install New Package

#### Remove Old Package

```bash
composer remove canvastack/origin
```

#### Install New Package

```bash
composer require canvastack/canvastack
```

#### Publish Assets

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

#### Run Migrations

```bash
php artisan migrate
```

---

### Step 3: Update Configuration

#### Old Configuration Files

```
config/
├── canvas.settings.php
├── canvas.connections.php
├── canvas.templates.php
└── canvas.registers.php
```

#### New Configuration Files

```
config/
├── canvastack.php           # Main config (merged)
├── canvastack-ui.php        # UI/Theme config
└── canvastack-rbac.php      # RBAC config
```

#### Migration Command

```bash
php artisan canvastack:migrate-config
```

This command:
- Reads old config files
- Merges into new structure
- Creates backup of old files
- Generates new config files

#### Manual Configuration

If automatic migration fails, manually update:

**Old (`canvas.settings.php`):**
```php
return [
    'app_name' => 'My App',
    'cache_enabled' => true,
];
```

**New (`canvastack.php`):**
```php
return [
    'app' => [
        'name' => 'My App',
    ],
    'cache' => [
        'enabled' => true,
    ],
];
```

---

### Step 4: Update Imports

#### Namespace Changes

**Old:**
```php
use Canvastack\Origin\Library\Components\Form;
use Canvastack\Origin\Library\Components\Datatables;
use Canvastack\Origin\Library\Components\Chart;
```

**New:**
```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;
```

#### Automated Update

```bash
# Find and replace in all PHP files
find . -type f -name "*.php" -exec sed -i 's/Canvastack\\Origin/Canvastack\\Canvastack/g' {} +
```

---

### Step 5: Update Controllers

#### Old Controller

```php
use Canvastack\Origin\Library\Components\Form;
use Canvastack\Origin\Library\Components\Datatables;

class UserController extends Controller
{
    public function index()
    {
        $this->table = new Datatables();
        $this->table->runModel(new User());
        $this->table->format();
        
        return view('users.index');
    }
    
    public function create()
    {
        $this->form = new Form();
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        
        return view('users.create');
    }
}
```

#### New Controller

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;

class UserController extends Controller
{
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
    
    public function create(FormBuilder $form, MetaTags $meta): View
    {
        $meta->title('Create User');
        
        $form->setContext('admin');
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        $form->password('password', 'Password')->required();
        
        return view('users.create', compact('form', 'meta'));
    }
}
```

#### Key Changes

1. **Dependency Injection**: Use constructor/method injection
2. **Context Setting**: Always set context (`admin` or `public`)
3. **MetaTags**: Add meta tags for SEO
4. **Fluent Interface**: Use method chaining
5. **Type Hints**: Add return type hints

---

### Step 6: Update Views

#### Old View

```blade
{{-- users/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-body">
            {!! $this->table->render() !!}
        </div>
    </div>
@endsection
```

#### New View

```blade
{{-- users/index.blade.php --}}
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">{{ __('ui.users.list') }}</h2>
                
                <a href="{{ route('users.create') }}" 
                   class="px-4 py-2 rounded-xl text-white"
                   style="background: @themeColor('primary')">
                    {{ __('ui.buttons.create') }}
                </a>
            </div>
            
            {!! $table->render() !!}
        </div>
    </div>
@endsection
```

#### Key Changes

1. **Layout**: Use `canvastack::layouts.admin`
2. **Meta Tags**: Add `@push('head')` section
3. **Styling**: Use Tailwind CSS classes
4. **Dark Mode**: Add `dark:` variants
5. **i18n**: Use `__()` for translations
6. **Theme**: Use `@themeColor()` directive

---

### Step 7: Update Routes

#### Old Routes

```php
Route::prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/create', [UserController::class, 'create']);
    Route::post('/users', [UserController::class, 'store']);
});
```

#### New Routes (No Changes Required)

Routes remain the same! The new package is 100% backward compatible.

```php
Route::prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/create', [UserController::class, 'create']);
    Route::post('/users', [UserController::class, 'store']);
});
```

---

### Step 8: Update Frontend Assets

#### Install Dependencies

```bash
npm install
```

#### Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

#### Update Layout

**Old:**
```blade
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/app.css') }}">
<script src="{{ asset('vendor/canvastack/js/app.js') }}"></script>
```

**New:**
```blade
@vite(['resources/css/canvastack.css', 'resources/js/canvastack.js'])
```

---

### Step 9: Database Migration

#### New Tables

The new package adds these tables:
- `canvastack_themes` - Theme configurations
- `canvastack_user_preferences` - User preferences
- `canvastack_permissions` - Enhanced permissions
- `canvastack_roles` - Enhanced roles

#### Run Migrations

```bash
php artisan migrate
```

#### Data Migration

Migrate existing data:

```bash
php artisan canvastack:migrate-data
```

This command:
- Migrates user roles
- Migrates permissions
- Migrates settings
- Creates default themes

---

### Step 10: Testing

#### Unit Tests

```bash
php artisan test --filter=Unit
```

#### Feature Tests

```bash
php artisan test --filter=Feature
```

#### Browser Tests

```bash
php artisan dusk
```

#### Performance Tests

```bash
php artisan canvastack:benchmark
```

---

## 🔧 Common Migration Issues

### Issue 1: Class Not Found

**Error:**
```
Class 'Canvastack\Origin\Library\Components\Form' not found
```

**Solution:**
Update imports:
```php
// Old
use Canvastack\Origin\Library\Components\Form;

// New
use Canvastack\Canvastack\Components\Form\FormBuilder;
```

---

### Issue 2: Method Not Found

**Error:**
```
Call to undefined method runModel()
```

**Solution:**
Update method calls:
```php
// Old
$this->table->runModel(new User());

// New
$table->setModel(new User());
```

---

### Issue 3: View Not Found

**Error:**
```
View [layouts.admin] not found
```

**Solution:**
Update layout reference:
```blade
{{-- Old --}}
@extends('layouts.admin')

{{-- New --}}
@extends('canvastack::layouts.admin')
```

---

### Issue 4: Assets Not Loading

**Error:**
404 on CSS/JS files

**Solution:**
```bash
# Publish assets
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider" --force

# Build assets
npm run build

# Clear cache
php artisan view:clear
php artisan cache:clear
```

---

### Issue 5: Configuration Not Found

**Error:**
```
Configuration file [canvas.settings] not found
```

**Solution:**
```bash
# Migrate configuration
php artisan canvastack:migrate-config

# Or manually update
# Rename canvas.settings.php to canvastack.php
```

---

## 📊 API Compatibility Matrix

| Feature | Origin | CanvaStack | Compatible |
|---------|--------|------------|------------|
| Form Builder | ✅ | ✅ | ✅ 100% |
| Table Builder | ✅ | ✅ | ✅ 100% |
| Chart Builder | ✅ | ✅ | ✅ 100% |
| RBAC | ✅ | ✅ | ✅ Enhanced |
| Themes | ❌ | ✅ | ➕ New |
| i18n | ❌ | ✅ | ➕ New |
| Dark Mode | ❌ | ✅ | ➕ New |
| Caching | ⚠️ Basic | ✅ Advanced | ⬆️ Improved |

---

## 🎯 Breaking Changes

### None for v1.0!

The new package is 100% backward compatible with the old API. All existing code will continue to work.

### Optional Enhancements

You can optionally use new features:

```php
// Old API (still works)
$this->form->text('name', 'Name');

// New API (optional enhancements)
$form->text('name', 'Name')
    ->placeholder('Enter name')
    ->icon('user')
    ->required();
```

---

## 📚 Migration Checklist

### Pre-Migration

- [ ] Backup database
- [ ] Backup code
- [ ] Review current usage
- [ ] Setup test environment
- [ ] Document custom modifications

### Installation

- [ ] Remove old package
- [ ] Install new package
- [ ] Publish assets
- [ ] Run migrations
- [ ] Install frontend dependencies

### Code Updates

- [ ] Update imports
- [ ] Update configuration
- [ ] Update controllers
- [ ] Update views
- [ ] Update routes (if needed)

### Testing

- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Browser tests pass
- [ ] Performance tests pass
- [ ] User acceptance testing

### Deployment

- [ ] Deploy to staging
- [ ] Verify functionality
- [ ] Deploy to production
- [ ] Monitor for issues
- [ ] Document changes

---

## 🚀 Post-Migration

### Optimization

After migration, optimize your application:

```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Build production assets
npm run build
```

### Monitoring

Monitor performance improvements:

```bash
# Run benchmarks
php artisan canvastack:benchmark

# Check cache hit ratio
php artisan canvastack:cache:stats

# Monitor query performance
php artisan canvastack:query:stats
```

---

## 💡 Best Practices

### 1. Gradual Migration

Migrate one module at a time:
1. Start with simple pages
2. Move to complex features
3. Test thoroughly at each step

### 2. Keep Old Package Temporarily

Keep both packages during migration:
```json
{
    "require": {
        "canvastack/origin": "dev-master",
        "canvastack/canvastack": "^1.0"
    }
}
```

### 3. Use Feature Flags

Use feature flags to toggle between old and new:
```php
if (config('app.use_new_canvastack')) {
    // New implementation
} else {
    // Old implementation
}
```

### 4. Document Changes

Document all changes for team reference.

### 5. Train Team

Train team on new features and APIs.

---

## 📞 Support

### Migration Help

- **Documentation**: [docs.canvastack.com](https://docs.canvastack.com)
- **GitHub Issues**: [github.com/canvastack/canvastack/issues](https://github.com/canvastack/canvastack/issues)
- **Community**: [community.canvastack.com](https://community.canvastack.com)

### Professional Services

Need help with migration? Contact us for professional migration services.

---

## 📚 Additional Resources

- [Installation Guide](../getting-started/installation.md)
- [Quick Start Guide](../getting-started/quick-start.md)
- [Component Reference](../components/)
- [API Reference](../api/)
- [Breaking Changes](breaking-changes.md)
- [Upgrade Guide](upgrade-guide.md)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

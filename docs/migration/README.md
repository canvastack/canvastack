# Migration Documentation

Complete guide for migrating from CanvaStack Origin to CanvaStack.

## 📚 Documentation Index

### Getting Started

1. **[Migration Guide](from-origin.md)** - Complete step-by-step migration guide
   - Preparation checklist
   - Installation steps
   - Code updates
   - Testing procedures
   - Deployment guide

2. **[Compatibility Layer](compatibility-layer.md)** - Backward compatibility guide
   - Usage options (Facades, Traits, DI)
   - API mapping
   - Migration path
   - Troubleshooting

3. **[Breaking Changes](breaking-changes.md)** - List of breaking changes (if any)
   - API changes
   - Configuration changes
   - View changes

4. **[Upgrade Guide](upgrade-guide.md)** - Version upgrade guide
   - Version-specific changes
   - Upgrade procedures

---

## 🚀 Quick Start

### 1. Install New Package

```bash
composer remove canvastack/origin
composer require canvastack/canvastack
```

### 2. Publish Assets

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Migrate Configuration

```bash
php artisan canvastack:migrate-config --backup
```

### 5. Migrate Views

```bash
php artisan canvastack:migrate-views --backup
```

### 6. Clear Cache

```bash
php artisan canvastack:cache-clear --all
php artisan config:clear
php artisan view:clear
```

---

## 🛠️ Migration Tools

### canvastack:migrate-config

Migrates old CanvaStack Origin config files to new structure.

**Usage:**
```bash
# Basic migration
php artisan canvastack:migrate-config

# With backup
php artisan canvastack:migrate-config --backup

# Force overwrite
php artisan canvastack:migrate-config --force

# Dry run (preview changes)
php artisan canvastack:migrate-config --dry-run
```

**What it does:**
- Reads old config files (canvas.settings.php, canvas.connections.php, etc.)
- Converts to new structure (canvastack.php, canvastack-ui.php, etc.)
- Merges with existing configs
- Creates backup if requested

**Old → New Mapping:**
- `canvas.settings.php` → `canvastack.php`
- `canvas.connections.php` → `canvastack.php` (database section)
- `canvas.templates.php` → `canvastack-ui.php`
- `canvas.registers.php` → `canvastack.php` (modules section)

---

### canvastack:migrate-views

Migrates old CanvaStack Origin views to new structure and syntax.

**Usage:**
```bash
# Migrate all views
php artisan canvastack:migrate-views

# Migrate specific path
php artisan canvastack:migrate-views users

# With backup
php artisan canvastack:migrate-views --backup

# Force overwrite
php artisan canvastack:migrate-views --force

# Dry run (preview changes)
php artisan canvastack:migrate-views --dry-run
```

**What it does:**
- Updates layout references (`layouts.admin` → `canvastack::layouts.admin`)
- Updates component references (`$this->form` → `$form`)
- Converts Bootstrap classes to Tailwind CSS
- Adds meta tags sections
- Converts hardcoded text to translations
- Converts hardcoded colors to theme colors

**Replacements:**
- Layout: `@extends('layouts.admin')` → `@extends('canvastack::layouts.admin')`
- Components: `$this->form->` → `$form->`
- Styling: Bootstrap → Tailwind CSS
- Text: Hardcoded → `__('ui.key')`
- Colors: `#6366f1` → `@themeColor('primary')`

---

### canvastack:cache-clear

Clears CanvaStack cache (already exists, enhanced for migration).

**Usage:**
```bash
# Clear all cache
php artisan canvastack:cache-clear --all

# Clear specific component
php artisan canvastack:cache-clear forms
php artisan canvastack:cache-clear tables
php artisan canvastack:cache-clear permissions

# Interactive mode
php artisan canvastack:cache-clear
```

**What it clears:**
- Forms cache
- Tables cache
- Permissions cache
- Views cache
- Queries cache
- Config cache

---

## 🔧 Compatibility Layer

The compatibility layer provides 100% backward compatibility with CanvaStack Origin API.

### Usage Options

#### Option 1: Facades (Easiest)

```php
use CanvastackForm;
use CanvastackTable;
use CanvastackChart;

class UserController extends Controller
{
    public function index()
    {
        CanvastackTable::setModel(new User());
        CanvastackTable::format();
        
        return view('users.index');
    }
}
```

#### Option 2: Trait (Middle Ground)

```php
use Canvastack\Canvastack\Support\Compatibility\Traits\UsesCanvastack;

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
```

#### Option 3: Dependency Injection (Recommended)

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;

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

## 📊 Migration Checklist

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

### Configuration

- [ ] Run `canvastack:migrate-config`
- [ ] Review new config files
- [ ] Update .env file
- [ ] Clear config cache

### Views

- [ ] Run `canvastack:migrate-views`
- [ ] Review migrated views
- [ ] Update custom views
- [ ] Clear view cache

### Code Updates

- [ ] Update imports (if needed)
- [ ] Add compatibility trait (if needed)
- [ ] Update controllers (if needed)
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

## 🎯 Migration Strategies

### Strategy 1: Big Bang Migration

Migrate everything at once.

**Pros:**
- Faster overall
- Clean break from old code
- Easier to manage

**Cons:**
- Higher risk
- More testing required
- Potential downtime

**Best for:**
- Small applications
- Simple codebases
- Dedicated migration time

### Strategy 2: Gradual Migration

Migrate one module at a time.

**Pros:**
- Lower risk
- Easier to test
- Can be done incrementally

**Cons:**
- Takes longer
- Need to maintain both APIs
- More complex

**Best for:**
- Large applications
- Complex codebases
- Production systems

### Strategy 3: Parallel Migration

Run both versions side by side.

**Pros:**
- Zero downtime
- Easy rollback
- Thorough testing

**Cons:**
- Most complex
- Requires more resources
- Longer timeline

**Best for:**
- Critical systems
- High-traffic applications
- Risk-averse organizations

---

## 💡 Best Practices

### 1. Always Backup

Create backups before migration:
```bash
# Database
mysqldump -u user -p database > backup.sql

# Code
git tag -a v1.0-before-migration -m "Before migration"
```

### 2. Test in Staging

Always test migration in staging environment first.

### 3. Use Dry Run

Preview changes before applying:
```bash
php artisan canvastack:migrate-config --dry-run
php artisan canvastack:migrate-views --dry-run
```

### 4. Migrate Gradually

Don't try to migrate everything at once. Start with simple modules.

### 5. Monitor Performance

Compare performance before and after migration:
```bash
php artisan canvastack:benchmark
```

### 6. Document Changes

Document all changes for team reference.

### 7. Train Team

Train team on new features and APIs.

---

## 🔍 Troubleshooting

### Common Issues

1. **Class Not Found**
   - Update imports
   - Clear autoload cache: `composer dump-autoload`

2. **Method Not Found**
   - Check API mapping
   - Use compatibility layer

3. **View Not Found**
   - Update layout references
   - Clear view cache: `php artisan view:clear`

4. **Assets Not Loading**
   - Publish assets: `php artisan vendor:publish`
   - Build assets: `npm run build`

5. **Configuration Not Found**
   - Run migration: `php artisan canvastack:migrate-config`
   - Clear config cache: `php artisan config:clear`

---

## 📞 Support

### Getting Help

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
- [Theming Guide](../guides/theming.md)
- [Performance Guide](../guides/performance.md)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

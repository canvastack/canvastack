# CanvaStack Documentation

Welcome to the CanvaStack documentation! This comprehensive guide will help you get started with CanvaStack and master all its features.

## 📚 Documentation Structure

### 🚀 Getting Started

Start here if you're new to CanvaStack:

1. **[Installation Guide](getting-started/installation.md)** - Complete installation instructions
2. **[Quick Start](getting-started/quick-start.md)** - Get up and running in 5 minutes
3. **[Configuration](getting-started/configuration.md)** - Configure CanvaStack for your needs
4. **[Project Structure](getting-started/project-structure.md)** - Understand the codebase

### 📦 Components

Learn about CanvaStack's powerful components:

- **[Component Overview](components/README.md)** - All available components
- **[FormBuilder](components/form-builder.md)** - Dynamic form generation
- **[TableBuilder](components/table-builder.md)** - Optimized data tables
  - **[Bi-Directional Filter Cascade](examples/bi-directional-cascade-examples.md)** - Advanced filter cascade (NEW!)
- **[ChartBuilder](components/chart-builder.md)** - Data visualization
- **[MetaTags](components/meta-tags.md)** - SEO and social media
- **[UI Components](components/ui-components.md)** - Buttons, cards, badges, etc.

### 🎨 Frontend

Master the frontend technologies:

- **[Frontend Overview](frontend/README.md)** - Frontend stack overview
- **[Alpine.js](frontend/alpine-js.md)** - Interactive components
- **[Tailwind CSS](frontend/tailwind-css.md)** - Utility-first CSS
- **[DaisyUI](frontend/daisyui.md)** - Component library
- **[Dark Mode](frontend/dark-mode.md)** - Dark mode implementation
- **[Animations](frontend/animations.md)** - GSAP animations
- **[Icons](frontend/icons.md)** - Lucide icons

### ✨ Features

Explore CanvaStack's features:

- **[RBAC System](features/rbac.md)** - Role-based access control
- **[Caching](features/caching.md)** - Multi-layer caching
- **[Theming](features/theming.md)** - Dynamic theme system
- **[i18n](features/i18n.md)** - Internationalization

### 🏗️ Architecture

Understand the architecture:

- **[Architecture Overview](architecture/overview.md)** - System architecture
- **[Design Patterns](architecture/design-patterns.md)** - Patterns used
- **[Service Container](architecture/service-container.md)** - Dependency injection
- **[Repository Pattern](architecture/repository-pattern.md)** - Data access

### 📖 API Reference

Complete API documentation:

- **[API Overview](api/README.md)** - All available APIs
- **[FormBuilder API](api/form.md)** - Form API reference
- **[TableBuilder API](api/table.md)** - Table API reference
- **[ChartBuilder API](api/chart.md)** - Chart API reference
- **[Theme API](api/theme-api.md)** - Theme management
- **[Locale API](api/locale-api.md)** - Internationalization
- **[RBAC API](api/rbac.md)** - Access control
- **[Cache API](api/cache.md)** - Caching system

### 📝 Guides

Step-by-step guides:

- **[Creating Components](guides/creating-components.md)** - Build custom components
- **[Theming Guide](guides/theming.md)** - Create custom themes
- **[Performance Optimization](guides/performance.md)** - Optimize your app
- **[Testing Guide](guides/testing.md)** - Write tests
- **[Deployment Guide](guides/deployment.md)** - Deploy to production

### 🔄 Migration

Migrate from older versions:

- **[From Origin](migration/from-origin.md)** - Migrate from CanvaStack Origin
- **[Breaking Changes](migration/breaking-changes.md)** - Version breaking changes
- **[Upgrade Guide](migration/upgrade-guide.md)** - Upgrade between versions

### 🎓 Advanced

Advanced topics:

- **[Custom Renderers](advanced/custom-renderers.md)** - Create custom renderers
- **[Events](advanced/events.md)** - Event system
- **[Extending](advanced/extending.md)** - Extend CanvaStack
- **[Performance Tuning](advanced/performance-tuning.md)** - Advanced optimization

---

## 🎯 Quick Links

### For Beginners

1. [Installation Guide](getting-started/installation.md)
2. [Quick Start](getting-started/quick-start.md)
3. [Component Overview](components/README.md)

### For Developers

1. [API Reference](api/README.md)
2. [Architecture Overview](architecture/overview.md)
3. [Testing Guide](guides/testing.md)

### For Designers

1. [Theming Guide](guides/theming.md)
2. [Frontend Overview](frontend/README.md)
3. [UI Components](components/ui-components.md)

### For Migrators

1. [Migration from Origin](migration/from-origin.md)
2. [Breaking Changes](migration/breaking-changes.md)
3. [Upgrade Guide](migration/upgrade-guide.md)

---

## 📖 Common Tasks

### Creating a CRUD Module

1. [Create Controller with FormBuilder and TableBuilder](components/README.md#pattern-1-crud-list-page)
2. [Create Views with Blade Components](components/ui-components.md)
3. [Add Routes](getting-started/quick-start.md#step-4-create-routes)
4. [Add Permissions](features/rbac.md#assigning-permissions)

### Customizing Theme

1. [Create Custom Theme](guides/theming.md#creating-custom-themes)
2. [Configure Colors](guides/theming.md#color-palette)
3. [Add Custom Fonts](guides/theming.md#font-families)
4. [Enable Dark Mode](guides/theming.md#dark-mode)

### Adding Translations

1. [Create Translation Files](features/i18n.md#translation-file-structure)
2. [Use Translation Functions](features/i18n.md#using-translations)
3. [Add Locale Switcher](features/i18n.md#locale-switcher)
4. [Configure RTL Support](features/i18n.md#rtl-support)

### Optimizing Performance

1. [Enable Caching](features/caching.md#enabling-caching)
2. [Optimize Queries](guides/performance.md#query-optimization)
3. [Configure Redis](getting-started/installation.md#redis-configuration)
4. [Build Production Assets](guides/deployment.md#asset-optimization)

---

## 🎨 Code Examples

### Basic Form

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;

public function create(FormBuilder $form): View
{
    $form->setContext('admin');
    $form->text('name', 'Name')->required();
    $form->email('email', 'Email')->required();
    $form->select('status', 'Status', ['active' => 'Active']);
    
    return view('users.create', ['form' => $form]);
}
```

### Basic Table

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    $table->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit');
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Bi-Directional Filter Cascade (NEW!)

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable bi-directional cascade - users can select filters in ANY order!
    $table->setBidirectionalCascade(true);
    
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**What's New?**
- Users can select filters in ANY order (date first, name first, etc.)
- All filters update automatically based on selection
- No invalid filter combinations possible
- < 500ms cascade response time
- 100% backward compatible

[Learn more about Bi-Directional Filter Cascade →](examples/bi-directional-cascade-examples.md)

### Basic Chart

```php
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

public function dashboard(ChartBuilder $chart): View
{
    $chart->setContext('admin');
    $chart->line([
        ['name' => 'Sales', 'data' => [10, 20, 30, 40]]
    ], ['Jan', 'Feb', 'Mar', 'Apr']);
    
    return view('dashboard', ['chart' => $chart]);
}
```

---

## 🔧 Configuration

### Environment Variables

```env
# CanvaStack Configuration
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis
CANVASTACK_THEME=default
CANVASTACK_LOCALE=en
```

### Configuration Files

- `config/canvastack.php` - Main configuration
- `config/canvastack-ui.php` - UI and theme configuration
- `config/canvastack-rbac.php` - RBAC configuration

---

## 🎯 Best Practices

### 1. Always Use Components

Use CanvaStack components instead of raw HTML:

```php
// ✅ Good
$form->text('name', 'Name');
$table->setModel(new User());

// ❌ Bad
<input type="text" name="name">
<table>...</table>
```

### 2. Set Context

Always set context for components:

```php
// ✅ Good
$form->setContext('admin');
$table->setContext('public');

// ❌ Bad
// No context set
```

### 3. Use MetaTags

Add meta tags to every page:

```php
// ✅ Good
$meta->title('Page Title');
$meta->description('Page description');

// ❌ Bad
// No meta tags
```

### 4. Enable Caching

Enable caching for better performance:

```php
// ✅ Good
$table->cache(300);
$chart->cache(600);

// ❌ Bad
// No caching
```

### 5. Use Translations

Use translation functions for all text:

```php
// ✅ Good
__('ui.welcome')

// ❌ Bad
'Welcome'
```

---

## 📊 Performance

### Benchmarks

| Metric | Origin | CanvaStack | Improvement |
|--------|--------|------------|-------------|
| DataTable (1K rows) | ~2000ms | < 500ms | 75% faster |
| Memory usage | ~256MB | < 128MB | 50% less |
| Form render | ~200ms | < 50ms | 75% faster |

### Optimization Tips

1. Enable Redis caching
2. Use eager loading for relationships
3. Enable query result caching
4. Build production assets
5. Use CDN for static assets

---

## 🐛 Troubleshooting

### Common Issues

1. **Class Not Found** - Run `composer dump-autoload`
2. **Assets Not Loading** - Run `npm run build`
3. **Redis Connection Failed** - Check Redis is running
4. **Migration Failed** - Check database connection
5. **Permission Denied** - Fix file permissions

See [Installation Guide](getting-started/installation.md#troubleshooting) for detailed solutions.

---

## 📞 Support

### Getting Help

- **Documentation**: You're reading it!
- **GitHub Issues**: [github.com/canvastack/canvastack/issues](https://github.com/canvastack/canvastack/issues)
- **Community**: [community.canvastack.com](https://community.canvastack.com)
- **Email**: support@canvastack.com

### Contributing

We welcome contributions! See [CONTRIBUTING.md](../CONTRIBUTING.md) for guidelines.

### Reporting Bugs

Found a bug? Please report it on [GitHub Issues](https://github.com/canvastack/canvastack/issues).

---

## 📚 Additional Resources

### External Documentation

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Tailwind CSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [DaisyUI](https://daisyui.com)
- [GSAP](https://greensock.com/gsap)

### Video Tutorials

- [Getting Started with CanvaStack](https://youtube.com/watch?v=...)
- [Building a CRUD Module](https://youtube.com/watch?v=...)
- [Creating Custom Themes](https://youtube.com/watch?v=...)

### Community Resources

- [CanvaStack Blog](https://blog.canvastack.com)
- [Community Forum](https://community.canvastack.com)
- [Discord Server](https://discord.gg/canvastack)

---

## 🎓 Learning Path

### Beginner (Week 1)

1. Read [Installation Guide](getting-started/installation.md)
2. Follow [Quick Start](getting-started/quick-start.md)
3. Learn [FormBuilder](components/form-builder.md)
4. Learn [TableBuilder](components/table-builder.md)

### Intermediate (Week 2-3)

1. Master [ChartBuilder](components/chart-builder.md)
2. Learn [RBAC System](features/rbac.md)
3. Understand [Theming](features/theming.md)
4. Explore [i18n](features/i18n.md)

### Advanced (Week 4+)

1. Study [Architecture](architecture/overview.md)
2. Learn [Custom Renderers](advanced/custom-renderers.md)
3. Master [Performance Tuning](advanced/performance-tuning.md)
4. Contribute to CanvaStack

---

## 📝 Changelog

### Version 1.0.0 (2026-03-01)

- Initial release
- FormBuilder with 20+ field types
- TableBuilder with caching and optimization
- ChartBuilder with multiple chart types
- Theme Engine with dark mode
- i18n System with RTL support
- Enhanced RBAC system
- Complete documentation

See [CHANGELOG.md](../CHANGELOG.md) for full history.

---

## 📄 License

CanvaStack is open-source software licensed under the [MIT license](../LICENSE.md).

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

---

**Happy Coding! 🚀**

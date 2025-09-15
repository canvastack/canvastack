# CanvaStack Table Documentation

Welcome to the comprehensive documentation for CanvaStack Table System - a powerful, secure, and highly configurable DataTables implementation for Laravel applications.

## 📚 Table of Contents

### Getting Started
- [Installation & Setup](installation.md)
- [Quick Start Guide](quick-start.md)
- [Configuration](configuration.md)
- [Basic Usage](basic-usage.md)

### Core Concepts
- [Architecture Overview](architecture.md)
- [Data Sources](data-sources.md)
- [Column Management](columns.md)
- [Server-Side Processing](server-side.md)

### API Reference
- [Objects Class](api/objects.md) - Main entry point and orchestrator
- [Builder Class](api/builder.md) - HTML and configuration builder
- [Datatables Class](api/datatables.md) - Server-side processing engine
- [Search & Filtering](api/search.md) - Advanced filtering system

### Features
- [Actions & Buttons](features/actions.md)
- [Filtering & Search](features/filtering.md)
- [Sorting & Ordering](features/sorting.md)
- [Export Functionality](features/export.md)
- [Fixed Columns](features/fixed-columns.md)
- [Image Handling](features/images.md)
- [Relationships](features/relationships.md)

### Advanced Topics
- [Security Features](advanced/security.md)
- [Performance Optimization](advanced/performance.md)
- [Custom Middleware](advanced/middleware.md)
- [Testing](advanced/testing.md)
- [Troubleshooting](advanced/troubleshooting.md)

### Method References
- [GET Method](methods/get.md)
- [POST Method](methods/post.md)
- [AJAX Handling](methods/ajax.md)

### Traits & Extensions
- [Available Traits](traits/overview.md)
- [Custom Extensions](traits/custom.md)

### Examples & Tutorials
- [Basic Table](examples/basic.md)
- [Advanced Filtering](examples/filtering.md)
- [Custom Actions](examples/actions.md)
- [Multiple Data Sources](examples/data-sources.md)
- [Real-world Examples](examples/real-world.md)

---

## 🚀 Quick Example

```php
// In your Controller
public function index()
{
    $this->table->method('POST')
                ->searchable()
                ->clickable()
                ->sortable()
                ->relations($this->model, 'group', 'group_info')
                ->filterGroups('username', 'selectbox', true)
                ->orderby('id', 'DESC')
                ->lists($this->model_table, [
                    'username:User', 
                    'email', 
                    'group_info', 
                    'active'
                ]);

    return $this->render();
}
```

## 🛡️ Security First

CanvaStack Table is built with security as a top priority:
- SQL Injection Prevention
- XSS Protection
- CSRF Token Validation
- Input Sanitization
- Rate Limiting

## 🎯 Key Features

- **Multi-Data Source Support**: Eloquent Models, Query Builder, Raw SQL
- **Advanced Filtering**: Modal-based filtering with dependency chains
- **Server-Side Processing**: High-performance data handling
- **Flexible Configuration**: Fluent API with method chaining
- **Security Hardened**: Multiple layers of security protection
- **Extensible Architecture**: Trait-based modular system

## 📖 Documentation Structure

This documentation is organized into logical sections:

1. **Getting Started** - Everything you need to begin using CanvaStack Tables
2. **Core Concepts** - Understanding the fundamental architecture
3. **API Reference** - Detailed method and class documentation
4. **Features** - Comprehensive feature guides
5. **Advanced Topics** - Deep-dive into complex scenarios
6. **Examples** - Practical implementation examples

Each section builds upon the previous ones, so we recommend reading them in order if you're new to CanvaStack Tables.

---

## 🤝 Contributing

Found an issue or want to contribute to the documentation? Please see our [Contributing Guide](contributing.md).

## 📄 License

CanvaStack Table is open-sourced software licensed under the [MIT license](license.md).
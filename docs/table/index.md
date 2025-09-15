# CanvaStack Table Documentation

Welcome to the complete documentation for CanvaStack Table - a powerful, secure, and highly configurable DataTables implementation for Laravel applications.

## 🚀 Quick Navigation

### Getting Started
- **[Installation & Setup](installation.md)** - Complete installation guide with requirements and configuration
- **[Quick Start Guide](quick-start.md)** - Get up and running in minutes with practical examples
- **[Basic Usage](basic-usage.md)** - Fundamental patterns and common use cases
- **[Configuration](configuration.md)** - Comprehensive configuration options and settings

### Core Documentation
- **[Architecture Overview](architecture.md)** - System architecture and design patterns
- **[API Reference](api/objects.md)** - Complete method documentation and parameters
- **[Data Sources](data-sources.md)** - Working with Eloquent, Query Builder, and Raw SQL
- **[Column Management](columns.md)** - Column configuration, formatting, and display options

### Features & Functionality
- **[Actions & Buttons](features/actions.md)** - CRUD operations and custom action buttons
- **[Filtering & Search](features/filtering.md)** - Advanced filtering system with modal interface
- **[Sorting & Ordering](features/sorting.md)** - Column sorting and default ordering
- **[Export Functionality](features/export.md)** - Data export to Excel, CSV, PDF formats
- **[Fixed Columns](features/fixed-columns.md)** - Keep columns visible during horizontal scrolling
- **[Image Handling](features/images.md)** - Display and manage image columns
- **[Relationships](features/relationships.md)** - Working with Eloquent relationships

### Advanced Topics
- **[Security Features](advanced/security.md)** - Multi-layer security implementation
- **[Performance Optimization](advanced/performance.md)** - Query optimization and caching strategies
- **[Custom Middleware](advanced/middleware.md)** - Creating custom processing middleware
- **[Testing](advanced/testing.md)** - Unit and integration testing approaches
- **[Troubleshooting](advanced/troubleshooting.md)** - Common issues and solutions

### Method References
- **[GET Method](methods/get.md)** - Client-side processing implementation
- **[POST Method](methods/post.md)** - Server-side processing with AJAX
- **[AJAX Handling](methods/ajax.md)** - Custom AJAX endpoints and responses

### Extensions & Customization
- **[Available Traits](traits/overview.md)** - Built-in traits and their functionality
- **[Custom Extensions](traits/custom.md)** - Creating custom traits and extensions
- **[Plugin Development](plugins/development.md)** - Developing plugins for CanvaStack Table

### Examples & Tutorials
- **[Basic Examples](examples/basic.md)** - Simple table implementations
- **[Advanced Filtering](examples/filtering.md)** - Complex filtering scenarios
- **[Custom Actions](examples/actions.md)** - Custom action button examples
- **[Multiple Data Sources](examples/data-sources.md)** - Working with different data sources
- **[Real-world Examples](examples/real-world.md)** - Production use cases and implementations

---

## 📖 Documentation Structure

This documentation is organized into logical sections that build upon each other:

### 🎯 For Beginners
Start with these sections if you're new to CanvaStack Table:
1. [Installation & Setup](installation.md)
2. [Quick Start Guide](quick-start.md)
3. [Basic Usage](basic-usage.md)
4. [Basic Examples](examples/basic.md)

### 🔧 For Developers
Dive deeper into implementation details:
1. [Architecture Overview](architecture.md)
2. [API Reference](api/objects.md)
3. [Configuration](configuration.md)
4. [Advanced Examples](examples/real-world.md)

### 🛡️ For System Administrators
Focus on security and performance:
1. [Security Features](advanced/security.md)
2. [Performance Optimization](advanced/performance.md)
3. [Troubleshooting](advanced/troubleshooting.md)

### 🎨 For Customization
Extend and customize functionality:
1. [Available Traits](traits/overview.md)
2. [Custom Extensions](traits/custom.md)
3. [Plugin Development](plugins/development.md)

---

## 🔍 Quick Reference

### Essential Methods

```php
// Basic table setup
$this->table->lists('users', ['name', 'email']);

// With features
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->lists('users', ['name', 'email'], true);

// Server-side processing
$this->table->method('POST')
            ->searchable()
            ->sortable()
            ->lists('users', ['name', 'email']);

// With relationships
$this->table->relations($model, 'group', 'name')
            ->lists('users', ['name', 'group.name:Group']);

// With filtering
$this->table->filterGroups('status', 'selectbox', true)
            ->filterGroups('created_at', 'date', true)
            ->lists('users', ['name', 'status', 'created_at']);
```

### Common Patterns

| Pattern | Use Case | Documentation |
|---------|----------|---------------|
| `->searchable()` | Enable global search | [Filtering & Search](features/filtering.md) |
| `->sortable()` | Enable column sorting | [Sorting & Ordering](features/sorting.md) |
| `->clickable()` | Make rows clickable | [Basic Usage](basic-usage.md) |
| `->method('POST')` | Server-side processing | [POST Method](methods/post.md) |
| `->relations()` | Display related data | [Relationships](features/relationships.md) |
| `->filterGroups()` | Add column filters | [Filtering & Search](features/filtering.md) |
| `->setActions()` | Custom action buttons | [Actions & Buttons](features/actions.md) |
| `->setFieldAsImage()` | Display images | [Image Handling](features/images.md) |

---

## 🎯 Key Features

### 🔒 Security First
- **SQL Injection Prevention** - Parameter binding and query validation
- **XSS Protection** - Output encoding and input sanitization
- **CSRF Protection** - Token validation for all POST requests
- **Access Control** - Integration with Laravel's authorization system
- **Security Monitoring** - Real-time threat detection and logging

### ⚡ High Performance
- **Server-Side Processing** - Handle millions of records efficiently
- **Query Optimization** - Intelligent JOIN and WHERE clause building
- **Caching Strategies** - Multiple caching layers for optimal performance
- **Memory Management** - Efficient memory usage for large datasets
- **Database Optimization** - Index-aware query building

### 🎨 Developer Experience
- **Fluent API** - Method chaining for readable code
- **Convention over Configuration** - Sensible defaults with customization options
- **Comprehensive Documentation** - Detailed guides and examples
- **Extensive Testing** - Unit and integration test coverage
- **Error Handling** - Clear error messages and debugging tools

### 🔧 Flexibility
- **Multiple Data Sources** - Eloquent Models, Query Builder, Raw SQL
- **Customizable UI** - Themes, styling, and layout options
- **Extensible Architecture** - Traits, plugins, and custom extensions
- **Framework Integration** - Deep Laravel integration with standalone options

---

## 🚦 Getting Started Checklist

### Prerequisites
- [ ] PHP 8.0 or higher
- [ ] Laravel 9.0 or higher
- [ ] MySQL 5.7+ or PostgreSQL 10+
- [ ] Basic understanding of Laravel and DataTables

### Installation Steps
- [ ] Install via Composer: `composer require canvastack/canvastack`
- [ ] Publish configuration: `php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"`
- [ ] Run migrations: `php artisan migrate`
- [ ] Publish assets: `php artisan vendor:publish --tag=canvastack-assets --force`

### First Implementation
- [ ] Create a controller extending CanvaStack Controller
- [ ] Set up basic table with `$this->table->lists()`
- [ ] Create corresponding view template
- [ ] Test basic functionality (search, sort, pagination)
- [ ] Add actions and filters as needed

### Next Steps
- [ ] Review [Security Features](advanced/security.md) for production deployment
- [ ] Optimize performance using [Performance Guide](advanced/performance.md)
- [ ] Explore [Advanced Examples](examples/real-world.md) for complex scenarios
- [ ] Set up monitoring and logging for production use

---

## 🤝 Community & Support

### Getting Help
- **Documentation** - Comprehensive guides and API reference
- **Examples** - Real-world implementation examples
- **Troubleshooting** - Common issues and solutions
- **GitHub Issues** - Bug reports and feature requests

### Contributing
- **Bug Reports** - Help improve the system by reporting issues
- **Feature Requests** - Suggest new functionality
- **Documentation** - Help improve and expand documentation
- **Code Contributions** - Submit pull requests for improvements

### Resources
- **GitHub Repository** - Source code and issue tracking
- **Changelog** - Version history and updates
- **Migration Guides** - Upgrading between versions
- **Best Practices** - Recommended implementation patterns

---

## 📄 License & Credits

CanvaStack Table is open-source software licensed under the [MIT License](license.md).

### Built With
- **Laravel** - PHP framework foundation
- **DataTables** - JavaScript table enhancement
- **jQuery** - JavaScript library
- **Bootstrap** - CSS framework (optional)
- **Font Awesome** - Icon library (optional)

### Acknowledgments
- Laravel community for framework excellence
- DataTables team for powerful table functionality
- Contributors and users for feedback and improvements

---

## 🔄 Version Information

**Current Version**: 2.0.0  
**Laravel Compatibility**: 9.x, 10.x, 11.x  
**PHP Compatibility**: 8.0+  
**Last Updated**: December 2024

### Recent Updates
- Enhanced security features with multi-layer protection
- Improved performance with query optimization
- Extended filtering capabilities with modal interface
- Better developer experience with comprehensive documentation
- Advanced customization options with trait system

---

*This documentation is continuously updated. For the latest information, please check the [GitHub repository](https://github.com/canvastack/canvastack) or [official website](https://canvastack.com).*
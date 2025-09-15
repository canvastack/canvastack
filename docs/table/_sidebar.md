# CanvaStack Table Documentation

## 🚀 Getting Started
- [📖 Overview](README.md)
- [⚡ Installation & Setup](installation.md)
- [🎯 Quick Start Guide](quick-start.md)
- [📚 Basic Usage](basic-usage.md)
- [⚙️ Configuration](configuration.md)

## 🏗️ Core Concepts
- [🏛️ Architecture Overview](architecture.md)
- [💾 Data Sources](data-sources.md)
- [📊 Column Management](columns.md)
- [🖥️ Server-Side Processing](server-side.md)

## 📋 API Reference
- [🎛️ Objects Class](api/objects.md)
- [🏗️ Builder Class](api/builder.md)
- [⚡ Datatables Class](api/datatables.md)
- [🔍 Search & Filtering](api/search.md)

## ✨ Features
- [🎬 Actions & Buttons](features/actions.md)
- [🔍 Filtering & Search](features/filtering.md)
- [📈 Sorting & Ordering](features/sorting.md)
- [📤 Export Functionality](features/export.md)
- [📌 Fixed Columns](features/fixed-columns.md)
- [🖼️ Image Handling](features/images.md)
- [🔗 Relationships](features/relationships.md)

## 🚀 Advanced Topics
- [🛡️ Security Features](advanced/security.md)
- [⚡ Performance Optimization](advanced/performance.md)
- [🔧 Custom Middleware](advanced/middleware.md)
- [🧪 Testing](advanced/testing.md)
- [🔧 Troubleshooting](advanced/troubleshooting.md)

## 🌐 Method References
- [📥 GET Method](methods/get.md)
- [📤 POST Method](methods/post.md)
- [🔄 AJAX Handling](methods/ajax.md)

## 🧩 Extensions
- [🎭 Available Traits](traits/overview.md)
- [🔧 Custom Extensions](traits/custom.md)
- [🔌 Plugin Development](plugins/development.md)

## 💡 Examples & Tutorials
- [📝 Basic Examples](examples/basic.md)
- [🔍 Advanced Filtering](examples/filtering.md)
- [🎬 Custom Actions](examples/actions.md)
- [💾 Multiple Data Sources](examples/data-sources.md)
- [🌍 Real-world Examples](examples/real-world.md)

---

## 🔗 Quick Links

### 🎯 Most Popular
- [Quick Start Guide](quick-start.md)
- [Basic Examples](examples/basic.md)
- [API Reference](api/objects.md)
- [Security Features](advanced/security.md)

### 🆘 Need Help?
- [Troubleshooting](advanced/troubleshooting.md)
- [Configuration](configuration.md)
- [Performance Tips](advanced/performance.md)

### 🔧 For Developers
- [Architecture](architecture.md)
- [Custom Extensions](traits/custom.md)
- [Testing Guide](advanced/testing.md)

---

## 📊 Quick Reference

### Essential Methods
```php
// Basic table
$this->table->lists('users', ['name', 'email']);

// With features
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->lists('users', ['name', 'email'], true);

// Server-side
$this->table->method('POST')
            ->lists('users', ['name', 'email']);
```

### Common Patterns
- `->searchable()` - Enable search
- `->sortable()` - Enable sorting  
- `->clickable()` - Clickable rows
- `->method('POST')` - Server-side
- `->relations()` - Relationships
- `->filterGroups()` - Add filters
- `->setActions()` - Custom actions

---

*📚 [Complete Documentation Index](index.md)*
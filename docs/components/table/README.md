# Table Component

Modern, secure, and high-performance data table builder for CanvaStack.

**Version**: 2.0.0  
**Status**: ✅ Production Ready  
**Last Updated**: 2026-02-26

---

## 🎯 Overview

The Table Component addresses all critical issues from the legacy `Datatables.php` implementation while maintaining 100% backward compatibility.

### Key Features

- ✅ **Security**: Zero SQL injection vulnerabilities with Query Builder
- ✅ **Performance**: < 500ms load time for 1K rows, < 128MB memory for 10K rows
- ✅ **N+1 Prevention**: Automatic eager loading support
- ✅ **Caching**: Redis-based query result caching
- ✅ **Chunk Processing**: Memory-efficient handling of large datasets
- ✅ **Dual Rendering**: Admin and Public frontend support
- ✅ **Backward Compatible**: Legacy API still works perfectly
- ✅ **Well Tested**: 80%+ test coverage with property-based tests

---

## 🚀 Quick Start

### Basic Usage

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

$table = app(TableBuilder::class);

$html = $table
    ->model(User::class)
    ->setFields(['id', 'name', 'email', 'created_at'])
    ->render();

echo $html;
```

### With Custom Labels

```php
$html = $table
    ->model(User::class)
    ->setFields([
        'id' => 'User ID',
        'name' => 'Full Name',
        'email' => 'Email Address',
        'created_at' => 'Registration Date'
    ])
    ->orderby('created_at', 'desc')
    ->render();
```

### With Relationships (Eager Loading)

```php
use App\Models\Post;

$html = $table
    ->model(Post::class)
    ->setFields(['id', 'title', 'user_id', 'created_at'])
    ->relations(new Post(), 'user', 'name', [], 'Author')
    ->render();
// Only 2 queries: 1 for posts + 1 for all users (no N+1!)
```

### With Caching

```php
$html = $table
    ->model(User::class)
    ->setFields(['id', 'name', 'email'])
    ->cache(300)  // Cache for 5 minutes
    ->render();
```

---

## 📚 Documentation

### Essential Guides

- **[API Reference](api-reference.md)** - Complete API documentation (60+ methods)
- **[Examples](examples.md)** - Real-world usage examples
- **[Migration Guide](migration.md)** - Migrate from legacy API
- **[Performance Tuning](performance.md)** - Optimization strategies
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions
- **[HTTP Methods](http-methods.md)** - HTTP method configuration

### Quick Links

- [Installation](../../getting-started/installation.md)
- [Quick Start Tutorial](../../getting-started/quick-start.md)
- [Architecture Overview](../../architecture/overview.md)
- [Security Features](../../features/security.md)

---

## 🏗️ Architecture

### Components

```
Components/Table/
├── TableBuilder.php          # Main table builder class
├── Query/
│   ├── QueryOptimizer.php    # Query optimization strategies
│   └── FilterBuilder.php     # Secure filter application
└── Renderers/
    ├── RendererInterface.php # Renderer contract
    ├── AdminRenderer.php     # Admin panel rendering (Bootstrap)
    └── PublicRenderer.php    # Public frontend rendering (Tailwind)
```

### Design Patterns

- **Strategy Pattern**: Admin/Public rendering strategies
- **Builder Pattern**: Fluent interface for table configuration
- **Repository Pattern**: Data access abstraction
- **Dependency Injection**: Loose coupling between components

---

## ⚡ Performance

### Benchmarks

| Metric | Legacy | CanvaStack | Improvement |
|--------|--------|------------|-------------|
| Load time (1K rows) | 2000ms | < 500ms | 75% faster |
| Memory (10K rows) | 256MB | < 128MB | 50% less |
| Query count | 1 + N | ≤ 5 | N+1 fixed |
| Cache hit ratio | 0% | > 80% | New feature |

### Performance Features

- **Eager Loading**: Automatic relationship loading prevents N+1 queries
- **Query Caching**: Redis-based caching for repeated queries
- **Chunk Processing**: Memory-efficient processing of large datasets
- **Select Optimization**: Load only required columns
- **Server-Side Processing**: AJAX-based pagination for large datasets

---

## 🔒 Security

### Built-in Protection

- **SQL Injection Prevention**: All queries use Query Builder with parameter binding
- **XSS Prevention**: All output is HTML-escaped automatically
- **Column Validation**: All column names validated against schema
- **URL Validation**: Action URLs validated to prevent malicious links
- **Attribute Sanitization**: HTML attributes sanitized to prevent XSS

### Security Features

```php
// ✅ Safe - uses parameter binding
$table->where('status', '=', $userInput);

// ✅ Safe - validates SQL
$table->query('SELECT * FROM users WHERE active = 1');

// ❌ Rejected - dangerous SQL
$table->query('DROP TABLE users'); // Throws exception

// ✅ Safe - escapes HTML
$table->render(); // All output escaped automatically
```

---

## 🎨 Features

### Column Configuration

- Custom labels and widths
- Hide/show columns
- Column alignment (left, center, right)
- Background colors
- Fixed columns (freeze left/right)
- Merge columns

### Sorting & Searching

- Sortable columns
- Searchable columns
- Default ordering
- Clickable rows

### Filtering

- WHERE conditions
- Filter groups
- Cascading filters
- Date range filters

### Advanced Features

- **Conditional Formatting**: Style cells/rows based on values
- **Formula Columns**: Calculated columns (e.g., price * quantity)
- **Data Formatting**: Numbers, currency, dates, percentages
- **Relational Data**: Display data from related models
- **Custom Actions**: View, edit, delete, custom buttons

### Display Options

- Pagination
- Row numbering
- Server-side vs client-side processing
- Custom AJAX URLs
- HTTP method configuration (GET/POST)

---

## 📖 Usage Examples

### Example 1: Simple User Table

```php
$html = $table
    ->model(User::class)
    ->setFields(['id', 'name', 'email', 'created_at'])
    ->orderby('created_at', 'desc')
    ->render();
```

### Example 2: Table with Conditional Formatting

```php
$html = $table
    ->model(Order::class)
    ->setFields(['id', 'customer', 'total', 'status'])
    ->columnCondition(
        'status',
        'cell',
        '==',
        'completed',
        'css style',
        'background-color: #d4edda; color: #155724;'
    )
    ->format(['total'], 2, ',', 'currency')
    ->render();
```

### Example 3: Table with Formula Columns

```php
$html = $table
    ->model(Product::class)
    ->setFields(['id', 'name', 'price', 'quantity'])
    ->formula(
        'total',
        'Total Value',
        ['price', 'quantity'],
        'price * quantity',
        'quantity',
        true
    )
    ->format(['price', 'total'], 2, ',', 'currency')
    ->render();
```

### Example 4: Server-Side Processing

```php
$html = $table
    ->model(Transaction::class)
    ->setFields(['id', 'date', 'amount', 'status'])
    ->setServerSide(true)
    ->setHttpMethod('POST')
    ->displayRowsLimitOnLoad(50)
    ->cache(300)
    ->render();
```

---

## 🔄 Migration from Legacy

### Legacy API (Still Works!)

```php
// Old way - still works perfectly
$html = $table->lists(
    'users',
    ['id', 'name', 'email'],
    true,  // actions
    true,  // server-side
    true   // numbering
);
```

### Enhanced API (Recommended)

```php
// New way - more features, better performance
$html = $table
    ->model(User::class)
    ->setFields(['id', 'name', 'email'])
    ->setActions(true)
    ->setServerSide(true)
    ->cache(300)
    ->render();
```

**See [Migration Guide](migration.md) for complete details.**

---

## 🧪 Testing

The Table Component has comprehensive test coverage:

- **Unit Tests**: 100+ tests covering all methods
- **Integration Tests**: Real database and model tests
- **Property-Based Tests**: 21 properties verified
- **Performance Tests**: Load time and memory benchmarks
- **Security Tests**: SQL injection and XSS prevention

---

## 🆘 Troubleshooting

### Common Issues

**Table not rendering?**
- Check if model is set: `$table->model(User::class)`
- Verify columns exist in database
- See [Troubleshooting Guide](troubleshooting.md)

**Slow performance?**
- Enable caching: `$table->cache(300)`
- Use eager loading: `$table->relations(...)`
- Enable server-side processing: `$table->setServerSide(true)`
- See [Performance Tuning](performance.md)

**N+1 queries?**
- Use `relations()` method for automatic eager loading
- Check query count with `DB::enableQueryLog()`
- See [Performance Guide](performance.md#eager-loading)

---

## 📊 API Overview

### Core Methods

| Method | Purpose |
|--------|---------|
| `model()` | Set Eloquent model |
| `setFields()` | Set columns to display |
| `render()` | Render table HTML |

### Configuration Methods

| Method | Purpose |
|--------|---------|
| `orderby()` | Set default sorting |
| `sortable()` | Configure sortable columns |
| `searchable()` | Configure searchable columns |
| `where()` | Add WHERE conditions |
| `setActions()` | Configure action buttons |

### Advanced Methods

| Method | Purpose |
|--------|---------|
| `relations()` | Display related data |
| `columnCondition()` | Conditional formatting |
| `formula()` | Create calculated columns |
| `format()` | Format data (currency, dates, etc.) |
| `cache()` | Enable caching |

**See [API Reference](api-reference.md) for complete documentation of all 60+ methods.**

---

## 🎓 Next Steps

1. **Read the [API Reference](api-reference.md)** - Learn all available methods
2. **Explore [Examples](examples.md)** - See real-world usage
3. **Check [Performance Guide](performance.md)** - Optimize your tables
4. **Review [Security Features](../../features/security.md)** - Understand security

---

## 📝 Contributing

Found a bug or have a feature request? Please open an issue on GitHub.

---

**Version**: 2.0.0  
**Last Updated**: 2026-02-26  
**Status**: ✅ Production Ready


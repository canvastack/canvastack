# Database Indexing Guide

## Overview

This guide provides recommendations for database indexing to optimize query performance in CanvaStack applications.

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published

---

## 📚 Table of Contents

1. [Index Basics](#index-basics)
2. [When to Add Indexes](#when-to-add-indexes)
3. [Index Types](#index-types)
4. [Recommended Indexes](#recommended-indexes)
5. [Index Analysis Tools](#index-analysis-tools)
6. [Best Practices](#best-practices)
7. [Common Pitfalls](#common-pitfalls)

---

## Index Basics

### What is an Index?

A database index is a data structure that improves the speed of data retrieval operations on a database table at the cost of additional writes and storage space.

### How Indexes Work

```
Without Index:
SELECT * FROM users WHERE email = 'john@example.com';
→ Full table scan (O(n))

With Index on email:
SELECT * FROM users WHERE email = 'john@example.com';
→ Index lookup (O(log n))
```

### Performance Impact

| Operation | Without Index | With Index | Improvement |
|-----------|--------------|------------|-------------|
| SELECT (1M rows) | ~2000ms | ~10ms | 200x faster |
| INSERT | ~5ms | ~8ms | 1.6x slower |
| UPDATE | ~10ms | ~15ms | 1.5x slower |
| DELETE | ~10ms | ~15ms | 1.5x slower |

---

## When to Add Indexes

### ✅ Add Indexes For

1. **Primary Keys** (automatic in Laravel)
   ```php
   $table->id(); // Automatically indexed
   ```

2. **Foreign Keys** (for JOIN operations)
   ```php
   $table->foreignId('user_id')->constrained();
   // Automatically creates index
   ```

3. **WHERE Clauses** (frequently filtered columns)
   ```php
   // Query
   User::where('email', 'john@example.com')->first();
   
   // Migration
   $table->string('email')->index();
   ```

4. **ORDER BY Clauses** (frequently sorted columns)
   ```php
   // Query
   User::orderBy('created_at', 'desc')->get();
   
   // Migration
   $table->timestamp('created_at')->index();
   ```

5. **UNIQUE Constraints** (prevent duplicates)
   ```php
   $table->string('email')->unique();
   ```

6. **Composite Searches** (multiple column filters)
   ```php
   // Query
   User::where('status', 'active')
       ->where('role', 'admin')
       ->get();
   
   // Migration
   $table->index(['status', 'role']);
   ```

### ❌ Don't Add Indexes For

1. **Small Tables** (< 1000 rows)
2. **Columns with Low Cardinality** (few unique values)
   - Example: `is_active` (only true/false)
3. **Frequently Updated Columns** (high write overhead)
4. **Large Text Columns** (use full-text search instead)
5. **Columns Never Used in WHERE/ORDER BY**

---

## Index Types

### 1. Single Column Index

**Use Case**: Filter or sort by one column

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
});

// Query
User::where('email', 'john@example.com')->first();
```

### 2. Composite Index (Multi-Column)

**Use Case**: Filter by multiple columns together

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->index(['status', 'role', 'created_at']);
});

// Query
User::where('status', 'active')
    ->where('role', 'admin')
    ->orderBy('created_at', 'desc')
    ->get();
```

**Column Order Matters!**
```php
// ✅ GOOD - Uses index
$table->index(['status', 'role']);
User::where('status', 'active')->where('role', 'admin')->get();

// ✅ GOOD - Uses index (leftmost prefix)
User::where('status', 'active')->get();

// ❌ BAD - Doesn't use index
User::where('role', 'admin')->get();
```

### 3. Unique Index

**Use Case**: Enforce uniqueness and fast lookups

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->unique('email');
});

// Query
User::where('email', 'john@example.com')->first();
```

### 4. Full-Text Index

**Use Case**: Search in large text columns

```php
// Migration
Schema::table('posts', function (Blueprint $table) {
    $table->fullText(['title', 'content']);
});

// Query
Post::whereFullText(['title', 'content'], 'search term')->get();
```

### 5. Spatial Index

**Use Case**: Geographic data queries

```php
// Migration
Schema::table('locations', function (Blueprint $table) {
    $table->point('coordinates')->spatialIndex();
});
```

---

## Recommended Indexes

### Users Table

```php
Schema::create('users', function (Blueprint $table) {
    $table->id(); // Primary key (auto-indexed)
    $table->string('name');
    $table->string('email')->unique(); // Unique index
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->string('role')->default('user');
    $table->timestamps();
    
    // Composite index for common queries
    $table->index(['is_active', 'role', 'created_at']);
});
```

### Posts Table

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained(); // Foreign key (auto-indexed)
    $table->string('title');
    $table->text('content');
    $table->string('status')->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    
    // Indexes for common queries
    $table->index(['status', 'published_at']);
    $table->index('user_id'); // Already indexed by foreign key
    $table->fullText(['title', 'content']); // Full-text search
});
```

### Orders Table

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('order_number')->unique();
    $table->string('status');
    $table->decimal('total', 10, 2);
    $table->timestamp('ordered_at');
    $table->timestamps();
    
    // Composite indexes for common queries
    $table->index(['user_id', 'status', 'ordered_at']);
    $table->index(['status', 'ordered_at']);
});
```

---

## Index Analysis Tools

### 1. QueryOptimizer::suggestIndexes()

```php
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;

$optimizer = app(QueryOptimizer::class);
$query = User::where('email', 'john@example.com')
    ->orderBy('created_at', 'desc');

$suggestions = $optimizer->suggestIndexes($query);
// [
//     ['table' => 'users', 'column' => 'email', 'reason' => 'Used in WHERE clause'],
//     ['table' => 'users', 'column' => 'created_at', 'reason' => 'Used in ORDER BY clause'],
// ]
```

### 2. EXPLAIN Query

```php
// Laravel
$query = User::where('email', 'john@example.com');
dd($query->explain());

// Raw SQL
DB::select('EXPLAIN SELECT * FROM users WHERE email = ?', ['john@example.com']);
```

### 3. Slow Query Log

Enable in MySQL:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Log queries > 1 second
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';
```

### 4. Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

Shows query count, execution time, and duplicate queries.

---

## Best Practices

### 1. Index Naming Convention

```php
// ✅ GOOD - Descriptive names
$table->index('email', 'users_email_index');
$table->index(['status', 'role'], 'users_status_role_index');

// ❌ BAD - Auto-generated names
$table->index('email'); // Creates 'users_email_index' anyway
```

### 2. Composite Index Column Order

**Rule**: Most selective column first

```php
// ✅ GOOD - status has more unique values
$table->index(['status', 'is_active']);

// ❌ BAD - is_active only has 2 values
$table->index(['is_active', 'status']);
```

### 3. Covering Indexes

Include all columns needed in the query:

```php
// Query
User::select('id', 'name', 'email')
    ->where('status', 'active')
    ->get();

// ✅ GOOD - Covering index (no table lookup needed)
$table->index(['status', 'id', 'name', 'email']);
```

### 4. Index Maintenance

```php
// Analyze table statistics
DB::statement('ANALYZE TABLE users');

// Optimize table
DB::statement('OPTIMIZE TABLE users');

// Check index usage
DB::select('SHOW INDEX FROM users');
```

### 5. Monitor Index Usage

```sql
-- MySQL
SELECT * FROM information_schema.STATISTICS 
WHERE table_schema = 'your_database' 
AND table_name = 'users';

-- Check unused indexes
SELECT * FROM sys.schema_unused_indexes;
```

---

## Common Pitfalls

### 1. Over-Indexing

**Problem**: Too many indexes slow down writes

```php
// ❌ BAD - 10 indexes on one table
$table->index('name');
$table->index('email');
$table->index('status');
$table->index('role');
$table->index('created_at');
$table->index(['name', 'email']);
$table->index(['status', 'role']);
$table->index(['status', 'created_at']);
$table->index(['role', 'created_at']);
$table->index(['status', 'role', 'created_at']);

// ✅ GOOD - 3-4 strategic indexes
$table->unique('email');
$table->index(['status', 'role', 'created_at']);
$table->index('name'); // Only if frequently searched
```

### 2. Wrong Column Order in Composite Index

```php
// Query
User::where('role', 'admin')
    ->where('status', 'active')
    ->get();

// ❌ BAD - Wrong order
$table->index(['status', 'role']);

// ✅ GOOD - Matches query order
$table->index(['role', 'status']);
```

### 3. Indexing Low Cardinality Columns

```php
// ❌ BAD - Only 2 unique values
$table->index('is_active'); // true/false

// ✅ GOOD - Use composite index
$table->index(['is_active', 'role', 'created_at']);
```

### 4. Not Using Indexes in Queries

```php
// ❌ BAD - Function on indexed column
User::whereRaw('LOWER(email) = ?', ['john@example.com'])->first();

// ✅ GOOD - Direct comparison
User::where('email', 'john@example.com')->first();
```

### 5. Forgetting Foreign Key Indexes

```php
// ❌ BAD - No index on foreign key
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->foreign('user_id')->references('id')->on('users');
});

// ✅ GOOD - Use foreignId (auto-indexes)
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
});
```

---

## Performance Testing

### Before Adding Index

```php
// Test query performance
$start = microtime(true);
$users = User::where('email', 'john@example.com')->get();
$time = microtime(true) - $start;
echo "Query time: {$time}s\n";

// Check query plan
dd(User::where('email', 'john@example.com')->explain());
```

### After Adding Index

```php
// Add index
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
});

// Re-test
$start = microtime(true);
$users = User::where('email', 'john@example.com')->get();
$time = microtime(true) - $start;
echo "Query time: {$time}s\n"; // Should be much faster
```

---

## Migration Examples

### Adding Indexes to Existing Tables

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Single column index
            $table->index('email');
            
            // Composite index
            $table->index(['status', 'role', 'created_at']);
            
            // Unique index
            $table->unique('username');
            
            // Full-text index
            $table->fullText(['bio', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status', 'role', 'created_at']);
            $table->dropUnique(['username']);
            $table->dropFullText(['bio', 'description']);
        });
    }
};
```

---

## Resources

### Documentation
- [Laravel Migrations - Indexes](https://laravel.com/docs/migrations#indexes)
- [MySQL Index Documentation](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [PostgreSQL Index Documentation](https://www.postgresql.org/docs/current/indexes.html)

### Tools
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [Laravel Telescope](https://laravel.com/docs/telescope)
- [MySQL Workbench](https://www.mysql.com/products/workbench/)

### Books
- "High Performance MySQL" by Baron Schwartz
- "Database Internals" by Alex Petrov

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published  
**Maintainer**: CanvaStack Team

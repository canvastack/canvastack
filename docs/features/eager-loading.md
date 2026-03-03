# Eager Loading Guide

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [The N+1 Problem](#the-n1-problem)
3. [Basic Eager Loading](#basic-eager-loading)
4. [Nested Eager Loading](#nested-eager-loading)
5. [Constrained Eager Loading](#constrained-eager-loading)
6. [Conditional Eager Loading](#conditional-eager-loading)
7. [Lazy Eager Loading](#lazy-eager-loading)
8. [Table Component Integration](#table-component-integration)
9. [Performance Impact](#performance-impact)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

---

## Overview

Eager loading is a technique to load related models in advance, preventing the N+1 query problem and dramatically improving performance.

### Benefits

- **Reduced Queries**: Load all related data in 2-3 queries instead of N+1
- **Better Performance**: 80-90% faster for data with relationships
- **Lower Memory**: More efficient memory usage
- **Predictable**: Consistent query count regardless of dataset size

### Performance Comparison

| Operation | Without Eager Loading | With Eager Loading | Improvement |
|-----------|----------------------|-------------------|-------------|
| 100 users with posts | 101 queries (~500ms) | 2 queries (~50ms) | 90% faster |
| 1000 users with posts | 1001 queries (~5000ms) | 2 queries (~200ms) | 96% faster |
| Complex relationships | 1000+ queries | 5-10 queries | 99% faster |

---

## The N+1 Problem

### What is N+1?

The N+1 problem occurs when you execute 1 query to fetch N records, then N additional queries to fetch related data for each record.

### Example Problem

```php
// ❌ BAD - N+1 queries
$users = User::all(); // 1 query

foreach ($users as $user) {
    echo $user->posts->count(); // N queries (one per user)
}

// Total: 1 + N queries
// For 100 users: 101 queries!
```

### SQL Queries Generated

```sql
-- Query 1: Fetch all users
SELECT * FROM users;

-- Query 2-101: Fetch posts for each user
SELECT * FROM posts WHERE user_id = 1;
SELECT * FROM posts WHERE user_id = 2;
SELECT * FROM posts WHERE user_id = 3;
-- ... 97 more queries
```

### Solution with Eager Loading

```php
// ✅ GOOD - 2 queries only
$users = User::with('posts')->get(); // 2 queries

foreach ($users as $user) {
    echo $user->posts->count(); // No additional queries
}

// Total: 2 queries
// For 100 users: Still only 2 queries!
```

### SQL Queries Generated

```sql
-- Query 1: Fetch all users
SELECT * FROM users;

-- Query 2: Fetch all posts for these users
SELECT * FROM posts WHERE user_id IN (1, 2, 3, ..., 100);
```

---

## Basic Eager Loading

### Single Relationship

```php
// Eager load single relationship
$users = User::with('posts')->get();

// Access relationship without additional queries
foreach ($users as $user) {
    foreach ($user->posts as $post) {
        echo $post->title;
    }
}
```

### Multiple Relationships

```php
// Eager load multiple relationships
$users = User::with(['posts', 'comments', 'profile'])->get();

// All relationships loaded, no additional queries
foreach ($users as $user) {
    echo $user->profile->bio;
    echo $user->posts->count();
    echo $user->comments->count();
}
```

### Array Syntax

```php
// Using array syntax
$users = User::with([
    'posts',
    'comments',
    'profile',
])->get();
```

---

## Nested Eager Loading

### Two Levels Deep

```php
// Load posts and their comments
$users = User::with('posts.comments')->get();

foreach ($users as $user) {
    foreach ($user->posts as $post) {
        foreach ($post->comments as $comment) {
            echo $comment->body;
        }
    }
}
```

### Three Levels Deep

```php
// Load posts, comments, and comment authors
$users = User::with('posts.comments.author')->get();

foreach ($users as $user) {
    foreach ($user->posts as $post) {
        foreach ($post->comments as $comment) {
            echo $comment->author->name;
        }
    }
}
```

### Multiple Nested Relationships

```php
// Load multiple nested relationships
$users = User::with([
    'posts.comments.author',
    'posts.tags',
    'profile.avatar',
])->get();
```

---

## Constrained Eager Loading

### Filter Related Records

```php
// Load only published posts
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published');
}])->get();

// Load only recent comments
$users = User::with(['comments' => function ($query) {
    $query->where('created_at', '>=', now()->subDays(7));
}])->get();
```

### Order Related Records

```php
// Load posts ordered by date
$users = User::with(['posts' => function ($query) {
    $query->orderBy('created_at', 'desc');
}])->get();

// Load top 5 posts
$users = User::with(['posts' => function ($query) {
    $query->orderBy('views', 'desc')->limit(5);
}])->get();
```

### Select Specific Columns

```php
// Load only specific columns from related records
$users = User::with(['posts' => function ($query) {
    $query->select('id', 'user_id', 'title', 'created_at');
}])->get();

// Note: Always include the foreign key (user_id)
```

### Complex Constraints

```php
// Combine multiple constraints
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published')
        ->where('views', '>', 100)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->select('id', 'user_id', 'title', 'views');
}])->get();
```

---

## Conditional Eager Loading

### Load Based on Condition

```php
// Load posts only if requested
$users = User::when(request('include_posts'), function ($query) {
    $query->with('posts');
})->get();

// Load different relationships based on user role
$users = User::when(auth()->user()->isAdmin(), function ($query) {
    $query->with(['posts', 'comments', 'profile']);
}, function ($query) {
    $query->with('posts');
})->get();
```

### Load Based on Request Parameter

```php
// Load relationships based on request
$includes = explode(',', request('include', ''));

$query = User::query();

if (in_array('posts', $includes)) {
    $query->with('posts');
}

if (in_array('comments', $includes)) {
    $query->with('comments');
}

$users = $query->get();
```

### Dynamic Eager Loading

```php
// Build eager loading dynamically
$with = [];

if (request('include_posts')) {
    $with[] = 'posts';
}

if (request('include_comments')) {
    $with[] = 'comments';
}

if (request('include_profile')) {
    $with[] = 'profile';
}

$users = User::with($with)->get();
```

---

## Lazy Eager Loading

### Load After Initial Query

```php
// Initial query without relationships
$users = User::all();

// Later, load relationships if needed
if ($needPosts) {
    $users->load('posts');
}

if ($needComments) {
    $users->load('comments');
}
```

### Load Multiple Relationships

```php
// Load multiple relationships at once
$users = User::all();

$users->load(['posts', 'comments', 'profile']);
```

### Constrained Lazy Loading

```php
// Load with constraints
$users = User::all();

$users->load(['posts' => function ($query) {
    $query->where('status', 'published');
}]);
```

### Load Missing Relationships

```php
// Load only if not already loaded
$users = User::all();

$users->loadMissing('posts');
$users->loadMissing(['comments', 'profile']);
```

---

## Table Component Integration

### Basic Eager Loading

```php
use Canvastack\Components\Table\TableBuilder;

$table = new TableBuilder();

// Enable eager loading
$table->eager(['posts', 'comments']);

// Display related data
$table->column('name', 'Name');
$table->column('posts_count', 'Posts')
    ->format(function ($value, $row) {
        return $row->posts->count();
    });

$table->runModel(User::class);
```

### Nested Eager Loading

```php
// Load nested relationships
$table->eager([
    'posts.comments',
    'posts.tags',
    'profile.avatar',
]);

$table->column('latest_post', 'Latest Post')
    ->format(function ($value, $row) {
        return $row->posts->first()?->title ?? 'No posts';
    });
```

### Constrained Eager Loading

```php
// Load with constraints
$table->eager([
    'posts' => function ($query) {
        $query->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit(5);
    }
]);

$table->column('published_posts', 'Published Posts')
    ->format(function ($value, $row) {
        return $row->posts->count();
    });
```

### Conditional Eager Loading

```php
// Load based on filter
$table->eager(function ($query) {
    if (request('show_posts')) {
        $query->with('posts');
    }
    
    if (request('show_comments')) {
        $query->with('comments');
    }
});
```

### Count Relationships

```php
// Use withCount for counting
$table->withCount(['posts', 'comments']);

$table->column('posts_count', 'Posts');
$table->column('comments_count', 'Comments');

// No need to load full relationships
```

---

## Performance Impact

### Benchmark: 100 Users with Posts

#### Without Eager Loading

```php
// ❌ 101 queries, ~500ms
$users = User::all();

foreach ($users as $user) {
    echo $user->posts->count();
}
```

**Queries:**
```
SELECT * FROM users; (1 query)
SELECT * FROM posts WHERE user_id = 1; (100 queries)
Total: 101 queries, ~500ms
```

#### With Eager Loading

```php
// ✅ 2 queries, ~50ms
$users = User::with('posts')->get();

foreach ($users as $user) {
    echo $user->posts->count();
}
```

**Queries:**
```
SELECT * FROM users; (1 query)
SELECT * FROM posts WHERE user_id IN (1,2,3,...,100); (1 query)
Total: 2 queries, ~50ms
```

**Improvement: 90% faster, 99% fewer queries**

### Benchmark: Complex Relationships

#### Without Eager Loading

```php
// ❌ 1000+ queries, ~10000ms
$users = User::all();

foreach ($users as $user) {
    foreach ($user->posts as $post) {
        foreach ($post->comments as $comment) {
            echo $comment->author->name;
        }
    }
}
```

#### With Eager Loading

```php
// ✅ 4 queries, ~200ms
$users = User::with('posts.comments.author')->get();

foreach ($users as $user) {
    foreach ($user->posts as $post) {
        foreach ($post->comments as $comment) {
            echo $comment->author->name;
        }
    }
}
```

**Improvement: 98% faster, 99.6% fewer queries**

---

## Best Practices

### 1. Always Use Eager Loading for Relationships

```php
// ✅ GOOD
$users = User::with('posts')->get();

// ❌ BAD
$users = User::all();
foreach ($users as $user) {
    $user->posts; // N+1 query
}
```

### 2. Load Only What You Need

```php
// ✅ GOOD - Load only needed relationships
$users = User::with('posts')->get();

// ❌ BAD - Load unnecessary relationships
$users = User::with(['posts', 'comments', 'profile', 'settings', 'notifications'])->get();
```

### 3. Use Constraints to Reduce Data

```php
// ✅ GOOD - Load only published posts
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published')->limit(10);
}])->get();

// ❌ BAD - Load all posts
$users = User::with('posts')->get();
```

### 4. Use withCount for Counting

```php
// ✅ GOOD - Use withCount
$users = User::withCount('posts')->get();
echo $user->posts_count;

// ❌ BAD - Load full relationship just to count
$users = User::with('posts')->get();
echo $user->posts->count();
```

### 5. Select Specific Columns

```php
// ✅ GOOD - Select only needed columns
$users = User::with(['posts' => function ($query) {
    $query->select('id', 'user_id', 'title');
}])->get();

// ❌ BAD - Select all columns
$users = User::with('posts')->get();
```

### 6. Use Lazy Eager Loading When Appropriate

```php
// ✅ GOOD - Load conditionally
$users = User::all();

if ($needPosts) {
    $users->load('posts');
}

// ❌ BAD - Always load
$users = User::with('posts')->get();
```

### 7. Monitor Query Count

```php
// Enable query logging
DB::enableQueryLog();

// Run your code
$users = User::with('posts')->get();

// Check query count
$queries = DB::getQueryLog();
echo "Total queries: " . count($queries);
```

---

## Troubleshooting

### Still Getting N+1 Queries

**Problem**: Eager loading doesn't seem to work

**Solution**: Check relationship is defined correctly

```php
// In User model
public function posts()
{
    return $this->hasMany(Post::class);
}

// Eager load
$users = User::with('posts')->get();
```

### Relationship Not Loaded

**Problem**: Relationship returns null or empty

**Solution**: Check foreign key and relationship type

```php
// Check foreign key matches
public function posts()
{
    return $this->hasMany(Post::class, 'user_id', 'id');
}
```

### Memory Issues with Large Datasets

**Problem**: Out of memory when eager loading

**Solution**: Use chunking

```php
// Use chunking for large datasets
User::with('posts')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### Slow Queries with Eager Loading

**Problem**: Queries still slow with eager loading

**Solution**: Add database indexes

```php
// Migration
Schema::table('posts', function (Blueprint $table) {
    $table->index('user_id');
});
```

---

## See Also

- [Performance Optimization](performance.md)
- [Table Component Performance](../components/table/performance.md)
- [Query Optimization](../guides/best-practices.md#query-optimization)
- [Caching System](caching.md)

---

**Next**: [Best Practices Guide](../guides/best-practices.md)

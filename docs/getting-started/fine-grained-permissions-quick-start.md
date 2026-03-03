# Fine-Grained Permissions System - Quick Start Guide

## 🚀 Get Started in 5 Minutes

This guide helps you quickly understand and start implementing the Fine-Grained Permissions System.

---

## 📋 Prerequisites Checklist

Before you begin, ensure these are completed:

- [x] Phase 3 RBAC Enhancement is done
- [x] FormBuilder component is implemented
- [x] TableBuilder component is implemented
- [x] Theme Engine is operational
- [x] i18n System is operational
- [x] Redis is configured and running
- [x] Development environment is set up

---

## 🎯 What You're Building

You're adding 4 types of fine-grained permissions:

1. **Row-Level** - "User can only edit their own posts"
2. **Column-Level** - "Editor cannot edit status field"
3. **JSON Attribute** - "Editor cannot edit metadata.featured"
4. **Conditional** - "User can only edit draft posts"

---

## 📚 Essential Reading (15 minutes)

Read these in order:

1. **[summary.md](./summary.md)** (5 min) - Quick overview
2. **[README.md](./README.md)** (10 min) - Architecture and features

---

## 🏗️ Implementation Overview

### 6-Week Timeline

```
Week 1: Database & Models
    ↓
Week 2-3: Core Implementation
    ↓
Week 4: Gate Integration
    ↓
Week 5: Component Integration
    ↓
Week 6: Testing & Documentation
```

### 5 Phases, 65 Tasks

- **Phase 1**: 13 tasks (Database & Models)
- **Phase 2**: 15 tasks (Core Implementation)
- **Phase 3**: 12 tasks (Gate Integration)
- **Phase 4**: 12 tasks (Component Integration)
- **Phase 5**: 13 tasks (Testing & Documentation)

---

## 🎬 Start Here: Phase 1 (Week 1)

### Day 1: Database Migrations

**Task 1.1.1**: Create `permission_rules` migration

```bash
php artisan make:migration create_permission_rules_table
```

```php
Schema::create('permission_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->enum('rule_type', ['row', 'column', 'json_attribute', 'conditional']);
    $table->json('rule_config');
    $table->integer('priority')->default(0);
    $table->timestamps();
    
    $table->index(['permission_id', 'rule_type']);
});
```

**Task 1.1.2**: Create `user_permission_overrides` migration

```bash
php artisan make:migration create_user_permission_overrides_table
```

```php
Schema::create('user_permission_overrides', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id')->nullable();
    $table->string('field_name')->nullable();
    $table->json('rule_config')->nullable();
    $table->boolean('allowed')->default(true);
    $table->timestamps();
    
    $table->index(['user_id', 'permission_id', 'model_type', 'model_id']);
});
```

**Run migrations:**

```bash
php artisan migrate
```

### Day 2: Create Models

**Task 1.2.1**: Create `PermissionRule` model

```bash
php artisan make:model PermissionRule
```

```php
namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRule extends Model
{
    protected $fillable = [
        'permission_id',
        'rule_type',
        'rule_config',
        'priority',
    ];
    
    protected $casts = [
        'rule_config' => 'array',
        'priority' => 'integer',
    ];
    
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
```

**Task 1.2.2**: Create `UserPermissionOverride` model

```bash
php artisan make:model UserPermissionOverride
```

```php
namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermissionOverride extends Model
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'model_type',
        'model_id',
        'field_name',
        'rule_config',
        'allowed',
    ];
    
    protected $casts = [
        'rule_config' => 'array',
        'allowed' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
```

### Day 3-5: Model Methods & Tests

Follow [tasks.md](./tasks.md) for detailed steps:
- Task 1.3.1: Implement `evaluate()` method
- Task 1.3.2: Implement evaluation methods
- Task 1.3.3: Write unit tests for PermissionRule
- Task 1.3.4: Write unit tests for UserPermissionOverride

---

## 📖 Detailed Implementation Guide

For complete implementation details, follow these documents in order:

### Week 1: Database & Models
- **[tasks.md - Phase 1](./tasks.md#phase-1-database-schema--models-week-1)**
- 13 tasks with detailed instructions
- Estimated: 40 hours

### Week 2-3: Core Implementation
- **[tasks.md - Phase 2](./tasks.md#phase-2-core-implementation-weeks-2-3)**
- 15 tasks including PermissionRuleManager
- Estimated: 80 hours

### Week 4: Gate Integration
- **[tasks.md - Phase 3](./tasks.md#phase-3-gate-integration-week-4)**
- 12 tasks including Blade directives
- Estimated: 40 hours

### Week 5: Component Integration
- **[tasks.md - Phase 4](./tasks.md#phase-4-component-integration-week-5)**
- 12 tasks including FormBuilder & TableBuilder
- Estimated: 40 hours

### Week 6: Testing & Documentation
- **[tasks.md - Phase 5](./tasks.md#phase-5-testing--documentation-week-6)**
- 13 tasks including comprehensive testing
- Estimated: 60 hours

---

## 🎯 Key Concepts to Understand

### 1. Permission Rules

Rules are stored in `permission_rules` table with 4 types:

```php
// Row-level: Control access to specific rows
'rule_type' => 'row',
'rule_config' => ['user_id' => '{{auth.id}}']

// Column-level: Control access to specific fields
'rule_type' => 'column',
'rule_config' => [
    'allowed' => ['title', 'content'],
    'denied' => ['status', 'featured']
]

// JSON attribute: Control access to nested JSON fields
'rule_type' => 'json_attribute',
'rule_config' => [
    'column' => 'metadata',
    'allowed_paths' => ['seo.*'],
    'denied_paths' => ['featured']
]

// Conditional: Rule-based access
'rule_type' => 'conditional',
'rule_config' => ['condition' => "status === 'draft'"]
```

### 2. Template Variables

Used in row-level rules for dynamic values:

```php
'{{auth.id}}'          // Current user ID
'{{auth.role}}'        // Current user role
'{{auth.department}}'  // Current user department
```

### 3. Caching Strategy

All evaluations are cached:

```php
// Cache key format
"canvastack:rbac:rules:can_access_row:{userId}:{permission}:{modelId}"

// Cache TTL
Row-level: 3600 seconds
Column-level: 3600 seconds
JSON attribute: 3600 seconds
Conditional: 1800 seconds
```

### 4. Evaluation Flow

```
1. Check basic permission (e.g., 'posts.edit')
   ↓ If denied, return false
2. Check user overrides
   ↓ If override exists, use it
3. Check fine-grained rules
   ↓ Evaluate all applicable rules
4. Return result (cached)
```

---

## 🧪 Testing Strategy

### Unit Tests (80%+ coverage required)

```bash
# Test models
php artisan test --filter=PermissionRuleTest
php artisan test --filter=UserPermissionOverrideTest

# Test manager
php artisan test --filter=PermissionRuleManagerTest

# Test Gate integration
php artisan test --filter=GateFineGrainedTest
```

### Feature Tests

```bash
# Test FormBuilder integration
php artisan test --filter=FormBuilderPermissionTest

# Test TableBuilder integration
php artisan test --filter=TableBuilderPermissionTest

# Test Blade directives
php artisan test --filter=BladeDirectivePermissionTest
```

### Performance Tests

```bash
# Test response times
php artisan test --filter=PermissionPerformanceTest

# Test cache hit rates
php artisan test --filter=PermissionCacheTest
```

---

## 📊 Progress Tracking

### Daily Checklist

- [ ] Complete assigned tasks from [tasks.md](./tasks.md)
- [ ] Write unit tests for new code
- [ ] Run all tests (must pass 100%)
- [ ] Update documentation if needed
- [ ] Commit code with descriptive message
- [ ] Update task status in tasks.md

### Weekly Checklist

- [ ] Review completed tasks
- [ ] Run full test suite
- [ ] Check code coverage (must be > 80%)
- [ ] Review code quality (PHPStan level 8)
- [ ] Update progress in project tracker
- [ ] Plan next week's tasks

---

## 🔍 Common Issues & Solutions

### Issue 1: Migration Fails

**Problem**: Foreign key constraint error

**Solution**: Ensure permissions table exists first
```bash
php artisan migrate:status
php artisan migrate --path=database/migrations/xxxx_create_permissions_table.php
```

### Issue 2: Tests Fail

**Problem**: Cache not clearing between tests

**Solution**: Add cache clearing in setUp()
```php
protected function setUp(): void
{
    parent::setUp();
    Cache::flush();
}
```

### Issue 3: Performance Issues

**Problem**: Slow permission checks

**Solution**: Verify Redis is running and cache is enabled
```bash
redis-cli ping
php artisan config:cache
```

---

## 📚 Additional Resources

### Documentation

- **[requirements.md](./requirements.md)** - Complete requirements
- **[tasks.md](./tasks.md)** - Detailed task breakdown
- **[README.md](./README.md)** - Architecture overview

### Standards

- **[Documentation Standards](./../../.kiro/steering/documentation-standards.md)**
- **[Component Standards](./../../.kiro/steering/canvastack-components.md)**
- **[i18n System](./../../.kiro/steering/i18n-system.md)**
- **[Theme Engine](./../../.kiro/steering/theme-engine-system.md)**

### External

- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Redis Caching](https://redis.io/docs/)
- [PHPUnit Testing](https://phpunit.de/documentation.html)

---

## 🎯 Success Criteria

You're done when:

- [x] All 65 tasks completed
- [x] All tests passing (100%)
- [x] Code coverage > 80%
- [x] PHPStan level 8 passes
- [x] Performance targets met
- [x] Documentation complete
- [x] Code reviewed and approved

---

## 📞 Need Help?

### Questions

1. Check [requirements.md](./requirements.md) for specifications
2. Check [tasks.md](./tasks.md) for implementation details
3. Review existing RBAC code for patterns
4. Ask team lead for clarification

### Issues

1. Check [Common Issues](#common-issues--solutions) above
2. Search GitHub issues
3. Create new issue with `fine-grained-permissions` label
4. Include error message and context

---

## 🚀 Ready to Start?

1. ✅ Read this guide
2. ✅ Review [summary.md](./summary.md)
3. ✅ Read [README.md](./README.md)
4. ✅ Start [Phase 1 tasks](./tasks.md#phase-1-database-schema--models-week-1)

**Good luck! 🎉**

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Complete  
**Author**: CanvaStack Team

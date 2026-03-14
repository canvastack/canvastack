# Proposal: Automated CRUD Actions with Hooks

**Status**: Proposal  
**Version**: 1.0.0  
**Date**: 2026-03-08  
**Author**: Kiro AI Assistant

---

## Overview

Proposal untuk menyederhanakan CRUD operations di CanvaStack dengan automated actions, hooks, dan cache invalidation otomatis.

---

## Problem Statement

### Current Implementation (Repetitive)

```php
// Controller harus tulis berulang-ulang untuk setiap model
public function store(Request $request)
{
    $user = User::create($request->validated());
    TableBuilder::clearCacheFor(User::class);
    return redirect()->route('users.index')->with('success', 'User created');
}

public function update(Request $request, User $user)
{
    $user->update($request->validated());
    TableBuilder::clearCacheFor(User::class);
    return redirect()->route('users.index')->with('success', 'User updated');
}

public function destroy(User $user)
{
    $user->delete();
    TableBuilder::clearCacheFor(User::class);
    return redirect()->route('users.index')->with('success', 'User deleted');
}
```

**Issues**:
- ❌ Repetitive code untuk setiap model
- ❌ Mudah lupa clear cache
- ❌ Tidak ada hooks untuk custom logic
- ❌ Tidak ada standardisasi

---

## Proposed Solution

### 1. Base CRUD Controller dengan Hooks

```php
namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Http\Request;

/**
 * Base CRUD Controller dengan automated actions dan hooks.
 * 
 * Provides:
 * - Automated CRUD operations
 * - Before/After hooks untuk custom logic
 * - Automatic cache invalidation
 * - Standardized responses
 * - Validation handling
 */
abstract class CrudController extends Controller
{
    /**
     * Model class untuk CRUD operations.
     * 
     * @var string
     */
    protected string $modelClass;

    /**
     * Route name prefix (e.g., 'users' untuk users.index, users.create, etc.)
     * 
     * @var string
     */
    protected string $routePrefix;

    /**
     * View path prefix (e.g., 'admin.users' untuk admin/users/index.blade.php)
     * 
     * @var string
     */
    protected string $viewPrefix;

    /**
     * Validation rules untuk create/update.
     * 
     * @var array
     */
    protected array $validationRules = [];

    /**
     * Fields untuk TableBuilder.
     * 
     * @var array
     */
    protected array $tableFields = [];

    /**
     * Automatic cache invalidation (default: true).
     * 
     * @var bool
     */
    protected bool $autoClearCache = true;

    /**
     * Display a listing of the resource.
     */
    public function index(TableBuilder $table)
    {
        // Before hook
        $this->beforeIndex($table);

        // Setup table
        $table->setContext($this->getContext());
        $table->setModel(new $this->modelClass());
        $table->setFields($this->tableFields);
        
        // Add default actions
        $this->configureTableActions($table);
        
        $table->format();

        // After hook
        $this->afterIndex($table);

        return view("{$this->viewPrefix}.index", ['table' => $table]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(FormBuilder $form)
    {
        // Before hook
        $this->beforeCreate($form);

        // Setup form
        $form->setContext($this->getContext());
        $this->configureForm($form);

        // After hook
        $this->afterCreate($form);

        return view("{$this->viewPrefix}.create", ['form' => $form]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate
        $validated = $request->validate($this->getValidationRules('create'));

        // Before hook
        $data = $this->beforeStore($validated);

        // Create model
        $model = $this->modelClass::create($data);

        // After hook
        $this->afterStore($model, $data);

        // Auto clear cache
        if ($this->autoClearCache) {
            TableBuilder::clearCacheFor($this->modelClass);
        }

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', __('ui.messages.created'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, FormBuilder $form)
    {
        $model = $this->findModel($id);

        // Before hook
        $this->beforeEdit($model, $form);

        // Setup form
        $form->setContext($this->getContext());
        $form->setModel($model);
        $this->configureForm($form, $model);

        // After hook
        $this->afterEdit($model, $form);

        return view("{$this->viewPrefix}.edit", [
            'form' => $form,
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $model = $this->findModel($id);

        // Validate
        $validated = $request->validate($this->getValidationRules('update', $model));

        // Before hook
        $data = $this->beforeUpdate($model, $validated);

        // Update model
        $model->update($data);

        // After hook
        $this->afterUpdate($model, $data);

        // Auto clear cache
        if ($this->autoClearCache) {
            TableBuilder::clearCacheFor($this->modelClass);
        }

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', __('ui.messages.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $model = $this->findModel($id);

        // Before hook
        $this->beforeDestroy($model);

        // Delete model
        $model->delete();

        // After hook
        $this->afterDestroy($model);

        // Auto clear cache
        if ($this->autoClearCache) {
            TableBuilder::clearCacheFor($this->modelClass);
        }

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', __('ui.messages.deleted'));
    }

    // ============================================
    // HOOKS - Override di child controller
    // ============================================

    /**
     * Hook: Before index.
     * 
     * @param TableBuilder $table
     * @return void
     */
    protected function beforeIndex(TableBuilder $table): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: After index.
     * 
     * @param TableBuilder $table
     * @return void
     */
    protected function afterIndex(TableBuilder $table): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: Before create.
     * 
     * @param FormBuilder $form
     * @return void
     */
    protected function beforeCreate(FormBuilder $form): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: After create.
     * 
     * @param FormBuilder $form
     * @return void
     */
    protected function afterCreate(FormBuilder $form): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: Before store (modify data before saving).
     * 
     * @param array $validated Validated data
     * @return array Modified data
     */
    protected function beforeStore(array $validated): array
    {
        // Override di child controller untuk modify data
        return $validated;
    }

    /**
     * Hook: After store (e.g., send email, log activity).
     * 
     * @param mixed $model Created model
     * @param array $data Stored data
     * @return void
     */
    protected function afterStore($model, array $data): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: Before edit.
     * 
     * @param mixed $model Model being edited
     * @param FormBuilder $form
     * @return void
     */
    protected function beforeEdit($model, FormBuilder $form): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: After edit.
     * 
     * @param mixed $model Model being edited
     * @param FormBuilder $form
     * @return void
     */
    protected function afterEdit($model, FormBuilder $form): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: Before update (modify data before saving).
     * 
     * @param mixed $model Model being updated
     * @param array $validated Validated data
     * @return array Modified data
     */
    protected function beforeUpdate($model, array $validated): array
    {
        // Override di child controller untuk modify data
        return $validated;
    }

    /**
     * Hook: After update (e.g., send notification, log activity).
     * 
     * @param mixed $model Updated model
     * @param array $data Updated data
     * @return void
     */
    protected function afterUpdate($model, array $data): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: Before destroy.
     * 
     * @param mixed $model Model being deleted
     * @return void
     */
    protected function beforeDestroy($model): void
    {
        // Override di child controller jika perlu
    }

    /**
     * Hook: After destroy (e.g., cleanup related data).
     * 
     * @param mixed $model Deleted model
     * @return void
     */
    protected function afterDestroy($model): void
    {
        // Override di child controller jika perlu
    }

    // ============================================
    // CONFIGURATION METHODS - Override di child
    // ============================================

    /**
     * Configure form fields.
     * MUST be implemented in child controller.
     * 
     * @param FormBuilder $form
     * @param mixed|null $model Model for edit, null for create
     * @return void
     */
    abstract protected function configureForm(FormBuilder $form, $model = null): void;

    /**
     * Configure table actions.
     * Override untuk customize actions.
     * 
     * @param TableBuilder $table
     * @return void
     */
    protected function configureTableActions(TableBuilder $table): void
    {
        $table->addAction('edit', route("{$this->routePrefix}.edit", ':id'), 'edit', __('ui.buttons.edit'));
        $table->addAction('delete', route("{$this->routePrefix}.destroy", ':id'), 'trash', __('ui.buttons.delete'), 'DELETE');
    }

    /**
     * Get validation rules.
     * Override untuk different rules untuk create vs update.
     * 
     * @param string $action 'create' or 'update'
     * @param mixed|null $model Model for update
     * @return array
     */
    protected function getValidationRules(string $action, $model = null): array
    {
        return $this->validationRules;
    }

    /**
     * Get context (admin or public).
     * 
     * @return string
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * Find model by ID or fail.
     * 
     * @param int|string $id
     * @return mixed
     */
    protected function findModel($id)
    {
        return $this->modelClass::findOrFail($id);
    }
}
```

---

## Usage Example: Simple Controller

### Minimal Implementation

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Http\Controllers\CrudController;

class UserController extends CrudController
{
    protected string $modelClass = User::class;
    protected string $routePrefix = 'admin.users';
    protected string $viewPrefix = 'admin.users';

    protected array $tableFields = [
        'name:Name',
        'email:Email',
        'created_at:Created',
    ];

    protected array $validationRules = [
        'name' => 'required|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
    ];

    /**
     * Configure form fields.
     */
    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', __('ui.labels.name'))->required();
        $form->email('email', __('ui.labels.email'))->required();
        
        if ($model === null) {
            // Create mode - password required
            $form->password('password', __('ui.labels.password'))->required();
        } else {
            // Edit mode - password optional
            $form->password('password', __('ui.labels.password'))
                ->placeholder(__('ui.placeholders.leave_blank'));
        }
    }
}
```

**That's it!** Semua CRUD methods (index, create, store, edit, update, destroy) sudah otomatis tersedia dengan:
- ✅ Automatic cache invalidation
- ✅ Standardized responses
- ✅ Validation handling
- ✅ i18n support

---

## Usage Example: With Hooks

### Advanced Implementation dengan Custom Logic

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Notifications\UserCreated;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends CrudController
{
    protected string $modelClass = User::class;
    protected string $routePrefix = 'admin.users';
    protected string $viewPrefix = 'admin.users';

    protected array $tableFields = [
        'name:Name',
        'email:Email',
        'role:Role',
        'created_at:Created',
    ];

    protected array $validationRules = [
        'name' => 'required|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'role' => 'required|in:admin,user',
    ];

    /**
     * Configure form fields.
     */
    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', __('ui.labels.name'))->required();
        $form->email('email', __('ui.labels.email'))->required();
        $form->password('password', __('ui.labels.password'))
            ->required($model === null);
        $form->select('role', __('ui.labels.role'), [
            'admin' => __('ui.roles.admin'),
            'user' => __('ui.roles.user'),
        ])->required();
    }

    /**
     * Configure table actions.
     */
    protected function configureTableActions(TableBuilder $table): void
    {
        // Add custom actions
        $table->addAction('view', route("{$this->routePrefix}.show", ':id'), 'eye', __('ui.buttons.view'));
        $table->addAction('edit', route("{$this->routePrefix}.edit", ':id'), 'edit', __('ui.buttons.edit'));
        $table->addAction('delete', route("{$this->routePrefix}.destroy", ':id'), 'trash', __('ui.buttons.delete'), 'DELETE');
    }

    /**
     * Hook: Before index - add filters.
     */
    protected function beforeIndex(TableBuilder $table): void
    {
        // Add custom filters
        $table->addFilter('role', 'select', [
            'label' => __('ui.labels.role'),
            'options' => [
                'admin' => __('ui.roles.admin'),
                'user' => __('ui.roles.user'),
            ],
        ]);

        // Enable caching
        $table->cache(300); // 5 minutes
    }

    /**
     * Hook: Before store - hash password.
     */
    protected function beforeStore(array $validated): array
    {
        // Hash password before saving
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        return $validated;
    }

    /**
     * Hook: After store - send notification.
     */
    protected function afterStore($model, array $data): void
    {
        // Send welcome email
        $model->notify(new UserCreated($model));

        // Log activity
        Log::info('User created', [
            'user_id' => $model->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Hook: Before update - hash password if provided.
     */
    protected function beforeUpdate($model, array $validated): array
    {
        // Only hash password if provided
        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Remove password from update if empty
            unset($validated['password']);
        }

        return $validated;
    }

    /**
     * Hook: After update - log activity.
     */
    protected function afterUpdate($model, array $data): void
    {
        Log::info('User updated', [
            'user_id' => $model->id,
            'updated_by' => auth()->id(),
            'changes' => $model->getChanges(),
        ]);
    }

    /**
     * Hook: Before destroy - check if can delete.
     */
    protected function beforeDestroy($model): void
    {
        // Prevent deleting yourself
        if ($model->id === auth()->id()) {
            abort(403, __('errors.cannot_delete_yourself'));
        }

        // Check if user has related data
        if ($model->posts()->count() > 0) {
            abort(403, __('errors.user_has_posts'));
        }
    }

    /**
     * Hook: After destroy - cleanup.
     */
    protected function afterDestroy($model): void
    {
        // Delete user's uploaded files
        // Storage::deleteDirectory("users/{$model->id}");

        // Log activity
        Log::info('User deleted', [
            'user_id' => $model->id,
            'deleted_by' => auth()->id(),
        ]);
    }

    /**
     * Get validation rules dengan dynamic unique rule untuk update.
     */
    protected function getValidationRules(string $action, $model = null): array
    {
        $rules = $this->validationRules;

        // For update, make email unique except current user
        if ($action === 'update' && $model) {
            $rules['email'] = "required|email|unique:users,email,{$model->id}";
            $rules['password'] = 'nullable|min:8'; // Password optional for update
        }

        return $rules;
    }
}
```

**Hasil**: Controller yang sangat clean dengan full control via hooks!

---

## Usage Example: Ultra Simple (No Hooks)

```php
namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Http\Controllers\CrudController;

class CategoryController extends CrudController
{
    protected string $modelClass = Category::class;
    protected string $routePrefix = 'admin.categories';
    protected string $viewPrefix = 'admin.categories';

    protected array $tableFields = [
        'name:Name',
        'slug:Slug',
        'created_at:Created',
    ];

    protected array $validationRules = [
        'name' => 'required|max:255',
        'slug' => 'required|unique:categories,slug',
    ];

    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', 'Name')->required();
        $form->text('slug', 'Slug')->required();
    }
}
```

**Done!** Hanya 20 baris code untuk full CRUD dengan cache invalidation otomatis!

---

## Advanced Features

### 1. Disable Auto Cache Invalidation

```php
class UserController extends CrudController
{
    protected bool $autoClearCache = false; // Disable auto clear

    protected function afterStore($model, array $data): void
    {
        // Manual cache clearing dengan custom logic
        if ($model->role === 'admin') {
            TableBuilder::clearCacheFor(User::class);
            TableBuilder::clearCacheFor(AdminLog::class);
        }
    }
}
```

### 2. Custom Redirect After Store

```php
class UserController extends CrudController
{
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules('create'));
        $data = $this->beforeStore($validated);
        $model = $this->modelClass::create($data);
        $this->afterStore($model, $data);

        if ($this->autoClearCache) {
            TableBuilder::clearCacheFor($this->modelClass);
        }

        // Custom redirect
        return redirect()
            ->route("{$this->routePrefix}.edit", $model->id)
            ->with('success', __('ui.messages.created_continue_editing'));
    }
}
```

### 3. Bulk Operations

```php
class UserController extends CrudController
{
    /**
     * Bulk delete users.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        foreach ($ids as $id) {
            $model = $this->findModel($id);
            $this->beforeDestroy($model);
            $model->delete();
            $this->afterDestroy($model);
        }

        // Clear cache once after all deletes
        if ($this->autoClearCache) {
            TableBuilder::clearCacheFor($this->modelClass);
        }

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', __('ui.messages.bulk_deleted', ['count' => count($ids)]));
    }
}
```

---

## Benefits

### Developer Experience
- ✅ **90% less code** untuk standard CRUD
- ✅ **Automatic cache invalidation** (no more forgetting)
- ✅ **Hooks** untuk custom logic
- ✅ **Standardized** responses dan error handling
- ✅ **Type-safe** dengan PHP 8.2+ features
- ✅ **i18n ready** dengan translation keys

### Maintainability
- ✅ **Single source of truth** untuk CRUD logic
- ✅ **Easy to update** (update base class, all controllers benefit)
- ✅ **Consistent** behavior across all controllers
- ✅ **Testable** (test base class once)

### Performance
- ✅ **Automatic cache invalidation** prevents stale data
- ✅ **Optimized** cache clearing (only affected models)
- ✅ **No performance overhead** (hooks only run when overridden)

---

## Implementation Plan

### Phase 1: Create Base CrudController
- [ ] Create `CrudController` base class
- [ ] Implement all CRUD methods
- [ ] Implement all hooks
- [ ] Add automatic cache invalidation
- [ ] Add comprehensive documentation

### Phase 2: Testing
- [ ] Unit tests untuk base controller
- [ ] Feature tests untuk CRUD operations
- [ ] Test hooks functionality
- [ ] Test cache invalidation

### Phase 3: Migration Guide
- [ ] Document migration from old controllers
- [ ] Provide examples
- [ ] Create generator command: `php artisan make:crud User`

---

## Comparison

### Before (Old Way)

```php
// UserController.php - 150+ lines
public function index() { /* 20 lines */ }
public function create() { /* 15 lines */ }
public function store(Request $request) { /* 25 lines */ }
public function edit($id) { /* 20 lines */ }
public function update(Request $request, $id) { /* 30 lines */ }
public function destroy($id) { /* 20 lines */ }
// + validation, cache clearing, redirects, etc.
```

### After (New Way)

```php
// UserController.php - 30 lines
class UserController extends CrudController
{
    protected string $modelClass = User::class;
    protected string $routePrefix = 'admin.users';
    protected string $viewPrefix = 'admin.users';
    protected array $tableFields = ['name:Name', 'email:Email'];
    protected array $validationRules = [/* rules */];

    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
    }
}
```

**80% code reduction!** 🎉

---

## Pertanyaan 2: Prisma Integration

**JAWABAN: BISA! Tapi perlu design yang hati-hati.**

### Prisma Overview

Prisma adalah ORM modern untuk Node.js/TypeScript. Untuk PHP, kita bisa:

1. **Option 1**: Integrate Prisma via API (Prisma as microservice)
2. **Option 2**: Use Prisma-like features in PHP (query builder enhancement)
3. **Option 3**: Create Prisma adapter untuk TableBuilder

### Proposed Design: Prisma-Style Query Builder

```php
// TableBuilder dengan Prisma-style API
$table->usePrisma(true)
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->include(['posts' => true, 'profile' => true]) // Like Prisma's include
    ->orderBy(['name' => 'asc'])
    ->take(10)
    ->skip(0);
```

### Implementation Concept

```php
// In TableBuilder.php
protected bool $usePrismaStyle = false;
protected array $prismaQuery = [];

public function usePrisma(bool $enabled = true): self
{
    $this->usePrismaStyle = $enabled;
    return $this;
}

public function include(array $relations): self
{
    if ($this->usePrismaStyle) {
        $this->prismaQuery['include'] = $relations;
        // Convert to Eloquent eager loading
        $this->eager(array_keys(array_filter($relations)));
    }
    return $this;
}

public function select(array $fields): self
{
    if ($this->usePrismaStyle) {
        $this->prismaQuery['select'] = $fields;
        // Convert to Eloquent select
        $this->setFields($fields);
    }
    return $this;
}

public function where($field, $operator = null, $value = null): self
{
    if ($this->usePrismaStyle) {
        // Prisma-style where
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->addCondition($key, '=', $val);
            }
        } else {
            $this->addCondition($field, $operator, $value);
        }
    }
    return $this;
}
```

---

## Recommendation

### Untuk Pertanyaan 1: IMPLEMENT NOW ✅

**Sangat recommended** untuk implement CrudController base class karena:
- ✅ Immediate value (simplify code drastically)
- ✅ Easy to implement (1-2 days)
- ✅ Backward compatible (optional, tidak break existing code)
- ✅ Solves real pain point (repetitive CRUD code)

### Untuk Pertanyaan 2: FUTURE ENHANCEMENT 🔮

**Bisa implement nanti** setelah core features stable karena:
- ⏰ Nice to have, bukan must have
- ⏰ Perlu research lebih dalam (Prisma integration strategy)
- ⏰ Eloquent sudah powerful, Prisma-style adalah syntactic sugar
- ⏰ Bisa jadi separate package/feature

---

## Next Steps

Mau saya buatkan:

1. **CrudController base class** dengan hooks dan auto cache invalidation?
2. **Generator command** untuk create CRUD controller: `php artisan make:crud User`?
3. **Migration guide** dari old controllers ke new CrudController?
4. **Spec document** untuk Prisma integration (future feature)?

Atau mau saya lanjutkan task berikutnya dari TanStack Multi-Table spec?

---

**Last Updated**: 2026-03-08  
**Status**: Proposal - Awaiting Approval

# CRUD Implementation Comparison

## Current vs Proposed Implementation

---

## Scenario: User Management CRUD

### ❌ CURRENT WAY (Repetitive - 150+ lines)

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(TableBuilder $table)
    {
        $table->setContext('admin');
        $table->setModel(new User());
        $table->setFields([
            'name:Name',
            'email:Email',
            'created_at:Created',
        ]);
        $table->addAction('edit', route('admin.users.edit', ':id'), 'edit', 'Edit');
        $table->addAction('delete', route('admin.users.destroy', ':id'), 'trash', 'Delete', 'DELETE');
        $table->format();
        
        return view('admin.users.index', ['table' => $table]);
    }

    public function create(FormBuilder $form)
    {
        $form->setContext('admin');
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        $form->password('password', 'Password')->required();
        
        return view('admin.users.create', ['form' => $form]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        // Create user
        $user = User::create($validated);

        // ⚠️ HARUS INGAT: Clear cache
        TableBuilder::clearCacheFor(User::class);

        // ⚠️ HARUS INGAT: Send notification
        // $user->notify(new UserCreated($user));

        // ⚠️ HARUS INGAT: Log activity
        // Log::info('User created', ['user_id' => $user->id]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully');
    }

    public function edit($id, FormBuilder $form)
    {
        $user = User::findOrFail($id);

        $form->setContext('admin');
        $form->setModel($user);
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        $form->password('password', 'Password')->placeholder('Leave blank to keep current');
        
        return view('admin.users.edit', ['form' => $form, 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'password' => 'nullable|min:8',
        ]);

        // Hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Update user
        $user->update($validated);

        // ⚠️ HARUS INGAT: Clear cache
        TableBuilder::clearCacheFor(User::class);

        // ⚠️ HARUS INGAT: Log activity
        // Log::info('User updated', ['user_id' => $user->id]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // ⚠️ HARUS INGAT: Check if can delete
        // if ($user->id === auth()->id()) {
        //     abort(403, 'Cannot delete yourself');
        // }

        $user->delete();

        // ⚠️ HARUS INGAT: Clear cache
        TableBuilder::clearCacheFor(User::class);

        // ⚠️ HARUS INGAT: Cleanup files
        // Storage::deleteDirectory("users/{$user->id}");

        // ⚠️ HARUS INGAT: Log activity
        // Log::info('User deleted', ['user_id' => $user->id]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }
}
```

**Problems**:
- 🔴 150+ lines of repetitive code
- 🔴 Easy to forget cache clearing
- 🔴 Easy to forget logging
- 🔴 Easy to forget notifications
- 🔴 Inconsistent error handling
- 🔴 Hard to maintain (change in one controller, must change all)

---

## ✅ PROPOSED WAY (Automated - 30 lines)

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Notifications\UserCreated;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends CrudController
{
    // Configuration (5 lines)
    protected string $modelClass = User::class;
    protected string $routePrefix = 'admin.users';
    protected string $viewPrefix = 'admin.users';
    protected array $tableFields = ['name:Name', 'email:Email', 'created_at:Created'];
    protected array $validationRules = [
        'name' => 'required|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
    ];

    // Form configuration (5 lines)
    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        $form->password('password', 'Password')->required($model === null);
    }

    // Hooks - OPTIONAL (hanya jika perlu custom logic)
    protected function beforeStore(array $validated): array
    {
        $validated['password'] = Hash::make($validated['password']);
        return $validated;
    }

    protected function afterStore($model, array $data): void
    {
        $model->notify(new UserCreated($model));
        Log::info('User created', ['user_id' => $model->id]);
    }

    protected function beforeUpdate($model, array $validated): array
    {
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        return $validated;
    }

    protected function beforeDestroy($model): void
    {
        if ($model->id === auth()->id()) {
            abort(403, 'Cannot delete yourself');
        }
    }
}
```

**Benefits**:
- ✅ 30 lines vs 150+ lines (80% reduction)
- ✅ Automatic cache invalidation (no forgetting)
- ✅ Hooks untuk custom logic (clean separation)
- ✅ Standardized responses
- ✅ Easy to maintain

---

## What You Get Automatically

### Without Writing Any Code:

```php
class CategoryController extends CrudController
{
    protected string $modelClass = Category::class;
    protected string $routePrefix = 'admin.categories';
    protected string $viewPrefix = 'admin.categories';
    protected array $tableFields = ['name:Name'];
    protected array $validationRules = ['name' => 'required'];

    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', 'Name')->required();
    }
}
```

**You automatically get**:
- ✅ `index()` - List page dengan TableBuilder
- ✅ `create()` - Create form dengan FormBuilder
- ✅ `store()` - Save data + clear cache + redirect
- ✅ `edit($id)` - Edit form dengan FormBuilder
- ✅ `update($id)` - Update data + clear cache + redirect
- ✅ `destroy($id)` - Delete data + clear cache + redirect
- ✅ Validation handling
- ✅ Error handling
- ✅ i18n messages
- ✅ Cache invalidation

---

## Hook System Explained

### Available Hooks

| Hook | When | Use Case |
|------|------|----------|
| `beforeIndex($table)` | Before showing list | Add filters, customize table |
| `afterIndex($table)` | After table configured | Add custom data to view |
| `beforeCreate($form)` | Before showing create form | Pre-fill form data |
| `afterCreate($form)` | After form configured | Add custom fields |
| `beforeStore($validated)` | Before saving new data | Hash password, modify data |
| `afterStore($model, $data)` | After data saved | Send email, log activity |
| `beforeEdit($model, $form)` | Before showing edit form | Check permissions |
| `afterEdit($model, $form)` | After form configured | Add custom fields |
| `beforeUpdate($model, $validated)` | Before updating data | Hash password, modify data |
| `afterUpdate($model, $data)` | After data updated | Send notification, log |
| `beforeDestroy($model)` | Before deleting | Check if can delete |
| `afterDestroy($model)` | After deleted | Cleanup files, log activity |

### Hook Examples

#### Example 1: Hash Password

```php
protected function beforeStore(array $validated): array
{
    if (isset($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    }
    return $validated;
}
```

#### Example 2: Send Notification

```php
protected function afterStore($model, array $data): void
{
    $model->notify(new UserCreated($model));
}
```

#### Example 3: Check Permission

```php
protected function beforeDestroy($model): void
{
    if ($model->id === auth()->id()) {
        abort(403, 'Cannot delete yourself');
    }
}
```

#### Example 4: Cleanup Files

```php
protected function afterDestroy($model): void
{
    Storage::deleteDirectory("users/{$model->id}");
}
```

#### Example 5: Add Table Filters

```php
protected function beforeIndex(TableBuilder $table): void
{
    $table->addFilter('role', 'select', [
        'label' => 'Role',
        'options' => ['admin' => 'Admin', 'user' => 'User'],
    ]);
    
    $table->cache(300); // Enable caching
}
```

---

## Real-World Example: Product Management

```php
namespace App\Http\Controllers\Admin;

use App\Models\Product;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Http\Controllers\CrudController;
use Illuminate\Support\Str;

class ProductController extends CrudController
{
    protected string $modelClass = Product::class;
    protected string $routePrefix = 'admin.products';
    protected string $viewPrefix = 'admin.products';

    protected array $tableFields = [
        'name:Product Name',
        'sku:SKU',
        'price:Price',
        'stock:Stock',
        'category.name:Category',
    ];

    protected array $validationRules = [
        'name' => 'required|max:255',
        'sku' => 'required|unique:products,sku',
        'price' => 'required|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'category_id' => 'required|exists:categories,id',
        'description' => 'nullable',
        'image' => 'nullable|image|max:2048',
    ];

    protected function configureForm(FormBuilder $form, $model = null): void
    {
        $form->text('name', 'Product Name')->required();
        $form->text('sku', 'SKU')->required();
        $form->number('price', 'Price')->required()->min(0);
        $form->number('stock', 'Stock')->required()->min(0);
        $form->select('category_id', 'Category', $this->getCategoryOptions())->required();
        $form->textarea('description', 'Description')->ckeditor();
        $form->file('image', 'Product Image')->accept('image/*');
    }

    protected function beforeIndex(TableBuilder $table): void
    {
        // Add filters
        $table->addFilter('category_id', 'select', [
            'label' => 'Category',
            'options' => $this->getCategoryOptions(),
        ]);

        // Add price range filter
        $table->addFilter('price_min', 'number', ['label' => 'Min Price']);
        $table->addFilter('price_max', 'number', ['label' => 'Max Price']);

        // Enable caching
        $table->cache(300);

        // Eager load category
        $table->eager(['category']);
    }

    protected function beforeStore(array $validated): array
    {
        // Auto-generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        // Handle image upload
        if (request()->hasFile('image')) {
            $validated['image'] = request()->file('image')->store('products', 'public');
        }

        return $validated;
    }

    protected function afterStore($model, array $data): void
    {
        // Clear category cache too (related data)
        TableBuilder::clearCacheFor(Category::class);

        // Log activity
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->log('Product created');
    }

    protected function beforeUpdate($model, array $validated): array
    {
        // Update slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $model->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle image upload
        if (request()->hasFile('image')) {
            // Delete old image
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
            $validated['image'] = request()->file('image')->store('products', 'public');
        }

        return $validated;
    }

    protected function afterUpdate($model, array $data): void
    {
        // Clear category cache too
        TableBuilder::clearCacheFor(Category::class);

        // Log activity
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->log('Product updated');
    }

    protected function beforeDestroy($model): void
    {
        // Check if product has orders
        if ($model->orders()->count() > 0) {
            abort(403, 'Cannot delete product with existing orders');
        }
    }

    protected function afterDestroy($model): void
    {
        // Delete product image
        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        // Clear category cache too
        TableBuilder::clearCacheFor(Category::class);

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->log("Product deleted: {$model->name}");
    }

    private function getCategoryOptions(): array
    {
        return Category::pluck('name', 'id')->toArray();
    }
}
```

**Total**: ~120 lines dengan hooks, tapi semua CRUD methods otomatis!

---

## Comparison Table

| Aspect | Current Way | Proposed Way | Improvement |
|--------|-------------|--------------|-------------|
| **Lines of Code** | 150+ lines | 30-120 lines | 20-80% reduction |
| **Cache Invalidation** | Manual (easy to forget) | Automatic | 100% reliable |
| **Validation** | Repeat in each method | Define once | DRY principle |
| **Error Handling** | Manual in each method | Automatic | Consistent |
| **Logging** | Manual (easy to forget) | Via hooks | Standardized |
| **Notifications** | Manual (easy to forget) | Via hooks | Consistent |
| **Redirects** | Manual in each method | Automatic | Standardized |
| **i18n Messages** | Manual strings | Automatic | Consistent |
| **Maintainability** | Hard (change all controllers) | Easy (change base class) | Much easier |
| **Testing** | Test each controller | Test base class once | Faster |

---

## Code Reduction Examples

### Example 1: Simple CRUD (Category)

**Before**: 80 lines  
**After**: 20 lines  
**Reduction**: 75%

### Example 2: CRUD with Hooks (User)

**Before**: 150 lines  
**After**: 60 lines  
**Reduction**: 60%

### Example 3: CRUD with Complex Logic (Product)

**Before**: 200 lines  
**After**: 120 lines  
**Reduction**: 40%

---

## Migration Path

### Step 1: Create Base CrudController

```bash
# Create base controller
php artisan make:controller CrudController --base
```

### Step 2: Migrate Existing Controllers

```bash
# Generate new CRUD controller from existing
php artisan make:crud User --from-existing
```

### Step 3: Test

```bash
# Test new controller
php artisan test --filter=UserControllerTest
```

### Step 4: Deploy

```bash
# Deploy when all tests pass
git commit -m "Migrate UserController to CrudController"
```

---

## Backward Compatibility

### Old Controllers Still Work

```php
// Old controller - masih bisa dipakai
class OldUserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->validated());
        TableBuilder::clearCacheFor(User::class);
        return redirect()->route('users.index');
    }
}
```

### New Controllers Use Base Class

```php
// New controller - lebih simple
class NewUserController extends CrudController
{
    protected string $modelClass = User::class;
    // ... minimal config
}
```

**Both work!** No breaking changes.

---

## Recommendation

### ✅ IMPLEMENT THIS NOW

**Why**:
1. **Immediate value** - Drastically reduce code
2. **Easy to implement** - 1-2 days work
3. **Backward compatible** - Optional, tidak break existing code
4. **Solves real pain** - Repetitive CRUD code
5. **Improves quality** - Standardized, tested, maintainable

### 📋 Implementation Checklist

- [ ] Create `CrudController` base class
- [ ] Implement all CRUD methods
- [ ] Implement all hooks
- [ ] Add automatic cache invalidation
- [ ] Write comprehensive tests
- [ ] Write documentation
- [ ] Create generator command
- [ ] Create migration guide

**Estimated Time**: 2-3 days

---

**Last Updated**: 2026-03-08  
**Status**: Proposal - Ready for Implementation

# Layered Architecture

Detailed explanation of CanvaStack's layered architecture and layer responsibilities.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Presentation Layer](#presentation-layer)
3. [Application Layer](#application-layer)
4. [Service Layer](#service-layer)
5. [Repository Layer](#repository-layer)
6. [Data Layer](#data-layer)
7. [Cross-Cutting Concerns](#cross-cutting-concerns)

---

## Architecture Overview

CanvaStack uses a 5-layer architecture for clear separation of concerns:

```
┌─────────────────────────────────────┐
│      Presentation Layer             │  ← User Interface
├─────────────────────────────────────┤
│      Application Layer              │  ← HTTP Handling
├─────────────────────────────────────┤
│      Service Layer                  │  ← Business Logic
├─────────────────────────────────────┤
│      Repository Layer               │  ← Data Access
├─────────────────────────────────────┤
│      Data Layer                     │  ← Persistence
└─────────────────────────────────────┘
```

### Layer Communication Rules

1. **Top-Down Only**: Layers can only call layers below them
2. **No Skipping**: Don't skip layers (e.g., Presentation → Repository)
3. **Interfaces**: Use interfaces for loose coupling
4. **Dependency Injection**: Inject dependencies, don't create them

---

## Presentation Layer

### Responsibility

Handle user interface and user interactions.

### Components

- **Blade Templates**: Server-side rendering
- **Alpine.js Components**: Client-side interactivity
- **Tailwind CSS**: Styling
- **JavaScript**: Browser interactions

### Example: Form View

```blade
<!-- resources/views/users/create.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Create User</h1>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                {!! $form->render() !!}
                
                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
```

### Example: Alpine.js Component

```html
<div x-data="{ 
    open: false,
    selected: null 
}">
    <button @click="open = !open" class="btn">
        Toggle Dropdown
    </button>
    
    <div x-show="open" 
         x-transition
         class="dropdown-menu">
        <a href="#" @click="selected = 'option1'">Option 1</a>
        <a href="#" @click="selected = 'option2'">Option 2</a>
    </div>
</div>
```

### Best Practices

- Keep logic minimal in views
- Use components for reusability
- Separate concerns (HTML, CSS, JS)
- Follow accessibility guidelines

---

## Application Layer

### Responsibility

Handle HTTP requests and responses, coordinate application flow.

### Components

- **Controllers**: Handle requests
- **Middleware**: Process requests/responses
- **Form Requests**: Validate input
- **Resources**: Format output

### Example: Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use App\Services\UserService;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    
    public function create(FormBuilder $form)
    {
        // Build form using service layer
        $form->text('name', 'Full Name')
            ->required()
            ->icon('user');
        
        $form->email('email', 'Email Address')
            ->required()
            ->icon('mail');
        
        $form->password('password', 'Password')
            ->required()
            ->minLength(8)
            ->icon('lock');
        
        return view('users.create', ['form' => $form]);
    }
    
    public function store(StoreUserRequest $request)
    {
        // Delegate to service layer
        $user = $this->userService->createUser($request->validated());
        
        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully!');
    }
}
```

### Example: Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }
        
        return $next($request);
    }
}
```

### Example: Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }
    
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
```

### Best Practices

- Keep controllers thin
- Delegate to service layer
- Use form requests for validation
- Return appropriate responses

---

## Service Layer

### Responsibility

Implement business logic and coordinate operations.

### Components

- **FormBuilder**: Build forms
- **TableBuilder**: Build tables
- **Business Services**: Domain logic
- **Validators**: Business rules

### Example: FormBuilder Service

```php
<?php

namespace Canvastack\Canvastack\Components\Form;

class FormBuilder
{
    private array $fields = [];
    private ?object $model = null;
    
    public function __construct(
        private FieldFactory $fieldFactory,
        private ValidationCache $validationCache,
        private RendererInterface $renderer
    ) {}
    
    public function text(string $name, $label = null): TextField
    {
        $field = $this->fieldFactory->create('text', $name, $label);
        
        // Bind model value if available
        if ($this->model && property_exists($this->model, $name)) {
            $field->setValue($this->model->{$name});
        }
        
        $this->fields[$name] = $field;
        return $field;
    }
    
    public function setModel(?object $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    public function render(): string
    {
        return $this->renderer->render([
            'fields' => $this->fields,
            'model' => $this->model,
        ]);
    }
}
```

### Example: Business Service

```php
<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Events\UserCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;

class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}
    
    public function createUser(array $data): User
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);
        
        // Create user via repository
        $user = $this->repository->create($data);
        
        // Dispatch event
        Event::dispatch(new UserCreated($user));
        
        // Send welcome email
        $this->sendWelcomeEmail($user);
        
        return $user;
    }
    
    public function updateUser(int $id, array $data): User
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        // Update via repository
        $user = $this->repository->update($id, $data);
        
        // Invalidate cache
        $this->repository->clearCache($id);
        
        return $user;
    }
    
    private function sendWelcomeEmail(User $user): void
    {
        // Email sending logic
    }
}
```

### Best Practices

- Encapsulate business logic
- Use repositories for data access
- Dispatch events for side effects
- Keep services focused

---

## Repository Layer

### Responsibility

Abstract data access and provide caching.

### Components

- **Repositories**: Data access
- **Query Builders**: Complex queries
- **Cache Managers**: Caching strategy

### Example: Repository

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserRepository
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function find(int $id): ?User
    {
        return Cache::remember(
            "user.{$id}",
            self::CACHE_TTL,
            fn() => User::find($id)
        );
    }
    
    public function all(): Collection
    {
        return Cache::remember(
            'users.all',
            self::CACHE_TTL,
            fn() => User::all()
        );
    }
    
    public function create(array $data): User
    {
        $user = User::create($data);
        
        // Invalidate list cache
        Cache::forget('users.all');
        
        return $user;
    }
    
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        
        // Invalidate caches
        Cache::forget("user.{$id}");
        Cache::forget('users.all');
        
        return $user;
    }
    
    public function delete(int $id): bool
    {
        $result = User::destroy($id);
        
        // Invalidate caches
        Cache::forget("user.{$id}");
        Cache::forget('users.all');
        
        return $result > 0;
    }
    
    public function findByEmail(string $email): ?User
    {
        return Cache::remember(
            "user.email.{$email}",
            self::CACHE_TTL,
            fn() => User::where('email', $email)->first()
        );
    }
    
    public function clearCache(int $id): void
    {
        Cache::forget("user.{$id}");
        Cache::forget('users.all');
    }
}
```

### Example: Query Builder

```php
<?php

namespace App\Repositories\Builders;

use Illuminate\Database\Eloquent\Builder;

class UserQueryBuilder
{
    public function __construct(
        private Builder $query
    ) {}
    
    public function active(): self
    {
        $this->query->where('status', 'active');
        return $this;
    }
    
    public function withRoles(): self
    {
        $this->query->with('roles');
        return $this;
    }
    
    public function search(string $term): self
    {
        $this->query->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
        return $this;
    }
    
    public function get()
    {
        return $this->query->get();
    }
}
```

### Best Practices

- Centralize data access
- Implement caching strategy
- Use query builders for complex queries
- Handle cache invalidation

---

## Data Layer

### Responsibility

Persist and retrieve data.

### Components

- **Eloquent Models**: ORM
- **Database**: MySQL
- **Cache Store**: Redis
- **Migrations**: Schema

### Example: Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function isAdmin(): bool
    {
        return $this->roles()->where('name', 'admin')->exists();
    }
}
```

### Example: Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('created_at');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### Best Practices

- Use Eloquent relationships
- Define fillable/guarded
- Cast attributes appropriately
- Add database indexes

---

## Cross-Cutting Concerns

### Logging

```php
use Illuminate\Support\Facades\Log;

Log::info('User created', ['user_id' => $user->id]);
Log::error('Failed to create user', ['error' => $e->getMessage()]);
```

### Caching

```php
use Illuminate\Support\Facades\Cache;

$value = Cache::remember('key', 3600, function() {
    return expensive_operation();
});
```

### Events

```php
use Illuminate\Support\Facades\Event;

Event::dispatch(new UserCreated($user));
```

### Validation

```php
$validator = Validator::make($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
```

---

## Layer Interaction Example

Complete flow from request to response:

```php
// 1. Presentation Layer - View
// resources/views/users/create.blade.php
{!! $form->render() !!}

// 2. Application Layer - Controller
class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        // 3. Service Layer - Business Logic
        $user = $this->userService->createUser($request->validated());
        
        return redirect()->route('users.show', $user);
    }
}

// 3. Service Layer
class UserService
{
    public function createUser(array $data): User
    {
        // 4. Repository Layer - Data Access
        return $this->repository->create($data);
    }
}

// 4. Repository Layer
class UserRepository
{
    public function create(array $data): User
    {
        // 5. Data Layer - Persistence
        return User::create($data);
    }
}

// 5. Data Layer - Model
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
}
```

---

## Benefits of Layered Architecture

1. **Separation of Concerns**: Each layer has clear responsibility
2. **Maintainability**: Easy to locate and fix issues
3. **Testability**: Each layer can be tested independently
4. **Scalability**: Easy to scale individual layers
5. **Flexibility**: Easy to swap implementations
6. **Reusability**: Layers can be reused across features

---

## Next Steps

- [Design Patterns](design-patterns.md) - Patterns used
- [Dependency Injection](dependency-injection.md) - DI details
- [Overview](overview.md) - Architecture overview

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Production Ready

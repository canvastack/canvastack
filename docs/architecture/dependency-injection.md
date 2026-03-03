# Dependency Injection

Complete guide to Dependency Injection in CanvaStack Enhanced.

## Table of Contents

1. [What is Dependency Injection](#what-is-dependency-injection)
2. [Benefits](#benefits)
3. [Laravel Service Container](#laravel-service-container)
4. [Constructor Injection](#constructor-injection)
5. [Method Injection](#method-injection)
6. [Service Providers](#service-providers)
7. [Binding Types](#binding-types)
8. [Best Practices](#best-practices)

---

## What is Dependency Injection

Dependency Injection (DI) is a design pattern where objects receive their dependencies from external sources rather than creating them internally.

### Without DI (Bad)

```php
class UserController
{
    public function store(Request $request)
    {
        // Creating dependencies inside the class
        $repository = new UserRepository();
        $validator = new UserValidator();
        $mailer = new Mailer();
        
        // Tightly coupled, hard to test
        $user = $repository->create($request->all());
    }
}
```

### With DI (Good)

```php
class UserController
{
    public function __construct(
        private UserRepository $repository,
        private UserValidator $validator,
        private Mailer $mailer
    ) {}
    
    public function store(Request $request)
    {
        // Dependencies injected, loosely coupled, easy to test
        $user = $this->repository->create($request->all());
    }
}
```

---

## Benefits

### 1. Loose Coupling

Classes don't depend on concrete implementations:

```php
// Interface
interface CacheInterface
{
    public function get(string $key);
    public function put(string $key, $value, int $ttl);
}

// Implementation 1
class RedisCache implements CacheInterface
{
    public function get(string $key) { /* Redis logic */ }
    public function put(string $key, $value, int $ttl) { /* Redis logic */ }
}

// Implementation 2
class FileCache implements CacheInterface
{
    public function get(string $key) { /* File logic */ }
    public function put(string $key, $value, int $ttl) { /* File logic */ }
}

// Consumer depends on interface, not implementation
class FormBuilder
{
    public function __construct(
        private CacheInterface $cache  // Can be Redis or File
    ) {}
}
```

### 2. Easy Testing

Mock dependencies in tests:

```php
class FormBuilderTest extends TestCase
{
    public function test_form_caches_validation_rules()
    {
        // Mock cache dependency
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('put')
            ->once()
            ->with('form.rules', Mockery::any(), 3600);
        
        // Inject mock
        $form = new FormBuilder($cache);
        $form->setValidations(['name' => 'required']);
    }
}
```

### 3. Flexibility

Easy to swap implementations:

```php
// Development: Use file cache
app()->bind(CacheInterface::class, FileCache::class);

// Production: Use Redis cache
app()->bind(CacheInterface::class, RedisCache::class);
```

### 4. Single Responsibility

Classes focus on their core responsibility:

```php
class UserService
{
    // Dependencies handle specific concerns
    public function __construct(
        private UserRepository $repository,      // Data access
        private UserValidator $validator,        // Validation
        private EventDispatcher $events,         // Events
        private Mailer $mailer                   // Email
    ) {}
    
    public function createUser(array $data): User
    {
        // Focus on business logic only
        $this->validator->validate($data);
        $user = $this->repository->create($data);
        $this->events->dispatch(new UserCreated($user));
        $this->mailer->send($user, 'welcome');
        return $user;
    }
}
```

---

## Laravel Service Container

Laravel's IoC container manages dependency injection automatically.

### Automatic Resolution

```php
// Laravel automatically resolves dependencies
class UserController extends Controller
{
    // No need to manually instantiate
    public function __construct(
        private UserService $userService,
        private FormBuilder $formBuilder
    ) {}
}
```

### How It Works

```php
// 1. Laravel sees UserController needs UserService
// 2. Laravel checks if UserService has dependencies
// 3. Laravel resolves all dependencies recursively
// 4. Laravel instantiates and injects everything

// Example resolution chain:
UserController
    ├── UserService
    │   ├── UserRepository
    │   │   └── CacheManager
    │   ├── UserValidator
    │   └── EventDispatcher
    └── FormBuilder
        ├── FieldFactory
        ├── ValidationCache
        └── RendererInterface
```

---

## Constructor Injection

Most common form of DI in CanvaStack.

### Basic Example

```php
class FormBuilder
{
    public function __construct(
        private FieldFactory $fieldFactory,
        private ValidationCache $validationCache
    ) {}
    
    public function text(string $name): TextField
    {
        return $this->fieldFactory->create('text', $name);
    }
}
```

### With Interfaces

```php
class FormBuilder
{
    public function __construct(
        private FieldFactory $fieldFactory,
        private ValidationCache $validationCache,
        private RendererInterface $renderer  // Interface, not concrete class
    ) {}
}
```

### Multiple Dependencies

```php
class TableBuilder
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private QueryOptimizer $optimizer,
        private QueryCache $cache,
        private RendererInterface $renderer,
        private PaginationManager $pagination
    ) {}
}
```

---

## Method Injection

Inject dependencies into specific methods.

### Controller Method Injection

```php
class UserController extends Controller
{
    // Inject into method, not constructor
    public function create(FormBuilder $form)
    {
        $form->text('name', 'Name')->required();
        $form->email('email', 'Email')->required();
        
        return view('users.create', ['form' => $form]);
    }
    
    public function index(TableBuilder $table)
    {
        $table->setModel(User::class);
        $table->column('name', 'Name');
        
        return view('users.index', ['table' => $table]);
    }
}
```

### When to Use

- **Constructor Injection**: Dependencies used across multiple methods
- **Method Injection**: Dependencies used in single method

```php
class UserController extends Controller
{
    // Used in multiple methods → Constructor injection
    public function __construct(
        private UserService $userService
    ) {}
    
    // Used only here → Method injection
    public function create(FormBuilder $form)
    {
        // Build form
    }
    
    // Used only here → Method injection
    public function index(TableBuilder $table)
    {
        // Build table
    }
}
```

---

## Service Providers

Register bindings in service providers.

### Basic Service Provider

```php
<?php

namespace Canvastack\Canvastack;

use Illuminate\Support\ServiceProvider;

class CanvastackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(
            RendererInterface::class,
            AdminRenderer::class
        );
        
        // Bind singletons
        $this->app->singleton(
            CacheManager::class,
            function ($app) {
                return new CacheManager(
                    $app->make('cache')->driver('redis')
                );
            }
        );
        
        // Bind with context
        $this->app->when(FormBuilder::class)
            ->needs(RendererInterface::class)
            ->give(AdminRenderer::class);
        
        $this->app->when(TableBuilder::class)
            ->needs(RendererInterface::class)
            ->give(AdminRenderer::class);
    }
    
    public function boot(): void
    {
        // Boot logic
    }
}
```

### Contextual Binding

Different implementations for different contexts:

```php
public function register(): void
{
    // Admin context uses AdminRenderer
    $this->app->when(AdminController::class)
        ->needs(RendererInterface::class)
        ->give(AdminRenderer::class);
    
    // Public context uses PublicRenderer
    $this->app->when(PublicController::class)
        ->needs(RendererInterface::class)
        ->give(PublicRenderer::class);
}
```

---

## Binding Types

### 1. Simple Binding

```php
// Bind interface to implementation
$this->app->bind(CacheInterface::class, RedisCache::class);

// Usage
$cache = app(CacheInterface::class);  // Returns RedisCache instance
```

### 2. Singleton Binding

```php
// Single instance shared across application
$this->app->singleton(CacheManager::class, function ($app) {
    return new CacheManager($app->make('cache'));
});

// Usage
$cache1 = app(CacheManager::class);
$cache2 = app(CacheManager::class);
// $cache1 === $cache2 (same instance)
```

### 3. Instance Binding

```php
// Bind existing instance
$cache = new CacheManager(Redis::connection());
$this->app->instance(CacheManager::class, $cache);
```

### 4. Closure Binding

```php
// Bind with custom logic
$this->app->bind(FormBuilder::class, function ($app) {
    $context = request()->segment(1) === 'admin' ? 'admin' : 'public';
    
    $renderer = $context === 'admin' 
        ? new AdminRenderer() 
        : new PublicRenderer();
    
    return new FormBuilder(
        $app->make(FieldFactory::class),
        $app->make(ValidationCache::class),
        $renderer
    );
});
```

### 5. Tagged Binding

```php
// Tag multiple bindings
$this->app->tag([
    RedisCache::class,
    FileCache::class,
    DatabaseCache::class
], 'caches');

// Resolve all tagged
$caches = app()->tagged('caches');
```

---

## Best Practices

### 1. Depend on Abstractions

```php
// Good: Depend on interface
class FormBuilder
{
    public function __construct(
        private RendererInterface $renderer
    ) {}
}

// Bad: Depend on concrete class
class FormBuilder
{
    public function __construct(
        private AdminRenderer $renderer  // Tightly coupled
    ) {}
}
```

### 2. Use Constructor Injection

```php
// Good: Constructor injection
class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}
}

// Bad: Property injection
class UserService
{
    public UserRepository $repository;
    
    public function setRepository(UserRepository $repository)
    {
        $this->repository = $repository;
    }
}
```

### 3. Keep Constructors Simple

```php
// Good: Simple constructor
public function __construct(
    private UserRepository $repository,
    private EventDispatcher $events
) {}

// Bad: Logic in constructor
public function __construct(
    private UserRepository $repository
) {
    $this->users = $this->repository->all();  // Don't do this
    $this->count = $this->users->count();     // Don't do this
}
```

### 4. Use Type Hints

```php
// Good: Type hints
public function __construct(
    private UserRepository $repository,
    private CacheInterface $cache
) {}

// Bad: No type hints
public function __construct(
    $repository,
    $cache
) {}
```

### 5. Avoid Service Locator

```php
// Good: Dependency injection
class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}
}

// Bad: Service locator
class UserService
{
    public function createUser(array $data)
    {
        $repository = app(UserRepository::class);  // Don't do this
        return $repository->create($data);
    }
}
```

---

## Testing with DI

### Mock Dependencies

```php
class UserServiceTest extends TestCase
{
    public function test_creates_user()
    {
        // Mock repository
        $repository = Mockery::mock(UserRepository::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(['name' => 'John'])
            ->andReturn(new User(['name' => 'John']));
        
        // Mock events
        $events = Mockery::mock(EventDispatcher::class);
        $events->shouldReceive('dispatch')->once();
        
        // Inject mocks
        $service = new UserService($repository, $events);
        
        // Test
        $user = $service->createUser(['name' => 'John']);
        $this->assertEquals('John', $user->name);
    }
}
```

### Swap Implementations

```php
class FormBuilderTest extends TestCase
{
    public function test_form_renders_correctly()
    {
        // Use test renderer
        $renderer = new TestRenderer();
        $form = new FormBuilder(
            new FieldFactory(),
            new ValidationCache(),
            $renderer
        );
        
        $form->text('name', 'Name');
        $html = $form->render();
        
        $this->assertStringContainsString('name="name"', $html);
    }
}
```

---

## Common Patterns

### Factory with DI

```php
class FieldFactory
{
    public function __construct(
        private ValidationCache $cache
    ) {}
    
    public function create(string $type, string $name): BaseField
    {
        $field = match($type) {
            'text' => new TextField($name),
            'email' => new EmailField($name),
            default => throw new InvalidArgumentException()
        };
        
        // Inject cache into field
        $field->setCache($this->cache);
        
        return $field;
    }
}
```

### Repository with DI

```php
class UserRepository
{
    public function __construct(
        private CacheManager $cache,
        private EventDispatcher $events
    ) {}
    
    public function create(array $data): User
    {
        $user = User::create($data);
        
        $this->cache->forget('users.all');
        $this->events->dispatch(new UserCreated($user));
        
        return $user;
    }
}
```

---

## Next Steps

- [Overview](overview.md) - Architecture overview
- [Design Patterns](design-patterns.md) - Patterns used
- [Layered Architecture](layered-architecture.md) - Layer details

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Production Ready

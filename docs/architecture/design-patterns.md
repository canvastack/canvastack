# Design Patterns

Comprehensive guide to design patterns used in CanvaStack Enhanced.

## Table of Contents

1. [Creational Patterns](#creational-patterns)
2. [Structural Patterns](#structural-patterns)
3. [Behavioral Patterns](#behavioral-patterns)
4. [Architectural Patterns](#architectural-patterns)

---

## Creational Patterns

### 1. Factory Pattern

**Purpose**: Create objects without specifying exact class

**Implementation**: FieldFactory creates field instances

```php
class FieldFactory
{
    public function create(string $type, string $name, $label = null): BaseField
    {
        return match($type) {
            'text' => new TextField($name, $label),
            'email' => new EmailField($name, $label),
            'select' => new SelectField($name, $label),
            'checkbox' => new CheckboxField($name, $label),
            default => throw new InvalidArgumentException("Unknown field type: {$type}")
        };
    }
}
```

**Usage**:
```php
$form = new FormBuilder($fieldFactory, $validationCache);

// Factory creates appropriate field type
$textField = $form->text('name', 'Name');
$emailField = $form->email('email', 'Email');
```

**Benefits**:
- Centralized object creation
- Easy to add new field types
- Consistent instantiation
- Encapsulates creation logic

---

### 2. Builder Pattern

**Purpose**: Construct complex objects step by step

**Implementation**: FormBuilder and TableBuilder

```php
class FormBuilder
{
    private array $fields = [];
    private array $tabs = [];
    private ?object $model = null;
    
    public function text(string $name, $label = null): TextField
    {
        $field = $this->fieldFactory->create('text', $name, $label);
        $this->fields[] = $field;
        return $field;
    }
    
    public function openTab(string $label): self
    {
        $this->tabs[] = ['label' => $label, 'fields' => []];
        return $this;
    }
    
    public function setModel(?object $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    public function render(): string
    {
        return $this->renderer->render($this->fields);
    }
}
```

**Usage**:
```php
$form->text('name', 'Name')
    ->required()
    ->icon('user')
    ->placeholder('Enter name');

$form->email('email', 'Email')
    ->required()
    ->icon('mail');

$html = $form->render();
```

**Benefits**:
- Fluent interface
- Step-by-step construction
- Readable code
- Flexible configuration

---

### 3. Singleton Pattern

**Purpose**: Ensure only one instance exists

**Implementation**: CacheManager

```php
class CacheManager
{
    private static ?CacheManager $instance = null;
    private array $stores = [];
    
    private function __construct() {}
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function remember(string $key, int $ttl, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        return $value;
    }
}
```

**Usage**:
```php
$cache = CacheManager::getInstance();
$data = $cache->remember('users', 3600, function() {
    return User::all();
});
```

**Benefits**:
- Single point of access
- Controlled instantiation
- Global state management
- Resource efficiency

---

## Structural Patterns

### 1. Strategy Pattern

**Purpose**: Define family of algorithms, make them interchangeable

**Implementation**: Renderers for different contexts

```php
interface RendererInterface
{
    public function render(array $data): string;
}

class AdminRenderer implements RendererInterface
{
    public function render(array $data): string
    {
        // Admin-specific rendering with full features
        return view('canvastack::admin.form', $data)->render();
    }
}

class PublicRenderer implements RendererInterface
{
    public function render(array $data): string
    {
        // Public-facing rendering with simplified UI
        return view('canvastack::public.form', $data)->render();
    }
}
```

**Usage**:
```php
class FormBuilder
{
    private RendererInterface $renderer;
    
    public function setContext(string $context): self
    {
        $this->renderer = match($context) {
            'admin' => new AdminRenderer(),
            'public' => new PublicRenderer(),
            default => new AdminRenderer()
        };
        return $this;
    }
    
    public function render(): string
    {
        return $this->renderer->render($this->fields);
    }
}
```

**Benefits**:
- Flexible rendering
- Easy to add new contexts
- Consistent interface
- Separation of concerns

---

### 2. Adapter Pattern

**Purpose**: Convert interface to another interface

**Implementation**: Cache adapters for different stores

```php
interface CacheAdapterInterface
{
    public function get(string $key);
    public function put(string $key, $value, int $ttl): bool;
    public function forget(string $key): bool;
}

class RedisCacheAdapter implements CacheAdapterInterface
{
    public function __construct(private Redis $redis) {}
    
    public function get(string $key)
    {
        return $this->redis->get($key);
    }
    
    public function put(string $key, $value, int $ttl): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }
}

class FileCacheAdapter implements CacheAdapterInterface
{
    public function get(string $key)
    {
        $path = $this->getPath($key);
        return file_exists($path) ? unserialize(file_get_contents($path)) : null;
    }
    
    public function put(string $key, $value, int $ttl): bool
    {
        return file_put_contents($this->getPath($key), serialize($value)) !== false;
    }
}
```

**Benefits**:
- Unified interface
- Easy to switch implementations
- Backward compatibility
- Flexible storage

---

### 3. Decorator Pattern

**Purpose**: Add behavior to objects dynamically

**Implementation**: Field decorators for enhanced functionality

```php
abstract class FieldDecorator extends BaseField
{
    public function __construct(protected BaseField $field) {}
}

class IconDecorator extends FieldDecorator
{
    private string $icon;
    private string $position;
    
    public function __construct(BaseField $field, string $icon, string $position = 'left')
    {
        parent::__construct($field);
        $this->icon = $icon;
        $this->position = $position;
    }
    
    public function render(): string
    {
        $html = $this->field->render();
        $iconHtml = "<i class='lucide-{$this->icon}'></i>";
        
        return $this->position === 'left' 
            ? $iconHtml . $html 
            : $html . $iconHtml;
    }
}
```

**Usage**:
```php
$field = new TextField('name', 'Name');
$field = new IconDecorator($field, 'user', 'left');
$field = new ValidationDecorator($field, ['required', 'max:255']);
```

**Benefits**:
- Add features dynamically
- Flexible composition
- Single Responsibility
- Open/Closed Principle

---

### 4. Facade Pattern

**Purpose**: Provide simplified interface to complex subsystem

**Implementation**: FormBuilder as facade

```php
class FormBuilder
{
    public function __construct(
        private FieldFactory $fieldFactory,
        private ValidationCache $validationCache,
        private RendererInterface $renderer,
        private TabManager $tabManager,
        private AjaxSyncManager $ajaxSyncManager
    ) {}
    
    // Simple interface hiding complexity
    public function text(string $name, $label = null): TextField
    {
        $field = $this->fieldFactory->create('text', $name, $label);
        $this->addField($field);
        return $field;
    }
    
    public function sync(string $source, string $target, ...): self
    {
        $this->ajaxSyncManager->register($source, $target, ...);
        return $this;
    }
}
```

**Benefits**:
- Simplified API
- Hide complexity
- Easy to use
- Consistent interface

---

## Behavioral Patterns

### 1. Observer Pattern

**Purpose**: Define one-to-many dependency between objects

**Implementation**: Event-driven cache invalidation

```php
class CacheInvalidator
{
    private array $observers = [];
    
    public function attach(CacheObserver $observer): void
    {
        $this->observers[] = $observer;
    }
    
    public function notify(string $event, array $data): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }
}

interface CacheObserver
{
    public function update(string $event, array $data): void;
}

class ValidationCacheObserver implements CacheObserver
{
    public function update(string $event, array $data): void
    {
        if ($event === 'form.updated') {
            $this->invalidateCache($data['form_id']);
        }
    }
}
```

**Usage**:
```php
$invalidator = new CacheInvalidator();
$invalidator->attach(new ValidationCacheObserver());
$invalidator->attach(new QueryCacheObserver());

// When form is updated
$invalidator->notify('form.updated', ['form_id' => 'user-form']);
```

**Benefits**:
- Loose coupling
- Dynamic relationships
- Event-driven architecture
- Flexible notifications

---

### 2. Template Method Pattern

**Purpose**: Define skeleton of algorithm, let subclasses override steps

**Implementation**: BaseField with template method

```php
abstract class BaseField
{
    // Template method
    public function render(): string
    {
        $html = $this->renderLabel();
        $html .= $this->renderInput();
        $html .= $this->renderHelp();
        $html .= $this->renderErrors();
        
        return $this->wrapField($html);
    }
    
    // Steps that can be overridden
    protected function renderLabel(): string
    {
        return "<label>{$this->label}</label>";
    }
    
    abstract protected function renderInput(): string;
    
    protected function renderHelp(): string
    {
        return $this->help ? "<span class='help'>{$this->help}</span>" : '';
    }
    
    protected function renderErrors(): string
    {
        return $this->hasErrors() ? "<span class='error'>{$this->getError()}</span>" : '';
    }
    
    protected function wrapField(string $html): string
    {
        return "<div class='form-group'>{$html}</div>";
    }
}

class TextField extends BaseField
{
    protected function renderInput(): string
    {
        return "<input type='text' name='{$this->name}' value='{$this->value}' />";
    }
}
```

**Benefits**:
- Code reuse
- Consistent structure
- Flexible customization
- Controlled extension points

---

### 3. Chain of Responsibility Pattern

**Purpose**: Pass request along chain of handlers

**Implementation**: Middleware pipeline

```php
interface Middleware
{
    public function handle($request, callable $next);
}

class AuthenticationMiddleware implements Middleware
{
    public function handle($request, callable $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }
        return $next($request);
    }
}

class AuthorizationMiddleware implements Middleware
{
    public function handle($request, callable $next)
    {
        if (!auth()->user()->can('access-admin')) {
            abort(403);
        }
        return $next($request);
    }
}

class Pipeline
{
    private array $middleware = [];
    
    public function through(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }
    
    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn($request) => $middleware->handle($request, $next),
            $destination
        );
        
        return $pipeline($this->request);
    }
}
```

**Benefits**:
- Flexible request processing
- Easy to add/remove handlers
- Separation of concerns
- Reusable middleware

---

### 4. Command Pattern

**Purpose**: Encapsulate request as object

**Implementation**: Form actions

```php
interface CommandInterface
{
    public function execute(): mixed;
}

class CreateUserCommand implements CommandInterface
{
    public function __construct(
        private array $data,
        private UserRepository $repository
    ) {}
    
    public function execute(): User
    {
        $user = new User($this->data);
        $this->repository->save($user);
        return $user;
    }
}

class CommandBus
{
    public function dispatch(CommandInterface $command): mixed
    {
        return $command->execute();
    }
}
```

**Usage**:
```php
$command = new CreateUserCommand($validatedData, $userRepository);
$user = $commandBus->dispatch($command);
```

**Benefits**:
- Encapsulated operations
- Easy to queue/log
- Undo/redo support
- Testable commands

---

## Architectural Patterns

### 1. Repository Pattern

**Purpose**: Mediate between domain and data mapping layers

**Implementation**: Data access abstraction

```php
interface RepositoryInterface
{
    public function find(int $id);
    public function all();
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

class UserRepository implements RepositoryInterface
{
    public function __construct(
        private CacheManager $cache
    ) {}
    
    public function find(int $id): ?User
    {
        return $this->cache->remember("user.{$id}", 3600, function() use ($id) {
            return User::find($id);
        });
    }
    
    public function all(): Collection
    {
        return $this->cache->remember('users.all', 3600, function() {
            return User::all();
        });
    }
}
```

**Benefits**:
- Data access abstraction
- Centralized caching
- Easy to test
- Flexible data sources

---

### 2. Service Layer Pattern

**Purpose**: Define application's boundary and operations

**Implementation**: Business logic layer

```php
class UserService
{
    public function __construct(
        private UserRepository $repository,
        private ValidationService $validator,
        private EventDispatcher $events
    ) {}
    
    public function createUser(array $data): User
    {
        // Validate
        $this->validator->validate($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);
        
        // Create user
        $user = $this->repository->create($data);
        
        // Dispatch event
        $this->events->dispatch(new UserCreated($user));
        
        return $user;
    }
}
```

**Benefits**:
- Business logic separation
- Reusable operations
- Transaction management
- Consistent API

---

### 3. Dependency Injection Pattern

**Purpose**: Inject dependencies rather than create them

**Implementation**: Constructor injection

```php
class FormBuilder
{
    public function __construct(
        private FieldFactory $fieldFactory,
        private ValidationCache $validationCache,
        private RendererInterface $renderer
    ) {}
}

// Laravel Service Container
app()->bind(FormBuilder::class, function($app) {
    return new FormBuilder(
        $app->make(FieldFactory::class),
        $app->make(ValidationCache::class),
        $app->make(AdminRenderer::class)
    );
});

// Usage
$form = app(FormBuilder::class);
```

**Benefits**:
- Loose coupling
- Easy testing
- Flexible configuration
- Inversion of control

---

## Pattern Combinations

### Form Component Architecture

```php
// Combines multiple patterns
class FormBuilder  // Facade + Builder
{
    public function __construct(
        private FieldFactory $fieldFactory,      // Factory
        private RendererInterface $renderer,     // Strategy
        private ValidationCache $cache           // Singleton
    ) {}
    
    public function text(string $name): TextField
    {
        $field = $this->fieldFactory->create('text', $name);  // Factory
        return new IconDecorator($field, 'user');              // Decorator
    }
}
```

---

## Best Practices

### 1. Choose Right Pattern

- Don't force patterns
- Solve actual problems
- Keep it simple
- Consider trade-offs

### 2. Document Pattern Usage

```php
/**
 * FormBuilder uses Facade pattern to provide simplified interface
 * to complex form building subsystem.
 * 
 * @pattern Facade
 * @pattern Builder
 */
class FormBuilder { }
```

### 3. Test Pattern Implementation

```php
class FactoryTest extends TestCase
{
    public function test_factory_creates_correct_field_type()
    {
        $factory = new FieldFactory();
        $field = $factory->create('text', 'name');
        
        $this->assertInstanceOf(TextField::class, $field);
    }
}
```

---

## Next Steps

- [Layered Architecture](layered-architecture.md) - Layer details
- [Dependency Injection](dependency-injection.md) - DI implementation
- [Overview](overview.md) - Architecture overview

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Production Ready

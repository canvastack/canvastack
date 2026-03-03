# Core Module

The Core module provides the foundation for CanvaStack's dependency injection system and service management.

## Components

### Container
A powerful dependency injection container that automatically resolves class dependencies.

**Features:**
- Automatic dependency resolution
- Singleton support
- Instance binding
- Alias support
- Method injection
- Closure binding

**Usage:**
```php
use Canvastack\Core\Container;

$container = new Container();

// Bind a class
$container->bind(UserRepository::class);

// Bind as singleton
$container->singleton(CacheManager::class);

// Bind with closure
$container->bind(DatabaseConnection::class, function ($container) {
    return new DatabaseConnection(config('database'));
});

// Resolve
$repository = $container->make(UserRepository::class);

// Create alias
$container->alias(UserRepository::class, 'users');
$repository = $container->make('users');
```

### ServiceProvider
Base class for organizing service registration and bootstrapping.

**Usage:**
```php
use Canvastack\Core\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('form', function ($app) {
            return new FormBuilder($app);
        });
    }

    public function boot(): void
    {
        // Bootstrap code here
    }
}
```

### Application
Central application instance that extends Container and manages service providers.

**Features:**
- Service provider registration
- Service provider bootstrapping
- Base path management
- Global instance access

**Usage:**
```php
use Canvastack\Core\Application;

$app = new Application(__DIR__);

// Register providers
$app->register(CoreServiceProvider::class);
$app->register(FormServiceProvider::class);

// Boot all providers
$app->boot();

// Resolve services
$form = $app->make('form');
```

## Architecture

### Dependency Injection Flow
```
Application
    ├── Container (DI Container)
    │   ├── Bindings (Service definitions)
    │   ├── Instances (Singletons)
    │   └── Aliases (Shortcuts)
    └── Service Providers
        ├── Register (Define services)
        └── Boot (Initialize services)
```

### Resolution Process
1. Check if instance exists (singleton)
2. Get concrete implementation from bindings
3. Resolve constructor dependencies recursively
4. Create instance with resolved dependencies
5. Store as singleton if needed

## Design Patterns

### Dependency Injection
Automatically injects dependencies into constructors:
```php
class UserService
{
    public function __construct(
        private UserRepository $repository,
        private CacheManager $cache
    ) {}
}

// Container automatically resolves both dependencies
$service = $container->make(UserService::class);
```

### Service Locator
Access services through the container:
```php
$form = $app->make('form');
$table = $app->make('table');
```

### Singleton Pattern
Share single instance across application:
```php
$container->singleton(CacheManager::class);

$cache1 = $container->make(CacheManager::class);
$cache2 = $container->make(CacheManager::class);

// $cache1 === $cache2 (same instance)
```

## Testing

Run unit tests:
```bash
./vendor/bin/phpunit tests/Unit/Core
```

## Performance

The container uses reflection to analyze dependencies, which has minimal overhead:
- First resolution: ~1-2ms (with reflection)
- Subsequent resolutions (singletons): ~0.01ms (cached instance)

## Best Practices

1. **Use constructor injection** for required dependencies
2. **Register singletons** for stateless services
3. **Use service providers** to organize related bindings
4. **Avoid service locator pattern** in business logic (use DI instead)
5. **Type-hint interfaces** for better testability

## Examples

### Basic Service Registration
```php
// In a service provider
public function register(): void
{
    $this->container->bind(FormBuilder::class);
    $this->container->singleton(CacheManager::class);
    $this->container->instance('config', $config);
}
```

### Interface Binding
```php
$container->bind(
    RepositoryInterface::class,
    UserRepository::class
);

// Resolves to UserRepository
$repo = $container->make(RepositoryInterface::class);
```

### Contextual Binding
```php
$container->bind(Logger::class, function ($container) {
    return new FileLogger(storage_path('logs'));
});
```

## Related Documentation

- [Service Providers](../Providers/README.md)
- [Repositories](../Repositories/README.md)
- [Architecture Overview](../../docs/architecture.md)

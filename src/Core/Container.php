<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Dependency Injection Container.
 *
 * Manages service bindings and resolves dependencies automatically.
 */
class Container
{
    /**
     * Registered bindings.
     *
     * @var array<string, array{concrete: Closure|string, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * Shared instances (singletons).
     *
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * Aliases for abstract types.
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Bind a type to the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding (singleton).
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared.
     *
     * @param string $abstract
     * @param object $instance
     * @return object
     */
    public function instance(string $abstract, object $instance): object
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve a type from the container.
     *
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // Return existing instance if it's a singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // Build the instance
        $object = $this->build($concrete, $parameters);

        // Store as singleton if needed
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for an abstract type.
     *
     * @param string $abstract
     * @return Closure|string
     */
    protected function getConcrete(string $abstract): Closure|string
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Build an instance of the given type.
     *
     * @param Closure|string $concrete
     * @param array<string, mixed> $parameters
     * @return mixed
     * @throws ReflectionException
     */
    protected function build(Closure|string $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies.
     *
     * @param array<ReflectionParameter> $parameters
     * @param array<string, mixed> $primitives
     * @return array<mixed>
     * @throws ReflectionException
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Use provided primitive if available
            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Resolve class dependency
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            // Use default value if available
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException("Unresolvable dependency [{$name}] in class.");
        }

        return $dependencies;
    }

    /**
     * Get a closure to resolve the given type.
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete, $parameters);
            }

            return $container->make($concrete, $parameters);
        };
    }

    /**
     * Get the alias for an abstract type.
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Determine if a given type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               isset($this->aliases[$abstract]);
    }

    /**
     * Call a method with dependency injection.
     *
     * @param callable|array<object|string, string> $callback
     * @param array<string, mixed> $parameters
     * @return mixed
     * @throws ReflectionException
     */
    public function call(callable|array $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$class, $method] = $callback;
            $instance = is_object($class) ? $class : $this->make($class);
            $reflector = new \ReflectionMethod($instance, $method);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

            return $reflector->invokeArgs($instance, $dependencies);
        }

        $reflector = new \ReflectionFunction($callback);
        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

        return $reflector->invokeArgs($dependencies);
    }

    /**
     * Flush the container of all bindings and instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
    }
}

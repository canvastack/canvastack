<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Core;

use Canvastack\Canvastack\Core\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function test_can_bind_and_resolve_concrete_class(): void
    {
        $this->container->bind(TestClass::class);

        $instance = $this->container->make(TestClass::class);

        $this->assertInstanceOf(TestClass::class, $instance);
    }

    public function test_can_bind_singleton(): void
    {
        $this->container->singleton(TestClass::class);

        $instance1 = $this->container->make(TestClass::class);
        $instance2 = $this->container->make(TestClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_can_bind_with_closure(): void
    {
        $this->container->bind(TestClass::class, function () {
            return new TestClass('custom');
        });

        $instance = $this->container->make(TestClass::class);

        $this->assertInstanceOf(TestClass::class, $instance);
        $this->assertEquals('custom', $instance->value);
    }

    public function test_can_resolve_dependencies(): void
    {
        $this->container->bind(TestDependency::class);
        $this->container->bind(TestClassWithDependency::class);

        $instance = $this->container->make(TestClassWithDependency::class);

        $this->assertInstanceOf(TestClassWithDependency::class, $instance);
        $this->assertInstanceOf(TestDependency::class, $instance->dependency);
    }

    public function test_can_register_instance(): void
    {
        $instance = new TestClass('test');

        $this->container->instance(TestClass::class, $instance);
        $resolved = $this->container->make(TestClass::class);

        $this->assertSame($instance, $resolved);
    }

    public function test_can_create_alias(): void
    {
        $this->container->bind(TestClass::class);
        $this->container->alias(TestClass::class, 'test');

        $instance = $this->container->make('test');

        $this->assertInstanceOf(TestClass::class, $instance);
    }

    public function test_can_check_if_bound(): void
    {
        $this->assertFalse($this->container->bound(TestClass::class));

        $this->container->bind(TestClass::class);

        $this->assertTrue($this->container->bound(TestClass::class));
    }

    public function test_can_call_method_with_dependency_injection(): void
    {
        $this->container->bind(TestDependency::class);

        $result = $this->container->call([new TestClassWithMethod(), 'methodWithDependency']);

        $this->assertInstanceOf(TestDependency::class, $result);
    }

    public function test_can_flush_container(): void
    {
        $this->container->bind(TestClass::class);
        $this->container->singleton(TestDependency::class);

        $this->assertTrue($this->container->bound(TestClass::class));

        $this->container->flush();

        $this->assertFalse($this->container->bound(TestClass::class));
        $this->assertFalse($this->container->bound(TestDependency::class));
    }
}

// Test classes
class TestClass
{
    public function __construct(public string $value = 'default')
    {
    }
}

class TestDependency
{
}

class TestClassWithDependency
{
    public function __construct(public TestDependency $dependency)
    {
    }
}

class TestClassWithMethod
{
    public function methodWithDependency(TestDependency $dependency): TestDependency
    {
        return $dependency;
    }
}

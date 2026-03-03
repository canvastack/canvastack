<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Core;

use Canvastack\Canvastack\Core\Application;
use Canvastack\Canvastack\Core\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__);
    }

    public function test_can_create_application(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function test_can_set_and_get_base_path(): void
    {
        $basePath = '/test/path';
        $this->app->setBasePath($basePath);

        $this->assertEquals($basePath, $this->app->basePath());
    }

    public function test_base_path_with_relative_path(): void
    {
        $basePath = '/test/path';
        $this->app->setBasePath($basePath);

        $expected = $basePath . DIRECTORY_SEPARATOR . 'config';
        $this->assertEquals($expected, $this->app->basePath('config'));
    }

    public function test_can_register_service_provider(): void
    {
        $provider = $this->app->register(TestServiceProvider::class);

        $this->assertInstanceOf(TestServiceProvider::class, $provider);
        $this->assertTrue($this->app->bound('test.service'));
    }

    public function test_can_boot_service_providers(): void
    {
        $provider = new TestServiceProvider($this->app);
        $this->app->register($provider);

        $this->assertFalse($this->app->hasBeenBootstrapped());

        $this->app->boot();

        $this->assertTrue($this->app->hasBeenBootstrapped());
        $this->assertTrue($provider->booted);
    }

    public function test_boot_only_runs_once(): void
    {
        $provider = new TestServiceProvider($this->app);
        $this->app->register($provider);

        $this->app->boot();
        $this->app->boot();

        $this->assertEquals(1, $provider->bootCount);
    }

    public function test_can_get_all_providers(): void
    {
        $this->app->register(TestServiceProvider::class);

        $providers = $this->app->getProviders();

        $this->assertCount(1, $providers);
        $this->assertInstanceOf(TestServiceProvider::class, $providers[0]);
    }

    public function test_application_is_globally_accessible(): void
    {
        $instance = Application::getInstance();

        $this->assertSame($this->app, $instance);
    }
}

// Test service provider
class TestServiceProvider extends ServiceProvider
{
    public bool $booted = false;

    public int $bootCount = 0;

    public function register(): void
    {
        $this->container->bind('test.service', fn () => new \stdClass());
    }

    public function boot(): void
    {
        $this->booted = true;
        $this->bootCount++;
    }
}

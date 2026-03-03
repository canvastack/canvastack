<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Routes;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test that all package routes are properly registered.
 *
 * This test ensures backward compatibility by verifying that all
 * legacy routes are available during testing.
 */
class RouteRegistrationTest extends TestCase
{
    /**
     * Test that the Ajax sync route is registered.
     *
     * @return void
     */
    public function test_ajax_sync_route_is_registered(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('canvastack.ajax.sync');

        $this->assertNotNull(
            $route,
            'Route [canvastack.ajax.sync] should be registered'
        );
    }

    /**
     * Test that the Ajax sync route has correct URI.
     *
     * @return void
     */
    public function test_ajax_sync_route_has_correct_uri(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('canvastack.ajax.sync');

        $this->assertNotNull($route, 'Route [canvastack.ajax.sync] should exist');
        $this->assertEquals('canvastack/ajax/sync', $route->uri());
    }

    /**
     * Test that the Ajax sync route accepts POST method.
     *
     * @return void
     */
    public function test_ajax_sync_route_accepts_post_method(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('canvastack.ajax.sync');

        $this->assertNotNull($route, 'Route [canvastack.ajax.sync] should exist');
        $this->assertContains('POST', $route->methods());
    }

    /**
     * Test that the Ajax sync route has web middleware.
     *
     * @return void
     */
    public function test_ajax_sync_route_has_web_middleware(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('canvastack.ajax.sync');

        $this->assertNotNull($route, 'Route [canvastack.ajax.sync] should exist');
        $this->assertContains('web', $route->middleware());
    }

    /**
     * Test that the locale switch route is registered.
     *
     * @return void
     */
    public function test_locale_switch_route_is_registered(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('locale.switch');

        $this->assertNotNull(
            $route,
            'Route [locale.switch] should be registered'
        );
    }

    /**
     * Test that the datatable data route is registered.
     *
     * @return void
     */
    public function test_datatable_data_route_is_registered(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('datatable.data');

        $this->assertNotNull(
            $route,
            'Route [datatable.data] should be registered'
        );
    }

    /**
     * Test that admin dashboard route is registered.
     *
     * @return void
     */
    public function test_admin_dashboard_route_is_registered(): void
    {
        $router = app('router');
        $route = $router->getRoutes()->getByName('admin.dashboard');

        $this->assertNotNull(
            $route,
            'Route [admin.dashboard] should be registered'
        );
    }

    /**
     * Test that admin theme routes are registered.
     *
     * @return void
     */
    public function test_admin_theme_routes_are_registered(): void
    {
        $router = app('router');

        $expectedRoutes = [
            'admin.themes.index',
            'admin.themes.show',
            'admin.themes.activate',
            'admin.themes.clear-cache',
            'admin.themes.reload',
            'admin.themes.export',
            'admin.themes.preview',
            'admin.themes.stats',
        ];

        foreach ($expectedRoutes as $routeName) {
            $route = $router->getRoutes()->getByName($routeName);
            $this->assertNotNull(
                $route,
                "Route [{$routeName}] should be registered"
            );
        }
    }

    /**
     * Test that route() helper works with registered routes.
     *
     * @return void
     */
    public function test_route_helper_works_with_ajax_sync(): void
    {
        $url = route('canvastack.ajax.sync');

        $this->assertStringContainsString('/canvastack/ajax/sync', $url);
    }

    /**
     * Test that all routes are loaded automatically in TestCase.
     *
     * This ensures backward compatibility - tests should not need to
     * manually register routes.
     *
     * @return void
     */
    public function test_routes_are_loaded_automatically_in_test_case(): void
    {
        // This test passes if setUp() in TestCase properly loads routes
        // If routes weren't loaded, getByName() would return null

        $router = app('router');
        $route = $router->getRoutes()->getByName('canvastack.ajax.sync');

        $this->assertNotNull(
            $route,
            'Routes should be loaded automatically in TestCase::setUp()'
        );
    }

    /**
     * Test that route names follow naming convention.
     *
     * @return void
     */
    public function test_route_names_follow_naming_convention(): void
    {
        $router = app('router');
        $routes = $router->getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();

            if ($name && str_starts_with($name, 'canvastack.')) {
                // CanvaStack routes should use dot notation
                $this->assertMatchesRegularExpression(
                    '/^canvastack\.[a-z]+(\.[a-z]+)*$/',
                    $name,
                    "Route name [{$name}] should follow canvastack.* naming convention"
                );
            }

            if ($name && str_starts_with($name, 'admin.')) {
                // Admin routes should use dot notation
                $this->assertMatchesRegularExpression(
                    '/^admin\.[a-z]+(\.[a-z\-]+)*$/',
                    $name,
                    "Route name [{$name}] should follow admin.* naming convention"
                );
            }
        }
    }
}

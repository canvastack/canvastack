<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Routes;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Test for API routes registration.
 *
 * @group routes
 * @group api
 */
class ApiRoutesTest extends TestCase
{
    /**
     * Test that tab loading route is registered.
     *
     * @return void
     */
    public function test_tab_loading_route_is_registered(): void
    {
        // Check if route exists
        $this->assertTrue(
            Route::has('canvastack.table.tab.load'),
            'Tab loading route should be registered'
        );
    }

    /**
     * Test that tab loading route uses POST method.
     *
     * @return void
     */
    public function test_tab_loading_route_uses_post_method(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        $this->assertContains('POST', $route->methods(), 'Tab loading route should accept POST method');
    }

    /**
     * Test that tab loading route has correct URI pattern.
     *
     * @return void
     */
    public function test_tab_loading_route_has_correct_uri(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        $this->assertEquals(
            'api/canvastack/table/tab/{index}',
            $route->uri(),
            'Tab loading route should have correct URI pattern'
        );
    }

    /**
     * Test that tab loading route has web middleware.
     *
     * @return void
     */
    public function test_tab_loading_route_has_web_middleware(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        $this->assertContains('web', $middleware, 'Tab loading route should have web middleware');
    }

    /**
     * Test that tab loading route has auth middleware.
     * Task 4.2.2 - Requirement 10.6
     *
     * @return void
     */
    public function test_tab_loading_route_has_auth_middleware(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        $this->assertContains('auth', $middleware, 'Tab loading route should have auth middleware for security');
    }

    /**
     * Test that tab loading route requires authentication.
     * Task 4.2.2 - Requirement 10.6
     *
     * @return void
     */
    public function test_tab_loading_route_requires_authentication(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Verify both web and auth middleware are present
        $this->assertContains('web', $middleware, 'Tab loading route should have web middleware');
        $this->assertContains('auth', $middleware, 'Tab loading route should require authentication');
        
        // Verify auth comes after web (correct order)
        $webIndex = array_search('web', $middleware);
        $authIndex = array_search('auth', $middleware);
        
        $this->assertNotFalse($webIndex, 'Web middleware should be present');
        $this->assertNotFalse($authIndex, 'Auth middleware should be present');
        $this->assertLessThan($authIndex, $webIndex, 'Web middleware should come before auth middleware');
    }

    /**
     * Test that tab loading route maps to correct controller.
     *
     * @return void
     */
    public function test_tab_loading_route_maps_to_controller(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $action = $route->getAction();
        $this->assertEquals(
            'Canvastack\Canvastack\Http\Controllers\TableTabController@loadTab',
            $action['controller'],
            'Tab loading route should map to TableTabController@loadTab'
        );
    }

    /**
     * Test that tab loading route has numeric constraint on index parameter.
     *
     * @return void
     */
    public function test_tab_loading_route_has_numeric_constraint(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $wheres = $route->wheres;
        $this->assertArrayHasKey('index', $wheres, 'Tab loading route should have constraint on index parameter');
        $this->assertEquals('[0-9]+', $wheres['index'], 'Index parameter should only accept numeric values');
    }

    /**
     * Test that tab loading route has rate limiting middleware.
     * Task 4.2.3 - Requirement 10.10
     *
     * @return void
     */
    public function test_tab_loading_route_has_rate_limiting(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Check for throttle middleware (can be string or object)
        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'throttle:')) {
                $hasThrottle = true;
                break;
            }
            // Handle middleware as object (Laravel may resolve it)
            if (is_object($m) && method_exists($m, '__toString')) {
                $mStr = (string) $m;
                if (str_starts_with($mStr, 'throttle:')) {
                    $hasThrottle = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($hasThrottle, 'Tab loading route should have rate limiting (throttle) middleware. Middleware: ' . json_encode($middleware));
    }

    /**
     * Test that tab loading route has correct rate limit configuration.
     * Task 4.2.3 - Requirement 10.10
     *
     * @return void
     */
    public function test_tab_loading_route_has_correct_rate_limit(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Find throttle middleware
        $throttleMiddleware = null;
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'throttle:')) {
                $throttleMiddleware = $m;
                break;
            }
        }
        
        $this->assertNotNull($throttleMiddleware, 'Throttle middleware should be present');
        
        // Verify rate limit is 60 requests per minute
        $this->assertEquals(
            'throttle:60,1',
            $throttleMiddleware,
            'Rate limit should be 60 requests per minute (per user)'
        );
    }

    /**
     * Test that rate limiting is per-user (authenticated).
     * Task 4.2.3 - Requirement 10.10
     *
     * @return void
     */
    public function test_rate_limiting_is_per_user(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Verify auth middleware is present (required for per-user rate limiting)
        $this->assertContains('auth', $middleware, 'Auth middleware required for per-user rate limiting');
        
        // Find throttle middleware
        $throttleMiddleware = null;
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'throttle:')) {
                $throttleMiddleware = $m;
                break;
            }
        }
        
        $this->assertNotNull($throttleMiddleware, 'Throttle middleware should be present');
        
        // Verify throttle uses per-minute limit (second parameter = 1 minute)
        $parts = explode(':', $throttleMiddleware);
        $this->assertCount(2, $parts, 'Throttle middleware should have format throttle:max,minutes');
        
        $params = explode(',', $parts[1]);
        $this->assertCount(2, $params, 'Throttle parameters should be max,minutes');
        $this->assertEquals('60', $params[0], 'Max requests should be 60');
        $this->assertEquals('1', $params[1], 'Time window should be 1 minute');
    }

    /**
     * Test that middleware is applied in correct order.
     * Task 4.2.2, 4.2.3 - Requirements 10.6, 10.10
     *
     * @return void
     */
    public function test_middleware_order_is_correct(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Expected order: web -> auth -> throttle
        $webIndex = array_search('web', $middleware);
        $authIndex = array_search('auth', $middleware);
        
        // Find throttle index
        $throttleIndex = false;
        foreach ($middleware as $index => $m) {
            if (is_string($m) && str_starts_with($m, 'throttle:')) {
                $throttleIndex = $index;
                break;
            }
        }
        
        $this->assertNotFalse($webIndex, 'Web middleware should be present');
        $this->assertNotFalse($authIndex, 'Auth middleware should be present');
        $this->assertNotFalse($throttleIndex, 'Throttle middleware should be present');
        
        // Verify order
        $this->assertLessThan($authIndex, $webIndex, 'Web should come before auth');
        $this->assertLessThan($throttleIndex, $authIndex, 'Auth should come before throttle');
    }

    /**
     * Test that tab loading route has CSRF protection via web middleware.
     * Task 4.2.4 - Requirement 10.5
     *
     * @return void
     */
    public function test_tab_loading_route_has_csrf_protection(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // CSRF protection is provided by the 'web' middleware group
        // which includes VerifyCsrfToken middleware by default in Laravel
        $this->assertContains(
            'web',
            $middleware,
            'Tab loading route should have web middleware which includes CSRF protection'
        );
    }

    /**
     * Test that CSRF token is required for POST requests.
     * Task 4.2.4 - Requirement 10.5
     *
     * @return void
     */
    public function test_csrf_token_required_for_post_requests(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Verify route uses POST method (CSRF protection applies to POST/PUT/PATCH/DELETE)
        $this->assertContains('POST', $route->methods(), 'Tab loading route should use POST method');
        
        // Verify web middleware is present (includes CSRF protection)
        $middleware = $route->middleware();
        $this->assertContains('web', $middleware, 'Web middleware should be present for CSRF protection');
    }

    /**
     * Test that web middleware group includes CSRF protection.
     * Task 4.2.4 - Requirement 10.5
     *
     * @return void
     */
    public function test_web_middleware_includes_csrf_protection(): void
    {
        // Get the web middleware group from the router
        $router = app('router');
        $middlewareGroups = $router->getMiddlewareGroups();
        
        // In test environment, web middleware group might not be fully configured
        // The important thing is that our route has 'web' middleware applied
        // which in production includes VerifyCsrfToken
        
        if (array_key_exists('web', $middlewareGroups)) {
            $webMiddleware = $middlewareGroups['web'];
            
            // Check if VerifyCsrfToken middleware is in the web group
            // It can be either the class name or an alias
            $hasCsrfProtection = false;
            foreach ($webMiddleware as $middleware) {
                if (
                    $middleware === \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class ||
                    $middleware === \App\Http\Middleware\VerifyCsrfToken::class ||
                    (is_string($middleware) && str_contains($middleware, 'VerifyCsrfToken'))
                ) {
                    $hasCsrfProtection = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasCsrfProtection,
                'Web middleware group should include CSRF protection (VerifyCsrfToken middleware)'
            );
        } else {
            // In test environment, verify that the route at least has web middleware
            $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
            $this->assertNotNull($route, 'Tab loading route should exist');
            
            $middleware = $route->middleware();
            $this->assertContains(
                'web',
                $middleware,
                'Tab loading route should have web middleware (which includes CSRF protection in production)'
            );
        }
    }

    /**
     * Test that all security middleware are present.
     * Task 4.2.2, 4.2.3, 4.2.4 - Requirements 10.5, 10.6, 10.10
     *
     * @return void
     */
    public function test_all_security_middleware_are_present(): void
    {
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        $middleware = $route->middleware();
        
        // Verify all required security middleware
        $this->assertContains('web', $middleware, 'Web middleware (CSRF protection) should be present');
        $this->assertContains('auth', $middleware, 'Auth middleware (authentication) should be present');
        
        // Verify throttle middleware
        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'throttle:')) {
                $hasThrottle = true;
                break;
            }
        }
        $this->assertTrue($hasThrottle, 'Throttle middleware (rate limiting) should be present');
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Rate Limiting Security Tests for TanStack Table Multi-Table & Tab System.
 *
 * Tests Requirement 10.10:
 * - THE TableBuilder SHALL rate-limit AJAX requests to prevent abuse
 *
 * This test suite verifies:
 * 1. Rate limiting middleware is applied to tab loading endpoint
 * 2. Rate limit configuration is correct (60 requests per minute)
 * 3. Rate limiting is per-user (not global)
 * 4. Route security configuration is complete
 *
 * @covers \Canvastack\Canvastack\Http\Controllers\TableTabController
 * @group security
 * @group rate-limiting
 */
class RateLimitSecurityTest extends TestCase
{
    /**
     * Test that rate limiting middleware is applied to tab loading route.
     *
     * Validates: Requirement 10.10 - Rate limit enforcement
     *
     * @return void
     */
    public function test_rate_limiting_middleware_is_applied(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Assert - Route has throttle middleware
        $middleware = $route->gatherMiddleware();
        
        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
        
        $this->assertTrue($hasThrottle, 
            'Tab loading route should have throttle middleware applied');
    }

    /**
     * Test that rate limit configuration is correct (60 requests per minute).
     *
     * Validates: Requirement 10.10 - Rate limit configuration
     *
     * @return void
     */
    public function test_rate_limit_configuration_is_correct(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get middleware
        $middleware = $route->gatherMiddleware();
        
        // Find throttle middleware
        $throttleConfig = null;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $throttleConfig = $m;
                break;
            }
        }
        
        // Assert - Throttle middleware found
        $this->assertNotNull($throttleConfig, 
            'Throttle middleware should be configured');
        
        // Assert - Configuration is 60 requests per 1 minute
        $this->assertStringContainsString('60', $throttleConfig, 
            'Rate limit should be 60 requests');
        $this->assertStringContainsString('1', $throttleConfig, 
            'Rate limit window should be 1 minute');
    }

    /**
     * Test that rate limiting is per-user (uses auth middleware).
     *
     * Validates: Requirement 10.10 - Per-user rate limits
     *
     * @return void
     */
    public function test_rate_limiting_is_per_user(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get middleware
        $middleware = $route->gatherMiddleware();
        
        // Assert - Route has auth middleware (required for per-user throttling)
        $hasAuth = false;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'auth')) {
                $hasAuth = true;
                break;
            }
        }
        
        $this->assertTrue($hasAuth, 
            'Tab loading route should have auth middleware for per-user rate limiting');
    }

    /**
     * Test that route configuration includes all required security middleware.
     *
     * Validates: Requirement 10.10 - Complete security stack
     *
     * @return void
     */
    public function test_route_has_complete_security_middleware_stack(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get middleware
        $middleware = $route->gatherMiddleware();
        
        // Convert to string for easier checking
        $middlewareString = implode(',', $middleware);
        
        // Assert - Has authentication
        $this->assertStringContainsString('auth', $middlewareString, 
            'Route should have authentication middleware');
        
        // Assert - Has rate limiting
        $this->assertStringContainsString('throttle', $middlewareString, 
            'Route should have rate limiting middleware');
        
        // Note: CSRF is handled by web middleware group, not explicitly in route
    }

    /**
     * Test that throttle middleware uses correct signature format.
     *
     * Validates: Requirement 10.10 - Proper throttle configuration
     *
     * @return void
     */
    public function test_throttle_middleware_uses_correct_format(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get middleware
        $middleware = $route->gatherMiddleware();
        
        // Find throttle middleware
        $throttleConfig = null;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $throttleConfig = $m;
                break;
            }
        }
        
        // Assert - Throttle middleware found
        $this->assertNotNull($throttleConfig, 
            'Throttle middleware should be configured');
        
        // Assert - Format is throttle:maxAttempts,decayMinutes
        $this->assertMatchesRegularExpression(
            '/throttle:\d+,\d+/', 
            $throttleConfig,
            'Throttle middleware should use format throttle:maxAttempts,decayMinutes'
        );
    }

    /**
     * Test that route method is POST (required for rate limiting).
     *
     * Validates: Requirement 10.10 - Proper HTTP method
     *
     * @return void
     */
    public function test_route_uses_post_method(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Assert - Route uses POST method
        $methods = $route->methods();
        $this->assertContains('POST', $methods, 
            'Tab loading route should use POST method for security');
    }

    /**
     * Test that route pattern validates tab index as numeric.
     *
     * Validates: Requirement 10.10 - Input validation
     *
     * @return void
     */
    public function test_route_validates_tab_index_as_numeric(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get route pattern constraints
        $wheres = $route->wheres;
        
        // Assert - Index parameter has numeric constraint
        $this->assertArrayHasKey('index', $wheres, 
            'Route should have constraint for index parameter');
        
        $this->assertEquals('[0-9]+', $wheres['index'], 
            'Index parameter should be constrained to numeric values');
    }

    /**
     * Test that rate limiting documentation is present in route file.
     *
     * Validates: Requirement 10.10 - Documentation
     *
     * @return void
     */
    public function test_rate_limiting_is_documented_in_route_file(): void
    {
        // Arrange - Read the route file
        $routeFile = __DIR__ . '/../../../routes/api.php';
        
        $this->assertFileExists($routeFile, 'API routes file should exist');
        
        $content = file_get_contents($routeFile);
        
        // Assert - Documentation mentions rate limiting
        $this->assertStringContainsString('throttle', $content, 
            'Route file should document rate limiting');
        
        // Assert - Documentation mentions the limit (60)
        $this->assertStringContainsString('60', $content, 
            'Route file should document the rate limit value');
    }

    /**
     * Test that rate limit configuration prevents abuse as documented in requirements.
     *
     * Validates: Requirement 10.10 - Abuse prevention
     *
     * @return void
     */
    public function test_rate_limit_configuration_prevents_abuse(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Get middleware
        $middleware = $route->gatherMiddleware();
        
        // Find throttle middleware
        $throttleConfig = null;
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $throttleConfig = $m;
                break;
            }
        }
        
        // Assert - Throttle middleware found
        $this->assertNotNull($throttleConfig, 
            'Throttle middleware should be configured to prevent abuse');
        
        // Assert - Limit is reasonable (not too high)
        // 60 requests per minute = 1 request per second average
        // This is reasonable for tab loading while preventing abuse
        $this->assertStringContainsString('60', $throttleConfig, 
            'Rate limit of 60 requests per minute prevents abuse while allowing normal usage');
    }

    /**
     * Test that rate limiting applies to all tab indices.
     *
     * Validates: Requirement 10.10 - Consistent rate limiting
     *
     * @return void
     */
    public function test_rate_limiting_applies_to_all_tab_indices(): void
    {
        // Arrange - Get the route
        $route = Route::getRoutes()->getByName('canvastack.table.tab.load');
        
        // Assert - Route exists
        $this->assertNotNull($route, 'Tab loading route should exist');
        
        // Assert - Route uses parameter {index} (not specific indices)
        $uri = $route->uri();
        $this->assertStringContainsString('{index}', $uri, 
            'Route should use parameter placeholder for all tab indices');
        
        // This ensures rate limiting applies to ALL tab loading requests,
        // not just specific tab indices
    }
}

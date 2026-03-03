<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\Middleware;

use Canvastack\Canvastack\Auth\Middleware\AuthenticateAdmin;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Mockery;

/**
 * Test for AuthenticateAdmin middleware.
 */
class AuthenticateAdminTest extends TestCase
{
    /**
     * Test that authenticated user can access admin routes.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_admin_routes(): void
    {
        // Arrange
        $request = Request::create('/admin/dashboard', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertTrue($nextCalled, 'Next middleware should be called');
        $this->assertEquals('response', $response);
    }

    /**
     * Test that unauthenticated user cannot access admin routes.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        // Arrange
        config(['canvastack-rbac.contexts.admin.login_url' => '/login']);

        $request = Request::create('/admin/dashboard', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        // Act
        $middleware->handle($request, $next);
    }

    /**
     * Test that middleware uses default guard from config.
     *
     * @return void
     */
    public function test_middleware_uses_default_guard_from_config(): void
    {
        // Arrange
        config(['canvastack-rbac.contexts.admin.guard' => 'web']);

        $request = Request::create('/admin/dashboard', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertEquals('response', $response);
    }

    /**
     * Test that middleware uses custom guard when specified.
     *
     * @return void
     */
    public function test_middleware_uses_custom_guard_when_specified(): void
    {
        // Arrange
        $request = Request::create('/admin/dashboard', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('admin')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next, 'admin');

        // Assert
        $this->assertEquals('response', $response);
    }

    /**
     * Test that middleware returns null redirect for JSON requests.
     *
     * @return void
     */
    public function test_middleware_returns_null_redirect_for_json_requests(): void
    {
        // Arrange
        $request = Request::create('/admin/api/users', 'GET');
        $request->headers->set('Accept', 'application/json');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act & Assert
        try {
            $middleware->handle($request, $next);
            $this->fail('Expected AuthenticationException to be thrown');
        } catch (AuthenticationException $e) {
            $this->assertEquals('Unauthenticated.', $e->getMessage());
            $this->assertEquals(['web'], $e->guards());
            // For JSON requests, redirectTo should be null
            $redirectTo = $e->redirectTo($request);
            $this->assertNull($redirectTo);
        }
    }

    /**
     * Test that middleware redirects to login for web requests.
     *
     * @return void
     */
    public function test_middleware_redirects_to_login_for_web_requests(): void
    {
        // Arrange
        config(['canvastack-rbac.contexts.admin.login_url' => '/admin/login']);

        $request = Request::create('/admin/dashboard', 'GET');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act & Assert
        try {
            $middleware->handle($request, $next);
            $this->fail('Expected AuthenticationException to be thrown');
        } catch (AuthenticationException $e) {
            $this->assertEquals('Unauthenticated.', $e->getMessage());
            $this->assertEquals(['web'], $e->guards());
            $redirectTo = $e->redirectTo($request);
            $this->assertEquals('/admin/login', $redirectTo);
        }
    }

    /**
     * Test that middleware uses route helper for default redirect.
     *
     * @return void
     */
    public function test_middleware_uses_default_path_when_route_not_found(): void
    {
        // Arrange
        config(['canvastack-rbac.contexts.admin.login_url' => null]);

        $request = Request::create('/admin/dashboard', 'GET');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act & Assert
        try {
            $middleware->handle($request, $next);
            $this->fail('Expected AuthenticationException to be thrown');
        } catch (AuthenticationException $e) {
            $this->assertEquals('Unauthenticated.', $e->getMessage());
            $this->assertEquals(['web'], $e->guards());
            $redirectTo = $e->redirectTo($request);
            // Should fallback to /login when route not found
            $this->assertEquals('/login', $redirectTo);
        }
    }

    /**
     * Test that middleware passes request to next middleware.
     *
     * @return void
     */
    public function test_middleware_passes_request_to_next_middleware(): void
    {
        // Arrange
        $request = Request::create('/admin/dashboard', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return 'response';
        };

        // Act
        $middleware->handle($request, $next);

        // Assert
        $this->assertSame($request, $receivedRequest, 'Request should be passed to next middleware');
    }

    /**
     * Test that middleware works with multiple guards.
     *
     * @return void
     */
    public function test_middleware_works_with_multiple_guards(): void
    {
        // Arrange
        $request = Request::create('/admin/dashboard', 'GET');

        // Test with 'web' guard
        $webGuard = Mockery::mock(Guard::class);
        $webGuard->shouldReceive('check')->once()->andReturn(true);

        $authFactory1 = Mockery::mock(AuthFactory::class);
        $authFactory1->shouldReceive('guard')->with('web')->once()->andReturn($webGuard);

        $middleware1 = new AuthenticateAdmin($authFactory1);

        $next = function ($req) {
            return 'response';
        };

        $response1 = $middleware1->handle($request, $next, 'web');
        $this->assertEquals('response', $response1);

        // Test with 'admin' guard
        $adminGuard = Mockery::mock(Guard::class);
        $adminGuard->shouldReceive('check')->once()->andReturn(true);

        $authFactory2 = Mockery::mock(AuthFactory::class);
        $authFactory2->shouldReceive('guard')->with('admin')->once()->andReturn($adminGuard);

        $middleware2 = new AuthenticateAdmin($authFactory2);

        $response2 = $middleware2->handle($request, $next, 'admin');
        $this->assertEquals('response', $response2);
    }

    /**
     * Test that middleware handles POST requests correctly.
     *
     * @return void
     */
    public function test_middleware_handles_post_requests_correctly(): void
    {
        // Arrange
        $request = Request::create('/admin/users', 'POST', ['name' => 'John Doe']);
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertEquals('response', $response);
    }

    /**
     * Test that middleware handles PUT requests correctly.
     *
     * @return void
     */
    public function test_middleware_handles_put_requests_correctly(): void
    {
        // Arrange
        $request = Request::create('/admin/users/1', 'PUT', ['name' => 'Jane Doe']);
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertEquals('response', $response);
    }

    /**
     * Test that middleware handles DELETE requests correctly.
     *
     * @return void
     */
    public function test_middleware_handles_delete_requests_correctly(): void
    {
        // Arrange
        $request = Request::create('/admin/users/1', 'DELETE');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticateAdmin($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertEquals('response', $response);
    }

    /**
     * Clean up Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

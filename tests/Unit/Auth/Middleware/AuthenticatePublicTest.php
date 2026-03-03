<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\Middleware;

use Canvastack\Canvastack\Auth\Middleware\AuthenticatePublic;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Mockery;

/**
 * Test for AuthenticatePublic middleware.
 */
class AuthenticatePublicTest extends TestCase
{
    /**
     * Test that authenticated user can access public routes.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_public_routes(): void
    {
        // Arrange
        $request = Request::create('/profile', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
     * Test that unauthenticated user cannot access public routes.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_public_routes(): void
    {
        // Arrange
        config(['canvastack-rbac.contexts.public.login_url' => '/login']);

        $request = Request::create('/profile', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        config(['canvastack-rbac.contexts.public.guard' => 'web']);

        $request = Request::create('/profile', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        $request = Request::create('/profile', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('public')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

        $next = function ($req) {
            return 'response';
        };

        // Act
        $response = $middleware->handle($request, $next, 'public');

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
        $request = Request::create('/api/profile', 'GET');
        $request->headers->set('Accept', 'application/json');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        config(['canvastack-rbac.contexts.public.login_url' => '/login']);

        $request = Request::create('/profile', 'GET');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
            $this->assertEquals('/login', $redirectTo);
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
        config(['canvastack-rbac.contexts.public.login_url' => null]);

        $request = Request::create('/profile', 'GET');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(false);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        $request = Request::create('/profile', 'GET');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        $request = Request::create('/profile', 'GET');

        // Test with 'web' guard
        $webGuard = Mockery::mock(Guard::class);
        $webGuard->shouldReceive('check')->once()->andReturn(true);

        $authFactory1 = Mockery::mock(AuthFactory::class);
        $authFactory1->shouldReceive('guard')->with('web')->once()->andReturn($webGuard);

        $middleware1 = new AuthenticatePublic($authFactory1);

        $next = function ($req) {
            return 'response';
        };

        $response1 = $middleware1->handle($request, $next, 'web');
        $this->assertEquals('response', $response1);

        // Test with 'public' guard
        $publicGuard = Mockery::mock(Guard::class);
        $publicGuard->shouldReceive('check')->once()->andReturn(true);

        $authFactory2 = Mockery::mock(AuthFactory::class);
        $authFactory2->shouldReceive('guard')->with('public')->once()->andReturn($publicGuard);

        $middleware2 = new AuthenticatePublic($authFactory2);

        $response2 = $middleware2->handle($request, $next, 'public');
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
        $request = Request::create('/profile', 'POST', ['name' => 'John Doe']);
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        $request = Request::create('/profile', 'PUT', ['name' => 'Jane Doe']);
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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
        $request = Request::create('/profile', 'DELETE');
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturn(true);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('web')->once()->andReturn($guard);

        $middleware = new AuthenticatePublic($authFactory);

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

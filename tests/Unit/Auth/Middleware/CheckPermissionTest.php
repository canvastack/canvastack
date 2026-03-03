<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\Middleware;

use Canvastack\Canvastack\Auth\Middleware\CheckPermission;
use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Mockery;

/**
 * Test for CheckPermission middleware.
 */
class CheckPermissionTest extends TestCase
{
    /**
     * Test that middleware allows access when user has required permission.
     *
     * @return void
     */
    public function test_allows_access_when_user_has_permission(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'users.view');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware denies access when user lacks permission.
     *
     * @return void
     */
    public function test_denies_access_when_user_lacks_permission(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.delete')
            ->once()
            ->andReturn(false);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to access this resource. Required permission: users.delete');

        // Act
        $middleware->handle($request, $next, 'users.delete');
    }

    /**
     * Test that middleware throws exception when user is not authenticated.
     *
     * @return void
     */
    public function test_throws_exception_when_user_not_authenticated(): void
    {
        // Arrange
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn(null);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        // Act
        $middleware->handle($request, $next, 'users.view');
    }

    /**
     * Test that middleware allows access when user has any of the required permissions (OR logic).
     *
     * @return void
     */
    public function test_allows_access_with_or_logic_when_user_has_any_permission(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(false);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'users.view,users.edit', 'or');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware denies access with OR logic when user has none of the permissions.
     *
     * @return void
     */
    public function test_denies_access_with_or_logic_when_user_has_no_permissions(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(false);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(false);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to access this resource. Required permissions (any): users.view, users.edit');

        // Act
        $middleware->handle($request, $next, 'users.view,users.edit', 'or');
    }

    /**
     * Test that middleware allows access with AND logic when user has all permissions.
     *
     * @return void
     */
    public function test_allows_access_with_and_logic_when_user_has_all_permissions(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'users.view,users.edit', 'and');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware denies access with AND logic when user lacks any permission.
     *
     * @return void
     */
    public function test_denies_access_with_and_logic_when_user_lacks_any_permission(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(false);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to access this resource. Required permissions (all): users.view, users.edit');

        // Act
        $middleware->handle($request, $next, 'users.view,users.edit', 'and');
    }

    /**
     * Test that middleware uses context-aware permission check when context is provided.
     *
     * @return void
     */
    public function test_uses_context_aware_check_when_context_provided(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('setContext')
            ->with('admin')
            ->once()
            ->andReturnSelf();

        $gate->shouldReceive('allowsInContext')
            ->with($user, 'users.view', 'admin')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'users.view', 'and', 'admin');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware uses custom guard when provided.
     *
     * @return void
     */
    public function test_uses_custom_guard_when_provided(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with('api')
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'users.view', 'and', null, 'api');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware throws exception for invalid logic operator.
     *
     * @return void
     */
    public function test_throws_exception_for_invalid_logic_operator(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid logic operator: invalid. Use 'and' or 'or'.");

        // Act
        $middleware->handle($request, $next, 'users.view', 'invalid');
    }

    /**
     * Test that middleware handles multiple permissions with spaces correctly.
     *
     * @return void
     */
    public function test_handles_permissions_with_spaces_correctly(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act - permissions with spaces around commas
        $result = $middleware->handle($request, $next, 'users.view , users.edit', 'and');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware works with context and OR logic together.
     *
     * @return void
     */
    public function test_works_with_context_and_or_logic(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('setContext')
            ->with('public')
            ->once()
            ->andReturnSelf();

        $gate->shouldReceive('allowsInContext')
            ->with($user, 'posts.view', 'public')
            ->once()
            ->andReturn(false);

        $gate->shouldReceive('allowsInContext')
            ->with($user, 'posts.create', 'public')
            ->once()
            ->andReturn(true);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Act
        $result = $middleware->handle($request, $next, 'posts.view,posts.create', 'or', 'public');

        // Assert
        $this->assertEquals('success', $result);
    }

    /**
     * Test that middleware defaults to AND logic when not specified.
     *
     * @return void
     */
    public function test_defaults_to_and_logic(): void
    {
        // Arrange
        $user = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);
        $authFactory = Mockery::mock(AuthFactory::class);
        $gate = Mockery::mock(Gate::class);

        $authFactory->shouldReceive('guard')
            ->with(null)
            ->once()
            ->andReturn($guard);

        $guard->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.view')
            ->once()
            ->andReturn(true);

        $gate->shouldReceive('hasPermission')
            ->with($user, 'users.edit')
            ->once()
            ->andReturn(false);

        $middleware = new CheckPermission($authFactory, $gate);
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return 'success';
        };

        // Assert - should fail because AND logic requires all permissions
        $this->expectException(AuthorizationException::class);

        // Act - not specifying logic parameter (defaults to 'and')
        $middleware->handle($request, $next, 'users.view,users.edit');
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

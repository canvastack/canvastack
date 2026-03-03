<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\Middleware;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

/**
 * Check Permission Middleware.
 *
 * Ensures that the authenticated user has the required permission(s) to access a route.
 * Supports multiple permissions with AND/OR logic, context-aware checks, and custom error messages.
 */
class CheckPermission
{
    /**
     * The authentication factory instance.
     *
     * @var AuthFactory
     */
    protected AuthFactory $auth;

    /**
     * The gate instance.
     *
     * @var Gate
     */
    protected Gate $gate;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AuthFactory $auth, Gate $gate)
    {
        $this->auth = $auth;
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permissions Comma-separated permissions (e.g., 'users.view' or 'users.view,users.edit')
     * @param string $logic Logic operator: 'and' (all required) or 'or' (any required). Default: 'and'
     * @param string|null $context Context name (admin, public, api). Default: null (no context)
     * @param string|null $guard Guard name. Default: null (use default guard)
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permissions,
        string $logic = 'and',
        ?string $context = null,
        ?string $guard = null
    ): mixed {
        // Get authenticated user
        $user = $this->auth->guard($guard)->user();

        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        // Parse permissions
        $permissionList = $this->parsePermissions($permissions);

        // Set context if provided
        if ($context !== null) {
            $this->gate->setContext($context);
        }

        // Check permissions based on logic
        $hasPermission = match (strtolower($logic)) {
            'or' => $this->checkAnyPermission($user, $permissionList, $context),
            'and' => $this->checkAllPermissions($user, $permissionList, $context),
            default => throw new \InvalidArgumentException("Invalid logic operator: {$logic}. Use 'and' or 'or'."),
        };

        if (!$hasPermission) {
            $this->throwAuthorizationException($permissionList, $logic);
        }

        return $next($request);
    }

    /**
     * Parse permissions string into array.
     *
     * @param string $permissions Comma-separated permissions
     * @return array<string>
     */
    protected function parsePermissions(string $permissions): array
    {
        return array_map('trim', explode(',', $permissions));
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array<string> $permissions
     * @param string|null $context
     * @return bool
     */
    protected function checkAnyPermission($user, array $permissions, ?string $context): bool
    {
        foreach ($permissions as $permission) {
            if ($this->checkSinglePermission($user, $permission, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array<string> $permissions
     * @param string|null $context
     * @return bool
     */
    protected function checkAllPermissions($user, array $permissions, ?string $context): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->checkSinglePermission($user, $permission, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check a single permission.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $permission
     * @param string|null $context
     * @return bool
     */
    protected function checkSinglePermission($user, string $permission, ?string $context): bool
    {
        if ($context !== null) {
            return $this->gate->allowsInContext($user, $permission, $context);
        }

        return $this->gate->hasPermission($user, $permission);
    }

    /**
     * Throw authorization exception with appropriate message.
     *
     * @param array<string> $permissions
     * @param string $logic
     * @return void
     * @throws AuthorizationException
     */
    protected function throwAuthorizationException(array $permissions, string $logic): void
    {
        if (count($permissions) === 1) {
            $message = "You do not have permission to access this resource. Required permission: {$permissions[0]}";
        } elseif ($logic === 'or') {
            $message = 'You do not have permission to access this resource. Required permissions (any): ' . implode(', ', $permissions);
        } else {
            $message = 'You do not have permission to access this resource. Required permissions (all): ' . implode(', ', $permissions);
        }

        throw new AuthorizationException($message);
    }
}

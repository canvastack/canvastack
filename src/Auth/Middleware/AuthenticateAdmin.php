<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

/**
 * Authenticate Admin Middleware.
 *
 * Ensures that the user is authenticated for admin context.
 * Redirects unauthenticated users to the login page.
 */
class AuthenticateAdmin
{
    /**
     * The authentication factory instance.
     *
     * @var AuthFactory
     */
    protected AuthFactory $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        // Get guard from config if not specified
        if ($guard === null) {
            $guard = config('canvastack-rbac.contexts.admin.guard', 'web');
        }

        // Check if user is authenticated
        if (!$this->auth->guard($guard)->check()) {
            throw new AuthenticationException(
                'Unauthenticated.',
                [$guard],
                $this->redirectTo($request)
            );
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // Don't redirect for JSON requests
        if ($request->expectsJson()) {
            return null;
        }

        // Get redirect URL from config
        $loginUrl = config('canvastack-rbac.contexts.admin.login_url');

        // If no config, try to use route helper
        if ($loginUrl === null) {
            try {
                $loginUrl = route('login');
            } catch (\Exception $e) {
                // If route doesn't exist, use default path
                $loginUrl = '/login';
            }
        }

        return $loginUrl;
    }
}

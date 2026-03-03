<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Load Theme Preference Middleware.
 *
 * Loads user's theme preference from database and applies it.
 */
class LoadThemePreference
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Load theme preference for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $preferences = $user->preferences ?? [];

            if (isset($preferences['theme'])) {
                $themeName = $preferences['theme'];
                $themeManager = app('canvastack.theme');

                // Validate and set theme
                if ($themeManager->has($themeName)) {
                    $themeManager->setCurrentTheme($themeName);
                }
            }
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Middleware;

use Canvastack\Canvastack\Components\Table\Support\ThemePreferenceLoader;
use Closure;
use Illuminate\Http\Request;

/**
 * Load User Theme Preference Middleware.
 *
 * Automatically loads the user's preferred theme from UserPreferences
 * and applies it to the ThemeManager on every request.
 *
 * This ensures the user's theme choice persists across sessions and
 * is applied before any views are rendered.
 *
 * Requirements: 51.10 - Theme persistence via UserPreferences
 *
 * @example
 * // In app/Http/Kernel.php or bootstrap/app.php:
 * $middleware->append(LoadUserThemePreference::class);
 */
class LoadUserThemePreference
{
    /**
     * Theme preference loader instance.
     */
    protected ThemePreferenceLoader $loader;

    /**
     * Constructor.
     *
     * @param ThemePreferenceLoader $loader Theme preference loader instance
     */
    public function __construct(ThemePreferenceLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Handle an incoming request.
     *
     * Loads the user's preferred theme before the request is processed.
     * This ensures the correct theme is active when views are rendered.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return mixed The response from the next middleware
     *
     * @example
     * // Middleware automatically loads user's theme preference:
     * // 1. User has 'ocean' theme saved in preferences
     * // 2. Middleware loads 'ocean' theme
     * // 3. All views render with 'ocean' theme
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Load user's preferred theme
        $this->loader->load();

        // Continue with request
        return $next($request);
    }
}

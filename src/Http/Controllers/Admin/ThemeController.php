<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers\Admin;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Admin Theme Controller.
 *
 * Manages theme configuration and settings in the admin panel.
 * Uses CanvaStack components (TableBuilder, MetaTags) for consistent UI.
 */
class ThemeController extends Controller
{
    /**
     * Display theme management page.
     *
     * @param TableBuilder $table Table builder component
     * @param MetaTags $meta Meta tags component
     * @return View
     */
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $themeManager = app('canvastack.theme');

        // Prepare themes collection
        $themes = collect($themeManager->all())->map(function ($theme) use ($themeManager) {
            return [
                'name' => $theme->getName(),
                'display_name' => $theme->getDisplayName(),
                'description' => $theme->getDescription(),
                'version' => $theme->getVersion(),
                'author' => $theme->getAuthor(),
                'dark_mode' => $theme->supportsDarkMode(),
                'is_active' => $theme->getName() === $themeManager->current()->getName(),
                'colors' => $theme->getColors(),
            ];
        });

        // Configure meta tags
        $meta->title('Theme Management');
        $meta->description('Manage and customize your application themes');
        $meta->keywords('themes, customization, appearance, design, colors');

        // Configure table
        $table->setContext('admin');
        $table->setCollection($themes);
        $table->setFields([
            'display_name:Theme',
            'version:Version',
            'author:Author',
            'dark_mode:Dark Mode',
            'is_active:Status',
        ]);

        // Custom renderers
        $table->setColumnRenderer('display_name', function ($row) {
            $primary = $row['colors']['primary']['500'] ?? $row['colors']['primary'] ?? '#6366f1';
            $secondary = $row['colors']['secondary']['500'] ?? $row['colors']['secondary'] ?? '#8b5cf6';
            $accent = $row['colors']['accent']['500'] ?? $row['colors']['accent'] ?? '#a855f7';

            return "
                <div class='flex items-center gap-3'>
                    <div class='flex w-12 h-12 rounded-lg overflow-hidden border-2 border-gray-300 dark:border-gray-700'>
                        <div class='flex-1' style='background-color: {$primary}'></div>
                        <div class='flex-1' style='background-color: {$secondary}'></div>
                        <div class='flex-1' style='background-color: {$accent}'></div>
                    </div>
                    <div>
                        <div class='font-bold text-gray-900 dark:text-gray-100'>{$row['display_name']}</div>
                        <div class='text-sm text-gray-600 dark:text-gray-400'>{$row['description']}</div>
                    </div>
                </div>
            ";
        });

        $table->setColumnRenderer('version', function ($row) {
            return "<span class='badge badge-outline'>v{$row['version']}</span>";
        });

        $table->setColumnRenderer('dark_mode', function ($row) {
            if ($row['dark_mode']) {
                return "<span class='badge badge-success gap-1'><i data-lucide='moon' class='w-3 h-3'></i> Yes</span>";
            }

            return "<span class='badge badge-ghost gap-1'><i data-lucide='sun' class='w-3 h-3'></i> No</span>";
        });

        $table->setColumnRenderer('is_active', function ($row) {
            if ($row['is_active']) {
                return "<span class='badge badge-primary gap-1'><i data-lucide='check' class='w-3 h-3'></i> Active</span>";
            }

            return "<span class='badge badge-ghost'>Inactive</span>";
        });

        // Add actions
        $table->setActions([
            'view' => [
                'label' => 'View Details',
                'icon' => 'eye',
                'url' => fn ($row) => route('admin.themes.show', $row['name']),
                'class' => 'btn-sm btn-info',
            ],
            'activate' => [
                'label' => 'Activate',
                'icon' => 'check-circle',
                'url' => fn ($row) => route('admin.themes.activate', $row['name']),
                'method' => 'POST',
                'class' => 'btn-sm btn-success',
                'condition' => fn ($row) => !$row['is_active'], // Only show if not active
            ],
            'export' => [
                'label' => 'Export',
                'icon' => 'download',
                'url' => fn ($row) => route('admin.themes.export', [$row['name'], 'json']),
                'class' => 'btn-sm btn-ghost',
            ],
        ], false); // false = don't include default actions

        $table->format();

        // Statistics
        $stats = [
            'total_themes' => $themes->count(),
            'active_theme' => $themeManager->current()->getDisplayName(),
            'cache_enabled' => config('canvastack-ui.theme.cache_enabled', true),
            'hot_reload' => config('canvastack-ui.theme.hot_reload', false),
        ];

        return view('canvastack::admin.themes.index', [
            'table' => $table,
            'meta' => $meta,
            'stats' => $stats,
            'themes' => $themes, // For test compatibility
            'currentTheme' => $themeManager->current()->getName(), // For test compatibility
        ]);
    }

    /**
     * Show theme details.
     *
     * @param string $theme
     * @param MetaTags $meta Meta tags component
     * @return View
     */
    public function show(string $theme, MetaTags $meta): View
    {
        $themeManager = app('canvastack.theme');

        if (!$themeManager->has($theme)) {
            abort(404, 'Theme not found');
        }

        $themeObj = $themeManager->get($theme);

        // Configure meta tags
        $meta->title($themeObj->getDisplayName() . ' Theme');
        $meta->description('View details and configuration for ' . $themeObj->getDisplayName() . ' theme');
        $meta->keywords('theme, ' . $themeObj->getName() . ', colors, design, customization');

        return view('canvastack::admin.themes.show', [
            'meta' => $meta,
            'theme' => $themeObj,
            'themeData' => [
                'name' => $themeObj->getName(),
                'display_name' => $themeObj->getDisplayName(),
                'description' => $themeObj->getDescription(),
                'version' => $themeObj->getVersion(),
                'author' => $themeObj->getAuthor(),
                'colors' => $themeObj->getColors(),
                'fonts' => $themeObj->getFonts(),
                'layout' => $themeObj->getLayout(),
                'gradient' => $themeObj->get('gradient', []),
                'dark_mode' => $themeObj->supportsDarkMode(),
                'components' => $themeObj->get('components', []),
            ],
            'isActive' => $themeObj->getName() === $themeManager->current()->getName(),
        ]);
    }

    /**
     * Activate a theme.
     *
     * @param Request $request
     * @param string $theme
     * @return RedirectResponse
     */
    public function activate(Request $request, string $theme): RedirectResponse
    {
        $themeManager = app('canvastack.theme');

        if (!$themeManager->has($theme)) {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', "Theme '{$theme}' not found");
        }

        try {
            // Set as current theme
            $themeManager->setCurrentTheme($theme);

            // Update config (this would need to write to .env or config file)
            // For now, we'll just update the session
            session(['active_theme' => $theme]);

            // Update user preference if authenticated and method exists
            if (auth()->check()) {
                $user = auth()->user();
                if (method_exists($user, 'setThemePreference')) {
                    $user->setThemePreference($theme);
                    $user->save();
                }
            }

            return redirect()
                ->route('admin.themes.index')
                ->with('success', "Theme '{$theme}' activated successfully");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', "Failed to activate theme: {$e->getMessage()}");
        }
    }

    /**
     * Clear theme cache.
     *
     * @return RedirectResponse
     */
    public function clearCache(): RedirectResponse
    {
        try {
            $themeManager = app('canvastack.theme');
            $themeManager->clearCache();

            return redirect()
                ->route('admin.themes.index')
                ->with('success', 'Theme cache cleared successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', "Failed to clear cache: {$e->getMessage()}");
        }
    }

    /**
     * Reload themes from filesystem.
     *
     * @return RedirectResponse
     */
    public function reload(): RedirectResponse
    {
        try {
            $themeManager = app('canvastack.theme');
            $themeManager->reload();

            return redirect()
                ->route('admin.themes.index')
                ->with('success', 'Themes reloaded successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', "Failed to reload themes: {$e->getMessage()}");
        }
    }

    /**
     * Export theme configuration.
     *
     * @param string $theme
     * @param string $format
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(string $theme, string $format = 'json')
    {
        $themeManager = app('canvastack.theme');

        if (!$themeManager->has($theme)) {
            return response()->json([
                'success' => false,
                'message' => 'Theme not found',
            ], 404);
        }

        try {
            $exported = $themeManager->export($format);

            if ($format === 'json') {
                return response()->streamDownload(function () use ($exported) {
                    echo $exported;
                }, "{$theme}-theme.json", [
                    'Content-Type' => 'application/json',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $exported,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to export theme: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Get theme preview data (AJAX).
     *
     * @param string $theme
     * @return JsonResponse
     */
    public function preview(string $theme): JsonResponse
    {
        $themeManager = app('canvastack.theme');

        if (!$themeManager->has($theme)) {
            return response()->json([
                'success' => false,
                'message' => 'Theme not found',
            ], 404);
        }

        try {
            $themeObj = $themeManager->get($theme);

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $themeObj->getName(),
                    'display_name' => $themeObj->getDisplayName(),
                    'colors' => $themeObj->getColors(),
                    'gradient' => $themeObj->get('gradient', []),
                    'css_variables' => $themeObj->getCssVariables(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to get theme preview: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Get theme statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $themeManager = app('canvastack.theme');
            $themes = $themeManager->all();

            $stats = [
                'total_themes' => count($themes),
                'active_theme' => $themeManager->current()->getName(),
                'cache_enabled' => config('canvastack-ui.theme.cache_enabled', true),
                'cache_ttl' => config('canvastack-ui.theme.cache_ttl', 3600),
                'hot_reload' => config('canvastack-ui.theme.hot_reload', false),
                'themes_with_dark_mode' => collect($themes)->filter(fn ($t) => $t->supportsDarkMode())->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to get statistics: {$e->getMessage()}",
            ], 500);
        }
    }
}

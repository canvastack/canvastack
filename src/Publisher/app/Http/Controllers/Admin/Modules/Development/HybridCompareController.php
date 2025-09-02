<?php

namespace App\Http\Controllers\Admin\Modules\Development;

use Canvastack\Canvastack\Library\Components\MetaTags;
use Canvastack\Canvastack\Library\Components\Template as TemplateComponents;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class HybridCompareController extends BaseController
{
    // Show form with available routes
    public function index(Request $request)
    {
        $routes = collect(Route::getRoutes())
            ->filter(function ($route) {
                return ! empty($route->getName());
            })
            ->map(function ($route) {
                return $route->getName();
            })
            ->unique()
            ->sort()
            ->values();

        // Prefer system.* routes; default to system.accounts.user(.index)
        $default = $routes->contains('system.accounts.user.index')
            ? 'system.accounts.user.index'
            : ($routes->contains('system.accounts.user') ? 'system.accounts.user' : ($routes->first() ?: ''));

        // Prepare template components expected by the admin layout
        $template = new TemplateComponents();
        $meta = new MetaTags();
        $components = (object) [
            'template' => $template,
            'meta' => $meta,
        ];

        // Optional UI vars expected in partials
        $breadcrumbs = canvastack_breadcrumb('Hybrid Compare');
        $menu_sidebar = $template->menu_sidebar ?? [];
        $sidebar_content = $template->sidebar_content ?? null;
        $logo = '/assets/brand/logo.png'; // non-existent to trigger else branch with src attribute
        $appName = config('app.name');

        return view('modules.development.hybrid-compare', [
            'routes' => $routes,
            'default' => $default,
            'result' => null,
            'components' => $components,
            'breadcrumbs' => $breadcrumbs,
            'menu_sidebar' => $menu_sidebar,
            'sidebar_content' => $sidebar_content,
            'logo' => $logo,
            'appName' => $appName,
        ]);
    }

    // Execute selected route under hybrid mode in a sub-request
    public function run(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string',
        ]);

        $routeName = $request->input('route_name');

        if (! Route::has($routeName)) {
            return back()->withErrors(['route_name' => 'Route not found: '.$routeName])->withInput();
        }

        // Force hybrid mode for this sub-request only
        config(['canvastack.datatables.mode' => 'hybrid']);

        $url = route($routeName);
        $subRequest = Request::create($url, 'GET');

        try {
            $response = app()->handle($subRequest);
            $status = $response->getStatusCode();
            $note = 'Executed with hybrid mode. Check logs for [DT Hybrid] Diff and inspector JSON under storage/app/datatable-inspector.';
        } catch (\Throwable $e) {
            Log::error('[HybridCompare Tool] Error executing route', ['route' => $routeName, 'error' => $e->getMessage()]);
            $status = 500;
            $note = 'Error executing route: '.$e->getMessage();
        }

        $routes = collect(Route::getRoutes())
            ->filter(function ($route) {
                return ! empty($route->getName());
            })
            ->map(function ($route) {
                return $route->getName();
            })
            ->unique()
            ->sort()
            ->values();

        // Prepare template components for the response view as well
        $template = new TemplateComponents();
        $meta = new MetaTags();
        $components = (object) [
            'template' => $template,
            'meta' => $meta,
        ];

        $breadcrumbs = canvastack_breadcrumb('Hybrid Compare');
        $menu_sidebar = $template->menu_sidebar ?? [];
        $sidebar_content = $template->sidebar_content ?? null;
        $logo = '/assets/brand/logo.png';
        $appName = config('app.name');

        return view('modules.development.hybrid-compare', [
            'routes' => $routes,
            'default' => $routeName,
            'result' => [
                'route' => $routeName,
                'status' => $status ?? null,
                'note' => $note,
            ],
            'components' => $components,
            'breadcrumbs' => $breadcrumbs,
            'menu_sidebar' => $menu_sidebar,
            'sidebar_content' => $sidebar_content,
            'logo' => $logo,
            'appName' => $appName,
        ]);
    }
}

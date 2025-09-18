<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Actions;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * Compose action column and removed privileges per legacy logic.
 */
final class ActionButtonsResolver
{
    /**
     * @param  \Yajra\DataTables\DataTableAbstract  $datatables
     * @param  array  $privileges Legacy privileges (role_group, role, etc.)
     * @param  array  $actionList Merged action list (view/insert/edit/delete + custom)
     * @param  array  $buttonsRemoval From columns config button_removed (optional)
     * @param  array  $removedPrivileges Precomputed removed privileges (optional)
     */
    public static function apply($datatables, TableContext $ctx, array $privileges, array $actionList, array $buttonsRemoval = [], array $removedPrivileges = []): void
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ActionButtonsResolver: Starting action buttons resolution', [
                'table_name' => $ctx->tableName ?? 'unknown',
                'model_name' => $ctx->method['difta']['name'] ?? 'unknown',
                'action_count' => count($actionList),
                'has_button_removal' => !empty($buttonsRemoval),
                'has_removed_privileges' => !empty($removedPrivileges)
            ]);
        }

        $data = $ctx->data;
        $table = $ctx->tableName;
        $model = $ctx->method['difta']['name'] ?? 'unknown';

        // Build action_data structure as legacy
        $action_data = [];
        $action_data['model'] = $model; // kept for parity though legacy passes $model instance; not used directly in renderer
        
        // CRITICAL FIX: Get correct route path for action buttons
        // The issue is that datatables are called via AJAX, but we need the original page route
        // We need to detect the actual controller route, not the ajax route
        try {
            // First, try to get the route from the HTTP_REFERER header (the page that made the AJAX call)
            $refererUrl = $_SERVER['HTTP_REFERER'] ?? null;
            if ($refererUrl) {
                $parsedReferer = parse_url($refererUrl);
                $refererPath = $parsedReferer['path'] ?? '';
                
                // Remove the public part and leading slash
                $refererPath = preg_replace('#^/[^/]*/public/#', '/', $refererPath);
                $refererPath = preg_replace('#^/public/#', '/', $refererPath);
                $refererPath = ltrim($refererPath, '/');
                
                // Remove trailing action suffixes (index, create, edit, show) to get base path
                $pathParts = explode('/', $refererPath);
                if (count($pathParts) > 0 && in_array(end($pathParts), ['index', 'create', 'edit', 'show'])) {
                    array_pop($pathParts);
                }
                $basePath = implode('/', array_filter($pathParts));
                
                if (!empty($basePath)) {
                    $action_data['current_url'] = url($basePath);
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('ActionButtonsResolver: URL resolved from HTTP_REFERER', [
                            'method' => 'referer',
                            'base_path' => $basePath,
                            'resolved_url' => $action_data['current_url']
                        ]);
                    }
                } else {
                    throw new \Exception('Empty base path from referer');
                }
            } else {
                throw new \Exception('No HTTP_REFERER found');
            }
        } catch (\Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::warning('ActionButtonsResolver: Failed to resolve URL from HTTP_REFERER', [
                    'error_type' => get_class($e),
                    'fallback_method' => 'current_route_info'
                ]);
            }
            
            // Fallback 1: Try to get from current route info
            try {
                $currentRouteInfo = canvastack_current_route();
                if ($currentRouteInfo && isset($currentRouteInfo->uri)) {
                    // Use the actual route URI, not the ajax route
                    $routeUri = $currentRouteInfo->uri;
                    
                    // Remove trailing action suffixes (index, create, edit, show) to get base path
                    $pathParts = explode('/', trim($routeUri, '/'));
                    if (count($pathParts) > 0 && in_array(end($pathParts), ['index', 'create', 'edit', 'show'])) {
                        array_pop($pathParts);
                    }
                    $basePath = implode('/', array_filter($pathParts));
                    
                    $action_data['current_url'] = url($basePath);
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('ActionButtonsResolver: URL resolved from current route info', [
                            'method' => 'current_route_info',
                            'route_uri' => $routeUri,
                            'base_path' => $basePath
                        ]);
                    }
                } else {
                    throw new \Exception('No current route info');
                }
            } catch (\Throwable $e2) {
                // Fallback 2: Try to extract route path from current route name
                try {
                    $currentRoute = current_route();
                    if ($currentRoute && $currentRoute !== 'datatables.post') {
                        // Remove the last segment (index/create/edit/show) to get base route
                        $routeParts = explode('.', $currentRoute);
                        if (count($routeParts) > 1) {
                            array_pop($routeParts); // Remove last part (index/create/edit/show)
                            $baseRoute = implode('.', $routeParts);
                            $basePath = str_replace('.', '/', $baseRoute);
                        } else {
                            // Single part route, use as is
                            $basePath = str_replace('.', '/', $currentRoute);
                        }
                        
                        $action_data['current_url'] = url($basePath);
                        if (app()->environment(['local', 'testing'])) {
                            SafeLogger::debug('ActionButtonsResolver: URL resolved from route name', [
                                'method' => 'route_name',
                                'current_route' => $currentRoute,
                                'base_path' => $basePath
                            ]);
                        }
                    } else {
                        throw new \Exception('Invalid current route');
                    }
                } catch (\Throwable $e3) {
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::warning('ActionButtonsResolver: Using final URL fallback', [
                            'error_type' => get_class($e3),
                            'fallback_method' => 'current_url'
                        ]);
                    }
                    
                    // Final fallback: extract from current URL
                    $currentUrl = canvastack_current_url();
                    $parsedUrl = parse_url($currentUrl);
                    $path = $parsedUrl['path'] ?? '/';
                    
                    // Remove trailing action suffixes from path
                    $pathParts = explode('/', trim($path, '/'));
                    if (count($pathParts) > 0 && in_array(end($pathParts), ['index', 'create', 'edit', 'show'])) {
                        array_pop($pathParts);
                    }
                    $cleanPath = implode('/', array_filter($pathParts));
                    
                    $action_data['current_url'] = url($cleanPath ?: '/');
                    if (app()->environment(['local', 'testing'])) {
                        SafeLogger::debug('ActionButtonsResolver: Final URL resolution complete', [
                            'method' => 'final_fallback',
                            'clean_path' => $cleanPath ?: '/',
                            'resolved_url' => $action_data['current_url']
                        ]);
                    }
                }
            }
        }
        

        $action_data['action']['data'] = $actionList;
        if (($privileges['role_group'] ?? 0) > 1) {
            if (! empty($removedPrivileges)) {
                $action_data['action']['removed'] = $removedPrivileges;
            } else {
                $action_data['action']['removed'] = $data->datatables->button_removed ?? [];
            }
        } else {
            $action_data['action']['removed'] = $data->datatables->button_removed ?? [];
        }

        if (! empty($buttonsRemoval)) {
            $removeActions = $action_data['action']['removed'];
            unset($action_data['action']['removed']);
            $action_data['action']['removed'] = array_merge_recursive_distinct($buttonsRemoval, $removeActions);
        }

        $urlTarget = $data->datatables->useFieldTargetURL ?? 'id';

        $datatables->addColumn('action', function ($row) use ($action_data, $urlTarget, $data, $table) {
            // Try legacy helper first; fallback to safe renderer when helper not available
            if (function_exists('canvastack_table_action_button')) {
                return canvastack_table_action_button($row, $urlTarget, $action_data['current_url'], $action_data['action']['data'], $action_data['action']['removed']);
            }
            // Normalize row to array
            $arr = method_exists($row, 'getAttributes') ? $row->getAttributes() : (array) $row;
            return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\ActionButtonsRenderer::render($arr, $data->datatables, $table);
        });

        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ActionButtonsResolver: Action buttons resolution completed', [
                'table_name' => $table,
                'url_target' => $urlTarget,
                'current_url' => $action_data['current_url'] ?? 'unknown',
                'actions_count' => count($action_data['action']['data'] ?? []),
                'removed_count' => count($action_data['action']['removed'] ?? [])
            ]);
        }
    }
}

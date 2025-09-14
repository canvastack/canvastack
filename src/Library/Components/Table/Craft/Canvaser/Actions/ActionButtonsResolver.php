<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Actions;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;

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
        $data = $ctx->data;
        $table = $ctx->tableName;
        $model = $ctx->method['difta']['name'] ?? 'unknown';

        // Build action_data structure as legacy
        $action_data = [];
        $action_data['model'] = $model; // kept for parity though legacy passes $model instance; not used directly in renderer
        
        // CRITICAL FIX: Get correct route path for action buttons
        // Use canvastack_current_route() to get the actual route, not the ajax route
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
                $basePath = '/' . implode('/', $pathParts);
                
                $action_data['current_url'] = url($basePath);
            } else {
                // Fallback: try to extract route path from current route name
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
                } else {
                    // Final fallback: extract from current URL
                    $currentUrl = canvastack_current_url();
                    $parsedUrl = parse_url($currentUrl);
                    $path = $parsedUrl['path'] ?? '/';
                    
                    // Remove trailing action suffixes from path
                    $pathParts = explode('/', trim($path, '/'));
                    if (count($pathParts) > 0 && in_array(end($pathParts), ['index', 'create', 'edit', 'show'])) {
                        array_pop($pathParts);
                    }
                    $cleanPath = '/' . implode('/', $pathParts);
                    
                    $action_data['current_url'] = url($cleanPath);
                }
            }
        } catch (\Throwable $e) {
            // Emergency fallback: extract from current URL
            $currentUrl = canvastack_current_url();
            $parsedUrl = parse_url($currentUrl);
            $path = $parsedUrl['path'] ?? '/';
            
            // Remove trailing action suffixes from path
            $pathParts = explode('/', trim($path, '/'));
            if (count($pathParts) > 0 && in_array(end($pathParts), ['index', 'create', 'edit', 'show'])) {
                array_pop($pathParts);
            }
            $cleanPath = '/' . implode('/', $pathParts);
            
            $action_data['current_url'] = url($cleanPath);
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
    }
}

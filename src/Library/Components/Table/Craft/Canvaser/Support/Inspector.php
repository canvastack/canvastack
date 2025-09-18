<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class Inspector
{
    /**
     * Dump datatable descriptor to storage for diagnostics.
     * Only runs when app env is local OR canvastack.datatables.mode === 'hybrid'.
     */
    public static function dump(array $descriptor): void
    {
        try {
            $enabled = (app()->environment('local') || FeatureFlag::mode() === 'hybrid');
            if (! $enabled) {
                return;
            }

            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('Inspector: Dumping datatable descriptor', [
                    'descriptor_keys' => array_keys($descriptor),
                    'enabled' => $enabled
                ]);
            }

            $dir = storage_path('app/datatable-inspector');
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $routeName = null;
            $routePath = null;
            if (function_exists('request')) {
                $req = request();
                if ($req) {
                    $route = $req->route();
                    if ($route) {
                        $routeName = method_exists($route, 'getName') ? $route->getName() : null;
                        $routePath = method_exists($route, 'uri') ? $route->uri() : ($req->path() ?? null);
                    } else {
                        $routePath = $req->path();
                    }
                }
            }

            $filenameBase = ($descriptor['table_name'] ?? 'unknown');
            if (! empty($routeName)) {
                $filenameBase = $filenameBase.'_'.str_replace([':', '/', '\\'], '-', $routeName);
            } elseif (! empty($routePath)) {
                $filenameBase = $filenameBase.'_'.str_replace([':', '/', '\\'], '-', $routePath);
            }

            $file = $dir.DIRECTORY_SEPARATOR.$filenameBase.'_'.date('Ymd_His').'.json';
            @file_put_contents($file, json_encode($descriptor, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    /**
     * Build and dump datatable descriptor from context variables.
     *
     * Required keys (ctx):
     * - table_name: string|null
     * - model_type: string|null
     * - model_source: mixed (string|object|null)
     * - columns: array{lists?:array, blacklist?:array, format?:array, relations?:array, orderby?:array|null, clickable?:array, actions?:array|null, removed?:array}
     * - joins: array{foreign_keys?:array, selected?:array}
     * - filters: array{where?:array, applied?:array, raw_params?:array}
     * - paging: array{start?:int, length?:int, total?:int}
     * - row: array{attributes?:array, urlTarget?:string|null}
     *
     * Notes:
     * - Will enrich with route info and timestamp automatically.
     * - Swallows all errors; never breaks the request flow.
     */
    public static function inspect(array $ctx): void
    {
        try {
            $descriptor = [
                'timestamp' => date('c'),
                'route' => [
                    'name' => optional(request()->route())->getName(),
                    'path' => optional(request())->path(),
                ],
                'table_name' => $ctx['table_name'] ?? null,
                'model' => [
                    'type' => $ctx['model_type'] ?? null,
                    'source' => is_string($ctx['model_source'] ?? null)
                        ? $ctx['model_source']
                        : (is_object($ctx['model_source'] ?? null) ? get_class($ctx['model_source']) : null),
                ],
                'columns' => $ctx['columns'] ?? [],
                'joins' => $ctx['joins'] ?? [],
                'filters' => $ctx['filters'] ?? [],
                'paging' => [
                    'start' => intval($ctx['paging']['start'] ?? 0),
                    'length' => intval($ctx['paging']['length'] ?? 10),
                    'total' => intval($ctx['paging']['total'] ?? 0),
                ],
                'row' => $ctx['row'] ?? [],
            ];

            self::dump($descriptor);
        } catch (\Throwable $e) {
            // Silent fail
        }
    }
}

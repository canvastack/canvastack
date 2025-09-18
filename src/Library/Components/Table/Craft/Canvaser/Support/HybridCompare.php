<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\DatatablesPipeline;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables as LegacyDatatables;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class HybridCompare
{
    /**
     * Run pipeline preflight (no-op) and legacy process, then diff if pipeline output available (Phase 3).
     * Returns array with legacy_result and diff summary.
     *
     * @return array{legacy_result:mixed,diff:array}
     */
    public static function run(array $method, object $data, array $filters = [], array $filter_page = []): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('HybridCompare: Starting hybrid comparison', [
                'method_keys' => array_keys($method),
                'has_filters' => !empty($filters),
                'has_filter_page' => !empty($filter_page)
            ]);
        }

        $pipelineOutput = null; // Will be available in Phase 3

        // Preflight pipeline
        try {
            $adapter = new LegacyContextAdapter();
            $context = $adapter->fromLegacyInputs($method, $data, $filters, $filter_page);
            $pipeline = new DatatablesPipeline();
            $context = $pipeline->run($context);
            
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('HybridCompare: Preflight pipeline completed', [
                    'table' => $context->tableName
                ]);
            }

            // Phase 2+: expose a placeholder response if available on context
            if (property_exists($context, 'response')) {
                $pipelineOutput = $context->response;
            } elseif (property_exists($context, 'payload')) {
                $pipelineOutput = $context->payload;
            }
        } catch (\Throwable $e) {
            if (function_exists('app') && function_exists('logger')) {
                try {
                    if (app()->bound('log')) {
                        logger()->warning('[DT HybridCompare] Preflight Error '.$e->getMessage());
                    }
                } catch (\Throwable $e2) { /* ignore */
                }
            }
        }

        // Run legacy for actual result
        $legacyService = new LegacyDatatables();
        $legacyResult = $legacyService->process($method, $data, $filters, $filter_page);

        $diff = JsonDiff::compare($legacyResult, $pipelineOutput);

        // Write a compact inspector diff summary for tooling (local/hybrid only)
        try {
            if (function_exists('app') && function_exists('storage_path')) {
                $enabled = (app()->environment('local') || FeatureFlag::mode() === 'hybrid');
                if ($enabled) {
                    $dir = storage_path('app/datatable-inspector');
                    if (! is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    // Best-effort table and route
                    $table = null;
                    try {
                        $table = is_object($data) && property_exists($data, 'table_name') ? $data->table_name : ($data->table_name ?? null);
                    } catch (\Throwable $e) {
                        $table = null;
                    }
                    if (! $table) {
                        try {
                            $table = is_array($method) ? ($method['table'] ?? $method['table_name'] ?? null) : null;
                        } catch (\Throwable $e) {
                            $table = null;
                        }
                    }
                    $routeName = null;
                    $routePath = null;
                    try {
                        $req = function_exists('request') ? request() : null;
                        if ($req) {
                            $route = $req->route();
                            if ($route) {
                                $routeName = method_exists($route, 'getName') ? $route->getName() : null;
                                $routePath = method_exists($route, 'uri') ? $route->uri() : ($req->path() ?? null);
                            } else {
                                $routePath = $req->path();
                            }
                        }
                    } catch (\Throwable $e) { /* ignore */
                    }
                    $filenameBase = ($table ?: 'unknown');
                    if (! empty($routeName)) {
                        $filenameBase .= '_'.str_replace([':', '/', '\\'], '-', $routeName);
                    } elseif (! empty($routePath)) {
                        $filenameBase .= '_'.str_replace([':', '/', '\\'], '-', $routePath);
                    }
                    $out = [
                        'timestamp' => date('c'),
                        'route' => ['name' => $routeName, 'path' => $routePath],
                        'table' => $table,
                        'diff' => $diff,
                    ];
                    @file_put_contents($dir.DIRECTORY_SEPARATOR.$filenameBase.'_'.date('Ymd_His').'.json', json_encode($out, JSON_PRETTY_PRINT));
                }
            }
        } catch (\Throwable $e) { /* silent */
        }

        return [
            'legacy_result' => $legacyResult,
            'diff' => $diff,
        ];
    }
}

<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Charts;

use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * ChartBridge — thin wrapper to invoke chart rendering and extract needed artifacts
 * without coupling Objects.php to Chart internals.
 */
final class ChartBridge
{
    /**
     * Invoke a chart and return artifacts needed by table Objects.
     *
     * @param  mixed  $format
     * @param  mixed  $category
     * @param  mixed  $group
     * @param  mixed  $order
     * @param  array  $chartOptions optionName => optionValues (invoked as dynamic methods on Chart)
     * @return array{chart: Chart, chartLibrary: mixed, elements: array, identities: mixed, script_js: string}
     */
    public static function invoke(
        string $chartType,
        array $fieldsets,
        $format,
        $category,
        $group,
        $order,
        ?string $connection,
        string $tableName,
        array $chartOptions = []
    ): array {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ChartBridge: Starting chart invocation', [
                'chart_type' => $chartType,
                'table_name' => $tableName,
                'connection' => $connection ?? 'default',
                'fieldsets_count' => count($fieldsets),
                'chart_options_count' => count($chartOptions)
            ]);
        }

        $chart = new Chart();
        $chart->connection = $connection;

        // Apply options as dynamic method calls (legacy behavior)
        if (! empty($chartOptions)) {
            foreach ($chartOptions as $optName => $optValues) {
                $chart->{$optName}($optValues);
            }
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::debug('ChartBridge: Chart options applied', [
                    'options_applied' => array_keys($chartOptions)
                ]);
            }
        }

        // Execute chart type method
        $chart->{$chartType}($tableName, $fieldsets, $format, $category, $group, $order);

        $result = [
            'chart' => $chart,
            'chartLibrary' => $chart->chartLibrary,
            'elements' => $chart->elements,
            'identities' => $chart->identities,
            'script_js' => $chart->script_chart['js'] ?? '',
        ];

        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ChartBridge: Chart invocation completed', [
                'chart_library' => $chart->chartLibrary ?? 'unknown',
                'elements_count' => is_array($chart->elements) ? count($chart->elements) : 0,
                'has_script_js' => !empty($result['script_js'])
            ]);
        }

        return $result;
    }
}

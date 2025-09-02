<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Charts;

use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;

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
        $chart = new Chart();
        $chart->connection = $connection;

        // Apply options as dynamic method calls (legacy behavior)
        if (! empty($chartOptions)) {
            foreach ($chartOptions as $optName => $optValues) {
                $chart->{$optName}($optValues);
            }
        }

        // Execute chart type method
        $chart->{$chartType}($tableName, $fieldsets, $format, $category, $group, $order);

        return [
            'chart' => $chart,
            'chartLibrary' => $chart->chartLibrary,
            'elements' => $chart->elements,
            'identities' => $chart->identities,
            'script_js' => $chart->script_chart['js'] ?? '',
        ];
    }
}

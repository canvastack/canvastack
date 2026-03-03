<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart\Renderers;

use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * PublicRenderer - Renders charts for public frontend.
 *
 * Extends AdminRenderer with public-specific styling adjustments.
 * Uses same ApexCharts library but with public-facing design.
 */
class PublicRenderer extends AdminRenderer
{
    /**
     * Render chart container with public styling.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string HTML container
     */
    public function renderContainer(ChartBuilder $chart): string
    {
        $chartId = htmlspecialchars($chart->getId());
        $height = $chart->getHeight() ?? 350;

        // Public styling: simpler card design
        return <<<HTML
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 sm:p-6 shadow-sm">
            <div id="{$chartId}" style="min-height: {$height}px;"></div>
        </div>
        HTML;
    }

    /**
     * Build chart configuration for public context.
     *
     * @param ChartBuilder $chart Chart instance
     * @return array Chart configuration
     */
    protected function buildChartConfig(ChartBuilder $chart): array
    {
        // Get base configuration from parent
        $config = parent::buildChartConfig($chart);

        // Customize for public context
        // Simpler toolbar for public users
        $config['chart']['toolbar'] = [
            'show' => true,
            'tools' => [
                'download' => true,
                'selection' => false,
                'zoom' => false,
                'zoomin' => false,
                'zoomout' => false,
                'pan' => false,
                'reset' => false,
            ],
        ];

        // Adjust animations for public (slightly faster)
        $config['chart']['animations'] = [
            'enabled' => true,
            'easing' => 'easeinout',
            'speed' => 600,
        ];

        return $config;
    }
}

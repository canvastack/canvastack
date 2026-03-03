<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart\Renderers;

use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * AdminRenderer - Renders charts for admin panel.
 *
 * Uses Tailwind CSS + DaisyUI styling with dark mode support.
 * Integrates ApexCharts library for chart rendering.
 */
class AdminRenderer implements RendererInterface
{
    /**
     * Track if assets have been rendered.
     */
    protected static bool $assetsRendered = false;

    /**
     * Render a complete chart with container and script.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string HTML output
     */
    public function render(ChartBuilder $chart): string
    {
        $html = '';

        // Render assets once per page
        if (!self::$assetsRendered) {
            $html .= $this->renderAssets();
            self::$assetsRendered = true;
        }

        // Render filter form if enabled
        if ($chart->hasFilters()) {
            $html .= $this->renderFilterForm($chart);
        }

        // Render chart container
        $html .= $this->renderContainer($chart);

        // Render chart script
        $html .= $this->renderScript($chart);

        return $html;
    }

    /**
     * Render chart container with card styling.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string HTML container
     */
    public function renderContainer(ChartBuilder $chart): string
    {
        $chartId = htmlspecialchars($chart->getId());
        $height = $chart->getHeight() ?? 350;

        return <<<HTML
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
            <div id="{$chartId}" style="min-height: {$height}px;"></div>
        </div>
        HTML;
    }

    /**
     * Render chart initialization script.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string JavaScript code
     */
    public function renderScript(ChartBuilder $chart): string
    {
        $chartId = $chart->getId();

        // Check if AJAX mode is enabled
        if ($chart->isAjaxMode()) {
            return $this->renderAjaxScript($chart);
        }

        // Regular static data rendering
        $config = $this->buildChartConfig($chart);
        $configJson = json_encode($config, JSON_THROW_ON_ERROR);

        // Use defer to ensure script runs after Vite assets are loaded
        return <<<HTML
        <script type="module">
        // Wait for window load to ensure all modules are loaded
        window.addEventListener('load', function() {
            // Function to initialize chart
            function initChart() {
                // Wait for ApexCharts to be loaded
                if (typeof window.ApexCharts === 'undefined') {
                    console.error('ApexCharts library not loaded. Make sure Vite assets are built.');
                    return;
                }

                // Chart configuration
                const config = {$configJson};

                // Apply dark mode theme if active
                if (document.documentElement.classList.contains('dark')) {
                    config.theme = config.theme || {};
                    config.theme.mode = 'dark';
                }

                // Initialize chart
                const chart = new window.ApexCharts(document.querySelector('#{$chartId}'), config);
                chart.render();

                // Store chart instance for later access
                window.charts = window.charts || {};
                window.charts['{$chartId}'] = chart;

                // Listen for dark mode changes
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            const isDark = document.documentElement.classList.contains('dark');
                            chart.updateOptions({
                                theme: {
                                    mode: isDark ? 'dark' : 'light'
                                }
                            });
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            // Initialize chart
            initChart();
        });
        </script>
        HTML;
    }

    /**
     * Render AJAX-enabled chart script.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string JavaScript code
     */
    protected function renderAjaxScript(ChartBuilder $chart): string
    {
        $chartId = $chart->getId();
        $ajaxUrl = $chart->getAjaxUrl();
        $method = $chart->getMethod();
        $params = json_encode($chart->getAjaxParams(), JSON_THROW_ON_ERROR);
        $autoLoad = $chart->isAutoLoad() ? 'true' : 'false';
        $interval = $chart->getRealtimeInterval();

        $config = $this->buildChartConfig($chart);
        $configJson = json_encode($config, JSON_THROW_ON_ERROR);

        $script = <<<HTML
        <script>
        (function() {
            // Function to initialize chart
            function initChart() {
                // Wait for ApexCharts to be loaded
                if (typeof ApexCharts === 'undefined') {
                    console.error('ApexCharts library not loaded. Make sure Vite assets are built.');
                    return;
                }

                // Base chart configuration
                let config = {$configJson};

                // Apply dark mode theme if active
                if (document.documentElement.classList.contains('dark')) {
                    config.theme = config.theme || {};
                    config.theme.mode = 'dark';
                }

                // Initialize chart
                const chart = new ApexCharts(document.querySelector('#{$chartId}'), config);
                chart.render();

                // Store chart instance
                window.charts = window.charts || {};
                window.charts['{$chartId}'] = chart;

                // AJAX data loading function
                async function loadChartData() {
                    try {
                        // Merge base params with filters
                        const baseParams = {$params};
                        const allParams = {...baseParams, ...(chart.filters || {})};

                        const options = {
                            method: '{$method}',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        };

                        // Add body for POST/PUT/PATCH requests
                        if (['{$method}'].includes('POST') || ['{$method}'].includes('PUT') || ['{$method}'].includes('PATCH')) {
                            options.body = JSON.stringify(allParams);
                        }

                        const response = await fetch('{$ajaxUrl}', options);
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }

                        const data = await response.json();

                        // Update chart with new data
                        if (data.series) {
                            chart.updateSeries(data.series, true);
                        }

                        if (data.labels || data.categories) {
                            chart.updateOptions({
                                xaxis: {
                                    categories: data.labels || data.categories
                                }
                            }, false, true);
                        }

                        // Trigger custom event
                        document.dispatchEvent(new CustomEvent('chart:loaded', {
                            detail: { chartId: '{$chartId}', data: data }
                        }));

                    } catch (error) {
                        console.error('Error loading chart data:', error);
                        
                        // Trigger error event
                        document.dispatchEvent(new CustomEvent('chart:error', {
                            detail: { chartId: '{$chartId}', error: error.message }
                        }));
                    }
                }

                // Auto-load on page load
                if ({$autoLoad}) {
                    loadChartData();
                }

                // Store load function for manual trigger
                window.charts['{$chartId}'].loadData = loadChartData;
HTML;

        // Add real-time updates if interval is set
        if ($interval) {
            $intervalMs = $interval * 1000;
            $script .= <<<HTML


                // Real-time updates
                const intervalId = setInterval(loadChartData, {$intervalMs});
                
                // Store interval ID for cleanup
                window.charts['{$chartId}'].intervalId = intervalId;
                
                // Cleanup on page unload
                window.addEventListener('beforeunload', function() {
                    clearInterval(intervalId);
                });
HTML;
        }

        $script .= <<<HTML


                // Listen for dark mode changes
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            const isDark = document.documentElement.classList.contains('dark');
                            chart.updateOptions({
                                theme: {
                                    mode: isDark ? 'dark' : 'light'
                                }
                            });
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            // Initialize chart when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
                // DOM already loaded, init immediately
                initChart();
            }
        })();
        </script>
        HTML;

        return $script;
    }

    /**
     * Render chart initialization script (legacy method).
     *
     * @param ChartBuilder $chart Chart instance
     * @return string JavaScript code
     * @deprecated Use renderScript() instead
     */
    protected function renderLegacyScript(ChartBuilder $chart): string
    {
        $chartId = $chart->getId();
        $config = $this->buildChartConfig($chart);
        $configJson = json_encode($config, JSON_THROW_ON_ERROR);

        return <<<HTML
        <script>
        (function() {
            // Wait for ApexCharts to be loaded
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts library not loaded');
                return;
            }

            // Chart configuration
            const config = {$configJson};

            // Apply dark mode theme if active
            if (document.documentElement.classList.contains('dark')) {
                config.theme = config.theme || {};
                config.theme.mode = 'dark';
            }

            // Initialize chart
            const chart = new ApexCharts(document.querySelector('#{$chartId}'), config);
            chart.render();

            // Store chart instance for later access
            window.charts = window.charts || {};
            window.charts['{$chartId}'] = chart;

            // Listen for dark mode changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        chart.updateOptions({
                            theme: {
                                mode: isDark ? 'dark' : 'light'
                            }
                        });
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        })();
        </script>
        HTML;
    }

    /**
     * Render ApexCharts assets.
     *
     * @return string HTML asset tags
     */
    public function renderAssets(): string
    {
        // ApexCharts should be loaded via npm/Vite (window.ApexCharts)
        // No need to load from CDN if already available
        return <<<HTML
        <script>
        // ApexCharts is loaded via Vite in app.js
        // This is just a fallback check
        if (typeof ApexCharts === 'undefined') {
            console.warn('ApexCharts not loaded. Make sure to run: npm run dev or npm run build');
        }
        </script>
        HTML;
    }

    /**
     * Build complete chart configuration for ApexCharts.
     *
     * @param ChartBuilder $chart Chart instance
     * @return array Chart configuration
     */
    protected function buildChartConfig(ChartBuilder $chart): array
    {
        $type = $chart->getType();
        $series = $chart->getSeries();
        $labels = $chart->getLabels();
        $options = $chart->getOptions();

        // Base configuration
        $config = [
            'chart' => [
                'type' => $type,
                'height' => $chart->getHeight() ?? 350,
                'width' => $chart->getWidth() ?? '100%',
                'fontFamily' => 'Inter, system-ui, sans-serif',
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => true,
                        'selection' => true,
                        'zoom' => true,
                        'zoomin' => true,
                        'zoomout' => true,
                        'pan' => true,
                        'reset' => true,
                    ],
                ],
                'animations' => [
                    'enabled' => true,
                    'easing' => 'easeinout',
                    'speed' => 800,
                ],
            ],
            'colors' => $chart->getColors(),
            'theme' => [
                'mode' => 'light', // Will be toggled by JavaScript
                'palette' => 'palette1',
            ],
        ];

        // Add series data
        if (in_array($type, ['pie', 'donut', 'radialBar'])) {
            $config['series'] = $series;
            $config['labels'] = $labels;
        } else {
            $config['series'] = $series;
            if (!empty($labels)) {
                $config['xaxis'] = [
                    'categories' => $labels,
                ];
            }
        }

        // Merge custom options
        $config = array_merge_recursive($config, $options);

        // Add responsive configuration
        if ($chart->isResponsive()) {
            $config['responsive'] = [
                [
                    'breakpoint' => 480,
                    'options' => [
                        'chart' => [
                            'width' => '100%',
                        ],
                        'legend' => [
                            'position' => 'bottom',
                        ],
                    ],
                ],
            ];
        }

        // Add dark mode specific styling
        $config['grid'] = array_merge($config['grid'] ?? [], [
            'borderColor' => '#e5e7eb',
        ]);

        $config['tooltip'] = array_merge($config['tooltip'] ?? [], [
            'theme' => 'light',
        ]);

        return $config;
    }

    /**
     * Render filter form for chart.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string HTML filter form
     */
    protected function renderFilterForm(ChartBuilder $chart): string
    {
        $filters = $chart->getFilters();
        $containerId = $chart->getFilterContainerId();
        $chartId = $chart->getId();

        $html = '<div id="' . htmlspecialchars($containerId) . '" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm mb-4">';
        $html .= '<form id="' . htmlspecialchars($containerId) . '-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">';

        foreach ($filters as $name => $config) {
            $html .= $this->renderFilterField($name, $config);
        }

        // Filter buttons
        $html .= '<div class="flex items-end gap-2">';
        $html .= '<button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg">';
        $html .= '<i data-lucide="filter" class="w-4 h-4 inline mr-1"></i> Filter';
        $html .= '</button>';
        $html .= '<button type="button" onclick="resetChartFilters(\'' . $chartId . '\')" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">';
        $html .= '<i data-lucide="x" class="w-4 h-4 inline mr-1"></i> Reset';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        // Add filter script
        $html .= $this->renderFilterScript($chart);

        return $html;
    }

    /**
     * Render individual filter field.
     *
     * @param string $name Field name
     * @param array $config Field configuration
     * @return string HTML field
     */
    protected function renderFilterField(string $name, array $config): string
    {
        $type = $config['type'] ?? 'text';
        $label = $config['label'] ?? ucfirst($name);
        $placeholder = $config['placeholder'] ?? '';
        $value = $config['value'] ?? '';

        $html = '<div>';
        $html .= '<label class="block text-sm font-medium mb-2">' . htmlspecialchars($label) . '</label>';

        switch ($type) {
            case 'select':
                $html .= '<select name="' . htmlspecialchars($name) . '" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">';
                $html .= '<option value="">All</option>';
                foreach ($config['options'] ?? [] as $optValue => $optLabel) {
                    $selected = $value == $optValue ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optValue) . '"' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'date':
                $html .= '<input type="date" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">';
                break;

            case 'daterange':
                $html .= '<div class="grid grid-cols-2 gap-2">';
                $html .= '<input type="date" name="' . htmlspecialchars($name) . '_start" placeholder="Start" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">';
                $html .= '<input type="date" name="' . htmlspecialchars($name) . '_end" placeholder="End" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">';
                $html .= '</div>';
                break;

            case 'text':
            default:
                $html .= '<input type="text" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" placeholder="' . htmlspecialchars($placeholder) . '" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">';
                break;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render filter form script.
     *
     * @param ChartBuilder $chart Chart instance
     * @return string JavaScript code
     */
    protected function renderFilterScript(ChartBuilder $chart): string
    {
        $containerId = $chart->getFilterContainerId();
        $chartId = $chart->getId();

        return <<<HTML
        <script>
        (function() {
            const form = document.getElementById('{$containerId}-form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(form);
                const filters = {};
                for (let [key, value] of formData.entries()) {
                    if (value) filters[key] = value;
                }

                // Reload chart with filters
                if (window.charts && window.charts['{$chartId}']) {
                    // Update AJAX params with filters
                    const chart = window.charts['{$chartId}'];
                    if (chart.loadData) {
                        // Store filters for next load
                        chart.filters = filters;
                        chart.loadData();
                    }
                }
            });

            // Reset filters function
            window.resetChartFilters = function(chartId) {
                form.reset();
                if (window.charts && window.charts[chartId]) {
                    const chart = window.charts[chartId];
                    chart.filters = {};
                    if (chart.loadData) {
                        chart.loadData();
                    }
                }
            };
        })();
        </script>
        HTML;
    }

    /**
     * Reset assets rendered flag (for testing).
     */
    public static function resetAssetsFlag(): void
    {
        self::$assetsRendered = false;
    }
}

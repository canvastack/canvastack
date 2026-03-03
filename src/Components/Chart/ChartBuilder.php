<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart;

use Canvastack\Canvastack\Components\Chart\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Chart\Renderers\PublicRenderer;
use Canvastack\Canvastack\Components\Chart\Renderers\RendererInterface;
use Illuminate\Support\Facades\Cache;

/**
 * ChartBuilder - Modern chart component with performance optimization.
 *
 * Provides chart rendering with ApexCharts library.
 * Supports Line, Bar, Pie, Donut, Area charts.
 * Includes data caching and transformation optimization.
 * Supports Admin and Public rendering strategies.
 *
 * Performance targets:
 * - < 100ms data preparation
 * - Data caching support
 * - Lazy loading capability
 */
class ChartBuilder
{
    protected RendererInterface $renderer;

    protected string $type = 'line';

    protected array $data = [];

    protected array $options = [];

    protected array $series = [];

    protected array $labels = [];

    protected string $context = 'admin';

    protected ?int $cacheTime = null;

    protected ?string $cacheKey = null;

    protected string $chartId;

    protected ?int $height = null;

    protected ?int $width = null;

    protected bool $responsive = true;

    protected array $colors = [];

    protected array $config = [];

    protected ?string $ajaxUrl = null;

    protected string $ajaxMethod = 'POST';

    protected array $ajaxParams = [];

    protected ?int $ajaxInterval = null;

    protected bool $ajaxAutoLoad = true;

    protected array $filters = [];

    protected bool $enableFiltering = false;

    protected string $filterContainerId = '';

    /**
     * Supported chart types.
     */
    protected const SUPPORTED_TYPES = [
        'line',
        'bar',
        'pie',
        'donut',
        'area',
        'radialBar',
        'scatter',
        'heatmap',
        'treemap',
        'boxPlot',
        'candlestick',
        'radar',
        'polarArea',
    ];

    /**
     * Default color palette (gradient colors from design system).
     */
    protected const DEFAULT_COLORS = [
        '#6366f1', // Indigo
        '#8b5cf6', // Purple
        '#a855f7', // Fuchsia
        '#ec4899', // Pink
        '#f43f5e', // Rose
        '#3b82f6', // Blue
        '#06b6d4', // Cyan
        '#10b981', // Emerald
        '#f59e0b', // Amber
        '#ef4444', // Red
    ];

    public function __construct()
    {
        $this->chartId = 'chart-' . uniqid();
        $this->setContext('admin'); // Default to admin context
        $this->colors = self::DEFAULT_COLORS;
    }

    /**
     * Set rendering context (admin or public).
     *
     * @param string $context The rendering context ('admin' or 'public')
     * @return self For method chaining
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        $this->renderer = $context === 'public'
            ? new PublicRenderer()
            : new AdminRenderer();

        return $this;
    }

    /**
     * Get current rendering context.
     *
     * @return string The current context ('admin' or 'public')
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Set chart type.
     *
     * @param string $type Chart type (line, bar, pie, donut, area, etc.)
     * @return self For method chaining
     * @throws \InvalidArgumentException If chart type is not supported
     */
    public function type(string $type): self
    {
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new \InvalidArgumentException(
                "Unsupported chart type: {$type}. Supported types: " .
                implode(', ', self::SUPPORTED_TYPES)
            );
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get chart type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set chart data (raw format).
     *
     * @param array $data Chart data
     * @return self For method chaining
     */
    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get chart data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set chart series (for line, bar, area charts).
     *
     * @param array $series Series data
     * @return self For method chaining
     */
    public function series(array $series): self
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Get chart series.
     *
     * @return array
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    /**
     * Set chart labels (for pie, donut charts or x-axis labels).
     *
     * @param array $labels Labels array
     * @return self For method chaining
     */
    public function labels(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Get chart labels.
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Set chart options (ApexCharts configuration).
     *
     * @param array $options ApexCharts options
     * @return self For method chaining
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Get chart options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set chart height.
     *
     * @param int $height Height in pixels
     * @return self For method chaining
     */
    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get chart height.
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Set chart width.
     *
     * @param int $width Width in pixels
     * @return self For method chaining
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get chart width.
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Enable or disable responsive mode.
     *
     * @param bool $responsive Responsive mode
     * @return self For method chaining
     */
    public function responsive(bool $responsive = true): self
    {
        $this->responsive = $responsive;

        return $this;
    }

    /**
     * Check if responsive mode is enabled.
     *
     * @return bool
     */
    public function isResponsive(): bool
    {
        return $this->responsive;
    }

    /**
     * Set custom color palette.
     *
     * @param array $colors Array of hex color codes
     * @return self For method chaining
     */
    public function colors(array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Get color palette.
     *
     * @return array
     */
    public function getColors(): array
    {
        return $this->colors;
    }

    /**
     * Set chart ID.
     *
     * @param string $id Chart element ID
     * @return self For method chaining
     */
    public function id(string $id): self
    {
        $this->chartId = $id;

        return $this;
    }

    /**
     * Get chart ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->chartId;
    }

    /**
     * Enable data caching.
     *
     * @param int $minutes Cache duration in minutes
     * @param string|null $key Custom cache key (auto-generated if null)
     * @return self For method chaining
     */
    public function cache(int $minutes, ?string $key = null): self
    {
        $this->cacheTime = $minutes;
        $this->cacheKey = $key ?? 'chart.' . $this->chartId;

        return $this;
    }

    /**
     * Get cache time.
     *
     * @return int|null
     */
    public function getCacheTime(): ?int
    {
        return $this->cacheTime;
    }

    /**
     * Get cache key.
     *
     * @return string|null
     */
    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }

    /**
     * Clear cached data.
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        if ($this->cacheKey) {
            return Cache::forget($this->cacheKey);
        }

        return false;
    }

    /**
     * Set configuration options.
     *
     * @param array $config Configuration array
     * @return self For method chaining
     */
    public function config(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Get configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Enable AJAX data loading.
     *
     * @param string $url API endpoint URL
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param array $params Additional parameters to send
     * @return self For method chaining
     */
    public function ajax(string $url, string $method = 'POST', array $params = []): self
    {
        $this->ajaxUrl = $url;
        $this->setMethod($method);
        $this->ajaxParams = $params;

        return $this;
    }

    /**
     * Set HTTP method for AJAX requests.
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @return self For method chaining
     * @throws \InvalidArgumentException If method is not supported
     */
    public function setMethod(string $method): self
    {
        $method = strtoupper($method);
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException(
                "Unsupported HTTP method: {$method}. Allowed: " . implode(', ', $allowedMethods)
            );
        }

        $this->ajaxMethod = $method;

        return $this;
    }

    /**
     * Get HTTP method for AJAX requests.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->ajaxMethod;
    }

    /**
     * Set AJAX parameters.
     *
     * @param array $params Parameters to send with AJAX request
     * @return self For method chaining
     */
    public function ajaxParams(array $params): self
    {
        $this->ajaxParams = array_merge($this->ajaxParams, $params);

        return $this;
    }

    /**
     * Get AJAX parameters.
     *
     * @return array
     */
    public function getAjaxParams(): array
    {
        return $this->ajaxParams;
    }

    /**
     * Enable real-time data updates.
     *
     * @param int $interval Update interval in seconds
     * @return self For method chaining
     */
    public function realtime(int $interval): self
    {
        $this->ajaxInterval = $interval;

        return $this;
    }

    /**
     * Get real-time update interval.
     *
     * @return int|null
     */
    public function getRealtimeInterval(): ?int
    {
        return $this->ajaxInterval;
    }

    /**
     * Disable auto-load on page load (manual trigger required).
     *
     * @param bool $autoLoad Auto-load on page load
     * @return self For method chaining
     */
    public function autoLoad(bool $autoLoad = true): self
    {
        $this->ajaxAutoLoad = $autoLoad;

        return $this;
    }

    /**
     * Check if auto-load is enabled.
     *
     * @return bool
     */
    public function isAutoLoad(): bool
    {
        return $this->ajaxAutoLoad;
    }

    /**
     * Get AJAX URL.
     *
     * @return string|null
     */
    public function getAjaxUrl(): ?string
    {
        return $this->ajaxUrl;
    }

    /**
     * Check if AJAX mode is enabled.
     *
     * @return bool
     */
    public function isAjaxMode(): bool
    {
        return $this->ajaxUrl !== null;
    }

    /**
     * Enable filtering with form inputs.
     *
     * @param array $filters Filter configuration
     * @param string|null $containerId Container ID for filter form
     * @return self For method chaining
     */
    public function withFilters(array $filters, ?string $containerId = null): self
    {
        $this->enableFiltering = true;
        $this->filters = $filters;
        $this->filterContainerId = $containerId ?? 'filter-' . $this->chartId;

        return $this;
    }

    /**
     * Add a single filter.
     *
     * @param string $name Filter name/key
     * @param string $type Filter type (select, date, text, daterange)
     * @param array $options Filter options
     * @return self For method chaining
     */
    public function addFilter(string $name, string $type, array $options = []): self
    {
        $this->enableFiltering = true;
        $this->filters[$name] = array_merge([
            'type' => $type,
            'label' => ucfirst(str_replace('_', ' ', $name)),
        ], $options);

        return $this;
    }

    /**
     * Get filters configuration.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Check if filtering is enabled.
     *
     * @return bool
     */
    public function hasFilters(): bool
    {
        return $this->enableFiltering && !empty($this->filters);
    }

    /**
     * Get filter container ID.
     *
     * @return string
     */
    public function getFilterContainerId(): string
    {
        return $this->filterContainerId ?: 'filter-' . $this->chartId;
    }

    /**
     * Create a line chart.
     *
     * @param array $series Series data
     * @param array $labels X-axis labels
     * @param array $options Additional options
     * @return self For method chaining
     */
    public function line(array $series, array $labels = [], array $options = []): self
    {
        return $this->type('line')
            ->series($series)
            ->labels($labels)
            ->options($options);
    }

    /**
     * Create a bar chart.
     *
     * @param array $series Series data
     * @param array $labels X-axis labels
     * @param array $options Additional options
     * @return self For method chaining
     */
    public function bar(array $series, array $labels = [], array $options = []): self
    {
        return $this->type('bar')
            ->series($series)
            ->labels($labels)
            ->options($options);
    }

    /**
     * Create a pie chart.
     *
     * @param array $data Data values
     * @param array $labels Slice labels
     * @param array $options Additional options
     * @return self For method chaining
     */
    public function pie(array $data, array $labels = [], array $options = []): self
    {
        return $this->type('pie')
            ->series($data)
            ->labels($labels)
            ->options($options);
    }

    /**
     * Create a donut chart.
     *
     * @param array $data Data values
     * @param array $labels Slice labels
     * @param array $options Additional options
     * @return self For method chaining
     */
    public function donut(array $data, array $labels = [], array $options = []): self
    {
        return $this->type('donut')
            ->series($data)
            ->labels($labels)
            ->options($options);
    }

    /**
     * Create an area chart.
     *
     * @param array $series Series data
     * @param array $labels X-axis labels
     * @param array $options Additional options
     * @return self For method chaining
     */
    public function area(array $series, array $labels = [], array $options = []): self
    {
        return $this->type('area')
            ->series($series)
            ->labels($labels)
            ->options($options);
    }

    /**
     * Check if chart has data configured.
     * 
     * Returns true if the chart has series data or labels configured.
     * Used by BaseController to determine if chart should be rendered.
     *
     * @return bool True if chart has data, false otherwise
     */
    public function hasData(): bool
    {
        // Check if series has data
        if (!empty($this->series)) {
            return true;
        }
        
        // Check if labels has data (for pie/donut charts)
        if (!empty($this->labels)) {
            return true;
        }
        
        // Check if data array has data
        if (!empty($this->data)) {
            return true;
        }
        
        return false;
    }

    /**
     * Render the chart.
     *
     * @return string HTML output
     */
    public function render(): string
    {
        // Check cache first
        if ($this->cacheTime && $this->cacheKey) {
            $cached = Cache::get($this->cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Render chart
        $output = $this->renderer->render($this);

        // Cache output if enabled
        if ($this->cacheTime && $this->cacheKey) {
            Cache::put($this->cacheKey, $output, now()->addMinutes($this->cacheTime));
        }

        return $output;
    }

    /**
     * Convert chart to JSON configuration.
     *
     * @return string JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Convert chart to array configuration.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->chartId,
            'type' => $this->type,
            'series' => $this->series,
            'labels' => $this->labels,
            'options' => $this->buildOptions(),
        ];
    }

    /**
     * Build complete ApexCharts options.
     *
     * @return array
     */
    protected function buildOptions(): array
    {
        $options = $this->options;

        // Set chart type
        $options['chart'] = array_merge($options['chart'] ?? [], [
            'type' => $this->type,
            'height' => $this->height ?? 350,
            'width' => $this->width ?? '100%',
        ]);

        // Set colors
        if (!isset($options['colors'])) {
            $options['colors'] = $this->colors;
        }

        // Set labels for pie/donut charts
        if (in_array($this->type, ['pie', 'donut']) && !empty($this->labels)) {
            $options['labels'] = $this->labels;
        }

        // Set x-axis labels for other charts
        if (!in_array($this->type, ['pie', 'donut']) && !empty($this->labels)) {
            $options['xaxis'] = array_merge($options['xaxis'] ?? [], [
                'categories' => $this->labels,
            ]);
        }

        // Set responsive options
        if ($this->responsive) {
            $options['responsive'] = $options['responsive'] ?? [
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

        // Set dark mode theme
        $options['theme'] = array_merge($options['theme'] ?? [], [
            'mode' => 'light', // Will be toggled by JavaScript
        ]);

        return $options;
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Tab;

/**
 * Tab - Represents a single tab in the TableBuilder
 * 
 * This class manages table instances, custom content, and configuration for a single tab.
 * 
 * @package Canvastack\Canvastack\Components\Table\Tab
 * @version 1.0.0
 */
class Tab
{
    /**
     * Tab display name
     * 
     * @var string
     */
    protected string $name;

    /**
     * Tab unique ID
     * 
     * @var string
     */
    protected string $id;

    /**
     * Array of TableInstance objects
     * 
     * @var array<TableInstance>
     */
    protected array $tables = [];

    /**
     * Array of HTML content blocks
     * 
     * @var array<string>
     */
    protected array $content = [];

    /**
     * Array of ChartBuilder instances
     * 
     * @var array<\Canvastack\Canvastack\Components\Chart\ChartBuilder>
     */
    protected array $charts = [];

    /**
     * Tab configuration
     * 
     * @var array
     */
    protected array $config = [];

    /**
     * Whether this tab should be lazy loaded (Requirement 32.7)
     * 
     * @var bool
     */
    protected bool $lazyLoad = false;

    /**
     * AJAX URL for lazy loading content (Requirement 32.7)
     * 
     * @var string|null
     */
    protected ?string $lazyLoadUrl = null;

    /**
     * Whether tab content has been loaded (Requirement 32.7)
     * 
     * @var bool
     */
    protected bool $isLoaded = false;

    /**
     * Constructor
     * 
     * @param string $name Tab display name
     * @param string $id Tab unique ID
     */
    public function __construct(string $name, string $id)
    {
        $this->name = $name;
        $this->id = $id;
    }

    /**
     * Get tab name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get tab ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Add a table instance to this tab
     * 
     * @param TableInstance $table Table instance to add
     * @return void
     */
    public function addTable(TableInstance $table): void
    {
        $this->tables[] = $table;
    }

    /**
     * Get all table instances
     * 
     * @return array<TableInstance>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Add HTML content to this tab
     * 
     * @param string $html HTML content to add
     * @return void
     */
    public function addContent(string $html): void
    {
        $this->content[] = $html;
    }

    /**
     * Get all content blocks
     * 
     * @return array<string>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Add chart to tab
     * 
     * @param \Canvastack\Canvastack\Components\Chart\ChartBuilder $chart Chart instance
     * @return void
     */
    public function addChart(\Canvastack\Canvastack\Components\Chart\ChartBuilder $chart): void
    {
        $this->charts[] = $chart;
    }

    /**
     * Get all charts
     * 
     * @return array<\Canvastack\Canvastack\Components\Chart\ChartBuilder>
     */
    public function getCharts(): array
    {
        return $this->charts;
    }

    /**
     * Check if tab has charts
     * 
     * @return bool
     */
    public function hasCharts(): bool
    {
        return !empty($this->charts);
    }

    /**
     * Set tab configuration
     * 
     * @param array $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get tab configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Render the tab content
     * 
     * @return string Rendered HTML
     */
    public function render(): string
    {
        $html = '';

        // Render custom content
        foreach ($this->content as $contentBlock) {
            $html .= $contentBlock;
        }

        // Render charts
        foreach ($this->charts as $chart) {
            $html .= '<div class="mb-6">';
            $html .= $chart->render();
            $html .= '</div>';
        }

        // Render tables
        foreach ($this->tables as $table) {
            $html .= $table->render();
        }

        return $html;
    }

    /**
     * Convert tab to array for JSON serialization
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'id' => $this->id,
            'tables' => array_map(fn($table) => $table->toArray(), $this->tables),
            'charts' => array_map(fn($chart) => $chart->toArray(), $this->charts),
            'content' => $this->content,
            'config' => $this->config,
        ];
    }

    /**
     * Get keyboard navigation attributes for accessibility (Requirement 32.5)
     * 
     * @param bool $isActive Whether this tab is currently active
     * @param int $index Tab index (0-based)
     * @return array Attributes for tab button
     */
    public function getKeyboardAttributes(bool $isActive, int $index): array
    {
        return [
            'role' => 'tab',
            'aria-selected' => $isActive ? 'true' : 'false',
            'aria-controls' => 'tabpanel-' . $this->id,
            'id' => 'tab-' . $this->id,
            'tabindex' => $isActive ? '0' : '-1',
            'data-tab-index' => (string) $index,
        ];
    }

    /**
     * Get keyboard navigation attributes for tab panel (Requirement 32.5)
     * 
     * @param bool $isActive Whether this tab is currently active
     * @return array Attributes for tab panel
     */
    public function getPanelAttributes(bool $isActive): array
    {
        return [
            'role' => 'tabpanel',
            'id' => 'tabpanel-' . $this->id,
            'aria-labelledby' => 'tab-' . $this->id,
            'tabindex' => '0',
            'hidden' => !$isActive,
        ];
    }

    /**
     * Enable lazy loading for this tab (Requirement 32.7)
     * 
     * @param string $url AJAX URL to load content from
     * @return void
     */
    public function enableLazyLoad(string $url): void
    {
        $this->lazyLoad = true;
        $this->lazyLoadUrl = $url;
        $this->isLoaded = false;
    }

    /**
     * Disable lazy loading for this tab (Requirement 32.7)
     * 
     * @return void
     */
    public function disableLazyLoad(): void
    {
        $this->lazyLoad = false;
        $this->lazyLoadUrl = null;
        $this->isLoaded = true;
    }

    /**
     * Check if tab is lazy loaded (Requirement 32.7)
     * 
     * @return bool
     */
    public function isLazyLoaded(): bool
    {
        return $this->lazyLoad;
    }

    /**
     * Get lazy load URL (Requirement 32.7)
     * 
     * @return string|null
     */
    public function getLazyLoadUrl(): ?string
    {
        return $this->lazyLoadUrl;
    }

    /**
     * Check if tab content has been loaded (Requirement 32.7)
     * 
     * @return bool
     */
    public function isContentLoaded(): bool
    {
        return $this->isLoaded;
    }

    /**
     * Mark tab content as loaded (Requirement 32.7)
     * 
     * @return void
     */
    public function markAsLoaded(): void
    {
        $this->isLoaded = true;
    }

    /**
     * Get lazy loading attributes for tab panel (Requirement 32.7)
     * 
     * @return array Attributes for lazy loading
     */
    public function getLazyLoadAttributes(): array
    {
        if (!$this->lazyLoad) {
            return [];
        }

        return [
            'data-lazy-load' => 'true',
            'data-lazy-url' => $this->lazyLoadUrl,
            'data-loaded' => $this->isLoaded ? 'true' : 'false',
        ];
    }
}

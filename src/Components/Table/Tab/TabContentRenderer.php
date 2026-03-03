<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Tab;

use Illuminate\Support\Facades\View;

/**
 * TabContentRenderer - Handles rendering of tab content
 * 
 * This class is responsible for rendering tab content including custom HTML
 * and table instances with proper responsive design and accessibility.
 * 
 * @package Canvastack\Canvastack\Components\Table\Tab
 * @version 1.0.0
 */
class TabContentRenderer
{
    /**
     * Render context (admin or public)
     * 
     * @var string
     */
    protected string $context = 'admin';

    /**
     * Enable responsive design
     * 
     * @var bool
     */
    protected bool $responsive = true;

    /**
     * Table ID for unique identification
     * 
     * @var string
     */
    protected string $tableId;

    /**
     * Constructor
     * 
     * @param string $tableId Unique table identifier
     * @param string $context Render context (admin or public)
     */
    public function __construct(string $tableId = 'table', string $context = 'admin')
    {
        $this->tableId = $tableId;
        $this->context = $context;
    }

    /**
     * Set render context
     * 
     * @param string $context Context (admin or public)
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get render context
     * 
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Enable or disable responsive design
     * 
     * @param bool $responsive Enable responsive design
     * @return self
     */
    public function setResponsive(bool $responsive): self
    {
        $this->responsive = $responsive;
        return $this;
    }

    /**
     * Check if responsive design is enabled
     * 
     * @return bool
     */
    public function isResponsive(): bool
    {
        return $this->responsive;
    }

    /**
     * Render a single tab's content
     * 
     * @param Tab $tab Tab instance to render
     * @param bool $isActive Whether this tab is currently active
     * @return string Rendered HTML
     */
    public function renderTabContent(Tab $tab, bool $isActive = false): string
    {
        $tabData = $tab->toArray();
        
        return View::make('canvastack::components.table.tab-content', [
            'tab' => $tabData,
            'isActive' => $isActive,
            'tableId' => $this->tableId,
        ])->render();
    }

    /**
     * Render all tabs with navigation
     * 
     * @param TabManager $tabManager Tab manager instance
     * @return string Rendered HTML
     */
    public function renderTabContainer(TabManager $tabManager): string
    {
        $tabs = $tabManager->getTabsArray();
        $activeTab = $tabManager->getActiveTab();

        return View::make('canvastack::components.table.tab-container', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'tableId' => $this->tableId,
            'responsive' => $this->responsive,
        ])->render();
    }

    /**
     * Render a table instance
     * 
     * @param TableInstance $table Table instance to render
     * @return string Rendered HTML
     */
    public function renderTableInstance(TableInstance $table): string
    {
        return $table->render();
    }

    /**
     * Render custom content block
     * 
     * @param string $content HTML content
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function renderContentBlock(string $content, array $options = []): string
    {
        $wrapperClass = $options['class'] ?? 'content-block mb-4';
        $sanitize = $options['sanitize'] ?? false;

        // Sanitize content if requested
        if ($sanitize) {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        return sprintf(
            '<div class="%s">%s</div>',
            htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8'),
            $content
        );
    }

    /**
     * Render empty state for a tab
     * 
     * @param string $message Empty state message
     * @return string Rendered HTML
     */
    public function renderEmptyState(string $message = 'No content available'): string
    {
        return View::make('canvastack::components.table.tab-empty-state', [
            'message' => $message,
        ])->render();
    }

    /**
     * Render loading placeholder
     * 
     * @param string $message Loading message
     * @return string Rendered HTML
     */
    public function renderLoadingPlaceholder(string $message = 'Loading...'): string
    {
        return sprintf(
            '<div class="table-loading flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 dark:border-primary-400 mb-4"></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">%s</p>
                </div>
            </div>',
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Render tab navigation only
     * 
     * @param TabManager $tabManager Tab manager instance
     * @return string Rendered HTML
     */
    public function renderTabNavigation(TabManager $tabManager): string
    {
        $tabs = $tabManager->getTabsArray();
        $activeTab = $tabManager->getActiveTab();

        return View::make('canvastack::components.table.tab-navigation', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'tableId' => $this->tableId,
        ])->render();
    }

    /**
     * Render multiple table instances
     * 
     * @param array $tables Array of TableInstance objects
     * @return string Rendered HTML
     */
    public function renderTableInstances(array $tables): string
    {
        $html = '<div class="tab-tables-container space-y-6">';

        foreach ($tables as $index => $table) {
            if ($table instanceof TableInstance) {
                $html .= sprintf(
                    '<div class="table-wrapper" data-table-index="%d">%s</div>',
                    $index,
                    $this->renderTableInstance($table)
                );
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render content blocks
     * 
     * @param array $contentBlocks Array of HTML content strings
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function renderContentBlocks(array $contentBlocks, array $options = []): string
    {
        if (empty($contentBlocks)) {
            return '';
        }

        $html = '<div class="tab-custom-content mb-6">';

        foreach ($contentBlocks as $index => $content) {
            $html .= $this->renderContentBlock($content, array_merge($options, [
                'class' => 'content-block mb-4 last:mb-0'
            ]));
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get responsive CSS classes
     * 
     * @return string CSS classes
     */
    public function getResponsiveClasses(): string
    {
        if (!$this->responsive) {
            return '';
        }

        return 'responsive w-full overflow-x-auto';
    }

    /**
     * Get context-specific CSS classes
     * 
     * @return string CSS classes
     */
    public function getContextClasses(): string
    {
        return match ($this->context) {
            'admin' => 'admin-context',
            'public' => 'public-context',
            default => '',
        };
    }

    /**
     * Wrap content in responsive container
     * 
     * @param string $content Content to wrap
     * @return string Wrapped HTML
     */
    public function wrapInResponsiveContainer(string $content): string
    {
        if (!$this->responsive) {
            return $content;
        }

        return sprintf(
            '<div class="responsive-container %s">%s</div>',
            $this->getResponsiveClasses(),
            $content
        );
    }

    /**
     * Generate data attributes for tab content
     * 
     * @param Tab $tab Tab instance
     * @return string HTML data attributes
     */
    public function generateDataAttributes(Tab $tab): string
    {
        $attributes = [
            'data-tab-id' => $tab->getId(),
            'data-tab-name' => $tab->getName(),
            'data-table-count' => count($tab->getTables()),
            'data-content-count' => count($tab->getContent()),
        ];

        return implode(' ', array_map(
            fn($key, $value) => sprintf('%s="%s"', $key, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')),
            array_keys($attributes),
            $attributes
        ));
    }

    /**
     * Validate tab data before rendering
     * 
     * @param Tab $tab Tab instance to validate
     * @return bool
     * @throws \InvalidArgumentException If validation fails
     */
    public function validateTab(Tab $tab): bool
    {
        if (empty($tab->getName())) {
            throw new \InvalidArgumentException('Tab name cannot be empty');
        }

        if (empty($tab->getId())) {
            throw new \InvalidArgumentException('Tab ID cannot be empty');
        }

        return true;
    }

    /**
     * Get table ID
     * 
     * @return string
     */
    public function getTableId(): string
    {
        return $this->tableId;
    }

    /**
     * Set table ID
     * 
     * @param string $tableId Table ID
     * @return self
     */
    public function setTableId(string $tableId): self
    {
        $this->tableId = $tableId;
        return $this;
    }
}

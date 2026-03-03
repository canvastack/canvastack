<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;

/**
 * TabRenderingTrait - Provides tab rendering functionality for renderers.
 *
 * Implements tab navigation and content rendering with Alpine.js integration,
 * DaisyUI styling, and dark mode support.
 */
trait TabRenderingTrait
{
    /**
     * Render tabs with navigation and content.
     *
     * @param array<Tab> $tabs Array of Tab instances
     * @param array<string, mixed> $errors Validation errors
     * @return string Rendered HTML
     */
    public function renderTabs(array $tabs, array $errors = []): string
    {
        if (empty($tabs)) {
            return '';
        }

        $activeTabId = $this->getActiveTabId($tabs, $errors);
        $navigation = $this->renderTabNavigation($tabs, $errors);
        $content = $this->renderTabContent($tabs);

        return <<<HTML
        <div class="tabs-container mb-6" x-data="{ activeTab: '{$activeTabId}' }">
            {$navigation}
            {$content}
        </div>
        HTML;
    }

    /**
     * Render tab navigation.
     *
     * @param array<Tab> $tabs Array of Tab instances
     * @param array<string, mixed> $errors Validation errors
     * @return string Rendered navigation HTML
     */
    protected function renderTabNavigation(array $tabs, array $errors = []): string
    {
        $items = [];

        foreach ($tabs as $index => $tab) {
            $items[] = $this->renderTabNavItem($tab, $index, $errors);
        }

        $itemsHtml = implode("\n", $items);

        return <<<HTML
        <div role="tablist" class="tabs tabs-bordered border-b border-gray-200 dark:border-gray-700">
            {$itemsHtml}
        </div>
        HTML;
    }

    /**
     * Render single tab navigation item.
     *
     * @param Tab $tab Tab instance
     * @param int $index Tab index
     * @param array<string, mixed> $errors Validation errors
     * @return string Rendered navigation item HTML
     */
    protected function renderTabNavItem(Tab $tab, int $index, array $errors = []): string
    {
        $id = $tab->getId();
        $label = htmlspecialchars($tab->getLabel());
        $hasErrors = $tab->hasErrors($errors);

        // Build CSS classes
        $classes = ['tab', 'tab-lg'];

        // Add active class if this is the active tab
        if ($tab->isActive()) {
            $classes[] = 'tab-active';
        }

        // Add error styling if tab contains errors
        if ($hasErrors) {
            $classes[] = 'text-red-600 dark:text-red-400';
        }

        $classString = implode(' ', $classes);

        // Add error indicator badge
        $errorBadge = $hasErrors
            ? '<span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">!</span>'
            : '';

        return <<<HTML
        <a 
            role="tab" 
            class="{$classString}" 
            :class="{ 'tab-active': activeTab === '{$id}' }"
            @click.prevent="activeTab = '{$id}'"
            href="#{$id}"
            aria-controls="{$id}"
            :aria-selected="activeTab === '{$id}' ? 'true' : 'false'"
        >
            {$label}{$errorBadge}
        </a>
        HTML;
    }

    /**
     * Render tab content panels.
     *
     * @param array<Tab> $tabs Array of Tab instances
     * @return string Rendered content HTML
     */
    protected function renderTabContent(array $tabs): string
    {
        $panels = [];

        foreach ($tabs as $tab) {
            $panels[] = $this->renderTabPanel($tab);
        }

        $panelsHtml = implode("\n", $panels);

        return <<<HTML
        <div class="tab-content mt-6">
            {$panelsHtml}
        </div>
        HTML;
    }

    /**
     * Render single tab content panel.
     *
     * @param Tab $tab Tab instance
     * @return string Rendered panel HTML
     */
    protected function renderTabPanel(Tab $tab): string
    {
        $id = $tab->getId();
        $fieldsHtml = $this->renderTabFields($tab);
        $contentHtml = $this->renderTabCustomContent($tab);

        return <<<HTML
        <div 
            id="{$id}" 
            role="tabpanel"
            x-show="activeTab === '{$id}'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="tab-panel"
            :aria-hidden="activeTab !== '{$id}' ? 'true' : 'false'"
        >
            {$fieldsHtml}
            {$contentHtml}
        </div>
        HTML;
    }

    /**
     * Render fields within a tab.
     *
     * @param Tab $tab Tab instance
     * @return string Rendered fields HTML
     */
    protected function renderTabFields(Tab $tab): string
    {
        $html = '';

        foreach ($tab->getFields() as $field) {
            $html .= $this->render($field);
        }

        return $html;
    }

    /**
     * Render custom HTML content within a tab.
     *
     * @param Tab $tab Tab instance
     * @return string Rendered custom content HTML
     */
    protected function renderTabCustomContent(Tab $tab): string
    {
        return implode("\n", $tab->getContent());
    }

    /**
     * Get the ID of the active tab.
     *
     * If validation errors exist, returns the first tab with errors.
     * Otherwise, returns the first tab marked as active, or the first tab.
     *
     * @param array<Tab> $tabs Array of Tab instances
     * @param array<string, mixed> $errors Validation errors
     * @return string Active tab ID
     */
    protected function getActiveTabId(array $tabs, array $errors = []): string
    {
        // If there are validation errors, activate the first tab with errors
        if (!empty($errors)) {
            foreach ($tabs as $tab) {
                if ($tab->hasErrors($errors)) {
                    return $tab->getId();
                }
            }
        }

        // Otherwise, find the tab marked as active
        foreach ($tabs as $tab) {
            if ($tab->isActive()) {
                return $tab->getId();
            }
        }

        // Default to first tab
        return $tabs[0]->getId();
    }
}

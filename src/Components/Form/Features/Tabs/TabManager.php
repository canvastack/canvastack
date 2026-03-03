<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Canvastack\Canvastack\Components\Form\Renderers\RendererInterface;

/**
 * TabManager - Manages tab creation, field association, and rendering.
 *
 * Responsibilities:
 * - Create and manage tabs
 * - Associate fields with active tab
 * - Track active tab state
 * - Provide tab rendering interface
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4
 */
class TabManager
{
    /**
     * @var Tab[] Array of tab instances
     */
    protected array $tabs = [];

    /**
     * @var Tab|null Currently active tab
     */
    protected ?Tab $activeTab = null;

    /**
     * @var RendererInterface Renderer for tab output
     */
    protected RendererInterface $renderer;

    /**
     * Create a new TabManager instance.
     *
     * @param RendererInterface $renderer Renderer for tab output
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Open a new tab section.
     *
     * Creates a new tab with the specified label and optional CSS class.
     * The newly created tab becomes the active tab for field association.
     *
     * Requirement 1.1: Create new tab section with label and class
     *
     * @param string $label Tab label displayed in navigation
     * @param string|bool $class Optional CSS class or 'active' flag
     * @return Tab The created tab instance
     */
    public function openTab(string $label, string|bool $class = false): Tab
    {
        $tab = new Tab($label, $class);
        $this->tabs[] = $tab;
        $this->activeTab = $tab;

        return $tab;
    }

    /**
     * Close the current active tab.
     *
     * Clears the active tab reference, preparing for the next tab
     * or regular field additions.
     *
     * Requirement 1.3: Close current tab section
     *
     * @return void
     */
    public function closeTab(): void
    {
        $this->activeTab = null;
    }

    /**
     * Add a field to the active tab.
     *
     * Associates a field with the currently active tab. If no tab is active,
     * the field is not added to any tab.
     *
     * Requirement 1.2: Associate fields with active tab
     *
     * @param BaseField $field Field to add to active tab
     * @return void
     */
    public function addFieldToActiveTab(BaseField $field): void
    {
        if ($this->activeTab !== null) {
            $this->activeTab->addField($field);
        }
    }

    /**
     * Add custom HTML content to active tab.
     *
     * Allows adding arbitrary HTML content to the currently active tab.
     * Useful for custom layouts, instructions, or non-field content.
     *
     * Requirement 1.4: Add custom HTML content to tab
     *
     * @param string $html HTML content to add
     * @return void
     */
    public function addTabContent(string $html): void
    {
        if ($this->activeTab !== null) {
            $this->activeTab->addContent($html);
        }
    }

    /**
     * Check if any tabs are defined.
     *
     * @return bool True if tabs exist, false otherwise
     */
    public function hasTabs(): bool
    {
        return count($this->tabs) > 0;
    }

    /**
     * Get all tabs.
     *
     * @return Tab[] Array of tab instances
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Get the currently active tab.
     *
     * @return Tab|null Active tab or null if no tab is active
     */
    public function getActiveTab(): ?Tab
    {
        return $this->activeTab;
    }

    /**
     * Render all tabs.
     *
     * Delegates rendering to the configured renderer if tabs exist.
     * Returns empty string if no tabs are defined.
     *
     * @return string Rendered HTML for all tabs
     */
    public function render(): string
    {
        if (!$this->hasTabs()) {
            return '';
        }

        return $this->renderer->renderTabs($this->tabs);
    }

    /**
     * Get tab containing validation errors.
     *
     * Searches through all tabs to find the first tab that contains
     * fields with validation errors. Used for error highlighting.
     *
     * Requirement 1.11: Highlight tabs containing errors
     *
     * @param array<string, mixed> $errors Validation errors array
     * @return Tab|null Tab with errors or null if none found
     */
    public function getTabWithErrors(array $errors): ?Tab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->hasErrors($errors)) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * Reset tab manager state.
     *
     * Clears all tabs and active tab reference.
     * Useful for testing or reusing the manager instance.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->tabs = [];
        $this->activeTab = null;
    }
}

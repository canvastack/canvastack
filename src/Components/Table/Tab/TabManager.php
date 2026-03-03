<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Tab;

/**
 * TabManager - Manages multiple tabs for TableBuilder
 * 
 * This class handles the creation, management, and state of tabs in the TableBuilder.
 * It supports multiple tables per tab, custom content, and configuration isolation.
 * 
 * @package Canvastack\Canvastack\Components\Table\Tab
 * @version 1.0.0
 */
class TabManager
{
    /**
     * Array of Tab instances
     * 
     * @var array<string, Tab>
     */
    protected array $tabs = [];

    /**
     * Currently active tab ID
     * 
     * @var string|null
     */
    protected ?string $activeTab = null;

    /**
     * Current tab being built (for openTab/closeTab workflow)
     * 
     * @var Tab|null
     */
    protected ?Tab $currentTab = null;

    /**
     * Tab content buffer (for addContent calls)
     * 
     * @var array<string, array<string>>
     */
    protected array $tabContent = [];

    /**
     * Tab configuration buffer
     * 
     * @var array<string, array>
     */
    protected array $tabConfig = [];

    /**
     * Open a new tab or switch to existing tab
     * 
     * @param string $name Tab display name
     * @return void
     */
    public function openTab(string $name): void
    {
        // Generate unique ID from name
        $id = $this->generateTabId($name);

        // Check if tab already exists
        if (isset($this->tabs[$id])) {
            $this->currentTab = $this->tabs[$id];
        } else {
            // Create new tab
            $this->currentTab = new Tab($name, $id);
            $this->tabs[$id] = $this->currentTab;

            // Set as active if it's the first tab
            if ($this->activeTab === null) {
                $this->activeTab = $id;
            }
        }

        // Initialize content buffer for this tab if not exists
        if (!isset($this->tabContent[$id])) {
            $this->tabContent[$id] = [];
        }

        // Initialize config buffer for this tab if not exists
        if (!isset($this->tabConfig[$id])) {
            $this->tabConfig[$id] = [];
        }
    }

    /**
     * Close the current tab
     * 
     * @return void
     */
    public function closeTab(): void
    {
        if ($this->currentTab !== null) {
            // Apply buffered content to the tab
            $tabId = $this->currentTab->getId();
            if (isset($this->tabContent[$tabId]) && !empty($this->tabContent[$tabId])) {
                foreach ($this->tabContent[$tabId] as $content) {
                    $this->currentTab->addContent($content);
                }
            }

            // Apply buffered config to the tab
            if (isset($this->tabConfig[$tabId]) && !empty($this->tabConfig[$tabId])) {
                $this->currentTab->setConfig($this->tabConfig[$tabId]);
            }

            // Clear current tab reference
            $this->currentTab = null;
        }
    }

    /**
     * Add HTML content to the current tab
     * 
     * @param string $html HTML content to add
     * @return void
     * @throws \RuntimeException if no tab is currently open
     */
    public function addContent(string $html): void
    {
        if ($this->currentTab === null) {
            throw new \RuntimeException('Cannot add content: No tab is currently open. Call openTab() first.');
        }

        $tabId = $this->currentTab->getId();
        $this->tabContent[$tabId][] = $html;
    }

    /**
     * Add a table instance to the current tab
     * 
     * @param TableInstance $table Table instance to add
     * @return void
     * @throws \RuntimeException if no tab is currently open
     */
    public function addTableToCurrentTab(TableInstance $table): void
    {
        if ($this->currentTab === null) {
            throw new \RuntimeException('Cannot add table: No tab is currently open. Call openTab() first.');
        }

        $this->currentTab->addTable($table);
    }

    /**
     * Add chart to current tab
     * 
     * @param \Canvastack\Canvastack\Components\Chart\ChartBuilder $chart Chart instance
     * @return void
     * @throws \RuntimeException if no tab is currently open
     */
    public function addChart(\Canvastack\Canvastack\Components\Chart\ChartBuilder $chart): void
    {
        if ($this->currentTab === null) {
            throw new \RuntimeException('Cannot add chart: No tab is currently open. Call openTab() first.');
        }

        $this->currentTab->addChart($chart);
    }

    /**
     * Clear configuration for the current tab
     * 
     * @return void
     */
    public function clearConfig(): void
    {
        if ($this->currentTab !== null) {
            $tabId = $this->currentTab->getId();
            $this->tabConfig[$tabId] = [];
            $this->currentTab->setConfig([]);
        }
    }

    /**
     * Set configuration for the current tab
     * 
     * @param array $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void
    {
        if ($this->currentTab !== null) {
            $tabId = $this->currentTab->getId();
            $this->tabConfig[$tabId] = array_merge(
                $this->tabConfig[$tabId] ?? [],
                $config
            );
        }
    }

    /**
     * Get all tabs
     * 
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Get tabs as array for JSON serialization
     * 
     * @return array
     */
    public function getTabsArray(): array
    {
        $result = [];
        foreach ($this->tabs as $id => $tab) {
            $result[] = $tab->toArray();
        }
        return $result;
    }

    /**
     * Get the active tab ID
     * 
     * @return string|null
     */
    public function getActiveTab(): ?string
    {
        return $this->activeTab;
    }

    /**
     * Set the active tab
     * 
     * @param string $tabId Tab ID to set as active
     * @return void
     * @throws \InvalidArgumentException if tab ID doesn't exist
     */
    public function setActiveTab(string $tabId): void
    {
        if (!isset($this->tabs[$tabId])) {
            throw new \InvalidArgumentException("Tab with ID '{$tabId}' does not exist.");
        }

        $this->activeTab = $tabId;
    }

    /**
     * Get the current tab being built
     * 
     * @return Tab|null
     */
    public function getCurrentTab(): ?Tab
    {
        return $this->currentTab;
    }

    /**
     * Check if any tabs exist
     * 
     * @return bool
     */
    public function hasTabs(): bool
    {
        return !empty($this->tabs);
    }

    /**
     * Get the number of tabs
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->tabs);
    }

    /**
     * Clear all tabs
     * 
     * @return void
     */
    public function clearAll(): void
    {
        $this->tabs = [];
        $this->activeTab = null;
        $this->currentTab = null;
        $this->tabContent = [];
        $this->tabConfig = [];
    }

    /**
     * Generate a unique tab ID from the tab name
     * 
     * @param string $name Tab name
     * @return string Unique tab ID
     */
    protected function generateTabId(string $name): string
    {
        // Convert to lowercase, replace spaces with hyphens, remove special chars
        $id = strtolower($name);
        $id = preg_replace('/[^a-z0-9\s-]/', '', $id);
        $id = preg_replace('/[\s-]+/', '-', $id);
        $id = trim($id, '-');

        return $id;
    }

    /**
     * Get a specific tab by ID
     * 
     * @param string $tabId Tab ID
     * @return Tab|null
     */
    public function getTab(string $tabId): ?Tab
    {
        return $this->tabs[$tabId] ?? null;
    }

    /**
     * Check if a tab exists
     * 
     * @param string $tabId Tab ID
     * @return bool
     */
    public function hasTab(string $tabId): bool
    {
        return isset($this->tabs[$tabId]);
    }

    /**
     * Remove a tab
     * 
     * @param string $tabId Tab ID to remove
     * @return void
     */
    public function removeTab(string $tabId): void
    {
        if (isset($this->tabs[$tabId])) {
            unset($this->tabs[$tabId]);
            unset($this->tabContent[$tabId]);
            unset($this->tabConfig[$tabId]);

            // If we removed the active tab, set a new active tab
            if ($this->activeTab === $tabId) {
                $this->activeTab = !empty($this->tabs) ? array_key_first($this->tabs) : null;
            }

            // If we removed the current tab, clear it
            if ($this->currentTab !== null && $this->currentTab->getId() === $tabId) {
                $this->currentTab = null;
            }
        }
    }
}

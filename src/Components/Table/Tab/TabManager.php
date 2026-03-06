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
     * Set active tab from URL parameter (Requirement 32.4)
     * 
     * @param string|null $tabId Tab ID from URL
     * @return bool True if tab was set, false if tab doesn't exist
     */
    public function setActiveTabFromUrl(?string $tabId): bool
    {
        if ($tabId === null || !isset($this->tabs[$tabId])) {
            return false;
        }

        $this->activeTab = $tabId;
        return true;
    }

    /**
     * Get active tab for URL persistence (Requirement 32.4)
     * 
     * @return string|null Active tab ID for URL parameter
     */
    public function getActiveTabForUrl(): ?string
    {
        return $this->activeTab;
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

    /**
     * Get JavaScript for keyboard navigation (Requirement 32.5)
     * 
     * Implements ARIA keyboard navigation pattern:
     * - Arrow Left/Right: Navigate between tabs
     * - Home: Go to first tab
     * - End: Go to last tab
     * - Enter/Space: Activate focused tab
     * 
     * @param string $containerId Container element ID
     * @return string JavaScript code
     */
    public function getKeyboardNavigationScript(string $containerId): string
    {
        return <<<JS
(function() {
    const container = document.getElementById('{$containerId}');
    if (!container) return;
    
    const tabList = container.querySelector('[role="tablist"]');
    if (!tabList) return;
    
    const tabs = Array.from(tabList.querySelectorAll('[role="tab"]'));
    
    tabList.addEventListener('keydown', function(e) {
        const currentTab = document.activeElement;
        const currentIndex = tabs.indexOf(currentTab);
        
        if (currentIndex === -1) return;
        
        let targetIndex = currentIndex;
        
        switch(e.key) {
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                targetIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                break;
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                targetIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                break;
            case 'Home':
                e.preventDefault();
                targetIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                targetIndex = tabs.length - 1;
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                currentTab.click();
                return;
            default:
                return;
        }
        
        // Focus and activate target tab
        tabs[targetIndex].focus();
        tabs[targetIndex].click();
    });
})();
JS;
    }

    /**
     * Get Alpine.js data for keyboard navigation (Requirement 32.5)
     * 
     * @return array Alpine.js data object
     */
    public function getAlpineKeyboardData(): array
    {
        return [
            'handleKeydown' => '(event) => {
                const tabs = Array.from($refs.tablist.querySelectorAll(\'[role="tab"]\'));
                const currentIndex = tabs.indexOf(event.target);
                
                let targetIndex = currentIndex;
                
                switch(event.key) {
                    case \'ArrowLeft\':
                    case \'ArrowUp\':
                        event.preventDefault();
                        targetIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                        break;
                    case \'ArrowRight\':
                    case \'ArrowDown\':
                        event.preventDefault();
                        targetIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                        break;
                    case \'Home\':
                        event.preventDefault();
                        targetIndex = 0;
                        break;
                    case \'End\':
                        event.preventDefault();
                        targetIndex = tabs.length - 1;
                        break;
                    case \'Enter\':
                    case \' \':
                        event.preventDefault();
                        event.target.click();
                        return;
                    default:
                        return;
                }
                
                tabs[targetIndex].focus();
                tabs[targetIndex].click();
            }',
        ];
    }

    /**
     * Enable lazy loading for a specific tab (Requirement 32.7)
     * 
     * @param string $tabId Tab ID
     * @param string $url AJAX URL to load content from
     * @return void
     * @throws \InvalidArgumentException if tab doesn't exist
     */
    public function enableLazyLoadForTab(string $tabId, string $url): void
    {
        if (!isset($this->tabs[$tabId])) {
            throw new \InvalidArgumentException("Tab with ID '{$tabId}' does not exist.");
        }

        $this->tabs[$tabId]->enableLazyLoad($url);
    }

    /**
     * Get lazy loading script for tabs (Requirement 32.7)
     * 
     * @param string $containerId Container element ID
     * @return string JavaScript code for lazy loading
     */
    public function getLazyLoadScript(string $containerId): string
    {
        return <<<JS
(function() {
    const container = document.getElementById('{$containerId}');
    if (!container) return;
    
    // Load tab content when tab is activated
    container.addEventListener('click', function(e) {
        const tab = e.target.closest('[role="tab"]');
        if (!tab) return;
        
        const panelId = tab.getAttribute('aria-controls');
        const panel = document.getElementById(panelId);
        
        if (!panel) return;
        
        // Check if lazy loading is enabled
        if (panel.getAttribute('data-lazy-load') !== 'true') return;
        
        // Check if already loaded
        if (panel.getAttribute('data-loaded') === 'true') return;
        
        // Get URL
        const url = panel.getAttribute('data-lazy-url');
        if (!url) return;
        
        // Show loading indicator
        panel.innerHTML = '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';
        
        // Load content via AJAX
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load tab content');
            return response.text();
        })
        .then(html => {
            panel.innerHTML = html;
            panel.setAttribute('data-loaded', 'true');
            
            // Dispatch event for any listeners
            panel.dispatchEvent(new CustomEvent('tab-loaded', {
                detail: { tabId: panelId, url: url }
            }));
        })
        .catch(error => {
            console.error('Error loading tab content:', error);
            panel.innerHTML = '<div class="alert alert-error">Failed to load content. Please try again.</div>';
        });
    });
})();
JS;
    }

    /**
     * Get Alpine.js data for lazy loading (Requirement 32.7)
     * 
     * @return array Alpine.js data object
     */
    public function getAlpineLazyLoadData(): array
    {
        return [
            'loadingTabs' => '{}',
            'loadTab' => '(tabId, url) => {
                if (this.loadingTabs[tabId]) return;
                
                const panel = document.getElementById(\'tabpanel-\' + tabId);
                if (!panel) return;
                
                if (panel.getAttribute(\'data-loaded\') === \'true\') return;
                
                this.loadingTabs[tabId] = true;
                
                fetch(url, {
                    method: \'GET\',
                    headers: {
                        \'X-Requested-With\': \'XMLHttpRequest\',
                        \'Accept\': \'text/html\'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(\'Failed to load tab content\');
                    return response.text();
                })
                .then(html => {
                    panel.innerHTML = html;
                    panel.setAttribute(\'data-loaded\', \'true\');
                    delete this.loadingTabs[tabId];
                })
                .catch(error => {
                    console.error(\'Error loading tab content:\', error);
                    panel.innerHTML = \'<div class="alert alert-error">Failed to load content.</div>\';
                    delete this.loadingTabs[tabId];
                });
            }',
        ];
    }

    /**
     * Check if any tabs have lazy loading enabled (Requirement 32.7)
     * 
     * @return bool
     */
    public function hasLazyLoadedTabs(): bool
    {
        foreach ($this->tabs as $tab) {
            if ($tab->isLazyLoaded()) {
                return true;
            }
        }
        return false;
    }
}

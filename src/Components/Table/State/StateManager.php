<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\State;

/**
 * StateManager - Manages table configuration state and history.
 *
 * This class provides state management for TableBuilder, allowing:
 * - Saving and retrieving state values per unique table ID
 * - Supporting multiple table instances on the same page
 * - Tracking state history for debugging
 * - Clearing specific or all state variables
 * - Managing clearable configuration variables
 *
 * VALIDATES: Requirements 5.1, 5.2, 5.6
 *
 * @version 1.1.0
 */
class StateManager
{
    /**
     * Current state storage per unique table ID.
     *
     * Structure: ['table_id' => ['key' => value, ...], ...]
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $state = [];

    /**
     * State change history for debugging and auditing.
     *
     * Structure: [['table_id' => string, 'key' => string, 'old' => mixed, 'new' => mixed, 'timestamp' => float], ...]
     *
     * @var array<int, array{table_id: string, key: string, old: mixed, new: mixed, timestamp: float}>
     */
    protected array $stateHistory = [];

    /**
     * Current active table ID for operations.
     *
     * When set, all state operations apply to this table ID.
     * When null, operations apply to global state (backward compatibility).
     *
     * @var string|null
     */
    protected ?string $currentTableId = null;

    /**
     * List of clearable configuration variables.
     *
     * These variables can be cleared using clearClearableVars() method.
     *
     * @var array<int, string>
     */
    protected array $clearableVars = [
        'merged_columns',
        'fixed_columns',
        'hidden_columns',
        'formats',
        'conditions',
        'alignments',
        'filters',
    ];

    /**
     * Set the current table ID for state operations.
     *
     * All subsequent state operations will apply to this table ID.
     * This enables multiple table instances to maintain separate state.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     * VALIDATES: Requirement 5.2 - Unique IDs for each instance
     *
     * @param string $tableId The unique table identifier
     * @return void
     */
    public function setTableId(string $tableId): void
    {
        $this->currentTableId = $tableId;

        // Initialize state array for this table if not exists
        if (!isset($this->state[$tableId])) {
            $this->state[$tableId] = [];
        }
    }

    /**
     * Get the current table ID.
     *
     * @return string|null The current table ID or null if not set
     */
    public function getTableId(): ?string
    {
        return $this->currentTableId;
    }

    /**
     * Clear the current table ID.
     *
     * Resets to global state mode (backward compatibility).
     *
     * @return void
     */
    public function clearTableId(): void
    {
        $this->currentTableId = null;
    }

    /**
     * Get the state array for the current table ID.
     *
     * Returns the state array for the current table ID, or global state
     * if no table ID is set (backward compatibility).
     *
     * @return array<string, mixed>
     */
    protected function getCurrentStateArray(): array
    {
        if ($this->currentTableId === null) {
            // Backward compatibility: use root level state
            return $this->state['__global__'] ?? [];
        }

        return $this->state[$this->currentTableId] ?? [];
    }

    /**
     * Set the state array for the current table ID.
     *
     * @param array<string, mixed> $stateArray The state array to set
     * @return void
     */
    protected function setCurrentStateArray(array $stateArray): void
    {
        if ($this->currentTableId === null) {
            // Backward compatibility: use root level state
            $this->state['__global__'] = $stateArray;
        } else {
            $this->state[$this->currentTableId] = $stateArray;
        }
    }

    /**
     * Save a state value and track the change in history.
     *
     * State is stored per unique table ID when setTableId() has been called.
     * This allows multiple table instances to maintain separate state.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @param string $key The state key
     * @param mixed $value The state value
     * @return void
     */
    public function saveState(string $key, $value): void
    {
        $currentState = $this->getCurrentStateArray();
        $oldValue = $currentState[$key] ?? null;

        // Track in history
        $this->stateHistory[] = [
            'table_id' => $this->currentTableId ?? '__global__',
            'key' => $key,
            'old' => $oldValue,
            'new' => $value,
            'timestamp' => microtime(true),
        ];

        // Update state
        $currentState[$key] = $value;
        $this->setCurrentStateArray($currentState);
    }

    /**
     * Get a state value with optional default.
     *
     * Retrieves state for the current table ID when setTableId() has been called.
     *
     * @param string $key The state key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The state value or default
     */
    public function getState(string $key, $default = null)
    {
        $currentState = $this->getCurrentStateArray();

        return $currentState[$key] ?? $default;
    }

    /**
     * Check if a state key exists.
     *
     * Checks state for the current table ID when setTableId() has been called.
     *
     * @param string $key The state key
     * @return bool True if key exists
     */
    public function hasState(string $key): bool
    {
        $currentState = $this->getCurrentStateArray();

        return isset($currentState[$key]);
    }

    /**
     * Clear a specific state variable.
     *
     * Clears state for the current table ID when setTableId() has been called.
     *
     * @param string $var The variable name to clear
     * @return void
     */
    public function clearVar(string $var): void
    {
        $currentState = $this->getCurrentStateArray();

        if (isset($currentState[$var])) {
            unset($currentState[$var]);
            $this->setCurrentStateArray($currentState);
        }
    }

    /**
     * Clear all state variables.
     *
     * When table ID is set, clears only that table's state.
     * When table ID is not set, clears global state (backward compatibility).
     *
     * @return void
     */
    public function clearAll(): void
    {
        if ($this->currentTableId === null) {
            // Backward compatibility: clear global state
            if (isset($this->state['__global__'])) {
                $this->state['__global__'] = [];
            }
        } else {
            // Clear current table's state
            $this->state[$this->currentTableId] = [];
        }
    }

    /**
     * Clear all clearable configuration variables.
     *
     * This clears only the variables defined in $clearableVars array
     * for the current table ID.
     *
     * @return void
     */
    public function clearClearableVars(): void
    {
        foreach ($this->clearableVars as $var) {
            $this->clearVar($var);
        }
    }

    /**
     * Get the complete state history.
     *
     * Useful for debugging and understanding state changes over time.
     * Includes table_id in each history entry to track which table was affected.
     *
     * @param string|null $tableId Optional table ID to filter history
     * @return array<int, array{table_id: string, key: string, old: mixed, new: mixed, timestamp: float}>
     */
    public function getStateHistory(?string $tableId = null): array
    {
        if ($tableId === null) {
            return $this->stateHistory;
        }

        // Filter history for specific table ID
        return array_filter(
            $this->stateHistory,
            fn ($entry) => $entry['table_id'] === $tableId
        );
    }

    /**
     * Get all current state values.
     *
     * When table ID is set, returns state for that table only.
     * When table ID is not set, returns global state (backward compatibility).
     *
     * @return array<string, mixed>
     */
    public function getAllState(): array
    {
        return $this->getCurrentStateArray();
    }

    /**
     * Get state for all table instances.
     *
     * Returns the complete state structure with all table IDs.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllTableStates(): array
    {
        return $this->state;
    }

    /**
     * Get state for a specific table ID without changing current table ID.
     *
     * Useful for inspecting state of other tables without affecting
     * the current table context.
     *
     * @param string $tableId The table ID to get state for
     * @return array<string, mixed>
     */
    public function getStateForTable(string $tableId): array
    {
        return $this->state[$tableId] ?? [];
    }

    /**
     * Check if a table ID has any state stored.
     *
     * @param string $tableId The table ID to check
     * @return bool True if table has state
     */
    public function hasTableState(string $tableId): bool
    {
        return isset($this->state[$tableId]) && !empty($this->state[$tableId]);
    }

    /**
     * Clear state for a specific table ID.
     *
     * Does not affect the current table ID setting.
     *
     * @param string $tableId The table ID to clear state for
     * @return void
     */
    public function clearTableState(string $tableId): void
    {
        if (isset($this->state[$tableId])) {
            $this->state[$tableId] = [];
        }
    }

    /**
     * Get the list of clearable variables.
     *
     * @return array<int, string>
     */
    public function getClearableVars(): array
    {
        return $this->clearableVars;
    }

    /**
     * Add a variable to the clearable vars list.
     *
     * @param string $var The variable name
     * @return void
     */
    public function addClearableVar(string $var): void
    {
        if (!in_array($var, $this->clearableVars, true)) {
            $this->clearableVars[] = $var;
        }
    }

    /**
     * Remove a variable from the clearable vars list.
     *
     * @param string $var The variable name
     * @return void
     */
    public function removeClearableVar(string $var): void
    {
        $this->clearableVars = array_values(
            array_filter($this->clearableVars, fn ($v) => $v !== $var)
        );
    }

    /**
     * Clear state history.
     *
     * Useful for memory management in long-running processes.
     *
     * @return void
     */
    public function clearHistory(): void
    {
        $this->stateHistory = [];
    }

    /**
     * Set the active tab index.
     *
     * Tracks which tab is currently active for the current table instance.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     * VALIDATES: Requirement 6.6 - Display cached content
     *
     * @param int $tabIndex The tab index (0-based)
     * @return void
     */
    public function setActiveTab(int $tabIndex): void
    {
        $this->saveState('active_tab', $tabIndex);
    }

    /**
     * Get the active tab index.
     *
     * Returns the currently active tab index, defaulting to 0 (first tab).
     *
     * @return int The active tab index
     */
    public function getActiveTab(): int
    {
        return $this->getState('active_tab', 0);
    }

    /**
     * Mark a tab as loaded.
     *
     * Adds the tab index to the list of loaded tabs for the current table instance.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     *
     * @param int $tabIndex The tab index to mark as loaded
     * @return void
     */
    public function addLoadedTab(int $tabIndex): void
    {
        $loadedTabs = $this->getLoadedTabs();

        if (!in_array($tabIndex, $loadedTabs, true)) {
            $loadedTabs[] = $tabIndex;
            $this->saveState('loaded_tabs', $loadedTabs);
        }
    }

    /**
     * Get all loaded tab indices.
     *
     * Returns an array of tab indices that have been loaded.
     *
     * @return array<int, int> Array of loaded tab indices
     */
    public function getLoadedTabs(): array
    {
        return $this->getState('loaded_tabs', []);
    }

    /**
     * Check if a tab has been loaded.
     *
     * VALIDATES: Requirement 6.6 - Display cached content
     *
     * @param int $tabIndex The tab index to check
     * @return bool True if tab has been loaded
     */
    public function isTabLoaded(int $tabIndex): bool
    {
        $loadedTabs = $this->getLoadedTabs();

        return in_array($tabIndex, $loadedTabs, true);
    }

    /**
     * Cache tab content.
     *
     * Stores the rendered HTML content for a specific tab.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     *
     * @param int $tabIndex The tab index
     * @param string $content The HTML content to cache
     * @return void
     */
    public function setTabContent(int $tabIndex, string $content): void
    {
        $tabContent = $this->getState('tab_content', []);
        $tabContent[$tabIndex] = $content;
        $this->saveState('tab_content', $tabContent);
    }

    /**
     * Get cached tab content.
     *
     * Retrieves the cached HTML content for a specific tab.
     *
     * VALIDATES: Requirement 6.6 - Display cached content
     *
     * @param int $tabIndex The tab index
     * @return string|null The cached content or null if not cached
     */
    public function getTabContent(int $tabIndex): ?string
    {
        $tabContent = $this->getState('tab_content', []);

        return $tabContent[$tabIndex] ?? null;
    }

    /**
     * Check if tab content is cached.
     *
     * @param int $tabIndex The tab index to check
     * @return bool True if content is cached
     */
    public function hasTabContent(int $tabIndex): bool
    {
        $tabContent = $this->getState('tab_content', []);

        return isset($tabContent[$tabIndex]);
    }

    /**
     * Clear all tab-related state.
     *
     * Removes active tab, loaded tabs, and cached tab content for the current table instance.
     *
     * @return void
     */
    public function clearTabState(): void
    {
        $this->clearVar('active_tab');
        $this->clearVar('loaded_tabs');
        $this->clearVar('tab_content');
    }
}

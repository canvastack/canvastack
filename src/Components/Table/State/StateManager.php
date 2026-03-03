<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\State;

/**
 * StateManager - Manages table configuration state and history.
 *
 * This class provides state management for TableBuilder, allowing:
 * - Saving and retrieving state values
 * - Tracking state history for debugging
 * - Clearing specific or all state variables
 * - Managing clearable configuration variables
 *
 * @version 1.0.0
 */
class StateManager
{
    /**
     * Current state storage.
     *
     * @var array<string, mixed>
     */
    protected array $state = [];

    /**
     * State change history for debugging and auditing.
     *
     * @var array<int, array{key: string, old: mixed, new: mixed, timestamp: float}>
     */
    protected array $stateHistory = [];

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
     * Save a state value and track the change in history.
     *
     * @param string $key The state key
     * @param mixed $value The state value
     * @return void
     */
    public function saveState(string $key, $value): void
    {
        $this->stateHistory[] = [
            'key' => $key,
            'old' => $this->state[$key] ?? null,
            'new' => $value,
            'timestamp' => microtime(true),
        ];

        $this->state[$key] = $value;
    }

    /**
     * Get a state value with optional default.
     *
     * @param string $key The state key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The state value or default
     */
    public function getState(string $key, $default = null)
    {
        return $this->state[$key] ?? $default;
    }

    /**
     * Check if a state key exists.
     *
     * @param string $key The state key
     * @return bool True if key exists
     */
    public function hasState(string $key): bool
    {
        return isset($this->state[$key]);
    }

    /**
     * Clear a specific state variable.
     *
     * @param string $var The variable name to clear
     * @return void
     */
    public function clearVar(string $var): void
    {
        if (isset($this->state[$var])) {
            unset($this->state[$var]);
        }
    }

    /**
     * Clear all state variables.
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->state = [];
    }

    /**
     * Clear all clearable configuration variables.
     *
     * This clears only the variables defined in $clearableVars array.
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
     *
     * @return array<int, array{key: string, old: mixed, new: mixed, timestamp: float}>
     */
    public function getStateHistory(): array
    {
        return $this->stateHistory;
    }

    /**
     * Get all current state values.
     *
     * @return array<string, mixed>
     */
    public function getAllState(): array
    {
        return $this->state;
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
}

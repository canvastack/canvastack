<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Illuminate\Support\Facades\Session;

/**
 * TableStateManager - Manages table state persistence.
 *
 * This class provides state management for table user preferences:
 * - Sort state (column, direction)
 * - Filter state (active filters)
 * - Page size (rows per page)
 * - Column visibility (hidden columns)
 * - Column widths (resizable columns)
 *
 * State is persisted in session storage and can be loaded on page load.
 *
 * @version 1.0.0
 */
class TableStateManager
{
    /**
     * Session key prefix for table state.
     */
    protected const SESSION_PREFIX = 'table_state_';

    /**
     * Table identifier (unique per table).
     *
     * @var string
     */
    protected string $tableId;

    /**
     * Current state data.
     *
     * @var array<string, mixed>
     */
    protected array $state = [];

    /**
     * Constructor.
     *
     * @param string $tableId Unique table identifier
     */
    public function __construct(string $tableId)
    {
        $this->tableId = $tableId;
        $this->load();
    }

    /**
     * Save the current state to session.
     *
     * @return void
     */
    public function save(): void
    {
        Session::put($this->getSessionKey(), $this->state);
    }

    /**
     * Load state from session.
     *
     * @return void
     */
    public function load(): void
    {
        $this->state = Session::get($this->getSessionKey(), []);
    }

    /**
     * Clear all state from session.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->state = [];
        Session::forget($this->getSessionKey());
    }

    /**
     * Merge new state with existing state.
     *
     * @param array<string, mixed> $newState New state to merge
     * @return void
     */
    public function merge(array $newState): void
    {
        $this->state = array_merge($this->state, $newState);
        $this->save();
    }

    /**
     * Get the session key for this table.
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return self::SESSION_PREFIX . $this->tableId;
    }

    /**
     * Set sort state.
     *
     * @param string|null $column Sort column
     * @param string|null $direction Sort direction (asc/desc)
     * @return void
     */
    public function setSortState(?string $column, ?string $direction = null): void
    {
        if ($column === null) {
            unset($this->state['sort']);
        } else {
            $this->state['sort'] = [
                'column' => $column,
                'direction' => $direction ?? 'asc',
            ];
        }
        $this->save();
    }

    /**
     * Get sort state.
     *
     * @return array{column: string, direction: string}|null
     */
    public function getSortState(): ?array
    {
        return $this->state['sort'] ?? null;
    }

    /**
     * Set filter state.
     *
     * @param array<string, mixed> $filters Active filters
     * @return void
     */
    public function setFilterState(array $filters): void
    {
        $this->state['filters'] = $filters;
        $this->save();
    }

    /**
     * Get filter state.
     *
     * @return array<string, mixed>
     */
    public function getFilterState(): array
    {
        return $this->state['filters'] ?? [];
    }

    /**
     * Clear filter state.
     *
     * @return void
     */
    public function clearFilterState(): void
    {
        unset($this->state['filters']);
        $this->save();
    }

    /**
     * Set page size.
     *
     * @param int $pageSize Rows per page
     * @return void
     */
    public function setPageSize(int $pageSize): void
    {
        $this->state['page_size'] = $pageSize;
        $this->save();
    }

    /**
     * Get page size.
     *
     * @param int $default Default page size
     * @return int
     */
    public function getPageSize(int $default = 10): int
    {
        return $this->state['page_size'] ?? $default;
    }

    /**
     * Set column visibility state.
     *
     * @param array<int, string> $hiddenColumns Hidden column names
     * @return void
     */
    public function setColumnVisibility(array $hiddenColumns): void
    {
        $this->state['hidden_columns'] = $hiddenColumns;
        $this->save();
    }

    /**
     * Get column visibility state.
     *
     * @return array<int, string>
     */
    public function getColumnVisibility(): array
    {
        return $this->state['hidden_columns'] ?? [];
    }

    /**
     * Set column widths (for resizable columns).
     *
     * @param array<string, int> $widths Column widths (column => width in px)
     * @return void
     */
    public function setColumnWidths(array $widths): void
    {
        $this->state['column_widths'] = $widths;
        $this->save();
    }

    /**
     * Get column widths.
     *
     * @return array<string, int>
     */
    public function getColumnWidths(): array
    {
        return $this->state['column_widths'] ?? [];
    }

    /**
     * Get column width for specific column.
     *
     * @param string $column Column name
     * @return int|null Width in px or null if not set
     */
    public function getColumnWidth(string $column): ?int
    {
        return $this->state['column_widths'][$column] ?? null;
    }

    /**
     * Set current page number.
     *
     * @param int $page Page number
     * @return void
     */
    public function setCurrentPage(int $page): void
    {
        $this->state['current_page'] = $page;
        $this->save();
    }

    /**
     * Get current page number.
     *
     * @param int $default Default page number
     * @return int
     */
    public function getCurrentPage(int $default = 1): int
    {
        return $this->state['current_page'] ?? $default;
    }

    /**
     * Set search value.
     *
     * @param string|null $search Search value
     * @return void
     */
    public function setSearchValue(?string $search): void
    {
        if ($search === null || $search === '') {
            unset($this->state['search']);
        } else {
            $this->state['search'] = $search;
        }
        $this->save();
    }

    /**
     * Get search value.
     *
     * @return string|null
     */
    public function getSearchValue(): ?string
    {
        return $this->state['search'] ?? null;
    }

    /**
     * Get all state data.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->state;
    }

    /**
     * Set custom state value.
     *
     * @param string $key State key
     * @param mixed $value State value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->state[$key] = $value;
        $this->save();
    }

    /**
     * Get custom state value.
     *
     * @param string $key State key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->state[$key] ?? $default;
    }

    /**
     * Check if state key exists.
     *
     * @param string $key State key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->state[$key]);
    }

    /**
     * Remove custom state value.
     *
     * @param string $key State key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->state[$key]);
        $this->save();
    }

    /**
     * Get table identifier.
     *
     * @return string
     */
    public function getTableId(): string
    {
        return $this->tableId;
    }

    /**
     * Check if state is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->state);
    }

    /**
     * Get state as JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->state, JSON_THROW_ON_ERROR);
    }

    /**
     * Load state from JSON string.
     *
     * @param string $json JSON string
     * @return void
     */
    public function fromJson(string $json): void
    {
        $this->state = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->save();
    }
}

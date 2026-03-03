<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Filter;

/**
 * FilterManager - Manages filters for TableBuilder
 * 
 * Handles filter configuration, active filter state, and session persistence.
 * Supports cascading filters, auto-submit, and filter options loading.
 * 
 * @package Canvastack\Canvastack\Components\Table\Filter
 */
class FilterManager
{
    /**
     * All registered filters
     * 
     * @var array<string, Filter>
     */
    protected array $filters = [];

    /**
     * Currently active filter values
     * 
     * @var array<string, mixed>
     */
    protected array $activeFilters = [];

    /**
     * Session key for persistence
     * 
     * @var string|null
     */
    protected ?string $sessionKey = null;

    /**
     * Add a filter to the manager
     * 
     * @param string $column Column name to filter
     * @param string $type Filter type (selectbox, inputbox, datebox)
     * @param bool|string|array $relate Related filters for cascading
     * @param bool $bidirectional Whether to enable bi-directional cascade
     * @return void
     */
    public function addFilter(string $column, string $type, $relate = false, bool $bidirectional = false): void
    {
        $filter = new Filter($column, $type, $relate);
        $filter->setBidirectional($bidirectional);
        $this->filters[$column] = $filter;
    }

    /**
     * Get all registered filters
     * 
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get active filter values
     * 
     * @return array<string, mixed>
     */
    public function getActiveFilters(): array
    {
        return $this->activeFilters;
    }

    /**
     * Set active filter values
     * 
     * @param array<string, mixed> $filters Filter values to set
     * @return void
     */
    public function setActiveFilters(array $filters): void
    {
        $this->activeFilters = $filters;
        
        // Update filter values
        foreach ($filters as $column => $value) {
            if (isset($this->filters[$column])) {
                $this->filters[$column]->setValue($value);
            }
        }
    }

    /**
     * Clear all active filters
     * 
     * @return void
     */
    public function clearFilters(): void
    {
        $this->activeFilters = [];
        
        // Clear filter values
        foreach ($this->filters as $filter) {
            $filter->setValue(null);
        }
    }

    /**
     * Set session key for persistence
     * 
     * @param string $sessionKey Session key
     * @return void
     */
    public function setSessionKey(string $sessionKey): void
    {
        $this->sessionKey = $sessionKey;
    }

    /**
     * Get session key
     * 
     * @return string|null
     */
    public function getSessionKey(): ?string
    {
        return $this->sessionKey;
    }

    /**
     * Save active filters to session
     * 
     * @return void
     */
    public function saveToSession(): void
    {
        if ($this->sessionKey === null) {
            return;
        }

        session([$this->sessionKey => $this->activeFilters]);
    }

    /**
     * Load active filters from session
     * 
     * @return void
     */
    public function loadFromSession(): void
    {
        if ($this->sessionKey === null) {
            return;
        }

        $savedFilters = session($this->sessionKey, []);
        
        if (!empty($savedFilters)) {
            $this->setActiveFilters($savedFilters);
        }
    }

    /**
     * Check if a filter exists
     * 
     * @param string $column Column name
     * @return bool
     */
    public function hasFilter(string $column): bool
    {
        return isset($this->filters[$column]);
    }

    /**
     * Get a specific filter
     * 
     * @param string $column Column name
     * @return Filter|null
     */
    public function getFilter(string $column): ?Filter
    {
        return $this->filters[$column] ?? null;
    }

    /**
     * Get count of active filters
     * 
     * @return int
     */
    public function getActiveFilterCount(): int
    {
        return count(array_filter($this->activeFilters, function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    /**
     * Check if any filters are active
     * 
     * @return bool
     */
    public function hasActiveFilters(): bool
    {
        return $this->getActiveFilterCount() > 0;
    }

    /**
     * Get filters as array for JSON serialization
     * 
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        
        foreach ($this->filters as $column => $filter) {
            $result[] = [
                'column' => $column,
                'type' => $filter->getType(),
                'label' => $filter->getLabel(),
                'value' => $filter->getValue(),
                'options' => $filter->getOptions(),
                'relate' => $filter->getRelate(),
                'bidirectional' => $filter->isBidirectional(),
                'autoSubmit' => $filter->shouldAutoSubmit(),
            ];
        }
        
        return $result;
    }
}

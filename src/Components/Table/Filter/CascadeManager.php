<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Filter;

/**
 * CascadeManager - Manages bi-directional cascading filter relationships.
 *
 * Handles the logic for updating dependent filters when a parent filter changes,
 * including bi-directional relationships where filters can affect each other.
 *
 * @package Canvastack\Canvastack\Components\Table\Filter
 */
class CascadeManager
{
    /**
     * Filter manager instance.
     *
     * @var FilterManager
     */
    protected FilterManager $filterManager;

    /**
     * Filter options provider.
     *
     * @var FilterOptionsProvider
     */
    protected FilterOptionsProvider $optionsProvider;

    /**
     * Table name for option loading.
     *
     * @var string|null
     */
    protected ?string $tableName = null;

    /**
     * Cascade graph (adjacency list).
     * Maps filter column to array of dependent filter columns.
     *
     * @var array<string, array<string>>
     */
    protected array $cascadeGraph = [];

    /**
     * Reverse cascade graph for bi-directional relationships.
     * Maps filter column to array of parent filter columns.
     *
     * @var array<string, array<string>>
     */
    protected array $reverseCascadeGraph = [];

    /**
     * Constructor.
     *
     * @param FilterManager $filterManager Filter manager instance
     * @param FilterOptionsProvider $optionsProvider Options provider instance
     */
    public function __construct(FilterManager $filterManager, FilterOptionsProvider $optionsProvider)
    {
        $this->filterManager = $filterManager;
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * Set table name for option loading.
     *
     * @param string $tableName Table name
     * @return void
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Build cascade graph from registered filters.
     *
     * @return void
     */
    public function buildCascadeGraph(): void
    {
        $this->cascadeGraph = [];
        $this->reverseCascadeGraph = [];

        $filters = $this->filterManager->getFilters();

        foreach ($filters as $column => $filter) {
            if (!$filter->hasCascading()) {
                continue;
            }

            $relatedFilters = $filter->getRelatedFilters();

            // Build forward cascade graph
            if (!isset($this->cascadeGraph[$column])) {
                $this->cascadeGraph[$column] = [];
            }

            foreach ($relatedFilters as $relatedColumn) {
                $this->cascadeGraph[$column][] = $relatedColumn;

                // Build reverse cascade graph for bi-directional
                if ($filter->isBidirectional()) {
                    if (!isset($this->reverseCascadeGraph[$relatedColumn])) {
                        $this->reverseCascadeGraph[$relatedColumn] = [];
                    }
                    $this->reverseCascadeGraph[$relatedColumn][] = $column;
                }
            }
        }
    }

    /**
     * Get dependent filters for a given filter column.
     *
     * @param string $column Filter column
     * @param bool $includeBidirectional Include bi-directional dependencies
     * @return array<string> Array of dependent filter columns
     */
    public function getDependentFilters(string $column, bool $includeBidirectional = true): array
    {
        $dependents = $this->cascadeGraph[$column] ?? [];

        if ($includeBidirectional) {
            $reverseDependents = $this->reverseCascadeGraph[$column] ?? [];
            $dependents = array_unique(array_merge($dependents, $reverseDependents));
        }

        return $dependents;
    }

    /**
     * Update dependent filter options when a filter value changes.
     *
     * @param string $changedColumn The filter column that changed
     * @param mixed $newValue The new value of the changed filter
     * @return array<string, array> Map of filter column to updated options
     */
    public function updateDependentOptions(string $changedColumn, $newValue): array
    {
        if ($this->tableName === null) {
            return [];
        }

        $updatedOptions = [];
        $dependents = $this->getDependentFilters($changedColumn, true);

        // Get current active filters
        $activeFilters = $this->filterManager->getActiveFilters();

        // Update the changed filter value
        $activeFilters[$changedColumn] = $newValue;

        foreach ($dependents as $dependentColumn) {
            $dependentFilter = $this->filterManager->getFilter($dependentColumn);

            if ($dependentFilter === null) {
                continue;
            }

            // Build parent filters for this dependent
            $parentFilters = $this->buildParentFilters($dependentColumn, $activeFilters);

            // Load new options
            try {
                $dependentFilter->setLoading(true);
                $dependentFilter->clearError();

                $options = $this->optionsProvider->getOptions(
                    $this->tableName,
                    $dependentColumn,
                    $parentFilters
                );

                $dependentFilter->setOptions($options);
                $updatedOptions[$dependentColumn] = $options;

                // Clear dependent filter value if it's no longer valid
                $currentValue = $dependentFilter->getValue();
                if ($currentValue !== null && !$this->isValueInOptions($currentValue, $options)) {
                    $dependentFilter->setValue(null);
                }
            } catch (\Exception $e) {
                $dependentFilter->setError($e->getMessage());
                $updatedOptions[$dependentColumn] = [];
            } finally {
                $dependentFilter->setLoading(false);
            }
        }

        return $updatedOptions;
    }

    /**
     * Build parent filters for a dependent filter.
     *
     * @param string $dependentColumn The dependent filter column
     * @param array $activeFilters Current active filters
     * @return array Parent filter values
     */
    protected function buildParentFilters(string $dependentColumn, array $activeFilters): array
    {
        $parentFilters = [];

        // Get all filters that affect this dependent filter
        $parentColumns = $this->getParentFilters($dependentColumn);

        foreach ($parentColumns as $parentColumn) {
            if (isset($activeFilters[$parentColumn]) && $activeFilters[$parentColumn] !== null) {
                $parentFilters[$parentColumn] = $activeFilters[$parentColumn];
            }
        }

        return $parentFilters;
    }

    /**
     * Get parent filters for a given filter column.
     *
     * @param string $column Filter column
     * @return array<string> Array of parent filter columns
     */
    protected function getParentFilters(string $column): array
    {
        $parents = [];

        // Check forward cascade graph (this filter depends on these)
        foreach ($this->cascadeGraph as $parentColumn => $dependents) {
            if (in_array($column, $dependents)) {
                $parents[] = $parentColumn;
            }
        }

        // Check reverse cascade graph (bi-directional)
        if (isset($this->reverseCascadeGraph[$column])) {
            $parents = array_merge($parents, $this->reverseCascadeGraph[$column]);
        }

        return array_unique($parents);
    }

    /**
     * Check if a value exists in options array.
     *
     * @param mixed $value Value to check
     * @param array $options Options array
     * @return bool
     */
    protected function isValueInOptions($value, array $options): bool
    {
        foreach ($options as $option) {
            if (isset($option['value']) && $option['value'] == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cascade graph.
     *
     * @return array<string, array<string>>
     */
    public function getCascadeGraph(): array
    {
        return $this->cascadeGraph;
    }

    /**
     * Get reverse cascade graph.
     *
     * @return array<string, array<string>>
     */
    public function getReverseCascadeGraph(): array
    {
        return $this->reverseCascadeGraph;
    }

    /**
     * Check if a filter has dependencies.
     *
     * @param string $column Filter column
     * @return bool
     */
    public function hasDependencies(string $column): bool
    {
        return !empty($this->getDependentFilters($column, true));
    }

    /**
     * Get all filters in cascade order (topological sort).
     *
     * @return array<string> Array of filter columns in cascade order
     */
    public function getCascadeOrder(): array
    {
        $visited = [];
        $order = [];

        $filters = $this->filterManager->getFilters();

        foreach (array_keys($filters) as $column) {
            if (!isset($visited[$column])) {
                $this->topologicalSort($column, $visited, $order);
            }
        }

        return array_reverse($order);
    }

    /**
     * Topological sort helper for cascade ordering.
     *
     * @param string $column Current filter column
     * @param array $visited Visited filters
     * @param array $order Sorted order
     * @return void
     */
    protected function topologicalSort(string $column, array &$visited, array &$order): void
    {
        $visited[$column] = true;

        $dependents = $this->cascadeGraph[$column] ?? [];

        foreach ($dependents as $dependent) {
            if (!isset($visited[$dependent])) {
                $this->topologicalSort($dependent, $visited, $order);
            }
        }

        $order[] = $column;
    }
}

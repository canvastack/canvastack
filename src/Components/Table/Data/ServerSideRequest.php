<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Data;

/**
 * ServerSideRequest - Normalize server-side request parameters across engines.
 *
 * This class provides a unified interface for handling server-side table requests
 * from both DataTables.js and TanStack Table, normalizing their different request
 * formats into a consistent structure.
 *
 * @package Canvastack\Canvastack\Components\Table\Data
 */
class ServerSideRequest
{
    /**
     * Current page number (1-indexed).
     */
    public int $page;

    /**
     * Number of items per page.
     */
    public int $pageSize;

    /**
     * Starting index for pagination (0-indexed).
     */
    public int $start;

    /**
     * Number of items to fetch.
     */
    public int $length;

    /**
     * Primary sort column name.
     */
    public ?string $sortColumn;

    /**
     * Primary sort direction ('asc' or 'desc').
     */
    public ?string $sortDirection;

    /**
     * Multiple sort columns with directions.
     * Format: [['column' => 'name', 'direction' => 'asc'], ...]
     */
    public array $sortColumns;

    /**
     * Global search value.
     */
    public ?string $searchValue;

    /**
     * Column-specific search values.
     * Format: ['column_name' => 'search_value', ...]
     */
    public array $columnSearches;

    /**
     * Advanced filter values.
     * Format: ['filter_name' => 'filter_value', ...]
     */
    public array $filters;

    /**
     * Additional request parameters.
     */
    public array $extra;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->page = 1;
        $this->pageSize = 10;
        $this->start = 0;
        $this->length = 10;
        $this->sortColumn = null;
        $this->sortDirection = null;
        $this->sortColumns = [];
        $this->searchValue = null;
        $this->columnSearches = [];
        $this->filters = [];
        $this->extra = [];
    }

    /**
     * Create ServerSideRequest from DataTables.js request format.
     *
     * DataTables sends requests in this format:
     * - draw: Request counter
     * - start: Starting index
     * - length: Page size
     * - search[value]: Global search
     * - order[0][column]: Sort column index
     * - order[0][dir]: Sort direction
     * - columns[i][data]: Column name
     * - columns[i][search][value]: Column search
     *
     * @param array $request Raw request data from DataTables
     * @return self Normalized request object
     */
    public static function fromDataTables(array $request): self
    {
        $instance = new self();

        // Pagination
        $instance->start = (int) ($request['start'] ?? 0);
        $instance->length = (int) ($request['length'] ?? 10);
        $instance->pageSize = $instance->length;
        $instance->page = $instance->length > 0
            ? (int) floor($instance->start / $instance->length) + 1
            : 1;

        // Global search
        $instance->searchValue = $request['search']['value'] ?? null;
        if ($instance->searchValue === '') {
            $instance->searchValue = null;
        }

        // Sorting
        if (isset($request['order']) && is_array($request['order']) && count($request['order']) > 0) {
            $columns = $request['columns'] ?? [];

            foreach ($request['order'] as $order) {
                $columnIndex = (int) ($order['column'] ?? 0);
                $direction = strtolower($order['dir'] ?? 'asc');

                if (isset($columns[$columnIndex]['data'])) {
                    $columnName = $columns[$columnIndex]['data'];

                    $instance->sortColumns[] = [
                        'column' => $columnName,
                        'direction' => $direction,
                    ];

                    // Set primary sort from first order
                    if ($instance->sortColumn === null) {
                        $instance->sortColumn = $columnName;
                        $instance->sortDirection = $direction;
                    }
                }
            }
        }

        // Column-specific searches
        if (isset($request['columns']) && is_array($request['columns'])) {
            foreach ($request['columns'] as $column) {
                $columnName = $column['data'] ?? null;
                $searchValue = $column['search']['value'] ?? null;

                if ($columnName && $searchValue !== null && $searchValue !== '') {
                    $instance->columnSearches[$columnName] = $searchValue;
                }
            }
        }

        // Additional parameters (draw, custom filters, etc.)
        $instance->extra = array_diff_key($request, [
            'start' => true,
            'length' => true,
            'search' => true,
            'order' => true,
            'columns' => true,
        ]);

        return $instance;
    }

    /**
     * Create ServerSideRequest from TanStack Table request format.
     *
     * TanStack sends requests in this format:
     * - pagination: { pageIndex: 0, pageSize: 10 }
     * - sorting: [{ id: 'column', desc: false }]
     * - globalFilter: 'search value'
     * - columnFilters: [{ id: 'column', value: 'filter' }]
     * - filters: { custom_filter: 'value' }
     *
     * @param array $request Raw request data from TanStack Table
     * @return self Normalized request object
     */
    public static function fromTanStack(array $request): self
    {
        $instance = new self();

        // Pagination
        $pagination = $request['pagination'] ?? [];
        $pageIndex = (int) ($pagination['pageIndex'] ?? 0);
        $pageSize = (int) ($pagination['pageSize'] ?? 10);

        $instance->page = $pageIndex + 1; // TanStack uses 0-indexed pages
        $instance->pageSize = $pageSize;
        $instance->start = $pageIndex * $pageSize;
        $instance->length = $pageSize;

        // Global search
        $instance->searchValue = $request['globalFilter'] ?? null;
        if ($instance->searchValue === '') {
            $instance->searchValue = null;
        }

        // Sorting
        $sorting = $request['sorting'] ?? [];
        if (is_array($sorting) && count($sorting) > 0) {
            foreach ($sorting as $sort) {
                $columnName = $sort['id'] ?? null;
                $desc = $sort['desc'] ?? false;
                $direction = $desc ? 'desc' : 'asc';

                if ($columnName) {
                    $instance->sortColumns[] = [
                        'column' => $columnName,
                        'direction' => $direction,
                    ];

                    // Set primary sort from first sort
                    if ($instance->sortColumn === null) {
                        $instance->sortColumn = $columnName;
                        $instance->sortDirection = $direction;
                    }
                }
            }
        }

        // Column-specific filters
        $columnFilters = $request['columnFilters'] ?? [];
        if (is_array($columnFilters)) {
            foreach ($columnFilters as $filter) {
                $columnName = $filter['id'] ?? null;
                $filterValue = $filter['value'] ?? null;

                if ($columnName && $filterValue !== null && $filterValue !== '') {
                    $instance->columnSearches[$columnName] = $filterValue;
                }
            }
        }

        // Advanced filters
        $instance->filters = $request['filters'] ?? [];

        // Additional parameters
        $instance->extra = array_diff_key($request, [
            'pagination' => true,
            'sorting' => true,
            'globalFilter' => true,
            'columnFilters' => true,
            'filters' => true,
        ]);

        return $instance;
    }

    /**
     * Get pagination offset.
     *
     * @return int Starting index for database query
     */
    public function getOffset(): int
    {
        return $this->start;
    }

    /**
     * Get pagination limit.
     *
     * @return int Number of items to fetch
     */
    public function getLimit(): int
    {
        return $this->length;
    }

    /**
     * Check if request has sorting.
     *
     * @return bool True if sorting is specified
     */
    public function hasSorting(): bool
    {
        return $this->sortColumn !== null;
    }

    /**
     * Check if request has global search.
     *
     * @return bool True if global search is specified
     */
    public function hasGlobalSearch(): bool
    {
        return $this->searchValue !== null && $this->searchValue !== '';
    }

    /**
     * Check if request has column searches.
     *
     * @return bool True if column searches are specified
     */
    public function hasColumnSearches(): bool
    {
        return count($this->columnSearches) > 0;
    }

    /**
     * Check if request has filters.
     *
     * @return bool True if filters are specified
     */
    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    /**
     * Get all sort columns.
     *
     * @return array Array of sort columns with directions
     */
    public function getSortColumns(): array
    {
        return $this->sortColumns;
    }

    /**
     * Get primary sort column.
     *
     * @return string|null Primary sort column name
     */
    public function getPrimarySortColumn(): ?string
    {
        return $this->sortColumn;
    }

    /**
     * Get primary sort direction.
     *
     * @return string|null Primary sort direction ('asc' or 'desc')
     */
    public function getPrimarySortDirection(): ?string
    {
        return $this->sortDirection;
    }

    /**
     * Convert to array representation.
     *
     * @return array Array representation of the request
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'start' => $this->start,
            'length' => $this->length,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'sortColumns' => $this->sortColumns,
            'searchValue' => $this->searchValue,
            'columnSearches' => $this->columnSearches,
            'filters' => $this->filters,
            'extra' => $this->extra,
        ];
    }
}

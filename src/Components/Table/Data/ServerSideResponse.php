<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Data;

/**
 * Server-Side Response Data Model
 * 
 * Normalizes server-side response format across different table engines.
 * Provides conversion methods for DataTables.js and TanStack Table formats.
 * 
 * @package Canvastack\Canvastack\Components\Table\Data
 */
class ServerSideResponse
{
    /**
     * Response data rows.
     *
     * @var array
     */
    public array $data;

    /**
     * Total number of records (before filtering).
     *
     * @var int
     */
    public int $total;

    /**
     * Number of records after filtering.
     *
     * @var int
     */
    public int $filtered;

    /**
     * Current page number (1-indexed).
     *
     * @var int
     */
    public int $page;

    /**
     * Number of records per page.
     *
     * @var int
     */
    public int $pageSize;

    /**
     * Total number of pages.
     *
     * @var int
     */
    public int $totalPages;

    /**
     * Additional metadata.
     *
     * @var array
     */
    public array $meta;

    /**
     * Constructor.
     *
     * @param array $data Response data rows
     * @param int $total Total records before filtering
     * @param int $filtered Records after filtering
     * @param int $page Current page number
     * @param int $pageSize Records per page
     * @param array $meta Additional metadata
     */
    public function __construct(
        array $data,
        int $total,
        int $filtered,
        int $page = 1,
        int $pageSize = 10,
        array $meta = []
    ) {
        $this->data = $data;
        $this->total = $total;
        $this->filtered = $filtered;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->totalPages = $pageSize > 0 ? (int) ceil($filtered / $pageSize) : 0;
        $this->meta = $meta;
    }

    /**
     * Convert to DataTables.js format.
     * 
     * DataTables expects:
     * - draw: Request counter for async requests
     * - recordsTotal: Total records before filtering
     * - recordsFiltered: Records after filtering
     * - data: Array of data rows
     *
     * @param int $draw Request counter from DataTables
     * @return array DataTables-formatted response
     */
    public function toDataTables(int $draw = 1): array
    {
        return [
            'draw' => $draw,
            'recordsTotal' => $this->total,
            'recordsFiltered' => $this->filtered,
            'data' => $this->data,
        ];
    }

    /**
     * Convert to TanStack Table format.
     * 
     * TanStack expects:
     * - data: Array of data rows
     * - pagination: Pagination metadata
     *   - page: Current page (1-indexed)
     *   - pageSize: Records per page
     *   - totalPages: Total number of pages
     *   - total: Total records after filtering
     *   - totalRecords: Total records before filtering
     *
     * @return array TanStack-formatted response
     */
    public function toTanStack(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'page' => $this->page,
                'pageSize' => $this->pageSize,
                'totalPages' => $this->totalPages,
                'total' => $this->filtered,
                'totalRecords' => $this->total,
            ],
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'total' => $this->total,
            'filtered' => $this->filtered,
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'totalPages' => $this->totalPages,
            'meta' => $this->meta,
        ];
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['data'] ?? [],
            $data['total'] ?? 0,
            $data['filtered'] ?? 0,
            $data['page'] ?? 1,
            $data['pageSize'] ?? 10,
            $data['meta'] ?? []
        );
    }

    /**
     * Get start record number for current page.
     *
     * @return int
     */
    public function getStartRecord(): int
    {
        if ($this->filtered === 0) {
            return 0;
        }

        return (($this->page - 1) * $this->pageSize) + 1;
    }

    /**
     * Get end record number for current page.
     *
     * @return int
     */
    public function getEndRecord(): int
    {
        $end = $this->page * $this->pageSize;
        return min($end, $this->filtered);
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages;
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * Get pagination info text.
     * 
     * Example: "Showing 1 to 10 of 100 entries"
     *
     * @return string
     */
    public function getPaginationText(): string
    {
        if ($this->filtered === 0) {
            return 'Showing 0 entries';
        }

        $start = $this->getStartRecord();
        $end = $this->getEndRecord();

        if ($this->total !== $this->filtered) {
            return sprintf(
                'Showing %d to %d of %d entries (filtered from %d total entries)',
                $start,
                $end,
                $this->filtered,
                $this->total
            );
        }

        return sprintf(
            'Showing %d to %d of %d entries',
            $start,
            $end,
            $this->filtered
        );
    }

    /**
     * Add metadata.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMeta(string $key, $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Get metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Check if response has data.
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Get number of records in current page.
     *
     * @return int
     */
    public function getRecordCount(): int
    {
        return count($this->data);
    }
}

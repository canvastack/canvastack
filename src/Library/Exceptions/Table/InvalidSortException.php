<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when invalid sort parameters are provided
 * 
 * This exception is thrown when:
 * - Sort column doesn't exist
 * - Sort direction is invalid (not 'asc' or 'desc')
 * - Sort column is not sortable
 * - Multiple sort parameters conflict
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating sort parameters
 * ```php
 * public function validateSort(string $column, string $direction): void
 * {
 *     // Validate column exists
 *     if (!$this->columnExists($column)) {
 *         throw new InvalidSortException(
 *             "Sort column '{$column}' does not exist",
 *             0,
 *             null,
 *             ['column' => $column, 'direction' => $direction]
 *         );
 *     }
 *     
 *     // Validate direction
 *     $validDirections = ['asc', 'desc'];
 *     if (!in_array(strtolower($direction), $validDirections)) {
 *         throw new InvalidSortException(
 *             "Sort direction must be 'asc' or 'desc'",
 *             0,
 *             null,
 *             [
 *                 'column' => $column,
 *                 'direction' => $direction,
 *                 'valid_directions' => $validDirections
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling invalid sort
 * ```php
 * try {
 *     $table->orderBy($column, $direction);
 * } catch (InvalidSortException $e) {
 *     return response()->json([
 *         'error' => 'Invalid sort parameters',
 *         'message' => $e->getMessage()
 *     ], 400);
 * }
 * ```
 */
class InvalidSortException extends TableValidationException
{
    /**
     * The invalid sort column
     *
     * @var string|null
     */
    protected ?string $sortColumn = null;

    /**
     * The invalid sort direction
     *
     * @var string|null
     */
    protected ?string $sortDirection = null;

    /**
     * Set the sort column
     *
     * @param string $column The sort column
     * @return self
     */
    public function setSortColumn(string $column): self
    {
        $this->sortColumn = $column;
        return $this;
    }

    /**
     * Get the sort column
     *
     * @return string|null The sort column
     */
    public function getSortColumn(): ?string
    {
        return $this->sortColumn;
    }

    /**
     * Set the sort direction
     *
     * @param string $direction The sort direction
     * @return self
     */
    public function setSortDirection(string $direction): self
    {
        $this->sortDirection = $direction;
        return $this;
    }

    /**
     * Get the sort direction
     *
     * @return string|null The sort direction
     */
    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }
}

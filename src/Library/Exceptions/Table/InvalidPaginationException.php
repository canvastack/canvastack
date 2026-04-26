<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when invalid pagination parameters are provided
 * 
 * This exception is thrown when:
 * - Start offset is negative
 * - Page length is zero or negative
 * - Page length exceeds maximum allowed
 * - Page number is invalid
 * - Pagination parameters are not numeric
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating pagination parameters
 * ```php
 * public function validatePagination(int $start, int $length): void
 * {
 *     if ($start < 0) {
 *         throw new InvalidPaginationException(
 *             'Start offset must be non-negative',
 *             0,
 *             null,
 *             ['start' => $start, 'length' => $length]
 *         );
 *     }
 *     
 *     if ($length <= 0) {
 *         throw new InvalidPaginationException(
 *             'Page length must be positive',
 *             0,
 *             null,
 *             ['start' => $start, 'length' => $length]
 *         );
 *     }
 *     
 *     if ($length > 100) {
 *         throw new InvalidPaginationException(
 *             'Page length exceeds maximum allowed (100)',
 *             0,
 *             null,
 *             ['start' => $start, 'length' => $length, 'max' => 100]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling invalid pagination
 * ```php
 * try {
 *     $this->validatePagination($request->start, $request->length);
 * } catch (InvalidPaginationException $e) {
 *     return response()->json([
 *         'error' => 'Invalid pagination',
 *         'message' => $e->getMessage()
 *     ], 400);
 * }
 * ```
 */
class InvalidPaginationException extends TableValidationException
{
    /**
     * The invalid start offset
     *
     * @var int|null
     */
    protected ?int $start = null;

    /**
     * The invalid page length
     *
     * @var int|null
     */
    protected ?int $length = null;

    /**
     * Set the start offset
     *
     * @param int $start The start offset
     * @return self
     */
    public function setStart(int $start): self
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get the start offset
     *
     * @return int|null The start offset
     */
    public function getStart(): ?int
    {
        return $this->start;
    }

    /**
     * Set the page length
     *
     * @param int $length The page length
     * @return self
     */
    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    /**
     * Get the page length
     *
     * @return int|null The page length
     */
    public function getLength(): ?int
    {
        return $this->length;
    }
}

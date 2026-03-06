<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Exceptions;

use Canvastack\Canvastack\Exceptions\CanvastackException;

/**
 * Base exception for all table-related errors.
 *
 * This is the parent exception class for all table component exceptions.
 * Catch this exception to handle any table-related error.
 *
 * @package Canvastack\Canvastack\Components\Table\Exceptions
 */
class TableException extends CanvastackException
{
    /**
     * Create a new table exception instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

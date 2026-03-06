<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Exceptions;

use Exception;

/**
 * Base exception for all CanvaStack-related errors.
 *
 * This is the parent exception class for all CanvaStack exceptions.
 * Catch this exception to handle any CanvaStack-related error.
 *
 * @package Canvastack\Canvastack\Exceptions
 */
class CanvastackException extends Exception
{
    /**
     * Create a new CanvaStack exception instance.
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

<?php

namespace Canvastack\Canvastack\Library\Exceptions;

use Exception;

/**
 * General exception thrown by controller operations
 * 
 * This exception is used for general controller errors that don't fit
 * into more specific exception categories.
 * 
 * @package Canvastack\Canvastack\Library\Exceptions
 */
class ControllerException extends Exception
{
    /**
     * Create a new controller exception
     * 
     * @param string $message The exception message
     * @param int $code The exception code (default: 500 Internal Server Error)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message = "Controller error", int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

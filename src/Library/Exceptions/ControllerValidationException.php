<?php

namespace Canvastack\Canvastack\Library\Exceptions;

use Exception;

/**
 * Exception thrown when controller input validation fails
 * 
 * This exception is used to indicate that user input or request parameters
 * failed validation checks in controller methods.
 * 
 * @package Canvastack\Canvastack\Library\Exceptions
 */
class ControllerValidationException extends Exception
{
    /**
     * Create a new controller validation exception
     * 
     * @param string $message The exception message
     * @param int $code The exception code (default: 422 Unprocessable Entity)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message = "Validation failed", int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

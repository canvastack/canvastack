<?php

namespace Canvastack\Canvastack\Library\Exceptions;

use Exception;

/**
 * Exception thrown when CSRF token validation fails
 * 
 * This exception is used to indicate that a request failed CSRF token validation,
 * which is a critical security check to prevent Cross-Site Request Forgery attacks.
 * 
 * @package Canvastack\Canvastack\Library\Exceptions
 */
class CSRFException extends Exception
{
    /**
     * Create a new CSRF exception
     * 
     * @param string $message The exception message
     * @param int $code The exception code (default: 419 Page Expired)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message = "CSRF token mismatch", int $code = 419, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace Canvastack\Canvastack\Library\Components\Table\Exceptions;

/**
 * SecurityException - Custom exception for security violations
 * 
 * This exception is thrown when security validation fails
 * such as unauthorized table access, invalid field names,
 * or potential SQL injection attempts.
 */
class SecurityException extends \Exception
{
    /**
     * Create a new SecurityException instance
     *
     * @param string $message
     * @param array $context
     * @param \Throwable $previous
     */
    public function __construct(string $message = 'Security violation detected', array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
        
        // Merge basic context with provided context
        $fullContext = array_merge([
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'ip' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'user_id' => auth()->id() ?? null,
            'timestamp' => now()->toISOString()
        ], $context);
        
        // Log security exception immediately
        \Log::channel('security')->error('SECURITY_EXCEPTION: ' . $message, $fullContext);
    }
}
<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

use Exception;

/**
 * Controller Exception Base Class
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Base exception class for all controller-related exceptions.
 * Provides common functionality for context data, user messages, and logging.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Error Handling
 * @version 1.0.0
 * 
 * @example
 * ```php
 * try {
 *     // Controller operation
 * } catch (ControllerException $e) {
 *     Log::error($e->getMessage(), $e->getContext());
 *     return response()->json([
 *         'error' => $e->getUserMessage()
 *     ], $e->getCode());
 * }
 * ```
 */
class ControllerException extends Exception
{
    /**
     * @var array Context data for debugging
     */
    protected array $context = [];
    
    /**
     * @var string User-friendly error message
     */
    protected string $userMessage = 'An error occurred while processing your request.';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data for debugging
     * @param int $code HTTP status code (default: 500)
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Controller error occurred',
        array $context = [],
        int $code = 500,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    /**
     * Get exception context data
     * 
     * Returns additional context information that can be used for debugging,
     * logging, or error reporting. This may include request data, user info,
     * or any other relevant details.
     * 
     * @return array Context data with debugging information
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get user-friendly error message
     * 
     * Returns a sanitized, user-friendly error message that is safe to display
     * to end users. This message should not contain sensitive technical details.
     * 
     * @return string User-friendly error message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
    
    /**
     * Set user-friendly error message
     * 
     * Allows customization of the user-facing error message.
     * 
     * @param string $message User-friendly error message
     * @return self For method chaining
     */
    public function setUserMessage(string $message): self
    {
        $this->userMessage = $message;
        return $this;
    }
    
    /**
     * Add context data
     * 
     * Adds additional context information to the exception.
     * 
     * @param string $key Context key
     * @param mixed $value Context value
     * @return self For method chaining
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
    
    /**
     * Get full exception details for logging
     * 
     * Returns a comprehensive array of exception details suitable for logging,
     * including message, code, context, file, line, and stack trace.
     * 
     * @return array Complete exception details
     */
    public function getDetails(): array
    {
        return [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}

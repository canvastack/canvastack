<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * Controller Validation Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when input validation fails in controller operations.
 * This includes form validation, request parameter validation, and data
 * integrity checks.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Validation
 * @version 1.0.0
 * 
 * @example
 * ```php
 * // In controller validation
 * $validator = Validator::make($request->all(), $rules);
 * 
 * if ($validator->fails()) {
 *     throw new ControllerValidationException(
 *         'Validation failed',
 *         [
 *             'errors' => $validator->errors()->toArray(),
 *             'input' => $request->except(['password']),
 *             'rules' => $rules
 *         ]
 *     );
 * }
 * ```
 */
class ControllerValidationException extends ControllerException
{
    /**
     * @var array Validation errors
     */
    protected array $errors = [];
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including validation errors
     * @param int $code HTTP status code (default: 422 Unprocessable Entity)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Validation failed',
        array $context = [],
        int $code = 422,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->errors = $context['errors'] ?? [];
        $this->userMessage = 'The provided data is invalid. Please check your input and try again.';
    }
    
    /**
     * Get validation errors
     * 
     * Returns array of validation errors keyed by field name.
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field Field name
     * @return array Errors for the specified field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if a specific field has errors
     * 
     * @param string $field Field name
     * @return bool True if field has errors
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    /**
     * Get first error message
     * 
     * Returns the first validation error message, useful for displaying
     * a single error to the user.
     * 
     * @return string|null First error message or null if no errors
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstField = array_key_first($this->errors);
        $fieldErrors = $this->errors[$firstField];
        
        return is_array($fieldErrors) ? ($fieldErrors[0] ?? null) : $fieldErrors;
    }
    
    /**
     * Get all error messages as flat array
     * 
     * Returns all validation error messages in a flat array, useful for
     * displaying all errors in a list.
     * 
     * @return array Flat array of error messages
     */
    public function getAllMessages(): array
    {
        $messages = [];
        
        foreach ($this->errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                $messages = array_merge($messages, $fieldErrors);
            } else {
                $messages[] = $fieldErrors;
            }
        }
        
        return $messages;
    }
    
    /**
     * Get validation summary
     * 
     * Returns a summary of validation errors suitable for logging or display.
     * 
     * @return array Validation summary
     */
    public function getValidationSummary(): array
    {
        return [
            'total_errors' => count($this->errors),
            'failed_fields' => array_keys($this->errors),
            'errors' => $this->errors,
            'input' => $this->context['input'] ?? [],
            'rules' => $this->context['rules'] ?? [],
        ];
    }
    
    /**
     * Convert to JSON response format
     * 
     * Returns validation errors in a format suitable for JSON API responses.
     * 
     * @return array JSON-ready validation response
     */
    public function toJsonResponse(): array
    {
        return [
            'message' => $this->getUserMessage(),
            'errors' => $this->errors,
            'code' => $this->getCode(),
        ];
    }
    
    // ==================== Factory Methods ====================
    
    /**
     * Create exception for invalid pagination parameters
     * 
     * @param int $start Start parameter value
     * @param int $length Length parameter value
     * @param string $reason Reason for validation failure
     * @param array $context Additional context data
     * @return static
     */
    public static function invalidPaginationParams(
        int $start,
        int $length,
        string $reason,
        array $context = []
    ): static {
        return new static(
            "Invalid pagination parameters: {$reason}",
            array_merge($context, [
                'start' => $start,
                'length' => $length,
                'errors' => [
                    'pagination' => [$reason],
                ],
            ])
        );
    }
    
    /**
     * Create exception for invalid parameter
     * 
     * @param string $paramName Parameter name
     * @param mixed $paramValue Parameter value
     * @param string $reason Reason for validation failure
     * @param array $context Additional context data
     * @return static
     */
    public static function invalidParameter(
        string $paramName,
        mixed $paramValue,
        string $reason,
        array $context = []
    ): static {
        return new static(
            "Invalid parameter '{$paramName}': {$reason}",
            array_merge($context, [
                'parameter' => $paramName,
                'value' => is_scalar($paramValue) ? $paramValue : gettype($paramValue),
                'errors' => [
                    $paramName => [$reason],
                ],
            ])
        );
    }
    
    /**
     * Create exception for invalid filter value
     * 
     * @param string $fieldName Field name being filtered
     * @param mixed $value Filter value
     * @param string $reason Reason for validation failure
     * @param array $context Additional context data
     * @return static
     */
    public static function invalidFilterValue(
        string $fieldName,
        mixed $value,
        string $reason,
        array $context = []
    ): static {
        return new static(
            "Invalid filter value for '{$fieldName}': {$reason}",
            array_merge($context, [
                'field_name' => $fieldName,
                'value' => is_scalar($value) ? $value : gettype($value),
                'errors' => [
                    $fieldName => [$reason],
                ],
            ])
        );
    }
    
    /**
     * Create exception for invalid route parameter
     * 
     * @param string $paramType Expected parameter type
     * @param mixed $paramValue Actual parameter value
     * @param string $reason Reason for validation failure
     * @param array $context Additional context data
     * @return static
     */
    public static function invalidRouteParameter(
        string $paramType,
        mixed $paramValue,
        string $reason,
        array $context = []
    ): static {
        return new static(
            "Invalid route parameter: {$reason}",
            array_merge($context, [
                'expected_type' => $paramType,
                'value' => is_scalar($paramValue) ? $paramValue : gettype($paramValue),
                'errors' => [
                    'route_parameter' => [$reason],
                ],
            ])
        );
    }
}


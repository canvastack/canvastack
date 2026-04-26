<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * DataTables Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when DataTables operations fail.
 * This includes request validation failures, processing errors, and
 * response generation issues.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category DataTables
 * @version 1.0.0
 * 
 * @example
 * ```php
 * // In DataTables request processing
 * if (!isset($request['draw'])) {
 *     throw new DataTablesException(
 *         'Invalid DataTables request: missing draw parameter',
 *         [
 *             'request' => $request,
 *             'required_params' => ['draw', 'start', 'length']
 *         ]
 *     );
 * }
 * ```
 */
class DataTablesException extends ControllerException
{
    /**
     * @var string DataTables error type
     */
    protected string $datatableErrorType = 'unknown';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including request details
     * @param int $code HTTP status code (default: 400 Bad Request)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'DataTables error occurred',
        array $context = [],
        int $code = 400,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->datatableErrorType = $context['error_type'] ?? 'unknown';
        $this->userMessage = 'Failed to load table data. Please refresh the page and try again.';
    }
    
    /**
     * Get DataTables error type
     * 
     * Returns the type of DataTables error (e.g., 'invalid_request', 'processing_failed', 'query_error').
     * 
     * @return string DataTables error type
     */
    public function getDatatableErrorType(): string
    {
        return $this->datatableErrorType;
    }
    
    /**
     * Get DataTables request details
     * 
     * Returns detailed information about the DataTables request and error.
     * 
     * @return array DataTables error details
     */
    public function getDatatableDetails(): array
    {
        return [
            'error_type' => $this->datatableErrorType,
            'draw' => $this->context['draw'] ?? null,
            'start' => $this->context['start'] ?? null,
            'length' => $this->context['length'] ?? null,
            'search' => $this->context['search'] ?? null,
            'order' => $this->context['order'] ?? null,
            'columns' => $this->context['columns'] ?? null,
            'table' => $this->context['table'] ?? 'unknown',
        ];
    }
    
    /**
     * Get user-friendly error message based on error type
     * 
     * @return string Contextual user-friendly message
     */
    public function getUserMessage(): string
    {
        switch ($this->datatableErrorType) {
            case 'invalid_request':
                return 'Invalid table request. Please refresh the page and try again.';
                
            case 'processing_failed':
                return 'Failed to process table data. Please try again later.';
                
            case 'query_error':
                return 'Database error while loading table data. Please contact support.';
                
            case 'invalid_parameters':
                return 'Invalid table parameters. Please refresh the page.';
                
            case 'timeout':
                return 'Table data loading timed out. Please try with fewer filters.';
                
            case 'permission_denied':
                return 'You do not have permission to view this table data.';
                
            default:
                return $this->userMessage;
        }
    }
    
    /**
     * Get DataTables error response
     * 
     * Returns error response in DataTables format for AJAX requests.
     * 
     * @return array DataTables error response
     */
    public function toDataTablesResponse(): array
    {
        return [
            'draw' => $this->context['draw'] ?? 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $this->getUserMessage(),
        ];
    }
    
    /**
     * Create exception for invalid DataTables request
     * 
     * @param array $request Request data
     * @param array $missingParams Missing required parameters
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidRequest(
        array $request,
        array $missingParams,
        array $additionalContext = []
    ): self {
        return new self(
            'Invalid DataTables request: missing parameters - ' . implode(', ', $missingParams),
            array_merge([
                'error_type' => 'invalid_request',
                'request' => $request,
                'missing_params' => $missingParams,
            ], $additionalContext),
            400
        );
    }
    
    /**
     * Create exception for DataTables processing failure
     * 
     * @param string $reason Failure reason
     * @param array $request Request data
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function processingFailed(
        string $reason,
        array $request = [],
        array $additionalContext = []
    ): self {
        return new self(
            "DataTables processing failed: {$reason}",
            array_merge([
                'error_type' => 'processing_failed',
                'reason' => $reason,
                'draw' => $request['draw'] ?? null,
            ], $additionalContext),
            500
        );
    }
    
    /**
     * Create exception for DataTables query error
     * 
     * @param string $query SQL query
     * @param string $error Database error message
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function queryError(
        string $query,
        string $error,
        array $additionalContext = []
    ): self {
        return new self(
            "DataTables query error: {$error}",
            array_merge([
                'error_type' => 'query_error',
                'query' => substr($query, 0, 200), // Truncate long queries
                'error' => $error,
            ], $additionalContext),
            500
        );
    }
    
    /**
     * Create exception for invalid DataTables parameters
     * 
     * @param string $parameter Parameter name
     * @param mixed $value Invalid value
     * @param string $reason Validation failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidParameter(
        string $parameter,
        $value,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Invalid DataTables parameter '{$parameter}': {$reason}",
            array_merge([
                'error_type' => 'invalid_parameters',
                'parameter' => $parameter,
                'value' => $value,
                'reason' => $reason,
            ], $additionalContext),
            400
        );
    }
    
    /**
     * Create exception for DataTables timeout
     * 
     * @param int $timeout Timeout duration in seconds
     * @param array $request Request data
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function timeout(
        int $timeout,
        array $request = [],
        array $additionalContext = []
    ): self {
        return new self(
            "DataTables request timed out after {$timeout} seconds",
            array_merge([
                'error_type' => 'timeout',
                'timeout' => $timeout,
                'draw' => $request['draw'] ?? null,
                'filters' => $request['search'] ?? null,
            ], $additionalContext),
            504
        );
    }
    
    /**
     * Create exception for permission denied
     * 
     * @param int $userId User ID
     * @param string $table Table name
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function permissionDenied(
        int $userId,
        string $table,
        array $additionalContext = []
    ): self {
        return new self(
            "User {$userId} does not have permission to access table '{$table}'",
            array_merge([
                'error_type' => 'permission_denied',
                'user_id' => $userId,
                'table' => $table,
            ], $additionalContext),
            403
        );
    }
}

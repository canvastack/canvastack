<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * Route Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when route-related operations fail.
 * This includes missing routes, invalid route parameters, route generation
 * failures, and action button generation errors.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Routing
 * @version 1.0.0
 * 
 * @example
 * ```php
 * // In route info generation
 * if (!Route::has($routeName)) {
 *     throw new RouteException(
 *         'Route not found',
 *         [
 *             'route_name' => $routeName,
 *             'controller' => get_class($this),
 *             'action' => $action
 *         ]
 *     );
 * }
 * ```
 */
class RouteException extends ControllerException
{
    /**
     * @var string Route error type
     */
    protected string $routeErrorType = 'unknown';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including route details
     * @param int $code HTTP status code (default: 404 Not Found)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Route error occurred',
        array $context = [],
        int $code = 404,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->routeErrorType = $context['error_type'] ?? 'unknown';
        $this->userMessage = 'The requested page could not be found.';
    }
    
    /**
     * Get route error type
     * 
     * Returns the type of route error (e.g., 'not_found', 'invalid_params', 'generation_failed').
     * 
     * @return string Route error type
     */
    public function getRouteErrorType(): string
    {
        return $this->routeErrorType;
    }
    
    /**
     * Get route details
     * 
     * Returns detailed information about the route error.
     * 
     * @return array Route error details
     */
    public function getRouteDetails(): array
    {
        return [
            'route_name' => $this->context['route_name'] ?? 'unknown',
            'controller' => $this->context['controller'] ?? 'unknown',
            'action' => $this->context['action'] ?? 'unknown',
            'parameters' => $this->context['parameters'] ?? [],
            'error_type' => $this->routeErrorType,
            'current_path' => $this->context['current_path'] ?? 'unknown',
        ];
    }
    
    /**
     * Get user-friendly error message based on error type
     * 
     * @return string Contextual user-friendly message
     */
    public function getUserMessage(): string
    {
        switch ($this->routeErrorType) {
            case 'not_found':
                return 'The requested page could not be found.';
                
            case 'invalid_params':
                return 'Invalid page parameters. Please check the URL and try again.';
                
            case 'generation_failed':
                return 'Failed to generate page URL. Please contact support.';
                
            case 'action_button_failed':
                return 'Failed to generate action buttons. Some features may not be available.';
                
            case 'missing_controller':
                return 'The requested controller could not be found.';
                
            case 'invalid_action':
                return 'The requested action is not available.';
                
            default:
                return $this->userMessage;
        }
    }
    
    /**
     * Check if error should be logged
     * 
     * Some route errors are expected (e.g., 404s) and don't need detailed logging.
     * 
     * @return bool True if error should be logged
     */
    public function shouldLog(): bool
    {
        // Don't log common 404s
        if ($this->routeErrorType === 'not_found' && $this->getCode() === 404) {
            return false;
        }
        
        // Log all other route errors
        return true;
    }
    
    /**
     * Create exception for route not found
     * 
     * @param string $routeName Route name
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function notFound(
        string $routeName,
        array $additionalContext = []
    ): self {
        return new self(
            "Route not found: {$routeName}",
            array_merge([
                'error_type' => 'not_found',
                'route_name' => $routeName,
            ], $additionalContext),
            404
        );
    }
    
    /**
     * Create exception for invalid route parameters
     * 
     * @param string $routeName Route name
     * @param array $parameters Invalid parameters
     * @param string $reason Validation failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidParameters(
        string $routeName,
        array $parameters,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Invalid parameters for route '{$routeName}': {$reason}",
            array_merge([
                'error_type' => 'invalid_params',
                'route_name' => $routeName,
                'parameters' => $parameters,
                'reason' => $reason,
            ], $additionalContext),
            400
        );
    }
    
    /**
     * Create exception for route generation failure
     * 
     * @param string $routeName Route name
     * @param string $reason Failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function generationFailed(
        string $routeName,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Failed to generate route '{$routeName}': {$reason}",
            array_merge([
                'error_type' => 'generation_failed',
                'route_name' => $routeName,
                'reason' => $reason,
            ], $additionalContext),
            500
        );
    }
    
    /**
     * Create exception for action button generation failure
     * 
     * @param string $action Action name
     * @param string $reason Failure reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function actionButtonFailed(
        string $action,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Failed to generate action button for '{$action}': {$reason}",
            array_merge([
                'error_type' => 'action_button_failed',
                'action' => $action,
                'reason' => $reason,
            ], $additionalContext),
            500
        );
    }
    
    /**
     * Create exception for missing controller
     * 
     * @param string $controllerName Controller name
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function missingController(
        string $controllerName,
        array $additionalContext = []
    ): self {
        return new self(
            "Controller not found: {$controllerName}",
            array_merge([
                'error_type' => 'missing_controller',
                'controller' => $controllerName,
            ], $additionalContext),
            404
        );
    }
    
    /**
     * Create exception for route not found (alias for notFound)
     * 
     * @param string $message Error message
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function routeNotFound(
        string $message,
        array $additionalContext = []
    ): self {
        return new self(
            $message,
            array_merge([
                'error_type' => 'not_found',
            ], $additionalContext),
            404
        );
    }
    
    /**
     * Create exception for invalid route structure
     * 
     * @param string $message Error message
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidRouteStructure(
        string $message,
        array $additionalContext = []
    ): self {
        return new self(
            $message,
            array_merge([
                'error_type' => 'invalid_structure',
            ], $additionalContext),
            500
        );
    }
    
    /**
     * Create exception for invalid parameter
     * 
     * @param string $message Error message
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidParameter(
        string $message,
        array $additionalContext = []
    ): self {
        return new self(
            $message,
            array_merge([
                'error_type' => 'invalid_parameter',
            ], $additionalContext),
            400
        );
    }
    
    /**
     * Create exception for invalid URL
     * 
     * @param string $message Error message
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function invalidUrl(
        string $message,
        array $additionalContext = []
    ): self {
        return new self(
            $message,
            array_merge([
                'error_type' => 'invalid_url',
            ], $additionalContext),
            400
        );
    }
}

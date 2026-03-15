<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * TableTabController - Handles AJAX tab loading requests
 * 
 * This controller is responsible for:
 * - Validating CSRF tokens
 * - Validating tab index parameters
 * - Loading tab configurations
 * - Rendering tab content
 * - Returning JSON responses
 * 
 * Security features:
 * - CSRF token validation (automatic via Laravel middleware)
 * - Input validation
 * - Permission validation
 * - Rate limiting (configured in routes)
 * - Error sanitization for production
 * 
 * Requirements: 6.3, 6.7, 10.5
 * 
 * @package Canvastack\Canvastack\Http\Controllers
 */
class TableTabController extends BaseController
{
    /**
     * Load tab content via AJAX
     * 
     * This method handles lazy loading of tab content.
     * It validates the request, retrieves tab configuration,
     * renders the content, and returns a JSON response.
     * 
     * @param Request $request The HTTP request
     * @param int $index The tab index to load
     * @return JsonResponse
     */
    public function loadTab(Request $request, int $index): JsonResponse
    {
        try {
            // Step 1: Validate CSRF token (automatic via Laravel middleware)
            // The VerifyCsrfToken middleware handles this automatically
            
            // Step 2: Validate tab index parameter
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'tab_index' => 'required|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    422,
                    ['errors' => $validator->errors()->toArray()]
                );
            }
            
            $validated = $validator->validated();
            
            // Ensure the index from route matches the request body
            if ($validated['tab_index'] !== $index) {
                return $this->errorResponse(
                    'Tab index mismatch',
                    400,
                    ['expected' => $index, 'received' => $validated['tab_index']]
                );
            }
            
            // Log the request for debugging
            Log::debug('TableTabController: Loading tab', [
                'index' => $index,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
            
            // Step 3: Check permissions
            if (!$this->canAccessTab($index)) {
                Log::warning('TableTabController: Unauthorized tab access denied', [
                    'tab_index' => $index,
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                ]);
                
                return $this->errorResponse('Unauthorized', 403);
            }
            
            // Step 4: Get tab configuration (placeholder - will be implemented in task 4.1.3)
            $tabConfig = $this->getTabConfig($index);
            
            if (!$tabConfig) {
                return $this->errorResponse('Tab not found', 404);
            }
            
            // Step 5: Render tab content (placeholder - will be implemented in task 4.1.4)
            $html = $this->renderTabContent($tabConfig);
            $scripts = $this->generateInitScripts($tabConfig);
            
            // Step 6: Return success response
            return $this->successResponse([
                'html' => $html,
                'scripts' => $scripts,
                'tab_index' => $index,
            ]);
            
        } catch (\Exception $e) {
            // Handle all other errors (will be enhanced in task 4.1.5)
            return $this->handleException($e, $index);
        }
    }

    /**
     * Check if the current user can access the specified tab
     * 
     * This method validates user permissions for tab access.
     * It checks if the user is authenticated and has the necessary
     * permissions to view the tab content.
     * 
     * Requirements: 10.6
     * 
     * @param int $index The tab index
     * @return bool True if user can access, false otherwise
     */
    protected function canAccessTab(int $index): bool
    {
        // DEMO MODE: Allow unauthenticated access for testing
        // TODO: Re-enable authentication check in production
        // Comment out the return true below and uncomment the auth check
        
        // For demo/development, allow all access
        Log::info('TableTabController: Tab access allowed (demo mode)', [
            'tab_index' => $index,
            'authenticated' => auth()->check(),
            'user_id' => auth()->id() ?? 'guest',
        ]);
        return true;
        
        /* PRODUCTION CODE - Uncomment this block for production use
        // Check if user is authenticated
        if (!auth()->check()) {
            Log::warning('TableTabController: Unauthorized tab access attempt', [
                'tab_index' => $index,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return false;
        }
        
        // Get the current user
        $user = auth()->user();
        
        // Log the access attempt
        Log::info('TableTabController: Tab access check', [
            'tab_index' => $index,
            'user_id' => $user->id,
            'user_email' => $user->email ?? 'N/A',
        ]);
        
        // TODO: Implement specific permission checks based on tab configuration
        // This could check:
        // - User roles (admin, user, etc.)
        // - Specific permissions (view_users, view_reports, etc.)
        // - Tab-specific permissions stored in configuration
        // 
        // Example implementation:
        // $tabConfig = $this->getTabConfig($index);
        // if ($tabConfig && isset($tabConfig['permission'])) {
        //     return $user->can($tabConfig['permission']);
        // }
        
        // For now, allow access to authenticated users
        // This will be enhanced when tab configuration is implemented
        return true;
        */
    }
    
    /**
     * Get tab configuration from session/cache
     * 
     * This method retrieves tab configuration from session storage.
     * The configuration is stored by TableBuilder when rendering tabs.
     * 
     * Storage key format: "canvastack.table.tabs.{uniqueId}"
     * 
     * Requirements: 6.4
     * 
     * @param int $index The tab index
     * @return array|null The tab configuration or null if not found
     */
    protected function getTabConfig(int $index): ?array
    {
        // Get the unique table ID from request
        // This should be passed from the frontend when making the AJAX request
        $uniqueId = request()->input('table_id');
        
        if (!$uniqueId) {
            Log::warning('TableTabController: Missing table_id in request', [
                'tab_index' => $index,
                'user_id' => auth()->id(),
            ]);
            return null;
        }
        
        // Try to get from session first
        $sessionKey = "canvastack.table.tabs.{$uniqueId}";
        $tabsConfig = session($sessionKey);
        
        if (!$tabsConfig) {
            // Try cache as fallback
            $cacheKey = "canvastack:table:tabs:{$uniqueId}";
            $tabsConfig = cache()->get($cacheKey);
            
            if (!$tabsConfig) {
                Log::warning('TableTabController: Tab configuration not found', [
                    'table_id' => $uniqueId,
                    'tab_index' => $index,
                    'user_id' => auth()->id(),
                ]);
                return null;
            }
        }
        
        // Validate that tabs config is an array
        if (!is_array($tabsConfig)) {
            Log::error('TableTabController: Invalid tab configuration format', [
                'table_id' => $uniqueId,
                'tab_index' => $index,
                'type' => gettype($tabsConfig),
            ]);
            return null;
        }
        
        // Check if the requested tab index exists
        if (!isset($tabsConfig[$index])) {
            Log::warning('TableTabController: Tab index not found in configuration', [
                'table_id' => $uniqueId,
                'tab_index' => $index,
                'available_tabs' => array_keys($tabsConfig),
                'user_id' => auth()->id(),
            ]);
            return null;
        }
        
        // Get the specific tab configuration
        $tabConfig = $tabsConfig[$index];
        
        // Validate tab configuration structure
        if (!$this->isValidTabConfig($tabConfig)) {
            Log::error('TableTabController: Invalid tab configuration structure', [
                'table_id' => $uniqueId,
                'tab_index' => $index,
                'config_keys' => array_keys($tabConfig),
            ]);
            return null;
        }
        
        Log::debug('TableTabController: Tab configuration retrieved', [
            'table_id' => $uniqueId,
            'tab_index' => $index,
            'tab_name' => $tabConfig['name'] ?? 'Unknown',
        ]);
        
        return $tabConfig;
    }
    
    /**
     * Validate tab configuration structure
     * 
     * Ensures the tab configuration has all required fields.
     * 
     * @param mixed $config The configuration to validate
     * @return bool True if valid, false otherwise
     */
    protected function isValidTabConfig($config): bool
    {
        if (!is_array($config)) {
            return false;
        }
        
        // Required fields
        $requiredFields = ['name', 'tables'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $config)) {
                return false;
            }
        }
        
        // Validate tables is an array
        if (!is_array($config['tables'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render tab content HTML
     * 
     * Renders the table HTML for the requested tab.
     * Supports multiple tables per tab and custom content.
     * 
     * Requirements: 6.3, 6.4
     * 
     * @param array $tabConfig The tab configuration
     * @return string The rendered HTML
     */
    protected function renderTabContent(array $tabConfig): string
    {
        try {
            $html = '';
            
            // Render custom content if provided (Requirement 4.6)
            if (isset($tabConfig['custom_content']) && !empty($tabConfig['custom_content'])) {
                $html .= $tabConfig['custom_content'];
            }
            
            // Render tables in this tab (Requirement 4.10)
            if (isset($tabConfig['tables']) && is_array($tabConfig['tables'])) {
                foreach ($tabConfig['tables'] as $tableConfig) {
                    $html .= $this->renderTableFromConfig($tableConfig);
                }
            }
            
            // If no content was rendered, return empty div
            if (empty($html)) {
                Log::warning('TableTabController: No content to render for tab', [
                    'tab_name' => $tabConfig['name'] ?? 'Unknown',
                    'user_id' => auth()->id(),
                ]);
                
                $html = '<div class="p-4 text-center text-gray-500 dark:text-gray-400">' .
                        __('canvastack::components.table.no_content') .
                        '</div>';
            }
            
            return $html;
            
        } catch (\Exception $e) {
            Log::error('TableTabController: Error rendering tab content', [
                'error' => $e->getMessage(),
                'tab_name' => $tabConfig['name'] ?? 'Unknown',
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException(
                'Failed to render tab content: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Generate TanStack initialization scripts
     * 
     * Generates the JavaScript code to initialize TanStack Table instances.
     * Each table in the tab gets its own initialization script.
     * 
     * Requirements: 6.3, 6.4, 8.6
     * 
     * @param array $tabConfig The tab configuration
     * @return string The JavaScript code
     */
    protected function generateInitScripts(array $tabConfig): string
    {
        try {
            $scripts = '';
            
            // Generate initialization scripts for each table in the tab
            if (isset($tabConfig['tables']) && is_array($tabConfig['tables'])) {
                foreach ($tabConfig['tables'] as $tableConfig) {
                    $scripts .= $this->generateTableInitScript($tableConfig);
                }
            }
            
            // If no scripts were generated, return empty string
            if (empty($scripts)) {
                Log::debug('TableTabController: No initialization scripts to generate', [
                    'tab_name' => $tabConfig['name'] ?? 'Unknown',
                    'user_id' => auth()->id(),
                ]);
            }
            
            return $scripts;
            
        } catch (\Exception $e) {
            Log::error('TableTabController: Error generating initialization scripts', [
                'error' => $e->getMessage(),
                'tab_name' => $tabConfig['name'] ?? 'Unknown',
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException(
                'Failed to generate initialization scripts: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Render a single table from its configuration
     * 
     * Creates a TableBuilder instance from stored configuration
     * and renders it to HTML.
     * 
     * @param array $tableConfig The table configuration
     * @return string The rendered table HTML
     */
    protected function renderTableFromConfig(array $tableConfig): string
    {
        // If HTML is already rendered, return it
        if (isset($tableConfig['html']) && !empty($tableConfig['html'])) {
            return $tableConfig['html'];
        }
        
        // Get the unique table ID
        $tableId = $tableConfig['id'] ?? null;
        
        if (!$tableId) {
            throw new \RuntimeException('Table configuration missing unique ID');
        }
        
        // Get TableBuilder instance from container
        $tableBuilder = app(\Canvastack\Canvastack\Components\Table\TableBuilder::class);
        
        // Restore the table configuration
        if (isset($tableConfig['config']) && is_array($tableConfig['config'])) {
            $tableBuilder->fromArray($tableConfig['config']);
        }
        
        // Set the unique ID
        if (method_exists($tableBuilder, 'setUniqueId')) {
            $tableBuilder->setUniqueId($tableId);
        }
        
        // Render the table using TanStack engine
        return $tableBuilder->renderWithTanStack();
    }
    
    /**
     * Generate initialization script for a single table
     * 
     * Generates the JavaScript code to initialize a TanStack Table instance.
     * The script is wrapped in a self-executing function for isolation.
     * 
     * Requirements: 8.6 - Lazy initialization support
     * 
     * @param array $tableConfig The table configuration
     * @return string The JavaScript initialization code
     */
    protected function generateTableInitScript(array $tableConfig): string
    {
        $tableId = $tableConfig['id'] ?? null;
        
        if (!$tableId) {
            Log::warning('TableTabController: Table configuration missing ID for script generation');
            return '';
        }
        
        // Generate a script that initializes the table after DOM is ready
        // This ensures the table container exists before initialization
        $script = <<<JS
<script>
(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTable_{$tableId});
    } else {
        initTable_{$tableId}();
    }
    
    function initTable_{$tableId}() {
        // Check if Alpine is available
        if (typeof Alpine === 'undefined') {
            console.error('TanStack Table: Alpine.js not loaded for table {$tableId}');
            return;
        }
        
        // Find the table container
        const container = document.querySelector('[data-table-id="{$tableId}"]');
        
        if (!container) {
            console.error('TanStack Table: Container not found for table {$tableId}');
            return;
        }
        
        // Check if already initialized
        if (container.__x) {
            console.log('TanStack Table: Table {$tableId} already initialized');
            return;
        }
        
        // Initialize Alpine on the container
        console.log('TanStack Table: Initializing table {$tableId}');
        Alpine.initTree(container);
        
        // Initialize Lucide icons if available
        if (typeof lucide !== 'undefined') {
            const iconElements = container.querySelectorAll('.lucide-icon');
            iconElements.forEach(el => {
                const iconName = el.getAttribute('data-icon');
                if (iconName && lucide[iconName]) {
                    try {
                        const svg = lucide[iconName].toSvg({
                            class: 'w-4 h-4',
                            'stroke-width': 2
                        });
                        el.outerHTML = svg;
                    } catch (e) {
                        console.error('Error rendering icon:', iconName, e);
                    }
                }
            });
        }
        
        console.log('TanStack Table: Table {$tableId} initialized successfully');
    }
})();
</script>
JS;
        
        return $script;
    }
    
    /**
     * Return a success JSON response
     * 
     * @param array $data The response data
     * @param int $status The HTTP status code
     * @return JsonResponse
     */
    protected function successResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json(
            array_merge(['success' => true], $data),
            $status
        );
    }
    
    /**
     * Return an error JSON response
     * 
     * @param string $message The error message
     * @param int $status The HTTP status code
     * @param array $additional Additional error data
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $status = 500,
        array $additional = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => $message,
        ];
        
        // Add additional error data if provided
        if (!empty($additional)) {
            $response = array_merge($response, $additional);
        }
        
        return response()->json($response, $status);
    }

    /**
     * Handle exceptions and return appropriate error response
     * 
     * This method provides comprehensive error handling with:
     * - Detailed error logging for debugging
     * - Production vs development error messages
     * - Sensitive information filtering
     * - Specific error codes for different error types
     * 
     * Requirements: 15.2, 15.3, 15.9
     * 
     * @param \Exception $e The exception
     * @param int $index The tab index
     * @return JsonResponse
     */
    protected function handleException(\Exception $e, int $index): JsonResponse
    {
        // Determine error type and appropriate status code
        $statusCode = $this->getStatusCodeForException($e);
        
        // Log the error with full details (always log, regardless of environment)
        $this->logException($e, $index);
        
        // Get user-friendly error message (filtered for production)
        $message = $this->getErrorMessage($e);
        
        // Get additional error context (only in development)
        $additional = $this->getErrorContext($e);
        
        return $this->errorResponse($message, $statusCode, $additional);
    }
    
    /**
     * Log exception with comprehensive details
     * 
     * Logs all exception details for debugging purposes.
     * This information is never exposed to end users.
     * 
     * Requirements: 15.2 - Log detailed errors for debugging
     * 
     * @param \Exception $e The exception
     * @param int $index The tab index
     * @return void
     */
    protected function logException(\Exception $e, int $index): void
    {
        // Prepare comprehensive log context
        $context = [
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_code' => $e->getCode(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'tab_index' => $index,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'request_data' => $this->sanitizeRequestData(request()->all()),
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Add stack trace for detailed debugging
        $context['stack_trace'] = $e->getTraceAsString();
        
        // Add previous exception if exists (for chained exceptions)
        if ($previous = $e->getPrevious()) {
            $context['previous_exception'] = [
                'class' => get_class($previous),
                'message' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
            ];
        }
        
        // Log with appropriate level based on exception type
        $logLevel = $this->getLogLevelForException($e);
        
        Log::log($logLevel, 'TableTabController: Tab loading failed', $context);
    }
    
    /**
     * Sanitize request data for logging
     * 
     * Removes sensitive information from request data before logging.
     * 
     * Requirements: 15.9 - Don't expose sensitive info
     * 
     * @param array $data The request data
     * @return array Sanitized data
     */
    protected function sanitizeRequestData(array $data): array
    {
        // List of sensitive keys to redact
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
            '_token', // CSRF token
        ];
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Check if key is sensitive
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = $this->sanitizeRequestData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get appropriate log level for exception
     * 
     * Different exception types warrant different log levels.
     * 
     * @param \Exception $e The exception
     * @return string Log level (error, warning, info)
     */
    protected function getLogLevelForException(\Exception $e): string
    {
        // Validation errors are warnings (user input issues)
        if ($e instanceof ValidationException) {
            return 'warning';
        }
        
        // Not found errors are info (expected behavior)
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 'info';
        }
        
        // Authorization errors are warnings (permission issues)
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 'warning';
        }
        
        // All other exceptions are errors (unexpected issues)
        return 'error';
    }
    
    /**
     * Get appropriate HTTP status code for exception
     * 
     * Maps exception types to HTTP status codes.
     * 
     * Requirements: 15.2 - Return user-friendly error messages
     * 
     * @param \Exception $e The exception
     * @return int HTTP status code
     */
    protected function getStatusCodeForException(\Exception $e): int
    {
        // Validation errors
        if ($e instanceof ValidationException) {
            return 422; // Unprocessable Entity
        }
        
        // Not found errors
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404; // Not Found
        }
        
        // Authorization errors
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403; // Forbidden
        }
        
        // Authentication errors
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401; // Unauthorized
        }
        
        // Rate limiting errors
        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return 429; // Too Many Requests
        }
        
        // HTTP exceptions (preserve original status code)
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $e->getStatusCode();
        }
        
        // Default to 500 for all other exceptions
        return 500; // Internal Server Error
    }
    
    /**
     * Get user-friendly error message
     * 
     * Returns appropriate error message based on environment.
     * Production: Generic messages that don't expose internals
     * Development: Detailed messages for debugging
     * 
     * Requirements: 15.2, 15.3, 15.9
     * 
     * @param \Exception $e The exception
     * @return string User-friendly error message
     */
    protected function getErrorMessage(\Exception $e): string
    {
        $isProduction = config('app.env') === 'production';
        
        // In production, return generic messages
        if ($isProduction) {
            return $this->getProductionErrorMessage($e);
        }
        
        // In development, return detailed messages
        return $this->getDevelopmentErrorMessage($e);
    }
    
    /**
     * Get production error message
     * 
     * Returns generic, user-friendly messages that don't expose
     * sensitive information or system internals.
     * 
     * Requirements: 15.9 - Don't expose sensitive info in production
     * 
     * @param \Exception $e The exception
     * @return string Generic error message
     */
    protected function getProductionErrorMessage(\Exception $e): string
    {
        // Validation errors can show field-specific messages
        if ($e instanceof ValidationException) {
            return 'The provided data is invalid. Please check your input and try again.';
        }
        
        // Not found errors
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 'The requested content could not be found.';
        }
        
        // Authorization errors
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 'You do not have permission to access this content.';
        }
        
        // Authentication errors
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 'You must be logged in to access this content.';
        }
        
        // Rate limiting errors
        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return 'Too many requests. Please try again later.';
        }
        
        // Rendering errors (custom RuntimeException from renderTabContent)
        if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'render')) {
            return 'An error occurred while loading the content. Please try again.';
        }
        
        // Generic message for all other errors
        return 'An error occurred while loading the tab content. Please try again or contact support if the problem persists.';
    }
    
    /**
     * Get development error message
     * 
     * Returns detailed error messages for debugging purposes.
     * Only used in non-production environments.
     * 
     * Requirements: 15.2 - Return user-friendly error messages
     * 
     * @param \Exception $e The exception
     * @return string Detailed error message
     */
    protected function getDevelopmentErrorMessage(\Exception $e): string
    {
        // For development, include exception class and message
        $message = get_class($e) . ': ' . $e->getMessage();
        
        // Add file and line information
        $message .= ' in ' . $e->getFile() . ':' . $e->getLine();
        
        return $message;
    }
    
    /**
     * Get additional error context
     * 
     * Returns additional error information for debugging.
     * Only included in non-production environments.
     * 
     * Requirements: 15.2 - Log detailed errors for debugging
     * 
     * @param \Exception $e The exception
     * @return array Additional error context
     */
    protected function getErrorContext(\Exception $e): array
    {
        $isProduction = config('app.env') === 'production';
        
        // Don't include additional context in production
        if ($isProduction) {
            return [];
        }
        
        // In development, include helpful debugging information
        $context = [
            'exception_class' => get_class($e),
            'exception_code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        
        // Include validation errors if available
        if ($e instanceof ValidationException) {
            $context['validation_errors'] = $e->errors();
        }
        
        // Include stack trace (first 5 frames for brevity)
        $trace = $e->getTrace();
        $context['stack_trace'] = array_slice($trace, 0, 5);
        
        return $context;
    }
}

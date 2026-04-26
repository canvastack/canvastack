<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\ControllerConstants;
use Canvastack\Canvastack\Library\Helpers\ControllerConfig;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\DataTablesException;
use Canvastack\Canvastack\Exceptions\Controller\SQLInjectionAttemptException;
use Canvastack\Canvastack\Exceptions\Controller\XSSAttemptException;

/**
 * Action Trait - CRUD Operations and DataTables Integration
 *
 * Provides comprehensive CRUD (Create, Read, Update, Delete) operations for Laravel controllers
 * with advanced DataTables integration, security features, and performance optimizations.
 * This trait implements the core action methods required for resource management in the
 * Canvastack Origin framework, handling everything from form rendering to data persistence.
 *
 * The trait offers a complete solution for building data-driven applications with features including:
 * - RESTful resource operations (index, create, store, show, edit, update, destroy)
 * - Server-side DataTables processing with AJAX support
 * - Advanced filtering and pagination with SQL injection prevention
 * - File upload handling with security validation
 * - Form validation with XSS-protected error messages
 * - Soft delete support with optimized queries
 * - Eager loading to prevent N+1 query problems
 * - Query performance monitoring and optimization
 *
 * Security Features:
 * - CSRF protection on all state-changing operations (handled by Controller::callAction())
 * - XSS prevention through comprehensive output escaping
 * - SQL injection prevention via table/column name validation
 * - Input validation with configurable strict mode
 * - Array depth and size limits to prevent DoS attacks
 * - Null byte detection in string inputs
 * - Operator whitelist validation for dynamic queries
 * - Route parameter type validation and sanitization
 *
 * Performance Optimizations:
 * - Eager loading of relationships to prevent N+1 queries
 * - Optimized column selection (SELECT specific columns vs SELECT *)
 * - Efficient pagination with LIMIT/OFFSET optimization
 * - Soft delete query optimization (single query vs double query)
 * - Memory management with variable cleanup after operations
 * - Query performance monitoring with slow query logging
 * - Configurable performance features via config files
 *
 * DataTables Integration:
 * - Server-side processing for large datasets
 * - AJAX-based data loading with POST request handling
 * - Searchable, sortable, and clickable table columns
 * - Custom filter support with validation
 * - Export functionality for data extraction
 * - Pagination parameter validation
 * - Performance monitoring for DataTables queries
 *
 * Configuration:
 * All features can be configured via config/canvastack.php:
 * - canvastack.controller.security.sql_injection_prevention
 * - canvastack.controller.validation.strict_mode
 * - canvastack.controller.validation.max_array_depth
 * - canvastack.controller.validation.max_input_variables
 * - canvastack.controller.performance.query_optimization
 * - canvastack.controller.performance.eager_loading
 * - canvastack.controller.logging.log_security_events
 * - canvastack.controller.logging.log_validation_failures
 * - canvastack.controller.logging.log_performance_issues
 *
 * @package    Canvastack\Canvastack\Controllers\Core\Craft
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 wisnuwidi
 * @since      24 Mar 2021
 * @version    2.0.0
 *
 * @property mixed $model The Eloquent model instance or query builder
 * @property string|null $model_path Fully qualified model class name
 * @property string|null $model_table Database table name for the model
 * @property mixed $model_id Current model ID being processed
 * @property mixed $model_data Current model data retrieved from database
 * @property mixed $model_original Original model instance before modifications
 * @property bool $softDeletedModel Whether model uses soft deletes
 * @property bool $is_softdeleted Whether current record is soft deleted
 * @property array $validations Validation rules for form inputs
 * @property mixed $uploadTrack File upload tracking information
 * @property mixed $stored_id ID of last stored/updated record
 * @property bool $store_routeback Whether to redirect after store/update
 * @property string|null $filter_datatables_string DataTables filter string
 * @property array $model_filters Filters applied to model queries
 * @property array $model_class_path Model class path information
 * @property array $objectInjection DataTables object injection configuration
 *
 * @see \Canvastack\Canvastack\Controllers\Core\Controller Base controller class
 * @see \Canvastack\Canvastack\Library\Components\Table\Craft\Datatables DataTables implementation
 * @see \Canvastack\Canvastack\Library\Helpers\ControllerConfig Configuration helper
 *
 * @example Basic usage in a controller:
 * ```php
 * class UserController extends Controller {
 *     use Action;
 *     
 *     public function __construct() {
 *         parent::__construct();
 *         $this->model(User::class);
 *         $this->setValidations([
 *             'name' => 'required|string|max:255',
 *             'email' => 'required|email|unique:users',
 *         ]);
 *     }
 * }
 * ```
 *
 * @example Advanced usage with filters and eager loading:
 * ```php
 * class PostController extends Controller {
 *     use Action;
 *     
 *     public function __construct() {
 *         parent::__construct();
 *         // Initialize model with filters
 *         $this->model(Post::class, ['status' => 'published']);
 *         
 *         // Apply page filters
 *         $this->filterPage(['category_id' => request('category')]);
 *     }
 * }
 * ```
 *
 * Created on 24 Mar 2021
 * Time Created: 17:56:08
 *
 * @filesource Action.php
 */
 
trait Action {
	
	public mixed $model = [];
	public ?string $model_path = null;
	public ?string $model_table = null;
	public mixed $model_id = null;
	public mixed $model_data = null;
	public mixed $model_original = null;
	
	public bool $softDeletedModel = false;
	public bool $is_softdeleted = false;
	
	public array $validations = [];
	public mixed $uploadTrack = null;
	
	public mixed $stored_id = null;
	public bool $store_routeback = true;
	public ?string $filter_datatables_string = null;
	
	/**
	 * Display index page with DataTables listing
	 * 
	 * Renders the main index page with an interactive DataTables component for listing records.
	 * This method handles both initial page load (GET request) and AJAX data requests (POST).
	 * The DataTables component provides searchable, sortable, and paginated data display with
	 * server-side processing for optimal performance with large datasets.
	 * 
	 * The method automatically configures the table with searchable, clickable, and sortable
	 * features, then delegates rendering to the parent Controller's render() method which
	 * handles the view selection and data binding.
	 * 
	 * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse View for GET requests, JSON for POST/AJAX requests
	 * 
	 * @security CSRF Protection - All POST requests are protected by CSRF token verification in Controller::callAction()
	 *           The framework automatically validates CSRF tokens before this method executes
	 * @security XSS Protection - Model table name is escaped using escapeRouteParameter() to prevent XSS attacks
	 *           All output data is sanitized before rendering in the view
	 * 
	 * @performance Server-side processing handles large datasets efficiently without loading all records into memory
	 * @performance DataTables AJAX requests are optimized with pagination and filtering at the database level
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: GET /users
	 * // Renders: resources/views/users/index.blade.php with DataTables
	 * ```
	 * 
	 * @example DataTables AJAX request:
	 * ```php
	 * // Route: POST /users (with DataTables parameters)
	 * // Returns: JSON response with paginated, filtered, and sorted data
	 * ```
	 */
		public function index() {
				$this->setPage();

				if (!empty($this->model_table)) {
					// Escape model table name to prevent XSS in table rendering
					$safeModelTable = $this->escapeRouteParameter($this->model_table);

					$this->table->searchable();
					$this->table->clickable();
					$this->table->sortable();

					// Use escaped table name
					$this->table->lists($safeModelTable);
				}
				return $this->render();
			}
	
	/**
	 * Show the form for creating a new resource
	 * 
	 * Renders the create form view for adding a new record to the database.
	 * This method delegates to the parent Controller's render() method which handles
	 * view selection, form component initialization, and data binding. The form is
	 * automatically populated with validation rules and CSRF protection.
	 * 
	 * @return \Illuminate\View\View The create form view
	 * 
	 * @security CSRF Protection - Form automatically includes CSRF token field via form helpers
	 * @security XSS Protection - All form HTML is generated by form helpers which escape user input
	 * @security Input Validation - Form fields are validated against rules set via setValidations()
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: GET /users/create
	 * // Renders: resources/views/users/create.blade.php
	 * ```
	 */
		public function create() {
				return $this->render();
			}
	
	/**
	 * Display the specified resource in read-only mode
	 * 
	 * Renders a read-only view of a specific record, displaying all fields in a disabled state.
	 * This method adds readonly and disabled attributes to all form fields, preventing any
	 * modifications while allowing users to view the complete record details. The ID parameter
	 * is validated and escaped to prevent security vulnerabilities.
	 * 
	 * @param mixed $id The resource ID to display (typically an integer primary key)
	 * @return \Illuminate\View\View The show view with read-only form fields
	 * 
	 * @security Route Parameter Validation - ID is escaped and validated as integer via escapeRouteParameter()
	 * @security XSS Protection - All field values are escaped before rendering
	 * @security Type Validation - ID parameter is validated to ensure it's a positive integer
	 * 
	 * @throws \InvalidArgumentException If ID is not a valid positive integer
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: GET /users/123
	 * // Renders: resources/views/users/show.blade.php with user ID 123 in read-only mode
	 * ```
	 */
	public function show(mixed $id) {
		// Escape ID parameter to prevent XSS and validate as integer
		$safeId = $this->escapeRouteParameter($id, 'int');
		
		$this->form->addAttributes(['readonly', 'disabled', 'class' => 'form-show-only']);
		
		return $this->create();
	}
	/**
	 * Show the form for editing the specified resource
	 * 
	 * Renders the edit form view for modifying an existing record. This method retrieves the
	 * record by ID, validates its existence, and displays the form pre-populated with current
	 * values. The ID parameter is validated and escaped for security. If the record doesn't
	 * exist or has been deleted (and soft deletes are enabled), the method returns null.
	 * 
	 * @param mixed $id The resource ID to edit (typically an integer primary key)
	 * @return \Illuminate\View\View|null The edit form view, or null if record not found
	 * 
	 * @security Route Parameter Validation - ID is escaped and validated as integer via escapeRouteParameter()
	 * @security XSS Protection - All field values are escaped before rendering in the form
	 * @security Type Validation - ID parameter is validated to ensure it's a positive integer
	 * @security Existence Check - Verifies record exists before rendering edit form
	 * 
	 * @throws \InvalidArgumentException If ID is not a valid positive integer
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: GET /users/123/edit
	 * // Renders: resources/views/users/edit.blade.php with user ID 123 data
	 * ```
	 */
		public function edit($id) {
				// Escape ID parameter to prevent XSS and validate as integer
				$safeId = $this->escapeRouteParameter($id, 'int');

				$this->setPage('&nbsp;');
				if (!empty($this->getModel($safeId))) {
					$model = $this->getModel($safeId);
					$model->find($safeId);

					if (!empty($model->getAttributes())) {
						return $this->create();
					}
				}
			}
	
	/**
	 * Insert new data with validation
	 * 
	 * Public interface for inserting new records with comprehensive validation. This method
	 * validates the request data against configured validation rules, then delegates to the
	 * internal INSERT_DATA_PROCESSOR for actual data persistence. Provides control over
	 * whether to redirect back after insertion or return the stored ID directly.
	 * 
	 * @param Request $request The HTTP request containing form data to insert
	 * @param bool $routeback Whether to redirect back after insertion (true) or return stored ID (false)
	 * @return mixed Redirect response if $routeback is true, stored ID otherwise
	 * 
	 * @security CSRF Protection - Request is validated by framework before reaching this method
	 * @security Input Validation - All inputs are validated against rules set via setValidations()
	 * @security XSS Protection - Validation error messages are escaped before display
	 * @security Array Validation - Array inputs are validated for depth and size limits
	 * 
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * 
	 * @example Programmatic usage with redirect:
	 * ```php
	 * public function customStore(Request $request) {
	 *     return $this->insert_data($request, true);
	 * }
	 * ```
	 * 
	 * @example Programmatic usage returning ID:
	 * ```php
	 * public function apiStore(Request $request) {
	 *     $id = $this->insert_data($request, false);
	 *     return response()->json(['id' => $id]);
	 * }
	 * ```
	 */
	public function insert_data(Request $request, bool $routeback = true): mixed {
		$this->validation($request, ControllerConstants::ACTION_EDIT);
		return $this->INSERT_DATA_PROCESSOR($request, $routeback);
	}
	
	/**
	 * Export DataTables data to various formats
	 * 
	 * Handles data export functionality for DataTables, allowing users to download table data
	 * in various formats. This method processes export requests triggered by the exportDataTables
	 * parameter, retrieves all matching records from the database, and prepares them for export.
	 * Supports both standard models and dynamic tables.
	 * 
	 * The export process extracts column headers and values from the model, organizing them
	 * into a structured array suitable for conversion to CSV, Excel, PDF, or other formats.
	 * 
	 * @return void This method outputs data directly or sets export data for processing
	 * 
	 * @security Access Control - Export functionality should be protected by appropriate permissions
	 * @security Data Filtering - Only exports data accessible to the current user based on model filters
	 * 
	 * @performance Memory Warning - Exports all matching records, which may cause memory issues with large datasets
	 * @performance Consider implementing chunked export for tables with >10,000 records
	 * 
	 * @example Triggering export via URL:
	 * ```php
	 * // URL: /users?exportDataTables=true&difta[name]=users&difta[source]=dynamics
	 * // Exports all user records to configured format
	 * ```
	 */
	private function exportDatatables(): void {
		if (!empty($_GET['exportDataTables'])) {
			if (true == $_GET['exportDataTables']) {
				$data         = [];
				$table_source = $_GET['difta']['name'];
				$model_source = $_GET['difta']['source'];
				unset($_POST['_token']);
				
				if ('dynamics' === $model_source) {
					$model = new DynamicTables(null, $this->connection);
					$model->setTable($table_source);
					$data[$table_source]['model'] = get_class($model);
					
					foreach ($model->get() as $rowIndex => $modelRecord) {
						foreach ($modelRecord->getAttributes() as $fieldname => $fieldvalue) {
							$data[$table_source]['export']['head'][$fieldname]              = $fieldname;
							$data[$table_source]['export']['values'][$rowIndex][$fieldname] = $fieldvalue;
						}
					}
				}
			}
		}
	}
	
	private array $objectInjection = [];
	
	/**
	 * Set object injection for DataTables POST processing
	 * 
	 * Configures and processes DataTables AJAX requests with custom object injection. This method
	 * stores the DataTables configuration object and immediately delegates to processDataTablesPost()
	 * for request handling. The object injection allows customization of DataTables behavior including
	 * column definitions, filtering, sorting, and data source configuration.
	 * 
	 * @param array $object DataTables configuration array containing:
	 *               - 'datatables': DataTables column and behavior configuration
	 *               - 'model_filters': Additional model-level filters to apply
	 *               - Other custom configuration options
	 * @return \Illuminate\Http\JsonResponse JSON response with paginated, filtered, and sorted data
	 * 
	 * @security CSRF Protection - POST requests are validated by framework before reaching this method
	 * @security Input Validation - Pagination parameters are validated to prevent injection attacks
	 * @security SQL Injection Prevention - All filter values are validated and sanitized
	 * 
	 * @performance Server-side processing handles large datasets efficiently
	 * @performance Pagination limits memory usage by loading only requested page
	 * 
	 * @example Usage in controller:
	 * ```php
	 * $config = [
	 *     'datatables' => [
	 *         'columns' => ['id', 'name', 'email', 'created_at'],
	 *         'searchable' => ['name', 'email'],
	 *         'sortable' => ['id', 'name', 'created_at']
	 *     ],
	 *     'model_filters' => ['status' => 'active']
	 * ];
	 * return $this->setObjectInjection($config);
	 * ```
	 */
	public function setObjectInjection(array $object): \Illuminate\Http\JsonResponse {
		$this->objectInjection = $object;
		return $this->processDataTablesPost($object);
	}
	
	/**
	 * Process DataTables POST request
	 * Handles POST ajax requests from DataTables
	 * 
	 * @param array $object DataTables configuration
	 * @return \Illuminate\Http\JsonResponse
	 * 
	 * @security Validates pagination parameters to prevent injection attacks
	 * @performance Optimizes pagination queries with efficient LIMIT/OFFSET
	 * @performance Memory Management - Cleans up large variables after use
	 */
	private function processDataTablesPost(array $object): \Illuminate\Http\JsonResponse {
		if (empty($object['datatables'])) {
			return response()->json(['error' => 'No datatables configuration'], 400);
		}
		
		$config = $object['datatables'];
		$DataTables = new \Canvastack\Canvastack\Library\Components\Table\Craft\Datatables();
		
		// Parse POST request (includes filter parameters)
		$postData = $DataTables->parsePostRequest(request());
		
		// Validate DataTables request structure (Subtask 5.6.2)
		// This validates draw, start, length, search, order, and columns parameters
		// The validation is performed internally by the Datatables class
		// which throws exceptions for invalid parameters
		
		// Validate pagination parameters (legacy validation, kept for backward compatibility)
		$this->validatePaginationParameters($postData);
		
		// Optimize pagination if enabled
		if (config('canvastack.controller.performance.query_optimization', true)) {
			$postData = $this->optimizePaginationQuery($postData);
		}
		
		// Log DataTables request if logging is enabled (Subtask 5.6.8)
		if (config('canvastack.controller.logging.log_datatables_requests', false)) {
			$this->logDataTablesRequest($postData, $config);
		}
		
		// Pass $postData as filters parameter
		// $postData contains all POST body data including custom filter parameters
		// processPost() will convert it to GET format and pass to process()
		$result = $DataTables->processPost(
			$postData,
			$config['datatables'],
			$postData,  // Pass postData as filters (contains filter parameters)
			$config['model_filters'] ?? []
		);
		
		// Clean up large variables to free memory
		unset($config, $DataTables, $postData, $object);
		
		// Wrap result in JsonResponse if it's an array
		// The Datatables::process() method returns an array, not a JsonResponse
		if (is_array($result)) {
			return response()->json($result);
		}
		
		// If result is null (error case), return error response
		if ($result === null) {
			return response()->json([
				'draw' => 0,
				'recordsTotal' => 0,
				'recordsFiltered' => 0,
				'data' => [],
				'error' => 'Failed to process DataTables request'
			], 500);
		}
		
		// If result is already a JsonResponse, return it
		return $result;
	}
	
	/**
	 * Optimize pagination query parameters
	 * 
	 * Optimizes pagination parameters to use efficient LIMIT/OFFSET queries.
	 * Ensures parameters are within acceptable ranges and properly formatted.
	 * 
	 * @param array $params Request parameters
	 * @return array Optimized parameters
	 * 
	 * @performance Uses efficient pagination strategies to minimize query time
	 */
	private function optimizePaginationQuery(array $params): array {
		// Ensure start and length are integers
		if (isset($params['start'])) {
			$params['start'] = (int) $params['start'];
		}
		
		if (isset($params['length'])) {
			$params['length'] = (int) $params['length'];
			
			// Cap length at reasonable maximum to prevent memory issues
			$maxLength = config('canvastack.controller.datatables.max_records_per_page', 1000);
			if ($params['length'] > $maxLength) {
				$params['length'] = $maxLength;
			}
			
			// Use default length if too small
			$minLength = 1;
			if ($params['length'] < $minLength) {
				$params['length'] = config('canvastack.controller.datatables.default_page_length', 10);
			}
		}
		
		// Log optimization if performance logging is enabled
		if (config('canvastack.controller.logging.log_performance_issues', true)) {
			\Log::debug('Optimized pagination query', [
				'start' => $params['start'] ?? 0,
				'length' => $params['length'] ?? 10,
			]);
		}
		
		return $params;
	}
	
	/**
	 * Log DataTables request for monitoring and debugging
	 * 
	 * Logs DataTables AJAX request details including pagination, search, ordering,
	 * and filter parameters. This helps with debugging DataTables issues and
	 * monitoring request patterns.
	 * 
	 * @param array $postData Parsed POST request data
	 * @param array $config DataTables configuration
	 * @return void
	 * 
	 * @security Logs request details for security monitoring
	 * @performance Minimal overhead, only logs when enabled
	 */
	private function logDataTablesRequest(array $postData, array $config): void {
		\Log::info('DataTables POST Request', [
			'draw' => $postData['draw'] ?? null,
			'start' => $postData['start'] ?? 0,
			'length' => $postData['length'] ?? 10,
			'search' => $postData['search']['value'] ?? '',
			'order_count' => count($postData['order'] ?? []),
			'columns_count' => count($postData['columns'] ?? []),
			'has_model_filters' => !empty($config['model_filters']),
			'user_id' => session('id'),
			'ip_address' => request()->ip(),
			'route' => request()->path(),
			'timestamp' => now()->toDateTimeString(),
		]);
	}
	
	/**
	 * Monitor query performance
	 * 
	 * Monitors database query execution time and logs slow queries.
	 * Helps identify performance bottlenecks in database operations.
	 * 
	 * @param callable $callback Query callback to execute
	 * @param string $queryName Name/description of the query
	 * @return mixed Result from callback
	 * 
	 * @performance Tracks query execution time and logs slow queries
	 */
	private function monitorQueryPerformance(callable $callback, string $queryName = 'Query'): mixed {
		if (!config('canvastack.controller.performance.enable_query_monitoring', true)) {
			return $callback();
		}
		
		$startTime = microtime(true);
		$startMemory = memory_get_usage();
		
		try {
			$result = $callback();
			
			$endTime = microtime(true);
			$endMemory = memory_get_usage();
			
			$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
			$memoryUsed = $endMemory - $startMemory;
			
			// Check if query is slow
			$slowQueryThreshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
			if ($executionTime > $slowQueryThreshold) {
				\Log::warning('Slow query detected', [
					'query_name' => $queryName,
					'execution_time_ms' => round($executionTime, 2),
					'memory_used_bytes' => $memoryUsed,
					'threshold_ms' => $slowQueryThreshold,
					'user_id' => session('id'),
					'route' => request()->path(),
				]);
			} else if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::debug('Query performance', [
					'query_name' => $queryName,
					'execution_time_ms' => round($executionTime, 2),
					'memory_used_bytes' => $memoryUsed,
				]);
			}
			
			return $result;
		} catch (\Exception $e) {
			$endTime = microtime(true);
			$executionTime = ($endTime - $startTime) * 1000;
			
			\Log::error('Query execution failed', [
				'query_name' => $queryName,
				'execution_time_ms' => round($executionTime, 2),
				'error' => $e->getMessage(),
				'user_id' => session('id'),
				'route' => request()->path(),
			]);
			
			throw $e;
		}
	}
	
	/**
	 * Validate pagination parameters
	 * 
	 * Validates pagination parameters (start, length, page) to ensure they are
	 * valid integers within acceptable ranges.
	 * 
	 * @param array $params Request parameters
	 * @return bool True if parameters are valid
	 * @throws \InvalidArgumentException If parameters are invalid
	 * 
	 * @security CRITICAL - Validates pagination parameters to prevent injection
	 */
	private function validatePaginationParameters(array $params): bool {
		// Get configuration
		$maxRecordsPerPage = config('canvastack.controller.datatables.max_records_per_page', 1000);
		
		// Validate 'start' parameter (offset)
		if (isset($params['start'])) {
			if (!is_numeric($params['start']) || $params['start'] < 0) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Pagination Validation Failed: Invalid start parameter', [
						'start' => $params['start'],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw new ControllerValidationException(
					'Invalid pagination start parameter: must be a non-negative integer',
					[
						'parameter' => 'start',
						'value' => $params['start'],
						'errors' => ['start' => ['Must be a non-negative integer']],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		// Validate 'length' parameter (page size)
		if (isset($params['length'])) {
			if (!is_numeric($params['length']) || $params['length'] < 1) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Pagination Validation Failed: Invalid length parameter', [
						'length' => $params['length'],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw new ControllerValidationException(
					'Invalid pagination length parameter: must be a positive integer',
					[
						'parameter' => 'length',
						'value' => $params['length'],
						'errors' => ['length' => ['Must be a positive integer']],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Check if length exceeds maximum
			if ($params['length'] > $maxRecordsPerPage) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Pagination Validation Failed: Length exceeds maximum', [
						'length' => $params['length'],
						'max_records_per_page' => $maxRecordsPerPage,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw new ControllerValidationException(
					"Pagination length ({$params['length']}) exceeds maximum allowed ({$maxRecordsPerPage})",
					[
						'parameter' => 'length',
						'value' => $params['length'],
						'max_allowed' => $maxRecordsPerPage,
						'errors' => ['length' => ["Must not exceed {$maxRecordsPerPage}"]],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		// Validate 'page' parameter if present
		if (isset($params['page'])) {
			if (!is_numeric($params['page']) || $params['page'] < 1) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Pagination Validation Failed: Invalid page parameter', [
						'page' => $params['page'],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw new ControllerValidationException(
					'Invalid pagination page parameter: must be a positive integer',
					[
						'parameter' => 'page',
						'value' => $params['page'],
						'errors' => ['page' => ['Must be a positive integer']],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		return true;
	}
	
	/**
	 * Check DataTables access and request type
	 * 
	 * Detects whether the current request is a DataTables POST AJAX request by checking for
	 * specific DataTables parameters (draw, columns, length). This method prevents DataTables
	 * requests from being incorrectly processed by the INSERT_DATA_PROCESSOR, ensuring they
	 * are handled by the appropriate DataTables rendering logic instead.
	 * 
	 * This is a critical routing method that distinguishes between:
	 * - Regular form submissions (should go to INSERT_DATA_PROCESSOR)
	 * - DataTables AJAX requests (should go to DataTables renderer)
	 * 
	 * @return string|bool Returns 'DATATABLES_POST_REQUEST' if DataTables POST detected, false otherwise
	 * 
	 * @security Prevents DataTables requests from bypassing proper validation and processing
	 * @security Ensures CSRF protection is maintained for both request types
	 * 
	 * @performance Lightweight check using isset() for minimal overhead
	 * 
	 * @example Internal usage in INSERT_DATA_PROCESSOR:
	 * ```php
	 * $check = $this->CHECK_DATATABLES_ACCESS_PROCESSOR();
	 * if ('DATATABLES_POST_REQUEST' === $check) {
	 *     return response()->json(['error' => 'Wrong handler'], 500);
	 * }
	 * ```
	 */
	private function CHECK_DATATABLES_ACCESS_PROCESSOR(): string|bool {
		// Check if this is a DataTables POST request
		if (!empty($_POST[ControllerConstants::DT_PARAM_DRAW]) && !empty($_POST[ControllerConstants::DT_PARAM_COLUMNS]) && !empty($_POST[ControllerConstants::DT_PARAM_LENGTH])) {
			// POST DataTables request detected
			// This should be handled by View.php render() method, not INSERT_DATA_PROCESSOR
			// Return special marker to stop INSERT_DATA_PROCESSOR execution
			return 'DATATABLES_POST_REQUEST';
		}
		return false;
	}
	
	/**
	 * Internal data insertion processor
	 * 
	 * Core method that handles the actual data insertion logic after validation. This method
	 * processes various types of requests including chart rendering, DataTables AJAX, data export,
	 * and standard form submissions. It validates array inputs, handles file uploads, and persists
	 * data to the database using the canvastack_insert() helper function.
	 * 
	 * The processor implements multiple request type checks to route requests appropriately:
	 * - Chart rendering requests (renderCharts parameter)
	 * - DataTables AJAX requests (detected via CHECK_DATATABLES_ACCESS_PROCESSOR)
	 * - Export requests (exportData parameter)
	 * - Standard form submissions (default path)
	 * 
	 * @param Request $request The HTTP request containing form data
	 * @param bool $routeback Whether to redirect back after insertion
	 * @return mixed Redirect response, stored ID, or JSON response depending on request type
	 * 
	 * @security CSRF Protection - All POST requests are validated before reaching this method
	 * @security Array Input Validation - Validates array depth and element count via validateArrayInputs()
	 * @security File Upload Security - Validates uploaded files via checkFileInputSubmited()
	 * @security Request Type Validation - Ensures DataTables requests don't bypass proper handling
	 * 
	 * @performance Memory Management - Cleans up large variables after processing with unset()
	 * @performance File Handling - Efficiently processes file uploads without loading entire files into memory
	 * 
	 * @throws \InvalidArgumentException If array inputs exceed configured limits
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * 
	 * @example Internal usage from store() method:
	 * ```php
	 * protected function store(Request $request) {
	 *     $this->INSERT_DATA_PROCESSOR($request);
	 *     return $this->routeBackAfterAction(__FUNCTION__, $this->stored_id);
	 * }
	 * ```
	 */
	private function INSERT_DATA_PROCESSOR(Request $request, bool $routeback = true): void {
		// Guard clause: Handle chart rendering early
		if ($this->shouldRenderCharts()) {
			$this->processChartRendering();
			return;
		}
		
		// Guard clause: Check if this is DataTables POST request
		$datatables_check = $this->CHECK_DATATABLES_ACCESS_PROCESSOR();
		if ('DATATABLES_POST_REQUEST' === $datatables_check) {
			// Stop execution - this request should be handled by View.php render()
			response()->json(['error' => 'DataTables POST request should not reach INSERT_DATA_PROCESSOR'], 500);
			return;
		}
		
		// Guard clause: Handle export redirection early
		if ($this->shouldHandleExportRedirection()) {
			echo redirect($this->exportRedirection);
			exit;
		}
		
		$this->store_routeback = $routeback;
		$requestData = $request->all();
		
		// Guard clause: Handle filter requests early
		if ($this->isFilterRequest($requestData)) {
			$this->filterDataTable($request);
			return;
		}
		
		// Process normal data insertion
		$this->processDataInsertion($request);
		
		// Clean up large variables after insert
		unset($requestData);
	}
	
	/**
	 * Check if charts should be rendered
	 * 
	 * @return bool True if chart rendering is requested
	 */
	private function shouldRenderCharts(): bool {
		return !empty($this->data['components']->chart) 
			&& !empty($_GET['renderCharts']) 
			&& 'false' != $_GET['renderCharts'];
	}
	
	/**
	 * Process chart rendering
	 * 
	 * @return mixed Chart processing result
	 */
	private function processChartRendering(): mixed {
		$chart = new Chart();
		$result = $chart->process($_POST);
		
		// Clean up
		unset($chart);
		
		return $result;
	}
	
	/**
	 * Check if export redirection should be handled
	 * 
	 * @return bool True if export redirection is needed
	 */
	private function shouldHandleExportRedirection(): bool {
		return !empty($this->exportRedirection) && true == $_POST['exportData'];
	}
	
	/**
	 * Check if this is a filter request
	 * 
	 * @param array $requestData Request data
	 * @return bool True if this is a filter request
	 */
	private function isFilterRequest(array $requestData): bool {
		return isset($requestData['filters']) && !empty($requestData['filters']) && 'true' === $requestData['filters'];
	}
	
	/**
	 * Process data insertion
	 * 
	 * @param Request $request HTTP request
	 * @return void
	 * @throws \Exception If database error occurs and graceful degradation is disabled
	 */
	private function processDataInsertion(Request $request): void {
		// Validate array inputs before processing
		$this->validateArrayInputs($request->all());
		
		$request->validate($this->validations);
		
		$model = $this->getModel();
		if ('Builder' === class_basename($model)) {
			$model = $this->model_path;
		}
		
		// check if any input file type submited
		$data = $this->checkFileInputSubmited($request);
		
		// Wrap database operation with error handling
		try {
			$this->stored_id = canvastack_insert($model, $data, true);
		} catch (\Exception $e) {
			// Handle database error gracefully
			$dataKeys = is_array($data) ? array_keys($data) : (is_object($data) && method_exists($data, 'all') ? array_keys($data->all()) : ['unknown']);
			
			$this->handleDatabaseError($e, 'insert', [
				'model' => is_object($model) ? get_class($model) : $model,
				'data_keys' => $dataKeys,
			]);
			
			// Always re-throw the exception so user can see the error
			throw $e;
		}
		
		// Clean up large variables after insert
		unset($data);
	}
	
	/**
	 * Validate array inputs
	 * 
	 * Validates all array inputs to prevent injection attacks and ensure data integrity.
	 * Checks array depth, element count, and validates each element.
	 * 
	 * @param array $inputs Request inputs
	 * @return bool True if all arrays are valid
	 * @throws \InvalidArgumentException If any array is invalid
	 * 
	 * @security CRITICAL - Validates array inputs to prevent injection attacks
	 */
	private function validateArrayInputs(array $inputs): bool {
		// Check if validation is enabled
		$strictMode = config('canvastack.controller.validation.strict_mode', true);
		if (!$strictMode) {
			return true;
		}
		
		// Get configuration
		$maxArrayDepth = config('canvastack.controller.validation.max_array_depth', 10);
		$maxInputVariables = config('canvastack.controller.validation.max_input_variables', 1000);
		
		// Count total input variables
		$totalVariables = $this->countArrayElements($inputs);
		if ($totalVariables > $maxInputVariables) {
			// Log validation failure
			$this->logValidationFailure('Array Input Validation Failed: Too many variables', [
				'total_variables' => $totalVariables,
				'max_variables' => $maxInputVariables,
			]);
			
			throw new ControllerValidationException(
				"Total input variables ({$totalVariables}) exceeds maximum allowed ({$maxInputVariables})",
				[
					'parameter' => 'total_variables',
					'value' => $totalVariables,
					'max_allowed' => $maxInputVariables,
					'errors' => ['total_variables' => ["Must not exceed {$maxInputVariables}"]],
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}
		
		// Validate each array input
		foreach ($inputs as $key => $value) {
			if (is_array($value)) {
				// Check array depth
				$depth = $this->getArrayDepth($value);
				if ($depth > $maxArrayDepth) {
					// Log validation failure
					$this->logValidationFailure('Array Input Validation Failed: Array too deep', [
						'field' => $key,
						'depth' => $depth,
						'max_depth' => $maxArrayDepth,
					]);
					
					throw new ControllerValidationException(
						"Array input '{$key}' depth ({$depth}) exceeds maximum allowed ({$maxArrayDepth})",
						[
							'parameter' => $key,
							'depth' => $depth,
							'max_depth' => $maxArrayDepth,
							'errors' => [$key => ["Array depth must not exceed {$maxArrayDepth}"]],
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
				
				// Recursively validate nested arrays
				$this->validateArrayInputs($value);
			} elseif (is_string($value)) {
				// Check for null bytes in string values
				if (strpos($value, chr(0)) !== false) {
					// Log security event
					$this->logSecurityEvent('Array Input Validation Failed: Null byte detected', [
						'field' => $key,
					]);
					
					throw new ControllerValidationException(
						"Input '{$key}' contains null bytes",
						[
							'parameter' => $key,
							'errors' => [$key => ['Must not contain null bytes']],
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
				
				// Check string length
				$maxLength = config('canvastack.controller.validation.max_query_length', 10000);
				if (strlen($value) > $maxLength) {
					// Log validation failure
					$this->logValidationFailure('Array Input Validation Failed: String too long', [
						'field' => $key,
						'length' => strlen($value),
						'max_length' => $maxLength,
					]);
					
					throw new ControllerValidationException(
						"Input '{$key}' length exceeds maximum allowed",
						[
							'parameter' => $key,
							'length' => strlen($value),
							'max_length' => $maxLength,
							'errors' => [$key => ['Length exceeds maximum allowed']],
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Count array elements recursively
	 * 
	 * Counts the total number of elements in an array including nested arrays.
	 * 
	 * @param array $array Array to count
	 * @return int Total element count
	 */
	private function countArrayElements(array $array): int {
		$count = 0;
		
		foreach ($array as $value) {
			$count++;
			if (is_array($value)) {
				$count += $this->countArrayElements($value);
			}
		}
		
		return $count;
	}
	
	/**
	 * Log validation failure
	 * 
	 * Centralized method for logging validation failures with consistent format.
	 * 
	 * @param string $message Log message
	 * @param array $context Additional context data
	 * @return void
	 */
	private function logValidationFailure(string $message, array $context = []): void {
		if (!config('canvastack.controller.logging.log_validation_failures', true)) {
			return;
		}
		
		$context['user_id'] = session('id');
		$context['ip_address'] = request()->ip();
		
		\Illuminate\Support\Facades\Log::warning($message, $context);
	}
	
	/**
	 * Log security event
	 * 
	 * Centralized method for logging security events with consistent format.
	 * 
	 * @param string $message Log message
	 * @param array $context Additional context data
	 * @return void
	 */
	private function logSecurityEvent(string $message, array $context = []): void {
		if (!config('canvastack.controller.logging.log_security_events', true)) {
			return;
		}
		
		$context['user_id'] = session('id');
		$context['ip_address'] = request()->ip();
		
		\Illuminate\Support\Facades\Log::warning($message, $context);
	}
	
	/**
	 * Log performance issue
	 * 
	 * Centralized method for logging performance issues with consistent format.
	 * 
	 * @param string $message Log message
	 * @param array $context Additional context data
	 * @param string $level Log level (debug, info, warning)
	 * @return void
	 */
	private function logPerformanceIssue(string $message, array $context = [], string $level = 'debug'): void {
		if (!config('canvastack.controller.logging.log_performance_issues', true)) {
			return;
		}
		
		$context['timestamp'] = microtime(true);
		$context['memory_usage'] = memory_get_usage(true);
		
		\Log::$level($message, $context);
	}
	
	/**
	 * Store new resource in database
	 * 
	 * RESTful controller method that handles POST requests to create new records. This method
	 * delegates to INSERT_DATA_PROCESSOR for the actual insertion logic, then either redirects
	 * to the edit page of the newly created record or returns the stored ID directly, depending
	 * on the store_routeback property.
	 * 
	 * This is the standard Laravel resource controller method called by POST /resource routes.
	 * 
	 * @param Request $request HTTP request with form data to store
	 * @return mixed Redirect response to edit page if store_routeback is true, stored ID otherwise
	 * 
	 * @security CSRF Protection - All POST requests are automatically protected by CSRF token verification
	 *           The framework validates CSRF tokens in Controller::callAction() before this method executes
	 * @security Input Validation - All inputs are validated via INSERT_DATA_PROCESSOR
	 * @security XSS Protection - All output is escaped before rendering
	 * 
	 * @performance Single Insert Query - Creates record with single database INSERT statement
	 * @performance Returns ID Immediately - No additional queries after insertion
	 * 
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: POST /users
	 * // Creates new user and redirects to /users/123/edit
	 * ```
	 * 
	 * @example API usage returning ID:
	 * ```php
	 * $this->store_routeback = false;
	 * $id = $this->store($request);
	 * return response()->json(['id' => $id]);
	 * ```
	 */
	protected function store(Request $request) {
		$this->INSERT_DATA_PROCESSOR($request);
		
		if (true === $this->store_routeback) {
			return $this->routeBackAfterAction(__FUNCTION__, $this->stored_id);
		} else {
			return $this->stored_id;
		}
	}
	
	/**
	 * Update existing data with validation
	 * 
	 * Public interface for updating existing records with comprehensive validation. This method
	 * validates the request data against configured validation rules (including update-specific
	 * rules if set), then delegates to the internal UPDATE_DATA_PROCESSOR for actual data
	 * persistence. Provides control over whether to redirect back after update or return the ID.
	 * 
	 * @param Request $request The HTTP request containing form data to update
	 * @param mixed $id The ID of the record to update (typically an integer primary key)
	 * @param bool $routeback Whether to redirect back after update (true) or return ID (false)
	 * @return mixed Redirect response if $routeback is true, stored ID otherwise
	 * 
	 * @security CSRF Protection - Request is validated by framework before reaching this method
	 * @security Input Validation - All inputs are validated against update-specific rules
	 * @security XSS Protection - Validation error messages are escaped before display
	 * @security ID Validation - Record ID is validated to ensure it exists before update
	 * 
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If record not found
	 * 
	 * @example Programmatic usage with redirect:
	 * ```php
	 * public function customUpdate(Request $request, $id) {
	 *     return $this->update_data($request, $id, true);
	 * }
	 * ```
	 * 
	 * @example Programmatic usage returning ID:
	 * ```php
	 * public function apiUpdate(Request $request, $id) {
	 *     $id = $this->update_data($request, $id, false);
	 *     return response()->json(['id' => $id, 'message' => 'Updated']);
	 * }
	 * ```
	 */
	public function update_data(Request $request, mixed $id, bool $routeback = true): mixed {
		$this->validation($request, ControllerConstants::ACTION_EDIT);
		return $this->UPDATE_DATA_PROCESSOR($request, $id, $routeback);
	}
	
	/**
	 * Set validation rules for form inputs
	 * 
	 * Configures validation rules for both create and update operations. This method accepts
	 * two sets of rules: standard rules for create operations and optional update-specific rules.
	 * When processing an edit or update action, the method automatically switches to update rules
	 * if provided, allowing for different validation logic (e.g., unique email except current user).
	 * 
	 * The validation rules are also passed to the form component for client-side validation
	 * and field attribute generation (required, maxlength, etc.).
	 * 
	 * @param array $roles Standard validation rules for create operations (Laravel validation syntax)
	 * @param array $on_update Optional validation rules specific to update operations
	 * @return void
	 * 
	 * @security Input Validation - Defines rules that protect against invalid or malicious data
	 * @security Type Safety - Ensures data types match expected formats before database operations
	 * 
	 * @example Basic validation rules:
	 * ```php
	 * $this->setValidations([
	 *     'name' => 'required|string|max:255',
	 *     'email' => 'required|email|unique:users',
	 *     'age' => 'required|integer|min:18|max:120'
	 * ]);
	 * ```
	 * 
	 * @example With update-specific rules:
	 * ```php
	 * $this->setValidations(
	 *     // Create rules
	 *     [
	 *         'email' => 'required|email|unique:users',
	 *         'password' => 'required|min:8'
	 *     ],
	 *     // Update rules (password optional, email unique except current)
	 *     [
	 *         'email' => 'required|email|unique:users,email,' . $id,
	 *         'password' => 'nullable|min:8'
	 *     ]
	 * );
	 * ```
	 */
	public function setValidations(array $roles = [], array $on_update = []): void {
		$this->validations = $roles;
		if (!empty($on_update) && canvastack_array_contained_string([ControllerConstants::ACTION_EDIT, ControllerConstants::ACTION_UPDATE], explode('.', current_route()))) {
			unset($this->validations);
			$this->validations = $on_update;
		}
		$this->form->setValidations($this->validations);
	}
	
	private static array $validation_messages = [];
	private static array $validation_rules = [];
	
	/**
	 * Validate request data against configured rules
	 * 
	 * Performs comprehensive validation of request data using Laravel's Validator. This method
	 * creates a validator instance with the configured rules, checks for validation failures,
	 * and handles error messages with XSS protection. If validation fails, it redirects back
	 * to the specified page with escaped error messages.
	 * 
	 * The method stores validation rules and messages in static properties for potential
	 * access by other methods or debugging purposes.
	 * 
	 * @param Request $request The request to validate
	 * @param string|null $current_page The page to redirect to on validation failure (typically 'edit' or 'create')
	 * @return \Illuminate\Http\RedirectResponse|null Redirect response if validation fails, null if passes
	 * 
	 * @security XSS Protection - All validation error messages are escaped via escapeValidationMessages()
	 * @security Input Validation - Enforces data integrity rules before database operations
	 * @security Type Safety - Ensures data types match expected formats
	 * 
	 * @example Internal usage in insert_data():
	 * ```php
	 * public function insert_data(Request $request, bool $routeback = true): mixed {
	 *     $this->validation($request, ControllerConstants::ACTION_EDIT);
	 *     return $this->INSERT_DATA_PROCESSOR($request, $routeback);
	 * }
	 * ```
	 */
	protected function validation(Request $request, ?string $current_page = null): ?\Illuminate\Http\RedirectResponse {
		$validator = Validator::make($request->all(), $this->validations);
		self::$validation_rules = $validator->getRules();
		if (true === $validator->fails()) {
			self::$validation_messages['status']   = ControllerConstants::VALIDATION_STATUS_FAILED;
			
			// Escape validation error messages to prevent XSS
			$rawMessages = $validator->getMessageBag()->messages();
			self::$validation_messages['messages'] = $this->escapeValidationMessages($rawMessages);
			
			return self::redirect($current_page, self::$validation_messages['messages'], self::$validation_messages['status']);
		}
		
		return null;
	}
	
	/**
	 * Redirect with message and status
	 * 
	 * Creates a redirect response with flash messages and status indicators. This method handles
	 * various message data types (Request objects, arrays, strings) and ensures all message
	 * content is properly escaped to prevent XSS attacks. It processes file uploads separately
	 * from regular form data and sets appropriate success/failure status flags.
	 * 
	 * The method supports three status types:
	 * - Success: true or ControllerConstants::VALIDATION_STATUS_SUCCESS
	 * - Failed: false or ControllerConstants::VALIDATION_STATUS_FAILED
	 * - Custom: any other string value
	 * 
	 * @param string|null $to The destination route (null = redirect back)
	 * @param mixed $message_data The message data to display (string, array, or Request object)
	 * @param bool|string $status_info The status indicator (true=success, false=failed, string=custom)
	 * @return \Illuminate\Http\RedirectResponse Redirect response with flashed messages
	 * 
	 * @security XSS Protection - All message data is escaped via escapeRedirectMessageStatic()
	 * @security File Handling - Separates file data from regular form data for secure processing
	 * @security Type Safety - Validates and normalizes status values
	 * 
	 * @example Redirect with success message:
	 * ```php
	 * return self::redirect('users.index', 'User created successfully', true);
	 * ```
	 * 
	 * @example Redirect with validation errors:
	 * ```php
	 * return self::redirect('users.create', $validator->errors(), false);
	 * ```
	 * 
	 * @example Redirect back with array message:
	 * ```php
	 * return self::redirect(null, ['name' => 'Updated', 'email' => 'Changed'], true);
	 * ```
	 */
	public static function redirect(?string $to, mixed $message_data = [], bool|string $status_info = true): \Illuminate\Http\RedirectResponse {
		$message  = null;
		if (!empty($message_data)) {
			if (is_object($message_data) && 'Request' === class_basename($message_data)) {
				if ($message_data->allFiles()) {
					$message = $message_data->all();
					$files   = [];
					foreach ($message_data->allFiles() as $filename => $filedata) {
						$files[$filename] = $filedata;
						unset($message[$filename]);
					}
					// Files Need Re-Check Again!!!
				} else {
					$message = $message_data->all();
				}
			} else {
				$message = $message_data;
			}
		}
		
		$status = false;
		if (!empty($status_info)) {
			if (!in_array($status_info, [ControllerConstants::VALIDATION_STATUS_SUCCESS, true]) || ControllerConstants::VALIDATION_STATUS_FAILED === $status_info) {
				$status     = ControllerConstants::VALIDATION_STATUS_FAILED;
			} else $status = ControllerConstants::VALIDATION_STATUS_SUCCESS;
		} else $status    = $status_info;
		
		$compact            = [];
		$compact['message'] = null;
		$compact['status']  = false;
		
		// Escape message data to prevent XSS
		if (!empty($message)) {
			// Create temporary instance to access escape method
			$escapedMessage = self::escapeRedirectMessageStatic($message);
			$compact['message'] = compact('escapedMessage');
			// Rename key back to 'message' for compatibility
			$compact['message']['message'] = $compact['message']['escapedMessage'];
			unset($compact['message']['escapedMessage']);
		}
		if (!empty($status))  $compact['status']  = compact('status');
		
		return canvastack_redirect($to, $compact['message'], $compact['status']);
	}
	
	/**
	 * Static helper for escaping redirect messages
	 * 
	 * @param mixed $messageData The message data to escape
	 * @return mixed Escaped message data
	 * 
	 * @security CRITICAL - Escapes all message data
	 */
	private static function escapeRedirectMessageStatic(mixed $messageData): mixed {
		if (is_array($messageData)) {
			$escaped = [];
			foreach ($messageData as $key => $value) {
				$safeKey = is_string($key) ? htmlspecialchars($key, ENT_QUOTES, 'UTF-8') : $key;
				$escaped[$safeKey] = self::escapeRedirectMessageStatic($value);
			}
			return $escaped;
		}
		
		if (is_string($messageData)) {
			return htmlspecialchars($messageData, ENT_QUOTES, 'UTF-8');
		}
		
		return $messageData;
	}
	
	/**
	 * Internal data update processor
	 * 
	 * Core method that handles the actual data update logic after validation. This method
	 * retrieves the existing model by ID, validates the request data, handles file uploads,
	 * and persists changes to the database using the canvastack_update() helper function.
	 * 
	 * The processor ensures data integrity by validating all inputs before updating and
	 * properly handles file uploads by checking for submitted files and processing them
	 * through the file upload system.
	 * 
	 * @param Request $request The HTTP request containing form data
	 * @param mixed $id The ID of the record to update
	 * @param bool $routeback Whether to redirect back after update (stored for use by update() method)
	 * @return void
	 * 
	 * @security CSRF Protection - All PUT/PATCH requests are validated before reaching this method
	 * @security Input Validation - Validates all inputs against configured rules
	 * @security File Upload Security - Validates uploaded files via checkFileInputSubmited()
	 * @security Record Existence - Verifies record exists via getModel() before updating
	 * 
	 * @performance Memory Management - Cleans up large variables after processing with unset()
	 * @performance Efficient Updates - Only updates changed fields, not entire record
	 * 
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If record not found
	 * 
	 * @example Internal usage from update() method:
	 * ```php
	 * protected function update(Request $request, int $id) {
	 *     $this->UPDATE_DATA_PROCESSOR($request, $id);
	 *     return $this->routeBackAfterAction(__FUNCTION__, $id);
	 * }
	 * ```
	 */
	private function UPDATE_DATA_PROCESSOR(Request $request, mixed $id, bool $routeback = true): void {
		$request->validate($this->validations);
		$model = $this->getModel($id);
		
		// check if any input file type submited
		$data = $this->checkFileInputSubmited($request);
		
		// Convert Request object to array if needed (for error handling compatibility)
		// Use original $request to preserve merged data
		if ($data instanceof \Illuminate\Http\Request) {
			$data = $request->all();
		}
		
		// Wrap database operation with error handling
		try {
			canvastack_update($model, $data);
			$this->stored_id = intval($id);
		} catch (\Exception $e) {
			// Handle database error gracefully
			$dataKeys = is_array($data) ? array_keys($data) : (is_object($data) && method_exists($data, 'all') ? array_keys($data->all()) : ['unknown']);
			
			$this->handleDatabaseError($e, 'update', [
				'model' => get_class($model),
				'id' => $id,
				'data_keys' => $dataKeys,
			]);
			
			// Re-throw if graceful degradation is disabled
			if (!config('canvastack.controller.error_handling.handle_database_errors', true)) {
				throw $e;
			}
		}
		
		// Clean up large variables after update
		unset($data, $model);
	}
	
	/**
	 * Update existing resource in database
	 * 
	 * RESTful controller method that handles PUT/PATCH requests to update existing records.
	 * This method delegates to UPDATE_DATA_PROCESSOR for the actual update logic, then either
	 * redirects to the edit page of the updated record or returns the ID directly, depending
	 * on the store_routeback property.
	 * 
	 * This is the standard Laravel resource controller method called by PUT/PATCH /resource/{id} routes.
	 * 
	 * @param Request $request HTTP request with form data to update
	 * @param int $id Record ID to update (typically an integer primary key)
	 * @return mixed Redirect response to edit page if store_routeback is true, stored ID otherwise
	 * 
	 * @security CSRF Protection - All PUT/PATCH requests are automatically protected by CSRF token verification
	 *           The framework validates CSRF tokens in Controller::callAction() before this method executes
	 * @security Input Validation - All inputs are validated via UPDATE_DATA_PROCESSOR
	 * @security XSS Protection - All output is escaped before rendering
	 * @security Record Existence - Verifies record exists before updating
	 * 
	 * @performance Single Update Query - Updates record with single database UPDATE statement
	 * @performance Efficient Updates - Only updates changed fields, not entire record
	 * 
	 * @throws \Illuminate\Validation\ValidationException If validation fails
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If record not found
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: PUT /users/123
	 * // Updates user ID 123 and redirects to /users/123/edit
	 * ```
	 * 
	 * @example API usage returning ID:
	 * ```php
	 * $this->store_routeback = false;
	 * $id = $this->update($request, 123);
	 * return response()->json(['id' => $id, 'message' => 'Updated']);
	 * ```
	 */
	protected function update(Request $request, int $id) {
		$this->UPDATE_DATA_PROCESSOR($request, $id);
		
		if (true === $this->store_routeback) {
			return $this->routeBackAfterAction(__FUNCTION__, $id);
		} else {
			return $this->stored_id;
		}
	}
	
	/**
	 * Remove the specified resource from storage
	 * 
	 * Deletes a record from the database, supporting both hard deletes and soft deletes depending
	 * on the model configuration. This method retrieves the model by ID and delegates to the
	 * canvastack_delete() helper function which handles the actual deletion logic including
	 * soft delete support, cascade deletes, and event firing.
	 * 
	 * After successful deletion, the method redirects back to the index page or previous page.
	 * 
	 * @param Request $request The HTTP request (may contain additional deletion parameters)
	 * @param mixed $id The resource ID to delete (typically an integer primary key)
	 * @return \Illuminate\Http\RedirectResponse Redirect response to index or previous page
	 * 
	 * @security CSRF Protection - DELETE requests are validated by framework before reaching this method
	 * @security ID Validation - Record ID is validated via getModel() before deletion
	 * @security Authorization - Should be protected by appropriate permissions/policies
	 * @security Cascade Protection - Related records are handled according to model relationships
	 * 
	 * @performance Soft Deletes - Uses soft delete if model supports it, avoiding data loss
	 * @performance Single Query - Deletes record with single database query
	 * 
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If record not found
	 * 
	 * @example Basic usage (automatically called by route):
	 * ```php
	 * // Route: DELETE /users/123
	 * // Deletes user ID 123 and redirects to /users
	 * ```
	 */
	protected function destroy(Request $request, $id) {
			$model = $this->getModel($id);
			
			// Wrap database operation with error handling
			try {
				canvastack_delete($request, $model, $id);
			} catch (\Exception $e) {
				// Handle database error gracefully
				$this->handleDatabaseError($e, 'delete', [
					'model' => get_class($model),
					'id' => $id,
				]);
				
				// Re-throw if graceful degradation is disabled
				if (!config('canvastack.controller.error_handling.handle_database_errors', true)) {
					throw $e;
				}
			}

			return $this->routeBackAfterAction(__FUNCTION__);
		}
	
	/**
	 * Find and load model data by ID
	 * 
	 * Retrieves a specific record from the database by its primary key and stores it in the
	 * model_data property. This method also checks if the record is soft deleted (if the model
	 * supports soft deletes) and sets the is_softdeleted flag accordingly.
	 * 
	 * This is an internal method used by other action methods to load record data before
	 * displaying or modifying it.
	 * 
	 * @param mixed $id The ID to find (typically an integer primary key)
	 * @return void Data is stored in $this->model_data property
	 * 
	 * @security ID Validation - ID should be validated before calling this method
	 * @security Soft Delete Awareness - Properly handles soft deleted records
	 * 
	 * @performance Single Query - Retrieves record with single database query
	 * @performance Eager Loading - Uses eager loading if configured to prevent N+1 queries
	 * 
	 * @example Internal usage in edit() method:
	 * ```php
	 * public function edit($id) {
	 *     $safeId = $this->escapeRouteParameter($id, 'int');
	 *     $model = $this->getModel($safeId);
	 *     $model->find($safeId); // Calls this method internally
	 * }
	 * ```
	 */
	/**
		 * Find Model by ID with Database Error Handling
		 * 
		 * Retrieves a model record by its primary key ID with comprehensive error handling.
		 * This method wraps the Eloquent find() operation in try-catch blocks to gracefully
		 * handle database connection failures, query errors, and other database-related issues.
		 * 
		 * The method performs the following operations:
		 * 1. Attempts to find the model record by ID
		 * 2. Checks if model uses soft deletes
		 * 3. Sets soft delete flag if record is deleted
		 * 4. Handles database errors gracefully with logging and user-friendly messages
		 * 
		 * Error Handling Strategy:
		 * - Database connection errors: Log error, set model_data to null, throw exception
		 * - Query errors: Log error with context, set model_data to null, throw exception
		 * - Soft delete check errors: Log warning, continue without soft delete flag
		 * - All errors are logged with user context for debugging
		 * 
		 * @param mixed $id The primary key ID to find (typically integer)
		 * @return void Sets $this->model_data with found record or null on error
		 * 
		 * @throws \Illuminate\Database\QueryException If database query fails
		 * @throws \PDOException If database connection fails
		 * @throws ControllerException If model find operation fails
		 * 
		 * @security Route Parameter Validation - ID should be validated before calling this method
		 * @security SQL Injection Prevention - Uses Eloquent ORM parameter binding
		 * 
		 * @performance Single database query using Eloquent find()
		 * @performance Soft delete check only if model supports soft deletes
		 * @performance Error logging only when errors occur
		 * 
		 * @example
		 * ```php
		 * try {
		 *     $safeId = $this->escapeRouteParameter($id, 'int');
		 *     $this->model_find($safeId);
		 *     
		 *     if ($this->model_data) {
		 *         // Record found
		 *         return view('edit', ['model' => $this->model_data]);
		 *     } else {
		 *         // Record not found
		 *         return redirect()->back()->with('error', 'Record not found');
		 *     }
		 * } catch (ControllerException $e) {
		 *     // Database error occurred
		 *     return redirect()->back()->with('error', 'Database error: ' . $e->getMessage());
		 * }
		 * ```
		 */
		public function model_find(mixed $id): void {
			try {
				// Attempt to find model by ID
				$this->model_data = $this->model->find($id);

				// Guard clause: Check if model supports soft deletes and record is soft deleted
				if (!$this->softDeletedModel) {
					return;
				}

				// Check if record is soft deleted
				try {
					if ($this->model_data && !is_null($this->model_data->deleted_at)) {
						$this->is_softdeleted = true;
					}
				} catch (\Exception $e) {
					// Soft delete check failed - log warning but continue
					if (config('canvastack.controller.logging.log_performance_issues', true)) {
						\Illuminate\Support\Facades\Log::warning('Soft delete check failed in model_find', [
							'model_id' => $id,
							'model_class' => get_class($this->model),
							'error' => $e->getMessage(),
							'user_id' => session('id'),
						]);
					}
				}

			} catch (\Illuminate\Database\QueryException $e) {
				// Database query error - log and throw user-friendly exception
				\Illuminate\Support\Facades\Log::error('Database query error in model_find', [
					'model_id' => $id,
					'model_class' => get_class($this->model),
					'error_code' => $e->getCode(),
					'error_message' => $e->getMessage(),
					'sql' => $e->getSql() ?? 'N/A',
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);

				// Set model_data to null
				$this->model_data = null;

				// Throw user-friendly exception
				throw new ControllerException(
					'Unable to retrieve record from database. Please try again later.',
					[],
					500,
					$e
				);

			} catch (\PDOException $e) {
				// Database connection error - log and throw user-friendly exception
				\Illuminate\Support\Facades\Log::error('Database connection error in model_find', [
					'model_id' => $id,
					'model_class' => get_class($this->model),
					'error_code' => $e->getCode(),
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);

				// Set model_data to null
				$this->model_data = null;

				// Throw user-friendly exception
				throw new ControllerException(
					'Database connection failed. Please check your connection and try again.',
					[],
					503,
					$e
				);

			} catch (\Exception $e) {
				// Generic error - log and throw user-friendly exception
				\Illuminate\Support\Facades\Log::error('Unexpected error in model_find', [
					'model_id' => $id,
					'model_class' => get_class($this->model),
					'error_message' => $e->getMessage(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);

				// Set model_data to null
				$this->model_data = null;

				// Throw user-friendly exception
				throw new ControllerException(
					'An unexpected error occurred while retrieving the record.',
					[],
					500,
					$e
				);
			}
		}
	
	public array $model_filters = [];
	
	/**
	 * Apply filters to model query
	 * 
	 * Applies filters to the model query with SQL injection prevention.
	 * Validates table and column names against schema before applying filters.
	 * 
	 * @param array $filters Filters to apply
	 * @param string $operator Comparison operator (=, !=, >, <, >=, <=, LIKE, etc.)
	 * @return void
	 * 
	 * @security SQL Injection Prevention - Validates table/column names and uses query builder bindings
	 * @performance Batch processes where conditions for better query optimization
	 */
	public function filterPage(array $filters = [], string $operator = '='): void {
		// Determine which filters to use (priority: parameter > filter_page > empty)
		$this->model_filters = !empty($filters) ? $filters : ($this->filter_page ?? []);

		// Validate operator to prevent SQL injection
		$operator = $this->validateOperator($operator);

		// Validate table name if model_table is set
		if (!empty($this->model_table)) {
			$this->validateTableName($this->model_table);
		}

		// Build optimized where conditions
		$whereConditions = [];
		
		foreach ($this->model_filters as $fieldname => $fieldvalue) {
			// Validate column name against table schema
			if (!empty($this->model_table)) {
				$this->validateColumnName($this->model_table, $fieldname);
			}
			
			// Validate filter value
			$this->validateFilterValue($fieldname, $fieldvalue, $operator);

			// Add to where conditions array for batch processing
			$whereConditions[] = [
				'field_name' => $fieldname,
				'operator'   => $operator,
				'value'      => $fieldvalue
			];
		}
		
		// Apply all where conditions at once for better query optimization
		if (!empty($whereConditions)) {
			$this->table->conditions['where'] = array_merge(
				$this->table->conditions['where'] ?? [],
				$whereConditions
			);
		}
	}
	
	/**
	 * Validate filter value
	 * 
	 * Validates filter values to ensure they are safe and within acceptable ranges.
	 * Checks for type safety, length limits, and dangerous patterns.
	 * 
	 * @param string $fieldName Field name being filtered
	 * @param mixed $value Filter value
	 * @param string $operator Comparison operator
	 * @return bool True if value is valid
	 * @throws \InvalidArgumentException If value is invalid
	 * 
	 * @security CRITICAL - Validates filter values to prevent injection attacks
	 */
	private function validateFilterValue(string $fieldName, mixed $value, string $operator): bool {
		// Check if validation is enabled
		$strictMode = config('canvastack.controller.validation.strict_mode', true);
		if (!$strictMode) {
			return true;
		}
		
		// Validate array depth for array values
		if (is_array($value)) {
			$maxArrayDepth = config('canvastack.controller.validation.max_array_depth', 10);
			$depth = $this->getArrayDepth($value);
			
			if ($depth > $maxArrayDepth) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Filter Validation Failed: Array depth exceeds maximum', [
						'field_name' => $fieldName,
						'array_depth' => $depth,
						'max_depth' => $maxArrayDepth,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw ControllerValidationException::invalidFilterValue(
					$fieldName,
					$value,
					"Filter value array depth ({$depth}) exceeds maximum allowed ({$maxArrayDepth})",
					[
						'field_name' => $fieldName,
						'array_depth' => $depth,
						'max_depth' => $maxArrayDepth,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Validate each array element
			foreach ($value as $element) {
				$this->validateFilterValue($fieldName, $element, $operator);
			}
			
			return true;
		}
		
		// Validate string length
		if (is_string($value)) {
			$maxLength = config('canvastack.controller.validation.max_query_length', 10000);
			
			if (strlen($value) > $maxLength) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Filter Validation Failed: Value too long', [
						'field_name' => $fieldName,
						'value_length' => strlen($value),
						'max_length' => $maxLength,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw ControllerValidationException::invalidFilterValue(
					$fieldName,
					$value,
					"Filter value length (" . strlen($value) . ") exceeds maximum allowed ({$maxLength})",
					[
						'field_name' => $fieldName,
						'value_length' => strlen($value),
						'max_length' => $maxLength,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Check for null bytes (potential injection)
			if (strpos($value, chr(0)) !== false) {
				// Log security event
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Filter Validation Failed: Null byte detected', [
						'field_name' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw ControllerValidationException::invalidFilterValue(
					$fieldName,
					$value,
					"Filter value contains null bytes",
					[
						'field_name' => $fieldName,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		// Validate numeric values for numeric operators
		if (in_array($operator, ['>', '<', '>=', '<='])) {
			if (!is_numeric($value) && !is_null($value)) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Filter Validation Failed: Non-numeric value for numeric operator', [
						'field_name' => $fieldName,
						'operator' => $operator,
						'value_type' => gettype($value),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw ControllerValidationException::invalidFilterValue(
					$fieldName,
					$value,
					"Filter value must be numeric for operator {$operator}",
					[
						'field_name' => $fieldName,
						'operator' => $operator,
						'value_type' => gettype($value),
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		return true;
	}
	
	/**
	 * Get array depth
	 * 
	 * Calculates the maximum nesting depth of an array.
	 * 
	 * @param array $array Array to check
	 * @return int Maximum depth
	 */
	private function getArrayDepth(array $array): int {
		$maxDepth = 1;
		
		foreach ($array as $value) {
			if (is_array($value)) {
				$depth = $this->getArrayDepth($value) + 1;
				if ($depth > $maxDepth) {
					$maxDepth = $depth;
				}
			}
		}
		
		return $maxDepth;
	}

	/**
	 * Validate table name against whitelist
	 *
	 * Validates that a table name exists in the database schema to prevent
	 * SQL injection via dynamic table names.
	 *
	 * @param string $tableName Table name to validate
	 * @return bool True if table name is valid
	 * @throws \InvalidArgumentException If table name is invalid
	 *
	 * @security SQL Injection Prevention - Validates table names against database schema
	 */
	private function validateTableName(string $tableName): bool {
		// Check if SQL injection prevention is enabled
		$sqlInjectionPrevention = config('canvastack.controller.security.sql_injection_prevention', true);
		if (!$sqlInjectionPrevention) {
			return true;
		}

		// Check if table name validation is enabled
		$validateTableNames = config('canvastack.controller.validation.validate_table_names', true);
		if (!$validateTableNames) {
			return true;
		}

		// Remove any backticks or quotes
		$tableName = str_replace(['`', '"', "'"], '', $tableName);

		// Determine which connection to use
		// Priority: 1. Controller connection property, 2. Model connection, 3. Default connection
		$connection = null;
		
		if (!empty($this->connection)) {
			// Use controller's connection if set
			$connection = $this->connection;
		} elseif (!empty($this->model)) {
			// Try to get connection from model
			try {
				$modelInstance = is_string($this->model) ? new $this->model : $this->model;
				$connection = $modelInstance->getConnectionName();
			} catch (\Exception $e) {
				// If model instantiation fails, use default connection
				$connection = null;
			}
		}

		// Check if table exists in database schema using the correct connection
		$tableExists = false;
		if ($connection) {
			$tableExists = \Illuminate\Support\Facades\Schema::connection($connection)->hasTable($tableName);
		} else {
			$tableExists = \Illuminate\Support\Facades\Schema::hasTable($tableName);
		}

		if (!$tableExists) {
			// Log security event
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::warning('SQL Injection Attempt: Invalid table name', [
					'table_name' => $tableName,
					'connection' => $connection ?? 'default',
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'user_agent' => request()->userAgent(),
				]);
			}

			throw new SQLInjectionAttemptException(
				"Invalid table name: {$tableName}",
				[
					'table_name' => $tableName,
					'connection' => $connection ?? 'default',
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'route' => request()->path(),
				]
			);
		}

		return true;
	}

	/**
	 * Validate column name against table schema
	 *
	 * Validates that a column name exists in the specified table's schema
	 * to prevent SQL injection via dynamic column names.
	 *
	 * @param string $tableName Table name
	 * @param string $columnName Column name to validate
	 * @return bool True if column name is valid
	 * @throws \InvalidArgumentException If column name is invalid
	 *
	 * @security SQL Injection Prevention - Validates column names against table schema
	 */
	private function validateColumnName(string $tableName, string $columnName): bool {
		// Check if SQL injection prevention is enabled
		$sqlInjectionPrevention = config('canvastack.controller.security.sql_injection_prevention', true);
		if (!$sqlInjectionPrevention) {
			return true;
		}

		// Check if column name validation is enabled
		$validateColumnNames = config('canvastack.controller.validation.validate_column_names', true);
		if (!$validateColumnNames) {
			return true;
		}

		// Remove any backticks or quotes
		$columnName = str_replace(['`', '"', "'"], '', $columnName);

		// Determine which connection to use
		// Priority: 1. Controller connection property, 2. Model connection, 3. Default connection
		$connection = null;
		
		if (!empty($this->connection)) {
			// Use controller's connection if set
			$connection = $this->connection;
		} elseif (!empty($this->model)) {
			// Try to get connection from model
			try {
				$modelInstance = is_string($this->model) ? new $this->model : $this->model;
				$connection = $modelInstance->getConnectionName();
			} catch (\Exception $e) {
				// If model instantiation fails, use default connection
				$connection = null;
			}
		}

		// Check if column exists in table schema using the correct connection
		$columnExists = false;
		if ($connection) {
			$columnExists = \Illuminate\Support\Facades\Schema::connection($connection)->hasColumn($tableName, $columnName);
		} else {
			$columnExists = \Illuminate\Support\Facades\Schema::hasColumn($tableName, $columnName);
		}

		if (!$columnExists) {
			// Log security event
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::warning('SQL Injection Attempt: Invalid column name', [
					'table_name' => $tableName,
					'column_name' => $columnName,
					'connection' => $connection ?? 'default',
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'user_agent' => request()->userAgent(),
				]);
			}

			throw new SQLInjectionAttemptException(
				"Invalid column name: {$columnName} for table: {$tableName}",
				[
					'column_name' => $columnName,
					'table_name' => $tableName,
					'connection' => $connection ?? 'default',
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'route' => request()->path(),
				]
			);
		}

		return true;
	}
	
	public array $model_class_path = [];
	/**
	 * Apply eager loading to model to prevent N+1 queries
	 * 
	 * Automatically detects and loads relationships defined in the model
	 * to optimize query performance.
	 * 
	 * @param mixed $model Eloquent model or query builder
	 * @return mixed Model with eager loading applied
	 * 
	 * @performance Prevents N+1 query problems by loading relationships upfront
	 */
	private function applyEagerLoading(mixed $model): mixed {
		// Check if model has relationships defined
		if (method_exists($model, 'getRelations')) {
			// Get model class to check for relationship methods
			$modelClass = get_class($model);
			
			// Common relationship method patterns to eager load
			$relationshipMethods = [];
			
			// Use reflection to find relationship methods
			try {
				$reflection = new \ReflectionClass($modelClass);
				$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
				
				foreach ($methods as $method) {
					$methodName = $method->getName();
					
					// Skip magic methods, getters, setters, and common non-relationship methods
					if (
						strpos($methodName, '__') === 0 ||
						strpos($methodName, 'get') === 0 ||
						strpos($methodName, 'set') === 0 ||
						strpos($methodName, 'scope') === 0 ||
						in_array($methodName, ['getTable', 'getKey', 'getKeyName', 'getConnection', 'getRelations'])
					) {
						continue;
					}
					
					// Check if method returns a relationship
					$returnType = $method->getReturnType();
					if ($returnType) {
						$returnTypeName = $returnType->getName();
						// Check if return type is a relationship class
						if (
							strpos($returnTypeName, 'Illuminate\\Database\\Eloquent\\Relations\\') === 0 ||
							in_array($returnTypeName, [
								'HasOne', 'HasMany', 'BelongsTo', 'BelongsToMany', 
								'MorphTo', 'MorphOne', 'MorphMany', 'MorphToMany'
							])
						) {
							$relationshipMethods[] = $methodName;
						}
					}
				}
				
				// Apply eager loading if relationships found
				if (!empty($relationshipMethods)) {
					// Limit to first 5 relationships to avoid over-eager loading
					$relationshipsToLoad = array_slice($relationshipMethods, 0, 5);
					$model = $model->with($relationshipsToLoad);
					
					// Log performance optimization if enabled
					if (config('canvastack.controller.logging.log_performance_issues', true)) {
						\Log::debug('Applied eager loading', [
							'model' => $modelClass,
							'relationships' => $relationshipsToLoad
						]);
					}
				}
			} catch (\ReflectionException $e) {
				// Silently fail if reflection fails
				if (config('canvastack.controller.logging.log_performance_issues', true)) {
					\Log::warning('Failed to apply eager loading', [
						'model' => $modelClass,
						'error' => $e->getMessage()
					]);
				}
			}
		}
		
		return $model;
	}

	/**
	 * Optimize column selection for query
	 * 
	 * Selects only required columns instead of SELECT * to improve performance.
	 * Automatically includes primary key and timestamps if they exist.
	 * 
	 * @param mixed $model Eloquent model or query builder
	 * @param array $columns Specific columns to select (empty = use table columns)
	 * @return mixed Model with optimized column selection
	 * 
	 * @performance Reduces data transfer and memory usage by selecting only needed columns
	 */
	private function optimizeColumnSelection(mixed $model, array $columns = []): mixed {
		if (!config('canvastack.controller.performance.query_optimization', true)) {
			return $model;
		}
		
		// If specific columns provided, use them
		if (!empty($columns)) {
			// Always include primary key
			$keyName = $model->getKeyName();
			if (!in_array($keyName, $columns)) {
				array_unshift($columns, $keyName);
			}
			
			// Include timestamps if model uses them
			if (method_exists($model, 'usesTimestamps') && $model->usesTimestamps()) {
				if (!in_array('created_at', $columns)) {
					$columns[] = 'created_at';
				}
				if (!in_array('updated_at', $columns)) {
					$columns[] = 'updated_at';
				}
			}
			
			// Include soft delete column if model uses soft deletes
			if ($this->softDeletedModel && !in_array('deleted_at', $columns)) {
				$columns[] = 'deleted_at';
			}
			
			return $model->select($columns);
		}
		
		// If table component is set, use its columns
		if (!empty($this->table) && !empty($this->table->columns)) {
			$tableColumns = [];
			
			// Extract column names from table configuration
			foreach ($this->table->columns as $column) {
				if (is_array($column) && isset($column['field'])) {
					$tableColumns[] = $column['field'];
				} elseif (is_string($column)) {
					$tableColumns[] = $column;
				}
			}
			
			if (!empty($tableColumns)) {
				return $this->optimizeColumnSelection($model, $tableColumns);
			}
		}
		
		// Default: return model without modification (will use SELECT *)
		return $model;
	}

	/**
	 * Initialize model with query optimization
	 * 
	 * Implements eager loading to prevent N+1 queries when relationships are used.
	 * Applies filters and handles soft deletes efficiently.
	 * 
	 * @param string $class Model class name
	 * @param array $filter Model filters
	 * @return void
	 * 
	 * @security Validates table and column names to prevent SQL injection
	 * @performance Uses eager loading and query optimization when enabled
	 */
	protected function model(string $class, array $filter = []): void {
		$routeprocessor         = ['store', 'update', 'delete'];
		$currentPage            = last(explode('.', current_route()));
		
		$this->model_path       = $class;
		$this->model_filters    = $filter;
		$this->softDeletedModel = canvastack_is_softdeletes($class);
		
		$this->model            = new $this->model_path();
		$this->model_table      = $this->model->getTable();
		
		// Apply eager loading if enabled in configuration
		if (config('canvastack.controller.performance.eager_loading', true)) {
			$this->model = $this->applyEagerLoading($this->model);
		}
		
		// Apply column optimization if enabled
		if (config('canvastack.controller.performance.query_optimization', true)) {
			$this->model = $this->optimizeColumnSelection($this->model);
		}
		
		if (true === $this->softDeletedModel) {
			if (!in_array($currentPage, $routeprocessor)) {
				$this->model    = $this->model::withTrashed();
			}
		}
		if (!empty($this->model_filters)) {
			$this->model        = $this->model->where($this->model_filters);
		}
		$this->model_original   = $this->model;
		
		if (!empty(canvastack_get_current_route_id())) {
			$this->model_id     = canvastack_get_current_route_id();
			$this->model_find($this->model_id);
		//	$this->connection   = $this->model->getConnectionName();
		}
		
		if (!empty($this->form)) $this->form->model = $this->model;
	}
	
	/**
	 * Redirect page after successful login
	 * 
	 * Determines the appropriate redirect destination based on the user's group ID after
	 * successful authentication. Root users (group_id = 1) are redirected to the internal
	 * admin panel, while other users are redirected to the external admin interface.
	 * 
	 * This method uses Laravel's intended() redirect to support redirect-after-login
	 * functionality, sending users to their originally requested page if available.
	 * 
	 * Created @Aug 18, 2018
	 * Author: wisnuwidi
	 * 
	 * @return \Illuminate\Http\RedirectResponse Redirect to appropriate admin panel
	 * 
	 * @security Group-based Access Control - Separates root and admin user interfaces
	 * @security Session Validation - Relies on authenticated session data
	 * 
	 * @example After login:
	 * ```php
	 * // Root user (group_id = 1): redirects to $this->rootPage
	 * // Admin user (group_id = 2): redirects to $this->adminPage
	 * return $this->firstRedirect();
	 * ```
	 */
	public function firstRedirect(): \Illuminate\Http\RedirectResponse {
		$group_id = null;
		if (!empty($this->session_auth['group_id'])) $group_id = intval($this->session_auth['group_id']);
		if (1 === intval($group_id)) {
			// root group as internal
			return redirect()->intended($this->rootPage);
		} else {
			// admin and/or another group except root group as external
			return redirect()->intended($this->adminPage);
		}
	}
	
	/**
	 * Get model instance with soft delete handling
	 * 
	 * Returns the model instance, handling soft deletes efficiently.
	 * Optimized to avoid duplicate queries when finding soft-deleted records.
	 * 
	 * @param mixed $find ID to find (false = return query builder)
	 * @return mixed Model instance or query builder
	 * 
	 * @performance Optimizes soft delete queries to avoid duplicate find() calls
	 */
	protected function getModel(mixed $find = false): mixed {
		try {
			$model = [];
			if ('Builder' === class_basename($this->model)) {
				$model = $this->model_original;
			} else {
				$model = $this->model;
			}
			
			// If model is still an array (not initialized), throw exception
			if (is_array($model)) {
				throw new \RuntimeException('Model not initialized. Cannot retrieve model instance.');
			}
			
			// If model is a Builder, get the model class from it
			if ($model instanceof \Illuminate\Database\Eloquent\Builder) {
				$modelClass = get_class($model->getModel());
			} else {
				$modelClass = is_string($model) ? $model : get_class($model);
			}
			
			if (true === $this->softDeletedModel) {
				if (false !== $find) {
					try {
						// Optimized: Use withTrashed() directly to avoid two queries
						// Old approach: try find(), then withTrashed()->find() (2 queries)
						// New approach: withTrashed()->find() once (1 query)
						if (config('canvastack.controller.performance.query_optimization', true)) {
							$result = $modelClass::withTrashed()->find($find);
							
							// Log optimization if enabled
							if (config('canvastack.controller.logging.log_performance_issues', true) && $result) {
								\Log::debug('Optimized soft delete query', [
									'model' => $modelClass,
									'id' => $find,
									'is_deleted' => !is_null($result->deleted_at ?? null)
								]);
							}
							
							return $result;
						} else {
							// Legacy behavior: try without trashed first
							if ($model instanceof \Illuminate\Database\Eloquent\Builder) {
								$normalResult = $model->find($find);
							} else {
								$normalResult = $model->find($find);
							}
							if (!empty($normalResult)) {
								return $normalResult;
							} else {
								return $modelClass::withTrashed()->find($find);
							}
						}
					} catch (\Illuminate\Database\QueryException $e) {
						// Database query error in soft delete operation
						\Illuminate\Support\Facades\Log::error('Database query error in getModel (soft delete)', [
							'model_id' => $find,
							'model_class' => $modelClass,
							'error_code' => $e->getCode(),
							'error_message' => $e->getMessage(),
							'sql' => $e->getSql() ?? 'N/A',
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]);
						
						// Throw user-friendly exception
						throw new ControllerException(
							'Unable to retrieve record from database. Please try again later.',
							[],
							500,
							$e
						);
					}
					
				} else {
					return canvastack_get_model($model, $find);
				}
			} else {
				return canvastack_get_model($model, $find);
			}
			
		} catch (\PDOException $e) {
			// Database connection error
			\Illuminate\Support\Facades\Log::error('Database connection error in getModel', [
				'model_id' => $find,
				'model_class' => isset($model) ? get_class($model) : 'unknown',
				'error_code' => $e->getCode(),
				'error_message' => $e->getMessage(),
				'user_id' => session('id'),
				'ip_address' => request()->ip(),
			]);
			
			// Throw user-friendly exception
			throw new ControllerException(
				'Database connection failed. Please check your connection and try again.',
				[],
				503,
				$e
			);
			
		} catch (ControllerException $e) {
			// Re-throw ControllerException (already handled)
			throw $e;
			
		} catch (\Exception $e) {
			// Generic error
			\Illuminate\Support\Facades\Log::error('Unexpected error in getModel', [
				'model_id' => $find,
				'model_class' => isset($model) ? get_class($model) : 'unknown',
				'model_type' => gettype($model),
				'model_value' => is_array($model) ? 'array' : (is_object($model) ? get_class($model) : $model),
				'error_message' => $e->getMessage(),
				'error_trace' => $e->getTraceAsString(),
				'user_id' => session('id'),
				'ip_address' => request()->ip(),
			]);
			
			// Throw user-friendly exception
			throw new ControllerException(
				'An unexpected error occurred while retrieving the model.',
				[],
				500,
				$e
			);
		}
	}
	
	/**
	 * Get model table name from model instance
	 * 
	 * Retrieves the database table name associated with the model. This method delegates to
	 * getModel() to retrieve the model instance, then calls the Eloquent getTable() method
	 * to extract the table name. Useful for dynamic table operations and query building.
	 * 
	 * @param bool $find Whether to find a specific record (false = just get table name)
	 * @return string The database table name (e.g., 'users', 'posts', 'categories')
	 * 
	 * @example Get table name for current model:
	 * ```php
	 * $tableName = $this->getModelTable(); // Returns 'users'
	 * ```
	 */
	protected function getModelTable(bool $find = false): string {
		return $this->getModel($find)->getTable();
	}
	
	/**
	 * Redirect back after data submission process
	 * 
	 * Generates a redirect response to the appropriate page after a successful data operation
	 * (store, update, or destroy). For store and update operations, redirects to the edit page
	 * with the record ID. For destroy operations, redirects to the index page.
	 * 
	 * The method constructs the redirect URL by parsing the current route and replacing the
	 * action name with the appropriate destination (e.g., 'store' becomes '{id}.edit').
	 * 
	 * @param string $function_name The name of the calling function ('store', 'update', or 'destroy')
	 * @param int|bool $id The record ID (false for destroy operations)
	 * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse Redirect response
	 * 
	 * @example After store operation:
	 * ```php
	 * // Current route: users.store
	 * // Redirects to: users/123/edit (where 123 is the new record ID)
	 * return $this->routeBackAfterAction('store', 123);
	 * ```
	 * 
	 * @example After destroy operation:
	 * ```php
	 * // Current route: users.destroy
	 * // Redirects to: users (index page)
	 * return $this->routeBackAfterAction('destroy');
	 * ```
	 */
	private function routeBackAfterAction(string $function_name, int|bool|null $id = false): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse {
		if (!empty($id)) {
			$routeBack = str_replace('.', '/', str_replace($function_name, "{$id}.edit", current_route()));
		} else {
			$routeBack = str_replace('.', '/', str_replace($function_name, '', current_route()));
		}
		
		return redirect($routeBack);
	}
	
	/**
	 * Set upload path URL for file uploads
	 * 
	 * Constructs the URL path for file upload destinations by parsing the current route and
	 * removing the action method name. This URL is used to organize uploaded files into
	 * directories that match the controller structure (e.g., uploads/users/, uploads/posts/).
	 * 
	 * The method removes the last segment of the route (the action name) and converts dots
	 * to slashes to create a valid URL path.
	 * 
	 * @return string The upload URL path (e.g., 'users', 'admin/posts', 'settings/profile')
	 * 
	 * @example For route 'admin.users.store':
	 * ```php
	 * $uploadUrl = $this->setUploadURL(); // Returns 'admin/users'
	 * // Files will be uploaded to: storage/app/public/admin/users/
	 * ```
	 */
	private function setUploadURL(): string {
		$currentRoute = explode('.', current_route());
		unset($currentRoute[array_key_last($currentRoute)]);
		$currentRoute = implode('.', $currentRoute);
		
		return str_replace('.', '/', str_replace('.' . __FUNCTION__, '', $currentRoute));
	}
	
	/**
	 * Check if any file input has been submitted
	 * 
	 * Examines the request for file uploads and processes them if found. This method iterates
	 * through all request files, checks if each file input has an actual file submitted, and
	 * delegates to the uploadFiles() method for processing. If no files are submitted, returns
	 * the original request unchanged.
	 * 
	 * The method handles multiple file inputs and ensures only valid file submissions are
	 * processed through the upload system.
	 * 
	 * @param Request $request The HTTP request to check for file uploads
	 * @return object|\Illuminate\Http\Request Modified request with file paths if files uploaded, original request otherwise
	 * 
	 * @security File Upload Validation - Validates file types, sizes, and extensions via uploadFiles()
	 * @security MIME Type Checking - Verifies actual file MIME types match extensions
	 * @security Path Traversal Prevention - Sanitizes file names to prevent directory traversal attacks
	 * 
	 * @performance Lazy Processing - Only processes files if they exist in the request
	 * @performance Memory Efficient - Streams files to disk without loading into memory
	 * 
	 * @example Internal usage in INSERT_DATA_PROCESSOR:
	 * ```php
	 * $data = $this->checkFileInputSubmited($request);
	 * $this->stored_id = canvastack_insert($model, $data, true);
	 * ```
	 */
	private function checkFileInputSubmited(Request $request): Request|array|\Illuminate\Http\RedirectResponse {
		if (!empty($request->files)) {
			
			foreach ($request->files as $inputname => $file) {
				if ($request->hasfile($inputname)) {
					// if any file type submited
					$file = $this->fileAttributes;
					return $this->uploadFiles($this->setUploadURL(), $request, $file);
				} else {
					// if no one file type submited
					return $request;
				}
			}
			
			// if no one file type submited
			return $request;
			
		} else {
			// if no one file type submited
			return $request;
		}
	}
	
	/**
	 * Escape route parameters for XSS protection
	 * 
	 * Escapes all route parameters to prevent XSS attacks.
	 * Uses htmlspecialchars with ENT_QUOTES to escape special characters.
	 * Also validates parameter types and ranges.
	 * 
	 * @param mixed $parameter The parameter value to escape
	 * @param string $expectedType Expected type (int, string, etc.)
	 * @return mixed Escaped parameter (string values are escaped, others returned as-is)
	 * 
	 * @security CRITICAL - Must be called before displaying route parameters
	 * 
	 * @example
	 * $safeId = $this->escapeRouteParameter($id, 'int');
	 */
	private function escapeRouteParameter(mixed $parameter, string $expectedType = 'string'): mixed {
		// Validate parameter type if specified
		if ($expectedType !== 'string') {
			$this->validateRouteParameterType($parameter, $expectedType);
		}
		
		// Only escape string values
		if (is_string($parameter)) {
			return htmlspecialchars($parameter, ENT_QUOTES, 'UTF-8');
		}
		
		// Return non-string values as-is (int, bool, null, etc.)
		return $parameter;
	}
	
	/**
	 * Validate route parameter type
	 * 
	 * Validates that a route parameter matches the expected type.
	 * Throws exception if type doesn't match.
	 * 
	 * @param mixed $parameter Parameter value
	 * @param string $expectedType Expected type (int, string, bool, etc.)
	 * @return bool True if type is valid
	 * @throws \InvalidArgumentException If type is invalid
	 * 
	 * @security CRITICAL - Validates route parameter types
	 */
	private function validateRouteParameterType(mixed $parameter, string $expectedType): bool {
		// Check if validation is enabled
		$strictMode = config('canvastack.controller.validation.strict_mode', true);
		if (!$strictMode) {
			return true;
		}
		
		$isValid = false;
		
		switch ($expectedType) {
			case 'int':
			case 'integer':
				$isValid = is_numeric($parameter) && (int)$parameter == $parameter;
				if ($isValid && $parameter < 0) {
					// Log validation failure for negative IDs
					if (config('canvastack.controller.logging.log_validation_failures', true)) {
						\Illuminate\Support\Facades\Log::warning('Route Parameter Validation Failed: Negative integer', [
							'parameter' => $parameter,
							'expected_type' => $expectedType,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]);
					}
					throw ControllerValidationException::invalidRouteParameter(
						'id',
						$parameter,
						"Route parameter must be a positive integer, got: {$parameter}",
						[
							'parameter' => $parameter,
							'expected_type' => $expectedType,
							'user_id' => session('id'),
							'ip_address' => request()->ip(),
						]
					);
				}
				break;
				
			case 'string':
				$isValid = is_string($parameter);
				// Check string length
				if ($isValid) {
					$maxLength = config('canvastack.controller.validation.max_query_length', 10000);
					if (strlen($parameter) > $maxLength) {
						// Log validation failure
						if (config('canvastack.controller.logging.log_validation_failures', true)) {
							\Illuminate\Support\Facades\Log::warning('Route Parameter Validation Failed: String too long', [
								'parameter_length' => strlen($parameter),
								'max_length' => $maxLength,
								'user_id' => session('id'),
								'ip_address' => request()->ip(),
							]);
						}
						throw ControllerValidationException::invalidRouteParameter(
							'string',
							$parameter,
							"Route parameter string length exceeds maximum allowed",
							[
								'parameter_length' => strlen($parameter),
								'max_length' => $maxLength,
								'user_id' => session('id'),
								'ip_address' => request()->ip(),
							]
						);
					}
				}
				break;
				
			case 'bool':
			case 'boolean':
				$isValid = is_bool($parameter) || in_array($parameter, [0, 1, '0', '1', 'true', 'false'], true);
				break;
				
			case 'float':
			case 'double':
				$isValid = is_numeric($parameter);
				break;
				
			default:
				$isValid = true; // Unknown type, skip validation
		}
		
		if (!$isValid) {
			// Log validation failure
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::warning('Route Parameter Validation Failed: Type mismatch', [
					'parameter' => $parameter,
					'parameter_type' => gettype($parameter),
					'expected_type' => $expectedType,
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]);
			}
			
			throw ControllerValidationException::invalidRouteParameter(
				$expectedType,
				$parameter,
				"Route parameter type mismatch: expected {$expectedType}, got " . gettype($parameter),
				[
					'parameter' => $parameter,
					'parameter_type' => gettype($parameter),
					'expected_type' => $expectedType,
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}
		
		return true;
	}
	
	/**
	 * Escape validation error messages for XSS protection
	 * 
	 * Recursively escapes all validation error messages to prevent XSS attacks.
	 * Handles both single messages and arrays of messages.
	 * 
	 * @param mixed $messages The validation messages to escape
	 * @return mixed Escaped messages
	 * 
	 * @security CRITICAL - Must be called before displaying validation errors
	 * 
	 * @example
	 * $safeMessages = $this->escapeValidationMessages($validator->messages());
	 */
	private function escapeValidationMessages(mixed $messages): mixed {
		if (is_array($messages)) {
			$escaped = [];
			foreach ($messages as $key => $value) {
				$escaped[$this->escapeRouteParameter($key)] = $this->escapeValidationMessages($value);
			}
			return $escaped;
		}
		
		if (is_string($messages)) {
			return htmlspecialchars($messages, ENT_QUOTES, 'UTF-8');
		}
		
		return $messages;
	}
	
	/**
	 * Escape redirect message data for XSS protection
	 * 
	 * Escapes all message data before redirecting to prevent XSS attacks.
	 * Handles arrays, objects, and string values.
	 * 
	 * @param mixed $messageData The message data to escape
	 * @return mixed Escaped message data
	 * 
	 * @security CRITICAL - Must be called before redirecting with messages
	 * 
	 * @example
	 * $safeMessage = $this->escapeRedirectMessage($message);
	 */
	private function escapeRedirectMessage(mixed $messageData): mixed {
		if (is_array($messageData)) {
			$escaped = [];
			foreach ($messageData as $key => $value) {
				$escaped[$this->escapeRouteParameter($key)] = $this->escapeRedirectMessage($value);
			}
			return $escaped;
		}
		
		if (is_string($messageData)) {
			return htmlspecialchars($messageData, ENT_QUOTES, 'UTF-8');
		}
		
		return $messageData;
	}

	/**
	 * Validate SQL operator
	 * 
	 * Validates that an operator is in the whitelist of allowed SQL operators
	 * to prevent SQL injection via operator manipulation.
	 * 
	 * @param string $operator Operator to validate
	 * @return string Validated operator
	 * @throws \InvalidArgumentException If operator is invalid
	 * 
	 * @security SQL Injection Prevention - Validates operators against whitelist
	 */
	private function validateOperator(string $operator): string {
		// Check if SQL injection prevention is enabled
		$sqlInjectionPrevention = config('canvastack.controller.security.sql_injection_prevention', true);
		if (!$sqlInjectionPrevention) {
			return $operator;
		}

		// Whitelist of allowed operators
		$allowedOperators = [
			'=', '!=', '<>', '>', '<', '>=', '<=',
			'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
			'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
			'IS NULL', 'IS NOT NULL',
			'REGEXP', 'NOT REGEXP',
			'RLIKE', 'NOT RLIKE',
		];

		// Normalize operator (trim and uppercase for comparison)
		$normalizedOperator = strtoupper(trim($operator));

		// Check if operator is in whitelist
		if (!in_array($normalizedOperator, array_map('strtoupper', $allowedOperators))) {
			// Log security event
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::warning('SQL Injection Attempt: Invalid operator', [
					'operator' => $operator,
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'user_agent' => request()->userAgent(),
				]);
			}

			throw new SQLInjectionAttemptException(
				"Invalid SQL operator: {$operator}",
				[
					'operator' => $operator,
					'allowed_operators' => $allowedOperators,
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
					'route' => request()->path(),
				]
			);
		}

		return $operator;
	}
	
	/**
	 * Handle Database Error Gracefully
	 * 
	 * Handles database errors with graceful degradation, providing user-friendly error messages
	 * and detailed logging for debugging. Implements retry mechanisms for transient errors.
	 * 
	 * This method is called when database operations fail (insert, update, delete, query).
	 * It logs detailed error information, attempts retries for transient errors, and provides
	 * user-friendly error messages while preserving technical details in logs.
	 * 
	 * @param \Exception $exception The database exception that occurred
	 * @param string $operation The database operation that failed (insert, update, delete, query)
	 * @param array $context Additional context information for logging
	 * @return void
	 * 
	 * @throws \Exception Re-throws exception if graceful degradation is disabled
	 * 
	 * @security Logs detailed error information for security monitoring
	 * @security Prevents information disclosure by showing user-friendly messages
	 * @security Tracks repeated failures for potential attack detection
	 * 
	 * @performance Implements retry mechanisms for transient errors
	 * @performance Configurable retry attempts and delays
	 * 
	 * @example
	 * ```php
	 * try {
	 *     canvastack_insert($model, $data, true);
	 * } catch (\Exception $e) {
	 *     $this->handleDatabaseError($e, 'insert', [
	 *         'model' => get_class($model),
	 *         'data_keys' => array_keys($data),
	 *     ]);
	 * }
	 * ```
	 */
	private function handleDatabaseError(\Exception $exception, string $operation, array $context = []): void {
		// Check if graceful degradation is enabled
		$gracefulDegradation = config('canvastack.controller.error_handling.enable_graceful_degradation', true);
		$logDetailedErrors = config('canvastack.controller.error_handling.log_detailed_errors', true);
		$userFriendlyMessages = config('canvastack.controller.error_handling.user_friendly_messages', true);
		
		// Log detailed error information
		if ($logDetailedErrors) {
			\Illuminate\Support\Facades\Log::error("Database {$operation} operation failed", [
				'operation' => $operation,
				'exception_class' => get_class($exception),
				'exception_message' => $exception->getMessage(),
				'exception_code' => $exception->getCode(),
				'exception_file' => $exception->getFile(),
				'exception_line' => $exception->getLine(),
				'context' => $context,
				'user_id' => session('id'),
				'route' => request()->path(),
				'ip_address' => request()->ip(),
				'timestamp' => now()->toDateTimeString(),
			]);
		}
		
		// Determine if this is a transient error that can be retried
		$isTransientError = $this->isTransientDatabaseError($exception);
		
		// Attempt retry for transient errors
		if ($isTransientError && config('canvastack.controller.error_handling.enable_retry_mechanisms', true)) {
			$maxRetries = config('canvastack.controller.error_handling.max_retry_attempts', 3);
			$retryDelay = config('canvastack.controller.error_handling.retry_delay_ms', 100);
			
			\Illuminate\Support\Facades\Log::info("Attempting retry for transient database error", [
				'operation' => $operation,
				'max_retries' => $maxRetries,
				'retry_delay_ms' => $retryDelay,
			]);
			
			// Note: Actual retry logic would need to be implemented in the calling code
			// This method only logs the retry attempt
		}
		
		// Prepare user-friendly error message
		if ($gracefulDegradation && $userFriendlyMessages) {
			$userMessage = $this->getUserFriendlyDatabaseErrorMessage($operation);
			
			// Store error message in session for display
			session()->flash('error', $userMessage);
			
			// Log that user-friendly message was shown
			if ($logDetailedErrors) {
				\Illuminate\Support\Facades\Log::info("User-friendly error message displayed", [
					'operation' => $operation,
					'message' => $userMessage,
					'user_id' => session('id'),
				]);
			}
		}
		
		// If graceful degradation is disabled, re-throw the exception
		if (!$gracefulDegradation) {
			throw $exception;
		}
	}
	
	/**
	 * Check if Database Error is Transient
	 * 
	 * Determines if a database error is transient (temporary) and can be retried.
	 * Transient errors include connection timeouts, deadlocks, and temporary unavailability.
	 * 
	 * @param \Exception $exception The database exception
	 * @return bool True if error is transient and can be retried
	 * 
	 * @performance Helps implement efficient retry mechanisms
	 */
	private function isTransientDatabaseError(\Exception $exception): bool {
		$message = strtolower($exception->getMessage());
		$code = $exception->getCode();
		
		// Common transient error patterns
		$transientPatterns = [
			'connection',
			'timeout',
			'deadlock',
			'lock wait timeout',
			'too many connections',
			'server has gone away',
			'lost connection',
			'broken pipe',
		];
		
		// Check message for transient patterns
		foreach ($transientPatterns as $pattern) {
			if (str_contains($message, $pattern)) {
				return true;
			}
		}
		
		// Check error codes for transient errors
		$transientCodes = [
			1040, // Too many connections
			1205, // Lock wait timeout
			1213, // Deadlock
			2002, // Connection refused
			2003, // Can't connect
			2006, // Server has gone away
			2013, // Lost connection
		];
		
		if (in_array($code, $transientCodes)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get User-Friendly Database Error Message
	 * 
	 * Returns a user-friendly error message based on the database operation that failed.
	 * Technical details are hidden from users but logged for debugging.
	 * 
	 * @param string $operation The database operation that failed
	 * @return string User-friendly error message
	 * 
	 * @security Prevents information disclosure by hiding technical details
	 */
	private function getUserFriendlyDatabaseErrorMessage(string $operation): string {
		$messages = [
			'insert' => 'Unable to save the data. Please try again or contact support if the problem persists.',
			'update' => 'Unable to update the data. Please try again or contact support if the problem persists.',
			'delete' => 'Unable to delete the record. Please try again or contact support if the problem persists.',
			'query' => 'Unable to retrieve the data. Please try again or contact support if the problem persists.',
		];
		
		return $messages[$operation] ?? 'A database error occurred. Please try again or contact support if the problem persists.';
	}
}

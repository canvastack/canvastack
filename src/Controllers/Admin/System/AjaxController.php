<?php
namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Library\Components\Table\Craft\Export;
use Canvastack\Canvastack\Library\Components\Chart\Charts;
use Canvastack\Canvastack\Library\Constants\ControllerConstants as CC;
use Canvastack\Canvastack\Exceptions\Controller\CSRFException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\SQLInjectionAttemptException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AJAX Controller for handling asynchronous requests
 * 
 * Handles various AJAX operations including:
 * - Data filtering and synchronization
 * - Database connection management
 * - Data transfer between connections
 * - DataTables filtering
 * - Chart filtering
 * - CSV export
 * 
 * @package Canvastack\Canvastack\Controllers\Admin\System
 * @author wisnuwidi@canvastack.com - 2022
 * @copyright wisnuwidi
 * 
 * @security CRITICAL - All methods validate CSRF tokens and sanitize inputs
 * @version 2.0.0 - Security hardened with Core functions
 */
class AjaxController extends Controller {
	
	/**
	 * Database connection name for AJAX operations
	 * 
	 * @var string|null
	 */
	private $ajaxConnection = null;
	
	/**
	 * DataTables instance
	 * 
	 * @var Datatables|null
	 */
	private $datatables = null;
	
	/**
	 * Charts instance
	 * 
	 * @var Charts|null
	 */
	private $charts = null;
	
	/**
	 * Static property for AJAX URL
	 * 
	 * @var string|null
	 */
	public static $ajaxUrli;
	
	/**
	 * Filter data for DataTables
	 * 
	 * @var array
	 */
	public $filter_datatables = [];
	
	/**
	 * Constructor
	 * 
	 * @param string|null $connection Database connection name
	 */
	public function __construct(?string $connection = null) {
		parent::__construct();
		
		if (!empty($connection)) {
			$this->ajaxConnection = $connection;
		}
	}
	
	/**
	 * Generate AJAX POST URL with CSRF token
	 * 
	 * @param string $init_post Initialize post key
	 * @param bool $return_data Whether to return the URL
	 * @return string|null AJAX URL or null
	 * 
	 * @security Includes CSRF token in URL for AJAX requests
	 */
	public static function urli(string $init_post = 'AjaxPosF', bool $return_data = false): ?string {
		$current_url = route('ajax.post');
		
		if ('filterDataTables' === $init_post) {
			$urlset = [$init_post => 'true'];
		} else {
			$urlset = [$init_post => 'true', '_token' => csrf_token()];
		}
		
		$uri = [];
		foreach ($urlset as $fieldurl => $urlvalue) {
			$uri[] = "{$fieldurl}={$urlvalue}";
		}
		
		self::$ajaxUrli = $current_url . '?' . implode('&', $uri);
		
		if (true === $return_data) {
			return self::$ajaxUrli;
		}
		
		return null;
	}
	
	/**
	 * Main AJAX POST handler with CSRF protection
	 * 
	 * Routes requests to appropriate handlers based on query parameters.
	 * 
	 * @param Request $request HTTP request object
	 * @return JsonResponse JSON response
	 * 
	 * @throws CSRFException If CSRF validation fails
	 * 
	 * @security CRITICAL - Validates CSRF token before processing
	 */
	public function post(Request $request): JsonResponse {
		try {
			// CRITICAL: Validate CSRF token using Core function
			if (!canvastack_controller_validate_csrf($request)) {
				canvastack_controller_log_security_event(
					'csrf_validation_failed',
					'CSRF token validation failed for AJAX request',
					[
						'url' => $request->fullUrl(),
						'ip' => $request->ip(),
					]
				);
				
				return $this->errorResponse('CSRF token validation failed', 419);
			}
			
			// Route to appropriate handler based on query parameters
			if ($request->has('AjaxPosF')) {
				return $this->post_filters($request);
			} elseif ($request->has('canvastackHostConn')) {
				return $this->getHostConnections($request);
			} elseif ($request->has('canvastackHostProcess')) {
				return $this->getHostProcess($request);
			} elseif ($request->has('filterDataTables')) {
				return $this->initFilterDatatables($request);
			} elseif ($request->has('filterCharts')) {
				return $this->initFilterCharts($request);
			}
			
			return $this->errorResponse('Invalid AJAX request', 400);
			
		} catch (CSRFException $e) {
			return $this->errorResponse($e->getUserMessage(), 419);
		} catch (SQLInjectionAttemptException $e) {
			// Don't expose SQL injection details to user
			return $this->errorResponse('Invalid request parameters', 400);
		} catch (ControllerValidationException $e) {
			return $this->errorResponse($e->getUserMessage(), 400);
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'ajax_exception',
				'Unhandled exception in AJAX handler: ' . $e->getMessage(),
				[
					'exception' => get_class($e),
					'trace' => $e->getTraceAsString()
				]
			);
			
			return $this->errorResponse('Internal server error', 500);
		}
	}
	
	/**
	 * Handle data transfer between database connections
	 * 
	 * Transfers data from source table to target table with validation
	 * and transaction support.
	 * 
	 * @param Request $request HTTP request with connection and table info
	 * @return JsonResponse Transfer result with counts
	 * 
	 * @throws SQLInjectionAttemptException If table names are invalid
	 * @throws ControllerValidationException If validation fails
	 * 
	 * @security CRITICAL - Validates table names to prevent SQL injection
	 * @performance Uses chunking for large datasets
	 */
	private function getHostProcess(Request $request): JsonResponse {
		try {
			// Validate input parameters
			$validated = $request->validate([
				'source_connection_name' => 'required|string|max:255',
				'source_table_name' => 'required|string|max:255',
				'target_connection_name' => 'required|string|max:255',
				'target_table_name' => 'required|string|max:255',
			]);
			
			$sconnect = $validated['source_connection_name'];
			$stable = $validated['source_table_name'];
			$tconnect = $validated['target_connection_name'];
			$ttable = $validated['target_table_name'];
			
			// Validate connection names against configured connections
			$this->validateConnectionName($sconnect);
			$this->validateConnectionName($tconnect);
			
			// CRITICAL: Validate table names to prevent SQL injection
			$this->validateTableName($stable, $sconnect);
			$this->validateTableName($ttable, $tconnect);
			
			// Get connections
			$sourceConnection = DB::connection($sconnect);
			$targetConnection = DB::connection($tconnect);
			
			// Get source count
			$sourceCount = $sourceConnection->table($stable)->count();
			
			// Begin transaction
			$targetConnection->beginTransaction();
			
			try {
				// Truncate target table
				$targetConnection->table($ttable)->truncate();
				
				// Transfer data in chunks for memory efficiency
				$chunkSize = config('canvastack.controller.ajax.chunk_size', 1000);
				$transferred = 0;
				
				$sourceConnection->table($stable)
					->orderBy('id')
					->chunk($chunkSize, function ($records) use ($targetConnection, $ttable, &$transferred) {
						$data = $records->map(fn($record) => (array) $record)->toArray();
						$targetConnection->table($ttable)->insert($data);
						$transferred += count($data);
					});
				
				// Commit transaction
				$targetConnection->commit();
				
				// Get target count for verification
				$targetCount = $targetConnection->table($ttable)->count();
				
				// Log successful transfer
				canvastack_controller_log_security_event(
					'data_transfer_completed',
					'Data transfer completed successfully',
					[
						'source' => ['connection' => $sconnect, 'table' => $stable, 'count' => $sourceCount],
						'target' => ['connection' => $tconnect, 'table' => $ttable, 'count' => $targetCount],
					]
				);
				
				return $this->successResponse([
					'counts' => [
						'source' => $sourceCount,
						'target' => $targetCount,
					]
				]);
				
			} catch (\Exception $e) {
				// Rollback on error
				$targetConnection->rollBack();
				throw $e;
			}
			
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->errorResponse('Invalid input parameters', 400, [
				'errors' => $e->errors()
			]);
		} catch (ControllerValidationException $e) {
			return $this->errorResponse($e->getUserMessage(), 400);
		} catch (SQLInjectionAttemptException $e) {
			return $this->errorResponse('Invalid request', 400);
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'data_transfer_failed',
				'Data transfer failed: ' . $e->getMessage(),
				[
					'exception' => get_class($e),
					'message' => $e->getMessage()
				]
			);
			
			return $this->errorResponse('Data transfer failed', 500);
		}
	}
	
	/**
	 * Get host database connections and tables
	 * 
	 * Returns list of tables for a specified database connection.
	 * 
	 * @param Request $request HTTP request with connection info
	 * @return JsonResponse List of tables
	 * 
	 * @security Validates connection names and escapes output
	 * @performance Caches table lists
	 */
	private function getHostConnections(Request $request): JsonResponse {
		try {
			$connection_sources = canvastack_config('sources', 'connections');
			
			// Get selected value if provided
			$selected = null;
			if ($request->has('s')) {
				try {
					$selected = decrypt($request->get('s'));
				} catch (\Exception $e) {
					canvastack_controller_log_security_event(
						'parameter_decryption_failed',
						'Failed to decrypt selected parameter',
						['error' => $e->getMessage()]
					);
				}
			}
			
			// Get connection name from POST
			$connectionValue = $request->input('connection');
			if (empty($connectionValue)) {
				return $this->errorResponse('Connection name required', 400);
			}
			
			// Validate connection exists in sources
			if (!isset($connection_sources[$connectionValue])) {
				return $this->errorResponse('Invalid connection', 400);
			}
			
			$connectionName = $connection_sources[$connectionValue]['connection_name'];
			
			// Validate connection name
			$this->validateConnectionName($connectionName);
			
			// Get tables with caching
			$cacheKey = "ajax_tables_{$connectionName}";
			$cacheTTL = config('canvastack.controller.ajax.cache_ttl', 3600);
			
			$allTables = Cache::remember($cacheKey, $cacheTTL, function () use ($connectionName) {
				return canvastack_get_all_tables($connectionName);
			});
			
			// Convert Collection to array if needed
			if ($allTables instanceof \Illuminate\Support\Collection) {
				$allTables = $allTables->toArray();
			}
			
			// Build result with XSS protection
			$result = ['data' => []];
			
			if (!empty($allTables)) {
				foreach ($allTables as $tablename) {
					$safeTablename = htmlspecialchars($tablename, ENT_QUOTES, 'UTF-8');
					$label = ucwords(str_replace('_', ' ', $safeTablename));
					$result['data'][$safeTablename] = $label;
				}
			}
			
			if ($selected !== null) {
				$result['selected'] = htmlspecialchars($selected, ENT_QUOTES, 'UTF-8');
			}
			
			return $this->successResponse($result);
			
		} catch (ControllerValidationException $e) {
			return $this->errorResponse($e->getUserMessage(), 400);
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'get_host_connections_failed',
				'Failed to get host connections: ' . $e->getMessage(),
				['exception' => get_class($e)]
			);
			
			return $this->errorResponse('Failed to retrieve connections', 500);
		}
	}
	
	/**
	 * Handle AJAX filter requests for dynamic dropdowns
	 * 
	 * Filters data based on provided criteria and returns formatted results.
	 * 
	 * @param Request $request HTTP request with filter parameters
	 * @return JsonResponse Filtered data
	 * 
	 * @throws ControllerValidationException If validation fails
	 * @throws SQLInjectionAttemptException If SQL injection detected
	 * 
	 * @security CRITICAL - Validates all parameters and uses query builder
	 */
	private function post_filters(Request $request): JsonResponse {
		try {
			// Read parameters from POST body (not GET query)
			$postData = $request->all();
			
			// Decrypt and validate parameters from POST
			$label = $this->getDecryptedParameterFromPost($postData, 'l', 'label');
			$value = $this->getDecryptedParameterFromPost($postData, 'v', 'value');
			$selected = $this->getDecryptedParameterFromPost($postData, 's', 'selected', false);
			
			// Get query from dynamic parameter (any parameter except l, v, s, _token)
			$query = null;
			$queryKey = null;
			
			foreach ($postData as $key => $val) {
				if (!in_array($key, ['l', 'v', 's', '_token'])) {
					// Check if this is a filter field (not encrypted) or query parameter (encrypted)
					// Filter fields are simple values, query parameters are long encrypted strings
					if (strlen($val) > 100) {
						// This is likely the encrypted query parameter
						$queryKey = $key;
						$query = $this->decryptParameter($val, 'query');
						break;
					}
				}
			}
			
			// Validate parameters - basic validation
			if (empty($label) || strlen($label) > 255) {
				throw new ControllerValidationException("Invalid label parameter");
			}
			
			if (empty($value) || strlen($value) > 255) {
				throw new ControllerValidationException("Invalid value parameter");
			}
			
			// Validate field names - only alphanumeric and underscore allowed
			if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $label)) {
				throw new ControllerValidationException("Invalid label field name");
			}
			
			if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
				throw new ControllerValidationException("Invalid value field name");
			}
			
			// Get filter field and value from POST (exclude encrypted parameters)
			$filterField = null;
			$filterValue = null;
			
			foreach ($postData as $key => $val) {
				if (!in_array($key, ['l', 'v', 's', '_token', $queryKey])) {
					$filterField = $key;
					$filterValue = $val;
					break;
				}
			}
			
			if (empty($filterField)) {
				return $this->errorResponse('Filter parameters required', 400);
			}
			
			// Validate filter field name format (prevent SQL injection)
			if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $filterField)) {
				throw new ControllerValidationException("Invalid filter field name");
			}
			
			// SPECIAL CASE: Use Eloquent for known sync patterns
			// This is more efficient and maintainable than raw SQL
			if ($filterField === 'group_id' && $value === 'route_path' && $label === 'module_name') {
				$options = \Canvastack\Canvastack\Models\Admin\System\Group::getFirstRouteOptions($filterValue);
				
				// Build result with XSS protection
				$result = [];
				foreach ($options as $optionValue => $optionLabel) {
					$safeValue = htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8');
					$safeLabel = htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8');
					$result[$safeValue] = $safeLabel;
				}
				
				$response = ['data' => $result];
				if ($selected !== null) {
					$response['selected'] = htmlspecialchars($selected, ENT_QUOTES, 'UTF-8');
				}
				
				// Use successResponse for consistent format
				return $this->successResponse($response);
			}
			
			// FALLBACK: Raw SQL for other queries (backward compatibility)
			if (empty($query)) {
				throw new ControllerValidationException("Missing query parameter");
			}
			
			// Validate query structure
			$this->validateQueryStructure($query);
			
			// Extract table name from query
			$tableName = $this->extractTableName($query);
			
			// Validate table name
			$this->validateTableName($tableName, $this->ajaxConnection);
			
			// Execute query with parameter binding (secure)
			$queryData = DB::connection($this->ajaxConnection)
				->table($tableName)
				->select([$value, $label])
				->where($filterField, '=', $filterValue)
				->orderBy($filterField, 'desc')
				->get();
			
			// Build result with XSS protection
			$result = [];
			
			foreach ($queryData as $row) {
				$valueField = htmlspecialchars($row->{$value}, ENT_QUOTES, 'UTF-8');
				$labelField = htmlspecialchars($row->{$label}, ENT_QUOTES, 'UTF-8');
				$result[$valueField] = $labelField;
			}
			
			$response = ['data' => $result];
			if ($selected !== null) {
				$response['selected'] = htmlspecialchars($selected, ENT_QUOTES, 'UTF-8');
			}
			
			// Log successful request
			canvastack_controller_log_security_event(
				'ajax_filter_completed',
				'AJAX filter request completed',
				[
					'table' => $tableName,
					'filter_field' => $filterField,
					'result_count' => count($result),
				]
			);
			
			return $this->successResponse($response);
			
		} catch (ControllerValidationException $e) {
			// Log validation error for debugging
			canvastack_controller_log_security_event(
				'ajax_validation_error',
				'Validation error in post_filters: ' . $e->getMessage(),
				[
					'message' => $e->getMessage(),
					'request_params' => $request->all()
				]
			);
			
			return $this->errorResponse($e->getUserMessage(), 400);
		} catch (SQLInjectionAttemptException $e) {
			return $this->errorResponse('Invalid request', 400);
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'ajax_filter_exception',
				'Exception in filter handler: ' . $e->getMessage(),
				['exception' => get_class($e)]
			);
			
			return $this->errorResponse('Filter request failed', 500);
		}
	}
	
	/**
	 * Initialize DataTables class
	 * 
	 * @return void
	 */
	private function datatableClass(): void {
		if ($this->datatables === null) {
			$this->datatables = new Datatables();
		}
	}
	
	/**
	 * Filter DataTable with request
	 * 
	 * @param Request $request HTTP request
	 * @return self
	 */
	protected function filterDataTable(Request $request): self {
		$this->datatableClass();
		$this->filter_datatables = $this->datatables->filter_datatable($request);
		
		return $this;
	}
	
	/**
	 * Initialize DataTables filtering
	 * 
	 * @param Request $request HTTP request
	 * @return JsonResponse|mixed DataTables response
	 */
	private function initFilterDatatables(Request $request): JsonResponse {
		try {
			if ($request->has('filterDataTables')) {
				$this->datatableClass();
				
				// Pass GET and POST data to DataTables component
				$result = $this->datatables->init_filter_datatables(
					$request->query->all(),
					$request->all(),
					$this->ajaxConnection
				);
				
				// Convert Collection to JsonResponse if needed
				if ($result instanceof \Illuminate\Support\Collection) {
					return response()->json($result->toArray());
				}
				
				// If already JsonResponse, return as is
				if ($result instanceof JsonResponse) {
					return $result;
				}
				
				// Otherwise wrap in JsonResponse
				return response()->json($result);
			}
			
			return $this->errorResponse('Invalid DataTables request', 400);
			
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'datatables_filter_exception',
				'Exception in DataTables filter: ' . $e->getMessage(),
				['exception' => get_class($e)]
			);
			
			return $this->errorResponse('DataTables filter failed', 500);
		}
	}
	
	/**
	 * Initialize Charts class
	 * 
	 * @return void
	 */
	private function chartClass(): void {
		if ($this->charts === null) {
			$this->charts = new Charts();
		}
	}
	
	/**
	 * Initialize Charts filtering
	 * 
	 * @param Request $request HTTP request
	 * @return JsonResponse|mixed Charts response
	 */
	private function initFilterCharts(Request $request): JsonResponse {
		try {
			if ($request->has('filterCharts')) {
				$this->chartClass();
				
				// Pass GET and POST data to Charts component
				$result = $this->charts->init_filter_charts(
					$request->query->all(),
					$request->all(),
					$this->ajaxConnection
				);
				
				// Convert Collection to JsonResponse if needed
				if ($result instanceof \Illuminate\Support\Collection) {
					return response()->json($result->toArray());
				}
				
				// If already JsonResponse, return as is
				if ($result instanceof JsonResponse) {
					return $result;
				}
				
				// Otherwise wrap in JsonResponse
				return response()->json($result);
			}
			
			return $this->errorResponse('Invalid Charts request', 400);
			
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'charts_filter_exception',
				'Exception in Charts filter: ' . $e->getMessage(),
				['exception' => get_class($e)]
			);
			
			return $this->errorResponse('Charts filter failed', 500);
		}
	}
	
	/**
	 * Export data to CSV
	 * 
	 * @return JsonResponse|Response Export response
	 */
	public function export() {
		try {
			$export = new Export();
			$result = $export->csv('assets/resources/exports');
			
			// If result is null, return error response
			if ($result === null) {
				return response()->json([
					'error' => true,
					'message' => 'Export request is invalid or missing required parameters'
				], 400);
			}
			
			// Return the export result
			return response($result)->header('Content-Type', 'application/json');
			
		} catch (\Exception $e) {
			Log::error('Export controller error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			
			return response()->json([
				'error' => true,
				'message' => 'Export failed: ' . $e->getMessage()
			], 500);
		}
	}

	
	// ========================================================================
	// PRIVATE HELPER METHODS - Security & Validation
	// ========================================================================
	
	/**
	 * Get and decrypt parameter from POST data array
	 * 
	 * @param array $postData POST data array
	 * @param string $key Parameter key
	 * @param string $name Parameter name for logging
	 * @param bool $required Whether parameter is required
	 * @return string|null Decrypted parameter value
	 * 
	 * @throws ControllerValidationException If decryption fails or required parameter missing
	 */
	private function getDecryptedParameterFromPost(array $postData, string $key, string $name, bool $required = true): ?string {
		if (!isset($postData[$key]) || empty($postData[$key])) {
			if ($required) {
				throw new ControllerValidationException("Missing required parameter: {$name}");
			}
			return null;
		}
		
		// Laravel automatically URL decodes POST data, so we can use it directly
		$encrypted = $postData[$key];
		
		try {
			return $this->decryptParameter($encrypted, $name);
		} catch (ControllerValidationException $e) {
			// If not required and decryption fails, return null
			if (!$required) {
				return null;
			}
			// If required, re-throw the exception
			throw $e;
		}
	}
	
	/**
	 * Decrypt parameter value with integrity verification
	 * 
	 * @param string $encrypted Encrypted value
	 * @param string $name Parameter name for logging
	 * @return string Decrypted value
	 * 
	 * @throws ControllerValidationException If decryption fails
	 */
	private function decryptParameter(string $encrypted, string $name): string {
		try {
			$decrypted = null;
			
			// Check if data is still URL encoded (shouldn't happen, but handle it)
			if (strpos($encrypted, '%') !== false) {
				$encrypted = urldecode($encrypted);
			}
			
			// Verify integrity if signature present (format: encrypted::signature)
			if (str_contains($encrypted, '::')) {
				[$encryptedData, $providedSignature] = explode('::', $encrypted, 2);
				
				// Verify HMAC signature
				$key = config('app.key');
				$expectedSignature = hash_hmac('sha256', $encryptedData, $key);
				
				if (!hash_equals($expectedSignature, $providedSignature)) {
					canvastack_controller_log_security_event(
						'integrity_check_failed',
						"Integrity check failed for parameter: {$name}",
						['parameter' => $name]
					);
					
					throw new ControllerValidationException("Data integrity check failed");
				}
				
				// Decrypt the verified data
				$decrypted = decrypt($encryptedData);
			} else {
				// Fallback to direct decryption for backward compatibility
				$decrypted = decrypt($encrypted);
			}
			
			// Check if decryption returned null or empty
			if ($decrypted === null || $decrypted === '' || $decrypted === false) {
				throw new \Exception("Decryption returned null, empty string, or false");
			}
			
			// Ensure we return a string
			return (string) $decrypted;
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'parameter_decryption_failed',
				"Failed to decrypt parameter: {$name}",
				['parameter' => $name, 'error' => $e->getMessage(), 'encrypted_length' => strlen($encrypted)]
			);
			
			throw new ControllerValidationException("Invalid encrypted parameter: {$name}");
		}
	}
	
	/**
	 * Validate connection name against configured connections
	 * 
	 * @param string $connectionName Connection name to validate
	 * @return void
	 * 
	 * @throws ControllerValidationException If connection is invalid
	 * 
	 * @security CRITICAL - Prevents unauthorized database access
	 */
	private function validateConnectionName(string $connectionName): void {
		$allowedConnections = array_keys(config('database.connections'));
		
		if (!in_array($connectionName, $allowedConnections)) {
			canvastack_controller_log_security_event(
				'invalid_connection_attempt',
				'Attempt to use invalid database connection',
				['connection' => $connectionName, 'allowed' => $allowedConnections]
			);
			
			throw new ControllerValidationException(
				"Invalid database connection",
				['connection' => $connectionName]
			);
		}
	}
	
	/**
	 * Validate table name against database schema
	 * 
	 * @param string $tableName Table name to validate
	 * @param string|null $connection Database connection name
	 * @return void
	 * 
	 * @throws SQLInjectionAttemptException If table name is invalid
	 * 
	 * @security CRITICAL - Prevents SQL injection via table names
	 */
	private function validateTableName(string $tableName, ?string $connection = null): void {
		// Get all tables for the connection
		$allTables = canvastack_get_all_tables($connection);
		
		// Convert Collection to array if needed
		if ($allTables instanceof \Illuminate\Support\Collection) {
			$allTables = $allTables->toArray();
		}
		
		if (!in_array($tableName, $allTables)) {
			canvastack_controller_log_security_event(
				'invalid_table_name',
				'Attempt to access invalid table',
				['table' => $tableName, 'connection' => $connection]
			);
			
			throw new SQLInjectionAttemptException(
				"Invalid table name",
				['table' => $tableName, 'connection' => $connection]
			);
		}
	}
	
	/**
	 * Validate column name against table schema
	 * 
	 * @param string $columnName Column name to validate
	 * @param string $tableName Table name
	 * @param string|null $connection Database connection name
	 * @return void
	 * 
	 * @throws SQLInjectionAttemptException If column name is invalid
	 * 
	 * @security CRITICAL - Prevents SQL injection via column names
	 */
	private function validateColumnName(string $columnName, string $tableName, ?string $connection = null): void {
		try {
			// Get table columns
			$columns = DB::connection($connection)
				->getSchemaBuilder()
				->getColumnListing($tableName);
			
			if (!in_array($columnName, $columns)) {
				canvastack_controller_log_security_event(
					'invalid_column_name',
					'Attempt to access invalid column',
					['column' => $columnName, 'table' => $tableName, 'connection' => $connection]
				);
				
				throw new SQLInjectionAttemptException(
					"Invalid column name",
					['column' => $columnName, 'table' => $tableName]
				);
			}
		} catch (SQLInjectionAttemptException $e) {
			throw $e;
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'column_validation_error',
				'Error validating column name: ' . $e->getMessage(),
				['column' => $columnName, 'table' => $tableName]
			);
			
			throw new ControllerValidationException("Failed to validate column name");
		}
	}
	
	/**
	 * Validate query structure to prevent SQL injection
	 * 
	 * @param string $query SQL query to validate
	 * @return void
	 * 
	 * @throws SQLInjectionAttemptException If query is invalid
	 * 
	 * @security CRITICAL - Prevents SQL injection via query manipulation
	 */
	private function validateQueryStructure(string $query): void {
		// Check query length
		$maxLength = config('canvastack.controller.ajax.max_query_length', 1000);
		if (strlen($query) > $maxLength) {
			canvastack_controller_log_security_event(
				'query_too_long',
				'Query exceeds maximum length',
				['length' => strlen($query), 'max' => $maxLength]
			);
			
			throw new SQLInjectionAttemptException(
				"Query too long",
				['length' => strlen($query)]
			);
		}
		
		// Check for dangerous SQL keywords
		$dangerousKeywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE', 'EXEC', 'EXECUTE'];
		$upperQuery = strtoupper($query);
		
		foreach ($dangerousKeywords as $keyword) {
			if (strpos($upperQuery, $keyword) !== false) {
				canvastack_controller_log_security_event(
					'dangerous_sql_keyword',
					'Dangerous SQL keyword detected in query',
					['keyword' => $keyword, 'query' => substr($query, 0, 100)]
				);
				
				throw new SQLInjectionAttemptException(
					"Dangerous SQL keyword detected",
					['keyword' => $keyword]
				);
			}
		}
		
		// Check for SQL comments
		if (strpos($query, '--') !== false || strpos($query, '/*') !== false) {
			canvastack_controller_log_security_event(
				'sql_comment_detected',
				'SQL comment detected in query',
				['query' => substr($query, 0, 100)]
			);
			
			throw new SQLInjectionAttemptException("SQL comments not allowed");
		}
	}
	
	/**
	 * Extract table name from SELECT query
	 * 
	 * @param string $query SQL query
	 * @return string Table name
	 * 
	 * @throws ControllerValidationException If table name cannot be extracted
	 */
	private function extractTableName(string $query): string {
		// Match: SELECT ... FROM table_name or SELECT ... FROM `table_name`
		if (preg_match('/FROM\s+`?(\w+)`?/i', $query, $matches)) {
			return $matches[1];
		}
		
		canvastack_controller_log_security_event(
			'table_extraction_failed',
			'Cannot extract table name from query',
			['query' => substr($query, 0, 100)]
		);
		
		throw new ControllerValidationException("Cannot extract table name from query");
	}
	
	/**
	 * Get and decrypt parameter from request
	 * 
	 * @param Request $request HTTP request
	 * @param string $key Parameter key
	 * @param string $name Parameter name for logging
	 * @param bool $required Whether parameter is required
	 * @return string|null Decrypted parameter value
	 * 
	 * @throws ControllerValidationException If decryption fails or required parameter missing
	 */
	private function getDecryptedParameter(Request $request, string $key, string $name, bool $required = true): ?string {
		if (!$request->has($key)) {
			if ($required) {
				throw new ControllerValidationException("Missing required parameter: {$name}");
			}
			return null;
		}
		
		try {
			$encrypted = $request->get($key);
			
			// Verify integrity if signature present (format: encrypted::signature)
			if (str_contains($encrypted, '::')) {
				[$encryptedData, $providedSignature] = explode('::', $encrypted, 2);
				
				// Verify HMAC signature
				$key = config('app.key');
				$expectedSignature = hash_hmac('sha256', $encryptedData, $key);
				
				if (!hash_equals($expectedSignature, $providedSignature)) {
					canvastack_controller_log_security_event(
						'integrity_check_failed',
						"Integrity check failed for parameter: {$name}",
						['key' => $key]
					);
					
					throw new ControllerValidationException("Data integrity check failed");
				}
				
				// Decrypt the verified data
				return decrypt($encryptedData);
			}
			
			// Fallback to direct decryption for backward compatibility
			return decrypt($encrypted);
		} catch (\Exception $e) {
			canvastack_controller_log_security_event(
				'parameter_decryption_failed',
				"Failed to decrypt parameter: {$name}",
				['key' => $key, 'error' => $e->getMessage()]
			);
			
			throw new ControllerValidationException("Invalid encrypted parameter: {$name}");
		}
	}
	
	/**
	 * Return successful JSON response
	 * 
	 * @param array $data Response data
	 * @param int $code HTTP status code
	 * @param bool $asString Return as JSON string for backward compatibility
	 * @return JsonResponse|string
	 */
	private function successResponse(array $data, int $code = 200, bool $asString = false) {
		$response = [
			'success' => true,
			'data' => $data
		];
		
		if ($asString) {
			// Return plain JSON string for backward compatibility with old JavaScript
			return json_encode($response);
		}
		
		return response()->json($response, $code);
	}
	
	/**
	 * Return error JSON response with logging
	 * 
	 * @param string $message Error message
	 * @param int $code HTTP status code
	 * @param array $context Additional context for logging
	 * @return JsonResponse
	 * 
	 * @security Logs all errors for security audit
	 */
	private function errorResponse(string $message, int $code = 400, array $context = []): JsonResponse {
		canvastack_controller_log_security_event(
			'ajax_error_response',
			$message,
			array_merge(['code' => $code], $context)
		);
		
		return response()->json([
			'success' => false,
			'error' => $message
		], $code);
	}
}

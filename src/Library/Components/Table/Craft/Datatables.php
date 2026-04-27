<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Canvastack\Canvastack\Controllers\Core\Craft\Includes\Privileges;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidPaginationException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidSortException;
use Canvastack\Canvastack\Library\Exceptions\Table\SQLInjectionAttemptException;
use Canvastack\Canvastack\Library\Exceptions\Table\QueryTimeoutException;
use Canvastack\Canvastack\Library\Exceptions\Table\RelationshipException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidTableNameException;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;
use Yajra\DataTables\DataTables as DataTable;
use Illuminate\Support\Facades\DB;

/**
 * Datatables Component - Dynamic DataTables Generator
 * 
 * Generates server-side DataTables with advanced features including:
 * - Dynamic model initialization from configuration
 * - Automatic relationship handling (joins and eager loading)
 * - Advanced filtering with multiple conditions
 * - Image column detection and rendering
 * - Action button generation with privilege checking
 * - Formula and data formatting support
 * - Comprehensive security validation
 * - Performance optimization (eager loading, caching)
 * 
 * SECURITY FEATURES:
 * - Table/connection whitelist validation
 * - Input validation for all public methods
 * - SQL injection prevention via query builder
 * - XSS protection for all outputs
 * - Path traversal protection
 * - Comprehensive error handling with logging
 * 
 * PERFORMANCE FEATURES:
 * - Eager loading for Eloquent relations (prevents N+1)
 * - Image validation caching
 * - Optimized query building
 * 
 * USAGE EXAMPLE:
 * ```php
 * $datatables = new Datatables();
 * 
 * // Process DataTables request
 * $result = $datatables->process(
 *     $request->all(),           // Method parameters (difta, start, length, etc.)
 *     $tableConfig,              // Table configuration object
 *     $filters,                  // Additional filters (optional)
 *     $filterPage                // Page filters (optional)
 * );
 * 
 * // Initialize filter dropdown
 * $options = $datatables->init_filter_datatables(
 *     $_GET,                     // GET parameters
 *     $_POST,                    // POST parameters
 *     'mysql'                    // Connection name (optional)
 * );
 * ```
 * Created on 21 Apr 2021
 * Time Created : 12:45:06
 * 
 * @package    Canvastack\Canvastack\Library\Components\Table\Craft
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @version    2.0.0 (with Phase 1 Security + Phase 2 Performance)
 * @since      21 Apr 2021
 * 
 * @see        \Yajra\DataTables\DataTables
 * @see        \Canvastack\Canvastack\Models\Admin\System\DynamicTables
 */
class Datatables {
	use Privileges;
	
	// Constants - Configuration
	private const DEFAULT_LIMIT_START  = TableConstants::DEFAULT_START;
	private const DEFAULT_LIMIT_LENGTH = TableConstants::DEFAULT_PAGE_LENGTH;
	private const BLACKLIST_FIELDS     = ['password', TableConstants::COL_ACTION, TableConstants::COL_NO];
	private const BLACKLIST_WITH_ID    = ['password', TableConstants::COL_ACTION, TableConstants::COL_NO, TableConstants::COL_ID];
	private const DEFAULT_ACTIONS      = [TableConstants::ACTION_VIEW, TableConstants::ACTION_INSERT, TableConstants::ACTION_EDIT, TableConstants::ACTION_DELETE];
	private const AJAX_RESERVED_PARAMS = ['renderDataTables', 'draw', 'columns', 'order', 'start', 'length', 'search', 'difta', '_token', '_'];

	// Constants - Memory Management (Requirement 6.1)
	/**
	 * Number of rows processed per chunk when iterating large datasets.
	 * Keeps memory usage bounded by processing data in batches rather than
	 * loading the entire result set at once.
	 *
	 * @performance Memory Management - chunk size for large dataset processing
	 */
	private const CHUNK_SIZE = 500;

	/**
	 * Row count threshold above which chunking is activated.
	 * Datasets with more than this many rows will be processed in chunks
	 * of CHUNK_SIZE to prevent memory exhaustion.
	 *
	 * @performance Memory Management - threshold for enabling chunked processing
	 */
	private const LARGE_DATASET_THRESHOLD = 1000;

	// Constants - Magic Values
	private const ADMIN_ROLE_GROUP     = 1;
	private const IMAGE_ALT_PREFIX     = 'imgsrc::';
	private const THUMBNAIL_PREFIX     = 'tnail_';
	private const THUMBNAIL_FOLDER     = 'thumb';
	private const NULL_CONDITION       = '#null';
	
	/**
	 * Dangerous HTML event handler attribute prefixes/names that must be blocked.
	 * These can be used to inject JavaScript via attribute injection attacks.
	 *
	 * @security XSS Prevention - blocks event handler injection
	 * @var string[]
	 */
	private const DANGEROUS_ATTR_PATTERNS = [
		'on',         // Matches all on* handlers (onclick, onload, onerror, etc.)
		'formaction',
		'srcdoc',
		'xlink:href',
	];
	
	// Properties
	public  $filter_model  = [];
	private $image_checker = ['jpg', 'jpeg', 'png', 'gif'];
	
	// PERFORMANCE: L1 in-memory cache for image validation (per-request)
	private $imageValidationCache = [];

	// PERFORMANCE: L1 in-memory cache for table name validation (per-request)
	private $tableNameValidationCache = [];
	
	// PERFORMANCE: Query performance metrics
	private $queryMetrics = [];
	
	// PERFORMANCE: Slow query threshold in milliseconds
	private const SLOW_QUERY_THRESHOLD_MS = 1000;

	// PERFORMANCE: Cache TTL for image validation (from config, default 1 hour)
	private function getImageValidationCacheTtl(): int {
		return (int) config('canvastack.cache.validation.ttl', 3600);
	}
	/**
	 * Check if cache should be used based on development settings
	 *
	 * @param string $cacheType Type of cache (validation, relationships, etc.)
	 * @return bool
	 */
	private function shouldUseCache(string $cacheType = 'validation'): bool
	{
	    // Disable cache in development if configured
	    if (config('canvastack.cache.development.disable_in_dev', false) && app()->environment('local')) {
	        if (config('canvastack.cache.development.log_operations', false)) {
	            \Log::debug('Cache disabled in development environment', ['type' => $cacheType]);
	        }
	        return false;
	    }

	    return config("canvastack.cache.{$cacheType}.enabled", true);
	}
	/**
	 * Validate SQL operator against whitelist
	 *
	 * SECURITY: Prevents SQL injection by validating operators against whitelist
	 *
	 * @param string $operator SQL operator to validate
	 * @return string Validated operator
	 * @throws \InvalidArgumentException If operator is not allowed
	 */
	private function validateOperator(string $operator): string
	{
	    if (!config('canvastack.datatables.security.sql_injection_prevention', true)) {
	        return $operator;
	    }

	    $allowedOperators = config('canvastack.datatables.security.allowed_operators', [
	        '=', '!=', '<>', '>', '<', '>=', '<=',
	        'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL',
	    ]);

	    $operator = strtoupper(trim($operator));

	    if (!in_array($operator, $allowedOperators, true)) {
	        if (config('canvastack.datatables.security.log_security_events', true)) {
	            canvastack_table_log_security_event(
	                'invalid_operator',
	                "Invalid SQL operator detected: {$operator}",
	                [
	                    'operator' => $operator,
	                    'allowed' => $allowedOperators,
	                ]
	            );
	        }
	        throw new \InvalidArgumentException("Invalid SQL operator: {$operator}");
	    }

	    return $operator;
	}

	/**
	 * Validate sort direction against whitelist
	 *
	 * SECURITY: Prevents SQL injection via sort direction parameter
	 *
	 * @param string $direction Sort direction (asc/desc)
	 * @return string Validated and normalized direction
	 * @throws \InvalidArgumentException If direction is not allowed
	 */
	private function validateSortDirection(string $direction): string
	{
	    if (!config('canvastack.datatables.security.sql_injection_prevention', true)) {
	        return strtolower(trim($direction));
	    }

	    // Trim whitespace first
	    $direction = trim($direction);
	    
	    $allowed = config('canvastack.datatables.security.allowed_sort_directions', ['asc', 'desc', 'ASC', 'DESC']);

	    if (!in_array($direction, $allowed, true)) {
	        if (config('canvastack.datatables.security.log_security_events', true)) {
	            canvastack_table_log_security_event(
	                'invalid_sort_direction',
	                "Invalid sort direction detected: {$direction}",
	                [
	                    'direction' => $direction,
	                    'allowed' => $allowed,
	                ]
	            );
	        }
	        throw new \InvalidArgumentException("Invalid sort direction: {$direction}");
	    }

	    return strtolower($direction);
	}

	/**
	 * Sanitize search term for XSS protection
	 *
	 * SECURITY: Prevents XSS attacks via search input
	 *
	 * @param string $search Search term to sanitize
	 * @return string Sanitized search term
	 */
	private function sanitizeSearchTerm(string $search): string
	{
	    if (!config('canvastack.datatables.security.input_validation', true)) {
	        return $search;
	    }

	    // Enforce max length
	    $maxLength = config('canvastack.datatables.security.max_search_length', 255);
	    if (strlen($search) > $maxLength) {
	        $search = substr($search, 0, $maxLength);

	        if (config('canvastack.datatables.security.log_security_events', true)) {
	            canvastack_table_log_security_event(
	                'search_term_truncated',
	                "Search term truncated from {$search} characters to {$maxLength}",
	                [
	                    'original_length' => strlen($search),
	                    'max_length' => $maxLength,
	                ]
	            );
	        }
	    }

	    // XSS protection
	    if (config('canvastack.datatables.security.xss_protection', true)) {
	        $search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
	    }

	    return $search;
	}
	
	/**
	 * Process search term with wildcard support
	 * 
	 * Phase 3: Advanced Search
	 * - Supports wildcard search (*, ?)
	 * - Supports partial matching
	 * 
	 * @param string $search Search term
	 * @return string Processed search term
	 */
	private function processSearchTerm(string $search): string
	{
		// Phase 3: Wildcard search support
		if (config('canvastack.datatables.search.wildcard_search', false)) {
			// Convert wildcards to SQL LIKE patterns
			// * = any characters (%)
			// ? = single character (_)
			$search = str_replace(['*', '?'], ['%', '_'], $search);
		}
		
		// Phase 3: Partial matching (add % at start and end if not present)
		if (config('canvastack.datatables.search.partial_matching', true)) {
			// Only add % if not already at start/end
			if (!str_starts_with($search, '%')) {
				$search = '%' . $search;
			}
			if (!str_ends_with($search, '%')) {
				$search = $search . '%';
			}
		}
		
		return $search;
	}
	
	/**
	 * Save search state to session
	 * 
	 * Phase 3: Advanced Search - Persist search state
	 * 
	 * @param string $tableName Table name
	 * @param string $searchTerm Search term
	 * @return void
	 */
	private function saveSearchState(string $tableName, string $searchTerm): void
	{
		if (!config('canvastack.datatables.search.persist_search_state', false)) {
			return;
		}
		
		$sessionKey = 'datatables_search_' . $tableName;
		session([$sessionKey => $searchTerm]);
		
		// Phase 3: Save to search history
		if (config('canvastack.datatables.search.search_history', false)) {
			$historyKey = 'datatables_search_history_' . $tableName;
			$history = session($historyKey, []);
			
			// Add to history if not empty and not duplicate
			if (!empty($searchTerm) && !in_array($searchTerm, $history)) {
				array_unshift($history, $searchTerm);
				
				// Limit history size
				$maxHistory = config('canvastack.datatables.search.max_search_history', 10);
				$history = array_slice($history, 0, $maxHistory);
				
				session([$historyKey => $history]);
			}
		}
	}
	
	/**
	 * Get saved search state from session
	 * 
	 * Phase 3: Advanced Search - Restore search state
	 * 
	 * @param string $tableName Table name
	 * @return string|null Saved search term
	 */
	private function getSavedSearchState(string $tableName): ?string
	{
		if (!config('canvastack.datatables.search.persist_search_state', false)) {
			return null;
		}
		
		$sessionKey = 'datatables_search_' . $tableName;
		return session($sessionKey);
	}

	/**
	 * Validate column name against table schema
	 *
	 * SECURITY: Prevents SQL injection via column name parameter
	 *
	 * @param string $columnName Column name to validate
	 * @param string $tableName Table name for schema lookup
	 * @return string Validated column name
	 * @throws \InvalidArgumentException If column doesn't exist in table
	 */
	private function validateColumnName(string $columnName, string $tableName): string
	{
	    if (!config('canvastack.datatables.security.validate_column_names', true)) {
	        return $columnName;
	    }

	    // Get table columns from cache or database
	    $cacheKey = config('canvastack.cache.prefix', 'canvastack_') .
	                config('canvastack.cache.table_schema.key_prefix', 'table_schema_') .
	                $tableName . '_columns';

	    // Phase 4: Try cache first with monitoring
	    $columns = \Illuminate\Support\Facades\Cache::get($cacheKey);
	    if ($columns !== null) {
	        canvastack_table_cache_monitor('get', $cacheKey, true);
	    } else {
	        canvastack_table_cache_monitor('get', $cacheKey, false);
	        
	        // Fetch from database and cache
	        try {
	            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
	            \Illuminate\Support\Facades\Cache::put($cacheKey, $columns, 3600);
	        } catch (\Exception $e) {
	            $columns = [];
	        }
	    }

	    // Check if column exists
	    if (!empty($columns) && !in_array($columnName, $columns, true)) {
	        if (config('canvastack.datatables.security.log_security_events', true)) {
	            canvastack_table_log_security_event(
	                'invalid_column_name',
	                "Invalid column name detected: {$columnName} for table: {$tableName}",
	                [
	                    'column' => $columnName,
	                    'table' => $tableName,
	                    'valid_columns' => $columns,
	                ]
	            );
	        }
	        throw new \InvalidArgumentException("Invalid column name: {$columnName} for table: {$tableName}");
	    }

	    return $columnName;
	}

	/**
	 * Check current memory usage and log warnings when approaching PHP memory limit.
	 *
	 * Compares current real memory usage against the PHP memory_limit ini setting.
	 * Logs a warning at 75% usage and an error at 90% usage so operators can
	 * investigate before an out-of-memory condition occurs.
	 *
	 * The check is skipped when memory_limit is -1 (unlimited).
	 *
	 * @performance Memory Management (Requirement 6.7) - memory limit warnings
	 *
	 * @param string $context Human-readable label identifying the call-site
	 *                        (e.g. 'Datatables::process', 'Datatables::processRows')
	 * @return void
	 */
	private function checkMemoryUsage(string $context): void {
		$memoryLimit = ini_get('memory_limit');

		// -1 means unlimited; nothing to check
		if ('-1' === $memoryLimit) {
			return;
		}

		$limitBytes   = $this->parseMemoryLimit($memoryLimit);
		$currentUsage = memory_get_usage(true);

		if ($limitBytes <= 0) {
			return;
		}

		$usagePercent = ($currentUsage / $limitBytes) * 100;

		$context_data = [
			'context'       => $context,
			'usage_bytes'   => $currentUsage,
			'limit_bytes'   => $limitBytes,
			'usage_percent' => round($usagePercent, 2),
			'memory_limit'  => $memoryLimit,
		];

		if ($usagePercent >= 90.0) {
			\Log::error('Datatables: Memory usage critical (>=90% of limit)', $context_data);
		} elseif ($usagePercent >= 75.0) {
			\Log::warning('Datatables: Memory usage high (>=75% of limit)', $context_data);
		}
	}

	/**
	 * Convert a PHP memory_limit string to bytes.
	 *
	 * Handles the shorthand suffixes recognised by PHP:
	 * - K / k  → kilobytes (×1024)
	 * - M / m  → megabytes (×1024²)
	 * - G / g  → gigabytes (×1024³)
	 *
	 * @performance Memory Management (Requirement 6.7) - helper for checkMemoryUsage
	 *
	 * @param string $memoryLimit Value returned by ini_get('memory_limit'), e.g. "128M"
	 * @return int Memory limit in bytes, or 0 if the value cannot be parsed
	 */
	private function parseMemoryLimit(string $memoryLimit): int {
		$memoryLimit = trim($memoryLimit);

		if ('' === $memoryLimit || '-1' === $memoryLimit) {
			return 0;
		}

		$value  = (int) $memoryLimit;
		$suffix = strtolower(substr($memoryLimit, -1));

		switch ($suffix) {
			case 'g':
				return $value * 1024 * 1024 * 1024;
			case 'm':
				return $value * 1024 * 1024;
			case 'k':
				return $value * 1024;
			default:
				return $value;
		}
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Determine whether a Throwable represents an out-of-memory condition.
	 *
	 * PHP raises an \Error (not \Exception) when the process exhausts the
	 * memory_limit. The error message contains one of two well-known strings
	 * depending on the PHP version and the allocation that triggered the OOM:
	 *   - "Allowed memory size of … bytes exhausted"
	 *   - "Out of memory (allocated …)"
	 *
	 * @performance Memory Management (Requirement 6.8) - OOM detection helper
	 *
	 * @param \Throwable $e The error or exception to inspect
	 * @return bool True when the Throwable is an out-of-memory error
	 */
	private function isOutOfMemoryError(\Throwable $e): bool {
		$message = $e->getMessage();
		return str_contains($message, 'Allowed memory size')
			|| str_contains($message, 'Out of memory');
	}
	
	/**
	 * Escape a scalar value for safe HTML output.
	 *
	 * @security XSS Prevention - escapes all user-controllable data before HTML output.
	 *           Uses htmlspecialchars with ENT_QUOTES to cover both single and double quotes.
	 *
	 * @param mixed $value Value to escape (non-strings are cast to string)
	 * @return string HTML-escaped string
	 */
	private function escapeData($value): string {
		if (is_null($value)) {
			return '';
		}
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Validate and strip dangerous event-handler attributes from an array.
	 *
	 * @security XSS Prevention - removes on* event handlers and other dangerous
	 *           attribute keys that could be used for attribute-injection attacks.
	 *
	 * @param array $attributes Attribute key/value pairs to validate
	 * @return array Safe attributes with dangerous keys removed
	 */
	private function validateAttributeKeys(array $attributes): array {
		$safe = [];
		foreach ($attributes as $key => $value) {
			$keyLower = strtolower((string) $key);
			
			$isDangerous = false;
			foreach (self::DANGEROUS_ATTR_PATTERNS as $pattern) {
				if ($keyLower === $pattern || str_starts_with($keyLower, $pattern)) {
					$isDangerous = true;
					error_log('[SECURITY] Datatables::validateAttributeKeys(): Blocked dangerous attribute key "' . $key . '"');
					break;
				}
			}
			
			if (!$isDangerous) {
				$safe[$key] = $value;
			}
		}
		return $safe;
	}
	
	/**
	 * Validate table name against whitelist
	 *
	 * @security SQL Injection Prevention - validates table name against a whitelist
	 *           of allowed tables (from config or database schema). Throws
	 *           InvalidArgumentException and logs a warning if the table is not
	 *           in the whitelist, preventing unauthorized table access.
	 *
	 * @param string $table Table name to validate
	 * @return string Validated table name
	 * @throws \InvalidArgumentException If table not in whitelist
	 */
	/**
	 * Validate table name against whitelist with caching
	 *
	 * @security 1.7.3 - Uses canvastack_table_validate_table_name() helper which checks
	 *           both format (alphanumeric/underscore) and database existence.
	 *           Replaces deprecated getDoctrineSchemaManager() with getSchemaBuilder().
	 * @performance Validation result is cached in-memory (L1) to avoid repeated DB
	 *              existence checks for the same table within a single request.
	 *
	 * @param string $table Table name to validate
	 * @return string Validated table name
	 * @throws InvalidTableNameException If table name is invalid or not in whitelist
	 */
	private function validateTableName(string $table, ?string $connection = null): string {
		// Use provided connection or fall back to instance connection
		$connection = $connection ?? $this->connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		
		// L1: in-memory cache for validation results (per-request + per-connection)
		$cacheKey = $table . '@' . $connection;
		if (isset($this->tableNameValidationCache[$cacheKey])) {
			return $this->tableNameValidationCache[$cacheKey];
		}

		// Get allowed tables from config or use null (will check DB existence)
		$allowedTables = config('canvastack.datatables.allowed_tables', null);
		
		// Use the centralized helper function for consistent validation
		// This checks format AND database existence (or whitelist if configured)
		try {
			$validated = canvastack_table_validate_table_name($table, $allowedTables, $connection);
			// Cache the validated result
			$this->tableNameValidationCache[$cacheKey] = $validated;
			return $validated;
		} catch (\InvalidArgumentException $e) {
			\Log::warning('[SECURITY] Datatables::validateTableName() - Invalid table access attempt', [
				'table'      => $table,
				'connection' => $connection,
				'error'      => $e->getMessage(),
				'context'    => 'SQL injection prevention - table whitelist check'
			]);
			throw new InvalidTableNameException($e->getMessage(), 0, $e);
		}
	}
	
	/**
	 * Validate database connection name
	 *
	 * @security SQL Injection Prevention - validates connection name against the
	 *           list of configured database connections. Throws TableValidationException
	 *           and logs a warning if the connection is not in the allowed list,
	 *           preventing unauthorized database access.
	 *
	 * @param string|null $connection Connection name to validate
	 * @return string|null Validated connection name
	 * @throws TableValidationException If connection not valid
	 */
	private function validateConnection(?string $connection): ?string {
		// Null connection is valid (uses default)
		if ($connection === null) {
			return null;
		}
		
		// Get allowed connections from config
		$allowedConnections = array_keys(config('database.connections', []));
		
		// Validate connection name
		// @performance 6.6 - Use strict mode (true) for type-safe comparison
		if (!in_array($connection, $allowedConnections, true)) {
			\Log::warning('Datatables: Invalid connection access attempt', [
				'connection' => $connection,
				'allowed' => $allowedConnections
			]);
			throw new TableValidationException('Invalid connection name');
		}
		
		return $connection;
	}
	
	/**
	 * Validate process method inputs
	 *
	 * @security Input Validation - ensures all required parameters are present and
	 *           of the correct type before any processing begins. Throws
	 *           TableValidationException for invalid inputs, preventing malformed
	 *           requests from reaching the database query layer.
	 *
	 * @param array $method Method parameters
	 * @param object $data Data configuration
	 * @throws TableValidationException If validation fails
	 */
	private function validateProcessInputs(array $method, object $data): void {
		// Validate method parameter
		if (!is_array($method)) {
			throw new TableValidationException('Method parameter must be an array');
		}
		
		// Validate data parameter
		if (!is_object($data)) {
			throw new TableValidationException('Data parameter must be an object');
		}
		
		if (!isset($data->datatables)) {
			throw new TableValidationException('Data object must have datatables property');
		}
		
		// Validate required method keys
		if (!isset($method['difta']) || !is_array($method['difta'])) {
			throw new TableValidationException('Method must have difta array');
		}
		
		if (!isset($method['difta']['name'])) {
			throw new TableValidationException('Method difta must have name');
		}
	}
	
	/**
	 * Validate filter inputs
	 *
	 * @security Input Validation - validates GET and POST parameters for the filter
	 *           initialization request. Ensures required parameters (_fita,
	 *           filterDataTables) are present and that inputs are arrays, preventing
	 *           type confusion attacks.
	 *
	 * @param array $get GET parameters
	 * @param array $post POST parameters
	 * @throws TableValidationException If validation fails
	 */
	private function validateFilterInputs(array $get, array $post): void {
		// Validate get parameter
		if (!is_array($get)) {
			throw new TableValidationException('GET parameter must be an array');
		}
		
		// Validate post parameter
		if (!is_array($post)) {
			throw new TableValidationException('POST parameter must be an array');
		}
		
		// Check required GET parameter
		if (empty($get['filterDataTables'])) {
			throw new TableValidationException('Missing filterDataTables parameter');
		}
		
		// Check required POST parameter
		if (!isset($post['_fita'])) {
			throw new TableValidationException('Missing _fita parameter');
		}
	}
	
	/**
	 * Validate DataTables request parameters
	 * 
	 * Validates all DataTables AJAX request parameters including:
	 * - draw: Request counter for concurrent request handling
	 * - start: Pagination offset
	 * - length: Page size
	 * - order: Sorting parameters
	 * - search: Search parameters
	 * - columns: Column definitions
	 * 
	 * SECURITY: Validates all parameters to prevent malicious inputs
	 * PERFORMANCE: Early validation prevents unnecessary processing
	 * 
	 * @param array $request DataTables request parameters
	 * @param string $tableName Table name for column validation
	 * @return array Validated and sanitized request parameters
	 * @throws InvalidPaginationException If pagination parameters are invalid
	 * @throws InvalidSortException If sort parameters are invalid
	 * @throws SQLInjectionAttemptException If suspicious patterns detected
	 * 
	 * @security Input Validation (Requirement 3) - validates all request parameters
	 * @security SQL Injection Prevention (Requirement 2) - validates column names
	 * @security Concurrent Request Handling (Requirement 14.8) - validates draw parameter
	 */
	private function validateDatatablesRequest(array $request, string $tableName): array {
		$validated = [];
		
		// Validate draw parameter (concurrent request handling - Requirement 14.8)
		$validated['draw'] = isset($request['draw']) ? max(0, intval($request['draw'])) : 0;
		
		// Validate pagination parameters (Requirement 3.4, 14.2)
		$start = isset($request['start']) ? intval($request['start']) : self::DEFAULT_LIMIT_START;
		$length = isset($request['length']) ? intval($request['length']) : self::DEFAULT_LIMIT_LENGTH;
		
		if ($start < 0) {
			throw new InvalidPaginationException('Pagination start must be non-negative');
		}
		
		if ($length < 1 || $length > TableConstants::MAX_PAGE_LENGTH) {
			throw new InvalidPaginationException('Pagination length must be between 1 and ' . TableConstants::MAX_PAGE_LENGTH);
		}
		
		$validated['start'] = $start;
		$validated['length'] = $length;
		
		// Validate search parameters (Requirement 3.6, 14.5)
		if (isset($request['search']) && is_array($request['search'])) {
			$searchValue = $request['search']['value'] ?? '';
			
			// INTEGRATION: Sanitize and process search term (Phase 1 & 3)
			$sanitized = canvastack_table_sanitize_search($searchValue);
			$processed = $this->processSearchTerm($sanitized);
			
			$validated['search'] = [
				'value' => $processed,
				'regex' => !empty($request['search']['regex'])
			];
			
			// INTEGRATION: Save search state (Phase 3)
			if (!empty($processed)) {
				$this->saveSearchState($tableName, $processed);
			}
		} else {
			$validated['search'] = ['value' => '', 'regex' => false];
		}
		
		// Validate order parameters (Requirement 3.5, 14.3)
		$validated['order'] = [];
		if (isset($request['order']) && is_array($request['order'])) {
			foreach ($request['order'] as $orderItem) {
				if (!isset($orderItem['column']) || !isset($orderItem['dir'])) {
					continue;
				}
				
				$columnIndex = intval($orderItem['column']);
				$direction = strtolower(trim($orderItem['dir']));
				
				// Validate direction
				if (!in_array($direction, [TableConstants::SORT_ASC, TableConstants::SORT_DESC], true)) {
					\Log::warning('[SECURITY] Datatables::validateDatatablesRequest() - Invalid sort direction', [
						'direction' => $orderItem['dir'],
						'table' => $tableName,
						'context' => 'SQL injection prevention - sort direction validation'
					]);
					throw new InvalidSortException('Invalid sort direction: ' . $orderItem['dir']);
				}
				
				$validated['order'][] = [
					'column' => $columnIndex,
					'dir' => $direction
				];
			}
		}
		
		// Validate columns parameters
		$validated['columns'] = [];
		if (isset($request['columns']) && is_array($request['columns'])) {
			foreach ($request['columns'] as $column) {
				if (!isset($column['data'])) {
					continue;
				}
				
				// Sanitize column name (Requirement 2.3)
				$columnName = preg_replace('/[^a-zA-Z0-9_.]/', '', $column['data']);
				
				if ($columnName !== $column['data']) {
					\Log::warning('[SECURITY] Datatables::validateDatatablesRequest() - Column name sanitized', [
						'original' => $column['data'],
						'sanitized' => $columnName,
						'table' => $tableName,
						'context' => 'SQL injection prevention - column name validation'
					]);
					throw new SQLInjectionAttemptException('Invalid column name detected: ' . $column['data']);
				}
				
				$validated['columns'][] = [
					'data' => $columnName,
					'name' => $column['name'] ?? '',
					'searchable' => !empty($column['searchable']),
					'orderable' => !empty($column['orderable']),
					'search' => [
						'value' => isset($column['search']['value']) ? 
							canvastack_table_sanitize_search($column['search']['value']) : '',
						'regex' => !empty($column['search']['regex'])
					]
				];
			}
		}
		
		return $validated;
	}
	
	/**
	 * Generate error response for DataTables
	 * 
	 * Creates a properly formatted error response that DataTables can handle.
	 * Includes the draw parameter for concurrent request handling.
	 * 
	 * ERROR HANDLING: Provides user-friendly error messages while logging
	 * detailed error information for debugging.
	 * 
	 * @param int $draw Request counter from DataTables
	 * @param string $errorMessage User-friendly error message
	 * @param array $context Additional context for logging
	 * @return array DataTables error response
	 * 
	 * @security XSS Prevention - error message is escaped before being sent to client
	 */
	/**
	 * Generate error response for DataTables with comprehensive logging
	 * 
	 * @param int $draw Request counter
	 * @param string $errorMessage Error message
	 * @param array $context Additional context data
	 * @return array DataTables-compatible error response
	 * 
	 * @security Logs security events when configured
	 * @performance Minimal overhead when logging disabled
	 */
	private function generateErrorResponse(int $draw, string $errorMessage, array $context = []): array {
		// CONFIG: Error handling configuration
		$logErrors = config('canvastack.datatables.error_handling.log_errors', true);
		$detailedErrors = config('canvastack.datatables.error_handling.detailed_errors', false);
		$logStackTrace = config('canvastack.datatables.error_handling.log_stack_trace', true);
		$logRequestContext = config('canvastack.datatables.error_handling.log_request_context', true);
		$userErrorMessage = config('canvastack.datatables.error_handling.user_error_message', 
			'An error occurred while processing your request.');

		// Log detailed error for debugging
		if ($logErrors) {
			$logChannel = config('canvastack.datatables.error_handling.error_log_channel', 'daily');
			$logData = [
				'draw' => $draw,
				'error_message' => $errorMessage,
				'table' => $this->table ?? 'unknown',
			];

			// Add stack trace if configured
			if ($logStackTrace && isset($context['exception'])) {
				$logData['trace'] = $context['exception']->getTraceAsString();
			}

			// Add request context if configured
			if ($logRequestContext) {
				$logData['request_context'] = [
					'ip' => request()->ip(),
					'user_id' => auth()->id(),
					'url' => request()->fullUrl(),
					'method' => request()->method(),
				];
			}

			// Merge additional context
			$logData = array_merge($logData, $context);

			\Log::channel($logChannel)->error('Datatables: Error response generated', $logData);
		}

		// Return user-friendly or detailed error based on config
		$displayMessage = $detailedErrors ? $errorMessage : $userErrorMessage;

		// Return DataTables-compatible error response
		return [
			'draw' => $draw,
			'recordsTotal' => 0,
			'recordsFiltered' => 0,
			'data' => [],
			'error' => htmlspecialchars($displayMessage, ENT_QUOTES, 'UTF-8')
		];
	}
	
	/**
	 * Set asset path with optional HTTP URL conversion
	 * 
	 * Converts file path to full system path or HTTP URL.
	 * Includes path traversal protection.
	 * 
	 * SECURITY: Sanitizes file path to prevent path traversal attacks
	 *
	 * @param string $file_path Relative file path
	 * @param boolean $http Convert to HTTP URL if true
	 * @param string $public_path Public directory name (default: 'public')
	 * 
	 * @return string Full system path or HTTP URL
	 */
	private function setAssetPath(string $file_path, bool $http = false, string $public_path = 'public'): string {
		// Sanitize file path untuk mencegah path traversal
		$file_path = $this->sanitizeFilePath($file_path);
		
		if (true === $http) {
			$assetsURL = explode('/', url()->asset('assets'));
			$stringURL = explode('/', $file_path);
			
			return implode('/', array_unique(array_merge_recursive($assetsURL, $stringURL)));
		}
		
		$file_path = str_replace($public_path . '/', public_path("\\"), $file_path);
		
		return $file_path;
	}
	
	/**
	 * Sanitize file path to prevent path traversal attacks
	 * 
	 * Removes dangerous path sequences:
	 * - ../ and ..\ (directory traversal)
	 * - Absolute path indicators (C:\, /)
	 *
	 * @security Path Traversal Prevention - strips ../ and ..\ sequences and
	 *           absolute path indicators to prevent unauthorized file system access.
	 *           This is critical for preventing attackers from reading arbitrary files.
	 *
	 * @param string $path File path to sanitize
	 * 
	 * @return string Sanitized path (relative, safe)
	 */
	private function sanitizeFilePath(string $path): string {
		// Remove any ../ or ..\\ sequences
		$path = str_replace(['../', '..\\'], '', $path);
		
		// Remove any absolute path indicators
		$path = preg_replace('/^[a-zA-Z]:\\\\/', '', $path); // Windows absolute path
		$path = ltrim($path, '/'); // Unix absolute path
		
		return $path;
	}
	
	/**
	 * Check if file is a valid image
	 * 
	 * Validates if file exists and has valid image extension.
	 * Returns HTML error message if file doesn't exist.
	 * 
	 * PERFORMANCE: Results are cached in two levels:
	 *   L1 - in-memory per-request cache (fastest)
	 *   L2 - persistent Laravel Cache (cross-request, configurable TTL)
	 * This prevents repeated file_exists() syscalls for the same path across
	 * multiple rows and multiple requests.
	 *
	 * @security XSS Prevention - the filename in the error HTML is escaped with
	 *           htmlspecialchars before embedding in HTML. The error HTML string
	 *           is marked with SafeHtml::mark() to prevent double-encoding.
	 * @security Path Traversal Prevention - file path is sanitized via
	 *           sanitizeFilePath() before use in file_exists() check.
	 * 
	 * Supported extensions: jpg, jpeg, png, gif
	 *
	 * @param string|null $string File path to check
	 * @param boolean $local_path Use local path (default: true)
	 * 
	 * @return boolean|string True if valid image, false if not image, HTML string if file missing
	 */
	private function checkValidImage(?string $string, bool $local_path = true): bool|string {
		// Handle null values (treat as non-image)
		if ($string === null) {
			return false;
		}
		
		// L1: in-memory cache (fastest, per-request)
		if (isset($this->imageValidationCache[$string])) {
			return $this->imageValidationCache[$string];
		}

		// L2: persistent cache key
		$cacheEnabled = $this->shouldUseCache('validation');
		$cacheKey     = config('canvastack.cache.prefix', 'canvastack_') . config('canvastack.cache.validation.key_prefix', 'validation_') . 'img_' . md5($string);
		$cacheTtl     = $this->getImageValidationCacheTtl();

		if ($cacheEnabled) {
			$cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
			if ($cached !== null) {
				// Phase 4: Cache monitoring
				canvastack_table_cache_monitor('get', $cacheKey, true);
				
				// Restore L1 from L2
				$this->imageValidationCache[$string] = $cached;
				return $cached;
			}
			
			// Phase 4: Cache monitoring - MISS
			canvastack_table_cache_monitor('get', $cacheKey, false);
		}

		// Compute result
		$filePath = $this->setAssetPath($string);
		
		if (true === file_exists($filePath)) {
			$isValidImage = false;
			foreach ($this->image_checker as $check) {
				if (false !== strpos($string, $check)) {
					$isValidImage = true;
					break;
				}
			}
			$result = $isValidImage;
		} else {
			$filePath = explode('/', $string);
			$lastSrc  = array_key_last($filePath);
			$lastFile = isset($filePath[$lastSrc]) ? $filePath[$lastSrc] : 'unknown';
			
			// Escape untuk mencegah XSS
			$safeLastFile = htmlspecialchars($lastFile, ENT_QUOTES, 'UTF-8');
			$info   = "This File [ {$safeLastFile} ] Do Not or Never Exist!";
			$result = SafeHtml::mark("<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$safeLastFile}</div>");
		}

		// Store in L1
		$this->imageValidationCache[$string] = $result;

		// Store in L2 (persistent cache)
		if ($cacheEnabled) {
			try {
				\Illuminate\Support\Facades\Cache::put($cacheKey, $result, $cacheTtl);
			} catch (\Exception $e) {
				// Non-fatal: log and continue without persistent cache
				error_log('Datatables::checkValidImage() cache write failed: ' . $e->getMessage());
			}
		}

		return $result;
	}

	/**
	 * Main process method untuk generate datatables
	 * 
	 * Processes DataTables AJAX request and returns formatted JSON response.
	 * Handles complete DataTables lifecycle including:
	 * - Model initialization from configuration
	 * - Privilege-based action filtering
	 * - Relationship handling (joins and eager loading)
	 * - Condition and filter application
	 * - Pagination and ordering
	 * - Image column detection and rendering
	 * - Action button generation
	 * - Formula and data formatting
	 * 
	 * SECURITY: Added input validation and error handling
	 * PERFORMANCE: Includes eager loading for relations
	 *
	 * @security XSS Prevention - all user-controllable data rendered to HTML output
	 *           is escaped via escapeData() (htmlspecialchars ENT_QUOTES UTF-8).
	 *           Specifically:
	 *           - ip_address values escaped in processStatusColumns()
	 *           - relation field values escaped in processRelations()
	 *           - formatted data values escaped in applyDataFormatting()
	 *           - image paths/labels escaped in renderImageColumn()
	 *           - filenames escaped in renderFileNameColumn()
	 *           - action button labels/URLs escaped by canvastack_table_action_button()
	 *           - row attributes validated via validateAttributeKeys()
	 *           - HTML output marked with SafeHtml::mark() to prevent double-encoding
	 * @param array $method Request parameters including:
	 *   - difta: array Table identifier
	 *     - name: string Table name
	 *   - start: int Pagination start offset (default: 0)
	 *   - length: int Page size (default: 10)
	 *   - draw: int Request counter
	 *   - search: array Search parameters
	 *   - order: array Ordering parameters
	 * 
	 * @param object $data Table configuration object with:
	 *   - datatables: object Main configuration
	 *     - model: array Model class mappings
	 *     - columns: array Column configurations
	 *       - lists: array Visible columns
	 *       - foreign_keys: array Join definitions
	 *       - relations: array Eloquent relations
	 *       - formulas: array Calculated columns
	 *       - formats: array Data formatters
	 *     - conditions: array Where clauses
	 *     - actions: array Action button configs
	 *     - modelProcessing: array Model processors
	 * 
	 * @param array $filters Additional filter conditions (optional)
	 *   Format: [['field_name' => 'column', 'value' => 'filter_value'], ...]
	 * 
	 * @param array $filter_page Page-specific filters (optional)
	 * 
	 * @return mixed DataTables JSON response array with:
	 *   - draw: int Request counter
	 *   - recordsTotal: int Total records
	 *   - recordsFiltered: int Filtered records
	 *   - data: array Table rows
	 *   Returns null on error
	 * 
	 * @throws \InvalidArgumentException If invalid inputs (caught and logged)
	 * @throws \Exception If database query fails (caught and logged)
	 */
	public function process(array $method, object $data, array $filters = [], array $filter_page = []): mixed {
		// PERFORMANCE: Start monitoring
		$processStart = microtime(true);
		$tableName    = $method['difta']['name'] ?? 'unknown';
		
		try {
			// PERFORMANCE: Monitor memory usage if enabled
			if (config('canvastack.datatables.performance.monitor_memory', true)) {
				$this->checkMemoryUsage('Datatables::process');
			}

			// SECURITY: Validate inputs
			$this->validateProcessInputs($method, $data);
			
			// Initialize model dan table name
			$modelInfo = $this->initializeModel($method, $data);
			if (empty($modelInfo)) {
				\Log::warning('Datatables: Model initialization failed', [
					'method' => $tableName
				]);
				return null;
			}
			
			$model_data = $modelInfo['model_data'];
			$table_name = $modelInfo['table_name'];
			
			// SECURITY: Validate DataTables request parameters (Requirement 14.8)
			// This validates pagination, sorting, searching, and concurrent requests
			$validatedRequest = $this->validateDatatablesRequest($method, $table_name);
			
			// Use validated parameters for processing
			$method = array_merge($method, $validatedRequest);
			
			// Check if any model processing needed
			if (isset($data->datatables->modelProcessing[$table_name])) {
				canvastack_model_processing_table($data->datatables->modelProcessing, $table_name);
			}
			
			// Setup privileges dan actions
			$actionConfig = $this->setupActionConfig($data, $table_name);
			
			// Setup field configuration
			$fieldConfig = $this->setupFieldConfig($data, $table_name);
			
			// Apply relationships (joins)
			$joinResult = $this->applyRelationships($model_data, $data, $table_name);
			$model_data = $joinResult['model'];
			$joinFields = $joinResult['joinFields'];
			// @performance 6.5 - Free intermediate join result array after extraction
			unset($joinResult);
			
			// PERFORMANCE: Apply column selection when no joins (avoid SELECT *)
			if (empty($joinFields)) {
				$model_data = $this->selectRequiredColumns($model_data, $data, $table_name);
			}
			
			// Apply conditions (where clauses)
			$model_condition = $this->applyConditions($model_data, $data, $table_name);
			// @performance 6.5 - Free model_data after conditions are applied
			unset($model_data);
			
			// Apply filters
			$filterResult = $this->applyFilters($model_condition, $filters, $table_name, $fieldConfig['firstField']);
			$model        = $filterResult['model'];
			$limitTotal   = $filterResult['limitTotal'];
			// @performance 6.5 - Free intermediate filter result and condition model after extraction
			unset($filterResult, $model_condition);
			
			// Apply pagination
			$limit = $this->applyPagination($model, $limitTotal);
			
			// Build datatables
			$datatables = $this->buildDatatables($model, $limit, $fieldConfig['blacklists']);
			// @performance 6.5 - Free pagination config after datatables instance is built
			unset($limit);
			
			// Apply ordering
			$this->applyOrdering($datatables, $data, $table_name);
			
			// PERFORMANCE: Check memory before row processing (Requirement 6.7)
			$this->checkMemoryUsage('Datatables::processRows');

			// Process rows
			$this->processRows($model, $datatables, $data, $table_name, $joinFields, $limitTotal);
			// @performance 6.5 - Free field config after row processing is complete
			unset($fieldConfig);
			
			// Setup row attributes (clickable)
			$this->setupRowAttributes($datatables, $data, $table_name);
			
			// Add action column
			$this->addActionColumn($datatables, $model, $actionConfig, $data);
			// @performance 6.5 - Free action config and model after action column is added
			unset($actionConfig, $model);
			
			// Generate final table data
			$result = $this->generateTableData($datatables, $data);
			// @performance 6.5 - Free datatables instance after result is generated
			unset($datatables);
			
			// PERFORMANCE: Log metrics
			$this->logQueryPerformance($tableName, $processStart);
			
			return $result;
			
		} catch (\InvalidArgumentException $e) {
			$draw = $method['draw'] ?? 0;
			\Log::warning('Datatables: Input validation failed', [
				'error'  => $e->getMessage(),
				'method' => $tableName,
				'draw'   => $draw
			]);
			return $this->generateErrorResponse($draw, 'Invalid request parameters', [
				'table' => $tableName,
				'error_type' => 'validation'
			]);
		} catch (\Error $e) {
			$draw = $method['draw'] ?? 0;
			if ($this->isOutOfMemoryError($e)) {
				$context = [
					'table' => $tableName,
					'error_type' => 'out_of_memory',
					'draw' => $draw,
				];
				
				// Log with config
				if (config('canvastack.datatables.error_handling.log_errors', true)) {
					$channel = config('canvastack.datatables.error_handling.error_log_channel', 'daily');
					$logData = ['message' => $e->getMessage(), 'context' => $context];
					
					if (config('canvastack.datatables.error_handling.log_stack_trace', true)) {
						$logData['trace'] = $e->getTraceAsString();
					}
					
					if (config('canvastack.datatables.error_handling.log_request_context', true)) {
						$logData['ip'] = request()->ip();
						$logData['user'] = auth()->id();
					}
					
					\Log::channel($channel)->error('Datatables: Out of memory during process()', $logData);
				}
				
				$errorMsg = config('canvastack.datatables.error_handling.detailed_errors', false)
					? 'Server memory limit exceeded: ' . $e->getMessage()
					: config('canvastack.datatables.error_handling.user_error_message', 'An error occurred while processing your request');
				
				return $this->generateErrorResponse($draw, $errorMsg, $context);
			}
			// Re-throw non-OOM errors so they are not silently swallowed
			throw $e;
		} catch (\Illuminate\Database\QueryException $e) {
			$draw = $method['draw'] ?? 0;
			$context = [
				'table' => $tableName,
				'error_type' => 'database',
				'draw' => $draw,
			];
			
			// Log with config
			if (config('canvastack.datatables.error_handling.log_errors', true)) {
				$channel = config('canvastack.datatables.error_handling.error_log_channel', 'daily');
				$logData = ['message' => $e->getMessage(), 'context' => $context, 'sql' => $e->getSql() ?? 'N/A'];
				
				if (config('canvastack.datatables.error_handling.log_stack_trace', true)) {
					$logData['trace'] = $e->getTraceAsString();
				}
				
				if (config('canvastack.datatables.error_handling.log_request_context', true)) {
					$logData['ip'] = request()->ip();
					$logData['user'] = auth()->id();
				}
				
				\Log::channel($channel)->error('Datatables: Database query failed', $logData);
			}
			
			$errorMsg = config('canvastack.datatables.error_handling.detailed_errors', false)
				? 'Database query error: ' . $e->getMessage()
				: config('canvastack.datatables.error_handling.user_error_message', 'An error occurred while processing your request');
			
			return $this->generateErrorResponse($draw, $errorMsg, $context);
		} catch (\Exception $e) {
			$draw = $method['draw'] ?? 0;
			$context = [
				'table' => $tableName,
				'error_type' => 'general',
				'draw' => $draw,
			];
			
			// Log with config
			if (config('canvastack.datatables.error_handling.log_errors', true)) {
				$channel = config('canvastack.datatables.error_handling.error_log_channel', 'daily');
				$logData = ['message' => $e->getMessage(), 'context' => $context];
				
				if (config('canvastack.datatables.error_handling.log_stack_trace', true)) {
					$logData['trace'] = $e->getTraceAsString();
				}
				
				if (config('canvastack.datatables.error_handling.log_request_context', true)) {
					$logData['ip'] = request()->ip();
					$logData['user'] = auth()->id();
				}
				
				\Log::channel($channel)->error('Datatables: Process failed', $logData);
			}
			
			$errorMsg = config('canvastack.datatables.error_handling.detailed_errors', false)
				? $e->getMessage()
				: config('canvastack.datatables.error_handling.user_error_message', 'An error occurred while processing your request');
			
			return $this->generateErrorResponse($draw, $errorMsg, $context);
		}
	}

	/**
	 * Initialize model and table name from configuration
	 * 
	 * Extracts model class and table name from configuration object.
	 * Returns null if model not found in configuration.
	 *
	 * @param array $method Request parameters with difta.name
	 * @param object $data Configuration object with datatables.model mapping
	 * 
	 * @return array|null Array with keys:
	 *   - model_data: mixed Eloquent model or query builder instance
	 *   - table_name: string Database table name
	 *   Returns null if model not configured
	 */
	private function initializeModel(array $method, object $data): ?array {
		if (empty($data->datatables->model[$method['difta']['name']])) {
			return null;
		}
		
		$model_type   = $data->datatables->model[$method['difta']['name']]['type'];
		$model_source = $data->datatables->model[$method['difta']['name']]['source'];
		
		$model_data = null;
		$table_name = null;
		
		if ('model' === $model_type) {
			$model_data = $model_source;
			$table_name = $model_data->getTable();
			
			// Model already uses its own connection automatically via Eloquent
			// No need to manually set connection - Eloquent handles it
		}
		
		// DEVELOPMENT STATUS | @WAITINGLISTS
		if ('sql' === $model_type) {
			$model_data = new DynamicTables($model_source);
			$table_name = $model_source; // Assuming source is table name for SQL type
		}
		
		return [
			'model_data' => $model_data,
			'table_name' => $table_name
		];
	}
	
	/**
	 * Setup action configuration based on user privileges
	 * 
	 * Determines which action buttons should be displayed based on:
	 * - User role and privileges
	 * - Table-specific action configuration
	 * - Explicitly removed buttons
	 * 
	 * Refactored to reduce nesting from 8 to 2 levels.
	 * 
	 * @param object $data Table configuration with datatables.columns
	 * @param string $table_name Database table name
	 * 
	 * @return array Action configuration with keys:
	 *   - privileges: array User privilege information
	 *   - action_list: array|false Available actions or false if none
	 *   - removed_privileges: array Actions removed by privilege check
	 *   - buttonsRemoval: array Explicitly removed buttons
	 */
	private function setupActionConfig(object $data, string $table_name): array {
		$privileges = $this->set_module_privileges();
		$column_data = $data->datatables->columns;
		
		$buttonsRemoval = $data->datatables->columns[$table_name]['button_removed'] ?? [];
		
		// Extract action list from configuration
		$action_list = $this->extractActionList($column_data, $table_name);
		
		// If no actions configured, return early
		if (false === $action_list) {
			return [
				'privileges'         => $privileges,
				'action_list'        => false,
				'removed_privileges' => [],
				'buttonsRemoval'     => $buttonsRemoval
			];
		}
		
		// Filter actions based on privileges
		$_action_lists = $this->filterActionsByPrivileges($action_list, $privileges);
		
		// Calculate removed privileges
		$removed_privileges = $this->calculateRemovedPrivileges($action_list, $_action_lists);
		
		return [
			'privileges'         => $privileges,
			'action_list'        => $action_list,
			'removed_privileges' => $removed_privileges,
			'buttonsRemoval'     => $buttonsRemoval
		];
	}
	
	/**
	 * Extract action list from column configuration
	 * 
	 * Determines available actions from table configuration.
	 * Returns default actions if true, custom actions if array, or false if none.
	 * 
	 * @param array $column_data Column configuration array
	 * @param string $table_name Database table name
	 * 
	 * @return array|false Array of action names ['view', 'edit', 'delete', ...] or false
	 */
	private function extractActionList(array $column_data, string $table_name): array|false {
		if (!isset($column_data[$table_name]['actions'])) {
			return false;
		}
		
		$actions = $column_data[$table_name]['actions'];
		
		if (!is_array($actions) && true !== $actions) {
			return false;
		}
		
		if (true === $actions) {
			return self::DEFAULT_ACTIONS;
		}
		
		// Create temporary variable to avoid passing constant by reference
		$defaultActions = self::DEFAULT_ACTIONS;
		return array_merge_recursive_distinct($defaultActions, $actions);
	}
	
	/**
	 * Filter actions based on user privileges
	 * 
	 * Removes actions that user doesn't have permission to perform.
	 * Admin users (role_group <= 1) bypass privilege checking.
	 * 
	 * @param array $action_list Full list of available actions
	 * @param array $privileges User privilege information with:
	 *   - role_group: int User role group level
	 *   - role: array User role permissions
	 * 
	 * @return array Filtered action list (empty if admin or no privileges)
	 */
	private function filterActionsByPrivileges(array $action_list, array $privileges): array {
		// If role_group <= 1, no filtering needed (admin/superadmin)
		if ($privileges['role_group'] <= self::ADMIN_ROLE_GROUP) {
			return [];
		}
		
		if (empty($privileges['role'])) {
			return [];
		}
		
		// Extract privilege actions
		$privilegeActions = $this->extractPrivilegeActions($privileges['role']);
		
		if (empty($privilegeActions)) {
			return [];
		}
		
		// Filter action list based on privileges
		return $this->buildFilteredActionList($action_list, $privilegeActions);
	}
	
	/**
	 * Extract actions from user privileges/roles
	 * 
	 * @param array $roles User roles
	 * @return array Actions mapped from roles
	 */
	private function extractPrivilegeActions(array $roles): array {
		$baseInfo = routelists_info()['base_info'];
		
		// Check if base_info exists in roles
		if (empty(strpos(json_encode($roles), $baseInfo))) {
			return [];
		}
		
		$actions = [];
		
		foreach ($roles as $role) {
			if (!canvastack_string_contained($role, $baseInfo)) {
				continue;
			}
			
			$actionType = $this->mapRouteToAction($role);
			
			if ($actionType) {
				$actions[$baseInfo][$actionType] = $actionType;
			}
		}
		
		return $actions;
	}
	
	/**
	 * Map route name to action type
	 * 
	 * @param string $role Role/route name
	 * @return string|null Action type or null
	 */
	private function mapRouteToAction(string $role): ?string {
		$routename = routelists_info($role)['last_info'];
		
		if (in_array($routename, ['index', 'show', 'view'], true)) {
			return 'view';
		}
		
		if (in_array($routename, ['create', 'insert'], true)) {
			return 'insert';
		}
		
		if (in_array($routename, ['edit', 'modify', 'update'], true)) {
			return 'edit';
		}
		
		if (in_array($routename, ['destroy', 'delete'], true)) {
			return 'delete';
		}
		
		return null;
	}
	
	/**
	 * Build filtered action list based on privileges
	 * 
	 * @param array $action_list Full action list
	 * @param array $privilegeActions Actions from privileges
	 * @return array Filtered action list
	 */
	private function buildFilteredActionList(array $action_list, array $privilegeActions): array {
		$baseInfo = routelists_info()['base_info'];
		$_action_lists = [];
		
		foreach ($action_list as $_list) {
			if (isset($privilegeActions[$baseInfo][$_list])) {
				$_action_lists[] = $privilegeActions[$baseInfo][$_list];
			} elseif (!in_array($_list, self::DEFAULT_ACTIONS, true)) {
				// Custom actions (not in DEFAULT_ACTIONS) are always included
				$_action_lists[] = $_list;
			}
		}
		
		return $_action_lists;
	}
	
	/**
	 * Calculate removed privileges (actions not allowed)
	 * 
	 * @param array $action_list Full action list
	 * @param array $_action_lists Filtered action list
	 * @return array Removed privileges
	 */
	private function calculateRemovedPrivileges(array $action_list, array $_action_lists): array {
		$diff = array_diff($action_list, $_action_lists);
		
		return !empty($diff) ? $diff : [];
	}
	
	/**
	 * Setup field configuration for DataTables
	 * 
	 * Determines first field and blacklist based on table configuration.
	 * Used for default filtering and column visibility.
	 * 
	 * @param object $data Table configuration with datatables.columns
	 * @param string $table_name Database table name
	 * 
	 * @return array Field configuration with keys:
	 *   - firstField: string First visible field (default: 'id')
	 *   - blacklists: array Fields to hide from display
	 */
	private function setupFieldConfig(object $data, string $table_name): array {
		$firstField = 'id';
		$blacklists = self::BLACKLIST_FIELDS;
		
		// @performance 6.6 - Use strict mode (true) for type-safe comparison
		if (isset($data->datatables->columns[$table_name]['lists']) && !in_array('id', $data->datatables->columns[$table_name]['lists'], true)) {
			$firstField = $data->datatables->columns[$table_name]['lists'][0];
			$blacklists = self::BLACKLIST_WITH_ID;
		}
		
		return [
			'firstField' => $firstField,
			'blacklists' => $blacklists
		];
	}

	/**
	 * Apply relationships (joins) to model
	 * 
	 * Handles both SQL joins (foreign_keys) and Eloquent relations.
	 * Automatically selects all fields from joined tables.
	 * Applies eager loading for Eloquent relations to prevent N+1 queries.
	 * 
	 * PERFORMANCE: Includes eager loading optimization
	 *
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param object $data Table configuration with:
	 *   - datatables->columns->foreign_keys: array Join definitions
	 *   - datatables->columns->relations: array Eloquent relations
	 * @param string $table_name Database table name
	 * 
	 * @return array Result with keys:
	 *   - model: mixed Modified model with joins and eager loading
	 *   - joinFields: array Selected fields from joins
	 */
	/**
	 * Apply relationships to model (joins and eager loading)
	 * 
	 * Handles both foreign key joins and Eloquent relationships.
	 * Supports different join types (INNER, LEFT, RIGHT) for better query optimization.
	 * Validates relationships before applying them.
	 * 
	 * PERFORMANCE: Optimizes join queries and applies eager loading
	 * SECURITY: Validates table and column names before joining
	 * 
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param object $data Table configuration with foreign_keys and relations
	 * @param string $table_name Database table name
	 * 
	 * @return array ['model' => mixed, 'joinFields' => array]
	 * 
	 * @throws \InvalidArgumentException If relationship validation fails
	 */
	private function applyRelationships(mixed $model_data, object $data, string $table_name): array {
		$joinFields = [];
		
		if (isset($data->datatables->columns[$table_name]['foreign_keys'])) {
			$fieldsets  = [];
			$joinFields = ["{$table_name}.*"];
			
			foreach ($data->datatables->columns[$table_name]['foreign_keys'] as $fkey1 => $fkey2) {
				// Parse join configuration
				$joinConfig = $this->parseJoinConfiguration($fkey1, $fkey2);
				
				// Validate relationship before applying
				$this->validateRelationship($joinConfig['table'], $joinConfig['foreignKey'], $joinConfig['localKey'], $table_name);
				
				// Apply join with specified type (default: LEFT)
				$model_data = $this->applyJoin($model_data, $joinConfig);
				
				// Collect fields from joined table
				$fieldsets[$joinConfig['table']] = canvastack_get_table_columns($joinConfig['table']);
			}
			
			// Build select fields for joined tables
			foreach ($fieldsets as $fstname => $fieldRows) {
				foreach ($fieldRows as $fieldset) {
					if ('id' === $fieldset) {
						// Alias id columns to avoid conflicts
						$joinFields[] = "{$fstname}.{$fieldset} as {$fstname}_{$fieldset}";
					} else {
						$joinFields[] = "{$fstname}.{$fieldset}";
					}
				}
			}
			$model_data = $model_data->select($joinFields);
		}
		
		// PERFORMANCE: Apply eager loading for Eloquent relations
		$model_data = $this->applyEagerLoading($model_data, $data, $table_name);
		
		return [
			'model'      => $model_data,
			'joinFields' => $joinFields
		];
	}
	
	/**
	 * Parse join configuration from foreign key definition
	 * 
	 * Supports multiple formats:
	 * - Simple: "related_table.id" => "table.foreign_id"
	 * - With join type: "related_table.id:INNER" => "table.foreign_id"
	 * 
	 * @param string $fkey1 Foreign key definition (table.column or table.column:joinType)
	 * @param string $fkey2 Local key definition (table.column)
	 * 
	 * @return array ['table' => string, 'foreignKey' => string, 'localKey' => string, 'joinType' => string]
	 */
	private function parseJoinConfiguration(string $fkey1, string $fkey2): array {
		// Check for join type specification (e.g., "users.id:INNER")
		$joinType = 'LEFT'; // Default join type
		if (strpos($fkey1, ':') !== false) {
			[$fkey1, $joinType] = explode(':', $fkey1, 2);
			$joinType = strtoupper(trim($joinType));
			
			// Validate join type
			if (!in_array($joinType, ['INNER', 'LEFT', 'RIGHT', 'CROSS'], true)) {
				\Log::warning('Datatables: Invalid join type specified, using LEFT', [
					'specified' => $joinType,
					'foreign_key' => $fkey1
				]);
				$joinType = 'LEFT';
			}
		}
		
		// Parse table and column from foreign key
		$ftables = explode('.', $fkey1);
		if (count($ftables) < 2) {
			throw new \InvalidArgumentException("Invalid foreign key format: {$fkey1}. Expected format: table.column");
		}
		
		return [
			'table'      => $ftables[0],
			'foreignKey' => $fkey1,
			'localKey'   => $fkey2,
			'joinType'   => $joinType
		];
	}
	
	/**
	 * Apply join to model with specified join type
	 * 
	 * Supports INNER, LEFT, RIGHT, and CROSS joins for better query optimization.
	 * 
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param array $joinConfig Join configuration from parseJoinConfiguration
	 * 
	 * @return mixed Model with join applied
	 */
	private function applyJoin(mixed $model_data, array $joinConfig): mixed {
		$table = $joinConfig['table'];
		$foreignKey = $joinConfig['foreignKey'];
		$localKey = $joinConfig['localKey'];
		$joinType = $joinConfig['joinType'];
		
		// Apply appropriate join type
		switch ($joinType) {
			case 'INNER':
				return $model_data->join($table, $foreignKey, '=', $localKey);
			
			case 'RIGHT':
				return $model_data->rightJoin($table, $foreignKey, '=', $localKey);
			
			case 'CROSS':
				return $model_data->crossJoin($table);
			
			case 'LEFT':
			default:
				return $model_data->leftJoin($table, $foreignKey, '=', $localKey);
		}
	}
	
	/**
	 * Validate relationship definition before applying
	 * 
	 * Checks that:
	 * - Related table exists
	 * - Foreign key column exists in related table
	 * - Local key column exists in base table
	 * 
	 * SECURITY: Prevents SQL injection through invalid table/column names
	 * 
	 * @param string $relatedTable Related table name
	 * @param string $foreignKey Foreign key (table.column format)
	 * @param string $localKey Local key (table.column format)
	 * @param string $baseTable Base table name
	 * 
	 * @return void
	 * 
	 * @throws RelationshipException If validation fails
	 */
	private function validateRelationship(string $relatedTable, string $foreignKey, string $localKey, string $baseTable): void {
		try {
			// Get database connection
			$connection = \DB::connection();
			$schema = $connection->getSchemaBuilder();
			
			// Check if related table exists
			if (!$schema->hasTable($relatedTable)) {
				throw new RelationshipException("Related table does not exist: {$relatedTable}");
			}
			
			// Parse and validate foreign key column
			$foreignKeyParts = explode('.', $foreignKey);
			if (count($foreignKeyParts) >= 2) {
				$foreignColumn = end($foreignKeyParts);
				if (!$schema->hasColumn($relatedTable, $foreignColumn)) {
					throw new RelationshipException("Foreign key column does not exist: {$foreignColumn} in table {$relatedTable}");
				}
			}
			
			// Parse and validate local key column
			$localKeyParts = explode('.', $localKey);
			if (count($localKeyParts) >= 2) {
				$localTable = $localKeyParts[0];
				$localColumn = $localKeyParts[1];
				
				// Validate local table (should be base table or already joined table)
				if ($localTable === $baseTable) {
					if (!$schema->hasColumn($baseTable, $localColumn)) {
						throw new RelationshipException("Local key column does not exist: {$localColumn} in table {$baseTable}");
					}
				}
			}
			
		} catch (RelationshipException $e) {
			// Log validation error
			\Log::error('Datatables: Relationship validation failed', [
				'related_table' => $relatedTable,
				'foreign_key' => $foreignKey,
				'local_key' => $localKey,
				'base_table' => $baseTable,
				'error' => $e->getMessage()
			]);
			
			// Re-throw for caller to handle
			throw $e;
		} catch (\Exception $e) {
			// Log validation error
			\Log::error('Datatables: Relationship validation failed', [
				'related_table' => $relatedTable,
				'foreign_key' => $foreignKey,
				'local_key' => $localKey,
				'base_table' => $baseTable,
				'error' => $e->getMessage()
			]);
			
			// Re-throw as RelationshipException
			throw new RelationshipException("Relationship validation failed: " . $e->getMessage(), 0, $e);
		}
	}
	
	/**
	 * Apply eager loading for Eloquent relationships
	 * 
	 * Prevents N+1 query problem by loading all relations at once.
	 * Supports nested relationships (e.g., 'user.profile.avatar').
	 * Optimizes relationship loading strategy based on configuration.
	 * Only applies to Eloquent models (checks for 'with' method).
	 * 
	 * PERFORMANCE: Reduces N queries to 1 query for relations
	 * 
	 * Example:
	 * Before: 1 query + N queries (one per row for each relation)
	 * After: 1 query + 1 query (all relations loaded at once)
	 * 
	 * Nested Example:
	 * $relations = ['user.profile', 'user.roles', 'category']
	 * Result: Loads user with profile, user with roles, and category in optimized queries
	 *
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param object $data Table configuration with datatables.columns.relations
	 * @param string $table_name Database table name
	 * 
	 * @return mixed Model with eager loading applied (or unchanged if not applicable)
	 */
	private function applyEagerLoading(mixed $model_data, object $data, string $table_name): mixed {
		// Check if model has relations defined
		if (!isset($data->datatables->columns[$table_name]['relations'])) {
			return $model_data;
		}
		
		// Check if eager loading is enabled in config
		if (!config('canvastack.datatables.performance.eager_loading', true)) {
			return $model_data;
		}
		
		$relations = $data->datatables->columns[$table_name]['relations'];
		
		// Only apply eager loading if model is Eloquent (has 'with' method)
		if (!method_exists($model_data, 'with')) {
			return $model_data;
		}
		
		// Extract and optimize relation names
		$relationNames = $this->extractRelationNames($relations);
		
		// Filter out relations that don't exist on the model
		// This prevents errors when using custom relational fields (via JOINs) instead of Eloquent relationships
		$relationNames = $this->filterValidRelations($model_data, $relationNames, $table_name);
		
		if (empty($relationNames)) {
			return $model_data;
		}
		
		// Phase 3: Check lazy loading threshold
		$lazyThreshold = config('canvastack.datatables.relationships.lazy_loading_threshold', 100);
		$estimatedRows = $model_data->count();
		
		if ($estimatedRows > $lazyThreshold) {
			\Log::info('Datatables: Skipping eager loading due to threshold', [
				'table' => $table_name,
				'rows' => $estimatedRows,
				'threshold' => $lazyThreshold,
				'relations' => $relationNames
			]);
			return $model_data; // Skip eager loading for large datasets
		}
		
		// PERFORMANCE: Check if relationships cache is enabled
		if ($this->shouldUseCache('relationships') && config('canvastack.cache.relationships.cache_definitions', true)) {
			$cacheKey = config('canvastack.cache.prefix', 'canvastack_') . 
			            config('canvastack.cache.relationships.key_prefix', 'relationships_') . 
			            $table_name . '_' . md5(serialize($relationNames));
			
			// Phase 3: Use relationship-specific cache TTL
			$cacheTtl = config('canvastack.datatables.relationships.relationship_cache_ttl', 
			                   config('canvastack.cache.relationships.ttl', 3600));
			
			// Try to get from cache
			$cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
			if ($cached !== null) {
				canvastack_table_cache_monitor('get', $cacheKey, true);
				
				if (config('canvastack.cache.development.log_operations', false)) {
					\Log::debug('Cache HIT: Relationships', ['table' => $table_name, 'key' => $cacheKey]);
				}
				
				return $model_data->with($cached);
			}
			
			canvastack_table_cache_monitor('get', $cacheKey, false);
			
			// Cache the relation names for future use
			try {
				\Illuminate\Support\Facades\Cache::put($cacheKey, $relationNames, $cacheTtl);
			} catch (\Exception $e) {
				error_log('Datatables::applyEagerLoading() cache write failed: ' . $e->getMessage());
			}
		}
		
		// PERFORMANCE: Eager load all relations at once
		// Phase 3: Supports nested relations (e.g., 'user.profile.avatar')
		// Nested eager loading is enabled by config
		if (config('canvastack.datatables.relationships.nested_eager_loading', true)) {
			$model_data = $model_data->with($relationNames);
		} else {
			// Only load first-level relationships
			$firstLevelRelations = array_filter($relationNames, function($rel) {
				return strpos($rel, '.') === false;
			});
			$model_data = $model_data->with($firstLevelRelations);
		}
		
		// Log eager loading for performance monitoring
		\Log::debug('Datatables: Applied eager loading', [
			'table' => $table_name,
			'relations' => $relationNames,
			'count' => count($relationNames),
			'nested_enabled' => config('canvastack.datatables.relationships.nested_eager_loading', true)
		]);
		
		return $model_data;
	}
	
	/**
	 * Extract relation names from configuration
	 * 
	 * Handles multiple configuration formats:
	 * - Simple array keys: ['user' => [...], 'category' => [...]]
	 * - Nested relations: ['user.profile' => [...], 'user.roles' => [...]]
	 * - With constraints: ['user' => ['relation_name' => 'user', ...]]
	 * 
	 * Optimizes nested relations to avoid redundant loading:
	 * - ['user.profile', 'user.roles'] stays as is (both needed)
	 * - ['user', 'user.profile'] becomes ['user.profile'] (profile includes user)
	 * 
	 * @param array $relations Relations configuration from table data
	 * 
	 * @return array Optimized list of relation names for eager loading
	 */
	private function extractRelationNames(array $relations): array {
		$relationNames = [];
		
		foreach ($relations as $relField => $relData) {
			// Check if relation_name is explicitly specified
			if (isset($relData['relation_name'])) {
				$relationNames[] = $relData['relation_name'];
			} else {
				// Use field name as relation name
				$relationNames[] = $relField;
			}
		}
		
		// Remove duplicates
		$relationNames = array_unique($relationNames);
		
		// Optimize nested relations
		$relationNames = $this->optimizeNestedRelations($relationNames);
		
		return array_values($relationNames);
	}
	
	/**
	 * Optimize nested relation loading
	 * 
	 * Removes redundant parent relations when child relations are present.
	 * Example: ['user', 'user.profile'] becomes ['user.profile']
	 * because loading 'user.profile' automatically loads 'user'.
	 * 
	 * However, keeps siblings: ['user.profile', 'user.roles'] stays as is.
	 * 
	 * @param array $relationNames List of relation names (may include nested)
	 * 
	 * @return array Optimized list without redundant parent relations
	 */
	private function optimizeNestedRelations(array $relationNames): array {
		$optimized = [];
		
		foreach ($relationNames as $relation) {
			$isRedundant = false;
			
			// Check if this relation is a parent of any other relation
			foreach ($relationNames as $otherRelation) {
				if ($relation !== $otherRelation && strpos($otherRelation, $relation . '.') === 0) {
					// This relation is a parent of another relation, mark as redundant
					$isRedundant = true;
					break;
				}
			}
			
			if (!$isRedundant) {
				$optimized[] = $relation;
			}
		}
		
		return $optimized;
	}
	
	/**
	 * Filter relation names to only include valid Eloquent relationships
	 * 
	 * This prevents errors when custom relational fields (via JOINs) are used
	 * instead of Eloquent relationships. Only relations that actually exist
	 * on the model will be eager loaded.
	 * 
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param array $relationNames List of relation names to validate
	 * @param string $table_name Table name for logging
	 * 
	 * @return array Filtered list of valid relation names
	 */
	private function filterValidRelations(mixed $model_data, array $relationNames, string $table_name): array {
		$validRelations = [];
		
		// Get the model instance to check for relationship methods
		$modelInstance = null;
		if (method_exists($model_data, 'getModel')) {
			$modelInstance = $model_data->getModel();
		} elseif (is_object($model_data) && method_exists($model_data, 'newInstance')) {
			$modelInstance = $model_data->newInstance();
		}
		
		if ($modelInstance === null) {
			\Log::debug('Datatables: Cannot validate relations - no model instance', [
				'table' => $table_name,
				'relations' => $relationNames
			]);
			return [];
		}
		
		foreach ($relationNames as $relationName) {
			// For nested relations (e.g., 'user.profile'), check only the first level
			$firstLevel = explode('.', $relationName)[0];
			
			// Check if the relationship method exists on the model
			if (method_exists($modelInstance, $firstLevel)) {
				try {
					// Try to call the relationship method to verify it's a valid relationship
					$relation = $modelInstance->{$firstLevel}();
					
					// Check if it returns a Relation instance
					if ($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
						$validRelations[] = $relationName;
					}
				} catch (\Exception $e) {
					\Log::debug('Datatables: Relation method exists but is not a valid relationship', [
						'table' => $table_name,
						'relation' => $relationName,
						'error' => $e->getMessage()
					]);
				}
			} else {
				\Log::debug('Datatables: Skipping non-existent relation (likely custom relational field via JOIN)', [
					'table' => $table_name,
					'relation' => $relationName
				]);
			}
		}
		
		return $validRelations;
	}
	
	/**
	 * Select only required columns to avoid SELECT *
	 * 
	 * PERFORMANCE: Reduces data transfer from DB by selecting only the columns
	 * that are actually displayed in the table. Falls back to table.* if no
	 * column list is configured.
	 * 
	 * Only applied when there are no JOIN operations (joins manage their own select).
	 *
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param object $data Table configuration
	 * @param string $table_name Database table name
	 * @return mixed Model with select applied
	 */
	private function selectRequiredColumns(mixed $model_data, object $data, string $table_name): mixed {
		// Check if select optimization is enabled
		if (!config('canvastack.datatables.performance.select_required_only', true)) {
			return $model_data;
		}
		
		$lists = $data->datatables->columns[$table_name]['lists'] ?? null;
		
		// Debug: log available column keys vs requested table_name
		$availableKeys = isset($data->datatables->columns) ? array_keys((array)$data->datatables->columns) : [];
		\Log::debug('Datatables::selectRequiredColumns', [
			'table_name' => $table_name,
			'available_column_keys' => $availableKeys,
			'key_found' => in_array($table_name, $availableKeys),
			'lists_count' => $lists ? count($lists) : 0,
			'lists' => $lists ?? 'NULL'
		]);
		
		if (empty($lists) || !is_array($lists)) {
			\Log::warning('Datatables::selectRequiredColumns - no lists found, using SELECT *', [
				'table' => $table_name
			]);
			return $model_data;
		}
		
		// Check if 'id' column exists in the table schema
		$requiredColumns = $lists;
		try {
			$tableColumns = \DB::connection($model_data->getConnection()->getName())
				->getSchemaBuilder()
				->getColumnListing($table_name);
			
			// Only include 'id' if it exists in the table
			if (in_array('id', $tableColumns)) {
				$requiredColumns = array_merge(['id'], $lists);
			}
		} catch (\Exception $e) {
			// If schema check fails, try to include id anyway (backward compatibility)
			// The query will fail if id doesn't exist, which is expected behavior
			\Log::warning('Datatables: Could not check table schema', [
				'table' => $table_name,
				'error' => $e->getMessage()
			]);
			$requiredColumns = array_merge(['id'], $lists);
		}
		
		$requiredColumns = array_unique($requiredColumns);
		
		// Prefix with table name to avoid ambiguity
		$selectColumns = array_map(function($col) use ($table_name) {
			// Skip columns that already have a table prefix or are expressions
			if (strpos($col, '.') !== false || strpos($col, '(') !== false) {
				return $col;
			}
			return "{$table_name}.{$col}";
		}, $requiredColumns);
		
		return $model_data->select($selectColumns);
	}
	
	/**
	 * Log query performance metrics and warn on slow queries
	 * 
	 * PERFORMANCE: Tracks execution time per table and logs slow queries
	 * (exceeding SLOW_QUERY_THRESHOLD_MS) to the Laravel log for investigation.
	 *
	 * @param string $tableName Table/method name being processed
	 * @param float  $startTime microtime(true) from start of process()
	 * @return void
	 */
	private function logQueryPerformance(string $tableName, float $startTime): void {
		$elapsedMs = (microtime(true) - $startTime) * 1000;
		
		$this->queryMetrics[$tableName] = [
			'elapsed_ms' => round($elapsedMs, 2),
			'timestamp'  => now()->toIso8601String(),
		];
		
		// DEVELOPMENT: Log all queries if enabled (Phase 3: Development Settings)
		if (config('canvastack.datatables.development.log_queries', false)) {
			\Log::channel('daily')->info('[DEV] Datatables: Query executed', [
				'table'      => $tableName,
				'elapsed_ms' => round($elapsedMs, 2),
			]);
		}
		
		// DEVELOPMENT: Log performance metrics if enabled (Phase 3: Development Settings)
		if (config('canvastack.datatables.development.log_performance_metrics', false)) {
			$memoryUsage = memory_get_usage(true);
			$peakMemory = memory_get_peak_usage(true);
			
			\Log::channel('daily')->info('[DEV] Datatables: Performance metrics', [
				'table'         => $tableName,
				'elapsed_ms'    => round($elapsedMs, 2),
				'memory_mb'     => round($memoryUsage / 1024 / 1024, 2),
				'peak_memory_mb'=> round($peakMemory / 1024 / 1024, 2),
			]);
		}
		
		// Use config for slow query logging
		if (config('canvastack.datatables.performance.log_slow_queries', true)) {
			$threshold = config('canvastack.datatables.performance.slow_query_threshold', 1000); // in milliseconds
			
			if ($elapsedMs >= $threshold) {
				$channel = config('canvastack.datatables.performance.slow_query_log_channel', 'daily');
				\Log::channel($channel)->warning('[PERFORMANCE] Datatables: Slow query detected', [
					'table'      => $tableName,
					'elapsed_ms' => round($elapsedMs, 2),
					'threshold'  => $threshold,
					'context'    => 'Consider adding indexes or optimizing query conditions',
				]);
			}
		}
	}
	
	/**
	 * Get query performance metrics collected during this request
	 * 
	 * @return array Metrics keyed by table name with elapsed_ms and timestamp
	 */
	public function getQueryMetrics(): array {
		return $this->queryMetrics;
	}
	
	/**
	 * Apply conditions (where clauses) to model
	 * 
	 * Applies configured where conditions from table configuration.
	 * Supports both regular where and whereIn conditions.
	 *
	 * @param mixed $model_data Eloquent model or query builder instance
	 * @param object $data Table configuration with datatables.conditions
	 * @param string $table_name Database table name
	 * 
	 * @return mixed Model with conditions applied
	 */
	private function applyConditions(mixed $model_data, object $data, string $table_name): mixed {
		$model_condition  = $model_data;
		$where_conditions = [];
		
		if (isset($data->datatables->conditions[$table_name]['where'])) {
			foreach ($data->datatables->conditions[$table_name]['where'] as $conditional_where) {
				$fieldName = $conditional_where['field_name'] ?? '';
				$operator  = $conditional_where['operator'] ?? '=';
				$value     = $conditional_where['value'] ?? null;
				
				// @security 1.7.4 - Validate field name format before using in query builder
				// Query builder provides parameter binding for values automatically
				$safeFieldName = preg_replace('/[^a-zA-Z0-9_.]/', '', $fieldName);
				if ($safeFieldName !== $fieldName) {
					\Log::warning('[SECURITY] Datatables::applyConditions() - Suspicious field name sanitized', [
						'original'  => $fieldName,
						'sanitized' => $safeFieldName,
						'table'     => $table_name,
						'context'   => 'SQL injection prevention - parameter binding'
					]);
				}
				
				// INTEGRATION: Validate operator (Phase 1 Security)
				try {
					$operator = $this->validateOperator($operator);
				} catch (\InvalidArgumentException $e) {
					\Log::warning('[SECURITY] Datatables::applyConditions() - Invalid operator, defaulting to =', [
						'operator' => $operator,
						'table'    => $table_name,
						'error'    => $e->getMessage()
					]);
					$operator = '=';
				}
				
				if (!is_array($value)) {
					// Query builder ->where() uses PDO parameter binding for $value automatically
					$where_conditions['o'][] = [$safeFieldName, $operator, $value];
				} else {
					// Query builder ->whereIn() uses PDO parameter binding for $value automatically
					$where_conditions['i'][$safeFieldName] = $value;
				}
			}
			
			if (isset($where_conditions['o'])) {
				$model_condition = $model_data->where($where_conditions['o']);
			}
			
			if (isset($where_conditions['i'])) {
				foreach ($where_conditions['i'] as $if => $iv) {
					$model_condition = $model_condition->whereIn($if, $iv);
				}
			}
			
			// @performance 6.5 - Free large where conditions array after it has been applied
			unset($where_conditions);
		}
		
		return $model_condition;
	}

	/**
	 * Apply filters to model
	 *
	 * @param mixed $model_condition
	 * @param array $filters
	 * @param string $table_name
	 * @param string $firstField
	 * @return array
	 */
	/**
	 * Apply filters to model
	 * Refactored to reduce nesting from 6 to 2 levels
	 * 
	 * @param mixed $model_condition Model with conditions
	 * @param array $filters Filter data
	 * @param string $table_name Table name
	 * @param string $firstField First field name
	 * @return array [model, limitTotal]
	 */
	private function applyFilters(mixed $model_condition, array $filters, string $table_name, string $firstField): array {
		// Parse filter strings from input
		$fstrings = $this->parseFilterStrings($filters);
		
		if (empty($fstrings)) {
			return $this->applyDefaultFilter($model_condition, $table_name, $firstField);
		}
		
		// Transform filters to usable format
		$transformedFilters = $this->transformFilters($fstrings);
		
		// Apply filter conditions to model
		return $this->applyFilterConditions($model_condition, $transformedFilters);
	}
	
	/**
	 * Parse filter strings from input
	 * 
	 * @param array $filters Filter input
	 * @return array Parsed filter strings
	 */
	private function parseFilterStrings(array $filters): array {
		if (!is_array($filters) || empty($filters)) {
			return [];
		}
		
		$fstrings = [];
		
		foreach ($filters as $name => $value) {
			if ('filters' === $name || '' === $value) {
				continue;
			}
			
			// @performance 6.6 - Use strict mode (true) for type-safe comparison
			if (in_array($name, self::AJAX_RESERVED_PARAMS, true)) {
				continue;
			}
			
			if (!is_array($value)) {
				$fstrings[] = [$name => urldecode($value)];
			} else {
				foreach ($value as $val) {
					$fstrings[] = [$name => urldecode($val)];
				}
			}
		}
		
		return $fstrings;
	}
	
	/**
	 * Transform filter strings to usable format
	 *
	 * @performance 6.3 - Collapsed two-pass loop into a single pass: instead of first
	 *              grouping all values per field and then iterating again to pick the
	 *              last one, we overwrite the entry on every iteration so only the last
	 *              value survives. This halves the number of iterations and eliminates
	 *              the intermediate $filters array entirely.
	 *
	 * @param array $fstrings Parsed filter strings
	 * @return array Transformed filters (last value wins per field)
	 */
	private function transformFilters(array $fstrings): array {
		$fconds = [];
		
		// Single pass: overwrite on each occurrence so the last value wins per field.
		foreach ($fstrings as $fdata) {
			foreach ($fdata as $fkey => $fvalue) {
				$fconds[$fkey] = $fvalue;
			}
		}
		
		return $fconds;
	}
	
	/**
	 * Apply filter conditions to model
	 * 
	 * @param mixed $model_condition Model with conditions
	 * @param array $fconds Filter conditions
	 * @return array [model, limitTotal]
	 */
	/**
	 * Apply filter conditions to model
	 * 
	 * PERFORMANCE: Uses DB-level COUNT(*) instead of loading all rows into memory.
	 * 
	 * @param mixed $model_condition Model with conditions
	 * @param array $fconds Filter conditions
	 * @return array [model, limitTotal]
	 */
	private function applyFilterConditions(mixed $model_condition, array $fconds): array {
		if (empty($fconds)) {
			return [
				'model'      => $model_condition,
				'limitTotal' => 0
			];
		}
		
		$model = $model_condition->where($fconds);
		
		// PERFORMANCE: Use DB-level COUNT instead of loading all rows into memory
		$limitTotal = $model->count();
		
		return [
			'model'      => $model,
			'limitTotal' => $limitTotal
		];
	}
	
	/**
	 * Apply default filter (no filters provided)
	 * 
	 * PERFORMANCE: Uses DB-level COUNT(*) instead of loading all rows into memory.
	 * 
	 * @param mixed $model_condition Model with conditions
	 * @param string $table_name Table name
	 * @param string $firstField First field name
	 * @return array [model, limitTotal]
	 */
	private function applyDefaultFilter(mixed $model_condition, string $table_name, string $firstField): array {
		$model = $model_condition->where("{$table_name}.{$firstField}", '!=', null);
		
		// PERFORMANCE: Use DB-level COUNT instead of loading all rows into memory
		$limitTotal = $model->count();
		
		return [
			'model'      => $model,
			'limitTotal' => $limitTotal
		];
	}

	/**
	 * Apply pagination to model
	 * 
	 * PERFORMANCE: Applies LIMIT/OFFSET at database level.
	 * Validates and clamps pagination parameters to safe ranges.
	 *
	 * @param mixed $model
	 * @param int $limitTotal
	 * @return array Pagination config with keys: start, length, total, model
	 */
	private function applyPagination(mixed $model, int $limitTotal): array {
		$start  = self::DEFAULT_LIMIT_START;
		$length = self::DEFAULT_LIMIT_LENGTH;
		
		$requestStart  = request()->get('start');
		$requestLength = request()->get('length');
		
		if (!empty($requestStart))  $start  = max(0, intval($requestStart));
		if (!empty($requestLength)) $length = min(max(1, intval($requestLength)), 1000);
		
		$limit = [
			'start'  => $start,
			'length' => $length,
			'total'  => intval($limitTotal),
			// PERFORMANCE: Apply LIMIT/OFFSET at DB level - must assign result back
			'model'  => $model->skip($start)->take($length)
		];
		
		return $limit;
	}
	
	/**
	 * Build datatables instance
	 * 
	 * PERFORMANCE: Uses the paginated model (with LIMIT/OFFSET applied) from
	 * the limit array so DataTables only fetches the current page of data.
	 *
	 * @param mixed $model
	 * @param array $limit
	 * @param array $blacklists
	 * @return mixed
	 */
	private function buildDatatables(mixed $model, array $limit, array $blacklists): mixed {
		// PERFORMANCE: Use paginated model if available (has LIMIT/OFFSET applied)
		$paginatedModel = $limit['model'] ?? $model;
		
		$datatables = DataTable::of($paginatedModel)
			->setTotalRecords($limit['total'])
			->setFilteredRecords($limit['total'])
			->blacklist($blacklists)
			->smart(true);
		
		// Setup raw columns untuk image fields
		// @performance 6.3 - Use array_merge instead of array_merge_recursive; no nested arrays here,
		//                     so the simpler (and faster) merge is sufficient.
		if (isset($this->form->imageTagFieldsDatatable)) {
			$is_image = array_keys($this->form->imageTagFieldsDatatable);
			$datatables->rawColumns(array_merge(['action', 'flag_status'], $is_image));
		}
		
		return $datatables;
	}
	
	/**
	 * Apply ordering to datatables
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	private function applyOrdering(mixed $datatables, object $data, string $table_name): void {
		$order_by = [];
		if (isset($data->datatables->columns[$table_name]['orderby'])) {
			$order_by = $data->datatables->columns[$table_name]['orderby'];
		}
		
		if (!empty($order_by)) {
			// @security 1.7.7 - Validate column name and direction before using in ORDER BY
			$orderColumn    = preg_replace('/[^a-zA-Z0-9_.]/', '', $order_by['column'] ?? '');
			$rawDirection   = trim($order_by['order'] ?? 'asc');
			
			if ($orderColumn !== ($order_by['column'] ?? '')) {
				\Log::warning('[SECURITY] Datatables::applyOrdering() - Column name sanitized', [
					'original'  => $order_by['column'] ?? '',
					'sanitized' => $orderColumn,
					'table'     => $table_name,
					'context'   => 'SQL injection prevention - ORDER BY column validation'
				]);
			}
			
			// Use new validateSortDirection method with config
			try {
				$orderDirection = $this->validateSortDirection($rawDirection);
			} catch (\InvalidArgumentException $e) {
				\Log::warning('[SECURITY] Datatables::applyOrdering() - Invalid sort direction, defaulting to asc', [
					'direction' => $rawDirection,
					'table'     => $table_name,
					'context'   => 'SQL injection prevention - ORDER BY direction validation',
					'error'     => $e->getMessage()
				]);
				$orderDirection = 'asc';
			}
			
			$orderBy = ['column' => $orderColumn, 'order' => $orderDirection];
			$datatables->order(function ($query) use($orderBy) {
				$query->orderBy($orderBy['column'], $orderBy['order']);
			});
		}
	}

	/**
	 * Process rows - apply formulas, formatting, dan special columns
	 *
	 * @param mixed $model
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @param array $joinFields
	 * @return void
	 */
	/**
	 * Process rows with relations and status columns
	 * 
	 * PERFORMANCE: Uses limit(1)->get() to detect column types for image/status
	 * rendering, then passes the result to DataTables. The DataTables library
	 * handles the actual row iteration internally.
	 *
	 * When the total row count exceeds LARGE_DATASET_THRESHOLD, a log entry is
	 * emitted to confirm that chunked processing is available via
	 * processLargeDatasetChunked() for any full-dataset operations.
	 * 
	 * @performance Memory Management (Requirement 6.1) - uses limit(1) for column-type
	 *              detection to avoid loading the full result set into memory.
	 *
	 * @param mixed  $model      Model instance (filtered, no LIMIT/OFFSET)
	 * @param mixed  $datatables Datatables instance
	 * @param object $data       Table data
	 * @param string $table_name Table name
	 * @param array  $joinFields Join fields
	 * @param int    $totalRows  Total filtered row count (used for chunking decision)
	 * @return void
	 */
	private function processRows($model, $datatables, $data, $table_name, $joinFields, int $totalRows = 0) {
		$object_called = get_object_called_name($model);

		// PERFORMANCE: Log when dataset is large so operators are aware chunking is active.
		if ($this->shouldUseChunking($totalRows)) {
			\Log::info('Datatables: Large dataset detected, chunking available for full-dataset operations', [
				'table'      => $table_name,
				'total_rows' => $totalRows,
				'threshold'  => self::LARGE_DATASET_THRESHOLD,
				'chunk_size' => self::CHUNK_SIZE,
			]);
		}

		// PERFORMANCE: Fetch only the first row for column-type detection (image/status columns).
		// DataTables::of() will re-execute the query internally; this fetch is only
		// used to inspect the first row's attributes for column presence checks.
		// Using limit(1) avoids loading the entire result set into memory, which is
		// critical for large datasets (Requirement 6.1 - Memory Management).
		try {
			$rows = $model->limit(1)->get();
		} catch (\Exception $e) {
			// If query fails (e.g., missing 'id' column in views), try without explicit select
			\Log::warning('Datatables: First row fetch failed, retrying without column selection', [
				'table' => $table_name,
				'error' => $e->getMessage()
			]);
			
			// Clone model and clear select to let Eloquent use SELECT *
			$modelClone = clone $model;
			$rows = $modelClone->select('*')->limit(1)->get();
		}
		
		foreach ($rows as $modelData) {
			$rowModel = $this->extractRowModel($modelData, $object_called);
			
			// Process image columns
			$this->imageViewColumn($rowModel, $datatables);
			
			// Process relations if no joins
			if (empty($joinFields)) {
				$this->processRelations($datatables, $data, $table_name);
			}
			
			// Process special status columns
			$this->processStatusColumns($datatables, $rowModel);
		}
		
		// @performance 6.5 - Free the sample row result set after column-type detection is done
		unset($rows);
		
		// Apply formulas
		$this->applyFormulas($datatables, $data, $table_name);
		
		// Apply data formatting
		$this->applyDataFormatting($datatables, $data, $table_name);
	}
	
	/**
	 * Extract row model from model data
	 * 
	 * @param mixed $modelData Model data
	 * @param string $object_called Object type
	 * @return object Row model
	 */
	private function extractRowModel(mixed $modelData, string $object_called): object {
		if ('builder' === $object_called) {
			return (object) $modelData->getAttributes();
		}
		
		return $modelData;
	}
	
	/**
	 * Process relations for display in DataTables
	 * 
	 * Handles both simple and nested relationships (e.g., user.profile.avatar).
	 * Supports multi-level relationship chains.
	 * Optimizes nested relationship queries through eager loading.
	 * 
	 * SECURITY: Escapes all relation field values to prevent XSS
	 * PERFORMANCE: Uses eager-loaded data (no additional queries)
	 * 
	 * Examples:
	 * - Simple: 'user' => displays user name
	 * - Nested: 'user.profile' => displays user's profile data
	 * - Deep: 'user.profile.avatar' => displays user's profile avatar
	 * 
	 * @param mixed $datatables DataTables instance
	 * @param object $data Table data configuration
	 * @param string $table_name Table name
	 * 
	 * @return void
	 */
	private function processRelations(mixed $datatables, object $data, string $table_name): void {
		if (!isset($data->datatables->columns[$table_name]['relations'])) {
			return;
		}
		
		foreach ($data->datatables->columns[$table_name]['relations'] as $relField => $relData) {
			// Check if this is a nested relationship (contains dots)
			if (strpos($relField, '.') !== false) {
				// Handle nested relationship
				$this->processNestedRelation($datatables, $relField, $relData);
			} else {
				// Handle simple relationship (backward compatibility)
				$this->processSimpleRelation($datatables, $relField, $relData);
			}
		}
	}
	
	/**
	 * Process simple (non-nested) relationship
	 * 
	 * Maintains backward compatibility with existing relation_data format.
	 * 
	 * @param mixed $datatables DataTables instance
	 * @param string $relField Relation field name
	 * @param array $relData Relation configuration with relation_data
	 * 
	 * @return void
	 */
	private function processSimpleRelation(mixed $datatables, string $relField, array $relData): void {
		$dataRelations = $relData['relation_data'] ?? [];
		
		$datatables->editColumn($relField, function($data) use ($dataRelations) {
			$dataID = intval($data['id'] ?? 0);
			
			if (isset($dataRelations[$dataID]['field_value'])) {
				// SECURITY: Escape relation field value to prevent XSS
				return $this->escapeData($dataRelations[$dataID]['field_value']);
			}
			
			return null;
		});
	}
	
	/**
	 * Process nested relationship (e.g., user.profile.avatar)
	 * 
	 * Handles multi-level relationship chains by traversing the object graph.
	 * Supports both Eloquent relationships and array access.
	 * 
	 * @param mixed $datatables DataTables instance
	 * @param string $relField Nested relation field (e.g., 'user.profile.name')
	 * @param array $relData Relation configuration
	 * 
	 * @return void
	 */
	private function processNestedRelation(mixed $datatables, string $relField, array $relData): void {
		// Parse the nested relationship path
		$relationPath = explode('.', $relField);
		$displayField = $relData['display_field'] ?? end($relationPath);
		
		$datatables->editColumn($relField, function($model) use ($relationPath, $displayField) {
			try {
				// Traverse the relationship chain
				$value = $this->traverseNestedRelation($model, $relationPath, $displayField);
				
				if ($value !== null) {
					// SECURITY: Escape the value to prevent XSS
					return $this->escapeData($value);
				}
				
				return null;
			} catch (\Exception $e) {
				// Log error but don't break the table
				\Log::warning('Datatables: Failed to process nested relation', [
					'relation' => $relField,
					'error' => $e->getMessage()
				]);
				return null;
			}
		});
	}
	
	/**
	 * Traverse nested relationship to get final value
	 * 
	 * Walks through the relationship chain (e.g., user -> profile -> avatar)
	 * and retrieves the specified display field value.
	 * 
	 * Supports both object properties and array access for flexibility.
	 * 
	 * @param mixed $model Current model/data object
	 * @param array $relationPath Path segments (e.g., ['user', 'profile', 'avatar'])
	 * @param string $displayField Final field to display
	 * 
	 * @return mixed|null The value at the end of the chain, or null if not found
	 */
	private function traverseNestedRelation(mixed $model, array $relationPath, string $displayField): mixed {
		$current = $model;
		
		// Traverse each level of the relationship
		foreach ($relationPath as $segment) {
			if ($current === null) {
				return null;
			}
			
			// Try object property access first (Eloquent models)
			if (is_object($current) && isset($current->$segment)) {
				$current = $current->$segment;
			}
			// Try array access (for arrays or ArrayAccess objects)
			elseif (is_array($current) && isset($current[$segment])) {
				$current = $current[$segment];
			}
			// Relationship not found
			else {
				return null;
			}
		}
		
		// Get the display field from the final object
		if ($current !== null) {
			// Try object property
			if (is_object($current) && isset($current->$displayField)) {
				return $current->$displayField;
			}
			// Try array access
			elseif (is_array($current) && isset($current[$displayField])) {
				return $current[$displayField];
			}
			// If no display field specified, return the object itself (will be converted to string)
			elseif ($displayField === end($relationPath)) {
				return $current;
			}
		}
		
		return null;
	}

	/**
	 * Determine whether chunked processing should be used for a dataset.
	 *
	 * Chunking is activated when the total row count exceeds LARGE_DATASET_THRESHOLD
	 * (default: 1000 rows). Below this threshold the overhead of chunking is not
	 * justified and a regular query is used instead.
	 *
	 * @performance Memory Management (Requirement 6.1) - guards against memory
	 *              exhaustion when processing large result sets.
	 *
	 * @param int $totalRows Total number of rows in the dataset
	 * @return bool True when chunked processing should be used
	 */
	private function shouldUseChunking(int $totalRows): bool {
		return $totalRows > self::LARGE_DATASET_THRESHOLD;
	}

	/**
	 * Process a large dataset in memory-safe chunks using Laravel's chunk().
	 *
	 * When the total row count exceeds LARGE_DATASET_THRESHOLD, this method
	 * iterates the query in batches of CHUNK_SIZE rows rather than loading the
	 * entire result set into memory at once. Each chunk is processed by the
	 * provided callback and the results are accumulated into a flat array.
	 *
	 * The output format is identical to a regular ->get() call so callers are
	 * fully backward-compatible.
	 *
	 * @performance Memory Management (Requirement 6.1) - prevents memory exhaustion
	 *              for datasets larger than LARGE_DATASET_THRESHOLD rows by processing
	 *              CHUNK_SIZE rows at a time (default: 500 rows per chunk).
	 *
	 * @param mixed    $query      Eloquent query builder instance (must support chunk())
	 * @param int      $totalRows  Total number of rows (used to decide chunk vs. get)
	 * @param callable $callback   Called with each Illuminate\Support\Collection chunk;
	 *                             should return an array of processed rows for that chunk
	 * @return array Flat array of all processed rows, identical in structure to a
	 *               direct ->get() result processed by the same callback
	 */
	private function processLargeDatasetChunked(mixed $query, int $totalRows, callable $callback): array {
		$results = [];

		if (!$this->shouldUseChunking($totalRows)) {
			// Small dataset: process all rows at once (no chunking overhead)
			$rows = $query->get();
			$results = $callback($rows);
		} else {
			// Large dataset: process in memory-safe chunks
			\Log::info('Datatables: Using chunked processing for large dataset', [
				'total_rows' => $totalRows,
				'chunk_size' => self::CHUNK_SIZE,
				'threshold'  => self::LARGE_DATASET_THRESHOLD,
			]);

			$query->chunk(self::CHUNK_SIZE, function ($chunk) use ($callback, &$results) {
				$chunkResults = $callback($chunk);
				if (is_array($chunkResults)) {
					foreach ($chunkResults as $row) {
						$results[] = $row;
					}
				}
			});
		}

		return $results;
	}

	/**
	 * Process special status columns
	 *
	 * @security XSS Prevention - status column values that are plain text (ip_address)
	 *           are escaped with htmlspecialchars before output. Columns that return
	 *           trusted HTML from internal helpers (flag_status, active, etc.) are
	 *           already escaped by those helpers and returned via canvastack_unescape_html.
	 *
	 * @param mixed $datatables
	 * @param object $rowModel
	 * @return void
	 */
	private function processStatusColumns(mixed $datatables, object $rowModel): void {
		if (isset($rowModel->flag_status)) {
			$datatables->editColumn('flag_status', function($model) {
				return canvastack_unescape_html(canvastack_form_internal_flag_status($model->flag_status));
			});
		}
		
		if (isset($rowModel->active)) {
			$datatables->editColumn('active', function($model) {
				return canvastack_form_set_active_value($model->active);
			});
		}
		
		if (isset($rowModel->update_status)) {
			$datatables->editColumn('update_status', function($model) {
				return canvastack_form_set_active_value($model->update_status);
			});
		}
		
		if (isset($rowModel->request_status)) {
			$datatables->editColumn('request_status', function($model) {
				return canvastack_form_request_status(true, $model->request_status);
			});
		}
		
		if (isset($rowModel->ip_address)) {
			$datatables->editColumn('ip_address', function($model) {
				if ('::1' == $model->ip_address) {
					return $this->escapeData(canvastack_form_get_client_ip());
				}
				// SECURITY: Escape IP address to prevent XSS
				return $this->escapeData($model->ip_address);
			});
		}
	}

	/**
	 * Apply formulas to columns
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	private function applyFormulas(mixed $datatables, object $data, string $table_name): void {
		if (isset($data->datatables->formula[$table_name])) {
			$data_formula = $data->datatables->formula[$table_name];
			$data->datatables->columns[$table_name]['lists'] = canvastack_set_formula_columns(
				$data->datatables->columns[$table_name]['lists'], 
				$data_formula
			);
			
			foreach ($data_formula as $formula) {
				$datatables->editColumn($formula['name'], function($data) use ($formula) {
					$logic = new Formula($formula, $data);
					return $logic->calculate();
				});
			}
		}
	}
	
	/**
	 * Apply data formatting to columns
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	/**
	 * Apply data formatting to columns
	 *
	 * @security XSS Prevention - formatted numeric/string values are returned as
	 *           plain text scalars. The canvastack_format() helper returns a
	 *           formatted string that is safe for display (no HTML tags).
	 *           Non-formatted fallback returns null (no output).
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	private function applyDataFormatting(mixed $datatables, object $data, string $table_name): void {
		// INTEGRATION: Apply config-based formatting first (Phase 3)
		$this->applyConfigBasedFormatting($datatables, $data, $table_name);
		
		// Then apply custom formatting from data configuration
		if (isset($data->datatables->columns[$table_name]['format_data'])) {
			$data_format = $data->datatables->columns[$table_name]['format_data'];
			
			foreach ($data_format as $field => $format) {
				$datatables->editColumn($format['field_name'], function($data) use ($field, $format) {
					if ($field === $format['field_name']) {
						$dataValue = $data->getAttributes();
						if (isset($dataValue[$field])) {
							// SECURITY: canvastack_format() returns a numeric/formatted string;
							// escape it to prevent XSS if the value contains special characters.
							return $this->escapeData(canvastack_format(
								$dataValue[$field], 
								$format['decimal_endpoint'], 
								$format['separator'], 
								$format['format_type']
							));
						}
					}
					return null;
				});
			}
		}
	}
	
	/**
	 * Apply config-based column formatting
	 * 
	 * Phase 3: Column Formatting Integration
	 * Automatically formats columns based on their type and config settings
	 * 
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	private function applyConfigBasedFormatting(mixed $datatables, object $data, string $table_name): void {
		// Check if column types are defined
		if (!isset($data->datatables->columns[$table_name]['types'])) {
			return;
		}
		
		$columnTypes = $data->datatables->columns[$table_name]['types'];
		
		foreach ($columnTypes as $columnName => $columnType) {
			// Skip if already has custom formatting
			if (isset($data->datatables->columns[$table_name]['format_data'][$columnName])) {
				continue;
			}
			
			// Apply formatting based on type
			$datatables->editColumn($columnName, function($model) use ($columnName, $columnType) {
				$value = $model->$columnName ?? null;
				
				if ($value === null) {
					return '';
				}
				
				// INTEGRATION: Use formatColumnValue (Phase 3)
				return $this->escapeData($this->formatColumnValue($value, $columnType));
			});
		}
	}
	
	/**
	 * Format column value based on type and configuration
	 * 
	 * Implements Phase 3: Column Formatting (6 options)
	 * - columns.date_format
	 * - columns.datetime_format
	 * - columns.time_format
	 * - columns.decimal_places
	 * - columns.thousand_separator
	 * - columns.decimal_separator
	 * 
	 * @param mixed $value Column value to format
	 * @param string $type Column type (date, datetime, time, decimal, float, integer, string)
	 * @return string Formatted value
	 */
	private function formatColumnValue($value, string $type): string {
		if ($value === null || $value === '') {
			return '';
		}
		
		switch ($type) {
			case 'date':
				$format = config('canvastack.datatables.columns.date_format', 'Y-m-d');
				try {
					return date($format, strtotime($value));
				} catch (\Exception $e) {
					\Log::warning('Datatables: Date formatting failed', [
						'value' => $value,
						'format' => $format,
						'error' => $e->getMessage()
					]);
					return (string) $value;
				}
				
			case 'datetime':
				$format = config('canvastack.datatables.columns.datetime_format', 'Y-m-d H:i:s');
				try {
					return date($format, strtotime($value));
				} catch (\Exception $e) {
					\Log::warning('Datatables: DateTime formatting failed', [
						'value' => $value,
						'format' => $format,
						'error' => $e->getMessage()
					]);
					return (string) $value;
				}
				
			case 'time':
				$format = config('canvastack.datatables.columns.time_format', 'H:i:s');
				try {
					return date($format, strtotime($value));
				} catch (\Exception $e) {
					\Log::warning('Datatables: Time formatting failed', [
						'value' => $value,
						'format' => $format,
						'error' => $e->getMessage()
					]);
					return (string) $value;
				}
				
			case 'decimal':
			case 'float':
			case 'double':
				$decimals = config('canvastack.datatables.columns.decimal_places', 2);
				$decSep = config('canvastack.datatables.columns.decimal_separator', '.');
				$thousandSep = config('canvastack.datatables.columns.thousand_separator', ',');
				
				try {
					return number_format((float)$value, $decimals, $decSep, $thousandSep);
				} catch (\Exception $e) {
					\Log::warning('Datatables: Number formatting failed', [
						'value' => $value,
						'decimals' => $decimals,
						'error' => $e->getMessage()
					]);
					return (string) $value;
				}
				
			case 'integer':
			case 'int':
				$thousandSep = config('canvastack.datatables.columns.thousand_separator', ',');
				try {
					return number_format((int)$value, 0, '', $thousandSep);
				} catch (\Exception $e) {
					return (string) $value;
				}
				
			case 'string':
			case 'text':
			default:
				return (string) $value;
		}
	}

	/**
	 * Setup row attributes (clickable rows)
	 *
	 * @security XSS Prevention - row_attributes array is validated through
	 *           validateAttributeKeys() to strip dangerous event-handler attributes
	 *           before being passed to setRowAttr(). The 'class' value is a
	 *           hardcoded safe string, not user-controlled.
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @param string $table_name
	 * @return void
	 */
	private function setupRowAttributes(mixed $datatables, object $data, string $table_name): void {
		$rlp                     = false;
		$row_attributes          = [];
		$row_attributes['class'] = null;
		$row_attributes['rlp']   = null;
		
		if (isset($data->datatables->columns[$table_name]['clickable'])) {
			if (count($data->datatables->columns[$table_name]['clickable']) >= 1) {
				$rlp = function($model) { 
					return canvastack_unescape_html(encode_id(intval($model->id))); 
				};
			}
			$row_attributes['class'] = 'row-list-url';
			$row_attributes['rlp']   = $rlp;
		}
		
		// SECURITY: Validate attributes to strip dangerous event handlers
		$row_attributes = $this->validateAttributeKeys($row_attributes);
		
		$datatables->setRowAttr($row_attributes);
	}
	
	/**
	 * Add action column to datatables
	 *
	 * @param mixed $datatables
	 * @param mixed $model
	 * @param array $actionConfig
	 * @param object $data
	 * @return void
	 */
	private function addActionColumn(mixed $datatables, mixed $model, array $actionConfig, object $data): void {
		$action_data                   = [];
		$action_data['model']          = $model;
		$action_data['current_url']    = canvastack_current_url();
		$action_data['action']['data'] = $actionConfig['action_list'];
		
		if ($actionConfig['privileges']['role_group'] > 1) {
			if (!empty($actionConfig['removed_privileges'])) {
				$action_data['action']['removed'] = $actionConfig['removed_privileges'];
			} else {
				$action_data['action']['removed'] = $data->datatables->button_removed ?? [];
			}
		} else {
			$action_data['action']['removed'] = $data->datatables->button_removed ?? [];
		}
		
		if (!empty($actionConfig['buttonsRemoval'])) {
			$removeActions = $action_data['action']['removed'];
			$action_data['action']['removed'] = array_merge_recursive_distinct($actionConfig['buttonsRemoval'], $removeActions);
		}
		
		$urlTarget = $data->datatables->useFieldTargetURL ?? 'id';
		
		$datatables->addColumn('action', function($model) use($action_data, $urlTarget) {
			return $this->setRowActionURLs($model, $action_data, $urlTarget);
		});
	}

	/**
	 * Generate final table data
	 *
	 * @param mixed $datatables
	 * @param object $data
	 * @return mixed
	 */
	private function generateTableData(mixed $datatables, object $data): mixed {
		$index_lists = $data->datatables->records['index_lists'];
		
		if (true === $index_lists) {
			$result = $datatables->addIndexColumn()->make(true);
		} else {
			$result = $datatables->make();
		}
		
		// Debug: log how many columns are in the first data row
		if (is_object($result) && method_exists($result, 'getData')) {
			$responseData = $result->getData(true);
			if (!empty($responseData['data'][0])) {
				\Log::debug('Datatables::generateTableData - response column count', [
					'column_count' => count($responseData['data'][0]),
					'columns' => array_keys($responseData['data'][0])
				]);
			}
		}
		
		return $result;
	}
	
	/**
	 * Set row action URLs/buttons
	 *
	 * @security XSS Prevention - delegates to canvastack_table_action_button() which
	 *           escapes all button labels and URLs using canvastack_escape_html()
	 *           (htmlspecialchars with ENT_QUOTES) before rendering HTML output.
	 *
	 * @param object $model
	 * @param array $data
	 * @param string $field_target
	 * @return string
	 */
	private function setRowActionURLs(object $model, array $data, string $field_target = 'id'): string {
		return canvastack_table_action_button(
			$model, 
			$field_target, 
			$data['current_url'], 
			$data['action']['data'], 
			$data['action']['removed']
		);
	}
	
	/**
	 * Process image view column
	 *
	 * @param object $model
	 * @param mixed $datatables
	 * @return void
	 */
	/**
	 * Process image view column
	 * Refactored to reduce nesting from 5 to 2 levels
	 * 
	 * @param object $model Row model
	 * @param mixed $datatables Datatables instance
	 * @return void
	 */
	private function imageViewColumn(object $model, mixed $datatables): void {
		// Detect which fields contain images
		$imageFields = $this->detectImageFields($model);
		
		if (empty($imageFields)) {
			return;
		}
		
		// Setup column rendering for each image field
		foreach ($imageFields as $field => $imgSrc) {
			$this->setupImageColumnRendering($field, $datatables);
		}
	}
	
	/**
	 * Detect which fields in model contain valid images
	 * 
	 * @param object $model Row model
	 * @return array Image fields
	 */
	private function detectImageFields(object $model): array {
		$imageFields = [];
		
		foreach ($model as $field => $strImg) {
			$checkImage = $this->checkValidImage($strImg);
			
			if (false !== $checkImage && true === $checkImage) {
				$imageFields[$field] = $checkImage;
			}
		}
		
		return $imageFields;
	}
	
	/**
	 * Setup image column rendering for datatables
	 * 
	 * @param string $field Field name
	 * @param mixed $datatables Datatables instance
	 * @return void
	 */
	private function setupImageColumnRendering(string $field, mixed $datatables): void {
		$datatables->editColumn($field, function($model) use ($field) {
			return $this->renderImageOrFilename($model, $field);
		});
	}
	
	/**
	 * Render image HTML or filename fallback
	 *
	 * @security XSS Prevention - delegates to renderImageColumn() (which escapes
	 *           all HTML attributes) or renderFileNameColumn() (which escapes the
	 *           filename). When checkValidImage() returns an HTML string (missing
	 *           file error), it is already marked with SafeHtml::mark() and is
	 *           unwrapped via SafeHtml::unmark() before passing to canvastack_unescape_html().
	 *
	 * @param object $model Row model
	 * @param string $field Field name
	 * @return string HTML or filename
	 */
	private function renderImageOrFilename(object $model, string $field): string {
		if (!isset($model->{$field})) {
			return '';
		}
		
		$imgCheck = $this->checkValidImage($model->{$field});
		
		if (false === $imgCheck) {
			return $this->renderFileNameColumn($model->{$field});
		}
		
		if (true === $imgCheck) {
			return $this->renderImageColumn($model->{$field}, $field);
		}
		
		// $imgCheck contains SafeHtml-marked HTML string (from missing file error)
		// SECURITY: Unmark SafeHtml before passing to canvastack_unescape_html (1.5.5)
		return canvastack_unescape_html(SafeHtml::unmark($imgCheck));
	}
	
	/**
	 * Render image column with thumbnail support
	 *
	 * @security XSS Prevention - both the file path and the alt label are escaped
	 *           with htmlspecialchars before being embedded in HTML attributes.
	 *           The resulting HTML is marked with SafeHtml::mark() to prevent
	 *           double-encoding downstream.
	 *
	 * @param string $filePath Image file path
	 * @param string $field Field name
	 * @return string Image HTML (marked as SafeHtml)
	 */
	private function renderImageColumn(string $filePath, string $field): string {
		$label = ucwords(str_replace('-', ' ', canvastack_clean_strings($field)));
		
		// Try to get thumbnail path
		$thumbnailPath = $this->getThumbnailPath($filePath);
		$displayPath = $thumbnailPath ?: $filePath;
		
		// SECURITY: Escape untuk mencegah XSS di HTML attribute
		$safeLabel    = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
		$safeFilePath = htmlspecialchars($displayPath, ENT_QUOTES, 'UTF-8');
		$alt          = self::IMAGE_ALT_PREFIX . $safeLabel;
		
		$html = "<center><img class=\"CanvaStack-img-thumb\" src=\"{$safeFilePath}\" alt=\"{$alt}\" /></center>";
		
		// SECURITY: Mark as safe HTML to prevent double-encoding (1.5.5)
		return canvastack_unescape_html(SafeHtml::mark($html));
	}
	
	/**
	 * Get thumbnail path if exists
	 * 
	 * @param string $filePath Original file path
	 * @return string|null Thumbnail path or null
	 */
	private function getThumbnailPath(string $filePath): ?string {
		$pathParts = explode('/', $filePath);
		$lastIndex = array_key_last($pathParts);
		$fileName = $pathParts[$lastIndex] ?? '';
		
		if (empty($fileName)) {
			return null;
		}
		
		// Remove filename from path
		unset($pathParts[$lastIndex]);
		
		// Build thumbnail path
		$thumbPath = implode('/', $pathParts) . '/' . self::THUMBNAIL_FOLDER . '/' . self::THUMBNAIL_PREFIX . $fileName;
		
		// Check if thumbnail exists
		if (!empty($this->setAssetPath($thumbPath))) {
			return $thumbPath;
		}
		
		return null;
	}
	
	/**
	 * Render filename column (fallback when not valid image)
	 *
	 * @security XSS Prevention - the filename extracted from the path is escaped
	 *           with htmlspecialchars before being returned as output.
	 *
	 * @param string $filePath File path
	 * @return string Escaped filename
	 */
	private function renderFileNameColumn(string $filePath): string {
		$pathParts = explode('/', $filePath);
		$lastIndex = array_key_last($pathParts);
		$filename  = $pathParts[$lastIndex] ?? '';
		
		// SECURITY: Escape filename to prevent XSS
		return $this->escapeData($filename);
	}

	/**
	 * Filter datatables - store filter request
	 *
	 * @param mixed $request
	 * @return void
	 */
	public $filter_datatables = [];
	public function filter_datatable(mixed $request): void {
		$this->filter_datatables = $request->all();
	}
	
	/**
	 * Initialize filter datatables for dropdown options
	 * 
	 * Generates distinct values for filter dropdowns in DataTables.
	 * Handles complex filtering with joins, conditions, and previous selections.
	 * 
	 * SECURITY: Enhanced with validation and SQL injection prevention
	 * PERFORMANCE: Refactored with extracted sub-methods for better readability
	 * 
	 * Uses query builder with parameter binding to prevent SQL injection.
	 * Validates table names and connections against whitelists.
	 * 
	 * Filter Format (_fita):
	 * "filterType::tableName::targetField::previousConditions"
	 * 
	 * Example:
	 * ```php
	 * $options = $datatables->init_filter_datatables(
	 *     ['filterDataTables' => true],
	 *     [
	 *         '_fita' => 'select::users::name::#null',
	 *         '_forKeys' => '{"users.role_id":"roles.id"}',
	 *         'status' => 'active'
	 *     ],
	 *     'mysql'
	 * );
	 * ```
	 *
	 * @param array $get GET parameters with:
	 *   - filterDataTables: bool Filter flag (required)
	 * 
	 * @param array $post POST parameters with:
	 *   - _fita: string Filter configuration (format: type::table::field::prev)
	 *   - _forKeys: string JSON encoded foreign key joins (optional)
	 *   - _canvastackF: array Additional filter conditions (optional)
	 *   - grabCanvaStackC: string Connection name override (optional)
	 *   - [field]: mixed Additional where conditions
	 * 
	 * @param string|null $connection Database connection name (optional, uses default if null)
	 * 
	 * @return \Illuminate\Support\Collection|null Collection of distinct values or null on error
	 * 
	 * @throws \InvalidArgumentException If validation fails (caught and logged)
	 * @throws \Exception If database query fails (caught and logged)
	 *
	 * @security SQL Injection Prevention - table names validated via validateTableName(),
	 *           connection names validated via validateConnection(), field names sanitized
	 *           with preg_replace('/[^a-zA-Z0-9_.]/', '', ...) in applyFilterWhereConditions()
	 *           and applyFilterQueries(). All query values use parameter binding via
	 *           Laravel query builder (->where($field, '=', $value)).
	 * @security XSS Prevention - filter values are used only in SQL queries (not rendered
	 *           to HTML in this method). The returned Collection contains raw DB values
	 *           that must be escaped by the view layer before rendering.
	 */
	public function init_filter_datatables(array $get = [], array $post = [], ?string $connection = null): mixed {
		$filterStart = microtime(true);
		
		try {
			// SECURITY: Validate inputs
			$this->validateFilterInputs($get, $post);
			
			// PERFORMANCE: Extract filter parameters
			$filterParams = $this->extractFilterParameters($post, $connection);
			
			// PERFORMANCE: Build filter query
			$query = $this->buildFilterQuery($filterParams);
			
			// Execute query dengan parameter binding (SECURE)
			$results = $query->distinct()->select($filterParams['safeTarget'])->get();
			
			// PERFORMANCE: Log slow filter queries
			$this->logQueryPerformance('filter_' . ($filterParams['safeTable'] ?? 'unknown'), $filterStart);
			
			return $results;
			
		} catch (\InvalidArgumentException $e) {
			\Log::warning('Datatables: Filter validation failed', [
				'error' => $e->getMessage(),
				'get' => $get,
				'post' => array_keys($post),
				'error_type' => 'validation'
			]);
			return collect([]); // Return empty collection instead of null
		} catch (\Illuminate\Database\QueryException $e) {
			\Log::error('Datatables: Filter database query failed', [
				'error' => $e->getMessage(),
				'sql' => $e->getSql() ?? 'N/A',
				'trace' => $e->getTraceAsString(),
				'error_type' => 'database'
			]);
			return collect([]); // Return empty collection instead of null
		} catch (\Exception $e) {
			\Log::error('Datatables: Filter initialization failed', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'error_type' => 'general'
			]);
			return collect([]); // Return empty collection instead of null
		}
	}
	
	/**
	 * Extract and validate filter parameters from request
	 * 
	 * Parses and validates all filter-related parameters including:
	 * - Connection name
	 * - Filter conditions
	 * - Table and target field from _fita
	 * - Foreign key joins
	 * - Previous conditions
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables for better readability
	 * SECURITY: Validates table name and connection
	 *
	 * @param array $post POST parameters (passed by reference, modified to remove reserved params)
	 * @param string|null $connection Database connection name
	 * 
	 * @return array Extracted parameters with keys:
	 *   - connection: string|null Validated connection name
	 *   - table: string Validated table name
	 *   - target: string Target field name (original)
	 *   - safeTarget: string Sanitized target field name
	 *   - prev: string Previous condition string
	 *   - filters: array Additional filter conditions
	 *   - fKeys: array Foreign key join definitions
	 *   - post: array Remaining POST data for where conditions
	 * 
	 * @throws \InvalidArgumentException If validation fails
	 */
	/**
	 * Extract filter parameters from POST data
	 * 
	 * FIXED: 2026-04-27 - Properly separate system parameters from filter values
	 * 
	 * @param array $post POST data (passed by reference)
	 * @param string|null $connection Database connection
	 * @return array Extracted filter parameters
	 * @throws \InvalidArgumentException If _fita format is invalid
	 */
	private function extractFilterParameters(array &$post, ?string $connection): array {
		// Extract connection
		if (isset($post['grabCanvaStackC'])) {
			$connection = $post['grabCanvaStackC'];
			unset($post['grabCanvaStackC']);
		}
		
		// SECURITY: Validate connection
		$connection = $this->validateConnection($connection);
		
		// Extract filters
		$filters = [];
		if (isset($post['_canvastackF'])) {
			$filters = $post['_canvastackF'];
			unset($post['_canvastackF']);
		}
		
		// Parse filter data
		$fdata  = explode('::', $post['_fita']);
		if (count($fdata) < 4) {
			throw new \InvalidArgumentException('Invalid _fita format');
		}
		
		$table  = $fdata[1];
		$target = $fdata[2];
		$prev   = $fdata[3];
		
		// SECURITY: Validate table name WITH connection parameter
		$table = $this->validateTableName($table, $connection);
		
		// Parse foreign keys
		$fKeys = [];
		if (isset($post['_forKeys'])) {
			// Check if already an array (from POST body) or needs decoding (from JSON string)
			if (is_array($post['_forKeys'])) {
				$fKeys = $post['_forKeys'];
			} else {
				$fKeys = json_decode($post['_forKeys'], true);
				if (!is_array($fKeys)) {
					$fKeys = [];
				}
			}
		}
		
		// FIXED: Separate system parameters from filter values
		// Define all reserved/system parameters that should NOT be treated as filters
		$reservedParams = [
			'filterDataTables',
			'_fita',
			'_token',
			'_n',
			'_forKeys',
			'grabCanvaStackC',
			'_canvastackF',
			'filters',
			// DataTables standard parameters
			'draw',
			'columns',
			'order',
			'start',
			'length',
			'search',
			// Additional system parameters
			'renderDataTables',
			'difta',
			'_method'
		];
		
		// Extract filter values (everything that's NOT a reserved parameter)
		$filterValues = [];
		foreach ($post as $key => $value) {
			if (!in_array($key, $reservedParams) && !empty($value)) {
				// Validate field name format (prevent SQL injection)
				if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
					$filterValues[$key] = $value;
				} else {
					\Log::warning('Datatables: Invalid filter field name ignored', [
						'field' => $key,
						'value' => $value
					]);
				}
			}
		}
		
		// Log filter extraction for debugging
		if (config('app.debug')) {
			\Log::debug('Datatables: Filter parameters extracted', [
				'table' => $table,
				'target' => $target,
				'filter_values' => $filterValues,
				'filters' => $filters,
				'prev' => $prev
			]);
		}
		
		// Remove reserved parameters from $post
		foreach ($reservedParams as $param) {
			unset($post[$param]);
		}
		
		// Sanitize target field name
		$safeTarget = preg_replace('/[^a-zA-Z0-9_.]/', '', $target);
		
		return [
			'connection' => $connection,
			'table' => $table,
			'target' => $target,
			'safeTarget' => $safeTarget,
			'prev' => $prev,
			'filters' => $filters,
			'fKeys' => $fKeys,
			'post' => $filterValues  // ← FIXED: Only pass filter values, not all POST data
		];
	}
	
	/**
	 * Build filter query with all conditions applied
	 * 
	 * Orchestrates query building by applying:
	 * 1. Foreign key joins
	 * 2. Where conditions from POST data
	 * 3. Additional filter queries
	 * 4. Previous condition filters
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables for better readability
	 * SECURITY: Uses query builder with parameter binding
	 *
	 * @param array $params Filter parameters from extractFilterParameters()
	 * 
	 * @return \Illuminate\Database\Query\Builder Query builder instance with all conditions
	 */
	private function buildFilterQuery(array $params): mixed {
		// Build query menggunakan query builder (SECURE)
		$query = DB::connection($params['connection'])->table($params['table']);
		
		// PERFORMANCE: Apply joins
		$query = $this->applyFilterJoins($query, $params['fKeys']);
		
		// PERFORMANCE: Apply where conditions
		$query = $this->applyFilterWhereConditions($query, $params['post']);
		
		// PERFORMANCE: Apply filter queries
		$query = $this->applyFilterQueries($query, $params['filters']);
		
		// PERFORMANCE: Apply previous conditions
		$query = $this->applyFilterPreviousConditions($query, $params['prev']);
		
		return $query;
	}
	
	/**
	 * Apply foreign key joins to filter query
	 * 
	 * Adds LEFT JOIN clauses for related tables.
	 * Validates each joined table against whitelist.
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables
	 * SECURITY: Validates joined table names
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param array $fKeys Foreign key definitions (format: ['table.field' => 'other_table.field'])
	 * 
	 * @return \Illuminate\Database\Query\Builder Modified query with joins
	 */
	private function applyFilterJoins(mixed $query, array $fKeys): mixed {
		if (empty($fKeys)) {
			return $query;
		}
		
		foreach ($fKeys as $fqs => $fqt) {
			$tqs = explode('.', $fqs);
			$tqsTable = $tqs[0];
			
			// SECURITY: Validate joined table
			try {
				$tqsTable = $this->validateTableName($tqsTable);
				$query->leftJoin($tqsTable, $fqs, '=', $fqt);
			} catch (\InvalidArgumentException $e) {
				\Log::warning('Datatables: Invalid join table', [
					'table' => $tqsTable,
					'error' => $e->getMessage()
				]);
				// Skip invalid join
				continue;
			}
		}
		
		return $query;
	}
	
	/**
	 * Apply where conditions from POST data to filter query
	 * 
	 * Adds WHERE clauses for each POST parameter.
	 * Sanitizes field names to prevent SQL injection.
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables
	 * SECURITY: Sanitizes field names, uses parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param array $post POST data (field => value pairs)
	 * 
	 * @return \Illuminate\Database\Query\Builder Modified query with where conditions
	 */
	private function applyFilterWhereConditions(mixed $query, array $post): mixed {
		foreach ($post as $key => $value) {
			// Sanitize field name untuk mencegah SQL injection
			$safeKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $key);
			if ($safeKey !== $key) {
				\Log::warning('Datatables: Invalid field name sanitized', [
					'original' => $key,
					'sanitized' => $safeKey
				]);
			}
			$query->where($safeKey, '=', $value);
		}
		
		return $query;
	}
	
	/**
	 * Apply additional filter queries to filter query
	 * 
	 * Adds WHERE or WHERE IN clauses from filter array.
	 * Supports both single values and array values (whereIn).
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables
	 * SECURITY: Sanitizes field names, uses parameter binding
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param array $filters Filter array with format:
	 *   [['field_name' => 'column', 'value' => 'single_value'], ...]
	 *   or [['field_name' => 'column', 'value' => ['array', 'values']], ...]
	 * 
	 * @return \Illuminate\Database\Query\Builder Modified query with filter conditions
	 */
	private function applyFilterQueries(mixed $query, array $filters): mixed {
		if (empty($filters)) {
			return $query;
		}
		
		foreach ($filters as $filter) {
			if (!isset($filter['field_name']) || !isset($filter['value'])) {
				continue;
			}
			
			$fqFieldName = preg_replace('/[^a-zA-Z0-9_.]/', '', $filter['field_name']);
			$fqDataValue = $filter['value'];
			
			if (is_array($fqDataValue)) {
				$query->whereIn($fqFieldName, $fqDataValue);
			} else {
				$query->where($fqFieldName, '=', $fqDataValue);
			}
		}
		
		return $query;
	}
	
	/**
	 * Apply previous conditions to filter query
	 * 
	 * Applies cascading filter conditions from previous filter selections.
	 * Used for dependent dropdowns (e.g., Province -> City -> District).
	 * 
	 * PERFORMANCE: Extracted from init_filter_datatables
	 * SECURITY: Sanitizes field names, uses parameter binding
	 * 
	 * Previous Condition Format:
	 * "field1|field2|field3#value1|value2|value3"
	 *
	 * @param \Illuminate\Database\Query\Builder $query Query builder instance
	 * @param string $prev Previous condition string (format: "fields#values" or "#null")
	 * 
	 * @return \Illuminate\Database\Query\Builder Modified query with previous conditions
	 */
	private function applyFilterPreviousConditions(mixed $query, string $prev): mixed {
		if (self::NULL_CONDITION === $prev) {
			return $query;
		}
		
		$previous = explode("#", $prev);
		if (count($previous) < 2) {
			return $query;
		}
		
		$preFields = explode('|', $previous[0]);
		$preFieldt = explode('|', $previous[1]);
		
		foreach ($preFields as $idf => $prev_field) {
			if (isset($preFieldt[$idf])) {
				$safeField = preg_replace('/[^a-zA-Z0-9_.]/', '', $prev_field);
				$query->where($safeField, '=', $preFieldt[$idf]);
			}
		}
		
		return $query;
	}
	
	// ============================================================================
	// POST METHOD IMPLEMENTATION
	// ============================================================================
	
	/**
	 * Process POST request from DataTables ajax
	 * Converts POST format to GET format and calls existing process() method
	 * SECURITY: Added input validation
	 * 
	 * @param array $postData POST request data
	 * @param object $data Table data configuration
	 * @param array $filters Additional filters
	 * @param array $filter_page Page filters
	 * @return array JSON response for DataTables or null on error
	 * @throws \InvalidArgumentException If invalid inputs
	 */
	public function processPost(array $postData, object $data, array $filters = [], array $filter_page = []): mixed {
		try {
			// CRITICAL DEBUG: Log processPost entry
			\Log::info('Datatables::processPost called', [
				'postData_keys' => array_keys($postData),
				'filters_param' => $filters,
				'has_user_fullname' => isset($postData['user_fullname']),
				'has_module_name' => isset($postData['module_name'])
			]);
			
			// SECURITY: Validate inputs
			if (!is_array($postData)) {
				throw new \InvalidArgumentException('POST data must be an array');
			}
			
			if (!is_object($data)) {
				throw new \InvalidArgumentException('Data must be an object');
			}
			
			// Convert POST data to GET format (same format as process() expects)
			$method = $this->convertPostToGetFormat($postData);
			
			\Log::info('Datatables::processPost after convertPostToGetFormat', [
				'method_keys' => array_keys($method),
				'has_filters_flag' => !empty($method['filters']),
				'has_user_fullname' => isset($method['user_fullname']),
				'has_module_name' => isset($method['module_name'])
			]);
			
			// Call existing process() method (reuse all GET logic)
			return $this->process($method, $data, $filters, $filter_page);
			
		} catch (\InvalidArgumentException $e) {
			\Log::warning('Datatables: POST validation failed', [
				'error' => $e->getMessage()
			]);
			return null;
		} catch (\Exception $e) {
			\Log::error('Datatables: POST process failed', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return null;
		}
	}
	
	/**
	 * Convert POST request data to GET format
	 * DataTables sends different format for POST vs GET
	 * 
	 * @param array $postData POST request data
	 * @return array GET-formatted data
	 */
	private function convertPostToGetFormat(array $postData): array {
		// Extract standard DataTables parameters
		$method = [
			'renderDataTables' => true,
			'draw' => $postData['draw'] ?? 0,
			'start' => $postData['start'] ?? 0,
			'length' => $postData['length'] ?? 10,
			'order' => $postData['order'] ?? [],
			'columns' => $postData['columns'] ?? [],
			'search' => $postData['search'] ?? [],
			'difta' => $postData['difta'] ?? [],
			'filters' => $postData['filters'] ?? false
		];
		
		// Include custom filter parameters
		// Remove reserved parameters and merge the rest (custom filters)
		foreach ($postData as $key => $value) {
			// @performance 6.6 - Use strict mode (true) for type-safe comparison
			if (!in_array($key, self::AJAX_RESERVED_PARAMS, true)) {
				$method[$key] = $value;
			}
		}
		
		return $method;
	}
	
	/**
	 * Parse POST request from Laravel Request object
	 * Extracts all DataTables parameters from POST body
	 * 
	 * @param \Illuminate\Http\Request $request
	 * @return array Parsed POST data
	 */
	public function parsePostRequest(mixed $request): array {
		// CRITICAL DEBUG: Log raw request
		\Log::info('Datatables::parsePostRequest called', [
			'all_input_keys' => array_keys($request->all()),
			'has_filters_flag' => $request->has('filters'),
			'filters_value' => $request->input('filters')
		]);
		
		// Extract standard DataTables parameters
		$postData = [
			'draw' => $request->input('draw', 0),
			'start' => $request->input('start', 0),
			'length' => $request->input('length', 10),
			'order' => $request->input('order', []),
			'columns' => $request->input('columns', []),
			'search' => $request->input('search', []),
			'difta' => $request->input('difta', []),
			'filters' => $request->input('filters', false),
			'_token' => $request->input('_token')
		];
		
		// Extract custom filter parameters (for filtering functionality)
		// Get all POST data and remove reserved parameters
		$allInput = $request->all();
		foreach (self::AJAX_RESERVED_PARAMS as $reserved) {
			unset($allInput[$reserved]);
		}
		
		// CRITICAL DEBUG: Log extracted filters
		\Log::info('Datatables::parsePostRequest extracted filters', [
			'custom_filters' => $allInput,
			'filter_count' => count($allInput)
		]);
		
		// Merge custom filters into postData
		$postData = array_merge($postData, $allInput);
		
		\Log::info('Datatables::parsePostRequest final postData', [
			'postData_keys' => array_keys($postData),
			'has_user_fullname' => isset($postData['user_fullname']),
			'has_module_name' => isset($postData['module_name'])
		]);
		
		return $postData;
	}
}

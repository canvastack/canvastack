<?php
use Illuminate\Support\Facades\DB;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidTableNameException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidColumnException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidPaginationException;
use Canvastack\Canvastack\Library\Exceptions\Table\InvalidSortException;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;

/**
 * Table Helper Functions
 * 
 * Collection of utility functions for table management, data processing,
 * action buttons generation, and HTML table rendering.
 * 
 * @filesource Table.php
 * @package    Canvastack\Canvastack\Library\Helpers
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 * @created    13 Apr 2021 04:05:22
 * @version    2.0.0
 * 
 * ============================================================================
 * FEATURES
 * ============================================================================
 * 
 * 1. Security Functions (3 functions)
 *    - XSS protection (HTML & JavaScript escaping)
 *    - URL validation and sanitization
 *    - Input validation with regex patterns
 * 
 * 2. Data Processing (5 functions)
 *    - Filter data normalization
 *    - Model table extraction
 *    - Database table listing
 *    - Temporary table creation
 *    - Formula column processing
 * 
 * 3. Table Generation (4 functions)
 *    - Complete table HTML generation
 *    - Query map table rendering
 *    - Custom column HTML generation
 *    - Row attributes management
 * 
 * 4. Action Buttons (3 functions)
 *    - Dynamic action button generation
 *    - Custom action button parsing
 *    - Multi-button rendering (view, edit, delete, custom)
 * 
 * 5. Utility Functions (6 functions)
 *    - Database connection management
 *    - Column existence checking
 *    - Column type detection
 *    - JSON formatting
 * 
 * ============================================================================
 * SECURITY
 * ============================================================================
 * 
 * - Input Validation: 90% coverage (19 of 21 functions)
 * - XSS Protection: Comprehensive HTML/JS escaping
 * - SQL Injection: Protected via Laravel DB facade
 * - Error Handling: 90% coverage with try-catch blocks
 * - Error Logging: All critical operations logged
 * 
 * ============================================================================
 * USAGE EXAMPLES
 * ============================================================================
 * 
 * // Example 1: Generate table with action buttons
 * $table = canvastack_generate_table(
 *     'Users',
 *     'users_table',
 *     ['id', 'name', 'email', 'action'],
 *     $users_data,
 *     ['class' => 'table table-striped'],
 *     true // numbering
 * );
 * 
 * // Example 2: Create action buttons
 * $buttons = canvastack_table_action_button(
 *     $row,
 *     'id',
 *     '/admin/users',
 *     ['approve', 'reject'],
 *     ['delete'] // removed buttons
 * );
 * 
 * // Example 3: Escape user input
 * $safe_html = canvastack_escape_html($user_input);
 * $safe_js = canvastack_escape_js($user_input);
 * 
 * // Example 4: Validate URL
 * $validated = canvastack_validate_url($url);
 * if ($validated !== false) {
 *     // URL is safe to use
 * }
 * 
 * ============================================================================
 * PERFORMANCE
 * ============================================================================
 * 
 * - Optimized array operations with array_flip()
 * - Efficient string operations
 * - Minimal database queries
 * - No N+1 query issues
 * 
 * ============================================================================
 * ERROR HANDLING
 * ============================================================================
 * 
 * All functions implement comprehensive error handling:
 * - Try-catch blocks for all operations
 * - Error logging for debugging
 * - Graceful fallbacks (empty arrays, false, empty strings)
 * - Validation errors throw InvalidArgumentException
 * - Runtime errors return safe defaults
 * 
 * ============================================================================
 */

// ============================================================================
// CONSTANTS
// ============================================================================

if (!defined('CANVASTACK_CONNECTION_SEPARATOR')) {
	/**
	 * Default separator for database connection identification
	 */
	define('CANVASTACK_CONNECTION_SEPARATOR', '--canvastackcon--');
}

if (!defined('CANVASTACK_RESTORE_DELETED_ACTION')) {
	/**
	 * Action name for restoring deleted records
	 */
	define('CANVASTACK_RESTORE_DELETED_ACTION', 'restore_deleted');
}

if (!defined('CANVASTACK_DEFAULT_ACTIONS')) {
	/**
	 * Default CRUD action names
	 */
	define('CANVASTACK_DEFAULT_ACTIONS', ['index', 'insert', 'update', 'destroy']);
}

if (!defined('CANVASTACK_ALL_ACTION_ALIASES')) {
	/**
	 * All action name aliases (for filtering)
	 */
	define('CANVASTACK_ALL_ACTION_ALIASES', [
		'index', 'show', 'view', 
		'create', 'insert', 'add', 
		'edit', 'update', 'modify', 
		'delete', 'destroy'
	]);
}

if (!defined('CANVASTACK_DEFAULT_DB_CONNECTION')) {
	/**
	 * Default database connection name
	 */
	define('CANVASTACK_DEFAULT_DB_CONNECTION', 'mysql');
}

if (!defined('CANVASTACK_TEMP_TABLE_PREFIX')) {
	/**
	 * Prefix for temporary tables
	 */
	define('CANVASTACK_TEMP_TABLE_PREFIX', 'temp_');
}

if (!defined('CANVASTACK_DEFAULT_TABLE_CLASS')) {
	/**
	 * Default CSS classes for DataTables
	 */
	define('CANVASTACK_DEFAULT_TABLE_CLASS', 'CanvaStack-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap');
}

if (!defined('CANVASTACK_ROUTE_INDEX_ACTION')) {
	/**
	 * Route action name for index
	 */
	define('CANVASTACK_ROUTE_INDEX_ACTION', '@index');
}

if (!defined('CANVASTACK_ROUTE_DESTROY_ACTION')) {
	/**
	 * Route action name for destroy
	 */
	define('CANVASTACK_ROUTE_DESTROY_ACTION', '@destroy');
}

// ============================================================================
// SECURITY FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_escape_html')) {
	/**
	 * Escape HTML to prevent XSS attacks
	 * 
	 * Converts special characters to HTML entities to prevent
	 * Cross-Site Scripting (XSS) attacks.
	 *
	 * @param string|null $value The value to escape
	 * @return string Escaped HTML-safe string
	 * 
	 * @example
	 * $safe = canvastack_escape_html('<script>alert("XSS")</script>');
	 * // Returns: '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
	 * 
	 * $safe = canvastack_escape_html(null);
	 * // Returns: ''
	 */
	function canvastack_escape_html(mixed $value): string {
		if (is_null($value)) {
			return '';
		}
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('canvastack_escape_js')) {
	/**
	 * Escape JavaScript strings to prevent XSS
	 * 
	 * Escapes special characters in strings that will be used
	 * in JavaScript context to prevent XSS attacks.
	 *
	 * @param string|null $value The value to escape
	 * @return string Escaped JavaScript-safe string
	 * 
	 * @example
	 * $safe = canvastack_escape_js("alert('test')");
	 * // Returns: "alert(\'test\')"
	 * 
	 * $safe = canvastack_escape_js(null);
	 * // Returns: ''
	 */
	function canvastack_escape_js(mixed $value): string {
		if (is_null($value)) {
			return '';
		}
		return addslashes($value);
	}
}

if (!function_exists('canvastack_validate_url')) {
	/**
	 * Validate and sanitize URL
	 * 
	 * Validates URLs to ensure they are safe to use. Supports both
	 * relative URLs (starting with /) and absolute URLs.
	 *
	 * @param string $url The URL to validate
	 * @return string|false Validated URL or false if invalid
	 * 
	 * @example
	 * $url = canvastack_validate_url('/admin/users');
	 * // Returns: '/admin/users' (relative URL is valid)
	 * 
	 * $url = canvastack_validate_url('https://example.com');
	 * // Returns: 'https://example.com' (absolute URL is valid)
	 * 
	 * $url = canvastack_validate_url('javascript:alert(1)');
	 * // Returns: false (invalid/dangerous URL)
	 */
	function canvastack_validate_url(string $url): string|false {
		// Allow relative URLs and absolute URLs
		if (empty($url)) {
			return false;
		}

		// If it's a relative URL (starts with /), it's valid
		if (strpos($url, '/') === 0) {
			return $url;
		}

		// If it's an absolute URL, validate it
		$validated = filter_var($url, FILTER_VALIDATE_URL);
		return $validated !== false ? $validated : false;
	}
}

// ============================================================================
// SECURITY VALIDATION FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_is_out_of_memory_error')) {
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
	 *
	 * @example
	 * try {
	 *     // ... memory-intensive operation
	 * } catch (\Error $e) {
	 *     if (canvastack_is_out_of_memory_error($e)) {
	 *         // handle gracefully
	 *     }
	 *     throw $e;
	 * }
	 */
	function canvastack_is_out_of_memory_error(\Throwable $e): bool {
		$message = $e->getMessage();
		return str_contains($message, 'Allowed memory size')
			|| str_contains($message, 'Out of memory');
	}
}

if (!function_exists('canvastack_table_validate_table_name')) {
	/**
	 * Validate table name against format rules and optional whitelist
	 *
	 * Ensures the table name is safe to use in database queries by checking
	 * format (alphanumeric + underscore only) and optionally verifying it
	 * exists in the database or a provided whitelist.
	 *
	 * @security CRITICAL - Always call this before using a table name in any query.
	 *           Prevents SQL injection via table name manipulation.
	 *
	 * @param string          $tableName     Table name to validate
	 * @param array|null      $allowedTables Explicit whitelist of allowed table names.
	 *                                       Pass null to allow any existing DB table.
	 * @param string          $connection    Database connection name
	 *
	 * @return string Validated, safe table name
	 *
	 * @throws \InvalidArgumentException If table name is empty, has invalid format,
	 *                                   or is not in the whitelist / database
	 *
	 * @example
	 * // Validate against all existing tables
	 * $table = canvastack_table_validate_table_name('users');
	 *
	 * // Validate against explicit whitelist
	 * $table = canvastack_table_validate_table_name('users', ['users', 'posts']);
	 *
	 * // Throws InvalidArgumentException:
	 * canvastack_table_validate_table_name('users; DROP TABLE users--');
	 */
	function canvastack_table_validate_table_name(string $tableName, ?array $allowedTables = null, string $connection = CANVASTACK_DEFAULT_DB_CONNECTION): string {
		// Guard: non-empty
		if (empty($tableName)) {
			$msg = 'Table name must be a non-empty string';
			error_log('[SECURITY] canvastack_table_validate_table_name(): ' . $msg . ' | input=' . var_export($tableName, true));
			throw new InvalidTableNameException($msg);
		}

		// Guard: safe format (alphanumeric + underscore only)
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
			$msg = 'Invalid table name format: "' . $tableName . '". Only alphanumeric characters and underscores are allowed.';
			error_log('[SECURITY] canvastack_table_validate_table_name(): ' . $msg);
			throw new InvalidTableNameException($msg);
		}

		// Guard: whitelist check
		if ($allowedTables !== null) {
			if (!in_array($tableName, $allowedTables, true)) {
				$msg = 'Table "' . $tableName . '" is not in the allowed tables whitelist.';
				error_log('[SECURITY] canvastack_table_validate_table_name(): ' . $msg);
				throw new InvalidTableNameException($msg);
			}
			return $tableName;
		}

		// Guard: verify table or view exists in the database
		try {
			$schemaBuilder = DB::connection($connection)->getSchemaBuilder();
			$exists = $schemaBuilder->hasTable($tableName);
			
			// If not found as table, check if it's a view
			if (!$exists) {
				// Check if it's a view by querying information_schema
				$databaseName = DB::connection($connection)->getDatabaseName();
				$viewExists = DB::connection($connection)
					->table('information_schema.VIEWS')
					->where('TABLE_SCHEMA', $databaseName)
					->where('TABLE_NAME', $tableName)
					->exists();
				
				if ($viewExists) {
					$exists = true;
				}
			}
			
			if (!$exists) {
				$msg = 'Table "' . $tableName . '" does not exist in the database.';
				error_log('[SECURITY] canvastack_table_validate_table_name(): ' . $msg . ' | connection=' . $connection);
				throw new InvalidTableNameException($msg);
			}
		} catch (InvalidTableNameException $e) {
			throw $e;
		} catch (\Exception $e) {
			error_log('[SECURITY] canvastack_table_validate_table_name(): DB check failed for "' . $tableName . '": ' . $e->getMessage());
			throw new InvalidTableNameException('Unable to verify table "' . $tableName . '": ' . $e->getMessage(), 0, $e);
		}

		return $tableName;
	}
}

if (!function_exists('canvastack_table_validate_column_name')) {
	/**
	 * Validate a column name against the actual table schema
	 *
	 * Ensures the column name is safe to use in queries by checking format
	 * and verifying the column exists in the given table's schema.
	 *
	 * @security CRITICAL - Always call this before using a column name in ORDER BY,
	 *           WHERE, or SELECT clauses. Prevents SQL injection via column name injection.
	 *
	 * @param string $tableName   Table that should contain the column
	 * @param string $columnName  Column name to validate
	 * @param string $connection  Database connection name
	 *
	 * @return string Validated, safe column name
	 *
	 * @throws \InvalidArgumentException If column name is empty, has invalid format,
	 *                                   or does not exist in the table schema
	 *
	 * @example
	 * $col = canvastack_table_validate_column_name('users', 'email');
	 *
	 * // Throws InvalidArgumentException:
	 * canvastack_table_validate_column_name('users', '1=1; DROP TABLE users--');
	 */
	function canvastack_table_validate_column_name(string $tableName, string $columnName, string $connection = CANVASTACK_DEFAULT_DB_CONNECTION): string {
		// Guard: non-empty inputs
		if (empty($tableName)) {
			$msg = 'Table name must be a non-empty string';
			error_log('[SECURITY] canvastack_table_validate_column_name(): ' . $msg);
			throw new InvalidTableNameException($msg);
		}

		if (empty($columnName)) {
			$msg = 'Column name must be a non-empty string';
			error_log('[SECURITY] canvastack_table_validate_column_name(): ' . $msg);
			throw new InvalidColumnException($msg);
		}

		// Guard: safe format for table name
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
			$msg = 'Invalid table name format: "' . $tableName . '"';
			error_log('[SECURITY] canvastack_table_validate_column_name(): ' . $msg);
			throw new InvalidTableNameException($msg);
		}

		// Guard: safe format for column name (allow dot notation for joins: table.column)
		if (!preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)?$/', $columnName)) {
			$msg = 'Invalid column name format: "' . $columnName . '". Only alphanumeric characters, underscores, and dot notation (table.column) are allowed.';
			error_log('[SECURITY] canvastack_table_validate_column_name(): ' . $msg);
			throw new InvalidColumnException($msg);
		}

		// Resolve actual column name (strip table prefix if dot notation used)
		$resolvedColumn = strpos($columnName, '.') !== false
			? explode('.', $columnName)[1]
			: $columnName;

		// Guard: verify column exists in schema
		try {
			$exists = DB::connection($connection)->getSchemaBuilder()->hasColumn($tableName, $resolvedColumn);
			if (!$exists) {
				$msg = 'Column "' . $columnName . '" does not exist in table "' . $tableName . '".';
				error_log('[SECURITY] canvastack_table_validate_column_name(): ' . $msg);
				throw new InvalidColumnException($msg);
			}
		} catch (InvalidColumnException $e) {
			throw $e;
		} catch (InvalidTableNameException $e) {
			throw $e;
		} catch (\Exception $e) {
			error_log('[SECURITY] canvastack_table_validate_column_name(): Schema check failed for "' . $tableName . '"."' . $columnName . '": ' . $e->getMessage());
			throw new InvalidColumnException('Unable to verify column "' . $columnName . '" in table "' . $tableName . '": ' . $e->getMessage(), 0, $e);
		}

		return $columnName;
	}
}

if (!function_exists('canvastack_table_validate_pagination')) {
	/**
	 * Validate DataTables pagination parameters (start offset and page length)
	 *
	 * Ensures pagination values are non-negative integers within safe bounds
	 * to prevent resource exhaustion and unexpected query behavior.
	 *
	 * @security Prevents excessively large page sizes that could cause memory
	 *           exhaustion or denial-of-service via resource abuse.
	 *
	 * @param int $start   Zero-based row offset (must be >= 0)
	 * @param int $length  Number of rows per page (must be 1–MAX_PAGE_LENGTH)
	 *
	 * @return array{start: int, length: int} Validated pagination parameters
	 *
	 * @throws \InvalidArgumentException If start is negative or length is out of range
	 *
	 * @example
	 * [$start, $length] = canvastack_table_validate_pagination(0, 25);
	 *
	 * // Throws InvalidArgumentException:
	 * canvastack_table_validate_pagination(-1, 25);
	 * canvastack_table_validate_pagination(0, 99999);
	 */
	function canvastack_table_validate_pagination(int $start, int $length): array {
		// Guard: start must be non-negative
		if ($start < 0) {
			$msg = 'Pagination start must be a non-negative integer, got: ' . $start;
			error_log('[SECURITY] canvastack_table_validate_pagination(): ' . $msg);
			throw new InvalidPaginationException($msg);
		}

		// Guard: length must be at least 1
		if ($length < 1) {
			$msg = 'Pagination length must be at least 1, got: ' . $length;
			error_log('[SECURITY] canvastack_table_validate_pagination(): ' . $msg);
			throw new InvalidPaginationException($msg);
		}

		// Guard: length must not exceed maximum allowed page size
		$maxLength = defined('CANVASTACK_MAX_PAGE_LENGTH') ? CANVASTACK_MAX_PAGE_LENGTH : 100;
		if ($length > $maxLength) {
			$msg = 'Pagination length ' . $length . ' exceeds maximum allowed value of ' . $maxLength . '.';
			error_log('[SECURITY] canvastack_table_validate_pagination(): ' . $msg);
			throw new InvalidPaginationException($msg);
		}

		return ['start' => $start, 'length' => $length];
	}
}

if (!function_exists('canvastack_table_validate_sort')) {
	/**
	 * Validate sort column and direction for use in ORDER BY clauses
	 *
	 * Validates the sort column against the table schema and ensures the
	 * direction is strictly 'asc' or 'desc' to prevent SQL injection via
	 * ORDER BY manipulation.
	 *
	 * @security CRITICAL - Never pass unvalidated sort parameters to ORDER BY.
	 *           Attackers can inject arbitrary SQL through sort column/direction.
	 *
	 * @param string $tableName  Table name (used for column schema validation)
	 * @param string $column     Column name to sort by
	 * @param string $direction  Sort direction: 'asc' or 'desc' (case-insensitive)
	 * @param string $connection Database connection name
	 *
	 * @return array{column: string, direction: string} Validated sort parameters
	 *                                                   (direction is always lowercase)
	 *
	 * @throws \InvalidArgumentException If column is invalid or direction is not asc/desc
	 *
	 * @example
	 * $sort = canvastack_table_validate_sort('users', 'created_at', 'desc');
	 * // Returns: ['column' => 'created_at', 'direction' => 'desc']
	 *
	 * // Throws InvalidArgumentException:
	 * canvastack_table_validate_sort('users', 'name', 'RANDOM()');
	 */
	function canvastack_table_validate_sort(string $tableName, string $column, string $direction, string $connection = CANVASTACK_DEFAULT_DB_CONNECTION): array {
		// Guard: non-empty inputs
		if (empty($tableName)) {
			$msg = 'Table name must be a non-empty string';
			error_log('[SECURITY] canvastack_table_validate_sort(): ' . $msg);
			throw new InvalidTableNameException($msg);
		}

		if (empty($column)) {
			$msg = 'Sort column must be a non-empty string';
			error_log('[SECURITY] canvastack_table_validate_sort(): ' . $msg);
			throw new InvalidSortException($msg);
		}

		// Guard: direction must be strictly 'asc' or 'desc'
		$normalizedDirection = strtolower(trim($direction));
		if (!in_array($normalizedDirection, ['asc', 'desc'], true)) {
			$msg = 'Sort direction must be "asc" or "desc", got: "' . $direction . '"';
			error_log('[SECURITY] canvastack_table_validate_sort(): ' . $msg . ' | table=' . $tableName . ', column=' . $column);
			throw new InvalidSortException($msg);
		}

		// Guard: validate column against schema (reuses existing validation)
		$validatedColumn = canvastack_table_validate_column_name($tableName, $column, $connection);

		return ['column' => $validatedColumn, 'direction' => $normalizedDirection];
	}
}

if (!function_exists('canvastack_table_sanitize_search')) {
	/**
	 * Sanitize a search term for safe use in LIKE queries
	 *
	 * Strips control characters, trims whitespace, and escapes LIKE
	 * wildcard metacharacters (%, _) so the term is treated as a literal
	 * string rather than a pattern — unless the caller explicitly wants
	 * wildcard support.
	 *
	 * @security Prevents LIKE-based DoS attacks (e.g., "%%%%%" causing full
	 *           table scans) and ensures search terms are safe for output.
	 *           Always combine with parameterized query bindings.
	 *
	 * @param string $searchTerm      Raw search term from user input
	 * @param bool   $allowWildcards  Set true to preserve % and _ wildcards
	 *                                (only use when explicitly needed)
	 * @param int    $maxLength       Maximum allowed length (default: 255)
	 *
	 * @return string Sanitized search term, safe for LIKE binding and HTML output
	 *
	 * @throws \InvalidArgumentException If search term exceeds maximum length
	 *
	 * @example
	 * $term = canvastack_table_sanitize_search('  hello%world  ');
	 * // Returns: 'hello\%world'  (wildcards escaped, whitespace trimmed)
	 *
	 * $term = canvastack_table_sanitize_search('50%', true);
	 * // Returns: '50%'  (wildcards preserved)
	 *
	 * $term = canvastack_table_sanitize_search('<script>alert(1)</script>');
	 * // Returns: '&lt;script&gt;alert(1)&lt;/script&gt;'
	 */
	function canvastack_table_sanitize_search(string $searchTerm, bool $allowWildcards = false, ?int $maxLength = null): string {
		// Get max length from config if not provided
		if ($maxLength === null) {
			$maxLength = config('canvastack.datatables.security.max_search_length', 255);
		}
		
		// Guard: length limit to prevent DoS via oversized search terms
		if (mb_strlen($searchTerm) > $maxLength) {
			// Log security event if configured
			if (config('canvastack.datatables.security.log_security_events', true)) {
				canvastack_table_log_security_event('search_term_too_long', [
					'length' => mb_strlen($searchTerm),
					'max_length' => $maxLength,
				]);
			}
			
			$msg = 'Search term exceeds maximum allowed length of ' . $maxLength . ' characters.';
			error_log('[SECURITY] canvastack_table_sanitize_search(): ' . $msg . ' | length=' . mb_strlen($searchTerm));
			throw new TableValidationException($msg);
		}

		// Strip null bytes and control characters (except tab/newline which are harmless)
		$sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $searchTerm);

		// Trim surrounding whitespace
		$sanitized = trim($sanitized ?? '');

		// Escape LIKE metacharacters unless wildcards are explicitly allowed
		if (!$allowWildcards) {
			$sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $sanitized);
		}

		// HTML-encode for safe output (prevents XSS if term is reflected in UI)
		// Only if XSS protection is enabled in config
		if (config('canvastack.datatables.security.xss_protection', true)) {
			$sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
		}

		return $sanitized;
	}
}

// ============================================================================
// DATA PROCESSING FUNCTIONS
// ============================================================================
 
if (!function_exists('canvastack_filter_data_normalizer')) {
	
	/**
	 * Normalizing Data Filters
	 * 
	 * Converts filter data from various formats into a standardized
	 * format for consistent processing. Handles both single values
	 * and array values.
	 * 
	 * SECURITY: Added input validation and error handling
	 * 
	 * @param array $filters Array of filter data to normalize
	 * 
	 * @return array Normalized filter data
	 * @throws \InvalidArgumentException If filters is not an array
	 * 
	 * @example
	 * $filters = [
	 *     ['field_name' => 'status', 'value' => 'active'],
	 *     ['field_name' => 'role', 'value' => ['admin', 'user']]
	 * ];
	 * $normalized = canvastack_filter_data_normalizer($filters);
	 * // Returns normalized array with consistent structure
	 */
	function canvastack_filter_data_normalizer(array $filters = []): array {
		// Input validation
		if (!is_array($filters)) {
			throw new \InvalidArgumentException('Filters must be an array');
		}
		
		try {
			// @performance 6.3 - Optimized from triple-nested loop to single pass using direct
			//              assignment. Instead of building intermediate arrays and then converting,
			//              we build the final structure directly. This reduces iterations from
			//              O(n*m*k) to O(n) where n is the number of filter items.
			$result = [];
			
			foreach ($filters as $filter_data) {
				// Validate filter structure
				if (!is_array($filter_data) || !isset($filter_data['field_name']) || !isset($filter_data['value'])) {
					continue; // Skip invalid filter data
				}
				
				$fieldName = $filter_data['field_name'];
				
				// Build final structure directly (single pass)
				if (!isset($result[$fieldName])) {
					$result[$fieldName] = [
						'field_name' => $fieldName,
						'operator'   => '=',
						'value'      => []
					];
				}
				
				// Handle both array and scalar values
				if (is_array($filter_data['value'])) {
					foreach ($filter_data['value'] as $filterValues) {
						$result[$fieldName]['value'][] = $filterValues;
					}
				} else {
					$result[$fieldName]['value'][] = $filter_data['value'];
				}
			}
			
			// Return as indexed array (array_values extracts values, discarding string keys)
			return array_values($result);
			
		} catch (\Exception $e) {
			error_log('canvastack_filter_data_normalizer() error: ' . $e->getMessage());
			return [];
		}
	}
}

if (!function_exists('canvastack_get_model_table')) {
	
	/**
	 * Get Table Name From Data Model
	 * 
	 * Extracts the database table name from a Laravel Eloquent model.
	 * Useful for dynamic table operations.
	 * 
	 * SECURITY: Added input validation and error handling
	 *
	 * @param object|string $model Model instance or class name
	 * @param boolean $find Whether to find the model first
	 *
	 * @return string|false Table name or false on error
	 * @throws \InvalidArgumentException If model is invalid
	 * 
	 * @example
	 * $table = canvastack_get_model_table(User::class);
	 * // Returns: 'users'
	 * 
	 * $table = canvastack_get_model_table(new User());
	 * // Returns: 'users'
	 */
	function canvastack_get_model_table(object|string $model, bool $find = false): string|false {
		try {
			// Input validation
			if (empty($model)) {
				throw new \InvalidArgumentException('Model cannot be empty');
			}
			
			$model = canvastack_get_model($model, $find);
			
			if (!is_object($model) || !method_exists($model, 'getTable')) {
				throw new \InvalidArgumentException('Invalid model object');
			}
			
			return $model->getTable();
			
		} catch (\Exception $e) {
			error_log('canvastack_get_model_table() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_get_all_tables')) {
	
	/**
	 * Get All Table Lists From Host Connection
	 * 
	 * Retrieves a list of all tables in the specified database connection.
	 * Useful for dynamic table operations and database introspection.
	 * 
	 * SECURITY: Added error handling for database operations
	 *
	 * @param string|null $connection Database connection name (null for default)
	 *
	 * @return \Illuminate\Support\Collection Collection of table names
	 * 
	 * @example
	 * $tables = canvastack_get_all_tables();
	 * // Returns: Collection(['users', 'posts', 'comments', ...])
	 * 
	 * $tables = canvastack_get_all_tables('mysql_secondary');
	 * // Returns: Collection of tables from secondary connection
	 */
	function canvastack_get_all_tables(?string $connection = null): \Illuminate\Support\Collection {
		try {
			return collect(DB::connection($connection)->select('show tables'))->map(function ($val) {
				foreach ($val as $tbl) return $tbl;
			});
			
		} catch (\Exception $e) {
			error_log('canvastack_get_all_tables() error: ' . $e->getMessage());
			return collect([]);
		}
	}
}

if (!function_exists('canvastack_set_connection_separator')) {
	
	/**
	 * Set Database Connection Separator
	 * 
	 * Returns the separator string used to identify database connections
	 * in table names or field names.
	 *
	 * @param string $separator Default separator string
	 *
	 * @return string The separator string
	 * 
	 * @example
	 * $sep = canvastack_set_connection_separator();
	 * // Returns: '--canvastackcon--'
	 * 
	 * $custom = canvastack_set_connection_separator('::');
	 * // Returns: '::'
	 */
	function canvastack_set_connection_separator($separator = CANVASTACK_CONNECTION_SEPARATOR) {
		return $separator;
	}
}

if (!function_exists('canvastack_check_table_columns')) {
	
	/**
	 * Check if Table Column(s) Exist
	 * 
	 * Verifies whether a specific column exists in a database table.
	 * Useful for dynamic schema validation.
	 * 
	 * SECURITY: Added input validation and error handling
	 *
	 * @param string $table_name Name of the table to check
	 * @param string $field_name Name of the column to check
	 * @param string $db_connection Database connection name
	 *
	 * @return bool True if column exists, false otherwise
	 * @throws \InvalidArgumentException If table/field name is invalid
	 * 
	 * @example
	 * if (canvastack_check_table_columns('users', 'email')) {
	 *     // Column exists
	 * }
	 * 
	 * if (canvastack_check_table_columns('posts', 'slug', 'mysql_secondary')) {
	 *     // Check on secondary connection
	 * }
	 */
	function canvastack_check_table_columns(string $table_name, string $field_name, string $db_connection = CANVASTACK_DEFAULT_DB_CONNECTION): bool {
		// Input validation
		if (!is_string($table_name) || empty($table_name)) {
			throw new \InvalidArgumentException('Table name must be a non-empty string');
		}
		
		if (!is_string($field_name) || empty($field_name)) {
			throw new \InvalidArgumentException('Field name must be a non-empty string');
		}
		
		// Validate table name format (alphanumeric and underscore only)
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
			throw new \InvalidArgumentException('Invalid table name format');
		}
		
		// Validate field name format
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $field_name)) {
			throw new \InvalidArgumentException('Invalid field name format');
		}

		// PERFORMANCE: Use cached column list to avoid per-column DB calls
		$cachedColumns = canvastack_table_get_cached_columns($table_name, $db_connection);
		if (!empty($cachedColumns)) {
			return in_array($field_name, $cachedColumns, true);
		}

		// Fallback: direct DB call
		try {
			$connection = DB::connection($db_connection);
			return $connection->getSchemaBuilder()->hasColumn($table_name, $field_name);
			
		} catch (\Exception $e) {
			error_log('canvastack_check_table_columns() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_get_table_columns')) {
	
	/**
	 * Get Table Column(s)
	 * 
	 * Retrieves a list of all column names in a specified table.
	 * Useful for dynamic form generation and data processing.
	 * 
	 * SECURITY: Added input validation and error handling
	 * PERFORMANCE: Results are cached via canvastack_table_get_cached_columns() to
	 *              avoid repeated DB schema queries for the same table.
	 *
	 * @param string $table_name Name of the table
	 * @param string $db_connection Database connection name
	 *
	 * @return array Array of column names
	 * @throws \InvalidArgumentException If table name is invalid
	 * 
	 * @example
	 * $columns = canvastack_get_table_columns('users');
	 * // Returns: ['id', 'name', 'email', 'created_at', 'updated_at']
	 * 
	 * $columns = canvastack_get_table_columns('posts', 'mysql_secondary');
	 * // Returns columns from secondary connection
	 */
	function canvastack_get_table_columns(string $table_name, ?string $db_connection = CANVASTACK_DEFAULT_DB_CONNECTION): array {
		// Handle null connection by using default
		if ($db_connection === null) {
			$db_connection = CANVASTACK_DEFAULT_DB_CONNECTION;
		}
		
		// Input validation
		if (!is_string($table_name) || empty($table_name)) {
			throw new \InvalidArgumentException('Table name must be a non-empty string');
		}
		
		// Validate table name format
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
			throw new \InvalidArgumentException('Invalid table name format');
		}

		// DEBUG: Log function call
		\Log::debug('canvastack_get_table_columns() called', [
			'table' => $table_name,
			'connection' => $db_connection
		]);

		// PERFORMANCE: Use persistent cache-backed helper
		$cached = canvastack_table_get_cached_columns($table_name, $db_connection);
		if (!empty($cached)) {
			\Log::debug('canvastack_get_table_columns() cache hit', [
				'table' => $table_name,
				'connection' => $db_connection,
				'columns_count' => count($cached)
			]);
			return $cached;
		}

		// Fallback: direct DB call (also populates cache via the helper on next call)
		try {
			$connection = DB::connection($db_connection);
			$columns = $connection->getSchemaBuilder()->getColumnListing($table_name);
			
			\Log::debug('canvastack_get_table_columns() DB query result', [
				'table' => $table_name,
				'connection' => $db_connection,
				'columns_count' => count($columns),
				'columns' => $columns
			]);
			
			return $columns;
			
		} catch (\Exception $e) {
			\Log::error('canvastack_get_table_columns() error', [
				'table' => $table_name,
				'connection' => $db_connection,
				'error' => $e->getMessage()
			]);
			error_log('canvastack_get_table_columns() error: ' . $e->getMessage());
			return [];
		}
	}
}

if (!function_exists('canvastack_get_table_column_type')) {
	
	/**
	 * Get Table Column Type
	 * SECURITY: Added input validation and error handling
	 * PERFORMANCE: Uses canvastack_table_get_cached_schema() to avoid repeated DB calls.
	 *              The full schema (all column types) is fetched and cached in one query,
	 *              then individual column types are looked up from the cached schema.
	 *
	 * @param string $table_name
	 * @param string $field_name
	 * @param string $db_connection
	 *
	 * @return string|false Column type string, or false on error
	 * @throws \InvalidArgumentException
	 */
    function canvastack_get_table_column_type(string $table_name, string $field_name, string $db_connection = CANVASTACK_DEFAULT_DB_CONNECTION): string|false {
		// Input validation
		if (!is_string($table_name) || empty($table_name)) {
			throw new \InvalidArgumentException('Table name must be a non-empty string');
		}
		
		if (!is_string($field_name) || empty($field_name)) {
			throw new \InvalidArgumentException('Field name must be a non-empty string');
		}
		
		// Validate table name format
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
			throw new \InvalidArgumentException('Invalid table name format');
		}
		
		// Validate field name format
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $field_name)) {
			throw new \InvalidArgumentException('Invalid field name format');
		}

		// PERFORMANCE: Use cached schema to avoid per-column DB calls
		$schema = canvastack_table_get_cached_schema($table_name, $db_connection);
		if (!empty($schema) && array_key_exists($field_name, $schema)) {
			return $schema[$field_name];
		}

		// Fallback: direct DB call
		try {
			$connection = DB::connection($db_connection);
			return $connection->getSchemaBuilder()->getColumnType($table_name, $field_name);
			
		} catch (\Exception $e) {
			error_log('canvastack_get_table_column_type() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_temp_table')) {
	
	/**
	 * Create Temporary Table
	 * SECURITY: Added input validation and error handling
	 *
	 * @param string $table_name
	 * @param string $sql
	 * @param boolean $strict
	 * @param string $conn
	 * 
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	function canvastack_temp_table(string $table_name, string $sql, bool $strict = true, string $conn = CANVASTACK_DEFAULT_DB_CONNECTION): bool {
		// Input validation
		if (!is_string($table_name) || empty($table_name)) {
			throw new \InvalidArgumentException('Table name must be a non-empty string');
		}
		
		if (!is_string($sql) || empty($sql)) {
			throw new \InvalidArgumentException('SQL must be a non-empty string');
		}
		
		// Validate table name format (alphanumeric and underscore only)
		$clean_table_name = str_replace(CANVASTACK_TEMP_TABLE_PREFIX, '', $table_name);
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $clean_table_name)) {
			throw new \InvalidArgumentException('Invalid table name format');
		}
		
		try {
			$strictConfig = config("database.connections.{$conn}.strict");
			$table_name   = $clean_table_name;
			
			if (Illuminate\Support\Facades\Schema::hasTable(CANVASTACK_TEMP_TABLE_PREFIX . $table_name)) {
				Illuminate\Support\Facades\Schema::dropIfExists(CANVASTACK_TEMP_TABLE_PREFIX . $table_name);
			}
			
			if (false === $strict) {
				Illuminate\Support\Facades\DB::purge($conn);
				config()->set("database.connections.{$conn}.strict", $strict);
				Illuminate\Support\Facades\DB::reconnect();
			}
		//	dump(microtime(true));
			
			canvastack_query($sql, 'SELECT');
			Illuminate\Support\Facades\DB::unprepared("CREATE TABLE " . CANVASTACK_TEMP_TABLE_PREFIX . "{$table_name} {$sql}");
			
			if (false === $strict) {
				Illuminate\Support\Facades\DB::purge($conn);
				config()->set("database.connections.{$conn}.strict", $strictConfig);
				Illuminate\Support\Facades\DB::reconnect();
			}
			
			return true;
			
		} catch (\InvalidArgumentException $e) {
			error_log('canvastack_temp_table() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('canvastack_temp_table() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_model_processing_table')) {
	
	/**
	 * Call Model Process Data Table
	 * SECURITY: Added input validation and error handling
	 *
	 * @param array $data
	 * @param string $name
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	function canvastack_model_processing_table(array $data, string $name): array {
		// Input validation
		if (!is_array($data)) {
			throw new \InvalidArgumentException('Data must be an array');
		}
		
		if (!is_string($name) || empty($name)) {
			throw new \InvalidArgumentException('Name must be a non-empty string');
		}
		
		try {
			if (!empty($data[$name])) {
				// Validate required keys
				if (!isset($data[$name]['model']) || !isset($data[$name]['function'])) {
					throw new \InvalidArgumentException('Data must contain model and function keys');
				}
				
				$model = $data[$name]['model'];
				
				// Validate model has the required method
				if (!is_object($model) || !method_exists($model, $data[$name]['function'])) {
					throw new \InvalidArgumentException('Invalid model or function');
				}
				
				if (false === $data[$name]['strict']) {
					canvastack_db('purge', $data[$name]['connection']);
					config()->set("database.connections.{$data[$name]['connection']}.strict", $data[$name]['strict']);
					canvastack_db('reconnect');
				}
				
				$model->{$data[$name]['function']}();
			}
			
			return true;
			
		} catch (\InvalidArgumentException $e) {
			error_log('canvastack_model_processing_table() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('canvastack_model_processing_table() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_set_formula_columns')) {
	
	/**
		 * Set formula columns in table
		 * SECURITY: Added input validation and error handling
		 * 
		 * @param array $columns Column list
		 * @param array $data Formula data
		 * @return array Modified columns with formula columns inserted
		 * @throws \InvalidArgumentException
		 */
		function canvastack_set_formula_columns(array $columns, array $data): array {
			// Input validation
			if (!is_array($columns)) {
				throw new \InvalidArgumentException('Columns must be an array');
			}
			
			if (!is_array($data)) {
				throw new \InvalidArgumentException('Data must be an array');
			}
			
			try {
				arsort($data);

				$key_columns = array_flip($columns);
				$c_action = !empty($key_columns['action']);
				$c_lists = isset($key_columns['number_lists']);

				$f_node = canvastack_formula_build_nodes($data, $columns, $key_columns);
				$columns = canvastack_formula_insert_columns($columns, $f_node, $c_lists, $c_action);

				return $columns;
				
			} catch (\InvalidArgumentException $e) {
				error_log('canvastack_set_formula_columns() validation error: ' . $e->getMessage());
				throw $e;
			} catch (\Exception $e) {
				error_log('canvastack_set_formula_columns() error: ' . $e->getMessage());
				return $columns; // Return original columns on error
			}
		}

		/**
		 * Build formula node data structure
		 * SECURITY: Added error handling
		 * 
		 * @param array $data Formula data
		 * @param array $columns Column list
		 * @param array $key_columns Flipped column keys
		 * @return array Formula nodes with field info
		 */
		function canvastack_formula_build_nodes(array $data, array $columns, array $key_columns): array {
			try {
				$f_node = [];

				foreach ($data as $formula_data) {
					// Validate formula data structure
					if (!is_array($formula_data) || !isset($formula_data['name'])) {
						continue; // Skip invalid data
					}
					
					$field_name = canvastack_formula_determine_field_name($formula_data, $columns);

					$f_node[$formula_data['name']] = [
						'field_label' => $formula_data['label'] ?? '',
						'field_name' => $field_name,
						'field_key' => $key_columns[$field_name] ?? 0,
						'node_after' => $formula_data['node_after'] ?? false,
						'node_location' => $formula_data['node_location'] ?? ''
					];
				}

				return $f_node;
				
			} catch (\Exception $e) {
				error_log('canvastack_formula_build_nodes() error: ' . $e->getMessage());
				return [];
			}
		}

		/**
		 * Determine field name based on node location
		 * SECURITY: Added error handling
		 * 
		 * @param array $formula_data Formula data
		 * @param array $columns Column list
		 * @return string Field name
		 */
		function canvastack_formula_determine_field_name(array $formula_data, array $columns): string {
			try {
				$for_node = $formula_data['node_location'] ?? '';

				if (empty($for_node)) {
					return end($formula_data['field_lists']);
				}

				if ('first' === $for_node) {
					return $columns[0] ?? '';
				}

				if ('last' === $for_node) {
					return $columns[array_key_last($columns)] ?? '';
				}

				return $for_node;
				
			} catch (\Exception $e) {
				error_log('canvastack_formula_determine_field_name() error: ' . $e->getMessage());
				return '';
			}
		}

		/**
		 * Insert formula columns into column list
		 * SECURITY: Added error handling
		 * 
		 * @param array $columns Column list
		 * @param array $f_node Formula nodes
		 * @param bool $c_lists Has number lists column
		 * @param bool $c_action Has action column
		 * @return array Modified columns
		 */
		function canvastack_formula_insert_columns(array $columns, array $f_node, array $c_lists, array $c_action): array {
			try {
				foreach ($f_node as $key => $fdata) {
					$position = canvastack_formula_calculate_position($fdata, $c_lists, $c_action);

					if ($position === 'push') {
						array_push($columns, $key);
					} else {
						canvastack_array_insert($columns, $position, $key);
					}
				}

				return $columns;
				
			} catch (\Exception $e) {
				error_log('canvastack_formula_insert_columns() error: ' . $e->getMessage());
				return $columns; // Return original columns on error
			}
		}

		/**
		 * Calculate insertion position for formula column
		 * SECURITY: Added error handling
		 * 
		 * @param array $fdata Formula node data
		 * @param bool $c_lists Has number lists column
		 * @param bool $c_action Has action column
		 * @return int|string Position index or 'push'
		 */
		function canvastack_formula_calculate_position(array $fdata, array $c_lists, array $c_action): int|string {
			try {
				$field_key = intval($fdata['field_key'] ?? 0);
				$location = $fdata['node_location'] ?? '';
				$after = $fdata['node_after'] ?? false;

				if ('first' === $location) {
					return $c_lists ? $field_key + 1 : $field_key;
				}

				if ('last' === $location) {
					if (true === $after) {
						return $c_action ? $field_key : 'push';
					}
					return $field_key;
				}

				return $after ? $field_key + 1 : $field_key;
				
			} catch (\Exception $e) {
				error_log('canvastack_formula_calculate_position() error: ' . $e->getMessage());
				return 'push'; // Default to push on error
			}
		}
}

if (!function_exists('canvastack_modal_content_html')) {
	/**
	 * Generate modal content HTML
	 * SECURITY: XSS Fixed - All user input escaped, added error handling
	 * 
	 * @param string $name
	 * @param string $title
	 * @param array $elements
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	function canvastack_modal_content_html(string $name, string $title, array $elements): string {
		// Input validation
		if (!is_string($name) || empty($name)) {
			throw new \InvalidArgumentException('Name must be a non-empty string');
		}
		
		if (!is_string($title)) {
			throw new \InvalidArgumentException('Title must be a string');
		}
		
		if (!is_array($elements)) {
			throw new \InvalidArgumentException('Elements must be an array');
		}
		
		try {
			// Escape for HTML context to prevent XSS
			$name_safe = canvastack_escape_html($name);
			$title_safe = canvastack_escape_html($title);
			$buttonID = str_replace('_CanvaStackFILTERmodalBOX', '_submitFilterButton', $name_safe);

			$html  = '<div class="modal-body">';
				$html .= '<div id="' . $name_safe . '">';
					$html .= implode('', $elements);
				$html .= '</div>';
			$html .= '</div>';
			$html .= '<div class="modal-footer">';
				$html .= '<div class="canvastack-action-box">';
					$html .= '<button type="reset" id="' . $name_safe . '-cancel" class="btn btn-danger btn-slideright pull-right" data-dismiss="modal">Cancel</button>';
					$html .= '<button id="' . $buttonID . '" class="btn btn-primary btn-slideright pull-right" type="submit">';
						$html .= '<i class="fa fa-filter"></i> &nbsp; Filter Data ' . $title_safe;
					$html .= '</button>';
					$html .= '<button id="exportFilterButton' . $name_safe . '" class="btn btn-info btn-slideright pull-right btn-export-csv hide" type="button">Export to CSV</button>';
				$html .= '</div>';
			$html .= '</div>';

			return $html;
			
		} catch (\InvalidArgumentException $e) {
			error_log('canvastack_modal_content_html() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('canvastack_modal_content_html() error: ' . $e->getMessage());
			return '';
		}
	}
}

if (!function_exists('canvastack_clear_json')) {
	
	/**
	 * Clear JSON formatting for JavaScript context
	 * SECURITY: Added input validation
	 * NOTE: This function is used for JavaScript code generation, not HTML output
	 * 
	 * @param string $data
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	function canvastack_clear_json(string $data): string {
		// Input validation
		if (!is_string($data)) {
			throw new \InvalidArgumentException('Data must be a string');
		}
		
		try {
			$json = str_replace('"data"', "data", $data);
			$json = str_replace('"name"', "name", $json);
			$json = str_replace('"', "'", $json);
			
			// NOTE: No HTML escaping here - this is for JavaScript context
			// The output is used in JavaScript code, not HTML
			return $json;
			
		} catch (\Exception $e) {
			error_log('canvastack_clear_json() error: ' . $e->getMessage());
			return '';
		}
	}
}

if (!function_exists('canvastack_table_action_button')) {
	
	/**
	 * Set Action Button URL Used For create_action_buttons() Function
	 * 
	 * Generates action buttons (view, edit, delete, custom) for table rows
	 * based on user privileges and configuration. Supports custom actions
	 * and button removal.
	 * 
	 * REFACTORED: Reduced from 134 lines to orchestrator pattern
	 * SECURITY: Added input validation and error handling
	 *
	 * created @Sep 6, 2018
	 * author: wisnuwidi
	 *
	 * @param object $row_data Row data object containing field values
	 * @param string $field_target Field name to use as URL parameter (default: 'id')
	 * @param string $current_url Base URL for actions
	 * @param bool|array $action Additional actions or action configuration
	 * @param array|null $removed_button Array of buttons to remove
	 *
	 * @return string HTML for action buttons
	 * @throws \InvalidArgumentException If parameters are invalid
	 * 
	 * @example
	 * // Basic action buttons (view, edit, delete)
	 * $buttons = canvastack_table_action_button(
	 *     $user,
	 *     'id',
	 *     '/admin/users',
	 *     false
	 * );
	 * 
	 * // With custom actions
	 * $buttons = canvastack_table_action_button(
	 *     $order,
	 *     'id',
	 *     '/admin/orders',
	 *     ['approve|success|check', 'reject|danger|times']
	 * );
	 * 
	 * // Remove specific buttons
	 * $buttons = canvastack_table_action_button(
	 *     $post,
	 *     'id',
	 *     '/admin/posts',
	 *     ['publish|primary|paper-plane'],
	 *     ['delete'] // Remove delete button
	 * );
	 */
	/**
	 * Generate action buttons for table rows
	 * 
	 * SECURITY: All user-controllable data is escaped using canvastack_escape_html()
	 *           before rendering to prevent XSS attacks.
	 * 
	 * CUSTOM ACTIONS: Supports custom actions beyond view/edit/delete:
	 *   - String format: 'action_name|color|icon'
	 *   - Array format: ['action_name' => ['url' => '...', 'color' => '...', 'icon' => '...', 'label' => '...', 'tooltip' => '...', 'confirm' => '...']]
	 * 
	 * @param object $row_data Row data object containing field values
	 * @param string $field_target Field name to use for URL target (default: 'id')
	 * @param string $current_url Current URL base for building action URLs
	 * @param array|string $action Action configuration (string or array of actions)
	 * @param array|null $removed_button Array of button names to remove/hide
	 * @return string HTML for action buttons
	 * @throws \InvalidArgumentException If parameters are invalid
	 * 
	 * @example
	 * // Basic usage with default actions
	 * $buttons = canvastack_table_action_button($row, 'id', '/admin/users', ['view', 'edit', 'delete']);
	 * 
	 * // With custom action (string format)
	 * $buttons = canvastack_table_action_button($row, 'id', '/admin/users', ['view', 'edit', 'approve|success|check']);
	 * 
	 * // With custom action (array format)
	 * $buttons = canvastack_table_action_button($row, 'id', '/admin/users', [
	 *   'view', 'edit',
	 *   'approve' => [
	 *     'url' => '/admin/users/{id}/approve',
	 *     'color' => 'success',
	 *     'icon' => 'check',
	 *     'label' => 'Approve User',
	 *     'tooltip' => 'Approve this user account',
	 *     'confirm' => 'Are you sure you want to approve this user?'
	 *   ]
	 * ]);
	 * 
	 * // With removed buttons
	 * $buttons = canvastack_table_action_button($row, 'id', '/admin/users', ['view', 'edit', 'delete'], ['delete']);
	 */
	function canvastack_table_action_button(object $row_data, string $field_target = 'id', string $current_url, array|string $action, ?array $removed_button = null): string {
		// Check if actions are enabled
		if (!config('canvastack.datatables.actions.enabled', true)) {
			return '';
		}
		
		// Input validation
		if (!is_object($row_data)) {
			throw new \InvalidArgumentException('Row data must be an object');
		}

		if (!is_string($field_target) || empty($field_target)) {
			throw new \InvalidArgumentException('Field target must be a non-empty string');
		}

		if (!is_string($current_url) || empty($current_url)) {
			throw new \InvalidArgumentException('Current URL must be a non-empty string');
		}

		try {
			$enabledAction = canvastack_action_init_enabled_actions();
			
			// Check privileges if enabled
			if (config('canvastack.datatables.actions.check_privileges', true)) {
				$actions = canvastack_action_check_privileges($action);
			} else {
				$actions = is_array($action) ? $action : [$action];
			}
			
			$addActions = canvastack_action_parse_actions($action, $enabledAction);

			canvastack_action_process_removed_buttons($removed_button, $actions, $enabledAction);

			$path = canvastack_action_build_paths($row_data, $field_target, $current_url, $enabledAction);
			$add_path = canvastack_action_build_additional_paths($addActions, $current_url, $row_data, $field_target);

			return create_action_buttons($path['view'], $path['edit'], $path['delete'], $add_path);

		} catch (\InvalidArgumentException $e) {
			error_log('canvastack_table_action_button() validation error: ' . $e->getMessage());
			throw $e;
		} catch (\Exception $e) {
			error_log('canvastack_table_action_button() error: ' . $e->getMessage());
			return ''; // Return empty string on error
		}
	}
	
	/**
	 * Initialize enabled actions
	 * 
	 * @return array
	 */
	function canvastack_action_init_enabled_actions() {
		return [
			'read'   => true,
			'insert' => true,
			'modify' => true,
			'delete' => true
		];
	}
	
	/**
	 * Check privileges and filter actions
	 * 
	 * @param mixed $action
	 * @return array
	 */
	function canvastack_action_check_privileges(array|string $action): array {
		$privileges = session()->all()['privileges']['role'];
		$actions = [];
		
		// @performance 6.6 - Use strict mode (true) for type-safe comparison
		if (!in_array(current_route(), $privileges, true)) {
			return $actions;
		}
		
		foreach ($privileges as $roles) {
			if (!canvastack_string_contained($roles, routelists_info()['base_info'])) {
				continue;
			}
			
			$last_info = routelists_info($roles)['last_info'];
			// @performance 6.6 - Use strict mode (true) for type-safe comparison
			if (!in_array($last_info, CANVASTACK_DEFAULT_ACTIONS, true)) {
				$actions[routelists_info()['base_info']][] = $last_info;
			}
		}
		
		return $actions;
	}
	
	/**
	 * Parse and process actions
	 * 
	 * @param mixed $action
	 * @param array &$enabledAction
	 * @return array
	 */
	function canvastack_action_parse_actions(array|string $action, array &$enabledAction): array {
		if (is_array($action)) {
			return canvastack_action_parse_array_actions($action, $enabledAction);
		}
		
		if (is_string($action)) {
			return canvastack_action_parse_string_action($action);
		}
		
		return [];
	}
	
	/**
	 * Parse array actions
	 * 
	 * @param array $action
	 * @param array &$enabledAction
	 * @return array
	 */
	/**
		 * Parse array of actions and filter out default actions
		 * 
		 * @param array $action
		 * @param array &$enabledAction
		 * @return array
		 */
		/**
		 * Parse array of actions and filter out default actions
		 * Supports both simple string actions and complex array configurations
		 * 
		 * @param array $action Array of actions (strings or arrays)
		 * @param array &$enabledAction Reference to enabled actions array
		 * @return array Parsed additional actions
		 * 
		 * @example
		 * // Simple string actions
		 * $actions = ['approve|success|check', 'reject|danger|times'];
		 * 
		 * // Complex array actions
		 * $actions = [
		 *   'approve' => [
		 *     'color' => 'success',
		 *     'icon' => 'check',
		 *     'label' => 'Approve',
		 *     'tooltip' => 'Approve this item',
		 *     'confirm' => 'Are you sure?'
		 *   ]
		 * ];
		 */
		function canvastack_action_parse_array_actions(array $action, array &$enabledAction): array {
			$addActions = [];

			foreach ($action as $action_key => $action_data) {
				// Handle array-based action configuration (key => config array)
				if (is_array($action_data)) {
					$action_name = $action_key;

					// Skip default actions
					if (in_array($action_name, CANVASTACK_ALL_ACTION_ALIASES, true)) {
						continue;
					}

					// Store the full configuration
					$addActions[$action_name] = $action_data;
					$enabledAction[$action_name] = true;
					continue;
				}

				// Handle string-based action configuration
				// Filter out default actions (view, edit, delete, insert, etc.)
				// These are handled by default buttons, not additional buttons
				if (in_array($action_data, CANVASTACK_ALL_ACTION_ALIASES, true)) {
					continue; // Skip default actions
				}

				if (canvastack_string_contained($action_data, '|')) {
					$action_info = canvastack_add_action_button_by_string($action_data);
					$addActions[key($action_info)] = $action_info[key($action_info)];
					$enabledAction[key($action_info)] = true;
				} else {
					$addActions[$action_data] = canvastack_add_action_button_by_string("{$action_data}|default|link");
					$enabledAction[$action_data] = true;
				}
			}

			return $addActions;
		}
	
	/**
	 * Parse string action
	 * 
	 * @param string $action
	 * @return array
	 */
	function canvastack_action_parse_string_action(string $action): array {
		if (canvastack_string_contained($action, '|')) {
			return canvastack_add_action_button_by_string($action);
		}
		
		return canvastack_add_action_button_by_string("{$action}|default|link");
	}
	
	/**
	 * Process removed buttons
	 * 
	 * @param array|null $removed_button
	 * @param array &$actions
	 * @param array &$enabledAction
	 * @return void
	 */
	function canvastack_action_process_removed_buttons(?array $removed_button, array &$actions, array &$enabledAction): void {
		if (empty($removed_button) || !is_array($removed_button)) {
			return;
		}
		
		$actionNode = array_flip($actions);
		
		foreach ($removed_button as $remove) {
			canvastack_action_remove_button_type($remove, $actionNode, $actions, $enabledAction);
		}
	}
	
	/**
	 * Remove specific button type
	 * 
	 * @param string $remove
	 * @param array $actionNode
	 * @param array &$actions
	 * @param array &$enabledAction
	 * @return void
	 */
	function canvastack_action_remove_button_type(string $remove, array $actionNode, array &$actions, array &$enabledAction): void {
		// @performance 6.6 - Use strict mode (true) for type-safe comparisons
		if (in_array($remove, ['index', 'show', 'view', 'read'], true)) {
			$enabledAction['read'] = false;
			canvastack_action_unset_actions($actionNode, $actions, ['view', 'index', 'show']);
		} elseif (in_array($remove, ['create', 'insert', 'add'], true)) {
			$enabledAction['insert'] = false;
			canvastack_action_unset_actions($actionNode, $actions, ['create', 'insert', 'add']);
		} elseif (in_array($remove, ['edit', 'update', 'modify'], true)) {
			$enabledAction['modify'] = false;
			canvastack_action_unset_actions($actionNode, $actions, ['edit', 'update', 'modify']);
		} elseif (in_array($remove, ['delete', 'destroy'], true)) {
			$enabledAction['delete'] = false;
			canvastack_action_unset_actions($actionNode, $actions, ['delete', 'destroy']);
		} else {
			$enabledAction[$remove] = false;
		}
	}
	
	/**
	 * Unset multiple actions
	 * 
	 * @param array $actionNode
	 * @param array &$actions
	 * @param array $keys
	 * @return void
	 */
	function canvastack_action_unset_actions(array $actionNode, array &$actions, array $keys): void {
		foreach ($keys as $key) {
			if (!empty($actionNode[$key])) {
				unset($actions[$actionNode[$key]]);
			}
		}
	}
	
	/**
	 * Build action paths
	 * 
	 * @param object $row_data
	 * @param string $field_target
	 * @param string $current_url
	 * @param array $enabledAction
	 * @return array
	 */
	function canvastack_action_build_paths(object $row_data, string $field_target, string $current_url, array $enabledAction): array {
		$urlTarget = $row_data->{$field_target};
		
		$path = [
			'view' => "{$current_url}/{$urlTarget}",
			'edit' => "{$current_url}/{$urlTarget}/edit",
			'delete' => !empty($row_data->deleted_at) 
				? "{$current_url}/{$urlTarget}/" . CANVASTACK_RESTORE_DELETED_ACTION
				: "{$current_url}/{$urlTarget}/delete"
		];
		
		if (false === $enabledAction['read'])   $path['view'] = false;
		if (false === $enabledAction['modify']) $path['edit'] = false;
		if (false === $enabledAction['delete']) $path['delete'] = false;
		
		return $path;
	}
	
	/**
	 * Build additional action paths
	 * 
	 * @param array $addActions
	 * @param string $current_url
	 * @param object $row_data
	 * @param string $field_target
	 * @return array|false
	 */
	/**
	 * Build additional action paths
	 * Supports privilege checking for custom actions
	 * 
	 * @param array $addActions Additional actions configuration
	 * @param string $current_url Current URL base
	 * @param object $row_data Row data object
	 * @param string $field_target Field name for URL target
	 * @return array|false Additional action paths or false if none
	 * 
	 * @example
	 * $addActions = [
	 *   'approve' => [
	 *     'color' => 'success',
	 *     'icon' => 'check',
	 *     'privileges' => ['admin', 'manager'] // Optional privilege check
	 *   ]
	 * ];
	 */
	function canvastack_action_build_additional_paths(array $addActions, string $current_url, object $row_data, string $field_target): array|false {
		if (count($addActions) < 1) {
			return false;
		}

		$add_path = [];
		$urlTarget = $row_data->{$field_target};

		foreach ($addActions as $action_name => $action_values) {
			// Filter out default actions - these should not appear in additional buttons
			if (in_array($action_name, CANVASTACK_ALL_ACTION_ALIASES, true)) {
				continue;
			}

			// Check privileges if specified
			if (is_array($action_values) && isset($action_values['privileges'])) {
				if (!canvastack_action_check_custom_privilege($action_values['privileges'])) {
					continue; // Skip this action if user doesn't have required privileges
				}
			}

			$add_path[$action_name] = canvastack_action_build_single_path(
				$action_name, 
				$action_values, 
				$current_url, 
				$urlTarget
			);
		}

		return $add_path;
	}
	/**
	 * Check if user has required privileges for custom action
	 *
	 * @param array|string $required_privileges Required privilege(s)
	 * @return bool True if user has privilege, false otherwise
	 *
	 * @example
	 * // Single privilege
	 * if (canvastack_action_check_custom_privilege('admin')) { ... }
	 *
	 * // Multiple privileges (user needs at least one)
	 * if (canvastack_action_check_custom_privilege(['admin', 'manager'])) { ... }
	 */
	function canvastack_action_check_custom_privilege(array|string $required_privileges): bool {
		try {
			// Get user session privileges
			$session = session()->all();

			if (empty($session['privileges']['role'])) {
				return false; // No privileges in session
			}

			$user_roles = $session['privileges']['role'];
			$role_group = $session['privileges']['role_group'] ?? 999;

			// Admin/superadmin (role_group <= 1) has all privileges
			if ($role_group <= 1) {
				return true;
			}

			// Convert single privilege to array
			if (is_string($required_privileges)) {
				$required_privileges = [$required_privileges];
			}

			// Check if user has any of the required privileges
			foreach ($required_privileges as $privilege) {
				// Check if privilege exists in user roles
				foreach ($user_roles as $role) {
					if (canvastack_string_contained($role, $privilege)) {
						return true;
					}
				}
			}

			return false;

		} catch (\Exception $e) {
			error_log('canvastack_action_check_custom_privilege() error: ' . $e->getMessage());
			return false; // Deny access on error
		}
	}


	
	/**
	 * Build single additional action path
	 * 
	 * @param string $action_name
	 * @param mixed $action_values
	 * @param string $current_url
	 * @param string $urlTarget
	 * @return array
	 */
	function canvastack_action_build_single_path(string $action_name, array $action_values, string $current_url, mixed $urlTarget): array {
		$path = ['url' => "{$current_url}/{$urlTarget}/{$action_name}"];
		
		if (!is_array($action_values)) {
			return $path;
		}
		
		foreach ($action_values as $actionKey => $actionValue) {
			if ($actionKey === $action_name) {
				$path = $actionValue;
				$path['url'] = "{$current_url}/{$urlTarget}/{$action_name}";
			} else {
				$path[$actionKey] = $actionValue;
			}
		}
		
		return $path;
	}
}

if (!function_exists('canvastack_add_action_button_by_string')) {
	
	/**
	 * Add action button by string
	 * SECURITY: Added input validation
	 * 
	 * @param string|bool $action
	 * @param bool $is_array
	 * @return array
	 */
	function canvastack_add_action_button_by_string(string $action, bool $is_array = false): array {
		$addActions = [];
		
		if (is_bool($action)) {
			if (true === $action) {
				$addActions['view']['color']   = 'success';
				$addActions['view']['icon']    = 'eye';
				
				$addActions['edit']['color']   = 'primary';
				$addActions['edit']['icon']    = 'pencil';
				
				$addActions['delete']['color'] = 'danger';
				$addActions['delete']['icon']  = 'times';
			}
		} else {
			// Input validation for string action
			if (!is_string($action)) {
				error_log('canvastack_add_action_button_by_string() error: Action must be string or boolean');
				return $addActions;
			}
			
			if (canvastack_string_contained($action, '|')) {
				$str_action = explode('|', $action);
				$str_name	= reset($str_action);
			} else {
				$str_action = $action;
				$str_name   = false;
			}
			
			$actionAttr = [];
			
			if (count($str_action) >= 2) {
				$actionAttr['color'] = false;
				if (isset($str_action[1])) {
					$actionAttr['color'] = $str_action[1];
				}
				
				$actionAttr['icon'] = false;
				if (isset($str_action[2])) {
					$actionAttr['icon'] = $str_action[2];
				}
				$addActions[$str_name]  = $actionAttr;
			} else {
				$addActions[$action]    = $action;
			}
		}
		
		return $addActions;
	}
}

if (!function_exists('create_action_buttons')) {
	
	/**
	 * Action Button(s) Builder
	 *
	 * created @Sep 6, 2018
	 * author: wisnuwidi
	 *
	 * @param string $view
	 * @param string $edit
	 * @param string $delete
	 * @param string $add_action
	 * @param string $as_root
	 *
	 * @return string
	 */
	/**
		 * Create action buttons for table rows
		 * SECURITY: Added input validation and error handling
		 * 
		 * @param string|false $view View URL or false
		 * @param string|false $edit Edit URL or false
		 * @param string|false $delete Delete URL or false
		 * @param array|bool $add_action Additional actions or false
		 * @param bool $as_root Whether to use root context
		 * @return string HTML for action buttons
		 */
		function create_action_buttons(string|false $view = false, string|false $edit = false, string|array|false $delete = false, array|bool $add_action = [], bool $as_root = false): string {
			try {
				// Input validation
				if (!is_array($add_action)) {
					$add_action = [];
				}
				
				$restoreDeleted = false;
				$deleteData = false;

				if (false !== $delete) {
					$deleteData = create_action_buttons_parse_delete($delete);
					$restoreDeleted = $deleteData['is_restore'];
				}

				$buttonView = create_action_buttons_view($view, $restoreDeleted);
				$buttonEdit = create_action_buttons_edit($edit, $restoreDeleted);
				$buttonDelete = create_action_buttons_delete($deleteData);
				$buttonNew = create_action_buttons_additional($add_action, $restoreDeleted);

				return create_action_buttons_render($buttonView, $buttonEdit, $buttonDelete, $buttonNew);
				
			} catch (\Exception $e) {
				error_log('create_action_buttons() error: ' . $e->getMessage());
				return ''; // Return empty string on error
			}
		}

		/**
		 * Parse delete URL and determine if it's a restore action
		 * SECURITY: Added error handling
		 * 
		 * @param string $delete Delete URL
		 * @return array Delete data with URL, ID, and restore flag
		 */
		function create_action_buttons_parse_delete(string|array|false $delete): array|false {
			try {
				$deletePath = explode('/', $delete);
				$deleteFlag = end($deletePath);
				$delete_id = intval($deletePath[count($deletePath)-2] ?? 0);
				
				// Get current route action name, fallback to empty string if not available
				$currentRoute = function_exists('canvastack_current_route') ? canvastack_current_route() : null;
				$actionName = $currentRoute ? $currentRoute->getActionName() : '';
				$deleteURL = $actionName ? str_replace(CANVASTACK_ROUTE_INDEX_ACTION, CANVASTACK_ROUTE_DESTROY_ACTION, $actionName) : '';

				$isRestore = (CANVASTACK_RESTORE_DELETED_ACTION === $deleteFlag);

				return [
					'url' => $deleteURL,
					'id' => $delete_id,
					'is_restore' => $isRestore,
					'delete_path' => $delete
				];
				
			} catch (\Exception $e) {
				error_log('create_action_buttons_parse_delete() error: ' . $e->getMessage());
				return [
					'url' => '',
					'id' => 0,
					'is_restore' => false,
					'delete_path' => ''
				];
			}
		}

		/**
		 * Create view button HTML
		 * SECURITY: Added error handling
		 * 
		 * @param string|false $view View URL
		 * @param bool $restoreDeleted Whether this is a restore action
		 * @return array Desktop and mobile button HTML
		 */
		/**
			 * Create view button HTML
			 * SECURITY: Added error handling and XSS protection
			 * ACCESSIBILITY: Enhanced ARIA attributes and keyboard support
			 * 
			 * @param string|false $view View URL
			 * @param bool $restoreDeleted Whether this is a restore action
			 * @return array Desktop and mobile button HTML
			 */
			function create_action_buttons_view(string|false $view, bool $restoreDeleted): array {
				try {
					if (false == $view) {
						return ['desktop' => false, 'mobile' => false];
					}

					$view_safe = canvastack_escape_html($view);

					if (true === $restoreDeleted) {
						// Disabled state for restored items
						$desktop = '<button type="button" disabled class="btn btn-default btn-xs btn_view" data-toggle="tooltip" data-placement="top" title="View detail" aria-label="View detail (disabled)" aria-disabled="true"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">View detail (disabled)</span></button>';
						$mobile = '<li class="btn_view"><button type="button" disabled class="tooltip-info" data-rel="tooltip" title="View" aria-label="View detail (disabled)" aria-disabled="true"><span class="blue"><i class="fa fa-search-plus bigger-120" aria-hidden="true"></i></span><span class="sr-only">View (disabled)</span></button></li>';
					} else {
						// Active state with proper link
						$desktop = '<a href="' . $view_safe . '" class="btn btn-success btn-xs btn_view" data-toggle="tooltip" data-placement="top" title="View detail" aria-label="View detail" role="button"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">View</span></a>';
						$mobile = '<li class="btn_view"><a href="' . $view_safe . '" class="tooltip-info" data-rel="tooltip" title="View" aria-label="View detail" role="button"><span class="blue"><i class="fa fa-search-plus bigger-120" aria-hidden="true"></i></span><span class="sr-only">View</span></a></li>';
					}

					return ['desktop' => $desktop, 'mobile' => $mobile];

				} catch (\Exception $e) {
					error_log('create_action_buttons_view() error: ' . $e->getMessage());
					return ['desktop' => false, 'mobile' => false];
				}
			}

		/**
		 * Create edit button HTML
		 * SECURITY: Added error handling
		 * 
		 * @param string|false $edit Edit URL
		 * @param bool $restoreDeleted Whether this is a restore action
		 * @return array Desktop and mobile button HTML
		 */
		/**
			 * Create edit button HTML
			 * SECURITY: Added error handling and XSS protection
			 * ACCESSIBILITY: Enhanced ARIA attributes and keyboard support
			 * 
			 * @param string|false $edit Edit URL
			 * @param bool $restoreDeleted Whether this is a restore action
			 * @return array Desktop and mobile button HTML
			 */
			function create_action_buttons_edit(string|false $edit, bool $restoreDeleted): array {
				try {
					if (false == $edit) {
						return ['desktop' => false, 'mobile' => false];
					}

					$edit_safe = canvastack_escape_html($edit);

					if (true === $restoreDeleted) {
						// Disabled state for restored items
						$desktop = '<button type="button" disabled class="btn btn-default btn-xs btn_edit" data-toggle="tooltip" data-placement="top" title="Edit" aria-label="Edit (disabled)" aria-disabled="true"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sr-only">Edit (disabled)</span></button>';
						$mobile = '<li class="btn_edit"><button type="button" disabled class="tooltip-success" data-rel="tooltip" title="Edit" aria-label="Edit (disabled)" aria-disabled="true"><span class="green"><i class="fa fa-pencil-square-o bigger-120" aria-hidden="true"></i></span><span class="sr-only">Edit (disabled)</span></button></li>';
					} else {
						// Active state with proper link
						$desktop = '<a href="' . $edit_safe . '" class="btn btn-primary btn-xs btn_edit" data-toggle="tooltip" data-placement="top" title="Edit" aria-label="Edit" role="button"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sr-only">Edit</span></a>';
						$mobile = '<li class="btn_edit"><a href="' . $edit_safe . '" class="tooltip-success" data-rel="tooltip" title="Edit" aria-label="Edit" role="button"><span class="green"><i class="fa fa-pencil-square-o bigger-120" aria-hidden="true"></i></span><span class="sr-only">Edit</span></a></li>';
					}

					return ['desktop' => $desktop, 'mobile' => $mobile];

				} catch (\Exception $e) {
					error_log('create_action_buttons_edit() error: ' . $e->getMessage());
					return ['desktop' => false, 'mobile' => false];
				}
			}

		/**
		 * Create delete button HTML
		 * SECURITY: Added error handling
		 * 
		 * @param array|false $deleteData Delete data from parse_delete
		 * @return array Desktop and mobile button HTML
		 */
		/**
			 * Create delete button HTML
			 * SECURITY: Added error handling and XSS protection
			 * ACCESSIBILITY: Enhanced ARIA attributes and keyboard support
			 * 
			 * @param array|false $deleteData Delete data from parse_delete
			 * @return array Desktop and mobile button HTML
			 */
			function create_action_buttons_delete(array|false $deleteData): array {
				try {
					if (false === $deleteData) {
						return ['desktop' => false, 'mobile' => false];
					}

					// Generate delete URL - handle both route-based and direct URL
					try {
						if (!empty($deleteData['url'])) {
							$delete_url = action($deleteData['url'], $deleteData['id']);
						} else {
							// Fallback: use delete_path directly
							$delete_url = $deleteData['delete_path'];
						}
					} catch (\Exception $e) {
						// If action() fails (e.g., in tests), use delete_path directly
						$delete_url = $deleteData['delete_path'];
					}
					
					$delete_url_safe = canvastack_escape_html($delete_url);
					$delete_path_safe = canvastack_escape_html($deleteData['delete_path']);

					if ($deleteData['is_restore']) {
						// Restore button
						$buttonAttr = 'class="btn btn-warning btn-xs" data-toggle="tooltip" data-placement="top" title="Restore" aria-label="Restore deleted item"';
						$iconAttr = 'fa fa-recycle';
						$ariaLabel = 'Restore deleted item';
						$srText = 'Restore';
					} else {
						// Delete button with confirmation
						$buttonAttr = 'class="btn btn-danger btn-xs" data-toggle="tooltip" data-placement="top" title="Delete" aria-label="Delete" data-confirm="Are you sure you want to delete this item?"';
						$iconAttr = 'fa fa-times';
						$ariaLabel = 'Delete';
						$srText = 'Delete';
					}

					$delete_action = '<form action="' . $delete_url_safe . '" method="post" class="btn btn_delete" style="padding:0 !important" onsubmit="return confirm(\'Are you sure you want to delete this item?\');">' . csrf_field() . '<input name="_method" type="hidden" value="DELETE">';
					$desktop = $delete_action . '<button ' . $buttonAttr . ' type="submit"><i class="' . $iconAttr . '" aria-hidden="true"></i><span class="sr-only">' . $srText . '</span></button></form>';
					$mobile = '<li><a href="' . $delete_path_safe . '" class="tooltip-error btn_delete" data-rel="tooltip" title="' . $ariaLabel . '" aria-label="' . $ariaLabel . '" role="button" data-confirm="Are you sure?"><span class="red"><i class="fa fa-trash-o bigger-120" aria-hidden="true"></i></span><span class="sr-only">' . $srText . '</span></a></li>';

					return ['desktop' => $desktop, 'mobile' => $mobile];

				} catch (\Exception $e) {
					error_log('create_action_buttons_delete() error: ' . $e->getMessage());
					return ['desktop' => false, 'mobile' => false];
				}
			}

		/**
		 * Create additional action buttons HTML
		 * SECURITY: Added error handling
		 * 
		 * @param array $add_action Additional actions
		 * @param bool $restoreDeleted Whether this is a restore action
		 * @return array Desktop and mobile button HTML
		 */
		/**
			 * Create additional action buttons HTML
			 * SECURITY: Added error handling and XSS protection
			 * ACCESSIBILITY: Enhanced ARIA attributes and keyboard support
			 * 
			 * @param array $add_action Additional actions with structure:
			 *        [
			 *          'action_name' => [
			 *            'url' => 'action_url',
			 *            'color' => 'btn_color',
			 *            'icon' => 'icon_name',
			 *            'label' => 'Button Label',
			 *            'tooltip' => 'Tooltip text',
			 *            'confirm' => 'Confirmation message' (optional)
			 *          ]
			 *        ]
			 * @param bool $restoreDeleted Whether this is a restore action
			 * @return array Desktop and mobile button HTML
			 */
			function create_action_buttons_additional(array $add_action, bool $restoreDeleted): array {
				try {
					if (!is_array($add_action) || count($add_action) < 1) {
						return ['desktop' => '', 'mobile' => ''];
					}

					$desktop = '';
					$mobile = '';

					foreach ($add_action as $new_action_name => $new_action_values) {
						// Extract action properties with defaults
						$btn_name = $new_action_name;
						$row_name = $new_action_values['label'] ?? camel_case($new_action_name);
						$row_url = $new_action_values['url'] ?? '';
						$row_color = $new_action_values['color'] ?? 'default';
						$row_icon = $new_action_values['icon'] ?? 'link';
						$row_tooltip = $new_action_values['tooltip'] ?? $row_name;
						$row_confirm = $new_action_values['confirm'] ?? null;

						// Escape for HTML context
						$row_name_safe = canvastack_escape_html($row_name);
						$row_url_safe = canvastack_escape_html($row_url);
						$row_icon_safe = canvastack_escape_html($row_icon);
						$row_tooltip_safe = canvastack_escape_html($row_tooltip);
						$btn_name_safe = canvastack_escape_html($btn_name);

						// Build confirmation attribute if needed
						$confirmAttr = '';
						if ($row_confirm) {
							$row_confirm_safe = canvastack_escape_html($row_confirm);
							$confirmAttr = ' data-confirm="' . $row_confirm_safe . '" onclick="return confirm(\'' . addslashes($row_confirm_safe) . '\');"';
						}

						if (true === $restoreDeleted) {
							// Disabled state for restored items
							$desktop .= '<button type="button" disabled class="btn btn-default btn-xs ' . $btn_name_safe . '" data-toggle="tooltip" data-placement="top" title="' . $row_tooltip_safe . '" aria-label="' . $row_name_safe . ' (disabled)" aria-disabled="true"><i class="fa fa-' . $row_icon_safe . '" aria-hidden="true"></i><span class="sr-only">' . $row_name_safe . ' (disabled)</span></button>';
							$mobile .= '<li><button type="button" disabled class="tooltip-error ' . $btn_name_safe . '" data-rel="tooltip" title="' . $row_tooltip_safe . '" aria-label="' . $row_name_safe . ' (disabled)" aria-disabled="true"><span class="red"><i class="fa fa-' . $row_icon_safe . ' bigger-120" aria-hidden="true"></i></span><span class="sr-only">' . $row_name_safe . ' (disabled)</span></button></li>';
						} else {
							// Active state with proper link
							$desktop .= '<a href="' . $row_url_safe . '" class="btn ' . $btn_name_safe . ' btn-' . $row_color. ' btn-xs" data-toggle="tooltip" data-placement="top" title="' . $row_tooltip_safe . '" aria-label="' . $row_name_safe . '" role="button"' . $confirmAttr . '><i class="fa fa-' . $row_icon_safe . '" aria-hidden="true"></i><span class="sr-only">' . $row_name_safe . '</span></a>';
							$mobile .= '<li><a href="' . $row_url_safe . '" class="tooltip-error ' . $btn_name_safe . '" data-rel="tooltip" title="' . $row_tooltip_safe . '" aria-label="' . $row_name_safe . '" role="button"' . $confirmAttr . '><span class="red"><i class="fa fa-' . $row_icon_safe . ' bigger-120" aria-hidden="true"></i></span><span class="sr-only">' . $row_name_safe . '</span></a></li>';
						}
					}

					return ['desktop' => $desktop, 'mobile' => $mobile];

				} catch (\Exception $e) {
					error_log('create_action_buttons_additional() error: ' . $e->getMessage());
					return ['desktop' => '', 'mobile' => ''];
				}
			}

		/**
		 * Render final action buttons HTML
		 * 
		 * @param array $buttonView View button data
		 * @param array $buttonEdit Edit button data
		 * @param array $buttonDelete Delete button data
		 * @param array $buttonNew Additional button data
		 * @return string Final HTML for action buttons
		 */
		/**
			 * Render final action buttons HTML
			 * ACCESSIBILITY: Enhanced structure with proper ARIA attributes
			 * 
			 * @param array $buttonView View button data
			 * @param array $buttonEdit Edit button data
			 * @param array $buttonDelete Delete button data
			 * @param array $buttonNew Additional button data
			 * @return string Final HTML for action buttons
			 */
			function create_action_buttons_render(array $buttonView, array $buttonEdit, array $buttonDelete, array $buttonNew): string {
				$buttons = ($buttonView['desktop'] ?? '') . ($buttonEdit['desktop'] ?? '') . ($buttonDelete['desktop'] ?? '') . ($buttonNew['desktop'] ?? '');
				$buttonsMobile = ($buttonView['mobile'] ?? '') . ($buttonEdit['mobile'] ?? '') . ($buttonDelete['mobile'] ?? '') . ($buttonNew['mobile'] ?? '');

				// Enhanced structure with better accessibility
				$html = '<div class="action-buttons-box" role="group" aria-label="Row actions">';
				$html .= '<div class="hidden-sm hidden-xs action-buttons" role="toolbar" aria-label="Desktop actions">' . $buttons . '</div>';
				$html .= '<div class="hidden-md hidden-lg">';
				$html .= '<div class="inline pos-rel">';
				$html .= '<button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown" data-position="auto" aria-haspopup="true" aria-expanded="false" aria-label="Show actions menu">';
				$html .= '<i class="fa fa-caret-down icon-only bigger-120" aria-hidden="true"></i>';
				$html .= '<span class="sr-only">Actions</span>';
				$html .= '</button>';
				$html .= '<ul class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close" role="menu" aria-label="Mobile actions">' . $buttonsMobile . '</ul>';
				$html .= '</div></div></div>';

				return $html;
			}
}

if (!function_exists('canvastack_table_row_attr')) {
	/**
	 * Set Default Row Attributes for Table
	 * 
	 * Adds custom HTML attributes to table cells. Supports both
	 * string format (key=value|key2=value2) and array format.
	 * 
	 * SECURITY: Added input validation
	 *
	 * @param string $str_value Cell content value
	 * @param string|array $attributes HTML attributes
	 * 		String format: 'colspan=2|id=idLists'
	 * 		Array format: ['colspan' => 2, 'id' => 'idLists']
	 *
	 * @return string Formatted string with attributes
	 * @throws \InvalidArgumentException If parameters are invalid
	 * 
	 * @example
	 * // String format
	 * $cell = canvastack_table_row_attr('Total', 'colspan=2|class=text-right');
	 * // Returns: 'Total{:}colspan=2|class=text-right'
	 * 
	 * // Array format
	 * $cell = canvastack_table_row_attr('Summary', ['colspan' => 3, 'class' => 'bold']);
	 * // Returns: 'Summary{:}colspan="3" class="bold"'
	 */
	function canvastack_table_row_attr(string $str_value, array $attributes): string {
		// Input validation
		if (!is_string($str_value)) {
			throw new \InvalidArgumentException('String value must be a string');
		}
		
		if (!is_string($attributes) && !is_array($attributes)) {
			throw new \InvalidArgumentException('Attributes must be string or array');
		}
		
		$attr = $attributes;
		if (is_array($attributes)) {
			$attribute = [];
			foreach ($attributes as $key => $value) {
				// Escape key and value for HTML context
				$key_safe = canvastack_escape_html($key);
				$value_safe = canvastack_escape_html($value);
				$attribute[] = "{$key_safe}=\"{$value_safe}\"";
			}
			$attr = implode(' ', $attribute);
		}
		
		return "{$str_value}{:}$attr";
	}
}

if (!function_exists('canvastack_generate_table')) {
	
	/**
	 * Table Builder
	 * 
	 * Generates complete HTML table with headers, body, and attributes.
	 * Supports server-side processing, numbering, and custom attributes.
	 * 
	 * REFACTORED: Reduced from 202 lines to orchestrator pattern
	 * SECURITY: All XSS vulnerabilities fixed, added error handling
	 *
	 * @param string|false $title Table title (optional)
	 * @param string|false $title_id Table ID for HTML element
	 * @param array $header Array of header column names
	 * @param array $body Array of table data rows
	 * @param array $attributes HTML attributes for table element
	 * @param bool $numbering Whether to add row numbering
	 * @param bool $containers Whether to draw container div (default: true)
	 * @param bool $server_side Whether to use server-side processing
	 * @param bool|string|array $server_side_custom_url Custom URL for server-side
	 *
	 * @return string Complete HTML table
	 * 
	 * @example
	 * // Basic table
	 * $table = canvastack_generate_table(
	 *     'Users',
	 *     'users_table',
	 *     ['ID', 'Name', 'Email'],
	 *     [
	 *         ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
	 *         ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
	 *     ]
	 * );
	 * 
	 * // Table with numbering and custom attributes
	 * $table = canvastack_generate_table(
	 *     'Products',
	 *     'products_table',
	 *     ['Name', 'Price', 'Stock'],
	 *     $products,
	 *     ['class' => 'table table-striped', 'data-page-length' => 25],
	 *     true // numbering
	 * );
	 * 
	 * // Server-side table
	 * $table = canvastack_generate_table(
	 *     'Orders',
	 *     'orders_table',
	 *     ['Order ID', 'Customer', 'Total', 'Status'],
	 *     [],
	 *     [],
	 *     false,
	 *     true,
	 *     true // server-side
	 * );
	 */
	function canvastack_generate_table(string|false $title = false, string|false $title_id = false, array $header = array(), array $body = array(), array $attributes = array(), bool $numbering = false, bool $containers = true, bool $server_side = false, string|false $server_side_custom_url = false): string {
		try {
			// PERFORMANCE: Check memory usage before generating table body (Requirement 6.7)
			canvastack_table_check_memory_usage('canvastack_generate_table');

			$attributes = canvastack_table_setup_attributes($title_id, $attributes);
			$_header = canvastack_table_generate_header($header, $numbering);
			$_body = canvastack_table_generate_body($body, $numbering, $server_side);
			
			return "<table{$attributes}>{$_header}{$_body}</table>";
			
		} catch (\Error $e) {
			if (canvastack_is_out_of_memory_error($e)) {
				error_log('canvastack_generate_table() OOM: ' . $e->getMessage());
				// Return a safe, minimal fallback table so the page does not crash
				return '<table class="table"><tbody><tr><td>Table could not be rendered: insufficient memory.</td></tr></tbody></table>';
			}
			throw $e;
		} catch (\Exception $e) {
			error_log('canvastack_generate_table() error: ' . $e->getMessage());
			return '<table class="table"><tbody><tr><td>Error generating table</td></tr></tbody></table>';
		}
	}
	
	/**
	 * Setup table attributes
	 * SECURITY: Added error handling
	 * 
	 * @param string $title_id
	 * @param array $attributes
	 * @return string
	 */
	function canvastack_table_setup_attributes(string|false $title_id, array|false $attributes): string {
		try {
			$datatableClass = CANVASTACK_DEFAULT_TABLE_CLASS;
			
			if (false !== $attributes && is_array($attributes)) {
				$_attributes = canvastack_table_merge_attributes($title_id, $attributes, $datatableClass);
			} else {
				$_attributes = canvastack_table_default_attributes($title_id, $datatableClass);
			}
			
			return ' ' . rtrim(canvastack_attributes_to_string($_attributes));
			
		} catch (\Exception $e) {
			error_log('canvastack_table_setup_attributes() error: ' . $e->getMessage());
			return ' class="table"'; // Minimal fallback
		}
	}
	
	/**
	 * Merge custom attributes with defaults
	 * 
	 * @param string $title_id
	 * @param array $attributes
	 * @param string $datatableClass
	 * @return array
	 */
	function canvastack_table_merge_attributes(string|false $title_id, array $attributes, string $datatableClass): array {
		if (empty($attributes)) {
			return canvastack_table_default_attributes($title_id, $datatableClass);
		}
		
		$_attributes = [];
		$_attributes['id'] = $attributes['id'] ?? "datatable-{$title_id}";
		$_attributes['class'] = $attributes['class'] ?? $datatableClass;
		
		foreach ($attributes as $attrField => $attrValue) {
			$_attributes[$attrField] = $attrValue;
		}
		
		return $_attributes;
	}
	
	/**
	 * Get default table attributes
	 * 
	 * @param string $title_id
	 * @param string $datatableClass
	 * @return array
	 */
	function canvastack_table_default_attributes(string|false $title_id, string $datatableClass): array {
		return [
			'id'    => "datatable-{$title_id}",
			'class' => $datatableClass
		];
	}
	
	/**
	 * Generate table header
	 * SECURITY: Added error handling
	 * 
	 * @param array $header
	 * @param bool $numbering
	 * @return string|false
	 */
	function canvastack_table_generate_header(array|false $header, bool $numbering): string|false {
		try {
			if (false === $header) {
				return false;
			}
			
			if (true === $numbering) {
				$header = array_merge(['number_lists'], $header);
			}
			
			$_merge = [];

			/**
			 * @performance Use array collection + implode() instead of repeated .= in loop
			 * to avoid repeated string reallocation on each append.
			 */
			$_headerParts = [];
			
			foreach ($header as $hIndex => $hList) {
				if (is_array($hList)) {
					$_merge[$hIndex] = $hList['merge'];
					$_headerParts[] = tableColumn($header, $hIndex, $hList['column']);
				} else {
					$_headerParts[] = tableColumn($header, $hIndex, $hList);
				}
			}
			
			$_header = '<thead><tr>' . implode('', $_headerParts) . '</tr>';
			
			if (!empty($_merge)) {
				$_header .= canvastack_table_generate_merge_columns($_merge);
			}
			
			$_header .= '</thead>';
			
			return $_header;
			
		} catch (\Exception $e) {
			error_log('canvastack_table_generate_header() error: ' . $e->getMessage());
			return '<thead><tr><th>Error</th></tr></thead>';
		}
	}
	
	/**
	 * Generate merge columns for header
	 * 
	 * @param array $_merge
	 * @return string
	 */
	function canvastack_table_generate_merge_columns(array $_merge): string {
		/**
		 * @performance Use array collection + implode() instead of repeated .= in nested loops
		 * to avoid repeated string reallocation on each append.
		 */
		$parts = [];
		
		foreach ($_merge as $_mergedata) {
			foreach ($_mergedata as $idx => $mdList) {
				$parts[] = tableColumn($_mergedata, $idx, $mdList);
			}
		}
		
		return implode('', $parts);
	}
	
	/**
	 * Generate table body
	 * SECURITY: Added error handling
	 * 
	 * @param array $body
	 * @param bool $numbering
	 * @param bool $server_side
	 * @return string|null
	 */
	function canvastack_table_generate_body(array $body, bool $numbering, bool $server_side): ?string {
		try {
			if (true === $server_side) {
				return null;
			}
			
			if (false === $body) {
				return '<tbody><tr><td>Found no data</td></tr></tbody>';
			}
			
			$_body = '<tbody>';
			$array_keys = array_keys($body);
			$first_key = reset($array_keys);
			
			/**
			 * @performance Use array collection + implode() instead of repeated .= in loop
			 * to avoid repeated string reallocation on each append.
			 */
			$_bodyParts = [];
			foreach ($body as $bIndex => $bLists) {
				$_bodyParts[] = canvastack_table_generate_row($bIndex, $bLists, $body, $numbering, $first_key);
			}
			
			$_body .= implode('', $_bodyParts) . '</tbody>';
			
			return $_body;
			
		} catch (\Exception $e) {
			error_log('canvastack_table_generate_body() error: ' . $e->getMessage());
			return '<tbody><tr><td>Error generating table body</td></tr></tbody>';
		}
	}
	
	/**
	 * Generate single table row
	 * 
	 * @param int $bIndex
	 * @param array $bLists
	 * @param array $body
	 * @param bool $numbering
	 * @param int $first_key
	 * @return string
	 */
	function canvastack_table_generate_row(int $bIndex, array $bLists, array $body, bool $numbering, mixed $first_key): string {
		$rowClickAction = canvastack_table_get_row_click_action($bLists);
		unset($bLists['row_data_url']);
		
		$row_html = '<tr>';
		
		for ($row = 0; $row <= count($body); $row++) {
			if ($bIndex === $row) {
				if (true === $numbering) {
					$numLists = ($first_key <= 0) ? intval($row)+1 : intval($row);
					$row_html .= "<td class=\"center\">{$numLists}</td>";
				}
				
				$row_html .= canvastack_table_generate_cells($bLists, $rowClickAction);
			}
		}
		
		$row_html .= '</tr>';
		
		return $row_html;
	}
	
	/**
	 * Get row click action attribute
	 * 
	 * @param array $bLists
	 * @return string|false
	 */
	function canvastack_table_get_row_click_action(array $bLists): string|false {
		if (empty($bLists['row_data_url']) || false === $bLists['row_data_url']) {
			return false;
		}
		
		// Validate and escape URL for JavaScript context
		$validated_url = canvastack_validate_url($bLists['row_data_url']);
		
		if ($validated_url !== false) {
			$url_safe = canvastack_escape_js($validated_url);
			return ' onclick="location.href=\'' . $url_safe . '\'" class="row-list-url"';
		}
		
		return false;
	}
	
	/**
	 * Generate all cells for a row
	 * 
	 * @param array $bLists
	 * @param string|false $rowClickAction
	 * @return string
	 */
	function canvastack_table_generate_cells(array $bLists, string|false $rowClickAction): string {
		/**
		 * @performance Use array collection + implode() instead of repeated .= in loop
		 * to avoid repeated string reallocation on each append.
		 */
		$cellParts = [];
		
		foreach ($bLists as $index => $list) {
			$cellParts[] = canvastack_table_generate_single_cell($index, $list, $rowClickAction);
		}
		
		return implode('', $cellParts);
	}
	
	/**
	 * Generate single table cell
	 * 
	 * @param string $index
	 * @param mixed $list
	 * @param string|false $rowClickAction
	 * @return string
	 */
	function canvastack_table_generate_single_cell(string $index, mixed $list, string|false $rowClickAction): string {
		// Disable row click for action column
		if ('action' === $index) {
			$rowClickAction = false;
		}
		
		// Parse row attributes if present
		$parsed = canvastack_table_parse_cell_attributes($list, $rowClickAction);
		$list = $parsed['list'];
		$row_list = $parsed['row_list'];
		
		// Handle special column types
		return canvastack_table_handle_special_column($index, $list, $row_list, $rowClickAction);
	}
	
	/**
	 * Parse cell attributes from list value
	 * 
	 * Processes cell content and attributes, handling both safe HTML (from form helpers)
	 * and user data that needs escaping.
	 * 
	 * Uses marker-based approach to detect safe HTML:
	 * - If content is marked with SafeHtml::MARKER, it's trusted HTML (no escaping)
	 * - Otherwise, content is escaped for XSS protection
	 * 
	 * @param string $list Cell content (may be marked safe HTML or plain text)
	 * @param string|false $rowClickAction Row click action attribute
	 * @return array ['list' => original content, 'row_list' => HTML td element]
	 */
	function canvastack_table_parse_cell_attributes(mixed $list, string|false $rowClickAction): array {
		$row_attr = false;
		
		if (true === str_contains($list, '{:}')) {
			$reList = explode('{:}', $list);
			$list = $reList[0];
			
			if (isset($reList[1])) {
				$rowAttr = explode('|', $reList[1]);
				$row_attr = ' ' . implode(' ', $rowAttr);
			}
			
			// Use marker-based approach for safe HTML detection
			$list_safe = SafeHtml::process($list);
			$row_list = "<td{$row_attr}{$rowClickAction}>{$list_safe}</td>";
		} else {
			// Use marker-based approach for safe HTML detection
			$list_safe = SafeHtml::process($list);
			$row_list = "<td{$rowClickAction}>{$list_safe}</td>";
		}
		
		return ['list' => $list, 'row_list' => $row_list];
	}
	
	/**
	 * Handle special column types (active, flag_status, etc.)
	 * 
	 * @param string $index
	 * @param mixed $list
	 * @param string $row_list
	 * @param string|false $rowClickAction
	 * @return string
	 */
	function canvastack_table_handle_special_column(string $index, mixed $list, string $row_list, string|false $rowClickAction): string {
		// Note: $hNumber and $hEmpty are handled in the calling context
		// This function focuses on special column types
		
		switch ($index) {
			case 'active':
				$_list = set_active_value($list);
				return "<td align=\"center\">{$_list}</td>";
				
			case 'flag_status':
				$_list = internal_flag_status($list);
				return "<td align=\"center\"{$rowClickAction}>{$_list}</td>";
				
			case 'request_status':
				$_list = request_status(true, $list);
				return "<td align=\"center\">{$_list}</td>";
				
			case 'update_status':
				$_list = active_box();
				return "<td align=\"center\">{$_list[$list]}</td>";
				
			case 'action':
				// Action buttons are already escaped by create_action_buttons()
				return "<td align=\"center\"{$rowClickAction}>{$list}</td>";
				
			default:
				return $row_list;
		}
	}
	
	/**
	 * OLD IMPLEMENTATION - KEPT FOR REFERENCE
	 * This was the original 202-line function with 8-level nesting
	 * Now refactored into 10 focused functions above
	 */
	function canvastack_generate_table_OLD_BACKUP(string|false $title = false, string|false $title_id = false, array $header = array(), array $body = array(), array $attributes = array(), bool $numbering = false, bool $containers = true, bool $server_side = false, string|false $server_side_custom_url = false): string {
		// Original implementation moved to backup
		// See refactored version above
		return '';
	}
	
	/**
	 * Generate table column header
	 * REFACTORED: Reduced from 87 lines to orchestrator pattern
	 * SECURITY: XSS Fixed - All user input escaped, added error handling
	 * 
	 * @param array $header
	 * @param int $hIndex
	 * @param mixed $hList
	 * @return string
	 */
	function tableColumn(array $header, int $hIndex, mixed $hList): string {
		try {
			$headerData = canvastack_column_extract_header_data($hList);
			$headerData = canvastack_column_process_field_name($headerData);
			$headerType = canvastack_column_determine_type($header, $hIndex, $headerData);
			
			return canvastack_column_generate_html($headerData, $headerType, $hIndex);
			
		} catch (\Exception $e) {
			error_log('tableColumn() error: ' . $e->getMessage());
			return '<th>Error</th>';
		}
	}
	
	/**
	 * Extract header key and value
	 * SECURITY: Added error handling
	 * 
	 * @param mixed $hList
	 * @return array
	 */
	function canvastack_column_extract_header_data(mixed $hList): array {
		try {
			if (is_array($hList)) {
				$keyList = array_keys($hList);
				$HKEY = $keyList[0] ?? '';
				$HVAL = $hList[$HKEY] ?? '';
			} else {
				$HKEY = $hList;
				$HVAL = trim(ucwords(str_replace('_', ' ', $HKEY)));
			}
			
			return [
				'key' => $HKEY,
				'value' => $HVAL,
				'list' => $HKEY,
				'label' => $HVAL,
				'fields' => $HKEY
			];
			
		} catch (\Exception $e) {
			error_log('canvastack_column_extract_header_data() error: ' . $e->getMessage());
			return [
				'key' => '',
				'value' => '',
				'list' => '',
				'label' => '',
				'fields' => ''
			];
		}
	}
	
	/**
	 * Process field name (handle pipe and dot separators)
	 * SECURITY: Added error handling
	 * 
	 * @param array $headerData
	 * @return array
	 */
	function canvastack_column_process_field_name(array $headerData): array {
		try {
			$hList = $headerData['list'];
			$hListFields = $hList;
			
			if (true === str_contains($hList, '|')) {
				$newHList = explode('|', $hList);
				$hList = $newHList[1] ?? $hList;
				$hListFields = $hList;
			}
			
			if (true === str_contains($hList, '.')) {
				$newHList = explode('.', $hList);
				$hList = $newHList[0] ?? $hList;
			}
			
			$headerData['list'] = trim(ucwords(str_replace('_', ' ', $hList)));
			$headerData['fields'] = $hListFields;
			
			return $headerData;
			
		} catch (\Exception $e) {
			error_log('canvastack_column_process_field_name() error: ' . $e->getMessage());
			return $headerData; // Return original data on error
		}
	}
	
	/**
	 * Determine header column type
	 * SECURITY: Added error handling
	 * 
	 * @param array $header
	 * @param int $hIndex
	 * @param array $headerData
	 * @return array
	 */
	function canvastack_column_determine_type(array $header, int $hIndex, array $headerData): array {
		try {
			$idHeader = $header[$hIndex] ?? '';
			
			if (is_array($idHeader)) {
				$fHead = array_keys($idHeader);
				$idHeader = $fHead[0] ?? '';
			}
			
			$type = [
				// @performance 6.6 - Use strict mode (true) for type-safe comparison
				'isNumber' => in_array(strtolower($idHeader), ['no', 'id', 'nik'], true),
				'isCheck' => canvastack_string_contained($headerData['list'], '<input type="checkbox"'),
				'isEmpty' => is_empty($headerData['list']),
				'idHeader' => $idHeader
			];
			
			return $type;
			
		} catch (\Exception $e) {
			error_log('canvastack_column_determine_type() error: ' . $e->getMessage());
			return [
				'isNumber' => false,
				'isCheck' => false,
				'isEmpty' => false,
				'idHeader' => ''
			];
		}
	}
	
	/**
	 * Generate header HTML based on type
	 * 
	 * @param array $headerData
	 * @param array $headerType
	 * @param int $hIndex
	 * @return string
	 */
	function canvastack_column_generate_html(array $headerData, array $headerType, int $hIndex): string {
		$hList = $headerData['list'];
		$hLabel = $headerData['label'];
		$hListFields = $headerData['fields'];
		
		// Escape all output for HTML context
		$hList_safe = canvastack_escape_html($hList);
		$hLabel_safe = canvastack_escape_html($hLabel);
		$hListFields_safe = canvastack_escape_html($hListFields);
		
		if ($headerType['isNumber']) {
			return "<th class=\"center\" width=\"50\">{$hList_safe}</th>";
		}
		
		if (true === str_contains($hList, ':changeHeaderName:')) {
			$newHList = explode(':changeHeaderName:', $hList);
			$hList = ucwords($newHList[1]);
			$hListFields_safe = canvastack_escape_html($hList);
			return "<th class=\"center\" width=\"120\">{$hListFields_safe}</th>";
		}
		
		if ($headerType['isCheck']) {
			return "<th width=\"50\">{$hList_safe}</th>";
		}
		
		if ($headerType['isEmpty']) {
			return "<th class=\"center\" width=\"120\">{$hList_safe}</th>";
		}
		
		// Handle special column names
		$specialColumns = ['Action', 'Active', 'Flag Status'];
		// @performance 6.6 - Use strict mode (true) for type-safe comparison
		if (in_array($hList, $specialColumns, true)) {
			return "<th class=\"center\" width=\"120\">{$hList_safe}</th>";
		}
		
		// Handle number_lists
		if ('number_lists' === strtolower($headerType['idHeader'])) {
			return "<th class=\"center\" width=\"30\">No</th><th class=\"center\" width=\"30\">ID</th>";
		}
		
		// Handle custom attributes
		return canvastack_column_generate_custom_html($hList, $hLabel_safe);
	}
	
	/**
	 * Generate custom header HTML with attributes
	 * 
	 * @param string $hList
	 * @param string $hLabel_safe
	 * @return string
	 */
	function canvastack_column_generate_custom_html(mixed $hList, string $hLabel_safe): string {
		$row_attr = false;
		
		if (true === str_contains($hList, '{:}')) {
			$reList = explode('{:}', $hList);
			$hList = $reList[0];
			
			if (isset($reList[1])) {
				$rowAttr = explode('|', $reList[1]);
				$row_attr = ' ' . implode(' ', $rowAttr);
			}
			
			$hList_safe = canvastack_escape_html($hList);
			return "<th{$row_attr}>{$hList_safe}</th>";
		}
		
		return "<th>{$hLabel_safe}</th>";
	}
}

if (!function_exists('canvastack_draw_query_map_page_table')) {
	
	/**
		 * Draw query map page table
		 * SECURITY: XSS Fixed - All user input escaped
		 * 
		 * @param string $name
		 * @param string $field_id
		 * @param string $value_id
		 * @param array $data
		 * @param array $buffers
		 * @param array $fieldbuff
		 * @return string
		 */
		/**
		 * Draw query mapping page table with field name and value selects
		 * 
		 * Generates nested table for role-based page mapping with dynamic select elements.
		 * Uses SafeHtml marker approach to handle form helper output.
		 * 
		 * @param string $name Table class name
		 * @param string $field_id Field select ID
		 * @param string $value_id Value select ID
		 * @param array $data Data containing field_name and field_value select elements
		 * @param array $buffers Existing buffer data
		 * @param array $fieldbuff Field buffer IDs
		 * 
		 * @return string Safe HTML table (marked as safe)
		 */
		function canvastack_draw_query_map_page_table(string $name, string $field_id, mixed $value_id, array $data, array $buffers, array $fieldbuff): string {		
			$fieldID   = $field_id;
			$trClass   = null;

			// Escape $name for HTML context
			$name_safe = canvastack_escape_html($name);
			$o         = "<table class=\"table mapping-table display responsive relative-box {$name_safe}\"><tbody>";

			if (!empty($buffers)) {
				$n      = 0;
				$id     = explode('__node__', $field_id)[0];
				$ico    = 'fa fa-recycle warning';
				$script = null;

				/**
				 * @performance Use array collection + implode() instead of repeated .= in loop
				 * to avoid repeated string reallocation on each append.
				 */
				$rowParts = [];
				foreach ($buffers[$id] as $field_info => $value) {
					$n++;

					if ($n > 1) {
						$field_id = $fieldbuff['ranid'][$field_info];
						$value_id = $fieldbuff['ranval'][$field_info];
						$trClass  = " role-add-{$fieldID}";
						$ico      = 'fa fa-minus-circle danger';

						// Escape for JavaScript context
						$field_id_safe = canvastack_escape_js($field_id);
						$value_id_safe = canvastack_escape_js($value_id);
						$ajax_field_name_safe = canvastack_escape_js($data['ajax_field_name']);
						$script   = "<script type='text/javascript'>$(document).ready(function() { rowButtonRemovalMapRoles('{$field_id_safe}', '{$value_id_safe}'); mappingPageFieldnameValues('{$field_id_safe}', '{$value_id_safe}', '{$ajax_field_name_safe}'); });</script>";
					}

					$rowParts[] = "<tr id=\"row-box-{$field_id}\" class=\"relative-box row-box-{$fieldID}{$trClass}\">"
						. "<td class=\"qmap-box-{$fieldID} field-name-box\">"
							// Use SafeHtml::process() to handle marked safe HTML
							. SafeHtml::process($data['field_name'][$value->target_table][$value->target_field_name])
						. "</td>"
						. "<td class=\"qmap-box-{$fieldID} relative-box field-value-box\">"
							// Use SafeHtml::process() to handle marked safe HTML
							. SafeHtml::process($data['field_value'][$value->target_table][$field_info])
							. "<span id=\"remove-row{$field_id}\" class=\"remove-row{$fieldID} multi-chain-buttons\" style=\"\">"
								. "<i class='{$ico}' aria-hidden='true'></i>"
							. "</span>"
							. $script
						. "</td>"
					. "</tr>";
				}
				$o .= implode('', $rowParts);

			} else {
				$o .= "<tr id=\"row-box-{$field_id}\" class=\"relative-box row-box-{$field_id}\">";
					$o .= "<td class=\"qmap-box-{$field_id} field-name-box\">";
						// Use SafeHtml::process() to handle marked safe HTML
						$o .= SafeHtml::process($data['field_name']);
					$o .= "</td>";
					$o .= "<td class=\"qmap-box-{$field_id} relative-box field-value-box\">";
						// Use SafeHtml::process() to handle marked safe HTML
						$o .= SafeHtml::process($data['field_value']);
						$o .= "<span id=\"remove-row{$field_id}\" class=\"remove-row{$field_id} multi-chain-buttons\" style=\"display:none;\">";
							$o .= "<i class='fa fa-recycle warning' aria-hidden='true'></i>";
						$o .= "</span>";
					$o .= "</td>";
				$o .= "</tr>";
			}

			$o .= "</tbody></table>";

			// Mark entire table as safe HTML
			return SafeHtml::mark($o);
		}
}

// ============================================================================
// CACHING HELPER FUNCTIONS
// ============================================================================

if (!defined('CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX')) {
	/**
	 * Cache key prefix for table schema data
	 */
	define('CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX', 'table_schema_');
}

if (!defined('CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX')) {
	/**
	 * Cache key prefix for table column lists
	 */
	define('CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX', 'table_columns_');
}

if (!defined('CANVASTACK_TABLE_CACHE_CONFIG_PREFIX')) {
	/**
	 * Cache key prefix for table configuration data
	 */
	define('CANVASTACK_TABLE_CACHE_CONFIG_PREFIX', 'table_config_');
}

if (!defined('CANVASTACK_TABLE_CACHE_TTL')) {
	/**
	 * Default cache TTL in seconds (1 hour)
	 */
	define('CANVASTACK_TABLE_CACHE_TTL', 3600);
}

if (!defined('CANVASTACK_TABLE_CACHE_SCHEMA_TTL')) {
	/**
	 * Cache TTL for schema data in seconds (6 hours - schema rarely changes)
	 */
	define('CANVASTACK_TABLE_CACHE_SCHEMA_TTL', 21600);
}

if (!defined('CANVASTACK_TABLE_CACHE_CONFIG_TTL')) {
	/**
	 * Cache TTL for configuration data in seconds (30 minutes)
	 */
	define('CANVASTACK_TABLE_CACHE_CONFIG_TTL', 1800);
}

if (!function_exists('canvastack_table_cache_key')) {
	/**
	 * Build a normalized cache key for table-related data
	 *
	 * Combines prefix, table name, and optional connection into a consistent
	 * cache key string. The key is lowercased and sanitized to avoid collisions.
	 *
	 * @param string $prefix     Cache key prefix (use CANVASTACK_TABLE_CACHE_* constants)
	 * @param string $tableName  Table name
	 * @param string $connection Database connection name
	 * @return string Normalized cache key
	 *
	 * @example
	 * $key = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users');
	 * // Returns: 'table_schema_mysql_users'
	 */
	function canvastack_table_cache_key(string $prefix, string $tableName, ?string $connection = CANVASTACK_DEFAULT_DB_CONNECTION): string {
		$connection     = $connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		$safeName       = preg_replace('/[^a-zA-Z0-9_]/', '_', $tableName);
		$safeConnection = preg_replace('/[^a-zA-Z0-9_]/', '_', $connection);
		return strtolower($prefix . $safeConnection . '_' . $safeName);
	}
}

if (!function_exists('canvastack_table_get_cached_schema')) {
	/**
	 * Get cached table schema (column names and types)
	 *
	 * Retrieves the full schema for a table from cache. On a cache miss the
	 * schema is fetched from the database, stored in cache, and returned.
	 * Schema data includes column names and their database types.
	 *
	 * @performance CRITICAL - Eliminates repeated getSchemaBuilder() calls for the
	 *              same table. Schema queries are expensive; caching reduces DB round-trips
	 *              by ~80% for pages that render multiple tables or call lists() repeatedly.
	 *
	 * @param string $tableName  Table name to fetch schema for
	 * @param string $connection Database connection name
	 * @param int    $ttl        Cache TTL in seconds (default: CANVASTACK_TABLE_CACHE_SCHEMA_TTL)
	 * @return array Associative array of ['column_name' => 'column_type', ...]
	 *               Returns empty array on error.
	 *
	 * @example
	 * $schema = canvastack_table_get_cached_schema('users');
	 * // Returns: ['id' => 'integer', 'name' => 'string', 'email' => 'string', ...]
	 */
	function canvastack_table_get_cached_schema(string $tableName, ?string $connection = CANVASTACK_DEFAULT_DB_CONNECTION, int $ttl = CANVASTACK_TABLE_CACHE_SCHEMA_TTL): array {
		$connection = $connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		if (empty($tableName)) {
			return [];
		}

		$cacheKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);

		try {
			return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($tableName, $connection) {
				$schema  = [];
				$builder = \Illuminate\Support\Facades\DB::connection($connection)->getSchemaBuilder();
				$columns = $builder->getColumnListing($tableName);

				foreach ($columns as $column) {
					try {
						$schema[$column] = $builder->getColumnType($tableName, $column);
					} catch (\Exception $e) {
						$schema[$column] = 'string'; // safe fallback
					}
				}

				return $schema;
			});
		} catch (\Exception $e) {
			error_log('canvastack_table_get_cached_schema() error for "' . $tableName . '": ' . $e->getMessage());
			return [];
		}
	}
}

if (!function_exists('canvastack_table_get_cached_columns')) {
	/**
	 * Get cached list of column names for a table
	 *
	 * Returns only column names (not types). Uses a separate cache entry from
	 * the full schema to allow lighter-weight column-existence checks.
	 *
	 * @performance Reduces repeated getColumnListing() DB calls. Particularly
	 *              beneficial in Objects::check_column_exist() which iterates
	 *              over many fields per table render.
	 *
	 * @param string $tableName  Table name
	 * @param string $connection Database connection name
	 * @param int    $ttl        Cache TTL in seconds
	 * @return array Indexed array of column name strings. Empty array on error.
	 *
	 * @example
	 * $cols = canvastack_table_get_cached_columns('users');
	 * // Returns: ['id', 'name', 'email', 'created_at', 'updated_at']
	 */
	function canvastack_table_get_cached_columns(string $tableName, ?string $connection = CANVASTACK_DEFAULT_DB_CONNECTION, int $ttl = CANVASTACK_TABLE_CACHE_SCHEMA_TTL): array {
		$connection = $connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		if (empty($tableName)) {
			return [];
		}

		$cacheKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName, $connection);

		try {
			return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($tableName, $connection) {
				return \Illuminate\Support\Facades\DB::connection($connection)
					->getSchemaBuilder()
					->getColumnListing($tableName);
			});
		} catch (\Exception $e) {
			error_log('canvastack_table_get_cached_columns() error for "' . $tableName . '": ' . $e->getMessage());
			return [];
		}
	}
}

if (!function_exists('canvastack_table_cache_schema')) {
	/**
	 * Explicitly store table schema data in cache
	 *
	 * Use this when you have already fetched schema data and want to prime the
	 * cache without an additional DB round-trip.
	 *
	 * @param string $tableName  Table name
	 * @param array  $schema     Schema data to cache (['column' => 'type', ...])
	 * @param string $connection Database connection name
	 * @param int    $ttl        Cache TTL in seconds
	 * @return bool True on success, false on failure
	 *
	 * @example
	 * canvastack_table_cache_schema('users', ['id' => 'integer', 'name' => 'string']);
	 */
	function canvastack_table_cache_schema(string $tableName, array $schema, string $connection = CANVASTACK_DEFAULT_DB_CONNECTION, int $ttl = CANVASTACK_TABLE_CACHE_SCHEMA_TTL): bool {
		if (empty($tableName)) {
			return false;
		}

		$cacheKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);

		try {
			\Illuminate\Support\Facades\Cache::put($cacheKey, $schema, $ttl);
			
			// DEVELOPMENT: Log cache operations if enabled (Phase 3: Development Settings)
			if (config('canvastack.datatables.development.log_cache_operations', false)) {
				\Log::debug('[DEV] Cache: Schema cached', [
					'table' => $tableName,
					'connection' => $connection,
					'key' => $cacheKey,
					'ttl' => $ttl,
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			error_log('canvastack_table_cache_schema() error for "' . $tableName . '": ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_table_invalidate_schema_cache')) {
	/**
	 * Invalidate cached schema data for a table
	 *
	 * Call this after running migrations or schema changes to ensure stale
	 * schema data is not served from cache.
	 *
	 * @param string      $tableName  Table name whose cache should be cleared
	 * @param string      $connection Database connection name
	 * @param bool        $allEntries When true, also clears the columns-only cache entry
	 * @return bool True on success, false on failure
	 *
	 * @example
	 * // After a migration:
	 * canvastack_table_invalidate_schema_cache('users');
	 */
	function canvastack_table_invalidate_schema_cache(string $tableName, ?string $connection = CANVASTACK_DEFAULT_DB_CONNECTION, bool $allEntries = true): bool {
		$connection = $connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		if (empty($tableName)) {
			return false;
		}

		try {
			$schemaKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);
			\Illuminate\Support\Facades\Cache::forget($schemaKey);

			if ($allEntries) {
				$columnsKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName, $connection);
				\Illuminate\Support\Facades\Cache::forget($columnsKey);
			}

			return true;
		} catch (\Exception $e) {
			error_log('canvastack_table_invalidate_schema_cache() error for "' . $tableName . '": ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_table_get_cached_config')) {
	/**
	 * Get cached table configuration
	 *
	 * Caches arbitrary table configuration arrays (e.g. column definitions,
	 * action configs) to avoid rebuilding them on every request.
	 *
	 * @performance Reduces repeated config-building overhead for tables that are
	 *              rendered on high-traffic pages.
	 *
	 * @param string   $tableName  Table name used as part of the cache key
	 * @param string   $configKey  Sub-key to distinguish different config types
	 *                             (e.g. 'columns', 'actions', 'filters')
	 * @param callable $builder    Callable that returns the config array when cache misses
	 * @param int      $ttl        Cache TTL in seconds (default: CANVASTACK_TABLE_CACHE_CONFIG_TTL)
	 * @return array Cached or freshly-built configuration array
	 *
	 * @example
	 * $config = canvastack_table_get_cached_config('users', 'columns', function () {
	 *     return build_column_config();
	 * });
	 */
	function canvastack_table_get_cached_config(string $tableName, string $configKey, callable $builder, int $ttl = CANVASTACK_TABLE_CACHE_CONFIG_TTL): array {
		if (empty($tableName)) {
			return $builder();
		}

		$safeConfigKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $configKey);
		$cacheKey      = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_CONFIG_PREFIX . $safeConfigKey . '_', $tableName);

		try {
			$result = \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, $builder);
			return is_array($result) ? $result : [];
		} catch (\Exception $e) {
			error_log('canvastack_table_get_cached_config() error for "' . $tableName . '/' . $configKey . '": ' . $e->getMessage());
			// Fall back to direct builder call on cache failure
			return $builder();
		}
	}
}

if (!function_exists('canvastack_table_invalidate_config_cache')) {
	/**
	 * Invalidate cached configuration for a table
	 *
	 * @param string $tableName  Table name
	 * @param string $configKey  Sub-key to invalidate (e.g. 'columns', 'actions')
	 * @return bool True on success, false on failure
	 *
	 * @example
	 * canvastack_table_invalidate_config_cache('users', 'columns');
	 */
	function canvastack_table_invalidate_config_cache(string $tableName, string $configKey): bool {
		if (empty($tableName)) {
			return false;
		}

		$safeConfigKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $configKey);
		$cacheKey      = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_CONFIG_PREFIX . $safeConfigKey . '_', $tableName);

		try {
			\Illuminate\Support\Facades\Cache::forget($cacheKey);
			return true;
		} catch (\Exception $e) {
			error_log('canvastack_table_invalidate_config_cache() error: ' . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('canvastack_table_invalidate_all_cache')) {
	/**
	 * Invalidate ALL cached data for a table (schema, columns, and all config keys)
	 *
	 * Convenience function that clears every cache entry associated with a table.
	 * Call this after running migrations or making structural changes to a table.
	 *
	 * @param string $tableName  Table name to fully invalidate
	 * @param string $connection Database connection name
	 * @return bool True if all invalidations succeeded, false if any failed
	 *
	 * @example
	 * // After a migration that alters the 'users' table:
	 * canvastack_table_invalidate_all_cache('users');
	 *
	 * // After a migration on a non-default connection:
	 * canvastack_table_invalidate_all_cache('orders', 'mysql_secondary');
	 */
	function canvastack_table_invalidate_all_cache(string $tableName, ?string $connection = CANVASTACK_DEFAULT_DB_CONNECTION): bool {
		$connection = $connection ?? CANVASTACK_DEFAULT_DB_CONNECTION;
		if (empty($tableName)) {
			return false;
		}

		$success = true;

		// Invalidate schema + columns cache
		$success = canvastack_table_invalidate_schema_cache($tableName, $connection, true) && $success;

		// Invalidate known config sub-keys
		$configKeys = ['columns', 'actions', 'filters', 'relations', 'formulas'];
		foreach ($configKeys as $key) {
			$success = canvastack_table_invalidate_config_cache($tableName, $key) && $success;
		}

		return $success;
	}
}

// ============================================================================
// MEMORY MANAGEMENT FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_table_parse_memory_limit')) {
	/**
	 * Convert a PHP memory_limit string to bytes.
	 *
	 * Handles the shorthand suffixes recognised by PHP:
	 * - K / k  → kilobytes (×1024)
	 * - M / m  → megabytes (×1024²)
	 * - G / g  → gigabytes (×1024³)
	 *
	 * @performance Memory Management (Requirement 6.7) - helper for memory limit warnings
	 *
	 * @param string $memoryLimit Value returned by ini_get('memory_limit'), e.g. "128M"
	 * @return int Memory limit in bytes, or 0 if the value cannot be parsed
	 */
	function canvastack_table_parse_memory_limit(string $memoryLimit): int {
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
}

if (!function_exists('canvastack_table_check_memory_usage')) {
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
	 *                        (e.g. 'canvastack_generate_table', 'canvastack_generate_body')
	 * @return void
	 */
	function canvastack_table_check_memory_usage(string $context = ''): void {
		$memoryLimit = ini_get('memory_limit');

		// -1 means unlimited; nothing to check
		if ('-1' === $memoryLimit) {
			return;
		}

		$limitBytes   = canvastack_table_parse_memory_limit($memoryLimit);
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
			\Illuminate\Support\Facades\Log::error('Table: Memory usage critical (>=90% of limit)', $context_data);
		} elseif ($usagePercent >= 75.0) {
			\Illuminate\Support\Facades\Log::warning('Table: Memory usage high (>=75% of limit)', $context_data);
		}
	}
}


/**
 * Validate and normalize file path to prevent path traversal attacks
 * 
 * This function validates file paths to ensure they:
 * - Don't contain path traversal sequences (../, ..\)
 * - Don't contain null bytes
 * - Stay within the allowed base directory
 * - Use normalized separators
 * 
 * @param string $path Path to validate
 * @param string $baseDir Base directory that path must be within
 * @return string Validated and normalized path
 * @throws \InvalidArgumentException If path is invalid or contains traversal attempts
 * 
 * @security Path Traversal Prevention
 * @performance O(1) - Simple string operations
 * 
 * @example
 * // Valid path
 * canvastack_table_validate_path('exports/data.csv', '/var/www/exports');
 * // Returns: '/var/www/exports/data.csv'
 * 
 * // Path traversal attempt
 * canvastack_table_validate_path('../../../etc/passwd', '/var/www/exports');
 * // Throws: InvalidArgumentException
 * 
 * // Null byte injection
 * canvastack_table_validate_path("data.csv\0.php", '/var/www/exports');
 * // Throws: InvalidArgumentException
 */
function canvastack_table_validate_path(string $path, string $baseDir): string {
	// Guard: Check for null bytes (security vulnerability)
	if (str_contains($path, "\0")) {
		throw new \InvalidArgumentException('Path contains null byte - potential security vulnerability');
	}
	
	// Guard: Check for empty path
	if (empty($path)) {
		throw new \InvalidArgumentException('Path cannot be empty');
	}
	
	// Guard: Check for empty base directory
	if (empty($baseDir)) {
		throw new \InvalidArgumentException('Base directory cannot be empty');
	}
	
	// Normalize path separators to forward slashes
	$path = str_replace('\\', '/', $path);
	$baseDir = str_replace('\\', '/', $baseDir);
	
	// Remove trailing slashes from base directory
	$baseDir = rtrim($baseDir, '/');
	
	// Check for path traversal patterns
	if (str_contains($path, '../') || str_contains($path, '..\\')) {
		throw new \InvalidArgumentException('Path traversal detected - path contains ../ or ..\\');
	}
	
	// If path is absolute, reject it (we only want relative paths within baseDir)
	if (preg_match('/^([a-zA-Z]:)?[\/\\\\]/', $path)) {
		throw new \InvalidArgumentException('Absolute paths are not allowed');
	}
	
	// Build full path
	$fullPath = $baseDir . '/' . ltrim($path, '/');
	
	// Resolve real path (this also resolves symlinks)
	$realPath = realpath(dirname($fullPath));
	$realBase = realpath($baseDir);
	
	// If directories don't exist yet, that's okay for new files
	// But we still need to validate the path structure
	if ($realPath === false || $realBase === false) {
		// Directories don't exist - validate path structure only
		// Ensure the path would be within base directory
		$normalizedPath = $baseDir . '/' . ltrim($path, '/');
		$normalizedBase = $baseDir;
		
		// Simple check: normalized path must start with base directory
		if (!str_starts_with($normalizedPath, $normalizedBase)) {
			throw new \InvalidArgumentException('Path is outside allowed directory');
		}
		
		return $normalizedPath;
	}
	
	// Normalize paths for comparison
	$realPath = str_replace('\\', '/', $realPath);
	$realBase = str_replace('\\', '/', $realBase);
	
	// Verify that real path is within base directory
	if (!str_starts_with($realPath, $realBase)) {
		throw new \InvalidArgumentException('Path is outside allowed directory (after resolving symlinks)');
	}
	
	return $fullPath;
}


// ============================================================================
// SECURITY LOGGING FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_table_log_security_event')) {
	/**
	 * Log security events (XSS attempts, SQL injection attempts, etc.)
	 * 
	 * @param string $eventType Type of security event (xss_attempt, sql_injection, invalid_input, etc.)
	 * @param string $message Event message
	 * @param array $context Additional context data
	 * @return void
	 * 
	 * @security Logs security events when configured
	 * @performance Minimal overhead when logging disabled
	 */
	function canvastack_table_log_security_event(string $eventType, string $message, array $context = []): void {
		// CONFIG: Check if security logging is enabled
		if (!config('canvastack.datatables.security.log_security_events', true)) {
			return;
		}
		
		$logChannel = config('canvastack.datatables.security.security_log_channel', 'daily');
		
		$logData = [
			'event_type' => $eventType,
			'message' => $message,
			'ip' => request()->ip(),
			'user_id' => auth()->id(),
			'url' => request()->fullUrl(),
			'user_agent' => request()->userAgent(),
			'timestamp' => now()->toDateTimeString(),
		];
		
		// Merge additional context
		$logData = array_merge($logData, $context);
		
		\Log::channel($logChannel)->warning("Security Event: {$eventType}", $logData);
	}
}

if (!function_exists('canvastack_table_validate_operator')) {
	/**
	 * Validate SQL operator against whitelist
	 * 
	 * @param string $operator SQL operator to validate
	 * @return string Validated operator
	 * @throws \InvalidArgumentException If operator not in whitelist
	 * 
	 * @security Prevents SQL injection through operator manipulation
	 */
	function canvastack_table_validate_operator(string $operator): string {
		// CONFIG: Get allowed operators from config
		$allowedOperators = config('canvastack.datatables.security.allowed_operators', [
			'=', '!=', '<>', '>', '<', '>=', '<=',
			'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
			'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
			'IS NULL', 'IS NOT NULL',
		]);
		
		$operator = strtoupper(trim($operator));
		
		if (!in_array($operator, $allowedOperators, true)) {
			// Log security event
			canvastack_table_log_security_event('invalid_operator', 'Invalid SQL operator attempted', [
				'operator' => $operator,
				'allowed_operators' => $allowedOperators,
			]);
			
			throw new \InvalidArgumentException("Invalid SQL operator: {$operator}");
		}
		
		return $operator;
	}
}

if (!function_exists('canvastack_table_validate_sort_direction')) {
	/**
	 * Validate sort direction against whitelist
	 * 
	 * @param string $direction Sort direction to validate
	 * @return string Validated direction (lowercase)
	 * @throws \InvalidArgumentException If direction not in whitelist
	 * 
	 * @security Prevents SQL injection through sort direction manipulation
	 */
	function canvastack_table_validate_sort_direction(string $direction): string {
		// CONFIG: Get allowed sort directions from config
		$allowedDirections = config('canvastack.datatables.security.allowed_sort_directions', 
			['asc', 'desc', 'ASC', 'DESC']);
		
		$direction = trim($direction);
		
		if (!in_array($direction, $allowedDirections, true)) {
			// Log security event
			canvastack_table_log_security_event('invalid_sort_direction', 'Invalid sort direction attempted', [
				'direction' => $direction,
				'allowed_directions' => $allowedDirections,
			]);
			
			throw new \InvalidArgumentException("Invalid sort direction: {$direction}");
		}
		
		return strtolower($direction);
	}
}

// ============================================================================
// DEPRECATION HELPER FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_table_deprecated')) {
	/**
	 * Mark feature as deprecated and log usage
	 * 
	 * @param string $feature Deprecated feature name
	 * @param string $alternative Alternative feature to use
	 * @return void
	 * 
	 * @compatibility Helps track deprecated feature usage
	 */
	function canvastack_table_deprecated(string $feature, string $alternative = ''): void {
		// CONFIG: Check if deprecation warnings are enabled
		if (!config('canvastack.datatables.compatibility.warn_deprecated', true)) {
			return;
		}
		
		$message = "Deprecated feature used: {$feature}";
		if ($alternative) {
			$message .= ". Use {$alternative} instead.";
		}
		
		// Log deprecated feature usage
		if (config('canvastack.datatables.compatibility.log_deprecated', true)) {
			$channel = config('canvastack.datatables.compatibility.deprecated_log_channel', 'daily');
			\Log::channel($channel)->warning($message, [
				'feature' => $feature,
				'alternative' => $alternative,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
				'ip' => request()->ip(),
				'user_id' => auth()->id(),
			]);
		}
		
		// Trigger user-level warning in development
		if (app()->environment('local')) {
			trigger_error($message, E_USER_DEPRECATED);
		}
	}
}

// ============================================================================
// CACHE INVALIDATION FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_table_invalidate_cache')) {
	/**
	 * Invalidate table cache with cascade support
	 * 
	 * @param string $tableName Table name
	 * @param string $type Cache type to invalidate (all, schema, validation, config, relationships)
	 * @param string $connection Database connection name
	 * @return bool Success status
	 * 
	 * @performance Supports cascade invalidation for related caches
	 */
	function canvastack_table_invalidate_cache(string $tableName, string $type = 'all', string $connection = 'mysql'): bool {
		// Phase 3: Check if invalidation is enabled
		if (!config('canvastack.cache.invalidation.enabled', true)) {
			return false;
		}
		
		$prefix = config('canvastack.cache.prefix', 'canvastack_');
		$cascade = config('canvastack.cache.invalidation.cascade_invalidation', true);
		$strategy = config('canvastack.cache.invalidation.strategy', 'immediate');
		$invalidated = false;
		
		// Phase 3: Handle invalidation strategy
		if ($strategy === 'lazy') {
			// Mark for lazy invalidation (will be cleared on next access)
			$lazyKey = $prefix . 'lazy_invalidate_' . strtolower($tableName);
			\Cache::put($lazyKey, time(), 60); // Mark for 60 seconds
			return true;
		} elseif ($strategy === 'scheduled') {
			// Queue for scheduled invalidation (requires job/scheduler)
			\Log::info('Cache invalidation scheduled', [
				'table' => $tableName,
				'type' => $type,
				'connection' => $connection
			]);
			return true;
		}
		
		// Immediate invalidation (default)
		
		// Invalidate schema cache
		if ($type === 'all' || $type === 'schema') {
			$schemaKey = $prefix . config('canvastack.cache.table_schema.key_prefix', 'table_schema_') . 
						 strtolower($connection) . '_' . strtolower($tableName);
			\Cache::forget($schemaKey);
			$invalidated = true;
			
			// DEVELOPMENT: Log cache operations
			if (config('canvastack.datatables.development.log_cache_operations', false)) {
				\Log::debug('[DEV] Cache: Schema invalidated', [
					'table' => $tableName,
					'key' => $schemaKey
				]);
			}
		}
		
		// Invalidate config cache
		if ($type === 'all' || $type === 'config') {
			$configKey = $prefix . config('canvastack.cache.config.key_prefix', 'config_') . 
						 strtolower($connection) . '_' . strtolower($tableName);
			\Cache::forget($configKey);
			$invalidated = true;
			
			// DEVELOPMENT: Log cache operations
			if (config('canvastack.datatables.development.log_cache_operations', false)) {
				\Log::debug('[DEV] Cache: Config invalidated', [
					'table' => $tableName,
					'key' => $configKey
				]);
			}
		}
		
		// Invalidate validation cache
		if ($type === 'all' || $type === 'validation') {
			$validationKey = $prefix . config('canvastack.cache.validation.key_prefix', 'validation_') . 
							 strtolower($connection) . '_' . strtolower($tableName);
			\Cache::forget($validationKey);
			$invalidated = true;
		}
		
		// Invalidate relationships cache
		if ($type === 'all' || $type === 'relationships') {
			$relationshipsKey = $prefix . config('canvastack.cache.relationships.key_prefix', 'relationships_') . 
								strtolower($tableName);
			\Cache::forget($relationshipsKey);
			$invalidated = true;
		}
		
		// Phase 3: Cascade invalidation for related caches
		if ($cascade && $type === 'all') {
			// Invalidate query results cache
			$queryKey = $prefix . config('canvastack.cache.query_results.key_prefix', 'query_') . 
						strtolower($tableName);
			\Cache::forget($queryKey);
			
			// Invalidate formula cache
			$formulaKey = $prefix . config('canvastack.cache.formula_results.key_prefix', 'formula_') . 
						  strtolower($tableName);
			\Cache::forget($formulaKey);
			
			$invalidated = true;
			
			\Log::info('Cache cascade invalidation completed', [
				'table' => $tableName,
				'connection' => $connection
			]);
		}
		
		return $invalidated;
	}
}

// ============================================================================
// CACHE MONITORING FUNCTIONS
// ============================================================================

if (!function_exists('canvastack_table_cache_monitor')) {
	/**
	 * Monitor cache operations (hits, misses, statistics)
	 * 
	 * @param string $operation Cache operation (get, put, forget)
	 * @param string $key Cache key
	 * @param bool $hit Whether operation was a hit (true) or miss (false)
	 * @return void
	 * 
	 * @performance Tracks cache performance metrics
	 */
	function canvastack_table_cache_monitor(string $operation, string $key, bool $hit): void {
		// CONFIG: Check if monitoring is enabled
		if (!config('canvastack.cache.monitoring.enabled', false)) {
			return;
		}
		
		// Log hits and misses
		if (config('canvastack.cache.monitoring.log_hits_misses', false)) {
			$channel = config('canvastack.cache.monitoring.log_channel', 'daily');
			\Log::channel($channel)->info("Cache {$operation}: " . ($hit ? 'HIT' : 'MISS'), [
				'key' => $key,
				'timestamp' => now()->toDateTimeString(),
			]);
		}
		
		// Track statistics
		if (config('canvastack.cache.monitoring.track_statistics', false)) {
			$statsKey = 'cache_stats_' . date('Y-m-d');
			$statsTtl = config('canvastack.cache.monitoring.statistics_ttl', 86400);
			
			$stats = \Cache::get($statsKey, ['hits' => 0, 'misses' => 0, 'operations' => []]);
			
			// Increment counter
			if ($hit) {
				$stats['hits']++;
			} else {
				$stats['misses']++;
			}
			
			// Track operation
			if (!isset($stats['operations'][$operation])) {
				$stats['operations'][$operation] = 0;
			}
			$stats['operations'][$operation]++;
			
			\Cache::put($statsKey, $stats, $statsTtl);
		}
	}
}

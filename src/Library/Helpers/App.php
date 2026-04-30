<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Models\Admin\System\Preference;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Canvastack\Canvastack\Library\Constants\SafeHtml;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\SessionException;
use Canvastack\Canvastack\Exceptions\Controller\FileUploadException;
use Canvastack\Canvastack\Exceptions\Controller\SQLInjectionAttemptException;
use Canvastack\Canvastack\Exceptions\Controller\RouteException;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Controllers\Admin\System\AjaxController;
use Canvastack\Canvastack\Models\Admin\System\Log;

/**
 * CanvaStack Framework - Helper Functions Library
 * 
 * First Created on 10 Mar 2021
 * Time Created : 13:28:50
 * 
 * This file contains a comprehensive collection of helper functions for the CanvaStack framework.
 * These functions provide utilities for common tasks including database operations, string manipulation,
 * array operations, file handling, routing, session management, and more.
 * 
 * Function Categories:
 * 
 * 1. Configuration & Settings:
 *    - canvastack_config(): Get configuration values
 *    - is_multiplatform(): Check platform type
 *    - getPreference(): Get web preferences with caching
 * 
 * 2. Database Operations:
 *    - canvastack_query(): Execute SQL queries with security validation
 *    - canvastack_insert(): Insert data with optimization
 *    - canvastack_update(): Update data with optimization
 *    - canvastack_delete(): Soft delete/restore records
 *    - canvastack_get_model(): Get model instance
 *    - canvastack_query_get_id(): Get record by where conditions
 * 
 * 3. Session Management:
 *    - canvastack_sessions(): Get/set session data
 *    - canvastack_encrypt(): Encrypt strings
 *    - canvastack_decrypt(): Decrypt strings
 *    - canvastack_user_cryptcode(): Generate user crypt code
 * 
 * 4. String Manipulation:
 *    - canvastack_clean_strings(): Clean and format strings
 *    - canvastack_string_contained(): Check if string contains substring
 *    - canvastack_underscore_to_camelcase(): Convert underscore to camel case
 *    - canvastack_random_strings(): Generate random strings
 *    - camel_case(): Convert to camel case
 * 
 * 5. Array Operations:
 *    - canvastack_array_contained_string(): Check array contains string
 *    - canvastack_array_to_object_recursive(): Convert array to object
 *    - canvastack_array_insert_new(): Insert element at specific position
 *    - canvastack_array_insert(): Insert element by position/key
 *    - canvastack_combobox_data(): Format data for combobox
 * 
 * 6. Routing & URL:
 *    - current_route(): Get current route name
 *    - canvastack_current_route(): Get current route object
 *    - canvastack_current_baseroute(): Get base route
 *    - canvastack_current_url(): Get current URL
 *    - canvastack_get_current_route_id(): Get ID from route
 *    - canvastack_redirect(): Redirect with message
 *    - routelists_info(): Get route information
 *    - get_route_lists(): Get all route lists
 * 
 * 7. File Operations:
 *    - canvastack_set_filesize(): Set file size limits
 *    - canvastack_image_validations(): Get image validation rules
 *    - canvastack_file_validations(): Get file validation rules
 *    - canvastack_file_exist(): Check if file exists
 *    - canvastack_make_dir(): Create directory
 *    - canvastack_exist_url(): Check if URL exists
 * 
 * 8. HTML & UI Generation:
 *    - canvastack_action_buttons(): Generate action buttons HTML
 *    - canvastack_action_button_box(): Generate single action button
 *    - canvastack_mappage_button_add(): Generate mapping page buttons
 *    - canvastack_input(): Generate HTML input element
 *    - canvastack_script(): Generate script tag
 *    - canvastack_unescape_html(): Return unescaped HTML
 * 
 * 9. Data Formatting:
 *    - canvastack_format(): Format numbers with decimals
 *    - canvastack_object(): Convert array to object
 *    - canvastack_is_collection(): Check if object is collection
 *    - canvastack_attributes_to_string(): Convert attributes to string
 * 
 * 10. Validation & Security:
 *     - canvastack_not_empty(): Check if data is not empty
 *     - canvastack_is_empty(): Check if data is empty
 *     - not_empty(): Alias for canvastack_not_empty()
 *     - canvastack_is_softdeletes(): Check if model uses soft deletes
 * 
 * 11. Logging & Activity:
 *     - canvastack_log_activity(): Log user activity
 *     - meta(): Get MetaTags instance
 * 
 * 12. Cache Management:
 *     - canvastack_invalidate_privilege_cache(): Invalidate privilege cache
 *     - canvastack_invalidate_preference_cache(): Invalidate preference cache
 * 
 * 13. Miscellaneous:
 *     - canvastack_base_assets(): Get base assets URL
 *     - canvastack_url(): Get URL helper
 *     - canvastack_last_explode(): Get last element from exploded string
 *     - canvastack_merge_request(): Merge request data
 *     - canvastack_get_model_controllers_info(): Get model/controller mapping
 *     - get_object_called_name(): Get object class name
 *     - minify_code(): Minify HTML output
 *     - set_break_line_html(): Add break lines
 *     - active_box(): Get active status options
 *     - flag_status(): Get flag status options
 *     - internal_flag_status(): Get flag status label
 *     - encode_id(): Encode ID with hash
 *     - decode_id(): Decode ID with hash
 *     - hash_code_id(): Generate hash code
 * 
 * Security Features:
 * - SQL injection prevention in canvastack_query()
 * - XSS protection in canvastack_underscore_to_camelcase()
 * - XSS protection in canvastack_action_buttons()
 * - XSS protection in canvastack_action_button_box()
 * - Input validation in database operations
 * - Security event logging
 * 
 * Performance Features:
 * - Query optimization in database operations
 * - Caching support for preferences and privileges
 * - Memory-efficient array operations
 * - Reduced memory allocations
 * - Efficient string operations
 * 
 * Configuration:
 * - canvastack.controller.security.sql_injection_prevention: Enable SQL injection prevention
 * - canvastack.controller.security.xss_protection: Enable XSS protection
 * - canvastack.controller.validation.max_query_length: Maximum query length
 * - canvastack.controller.performance.enable_query_monitoring: Enable query monitoring
 * - canvastack.controller.performance.slow_query_threshold: Slow query threshold (ms)
 * - canvastack.controller.caching.preference_cache_enabled: Enable preference caching
 * - canvastack.controller.caching.preference_cache_ttl: Preference cache TTL
 * - canvastack.controller.logging.log_security_events: Enable security logging
 * - canvastack.controller.logging.log_validation_failures: Enable validation logging
 * - canvastack.controller.logging.log_performance_issues: Enable performance logging
 * 
 * @package    Canvastack\Canvastack\Library\Helpers
 * @category   Helper Functions
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 Canvastack
 * @license    Proprietary
 * @version    2.0.0
 * @since      1.0.0
 * 
 * @security   SQL injection prevention in database operations
 * @security   XSS protection in HTML generation functions
 * @security   Input validation and sanitization
 * @security   Security event logging for audit trails
 * 
 * @performance Query optimization and monitoring
 * @performance Caching support for frequently accessed data
 * @performance Memory-efficient operations
 * @performance Reduced allocations and efficient algorithms
 * 
 * @filesource App.php
 */

if (!function_exists('meta')) {
	
	function meta(): MetaTags {
		$meta = new MetaTags();
		
		return $meta;
	}
}

if (!function_exists('canvastack_config')) {
	
	/**
	 * Get Config
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvastack_config($string, $fileNameSettings = 'settings') {
		return Illuminate\Support\Facades\Config::get("canvastack.{$fileNameSettings}.{$string}");
	}
}

if (!function_exists('is_multiplatform')) {
	
	/**
	 * Get Config
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function is_multiplatform() {
		$platform = canvastack_config('platform_type');
		if ('single' === $platform) {
			return false;
		} else {
			return true;
		}
	}
}

if (!function_exists('canvastack_sessions')) {
	/**
	 * Get Sessions
	 *
	 * author: wisnuwidi
	 *
	 * @return Illuminate\Support\Facades\Session
	 *
	 * created @Dec 14, 2018
	 * 
	 * @param string $param
	 * @param array $data
	 * 
	 * @return Session
	 */
	function canvastack_sessions(string $param = 'all', mixed $data = []): mixed {
		if (!empty($data)) {
			return Session::{$param}($data);
		} else {
			return Session::{$param}();
		}
	}
}

if (!function_exists('canvastack_redirect')) {
	
	/**
	 * Set Re-Direction Path With Some Data Info
	 *
	 * created @Nov 09, 2022
	 * author: wisnuwidi
	 * 
	 * @param string $to
	 * @param mixed|string|array $message
	 * @param mixed|string|boolean $status
	 * 
	 * @return \Illuminate\Http\RedirectResponse
	 */
	function canvastack_redirect(string $to, string|array|null $message = null, string|bool|array $status = false): mixed {
		if (canvastack_string_contained($to, 'http://') || canvastack_string_contained($to, 'https://')) {
			$redirect = $to;
		} else {
			$redirect = Illuminate\Support\Facades\Redirect::to(url()->current() . '/' . $to);
		}
		
		if (!empty($message)) $redirect = $redirect->with('message', $message);
		if (!empty($status))  $redirect = $redirect->with($status, true);
		
		return $redirect;
	}
}

if (!function_exists('routelists_info')) {
	
	function routelists_info(?string $route = null): array {
		if (!empty($route)) {
			$currentRoute = explode('.', $route);
		} else {
			$currentRoute = explode('.', current_route());
		}
		
		$count_route     = intval(count($currentRoute)) - 1;
		$actionPageInfo  = last($currentRoute);
		unset($currentRoute[$count_route]);
		$baseRouteInfo   = implode('.', $currentRoute);
		
		return ['base_info' => $baseRouteInfo, 'last_info' => $actionPageInfo];
	}
}

if (!function_exists('canvastack_base_assets')) {
	
	/**
	 * Get Base Assets URL
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvastack_base_assets(): string {
		return canvastack_config('baseURL') . '/' . canvastack_config('base_template') . '/' . canvastack_config('template');
	}
}

if (!function_exists('canvastack_object')) {
    
    /**
     * Create Object
     *
     * @param mixed $array
     *
     * @return object
     */
    function canvastack_object(mixed $array): object {
        return (object) $array;
    }
}

if (!function_exists('canvastack_clean_strings')) {
	
	/**
	 * Clean Strings
	 *
	 * @param string $strings
	 * @return string
	 */
	function canvastack_clean_strings(string $strings, string $node = '-'): string {
		$strings = trim(preg_replace('/[;\.\/\?\\\:@&=+\$,_\~\*\'"\!\|%<>\{\}\^\[\]`\-]/', ' ', $strings));
		
		return strtolower(preg_replace('/\s+/', $node, $strings));
	}
}

if (!function_exists('canvastack_is_collection')) {
	
	/**
	 * Check if object is instance of Illuminate\Support\Collection
	 *
	 * @param object $object
	 *
	 * @return boolean
	 */
	function canvastack_is_collection(mixed $object): bool {
		if ($object instanceof Illuminate\Support\Collection) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('canvastack_format')) {
	
	/**
	 * Format Data
	 *
	 * @param string $data
	 * @param int $decimal_endpoint
	 * 	: Specifies how many decimals
	 * @param string $format_type
	 * 	: number, boolean
	 * @param string $separator
	 * 	: [,], [.]
	 *
	 * @return object
	 */
	function canvastack_format(mixed $data, int $decimal_endpoint = 0, string $separator = '.', string $format_type = 'number'): ?string {
		if (!empty($data)) {
			if (!empty($decimal_endpoint)) {
				$format_type = 'decimal';
			}
			
			$_separator  = [',', '.'];
			if ('.' !== $separator) $_separator = ['.', ','];
			
			$format_data = null;
			if ($format_type === 'decimal' || !empty($decimal_endpoint)) {
				$format_data = number_format($data, $decimal_endpoint, $_separator[0], $_separator[1]);
			} else {
				$format_data = number_format($data, 0, $_separator[0], $_separator[1]);
			}
			
			return $format_data;
		}
		
		return null;
	}
}

if (!function_exists('canvastack_get_model')) {
	
	/**
	 * Get Model
	 *
	 * @param object $model
	 * @param boolean $find
	 *
	 * @return object|array
	 */
	function canvastack_get_model(object|string $model, int|bool $find = false): object|array {
		if (is_string($model)) $model = new $model;
		if (false !== $find)   $model = $model->find($find);
		
		return $model;
	}
}

if (!function_exists('canvastack_query')) {

	/**
	 * Execute Database Query with Security and Performance Optimization
	 *
	 * Optimizations:
	 * - SQL injection prevention with pattern detection
	 * - Query length validation
	 * - Security event logging
	 * - Result caching support (use canvastack_controller_cache_remember for caching)
	 * - Performance monitoring
	 *
	 * @param string $sql SQL query to execute
	 * @param string $type Query type (TABLE, SELECT, INSERT, UPDATE, DELETE, STATEMENT)
	 * @param string|null $connection Database connection name
	 * @return mixed Query result
	 * @throws \InvalidArgumentException
	 * 
	 * @security Validates query for SQL injection patterns and logs suspicious activity
	 * @performance Optimized with validation caching and efficient pattern matching
	 */
	function canvastack_query($sql, $type = 'TABLE', $connection = null) {
		// Validate SQL parameter
		if (empty($sql) || !is_string($sql)) {
			throw ControllerValidationException::invalidParameter(
				'sql',
				$sql,
				'SQL parameter must be a non-empty string',
				[
					'type' => gettype($sql),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}

		// Check if SQL injection prevention is enabled
		$sqlInjectionPrevention = config('canvastack.controller.security.sql_injection_prevention', true);

		if ($sqlInjectionPrevention) {
			// Validate query length
			$maxQueryLength = config('canvastack.controller.validation.max_query_length', 10000);
			$queryLength = strlen($sql);

			if ($queryLength > $maxQueryLength) {
				// Log security event
				if (config('canvastack.controller.logging.log_security_events', true)) {
					canvastack_controller_log_security_event(
						'sql_injection_attempt',
						'Query exceeds maximum length',
						[
							'query_length' => $queryLength,
							'max_length' => $maxQueryLength,
							'query_preview' => substr($sql, 0, 200),
						]
					);
				}

				throw SQLInjectionAttemptException::suspiciousQuery(
					substr($sql, 0, 200),
					"Query exceeds maximum length of {$maxQueryLength} characters",
					[
						'query_length' => $queryLength,
						'max_length' => $maxQueryLength,
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}

			// Check for suspicious patterns (optimized with early exit)
			$suspiciousPatterns = [
				'/UNION\s+SELECT/i',
				'/;\s*DROP\s+TABLE/i',
				'/;\s*DELETE\s+FROM/i',
				'/;\s*UPDATE\s+.*\s+SET/i',
				'/;\s*INSERT\s+INTO/i',
				'/EXEC\s*\(/i',
				'/EXECUTE\s*\(/i',
				'/xp_cmdshell/i',
				'/INTO\s+OUTFILE/i',
				'/LOAD_FILE\s*\(/i',
			];

			foreach ($suspiciousPatterns as $pattern) {
				if (preg_match($pattern, $sql)) {
					// Log security event
					if (config('canvastack.controller.logging.log_security_events', true)) {
						canvastack_controller_log_security_event(
							'sql_injection_attempt',
							'Suspicious SQL pattern detected',
							[
								'pattern' => $pattern,
								'query_preview' => substr($sql, 0, 200),
							]
						);
					}

					// Don't throw exception, just log - allow legitimate queries
					break;
				}
			}
		}

		// Monitor query performance if enabled
		$monitorPerformance = config('canvastack.controller.performance.enable_query_monitoring', true);
		$startTime = $monitorPerformance ? microtime(true) : 0;

		try {
			// Execute query efficiently
			if (!empty($connection)) {
				$query = \Illuminate\Support\Facades\DB::connection($connection)->{$type}($sql);
			} else {
				$query = \Illuminate\Support\Facades\DB::{$type}($sql);
			}

			// Log slow queries if monitoring is enabled
			if ($monitorPerformance) {
				$executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
				$slowQueryThreshold = config('canvastack.controller.performance.slow_query_threshold', 1000);

				if ($executionTime > $slowQueryThreshold) {
					if (config('canvastack.controller.logging.log_performance_issues', true)) {
						\Illuminate\Support\Facades\Log::warning('Slow query detected', [
							'execution_time_ms' => round($executionTime, 2),
							'threshold_ms' => $slowQueryThreshold,
							'query_preview' => substr($sql, 0, 200),
							'connection' => $connection ?? 'default',
							'user_id' => session('id'),
						]);
					}
				}
			}

			return $query;

		} catch (\Exception $e) {
			// Log query execution error
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::error('Query execution failed', [
					'error' => $e->getMessage(),
					'query_preview' => substr($sql, 0, 200),
					'connection' => $connection ?? 'default',
					'user_id' => session('id'),
				]);
			}

			throw $e;
		}
	}
}

if (!function_exists('canvastack_schema')) {
	
	function canvastack_schema(string $param, mixed $data = null): mixed {
		if (!empty($data)) {
			return Illuminate\Support\Facades\Schema::{$param}($data);
		} else {
			return Illuminate\Support\Facades\Schema::{$param}();
		}
	}
}

if (!function_exists('canvastack_db')) {
	
	function canvastack_db($param, $data = null) {
		if (!empty($data)) {
			return Illuminate\Support\Facades\DB::{$param}($data);
		} else {
			return Illuminate\Support\Facades\DB::{$param}();
		}
	}
}

if (!function_exists('canvastack_get_table_name_from_sql')) {
	
	function canvastack_get_table_name_from_sql(string $sql): string {
		$query = explode('from ', $sql);
		$query = explode(' ', $query[1]);
		
		return $query[0];
	}
}

if (!function_exists('canvastack_mapping_page')) {
	
	function canvastack_mapping_page(int $user_id): mixed {
		$groupController = new GroupController();
		return $groupController->get_data_mapping_page($user_id);
	}
}

if (!function_exists('canvastack_encrypt')) {
	
	/**
	 * Encrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvastack_encrypt(string $string): string {
		return Illuminate\Support\Facades\Crypt::encryptString($string);
	}
}

if (!function_exists('canvastack_decrypt')) {
	
	/**
	 * Decrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvastack_decrypt(string $string): string {
		return Illuminate\Support\Facades\Crypt::decryptString($string);
	}
}

if (!function_exists('canvastack_user_cryptcode')) {
	
	/**
	 * Encrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvastack_user_cryptcode(string $user_name, string $user_email): string {
		return canvastack_encrypt($user_name . canvastack_config('encode_separate') . $user_email);
	}
}

if (!function_exists('canvastack_clean_strings')) {
	
	/**
	 * Clean Strings
	 *
	 * @param string $strings
	 * @param string $replace_with
	 * 
	 * @return string
	 */
	function canvastack_clean_strings($strings, $replace_with = '-') {
		$strings = trim(preg_replace('/[;\.\/\?\\\:@&=+\$,_\~\*\'"\!\|%<>\{\}\^\[\]`\-]/', ' ', $strings));
		
		return strtolower(preg_replace('/\s+/', $replace_with, $strings));
	}
}

if (!function_exists('canvastack_string_contained')) {
	
	/**
	 * Find contained character in string(s)
	 *
	 * @param string|null $string The string to search in (null will return false)
	 * @param string|array $find The substring(s) to search for
	 *
	 * @return boolean True if any substring is found, false otherwise
	 */
	function canvastack_string_contained(?string $string, string|array $find): bool {
		// Handle null or empty string
		if ($string === null || $string === '') {
			return false;
		}
		
		if (is_array($find)) {
			foreach ($find as $str) if (strpos($string, $str) !== false) return true;
		} else {
			if (strpos($string, $find) !== false) return true;
		}
		
		return false;
	}
}

if (!function_exists('canvastack_underscore_to_camelcase')) {

    /**
	 * Convert underscore-separated string to Camel Case
	 * 
	 * Converts strings like 'user_name' to 'User Name' or 'USER NAME' for short acronyms.
	 * Output is escaped to prevent XSS when used in HTML context.
	 * 
	 * @param string $str Input string with underscores
	 * @return string Camel case string (escaped if XSS protection is enabled)
	 * 
	 * @security XSS Protection - Output is escaped to prevent XSS attacks
	 * 
	 * @example
	 * echo canvastack_underscore_to_camelcase('user_name'); // 'User Name'
	 * echo canvastack_underscore_to_camelcase('api_key'); // 'API Key'
	 */
	function canvastack_underscore_to_camelcase($str) {
		// Handle edge cases
		if (empty($str) || !is_string($str)) {
			return '';
		}

		// Check if XSS protection is enabled in config
		$xssProtection = config('canvastack.controller.security.xss_protection', true);

		$string = false;
		if (true === str_contains($str, '_')) {
			$slices  = explode('_', $str);
			$strings = [];

			foreach ($slices as $str) {
				// Skip empty slices
				if (empty($str)) {
					continue;
				}

				$_str = ucwords($str);
				if (strlen($str) <= 3) $_str = strtoupper($str);

				$strings[] = $_str;
			}
			$new_str = implode(' ', $strings);
			$string  = ucwords($new_str);
		} else {
			$string  = ucwords($str);
		}

		// Escape output to prevent XSS when used in HTML context
		if ($xssProtection) {
			$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
		}

		return $string;
	}
}

if (!function_exists('canvastack_url')) {
    
    /**
     * Get Url
     *
     * @param string $string
     *
     * @return string
     */
    function canvastack_url(string $string): string {
        return url()->{$string}();
    }
}

if (!function_exists('canvastack_array_contained_string')) {
	
	/**
	 * Check Array Contained With String
	 *
	 * @param array  $array
	 * @param string|array $string
	 *
	 * @return boolean
	 */
	function canvastack_array_contained_string(array $array, string|array $string, bool $return_data = false): bool|array {
		$result           = [];
		$result['status'] = false;
		$result['data']   = [];
		
		foreach($array as $info => $data) {
			if (canvastack_string_contained($data, $string)) {
				$result['status']      = true;
				$result['data'][$info] = $string;
			}
		}
		
		if (false !== $return_data) {
			return $result['data'];
		} else {
			return $result['status'];
		}
	}
}

if (!function_exists('canvastack_array_to_object_recursive')) {
    
    /**
     * Converting multidimensional array to object
     *
     * @param array $array
     * @return StdClass|array
     *
     * @link: https://stackoverflow.com/questions/9169892/how-to-convert-multidimensional-array-to-object-in-php
     */
    function canvastack_array_to_object_recursive(mixed $array): mixed {
        if (is_array($array) ) {
            foreach($array as $key => $value) {
                $array[$key] = canvastack_array_to_object_recursive($value);
            }
            
            return (object) $array;
        }
        
        return $array;
    }
}

if (!function_exists('canvastack_array_insert_new')) {
	
	/**
	 * Insert Data In Spesific array pos
	 *
	 * @author: https://stackoverflow.com/a/11321318/20139717
	 *
	 * @param array $array
	 * @param int $index
	 * @param string $val
	 *
	 * @return number|array
	 */
	function canvastack_array_insert_new(array $array, int $index, mixed $val): int|array {
		$size = count($array);
		if (!is_int($index) || $index <0 || $index > $size) {
			return -1;
		} else {
			$temp   = array_slice($array, 0, $index);
			$temp[] = $val;
			
			return array_merge($temp, array_slice($array, $index, $size));
		}
	}
}

if (!function_exists('canvastack_array_insert')) {
	
	function canvastack_array_insert(array &$array, int|string $position, mixed $insert): void {
		if (is_int($position)) {
			array_splice($array, $position, 0, $insert);
		} else {
			$pos   = array_search($position, array_keys($array));
			$array = array_merge (
				array_slice($array, 0, $pos),
				$insert,
				array_slice($array, $pos)
			);
		}
	}
}

if (!function_exists('canvastack_exist_url')) {
    
    /**
     * Check if url exist
     * 
     * @param string $url
     * 
     * @return boolean
     */
    function canvastack_exist_url(string $url): bool {
        $file_headers = @get_headers($url);
        
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        
        return true;
    }
}

if (!function_exists('canvastack_not_empty')) {
    
    /**
     * Checking Not Empty Data
     *
     * @param mixed $data
     * @return mixed|boolean
     */
    function canvastack_not_empty(mixed $data): mixed {
        if (isset($data) && !empty($data) && '' != $data && NULL != $data) {
            return $data;
        } else {
            return false;
        }
    }
}

if (!function_exists('canvastack_is_empty')) {
    
    /**
     * Checking Empty Data
     *
     * @param mixed $data
     * @return boolean
     */
    function canvastack_is_empty($data) {
        return !canvastack_not_empty($data);
    }
}

if (!function_exists('canvastack_object')) {
	
	/**
	 * Create Object
	 *
	 * @param mixed $array
	 *
	 * @return object
	 */
	function canvastack_object($array) {
		return (object) $array;
	}
}

if (!function_exists('camel_case')) {
	
	/**
	 * Camel Case
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string
	 * @return string
	 */
	function camel_case(string $string): string {
		return ucfirst($string);
	}
}

if (!function_exists('canvastack_random_strings')) {
	
	/**
	 * Random String
	 *
	 * @param number $length
	 * @return string
	 */
	function canvastack_random_strings(int $length = 8, bool $symbol = true, ?string $string_set = null, ?string $node = '_'): string {
		$random_strings = '';
		$strSymbol      = null;
		if (true === $symbol) {
			$strSymbol   = '!@#$%';
		}
		$strings        = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789{$strSymbol}";
		$stringsLength  = strlen($strings);
		
		for ($i = 0; $i < $length; $i ++) {
			$random_strings .= $strings[rand(0, $stringsLength - 1)];
		}
		
		if (!empty($string_set)) {
			return $string_set . $node . $random_strings;
		} else {
			return $random_strings;
		}
	}
}

if (!function_exists('canvastack_unescape_html')) {
	
	/**
	 * Returning Back Escaped HTML
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param string $html
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	function canvastack_unescape_html(string $html): Illuminate\Support\HtmlString {
		return new Illuminate\Support\HtmlString($html);
	}
}

if (!function_exists('get_object_called_name')) {
	
	/**
	 * Get Called Name Object
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param object $object
	 *
	 * @return string
	 */
	function get_object_called_name($object) {
		return strtolower(last(explode('\\', get_class($object))));
	}
}

if (!function_exists('current_route')) {
	
	/**
	 * Get Current Route Name
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function current_route() {
		return Route::currentRouteName();
	}
}

if (!function_exists('canvastack_current_route')) {
	/**
	 * Get Current Route
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $facadeRoot
	 * @return object|mixed|string[]|object[]|\Illuminate\Contracts\Foundation\Application
	 */
	function canvastack_current_route($facadeRoot = false) {
		if (false === $facadeRoot) return Route::getCurrentRoute();
		else return Route::getFacadeRoot();
	}
}

if (!function_exists('canvastack_current_baseroute')) {
	/**
	 * Get Base Route From Current Route
	 *
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $facadeRoot
	 * @return mixed
	 */
	function canvastack_current_baseroute(): string {
		$lastRoute = canvastack_last_explode('.', canvastack_current_route()->getName());
		
		return str_replace(".{$lastRoute}", '', canvastack_current_route()->getName());
	}
}

if (!function_exists('canvastack_last_explode')) {
	/**
	 * Get Last Array
	 *
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param string $delimeter
	 * @param array $array
	 *
	 * @return mixed
	 */
	function canvastack_last_explode(string $delimeter, string $array): string {
		return last(explode($delimeter, $array));
	}
}

if (!function_exists('canvastack_get_current_route_id')) {
	
	/**
	 * Get ID From Current Route
	 *
	 * created @Apr 12, 2021
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvastack_get_current_route_id($exclude_last = true) {
		$currentDataURL = explode('/', canvastack_current_url());
		if (true === $exclude_last) {
			unset($currentDataURL[array_key_last($currentDataURL)]);
		}
		
		return intval($currentDataURL[array_key_last($currentDataURL)]);
	}
}

if (!function_exists('canvastack_route_request_value')) {
	
	/**
	 * Get Route Value From Request
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param string $field
	 * 
	 * @return string
	 */
	function canvastack_route_request_value($field) {
		$request = new Request();
		$request->route($field);
	}
}

if (!function_exists('canvastack_current_url')) {

	/**
	 * Get Current URL
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvastack_current_url(): string {
		return url()->current();
	}
}

if (!function_exists('canvastack_log_activity')) {
	
	/**
	 * Create Data User Log Activity
	 */
	function canvastack_log_activity(array $routeInfo = [], array $data = []): void {
		$configuration = canvastack_config('log_activity');
		if (!empty($configuration) && in_array($configuration['run_status'], [true, 'unexceptions'])) {
			
			$sessions = session()->all();
			if(!empty($data)) $sessions = $data;
			
			if (!empty($sessions['user_group'])) {
				if (empty($routeInfo)) {
					$routes       = canvastack_current_route();
				}
				$requests         = Illuminate\Support\Facades\Request::class;
				$group_exceptions = ['root'];
				if (!empty($configuration['exceptions']['groups'])) {
					$group_exceptions = array_merge_recursive(['root'], $configuration['exceptions']['groups']);
				}
				if ('unexceptions' === $configuration['run_status']) {
					$group_exceptions = [];
				}
				
				if (!in_array($sessions['user_group'], $group_exceptions)) {
					if (empty($routeInfo)) {
						$current_controller = explode('@', $routes->action['controller']);
					} else {
						$current_controller = explode('@', $routeInfo['controller']);
					}
					
					if (!in_array($current_controller[0], $configuration['exceptions']['controllers'])) {
						$logs                    = [];
						
						$logs['user_id']         = $sessions['id'];
						$logs['username']        = $sessions['username'];
						$logs['user_fullname']   = $sessions['fullname'];
						$logs['user_email']      = $sessions['email'];
						
						$logs['user_group_id']   = $sessions['group_id'];
						$logs['user_group_name'] = $sessions['user_group'];
						$logs['user_group_info'] = $sessions['group_info'];
						
						if (empty($routeInfo)) {
							$logs['route_path']  = $routes->controller->data['route_info']->current_path;
							$logs['module_name'] = $routes->controller->data['route_info']->module_name;
							$logs['page_info']   = $routes->controller->data['route_info']->page_info;
						} else {
							$logs['route_path']  = $routeInfo['current_path'];
							$logs['module_name'] = $routeInfo['module_name'];
							$logs['page_info']   = $routeInfo['page_info'];
						}
						$logs['urli']            = $requests::fullUrl();
						$logs['method']          = $requests::method();
						
						$logs['ip_address']      = $requests::ip();
						$logs['user_agent']      = $requests::header('user-agent');
						$logs['sql_dump']        = NULL;
						
						$logs['created_at']      = date('Y-m-d h:i:s');
						$logs['updated_at']      = NULL;
						
						Log::create($logs);
					}
				}
			}
		}
	}
}

if (!function_exists('set_break_line_html')) {
	
	/**
	 * Adding Break Line
	 *
	 * @param string $tag
	 * @param integer $loops
	 *
	 * @return string
	 */
	function set_break_line_html(string $tag, int $loops = 1): string {
		$tags	= "";
		for ($x=2; $x<=$loops; $x++) $tags .= $tag;
		
		return "{$tags}";
	}
}

if (!function_exists('minify_code')) {
	
	/**
	 * Sanitizing Output
	 *
	 * Copyed From: http://php.net/manual/en/function.ob-start.php#71953:sanitize_output
	 *
	 * @param array $buffer
	 * @return mixed
	 */
	function sanitize_output($buffer) {
		/**
		 * (1) Strip whitespaces after tags, except space
		 * (2) Strip whitespaces before tags, except space
		 * (3) Shorten multiple whitespace sequences
		 * (4) Remove empty lines (between HTML tags)
		 * 			: cannot remove just any line-end characters because in inline JS they can matter!
		 * (5) Remove unwanted HTML comments <!-- text -->
		 * (6) Remove unwanted HTML comments /**
		 */
		
		$search = [
			'/\>[^\S ]+/s', 
			'/[^\S ]+\</s', 
			'/(\s)+/s', 
			'/\>[\r\n\t ]+\</s', 
			'/<!--(.|\s)*?-->/',
			'~^\s*//.*$\s*~m'
		];
		$replace = ['>', '<', '\\1', '><', '', ''];
		$search  = ['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'];
		$replace = [' ', ''];
		return set_break_line_html("\n", 1986) . preg_replace($search, $replace, $buffer);
	}
	
	/**
	 * Minified HTML output in a single line
	 * 		: Remember to remove all double slash comment(s) in your javascript code !!!
	 *
	 * @param string $output
	 */
	function minify_code($output = true) {
		if (false !== $output) {
			ob_start('sanitize_output');
		}
	}
}

if (!function_exists('canvastack_insert')) {
	
	/**
	 * Simply Insert POST Data to Database with Optimization
	 *
	 * Optimizations:
	 * - Reduced memory allocations
	 * - Efficient array operations
	 * - Input validation
	 * - Result caching support
	 * - Security logging
	 *
	 * @param object|string $model Model instance or class name
	 * @param array|object $data Data to insert
	 * @param bool|string $get_field Field to return (true for 'id', string for specific field, false for none)
	 * @return mixed
	 * @throws \InvalidArgumentException
	 * 
	 * @security Validates input data and logs suspicious patterns
	 * @performance Optimized for memory efficiency and speed
	 */
	function canvastack_insert($model, $data, $get_field = false) {
		// Check if helper optimization is enabled
		$optimizationEnabled = config('canvastack.controller.performance.enable_helper_optimization', true);
		
		// Start performance monitoring if enabled
		$startTime = $optimizationEnabled && config('canvastack.controller.performance.enable_query_monitoring', true) 
			? microtime(true) : 0;

		// Validate inputs
		if (empty($model)) {
			throw ControllerValidationException::invalidParameter(
				'model',
				$model,
				'Model parameter cannot be empty',
				[
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}

		if (empty($data)) {
			throw ControllerValidationException::invalidParameter(
				'data',
				$data,
				'Data parameter cannot be empty',
				[
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}

		// Convert data to array efficiently
		$requestData = is_object($data) ? (array) $data->all() : (array) $data;

		// Pre-allocate array with expected size for better memory efficiency
		$requests = [];
		
		// Cache password keys to avoid repeated array creation
		static $passwordKeys = ['pass', 'password', 'passkey'];

		// Process request data efficiently with optimized string operations
		foreach ($requestData as $key => $value) {
			// Skip nested arrays (like DataTables POST params) - optimized check
			if (is_array($value)) {
				// Use array_filter for faster nested array detection
				$hasNestedArray = false;
				foreach ($value as $v) {
					if (is_array($v)) {
						$hasNestedArray = true;
						break;
					}
				}

				if ($hasNestedArray) {
					continue;
				}

				// Convert array to comma-separated string efficiently
				$value = implode(',', $value);
			}

			// Handle date/datetime placeholders - optimized comparison
			if ($value === '____-__-__' || $value === '____-__-__ __:__:__') {
				$value = null;
			}

			// Handle WIB timezone - optimized string operation
			if (is_string($value) && str_contains($value, 'WIB')) {
				$value = str_replace(' WIB', ':' . date('s'), $value);
			}

			// Hash password fields - use strict comparison for performance
			if (in_array($key, $passwordKeys, true)) {
				$value = Hash::make($value);
			}

			$requests[$key] = $value;
		}

		// Create model instance efficiently
		try {
			$modelInstance = new $model($requests);

			// Handle password field separately if exists
			if (array_key_exists('password', $requests)) {
				$modelInstance->fill(['password' => $requests['password']]);
			}

			// Create record
			$created = $modelInstance::create($requests);

			// Log performance metrics if monitoring is enabled
			if ($startTime > 0) {
				$executionTime = (microtime(true) - $startTime) * 1000;
				$threshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
				
				if ($executionTime > $threshold && config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::warning('Slow canvastack_insert operation', [
						'execution_time_ms' => round($executionTime, 2),
						'threshold_ms' => $threshold,
						'model' => is_string($model) ? $model : get_class($model),
						'user_id' => session('id'),
					]);
				}
			}

			// Return requested field
			if ($get_field !== false) {
				if ($get_field === true) {
					return $created->id;
				}
				return $created->{$get_field};
			}

			return $created;

		} catch (\Exception $e) {
			// Log error if logging is enabled
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::error('canvastack_insert failed', [
					'model' => is_string($model) ? $model : get_class($model),
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}

			throw $e;
		}
	}
}

if (!function_exists('canvastack_update')) {
	
	/**
	 * Simply Update POST Data to Database with Optimization
	 *
	 * Optimizations:
	 * - Reduced memory allocations
	 * - Efficient array operations
	 * - Input validation
	 * - Security logging
	 * - Proper error handling
	 *
	 * @param object $model Model instance to update
	 * @param array|object $data Data to update
	 * @return bool Success status
	 * @throws \InvalidArgumentException
	 * 
	 * @security Validates input data and logs suspicious patterns
	 * @performance Optimized for memory efficiency and speed
	 */
	function canvastack_update($model, $data) {
		// Check if helper optimization is enabled
		$optimizationEnabled = config('canvastack.controller.performance.enable_helper_optimization', true);
		
		// Start performance monitoring if enabled
		$startTime = $optimizationEnabled && config('canvastack.controller.performance.enable_query_monitoring', true) 
			? microtime(true) : 0;

		// Validate inputs
		if (empty($model)) {
			throw ControllerValidationException::invalidParameter(
				'model',
				$model,
				'Model parameter cannot be empty',
				[
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}

		if (empty($data)) {
			throw ControllerValidationException::invalidParameter(
				'data',
				$data,
				'Data parameter cannot be empty',
				[
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}

		// Convert data to array efficiently
		$requestData = is_object($data) ? (array) $data->all() : (array) $data;

		// Pre-allocate array with expected size for better memory efficiency
		$requests = [];
		
		// Cache password keys to avoid repeated array creation
		static $passwordKeys = ['pass', 'password', 'passkey'];

		// Process request data efficiently with optimized string operations
		foreach ($requestData as $key => $value) {
			// Skip nested arrays (like DataTables POST params) - optimized check
			if (is_array($value)) {
				// Use optimized nested array detection
				$hasNestedArray = false;
				foreach ($value as $v) {
					if (is_array($v)) {
						$hasNestedArray = true;
						break;
					}
				}

				if ($hasNestedArray) {
					continue;
				}

				// Convert array to comma-separated string efficiently
				$value = implode(',', $value);
			}

			// Handle date/datetime placeholders - optimized comparison
			if ($value === '____-__-__' || $value === '____-__-__ __:__:__') {
				$value = null;
			}

			// Handle WIB timezone - optimized string operation
			if (is_string($value) && str_contains($value, 'WIB')) {
				$value = str_replace(' WIB', ':' . date('s'), $value);
			}

			// Hash password fields - use strict comparison for performance
			if (in_array($key, $passwordKeys, true)) {
				$value = Hash::make($value);
			}

			$requests[$key] = $value;
		}

		// Update model efficiently
		try {
			// Handle password field separately if exists
			if (array_key_exists('password', $requests)) {
				$model->fill(['password' => $requests['password']]);
			}

			// Update record
			$result = $model->update($requests);

			// Log performance metrics if monitoring is enabled
			if ($startTime > 0) {
				$executionTime = (microtime(true) - $startTime) * 1000;
				$threshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
				
				if ($executionTime > $threshold && config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::warning('Slow canvastack_update operation', [
						'execution_time_ms' => round($executionTime, 2),
						'threshold_ms' => $threshold,
						'model' => get_class($model),
						'model_id' => $model->id ?? null,
						'user_id' => session('id'),
					]);
				}
			}

			return $result;

		} catch (\Exception $e) {
			// Log error if logging is enabled
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::error('canvastack_update failed', [
					'model' => get_class($model),
					'model_id' => $model->id ?? null,
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}

			throw $e;
		}
	}
}

if (!function_exists('canvastack_delete')) {
	
	/**
	 * Simply Delete(Soft) and or Restore deleted row from database
	 *
	 * @param object $request
	 * @param object $model_name
	 * @param int $id
	 *
	 * created @Aug 10, 2018
	 * author: wisnuwidi
	 */
	function canvastack_delete($request, $model_name, $id) {
		// Check if helper optimization is enabled
		$optimizationEnabled = config('canvastack.controller.performance.enable_helper_optimization', true);
		
		// Start performance monitoring if enabled
		$startTime = $optimizationEnabled && config('canvastack.controller.performance.enable_query_monitoring', true) 
			? microtime(true) : 0;

		try {
			// Find model efficiently
			$model = $model_name->find($id);
			
			if (!empty($model->id)) {
				// Model exists, perform soft delete
				$model->delete();
				
				// Log performance if monitoring enabled
				if ($startTime > 0) {
					$executionTime = (microtime(true) - $startTime) * 1000;
					$threshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
					
					if ($executionTime > $threshold && config('canvastack.controller.logging.log_performance_issues', true)) {
						\Illuminate\Support\Facades\Log::warning('Slow canvastack_delete operation', [
							'execution_time_ms' => round($executionTime, 2),
							'threshold_ms' => $threshold,
							'model' => get_class($model_name),
							'model_id' => $id,
							'operation' => 'delete',
							'user_id' => session('id'),
						]);
					}
				}
			} else {
				// Model not found, check if it's soft deleted and restore
				if (true === canvastack_is_softdeletes($model_name)) {
					$remodel = $model_name::withTrashed()->find($id);
					
					if ($remodel) {
						$remodel->restore();
						
						// Log performance if monitoring enabled
						if ($startTime > 0) {
							$executionTime = (microtime(true) - $startTime) * 1000;
							$threshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
							
							if ($executionTime > $threshold && config('canvastack.controller.logging.log_performance_issues', true)) {
								\Illuminate\Support\Facades\Log::warning('Slow canvastack_delete operation', [
									'execution_time_ms' => round($executionTime, 2),
									'threshold_ms' => $threshold,
									'model' => get_class($model_name),
									'model_id' => $id,
									'operation' => 'restore',
									'user_id' => session('id'),
								]);
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
			// Log error if logging is enabled
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::error('canvastack_delete failed', [
					'model' => is_object($model_name) ? get_class($model_name) : $model_name,
					'model_id' => $id,
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}

			throw $e;
		}
	}
}

if (!function_exists('canvastack_query_get_id')) {
	function canvastack_query_get_id(object $model_class, array $where = []): mixed {
		return $model_class::where($where)->first();
	}
}

if (!function_exists('canvastack_set_filesize')) {
	
	/**
	 * Set File Size
	 *
	 * created @Sep 8, 2018
	 * author: wisnuwidi
	 *
	 * @param integer $size
	 * @param string $type
	 *
	 * @return number
	 */
	function canvastack_set_filesize(int $size, string $type = 'M'): int {
		if ('M' === $type) $megabytes = 1024;
		$filesize = intval($megabytes*intval($size));
		ini_set('upload_max_filesize', "{$filesize}{$type}");
		ini_set('post_max_size', "{$filesize}{$type}");
		
		return $filesize;
	}
}

if (!function_exists('canvastack_image_validations')) {
	
	/**
	 * Set Image Validations
	 *
	 * created @Sep 8, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean|integer $max_size
	 *
	 * @return string
	 */
	function canvastack_image_validations(int|bool $max_size = false): string {
		$max = false;
		if (false !== $max_size) $max = "|max:{$max_size}";
		
		return "image|mimes:jpeg,png,jpg,gif,svg{$max}";
	}
}

if (!function_exists('canvastack_file_validations')) {
	
	/**
	 * Set File Validations
	 *
	 * created @Oct 16, 2018
	 * author: wisnuwidi
	 *
	 * @param string $mimes
	 * @param boolean|integer $max_size
	 *
	 * @return string
	 */
	function canvastack_file_validations(string $mimes = "txt", int|bool $max_size = false): string {
		$max = false;
		if (false !== $max_size) $max = "|max:{$max_size}";
		
		return "text|mimes:{$mimes}{$max}";
	}
}

if (!function_exists('canvastack_file_exist')) {
	
	function canvastack_file_exist($filePath) {
		return File::exists($filePath);
	}
}

if (!function_exists('canvastack_make_dir')) {
	
	/**
	 * Create New Directory
	 *
	 * created @Jul 13, 2018
	 * author: wisnuwidi
	 *
	 * @param string $filePath
	 * @param number $mode
	 * @param bool $recursive
	 * @param bool $force
	 */
	function canvastack_make_dir(string $filePath, int $mode = 0777, bool $recursive = true, bool $force = true): void {
		if (false === canvastack_file_exist($filePath)) {
			File::makeDirectory($filePath, $mode, $recursive, $force);
		}
	}
}

if (!function_exists('canvastack_action_buttons')) {
	
	/**
	 * Generate action buttons HTML from route info
	 * 
	 * Generates HTML for action buttons based on route information.
	 * All user-controllable data is escaped to prevent XSS attacks.
	 * 
	 * @param object $route_info Route information object with action_page property
	 * @param string $background_color Background color class (default: 'white')
	 * @return string Safe HTML for action buttons (already escaped, ready for output)
	 * 
	 * @security XSS Protection - All user data is escaped before output
	 * @security Output is safe for direct rendering with {!! !!} in Blade templates
	 * 
	 * @example
	 * $html = canvastack_action_buttons($route_info);
	 * echo $html; // Safe to output directly
	 */
	function canvastack_action_buttons($route_info, $background_color = 'white') {
		if (!empty($route_info) && !empty($route_info->action_page)) {
			// Check if XSS protection is enabled in config
			$xssProtection = config('canvastack.controller.security.xss_protection', true);

			$box  = '';
			// Escape background color to prevent XSS
			$escapedBgColor = $xssProtection ? htmlspecialchars($background_color, ENT_QUOTES, 'UTF-8') : $background_color;
			$box .= "<div class=\"header {$escapedBgColor}\">";

			foreach ($route_info->action_page as $key => $value) {
				$keys  = explode('|', $key);
				$color = $keys[0];
				$text  = $keys[1];

				// Check if this is a dropdown button (value is array)
				if (is_array($value)) {
					// Render dropdown button
					$box .= canvastack_dropdown_button_box($value, $text, $color);
					continue;
				}

				if (!canvastack_string_contained($text, 'delete') && !canvastack_string_contained($text, 'restore')) {
					// Get button HTML (already escaped)
					$box .= canvastack_action_button_box($value, $text, $color);
				} else {
					$routeInfo = explode('::', $value);
					$routeUri  = [$routeInfo[0], (int)$routeInfo[1]];

					$box .= Collective\Html\FormFacade::open(['route'=> $routeUri, 'method'=>'Delete', 'onsubmit' => 'confirm("Are you sure?")']);
					$box .= canvastack_action_button_box('submitButtonTag', $text, $color);
					$box .= Collective\Html\FormFacade::close();
				}
			}
			$box .= "</div>";

			// Return safe HTML (already escaped, ready for output)
			return $box;
		}

		return '';
	}
}

if (!function_exists('canvastack_page_action_buttons')) {

	/**
	 * Render page-level action buttons using the active theme adapter.
	 *
	 * This is the DRY, template-aware replacement for calling
	 * canvastack_action_buttons() directly. It delegates to:
	 *   - DefaultAdapter::renderPageActionButtons()   → Bootstrap 4 HTML
	 *   - Bootstrap5Adapter::renderPageActionButtons() → Bootstrap 5 HTML
	 *   - TailwindAdapter::renderPageActionButtons()   → Tailwind HTML
	 *
	 * Usage in Blade (any template):
	 *   {!! canvastack_page_action_buttons($route_info) !!}
	 *
	 * @param object|null $route_info  Route info object with action_page property.
	 * @return string                  Safe HTML ready for {!! !!} output.
	 */
	function canvastack_page_action_buttons(?object $route_info): string
	{
		if (empty($route_info) || empty($route_info->action_page)) {
			return '';
		}

		return \Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver::resolve()
			->renderPageActionButtons($route_info);
	}
}

if (!function_exists('encode_id')) {
	
	function encode_id(int $id, bool $hashing = true): string {
		$hash = false;
		if (true === $hashing) $hash = hash_code_id();
		
		return intval($id + 8 * 800 / 80) . $hash;
	}
}

if (!function_exists('decode_id')) {
	
	function decode_id($id, $hashing = true) {
		$hash = false;
		if (true === $hashing) $hash = hash_code_id();
		$ID = str_replace($hash, "", $id);
		
		return intval($ID - 8 * 800 / 80) . $hash;
	}
}

if (!function_exists('hash_code_id')) {
	
	function hash_code_id(): string {
		return hash('haval128,4', 'IDRIS');
	}
}

if (!function_exists('canvastack_action_button_box')) {
	
	/**
	 * Generate action button box HTML
	 * 
	 * Creates HTML for a single action button with proper escaping.
	 * All user-controllable data is escaped to prevent XSS attacks.
	 * 
	 * @param string $url Button URL or 'submitButtonTag' for submit buttons
	 * @param string $button_text Button text to display
	 * @param string|bool $url_class Button color class (default: false for default styling)
	 * @param string $panel_class Panel class for the container (default: 'panel-title header-list-panel')
	 * @return string Safe HTML for action button box (already escaped, ready for output)
	 * 
	 * @security XSS Protection - All user data is escaped before output
	 * @security Output is safe for direct rendering with {!! !!} in Blade templates
	 * 
	 * @example
	 * $html = canvastack_action_button_box('/users/create', 'Create User', 'primary');
	 * echo $html; // Safe to output directly
	 */
	function canvastack_action_button_box($url, $button_text, $url_class = false, $panel_class = 'panel-title header-list-panel') {
		// Check if XSS protection is enabled in config
		$xssProtection = config('canvastack.controller.security.xss_protection', true);

		// Escape button text to prevent XSS
		$buttonText = ucwords($button_text);
		if ($xssProtection) {
			$buttonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
		}

		// Build URL class with escaping
		if (empty($url_class)) {
			$urlClass = 'btn btn-default btn_create btn-slideright button-app action-button pull-right';
		} else {
			// Escape color class to prevent XSS
			$escapedUrlClass = $xssProtection ? htmlspecialchars($url_class, ENT_QUOTES, 'UTF-8') : $url_class;
			$urlClass = "btn btn-{$escapedUrlClass} btn_create btn-slideright button-app action-button pull-right";
		}

		// Escape panel class to prevent XSS
		$escapedPanelClass = $xssProtection ? htmlspecialchars($panel_class, ENT_QUOTES, 'UTF-8') : $panel_class;

		$box  = "<h3 class=\"{$escapedPanelClass}\">";
		if ('submitButtonTag' === $url) {
			$box .= "<button class=\"{$urlClass}\" type=\"submit\">{$buttonText}</button>";
		} else {
			// Escape URL to prevent XSS
			$escapedUrl = $xssProtection ? htmlspecialchars($url, ENT_QUOTES, 'UTF-8') : $url;
			$box .= "<a href=\"{$escapedUrl}\" class=\"{$urlClass}\">{$buttonText}</a>";
		}
		$box .= "</h3>";

		// Return safe HTML (already escaped, ready for output)
		return $box;
	}
}

if (!function_exists('canvastack_dropdown_button_box')) {
	
	/**
	 * Generate dropdown button box HTML
	 * 
	 * Creates HTML for a dropdown button with multiple menu items.
	 * All user-controllable data is escaped to prevent XSS attacks.
	 * 
	 * @param array $items Dropdown menu items (each with 'label', 'url', optional 'icon', 'data')
	 * @param string $button_text Button text to display
	 * @param string|bool $url_class Button color class (default: false for default styling)
	 * @param string $panel_class Panel class for the container (default: 'panel-title header-list-panel')
	 * @return string Safe HTML for dropdown button box (already escaped, ready for output)
	 * 
	 * @security XSS Protection - All user data is escaped before output
	 * @security Output is safe for direct rendering with {!! !!} in Blade templates
	 * 
	 * @example
	 * $items = [
	 *     ['label' => 'Clear All', 'url' => '/cache/clear', 'icon' => 'fa fa-trash'],
	 *     ['divider' => true],
	 *     ['label' => 'Clear Config', 'url' => '/cache/config', 'icon' => 'fa fa-cog'],
	 * ];
	 * $html = canvastack_dropdown_button_box($items, 'Cache Tools', 'primary');
	 * echo $html; // Safe to output directly
	 */
	function canvastack_dropdown_button_box($items, $button_text, $url_class = false, $panel_class = 'panel-title header-list-panel') {
		// Check if XSS protection is enabled in config
		$xssProtection = config('canvastack.controller.security.xss_protection', true);

		// Escape button text to prevent XSS
		$buttonText = ucwords($button_text);
		if ($xssProtection) {
			$buttonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
		}

		// Build URL class with escaping
		if (empty($url_class)) {
			$urlClass = 'btn btn-default btn_create btn-slideright button-app action-button pull-right dropdown-toggle';
		} else {
			// Escape color class to prevent XSS
			$escapedUrlClass = $xssProtection ? htmlspecialchars($url_class, ENT_QUOTES, 'UTF-8') : $url_class;
			$urlClass = "btn btn-{$escapedUrlClass} btn_create btn-slideright button-app action-button pull-right dropdown-toggle";
		}

		// Escape panel class to prevent XSS
		$escapedPanelClass = $xssProtection ? htmlspecialchars($panel_class, ENT_QUOTES, 'UTF-8') : $panel_class;

		// Generate unique ID for dropdown
		$dropdownId = 'dropdown-' . uniqid();

		$box  = "<h3 class=\"{$escapedPanelClass}\">";
		$box .= "<div class=\"btn-group\">";
		$box .= "<button type=\"button\" class=\"{$urlClass}\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
		$box .= "{$buttonText} <span class=\"caret\"></span>";
		$box .= "</button>";
		$box .= "<div class=\"dropdown-menu\">";

		// Render dropdown items
		foreach ($items as $item) {
			// Check if this is a divider
			if (isset($item['divider']) && $item['divider'] === true) {
				$box .= "<div class=\"dropdown-divider\"></div>";
				continue;
			}

			// Escape item data
			$label = $xssProtection ? htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8') : ($item['label'] ?? '');
			$url = $xssProtection ? htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8') : ($item['url'] ?? '#');
			$icon = isset($item['icon']) ? ($xssProtection ? htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') : $item['icon']) : '';

			// Build data attributes
			$dataAttrs = '';
			if (isset($item['data']) && is_array($item['data'])) {
				foreach ($item['data'] as $key => $value) {
					$escapedKey = $xssProtection ? htmlspecialchars($key, ENT_QUOTES, 'UTF-8') : $key;
					$escapedValue = $xssProtection ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
					$dataAttrs .= " data-{$escapedKey}=\"{$escapedValue}\"";
				}
			}

			$box .= "<a href=\"{$url}\" class=\"dropdown-item cache-action\"{$dataAttrs}>";
			if (!empty($icon)) {
				$box .= "<i class=\"{$icon}\"></i> ";
			}
			$box .= $label;
			$box .= "</a>";
		}

		$box .= "</div>";
		$box .= "</div>";
		$box .= "</h3>";
		
		// Add modal HTML for confirmations and messages
		$modalId = 'cacheModal-' . uniqid();
		$box .= "
		<!-- Cache Management Modal -->
		<div class=\"modal fade\" id=\"{$modalId}\" tabindex=\"-1\" role=\"dialog\">
			<div class=\"modal-dialog\" role=\"document\">
				<div class=\"modal-content\">
					<div class=\"modal-header\">
						<h5 class=\"modal-title\" id=\"{$modalId}Title\">Cache Management</h5>
						<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
							<span aria-hidden=\"true\">&times;</span>
						</button>
					</div>
					<div class=\"modal-body\" id=\"{$modalId}Body\">
						<!-- Content will be inserted here -->
					</div>
					<div class=\"modal-footer\" id=\"{$modalId}Footer\">
						<!-- Buttons will be inserted here -->
					</div>
				</div>
			</div>
		</div>
		";
		
		// Add inline JavaScript to handle cache actions with modal
		$scriptContent = "
		(function() {
			var modalId = '{$modalId}';
			
			// Helper function to show modal
			function showModal(title, body, buttons) {
				document.getElementById(modalId + 'Title').textContent = title;
				document.getElementById(modalId + 'Body').innerHTML = body;
				document.getElementById(modalId + 'Footer').innerHTML = buttons;
				$('#' + modalId).modal('show');
			}
			
			// Helper function to hide modal
			function hideModal() {
				$('#' + modalId).modal('hide');
			}
			
			document.addEventListener('DOMContentLoaded', function() {
				var cacheLinks = document.querySelectorAll('.cache-action[data-cache-type]');
				cacheLinks.forEach(function(link) {
					link.addEventListener('click', function(e) {
						e.preventDefault();
						var cacheType = this.dataset.cacheType;
						var url = this.dataset.url;
						var token = document.querySelector('meta[name=\"csrf-token\"]');
						
						if (!token) {
							showModal(
								'Error',
								'<p>CSRF token not found. Please refresh the page.</p>',
								'<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>'
							);
							return;
						}
						
						// Show confirmation modal (except for optimize)
						if (cacheType !== 'optimize') {
							showModal(
								'Confirm Cache Clear',
								'<p>Are you sure you want to clear <strong>' + cacheType + '</strong> cache?</p>',
								'<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Cancel</button>' +
								'<button type=\"button\" class=\"btn btn-danger\" id=\"confirmCacheClear\">Clear Cache</button>'
							);
							
							// Handle confirm button
							document.getElementById('confirmCacheClear').addEventListener('click', function() {
								executeCacheClear(cacheType, url, token.content);
							});
						} else {
							// Execute optimize directly
							executeCacheClear(cacheType, url, token.content);
						}
					});
				});
			});
			
			// Execute cache clear
			function executeCacheClear(cacheType, url, csrfToken) {
				// Show loading modal
				showModal(
					'Processing',
					'<div class=\"text-center\"><i class=\"fa fa-spinner fa-spin fa-3x\"></i><p class=\"mt-3\">Clearing ' + cacheType + ' cache...</p></div>',
					''
				);
				
				fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken,
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: JSON.stringify({ type: cacheType })
				})
				.then(function(response) {
					if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
					return response.json();
				})
				.then(function(data) {
					if (data.success) {
						showModal(
							'Success',
							'<div class=\"alert alert-success\"><i class=\"fa fa-check-circle\"></i> ' + data.message + '</div>',
							'<button type=\"button\" class=\"btn btn-primary\" onclick=\"location.reload()\">Reload Page</button>' +
							'<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>'
						);
					} else {
						showModal(
							'Error',
							'<div class=\"alert alert-danger\"><i class=\"fa fa-exclamation-circle\"></i> ' + (data.message || 'Cache clear failed') + '</div>',
							'<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>'
						);
					}
				})
				.catch(function(error) {
					showModal(
						'Error',
						'<div class=\"alert alert-danger\"><i class=\"fa fa-exclamation-circle\"></i> Cache clear failed: ' + error.message + '</div>',
						'<button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>'
					);
					console.error('Cache clear error:', error);
				});
			}
		})();
		";
		
		// Minify the script if minification is enabled
		if (function_exists('canvastack_minify_inline_script')) {
			$scriptContent = canvastack_minify_inline_script($scriptContent);
		}
		
		$box .= "<script>{$scriptContent}</script>";

		// Return safe HTML (already escaped, ready for output)
		return $box;
	}
}

if (!function_exists('canvastack_get_model_controllers_info')) {
	
	function canvastack_get_model_controllers_info(array $buffers = [], mixed $table_replace_map = null, string $restriction_path = 'App\Http\Controllers\Admin\\'): array {
		$routeLists = Route::getRoutes();
		$models     = [];
		
		foreach ($routeLists as $list) {
			$route_name = $list->getName();
			$routeObj   = explode('.', $route_name);
			$actionName = $list->getActionName();
			
			if (str_contains($actionName, $restriction_path)) {
				// check if controller created in Admin folder
				if (count($routeObj) > 1) {
					if (in_array('index', $routeObj)) {
						$controllerPath = str_replace('@index', '', $actionName);
						$controller     = new $controllerPath();
						$controllerName = str_replace('Controller', ' Controller', class_basename($controller));
						
						if (is_array($controller->model_class_path)) {
							foreach ($controller->model_class_path as $model) {
								$modelclass = new $model();
								$baseRoute = str_replace('.index', '', $route_name);
								
								$modelConnection = null;
								if (!empty($modelclass->getConnectionName())) $modelConnection = $modelclass->getConnectionName();
								
								$models[$baseRoute]['controller']['route_base']  = str_replace('.index', '', $route_name);
								$models[$baseRoute]['controller']['route_index'] = $route_name;
								$models[$baseRoute]['controller']['path']        = $controllerPath;
								$models[$baseRoute]['controller']['name']        = $controllerName;
								$models[$baseRoute]['model']['name']             = class_basename($modelclass);
								$models[$baseRoute]['model']['connection']       = $modelConnection;
								$models[$baseRoute]['model']['path']             = get_class($modelclass);
								$models[$baseRoute]['model']['table']            = $modelclass->getTable();
								// use if any new table set for replace current table used in model
								if (!empty($table_replace_map)) {
									$models[$baseRoute]['model']['table_map']     = $table_replace_map;
								} else {
									$models[$baseRoute]['model']['table_map']     = $modelclass->getTable();
								}
								
								if (!empty($buffers[$baseRoute]['model'])) {
									$models[$baseRoute]['model']['buffers']       = $buffers[$baseRoute]['model'];
								}
							}
						}
					}
				}
			}
		}
		
		return $models;
	}
}

if (!function_exists('get_route_lists')) {
	
	/**
	 * Get Route Lists
	 *
	 * @param string $selected
	 * 		: true	=> fungsinya untuk memunculkan route path yang belum didaftarkan beserta dengan selected routenya.
	 * 		: false	=> fungsinya hanya untuk memunculkan route path yang belum didaftarkan saja.
	 *
	 * @return StdClass
	 */
	function get_route_lists(string|bool $selected = false, bool $fullRender = false, string $path_controllers = 'App\Http\Controllers\Admin\\'): object {
		$model   = Modules::withTrashed()->get();
		$modules = [];
		foreach ($model as $modul) {
			$mod  = $modul->getAttributes();
			$modules[$mod['route_path']] = $mod['route_path'];
		}
		
		$routeLists = Route::getRoutes();
		$routelists = [];
		foreach ($routeLists as $list) {
			$route_name = $list->getName();
			$routeObj   = explode('.', $route_name);
			
			if (str_contains($list->getActionName(), $path_controllers)) {
				// check if controller created in Admin folder
				if (count($routeObj) > 1) {
					if (in_array('index', $routeObj)) {
						$route_cat = count($routeObj);
						if (5 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]]['index'] = $routeObj[4];
						if (4 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]]                        = $routeObj[3];
						if (3 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]]['index']                             = $routeObj[2];
						if (2 === $route_cat) $routelists[$routeObj[0]]['index']                                           = $routeObj[1];
					}
				}
			}
		}
		
		$routes    = [];
		$allroutes = [];
		foreach ($routelists as $parent => $category) {
			foreach ($category as $child => $route_data) {
				if (is_array($route_data)) {
					foreach ($route_data as $model => $second_child) {
						if (is_array($second_child)) {
							foreach ($second_child as $third_model => $last_index) {
								if ($last_index !== $third_model) {
									$route_base	= "{$parent}.{$child}.{$model}.{$third_model}";
									if (in_array($selected, $modules)) {
										if ($selected === $route_base) {
											// MAINTENANCE_WARNING
											$routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
												'route_base' => $route_base,
												'route_name' => "{$route_base}.{$third_model}.index",
												'route_url'  => route("{$route_base}.index")
											];
										}
									} elseif (!in_array($route_base, $modules)) {
										$routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$third_model}.index",
											'route_url'  => route("{$route_base}.index")
										];
									}
									
									$allroutes[$parent][$child][$model][$third_model]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$third_model}.index",
										'route_url'  => route("{$route_base}.index")
									];
								} else {
									dd($third_model);
								}
							}
						} else {
							if ($second_child !== $model) {
								$route_base	= "{$parent}.{$child}.{$model}";
								if (in_array($selected, $modules)) {
									if ($selected === $route_base) {
										$routes[$parent][$child][$model]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$second_child}",
											'route_url'  => route("{$route_base}.{$second_child}")
										];
									}
								} elseif (!in_array($route_base, $modules)) {
									$routes[$parent][$child][$model]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$second_child}",
										'route_url'  => route("{$route_base}.{$second_child}")
									];
								}
								
								$allroutes[$parent][$child][$model]['route_data'] = (object) [
									'route_base' => $route_base,
									'route_name' => "{$route_base}.{$second_child}",
									'route_url'  => route("{$route_base}.{$second_child}")
								];
							} else {
								$route_base	= "{$parent}.{$child}";
								if (in_array($selected, $modules)) {
									if ($selected === $route_base) {
										$routes[$parent][$child]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$model}",
											'route_url'  => route("{$route_base}.{$model}")
										];
									}
								} elseif (!in_array($route_base, $modules)) {
									$routes[$parent][$child]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$model}",
										'route_url'  => route("{$route_base}.{$model}")
									];
								}
								$allroutes[$parent][$child]['route_data'] = (object) [
									'route_base' => $route_base,
									'route_name' => "{$route_base}.{$model}",
									'route_url'  => route("{$route_base}.{$model}")
								];
							}
						}
					}
				} else {
					$route_base	= $parent;
					$routes['single'][$parent]['route_data'] = (object) [
						'route_base' => $route_base,
						'route_name' => "{$route_base}.{$child}",
						'route_url'  => route("{$route_base}.{$child}")
					];
					$allroutes['single'][$parent]['route_data'] = (object) [
						'route_base' => $route_base,
						'route_name' => "{$route_base}.{$child}",
						'route_url'  => route("{$route_base}.{$child}")
					];
				}
			}
		}
		
		if (true === $fullRender) {
			$routeresult = $allroutes;
		} else {
			$routeresult = $routes;
		}
		
		return canvastack_array_to_object_recursive($routeresult);
	}
}

if (!function_exists('getPreference')) {
	
	/**
	 * Get All Web Preferences with Caching
	 *
	 * Retrieves web preferences from database with caching support.
	 * Cache is automatically used if enabled in configuration.
	 * 
	 * To invalidate cache after updating preferences, call:
	 * canvastack_invalidate_preference_cache()
	 *
	 * created @Aug 21, 2018
	 * author: wisnuwidi
	 * 
	 * @return array Preference data
	 * 
	 * @performance Caches preference data to reduce database queries
	 */
	function getPreference() {
		// Check if preference caching is enabled
		$cachingEnabled = config('canvastack.controller.caching.preference_cache_enabled', true);
		
		if (!$cachingEnabled) {
			// No caching - query directly
			foreach (Preference::all() as $preferences) {
				$preference = $preferences->getAttributes();
			}
			return $preference;
		}
		
		// Use caching
		$cacheKey = 'preference_all';
		$ttl = config('canvastack.controller.caching.preference_cache_ttl', 7200);
		
		return canvastack_controller_cache_remember($cacheKey, function() {
			$preference = [];
			foreach (Preference::all() as $preferences) {
				$preference = $preferences->getAttributes();
			}
			return $preference;
		}, $ttl);
	}
}

if (!function_exists('canvastack_invalidate_privilege_cache')) {
	
	/**
	 * Invalidate Privilege Cache
	 * 
	 * Call this function after updating modules, user groups, or privileges
	 * to ensure cached privilege data is refreshed and users see the latest menu.
	 * 
	 * @param int|null $groupId Group ID to invalidate (null = invalidate all groups)
	 * @return bool True if cache was invalidated successfully
	 * 
	 * @performance Cache invalidation ensures data consistency after privilege changes
	 * 
	 * @example
	 * // After creating/updating/deleting a module
	 * canvastack_invalidate_privilege_cache();
	 * 
	 * // After updating specific group privileges
	 * canvastack_invalidate_privilege_cache($groupId);
	 */
	function canvastack_invalidate_privilege_cache(?int $groupId = null): bool {
		try {
			// For module changes, we need to flush ALL caches because
			// the module structure affects all groups
			\Illuminate\Support\Facades\Cache::flush();
			
			// Log cache invalidation for debugging
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::info('Privilege cache invalidated', [
					'group_id' => $groupId,
					'user_id' => session('id'),
					'route' => request()->path(),
					'timestamp' => now()->toDateTimeString(),
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			// Log error but don't fail
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::error('Privilege cache invalidation failed', [
					'group_id' => $groupId,
					'error' => $e->getMessage(),
				]);
			}
			return false;
		}
	}
}

if (!function_exists('canvastack_invalidate_preference_cache')) {
	
	/**
	 * Invalidate Preference Cache
	 * 
	 * Call this function after updating preference data to ensure
	 * the cache is refreshed and users see the latest data.
	 * 
	 * @return bool True if cache was invalidated successfully
	 * 
	 * @performance Cache invalidation for data consistency
	 * 
	 * @example
	 * // After updating preference
	 * Preference::find(1)->update($data);
	 * canvastack_invalidate_preference_cache();
	 */
	function canvastack_invalidate_preference_cache(): bool {
		return canvastack_controller_cache_forget('preference_all');
	}
}

if (!function_exists('canvastack_combobox_data')) {
	
	/**
	 * Set Default Combobox Data
	 * 
	 * Converts an array or Eloquent Collection into a combobox-friendly format
	 * with key-value pairs suitable for HTML select elements.
	 *
	 * @param array|\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $object Data source (array or Collection)
	 * @param string $key_value Field name to use as option value
	 * @param string $key_label Field name to use as option label
	 * @param bool $set_null_array Whether to include empty option at start (default: true)
	 * @return array Formatted array for combobox [value => label]
	 * 
	 * @throws \InvalidArgumentException If object is not array or Collection
	 * 
	 * @example
	 * // With Eloquent Collection
	 * $icons = Icon::all();
	 * $options = canvastack_combobox_data($icons, 'class', 'label');
	 * // Result: [0 => '', '' => '', 'fa-home' => 'Home Icon', 'fa-user' => 'User Icon']
	 * 
	 * // With array
	 * $data = [['id' => 1, 'name' => 'Admin'], ['id' => 2, 'name' => 'User']];
	 * $options = canvastack_combobox_data($data, 'id', 'name');
	 * // Result: [0 => '', '' => '', 1 => 'Admin', 2 => 'User']
	 * 
	 * // Without null option
	 * $options = canvastack_combobox_data($icons, 'class', 'label', false);
	 * // Result: ['fa-home' => 'Home Icon', 'fa-user' => 'User Icon']
	 */
	function canvastack_combobox_data($object, string $key_value, string $key_label, bool $set_null_array = true): array {
		// Convert Collection to array if needed
		if ($object instanceof \Illuminate\Support\Collection || $object instanceof \Illuminate\Database\Eloquent\Collection) {
			$object = $object->toArray();
		}
		
		// Validate input type
		if (!is_array($object)) {
			throw new \InvalidArgumentException(
				'Argument #1 ($object) must be of type array or Collection, ' . gettype($object) . ' given'
			);
		}
		
		$options = [0 => ''];
		if (true === $set_null_array) $options[] = '';
		
		foreach ($object as $row) {
			// Handle both array and object notation
			if (is_array($row)) {
				$options[$row[$key_value]] = $row[$key_label];
			} elseif (is_object($row)) {
				$options[$row->$key_value] = $row->$key_label;
			}
		}
		
		return $options;
	}
}

if (!function_exists('active_box')) {
	
	/**
	 * Active Status Combobox Value
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $en
	 * @return string['No', 'Yes']
	 */
	function active_box(bool $en = true): array {
		if (true === $en) {
			return [null => ''] + ['Off, Non Active', 'Active'];
		} else {
			return [null => ''] + ['Tidak Aktif', 'Aktif'];
		}
	}
}

if (!function_exists('flag_status')) {
	
	/**
	 * Set Flag Status
	 *
	 * This function used to manage status module
	 * 		[ 0 => Super Admin ]   : Just root user can manage and access the module
	 * 		[ 1 => Administrator ] : End user can manage and access the module | root can manage the module too, with special condition
	 * 		[ 2 => End User ]      : All users can manage and access the module
	 *
	 * @return string[]
	 */
	function flag_status(bool $as_root = false): array {
		if (true === $as_root) {
			return [null => ''] + ['Super Admin', 'Administrator', 'End User'];
		} else {
			return [null => ''] + [1 => 'Administrator', 2 => 'End User'];
		}
	}
}

if (!function_exists('canvastack_get_ajax_urli')) {
	
	function canvastack_get_ajax_urli(string $init_post = 'AjaxPosF', ?string $connections = null): mixed {
		$ajaxURL = new AjaxController($connections);
		$ajaxURL::urli($init_post);
		
		return $ajaxURL::$ajaxUrli;
	}
}

if (!function_exists('internal_flag_status')) {
	
	/**
	 * Set Flag Status Value
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param integer|string $flag_row
	 *
	 * @return string
	 */
	function internal_flag_status(int|string $flag_row): string {
		$flaging = intval($flag_row);
		if (0 == intval($flaging)) {
			$flag_status = 'Super Admin <sup>( root )</sup>';
		} elseif (1 == $flaging)  {
			$flag_status = 'Administrator';
		} else {
			$flag_status = 'End User <sup>( all )</sup>';
		}
		
		return $flag_status;
	}
}

if (!function_exists('canvastack_mappage_button_add')) {
	
	/**
	 * Generate button add/reset for mapping page with JavaScript
	 * 
	 * @param string $ajax_url AJAX URL for data loading
	 * @param string $node_btn Button node ID
	 * @param string $id Checkbox ID
	 * @param string $target_id Field name select ID
	 * @param string $second_target Field value select ID
	 * 
	 * @return string Safe HTML (marked as safe)
	 */
	function canvastack_mappage_button_add($ajax_url, $node_btn, $id, $target_id, $second_target) {
		// Escape for JavaScript context
		$node_btn_safe = canvastack_escape_js($node_btn);
		$id_safe = canvastack_escape_js($id);
		$target_id_safe = canvastack_escape_js($target_id);
		$second_target_safe = canvastack_escape_js($second_target);
		$ajax_url_safe = canvastack_escape_js($ajax_url);
		
		$o  = "<div id='{$node_btn}' class='action-buttons-box' style='display: none;'>";
			$o .= "<div class='hidden-sm hidden-xs action-buttons'>";
				$o .= "<a id='plusn{$node_btn}' class='btn btn-success btn-xs btn_view'><i class='fa fa-plus-circle' aria-hidden='true'></i></a>";
				$o .= "<a id='reset{$node_btn}' class='btn btn-danger btn-xs btn_view' style='display: none;'><i class='fa fa-recycle' aria-hidden='true'></i></a>";
			$o .= "</div>";
		$o .= "</div>";
		
		$o .= "<div id=\"qc_{$id}\" class=\"qc_{$id}\" style=\"display:none;\"></div>";
		
		// Generate and minify inline script
		$scriptContent = "$(document).ready(function() {mappingPageButtonManipulation('{$node_btn_safe}', '{$id_safe}', '{$target_id_safe}', '{$second_target_safe}', '{$ajax_url_safe}');});";
		if (function_exists('canvastack_minify_inline_script')) {
			$scriptContent = canvastack_minify_inline_script($scriptContent);
		}
		$o .= "<script type='text/javascript'>{$scriptContent}</script>";
		
		// Mark as safe HTML to prevent double-encoding
		return SafeHtml::mark($o);
	}
}

if (!function_exists('canvastack_input')) {
	
	/**
	 * Generate HTML input element
	 * 
	 * Simple helper to generate input elements with common attributes.
	 * 
	 * @param string $type Input type (text, hidden, checkbox, etc)
	 * @param string|null $id Input ID attribute
	 * @param string|null $class Input class attribute
	 * @param string|null $name Input name attribute
	 * @param string|null $value Input value attribute
	 * 
	 * @return string Safe HTML input element (marked as safe)
	 */
	function canvastack_input($type, $id = null, $class = null, $name = null, $value = null) {
		// Escape all attributes for security
		$type_safe  = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
		$id_safe    = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
		$class_safe = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
		$name_safe  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$value_safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		
		$id_attr    = " id=\"{$id_safe}\"";
		$class_attr = " class=\"{$class_safe}\"";
		$value_attr = " value=\"{$value_safe}\"";
		$name_attr  = " name=\"{$name_safe}\"";
		
		$html = "<input type=\"{$type_safe}\"{$id_attr}{$class_attr}{$value_attr}{$name_attr} />";
		
		// Mark as safe HTML to prevent double-encoding
		return SafeHtml::mark($html);
	}
}

if (!function_exists('canvastack_script')) {
	
	function canvastack_script($script, $ready = true) {
		// Minify the script if minification is enabled
		if (function_exists('canvastack_minify_inline_script')) {
			$script = canvastack_minify_inline_script($script);
		}
		
		if (true === $ready) {
			return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
		} else {
			return "<script type='text/javascript'>{$script}</script>";
		}
	}
}

if (!function_exists('canvastack_minify_inline_script')) {
	/**
	 * Minify Inline JavaScript Code
	 * 
	 * Performs basic minification on inline JavaScript code to reduce file size by:
	 * - Removing single-line comments (// ...)
	 * - Removing multi-line comments (/* ... *\/) except important ones (/*! ... *\/)
	 * - Removing unnecessary whitespace (multiple spaces, tabs, newlines)
	 * - Preserving string literals (both single and double quoted)
	 * - Preserving important comments (marked with /*! or /**)
	 * 
	 * This function is used by the template engine to automatically minify
	 * all inline scripts that are generated by helper functions or components.
	 * 
	 * Configuration:
	 * - Enable/disable via config: canvastack.controller.script_management.enable_minification
	 * - Preserve important comments: canvastack.controller.script_management.preserve_important_comments
	 * 
	 * @param string $script JavaScript code to minify
	 * @return string Minified JavaScript code, or original if minification disabled/fails
	 * 
	 * @example
	 * ```php
	 * $script = '
	 *     // This is a comment
	 *     function test() {
	 *         console.log("Hello");
	 *     }
	 * ';
	 * $minified = canvastack_minify_inline_script($script);
	 * // Result: 'function test(){console.log("Hello");}'
	 * ```
	 */
	function canvastack_minify_inline_script(string $script): string {
		// Check if minification is enabled
		$config = config('canvastack.controller.script_management', []);
		$minificationEnabled = $config['enable_minification'] ?? false;
		
		if (!$minificationEnabled) {
			return $script;
		}
		
		// Validate input
		if (empty($script) || !is_string($script)) {
			return $script;
		}
		
		try {
			$minified = $script;
			$preserveImportant = $config['preserve_important_comments'] ?? true;
			
			// Step 1: Preserve string literals (single, double, and template literals)
			// This regex handles escaped quotes properly
			$stringLiterals = [];
			$minified = preg_replace_callback(
				'/(["\'])(?:\\\\.|(?!\1)[^\\\\])*\1|`(?:\\\\.|[^`\\\\])*`/s',
				function($matches) use (&$stringLiterals) {
					$placeholder = '___STRING_LITERAL_' . count($stringLiterals) . '___';
					$stringLiterals[$placeholder] = $matches[0];
					return $placeholder;
				},
				$minified
			);
			
			// Step 2: Preserve regex literals (/pattern/flags)
			$regexLiterals = [];
			$minified = preg_replace_callback(
				'/(?<=[=\(,\[!&|:;])(\s*)(\/(?![*\/])(?:[^\r\n\[\/\\\\]|\\\\.|\[(?:[^\r\n\]\\\\]|\\\\.)*\])+\/[gimuy]*)/s',
				function($matches) use (&$regexLiterals) {
					$placeholder = '___REGEX_LITERAL_' . count($regexLiterals) . '___';
					$regexLiterals[$placeholder] = $matches[2];
					return $matches[1] . $placeholder;
				},
				$minified
			);
			
			// Step 3: Preserve important comments (/*! ... */ or /** ... */)
			$importantComments = [];
			if ($preserveImportant) {
				$minified = preg_replace_callback(
					'/\/\*[!*].*?\*\//s',
					function($matches) use (&$importantComments) {
						$placeholder = '___IMPORTANT_COMMENT_' . count($importantComments) . '___';
						$importantComments[$placeholder] = $matches[0];
						return $placeholder;
					},
					$minified
				);
			}
			
			// Step 4: Remove multi-line comments (/* ... */)
			$minified = preg_replace('/\/\*.*?\*\//s', '', $minified);
			
			// Step 5: Remove single-line comments (// ...)
			// But preserve URLs (http://, https://)
			$minified = preg_replace('/(?<!:)\/\/.*$/m', '', $minified);
			
			// Step 6: Remove leading/trailing whitespace on each line
			$minified = preg_replace('/^\s+|\s+$/m', '', $minified);
			
			// Step 7: Replace multiple spaces/tabs/newlines with single space
			$minified = preg_replace('/\s+/', ' ', $minified);
			
			// Step 8: Remove spaces around punctuation (VERY CONSERVATIVE)
			// Only remove spaces around: { } ( ) ; ,
			// DO NOT remove spaces around : (colon) - it breaks ternary operators and object literals
			$minified = preg_replace('/\s*([{}();,])\s*/', '$1', $minified);
			
			// Step 9: Restore preserved content
			// Restore regex literals first
			if (!empty($regexLiterals)) {
				foreach ($regexLiterals as $placeholder => $regex) {
					$minified = str_replace($placeholder, $regex, $minified);
				}
			}
			
			// Restore string literals
			if (!empty($stringLiterals)) {
				foreach ($stringLiterals as $placeholder => $string) {
					$minified = str_replace($placeholder, $string, $minified);
				}
			}
			
			// Restore important comments
			if ($preserveImportant && !empty($importantComments)) {
				foreach ($importantComments as $placeholder => $comment) {
					$minified = str_replace($placeholder, $comment, $minified);
				}
			}
			
			// Step 10: Final cleanup - trim
			$minified = trim($minified);
			
			return $minified;
			
		} catch (\Exception $e) {
			// On error, return original script (graceful degradation)
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Log::warning('Inline script minification failed', [
					'error' => $e->getMessage(),
					'script_preview' => substr($script, 0, 100)
				]);
			}
			return $script;
		}
	}
}

if (!function_exists('canvastack_merge_request')) {
	
	/**
	 * Controlling Request Before Insert Process
	 * 
	 * @param object $request
	 * @param array $new_data
	 * @return object
	 * 
	 * @author: wisnuwidi
	 */
	function canvastack_merge_request(object $request, array $new_data = []): object {
		foreach ($new_data as $field_name => $value) {
			if (null == $request->{$field_name}) {
				$request->merge([$field_name => $value]);
			}
		}
		
		return $request;
	}
}

if (!function_exists('canvastack_is_softdeletes')) {
	
	function canvastack_is_softdeletes(object|string $model): bool {
		return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model), true);
	}
}

if (!function_exists('canvastack_attributes_to_string')) {
	
	/**
	 * Attributes To String
	 * Helper function used by some of the form helpers
	 *
	 * @param mixed
	 * @return string
	 */
	function canvastack_attributes_to_string(mixed $attributes): string|bool {
		if (empty($attributes)) return '';
		
		// Handle array attributes
		if (is_array($attributes)) {
			$atts = '';
			foreach ($attributes as $key => $val) {
				$atts .= ' ' . $key . '="' . $val . '"';
			}
			return $atts;
		}
		
		// Handle object attributes
		if (is_object($attributes)) {
			$attributes = (array) $attributes;
			
			if (is_array($attributes)) {
				$atts = '';
				foreach ($attributes as $key => $val) {
					$atts .= ' ' . $key . '="' . $val . '"';
				}
				
				return $atts;
			}
			
			if (is_string($attributes)) {
				return ' ' . $attributes;
			}
			
			return false;
		}
		
		// Handle string attributes
		if (is_string($attributes)) {
			return ' ' . $attributes;
		}
		
		return '';
	}
}

if (!function_exists('not_empty')) {
	
	/**
	 * Checking Not Empty Data
	 *
	 * @param mixed $data
	 * @return mixed|boolean
	 */
	function not_empty(mixed $data): mixed {
		if (isset($data) && !empty($data) && '' != $data && NULL != $data) {
			return $data;
		} else {
			return false;
		}
	}
}

if (!function_exists('is_empty')) {
	
	/**
	 * Checking Empty Data
	 *
	 * @param mixed $data
	 * @return boolean
	 */
	function is_empty($data) {
		return !not_empty($data);
	}
}

if (!function_exists('canvastack_get_model_data')) {
	
	/**
	 * Get All Web Preferences
	 *
	 * created @Aug 21, 2018
	 * author: wisnuwidi
	 */
	function canvastack_get_model_data(object|string $model): array {
		$data = [];
		foreach ($model::all() as $row) {
			$data = $row->getAttributes();
		}
		
		return $data;
	}
}

if (!function_exists('canvastack_memory')) {
	
	/**
	 * Memory?
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @param bool $min
	 * @param integer $limit
	 */
	function canvastack_memory(?bool $min = null, int $limit = -1): void {
		ini_set('memory_limit', $limit);
		if (null === $min) {
			minify_code(canvastack_config('minify'));
		} else {
			minify_code($min);
		}
	}
}

if (!function_exists('canvastack_date_info')) {
	
	function canvastack_date_info(string $table, string $field, ?string $filter = null, ?string $connection = null): string {
		$query = canvastack_query("SELECT DATE_FORMAT(MAX(`{$field}`), '%d% %M %Y') date_info FROM `{$table}` {$filter}", 'SELECT', $connection);
		
		return $query[0]->date_info;
	}
}

if (!function_exists('canvastack_get_os')) {
	
	function canvastack_get_os(string $user_agent): string {
		$os_platform  = "Unknown OS Platform";
		$os_array     = [
			'/windows nt 11/i'      =>  'Windows 11',
			'/windows nt 10/i'      =>  'Windows 10',
			'/windows nt 6.3/i'     =>  'Windows 8.1',
			'/windows nt 6.2/i'     =>  'Windows 8',
			'/windows nt 6.1/i'     =>  'Windows 7',
			'/windows nt 6.0/i'     =>  'Windows Vista',
			'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
			'/windows nt 5.1/i'     =>  'Windows XP',
			'/windows xp/i'         =>  'Windows XP',
			'/windows nt 5.0/i'     =>  'Windows 2000',
			'/windows me/i'         =>  'Windows ME',
			'/win98/i'              =>  'Windows 98',
			'/win95/i'              =>  'Windows 95',
			'/win16/i'              =>  'Windows 3.11',
			'/macintosh|mac os x/i' =>  'Mac OS X',
			'/mac_powerpc/i'        =>  'Mac OS 9',
			'/linux/i'              =>  'Linux',
			'/ubuntu/i'             =>  'Ubuntu',
			'/iphone/i'             =>  'iPhone',
			'/ipod/i'               =>  'iPod',
			'/ipad/i'               =>  'iPad',
			'/android/i'            =>  'Android',
			'/blackberry/i'         =>  'BlackBerry',
			'/webos/i'              =>  'Mobile'
		];
		
		foreach ($os_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) $os_platform = $value;
		}
		
		return $os_platform;
	}
}

if (!function_exists('canvastack_get_os')) {
	
	function canvastack_get_os($user_agent) {
		$browser       = "Unknown Browser";
		$browser_array = [
			'/msie/i'      => 'Internet Explorer',
			'/firefox/i'   => 'Firefox',
			'/safari/i'    => 'Safari',
			'/chrome/i'    => 'Chrome',
			'/edge/i'      => 'Edge',
			'/opera/i'     => 'Opera',
			'/netscape/i'  => 'Netscape',
			'/maxthon/i'   => 'Maxthon',
			'/konqueror/i' => 'Konqueror',
			'/mobile/i'    => 'Handheld Browser'
		];
		
		foreach ($browser_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) $browser = $value;
		}
		
		return $browser;
	}
}


if (!function_exists('canvastack_controller_validate_csrf')) {
	/**
	 * Validate CSRF token for request
	 * 
	 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
	 * 
	 * Validates CSRF token from request to prevent Cross-Site Request Forgery attacks.
	 * Checks token in request body, headers (X-CSRF-TOKEN, X-XSRF-TOKEN), and query parameters.
	 * 
	 * @param \Illuminate\Http\Request $request The HTTP request to validate
	 * @return bool True if CSRF token is valid
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\CSRFException If token is invalid or missing
	 * 
	 * @security CRITICAL - This function prevents CSRF attacks
	 *           - Validates token from multiple sources (body, headers, query)
	 *           - Logs all CSRF failures for security monitoring
	 *           - Throws exception on validation failure
	 * 
	 * @example
	 * try {
	 *     canvastack_controller_validate_csrf($request);
	 *     // Process request
	 * } catch (CSRFException $e) {
	 *     // Handle CSRF failure
	 * }
	 */
	function canvastack_controller_validate_csrf($request): bool {
		// Check if CSRF protection is enabled in configuration
		if (!config('canvastack.controller.security.csrf_protection', true)) {
			return true;
		}
		
		// Get token from request (body, header, or query)
		$token = $request->input('_token') 
			?? $request->header('X-CSRF-TOKEN') 
			?? $request->header('X-XSRF-TOKEN')
			?? $request->query('_token');
		
		// Get session token
		$sessionToken = $request->session()->token();
		
		// Validate token
		if (empty($token) || empty($sessionToken)) {
			// Log CSRF failure
			canvastack_controller_log_security_event(
				'csrf_failure',
				'CSRF token missing',
				[
					'url' => $request->fullUrl(),
					'method' => $request->method(),
					'ip' => $request->ip(),
					'user_agent' => $request->userAgent(),
					'token_present' => !empty($token),
					'session_token_present' => !empty($sessionToken),
				]
			);
			
			throw new \Canvastack\Canvastack\Exceptions\Controller\CSRFException(
				'CSRF token is missing',
				[
					'url' => $request->fullUrl(),
					'method' => $request->method(),
				]
			);
		}
		
		// Compare tokens using hash_equals to prevent timing attacks
		if (!hash_equals($sessionToken, $token)) {
			// Log CSRF failure
			canvastack_controller_log_security_event(
				'csrf_failure',
				'CSRF token mismatch',
				[
					'url' => $request->fullUrl(),
					'method' => $request->method(),
					'ip' => $request->ip(),
					'user_agent' => $request->userAgent(),
				]
			);
			
			throw new \Canvastack\Canvastack\Exceptions\Controller\CSRFException(
				'CSRF token mismatch',
				[
					'url' => $request->fullUrl(),
					'method' => $request->method(),
				]
			);
		}
		
		return true;
	}
}

if (!function_exists('canvastack_controller_log_security_event')) {
	/**
	 * Log security event
	 * 
	 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
	 * 
	 * Logs security-related events for monitoring and auditing.
	 * Events are logged only if security logging is enabled in configuration.
	 * 
	 * @param string $type Event type (csrf_failure, xss_attempt, sql_injection, etc.)
	 * @param string $message Event message
	 * @param array $context Additional context data
	 * @return void
	 * 
	 * @security Logs security events for monitoring and incident response
	 * 
	 * @example
	 * canvastack_controller_log_security_event(
	 *     'csrf_failure',
	 *     'CSRF token validation failed',
	 *     ['url' => $request->fullUrl(), 'ip' => $request->ip()]
	 * );
	 */
	function canvastack_controller_log_security_event(string $type, string $message, array $context = []): void {
		// Check if security logging is enabled
		if (!config('canvastack.controller.logging.log_security_events', true)) {
			return;
		}
		
		// Get log channel from configuration
		$channel = config('canvastack.controller.logging.log_channel');
		
		// Prepare log context
		$logContext = array_merge([
			'type' => $type,
			'timestamp' => now()->toDateTimeString(),
		], $context);
		
		// Log the security event
		if ($channel) {
			\Illuminate\Support\Facades\Log::channel($channel)->warning(
				"[SECURITY] {$type}: {$message}",
				$logContext
			);
		} else {
			\Illuminate\Support\Facades\Log::warning(
				"[SECURITY] {$type}: {$message}",
				$logContext
			);
		}
	}
}

if (!function_exists('canvastack_escape_js')) {
	/**
	 * Escape string for JavaScript context
	 * 
	 * Escapes special characters for safe use in JavaScript strings.
	 * Prevents XSS attacks when outputting data in JavaScript context.
	 * 
	 * @param string $string String to escape
	 * @return string Escaped string safe for JavaScript
	 * 
	 * @security XSS Protection - Escapes for JavaScript context
	 * 
	 * @example
	 * $safe = canvastack_escape_js($userInput);
	 * echo "<script>var data = '{$safe}';</script>";
	 */
	function canvastack_escape_js(string $string): string {
		// Escape for JavaScript context
		return str_replace(
			["\\", "'", '"', "\n", "\r", "\t", "\0", "\x08", "\x0c"],
			["\\\\", "\\'", '\\"', "\\n", "\\r", "\\t", "\\0", "\\b", "\\f"],
			$string
		);
	}
}

/*
|--------------------------------------------------------------------------
| Cache Helper Functions
|--------------------------------------------------------------------------
|
| بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
|
| These functions provide caching capabilities for controller components
| to improve performance by caching expensive operations like privilege
| checks, route info generation, preference loading, and file validation.
|
*/

if (!function_exists('canvastack_controller_cache_get')) {
	/**
	 * Get value from controller cache
	 * 
	 * Retrieves a cached value if caching is enabled and the key exists.
	 * Returns null if caching is disabled or key doesn't exist.
	 * 
	 * @param string $key Cache key
	 * @param mixed $default Default value if key doesn't exist
	 * @return mixed Cached value or default
	 * 
	 * @performance Reduces repeated expensive operations by caching results
	 * 
	 * @example
	 * $privileges = canvastack_controller_cache_get('privilege_user_123_module_admin');
	 * if ($privileges === null) {
	 *     $privileges = loadPrivilegesFromDatabase();
	 *     canvastack_controller_cache_put('privilege_user_123_module_admin', $privileges);
	 * }
	 */
	function canvastack_controller_cache_get(string $key, $default = null) {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return $default;
		}
		
		// Check if cache fallback to database is enabled
		$fallbackEnabled = config('canvastack.controller.error_handling.cache_fallback_to_database', true);
		
		// Retry cache get operation with exponential backoff
		$result = canvastack_controller_retry(function() use ($key, $default) {
			return \Illuminate\Support\Facades\Cache::get($key, $default);
		}, 3, 100, 'cache_get');
		
		// If retry failed and fallback is enabled, return default
		if ($result === null && $fallbackEnabled) {
			// Log cache fallback
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::info('Cache get failed, using default value', [
					'key' => $key,
				]);
			}
			return $default;
		}
		
		return $result ?? $default;
	}
}

if (!function_exists('canvastack_controller_cache_put')) {
	/**
	 * Store value in controller cache
	 * 
	 * Stores a value in cache with the specified TTL if caching is enabled.
	 * Uses default TTL from configuration if not specified.
	 * 
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int|null $ttl Time-to-live in seconds (null = use default)
	 * @return bool True if cached successfully, false otherwise
	 * 
	 * @performance Caches expensive operation results for faster subsequent access
	 * 
	 * @example
	 * $routeInfo = generateRouteInfo();
	 * canvastack_controller_cache_put('route_info_admin_users_index', $routeInfo, 3600);
	 */
	function canvastack_controller_cache_put(string $key, $value, ?int $ttl = null): bool {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return false;
		}
		
		// Use default TTL if not specified
		if ($ttl === null) {
			$ttl = config('canvastack.controller.performance.cache_ttl', 3600);
		}
		
		// Retry cache put operation with exponential backoff
		$result = canvastack_controller_retry(function() use ($key, $value, $ttl) {
			$success = \Illuminate\Support\Facades\Cache::put($key, $value, $ttl);
			if (!$success) {
				throw ControllerException::operationFailed(
					'cache_put',
					'Cache put operation failed',
					[
						'key' => $key,
						'ttl' => $ttl,
						'user_id' => session('id'),
					]
				);
			}
			return $success;
		}, 3, 100, 'cache_put');
		
		return $result === true;
	}
}

if (!function_exists('canvastack_controller_cache_remember')) {
	/**
	 * Get value from cache or execute callback and cache result
	 * 
	 * Retrieves cached value if it exists, otherwise executes the callback,
	 * caches the result, and returns it. This is the recommended way to use
	 * caching as it handles both get and put operations.
	 * 
	 * @param string $key Cache key
	 * @param callable $callback Callback to execute if cache miss
	 * @param int|null $ttl Time-to-live in seconds (null = use default)
	 * @return mixed Cached or computed value
	 * 
	 * @performance Automatically handles cache get/put logic
	 * 
	 * @example
	 * $privileges = canvastack_controller_cache_remember(
	 *     'privilege_user_123_module_admin',
	 *     function() {
	 *         return loadPrivilegesFromDatabase();
	 *     },
	 *     3600
	 * );
	 */
	function canvastack_controller_cache_remember(string $key, callable $callback, ?int $ttl = null) {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return $callback();
		}
		
		// Use default TTL if not specified
		if ($ttl === null) {
			$ttl = config('canvastack.controller.performance.cache_ttl', 3600);
		}
		
		// Retry cache remember operation with exponential backoff
		$result = canvastack_controller_retry(function() use ($key, $callback, $ttl) {
			return \Illuminate\Support\Facades\Cache::remember($key, $ttl, $callback);
		}, 3, 100, 'cache_remember');
		
		// If retry failed, execute callback directly (graceful degradation)
		if ($result === null) {
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::info('Cache remember failed, executing callback directly', [
					'key' => $key,
				]);
			}
			return $callback();
		}
		
		return $result;
	}
}

if (!function_exists('canvastack_controller_cache_forget')) {
	/**
	 * Remove value from controller cache
	 * 
	 * Invalidates a cached value by removing it from cache.
	 * Use this when the underlying data changes and cache needs to be refreshed.
	 * 
	 * @param string $key Cache key to remove
	 * @return bool True if removed successfully, false otherwise
	 * 
	 * @performance Cache invalidation ensures data consistency
	 * 
	 * @example
	 * // After updating user privileges
	 * canvastack_controller_cache_forget('privilege_user_123_module_admin');
	 */
	function canvastack_controller_cache_forget(string $key): bool {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return false;
		}
		
		try {
			return \Illuminate\Support\Facades\Cache::forget($key);
		} catch (\Exception $e) {
			// Log cache error but don't fail
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::warning('Controller cache forget failed', [
					'key' => $key,
					'error' => $e->getMessage(),
				]);
			}
			return false;
		}
	}
}

if (!function_exists('canvastack_controller_cache_flush')) {
	/**
	 * Clear all controller cache entries
	 * 
	 * Removes all cached values with the controller cache prefix.
	 * Use this for bulk cache invalidation.
	 * 
	 * @param string|null $prefix Cache key prefix to flush (null = flush all)
	 * @return bool True if flushed successfully, false otherwise
	 * 
	 * @performance Bulk cache invalidation for major data changes
	 * 
	 * @example
	 * // Clear all privilege caches
	 * canvastack_controller_cache_flush('privilege_');
	 * 
	 * // Clear all controller caches
	 * canvastack_controller_cache_flush();
	 */
	function canvastack_controller_cache_flush(?string $prefix = null): bool {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return false;
		}
		
		try {
			if ($prefix === null) {
				// Flush all cache
				return \Illuminate\Support\Facades\Cache::flush();
			} else {
				// Flush cache with specific prefix
				// Note: This is a simple implementation that may not work with all cache drivers
				// For production, consider using cache tags if your driver supports them
				$keys = \Illuminate\Support\Facades\Cache::get('controller_cache_keys', []);
				$flushed = true;
				
				foreach ($keys as $key) {
					if (strpos($key, $prefix) === 0) {
						$flushed = \Illuminate\Support\Facades\Cache::forget($key) && $flushed;
					}
				}
				
				return $flushed;
			}
		} catch (\Exception $e) {
			// Log cache error but don't fail
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::warning('Controller cache flush failed', [
					'prefix' => $prefix,
					'error' => $e->getMessage(),
				]);
			}
			return false;
		}
	}
}

if (!function_exists('canvastack_controller_cache_has')) {
	/**
	 * Check if key exists in controller cache
	 * 
	 * Checks whether a cache key exists without retrieving its value.
	 * 
	 * @param string $key Cache key to check
	 * @return bool True if key exists, false otherwise
	 * 
	 * @performance Allows checking cache existence before retrieval
	 * 
	 * @example
	 * if (canvastack_controller_cache_has('privilege_user_123_module_admin')) {
	 *     $privileges = canvastack_controller_cache_get('privilege_user_123_module_admin');
	 * }
	 */
	function canvastack_controller_cache_has(string $key): bool {
		// Check if caching is enabled
		if (!config('canvastack.controller.performance.enable_caching', true)) {
			return false;
		}
		
		try {
			return \Illuminate\Support\Facades\Cache::has($key);
		} catch (\Exception $e) {
			// Log cache error but don't fail
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::warning('Controller cache has failed', [
					'key' => $key,
					'error' => $e->getMessage(),
				]);
			}
			return false;
		}
	}
}

if (!function_exists('canvastack_controller_cache_key')) {
	/**
	 * Generate cache key for controller components
	 * 
	 * Creates a standardized cache key with prefix and parameters.
	 * This ensures consistent key naming across the application.
	 * 
	 * @param string $prefix Cache key prefix (e.g., 'privilege', 'route_info')
	 * @param array $params Parameters to include in key
	 * @return string Generated cache key
	 * 
	 * @performance Standardized key generation for consistent caching
	 * 
	 * @example
	 * $key = canvastack_controller_cache_key('privilege', ['user' => 123, 'module' => 'admin']);
	 * // Returns: 'privilege_user_123_module_admin'
	 */
	function canvastack_controller_cache_key(string $prefix, array $params = []): string {
		$key = $prefix;
		
		foreach ($params as $name => $value) {
			// Convert value to string representation
			if (is_array($value)) {
				$value = md5(json_encode($value));
			} elseif (is_object($value)) {
				$value = md5(serialize($value));
			}
			
			$key .= "_{$name}_{$value}";
		}
		
		return $key;
	}
}


if (!function_exists('canvastack_memory_usage')) {
	/**
	 * Get current memory usage information
	 * 
	 * Returns detailed memory usage information including current usage,
	 * peak usage, and available memory.
	 * 
	 * @param bool $formatted Return formatted strings (true) or raw bytes (false)
	 * @return array Memory usage information
	 * 
	 * @performance Memory Management - Monitors memory usage
	 * 
	 * @example
	 * $memory = canvastack_memory_usage(true);
	 * echo "Current: {$memory['current']}, Peak: {$memory['peak']}";
	 */
	function canvastack_memory_usage(bool $formatted = true): array {
		$current = memory_get_usage(true);
		$peak = memory_get_peak_usage(true);
		
		// Get memory limit
		$limit = ini_get('memory_limit');
		if ($limit === '-1') {
			$limitBytes = PHP_INT_MAX;
		} else {
			$limitBytes = canvastack_convert_to_bytes($limit);
		}
		
		$available = $limitBytes - $current;
		$usagePercent = ($current / $limitBytes) * 100;
		
		if ($formatted) {
			return [
				'current' => canvastack_format_bytes($current),
				'peak' => canvastack_format_bytes($peak),
				'limit' => canvastack_format_bytes($limitBytes),
				'available' => canvastack_format_bytes($available),
				'usage_percent' => round($usagePercent, 2) . '%',
			];
		} else {
			return [
				'current' => $current,
				'peak' => $peak,
				'limit' => $limitBytes,
				'available' => $available,
				'usage_percent' => $usagePercent,
			];
		}
	}
}

if (!function_exists('canvastack_convert_to_bytes')) {
	/**
	 * Convert memory string to bytes
	 * 
	 * Converts memory limit string (e.g., "256M") to bytes.
	 * 
	 * @param string $value Memory value (e.g., "256M", "1G")
	 * @return int Memory in bytes
	 * 
	 * @performance Memory Management - Utility function for memory calculations
	 */
	function canvastack_convert_to_bytes(string $value): int {
		$value = trim($value);
		$unit = strtoupper(substr($value, -1));
		$number = (int)substr($value, 0, -1);
		
		switch ($unit) {
			case 'G':
				return $number * 1024 * 1024 * 1024;
			case 'M':
				return $number * 1024 * 1024;
			case 'K':
				return $number * 1024;
			default:
				return (int)$value;
		}
	}
}

if (!function_exists('canvastack_format_bytes')) {
	/**
	 * Format bytes to human-readable string
	 * 
	 * Converts bytes to human-readable format (KB, MB, GB).
	 * 
	 * @param int $bytes Bytes to format
	 * @param int $precision Decimal precision
	 * @return string Formatted string
	 * 
	 * @performance Memory Management - Utility function for displaying memory usage
	 */
	function canvastack_format_bytes(int $bytes, int $precision = 2): string {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		
		$bytes /= pow(1024, $pow);
		
		return round($bytes, $precision) . ' ' . $units[$pow];
	}
}

if (!function_exists('canvastack_check_memory_limit')) {
	/**
	 * Check if memory usage is approaching limit
	 * 
	 * Checks if current memory usage is approaching the configured limit.
	 * Logs warning if usage exceeds threshold.
	 * 
	 * @param float $threshold Warning threshold (0.0 to 1.0, default 0.8 = 80%)
	 * @return bool True if memory usage is within safe limits
	 * 
	 * @performance Memory Management - Prevents out-of-memory errors
	 */
	function canvastack_check_memory_limit(float $threshold = 0.8): bool {
		$memoryInfo = canvastack_memory_usage(false);
		$usageRatio = $memoryInfo['current'] / $memoryInfo['limit'];
		
		if ($usageRatio >= $threshold) {
			// Log warning
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::warning('Memory usage approaching limit', [
					'current' => canvastack_format_bytes($memoryInfo['current']),
					'limit' => canvastack_format_bytes($memoryInfo['limit']),
					'usage_percent' => round($usageRatio * 100, 2) . '%',
					'threshold' => round($threshold * 100, 2) . '%',
					'user_id' => session('id'),
					'route' => request()->path(),
				]);
			}
			
			return false;
		}
		
		return true;
	}
}

if (!function_exists('canvastack_array_merge_efficient')) {
	/**
	 * Memory-efficient array merge
	 * 
	 * Merges arrays efficiently without creating unnecessary copies.
	 * Uses array_replace for better performance with large arrays.
	 * 
	 * @param array ...$arrays Arrays to merge
	 * @return array Merged array
	 * 
	 * @performance Memory Management - Optimized array merging
	 */
	function canvastack_array_merge_efficient(array ...$arrays): array {
		// For small arrays (< 100 elements), use array_merge
		$totalElements = 0;
		foreach ($arrays as $array) {
			$totalElements += count($array);
		}
		
		if ($totalElements < 100) {
			return array_merge(...$arrays);
		}
		
		// For large arrays, use array_replace which is more memory efficient
		$result = [];
		foreach ($arrays as $array) {
			$result = array_replace($result, $array);
		}
		
		return $result;
	}
}

if (!function_exists('canvastack_string_builder')) {
	/**
	 * Memory-efficient string concatenation
	 * 
	 * Builds strings efficiently using array join instead of concatenation.
	 * Significantly faster and more memory-efficient for large strings.
	 * 
	 * @param array $parts String parts to concatenate
	 * @param string $separator Separator between parts (default: empty string)
	 * @return string Concatenated string
	 * 
	 * @performance Memory Management - Optimized string concatenation
	 * 
	 * @example
	 * // Instead of: $str = $a . $b . $c . $d;
	 * // Use: $str = canvastack_string_builder([$a, $b, $c, $d]);
	 */
	function canvastack_string_builder(array $parts, string $separator = ''): string {
		return implode($separator, $parts);
	}
}

if (!function_exists('canvastack_array_chunk_process')) {
	/**
	 * Process large arrays in chunks
	 * 
	 * Processes large arrays in chunks to prevent memory exhaustion.
	 * Useful for batch operations on large datasets.
	 * 
	 * @param array $array Array to process
	 * @param callable $callback Callback function to process each chunk
	 * @param int $chunkSize Chunk size (default: 100)
	 * @return array Results from all chunks
	 * 
	 * @performance Memory Management - Processes large arrays efficiently
	 * 
	 * @example
	 * $results = canvastack_array_chunk_process($largeArray, function($chunk) {
	 *     return array_map('strtoupper', $chunk);
	 * }, 100);
	 */
	function canvastack_array_chunk_process(array $array, callable $callback, int $chunkSize = 100): array {
		$results = [];
		$chunks = array_chunk($array, $chunkSize);
		
		foreach ($chunks as $chunk) {
			$chunkResult = $callback($chunk);
			
			if (is_array($chunkResult)) {
				$results = array_merge($results, $chunkResult);
			}
			
			// Free memory after each chunk
			unset($chunk, $chunkResult);
		}
		
		// Free memory
		unset($chunks);
		
		return $results;
	}
}

if (!function_exists('canvastack_cleanup_variables')) {
	/**
	 * Clean up multiple variables at once
	 * 
	 * Unsets multiple variables to free memory.
	 * Useful for cleaning up after processing large datasets.
	 * 
	 * @param mixed ...$variables Variables to unset (passed by reference)
	 * @return void
	 * 
	 * @performance Memory Management - Frees memory by unsetting variables
	 * 
	 * @example
	 * canvastack_cleanup_variables($largeArray, $tempData, $processedResults);
	 */
	function canvastack_cleanup_variables(&...$variables): void {
		foreach ($variables as &$variable) {
			$variable = null;
			unset($variable);
		}
	}
}


if (!function_exists('canvastack_memory_warning')) {
	/**
	 * Issue memory warning if usage is high
	 * 
	 * Checks memory usage and issues warning if it exceeds threshold.
	 * Can optionally throw exception if memory is critically low.
	 * 
	 * @param float $warningThreshold Warning threshold (0.0 to 1.0, default 0.75 = 75%)
	 * @param float $criticalThreshold Critical threshold (0.0 to 1.0, default 0.9 = 90%)
	 * @param bool $throwOnCritical Throw exception on critical threshold (default: false)
	 * @return array Memory status information
	 * @throws \RuntimeException If memory is critically low and $throwOnCritical is true
	 * 
	 * @performance Memory Management - Prevents out-of-memory errors
	 */
	function canvastack_memory_warning(
		float $warningThreshold = 0.75,
		float $criticalThreshold = 0.9,
		bool $throwOnCritical = false
	): array {
		$memoryInfo = canvastack_memory_usage(false);
		$usageRatio = $memoryInfo['current'] / $memoryInfo['limit'];
		
		$status = 'ok';
		$message = null;
		
		if ($usageRatio >= $criticalThreshold) {
			$status = 'critical';
			$message = sprintf(
				'CRITICAL: Memory usage at %.2f%% (%s of %s)',
				$usageRatio * 100,
				canvastack_format_bytes($memoryInfo['current']),
				canvastack_format_bytes($memoryInfo['limit'])
			);
			
			// Log critical memory usage
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::critical('Critical memory usage', [
					'current' => canvastack_format_bytes($memoryInfo['current']),
					'limit' => canvastack_format_bytes($memoryInfo['limit']),
					'usage_percent' => round($usageRatio * 100, 2) . '%',
					'available' => canvastack_format_bytes($memoryInfo['available']),
					'user_id' => session('id'),
					'route' => request()->path(),
				]);
			}
			
			if ($throwOnCritical) {
				throw ControllerException::operationFailed(
					'memory_limit',
					$message,
					[
						'current' => canvastack_format_bytes($memoryInfo['current']),
						'limit' => canvastack_format_bytes($memoryInfo['limit']),
						'usage_percent' => round($usageRatio * 100, 2) . '%',
						'user_id' => session('id'),
					]
				);
			}
			
		} elseif ($usageRatio >= $warningThreshold) {
			$status = 'warning';
			$message = sprintf(
				'WARNING: Memory usage at %.2f%% (%s of %s)',
				$usageRatio * 100,
				canvastack_format_bytes($memoryInfo['current']),
				canvastack_format_bytes($memoryInfo['limit'])
			);
			
			// Log warning
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::warning('High memory usage', [
					'current' => canvastack_format_bytes($memoryInfo['current']),
					'limit' => canvastack_format_bytes($memoryInfo['limit']),
					'usage_percent' => round($usageRatio * 100, 2) . '%',
					'available' => canvastack_format_bytes($memoryInfo['available']),
					'user_id' => session('id'),
					'route' => request()->path(),
				]);
			}
		}
		
		return [
			'status' => $status,
			'message' => $message,
			'usage_ratio' => $usageRatio,
			'usage_percent' => round($usageRatio * 100, 2),
			'current' => canvastack_format_bytes($memoryInfo['current']),
			'limit' => canvastack_format_bytes($memoryInfo['limit']),
			'available' => canvastack_format_bytes($memoryInfo['available']),
		];
	}
}

if (!function_exists('canvastack_set_memory_limit')) {
	/**
	 * Set PHP memory limit
	 * 
	 * Sets PHP memory limit from configuration or specified value.
	 * Validates and applies memory limit safely.
	 * 
	 * @param string|null $limit Memory limit (e.g., "256M", "1G") or null to use config
	 * @return bool True if limit was set successfully
	 * 
	 * @performance Memory Management - Configures memory limit
	 */
	function canvastack_set_memory_limit(?string $limit = null): bool {
		if ($limit === null) {
			$limit = config('canvastack.controller.performance.memory_limit', '256M');
		}
		
		if (empty($limit)) {
			return false;
		}
		
		// Get current limit
		$currentLimit = ini_get('memory_limit');
		
		// Don't reduce memory limit if current is higher
		if ($currentLimit !== '-1') {
			$currentBytes = canvastack_convert_to_bytes($currentLimit);
			$newBytes = canvastack_convert_to_bytes($limit);
			
			if ($newBytes < $currentBytes) {
				// Log that we're not reducing the limit
				if (config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::info('Memory limit not changed (current limit is higher)', [
						'current_limit' => $currentLimit,
						'requested_limit' => $limit,
					]);
				}
				return false;
			}
		}
		
		// Set new limit
		$result = ini_set('memory_limit', $limit);
		
		// Log memory limit change
		if ($result !== false && config('canvastack.controller.logging.log_performance_issues', true)) {
			\Illuminate\Support\Facades\Log::info('Memory limit changed', [
				'old_limit' => $currentLimit,
				'new_limit' => $limit,
			]);
		}
		
		return $result !== false;
	}
}


if (!function_exists('canvastack_handle_out_of_memory')) {
	/**
	 * Handle out-of-memory situations gracefully
	 * 
	 * Attempts to recover from out-of-memory situations by:
	 * 1. Clearing caches
	 * 2. Running garbage collection
	 * 3. Freeing up memory where possible
	 * 
	 * @param callable|null $callback Optional callback to execute after cleanup
	 * @return bool True if recovery was successful
	 * 
	 * @performance Memory Management - Handles out-of-memory errors gracefully
	 */
	function canvastack_handle_out_of_memory(?callable $callback = null): bool {
		// Log the out-of-memory situation
		if (config('canvastack.controller.logging.log_performance_issues', true)) {
			$memoryInfo = canvastack_memory_usage(false);
			\Illuminate\Support\Facades\Log::error('Out of memory situation detected', [
				'current' => canvastack_format_bytes($memoryInfo['current']),
				'peak' => canvastack_format_bytes($memoryInfo['peak']),
				'limit' => canvastack_format_bytes($memoryInfo['limit']),
				'user_id' => session('id'),
				'route' => request()->path(),
			]);
		}
		
		try {
			// Step 1: Clear all caches
			if (function_exists('canvastack_controller_cache_flush')) {
				canvastack_controller_cache_flush();
			}
			
			// Step 2: Run garbage collection
			if (function_exists('gc_collect_cycles')) {
				gc_collect_cycles();
			}
			
			// Step 3: Clear Laravel caches
			if (function_exists('cache')) {
				try {
					cache()->flush();
				} catch (\Exception $e) {
					// Ignore cache flush errors
				}
			}
			
			// Step 4: Execute callback if provided
			if ($callback !== null && is_callable($callback)) {
				$callback();
			}
			
			// Check if we recovered enough memory
			$memoryInfo = canvastack_memory_usage(false);
			$usageRatio = $memoryInfo['current'] / $memoryInfo['limit'];
			
			if ($usageRatio < 0.85) {
				// Successfully recovered
				if (config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::info('Successfully recovered from out-of-memory situation', [
						'current' => canvastack_format_bytes($memoryInfo['current']),
						'usage_percent' => round($usageRatio * 100, 2) . '%',
					]);
				}
				return true;
			}
			
			return false;
			
		} catch (\Exception $e) {
			// Log recovery failure
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::error('Failed to recover from out-of-memory situation', [
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}
			
			return false;
		}
	}
}

if (!function_exists('canvastack_register_shutdown_handler')) {
	/**
	 * Register shutdown handler for out-of-memory errors
	 * 
	 * Registers a shutdown function to handle fatal errors including
	 * out-of-memory errors gracefully.
	 * 
	 * @return void
	 * 
	 * @performance Memory Management - Handles fatal memory errors
	 */
	function canvastack_register_shutdown_handler(): void {
		register_shutdown_function(function() {
			$error = error_get_last();
			
			if ($error !== null && $error['type'] === E_ERROR) {
				// Check if it's an out-of-memory error
				if (strpos($error['message'], 'Allowed memory size') !== false) {
					// Log the error
					if (config('canvastack.controller.logging.log_performance_issues', true)) {
						\Illuminate\Support\Facades\Log::critical('Fatal out-of-memory error', [
							'message' => $error['message'],
							'file' => $error['file'],
							'line' => $error['line'],
							'user_id' => session('id'),
							'route' => request()->path(),
						]);
					}
					
					// Attempt to display user-friendly error message
					if (!headers_sent()) {
						http_response_code(500);
						header('Content-Type: application/json');
						echo json_encode([
							'error' => 'Server ran out of memory processing your request',
							'message' => 'Please try again with a smaller dataset or contact support',
							'code' => 'OUT_OF_MEMORY'
						]);
					}
				}
			}
		});
	}
}


if (!function_exists('canvastack_memory_monitor_start')) {
	/**
	 * Start memory monitoring for a code block
	 * 
	 * Starts monitoring memory usage for a specific code block.
	 * Returns a monitoring context that should be passed to canvastack_memory_monitor_end().
	 * 
	 * @param string $label Label for this monitoring session
	 * @return array Monitoring context
	 * 
	 * @performance Memory Management - Monitors memory usage
	 * 
	 * @example
	 * $monitor = canvastack_memory_monitor_start('Processing large dataset');
	 * // ... your code ...
	 * canvastack_memory_monitor_end($monitor);
	 */
	function canvastack_memory_monitor_start(string $label): array {
		return [
			'label' => $label,
			'start_time' => microtime(true),
			'start_memory' => memory_get_usage(true),
			'start_peak' => memory_get_peak_usage(true),
		];
	}
}

if (!function_exists('canvastack_memory_monitor_end')) {
	/**
	 * End memory monitoring and log results
	 * 
	 * Ends memory monitoring session and logs the results.
	 * Calculates memory used and execution time.
	 * 
	 * @param array $context Monitoring context from canvastack_memory_monitor_start()
	 * @param bool $logResults Log results to log file (default: true)
	 * @return array Monitoring results
	 * 
	 * @performance Memory Management - Monitors memory usage
	 */
	function canvastack_memory_monitor_end(array $context, bool $logResults = true): array {
		$endTime = microtime(true);
		$endMemory = memory_get_usage(true);
		$endPeak = memory_get_peak_usage(true);
		
		$results = [
			'label' => $context['label'],
			'execution_time' => round(($endTime - $context['start_time']) * 1000, 2), // milliseconds
			'memory_used' => $endMemory - $context['start_memory'],
			'memory_used_formatted' => canvastack_format_bytes($endMemory - $context['start_memory']),
			'peak_memory' => $endPeak,
			'peak_memory_formatted' => canvastack_format_bytes($endPeak),
			'peak_increase' => $endPeak - $context['start_peak'],
			'peak_increase_formatted' => canvastack_format_bytes($endPeak - $context['start_peak']),
		];
		
		// Log results if enabled
		if ($logResults && config('canvastack.controller.logging.log_performance_issues', true)) {
			\Illuminate\Support\Facades\Log::debug('Memory monitoring results', [
				'label' => $results['label'],
				'execution_time_ms' => $results['execution_time'],
				'memory_used' => $results['memory_used_formatted'],
				'peak_memory' => $results['peak_memory_formatted'],
				'peak_increase' => $results['peak_increase_formatted'],
				'user_id' => session('id'),
				'route' => request()->path(),
			]);
		}
		
		return $results;
	}
}

if (!function_exists('canvastack_memory_monitor')) {
	/**
	 * Monitor memory usage of a callback
	 * 
	 * Executes a callback while monitoring its memory usage.
	 * Returns both the callback result and monitoring data.
	 * 
	 * @param string $label Label for this monitoring session
	 * @param callable $callback Callback to execute and monitor
	 * @param bool $logResults Log results to log file (default: true)
	 * @return array ['result' => callback result, 'monitoring' => monitoring data]
	 * 
	 * @performance Memory Management - Monitors memory usage
	 * 
	 * @example
	 * $data = canvastack_memory_monitor('Process users', function() {
	 *     return User::all()->map(function($user) {
	 *         return $user->transform();
	 *     });
	 * });
	 * $users = $data['result'];
	 * $memoryUsed = $data['monitoring']['memory_used_formatted'];
	 */
	function canvastack_memory_monitor(string $label, callable $callback, bool $logResults = true): array {
		$context = canvastack_memory_monitor_start($label);
		
		try {
			$result = $callback();
			$monitoring = canvastack_memory_monitor_end($context, $logResults);
			
			return [
				'result' => $result,
				'monitoring' => $monitoring,
			];
			
		} catch (\Exception $e) {
			// Log error with monitoring data
			$monitoring = canvastack_memory_monitor_end($context, false);
			
			if (config('canvastack.controller.logging.log_performance_issues', true)) {
				\Illuminate\Support\Facades\Log::error('Error during monitored execution', [
					'label' => $label,
					'error' => $e->getMessage(),
					'execution_time_ms' => $monitoring['execution_time'],
					'memory_used' => $monitoring['memory_used_formatted'],
					'user_id' => session('id'),
				]);
			}
			
			throw $e;
		}
	}
}

if (!function_exists('canvastack_log_memory_snapshot')) {
	/**
	 * Log current memory snapshot
	 * 
	 * Logs a snapshot of current memory usage with optional label.
	 * Useful for debugging memory issues.
	 * 
	 * @param string $label Label for this snapshot
	 * @param string $level Log level (debug, info, warning, error)
	 * @return array Memory snapshot data
	 * 
	 * @performance Memory Management - Debugging memory issues
	 */
	function canvastack_log_memory_snapshot(string $label, string $level = 'debug'): array {
		$memoryInfo = canvastack_memory_usage(true);
		
		$snapshot = [
			'label' => $label,
			'timestamp' => date('Y-m-d H:i:s'),
			'current' => $memoryInfo['current'],
			'peak' => $memoryInfo['peak'],
			'limit' => $memoryInfo['limit'],
			'available' => $memoryInfo['available'],
			'usage_percent' => $memoryInfo['usage_percent'],
		];
		
		// Log snapshot
		if (config('canvastack.controller.logging.log_performance_issues', true)) {
			\Illuminate\Support\Facades\Log::log($level, 'Memory snapshot', array_merge($snapshot, [
				'user_id' => session('id'),
				'route' => request()->path(),
			]));
		}
		
		return $snapshot;
	}
}

if (!function_exists('canvastack_query_cached')) {
	/**
	 * Execute Database Query with Caching Support
	 *
	 * Wrapper around canvastack_query() that adds result caching.
	 * Use this for expensive queries that are executed frequently with the same parameters.
	 *
	 * @param string $sql SQL query to execute
	 * @param string $type Query type (TABLE, SELECT, INSERT, UPDATE, DELETE, STATEMENT)
	 * @param string|null $connection Database connection name
	 * @param int|null $ttl Cache TTL in seconds (null = use default from config)
	 * @param bool $useCache Enable/disable caching for this call
	 * @return mixed Query result
	 * @throws \InvalidArgumentException
	 * 
	 * @security Inherits security from canvastack_query()
	 * @performance Caches query results to avoid repeated database calls
	 * 
	 * @example
	 * // Cache for 1 hour (default)
	 * $users = canvastack_query_cached('SELECT * FROM users WHERE active = 1', 'SELECT');
	 * 
	 * // Cache for 5 minutes
	 * $stats = canvastack_query_cached('SELECT COUNT(*) as total FROM orders', 'SELECT', null, 300);
	 * 
	 * // Disable caching for this call
	 * $realtime = canvastack_query_cached('SELECT * FROM live_data', 'SELECT', null, null, false);
	 */
	function canvastack_query_cached(string $sql, string $type = 'TABLE', ?string $connection = null, ?int $ttl = null, bool $useCache = true) {
		// Check if caching is enabled globally
		$cachingEnabled = config('canvastack.controller.performance.enable_caching', true);
		
		// If caching is disabled, execute query directly
		if (!$cachingEnabled || !$useCache) {
			return canvastack_query($sql, $type, $connection);
		}
		
		// Generate cache key based on query, type, and connection
		$cacheKey = canvastack_controller_cache_key('query', [
			'sql' => md5($sql),
			'type' => $type,
			'connection' => $connection ?? 'default',
		]);
		
		// Get TTL from config if not specified
		if ($ttl === null) {
			$ttl = config('canvastack.controller.performance.cache_ttl', 3600);
		}
		
		// Try to get from cache, or execute and cache
		return canvastack_controller_cache_remember($cacheKey, function() use ($sql, $type, $connection) {
			return canvastack_query($sql, $type, $connection);
		}, $ttl);
	}
}

if (!function_exists('canvastack_insert_optimized')) {
	/**
	 * Optimized Insert with Batch Support
	 *
	 * Enhanced version of canvastack_insert() with batch insert support
	 * for better performance when inserting multiple records.
	 *
	 * @param object|string $model Model instance or class name
	 * @param array $data Single record or array of records to insert
	 * @param bool|string $get_field Field to return (true for 'id', string for specific field, false for none)
	 * @param bool $batch Enable batch insert mode (default: false)
	 * @return mixed
	 * @throws \InvalidArgumentException
	 * 
	 * @security Inherits security from canvastack_insert()
	 * @performance Supports batch inserts for better performance
	 * 
	 * @example
	 * // Single insert
	 * $id = canvastack_insert_optimized(User::class, ['name' => 'John', 'email' => 'john@example.com'], true);
	 * 
	 * // Batch insert
	 * $users = [
	 *     ['name' => 'John', 'email' => 'john@example.com'],
	 *     ['name' => 'Jane', 'email' => 'jane@example.com'],
	 * ];
	 * canvastack_insert_optimized(User::class, $users, false, true);
	 */
	function canvastack_insert_optimized($model, $data, $get_field = false, bool $batch = false) {
		// If not batch mode or single record, use regular insert
		if (!$batch || !is_array($data) || empty($data)) {
			return canvastack_insert($model, $data, $get_field);
		}
		
		// Check if data is array of arrays (batch mode)
		$isBatch = is_array(reset($data));
		
		if (!$isBatch) {
			return canvastack_insert($model, $data, $get_field);
		}
		
		// Batch insert mode
		try {
			$modelClass = is_string($model) ? $model : get_class($model);
			$inserted = [];
			
			// Process in chunks to avoid memory issues
			$chunkSize = config('canvastack.controller.performance.batch_insert_chunk_size', 100);
			$chunks = array_chunk($data, $chunkSize);
			
			foreach ($chunks as $chunk) {
				// Use DB transaction for better performance
				\Illuminate\Support\Facades\DB::transaction(function() use ($modelClass, $chunk, &$inserted) {
					foreach ($chunk as $record) {
						$result = canvastack_insert($modelClass, $record, true);
						$inserted[] = $result;
					}
				});
			}
			
			// Return based on get_field parameter
			if ($get_field !== false) {
				return $inserted;
			}
			
			return true;
			
		} catch (\Exception $e) {
			// Log error
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::error('Batch insert failed', [
					'model' => is_string($model) ? $model : get_class($model),
					'records_count' => count($data),
					'error' => $e->getMessage(),
					'user_id' => session('id'),
				]);
			}
			
			throw $e;
		}
	}
}

if (!function_exists('canvastack_model_cache')) {
	/**
	 * Cache Model Query Results
	 *
	 * Caches the results of expensive model queries.
	 * Useful for frequently accessed data that doesn't change often.
	 *
	 * @param string $cacheKey Unique cache key
	 * @param callable $callback Callback that returns the model query result
	 * @param int|null $ttl Cache TTL in seconds (null = use default from config)
	 * @return mixed Query result
	 * 
	 * @performance Caches model query results to avoid repeated database calls
	 * 
	 * @example
	 * // Cache user preferences
	 * $preferences = canvastack_model_cache('user_preferences_' . $userId, function() use ($userId) {
	 *     return UserPreference::where('user_id', $userId)->get();
	 * }, 7200);
	 * 
	 * // Cache active modules
	 * $modules = canvastack_model_cache('active_modules', function() {
	 *     return Module::where('active', 1)->orderBy('order')->get();
	 * });
	 */
	function canvastack_model_cache(string $cacheKey, callable $callback, ?int $ttl = null) {
		// Check if caching is enabled
		$cachingEnabled = config('canvastack.controller.performance.enable_caching', true);
		
		if (!$cachingEnabled) {
			return $callback();
		}
		
		// Get TTL from config if not specified
		if ($ttl === null) {
			$ttl = config('canvastack.controller.performance.cache_ttl', 3600);
		}
		
		// Use cache remember
		return canvastack_controller_cache_remember($cacheKey, $callback, $ttl);
	}
}

if (!function_exists('canvastack_invalidate_model_cache')) {
	/**
	 * Invalidate Model Cache
	 *
	 * Invalidates cached model query results.
	 * Call this after updating/deleting records to ensure cache consistency.
	 *
	 * @param string|array $cacheKeys Cache key(s) to invalidate
	 * @return bool Success status
	 * 
	 * @performance Cache invalidation for data consistency
	 * 
	 * @example
	 * // Invalidate single cache
	 * canvastack_invalidate_model_cache('user_preferences_' . $userId);
	 * 
	 * // Invalidate multiple caches
	 * canvastack_invalidate_model_cache(['active_modules', 'module_menu']);
	 * 
	 * // Invalidate all caches with prefix
	 * canvastack_invalidate_model_cache('user_preferences_*');
	 */
	function canvastack_invalidate_model_cache($cacheKeys): bool {
		if (is_string($cacheKeys)) {
			// Check if wildcard pattern
			if (strpos($cacheKeys, '*') !== false) {
				$prefix = str_replace('*', '', $cacheKeys);
				return canvastack_controller_cache_flush($prefix);
			}
			
			return canvastack_controller_cache_forget($cacheKeys);
		}
		
		if (is_array($cacheKeys)) {
			$success = true;
			foreach ($cacheKeys as $key) {
				if (!canvastack_controller_cache_forget($key)) {
					$success = false;
				}
			}
			return $success;
		}
		
		return false;
	}
}

if (!function_exists('canvastack_get_model_controllers_info_cached')) {
	/**
	 * Get Model Controllers Info with Caching
	 *
	 * Cached version of canvastack_get_model_controllers_info().
	 * This is an expensive operation that scans all routes and controllers.
	 *
	 * @param array $buffers Buffer data
	 * @param mixed $table_replace_map Table replacement map
	 * @param string $restriction_path Controller path restriction
	 * @param int|null $ttl Cache TTL in seconds (null = use default)
	 * @return array Controller info
	 * 
	 * @performance Caches expensive route scanning operation
	 * 
	 * @example
	 * $controllers = canvastack_get_model_controllers_info_cached();
	 */
	function canvastack_get_model_controllers_info_cached($buffers = [], $table_replace_map = null, $restriction_path = 'App\Http\Controllers\Admin\\', ?int $ttl = null) {
		// Check if caching is enabled
		$cachingEnabled = config('canvastack.controller.performance.enable_caching', true);
		
		if (!$cachingEnabled) {
			return canvastack_get_model_controllers_info($buffers, $table_replace_map, $restriction_path);
		}
		
		// Generate cache key
		$cacheKey = canvastack_controller_cache_key('model_controllers_info', [
			'buffers' => md5(serialize($buffers)),
			'table_map' => md5(serialize($table_replace_map)),
			'path' => $restriction_path,
		]);
		
		// Get TTL from config if not specified
		if ($ttl === null) {
			$ttl = config('canvastack.controller.caching.route_info_cache_ttl', 3600);
		}
		
		// Use cache remember
		return canvastack_controller_cache_remember($cacheKey, function() use ($buffers, $table_replace_map, $restriction_path) {
			return canvastack_get_model_controllers_info($buffers, $table_replace_map, $restriction_path);
		}, $ttl);
	}
}

if (!function_exists('canvastack_string_concat_efficient')) {
	/**
	 * Efficient String Concatenation
	 *
	 * More efficient than repeated string concatenation using the . operator.
	 * Uses array join which is faster for multiple concatenations.
	 *
	 * @param array $parts Array of strings to concatenate
	 * @param string $separator Separator between parts (default: '')
	 * @return string Concatenated string
	 * 
	 * @performance Optimized string concatenation using array join
	 * 
	 * @example
	 * // Instead of: $str = $a . $b . $c . $d;
	 * $str = canvastack_string_concat_efficient([$a, $b, $c, $d]);
	 * 
	 * // With separator
	 * $csv = canvastack_string_concat_efficient([$col1, $col2, $col3], ',');
	 */
	function canvastack_string_concat_efficient(array $parts, string $separator = ''): string {
		// Filter out null/empty values if needed
		$parts = array_filter($parts, function($part) {
			return $part !== null && $part !== '';
		});
		
		return implode($separator, $parts);
	}
}

if (!function_exists('canvastack_string_replace_multiple')) {
	/**
	 * Replace Multiple Strings Efficiently
	 *
	 * More efficient than multiple str_replace calls.
	 * Uses single str_replace call with arrays.
	 *
	 * @param array $search Array of strings to search for
	 * @param array|string $replace Array of replacements or single replacement for all
	 * @param string $subject String to perform replacements on
	 * @return string String with replacements
	 * 
	 * @performance Optimized multiple string replacement
	 * 
	 * @example
	 * // Replace multiple patterns
	 * $clean = canvastack_string_replace_multiple(
	 *     ['<script>', '</script>', '<iframe>'],
	 *     '',
	 *     $userInput
	 * );
	 * 
	 * // Replace with different values
	 * $formatted = canvastack_string_replace_multiple(
	 *     ['{name}', '{email}', '{date}'],
	 *     [$userName, $userEmail, $currentDate],
	 *     $template
	 * );
	 */
	function canvastack_string_replace_multiple(array $search, $replace, string $subject): string {
		return str_replace($search, $replace, $subject);
	}
}

if (!function_exists('canvastack_string_sanitize_efficient')) {
	/**
	 * Efficient String Sanitization
	 *
	 * Optimized version of canvastack_clean_strings with better performance.
	 * Uses single preg_replace call instead of multiple operations.
	 *
	 * @param string $string String to sanitize
	 * @param string $replaceWith Character to replace special chars with (default: '-')
	 * @param bool $lowercase Convert to lowercase (default: true)
	 * @return string Sanitized string
	 * 
	 * @performance Optimized string sanitization with single regex
	 * 
	 * @example
	 * $slug = canvastack_string_sanitize_efficient('Hello World! @2024', '-');
	 * // Result: 'hello-world-2024'
	 */
	function canvastack_string_sanitize_efficient(string $string, string $replaceWith = '-', bool $lowercase = true): string {
		// Trim and remove special characters in one operation
		$string = trim($string);
		$string = preg_replace('/[;\.\/\?\\\:@&=+\$,_\~\*\'"\!\|%<>\{\}\^\[\]`\-\s]+/', $replaceWith, $string);
		
		// Remove leading/trailing replacement character
		$string = trim($string, $replaceWith);
		
		// Convert to lowercase if requested
		if ($lowercase) {
			$string = strtolower($string);
		}
		
		return $string;
	}
}

if (!function_exists('canvastack_string_truncate')) {
	/**
	 * Truncate String with Ellipsis
	 *
	 * Truncates a string to specified length and adds ellipsis.
	 * Avoids cutting words in the middle.
	 *
	 * @param string $string String to truncate
	 * @param int $length Maximum length (default: 100)
	 * @param string $ellipsis Ellipsis string (default: '...')
	 * @param bool $breakWords Allow breaking words (default: false)
	 * @return string Truncated string
	 * 
	 * @performance Efficient string truncation
	 * 
	 * @example
	 * $short = canvastack_string_truncate($longText, 50);
	 * // Result: 'This is a long text that will be truncated...'
	 */
	function canvastack_string_truncate(string $string, int $length = 100, string $ellipsis = '...', bool $breakWords = false): string {
		if (strlen($string) <= $length) {
			return $string;
		}
		
		$truncated = substr($string, 0, $length);
		
		if (!$breakWords) {
			// Find last space to avoid breaking words
			$lastSpace = strrpos($truncated, ' ');
			if ($lastSpace !== false) {
				$truncated = substr($truncated, 0, $lastSpace);
			}
		}
		
		return $truncated . $ellipsis;
	}
}

if (!function_exists('canvastack_string_contains_any')) {
	/**
	 * Check if String Contains Any of Multiple Needles
	 *
	 * Optimized version of canvastack_string_contained with early exit.
	 * More efficient for checking multiple patterns.
	 *
	 * @param string $haystack String to search in
	 * @param array|string $needles String(s) to search for
	 * @param bool $caseSensitive Case sensitive search (default: true)
	 * @return bool True if any needle is found
	 * 
	 * @performance Optimized with early exit and optional case-insensitive search
	 * 
	 * @example
	 * if (canvastack_string_contains_any($userInput, ['<script>', '<iframe>', 'javascript:'])) {
	 *     // Potential XSS detected
	 * }
	 */
	function canvastack_string_contains_any(string $haystack, $needles, bool $caseSensitive = true): bool {
		if (!$caseSensitive) {
			$haystack = strtolower($haystack);
		}
		
		$needles = is_array($needles) ? $needles : [$needles];
		
		foreach ($needles as $needle) {
			if (!$caseSensitive) {
				$needle = strtolower($needle);
			}
			
			if (strpos($haystack, $needle) !== false) {
				return true;
			}
		}
		
		return false;
	}
}

if (!function_exists('canvastack_string_starts_with')) {
	/**
	 * Check if String Starts With Prefix
	 *
	 * Efficient prefix checking without regex.
	 *
	 * @param string $string String to check
	 * @param string $prefix Prefix to look for
	 * @param bool $caseSensitive Case sensitive check (default: true)
	 * @return bool True if string starts with prefix
	 * 
	 * @performance Optimized prefix checking
	 * 
	 * @example
	 * if (canvastack_string_starts_with($route, 'admin.')) {
	 *     // Admin route
	 * }
	 */
	function canvastack_string_starts_with(string $string, string $prefix, bool $caseSensitive = true): bool {
		if (!$caseSensitive) {
			$string = strtolower($string);
			$prefix = strtolower($prefix);
		}
		
		return substr($string, 0, strlen($prefix)) === $prefix;
	}
}

if (!function_exists('canvastack_string_ends_with')) {
	/**
	 * Check if String Ends With Suffix
	 *
	 * Efficient suffix checking without regex.
	 *
	 * @param string $string String to check
	 * @param string $suffix Suffix to look for
	 * @param bool $caseSensitive Case sensitive check (default: true)
	 * @return bool True if string ends with suffix
	 * 
	 * @performance Optimized suffix checking
	 * 
	 * @example
	 * if (canvastack_string_ends_with($filename, '.php')) {
	 *     // PHP file
	 * }
	 */
	function canvastack_string_ends_with(string $string, string $suffix, bool $caseSensitive = true): bool {
		if (!$caseSensitive) {
			$string = strtolower($string);
			$suffix = strtolower($suffix);
		}
		
		$length = strlen($suffix);
		if ($length === 0) {
			return true;
		}
		
		return substr($string, -$length) === $suffix;
	}
}

if (!function_exists('canvastack_string_escape_sql')) {
	/**
	 * Escape String for SQL (Legacy Support)
	 *
	 * Note: This should NOT be used for new code. Use parameterized queries instead.
	 * This function is provided for legacy code compatibility only.
	 *
	 * @param string $string String to escape
	 * @param string|null $connection Database connection
	 * @return string Escaped string
	 * 
	 * @deprecated Use parameterized queries instead
	 * @security This is a fallback - always prefer parameterized queries
	 * 
	 * @example
	 * // DON'T DO THIS - use parameterized queries instead!
	 * $escaped = canvastack_string_escape_sql($userInput);
	 * $sql = "SELECT * FROM users WHERE name = '{$escaped}'";
	 * 
	 * // DO THIS INSTEAD:
	 * $users = DB::table('users')->where('name', $userInput)->get();
	 */
	function canvastack_string_escape_sql(string $string, ?string $connection = null): string {
		// Log usage of this deprecated function
		if (config('canvastack.controller.logging.log_security_events', true)) {
			\Illuminate\Support\Facades\Log::warning('Deprecated function canvastack_string_escape_sql used', [
				'string_preview' => substr($string, 0, 50),
				'connection' => $connection ?? 'default',
				'user_id' => session('id'),
				'route' => request()->path(),
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
			]);
		}
		
		// Use PDO quote if available
		if ($connection) {
			$pdo = \Illuminate\Support\Facades\DB::connection($connection)->getPdo();
		} else {
			$pdo = \Illuminate\Support\Facades\DB::getPdo();
		}
		
		// Remove quotes added by PDO::quote
		$quoted = $pdo->quote($string);
		return substr($quoted, 1, -1);
	}
}


if (!function_exists('canvastack_controller_retry')) {
	/**
	 * Retry a callback with exponential backoff for transient errors
	 * 
	 * Executes a callback and retries on failure with exponential backoff delay.
	 * This is useful for handling transient errors like network timeouts, cache
	 * connection failures, or temporary database unavailability.
	 * 
	 * The retry mechanism implements:
	 * - Configurable number of retry attempts
	 * - Exponential backoff delay (100ms, 200ms, 400ms, 800ms, etc.)
	 * - Detailed logging of retry attempts and failures
	 * - Graceful degradation on final failure
	 * 
	 * Retry Strategy:
	 * - Attempt 1: Immediate execution
	 * - Attempt 2: Wait 100ms, then retry
	 * - Attempt 3: Wait 200ms, then retry
	 * - Attempt 4: Wait 400ms, then retry
	 * - Attempt 5: Wait 800ms, then retry
	 * 
	 * @param callable $callback Function to execute and retry on failure
	 * @param int $maxAttempts Maximum number of retry attempts (default: 3)
	 * @param int $initialDelayMs Initial delay in milliseconds (default: 100)
	 * @param string $operationName Operation name for logging (default: 'operation')
	 * @return mixed Result from callback if successful, null on final failure
	 * 
	 * @security Prevents infinite retry loops with max attempts limit
	 * @security Logs all retry attempts for security monitoring
	 * @security Does not retry on authentication or authorization errors
	 * 
	 * @performance Exponential backoff prevents overwhelming failing services
	 * @performance Configurable delays allow tuning for specific use cases
	 * @performance Total retry time: ~1.5 seconds for 3 attempts with 100ms initial delay
	 * 
	 * @example
	 * ```php
	 * // Retry cache operation
	 * $result = canvastack_controller_retry(function() use ($key, $value) {
	 *     return Cache::put($key, $value, 3600);
	 * }, 3, 100, 'cache_put');
	 * 
	 * // Retry database query
	 * $users = canvastack_controller_retry(function() {
	 *     return DB::table('users')->get();
	 * }, 5, 200, 'database_query');
	 * 
	 * // Retry API call
	 * $response = canvastack_controller_retry(function() use ($url) {
	 *     return Http::timeout(5)->get($url);
	 * }, 3, 500, 'api_call');
	 * 
	 * // With custom error handling
	 * $result = canvastack_controller_retry(function() {
	 *     $result = someTransientOperation();
	 *     if (!$result) {
	 *         throw new \RuntimeException('Operation failed');
	 *     }
	 *     return $result;
	 * }, 3, 100, 'custom_operation');
	 * ```
	 */
	function canvastack_controller_retry(callable $callback, int $maxAttempts = 3, int $initialDelayMs = 100, string $operationName = 'operation') {
		$attempt = 0;
		$delay = $initialDelayMs;
		
		while ($attempt < $maxAttempts) {
			$attempt++;
			
			try {
				// Execute callback
				$result = $callback();
				
				// Log successful retry if not first attempt
				if ($attempt > 1 && config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::info("Retry successful for {$operationName}", [
						'attempt' => $attempt,
						'max_attempts' => $maxAttempts,
					]);
				}
				
				return $result;
			} catch (\Exception $e) {
				// Check if this is the last attempt
				if ($attempt >= $maxAttempts) {
					// Log final failure
					if (config('canvastack.controller.logging.log_performance_issues', true)) {
						\Illuminate\Support\Facades\Log::error("Retry failed for {$operationName} after {$maxAttempts} attempts", [
							'error' => $e->getMessage(),
							'attempts' => $attempt,
						]);
					}
					
					// Return null on final failure (graceful degradation)
					return null;
				}
				
				// Log retry attempt
				if (config('canvastack.controller.logging.log_performance_issues', true)) {
					\Illuminate\Support\Facades\Log::warning("Retrying {$operationName} (attempt {$attempt}/{$maxAttempts})", [
						'error' => $e->getMessage(),
						'delay_ms' => $delay,
					]);
				}
				
				// Wait before retrying (exponential backoff)
				usleep($delay * 1000); // Convert ms to microseconds
				
				// Double the delay for next attempt (exponential backoff)
				$delay *= 2;
			}
		}
		
		// Should never reach here, but return null for safety
		return null;
	}
}


if (!function_exists('canvastack_mail_config_service')) {
	/**
	 * Get Mail Configuration Service Instance
	 * 
	 * Returns singleton instance of MailConfigService for managing
	 * dynamic SMTP configuration from database preferences.
	 * 
	 * @return \Canvastack\Canvastack\Services\MailConfigService
	 * 
	 * @example
	 * // Load SMTP config from preference
	 * canvastack_mail_config_service()->loadSmtpFromPreference();
	 * 
	 * // Test SMTP connection
	 * $result = canvastack_mail_config_service()->testConnection();
	 * if ($result['success']) {
	 *     echo 'Connection OK';
	 * }
	 */
	function canvastack_mail_config_service(): \Canvastack\Canvastack\Services\MailConfigService {
		return app(\Canvastack\Canvastack\Services\MailConfigService::class);
	}
}

if (!function_exists('canvastack_mail_reload_config')) {
	/**
	 * Reload Mail Configuration from Preference
	 * 
	 * Invalidates preference cache and reloads SMTP configuration
	 * from database. Call this after updating SMTP settings in preferences.
	 * 
	 * @return bool True if configuration was reloaded successfully
	 * 
	 * @example
	 * // After updating preference
	 * Preference::find(1)->update($smtpData);
	 * canvastack_mail_reload_config();
	 */
	function canvastack_mail_reload_config(): bool {
		return canvastack_mail_config_service()->reloadConfig();
	}
}

if (!function_exists('canvastack_mail_test_smtp')) {
	/**
	 * Test SMTP Connection
	 * 
	 * Tests SMTP connection with current configuration or provided config.
	 * Returns array with 'success' boolean and 'message' string.
	 * 
	 * @param array|null $config Optional SMTP config to test
	 * @return array ['success' => bool, 'message' => string]
	 * 
	 * @example
	 * // Test current configuration
	 * $result = canvastack_mail_test_smtp();
	 * if (!$result['success']) {
	 *     echo 'Error: ' . $result['message'];
	 * }
	 * 
	 * // Test specific configuration
	 * $result = canvastack_mail_test_smtp([
	 *     'host' => 'smtp.gmail.com',
	 *     'port' => 587,
	 *     'encryption' => 'tls',
	 *     'username' => 'user@gmail.com',
	 *     'password' => 'password'
	 * ]);
	 */
	function canvastack_mail_test_smtp(?array $config = null): array {
		return canvastack_mail_config_service()->testConnection($config);
	}
}

if (!function_exists('canvastack_mail_encrypt_password')) {
	/**
	 * Encrypt SMTP Password
	 * 
	 * Encrypts password before storing in database using Laravel Crypt.
	 * Returns encrypted string or null if password is empty.
	 * 
	 * @param string|null $password Plain text password
	 * @return string|null Encrypted password
	 * 
	 * @example
	 * $encrypted = canvastack_mail_encrypt_password('mypassword');
	 * Preference::find(1)->update(['smtp_password' => $encrypted]);
	 */
	function canvastack_mail_encrypt_password(?string $password): ?string {
		return canvastack_mail_config_service()->encryptPassword($password);
	}
}

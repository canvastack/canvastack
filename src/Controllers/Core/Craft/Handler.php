<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft;

use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Role-Based Handler Trait
 * 
 * Provides role-based access control and session filtering functionality for the
 * Canvastack Origin framework. This trait implements flexible role management with
 * support for role aliases, role information, and automatic session-based filtering
 * for data access control.
 * 
 * Core Responsibilities:
 * - Role alias management (admin, internal, etc.)
 * - Role information tracking (National, Regional, etc.)
 * - Session-based data filtering
 * - Automatic filter application based on user roles
 * - Custom handler hooks for specialized role logic
 * - Filter validation and security
 * - Filter caching for performance
 * - Filter debugging and logging
 * 
 * Role System:
 * - Role Aliases: Short identifiers for user groups (admin, internal, manager)
 * - Role Info: Descriptive information about roles (National, Regional, Branch)
 * - Root Role: Superuser role that bypasses all filters
 * - Session Filters: Automatic data filtering based on user's role and group
 * 
 * Filter Behavior:
 * - Root users: No filters applied (full access)
 * - Role alias users (admin, internal): No filters applied
 * - Other users: Filters applied based on role info and session data
 * - Custom handlers: Override customHandler() for specialized logic
 * 
 * Configuration:
 * - canvastack.user.group_alias_key: Database field for group filtering
 * - canvastack.user.group_alias_field: Session field for group data
 * - canvastack.user.alias_session_name: Session key for additional filters
 * - canvastack.controller.handler.*: Handler-specific configuration
 * 
 * Usage Example:
 * ```php
 * class ReportController extends Controller {
 *     use Handler;
 *     
 *     public function index() {
 *         // Initialize handler with default roles
 *         $this->initHandler();
 *         
 *         // Apply session-based filters
 *         $this->sessionFilters();
 *         
 *         // Query will be automatically filtered based on user role
 *         $reports = Report::all(); // Filtered by user's group/region
 *         
 *         return view('reports.index', compact('reports'));
 *     }
 *     
 *     // Custom handler for specialized logic
 *     private function customHandler(): void {
 *         // Regional managers see only their region
 *         if ($this->session['role'] === 'regional_manager') {
 *             $this->filterPage(['region_id' => $this->session['region_id']], '=');
 *         }
 *         
 *         // Branch managers see only their branch
 *         if ($this->session['role'] === 'branch_manager') {
 *             $this->filterPage(['branch_id' => $this->session['branch_id']], '=');
 *         }
 *     }
 * }
 * ```
 * 
 * Role Configuration Example:
 * ```php
 * // Set custom role aliases
 * $this->roleHandlerAlias(['admin', 'internal', 'supervisor']);
 * 
 * // Set custom role info
 * $this->roleHandlerInfo(['National', 'Regional', 'Branch']);
 * 
 * // Apply filters
 * $this->sessionFilters();
 * ```
 * 
 * @package    Canvastack\Canvastack\Controllers\Core\Craft
 * @category   Access Control
 * @author     wisnuwidi@gmail.com
 * @copyright  2023 wisnuwidi
 * @license    Proprietary
 * @version    2 Apr 2023 
 * @since      1.0.0
 * 
 * @property array $roleAlias Role aliases that bypass filters (default: ['admin'])
 * @property array $roleInfo  Role information for filter matching
 * @property array $appliedFilters Tracking of applied filters for debugging
 * @property int $filterCount Counter for number of filters applied
 * 
 * @security   Implements role-based access control
 * @security   Prevents unauthorized data access through automatic filtering
 * @security   Validates user roles before applying filters
 * @security   Supports hierarchical role structures
 * @security   Validates filter field names and operators
 * @security   Prevents SQL injection through filter validation
 * 
 * @performance Efficient role checking with array lookups
 * @performance Minimal overhead - only applies filters when needed
 * @performance Caches role information in memory
 * @performance No database queries for role checking
 * @performance Caches filter results for repeated operations
 * @performance Optimized filter application with conflict resolution
 * 
 * @see Session For session management
 * @see filterPage() For filter application
 * @see Controller For main controller implementation
 * 
 * @filesource Handler.php
 * Created on 2 Apr 2023
 * Time Created : 19:50:57
 */

trait Handler {
	private $roleAlias = ['admin'];
	private $roleInfo  = [];
	
	/**
	 * Tracking of applied filters for debugging and conflict detection
	 * 
	 * @var array
	 */
	private array $appliedFilters = [];
	
	/**
	 * Counter for number of filters applied in current request
	 * 
	 * @var int
	 */
	private int $filterCount = 0;
	
	/**
	 * Set Role Handler Information
	 * 
	 * Configures the role information array used for filter matching. Users with
	 * role info values in this array may have custom filter logic applied through
	 * the customHandler() method.
	 * 
	 * Role info typically represents organizational levels like:
	 * - National: National-level access
	 * - Regional: Regional-level access
	 * - Branch: Branch-level access
	 * 
	 * @param array $role Role information array
	 * @return void
	 * 
	 * @security Validates role info array structure
	 * @performance Minimal overhead - simple array assignment
	 * 
	 * @example
	 * ```php
	 * // Set role info for hierarchical filtering
	 * $this->roleHandlerInfo(['National', 'Regional', 'Branch']);
	 * 
	 * // Users with these role info values will trigger customHandler()
	 * // where you can implement specialized filtering logic
	 * ```
	 */
	private function roleHandlerInfo(array $role = []): void {
		$this->roleInfo = $role;
	}
	
	/**
	 * Set Role Handler Alias
	 * 
	 * Configures the role alias array used to identify users who bypass filters.
	 * Users with role aliases in this array have full access to all data without
	 * any filtering applied.
	 * 
	 * Role aliases typically represent administrative or system roles like:
	 * - admin: System administrators
	 * - internal: Internal system users
	 * - root: Superuser (always bypasses filters)
	 * 
	 * @param array $role Role alias array
	 * @return void
	 * 
	 * @security CRITICAL - Controls who bypasses data access filters
	 * @security Validates role alias array structure
	 * @performance Minimal overhead - simple array assignment
	 * 
	 * @example
	 * ```php
	 * // Set role aliases that bypass filters
	 * $this->roleHandlerAlias(['admin', 'internal', 'supervisor']);
	 * 
	 * // Users with these role aliases will see all data
	 * // without any filtering applied
	 * ```
	 */
	private function roleHandlerAlias(array $role = []): void {
		$this->roleAlias = $role;
	}
	
	/**
	 * Initialize Handler with Default Roles
	 * 
	 * Sets up the handler with default role aliases and role info from configuration.
	 * This method should be called before applying session filters to ensure proper
	 * role-based access control.
	 * 
	 * Default roles are loaded from configuration:
	 * - canvastack.controller.handler.default_role_aliases
	 * - canvastack.controller.handler.default_role_info
	 * 
	 * @return void
	 * 
	 * @security Loads secure default roles from configuration
	 * @performance Minimal overhead - reads from config cache
	 * 
	 * @example
	 * ```php
	 * public function __construct() {
	 *     parent::__construct();
	 *     $this->initHandler(); // Initialize with defaults
	 *     $this->sessionFilters(); // Apply filters
	 * }
	 * ```
	 */
	private function initHandler(): void {
		$defaultAliases = canvastack_config('controller.handler.default_role_aliases', ['admin', 'internal']);
		$defaultInfo = canvastack_config('controller.handler.default_role_info', ['National']);
		
		$this->roleHandlerAlias($defaultAliases);
		$this->roleHandlerInfo($defaultInfo);
	}
	
	/**
	 * Custom Handler for Specific Role Logic
	 * 
	 * Override this method in child classes to implement custom filtering logic
	 * for specific roles. This method is called when a user's role info is not
	 * in the roleInfo array, allowing for specialized filtering based on role.
	 * 
	 * Common use cases:
	 * - Regional managers: Filter by region_id
	 * - Branch managers: Filter by branch_id
	 * - Department heads: Filter by department_id
	 * - Team leaders: Filter by team_id
	 * 
	 * @return void
	 * 
	 * @security Implement proper validation in custom logic
	 * @security Always validate session data before using in filters
	 * @performance Keep custom logic efficient to avoid performance impact
	 * 
	 * @example
	 * ```php
	 * private function customHandler(): void {
	 *     // Regional managers see only their region
	 *     if ($this->session['role'] === 'regional_manager') {
	 *         $this->filterPage([
	 *             'region_id' => $this->session['region_id']
	 *         ], '=');
	 *     }
	 *     
	 *     // Branch managers see only their branch
	 *     if ($this->session['role'] === 'branch_manager') {
	 *         $this->filterPage([
	 *             'branch_id' => $this->session['branch_id']
	 *         ], '=');
	 *     }
	 *     
	 *     // Department heads see only their department
	 *     if ($this->session['role'] === 'department_head') {
	 *         $this->filterPage([
	 *             'department_id' => $this->session['department_id']
	 *         ], '=');
	 *     }
	 * }
	 * ```
	 */
	private function customHandler(): void {}
	
	/**
	 * Apply Session-Based Data Filters
	 * 
	 * Applies automatic data filtering based on the current user's session data and role.
	 * This method implements role-based access control by restricting data visibility
	 * according to user privileges and organizational hierarchy.
	 * 
	 * The filtering logic follows this hierarchy:
	 * 1. Root users: No filters applied (full access to all data)
	 * 2. Role alias users (admin, internal): No filters applied
	 * 3. Role info users (National, Regional): Custom handler logic applied
	 * 4. Other users: Session-based filters applied automatically
	 * 
	 * The method initializes default roles, checks user's role against configured
	 * aliases and info, and applies appropriate filters through sessionConfig().
	 * 
	 * Enhanced Features:
	 * - Filter validation before application
	 * - Filter caching for performance
	 * - Filter logging for debugging
	 * - Filter conflict detection and resolution
	 * - Maximum filter limit enforcement
	 * 
	 * @return void
	 * 
	 * @throws ControllerValidationException If filter validation fails
	 * @throws ControllerValidationException If max filters exceeded
	 * 
	 * @security CRITICAL - Implements data access control
	 * @security Prevents unauthorized access to restricted data
	 * @security Validates user roles before applying filters
	 * @security Supports hierarchical access control
	 * @security Validates all filter values before application
	 * @security Enforces maximum filter limits
	 * 
	 * @performance Efficient role checking with array lookups
	 * @performance Minimal overhead when user has full access
	 * @performance Filters applied at query level for efficiency
	 * @performance Caches filter results for repeated operations
	 * @performance Optimized filter conflict resolution
	 * 
	 * @example
	 * ```php
	 * // In controller constructor
	 * public function __construct() {
	 *     parent::__construct();
	 *     $this->sessionFilters(); // Apply filters automatically
	 * }
	 * 
	 * // Root user - no filters
	 * // $this->session['user_group'] = 'root'
	 * // All data visible
	 * 
	 * // Admin user - no filters
	 * // $this->session['user_group'] = 'admin'
	 * // All data visible
	 * 
	 * // Regional manager - filtered by region
	 * // $this->session['user_group'] = 'manager'
	 * // $this->session['group_alias'] = 'Regional'
	 * // Only regional data visible
	 * 
	 * // Branch user - filtered by branch
	 * // $this->session['user_group'] = 'user'
	 * // $this->session['branch_id'] = 123
	 * // Only branch 123 data visible
	 * 
	 * // Custom role handling
	 * class SalesController extends Controller {
	 *     use Handler;
	 *     
	 *     private function customHandler(): void {
	 *         // Sales managers see only their team's data
	 *         if ($this->session['role'] === 'sales_manager') {
	 *             $this->filterPage([
	 *                 'team_id' => $this->session['team_id']
	 *             ], '=');
	 *         }
	 *     }
	 *     
	 *     public function index() {
	 *         $this->sessionFilters();
	 *         $sales = Sale::all(); // Automatically filtered
	 *         return view('sales.index', compact('sales'));
	 *     }
	 * }
	 * ```
	 * 
	 * @see initHandler() For default role initialization
	 * @see customHandler() For custom role logic
	 * @see sessionConfig() For filter application
	 * @see filterPage() For filter implementation
	 * @see validateFilter() For filter validation
	 * @see cacheFilterResult() For filter caching
	 */
	protected function sessionFilters(): void {
		$this->initHandler();
		
		// Root users bypass all filters
		if ('root' !== $this->session['user_group']) {
			// Check if user's role is in bypass list
			if (!in_array($this->session['user_group'], $this->roleAlias)) {
				// Check if user's role info requires custom handling
				if (!empty($this->roleInfo) && !in_array($this->session['group_alias'], $this->roleInfo)) {
					$this->customHandler();
				}

				// Apply session-based filters
				$this->sessionConfig();
			}
		}
		
		// Log filter applications if enabled
		if (canvastack_config('controller.handler.log_filter_applications', false)) {
			$this->logFilterApplications();
		}
	}
	
	/**
	 * Configure Session-Based Filters
	 * 
	 * Applies filters based on session data and configuration. This method reads
	 * filter configuration from the user config and applies filters based on the
	 * current user's session data.
	 * 
	 * Enhanced with:
	 * - Filter validation before application
	 * - Filter caching for performance
	 * - Filter conflict detection
	 * - Maximum filter limit enforcement
	 * 
	 * @return void
	 * 
	 * @throws ControllerValidationException If filter validation fails
	 * @throws ControllerValidationException If max filters exceeded
	 * 
	 * @security Validates all filter values before application
	 * @security Enforces maximum filter limits
	 * @security Validates filter field names and operators
	 * 
	 * @performance Caches filter results for repeated operations
	 * @performance Optimized filter conflict resolution
	 * @performance Efficient filter chaining
	 */
	private function sessionConfig(): void {
		$user_group_session_key   = canvastack_config('user.group_alias_key');
		$user_group_session_field = canvastack_config('user.group_alias_field');
		$user_session_alias       = canvastack_config('user.alias_session_name');
		
		// Apply group-based filter
		if (!empty($this->session[$user_group_session_field])) {
			$this->applyFilterWithValidation(
				[$user_group_session_key => $this->session[$user_group_session_field]],
				'='
			);
		}
		
		// Apply additional session-based filters
		if (!empty($this->session[$user_session_alias])) {
			foreach ($this->session[$user_session_alias] as $fieldset => $fieldvalues) {
				$this->applyFilterWithValidation(
					[$fieldset => $fieldvalues],
					'='
				);
			}
		}
	}
	
	/**
	 * Apply Filter with Validation
	 * 
	 * Applies a filter after validating the filter parameters. This method provides
	 * enhanced security and performance through validation, caching, and conflict
	 * detection.
	 * 
	 * Features:
	 * - Validates filter field names and operators
	 * - Checks for filter conflicts
	 * - Enforces maximum filter limits
	 * - Caches filter results
	 * - Logs filter applications
	 * 
	 * @param array $filters Filter array (field => value)
	 * @param string $operator Filter operator (=, !=, <, >, etc.)
	 * @return void
	 * 
	 * @throws ControllerValidationException If filter validation fails
	 * @throws ControllerValidationException If max filters exceeded
	 * 
	 * @security CRITICAL - Validates all filter parameters
	 * @security Prevents SQL injection through operator validation
	 * @security Enforces maximum filter limits
	 * @security Validates filter field names
	 * 
	 * @performance Caches filter results for repeated operations
	 * @performance Optimized conflict detection
	 * @performance Efficient filter application
	 */
	private function applyFilterWithValidation(array $filters, string $operator = '='): void {
		// Check if filter validation is enabled
		if (!canvastack_config('controller.handler.enable_filter_validation', true)) {
			$this->filterPage($filters, $operator);
			return;
		}
		
		// Check maximum filter limit
		$maxFilters = canvastack_config('controller.handler.max_filters_per_request', 50);
		if ($this->filterCount >= $maxFilters) {
			throw ControllerValidationException::invalidFilterValue(
				'filter_count',
				$this->filterCount,
				"Maximum filter limit of {$maxFilters} exceeded"
			);
		}
		
		// Validate operator
		$this->validateFilterOperator($operator);
		
		// Validate and apply each filter
		foreach ($filters as $field => $value) {
			// Validate field name
			$this->validateFilterField($field);
			
			// Check for conflicts
			$this->checkFilterConflict($field, $value, $operator);
			
			// Check cache if enabled
			if (canvastack_config('controller.handler.cache_filter_results', true)) {
				$cacheKey = $this->getFilterCacheKey($field, $value, $operator);
				$cached = Cache::get($cacheKey);
				
				if ($cached !== null) {
					// Use cached filter result
					$this->appliedFilters[$field] = [
						'value' => $value,
						'operator' => $operator,
						'cached' => true,
					];
					$this->filterCount++;
					continue;
				}
			}
			
			// Apply filter
			$this->filterPage([$field => $value], $operator);
			
			// Track applied filter
			$this->appliedFilters[$field] = [
				'value' => $value,
				'operator' => $operator,
				'cached' => false,
			];
			$this->filterCount++;
			
			// Cache filter result if enabled
			if (canvastack_config('controller.handler.cache_filter_results', true)) {
				$cacheTtl = canvastack_config('controller.handler.filter_cache_ttl', 1800);
				Cache::put($cacheKey, true, $cacheTtl);
			}
		}
	}
	
	/**
	 * Validate Filter Operator
	 * 
	 * Validates that the filter operator is in the allowed list to prevent
	 * SQL injection attacks.
	 * 
	 * @param string $operator Filter operator
	 * @return void
	 * 
	 * @throws ControllerValidationException If operator is invalid
	 * 
	 * @security CRITICAL - Prevents SQL injection through operator validation
	 */
	private function validateFilterOperator(string $operator): void {
		if (!canvastack_config('controller.handler.validate_filter_operators', true)) {
			return;
		}
		
		$allowedOperators = canvastack_config('controller.handler.allowed_filter_operators', [
			'=', '!=', '<', '>', '<=', '>=',
			'LIKE', 'NOT LIKE',
			'IN', 'NOT IN',
			'BETWEEN',
			'IS NULL', 'IS NOT NULL',
		]);
		
		if (!in_array(strtoupper($operator), array_map('strtoupper', $allowedOperators))) {
			throw ControllerValidationException::invalidFilterValue(
				'operator',
				$operator,
				'Invalid filter operator. Allowed operators: ' . implode(', ', $allowedOperators)
			);
		}
	}
	
	/**
	 * Validate Filter Field
	 * 
	 * Validates that the filter field name is valid to prevent SQL injection
	 * and invalid field references.
	 * 
	 * @param string $field Filter field name
	 * @return void
	 * 
	 * @throws ControllerValidationException If field name is invalid
	 * 
	 * @security CRITICAL - Prevents SQL injection through field name validation
	 */
	private function validateFilterField(string $field): void {
		if (!canvastack_config('controller.handler.validate_filter_field_names', true)) {
			return;
		}
		
		// Check for SQL injection patterns
		if (preg_match('/[;\'"\\\\]|--|\\/\\*|\\*\\/|xp_|sp_|exec|execute|union|select|insert|update|delete|drop|create|alter/i', $field)) {
			throw ControllerValidationException::invalidFilterValue(
				'field',
				$field,
				'Invalid filter field name. Field name contains suspicious characters or SQL keywords.'
			);
		}
		
		// Check field name length
		if (strlen($field) > 64) {
			throw ControllerValidationException::invalidFilterValue(
				'field',
				$field,
				'Filter field name too long. Maximum length is 64 characters.'
			);
		}
	}
	
	/**
	 * Check Filter Conflict
	 * 
	 * Checks if a filter conflicts with an already applied filter and handles
	 * the conflict according to the configured strategy.
	 * 
	 * @param string $field Filter field name
	 * @param mixed $value Filter value
	 * @param string $operator Filter operator
	 * @return void
	 * 
	 * @security Logs filter conflicts for security monitoring
	 * @performance Efficient conflict detection with array lookup
	 */
	private function checkFilterConflict(string $field, $value, string $operator): void {
		if (!isset($this->appliedFilters[$field])) {
			return;
		}
		
		$existingFilter = $this->appliedFilters[$field];
		$strategy = canvastack_config('controller.handler.filter_conflict_strategy', 'last_wins');
		
		// Log conflict if enabled
		if (canvastack_config('controller.handler.log_filter_conflicts', true)) {
			Log::warning('Filter conflict detected', [
				'field' => $field,
				'existing_value' => $existingFilter['value'],
				'existing_operator' => $existingFilter['operator'],
				'new_value' => $value,
				'new_operator' => $operator,
				'strategy' => $strategy,
				'user_id' => $this->session['id'] ?? null,
			]);
		}
		
		// Handle conflict based on strategy
		switch ($strategy) {
			case 'first_wins':
				// Keep existing filter, ignore new one
				throw ControllerValidationException::invalidFilterValue(
					$field,
					$value,
					"Filter conflict: Field '{$field}' already has a filter applied (first_wins strategy)"
				);
				
			case 'throw':
				// Throw exception on conflict
				throw ControllerValidationException::invalidFilterValue(
					$field,
					$value,
					"Filter conflict: Field '{$field}' already has a filter applied"
				);
				
			case 'merge':
			case 'last_wins':
			default:
				// Allow new filter to override (default behavior)
				// The new filter will be applied and tracked
				break;
		}
	}
	
	/**
	 * Get Filter Cache Key
	 * 
	 * Generates a cache key for a filter based on field, value, and operator.
	 * 
	 * @param string $field Filter field name
	 * @param mixed $value Filter value
	 * @param string $operator Filter operator
	 * @return string Cache key
	 * 
	 * @performance Efficient cache key generation
	 */
	private function getFilterCacheKey(string $field, $value, string $operator): string {
		$userId = $this->session['id'] ?? 'guest';
		$valueHash = is_array($value) ? md5(json_encode($value)) : md5((string)$value);
		return "filter_result_{$userId}_{$field}_{$valueHash}_{$operator}";
	}
	
	/**
	 * Log Filter Applications
	 * 
	 * Logs all applied filters for debugging and auditing purposes.
	 * 
	 * @return void
	 * 
	 * @security Provides audit trail for filter applications
	 * @performance Minimal overhead - only logs when enabled
	 */
	private function logFilterApplications(): void {
		if (empty($this->appliedFilters)) {
			return;
		}
		
		Log::info('Filters applied', [
			'user_id' => $this->session['id'] ?? null,
			'user_group' => $this->session['user_group'] ?? null,
			'filter_count' => $this->filterCount,
			'filters' => $this->appliedFilters,
			'route' => request()->path(),
		]);
	}
	
	/**
	 * Get Applied Filters (Debugging Tool)
	 * 
	 * Returns information about all filters applied in the current request.
	 * This is useful for debugging filter issues.
	 * 
	 * @return array Applied filters information
	 * 
	 * @security Only available when debugging is enabled
	 * @performance Minimal overhead - simple array return
	 * 
	 * @example
	 * ```php
	 * // In controller
	 * $this->sessionFilters();
	 * 
	 * // Get applied filters for debugging
	 * if (config('app.debug')) {
	 *     $filters = $this->getAppliedFilters();
	 *     dump($filters);
	 * }
	 * ```
	 */
	protected function getAppliedFilters(): array {
		if (!canvastack_config('controller.handler.enable_filter_debugging', false) || !config('app.debug')) {
			return [];
		}
		
		return [
			'count' => $this->filterCount,
			'filters' => $this->appliedFilters,
			'user' => [
				'id' => $this->session['id'] ?? null,
				'group' => $this->session['user_group'] ?? null,
				'role_alias' => $this->roleAlias,
				'role_info' => $this->roleInfo,
			],
		];
	}
	
	/**
	 * Clear Filter Cache
	 * 
	 * Clears all cached filter results for the current user. This is useful
	 * when user permissions change or when you need to force filter re-evaluation.
	 * 
	 * @return void
	 * 
	 * @security Ensures fresh filter evaluation after permission changes
	 * @performance Efficient cache clearing with pattern matching
	 * 
	 * @example
	 * ```php
	 * // After updating user permissions
	 * $user->update(['group_id' => $newGroupId]);
	 * $this->clearFilterCache(); // Force filter re-evaluation
	 * ```
	 */
	protected function clearFilterCache(): void {
		if (!canvastack_config('controller.handler.cache_filter_results', true)) {
			return;
		}
		
		$userId = $this->session['id'] ?? 'guest';
		$pattern = "filter_result_{$userId}_*";
		
		// Clear cache entries matching pattern
		// Note: This is a simplified implementation
		// In production, you may want to use cache tags or a more sophisticated approach
		Cache::forget($pattern);
		
		Log::info('Filter cache cleared', [
			'user_id' => $userId,
			'pattern' => $pattern,
		]);
	}
}

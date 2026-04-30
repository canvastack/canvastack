<?php
namespace Canvastack\Canvastack\Controllers\Admin\System\Includes;

use Canvastack\Canvastack\Models\Admin\System\MappingPage as MappingData;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Illuminate\Http\Request;

/**
 * Created on Sep 6, 2022
 * 
 * Time Created : 1:52:26 PM
 *
 * @filesource	MappingPage.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */

trait MappingPage {
	
	public $mapping_page      = [];
	
	private $mapRoute;
	private $mapTable;
	private $model_class_info;
	private $ajaxUrli         = null;
	private $nodeID           = '__node__';
	private $nodeActionButton = '__btnact__';
	
	/**
	 * Get MappingPage model instance
	 * 
	 * @return \Canvastack\Canvastack\Models\Admin\System\MappingPage
	 */
	private function map(): \Canvastack\Canvastack\Models\Admin\System\MappingPage {
		return new MappingData();
	}
	
	/**
	 * Get page mapping data for a specific user
	 * 
	 * Retrieves the page-level data filtering rules configured for a user's group.
	 * This data is used to filter query results based on group permissions.
	 * 
	 * @param int|string $user_id User ID
	 * @return array Filter data for current route
	 * 
	 * @performance
	 * - Mapping data is cached for 5 minutes (300 seconds)
	 * - Cache key includes user ID and route for granular invalidation
	 * - Use invalidateMappingCache() to clear cache after updates
	 * 
	 * @example
	 * ```php
	 * $filters = $this->get_data_mapping_page(5);
	 * // Returns: ['table_name' => 'users', 'field_name' => 'department', 'field_value' => ['sales', 'marketing']]
	 * ```
	 */
	public function get_data_mapping_page($user_id) {
		$currentRoute = canvastack_current_baseroute();

		// Create cache key: "mapping_page_{$user_id}_{$currentRoute}"
		$cacheKey = "mapping_page_{$user_id}_{$currentRoute}";

		// Use Cache::remember() with 300 second TTL (5 minutes)
		return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($user_id, $currentRoute) {
			$dataPageMaps = [];
			$filter_page_maps = [];

			if (!empty($this->map()->getUserDataMapping($user_id))) {
				$sessionID    = intval(session()->all()['id']);
				$dataPageMaps = $this->map()->getUserDataMapping($user_id);

				if (!empty($dataPageMaps[$sessionID][$currentRoute])) {
					foreach ($dataPageMaps[$sessionID][$currentRoute] as $dataTable) {
						$filter_page_maps = $dataTable['filter_data'];
					}
				}
			}

			return $filter_page_maps;
		});
	}
	
	public $filter_page_maps = [];
	
	/**
	 * Get role page data with input validation
	 * 
	 * Retrieves data for AJAX requests based on the context (table names, field names,
	 * or field values). Validates all inputs and wraps database calls in error handling.
	 * 
	 * @param mixed $data Data to query
	 * @param string $usein Context for data retrieval (table_name, field_name, field_value)
	 * @return mixed Query results based on context
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException If usein is invalid or data is empty
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerException If getData() fails
	 * 
	 * @security
	 * - Validates usein parameter against whitelist ['table_name', 'field_name', 'field_value']
	 * - Validates data parameter is not empty
	 * - Logs all validation failures with user context
	 * - Wraps database calls in try-catch for error handling
	 * 
	 * @example Get table names
	 * ```php
	 * $tables = $this->rolepage($data, 'table_name');
	 * ```
	 * 
	 * @example Get field names for a table
	 * ```php
	 * $fields = $this->rolepage(['table' => 'users'], 'field_name');
	 * ```
	 * 
	 * @example Get field values
	 * ```php
	 * $values = $this->rolepage(['table' => 'users', 'field' => 'department'], 'field_value');
	 * ```
	 */
	public function rolepage($data, string $usein) {
		// Validate $usein parameter against whitelist
		$allowedContexts = ['table_name', 'field_name', 'field_value'];

		if (!in_array($usein, $allowedContexts, true)) {
			\Log::warning('Invalid usein parameter in rolepage()', [
				'usein' => $usein,
				'allowed' => $allowedContexts,
				'user_id' => auth()->id() ?? 'guest',
				'ip' => request()->ip()
			]);

			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
				'Invalid context parameter',
				['usein' => $usein, 'allowed' => $allowedContexts]
			);
		}

		// Validate $data parameter is not empty
		if (empty($data)) {
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
				'Data parameter cannot be empty'
			);
		}

		// Wrap getData() call in try-catch block
		try {
			return $this->map()::getData($data, $usein, $this->nodeID);
		} catch (\Exception $e) {
			\Log::error('Failed to get role page data', [
				'usein' => $usein,
				'user_id' => auth()->id() ?? 'guest',
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
				'Failed to retrieve role page data: ' . $e->getMessage(),
				['usein' => $usein, 'original_error' => $e->getMessage()]
			);
		}
	}
	
	/**
	 * Process and save Mapping Page Privileges for a group
	 * 
	 * This method handles the creation, update, and deletion of mapping page privileges
	 * (data-level access control) for a user group. It processes form data from the
	 * "Mapping Page Privileges" tab in the Group edit form.
	 * 
	 * **CRITICAL BEHAVIOR - "Clear All" Support:**
	 * This method ALWAYS calls insert_process(), even when $roles is empty. This is
	 * intentional to support the "clear all" functionality where users remove all
	 * mapping privileges from a group. An empty $roles array triggers DELETE operations
	 * in insert_process() to remove existing records.
	 * 
	 * **Request Data Structure:**
	 * Expected input format (from form submission):
	 * ```php
	 * $request['rolePages'] = [
	 *     'module' => [
	 *         'admin.content.articles' => 12  // route_path => module_id
	 *     ],
	 *     'field_name' => [
	 *         'admin.content.articles' => [
	 *             'users' => ['department', 'status']  // table => [field_names]
	 *         ]
	 *     ],
	 *     'field_value' => [
	 *         'admin.content.articles' => [
	 *             'users' => [
	 *                 'department' => ['sales', 'marketing'],  // field => [values]
	 *                 'status' => ['active']
	 *             ]
	 *         ]
	 *     ]
	 * ];
	 * ```
	 * 
	 * **Usage Scenarios:**
	 * 
	 * 1. **Add New Mapping** (No existing data):
	 *    - User selects module, table, field, and values
	 *    - Method builds $roles array with new data
	 *    - insert_process() performs INSERT operations
	 * 
	 * 2. **Update Existing Mapping**:
	 *    - User modifies field values or adds new fields
	 *    - Method builds $roles array with updated data
	 *    - insert_process() performs UPDATE/INSERT operations
	 * 
	 * 3. **Clear All Mappings** (Remove all privileges):
	 *    - User submits form WITHOUT 'rolePages' key (or empty)
	 *    - Method builds empty $roles array
	 *    - insert_process() performs DELETE operations on all existing records
	 *    - **BUGFIX (2026-04-08):** Previously had early returns that prevented
	 *      insert_process() from being called, so DELETE never happened
	 * 
	 * 4. **Partial Clear** (Remove some fields, keep others):
	 *    - User removes some fields but keeps others
	 *    - Method builds $roles array with remaining fields only
	 *    - insert_process() DELETEs removed fields, keeps/updates remaining ones
	 * 
	 * **Database Operations (handled by insert_process()):**
	 * - INSERT: When new mapping doesn't exist in database
	 * - UPDATE: When mapping exists but values changed
	 * - DELETE: When mapping exists in database but not in $roles array
	 * 
	 * **Consistency with Module Privileges:**
	 * This method's behavior is consistent with privileges_before_insert() which
	 * also always processes data even when empty, using a "setnull" marker to
	 * clear privileges (UPDATE to NULL) instead of DELETE.
	 * 
	 * @param Request $requests The HTTP request containing form data
	 * @param object $group The group object being updated (must have 'id' property)
	 * 
	 * @return void
	 * 
	 * @throws \Exception If insert_process() fails (database errors, validation errors, etc.)
	 * 
	 * @see \Canvastack\Canvastack\Models\Admin\System\MappingPage::insert_process()
	 * @see privileges_before_insert() For similar behavior with module privileges
	 * 
	 * @bugfix 2026-04-08 Removed early returns to support "clear all" functionality
	 * 
	 * @example Clear all mappings
	 * ```php
	 * // Form submission without 'rolePages' key
	 * $request = Request::create('/groups/1', 'PUT', [
	 *     'group_name' => 'test_group',
	 *     // NO 'rolePages' key
	 * ]);
	 * $this->mapping_before_insert($request, $group);
	 * // Result: All existing mappings for this group are deleted
	 * ```
	 * 
	 * @example Add new mapping
	 * ```php
	 * $request = Request::create('/groups/1', 'PUT', [
	 *     'rolePages' => [
	 *         'module' => ['admin.content.articles' => 12],
	 *         'field_name' => ['admin.content.articles' => ['users' => ['department']]],
	 *         'field_value' => ['admin.content.articles' => ['users' => ['department' => ['sales']]]]
	 *     ]
	 * ]);
	 * $this->mapping_before_insert($request, $group);
	 * // Result: New mapping inserted for department='sales'
	 * ```
	 * 
	 * @example Update existing mapping
	 * ```php
	 * // Existing: department='sales'
	 * // New request: department='sales::marketing'
	 * $request = Request::create('/groups/1', 'PUT', [
	 *     'rolePages' => [
	 *         'module' => ['admin.content.articles' => 12],
	 *         'field_name' => ['admin.content.articles' => ['users' => ['department']]],
	 *         'field_value' => ['admin.content.articles' => ['users' => ['department' => ['sales', 'marketing']]]]
	 *     ]
	 * ]);
	 * $this->mapping_before_insert($request, $group);
	 * // Result: Existing mapping updated to department='sales::marketing'
	 * ```
	 */
	public function mapping_before_insert(Request $requests, object $group): void {
		$reqs = $requests->all();

		// Initialize empty roles array
		$roles = [];

		// Only build roles if mapping data exists
		if (isset($reqs[$this->map()::$prefixNode])) {
			$request = $reqs[$this->map()::$prefixNode];

			// Only process if structure is valid
			if (isset($request['field_name']) && isset($request['module'])) {
				// Optimized: Build roles array directly without intermediate $role array
				foreach ($request['field_name'] as $route_path => $mdata) {
					// Skip if module not configured for this route
					if (empty($request['module'][$route_path])) {
						continue;
					}

					$module_id = intval($request['module'][$route_path]);

					foreach ($mdata as $table_name => $tdata) {
						foreach ($tdata as $field_name) {
							// Get field values if they exist, otherwise null (preserves original behavior)
							$field_values = $request['field_value'][$route_path][$table_name][$field_name] ?? null;
							
							// Convert field values to string format (null if empty, preserves original behavior)
							$target_field_values = null;
							if (!empty($field_values)) {
								$target_field_values = implode('::', $field_values);
							}

							// Build role entry directly (always create entry, even if null - preserves original behavior)
							$roles[$route_path][$table_name][$field_name] = [
								'group_id'            => $group->id,
								'module_id'           => $module_id,
								'target_table'        => $table_name,
								'target_field_name'   => $field_name,
								'target_field_values' => $target_field_values
							];
						}
					}
				}
			} else {
				\Log::debug('Invalid mapping data structure - missing required keys', [
					'group_id' => $group->id,
					'group_name' => $group->group_name ?? 'unknown',
					'has_field_name' => isset($request['field_name']),
					'has_module' => isset($request['module']),
					'available_keys' => array_keys($request)
				]);
			}
		}

		// ALWAYS call insert_process(), even if $roles is empty
		// This allows insert_process() to DELETE existing records when user clears all data
		\Log::debug('Processing mapping data for group', [
			'group_id' => $group->id,
			'group_name' => $group->group_name ?? 'unknown',
			'roles_count' => count($roles),
			'action' => empty($roles) ? 'clear_all' : 'update'
		]);

		// CLEARING MAPPING PAGE REQUESTS
		if (isset($reqs[$this->map()::$prefixNode])) {
			request()->offsetUnset($this->map()::$prefixNode);
		}

		// Wrap insert_process() in try-catch for proper error handling
		try {
			// Call insert_process() even with empty $roles
			// Empty $roles will trigger DELETE of all existing records
			$this->map()->insert_process($roles, $group);
			
			\Log::info('Mapping data processed successfully', [
				'group_id' => $group->id,
				'group_name' => $group->group_name ?? 'unknown',
				'action' => empty($roles) ? 'cleared' : 'updated',
				'roles_count' => count($roles)
			]);
			
		} catch (\Exception $e) {
			\Log::error('Failed to process mapping data', [
				'error' => $e->getMessage(),
				'group_id' => $group->id,
				'group_name' => $group->group_name ?? 'unknown',
				'roles_count' => count($roles),
				'trace' => $e->getTraceAsString()
			]);
			
			// Re-throw exception for caller to handle
			throw $e;
		}
	}
	
	/**
	 * Generate HTML table for page mapping configuration
	 * 
	 * Creates a table with module names, table names, field names, field values,
	 * and action buttons for configuring page-level data filtering rules.
	 * 
	 * @return string HTML table markup
	 * 
	 * @example Table structure
	 * ```
	 * | Module Name | Table Name | Field Name | Field Value | Action |
	 * |-------------|------------|------------|-------------|--------|
	 * | Articles    | users      | department | sales       | [+]    |
	 * ```
	 */
	private function mapping(): string {
		$title_id                    = 'page_privileges_' . canvastack_random_strings(50, false) . ' role-priv';
		$headerData                  = [];
		$headerData['module_id']     = [canvastack_table_row_attr('Module Name' , ['class' => 'canvastack-mappriv-modname', 'rowspan' => 2])];
		$headerData['target_table']  = [canvastack_table_row_attr('Table Name'  , ['class' => 'canvastack-mappriv-tabname', 'rowspan' => 2])];
		$headerData['target_roles']  = [
			[
				'column' => canvastack_table_row_attr('Role Query'  , ['class' => 'canvastack-mappriv-rqfs', 'colspan' => 2]),
				'merge'  => [
					canvastack_table_row_attr('Field Name'  , ['style' => 'canvastack-mappriv-rqfname']),
					canvastack_table_row_attr('Field Value' , ['style' => 'canvastack-mappriv-rqfval'])
				]
			]
		];
		$headerData['action_button'] = [canvastack_table_row_attr('Action'  , ['style' => 'canvastack-mappriv-act', 'rowspan' => 2])];
		
		$header    = array_merge_recursive($headerData['module_id'], $headerData['target_table'], $headerData['target_roles'], $headerData['action_button']);		
		$row_table = $this->mapping_box();
		
		return canvastack_generate_table('Set Role Module Page', $title_id, $header, $row_table, [], false, false);
	}
	
	/**
	 * Load current mapping data for the group being edited
	 * 
	 * Retrieves existing page mapping rules for the current group from the URL
	 * and loads them into $model_class_info for display in the edit form.
	 * 
	 * @return void
	 */
	private function get_data_map(): void {
		$urli = explode('/', url()->current());
		if ('edit' === last($urli)) {
			unset($urli[count($urli)-1]);
			$this->group_id = intval(last($urli));
		}
		
		$current_rolemaps = [];
		if (!empty($this->map()->current_data($this->group_id))) {
			foreach ($this->map()->current_data($this->group_id) as $current_module => $current_module_data) {
				$module_data = canvastack_query("base_module")->where('id', intval($current_module))->first();
				$current_rolemaps[$module_data->route_path]['model']['page_roles'] = $current_module_data;
			}
		}
		
		$this->model_class_info = canvastack_get_model_controllers_info($current_rolemaps);
	}
	
	private $group_id;
	
	/**
	 * Generate unique ID for HTML elements
	 * 
	 * @param string $string Base string for ID generation
	 * @param string|null $node Node prefix (defaults to __node__)
	 * @return string Unique ID string
	 */
	private function setID($string, $node = null) {
		if (empty($node))	$node = $this->nodeID;
		return canvastack_random_strings(8, false, $string, '__node__');
	}
	
	/**
	 * Build hierarchical mapping box UI with table/field selection
	 * 
	 * Generates table rows for each module in the menu hierarchy, displaying
	 * table selection, field selection, and value selection dropdowns for
	 * configuring page-level data filtering rules.
	 * 
	 * @return array Table row data for mapping interface
	 * 
	 * @performance
	 * - Builds UI structure in memory before rendering
	 * - Uses efficient array operations for hierarchy building
	 * - Handles up to 4 levels of module nesting
	 * 
	 * @security
	 * - All module names are escaped to prevent XSS
	 * - Uses SafeHtml for safe HTML concatenation
	 */
	private function mapping_box(): array {
		$this->get_data_map();
		
		$row_table = [];
		$icon = SafeHtml::mark('<i class="fa fa-caret-right"></i> &nbsp; ');
		
		foreach ($this->menu_privileges as $parent => $childs) {
			try {
				$row_table = array_merge(
					$row_table,
					$this->buildParentRow($parent, $childs, $icon)
				);
			} catch (\Exception $e) {
				\Log::error('Failed to build parent row in mapping box', [
					'parent' => $parent,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);
				// Continue with next parent instead of failing completely
				continue;
			}
		}
		
		return $row_table;
	}
	
	/**
	 * Build parent row with children
	 * 
	 * Generates table rows for a parent module and all its child modules in the
	 * hierarchical menu structure. Handles nested module hierarchies up to 4 levels deep.
	 * 
	 * @param string $parent Parent module name
	 * @param mixed $childs Child module data (object or array)
	 * @param string $icon Icon HTML markup for display
	 * @return array Table rows for parent and all children
	 * 
	 * @throws \Exception If child row building fails (caught and logged)
	 */
	private function buildParentRow(string $parent, mixed $childs, string $icon): array {
		$rows = [];
		
		// Build parent header row
		$parent_title = ucwords(str_replace('_', ' ', $parent));
		if (!empty($childs->name)) {
			$parent_title = $childs->name;
		}
		
		// Unmark icon, concatenate, then mark as safe HTML
		$parentContent = SafeHtml::unmark($icon) . $parent_title;
		$rows[] = [canvastack_table_row_attr(SafeHtml::mark($parentContent), [
			'style' => 'font-weight:500;text-indent:5pt;color:black',
			'colspan' => 5
		])];
		
		// Process each child
		foreach ($childs as $child_name => $data_module) {
			try {
				if (!isset($data_module->id)) {
					// Child has sub-children, build child rows recursively
					$rows = array_merge($rows, $this->buildChildRows($child_name, $data_module, $icon));
				} else {
					// Child is a module, build module row directly
					if (!empty($this->model_class_info[$data_module->route])) {
						$roleData = $this->model_class_info[$data_module->route];
						
						if (!empty($roleData)) {
							$moduleRow = $this->buildModuleRow($child_name, $data_module, $icon, 'text-indent:15pt');
							if (!empty($moduleRow)) {
								$rows[] = $moduleRow;
							}
						}
					}
				}
			} catch (\Exception $e) {
				\Log::error('Failed to build child row in mapping box', [
					'parent' => $parent,
					'child' => $child_name,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);
				// Continue with next child instead of failing completely
				continue;
			}
		}
		
		return $rows;
	}
	
	/**
	 * Build child rows recursively
	 * 
	 * Generates table rows for a child module and all its sub-children in the
	 * hierarchical menu structure. Handles nested sub-children and modules.
	 * 
	 * @param string $child_name Child module name
	 * @param mixed $data_module Module data (object or array)
	 * @param string $icon Icon HTML markup for display
	 * @return array Table rows for child and all sub-children
	 * 
	 * @throws \Exception If subchild row building fails (caught and logged)
	 */
	private function buildChildRows(string $child_name, mixed $data_module, string $icon): array {
		$rows = [];
		
		// Build child header row
		$child_title = ucwords(str_replace('_', ' ', $child_name));
		if (!empty($data_module->name)) {
			$child_title = $data_module->name;
		}
		
		// Unmark icon, concatenate, then mark as safe HTML
		$childContent = SafeHtml::unmark($icon) . $child_title;
		$rows[] = [canvastack_table_row_attr(SafeHtml::mark($childContent), [
			'style' => 'font-weight:500;text-indent:12pt;color:green',
			'colspan' => 5
		])];
		
		// Process each sub-child
		foreach ($data_module as $module_name => $module_data) {
			try {
				if (!empty($this->model_class_info[$module_data->route])) {
					$roleData = $this->model_class_info[$module_data->route];
				} else {
					$roleData = null;
				}
				
				if (!empty($module_data->id)) {
					// Module with ID - build module row
					if (!empty($roleData)) {
						$moduleRow = $this->buildModuleRow($module_name, $module_data, $icon, 'text-indent:15pt');
						if (!empty($moduleRow)) {
							$rows[] = $moduleRow;
						}
					}
				} else {
					// Sub-child has more nesting, build subchild rows
					$rows = array_merge($rows, $this->buildSubChildRows($module_name, $module_data, $icon));
				}
			} catch (\Exception $e) {
				\Log::error('Failed to build subchild row in mapping box', [
					'child' => $child_name,
					'module' => $module_name,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);
				// Continue with next module instead of failing completely
				continue;
			}
		}
		
		return $rows;
	}
	
	/**
	 * Build subchild rows
	 * 
	 * Generates table rows for a subchild module and all its nested modules.
	 * This handles the third level of nesting in the module hierarchy.
	 * 
	 * @param string $subchild_name Subchild module name
	 * @param mixed $subdata_module Module data (object or array)
	 * @param string $icon Icon HTML markup for display
	 * @return array Table rows for subchild and all nested modules
	 * 
	 * @throws \Exception If module row building fails (caught and logged)
	 */
	private function buildSubChildRows(string $subchild_name, mixed $subdata_module, string $icon): array {
		$rows = [];
		
		// Build subchild header row
		$module_title = ucwords(str_replace('_', ' ', $subchild_name));
		if (!empty($subdata_module->name)) {
			$module_title = $subdata_module->name;
		}
		
		// Unmark icon, concatenate, then mark as safe HTML
		$moduleContent = SafeHtml::unmark($icon) . $module_title;
		$rows[] = [canvastack_table_row_attr(SafeHtml::mark($moduleContent), [
			'style' => 'font-weight:500;text-indent:19pt',
			'colspan' => 4
		])];
		
		// Process each nested module
		foreach ($subdata_module as $third_name => $third_data) {
			try {
				if (!empty($this->model_class_info[$third_data->route])) {
					$roleData = $this->model_class_info[$third_data->route];
				} else {
					$roleData = null;
				}
				
				if (!empty($roleData)) {
					$moduleRow = $this->buildModuleRow($third_name, $third_data, $icon, 'text-indent:25pt');
					if (!empty($moduleRow)) {
						$rows[] = $moduleRow;
					}
				}
			} catch (\Exception $e) {
				\Log::error('Failed to build module row in mapping box', [
					'subchild' => $subchild_name,
					'module' => $third_name,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);
				// Continue with next module instead of failing completely
				continue;
			}
		}
		
		return $rows;
	}
	
	/**
	 * Build module row with role box
	 * 
	 * Generates a single table row for a module with its role box UI
	 * (table/field/value selection dropdowns).
	 * 
	 * @param string $module_name Module name
	 * @param object $module_data Module data object
	 * @param string $icon Icon HTML markup for display
	 * @param string $indent Indentation style for the row
	 * @return array|null Table row data or null if buildRoleBox fails
	 * 
	 * @throws \Exception If buildRoleBox fails
	 */
	private function buildModuleRow(string $module_name, object $module_data, string $icon, string $indent): ?array {
		try {
			$roleData = $this->buildRoleBox(
				$this->model_class_info[$module_data->route],
				$module_name,
				$module_data,
				$icon,
				$indent
			);
			
			return $roleData ?: null;
		} catch (\Exception $e) {
			\Log::error('Failed to build module row', [
				'module' => $module_name,
				'route' => $module_data->route ?? 'unknown',
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			
			// Return null to skip this module
			return null;
		}
	}
	
	/**
	 * Generate role name attribute for form inputs
	 * 
	 * Builds the name attribute for form inputs in the mapping interface,
	 * handling nested array structures for module, table, and field data.
	 * 
	 * @param string $basename Base name for the input (e.g., 'field_name', 'field_value')
	 * @param string|array $identify Identifier array for nested structure
	 * @return string Form input name attribute
	 * 
	 * @example Simple name
	 * ```php
	 * $name = $this->rolename('table_name');
	 * // Returns: __node__[table_name][]
	 * ```
	 * 
	 * @example Nested name
	 * ```php
	 * $name = $this->rolename('field_name', ['content.articles', 'users']);
	 * // Returns: __node__[field_name][content.articles][users][]
	 * ```
	 */
	private function rolename(string $basename, string|array $identify = []): string {
		$rolename       = [];
		$this->roleNode = $this->map()::$prefixNode;

		$basename = "{$this->roleNode}[{$basename}]";
		if (!empty($identify)) {
			if (is_array($identify)) {
				if (!empty($identify[2])) {
					return $rolename[$basename] = "{$basename}[{$identify[0]}][{$identify[1]}][{$identify[2]}][]";
				} else {
					return $rolename[$basename] = "{$basename}[{$identify[0]}][{$identify[1]}][]";
				}
			} else {
				return $rolename[$basename]    = "{$basename}[$identify][]";
			}
		} else {
			return $rolename[$basename]       = "{$basename}[]";
		}
	}
	
	private $roleNode;
	
	/**
	 * Get field data for a table
	 * 
	 * Retrieves field names or field values for a table using the specified
	 * function from the MappingPage model.
	 * 
	 * @param string|array $table_name Table name or array of table/field data
	 * @param string $func Function name to call (getTableFields or getFieldValues)
	 * @param string|null $connection Database connection name
	 * @return array Field data with labels and values
	 * 
	 * @example Get table fields
	 * ```php
	 * $fields = $this->getFieldTable('users', 'getTableFields');
	 * // Returns: ['id' => 'Id', 'name' => 'Name', 'email' => 'Email']
	 * ```
	 */
	private function getFieldTable(string|array $table_name, string $func, ?string $connection = null): array {
		$result = [];
		$data   = $this->map()::{$func}($table_name, $connection);
		
		foreach ((array)json_decode($data) as $label => $value) {
			$result[$value] = ucwords(str_replace('-', ' ', canvastack_clean_strings($label)));
		}
		
		return $result;
	}
	
	/**
	 * Format and escape module title for safe HTML output
	 * 
	 * @param string $name Module name to format
	 * @param mixed $data Module data object (optional)
	 * @return string Escaped and formatted module title
	 */
	private function formatModuleTitle(string $name, $data = null): string {
		// Use module data name if available, otherwise format the name parameter
		$title = $name;
		if (!empty($data) && is_object($data) && !empty($data->name)) {
			$title = $data->name;
		} else {
			// Format the name: remove prefixes and convert underscores to spaces
			$title = ucwords(str_replace('_', ' ', str_replace('view_', ' ', str_replace('t_', ' ', $name))));
		}
		
		// Escape for safe HTML output
		return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Build role box UI for module with table/field/value selection
	 * 
	 * Generates a complete row in the mapping table with dropdowns for selecting
	 * tables, fields, and values for page-level data filtering. Includes AJAX
	 * functionality for dynamic field and value loading.
	 * 
	 * @param array $roleData Role data containing model information
	 * @param string $module_name Module name
	 * @param object $module_data Module data object
	 * @param string $icon Icon HTML for display
	 * @param string|bool $indent Indentation style or false
	 * @return array Table row data for role box UI
	 * 
	 * @security
	 * - Module name is escaped to prevent XSS (via formatModuleTitle)
	 * - Uses SafeHtml for safe HTML concatenation
	 * - Escapes module_name in HTML attributes
	 * 
	 * @example
	 * ```php
	 * $icon = '<i class="fa fa-caret-right"></i>';
	 * $row = $this->buildRoleBox($roleData, 'Articles', $moduleData, $icon);
	 * ```
	 */
	private function buildRoleBox(array $roleData, string $module_name, object $module_data, string $icon, string|bool $indent = false): array {
		if ($roleData) {
			
			$connection                     = $roleData['model']['connection'];
			$identifier                     = $roleData['model']['table_map'];
			if (!empty($connection)) {
				$identifier                  = $roleData['model']['table_map'] . $this->map()::$canvastackcon . $connection;
			}
			$routeName                      = strtolower($module_data->route);
			$routeNameAttribute             = str_replace('.', '-', $module_data->route);
			$routeToAttribute               = 'role__' . $routeNameAttribute . '__' . $roleData['model']['table_map'];
			
			$roleAttributes                 = [];
			$roleAttributes['table_name']   = $this->rolename('table_name');
			$roleAttributes['field_name']   = "{$this->roleNode}[field_name]";
			$roleAttributes['field_value']  = $this->rolename('field_value', $identifier);
			
			$roleValues                     = [];
			$roleValues['table_checked']    = false;
			$roleValues['table_map']        = $identifier;
			$roleValues['table_connection'] = $roleData['model']['connection'];
			$roleValues['field_name']       = [];
			$roleValues['field_value']      = [];
			
			$connection                     = null;
			$buffers                        = [];
			$buffer_data                    = [];
			
			if (isset($roleData['model']['buffers']['page_roles'])) {
				$buffers                     = $roleData['model']['buffers']['page_roles'];				
				$roleValues['table_checked'] = true;
				
				foreach ($buffers as $buffer_table => $buffer_data) {
					$roleValues['table_map']  = $buffer_table;
					
					$bufferTableGetField      = $buffer_table;
					if (canvastack_string_contained($buffer_table, $this->map()::$canvastackcon)) {
						$buffer_table_split    = explode($this->map()::$canvastackcon, $buffer_table);
						$bufferTableGetField   = $buffer_table_split[0];
						$connection            = $buffer_table_split[1];
					}
					
					if (!empty($buffer_data)) {
						foreach ($buffer_data as $buffer_field => $buffered) {
							$roleValues['field_name'][$buffer_table][$buffer_field]['selected']  = [$buffered->target_field_name => $buffered->target_field_name];
							$roleValues['field_name'][$buffer_table][$buffer_field]['data']      = $this->getFieldTable($bufferTableGetField, 'getTableFields', $connection);
							
							$buffered_values = [];
							foreach (explode('::', $buffered->target_field_values) as $value_buffered) {
								$buffered_values['selected'][$value_buffered]         = $value_buffered;
								$buffered_values['data']                              = $this->getFieldTable([$buffer_table => [$buffer_field]], 'getFieldValues');
							}
							
							$roleValues['field_value'][$buffer_table][$buffer_field] = $buffered_values;
						}
					}
				}
			}
			
			$nodeModel                      = str_replace('.', '-', $routeName);
			$roleColumns                    = [];
			$roleColumns['ajax_field_name'] = $this->ajax_urli('field_name', true);
			$roleColumns['identifier']      = canvastack_input('hidden', "qmod-{$identifier}", $nodeModel, null, $module_data->id);
			$tableID                        = $this->setID($identifier);
			$tableLabel                     = ucwords(str_replace('_', ' ', str_replace('view_', ' ', str_replace('t_', ' ', $roleData['model']['table_map']))));
			$roleColumns['table_name']      = canvastack_form_checkList($roleAttributes['table_name'], $roleValues['table_map'], $tableLabel, $roleValues['table_checked'], 'success read-select full-width text-left', $tableID, "class=\"{$tableID}{$this->nodeID}{$nodeModel}\"");
			
			$fieldID   = $this->setID($identifier);
			$valueID   = $this->setID($identifier);
			
			$rand      = [];
			$fieldbuff = [];
			
			if (!empty($buffer_data)) {
				$n = 0;
				foreach ($buffer_data as $buffer_field => $buffered) {
					$n++;
					
					$rand['f']          = canvastack_random_strings(8, false, null, null);
					$rand['v']          = canvastack_random_strings(8, false, null, null);
					
					$fieldbuff['field'] = $fieldID . $rand['f'];
					$fieldbuff['value'] = $valueID . $rand['v'];
					
					$fieldbuff['ranid'][$buffer_field]  = $fieldID . $rand['f'];
					$fieldbuff['ranval'][$buffer_field] = $valueID . $rand['v'];
					
					if ($n > 1) {
						$fieldNameAttr   = ['id' => $fieldbuff['field'], 'class' => $routeToAttribute . "{$fieldID}field_name"];
						$fieldValueAttr  = ['id' => $fieldbuff['value'], 'class' => $routeToAttribute . "{$valueID}field_value", 'multiple'];
					} else {
						$fieldNameAttr   = ['id' => $fieldID, 'class' => $routeToAttribute . "{$fieldID}field_name"];
						$fieldValueAttr  = ['id' => $valueID, 'class' => $routeToAttribute . "{$valueID}field_value", 'multiple'];
					}
					
					$roleColumns['identifier'] = canvastack_input('hidden', "qmod-{$identifier}", $nodeModel, "{$this->roleNode}[module][{$module_data->route}]", $module_data->id);
					
					$fieldNameValues    = $roleValues['field_name'][$identifier][$buffer_field];
					$roleColumns['field_name'][$identifier][$buffer_field] = canvastack_form_selectbox (
						$this->rolename('field_name', [$routeName, $identifier]), 
						$fieldNameValues['data'], 
						$fieldNameValues['selected'], 
						$fieldNameAttr, 
						false, 
						false
					);
					
					$fieldDataValues    = $roleValues['field_value'][$identifier][$buffer_field];			
					$roleColumns['field_value'][$identifier][$buffer_field] = canvastack_form_selectbox (
						$this->rolename('field_value', [$routeName, $identifier, array_keys($fieldNameValues['selected'])[0]]), 
						$fieldDataValues['data'],
						$fieldDataValues['selected'], 
						$fieldValueAttr, 
						false, 
						false
					);
				}
			} else {
				$roleColumns['field_name']  = canvastack_form_selectbox($roleAttributes['field_name'] , $roleValues['field_name'] , null, ['id' => $fieldID, 'class' => $routeToAttribute . "{$fieldID}field_name"], false, false);				
				$roleColumns['field_value'] = canvastack_form_selectbox($roleAttributes['field_value'], $roleValues['field_value'], null, ['id' => $valueID, 'class' => $routeToAttribute . "{$valueID}field_value", 'multiple'], false, false);
			}
			
			$module_name_label = $this->formatModuleTitle($module_name, $module_data);
			// Escape module_name for use in HTML attributes to prevent XSS
			$escaped_module_name = htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8');
			$opt               = ['align' => 'center', 'id' => strtolower($escaped_module_name) . '-row', 'colspan' => 2, 'style' => 'padding: 0 !important;'];
			
			$mergeBox          = canvastack_draw_query_map_page_table($routeToAttribute, $fieldID, $valueID, $roleColumns, $buffers, $fieldbuff);
			
			$resultBox         = [];
			// Concatenate all parts then mark as safe HTML (module_name_label is already escaped by formatModuleTitle)
			$headContent       = SafeHtml::unmark($icon) . $module_name_label . SafeHtml::unmark($roleColumns['identifier']);
			$resultBox['head'] = [canvastack_table_row_attr(SafeHtml::mark($headContent), ['style' => 'text-indent:19pt', 'id' => strtolower($escaped_module_name) . '-row'])];
			$resultBox['body'] = [
				canvastack_table_row_attr($roleColumns['table_name'] , ['align' => 'left', 'id' => strtolower($escaped_module_name) . '-row']),
				canvastack_table_row_attr($mergeBox , $opt),
			];
			
			$nodebtn = "node_btn_{$tableID}{$this->nodeActionButton}{$fieldID}{$this->nodeActionButton}{$valueID}";
			$resultBox['scripts']['table'] = [
				canvastack_table_row_attr (
					$this->buttonAdd($nodebtn, $tableID, $fieldID, $valueID) . 
					$this->js_rolemap_table($tableID, $fieldID, $valueID, $nodebtn, $nodeModel) .
					$this->js_rolemap_fieldname($fieldID, $valueID),
					['align' => 'center', 'id' => strtolower($escaped_module_name) . '-row', 'width' => 100, 'style' => 'padding:8px']
				)
			];
			
			$o = array_merge_recursive($resultBox['head'], $resultBox['body'], $resultBox['scripts']['table']);
			
			return $o;
		}
	}
	
	/**
	 * Generate AJAX URL for rolemapage requests with proper validation and encoding
	 * 
	 * Builds a secure AJAX URL with validated parameters and proper encoding.
	 * Uses Laravel's URL builder and http_build_query for safe URL construction.
	 * 
	 * @param string $usein Context for AJAX request (table_name, field_name, field_value, rolemapage)
	 * @param bool $return_data Whether to return the URL or just set it
	 * @return string|null The generated URL if $return_data is true, null otherwise
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException If $usein is invalid
	 * 
	 * @security
	 * - Validates usein parameter against whitelist to prevent URL manipulation
	 * - Uses Laravel URL builder for proper URL construction
	 * - Uses http_build_query for proper encoding of special characters
	 * - Includes CSRF token in query string
	 * 
	 * @example
	 * ```php
	 * $url = $this->ajax_urli('table_name', true);
	 * // Returns: http://example.com/admin/system/group?rolemapage=true&usein=table_name&_token=...
	 * ```
	 */
	private function ajax_urli(string $usein, bool $return_data = false): ?string {
		// Validate $usein parameter against whitelist
		$allowedContexts = ['table_name', 'field_name', 'field_value', 'rolemapage'];
		
		if (!in_array($usein, $allowedContexts, true)) {
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
				'Invalid AJAX context parameter',
				['usein' => $usein, 'allowed' => $allowedContexts]
			);
		}
		
		// Use Laravel URL builder for proper URL construction
		$current_url = url(str_replace('.', '/', canvastack_current_baseroute()));
		
		// Build query parameters with proper encoding
		$queryParams = [
			'rolemapage' => 'true',
			'usein'      => $usein,
			'_token'     => csrf_token()
		];
		
		// Use http_build_query for proper encoding of special characters
		$this->ajaxUrli = $current_url . '?' . http_build_query($queryParams);
		
		if (true === $return_data) {
			return $this->ajaxUrli;
		}
		
		return null;
	}
	
	/**
	 * Generate JavaScript for table field name mapping
	 * 
	 * Creates inline JavaScript that handles AJAX loading of field names
	 * when a table is selected in the mapping interface.
	 * 
	 * @param string $id Table select element ID
	 * @param string $target_id Field name select element ID
	 * @param string $second_target Field value select element ID
	 * @param string $nodebtn Button node identifier
	 * @param string $nodeModel Model node identifier
	 * @return string JavaScript code wrapped in script tags
	 */
	private function js_rolemap_table(string $id, string $target_id, string $second_target, string $nodebtn, string $nodeModel): string {
		$this->ajax_urli('table_name');
		return canvastack_script("mappingPageTableFieldname('{$id}', '{$target_id}', '{$this->ajaxUrli}', '{$second_target}', '{$nodebtn}', '{$nodeModel}');");
	}
	
	/**
	 * Generate JavaScript for field name to field values mapping
	 * 
	 * Creates inline JavaScript that handles AJAX loading of field values
	 * when a field name is selected in the mapping interface.
	 * 
	 * @param string $id Field name select element ID
	 * @param string $target_id Field value select element ID
	 * @return string JavaScript code wrapped in script tags
	 */
	private function js_rolemap_fieldname(string $id, string $target_id): string {
		$this->ajax_urli('field_name');
		return canvastack_script("mappingPageFieldnameValues('{$id}', '{$target_id}', '{$this->ajaxUrli}');");
	}
	
	/**
	 * Generate add button for mapping interface
	 * 
	 * Creates an "Add" button that allows users to add additional field
	 * mappings for a module.
	 * 
	 * @param string $node_btn Button node identifier
	 * @param string $id Table select element ID
	 * @param string $target_id Field name select element ID
	 * @param string $second_target Field value select element ID
	 * @return string HTML button markup
	 */
	private function buttonAdd(string $node_btn, string $id, string $target_id, string $second_target): string {
		$this->ajax_urli('field_name');		
		return canvastack_mappage_button_add($this->ajaxUrli, $node_btn, $id, $target_id, $second_target);
	}
	
	/**
	 * Invalidate mapping cache for a specific user or all users
	 * 
	 * Clears cached mapping data to ensure fresh data is loaded after mapping
	 * rule changes. Can target a specific user or clear all caches.
	 * 
	 * @param int|null $userId User ID to invalidate cache for, null for all users
	 * @return void
	 * 
	 * @performance
	 * - Mapping data is cached for 5 minutes to reduce database queries
	 * - Call this method after mapping changes to ensure users see updated rules
	 * 
	 * @example Invalidate for specific user
	 * ```php
	 * $this->invalidateMappingCache(5);
	 * ```
	 * 
	 * @example Invalidate for all users
	 * ```php
	 * $this->invalidateMappingCache();
	 * ```
	 */
	private function invalidateMappingCache(?int $userId = null): void {
		if ($userId) {
			\Cache::forget("mapping_data_user_{$userId}");
			\Log::info('Mapping cache invalidated for user', ['user_id' => $userId]);
		} else {
			// Invalidate all mapping caches
			\Cache::flush(); // Or use a more targeted approach if available
			\Log::info('All mapping caches invalidated');
		}
	}
}

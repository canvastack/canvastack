<?php
namespace Canvastack\Canvastack\Controllers\Admin\System\Includes;

use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Models\Admin\System\Privilege;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;
use Illuminate\Http\Request;

/**
 * Created on Jan 19, 2018
 * Time Created	: 17:58:08
 *
 * @filesource	Privileges.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
trait Privileges {
	
	private $roles              = [];
	private $group_privileges   = [];
	private $menu_privileges    = [];
	private $viewIndexPrivilege = false;
	private $admin_privilege    = 'admin_privilege';
	private $index_privilege    = 'index_privilege';
	private $table_privilege	= 'base_group_privilege';
		
	/**
	 * Check if privilege exists for group and module combination
	 * 
	 * Queries the privilege table to find an existing privilege record for
	 * a specific group and module combination.
	 * 
	 * @param int|string $group_id Group ID
	 * @param int|string $module_id Module ID
	 * @return object|null Privilege record or null if not found
	 * 
	 * @example
	 * ```php
	 * $privilege = $this->check_data(5, 12);
	 * if ($privilege) {
	 *     echo "Admin privilege: " . $privilege->admin_privilege;
	 * }
	 * ```
	 */
	private function check_data(int|string $group_id, int|string $module_id): ?object {
		$data = canvastack_query($this->table_privilege)
			->where('group_id', $group_id)
			->where('module_id', $module_id)
			->first();

		return $data;
	}
	
	/**
	 * Check if any privilege exists for a group
	 * 
	 * Queries the privilege table to find if any privilege record exists
	 * for the specified group.
	 * 
	 * @param int $group_id Group ID
	 * @return object|null First privilege record or null if none found
	 * 
	 * @example
	 * ```php
	 * $hasPrivileges = $this->check_group(5);
	 * if ($hasPrivileges) {
	 *     echo "Group has privileges configured";
	 * }
	 * ```
	 */
	private function check_group(int $group_id): ?object {
		$data = canvastack_query($this->table_privilege)
			->where('group_id', $group_id)
			->first();

		return $data;
	}
	
	/**
	 * Load all privileges for a specific group
	 * 
	 * Retrieves all privilege records for the specified group and stores
	 * them in the $group_privileges property for later use.
	 * 
	 * @param int $group_id Group ID
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $this->get_group_privileges(5);
	 * // Now $this->group_privileges contains all privileges for group 5
	 * ```
	 */
	private function get_group_privileges(int $group_id): void {
		$this->group_privileges = canvastack_query($this->table_privilege)->where('group_id', $group_id)->get();
	}
	
	/**
	 * Process and prepare Module Privileges for a group
	 * 
	 * This method handles the preparation of module privileges (feature-level access control)
	 * for a user group. It processes form data from the "Module Privileges" tab in the
	 * Group edit form and stores the prepared data in $this->roles for later processing
	 * by privileges_after_insert().
	 * 
	 * **CRITICAL BEHAVIOR - "Clear All" Support:**
	 * This method ALWAYS processes data, even when the 'modules' key is missing or empty.
	 * When no modules are provided, it sets a special "setnull" marker in $this->roles
	 * to indicate that all privileges should be cleared (set to NULL) for this group.
	 * 
	 * **Request Data Structure:**
	 * Expected input format (from form submission):
	 * ```php
	 * $request['modules'] = [
	 *     'admin_privilege' => [
	 *         'admin.content.articles' => [
	 *             '8' => '12',  // privilege_value => module_id
	 *             '4' => '12'   // privilege_value => module_id
	 *         ]
	 *     ],
	 *     'index_privilege' => [
	 *         'admin.content.articles' => [
	 *             '8' => '12'   // privilege_value => module_id
	 *         ]
	 *     ]
	 * ];
	 * ```
	 * 
	 * **Privilege Values:**
	 * - 8 = Read/View
	 * - 4 = Create/Insert
	 * - 2 = Update/Edit
	 * - 1 = Delete/Destroy
	 * 
	 * **Output Format (stored in $this->roles):**
	 * ```php
	 * $this->roles = [
	 *     [
	 *         'group_id' => 1,
	 *         'module_id' => 12,
	 *         'admin_privilege' => '8:4',  // Colon-separated privilege values
	 *         'index_privilege' => '8'
	 *     ]
	 * ];
	 * ```
	 * 
	 * **Usage Scenarios:**
	 * 
	 * 1. **Add New Privileges** (No existing data):
	 *    - User checks privilege checkboxes for modules
	 *    - Method builds $this->roles array with new data
	 *    - privileges_after_insert() performs INSERT operations
	 * 
	 * 2. **Update Existing Privileges**:
	 *    - User modifies checked privileges
	 *    - Method builds $this->roles array with updated data
	 *    - privileges_after_insert() performs UPDATE operations
	 * 
	 * 3. **Clear All Privileges** (Remove all privileges):
	 *    - User submits form WITHOUT 'modules' key (all checkboxes unchecked)
	 *    - Method sets special "setnull" marker in $this->roles
	 *    - privileges_after_insert() performs UPDATE to set all privileges to NULL
	 *    - **NOTE:** Unlike mapping privileges (which DELETE records), module privileges
	 *      are cleared by setting columns to NULL, preserving the record
	 * 
	 * 4. **Partial Clear** (Remove some modules, keep others):
	 *    - User unchecks some modules but keeps others
	 *    - Method builds $this->roles array with remaining modules only
	 *    - privileges_after_insert() sets unchecked modules to NULL, updates checked ones
	 * 
	 * **Database Operations (handled by privileges_after_insert()):**
	 * - INSERT: When privilege record doesn't exist for group+module
	 * - UPDATE: When privilege record exists (set values or NULL)
	 * - NO DELETE: Records are never deleted, only set to NULL
	 * 
	 * **Consistency with Mapping Page Privileges:**
	 * This method's behavior is consistent with mapping_before_insert() which also
	 * always processes data even when empty. The key difference is:
	 * - Module Privileges: UPDATE to NULL (preserve record)
	 * - Mapping Privileges: DELETE (remove record)
	 * 
	 * **Multi-Platform Support:**
	 * If multi-platform is enabled, this method also processes platform-specific data
	 * from $request[$this->platform_key].
	 * 
	 * @param Request $request The HTTP request containing form data
	 * @param object $group The group object being updated (must have 'id' property)
	 * 
	 * @return void Data is stored in $this->roles for later processing
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException
	 *         If invalid module route is provided
	 * 
	 * @see privileges_after_insert() For the actual database operations
	 * @see mapping_before_insert() For similar behavior with mapping privileges
	 * 
	 * @example Clear all privileges
	 * ```php
	 * // Form submission without 'modules' key (all checkboxes unchecked)
	 * $request = Request::create('/groups/1', 'PUT', [
	 *     'group_name' => 'test_group',
	 *     // NO 'modules' key
	 * ]);
	 * $this->privileges_before_insert($request, $group);
	 * // Result: $this->roles = ['setnull' => ['group_id' => 1]]
	 * // privileges_after_insert() will set all privileges to NULL
	 * ```
	 * 
	 * @example Add new privileges
	 * ```php
	 * $request = Request::create('/groups/1', 'PUT', [
	 *     'modules' => [
	 *         'admin_privilege' => [
	 *             'admin.content.articles' => ['8' => '12', '4' => '12']
	 *         ]
	 *     ]
	 * ]);
	 * $this->privileges_before_insert($request, $group);
	 * // Result: $this->roles = [['group_id' => 1, 'module_id' => 12, 'admin_privilege' => '8:4', ...]]
	 * ```
	 */
	private function privileges_before_insert(Request $request, object $group): void {

		$dataRequest = $request->all();
		if (true === is_multiplatform()) {
			$platform_key	= $dataRequest[$this->platform_key];
		}

		// Always initialize roles array, even if empty
		$this->roles = [];

		if (isset($dataRequest['modules'])) {
			if (!empty($group)) {
				$group_id = $group->id;
				
				// Get module class to map route names to module IDs
				$modules = \Canvastack\Canvastack\Models\Admin\System\Modules::where('active', 1)->get();
				$routeToModuleId = [];
				foreach ($modules as $module) {
					$routeToModuleId[$module->route_path] = $module->id;
				}
				
				// Extract privilege data from the nested structure
				$adminPrivileges = $dataRequest['modules'][$this->admin_privilege] ?? [];
				$indexPrivileges = $dataRequest['modules'][$this->index_privilege] ?? [];
				
				// Collect all unique route names
				$allRoutes = array_unique(array_merge(
					array_keys($adminPrivileges),
					array_keys($indexPrivileges)
				));
				
				// Build roles array for each route
				foreach ($allRoutes as $routeName) {
					if (!isset($routeToModuleId[$routeName])) {
						\Log::error('Invalid module route provided', [
							'route' => $routeName,
							'group_id' => $group_id,
							'available_routes' => array_keys($routeToModuleId)
						]);
						
						throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
							"Invalid module route: {$routeName}",
							[
								'route' => $routeName,
								'group_id' => $group_id
							]
						);
					}
					
					$moduleId = $routeToModuleId[$routeName];
					
					// Get privilege values for this route
					// The structure is: {"8":"module_id", "4":"module_id"} where keys are privilege values
					$adminPriv = null;
					if (isset($adminPrivileges[$routeName]) && is_array($adminPrivileges[$routeName])) {
						// Extract the keys (privilege values), not the values (module_ids)
						$adminPriv = implode(':', array_keys($adminPrivileges[$routeName]));
					}
					
					$indexPriv = null;
					if (isset($indexPrivileges[$routeName]) && is_array($indexPrivileges[$routeName])) {
						// Extract the keys (privilege values), not the values (module_ids)
						$indexPriv = implode(':', array_keys($indexPrivileges[$routeName]));
					}
					
					$this->roles[] = [
						'group_id'  => $group_id,
						'module_id' => intval($moduleId),
						$this->admin_privilege => $adminPriv,
						$this->index_privilege => $indexPriv
					];
				}
			}
		} else {
			// No modules data sent - this means all checkboxes were unchecked
			// Set a special marker to clear all privileges
			if (!empty($group)) {
				$this->roles = [
					'setnull' => [
						'group_id' => $group->id
					]
				];
				
				\Log::info('Clearing all privileges for group', [
					'group_id' => $group->id
				]);
			}
		}
	}
	
	/**
	 * Execute database operations for Module Privileges
	 * 
	 * This method performs the actual INSERT/UPDATE operations to save module privileges
	 * to the database. It processes the data prepared by privileges_before_insert() which
	 * is stored in $this->roles.
	 * 
	 * **CRITICAL BEHAVIOR - "Clear All" Support:**
	 * This method handles two distinct scenarios:
	 * 1. **"setnull" marker present**: Clear all privileges by setting columns to NULL
	 * 2. **Normal data**: Clear all first, then INSERT/UPDATE specific privileges
	 * 
	 * **Database Strategy:**
	 * - **Clear First**: Always UPDATE all privileges to NULL for the group
	 * - **Then Apply**: INSERT new or UPDATE existing privilege records
	 * - **No DELETE**: Records are preserved, only values are set to NULL
	 * 
	 * This "clear first, then apply" strategy ensures consistency and prevents orphaned
	 * privileges when users uncheck some modules.
	 * 
	 * **Input Data Format:**
	 * 
	 * Case 1 - Clear All (from privileges_before_insert when no modules selected):
	 * ```php
	 * $data = [
	 *     'setnull' => [
	 *         'group_id' => 1
	 *     ]
	 * ];
	 * ```
	 * 
	 * Case 2 - Normal Update (from privileges_before_insert with modules selected):
	 * ```php
	 * $data = [
	 *     [
	 *         'group_id' => 1,
	 *         'module_id' => 12,
	 *         'admin_privilege' => '8:4',  // Read + Create
	 *         'index_privilege' => '8'     // Read only
	 *     ],
	 *     [
	 *         'group_id' => 1,
	 *         'module_id' => 15,
	 *         'admin_privilege' => '8:4:2:1',  // Full access
	 *         'index_privilege' => null
	 *     ]
	 * ];
	 * ```
	 * 
	 * **Database Operations:**
	 * 
	 * 1. **Clear All Scenario** (setnull marker):
	 *    ```sql
	 *    UPDATE base_group_privilege 
	 *    SET admin_privilege = NULL, index_privilege = NULL 
	 *    WHERE group_id = 1
	 *    ```
	 * 
	 * 2. **Normal Update Scenario**:
	 *    Step 1 - Clear all privileges for group:
	 *    ```sql
	 *    UPDATE base_group_privilege 
	 *    SET admin_privilege = NULL, index_privilege = NULL 
	 *    WHERE group_id = 1
	 *    ```
	 *    
	 *    Step 2 - For each module in $data:
	 *    - If record exists: UPDATE with new values
	 *    - If record doesn't exist: INSERT new record
	 *    - If both privileges are NULL: SKIP (already cleared in step 1)
	 * 
	 * **Consistency with Mapping Page Privileges:**
	 * Unlike mapping_before_insert() which DELETEs records, this method preserves
	 * records and only sets values to NULL. This difference is intentional:
	 * - Module Privileges: Core access control, preserve record structure
	 * - Mapping Privileges: Data-level filters, can be completely removed
	 * 
	 * **Transaction Handling:**
	 * This method does NOT start its own transaction. Transaction management is handled
	 * by the calling method (store/update in GroupController) to avoid nested transaction
	 * conflicts and ensure atomicity across all group update operations.
	 * 
	 * **Cache Invalidation:**
	 * Cache invalidation is handled by the calling method AFTER the transaction commits.
	 * This ensures cache is only cleared if the entire operation succeeds.
	 * 
	 * @param array $data Privilege data prepared by privileges_before_insert()
	 *                    Either contains 'setnull' marker or array of privilege records
	 * 
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Library\Exceptions\ControllerException
	 *         If database operations fail
	 * 
	 * @see privileges_before_insert() For data preparation
	 * @see \App\Http\Controllers\Admin\System\GroupController::update() For transaction management
	 * 
	 * @example Clear all privileges
	 * ```php
	 * // Input from privileges_before_insert()
	 * $data = ['setnull' => ['group_id' => 1]];
	 * $this->privileges_after_insert($data);
	 * // Result: All privileges for group 1 set to NULL
	 * ```
	 * 
	 * @example Update privileges
	 * ```php
	 * // Input from privileges_before_insert()
	 * $data = [
	 *     ['group_id' => 1, 'module_id' => 12, 'admin_privilege' => '8:4', 'index_privilege' => '8']
	 * ];
	 * $this->privileges_after_insert($data);
	 * // Result: 
	 * // 1. All privileges for group 1 cleared (set to NULL)
	 * // 2. Module 12 privileges set to admin='8:4', index='8'
	 * // 3. Other modules remain NULL
	 * ```
	 */
	private function privileges_after_insert($data) {
		$nullset = null;
		$groups  = false;
		$IDP     = PrivilegeConstants::INDEX_PRIVILEGE;
		$ADP     = PrivilegeConstants::ADMIN_PRIVILEGE;
		
		// NOTE: Transaction management is handled by the calling method (store/update)
		// Do NOT start a nested transaction here to avoid transaction conflicts
		
		try {
			if (isset($data['setnull'])) {
				// Handle setnull case - clear all privileges for group
				$nullGroup = intval($data['setnull']['group_id']);
				$affectedRows = canvastack_query($this->table_privilege)
					->where('group_id', $nullGroup)
					->update([$IDP => $nullset, $ADP => $nullset]);
				
				\Log::info('Privileges cleared for group', [
					'group_id' => $nullGroup,
					'affected_rows' => $affectedRows
				]);
				
				$groupId = $nullGroup;
			} else {
				// Process privilege insert/update operations
				foreach ($data as $moduleId => $roles) $groups = $roles['group_id'];
				
				// Clear existing privileges for group
				$affectedRows = canvastack_query($this->table_privilege)
					->where('group_id', $groups)
					->update([$IDP => $nullset, $ADP => $nullset]);
				
				\Log::info('Privileges cleared before update', [
					'group_id' => $groups,
					'affected_rows' => $affectedRows
				]);
				
				$insertedCount = 0;
				$updatedCount = 0;
				
				$request = [];
				foreach ($data as $roles) {
					$request['group_id']  = $roles['group_id'];
					$request['module_id'] = $roles['module_id'];
					$request[$IDP]        = $nullset;
					$request[$ADP]        = $nullset;
					
					foreach ($roles as $role_info => $role_value) {
						if ($role_info === 'group_id' || $role_info === 'module_id') {
							continue;
						}
						if ($IDP === $role_info || $ADP === $role_info) {
							$request[$role_info] = is_array($role_value) ? implode(':', array_values($role_value)) : $role_value;
						}
					}
					
					// Skip if both privileges are null
					if ($request[$IDP] === null && $request[$ADP] === null) {
						continue;
					}
					
					$check_role	= $this->check_data($request['group_id'], $request['module_id']);
					
					if (is_empty($check_role)) {
						// Insert new privilege record
						canvastack_insert(new Privilege, $request, true);
						$insertedCount++;
					} else {
						// Update existing privilege record
						canvastack_update(Privilege::find($check_role->id), $request, true);
						$updatedCount++;
					}
				}
				
				\Log::info('Privileges processed successfully', [
					'group_id' => $groups,
					'inserted' => $insertedCount,
					'updated' => $updatedCount
				]);
				
				$groupId = $groups;
			}
			
			// NOTE: Cache invalidation moved to calling method (after transaction commit)
			// This ensures cache is only cleared if the entire transaction succeeds
			
		} catch (\Exception $e) {
			\Log::error('Failed to process privileges', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'data_count' => count($data)
			]);
			
			throw new \Canvastack\Canvastack\Library\Exceptions\ControllerException(
				'Failed to process privileges: ' . $e->getMessage()
			);
		}
	}
	
	/**
	 * Build hierarchical menu structure from active modules with caching
	 * 
	 * Loads all active modules and builds a 4-level hierarchical menu structure
	 * based on route paths. Stores the result in $menu_privileges property.
	 * Menu data is cached for 1 hour to reduce database queries.
	 *
	 * created @Sep 11, 2018
	 * author: wisnuwidi
	 * 
	 * @return void
	 * 
	 * @performance
	 * - Menu data is cached for 1 hour (3600 seconds)
	 * - Cache key includes user ID for user-specific menus
	 * - Use invalidateMenuCache() to clear cache after module changes
	 * 
	 * @example Menu structure
	 * ```
	 * Level 1: content
	 * Level 2: content.articles
	 * Level 3: content.articles.categories
	 * Level 4: content.articles.categories.tags
	 * ```
	 */
	private function get_menu(): void {
		// Create cache key based on user ID
		$userId = auth()->id() ?? 'guest';
		$cacheKey = "menu_privileges_{$userId}";

		// Try to get menu from cache
		$cachedMenu = \Cache::remember($cacheKey, 3600, function () {
			try {
				// Load active modules
				$this->module_class = Modules::where('active', 1)->get();
				$modules            = $this->module_class;
				$menuObj            = $modules;
				$routeData          = [];
				$parentMenu         = [];
				$mainMenu           = [];

				foreach ($menuObj as $menuArray) {
					$menuData = $menuArray->getAttributes();

					// Validate required fields
					if (empty($menuData['route_path'])) {
						\Log::warning('Module missing route_path', [
							'module_id' => $menuData['id'] ?? 'unknown',
							'module_name' => $menuData['module_name'] ?? 'unknown'
						]);
						continue;
					}

					if (empty($menuData['id'])) {
						\Log::warning('Module missing id', [
							'route_path' => $menuData['route_path'],
							'module_name' => $menuData['module_name'] ?? 'unknown'
						]);
						continue;
					}

					$routeData[$menuData['route_path']]['id']    = $menuData['id'];
					$routeData[$menuData['route_path']]['name']  = $menuData['module_name'] ?? '';
					$routeData[$menuData['route_path']]['route'] = $menuData['route_path'];
					$routeData[$menuData['route_path']]['url']   = route("{$menuData['route_path']}.index");
					$routeData[$menuData['route_path']]['icon']  = $menuData['icon'] ?? '';
				}

				foreach ($routeData as $key => $value) {
					$key = explode('.', $key);

					if (count($key) === 1) {
						$parentMenu[$key[0]]        = $key[0];
						$mainMenu[$key[0]][$key[0]] = $value;
					}
					if (count($key) === 2 && !empty($key[1])) {
						$parentMenu[$key[0]][$key[1]] = $key[1];
						$mainMenu[$key[0]][$key[1]]   = $value;
					}
					if (count($key) === 3 && !empty($key[2])) {
						$parentMenu[$key[0]][$key[1]][$key[2]] = $key[2];
						$mainMenu[$key[0]][$key[1]][$key[2]]   = $value;
					}
					if (count($key) === 4 && !empty($key[3])) {
						$parentMenu[$key[0]][$key[1]][$key[2]][$key[3]] = $key[3];
						$mainMenu[$key[0]][$key[1]][$key[2]][$key[3]]   = $value;
					}
				}

				return canvastack_array_to_object_recursive($mainMenu);

			} catch (\Exception $e) {
				\Log::error('Failed to build menu structure', [
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				]);

				// Return empty object on error
				return (object)[];
			}
		});

		$this->menu_privileges = $cachedMenu;
	}
	
	protected $module_class = [];
	
	/**
	 * Center table cell content with styling
	 *
	 * created @Sep 11, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string Content to center
	 * @return string HTML table cell attributes with centered alignment
	 * 
	 * @example
	 * ```php
	 * $cell = $this->_center('Read');
	 * // Returns: <td align="center" valign="middle" class="privilege-subheader">Read</td>
	 * ```
	 */
	private function _center(string $string): string {
		return canvastack_table_row_attr($string, ['align' => 'center', 'valign' => 'middle', 'class' => 'privilege-subheader']);
	}
	
	public $module_privileges = [];
	
	/**
	 * Check and load module privileges for current group
	 *
	 * Parses privilege data for the current group being edited and builds
	 * a structured array of privileges for both frontend (index) and backend (admin).
	 *
	 * created @Dec 10, 2018
	 * author: wisnuwidi
	 *
	 * @param string $index Index privilege key (index_privilege)
	 * @param string $admin Admin privilege key (admin_privilege)
	 * @return void
	 *
	 * @example Privilege values
	 * ```
	 * 8: read|select
	 * 4: write|insert
	 * 2: modify|update
	 * 1: destroy|delete
	 * ```
	 */
	private function check_module_privileges(string $index, string $admin): void {
		$roles = [];
		$urli  = explode('/', url()->current());
		if ('edit' === last($urli)) {
			unset($urli[count($urli)-1]);
			$this->id = intval(last($urli));
		}

		if (isset($this->id)) $this->get_group_privileges($this->id);

		if (count($this->group_privileges) >= 1) {
			foreach ($this->group_privileges as $role) {
				// INFO: [ 8:read|select, 4:write|insert, 2:modify|update, 1:destroy|delete ]

				$frontend = explode(':', $role->{$index});
				foreach ($frontend as $index_role) $roles[$index][$role->module_id][$index_role] = $index_role;

				$backend  = explode(':', $role->{$admin});
				foreach ($backend  as $admin_role) $roles[$admin][$role->module_id][$admin_role] = $admin_role;
			}
		}

		$this->module_privileges = $roles;
	}
	
	/**
	 * Load module privileges for current group
	 * 
	 * Wrapper method that calls check_module_privileges with the standard
	 * index and admin privilege keys.
	 *
	 * created @Dec 10, 2018
	 * author: wisnuwidi
	 *
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $this->get_module_privileges();
	 * // Now $this->module_privileges contains privilege data
	 * ```
	 */
	private function get_module_privileges(): void {
		$this->check_module_privileges($this->index_privilege, $this->admin_privilege);
	}
	
	/**
	 * Render privilege checkboxes for a module
	 * 
	 * Generates HTML checkboxes for read, insert, update, and delete privileges
	 * for both frontend (index) and backend (admin) access. Checkboxes are
	 * pre-checked based on existing privileges for the current group.
	 *
	 * @tutorial: Note for Privileges Data Value Information [ 8: read|select, 4: write|insert, 2: modify|update, 1: destroy|delete ]
	 *
	 * @param string $module_name Display name of the module
	 * @param object $module_data Module data object with id and route properties
	 * @param string $icon Icon HTML to display before module name
	 * @param string $indentClass CSS class for indentation level (privilege-indent-0 to privilege-indent-3)
	 * @return array Table row data with module name and checkbox cells
	 * 
	 * @security
	 * - Module name is escaped to prevent XSS (handled by SafeHtml)
	 * - Checkbox values are validated against module IDs
	 * 
	 * @example
	 * ```php
	 * $icon = '<i class="fa fa-caret-right"></i>';
	 * $row = $this->_checkboxes('Articles', $moduleData, $icon, 'privilege-indent-1');
	 * ```
	 */
	private function _checkboxes(string $module_name, object $module_data, string $icon, string $indentClass = 'privilege-indent-0'): array {
		$this->get_module_privileges();
		$routeName = strtolower($module_data->route);
		
		// Frontend Privileges
		if (true === $this->viewIndexPrivilege) {
			$IDP                    = $this->index_privilege;
			$checkedIndex           = [];
			$checkedIndex['read']   = canvastack_form_checkList("modules[{$IDP}][{$routeName}][8]", $module_data->id, false, false, 'success read-select privilege-visible-checkbox');
			$checkedIndex['write']  = canvastack_form_checkList("modules[{$IDP}][{$routeName}][4]", $module_data->id, false, false, 'lilac write-insert privilege-visible-checkbox');
			$checkedIndex['modify'] = canvastack_form_checkList("modules[{$IDP}][{$routeName}][2]", $module_data->id, false, false, 'warning modify-update privilege-visible-checkbox');
			$checkedIndex['delete'] = canvastack_form_checkList("modules[{$IDP}][{$routeName}][1]", $module_data->id, false, false, 'danger delete-destroy privilege-visible-checkbox');
			
			if (isset($this->module_privileges[$IDP][$module_data->id])) {
				if (isset($this->module_privileges[$IDP][$module_data->id]['8']) && $this->module_privileges[$IDP][$module_data->id]['8'] >= 1)
					$checkedIndex['read']   = canvastack_form_checkList("modules[{$IDP}][{$routeName}][8]", $module_data->id, false, true, 'success read-select privilege-visible-checkbox');
				if (isset($this->module_privileges[$IDP][$module_data->id]['4']) && $this->module_privileges[$IDP][$module_data->id]['4'] >= 1)
					$checkedIndex['write']  = canvastack_form_checkList("modules[{$IDP}][{$routeName}][4]", $module_data->id, false, true, 'lilac write-insert privilege-visible-checkbox');
				if (isset($this->module_privileges[$IDP][$module_data->id]['2']) && $this->module_privileges[$IDP][$module_data->id]['2'] >= 1)
					$checkedIndex['modify'] = canvastack_form_checkList("modules[{$IDP}][{$routeName}][2]", $module_data->id, false, true, 'warning modify-update privilege-visible-checkbox');
				if (isset($this->module_privileges[$IDP][$module_data->id]['1']) && $this->module_privileges[$IDP][$module_data->id]['1'] >= 1)
					$checkedIndex['delete'] = canvastack_form_checkList("modules[{$IDP}][{$routeName}][1]", $module_data->id, false, true, 'danger delete-destroy privilege-visible-checkbox');
			}
		}
		
		// Backend Privileges
		$ADP                    = $this->admin_privilege;
		$checkedAdmin           = [];
		$checkedAdmin['read']	= canvastack_form_checkList("modules[{$ADP}][{$routeName}][8]", $module_data->id, false, false, 'success read-select privilege-visible-checkbox');
		$checkedAdmin['write']	= canvastack_form_checkList("modules[{$ADP}][{$routeName}][4]", $module_data->id, false, false, 'lilac write-insert privilege-visible-checkbox');
		$checkedAdmin['modify']	= canvastack_form_checkList("modules[{$ADP}][{$routeName}][2]", $module_data->id, false, false, 'warning modify-update privilege-visible-checkbox');
		$checkedAdmin['delete']	= canvastack_form_checkList("modules[{$ADP}][{$routeName}][1]", $module_data->id, false, false, 'danger delete-destroy privilege-visible-checkbox');
		
		if (isset($this->module_privileges[$ADP][$module_data->id])) {
			// Backend Privileges
			if (isset($this->module_privileges[$ADP][$module_data->id]['8']) && $this->module_privileges[$ADP][$module_data->id]['8'] >= 1)
				$checkedAdmin['read']   = canvastack_form_checkList("modules[{$ADP}][{$routeName}][8]", $module_data->id, false, true, 'success read-select privilege-visible-checkbox');
			if (isset($this->module_privileges[$ADP][$module_data->id]['4']) && $this->module_privileges[$ADP][$module_data->id]['4'] >= 1)
				$checkedAdmin['write']  = canvastack_form_checkList("modules[{$ADP}][{$routeName}][4]", $module_data->id, false, true, 'lilac write-insert privilege-visible-checkbox');
			if (isset($this->module_privileges[$ADP][$module_data->id]['2']) && $this->module_privileges[$ADP][$module_data->id]['2'] >= 1)
				$checkedAdmin['modify'] = canvastack_form_checkList("modules[{$ADP}][{$routeName}][2]", $module_data->id, false, true, 'warning modify-update privilege-visible-checkbox');
			if (isset($this->module_privileges[$ADP][$module_data->id]['1']) && $this->module_privileges[$ADP][$module_data->id]['1'] >= 1)
				$checkedAdmin['delete'] = canvastack_form_checkList("modules[{$ADP}][{$routeName}][1]", $module_data->id, false, true, 'danger delete-destroy privilege-visible-checkbox');
		}
		
		$opt                = ['align' => 'center', 'id' => strtolower($module_name) . '-row', 'class' => 'privilege-checkbox-cell'];
		$resultBox          = [];
		// Concatenate icon with module name then mark as safe HTML
		$headContent        = SafeHtml::unmark($icon) . $module_name;
		$resultBox['head']  = [canvastack_table_row_attr(SafeHtml::mark($headContent), ['class' => 'privilege-module-name ' . $indentClass, 'id' => strtolower($module_name) . '-row'])];
		$resultBox['admin'] = [
			canvastack_table_row_attr($checkedAdmin['read'],   $opt),
			canvastack_table_row_attr($checkedAdmin['write'],  $opt),
			canvastack_table_row_attr($checkedAdmin['modify'], $opt),
			canvastack_table_row_attr($checkedAdmin['delete'], $opt)
		];
		if (true === $this->viewIndexPrivilege) {
			$resultBox['index'] = [
				canvastack_table_row_attr($checkedIndex['read'],   $opt),
				canvastack_table_row_attr($checkedIndex['write'],  $opt),
				canvastack_table_row_attr($checkedIndex['modify'], $opt),
				canvastack_table_row_attr($checkedIndex['delete'], $opt)
			];
		} else {
			$resultBox['index'] = [];
		}
		
		$o = array_merge_recursive($resultBox['head'], $resultBox['admin'], $resultBox['index']);
		
		return $o;
	}
	
	/**
	 * Render complete group privileges table with hierarchical module structure
	 * 
	 * Generates an HTML table displaying all modules in a hierarchical structure
	 * (up to 4 levels deep) with privilege checkboxes for each module. The table
	 * includes separate columns for backend and frontend privileges.
	 *
	 * created @Sep 11, 2018
	 * author: wisnuwidi
	 *
	 * @return string HTML table with privilege checkboxes
	 * 
	 * @security
	 * - All module names are escaped to prevent XSS
	 * - Uses SafeHtml for safe HTML concatenation
	 * 
	 * @performance
	 * - Builds table structure in memory before rendering
	 * - Uses efficient array operations for hierarchy building
	 * 
	 * @example Table structure
	 * ```
	 * | Module Name          | Backend Privilege      | Frontend Privilege     |
	 * |                      | R | I | U | D          | R | I | U | D          |
	 * |----------------------|------------------------|------------------------|
	 * | Content              | (colspan for group)                             |
	 * |   Articles           | ☑ | ☑ | ☐ | ☐          | ☑ | ☐ | ☐ | ☐          |
	 * |     Categories       | ☑ | ☑ | ☑ | ☐          | ☑ | ☐ | ☐ | ☐          |
	 * ```
	 */
	private function group_privilege(): string {
		$rowData     = [];
		$row_table   = [];
		$icon        = SafeHtml::mark('<i class="fa fa-caret-right privilege-icon"></i>');
		$dataCenter  = [
			$this->_center('Read'),
			$this->_center('Insert'),
			$this->_center('Update'),
			$this->_center('Delete'),
		];
		
		// Calculate total columns dynamically
		$totalColumns = 1; // Module Name column
		$totalColumns += 4; // Backend Privilege (4 columns)
		if (true === $this->viewIndexPrivilege) {
			$totalColumns += 4; // Frontend Privilege (4 columns)
		}
		
		foreach ($this->menu_privileges as $parent => $childs) {
			$parent_title	= ucwords(str_replace('_', ' ', $parent));
			if (!empty($childs->name)) $parent_title = $childs->name;
			// Unmark icon, concatenate, then mark as safe HTML
			$parentContent = SafeHtml::unmark($icon) . $parent_title;
			$row_table[]	= [canvastack_table_row_attr(SafeHtml::mark($parentContent), ['class' => 'privilege-module-name privilege-indent-0 privilege-group-row', 'colspan' => $totalColumns])];
			
			foreach ($childs as $child_name => $data_module) {
				if (isset($data_module->id) === false) {
					$child_title	= ucwords(str_replace('_', ' ', $child_name));
					if (!empty($data_module->name)) $child_title = $data_module->name;
					
					// Unmark icon, concatenate, then mark as safe HTML
					$childContent = SafeHtml::unmark($icon) . $child_title;
					$row_table[]	= [canvastack_table_row_attr(SafeHtml::mark($childContent), ['class' => 'privilege-module-name privilege-indent-1', 'colspan' => $totalColumns])];
					foreach ($data_module as $module_name => $module_data) {
						
						if (!empty($module_data->id)) {
							$module_title = ucwords(str_replace('_', ' ', $module_name));
							if (!empty($module_data->name)) $module_title = $module_data->name;
							
							$row_table[] = $this->_checkboxes($module_title, $module_data, $icon, 'privilege-indent-2');
						} else {
							
							$module_title = ucwords(str_replace('_', ' ', $module_name));
							if (!empty($module_data->name)) $module_title = $module_data->name;
							
							// Unmark icon, concatenate, then mark as safe HTML
							$moduleContent = SafeHtml::unmark($icon) . $module_title;
							$row_table[] = [canvastack_table_row_attr(SafeHtml::mark($moduleContent), ['class' => 'privilege-module-name privilege-indent-2', 'colspan' => $totalColumns])];
							foreach ($module_data as $third_name => $third_data) {
								$third_title = ucwords(str_replace('_', ' ', $third_name));
								if (!empty($third_data->name)) $third_title = $third_data->name;
								
								$row_table[] = $this->_checkboxes($third_title, $third_data, $icon, 'privilege-indent-3');
							}
						}
					}
				} else {
					
					$child_title = ucwords(str_replace('_', ' ', $child_name));
					if (!empty($data_module->name)) $child_title = $data_module->name;
					
					$row_table[] = $this->_checkboxes($child_title, $data_module, $icon, 'privilege-indent-1');
				}
			}
		}
		
		// Build header with proper structure for rowspan
		// We need to use the 'merge' key to create second header row
		$header = [];
		
		// Module Name column with rowspan=2 and merge for second row
		$header[] = [
			'column' => canvastack_table_row_attr('Module Name', ['rowspan' => 2, 'class' => 'privilege-header-module']),
			'merge'  => $dataCenter // Backend sub-headers (Read, Insert, Update, Delete)
		];
		
		// Backend Privilege header (colspan=4)
		$header[] = canvastack_table_row_attr('Backend Privilege', ['colspan' => 4, 'class' => 'privilege-header-backend']);
		
		// Frontend Privilege header (colspan=4) if enabled
		if (true === $this->viewIndexPrivilege) {
			// Add Frontend sub-headers to the merge array of first column
			$header[0]['merge'] = array_merge($header[0]['merge'], $dataCenter);
			$header[] = canvastack_table_row_attr('Frontend Privilege', ['colspan' => 4, 'class' => 'privilege-header-frontend']);
		}
		
		$title_id = 'group_privileges_' . canvastack_random_strings(50, false);
		
		// Add privilege-table class to table attributes
		$tableAttributes = [
			'id'    => "datatable-{$title_id}",
			'class' => 'table privilege-table'
		];
		
		// Generate table with custom class
		$tableHtml = canvastack_generate_table('Set Role Module Page', $title_id, $header, $row_table, $tableAttributes, false, false);
				
		// Wrap table with privilege-table-container and add script
		return '<div class="privilege-table-container">' . $tableHtml . '</div>';
	}
	
	/**
	 * Invalidate menu cache for a specific user or all users
	 * 
	 * Clears cached menu data to ensure fresh data is loaded after module
	 * or privilege changes. Can target a specific user or clear all caches.
	 * 
	 * @param int|null $userId User ID to invalidate cache for, null for all users
	 * @return void
	 * 
	 * @performance
	 * - Menu data is cached for 1 hour to reduce database queries
	 * - Call this method after privilege changes to ensure users see updated menus
	 * 
	 * @example Invalidate for specific user
	 * ```php
	 * $this->invalidateMenuCache(5);
	 * ```
	 * 
	 * @example Invalidate for all users
	 * ```php
	 * $this->invalidateMenuCache();
	 * ```
	 */
	private function invalidateMenuCache(?int $userId = null): void {
		if ($userId !== null) {
			// Clear cache for specific user
			$cacheKey = "menu_privileges_{$userId}";
			\Cache::forget($cacheKey);
			\Log::info('Menu cache invalidated for user', ['user_id' => $userId]);
		} else {
			// Clear menu caches for guest and current user
			// Note: We don't use cache tags as not all cache drivers support them
			\Cache::forget('menu_privileges_guest');

			if (auth()->check()) {
				\Cache::forget('menu_privileges_' . auth()->id());
			}

			\Log::info('Menu cache invalidated for guest and current user');
		}
	}
}

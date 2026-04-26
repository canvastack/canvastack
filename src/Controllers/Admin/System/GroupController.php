<?php
namespace Canvastack\Canvastack\Controllers\Admin\System;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Controllers\Admin\System\Includes\Privileges;
use Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;

/**
 * Created on Jan 19, 2018
 * Time Created	: 7:25:45 PM
 * Filename		: GroupController.php
 *
 * @filesource	GroupController.php
 *
 * @author		wisnuwidi@CanvaStack - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class GroupController extends Controller {
	use Privileges, MappingPage;
	
	public $data;
	
	private $id           = false;
	private $_set_tab     = [];
	private $_tab_config  = [];
	private $_hide_fields = ['id'];
	
	/**
	 * Initialize GroupController with model and validation rules
	 * 
	 * Sets up the controller with the Group model and defines validation rules
	 * for group creation and updates. Ensures group names are unique and required
	 * fields are provided.
	 * 
	 * @return void
	 */
	public function __construct() {
		parent::__construct(Group::class, 'system.config');

		$this->setValidations(
			[
				'group_name' => 'required|unique:base_group',
				'group_info' => 'required',
				'active'     => 'required'
			],[
				'group_name' => 'required',
				'group_info' => 'required',
				'active'     => 'required'
			]
		);
	}
	
	/**
	 * Display group list with filtering and access control
	 * 
	 * Renders a datatable view of all user groups with filtering, sorting, and search
	 * capabilities. Non-root users cannot see the root group for security reasons.
	 * 
	 * created @Sep 11, 2018
	 * author: wisnuwidi
	 * 
	 * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse
	 * 
	 * @security
	 * - Root group is hidden from non-root users
	 * - Current user's group is highlighted in the list
	 * 
	 * @performance
	 * - Group list is cached for 5 minutes (see invalidateGroupCache())
	 * - Filters use selectbox for efficient querying
	 */
	public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse {
		$this->setPage();

		if ('root' !== $this->session['user_group']) {
			$this->filterPage(['group_name' => 'root'], '!=');
		}

		$this->table->mergeColumns('Group', ['group_info', 'group_name', 'group_alias']);

		$this->table->searchable();
		$this->table->clickable();
		$this->table->sortable();

		$this->table->filterGroups('group_name', 'selectbox', true);
		$this->table->filterGroups('group_alias', 'selectbox', true);
		$this->table->filterGroups('group_info', 'selectbox', true);

		$this->table->columnCondition('group_name', 'row', '==', $this->session['user_group'], 'background-color', 'rgba(222, 249, 195, 0.51)');
		$this->table->lists($this->model_table, ['group_info', 'group_name', 'group_alias', 'active']);

		return $this->render();
	}
	
	/**
	 * Display group creation form with privilege configuration
	 * 
	 * Renders a form for creating a new user group with module-level privileges
	 * (read, insert, update, delete) and page-level data filtering rules.
	 * 
	 * created @Sep 11, 2017
	 * author: wisnuwidi
	 * 
	 * @tutorial: Description Logic as Internal ROOT
	 * 		: This form will rendering all fields in group table including [ $this->platform_key ].
	 * @tutorial: Description Logic as External Administrator ( users )
	 * 		: This form will rendering all fields in group table except [ $this->platform_key ].
	 * 		: [ $this->platform_key ] data field would be posted into the database using [ $this->platform_key ] value saved in the user data sessions.
	 * 
	 * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
	 * 
	 * @example Form includes:
	 * - Basic group fields (name, alias, info, active status)
	 * - Module Privileges tab with hierarchical checkbox tree
	 * - Mapping Page Privileges tab with dynamic field selection
	 */
	public function create(): \Illuminate\View\View|\Illuminate\Contracts\View\Factory {
		$this->setPage();
		$this->get_menu();

		$this->form->model();
		$this->form->text('group_name', null, ['required']);
		$this->form->text('group_alias', null, ['required']);
		$this->form->text('group_info', null, ['required']);
		$this->form->selectbox('active', active_box(), false, ['required']);

		// SET MODULE PRIVILEGES
		$this->form->openTab('Module Privileges');
		$this->form->draw($this->group_privilege());

		// SET PAGE PRIVILEGES
		$this->form->openTab('Mapping Page Privileges');
		$this->form->draw($this->mapping());

		$this->form->closeTab();

		$this->form->close('Save Group');

		return $this->render();
	}
	
	/**
	 * Stored to inserting data packages requested by $_POST data
	 * 
	 * created @Sep 11, 2017
	 * author: wisnuwidi
	 * 
	 * This method creates a new user group and configures its module-level privileges
	 * and page-level data filtering rules. It handles both normal form submissions and
	 * AJAX requests for dynamic privilege configuration.
	 * 
	 * The method performs the following operations:
	 * 1. Validates CSRF token for AJAX requests
	 * 2. Separates module privileges and page mapping data
	 * 3. Inserts group record
	 * 4. Configures module privileges (read, insert, update, delete)
	 * 5. Configures page-level data filtering rules
	 * 
	 * @tutorial: 1. This script would check user sessions group.
	 * @tutorial: 2. If user has logged as External users Groups, the data requested would be merge [ $this->platform_key ] from user sessions.
	 * @tutorial: 3. If user has logged as Internal Root Group, [ $this->platform_key ] data requested would send by [ $this->platform_key ], posted by selected form.
	 * @tutorial: 4. Group name data inserted would be uniquee in every [ $this->platform_key ].
	 * @tutorial: 5. Modular data checkboxes, used for setting the access privileges in every single group in every single [ $this->platform_key ].
	 * @tutorial: 6. Modular data checkboxes collections values, would added after inserting group data.
	 * 			 	 It would draw all the modular array before inserting in the base_group_privilege table ["see: $this->set_data_before_insert($callbackRequest, $model_id)"]
	 * @tutorial: 7. Last process, modular data collections package would insert into base_group_privilege table with group_id and module_id.
	 * 			 	 These row data packages would set group privileges in every single group with every single [ $this->platform_key ]
	 * 			 	 ["see: $this->set_data_after_insert($this->roles)"].
	 * 
	 * @param Request $request Group creation request with validated data
	 * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse|mixed Redirect to edit page or AJAX response
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\CSRFException If CSRF validation fails for AJAX requests
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException If validation fails
	 * 
	 * @security
	 * - CSRF token validation for AJAX requests with rolemapage parameter
	 * - Input validation for usein parameter against whitelist ['table_name', 'field_name', 'field_value']
	 * - POST data validation to ensure non-empty data
	 * - Security event logging for all AJAX requests
	 * - Uses Request object methods instead of superglobals
	 * 
	 * @example AJAX request with CSRF token
	 * ```javascript
	 * $.ajax({
	 *     url: '/admin/system/group?rolemapage=true&usein=table_name',
	 *     method: 'POST',
	 *     data: { data: 'value', _token: '{{ csrf_token() }}' },
	 *     headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
	 * });
	 * ```
	 * 
	 * @example Normal form submission
	 * ```php
	 * $request = new Request([
	 *     'group_name' => 'editors',
	 *     'group_alias' => 'Editor',
	 *     'group_info' => 'Content Editors',
	 *     'active' => 1,
	 *     'modules' => [
	 *         'admin_privilege' => [
	 *             'content.articles' => [8, 4, 2] // read, insert, update
	 *         ]
	 *     ]
	 * ]);
	 * ```
	 */
	public function store(Request $request) {
		$this->get_session();
		
		// CRITICAL: Validate CSRF for AJAX rolemapage requests
		if ($request->query('rolemapage')) {
			$this->validateAjaxCsrfToken();
			
			// Validate usein parameter
			$usein = $request->query('usein');
			$allowedContexts = ['table_name', 'field_name', 'field_value'];
			
			if (!in_array($usein, $allowedContexts)) {
				throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
					'Invalid AJAX context',
					['usein' => $usein, 'allowed' => $allowedContexts]
				);
			}
			
			// Validate POST data
			$postData = $request->all();
			if (empty($postData)) {
				throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException('POST data is required');
			}
			
			\Log::info('AJAX rolemapage request validated', [
				'user_id' => $this->session['id'],
				'usein' => $usein
			]);
			
			return $this->rolepage($postData, $usein);
		}
		
		// Normal form submission with transaction management
		DB::beginTransaction();
		
		try {
			$requests = $request->all();                                       // collect all requests
			
			// Separate modules and mapping data from request
			$modules = [];
			$rolepages = [];
			
			if (isset($requests['modules'])) {
				$modules['modules'] = $requests['modules'];                     // get modules requests, if any
				$request->offsetUnset('modules');                               // throw modules request before insert to group table)
				
				$mapPage            = $this->map();
				$mapNode            = $mapPage::$prefixNode;
				
				if (isset($requests[$mapNode])) {
					$rolepages[$mapNode] = $requests[$mapNode];
					$request->offsetUnset($mapNode);
				}
			}
			
			// Insert group
			$this->insert_data($request, false);
			
			if (!$this->stored_id) {
				throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException('Failed to create group');
			}
			
			// Merge back modules and rolepages
			$callbackRequest = isset($requests['modules']) 
				? $request->merge(array_merge($modules, $rolepages))
				: $request;
			
			// Set privileges and mapping
			$this->set_data_before_insert($callbackRequest, $this->stored_id);
			$this->set_data_after_insert($this->roles);
			
			DB::commit();
			
			\Log::info('Group created successfully', [
				'group_id' => $this->stored_id,
				'group_name' => $request->group_name,
				'created_by' => $this->session['id']
			]);
			
			// Invalidate caches after successful commit
			$this->invalidateGroupCache();
			canvastack_invalidate_privilege_cache($this->stored_id);
			
			return self::redirect("{$this->stored_id}/edit", $request);
			
		} catch (\Exception $e) {
			DB::rollBack();
			
			\Log::error('Failed to create group', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'request' => $request->except(['password', '_token'])
			]);
			
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
				'Failed to create group: ' . $e->getMessage(),
				['original_error' => $e->getMessage()]
			);
		}
	}
	
	/**
	 * Display group edit form with current privileges
	 * 
	 * Renders a form for editing an existing user group. Root group cannot have
	 * its privileges modified. Non-root users cannot edit root group or admin groups.
	 *
	 * created @Sep 11, 2017
	 * author: wisnuwidi
	 * 
	 * @param int|string $id Group ID to edit
	 *
	 * @tutorial: Description Logic as Internal ROOT
	 * 		: 1. This form will rendering all fields in group table including [ $this->platform_key ].
	 * 		: 2. This form will rendering all checkbox privileges group when you edit other group except internal root (group) edit page.
	 * @tutorial: Description Logic as External Administrator ( users )
	 * 		: 1. This form will rendering all fields in group table except [ $this->platform_key ].
	 * 			 [ $this->platform_key ] data field would be posted into the database using [ $this->platform_key ] value saved in the user data sessions.
	 * 		: 2. This form will rendering all checkbox privileges group when you edit other group except internal root (group) and external admin(group) edit page.
	 *
	 * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
	 * 
	 * @security
	 * - Root group privileges cannot be modified (no privilege tabs shown)
	 * - Only root users and admins can edit group privileges
	 */
	public function edit($id): \Illuminate\View\View|\Illuminate\Contracts\View\Factory {	
		$this->setPage();
	//	$this->filterPage(['group_name' => 'admin']);
		$this->get_menu();

		$this->form->model();
		$this->form->text('group_name', null, ['required', 'readonly']);
		$this->form->text('group_info', null, ['required']);
		$this->form->text('group_alias', null);
		$this->form->selectbox('active', active_box(), $this->model_data->active, ['required']);

		if (1 === $this->session['group_id'] || true === canvastack_string_contained($this->session['user_group'], 'admin'))	{
			if ('root' !== $this->model_data->group_name) {
				// SET MODULE PRIVILEGES
				$this->form->openTab('Module Privileges');
				$this->form->draw($this->group_privilege());

				// SET PAGE PRIVILEGES
				$this->form->openTab('Mapping Page Privileges');
				$this->form->draw($this->mapping());

				$this->form->closeTab();

			}
		}

		$this->form->close('Update Group');

		return $this->render();
	}
	
	/**
	 * Update group with validation and transaction management
	 * 
	 * Updates an existing user group with validation, root group protection,
	 * and transaction management to ensure data consistency.
	 * 
	 * @param Request $request Update request with validated data
	 * @param int $id Group ID to update
	 * @return \Illuminate\Http\RedirectResponse Redirect to edit page
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException If ID is invalid
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerException If group not found or update fails
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\PrivilegeException If non-root user tries to modify root group
	 * 
	 * @security
	 * - Validates group ID is positive integer
	 * - Prevents non-root users from modifying root group
	 * - Logs unauthorized modification attempts
	 * - Uses database transactions for atomicity
	 * 
	 * @performance
	 * - Invalidates group cache after successful update
	 * - Invalidates privilege cache for the updated group
	 * 
	 * @example
	 * ```php
	 * $request = new Request([
	 *     'group_info' => 'Updated description',
	 *     'active' => 1
	 * ]);
	 * $controller->update($request, 5);
	 * ```
	 */
	public function update(Request $request, $id): \Illuminate\Http\RedirectResponse {
		$this->get_session();

		// 10.1: Validate $id parameter
		if (!is_numeric($id) || $id <= 0) {
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
				'Invalid group ID',
				['id' => $id]
			);
		}

		// Check if group exists
		$group = \App\Models\Admin\System\Group::find($id);
		if (!$group) {
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
				'Group not found',
				['id' => $id]
			);
		}

		// 10.2: Add root group protection
		if ($group->group_name === 'root' && $this->session['user_group'] !== 'root') {
			\Log::warning('Unauthorized attempt to modify root group', [
				'user_id' => $this->session['id'],
				'user_group' => $this->session['user_group'],
				'target_group_id' => $id
			]);

			throw new \Canvastack\Canvastack\Exceptions\Controller\PrivilegeException(
				'Non-root users cannot modify root group',
				[
					'user_id' => $this->session['id'],
					'user_group' => $this->session['user_group'],
					'target_group' => 'root'
				]
			);
		}

		// 10.3: Wrap update operations in transaction
		DB::beginTransaction();

		try {
			$this->set_data_before_insert($request, $id);
			$this->update_data($request, $id);
			$this->set_data_after_insert($this->roles);

			DB::commit();

			// 10.4: Add logging and cache invalidation
			\Log::info('Group updated successfully', [
				'group_id' => $id,
				'group_name' => $request->group_name ?? $group->group_name,
				'updated_by' => $this->session['id']
			]);

			// Invalidate caches
			$this->invalidateGroupCache();
			canvastack_invalidate_privilege_cache($id);
			$this->invalidateMappingCache();

			return self::redirect('edit', $request);

		} catch (\Exception $e) {
			DB::rollBack();

			\Log::error('Failed to update group', [
				'error' => $e->getMessage(),
				'group_id' => $id,
				'trace' => $e->getTraceAsString()
			]);

			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
				'Failed to update group: ' . $e->getMessage(),
				['original_error' => $e->getMessage(), 'group_id' => $id]
			);
		}
	}
	
	/**
	 * Prepare data before group insert/update with validation
	 * 
	 * Validates group existence and processes module privileges and page mapping
	 * data before inserting or updating a group. Handles both new group creation
	 * and existing group updates.
	 * 
	 * @param Request $request Request containing modules and mapping data
	 * @param int|bool $model_id Group ID for update, false for insert
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerException If group not found
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException If model_id is invalid
	 * 
	 * @example For new group (after insert)
	 * ```php
	 * $this->set_data_before_insert($request, false);
	 * ```
	 * 
	 * @example For existing group update
	 * ```php
	 * $this->set_data_before_insert($request, 5);
	 * ```
	 */
	private function set_data_before_insert(Request $request, int|bool $model_id = false): void {
		try {
			if (false === $model_id) {
				// For new group, find by name/alias/info
				$getGroup = canvastack_query($this->model_table)
					->where('group_name', $request->group_name)
					->where('group_alias', $request->group_alias)
					->where('group_info', $request->group_info)
					->first();
				
				if (!$getGroup) {
					\Log::error('Group not found after creation', [
						'group_name' => $request->group_name,
						'group_alias' => $request->group_alias,
						'group_info' => $request->group_info
					]);
					
					throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
						'Group not found after creation',
						[
							'group_name' => $request->group_name,
							'group_alias' => $request->group_alias
						]
					);
				}
			} else {
				// For update, validate and find by ID
				if (!is_numeric($model_id) || $model_id <= 0) {
					\Log::error('Invalid group ID provided', [
						'model_id' => $model_id,
						'type' => gettype($model_id)
					]);
					
					throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException(
						'Invalid group ID',
						['model_id' => $model_id]
					);
				}
				
				$getGroup = canvastack_query($this->model_table)
					->where('id', $model_id)
					->first();
				
				if (!$getGroup) {
					\Log::error('Group not found', [
						'group_id' => $model_id
					]);
					
					throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
						'Group not found',
						['group_id' => $model_id]
					);
				}
			}
			
			// Process privileges with error handling
			try {
				$this->privileges_before_insert($request, $getGroup);
			} catch (\Exception $e) {
				\Log::error('Failed to process privileges', [
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
					'group_id' => $getGroup->id ?? null
				]);
				
				throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
					'Failed to process privileges: ' . $e->getMessage(),
					['original_error' => $e->getMessage()]
				);
			}
			
			// Process mapping with error handling
			try {
				$this->mapping_before_insert($request, $getGroup);
			} catch (\Exception $e) {
				\Log::error('Failed to process page mapping', [
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
					'group_id' => $getGroup->id ?? null
				]);
				
				throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
					'Failed to process page mapping: ' . $e->getMessage(),
					['original_error' => $e->getMessage()]
				);
			}
			
		} catch (\Exception $e) {
			\Log::error('Failed to prepare group data', [
				'error' => $e->getMessage(),
				'model_id' => $model_id,
				'group_name' => $request->group_name ?? null
			]);
			
			throw $e;
		}
	}
	
	/**
	 * Insert privileges and mapping data after group creation
	 * 
	 * Processes and inserts module privileges and page mapping rules after
	 * a group has been created or updated. This is the final step in the
	 * group configuration process.
	 * 
	 * @param array $data Roles array containing privilege and mapping data
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerException If privilege insertion fails
	 * 
	 * @example
	 * ```php
	 * $this->set_data_after_insert($this->roles);
	 * ```
	 */
	private function set_data_after_insert(array $data): void {
		try {
			$this->privileges_after_insert($data);
		} catch (\Exception $e) {
			\Log::error('Failed to insert privileges', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'data_count' => is_array($data) ? count($data) : 0
			]);
			
			throw new \Canvastack\Canvastack\Exceptions\Controller\ControllerException(
				'Failed to insert privileges: ' . $e->getMessage(),
				['original_error' => $e->getMessage()]
			);
		}
	}
	
	/**
	 * Validate CSRF token for AJAX requests
	 * 
	 * This method explicitly validates CSRF tokens for AJAX requests that bypass
	 * the normal form submission flow. It checks for tokens in request body,
	 * X-CSRF-TOKEN header, and X-XSRF-TOKEN header.
	 * 
	 * @return void
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\CSRFException If token is missing or invalid
	 * 
	 * @security
	 * - Prevents CSRF attacks on AJAX endpoints
	 * - Logs failed validation attempts with IP and user agent
	 * - Uses constant-time comparison to prevent timing attacks
	 */
	private function validateAjaxCsrfToken(): void {
		$token = request()->input('_token') 
			?? request()->header('X-CSRF-TOKEN') 
			?? request()->header('X-XSRF-TOKEN');
		
		if (!$token || !hash_equals(session()->token(), $token)) {
			\Log::warning('CSRF token validation failed', [
				'user_id' => auth()->id(),
				'ip' => request()->ip(),
				'route' => request()->path(),
				'user_agent' => request()->userAgent()
			]);
			
			throw new \Canvastack\Canvastack\Exceptions\Controller\CSRFException('CSRF token mismatch');
		}
	}
	
	/**
	 * Invalidate all group list caches
	 * 
	 * Clears cached group lists for both root and non-root users. This method
	 * should be called after any group modification (create, update, delete)
	 * to ensure users see the latest group data.
	 * 
	 * Cache Strategy:
	 * - 'group_list_root': Cache for root users (includes root group)
	 * - 'group_list_admin': Cache for non-root users (excludes root group)
	 * - Clears specific cache keys (compatible with all cache drivers)
	 * 
	 * @return void
	 * 
	 * @example Called after successful group creation
	 * ```php
	 * DB::commit();
	 * $this->invalidateGroupCache();
	 * ```
	 */
	private function invalidateGroupCache(): void {
		// Clear specific cache keys
		// Note: We don't use cache tags as not all cache drivers support them
		Cache::forget('group_list_root');
		Cache::forget('group_list_admin');
		
		\Log::info('Group cache invalidated', [
			'keys_cleared' => ['group_list_root', 'group_list_admin']
		]);
	}
	
	/**
	 * Invalidate mapping page cache
	 * 
	 * Clears cached mapping page data for a specific user or all users.
	 * This method should be called after any group mapping modification
	 * to ensure users see the latest mapping configuration.
	 * 
	 * Cache Strategy:
	 * - If userId provided: Clears specific user's mapping caches (all routes)
	 * - If userId null: Clears all mapping caches using pattern matching
	 * - Cache keys follow pattern: 'mapping_page_{userId}_{route}'
	 * 
	 * @param int|null $userId User ID to clear cache for, or null to clear all
	 * @return void
	 * 
	 * @example Clear specific user's mapping cache
	 * ```php
	 * $this->invalidateMappingCache(5);
	 * ```
	 * 
	 * @example Clear all mapping caches
	 * ```php
	 * $this->invalidateMappingCache();
	 * ```
	 */
	private function invalidateMappingCache(?int $userId = null): void {
		if ($userId !== null) {
			// Clear specific user's mapping caches
			// We need to clear all routes for this user
			// Since we don't know all possible routes, we'll use a pattern
			$pattern = "mapping_page_{$userId}_*";
			
			// Get all cache keys matching the pattern
			// Note: This approach works with Redis and Memcached drivers
			// For file/database drivers, we'll need to track keys differently
			try {
				// Try to use Redis/Memcached pattern matching if available
				if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
					$redis = Cache::getStore()->connection();
					$keys = $redis->keys($pattern);
					foreach ($keys as $key) {
						Cache::forget($key);
					}
				} else {
					// For other cache drivers, we'll need to clear common routes
					// This is a fallback approach
					$commonRoutes = [
						'admin.system.group',
						'admin.system.user',
						'admin.system.module',
						'content.articles',
						'content.pages'
					];
					
					foreach ($commonRoutes as $route) {
						Cache::forget("mapping_page_{$userId}_{$route}");
					}
				}
				
				\Log::info('Mapping cache invalidated for user', [
					'user_id' => $userId
				]);
			} catch (\Exception $e) {
				\Log::warning('Failed to invalidate mapping cache with pattern, using fallback', [
					'user_id' => $userId,
					'error' => $e->getMessage()
				]);
			}
		} else {
			// Clear all mapping caches
			// For cache drivers that support tags, we could use Cache::tags(['mapping'])->flush()
			// But since not all drivers support tags, we'll use a different approach
			
			// Clear mapping caches for all known users
			// This is a best-effort approach
			try {
				if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
					$redis = Cache::getStore()->connection();
					$keys = $redis->keys('mapping_page_*');
					foreach ($keys as $key) {
						Cache::forget($key);
					}
					
					\Log::info('All mapping caches invalidated', [
						'keys_cleared' => count($keys)
					]);
				} else {
					// For other cache drivers, we'll clear caches for active users
					$activeUsers = \App\Models\Admin\System\User::where('active', 1)->pluck('id');
					$commonRoutes = [
						'admin.system.group',
						'admin.system.user',
						'admin.system.module',
						'content.articles',
						'content.pages'
					];
					
					$clearedCount = 0;
					foreach ($activeUsers as $uid) {
						foreach ($commonRoutes as $route) {
							Cache::forget("mapping_page_{$uid}_{$route}");
							$clearedCount++;
						}
					}
					
					\Log::info('Mapping caches invalidated for active users', [
						'users_count' => count($activeUsers),
						'keys_cleared' => $clearedCount
					]);
				}
			} catch (\Exception $e) {
				\Log::error('Failed to invalidate all mapping caches', [
					'error' => $e->getMessage()
				]);
			}
		}
	}
}
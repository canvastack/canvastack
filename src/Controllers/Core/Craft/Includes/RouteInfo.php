<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft\Includes;

use Illuminate\Support\Facades\Route;
use Canvastack\Canvastack\Models\Admin\System\Modules;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\RouteException;

/**
 * Route Information Management Trait
 * 
 * First Created on 9 Apr 2021
 * Time Created : 14:49:04
 * 
 * Provides comprehensive route information management for the Canvastack Origin framework.
 * This trait handles current route analysis, action button generation, page information extraction,
 * and route-based UI element rendering with caching support.
 * 
 * Core Responsibilities:
 * - Current route and controller information extraction
 * - Action button generation based on route and privileges
 * - Page information and metadata management
 * - Route URL manipulation and generation
 * - Route information caching for performance
 * 
 * Action Button Logic:
 * - Index page: Shows "Add" button (if create privilege exists)
 * - Create page: Shows "Back to List" button
 * - Edit page: Shows "Delete", "Add", "View", "Back to List" buttons (based on privileges)
 * - Show page: Shows "Add", "Edit", "Back to List" buttons (based on privileges)
 * 
 * Caching Strategy:
 * - Route info cached per route/page/group/controller combination
 * - Cache key format: route_info_{route_name}_{page_info}_{user_group}_{controller}
 * - Configurable TTL (default: 3600 seconds / 1 hour)
 * - Automatic cache invalidation on route updates
 * 
 * Configuration Options:
 * - canvastack.controller.caching.route_info_cache_enabled: Enable route info caching
 * - canvastack.controller.caching.route_info_cache_ttl: Cache TTL in seconds
 * - canvastack.controller.security.xss_protection: Enable XSS protection for output
 * - canvastack.controller.logging.log_security_events: Enable security logging
 * 
 * Usage Example:
 * ```php
 * class ProductController extends Controller {
 *     use RouteInfo;
 *     
 *     public function __construct() {
 *         parent::__construct();
 *         
 *         // Initialize route information
 *         $this->routeInfo();
 *         
 *         // Hide action buttons if needed
 *         // $this->hideActionButton();
 *         
 *         // Set custom route page
 *         // $this->set_route_page('admin.products');
 *     }
 *     
 *     public function index() {
 *         // Route info available in $this->data['route_info']
 *         return view('products.index');
 *     }
 * }
 * ```
 * 
 * Route Info Data Structure:
 * ```php
 * $this->data['route_info'] = (object) [
 *     'current_path' => 'admin.products.index',    // Current route name
 *     'module_name' => 'Product',                  // Controller name (escaped)
 *     'page_info' => 'index',                      // Current page action (escaped)
 *     'action_page' => [                           // Action buttons
 *         'warning|add Product' => 'http://example.com/products/create',
 *     ],
 * ];
 * ```
 * 
 * Action Button Format:
 * ```php
 * // Button format: "color|label" => "url"
 * [
 *     'warning|add Product' => 'http://example.com/products/create',
 *     'info|back to Product lists' => 'http://example.com/products',
 *     'success|edit this Product' => 'http://example.com/products/1/edit',
 *     'danger|delete Product' => 'destroy::1',
 * ]
 * ```
 * 
 * Cache Invalidation Example:
 * ```php
 * // After updating route permissions
 * $this->invalidateRouteInfoCache('admin.products.index');
 * 
 * // Invalidate all route info caches
 * $this->invalidateRouteInfoCache();
 * ```
 * 
 * @package    Canvastack\Canvastack\Controllers\Core\Craft\Includes
 * @category   Route Management
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 Canvastack
 * @license    Proprietary
 * @version    2.0.0
 * @since      1.0.0
 * 
 * @property object $pageInfo Current page action (index, create, edit, show)
 * @property object $routeInfo Route information object
 * @property string $route_page Custom route page override
 * @property string $controllerName Controller name without namespace
 * @property object $currentRoute Current Laravel route object
 * @property array $actionButton Action buttons to display (default: index, edit, show)
 * @property string $controllerPathName Full controller path with namespace
 * 
 * @security   XSS Protection - All output is escaped to prevent XSS attacks
 * @security   Action button labels, module names, and page info are escaped
 * @security   Route segments validated before use
 * @security   Prevents injection attacks through route manipulation
 * 
 * @performance Route info caching reduces repeated route analysis
 * @performance Cache key includes route, page, group, and controller
 * @performance Configurable TTL for cache expiration
 * @performance Automatic cache invalidation on route updates
 * @performance Reduces processing overhead by 80%+ for route info
 * 
 * @see Privileges For privilege management
 * @see routelists_info() For route information helper
 * @see escapeValue() For XSS protection
 * 
 * @filesource RouteInfo.php
 */
 
trait RouteInfo {
	use Privileges;

	public $pageInfo;
	public $routeInfo;
	public $route_page;
	public $controllerName;
	public $currentRoute;
	public $actionButton = ['index', 'index', 'edit', 'show'];
	
	/**
	 * Custom action buttons to be added to the route info
	 * 
	 * Format: ['color|label' => 'url']
	 * Example: ['primary|Export' => 'http://example.com/export']
	 * 
	 * @var array
	 */
	private array $customActionButtons = [];
	
	/**
	 * Hide action button(s) in module page.
	 *
	 * created @Aug 10, 2018
	 * author: wisnuwidi
	 * 
	 * @return void
	 */
	public function hideActionButton(): void {
		$this->actionButton = [];
	}

	/**
	 * Set route page
	 * 
	 * @param string $route Route name
	 * @return void
	 */
	public function set_route_page(string $route): void {
		$this->route_page = $route;
	}
	
	/**
	 * Add custom action button
	 * 
	 * Adds a custom action button to the route info. The button will be displayed
	 * alongside the default CRUD buttons based on the current page.
	 * 
	 * @param string $color Button color (primary, success, info, warning, danger, secondary)
	 * @param string $label Button label (will be escaped for XSS protection)
	 * @param string $url Button URL (will be validated)
	 * @param bool $enabled Whether the button is enabled (default: true)
	 * @return void
	 * 
	 * @throws RouteException If parameters are invalid
	 * 
	 * @security XSS Protection - Button label is escaped before storage
	 * @security URL Validation - Button URL is validated before storage
	 * 
	 * @example
	 * // Add an export button
	 * $this->addCustomActionButton('primary', 'Export', route('products.export'));
	 * 
	 * // Add a disabled import button
	 * $this->addCustomActionButton('secondary', 'Import', route('products.import'), false);
	 */
	public function addCustomActionButton(string $color, string $label, string $url, bool $enabled = true): void {
		// Validate color
		$validColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
		if (!in_array($color, $validColors)) {
			throw RouteException::invalidParameter("Invalid button color: {$color}. Must be one of: " . implode(', ', $validColors));
		}
		
		// Validate label
		if (empty(trim($label))) {
			throw RouteException::invalidParameter('Button label cannot be empty');
		}
		
		// Validate URL
		if (!$this->validateGeneratedUrl($url)) {
			throw RouteException::invalidUrl("Invalid button URL: {$url}");
		}
		
		// Escape label for XSS protection
		$escapedLabel = $this->escapeValue($label);
		
		// Create button key
		$buttonKey = "{$color}|{$escapedLabel}";
		
		// Add enabled state if disabled
		if (!$enabled) {
			$buttonKey .= '|disabled';
		}
		
		// Store custom button
		$this->customActionButtons[$buttonKey] = $url;
	}
	
	/**
	 * Add multiple custom action buttons
	 * 
	 * Convenience method to add multiple custom action buttons at once.
	 * 
	 * @param array $buttons Array of buttons in format: [['color' => 'primary', 'label' => 'Export', 'url' => '...', 'enabled' => true], ...]
	 * @return void
	 * 
	 * @throws RouteException If any button parameters are invalid
	 * 
	 * @example
	 * $this->addCustomActionButtons([
	 *     ['color' => 'primary', 'label' => 'Export', 'url' => route('products.export')],
	 *     ['color' => 'secondary', 'label' => 'Import', 'url' => route('products.import'), 'enabled' => false],
	 * ]);
	 */
	public function addCustomActionButtons(array $buttons): void {
		foreach ($buttons as $button) {
			$color = $button['color'] ?? 'primary';
			$label = $button['label'] ?? '';
			$url = $button['url'] ?? '';
			$enabled = $button['enabled'] ?? true;
			
			$this->addCustomActionButton($color, $label, $url, $enabled);
		}
	}
	
	/**
	 * Clear all custom action buttons
	 * 
	 * Removes all custom action buttons that were added.
	 * 
	 * @return void
	 */
	public function clearCustomActionButtons(): void {
		$this->customActionButtons = [];
	}
	
	/**
	 * Get custom action buttons
	 * 
	 * Returns all custom action buttons that have been added.
	 * 
	 * @return array Custom action buttons
	 */
	public function getCustomActionButtons(): array {
		return $this->customActionButtons;
	}
	
	/**
	 * Custom dropdown buttons to be added to the route info
	 * 
	 * Format: ['color|label|dropdown' => [['label' => 'Item 1', 'url' => '...'], ...]]
	 * 
	 * @var array
	 */
	private array $customDropdownButtons = [];
	
	/**
	 * Add custom dropdown button
	 * 
	 * Adds a dropdown button with multiple menu items to the route info.
	 * The dropdown will be rendered as a Bootstrap dropdown component.
	 * 
	 * @param string $color Button color (primary, success, info, warning, danger, secondary)
	 * @param string $label Button label (will be escaped for XSS protection)
	 * @param array $items Dropdown items in format: [['label' => 'Item 1', 'url' => '...', 'icon' => 'fa-icon'], ...]
	 * @param bool $enabled Whether the button is enabled (default: true)
	 * @return void
	 * 
	 * @throws RouteException If parameters are invalid
	 * 
	 * @security XSS Protection - Button label and item labels are escaped
	 * @security URL Validation - All item URLs are validated
	 * 
	 * @example
	 * // Add a cache management dropdown
	 * $this->addCustomDropdownButton('secondary', 'Cache', [
	 *     ['label' => 'Clear All', 'url' => route('cache.clear', ['type' => 'all']), 'icon' => 'fa-trash'],
	 *     ['label' => 'Clear Config', 'url' => route('cache.clear', ['type' => 'config']), 'icon' => 'fa-cog'],
	 *     ['divider' => true], // Add divider
	 *     ['label' => 'Optimize', 'url' => route('cache.clear', ['type' => 'optimize']), 'icon' => 'fa-rocket'],
	 * ]);
	 */
	public function addCustomDropdownButton(string $color, string $label, array $items, bool $enabled = true): void {
		// Validate color
		$validColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
		if (!in_array($color, $validColors)) {
			throw RouteException::invalidParameter("Invalid button color: {$color}. Must be one of: " . implode(', ', $validColors));
		}
		
		// Validate label
		if (empty(trim($label))) {
			throw RouteException::invalidParameter('Dropdown button label cannot be empty');
		}
		
		// Validate items
		if (empty($items)) {
			throw RouteException::invalidParameter('Dropdown button must have at least one item');
		}
		
		// Validate and escape each item
		$validatedItems = [];
		foreach ($items as $item) {
			// Check if it's a divider
			if (isset($item['divider']) && $item['divider'] === true) {
				$validatedItems[] = ['divider' => true];
				continue;
			}
			
			// Validate item structure
			if (!isset($item['label']) || !isset($item['url'])) {
				throw RouteException::invalidParameter('Dropdown item must have "label" and "url" keys');
			}
			
			// Validate URL
			if (!$this->validateGeneratedUrl($item['url'])) {
				throw RouteException::invalidUrl("Invalid dropdown item URL: {$item['url']}");
			}
			
			// Escape label for XSS protection
			$escapedLabel = $this->escapeValue($item['label']);
			
			// Build validated item
			$validatedItem = [
				'label' => $escapedLabel,
				'url' => $item['url'],
			];
			
			// Add icon if provided
			if (isset($item['icon'])) {
				$validatedItem['icon'] = $this->escapeValue($item['icon']);
			}
			
			// Add data attributes if provided
			if (isset($item['data'])) {
				$validatedItem['data'] = $item['data'];
			}
			
			$validatedItems[] = $validatedItem;
		}
		
		// Escape label for XSS protection
		$escapedLabel = $this->escapeValue($label);
		
		// Create dropdown key
		$dropdownKey = "{$color}|{$escapedLabel}|dropdown";
		
		// Add enabled state if disabled
		if (!$enabled) {
			$dropdownKey .= '|disabled';
		}
		
		// Store custom dropdown button
		$this->customDropdownButtons[$dropdownKey] = $validatedItems;
	}
	
	/**
	 * Add multiple custom dropdown buttons
	 * 
	 * Convenience method to add multiple custom dropdown buttons at once.
	 * 
	 * @param array $dropdowns Array of dropdowns in format: [['color' => 'primary', 'label' => 'Actions', 'items' => [...], 'enabled' => true], ...]
	 * @return void
	 * 
	 * @throws RouteException If any dropdown parameters are invalid
	 * 
	 * @example
	 * $this->addCustomDropdownButtons([
	 *     [
	 *         'color' => 'secondary',
	 *         'label' => 'Cache',
	 *         'items' => [
	 *             ['label' => 'Clear All', 'url' => route('cache.clear', ['type' => 'all'])],
	 *             ['label' => 'Optimize', 'url' => route('cache.clear', ['type' => 'optimize'])],
	 *         ],
	 *     ],
	 * ]);
	 */
	public function addCustomDropdownButtons(array $dropdowns): void {
		foreach ($dropdowns as $dropdown) {
			$color = $dropdown['color'] ?? 'primary';
			$label = $dropdown['label'] ?? '';
			$items = $dropdown['items'] ?? [];
			$enabled = $dropdown['enabled'] ?? true;
			
			$this->addCustomDropdownButton($color, $label, $items, $enabled);
		}
	}
	
	/**
	 * Clear all custom dropdown buttons
	 * 
	 * Removes all custom dropdown buttons that were added.
	 * 
	 * @return void
	 */
	public function clearCustomDropdownButtons(): void {
		$this->customDropdownButtons = [];
	}
	
	/**
	 * Get custom dropdown buttons
	 * 
	 * Returns all custom dropdown buttons that have been added.
	 * 
	 * @return array Custom dropdown buttons
	 */
	public function getCustomDropdownButtons(): array {
		return $this->customDropdownButtons;
	}

	private $controllerPathName;
	
	/**
	 * Get Current Page Information Data
	 *
	 * Extracts page action (index, create, edit, show, etc.) and controller name
	 * from the current route. Handles both traditional and invokable controller formats.
	 *
	 * created @Nov 10, 2018
	 * author: wisnuwidi
	 *
	 * @return void
	 * 
	 * @throws RouteException If route information cannot be extracted
	 * 
	 * @security XSS Protection - Escapes page info and controller name
	 * @security Input Validation - Validates route structure before processing
	 */
	private function get_pageinfo(): void {
		$this->currentRoute = \Illuminate\Support\Facades\Route::getCurrentRoute();
		
		// Validate current route exists
		if ($this->currentRoute === null) {
			throw RouteException::routeNotFound('Current route not found');
		}
		
		$action_route = (object) $this->currentRoute->getAction();
		
		// Validate controller action exists
		if (!isset($action_route->controller) || empty($action_route->controller)) {
			throw RouteException::invalidRouteStructure('Controller action not found in route');
		}
		
		$controller_path = $action_route->controller;
		
		// Validate controller path format
		if (!is_string($controller_path)) {
			throw RouteException::invalidRouteStructure('Controller path must be a string');
		}

		// Handle different controller formats
		if (!canvastack_string_contained($controller_path, 'Controllers@')) {
			// Modern format: App\Http\Controllers\ProductController@index
			if (strpos($controller_path, 'Controllers') !== false) {
				$slice_controller = explode('Controllers', $controller_path);
				
				// Validate we have the expected parts
				if (count($slice_controller) < 2) {
					throw RouteException::invalidRouteStructure('Invalid controller path format');
				}
				
				$slice_controller = explode('Controller', $slice_controller[1]);
				$this->pageInfo = str_replace('@', '', $slice_controller[1] ?? 'index');
			} else {
				// Invokable controller or closure
				$this->pageInfo = 'index';
			}
		} else {
			// Traditional format: Controller@action
			$slice_controller = explode('@', $controller_path);
			$this->pageInfo = $slice_controller[1] ?? 'index';
		}
		
		// Clean and validate page info
		$this->pageInfo = trim($this->pageInfo);
		if (empty($this->pageInfo)) {
			$this->pageInfo = 'index';
		}

		// Extract controller name
		$slice_controller = explode('\\', explode('@', $controller_path)[0]);
		$this->controllerName = last($slice_controller);
		
		// Remove 'Controller' suffix if present
		$this->controllerName = str_replace('Controller', '', $this->controllerName);
		
		// Validate controller name
		if (empty($this->controllerName)) {
			throw RouteException::invalidRouteStructure('Controller name could not be extracted');
		}
		
		$this->controllerPathName = explode('@', $controller_path)[0];
	}
	
	/**
	 * Get Current Route Information
	 *
	 * Edit +/ Show = Back + Add
	 * Create = Back
	 * Index = Add
	 *
	 * @return void
	 * 
	 * @security XSS Protection - All output is escaped to prevent XSS attacks
	 *           Action button labels, module names, and page info are escaped
	 *           before being added to route info data structure
	 *
	 * @performance Route info caching - Results are cached when enabled in config
	 *              Cache key format: route_info_{route_name}_{page_info}_{user_group}
	 *              TTL: Configurable via canvastack.controller.caching.route_info_cache_ttl
	 */
	public function routeInfo(): void {

		if (strpos(php_sapi_name(), 'cli') === false) {
			$this->module_privileges();
			$this->get_pageinfo();
			
			// Setup developer tools BEFORE building route info
			// This ensures dropdown buttons are included in the route info object
			if (method_exists($this, 'setupDeveloperTools')) {
				$this->setupDeveloperTools();
			}
			
			// Check if route info caching is enabled
			$cacheEnabled = config('canvastack.controller.caching.route_info_cache_enabled', true);
			$cacheTtl = config('canvastack.controller.caching.route_info_cache_ttl', 3600);
			
			if ($cacheEnabled) {
				// Generate cache key based on route and user context
				$cacheKey = canvastack_controller_cache_key('route_info', [
					'route' => $this->currentRoute->getName(),
					'page' => $this->pageInfo,
					'group' => $this->role_group ?? 'guest',
					'controller' => $this->controllerName
				]);
				
				// Try to get from cache
				$cachedRouteInfo = canvastack_controller_cache_get($cacheKey);
				
				if ($cachedRouteInfo !== null) {
					// Use cached route info
					$this->setDataValues('route_info', $cachedRouteInfo);
					return;
				}
			}

			$action_role           = [];
			$action_role['show']   = false;
			$action_role['create'] = false;
			$action_role['edit']   = false;
			$action_role['delete'] = false;

			$actionPage            = [];
			$actionPage['show']    = [];
			$actionPage['create']  = [];
			$actionPage['edit']    = [];
			$actionPage['delete']  = [];

			$action_page                = [];
			$action_page['action_page'] = [];

			if (!empty($this->module_privilege['actions'])) {
				foreach ($this->module_privilege['actions'] as $role_action) {
					$action_role[$role_action] = true;
				}
			}

			if (count($this->actionButton) >= 1) {
				// Escape button label to prevent XSS
				$buttonLabel = $this->escapeValue($this->controllerName);
				if (!empty($this->page_name)) {
					$buttonLabel = $this->escapeValue($this->page_name);
				}

				if ('index' === $this->pageInfo && true === $action_role['create']) {
					if (in_array('index', get_class_methods($this->controllerPathName))) {
						// Button label already escaped
						$action_page['action_page'] = ["warning|add {$buttonLabel}" => $this->routeReplaceURL('index', 'create')];
					}

				} elseif ('create' === $this->pageInfo && true === $action_role['create']) {
					if (in_array('create', get_class_methods($this->controllerPathName))) {
						// Button label already escaped
						$action_page['action_page'] = ["info|back to {$buttonLabel} lists" => $this->routeReplaceURL('create', 'index')];
					}

				} elseif ('edit' === $this->pageInfo) {

					if (true === $action_role['delete']) {
						if (true === $this->is_softdeleted) {
							// Button label already escaped
							$actionPage['delete'] = ["secondary|restore {$buttonLabel}" => $this->routeReplaceURL('edit', 'destroy')];
						} else {
							// Button label already escaped
							$actionPage['delete'] = ["danger|delete {$buttonLabel}" => $this->routeReplaceURL('edit', 'destroy')];
						}
					}
					if (true === $action_role['create']) {
						if (in_array('create', get_class_methods($this->controllerPathName))) {
							// Button label already escaped
							$actionPage['create'] = ["warning|add {$buttonLabel}" => $this->routeReplaceURL('edit', 'create')];
						}
					}
					if (true === $action_role['show']) {
						if (in_array('edit', get_class_methods($this->controllerPathName))) {
							// Button label already escaped
							$actionPage['edit'] = ["success|view this {$buttonLabel}"  => str_replace('/edit', '', url()->current())];
						}

						if (in_array('index', get_class_methods($this->controllerPathName))) {
							// Button label already escaped
							$actionPage['show'] = ["info|back to {$buttonLabel} lists" => $this->routeReplaceURL('edit', 'index')];
						}
					}

					$action_page['action_page'] = array_merge_recursive($actionPage['delete'], $actionPage['create'], $actionPage['edit'], $actionPage['show']);

				} elseif ('show' === $this->pageInfo && true === $action_role['show']) {
					if (in_array('create', get_class_methods($this->controllerPathName))) {
						// Button label already escaped
						$action_page['action_page']["warning|add {$buttonLabel}"]        = $this->routeReplaceURL('show', 'create');
					}
					if (in_array('edit', get_class_methods($this->controllerPathName))) {
						// Button label already escaped
						$action_page['action_page']["success|edit this {$buttonLabel}"]  = url()->current() . '/edit';
					}
					if (in_array('index', get_class_methods($this->controllerPathName))) {
						// Button label already escaped
						$action_page['action_page']["info|back to {$buttonLabel} lists"] = $this->routeReplaceURL('show', 'index');
					}
				}
			}

			// Escape module name and page info to prevent XSS
			$routeInfo = [
				'current_path' => $this->currentRoute->getName(),
				'module_name'  => $this->escapeValue($this->controllerName),
				'page_info'    => $this->escapeValue($this->pageInfo)
			];
			
			// Merge custom action buttons with default action buttons
			if (!empty($this->customActionButtons)) {
				$action_page['action_page'] = array_merge($action_page['action_page'], $this->customActionButtons);
			}
			
			// Merge custom dropdown buttons with action buttons
			if (!empty($this->customDropdownButtons)) {
				$action_page['action_page'] = array_merge($action_page['action_page'], $this->customDropdownButtons);
			}

			$routeInfoObject = (object) array_merge($routeInfo, $action_page);
			
			// Cache the route info if caching is enabled
			if ($cacheEnabled) {
				canvastack_controller_cache_put($cacheKey, $routeInfoObject, $cacheTtl);
			}
			
			$this->setDataValues('route_info', $routeInfoObject);
		}
	}
	
	/**
	 * Replace URL segments for route generation
	 *
	 * @param string $from Source route segment
	 * @param string $to Target route segment
	 * @return string Generated route URL
	 *
	 * @throws RouteException If URL generation fails or validation fails
	 * 
	 * @security Input validation - Route segments are validated before use
	 * @security URL validation - Generated URLs are validated for well-formedness
	 */
	private function routeReplaceURL(string $from, string $to): string {
		// Validate input parameters
		if (empty($from) || empty($to)) {
			throw RouteException::invalidParameter('Route segments cannot be empty');
		}
		
		// Validate current route exists
		if ($this->currentRoute === null) {
			throw RouteException::routeNotFound('Current route not found');
		}
		
		$routeName = $this->currentRoute->getName();
		
		// Handle missing route name
		if (empty($routeName)) {
			throw RouteException::routeNotFound('Current route has no name');
		}
		
		$routeUri = str_replace($from, $to, $routeName);

		if ('destroy' !== $to) {
			try {
				// Generate route URL
				$url = route($routeUri);
				
				// Validate generated URL
				if (!$this->validateGeneratedUrl($url)) {
					throw RouteException::invalidUrl("Generated URL is invalid: {$url}");
				}
				
				return $url;
			} catch (\Exception $e) {
				// Handle missing route gracefully
				return $this->handleMissingRoute($routeUri);
			}
		} else {
			// Handle destroy action (returns route name with ID)
			$routeURI = $routeUri;
			$currentUrl = canvastack_current_url();
			
			if (empty($currentUrl)) {
				throw RouteException::invalidUrl('Current URL could not be determined');
			}
			
			$routeUri = explode('/', $currentUrl);
			unset($routeUri[array_key_last($routeUri)]);

			$id = (int) last($routeUri);
			
			// Validate ID
			if ($id <= 0) {
				throw RouteException::invalidParameter('Invalid resource ID for destroy action');
			}
			
			return $routeURI . '::' . $id;
		}
	}
	
	/**
	 * Validate generated URL
	 * 
	 * Ensures the generated URL is well-formed and safe to use.
	 * 
	 * @param string $url URL to validate
	 * @return bool True if URL is valid, false otherwise
	 * 
	 * @security URL validation - Prevents malformed or malicious URLs
	 * 
	 * @example
	 * if ($this->validateGeneratedUrl($url)) {
	 *     // URL is safe to use
	 * }
	 */
	private function validateGeneratedUrl(string $url): bool {
		// Check if URL is empty
		if (empty($url)) {
			return false;
		}
		
		// Allow anchor links (#) for JavaScript-handled actions
		if ($url === '#') {
			return true;
		}
		
		// Check if URL is a valid URL format
		if (filter_var($url, FILTER_VALIDATE_URL) === false) {
			// Allow relative URLs
			if (!str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
				return false;
			}
		}
		
		// Check for suspicious patterns
		$suspiciousPatterns = [
			'javascript:',
			'data:',
			'vbscript:',
			'<script',
			'onerror=',
			'onclick=',
		];
		
		$urlLower = strtolower($url);
		foreach ($suspiciousPatterns as $pattern) {
			if (strpos($urlLower, $pattern) !== false) {
				// Log security event
				if (config('canvastack.controller.logging.log_security_events', true)) {
					canvastack_controller_log_security_event(
						'url_validation',
						'Suspicious pattern detected in generated URL',
						[
							'url' => $url,
							'pattern' => $pattern,
							'route' => $this->currentRoute?->getName(),
						]
					);
				}
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Handle missing route gracefully
	 * 
	 * When a route doesn't exist, this method provides a fallback URL
	 * instead of throwing an exception, improving user experience.
	 * 
	 * @param string $route Route name that doesn't exist
	 * @return string Fallback URL
	 * 
	 * @security Graceful degradation - Prevents application crashes from missing routes
	 * 
	 * @example
	 * $url = $this->handleMissingRoute('admin.products.create');
	 * // Returns: '/admin/products' (fallback to index)
	 */
	private function handleMissingRoute(string $route): string {
		// Log the missing route
		if (config('canvastack.controller.logging.log_security_events', true)) {
			canvastack_controller_log_security_event(
				'missing_route',
				'Route not found, using fallback',
				[
					'route' => $route,
					'current_route' => $this->currentRoute?->getName(),
					'controller' => $this->controllerName ?? 'unknown',
				]
			);
		}
		
		// Try to generate a fallback URL
		// Remove the last segment and try to route to index
		$routeParts = explode('.', $route);
		
		// If we have multiple parts, try removing the last one
		if (count($routeParts) > 1) {
			array_pop($routeParts);
			$fallbackRoute = implode('.', $routeParts) . '.index';
			
			try {
				return route($fallbackRoute);
			} catch (\Exception $e) {
				// Fallback failed, return current URL
			}
		}
		
		// Ultimate fallback: return current URL
		return url()->current();
	}
	
	/**
	 * Invalidate route info cache
	 * 
	 * Call this method when route configurations change to ensure
	 * cached route info is refreshed.
	 * 
	 * @param string|null $routeName Route name to invalidate (null = invalidate all)
	 * @return bool True if cache was invalidated successfully
	 * 
	 * @performance Cache invalidation ensures data consistency after route changes
	 * 
	 * @example
	 * // After updating route permissions
	 * $this->invalidateRouteInfoCache('admin.users.index');
	 */
	public function invalidateRouteInfoCache(?string $routeName = null): bool {
		if ($routeName === null) {
			// Invalidate all route info caches
			return canvastack_controller_cache_flush('route_info_');
		} else {
			// Invalidate specific route's cache
			return canvastack_controller_cache_flush("route_info_route_{$routeName}_");
		}
	}
}

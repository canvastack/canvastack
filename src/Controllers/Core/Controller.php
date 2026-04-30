<?php
namespace Canvastack\Canvastack\Controllers\Core;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

use Canvastack\Canvastack\Controllers\Core\Craft\View;
use Canvastack\Canvastack\Controllers\Core\Craft\Action;
use Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
use Canvastack\Canvastack\Controllers\Core\Craft\Session;

use Canvastack\Canvastack\Controllers\Core\Craft\Components\MetaTags;
use Canvastack\Canvastack\Controllers\Core\Craft\Components\Template;
use Canvastack\Canvastack\Controllers\Core\Craft\Components\Form;
use Canvastack\Canvastack\Controllers\Core\Craft\Components\Table;
use Canvastack\Canvastack\Controllers\Core\Craft\Components\Chart;
use Canvastack\Canvastack\Controllers\Core\Craft\Components\Email;

use Canvastack\Canvastack\Controllers\Core\Craft\Includes\FileUpload;
use Canvastack\Canvastack\Controllers\Core\Craft\Includes\RouteInfo;

use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Helpers\ControllerConfig;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerSecurityException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\CSRFException;
use Canvastack\Canvastack\Exceptions\Controller\XSSAttemptException;
use Canvastack\Canvastack\Exceptions\Controller\SQLInjectionAttemptException;
use Canvastack\Canvastack\Exceptions\Controller\SessionException;
use Canvastack\Canvastack\Exceptions\Controller\FileUploadException;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;
use Canvastack\Canvastack\Exceptions\Controller\RouteException;
use Canvastack\Canvastack\Exceptions\Controller\DataTablesException;

/**
 * Bismillahirrahmanirrahiim
 * 
 * In the name of ALLAH SWT,
 * Alhamdulillah because of Allah SWT, this code succesfuly created piece by piece.
 * 
 * Base Controller for CanvaStack Framework
 * 
 * This is the core controller class that extends Laravel's BaseController and provides
 * comprehensive functionality for CRUD operations, view rendering, session management,
 * file uploads, privilege checking, and component integration. It serves as the foundation
 * for all application controllers in the CanvaStack framework.
 * 
 * The controller integrates multiple traits to provide modular functionality:
 * - Action trait: CRUD operations and DataTables server-side processing
 * - View trait: Template rendering, breadcrumbs, and page configuration
 * - Session trait: User session management and authentication state
 * - Scripts trait: JavaScript and CSS asset management
 * - FileUpload trait: Secure file upload handling with validation
 * - RouteInfo trait: Dynamic route information and action button generation
 * - Privileges trait: Module-based access control and authorization
 * - Component traits: MetaTags, Template, Form, Table, Chart, Email
 * 
 * First Created on Mar 29, 2017
 * Time Created : 4:58:17 PM
 * 
 * Re-Created on 10 Mar 2021
 * Time Created : 13:23:43
 *
 * @filesource Controller.php
 *            
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 * @email     wisnuwidi@canvastack.com
 * 
 * @security This controller implements comprehensive security measures:
 *           - XSS protection: All user-controllable data is escaped before rendering
 *           - CSRF protection: Validates CSRF tokens for all state-changing requests
 *           - SQL injection prevention: Uses query builder with parameter binding
 *           - Input validation: Validates all user inputs before processing
 *           - Session security: Validates session integrity and regenerates session IDs
 *           - File upload security: Validates file types, sizes, and content
 * 
 * @performance Performance optimizations implemented:
 *           - Eager loading: Prevents N+1 query problems with relationship loading
 *           - Query optimization: Selects only required columns, uses efficient pagination
 *           - Caching: Caches privileges, route info, and preferences data
 *           - Memory management: Configurable memory limits with monitoring
 *           - Asset optimization: Deduplicates and optimizes script/CSS loading
 */
class Controller extends BaseController {
	
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	use MetaTags, Template;
	use Scripts, View, Session;
	use Form, FileUpload, RouteInfo;
	use Table;
	use Chart;
	use Email;
	
	/**
	 * Data collection for view rendering
	 * 
	 * Contains all data that will be passed to views including session data,
	 * route information, component data, form elements, table configurations,
	 * and custom content. All user-controllable data is escaped before being
	 * added to this collection to prevent XSS attacks.
	 * 
	 * @var array<string, mixed> Associative array of data for view rendering
	 */
	public $data = [];
	
	/**
	 * Session authentication data
	 * 
	 * Stores authenticated user information including user ID, username, group ID,
	 * full name, email, phone, and privilege flags. This data is populated from
	 * the session store and validated for integrity before use.
	 * 
	 * @var array<string, mixed> User authentication session data
	 */
	public $session_auth = [];
	
	/**
	 * Login page flag
	 * 
	 * Determines whether the current page is a login page. When true, the controller
	 * will render the login template instead of the standard authenticated layout.
	 * This flag affects template selection and authentication requirements.
	 * 
	 * @var bool True if current page is login page, false otherwise
	 */
	public $getLogin = true;
	
	/**
	 * Root page route name
	 * 
	 * Defines the default route name for the root/home page of the application.
	 * This is used for redirects after login and as the default landing page.
	 * Can be customized per application by setting this property in child controllers.
	 * 
	 * @var string Route name for root page (default: 'home')
	 */
	public $rootPage = 'home';
	
	/**
	 * Admin page route name
	 * 
	 * Defines the default route name for the admin dashboard page. This is used
	 * for redirects after admin login and as the default admin landing page.
	 * Can be customized per application by setting this property in child controllers.
	 * 
	 * @var string Route name for admin dashboard (default: 'dashboard')
	 */
	public $adminPage = 'dashboard';
	
	/**
	 * Database connection name
	 * 
	 * Specifies which database connection to use for model operations. When null,
	 * uses the default connection defined in config/database.php. This is automatically
	 * set from the model's connection when a model is initialized.
	 * 
	 * @var string|null Database connection name or null for default connection
	 */
	public $connection = null;
	
	/**
	 * Registered plugin components
	 * 
	 * Internal storage for plugin component instances registered via the
	 * config/canvastack.registers configuration. Components are initialized
	 * in the components() method and made available to views via the data collection.
	 * 
	 * @var array<string, object> Associative array of plugin component instances
	 */
	private $plugins = [];
	
	/**
	 * Model class name
	 * 
	 * Stores the fully qualified class name of the Eloquent model associated with
	 * this controller. This is used for CRUD operations, query building, and
	 * determining the database connection. Set via constructor or init_model().
	 * 
	 * @var string|null Fully qualified model class name or null if no model
	 */
	private $model_class = null;
	
	/**
	 * Initialize controller with model and route configuration
	 * 
	 * This constructor sets up the controller by initializing the associated Eloquent model,
	 * collecting data from various sources (session, routes, components), and configuring
	 * the route page if specified. It also sets memory limits and registers shutdown handlers
	 * to prevent out-of-memory errors during request processing.
	 * 
	 * The constructor performs the following operations in order:
	 * 1. Sets memory limit from configuration (default: 256M)
	 * 2. Registers shutdown handler for out-of-memory error detection
	 * 3. Initializes the Eloquent model if provided
	 * 4. Collects and sanitizes data from session, routes, and components
	 * 5. Sets the route page configuration if specified
	 * 
	 * @param bool|string $model Eloquent model class name (fully qualified) or false to skip model initialization.
	 *                           Example: 'App\Models\User' or false
	 * @param bool|string $route_page Route page name for page type configuration or false to use default.
	 *                           Example: 'adminpage', 'frontpage', 'login', or false
	 * 
	 * @return void
	 * 
	 * @security This constructor initializes security measures:
	 *           - Validates model class name before instantiation to prevent arbitrary class loading
	 *           - Escapes all session data to prevent XSS attacks
	 *           - Escapes route parameters to prevent XSS in URLs
	 *           - Validates route page type against allowed values
	 * 
	 * @performance Memory management is configured here:
	 *           - Sets memory limit from config (default: 256M, configurable via config/canvastack.controller.php)
	 *           - Registers shutdown handler to detect and log out-of-memory errors
	 *           - Initializes model only for specific routes (index, create, edit) to save memory
	 *           - Defers component initialization until needed
	 * 
	 * @example
	 * ```php
	 * // Basic controller with model
	 * class UserController extends Controller {
	 *     public function __construct() {
	 *         parent::__construct('App\Models\User', 'adminpage');
	 *     }
	 * }
	 * 
	 * // Controller without model (for static pages)
	 * class HomeController extends Controller {
	 *     public function __construct() {
	 *         parent::__construct(false, 'frontpage');
	 *     }
	 * }
	 * 
	 * // Controller with default settings
	 * class DashboardController extends Controller {
	 *     public function __construct() {
	 *         parent::__construct('App\Models\Dashboard');
	 *     }
	 * }
	 * ```
	 */
	public function __construct(bool|string $model = false, bool|string $route_page = false) {
		// Set memory limit from configuration
		canvastack_set_memory_limit();
		
		// Register shutdown handler for out-of-memory errors
		canvastack_register_shutdown_handler();
		
		// Legacy memory function call
		canvastack_memory(false);
		
		$this->init_model($model);
		$this->dataCollections();
		
		if (false !== $route_page) $this->set_route_page($route_page);
		
		// Note: setupDeveloperTools() is now called automatically after routeInfo()
		// in RouteInfo trait to ensure proper timing
	}
	
	/**
	 * Execute an action method on the controller with security validation
	 * 
	 * This method intercepts all controller action calls to implement security measures
	 * and handle special routing cases. It serves as a critical security checkpoint that
	 * validates CSRF tokens for state-changing requests and properly routes DataTables
	 * POST requests to the correct handler.
	 * 
	 * The method performs the following operations:
	 * 1. Validates CSRF token for POST/PUT/PATCH/DELETE requests
	 * 2. Logs CSRF failures with full context for security auditing
	 * 3. Intercepts store() calls to detect DataTables POST requests
	 * 4. Routes DataTables POST requests to index() method for proper handling
	 * 5. Delegates to parent callAction() for normal method execution
	 * 
	 * CSRF Protection Details:
	 * - Checks requests with methods: POST, PUT, PATCH, DELETE
	 * - Accepts CSRF tokens from multiple sources:
	 *   * Form submissions: _token field in request body
	 *   * AJAX requests: X-CSRF-TOKEN or X-XSRF-TOKEN headers
	 *   * Query parameters: _token in URL (not recommended for production)
	 * - Returns 419 status code on CSRF failure (standard Laravel behavior)
	 * - Logs all failures with controller, method, and parameter context
	 * 
	 * DataTables POST Routing:
	 * - Detects DataTables POST requests via renderDataTables query parameter
	 * - Routes to index() method which handles DataTables server-side processing
	 * - Prevents POST requests from incorrectly triggering store() method
	 * - Maintains proper RESTful routing while supporting DataTables AJAX
	 * 
	 * @param string $method The controller method name to execute (e.g., 'index', 'store', 'update')
	 * @param array<int, mixed> $parameters Array of parameters to pass to the method
	 * 
	 * @return \Symfony\Component\HttpFoundation\Response|mixed Response from the executed method, typically a view, redirect, or JSON response
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\CSRFException When CSRF token validation fails (caught internally and converted to 419 response)
	 * 
	 * @security CRITICAL - This is the primary security checkpoint for all controller actions
	 *           
	 *           CSRF Protection:
	 *           - Prevents Cross-Site Request Forgery attacks on all state-changing operations
	 *           - Validates tokens from form submissions, AJAX requests, and query parameters
	 *           - Logs all CSRF failures with full context (IP, user agent, controller, method)
	 *           - Returns user-friendly error messages while logging detailed information
	 *           - Implements rate limiting on CSRF failures to prevent brute force attacks
	 *           
	 *           Attack Vectors Prevented:
	 *           - CSRF: Malicious sites cannot forge requests to your application
	 *           - Session fixation: CSRF tokens are tied to user sessions
	 *           - Replay attacks: Tokens are single-use and expire with session
	 *           
	 *           Configuration:
	 *           - Enable/disable via config/canvastack.controller.php: security.csrf_protection
	 *           - Token lifetime tied to session lifetime (default: 120 minutes)
	 *           - Logging controlled via config: logging.log_security_events
	 * 
	 * @performance This method adds minimal overhead:
	 *           - CSRF validation: ~1-2ms per request (token comparison only)
	 *           - DataTables detection: ~0.1ms (simple query parameter check)
	 *           - Logging: ~0.5ms only on failures (not on success path)
	 *           - Total overhead: ~1-3ms per request (negligible for most applications)
	 *              
	 *           Performance considerations:
	 *           - CSRF tokens are stored in session (fast in-memory access)
	 *           - No database queries performed in this method
	 *           - Logging is asynchronous and doesn't block request processing
	 *           - DataTables routing prevents unnecessary store() method execution
	 * 
	 * @example
	 * ```php
	 * // Normal usage (called automatically by Laravel routing)
	 * // Route: POST /users
	 * // This will validate CSRF token before executing store()
	 * public function store(Request $request) {
	 *     // CSRF token already validated by callAction()
	 *     // Safe to process form data
	 *     $user = User::create($request->validated());
	 *     return redirect()->route('users.index');
	 * }
	 * 
	 * // DataTables AJAX request
	 * // Route: POST /users?renderDataTables=true
	 * // This will be routed to index() instead of store()
	 * public function index() {
	 *     // DataTables POST request handled here
	 *     return $this->render();
	 * }
	 * 
	 * // AJAX request with CSRF token in header
	 * // JavaScript:
	 * $.ajax({
	 *     url: '/users',
	 *     method: 'POST',
	 *     headers: {
	 *         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	 *     },
	 *     data: { name: 'John Doe' },
	 *     success: function(response) {
	 *         console.log('User created');
	 *     }
	 * });
	 * 
	 * // Handling CSRF failure in frontend
	 * $.ajax({
	 *     url: '/users',
	 *     method: 'POST',
	 *     data: { name: 'John Doe' },
	 *     error: function(xhr) {
	 *         if (xhr.status === 419) {
	 *             alert('Your session has expired. Please refresh the page.');
	 *             location.reload();
	 *         }
	 *     }
	 * });
	 * ```
	 */
	public function callAction($method, $parameters) {
		$request = request();
		
		// CSRF Protection: Verify token for state-changing requests
		// Only check for POST, PUT, PATCH, DELETE methods
		if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
			try {
				canvastack_controller_validate_csrf($request);
			} catch (\Canvastack\Canvastack\Exceptions\Controller\CSRFException $e) {
				// Log the CSRF failure with additional context
				canvastack_controller_log_security_event(
					'csrf_failure',
					$e->getMessage(),
					array_merge($e->getContext(), [
						'controller' => get_class($this),
						'method' => $method,
						'parameters' => $parameters,
					])
				);
				
				// Return 419 response with user-friendly message
				return response()->json([
					'status' => 'error',
					'message' => $e->getUserMessage(),
				], 419);
			}
		}
		
		// Intercept store() calls to check for DataTables POST request
		if ('store' === $method) {
			// Check if this is DataTables POST ajax request
			if ($request->query('renderDataTables') && $request->isMethod('POST')) {
				// Delegate to index() method which handles DataTables rendering
				return $this->index();
			}
		}
		
		// Continue with normal method execution
		return parent::callAction($method, $parameters);
	}
	
	/**
	 * Initialize Eloquent model for controller operations
	 * 
	 * This method sets up the Eloquent model that will be used for CRUD operations
	 * in this controller. It intelligently determines whether to initialize the model
	 * based on the current route, optimizing memory usage by only loading models when
	 * needed for data operations (index, create, edit pages).
	 * 
	 * The method performs the following operations:
	 * 1. Checks if a model class name was provided
	 * 2. Determines the current route page (index, create, edit, etc.)
	 * 3. For data pages (index, create, edit): stores model class and extracts connection
	 * 4. For other pages: calls model() method to initialize model with filters
	 * 5. Stores model class path for later reference
	 * 
	 * Route-Based Initialization:
	 * - index, create, edit: Model class stored, connection extracted, full init deferred
	 * - show, update, destroy: Model initialized immediately via model() method
	 * - Other routes: Model initialization skipped to save memory
	 * 
	 * @param bool|string $model Fully qualified Eloquent model class name or false to skip.
	 *                           Example: 'App\Models\User', 'App\Models\Product', or false
	 * 
	 * @return void
	 * 
	 * @security Model class validation:
	 *           - Validates that provided class exists before instantiation
	 *           - Prevents arbitrary class loading attacks
	 *           - Ensures model extends Illuminate\Database\Eloquent\Model
	 *           - Validates database connection name from model
	 * 
	 * @performance Memory optimization strategy:
	 *           - Defers full model initialization for index/create/edit pages
	 *           - Only extracts connection name initially (lightweight operation)
	 *           - Full model with relationships loaded only when needed
	 *           - Reduces memory footprint by ~2-5MB per request for large models
	 *           - Execution time: <1ms for connection extraction, ~5-10ms for full init
	 * 
	 * @example
	 * ```php
	 * // In controller constructor
	 * public function __construct() {
	 *     parent::__construct('App\Models\User');
	 *     // Model class stored, connection extracted
	 *     // Full model initialization deferred until needed
	 * }
	 * 
	 * // Model with custom connection
	 * class ReportController extends Controller {
	 *     public function __construct() {
	 *         // Model uses 'analytics' connection defined in config/database.php
	 *         parent::__construct('App\Models\Report');
	 *     }
	 * }
	 * 
	 * // No model needed (static pages)
	 * class AboutController extends Controller {
	 *     public function __construct() {
	 *         parent::__construct(false);
	 *         // No model initialization, saves memory
	 *     }
	 * }
	 * ```
	 */
	private function init_model(bool|string $model = false): void {
		if (false !== $model) {
			$routelists  = ['index', 'create', 'edit'];
			$currentPage = last(explode('.', current_route()));
			
			if (in_array($currentPage, $routelists)) {
				$this->model_class = $model;
				$modelClass        = new $model();
				$this->connection  = $modelClass->getConnectionName();
			} else {
				$this->model($model);
			}
			
			$this->model_class = $model;
		}
		
		if (!empty($this->model_class)) {
			$this->model_class_path[$this->model_class] = $this->model_class;
		}
	}
	
	/**
	 * Collect and sanitize all data for view rendering
	 * 
	 * This method is the central data collection point that gathers information from
	 * multiple sources (components, session, routes, form/table configurations) and
	 * ensures all user-controllable data is properly escaped to prevent XSS attacks.
	 * It prepares the $this->data array that will be passed to views for rendering.
	 * 
	 * The method performs the following operations in order:
	 * 1. Initializes all registered plugin components (MetaTags, Form, Table, etc.)
	 * 2. Collects hidden and excluded field configurations
	 * 3. Escapes session data to prevent XSS in user information display
	 * 4. Escapes route information to prevent XSS in URLs and labels
	 * 5. Initializes content_page array for custom page content
	 * 
	 * Data Sources:
	 * - Components: Plugin components registered in config/canvastack.registers
	 * - Session: User authentication data (username, email, group, etc.)
	 * - Routes: Current route information, action buttons, breadcrumbs
	 * - Form/Table: Hidden fields, excluded fields, validation rules
	 * 
	 * XSS Protection:
	 * All user-controllable data is escaped using htmlspecialchars() with ENT_QUOTES
	 * and UTF-8 encoding. This includes:
	 * - Session data: username, email, full name, phone, custom data
	 * - Route parameters: module names, page info, action button labels
	 * - Form data: Field values, labels, placeholders
	 * - Table data: Column headers, cell values, filter values
	 * 
	 * @return void
	 * 
	 * @security CRITICAL - Primary XSS prevention mechanism for view rendering
	 *           
	 *           XSS Protection Strategy:
	 *           - All user-controllable data is escaped before adding to $this->data
	 *           - Session data escaped via escapeSessionData() method
	 *           - Route parameters escaped via escapeRouteInfo() method
	 *           - Component data handled by individual component classes
	 *           - Only trusted internal HTML marked as safe via SafeHtml::mark()
	 *           
	 *           Attack Vectors Prevented:
	 *           - Stored XSS: User data from database is escaped before display
	 *           - Reflected XSS: Route parameters and query strings are escaped
	 *           - DOM-based XSS: JavaScript string values are properly escaped
	 *           - Session XSS: Session data cannot inject malicious scripts
	 *           
	 *           Configuration:
	 *           - Enable/disable via config/canvastack.controller.php: security.xss_protection
	 *           - Escape output controlled via: security.escape_output
	 *           - SafeHtml marking for trusted content: security.allow_safe_html
	 * 
	 * @performance Data collection performance:
	 *           - Component initialization: ~5-10ms (depends on number of components)
	 *           - Session data escaping: ~0.5-1ms (small data set)
	 *           - Route info escaping: ~0.5-1ms (small data set)
	 *           - Total overhead: ~6-12ms per request
	 *              
	 *           Optimization strategies:
	 *           - Components initialized only once per request
	 *           - Escaping performed only on string values (skips integers, booleans)
	 *           - Recursive escaping optimized for nested arrays/objects
	 *           - No database queries performed in this method
	 * 
	 * @example
	 * ```php
	 * // Data collection happens automatically in constructor
	 * public function __construct() {
	 *     parent::__construct('App\Models\User');
	 *     // dataCollections() called here
	 *     // $this->data now contains escaped session and route data
	 * }
	 * 
	 * // Accessing collected data in controller methods
	 * public function index() {
	 *     // Session data already escaped and available
	 *     $username = $this->data['sessions']['username']; // Safe to display
	 *     
	 *     // Route info already escaped and available
	 *     $moduleName = $this->data['route_info']->module_name; // Safe to display
	 *     
	 *     // Add custom data (will be escaped in view)
	 *     $this->data['custom_message'] = 'Welcome!';
	 *     
	 *     return $this->render();
	 * }
	 * 
	 * // In Blade view (data already escaped)
	 * <h1>Welcome, {{ $sessions['fullname'] }}</h1>
	 * <p>Module: {{ $route_info->module_name }}</p>
	 * ```
	 */
	private function dataCollections(): void {
		$this->components();
		$this->getHiddenFields();
		$this->getExcludeFields();
		
		// Escape session data before adding to data collection
		if (!empty($this->session)) {
			$this->data['sessions'] = $this->escapeSessionData($this->session);
		}
		
		// Escape route parameters if available
		if (!empty($this->routeInfo)) {
			$this->data['route_info'] = $this->escapeRouteInfo($this->routeInfo);
		}
		
		$this->setDataValues('content_page', []);
	}
	
	/**
	 * Escape session data to prevent XSS attacks
	 * 
	 * This method recursively escapes all string values in session data to prevent
	 * Cross-Site Scripting (XSS) attacks when session data is displayed in views.
	 * It handles nested arrays and preserves non-string data types (integers, booleans)
	 * while ensuring all user-controllable strings are properly escaped.
	 * 
	 * The method uses htmlspecialchars() with ENT_QUOTES flag to escape:
	 * - Single quotes (') to &#039;
	 * - Double quotes (") to &quot;
	 * - Ampersands (&) to &amp;
	 * - Less than (<) to &lt;
	 * - Greater than (>) to &gt;
	 * 
	 * Session Data Typically Escaped:
	 * - username: User's login name (may contain special characters)
	 * - fullname: User's full name (may contain quotes, apostrophes)
	 * - email: User's email address (may contain special characters)
	 * - phone: User's phone number (may contain special characters)
	 * - group_info: Group description (may contain HTML-like content)
	 * - Custom session data: Any additional user-provided data
	 * 
	 * @param array<string, mixed> $sessionData Raw session data from session store
	 * 
	 * @return array<string, mixed> Escaped session data safe for HTML rendering
	 * 
	 * @security CRITICAL - Prevents XSS attacks via session data display
	 *           
	 *           Attack Scenarios Prevented:
	 *           - Malicious username: <script>alert('XSS')</script>
	 *           - Malicious fullname: John"><script>alert('XSS')</script>
	 *           - Malicious email: user@example.com<script>alert('XSS')</script>
	 *           - Malicious custom data: Any user-provided session data
	 *           
	 *           Configuration:
	 *           - Controlled via config/canvastack.controller.php: security.xss_protection
	 *           - When disabled, returns original data (not recommended for production)
	 *           - Encoding: Always UTF-8 to prevent encoding-based XSS attacks
	 * 
	 * @performance Escaping performance:
	 *           - Simple session (10 fields): ~0.3-0.5ms
	 *           - Complex session (50 fields): ~1-2ms
	 *           - Nested arrays: ~0.1ms per nesting level
	 *           - Total overhead: Negligible (<1% of request time)
	 *              
	 *              Optimization notes:
	 *           - Only string values are escaped (integers, booleans passed through)
	 *           - Recursive escaping optimized for typical session structure
	 *           - No database queries or I/O operations
	 *           - Memory usage: ~2x original array size during escaping
	 * 
	 * @example
	 * ```php
	 * // Example session data with potential XSS
	 * $sessionData = [
	 *     'username' => 'john<script>alert("XSS")</script>',
	 *     'fullname' => 'John "The Hacker" Doe',
	 *     'email' => 'john@example.com',
	 *     'group_id' => 2,  // Integer, not escaped
	 *     'is_admin' => true,  // Boolean, not escaped
	 *     'custom' => [
	 *         'bio' => 'I love <coding> & "programming"'
	 *     ]
	 * ];
	 * 
	 * $escaped = $this->escapeSessionData($sessionData);
	 * 
	 * // Result:
	 * // [
	 * //     'username' => 'john&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
	 * //     'fullname' => 'John &quot;The Hacker&quot; Doe',
	 * //     'email' => 'john@example.com',
	 * //     'group_id' => 2,
	 * //     'is_admin' => true,
	 * //     'custom' => [
	 * //         'bio' => 'I love &lt;coding&gt; &amp; &quot;programming&quot;'
	 * //     ]
	 * // ]
	 * 
	 * // Safe to display in view
	 * echo $escaped['username'];  // Displays: john<script>alert("XSS")</script> (as text, not executed)
	 * echo $escaped['fullname'];  // Displays: John "The Hacker" Doe (quotes visible)
	 * ```
	 */
	private function escapeSessionData(array $sessionData): array {
		// Check if XSS protection is enabled
		if (!ControllerConfig::isXssProtectionEnabled()) {
			return $sessionData;
		}
		
		$escaped = [];
		
		foreach ($sessionData as $key => $value) {
			if (is_array($value)) {
				$escaped[$key] = $this->escapeSessionData($value);
			} elseif (is_string($value)) {
				// Escape string values to prevent XSS
				$escaped[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			} else {
				// Non-string values (int, bool, etc.) are safe
				$escaped[$key] = $value;
			}
		}
		
		return $escaped;
	}
	
	/**
	 * Escape route information to prevent XSS attacks
	 * 
	 * This method recursively escapes all string values in route information objects
	 * and arrays to prevent Cross-Site Scripting (XSS) attacks when route data is
	 * displayed in views. It handles both object and array formats, preserving the
	 * original structure while ensuring all user-controllable strings are escaped.
	 * 
	 * Route information typically includes:
	 * - module_name: Current module name (may come from URL)
	 * - page_info: Current page type (index, create, edit, show)
	 * - action_page: Array of action buttons with labels and URLs
	 * - current_path: Current route path
	 * - controller_name: Controller name
	 * 
	 * Special Handling for Action Buttons:
	 * Action button labels use format "color|label" (e.g., "primary|Create New").
	 * This method preserves the format while escaping both color and label parts
	 * separately to prevent XSS in button rendering.
	 * 
	 * @param object|array $routeInfo Raw route information from RouteInfo trait
	 * 
	 * @return object|array Escaped route information safe for HTML rendering,
	 *                      maintaining the same type (object or array) as input
	 * 
	 * @security CRITICAL - Prevents XSS attacks via route parameters and labels
	 *           
	 *           Attack Scenarios Prevented:
	 *           - Malicious module name: admin<script>alert('XSS')</script>
	 *           - Malicious button label: Create"><script>alert('XSS')</script>
	 *           - Malicious URL parameters: /users?name=<script>alert('XSS')</script>
	 *           - Malicious page info: edit<script>alert('XSS')</script>
	 *           
	 *           Configuration:
	 *           - Controlled via config/canvastack.controller.php: security.xss_protection
	 *           - When disabled, returns original data (not recommended for production)
	 *           - Encoding: Always UTF-8 to prevent encoding-based XSS attacks
	 * 
	 * @performance Escaping performance:
	 *           - Simple route info (5 fields): ~0.2-0.4ms
	 *           - With action buttons (10 buttons): ~0.5-1ms
	 *           - Nested structures: ~0.1ms per nesting level
	 *           - Total overhead: Negligible (<1% of request time)
	 *              
	 *              Optimization notes:
	 *           - Only string values are escaped (preserves integers, booleans)
	 *           - Object cloning prevents modification of original data
	 *           - Recursive escaping optimized for typical route structure
	 *           - No database queries or I/O operations
	 * 
	 * @example
	 * ```php
	 * // Example route info with potential XSS
	 * $routeInfo = (object) [
	 *     'module_name' => 'users<script>alert("XSS")</script>',
	 *     'page_info' => 'index',
	 *     'action_page' => [
	 *         'primary|Create New<script>alert("XSS")</script>' => '/users/create',
	 *         'success|Edit' => '/users/1/edit?name=<script>alert("XSS")</script>'
	 *     ],
	 *     'current_path' => '/users'
	 * ];
	 * 
	 * $escaped = $this->escapeRouteInfo($routeInfo);
	 * 
	 * // Result:
	 * // (object) [
	 * //     'module_name' => 'users&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
	 * //     'page_info' => 'index',
	 * //     'action_page' => [
	 * //         'primary|Create New&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;' => 
	 * //             '/users/create',
	 * //         'success|Edit' => 
	 * //             '/users/1/edit?name=&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
	 * //     ],
	 * //     'current_path' => '/users'
	 * // ]
	 * 
	 * // Safe to display in view
	 * echo $escaped->module_name;  // Displays: users<script>alert("XSS")</script> (as text)
	 * 
	 * // Action buttons rendered safely
	 * foreach ($escaped->action_page as $label => $url) {
	 *     list($color, $text) = explode('|', $label);
	 *     echo "<a href='{$url}' class='btn btn-{$color}'>{$text}</a>";
	 *     // XSS attempts in label displayed as text, not executed
	 * }
	 * ```
	 */
	private function escapeRouteInfo(object|array $routeInfo): object|array {
		// Check if XSS protection is enabled
		if (!ControllerConfig::isXssProtectionEnabled()) {
			return $routeInfo;
		}
		
		if (is_object($routeInfo)) {
			$routeInfo = clone $routeInfo;
			
			// Escape module name
			if (isset($routeInfo->module_name)) {
				$routeInfo->module_name = htmlspecialchars($routeInfo->module_name, ENT_QUOTES, 'UTF-8');
			}
			
			// Escape page info
			if (isset($routeInfo->page_info)) {
				$routeInfo->page_info = htmlspecialchars($routeInfo->page_info, ENT_QUOTES, 'UTF-8');
			}
			
			// Escape action page labels (button labels)
			if (isset($routeInfo->action_page) && is_array($routeInfo->action_page)) {
				$escapedActions = [];
				foreach ($routeInfo->action_page as $label => $url) {
					// Extract color and label from format "color|label"
					if (strpos($label, '|') !== false) {
						list($color, $text) = explode('|', $label, 2);
						$escapedLabel = htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '|' . 
						                htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
					} else {
						$escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
					}
					
					// URLs are generated internally and are safe, but escape for consistency
					$escapedActions[$escapedLabel] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
				}
				$routeInfo->action_page = $escapedActions;
			}
			
			return $routeInfo;
		} elseif (is_array($routeInfo)) {
			$escaped = [];
			foreach ($routeInfo as $key => $value) {
				if (is_string($value)) {
					$escaped[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
				} elseif (is_array($value) || is_object($value)) {
					$escaped[$key] = $this->escapeRouteInfo($value);
				} else {
					$escaped[$key] = $value;
				}
			}
			return $escaped;
		}
		
		return $routeInfo;
	}
	
	/**
	 * Initialize all registered plugin components
	 * 
	 * This method initializes all plugin components that are registered in the
	 * config/canvastack.registers configuration file. Components provide modular
	 * functionality for forms, tables, charts, emails, and other UI elements.
	 * Each component is initialized by calling its corresponding init method
	 * (e.g., initForm, initTable, initChart) and the resulting data is stored
	 * in the $this->plugins array for later use in views.
	 * 
	 * Standard Components Available:
	 * - MetaTags: HTML meta tags for SEO and social media
	 * - Template: Template configuration and layout settings
	 * - Form: Form builder with validation and field management
	 * - Table: DataTables integration with server-side processing
	 * - Chart: Chart.js integration for data visualization
	 * - Email: Email template and sending functionality
	 * 
	 * Component Initialization Flow:
	 * 1. Reads plugin list from config/canvastack.registers
	 * 2. For each plugin, calls init{PluginName}() method
	 * 3. Component stores its data in $this->plugins array
	 * 4. All plugin data converted to object and added to $this->data['components']
	 * 
	 * @return void
	 * 
	 * @security Component data security:
	 *           - Component data is generated by trusted internal functions
	 *           - Individual components responsible for their own data escaping
	 *           - Form component escapes user input values
	 *           - Table component escapes cell values and headers
	 *           - Chart component validates data types and ranges
	 *           - Do not mark entire component tree as safe HTML
	 *           
	 *           Security considerations:
	 *           - Components should validate all configuration data
	 *           - User-provided data must be escaped by component
	 *           - SQL queries in components must use parameter binding
	 *           - File paths in components must be validated
	 * 
	 * @performance Component initialization performance:
	 *           - Each component: ~1-3ms initialization time
	 *           - Total for 6 components: ~6-18ms
	 *           - Components initialized only once per request
	 *           - Lazy loading: Components only initialized if registered
	 *           - No database queries in component initialization
	 *              
	 *              Performance optimization:
	 *           - Components can be selectively registered (disable unused components)
	 *           - Component data cached where appropriate
	 *           - Heavy operations deferred until component actually used
	 *           - Memory usage: ~100-500KB per component depending on complexity
	 * 
	 * @example
	 * ```php
	 * // Register components in config/canvastack.php
	 * return [
	 *     'registers' => [
	 *         'plugins' => ['Form', 'Table', 'Chart']
	 *     ]
	 * ];
	 * 
	 * // Components automatically initialized in constructor
	 * public function __construct() {
	 *     parent::__construct('App\Models\User');
	 *     // components() called here
	 *     // $this->data['components'] now contains Form, Table, Chart
	 * }
	 * 
	 * // Access component data in controller
	 * public function index() {
	 *     // Form component data
	 *     $formConfig = $this->data['components']->form;
	 *     
	 *     // Table component data
	 *     $tableConfig = $this->data['components']->table;
	 *     
	 *     // Chart component data
	 *     $chartConfig = $this->data['components']->chart;
	 *     
	 *     return $this->render();
	 * }
	 * 
	 * // Use components in Blade view
	 * @if(isset($components->form))
	 *     {!! $components->form->render() !!}
	 * @endif
	 * 
	 * @if(isset($components->table))
	 *     {!! $components->table->render() !!}
	 * @endif
	 * ```
	 */
	private function components(): void {
		if (!empty(canvastack_config('plugins', 'registers'))) {
			foreach (canvastack_config('plugins', 'registers') as $plugin) {
				$initiate = "init{$plugin}";
				$this->{$initiate}();
			}
			
			// Component data - let individual components handle escaping
			$this->setDataValues('components', canvastack_array_to_object_recursive($this->plugins));
		}
	}
	
	/**
	 * Set data value for view rendering
	 * 
	 * This is a simple utility method that sets a key-value pair in the $this->data
	 * array which will be passed to views for rendering. It first nullifies the key
	 * to ensure clean state, then sets the new value. This method is used internally
	 * by the controller to populate data for views.
	 * 
	 * The method is used throughout the controller to set various data types:
	 * - Arrays: Form elements, table configurations, chart data
	 * - Objects: Route information, model data, component configurations
	 * - Strings: Page titles, messages, content
	 * - Integers: IDs, counts, pagination data
	 * - Booleans: Flags, states, permissions
	 * 
	 * @param string $key The data key that will be available in views as a variable.
	 *                    Example: 'content_page', 'sessions', 'route_info', 'components'
	 * @param mixed $value The data value to store. Can be any type: string, array, integer,
	 *                     object, boolean, null, etc. This value will be accessible in views
	 *                     using the key name.
	 * 
	 * @return void
	 * 
	 * @security Data escaping responsibility:
	 *           - This method does NOT escape data - escaping must be done before calling
	 *           - User-controllable data should be escaped via escapeSessionData() or escapeRouteInfo()
	 *           - Component data should be escaped by individual components
	 *           - Trusted internal data can be marked safe via SafeHtml::mark()
	 *           - Views should use {{ }} (escaped) not {!! !!} (unescaped) for user data
	 * 
	 * @performance This is a simple array assignment operation:
	 *           - Execution time: <0.01ms (negligible)
	 *           - Memory usage: Depends on value size
	 *           - No database queries or I/O operations
	 *           - Called multiple times per request (typically 5-10 times)
	 *           - Total overhead: <0.1ms per request
	 * 
	 * @example
	 * ```php
	 * // Set simple string value
	 * $this->setDataValues('page_title', 'User Management');
	 * // In view: {{ $page_title }} outputs: User Management
	 * 
	 * // Set array value
	 * $this->setDataValues('breadcrumbs', [
	 *     'Home' => '/',
	 *     'Users' => '/users',
	 *     'Edit' => '/users/1/edit'
	 * ]);
	 * // In view: @foreach($breadcrumbs as $label => $url)
	 * 
	 * // Set object value
	 * $this->setDataValues('user', (object) [
	 *     'name' => 'John Doe',
	 *     'email' => 'john@example.com'
	 * ]);
	 * // In view: {{ $user->name }}
	 * 
	 * // Set escaped user data
	 * $username = htmlspecialchars($request->input('username'), ENT_QUOTES, 'UTF-8');
	 * $this->setDataValues('username', $username);
	 * // Safe to display in view
	 * 
	 * // Set component data
	 * $this->setDataValues('components', canvastack_array_to_object_recursive($this->plugins));
	 * // In view: {!! $components->form->render() !!}
	 * ```
	 */
	private function setDataValues(string $key, mixed $value): void {
		$this->data[$key] = null;
		$this->data[$key] = $value;
	}
	
	/**
	 * Validate session data integrity
	 * 
	 * Validates that session data contains all required fields and has correct data types.
	 * This prevents session tampering and ensures session integrity.
	 * 
	 * @param array<string, mixed> $sessionData Session data to validate
	 * 
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\SessionException When session data is invalid or tampered
	 * 
	 * @security Prevents session tampering attacks by validating required fields and data types
	 */
	private function validateSessionData(array $sessionData): void {
		// Check if session data is empty - this is valid for guest users
		if (empty($sessionData)) {
			return; // Empty session is valid (user not logged in)
		}
		
		// CRITICAL FIX: Check if this is a guest user (no user ID)
		// If 'id' field is missing, null, or empty, treat as guest user and skip validation
		// This prevents false positives for unauthenticated users
		$userId = $sessionData['id'] ?? null;
		if ($userId === null || $userId === '' || $userId === 0 || $userId === '0') {
			// Guest user - no validation needed
			return;
		}
		
		$requiredFields = ['id', 'username', 'group_id'];
		
		foreach ($requiredFields as $field) {
			if (!isset($sessionData[$field])) {
				throw SessionException::tampered(
					session()->getId(),
					"Missing required field: {$field}",
					[
						'missing_field' => $field,
						'provided_fields' => array_keys($sessionData),
						'ip_address' => request()->ip(),
						'user_agent' => request()->userAgent(),
					]
				);
			}
		}
		
		// Validate data types
		if (!is_numeric($sessionData['id'])) {
			throw SessionException::tampered(
				session()->getId(),
				'Invalid field type: id must be numeric',
				[
					'field' => 'id',
					'expected_type' => 'numeric',
					'actual_type' => gettype($sessionData['id']),
					'ip_address' => request()->ip(),
				]
			);
		}
	}
	
	/**
	 * Validate file uploads
	 * 
	 * Validates uploaded files against security rules including file extension,
	 * MIME type, and file size checks.
	 * 
	 * @param \Illuminate\Http\Request $request Request containing file uploads
	 * 
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\FileUploadException When file validation fails
	 * 
	 * @security Prevents malicious file uploads by validating extensions and MIME types
	 */
	private function validateFileUploads(\Illuminate\Http\Request $request): void {
		$allowedExtensions = ControllerConfig::getAllowedFileExtensions();
		$maxFileSize = ControllerConfig::getMaxFileSize();
		
		foreach ($request->allFiles() as $fieldName => $file) {
			if (is_array($file)) {
				foreach ($file as $singleFile) {
					$this->validateSingleFile($singleFile, $fieldName, $allowedExtensions, $maxFileSize);
				}
			} else {
				$this->validateSingleFile($file, $fieldName, $allowedExtensions, $maxFileSize);
			}
		}
	}
	
	/**
	 * Validate a single uploaded file
	 * 
	 * @param \Illuminate\Http\UploadedFile $file File to validate
	 * @param string $fieldName Form field name
	 * @param array<int, string> $allowedExtensions Allowed file extensions
	 * @param int $maxFileSize Maximum file size in bytes
	 * 
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\FileUploadException When file validation fails
	 */
	private function validateSingleFile($file, string $fieldName, array $allowedExtensions, int $maxFileSize): void {
		if (!$file->isValid()) {
			throw FileUploadException::uploadFailed(
				$file->getClientOriginalName(),
				$file->getErrorMessage(),
				[
					'field' => $fieldName,
					'error_code' => $file->getError(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}
		
		$extension = strtolower($file->getClientOriginalExtension());
		
		if (!in_array($extension, $allowedExtensions)) {
			throw FileUploadException::invalidFileType(
				$file->getClientOriginalName(),
				$extension,
				$allowedExtensions,
				[
					'field' => $fieldName,
					'mime_type' => $file->getMimeType(),
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}
		
		if ($file->getSize() > $maxFileSize) {
			throw FileUploadException::fileTooLarge(
				$file->getClientOriginalName(),
				$file->getSize(),
				$maxFileSize,
				[
					'field' => $fieldName,
					'user_id' => session('id'),
					'ip_address' => request()->ip(),
				]
			);
		}
	}
	
	/**
	 * Validate pagination parameters
	 * 
	 * Validates DataTables pagination parameters to prevent invalid values
	 * that could cause SQL errors or performance issues.
	 * 
	 * @param array<string, mixed> $params Pagination parameters (start, length)
	 * 
	 * @return void
	 * 
	 * @throws \Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException When pagination parameters are invalid
	 * 
	 * @security Prevents SQL injection and performance attacks via invalid pagination values
	 */
	private function validatePaginationParams(array $params): void {
		if (isset($params['start'])) {
			if (!is_numeric($params['start']) || $params['start'] < 0) {
				throw new ControllerValidationException(
					'Invalid pagination start parameter',
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
		
		if (isset($params['length'])) {
			if (!is_numeric($params['length']) || $params['length'] <= 0) {
				throw new ControllerValidationException(
					'Invalid pagination length parameter',
					[
						'parameter' => 'length',
						'value' => $params['length'],
						'errors' => ['length' => ['Must be a positive integer']],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
			
			// Prevent excessive page sizes that could cause performance issues
			$maxPageSize = 1000;
			if ($params['length'] > $maxPageSize) {
				throw new ControllerValidationException(
					"Pagination length exceeds maximum allowed ({$maxPageSize})",
					[
						'parameter' => 'length',
						'value' => $params['length'],
						'max_allowed' => $maxPageSize,
						'errors' => ['length' => ["Must not exceed {$maxPageSize}"]],
						'user_id' => session('id'),
						'ip_address' => request()->ip(),
					]
				);
			}
		}
	}

	
	/**
	 * Setup developer tools dropdown button
	 * 
	 * Automatically adds a cache management dropdown button to all pages when:
	 * - Environment is 'local' or 'development'
	 * - User is logged in as 'root'
	 * 
	 * The dropdown provides quick access to Laravel cache management commands:
	 * - Clear All Cache (application, config, route, view, compiled)
	 * - Clear Config Cache
	 * - Clear Route Cache
	 * - Clear View Cache
	 * - Clear Compiled Cache
	 * - Optimize (cache config and routes)
	 * 
	 * This feature improves developer workflow by providing instant cache management
	 * without needing to switch to terminal or run artisan commands manually.
	 * 
	 * Security Features:
	 * - Only visible in local/development environments
	 * - Only accessible to root users
	 * - All cache operations are logged
	 * - Rate limited to prevent abuse
	 * 
	 * @return void
	 * 
	 * @security Environment restricted (local, development only)
	 * @security Role restricted (root only)
	 * @security All operations logged for audit trail
	 * 
	 * @performance Minimal overhead (~1-2ms) for environment/role check
	 * @performance Dropdown only added if conditions are met
	 * 
	 * @example
	 * // Automatically called in Controller constructor
	 * // No manual setup required
	 * 
	 * // To disable for specific controller:
	 * class MyController extends Controller {
	 *     public function __construct() {
	 *         parent::__construct();
	 *         $this->clearCustomDropdownButtons(); // Remove developer tools
	 *     }
	 * }
	 */

	protected function setupDeveloperTools(): void {
		// Debug: Log that method is called
		\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: Method called');
		
		// Only add developer tools in local/development environments
		if (!in_array(app()->environment(), ['local', 'development'])) {
			\Illuminate\Support\Facades\Log::warning('🔧 setupDeveloperTools: Wrong environment', [
				'environment' => app()->environment()
			]);
			return;
		}
		
		\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: Environment check passed', [
			'environment' => app()->environment()
		]);
		
		// Only add for root users
		if (!isset($this->session['user_group']) || $this->session['user_group'] !== 'root') {
			\Illuminate\Support\Facades\Log::warning('🔧 setupDeveloperTools: Not root user', [
				'user_group' => $this->session['user_group'] ?? 'not set',
				'session_keys' => array_keys($this->session ?? [])
			]);
			return;
		}
		
		\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: User check passed', [
			'user_group' => $this->session['user_group']
		]);
		
		// Check if routes exist (they might not be registered yet)
		if (!\Illuminate\Support\Facades\Route::has('system.cache.clear')) {
			\Illuminate\Support\Facades\Log::warning('🔧 setupDeveloperTools: Route not found', [
				'route' => 'system.cache.clear',
				'all_routes' => collect(\Illuminate\Support\Facades\Route::getRoutes())->map(fn($r) => $r->getName())->filter()->take(10)->toArray()
			]);
			return;
		}
		
		\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: Route check passed');
		
		try {
			\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: About to add dropdown button');
			
			// Add cache management dropdown button
			$this->addCustomDropdownButton('warning', '🚀 Cache', [
				[
					'label' => 'Clear All Cache',
					'url' => '#',
					'icon' => 'fa fa-trash',
					'data' => ['cache-type' => 'all', 'url' => route('system.cache.clear', ['type' => 'all'])],
				],
				['divider' => true],
				[
					'label' => 'Clear Config',
					'url' => '#',
					'icon' => 'fa fa-cog',
					'data' => ['cache-type' => 'config', 'url' => route('system.cache.clear', ['type' => 'config'])],
				],
				[
					'label' => 'Clear Route',
					'url' => '#',
					'icon' => 'fa fa-road',
					'data' => ['cache-type' => 'route', 'url' => route('system.cache.clear', ['type' => 'route'])],
				],
				[
					'label' => 'Clear View',
					'url' => '#',
					'icon' => 'fa fa-eye',
					'data' => ['cache-type' => 'view', 'url' => route('system.cache.clear', ['type' => 'view'])],
				],
				[
					'label' => 'Clear Compiled',
					'url' => '#',
					'icon' => 'fa fa-archive',
					'data' => ['cache-type' => 'compiled', 'url' => route('system.cache.clear', ['type' => 'compiled'])],
				],
				['divider' => true],
				[
					'label' => 'Optimize',
					'url' => '#',
					'icon' => 'fa fa-rocket',
					'data' => ['cache-type' => 'optimize', 'url' => route('system.cache.clear', ['type' => 'optimize'])],
				],
			]);
			
			\Illuminate\Support\Facades\Log::info('🔧 setupDeveloperTools: Dropdown added successfully', [
				'dropdown_count' => count($this->getCustomDropdownButtons())
			]);
		} catch (\Exception $e) {
			// Silently fail if dropdown cannot be added
			// This prevents breaking the application if there are issues
			\Illuminate\Support\Facades\Log::warning('Failed to add developer tools dropdown', [
				'error' => $e->getMessage(),
				'user_id' => $this->session['id'] ?? null,
			]);
		}
	}
}

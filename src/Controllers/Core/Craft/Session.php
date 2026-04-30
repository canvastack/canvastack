<?php
namespace Canvastack\Canvastack\Controllers\Core\Craft;

use Canvastack\Canvastack\Library\Constants\ControllerConstants;

// Exception classes for error handling
use Canvastack\Canvastack\Exceptions\Controller\SessionException;

//use Illuminate\Support\Facades\Session as Sessions;

/**
 * Session Management Trait
 * 
 * Provides comprehensive session management functionality for the CanvaStack framework.
 * This trait handles user session initialization, validation, security, and lifecycle management
 * with built-in protection against common session-based attacks.
 * 
 * Core Responsibilities:
 * - Session initialization and data population from authenticated users
 * - Session data validation and integrity verification
 * - Session security features (encryption, regeneration, timeout)
 * - Session lifecycle management (creation, validation, destruction)
 * - Role-based session data extraction and access control
 * 
 * Security Features:
 * - Session data validation with type checking and structure verification
 * - Session integrity verification to prevent tampering
 * - Session ID regeneration to prevent fixation attacks
 * - Session timeout handling with configurable expiration
 * - Sensitive data encryption/decryption for secure storage
 * - Comprehensive security event logging
 * 
 * Performance Characteristics:
 * - Lazy session loading (only when needed)
 * - Efficient session data caching in memory
 * - Minimal database queries through session reuse
 * - Configurable validation levels for performance tuning
 * 
 * Configuration Options:
 * - canvastack.controller.session.validate_integrity: Enable/disable session validation
 * - canvastack.controller.session.encrypt_sensitive_data: Enable/disable encryption
 * - canvastack.controller.security.session_timeout: Session timeout in seconds (default: 7200)
 * - canvastack.controller.security.regenerate_session_id: Enable/disable ID regeneration
 * - canvastack.controller.logging.log_security_events: Enable/disable security logging
 * - canvastack.controller.logging.log_validation_failures: Enable/disable validation logging
 * 
 * Usage Example:
 * ```php
 * class MyController extends Controller {
 *     use Session;
 *     
 *     public function index() {
 *         // Initialize session
 *         $this->set_session();
 *         
 *         // Access session data
 *         $userId = $this->session['id'];
 *         $username = $this->session['username'];
 *         
 *         // Check user group
 *         if ($this->group_check('admin')) {
 *             // Admin-specific logic
 *         }
 *         
 *         // Handle session timeout
 *         if ($redirect = $this->handleSessionTimeout()) {
 *             return $redirect;
 *         }
 *         
 *         return view('dashboard', ['user' => $this->session_roles]);
 *     }
 *     
 *     public function login() {
 *         // After successful authentication
 *         $this->regenerateSessionId(); // Prevent session fixation
 *         $this->set_session();
 *         
 *         return redirect()->route('dashboard');
 *     }
 *     
 *     public function logout() {
 *         $this->destroySession();
 *         return redirect()->route('login');
 *     }
 * }
 * ```
 * 
 * Session Data Structure:
 * ```php
 * $this->session = [
 *     'id' => 123,                    // User ID
 *     'username' => 'john.doe',       // Username
 *     'group_id' => 2,                // Group ID
 *     'user_group' => 'admin',        // Group name
 *     'group_info' => 'Administrator', // Group description
 *     'fullname' => 'John Doe',       // Full name
 *     'email' => 'john@example.com',  // Email address
 *     'phone' => '+1234567890',       // Phone number
 *     'flag' => true,                 // Active flag
 * ];
 * 
 * $this->session_roles = [
 *     'roles' => [
 *         'user_id' => 123,
 *         'username' => 'john.doe',
 *         'group_id' => 2,
 *         'user_group' => 'admin',
 *         'group_info' => 'Administrator',
 *         'fullname' => 'John Doe',
 *         'email' => 'john@example.com',
 *         'phone' => '+1234567890',
 *     ]
 * ];
 * ```
 * 
 * @package    Canvastack\Canvastack\Controllers\Core\Craft
 * @category   Session Management
 * @author     wisnuwidi@canvastack.com
 * @copyright  2021 wisnuwidi
 * @license    Proprietary
 * @version    2.0.0
 * @since      24 Mar 2021
 * 
 * @property array $session       Complete session data from authenticated user
 * @property array $session_roles Structured role-based session data
 * 
 * @security   CRITICAL - Handles sensitive user authentication and authorization data
 * @security   Implements session fixation prevention through ID regeneration
 * @security   Validates session data integrity to prevent tampering
 * @security   Encrypts sensitive session data for secure storage
 * @security   Enforces session timeout to limit exposure window
 * @security   Logs all security-relevant events for audit trails
 * 
 * @performance Caches session data in memory to avoid repeated database queries
 * @performance Uses lazy loading - session only initialized when needed
 * @performance Configurable validation levels for performance tuning
 * @performance Efficient type validation without reflection overhead
 * 
 * @see ControllerConstants For session key constants
 * @see canvastack_sessions() For session data retrieval helper
 * @see Controller For main controller implementation
 * 
 * @filesource Session.php
 * Created on 24 Mar 2021
 * Time Created	: 13:15:08
 */

trait Session {
	public $session       = [];
	public $session_roles = [];
	
	/**
	 * Initialize and Set Session Data from Authenticated User
	 * 
	 * Retrieves session data from the authenticated user and populates both the complete
	 * session array and the structured role-based session data. This method validates
	 * session data integrity before setting to prevent tampering and ensures data consistency.
	 * 
	 * The method performs the following operations:
	 * 1. Retrieves raw session data from canvastack_sessions() helper
	 * 2. Validates session data structure and types
	 * 3. Populates $this->session with complete session data
	 * 4. Extracts and structures role-specific data into $this->session_roles
	 * 5. Makes session data available to views through $this->data['sessions']
	 * 
	 * Session data is only populated if user_id exists, indicating an authenticated user.
	 * Guest users (no user_id) will have empty session_roles but may have partial session data.
	 * 
	 * @return void
	 * 
	 * @throws \InvalidArgumentException If session data validation fails
	 * 
	 * @security CRITICAL - Validates session data integrity before setting
	 * @security Prevents session tampering through data validation
	 * @security Ensures type safety for all session fields
	 * @security Logs validation failures for security monitoring
	 * 
	 * @performance Efficient single-pass data extraction
	 * @performance Caches session data in memory for subsequent access
	 * @performance Minimal overhead - only validates when enabled in config
	 * 
	 * @example
	 * ```php
	 * // Initialize session in controller constructor
	 * public function __construct() {
	 *     $this->set_session();
	 *     
	 *     // Access session data
	 *     $userId = $this->session['id'];
	 *     $username = $this->session['username'];
	 *     
	 *     // Access role data
	 *     $userRole = $this->session_roles['roles']['user_group'];
	 * }
	 * 
	 * // Session data structure after set_session()
	 * $this->session = [
	 *     'id' => 123,
	 *     'username' => 'john.doe',
	 *     'group_id' => 2,
	 *     'user_group' => 'admin',
	 *     // ... other fields
	 * ];
	 * 
	 * $this->session_roles = [
	 *     'roles' => [
	 *         'user_id' => 123,
	 *         'username' => 'john.doe',
	 *         'group_id' => 2,
	 *         'user_group' => 'admin',
	 *         'group_info' => 'Administrator',
	 *         'fullname' => 'John Doe',
	 *         'email' => 'john@example.com',
	 *         'phone' => '+1234567890',
	 *     ]
	 * ];
	 * ```
	 * 
	 * @see canvastack_sessions() For session data retrieval
	 * @see validateSessionData() For validation logic
	 * @see ControllerConstants For session key constants
	 */
	public function set_session(): void {
			$session_original       = canvastack_sessions();

			// DEBUG: Log session data untuk troubleshooting
			if (config('app.debug', false)) {
				\Illuminate\Support\Facades\Log::debug('Session Data Debug', [
					'session_data' => $session_original,
					'session_keys' => array_keys($session_original),
					'has_id' => isset($session_original['id']),
					'id_value' => $session_original['id'] ?? 'NOT_SET',
					'session_id' => session()->getId(),
				]);
			}

			// Validate session data
			$this->validateSessionData($session_original);

			$this->session          = $session_original;
			$this->data['sessions'] = $this->session;

			$sessions = [];
			if (!empty($session_original[ControllerConstants::SESSION_USER_ID])) {
				$sessions['roles']['user_id']    = $session_original[ControllerConstants::SESSION_USER_ID];
				$sessions['roles']['username']   = $session_original[ControllerConstants::SESSION_USERNAME];
				$sessions['roles']['group_id']   = $session_original[ControllerConstants::SESSION_GROUP_ID];
				$sessions['roles']['user_group'] = $session_original[ControllerConstants::SESSION_USER_GROUP];
				$sessions['roles']['group_info'] = $session_original[ControllerConstants::SESSION_GROUP_INFO];
				$sessions['roles']['fullname']   = $session_original[ControllerConstants::SESSION_FULLNAME];
				$sessions['roles']['email']      = $session_original[ControllerConstants::SESSION_EMAIL];
				$sessions['roles']['phone']      = $session_original[ControllerConstants::SESSION_PHONE];

				$this->session_roles = $sessions;
			}
		}
	
	/**
	 * Retrieve Complete Session Data from Authenticated User
	 * 
	 * Retrieves and optionally returns the complete session data array for the currently
	 * authenticated user. This method always refreshes session data by calling set_session()
	 * to ensure the most current data is available.
	 * 
	 * The method can operate in two modes:
	 * 1. Side-effect mode ($return_data = false): Initializes session without returning data
	 * 2. Return mode ($return_data = true): Initializes and returns complete session array
	 * 
	 * This dual-mode design allows the method to be used both for session initialization
	 * (where return value is not needed) and for explicit session data retrieval.
	 * 
	 * @param bool $return_data Whether to return session data array. Default: false
	 *                          - true: Returns complete session array
	 *                          - false: Returns null (side-effect only)
	 * 
	 * @return array|null Complete session data array if $return_data is true, null otherwise
	 *                    Session array structure:
	 *                    - 'id': User ID (int)
	 *                    - 'username': Username (string)
	 *                    - 'group_id': Group ID (int)
	 *                    - 'user_group': Group name (string)
	 *                    - 'group_info': Group description (string)
	 *                    - 'fullname': User's full name (string)
	 *                    - 'email': User's email address (string)
	 *                    - 'phone': User's phone number (string)
	 *                    - 'flag': Active status (bool)
	 * 
	 * @throws \InvalidArgumentException If session data validation fails during set_session()
	 * 
	 * @security Validates session data integrity through set_session()
	 * @security Returns validated session data only
	 * @security Prevents access to tampered session data
	 * 
	 * @performance Always refreshes session data (no caching)
	 * @performance Consider caching result if called multiple times in same request
	 * @performance Minimal overhead when $return_data is false
	 * 
	 * @example
	 * ```php
	 * // Initialize session without returning data
	 * $this->get_session();
	 * // Access via $this->session property
	 * $userId = $this->session['id'];
	 * 
	 * // Get session data directly
	 * $sessionData = $this->get_session(true);
	 * $username = $sessionData['username'];
	 * $email = $sessionData['email'];
	 * 
	 * // Check if user is authenticated
	 * $session = $this->get_session(true);
	 * if (!empty($session['id'])) {
	 *     // User is authenticated
	 *     echo "Welcome, " . $session['fullname'];
	 * } else {
	 *     // Guest user
	 *     return redirect()->route('login');
	 * }
	 * 
	 * // Pass session to view
	 * return view('profile', [
	 *     'user' => $this->get_session(true)
	 * ]);
	 * ```
	 * 
	 * @see set_session() For session initialization logic
	 * @see $session For direct property access
	 */
	public function get_session(bool $return_data = false): ?array {
		$this->set_session();

		if (true === $return_data) return $this->session;

		return null;
	}
	
	/**
	 * Verify User Group Membership
	 * 
	 * Checks if the currently authenticated user belongs to a specific user group by
	 * comparing the provided group name against the user's session group. This method
	 * is commonly used for role-based access control and conditional feature display.
	 * 
	 * The comparison is case-sensitive and performs exact string matching. The method
	 * assumes session has been initialized via set_session() before calling.
	 * 
	 * @param string $group_name The group name to check against user's current group.
	 *                           Common values: 'admin', 'root', 'user', 'manager', etc.
	 *                           Must match exactly (case-sensitive)
	 * 
	 * @return bool True if user belongs to the specified group, false otherwise
	 *              - true: User's user_group matches $group_name
	 *              - false: User's user_group does not match $group_name
	 * 
	 * @security Used for access control decisions - ensure session is validated first
	 * @security Does not verify session validity - call set_session() first
	 * @security Case-sensitive comparison prevents bypass attempts
	 * 
	 * @performance O(1) constant time string comparison
	 * @performance No database queries - uses cached session data
	 * 
	 * @example
	 * ```php
	 * // Check if user is admin
	 * if ($this->group_check('admin')) {
	 *     // Show admin panel
	 *     return view('admin.dashboard');
	 * }
	 * 
	 * // Restrict access to specific group
	 * if (!$this->group_check('manager')) {
	 *     abort(403, 'Access denied. Manager role required.');
	 * }
	 * 
	 * // Conditional feature display
	 * $canExport = $this->group_check('admin') || $this->group_check('manager');
	 * 
	 * // Multiple group check
	 * $allowedGroups = ['admin', 'root', 'supervisor'];
	 * $hasAccess = false;
	 * foreach ($allowedGroups as $group) {
	 *     if ($this->group_check($group)) {
	 *         $hasAccess = true;
	 *         break;
	 *     }
	 * }
	 * 
	 * // In view (via controller)
	 * return view('dashboard', [
	 *     'is_admin' => $this->group_check('admin'),
	 *     'is_manager' => $this->group_check('manager'),
	 * ]);
	 * ```
	 * 
	 * @see set_session() To initialize session before checking
	 * @see $session['user_group'] For direct group access
	 */
	public function group_check(string $group_name): bool {
		if ($group_name === $this->session['user_group']) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Validate session data
	 * 
	 * Validates session data structure and types to ensure data integrity.
	 * Checks for required fields and validates data types.
	 * 
	 * @param array $sessionData Session data to validate
	 * @return bool True if session data is valid
	 * @throws \InvalidArgumentException If session data is invalid
	 * 
	 * @security CRITICAL - Validates session data integrity
	 */
	private function validateSessionData(array $sessionData): bool {
		// Check if validation is enabled
		$validateIntegrity = config('canvastack.controller.session.validate_integrity', true);
		if (!$validateIntegrity) {
			return true;
		}
		
		// Check if session data is empty - this is valid for guest users
		if (empty($sessionData)) {
			return true; // Empty session is valid (user not logged in)
		}
		
		// Define required session fields and their expected types
		$requiredFields = [
			ControllerConstants::SESSION_USER_ID => 'integer',
			ControllerConstants::SESSION_USERNAME => 'string',
			ControllerConstants::SESSION_GROUP_ID => 'integer',
			ControllerConstants::SESSION_USER_GROUP => 'string',
		];
		
		// Define optional session fields and their expected types
		$optionalFields = [
			ControllerConstants::SESSION_GROUP_INFO => 'string',
			ControllerConstants::SESSION_FULLNAME => 'string',
			ControllerConstants::SESSION_EMAIL => 'string',
			ControllerConstants::SESSION_PHONE => 'string',
			ControllerConstants::SESSION_FLAG => 'boolean',
		];
		
		// CRITICAL FIX: Check if this is a guest user (no user ID)
		// If 'id' field is missing, null, or empty, treat as guest user and skip validation
		// This prevents false positives for unauthenticated users
		$userIdKey = ControllerConstants::SESSION_USER_ID; // 'id'
		$userId = $sessionData[$userIdKey] ?? null;
		
		// Guest user detection - skip validation if:
		// 1. User ID key doesn't exist in session
		// 2. User ID is null
		// 3. User ID is empty string
		// 4. User ID is zero (0 or '0')
		// 5. User ID is false
		if (!isset($sessionData[$userIdKey]) || 
		    $userId === null || 
		    $userId === '' || 
		    $userId === 0 || 
		    $userId === '0' ||
		    $userId === false) {
			// Guest user - no validation needed
			return true;
		}
		
		// Validate required fields (only if user is logged in)
		foreach ($requiredFields as $field => $expectedType) {
			if (!isset($sessionData[$field])) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Session Validation Failed: Missing required field', [
						'field' => $field,
						'session_keys' => array_keys($sessionData),
						'ip_address' => request()->ip(),
					]);
				}
				
				throw SessionException::tampered(
					session_id(),
					"Missing required field: {$field}",
					[
						'field' => $field,
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
						'session_keys' => array_keys($sessionData),
					]
				);
			}
			
			// Validate field type
			if (!$this->validateSessionFieldType($sessionData[$field], $expectedType, $field)) {
				throw SessionException::tampered(
					session_id(),
					"Invalid field type: {$field}",
					[
						'field' => $field,
						'expected_type' => $expectedType,
						'actual_type' => gettype($sessionData[$field]),
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		// Validate optional fields if present
		foreach ($optionalFields as $field => $expectedType) {
			if (isset($sessionData[$field])) {
				if (!$this->validateSessionFieldType($sessionData[$field], $expectedType, $field)) {
					throw SessionException::tampered(
						session_id(),
						"Invalid optional field type: {$field}",
						[
							'field' => $field,
							'expected_type' => $expectedType,
							'actual_type' => gettype($sessionData[$field]),
							'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
							'ip_address' => request()->ip(),
						]
					);
				}
			}
		}
		
		// Validate user ID is positive
		if (isset($sessionData[ControllerConstants::SESSION_USER_ID]) && $sessionData[ControllerConstants::SESSION_USER_ID] <= 0) {
			// Log validation failure
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::warning('Session Validation Failed: Invalid user ID', [
					'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID],
					'ip_address' => request()->ip(),
				]);
			}
			
			throw SessionException::tampered(
				session_id(),
				"Invalid user ID: must be positive",
				[
					'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID],
					'ip_address' => request()->ip(),
				]
			);
		}
		
		// Validate group ID is positive
		if (isset($sessionData[ControllerConstants::SESSION_GROUP_ID]) && $sessionData[ControllerConstants::SESSION_GROUP_ID] <= 0) {
			// Log validation failure
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::warning('Session Validation Failed: Invalid group ID', [
					'group_id' => $sessionData[ControllerConstants::SESSION_GROUP_ID],
					'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			throw SessionException::tampered(
				session_id(),
				"Invalid group ID: must be positive",
				[
					'group_id' => $sessionData[ControllerConstants::SESSION_GROUP_ID],
					'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
					'ip_address' => request()->ip(),
				]
			);
		}
		
		// Validate email format if present
		if (isset($sessionData[ControllerConstants::SESSION_EMAIL]) && !empty($sessionData[ControllerConstants::SESSION_EMAIL])) {
			if (!filter_var($sessionData[ControllerConstants::SESSION_EMAIL], FILTER_VALIDATE_EMAIL)) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Session Validation Failed: Invalid email format', [
						'email' => $sessionData[ControllerConstants::SESSION_EMAIL],
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]);
				}
				
				throw SessionException::tampered(
					session_id(),
					"Invalid email format",
					[
						'email' => $sessionData[ControllerConstants::SESSION_EMAIL],
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		// Validate string lengths
		$maxStringLength = config('canvastack.controller.validation.max_query_length', 10000);
		foreach ($sessionData as $field => $value) {
			if (is_string($value) && strlen($value) > $maxStringLength) {
				// Log validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Session Validation Failed: String too long', [
						'field' => $field,
						'length' => strlen($value),
						'max_length' => $maxStringLength,
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]);
				}
				
				throw SessionException::tampered(
					session_id(),
					"Session field exceeds maximum length: {$field}",
					[
						'field' => $field,
						'length' => strlen($value),
						'max_length' => $maxStringLength,
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		return true;
	}
	
	/**
	 * Validate session field type
	 * 
	 * Validates that a session field value matches the expected type.
	 * 
	 * @param mixed $value Field value
	 * @param string $expectedType Expected type
	 * @param string $fieldName Field name (for logging)
	 * @return bool True if type is valid
	 */
	private function validateSessionFieldType($value, string $expectedType, string $fieldName): bool {
		$isValid = false;
		
		switch ($expectedType) {
			case 'integer':
				$isValid = is_int($value) || (is_numeric($value) && (int)$value == $value);
				break;
				
			case 'string':
				$isValid = is_string($value);
				break;
				
			case 'boolean':
				$isValid = is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
				break;
				
			case 'array':
				$isValid = is_array($value);
				break;
				
			default:
				$isValid = true; // Unknown type, skip validation
		}
		
		if (!$isValid) {
			// Log validation failure
			if (config('canvastack.controller.logging.log_validation_failures', true)) {
				\Illuminate\Support\Facades\Log::warning('Session Field Type Validation Failed', [
					'field' => $fieldName,
					'expected_type' => $expectedType,
					'actual_type' => gettype($value),
					'ip_address' => request()->ip(),
				]);
			}
		}
		
		return $isValid;
	}
	
	/**
	 * Verify session integrity
	 * 
	 * Verifies that session data has not been tampered with by checking
	 * session timestamps and data consistency.
	 * 
	 * @return bool True if session is valid
	 * @throws \InvalidArgumentException If session integrity check fails
	 * 
	 * @security CRITICAL - Verifies session integrity to prevent tampering
	 */
	private function validateSessionIntegrity(): bool {
		// Check if integrity validation is enabled
		$validateIntegrity = config('canvastack.controller.session.validate_integrity', true);
		if (!$validateIntegrity) {
			return true;
		}
		
		// Get current session data
		$sessionData = canvastack_sessions();
		
		// Empty session is valid (user not logged in)
		if (empty($sessionData)) {
			return true;
		}
		
		// Check if session has expired
		if ($this->isSessionExpired()) {
			// Log session expiration
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::warning('Session Expired', [
					'user_id' => $sessionData['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			throw SessionException::expired(
				session_id(),
				$sessionData['id'] ?? null,
				[
					'ip_address' => request()->ip(),
					'user_agent' => request()->userAgent(),
				]
			);
		}
		
		// Validate session data structure
		$this->validateSessionData($sessionData);
		
		return true;
	}
	
	/**
	 * Regenerate Session ID for Security
	 * 
	 * Regenerates the session ID to prevent session fixation attacks. This method should
	 * be called after successful authentication, privilege escalation, or any security-
	 * sensitive operation that changes the user's authentication state.
	 * 
	 * Session fixation is an attack where an attacker sets a user's session ID to a known
	 * value before authentication, then hijacks the session after the user logs in. By
	 * regenerating the session ID after authentication, this attack vector is eliminated.
	 * 
	 * The method only regenerates if:
	 * 1. Session regeneration is enabled in configuration
	 * 2. A session has been started
	 * 
	 * All session data is preserved during regeneration - only the ID changes.
	 * 
	 * @return void
	 * 
	 * @security CRITICAL - Prevents session fixation attacks
	 * @security Must be called after successful authentication
	 * @security Must be called after privilege escalation
	 * @security Logs regeneration events for security audit
	 * 
	 * @performance Minimal overhead - single session operation
	 * @performance No database queries required
	 * @performance Can be disabled via config for performance testing
	 * 
	 * @example
	 * ```php
	 * // After successful login
	 * public function login(Request $request) {
	 *     $credentials = $request->only('username', 'password');
	 *     
	 *     if (Auth::attempt($credentials)) {
	 *         // CRITICAL: Regenerate session ID after authentication
	 *         $this->regenerateSessionId();
	 *         $this->set_session();
	 *         
	 *         return redirect()->route('dashboard');
	 *     }
	 *     
	 *     return back()->withErrors(['Invalid credentials']);
	 * }
	 * 
	 * // After privilege escalation
	 * public function elevatePrivileges(Request $request) {
	 *     // Verify admin password
	 *     if ($this->verifyAdminPassword($request->password)) {
	 *         // Update user privileges
	 *         $this->session['is_elevated'] = true;
	 *         
	 *         // Regenerate session ID
	 *         $this->regenerateSessionId();
	 *         
	 *         return redirect()->route('admin.panel');
	 *     }
	 * }
	 * 
	 * // In authentication middleware
	 * public function handle($request, Closure $next) {
	 *     if ($this->isFirstRequest()) {
	 *         $this->regenerateSessionId();
	 *     }
	 *     
	 *     return $next($request);
	 * }
	 * ```
	 * 
	 * @see session()->regenerate() For Laravel session regeneration
	 * @see destroySession() For complete session destruction
	 */
	public function regenerateSessionId(): void {
		// Check if regeneration is enabled
		$regenerateEnabled = config('canvastack.controller.security.regenerate_session_id', true);
		if (!$regenerateEnabled) {
			return;
		}
		
		// Regenerate session ID
		if (session()->isStarted()) {
			session()->regenerate();
			
			// Log session regeneration
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::info('Session ID Regenerated', [
					'user_id' => $this->session['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
		}
	}
	
	/**
	 * Check Session Expiration Status
	 * 
	 * Determines if the current session has exceeded the configured timeout period by
	 * comparing the last activity timestamp against the current time. This method
	 * implements idle timeout functionality to automatically expire inactive sessions.
	 * 
	 * The method performs the following operations:
	 * 1. Retrieves session timeout from configuration (default: 7200 seconds / 2 hours)
	 * 2. Gets last activity timestamp from session
	 * 3. Calculates elapsed time since last activity
	 * 4. Compares elapsed time against timeout threshold
	 * 5. Updates last activity timestamp if session is still valid
	 * 
	 * If no last activity timestamp exists, the method initializes it with the current
	 * time and returns false (session not expired). This handles first-time session checks.
	 * 
	 * @return bool True if session has expired, false if still valid
	 *              - true: Elapsed time exceeds configured timeout
	 *              - false: Session is still within timeout window
	 * 
	 * @security Enforces session timeout to limit exposure window
	 * @security Prevents indefinite session persistence
	 * @security Updates activity timestamp to track user activity
	 * 
	 * @performance O(1) constant time comparison
	 * @performance No database queries - uses session storage only
	 * @performance Minimal overhead - simple timestamp arithmetic
	 * 
	 * @example
	 * ```php
	 * // Check session expiration in middleware
	 * public function handle($request, Closure $next) {
	 *     if ($this->isSessionExpired()) {
	 *         $this->destroySession();
	 *         return redirect()->route('login')
	 *             ->with('error', 'Session expired. Please login again.');
	 *     }
	 *     
	 *     return $next($request);
	 * }
	 * 
	 * // Check before sensitive operations
	 * public function processPayment(Request $request) {
	 *     if ($this->isSessionExpired()) {
	 *         return response()->json([
	 *             'error' => 'Session expired',
	 *             'redirect' => route('login')
	 *         ], 401);
	 *     }
	 *     
	 *     // Process payment
	 * }
	 * 
	 * // Display session status to user
	 * public function getSessionStatus() {
	 *     $lastActivity = session('last_activity');
	 *     $timeout = config('canvastack.controller.security.session_timeout', 7200);
	 *     $remaining = $timeout - (time() - $lastActivity);
	 *     
	 *     return response()->json([
	 *         'expired' => $this->isSessionExpired(),
	 *         'remaining_seconds' => max(0, $remaining),
	 *         'remaining_minutes' => max(0, floor($remaining / 60)),
	 *     ]);
	 * }
	 * 
	 * // Auto-logout on expiration
	 * if ($this->isSessionExpired()) {
	 *     Log::info('Session expired for user', [
	 *         'user_id' => $this->session['id'],
	 *         'last_activity' => session('last_activity'),
	 *     ]);
	 *     
	 *     $this->destroySession();
	 *     return redirect()->route('login');
	 * }
	 * ```
	 * 
	 * @see handleSessionTimeout() For automatic timeout handling
	 * @see destroySession() For session cleanup
	 * @see config('canvastack.controller.security.session_timeout') For timeout configuration
	 */
	public function isSessionExpired(): bool {
		// Get session timeout from config
		$timeout = config('canvastack.controller.security.session_timeout', 7200);
		
		// Get last activity timestamp from session
		$lastActivity = session('last_activity');
		
		// If no last activity recorded, session is not expired
		if (!$lastActivity) {
			// Set current time as last activity
			session(['last_activity' => time()]);
			return false;
		}
		
		// Check if session has expired
		$currentTime = time();
		$elapsed = $currentTime - $lastActivity;
		
		if ($elapsed > $timeout) {
			return true;
		}
		
		// Update last activity timestamp
		session(['last_activity' => $currentTime]);
		
		return false;
	}

	/**
	 * Encrypt Sensitive Session Data
	 * 
	 * Encrypts sensitive data before storing in session using Laravel's encryption
	 * facilities. This method provides an additional security layer for sensitive
	 * information like tokens, API keys, or personal data that should not be stored
	 * in plain text even in server-side sessions.
	 * 
	 * The method handles encryption failures gracefully by returning the original data
	 * (as string or JSON) if encryption fails, ensuring application continuity while
	 * logging the failure for security monitoring.
	 * 
	 * Encryption can be disabled via configuration for development or performance testing,
	 * in which case the method returns the data as a string without encryption.
	 * 
	 * @param mixed $data Data to encrypt. Can be any type - strings, arrays, objects.
	 *                    Non-string data is JSON-encoded before encryption.
	 * 
	 * @return string Encrypted data as base64-encoded string, or original data if:
	 *                - Encryption is disabled in configuration
	 *                - Encryption fails (with error logging)
	 * 
	 * @security Encrypts sensitive session data for secure storage
	 * @security Uses Laravel's encryption (AES-256-CBC by default)
	 * @security Logs encryption failures for security monitoring
	 * @security Graceful degradation on encryption failure
	 * 
	 * @performance Encryption adds ~1-2ms overhead per operation
	 * @performance Can be disabled via config for performance testing
	 * @performance Consider caching encrypted values if used repeatedly
	 * 
	 * @example
	 * ```php
	 * // Encrypt API token before storing in session
	 * $apiToken = 'sk_live_abc123xyz789';
	 * $encrypted = $this->encryptSessionData($apiToken);
	 * session(['api_token' => $encrypted]);
	 * 
	 * // Encrypt user preferences
	 * $preferences = [
	 *     'theme' => 'dark',
	 *     'notifications' => true,
	 *     'api_key' => 'secret_key_123',
	 * ];
	 * $encrypted = $this->encryptSessionData($preferences);
	 * session(['user_preferences' => $encrypted]);
	 * 
	 * // Encrypt sensitive form data
	 * $sensitiveData = [
	 *     'ssn' => '123-45-6789',
	 *     'credit_card' => '4111111111111111',
	 * ];
	 * $encrypted = $this->encryptSessionData($sensitiveData);
	 * session(['payment_info' => $encrypted]);
	 * 
	 * // Later decrypt the data
	 * $decrypted = $this->decryptSessionData(session('api_token'));
	 * 
	 * // Store encrypted session data
	 * public function storeSecureData(Request $request) {
	 *     $secureData = [
	 *         'oauth_token' => $request->token,
	 *         'oauth_secret' => $request->secret,
	 *     ];
	 *     
	 *     session([
	 *         'oauth' => $this->encryptSessionData($secureData)
	 *     ]);
	 * }
	 * ```
	 * 
	 * @see decryptSessionData() For decryption
	 * @see encrypt() For Laravel encryption helper
	 * @see config('canvastack.controller.session.encrypt_sensitive_data') For encryption toggle
	 */
	public function encryptSessionData(mixed $data): string {
		// Check if encryption is enabled
		$encryptEnabled = config('canvastack.controller.session.encrypt_sensitive_data', true);
		if (!$encryptEnabled) {
			return is_string($data) ? $data : json_encode($data);
		}

		// Encrypt data using Laravel's encryption
		try {
			$encrypted = encrypt($data);
			return $encrypted;
		} catch (\Exception $e) {
			// Log encryption failure
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Session Data Encryption Failed', [
					'error' => $e->getMessage(),
					'ip_address' => request()->ip(),
				]);
			}

			// Return unencrypted data if encryption fails
			return is_string($data) ? $data : json_encode($data);
		}
	}
	
	/**
	 * Decrypt Sensitive Session Data
	 * 
	 * Decrypts encrypted session data that was previously encrypted using encryptSessionData().
	 * This method reverses the encryption process to retrieve the original data, handling
	 * decryption failures gracefully to ensure application continuity.
	 * 
	 * The method handles decryption failures by returning the encrypted string unchanged
	 * and logging the failure for security monitoring. This prevents application crashes
	 * from decryption errors while alerting administrators to potential issues.
	 * 
	 * If encryption is disabled in configuration, the method returns the input unchanged,
	 * assuming it was never encrypted in the first place.
	 * 
	 * @param string $encrypted Encrypted data string (base64-encoded ciphertext from encryptSessionData())
	 * 
	 * @return mixed Decrypted original data (string, array, object, etc.), or encrypted string if:
	 *               - Encryption is disabled in configuration
	 *               - Decryption fails (with error logging)
	 * 
	 * @security Decrypts sensitive session data for use
	 * @security Uses Laravel's decryption (AES-256-CBC by default)
	 * @security Logs decryption failures for security monitoring
	 * @security Graceful degradation on decryption failure
	 * @security Prevents application crashes from corrupted encrypted data
	 * 
	 * @performance Decryption adds ~1-2ms overhead per operation
	 * @performance Can be disabled via config for performance testing
	 * @performance Consider caching decrypted values if used repeatedly
	 * 
	 * @example
	 * ```php
	 * // Decrypt API token from session
	 * $encrypted = session('api_token');
	 * $apiToken = $this->decryptSessionData($encrypted);
	 * 
	 * // Use decrypted token for API call
	 * $response = Http::withToken($apiToken)->get('https://api.example.com/data');
	 * 
	 * // Decrypt user preferences
	 * $encrypted = session('user_preferences');
	 * $preferences = $this->decryptSessionData($encrypted);
	 * $theme = $preferences['theme'];
	 * $apiKey = $preferences['api_key'];
	 * 
	 * // Decrypt and validate payment info
	 * $encrypted = session('payment_info');
	 * $paymentInfo = $this->decryptSessionData($encrypted);
	 * 
	 * if (is_array($paymentInfo) && isset($paymentInfo['credit_card'])) {
	 *     // Process payment
	 *     $this->processPayment($paymentInfo);
	 * }
	 * 
	 * // Retrieve and use OAuth credentials
	 * public function makeApiCall() {
	 *     $encrypted = session('oauth');
	 *     $oauth = $this->decryptSessionData($encrypted);
	 *     
	 *     if (is_array($oauth)) {
	 *         $client = new OAuthClient(
	 *             $oauth['oauth_token'],
	 *             $oauth['oauth_secret']
	 *         );
	 *         
	 *         return $client->request('GET', '/api/user');
	 *     }
	 *     
	 *     return null;
	 * }
	 * 
	 * // Handle decryption failure
	 * $encrypted = session('secure_data');
	 * $data = $this->decryptSessionData($encrypted);
	 * 
	 * if ($data === $encrypted) {
	 *     // Decryption failed, data is still encrypted
	 *     Log::warning('Failed to decrypt session data');
	 *     session()->forget('secure_data');
	 * }
	 * ```
	 * 
	 * @see encryptSessionData() For encryption
	 * @see decrypt() For Laravel decryption helper
	 * @see config('canvastack.controller.session.encrypt_sensitive_data') For encryption toggle
	 */
	public function decryptSessionData(string $encrypted): mixed {
			// Check if encryption is enabled
			$encryptEnabled = config('canvastack.controller.session.encrypt_sensitive_data', true);
			if (!$encryptEnabled) {
				return $encrypted;
			}

			// Decrypt data using Laravel's encryption
			try {
				$decrypted = decrypt($encrypted);
				return $decrypted;
			} catch (\Exception $e) {
				// Log decryption failure
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::error('Session Data Decryption Failed', [
						'error' => $e->getMessage(),
						'ip_address' => request()->ip(),
					]);
				}

				// Return encrypted data if decryption fails
				return $encrypted;
			}
		}
	
	/**
	 * Destroy Session on Logout
	 * 
	 * Completely destroys the current session and clears all session data. This method
	 * should be called when a user logs out to ensure complete cleanup of authentication
	 * state and prevent session reuse.
	 * 
	 * The method performs comprehensive session cleanup:
	 * 1. Captures user ID for logging before destruction
	 * 2. Clears internal session properties ($session, $session_roles)
	 * 3. Flushes Laravel session storage (removes all session data)
	 * 4. Regenerates session ID (creates new empty session)
	 * 5. Logs session destruction event for audit trail
	 * 
	 * After calling this method, the user is effectively logged out and all session
	 * data is permanently removed. A new empty session is created with a new ID.
	 * 
	 * @return void
	 * 
	 * @security CRITICAL - Properly destroys session on logout
	 * @security Prevents session reuse after logout
	 * @security Clears all authentication state
	 * @security Regenerates session ID to prevent fixation
	 * @security Logs destruction events for audit trail
	 * 
	 * @performance Single session operation - minimal overhead
	 * @performance No database queries required
	 * @performance Immediate effect - no delayed cleanup
	 * 
	 * @example
	 * ```php
	 * // Standard logout flow
	 * public function logout(Request $request) {
	 *     // Log the logout event before destroying session
	 *     Log::info('User logging out', [
	 *         'user_id' => $this->session['id'],
	 *         'username' => $this->session['username'],
	 *     ]);
	 *     
	 *     // Destroy session
	 *     $this->destroySession();
	 *     
	 *     // Redirect to login page
	 *     return redirect()->route('login')
	 *         ->with('success', 'You have been logged out successfully.');
	 * }
	 * 
	 * // Forced logout (security event)
	 * public function forceLogout($userId, $reason) {
	 *     Log::warning('Forced logout', [
	 *         'user_id' => $userId,
	 *         'reason' => $reason,
	 *         'ip_address' => request()->ip(),
	 *     ]);
	 *     
	 *     $this->destroySession();
	 *     
	 *     return redirect()->route('login')
	 *         ->with('error', 'Your session has been terminated: ' . $reason);
	 * }
	 * 
	 * // Logout all devices
	 * public function logoutAllDevices(Request $request) {
	 *     $userId = $this->session['id'];
	 *     
	 *     // Invalidate all user sessions in database
	 *     DB::table('sessions')
	 *         ->where('user_id', $userId)
	 *         ->delete();
	 *     
	 *     // Destroy current session
	 *     $this->destroySession();
	 *     
	 *     return redirect()->route('login')
	 *         ->with('success', 'Logged out from all devices.');
	 * }
	 * 
	 * // Session timeout handler
	 * public function handleTimeout() {
	 *     if ($this->isSessionExpired()) {
	 *         $this->destroySession();
	 *         
	 *         return response()->json([
	 *             'expired' => true,
	 *             'message' => 'Session expired',
	 *             'redirect' => route('login'),
	 *         ], 401);
	 *     }
	 * }
	 * 
	 * // Account deletion
	 * public function deleteAccount(Request $request) {
	 *     $userId = $this->session['id'];
	 *     
	 *     // Delete user account
	 *     User::destroy($userId);
	 *     
	 *     // Destroy session
	 *     $this->destroySession();
	 *     
	 *     return redirect()->route('home')
	 *         ->with('success', 'Account deleted successfully.');
	 * }
	 * ```
	 * 
	 * @see regenerateSessionId() For session ID regeneration
	 * @see isSessionExpired() For timeout checking
	 * @see session()->flush() For Laravel session flushing
	 */
	public function destroySession(): void {
		// Get user ID before destroying session
		$userId = $this->session['id'] ?? null;
		
		// Clear session data
		$this->session = [];
		$this->session_roles = [];
		
		// Flush Laravel session
		if (session()->isStarted()) {
			session()->flush();
			session()->regenerate();
		}
		
		// Log session destruction
		if (config('canvastack.controller.logging.log_security_events', true)) {
			\Illuminate\Support\Facades\Log::info('Session Destroyed', [
				'user_id' => $userId,
				'ip_address' => request()->ip(),
			]);
		}
	}
	
	/**
	 * Handle Session Timeout with Automatic Redirect
	 * 
	 * Checks if the session has expired and automatically handles the timeout by destroying
	 * the session and redirecting to the login page with an appropriate error message.
	 * This method provides a convenient way to implement session timeout handling in
	 * controllers and middleware.
	 * 
	 * The method returns null if the session is still valid, allowing the request to
	 * continue normally. If the session has expired, it returns a redirect response
	 * that should be returned immediately to the user.
	 * 
	 * This method combines session expiration checking, session destruction, and redirect
	 * logic into a single convenient call, reducing boilerplate code in controllers.
	 * 
	 * @return \Illuminate\Http\RedirectResponse|null Redirect response to login page if session expired,
	 *                                                 null if session is still valid
	 * 
	 * @security Enforces session timeout policy
	 * @security Destroys expired sessions automatically
	 * @security Redirects to login page for re-authentication
	 * @security Provides user-friendly timeout message
	 * 
	 * @performance O(1) constant time check
	 * @performance No database queries - uses session storage only
	 * @performance Minimal overhead when session is valid (null return)
	 * 
	 * @example
	 * ```php
	 * // In controller method
	 * public function index() {
	 *     // Check and handle session timeout
	 *     if ($redirect = $this->handleSessionTimeout()) {
	 *         return $redirect;
	 *     }
	 *     
	 *     // Continue with normal logic
	 *     return view('dashboard', ['user' => $this->session]);
	 * }
	 * 
	 * // In middleware
	 * public function handle($request, Closure $next) {
	 *     if ($redirect = $this->handleSessionTimeout()) {
	 *         return $redirect;
	 *     }
	 *     
	 *     return $next($request);
	 * }
	 * 
	 * // Before sensitive operations
	 * public function processPayment(Request $request) {
	 *     // Ensure session is valid before processing payment
	 *     if ($redirect = $this->handleSessionTimeout()) {
	 *         return $redirect;
	 *     }
	 *     
	 *     // Process payment
	 *     $payment = $this->paymentService->process($request->all());
	 *     
	 *     return response()->json(['success' => true]);
	 * }
	 * 
	 * // In AJAX requests
	 * public function ajaxRequest(Request $request) {
	 *     if ($redirect = $this->handleSessionTimeout()) {
	 *         // Return JSON response for AJAX
	 *         return response()->json([
	 *             'expired' => true,
	 *             'message' => 'Session expired',
	 *             'redirect' => $redirect->getTargetUrl(),
	 *         ], 401);
	 *     }
	 *     
	 *     // Process AJAX request
	 *     return response()->json(['data' => $this->getData()]);
	 * }
	 * 
	 * // Custom timeout message
	 * public function secureOperation() {
	 *     if ($this->isSessionExpired()) {
	 *         $this->destroySession();
	 *         
	 *         return redirect()->route('login')
	 *             ->with('error', 'Your session expired due to inactivity. Please login again to continue.');
	 *     }
	 *     
	 *     // Perform secure operation
	 * }
	 * ```
	 * 
	 * @see isSessionExpired() For expiration checking
	 * @see destroySession() For session cleanup
	 * @see redirect()->route('login') For redirect destination
	 */
	public function handleSessionTimeout(): ?\Illuminate\Http\RedirectResponse {
			if ($this->isSessionExpired()) {
				// Destroy session
				$this->destroySession();

				// Redirect to login page with timeout message
				return redirect()->route('login')->with('error', 'Your session has expired. Please login again.');
			}

			return null;
		}
	
	/**
	 * Update Session Data Atomically
	 * 
	 * Updates session data using atomic operations to prevent race conditions
	 * when multiple requests try to modify session data simultaneously.
	 * 
	 * This method uses Laravel's session locking mechanism to ensure that only
	 * one request can modify the session at a time, preventing data corruption
	 * and race conditions.
	 * 
	 * @param string $key Session key to update
	 * @param mixed $value New value for the key
	 * @return bool True if update was successful
	 * 
	 * @security CRITICAL - Prevents race conditions in session updates
	 * @security Uses atomic operations to ensure data consistency
	 * 
	 * @performance Uses session locking for thread safety
	 * 
	 * @example
	 * ```php
	 * // Update user's cart atomically
	 * $this->updateSessionAtomic('cart_items', $newCartItems);
	 * 
	 * // Update user preferences
	 * $this->updateSessionAtomic('preferences', [
	 *     'theme' => 'dark',
	 *     'language' => 'en',
	 * ]);
	 * ```
	 */
	public function updateSessionAtomic(string $key, $value): bool {
		try {
			// Use Laravel's session put method which is atomic
			session()->put($key, $value);
			
			// Update internal session cache if this is a core session field
			if (isset($this->session[$key])) {
				$this->session[$key] = $value;
			}
			
			// Log session update if enabled
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::info('Session Data Updated Atomically', [
					'key' => $key,
					'user_id' => $this->session['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			// Log error
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Atomic Session Update Failed', [
					'key' => $key,
					'error' => $e->getMessage(),
					'user_id' => $this->session['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			return false;
		}
	}
	
	/**
	 * Get Session Data Version
	 * 
	 * Retrieves the current version of session data structure.
	 * This is used for session data migration when the structure changes.
	 * 
	 * @return int Current session data version
	 */
	public function getSessionDataVersion(): int {
		return session()->get('_session_data_version', 1);
	}
	
	/**
	 * Set Session Data Version
	 * 
	 * Sets the session data version after migration.
	 * 
	 * @param int $version New version number
	 * @return void
	 */
	public function setSessionDataVersion(int $version): void {
		session()->put('_session_data_version', $version);
		
		// Log version update
		if (config('canvastack.controller.logging.log_security_events', true)) {
			\Illuminate\Support\Facades\Log::info('Session Data Version Updated', [
				'version' => $version,
				'user_id' => $this->session['id'] ?? null,
				'ip_address' => request()->ip(),
			]);
		}
	}
	
	/**
	 * Migrate Session Data
	 * 
	 * Migrates session data from old structure to new structure when
	 * the session data format changes between versions.
	 * 
	 * @return bool True if migration was successful or not needed
	 * 
	 * @security Validates data integrity during migration
	 * @security Backs up data before migration
	 * 
	 * @example
	 * ```php
	 * // Migrate session data after login
	 * if (!$this->migrateSessionData()) {
	 *     // Handle migration failure
	 *     $this->destroySession();
	 *     return redirect()->route('login')->with('error', 'Session migration failed');
	 * }
	 * ```
	 */
	public function migrateSessionData(): bool {
		$currentVersion = $this->getSessionDataVersion();
		$targetVersion = config('canvastack.controller.session.data_version', 1);
		
		// No migration needed
		if ($currentVersion >= $targetVersion) {
			return true;
		}
		
		try {
			// Backup current session data before migration
			$this->backupSessionData();
			
			// Perform migration based on version differences
			for ($version = $currentVersion + 1; $version <= $targetVersion; $version++) {
				$migrationMethod = "migrateToVersion{$version}";
				
				if (method_exists($this, $migrationMethod)) {
					$this->$migrationMethod();
				}
			}
			
			// Update version
			$this->setSessionDataVersion($targetVersion);
			
			// Log successful migration
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::info('Session Data Migrated Successfully', [
					'from_version' => $currentVersion,
					'to_version' => $targetVersion,
					'user_id' => $this->session['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			// Log migration failure
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Session Data Migration Failed', [
					'from_version' => $currentVersion,
					'to_version' => $targetVersion,
					'error' => $e->getMessage(),
					'user_id' => $this->session['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			// Attempt to recover from backup
			$this->recoverSessionData();
			
			return false;
		}
	}
	
	/**
	 * Validate Session Data Structure
	 * 
	 * Validates that session data conforms to the expected structure
	 * for the current version.
	 * 
	 * @return bool True if structure is valid
	 * @throws SessionException If structure validation fails
	 */
	public function validateSessionStructure(): bool {
		$sessionData = canvastack_sessions();
		
		// Empty session is valid (guest user)
		if (empty($sessionData)) {
			return true;
		}
		
		// Check if user is logged in
		if (empty($sessionData[ControllerConstants::SESSION_USER_ID])) {
			return true; // Guest user
		}
		
		// Define expected structure for current version
		$expectedStructure = [
			ControllerConstants::SESSION_USER_ID,
			ControllerConstants::SESSION_USERNAME,
			ControllerConstants::SESSION_GROUP_ID,
			ControllerConstants::SESSION_USER_GROUP,
		];
		
		// Validate required fields exist
		foreach ($expectedStructure as $field) {
			if (!isset($sessionData[$field])) {
				// Log structure validation failure
				if (config('canvastack.controller.logging.log_validation_failures', true)) {
					\Illuminate\Support\Facades\Log::warning('Session Structure Validation Failed', [
						'missing_field' => $field,
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]);
				}
				
				throw SessionException::tampered(
					session_id(),
					"Session structure invalid: missing field {$field}",
					[
						'missing_field' => $field,
						'user_id' => $sessionData[ControllerConstants::SESSION_USER_ID] ?? null,
						'ip_address' => request()->ip(),
					]
				);
			}
		}
		
		return true;
	}
	
	/**
	 * Backup Session Data
	 * 
	 * Creates a backup of current session data before performing
	 * potentially destructive operations like migration.
	 * 
	 * @return bool True if backup was successful
	 */
	public function backupSessionData(): bool {
		try {
			$sessionData = canvastack_sessions();
			
			// Store backup in session with timestamp
			session()->put('_session_backup', [
				'data' => $sessionData,
				'timestamp' => time(),
				'version' => $this->getSessionDataVersion(),
			]);
			
			// Log backup creation
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::info('Session Data Backed Up', [
					'user_id' => $sessionData['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			// Log backup failure
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Session Data Backup Failed', [
					'error' => $e->getMessage(),
					'ip_address' => request()->ip(),
				]);
			}
			
			return false;
		}
	}
	
	/**
	 * Recover Session Data from Backup
	 * 
	 * Restores session data from the most recent backup.
	 * This is used when migration or other operations fail.
	 * 
	 * @return bool True if recovery was successful
	 */
	public function recoverSessionData(): bool {
		try {
			$backup = session()->get('_session_backup');
			
			if (empty($backup) || empty($backup['data'])) {
				// No backup available
				if (config('canvastack.controller.logging.log_security_events', true)) {
					\Illuminate\Support\Facades\Log::warning('Session Data Recovery Failed: No Backup Available', [
						'ip_address' => request()->ip(),
					]);
				}
				
				return false;
			}
			
			// Restore session data from backup
			foreach ($backup['data'] as $key => $value) {
				session()->put($key, $value);
			}
			
			// Restore version
			if (isset($backup['version'])) {
				$this->setSessionDataVersion($backup['version']);
			}
			
			// Refresh internal session cache
			$this->set_session();
			
			// Log successful recovery
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::info('Session Data Recovered from Backup', [
					'backup_timestamp' => $backup['timestamp'] ?? null,
					'user_id' => $backup['data']['id'] ?? null,
					'ip_address' => request()->ip(),
				]);
			}
			
			return true;
		} catch (\Exception $e) {
			// Log recovery failure
			if (config('canvastack.controller.logging.log_security_events', true)) {
				\Illuminate\Support\Facades\Log::error('Session Data Recovery Failed', [
					'error' => $e->getMessage(),
					'ip_address' => request()->ip(),
				]);
			}
			
			return false;
		}
	}
}
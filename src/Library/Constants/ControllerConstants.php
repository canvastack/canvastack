<?php

namespace Canvastack\Canvastack\Library\Constants;

use Canvastack\Canvastack\Library\Helpers\ControllerConfig;

/**
 * Controller Constants
 * 
 * Centralized constants for Core Controller Components to replace magic strings
 * and improve code maintainability, type safety, and reduce typo-related bugs.
 * 
 * @package Canvastack\Canvastack\Library\Constants
 * @author Canvastack
 * @version 1.0.0
 * 
 * @example Basic usage in controllers:
 * ```php
 * use Canvastack\Canvastack\Library\Constants\ControllerConstants as CC;
 * 
 * // Route actions
 * if ($action === CC::ACTION_INDEX) {
 *     // Handle index action
 * }
 * 
 * // Session management
 * $userId = session(CC::SESSION_USER_ID);
 * $userGroup = session(CC::SESSION_USER_GROUP);
 * 
 * // Page types
 * $this->pageType = CC::PAGE_TYPE_ADMIN;
 * 
 * // File uploads
 * if ($fileType === CC::FILE_TYPE_IMAGE) {
 *     // Process image
 * }
 * ```
 * 
 * @example DataTables usage:
 * ```php
 * $draw = $request->input(CC::DT_PARAM_DRAW);
 * $start = $request->input(CC::DT_PARAM_START);
 * $length = $request->input(CC::DT_PARAM_LENGTH);
 * ```
 * 
 * @example Caching usage:
 * ```php
 * $cacheKey = CC::CACHE_PRIVILEGE_PREFIX . $userId;
 * Cache::remember($cacheKey, CC::CACHE_TTL, function() {
 *     return $this->loadPrivileges();
 * });
 * ```
 */
class ControllerConstants
{
    // ========================================================================
    // Route Action Constants
    // ========================================================================
    
    /**
     * Index action - Display list of resources
     */
    public const ACTION_INDEX = 'index';
    
    /**
     * Create action - Show form to create new resource
     */
    public const ACTION_CREATE = 'create';
    
    /**
     * Store action - Store newly created resource
     */
    public const ACTION_STORE = 'store';
    
    /**
     * Show action - Display specific resource
     */
    public const ACTION_SHOW = 'show';
    
    /**
     * Edit action - Show form to edit resource
     */
    public const ACTION_EDIT = 'edit';
    
    /**
     * Update action - Update specific resource
     */
    public const ACTION_UPDATE = 'update';
    
    /**
     * Destroy action - Delete specific resource
     */
    public const ACTION_DESTROY = 'destroy';
    
    // ========================================================================
    // Page Type Constants
    // ========================================================================
    
    /**
     * Admin page type - Backend administration pages
     */
    public const PAGE_TYPE_ADMIN = 'adminpage';
    
    /**
     * Front page type - Frontend public pages
     */
    public const PAGE_TYPE_FRONT = 'frontpage';
    
    /**
     * Login page type - Authentication pages
     */
    public const PAGE_TYPE_LOGIN = 'login';
    
    // ========================================================================
    // Session Key Constants
    // ========================================================================
    
    /**
     * Session key for user ID
     */
    public const SESSION_USER_ID = 'id';
    
    /**
     * Session key for username
     */
    public const SESSION_USERNAME = 'username';
    
    /**
     * Session key for group ID
     */
    public const SESSION_GROUP_ID = 'group_id';
    
    /**
     * Session key for user group name
     */
    public const SESSION_USER_GROUP = 'user_group';
    
    /**
     * Session key for group information
     */
    public const SESSION_GROUP_INFO = 'group_info';
    
    /**
     * Session key for user full name
     */
    public const SESSION_FULLNAME = 'fullname';
    
    /**
     * Session key for user email
     */
    public const SESSION_EMAIL = 'email';
    
    /**
     * Session key for user phone
     */
    public const SESSION_PHONE = 'phone';
    
    /**
     * Session key for root user flag
     */
    public const SESSION_FLAG = 'flag';
    
    // ========================================================================
    // User Group Constants
    // ========================================================================
    
    /**
     * Root user group - Highest privilege level
     */
    public const GROUP_ROOT = 'root';
    
    /**
     * Admin user group - Administrative privileges
     */
    public const GROUP_ADMIN = 'admin';
    
    /**
     * Internal user group - Internal staff privileges
     */
    public const GROUP_INTERNAL = 'internal';
    
    // ========================================================================
    // File Type Constants
    // ========================================================================
    
    /**
     * Image file type
     */
    public const FILE_TYPE_IMAGE = 'image';
    
    /**
     * Generic file type
     */
    public const FILE_TYPE_FILE = 'file';
    
    /**
     * Document file type
     */
    public const FILE_TYPE_DOCUMENT = 'document';
    
    /**
     * Video file type
     */
    public const FILE_TYPE_VIDEO = 'video';
    
    /**
     * Audio file type
     */
    public const FILE_TYPE_AUDIO = 'audio';
    
    // ========================================================================
    // File Validation Constants
    // ========================================================================
    
    /**
     * Image file validation rule
     */
    public const FILE_VALIDATION_IMAGE = 'image';
    
    /**
     * Document file validation rule
     */
    public const FILE_VALIDATION_DOCUMENT = 'document';
    
    /**
     * Video file validation rule
     */
    public const FILE_VALIDATION_VIDEO = 'video';
    
    /**
     * Audio file validation rule
     */
    public const FILE_VALIDATION_AUDIO = 'audio';
    
    /**
     * Maximum file size validation (in KB)
     */
    public const FILE_VALIDATION_MAX_SIZE = 'max_size';
    
    /**
     * Allowed extensions validation
     */
    public const FILE_VALIDATION_EXTENSIONS = 'extensions';
    
    // ========================================================================
    // Script Position Constants
    // ========================================================================
    
    /**
     * Script position - Top of page (in <head>)
     */
    public const SCRIPT_POSITION_TOP = 'top';
    
    /**
     * Script position - Bottom of page (before </body>)
     */
    public const SCRIPT_POSITION_BOTTOM = 'bottom';
    
    /**
     * Script position - Last (after all other scripts)
     */
    public const SCRIPT_POSITION_LAST = 'last';
    
    // ========================================================================
    // DataTables Parameter Constants
    // ========================================================================
    
    /**
     * DataTables draw counter parameter
     */
    public const DT_PARAM_DRAW = 'draw';
    
    /**
     * DataTables pagination start parameter
     */
    public const DT_PARAM_START = 'start';
    
    /**
     * DataTables page length parameter
     */
    public const DT_PARAM_LENGTH = 'length';
    
    /**
     * DataTables search parameter
     */
    public const DT_PARAM_SEARCH = 'search';
    
    /**
     * DataTables order parameter
     */
    public const DT_PARAM_ORDER = 'order';
    
    /**
     * DataTables columns parameter
     */
    public const DT_PARAM_COLUMNS = 'columns';
    
    /**
     * DataTables search value key
     */
    public const DT_SEARCH_VALUE = 'value';
    
    /**
     * DataTables search regex key
     */
    public const DT_SEARCH_REGEX = 'regex';
    
    /**
     * DataTables column data key
     */
    public const DT_COLUMN_DATA = 'data';
    
    /**
     * DataTables column name key
     */
    public const DT_COLUMN_NAME = 'name';
    
    /**
     * DataTables column searchable key
     */
    public const DT_COLUMN_SEARCHABLE = 'searchable';
    
    /**
     * DataTables column orderable key
     */
    public const DT_COLUMN_ORDERABLE = 'orderable';
    
    // ========================================================================
    // Validation Status Constants
    // ========================================================================
    
    /**
     * Validation status - Success
     */
    public const VALIDATION_STATUS_SUCCESS = 'success';
    
    /**
     * Validation status - Failed
     */
    public const VALIDATION_STATUS_FAILED = 'failed';
    
    /**
     * Validation status - Error
     */
    public const VALIDATION_STATUS_ERROR = 'error';
    
    /**
     * Validation status - Pending
     */
    public const VALIDATION_STATUS_PENDING = 'pending';
    
    // ========================================================================
    // Cache Key Constants
    // ========================================================================
    
    /**
     * Cache key prefix for user privileges
     */
    public const CACHE_PRIVILEGE_PREFIX = 'privilege_';
    
    /**
     * Cache key prefix for route information
     */
    public const CACHE_ROUTE_INFO_PREFIX = 'route_info_';
    
    /**
     * Cache key for user preferences
     */
    public const CACHE_PREFERENCE_KEY = 'preferences';
    
    /**
     * Cache key prefix for module data
     */
    public const CACHE_MODULE_PREFIX = 'module_';
    
    /**
     * Cache key prefix for file validation results
     */
    public const CACHE_FILE_VALIDATION_PREFIX = 'file_validation_';
    
    /**
     * Default cache TTL in seconds (1 hour)
     */
    public const CACHE_TTL = 3600;
    
    /**
     * Short cache TTL in seconds (5 minutes)
     */
    public const CACHE_TTL_SHORT = 300;
    
    /**
     * Long cache TTL in seconds (24 hours)
     */
    public const CACHE_TTL_LONG = 86400;
    
    // ========================================================================
    // HTTP Method Constants
    // ========================================================================
    
    /**
     * HTTP GET method
     */
    public const HTTP_METHOD_GET = 'GET';
    
    /**
     * HTTP POST method
     */
    public const HTTP_METHOD_POST = 'POST';
    
    /**
     * HTTP PUT method
     */
    public const HTTP_METHOD_PUT = 'PUT';
    
    /**
     * HTTP DELETE method
     */
    public const HTTP_METHOD_DELETE = 'DELETE';
    
    /**
     * HTTP PATCH method
     */
    public const HTTP_METHOD_PATCH = 'PATCH';
    
    /**
     * HTTP OPTIONS method
     */
    public const HTTP_METHOD_OPTIONS = 'OPTIONS';
    
    /**
     * HTTP HEAD method
     */
    public const HTTP_METHOD_HEAD = 'HEAD';
    
    // ========================================================================
    // Button Color Constants
    // ========================================================================
    
    /**
     * Primary button color (blue)
     */
    public const BUTTON_COLOR_PRIMARY = 'primary';
    
    /**
     * Success button color (green)
     */
    public const BUTTON_COLOR_SUCCESS = 'success';
    
    /**
     * Info button color (light blue)
     */
    public const BUTTON_COLOR_INFO = 'info';
    
    /**
     * Warning button color (yellow/orange)
     */
    public const BUTTON_COLOR_WARNING = 'warning';
    
    /**
     * Danger button color (red)
     */
    public const BUTTON_COLOR_DANGER = 'danger';
    
    /**
     * Secondary button color (gray)
     */
    public const BUTTON_COLOR_SECONDARY = 'secondary';
    
    /**
     * Light button color
     */
    public const BUTTON_COLOR_LIGHT = 'light';
    
    /**
     * Dark button color
     */
    public const BUTTON_COLOR_DARK = 'dark';
	
	// ========================================================================
	// Configuration-Based Helper Methods
	// ========================================================================
	
	/**
	 * Get cache TTL from configuration
	 * 
	 * Returns the configured cache TTL or the default constant value.
	 * 
	 * @param string|null $type Cache type (privilege, route_info, preference, view, file_validation)
	 * @return int Cache TTL in seconds
	 * 
	 * @example
	 * ```php
	 * $ttl = ControllerConstants::getCacheTtl('privilege');
	 * Cache::remember($key, $ttl, function() { ... });
	 * ```
	 */
	public static function getCacheTtl(?string $type = null): int {
		if ($type !== null) {
			return ControllerConfig::getCacheTtl($type);
		}
		return ControllerConfig::get('performance.cache_ttl', self::CACHE_TTL);
	}
	
	/**
	 * Get allowed file extensions from configuration
	 * 
	 * Returns the configured allowed file extensions or default list.
	 * 
	 * @return array Array of allowed file extensions
	 * 
	 * @example
	 * ```php
	 * $allowed = ControllerConstants::getAllowedFileExtensions();
	 * if (in_array($extension, $allowed)) { ... }
	 * ```
	 */
	public static function getAllowedFileExtensions(): array {
		return ControllerConfig::getAllowedFileExtensions();
	}
	
	/**
	 * Get maximum file size from configuration
	 * 
	 * Returns the configured maximum file size in bytes.
	 * 
	 * @return int Maximum file size in bytes
	 * 
	 * @example
	 * ```php
	 * $maxSize = ControllerConstants::getMaxFileSize();
	 * if ($file->getSize() > $maxSize) { ... }
	 * ```
	 */
	public static function getMaxFileSize(): int {
		return ControllerConfig::getMaxFileSize();
	}
	
	/**
	 * Check if XSS protection is enabled
	 * 
	 * @return bool True if XSS protection is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isXssProtectionEnabled()) {
	 *     $output = htmlspecialchars($input);
	 * }
	 * ```
	 */
	public static function isXssProtectionEnabled(): bool {
		return ControllerConfig::isXssProtectionEnabled();
	}
	
	/**
	 * Check if CSRF protection is enabled
	 * 
	 * @return bool True if CSRF protection is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isCsrfProtectionEnabled()) {
	 *     // Verify CSRF token
	 * }
	 * ```
	 */
	public static function isCsrfProtectionEnabled(): bool {
		return ControllerConfig::isCsrfProtectionEnabled();
	}
	
	/**
	 * Check if caching is enabled
	 * 
	 * @return bool True if caching is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isCachingEnabled()) {
	 *     return Cache::remember($key, $ttl, $callback);
	 * }
	 * return $callback();
	 * ```
	 */
	public static function isCachingEnabled(): bool {
		return ControllerConfig::isCachingEnabled();
	}
	
	/**
	 * Check if a specific cache type is enabled
	 * 
	 * @param string $type Cache type (privilege, route_info, preference, view, file_validation)
	 * @return bool True if cache type is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isCacheTypeEnabled('privilege')) {
	 *     return Cache::remember($key, $ttl, $callback);
	 * }
	 * ```
	 */
	public static function isCacheTypeEnabled(string $type): bool {
		return ControllerConfig::isCacheTypeEnabled($type);
	}
	
	/**
	 * Check if filename sanitization is enabled
	 * 
	 * @return bool True if filename sanitization is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isSanitizeFilenamesEnabled()) {
	 *     $filename = $this->sanitizeFilename($filename);
	 * }
	 * ```
	 */
	public static function isSanitizeFilenamesEnabled(): bool {
		return ControllerConfig::isSanitizeFilenamesEnabled();
	}
	
	/**
	 * Check if security event logging is enabled
	 * 
	 * @return bool True if security event logging is enabled
	 * 
	 * @example
	 * ```php
	 * if (ControllerConstants::isSecurityLoggingEnabled()) {
	 *     Log::warning('XSS attempt detected', $context);
	 * }
	 * ```
	 */
	public static function isSecurityLoggingEnabled(): bool {
		return ControllerConfig::isSecurityLoggingEnabled();
	}
}

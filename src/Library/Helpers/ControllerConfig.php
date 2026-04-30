<?php

namespace Canvastack\Canvastack\Library\Helpers;

/**
 * Controller Configuration Helper
 * 
 * Provides easy access to controller configuration values with fallback defaults.
 * This helper ensures backward compatibility by providing sensible defaults when
 * configuration is not available.
 * 
 * @package Canvastack\Canvastack\Library\Helpers
 * @author CanvaStack Framework
 * @version 1.0.0
 * 
 * @example Basic usage:
 * ```php
 * use Canvastack\Canvastack\Library\Helpers\ControllerConfig;
 * 
 * // Check if XSS protection is enabled
 * if (ControllerConfig::get('security.xss_protection')) {
 *     // Escape output
 * }
 * 
 * // Get max file size with default
 * $maxSize = ControllerConfig::get('security.max_file_size', 10485760);
 * 
 * // Check if caching is enabled
 * if (ControllerConfig::isCachingEnabled()) {
 *     // Use cache
 * }
 * ```
 */
class ControllerConfig
{
    /**
     * Cached configuration array
     * 
     * @var array|null
     */
    private static $config = null;
    
    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Configuration key in dot notation (e.g., 'security.xss_protection')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     * 
     * @example
     * ```php
     * $xssEnabled = ControllerConfig::get('security.xss_protection', true);
     * $cacheTtl = ControllerConfig::get('performance.cache_ttl', 3600);
     * ```
     */
    public static function get(string $key, $default = null)
    {
        self::loadConfig();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key Configuration key in dot notation
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key): bool
    {
        self::loadConfig();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }
        
        return true;
    }
    
    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public static function all(): array
    {
        self::loadConfig();
        return self::$config;
    }
    
    /**
     * Load configuration from file
     * 
     * @return void
     */
    private static function loadConfig(): void
    {
        if (self::$config === null) {
            $configPath = config_path('canvastack.controller.php');
            
            if (file_exists($configPath)) {
                self::$config = require $configPath;
            } else {
                // Fallback to default configuration
                self::$config = self::getDefaultConfig();
            }
        }
    }
    
    /**
     * Get default configuration values
     * 
     * Provides sensible defaults for backward compatibility when
     * configuration file is not available.
     * 
     * @return array Default configuration
     */
    private static function getDefaultConfig(): array
    {
        return [
            'security' => [
                'xss_protection' => true,
                'csrf_protection' => true,
                'sql_injection_prevention' => true,
                'escape_output' => true,
                'allowed_file_extensions' => [
                    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                    'txt', 'csv', 'zip', 'rar',
                ],
                'max_file_size' => 10485760, // 10MB
                'sanitize_filenames' => true,
                'session_timeout' => 7200, // 2 hours
                'regenerate_session_id' => true,
            ],
            'performance' => [
                'enable_caching' => true,
                'cache_ttl' => 3600, // 1 hour
                'eager_loading' => true,
                'query_optimization' => true,
                'memory_limit' => '256M',
                'enable_query_monitoring' => true,
                'slow_query_threshold' => 1000, // 1 second
            ],
            'caching' => [
                'privilege_cache_enabled' => true,
                'privilege_cache_ttl' => 3600,
                'route_info_cache_enabled' => true,
                'route_info_cache_ttl' => 3600,
                'preference_cache_enabled' => true,
                'preference_cache_ttl' => 7200,
                'view_cache_enabled' => true,
                'view_cache_ttl' => 3600,
                'file_validation_cache_enabled' => true,
                'file_validation_cache_ttl' => 7200,
            ],
            'file_upload' => [
                'enable_chunking' => true,
                'chunk_size' => 1048576, // 1MB
                'enable_thumbnails' => true,
                'thumbnail_width' => 150,
                'thumbnail_height' => 150,
                'storage_path' => 'uploads',
                'generate_unique_filenames' => true,
                'image_quality' => 85,
                'max_image_width' => null,
                'max_image_height' => null,
            ],
            'validation' => [
                'strict_mode' => true,
                'validate_table_names' => true,
                'validate_column_names' => true,
                'max_query_length' => 10000,
                'validate_mime_types' => true,
                'validate_image_dimensions' => true,
                'max_array_depth' => 10,
                'max_input_variables' => 1000,
            ],
            'logging' => [
                'log_security_events' => true,
                'log_performance_issues' => true,
                'log_validation_failures' => true,
                'log_file_uploads' => true,
                'log_privilege_violations' => true,
                'log_database_queries' => false,
                'log_channel' => null,
                'log_level' => 'info',
            ],
            'datatables' => [
                'enable_post_method' => true,
                'max_records_per_page' => 1000,
                'default_page_length' => 10,
                'enable_search' => true,
                'enable_ordering' => true,
            ],
            'session' => [
                'validate_integrity' => true,
                'encrypt_sensitive_data' => true,
                'data_version' => 1,
            ],
        ];
    }
    
    /**
     * Check if XSS protection is enabled
     * 
     * @return bool True if XSS protection is enabled
     */
    public static function isXssProtectionEnabled(): bool
    {
        return (bool) self::get('security.xss_protection', true);
    }
    
    /**
     * Check if CSRF protection is enabled
     * 
     * @return bool True if CSRF protection is enabled
     */
    public static function isCsrfProtectionEnabled(): bool
    {
        return (bool) self::get('security.csrf_protection', true);
    }
    
    /**
     * Check if SQL injection prevention is enabled
     * 
     * @return bool True if SQL injection prevention is enabled
     */
    public static function isSqlInjectionPreventionEnabled(): bool
    {
        return (bool) self::get('security.sql_injection_prevention', true);
    }
    
    /**
     * Check if output escaping is enabled
     * 
     * @return bool True if output escaping is enabled
     */
    public static function isEscapeOutputEnabled(): bool
    {
        return (bool) self::get('security.escape_output', true);
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool True if caching is enabled
     */
    public static function isCachingEnabled(): bool
    {
        return (bool) self::get('performance.enable_caching', true);
    }
    
    /**
     * Get cache TTL for a specific cache type
     * 
     * @param string $type Cache type (privilege, route_info, preference, view, file_validation)
     * @return int Cache TTL in seconds
     */
    public static function getCacheTtl(string $type): int
    {
        $key = "caching.{$type}_cache_ttl";
        $default = self::get('performance.cache_ttl', 3600);
        return (int) self::get($key, $default);
    }
    
    /**
     * Check if a specific cache type is enabled
     * 
     * @param string $type Cache type (privilege, route_info, preference, view, file_validation)
     * @return bool True if cache type is enabled
     */
    public static function isCacheTypeEnabled(string $type): bool
    {
        if (!self::isCachingEnabled()) {
            return false;
        }
        
        $key = "caching.{$type}_cache_enabled";
        return (bool) self::get($key, true);
    }
    
    /**
     * Get allowed file extensions
     * 
     * @return array Array of allowed file extensions
     */
    public static function getAllowedFileExtensions(): array
    {
        return (array) self::get('security.allowed_file_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar',
        ]);
    }
    
    /**
     * Get maximum file size
     * 
     * @return int Maximum file size in bytes
     */
    public static function getMaxFileSize(): int
    {
        return (int) self::get('security.max_file_size', 10485760);
    }
    
    /**
     * Check if filename sanitization is enabled
     * 
     * @return bool True if filename sanitization is enabled
     */
    public static function isSanitizeFilenamesEnabled(): bool
    {
        return (bool) self::get('security.sanitize_filenames', true);
    }
    
    /**
     * Check if security event logging is enabled
     * 
     * @return bool True if security event logging is enabled
     */
    public static function isSecurityLoggingEnabled(): bool
    {
        return (bool) self::get('logging.log_security_events', true);
    }
    
    /**
     * Check if performance issue logging is enabled
     * 
     * @return bool True if performance issue logging is enabled
     */
    public static function isPerformanceLoggingEnabled(): bool
    {
        return (bool) self::get('logging.log_performance_issues', true);
    }
    
    /**
     * Check if validation failure logging is enabled
     * 
     * @return bool True if validation failure logging is enabled
     */
    public static function isValidationLoggingEnabled(): bool
    {
        return (bool) self::get('logging.log_validation_failures', true);
    }
    
    /**
     * Reload configuration from file
     * 
     * Useful for testing or when configuration changes at runtime.
     * 
     * @return void
     */
    public static function reload(): void
    {
        self::$config = null;
        self::loadConfig();
    }
}

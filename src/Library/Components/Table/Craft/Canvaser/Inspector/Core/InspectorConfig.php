<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core;

/**
 * Inspector Configuration Management
 *
 * Centralized configuration handling for the Inspector module.
 * Provides default values and configuration validation.
 *
 * @version 1.0.0
 */
class InspectorConfig
{
    /**
     * Default configuration values.
     */
    private const DEFAULTS = [
        'enabled' => null, // null means auto-detect based on environment
        'mode' => 'file',
        'storage_path' => 'datatable-inspector',
        'max_files' => 100,
        'cleanup_days' => 7,
        'max_file_size' => 10485760, // 10MB
        'include_trace' => true,
        'include_request_data' => true,
        'exclude_sensitive' => true,
        'format_json' => true,
        'compress_old_files' => false,
    ];

    /**
     * Check if Inspector is enabled.
     */
    public static function isEnabled(): bool
    {
        return FeatureFlag::inspectorEnabled();
    }

    /**
     * Get Inspector mode.
     */
    public static function getMode(): string
    {
        return self::get('mode');
    }

    /**
     * Get storage path for Inspector files.
     */
    public static function getStoragePath(): string
    {
        $path = self::get('storage_path');

        // Ensure it's a relative path from storage/app
        if (strpos($path, '/') === 0 || strpos($path, '\\') === 0) {
            $path = ltrim($path, '/\\');
        }

        return $path;
    }

    /**
     * Get full storage directory path.
     */
    public static function getFullStoragePath(): string
    {
        if (function_exists('storage_path')) {
            return storage_path('app/'.self::getStoragePath());
        }

        // Fallback for when Laravel helpers are not available
        return __DIR__.'/../../../../../../../../../../storage/app/'.self::getStoragePath();
    }

    /**
     * Get maximum number of files to keep.
     */
    public static function getMaxFiles(): int
    {
        return (int) self::get('max_files');
    }

    /**
     * Get number of days after which files should be cleaned up.
     */
    public static function getCleanupDays(): int
    {
        return (int) self::get('cleanup_days');
    }

    /**
     * Get maximum file size in bytes.
     */
    public static function getMaxFileSize(): int
    {
        return (int) self::get('max_file_size');
    }

    /**
     * Check if debug mode is enabled.
     */
    public static function isDebugMode(): bool
    {
        return FeatureFlag::isDebugMode();
    }

    /**
     * Check if stack trace should be included.
     */
    public static function shouldIncludeTrace(): bool
    {
        return (bool) self::get('include_trace');
    }

    /**
     * Check if request data should be included.
     */
    public static function shouldIncludeRequestData(): bool
    {
        return (bool) self::get('include_request_data');
    }

    /**
     * Check if sensitive data should be excluded.
     */
    public static function shouldExcludeSensitive(): bool
    {
        return (bool) self::get('exclude_sensitive');
    }

    /**
     * Check if JSON should be formatted for readability.
     */
    public static function shouldFormatJson(): bool
    {
        return (bool) self::get('format_json');
    }

    /**
     * Check if old files should be compressed.
     */
    public static function shouldCompressOldFiles(): bool
    {
        return (bool) self::get('compress_old_files');
    }

    /**
     * Get a configuration value.
     *
     * @param  string  $key Configuration key
     * @param  mixed  $default Default value if not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Try to get from Laravel config first
        if (function_exists('config')) {
            try {
                $configValue = config("canvastack.datatables.inspector.{$key}");
                if ($configValue !== null) {
                    return $configValue;
                }
            } catch (\Throwable $e) {
                // Config not available, continue with defaults
            }
        }

        // Fall back to environment variables
        $envKey = 'CANVASTACK_INSPECTOR_'.strtoupper($key);
        $envValue = getenv($envKey);
        if ($envValue !== false) {
            return self::castValue($envValue, $key);
        }

        // Use default value
        $defaultValue = self::DEFAULTS[$key] ?? $default;

        return $defaultValue;
    }

    /**
     * Get all configuration values.
     */
    public static function all(): array
    {
        $config = [];
        foreach (self::DEFAULTS as $key => $defaultValue) {
            $config[$key] = self::get($key, $defaultValue);
        }

        return $config;
    }

    /**
     * Cast environment variable value to appropriate type.
     *
     * @param  string  $value Environment variable value
     * @param  string  $key Configuration key
     * @return mixed
     */
    private static function castValue(string $value, string $key)
    {
        // Boolean values
        if (in_array($key, ['enabled', 'include_trace', 'include_request_data', 'exclude_sensitive', 'format_json', 'compress_old_files'])) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        // Integer values
        if (in_array($key, ['max_files', 'cleanup_days', 'max_file_size'])) {
            return (int) $value;
        }

        // String values
        return $value;
    }

    /**
     * Validate configuration values.
     *
     * @return array Array of validation errors (empty if valid)
     */
    public static function validate(): array
    {
        $errors = [];

        // Validate max_files
        $maxFiles = self::getMaxFiles();
        if ($maxFiles < 1 || $maxFiles > 10000) {
            $errors[] = "max_files must be between 1 and 10000, got: {$maxFiles}";
        }

        // Validate cleanup_days
        $cleanupDays = self::getCleanupDays();
        if ($cleanupDays < 1 || $cleanupDays > 365) {
            $errors[] = "cleanup_days must be between 1 and 365, got: {$cleanupDays}";
        }

        // Validate max_file_size
        $maxFileSize = self::getMaxFileSize();
        if ($maxFileSize < 1024 || $maxFileSize > 104857600) { // 1KB to 100MB
            $errors[] = "max_file_size must be between 1KB and 100MB, got: {$maxFileSize}";
        }

        // Validate storage path
        $storagePath = self::getStoragePath();
        if (empty($storagePath) || strpos($storagePath, '..') !== false) {
            $errors[] = "storage_path is invalid or contains dangerous characters: {$storagePath}";
        }

        return $errors;
    }

    /**
     * Get sensitive data patterns to exclude.
     */
    public static function getSensitivePatterns(): array
    {
        return [
            'password',
            'passwd',
            'secret',
            'token',
            'key',
            'api_key',
            'auth',
            'credential',
            'private',
            'confidential',
            '_token',
            'csrf',
            'session',
            'cookie',
        ];
    }
}

<?php

namespace Canvastack\Canvastack\Core\Craft\Includes;

use Illuminate\Support\Facades\Log;

/**
 * Safe Logger Helper
 * 
 * Provides environment-aware logging to prevent sensitive data exposure in production
 * and reduce logging overhead in production environments.
 */
class SafeLogger
{
    /**
     * Log debug information only in development environments
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debugOnly(string $message, array $context = []): void
    {
        if (self::isDevelopmentEnvironment()) {
            Log::debug($message, $context);
        }
    }

    /**
     * Log info with environment awareness
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function infoSafe(string $message, array $context = []): void
    {
        if (self::isDevelopmentEnvironment()) {
            Log::info($message, $context);
        } else {
            // In production, log only essential info without sensitive data
            Log::info($message, self::sanitizeContext($context));
        }
    }

    /**
     * Log debug information (alias for debugOnly for consistency)
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::debugOnly($message, $context);
    }

    /**
     * Log warning with environment awareness
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        if (self::isDevelopmentEnvironment()) {
            Log::warning($message, $context);
        } else {
            // In production, log warnings but sanitize sensitive data
            Log::warning($message, self::sanitizeContext($context));
        }
    }

    /**
     * Log info (alias for infoSafe for consistency)
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::infoSafe($message, $context);
    }

    /**
     * Check if current environment is development
     * 
     * @return bool
     */
    private static function isDevelopmentEnvironment(): bool
    {
        return app()->environment(['local', 'testing', 'development']);
    }

    /**
     * Sanitize context data for production logging
     * 
     * @param array $context
     * @return array
     */
    private static function sanitizeContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            // Skip sensitive data
            if (self::isSensitiveKey($key)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }
            
            // Keep only basic info
            if (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = '[COMPLEX_DATA]';
            }
        }
        
        return $sanitized;
    }

    /**
     * Check if key contains sensitive information
     * 
     * @param string $key
     * @return bool
     */
    private static function isSensitiveKey(string $key): bool
    {
        $sensitiveKeys = [
            'session_data',
            'password',
            'token',
            'secret',
            'key',
            'auth',
            'user_data',
            'credentials'
        ];

        $key = strtolower($key);
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (strpos($key, $sensitiveKey) !== false) {
                return true;
            }
        }

        return false;
    }
}
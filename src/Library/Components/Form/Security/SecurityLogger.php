<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

// use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    /**
     * Log security events with standardized format
     */
    public static function logSecurityEvent($event, $context = [])
    {
        $standardContext = [
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'session_id' => session()->getId(),
        ];

        $fullContext = array_merge($standardContext, $context);

        if (class_exists('\Log')) {
            \Log::channel('security')->warning("SECURITY_EVENT: {$event}", $fullContext);
        } else {
            // Fallback for testing
            error_log("SECURITY_EVENT: {$event} - " . json_encode($fullContext));
        }
    }

    /**
     * Log authorization failures
     */
    public static function logAuthorizationFailure($modelClass, $recordId, $action, $reason = null)
    {
        self::logSecurityEvent('AUTHORIZATION_FAILURE', [
            'model_class' => $modelClass,
            'record_id' => $recordId,
            'action' => $action,
            'reason' => $reason,
            'severity' => 'HIGH'
        ]);
    }

    /**
     * Log suspicious file upload attempts
     */
    public static function logSuspiciousFileUpload($filename, $mimeType, $reason)
    {
        self::logSecurityEvent('SUSPICIOUS_FILE_UPLOAD', [
            'filename' => $filename,
            'mime_type' => $mimeType,
            'reason' => $reason,
            'severity' => 'CRITICAL'
        ]);
    }

    /**
     * Log potential XSS attempts
     */
    public static function logXSSAttempt($input, $field = null)
    {
        self::logSecurityEvent('XSS_ATTEMPT', [
            'input' => substr($input, 0, 500), // Limit log size
            'field' => $field,
            'severity' => 'HIGH'
        ]);
    }

    /**
     * Log SQL injection attempts
     */
    public static function logSQLInjectionAttempt($query, $parameters = [])
    {
        self::logSecurityEvent('SQL_INJECTION_ATTEMPT', [
            'query' => substr($query, 0, 500),
            'parameters' => $parameters,
            'severity' => 'CRITICAL'
        ]);
    }

    /**
     * Log rate limiting violations
     */
    public static function logRateLimitViolation($key, $attempts)
    {
        self::logSecurityEvent('RATE_LIMIT_VIOLATION', [
            'key' => $key,
            'attempts' => $attempts,
            'severity' => 'MEDIUM'
        ]);
    }
}
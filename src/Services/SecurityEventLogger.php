<?php

namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Security Event Logger Service
 * 
 * Logs security-related events including XSS attempts, SQL injection attempts,
 * CSRF failures, privilege violations, and other security incidents.
 * 
 * @package Canvastack\Canvastack\Services
 */
class SecurityEventLogger
{
    /**
     * Log channel for security events
     */
    private string $logChannel;

    /**
     * Whether security logging is enabled
     */
    private bool $enabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logChannel = config('canvastack.controller.logging.log_channel', 'stack');
        $this->enabled = config('canvastack.controller.logging.log_security_events', true);
    }

    /**
     * Log XSS attempt
     * 
     * @param string $input The potentially malicious input
     * @param string $location Where the XSS attempt was detected
     * @param array $context Additional context
     * @return void
     */
    public function logXSSAttempt(string $input, string $location, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logSecurityEvent('xss_attempt', [
            'input' => $this->sanitizeForLog($input),
            'location' => $location,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log SQL injection attempt
     * 
     * @param string $query The potentially malicious query
     * @param string $location Where the SQL injection attempt was detected
     * @param array $context Additional context
     * @return void
     */
    public function logSQLInjectionAttempt(string $query, string $location, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logSecurityEvent('sql_injection_attempt', [
            'query' => $this->sanitizeForLog($query),
            'location' => $location,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log CSRF token failure
     * 
     * @param string $reason Reason for CSRF failure
     * @param array $context Additional context
     * @return void
     */
    public function logCSRFFailure(string $reason, array $context = []): void
    {
        if (!$this->enabled || !config('canvastack.controller.logging.log_csrf_failures', true)) {
            return;
        }

        $this->logSecurityEvent('csrf_failure', [
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log privilege violation
     * 
     * @param int|null $userId User ID attempting access
     * @param string $module Module being accessed
     * @param string $action Action being attempted
     * @param array $context Additional context
     * @return void
     */
    public function logPrivilegeViolation(?int $userId, string $module, string $action, array $context = []): void
    {
        if (!$this->enabled || !config('canvastack.controller.logging.log_privilege_violations', true)) {
            return;
        }

        $this->logSecurityEvent('privilege_violation', [
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log file upload security event
     * 
     * @param string $eventType Type of security event (invalid_type, file_too_large, malware_detected, etc.)
     * @param string $filename Original filename
     * @param array $context Additional context
     * @return void
     */
    public function logFileUploadSecurityEvent(string $eventType, string $filename, array $context = []): void
    {
        if (!$this->enabled || !config('canvastack.controller.logging.log_file_uploads', true)) {
            return;
        }

        $this->logSecurityEvent('file_upload_security', [
            'event_type' => $eventType,
            'filename' => $this->sanitizeForLog($filename),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
            'url' => Request::fullUrl(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log session security event
     * 
     * @param string $eventType Type of security event (expired, tampered, invalid, etc.)
     * @param array $context Additional context
     * @return void
     */
    public function logSessionSecurityEvent(string $eventType, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logSecurityEvent('session_security', [
            'event_type' => $eventType,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log validation failure
     * 
     * @param string $validationType Type of validation that failed
     * @param array $errors Validation errors
     * @param array $context Additional context
     * @return void
     */
    public function logValidationFailure(string $validationType, array $errors, array $context = []): void
    {
        if (!$this->enabled || !config('canvastack.controller.logging.log_validation_failures', true)) {
            return;
        }

        $this->logSecurityEvent('validation_failure', [
            'validation_type' => $validationType,
            'errors' => $errors,
            'ip_address' => Request::ip(),
            'user_id' => auth()->id(),
            'url' => Request::fullUrl(),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Log generic security event
     * 
     * @param string $eventType Type of security event
     * @param array $data Event data
     * @return void
     */
    private function logSecurityEvent(string $eventType, array $data): void
    {
        Log::channel($this->logChannel)->warning("Security Event: {$eventType}", $data);
    }

    /**
     * Sanitize input for logging (truncate long strings, remove sensitive data)
     * 
     * @param string $input Input to sanitize
     * @param int $maxLength Maximum length
     * @return string Sanitized input
     */
    private function sanitizeForLog(string $input, int $maxLength = 500): string
    {
        if (strlen($input) > $maxLength) {
            return substr($input, 0, $maxLength) . '... [truncated]';
        }
        return $input;
    }
}

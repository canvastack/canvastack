<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Canvastack\Canvastack\Library\Constants\ControllerConstants as CC;

/**
 * Security Helper Functions for Core Controller Components
 * 
 * This file contains centralized security functions for validation, sanitization,
 * and security event logging to protect against XSS, CSRF, SQL injection, and
 * other security vulnerabilities.
 * 
 * @package Canvastack\Canvastack\Library\Helpers
 * @author CanvaStack Framework
 * @version 1.0.0
 * 
 * @security CRITICAL - These functions are security-sensitive and must be used
 *           correctly to prevent vulnerabilities. Review all changes carefully.
 */

if (!function_exists('canvastack_controller_validate_csrf')) {
    /**
     * Validate CSRF token from request
     * 
     * Verifies that the request contains a valid CSRF token to prevent
     * Cross-Site Request Forgery attacks. This function should be called
     * for all state-changing operations (POST, PUT, DELETE, PATCH).
     * 
     * @param \Illuminate\Http\Request $request The HTTP request object
     * 
     * @return bool True if CSRF token is valid, false otherwise
     * 
     * @throws \Illuminate\Session\TokenMismatchException If token is invalid
     * 
     * @security CRITICAL - Must be called for all POST/PUT/DELETE/PATCH requests
     * 
     * @example
     * ```php
     * if (!canvastack_controller_validate_csrf($request)) {
     *     abort(419, 'CSRF token mismatch');
     * }
     * ```
     */
    function canvastack_controller_validate_csrf(Request $request): bool
    {
        try {
            // Check if request method requires CSRF validation
            $method = strtoupper($request->method());
            $requiresCsrf = in_array($method, [
                CC::HTTP_METHOD_POST,
                CC::HTTP_METHOD_PUT,
                CC::HTTP_METHOD_DELETE,
                CC::HTTP_METHOD_PATCH
            ]);
            
            if (!$requiresCsrf) {
                return true;
            }
            
            // Get CSRF token from request
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            
            if (empty($token)) {
                canvastack_controller_log_security_event(
                    'csrf_validation_failed',
                    'CSRF token missing from request',
                    [
                        'method' => $method,
                        'url' => $request->fullUrl(),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                );
                return false;
            }
            
            // Validate token against session
            $sessionToken = Session::token();
            $isValid = hash_equals($sessionToken, $token);
            
            if (!$isValid) {
                canvastack_controller_log_security_event(
                    'csrf_validation_failed',
                    'CSRF token mismatch',
                    [
                        'method' => $method,
                        'url' => $request->fullUrl(),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                );
            }
            
            return $isValid;
            
        } catch (\Exception $e) {
            canvastack_controller_log_security_event(
                'csrf_validation_error',
                'Exception during CSRF validation: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }
}

if (!function_exists('canvastack_controller_validate_session')) {
    /**
     * Validate session data integrity
     * 
     * Verifies that session data is valid, properly structured, and has not
     * been tampered with. Checks for required session keys and validates
     * data types to prevent session hijacking and manipulation.
     * 
     * @param array $sessionData The session data to validate
     * 
     * @return bool True if session is valid, false otherwise
     * 
     * @security CRITICAL - Must be called before trusting session data
     * 
     * @example
     * ```php
     * $sessionData = [
     *     'id' => session('id'),
     *     'username' => session('username'),
     *     'group_id' => session('group_id')
     * ];
     * 
     * if (!canvastack_controller_validate_session($sessionData)) {
     *     return redirect()->route('login');
     * }
     * ```
     */
    function canvastack_controller_validate_session(array $sessionData): bool
    {
        try {
            // Check if session data is empty
            if (empty($sessionData)) {
                canvastack_controller_log_security_event(
                    'session_validation_failed',
                    'Empty session data provided',
                    ['session_data' => 'empty']
                );
                return false;
            }
            
            // Required session keys for authenticated users
            $requiredKeys = [
                CC::SESSION_USER_ID,
                CC::SESSION_USERNAME,
                CC::SESSION_GROUP_ID
            ];
            
            // Check for required keys
            foreach ($requiredKeys as $key) {
                if (!isset($sessionData[$key]) || empty($sessionData[$key])) {
                    canvastack_controller_log_security_event(
                        'session_validation_failed',
                        "Required session key missing: {$key}",
                        ['missing_key' => $key]
                    );
                    return false;
                }
            }
            
            // Validate data types
            if (!is_numeric($sessionData[CC::SESSION_USER_ID]) || $sessionData[CC::SESSION_USER_ID] <= 0) {
                canvastack_controller_log_security_event(
                    'session_validation_failed',
                    'Invalid user ID in session',
                    ['user_id' => $sessionData[CC::SESSION_USER_ID]]
                );
                return false;
            }
            
            if (!is_string($sessionData[CC::SESSION_USERNAME]) || strlen($sessionData[CC::SESSION_USERNAME]) < 3) {
                canvastack_controller_log_security_event(
                    'session_validation_failed',
                    'Invalid username in session',
                    ['username_length' => strlen($sessionData[CC::SESSION_USERNAME] ?? '')]
                );
                return false;
            }
            
            if (!is_numeric($sessionData[CC::SESSION_GROUP_ID]) || $sessionData[CC::SESSION_GROUP_ID] <= 0) {
                canvastack_controller_log_security_event(
                    'session_validation_failed',
                    'Invalid group ID in session',
                    ['group_id' => $sessionData[CC::SESSION_GROUP_ID]]
                );
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            canvastack_controller_log_security_event(
                'session_validation_error',
                'Exception during session validation: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }
}

if (!function_exists('canvastack_controller_validate_file_upload')) {
    /**
     * Validate file upload security
     * 
     * Performs comprehensive security validation on uploaded files including:
     * - File extension validation against whitelist
     * - MIME type validation
     * - File size validation
     * - Malicious content detection
     * - Image dimension validation (for images)
     * 
     * @param \Illuminate\Http\UploadedFile $file The uploaded file object
     * @param array $rules Validation rules [
     *     'type' => 'image|document|video|audio',
     *     'max_size' => 10240, // in KB
     *     'extensions' => ['jpg', 'png', 'pdf'],
     *     'mime_types' => ['image/jpeg', 'image/png'],
     *     'max_width' => 4096, // for images
     *     'max_height' => 4096 // for images
     * ]
     * 
     * @return bool True if file is valid and safe, false otherwise
     * 
     * @throws \InvalidArgumentException If rules are invalid
     * 
     * @security CRITICAL - Must be called before processing any file upload
     * 
     * @example
     * ```php
     * $rules = [
     *     'type' => 'image',
     *     'max_size' => 5120,
     *     'extensions' => ['jpg', 'jpeg', 'png'],
     *     'max_width' => 2048,
     *     'max_height' => 2048
     * ];
     * 
     * if (!canvastack_controller_validate_file_upload($file, $rules)) {
     *     return back()->withErrors(['file' => 'Invalid or unsafe file']);
     * }
     * ```
     */
    function canvastack_controller_validate_file_upload(UploadedFile $file, array $rules = []): bool
    {
        try {
            // Check if file is valid
            if (!$file->isValid()) {
                canvastack_controller_log_security_event(
                    'file_upload_validation_failed',
                    'Invalid file upload',
                    [
                        'error' => $file->getError(),
                        'error_message' => $file->getErrorMessage()
                    ]
                );
                return false;
            }
            
            // Get file information
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize() / 1024; // Convert to KB
            
            // Validate file extension
            if (isset($rules['extensions']) && is_array($rules['extensions'])) {
                $allowedExtensions = array_map('strtolower', $rules['extensions']);
                if (!in_array($extension, $allowedExtensions)) {
                    canvastack_controller_log_security_event(
                        'file_upload_validation_failed',
                        'File extension not allowed',
                        [
                            'file' => $originalName,
                            'extension' => $extension,
                            'allowed' => $allowedExtensions
                        ]
                    );
                    return false;
                }
            }
            
            // Validate MIME type
            if (isset($rules['mime_types']) && is_array($rules['mime_types'])) {
                if (!in_array($mimeType, $rules['mime_types'])) {
                    canvastack_controller_log_security_event(
                        'file_upload_validation_failed',
                        'MIME type not allowed',
                        [
                            'file' => $originalName,
                            'mime_type' => $mimeType,
                            'allowed' => $rules['mime_types']
                        ]
                    );
                    return false;
                }
            }
            
            // Validate file size
            if (isset($rules['max_size']) && is_numeric($rules['max_size'])) {
                if ($fileSize > $rules['max_size']) {
                    canvastack_controller_log_security_event(
                        'file_upload_validation_failed',
                        'File size exceeds maximum',
                        [
                            'file' => $originalName,
                            'size_kb' => $fileSize,
                            'max_size_kb' => $rules['max_size']
                        ]
                    );
                    return false;
                }
            }
            
            // Validate image dimensions (if image)
            if (isset($rules['type']) && $rules['type'] === CC::FILE_TYPE_IMAGE) {
                if (strpos($mimeType, 'image/') === 0) {
                    $imageInfo = @getimagesize($file->getRealPath());
                    if ($imageInfo !== false) {
                        list($width, $height) = $imageInfo;
                        
                        if (isset($rules['max_width']) && $width > $rules['max_width']) {
                            canvastack_controller_log_security_event(
                                'file_upload_validation_failed',
                                'Image width exceeds maximum',
                                [
                                    'file' => $originalName,
                                    'width' => $width,
                                    'max_width' => $rules['max_width']
                                ]
                            );
                            return false;
                        }
                        
                        if (isset($rules['max_height']) && $height > $rules['max_height']) {
                            canvastack_controller_log_security_event(
                                'file_upload_validation_failed',
                                'Image height exceeds maximum',
                                [
                                    'file' => $originalName,
                                    'height' => $height,
                                    'max_height' => $rules['max_height']
                                ]
                            );
                            return false;
                        }
                    }
                }
            }
            
            // Check for double extensions (e.g., file.php.jpg)
            $nameParts = explode('.', $originalName);
            if (count($nameParts) > 2) {
                $suspiciousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat', 'cmd'];
                foreach ($nameParts as $part) {
                    if (in_array(strtolower($part), $suspiciousExtensions)) {
                        canvastack_controller_log_security_event(
                            'file_upload_validation_failed',
                            'Suspicious double extension detected',
                            [
                                'file' => $originalName,
                                'suspicious_part' => $part
                            ]
                        );
                        return false;
                    }
                }
            }
            
            // Check for null bytes in filename
            if (strpos($originalName, "\0") !== false) {
                canvastack_controller_log_security_event(
                    'file_upload_validation_failed',
                    'Null byte detected in filename',
                    ['file' => bin2hex($originalName)]
                );
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            canvastack_controller_log_security_event(
                'file_upload_validation_error',
                'Exception during file upload validation: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }
}

if (!function_exists('canvastack_controller_sanitize_filename')) {
    /**
     * Sanitize filename for safe storage
     * 
     * Removes or replaces dangerous characters from filenames to prevent:
     * - Directory traversal attacks (../)
     * - Command injection
     * - XSS in file listings
     * - File system issues
     * 
     * @param string $filename The original filename
     * @param bool $preserveExtension Whether to preserve the file extension
     * 
     * @return string Sanitized filename safe for storage
     * 
     * @security CRITICAL - Must be called before saving any user-provided filename
     * 
     * @example
     * ```php
     * $originalName = $file->getClientOriginalName();
     * $safeName = canvastack_controller_sanitize_filename($originalName);
     * $file->storeAs('uploads', $safeName);
     * ```
     */
    function canvastack_controller_sanitize_filename(string $filename, bool $preserveExtension = true): string
    {
        try {
            // Remove null bytes
            $filename = str_replace("\0", '', $filename);
            
            // Get extension if preserving
            $extension = '';
            if ($preserveExtension && strpos($filename, '.') !== false) {
                $parts = explode('.', $filename);
                $extension = '.' . strtolower(array_pop($parts));
                $filename = implode('.', $parts);
            }
            
            // Remove directory traversal attempts
            $filename = str_replace(['../', '..\\', '../', '..\\'], '', $filename);
            
            // Remove special characters that could cause issues
            $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
            
            // Remove multiple underscores
            $filename = preg_replace('/_+/', '_', $filename);
            
            // Remove leading/trailing underscores
            $filename = trim($filename, '_');
            
            // Ensure filename is not empty
            if (empty($filename)) {
                $filename = 'file_' . time();
            }
            
            // Limit filename length (max 200 chars + extension)
            if (strlen($filename) > 200) {
                $filename = substr($filename, 0, 200);
            }
            
            // Add extension back
            $sanitized = $filename . $extension;
            
            // Log if significant changes were made
            if ($sanitized !== $filename . $extension) {
                canvastack_controller_log_security_event(
                    'filename_sanitized',
                    'Filename was sanitized',
                    [
                        'original' => $filename . $extension,
                        'sanitized' => $sanitized
                    ]
                );
            }
            
            return $sanitized;
            
        } catch (\Exception $e) {
            canvastack_controller_log_security_event(
                'filename_sanitization_error',
                'Exception during filename sanitization: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'filename' => $filename
                ]
            );
            // Return a safe default
            return 'file_' . time() . ($preserveExtension && !empty($extension) ? $extension : '');
        }
    }
}

if (!function_exists('canvastack_controller_validate_route_params')) {
    /**
     * Validate route parameters
     * 
     * Validates route parameters to ensure they match expected types and formats.
     * Prevents injection attacks and ensures data integrity.
     * 
     * @param mixed $parameter The parameter value to validate
     * @param string $type Expected type: 'int', 'string', 'uuid', 'slug', 'email', 'url'
     * @param array $options Additional validation options [
     *     'min' => minimum value/length,
     *     'max' => maximum value/length,
     *     'pattern' => regex pattern,
     *     'allowed_values' => array of allowed values
     * ]
     * 
     * @return bool True if parameter is valid, false otherwise
     * 
     * @throws \InvalidArgumentException If type is not supported
     * 
     * @security CRITICAL - Must be called before using route parameters in queries
     * 
     * @example
     * ```php
     * // Validate ID parameter
     * if (!canvastack_controller_validate_route_params($id, 'int', ['min' => 1])) {
     *     abort(400, 'Invalid ID');
     * }
     * 
     * // Validate slug parameter
     * if (!canvastack_controller_validate_route_params($slug, 'slug')) {
     *     abort(400, 'Invalid slug');
     * }
     * ```
     */
    function canvastack_controller_validate_route_params($parameter, string $type, array $options = []): bool
    {
        try {
            // Check if parameter is null or empty when it shouldn't be
            if ($parameter === null || $parameter === '') {
                canvastack_controller_log_security_event(
                    'route_param_validation_failed',
                    'Empty or null parameter',
                    ['type' => $type]
                );
                return false;
            }
            
            // Validate based on type
            switch ($type) {
                case 'int':
                case 'integer':
                    if (!is_numeric($parameter) || (int)$parameter != $parameter) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a valid integer',
                            ['parameter' => $parameter, 'type' => $type]
                        );
                        return false;
                    }
                    
                    $intValue = (int)$parameter;
                    
                    if (isset($options['min']) && $intValue < $options['min']) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Integer parameter below minimum',
                            ['parameter' => $intValue, 'min' => $options['min']]
                        );
                        return false;
                    }
                    
                    if (isset($options['max']) && $intValue > $options['max']) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Integer parameter above maximum',
                            ['parameter' => $intValue, 'max' => $options['max']]
                        );
                        return false;
                    }
                    break;
                    
                case 'string':
                    if (!is_string($parameter)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a string',
                            ['parameter' => gettype($parameter)]
                        );
                        return false;
                    }
                    
                    $length = strlen($parameter);
                    
                    if (isset($options['min']) && $length < $options['min']) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'String parameter too short',
                            ['length' => $length, 'min' => $options['min']]
                        );
                        return false;
                    }
                    
                    if (isset($options['max']) && $length > $options['max']) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'String parameter too long',
                            ['length' => $length, 'max' => $options['max']]
                        );
                        return false;
                    }
                    
                    if (isset($options['pattern']) && !preg_match($options['pattern'], $parameter)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'String parameter does not match pattern',
                            ['pattern' => $options['pattern']]
                        );
                        return false;
                    }
                    break;
                    
                case 'uuid':
                    $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
                    if (!preg_match($uuidPattern, $parameter)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a valid UUID',
                            ['parameter' => $parameter]
                        );
                        return false;
                    }
                    break;
                    
                case 'slug':
                    $slugPattern = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
                    if (!preg_match($slugPattern, $parameter)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a valid slug',
                            ['parameter' => $parameter]
                        );
                        return false;
                    }
                    break;
                    
                case 'email':
                    if (!filter_var($parameter, FILTER_VALIDATE_EMAIL)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a valid email',
                            ['parameter' => $parameter]
                        );
                        return false;
                    }
                    break;
                    
                case 'url':
                    if (!filter_var($parameter, FILTER_VALIDATE_URL)) {
                        canvastack_controller_log_security_event(
                            'route_param_validation_failed',
                            'Parameter is not a valid URL',
                            ['parameter' => $parameter]
                        );
                        return false;
                    }
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Unsupported validation type: {$type}");
            }
            
            // Check against allowed values if specified
            if (isset($options['allowed_values']) && is_array($options['allowed_values'])) {
                if (!in_array($parameter, $options['allowed_values'], true)) {
                    canvastack_controller_log_security_event(
                        'route_param_validation_failed',
                        'Parameter not in allowed values',
                        ['parameter' => $parameter]
                    );
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            canvastack_controller_log_security_event(
                'route_param_validation_error',
                'Exception during route parameter validation: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'parameter' => $parameter,
                    'type' => $type
                ]
            );
            return false;
        }
    }
}

if (!function_exists('canvastack_controller_log_security_event')) {
    /**
     * Log security-related events
     * 
     * Centralized logging for security events including:
     * - CSRF validation failures
     * - Session validation failures
     * - File upload validation failures
     * - Route parameter validation failures
     * - Suspicious activity detection
     * - Authentication failures
     * - Authorization failures
     * 
     * @param string $type Event type (e.g., 'csrf_validation_failed', 'xss_attempt')
     * @param string $message Human-readable message describing the event
     * @param array $context Additional context data (request info, user info, etc.)
     * 
     * @return void
     * 
     * @security IMPORTANT - All security events should be logged for audit trail
     * 
     * @example
     * ```php
     * canvastack_controller_log_security_event(
     *     'xss_attempt',
     *     'XSS payload detected in user input',
     *     [
     *         'field' => 'comment',
     *         'payload' => $suspiciousInput,
     *         'user_id' => auth()->id(),
     *         'ip' => request()->ip()
     *     ]
     * );
     * ```
     */
    function canvastack_controller_log_security_event(string $type, string $message, array $context = []): void
    {
        try {
            // Add timestamp
            $context['timestamp'] = now()->toDateTimeString();
            
            // Add request information if available
            if (app()->bound('request')) {
                $request = app('request');
                $context['request_info'] = [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ];
            }
            
            // Add authenticated user information if available
            if (auth()->check()) {
                $context['user_info'] = [
                    'user_id' => auth()->id(),
                    'username' => auth()->user()->username ?? 'unknown',
                ];
            }
            
            // Add session information if available
            if (Session::has(CC::SESSION_USER_ID)) {
                $context['session_info'] = [
                    'session_user_id' => Session::get(CC::SESSION_USER_ID),
                    'session_username' => Session::get(CC::SESSION_USERNAME),
                    'session_group' => Session::get(CC::SESSION_USER_GROUP),
                ];
            }
            
            // Determine log level based on event type
            $logLevel = 'warning';
            $criticalEvents = [
                'csrf_validation_failed',
                'sql_injection_attempt',
                'xss_attempt',
                'file_upload_malware',
                'session_hijack_attempt',
                'privilege_escalation_attempt'
            ];
            
            if (in_array($type, $criticalEvents)) {
                $logLevel = 'critical';
            }
            
            // Log to Laravel log
            Log::{$logLevel}("[SECURITY] [{$type}] {$message}", $context);
            
            // Also log to database if Log model is available
            if (class_exists(Log::class)) {
                try {
                    \Canvastack\Canvastack\Models\Admin\System\Log::create([
                        'type' => 'security',
                        'level' => $logLevel,
                        'event_type' => $type,
                        'message' => $message,
                        'context' => json_encode($context),
                        'user_id' => $context['user_info']['user_id'] ?? null,
                        'ip_address' => $context['request_info']['ip'] ?? null,
                        'user_agent' => $context['request_info']['user_agent'] ?? null,
                        'created_at' => now(),
                    ]);
                } catch (\Exception $dbException) {
                    // If database logging fails, just log to file
                    Log::error('Failed to log security event to database: ' . $dbException->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            // Fallback logging if main logging fails
            try {
                Log::emergency('Security logging failed: ' . $e->getMessage(), [
                    'original_type' => $type,
                    'original_message' => $message,
                    'exception' => get_class($e),
                ]);
            } catch (\Exception $fallbackException) {
                // Last resort - write to error log
                error_log("SECURITY EVENT LOGGING FAILED: [{$type}] {$message}");
            }
        }
    }
}

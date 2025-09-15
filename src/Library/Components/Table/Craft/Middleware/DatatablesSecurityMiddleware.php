<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Canvastack\Canvastack\Library\Components\Table\Craft\Security\SecurityInputValidator;
use Canvastack\Canvastack\Library\Components\Table\Craft\Security\SecurityMonitoringService;
use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

/**
 * DatatablesSecurityMiddleware
 * 
 * Comprehensive security middleware for Canvastack Datatables
 * Implements rate limiting, malicious pattern detection, security logging
 * 
 * @package Canvastack\Table\Middleware
 * @version 2.0
 * @author Security Hardening Team
 */
class DatatablesSecurityMiddleware
{
    /**
     * Rate limiting configuration
     */
    private const RATE_LIMIT_MAX_ATTEMPTS = 100;  // requests per minute
    private const RATE_LIMIT_DECAY_MINUTES = 1;
    private const STRICT_RATE_LIMIT = 20;  // for suspicious requests
    
    /**
     * Malicious patterns for detection
     */
    private const MALICIOUS_PATTERNS = [
        // SQL Injection patterns
        '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
        '/(\s|^)(or|and)\s+\d+\s*=\s*\d+/i',
        '/--\s*.*$/m',
        '/\/\*.*\*\//s',
        '/;\s*(union|select|insert|update|delete)/i',
        
        // XSS patterns
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe\b[^>]*>/i',
        
        // Path traversal patterns
        '/\.\.\//',
        '/\.\.\\\\/',
        '/\0/',
        
        // Command injection patterns
        '/[;&|`$(){}]/i',
        '/(exec|system|shell_exec|passthru|eval)/i',
        
        // File inclusion patterns
        '/(include|require|include_once|require_once)\s*\(/i',
    ];
    
    /**
     * Suspicious User-Agent patterns
     */
    private const SUSPICIOUS_USER_AGENTS = [
        '/sqlmap/i',
        '/nikto/i',
        '/w3af/i',
        '/acunetix/i',
        '/nessus/i',
        '/openvas/i',
        '/burp/i',
        '/havij/i',
        '/zmeu/i'
    ];
    
    /**
     * Security input validator
     */
    private SecurityInputValidator $validator;
    
    /**
     * Security monitoring service
     */
    private SecurityMonitoringService $monitor;
    
    public function __construct()
    {
        $this->validator = new SecurityInputValidator();
        $this->monitor = new SecurityMonitoringService();
    }
    
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws SecurityException
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        try {
            // 1. Check if request is for datatables
            if (!$this->isDatatablesRequest($request)) {
                return $next($request);
            }
            
            // 2. Rate limiting check
            $this->checkRateLimit($request);
            
            // 3. Validate User-Agent
            $this->validateUserAgent($request);
            
            // 4. Check for malicious patterns
            $this->detectMaliciousPatterns($request);
            
            // 5. Validate input data
            $this->validateInputData($request);
            
            // 6. Log security event
            $this->logSecurityEvent($request, 'request_validated', [
                'validation_time' => microtime(true) - $startTime
            ]);
            
            // Process the request
            $response = $next($request);
            
            // 7. Post-process security checks
            $this->postProcessSecurity($request, $response);
            
            return $response;
            
        } catch (SecurityException $e) {
            return $this->handleSecurityViolation($request, $e);
        } catch (\Exception $e) {
            $this->logSecurityEvent($request, 'middleware_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if request is for datatables
     *
     * @param Request $request
     * @return bool
     */
    private function isDatatablesRequest(Request $request): bool
    {
        // Check for datatables specific parameters
        $datatablesParams = ['draw', 'start', 'length', 'search', 'order', 'columns'];
        
        foreach ($datatablesParams as $param) {
            if ($request->has($param)) {
                return true;
            }
        }
        
        // Check for canvastack specific headers or parameters
        return $request->hasHeader('X-Datatables-Request') ||
               $request->has('canvastack_action') ||
               str_contains($request->url(), '/datatables/') ||
               str_contains($request->url(), '/canvastack/');
    }
    
    /**
     * Check rate limiting
     *
     * @param Request $request
     * @throws SecurityException
     */
    private function checkRateLimit(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        $isSuspicious = $this->isSuspiciousRequest($request);
        
        $maxAttempts = $isSuspicious ? self::STRICT_RATE_LIMIT : self::RATE_LIMIT_MAX_ATTEMPTS;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            $this->logSecurityEvent($request, 'rate_limit_exceeded', [
                'rate_limit_key' => $key,
                'max_attempts' => $maxAttempts,
                'retry_after' => $retryAfter,
                'is_suspicious' => $isSuspicious
            ]);
            
            throw new SecurityException('Rate limit exceeded', [
                'retry_after' => $retryAfter,
                'max_attempts' => $maxAttempts
            ]);
        }
        
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_MINUTES * 60);
    }
    
    /**
     * Validate User-Agent
     *
     * @param Request $request
     * @throws SecurityException
     */
    private function validateUserAgent(Request $request): void
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent)) {
            $this->logSecurityEvent($request, 'empty_user_agent', []);
            // Don't block empty user agents, just log for monitoring
            return;
        }
        
        // Check for suspicious patterns
        foreach (self::SUSPICIOUS_USER_AGENTS as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $this->logSecurityEvent($request, 'suspicious_user_agent', [
                    'user_agent' => $userAgent,
                    'pattern' => $pattern
                ]);
                
                throw new SecurityException('Suspicious User-Agent detected', [
                    'user_agent' => substr($userAgent, 0, 100)
                ]);
            }
        }
    }
    
    /**
     * Detect malicious patterns in request
     *
     * @param Request $request
     * @throws SecurityException
     */
    private function detectMaliciousPatterns(Request $request): void
    {
        // Check URL
        $this->scanForMaliciousPatterns($request->fullUrl(), 'url', $request);
        
        // Check all input data
        $inputData = $request->all();
        $this->scanInputRecursive($inputData, $request);
        
        // Check headers for suspicious content
        foreach ($request->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $this->scanForMaliciousPatterns($value, "header_{$name}", $request);
            }
        }
    }
    
    /**
     * Validate input data comprehensively
     *
     * @param Request $request
     * @throws SecurityException
     */
    private function validateInputData(Request $request): void
    {
        $inputData = $request->all();
        
        // Validate table name if present
        if ($request->has('table') || $request->has('tableName')) {
            $tableName = $request->input('table', $request->input('tableName'));
            $this->validator->validateTableName($tableName);
        }
        
        // Validate columns if present
        if ($request->has('columns')) {
            $columns = $request->input('columns');
            if (is_array($columns)) {
                foreach ($columns as $column) {
                    if (isset($column['name'])) {
                        $this->validator->validateColumnName($column['name']);
                    }
                    if (isset($column['data'])) {
                        $this->validator->validateColumnName($column['data']);
                    }
                }
            }
        }
        
        // Validate order columns
        if ($request->has('order')) {
            $orderData = $request->input('order');
            if (is_array($orderData)) {
                foreach ($orderData as $order) {
                    if (isset($order['column'])) {
                        // Column index should be numeric
                        if (!is_numeric($order['column'])) {
                            throw new SecurityException('Invalid order column index', [
                                'column' => $order['column']
                            ]);
                        }
                    }
                    if (isset($order['dir'])) {
                        $this->validator->sanitizeValue($order['dir'], 'order');
                    }
                }
            }
        }
        
        // Validate search values
        if ($request->has('search')) {
            $searchData = $request->input('search');
            if (is_array($searchData) && isset($searchData['value'])) {
                $this->validator->sanitizeValue($searchData['value'], 'search');
            }
        }
        
        // Validate pagination parameters
        if ($request->has('start')) {
            $start = $request->input('start');
            if (!is_numeric($start) || $start < 0) {
                throw new SecurityException('Invalid start parameter', [
                    'start' => $start
                ]);
            }
        }
        
        if ($request->has('length')) {
            $length = $request->input('length');
            if (!is_numeric($length) || $length < -1 || $length > 1000) {
                throw new SecurityException('Invalid length parameter', [
                    'length' => $length
                ]);
            }
        }
    }
    
    /**
     * Scan input data recursively for malicious patterns
     *
     * @param mixed $data
     * @param Request $request
     * @param string $path
     * @throws SecurityException
     */
    private function scanInputRecursive($data, Request $request, string $path = 'input'): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path . '.' . $key;
                $this->scanForMaliciousPatterns((string) $key, $currentPath . '_key', $request);
                $this->scanInputRecursive($value, $request, $currentPath);
            }
        } else {
            $this->scanForMaliciousPatterns((string) $data, $path, $request);
        }
    }
    
    /**
     * Scan for malicious patterns
     *
     * @param string $input
     * @param string $context
     * @param Request $request
     * @throws SecurityException
     */
    private function scanForMaliciousPatterns(string $input, string $context, Request $request): void
    {
        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input, $matches)) {
                $this->logSecurityEvent($request, 'malicious_pattern_detected', [
                    'input' => substr($input, 0, 200),
                    'context' => $context,
                    'pattern' => $pattern,
                    'match' => $matches[0] ?? null
                ]);
                
                throw new SecurityException("Malicious pattern detected in {$context}", [
                    'context' => $context,
                    'pattern_type' => $this->getPatternType($pattern)
                ]);
            }
        }
    }
    
    /**
     * Get rate limit key for request
     *
     * @param Request $request
     * @return string
     */
    private function getRateLimitKey(Request $request): string
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $userId = auth()->id();
        
        // Create composite key for more granular rate limiting
        if ($userId) {
            return "datatables_rate_limit:user:{$userId}";
        }
        
        return "datatables_rate_limit:ip:{$ip}:" . md5($userAgent ?? '');
    }
    
    /**
     * Check if request is suspicious
     *
     * @param Request $request
     * @return bool
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $suspiciousIndicators = 0;
        
        // Check for empty or suspicious User-Agent
        $userAgent = $request->userAgent();
        if (empty($userAgent)) {
            $suspiciousIndicators++;
        }
        
        foreach (self::SUSPICIOUS_USER_AGENTS as $pattern) {
            if ($userAgent && preg_match($pattern, $userAgent)) {
                $suspiciousIndicators += 3;
                break;
            }
        }
        
        // Check for unusual request frequency from this IP
        $recentRequestCount = Cache::get("request_count:" . $request->ip(), 0);
        if ($recentRequestCount > 50) {
            $suspiciousIndicators++;
        }
        
        // Check for suspicious parameters
        $inputData = $request->all();
        if ($this->containsSuspiciousData($inputData)) {
            $suspiciousIndicators += 2;
        }
        
        return $suspiciousIndicators >= 2;
    }
    
    /**
     * Check if data contains suspicious patterns
     *
     * @param mixed $data
     * @return bool
     */
    private function containsSuspiciousData($data): bool
    {
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->containsSuspiciousData($value)) {
                    return true;
                }
            }
            return false;
        }
        
        $stringData = (string) $data;
        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $stringData)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get pattern type for logging
     *
     * @param string $pattern
     * @return string
     */
    private function getPatternType(string $pattern): string
    {
        if (str_contains($pattern, 'union|select|insert')) {
            return 'sql_injection';
        }
        if (str_contains($pattern, 'script|javascript')) {
            return 'xss';
        }
        if (str_contains($pattern, '\\.\\.\\/')) {
            return 'path_traversal';
        }
        if (str_contains($pattern, 'exec|system')) {
            return 'command_injection';
        }
        return 'unknown';
    }
    
    /**
     * Post-process security checks
     *
     * @param Request $request
     * @param Response $response
     */
    private function postProcessSecurity(Request $request, $response): void
    {
        // Check response size for potential data exfiltration
        if ($response instanceof Response) {
            $contentLength = strlen($response->getContent());
            if ($contentLength > 10 * 1024 * 1024) { // 10MB
                $this->logSecurityEvent($request, 'large_response_size', [
                    'content_length' => $contentLength,
                    'warning' => 'Potential data exfiltration attempt'
                ]);
            }
        }
        
        // Update request tracking
        $this->updateRequestTracking($request);
    }
    
    /**
     * Update request tracking for analytics
     *
     * @param Request $request
     */
    private function updateRequestTracking(Request $request): void
    {
        $ip = $request->ip();
        $cacheKey = "request_count:{$ip}";
        
        $currentCount = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentCount + 1, now()->addMinutes(5));
    }
    
    /**
     * Handle security violation
     *
     * @param Request $request
     * @param SecurityException $e
     * @return Response
     */
    private function handleSecurityViolation(Request $request, SecurityException $e): Response
    {
        $this->logSecurityEvent($request, 'security_violation_blocked', [
            'exception_message' => $e->getMessage(),
            'exception_context' => $e->getContext(),
            'action' => 'request_blocked'
        ]);
        
        // Increment violation counter for this IP
        $violationKey = "security_violations:" . $request->ip();
        $violations = Cache::get($violationKey, 0) + 1;
        Cache::put($violationKey, $violations, now()->addHours(24));
        
        // Return appropriate error response
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Security violation detected',
                'message' => 'Your request has been blocked due to security policy violations.',
                'code' => 'SECURITY_VIOLATION'
            ], 403);
        }
        
        return response('Security violation detected. Access denied.', 403);
    }
    
    /**
     * Log security event
     *
     * @param Request $request
     * @param string $eventType
     * @param array $context
     */
    private function logSecurityEvent(Request $request, string $eventType, array $context = []): void
    {
        $logContext = array_merge([
            'event_type' => $eventType,
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'url' => $request->url(),
            'method' => $request->method(),
            'request_id' => $request->header('X-Request-ID', uniqid())
        ], $context);
        
        // Log to security channel
        Log::channel('security')->info("Datatables security middleware: {$eventType}", $logContext);
        
        // Send to monitoring service if available
        if (isset($this->monitor)) {
            $this->monitor->logSecurityEvent($eventType, $logContext);
        }
    }
}
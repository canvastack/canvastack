<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * SQL Injection Attempt Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when a potential SQL injection attack is detected.
 * This occurs when user input contains suspicious SQL patterns or when
 * query validation detects potentially malicious SQL constructs.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Security
 * @version 1.0.0
 * 
 * @security CRITICAL - SQL injection attempts indicate malicious intent
 *           All attempts should be logged and database access monitored
 * 
 * @example
 * ```php
 * // In query validation
 * if ($this->detectSqlInjectionPattern($input)) {
 *     throw new SQLInjectionAttemptException(
 *         'SQL injection pattern detected',
 *         [
 *             'field' => 'search',
 *             'input' => $input,
 *             'pattern' => 'UNION SELECT',
 *             'query' => $query,
 *             'user_id' => auth()->id()
 *         ]
 *     );
 * }
 * ```
 */
class SQLInjectionAttemptException extends ControllerSecurityException
{
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including suspicious query
     * @param int $code HTTP status code (default: 403 Forbidden)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'SQL injection attempt detected',
        array $context = [],
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->threatType = 'sql_injection';
        $this->userMessage = 'Your input contains potentially harmful content and has been blocked. Please use only alphanumeric characters and try again.';
    }
    
    /**
     * Get SQL injection attempt details
     * 
     * Returns specific details about the SQL injection attempt including the
     * suspicious input, detected patterns, and affected query.
     * 
     * @return array SQL injection attempt details
     */
    public function getSqlInjectionDetails(): array
    {
        return [
            'field' => $this->context['field'] ?? 'unknown',
            'input' => $this->sanitizeForLog($this->context['input'] ?? ''),
            'pattern' => $this->context['pattern'] ?? 'unknown',
            'query' => $this->sanitizeQuery($this->context['query'] ?? ''),
            'user_id' => $this->context['user_id'] ?? null,
            'detection_method' => $this->context['detection_method'] ?? 'pattern_match',
            'table' => $this->context['table'] ?? 'unknown',
        ];
    }
    
    /**
     * Sanitize input for logging
     * 
     * Removes potentially harmful content from input before logging.
     * 
     * @param string $input Raw input string
     * @return string Sanitized input safe for logging
     */
    private function sanitizeForLog(string $input): string
    {
        // Truncate long inputs
        if (strlen($input) > 200) {
            $input = substr($input, 0, 200) . '... [truncated]';
        }
        
        // Remove null bytes and control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        
        return $input;
    }
    
    /**
     * Sanitize query for logging
     * 
     * Removes sensitive data from query before logging.
     * 
     * @param string $query SQL query string
     * @return string Sanitized query safe for logging
     */
    private function sanitizeQuery(string $query): string
    {
        // Truncate long queries
        if (strlen($query) > 500) {
            $query = substr($query, 0, 500) . '... [truncated]';
        }
        
        // Mask potential passwords or sensitive data
        $query = preg_replace('/password\s*=\s*[\'"][^\'"]*[\'"]/i', 'password=***', $query);
        $query = preg_replace('/token\s*=\s*[\'"][^\'"]*[\'"]/i', 'token=***', $query);
        
        return $query;
    }
    
    /**
     * Get detected SQL patterns
     * 
     * Returns list of suspicious SQL patterns detected in the input.
     * 
     * @return array List of detected patterns
     */
    public function getDetectedPatterns(): array
    {
        $input = strtoupper($this->context['input'] ?? '');
        $patterns = [];
        
        $suspiciousPatterns = [
            'UNION SELECT' => 'Union-based injection',
            'OR 1=1' => 'Boolean-based injection',
            'DROP TABLE' => 'Destructive command',
            'DELETE FROM' => 'Destructive command',
            'INSERT INTO' => 'Data manipulation',
            'UPDATE ' => 'Data manipulation',
            '--' => 'Comment injection',
            ';' => 'Query stacking',
            'EXEC(' => 'Command execution',
            'EXECUTE(' => 'Command execution',
        ];
        
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (strpos($input, $pattern) !== false) {
                $patterns[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Get recommended action
     * 
     * Returns recommended security action based on the severity of the SQL injection attempt.
     * 
     * @return string Recommended action (e.g., 'log', 'block', 'alert_admin', 'lock_account')
     */
    public function getRecommendedAction(): string
    {
        $patterns = $this->getDetectedPatterns();
        
        // Check for destructive patterns
        foreach ($patterns as $pattern) {
            if (in_array($pattern['description'], ['Destructive command', 'Command execution'])) {
                return 'lock_account';
            }
        }
        
        // Multiple patterns indicate sophisticated attack
        if (count($patterns) > 2) {
            return 'alert_admin';
        }
        
        return 'block';
    }
}

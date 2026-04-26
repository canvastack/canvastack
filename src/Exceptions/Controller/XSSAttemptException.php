<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * XSS Attempt Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when a potential Cross-Site Scripting (XSS) attack is detected.
 * This occurs when user input contains suspicious HTML/JavaScript patterns that
 * could be used to inject malicious scripts.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Security
 * @version 1.0.0
 * 
 * @security CRITICAL - XSS attempts indicate malicious intent
 *           All XSS attempts should be logged and user activity monitored
 * 
 * @example
 * ```php
 * // In input validation
 * if ($this->detectXssPattern($input)) {
 *     throw new XSSAttemptException(
 *         'XSS pattern detected in user input',
 *         [
 *             'field' => 'comment',
 *             'input' => $input,
 *             'pattern' => '<script>',
 *             'user_id' => auth()->id()
 *         ]
 *     );
 * }
 * ```
 */
class XSSAttemptException extends ControllerSecurityException
{
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including suspicious input
     * @param int $code HTTP status code (default: 403 Forbidden)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'XSS attempt detected',
        array $context = [],
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->threatType = 'xss';
        $this->userMessage = 'Your input contains potentially harmful content and has been blocked. Please remove any HTML or script tags and try again.';
    }
    
    /**
     * Get XSS attempt details
     * 
     * Returns specific details about the XSS attempt including the suspicious
     * input, detected patterns, and affected field.
     * 
     * @return array XSS attempt details
     */
    public function getXssDetails(): array
    {
        return [
            'field' => $this->context['field'] ?? 'unknown',
            'input' => $this->sanitizeForLog($this->context['input'] ?? ''),
            'pattern' => $this->context['pattern'] ?? 'unknown',
            'user_id' => $this->context['user_id'] ?? null,
            'detection_method' => $this->context['detection_method'] ?? 'pattern_match',
        ];
    }
    
    /**
     * Sanitize input for logging
     * 
     * Removes potentially harmful content from input before logging to prevent
     * log injection attacks.
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
     * Get recommended action
     * 
     * Returns recommended security action based on the severity of the XSS attempt.
     * 
     * @return string Recommended action (e.g., 'log', 'block', 'alert_admin')
     */
    public function getRecommendedAction(): string
    {
        $input = $this->context['input'] ?? '';
        
        // High severity patterns
        $highSeverityPatterns = [
            '<script',
            'javascript:',
            'onerror=',
            'onload=',
            'eval(',
            'document.cookie',
        ];
        
        foreach ($highSeverityPatterns as $pattern) {
            if (stripos($input, $pattern) !== false) {
                return 'alert_admin';
            }
        }
        
        return 'log';
    }
}

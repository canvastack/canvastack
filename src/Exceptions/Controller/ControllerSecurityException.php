<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * Controller Security Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Base exception class for all security-related controller exceptions.
 * This includes XSS attempts, SQL injection, CSRF violations, and other
 * security threats detected during controller operations.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Security
 * @version 1.0.0
 * 
 * @security CRITICAL - All security exceptions should be logged and monitored
 *           Security teams should be alerted for repeated violations
 * 
 * @example
 * ```php
 * try {
 *     // Security-sensitive operation
 *     $this->validateInput($request);
 * } catch (ControllerSecurityException $e) {
 *     Log::critical('Security violation detected', $e->getContext());
 *     // Alert security team
 *     SecurityMonitor::alert($e);
 *     return response()->json([
 *         'error' => $e->getUserMessage()
 *     ], 403);
 * }
 * ```
 */
class ControllerSecurityException extends ControllerException
{
    /**
     * @var string Security threat type
     */
    protected string $threatType = 'unknown';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including threat details
     * @param int $code HTTP status code (default: 403 Forbidden)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Security violation detected',
        array $context = [],
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->userMessage = 'Your request was blocked due to security concerns. Please contact support if you believe this is an error.';
    }
    
    /**
     * Get security threat type
     * 
     * Returns the type of security threat detected (e.g., 'xss', 'sql_injection', 'csrf').
     * 
     * @return string Threat type identifier
     */
    public function getThreatType(): string
    {
        return $this->threatType;
    }
    
    /**
     * Set security threat type
     * 
     * @param string $type Threat type identifier
     * @return self For method chaining
     */
    public function setThreatType(string $type): self
    {
        $this->threatType = $type;
        return $this;
    }
    
    /**
     * Get security incident details
     * 
     * Returns comprehensive security incident information for logging and alerting.
     * 
     * @return array Security incident details
     */
    public function getSecurityIncident(): array
    {
        return [
            'threat_type' => $this->threatType,
            'severity' => 'critical',
            'timestamp' => date('Y-m-d H:i:s'),
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'context' => $this->context,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ];
    }
}

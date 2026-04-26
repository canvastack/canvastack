<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * Session Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when session operations fail or session integrity is compromised.
 * This includes session validation failures, expired sessions, and session tampering.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Session Management
 * @version 1.0.0
 * 
 * @security Session errors may indicate security issues
 *           Monitor for session tampering and hijacking attempts
 * 
 * @example
 * ```php
 * // In session validation
 * if (!$this->validateSessionIntegrity($session)) {
 *     throw new SessionException(
 *         'Session integrity check failed',
 *         [
 *             'session_id' => session_id(),
 *             'user_id' => $session['id'] ?? null,
 *             'ip_address' => request()->ip()
 *         ]
 *     );
 * }
 * ```
 */
class SessionException extends ControllerException
{
    /**
     * @var string Session error type
     */
    protected string $sessionErrorType = 'unknown';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including session details
     * @param int $code HTTP status code (default: 401 Unauthorized)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Session error occurred',
        array $context = [],
        int $code = 401,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->sessionErrorType = $context['error_type'] ?? 'unknown';
        $this->userMessage = 'Your session has expired or is invalid. Please log in again.';
    }
    
    /**
     * Get session error type
     * 
     * Returns the type of session error (e.g., 'expired', 'invalid', 'tampered', 'missing').
     * 
     * @return string Session error type
     */
    public function getSessionErrorType(): string
    {
        return $this->sessionErrorType;
    }
    
    /**
     * Get session details
     * 
     * Returns detailed information about the session error.
     * 
     * @return array Session error details
     */
    public function getSessionDetails(): array
    {
        return [
            'session_id' => $this->context['session_id'] ?? 'unknown',
            'user_id' => $this->context['user_id'] ?? null,
            'error_type' => $this->sessionErrorType,
            'ip_address' => $this->context['ip_address'] ?? 'unknown',
            'user_agent' => $this->context['user_agent'] ?? 'unknown',
            'last_activity' => $this->context['last_activity'] ?? null,
        ];
    }
    
    /**
     * Get user-friendly error message based on error type
     * 
     * @return string Contextual user-friendly message
     */
    public function getUserMessage(): string
    {
        switch ($this->sessionErrorType) {
            case 'expired':
                return 'Your session has expired due to inactivity. Please log in again.';
                
            case 'invalid':
                return 'Your session is invalid. Please log in again.';
                
            case 'tampered':
                return 'Session security check failed. Please log in again.';
                
            case 'missing':
                return 'No active session found. Please log in to continue.';
                
            case 'conflict':
                return 'Your account is logged in from another location. Please log in again.';
                
            case 'regeneration_failed':
                return 'Session refresh failed. Please log in again.';
                
            default:
                return $this->userMessage;
        }
    }
    
    /**
     * Check if session should be destroyed
     * 
     * Determines if the session should be destroyed based on error type.
     * 
     * @return bool True if session should be destroyed
     */
    public function shouldDestroySession(): bool
    {
        return in_array($this->sessionErrorType, [
            'tampered',
            'invalid',
            'conflict',
        ]);
    }
    
    /**
     * Check if user should be redirected to login
     * 
     * @return bool True if redirect to login is needed
     */
    public function shouldRedirectToLogin(): bool
    {
        return in_array($this->sessionErrorType, [
            'expired',
            'invalid',
            'missing',
            'tampered',
            'conflict',
        ]);
    }
    
    /**
     * Create exception for expired session
     * 
     * @param string $sessionId Session ID
     * @param int|null $userId User ID
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function expired(
        string $sessionId,
        ?int $userId = null,
        array $additionalContext = []
    ): self {
        return new self(
            "Session expired: {$sessionId}",
            array_merge([
                'error_type' => 'expired',
                'session_id' => $sessionId,
                'user_id' => $userId,
            ], $additionalContext),
            401
        );
    }
    
    /**
     * Create exception for tampered session
     * 
     * @param string $sessionId Session ID
     * @param string $reason Tampering reason
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function tampered(
        string $sessionId,
        string $reason,
        array $additionalContext = []
    ): self {
        return new self(
            "Session tampering detected: {$sessionId} - {$reason}",
            array_merge([
                'error_type' => 'tampered',
                'session_id' => $sessionId,
                'reason' => $reason,
            ], $additionalContext),
            403
        );
    }
    
    /**
     * Create exception for missing session
     * 
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function missing(array $additionalContext = []): self
    {
        return new self(
            'No active session found',
            array_merge([
                'error_type' => 'missing',
            ], $additionalContext),
            401
        );
    }
    
    /**
     * Create exception for session conflict
     * 
     * @param string $sessionId Session ID
     * @param int $userId User ID
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function conflict(
        string $sessionId,
        int $userId,
        array $additionalContext = []
    ): self {
        return new self(
            "Session conflict detected for user {$userId}",
            array_merge([
                'error_type' => 'conflict',
                'session_id' => $sessionId,
                'user_id' => $userId,
            ], $additionalContext),
            409
        );
    }
}

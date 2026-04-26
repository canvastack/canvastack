<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * Privilege Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when privilege/permission checks fail.
 * This includes unauthorized access attempts, insufficient permissions,
 * and role-based access control violations.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Authorization
 * @version 1.0.0
 * 
 * @security Privilege violations should be logged and monitored
 *           Repeated violations may indicate unauthorized access attempts
 * 
 * @example
 * ```php
 * // In privilege check
 * if (!$this->checkPrivilege($userId, $module, $action)) {
 *     throw new PrivilegeException(
 *         'Insufficient privileges',
 *         [
 *             'user_id' => $userId,
 *             'module' => $module,
 *             'action' => $action,
 *             'required_role' => 'admin'
 *         ]
 *     );
 * }
 * ```
 */
class PrivilegeException extends ControllerException
{
    /**
     * @var string Required privilege/role
     */
    protected string $requiredPrivilege = '';
    
    /**
     * @var string Attempted action
     */
    protected string $attemptedAction = '';
    
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including privilege details
     * @param int $code HTTP status code (default: 403 Forbidden)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Insufficient privileges',
        array $context = [],
        int $code = 403,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->requiredPrivilege = $context['required_privilege'] ?? '';
        $this->attemptedAction = $context['action'] ?? '';
        $this->userMessage = 'You do not have permission to perform this action.';
    }
    
    /**
     * Get required privilege
     * 
     * Returns the privilege/role required for the attempted action.
     * 
     * @return string Required privilege
     */
    public function getRequiredPrivilege(): string
    {
        return $this->requiredPrivilege;
    }
    
    /**
     * Get attempted action
     * 
     * Returns the action that was attempted without sufficient privileges.
     * 
     * @return string Attempted action
     */
    public function getAttemptedAction(): string
    {
        return $this->attemptedAction;
    }
    
    /**
     * Get privilege violation details
     * 
     * Returns detailed information about the privilege violation.
     * 
     * @return array Privilege violation details
     */
    public function getPrivilegeDetails(): array
    {
        return [
            'user_id' => $this->context['user_id'] ?? null,
            'username' => $this->context['username'] ?? 'unknown',
            'user_role' => $this->context['user_role'] ?? 'unknown',
            'module' => $this->context['module'] ?? 'unknown',
            'action' => $this->attemptedAction,
            'required_privilege' => $this->requiredPrivilege,
            'required_role' => $this->context['required_role'] ?? 'unknown',
            'ip_address' => $this->context['ip_address'] ?? 'unknown',
            'route' => $this->context['route'] ?? 'unknown',
        ];
    }
    
    /**
     * Get user-friendly error message with context
     * 
     * @return string Contextual user-friendly message
     */
    public function getUserMessage(): string
    {
        $module = $this->context['module'] ?? 'this resource';
        $action = $this->attemptedAction ?: 'this action';
        
        if ($this->requiredPrivilege) {
            return "You need '{$this->requiredPrivilege}' privilege to {$action} on {$module}.";
        }
        
        return "You do not have permission to {$action} on {$module}.";
    }
    
    /**
     * Check if violation should be logged as security incident
     * 
     * @return bool True if should be logged as security incident
     */
    public function isSecurityIncident(): bool
    {
        // Log as security incident if:
        // 1. Attempting destructive actions (delete, destroy)
        // 2. Attempting admin actions
        // 3. Repeated violations (check context)
        
        $destructiveActions = ['delete', 'destroy', 'drop', 'truncate'];
        $adminActions = ['manage_users', 'manage_roles', 'system_settings'];
        
        $action = strtolower($this->attemptedAction);
        
        foreach ($destructiveActions as $destructive) {
            if (strpos($action, $destructive) !== false) {
                return true;
            }
        }
        
        foreach ($adminActions as $admin) {
            if (strpos($action, $admin) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create exception for module access denial
     * 
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $requiredRole Required role
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function moduleAccessDenied(
        int $userId,
        string $module,
        string $requiredRole,
        array $additionalContext = []
    ): self {
        return new self(
            "Access denied to module '{$module}' for user {$userId}",
            array_merge([
                'user_id' => $userId,
                'module' => $module,
                'action' => 'access',
                'required_role' => $requiredRole,
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for action not permitted
     * 
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $action Action name
     * @param string $requiredPrivilege Required privilege
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function actionNotPermitted(
        int $userId,
        string $module,
        string $action,
        string $requiredPrivilege,
        array $additionalContext = []
    ): self {
        return new self(
            "Action '{$action}' not permitted on module '{$module}' for user {$userId}",
            array_merge([
                'user_id' => $userId,
                'module' => $module,
                'action' => $action,
                'required_privilege' => $requiredPrivilege,
            ], $additionalContext)
        );
    }
    
    /**
     * Create exception for role mismatch
     * 
     * @param int $userId User ID
     * @param string $userRole User's current role
     * @param string $requiredRole Required role
     * @param array $additionalContext Additional context data
     * @return self
     */
    public static function roleMismatch(
        int $userId,
        string $userRole,
        string $requiredRole,
        array $additionalContext = []
    ): self {
        return new self(
            "Role mismatch: user {$userId} has role '{$userRole}' but '{$requiredRole}' is required",
            array_merge([
                'user_id' => $userId,
                'user_role' => $userRole,
                'required_role' => $requiredRole,
            ], $additionalContext)
        );
    }
}

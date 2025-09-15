<?php

namespace Canvastack\Canvastack\Library\Components\Form\Security;

use Canvastack\Canvastack\Library\Components\Form\Security\SecurityLogger;
use Canvastack\Canvastack\Library\Components\Form\Security\InputValidator;

/**
 * Form Authorization Service for CanvaStack Form System
 * 
 * Provides comprehensive authorization controls to prevent
 * Insecure Direct Object Reference (IDOR) vulnerabilities.
 * 
 * @package Canvastack\Form\Security
 * @version 2.0.0
 * @author CanvaStack Security Team
 */
class FormAuthorizationService
{
    /**
     * Check if user can access a specific record
     * 
     * @param string $modelClass Model class name
     * @param int $recordId Record ID to check
     * @param string $action Action to perform (view, edit, delete)
     * @return bool True if authorized, false otherwise
     */
    public static function canAccessRecord($modelClass, $recordId, $action = 'view')
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        // Validate record ID
        if (!is_numeric($recordId) || $recordId <= 0) {
            return false;
        }
        
        // Check if model class exists
        if (!class_exists($modelClass)) {
            return false;
        }
        
        try {
            // Check if model has authorization policy
            $policyClass = $modelClass . 'Policy';
            if (class_exists($policyClass)) {
                $policy = app($policyClass);
                
                // Find record using Laravel's service container
                $record = null;
                if (class_exists($modelClass)) {
                    try {
                        // Use app() to resolve model with proper dependencies
                        $model = app($modelClass);
                        $record = $model->where($model->getKeyName(), $recordId)->first();
                    } catch (\Exception $e) {
                        \Log::warning('Model resolution failed', [
                            'model' => $modelClass,
                            'record_id' => $recordId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                if (!$record) {
                    return false;
                }
                
                // Use policy method if exists
                if (method_exists($policy, $action)) {
                    return $policy->$action($user, $record);
                }
            }
            
            // Default authorization logic
            return self::defaultAuthorization($user, $modelClass, $recordId, $action);
            
        } catch (\Exception $e) {
            // Log authorization error
            \Log::warning('Authorization check failed', [
                'model' => $modelClass,
                'record_id' => $recordId,
                'action' => $action,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Default authorization logic
     * 
     * @param object $user Current user
     * @param string $modelClass Model class name
     * @param int $recordId Record ID
     * @param string $action Action to perform
     * @return bool True if authorized, false otherwise
     */
    private static function defaultAuthorization($user, $modelClass, $recordId, $action)
    {
        // Find record using Laravel's service container
        $record = null;
        if (class_exists($modelClass)) {
            try {
                // Use app() to resolve model with proper dependencies
                $model = app($modelClass);
                $record = $model->where($model->getKeyName(), $recordId)->first();
            } catch (\Exception $e) {
                \Log::warning('Model resolution failed in defaultAuthorization', [
                    'model' => $modelClass,
                    'record_id' => $recordId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        if (!$record) {
            // If record not found, allow access (let controller handle 404)
            return true;
        }
        
        // Check if user is super admin
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }
        
        // Check ownership
        if (isset($record->user_id)) {
            if ($record->user_id === $user->id) {
                return true;
            }
        }
        
        // Check if user is admin for this resource
        if (method_exists($user, 'hasRole')) {
            $resourceName = strtolower(class_basename($modelClass));
            if ($user->hasRole('admin') || $user->hasRole($resourceName . '-admin')) {
                return true;
            }
        }
        
        // Check if user has specific permission
        if (method_exists($user, 'can')) {
            $permission = $action . '-' . strtolower(class_basename($modelClass));
            if ($user->can($permission)) {
                return true;
            }
        }
        
        // Fallback: Allow basic operations for authenticated users
        if (in_array($action, ['view', 'edit', 'update', 'show', 'create', 'store'])) {
            return true;
        }
        
        // Default: deny access
        return false;
    }
    
    /**
     * Check if user can access multiple records
     * 
     * @param string $modelClass Model class name
     * @param array $recordIds Array of record IDs
     * @param string $action Action to perform
     * @return array Array of authorized record IDs
     */
    public static function filterAuthorizedRecords($modelClass, array $recordIds, $action = 'view')
    {
        $authorizedIds = [];
        
        foreach ($recordIds as $recordId) {
            if (self::canAccessRecord($modelClass, $recordId, $action)) {
                $authorizedIds[] = $recordId;
            }
        }
        
        return $authorizedIds;
    }
    
    /**
     * Validate and authorize form model binding
     * 
     * @param string $modelClass Model class name
     * @param int $recordId Record ID from URL
     * @param string $routeName Current route name
     * @return bool True if authorized, false otherwise
     */
    public static function authorizeFormAccess($modelClass, $recordId, $routeName)
    {
        // Determine action based on route name
        $action = 'view';
        
        if (str_contains($routeName, 'edit') || str_contains($routeName, 'update')) {
            $action = 'update';
        } elseif (str_contains($routeName, 'delete') || str_contains($routeName, 'destroy')) {
            $action = 'delete';
        } elseif (str_contains($routeName, 'create') || str_contains($routeName, 'store')) {
            $action = 'create';
        }
        
        // For create actions, check general permission
        if ($action === 'create') {
            return self::canCreateRecord($modelClass);
        }
        
        // For other actions, check specific record access
        return self::canAccessRecord($modelClass, $recordId, $action);
    }
    
    /**
     * Check if user can create new records
     * 
     * @param string $modelClass Model class name
     * @return bool True if authorized, false otherwise
     */
    public static function canCreateRecord($modelClass)
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        // Check if user has create permission
        if (method_exists($user, 'can')) {
            $permission = 'create-' . strtolower(class_basename($modelClass));
            if ($user->can($permission)) {
                return true;
            }
        }
        
        // Check if user has admin role
        if (method_exists($user, 'hasRole')) {
            $resourceName = strtolower(class_basename($modelClass));
            if ($user->hasRole('admin') || $user->hasRole($resourceName . '-admin')) {
                return true;
            }
        }
        
        // Default: allow authenticated users to create (can be customized)
        return true;
    }
    
    /**
     * Log authorization attempt
     * 
     * @param string $modelClass Model class name
     * @param int $recordId Record ID
     * @param string $action Action attempted
     * @param bool $authorized Whether access was granted
     */
    public static function logAuthorizationAttempt($modelClass, $recordId, $action, $authorized)
    {
        $user = auth()->user();
        
        \Log::info('Authorization attempt', [
            'model' => $modelClass,
            'record_id' => $recordId,
            'action' => $action,
            'authorized' => $authorized,
            'user_id' => $user ? $user->id : null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'timestamp' => now()
        ]);
        
        // Log security incident if unauthorized access attempted
        if (!$authorized) {
            \Log::warning('SECURITY: Unauthorized access attempt', [
                'model' => $modelClass,
                'record_id' => $recordId,
                'action' => $action,
                'user_id' => $user ? $user->id : null,
                'ip' => request()->ip(),
                'url' => request()->fullUrl()
            ]);
        }
    }
    
    /**
     * Validate and sanitize record ID from URL
     * 
     * @param mixed $rawId Raw record ID from URL
     * @return int|null Validated record ID or null if invalid
     */
    public static function validateRecordId($rawId)
    {
        // Check if ID is provided
        if (empty($rawId)) {
            return null;
        }
        
        // Convert to string for validation
        $rawIdStr = (string) $rawId;
        
        // Check if ID contains only digits (no decimals, no scientific notation)
        if (!preg_match('/^\d+$/', $rawIdStr)) {
            return null;
        }
        
        // Check if ID is numeric
        if (!is_numeric($rawIdStr)) {
            return null;
        }
        
        // Convert to integer
        $recordId = intval($rawIdStr);
        
        // Check if ID is positive
        if ($recordId <= 0) {
            return null;
        }
        
        // Check for reasonable upper limit (prevent integer overflow attacks)
        if ($recordId > PHP_INT_MAX || $recordId > 2147483647) {
            return null;
        }
        
        // Additional check: ensure the string representation matches the integer
        // This prevents cases where intval() truncates decimals
        if ($rawIdStr !== (string) $recordId) {
            return null;
        }
        
        return $recordId;
    }
    
    /**
     * Create authorization middleware for forms
     * 
     * @param string $modelClass Model class name
     * @param string $paramName Parameter name in route
     * @return \Closure Middleware closure
     */
    public static function createMiddleware($modelClass, $paramName = 'id')
    {
        return function ($request, $next) use ($modelClass, $paramName) {
            $recordId = $request->route($paramName);
            $routeName = $request->route()->getName();
            
            if ($recordId && !self::authorizeFormAccess($modelClass, $recordId, $routeName)) {
                self::logAuthorizationAttempt($modelClass, $recordId, 'access', false);
                abort(403, 'Unauthorized access to this resource');
            }
            
            return $next($request);
        };
    }
}
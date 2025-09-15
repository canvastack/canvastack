<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

/**
 * AdvancedAccessControlManager
 * 
 * Modular access control system with RBAC, ABAC, and dynamic permissions
 * Can operate independently or integrate with existing authentication systems
 * 
 * @package Canvastack\Table\Security
 * @version 2.0
 * @author Security Hardening Team
 */
class AdvancedAccessControlManager
{
    /**
     * Access control modes
     */
    public const MODE_DISABLED = 'disabled';
    public const MODE_BASIC = 'basic';
    public const MODE_RBAC = 'rbac';
    public const MODE_ABAC = 'abac';
    public const MODE_HYBRID = 'hybrid';
    public const MODE_CUSTOM = 'custom';
    
    /**
     * Permission types
     */
    public const PERMISSION_READ = 'read';
    public const PERMISSION_WRITE = 'write';
    public const PERMISSION_DELETE = 'delete';
    public const PERMISSION_EXPORT = 'export';
    public const PERMISSION_ADMIN = 'admin';
    
    /**
     * Default role hierarchy
     */
    private const DEFAULT_ROLE_HIERARCHY = [
        'super_admin' => ['admin', 'manager', 'user', 'guest'],
        'admin' => ['manager', 'user', 'guest'],
        'manager' => ['user', 'guest'],
        'user' => ['guest'],
        'guest' => []
    ];
    
    /**
     * Default table permissions
     */
    private const DEFAULT_TABLE_PERMISSIONS = [
        'super_admin' => [
            self::PERMISSION_READ, 
            self::PERMISSION_WRITE, 
            self::PERMISSION_DELETE, 
            self::PERMISSION_EXPORT, 
            self::PERMISSION_ADMIN
        ],
        'admin' => [
            self::PERMISSION_READ, 
            self::PERMISSION_WRITE, 
            self::PERMISSION_EXPORT
        ],
        'manager' => [
            self::PERMISSION_READ, 
            self::PERMISSION_WRITE, 
            self::PERMISSION_EXPORT
        ],
        'user' => [
            self::PERMISSION_READ
        ],
        'guest' => []
    ];
    
    /**
     * Current access control mode
     */
    private string $mode;
    
    /**
     * Role hierarchy configuration
     */
    private array $roleHierarchy;
    
    /**
     * Table permissions configuration
     */
    private array $tablePermissions;
    
    /**
     * Custom auth provider
     */
    private ?object $authProvider = null;
    
    /**
     * Permission cache TTL (minutes)
     */
    private int $cacheTimeToLive = 30;
    
    /**
     * Access audit trail
     */
    private array $accessAuditTrail = [];
    
    public function __construct()
    {
        $this->mode = Config::get('canvastack.security.access_control.mode', self::MODE_DISABLED);
        $this->roleHierarchy = Config::get('canvastack.security.access_control.role_hierarchy', self::DEFAULT_ROLE_HIERARCHY);
        $this->tablePermissions = Config::get('canvastack.security.access_control.table_permissions', []);
        $this->cacheTimeToLive = Config::get('canvastack.security.access_control.cache_ttl', 30);
        
        $this->initializeAuthProvider();
    }
    
    /**
     * Check if user has permission to access table with specific operation
     *
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    public function hasPermission(string $table, string $operation, array $context = []): bool
    {
        // If access control is disabled, allow all operations
        if ($this->mode === self::MODE_DISABLED) {
            return true;
        }
        
        try {
            $user = $this->getCurrentUser($context);
            
            // Check cached permission first
            $cacheKey = $this->getCacheKey($user, $table, $operation, $context);
            $cachedResult = Cache::get($cacheKey);
            
            if ($cachedResult !== null) {
                $this->logAccessAttempt($table, $operation, $user, $cachedResult, 'cached', $context);
                return $cachedResult;
            }
            
            // Evaluate permission
            $hasPermission = $this->evaluatePermission($user, $table, $operation, $context);
            
            // Cache the result
            Cache::put($cacheKey, $hasPermission, now()->addMinutes($this->cacheTimeToLive));
            
            // Log access attempt
            $this->logAccessAttempt($table, $operation, $user, $hasPermission, 'evaluated', $context);
            
            return $hasPermission;
            
        } catch (\Exception $e) {
            $this->logAccessError($table, $operation, $e, $context);
            
            // Fail secure - deny access on error
            return false;
        }
    }
    
    /**
     * Get allowed operations for user on specific table
     *
     * @param string $table
     * @param array $context
     * @return array
     */
    public function getAllowedOperations(string $table, array $context = []): array
    {
        if ($this->mode === self::MODE_DISABLED) {
            return [
                self::PERMISSION_READ,
                self::PERMISSION_WRITE,
                self::PERMISSION_DELETE,
                self::PERMISSION_EXPORT,
                self::PERMISSION_ADMIN
            ];
        }
        
        $user = $this->getCurrentUser($context);
        $allowedOperations = [];
        
        $operations = [
            self::PERMISSION_READ,
            self::PERMISSION_WRITE,
            self::PERMISSION_DELETE,
            self::PERMISSION_EXPORT,
            self::PERMISSION_ADMIN
        ];
        
        foreach ($operations as $operation) {
            if ($this->hasPermission($table, $operation, $context)) {
                $allowedOperations[] = $operation;
            }
        }
        
        return $allowedOperations;
    }
    
    /**
     * Grant temporary permission to user
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param int $durationMinutes
     * @param string $reason
     * @return string
     */
    public function grantTemporaryPermission(array $user, string $table, string $operation, int $durationMinutes, string $reason = ''): string
    {
        $tempPermissionId = uniqid('temp_perm_', true);
        
        $tempPermission = [
            'id' => $tempPermissionId,
            'user_id' => $user['id'] ?? null,
            'user_identifier' => $this->getUserIdentifier($user),
            'table' => $table,
            'operation' => $operation,
            'granted_at' => now(),
            'expires_at' => now()->addMinutes($durationMinutes),
            'granted_by' => $this->getCurrentUser()['id'] ?? 'system',
            'reason' => $reason,
            'status' => 'active'
        ];
        
        $this->storeTempPermission($tempPermission);
        
        // Clear related permission caches
        $this->clearPermissionCache($user, $table);
        
        $this->logTemporaryPermissionGrant($tempPermission);
        
        return $tempPermissionId;
    }
    
    /**
     * Revoke temporary permission
     *
     * @param string $tempPermissionId
     * @param string $reason
     * @return bool
     */
    public function revokeTemporaryPermission(string $tempPermissionId, string $reason = ''): bool
    {
        $tempPermission = $this->getTempPermission($tempPermissionId);
        
        if (!$tempPermission) {
            return false;
        }
        
        $tempPermission['status'] = 'revoked';
        $tempPermission['revoked_at'] = now();
        $tempPermission['revoked_by'] = $this->getCurrentUser()['id'] ?? 'system';
        $tempPermission['revoke_reason'] = $reason;
        
        $this->updateTempPermission($tempPermission);
        
        // Clear permission cache
        $user = ['id' => $tempPermission['user_id']];
        $this->clearPermissionCache($user, $tempPermission['table']);
        
        $this->logTemporaryPermissionRevoke($tempPermission);
        
        return true;
    }
    
    /**
     * Get access audit trail for table
     *
     * @param string $table
     * @param array $filters
     * @return array
     */
    public function getAccessAuditTrail(string $table, array $filters = []): array
    {
        $auditEntries = $this->retrieveAuditEntries($table, $filters);
        
        return [
            'table' => $table,
            'total_entries' => count($auditEntries),
            'date_range' => [
                'from' => $auditEntries[0]['timestamp'] ?? null,
                'to' => end($auditEntries)['timestamp'] ?? null
            ],
            'access_patterns' => $this->analyzeAccessPatterns($auditEntries),
            'security_events' => array_filter($auditEntries, fn($e) => $e['access_granted'] === false),
            'most_active_users' => $this->getMostActiveUsers($auditEntries),
            'operation_statistics' => $this->getOperationStatistics($auditEntries),
            'entries' => $auditEntries
        ];
    }
    
    /**
     * Register custom authentication provider
     *
     * @param object $provider
     * @return void
     */
    public function registerAuthProvider(object $provider): void
    {
        $this->authProvider = $provider;
        $this->mode = self::MODE_CUSTOM;
    }
    
    /**
     * Add custom role to hierarchy
     *
     * @param string $role
     * @param array $inheritedRoles
     * @return void
     */
    public function addRole(string $role, array $inheritedRoles = []): void
    {
        $this->roleHierarchy[$role] = $inheritedRoles;
        $this->clearAllPermissionCaches();
        
        $this->logRoleManagement('role_added', $role, $inheritedRoles);
    }
    
    /**
     * Set table permissions for role
     *
     * @param string $table
     * @param string $role
     * @param array $permissions
     * @return void
     */
    public function setTablePermissions(string $table, string $role, array $permissions): void
    {
        if (!isset($this->tablePermissions[$table])) {
            $this->tablePermissions[$table] = [];
        }
        
        $this->tablePermissions[$table][$role] = $permissions;
        $this->clearTablePermissionCache($table);
        
        $this->logPermissionManagement('table_permissions_updated', $table, $role, $permissions);
    }
    
    /**
     * Evaluate permission based on access control mode
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluatePermission(array $user, string $table, string $operation, array $context): bool
    {
        switch ($this->mode) {
            case self::MODE_BASIC:
                return $this->evaluateBasicPermission($user, $table, $operation, $context);
                
            case self::MODE_RBAC:
                return $this->evaluateRbacPermission($user, $table, $operation, $context);
                
            case self::MODE_ABAC:
                return $this->evaluateAbacPermission($user, $table, $operation, $context);
                
            case self::MODE_HYBRID:
                return $this->evaluateHybridPermission($user, $table, $operation, $context);
                
            case self::MODE_CUSTOM:
                return $this->evaluateCustomPermission($user, $table, $operation, $context);
                
            default:
                return false;
        }
    }
    
    /**
     * Evaluate basic permission (simple allow/deny)
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluateBasicPermission(array $user, string $table, string $operation, array $context): bool
    {
        // Check temporary permissions first
        if ($this->hasTempPermission($user, $table, $operation)) {
            return true;
        }
        
        // Basic permission logic - authenticated users can read, admin can do everything
        $isAuthenticated = !empty($user['id']);
        $isAdmin = in_array('admin', $user['roles'] ?? []);
        
        if ($isAdmin) {
            return true;
        }
        
        if ($isAuthenticated && $operation === self::PERMISSION_READ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Evaluate RBAC permission
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluateRbacPermission(array $user, string $table, string $operation, array $context): bool
    {
        // Check temporary permissions first
        if ($this->hasTempPermission($user, $table, $operation)) {
            return true;
        }
        
        $userRoles = $user['roles'] ?? [];
        
        foreach ($userRoles as $role) {
            // Check direct role permissions
            if ($this->roleHasTablePermission($role, $table, $operation)) {
                return true;
            }
            
            // Check inherited role permissions
            $inheritedRoles = $this->getInheritedRoles($role);
            foreach ($inheritedRoles as $inheritedRole) {
                if ($this->roleHasTablePermission($inheritedRole, $table, $operation)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Evaluate ABAC permission (Attribute-Based Access Control)
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluateAbacPermission(array $user, string $table, string $operation, array $context): bool
    {
        // Start with RBAC evaluation
        $rbacResult = $this->evaluateRbacPermission($user, $table, $operation, $context);
        
        if (!$rbacResult) {
            return false;
        }
        
        // Apply ABAC rules
        $abacRules = [
            $this->checkDepartmentAccess($user, $table, $context),
            $this->checkTimeAccess($user, $table, $context),
            $this->checkLocationAccess($user, $table, $context),
            $this->checkDataSensitivity($user, $table, $operation, $context),
            $this->checkProjectAccess($user, $table, $context)
        ];
        
        // All ABAC rules must pass
        return array_reduce($abacRules, fn($carry, $rule) => $carry && $rule, true);
    }
    
    /**
     * Evaluate hybrid permission (RBAC + ABAC)
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluateHybridPermission(array $user, string $table, string $operation, array $context): bool
    {
        return $this->evaluateAbacPermission($user, $table, $operation, $context);
    }
    
    /**
     * Evaluate custom permission using external provider
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return bool
     */
    private function evaluateCustomPermission(array $user, string $table, string $operation, array $context): bool
    {
        if (!$this->authProvider) {
            throw new SecurityException('Custom auth provider not registered');
        }
        
        if (method_exists($this->authProvider, 'canAccess')) {
            return $this->authProvider->canAccess($table, $operation, array_merge($context, ['user' => $user]));
        }
        
        if (method_exists($this->authProvider, 'hasPermission')) {
            return $this->authProvider->hasPermission($user, $table, $operation, $context);
        }
        
        throw new SecurityException('Custom auth provider does not implement required methods');
    }
    
    /**
     * Get current user from context or auth system
     *
     * @param array $context
     * @return array
     */
    private function getCurrentUser(array $context = []): array
    {
        // Check context first
        if (isset($context['user'])) {
            return $context['user'];
        }
        
        // Use custom auth provider
        if ($this->authProvider && method_exists($this->authProvider, 'getCurrentUser')) {
            return $this->authProvider->getCurrentUser();
        }
        
        // Use Laravel's built-in auth
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();
            return [
                'id' => $user->id,
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : ['user']
            ];
        }
        
        // Return guest user
        return [
            'id' => null,
            'name' => 'Guest',
            'roles' => ['guest']
        ];
    }
    
    /**
     * Check if role has specific table permission
     *
     * @param string $role
     * @param string $table
     * @param string $operation
     * @return bool
     */
    private function roleHasTablePermission(string $role, string $table, string $operation): bool
    {
        // Check table-specific permissions
        if (isset($this->tablePermissions[$table][$role])) {
            return in_array($operation, $this->tablePermissions[$table][$role]);
        }
        
        // Check default permissions
        if (isset(self::DEFAULT_TABLE_PERMISSIONS[$role])) {
            return in_array($operation, self::DEFAULT_TABLE_PERMISSIONS[$role]);
        }
        
        return false;
    }
    
    /**
     * Get inherited roles for a role
     *
     * @param string $role
     * @return array
     */
    private function getInheritedRoles(string $role): array
    {
        return $this->roleHierarchy[$role] ?? [];
    }
    
    /**
     * Generate cache key for permission
     *
     * @param array $user
     * @param string $table
     * @param string $operation
     * @param array $context
     * @return string
     */
    private function getCacheKey(array $user, string $table, string $operation, array $context): string
    {
        $userIdentifier = $this->getUserIdentifier($user);
        $contextHash = md5(serialize($this->getRelevantContext($context)));
        
        return "access_control:{$userIdentifier}:{$table}:{$operation}:{$contextHash}";
    }
    
    /**
     * Get user identifier for caching
     *
     * @param array $user
     * @return string
     */
    private function getUserIdentifier(array $user): string
    {
        if (isset($user['id'])) {
            return "user_{$user['id']}";
        }
        
        if (isset($user['email'])) {
            return "email_" . md5($user['email']);
        }
        
        return 'guest_' . md5(serialize($user));
    }
    
    /**
     * Get relevant context for caching
     *
     * @param array $context
     * @return array
     */
    private function getRelevantContext(array $context): array
    {
        $relevantKeys = ['ip', 'time', 'location', 'department', 'project'];
        
        return array_intersect_key($context, array_flip($relevantKeys));
    }
    
    // ABAC Rule implementations
    
    private function checkDepartmentAccess(array $user, string $table, array $context): bool
    {
        $userDepartment = $user['department'] ?? null;
        $tableDepartment = $context['table_department'] ?? null;
        
        if (!$userDepartment || !$tableDepartment) {
            return true; // Skip check if department info not available
        }
        
        return $userDepartment === $tableDepartment || in_array('admin', $user['roles'] ?? []);
    }
    
    private function checkTimeAccess(array $user, string $table, array $context): bool
    {
        $tableAccessHours = $context['table_access_hours'] ?? null;
        
        if (!$tableAccessHours) {
            return true; // No time restrictions
        }
        
        $currentHour = now()->hour;
        [$startHour, $endHour] = explode('-', $tableAccessHours);
        
        return $currentHour >= (int)$startHour && $currentHour <= (int)$endHour;
    }
    
    private function checkLocationAccess(array $user, string $table, array $context): bool
    {
        $allowedNetworks = $context['allowed_networks'] ?? [];
        $userIp = $context['ip'] ?? request()->ip();
        
        if (empty($allowedNetworks)) {
            return true; // No IP restrictions
        }
        
        foreach ($allowedNetworks as $network) {
            if ($this->ipInNetwork($userIp, $network)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function checkDataSensitivity(array $user, string $table, string $operation, array $context): bool
    {
        $dataSensitivity = $context['data_sensitivity'] ?? 'public';
        $userClearance = $user['clearance_level'] ?? 'public';
        
        $sensitivityLevels = ['public', 'internal', 'confidential', 'restricted', 'secret'];
        $userLevel = array_search($userClearance, $sensitivityLevels);
        $requiredLevel = array_search($dataSensitivity, $sensitivityLevels);
        
        return $userLevel !== false && $requiredLevel !== false && $userLevel >= $requiredLevel;
    }
    
    private function checkProjectAccess(array $user, string $table, array $context): bool
    {
        $tableProjectId = $context['project_id'] ?? null;
        $userProjects = $user['projects'] ?? [];
        
        if (!$tableProjectId) {
            return true; // No project restrictions
        }
        
        return in_array($tableProjectId, $userProjects);
    }
    
    // Utility methods
    
    private function ipInNetwork(string $ip, string $network): bool
    {
        if (strpos($network, '/') !== false) {
            [$subnet, $mask] = explode('/', $network);
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
        }
        
        return $ip === $network;
    }
    
    private function initializeAuthProvider(): void
    {
        $providerClass = Config::get('canvastack.security.access_control.auth_provider');
        
        if ($providerClass && class_exists($providerClass)) {
            $this->authProvider = new $providerClass();
        }
    }
    
    // Cache management
    
    private function clearPermissionCache(array $user, string $table): void
    {
        $userIdentifier = $this->getUserIdentifier($user);
        $pattern = "access_control:{$userIdentifier}:{$table}:*";
        
        // Implementation depends on cache driver
        Cache::forget($pattern);
    }
    
    private function clearTablePermissionCache(string $table): void
    {
        // Implementation depends on cache driver
        $pattern = "access_control:*:{$table}:*";
        Cache::forget($pattern);
    }
    
    private function clearAllPermissionCaches(): void
    {
        // Implementation depends on cache driver
        Cache::forget('access_control:*');
    }
    
    // Placeholder methods for implementation
    private function hasTempPermission(array $user, string $table, string $operation): bool { return false; }
    private function storeTempPermission(array $tempPermission): void { }
    private function getTempPermission(string $id): ?array { return null; }
    private function updateTempPermission(array $tempPermission): void { }
    private function retrieveAuditEntries(string $table, array $filters): array { return []; }
    private function analyzeAccessPatterns(array $entries): array { return []; }
    private function getMostActiveUsers(array $entries): array { return []; }
    private function getOperationStatistics(array $entries): array { return []; }
    private function logAccessAttempt(string $table, string $op, array $user, bool $granted, string $method, array $context): void { }
    private function logAccessError(string $table, string $operation, \Exception $e, array $context): void { }
    private function logTemporaryPermissionGrant(array $tempPermission): void { }
    private function logTemporaryPermissionRevoke(array $tempPermission): void { }
    private function logRoleManagement(string $action, string $role, array $data): void { }
    private function logPermissionManagement(string $action, string $table, string $role, array $permissions): void { }
}
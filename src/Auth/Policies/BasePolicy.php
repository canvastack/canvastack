<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\Policies;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * Base Policy.
 *
 * Provides common functionality for all policies including:
 * - Context-aware authorization (admin, public, api)
 * - Permission caching
 * - Role-based checks
 * - Super admin bypass
 * - Standard CRUD abilities
 *
 * All application policies should extend this class.
 */
abstract class BasePolicy
{
    /**
     * Role manager instance.
     *
     * @var RoleManager
     */
    protected RoleManager $roleManager;

    /**
     * Permission manager instance.
     *
     * @var PermissionManager
     */
    protected PermissionManager $permissionManager;

    /**
     * Authorization configuration.
     *
     * @var array<string, mixed>
     */
    protected array $authConfig;

    /**
     * Cache configuration.
     *
     * @var array<string, mixed>
     */
    protected array $cacheConfig;

    /**
     * Current context (admin, public, api).
     *
     * @var string|null
     */
    protected ?string $context = null;

    /**
     * Create a new BasePolicy instance.
     */
    public function __construct(
        RoleManager $roleManager,
        PermissionManager $permissionManager
    ) {
        $this->roleManager = $roleManager;
        $this->permissionManager = $permissionManager;
        $this->authConfig = config('canvastack-rbac.authorization', []);
        $this->cacheConfig = config('canvastack-rbac.cache', []);
    }

    /**
     * Set the current context.
     *
     * @param string $context Context name (admin, public, api)
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the current context.
     *
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Check if user is super admin.
     *
     * Super admins bypass all authorization checks.
     *
     * @param Authenticatable $user User instance
     * @return bool
     */
    protected function isSuperAdmin(Authenticatable $user): bool
    {
        if (!($this->authConfig['super_admin_bypass'] ?? false)) {
            return false;
        }

        $superAdminRole = $this->authConfig['super_admin_role'] ?? 'super_admin';

        return $this->roleManager->userHasRole($user->id, $superAdminRole);
    }

    /**
     * Check if user has a role.
     *
     * @param Authenticatable $user User instance
     * @param int|string $role Role ID or name
     * @return bool
     */
    protected function hasRole(Authenticatable $user, int|string $role): bool
    {
        return $this->roleManager->userHasRole($user->id, $role);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param Authenticatable $user User instance
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    protected function hasAnyRole(Authenticatable $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param Authenticatable $user User instance
     * @param array<int|string> $roles Role IDs or names
     * @return bool
     */
    protected function hasAllRoles(Authenticatable $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($user, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a permission.
     *
     * @param Authenticatable $user User instance
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    protected function hasPermission(Authenticatable $user, int|string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check with caching
        if ($this->isCacheEnabled()) {
            return $this->hasPermissionCached($user, $permission);
        }

        return $this->permissionManager->userHasPermission($user->id, $permission);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param Authenticatable $user User instance
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    protected function hasAnyPermission(Authenticatable $user, array $permissions): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionManager->userHasAnyPermission($user->id, $permissions);
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param Authenticatable $user User instance
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    protected function hasAllPermissions(Authenticatable $user, array $permissions): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->permissionManager->userHasAllPermissions($user->id, $permissions);
    }

    /**
     * Check if user has permission with caching.
     *
     * @param Authenticatable $user User instance
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    protected function hasPermissionCached(Authenticatable $user, int|string $permission): bool
    {
        $cacheKey = $this->getPermissionCacheKey($user->id, $permission);

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($user, $permission) {
                return $this->permissionManager->userHasPermission($user->id, $permission);
            });
    }

    /**
     * Check if user can perform action in current context.
     *
     * @param Authenticatable $user User instance
     * @param string $permission Permission name
     * @return bool
     */
    protected function canInContext(Authenticatable $user, string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // If no context set, use standard permission check
        if (!$this->context) {
            return $this->hasPermission($user, $permission);
        }

        // Check if context is enabled
        $contextConfig = config("canvastack-rbac.contexts.{$this->context}");

        if (!($contextConfig['enabled'] ?? false)) {
            return false;
        }

        // Build context-aware permission name
        $contextPermission = "{$this->context}.{$permission}";

        // Try context-specific permission first
        if ($this->hasPermission($user, $contextPermission)) {
            return true;
        }

        // Fallback to standard permission if context allows
        if ($contextConfig['fallback_to_standard'] ?? false) {
            return $this->hasPermission($user, $permission);
        }

        return false;
    }

    /**
     * Check if user owns the model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $ownerColumn Owner column name (default: 'user_id')
     * @return bool
     */
    protected function owns(Authenticatable $user, mixed $model, string $ownerColumn = 'user_id'): bool
    {
        if (!$model || !isset($model->{$ownerColumn})) {
            return false;
        }

        return $model->{$ownerColumn} === $user->id;
    }

    /**
     * Check if user has higher role level than model owner.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $ownerColumn Owner column name (default: 'user_id')
     * @return bool
     */
    protected function hasHigherLevel(Authenticatable $user, mixed $model, string $ownerColumn = 'user_id'): bool
    {
        if (!$model || !isset($model->{$ownerColumn})) {
            return false;
        }

        $userLevel = $this->roleManager->getUserHighestPrivilegeLevel($user->id);
        $ownerLevel = $this->roleManager->getUserHighestPrivilegeLevel($model->{$ownerColumn});

        if ($userLevel === null || $ownerLevel === null) {
            return false;
        }

        // Lower level number = higher privilege
        return $userLevel < $ownerLevel;
    }

    /**
     * Standard viewAny ability.
     *
     * Check if user can view any models.
     *
     * @param Authenticatable $user User instance
     * @param string $permission Permission name (e.g., 'users.view')
     * @return bool
     */
    protected function viewAny(Authenticatable $user, string $permission): bool
    {
        return $this->canInContext($user, $permission);
    }

    /**
     * Standard view ability.
     *
     * Check if user can view a specific model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $permission Permission name (e.g., 'users.view')
     * @return bool
     */
    protected function view(Authenticatable $user, mixed $model, string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check permission
        if (!$this->canInContext($user, $permission)) {
            return false;
        }

        // Owner can always view their own
        if ($this->owns($user, $model)) {
            return true;
        }

        // Check if user has higher level
        return $this->hasHigherLevel($user, $model);
    }

    /**
     * Standard create ability.
     *
     * Check if user can create a model.
     *
     * @param Authenticatable $user User instance
     * @param string $permission Permission name (e.g., 'users.create')
     * @return bool
     */
    protected function create(Authenticatable $user, string $permission): bool
    {
        return $this->canInContext($user, $permission);
    }

    /**
     * Standard update ability.
     *
     * Check if user can update a specific model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $permission Permission name (e.g., 'users.update')
     * @return bool
     */
    protected function update(Authenticatable $user, mixed $model, string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check permission
        if (!$this->canInContext($user, $permission)) {
            return false;
        }

        // Owner can update their own
        if ($this->owns($user, $model)) {
            return true;
        }

        // Check if user has higher level
        return $this->hasHigherLevel($user, $model);
    }

    /**
     * Standard delete ability.
     *
     * Check if user can delete a specific model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $permission Permission name (e.g., 'users.delete')
     * @return bool
     */
    protected function delete(Authenticatable $user, mixed $model, string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check permission
        if (!$this->canInContext($user, $permission)) {
            return false;
        }

        // Owner cannot delete their own (prevent self-deletion)
        if ($this->owns($user, $model)) {
            return false;
        }

        // Check if user has higher level
        return $this->hasHigherLevel($user, $model);
    }

    /**
     * Standard restore ability.
     *
     * Check if user can restore a soft-deleted model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $permission Permission name (e.g., 'users.restore')
     * @return bool
     */
    protected function restore(Authenticatable $user, mixed $model, string $permission): bool
    {
        return $this->canInContext($user, $permission);
    }

    /**
     * Standard forceDelete ability.
     *
     * Check if user can permanently delete a model.
     *
     * @param Authenticatable $user User instance
     * @param mixed $model Model instance
     * @param string $permission Permission name (e.g., 'users.force_delete')
     * @return bool
     */
    protected function forceDelete(Authenticatable $user, mixed $model, string $permission): bool
    {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check permission
        if (!$this->canInContext($user, $permission)) {
            return false;
        }

        // Owner cannot force delete their own
        if ($this->owns($user, $model)) {
            return false;
        }

        // Check if user has higher level
        return $this->hasHigherLevel($user, $model);
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return $this->cacheConfig['enabled'] ?? false;
    }

    /**
     * Get cache TTL.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return $this->cacheConfig['ttl'] ?? 3600;
    }

    /**
     * Get permission cache key.
     *
     * @param int $userId User ID
     * @param int|string $permission Permission ID or name
     * @return string
     */
    protected function getPermissionCacheKey(int $userId, int|string $permission): string
    {
        $prefix = $this->cacheConfig['key_prefix'] ?? 'canvastack:rbac:';
        $permissionKey = is_int($permission) ? "id.{$permission}" : "name.{$permission}";

        return "{$prefix}user.{$userId}.permission.{$permissionKey}";
    }

    /**
     * Get cache tags.
     *
     * @param string $tag Tag name
     * @return array<string>
     */
    protected function getCacheTags(string $tag): array
    {
        $tags = $this->cacheConfig['tags'] ?? [];

        return [$tags[$tag] ?? "canvastack:rbac:{$tag}"];
    }

    /**
     * Clear permission cache for a user.
     *
     * @param int $userId User ID
     * @return bool
     */
    protected function clearUserPermissionCache(int $userId): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return $this->permissionManager->clearUserCache($userId);
    }
}

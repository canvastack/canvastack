<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Canvastack\Canvastack\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Permission Manager.
 *
 * Manages permission CRUD operations, assignments, and caching.
 * Provides methods for creating, updating, deleting, and querying permissions.
 */
class PermissionManager
{
    /**
     * Cache configuration.
     *
     * @var array<string, mixed>
     */
    protected array $cacheConfig;

    /**
     * Permission configuration.
     *
     * @var array<string, mixed>
     */
    protected array $permissionConfig;

    /**
     * Create a new PermissionManager instance.
     */
    public function __construct()
    {
        $this->cacheConfig = config('canvastack-rbac.cache', []);
        $this->permissionConfig = config('canvastack-rbac.permissions', []);
    }

    /**
     * Get all permissions.
     *
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function all(bool $useCache = true): Collection
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return Permission::all();
        }

        $cacheKey = $this->getCacheKey('all');

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () {
                return Permission::all();
            });
    }

    /**
     * Find a permission by ID.
     *
     * @param int $id Permission ID
     * @param bool $useCache Whether to use cache
     * @return Permission|null
     */
    public function find(int $id, bool $useCache = true): ?Permission
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return Permission::find($id);
        }

        $cacheKey = $this->getCacheKey("permission.{$id}");

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($id) {
                return Permission::find($id);
            });
    }

    /**
     * Find a permission by name.
     *
     * @param string $name Permission name
     * @param bool $useCache Whether to use cache
     * @return Permission|null
     */
    public function findByName(string $name, bool $useCache = true): ?Permission
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return Permission::where('name', $name)->first();
        }

        $cacheKey = $this->getCacheKey("permission.name.{$name}");

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($name) {
                return Permission::where('name', $name)->first();
            });
    }

    /**
     * Get permissions by module.
     *
     * @param string $module Module name
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getByModule(string $module, bool $useCache = true): Collection
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return Permission::where('module', $module)->get();
        }

        $cacheKey = $this->getCacheKey("module.{$module}");

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($module) {
                return Permission::where('module', $module)->get();
            });
    }

    /**
     * Create a new permission.
     *
     * @param array<string, mixed> $data Permission data
     * @return Permission
     * @throws InvalidArgumentException
     */
    public function create(array $data): Permission
    {
        $this->validatePermissionData($data);

        DB::beginTransaction();

        try {
            $permission = Permission::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'module' => $data['module'] ?? null,
            ]);

            // Attach to roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $permission->roles()->sync($data['roles']);
            }

            DB::commit();

            $this->clearCache();

            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Update a permission.
     *
     * @param int $id Permission ID
     * @param array<string, mixed> $data Permission data
     * @return bool
     * @throws InvalidArgumentException
     */
    public function update(int $id, array $data): bool
    {
        $permission = Permission::findOrFail($id);

        $this->validatePermissionData($data, $id);

        DB::beginTransaction();

        try {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'description' => $data['description'] ?? null,
                'module' => $data['module'] ?? null,
            ], fn ($value) => $value !== null);

            $permission->update($updateData);

            // Update roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $permission->roles()->sync($data['roles']);
            }

            DB::commit();

            $this->clearCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Delete a permission.
     *
     * @param int $id Permission ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        $permission = Permission::findOrFail($id);

        DB::beginTransaction();

        try {
            // Detach from all roles
            $permission->roles()->detach();

            // Detach from all users
            $permission->users()->detach();

            // Delete the permission
            $permission->delete();

            DB::commit();

            $this->clearCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Assign a permission to a role.
     *
     * @param int $roleId Role ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function assignToRole(int $roleId, int|string $permission): bool
    {
        $permissionModel = is_int($permission) ? $this->find($permission) : $this->findByName($permission);

        if (!$permissionModel) {
            throw new InvalidArgumentException('Permission not found');
        }

        DB::table(config('canvastack-rbac.tables.permission_role'))
            ->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionModel->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

        $this->clearRoleCache($roleId);

        return true;
    }

    /**
     * Remove a permission from a role.
     *
     * @param int $roleId Role ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function removeFromRole(int $roleId, int|string $permission): bool
    {
        $permissionModel = is_int($permission) ? $this->find($permission) : $this->findByName($permission);

        if (!$permissionModel) {
            throw new InvalidArgumentException('Permission not found');
        }

        DB::table(config('canvastack-rbac.tables.permission_role'))
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionModel->id)
            ->delete();

        $this->clearRoleCache($roleId);

        return true;
    }

    /**
     * Assign a permission directly to a user.
     *
     * @param int $userId User ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function assignToUser(int $userId, int|string $permission): bool
    {
        $permissionModel = is_int($permission) ? $this->find($permission) : $this->findByName($permission);

        if (!$permissionModel) {
            throw new InvalidArgumentException('Permission not found');
        }

        DB::table(config('canvastack-rbac.tables.permission_user'))
            ->updateOrInsert(
                ['user_id' => $userId, 'permission_id' => $permissionModel->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Remove a permission from a user.
     *
     * @param int $userId User ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function removeFromUser(int $userId, int|string $permission): bool
    {
        $permissionModel = is_int($permission) ? $this->find($permission) : $this->findByName($permission);

        if (!$permissionModel) {
            throw new InvalidArgumentException('Permission not found');
        }

        DB::table(config('canvastack-rbac.tables.permission_user'))
            ->where('user_id', $userId)
            ->where('permission_id', $permissionModel->id)
            ->delete();

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Get permissions for a role.
     *
     * @param int $roleId Role ID
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getRolePermissions(int $roleId, bool $useCache = true): Collection
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return $this->fetchRolePermissions($roleId);
        }

        $cacheKey = $this->getCacheKey("role.{$roleId}.permissions");

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($roleId) {
                return $this->fetchRolePermissions($roleId);
            });
    }

    /**
     * Get permissions for a user (from roles + direct permissions).
     *
     * @param int $userId User ID
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getUserPermissions(int $userId, bool $useCache = true): Collection
    {
        if (!$useCache || !$this->isCacheEnabled()) {
            return $this->fetchUserPermissions($userId);
        }

        $cacheKey = $this->getCacheKey("user.{$userId}.permissions");

        return Cache::tags($this->getCacheTags('permissions'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($userId) {
                return $this->fetchUserPermissions($userId);
            });
    }

    /**
     * Check if role has a permission.
     *
     * @param int $roleId Role ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function roleHasPermission(int $roleId, int|string $permission): bool
    {
        $permissions = $this->getRolePermissions($roleId);

        if (is_int($permission)) {
            return $permissions->contains('id', $permission);
        }

        return $permissions->contains('name', $permission);
    }

    /**
     * Check if user has a permission (from roles or direct).
     *
     * @param int $userId User ID
     * @param int|string $permission Permission ID or name
     * @return bool
     */
    public function userHasPermission(int $userId, int|string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);

        if (is_int($permission)) {
            return $permissions->contains('id', $permission);
        }

        return $permissions->contains('name', $permission);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param int $userId User ID
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function userHasAnyPermission(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);

        foreach ($permissions as $permission) {
            if (is_int($permission)) {
                if ($userPermissions->contains('id', $permission)) {
                    return true;
                }
            } else {
                if ($userPermissions->contains('name', $permission)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param int $userId User ID
     * @param array<int|string> $permissions Permission IDs or names
     * @return bool
     */
    public function userHasAllPermissions(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);

        foreach ($permissions as $permission) {
            if (is_int($permission)) {
                if (!$userPermissions->contains('id', $permission)) {
                    return false;
                }
            } else {
                if (!$userPermissions->contains('name', $permission)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sync permissions for a role.
     *
     * @param int $roleId Role ID
     * @param array<int> $permissionIds Permission IDs
     * @return bool
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): bool
    {
        DB::table(config('canvastack-rbac.tables.permission_role'))
            ->where('role_id', $roleId)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            DB::table(config('canvastack-rbac.tables.permission_role'))
                ->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->clearRoleCache($roleId);

        return true;
    }

    /**
     * Sync direct permissions for a user.
     *
     * @param int $userId User ID
     * @param array<int> $permissionIds Permission IDs
     * @return bool
     */
    public function syncUserPermissions(int $userId, array $permissionIds): bool
    {
        DB::table(config('canvastack-rbac.tables.permission_user'))
            ->where('user_id', $userId)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            DB::table(config('canvastack-rbac.tables.permission_user'))
                ->insert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Get all modules.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModules(): \Illuminate\Support\Collection
    {
        return Permission::select('module')
            ->distinct()
            ->whereNotNull('module')
            ->orderBy('module')
            ->pluck('module');
    }

    /**
     * Clear all permission cache.
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return Cache::tags($this->getCacheTags('permissions'))->flush();
    }

    /**
     * Clear cache for a specific role.
     *
     * @param int $roleId Role ID
     * @return bool
     */
    public function clearRoleCache(int $roleId): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        $cacheKey = $this->getCacheKey("role.{$roleId}.permissions");

        return Cache::tags($this->getCacheTags('permissions'))->forget($cacheKey);
    }

    /**
     * Clear cache for a specific user.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function clearUserCache(int $userId): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        $cacheKey = $this->getCacheKey("user.{$userId}.permissions");

        return Cache::tags($this->getCacheTags('permissions'))->forget($cacheKey);
    }

    /**
     * Validate permission data.
     *
     * @param array<string, mixed> $data Permission data
     * @param int|null $excludeId Exclude permission ID from unique check
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validatePermissionData(array $data, ?int $excludeId = null): void
    {
        // Only validate name if it's being set (for create or update)
        if (isset($data['name'])) {
            if (empty($data['name'])) {
                throw new InvalidArgumentException('Permission name is required');
            }

            // Check if name is unique
            $query = Permission::where('name', $data['name']);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException('Permission name already exists');
            }
        } elseif ($excludeId === null) {
            // Name is required for create (when excludeId is null)
            throw new InvalidArgumentException('Permission name is required');
        }
    }

    /**
     * Fetch role permissions from database.
     *
     * @param int $roleId Role ID
     * @return Collection
     */
    protected function fetchRolePermissions(int $roleId): Collection
    {
        return Permission::whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    /**
     * Fetch user permissions from database (roles + direct).
     *
     * @param int $userId User ID
     * @return Collection
     */
    protected function fetchUserPermissions(int $userId): Collection
    {
        // Get permission IDs from roles
        $rolePermissionIds = DB::table(config('canvastack-rbac.tables.permission_role'))
            ->join(
                config('canvastack-rbac.tables.role_user'),
                config('canvastack-rbac.tables.permission_role') . '.role_id',
                '=',
                config('canvastack-rbac.tables.role_user') . '.role_id'
            )
            ->where(config('canvastack-rbac.tables.role_user') . '.user_id', $userId)
            ->pluck(config('canvastack-rbac.tables.permission_role') . '.permission_id')
            ->toArray();

        // Get direct permission IDs
        $directPermissionIds = DB::table(config('canvastack-rbac.tables.permission_user'))
            ->where('user_id', $userId)
            ->pluck('permission_id')
            ->toArray();

        // Merge and get unique permission IDs
        $permissionIds = array_unique(array_merge($rolePermissionIds, $directPermissionIds));

        // Return empty collection if no permissions
        if (empty($permissionIds)) {
            return new Collection();
        }

        // Get permissions
        return Permission::whereIn('id', $permissionIds)->get();
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
     * Get cache key.
     *
     * @param string $key Key suffix
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        $prefix = $this->cacheConfig['key_prefix'] ?? 'canvastack:rbac:';

        return $prefix . $key;
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
}

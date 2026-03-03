<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Canvastack\Canvastack\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Role Manager.
 *
 * Manages role CRUD operations, hierarchy, and caching.
 * Provides methods for creating, updating, deleting, and querying roles.
 */
class RoleManager
{
    /**
     * Cache configuration.
     *
     * @var array<string, mixed>
     */
    protected array $cacheConfig;

    /**
     * Role configuration.
     *
     * @var array<string, mixed>
     */
    protected array $roleConfig;

    /**
     * Create a new RoleManager instance.
     */
    public function __construct()
    {
        $this->cacheConfig = config('canvastack-rbac.cache', []);
        $this->roleConfig = config('canvastack-rbac.roles', []);
    }

    /**
     * Get all roles.
     *
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function all(bool $useCache = true): Collection
    {
        // Bypass cache if disabled in config OR if $useCache is false
        if (!$this->isCacheEnabled() || !$useCache) {
            return Role::all();
        }

        $cacheKey = $this->getCacheKey('all');

        return Cache::tags($this->getCacheTags('roles'))
            ->remember($cacheKey, $this->getCacheTtl(), function () {
                return Role::all();
            });
    }

    /**
     * Find a role by ID.
     *
     * @param int $id Role ID
     * @param bool $useCache Whether to use cache
     * @return Role|null
     */
    public function find(int $id, bool $useCache = true): ?Role
    {
        // Bypass cache if disabled in config OR if $useCache is false
        if (!$this->isCacheEnabled() || !$useCache) {
            return Role::find($id);
        }

        $cacheKey = $this->getCacheKey("role.{$id}");

        return Cache::tags($this->getCacheTags('roles'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($id) {
                return Role::find($id);
            });
    }

    /**
     * Find a role by name.
     *
     * @param string $name Role name
     * @param bool $useCache Whether to use cache
     * @return Role|null
     */
    public function findByName(string $name, bool $useCache = true): ?Role
    {
        // Bypass cache if disabled in config OR if $useCache is false
        if (!$this->isCacheEnabled() || !$useCache) {
            return Role::where('name', $name)->first();
        }

        $cacheKey = $this->getCacheKey("role.name.{$name}");

        return Cache::tags($this->getCacheTags('roles'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($name) {
                return Role::where('name', $name)->first();
            });
    }

    /**
     * Create a new role.
     *
     * @param array<string, mixed> $data Role data
     * @return Role
     * @throws InvalidArgumentException
     */
    public function create(array $data): Role
    {
        $this->validateRoleData($data);

        DB::beginTransaction();

        try {
            $role = Role::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'level' => $data['level'] ?? 99,
                'is_system' => $data['is_system'] ?? false,
            ]);

            // Attach permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            DB::commit();

            $this->clearCache();

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Update a role.
     *
     * @param int $id Role ID
     * @param array<string, mixed> $data Role data
     * @return bool
     * @throws InvalidArgumentException
     */
    public function update(int $id, array $data): bool
    {
        $role = Role::findOrFail($id);

        // Prevent updating system roles
        if ($role->is_system && !($data['force'] ?? false)) {
            throw new InvalidArgumentException('Cannot update system role');
        }

        $this->validateRoleData($data, $id);

        DB::beginTransaction();

        try {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'description' => $data['description'] ?? null,
                'level' => $data['level'] ?? null,
            ], fn ($value) => $value !== null);

            $role->update($updateData);

            // Update permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
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
     * Delete a role.
     *
     * @param int $id Role ID
     * @param bool $force Force delete system role
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(int $id, bool $force = false): bool
    {
        $role = Role::findOrFail($id);

        // Prevent deleting system roles
        if ($role->is_system && !$force) {
            throw new InvalidArgumentException('Cannot delete system role');
        }

        DB::beginTransaction();

        try {
            // Detach all permissions
            $role->permissions()->detach();

            // Detach all users
            $role->users()->detach();

            // Delete the role
            $role->delete();

            DB::commit();

            $this->clearCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Assign a role to a user.
     *
     * @param int $userId User ID
     * @param int|string $role Role ID or name
     * @return bool
     */
    public function assignToUser(int $userId, int|string $role): bool
    {
        $roleModel = is_int($role) ? $this->find($role) : $this->findByName($role);

        if (!$roleModel) {
            throw new InvalidArgumentException('Role not found');
        }

        DB::table(config('canvastack-rbac.tables.role_user'))
            ->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleModel->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Remove a role from a user.
     *
     * @param int $userId User ID
     * @param int|string $role Role ID or name
     * @return bool
     */
    public function removeFromUser(int $userId, int|string $role): bool
    {
        $roleModel = is_int($role) ? $this->find($role) : $this->findByName($role);

        if (!$roleModel) {
            throw new InvalidArgumentException('Role not found');
        }

        DB::table(config('canvastack-rbac.tables.role_user'))
            ->where('user_id', $userId)
            ->where('role_id', $roleModel->id)
            ->delete();

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Get roles for a user.
     *
     * @param int $userId User ID
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getUserRoles(int $userId, bool $useCache = true): Collection
    {
        // Bypass cache if disabled in config OR if $useCache is false
        if (!$this->isCacheEnabled() || !$useCache) {
            return $this->fetchUserRoles($userId);
        }

        $cacheKey = $this->getCacheKey("user.{$userId}.roles");

        return Cache::tags($this->getCacheTags('roles'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($userId) {
                return $this->fetchUserRoles($userId);
            });
    }

    /**
     * Check if user has a role.
     *
     * @param int $userId User ID
     * @param int|string $role Role ID or name
     * @return bool
     */
    public function userHasRole(int $userId, int|string $role): bool
    {
        $roles = $this->getUserRoles($userId);

        if (is_int($role)) {
            return $roles->contains('id', $role);
        }

        return $roles->contains('name', $role);
    }

    /**
     * Get role hierarchy level.
     *
     * @param int|string $role Role ID or name
     * @return int
     */
    public function getRoleLevel(int|string $role): int
    {
        $roleModel = is_int($role) ? $this->find($role) : $this->findByName($role);

        if (!$roleModel) {
            throw new InvalidArgumentException('Role not found');
        }

        return $roleModel->level;
    }

    /**
     * Check if role A has higher level than role B.
     *
     * @param int|string $roleA Role A ID or name
     * @param int|string $roleB Role B ID or name
     * @return bool
     */
    public function isHigherLevel(int|string $roleA, int|string $roleB): bool
    {
        $levelA = $this->getRoleLevel($roleA);
        $levelB = $this->getRoleLevel($roleB);

        // Lower level number = higher privilege
        return $levelA < $levelB;
    }

    /**
     * Get system roles.
     *
     * @return Collection
     */
    public function getSystemRoles(): Collection
    {
        return $this->all()->where('is_system', true);
    }

    /**
     * Get custom roles (non-system).
     *
     * @return Collection
     */
    public function getCustomRoles(): Collection
    {
        return $this->all()->where('is_system', false);
    }

    /**
     * Get roles by level range.
     *
     * @param int $minLevel Minimum level (inclusive)
     * @param int|null $maxLevel Maximum level (inclusive, null = no limit)
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getRolesByLevelRange(int $minLevel, ?int $maxLevel = null, bool $useCache = true): Collection
    {
        // Bypass cache if disabled in config OR if $useCache is false
        if (!$this->isCacheEnabled() || !$useCache) {
            return $this->fetchRolesByLevelRange($minLevel, $maxLevel);
        }

        $cacheKey = $this->getCacheKey("roles.level.{$minLevel}.{$maxLevel}");

        return Cache::tags($this->getCacheTags('roles'))
            ->remember($cacheKey, $this->getCacheTtl(), function () use ($minLevel, $maxLevel) {
                return $this->fetchRolesByLevelRange($minLevel, $maxLevel);
            });
    }

    /**
     * Get roles with level equal to or higher privilege than given level.
     * (Level number equal or lower).
     *
     * @param int $level Level threshold
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getRolesWithHigherOrEqualPrivilege(int $level, bool $useCache = true): Collection
    {
        return $this->getRolesByLevelRange(1, $level, $useCache);
    }

    /**
     * Get roles with level equal to or lower privilege than given level.
     * (Level number equal or higher).
     *
     * @param int $level Level threshold
     * @param bool $useCache Whether to use cache
     * @return Collection
     */
    public function getRolesWithLowerOrEqualPrivilege(int $level, bool $useCache = true): Collection
    {
        return $this->getRolesByLevelRange($level, null, $useCache);
    }

    /**
     * Get user's highest privilege level (lowest level number).
     *
     * @param int $userId User ID
     * @return int|null Null if user has no roles
     */
    public function getUserHighestPrivilegeLevel(int $userId): ?int
    {
        $roles = $this->getUserRoles($userId);

        if ($roles->isEmpty()) {
            return null;
        }

        return $roles->min('level');
    }

    /**
     * Check if user can manage another user based on role levels.
     * User can manage if their highest privilege level is higher (lower number).
     *
     * @param int $managerId Manager user ID
     * @param int $targetUserId Target user ID
     * @return bool
     */
    public function canManageUser(int $managerId, int $targetUserId): bool
    {
        $managerLevel = $this->getUserHighestPrivilegeLevel($managerId);
        $targetLevel = $this->getUserHighestPrivilegeLevel($targetUserId);

        // If either user has no roles, cannot manage
        if ($managerLevel === null || $targetLevel === null) {
            return false;
        }

        // Manager must have higher privilege (lower level number)
        return $managerLevel < $targetLevel;
    }

    /**
     * Get users that current user can manage based on role levels.
     *
     * @param int $managerId Manager user ID
     * @return array<int> Array of user IDs that can be managed
     */
    public function getManagedUserIds(int $managerId): array
    {
        $managerLevel = $this->getUserHighestPrivilegeLevel($managerId);

        if ($managerLevel === null) {
            return [];
        }

        // Get all roles with lower privilege (higher level number)
        $manageableRoles = $this->getRolesWithLowerOrEqualPrivilege($managerLevel + 1, true);

        if ($manageableRoles->isEmpty()) {
            return [];
        }

        $roleIds = $manageableRoles->pluck('id')->toArray();

        // Get all users with these roles
        return DB::table(config('canvastack-rbac.tables.role_user'))
            ->whereIn('role_id', $roleIds)
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Sync roles for a user.
     *
     * @param int $userId User ID
     * @param array<int> $roleIds Role IDs
     * @return bool
     */
    public function syncUserRoles(int $userId, array $roleIds): bool
    {
        DB::table(config('canvastack-rbac.tables.role_user'))
            ->where('user_id', $userId)
            ->delete();

        foreach ($roleIds as $roleId) {
            DB::table(config('canvastack-rbac.tables.role_user'))
                ->insert([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->clearUserCache($userId);

        return true;
    }

    /**
     * Clear all role cache.
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return Cache::tags($this->getCacheTags('roles'))->flush();
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

        $cacheKey = $this->getCacheKey("user.{$userId}.roles");

        return Cache::tags($this->getCacheTags('roles'))->forget($cacheKey);
    }

    /**
     * Validate role data.
     *
     * @param array<string, mixed> $data Role data
     * @param int|null $excludeId Exclude role ID from unique check
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateRoleData(array $data, ?int $excludeId = null): void
    {
        // Only validate name if it's being set (for create or update)
        if (isset($data['name'])) {
            if (empty($data['name'])) {
                throw new InvalidArgumentException('Role name is required');
            }

            // Check if name is unique
            $query = Role::where('name', $data['name']);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException('Role name already exists');
            }
        } elseif ($excludeId === null) {
            // Name is required for create (when excludeId is null)
            throw new InvalidArgumentException('Role name is required');
        }

        // Validate level
        if (isset($data['level']) && (!is_int($data['level']) || $data['level'] < 1)) {
            throw new InvalidArgumentException('Role level must be a positive integer');
        }
    }

    /**
     * Fetch user roles from database.
     *
     * @param int $userId User ID
     * @return Collection
     */
    protected function fetchUserRoles(int $userId): Collection
    {
        $roleIds = DB::table(config('canvastack-rbac.tables.role_user'))
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->toArray();

        if (empty($roleIds)) {
            return new Collection();
        }

        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Fetch roles by level range from database.
     *
     * @param int $minLevel Minimum level (inclusive)
     * @param int|null $maxLevel Maximum level (inclusive, null = no limit)
     * @return Collection
     */
    protected function fetchRolesByLevelRange(int $minLevel, ?int $maxLevel = null): Collection
    {
        $query = Role::where('level', '>=', $minLevel);

        if ($maxLevel !== null) {
            $query->where('level', '<=', $maxLevel);
        }

        return $query->orderBy('level', 'asc')->get();
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

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Repositories;

use Canvastack\Canvastack\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * User Repository.
 *
 * Provides data access methods for User model with optimized queries.
 */
class UserRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get all active users.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function getActive(array $columns = ['*']): Collection
    {
        return $this->model->active()->get($columns);
    }

    /**
     * Get all inactive users.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function getInactive(array $columns = ['*']): Collection
    {
        return $this->model->inactive()->get($columns);
    }

    /**
     * Get all verified users.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function getVerified(array $columns = ['*']): Collection
    {
        return $this->model->verified()->get($columns);
    }

    /**
     * Get all unverified users.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function getUnverified(array $columns = ['*']): Collection
    {
        return $this->model->unverified()->get($columns);
    }

    /**
     * Get users with a specific role.
     *
     * @param string $role Role name
     * @param array<string> $columns
     * @return Collection
     */
    public function getByRole(string $role, array $columns = ['*']): Collection
    {
        return $this->model
            ->with('roles') // Eager load roles to avoid N+1
            ->withRole($role)
            ->get($columns);
    }

    /**
     * Get users with a specific permission.
     *
     * @param string $permission Permission name
     * @param array<string> $columns
     * @return Collection
     */
    public function getByPermission(string $permission, array $columns = ['*']): Collection
    {
        return $this->model
            ->with(['permissions', 'roles.permissions']) // Eager load permissions and role permissions
            ->withPermission($permission)
            ->get($columns);
    }

    /**
     * Get users with eager loaded relationships.
     *
     * @param array<string> $relations
     * @param array<string> $columns
     * @return Collection
     */
    public function getAllWithRelations(array $relations, array $columns = ['*']): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Create a new user with hashed password.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        /** @var User $user */
        $user = parent::create($data);

        return $user;
    }

    /**
     * Update a user with optional password hashing.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Activate a user.
     *
     * @param int $id
     * @return bool
     */
    public function activate(int $id): bool
    {
        return $this->update($id, ['active' => true]);
    }

    /**
     * Deactivate a user.
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['active' => false]);
    }

    /**
     * Assign a role to a user.
     *
     * @param int $userId
     * @param string $role Role name
     * @return void
     */
    public function assignRole(int $userId, string $role): void
    {
        $user = $this->model->with('roles')->findOrFail($userId); // Eager load to check existing roles
        $user->assignRole($role);
    }

    /**
     * Remove a role from a user.
     *
     * @param int $userId
     * @param string $role Role name
     * @return void
     */
    public function removeRole(int $userId, string $role): void
    {
        $user = $this->model->with('roles')->findOrFail($userId); // Eager load to check existing roles
        $user->removeRole($role);
    }

    /**
     * Sync roles for a user.
     *
     * @param int $userId
     * @param array<string> $roles Role names
     * @return void
     */
    public function syncRoles(int $userId, array $roles): void
    {
        $user = $this->model->with('roles')->findOrFail($userId); // Eager load existing roles
        $user->syncRoles($roles);
    }

    /**
     * Assign a permission to a user.
     *
     * @param int $userId
     * @param string $permission Permission name
     * @return void
     */
    public function assignPermission(int $userId, string $permission): void
    {
        $user = $this->model->with('permissions')->findOrFail($userId); // Eager load to check existing permissions
        $user->assignPermission($permission);
    }

    /**
     * Remove a permission from a user.
     *
     * @param int $userId
     * @param string $permission Permission name
     * @return void
     */
    public function removePermission(int $userId, string $permission): void
    {
        $user = $this->model->with('permissions')->findOrFail($userId); // Eager load to check existing permissions
        $user->removePermission($permission);
    }

    /**
     * Sync permissions for a user.
     *
     * @param int $userId
     * @param array<string> $permissions Permission names
     * @return void
     */
    public function syncPermissions(int $userId, array $permissions): void
    {
        $user = $this->model->with('permissions')->findOrFail($userId); // Eager load existing permissions
        $user->syncPermissions($permissions);
    }

    /**
     * Check if a user has a role.
     *
     * @param int $userId
     * @param string $role Role name
     * @return bool
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->model->with('roles')->findOrFail($userId); // Eager load roles

        return $user->hasRole($role);
    }

    /**
     * Check if a user has a permission.
     *
     * @param int $userId
     * @param string $permission Permission name
     * @param string|null $context Context (admin, public, etc.)
     * @return bool
     */
    public function hasPermission(int $userId, string $permission, ?string $context = null): bool
    {
        $user = $this->model->with(['permissions', 'roles.permissions'])->findOrFail($userId); // Eager load permissions and role permissions

        return $user->hasPermission($permission, $context);
    }

    /**
     * Get all permissions for a user.
     *
     * @param int $userId
     * @param string|null $context Context (admin, public, etc.)
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(int $userId, ?string $context = null)
    {
        $user = $this->model->with(['permissions', 'roles.permissions'])->findOrFail($userId); // Eager load all permissions

        return $user->getAllPermissions($context);
    }

    /**
     * Search users by name or email.
     *
     * @param string $query Search query
     * @param array<string> $columns
     * @return Collection
     */
    public function search(string $query, array $columns = ['*']): Collection
    {
        return $this->model
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get($columns);
    }

    /**
     * Get users created within a date range.
     *
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param array<string> $columns
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get($columns);
    }

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'inactive' => $this->model->inactive()->count(),
            'verified' => $this->model->verified()->count(),
            'unverified' => $this->model->unverified()->count(),
        ];
    }

    /**
     * Get recently created users.
     *
     * @param int $limit Number of users to retrieve
     * @param array<string> $columns
     * @return Collection
     */
    public function getRecent(int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Paginate users with optional filters.
     *
     * @param int $perPage Number of items per page
     * @param array<string, mixed> $filters Optional filters
     * @param array<string> $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = [], array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        // Eager load relationships to avoid N+1 queries
        $query->with(['roles', 'permissions']);

        // Apply filters
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($filters['role'])) {
            $query->withRole($filters['role']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($perPage, $columns);
    }

    /**
     * Get users with roles and permissions eager loaded.
     * Optimized for displaying user lists with RBAC information.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function getAllWithRolesAndPermissions(array $columns = ['*']): Collection
    {
        return $this->model
            ->with(['roles', 'permissions'])
            ->get($columns);
    }

    /**
     * Find user by ID with roles and permissions eager loaded.
     * Optimized for user detail pages with RBAC information.
     *
     * @param int $id
     * @param array<string> $columns
     * @return User|null
     */
    public function findWithRolesAndPermissions(int $id, array $columns = ['*']): ?User
    {
        /** @var User|null $user */
        $user = $this->model
            ->with(['roles', 'permissions', 'permissionOverrides'])
            ->find($id, $columns);

        return $user;
    }

    /**
     * Get users by multiple roles (OR condition).
     * Optimized with eager loading.
     *
     * @param array<string> $roles Role names
     * @param array<string> $columns
     * @return Collection
     */
    public function getByRoles(array $roles, array $columns = ['*']): Collection
    {
        return $this->model
            ->with('roles')
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->get($columns);
    }

    /**
     * Get users by multiple permissions (OR condition).
     * Optimized with eager loading.
     *
     * @param array<string> $permissions Permission names
     * @param array<string> $columns
     * @return Collection
     */
    public function getByPermissions(array $permissions, array $columns = ['*']): Collection
    {
        return $this->model
            ->with(['permissions', 'roles.permissions'])
            ->where(function ($query) use ($permissions) {
                $query->whereHas('permissions', function ($q) use ($permissions) {
                    $q->whereIn('name', $permissions);
                })->orWhereHas('roles.permissions', function ($q) use ($permissions) {
                    $q->whereIn('name', $permissions);
                });
            })
            ->get($columns);
    }

    /**
     * Bulk assign role to multiple users.
     * Optimized with single query.
     *
     * @param array<int> $userIds
     * @param string $role Role name
     * @return void
     */
    public function bulkAssignRole(array $userIds, string $role): void
    {
        $users = $this->model->with('roles')->findMany($userIds);

        foreach ($users as $user) {
            $user->assignRole($role);
        }
    }

    /**
     * Bulk remove role from multiple users.
     * Optimized with single query.
     *
     * @param array<int> $userIds
     * @param string $role Role name
     * @return void
     */
    public function bulkRemoveRole(array $userIds, string $role): void
    {
        $users = $this->model->with('roles')->findMany($userIds);

        foreach ($users as $user) {
            $user->removeRole($role);
        }
    }

    /**
     * Get user count grouped by role.
     * Optimized with single query.
     *
     * @return array<string, int>
     */
    public function countByRole(): array
    {
        $results = $this->model
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->selectRaw('roles.name, COUNT(DISTINCT users.id) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'name')
            ->toArray();

        return $results;
    }
}

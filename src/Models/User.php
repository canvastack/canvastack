<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model.
 *
 * Represents a user in the system with RBAC support.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'active',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the roles for this user.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            config('canvastack-rbac.tables.role_user', 'role_user'),
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Get the permissions for this user.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            config('canvastack-rbac.tables.permission_user', 'permission_user'),
            'user_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Get the permission overrides for this user.
     *
     * @return HasMany
     */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class, 'user_id');
    }

    /**
     * Get the activity logs for this user.
     *
     * @return HasMany
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Check if user has a role.
     *
     * @param string $role Role name
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array<string> $roles Role names
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param array<string> $roles Role names
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        $count = $this->roles()->whereIn('name', $roles)->count();

        return $count === count($roles);
    }

    /**
     * Check if user has a permission.
     *
     * @param string $permission Permission name
     * @param string|null $context Context (admin, public, etc.)
     * @return bool
     */
    public function hasPermission(string $permission, ?string $context = null): bool
    {
        // Check direct permissions
        $query = $this->permissions()->where('name', $permission);

        if ($context) {
            $query->where('context', $context);
        }

        if ($query->exists()) {
            return true;
        }

        // Check permissions through roles
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission, $context) {
                $query->where('name', $permission);

                if ($context) {
                    $query->where('context', $context);
                }
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array<string> $permissions Permission names
     * @param string|null $context Context (admin, public, etc.)
     * @return bool
     */
    public function hasAnyPermission(array $permissions, ?string $context = null): bool
    {
        // Check direct permissions
        $query = $this->permissions()->whereIn('name', $permissions);

        if ($context) {
            $query->where('context', $context);
        }

        if ($query->exists()) {
            return true;
        }

        // Check permissions through roles
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions, $context) {
                $query->whereIn('name', $permissions);

                if ($context) {
                    $query->where('context', $context);
                }
            })
            ->exists();
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param array<string> $permissions Permission names
     * @param string|null $context Context (admin, public, etc.)
     * @return bool
     */
    public function hasAllPermissions(array $permissions, ?string $context = null): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assign a role to the user.
     *
     * @param string|Role $role Role name or Role model
     * @return void
     */
    public function assignRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (! $this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from the user.
     *
     * @param string|Role $role Role name or Role model
     * @return void
     */
    public function removeRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    /**
     * Sync roles for the user.
     *
     * @param array<string|Role> $roles Role names or Role models
     * @return void
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = collect($roles)->map(function ($role) {
            if ($role instanceof Role) {
                return $role->id;
            }

            return Role::where('name', $role)->firstOrFail()->id;
        })->toArray();

        $this->roles()->sync($roleIds);
    }

    /**
     * Assign a permission to the user.
     *
     * @param string|Permission $permission Permission name or Permission model
     * @return void
     */
    public function assignPermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (! $this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Remove a permission from the user.
     *
     * @param string|Permission $permission Permission name or Permission model
     * @return void
     */
    public function removePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * Sync permissions for the user.
     *
     * @param array<string|Permission> $permissions Permission names or Permission models
     * @return void
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if ($permission instanceof Permission) {
                return $permission->id;
            }

            return Permission::where('name', $permission)->firstOrFail()->id;
        })->toArray();

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get all permissions for the user (direct + through roles).
     *
     * @param string|null $context Context (admin, public, etc.)
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(?string $context = null)
    {
        // Get direct permissions
        $directPermissions = $this->permissions();

        if ($context) {
            $directPermissions->where('permissions.context', $context);
        }

        $directPermissions = $directPermissions->get();

        // Get permissions through roles
        $rolePermissions = Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        });

        if ($context) {
            $rolePermissions->where('permissions.context', $context);
        }

        $rolePermissions = $rolePermissions->get();

        // Merge and remove duplicates
        return $directPermissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope a query to only include verified users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope a query to only include unverified users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope a query to filter by role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role Role name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        });
    }

    /**
     * Scope a query to filter by permission.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $permission Permission name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->where(function ($query) use ($permission) {
            $query->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })->orWhereHas('roles.permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            });
        });
    }

    /**
     * Check if user is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Check if user is verified.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Activate the user.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the user.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Canvastack\Canvastack\Tests\Fixtures\Factories\UserFactory::new();
    }
}

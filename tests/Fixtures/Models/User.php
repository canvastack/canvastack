<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Canvastack\Canvastack\Tests\Fixtures\Factories\UserFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements Authenticatable
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'organization_id',
        'team_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the column name for the password.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        //
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get the permission overrides for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissionOverrides()
    {
        return $this->hasMany(\Canvastack\Canvastack\Models\UserPermissionOverride::class, 'user_id');
    }

    /**
     * Get the roles for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(\Canvastack\Canvastack\Models\Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Get the permissions for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(\Canvastack\Canvastack\Models\Permission::class, 'permission_user', 'user_id', 'permission_id');
    }

    /**
     * Get the posts for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Assign a role to the user.
     *
     * @param \Canvastack\Canvastack\Models\Role|int $role
     * @return void
     */
    public function assignRole($role): void
    {
        $roleId = is_object($role) ? $role->id : $role;
        
        if (!$this->roles()->where('role_id', $roleId)->exists()) {
            $this->roles()->attach($roleId);
        }
    }

    /**
     * Remove a role from the user.
     *
     * @param \Canvastack\Canvastack\Models\Role|int $role
     * @return void
     */
    public function removeRole($role): void
    {
        $roleId = is_object($role) ? $role->id : $role;
        $this->roles()->detach($roleId);
    }

    /**
     * Check if user has a specific role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}

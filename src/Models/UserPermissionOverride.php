<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Permission Override Model.
 *
 * Represents user-specific permission exceptions that override role-based rules.
 * Allows granting or denying access to specific users for specific resources.
 *
 * @property int $id
 * @property int $user_id
 * @property int $permission_id
 * @property string $model_type
 * @property int|null $model_id
 * @property string|null $field_name
 * @property array|null $rule_config
 * @property bool $allowed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Canvastack\Canvastack\Models\User $user
 * @property-read \Canvastack\Canvastack\Models\Permission $permission
 */
class UserPermissionOverride extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_permission_overrides';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'permission_id',
        'model_type',
        'model_id',
        'field_name',
        'rule_config',
        'allowed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rule_config' => 'array',
        'allowed' => 'boolean',
    ];

    /**
     * Get the user that owns the override.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        // Use configured user model or default to test fixture
        $userModel = config('canvastack-rbac.models.user', \Canvastack\Canvastack\Tests\Fixtures\User::class);

        return $this->belongsTo($userModel);
    }

    /**
     * Get the permission that this override applies to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Scope a query to only include overrides for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include overrides for a specific permission.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $permissionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPermission(Builder $query, int $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
    }

    /**
     * Scope a query to only include overrides for a specific model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $modelType
     * @param int|null $modelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel(Builder $query, string $modelType, ?int $modelId = null): Builder
    {
        $query->where('model_type', $modelType);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }
}

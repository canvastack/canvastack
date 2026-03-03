<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Base Model.
 *
 * Provides common functionality for all CanvaStack models.
 * Includes automatic UUID generation, soft deletes support, and common scopes.
 */
abstract class BaseModel extends Model
{
    use HasPermissionScopes;

    /**
     * Indicates if the model should use UUID as primary key.
     *
     * @var bool
     */
    protected bool $useUuid = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->useUuid && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope a query to only include active records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to order by latest.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Scope a query to order by oldest.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldest($query, string $column = 'created_at')
    {
        return $query->orderBy($column, 'asc');
    }

    /**
     * Check if the model uses soft deletes.
     *
     * @return bool
     */
    public function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this));
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Get fillable attributes.
     *
     * @return array<int, string>
     */
    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    /**
     * Get hidden attributes.
     *
     * @return array<int, string>
     */
    public function getHiddenAttributes(): array
    {
        return $this->hidden;
    }

    /**
     * Check if attribute is fillable.
     *
     * @param string $attribute
     * @return bool
     */
    public function isAttributeFillable(string $attribute): bool
    {
        return $this->isFillable($attribute);
    }

    /**
     * Check if attribute is guarded.
     *
     * @param string $attribute
     * @return bool
     */
    public function isAttributeGuarded(string $attribute): bool
    {
        return $this->isGuarded($attribute);
    }
}

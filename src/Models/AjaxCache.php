<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * AjaxCache Model.
 *
 * Represents cached Ajax sync responses for cascading dropdown fields.
 * Provides scopes for querying valid and expired cache entries.
 *
 * @property int $id
 * @property string $cache_key
 * @property string $source_field
 * @property string $source_value
 * @property array $response_data
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AjaxCache extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'form_ajax_cache';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cache_key',
        'source_field',
        'source_value',
        'response_data',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'response_data' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get only valid (non-expired) cache entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Get only expired cache entries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Find by cache key.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $cacheKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCacheKey($query, string $cacheKey)
    {
        return $query->where('cache_key', $cacheKey);
    }

    /**
     * Scope: Find by source field and value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sourceField
     * @param string $sourceValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, string $sourceField, string $sourceValue)
    {
        return $query->where('source_field', $sourceField)
                     ->where('source_value', $sourceValue);
    }

    /**
     * Check if this cache entry is still valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Check if this cache entry has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the response data as an array.
     *
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->response_data ?? [];
    }

    /**
     * Delete all expired cache entries.
     *
     * @return int Number of deleted entries
     */
    public static function deleteExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Delete cache entries for a specific source field.
     *
     * @param string $sourceField
     * @return int Number of deleted entries
     */
    public static function deleteBySourceField(string $sourceField): int
    {
        return static::where('source_field', $sourceField)->delete();
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total' => static::count(),
            'valid' => static::valid()->count(),
            'expired' => static::expired()->count(),
            'oldest' => static::orderBy('created_at', 'asc')->first()?->created_at,
            'newest' => static::orderBy('created_at', 'desc')->first()?->created_at,
        ];
    }
}

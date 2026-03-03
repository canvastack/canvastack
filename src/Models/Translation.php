<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Canvastack\Canvastack\Exceptions\DuplicateTranslationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Translation Model.
 *
 * Stores translations for translatable model attributes.
 * Uses polymorphic relationship to support any model.
 *
 *
 * @property int $id
 * @property string $translatable_type
 * @property int $translatable_id
 * @property string $attribute
 * @property string $locale
 * @property string $value
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Translation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'attribute',
        'locale',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owning translatable model.
     *
     * @return MorphTo
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include translations for a specific locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to only include translations for a specific attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $attribute
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAttribute($query, string $attribute)
    {
        return $query->where('attribute', $attribute);
    }

    /**
     * Scope a query to only include translations for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @param  int  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('translatable_type', $type)
            ->where('translatable_id', $id);
    }

    /**
     * Create a new translation with proper exception handling.
     *
     * This method wraps the create() method to catch unique constraint violations
     * and throw a more descriptive exception.
     *
     * @param  array  $attributes
     * @return static
     *
     * @throws DuplicateTranslationException
     */
    public static function createTranslation(array $attributes): static
    {
        try {
            return static::create($attributes);
        } catch (UniqueConstraintViolationException $e) {
            // Laravel 11+ throws UniqueConstraintViolationException
            throw DuplicateTranslationException::forTranslation(
                $attributes['translatable_type'],
                $attributes['translatable_id'],
                $attributes['attribute'],
                $attributes['locale']
            );
        } catch (QueryException $e) {
            // Check if this is a unique constraint violation (for older Laravel versions)
            // Error code 23000 is for integrity constraint violation
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'unique_translation')) {
                throw DuplicateTranslationException::forTranslation(
                    $attributes['translatable_type'],
                    $attributes['translatable_id'],
                    $attributes['attribute'],
                    $attributes['locale']
                );
            }

            // Re-throw if it's a different error
            throw $e;
        }
    }

    /**
     * Create or update a translation.
     *
     * This is the recommended method for creating translations as it handles
     * both new translations and updates to existing ones.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return static
     */
    public static function createOrUpdateTranslation(array $attributes, array $values = []): static
    {
        return static::updateOrCreate($attributes, $values);
    }
}

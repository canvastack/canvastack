<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Localization;

use Canvastack\Canvastack\Models\Translation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Translatable Trait.
 *
 * Provides translation support for model attributes.
 * Models using this trait can have translatable fields that store
 * content in multiple languages.
 */
trait Translatable
{
    /**
     * Translation cache.
     *
     * @var array<string, array<string, string>>
     */
    protected array $translationCache = [];

    /**
     * Track if trait has been booted.
     *
     * @var bool
     */
    protected static $translatableBooted = false;

    /**
     * Boot the translatable trait.
     *
     * @return void
     */
    public static function bootTranslatable(): void
    {
        // Prevent multiple registrations
        if (static::$translatableBooted) {
            return;
        }

        static::$translatableBooted = true;

        // Clear translation cache when model is saved
        static::saved(function ($model) {
            $model->clearTranslationCache();
        });

        // Delete translations and clear cache when model is deleted
        static::deleted(function ($model) {
            $model->clearTranslationCache();
            $model->deleteTranslations();
        });
    }

    /**
     * Get translatable attributes.
     *
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Check if attribute is translatable.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function isTranslatable(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatableAttributes());
    }

    /**
     * Get translation for an attribute.
     *
     * @param  string  $attribute
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|null
     */
    public function getTranslation(string $attribute, ?string $locale = null, bool $fallback = true): ?string
    {
        if (!$this->isTranslatable($attribute)) {
            return parent::getAttribute($attribute);
        }

        $locale = $locale ?? App::getLocale();

        // Check cache first
        $cacheKey = $this->getTranslationCacheKey($attribute, $locale);
        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        // Try to get from cache
        $translation = Cache::tags(['translations', $this->getTranslationCacheTag()])
            ->remember($cacheKey, 3600, function () use ($attribute, $locale) {
                return $this->translations()
                    ->where('attribute', $attribute)
                    ->where('locale', $locale)
                    ->value('value');
            });

        // Fallback to default locale if not found
        if ($translation === null && $fallback && $locale !== $this->getDefaultLocale()) {
            $translation = $this->getTranslation($attribute, $this->getDefaultLocale(), false);
        }

        // Fallback to original attribute value (use parent to avoid recursion)
        if ($translation === null) {
            $translation = parent::getAttribute($attribute);
        }

        // Cache in memory
        $this->translationCache[$cacheKey] = $translation;

        return $translation;
    }

    /**
     * Set translation for an attribute.
     *
     * @param  string  $attribute
     * @param  string  $value
     * @param  string|null  $locale
     * @return bool
     */
    public function setTranslation(string $attribute, string $value, ?string $locale = null): bool
    {
        if (!$this->isTranslatable($attribute)) {
            return false;
        }

        $locale = $locale ?? App::getLocale();

        // Update or create translation
        $this->translations()->updateOrCreate(
            [
                'attribute' => $attribute,
                'locale' => $locale,
            ],
            [
                'value' => $value,
            ]
        );

        // Clear cache
        $this->clearTranslationCache($attribute, $locale);

        return true;
    }

    /**
     * Set translations for multiple attributes.
     *
     * @param  array<string, string>  $translations
     * @param  string|null  $locale
     * @return void
     */
    public function setTranslations(array $translations, ?string $locale = null): void
    {
        foreach ($translations as $attribute => $value) {
            $this->setTranslation($attribute, $value, $locale);
        }
    }

    /**
     * Get all translations for an attribute.
     *
     * @param  string  $attribute
     * @return array<string, string>
     */
    public function getTranslations(string $attribute): array
    {
        if (!$this->isTranslatable($attribute)) {
            return [];
        }

        return $this->translations()
            ->where('attribute', $attribute)
            ->pluck('value', 'locale')
            ->toArray();
    }

    /**
     * Check if translation exists.
     *
     * @param  string  $attribute
     * @param  string|null  $locale
     * @return bool
     */
    public function hasTranslation(string $attribute, ?string $locale = null): bool
    {
        if (!$this->isTranslatable($attribute)) {
            return false;
        }

        $locale = $locale ?? App::getLocale();

        return $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * Delete translation.
     *
     * @param  string  $attribute
     * @param  string|null  $locale
     * @return bool
     */
    public function deleteTranslation(string $attribute, ?string $locale = null): bool
    {
        if (!$this->isTranslatable($attribute)) {
            return false;
        }

        $locale = $locale ?? App::getLocale();

        $deleted = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->delete();

        // Clear cache
        $this->clearTranslationCache($attribute, $locale);

        return $deleted > 0;
    }

    /**
     * Delete all translations for this model.
     *
     * @return void
     */
    public function deleteTranslations(): void
    {
        $this->translations()->delete();
        $this->clearTranslationCache();
    }

    /**
     * Get translation relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * Get attribute with translation.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // Get the original value first
        $value = parent::getAttribute($key);

        // Don't translate if:
        // 1. Not a translatable attribute
        // 2. Is a relationship
        // 3. Model doesn't exist yet (no ID)
        // 4. We're already in a translation retrieval (prevent recursion)
        if (!$this->isTranslatable($key)
            || $this->isRelation($key)
            || !$this->exists
            || isset($this->translationCache['__retrieving__'])) {
            return $value;
        }

        // Mark that we're retrieving to prevent recursion
        $this->translationCache['__retrieving__'] = true;

        try {
            $translation = $this->getTranslation($key);

            return $translation;
        } finally {
            // Always unset the flag
            unset($this->translationCache['__retrieving__']);
        }
    }

    /**
     * Get translation cache key.
     *
     * @param  string  $attribute
     * @param  string  $locale
     * @return string
     */
    protected function getTranslationCacheKey(string $attribute, string $locale): string
    {
        return sprintf(
            'translation.%s.%s.%s.%s',
            $this->getTable(),
            $this->getKey(),
            $attribute,
            $locale
        );
    }

    /**
     * Get translation cache tag.
     *
     * @return string
     */
    protected function getTranslationCacheTag(): string
    {
        return sprintf('model:%s:%s', $this->getTable(), $this->getKey());
    }

    /**
     * Clear translation cache.
     *
     * @param  string|null  $attribute
     * @param  string|null  $locale
     * @return void
     */
    public function clearTranslationCache(?string $attribute = null, ?string $locale = null): void
    {
        // Clear memory cache
        if ($attribute && $locale) {
            $cacheKey = $this->getTranslationCacheKey($attribute, $locale);
            unset($this->translationCache[$cacheKey]);
        } else {
            $this->translationCache = [];
        }

        // Clear Laravel cache
        Cache::tags(['translations', $this->getTranslationCacheTag()])->flush();
    }

    /**
     * Get default locale.
     *
     * @return string
     */
    protected function getDefaultLocale(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Convert model to array with translations.
     *
     * @return array<string, mixed>
     */
    public function toArrayWithTranslations(): array
    {
        $array = $this->toArray();

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $array[$attribute . '_translations'] = $this->getTranslations($attribute);
        }

        return $array;
    }

    /**
     * Get translated attribute for a specific locale without changing current locale.
     *
     * @param  string  $attribute
     * @param  string  $locale
     * @return string|null
     */
    public function translate(string $attribute, string $locale): ?string
    {
        return $this->getTranslation($attribute, $locale, true);
    }
}

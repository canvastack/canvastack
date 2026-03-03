<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * TranslationCache.
 *
 * Advanced caching system for translations with intelligent invalidation.
 * Supports multiple cache strategies and warming.
 */
class TranslationCache
{
    /**
     * Cache prefix.
     *
     * @var string
     */
    protected string $prefix = 'canvastack.translations';

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $ttl;

    /**
     * Cache enabled.
     *
     * @var bool
     */
    protected bool $enabled;

    /**
     * Cache driver.
     *
     * @var string
     */
    protected string $driver;

    /**
     * Translation loader.
     *
     * @var TranslationLoader
     */
    protected TranslationLoader $loader;

    /**
     * Constructor.
     */
    public function __construct(TranslationLoader $loader)
    {
        $this->loader = $loader;
        $this->ttl = Config::get('canvastack.localization.cache_ttl', 3600);
        $this->enabled = Config::get('canvastack.localization.cache_enabled', true);
        $this->driver = Config::get('canvastack.localization.cache_driver', 'redis');
    }

    /**
     * Get translation from cache or load.
     *
     * @param  string  $locale
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @return string|array<string, mixed>|null
     */
    public function get(string $locale, string $key, array $replace = []): string|array|null
    {
        if (!$this->enabled) {
            return $this->loader->get($locale, $key, $replace);
        }

        $cacheKey = $this->getCacheKey($locale, $key);

        return Cache::tags($this->getTags($locale))->remember(
            $cacheKey,
            $this->ttl,
            fn () => $this->loader->get($locale, $key, $replace)
        );
    }

    /**
     * Get all translations for a locale from cache.
     *
     * @param  string  $locale
     * @param  string|null  $group
     * @return array<string, mixed>
     */
    public function getAll(string $locale, ?string $group = null): array
    {
        if (!$this->enabled) {
            return $this->loader->load($locale, $group);
        }

        $cacheKey = $this->getCacheKey($locale, $group ?? 'all');

        return Cache::tags($this->getTags($locale))->remember(
            $cacheKey,
            $this->ttl,
            fn () => $this->loader->load($locale, $group)
        );
    }

    /**
     * Put translation in cache.
     *
     * @param  string  $locale
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function put(string $locale, string $key, mixed $value): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($locale, $key);

        Cache::tags($this->getTags($locale))->put($cacheKey, $value, $this->ttl);
    }

    /**
     * Check if translation exists in cache.
     *
     * @param  string  $locale
     * @param  string  $key
     * @return bool
     */
    public function has(string $locale, string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $cacheKey = $this->getCacheKey($locale, $key);

        return Cache::tags($this->getTags($locale))->has($cacheKey);
    }

    /**
     * Forget translation from cache.
     *
     * @param  string  $locale
     * @param  string  $key
     * @return void
     */
    public function forget(string $locale, string $key): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($locale, $key);

        Cache::tags($this->getTags($locale))->forget($cacheKey);
    }

    /**
     * Flush cache for a locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function flush(string $locale): void
    {
        if (!$this->enabled) {
            return;
        }

        Cache::tags($this->getTags($locale))->flush();

        // Fire cache cleared event
        event(new \Canvastack\Canvastack\Events\Translation\TranslationCacheCleared($locale));
    }

    /**
     * Flush cache for a group.
     *
     * @param  string  $locale
     * @param  string  $group
     * @return void
     */
    public function flushGroup(string $locale, string $group): void
    {
        if (!$this->enabled) {
            return;
        }

        Cache::tags($this->getGroupTags($locale, $group))->flush();

        // Fire cache cleared event
        event(new \Canvastack\Canvastack\Events\Translation\TranslationCacheCleared($locale));
    }

    /**
     * Flush all translation caches.
     *
     * @return void
     */
    public function flushAll(): void
    {
        if (!$this->enabled) {
            return;
        }

        Cache::tags(['translations'])->flush();

        // Fire cache cleared event (all locales)
        event(new \Canvastack\Canvastack\Events\Translation\TranslationCacheCleared(null));
    }

    /**
     * Clear all translation caches (alias for flushAll).
     *
     * @return void
     */
    public function clear(): void
    {
        $this->flushAll();
    }

    /**
     * Warm cache for a locale.
     *
     * @param  string  $locale
     * @param  array<string>|null  $groups
     * @return int Number of translations cached
     */
    public function warm(string $locale, ?array $groups = null): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $count = 0;

        if ($groups === null) {
            // Warm all groups
            $translations = $this->loader->all($locale);

            foreach ($translations as $group => $items) {
                $count += $this->warmGroup($locale, $group, $items);
            }
        } else {
            // Warm specific groups
            foreach ($groups as $group) {
                $items = $this->loader->load($locale, $group);
                $count += $this->warmGroup($locale, $group, $items);
            }
        }

        return $count;
    }

    /**
     * Warm cache for a group.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  array<string, mixed>  $items
     * @param  string  $prefix
     * @return int
     */
    protected function warmGroup(string $locale, string $group, array $items, string $prefix = ''): int
    {
        $count = 0;

        foreach ($items as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $translationKey = "{$group}.{$fullKey}";

            if (is_array($value)) {
                $count += $this->warmGroup($locale, $group, $value, $fullKey);
            } else {
                $this->put($locale, $translationKey, $value);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'driver' => $this->driver,
            ];
        }

        $locales = array_keys(Config::get('canvastack.localization.available_locales', []));
        $stats = [
            'enabled' => true,
            'driver' => $this->driver,
            'ttl' => $this->ttl,
            'locales' => [],
        ];

        foreach ($locales as $locale) {
            $stats['locales'][$locale] = [
                'cached_keys' => $this->countCachedKeys($locale),
            ];
        }

        return $stats;
    }

    /**
     * Count cached keys for a locale.
     *
     * @param  string  $locale
     * @return int
     */
    protected function countCachedKeys(string $locale): int
    {
        // This is an approximation as Redis doesn't provide exact count
        // In production, you might want to maintain a counter
        return 0; // Placeholder
    }

    /**
     * Get cache key.
     *
     * @param  string  $locale
     * @param  string  $key
     * @return string
     */
    protected function getCacheKey(string $locale, string $key): string
    {
        return "{$this->prefix}.{$locale}.{$key}";
    }

    /**
     * Get cache tags.
     *
     * @param  string  $locale
     * @return array<string>
     */
    protected function getTags(string $locale): array
    {
        return [
            'translations',
            "translations.{$locale}",
        ];
    }

    /**
     * Get group cache tags.
     *
     * @param  string  $locale
     * @param  string  $group
     * @return array<string>
     */
    protected function getGroupTags(string $locale, string $group): array
    {
        return [
            'translations',
            "translations.{$locale}",
            "translations.{$locale}.{$group}",
        ];
    }

    /**
     * Enable cache.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable cache.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get cache TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set cache TTL.
     *
     * @param  int  $ttl
     * @return void
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Get cache driver.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Invalidate cache when translations are updated.
     *
     * @param  string  $locale
     * @param  string|null  $group
     * @return void
     */
    public function invalidate(string $locale, ?string $group = null): void
    {
        if ($group) {
            $this->flushGroup($locale, $group);
        } else {
            $this->flush($locale);
        }
    }

    /**
     * Refresh cache (flush and warm).
     *
     * @param  string  $locale
     * @param  array<string>|null  $groups
     * @return int
     */
    public function refresh(string $locale, ?array $groups = null): int
    {
        if ($groups) {
            foreach ($groups as $group) {
                $this->flushGroup($locale, $group);
            }
        } else {
            $this->flush($locale);
        }

        return $this->warm($locale, $groups);
    }
}

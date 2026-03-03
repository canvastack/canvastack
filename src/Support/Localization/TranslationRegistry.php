<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * TranslationRegistry.
 *
 * Maintains a registry of all translation keys used in the application.
 * Helps track usage, detect missing translations, and manage translation lifecycle.
 */
class TranslationRegistry
{
    /**
     * Registry cache key.
     *
     * @var string
     */
    protected string $cacheKey = 'canvastack.translation_registry';

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $cacheTtl;

    /**
     * Registry data.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $registry = [];

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
        $this->cacheTtl = Config::get('canvastack.localization.cache_ttl', 3600);
        $this->loadRegistry();
    }

    /**
     * Load registry from cache or build it.
     *
     * @return void
     */
    protected function loadRegistry(): void
    {
        if (Cache::has($this->cacheKey)) {
            $this->registry = Cache::get($this->cacheKey);
        } else {
            $this->buildRegistry();
        }
    }

    /**
     * Build registry from all translation files.
     *
     * @return void
     */
    public function buildRegistry(): void
    {
        $this->registry = [];

        $locales = array_keys(Config::get('canvastack.localization.available_locales', []));

        foreach ($locales as $locale) {
            $translations = $this->loader->all($locale);

            foreach ($translations as $group => $items) {
                $this->registerGroup($group, $items, $locale);
            }
        }

        $this->saveRegistry();
    }

    /**
     * Register a translation group.
     *
     * @param  string  $group
     * @param  array<string, mixed>  $items
     * @param  string  $locale
     * @param  string  $prefix
     * @return void
     */
    protected function registerGroup(string $group, array $items, string $locale, string $prefix = ''): void
    {
        foreach ($items as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $registryKey = "{$group}.{$fullKey}";

            if (is_array($value)) {
                $this->registerGroup($group, $value, $locale, $fullKey);
            } else {
                $this->registerKey($registryKey, $locale, $value);
            }
        }
    }

    /**
     * Register a translation key.
     *
     * @param  string  $key
     * @param  string  $locale
     * @param  mixed  $value
     * @return void
     */
    protected function registerKey(string $key, string $locale, mixed $value): void
    {
        if (!isset($this->registry[$key])) {
            $this->registry[$key] = [
                'key' => $key,
                'locales' => [],
                'usage_count' => 0,
                'last_used' => null,
                'created_at' => now()->toDateTimeString(),
            ];
        }

        $this->registry[$key]['locales'][$locale] = [
            'value' => $value,
            'length' => is_string($value) ? strlen($value) : 0,
        ];
    }

    /**
     * Track translation key usage.
     *
     * @param  string  $key
     * @return void
     */
    public function trackUsage(string $key): void
    {
        if (!isset($this->registry[$key])) {
            $this->registry[$key] = [
                'key' => $key,
                'locales' => [],
                'usage_count' => 0,
                'last_used' => null,
                'created_at' => now()->toDateTimeString(),
            ];
        }

        $this->registry[$key]['usage_count']++;
        $this->registry[$key]['last_used'] = now()->toDateTimeString();

        // Save periodically (every 10 uses)
        if ($this->registry[$key]['usage_count'] % 10 === 0) {
            $this->saveRegistry();
        }
    }

    /**
     * Get all registered keys.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->registry;
    }

    /**
     * Get keys for a specific group.
     *
     * @param  string  $group
     * @return array<string, array<string, mixed>>
     */
    public function getGroup(string $group): array
    {
        return array_filter($this->registry, function ($item) use ($group) {
            return str_starts_with($item['key'], $group . '.');
        });
    }

    /**
     * Get key information.
     *
     * @param  string  $key
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->registry[$key] ?? null;
    }

    /**
     * Check if key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->registry[$key]);
    }

    /**
     * Get missing translations for a locale.
     *
     * @param  string  $locale
     * @return array<string>
     */
    public function getMissing(string $locale): array
    {
        $missing = [];

        foreach ($this->registry as $key => $data) {
            if (!isset($data['locales'][$locale])) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Get unused translations (never used).
     *
     * @return array<string>
     */
    public function getUnused(): array
    {
        $unused = [];

        foreach ($this->registry as $key => $data) {
            if ($data['usage_count'] === 0) {
                $unused[] = $key;
            }
        }

        return $unused;
    }

    /**
     * Get rarely used translations (used less than threshold).
     *
     * @param  int  $threshold
     * @return array<string>
     */
    public function getRarelyUsed(int $threshold = 5): array
    {
        $rarelyUsed = [];

        foreach ($this->registry as $key => $data) {
            if ($data['usage_count'] > 0 && $data['usage_count'] < $threshold) {
                $rarelyUsed[] = $key;
            }
        }

        return $rarelyUsed;
    }

    /**
     * Get most used translations.
     *
     * @param  int  $limit
     * @return array<string, array<string, mixed>>
     */
    public function getMostUsed(int $limit = 10): array
    {
        $sorted = $this->registry;

        usort($sorted, function ($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });

        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $locales = array_keys(Config::get('canvastack.localization.available_locales', []));
        $stats = [
            'total_keys' => count($this->registry),
            'total_usage' => 0,
            'unused_keys' => 0,
            'locales' => [],
        ];

        foreach ($this->registry as $data) {
            $stats['total_usage'] += $data['usage_count'];
            if ($data['usage_count'] === 0) {
                $stats['unused_keys']++;
            }
        }

        foreach ($locales as $locale) {
            $stats['locales'][$locale] = [
                'translated' => 0,
                'missing' => 0,
                'coverage' => 0,
            ];

            foreach ($this->registry as $data) {
                if (isset($data['locales'][$locale])) {
                    $stats['locales'][$locale]['translated']++;
                } else {
                    $stats['locales'][$locale]['missing']++;
                }
            }

            if ($stats['total_keys'] > 0) {
                $stats['locales'][$locale]['coverage'] = round(
                    ($stats['locales'][$locale]['translated'] / $stats['total_keys']) * 100,
                    2
                );
            }
        }

        return $stats;
    }

    /**
     * Save registry to cache.
     *
     * @return void
     */
    protected function saveRegistry(): void
    {
        Cache::put($this->cacheKey, $this->registry, $this->cacheTtl);
    }

    /**
     * Clear registry cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
        $this->registry = [];
    }

    /**
     * Export registry to JSON.
     *
     * @param  string  $path
     * @return bool
     */
    public function export(string $path): bool
    {
        $data = [
            'exported_at' => now()->toDateTimeString(),
            'total_keys' => count($this->registry),
            'statistics' => $this->getStatistics(),
            'registry' => $this->registry,
        ];

        return File::put($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Import registry from JSON.
     *
     * @param  string  $path
     * @return bool
     */
    public function import(string $path): bool
    {
        if (!File::exists($path)) {
            return false;
        }

        $data = json_decode(File::get($path), true);

        if (!isset($data['registry'])) {
            return false;
        }

        $this->registry = $data['registry'];
        $this->saveRegistry();

        return true;
    }
}

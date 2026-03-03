<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * TranslationLoader.
 *
 * Loads and caches translation files for improved performance.
 */
class TranslationLoader
{
    /**
     * Translation cache.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $translations = [];

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $cacheTtl;

    /**
     * Cache enabled.
     *
     * @var bool
     */
    protected bool $cacheEnabled;

    /**
     * Translation paths.
     *
     * @var array<string>
     */
    protected array $paths = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cacheTtl = Config::get('canvastack.localization.cache_ttl', 3600);
        $this->cacheEnabled = Config::get('canvastack.localization.cache_enabled', true);
        $this->loadPaths();
    }

    /**
     * Load translation paths.
     *
     * @return void
     */
    protected function loadPaths(): void
    {
        // Check if paths are explicitly set in config (for testing)
        $configPaths = Config::get('canvastack.localization.paths');
        if ($configPaths !== null && is_array($configPaths)) {
            // Use only configured paths (allows empty array for testing)
            foreach ($configPaths as $path) {
                if (File::isDirectory($path)) {
                    $this->paths[] = $path;
                }
            }

            return;
        }

        // Default behavior: load package and application paths
        // Package translations
        $packagePath = __DIR__ . '/../../../resources/lang';
        if (File::isDirectory($packagePath)) {
            $this->paths[] = $packagePath;
        }

        // Application translations (if exists)
        $appLangPath = resource_path('lang/vendor/canvastack');
        if (File::isDirectory($appLangPath)) {
            $this->paths[] = $appLangPath;
        }
    }

    /**
     * Load translations for a locale.
     *
     * @param  string  $locale
     * @param  string|null  $group
     * @return array<string, mixed>
     */
    public function load(string $locale, ?string $group = null): array
    {
        $cacheKey = $this->getCacheKey($locale, $group);

        // Check cache
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Load from files
        $translations = $this->loadFromFiles($locale, $group);

        // Cache translations
        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $translations, $this->cacheTtl);
        }

        return $translations;
    }

    /**
     * Load translations from files.
     *
     * @param  string  $locale
     * @param  string|null  $group
     * @return array<string, mixed>
     */
    protected function loadFromFiles(string $locale, ?string $group = null): array
    {
        $translations = [];

        foreach ($this->paths as $path) {
            $localePath = $path . '/' . $locale;

            if (!File::isDirectory($localePath)) {
                continue;
            }

            if ($group) {
                // Load specific group
                $file = $localePath . '/' . $group . '.php';
                if (File::exists($file)) {
                    $translations = array_merge($translations, require $file);
                }
            } else {
                // Load all groups
                $files = File::files($localePath);
                foreach ($files as $file) {
                    $groupName = $file->getFilenameWithoutExtension();
                    $translations[$groupName] = require $file->getPathname();
                }
            }
        }

        return $translations;
    }

    /**
     * Get translation.
     *
     * @param  string  $locale
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $fallback
     * @return string|array<string, mixed>
     */
    public function get(string $locale, string $key, array $replace = [], ?string $fallback = null): string|array
    {
        // Parse key (format: group.key or group.nested.key)
        $parts = explode('.', $key);
        $group = array_shift($parts);
        $nestedKey = implode('.', $parts);

        // Load group translations
        $translations = $this->load($locale, $group);

        // Get nested value
        $value = $this->getNestedValue($translations, $nestedKey);

        // Use fallback if not found
        if ($value === null && $fallback !== null) {
            return $fallback;
        }

        // Replace placeholders
        if (is_string($value) && !empty($replace)) {
            $value = $this->replacePlaceholders($value, $replace);
        }

        return $value ?? $key;
    }

    /**
     * Get nested value from array.
     *
     * @param  array<string, mixed>  $array
     * @param  string  $key
     * @return mixed
     */
    protected function getNestedValue(array $array, string $key): mixed
    {
        if (empty($key)) {
            return $array;
        }

        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Replace placeholders in translation string.
     *
     * @param  string  $value
     * @param  array<string, mixed>  $replace
     * @return string
     */
    protected function replacePlaceholders(string $value, array $replace): string
    {
        foreach ($replace as $key => $val) {
            $value = str_replace(':' . $key, (string) $val, $value);
        }

        return $value;
    }

    /**
     * Check if translation exists.
     *
     * @param  string  $locale
     * @param  string  $key
     * @return bool
     */
    public function has(string $locale, string $key): bool
    {
        $parts = explode('.', $key);
        $group = array_shift($parts);
        $nestedKey = implode('.', $parts);

        $translations = $this->load($locale, $group);

        return $this->getNestedValue($translations, $nestedKey) !== null;
    }

    /**
     * Get all translations for a locale.
     *
     * @param  string  $locale
     * @return array<string, mixed>
     */
    public function all(string $locale): array
    {
        return $this->load($locale);
    }

    /**
     * Get missing translations.
     *
     * @param  string  $sourceLocale
     * @param  string  $targetLocale
     * @return array<string, array<string>>
     */
    public function getMissing(string $sourceLocale, string $targetLocale): array
    {
        $source = $this->all($sourceLocale);
        $target = $this->all($targetLocale);

        $missing = [];

        foreach ($source as $group => $translations) {
            $targetTranslations = $target[$group] ?? [];
            $missingKeys = $this->findMissingKeys($translations, $targetTranslations);

            if (!empty($missingKeys)) {
                $missing[$group] = $missingKeys;
            }
        }

        return $missing;
    }

    /**
     * Find missing keys recursively.
     *
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @param  string  $prefix
     * @return array<string>
     */
    protected function findMissingKeys(array $source, array $target, string $prefix = ''): array
    {
        $missing = [];

        foreach ($source as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $targetValue = $target[$key] ?? [];
                $missing = array_merge(
                    $missing,
                    $this->findMissingKeys($value, $targetValue, $fullKey)
                );
            } elseif (!isset($target[$key])) {
                $missing[] = $fullKey;
            }
        }

        return $missing;
    }

    /**
     * Clear translation cache.
     *
     * @param  string|null  $locale
     * @param  string|null  $group
     * @return void
     */
    public function clearCache(?string $locale = null, ?string $group = null): void
    {
        if ($locale && $group) {
            Cache::forget($this->getCacheKey($locale, $group));
        } elseif ($locale) {
            Cache::tags(['translations', "locale:{$locale}"])->flush();
        } else {
            Cache::tags(['translations'])->flush();
        }
    }

    /**
     * Get cache key.
     *
     * @param  string  $locale
     * @param  string|null  $group
     * @return string
     */
    protected function getCacheKey(string $locale, ?string $group = null): string
    {
        $key = "canvastack.translations.{$locale}";

        if ($group) {
            $key .= ".{$group}";
        }

        return $key;
    }

    /**
     * Set translation paths (replaces existing paths).
     *
     * @param  array<string>  $paths
     * @return void
     */
    public function setPaths(array $paths): void
    {
        $this->paths = $paths;

        // Clear cache if enabled
        if ($this->cacheEnabled) {
            try {
                $this->clearCache();
            } catch (\Exception $e) {
                // Silently fail if cache tags not supported
            }
        }
    }

    /**
     * Add translation path.
     *
     * @param  string  $path
     * @return void
     */
    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
    }

    /**
     * Get translation paths.
     *
     * @return array<string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get available locales from translation files.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array
    {
        $locales = [];

        foreach ($this->paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $directories = File::directories($path);

            foreach ($directories as $directory) {
                $locale = basename($directory);
                if (!in_array($locale, $locales)) {
                    $locales[] = $locale;
                }
            }
        }

        return $locales;
    }

    /**
     * Get available translation groups for a locale.
     *
     * @param  string  $locale
     * @return array<string>
     */
    public function getGroups(string $locale): array
    {
        $groups = [];

        foreach ($this->paths as $path) {
            $localePath = $path . '/' . $locale;

            if (!File::isDirectory($localePath)) {
                continue;
            }

            $files = File::files($localePath);

            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $group = $file->getBasename('.php');
                    if (!in_array($group, $groups)) {
                        $groups[] = $group;
                    }
                }
            }
        }

        return $groups;
    }
}

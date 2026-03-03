<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * TranslationVersion.
 *
 * Manages translation versioning for tracking changes and rollback capabilities.
 * Supports version history, diffs, and migration between versions.
 */
class TranslationVersion
{
    /**
     * Version storage path.
     *
     * @var string
     */
    protected string $storagePath;

    /**
     * Current version.
     *
     * @var string
     */
    protected string $currentVersion;

    /**
     * Translation loader.
     *
     * @var TranslationLoader
     */
    protected TranslationLoader $loader;

    /**
     * Version metadata cache key.
     *
     * @var string
     */
    protected string $metadataCacheKey = 'canvastack.translation_versions.metadata';

    /**
     * Constructor.
     */
    public function __construct(TranslationLoader $loader)
    {
        $this->loader = $loader;
        $this->storagePath = storage_path('app/translations/versions');
        $this->currentVersion = Config::get('canvastack.localization.version', '1.0.0');
        $this->ensureStorageExists();
    }

    /**
     * Ensure storage directory exists.
     *
     * @return void
     */
    protected function ensureStorageExists(): void
    {
        if (!File::isDirectory($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }
    }

    /**
     * Create a new version snapshot.
     *
     * @param  string  $version
     * @param  string|null  $description
     * @param  array<string>|null  $locales
     * @return bool
     */
    public function createSnapshot(string $version, ?string $description = null, ?array $locales = null): bool
    {
        $locales = $locales ?? array_keys(Config::get('canvastack.localization.available_locales', []));

        $snapshot = [
            'version' => $version,
            'description' => $description,
            'created_at' => now()->toDateTimeString(),
            'created_by' => auth()->user()->name ?? 'system',
            'locales' => [],
        ];

        foreach ($locales as $locale) {
            $snapshot['locales'][$locale] = $this->loader->all($locale);
        }

        $path = $this->getVersionPath($version);

        if (File::put($path, json_encode($snapshot, JSON_PRETTY_PRINT)) === false) {
            return false;
        }

        $this->updateMetadata($version, $description);

        return true;
    }

    /**
     * Load a version snapshot.
     *
     * @param  string  $version
     * @return array<string, mixed>|null
     */
    public function loadSnapshot(string $version): ?array
    {
        $path = $this->getVersionPath($version);

        if (!File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true);
    }

    /**
     * Restore from a version snapshot.
     *
     * @param  string  $version
     * @param  array<string>|null  $locales
     * @return bool
     */
    public function restore(string $version, ?array $locales = null): bool
    {
        $snapshot = $this->loadSnapshot($version);

        if (!$snapshot) {
            return false;
        }

        $locales = $locales ?? array_keys($snapshot['locales']);

        foreach ($locales as $locale) {
            if (!isset($snapshot['locales'][$locale])) {
                continue;
            }

            $this->restoreLocale($locale, $snapshot['locales'][$locale]);
        }

        $this->currentVersion = $version;

        return true;
    }

    /**
     * Restore translations for a locale.
     *
     * @param  string  $locale
     * @param  array<string, mixed>  $translations
     * @return void
     */
    protected function restoreLocale(string $locale, array $translations): void
    {
        $paths = $this->loader->getPaths();
        $targetPath = $paths[0] ?? resource_path('lang');
        $localePath = "{$targetPath}/{$locale}";

        // Ensure locale directory exists
        if (!File::isDirectory($localePath)) {
            File::makeDirectory($localePath, 0755, true);
        }

        // Write translation files
        foreach ($translations as $group => $items) {
            $filePath = "{$localePath}/{$group}.php";
            $content = "<?php\n\nreturn " . var_export($items, true) . ";\n";
            File::put($filePath, $content);
        }
    }

    /**
     * Get version diff.
     *
     * @param  string  $fromVersion
     * @param  string  $toVersion
     * @return array<string, mixed>
     */
    public function diff(string $fromVersion, string $toVersion): array
    {
        $from = $this->loadSnapshot($fromVersion);
        $to = $this->loadSnapshot($toVersion);

        if (!$from || !$to) {
            return [];
        }

        $diff = [
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'locales' => [],
        ];

        $allLocales = array_unique(array_merge(
            array_keys($from['locales']),
            array_keys($to['locales'])
        ));

        foreach ($allLocales as $locale) {
            $fromTranslations = $from['locales'][$locale] ?? [];
            $toTranslations = $to['locales'][$locale] ?? [];

            $diff['locales'][$locale] = $this->diffTranslations($fromTranslations, $toTranslations);
        }

        return $diff;
    }

    /**
     * Diff two translation arrays.
     *
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $to
     * @param  string  $prefix
     * @return array<string, mixed>
     */
    protected function diffTranslations(array $from, array $to, string $prefix = ''): array
    {
        $diff = [
            'added' => [],
            'removed' => [],
            'modified' => [],
        ];

        // Find added and modified
        foreach ($to as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (!isset($from[$key])) {
                    $diff['added'][$fullKey] = $value;
                } else {
                    $nested = $this->diffTranslations($from[$key], $value, $fullKey);
                    $diff['added'] = array_merge($diff['added'], $nested['added']);
                    $diff['removed'] = array_merge($diff['removed'], $nested['removed']);
                    $diff['modified'] = array_merge($diff['modified'], $nested['modified']);
                }
            } else {
                if (!isset($from[$key])) {
                    $diff['added'][$fullKey] = $value;
                } elseif ($from[$key] !== $value) {
                    $diff['modified'][$fullKey] = [
                        'from' => $from[$key],
                        'to' => $value,
                    ];
                }
            }
        }

        // Find removed
        foreach ($from as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (!isset($to[$key])) {
                if (is_array($value)) {
                    $diff['removed'][$fullKey] = $value;
                } else {
                    $diff['removed'][$fullKey] = $value;
                }
            }
        }

        return $diff;
    }

    /**
     * Get all versions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllVersions(): array
    {
        $metadata = $this->getMetadata();

        return $metadata['versions'] ?? [];
    }

    /**
     * Get version metadata.
     *
     * @param  string  $version
     * @return array<string, mixed>|null
     */
    public function getVersionMetadata(string $version): ?array
    {
        $metadata = $this->getMetadata();

        return $metadata['versions'][$version] ?? null;
    }

    /**
     * Get current version.
     *
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    /**
     * Set current version.
     *
     * @param  string  $version
     * @return void
     */
    public function setCurrentVersion(string $version): void
    {
        $this->currentVersion = $version;
    }

    /**
     * Delete a version.
     *
     * @param  string  $version
     * @return bool
     */
    public function deleteVersion(string $version): bool
    {
        $path = $this->getVersionPath($version);

        if (!File::exists($path)) {
            return false;
        }

        File::delete($path);
        $this->removeFromMetadata($version);

        return true;
    }

    /**
     * Export version to file.
     *
     * @param  string  $version
     * @param  string  $exportPath
     * @return bool
     */
    public function export(string $version, string $exportPath): bool
    {
        $snapshot = $this->loadSnapshot($version);

        if (!$snapshot) {
            return false;
        }

        return File::put($exportPath, json_encode($snapshot, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Import version from file.
     *
     * @param  string  $importPath
     * @param  string  $version
     * @return bool
     */
    public function import(string $importPath, string $version): bool
    {
        if (!File::exists($importPath)) {
            return false;
        }

        $snapshot = json_decode(File::get($importPath), true);

        if (!$snapshot || !isset($snapshot['locales'])) {
            return false;
        }

        $snapshot['version'] = $version;
        $path = $this->getVersionPath($version);

        if (File::put($path, json_encode($snapshot, JSON_PRETTY_PRINT)) === false) {
            return false;
        }

        $this->updateMetadata($version, $snapshot['description'] ?? null);

        return true;
    }

    /**
     * Get version path.
     *
     * @param  string  $version
     * @return string
     */
    protected function getVersionPath(string $version): string
    {
        return "{$this->storagePath}/{$version}.json";
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    protected function getMetadata(): array
    {
        if (Cache::has($this->metadataCacheKey)) {
            return Cache::get($this->metadataCacheKey);
        }

        $metadataPath = "{$this->storagePath}/metadata.json";

        if (!File::exists($metadataPath)) {
            return ['versions' => []];
        }

        $metadata = json_decode(File::get($metadataPath), true);
        Cache::put($this->metadataCacheKey, $metadata, 3600);

        return $metadata;
    }

    /**
     * Update metadata.
     *
     * @param  string  $version
     * @param  string|null  $description
     * @return void
     */
    protected function updateMetadata(string $version, ?string $description = null): void
    {
        $metadata = $this->getMetadata();

        $metadata['versions'][$version] = [
            'version' => $version,
            'description' => $description,
            'created_at' => now()->toDateTimeString(),
            'created_by' => auth()->user()->name ?? 'system',
        ];

        $metadataPath = "{$this->storagePath}/metadata.json";
        File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

        Cache::put($this->metadataCacheKey, $metadata, 3600);
    }

    /**
     * Remove from metadata.
     *
     * @param  string  $version
     * @return void
     */
    protected function removeFromMetadata(string $version): void
    {
        $metadata = $this->getMetadata();

        if (isset($metadata['versions'][$version])) {
            unset($metadata['versions'][$version]);

            $metadataPath = "{$this->storagePath}/metadata.json";
            File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

            Cache::put($this->metadataCacheKey, $metadata, 3600);
        }
    }

    /**
     * Clear metadata cache.
     *
     * @return void
     */
    public function clearMetadataCache(): void
    {
        Cache::forget($this->metadataCacheKey);
    }
}

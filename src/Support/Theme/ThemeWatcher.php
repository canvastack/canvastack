<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

/**
 * Theme Watcher.
 *
 * Watches theme files for changes and triggers hot-reload
 * in development environments.
 */
class ThemeWatcher
{
    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Theme manager instance.
     *
     * @var ThemeManager
     */
    protected ThemeManager $manager;

    /**
     * Theme base path.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * File modification times cache.
     *
     * @var array<string, int>
     */
    protected array $modificationTimes = [];

    /**
     * Whether hot-reload is enabled.
     *
     * @var bool
     */
    protected bool $enabled = false;

    /**
     * Create a new theme watcher instance.
     *
     * @param Filesystem $files
     * @param ThemeManager $manager
     * @param string $basePath
     * @param bool|null $enabled
     */
    public function __construct(Filesystem $files, ThemeManager $manager, string $basePath, ?bool $enabled = null)
    {
        $this->files = $files;
        $this->manager = $manager;
        $this->basePath = rtrim($basePath, '/');

        if ($enabled !== null) {
            $this->enabled = $enabled;
        } elseif (function_exists('config') && function_exists('app')) {
            $this->enabled = config('canvastack-ui.theme.hot_reload', false) && app()->environment('local');
        } else {
            $this->enabled = false;
        }
    }

    /**
     * Check if any theme files have been modified.
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $themeFiles = $this->getThemeFiles();

        foreach ($themeFiles as $file) {
            if ($this->isModified($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specific file has been modified.
     *
     * @param string $file
     * @return bool
     */
    protected function isModified(string $file): bool
    {
        if (!$this->files->exists($file)) {
            return false;
        }

        $currentTime = $this->files->lastModified($file);
        $cachedTime = $this->modificationTimes[$file] ?? null;

        if ($cachedTime === null) {
            $this->modificationTimes[$file] = $currentTime;

            return false;
        }

        if ($currentTime > $cachedTime) {
            $this->modificationTimes[$file] = $currentTime;

            return true;
        }

        return false;
    }

    /**
     * Get all theme files to watch.
     *
     * @return array<string>
     */
    protected function getThemeFiles(): array
    {
        if (!$this->files->isDirectory($this->basePath)) {
            return [];
        }

        $files = [];
        $directories = $this->files->directories($this->basePath);

        foreach ($directories as $directory) {
            // Watch theme.json
            $jsonFile = "{$directory}/theme.json";
            if ($this->files->exists($jsonFile)) {
                $files[] = $jsonFile;
            }

            // Watch theme.php
            $phpFile = "{$directory}/theme.php";
            if ($this->files->exists($phpFile)) {
                $files[] = $phpFile;
            }
        }

        return $files;
    }

    /**
     * Reload themes if changes detected.
     *
     * @return bool True if themes were reloaded
     */
    public function reloadIfChanged(): bool
    {
        if (!$this->hasChanges()) {
            return false;
        }

        $this->reload();

        return true;
    }

    /**
     * Reload all themes.
     *
     * @return void
     */
    public function reload(): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::info('Theme watcher: Reloading themes due to file changes');

        // Clear cache and reload themes
        $this->manager->clearCache();
        $this->manager->reload();

        // Reset modification times
        $this->modificationTimes = [];
    }

    /**
     * Watch for changes and reload automatically.
     *
     * @param int $interval Check interval in seconds
     * @param int $duration How long to watch in seconds (0 = infinite)
     * @return void
     */
    public function watch(int $interval = 1, int $duration = 0): void
    {
        if (!$this->enabled) {
            Log::warning('Theme watcher: Hot-reload is disabled');

            return;
        }

        $startTime = time();
        Log::info("Theme watcher: Started watching themes (interval: {$interval}s)");

        while (true) {
            if ($this->reloadIfChanged()) {
                Log::info('Theme watcher: Themes reloaded');
            }

            sleep($interval);

            // Check if duration limit reached
            if ($duration > 0 && (time() - $startTime) >= $duration) {
                Log::info('Theme watcher: Stopped watching (duration limit reached)');
                break;
            }
        }
    }

    /**
     * Enable hot-reload.
     *
     * @return self
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable hot-reload.
     *
     * @return self
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Check if hot-reload is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the base path.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the base path.
     *
     * @param string $path
     * @return self
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = rtrim($path, '/');

        return $this;
    }

    /**
     * Get modification times cache.
     *
     * @return array<string, int>
     */
    public function getModificationTimes(): array
    {
        return $this->modificationTimes;
    }

    /**
     * Clear modification times cache.
     *
     * @return self
     */
    public function clearModificationTimes(): self
    {
        $this->modificationTimes = [];

        return $this;
    }
}

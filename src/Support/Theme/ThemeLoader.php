<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Events\ThemeLoaded;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;

/**
 * Theme Loader.
 *
 * Handles loading theme configurations from various sources
 * including JSON files, PHP arrays, and directories.
 */
class ThemeLoader
{
    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Theme validator instance.
     *
     * @var ThemeValidator
     */
    protected ThemeValidator $validator;

    /**
     * Theme base path.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Supported file extensions.
     *
     * @var array<string>
     */
    protected array $supportedExtensions = ['json', 'php'];

    /**
     * Debug mode flag.
     *
     * @var bool
     */
    protected bool $debug = false;

    /**
     * Debug log messages.
     *
     * @var array<string>
     */
    protected array $debugLog = [];

    /**
     * Create a new theme loader instance.
     *
     * @param string $basePath
     * @param Filesystem|null $files
     * @param ThemeValidator|null $validator
     */
    public function __construct(string $basePath, ?Filesystem $files = null, ?ThemeValidator $validator = null)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->files = $files ?? new Filesystem();
        $this->validator = $validator ?? new ThemeValidator();
        $this->debug = config('canvastack-ui.theme.debug', false);
    }

    /**
     * Enable or disable debug mode.
     *
     * @param bool $enabled
     * @return self
     */
    public function setDebug(bool $enabled): self
    {
        $this->debug = $enabled;

        return $this;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }

        $logMessage = $message;
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context);
        }

        $this->debugLog[] = '[' . date('Y-m-d H:i:s') . '] ' . $logMessage;

        // Also log to Laravel log if available
        if (function_exists('logger')) {
            logger()->debug('ThemeLoader: ' . $message, $context);
        }
    }

    /**
     * Get all debug log messages.
     *
     * @return array<string>
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Clear debug log.
     *
     * @return self
     */
    public function clearDebugLog(): self
    {
        $this->debugLog = [];

        return $this;
    }

    /**
     * Load a theme from a file.
     *
     * @param string $path
     * @return Theme
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function loadFromFile(string $path): Theme
    {
        $this->debug('Loading theme from file', ['path' => $path]);

        if (!$this->files->exists($path)) {
            $this->debug('Theme file not found', ['path' => $path]);

            throw new InvalidArgumentException("Theme file not found: {$path}");
        }

        $extension = $this->files->extension($path);
        $this->debug('Detected file extension', ['extension' => $extension]);

        if (!in_array($extension, $this->supportedExtensions)) {
            $this->debug('Unsupported file extension', [
                'extension' => $extension,
                'supported' => $this->supportedExtensions,
            ]);

            throw new InvalidArgumentException(
                "Unsupported theme file extension: {$extension}. Supported: " .
                implode(', ', $this->supportedExtensions)
            );
        }

        $config = $this->parseFile($path, $extension);
        $this->debug('Parsed theme configuration', ['config_keys' => array_keys($config)]);

        $theme = $this->createThemeFromConfig($config);
        $this->debug('Created theme instance', ['name' => $theme->getName()]);

        // Dispatch theme loaded event
        if (function_exists('event') && function_exists('app')) {
            try {
                event(new ThemeLoaded($theme, 'file'));
                $this->debug('Dispatched ThemeLoaded event', ['source' => 'file']);
            } catch (\Exception $e) {
                $this->debug('Failed to dispatch event', ['error' => $e->getMessage()]);
            }
        }

        return $theme;
    }

    /**
     * Load a theme by name from the base path.
     *
     * @param string $name
     * @return Theme
     * @throws InvalidArgumentException
     */
    public function load(string $name): Theme
    {
        $themePath = $this->getThemePath($name);

        if (!$this->files->exists($themePath)) {
            throw new InvalidArgumentException("Theme not found: {$name}");
        }

        // Try to load theme.json first, then theme.php
        foreach ($this->supportedExtensions as $extension) {
            $configFile = "{$themePath}/theme.{$extension}";

            if ($this->files->exists($configFile)) {
                return $this->loadFromFile($configFile);
            }
        }

        throw new InvalidArgumentException("No theme configuration file found for: {$name}");
    }

    /**
     * Load all themes from the base path.
     *
     * @return array<Theme>
     */
    public function loadAll(): array
    {
        $themes = [];

        // First, load themes from config registry
        $registryThemes = $this->loadFromRegistry();
        $themes = array_merge($themes, $registryThemes);

        // Then, load themes from filesystem
        if ($this->files->isDirectory($this->basePath)) {
            $directories = $this->files->directories($this->basePath);

            foreach ($directories as $directory) {
                $themeName = basename($directory);

                try {
                    $themes[] = $this->load($themeName);
                } catch (InvalidArgumentException $e) {
                    // Skip invalid themes
                    continue;
                }
            }
        }

        return $themes;
    }

    /**
     * Load themes from config registry.
     *
     * @return array<Theme>
     */
    public function loadFromRegistry(): array
    {
        $this->debug('Loading themes from registry');

        // Defensive: check if config() helper exists and is callable
        if (!function_exists('config')) {
            $this->debug('Config helper not available');

            return [];
        }

        try {
            $registry = config('canvastack-ui.theme.registry', []);
            $this->debug('Retrieved registry', ['theme_count' => count($registry)]);
        } catch (\Exception $e) {
            $this->debug('Failed to load registry', ['error' => $e->getMessage()]);

            return [];
        }

        $themes = [];

        foreach ($registry as $index => $themeConfig) {
            try {
                $this->debug('Loading theme from registry', ['index' => $index, 'name' => $themeConfig['name'] ?? 'unknown']);
                $theme = $this->loadFromArray($themeConfig);
                $themes[] = $theme;
                $this->debug('Successfully loaded theme', ['name' => $theme->getName()]);
            } catch (InvalidArgumentException $e) {
                $this->debug('Failed to load theme from registry', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $this->debug('Loaded themes from registry', ['count' => count($themes)]);

        return $themes;
    }

    /**
     * Load a theme from an array configuration.
     *
     * @param array<string, mixed> $config
     * @return Theme
     */
    public function loadFromArray(array $config): Theme
    {
        $theme = $this->createThemeFromConfig($config);

        // Dispatch theme loaded event
        if (function_exists('event') && function_exists('app')) {
            try {
                event(new ThemeLoaded($theme, 'array'));
            } catch (\Exception $e) {
                // Silently fail if event system is not available
            }
        }

        return $theme;
    }

    /**
     * Parse a theme file.
     *
     * @param string $path
     * @param string $extension
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    protected function parseFile(string $path, string $extension): array
    {
        return match ($extension) {
            'json' => $this->parseJsonFile($path),
            'php' => $this->parsePhpFile($path),
            default => throw new RuntimeException("Unsupported file extension: {$extension}"),
        };
    }

    /**
     * Parse a JSON theme file.
     *
     * @param string $path
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    protected function parseJsonFile(string $path): array
    {
        $contents = $this->files->get($path);
        $config = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to parse JSON theme file: ' . json_last_error_msg()
            );
        }

        return $config;
    }

    /**
     * Parse a PHP theme file.
     *
     * @param string $path
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    protected function parsePhpFile(string $path): array
    {
        $config = require $path;

        if (!is_array($config)) {
            throw new RuntimeException('PHP theme file must return an array');
        }

        return $config;
    }

    /**
     * Create a theme instance from configuration.
     *
     * @param array<string, mixed> $config
     * @return Theme
     * @throws InvalidArgumentException
     */
    protected function createThemeFromConfig(array $config): Theme
    {
        $this->debug('Creating theme from config', ['name' => $config['name'] ?? 'unknown']);

        $this->validateConfig($config);

        // Handle two formats:
        // 1. Config with nested 'config' key (file format)
        // 2. Config with all data at root level (registry format)
        $themeConfig = $config['config'] ?? $config;

        $this->debug('Using theme config format', [
            'format' => isset($config['config']) ? 'nested' : 'flat',
            'config_keys' => array_keys($themeConfig),
        ]);

        return new Theme(
            name: $config['name'],
            displayName: $config['display_name'] ?? $config['name'],
            version: $config['version'] ?? '1.0.0',
            author: $config['author'] ?? 'Unknown',
            description: $config['description'] ?? '',
            config: $themeConfig,
            parent: $config['parent'] ?? null
        );
    }

    /**
     * Validate theme configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateConfig(array $config): void
    {
        $this->validator->validateOrFail($config);
    }

    /**
     * Get the full path to a theme directory.
     *
     * @param string $name
     * @return string
     */
    protected function getThemePath(string $name): string
    {
        return "{$this->basePath}/{$name}";
    }

    /**
     * Check if a theme exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        $themePath = $this->getThemePath($name);

        if (!$this->files->exists($themePath)) {
            return false;
        }

        foreach ($this->supportedExtensions as $extension) {
            if ($this->files->exists("{$themePath}/theme.{$extension}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all available theme names.
     *
     * @return array<string>
     */
    public function getAvailableThemes(): array
    {
        if (!$this->files->isDirectory($this->basePath)) {
            return [];
        }

        $themes = [];
        $directories = $this->files->directories($this->basePath);

        foreach ($directories as $directory) {
            $themeName = basename($directory);

            if ($this->exists($themeName)) {
                $themes[] = $themeName;
            }
        }

        return $themes;
    }

    /**
     * Set the base path for themes.
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
     * Get the base path.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the theme validator.
     *
     * @return ThemeValidator
     */
    public function getValidator(): ThemeValidator
    {
        return $this->validator;
    }
}

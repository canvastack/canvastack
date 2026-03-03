<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Canvastack\Canvastack\Events\ThemeChanged;
use Canvastack\Canvastack\Events\ThemeLoaded;
use Canvastack\Canvastack\Events\ThemesReloaded;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Theme Manager.
 *
 * Central manager for theme operations including loading, caching,
 * switching, and applying themes throughout the application.
 */
class ThemeManager
{
    /**
     * Theme repository.
     *
     * @var ThemeRepository
     */
    protected ThemeRepository $repository;

    /**
     * Theme loader.
     *
     * @var ThemeLoader
     */
    protected ThemeLoader $loader;

    /**
     * Theme cache.
     *
     * @var ThemeCache
     */
    protected ThemeCache $themeCache;

    /**
     * Theme compiler.
     *
     * @var ThemeCompiler|null
     */
    protected ?ThemeCompiler $compiler = null;

    /**
     * Theme watcher.
     *
     * @var ThemeWatcher|null
     */
    protected ?ThemeWatcher $watcher = null;

    /**
     * CSS injector.
     *
     * @var CssInjector|null
     */
    protected ?CssInjector $cssInjector = null;

    /**
     * Font loader.
     *
     * @var FontLoader|null
     */
    protected ?FontLoader $fontLoader = null;

    /**
     * Current active theme.
     *
     * @var ThemeInterface|null
     */
    protected ?ThemeInterface $currentTheme = null;

    /**
     * Cache repository.
     *
     * @var CacheRepository|null
     */
    protected ?CacheRepository $cache = null;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected string $cachePrefix = 'canvastack.theme';

    /**
     * Create a new theme manager instance.
     *
     * @param ThemeRepository $repository
     * @param ThemeLoader $loader
     * @param ThemeCache|null $themeCache
     */
    public function __construct(ThemeRepository $repository, ThemeLoader $loader, ?ThemeCache $themeCache = null)
    {
        $this->repository = $repository;
        $this->loader = $loader;
        $this->themeCache = $themeCache ?? new ThemeCache();
    }

    /**
     * Initialize the theme manager.
     *
     * @return self
     */
    public function initialize(): self
    {
        // Load all available themes
        $this->loadThemes();

        // If no themes loaded, create a default theme
        if ($this->repository->count() === 0) {
            $this->createDefaultTheme();
        }

        // Set the current theme from config or default
        $themeName = config('canvastack-ui.theme.active', 'default');

        if ($this->repository->has($themeName)) {
            $this->setCurrentTheme($themeName);
        } else {
            // Fallback to first available theme or default
            $names = $this->repository->names();
            if (!empty($names)) {
                $this->setCurrentTheme($names[0]);
            } else {
                $this->setCurrentTheme('default');
            }
        }

        return $this;
    }

    /**
     * Create a default theme if none exists.
     *
     * @return void
     */
    protected function createDefaultTheme(): void
    {
        $defaultTheme = new Theme(
            name: 'default',
            displayName: 'Default Theme',
            version: '1.0.0',
            author: 'CanvaStack',
            description: 'Default CanvaStack theme',
            config: [
                'name' => 'default',
                'display_name' => 'Default Theme',
                'version' => '1.0.0',
                'author' => 'CanvaStack',
                'description' => 'Default CanvaStack theme',
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                    'background' => '#ffffff',
                    'text' => '#111827',
                ],
                'fonts' => [
                    'sans' => 'Inter, system-ui, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'container' => '1280px',
                    'spacing' => '1rem',
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'default' => 'light',
                ],
            ]
        );

        $this->repository->register($defaultTheme);
    }

    /**
     * Load all themes from the themes directory.
     *
     * @return self
     */
    public function loadThemes(): self
    {
        // Try to load from cache first
        $cachedThemes = $this->themeCache->getAll();

        if (!empty($cachedThemes)) {
            $this->repository->registerMany($cachedThemes);
            $this->repository->resolveAllInheritance();

            return $this;
        }

        // Load from filesystem and registry
        $themes = $this->loader->loadAll();
        $this->repository->registerMany($themes);

        // Resolve theme inheritance
        $this->repository->resolveAllInheritance();

        // Cache the themes
        $this->themeCache->putAll($themes);

        return $this;
    }

    /**
     * Get the current active theme.
     *
     * @return ThemeInterface
     */
    public function current(): ThemeInterface
    {
        if ($this->currentTheme === null) {
            $this->currentTheme = $this->repository->getDefault();
        }

        return $this->currentTheme;
    }

    /**
     * Set the current active theme.
     *
     * @param string $name
     * @return self
     * @throws InvalidArgumentException
     */
    public function setCurrentTheme(string $name): self
    {
        $previousTheme = $this->currentTheme;
        $this->currentTheme = $this->repository->get($name);

        // Dispatch theme changed event
        if (function_exists('event')) {
            event(new ThemeChanged($this->currentTheme, $previousTheme));
        }

        return $this;
    }

    /**
     * Get a theme by name.
     *
     * @param string $name
     * @return ThemeInterface
     */
    public function get(string $name): ThemeInterface
    {
        return $this->repository->get($name);
    }

    /**
     * Check if a theme exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->repository->has($name);
    }

    /**
     * Get all registered themes.
     *
     * @return array<ThemeInterface>
     */
    public function all(): array
    {
        return $this->repository->all()->toArray();
    }

    /**
     * Get all theme names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return $this->repository->names();
    }

    /**
     * Register a new theme.
     *
     * @param ThemeInterface $theme
     * @return self
     */
    public function register(ThemeInterface $theme): self
    {
        $this->repository->register($theme);
        $this->clearCache();

        // Dispatch theme loaded event
        if (function_exists('event')) {
            event(new ThemeLoaded($theme, 'manual'));
        }

        return $this;
    }

    /**
     * Load a theme from file and register it.
     *
     * @param string $path
     * @return self
     */
    public function loadFromFile(string $path): self
    {
        $theme = $this->loader->loadFromFile($path);
        $this->register($theme);

        return $this;
    }

    /**
     * Load a theme from array and register it.
     *
     * @param array<string, mixed> $config
     * @return self
     */
    public function loadFromArray(array $config): self
    {
        $theme = $this->loader->loadFromArray($config);
        $this->register($theme);

        return $this;
    }

    /**
     * Get CSS variables for the current theme.
     *
     * @return array<string, string>
     */
    public function getCssVariables(): array
    {
        return $this->current()->getCssVariables();
    }

    /**
     * Generate CSS variable declarations.
     *
     * @param string|null $themeName
     * @return string
     */
    public function generateCss(?string $themeName = null): string
    {
        $theme = $themeName ? $this->get($themeName) : $this->current();

        return $this->getCompiler()->compileToCss($theme);
    }

    /**
     * Get compiled CSS for current theme.
     *
     * @param bool $minify
     * @return string
     */
    public function getCompiledCss(bool $minify = false): string
    {
        return $this->getCompiler()->compileToCss($this->current(), $minify);
    }

    /**
     * Get Tailwind config for current theme.
     *
     * @return array<string, mixed>
     */
    public function getTailwindConfig(): array
    {
        return $this->getCompiler()->compileToTailwindConfig($this->current());
    }

    /**
     * Get JavaScript config for current theme.
     *
     * @return string
     */
    public function getJavaScriptConfig(): string
    {
        return $this->getCompiler()->compileToJavaScript($this->current());
    }

    /**
     * Get a configuration value from the current theme.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return $this->current()->get($key, $default);
    }

    /**
     * Get colors from the current theme.
     *
     * @return array<string, mixed>
     */
    public function colors(): array
    {
        return $this->current()->getColors();
    }

    /**
     * Get fonts from the current theme.
     *
     * @return array<string, mixed>
     */
    public function fonts(): array
    {
        return $this->current()->getFonts();
    }

    /**
     * Get layout configuration from the current theme.
     *
     * @return array<string, mixed>
     */
    public function layout(): array
    {
        return $this->current()->getLayout();
    }

    /**
     * Check if current theme supports dark mode.
     *
     * @return bool
     */
    public function supportsDarkMode(): bool
    {
        return $this->current()->supportsDarkMode();
    }

    /**
     * Set the cache repository.
     *
     * @param CacheRepository $cache
     * @return self
     */
    public function setCache(CacheRepository $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Set the cache TTL.
     *
     * @param int $seconds
     * @return self
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    /**
     * Clear the theme cache.
     *
     * @return self
     */
    public function clearCache(): self
    {
        $this->themeCache->flush();

        return $this;
    }

    /**
     * Reload themes from filesystem.
     *
     * @return self
     */
    public function reload(): self
    {
        $this->clearCache();
        $this->repository->clear();
        $this->loadThemes();

        // Dispatch themes reloaded event
        if (function_exists('event')) {
            try {
                $themeNames = $this->names();
                event(new ThemesReloaded(count($themeNames), $themeNames));
            } catch (\Exception $e) {
                // Event system not available in test environment
            }
        }

        return $this;
    }

    /**
     * Get theme metadata for all themes.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllMetadata(): array
    {
        return $this->repository->getAllMetadata();
    }

    /**
     * Export current theme configuration.
     *
     * @param string $format
     * @return string
     */
    public function export(string $format = 'json'): string
    {
        $theme = $this->current();

        return match ($format) {
            'json' => $theme->toJson(JSON_PRETTY_PRINT),
            'array' => var_export($theme->toArray(), true),
            default => throw new InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * Get the theme repository.
     *
     * @return ThemeRepository
     */
    public function getRepository(): ThemeRepository
    {
        return $this->repository;
    }

    /**
     * Get the theme loader.
     *
     * @return ThemeLoader
     */
    public function getLoader(): ThemeLoader
    {
        return $this->loader;
    }

    /**
     * Get the theme cache.
     *
     * @return ThemeCache
     */
    public function getCache(): ThemeCache
    {
        return $this->themeCache;
    }

    /**
     * Get the theme compiler.
     *
     * @return ThemeCompiler
     */
    public function getCompiler(): ThemeCompiler
    {
        if ($this->compiler === null) {
            $this->compiler = new ThemeCompiler($this->themeCache);
        }

        return $this->compiler;
    }

    /**
     * Set the theme compiler.
     *
     * @param ThemeCompiler $compiler
     * @return self
     */
    public function setCompiler(ThemeCompiler $compiler): self
    {
        $this->compiler = $compiler;

        return $this;
    }

    /**
     * Get the theme watcher.
     *
     * @return ThemeWatcher
     */
    public function getWatcher(): ThemeWatcher
    {
        if ($this->watcher === null) {
            $basePath = function_exists('config')
                ? config('canvastack-ui.theme.path', resource_path('themes'))
                : resource_path('themes');
            $this->watcher = new ThemeWatcher(app('files'), $this, $basePath);
        }

        return $this->watcher;
    }

    /**
     * Set the theme watcher.
     *
     * @param ThemeWatcher $watcher
     * @return self
     */
    public function setWatcher(ThemeWatcher $watcher): self
    {
        $this->watcher = $watcher;

        return $this;
    }

    /**
     * Check for theme changes and reload if needed.
     *
     * @return bool True if themes were reloaded
     */
    public function reloadIfChanged(): bool
    {
        return $this->getWatcher()->reloadIfChanged();
    }

    /**
     * Get the CSS injector.
     *
     * @return CssInjector
     */
    public function getCssInjector(): CssInjector
    {
        if ($this->cssInjector === null) {
            $this->cssInjector = new CssInjector($this->getCompiler()->getVariableGenerator());
        }

        return $this->cssInjector;
    }

    /**
     * Set the CSS injector.
     *
     * @param CssInjector $injector
     * @return self
     */
    public function setCssInjector(CssInjector $injector): self
    {
        $this->cssInjector = $injector;

        return $this;
    }

    /**
     * Get the font loader.
     *
     * @return FontLoader
     */
    public function getFontLoader(): FontLoader
    {
        if ($this->fontLoader === null) {
            $this->fontLoader = new FontLoader();
        }

        return $this->fontLoader;
    }

    /**
     * Set the font loader.
     *
     * @param FontLoader $loader
     * @return self
     */
    public function setFontLoader(FontLoader $loader): self
    {
        $this->fontLoader = $loader;

        return $this;
    }

    /**
     * Inject CSS variables for current theme.
     *
     * @return string
     */
    public function injectCss(): string
    {
        return $this->getCssInjector()->inject($this->current());
    }

    /**
     * Inject font imports for current theme.
     *
     * @return string
     */
    public function injectFonts(): string
    {
        return $this->getFontLoader()->generateImports($this->current());
    }

    /**
     * Inject complete theme (CSS + fonts + JS).
     *
     * @return string
     */
    public function injectComplete(): string
    {
        $output = '';

        // Inject fonts
        $output .= $this->injectFonts() . "\n";

        // Inject CSS with theme switcher
        $output .= $this->getCssInjector()->injectComplete($this->current(), $this->all());

        return $output;
    }
}

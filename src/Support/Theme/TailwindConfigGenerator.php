<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;

/**
 * Tailwind Config Generator.
 *
 * Generates dynamic Tailwind configuration from theme JSON files.
 * Supports multiple themes, custom colors, fonts, and breakpoints.
 */
class TailwindConfigGenerator
{
    /**
     * Theme manager instance.
     *
     * @var ThemeManager
     */
    protected ThemeManager $themeManager;

    /**
     * Theme cache instance.
     *
     * @var ThemeCache
     */
    protected ThemeCache $themeCache;

    /**
     * Cache store for config.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key for Tailwind config.
     *
     * @var string
     */
    protected string $cacheKey = 'canvastack.tailwind.config';

    /**
     * Create a new Tailwind config generator instance.
     *
     * @param ThemeManager $themeManager
     * @param ThemeCache|null $themeCache
     */
    public function __construct(ThemeManager $themeManager, ?ThemeCache $themeCache = null)
    {
        $this->themeManager = $themeManager;
        $this->themeCache = $themeCache ?? new ThemeCache();
        $this->cache = app('cache')->store();
    }

    /**
     * Generate Tailwind configuration for a specific theme.
     *
     * @param string|ThemeInterface $theme
     * @param bool $useCache
     * @return array<string, mixed>
     */
    public function generate(string|ThemeInterface $theme, bool $useCache = true): array
    {
        $themeInstance = is_string($theme) ? $this->themeManager->get($theme) : $theme;
        $themeName = $themeInstance->getName();

        // Try to load from cache
        if ($useCache) {
            $cached = $this->cache->get("{$this->cacheKey}.{$themeName}");
            if ($cached !== null) {
                return $cached;
            }
        }

        // Generate configuration
        $config = $this->generateConfig($themeInstance);

        // Cache the result
        if ($useCache) {
            $this->cache->put("{$this->cacheKey}.{$themeName}", $config, $this->cacheTtl);
        }

        return $config;
    }

    /**
     * Generate Tailwind configuration for all themes.
     *
     * @param bool $useCache
     * @return array<string, mixed>
     */
    public function generateForAllThemes(bool $useCache = true): array
    {
        // Try to load from cache
        if ($useCache) {
            $cached = $this->cache->get("{$this->cacheKey}.all");
            if ($cached !== null) {
                return $cached;
            }
        }

        $themes = $this->themeManager->all();
        $config = $this->generateBaseConfig();

        // Merge colors from all themes
        $allColors = [];
        foreach ($themes as $theme) {
            $themeColors = $this->extractColors($theme);
            $allColors = array_merge($allColors, $themeColors);
        }

        $config['theme']['extend']['colors'] = $allColors;

        // Merge fonts from all themes
        $allFonts = [];
        foreach ($themes as $theme) {
            $themeFonts = $this->extractFonts($theme);
            $allFonts = array_merge($allFonts, $themeFonts);
        }

        if (!empty($allFonts)) {
            $config['theme']['extend']['fontFamily'] = $allFonts;
        }

        // Cache the result
        if ($useCache) {
            $this->cache->put("{$this->cacheKey}.all", $config, $this->cacheTtl);
        }

        return $config;
    }

    /**
     * Generate configuration for a specific theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function generateConfig(ThemeInterface $theme): array
    {
        $config = $this->generateBaseConfig();

        // Add theme-specific colors
        $config['theme']['extend']['colors'] = $this->extractColors($theme);

        // Add theme-specific fonts
        $fonts = $this->extractFonts($theme);
        if (!empty($fonts)) {
            $config['theme']['extend']['fontFamily'] = $fonts;
        }

        // Add theme-specific layout
        $layout = $this->extractLayout($theme);
        if (!empty($layout)) {
            $config['theme']['extend'] = array_merge($config['theme']['extend'], $layout);
        }

        // Add theme-specific breakpoints
        $breakpoints = $this->extractBreakpoints($theme);
        if (!empty($breakpoints)) {
            $config['theme']['screens'] = $breakpoints;
        }

        // Add theme-specific border radius
        $borderRadius = $this->extractBorderRadius($theme);
        if (!empty($borderRadius)) {
            $config['theme']['extend']['borderRadius'] = $borderRadius;
        }

        return $config;
    }

    /**
     * Generate base Tailwind configuration.
     *
     * @return array<string, mixed>
     */
    protected function generateBaseConfig(): array
    {
        return [
            'content' => [
                './resources/**/*.blade.php',
                './resources/**/*.js',
                './resources/**/*.vue',
                './src/**/*.php',
            ],
            'darkMode' => 'class',
            'theme' => [
                'extend' => [],
            ],
            'plugins' => [],
        ];
    }

    /**
     * Extract colors from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function extractColors(ThemeInterface $theme): array
    {
        $colors = $theme->getColors();
        $extracted = [];

        foreach ($colors as $name => $shades) {
            if (is_array($shades)) {
                // Color palette with shades (e.g., primary.500)
                $extracted[$name] = $shades;
            } else {
                // Single color value
                $extracted[$name] = $shades;
            }
        }

        return $extracted;
    }

    /**
     * Extract fonts from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function extractFonts(ThemeInterface $theme): array
    {
        $fonts = $theme->getFonts();
        $extracted = [];

        foreach ($fonts as $name => $value) {
            // Convert comma-separated string to array
            if (is_string($value)) {
                $extracted[$name] = array_map('trim', explode(',', $value));
            } else {
                $extracted[$name] = $value;
            }
        }

        return $extracted;
    }

    /**
     * Extract layout configuration from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function extractLayout(ThemeInterface $theme): array
    {
        $layout = $theme->getLayout();
        $extracted = [];

        // Extract container max width
        if (isset($layout['container_max_width'])) {
            $extracted['maxWidth'] = [
                'container' => $layout['container_max_width'],
            ];
        }

        // Extract spacing if available
        if (isset($layout['spacing'])) {
            $extracted['spacing'] = $layout['spacing'];
        }

        return $extracted;
    }

    /**
     * Extract custom breakpoints from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, string>
     */
    protected function extractBreakpoints(ThemeInterface $theme): array
    {
        $layout = $theme->getLayout();

        if (isset($layout['breakpoints']) && is_array($layout['breakpoints'])) {
            return $layout['breakpoints'];
        }

        // Return default breakpoints if not specified
        return [];
    }

    /**
     * Extract border radius configuration from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, string>
     */
    protected function extractBorderRadius(ThemeInterface $theme): array
    {
        $layout = $theme->getLayout();

        if (isset($layout['border_radius']) && is_array($layout['border_radius'])) {
            return $layout['border_radius'];
        }

        return [];
    }

    /**
     * Generate DaisyUI theme configuration.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    public function generateDaisyUITheme(ThemeInterface $theme): array
    {
        $colors = $theme->getColors();

        return [
            $theme->getName() => [
                'primary' => $colors['primary']['500'] ?? '#6366f1',
                'secondary' => $colors['secondary']['500'] ?? '#8b5cf6',
                'accent' => $colors['accent']['500'] ?? '#a855f7',
                'neutral' => $colors['gray']['800'] ?? '#1f2937',
                'base-100' => '#ffffff',
                'base-200' => $colors['gray']['100'] ?? '#f3f4f6',
                'base-300' => $colors['gray']['200'] ?? '#e5e7eb',
                'info' => $colors['info']['400'] ?? '#60a5fa',
                'success' => $colors['success']['400'] ?? '#34d399',
                'warning' => $colors['warning']['400'] ?? '#fbbf24',
                'error' => $colors['error']['400'] ?? '#f87171',
            ],
        ];
    }

    /**
     * Generate DaisyUI dark theme configuration.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    public function generateDaisyUIDarkTheme(ThemeInterface $theme): array
    {
        $colors = $theme->getColors();

        return [
            $theme->getName() . '-dark' => [
                'primary' => $colors['primary']['500'] ?? '#6366f1',
                'secondary' => $colors['secondary']['500'] ?? '#8b5cf6',
                'accent' => $colors['accent']['500'] ?? '#a855f7',
                'neutral' => $colors['gray']['100'] ?? '#f3f4f6',
                'base-100' => $colors['gray']['950'] ?? '#030712',
                'base-200' => $colors['gray']['900'] ?? '#111827',
                'base-300' => $colors['gray']['800'] ?? '#1f2937',
                'info' => $colors['info']['400'] ?? '#60a5fa',
                'success' => $colors['success']['400'] ?? '#34d399',
                'warning' => $colors['warning']['400'] ?? '#fbbf24',
                'error' => $colors['error']['400'] ?? '#f87171',
            ],
        ];
    }

    /**
     * Generate complete Tailwind config with DaisyUI themes.
     *
     * @param bool $useCache
     * @return array<string, mixed>
     */
    public function generateComplete(bool $useCache = true): array
    {
        $config = $this->generateForAllThemes($useCache);

        // Add DaisyUI configuration
        $themes = $this->themeManager->all();
        $daisyUIThemes = [];

        foreach ($themes as $theme) {
            $daisyUIThemes[] = $this->generateDaisyUITheme($theme);
            if ($theme->supportsDarkMode()) {
                $daisyUIThemes[] = $this->generateDaisyUIDarkTheme($theme);
            }
        }

        $config['daisyui'] = [
            'themes' => $daisyUIThemes,
            'darkTheme' => 'dark',
            'base' => true,
            'styled' => true,
            'utils' => true,
            'logs' => false,
        ];

        return $config;
    }

    /**
     * Export configuration as JavaScript module.
     *
     * @param bool $useCache
     * @return string
     */
    public function exportAsJavaScript(bool $useCache = true): string
    {
        $config = $this->generateComplete($useCache);

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return "export default {$json};";
    }

    /**
     * Export configuration as CommonJS module.
     *
     * @param bool $useCache
     * @return string
     */
    public function exportAsCommonJS(bool $useCache = true): string
    {
        $config = $this->generateComplete($useCache);

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return "module.exports = {$json};";
    }

    /**
     * Clear the configuration cache.
     *
     * @return self
     */
    public function clearCache(): self
    {
        $this->cache->forget($this->cacheKey);

        // Clear individual theme caches
        foreach ($this->themeManager->names() as $themeName) {
            $this->cache->forget("{$this->cacheKey}.{$themeName}");
        }

        return $this;
    }

    /**
     * Set cache TTL.
     *
     * @param int $seconds
     * @return self
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;

        return $this;
    }
}

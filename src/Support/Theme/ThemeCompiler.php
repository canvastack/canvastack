<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Arr;

/**
 * Theme Compiler.
 *
 * Compiles theme configurations into CSS variables, Tailwind config,
 * and other formats needed for runtime theme application.
 */
class ThemeCompiler
{
    /**
     * Theme cache instance.
     *
     * @var ThemeCache|null
     */
    protected ?ThemeCache $cache = null;

    /**
     * CSS variable generator.
     *
     * @var CssVariableGenerator|null
     */
    protected ?CssVariableGenerator $variableGenerator = null;

    /**
     * Gradient generator.
     *
     * @var GradientGenerator|null
     */
    protected ?GradientGenerator $gradientGenerator = null;

    /**
     * Whether to use caching.
     *
     * @var bool
     */
    protected bool $useCache = true;

    /**
     * Create a new theme compiler instance.
     *
     * @param ThemeCache|null $cache
     */
    public function __construct(?ThemeCache $cache = null)
    {
        $this->cache = $cache;
        $this->variableGenerator = new CssVariableGenerator();
        $this->gradientGenerator = new GradientGenerator();
    }

    /**
     * Compile theme to CSS variables.
     *
     * @param ThemeInterface $theme
     * @param bool $minify
     * @return string
     */
    public function compileToCss(ThemeInterface $theme, bool $minify = false): string
    {
        // Check cache first
        if ($this->useCache && $this->cache) {
            $cached = $this->cache->getCompiledCss($theme->getName());
            if ($cached !== null) {
                return $cached;
            }
        }

        $variables = $this->extractCssVariables($theme);
        $css = $this->generateCssFromVariables($variables, $minify);

        // Cache the result
        if ($this->useCache && $this->cache) {
            $this->cache->putCompiledCss($theme->getName(), $css);
        }

        return $css;
    }

    /**
     * Extract CSS variables from theme.
     *
     * @param ThemeInterface $theme
     * @return array<string, string>
     */
    public function extractCssVariables(ThemeInterface $theme): array
    {
        // Check cache first
        if ($this->useCache && $this->cache) {
            $cached = $this->cache->getCssVariables($theme->getName());
            if ($cached !== null) {
                return $cached;
            }
        }

        // Use the CSS variable generator
        $variables = $this->variableGenerator->generate($theme);

        // Cache the result
        if ($this->useCache && $this->cache) {
            $this->cache->putCssVariables($theme->getName(), $variables);
        }

        return $variables;
    }

    /**
     * Generate CSS from variables.
     *
     * @param array<string, string> $variables
     * @param bool $minify
     * @return string
     */
    protected function generateCssFromVariables(array $variables, bool $minify = false): string
    {
        if (empty($variables)) {
            return '';
        }

        $indent = $minify ? '' : '  ';
        $newline = $minify ? '' : "\n";
        $space = $minify ? '' : ' ';

        $css = ":root{$space}{{$newline}";

        foreach ($variables as $name => $value) {
            $css .= "{$indent}{$name}:{$space}{$value};{$newline}";
        }

        $css .= "}{$newline}";

        // Add dark mode variables if needed
        $darkVariables = $this->generateDarkModeVariables($variables);
        if (!empty($darkVariables)) {
            $css .= "{$newline}.dark{$space}{{$newline}";
            foreach ($darkVariables as $name => $value) {
                $css .= "{$indent}{$name}:{$space}{$value};{$newline}";
            }
            $css .= "}{$newline}";
        }

        return $css;
    }

    /**
     * Generate dark mode variables.
     *
     * @param array<string, string> $variables
     * @return array<string, string>
     */
    protected function generateDarkModeVariables(array $variables): array
    {
        // For now, return empty array
        // In the future, this could automatically generate dark mode variants
        return [];
    }

    /**
     * Compile theme to Tailwind config.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    public function compileToTailwindConfig(ThemeInterface $theme): array
    {
        $config = $theme->getConfig();

        return [
            'theme' => [
                'extend' => [
                    'colors' => $this->extractTailwindColors($config),
                    'fontFamily' => $this->extractTailwindFonts($config),
                    'borderRadius' => $this->extractTailwindBorderRadius($config),
                ],
            ],
        ];
    }

    /**
     * Extract Tailwind colors from theme config.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function extractTailwindColors(array $config): array
    {
        return Arr::get($config, 'colors', []);
    }

    /**
     * Extract Tailwind fonts from theme config.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function extractTailwindFonts(array $config): array
    {
        $fonts = Arr::get($config, 'fonts', []);
        $tailwindFonts = [];

        foreach ($fonts as $name => $value) {
            if (is_string($value)) {
                $tailwindFonts[$name] = explode(',', $value);
            }
        }

        return $tailwindFonts;
    }

    /**
     * Extract Tailwind border radius from theme config.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function extractTailwindBorderRadius(array $config): array
    {
        return Arr::get($config, 'layout.border_radius', []);
    }

    /**
     * Compile theme to JavaScript config.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    public function compileToJavaScript(ThemeInterface $theme): string
    {
        $config = [
            'name' => $theme->getName(),
            'displayName' => $theme->getDisplayName(),
            'colors' => $theme->getColors(),
            'fonts' => $theme->getFonts(),
            'darkMode' => $theme->supportsDarkMode(),
        ];

        return 'window.canvastackTheme = ' . json_encode($config, JSON_PRETTY_PRINT) . ';';
    }

    /**
     * Set whether to use caching.
     *
     * @param bool $useCache
     * @return self
     */
    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * Get whether caching is enabled.
     *
     * @return bool
     */
    public function isUsingCache(): bool
    {
        return $this->useCache;
    }

    /**
     * Set the cache instance.
     *
     * @param ThemeCache $cache
     * @return self
     */
    public function setCache(ThemeCache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the cache instance.
     *
     * @return ThemeCache|null
     */
    public function getCache(): ?ThemeCache
    {
        return $this->cache;
    }

    /**
     * Set the CSS variable generator.
     *
     * @param CssVariableGenerator $generator
     * @return self
     */
    public function setVariableGenerator(CssVariableGenerator $generator): self
    {
        $this->variableGenerator = $generator;

        return $this;
    }

    /**
     * Get the CSS variable generator.
     *
     * @return CssVariableGenerator
     */
    public function getVariableGenerator(): CssVariableGenerator
    {
        return $this->variableGenerator;
    }

    /**
     * Set the gradient generator.
     *
     * @param GradientGenerator $generator
     * @return self
     */
    public function setGradientGenerator(GradientGenerator $generator): self
    {
        $this->gradientGenerator = $generator;

        return $this;
    }

    /**
     * Get the gradient generator.
     *
     * @return GradientGenerator
     */
    public function getGradientGenerator(): GradientGenerator
    {
        return $this->gradientGenerator;
    }
}

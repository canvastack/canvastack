<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;

/**
 * Tailwind Theme Plugin.
 *
 * Generates Tailwind plugin code for theme-specific utility classes.
 * Provides utilities like theme-primary, theme-gradient, etc.
 */
class TailwindThemePlugin
{
    /**
     * Theme manager instance.
     *
     * @var ThemeManager
     */
    protected ThemeManager $themeManager;

    /**
     * Create a new Tailwind theme plugin instance.
     *
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Generate plugin code for all themes.
     *
     * @return string
     */
    public function generate(): string
    {
        $themes = $this->themeManager->all();
        $utilities = [];

        foreach ($themes as $theme) {
            $utilities[] = $this->generateThemeUtilities($theme);
        }

        return $this->wrapInPlugin(implode("\n\n", $utilities));
    }

    /**
     * Generate utilities for a specific theme.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateThemeUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $utilities = [];

        // Generate color utilities
        $utilities[] = $this->generateColorUtilities($theme);

        // Generate gradient utilities
        $utilities[] = $this->generateGradientUtilities($theme);

        // Generate background utilities
        $utilities[] = $this->generateBackgroundUtilities($theme);

        // Generate border utilities
        $utilities[] = $this->generateBorderUtilities($theme);

        // Generate text utilities
        $utilities[] = $this->generateTextUtilities($theme);

        return implode("\n", array_filter($utilities));
    }

    /**
     * Generate color utilities.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateColorUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $colors = $theme->getColors();
        $utilities = [];

        // Generate .theme-{name}-primary, .theme-{name}-secondary, etc.
        foreach (['primary', 'secondary', 'accent'] as $colorType) {
            if (isset($colors[$colorType])) {
                $color = is_array($colors[$colorType])
                    ? ($colors[$colorType]['500'] ?? reset($colors[$colorType]))
                    : $colors[$colorType];

                $utilities[] = "      '.theme-{$themeName}-{$colorType}': {";
                $utilities[] = "        color: '{$color}',";
                $utilities[] = '      },';
            }
        }

        return implode("\n", $utilities);
    }

    /**
     * Generate gradient utilities.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateGradientUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $gradients = $theme->get('gradient', []);
        $utilities = [];

        if (empty($gradients)) {
            return '';
        }

        foreach ($gradients as $name => $gradient) {
            $utilities[] = "      '.theme-{$themeName}-gradient-{$name}': {";
            $utilities[] = "        backgroundImage: '{$gradient}',";
            $utilities[] = '      },';
        }

        return implode("\n", $utilities);
    }

    /**
     * Generate background utilities.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateBackgroundUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $colors = $theme->getColors();
        $utilities = [];

        foreach (['primary', 'secondary', 'accent'] as $colorType) {
            if (isset($colors[$colorType])) {
                $color = is_array($colors[$colorType])
                    ? ($colors[$colorType]['500'] ?? reset($colors[$colorType]))
                    : $colors[$colorType];

                $utilities[] = "      '.bg-theme-{$themeName}-{$colorType}': {";
                $utilities[] = "        backgroundColor: '{$color}',";
                $utilities[] = '      },';
            }
        }

        return implode("\n", $utilities);
    }

    /**
     * Generate border utilities.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateBorderUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $colors = $theme->getColors();
        $utilities = [];

        foreach (['primary', 'secondary', 'accent'] as $colorType) {
            if (isset($colors[$colorType])) {
                $color = is_array($colors[$colorType])
                    ? ($colors[$colorType]['500'] ?? reset($colors[$colorType]))
                    : $colors[$colorType];

                $utilities[] = "      '.border-theme-{$themeName}-{$colorType}': {";
                $utilities[] = "        borderColor: '{$color}',";
                $utilities[] = '      },';
            }
        }

        return implode("\n", $utilities);
    }

    /**
     * Generate text utilities.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateTextUtilities(ThemeInterface $theme): string
    {
        $themeName = $theme->getName();
        $colors = $theme->getColors();
        $utilities = [];

        foreach (['primary', 'secondary', 'accent'] as $colorType) {
            if (isset($colors[$colorType])) {
                $color = is_array($colors[$colorType])
                    ? ($colors[$colorType]['500'] ?? reset($colors[$colorType]))
                    : $colors[$colorType];

                $utilities[] = "      '.text-theme-{$themeName}-{$colorType}': {";
                $utilities[] = "        color: '{$color}',";
                $utilities[] = '      },';
            }
        }

        return implode("\n", $utilities);
    }

    /**
     * Wrap utilities in Tailwind plugin function.
     *
     * @param string $utilities
     * @return string
     */
    protected function wrapInPlugin(string $utilities): string
    {
        return <<<JS
const plugin = require('tailwindcss/plugin');

module.exports = plugin(function({ addUtilities }) {
  addUtilities({
{$utilities}
  });
});
JS;
    }

    /**
     * Generate plugin as JavaScript module.
     *
     * @return string
     */
    public function generateAsModule(): string
    {
        $themes = $this->themeManager->all();
        $utilities = [];

        foreach ($themes as $theme) {
            $utilities[] = $this->generateThemeUtilitiesAsObject($theme);
        }

        $utilitiesJson = json_encode(array_merge(...$utilities), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<JS
import plugin from 'tailwindcss/plugin';

export default plugin(function({ addUtilities }) {
  addUtilities({$utilitiesJson});
});
JS;
    }

    /**
     * Generate theme utilities as object.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function generateThemeUtilitiesAsObject(ThemeInterface $theme): array
    {
        $themeName = $theme->getName();
        $colors = $theme->getColors();
        $utilities = [];

        // Color utilities
        foreach (['primary', 'secondary', 'accent'] as $colorType) {
            if (isset($colors[$colorType])) {
                $color = is_array($colors[$colorType])
                    ? ($colors[$colorType]['500'] ?? reset($colors[$colorType]))
                    : $colors[$colorType];

                $utilities[".theme-{$themeName}-{$colorType}"] = ['color' => $color];
                $utilities[".bg-theme-{$themeName}-{$colorType}"] = ['backgroundColor' => $color];
                $utilities[".border-theme-{$themeName}-{$colorType}"] = ['borderColor' => $color];
                $utilities[".text-theme-{$themeName}-{$colorType}"] = ['color' => $color];
            }
        }

        // Gradient utilities
        $gradients = $theme->get('gradient', []);
        foreach ($gradients as $name => $gradient) {
            $utilities[".theme-{$themeName}-gradient-{$name}"] = ['backgroundImage' => $gradient];
        }

        return $utilities;
    }

    /**
     * Save plugin to file.
     *
     * @param string $path
     * @param string $format
     * @return bool
     */
    public function saveToFile(string $path, string $format = 'commonjs'): bool
    {
        $content = $format === 'module'
            ? $this->generateAsModule()
            : $this->generate();

        return file_put_contents($path, $content) !== false;
    }
}

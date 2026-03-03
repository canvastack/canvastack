<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;

/**
 * Font Loader.
 *
 * Handles loading and injection of custom font families from themes.
 */
class FontLoader
{
    /**
     * Supported font providers.
     */
    protected array $providers = [
        'google' => 'https://fonts.googleapis.com/css2',
        'adobe' => 'https://use.typekit.net',
    ];

    /**
     * Generate font import tags for theme.
     */
    public function generateImports(ThemeInterface $theme): string
    {
        $fonts = $theme->getFonts();
        $imports = [];

        foreach ($fonts as $name => $config) {
            if (is_string($config)) {
                // Simple font family string
                $import = $this->generateGoogleFontImport($config);
                if ($import) {
                    $imports[] = $import;
                }
            } elseif (is_array($config) && isset($config['family'])) {
                // Detailed font configuration
                $import = $this->generateFontImport($config);
                if ($import) {
                    $imports[] = $import;
                }
            }
        }

        return implode("\n", array_unique($imports));
    }

    /**
     * Generate Google Fonts import.
     */
    public function generateGoogleFontImport(string $fontFamily, array $weights = []): ?string
    {
        // Extract font name from family string
        $fontName = $this->extractFontName($fontFamily);

        if (!$fontName || $this->isSystemFont($fontName)) {
            return null;
        }

        // Build Google Fonts URL
        $url = $this->buildGoogleFontsUrl($fontName, $weights);

        return "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n" .
               "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n" .
               "<link href=\"{$url}\" rel=\"stylesheet\">";
    }

    /**
     * Generate font import from configuration.
     */
    public function generateFontImport(array $config): ?string
    {
        $provider = $config['provider'] ?? 'google';

        return match ($provider) {
            'google' => isset($config['family'])
                ? $this->generateGoogleFontImport($config['family'], $config['weights'] ?? [])
                : null,
            'adobe' => $this->generateAdobeFontImport($config['kit_id'] ?? null),
            'custom' => $this->generateCustomFontImport($config),
            default => null,
        };
    }

    /**
     * Build Google Fonts URL.
     */
    protected function buildGoogleFontsUrl(string $fontName, array $weights = []): string
    {
        $fontName = str_replace(' ', '+', $fontName);

        if (empty($weights)) {
            $weights = [300, 400, 500, 600, 700, 800, 900];
        }

        $weightsString = implode(';', $weights);

        return "https://fonts.googleapis.com/css2?family={$fontName}:wght@{$weightsString}&display=swap";
    }

    /**
     * Generate Adobe Fonts import.
     */
    protected function generateAdobeFontImport(?string $kitId): ?string
    {
        if (!$kitId) {
            return null;
        }

        return "<link rel=\"stylesheet\" href=\"https://use.typekit.net/{$kitId}.css\">";
    }

    /**
     * Generate custom font import.
     */
    protected function generateCustomFontImport(array $config): ?string
    {
        $url = $config['url'] ?? null;

        if (!$url) {
            return null;
        }

        return "<link rel=\"stylesheet\" href=\"{$url}\">";
    }

    /**
     * Generate @font-face CSS for local fonts.
     */
    public function generateFontFace(array $config): string
    {
        $family = $config['family'] ?? 'Custom Font';
        $src = $config['src'] ?? [];
        $weight = $config['weight'] ?? 400;
        $style = $config['style'] ?? 'normal';
        $display = $config['display'] ?? 'swap';

        if (empty($src)) {
            return '';
        }

        $srcStrings = [];
        foreach ($src as $format => $url) {
            $srcStrings[] = "url('{$url}') format('{$format}')";
        }

        $srcString = implode(",\n       ", $srcStrings);

        return <<<CSS
@font-face {
  font-family: '{$family}';
  src: {$srcString};
  font-weight: {$weight};
  font-style: {$style};
  font-display: {$display};
}
CSS;
    }

    /**
     * Generate preload links for fonts.
     */
    public function generatePreloads(array $fonts): string
    {
        $preloads = [];

        foreach ($fonts as $font) {
            if (is_array($font) && isset($font['preload']) && $font['preload']) {
                $url = $font['url'] ?? null;
                $format = $font['format'] ?? 'woff2';

                if ($url) {
                    $preloads[] = "<link rel=\"preload\" href=\"{$url}\" as=\"font\" type=\"font/{$format}\" crossorigin>";
                }
            }
        }

        return implode("\n", $preloads);
    }

    /**
     * Extract font name from font family string.
     */
    protected function extractFontName(string $fontFamily): ?string
    {
        // Extract first font from comma-separated list
        $fonts = explode(',', $fontFamily);
        $firstFont = trim($fonts[0]);

        // Remove quotes
        $firstFont = trim($firstFont, '"\'');

        return $firstFont ?: null;
    }

    /**
     * Check if font is a system font.
     */
    protected function isSystemFont(string $fontName): bool
    {
        $systemFonts = [
            'system-ui',
            '-apple-system',
            'BlinkMacSystemFont',
            'Segoe UI',
            'Helvetica Neue',
            'Arial',
            'sans-serif',
            'serif',
            'monospace',
            'cursive',
            'fantasy',
        ];

        return in_array($fontName, $systemFonts, true);
    }

    /**
     * Generate complete font loading HTML.
     */
    public function generateComplete(ThemeInterface $theme): string
    {
        $output = '';

        // Generate imports
        $imports = $this->generateImports($theme);
        if ($imports) {
            $output .= $imports . "\n";
        }

        // Generate @font-face rules if any
        $fonts = $theme->getFonts();
        $fontFaces = [];

        foreach ($fonts as $name => $config) {
            if (is_array($config) && isset($config['local']) && $config['local']) {
                $fontFace = $this->generateFontFace($config);
                if ($fontFace) {
                    $fontFaces[] = $fontFace;
                }
            }
        }

        if (!empty($fontFaces)) {
            $output .= "<style>\n" . implode("\n\n", $fontFaces) . "\n</style>";
        }

        return $output;
    }

    /**
     * Get font family CSS value.
     */
    public function getFontFamilyValue(mixed $font): string
    {
        if (is_string($font)) {
            return $font;
        }

        if (is_array($font) && isset($font['family'])) {
            return $font['family'];
        }

        return 'system-ui, -apple-system, sans-serif';
    }

    /**
     * Add provider.
     */
    public function addProvider(string $name, string $url): self
    {
        $this->providers[$name] = $url;

        return $this;
    }

    /**
     * Get providers.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}

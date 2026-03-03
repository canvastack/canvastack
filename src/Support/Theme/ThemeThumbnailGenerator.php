<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;

/**
 * Theme Thumbnail Generator.
 *
 * Generates SVG thumbnails for theme previews showing color palette and gradients.
 */
class ThemeThumbnailGenerator
{
    /**
     * Default thumbnail width.
     *
     * @var int
     */
    protected int $width = 320;

    /**
     * Default thumbnail height.
     *
     * @var int
     */
    protected int $height = 180;

    /**
     * Generate thumbnail SVG for a theme.
     *
     * @param ThemeInterface $theme
     * @param string $variant Thumbnail variant: 'gradient', 'palette', 'split', 'card'
     * @return string SVG markup
     */
    public function generate(ThemeInterface $theme, string $variant = 'gradient'): string
    {
        return match ($variant) {
            'gradient' => $this->generateGradientThumbnail($theme),
            'palette' => $this->generatePaletteThumbnail($theme),
            'split' => $this->generateSplitThumbnail($theme),
            'card' => $this->generateCardThumbnail($theme),
            default => $this->generateGradientThumbnail($theme),
        };
    }

    /**
     * Generate gradient thumbnail showing the theme's primary gradient.
     *
     * @param ThemeInterface $theme
     * @return string SVG markup
     */
    protected function generateGradientThumbnail(ThemeInterface $theme): string
    {
        $gradient = $this->getGradientDefinition($theme);
        $themeName = htmlspecialchars($theme->getDisplayName());

        return <<<SVG
<svg width="{$this->width}" height="{$this->height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    {$gradient}
  </defs>
  <rect width="100%" height="100%" fill="url(#themeGradient)"/>
  <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" 
        fill="white" font-family="Inter, sans-serif" font-size="24" font-weight="700"
        style="text-shadow: 0 2px 8px rgba(0,0,0,0.3);">
    {$themeName}
  </text>
</svg>
SVG;
    }

    /**
     * Generate palette thumbnail showing color swatches.
     *
     * @param ThemeInterface $theme
     * @return string SVG markup
     */
    protected function generatePaletteThumbnail(ThemeInterface $theme): string
    {
        $colors = $theme->getColors();
        $primary = $this->getColorValue($colors, 'primary');
        $secondary = $this->getColorValue($colors, 'secondary');
        $accent = $this->getColorValue($colors, 'accent');
        $success = $this->getColorValue($colors, 'success');
        $warning = $this->getColorValue($colors, 'warning');
        $error = $this->getColorValue($colors, 'error');

        $swatchWidth = $this->width / 3;
        $swatchHeight = $this->height / 2;
        $swatchWidth2x = $swatchWidth * 2;

        return <<<SVG
<svg width="{$this->width}" height="{$this->height}" xmlns="http://www.w3.org/2000/svg">
  <rect x="0" y="0" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$primary}"/>
  <rect x="{$swatchWidth}" y="0" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$secondary}"/>
  <rect x="{$swatchWidth2x}" y="0" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$accent}"/>
  <rect x="0" y="{$swatchHeight}" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$success}"/>
  <rect x="{$swatchWidth}" y="{$swatchHeight}" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$warning}"/>
  <rect x="{$swatchWidth2x}" y="{$swatchHeight}" width="{$swatchWidth}" height="{$swatchHeight}" fill="{$error}"/>
</svg>
SVG;
    }

    /**
     * Generate split thumbnail with gradient and color info.
     *
     * @param ThemeInterface $theme
     * @return string SVG markup
     */
    protected function generateSplitThumbnail(ThemeInterface $theme): string
    {
        $gradient = $this->getGradientDefinition($theme);
        $colors = $theme->getColors();
        $primary = $this->getColorValue($colors, 'primary');
        $secondary = $this->getColorValue($colors, 'secondary');
        $accent = $this->getColorValue($colors, 'accent');
        $themeName = htmlspecialchars($theme->getDisplayName());

        $halfHeight = $this->height / 2;
        $halfHeightDiv2 = $halfHeight / 2;
        $swatchWidth = $this->width / 3;
        $swatchWidth2x = $swatchWidth * 2;

        return <<<SVG
<svg width="{$this->width}" height="{$this->height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    {$gradient}
  </defs>
  <rect width="100%" height="{$halfHeight}" fill="url(#themeGradient)"/>
  <text x="50%" y="{$halfHeightDiv2}" text-anchor="middle" dominant-baseline="middle" 
        fill="white" font-family="Inter, sans-serif" font-size="20" font-weight="700"
        style="text-shadow: 0 2px 8px rgba(0,0,0,0.3);">
    {$themeName}
  </text>
  <rect x="0" y="{$halfHeight}" width="{$swatchWidth}" height="{$halfHeight}" fill="{$primary}"/>
  <rect x="{$swatchWidth}" y="{$halfHeight}" width="{$swatchWidth}" height="{$halfHeight}" fill="{$secondary}"/>
  <rect x="{$swatchWidth2x}" y="{$halfHeight}" width="{$swatchWidth}" height="{$halfHeight}" fill="{$accent}"/>
</svg>
SVG;
    }

    /**
     * Generate card-style thumbnail with UI elements preview.
     *
     * @param ThemeInterface $theme
     * @return string SVG markup
     */
    protected function generateCardThumbnail(ThemeInterface $theme): string
    {
        $colors = $theme->getColors();
        $primary = $this->getColorValue($colors, 'primary');
        $secondary = $this->getColorValue($colors, 'secondary');
        $gray = $this->getColorValue($colors, 'gray', '200');
        $darkGray = $this->getColorValue($colors, 'gray', '800');
        $themeName = htmlspecialchars($theme->getDisplayName());

        return <<<SVG
<svg width="{$this->width}" height="{$this->height}" xmlns="http://www.w3.org/2000/svg">
  <!-- Background -->
  <rect width="100%" height="100%" fill="#f9fafb"/>
  
  <!-- Header -->
  <rect x="0" y="0" width="100%" height="40" fill="{$primary}"/>
  <text x="16" y="25" fill="white" font-family="Inter, sans-serif" font-size="14" font-weight="700">
    {$themeName}
  </text>
  
  <!-- Card -->
  <rect x="16" y="56" width="288" height="100" rx="12" fill="white" stroke="{$gray}" stroke-width="1"/>
  
  <!-- Card Header -->
  <rect x="24" y="64" width="60" height="8" rx="4" fill="{$gray}"/>
  
  <!-- Card Content -->
  <rect x="24" y="80" width="272" height="6" rx="3" fill="{$gray}"/>
  <rect x="24" y="92" width="200" height="6" rx="3" fill="{$gray}"/>
  
  <!-- Buttons -->
  <rect x="24" y="112" width="80" height="32" rx="8" fill="{$primary}"/>
  <rect x="112" y="112" width="80" height="32" rx="8" fill="white" stroke="{$secondary}" stroke-width="2"/>
</svg>
SVG;
    }

    /**
     * Get gradient definition for SVG.
     *
     * @param ThemeInterface $theme
     * @return string SVG gradient definition
     */
    protected function getGradientDefinition(ThemeInterface $theme): string
    {
        $gradient = $theme->get('gradient.primary');

        if ($gradient && str_contains($gradient, 'linear-gradient')) {
            // Parse linear-gradient CSS
            preg_match('/linear-gradient\(([^,]+),\s*([^,]+),\s*([^,]+)(?:,\s*([^)]+))?\)/', $gradient, $matches);

            if (count($matches) >= 4) {
                $color1 = trim($matches[2]);
                $color2 = trim($matches[3]);
                $color3 = $matches[4] ?? $color2;

                return <<<SVG
<linearGradient id="themeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
  <stop offset="0%" style="stop-color:{$color1};stop-opacity:1" />
  <stop offset="50%" style="stop-color:{$color2};stop-opacity:1" />
  <stop offset="100%" style="stop-color:{$color3};stop-opacity:1" />
</linearGradient>
SVG;
            }
        }

        // Fallback: use primary, secondary, accent colors
        $colors = $theme->getColors();
        $primary = $this->getColorValue($colors, 'primary');
        $secondary = $this->getColorValue($colors, 'secondary');
        $accent = $this->getColorValue($colors, 'accent');

        return <<<SVG
<linearGradient id="themeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
  <stop offset="0%" style="stop-color:{$primary};stop-opacity:1" />
  <stop offset="50%" style="stop-color:{$secondary};stop-opacity:1" />
  <stop offset="100%" style="stop-color:{$accent};stop-opacity:1" />
</linearGradient>
SVG;
    }

    /**
     * Get color value from theme colors.
     *
     * @param array<string, mixed> $colors
     * @param string $colorName
     * @param string $shade
     * @return string Color hex value
     */
    protected function getColorValue(array $colors, string $colorName, string $shade = '500'): string
    {
        if (!isset($colors[$colorName])) {
            return '#6366f1'; // Default fallback
        }

        $color = $colors[$colorName];

        // If it's an array (palette), get the specified shade
        if (is_array($color)) {
            return $color[$shade] ?? $color['500'] ?? array_values($color)[0] ?? '#6366f1';
        }

        // If it's a string, return it directly
        return $color;
    }

    /**
     * Set thumbnail dimensions.
     *
     * @param int $width
     * @param int $height
     * @return self
     */
    public function setDimensions(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Generate data URI for inline embedding.
     *
     * @param ThemeInterface $theme
     * @param string $variant
     * @return string Data URI
     */
    public function generateDataUri(ThemeInterface $theme, string $variant = 'gradient'): string
    {
        $svg = $this->generate($theme, $variant);
        $encoded = base64_encode($svg);

        return "data:image/svg+xml;base64,{$encoded}";
    }

    /**
     * Generate thumbnail and save to file.
     *
     * @param ThemeInterface $theme
     * @param string $path
     * @param string $variant
     * @return bool Success status
     */
    public function saveToFile(ThemeInterface $theme, string $path, string $variant = 'gradient'): bool
    {
        $svg = $this->generate($theme, $variant);

        return file_put_contents($path, $svg) !== false;
    }

    /**
     * Generate thumbnails for all variants.
     *
     * @param ThemeInterface $theme
     * @return array<string, string> Variant => SVG markup
     */
    public function generateAll(ThemeInterface $theme): array
    {
        return [
            'gradient' => $this->generateGradientThumbnail($theme),
            'palette' => $this->generatePaletteThumbnail($theme),
            'split' => $this->generateSplitThumbnail($theme),
            'card' => $this->generateCardThumbnail($theme),
        ];
    }
}

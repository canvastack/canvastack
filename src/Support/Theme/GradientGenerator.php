<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

/**
 * Gradient Generator.
 *
 * Utility for generating CSS gradients from theme colors.
 */
class GradientGenerator
{
    /**
     * Generate linear gradient.
     */
    public function linear(array $colors, int $angle = 135): string
    {
        $colorStops = $this->formatColorStops($colors);

        return "linear-gradient({$angle}deg, {$colorStops})";
    }

    /**
     * Generate radial gradient.
     */
    public function radial(array $colors, string $shape = 'circle', string $position = 'center'): string
    {
        $colorStops = $this->formatColorStops($colors);

        return "radial-gradient({$shape} at {$position}, {$colorStops})";
    }

    /**
     * Generate conic gradient.
     */
    public function conic(array $colors, int $angle = 0, string $position = 'center'): string
    {
        $colorStops = $this->formatColorStops($colors);

        return "conic-gradient(from {$angle}deg at {$position}, {$colorStops})";
    }

    /**
     * Generate gradient from theme colors.
     */
    public function fromTheme(array $themeColors, string $type = 'linear', array $options = []): string
    {
        $colors = $this->extractColors($themeColors);

        return match ($type) {
            'linear' => $this->linear($colors, $options['angle'] ?? 135),
            'radial' => $this->radial($colors, $options['shape'] ?? 'circle', $options['position'] ?? 'center'),
            'conic' => $this->conic($colors, $options['angle'] ?? 0, $options['position'] ?? 'center'),
            default => $this->linear($colors),
        };
    }

    /**
     * Generate gradient with specific stops.
     */
    public function withStops(array $colorStops, string $type = 'linear', array $options = []): string
    {
        $stops = [];
        foreach ($colorStops as $color => $position) {
            $stops[] = "{$color} {$position}";
        }

        $stopsString = implode(', ', $stops);

        return match ($type) {
            'linear' => 'linear-gradient(' . ($options['angle'] ?? 135) . "deg, {$stopsString})",
            'radial' => 'radial-gradient(' . ($options['shape'] ?? 'circle') . ' at ' . ($options['position'] ?? 'center') . ", {$stopsString})",
            'conic' => 'conic-gradient(from ' . ($options['angle'] ?? 0) . 'deg at ' . ($options['position'] ?? 'center') . ", {$stopsString})",
            default => "linear-gradient(135deg, {$stopsString})",
        };
    }

    /**
     * Generate two-color gradient.
     */
    public function twoColor(string $startColor, string $endColor, int $angle = 135): string
    {
        return $this->linear([$startColor, $endColor], $angle);
    }

    /**
     * Generate three-color gradient.
     */
    public function threeColor(string $startColor, string $midColor, string $endColor, int $angle = 135): string
    {
        return $this->linear([$startColor, $midColor, $endColor], $angle);
    }

    /**
     * Generate gradient from primary colors.
     */
    public function primary(array $themeColors, int $angle = 135): string
    {
        $colors = [];

        if (isset($themeColors['primary'])) {
            $colors[] = is_array($themeColors['primary'])
                ? ($themeColors['primary']['500'] ?? reset($themeColors['primary']))
                : $themeColors['primary'];
        }

        if (isset($themeColors['secondary'])) {
            $colors[] = is_array($themeColors['secondary'])
                ? ($themeColors['secondary']['500'] ?? reset($themeColors['secondary']))
                : $themeColors['secondary'];
        }

        if (isset($themeColors['accent'])) {
            $colors[] = is_array($themeColors['accent'])
                ? ($themeColors['accent']['500'] ?? reset($themeColors['accent']))
                : $themeColors['accent'];
        }

        return $this->linear($colors, $angle);
    }

    /**
     * Generate subtle gradient for backgrounds.
     */
    public function subtle(array $themeColors, bool $dark = false): string
    {
        $colors = [];

        if ($dark) {
            // Dark mode subtle gradient
            if (isset($themeColors['primary'])) {
                $colors[] = $this->getColorShade($themeColors['primary'], '950');
            }
            if (isset($themeColors['secondary'])) {
                $colors[] = $this->getColorShade($themeColors['secondary'], '900');
            }
            if (isset($themeColors['accent'])) {
                $colors[] = $this->getColorShade($themeColors['accent'], '900');
            }
        } else {
            // Light mode subtle gradient
            if (isset($themeColors['primary'])) {
                $colors[] = $this->getColorShade($themeColors['primary'], '50');
            }
            if (isset($themeColors['secondary'])) {
                $colors[] = $this->getColorShade($themeColors['secondary'], '50');
            }
            if (isset($themeColors['accent'])) {
                $colors[] = $this->getColorShade($themeColors['accent'], '50');
            }
        }

        return $this->linear($colors);
    }

    /**
     * Generate mesh gradient (multiple overlapping gradients).
     */
    public function mesh(array $colors, int $count = 3): string
    {
        $gradients = [];

        for ($i = 0; $i < $count; $i++) {
            $angle = 135 + ($i * 60);
            $colorSubset = array_slice($colors, $i, 2);
            if (count($colorSubset) < 2) {
                $colorSubset = array_slice($colors, 0, 2);
            }
            $gradients[] = $this->linear($colorSubset, $angle);
        }

        return implode(', ', $gradients);
    }

    /**
     * Format color stops for CSS.
     */
    protected function formatColorStops(array $colors): string
    {
        return implode(', ', $colors);
    }

    /**
     * Extract colors from theme color configuration.
     */
    protected function extractColors(array $themeColors): array
    {
        $colors = [];

        foreach ($themeColors as $name => $value) {
            if (is_string($value)) {
                $colors[] = $value;
            } elseif (is_array($value)) {
                // Get the 500 shade or first available
                $colors[] = $value['500'] ?? reset($value);
            }
        }

        return $colors;
    }

    /**
     * Get specific shade from color configuration.
     */
    protected function getColorShade(mixed $color, string $shade): string
    {
        if (is_string($color)) {
            return $color;
        }

        if (is_array($color)) {
            return $color[$shade] ?? reset($color);
        }

        return '#000000';
    }

    /**
     * Generate CSS variable reference for gradient.
     */
    public function varReference(string $name): string
    {
        return "var(--cs-gradient-{$name})";
    }

    /**
     * Generate gradient with CSS variables.
     */
    public function withVariables(array $variableNames, string $type = 'linear', array $options = []): string
    {
        $colors = array_map(fn ($name) => "var(--cs-color-{$name})", $variableNames);

        return match ($type) {
            'linear' => $this->linear($colors, $options['angle'] ?? 135),
            'radial' => $this->radial($colors, $options['shape'] ?? 'circle', $options['position'] ?? 'center'),
            'conic' => $this->conic($colors, $options['angle'] ?? 0, $options['position'] ?? 'center'),
            default => $this->linear($colors),
        };
    }
}

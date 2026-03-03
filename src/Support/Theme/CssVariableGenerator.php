<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Arr;

/**
 * CSS Variable Generator.
 *
 * Converts theme configuration into CSS custom properties (variables)
 * for dynamic theming support.
 */
class CssVariableGenerator
{
    /**
     * Variable prefix.
     */
    protected string $prefix = 'cs';

    /**
     * Create a new CSS variable generator instance.
     */
    public function __construct(?string $prefix = null)
    {
        if ($prefix !== null) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Generate CSS variables from theme.
     */
    public function generate(ThemeInterface $theme): array
    {
        $variables = [];
        $config = $theme->getConfig();

        // Generate color variables
        $colors = Arr::get($config, 'colors', []);
        $variables = array_merge($variables, $this->generateColorVariables($colors));

        // Generate font variables
        $fonts = Arr::get($config, 'fonts', []);
        $variables = array_merge($variables, $this->generateFontVariables($fonts));

        // Generate layout variables
        $layout = Arr::get($config, 'layout', []);
        $variables = array_merge($variables, $this->generateLayoutVariables($layout));

        // Generate component variables
        $components = Arr::get($config, 'components', []);
        $variables = array_merge($variables, $this->generateComponentVariables($components));

        // Generate gradient variables
        $gradients = Arr::get($config, 'gradient', []);
        $variables = array_merge($variables, $this->generateGradientVariables($gradients));

        return $variables;
    }

    /**
     * Generate color variables.
     */
    public function generateColorVariables(array $colors): array
    {
        $variables = [];

        foreach ($colors as $name => $value) {
            if (is_array($value)) {
                // Handle color shades
                foreach ($value as $shade => $color) {
                    if (is_string($color)) {
                        $varName = $this->makeVariableName('color', $name, (string) $shade);
                        $variables[$varName] = $color;
                    }
                }
            } elseif (is_string($value)) {
                // Handle simple colors
                $varName = $this->makeVariableName('color', $name);
                $variables[$varName] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate font variables.
     */
    public function generateFontVariables(array $fonts): array
    {
        $variables = [];

        foreach ($fonts as $name => $value) {
            if (is_string($value)) {
                $varName = $this->makeVariableName('font', $name);
                $variables[$varName] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate layout variables.
     */
    public function generateLayoutVariables(array $layout): array
    {
        $variables = [];

        foreach ($layout as $name => $value) {
            if (is_scalar($value)) {
                $varName = $this->makeVariableName('layout', $name);
                $variables[$varName] = (string) $value;
            } elseif (is_array($value)) {
                $variables = array_merge(
                    $variables,
                    $this->generateNestedVariables('layout', $name, $value)
                );
            }
        }

        return $variables;
    }

    /**
     * Generate component variables.
     */
    public function generateComponentVariables(array $components): array
    {
        $variables = [];

        foreach ($components as $component => $config) {
            if (!is_array($config)) {
                continue;
            }

            foreach ($config as $property => $value) {
                if (is_scalar($value)) {
                    $varName = $this->makeVariableName('component', $component, $property);
                    $variables[$varName] = (string) $value;
                } elseif (is_array($value)) {
                    $variables = array_merge(
                        $variables,
                        $this->generateNestedVariables('component', "{$component}-{$property}", $value)
                    );
                }
            }
        }

        return $variables;
    }

    /**
     * Generate gradient variables.
     */
    public function generateGradientVariables(array $gradients): array
    {
        $variables = [];

        foreach ($gradients as $name => $value) {
            if (is_string($value)) {
                $varName = $this->makeVariableName('gradient', $name);
                $variables[$varName] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate nested variables recursively.
     */
    protected function generateNestedVariables(string $category, string $parent, array $values): array
    {
        $variables = [];

        foreach ($values as $key => $value) {
            if (is_scalar($value)) {
                $varName = $this->makeVariableName($category, $parent, (string) $key);
                $variables[$varName] = (string) $value;
            } elseif (is_array($value)) {
                $variables = array_merge(
                    $variables,
                    $this->generateNestedVariables($category, "{$parent}-{$key}", $value)
                );
            }
        }

        return $variables;
    }

    /**
     * Make a CSS variable name.
     */
    protected function makeVariableName(string ...$parts): string
    {
        $parts = array_filter($parts);
        $name = implode('-', $parts);
        $name = str_replace('_', '-', $name);

        return "--{$this->prefix}-{$name}";
    }

    /**
     * Set the variable prefix.
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the variable prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Generate CSS variable reference.
     */
    public function var(string ...$parts): string
    {
        $varName = $this->makeVariableName(...$parts);

        return "var({$varName})";
    }

    /**
     * Generate CSS variable reference with fallback.
     */
    public function varWithFallback(string $fallback, string ...$parts): string
    {
        $varName = $this->makeVariableName(...$parts);

        return "var({$varName}, {$fallback})";
    }
}

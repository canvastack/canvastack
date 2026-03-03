<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Theme Validator.
 *
 * Validates theme configurations to ensure they meet the required
 * structure and contain all necessary fields.
 */
class ThemeValidator
{
    /**
     * Validation rules from config.
     *
     * @var array<string, mixed>
     */
    protected array $rules;

    /**
     * Validation errors.
     *
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * Create a new theme validator instance.
     *
     * @param array<string, mixed>|null $rules
     */
    public function __construct(?array $rules = null)
    {
        if ($rules === null) {
            $this->rules = [
                'required_fields' => ['name', 'display_name', 'version', 'author', 'config'],
                'required_config' => ['colors', 'fonts'],
                'required_colors' => ['primary', 'secondary', 'accent'],
            ];
        } else {
            $this->rules = $rules;
        }
    }

    /**
     * Validate a theme configuration array.
     *
     * @param array<string, mixed> $config
     * @return bool
     */
    public function validate(array $config): bool
    {
        $this->errors = [];

        // Validate required fields
        $this->validateRequiredFields($config);

        // Validate name format
        $this->validateNameFormat($config);

        // Validate version format
        $this->validateVersionFormat($config);

        // Validate config structure
        $this->validateConfigStructure($config);

        // Validate colors
        $this->validateColors($config);

        // Validate fonts
        $this->validateFonts($config);

        return empty($this->errors);
    }

    /**
     * Validate a theme instance.
     *
     * @param ThemeInterface $theme
     * @return bool
     */
    public function validateTheme(ThemeInterface $theme): bool
    {
        return $theme->isValid() && $this->validate($theme->toArray());
    }

    /**
     * Validate required fields.
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateRequiredFields(array $config): void
    {
        $requiredFields = $this->rules['required_fields'] ?? [];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $this->errors[] = "Missing required field: {$field}";
            }
        }
    }

    /**
     * Validate name format (kebab-case).
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateNameFormat(array $config): void
    {
        if (!isset($config['name'])) {
            return;
        }

        $name = $config['name'];

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name)) {
            $this->errors[] = "Theme name must be in kebab-case format: {$name}";
        }
    }

    /**
     * Validate version format (semver).
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateVersionFormat(array $config): void
    {
        if (!isset($config['version'])) {
            return;
        }

        $version = $config['version'];

        // Basic semver validation (major.minor.patch)
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->errors[] = "Theme version must follow semver format (e.g., 1.0.0): {$version}";
        }
    }

    /**
     * Validate config structure.
     *
     * Supports two formats:
     * 1. Nested format: ['name' => 'theme', 'config' => ['colors' => [...]]]
     * 2. Flat format: ['name' => 'theme', 'colors' => [...]]
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateConfigStructure(array $config): void
    {
        // Support both nested and flat formats
        $themeConfig = $config['config'] ?? $config;

        if (!is_array($themeConfig)) {
            $this->errors[] = 'Theme config must be an array';

            return;
        }

        $requiredConfig = $this->rules['required_config'] ?? [];

        foreach ($requiredConfig as $key) {
            // Check in nested config first, then in root level
            if (!isset($config['config'][$key]) && !isset($config[$key])) {
                $this->errors[] = "Missing required config section: {$key}";
            }
        }
    }

    /**
     * Validate colors configuration.
     *
     * Supports both nested and flat formats:
     * - Nested: $config['config']['colors']
     * - Flat: $config['colors']
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateColors(array $config): void
    {
        // Try nested format first, then flat format
        $colors = Arr::get($config, 'config.colors', Arr::get($config, 'colors', []));

        if (empty($colors)) {
            $this->errors[] = 'Theme must define colors';

            return;
        }

        $requiredColors = $this->rules['required_colors'] ?? [];

        foreach ($requiredColors as $color) {
            if (!isset($colors[$color])) {
                $this->errors[] = "Missing required color: {$color}";
            } else {
                // Validate color format (hex)
                $colorValue = $colors[$color];
                if (is_string($colorValue) && !$this->isValidHexColor($colorValue)) {
                    $this->errors[] = "Invalid hex color format for {$color}: {$colorValue}";
                }
            }
        }
    }

    /**
     * Validate fonts configuration.
     *
     * Supports both nested and flat formats:
     * - Nested: $config['config']['fonts']
     * - Flat: $config['fonts']
     *
     * @param array<string, mixed> $config
     * @return void
     */
    protected function validateFonts(array $config): void
    {
        // Skip if fonts are not in required config
        $requiredConfig = $this->rules['required_config'] ?? [];
        if (!in_array('fonts', $requiredConfig)) {
            return;
        }

        // Try nested format first, then flat format
        $fonts = Arr::get($config, 'config.fonts', Arr::get($config, 'fonts', []));

        if (empty($fonts)) {
            $this->errors[] = 'Theme must define fonts';

            return;
        }

        // Check for at least 'sans' font
        if (!isset($fonts['sans'])) {
            $this->errors[] = "Theme must define 'sans' font family";
        }
    }

    /**
     * Check if a string is a valid hex color.
     *
     * @param string $color
     * @return bool
     */
    protected function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color);
    }

    /**
     * Get validation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first validation error.
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Check if there are validation errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get validation errors as a string.
     *
     * @param string $separator
     * @return string
     */
    public function getErrorsAsString(string $separator = "\n"): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Validate and throw exception if invalid.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateOrFail(array $config): void
    {
        if (!$this->validate($config)) {
            throw new InvalidArgumentException(
                "Theme validation failed:\n" . $this->getErrorsAsString()
            );
        }
    }

    /**
     * Set custom validation rules.
     *
     * @param array<string, mixed> $rules
     * @return self
     */
    public function setRules(array $rules): self
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * Get validation rules.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}

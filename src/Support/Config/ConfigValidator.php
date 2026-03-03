<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Config;

/**
 * Configuration Validator.
 *
 * Validates configuration values to ensure they meet requirements.
 */
class ConfigValidator
{
    /**
     * Validation rules for each configuration group.
     */
    protected array $rules = [
        'app' => [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'base_url' => ['required', 'url'],
            'lang' => ['required', 'string', 'size:2'],
            'maintenance' => ['required', 'boolean'],
        ],
        'theme' => [
            'active' => ['required', 'string'],
            'cache_enabled' => ['required', 'boolean'],
            'hot_reload' => ['required', 'boolean'],
        ],
        'localization' => [
            'default_locale' => ['required', 'string', 'size:2'],
            'fallback_locale' => ['required', 'string', 'size:2'],
            'detect_browser' => ['required', 'boolean'],
            'cache_enabled' => ['required', 'boolean'],
        ],
        'rbac' => [
            'cache_enabled' => ['required', 'boolean'],
            'super_admin_bypass' => ['required', 'boolean'],
            'context_aware' => ['required', 'boolean'],
            'strict_mode' => ['required', 'boolean'],
        ],
        'performance' => [
            'chunk_size' => ['required', 'integer', 'min:10', 'max:1000'],
            'eager_load' => ['required', 'boolean'],
            'query_cache' => ['required', 'boolean'],
            'lazy_load_components' => ['required', 'boolean'],
            'optimize_queries' => ['required', 'boolean'],
        ],
        'cache' => [
            'enabled' => ['required', 'boolean'],
            'driver' => ['required', 'string', 'in:redis,file,array'],
        ],
    ];

    /**
     * Validate configuration settings.
     */
    public function validate(string $group, array $settings): array
    {
        if (!isset($this->rules[$group])) {
            return [
                'valid' => false,
                'errors' => ['group' => "Unknown configuration group: {$group}"],
            ];
        }

        $errors = [];

        foreach ($settings as $key => $value) {
            if (!isset($this->rules[$group][$key])) {
                continue; // Skip unknown keys
            }

            $rules = $this->rules[$group][$key];
            $fieldErrors = $this->validateField($key, $value, $rules);

            if (!empty($fieldErrors)) {
                $errors[$key] = $fieldErrors;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate a single field.
     */
    protected function validateField(string $key, $value, array $rules): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            $error = $this->applyRule($key, $value, $rule);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Apply a validation rule.
     */
    protected function applyRule(string $key, $value, string $rule): ?string
    {
        // Parse rule and parameters
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        return match ($ruleName) {
            'required' => $this->validateRequired($key, $value),
            'string' => $this->validateString($key, $value),
            'integer' => $this->validateInteger($key, $value),
            'boolean' => $this->validateBoolean($key, $value),
            'url' => $this->validateUrl($key, $value),
            'max' => $this->validateMax($key, $value, (int) $parameters[0]),
            'min' => $this->validateMin($key, $value, (int) $parameters[0]),
            'size' => $this->validateSize($key, $value, (int) $parameters[0]),
            'in' => $this->validateIn($key, $value, $parameters),
            default => null,
        };
    }

    /**
     * Validate required rule.
     */
    protected function validateRequired(string $key, $value): ?string
    {
        if ($value === null || $value === '') {
            return "The {$key} field is required.";
        }

        return null;
    }

    /**
     * Validate string rule.
     */
    protected function validateString(string $key, $value): ?string
    {
        if (!is_string($value)) {
            return "The {$key} must be a string.";
        }

        return null;
    }

    /**
     * Validate integer rule.
     */
    protected function validateInteger(string $key, $value): ?string
    {
        if (!is_int($value) && !ctype_digit((string) $value)) {
            return "The {$key} must be an integer.";
        }

        return null;
    }

    /**
     * Validate boolean rule.
     */
    protected function validateBoolean(string $key, $value): ?string
    {
        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return "The {$key} must be a boolean.";
        }

        return null;
    }

    /**
     * Validate URL rule.
     */
    protected function validateUrl(string $key, $value): ?string
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return "The {$key} must be a valid URL.";
        }

        return null;
    }

    /**
     * Validate max rule.
     */
    protected function validateMax(string $key, $value, int $max): ?string
    {
        if (is_string($value) && strlen($value) > $max) {
            return "The {$key} may not be greater than {$max} characters.";
        }

        if (is_int($value) && $value > $max) {
            return "The {$key} may not be greater than {$max}.";
        }

        return null;
    }

    /**
     * Validate min rule.
     */
    protected function validateMin(string $key, $value, int $min): ?string
    {
        if (is_string($value) && strlen($value) < $min) {
            return "The {$key} must be at least {$min} characters.";
        }

        if (is_int($value) && $value < $min) {
            return "The {$key} must be at least {$min}.";
        }

        return null;
    }

    /**
     * Validate size rule.
     */
    protected function validateSize(string $key, $value, int $size): ?string
    {
        if (is_string($value) && strlen($value) !== $size) {
            return "The {$key} must be {$size} characters.";
        }

        return null;
    }

    /**
     * Validate in rule.
     */
    protected function validateIn(string $key, $value, array $allowed): ?string
    {
        if (!in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);

            return "The {$key} must be one of: {$allowedStr}.";
        }

        return null;
    }

    /**
     * Add custom validation rule.
     */
    public function addRule(string $group, string $key, array $rules): void
    {
        if (!isset($this->rules[$group])) {
            $this->rules[$group] = [];
        }

        $this->rules[$group][$key] = $rules;
    }

    /**
     * Get validation rules for a group.
     */
    public function getRules(string $group): array
    {
        return $this->rules[$group] ?? [];
    }
}

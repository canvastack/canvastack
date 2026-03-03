<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

/**
 * Template Variable Resolver.
 *
 * Resolves template variables in permission rules (e.g., {{auth.id}}, {{auth.role}}).
 * Provides a flexible system for dynamic value substitution in rule conditions.
 */
class TemplateVariableResolver
{
    /**
     * Registered template variables.
     *
     * @var array<string, callable>
     */
    protected array $variables = [];

    /**
     * Create a new template variable resolver instance.
     */
    public function __construct()
    {
        $this->registerDefaultVariables();
        $this->loadConfigVariables();
    }

    /**
     * Register default template variables.
     *
     * @return void
     */
    protected function registerDefaultVariables(): void
    {
        // Authentication variables
        $this->variables['auth.id'] = fn () => app('auth')->id();
        $this->variables['auth.role'] = fn () => app('auth')->user()?->role;
        $this->variables['auth.department'] = fn () => app('auth')->user()?->department_id;
        $this->variables['auth.email'] = fn () => app('auth')->user()?->email;
        $this->variables['auth.name'] = fn () => app('auth')->user()?->name;
        $this->variables['auth.organization'] = fn () => app('auth')->user()?->organization_id;
        $this->variables['auth.team'] = fn () => app('auth')->user()?->team_id;

        // User-related variables
        $this->variables['user.id'] = fn () => app('auth')->id();
        $this->variables['user.role'] = fn () => app('auth')->user()?->role;
        $this->variables['user.department'] = fn () => app('auth')->user()?->department_id;
        $this->variables['user.email'] = fn () => app('auth')->user()?->email;
        $this->variables['user.name'] = fn () => app('auth')->user()?->name;
        $this->variables['user.organization'] = fn () => app('auth')->user()?->organization_id;
        $this->variables['user.team'] = fn () => app('auth')->user()?->team_id;

        // System variables
        $this->variables['now'] = fn () => now()->toDateTimeString();
        $this->variables['today'] = fn () => now()->toDateString();
        $this->variables['year'] = fn () => now()->year;
        $this->variables['month'] = fn () => now()->month;
        $this->variables['day'] = fn () => now()->day;
    }

    /**
     * Load custom template variables from configuration.
     *
     * Loads variables from config('canvastack-rbac.fine_grained.row_level.template_variables')
     * and merges them with default variables. Custom variables override defaults.
     *
     * @return void
     */
    protected function loadConfigVariables(): void
    {
        $configVariables = config('canvastack-rbac.fine_grained.row_level.template_variables', []);

        if (!is_array($configVariables)) {
            return;
        }

        foreach ($configVariables as $name => $resolver) {
            // Only register if resolver is callable
            if (is_callable($resolver)) {
                $this->variables[$name] = $resolver;
            }
        }
    }

    /**
     * Register a custom template variable.
     *
     * @param string $name Variable name (e.g., 'auth.custom')
     * @param callable $resolver Resolver function that returns the value
     * @return void
     */
    public function register(string $name, callable $resolver): void
    {
        $this->variables[$name] = $resolver;
    }

    /**
     * Unregister a template variable.
     *
     * @param string $name Variable name
     * @return void
     */
    public function unregister(string $name): void
    {
        unset($this->variables[$name]);
    }

    /**
     * Check if a template variable is registered.
     *
     * @param string $name Variable name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->variables[$name]);
    }

    /**
     * Get all registered template variables.
     *
     * @return array<string, callable>
     */
    public function all(): array
    {
        return $this->variables;
    }

    /**
     * Resolve a template variable.
     *
     * If the value contains a template variable (e.g., {{auth.id}}),
     * it will be replaced with the actual value.
     *
     * @param string|mixed $template Template string or value
     * @return mixed Resolved value
     */
    public function resolve(mixed $template): mixed
    {
        // Only process strings
        if (!is_string($template)) {
            return $template;
        }

        // Check if template contains variable syntax
        if (!str_contains($template, '{{') || !str_contains($template, '}}')) {
            return $template;
        }

        // Extract variable name from {{variable}}
        if (preg_match('/^\{\{(.+?)\}\}$/', $template, $matches)) {
            $varName = trim($matches[1]);

            if (isset($this->variables[$varName])) {
                return $this->variables[$varName]();
            }

            // Variable not found, return original template
            return $template;
        }

        // Multiple variables or mixed content - replace all occurrences
        return preg_replace_callback(
            '/\{\{(.+?)\}\}/',
            function ($matches) {
                $varName = trim($matches[1]);

                if (isset($this->variables[$varName])) {
                    $value = $this->variables[$varName]();

                    // Convert value to string for replacement
                    if (is_scalar($value) || is_null($value)) {
                        return (string) $value;
                    }

                    // Cannot convert to string, return original
                    return $matches[0];
                }

                // Variable not found, return original
                return $matches[0];
            },
            $template
        );
    }

    /**
     * Resolve conditions array.
     *
     * Resolves all template variables in a conditions array.
     *
     * @param array $conditions Conditions array with potential template variables
     * @return array Resolved conditions array
     */
    public function resolveConditions(array $conditions): array
    {
        $resolved = [];

        foreach ($conditions as $key => $value) {
            $resolved[$key] = $this->resolve($value);
        }

        return $resolved;
    }

    /**
     * Resolve multiple values.
     *
     * @param array $values Array of values to resolve
     * @return array Resolved values
     */
    public function resolveMany(array $values): array
    {
        return array_map(fn ($value) => $this->resolve($value), $values);
    }

    /**
     * Check if a string contains template variables.
     *
     * @param string $template Template string
     * @return bool
     */
    public function hasTemplateVariables(string $template): bool
    {
        return str_contains($template, '{{') && str_contains($template, '}}');
    }

    /**
     * Extract all template variable names from a string.
     *
     * @param string $template Template string
     * @return array<string> Array of variable names
     */
    public function extractVariables(string $template): array
    {
        if (!$this->hasTemplateVariables($template)) {
            return [];
        }

        preg_match_all('/\{\{(.+?)\}\}/', $template, $matches);

        return array_map('trim', $matches[1] ?? []);
    }

    /**
     * Validate that all template variables in a string are registered.
     *
     * @param string $template Template string
     * @return bool True if all variables are registered
     */
    public function validateTemplate(string $template): bool
    {
        $variables = $this->extractVariables($template);

        foreach ($variables as $variable) {
            if (!$this->has($variable)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get unregistered variables from a template string.
     *
     * @param string $template Template string
     * @return array<string> Array of unregistered variable names
     */
    public function getUnregisteredVariables(string $template): array
    {
        $variables = $this->extractVariables($template);
        $unregistered = [];

        foreach ($variables as $variable) {
            if (!$this->has($variable)) {
                $unregistered[] = $variable;
            }
        }

        return $unregistered;
    }

    /**
     * Reset to default variables only.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->variables = [];
        $this->registerDefaultVariables();
    }

    /**
     * Reload configuration variables.
     *
     * Reloads custom variables from configuration without resetting defaults.
     * Useful when configuration changes at runtime.
     *
     * @return void
     */
    public function reloadConfig(): void
    {
        $this->loadConfigVariables();
    }

    /**
     * Get count of registered variables.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->variables);
    }

    /**
     * Get list of registered variable names.
     *
     * @return array<string>
     */
    public function getVariableNames(): array
    {
        return array_keys($this->variables);
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use InvalidArgumentException;

/**
 * Configuration Validator for Fine-Grained Permissions.
 *
 * Validates the fine_grained configuration section to ensure all required
 * settings are present and valid.
 */
class ConfigValidator
{
    /**
     * Validate the fine-grained permissions configuration.
     *
     * @param array<string, mixed> $config The fine_grained configuration array
     * @return void
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function validate(array $config): void
    {
        // Validate main enabled flag
        $this->validateEnabled($config);

        // Validate cache configuration
        $this->validateCache($config);

        // Validate row-level configuration
        $this->validateRowLevel($config);

        // Validate column-level configuration
        $this->validateColumnLevel($config);

        // Validate JSON attribute configuration
        $this->validateJsonAttribute($config);

        // Validate conditional configuration
        $this->validateConditional($config);

        // Validate audit configuration
        $this->validateAudit($config);
    }

    /**
     * Validate the main enabled flag.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateEnabled(array $config): void
    {
        if (!isset($config['enabled'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($config['enabled'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions "enabled" must be a boolean value.'
            );
        }
    }

    /**
     * Validate cache configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateCache(array $config): void
    {
        if (!isset($config['cache'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have a "cache" section.'
            );
        }

        $cache = $config['cache'];

        // Validate cache enabled flag
        if (!isset($cache['enabled'])) {
            throw new InvalidArgumentException(
                'Cache configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($cache['enabled'])) {
            throw new InvalidArgumentException(
                'Cache "enabled" must be a boolean value.'
            );
        }

        // Validate TTL configuration
        if (!isset($cache['ttl'])) {
            throw new InvalidArgumentException(
                'Cache configuration must have a "ttl" section.'
            );
        }

        $ttl = $cache['ttl'];
        $requiredTtlKeys = ['row', 'column', 'json_attribute', 'conditional'];

        foreach ($requiredTtlKeys as $key) {
            if (!isset($ttl[$key])) {
                throw new InvalidArgumentException(
                    "Cache TTL configuration must have a \"{$key}\" key."
                );
            }

            // Coerce string integers to integers
            $value = $ttl[$key];
            if (is_string($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            if (!is_int($value) || $value < 0) {
                throw new InvalidArgumentException(
                    "Cache TTL \"{$key}\" must be a non-negative integer."
                );
            }
        }

        // Validate key_prefix
        if (!isset($cache['key_prefix'])) {
            throw new InvalidArgumentException(
                'Cache configuration must have a "key_prefix" key.'
            );
        }

        if (!is_string($cache['key_prefix']) || empty($cache['key_prefix'])) {
            throw new InvalidArgumentException(
                'Cache "key_prefix" must be a non-empty string.'
            );
        }

        // Validate tags
        if (!isset($cache['tags'])) {
            throw new InvalidArgumentException(
                'Cache configuration must have a "tags" section.'
            );
        }

        if (!is_array($cache['tags']) || empty($cache['tags'])) {
            throw new InvalidArgumentException(
                'Cache "tags" must be a non-empty array.'
            );
        }
    }

    /**
     * Validate row-level configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateRowLevel(array $config): void
    {
        if (!isset($config['row_level'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have a "row_level" section.'
            );
        }

        $rowLevel = $config['row_level'];

        // Validate enabled flag
        if (!isset($rowLevel['enabled'])) {
            throw new InvalidArgumentException(
                'Row-level configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($rowLevel['enabled'])) {
            throw new InvalidArgumentException(
                'Row-level "enabled" must be a boolean value.'
            );
        }

        // Validate template_variables
        if (!isset($rowLevel['template_variables'])) {
            throw new InvalidArgumentException(
                'Row-level configuration must have a "template_variables" section.'
            );
        }

        if (!is_array($rowLevel['template_variables'])) {
            throw new InvalidArgumentException(
                'Row-level "template_variables" must be an array.'
            );
        }

        // Validate each template variable is callable
        foreach ($rowLevel['template_variables'] as $key => $value) {
            if (!is_string($key) || empty($key)) {
                throw new InvalidArgumentException(
                    'Template variable keys must be non-empty strings.'
                );
            }

            if (!is_callable($value)) {
                throw new InvalidArgumentException(
                    "Template variable \"{$key}\" must be a callable."
                );
            }
        }
    }

    /**
     * Validate column-level configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateColumnLevel(array $config): void
    {
        if (!isset($config['column_level'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have a "column_level" section.'
            );
        }

        $columnLevel = $config['column_level'];

        // Validate enabled flag
        if (!isset($columnLevel['enabled'])) {
            throw new InvalidArgumentException(
                'Column-level configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($columnLevel['enabled'])) {
            throw new InvalidArgumentException(
                'Column-level "enabled" must be a boolean value.'
            );
        }

        // Validate default_deny flag
        if (!isset($columnLevel['default_deny'])) {
            throw new InvalidArgumentException(
                'Column-level configuration must have a "default_deny" key.'
            );
        }

        if (!is_bool($columnLevel['default_deny'])) {
            throw new InvalidArgumentException(
                'Column-level "default_deny" must be a boolean value.'
            );
        }
    }

    /**
     * Validate JSON attribute configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateJsonAttribute(array $config): void
    {
        if (!isset($config['json_attribute'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have a "json_attribute" section.'
            );
        }

        $jsonAttribute = $config['json_attribute'];

        // Validate enabled flag
        if (!isset($jsonAttribute['enabled'])) {
            throw new InvalidArgumentException(
                'JSON attribute configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($jsonAttribute['enabled'])) {
            throw new InvalidArgumentException(
                'JSON attribute "enabled" must be a boolean value.'
            );
        }

        // Validate path_separator
        if (!isset($jsonAttribute['path_separator'])) {
            throw new InvalidArgumentException(
                'JSON attribute configuration must have a "path_separator" key.'
            );
        }

        if (!is_string($jsonAttribute['path_separator']) || empty($jsonAttribute['path_separator'])) {
            throw new InvalidArgumentException(
                'JSON attribute "path_separator" must be a non-empty string.'
            );
        }

        if (strlen($jsonAttribute['path_separator']) !== 1) {
            throw new InvalidArgumentException(
                'JSON attribute "path_separator" must be a single character.'
            );
        }
    }

    /**
     * Validate conditional configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateConditional(array $config): void
    {
        if (!isset($config['conditional'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have a "conditional" section.'
            );
        }

        $conditional = $config['conditional'];

        // Validate enabled flag
        if (!isset($conditional['enabled'])) {
            throw new InvalidArgumentException(
                'Conditional configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($conditional['enabled'])) {
            throw new InvalidArgumentException(
                'Conditional "enabled" must be a boolean value.'
            );
        }

        // Validate allowed_operators
        if (!isset($conditional['allowed_operators'])) {
            throw new InvalidArgumentException(
                'Conditional configuration must have an "allowed_operators" key.'
            );
        }

        if (!is_array($conditional['allowed_operators']) || empty($conditional['allowed_operators'])) {
            throw new InvalidArgumentException(
                'Conditional "allowed_operators" must be a non-empty array.'
            );
        }

        foreach ($conditional['allowed_operators'] as $operator) {
            if (!is_string($operator) || empty($operator)) {
                throw new InvalidArgumentException(
                    'All allowed operators must be non-empty strings.'
                );
            }
        }

        // Validate allowed_functions
        if (!isset($conditional['allowed_functions'])) {
            throw new InvalidArgumentException(
                'Conditional configuration must have an "allowed_functions" key.'
            );
        }

        if (!is_array($conditional['allowed_functions'])) {
            throw new InvalidArgumentException(
                'Conditional "allowed_functions" must be an array.'
            );
        }

        foreach ($conditional['allowed_functions'] as $function) {
            if (!is_string($function) || empty($function)) {
                throw new InvalidArgumentException(
                    'All allowed functions must be non-empty strings.'
                );
            }
        }
    }

    /**
     * Validate audit configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateAudit(array $config): void
    {
        if (!isset($config['audit'])) {
            throw new InvalidArgumentException(
                'Fine-grained permissions configuration must have an "audit" section.'
            );
        }

        $audit = $config['audit'];

        // Validate enabled flag
        if (!isset($audit['enabled'])) {
            throw new InvalidArgumentException(
                'Audit configuration must have an "enabled" key.'
            );
        }

        if (!is_bool($audit['enabled'])) {
            throw new InvalidArgumentException(
                'Audit "enabled" must be a boolean value.'
            );
        }

        // Validate log_denials flag
        if (!isset($audit['log_denials'])) {
            throw new InvalidArgumentException(
                'Audit configuration must have a "log_denials" key.'
            );
        }

        if (!is_bool($audit['log_denials'])) {
            throw new InvalidArgumentException(
                'Audit "log_denials" must be a boolean value.'
            );
        }

        // Validate log_channel
        if (!isset($audit['log_channel'])) {
            throw new InvalidArgumentException(
                'Audit configuration must have a "log_channel" key.'
            );
        }

        if (!is_string($audit['log_channel']) || empty($audit['log_channel'])) {
            throw new InvalidArgumentException(
                'Audit "log_channel" must be a non-empty string.'
            );
        }
    }
}

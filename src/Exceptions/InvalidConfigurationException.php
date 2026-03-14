<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Exceptions;

/**
 * Exception thrown when TableBuilder configuration is invalid.
 *
 * This exception is thrown when:
 * - Invalid warning method is specified
 * - Lazy loading is disabled but tabs are defined
 * - Other configuration inconsistencies are detected
 *
 * Implements Requirement 15.7: Configuration validation and error handling.
 *
 * @package Canvastack\Canvastack\Exceptions
 */
class InvalidConfigurationException extends TableBuilderException
{
    /**
     * Create exception for lazy loading disabled with tabs.
     *
     * @return static
     */
    public static function lazyLoadingDisabledWithTabs(): static
    {
        return new static(
            "Lazy loading is disabled but tabs are defined. " .
            "Either enable lazy loading (set CANVASTACK_LAZY_LOAD_TABS=true in .env) " .
            "or don't use tabs. Rendering all tabs immediately may cause performance issues. " .
            "See documentation: docs/guides/tab-system-usage.md"
        );
    }

    /**
     * Create exception for invalid warning method.
     *
     * @param string $method The invalid method
     * @return static
     */
    public static function invalidWarningMethod(string $method): static
    {
        return new static(
            "Invalid connection warning method: '{$method}'. " .
            "Allowed values: 'log', 'toast', 'both'. " .
            "Check CANVASTACK_CONNECTION_WARNING_METHOD in your .env file."
        );
    }

    /**
     * Create exception for invalid configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Invalid value
     * @param string $expected Expected value description
     * @return static
     */
    public static function invalidValue(string $key, mixed $value, string $expected): static
    {
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);
        
        return new static(
            "Invalid configuration value for '{$key}': '{$valueStr}'. " .
            "Expected: {$expected}. " .
            "Check your configuration in config/canvastack.php or .env file."
        );
    }
}

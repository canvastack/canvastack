<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Exceptions;

/**
 * Exception thrown when table configuration is invalid or missing.
 *
 * This exception is thrown when:
 * - Required configuration is missing
 * - Configuration values are invalid
 * - Configuration format is incorrect
 * - Conflicting configuration options are provided
 *
 * @package Canvastack\Canvastack\Components\Table\Exceptions
 */
class ConfigurationException extends TableException
{
    /**
     * Create exception for missing required configuration.
     *
     * @param string $configKey The missing configuration key
     * @return static
     */
    public static function missingRequired(string $configKey): static
    {
        return new static(
            "Required table configuration '{$configKey}' is missing. Please provide this configuration value."
        );
    }

    /**
     * Create exception for invalid configuration value.
     *
     * @param string $configKey The configuration key with invalid value
     * @param mixed $value The invalid value
     * @param string $expectedType The expected type or format
     * @return static
     */
    public static function invalidValue(string $configKey, mixed $value, string $expectedType): static
    {
        $actualType = get_debug_type($value);
        
        return new static(
            "Invalid value for table configuration '{$configKey}'. Expected {$expectedType}, got {$actualType}."
        );
    }

    /**
     * Create exception for invalid configuration format.
     *
     * @param string $configKey The configuration key with invalid format
     * @param string $expectedFormat Description of expected format
     * @return static
     */
    public static function invalidFormat(string $configKey, string $expectedFormat): static
    {
        return new static(
            "Invalid format for table configuration '{$configKey}'. Expected format: {$expectedFormat}."
        );
    }

    /**
     * Create exception for conflicting configuration options.
     *
     * @param string $option1 First conflicting option
     * @param string $option2 Second conflicting option
     * @return static
     */
    public static function conflictingOptions(string $option1, string $option2): static
    {
        return new static(
            "Conflicting table configuration options: '{$option1}' and '{$option2}' cannot be used together."
        );
    }

    /**
     * Create exception for unsupported configuration option.
     *
     * @param string $configKey The unsupported configuration key
     * @param string $engineName The engine that doesn't support this option
     * @return static
     */
    public static function unsupportedOption(string $configKey, string $engineName): static
    {
        return new static(
            "Configuration option '{$configKey}' is not supported by the '{$engineName}' engine."
        );
    }

    /**
     * Create exception for invalid column configuration.
     *
     * @param string $columnName The column with invalid configuration
     * @param string $reason The reason why the configuration is invalid
     * @return static
     */
    public static function invalidColumn(string $columnName, string $reason): static
    {
        return new static(
            "Invalid configuration for column '{$columnName}': {$reason}"
        );
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Exceptions;

/**
 * Exception thrown when an invalid or unregistered engine is requested.
 *
 * This exception is thrown when:
 * - Attempting to use an engine that doesn't exist
 * - Requesting an engine that hasn't been registered
 * - Providing an invalid engine name
 *
 * @package Canvastack\Canvastack\Components\Table\Exceptions
 */
class InvalidEngineException extends TableException
{
    /**
     * Create exception for engine not found.
     *
     * @param string $engineName The name of the engine that was not found
     * @param array<string> $availableEngines List of available engine names
     * @return static
     */
    public static function notFound(string $engineName, array $availableEngines = []): static
    {
        $message = "Table engine '{$engineName}' not found.";
        
        if (!empty($availableEngines)) {
            $message .= ' Available engines: ' . implode(', ', $availableEngines);
        }
        
        return new static($message);
    }

    /**
     * Create exception for invalid engine name.
     *
     * @param string $engineName The invalid engine name
     * @return static
     */
    public static function invalidName(string $engineName): static
    {
        return new static(
            "Invalid engine name '{$engineName}'. Engine names must be alphanumeric and may contain hyphens or underscores."
        );
    }

    /**
     * Create exception for engine not registered.
     *
     * @param string $engineName The name of the unregistered engine
     * @return static
     */
    public static function notRegistered(string $engineName): static
    {
        return new static(
            "Table engine '{$engineName}' is not registered. Please register the engine before using it."
        );
    }

    /**
     * Create exception for engine already registered.
     *
     * @param string $engineName The name of the engine that's already registered
     * @return static
     */
    public static function alreadyRegistered(string $engineName): static
    {
        return new static(
            "Table engine '{$engineName}' is already registered. Use a different name or unregister the existing engine first."
        );
    }
}

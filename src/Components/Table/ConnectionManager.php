<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Connection Manager Component
 * 
 * Automatically detects database connections from Eloquent models with
 * priority-based resolution and configurable override warnings.
 * 
 * Connection Priority:
 * 1. Manual override (via connection() method)
 * 2. Model connection (via getConnectionName())
 * 3. Config default (config('database.default'))
 * 
 * @package Canvastack\Canvastack\Components\Table
 */
class ConnectionManager
{
    /**
     * Detected connection name from model.
     * 
     * @var string|null
     */
    protected ?string $detectedConnection = null;

    /**
     * Manual override connection name.
     * 
     * @var string|null
     */
    protected ?string $overrideConnection = null;

    /**
     * Eloquent model instance.
     * 
     * @var Model|null
     */
    protected ?Model $model = null;

    /**
     * Warning system instance.
     * 
     * @var WarningSystem
     */
    protected WarningSystem $warningSystem;

    /**
     * Constructor.
     * 
     * Injects WarningSystem dependency for connection override warnings.
     * 
     * @param WarningSystem $warningSystem Warning system instance
     */
    public function __construct(WarningSystem $warningSystem)
    {
        $this->warningSystem = $warningSystem;
    }

    /**
     * Detect connection from model.
     * 
     * Calls getConnectionName() on the model if available and stores
     * the detected connection. Logs the detection at debug level.
     * If the model returns null, uses the default connection.
     * 
     * @param Model|null $model Eloquent model instance
     * @return string Detected connection name or default
     */
    public function detectConnection(?Model $model = null): string
    {
        // Store model reference
        if ($model !== null) {
            $this->model = $model;
        }

        // If no model, return default connection
        if ($this->model === null) {
            $this->detectedConnection = config('database.default');
            Log::debug('ConnectionManager: No model provided, using default connection', [
                'connection' => $this->detectedConnection,
            ]);
            return $this->detectedConnection;
        }

        // Get connection name from model (may return null)
        $connectionName = $this->model->getConnectionName();
        
        // If model returns null, use default connection
        if ($connectionName === null) {
            $this->detectedConnection = config('database.default');
            Log::debug('ConnectionManager: Model has no connection, using default', [
                'model' => get_class($this->model),
                'connection' => $this->detectedConnection,
            ]);
            return $this->detectedConnection;
        }
        
        $this->detectedConnection = $connectionName;

        // Log detection at debug level
        Log::debug('ConnectionManager: Detected connection from model', [
            'model' => get_class($this->model),
            'connection' => $this->detectedConnection,
        ]);

        return $this->detectedConnection;
    }


    /**
     * Set manual connection override.
     * 
     * Sets a manual connection override that takes priority over
     * the model's connection.
     * 
     * @param string $connection Connection name
     * @return self For method chaining
     */
    public function setOverride(string $connection): self
    {
        $this->overrideConnection = $connection;

        Log::debug('ConnectionManager: Connection override set', [
            'override' => $connection,
            'detected' => $this->detectedConnection,
        ]);

        return $this;
    }

    /**
     * Get final connection to use.
     * 
     * Returns the connection based on priority:
     * 1. Override connection (if set)
     * 2. Model connection (if detected)
     * 3. Config default
     * 
     * @return string Connection name to use
     */
    public function getConnection(): string
    {
        // Priority 1: Manual override
        if ($this->overrideConnection !== null) {
            return $this->overrideConnection;
        }

        // Priority 2: Model connection
        if ($this->detectedConnection !== null) {
            return $this->detectedConnection;
        }

        // Priority 3: Config default
        return config('database.default');
    }

    /**
     * Check if connection was manually overridden.
     * 
     * Returns true if a manual override connection has been set.
     * 
     * @return bool True if override exists, false otherwise
     */
    public function hasOverride(): bool
    {
        return $this->overrideConnection !== null;
    }

    /**
     * Check if override differs from model connection.
     * 
     * Returns true if:
     * - An override connection is set, AND
     * - A model connection was detected, AND
     * - They are different
     * 
     * This indicates a potential configuration issue that should
     * trigger a warning.
     * 
     * @return bool True if mismatch exists, false otherwise
     */
    public function hasConnectionMismatch(): bool
    {
        // No mismatch if no override
        if ($this->overrideConnection === null) {
            return false;
        }

        // No mismatch if no detected connection
        if ($this->detectedConnection === null) {
            return false;
        }

        // Mismatch if they differ
        return $this->overrideConnection !== $this->detectedConnection;
    }

    /**
     * Get detected connection name.
     * 
     * Returns the connection name that was detected from the model.
     * 
     * @return string|null Detected connection name or null
     */
    public function getDetectedConnection(): ?string
    {
        return $this->detectedConnection;
    }

    /**
     * Get override connection name.
     * 
     * Returns the manually set override connection name.
     * 
     * @return string|null Override connection name or null
     */
    public function getOverrideConnection(): ?string
    {
        return $this->overrideConnection;
    }

    /**
     * Get model instance.
     * 
     * Returns the stored model instance.
     * 
     * @return Model|null Model instance or null
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * Reset connection detection.
     * 
     * Clears all stored connection information and model reference.
     * Useful for testing or when reusing the manager instance.
     * 
     * @return self For method chaining
     */
    public function reset(): self
    {
        $this->detectedConnection = null;
        $this->overrideConnection = null;
        $this->model = null;

        Log::debug('ConnectionManager: Reset all connections');

        return $this;
    }

    /**
     * Get warning system instance.
     * 
     * Returns the injected warning system for testing purposes.
     * 
     * @internal This method is for testing only
     * @return WarningSystem Warning system instance
     */
    public function getWarningSystem(): WarningSystem
    {
        return $this->warningSystem;
    }
}

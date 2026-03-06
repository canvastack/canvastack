<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Exceptions\InvalidEngineException;
use Illuminate\Support\Facades\Log;

/**
 * Engine Manager
 * 
 * Manages table engine registration, selection, and lifecycle.
 * Supports runtime engine switching and auto-detection based on requirements.
 */
class EngineManager
{
    /**
     * Registered engines.
     *
     * @var array<string, TableEngineInterface>
     */
    protected array $engines = [];

    /**
     * Default engine name.
     *
     * @var string
     */
    protected string $defaultEngine = 'datatables';

    /**
     * Register an engine.
     *
     * @param string $name
     * @param TableEngineInterface $engine
     * @return void
     */
    public function register(string $name, TableEngineInterface $engine): void
    {
        $this->engines[$name] = $engine;
        
        if (config('app.debug')) {
            Log::debug("Table engine registered: {$name}");
        }
    }

    /**
     * Get an engine by name.
     *
     * @param string $name
     * @return TableEngineInterface
     * @throws InvalidEngineException
     */
    public function get(string $name): TableEngineInterface
    {
        if (!$this->has($name)) {
            throw new InvalidEngineException(
                "Table engine '{$name}' is not registered. " .
                "Available engines: " . implode(', ', array_keys($this->engines))
            );
        }

        return $this->engines[$name];
    }

    /**
     * Check if an engine is registered.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->engines[$name]);
    }

    /**
     * Get all registered engines.
     *
     * @return array<string, TableEngineInterface>
     */
    public function all(): array
    {
        return $this->engines;
    }

    /**
     * Set the default engine.
     *
     * @param string $name
     * @return void
     * @throws InvalidEngineException
     */
    public function setDefault(string $name): void
    {
        if (!$this->has($name)) {
            throw new InvalidEngineException(
                "Cannot set default engine to '{$name}' - engine is not registered."
            );
        }

        $this->defaultEngine = $name;
        
        if (config('app.debug')) {
            Log::debug("Default table engine set to: {$name}");
        }
    }

    /**
     * Get the default engine name.
     *
     * @return string
     */
    public function getDefault(): string
    {
        return $this->defaultEngine;
    }

    /**
     * Select the best engine for a table.
     *
     * @param TableBuilder $table
     * @return TableEngineInterface
     */
    public function selectEngine(TableBuilder $table): TableEngineInterface
    {
        // 1. Check if table has explicit engine set
        $explicitEngine = $table->getEngine();
        if ($explicitEngine !== null) {
            $this->logSelection($explicitEngine, 'Explicit engine set via setEngine()');
            return $this->get($explicitEngine);
        }

        // 2. Check environment variable
        $envEngine = config('canvastack-table.engine');
        if ($envEngine !== null && $this->has($envEngine)) {
            $this->logSelection($envEngine, 'Engine from CANVASTACK_TABLE_ENGINE config');
            return $this->get($envEngine);
        }

        // 3. Auto-detect based on requirements
        $autoEngine = $this->autoDetect($table);
        $this->logSelection($autoEngine, 'Auto-detected based on table requirements');
        return $this->get($autoEngine);
    }

    /**
     * Auto-detect best engine based on requirements.
     *
     * @param TableBuilder $table
     * @return string
     */
    protected function autoDetect(TableBuilder $table): string
    {
        // For now, always return default engine
        // Auto-detection based on features will be implemented later
        // when TableConfiguration is fully integrated
        
        // Default to DataTables for backward compatibility
        return $this->defaultEngine;
    }

    /**
     * Check if table requires column resizing.
     *
     * @param TableBuilder $table
     * @return bool
     */
    protected function requiresColumnResizing(TableBuilder $table): bool
    {
        // Check if column resizing is explicitly enabled
        $config = $table->getConfiguration();
        return property_exists($config, 'columnResizing') && $config->columnResizing === true;
    }

    /**
     * Check if table prefers modern design.
     *
     * @param TableBuilder $table
     * @return bool
     */
    protected function prefersModernDesign(TableBuilder $table): bool
    {
        // Check if modern design is explicitly requested
        $config = $table->getConfiguration();
        return property_exists($config, 'modernDesign') && $config->modernDesign === true;
    }

    /**
     * Log engine selection reasoning.
     *
     * @param string $engine
     * @param string $reason
     * @return void
     */
    protected function logSelection(string $engine, string $reason): void
    {
        if (config('app.debug')) {
            Log::debug("Table engine selected: {$engine}", [
                'reason' => $reason,
                'available_engines' => array_keys($this->engines),
            ]);
        }
    }
}

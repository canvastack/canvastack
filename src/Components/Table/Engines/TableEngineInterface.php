<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\TableBuilder;

/**
 * Interface for table rendering engines.
 *
 * This interface defines the contract that all table engines must implement.
 * It supports both DataTables.js and TanStack Table v8 engines, allowing
 * runtime switching between engines while maintaining feature parity.
 *
 * @package Canvastack\Canvastack\Components\Table\Engines
 * @version 1.0.0
 */
interface TableEngineInterface
{
    /**
     * Render the table HTML.
     *
     * This method generates the complete HTML output for the table,
     * including all necessary markup, scripts, and styles.
     *
     * @param TableBuilder $table The table builder instance
     * @return string The rendered HTML
     */
    public function render(TableBuilder $table): string;

    /**
     * Configure the engine with table settings.
     *
     * This method is called to configure the engine with the table's
     * settings before rendering. It should prepare all necessary
     * configuration for the rendering process.
     *
     * @param TableBuilder $table The table builder instance
     * @return void
     */
    public function configure(TableBuilder $table): void;

    /**
     * Get required CSS/JS assets for this engine.
     *
     * Returns an array containing the CSS and JavaScript assets
     * required by this engine. Assets should be CDN URLs or
     * local paths.
     *
     * @return array{css: array<string>, js: array<string>} Array with 'css' and 'js' keys
     */
    public function getAssets(): array;

    /**
     * Check if this engine supports a specific feature.
     *
     * Supported features:
     * - sorting: Column sorting
     * - pagination: Data pagination
     * - searching: Global and column search
     * - filtering: Advanced filtering
     * - fixed-columns: Column pinning
     * - row-selection: Row selection
     * - export: Data export
     * - column-resizing: Column resizing
     * - virtual-scrolling: Virtual scrolling
     * - lazy-loading: Lazy loading
     * - responsive: Responsive design
     * - dark-mode: Dark mode support
     *
     * @param string $feature The feature name to check
     * @return bool True if the feature is supported, false otherwise
     */
    public function supports(string $feature): bool;

    /**
     * Get the engine name.
     *
     * Returns a unique identifier for this engine.
     * Examples: 'datatables', 'tanstack'
     *
     * @return string The engine name
     */
    public function getName(): string;

    /**
     * Get the engine version.
     *
     * Returns the version of the underlying library.
     * Examples: '1.13.x', '8.x'
     *
     * @return string The engine version
     */
    public function getVersion(): string;

    /**
     * Process server-side request.
     *
     * This method handles server-side processing for pagination,
     * sorting, searching, and filtering. It should return an array
     * in the format expected by the engine.
     *
     * @param TableBuilder $table The table builder instance
     * @return array The processed data response
     */
    public function processServerSide(TableBuilder $table): array;

    /**
     * Get engine-specific configuration.
     *
     * Returns the current configuration array for this engine.
     * This can include custom settings, options, and preferences.
     *
     * @return array The configuration array
     */
    public function getConfig(): array;

    /**
     * Set engine-specific configuration.
     *
     * Allows setting custom configuration for this engine.
     * The configuration array can include any engine-specific
     * settings, options, and preferences.
     *
     * @param array $config The configuration array
     * @return void
     */
    public function setConfig(array $config): void;
}

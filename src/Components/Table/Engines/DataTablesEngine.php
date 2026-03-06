<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\Exceptions\RenderException;

/**
 * DataTables.js Engine Implementation
 *
 * This engine wraps the existing AdminRenderer to provide DataTables.js
 * functionality through the TableEngineInterface. It maintains 100%
 * backward compatibility with existing code while supporting the new
 * dual-engine architecture.
 *
 * Features:
 * - Wraps existing AdminRenderer implementation
 * - Supports all current DataTables.js features
 * - Uses Yajra for server-side processing
 * - Supports FixedColumns extension for column pinning
 * - Supports Buttons extension for export functionality
 * - Maintains current styling and behavior
 *
 * @package Canvastack\Canvastack\Components\Table\Engines
 * @version 1.0.0
 */
class DataTablesEngine implements TableEngineInterface
{
    /**
     * Admin renderer instance.
     *
     * @var AdminRenderer
     */
    protected AdminRenderer $renderer;

    /**
     * Engine configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Supported features by this engine.
     *
     * @var array<string, bool>
     */
    protected array $supportedFeatures = [
        'sorting' => true,
        'pagination' => true,
        'searching' => true,
        'filtering' => true,
        'fixed-columns' => true,
        'row-selection' => true,
        'export' => true,
        'column-resizing' => false, // DataTables has limited support
        'virtual-scrolling' => false, // Not natively supported
        'lazy-loading' => false, // Not natively supported
        'responsive' => true,
        'dark-mode' => true,
    ];

    /**
     * Constructor.
     *
     * @param AdminRenderer $renderer The admin renderer instance
     */
    public function __construct(AdminRenderer $renderer)
    {
        $this->renderer = $renderer;
        $this->config = config('canvastack-table.engines.datatables', []);
    }

    /**
     * {@inheritdoc}
     */
    public function render(TableBuilder $table): string
    {
        try {
            // Configure the engine before rendering
            $this->configure($table);

            // Prepare data for AdminRenderer
            $data = $this->prepareRenderData($table);

            // Render using AdminRenderer
            return $this->renderer->render($data);
        } catch (\Exception $e) {
            throw new RenderException(
                "Failed to render table with DataTables engine: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configure(TableBuilder $table): void
    {
        // Get DataTables configuration
        $config = $this->getDataTablesConfig($table);

        // Apply FixedColumns configuration if set
        $fixedColumnsConfig = $this->getFixedColumnsConfig($table);
        if ($fixedColumnsConfig !== null) {
            $config['fixedColumns'] = $fixedColumnsConfig;
        }

        // Apply Buttons configuration if set
        $buttonsConfig = $this->getButtonsConfig($table);
        if ($buttonsConfig !== null) {
            $config['buttons'] = $buttonsConfig;
        }

        // Apply Select configuration if set
        $selectConfig = $this->getSelectConfig($table);
        if ($selectConfig !== null) {
            $config['select'] = $selectConfig;
        }

        // Store configuration
        $this->config = array_merge($this->config, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets(): array
    {
        $cdnBase = config('canvastack-table.assets.cdn_base', 'https://cdn.datatables.net');
        $version = config('canvastack-table.assets.datatables_version', '1.13.8');
        $buttonsVersion = config('canvastack-table.assets.buttons_version', '2.4.2');

        return [
            'css' => [
                "{$cdnBase}/{$version}/css/dataTables.bootstrap5.min.css",
                "{$cdnBase}/fixedcolumns/{$version}/css/fixedColumns.bootstrap5.min.css",
                "{$cdnBase}/buttons/{$buttonsVersion}/css/buttons.bootstrap5.min.css",
                "{$cdnBase}/select/{$version}/css/select.bootstrap5.min.css",
            ],
            'js' => [
                // Core DataTables
                "{$cdnBase}/{$version}/js/jquery.dataTables.min.js",
                "{$cdnBase}/{$version}/js/dataTables.bootstrap5.min.js",
                
                // Extensions
                "{$cdnBase}/fixedcolumns/{$version}/js/dataTables.fixedColumns.min.js",
                "{$cdnBase}/select/{$version}/js/dataTables.select.min.js",
                
                // Buttons extension (core)
                "{$cdnBase}/buttons/{$buttonsVersion}/js/dataTables.buttons.min.js",
                "{$cdnBase}/buttons/{$buttonsVersion}/js/buttons.bootstrap5.min.js",
                
                // Export buttons (HTML5)
                "{$cdnBase}/buttons/{$buttonsVersion}/js/buttons.html5.min.js",
                "{$cdnBase}/buttons/{$buttonsVersion}/js/buttons.print.min.js",
                "{$cdnBase}/buttons/{$buttonsVersion}/js/buttons.colVis.min.js",
                
                // Required libraries for Excel/PDF export
                'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $feature): bool
    {
        return $this->supportedFeatures[$feature] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'datatables';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return config('canvastack-table.assets.datatables_version', '1.13.8');
    }

    /**
     * {@inheritdoc}
     */
    public function processServerSide(TableBuilder $table): array
    {
        // DataTables uses Yajra for server-side processing
        // This is handled by the existing TableBuilder implementation
        // We just need to ensure the response format is correct
        
        // The AdminRenderer already handles this through the existing
        // server-side processing logic, so we delegate to it
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Prepare data for AdminRenderer.
     *
     * Converts TableBuilder configuration into the format expected
     * by AdminRenderer.
     *
     * @param TableBuilder $table The table builder instance
     * @return array<string, mixed> The prepared data
     */
    protected function prepareRenderData(TableBuilder $table): array
    {
        // Get table configuration
        $config = $table->toArray();

        // Build fixed_columns array if fixedLeft or fixedRight is set
        $fixedColumns = null;
        if ($config['fixedLeft'] !== null || $config['fixedRight'] !== null) {
            $fixedColumns = [
                'left' => $config['fixedLeft'],
                'right' => $config['fixedRight'],
            ];
        }

        // Prepare data structure for AdminRenderer
        $data = [
            'columns' => $config['columns'] ?? [],
            'rows' => $table->getData(),
            'actions' => $config['actions'] ?? [],
            'config' => $this->config,
            'table_id' => $config['tableName'] ?? 'datatable',
            'server_side' => $config['serverSide'] ?? false,
            'ajax_url' => $table->getAjaxUrl(),
            'hidden_columns' => $config['hiddenColumns'] ?? [],
            'right_columns' => $this->getRightAlignedColumns($config),
            'center_columns' => $this->getCenterAlignedColumns($config),
            'column_widths' => $config['columnWidths'] ?? [],
            'column_colors' => $config['columnColors'] ?? [],
            'fixed_columns' => $fixedColumns,
            'buttons' => $table->getExportButtons(),
            'searchable_columns' => $config['searchableColumns'] ?? [],
            'order_by' => $this->getOrderByConfig($config),
            'filters' => $config['filters'] ?? [],
            'filter_groups' => $config['filterGroups'] ?? [],
        ];

        return $data;
    }

    /**
     * Get DataTables.js configuration.
     *
     * Builds the DataTables configuration object from TableBuilder settings.
     *
     * @param TableBuilder $table The table builder instance
     * @return array<string, mixed> The DataTables configuration
     */
    protected function getDataTablesConfig(TableBuilder $table): array
    {
        $config = $table->toArray();

        $dtConfig = [
            'processing' => true,
            'serverSide' => $config['serverSide'] ?? false,
            'ajax' => $table->getAjaxUrl(),
            'pageLength' => $config['displayLimit'] ?? 10,
            'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            'order' => $this->getOrderConfig($config),
            'searching' => !empty($config['searchableColumns']),
            'ordering' => true,
            'info' => true,
            'autoWidth' => false,
            'responsive' => true,
            'language' => $this->getLanguageConfig(),
        ];

        // Add scrolling if fixed columns are enabled
        if ($config['fixedLeft'] !== null || $config['fixedRight'] !== null) {
            $dtConfig['scrollX'] = true;
            $dtConfig['scrollY'] = '500px';
            $dtConfig['scrollCollapse'] = true;
        }

        // Add dom configuration to include buttons
        $buttons = $table->getExportButtons();
        if (!empty($buttons)) {
            // Include buttons in the DataTables DOM
            // B = Buttons, l = length changing, f = filtering, r = processing, t = table, i = info, p = pagination
            $dtConfig['dom'] = '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' .
                              '<"row"<"col-sm-12 col-md-12"B>>' .
                              '<"row"<"col-sm-12"tr>>' .
                              '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>';
        }

        return $dtConfig;
    }

    /**
     * Get FixedColumns configuration.
     *
     * Builds the FixedColumns extension configuration from TableBuilder settings.
     * This enables column pinning functionality.
     *
     * @param TableBuilder $table The table builder instance
     * @return array<string, int>|null The FixedColumns configuration or null if not set
     */
    protected function getFixedColumnsConfig(TableBuilder $table): ?array
    {
        $fixedLeft = $table->getFixedLeft();
        $fixedRight = $table->getFixedRight();

        if ($fixedLeft === null && $fixedRight === null) {
            return null;
        }

        $config = [];

        // Left fixed columns
        if ($fixedLeft !== null && $fixedLeft > 0) {
            $config['leftColumns'] = (int) $fixedLeft;
        }

        // Right fixed columns
        if ($fixedRight !== null && $fixedRight > 0) {
            $config['rightColumns'] = (int) $fixedRight;
        }

        return !empty($config) ? $config : null;
    }

    /**
     * Get Buttons configuration.
     *
     * Builds the Buttons extension configuration for export functionality.
     * Supports Excel, CSV, PDF, and Print exports with proper column exclusion.
     *
     * Requirements:
     * - 17.1: Support Excel export
     * - 17.2: Support CSV export
     * - 17.3: Support PDF export
     * - 17.4: Support print functionality
     * - 17.5: Respect non-exportable columns
     * - 34.1: Use setButtons() method for export configuration
     *
     * @param TableBuilder $table The table builder instance
     * @return array<string, mixed>|null The Buttons configuration or null if not set
     */
    protected function getButtonsConfig(TableBuilder $table): ?array
    {
        $buttons = $table->getExportButtons();

        if (empty($buttons)) {
            return null;
        }

        $config = [];
        $nonExportableColumns = $table->getNonExportableColumns();
        $columns = $table->getColumns();

        // Build column indices to exclude (non-exportable columns)
        $excludeIndices = [];
        if (!empty($nonExportableColumns)) {
            $columnKeys = array_keys($columns);
            foreach ($nonExportableColumns as $nonExportableColumn) {
                $index = array_search($nonExportableColumn, $columnKeys);
                if ($index !== false) {
                    $excludeIndices[] = $index;
                }
            }
        }

        // Map button names to DataTables button types
        $buttonMap = [
            'excel' => 'excel',
            'csv' => 'csv',
            'pdf' => 'pdf',
            'print' => 'print',
            'copy' => 'copy',
        ];

        foreach ($buttons as $button) {
            // Normalize button name
            $buttonType = strtolower($button);
            $extend = $buttonMap[$buttonType] ?? $buttonType;

            $buttonConfig = [
                'extend' => $extend,
                'className' => 'btn btn-sm btn-primary me-1',
                'text' => $this->getButtonText($extend),
            ];

            // Configure export options
            $exportOptions = [];

            // Exclude non-exportable columns
            if (!empty($excludeIndices)) {
                // Build column selector that excludes specific indices
                $exportOptions['columns'] = function () use ($excludeIndices, $columns) {
                    $indices = [];
                    for ($i = 0; $i < count($columns); $i++) {
                        if (!in_array($i, $excludeIndices)) {
                            $indices[] = $i;
                        }
                    }
                    return $indices;
                };
            } else {
                // Export all visible columns
                $exportOptions['columns'] = ':visible';
            }

            // Add export options to button config
            if (!empty($exportOptions)) {
                $buttonConfig['exportOptions'] = $exportOptions;
            }

            // Add button-specific configuration
            switch ($extend) {
                case 'excel':
                    $buttonConfig['title'] = $table->getTableName() ?? 'Export';
                    $buttonConfig['filename'] = $this->generateFilename($table, 'xlsx');
                    break;

                case 'csv':
                    $buttonConfig['title'] = $table->getTableName() ?? 'Export';
                    $buttonConfig['filename'] = $this->generateFilename($table, 'csv');
                    $buttonConfig['fieldSeparator'] = ',';
                    $buttonConfig['fieldBoundary'] = '"';
                    break;

                case 'pdf':
                    $buttonConfig['title'] = $table->getTableName() ?? 'Export';
                    $buttonConfig['filename'] = $this->generateFilename($table, 'pdf');
                    $buttonConfig['orientation'] = 'landscape';
                    $buttonConfig['pageSize'] = 'A4';
                    break;

                case 'print':
                    $buttonConfig['title'] = $table->getTableName() ?? 'Print';
                    $buttonConfig['autoPrint'] = true;
                    break;
            }

            $config[] = $buttonConfig;
        }

        return $config;
    }

    /**
     * Get button text for export buttons.
     *
     * Returns translated button text for export functionality.
     *
     * @param string $buttonType The button type (excel, csv, pdf, print)
     * @return string The translated button text
     */
    protected function getButtonText(string $buttonType): string
    {
        $translations = [
            'excel' => __('components.table.export_excel'),
            'csv' => __('components.table.export_csv'),
            'pdf' => __('components.table.export_pdf'),
            'print' => __('components.table.print'),
            'copy' => __('components.table.copy'),
        ];

        return $translations[$buttonType] ?? ucfirst($buttonType);
    }

    /**
     * Generate filename for export.
     *
     * Creates a filename with timestamp for exported files.
     *
     * @param TableBuilder $table The table builder instance
     * @param string $extension The file extension (xlsx, csv, pdf)
     * @return string The generated filename
     */
    protected function generateFilename(TableBuilder $table, string $extension): string
    {
        $tableName = $table->getTableName() ?? 'export';
        $timestamp = date('Y-m-d_His');
        
        return "{$tableName}_{$timestamp}.{$extension}";
    }

    /**
     * Get Select configuration.
     *
     * Builds the Select extension configuration for row selection functionality.
     * Supports both single and multiple selection modes.
     *
     * @param TableBuilder $table The table builder instance
     * @return array<string, mixed>|null The Select configuration or null if not enabled
     */
    protected function getSelectConfig(TableBuilder $table): ?array
    {
        $selectable = $table->getSelectable();

        if (!$selectable) {
            return null;
        }

        $selectionMode = $table->getSelectionMode() ?? 'multiple';

        $config = [
            'style' => $selectionMode === 'single' ? 'single' : 'multi',
            'selector' => 'td:first-child',
            'className' => 'selected',
        ];

        // Add select all checkbox for multiple selection mode
        if ($selectionMode === 'multiple') {
            $config['selectAll'] = true;
        }

        return $config;
    }

    /**
     * Get right-aligned columns from configuration.
     *
     * @param array<string, mixed> $config The table configuration
     * @return array<string> The right-aligned columns
     */
    protected function getRightAlignedColumns(array $config): array
    {
        $alignments = $config['columnAlignments'] ?? [];
        $rightColumns = [];

        foreach ($alignments as $column => $alignment) {
            if ($alignment === 'right') {
                $rightColumns[] = $column;
            }
        }

        return $rightColumns;
    }

    /**
     * Get center-aligned columns from configuration.
     *
     * @param array<string, mixed> $config The table configuration
     * @return array<string> The center-aligned columns
     */
    protected function getCenterAlignedColumns(array $config): array
    {
        $alignments = $config['columnAlignments'] ?? [];
        $centerColumns = [];

        foreach ($alignments as $column => $alignment) {
            if ($alignment === 'center') {
                $centerColumns[] = $column;
            }
        }

        return $centerColumns;
    }

    /**
     * Get order by configuration.
     *
     * @param array<string, mixed> $config The table configuration
     * @return array<string, mixed>|null The order by configuration
     */
    protected function getOrderByConfig(array $config): ?array
    {
        if (isset($config['orderColumn']) && $config['orderColumn'] !== null) {
            return [
                'column' => $config['orderColumn'],
                'direction' => $config['orderDirection'] ?? 'asc',
            ];
        }

        return null;
    }

    /**
     * Get order configuration.
     *
     * @param array<string, mixed> $config The table configuration
     * @return array<int, array<int, string|int>> The order configuration
     */
    protected function getOrderConfig(array $config): array
    {
        if (isset($config['orderColumn']) && $config['orderColumn'] !== null) {
            $column = $config['orderColumn'];
            $direction = $config['orderDirection'] ?? 'asc';
            return [[$column, $direction]];
        }

        return [[0, 'asc']];
    }

    /**
     * Get language configuration for i18n.
     *
     * @return array<string, string> The language configuration
     */
    protected function getLanguageConfig(): array
    {
        return [
            'processing' => __('components.table.processing'),
            'search' => __('components.table.search'),
            'lengthMenu' => __('components.table.length_menu'),
            'info' => __('components.table.info'),
            'infoEmpty' => __('components.table.info_empty'),
            'infoFiltered' => __('components.table.info_filtered'),
            'infoPostFix' => '',
            'loadingRecords' => __('components.table.loading'),
            'zeroRecords' => __('components.table.no_data'),
            'emptyTable' => __('components.table.empty_table'),
            'paginate' => [
                'first' => __('components.table.first'),
                'previous' => __('components.table.previous'),
                'next' => __('components.table.next'),
                'last' => __('components.table.last'),
            ],
            'aria' => [
                'sortAscending' => __('components.table.sort_ascending'),
                'sortDescending' => __('components.table.sort_descending'),
            ],
        ];
    }
}

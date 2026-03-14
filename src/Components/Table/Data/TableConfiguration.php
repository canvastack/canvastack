<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Data;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * TableConfiguration Data Model
 *
 * Encapsulates all table configuration in a structured format.
 * This class provides validation and serialization methods for table settings.
 *
 * @package Canvastack\Canvastack\Components\Table\Data
 */
class TableConfiguration
{
    // Engine settings
    public string $engine;
    public string $context; // 'admin' or 'public'

    // Data source
    public ?Model $model = null;
    public ?Collection $collection = null;
    public ?array $data = null;
    public ?string $connection = null; // Database connection name
    public ?string $tableName = null; // Table name (for non-model queries)

    // Column configuration
    public array $fields = [];
    public array $hiddenColumns = [];
    public array $rightColumns = [];
    public array $centerColumns = [];
    public array $columnWidths = [];
    public array $columnColors = [];
    public array $nonExportableColumns = [];

    // Fixed columns (column pinning)
    public ?int $fixedLeft = null;
    public ?int $fixedRight = null;

    // Sorting
    public ?string $orderByColumn = null;
    public ?string $orderByDirection = null;

    // Pagination
    public bool $serverSide = true;
    public int $pageSize = 25;
    public array $pageSizeOptions = [10, 25, 50, 100];

    // Searching
    public bool $searchable = true;
    public array $searchableColumns = [];

    // Filtering
    public array $filterGroups = [];
    public array $activeFilters = [];

    // Actions
    public array $actions = [];
    public bool $includeDefaultActions = true;

    // Row selection
    public bool $selectable = false;
    public string $selectionMode = 'multiple'; // 'single' or 'multiple'

    // Export
    public array $buttons = [];
    public bool $exportEnabled = true;

    // Performance
    public ?int $cacheTime = null;
    public array $eagerLoad = [];
    public bool $virtualScrolling = false;
    public bool $lazyLoading = false;
    public bool $columnResizing = false;
    public bool $modernDesign = false;

    // Relationships
    public array $relations = [];
    public array $fieldReplacements = [];

    // Formulas and conditions
    public array $formulas = [];
    public array $columnConditions = [];

    // Permissions
    public ?string $permission = null;

    // Tabs
    public array $tabs = [];
    public ?string $activeTab = null;

    // Custom renderers
    public array $columnRenderers = [];

    // Theme and i18n
    public string $theme = 'default';
    public string $locale = 'en';
    public bool $rtl = false;

    // State management
    public bool $persistState = true;
    public string $stateKey = '';

    /**
     * Create a new TableConfiguration instance.
     *
     * @param string $engine
     * @param string $context
     */
    public function __construct(string $engine = 'datatables', string $context = 'admin')
    {
        $this->engine = $engine;
        $this->context = $context;
        $this->stateKey = $this->generateStateKey();
    }

    /**
     * Validate the configuration.
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // Validate engine
        if (!in_array($this->engine, ['datatables', 'tanstack'])) {
            $errors[] = "Invalid engine: {$this->engine}. Must be 'datatables' or 'tanstack'.";
        }

        // Validate context
        if (!in_array($this->context, ['admin', 'public'])) {
            $errors[] = "Invalid context: {$this->context}. Must be 'admin' or 'public'.";
        }

        // Validate data source (must have at least one)
        if ($this->model === null && $this->collection === null && $this->data === null) {
            $errors[] = 'No data source specified. Must provide model, collection, or data array.';
        }

        // Validate only one data source is set
        $dataSources = array_filter([
            $this->model !== null,
            $this->collection !== null,
            $this->data !== null,
        ]);
        if (count($dataSources) > 1) {
            $errors[] = 'Multiple data sources specified. Only one of model, collection, or data should be set.';
        }

        // Validate page size
        if ($this->pageSize < 1) {
            $errors[] = "Invalid page size: {$this->pageSize}. Must be greater than 0.";
        }

        // Validate page size options
        if (empty($this->pageSizeOptions)) {
            $errors[] = 'Page size options cannot be empty.';
        }

        // Validate selection mode
        if (!in_array($this->selectionMode, ['single', 'multiple'])) {
            $errors[] = "Invalid selection mode: {$this->selectionMode}. Must be 'single' or 'multiple'.";
        }

        // Validate fixed columns
        if ($this->fixedLeft !== null && $this->fixedLeft < 0) {
            $errors[] = "Invalid fixedLeft: {$this->fixedLeft}. Must be 0 or greater.";
        }
        if ($this->fixedRight !== null && $this->fixedRight < 0) {
            $errors[] = "Invalid fixedRight: {$this->fixedRight}. Must be 0 or greater.";
        }

        // Validate order direction
        if ($this->orderByDirection !== null && !in_array(strtolower($this->orderByDirection), ['asc', 'desc'])) {
            $errors[] = "Invalid order direction: {$this->orderByDirection}. Must be 'asc' or 'desc'.";
        }

        // Validate cache time
        if ($this->cacheTime !== null && $this->cacheTime < 0) {
            $errors[] = "Invalid cache time: {$this->cacheTime}. Must be 0 or greater.";
        }

        return $errors;
    }

    /**
     * Check if configuration is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Serialize configuration to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'engine' => $this->engine,
            'context' => $this->context,
            'has_model' => $this->model !== null,
            'has_collection' => $this->collection !== null,
            'has_data' => $this->data !== null,
            'fields' => $this->fields,
            'hidden_columns' => $this->hiddenColumns,
            'right_columns' => $this->rightColumns,
            'center_columns' => $this->centerColumns,
            'column_widths' => $this->columnWidths,
            'column_colors' => $this->columnColors,
            'non_exportable_columns' => $this->nonExportableColumns,
            'fixed_left' => $this->fixedLeft,
            'fixed_right' => $this->fixedRight,
            'order_by_column' => $this->orderByColumn,
            'order_by_direction' => $this->orderByDirection,
            'server_side' => $this->serverSide,
            'page_size' => $this->pageSize,
            'page_size_options' => $this->pageSizeOptions,
            'searchable' => $this->searchable,
            'searchable_columns' => $this->searchableColumns,
            'filter_groups' => $this->filterGroups,
            'active_filters' => $this->activeFilters,
            'actions' => $this->actions,
            'include_default_actions' => $this->includeDefaultActions,
            'selectable' => $this->selectable,
            'selection_mode' => $this->selectionMode,
            'buttons' => $this->buttons,
            'export_enabled' => $this->exportEnabled,
            'cache_time' => $this->cacheTime,
            'eager_load' => $this->eagerLoad,
            'virtual_scrolling' => $this->virtualScrolling,
            'lazy_loading' => $this->lazyLoading,
            'relations' => $this->relations,
            'field_replacements' => $this->fieldReplacements,
            'formulas' => $this->formulas,
            'column_conditions' => $this->columnConditions,
            'permission' => $this->permission,
            'tabs' => $this->tabs,
            'active_tab' => $this->activeTab,
            'column_renderers' => array_keys($this->columnRenderers), // Only keys, not closures
            'theme' => $this->theme,
            'locale' => $this->locale,
            'rtl' => $this->rtl,
            'persist_state' => $this->persistState,
            'state_key' => $this->stateKey,
        ];
    }

    /**
     * Serialize configuration to JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Generate a unique state key for this configuration.
     *
     * @return string
     */
    protected function generateStateKey(): string
    {
        $parts = [
            $this->context,
            $this->model ? class_basename($this->model) : 'collection',
        ];

        return implode('_', array_filter($parts));
    }

    /**
     * Get data source type.
     *
     * @return string 'model', 'collection', 'data', or 'none'
     */
    public function getDataSourceType(): string
    {
        if ($this->model !== null) {
            return 'model';
        }
        if ($this->collection !== null) {
            return 'collection';
        }
        if ($this->data !== null) {
            return 'data';
        }
        return 'none';
    }

    /**
     * Check if using Eloquent model as data source.
     *
     * @return bool
     */
    public function hasModel(): bool
    {
        return $this->model !== null;
    }

    /**
     * Check if using collection as data source.
     *
     * @return bool
     */
    public function hasCollection(): bool
    {
        return $this->collection !== null;
    }

    /**
     * Check if using array as data source.
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return $this->data !== null;
    }

    /**
     * Check if server-side processing is enabled.
     *
     * @return bool
     */
    public function isServerSide(): bool
    {
        return $this->serverSide && $this->hasModel();
    }

    /**
     * Check if client-side processing should be used.
     *
     * @return bool
     */
    public function isClientSide(): bool
    {
        return !$this->isServerSide();
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheTime !== null && $this->cacheTime > 0;
    }

    /**
     * Check if virtual scrolling is enabled.
     *
     * @return bool
     */
    public function isVirtualScrollingEnabled(): bool
    {
        return $this->virtualScrolling;
    }

    /**
     * Check if lazy loading is enabled.
     *
     * @return bool
     */
    public function isLazyLoadingEnabled(): bool
    {
        return $this->lazyLoading;
    }

    /**
     * Check if export is enabled.
     *
     * @return bool
     */
    public function isExportEnabled(): bool
    {
        return $this->exportEnabled && !empty($this->buttons);
    }

    /**
     * Check if row selection is enabled.
     *
     * @return bool
     */
    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    /**
     * Check if fixed columns are configured.
     *
     * @return bool
     */
    public function hasFixedColumns(): bool
    {
        return $this->fixedLeft !== null || $this->fixedRight !== null;
    }

    /**
     * Check if filtering is configured.
     *
     * @return bool
     */
    public function hasFilters(): bool
    {
        return !empty($this->filterGroups);
    }

    /**
     * Check if tabs are configured.
     *
     * @return bool
     */
    public function hasTabs(): bool
    {
        return !empty($this->tabs);
    }

    /**
     * Get configuration summary for debugging.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'engine' => $this->engine,
            'context' => $this->context,
            'data_source' => $this->getDataSourceType(),
            'server_side' => $this->isServerSide(),
            'fields_count' => count($this->fields),
            'actions_count' => count($this->actions),
            'filters_count' => count($this->filterGroups),
            'cache_enabled' => $this->isCacheEnabled(),
            'virtual_scrolling' => $this->virtualScrolling,
            'lazy_loading' => $this->lazyLoading,
            'selectable' => $this->selectable,
            'export_enabled' => $this->isExportEnabled(),
            'has_fixed_columns' => $this->hasFixedColumns(),
            'has_tabs' => $this->hasTabs(),
        ];
    }
}

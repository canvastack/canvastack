<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\ServerSide;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Data\ServerSideRequest;
use Canvastack\Canvastack\Components\Table\Data\ServerSideResponse;
use Canvastack\Canvastack\Components\Table\Exceptions\ServerSideException;
use Canvastack\Canvastack\Components\Table\Support\FormulaParser;
use Canvastack\Canvastack\Components\Table\Processors\DataFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * TanStack Server-Side Adapter
 * 
 * Handles server-side processing for TanStack Table engine.
 * Provides pagination, sorting, filtering, and searching on the backend.
 * 
 * @package Canvastack\Canvastack\Components\Table\ServerSide
 */
class TanStackServerAdapter
{
    /**
     * Formula parser instance.
     *
     * @var FormulaParser
     */
    protected FormulaParser $formulaParser;
    
    /**
     * Data formatter instance for date/time localization.
     *
     * VALIDATES: Requirements 40.13, 52.9
     *
     * @var DataFormatter
     */
    protected DataFormatter $dataFormatter;
    
    /**
     * Table builder instance.
     *
     * @var TableBuilder|null
     */
    protected ?TableBuilder $table = null;
    
    /**
     * Constructor.
     *
     * @param FormulaParser|null $formulaParser
     * @param DataFormatter|null $dataFormatter
     */
    public function __construct(
        ?FormulaParser $formulaParser = null,
        ?DataFormatter $dataFormatter = null
    ) {
        $this->formulaParser = $formulaParser ?? new FormulaParser();
        $this->dataFormatter = $dataFormatter ?? new DataFormatter();
    }
    
    /**
     * Configure the adapter with table instance.
     *
     * @param TableBuilder $table
     * @return void
     */
    public function configure(TableBuilder $table): void
    {
        $this->table = $table;
    }
    /**
     * Process server-side request.
     *
     * @param TableBuilder $table
     * @return array
     * @throws ServerSideException
     */
    public function process(TableBuilder $table): array
    {
        try {
            // Parse request
            $request = ServerSideRequest::fromTanStack(request()->all());
            
            // Build query
            $query = $this->buildQuery($table, $request);
            
            // Get total count before filtering
            $total = $this->getTotalCount($table);
            
            // Apply filters
            $this->applyGlobalFilter($query, $request, $table);
            $this->applyColumnFilters($query, $request, $table);
            $this->applyCustomFilters($query, $request, $table);
            
            // Get filtered count
            $filtered = $query->count();
            
            // Apply sorting
            $this->applySorting($query, $request, $table);
            
            // Apply pagination
            $this->applyPagination($query, $request);
            
            // Get data
            $data = $query->get();
            
            // Transform data
            $transformedData = $this->transformData($data, $table);
            
            // Build response
            $response = new ServerSideResponse();
            $response->data = $transformedData;
            $response->total = $total;
            $response->filtered = $filtered;
            $response->page = $request->page;
            $response->pageSize = $request->pageSize;
            $response->totalPages = (int) ceil($filtered / $request->pageSize);
            
            return $response->toTanStack();
            
        } catch (\Exception $e) {
            Log::error('TanStackServerAdapter error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => request()->all(),
            ]);
            
            throw new ServerSideException(
                'Server-side processing failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Build base query.
     *
     * @param TableBuilder $table
     * @param ServerSideRequest $request
     * @return Builder
     */
    protected function buildQuery(TableBuilder $table, ServerSideRequest $request): Builder
    {
        $model = $table->getModel();
        
        if (!$model instanceof Model) {
            throw new ServerSideException('Model must be an instance of Eloquent Model');
        }
        
        $query = $model->newQuery();
        
        // Apply eager loading to prevent N+1 queries
        $eagerLoad = $table->getEagerLoad();
        if (!empty($eagerLoad)) {
            $query->with($eagerLoad);
        }
        
        // Apply permission filtering if configured
        $permission = $table->getPermission();
        if ($permission) {
            $this->applyPermissionFilter($query, $permission);
        }
        
        return $query;
    }
    
    /**
     * Get total count before filtering.
     *
     * @param TableBuilder $table
     * @return int
     */
    protected function getTotalCount(TableBuilder $table): int
    {
        $model = $table->getModel();
        
        if (!$model instanceof Model) {
            return 0;
        }
        
        $query = $model->newQuery();
        
        // Apply permission filtering if configured
        $permission = $table->getPermission();
        if ($permission) {
            $this->applyPermissionFilter($query, $permission);
        }
        
        return $query->count();
    }
    
    /**
     * Apply global filter (search across all searchable columns).
     *
     * @param Builder $query
     * @param ServerSideRequest $request
     * @param TableBuilder $table
     * @return void
     */
    protected function applyGlobalFilter(Builder $query, ServerSideRequest $request, TableBuilder $table): void
    {
        if (empty($request->searchValue)) {
            return;
        }
        
        $searchableColumns = $table->getSearchableColumns();
        
        if (empty($searchableColumns)) {
            return;
        }
        
        $searchValue = $request->searchValue;
        
        $query->where(function ($q) use ($searchableColumns, $searchValue) {
            foreach ($searchableColumns as $column) {
                // Validate column name to prevent SQL injection
                if ($this->isValidColumnName($column)) {
                    $q->orWhere($column, 'LIKE', "%{$searchValue}%");
                }
            }
        });
    }
    
    /**
     * Apply column-specific filters.
     *
     * @param Builder $query
     * @param ServerSideRequest $request
     * @param TableBuilder $table
     * @return void
     */
    protected function applyColumnFilters(Builder $query, ServerSideRequest $request, TableBuilder $table): void
    {
        if (empty($request->columnSearches)) {
            return;
        }
        
        foreach ($request->columnSearches as $column => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Validate column name to prevent SQL injection
            if (!$this->isValidColumnName($column)) {
                continue;
            }
            
            $query->where($column, 'LIKE', "%{$value}%");
        }
    }
    
    /**
     * Apply custom filters (advanced filtering).
     *
     * @param Builder $query
     * @param ServerSideRequest $request
     * @param TableBuilder $table
     * @return void
     */
    protected function applyCustomFilters(Builder $query, ServerSideRequest $request, TableBuilder $table): void
    {
        if (empty($request->filters)) {
            Log::info('TanStackServerAdapter: No filters to apply');
            return;
        }
        
        Log::info('TanStackServerAdapter: Applying custom filters', [
            'filters' => $request->filters,
        ]);
        
        foreach ($request->filters as $filter => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Validate filter name to prevent SQL injection
            if (!$this->isValidColumnName($filter)) {
                Log::warning('TanStackServerAdapter: Invalid filter name', ['filter' => $filter]);
                continue;
            }
            
            Log::info('TanStackServerAdapter: Processing filter', [
                'filter' => $filter,
                'value' => $value,
                'type' => gettype($value),
            ]);
            
            // Handle different filter types
            if (is_array($value)) {
                // Array filter (e.g., status in ['active', 'pending'])
                $query->whereIn($filter, $value);
                Log::info('TanStackServerAdapter: Applied whereIn filter', ['filter' => $filter, 'values' => $value]);
            } elseif (strpos($value, ',') !== false) {
                // Range filter (e.g., "100,500" for price between 100 and 500)
                $range = explode(',', $value);
                if (count($range) === 2) {
                    $query->whereBetween($filter, [(float) $range[0], (float) $range[1]]);
                    Log::info('TanStackServerAdapter: Applied whereBetween filter', ['filter' => $filter, 'range' => $range]);
                }
            } else {
                // Check if this is a date column (ends with _at or is named 'date')
                $isDateColumn = (substr($filter, -3) === '_at' || $filter === 'date' || $filter === 'created_at' || $filter === 'updated_at');
                
                if ($isDateColumn && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    // Date filter - use whereDate for exact date match
                    $query->whereDate($filter, $value);
                    Log::info('TanStackServerAdapter: Applied whereDate filter', ['filter' => $filter, 'date' => $value]);
                } else {
                    // Exact match filter
                    $query->where($filter, $value);
                    Log::info('TanStackServerAdapter: Applied where filter', ['filter' => $filter, 'value' => $value]);
                }
            }
        }
        
        Log::info('TanStackServerAdapter: Custom filters applied', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
    }
    
    /**
     * Apply sorting.
     *
     * @param Builder $query
     * @param ServerSideRequest $request
     * @param TableBuilder $table
     * @return void
     */
    protected function applySorting(Builder $query, ServerSideRequest $request, TableBuilder $table): void
    {
        // Handle multi-column sorting
        if (!empty($request->sortColumns)) {
            foreach ($request->sortColumns as $sort) {
                $column = $sort['column'] ?? null;
                $direction = $sort['direction'] ?? 'asc';
                
                if ($column && $this->isValidColumnName($column)) {
                    $query->orderBy($column, $direction);
                }
            }
            return;
        }
        
        // Handle single column sorting
        if ($request->sortColumn && $this->isValidColumnName($request->sortColumn)) {
            $query->orderBy($request->sortColumn, $request->sortDirection ?? 'asc');
            return;
        }
        
        // Apply default sorting from table configuration
        $orderByColumn = $table->getOrderByColumn();
        $orderByDirection = $table->getOrderByDirection();
        
        if ($orderByColumn && $this->isValidColumnName($orderByColumn)) {
            $query->orderBy($orderByColumn, $orderByDirection ?? 'asc');
        }
    }
    
    /**
     * Apply pagination.
     *
     * @param Builder $query
     * @param ServerSideRequest $request
     * @return void
     */
    protected function applyPagination(Builder $query, ServerSideRequest $request): void
    {
        $query->skip($request->start)->take($request->length);
    }
    
    /**
     * Transform data for response.
     * 
     * Applies transformations in this order:
     * 1. Field replacements (foreign key relationships)
     * 2. Custom renderers (custom column rendering)
     * 3. Formulas (calculated columns)
     * 4. Actions (row action buttons)
     *
     * @param \Illuminate\Support\Collection $data
     * @param TableBuilder $table
     * @return array
     */
    protected function transformData($data, TableBuilder $table): array
    {
        $transformed = [];
        
        foreach ($data as $row) {
            try {
                $item = $row->toArray();
                
                // Step 1: Apply field replacements (foreign key relationships)
                $item = $this->applyFieldReplacements($item, $row, $table);
                
                // Step 2: Apply custom renderers (custom column rendering)
                $item = $this->applyCustomRenderers($item, $row, $table);
                
                // Step 3: Apply formulas (calculated columns)
                $item = $this->applyFormulas($item, $row, $table);
                
                // Step 4: Apply date/time localization (Requirements 40.13, 52.9)
                $item = $this->applyDateFormatting($item, $row, $table);
                
                // Step 5: Build actions (row action buttons)
                $item['_actions'] = $this->buildActions($row, $table);
                
                // Step 6: Add row metadata
                $item['_id'] = $row->id ?? null;
                $item['_class'] = $this->getRowClass($row, $table);
                
                $transformed[] = $item;
                
            } catch (\Exception $e) {
                // Log error but continue processing other rows
                Log::warning('Row transformation failed', [
                    'row_id' => $row->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                
                // Include row with error indicator
                $item = $row->toArray();
                $item['_error'] = true;
                $item['_error_message'] = 'Transformation failed';
                $transformed[] = $item;
            }
        }
        
        return $transformed;
    }
    
    /**
     * Apply field replacements (for foreign key relationships).
     * 
     * Replaces foreign key IDs with related model display values.
     * Example: user_id (1) → user_name ("John Doe")
     *
     * @param array $item
     * @param Model $row
     * @param TableBuilder $table
     * @return array
     */
    protected function applyFieldReplacements(array $item, Model $row, TableBuilder $table): array
    {
        $replacements = $table->getFieldReplacements();
        
        if (empty($replacements)) {
            return $item;
        }
        
        foreach ($replacements as $field => $config) {
            try {
                $relationName = $config['relation'] ?? null;
                $displayField = $config['display'] ?? 'name';
                $fallback = $config['fallback'] ?? null;
                
                if (!$relationName) {
                    continue;
                }
                
                // Check if relation is loaded (should be via eager loading)
                if ($row->relationLoaded($relationName)) {
                    $related = $row->$relationName;
                    
                    if ($related) {
                        // Get display value from related model
                        if (is_callable($displayField)) {
                            $item[$field] = $displayField($related);
                        } elseif (isset($related->$displayField)) {
                            $item[$field] = $related->$displayField;
                        } else {
                            $item[$field] = $fallback ?? $item[$field];
                        }
                    } else {
                        $item[$field] = $fallback ?? $item[$field];
                    }
                } else {
                    // Relation not loaded - log warning
                    Log::warning('Relation not loaded for field replacement', [
                        'field' => $field,
                        'relation' => $relationName,
                        'hint' => 'Use $table->eager([\'' . $relationName . '\']) to prevent N+1 queries',
                    ]);
                    
                    $item[$field] = $fallback ?? $item[$field];
                }
                
            } catch (\Exception $e) {
                Log::warning('Field replacement failed', [
                    'field' => $field,
                    'error' => $e->getMessage(),
                ]);
                
                // Keep original value on error
                continue;
            }
        }
        
        return $item;
    }
    
    /**
     * Apply custom column renderers.
     * 
     * Allows custom rendering logic for specific columns.
     * Renderers receive the full row model and can return any value.
     *
     * @param array $item
     * @param Model $row
     * @param TableBuilder $table
     * @return array
     */
    protected function applyCustomRenderers(array $item, Model $row, TableBuilder $table): array
    {
        $renderers = $table->getColumnRenderers();
        
        if (empty($renderers)) {
            return $item;
        }
        
        foreach ($renderers as $column => $renderer) {
            try {
                if (is_callable($renderer)) {
                    $rendered = $renderer($row);
                    
                    // Store both raw and rendered values
                    $item[$column . '_raw'] = $item[$column] ?? null;
                    $item[$column] = $rendered;
                }
            } catch (\Exception $e) {
                Log::warning('Custom renderer failed', [
                    'column' => $column,
                    'error' => $e->getMessage(),
                ]);
                
                // Keep original value on error
                continue;
            }
        }
        
        return $item;
    }
    
    /**
     * Apply formula calculations.
     * 
     * Evaluates mathematical formulas for calculated columns.
     * Uses FormulaParser for safe evaluation without eval().
     * 
     * Example: total = price * quantity
     *
     * @param array $item
     * @param Model $row
     * @param TableBuilder $table
     * @return array
     */
    protected function applyFormulas(array $item, Model $row, TableBuilder $table): array
    {
        $formulas = $table->getFormulas();
        
        if (empty($formulas)) {
            return $item;
        }
        
        foreach ($formulas as $column => $config) {
            try {
                // Support both string formula and array config
                if (is_string($config)) {
                    $formula = $config;
                    $format = 'number';
                    $decimals = 2;
                } else {
                    $formula = $config['formula'] ?? $config;
                    $format = $config['format'] ?? 'number';
                    $decimals = $config['decimals'] ?? 2;
                }
                
                // Evaluate formula using FormulaParser
                $result = $this->formulaParser->evaluate($formula, $item);
                
                // Format result if format is specified
                if ($format !== 'raw') {
                    $item[$column . '_raw'] = $result;
                    $item[$column] = $this->formulaParser->format($result, $format, $decimals);
                } else {
                    $item[$column] = $result;
                }
                
            } catch (\Exception $e) {
                Log::warning('Formula evaluation failed', [
                    'column' => $column,
                    'formula' => $formula ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                
                // Set error indicator
                $item[$column] = null;
                $item[$column . '_error'] = true;
            }
        }
        
        return $item;
    }
    
    /**
     * Apply date/time localization to configured columns.
     *
     * Formats date/time columns using Carbon with localization support.
     * Uses Carbon::setLocale() for locale-aware formatting.
     *
     * LOCALIZATION SUPPORT:
     * - Uses Carbon::setLocale() for date/time localization (Requirement 52.9)
     * - Uses translatedFormat() for localized date formatting (Requirement 40.13)
     * - Uses diffForHumans() for relative time (Requirement 40.13)
     * - Automatically uses app()->getLocale() for current locale
     *
     * VALIDATES: Requirements 40.13, 52.9
     *
     * @param array $item Row data
     * @param Model $row Model instance
     * @param TableBuilder $table Table builder instance
     * @return array Transformed row data with formatted dates
     */
    protected function applyDateFormatting(array $item, Model $row, TableBuilder $table): array
    {
        // Get all columns from the table
        $columns = $table->getColumns();
        
        foreach ($columns as $column) {
            // Check if this column is configured as a date column
            if (!$table->isDateColumn($column)) {
                continue;
            }
            
            // Skip if column value is empty
            if (!isset($item[$column]) || empty($item[$column])) {
                continue;
            }
            
            try {
                $config = $table->getDateColumnConfig($column);
                $format = $config['format'] ?? 'localized';
                $useRelative = $config['useRelative'] ?? false;
                
                $value = $item[$column];
                
                // Store original value for reference
                $item[$column . '_raw'] = $value;
                
                // Format based on configuration
                if ($useRelative || $format === 'relative') {
                    // Use relative time format (e.g., "2 hours ago")
                    $item[$column] = $this->dataFormatter->formatRelativeTime($value);
                } elseif ($format === 'localized') {
                    // Use localized date format (e.g., "Monday, 27 February 2026")
                    $item[$column] = $this->dataFormatter->formatDateLocalized($value, 'l, d F Y');
                } elseif ($format === 'date') {
                    // Standard date format
                    $item[$column] = $this->dataFormatter->formatDateLocalized($value, 'Y-m-d');
                } elseif ($format === 'datetime') {
                    // Standard datetime format
                    $item[$column] = $this->dataFormatter->formatDateLocalized($value, 'Y-m-d H:i:s');
                } else {
                    // Custom format string
                    $item[$column] = $this->dataFormatter->formatDateLocalized($value, $format);
                }
                
            } catch (\Exception $e) {
                Log::warning('Date formatting failed', [
                    'column' => $column,
                    'value' => $item[$column] ?? 'null',
                    'error' => $e->getMessage(),
                ]);
                
                // Keep original value on error
                // Don't set error indicator to avoid breaking the UI
            }
        }
        
        return $item;
    }
    
    /**
     * Build action buttons for row.
     * 
     * Generates action button configuration for each row.
     * Supports conditional visibility, custom URLs, and HTTP methods.
     *
     * @param Model $row
     * @param TableBuilder $table
     * @return array
     */
    protected function buildActions(Model $row, TableBuilder $table): array
    {
        $actions = $table->getActions();
        
        if (empty($actions)) {
            return [];
        }
        
        $builtActions = [];
        
        foreach ($actions as $name => $config) {
            try {
                // Check if action should be visible for this row
                $condition = $config['condition'] ?? null;
                if ($condition && is_callable($condition) && !$condition($row)) {
                    continue;
                }
                
                // Build action URL
                $url = $config['url'] ?? null;
                if (is_callable($url)) {
                    $url = $url($row);
                } elseif (is_string($url)) {
                    // Replace placeholders with actual values
                    $url = $this->replacePlaceholders($url, $row);
                }
                
                // Build action label (support translation)
                $label = $config['label'] ?? ucfirst($name);
                if (function_exists('__')) {
                    $label = __($label);
                }
                
                // Build action configuration
                $action = [
                    'name' => $name,
                    'label' => $label,
                    'icon' => $config['icon'] ?? null,
                    'url' => $url,
                    'method' => strtoupper($config['method'] ?? 'GET'),
                    'confirm' => $config['confirm'] ?? null,
                    'class' => $config['class'] ?? 'btn-sm btn-primary',
                    'target' => $config['target'] ?? '_self',
                ];
                
                // Add custom attributes if provided
                if (isset($config['attributes'])) {
                    $action['attributes'] = $config['attributes'];
                }
                
                $builtActions[] = $action;
                
            } catch (\Exception $e) {
                Log::warning('Action build failed', [
                    'action' => $name,
                    'row_id' => $row->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                
                // Skip this action on error
                continue;
            }
        }
        
        return $builtActions;
    }
    
    /**
     * Replace placeholders in URL with row values.
     * 
     * Supports: :id, :column_name, {id}, {column_name}
     *
     * @param string $url
     * @param Model $row
     * @return string
     */
    protected function replacePlaceholders(string $url, Model $row): string
    {
        // Replace :id and {id} with row ID
        $url = str_replace([':id', '{id}'], (string) $row->id, $url);
        
        // Replace other placeholders like :column_name or {column_name}
        preg_match_all('/:([a-z_]+)|\{([a-z_]+)\}/i', $url, $matches);
        
        $placeholders = array_merge($matches[1], $matches[2]);
        $placeholders = array_filter($placeholders);
        
        foreach ($placeholders as $placeholder) {
            if ($placeholder === 'id') {
                continue; // Already handled above
            }
            
            $value = $row->$placeholder ?? '';
            $url = str_replace([':' . $placeholder, '{' . $placeholder . '}'], (string) $value, $url);
        }
        
        return $url;
    }
    
    /**
     * Get row CSS class based on conditions.
     *
     * @param Model $row
     * @param TableBuilder $table
     * @return string|null
     */
    protected function getRowClass(Model $row, TableBuilder $table): ?string
    {
        $conditions = $table->getRowConditions();
        
        if (empty($conditions)) {
            return null;
        }
        
        $classes = [];
        
        foreach ($conditions as $condition) {
            try {
                $check = $condition['condition'] ?? null;
                $class = $condition['class'] ?? null;
                
                if ($check && $class && is_callable($check) && $check($row)) {
                    $classes[] = $class;
                }
            } catch (\Exception $e) {
                Log::warning('Row condition check failed', [
                    'row_id' => $row->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return !empty($classes) ? implode(' ', $classes) : null;
    }
    
    /**
     * Apply permission-based filtering.
     *
     * @param Builder $query
     * @param string $permission
     * @return void
     */
    protected function applyPermissionFilter(Builder $query, string $permission): void
    {
        // Permission filtering will be implemented when RBAC integration is added
        // For now, this is a placeholder
    }
    
    /**
     * Validate column name to prevent SQL injection.
     *
     * @param string $column
     * @return bool
     */
    protected function isValidColumnName(string $column): bool
    {
        // Allow alphanumeric, underscore, and dot (for relations)
        return preg_match('/^[a-zA-Z0-9_.]+$/', $column) === 1;
    }
}

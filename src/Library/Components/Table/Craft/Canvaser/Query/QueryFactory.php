<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\QueryFactoryInterface;

class QueryFactory implements QueryFactoryInterface
{
    /**
     * Build complete query with all processing steps
     *
     * Extracted from Datatables.php lines 163-300+
     * Preserves exact legacy behavior including quirks
     */
    public function buildQuery($model_data, $data, string $table_name, $filters = null, ?array $order_by = null): array
    {
        // CRITICAL: Validate model_data is not null
        if (empty($model_data)) {
            \Log::error('QueryFactory::buildQuery - Model data is null', [
                'table_name' => $table_name,
                'filters' => $filters,
                'order_by' => $order_by
            ]);
            throw new \Exception("Model data is null or empty. Cannot build query without valid model.");
        }
        
        $joinFields = null;

        // Step 1: Apply joins if foreign keys exist
        if (!empty($table_name) && ! empty($data->datatables->columns[$table_name]['foreign_keys'])) {
            // Get lists configuration for column filtering
            $lists_config = $data->datatables->columns[$table_name]['lists'] ?? [];
            
            $joinResult = $this->applyJoins(
                $model_data,
                $data->datatables->columns[$table_name]['foreign_keys'],
                $table_name,
                $lists_config
            );
            $model_data = $joinResult['model'];
            $joinFields = $joinResult['joinFields'];
        }

        // Step 2: Apply where conditions
        $model_condition = null;
        if (!empty($table_name) && ! empty($data->datatables->conditions[$table_name]['where'])) {
            $model_condition = $this->applyWhereConditions(
                $model_data,
                $data->datatables->conditions[$table_name]['where']
            );
        }

        // Step 3: Determine base model for filters
        if (! empty($model_condition)) {
            $model_filters = $model_condition;
        } else {
            $model_filters = $model_data;
        }
        
        // CRITICAL: Validate model_filters is not null before using
        if (empty($model_filters)) {
            \Log::error('QueryFactory::buildQuery - Model filters is null', [
                'table_name' => $table_name,
                'model_data_is_null' => is_null($model_data),
                'model_condition_is_null' => is_null($model_condition)
            ]);
            throw new \Exception("Model filters is null. Cannot apply filters without valid model.");
        }

        // Step 4: Apply filters and get totals
        $firstField = 'id'; // Default fallback
        if (!empty($table_name) && isset($data->datatables->columns[$table_name]['lists']) && !empty($data->datatables->columns[$table_name]['lists'])) {
            $firstField = $data->datatables->columns[$table_name]['lists'][0];
        }
        // Debug: Log model_filters before applyFilters
        \Log::info('QueryFactory::buildQuery - Before applyFilters', [
            'model_filters_is_null' => is_null($model_filters),
            'model_filters_class' => is_object($model_filters) ? get_class($model_filters) : gettype($model_filters),
            'table_name' => $table_name,
            'firstField' => $firstField
        ]);
        
        $filterResult = $this->applyFilters($model_filters, $filters, $table_name, $firstField);
        $model = $filterResult['model'];
        $limitTotal = $filterResult['limitTotal'];

        // Step 5: Setup pagination limits
        $limit = [
            'start' => 0,
            'length' => 10,
            'total' => intval($limitTotal),
        ];

        if (! empty(request()->get('start'))) {
            $limit['start'] = request()->get('start');
        }
        if (! empty(request()->get('length'))) {
            $limit['length'] = request()->get('length');
        }

        // Step 6: Skip ordering here - will be handled by Datatables.php to avoid double order by
        // $model = $this->applyOrdering($model, $order_by, $table_name);

        // Step 7: Apply pagination
        $model = $this->applyPagination($model, $limit['start'], $limit['length']);

        return [
            'model' => $model,
            'limit' => $limit,
            'joinFields' => $joinFields,
            'order_by' => $order_by,
        ];
    }

    /**
     * Apply joins based on foreign keys configuration
     * UPDATED: Now respects lists configuration to select only specified columns
     *
     * Extracted from Datatables.php lines 163-183
     * Enhanced to support column filtering based on lists configuration
     */
    public function applyJoins($model_data, array $foreign_keys, string $table_name, array $lists_config = []): array
    {
        $fieldsets = [];
        
        // UPDATED: Use lists configuration to determine which columns to select
        if (!empty($lists_config)) {
            // Build select fields based on lists configuration
            $joinFields = [];
            
            // Always include ID for internal operations (but won't be displayed if not in lists)
            $joinFields[] = "{$table_name}.id";
            
            // Add columns from lists configuration
            foreach ($lists_config as $column) {
                if ($column !== 'id') { // Avoid duplicate ID
                    // Check if column exists in main table or joined tables
                    if (in_array($column, ['username', 'email', 'photo', 'address', 'phone', 'expire_date', 'active'])) {
                        // Main table columns
                        $joinFields[] = "{$table_name}.{$column}";
                    }
                    // group_info will be handled by joins below
                }
            }
            
            \Log::info('QueryFactory::applyJoins - Using lists configuration', [
                'table_name' => $table_name,
                'lists_config' => $lists_config,
                'selected_fields' => $joinFields
            ]);
        } else {
            // Fallback to original behavior (select all)
            $joinFields = ["{$table_name}.*"];
            
            \Log::info('QueryFactory::applyJoins - Using fallback (select all)', [
                'table_name' => $table_name
            ]);
        }

        foreach ($foreign_keys as $fkey1 => $fkey2) {
            $ftables = explode('.', $fkey1);
            $model_data = $model_data->leftJoin($ftables[0], $fkey1, '=', $fkey2);
            $fieldsets[$ftables[0]] = canvastack_get_table_columns($ftables[0]);
        }

        // UPDATED: Only add joined table columns that are in lists configuration
        if (!empty($lists_config)) {
            foreach ($fieldsets as $fstname => $fieldRows) {
                foreach ($fieldRows as $fieldset) {
                    // Only include columns that are in lists configuration
                    $shouldInclude = false;
                    
                    // Special handling for group_info (maps to group_name, avoid duplicates)
                    if (in_array('group_info', $lists_config)) {
                        if ($fieldset === 'group_name') {
                            // Use group_name as group_info alias
                            $joinFields[] = "{$fstname}.{$fieldset} as group_info";
                            $shouldInclude = true;
                        } else if ($fieldset === 'group_info') {
                            // Skip the actual group_info column to avoid ambiguity with alias
                            // The alias group_info (from group_name) will be used instead
                            $shouldInclude = false;
                        } else if ($fieldset === 'group_alias') {
                            // Include group_alias as separate column
                            $shouldInclude = true;
                        }
                    }
                    
                    // Include other columns if they're in lists (but not group_name if already handled above)
                    if (in_array($fieldset, $lists_config) && $fieldset !== 'group_info') {
                        $shouldInclude = true;
                    }
                    
                    if ($shouldInclude) {
                        if ('id' === $fieldset) {
                            $joinFields[] = "{$fstname}.{$fieldset} as {$fstname}_{$fieldset}";
                        } else if ($fieldset !== 'group_name' || !in_array('group_info', $lists_config)) {
                            // Don't duplicate group_name if we already added it as group_info
                            $joinFields[] = "{$fstname}.{$fieldset}";
                        }
                    }
                }
            }
        } else {
            // Original behavior - add all joined columns
            foreach ($fieldsets as $fstname => $fieldRows) {
                foreach ($fieldRows as $fieldset) {
                    if ('id' === $fieldset) {
                        $joinFields[] = "{$fstname}.{$fieldset} as {$fstname}_{$fieldset}";
                    } else {
                        $joinFields[] = "{$fstname}.{$fieldset}";
                    }
                }
            }
        }

        $model_data = $model_data->select($joinFields);

        return [
            'model' => $model_data,
            'joinFields' => $joinFields,
        ];
    }

    /**
     * Apply where conditions from datatables configuration
     *
     * Extracted from Datatables.php lines 190-212
     * Preserves exact condition processing logic
     */
    public function applyWhereConditions($model_data, array $conditions)
    {
        $model_condition = [];
        $where_conditions = [];

        foreach ($conditions as $conditional_where) {
            if (! is_array($conditional_where['value'])) {
                $where_conditions['o'][] = [
                    $conditional_where['field_name'],
                    $conditional_where['operator'],
                    $conditional_where['value'],
                ];
            } else {
                $where_conditions['i'][$conditional_where['field_name']] = $conditional_where['value'];
            }
        }

        if (! empty($where_conditions['o'])) {
            try {
                $model_condition = $model_data->where($where_conditions['o']);
            } catch (\Exception $e) {
                \Log::error('QueryFactory::applyWhereConditions - Error applying where conditions', [
                    'conditions' => $where_conditions['o'],
                    'error' => $e->getMessage()
                ]);
                $model_condition = $model_data;
            }
        }
        if (empty($model_condition)) {
            $model_condition = $model_data;
        }

        if (! empty($where_conditions['i'])) {
            foreach ($where_conditions['i'] as $if => $iv) {
                try {
                    $model_condition = $model_condition->whereIn($if, $iv);
                } catch (\Exception $e) {
                    \Log::error('QueryFactory::applyWhereConditions - Error applying whereIn conditions', [
                        'field' => $if,
                        'values' => $iv,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with existing model_condition
                }
            }
        }

        // Final validation
        if (empty($model_condition)) {
            \Log::error('QueryFactory::applyWhereConditions - Model condition is null after processing');
            $model_condition = $model_data;
        }

        return $model_condition;
    }

    /**
     * Apply request filters to query
     *
     * Extracted from Datatables.php lines 214-274
     * Preserves exact filter processing and reserved field logic
     */
    public function applyFilters($model_filters, $filters, string $table_name, string $firstField): array
    {
        // CRITICAL: Validate model_filters is not null
        if (empty($model_filters)) {
            \Log::error('QueryFactory::applyFilters - Model filters is null', [
                'table_name' => $table_name,
                'firstField' => $firstField,
                'filters' => $filters
            ]);
            throw new \Exception("Model filters is null in applyFilters. Cannot proceed.");
        }
        
        $fstrings = [];
        $_ajax_url = 'renderDataTables';

        if (! empty($filters) && is_array($filters)) {
            foreach ($filters as $name => $value) {
                if ('filters' !== $name && '' !== $value) {
                    if (
                        $name !== $_ajax_url &&
                        $name !== 'draw' &&
                        $name !== 'columns' &&
                        $name !== 'order' &&
                        $name !== 'start' &&
                        $name !== 'length' &&
                        $name !== 'search' &&
                        $name !== 'difta' &&
                        $name !== '_token' &&
                        $name !== '_'
                    ) {
                        if (! is_array($value)) {
                            $fstrings[] = [$name => urldecode((string) $value)];
                        } else {
                            foreach ($value as $val) {
                                $fstrings[] = [$name => urldecode((string) $val)];
                            }
                        }
                    }
                }
            }
        }

        if (! empty($fstrings)) {
            $filters = [];
            foreach ($fstrings as $fdata) {
                foreach ($fdata as $fkey => $fvalue) {
                    $filters[$fkey][] = $fvalue;
                }
            }

            if (! empty($filters)) {
                $fconds = [];
                foreach ($filters as $fieldname => $rowdata) {
                    foreach ($rowdata as $dataRow) {
                        $fconds[$fieldname] = $dataRow;
                    }
                }

                // CRITICAL: Validate model_filters before where
                if (empty($model_filters)) {
                    \Log::error('QueryFactory::applyFilters - Model filters is null at line 261', [
                        'table_name' => $table_name,
                        'fconds' => $fconds
                    ]);
                    throw new \Exception("Model filters is null at line 261. This should not happen after validation.");
                }
                $model = $model_filters->where($fconds);
            }
            $limitTotal = count($model->get());
        } else {
            // CRITICAL: Double-check model_filters before using
            if (empty($model_filters)) {
                \Log::error('QueryFactory::applyFilters - Model filters is null at line 265', [
                    'table_name' => $table_name,
                    'firstField' => $firstField
                ]);
                throw new \Exception("Model filters is null at line 265. This should not happen after validation.");
            }
            $model = $model_filters->where("{$table_name}.{$firstField}", '!=', null);
            $limitTotal = count($model_filters->get());
        }

        return [
            'model' => $model,
            'limitTotal' => $limitTotal,
        ];
    }

    /**
     * Apply ordering to query
     *
     * Handles both DataTables processed_order and default column ordering
     */
    public function applyOrdering($model, ?array $order_by = null, string $table_name = '')
    {
        if (empty($order_by)) {
            \Log::info('QueryFactory::applyOrdering - No ordering specified, using default', [
                'table_name' => $table_name
            ]);
            return $model;
        }

        // Validate order_by structure
        if (!isset($order_by['column']) || !isset($order_by['order'])) {
            \Log::warning('QueryFactory::applyOrdering - Invalid order_by structure', [
                'order_by' => $order_by,
                'table_name' => $table_name
            ]);
            return $model;
        }

        $column = $order_by['column'];
        $direction = strtolower($order_by['order']);

        // Validate direction
        if (!in_array($direction, ['asc', 'desc'])) {
            \Log::warning('QueryFactory::applyOrdering - Invalid direction, defaulting to asc', [
                'direction' => $direction,
                'column' => $column,
                'table_name' => $table_name
            ]);
            $direction = 'asc';
        }

        // Validate column name (basic security check)
        if (empty($column) || !is_string($column) || trim($column) === '') {
            \Log::warning('QueryFactory::applyOrdering - Invalid or empty column name', [
                'column' => $column,
                'column_type' => gettype($column),
                'column_length' => is_string($column) ? strlen($column) : 'N/A',
                'table_name' => $table_name,
                'order_by_full' => $order_by
            ]);
            return $model;
        }

        // Apply ordering with smart column resolution for ambiguous columns
        try {
            // CRITICAL: Handle ambiguous columns by prefixing with main table name
            $orderColumn = $column;
            
            // Check if column needs table prefix to avoid ambiguity
            if (!empty($table_name) && !str_contains($column, '.')) {
                // List of columns that commonly exist in multiple tables and cause ambiguity
                $ambiguousColumns = ['id', 'active', 'status', 'created_at', 'updated_at', 'deleted_at', 'name'];
                
                if (in_array($column, $ambiguousColumns)) {
                    $orderColumn = "{$table_name}.{$column}";
                    
                    \Log::info('QueryFactory::applyOrdering - Resolved ambiguous column', [
                        'original_column' => $column,
                        'resolved_column' => $orderColumn,
                        'table_name' => $table_name
                    ]);
                }
            }
            
            $model = $model->orderBy($orderColumn, $direction);
            
            \Log::info('QueryFactory::applyOrdering - Applied ordering', [
                'column' => $orderColumn,
                'direction' => $direction,
                'table_name' => $table_name
            ]);
            
        } catch (\Exception $e) {
            \Log::error('QueryFactory::applyOrdering - Failed to apply ordering', [
                'column' => $column,
                'direction' => $direction,
                'table_name' => $table_name,
                'error' => $e->getMessage()
            ]);
            // Return model without ordering rather than failing
        }

        return $model;
    }

    /**
     * Apply pagination to query
     *
     * Extracted from Datatables.php line 281
     * Preserves exact skip/take behavior
     */
    public function applyPagination($model, ?int $start = null, ?int $length = null)
    {
        $start = $start ?? 0;
        $length = $length ?? 10;

        return $model->skip($start)->take($length);
    }

    /**
     * Calculate total records
     *
     * Extracted from Datatables.php lines 270, 273
     * Preserves exact count calculation logic
     */
    public function calculateTotals($model, $model_filters): int
    {
        // This method preserves the legacy counting logic
        // In legacy, counts are calculated within applyFilters
        // This method is kept for interface completeness
        return count($model_filters->get());
    }
}

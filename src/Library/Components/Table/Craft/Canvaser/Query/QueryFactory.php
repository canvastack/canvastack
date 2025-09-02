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
        $joinFields = null;

        // Step 1: Apply joins if foreign keys exist
        if (! empty($data->datatables->columns[$table_name]['foreign_keys'])) {
            $joinResult = $this->applyJoins(
                $model_data,
                $data->datatables->columns[$table_name]['foreign_keys'],
                $table_name
            );
            $model_data = $joinResult['model'];
            $joinFields = $joinResult['joinFields'];
        }

        // Step 2: Apply where conditions
        $model_condition = null;
        if (! empty($data->datatables->conditions[$table_name]['where'])) {
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

        // Step 4: Apply filters and get totals
        $firstField = $data->datatables->columns[$table_name]['lists'][0];
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

        // Step 6: Apply pagination
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
     *
     * Extracted from Datatables.php lines 163-183
     * Preserves exact field aliasing logic
     */
    public function applyJoins($model_data, array $foreign_keys, string $table_name): array
    {
        $fieldsets = [];
        $joinFields = ["{$table_name}.*"];

        foreach ($foreign_keys as $fkey1 => $fkey2) {
            $ftables = explode('.', $fkey1);
            $model_data = $model_data->leftJoin($ftables[0], $fkey1, '=', $fkey2);
            $fieldsets[$ftables[0]] = canvastack_get_table_columns($ftables[0]);
        }

        foreach ($fieldsets as $fstname => $fieldRows) {
            foreach ($fieldRows as $fieldset) {
                if ('id' === $fieldset) {
                    $joinFields[] = "{$fstname}.{$fieldset} as {$fstname}_{$fieldset}";
                } else {
                    $joinFields[] = "{$fstname}.{$fieldset}";
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
            $model_condition = $model_data->where($where_conditions['o']);
        }
        if (empty($model_condition)) {
            $model_condition = $model_data;
        }

        if (! empty($where_conditions['i'])) {
            foreach ($where_conditions['i'] as $if => $iv) {
                $model_condition = $model_condition->whereIn($if, $iv);
            }
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
        $fstrings = [];
        $_ajax_url = 'renderDataTables';

        if (! empty($filters) && true == $filters) {
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

                $model = $model_filters->where($fconds);
            }
            $limitTotal = count($model->get());
        } else {
            $model = $model_filters->where("{$table_name}.{$firstField}", '!=', null);
            $limitTotal = count($model_filters->get());
        }

        return [
            'model' => $model,
            'limitTotal' => $limitTotal,
        ];
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

<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * FilterSearchTrait
 *
 * Searching, filtering, on-load limits, model filter payload, and column conditions.
 */
trait FilterSearchTrait
{
    public $search_columns = false;

    protected $filter_model = [];

    public $conditions = [];

    public function searchable($columns = null)
    {
        $this->variables['searchable_columns'] = [];
        $this->variables['searchable_columns'] = $this->checkColumnSet($columns);
        if (empty($columns)) {
            if (false === $columns) {
                $filter_columns = false;
            } else {
                $filter_columns = $this->all_columns;
            }
        } else {
            $filter_columns = $columns;
        }
        $this->search_columns = $filter_columns;
    }

    public function filterGroups($column, $type, $relate = false)
    {
        $filters = [];
        $filters['column'] = $column;
        $filters['type'] = $type;
        $filters['relate'] = $relate;
        $this->variables['filter_groups'][] = $filters;
    }

    public function displayRowsLimitOnLoad($limit = 10)
    {
        if (is_string($limit)) {
            if (in_array(strtolower($limit), ['*', 'all'])) {
                $this->variables['on_load']['display_limit_rows'] = '*';
            }
        } else {
            $this->variables['on_load']['display_limit_rows'] = intval($limit);
        }
    }

    public function clearOnLoad()
    {
        unset($this->variables['on_load']['display_limit_rows']);
    }

    public function filterModel(array $data = [])
    {
        $this->filter_model = $data;
    }

    public function where($field_name, $logic_operator = false, $value = false)
    {
        $this->conditions['where'] = [];
        if (is_array($field_name)) {
            foreach ($field_name as $fieldname => $fieldvalue) {
                $this->conditions['where'][] = [
                    'field_name' => $fieldname,
                    'operator' => '=',
                    'value' => $fieldvalue,
                ];
            }
        } else {
            $this->conditions['where'][] = [
                'field_name' => $field_name,
                'operator' => $logic_operator,
                'value' => $value,
            ];
        }
    }

    public function filterConditions($filters = [])
    {
        return $this->where($filters);
    }

    public function columnCondition(string $field_name, string $target, string $logic_operator = null, string $value = null, string $rule, $action)
    {
        $this->conditions['columns'][] = [
            'field_name' => $field_name,
            'field_target' => $target,
            'logic_operator' => $logic_operator,
            'value' => $value,
            'rule' => $rule,
            'action' => $action,
        ];
    }

    /**
     * Apply and normalize conditions for a given table (legacy-compatible).
     * - Mirrors legacy behavior of moving formula/columns under per-table keys
     * - Normalizes where via WhereConditionsNormalizer
     */
    protected function applyConditionsForTable(string $table_name): void
    {
        if (empty($this->conditions)) {
            return;
        }

        // Keep raw snapshot for params consumers if available
        if (property_exists($this, 'params')) {
            $this->params[$table_name]['conditions'] = $this->conditions;
        }

        $cond = $this->conditions;

        // formula -> both $this->formula[$table] and $this->conditions[$table]['formula']
        if (! empty($cond['formula'])) {
            if (property_exists($this, 'formula')) {
                $this->formula[$table_name] = $cond['formula'];
            }
            unset($cond['formula']);
            $cond[$table_name]['formula'] = $this->formula[$table_name] ?? [];
        }

        // where -> normalize structure
        if (! empty($cond['where']) && is_array($cond['where'])) {
            $normalized = $this->normalizeWhereConditions($cond['where']);
            $cond[$table_name]['where'] = $normalized;
        }

        // columns -> move under table key
        if (! empty($cond['columns'])) {
            $columnCond = $cond['columns'];
            unset($cond['columns']);
            $cond[$table_name]['columns'] = $columnCond;
        }

        $this->conditions = $cond;
    }

    /**
     * Thin wrapper for legacy call-sites; delegates to the new normalizer.
     */
    protected function normalizeWhereConditions(array $raw): array
    {
        return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\WhereConditionsNormalizer::normalize($raw);
    }
}

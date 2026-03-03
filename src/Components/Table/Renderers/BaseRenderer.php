<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Renderers;

/**
 * BaseRenderer - Abstract base class for table renderers.
 *
 * Contains common functionality shared between AdminRenderer and PublicRenderer.
 */
abstract class BaseRenderer implements RendererInterface
{
    protected array $config;

    protected array $hiddenColumns = [];

    protected array $columnWidths = [];

    protected array $columnAlignments = [];

    protected array $columnColors = [];

    protected array $mergedColumns = [];

    protected ?int $fixedLeft = null;

    protected ?int $fixedRight = null;

    protected ?string $tableWidth = null;

    protected array $columnConditions = [];

    protected array $formulas = [];

    protected array $formats = [];

    protected array $relations = [];

    /**
     * Initialize renderer with configuration.
     *
     * @param array $config Configuration array
     */
    protected function initializeConfig(array $config): void
    {
        $this->hiddenColumns = $config['hidden_columns'] ?? [];
        $this->columnWidths = $config['column_widths'] ?? [];
        $this->columnAlignments = $config['column_alignments'] ?? [];
        $this->columnColors = $config['column_colors'] ?? [];
        $this->mergedColumns = $config['merged_columns'] ?? [];
        $this->fixedLeft = $config['fixed_left'] ?? null;
        $this->fixedRight = $config['fixed_right'] ?? null;
        $this->tableWidth = $config['table_width'] ?? null;
        $this->columnConditions = $config['column_conditions'] ?? [];
        $this->formulas = $config['formulas'] ?? [];
        $this->formats = $config['formats'] ?? [];
        $this->relations = $config['relations'] ?? [];
    }

    /**
     * Filter out hidden columns.
     *
     * @param array $columns The columns array
     * @return array Filtered columns
     */
    protected function filterHiddenColumns(array $columns): array
    {
        if (empty($this->hiddenColumns)) {
            return $columns;
        }

        return array_filter($columns, function ($column) {
            $columnName = is_array($column) ? $column['name'] : $column;

            return !in_array($columnName, $this->hiddenColumns);
        });
    }

    /**
     * Get value for a cell.
     *
     * @param mixed $row The row data
     * @param mixed $column The column definition
     * @param string $columnName The column name
     * @param bool $isFormula Whether this is a formula column
     * @return mixed The cell value
     */
    protected function getCellValue(mixed $row, mixed $column, string $columnName, bool $isFormula): mixed
    {
        if ($isFormula) {
            return $this->getFormulaValue($columnName, $row);
        }

        return is_array($row) ? ($row[$columnName] ?? '') : ($row->{$columnName} ?? '');
    }

    /**
     * Apply all formatting to cell value.
     *
     * @param mixed $value The cell value
     * @param string $columnName The column name
     * @param mixed $row The row data
     * @param array $cellAttributes Cell attributes (modified by reference)
     * @return string Formatted value
     */
    protected function applyAllFormatting(mixed $value, string $columnName, mixed $row, array &$cellAttributes): string
    {
        // Apply formatting (number, currency, percentage, date)
        $formattedValue = $this->applyFormatting($value, $columnName);

        // Apply conditional formatting (prefix, suffix, replace, css)
        $formattedValue = $this->applyColumnConditions(
            $columnName,
            $formattedValue,
            $row,
            $cellAttributes['class'],
            $cellAttributes['style']
        );

        // Final HTML escaping and formatting
        return $this->formatValue($formattedValue);
    }

    /**
     * Build final cell HTML.
     *
     * @param string $formattedValue The formatted value
     * @param array $cellAttributes Cell attributes
     * @return string HTML for cell
     */
    protected function buildCellHtml(string $formattedValue, array $cellAttributes): string
    {
        $styleAttr = !empty($cellAttributes['style'])
            ? ' style="' . htmlspecialchars(implode('; ', $cellAttributes['style'])) . '"'
            : '';

        return '<td class="' . $cellAttributes['class'] . '"' . $styleAttr . '>' . $formattedValue . '</td>';
    }

    /**
     * Format cell value.
     *
     * @param mixed $value The value to format
     * @return string Formatted HTML
     */
    abstract protected function formatValue(mixed $value): string;

    /**
     * Apply column conditions to value.
     *
     * @param string $columnName The column name
     * @param mixed $value The current value
     * @param mixed $row The row data
     * @param string $tdClass Cell class (modified by reference)
     * @param array $tdStyle Cell styles (modified by reference)
     * @return string Modified value
     */
    protected function applyColumnConditions(string $columnName, mixed $value, mixed $row, string &$tdClass, array &$tdStyle): string
    {
        if (empty($this->columnConditions)) {
            return (string) $value;
        }

        foreach ($this->columnConditions as $condition) {
            if ($condition['field_name'] !== $columnName) {
                continue;
            }

            // Get the value to compare
            $compareValue = is_array($row) ? ($row[$columnName] ?? null) : ($row->{$columnName} ?? null);

            // Evaluate condition
            if (!$this->evaluateCondition($compareValue, $condition['operator'], $condition['value'])) {
                continue;
            }

            // Apply rule based on type
            $value = $this->applyConditionRule(
                $condition['rule'],
                $value,
                $condition['action'],
                $condition['target'] ?? 'cell',
                $tdStyle
            );
        }

        return (string) $value;
    }

    /**
     * Apply a single condition rule to a value.
     *
     * @param string $rule The rule type (css style, prefix, suffix, etc.)
     * @param mixed $value The current value
     * @param mixed $action The action to apply
     * @param string $target The target (cell or row)
     * @param array $tdStyle Cell styles array (modified by reference)
     * @return mixed The modified value
     */
    protected function applyConditionRule(string $rule, mixed $value, mixed $action, string $target, array &$tdStyle): mixed
    {
        switch ($rule) {
            case 'css style':
                if ($target === 'cell') {
                    $tdStyle[] = htmlspecialchars($action);
                }

                return $value;

            case 'prefix':
                return $action . $value;

            case 'suffix':
                return $value . $action;

            case 'prefix&suffix':
                if (is_array($action) && count($action) >= 2) {
                    return $action[0] . $value . $action[1];
                }

                return $value;

            case 'replace':
                return $action;

            default:
                return $value;
        }
    }

    /**
     * Evaluate condition.
     *
     * @param mixed $value The value to compare
     * @param string|null $operator The comparison operator
     * @param mixed $compareValue The value to compare against
     * @return bool Whether condition is met
     */
    protected function evaluateCondition(mixed $value, ?string $operator, mixed $compareValue): bool
    {
        if ($operator === null) {
            return true;
        }

        switch ($operator) {
            case '==':
                return $value == $compareValue;
            case '!=':
                return $value != $compareValue;
            case '===':
                return $value === $compareValue;
            case '!==':
                return $value !== $compareValue;
            case '>':
                return $value > $compareValue;
            case '<':
                return $value < $compareValue;
            case '>=':
                return $value >= $compareValue;
            case '<=':
                return $value <= $compareValue;
            default:
                return false;
        }
    }

    /**
     * Check if row matches any row-level conditions.
     *
     * @param mixed $row The row data
     * @return string Style attribute string
     */
    protected function getRowConditionStyles(mixed $row): string
    {
        $styles = [];

        foreach ($this->columnConditions as $condition) {
            if ($condition['target'] !== 'row') {
                continue;
            }

            $columnName = $condition['field_name'];
            $value = is_array($row) ? ($row[$columnName] ?? null) : ($row->{$columnName} ?? null);

            if ($this->evaluateCondition($value, $condition['operator'], $condition['value'])) {
                if ($condition['rule'] === 'css style') {
                    $styles[] = htmlspecialchars($condition['action']);
                }
            }
        }

        return !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
    }

    /**
     * Calculate formula value for a row.
     *
     * @param array $formula The formula configuration
     * @param mixed $row The row data
     * @return float|int|string|null The calculated value
     */
    protected function calculateFormula(array $formula, mixed $row): float|int|string|null
    {
        $fieldValues = [];

        foreach ($formula['fields'] as $field) {
            $value = is_array($row) ? ($row[$field] ?? 0) : ($row->{$field} ?? 0);
            $fieldValues[$field] = is_numeric($value) ? (float) $value : 0;
        }

        $logic = $formula['logic'];
        foreach ($fieldValues as $field => $value) {
            $logic = str_replace($field, (string) $value, $logic);
        }

        try {
            $result = $this->evaluateExpression($logic);

            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Safely evaluate mathematical expression.
     *
     * @param string $expression The expression to evaluate
     * @return float|int The result
     */
    protected function evaluateExpression(string $expression): float|int
    {
        $cleanedExpression = preg_replace('/\s+/', '', $expression);

        if ($cleanedExpression === null) {
            return 0;
        }

        if (!preg_match('/^[0-9+\-*\/%().]+$/', $cleanedExpression)) {
            return 0;
        }

        try {
            $result = @eval('return ' . $cleanedExpression . ';');

            return is_numeric($result) ? (is_float($result) ? $result : (int) $result) : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Apply formatting to a value.
     *
     * @param mixed $value The value to format
     * @param string $columnName The column name
     * @return string|float|int Formatted value
     */
    protected function applyFormatting(mixed $value, string $columnName): string|float|int
    {
        // Handle null values (e.g., from division by zero in formulas)
        if ($value === null) {
            return __('components.table.na');
        }

        foreach ($this->formats as $format) {
            if (!in_array($columnName, $format['fields'])) {
                continue;
            }

            $decimals = $format['decimals'] ?? 0;
            $separator = $format['separator'] ?? '.';
            $type = $format['type'] ?? 'number';

            if (!is_numeric($value)) {
                return $value;
            }

            switch ($type) {
                case 'number':
                    return number_format((float) $value, $decimals, $separator, ',');

                case 'currency':
                    return '$' . number_format((float) $value, $decimals, $separator, ',');

                case 'percentage':
                    return number_format((float) $value, $decimals, $separator, ',') . '%';

                case 'date':
                    return date('Y-m-d', (int) $value);

                default:
                    return $value;
            }
        }

        return $value;
    }

    /**
     * Add formula columns to the columns array.
     *
     * @param array $columns The columns array
     * @return array Columns with formulas added
     */
    protected function addFormulaColumns(array $columns): array
    {
        if (empty($this->formulas)) {
            return $columns;
        }

        foreach ($this->formulas as $formula) {
            $formulaColumn = [
                'name' => $formula['name'],
                'label' => $formula['label'] ?? $formula['name'],
                'is_formula' => true,
            ];

            if (isset($formula['node_location']) && $formula['node_location'] !== null) {
                $position = $this->findColumnPosition($columns, $formula['node_location']);
                if ($position !== false) {
                    $offset = $formula['node_after'] ? $position + 1 : $position;
                    array_splice($columns, $offset, 0, [$formulaColumn]);
                    continue;
                }
            }

            $columns[] = $formulaColumn;
        }

        return $columns;
    }

    /**
     * Find column position by name.
     *
     * @param array $columns The columns array
     * @param string $columnName The column name to find
     * @return int|false The position or false if not found
     */
    protected function findColumnPosition(array $columns, string $columnName): int|false
    {
        foreach ($columns as $index => $column) {
            $name = is_array($column) ? $column['name'] : $column;
            if ($name === $columnName) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Get formula value for a column and row.
     *
     * @param string $columnName The column name
     * @param mixed $row The row data
     * @return mixed The formula value
     */
    protected function getFormulaValue(string $columnName, mixed $row): mixed
    {
        foreach ($this->formulas as $formula) {
            if ($formula['name'] === $columnName) {
                return $this->calculateFormula($formula, $row);
            }
        }

        return null;
    }

    /**
     * Render empty state when no data available.
     *
     * @param array $columns The table columns
     * @return string HTML for empty state
     */
    abstract protected function renderEmptyState(array $columns): string;
}

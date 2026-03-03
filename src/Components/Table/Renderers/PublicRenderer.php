<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Renderers;

/**
 * PublicRenderer - Renders tables for public frontend.
 *
 * Uses Tailwind CSS + DaisyUI styling with simplified design and dark mode support.
 */
class PublicRenderer implements RendererInterface
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

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'show_actions' => false,
            'show_pagination' => true,
            'show_search' => false,
            'striped' => false,
            'hoverable' => true,
            'compact' => false,
        ], $config);

        // Extract column configurations from config
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
     * Render complete table HTML.
     */
    public function render(array $data): string
    {
        $columns = $data['columns'] ?? [];
        $rows = $data['data'] ?? [];
        $total = $data['total'] ?? 0;
        $permissionHiddenColumns = $data['permissionHiddenColumns'] ?? [];

        // Add formula columns
        $columns = $this->addFormulaColumns($columns);

        // Filter out hidden columns
        $columns = $this->filterHiddenColumns($columns);

        // Apply table width if specified
        $tableStyle = $this->tableWidth ? ' style="width: ' . htmlspecialchars($this->tableWidth) . ';"' : '';

        $html = '<div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">';

        // Show permission indicator if columns are hidden
        if (!empty($permissionHiddenColumns)) {
            $html .= $this->renderPermissionIndicator($permissionHiddenColumns);
        }

        // Table wrapper
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full"' . $tableStyle . '>';

        // Table header
        $html .= $this->renderHeader($columns);

        // Table body
        $html .= $this->renderBody($rows, $columns);

        $html .= '</table>';
        $html .= '</div>';

        // Table footer with pagination
        if ($this->config['show_pagination']) {
            $html .= $this->renderFooter(['total' => $total]);
        }

        $html .= '</div>';

        // Add DataTables JavaScript if enabled (Requirements 30.3, 31.4)
        if ($data['isDatatable'] ?? true) {
            $html .= $this->renderDataTablesScript($data);
        }

        return $html;
    }

    /**
     * Filter out hidden columns.
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
     * Render permission indicator when columns are hidden.
     *
     * Shows a message to the user when columns are hidden due to permission rules.
     * Uses theme colors and i18n for messages.
     *
     * @param array $permissionHiddenColumns Array of hidden columns with reasons
     * @return string HTML for permission indicator
     */
    protected function renderPermissionIndicator(array $permissionHiddenColumns): string
    {
        $count = count($permissionHiddenColumns);

        // Get theme colors (with fallback for test environments)
        $bgColor = (function_exists('theme_color') ? theme_color('info-light') : null) ?? '#dbeafe';
        $textColor = (function_exists('theme_color') ? theme_color('info-dark') : null) ?? '#1e40af';
        $borderColor = (function_exists('theme_color') ? theme_color('info') : null) ?? '#3b82f6';

        // Build message using proper translation keys with fallback
        if (function_exists('trans_choice')) {
            $message = trans_choice('rbac.fine_grained.columns_hidden', $count, ['count' => $count]);
            
            // Check if translation was actually found (not just returning the key)
            if (str_starts_with($message, 'rbac.fine_grained.')) {
                // Translation not found, use fallback
                $message = $count === 1
                    ? '1 column is hidden due to permissions'
                    : "{$count} columns are hidden due to permissions";
            }
        } else {
            // Fallback for environments without trans_choice()
            $message = $count === 1
                ? '1 column is hidden due to permissions'
                : "{$count} columns are hidden due to permissions";
        }

        return '
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-start gap-3 p-3 rounded-xl" 
                 style="background: ' . htmlspecialchars($bgColor) . '; 
                        color: ' . htmlspecialchars($textColor) . '; 
                        border: 1px solid ' . htmlspecialchars($borderColor) . ';">
                <i data-lucide="eye-off" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium">' . htmlspecialchars($message) . '</p>
                </div>
            </div>
        </div>';
    }

    /**
     * Render table header.
     */
    public function renderHeader(array $columns): string
    {
        $html = '<thead class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">';
        $html .= '<tr>';

        foreach ($columns as $column) {
            $columnName = is_array($column) ? $column['name'] : $column;
            $label = is_array($column) ? ($column['label'] ?? $column['name']) : $column;

            // Build th attributes
            $thClass = 'px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300';
            $thStyle = [];

            // Apply column width
            if (isset($this->columnWidths[$columnName])) {
                $thStyle[] = 'width: ' . $this->columnWidths[$columnName] . 'px';
            }

            // Apply alignment for header
            if (isset($this->columnAlignments[$columnName]) && $this->columnAlignments[$columnName]['header']) {
                $align = $this->columnAlignments[$columnName]['align'];
                $thClass .= ' text-' . $align;
            } else {
                $thClass .= ' text-left';
            }

            // Apply background color for header
            if (isset($this->columnColors[$columnName]) && $this->columnColors[$columnName]['header']) {
                $bgColor = $this->columnColors[$columnName]['background'];
                $textColor = $this->columnColors[$columnName]['text'] ?? null;
                $thStyle[] = 'background-color: ' . $bgColor;
                if ($textColor) {
                    $thStyle[] = 'color: ' . $textColor;
                }
            }

            $styleAttr = !empty($thStyle) ? ' style="' . htmlspecialchars(implode('; ', $thStyle)) . '"' : '';

            $html .= '<th class="' . $thClass . '"' . $styleAttr . '>';
            $html .= htmlspecialchars($label);
            $html .= '</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }

    /**
     * Render table body.
     */
    public function renderBody(array $rows, array $columns): string
    {
        $hoverClass = $this->config['hoverable'] ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : '';
        $compactClass = $this->config['compact'] ? 'py-2' : 'py-3';

        $html = '<tbody class="divide-y divide-gray-200 dark:divide-gray-700">';

        if (empty($rows)) {
            $html .= $this->renderEmptyState($columns);
        } else {
            $html .= $this->renderRows($rows, $columns, $hoverClass, $compactClass);
        }

        $html .= '</tbody>';

        return $html;
    }

    /**
     * Render empty state when no data available.
     *
     * @param array $columns The table columns
     * @return string HTML for empty state
     */
    private function renderEmptyState(array $columns): string
    {
        $colSpan = count($columns);

        $html = '<tr>';
        $html .= '<td colspan="' . $colSpan . '" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">';
        $html .= '<p class="text-sm">' . __('components.table.empty_state') . '</p>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Render all data rows.
     *
     * @param array $rows The data rows
     * @param array $columns The table columns
     * @param string $hoverClass CSS class for hover effect
     * @param string $compactClass CSS class for compact mode
     * @return string HTML for all rows
     */
    private function renderRows(array $rows, array $columns, string $hoverClass, string $compactClass): string
    {
        $html = '';

        foreach ($rows as $row) {
            $html .= $this->renderSingleRow($row, $columns, $hoverClass, $compactClass);
        }

        return $html;
    }

    /**
     * Render a single data row.
     *
     * @param mixed $row The row data
     * @param array $columns The table columns
     * @param string $hoverClass CSS class for hover effect
     * @param string $compactClass CSS class for compact mode
     * @return string HTML for single row
     */
    private function renderSingleRow(mixed $row, array $columns, string $hoverClass, string $compactClass): string
    {
        $rowStyles = $this->getRowConditionStyles($row);

        $html = '<tr class="' . $hoverClass . ' transition"' . $rowStyles . '>';
        $html .= $this->renderRowCells($row, $columns, $compactClass);
        $html .= '</tr>';

        return $html;
    }

    /**
     * Render all cells in a row.
     *
     * @param mixed $row The row data
     * @param array $columns The table columns
     * @param string $compactClass CSS class for compact mode
     * @return string HTML for all cells
     */
    private function renderRowCells(mixed $row, array $columns, string $compactClass): string
    {
        $html = '';

        foreach ($columns as $column) {
            $html .= $this->renderCell($row, $column, $compactClass);
        }

        return $html;
    }

    /**
     * Render a single cell.
     *
     * @param mixed $row The row data
     * @param mixed $column The column definition
     * @param string $compactClass CSS class for compact mode
     * @return string HTML for single cell
     */
    private function renderCell(mixed $row, mixed $column, string $compactClass): string
    {
        $columnName = is_array($column) ? $column['name'] : $column;
        $isFormula = is_array($column) && ($column['is_formula'] ?? false);

        // Get cell value
        $value = $this->getCellValue($row, $column, $columnName, $isFormula);

        // Apply custom formatter if provided
        if (is_array($column) && isset($column['formatter'])) {
            $value = call_user_func($column['formatter'], $value, $row);
        }

        // Build cell attributes
        $cellAttributes = $this->buildCellAttributes($columnName, $compactClass);

        // Apply all formatting
        $formattedValue = $this->applyAllFormatting($value, $columnName, $row, $cellAttributes);

        // Build final HTML
        return $this->buildCellHtml($formattedValue, $cellAttributes);
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
    private function getCellValue(mixed $row, mixed $column, string $columnName, bool $isFormula): mixed
    {
        if ($isFormula) {
            return $this->getFormulaValue($columnName, $row);
        }

        return is_array($row) ? ($row[$columnName] ?? '') : ($row->{$columnName} ?? '');
    }

    /**
     * Build cell attributes (class and style).
     *
     * @param string $columnName The column name
     * @param string $compactClass CSS class for compact mode
     * @return array Array with 'class' and 'style' keys
     */
    private function buildCellAttributes(string $columnName, string $compactClass): array
    {
        $tdClass = 'px-4 ' . $compactClass . ' text-sm text-gray-900 dark:text-gray-100';
        $tdStyle = [];

        // Apply alignment for body
        if (isset($this->columnAlignments[$columnName]) && $this->columnAlignments[$columnName]['body']) {
            $align = $this->columnAlignments[$columnName]['align'];
            $tdClass .= ' text-' . $align;
        }

        // Apply background color for body
        if (isset($this->columnColors[$columnName]) && $this->columnColors[$columnName]['body']) {
            $bgColor = $this->columnColors[$columnName]['background'];
            $textColor = $this->columnColors[$columnName]['text'] ?? null;
            $tdStyle[] = 'background-color: ' . $bgColor;
            if ($textColor) {
                $tdStyle[] = 'color: ' . $textColor;
            }
        }

        return [
            'class' => $tdClass,
            'style' => $tdStyle,
        ];
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
    private function applyAllFormatting(mixed $value, string $columnName, mixed $row, array &$cellAttributes): string
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
    private function buildCellHtml(string $formattedValue, array $cellAttributes): string
    {
        $styleAttr = !empty($cellAttributes['style'])
            ? ' style="' . htmlspecialchars(implode('; ', $cellAttributes['style'])) . '"'
            : '';

        return '<td class="' . $cellAttributes['class'] . '"' . $styleAttr . '>' . $formattedValue . '</td>';
    }

    /**
     * Render table footer with pagination.
     */
    public function renderFooter(array $data): string
    {
        $total = $data['total'] ?? 0;

        return '
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                ' . __('components.table.total') . ': <span class="font-semibold text-gray-900 dark:text-gray-100">' . $total . '</span> ' . __('components.table.items') . '
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-50" disabled>
                    ' . __('components.pagination.previous') . '
                </button>
                <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    ' . __('components.pagination.next') . '
                </button>
            </div>
        </div>';
    }

    /**
     * Render action buttons for row (not used in public context).
     */
    public function renderActions($row, array $actions): string
    {
        return '';
    }

    /**
     * Render DataTables initialization script.
     *
     * Generates the JavaScript code to initialize DataTables with proper configuration
     * for server-side or client-side processing, AJAX settings, and CSRF protection.
     * Same implementation as AdminRenderer for consistency.
     *
     * @param  array  $config  Configuration array with DataTables settings
     * @return string The complete JavaScript code wrapped in <script> tags
     */
    protected function renderDataTablesScript(array $config): string
    {
        $tableId = $config['tableName'] ?? 'datatable';
        $serverSide = $config['serverSide'] ?? false;
        $httpMethod = $config['httpMethod'] ?? 'POST';
        $ajaxUrl = $config['ajaxUrl'] ?? '/datatable/data';
        $columns = $config['columns'] ?? [];
        
        // Get display limit from config (enhanced for task 3.1.3)
        $displayLimit = $config['displayLimit'] ?? 10;
        
        // Convert 'all' or '*' to -1 for DataTables
        $pageLength = ($displayLimit === 'all' || $displayLimit === '*') ? -1 : (int) $displayLimit;

        // Build columns configuration for DataTables
        $columnsJson = json_encode(array_map(function ($column) {
            return ['data' => $column];
        }, $columns));

        // Build AJAX configuration
        $ajaxConfig = '';
        if ($serverSide) {
            $ajaxConfig = "
        ajax: {
            url: '{$ajaxUrl}',
            type: '{$httpMethod}',";

            // Add CSRF token for POST requests
            if ($httpMethod === 'POST') {
                $csrfToken = $this->getCsrfToken();
                $ajaxConfig .= "
            headers: {
                'X-CSRF-TOKEN': '{$csrfToken}'
            },";
            }

            $ajaxConfig .= "
            data: function(d) {
                // Add custom filter data
                d.filters = window.tableFilters || {};
                return d;
            },
            error: function(xhr, error, code) {
                console.error('DataTables AJAX error:', error, code);
                alert('" . __('components.table.datatables.ajax_error') . "');
            }
        },";
        }

        // Build complete DataTables configuration
        $script = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    const table = $('#{$tableId}').DataTable({
        serverSide: " . ($serverSide ? 'true' : 'false') . ",{$ajaxConfig}
        processing: " . ($serverSide ? 'true' : 'false') . ",
        columns: {$columnsJson},
        responsive: true,
        pageLength: {$pageLength},
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            processing: '<div class=\"flex items-center justify-center gap-2\"><div class=\"animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600\"></div><span>" . __('components.table.datatables.processing') . "</span></div>',
            emptyTable: '" . __('components.table.datatables.empty_table') . "',
            zeroRecords: '" . __('components.table.datatables.zero_records') . "',
            info: '" . __('components.table.datatables.info') . "',
            infoEmpty: '" . __('components.table.datatables.info_empty') . "',
            infoFiltered: '" . __('components.table.datatables.info_filtered') . "',
            search: '" . __('components.table.datatables.search') . "',
            paginate: {
                first: '" . __('components.table.datatables.paginate.first') . "',
                last: '" . __('components.table.datatables.paginate.last') . "',
                next: '" . __('components.table.datatables.paginate.next') . "',
                previous: '" . __('components.table.datatables.paginate.previous') . "'
            }
        },
        dom: '<\"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4\"<\"flex-1\"f><\"flex items-center gap-2\"lB>>rtip',
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-sm btn-outline'
            },
            {
                extend: 'csv',
                className: 'btn btn-sm btn-outline'
            },
            {
                extend: 'excel',
                className: 'btn btn-sm btn-outline'
            },
            {
                extend: 'pdf',
                className: 'btn btn-sm btn-outline'
            },
            {
                extend: 'print',
                className: 'btn btn-sm btn-outline'
            }
        ]
    });

    // Store table instance globally for external access
    window.dataTable = table;
    
    // Listen for display-limit-changed events from DisplayLimit component (task 3.1.3)
    document.addEventListener('display-limit-changed', function(event) {
        if (event.detail && event.detail.limit !== undefined) {
            const limit = event.detail.limit;
            const pageLength = (limit === 'all' || limit === '*') ? -1 : parseInt(limit);
            table.page.len(pageLength).draw();
            console.log('DataTable page length updated to:', pageLength);
        }
    });
    
    // Expose method to update page length programmatically (task 3.1.3)
    table.updateDisplayLimit = function(limit) {
        const pageLength = (limit === 'all' || limit === '*') ? -1 : parseInt(limit);
        table.page.len(pageLength).draw();
        return table;
    };

    // Initialize Lucide icons after DataTables renders
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>";

        return $script;
    }

    /**
     * Get CSRF token for AJAX requests.
     *
     * @return string The CSRF token
     */
    protected function getCsrfToken(): string
    {
        // In testing environment, csrf_token() might return null
        // Return empty string as fallback for unit tests
        return csrf_token() ?? '';
    }

    /**
     * Format cell value.
     */
    protected function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-gray-400 dark:text-gray-600">—</span>';
        }

        if (is_bool($value)) {
            return $value
                ? '<span class="text-emerald-600 dark:text-emerald-400">✓</span>'
                : '<span class="text-gray-400 dark:text-gray-600">✗</span>';
        }

        if (is_array($value)) {
            $encoded = json_encode($value);

            return htmlspecialchars($encoded !== false ? $encoded : '[]');
        }

        return htmlspecialchars((string) $value);
    }

    /**
     * Apply column conditions to value.
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
    private function applyConditionRule(string $rule, mixed $value, mixed $action, string $target, array &$tdStyle): mixed
    {
        switch ($rule) {
            case 'css style':
                return $this->applyCssStyleRule($action, $target, $tdStyle, $value);

            case 'prefix':
                return $this->applyPrefixRule($action, $value);

            case 'suffix':
                return $this->applySuffixRule($action, $value);

            case 'prefix&suffix':
                return $this->applyPrefixSuffixRule($action, $value);

            case 'replace':
                return $this->applyReplaceRule($action);

            default:
                return $value;
        }
    }

    /**
     * Apply CSS style rule.
     *
     * @param mixed $action The CSS style string
     * @param string $target The target (cell or row)
     * @param array $tdStyle Cell styles array (modified by reference)
     * @param mixed $value The current value
     * @return mixed The value (unchanged)
     */
    private function applyCssStyleRule(mixed $action, string $target, array &$tdStyle, mixed $value): mixed
    {
        if ($target === 'cell') {
            $tdStyle[] = htmlspecialchars($action);
        }

        return $value;
    }

    /**
     * Apply prefix rule.
     *
     * @param mixed $action The prefix string
     * @param mixed $value The current value
     * @return mixed The value with prefix
     */
    private function applyPrefixRule(mixed $action, mixed $value): mixed
    {
        return $action . $value;
    }

    /**
     * Apply suffix rule.
     *
     * @param mixed $action The suffix string
     * @param mixed $value The current value
     * @return mixed The value with suffix
     */
    private function applySuffixRule(mixed $action, mixed $value): mixed
    {
        return $value . $action;
    }

    /**
     * Apply prefix and suffix rule.
     *
     * @param mixed $action Array with prefix and suffix
     * @param mixed $value The current value
     * @return mixed The value with prefix and suffix
     */
    private function applyPrefixSuffixRule(mixed $action, mixed $value): mixed
    {
        if (is_array($action) && count($action) >= 2) {
            return $action[0] . $value . $action[1];
        }

        return $value;
    }

    /**
     * Apply replace rule.
     *
     * @param mixed $action The replacement value
     * @return mixed The replacement value
     */
    private function applyReplaceRule(mixed $action): mixed
    {
        return $action;
    }

    /**
     * Evaluate condition.
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
                    if (is_numeric($value)) {
                        return date('Y-m-d', (int) $value);
                    }

                    return $value;

                default:
                    return $value;
            }
        }

        return $value;
    }

    /**
     * Add formula columns to the columns array.
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
}

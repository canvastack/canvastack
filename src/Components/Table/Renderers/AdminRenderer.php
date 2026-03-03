<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Renderers;

/**
 * AdminRenderer - Renders tables for admin panel.
 *
 * Uses Tailwind CSS + DaisyUI styling with dark mode support.
 */
class AdminRenderer implements RendererInterface
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

    protected array $actions = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'show_actions' => true,
            'show_pagination' => true,
            'show_search' => true,
            'striped' => true,
            'hoverable' => true,
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
        $actions = $data['actions'] ?? [];
        $permissionHiddenColumns = $data['permissionHiddenColumns'] ?? [];
        
        // Store actions for use in renderRow
        $this->actions = $actions;
        
        // Generate encrypted table ID for security (Requirement: Security Enhancement)
        // Format: tbl_{hash} where hash = md5(tableName + uniqid)
        $tableName = $data['tableName'] ?? 'datatable';
        $tableId = 'tbl_' . substr(md5($tableName . uniqid()), 0, 12);
        
        // Store mapping for JavaScript access if needed
        $data['tableId'] = $tableId;
        $data['tableName'] = $tableName;
        
        // Calculate active filter count from session
        $sessionKey = "datatable_filters_{$tableName}";
        $sessionFilters = session($sessionKey, []);
        $activeFilterCount = count(array_filter($sessionFilters, function($value) {
            return $value !== '' && $value !== null;
        }));
        $data['activeFilterCount'] = $activeFilterCount;
        
        // Store tableId, exportButtons, and activeFilterCount in config for use in other methods
        $this->config['tableId'] = $tableId;
        $this->config['exportButtons'] = $data['exportButtons'] ?? [];
        $this->config['activeFilterCount'] = $activeFilterCount;

        // Add formula columns
        $columns = $this->addFormulaColumns($columns);

        // Filter out hidden columns
        $columns = $this->filterHiddenColumns($columns);

        // Apply table width if specified
        $tableStyle = $this->tableWidth ? ' style="width: ' . htmlspecialchars($this->tableWidth) . ';"' : '';

        $html = '<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">';

        // Show permission indicator if columns are hidden
        if (!empty($permissionHiddenColumns)) {
            $html .= $this->renderPermissionIndicator($permissionHiddenColumns);
        }

        // Table header with search
        if ($this->config['show_search']) {
            $html .= $this->renderSearchBar();
        }

        // Table wrapper
        $html .= '<div class="overflow-x-auto">';
        
        // Add table ID for DataTables initialization
        $html .= '<table id="' . htmlspecialchars($tableId) . '" class="w-full"' . $tableStyle . '>';

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

        // Add filter modal component if filters are configured
        if (!empty($data['filters'])) {
            $html .= $this->renderFilterModal($data);
        }

        // Add DataTables JavaScript if enabled (Requirements 30.3, 49.5)
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
     * Render search bar with filter button and length menu.
     */
    protected function renderSearchBar(): string
    {
        $tableId = $this->config['tableId'] ?? 'datatable';
        $exportButtons = $this->config['exportButtons'] ?? [];
        $hasExportButtons = !empty($exportButtons);
        $activeFilterCount = $this->config['activeFilterCount'] ?? 0;
        
        return '
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between gap-4">
                <!-- Left: Length Menu -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <label class="text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars(__('canvastack::components.table.show')) . '</label>
                    <select id="' . $tableId . '_length" class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">' . htmlspecialchars(__('canvastack::components.table.all')) . '</option>
                    </select>
                    <span class="text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars(__('canvastack::components.table.entries')) . '</span>
                </div>
                
                <!-- Center: Export Buttons + Filter Button -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    ' . ($hasExportButtons ? '
                    <!-- Export Buttons Container -->
                    <div id="' . $tableId . '_export_buttons" class="flex items-center gap-2"></div>
                    ' : '') . '
                    
                    <!-- Filter Button with Badge -->
                    <button 
                        id="' . $tableId . '_filter_btn"
                        class="px-4 py-2 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25 flex items-center gap-2 relative">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        <span>' . htmlspecialchars(__('canvastack::components.table.filter')) . '</span>
                        ' . ($activeFilterCount > 0 ? '
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg">' . $activeFilterCount . '</span>
                        ' : '') . '
                    </button>
                </div>
                
                <!-- Right: Search Input -->
                <div class="relative flex items-center gap-2 flex-shrink-0" style="width: 300px;">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    <input 
                        type="text" 
                        id="' . $tableId . '_search"
                        placeholder="' . htmlspecialchars(__('canvastack::components.table.search_placeholder')) . '"
                        class="w-full pl-10 pr-12 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    >
                    <!-- Search Submit Button (appears when typing) -->
                    <button 
                        id="' . $tableId . '_search_btn"
                        class="absolute right-2 p-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition opacity-0 pointer-events-none flex items-center justify-center"
                        style="transition: opacity 0.2s; width: 28px; height: 28px;"
                        title="Search">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>';
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
        $html = '<thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">';
        $html .= '<tr>';

        foreach ($columns as $column) {
            $columnName = is_array($column) ? $column['name'] : $column;
            $label = is_array($column) ? ($column['label'] ?? $column['name']) : $column;
            $sortable = is_array($column) ? ($column['sortable'] ?? true) : true;

            // Build th attributes
            $thClass = 'px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider';
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

            // Apply fixed column positioning
            if ($this->fixedLeft !== null || $this->fixedRight !== null) {
                $thClass .= ' sticky';
                // Fixed positioning logic would be handled by JavaScript/CSS
            }

            $styleAttr = !empty($thStyle) ? ' style="' . htmlspecialchars(implode('; ', $thStyle)) . '"' : '';

            $html .= '<th class="' . $thClass . '"' . $styleAttr . '>';

            if ($sortable) {
                $html .= '<div class="flex items-center gap-2 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400">';
                $html .= htmlspecialchars($label);
                $html .= '<i data-lucide="chevron-down" class="w-3 h-3"></i>';
                $html .= '</div>';
            } else {
                $html .= htmlspecialchars($label);
            }

            $html .= '</th>';
        }

        // Actions column
        if ($this->config['show_actions']) {
            $html .= '<th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">' . __('canvastack::components.table.actions') . '</th>';
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
        $stripedClass = $this->config['striped'] ? 'even:bg-gray-50 dark:even:bg-gray-800/50' : '';
        $hoverClass = $this->config['hoverable'] ? 'hover:bg-gray-100 dark:hover:bg-gray-800' : '';

        $html = '<tbody class="divide-y divide-gray-200 dark:divide-gray-700">';

        if (empty($rows)) {
            $html .= $this->renderEmptyState($columns);
        } else {
            $html .= $this->renderRows($rows, $columns, $stripedClass, $hoverClass);
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
        $colSpan = count($columns) + ($this->config['show_actions'] ? 1 : 0);

        $html = '<tr>';
        $html .= '<td colspan="' . $colSpan . '" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">';
        $html .= '<i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>';
        $html .= '<p class="text-sm">' . __('canvastack::components.table.empty_state') . '</p>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Render all data rows.
     *
     * @param array $rows The data rows
     * @param array $columns The table columns
     * @param string $stripedClass CSS class for striped rows
     * @param string $hoverClass CSS class for hover effect
     * @return string HTML for all rows
     */
    private function renderRows(array $rows, array $columns, string $stripedClass, string $hoverClass): string
    {
        $html = '';

        foreach ($rows as $row) {
            $html .= $this->renderSingleRow($row, $columns, $stripedClass, $hoverClass);
        }

        return $html;
    }

    /**
     * Render a single data row.
     *
     * @param mixed $row The row data
     * @param array $columns The table columns
     * @param string $stripedClass CSS class for striped rows
     * @param string $hoverClass CSS class for hover effect
     * @return string HTML for single row
     */
    private function renderSingleRow(mixed $row, array $columns, string $stripedClass, string $hoverClass): string
    {
        $rowStyles = $this->getRowConditionStyles($row);

        $html = '<tr class="' . $stripedClass . ' ' . $hoverClass . ' transition"' . $rowStyles . '>';
        $html .= $this->renderRowCells($row, $columns);

        if ($this->config['show_actions']) {
            $html .= '<td class="px-4 py-3 text-right">';
            $html .= $this->renderActions($row, $this->actions);
            $html .= '</td>';
        }

        $html .= '</tr>';

        return $html;
    }

    /**
     * Render all cells in a row.
     *
     * @param mixed $row The row data
     * @param array $columns The table columns
     * @return string HTML for all cells
     */
    private function renderRowCells(mixed $row, array $columns): string
    {
        $html = '';

        foreach ($columns as $column) {
            $html .= $this->renderCell($row, $column);
        }

        return $html;
    }

    /**
     * Render a single cell.
     *
     * @param mixed $row The row data
     * @param mixed $column The column definition
     * @return string HTML for single cell
     */
    private function renderCell(mixed $row, mixed $column): string
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
        $cellAttributes = $this->buildCellAttributes($columnName);

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
     * @return array Array with 'class' and 'style' keys
     */
    private function buildCellAttributes(string $columnName): array
    {
        $tdClass = 'px-4 py-3 text-sm text-gray-900 dark:text-gray-100';
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
     * Render table footer with pagination info only (no pagination controls).
     * 
     * Pagination controls are handled by DataTables, so we only show the info text here.
     */
    public function renderFooter(array $data): string
    {
        // Don't render footer at all - DataTables handles everything
        // The info and pagination are controlled by DataTables' dom configuration
        return '';
    }

    /**
     * Render filter modal component.
     *
     * @param array $data Table data including filters and active filters
     * @return string HTML for filter modal
     */
    protected function renderFilterModal(array $data): string
    {
        $filters = $data['filters'] ?? [];
        $activeFilters = $data['activeFilters'] ?? [];
        $tableName = $data['tableName'] ?? 'datatable';
        $tableId = $data['tableId'] ?? 'datatable'; // Use generated tableId for JavaScript
        $config = $data['config'] ?? []; // Get config from data
        
        // Load active filters from session if available
        $sessionKey = "datatable_filters_{$tableName}";
        $sessionFilters = session($sessionKey, []);
        
        // Merge session filters with provided active filters (provided takes precedence)
        $activeFilters = array_merge($sessionFilters, $activeFilters);
        
        // Transform Filter objects to array format expected by modal component
        $transformedFilters = [];
        foreach ($filters as $column => $filter) {
            $transformedFilters[] = [
                'column' => $filter->getColumn(),
                'type' => $filter->getType(),
                'label' => $filter->getLabel(),
                'options' => $filter->getOptions(),
                'relate' => $filter->getRelate(),
                'bidirectional' => $filter->isBidirectional(), // Add bidirectional flag
                'autoSubmit' => $filter->shouldAutoSubmit(),
                'loading' => false,
            ];
        }
        
        // Count active filters (from session or provided)
        $activeFilterCount = count(array_filter($activeFilters, function($value) {
            return $value !== '' && $value !== null;
        }));
        
        // Use Blade component to render the modal
        try {
            return view('canvastack::components.table.filter-modal', [
                'filters' => $transformedFilters,
                'activeFilters' => $activeFilters,
                'tableName' => $tableName,
                'tableId' => $tableId, // Pass tableId for JavaScript global variable
                'activeFilterCount' => $activeFilterCount,
                'showButton' => false, // Don't show button, we have it in search bar
                'config' => $config, // Pass config to filter modal
            ])->render();
        } catch (\Exception $e) {
            // Fallback if view is not available
            return '<!-- Filter modal component not available: ' . $e->getMessage() . ' -->';
        }
    }

    /**
     * Render action buttons for row.
     */
    public function renderActions($row, array $actions): string
    {
        if (empty($actions)) {
            return '<div class="text-gray-400 text-xs">No actions</div>';
        }

        $id = is_array($row) ? ($row['id'] ?? null) : ($row->id ?? null);
        
        $html = '<div class="flex items-center justify-end gap-2">';
        
        foreach ($actions as $actionKey => $action) {
            $label = $action['label'] ?? 'Action';
            $icon = $action['icon'] ?? 'circle';
            $url = $action['url'] ?? '#';
            $method = $action['method'] ?? 'GET';
            $class = $action['class'] ?? 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400';
            $confirm = $action['confirm'] ?? null;
            
            // Replace :id placeholder with actual ID
            if (is_string($url)) {
                $url = str_replace(':id', (string) $id, $url);
            } elseif (is_callable($url)) {
                $url = $url($row);
            }
            
            // Build button attributes
            $attributes = [
                'class' => 'p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition ' . $class,
                'title' => $label,
            ];
            
            if ($method !== 'GET') {
                $attributes['data-method'] = $method;
                $attributes['data-url'] = $url;
            } else {
                $attributes['href'] = $url;
            }
            
            if ($confirm) {
                $attributes['data-confirm'] = $confirm;
            }
            
            // Build attributes string
            $attrString = '';
            foreach ($attributes as $key => $value) {
                $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
            
            // Get inline SVG for icon
            $iconSvg = $this->getIconSvg($icon);
            
            // Render button
            $tag = ($method === 'GET') ? 'a' : 'button';
            $html .= '<' . $tag . $attrString . '>';
            $html .= $iconSvg;
            $html .= '</' . $tag . '>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get inline SVG for common icons.
     * 
     * Returns inline SVG instead of data-lucide attributes to avoid
     * initialization issues with dynamically injected HTML.
     */
    protected function getIconSvg(string $icon): string
    {
        $svgs = [
            'eye' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
            'edit' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
            'trash' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
            'circle' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="2"></circle></svg>',
        ];
        
        return $svgs[$icon] ?? $svgs['circle'];
    }

    /**
     * Render DataTables initialization script.
     *
     * Generates the JavaScript code to initialize DataTables with proper configuration
     * for server-side or client-side processing, AJAX settings, and CSRF protection.
     *
     * @param  array  $config  Configuration array with DataTables settings
     * @return string The complete JavaScript code wrapped in <script> tags
     */
    protected function renderDataTablesScript(array $config): string
    {
        $tableId = $config['tableId'] ?? 'tbl_' . substr(md5(uniqid()), 0, 12);
        $tableName = $config['tableName'] ?? 'datatable';
        $serverSide = $config['serverSide'] ?? false;
        $httpMethod = $config['httpMethod'] ?? 'POST';
        $ajaxUrl = $config['ajaxUrl'] ?? '/datatable/data';
        $columns = $config['columns'] ?? [];
        
        // Use actions from class property (already extracted in render() method)
        $actions = $this->actions;
        
        // Debug: Log actions
        error_log('AdminRenderer::renderDataTablesScript - Actions count: ' . count($actions));
        error_log('AdminRenderer::renderDataTablesScript - Actions: ' . print_r($actions, true));
        
        // Get display limit from config (enhanced for task 3.1.3)
        $displayLimit = $config['displayLimit'] ?? 10;
        
        // Convert 'all' or '*' to -1 for DataTables
        $pageLength = ($displayLimit === 'all' || $displayLimit === '*') ? -1 : (int) $displayLimit;
        
        // Get fixed columns configuration (Phase 4: Fixed Columns)
        $fixedLeft = $config['fixedLeft'] ?? null;
        $fixedRight = $config['fixedRight'] ?? null;
        $hasFixedColumns = ($fixedLeft !== null && $fixedLeft > 0) || ($fixedRight !== null && $fixedRight > 0);
        
        // Get export buttons configuration (Phase 8: P2 Features)
        $exportButtons = $config['exportButtons'] ?? [];
        $hasExportButtons = !empty($exportButtons);
        
        // Build columns configuration for DataTables
        // For server-side: Specify columns explicitly INCLUDING action column
        // For client-side: Don't specify columns, let DataTable auto-detect
        $columnsConfig = '';
        $columnDefsConfig = '';
        
        if ($serverSide && !empty($columns)) {
            // Server-side: Specify ALL columns including actions
            $columnsArray = array_map(function ($column) {
                $columnName = is_array($column) ? $column['name'] : $column;
                return ['data' => $columnName];
            }, $columns);
            
            // Add action column with null data (will be rendered by rowCallback)
            $columnsArray[] = [
                'data' => null,
                'defaultContent' => '',
                'orderable' => false,
                'searchable' => false
            ];
            
            $columnsJson = json_encode($columnsArray);
            $columnsConfig = "columns: {$columnsJson},";
        }
        
        // Build columnDefs configuration with smart ID column detection
        $columnDefsArray = [];
        
        // 1. Actions column - always last, always right-aligned
        $columnDefsArray[] = [
            'targets' => -1,
            'orderable' => false,
            'searchable' => false,
            'className' => 'text-right'
        ];
        
        // 2. ID column detection - center align if column name is 'id' (case-insensitive)
        // Find ID column index
        $idColumnIndex = null;
        foreach ($columns as $index => $column) {
            $columnName = is_array($column) ? $column['name'] : $column;
            if (strtolower($columnName) === 'id') {
                $idColumnIndex = $index;
                break;
            }
        }
        
        // If ID column found and NOT manually overridden, apply center alignment
        if ($idColumnIndex !== null) {
            $columnName = is_array($columns[$idColumnIndex]) ? $columns[$idColumnIndex]['name'] : $columns[$idColumnIndex];
            
            // Check if this column has manual alignment override
            $hasManualOverride = isset($this->columnAlignments[$columnName]);
            
            if (!$hasManualOverride) {
                $columnDefsArray[] = [
                    'targets' => $idColumnIndex,
                    'className' => 'text-center'
                ];
            }
        }
        
        // 3. Apply manual column alignments from user configuration
        foreach ($this->columnAlignments as $columnName => $alignConfig) {
            // Find column index
            $columnIndex = null;
            foreach ($columns as $index => $column) {
                $colName = is_array($column) ? $column['name'] : $column;
                if ($colName === $columnName) {
                    $columnIndex = $index;
                    break;
                }
            }
            
            if ($columnIndex !== null) {
                $align = $alignConfig['align'] ?? 'left';
                $columnDefsArray[] = [
                    'targets' => $columnIndex,
                    'className' => 'text-' . $align
                ];
            }
        }
        
        // 4. Default styling for all cells
        $columnDefsArray[] = [
            'targets' => '_all',
            'className' => 'px-6 py-4 text-sm'
        ];
        
        $columnDefsJson = json_encode($columnDefsArray);
        $columnDefsConfig = "columnDefs: {$columnDefsJson},";


        // Pre-resolve ALL translations in PHP before passing to JavaScript
        // This ensures translation keys are resolved server-side, not client-side
        // Use canvastack:: namespace since translations are loaded with namespace
        // Use json_encode for proper JavaScript string escaping
        $translations = [
            'processing' => __('canvastack::components.table.datatables.processing'),
            'emptyTable' => __('canvastack::components.table.datatables.empty_table'),
            'zeroRecords' => __('canvastack::components.table.datatables.zero_records'),
            'info' => __('canvastack::components.table.datatables.info'),
            'infoEmpty' => __('canvastack::components.table.datatables.info_empty'),
            'infoFiltered' => __('canvastack::components.table.datatables.info_filtered'),
            'search' => __('canvastack::components.table.datatables.search'),
            'lengthMenu' => __('canvastack::components.table.datatables.length_menu'),
            'ajaxError' => __('canvastack::components.table.datatables.ajax_error'),
            'paginate' => [
                'first' => __('canvastack::components.table.datatables.paginate.first'),
                'last' => __('canvastack::components.table.datatables.paginate.last'),
                'next' => __('canvastack::components.table.datatables.paginate.next'),
                'previous' => __('canvastack::components.table.datatables.paginate.previous'),
            ],
        ];
        
        // Encode translations as JSON for safe JavaScript embedding
        $translationsJson = json_encode($translations, JSON_HEX_APOS | JSON_HEX_QUOT);
        
        // Prepare actions for JavaScript
        $actionsJson = json_encode($actions, JSON_HEX_APOS | JSON_HEX_QUOT);

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
                // Add table name for server-side processing
                d.tableName = '{$tableName}';
                
                // Add model class if available
                " . (isset($config['modelClass']) ? "d.modelClass = '" . addslashes($config['modelClass']) . "';" : "") . "
                
                // Debug: Log global variable name and value
                console.log('DataTable AJAX data function called');
                console.log('Looking for: window.tableFilters_{$tableId}');
                console.log('Value:', window.tableFilters_{$tableId});
                
                // Add custom filter data from filter form
                if (typeof window.tableFilters_{$tableId} !== 'undefined') {
                    d.filters = window.tableFilters_{$tableId};
                    console.log('Filters added to AJAX request:', d.filters);
                } else {
                    console.log('No filters found in global variable');
                }
                return d;
            },
            error: function(xhr, error, code) {
                console.error('DataTables AJAX error:', error, code);
                const translations = {$translationsJson};
                alert(translations.ajaxError);
            }
        },";
        }

        // Build fixed columns configuration (Phase 4: Fixed Columns)
        $fixedColumnsConfig = '';
        if ($hasFixedColumns) {
            // Disable responsive mode when using fixed columns (they conflict)
            $responsiveMode = 'false';
            
            // Build fixedColumns configuration
            $fixedColumnsJson = json_encode([
                'left' => $fixedLeft ?? 0,
                'right' => $fixedRight ?? 0,
            ]);
            
            $fixedColumnsConfig = "
            scrollX: true,
            scrollY: '500px',
            scrollCollapse: true,
            fixedColumns: {$fixedColumnsJson},";
        } else {
            $responsiveMode = 'true';
        }

        // Build export buttons configuration (Phase 8: P2 Features)
        $buttonsConfig = '';
        $domConfig = "'rt<\"flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-800\"ip>'";
        
        if ($hasExportButtons) {
            $buttonsArray = $this->getButtonsConfig($exportButtons);
            $buttonsJson = json_encode($buttonsArray);
            $buttonsConfig = "buttons: {$buttonsJson},";
            
            // Don't include 'B' in DOM - we'll initialize buttons manually
            // This prevents DataTables from creating wrapper divs that break layout
            $domConfig = "'rt<\"flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-800\"ip>'";
        }

        // Build complete DataTables configuration
        $script = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Translations object
    const translations = {$translationsJson};
    
    // Wait for jQuery to be ready
    if (typeof $ === 'undefined') {
        console.error('jQuery not loaded. DataTables requires jQuery.');
        return;
    }
    
    // Check if table exists
    if ($('#{$tableId}').length === 0) {
        console.error('Table #{$tableId} not found in DOM.');
        return;
    }
    
    // Initialize DataTables
    try {
        const table = $('#{$tableId}').DataTable({
            serverSide: " . ($serverSide ? 'true' : 'false') . ",{$ajaxConfig}
            processing: " . ($serverSide ? 'true' : 'false') . ",
            {$columnsConfig}{$columnDefsConfig}{$fixedColumnsConfig}{$buttonsConfig}
            responsive: {$responsiveMode},
            pageLength: {$pageLength},
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            rowCallback: function(row, data, dataIndex) {
                // Render action buttons for last column
                const lastCell = $(row).find('td:last');
                const tableActions = {$actionsJson};
                
                if (lastCell.length > 0 && tableActions && Object.keys(tableActions).length > 0) {
                    let actionsHtml = '<div class=\"flex items-center justify-end gap-2\">';
                    
                    // Icon SVG templates
                    const iconSvgs = {
                        'eye': '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\"></path></svg>',
                        'edit': '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\"></path></svg>',
                        'trash': '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path></svg>',
                        'circle': '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><circle cx=\"12\" cy=\"12\" r=\"10\" stroke-width=\"2\"></circle></svg>'
                    };
                    
                    // Iterate through configured actions
                    Object.keys(tableActions).forEach(function(actionKey) {
                        const action = tableActions[actionKey];
                        const label = action.label || 'Action';
                        const icon = action.icon || 'circle';
                        let url = action.url || '#';
                        const method = action.method || 'GET';
                        const className = action.class || 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400';
                        const confirm = action.confirm || null;
                        
                        // Replace :id placeholder with actual ID
                        if (typeof url === 'string') {
                            url = url.replace(':id', data.id || '');
                        }
                        
                        // Get icon SVG
                        const iconSvg = iconSvgs[icon] || iconSvgs['circle'];
                        
                        // Build button HTML
                        const tag = (method === 'GET') ? 'a' : 'button';
                        let attrs = 'class=\"p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition ' + className + '\" title=\"' + label + '\"';
                        
                        if (method !== 'GET') {
                            attrs += ' data-method=\"' + method + '\" data-url=\"' + url + '\"';
                        } else {
                            attrs += ' href=\"' + url + '\"';
                        }
                        
                        if (confirm) {
                            attrs += ' data-confirm=\"' + confirm + '\"';
                        }
                        
                        actionsHtml += '<' + tag + ' ' + attrs + '>' + iconSvg + '</' + tag + '>';
                    });
                    
                    actionsHtml += '</div>';
                    lastCell.html(actionsHtml);
                }
                
                return row;
            },
            language: {
                processing: '<div class=\"flex items-center justify-center gap-2\"><div class=\"animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600\"></div><span>' + translations.processing + '</span></div>',
                emptyTable: translations.emptyTable,
                zeroRecords: translations.zeroRecords,
                info: translations.info,
                infoEmpty: translations.infoEmpty,
                infoFiltered: translations.infoFiltered,
                search: translations.search,
                lengthMenu: '_MENU_',
                paginate: {
                    first: translations.paginate.first,
                    last: translations.paginate.last,
                    next: translations.paginate.next,
                    previous: translations.paginate.previous
                }
            },
            dom: {$domConfig}
        });

        // Store table instance globally for external access
        window.dataTable_{$tableName} = table;
        
        // Also store as default if first table
        if (typeof window.dataTable === 'undefined') {
            window.dataTable = table;
        }
        
        " . ($hasExportButtons ? "
        // Create and move export buttons to custom container
        setTimeout(function() {
            // Create a temporary container for buttons
            const tempContainer = $('<div></div>');
            $('body').append(tempContainer);
            
            // Initialize buttons on the table (not in DOM, so they go to temp container)
            new $.fn.dataTable.Buttons(table, {$buttonsJson});
            
            // Get the buttons container
            const buttonsContainer = table.buttons().container();
            const customContainer = $('#{$tableId}_export_buttons');
            
            if (buttonsContainer.length > 0 && customContainer.length > 0) {
                // Move buttons to our custom container
                customContainer.append(buttonsContainer);
                
                // Remove temp container
                tempContainer.remove();
                
                // Style the buttons container
                buttonsContainer.addClass('flex items-center gap-2');
                buttonsContainer.css({
                    'display': 'flex',
                    'align-items': 'center',
                    'gap': '0.5rem'
                });
                
                // Style individual buttons with proper sizing
                buttonsContainer.find('.dt-button').each(function() {
                    const btn = $(this);
                    btn.removeClass('dt-button');
                    btn.addClass('px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition');
                    btn.css({
                        'display': 'inline-flex',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'gap': '0.375rem',
                        'font-size': '0.75rem',
                        'font-weight': '500',
                        'line-height': '1.2',
                        'white-space': 'nowrap',
                        'padding': '0.5rem 0.875rem',
                        'min-width': 'fit-content',
                        'vertical-align': 'middle'
                    });
                    
                    // Style icons inside buttons
                    btn.find('i[data-lucide], svg').css({
                        'width': '12px',
                        'height': '12px',
                        'flex-shrink': '0',
                        'display': 'inline-block',
                        'vertical-align': 'middle',
                        'margin-right': '4px'
                    });
                });
                
                // Re-initialize Lucide icons for export buttons (with proper parameters)
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    // Use the global icons object if available
                    if (typeof lucide.icons !== 'undefined') {
                        lucide.createIcons({ icons: lucide.icons });
                    } else {
                        // Fallback: just replace icons in the buttons container
                        const iconElements = buttonsContainer.find('[data-lucide]');
                        iconElements.each(function() {
                            const iconName = $(this).attr('data-lucide');
                            if (iconName && lucide[iconName]) {
                                const icon = lucide[iconName];
                                $(this).replaceWith(icon.toSvg({ class: 'w-3.5 h-3.5', 'stroke-width': '2' }));
                            }
                        });
                    }
                }
            }
        }, 500);
        " : "") . "
        
        // Connect custom search input to DataTables search (MANUAL SUBMIT ONLY)
        const searchInput = $('#{$tableId}_search');
        const searchBtn = $('#{$tableId}_search_btn');
        
        // Show/hide search button based on input value
        searchInput.on('input', function() {
            const hasValue = $(this).val().trim().length > 0;
            if (hasValue) {
                searchBtn.css({
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            } else {
                searchBtn.css({
                    'opacity': '0',
                    'pointer-events': 'none'
                });
                // Clear search if input is empty
                table.search('').draw();
            }
        });
        
        // Search only when button is clicked
        searchBtn.on('click', function(e) {
            e.preventDefault();
            const searchValue = searchInput.val();
            table.search(searchValue).draw();
        });
        
        // Also allow Enter key to submit search
        searchInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                const searchValue = $(this).val();
                table.search(searchValue).draw();
            }
        });
        
        // Connect custom length menu to DataTables page length (enhanced for task 3.1.3)
        $('#{$tableId}_length').on('change', function() {
            const selectedValue = this.value;
            const pageLength = selectedValue === 'all' ? -1 : parseInt(selectedValue);
            table.page.len(pageLength).draw();
        });
        
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
        
        // Filter button functionality - Use actual modal component
        $('#{$tableId}_filter_btn').on('click', function() {
            // Find the filter modal component and open it
            const modalComponent = document.querySelector('[x-data*=\"filterModal\"]');
            if (modalComponent) {
                // Use _x_dataStack (internal Alpine.js property)
                if (modalComponent._x_dataStack && modalComponent._x_dataStack[0]) {
                    try {
                        modalComponent._x_dataStack[0].open = true;
                        return;
                    } catch (e) {
                        console.warn('_x_dataStack method failed:', e);
                    }
                }
                
                // Method 2: Dispatch custom event
                try {
                    modalComponent.dispatchEvent(new CustomEvent('open-filter-modal'));
                    return;
                } catch (e) {
                    console.warn('Custom event method failed:', e);
                }
                
                console.warn('All methods to open filter modal failed');
            } else {
                console.warn('Filter modal component not found');
            }
        });
        
        console.log('DataTable initialized successfully:', '#{$tableId}');
        
        // Add action button handlers
        document.querySelector('#{$tableId}').addEventListener('click', function(e) {
            const button = e.target.closest('button[data-method], a[data-method]');
            if (!button) return;
            
            e.preventDefault();
            
            const method = button.getAttribute('data-method');
            const url = button.getAttribute('data-url');
            const confirm = button.getAttribute('data-confirm');
            
            // Show confirmation if required
            if (confirm && !window.confirm(confirm)) {
                return;
            }
            
            // Handle different HTTP methods
            if (method === 'DELETE' || method === 'POST' || method === 'PUT' || method === 'PATCH') {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                
                // Add CSRF token
                const csrfToken = document.querySelector('meta[name=\"csrf-token\"]');
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken.content;
                    form.appendChild(csrfInput);
                }
                
                // Add method spoofing for DELETE/PUT/PATCH
                if (method !== 'POST') {
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = method;
                    form.appendChild(methodInput);
                }
                
                document.body.appendChild(form);
                form.submit();
            } else {
                // For GET, just navigate
                window.location.href = url;
            }
        });
    } catch (error) {
        console.error('Failed to initialize DataTable:', error);
    }
});
</script>";

        return $script;
    }

    /**
     * Get buttons configuration for DataTables Buttons extension.
     *
     * Builds the configuration array for export buttons (Excel, CSV, PDF, Print, Copy).
     * Each button is configured with proper styling, icons, and export options.
     *
     * @param  array  $buttons  Array of button types to include
     * @return array Array of button configurations
     */
    protected function getButtonsConfig(array $buttons): array
    {
        $buttonsConfig = [];
        
        foreach ($buttons as $button) {
            $config = match($button) {
                'excel' => [
                    'extend' => 'excelHtml5',
                    'text' => '<i data-lucide="file-spreadsheet" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px;"></i><span style="display:inline;vertical-align:middle;">Excel</span>',
                    'className' => 'btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white border-0',
                    'exportOptions' => [
                        'columns' => ':visible:not(.no-export):not(:last-child)' // Exclude actions column
                    ]
                ],
                'csv' => [
                    'extend' => 'csvHtml5',
                    'text' => '<i data-lucide="file-text" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px;"></i><span style="display:inline;vertical-align:middle;">CSV</span>',
                    'className' => 'btn btn-sm bg-cyan-600 hover:bg-cyan-700 text-white border-0',
                    'exportOptions' => [
                        'columns' => ':visible:not(.no-export):not(:last-child)' // Exclude actions column
                    ]
                ],
                'pdf' => [
                    'extend' => 'pdfHtml5',
                    'text' => '<i data-lucide="file" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px;"></i><span style="display:inline;vertical-align:middle;">PDF</span>',
                    'className' => 'btn btn-sm bg-red-600 hover:bg-red-700 text-white border-0',
                    'orientation' => 'landscape',
                    'pageSize' => 'A4',
                    'exportOptions' => [
                        'columns' => ':visible:not(.no-export):not(:last-child)' // Exclude actions column
                    ]
                ],
                'print' => [
                    'extend' => 'print',
                    'text' => '<i data-lucide="printer" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px;"></i><span style="display:inline;vertical-align:middle;">Print</span>',
                    'className' => 'btn btn-sm bg-gray-600 hover:bg-gray-700 text-white border-0',
                    'exportOptions' => [
                        'columns' => ':visible:not(.no-export):not(:last-child)' // Exclude actions column
                    ]
                ],
                'copy' => [
                    'extend' => 'copy',
                    'text' => '<i data-lucide="copy" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px;"></i><span style="display:inline;vertical-align:middle;">Copy</span>',
                    'className' => 'btn btn-sm bg-indigo-600 hover:bg-indigo-700 text-white border-0',
                    'exportOptions' => [
                        'columns' => ':visible:not(.no-export):not(:last-child)' // Exclude actions column
                    ]
                ],
                default => null,
            };
            
            if ($config !== null) {
                $buttonsConfig[] = $config;
            }
        }
        
        return $buttonsConfig;
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
                ? '<span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-full">' . __('canvastack::components.table.yes') . '</span>'
                : '<span class="px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full">' . __('canvastack::components.table.no') . '</span>';
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
        // Row styling would be handled at row level

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

        // Get values for all fields in the formula
        foreach ($formula['fields'] as $field) {
            $value = is_array($row) ? ($row[$field] ?? 0) : ($row->{$field} ?? 0);
            $fieldValues[$field] = is_numeric($value) ? (float) $value : 0;
        }

        // Replace field names with values in the logic string
        $logic = $formula['logic'];
        foreach ($fieldValues as $field => $value) {
            $logic = str_replace($field, (string) $value, $logic);
        }

        try {
            // Safely evaluate the expression
            // Note: In production, use a proper expression evaluator library
            $result = $this->evaluateExpression($logic);

            return $result;
        } catch (\Throwable $e) {
            // Handle division by zero or other errors gracefully
            return null;
        }
    }

    /**
     * Safely evaluate mathematical expression.
     */
    protected function evaluateExpression(string $expression): float|int
    {
        // Remove whitespace
        $cleanedExpression = preg_replace('/\s+/', '', $expression);

        if ($cleanedExpression === null) {
            return 0;
        }

        // Validate expression contains only allowed characters
        if (!preg_match('/^[0-9+\-*\/%().]+$/', $cleanedExpression)) {
            return 0;
        }

        try {
            // Use eval with extreme caution - only for validated numeric expressions
            // In production, consider using a proper math expression parser
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
            return 'N/A';
        }

        // Handle Carbon/DateTime objects - convert to string
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
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
                    // Assume value is a timestamp or date string
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

            // Insert at specified position or append
            if (isset($formula['node_location']) && $formula['node_location'] !== null) {
                $position = $this->findColumnPosition($columns, $formula['node_location']);
                if ($position !== false) {
                    $offset = $formula['node_after'] ? $position + 1 : $position;
                    array_splice($columns, $offset, 0, [$formulaColumn]);
                    continue;
                }
            }

            // Append if no position specified or position not found
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


<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Renderers;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Facades\Theme;
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;

/**
 * TanStack Table Renderer
 * 
 * Renders tables using TanStack Table v8 with Alpine.js integration.
 * Provides modern, performant table rendering with unlimited design flexibility.
 * 
 * @package Canvastack\Canvastack\Components\Table\Renderers
 */
class TanStackRenderer
{
    /**
     * Configuration cache.
     *
     * @var array
     */
    protected array $configCache = [];

    /**
     * Engine configuration.
     *
     * @var array
     */
    protected array $config = [];

    /**
     * Column pinning configuration.
     *
     * @var array|null
     */
    protected ?array $columnPinning = null;

    /**
     * Theme locale integration instance.
     *
     * @var ThemeLocaleIntegration
     */
    protected ThemeLocaleIntegration $themeLocaleIntegration;

    /**
     * Constructor.
     *
     * @param ThemeLocaleIntegration $themeLocaleIntegration
     */
    public function __construct(ThemeLocaleIntegration $themeLocaleIntegration)
    {
        $this->themeLocaleIntegration = $themeLocaleIntegration;
    }

    /**
     * Set configuration for renderer.
     *
     * @param array $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Render the complete table HTML.
     * 
     * Injects theme CSS via Theme facade for full Theme Engine compliance.
     * 
     * @param TableBuilder $table The table builder instance
     * @param array $config TanStack configuration
     * @param array $columns Column definitions
     * @param array $alpineData Alpine.js data configuration
     * @return string The rendered HTML
     */
    public function render(
        TableBuilder $table,
        array $config,
        array $columns,
        array $alpineData
    ): string {
        // Use cache key to avoid re-rendering identical tables
        $cacheKey = $this->getCacheKey($table, $config);
        
        if (isset($this->configCache[$cacheKey])) {
            return $this->configCache[$cacheKey];
        }
        
        // Use output buffering for better performance
        ob_start();
        
        // Inject theme CSS only once (Requirement 51.7, 51.8, 51.12)
        static $themeInjected = false;
        if (!$themeInjected) {
            echo $this->injectThemeCSS();
            $themeInjected = true;
        }
        
        // Inject table-specific CSS
        echo $this->renderStyles($table);
        
        // Store column pinning configuration
        $this->columnPinning = $config['columnPinning'] ?? null;
        
        // Render filter modal BEFORE table container so Alpine can init it
        $tableId = $table->getTableId() ?? 'tanstack-table-' . uniqid();
        echo $this->renderFilterModal($table, $tableId);
        
        // Render table container with Alpine.js
        echo $this->renderTableContainer($table, $alpineData);
        
        // Render JavaScript for Alpine.js (already wrapped in <script> tags)
        echo $this->renderScripts($table, $config, $columns, $alpineData);
        
        $html = ob_get_clean();
        
        // Cache the result
        $this->configCache[$cacheKey] = $html;
        
        return $html;
    }

    /**
     * Get cache key for configuration.
     *
     * @param TableBuilder $table
     * @param array $config
     * @return string
     */
    protected function getCacheKey(TableBuilder $table, array $config): string
    {
        return md5(serialize([
            $table->getTableId(),
            $config['columns'] ?? [],
            $config['pagination'] ?? [],
            $config['sorting'] ?? [],
        ]));
    }

    /**
     * Render JavaScript for TanStack Table.
     * 
     * @param TableBuilder $table The table builder instance
     * @param array $config TanStack configuration
     * @param array $columns Column definitions
     * @param array $alpineData Alpine.js data configuration
     * @return string The JavaScript code
     */
    public function renderScripts(
        TableBuilder $table,
        array $config,
        array $columns,
        array $alpineData
    ): string {
        $tableId = $table->getTableId() ?? 'tanstack-table-' . uniqid();
        
        // Merge columns into alpineData
        $alpineData['columns'] = $columns;
        
        // Add pagination object
        $alpineData['pagination'] = [
            'page' => 1,
            'pageSize' => $alpineData['pageSize'] ?? 10,
            'totalPages' => ceil(($alpineData['totalRows'] ?? 0) / ($alpineData['pageSize'] ?? 10)),
            'totalRows' => $alpineData['totalRows'] ?? 0,
        ];
        
        // Ensure all required properties exist
        $alpineData['loading'] = $alpineData['loading'] ?? false;
        $alpineData['error'] = $alpineData['error'] ?? null;
        $alpineData['errorMessage'] = $alpineData['errorMessage'] ?? '';
        $alpineData['globalFilter'] = $alpineData['globalFilter'] ?? '';
        $alpineData['data'] = $alpineData['data'] ?? [];
        
        // Add server-side properties
        $alpineData['serverSideUrl'] = $config['serverSide']['url'] ?? null;
        $alpineData['tableName'] = $table->getModel() ? $table->getModel()->getTable() : 'users';
        $alpineData['modelClass'] = $table->getModel() ? get_class($table->getModel()) : null;
        $alpineData['searchableColumns'] = $table->getConfiguration()->searchableColumns ?? [];
        
        // JSON encode with proper escaping
        $alpineDataJson = json_encode($alpineData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        // Extract column pinning configuration
        $pinnedLeft = json_encode($config['columnPinning']['left'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $pinnedRight = json_encode($config['columnPinning']['right'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        // Pre-encode all translation strings for use in JavaScript
        $noRowsSelected = json_encode(__('canvastack::components.table.no_rows_selected'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $confirmAction = json_encode(__('canvastack::components.table.confirm_action'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $bulkActionError = json_encode(__('canvastack::components.table.bulk_action_error'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        return <<<JS
<script>
// TanStack Table Component Registration
(function() {
    const registerComponent = () => {
        console.log('TanStack Table: Registering component tanstackTable_{$tableId}');
        
        Alpine.data('tanstackTable_{$tableId}', () => {
            const alpineData = {$alpineDataJson};
            
            // Debug: Log columns to verify header values
            console.log('TanStack Table: Columns data', alpineData.columns);
            
            return {
                ...alpineData,
                
                // Column pinning configuration
                pinnedLeft: {$pinnedLeft},
                pinnedRight: {$pinnedRight},
                
                // Row selection state
                rowSelection: {},
                selectedCount: 0,
                selectAll: false,
                isIndeterminate: false,
                selectionEnabled: false,
                
                // Sorting state
                sorting: {
                    column: null,
                    direction: 'asc'
                },
                
                // Filter state
                showFilters: false,
                
                // Confirmation modal state
                showConfirmModal: false,
                confirmModalTitle: '',
                confirmModalMessage: '',
                confirmModalAction: null,
                
                // Theme switching state
                currentTheme: null,
                themeTransitioning: false,
                
                /**
                 * Initialize the table component.
                 */
                init() {
                    console.log('TanStack Table: Initializing with', this.data.length, 'rows');
                    
                    // Check if server-side processing is enabled
                    if (this.serverSideUrl) {
                        console.log('TanStack Table: Server-side mode enabled, loading data from server');
                        
                        // Check if there are saved filters in session, then load data
                        this.checkSavedFilters().then(() => {
                            this.loadData();
                        });
                        
                        // Listen for filter apply event
                        window.addEventListener('filters-applied', () => {
                            console.log('TanStack Table: Filters applied, reloading data...');
                            this.pagination.page = 1; // Reset to first page
                            this.loadData();
                        });
                        
                        return;
                    }
                    
                    // Client-side mode
                    console.log('TanStack Table: Client-side mode, using provided data');
                    
                    // Store original data for client-side pagination/filtering
                    // Only set originalData if it doesn't exist yet (prevent override on re-init)
                    if (!this.originalData || this.originalData.length === 0) {
                        this.originalData = [...this.data];
                    }
                    
                    // Initialize row selection if enabled
                    if (this.selectionEnabled) {
                        this.updateSelectionState();
                    }
                    
                    // Initialize theme switching support
                    this.initThemeSwitching();
                    
                    // Apply initial pagination
                    this.applyPagination();
                },
                
                /**
                 * Apply client-side pagination to data.
                 * Slices the data array based on current page and page size.
                 */
                applyPagination() {
                    if (!this.originalData || this.originalData.length === 0) {
                        this.data = [];
                        return;
                    }
                    
                    const start = (this.pagination.page - 1) * this.pagination.pageSize;
                    const end = start + this.pagination.pageSize;
                    this.data = this.originalData.slice(start, end);
                    
                    console.log('TanStack Table: Pagination applied', {
                        page: this.pagination.page,
                        pageSize: this.pagination.pageSize,
                        start,
                        end,
                        showing: this.data.length,
                        total: this.originalData.length
                    });
                },
                
                /**
                 * Initialize theme switching support.
                 * 
                 * Listens for theme change events and updates table styling reactively.
                 * Validates: Requirements 51.9, 51.13
                 */
                initThemeSwitching() {
            // Get current theme from document
            this.currentTheme = this.getCurrentTheme();
            
            // Listen for theme change events
            window.addEventListener('theme:changed', (event) => {
                this.handleThemeChange(event.detail);
            });
            
            // Listen for MutationObserver on document.documentElement for class changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const newTheme = this.getCurrentTheme();
                        if (newTheme !== this.currentTheme) {
                            this.handleThemeChange({ theme: newTheme });
                        }
                    }
                });
            });
            
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Listen for dark mode toggle
            window.addEventListener('darkmode:toggled', (event) => {
                this.handleDarkModeToggle(event.detail);
            });
        },
        
        /**
         * Get current theme from document.
         * 
         * @return {string} - Current theme name
         */
        getCurrentTheme() {
            // Try to get theme from data attribute
            const themeAttr = document.documentElement.getAttribute('data-theme');
            if (themeAttr) {
                return themeAttr;
            }
            
            // Try to get theme from class
            const classList = document.documentElement.classList;
            for (const className of classList) {
                if (className.startsWith('theme-')) {
                    return className.replace('theme-', '');
                }
            }
            
            // Default theme
            return 'default';
        },
        
        /**
         * Handle theme change event.
         * 
         * Updates table styling reactively without page reload.
         * Validates: Requirements 51.9, 51.13
         * 
         * @param {Object} detail - Event detail with theme information
         */
        handleThemeChange(detail) {
            const newTheme = detail.theme || this.getCurrentTheme();
            
            if (newTheme === this.currentTheme) {
                return; // No change
            }
            
            console.log('TanStack Table: Theme changing from', this.currentTheme, 'to', newTheme);
            
            // Set transitioning state
            this.themeTransitioning = true;
            
            // Update current theme
            this.currentTheme = newTheme;
            
            // Apply smooth transition class (0.2s ease)
            const tableElement = this.\$el.querySelector('.tanstack-table-container');
            if (tableElement) {
                tableElement.classList.add('theme-transitioning');
            }
            
            // Update CSS variables if provided in event
            if (detail.colors) {
                this.updateThemeColors(detail.colors);
            }
            
            if (detail.fonts) {
                this.updateThemeFonts(detail.fonts);
            }
            
            // Remove transitioning class after transition completes (0.2s)
            setTimeout(() => {
                this.themeTransitioning = false;
                if (tableElement) {
                    tableElement.classList.remove('theme-transitioning');
                }
                console.log('TanStack Table: Theme transition complete');
            }, 200);
        },
        
        /**
         * Handle dark mode toggle event.
         * 
         * @param {Object} detail - Event detail with dark mode state
         */
        handleDarkModeToggle(detail) {
            console.log('TanStack Table: Dark mode toggled', detail.enabled ? 'ON' : 'OFF');
            
            // Dark mode is handled by Tailwind dark: prefix
            // No additional action needed - CSS will update automatically
            
            // Trigger a theme change event for consistency
            this.handleThemeChange({
                theme: this.currentTheme,
                darkMode: detail.enabled
            });
        },
        
        /**
         * Update theme colors dynamically.
         * 
         * Updates CSS variables for theme colors.
         * Validates: Requirement 51.13 (smooth transitions)
         * 
         * @param {Object} colors - Theme color palette
         */
        updateThemeColors(colors) {
            const root = document.documentElement;
            
            // Update CSS variables
            if (colors.primary) {
                root.style.setProperty('--cs-color-primary', colors.primary);
            }
            if (colors.secondary) {
                root.style.setProperty('--cs-color-secondary', colors.secondary);
            }
            if (colors.accent) {
                root.style.setProperty('--cs-color-accent', colors.accent);
            }
            if (colors.success) {
                root.style.setProperty('--cs-color-success', colors.success);
            }
            if (colors.warning) {
                root.style.setProperty('--cs-color-warning', colors.warning);
            }
            if (colors.error) {
                root.style.setProperty('--cs-color-error', colors.error);
            }
            if (colors.info) {
                root.style.setProperty('--cs-color-info', colors.info);
            }
            
            console.log('TanStack Table: Theme colors updated', colors);
        },
        
        /**
         * Update theme fonts dynamically.
         * 
         * Updates CSS variables for theme fonts.
         * 
         * @param {Object} fonts - Theme font definitions
         */
        updateThemeFonts(fonts) {
            const root = document.documentElement;
            
            // Update CSS variables
            if (fonts.sans) {
                root.style.setProperty('--cs-font-sans', fonts.sans);
            }
            if (fonts.mono) {
                root.style.setProperty('--cs-font-mono', fonts.mono);
            }
            
            console.log('TanStack Table: Theme fonts updated', fonts);
        },
        
        /**
         * Check if a row is selected.
         * 
         * @param {string|number} rowId - The row ID
         * @return {boolean} - True if row is selected
         */
        isRowSelected(rowId) {
            return this.rowSelection[rowId] === true;
        },
        
        /**
         * Handle row selection change.
         * 
         * @param {string|number} rowId - The row ID
         */
        onRowSelectChange(rowId) {
            if (this.rowSelection[rowId]) {
                delete this.rowSelection[rowId];
            } else {
                this.rowSelection[rowId] = true;
            }
            
            this.updateSelectionState();
        },
        
        /**
         * Handle select all checkbox change.
         */
        onSelectAllChange() {
            if (this.selectAll) {
                // Select all rows
                this.data.forEach(row => {
                    this.rowSelection[row.id] = true;
                });
            } else {
                // Deselect all rows
                this.rowSelection = {};
            }
            
            this.updateSelectionState();
        },
        
        /**
         * Update selection state (count, selectAll, indeterminate).
         */
        updateSelectionState() {
            const selectedIds = Object.keys(this.rowSelection).filter(id => this.rowSelection[id]);
            this.selectedCount = selectedIds.length;
            
            const totalRows = this.data.length;
            this.selectAll = this.selectedCount === totalRows && totalRows > 0;
            this.isIndeterminate = this.selectedCount > 0 && this.selectedCount < totalRows;
        },
        
        /**
         * Clear all selections.
         */
        clearSelection() {
            this.rowSelection = {};
            this.updateSelectionState();
        },
        
        /**
         * Get selected row IDs.
         * 
         * @return {Array} - Array of selected row IDs
         */
        getSelectedRowIds() {
            return Object.keys(this.rowSelection).filter(id => this.rowSelection[id]);
        },
        
        /**
         * Get selected rows data.
         * 
         * @return {Array} - Array of selected row objects
         */
        getSelectedRows() {
            const selectedIds = this.getSelectedRowIds();
            return this.data.filter(row => selectedIds.includes(String(row.id)));
        },
        
        /**
         * Execute bulk action.
         * 
         * @param {string} name - Action name
         * @param {string} url - Action URL
         * @param {string} method - HTTP method
         * @param {string|null} confirm - Confirmation message
         */
        async executeBulkAction(name, url, method, confirm) {
            // Get selected row IDs
            const selectedIds = this.getSelectedRowIds();
            
            if (selectedIds.length === 0) {
                this.showAlert({$noRowsSelected});
                return;
            }
            
            // Show confirmation dialog if required
            if (confirm) {
                this.confirmModalTitle = {$confirmAction};
                this.confirmModalMessage = confirm;
                this.confirmModalAction = async () => {
                    await this.performBulkAction(name, url, method, selectedIds);
                };
                this.showConfirmModal = true;
                return;
            }
            
            // Execute without confirmation
            await this.performBulkAction(name, url, method, selectedIds);
        },
        
        /**
         * Perform the actual bulk action.
         * 
         * @param {string} name - Action name
         * @param {string} url - Action URL
         * @param {string} method - HTTP method
         * @param {Array} selectedIds - Selected row IDs
         */
        async performBulkAction(name, url, method, selectedIds) {
            // Show loading state
            this.loading = true;
            
            try {
                // Prepare form data
                const formData = new FormData();
                formData.append('_method', method);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                // Add selected IDs
                selectedIds.forEach(id => {
                    formData.append('ids[]', id);
                });
                
                // Send request
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    // Success - show message and reload data
                    if (result.message) {
                        this.showAlert(result.message, 'success');
                    }
                    
                    // Clear selection
                    this.clearSelection();
                    
                    // Reload table data
                    await this.loadData();
                } else {
                    // Error - show error message
                    this.showAlert(result.message || {$bulkActionError}, 'error');
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                this.showAlert({$bulkActionError}, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        /**
         * Confirm the modal action.
         */
        async confirmModalActionHandler() {
            this.showConfirmModal = false;
            if (this.confirmModalAction) {
                await this.confirmModalAction();
                this.confirmModalAction = null;
            }
        },
        
        /**
         * Cancel the modal action.
         */
        cancelModalAction() {
            this.showConfirmModal = false;
            this.confirmModalAction = null;
        },
        
        /**
         * Show alert message.
         * 
         * @param {string} message - Alert message
         * @param {string} type - Alert type (success, error, info)
         */
        showAlert(message, type = 'info') {
            // Use browser alert for now
            // TODO: Implement custom alert component
            alert(message);
        },
        
        /**
         * Get CSS classes for a column based on pinning state.
         * 
         * @param {Object} column - The column object
         * @param {number} index - The column index
         * @return {Object} - Object with CSS classes
         */
        getColumnClass(column, index) {
            const classes = {
                'sortable': column.enableSorting
            };
            
            // Check if column is pinned to left
            if (this.pinnedLeft.includes(column.id)) {
                classes['tanstack-table-pinned-left'] = true;
            }
            
            // Check if column is pinned to right
            if (this.pinnedRight.includes(column.id)) {
                classes['tanstack-table-pinned-right'] = true;
            }
            
            return classes;
        },
        
        /**
         * Render cell content.
         * 
         * @param {Object} row - The row data
         * @param {Object} column - The column definition
         * @return {string} - Rendered HTML
         */
        renderCell(row, column) {
            // Handle actions column
            if (column.id === 'actions' && row._actions) {
                console.log('Rendering actions for row', row.id, row._actions);
                return this.renderActions(row._actions);
            }
            
            const value = row[column.id];
            
            // Handle null/undefined
            if (value === null || value === undefined) {
                return '<span class="text-gray-400">-</span>';
            }
            
            // Custom renderer if defined
            if (column.meta && column.meta.renderer) {
                return column.meta.renderer(row);
            }
            
            // Default rendering
            return String(value);
        },
        
        /**
         * Render action buttons for a row.
         * 
         * @param {Array} actions - Array of action objects
         * @return {string} - Rendered HTML
         */
        renderActions(actions) {
            if (!actions || actions.length === 0) {
                return '<span class="text-gray-400">-</span>';
            }
            
            // Heroicons for action buttons
            const icons = {
                'eye': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
                'edit': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                'trash': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
                'plus': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'check': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                'x': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
            };
            
            let html = '<div class="action-buttons-wrapper">';
            
            // Desktop: Inline buttons (hidden on mobile)
            html += '<div class="action-buttons-inline hidden md:flex gap-2">';
            actions.forEach(function(action) {
                const icon = action.icon || 'circle';
                const label = action.label || action.name;
                const url = action.url || '#';
                const method = action.method || 'GET';
                const iconSvg = icons[icon] || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';
                
                if (method === 'DELETE' && action.confirm) {
                    html += '<button onclick="if(confirm(\'' + action.confirm + '\')) { /* handle delete */ }" ';
                    html += 'class="action-btn-inline" title="' + label + '">';
                    html += iconSvg;
                    html += '</button>';
                } else {
                    html += '<a href="' + url + '" class="action-btn-inline" title="' + label + '">';
                    html += iconSvg;
                    html += '</a>';
                }
            });
            html += '</div>';
            
            // Mobile: Dropdown menu (visible only on mobile)
            const dropdownId = 'dropdown_' + Math.random().toString(36).substr(2, 9);
            html += '<div class="action-dropdown-container md:hidden">';
            html += '<button onclick="toggleActionDropdown(\'' + dropdownId + '\')" class="action-button" type="button">';
            html += '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">';
            html += '<circle cx="12" cy="5" r="2"/>';
            html += '<circle cx="12" cy="12" r="2"/>';
            html += '<circle cx="12" cy="19" r="2"/>';
            html += '</svg>';
            html += '</button>';
            
            html += '<div id="' + dropdownId + '" class="action-dropdown" style="display: none;">';
            actions.forEach(function(action, index) {
                const icon = action.icon || 'circle';
                const label = action.label || action.name;
                const url = action.url || '#';
                const method = action.method || 'GET';
                const iconSvg = icons[icon] || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';
                
                if (method === 'DELETE' && action.confirm) {
                    html += '<button onclick="if(confirm(\'' + action.confirm + '\')) { /* handle delete */ }" ';
                    html += 'class="action-dropdown-item">';
                    html += iconSvg;
                    html += '<span>' + label + '</span>';
                    html += '</button>';
                } else {
                    html += '<a href="' + url + '" class="action-dropdown-item">';
                    html += iconSvg;
                    html += '<span>' + label + '</span>';
                    html += '</a>';
                }
            });
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            return html;
        },
        
        /**
         * Get pagination text.
         * 
         * @return {string} - Pagination info text
         */
        paginationText() {
            const start = (this.pagination.page - 1) * this.pagination.pageSize + 1;
            const end = Math.min(this.pagination.page * this.pagination.pageSize, this.pagination.totalRows);
            return `Showing \${start} to \${end} of \${this.pagination.totalRows} entries`;
        },
        
        /**
         * Get page numbers to display.
         * Shows max 5 pages around current page.
         * 
         * @return {Array} - Array of page numbers
         */
        getPageNumbers() {
            const current = this.pagination.page;
            const total = this.pagination.totalPages;
            const pages = [];
            
            // Show max 5 pages
            let start = Math.max(1, current - 2);
            let end = Math.min(total, start + 4);
            
            // Adjust start if we're near the end
            if (end - start < 4) {
                start = Math.max(1, end - 4);
            }
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        /**
         * Export data to various formats.
         * 
         * @param {string} format - Export format (excel, csv, pdf, print, copy)
         */
        exportData(format) {
            const exportData = this.data || [];
            const columns = this.columns || [];
            
            switch(format) {
                case 'excel':
                    this.exportToExcel(exportData, columns);
                    break;
                case 'csv':
                    this.exportToCSV(exportData, columns);
                    break;
                case 'pdf':
                    this.exportToPDF(exportData, columns);
                    break;
                case 'print':
                    this.printTable(exportData, columns);
                    break;
                case 'copy':
                    this.copyToClipboard(exportData, columns);
                    break;
            }
        },
        
        /**
         * Export to Excel format.
         */
        exportToExcel(data, columns) {
            // Simple CSV export (Excel can open CSV files)
            this.exportToCSV(data, columns, 'export.xls');
        },
        
        /**
         * Export to CSV format.
         */
        exportToCSV(data, columns, filename = 'export.csv') {
            const headers = columns.map(col => col.header || col.label || col.id).join(',');
            const rows = data.map(row => {
                return columns.map(col => {
                    const value = row[col.id] || '';
                    return '"' + String(value).replace(/"/g, '""') + '"';
                }).join(',');
            });
            
            const csv = [headers, ...rows].join('\\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        },
        
        /**
         * Export to PDF format.
         */
        exportToPDF(data, columns) {
            alert('PDF export requires jsPDF library. Please implement PDF export functionality.');
        },
        
        /**
         * Print table.
         */
        printTable(data, columns) {
            const headers = columns.map(col => '<th>' + (col.header || col.label || col.id) + '</th>').join('');
            const rows = data.map(row => {
                const cells = columns.map(col => '<td>' + (row[col.id] || '') + '</td>').join('');
                return '<tr>' + cells + '</tr>';
            }).join('');
            
            const printWindow = window.open('', '_blank');
            const html = '<!DOCTYPE html><html><head><title>Print Table</title>' +
                '<style>table { border-collapse: collapse; width: 100%; }' +
                'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }' +
                'th { background-color: #f2f2f2; }</style></head><body>' +
                '<table><thead><tr>' + headers + '</tr></thead>' +
                '<tbody>' + rows + '</tbody></table>' +
                '<scr' + 'ipt>window.print(); window.close();</scr' + 'ipt></body></html>';
            
            printWindow.document.write(html);
            printWindow.document.close();
        },
        
        /**
         * Copy table to clipboard.
         */
        copyToClipboard(data, columns) {
            const headers = columns.map(col => col.header || col.label || col.id).join('\\t');
            const rows = data.map(row => {
                return columns.map(col => row[col.id] || '').join('\\t');
            });
            
            const text = [headers, ...rows].join('\\n');
            
            navigator.clipboard.writeText(text).then(() => {
                alert('Table data copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        },
        
        /**
         * Toggle fullscreen mode.
         */
        toggleFullscreen() {
            const tableCard = this.\$el.closest('.tanstack-table-card');
            if (!tableCard) return;
            
            if (tableCard.classList.contains('fullscreen')) {
                // Exit fullscreen
                tableCard.classList.remove('fullscreen');
                document.body.style.overflow = '';
                document.body.classList.remove('fullscreen-active');
                console.log('TanStack Table: Exited fullscreen mode');
            } else {
                // Enter fullscreen
                tableCard.classList.add('fullscreen');
                document.body.style.overflow = 'hidden';
                document.body.classList.add('fullscreen-active');
                console.log('TanStack Table: Entered fullscreen mode');
            }
        },
        
        /**
         * Go to specific page.
         * 
         * @param {number} page - Page number
         */
        goToPage(page) {
            this.pagination.page = page;
            
            if (this.serverSideUrl) {
                // Server-side: load data from server
                this.loadData();
            } else {
                // Client-side: apply pagination locally
                this.applyPagination();
            }
        },
        
        /**
         * Go to next page.
         */
        nextPage() {
            if (this.pagination.page < this.pagination.totalPages) {
                this.pagination.page++;
                
                if (this.serverSideUrl) {
                    this.loadData();
                } else {
                    this.applyPagination();
                }
            }
        },
        
        /**
         * Go to previous page.
         */
        previousPage() {
            if (this.pagination.page > 1) {
                this.pagination.page--;
                
                if (this.serverSideUrl) {
                    this.loadData();
                } else {
                    this.applyPagination();
                }
            }
        },
        
        /**
         * Handle page size change.
         */
        onPageSizeChange() {
            this.pagination.page = 1;
            
            if (this.serverSideUrl) {
                this.loadData();
            } else {
                this.pagination.totalPages = Math.ceil(this.originalData.length / this.pagination.pageSize);
                this.applyPagination();
            }
        },
        
        /**
         * Handle sort change.
         * 
         * @param {string} columnId - Column ID to sort by
         */
        onSort(columnId) {
            if (this.sorting.column === columnId) {
                // Toggle direction
                this.sorting.direction = this.sorting.direction === 'asc' ? 'desc' : 'asc';
            } else {
                // New column
                this.sorting.column = columnId;
                this.sorting.direction = 'asc';
            }
            
            // Apply sorting to data
            this.applySorting();
        },
        
        /**
         * Apply client-side sorting to data.
         */
        applySorting() {
            if (!this.sorting.column || !this.originalData) {
                return;
            }
            
            const column = this.sorting.column;
            const direction = this.sorting.direction;
            
            this.originalData.sort((a, b) => {
                const aVal = a[column];
                const bVal = b[column];
                
                if (aVal === bVal) return 0;
                if (aVal === null || aVal === undefined) return 1;
                if (bVal === null || bVal === undefined) return -1;
                
                const comparison = aVal < bVal ? -1 : 1;
                return direction === 'asc' ? comparison : -comparison;
            });
            
            // Re-apply pagination after sorting
            this.applyPagination();
        },
        
        /**
         * Handle global filter change.
         */
        onGlobalFilterChange() {
            this.pagination.page = 1;
            this.loadData();
        },
        
        /**
         * Check if there are saved filters in session and restore them.
         */
        async checkSavedFilters() {
            try {
                // Use table name (not table ID) to match saveFilters
                const response = await fetch(window.location.origin + '/datatable/get-filters', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        table_id: this.tableName // Use tableName (e.g., "users") not tableId
                    }),
                });
                
                if (response.ok) {
                    const result = await response.json();
                    
                    if (result.success && result.filters && Object.keys(result.filters).length > 0) {
                        console.log('TanStack Table: Restored filters from session', result.filters);
                        
                        // Set global filters variable
                        window.tableFilters_{$tableId} = result.filters;
                        
                        // Dispatch event to update filter modal
                        window.dispatchEvent(new CustomEvent('filters-restored', {
                            detail: { 
                                filters: result.filters, 
                                tableId: '{$tableId}' 
                            }
                        }));
                        
                        // Show active filter count badge
                        const filterCount = Object.keys(result.filters).length;
                        const badge = document.querySelector('[data-filter-count]');
                        if (badge) {
                            badge.textContent = filterCount;
                            badge.classList.remove('hidden');
                        }
                    }
                }
            } catch (error) {
                console.error('TanStack Table: Error checking saved filters', error);
            }
        },
        
        /**
         * Load data (for server-side processing).
         */
        async loadData() {
            // Check if server-side is enabled
            if (!this.serverSideUrl) {
                console.log('TanStack Table: Client-side mode, no AJAX loading');
                return;
            }
            
            console.log('TanStack Table: Loading data from server...');
            this.loading = true;
            this.error = null;
            
            try {
                // Prepare request data
                const requestData = {
                    page: this.pagination.page,
                    pageSize: this.pagination.pageSize,
                    sorting: this.sorting.column ? [{
                        id: this.sorting.column,
                        desc: this.sorting.direction === 'desc'
                    }] : [],
                    globalFilter: this.globalFilter,
                    columnFilters: this.columnFilters,
                    tableName: this.tableName,
                    modelClass: this.modelClass,
                    searchableColumns: this.searchableColumns,
                };
                
                // Add filters from global variable if available
                if (typeof window.tableFilters_{$tableId} !== 'undefined') {
                    requestData.filters = window.tableFilters_{$tableId};
                    console.log('TanStack Table: Filters added to request', requestData.filters);
                }
                
                // Send AJAX request
                const response = await fetch(this.serverSideUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(requestData),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: \${response.status}`);
                }
                
                const result = await response.json();
                
                // Debug: Log received data
                console.log('TanStack Table: Received data from server', {
                    rowCount: result.data?.length,
                    firstRow: result.data?.[0],
                    hasActions: result.data?.[0]?._actions !== undefined,
                    actionsCount: result.data?.[0]?._actions?.length,
                    meta: result.meta
                });
                
                // Update data and pagination
                this.originalData = result.data;
                this.data = result.data;
                this.pagination.totalRows = result.meta.totalRows;
                this.pagination.totalPages = result.meta.totalPages;
                
                console.log('TanStack Table: Data loaded', {
                    rows: this.data.length,
                    total: this.pagination.totalRows,
                    page: this.pagination.page
                });
            } catch (error) {
                console.error('TanStack Table: Error loading data', error);
                this.error = true;
                this.errorMessage = error.message || 'Failed to load data';
            } finally {
                this.loading = false;
            }
        },
        
        /**
         * Retry loading data after error.
         */
        retry() {
            this.error = null;
            this.loadData();
        },
        
        /**
         * Initialize Lucide icons in the table.
         * Replaces placeholder spans with actual Lucide SVG icons.
         */
        initializeLucideIcons() {
            if (typeof lucide === 'undefined') {
                console.warn('TanStack Table: Lucide not loaded');
                return;
            }
            
            const iconElements = this.\$el.querySelectorAll('.lucide-icon');
            let count = 0;
            
            iconElements.forEach(el => {
                const iconName = el.getAttribute('data-icon');
                if (iconName && lucide[iconName]) {
                    try {
                        const svg = lucide[iconName].toSvg({
                            class: 'w-4 h-4',
                            'stroke-width': 2
                        });
                        el.outerHTML = svg;
                        count++;
                    } catch (e) {
                        console.error('Error rendering icon:', iconName, e);
                    }
                }
            });
            
            console.log('TanStack Table: Initialized ' + count + ' Lucide icons');
        }
            };
        });
    };
    
    // Check if Alpine is already loaded
    if (typeof Alpine !== 'undefined') {
        console.log('TanStack Table: Alpine already loaded, registering component immediately');
        registerComponent();
        
        // Initialize Alpine on the table container after component registration
        setTimeout(() => {
            const tableContainer = document.querySelector('[x-data*="tanstackTable_{$tableId}"]');
            if (tableContainer && !tableContainer.__x) {
                console.log('TanStack Table: Initializing Alpine on table container');
                Alpine.initTree(tableContainer);
            }
            
            // Also initialize filter modal if exists
            const filterModal = document.querySelector('[x-data*="filterModal"]');
            if (filterModal && !filterModal.__x) {
                console.log('TanStack Table: Initializing Alpine on filter modal');
                Alpine.initTree(filterModal);
            }
        }, 100);
    } else {
        console.log('TanStack Table: Waiting for Alpine to load...');
        document.addEventListener('alpine:init', () => {
            registerComponent();
            
            // Initialize Alpine on the table container after component registration
            setTimeout(() => {
                const tableContainer = document.querySelector('[x-data*="tanstackTable_{$tableId}"]');
                if (tableContainer && !tableContainer.__x) {
                    console.log('TanStack Table: Initializing Alpine on table container');
                    Alpine.initTree(tableContainer);
                }
                
                // Also initialize filter modal if exists
                const filterModal = document.querySelector('[x-data*="filterModal"]');
                if (filterModal && !filterModal.__x) {
                    console.log('TanStack Table: Initializing Alpine on filter modal');
                    Alpine.initTree(filterModal);
                }
            }, 100);
        });
    }
})();

// Global function for action dropdown toggle
window.toggleActionDropdown = function(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) return;
    
    // Close all other dropdowns first
    document.querySelectorAll('.action-dropdown').forEach(d => {
        if (d.id !== dropdownId) {
            d.style.display = 'none';
        }
    });
    
    // Toggle this dropdown
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
};

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-dropdown-container')) {
        document.querySelectorAll('.action-dropdown').forEach(d => {
            d.style.display = 'none';
        });
    }
});
</script>
JS;
    }

    /**
     * Render CSS styles for TanStack Table.
     * 
     * Loads CSS via Vite in production, falls back to inline CSS in development.
     * Uses CSS variables only - NO PHP variables needed.
     * All theme colors/fonts are injected via Theme Engine.
     * 
     * @param TableBuilder $table The table builder instance
     * @return string The CSS styles
     */
    public function renderStyles(TableBuilder $table): string {
        // Check environment variable for CSS loading mode
        $cssMode = env('TANSTACK_CSS_MODE', 'inline'); // Options: 'inline', 'file', 'vite'
        
        // Debug: Log CSS mode
        \Log::info('TanStack Table: CSS Mode', [
            'mode' => $cssMode,
            'env_value' => env('TANSTACK_CSS_MODE'),
            'config_value' => config('canvastack-table.css_mode'),
        ]);
        
        if ($cssMode === 'file') {
            // Development: Load CSS directly from file (for testing)
            \Log::info('TanStack Table: Loading CSS from file');
            return $this->renderCssFromFile();
        }
        
        if ($cssMode === 'vite') {
            // Production: Load CSS via Vite (after npm run build)
            $manifestPath = base_path('packages/canvastack/canvastack/public/build/manifest.json');
            
            if (file_exists($manifestPath)) {
                try {
                    \Log::info('TanStack Table: Loading CSS via Vite');
                    return \Illuminate\Support\Facades\Vite::useBuildDirectory('packages/canvastack/canvastack/public/build')
                        ->withEntryPoints(['resources/css/tanstack-table.css'])
                        ->toHtml();
                } catch (\Exception $e) {
                    \Log::warning('TanStack Table: Failed to load CSS via Vite', ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Default: Inline CSS
        \Log::info('TanStack Table: Loading CSS inline (default)');
        return $this->renderInlineStyles();
    }
    
    /**
     * Render CSS from file (for development testing).
     * 
     * Loads CSS directly from tanstack-table.css file without Vite build.
     * Useful for testing if CSS file works correctly.
     * 
     * @return string The CSS link tag
     */
    protected function renderCssFromFile(): string {
        // Check published CSS file in public directory
        $cssPath = public_path('vendor/canvastack/css/tanstack-table.css');
        
        if (!file_exists($cssPath)) {
            \Log::warning('TanStack Table: CSS file not found, falling back to inline CSS', [
                'path' => $cssPath,
                'hint' => 'Run: php artisan vendor:publish --tag=canvastack-tanstack-css --force'
            ]);
            return $this->renderInlineStyles();
        }
        
        // Generate URL to CSS file
        $cssUrl = asset('vendor/canvastack/css/tanstack-table.css');
        
        \Log::info('TanStack Table: CSS loaded from file', [
            'path' => $cssPath,
            'url' => $cssUrl
        ]);
        
        return <<<HTML
<!-- TanStack Table CSS (loaded from file) -->
<link rel="stylesheet" href="{$cssUrl}">
HTML;
    }
    
    /**
     * Render inline CSS styles (fallback for development).
     * 
     * @return string The inline CSS
     */
    protected function renderInlineStyles(): string {
        return <<<CSS
<style>
/* TanStack Table Styles - Theme Engine Compliant */
/* Uses CSS variables (var(--cs-color-*)) - NO hardcoded colors */
/* Uses theme fonts via CSS variables - NO hardcoded fonts */

/* Card wrapper for table - matching reference design */
.tanstack-table-card {
    background: var(--cs-color-background-dark, #1a202c);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.tanstack-table-container {
    width: 100%;
    overflow-x: auto;
    font-family: var(--cs-font-sans, Inter, system-ui, sans-serif);
    background: var(--cs-color-background-dark, #1a202c);
    border-radius: 0.5rem;
    border: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.1));
    overflow: hidden;
}

/* Header (search bar) - outside container */
.tanstack-table-header {
    border-bottom: none;
    padding: 0 0 1rem 0;
    background: transparent;
    margin-bottom: 1rem;
}

.tanstack-table {
    width: 100%;
    border-collapse: collapse;
}

.tanstack-table th,
.tanstack-table td {
    padding: 1rem 1rem;
    text-align: left;
    font-family: var(--cs-font-sans, Inter, system-ui, sans-serif);
}

.tanstack-table th {
    background: transparent;
    font-weight: 500;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    border-bottom: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.1));
    height: 3rem;
}

/* Standard table rows - clean and minimal */
.tanstack-table tbody tr {
    border-bottom: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.08));
    transition: background-color 0.2s ease;
}

.tanstack-table tbody tr:hover {
    background: var(--cs-color-hover-dark, rgba(255, 255, 255, 0.05));
}

.tanstack-table tbody tr:last-child {
    border-bottom: none;
}

.tanstack-table tbody td {
    color: var(--cs-color-text-primary-dark, #e2e8f0);
    vertical-align: middle;
    font-size: 0.875rem;
}

/* Badge styles */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.025em;
}

.badge-success {
    background: var(--cs-color-success-bg, rgba(16, 185, 129, 0.2));
    color: var(--cs-color-success-text, #34d399);
}

.badge-warning {
    background: var(--cs-color-warning-bg, rgba(245, 158, 11, 0.2));
    color: var(--cs-color-warning-text, #fbbf24);
}

.badge-error {
    background: var(--cs-color-error-bg, rgba(239, 68, 68, 0.2));
    color: var(--cs-color-error-text, #f87171);
}

.badge-info {
    background: var(--cs-color-info-bg, rgba(59, 130, 246, 0.2));
    color: var(--cs-color-info-text, #60a5fa);
}

/* Avatar/Icon in first column */
.table-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    background: var(--cs-gradient-primary, linear-gradient(135deg, #6366f1, #8b5cf6));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Action buttons - Desktop inline - matching pagination hover */
.action-buttons-wrapper {
    display: flex;
    justify-content: center;
}

.action-buttons-inline {
    display: flex;
    gap: 0.375rem;
}

.action-btn-inline {
    padding: 0.5rem;
    border-radius: 0.375rem;
    background: transparent;
    border: none;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    transition: all 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.action-btn-inline:hover {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.4));
}

.action-btn-inline:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

.action-btn-inline svg {
    width: 1rem;
    height: 1rem;
}

/* Action dropdown - Mobile only */
.action-dropdown-container {
    position: relative;
}

.action-button {
    padding: 0.5rem;
    border-radius: 0.375rem;
    background: transparent;
    border: none;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-button:hover {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.4));
}

.action-button:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

.action-button svg {
    width: 1rem;
    height: 1rem;
}

.action-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 0.5rem;
    width: 12rem;
    background: var(--cs-color-background-secondary-dark, #2d3748);
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    border: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.15));
    z-index: 9999;
    overflow: hidden;
}

.action-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    color: var(--cs-color-text-primary-dark, #e2e8f0);
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-dropdown-item:hover {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateX(4px);
}

.action-dropdown-item:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

/* Primary color usage for interactive elements */
.tanstack-table .btn-primary {
    background: var(--cs-color-primary, #6366f1);
    color: var(--cs-color-text-inverse, #ffffff);
}

.tanstack-table .btn-primary:hover {
    opacity: 0.9;
    transition: opacity 0.2s ease;
}

/* Secondary color usage */
.tanstack-table .btn-secondary {
    background: var(--cs-color-secondary, #8b5cf6);
    color: var(--cs-color-text-inverse, #ffffff);
}

/* Semantic colors */
.tanstack-table .text-success {
    color: var(--cs-color-success, #10b981);
}

.tanstack-table .text-warning {
    color: var(--cs-color-warning, #f59e0b);
}

.tanstack-table .text-error {
    color: var(--cs-color-error, #ef4444);
}

.tanstack-table .text-info {
    color: var(--cs-color-info, #3b82f6);
}

/* Dark mode support */
.dark .tanstack-table-card {
    background: #1a202c;
}

.dark .tanstack-table-container {
    background: #1a202c;
}

.dark .tanstack-table-header {
    background: transparent;
}

.dark .tanstack-table th {
    color: #a0aec0;
}

.dark .tanstack-table tbody tr {
    background: transparent;
}

.dark .tanstack-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.dark .tanstack-table tbody td {
    color: #b5c2d4;
}

.dark .tanstack-pagination {
    background: transparent;
}

/* Sortable column headers */
.tanstack-table th.sortable {
    cursor: pointer;
    user-select: none;
}

.tanstack-table th.sortable:hover {
    color: var(--cs-color-text-primary, #111827);
    transition: color 0.2s ease;
}

.dark .tanstack-table th.sortable:hover {
    color: var(--cs-color-text-primary-dark, #f9fafb);
}

/* Sort indicators with primary color */
.tanstack-table .sort-icon {
    color: var(--cs-color-primary, #6366f1);
}

/* Focus states for accessibility */
.tanstack-table th:focus,
.tanstack-table td:focus,
.tanstack-table button:focus {
    outline: 2px solid var(--cs-color-primary, #6366f1);
    outline-offset: 2px;
}

/* Smooth transitions for theme switching */
.tanstack-table,
.tanstack-table th,
.tanstack-table td,
.tanstack-table button {
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

/* Enhanced transitions during theme switching */
.tanstack-table-container.theme-transitioning,
.tanstack-table-container.theme-transitioning * {
    transition: background-color 0.2s ease, 
                color 0.2s ease, 
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                opacity 0.2s ease !important;
}

.dark .tanstack-table th.sortable:hover {
    color: var(--cs-color-text-primary-dark, #f9fafb);
}

/* Sort indicators */
.sort-indicator {
    display: inline-block;
    margin-left: 0.5rem;
    opacity: 0.5;
}

.sort-indicator.active {
    opacity: 1;
}

/* Loading state - Skeleton loader */
.tanstack-table-skeleton {
    padding: 0;
    width: 100%;
}

.skeleton-table {
    display: flex;
    flex-direction: column;
    width: 100%;
}

/* Skeleton header */
.skeleton-header {
    display: flex;
    gap: 1rem;
    padding: 1rem 1rem;
    border-bottom: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.1));
    background: transparent;
}

.skeleton-header-cell {
    flex: 1;
    position: relative;
    overflow: hidden;
}

.skeleton-shimmer-header {
    height: 0.75rem;
    width: 60%;
    background: var(--cs-color-skeleton-bg, rgba(255, 255, 255, 0.08));
    border-radius: 0.375rem;
    position: relative;
    overflow: hidden;
}

.skeleton-shimmer-header::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--cs-color-skeleton-shine, rgba(255, 255, 255, 0.15)) 50%,
        transparent 100%
    );
    animation: shimmer 1.5s infinite;
}

/* Skeleton rows */
.skeleton-row {
    display: flex;
    gap: 1rem;
    padding: 1rem 1rem;
    border-bottom: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.08));
    min-height: 3.75rem;
}

.skeleton-row:last-child {
    border-bottom: none;
}

.skeleton-cell {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
}

.skeleton-shimmer {
    height: 1rem;
    width: 80%;
    background: var(--cs-color-skeleton-bg, rgba(255, 255, 255, 0.08));
    border-radius: 0.375rem;
    position: relative;
    overflow: hidden;
}

.skeleton-shimmer::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--cs-color-skeleton-shine, rgba(255, 255, 255, 0.15)) 50%,
        transparent 100%
    );
    animation: shimmer 1.5s infinite;
}

/* Dark mode adjustments */
.dark .skeleton-shimmer,
.dark .skeleton-shimmer-header {
    background: var(--cs-color-skeleton-bg, rgba(255, 255, 255, 0.08));
}

.dark .skeleton-shimmer::after,
.dark .skeleton-shimmer-header::after {
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--cs-color-skeleton-shine, rgba(255, 255, 255, 0.15)) 50%,
        transparent 100%
    );
}

@keyframes shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Old loading state - keep for backward compatibility */
.tanstack-table-loading {
    position: relative;
    opacity: 0.6;
    pointer-events: none;
}

.tanstack-table-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2rem;
    height: 2rem;
    margin: -1rem 0 0 -1rem;
    border: 3px solid var(--cs-color-primary, #6366f1);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Empty state */
.tanstack-table-empty {
    padding: 3rem 1rem;
    text-align: center;
    color: var(--cs-color-text-secondary, #6b7280);
}

/* Error state */
.tanstack-table-error {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--cs-color-error, #ef4444);
}

/* Column Pinning (Fixed Columns) */
.tanstack-table-pinned-left,
.tanstack-table-pinned-right {
    position: sticky;
    background: var(--cs-color-background, #ffffff);
    z-index: 10;
}

.dark .tanstack-table-pinned-left,
.dark .tanstack-table-pinned-right {
    background: var(--cs-color-background-dark, #1f2937);
}

.tanstack-table-pinned-left {
    left: 0;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

.tanstack-table-pinned-right {
    right: 0;
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
}

.dark .tanstack-table-pinned-left {
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.3);
}

.dark .tanstack-table-pinned-right {
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.3);
}

/* Pinned column headers */
.tanstack-table thead th.tanstack-table-pinned-left,
.tanstack-table thead th.tanstack-table-pinned-right {
    background: var(--cs-color-background-secondary, #f9fafb);
}

.dark .tanstack-table thead th.tanstack-table-pinned-left,
.dark .tanstack-table thead th.tanstack-table-pinned-right {
    background: var(--cs-color-background-secondary-dark, #1f2937);
}

/* Pagination - Modern design matching reference - outside container */
.tanstack-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0 0 0;
    border-top: none;
    background: transparent;
    margin-top: 1rem;
}

.pagination-info {
    font-size: 0.875rem;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-page-info {
    font-size: 0.875rem;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    margin-right: 0.5rem;
}

/* Base pagination button - NO border, NO background */
.pagination-button {
    min-width: 2.75rem;
    height: 2.75rem;
    padding: 0 0.75rem;
    border: none;
    background: transparent;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

/* Navigation buttons (First, Prev, Next, Last) */
.pagination-nav-button {
    min-width: auto;
    padding: 0 0.75rem;
    gap: 0.5rem;
}

.pagination-nav-button svg {
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
}

.pagination-button-text {
    display: inline-block;
    margin: 0 0.25rem;
    font-size: 0.875rem;
}

/* Hide text on mobile, show on desktop */
@media (max-width: 640px) {
    .pagination-button-text {
        display: none;
    }
    
    .pagination-nav-button {
        min-width: 2.5rem;
        padding: 0;
    }
}

/* Number buttons */
.pagination-number-button {
    min-width: 2.75rem;
}

/* Hover effect - Orange with glass shine effect */
.pagination-button:hover:not(:disabled):not(.pagination-button-active) {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.4));
}

/* Glass shine effect on hover */
.pagination-button:hover:not(:disabled):not(.pagination-button-active)::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Active page button - darker background with border */
.pagination-button-active {
    background: var(--cs-color-background-active-dark, #171C21) !important;
    color: var(--cs-color-text-inverse, #ffffff) !important;
    border: 2px solid var(--cs-color-border-active-dark, #20252e) !important;
    font-weight: 600;
}

.pagination-button-active:hover {
    background: var(--cs-color-background-active-hover-dark, #374151) !important;
    border-color: var(--cs-color-border-active-hover-dark, #5a6678) !important;
    transform: none;
    box-shadow: none;
}

/* Disabled state */
.pagination-button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    background: transparent;
}

.pagination-button:disabled:hover {
    transform: none;
    box-shadow: none;
    background: transparent;
}

/* Page size selector - matching pagination hover */
.pagination-select {
    background: transparent;
    border: none;
    color: var(--cs-color-text-primary-dark, #e2e8f0);
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    margin-left: 0.5rem;
    position: relative;
    overflow: hidden;
}

.pagination-select:hover {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.4));
}

.pagination-select:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--cs-color-shine, rgba(255, 255, 255, 0.3)) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

.pagination-select:focus {
    outline: none;
    border-color: var(--cs-color-accent, #ff8c00);
    box-shadow: 0 0 0 3px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.1));
}

/* Export Buttons - Matching pagination hover effect */
.export-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.export-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: transparent;
    border: none;
    color: var(--cs-color-text-secondary-dark, #a0aec0);
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.export-button:hover {
    background: var(--cs-gradient-accent, linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%));
    color: var(--cs-color-text-inverse, #ffffff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--cs-color-accent-shadow, rgba(255, 140, 0, 0.4));
}

.export-button:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--cs-color-shine, rgba(255, 255, 255, 0.3)) 50%,
        transparent 100%
    );
    animation: shine 0.6s ease-in-out;
}

.export-button svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.export-button-text {
    display: inline-block;
    font-size: 0.875rem;
}

/* Icon-only button (fullscreen) */
.export-button-icon-only {
    padding: 0.5rem;
    min-width: 2.5rem;
    justify-content: center;
}

.export-button-icon-only .export-button-text {
    display: none;
}

/* Tooltip container */
.tooltip-container {
    position: relative;
    display: inline-block;
}

.tooltip-content {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--cs-color-tooltip-bg, #1f2937);
    color: var(--cs-color-tooltip-text, #e5e7eb);
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    box-shadow: 0 4px 6px var(--cs-color-shadow, rgba(0, 0, 0, 0.3));
    pointer-events: none;
}

.tooltip-text {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--cs-color-tooltip-text, #e5e7eb);
}

.tooltip-shortcut {
    font-size: 0.625rem;
    color: var(--cs-color-tooltip-text-secondary, #9ca3af);
}

.tooltip-container:hover .tooltip-content {
    visibility: visible;
    opacity: 1;
}

/* Tooltip arrow */
.tooltip-content::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: var(--cs-color-tooltip-bg, #1f2937);
}

/* Fullscreen mode */
.tanstack-table-card.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    border-radius: 0;
    padding: 2rem;
    overflow: auto;
}

.tanstack-table-card.fullscreen .tanstack-table-container {
    max-height: calc(100vh - 12rem);
    overflow: auto;
}

/* Ensure filter modal wrapper is above fullscreen table */
[dusk="filter-modal"] {
    z-index: 10001 !important;
}

/* Filter modal backdrop - must be above fullscreen table */
[dusk="filter-modal"] > .fixed.inset-0 {
    z-index: 10000 !important;
}

/* Filter modal content - must be above backdrop */
[dusk="filter-modal"] > .relative {
    z-index: 10001 !important;
    position: relative;
}

/* Hide text on mobile, show on desktop */
@media (max-width: 768px) {
    .export-button-text {
        display: none;
    }
    
    .export-button {
        padding: 0.5rem;
        min-width: 2.5rem;
        justify-content: center;
    }
}

/* Row Selection */
.tanstack-table-select-column {
    width: 3rem;
    text-align: center;
}

.tanstack-table-checkbox {
    width: 1.125rem;
    height: 1.125rem;
    cursor: pointer;
    accent-color: var(--cs-color-primary, #6366f1);
}

.tanstack-table-row-selected {
    background: var(--cs-color-primary-light, #eef2ff) !important;
}

.dark .tanstack-table-row-selected {
    background: var(--cs-color-primary-dark, #312e81) !important;
}

.tanstack-table-row-selected:hover {
    background: var(--cs-color-primary-light-hover, #e0e7ff) !important;
}

.dark .tanstack-table-row-selected:hover {
    background: var(--cs-color-primary-dark-hover, #3730a3) !important;
}

/* Pagination - Modern design like reference */
.tanstack-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1rem;
    border-top: 1px solid var(--cs-color-border-dark, rgba(255, 255, 255, 0.05));
}

.dark .tanstack-pagination {
    border-top-color: var(--cs-color-border-dark, rgba(255, 255, 255, 0.05));
}

.pagination-info {
    color: var(--cs-color-text-secondary-dark, #9ca3af);
    font-size: 0.875rem;
}

.pagination-controls {
    display: flex;
    gap: 0.375rem;
    align-items: center;
}

.pagination-button {
    padding: 0.5rem 0.875rem;
    border: none;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--cs-color-text-secondary-dark, #d1d5db);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    font-weight: 500;
    min-width: 2.5rem;
    text-align: center;
}

.pagination-button:hover:not(:disabled) {
    background: var(--cs-color-hover-dark, rgba(255, 255, 255, 0.1));
    color: var(--cs-color-text-inverse, #ffffff);
}

.pagination-button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* Active page button */
.pagination-button.bg-primary {
    background: var(--cs-gradient-primary, linear-gradient(135deg, #6366f1, #8b5cf6));
    color: var(--cs-color-text-inverse, #ffffff);
}

.pagination-button.bg-primary:hover {
    background: var(--cs-gradient-primary-hover, linear-gradient(135deg, #5558e3, #7c4de8));
}

/* Page size select */
.pagination-button select,
select.pagination-button {
    padding-right: 2rem;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23d1d5db'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1rem;
    appearance: none;
}

/* Bulk Action Buttons */
.tanstack-bulk-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.tanstack-bulk-action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.tanstack-bulk-action-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tanstack-bulk-action-button:active {
    transform: translateY(0);
}

.tanstack-bulk-action-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Bulk Action Confirmation Modal */
.tanstack-confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.tanstack-confirm-modal-content {
    background: var(--cs-color-background, #ffffff);
    border-radius: 0.75rem;
    padding: 1.5rem;
    max-width: 28rem;
    width: 90%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dark .tanstack-confirm-modal-content {
    background: var(--cs-color-background-dark, #1f2937);
}

.tanstack-confirm-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--cs-color-text-primary, #111827);
    margin-bottom: 0.75rem;
}

.dark .tanstack-confirm-modal-title {
    color: var(--cs-color-text-primary-dark, #f9fafb);
}

.tanstack-confirm-modal-message {
    color: var(--cs-color-text-secondary, #6b7280);
    margin-bottom: 1.5rem;
}

.dark .tanstack-confirm-modal-message {
    color: var(--cs-color-text-secondary-dark, #9ca3af);
}

.tanstack-confirm-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.tanstack-confirm-modal-button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.tanstack-confirm-modal-button-cancel {
    background: var(--cs-color-background-secondary, #f3f4f6);
    color: var(--cs-color-text-primary, #111827);
}

.tanstack-confirm-modal-button-cancel:hover {
    background: var(--cs-color-background-hover, #e5e7eb);
}

.dark .tanstack-confirm-modal-button-cancel {
    background: var(--cs-color-background-secondary-dark, #374151);
    color: var(--cs-color-text-primary-dark, #f9fafb);
}

.dark .tanstack-confirm-modal-button-cancel:hover {
    background: var(--cs-color-background-hover-dark, #4b5563);
}

.tanstack-confirm-modal-button-confirm {
    background: var(--cs-color-error, #ef4444);
    color: #ffffff;
}

.tanstack-confirm-modal-button-confirm:hover {
    background: var(--cs-color-error-dark, #dc2626);
}

/* Responsive Styles */
.tanstack-table-responsive-wrapper {
    width: 100%;
}

/* Mobile Card View */
.tanstack-mobile-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 0.5rem;
}

.tanstack-mobile-card {
    background: var(--cs-color-background, #ffffff);
    border: 1px solid var(--cs-color-border, #e5e7eb);
    border-radius: 0.75rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.dark .tanstack-mobile-card {
    background: var(--cs-color-background-dark, #1f2937);
    border-color: var(--cs-color-border-dark, #374151);
}

.tanstack-mobile-card-selected {
    border-color: var(--cs-color-primary, #6366f1);
    background: var(--cs-color-primary-light, #eef2ff);
}

.dark .tanstack-mobile-card-selected {
    border-color: var(--cs-color-primary, #6366f1);
    background: var(--cs-color-primary-dark, #312e81);
}

.tanstack-mobile-card-select {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 0.75rem;
}

.tanstack-mobile-card-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.tanstack-mobile-card-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.tanstack-mobile-card-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--cs-color-text-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.dark .tanstack-mobile-card-label {
    color: var(--cs-color-text-secondary-dark, #9ca3af);
}

.tanstack-mobile-card-value {
    font-size: 0.875rem;
    color: var(--cs-color-text-primary, #111827);
}

.dark .tanstack-mobile-card-value {
    color: var(--cs-color-text-primary-dark, #f9fafb);
}

/* Touch-Friendly Interactions */
@media (hover: none) and (pointer: coarse) {
    /* Increase tap target sizes for touch devices */
    .tanstack-table-checkbox {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .pagination-button {
        padding: 0.75rem 1.25rem;
        min-height: 44px; /* iOS minimum tap target */
    }
    
    .tanstack-bulk-action-button {
        padding: 0.625rem 1rem;
        min-height: 44px;
    }
    
    .tanstack-table th.sortable {
        padding: 1rem;
    }
    
    /* Larger touch targets for mobile cards */
    .tanstack-mobile-card {
        padding: 1.25rem;
    }
    
    .tanstack-mobile-card-select input {
        width: 1.5rem;
        height: 1.5rem;
    }
}

/* Smooth scrolling for touch devices */
.tanstack-table-desktop {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Prevent text selection during touch interactions */
.tanstack-table th.sortable,
.pagination-button,
.tanstack-bulk-action-button {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
}

/* Mobile Pagination Adaptations */
@media (max-width: 767px) {
    .tanstack-pagination {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .pagination-info {
        text-align: center;
        order: -1;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination-button {
        flex: 1;
        min-width: 44px;
        text-align: center;
    }
    
    /* Stack bulk actions vertically on mobile */
    .tanstack-bulk-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .tanstack-bulk-action-button {
        width: 100%;
        justify-content: center;
    }
}

/* Tablet breakpoint (768px - 1023px) */
@media (min-width: 768px) and (max-width: 1023px) {
    .tanstack-table th,
    .tanstack-table td {
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
    }
}

/* Landscape mobile optimization */
@media (max-width: 767px) and (orientation: landscape) {
    .tanstack-mobile-card {
        padding: 0.75rem;
    }
    
    .tanstack-mobile-card-content {
        gap: 0.5rem;
    }
}

/* RTL (Right-to-Left) Support */
/* Requirement 51.11: Support RTL layouts for RTL locales (ar, he, fa, ur) */
[dir="rtl"] .tanstack-table th,
[dir="rtl"] .tanstack-table td {
    text-align: right;
}

[dir="rtl"] .sort-indicator {
    margin-left: 0;
    margin-right: 0.5rem;
}

[dir="rtl"] .tanstack-pagination {
    flex-direction: row-reverse;
}

[dir="rtl"] .pagination-controls {
    flex-direction: row-reverse;
}

[dir="rtl"] .tanstack-bulk-actions {
    flex-direction: row-reverse;
}

[dir="rtl"] .tanstack-bulk-action-button {
    flex-direction: row-reverse;
}

[dir="rtl"] .tanstack-confirm-modal-actions {
    flex-direction: row-reverse;
}

/* RTL Pinned Columns */
[dir="rtl"] .tanstack-table-pinned-left {
    left: auto;
    right: 0;
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
}

[dir="rtl"] .tanstack-table-pinned-right {
    right: auto;
    left: 0;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

[dir="rtl"].dark .tanstack-table-pinned-left {
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.3);
}

[dir="rtl"].dark .tanstack-table-pinned-right {
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.3);
}

/* RTL Mobile Card View */
[dir="rtl"] .tanstack-mobile-card-select {
    justify-content: flex-start;
}

[dir="rtl"] .tanstack-mobile-card-field {
    text-align: right;
}

/* RTL Touch-Friendly Interactions */
@media (hover: none) and (pointer: coarse) {
    [dir="rtl"] .tanstack-table th.sortable {
        text-align: right;
    }
}
</style>
CSS;
    }

    /**
     * Inject theme CSS into the table rendering.
     * 
     * Uses Theme facade to get compiled CSS and inject it into the page.
     * This ensures theme colors, fonts, and variables are available.
     * Includes locale-specific fonts and RTL CSS when needed.
     * 
     * Validates: Requirements 51.7, 51.8, 51.11, 51.12
     * 
     * @return string The theme CSS injection HTML
     */
    protected function injectThemeCSS(): string
    {
        // Get localized theme CSS (includes RTL and locale-specific fonts)
        // Requirement 51.11: Integrate with ThemeLocaleIntegration for RTL support
        $themeCss = $this->themeLocaleIntegration->getLocalizedThemeCss();
        
        // Get theme name and metadata
        $themeName = Theme::current()->getName();
        $themeVersion = Theme::current()->getVersion();
        
        // Get locale safely - check if translator is bound first
        $locale = 'en';
        if (function_exists('app')) {
            $app = app();
            if (method_exists($app, 'bound') && $app->bound('translator')) {
                $translator = $app->make('translator');
                if (method_exists($translator, 'getLocale')) {
                    $locale = $translator->getLocale();
                }
            }
        }
        
        return <<<HTML
<!-- Theme Engine Injection for TanStack Table -->
<!-- Theme: {$themeName} v{$themeVersion} | Locale: {$locale} -->
<style id="tanstack-table-theme-injection">
{$themeCss}
</style>
HTML;
    }

    /**
     * Get theme color for programmatic use.
     * 
     * Uses Theme facade to get specific theme colors.
     * Provides fallback values for safety.
     * 
     * @param string $colorName The color name (primary, secondary, success, etc.)
     * @param string $fallback Fallback color value
     * @return string The color value
     */
    protected function getThemeColor(string $colorName, string $fallback = '#6366f1'): string
    {
        $colors = Theme::colors();
        return $colors[$colorName] ?? $fallback;
    }

    /**
     * Get theme font for programmatic use.
     * 
     * Uses Theme facade to get specific theme fonts.
     * Provides fallback values for safety.
     * 
     * @param string $fontName The font name (sans, mono)
     * @param string $fallback Fallback font value
     * @return string The font value
     */
    protected function getThemeFont(string $fontName, string $fallback = 'Inter, system-ui, sans-serif'): string
    {
        $fonts = Theme::fonts();
        return $fonts[$fontName] ?? $fallback;
    }

    /**
     * Render the table container with Alpine.js directives.
     * 
     * @param TableBuilder $table The table builder instance
     * @param array $alpineData Alpine.js data configuration
     * @return string The HTML
     */
    protected function renderTableContainer(TableBuilder $table, array $alpineData): string
    {
        $tableId = $table->getTableId() ?? 'tanstack-table-' . uniqid();
        
        // Get HTML attributes for RTL support (Requirement 51.11, 52.5)
        $htmlAttributes = $this->themeLocaleIntegration->getHtmlAttributes();
        $bodyClasses = $this->themeLocaleIntegration->getBodyClasses();
        
        // Add card wrapper with Alpine.js data
        $html = '<div class="tanstack-table-card" ';
        $html .= 'dir="' . htmlspecialchars($htmlAttributes['dir']) . '" ';
        $html .= 'x-data="tanstackTable_' . $tableId . '" ';
        $html .= 'x-init="init()" ';
        $html .= '@keydown.window.alt.t.prevent="toggleFullscreen()" ';
        $html .= '@keydown.window.escape="if ($el.classList.contains(\'fullscreen\')) { $event.preventDefault(); toggleFullscreen(); }">';
        
        // Render table header (search bar) - OUTSIDE container
        $html .= $this->renderTableHeader($table);
        
        // Table container - ONLY contains the actual table
        $html .= '<div class="tanstack-table-container ' . htmlspecialchars($bodyClasses) . '">';
        
        // Render table
        $html .= $this->renderTable($table);
        
        $html .= '</div>'; // Close tanstack-table-container
        
        // Render pagination - OUTSIDE container
        $html .= $this->renderPagination($table);
        
        // Render confirmation modal
        $html .= $this->renderConfirmationModal();
        
        $html .= '</div>'; // Close tanstack-table-card
        
        return $html;
    }

    /**
     * Render table header with search and filters.
     * 
     * @param TableBuilder $table The table builder instance
     * @return string The HTML
     */
    protected function renderTableHeader(TableBuilder $table): string
    {
        $config = $table->getConfiguration();
        $selectionEnabled = $config->selectable ?? false;
        $hasBulkActions = $table->hasBulkActions();
        $tableId = $table->getTableId() ?? 'tanstack-table-' . uniqid();
        
        $html = '<div class="tanstack-table-header">';
        
        // Selection info and bulk actions (if enabled)
        if ($selectionEnabled) {
            $html .= '<div x-show="selectedCount > 0" class="mb-4 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">';
            
            // Selection count
            $html .= '<span class="text-sm font-medium text-blue-700 dark:text-blue-300">';
            $html .= '<span x-text="selectedCount"></span> ';
            // Use single quotes inside x-text expression
            $html .= '<span x-text="selectedCount === 1 ? \'' . addslashes(__('canvastack::components.table.row_selected')) . '\' : \'' . addslashes(__('canvastack::components.table.rows_selected')) . '\'"></span>';
            $html .= '</span>';
            
            // Bulk actions and clear button
            $html .= '<div class="flex items-center gap-2">';
            
            // Render bulk action buttons
            if ($hasBulkActions) {
                $html .= $this->renderBulkActionButtons($table);
            }
            
            // Clear selection button
            $html .= '<button @click="clearSelection()" ';
            $html .= 'class="text-sm text-blue-700 dark:text-blue-300 hover:underline">';
            $html .= __('canvastack::components.table.clear_selection');
            $html .= '</button>';
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Search input
        $html .= '<div class="flex items-center gap-4">';
        
        // Left: Page size selector
        $html .= '<div class="flex items-center gap-2">';
        $html .= '<label class="text-sm text-gray-400">' . __('canvastack::components.table.per_page') . '</label>';
        $html .= '<select x-model="pagination.pageSize" ';
        $html .= '@change="onPageSizeChange" ';
        $html .= 'class="pagination-select">';
        $html .= '<option value="10">10</option>';
        $html .= '<option value="25">25</option>';
        $html .= '<option value="50">50</option>';
        $html .= '<option value="100">100</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Middle: Search input with icon
        $html .= '<div class="relative flex-1">';
        $html .= '<i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>';
        $html .= '<input type="text" ';
        $html .= 'x-model="globalFilter" ';
        $html .= '@input.debounce.300ms="onGlobalFilterChange" ';
        $html .= 'placeholder="' . __('canvastack::components.table.search') . '" ';
        $html .= 'class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:border-transparent outline-none transition" />';
        $html .= '</div>';
        
        // Right side: Export buttons and Filter button
        $html .= '<div class="flex items-center gap-2">';
        
        // Export buttons (if enabled)
        $buttons = $table->getButtons();
        if (!empty($buttons)) {
            $html .= $this->renderExportButtons($buttons);
        }
        
        // Filter button (if filters exist)
        if ($table->hasFilters()) {
            $activeFilterCount = count(array_filter($table->getActiveFilters(), function($value) {
                return $value !== '' && $value !== null;
            }));
            
            $primaryColor = function_exists('theme_color') ? theme_color('primary') : '#6366f1';
            
            $html .= '<button ';
            $html .= 'id="' . $tableId . '_filter_btn" ';
            $html .= 'onclick="document.querySelector(\'[x-data*=filterModal]\').dispatchEvent(new CustomEvent(\'open-filter-modal\')); return false;" ';
            $html .= 'class="px-4 py-2 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg flex items-center gap-2 relative" ';
            $html .= 'style="box-shadow: 0 10px 15px -3px ' . $primaryColor . '40, 0 4px 6px -4px ' . $primaryColor . '40">';
            $html .= '<i data-lucide="filter" class="w-4 h-4"></i>';
            $html .= '<span>' . htmlspecialchars(__('canvastack::components.table.filters')) . '</span>';
            
            if ($activeFilterCount > 0) {
                $html .= '<span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg">';
                $html .= $activeFilterCount;
                $html .= '</span>';
            }
            
            $html .= '</button>';
        }
        
        $html .= '</div>'; // Close right side
        $html .= '</div>'; // Close flex container
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render export buttons (Excel, CSV, PDF, Print, Copy).
     * 
     * @param array $buttons Array of button names
     * @return string The HTML
     */
    protected function renderExportButtons(array $buttons): string
    {
        if (empty($buttons)) {
            return '';
        }
        
        $html = '<div class="export-buttons flex items-center gap-2">';
        
        // Heroicons (inline SVG) for export buttons
        $icons = [
            'excel' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
            'csv' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
            'pdf' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
            'print' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>',
            'copy' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>',
            'fullscreen' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>'
        ];
        
        $buttonConfig = [
            'excel' => [
                'label' => __('canvastack::components.table.export_excel'),
                'title' => 'Export to Excel',
                'class' => 'export-btn-excel',
                'action' => 'exportData(\'excel\')'
            ],
            'csv' => [
                'label' => 'CSV',
                'title' => 'Export to CSV',
                'class' => 'export-btn-csv',
                'action' => 'exportData(\'csv\')'
            ],
            'pdf' => [
                'label' => 'PDF',
                'title' => 'Export to PDF',
                'class' => 'export-btn-pdf',
                'action' => 'exportData(\'pdf\')'
            ],
            'print' => [
                'label' => __('canvastack::components.table.print'),
                'title' => 'Print',
                'class' => 'export-btn-print',
                'action' => 'exportData(\'print\')'
            ],
            'copy' => [
                'label' => 'Copy',
                'title' => 'Copy to clipboard',
                'class' => 'export-btn-copy',
                'action' => 'exportData(\'copy\')'
            ],
            'fullscreen' => [
                'label' => '',
                'title' => 'Toggle fullscreen table view',
                'class' => 'export-btn-fullscreen',
                'action' => 'toggleFullscreen()'
            ]
        ];
        
        foreach ($buttons as $button) {
            if (!isset($buttonConfig[$button]) || !isset($icons[$button])) {
                continue;
            }
            
            $config = $buttonConfig[$button];
            
            // Fullscreen button - icon only with tooltip
            if ($button === 'fullscreen') {
                $html .= '<div class="tooltip-container">';
                $html .= '<button ';
                $html .= '@click="' . $config['action'] . '" ';
                $html .= 'class="export-button export-button-icon-only ' . $config['class'] . '">';
                $html .= $icons[$button];
                $html .= '</button>';
                $html .= '<div class="tooltip-content">';
                $html .= '<div class="tooltip-text">' . $config['title'] . '</div>';
                $html .= '<div class="tooltip-shortcut">Shortcut: Alt+T to enter, Escape to exit</div>';
                $html .= '</div>';
                $html .= '</div>';
            } else {
                // Other buttons - with label
                $html .= '<button ';
                $html .= '@click="' . $config['action'] . '" ';
                $html .= 'class="export-button ' . $config['class'] . '" ';
                $html .= 'title="' . $config['title'] . '">';
                $html .= $icons[$button];
                $html .= '<span class="export-button-text">' . $config['label'] . '</span>';
                $html .= '</button>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render filter modal component.
     * 
     * Uses the same filter modal component as DataTables for consistency.
     *
     * @param TableBuilder $table The table builder instance
     * @param string $tableId The table ID
     * @return string The HTML
     */
    protected function renderFilterModal(TableBuilder $table, string $tableId): string
    {
        if (!$table->hasFilters()) {
            return '';
        }
        
        $filters = $table->getFilterManager()->getFilters();
        $activeFilters = $table->getActiveFilters();
        $config = $table->getConfiguration();
        $tableName = $config->tableName ?? 'tanstack_table';
        
        // Debug: Log active filters
        \Log::info('TanStackRenderer: renderFilterModal', [
            'tableName' => $tableName,
            'tableId' => $tableId,
            'activeFilters' => $activeFilters,
            'hasFilters' => !empty($activeFilters),
        ]);
        
        // Transform Filter objects to array format expected by modal component
        $transformedFilters = [];
        foreach ($filters as $column => $filter) {
            $transformedFilters[] = [
                'column' => $filter->getColumn(),
                'type' => $filter->getType(),
                'label' => $filter->getLabel(),
                'options' => $filter->getOptions(),
                'relate' => $filter->getRelate(),
                'bidirectional' => $filter->isBidirectional(),
                'autoSubmit' => $filter->shouldAutoSubmit(),
                'loading' => false,
            ];
        }
        
        // Count active filters
        $activeFilterCount = count(array_filter($activeFilters, function($value) {
            return $value !== '' && $value !== null;
        }));
        
        // Use Blade component to render the modal
        try {
            $modalHtml = view('canvastack::components.table.filter-modal', [
                'filters' => $transformedFilters,
                'activeFilters' => $activeFilters,
                'tableName' => $tableName,
                'tableId' => $tableId,
                'activeFilterCount' => $activeFilterCount,
                'showButton' => false, // Don't show button, we have it in search bar
                'config' => (array) $config,
            ])->render();
            
            
            return $modalHtml;
        } catch (\Exception $e) {
            return '<!-- Filter modal component not available: ' . $e->getMessage() . ' -->';
        }
    }

    /**
     * Render bulk action buttons.
     * 
     * @param TableBuilder $table The table builder instance
     * @return string The HTML
     */
    protected function renderBulkActionButtons(TableBuilder $table): string
    {
        $bulkActions = $table->getBulkActions();
        
        if (empty($bulkActions)) {
            return '';
        }
        
        $html = '';
        
        foreach ($bulkActions as $name => $action) {
            $label = $action['label'];
            $url = $action['url'];
            $method = $action['method'] ?? 'POST';
            $icon = $action['icon'] ?? null;
            $confirm = $action['confirm'] ?? null;
            
            // Determine button color based on action type
            $buttonClass = 'px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ';
            
            if ($method === 'DELETE' || str_contains(strtolower($name), 'delete')) {
                $buttonClass .= 'bg-red-600 hover:bg-red-700 text-white';
            } elseif (str_contains(strtolower($name), 'export')) {
                $buttonClass .= 'bg-green-600 hover:bg-green-700 text-white';
            } else {
                $buttonClass .= 'bg-blue-600 hover:bg-blue-700 text-white';
            }
            
            $html .= '<button ';
            $html .= '@click="executeBulkAction(\'' . $name . '\', \'' . $url . '\', \'' . $method . '\', ' . ($confirm ? '\'' . addslashes($confirm) . '\'' : 'null') . ')" ';
            $html .= 'class="' . $buttonClass . '">';
            
            // Icon (if provided)
            if ($icon) {
                $html .= '<i data-lucide="' . $icon . '" class="w-4 h-4 inline-block mr-1"></i>';
            }
            
            $html .= $label;
            $html .= '</button>';
        }
        
        return $html;
    }

    /**
     * Render the main table.
     * 
     * @param TableBuilder $table The table builder instance
     * @return string The HTML
     */
    protected function renderTable(TableBuilder $table): string
    {
        $config = $table->getConfiguration();
        $selectionEnabled = $config->selectable ?? false;
        
        // Responsive wrapper with horizontal scroll
        $html = '<div x-show="!loading && !error" class="tanstack-table-responsive-wrapper">';
        
        // Desktop table view (hidden on mobile < 768px)
        $html .= '<div class="tanstack-table-desktop hidden md:block overflow-x-auto">';
        $html .= '<table class="tanstack-table">';
        
        // Table head
        $html .= '<thead>';
        $html .= '<tr>';
        
        // Selection checkbox column (if enabled)
        if ($selectionEnabled) {
            $html .= '<th class="tanstack-table-select-column">';
            $html .= '<input type="checkbox" ';
            $html .= 'x-model="selectAll" ';
            $html .= '@change="onSelectAllChange" ';
            $html .= ':indeterminate.prop="isIndeterminate" ';
            $html .= 'class="tanstack-table-checkbox" ';
            $html .= 'aria-label="' . __('canvastack::components.table.select_all') . '" />';
            $html .= '</th>';
        }
        
        $html .= '<template x-for="(column, index) in columns" :key="column.id">';
        $html .= '<th :class="getColumnClass(column, index)" ';
        $html .= '@click="column.enableSorting && onSort(column.id)">';
        // Debug: Show both column.header and column.label
        $html .= '<span x-text="column.header || column.label || column.id"></span>';
        $html .= '<span x-show="column.enableSorting" class="sort-indicator" ';
        $html .= ':class="{ active: sorting.column === column.id }">';
        $html .= '<span x-show="sorting.column === column.id && sorting.direction === \'asc\'">↑</span>';
        $html .= '<span x-show="sorting.column === column.id && sorting.direction === \'desc\'">↓</span>';
        $html .= '<span x-show="sorting.column !== column.id">↕</span>';
        $html .= '</span>';
        $html .= '</th>';
        $html .= '</template>';
        $html .= '</tr>';
        $html .= '</thead>';
        
        // Table body
        $html .= '<tbody>';
        $html .= '<template x-for="row in data" :key="row.id">';
        $html .= '<tr :class="{ \'tanstack-table-row-selected\': isRowSelected(row.id) }">';
        
        // Selection checkbox cell (if enabled)
        if ($selectionEnabled) {
            $html .= '<td class="tanstack-table-select-column">';
            $html .= '<input type="checkbox" ';
            $html .= ':checked="isRowSelected(row.id)" ';
            $html .= '@change="onRowSelectChange(row.id)" ';
            $html .= 'class="tanstack-table-checkbox" ';
            $html .= ':aria-label="\'Select row \' + row.id" />';
            $html .= '</td>';
        }
        
        $html .= '<template x-for="(column, index) in columns" :key="column.id">';
        $html .= '<td :class="getColumnClass(column, index)" x-html="renderCell(row, column)"></td>';
        $html .= '</template>';
        $html .= '</tr>';
        $html .= '</template>';
        $html .= '</tbody>';
        
        $html .= '</table>';
        $html .= '</div>'; // Close tanstack-table-desktop
        
        // Mobile card view (visible only on mobile < 768px)
        $html .= $this->renderMobileCardView($table, $selectionEnabled);
        
        $html .= '</div>'; // Close tanstack-table-responsive-wrapper
        
        // Empty state
        $html .= '<div x-show="!loading && !error && data.length === 0" class="tanstack-table-empty">';
        $html .= '<p>' . __('canvastack::components.table.no_data') . '</p>';
        $html .= '</div>';
        
        // Loading state - Skeleton loader
        $html .= '<div x-show="loading" class="tanstack-table-skeleton">';
        
        // Skeleton table structure
        $html .= '<div class="skeleton-table">';
        
        // Skeleton header
        $html .= '<div class="skeleton-header">';
        $columnCount = count($table->getColumns());
        for ($j = 0; $j < $columnCount; $j++) {
            $html .= '<div class="skeleton-header-cell">';
            $html .= '<div class="skeleton-shimmer skeleton-shimmer-header"></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Skeleton rows (show 5 rows)
        for ($i = 0; $i < 5; $i++) {
            $html .= '<div class="skeleton-row">';
            
            // Skeleton cells (match number of columns)
            for ($j = 0; $j < $columnCount; $j++) {
                $html .= '<div class="skeleton-cell">';
                $html .= '<div class="skeleton-shimmer"></div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close skeleton-table
        $html .= '</div>'; // Close tanstack-table-skeleton
        
        // Error state
        $html .= '<div x-show="error" class="tanstack-table-error">';
        $html .= '<p x-text="errorMessage"></p>';
        $html .= '<button @click="retry()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg">';
        $html .= __('canvastack::components.table.retry');
        $html .= '</button>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render pagination controls.
     * 
     * @param TableBuilder $table The table builder instance
     * @return string The HTML
     */
    protected function renderPagination(TableBuilder $table): string
    {
        $html = '<div class="tanstack-pagination">';
        
        // Left side: Pagination info
        $html .= '<div class="pagination-info">';
        $html .= '<span x-text="paginationText()"></span>';
        $html .= '</div>';
        
        // Right side: Pagination controls
        $html .= '<div class="pagination-controls">';
        
        // Page info (Page X from Y)
        $html .= '<div class="pagination-page-info">';
        $html .= '<span>' . __('canvastack::components.table.page') . ' </span>';
        $html .= '<span x-text="pagination.page"></span>';
        $html .= '<span> ' . __('canvastack::components.table.from') . ' </span>';
        $html .= '<span x-text="pagination.totalPages"></span>';
        $html .= '</div>';
        
        // First page button with icon
        $html .= '<button @click="goToPage(1)" ';
        $html .= ':disabled="pagination.page === 1" ';
        $html .= 'class="pagination-button pagination-nav-button" ';
        $html .= 'title="' . __('canvastack::components.table.first') . '">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>';
        $html .= '</svg>';
        $html .= '<span class="pagination-button-text">' . __('canvastack::components.table.first') . '</span>';
        $html .= '</button>';
        
        // Previous page button with icon
        $html .= '<button @click="previousPage()" ';
        $html .= ':disabled="pagination.page === 1" ';
        $html .= 'class="pagination-button pagination-nav-button" ';
        $html .= 'title="' . __('canvastack::components.table.previous') . '">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>';
        $html .= '</svg>';
        $html .= '<span class="pagination-button-text">' . __('canvastack::components.table.previous') . '</span>';
        $html .= '</button>';
        
        // Page numbers (show max 5 pages around current page)
        $html .= '<template x-for="pageNum in getPageNumbers()" :key="pageNum">';
        $html .= '<button @click="goToPage(pageNum)" ';
        $html .= ':class="{ \'pagination-button-active\': pagination.page === pageNum }" ';
        $html .= 'class="pagination-button pagination-number-button">';
        $html .= '<span x-text="pageNum"></span>';
        $html .= '</button>';
        $html .= '</template>';
        
        // Next page button with icon
        $html .= '<button @click="nextPage()" ';
        $html .= ':disabled="pagination.page === pagination.totalPages" ';
        $html .= 'class="pagination-button pagination-nav-button" ';
        $html .= 'title="' . __('canvastack::components.table.next') . '">';
        $html .= '<span class="pagination-button-text">' . __('canvastack::components.table.next') . '</span>';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>';
        $html .= '</svg>';
        $html .= '</button>';
        
        // Last page button with icon
        $html .= '<button @click="goToPage(pagination.totalPages)" ';
        $html .= ':disabled="pagination.page === pagination.totalPages" ';
        $html .= 'class="pagination-button pagination-nav-button" ';
        $html .= 'title="' . __('canvastack::components.table.last') . '">';
        $html .= '<span class="pagination-button-text">' . __('canvastack::components.table.last') . '</span>';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>';
        $html .= '</svg>';
        $html .= '</button>';
        
        $html .= '</div>'; // Close pagination-controls
        $html .= '</div>'; // Close tanstack-pagination
        
        return $html;
    }

    /**
     * Render confirmation modal for bulk actions.
     * 
     * @return string The HTML
     */
    protected function renderConfirmationModal(): string
    {
        $html = '<div x-show="showConfirmModal" ';
        $html .= 'x-cloak ';
        $html .= '@click.self="cancelModalAction()" ';
        $html .= 'class="tanstack-confirm-modal">';
        
        $html .= '<div class="tanstack-confirm-modal-content">';
        
        // Modal title
        $html .= '<h3 class="tanstack-confirm-modal-title" x-text="confirmModalTitle"></h3>';
        
        // Modal message
        $html .= '<p class="tanstack-confirm-modal-message" x-text="confirmModalMessage"></p>';
        
        // Modal actions
        $html .= '<div class="tanstack-confirm-modal-actions">';
        
        // Cancel button
        $html .= '<button @click="cancelModalAction()" ';
        $html .= 'class="tanstack-confirm-modal-button tanstack-confirm-modal-button-cancel">';
        $html .= __('canvastack::components.table.cancel');
        $html .= '</button>';
        
        // Confirm button
        $html .= '<button @click="confirmModalActionHandler()" ';
        $html .= 'class="tanstack-confirm-modal-button tanstack-confirm-modal-button-confirm">';
        $html .= __('canvastack::components.table.confirm');
        $html .= '</button>';
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render mobile card view for responsive tables.
     * 
     * @param TableBuilder $table The table builder instance
     * @param bool $selectionEnabled Whether row selection is enabled
     * @return string The HTML
     */
    protected function renderMobileCardView(TableBuilder $table, bool $selectionEnabled): string
    {
        $html = '<div class="tanstack-table-mobile block md:hidden">';
        
        // Mobile cards container
        $html .= '<div class="tanstack-mobile-cards">';
        
        // Loop through each row as a card
        $html .= '<template x-for="row in data" :key="row.id">';
        $html .= '<div class="tanstack-mobile-card" :class="{ \'tanstack-mobile-card-selected\': isRowSelected(row.id) }">';
        
        // Selection checkbox (if enabled)
        if ($selectionEnabled) {
            $html .= '<div class="tanstack-mobile-card-select">';
            $html .= '<input type="checkbox" ';
            $html .= ':checked="isRowSelected(row.id)" ';
            $html .= '@change="onRowSelectChange(row.id)" ';
            $html .= 'class="tanstack-table-checkbox" ';
            $html .= ':aria-label="\'Select row \' + row.id" />';
            $html .= '</div>';
        }
        
        // Card content
        $html .= '<div class="tanstack-mobile-card-content">';
        
        // Loop through columns
        $html .= '<template x-for="(column, index) in columns" :key="column.id">';
        $html .= '<div class="tanstack-mobile-card-field" x-show="!column.hidden">';
        $html .= '<div class="tanstack-mobile-card-label" x-text="column.header || column.label || column.id"></div>';
        $html .= '<div class="tanstack-mobile-card-value" x-html="renderCell(row, column)"></div>';
        $html .= '</div>';
        $html .= '</template>';
        
        $html .= '</div>'; // Close tanstack-mobile-card-content
        $html .= '</div>'; // Close tanstack-mobile-card
        $html .= '</template>';
        
        $html .= '</div>'; // Close tanstack-mobile-cards
        $html .= '</div>'; // Close tanstack-table-mobile
        
        return $html;
    }
}

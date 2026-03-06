/**
 * TanStack Table Alpine.js Component
 * 
 * This component provides reactive state management for TanStack Table v8 integration.
 * It handles sorting, pagination, filtering, searching, and all table interactions.
 * 
 * @param {Object} config - Initial configuration from server
 * @returns {Object} Alpine.js component data and methods
 */
export function tanstackTable(config) {
    return {
        // ==================== State ====================
        
        // Table instance (TanStack Table)
        table: null,
        
        // Virtual scrolling
        virtualizer: null,
        
        // Data
        data: config.data || [],
        originalData: config.data || [],
        
        // Loading states
        loading: false,
        error: false,
        errorMessage: '',
        
        // Search
        globalFilter: '',
        
        // Filters
        filters: {},
        showFilters: false,
        activeFiltersCount: 0,
        
        // Sorting
        sorting: config.sorting || [],
        
        // Pagination
        pagination: {
            pageIndex: config.pageIndex || 0,
            pageSize: config.pageSize || 10,
        },
        
        // Column visibility
        columnVisibility: config.columnVisibility || {},
        
        // Column sizing (for resizing)
        columnSizing: {},
        columnSizingInfo: {},
        
        // Row selection
        rowSelection: {},
        
        // Configuration
        config: config,
        
        // Translations
        translations: config.translations || {},
        
        // Server-side mode
        serverSide: config.serverSide || false,
        
        // Total rows (for server-side pagination)
        totalRows: config.totalRows || 0,
        
        // ==================== Initialization ====================
        
        /**
         * Initialize the component
         */
        init() {
            // Check if TanStack Table is loaded
            if (!window.TanStackTable) {
                console.error('TanStack Table library not loaded. Please include @tanstack/table-core in your page.');
                this.error = true;
                this.errorMessage = 'Table library not loaded';
                return;
            }
            
            // Load sort state from URL if persistence is enabled
            this.loadSortStateFromUrl();
            
            // Load page size from session storage if persistence is enabled
            this.loadPageSizeFromSession();
            
            // Load column widths from session storage if persistence is enabled
            this.loadColumnWidthsFromSession();
            
            // Build columns
            const columns = this.buildColumns();
            
            // Import TanStack Table functions
            const {
                createTable,
                getCoreRowModel,
                getSortedRowModel,
                getFilteredRowModel,
                getPaginationRowModel,
            } = window.TanStackTable;
            
            // Create TanStack Table instance
            this.table = createTable({
                data: this.data,
                columns: columns,
                
                // State
                state: {
                    sorting: this.sorting,
                    globalFilter: this.globalFilter,
                    pagination: this.pagination,
                    columnVisibility: this.columnVisibility,
                    rowSelection: this.rowSelection,
                    columnSizing: this.columnSizing,
                    columnSizingInfo: this.columnSizingInfo,
                },
                
                // Event handlers
                onSortingChange: updater => this.onSortingChange(updater),
                onGlobalFilterChange: updater => this.onGlobalFilterChange(updater),
                onPaginationChange: updater => this.onPaginationChange(updater),
                onColumnVisibilityChange: updater => this.onColumnVisibilityChange(updater),
                onRowSelectionChange: updater => this.onRowSelectionChange(updater),
                onColumnSizingChange: updater => this.onColumnSizingChange(updater),
                onColumnSizingInfoChange: updater => this.onColumnSizingInfoChange(updater),
                
                // Row models
                getCoreRowModel: getCoreRowModel(),
                getSortedRowModel: getSortedRowModel(),
                getFilteredRowModel: getFilteredRowModel(),
                getPaginationRowModel: getPaginationRowModel(),
                
                // Manual pagination for server-side
                manualPagination: this.serverSide,
                manualSorting: this.serverSide,
                manualFiltering: this.serverSide,
                
                // Row count for server-side
                rowCount: this.serverSide ? this.totalRows : undefined,
                
                // Global filter function
                globalFilterFn: 'includesString',
                
                // Enable features
                enableSorting: this.config.sortable !== false,
                enableFiltering: this.config.searchable !== false,
                enableColumnResizing: this.config.columnResizing?.enabled !== false,
                columnResizeMode: this.config.columnResizing?.mode || 'onChange',
                enableRowSelection: this.config.selectable || false,
                
                // Multi-column sorting
                enableMultiSort: this.config.multiSort !== false,
                maxMultiSortColCount: this.config.maxMultiSortColCount || 3,
                isMultiSortEvent: (e) => e.shiftKey, // Hold Shift for multi-column sort
            });
            
            // Initialize Lucide icons
            if (window.lucide) {
                this.$nextTick(() => {
                    window.lucide.createIcons();
                });
            }
            
            // Initialize virtual scrolling
            this.$nextTick(() => {
                this.initVirtualScrolling();
            });
            
            // Initialize lazy loading
            this.$nextTick(() => {
                this.initLazyLoading();
            });
            
            // Load initial data if server-side
            if (this.serverSide) {
                this.fetchData();
            }
        },
        
        /**
         * Build column definitions for TanStack Table
         */
        buildColumns() {
            const columns = [];
            
            // Row selection column
            if (this.config.selectable) {
                columns.push({
                    id: 'select',
                    header: ({ table }) => {
                        return `
                            <input
                                type="checkbox"
                                ${table.getIsAllRowsSelected() ? 'checked' : ''}
                                ${table.getIsSomeRowsSelected() ? 'indeterminate' : ''}
                                class="rounded border-gray-300 dark:border-gray-600"
                            />
                        `;
                    },
                    cell: ({ row }) => {
                        return `
                            <input
                                type="checkbox"
                                ${row.getIsSelected() ? 'checked' : ''}
                                class="rounded border-gray-300 dark:border-gray-600"
                            />
                        `;
                    },
                    enableSorting: false,
                    enableResizing: false,
                });
            }
            
            // Data columns
            if (this.config.columns) {
                this.config.columns.forEach(column => {
                    columns.push({
                        accessorKey: column.field,
                        id: column.field,
                        header: column.label,
                        enableSorting: column.sortable !== false,
                        enableResizing: column.resizable !== false,
                        size: column.width || this.config.columnResizing?.defaultWidth || 150,
                        minSize: column.minWidth || this.config.columnResizing?.minWidth || 50,
                        maxSize: column.maxWidth || this.config.columnResizing?.maxWidth || 500,
                        
                        // Custom cell renderer
                        cell: ({ getValue, row }) => {
                            const value = getValue();
                            
                            // Use custom renderer if provided
                            if (column.renderer) {
                                return column.renderer(value, row.original);
                            }
                            
                            // Default rendering
                            return this.formatCellValue(value, column);
                        },
                        
                        // Column metadata
                        meta: {
                            align: column.align || 'left',
                            className: column.className || '',
                            backgroundColor: column.backgroundColor || null,
                        },
                    });
                });
            }
            
            // Actions column
            if (this.config.actions && this.config.actions.length > 0) {
                columns.push({
                    id: 'actions',
                    header: this.translations.actions || 'Actions',
                    cell: ({ row }) => this.buildActions(row.original),
                    enableSorting: false,
                    enableResizing: false,
                });
            }
            
            return columns;
        },
        
        // ==================== Event Handlers ====================
        
        /**
         * Handle sorting change
         */
        onSortingChange(updater) {
            this.sorting = typeof updater === 'function' 
                ? updater(this.sorting) 
                : updater;
            
            // Persist sort state in URL
            this.persistSortState();
            
            if (this.serverSide) {
                this.fetchData();
            }
        },
        
        /**
         * Persist sort state in URL parameters
         */
        persistSortState() {
            if (!this.config.persistState) {
                return;
            }
            
            const url = new URL(window.location);
            
            if (this.sorting.length > 0) {
                // Encode sorting as: column1:asc,column2:desc
                const sortParam = this.sorting
                    .map(sort => `${sort.id}:${sort.desc ? 'desc' : 'asc'}`)
                    .join(',');
                url.searchParams.set('sort', sortParam);
            } else {
                url.searchParams.delete('sort');
            }
            
            // Update URL without page reload
            window.history.replaceState({}, '', url);
            
            // Also persist to session storage
            this.persistSortStateToSession();
        },
        
        /**
         * Persist sort state to session storage
         */
        persistSortStateToSession() {
            if (!this.config.persistState) {
                return;
            }
            
            const stateKey = this.config.stateKey || `tanstack-table-${this.config.tableId || 'default'}`;
            
            try {
                const state = JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                state.sorting = this.sorting;
                state.globalFilter = this.globalFilter; // Persist search state
                sessionStorage.setItem(stateKey, JSON.stringify(state));
            } catch (err) {
                console.warn('Failed to persist sort state to session storage:', err);
            }
        },
        
        /**
         * Load sort state from URL parameters
         */
        loadSortStateFromUrl() {
            if (!this.config.persistState) {
                return;
            }
            
            const url = new URL(window.location);
            const sortParam = url.searchParams.get('sort');
            
            if (sortParam) {
                // Parse sorting from: column1:asc,column2:desc
                this.sorting = sortParam.split(',').map(item => {
                    const [id, direction] = item.split(':');
                    return {
                        id: id,
                        desc: direction === 'desc'
                    };
                });
            } else {
                // Try loading from session storage
                this.loadSortStateFromSession();
            }
        },
        
        /**
         * Load sort state from session storage
         */
        loadSortStateFromSession() {
            if (!this.config.persistState) {
                return;
            }
            
            const stateKey = this.config.stateKey || `tanstack-table-${this.config.tableId || 'default'}`;
            
            try {
                const state = JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                if (state.sorting && Array.isArray(state.sorting)) {
                    this.sorting = state.sorting;
                }
                // Load search state
                if (state.globalFilter !== undefined) {
                    this.globalFilter = state.globalFilter;
                }
            } catch (err) {
                console.warn('Failed to load sort state from session storage:', err);
            }
        },
        
        /**
         * Handle global filter change with debouncing
         * 
         * This method is called when the search input changes.
         * It updates the global filter state and triggers data fetching
         * for server-side mode or client-side filtering.
         * 
         * Debouncing is handled by Alpine.js @input.debounce.300ms directive
         */
        onGlobalFilterChange(updater) {
            this.globalFilter = typeof updater === 'function'
                ? updater(this.globalFilter)
                : updater;
            
            // Reset to first page when searching
            this.pagination.pageIndex = 0;
            
            if (this.serverSide) {
                this.fetchData();
            } else {
                // Client-side filtering is handled by TanStack Table automatically
                // The table will re-render with filtered data
                this.table.setGlobalFilter(this.globalFilter);
            }
        },
        
        /**
         * Clear search input
         * 
         * Resets the global filter and refreshes the table
         */
        clearSearch() {
            this.globalFilter = '';
            this.onGlobalFilterChange('');
        },
        
        /**
         * Set column-specific filter
         * 
         * Allows filtering on individual columns.
         * This is an optional feature that can be enabled per column.
         * 
         * @param {string} columnId - Column identifier
         * @param {string} value - Filter value
         */
        setColumnFilter(columnId, value) {
            if (!this.table) return;
            
            const column = this.table.getColumn(columnId);
            if (column) {
                column.setFilterValue(value);
                
                // Reset to first page when filtering
                this.pagination.pageIndex = 0;
                
                if (this.serverSide) {
                    this.fetchData();
                }
            }
        },
        
        /**
         * Clear column-specific filter
         * 
         * @param {string} columnId - Column identifier
         */
        clearColumnFilter(columnId) {
            this.setColumnFilter(columnId, '');
        },
        
        /**
         * Get column filter value
         * 
         * @param {string} columnId - Column identifier
         * @returns {string} Current filter value
         */
        getColumnFilterValue(columnId) {
            if (!this.table) return '';
            
            const column = this.table.getColumn(columnId);
            return column ? (column.getFilterValue() || '') : '';
        },
        
        /**
         * Handle pagination change
         */
        onPaginationChange(updater) {
            this.pagination = typeof updater === 'function'
                ? updater(this.pagination)
                : updater;
            
            // Persist pagination state to session storage
            this.persistPaginationStateToSession();
            
            if (this.serverSide) {
                this.fetchData();
            }
        },
        
        /**
         * Persist pagination state to session storage
         */
        persistPaginationStateToSession() {
            const stateKey = this.config.stateKey || `tanstack-table-${this.config.tableId || 'default'}`;
            
            try {
                const state = JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                state.pagination = this.pagination;
                sessionStorage.setItem(stateKey, JSON.stringify(state));
            } catch (err) {
                console.warn('Failed to persist pagination state to session storage:', err);
            }
        },
        
        /**
         * Handle column visibility change
         */
        onColumnVisibilityChange(updater) {
            this.columnVisibility = typeof updater === 'function'
                ? updater(this.columnVisibility)
                : updater;
        },
        
        /**
         * Handle row selection change
         */
        onRowSelectionChange(updater) {
            this.rowSelection = typeof updater === 'function'
                ? updater(this.rowSelection)
                : updater;
        },
        
        /**
         * Handle column sizing change
         * 
         * This is called when columns are resized by the user.
         * It persists the column widths to session storage.
         */
        onColumnSizingChange(updater) {
            this.columnSizing = typeof updater === 'function'
                ? updater(this.columnSizing)
                : updater;
            
            // Persist column widths to session storage
            if (this.config.columnResizing?.persistWidths !== false) {
                this.persistColumnWidthsToSession();
            }
        },
        
        /**
         * Handle column sizing info change
         * 
         * This tracks the current resize operation state.
         */
        onColumnSizingInfoChange(updater) {
            this.columnSizingInfo = typeof updater === 'function'
                ? updater(this.columnSizingInfo)
                : updater;
        },
        
        /**
         * Handle filter change
         */
        onFilterChange() {
            // Count active filters
            this.activeFiltersCount = Object.values(this.filters)
                .filter(value => value !== '' && value !== null && value !== undefined)
                .length;
            
            if (this.serverSide) {
                this.fetchData();
            } else {
                // Client-side filtering
                this.applyClientSideFilters();
            }
        },
        
        /**
         * Clear all filters
         */
        clearFilters() {
            this.filters = {};
            this.activeFiltersCount = 0;
            
            if (this.serverSide) {
                this.fetchData();
            } else {
                this.data = [...this.originalData];
            }
        },
        
        // ==================== Data Fetching (Server-Side) ====================
        
        /**
         * Fetch data from server
         */
        async fetchData() {
            if (!this.serverSide || !this.config.ajaxUrl) {
                return;
            }
            
            this.loading = true;
            this.error = false;
            
            try {
                const response = await fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        // Pagination
                        page: this.pagination.pageIndex + 1,
                        pageSize: this.pagination.pageSize,
                        
                        // Sorting
                        sorting: this.sorting,
                        
                        // Searching
                        globalFilter: this.globalFilter,
                        
                        // Filtering
                        filters: this.filters,
                    }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                // Update data
                this.data = result.data || [];
                this.totalRows = result.total || 0;
                
                // Update table
                this.table.setOptions(prev => ({
                    ...prev,
                    data: this.data,
                    rowCount: this.totalRows,
                }));
                
            } catch (err) {
                this.error = true;
                this.errorMessage = err.message || this.translations.error || 'An error occurred';
                console.error('TanStack Table fetch error:', err);
            } finally {
                this.loading = false;
                
                // Update virtualizer after data changes
                this.$nextTick(() => {
                    this.updateVirtualizer();
                });
                
                // Reinitialize Lucide icons
                if (window.lucide) {
                    this.$nextTick(() => {
                        window.lucide.createIcons();
                    });
                }
            }
        },
        
        /**
         * Refresh data
         */
        refreshData() {
            if (this.serverSide) {
                this.fetchData();
            } else {
                // Reset to original data
                this.data = [...this.originalData];
                this.globalFilter = '';
                this.filters = {};
                this.activeFiltersCount = 0;
            }
        },
        
        // ==================== Client-Side Filtering ====================
        
        /**
         * Apply client-side filters
         */
        applyClientSideFilters() {
            let filtered = [...this.originalData];
            
            // Apply each filter
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) {
                    filtered = filtered.filter(row => {
                        const rowValue = row[key];
                        
                        // Handle different filter types
                        if (typeof value === 'string') {
                            return String(rowValue).toLowerCase().includes(value.toLowerCase());
                        }
                        
                        return rowValue === value;
                    });
                }
            });
            
            this.data = filtered;
        },
        
        // ==================== Rendering Helpers ====================
        
        /**
         * Render a cell value
         * 
         * This is the main cell rendering method that handles:
         * - Custom renderers
         * - Default formatting
         * - Type-specific rendering
         * 
         * @param {Object} cell - TanStack Table cell object
         * @returns {string} Rendered HTML
         */
        renderCell(cell) {
            const value = cell.getValue();
            const column = cell.column.columnDef;
            
            // Use custom renderer if provided
            if (column.cell && typeof column.cell === 'function') {
                return column.cell({ getValue: () => value, row: cell.row });
            }
            
            return this.formatCellValue(value, column);
        },
        
        /**
         * Format cell value based on type
         * 
         * Handles automatic formatting for:
         * - Dates (ISO format, Date objects)
         * - Booleans (badge display)
         * - Numbers (locale formatting)
         * - Null/undefined (empty string)
         * - Strings (escaped HTML)
         * 
         * @param {*} value - Cell value
         * @param {Object} column - Column definition
         * @returns {string} Formatted HTML
         */
        formatCellValue(value, column) {
            if (value === null || value === undefined) {
                return '<span class="text-gray-400 dark:text-gray-600">—</span>';
            }
            
            // Handle dates
            if (value instanceof Date || (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value))) {
                const date = new Date(value);
                return `<span class="text-gray-700 dark:text-gray-300">${date.toLocaleDateString()}</span>`;
            }
            
            // Handle booleans
            if (typeof value === 'boolean') {
                return value 
                    ? '<span class="badge badge-success"><i data-lucide="check" class="w-3 h-3"></i> Yes</span>'
                    : '<span class="badge badge-error"><i data-lucide="x" class="w-3 h-3"></i> No</span>';
            }
            
            // Handle numbers
            if (typeof value === 'number') {
                return `<span class="font-mono text-gray-900 dark:text-gray-100">${value.toLocaleString()}</span>`;
            }
            
            // Handle arrays (display count)
            if (Array.isArray(value)) {
                return `<span class="badge badge-info">${value.length} items</span>`;
            }
            
            // Handle objects (display JSON)
            if (typeof value === 'object') {
                return `<span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{...}</span>`;
            }
            
            // Default: return as escaped string
            return this.escapeHtml(String(value));
        },
        
        /**
         * Escape HTML to prevent XSS
         * 
         * @param {string} text - Text to escape
         * @returns {string} Escaped HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Build action buttons for a row
         * 
         * Creates action buttons with:
         * - Icons (Lucide)
         * - Conditional visibility
         * - CSRF protection
         * - Confirmation dialogs
         * - Multiple HTTP methods
         * 
         * @param {Object} row - Row data
         * @returns {string} Action buttons HTML
         */
        buildActions(row) {
            if (!this.config.actions || this.config.actions.length === 0) {
                return '';
            }
            
            const buttons = this.config.actions.map(action => {
                // Check condition if provided
                if (action.condition && !action.condition(row)) {
                    return '';
                }
                
                // Replace :id placeholder in URL
                const url = action.url.replace(':id', row.id || row[this.config.primaryKey || 'id']);
                
                // Build button HTML
                const method = action.method || 'GET';
                const confirm = action.confirm ? `onclick="return confirm('${action.confirm}')"` : '';
                const icon = action.icon ? `<i data-lucide="${action.icon}" class="w-4 h-4"></i>` : '';
                const label = action.showLabel ? `<span class="ml-1">${action.label}</span>` : '';
                
                if (method === 'GET') {
                    return `
                        <a href="${url}" 
                           class="btn btn-sm ${action.class || 'btn-primary'}"
                           title="${action.label}">
                            ${icon}${label}
                        </a>
                    `;
                } else {
                    return `
                        <form method="POST" action="${url}" style="display: inline;" ${confirm}>
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                            <input type="hidden" name="_method" value="${method}">
                            <button type="submit" 
                                    class="btn btn-sm ${action.class || 'btn-primary'}"
                                    title="${action.label}">
                                ${icon}${label}
                            </button>
                        </form>
                    `;
                }
            }).filter(Boolean).join(' ');
            
            return `<div class="flex items-center gap-2 justify-end">${buttons}</div>`;
        },
        
        /**
         * Get header CSS class
         * 
         * Applies styling based on:
         * - Column alignment (left, center, right)
         * - Custom classes
         * - Sortable state
         * - Resizable state
         * 
         * @param {Object} header - TanStack Table header object
         * @returns {string} CSS classes
         */
        getHeaderClass(header) {
            const meta = header.column.columnDef.meta || {};
            const classes = ['px-4', 'py-3', 'font-semibold', 'text-sm'];
            
            // Alignment
            if (meta.align === 'center') {
                classes.push('text-center');
            } else if (meta.align === 'right') {
                classes.push('text-right');
            } else {
                classes.push('text-left');
            }
            
            // Sortable cursor
            if (header.column.getCanSort()) {
                classes.push('cursor-pointer', 'select-none', 'hover:bg-gray-100', 'dark:hover:bg-gray-800');
            }
            
            // Resizable
            if (header.column.getCanResize()) {
                classes.push('relative');
            }
            
            // Custom classes
            if (meta.className) {
                classes.push(meta.className);
            }
            
            return classes.join(' ');
        },
        
        /**
         * Get row CSS class
         * 
         * Applies styling based on:
         * - Row selection state
         * - Hover effects
         * - Striping (optional)
         * - Custom row conditions
         * 
         * @param {Object} row - TanStack Table row object
         * @returns {string} CSS classes
         */
        getRowClass(row) {
            const classes = ['border-b', 'border-gray-200', 'dark:border-gray-800'];
            
            // Hover effect
            classes.push('hover:bg-gray-50', 'dark:hover:bg-gray-900');
            
            // Selection state
            if (row.getIsSelected()) {
                classes.push('bg-indigo-50', 'dark:bg-indigo-900/20');
            }
            
            // Striping (optional)
            if (this.config.striped && row.index % 2 === 1) {
                classes.push('bg-gray-50', 'dark:bg-gray-900/50');
            }
            
            // Custom row class function
            if (this.config.rowClass && typeof this.config.rowClass === 'function') {
                const customClass = this.config.rowClass(row.original);
                if (customClass) {
                    classes.push(customClass);
                }
            }
            
            return classes.join(' ');
        },
        
        /**
         * Get cell CSS class
         * 
         * Applies styling based on:
         * - Column alignment
         * - Background color
         * - Custom classes
         * - Cell conditions
         * 
         * @param {Object} cell - TanStack Table cell object
         * @returns {string} CSS classes
         */
        getCellClass(cell) {
            const meta = cell.column.columnDef.meta || {};
            const classes = ['px-4', 'py-3', 'text-sm'];
            
            // Alignment
            if (meta.align === 'center') {
                classes.push('text-center');
            } else if (meta.align === 'right') {
                classes.push('text-right');
            } else {
                classes.push('text-left');
            }
            
            // Background color
            if (meta.backgroundColor) {
                // Use inline style for dynamic colors
                classes.push(`bg-[${meta.backgroundColor}]`);
            }
            
            // Custom classes
            if (meta.className) {
                classes.push(meta.className);
            }
            
            // Cell condition function
            if (meta.cellClass && typeof meta.cellClass === 'function') {
                const customClass = meta.cellClass(cell.getValue(), cell.row.original);
                if (customClass) {
                    classes.push(customClass);
                }
            }
            
            return classes.join(' ');
        },
        
        /**
         * Get sort icon based on sort state
         * 
         * Returns appropriate Lucide icon name:
         * - 'arrow-up' for ascending
         * - 'arrow-down' for descending
         * - 'arrow-up-down' for unsorted
         * 
         * @param {string|false} sortState - Sort state ('asc', 'desc', or false)
         * @returns {string} Lucide icon name
         */
        getSortIcon(sortState) {
            if (sortState === 'asc') return 'arrow-up';
            if (sortState === 'desc') return 'arrow-down';
            return 'arrow-up-down';
        },
        
        /**
         * Get sort icon color class
         * 
         * @param {string|false} sortState - Sort state
         * @returns {string} CSS color class
         */
        getSortIconClass(sortState) {
            if (sortState === 'asc' || sortState === 'desc') {
                return 'text-indigo-600 dark:text-indigo-400';
            }
            return 'text-gray-400 dark:text-gray-600';
        },
        
        /**
         * Clear all sorting
         */
        clearSorting() {
            this.sorting = [];
            this.onSortingChange([]);
        },
        
        /**
         * Get active sort columns
         * 
         * @returns {Array} Array of sorted column info
         */
        getActiveSortColumns() {
            return this.sorting.map((sort, index) => ({
                column: sort.id,
                direction: sort.desc ? 'desc' : 'asc',
                index: index + 1
            }));
        },
        
        // ==================== Pagination Helpers ====================
        
        /**
         * Get pagination text
         * 
         * Displays: "Showing X to Y of Z entries"
         * Supports translations via config.translations.showing
         * 
         * @returns {string} Pagination info text
         */
        paginationText() {
            const { pageIndex, pageSize } = this.pagination;
            const totalRows = this.serverSide ? this.totalRows : this.table.getFilteredRowModel().rows.length;
            
            if (totalRows === 0) {
                return this.translations.no_data || 'No data available';
            }
            
            const start = pageIndex * pageSize + 1;
            const end = Math.min((pageIndex + 1) * pageSize, totalRows);
            
            return this.translations.showing
                ? this.translations.showing
                    .replace(':start', start)
                    .replace(':end', end)
                    .replace(':total', totalRows)
                : `Showing ${start} to ${end} of ${totalRows} entries`;
        },
        
        /**
         * Get page numbers for pagination
         * 
         * Generates smart pagination with ellipsis:
         * - Always shows first and last page
         * - Shows current page and neighbors
         * - Uses '...' for gaps
         * 
         * Example: [1, '...', 5, 6, 7, '...', 20]
         * 
         * @returns {Array} Array of page numbers and ellipsis
         */
        getPageNumbers() {
            const pageCount = this.table.getPageCount();
            const currentPage = this.pagination.pageIndex + 1;
            const pages = [];
            
            if (pageCount <= 7) {
                // Show all pages if 7 or fewer
                for (let i = 1; i <= pageCount; i++) {
                    pages.push(i);
                }
            } else {
                // Always show first page
                pages.push(1);
                
                // Calculate range around current page
                const startPage = Math.max(2, currentPage - 1);
                const endPage = Math.min(pageCount - 1, currentPage + 1);
                
                // Add ellipsis before if needed
                if (startPage > 2) {
                    pages.push('...');
                }
                
                // Add pages around current page
                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }
                
                // Add ellipsis after if needed
                if (endPage < pageCount - 1) {
                    pages.push('...');
                }
                
                // Always show last page
                pages.push(pageCount);
            }
            
            return pages;
        },
        
        /**
         * Check if can go to previous page
         * 
         * @returns {boolean}
         */
        canPreviousPage() {
            return this.table.getCanPreviousPage();
        },
        
        /**
         * Check if can go to next page
         * 
         * @returns {boolean}
         */
        canNextPage() {
            return this.table.getCanNextPage();
        },
        
        /**
         * Go to first page
         */
        firstPage() {
            this.table.setPageIndex(0);
        },
        
        /**
         * Go to previous page
         */
        previousPage() {
            this.table.previousPage();
        },
        
        /**
         * Go to next page
         */
        nextPage() {
            this.table.nextPage();
        },
        
        /**
         * Go to last page
         */
        lastPage() {
            this.table.setPageIndex(this.table.getPageCount() - 1);
        },
        
        /**
         * Go to specific page
         * 
         * @param {number} page - Page number (1-indexed)
         */
        gotoPage(page) {
            this.table.setPageIndex(page - 1);
        },
        
        /**
         * Change page size
         * 
         * @param {number} size - New page size
         */
        changePageSize(size) {
            this.table.setPageSize(Number(size));
            
            // Persist page size to session storage
            this.persistPageSizeToSession(Number(size));
        },
        
        /**
         * Persist page size to session storage
         * 
         * @param {number} size - Page size to persist
         */
        persistPageSizeToSession(size) {
            const stateKey = this.config.stateKey || `tanstack-table-${this.config.tableId || 'default'}`;
            
            try {
                const state = JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                state.pageSize = size;
                sessionStorage.setItem(stateKey, JSON.stringify(state));
            } catch (err) {
                console.warn('Failed to persist page size to session storage:', err);
            }
        },
        
        /**
         * Load page size from session storage
         */
        loadPageSizeFromSession() {
            const stateKey = this.config.stateKey || `tanstack-table-${this.config.tableId || 'default'}`;
            
            try {
                const state = JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                
                // Load complete pagination state if available
                if (state.pagination && typeof state.pagination === 'object') {
                    this.pagination = {
                        ...this.pagination,
                        ...state.pagination
                    };
                }
                // Fallback: load just page size for backward compatibility
                else if (state.pageSize && typeof state.pageSize === 'number') {
                    this.pagination.pageSize = state.pageSize;
                }
            } catch (err) {
                console.warn('Failed to load page size from session storage:', err);
            }
        },
        
        /**
         * Persist column widths to session storage
         * 
         * Saves the current column widths so they persist across page reloads.
         */
        persistColumnWidthsToSession() {
            if (!this.config.columnResizing?.persistWidths) {
                return;
            }
            
            const storageKey = this.config.columnResizing?.storageKey || 
                `tanstack-table-column-widths-${this.config.tableId || 'default'}`;
            
            try {
                sessionStorage.setItem(storageKey, JSON.stringify(this.columnSizing));
            } catch (err) {
                console.warn('Failed to persist column widths to session storage:', err);
            }
        },
        
        /**
         * Load column widths from session storage
         * 
         * Restores previously saved column widths on page load.
         */
        loadColumnWidthsFromSession() {
            if (!this.config.columnResizing?.persistWidths) {
                return;
            }
            
            const storageKey = this.config.columnResizing?.storageKey || 
                `tanstack-table-column-widths-${this.config.tableId || 'default'}`;
            
            try {
                const savedWidths = sessionStorage.getItem(storageKey);
                if (savedWidths) {
                    this.columnSizing = JSON.parse(savedWidths);
                }
            } catch (err) {
                console.warn('Failed to load column widths from session storage:', err);
            }
        },
        
        /**
         * Reset column widths to defaults
         * 
         * Clears all saved column widths and resets to default sizes.
         */
        resetColumnWidths() {
            this.columnSizing = {};
            
            // Clear from session storage
            if (this.config.columnResizing?.persistWidths) {
                const storageKey = this.config.columnResizing?.storageKey || 
                    `tanstack-table-column-widths-${this.config.tableId || 'default'}`;
                
                try {
                    sessionStorage.removeItem(storageKey);
                } catch (err) {
                    console.warn('Failed to clear column widths from session storage:', err);
                }
            }
            
            // Update table
            if (this.table) {
                this.table.resetColumnSizing();
            }
        },
        
        // ==================== Export ====================
        
        /**
         * Export data
         */
        async exportData(type) {
            if (!this.config.exportUrl) {
                console.error('Export URL not configured');
                return;
            }
            
            try {
                const response = await fetch(this.config.exportUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        type: type,
                        filters: this.filters,
                        sorting: this.sorting,
                        globalFilter: this.globalFilter,
                    }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Download file
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `export-${Date.now()}.${type}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
            } catch (err) {
                console.error('Export error:', err);
                alert(this.translations.export_error || 'Export failed');
            }
        },
    };
}

// Register as Alpine.js component
if (window.Alpine) {
    window.Alpine.data('tanstackTable', tanstackTable);
}

/**
 * Screen Size Detection for Responsive Table Views
 * 
 * This component detects screen size changes and automatically switches
 * between table view (desktop) and card view (mobile) based on breakpoints.
 * 
 * Features:
 * - Automatic detection on page load
 * - Real-time detection on window resize
 * - Debounced resize handler for performance
 * - Configurable breakpoint (default: 768px)
 * - Smooth transitions between views
 * 
 * @param {Object} config - Configuration options
 * @returns {Object} Alpine.js component data and methods
 */
export function responsiveTableView(config = {}) {
    return {
        // ==================== State ====================
        
        // Current view mode: 'table' or 'card'
        viewMode: 'table',
        
        // Screen width breakpoint (default: 768px for mobile)
        breakpoint: config.breakpoint || 768,
        
        // Current screen width
        screenWidth: window.innerWidth,
        
        // Resize debounce timer
        resizeTimer: null,
        
        // Debounce delay in milliseconds
        debounceDelay: config.debounceDelay || 150,
        
        // ==================== Initialization ====================
        
        /**
         * Initialize the component
         */
        init() {
            // Detect initial screen size
            this.detectScreenSize();
            
            // Add resize event listener
            window.addEventListener('resize', () => this.handleResize());
            
            // Log initial state in development mode
            if (config.debug) {
                console.log('Responsive Table View initialized:', {
                    viewMode: this.viewMode,
                    screenWidth: this.screenWidth,
                    breakpoint: this.breakpoint
                });
            }
        },
        
        /**
         * Cleanup on component destroy
         */
        destroy() {
            // Remove resize event listener
            window.removeEventListener('resize', () => this.handleResize());
            
            // Clear any pending resize timer
            if (this.resizeTimer) {
                clearTimeout(this.resizeTimer);
            }
        },
        
        // ==================== Screen Size Detection ====================
        
        /**
         * Detect screen size and set appropriate view mode
         * 
         * Rules:
         * - Screen width < breakpoint → Card view (mobile)
         * - Screen width >= breakpoint → Table view (desktop)
         */
        detectScreenSize() {
            this.screenWidth = window.innerWidth;
            
            const previousViewMode = this.viewMode;
            
            // Determine view mode based on screen width
            if (this.screenWidth < this.breakpoint) {
                this.viewMode = 'card';
            } else {
                this.viewMode = 'table';
            }
            
            // Log view mode change in development mode
            if (config.debug && previousViewMode !== this.viewMode) {
                console.log('View mode changed:', {
                    from: previousViewMode,
                    to: this.viewMode,
                    screenWidth: this.screenWidth,
                    breakpoint: this.breakpoint
                });
            }
            
            // Trigger custom event for view mode change
            if (previousViewMode !== this.viewMode) {
                this.dispatchViewModeChangeEvent(previousViewMode, this.viewMode);
            }
        },
        
        /**
         * Handle window resize with debouncing
         * 
         * Debouncing prevents excessive function calls during resize,
         * improving performance and reducing unnecessary re-renders.
         */
        handleResize() {
            // Clear existing timer
            if (this.resizeTimer) {
                clearTimeout(this.resizeTimer);
            }
            
            // Set new timer
            this.resizeTimer = setTimeout(() => {
                this.detectScreenSize();
                
                // Reinitialize Lucide icons after view change
                if (window.lucide) {
                    this.$nextTick(() => {
                        window.lucide.createIcons();
                    });
                }
            }, this.debounceDelay);
        },
        
        /**
         * Dispatch custom event when view mode changes
         * 
         * This allows other components to react to view mode changes.
         * 
         * @param {string} from - Previous view mode
         * @param {string} to - New view mode
         */
        dispatchViewModeChangeEvent(from, to) {
            const event = new CustomEvent('table-view-mode-change', {
                detail: {
                    from: from,
                    to: to,
                    screenWidth: this.screenWidth,
                    breakpoint: this.breakpoint
                },
                bubbles: true,
                composed: true
            });
            
            this.$el.dispatchEvent(event);
        },
        
        // ==================== View Mode Helpers ====================
        
        /**
         * Check if current view is table view
         * 
         * @returns {boolean}
         */
        isTableView() {
            return this.viewMode === 'table';
        },
        
        /**
         * Check if current view is card view
         * 
         * @returns {boolean}
         */
        isCardView() {
            return this.viewMode === 'card';
        },
        
        /**
         * Check if screen is mobile size
         * 
         * @returns {boolean}
         */
        isMobile() {
            return this.screenWidth < this.breakpoint;
        },
        
        /**
         * Check if screen is desktop size
         * 
         * @returns {boolean}
         */
        isDesktop() {
            return this.screenWidth >= this.breakpoint;
        },
        
        /**
         * Get current screen size category
         * 
         * @returns {string} 'mobile', 'tablet', or 'desktop'
         */
        getScreenSizeCategory() {
            if (this.screenWidth < 640) {
                return 'mobile';
            } else if (this.screenWidth < 1024) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        },
        
        /**
         * Manually switch to table view
         * 
         * This overrides automatic detection until next resize.
         */
        switchToTableView() {
            this.viewMode = 'table';
            this.dispatchViewModeChangeEvent(this.viewMode, 'table');
        },
        
        /**
         * Manually switch to card view
         * 
         * This overrides automatic detection until next resize.
         */
        switchToCardView() {
            this.viewMode = 'card';
            this.dispatchViewModeChangeEvent(this.viewMode, 'card');
        },
        
        /**
         * Toggle between table and card view
         */
        toggleView() {
            if (this.viewMode === 'table') {
                this.switchToCardView();
            } else {
                this.switchToTableView();
            }
        },
    };
}

// Register as Alpine.js component
if (window.Alpine) {
    window.Alpine.data('responsiveTableView', responsiveTableView);
}

/**
 * Touch Support for Mobile Devices
 * 
 * This component provides touch-friendly interactions for mobile devices:
 * - Touch scrolling support
 * - Touch-friendly action buttons (larger tap targets)
 * - Swipe gestures for pagination
 * - Automatic touch device detection
 * 
 * Features:
 * - Detects touch-capable devices automatically
 * - Enables smooth touch scrolling
 * - Enlarges action buttons on touch devices
 * - Supports swipe left/right for pagination
 * - Configurable swipe threshold and sensitivity
 * 
 * @param {Object} config - Configuration options
 * @returns {Object} Alpine.js component data and methods
 */
export function touchSupport(config = {}) {
    return {
        // ==================== State ====================
        
        // Touch device detection
        isTouchDevice: false,
        
        // Touch scrolling state
        touchScrollEnabled: true,
        
        // Swipe gesture state
        swipeEnabled: config.swipeEnabled !== false, // Default: true
        touchStartX: 0,
        touchStartY: 0,
        touchEndX: 0,
        touchEndY: 0,
        isSwiping: false,
        
        // Swipe configuration
        swipeThreshold: config.swipeThreshold || 50, // Minimum distance for swipe (px)
        swipeVelocityThreshold: config.swipeVelocityThreshold || 0.3, // Minimum velocity
        swipeMaxVerticalDistance: config.swipeMaxVerticalDistance || 100, // Max vertical movement
        
        // Touch-friendly button size
        touchButtonSize: config.touchButtonSize || 'btn-md', // 'btn-sm', 'btn-md', 'btn-lg'
        
        // Debug mode
        debug: config.debug || false,
        
        // ==================== Initialization ====================
        
        /**
         * Initialize touch support
         */
        init() {
            // Detect if device supports touch
            this.detectTouchDevice();
            
            // Add touch event listeners if touch device
            if (this.isTouchDevice && this.swipeEnabled) {
                this.addTouchEventListeners();
            }
            
            // Apply touch-friendly styles
            if (this.isTouchDevice) {
                this.applyTouchStyles();
            }
            
            // Log initialization in debug mode
            if (this.debug) {
                console.log('Touch Support initialized:', {
                    isTouchDevice: this.isTouchDevice,
                    swipeEnabled: this.swipeEnabled,
                    touchScrollEnabled: this.touchScrollEnabled,
                    swipeThreshold: this.swipeThreshold
                });
            }
        },
        
        /**
         * Cleanup on component destroy
         */
        destroy() {
            if (this.isTouchDevice && this.swipeEnabled) {
                this.removeTouchEventListeners();
            }
        },
        
        // ==================== Touch Device Detection ====================
        
        /**
         * Detect if device supports touch
         * 
         * Uses multiple detection methods for reliability:
         * 1. 'ontouchstart' in window
         * 2. navigator.maxTouchPoints > 0
         * 3. navigator.msMaxTouchPoints > 0 (IE)
         * 4. matchMedia('(pointer: coarse)')
         */
        detectTouchDevice() {
            // Method 1: Check for touch events
            const hasTouchEvents = 'ontouchstart' in window;
            
            // Method 2: Check for touch points
            const hasTouchPoints = navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
            
            // Method 3: Check for coarse pointer (touch)
            const hasCoarsePointer = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
            
            // Device is touch-capable if any method returns true
            this.isTouchDevice = hasTouchEvents || hasTouchPoints || hasCoarsePointer;
            
            // Add CSS class to body for touch-specific styling
            if (this.isTouchDevice) {
                document.body.classList.add('touch-device');
            } else {
                document.body.classList.add('no-touch-device');
            }
            
            // Dispatch custom event
            this.dispatchTouchDetectionEvent();
        },
        
        /**
         * Dispatch custom event for touch detection
         */
        dispatchTouchDetectionEvent() {
            const event = new CustomEvent('touch-device-detected', {
                detail: {
                    isTouchDevice: this.isTouchDevice,
                    hasTouchEvents: 'ontouchstart' in window,
                    hasTouchPoints: navigator.maxTouchPoints > 0,
                    hasCoarsePointer: window.matchMedia && window.matchMedia('(pointer: coarse)').matches
                },
                bubbles: true,
                composed: true
            });
            
            this.$el.dispatchEvent(event);
        },
        
        // ==================== Touch Scrolling ====================
        
        /**
         * Enable touch scrolling
         * 
         * Touch scrolling is enabled by default on touch devices.
         * This method ensures smooth scrolling behavior.
         */
        enableTouchScrolling() {
            this.touchScrollEnabled = true;
            
            // Add smooth scrolling CSS
            const tableContainer = this.$el.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.style.overflowX = 'auto';
                tableContainer.style.webkitOverflowScrolling = 'touch'; // iOS smooth scrolling
                tableContainer.style.scrollBehavior = 'smooth';
            }
        },
        
        /**
         * Disable touch scrolling
         */
        disableTouchScrolling() {
            this.touchScrollEnabled = false;
            
            const tableContainer = this.$el.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.style.overflowX = 'hidden';
            }
        },
        
        // ==================== Touch-Friendly Buttons ====================
        
        /**
         * Apply touch-friendly styles
         * 
         * Increases button sizes and tap targets for better touch interaction.
         */
        applyTouchStyles() {
            // Increase action button sizes
            this.$nextTick(() => {
                const actionButtons = this.$el.querySelectorAll('.btn');
                actionButtons.forEach(button => {
                    // Add touch-friendly size class
                    if (!button.classList.contains('btn-lg') && !button.classList.contains('btn-md')) {
                        button.classList.remove('btn-sm');
                        button.classList.add(this.touchButtonSize);
                    }
                    
                    // Add minimum tap target size (44x44px per WCAG guidelines)
                    button.style.minWidth = '44px';
                    button.style.minHeight = '44px';
                    
                    // Add touch-action for better touch handling
                    button.style.touchAction = 'manipulation';
                });
                
                // Increase pagination button sizes
                const paginationButtons = this.$el.querySelectorAll('.pagination button, .pagination a');
                paginationButtons.forEach(button => {
                    button.style.minWidth = '44px';
                    button.style.minHeight = '44px';
                    button.style.touchAction = 'manipulation';
                });
                
                // Increase checkbox sizes
                const checkboxes = this.$el.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.style.width = '20px';
                    checkbox.style.height = '20px';
                    checkbox.style.touchAction = 'manipulation';
                });
            });
        },
        
        /**
         * Get touch-friendly button class
         * 
         * @returns {string} Button size class
         */
        getTouchButtonClass() {
            return this.isTouchDevice ? this.touchButtonSize : 'btn-sm';
        },
        
        // ==================== Swipe Gestures ====================
        
        /**
         * Add touch event listeners for swipe gestures
         */
        addTouchEventListeners() {
            this.$el.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
            this.$el.addEventListener('touchmove', (e) => this.handleTouchMove(e), { passive: true });
            this.$el.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });
        },
        
        /**
         * Remove touch event listeners
         */
        removeTouchEventListeners() {
            this.$el.removeEventListener('touchstart', (e) => this.handleTouchStart(e));
            this.$el.removeEventListener('touchmove', (e) => this.handleTouchMove(e));
            this.$el.removeEventListener('touchend', (e) => this.handleTouchEnd(e));
        },
        
        /**
         * Handle touch start event
         * 
         * @param {TouchEvent} e - Touch event
         */
        handleTouchStart(e) {
            if (!this.swipeEnabled) return;
            
            const touch = e.touches[0];
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            this.touchStartTime = Date.now();
            this.isSwiping = false;
            
            if (this.debug) {
                console.log('Touch start:', { x: this.touchStartX, y: this.touchStartY });
            }
        },
        
        /**
         * Handle touch move event
         * 
         * @param {TouchEvent} e - Touch event
         */
        handleTouchMove(e) {
            if (!this.swipeEnabled) return;
            
            const touch = e.touches[0];
            this.touchEndX = touch.clientX;
            this.touchEndY = touch.clientY;
            
            // Calculate distances
            const deltaX = Math.abs(this.touchEndX - this.touchStartX);
            const deltaY = Math.abs(this.touchEndY - this.touchStartY);
            
            // Detect if user is swiping horizontally
            if (deltaX > 10 && deltaX > deltaY) {
                this.isSwiping = true;
            }
        },
        
        /**
         * Handle touch end event
         * 
         * @param {TouchEvent} e - Touch event
         */
        handleTouchEnd(e) {
            if (!this.swipeEnabled || !this.isSwiping) return;
            
            const touchEndTime = Date.now();
            const touchDuration = touchEndTime - this.touchStartTime;
            
            // Calculate swipe distance and direction
            const deltaX = this.touchEndX - this.touchStartX;
            const deltaY = Math.abs(this.touchEndY - this.touchStartY);
            const distance = Math.abs(deltaX);
            
            // Calculate velocity (pixels per millisecond)
            const velocity = distance / touchDuration;
            
            // Check if swipe meets thresholds
            const isValidSwipe = 
                distance >= this.swipeThreshold &&
                velocity >= this.swipeVelocityThreshold &&
                deltaY <= this.swipeMaxVerticalDistance;
            
            if (isValidSwipe) {
                if (deltaX > 0) {
                    // Swipe right → Previous page
                    this.handleSwipeRight();
                } else {
                    // Swipe left → Next page
                    this.handleSwipeLeft();
                }
            }
            
            // Reset swipe state
            this.isSwiping = false;
            this.touchStartX = 0;
            this.touchStartY = 0;
            this.touchEndX = 0;
            this.touchEndY = 0;
            
            if (this.debug && isValidSwipe) {
                console.log('Swipe detected:', {
                    direction: deltaX > 0 ? 'right' : 'left',
                    distance: distance,
                    velocity: velocity,
                    duration: touchDuration
                });
            }
        },
        
        /**
         * Handle swipe left gesture (next page)
         */
        handleSwipeLeft() {
            // Dispatch custom event
            const event = new CustomEvent('table-swipe-left', {
                detail: {
                    action: 'next-page'
                },
                bubbles: true,
                composed: true
            });
            
            this.$el.dispatchEvent(event);
            
            // Trigger next page if table component is available
            if (this.nextPage && typeof this.nextPage === 'function') {
                this.nextPage();
            }
        },
        
        /**
         * Handle swipe right gesture (previous page)
         */
        handleSwipeRight() {
            // Dispatch custom event
            const event = new CustomEvent('table-swipe-right', {
                detail: {
                    action: 'previous-page'
                },
                bubbles: true,
                composed: true
            });
            
            this.$el.dispatchEvent(event);
            
            // Trigger previous page if table component is available
            if (this.previousPage && typeof this.previousPage === 'function') {
                this.previousPage();
            }
        },
        
        // ==================== Helper Methods ====================
        
        /**
         * Check if device is touch-capable
         * 
         * @returns {boolean}
         */
        isTouchCapable() {
            return this.isTouchDevice;
        },
        
        /**
         * Check if touch scrolling is enabled
         * 
         * @returns {boolean}
         */
        isTouchScrollingEnabled() {
            return this.touchScrollEnabled;
        },
        
        /**
         * Check if swipe gestures are enabled
         * 
         * @returns {boolean}
         */
        isSwipeEnabled() {
            return this.swipeEnabled;
        },
        
        /**
         * Enable swipe gestures
         */
        enableSwipe() {
            this.swipeEnabled = true;
            if (this.isTouchDevice) {
                this.addTouchEventListeners();
            }
        },
        
        /**
         * Disable swipe gestures
         */
        disableSwipe() {
            this.swipeEnabled = false;
            if (this.isTouchDevice) {
                this.removeTouchEventListeners();
            }
        },
        
        /**
         * Get device information
         * 
         * @returns {Object} Device info
         */
        getDeviceInfo() {
            return {
                isTouchDevice: this.isTouchDevice,
                hasTouchEvents: 'ontouchstart' in window,
                maxTouchPoints: navigator.maxTouchPoints || 0,
                hasCoarsePointer: window.matchMedia && window.matchMedia('(pointer: coarse)').matches,
                userAgent: navigator.userAgent,
                platform: navigator.platform
            };
        },
        
        // ==================== Virtual Scrolling ====================
        
        /**
         * Initialize virtual scrolling
         * 
         * Creates a virtualizer instance for rendering only visible rows.
         * This dramatically improves performance for large datasets.
         */
        initVirtualScrolling() {
            if (!this.config.virtualScrolling?.enabled) {
                return;
            }
            
            // Check if TanStack Virtual is loaded
            if (!window.TanStackVirtual) {
                console.warn('TanStack Virtual library not loaded. Virtual scrolling disabled.');
                return;
            }
            
            const { Virtualizer } = window.TanStackVirtual;
            
            // Create virtualizer instance
            this.virtualizer = new Virtualizer({
                count: this.getRowCount(),
                getScrollElement: () => this.$refs.scrollContainer,
                estimateSize: () => this.config.virtualScrolling.estimateSize || 50,
                overscan: this.config.virtualScrolling.overscan || 5,
                // Support dynamic row heights
                measureElement: this.config.virtualScrolling.dynamicHeight 
                    ? (el) => el?.getBoundingClientRect().height 
                    : undefined,
            });
            
            if (this.debug) {
                console.log('[Virtual Scrolling] Initialized:', {
                    count: this.getRowCount(),
                    estimateSize: this.config.virtualScrolling.estimateSize,
                    overscan: this.config.virtualScrolling.overscan,
                });
            }
        },
        
        /**
         * Get total row count for virtualizer
         * 
         * @returns {number}
         */
        getRowCount() {
            if (this.serverSide) {
                return this.totalRows;
            }
            return this.table ? this.table.getRowModel().rows.length : 0;
        },
        
        /**
         * Get visible rows for rendering
         * 
         * Returns only the rows that should be rendered based on scroll position.
         * For non-virtual mode, returns all rows.
         * 
         * @returns {Array} Visible rows
         */
        getVisibleRows() {
            if (!this.virtualizer || !this.config.virtualScrolling?.enabled) {
                return this.table.getRowModel().rows;
            }
            
            const virtualRows = this.virtualizer.getVirtualItems();
            const allRows = this.table.getRowModel().rows;
            
            return virtualRows.map(virtualRow => ({
                ...allRows[virtualRow.index],
                virtualIndex: virtualRow.index,
                virtualStart: virtualRow.start,
                virtualSize: virtualRow.size,
            }));
        },
        
        /**
         * Get row offset for virtual positioning
         * 
         * @param {Object} row - Row object
         * @returns {number} Offset in pixels
         */
        getRowOffset(row) {
            if (!this.virtualizer || !row.virtualStart) {
                return 0;
            }
            return row.virtualStart;
        },
        
        /**
         * Get total virtual size
         * 
         * @returns {number} Total height in pixels
         */
        getTotalVirtualSize() {
            if (!this.virtualizer) {
                return 0;
            }
            return this.virtualizer.getTotalSize();
        },
        
        /**
         * Handle scroll event for virtual scrolling
         * 
         * Updates the virtualizer when user scrolls.
         */
        onVirtualScroll() {
            if (this.virtualizer) {
                this.virtualizer.measure();
            }
        },
        
        /**
         * Update virtualizer when data changes
         */
        updateVirtualizer() {
            if (!this.virtualizer) {
                return;
            }
            
            // Update row count
            const newCount = this.getRowCount();
            if (this.virtualizer.options.count !== newCount) {
                this.virtualizer.options.count = newCount;
                this.virtualizer.measure();
            }
        },
        
        /**
         * Check if virtual scrolling is enabled
         * 
         * @returns {boolean}
         */
        isVirtualScrollingEnabled() {
            return this.config.virtualScrolling?.enabled && this.virtualizer !== null;
        },
        
        // ==================== Lazy Loading (Infinite Scroll) ====================
        
        /**
         * Lazy loading state
         */
        lazyLoadInProgress: false,
        lazyLoadHasMore: true,
        lazyLoadCurrentPage: 1,
        lazyLoadTotalPages: 1,
        
        /**
         * Initialize lazy loading
         * 
         * Sets up scroll event listener for infinite scroll mode.
         * Automatically loads more data when scrolling near bottom.
         */
        initLazyLoading() {
            if (!this.config.lazyLoad) {
                return;
            }
            
            // Get scroll container
            const scrollContainer = this.$refs.scrollContainer || this.$el.querySelector('[data-scroll-container]');
            
            if (!scrollContainer) {
                console.warn('[Lazy Loading] Scroll container not found');
                return;
            }
            
            // Add scroll event listener
            scrollContainer.addEventListener('scroll', this.handleLazyLoadScroll.bind(this));
            
            if (this.debug) {
                console.log('[Lazy Loading] Initialized:', {
                    pageSize: this.config.lazyLoadPageSize,
                    threshold: this.config.lazyLoadThreshold,
                    infiniteScroll: this.config.lazyLoadInfiniteScroll,
                });
            }
        },
        
        /**
         * Handle scroll event for lazy loading
         * 
         * Checks if user has scrolled near bottom and triggers data load.
         * Implements duplicate request prevention.
         */
        handleLazyLoadScroll(event) {
            if (!this.config.lazyLoad || this.lazyLoadInProgress || !this.lazyLoadHasMore) {
                return;
            }
            
            const scrollContainer = event.target;
            const threshold = this.config.lazyLoadThreshold || 200;
            
            // Calculate distance from bottom
            const scrollTop = scrollContainer.scrollTop;
            const scrollHeight = scrollContainer.scrollHeight;
            const clientHeight = scrollContainer.clientHeight;
            const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
            
            // Check if near bottom (within threshold)
            if (distanceFromBottom <= threshold) {
                this.loadMoreData();
            }
        },
        
        /**
         * Load more data (lazy loading)
         * 
         * Fetches the next page of data and appends to existing data.
         * Prevents duplicate requests and handles loading states.
         */
        async loadMoreData() {
            if (this.lazyLoadInProgress || !this.lazyLoadHasMore) {
                return;
            }
            
            // Prevent duplicate requests
            this.lazyLoadInProgress = true;
            
            try {
                const nextPage = this.lazyLoadCurrentPage + 1;
                
                if (this.debug) {
                    console.log('[Lazy Loading] Loading page:', nextPage);
                }
                
                const response = await fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        // Pagination for lazy loading
                        page: nextPage,
                        pageSize: this.config.lazyLoadPageSize || 50,
                        
                        // Sorting
                        sorting: this.sorting,
                        
                        // Searching
                        globalFilter: this.globalFilter,
                        
                        // Filtering
                        filters: this.filters,
                        
                        // Indicate this is a lazy load request
                        lazyLoad: true,
                    }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                // Append new data to existing data
                if (result.data && result.data.length > 0) {
                    this.data = [...this.data, ...result.data];
                    this.lazyLoadCurrentPage = nextPage;
                    
                    // Update table
                    this.table.setOptions(prev => ({
                        ...prev,
                        data: this.data,
                    }));
                    
                    // Check if there's more data
                    this.lazyLoadTotalPages = result.totalPages || 1;
                    this.lazyLoadHasMore = nextPage < this.lazyLoadTotalPages;
                    
                    if (this.debug) {
                        console.log('[Lazy Loading] Loaded:', {
                            newRows: result.data.length,
                            totalRows: this.data.length,
                            currentPage: this.lazyLoadCurrentPage,
                            totalPages: this.lazyLoadTotalPages,
                            hasMore: this.lazyLoadHasMore,
                        });
                    }
                } else {
                    // No more data
                    this.lazyLoadHasMore = false;
                    
                    if (this.debug) {
                        console.log('[Lazy Loading] No more data');
                    }
                }
                
                // Update virtualizer after data changes
                this.$nextTick(() => {
                    this.updateVirtualizer();
                });
                
                // Reinitialize Lucide icons
                if (window.lucide) {
                    this.$nextTick(() => {
                        window.lucide.createIcons();
                    });
                }
                
            } catch (err) {
                console.error('[Lazy Loading] Error:', err);
                this.error = true;
                this.errorMessage = err.message || this.translations.error || 'An error occurred';
            } finally {
                this.lazyLoadInProgress = false;
            }
        },
        
        /**
         * Reset lazy loading state
         * 
         * Called when filters, search, or sort changes.
         * Resets to first page and clears existing data.
         */
        resetLazyLoading() {
            if (!this.config.lazyLoad) {
                return;
            }
            
            this.lazyLoadCurrentPage = 1;
            this.lazyLoadHasMore = true;
            this.data = [];
            
            if (this.debug) {
                console.log('[Lazy Loading] Reset');
            }
        },
        
        /**
         * Check if lazy loading is enabled
         * 
         * @returns {boolean}
         */
        isLazyLoadingEnabled() {
            return this.config.lazyLoad === true;
        },
        
        /**
         * Check if currently loading more data
         * 
         * @returns {boolean}
         */
        isLoadingMore() {
            return this.lazyLoadInProgress;
        },
        
        /**
         * Check if there's more data to load
         * 
         * @returns {boolean}
         */
        hasMoreData() {
            return this.lazyLoadHasMore;
        },
    };
}

// Register as Alpine.js component
if (window.Alpine) {
    window.Alpine.data('touchSupport', touchSupport);
}

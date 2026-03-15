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
     * Flag to track if global functions have been output.
     * Static to ensure they're only output once across all instances.
     *
     * @var bool
     */
    protected static bool $globalFunctionsOutput = false;

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
     * Reset the global functions output flag.
     * This method is intended for testing purposes only.
     * 
     * @return void
     */
    public static function resetGlobalFunctionsFlag(): void
    {
        self::$globalFunctionsOutput = false;
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
        
        // Use unique ID from HashGenerator (Requirement 8.1, 8.2)
        $tableId = $table->getUniqueId();
        
        // CRITICAL FIX #35: Render JavaScript BEFORE HTML
        // Alpine.js needs the component to be registered BEFORE it parses the x-data attribute
        echo $this->renderScripts($table, $config, $columns, $alpineData);
        
        // Render filter modal (if filters are configured)
        echo $this->renderFilterModal($table, $tableId);
        
        // Render table container with Alpine.js
        echo $this->renderTableContainer($table, $alpineData);
        
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
            $table->getUniqueId(),
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
        // Use unique ID from HashGenerator (Requirement 8.1, 8.2)
        // This ensures each table instance has a secure, unique identifier
        $tableId = $table->getUniqueId();
        
        // Merge columns into alpineData
        $alpineData['columns'] = $columns;
        
        // Add pagination object with safe division
        $pageSize = $alpineData['pageSize'] ?? 10;
        $totalRows = $alpineData['totalRows'] ?? 0;
        
        // Prevent division by zero
        if ($pageSize <= 0) {
            $pageSize = $totalRows > 0 ? $totalRows : 10;
        }
        
        $alpineData['pagination'] = [
            'page' => 1,
            'pageSize' => $pageSize,
            'totalPages' => $totalRows > 0 ? ceil($totalRows / $pageSize) : 1,
            'totalRows' => $totalRows,
        ];
        
        // Ensure all required properties exist
        $alpineData['loading'] = $alpineData['loading'] ?? false;
        $alpineData['error'] = $alpineData['error'] ?? null;
        $alpineData['errorMessage'] = $alpineData['errorMessage'] ?? '';
        $alpineData['globalFilter'] = $alpineData['globalFilter'] ?? '';
        $alpineData['data'] = $alpineData['data'] ?? [];
        
        // Add server-side properties
        $alpineData['serverSideUrl'] = $config['serverSide']['url'] ?? null;
        $alpineData['filterUrl'] = route('datatable.get-filters'); // Add filter URL
        // Use configured table name first, fallback to model table name
        $alpineData['tableName'] = $config['tableName'] ?? ($table->getModel() ? $table->getModel()->getTable() : 'users');
        $alpineData['tableId'] = $tableId; // CRITICAL: Add unique table ID for filter matching
        $alpineData['modelClass'] = $table->getModel() ? get_class($table->getModel()) : null;
        $alpineData['connection'] = $table->getConnection(); // Add connection name
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
        
        $js = <<<JS
<script>
// TanStack Table Component Registration
// Each table instance is wrapped in an IIFE to ensure complete isolation
// Validates: Requirement 8.3 - Independent initialization
(function() {
    // Unique table ID from HashGenerator (Requirement 8.1, 8.2)
    const tableId = '{$tableId}';
    const componentName = 'tanstackTable_' + tableId;
    
    console.log('TanStack Table: Script loaded for', componentName);
    
    const registerComponent = () => {
        console.log('TanStack Table: Registering component', componentName, 'with unique ID', tableId);
        
        // Check if Alpine is available
        if (typeof Alpine === 'undefined') {
            console.error('TanStack Table: Alpine.js not found! Cannot register component', componentName);
            return;
        }
        
        // Prevent duplicate registration
        if (Alpine._x_dataStack && Alpine._x_dataStack.some(d => d.name === componentName)) {
            console.warn('TanStack Table: Component', componentName, 'already registered, skipping');
            return;
        }
        
        Alpine.data(componentName, () => {
            const alpineData = {$alpineDataJson};
            
            // Debug: Log columns to verify header values
            console.log('TanStack Table: Columns data', alpineData.columns);
            
            return {
                ...alpineData,
                
                // Initialization guard (prevent multiple init)
                _initialized: false,
                
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
                 * Each instance initializes independently with its own state.
                 * Validates: Requirement 8.3 - Independent initialization
                 * Validates: Requirement 8.8 - State persistence per tab
                 * 
                 * CRITICAL FIX #50: Use global flag to prevent Alpine.js re-initialization loop
                 * Alpine.js re-evaluates component factory on reactive updates, causing infinite init() calls.
                 * Solution: Store initialization state globally, outside Alpine's reactive system.
                 */
                init() {
                    // CRITICAL: Use global flag outside Alpine's reactive system
                    const globalKey = '_tanstack_init_' + tableId;
                    if (window[globalKey]) {
                        console.warn('TanStack Table: Instance', tableId, 'already initialized globally, skipping duplicate init()');
                        return;
                    }
                    window[globalKey] = true;
                    
                    console.log('TanStack Table: Initializing instance', tableId, 'with', this.data.length, 'rows');
                    
                    // Store instance reference for cleanup and debugging
                    if (!window._tanstackInstances) {
                        window._tanstackInstances = {};
                    }
                    window._tanstackInstances[tableId] = this;
                    
                    // Restore state from storage if available (Requirement 8.8)
                    this.restoreState();
                    
                    // Check if server-side processing is enabled
                    if (this.serverSideUrl) {
                        console.log('TanStack Table: Server-side mode enabled for instance', tableId);
                        
                        // CRITICAL FIX #50: Only load data once on initialization
                        // Check if there are saved filters in session, then load data
                        this.checkSavedFilters()
                            .catch(error => {
                                console.warn('TanStack Table: Failed to check saved filters, continuing with data load', error);
                            })
                            .finally(() => {
                                console.log('TanStack Table: Calling loadData() after checkSavedFilters()');
                                this.loadData();
                            });
                        
                        // Listen for filter apply event (scoped to this instance)
                        const filterEventName = 'filters-applied-' + tableId;
                        const filterHandler = () => {
                            console.log('TanStack Table: Filters applied for instance', tableId, ', reloading data...');
                            this.pagination.page = 1; // Reset to first page
                            this.loadData();
                        };
                        
                        // Remove existing listener if any (prevent duplicates)
                        if (window['_filter_handler_' + tableId]) {
                            window.removeEventListener(filterEventName, window['_filter_handler_' + tableId]);
                        }
                        window['_filter_handler_' + tableId] = filterHandler;
                        window.addEventListener(filterEventName, filterHandler);
                        
                        // Also listen for global filter event (backward compatibility)
                        const globalFilterHandler = (e) => {
                            // Only respond if event is for this instance or no instance specified
                            if (!e.detail || !e.detail.tableId || e.detail.tableId === tableId) {
                                console.log('TanStack Table: Global filters applied, reloading data for instance', tableId);
                                console.log('TanStack Table: Filter detail:', e.detail);
                                
                                // CRITICAL FIX: Update this.filters with the new filter values
                                if (e.detail && e.detail.filters) {
                                    this.filters = e.detail.filters;
                                    console.log('TanStack Table: Updated this.filters:', this.filters);
                                }
                                
                                this.pagination.page = 1;
                                this.loadData();
                            }
                        };
                        
                        // Remove existing listener if any (prevent duplicates)
                        if (window['_global_filter_handler_' + tableId]) {
                            window.removeEventListener('filters-applied', window['_global_filter_handler_' + tableId]);
                        }
                        window['_global_filter_handler_' + tableId] = globalFilterHandler;
                        window.addEventListener('filters-applied', globalFilterHandler);
                        
                        return;
                    }
                    
                    // Client-side mode
                    console.log('TanStack Table: Client-side mode for instance', tableId, ', using provided data');
                    
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
                    
                    // CRITICAL FIX #66: LOCK tbody to prevent Alpine.js from clearing it
                    // This is specifically for tab system where Alpine.js re-evaluates after data load
                    this.\$nextTick(() => {
                        const tbodyElement = this.\$refs.tableBody;
                        if (tbodyElement) {
                            // Store original innerHTML in a property that Alpine.js can't touch
                            Object.defineProperty(tbodyElement, '_lockedHTML', {
                                writable: true,
                                configurable: false,
                                enumerable: false,
                                value: null
                            });
                            
                            console.log('TanStack Table: tbody LOCKED against Alpine.js clearing for instance', tableId);
                        }
                    });
                    
                    console.log('TanStack Table: Instance', tableId, 'initialized successfully');
                },
                
                /**
                 * Setup scroll shadow for pinned columns.
                 * Shadow only appears when table is scrolled (not at initial position).
                 */
                setupScrollShadow() {
                    const tableContainer = this.\$el.querySelector('.tanstack-table-desktop');
                    if (!tableContainer) return;
                    
                    const updateShadow = () => {
                        const scrollLeft = tableContainer.scrollLeft;
                        const maxScrollLeft = tableContainer.scrollWidth - tableContainer.clientWidth;
                        
                        // Get all pinned-left elements
                        const pinnedLeftElements = tableContainer.querySelectorAll('.tanstack-table-pinned-left');
                        
                        // Get all pinned-right elements
                        const pinnedRightElements = tableContainer.querySelectorAll('.tanstack-table-pinned-right');
                        
                        // Show shadow on pinned-left when scrolled right (scrollLeft > 0)
                        pinnedLeftElements.forEach(el => {
                            if (scrollLeft > 0) {
                                el.classList.add('has-shadow');
                            } else {
                                el.classList.remove('has-shadow');
                            }
                        });
                        
                        // Show shadow on pinned-right when not scrolled to max right
                        pinnedRightElements.forEach(el => {
                            if (scrollLeft < maxScrollLeft) {
                                el.classList.add('has-shadow');
                            } else {
                                el.classList.remove('has-shadow');
                            }
                        });
                    };
                    
                    // Add scroll event listener
                    tableContainer.addEventListener('scroll', updateShadow);
                    
                    // Initial check
                    updateShadow();
                    
                    console.log('TanStack Table: Scroll shadow setup complete');
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
                 * Get storage key for state persistence.
                 * Uses table ID to ensure unique storage per table instance.
                 * Validates: Requirement 8.8 - State persistence per tab
                 * 
                 * @return {string} - Storage key
                 */
                getStateStorageKey() {
                    return 'tanstack_table_state_' + tableId;
                },
                
                /**
                 * Save current table state to sessionStorage.
                 * Persists sorting, pagination, filters, and column visibility.
                 * Validates: Requirement 8.8 - State persistence per tab
                 */
                saveState() {
                    try {
                        const state = {
                            sorting: this.sorting,
                            pagination: {
                                page: this.pagination.page,
                                pageSize: this.pagination.pageSize
                            },
                            globalFilter: this.globalFilter,
                            columnFilters: this.columnFilters || {},
                            rowSelection: this.rowSelection || {},
                            timestamp: Date.now()
                        };
                        
                        const key = this.getStateStorageKey();
                        sessionStorage.setItem(key, JSON.stringify(state));
                        
                        console.log('TanStack Table: State saved for instance', tableId, state);
                    } catch (error) {
                        console.error('TanStack Table: Error saving state', error);
                    }
                },
                
                /**
                 * Restore table state from sessionStorage.
                 * Restores sorting, pagination, filters, and column visibility.
                 * Validates: Requirement 8.8 - State persistence per tab
                 */
                restoreState() {
                    try {
                        const key = this.getStateStorageKey();
                        const savedState = sessionStorage.getItem(key);
                        
                        if (!savedState) {
                            console.log('TanStack Table: No saved state found for instance', tableId);
                            return;
                        }
                        
                        const state = JSON.parse(savedState);
                        
                        // Check if state is not too old (max 1 hour)
                        const maxAge = 60 * 60 * 1000; // 1 hour in milliseconds
                        if (state.timestamp && (Date.now() - state.timestamp) > maxAge) {
                            console.log('TanStack Table: Saved state expired for instance', tableId);
                            this.clearState();
                            return;
                        }
                        
                        // Restore sorting
                        if (state.sorting) {
                            this.sorting = state.sorting;
                        }
                        
                        // Restore pagination
                        if (state.pagination) {
                            this.pagination.page = state.pagination.page || 1;
                            this.pagination.pageSize = state.pagination.pageSize || this.pagination.pageSize;
                        }
                        
                        // Restore global filter
                        if (state.globalFilter !== undefined) {
                            this.globalFilter = state.globalFilter;
                        }
                        
                        // Restore column filters
                        if (state.columnFilters) {
                            this.columnFilters = state.columnFilters;
                        }
                        
                        // Restore row selection
                        if (state.rowSelection) {
                            this.rowSelection = state.rowSelection;
                            this.updateSelectionState();
                        }
                        
                        console.log('TanStack Table: State restored for instance', tableId, state);
                    } catch (error) {
                        console.error('TanStack Table: Error restoring state', error);
                        this.clearState();
                    }
                },
                
                /**
                 * Clear saved state from sessionStorage.
                 * Validates: Requirement 8.8 - State persistence per tab
                 */
                clearState() {
                    try {
                        const key = this.getStateStorageKey();
                        sessionStorage.removeItem(key);
                        console.log('TanStack Table: State cleared for instance', tableId);
                    } catch (error) {
                        console.error('TanStack Table: Error clearing state', error);
                    }
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
            
            // Save state after selection change (Requirement 8.8)
            this.saveState();
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
            
            // Save state after selection change (Requirement 8.8)
            this.saveState();
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
         * Get inline style for column (for pinned columns positioning).
         * 
         * @param {Object} column - Column definition
         * @param {number} index - Column index
         * @return {string} - Inline style string
         */
        getColumnStyle(column, index) {
            // Note: We can't calculate actual width here because DOM hasn't rendered yet
            // Width calculation will be done in applyPinnedColumnStyles() after render
            return '';
        },
        
        /**
         * Apply pinned column styles to tbody cells.
         * This is called after rows are injected to ensure td cells match th headers.
         */
        applyPinnedColumnStyles() {
            const tbodyElement = this.\$refs.tableBody;
            if (!tbodyElement) return;
            
            const tableElement = tbodyElement.closest('table');
            if (!tableElement) return;
            
            // Get all th elements to measure actual widths
            const headerCells = tableElement.querySelectorAll('thead th');
            
            // Calculate cumulative left positions for pinned-left columns
            const leftPositions = {};
            let cumulativeLeft = 0;
            
            this.pinnedLeft.forEach((columnId, pinnedIndex) => {
                leftPositions[columnId] = cumulativeLeft;
                
                // Find the th element for this column
                const columnIndex = this.columns.findIndex(col => col.id === columnId);
                if (columnIndex >= 0 && headerCells[columnIndex]) {
                    const thElement = headerCells[columnIndex];
                    const actualWidth = thElement.getBoundingClientRect().width;
                    
                    // Apply left position to th
                    thElement.style.left = cumulativeLeft + 'px';
                    
                    // Add to cumulative for next column
                    cumulativeLeft += actualWidth;
                    
                    console.log('TanStack Table: Pinned-left column', columnId, {
                        index: columnIndex,
                        left: leftPositions[columnId],
                        width: actualWidth,
                        nextLeft: cumulativeLeft
                    });
                }
            });
            
            // Calculate cumulative right positions for pinned-right columns
            const rightPositions = {};
            let cumulativeRight = 0;
            
            this.pinnedRight.forEach((columnId, pinnedIndex) => {
                rightPositions[columnId] = cumulativeRight;
                
                // Find the th element for this column
                const columnIndex = this.columns.findIndex(col => col.id === columnId);
                if (columnIndex >= 0 && headerCells[columnIndex]) {
                    const thElement = headerCells[columnIndex];
                    const actualWidth = thElement.getBoundingClientRect().width;
                    
                    // Apply right position to th
                    thElement.style.right = cumulativeRight + 'px';
                    
                    // Add to cumulative for next column
                    cumulativeRight += actualWidth;
                    
                    console.log('TanStack Table: Pinned-right column', columnId, {
                        index: columnIndex,
                        right: rightPositions[columnId],
                        width: actualWidth,
                        nextRight: cumulativeRight
                    });
                }
            });
            
            // Now apply to tbody cells
            const rows = tbodyElement.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                
                cells.forEach((cell, index) => {
                    const column = this.columns[index];
                    if (!column) return;
                    
                    // Apply pinned-left class and style
                    if (this.pinnedLeft.includes(column.id)) {
                        cell.classList.add('tanstack-table-pinned-left');
                        cell.style.left = leftPositions[column.id] + 'px';
                    }
                    
                    // Apply pinned-right class and style
                    if (this.pinnedRight.includes(column.id)) {
                        cell.classList.add('tanstack-table-pinned-right');
                        cell.style.right = rightPositions[column.id] + 'px';
                    }
                });
            });
            
            console.log('TanStack Table: Pinned column styles applied with actual widths', {
                leftPositions,
                rightPositions
            });
        },
        
        /**
         * Get cell value as plain text (no HTML).
         * 
         * @param {Object} row - Row data
         * @param {Object} column - Column definition
         * @return {string} - Cell value as plain text
         */
        getCellValue(row, column) {
            // Handle actions column - return placeholder text
            if (column.id === 'actions' && row._actions) {
                return '⋮'; // Vertical ellipsis for actions
            }
            
            const value = row[column.id];
            
            // Handle null/undefined
            if (value === null || value === undefined) {
                return '-';
            }
            
            // Return as string
            return String(value);
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
         * Update pagination info in DOM manually.
         * 
         * CRITICAL FIX #62: Since tbody uses x-ignore, Alpine.js doesn't know data is loaded.
         * We need to manually update pagination text in the DOM.
         * This method finds all pagination info elements and updates their text content.
         */
        updatePaginationInfo() {
            const paginationText = this.paginationText();
            
            // Find pagination info element by class (more reliable than ID)
            const paginationInfoElements = document.querySelectorAll('.pagination-info span');
            
            paginationInfoElements.forEach(element => {
                // Check if this element belongs to our table instance
                // by checking if it's inside our table container
                const tableContainer = element.closest('[x-data]');
                if (tableContainer && tableContainer.getAttribute('x-data')?.includes(tableId)) {
                    element.textContent = paginationText;
                    console.log('TanStack Table: Pagination info updated to:', paginationText);
                }
            });
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
         * For server-side tables, this will fetch ALL data from server (not just current page).
         * For client-side tables, this will export all data in originalData.
         * 
         * @param {string} format - Export format (excel, csv, pdf, print, copy)
         */
        async exportData(format) {
            console.log('TanStack Table: exportData called', { 
                format, 
                serverSideUrl: this.serverSideUrl,
                tableName: this.tableName,
                connection: this.connection,
                filters: this.filters
            });
            
            let exportData = [];
            const columns = this.columns || [];
            
            // For server-side tables, fetch ALL data from server
            if (this.serverSideUrl) {
                console.log('TanStack Table: Fetching ALL data from server for export...');
                
                try {
                    const url = new URL(this.serverSideUrl, window.location.origin);
                    
                    // Add export flag to fetch ALL data (no pagination)
                    url.searchParams.set('export', 'true');
                    url.searchParams.set('format', format);
                    
                    // Add table configuration parameters (CRITICAL!)
                    // Note: Properties are set directly on Alpine component, not in a config object
                    if (this.tableName) {
                        url.searchParams.set('tableName', this.tableName);
                        console.log('TanStack Table: Added tableName to export URL:', this.tableName);
                    } else {
                        console.warn('TanStack Table: tableName is not defined');
                    }
                    
                    if (this.connection) {
                        url.searchParams.set('connection', this.connection);
                        console.log('TanStack Table: Added connection to export URL:', this.connection);
                    }
                    
                    if (this.modelClass) {
                        url.searchParams.set('modelClass', this.modelClass);
                        console.log('TanStack Table: Added modelClass to export URL:', this.modelClass);
                    }
                    
                    if (this.searchableColumns && this.searchableColumns.length > 0) {
                        url.searchParams.set('searchableColumns', JSON.stringify(this.searchableColumns));
                        console.log('TanStack Table: Added searchableColumns to export URL:', this.searchableColumns);
                    }
                    
                    // CRITICAL FIX: Get active filters from global variable
                    // Filters are saved to global variable when user applies them (FilterModal.js line 597)
                    // IMPORTANT: Use this.tableId (unique ID), NOT this.tableName (database table name)
                    const tableIdForFilters = this.tableId || this.tableName || 'table';
                    
                    // Try to get filters from global variable first (set by FilterModal.js)
                    let activeFilters = window['tableFilters_' + tableIdForFilters] || this.filters || {};
                    
                    console.log('TanStack Table: Checking for active filters...');
                    console.log('TanStack Table: this.tableId:', this.tableId);
                    console.log('TanStack Table: this.tableName:', this.tableName);
                    console.log('TanStack Table: tableIdForFilters:', tableIdForFilters);
                    console.log('TanStack Table: window.tableFilters_' + tableIdForFilters + ':', window['tableFilters_' + tableIdForFilters]);
                    console.log('TanStack Table: this.filters:', this.filters);
                    console.log('TanStack Table: activeFilters:', activeFilters);
                    
                    // Add current filters
                    if (activeFilters && Object.keys(activeFilters).length > 0) {
                        url.searchParams.set('filters', JSON.stringify(activeFilters));
                        console.log('TanStack Table: Added filters to export URL:', activeFilters);
                    } else {
                        console.warn('TanStack Table: No active filters found for export');
                    }
                    
                    // Add current sorting
                    if (this.sorting && this.sorting.length > 0) {
                        url.searchParams.set('sorting', JSON.stringify(this.sorting));
                    }
                    
                    // Add global search
                    if (this.globalFilter) {
                        url.searchParams.set('globalFilter', this.globalFilter);
                    }
                    
                    console.log('TanStack Table: Export URL:', url.toString());
                    console.log('TanStack Table: Export params:', {
                        tableName: this.tableName,
                        connection: this.connection,
                        filters: activeFilters,
                        sorting: this.sorting,
                        globalFilter: this.globalFilter
                    });
                    
                    const response = await fetch(url.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    
                    console.log('TanStack Table: Export response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: \${response.status}`);
                    }
                    
                    const result = await response.json();
                    console.log('TanStack Table: Export result:', result);
                    console.log('TanStack Table: Export result.data length:', result.data ? result.data.length : 'null/undefined');
                    
                    exportData = result.data || [];
                    
                    console.log(`TanStack Table: Fetched \${exportData.length} rows for export`);
                    if (exportData.length > 0) {
                        console.log('TanStack Table: exportData sample (first 2 rows):', exportData.slice(0, 2));
                    }
                    
                } catch (error) {
                    console.error('TanStack Table: Export fetch error:', error);
                    alert('Failed to fetch data for export. Please try again.');
                    return;
                }
            } else {
                // For client-side tables, use all data from originalData
                exportData = this.originalData || this.data || [];
                console.log(`TanStack Table: Using \${exportData.length} rows from originalData for export`);
            }
            
            console.log('TanStack Table: About to export', exportData.length, 'rows with', columns.length, 'columns');
            console.log('TanStack Table: Columns:', columns.map(c => c.id || c.header));
            
            // Now export the data
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
            
            // Save state after page change (Requirement 8.8)
            this.saveState();
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
                
                // Save state after page change (Requirement 8.8)
                this.saveState();
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
                
                // Save state after page change (Requirement 8.8)
                this.saveState();
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
                // Prevent division by zero
                const pageSize = this.pagination.pageSize > 0 ? this.pagination.pageSize : 10;
                this.pagination.totalPages = Math.ceil(this.originalData.length / pageSize);
                this.applyPagination();
            }
            
            // Save state after page size change (Requirement 8.8)
            this.saveState();
        },
        
        /**
         * Handle sort change.
         * 
         * @param {string} columnId - Column ID to sort by
         */
        onSort(columnId) {
            console.log('TanStack Table: onSort called for column', columnId);
            
            if (this.sorting.column === columnId) {
                // Toggle direction
                this.sorting.direction = this.sorting.direction === 'asc' ? 'desc' : 'asc';
            } else {
                // New column
                this.sorting.column = columnId;
                this.sorting.direction = 'asc';
            }
            
            console.log('TanStack Table: Sorting state updated', this.sorting);
            
            // Apply sorting based on mode
            if (this.serverSideUrl) {
                // Server-side: reload data with new sorting
                console.log('TanStack Table: Server-side mode, calling loadData()');
                this.pagination.page = 1; // Reset to first page when sorting
                this.loadData();
            } else {
                // Client-side: apply sorting locally
                console.log('TanStack Table: Client-side mode, calling applySorting()');
                this.applySorting();
            }
            
            // Save state after sort change (Requirement 8.8)
            this.saveState();
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
                // Use filter URL from Alpine data
                const response = await fetch(this.filterUrl, {
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
                        
                        // Set global filters variable (use dynamic tableId)
                        window['tableFilters_' + tableId] = result.filters;
                        
                        // Dispatch event to update filter modal
                        window.dispatchEvent(new CustomEvent('filters-restored', {
                            detail: { 
                                filters: result.filters, 
                                tableId: tableId 
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
         * 
         * UPDATED: Now renders rows server-side to avoid Alpine.js DOM diffing issues.
         * Instead of updating this.data and letting Alpine render with x-for,
         * we fetch pre-rendered HTML from server and inject it into tbody.
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
                    connection: this.connection, // Add connection parameter
                    searchableColumns: this.searchableColumns,
                    columns: this.columns.map(col => col.id), // Send column IDs for server-side rendering
                    renderHtml: true, // Request HTML rendering instead of JSON data
                };
                
                // Add filters from global variable if available (use dynamic tableId)
                const filterVarName = 'tableFilters_' + tableId;
                if (typeof window[filterVarName] !== 'undefined') {
                    requestData.filters = window[filterVarName];
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
                    hasHtml: !!result.html,
                    htmlLength: result.html?.length,
                    rowCount: result.meta?.rowCount,
                    meta: result.meta
                });
                
                // Debug: Log current Alpine state BEFORE update
                console.log('TanStack Table: Alpine state BEFORE update', {
                    loading: this.loading,
                    error: this.error,
                    dataLength: this.data.length
                });
                
                // CRITICAL FIX #64: Install MutationObserver BEFORE injecting HTML
                // CRITICAL FIX #66: Enhanced with locked HTML property and multiple restore attempts
                // CRITICAL FIX #68: Add CONTINUOUS monitoring with setInterval
                // CRITICAL FIX #71: DELAY injection until Alpine finishes ALL initializations
                const tbodyId = 'tanstack-tbody-' + tableId;
                const tbodyElement = document.getElementById(tbodyId);
                const htmlToInject = result.html;
                
                if (tbodyElement && htmlToInject) {
                    console.log('TanStack Table: Preparing delayed injection (waiting 1000ms for Alpine to stabilize)...');
                    
                    // WAIT for Alpine to finish ALL initializations
                    // Console shows 6 initializations (1 + 5 duplicates)
                    // We need to wait until ALL are done before injecting
                    setTimeout(() => {
                        console.log('TanStack Table: Alpine should be stable now, injecting HTML...');
                        
                        // Store HTML in LOCKED property
                        if (!tbodyElement._lockedHTML) {
                            Object.defineProperty(tbodyElement, '_lockedHTML', {
                                writable: true,
                                configurable: false,
                                enumerable: false,
                                value: htmlToInject
                            });
                        } else {
                            tbodyElement._lockedHTML = htmlToInject;
                        }
                        
                        // Create ULTRA-AGGRESSIVE MutationObserver
                        const observer = new MutationObserver((mutations) => {
                            const lockedHtml = tbodyElement._lockedHTML;
                            if (!lockedHtml) return;
                            
                            const currentChildren = tbodyElement.children.length;
                            const expectedChildren = lockedHtml.match(/<tr/g)?.length || 0;
                            
                            if (currentChildren === 0 || currentChildren < expectedChildren) {
                                console.log('TanStack Table: MutationObserver detected clearing, RESTORING...', {
                                    current: currentChildren,
                                    expected: expectedChildren
                                });
                                tbodyElement.innerHTML = lockedHtml;
                            }
                        });
                        
                        // Start observing
                        observer.observe(tbodyElement, {
                            childList: true,
                            subtree: false,
                            attributes: false,
                            characterData: false
                        });
                        
                        // Store observer
                        if (!window.tanstackObservers) {
                            window.tanstackObservers = {};
                        }
                        window.tanstackObservers[tableId] = observer;
                        
                        console.log('TanStack Table: ULTRA-AGGRESSIVE MutationObserver installed');
                        
                        // NOW inject HTML (after delay)
                        tbodyElement.innerHTML = htmlToInject;
                        console.log('TanStack Table: Rows injected (delayed)');
                        
                        // CRITICAL: Apply pinned column styles to tbody cells
                        this.applyPinnedColumnStyles();
                        
                        // Setup scroll shadow for pinned columns
                        this.setupScrollShadow();
                        
                        // Debug
                        console.log('TanStack Table: Tbody verification', {
                            tbodyId: tbodyElement.id,
                            childrenCount: tbodyElement.children.length,
                            innerHTMLLength: tbodyElement.innerHTML.length,
                            hasLockedHTML: !!tbodyElement._lockedHTML
                        });
                        
                        // CRITICAL FIX #68: CONTINUOUS monitoring with setInterval
                        // This runs FOREVER to catch ANY clearing by Alpine.js at ANY time
                        const monitoringKey = '_tbody_monitor_' + tableId;
                        
                        // Clear existing monitor if any
                        if (window[monitoringKey]) {
                            clearInterval(window[monitoringKey]);
                        }
                        
                        // Start CONTINUOUS monitoring (every 100ms)
                        window[monitoringKey] = setInterval(() => {
                            if (tbodyElement.children.length === 0 && tbodyElement._lockedHTML) {
                                console.log('TanStack Table: CONTINUOUS monitor detected clearing, FORCE RESTORING...');
                                tbodyElement.innerHTML = tbodyElement._lockedHTML;
                            }
                        }, 100);
                        
                        console.log('TanStack Table: CONTINUOUS monitoring started (every 100ms)');
                    }, 1000); // Wait 1 second for Alpine to stabilize
                } else {
                    console.error('TanStack Table: Failed to setup tbody guard', {
                        tbodyFound: !!tbodyElement,
                        htmlReceived: !!htmlToInject
                    });
                }
                
                // Update pagination metadata
                this.pagination.totalRows = result.meta?.totalRows || 0;
                this.pagination.totalPages = result.meta?.totalPages || 1;
                
                // Update this.data with dummy array to indicate rows are loaded
                // This prevents the empty state from showing when using server-side rendering
                // We use a dummy array with correct length so Alpine knows there's data
                const rowCount = result.meta?.rowCount || 0;
                this.data = Array(rowCount).fill({_serverRendered: true});
                console.log('TanStack Table: Updated data array length to', rowCount);
                
                // CRITICAL FIX #56: Explicitly hide empty state with JavaScript
                // Empty state is now OUTSIDE x-show wrapper, so we control it manually
                const emptyStateId = 'tanstack-empty-' + tableId;
                const emptyState = document.getElementById(emptyStateId);
                if (emptyState) {
                    emptyState.style.display = 'none';
                    console.log('TanStack Table: Empty state hidden via JavaScript');
                }
                
                this.loading = false;
                
                // CRITICAL FIX #62: Update pagination info manually
                // Since tbody uses x-ignore, Alpine.js doesn't know data is loaded
                // We need to manually update pagination text in the DOM
                this.updatePaginationInfo();
                
                console.log('TanStack Table: Data loaded', {
                    rows: result.meta?.rowCount || 0,
                    total: this.pagination.totalRows,
                    page: this.pagination.page,
                    loading: this.loading
                });
            } catch (error) {
                console.error('TanStack Table: Error loading data', error);
                this.error = true;
                this.errorMessage = error.message || 'Failed to load data';
                this.loading = false; // Ensure loading is false on error too
            } finally {
                // Remove finally block setting loading to false - already set above
                // this.loading = false;
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
         * Cleanup method for instance destruction.
         * Removes instance from global registry and cleans up event listeners.
         * Validates: Requirement 8.3 - Independent initialization and cleanup
         * Validates: Requirement 8.8 - State persistence cleanup
         */
        destroy() {
            console.log('TanStack Table: Destroying instance', tableId);
            
            // Save final state before destruction (Requirement 8.8)
            this.saveState();
            
            // Remove from global instance registry
            if (window._tanstackInstances && window._tanstackInstances[tableId]) {
                delete window._tanstackInstances[tableId];
            }
            
            // Clean up instance-specific event listeners
            const filterEventName = 'filters-applied-' + tableId;
            window.removeEventListener(filterEventName, this.loadData);
            
            console.log('TanStack Table: Instance', tableId, 'destroyed successfully');
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
        console.log('TanStack Table: Alpine already loaded, registering component', componentName, 'immediately');
        
        // Requirement 8.4: Verify TanStack JS loaded before init
        // Check if TanStack Table library is available (exposed as TableCore)
        // VirtualCore is optional for virtualization features
        if (typeof window.TableCore === 'undefined') {
            console.error('TanStack Table: TanStack Table library not loaded for instance', tableId);
            console.error('TanStack Table: Please ensure TanStack Table JavaScript is included before initializing tables');
            console.error('TanStack Table: Expected window.TableCore to be defined');
            
            // Handle missing dependency gracefully - show error message in table container
            const tableContainer = document.querySelector('[x-data*="' + componentName + '"]');
            if (tableContainer) {
                tableContainer.innerHTML = `
                    <div class="alert alert-error" role="alert">
                        <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <strong>TanStack Table Error:</strong> TanStack Table library is not loaded. 
                        Please include the TanStack Table JavaScript file before initializing tables.
                    </div>
                `;
            }
            
            return; // Stop initialization
        }
        
        // VirtualCore is optional - log warning if not available but continue
        if (typeof window.VirtualCore === 'undefined') {
            console.warn('TanStack Table: VirtualCore not loaded - virtualization features will be disabled');
        }
        
        console.log('TanStack Table: TanStack Table library verified for instance', tableId);
        registerComponent();
        
        // Let Alpine automatically initialize the component (don't call initTree manually)
        console.log('TanStack Table: Component registered, Alpine will auto-initialize');
    } else {
        console.log('TanStack Table: Waiting for Alpine to load for instance', tableId, '...');
        document.addEventListener('alpine:init', () => {
            console.log('TanStack Table: Alpine initialized, registering component', componentName);
            
            // Requirement 8.4: Verify TanStack JS loaded before init
            // Check if TanStack Table library is available (exposed as TableCore)
            // VirtualCore is optional for virtualization features
            if (typeof window.TableCore === 'undefined') {
                console.error('TanStack Table: TanStack Table library not loaded for instance', tableId);
                console.error('TanStack Table: Please ensure TanStack Table JavaScript is included before initializing tables');
                console.error('TanStack Table: Expected window.TableCore to be defined');
                
                // Handle missing dependency gracefully - show error message in table container
                const tableContainer = document.querySelector('[x-data*="' + componentName + '"]');
                if (tableContainer) {
                    tableContainer.innerHTML = `
                        <div class="alert alert-error" role="alert">
                            <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <strong>TanStack Table Error:</strong> TanStack Table library is not loaded. 
                            Please include the TanStack Table JavaScript file before initializing tables.
                        </div>
                    `;
                }
                
                return; // Stop initialization
            }
            
            console.log('TanStack Table: TanStack Table library verified for instance', tableId);
            registerComponent();
            
            // Let Alpine automatically initialize the component (don't call initTree manually)
            console.log('TanStack Table: Component registered, Alpine will auto-initialize');
        });
    }
})();

JS;

        // Output global helper functions only once (shared across all instances)
        // Use static class property to prevent duplicate output
        if (!self::$globalFunctionsOutput) {
            $js .= <<<'JS'

// Global function for action dropdown toggle (scoped by dropdown ID)
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

// Close dropdown when clicking outside (global handler, but works for all instances)
document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-dropdown-container')) {
        document.querySelectorAll('.action-dropdown').forEach(d => {
            d.style.display = 'none';
        });
    }
});

JS;
            self::$globalFunctionsOutput = true;
        }
        
        return $js . '</script>';
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
        // Use unique ID from HashGenerator (Requirement 8.1, 8.2)
        $tableId = $table->getUniqueId();
        
        // Get HTML attributes for RTL support (Requirement 51.11, 52.5)
        $htmlAttributes = $this->themeLocaleIntegration->getHtmlAttributes();
        $bodyClasses = $this->themeLocaleIntegration->getBodyClasses();
        
        // Add card wrapper with Alpine.js data
        $html = '<div class="tanstack-table-card" ';
        $html .= 'dir="' . htmlspecialchars($htmlAttributes['dir']) . '" ';
        $html .= 'x-data="tanstackTable_' . $tableId . '" ';
        $html .= 'x-init="() => { if (typeof init === \'function\') init(); }" ';
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
        // Use unique ID from HashGenerator (Requirement 8.1, 8.2)
        $tableId = $table->getUniqueId();
        
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
            // FIX: Use safer modal targeting with detailed logging
            $html .= 'onclick="(function() { ';
            $html .= 'const modal = document.getElementById(\'' . $tableId . '_filter_modal\'); ';
            $html .= 'if (modal) { modal.dispatchEvent(new CustomEvent(\'open-filter-modal\')); } ';
            $html .= 'else { console.error(\'Filter modal not found: ' . $tableId . '_filter_modal\'); } ';
            $html .= '})(); return false;" ';
            $html .= 'class="px-4 py-2 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg flex items-center gap-2 relative" ';
            $html .= 'style="box-shadow: 0 10px 15px -3px ' . $primaryColor . '40, 0 4px 6px -4px ' . $primaryColor . '40">';
            $html .= '<i data-lucide="filter" class="w-4 h-4"></i>';
            $html .= '<span>' . htmlspecialchars(__('canvastack::components.table.filters')) . '</span>';
            
            // Badge with unique ID for JavaScript updates
            $html .= '<span ';
            $html .= 'id="' . $tableId . '_filter_badge" ';
            $html .= 'class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg" ';
            $html .= 'style="display: ' . ($activeFilterCount > 0 ? 'flex' : 'none') . '">';
            $html .= $activeFilterCount;
            $html .= '</span>';
            
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
            $connection = $config->connection ?? $table->getConnection();
            
            $modalHtml = view('canvastack::components.table.filter-modal', [
                'filters' => $transformedFilters,
                'activeFilters' => $activeFilters,
                'tableName' => $tableName,
                'tableId' => $tableId,
                'activeFilterCount' => $activeFilterCount,
                'showButton' => false,
                'config' => (array) $config,
                'connection' => $connection,
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
        
        // Get unique table ID
        $tableId = $table->getUniqueId();
        
        // Get unique table ID
        $tableId = $table->getUniqueId();
        
        // Empty state - OUTSIDE Alpine x-show wrapper, controlled by pure JavaScript
        $html = '<div id="tanstack-empty-' . $tableId . '" class="tanstack-table-empty" style="display: none;">';
        $html .= '<p>' . __('canvastack::components.table.no_data') . '</p>';
        $html .= '</div>';
        
        // Responsive wrapper with horizontal scroll
        $html .= '<div x-show="!loading && !error" class="tanstack-table-responsive-wrapper">';
        
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
        
        // CRITICAL FIX #71: Remove x-ignore (doesn't work), use delayed injection instead
        // Strategy: Wait for Alpine to finish ALL initializations, THEN inject HTML
        // This prevents Alpine from resetting tbody during its initialization phase
        $html .= '<tbody id="tanstack-tbody-' . $tableId . '" x-ref="tableBody">';
        $html .= '<!-- Rows will be rendered server-side via AJAX -->';
        $html .= '</tbody>';
        
        $html .= '</table>';
        $html .= '</div>'; // Close tanstack-table-desktop
        
        // Mobile card view (visible only on mobile < 768px)
        $html .= $this->renderMobileCardView($table, $selectionEnabled);
        
        $html .= '</div>'; // Close tanstack-table-responsive-wrapper
        
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
        // TEMPORARILY DISABLED: Mobile card view conflicts with server-side rendering
        // TODO: Implement server-side rendering for mobile cards in future update
        // For now, mobile users will see the desktop table (which is responsive)
        return '<!-- Mobile card view temporarily disabled for server-side rendering -->';
        
        /* ORIGINAL CODE - COMMENTED OUT
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
        
        // Render fields for each column (avoid nested x-for)
        $columns = $table->getColumns();
        foreach ($columns as $index => $column) {
            $html .= '<div class="tanstack-mobile-card-field" x-show="!columns[' . $index . '].hidden">';
            $html .= '<div class="tanstack-mobile-card-label" x-text="columns[' . $index . '].header || columns[' . $index . '].label || columns[' . $index . '].id"></div>';
            $html .= '<div class="tanstack-mobile-card-value" x-text="getCellValue(row, columns[' . $index . '])"></div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close tanstack-mobile-card-content
        $html .= '</div>'; // Close tanstack-mobile-card
        $html .= '</template>';
        
        $html .= '</div>'; // Close tanstack-mobile-cards
        $html .= '</div>'; // Close tanstack-table-mobile
        
        return $html;
        */
    }
}

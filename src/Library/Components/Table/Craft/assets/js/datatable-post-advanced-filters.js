/**
 * DataTable POST Advanced Filters Library
 * 
 * Comprehensive JavaScript library for handling advanced filtering
 * in DataTables with POST method support including:
 * - Date range filters with presets
 * - Multi-select dropdown filters
 * - Text search with operators
 * - Custom filter implementations
 * - Filter state management
 * - Performance optimization
 */

(function($) {
    'use strict';

    // Global namespace
    window.DataTableAdvancedFilters = window.DataTableAdvancedFilters || {};

    /**
     * Advanced Filter Manager
     */
    class AdvancedFilterManager {
        constructor(tableId, options = {}) {
            this.tableId = tableId;
            this.options = $.extend({
                enableDateRange: true,
                enableSelectbox: true,
                enableTextSearch: true,
                enableCustomFilters: true,
                autoApply: false,
                debounceDelay: 500,
                cacheEnabled: true,
                performanceMode: false,
                debug: false
            }, options);

            this.filters = {};
            this.filterState = {};
            this.cache = {};
            this.debounceTimers = {};
            this.performanceMetrics = {};

            this.init();
        }

        /**
         * Initialize the filter manager
         */
        init() {
            this.log('Initializing Advanced Filter Manager for table:', this.tableId);
            
            // Initialize filter types
            if (this.options.enableDateRange) {
                this.initDateRangeFilters();
            }
            
            if (this.options.enableSelectbox) {
                this.initSelectboxFilters();
            }
            
            if (this.options.enableTextSearch) {
                this.initTextSearchFilters();
            }
            
            if (this.options.enableCustomFilters) {
                this.initCustomFilters();
            }

            // Setup event handlers
            this.setupEventHandlers();
            
            // Load saved filter state
            this.loadFilterState();
            
            // Performance monitoring
            if (this.options.performanceMode) {
                this.initPerformanceMonitoring();
            }
        }

        /**
         * Initialize date range filters
         */
        initDateRangeFilters() {
            const self = this;
            const dateRangeElements = $(`#${this.tableId}_wrapper .date-range-filter`);
            
            dateRangeElements.each(function() {
                const $element = $(this);
                const filterId = $element.data('filter-id');
                const column = $element.data('column');
                
                // Initialize date picker
                self.initDatePicker($element, filterId, column);
                
                // Add preset buttons
                self.addDatePresets($element, filterId, column);
                
                self.filters[filterId] = {
                    type: 'daterange',
                    column: column,
                    element: $element,
                    value: null
                };
            });
        }

        /**
         * Initialize date picker
         */
        initDatePicker($element, filterId, column) {
            const self = this;
            const $startDate = $element.find('.start-date');
            const $endDate = $element.find('.end-date');
            
            // Configure date picker options
            const datePickerOptions = {
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            };

            // Initialize date pickers
            $startDate.datepicker(datePickerOptions);
            $endDate.datepicker(datePickerOptions);

            // Event handlers
            $startDate.on('changeDate', function(e) {
                self.handleDateRangeChange(filterId, 'start', e.date);
            });

            $endDate.on('changeDate', function(e) {
                self.handleDateRangeChange(filterId, 'end', e.date);
            });

            // Clear button
            $element.find('.clear-date-range').on('click', function() {
                self.clearDateRange(filterId);
            });
        }

        /**
         * Add date presets
         */
        addDatePresets($element, filterId, column) {
            const self = this;
            const presets = [
                { key: 'today', label: 'Today' },
                { key: 'yesterday', label: 'Yesterday' },
                { key: 'this_week', label: 'This Week' },
                { key: 'last_week', label: 'Last Week' },
                { key: 'this_month', label: 'This Month' },
                { key: 'last_month', label: 'Last Month' },
                { key: 'last_7_days', label: 'Last 7 Days' },
                { key: 'last_30_days', label: 'Last 30 Days' }
            ];

            const $presetsContainer = $element.find('.date-presets');
            if ($presetsContainer.length === 0) {
                $element.append('<div class="date-presets mt-2"></div>');
            }

            const $presets = $element.find('.date-presets');
            presets.forEach(preset => {
                const $button = $(`<button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-preset="${preset.key}">${preset.label}</button>`);
                $button.on('click', function() {
                    self.applyDatePreset(filterId, preset.key);
                });
                $presets.append($button);
            });
        }

        /**
         * Handle date range change
         */
        handleDateRangeChange(filterId, type, date) {
            if (!this.filters[filterId].value) {
                this.filters[filterId].value = {};
            }

            this.filters[filterId].value[type] = date ? this.formatDate(date) : null;
            
            // Validate date range
            if (this.validateDateRange(filterId)) {
                this.updateFilterState(filterId);
                
                if (this.options.autoApply) {
                    this.applyFilters();
                }
            }
        }

        /**
         * Apply date preset
         */
        applyDatePreset(filterId, preset) {
            const dateRange = this.calculateDatePreset(preset);
            
            if (dateRange) {
                this.filters[filterId].value = dateRange;
                
                // Update UI
                const $element = this.filters[filterId].element;
                $element.find('.start-date').datepicker('setDate', dateRange.start);
                $element.find('.end-date').datepicker('setDate', dateRange.end);
                
                this.updateFilterState(filterId);
                
                if (this.options.autoApply) {
                    this.applyFilters();
                }
            }
        }

        /**
         * Calculate date preset values
         */
        calculateDatePreset(preset) {
            const now = new Date();
            let start, end;

            switch (preset) {
                case 'today':
                    start = end = new Date(now);
                    break;
                    
                case 'yesterday':
                    start = end = new Date(now.getTime() - 24 * 60 * 60 * 1000);
                    break;
                    
                case 'this_week':
                    start = new Date(now.getTime() - (now.getDay() * 24 * 60 * 60 * 1000));
                    end = new Date(start.getTime() + (6 * 24 * 60 * 60 * 1000));
                    break;
                    
                case 'last_week':
                    const lastWeekStart = new Date(now.getTime() - ((now.getDay() + 7) * 24 * 60 * 60 * 1000));
                    start = lastWeekStart;
                    end = new Date(lastWeekStart.getTime() + (6 * 24 * 60 * 60 * 1000));
                    break;
                    
                case 'this_month':
                    start = new Date(now.getFullYear(), now.getMonth(), 1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    break;
                    
                case 'last_month':
                    start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    end = new Date(now.getFullYear(), now.getMonth(), 0);
                    break;
                    
                case 'last_7_days':
                    start = new Date(now.getTime() - (7 * 24 * 60 * 60 * 1000));
                    end = new Date(now);
                    break;
                    
                case 'last_30_days':
                    start = new Date(now.getTime() - (30 * 24 * 60 * 60 * 1000));
                    end = new Date(now);
                    break;
                    
                default:
                    return null;
            }

            return {
                start: this.formatDate(start),
                end: this.formatDate(end)
            };
        }

        /**
         * Initialize selectbox filters
         */
        initSelectboxFilters() {
            const self = this;
            const selectboxElements = $(`#${this.tableId}_wrapper .selectbox-filter`);
            
            selectboxElements.each(function() {
                const $element = $(this);
                const filterId = $element.data('filter-id');
                const column = $element.data('column');
                const multiple = $element.data('multiple') === true;
                const source = $element.data('source');
                
                // Initialize select2 or custom dropdown
                self.initSelectbox($element, filterId, column, multiple, source);
                
                self.filters[filterId] = {
                    type: 'selectbox',
                    column: column,
                    element: $element,
                    multiple: multiple,
                    source: source,
                    value: multiple ? [] : null
                };
            });
        }

        /**
         * Initialize selectbox
         */
        initSelectbox($element, filterId, column, multiple, source) {
            const self = this;
            const $select = $element.find('select');
            
            // Configure Select2 options
            const select2Options = {
                placeholder: 'Select options...',
                allowClear: true,
                width: '100%',
                multiple: multiple
            };

            // Add AJAX source if specified
            if (source) {
                select2Options.ajax = {
                    url: this.getSelectboxSourceUrl(source),
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function(params) {
                        return {
                            search_term: params.term,
                            page: params.page || 1,
                            source: source,
                            column: column,
                            table_id: self.tableId
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data || [],
                            pagination: {
                                more: data.pagination && data.pagination.more
                            }
                        };
                    },
                    cache: this.options.cacheEnabled
                };
            }

            // Initialize Select2
            $select.select2(select2Options);

            // Event handlers
            $select.on('change', function() {
                const value = $(this).val();
                self.filters[filterId].value = multiple ? (value || []) : value;
                self.updateFilterState(filterId);
                
                if (self.options.autoApply) {
                    self.applyFilters();
                }
            });

            // Clear button
            $element.find('.clear-selectbox').on('click', function() {
                $select.val(null).trigger('change');
            });
        }

        /**
         * Initialize text search filters
         */
        initTextSearchFilters() {
            const self = this;
            const textElements = $(`#${this.tableId}_wrapper .text-search-filter`);
            
            textElements.each(function() {
                const $element = $(this);
                const filterId = $element.data('filter-id');
                const column = $element.data('column');
                const operator = $element.data('operator') || 'like';
                
                self.initTextSearch($element, filterId, column, operator);
                
                self.filters[filterId] = {
                    type: 'text',
                    column: column,
                    element: $element,
                    operator: operator,
                    value: null
                };
            });
        }

        /**
         * Initialize text search
         */
        initTextSearch($element, filterId, column, operator) {
            const self = this;
            const $input = $element.find('input[type="text"]');
            const $operatorSelect = $element.find('.operator-select');
            
            // Setup operator dropdown if exists
            if ($operatorSelect.length > 0) {
                $operatorSelect.on('change', function() {
                    self.filters[filterId].operator = $(this).val();
                    self.updateFilterState(filterId);
                    
                    if (self.options.autoApply && self.filters[filterId].value) {
                        self.applyFilters();
                    }
                });
            }

            // Setup input with debouncing
            $input.on('input', function() {
                const value = $(this).val().trim();
                
                // Clear existing timer
                if (self.debounceTimers[filterId]) {
                    clearTimeout(self.debounceTimers[filterId]);
                }
                
                // Set new timer
                self.debounceTimers[filterId] = setTimeout(function() {
                    self.filters[filterId].value = value || null;
                    self.updateFilterState(filterId);
                    
                    if (self.options.autoApply) {
                        self.applyFilters();
                    }
                }, self.options.debounceDelay);
            });

            // Clear button
            $element.find('.clear-text-search').on('click', function() {
                $input.val('').trigger('input');
            });
        }

        /**
         * Initialize custom filters
         */
        initCustomFilters() {
            const self = this;
            const customElements = $(`#${this.tableId}_wrapper .custom-filter`);
            
            customElements.each(function() {
                const $element = $(this);
                const filterId = $element.data('filter-id');
                const column = $element.data('column');
                const customType = $element.data('custom-type');
                
                // Call custom initialization function if exists
                const initFunction = window[`init${customType}Filter`];
                if (typeof initFunction === 'function') {
                    initFunction($element, filterId, column, self);
                }
                
                self.filters[filterId] = {
                    type: 'custom',
                    customType: customType,
                    column: column,
                    element: $element,
                    value: null
                };
            });
        }

        /**
         * Setup global event handlers
         */
        setupEventHandlers() {
            const self = this;
            
            // Apply filters button
            $(`#${this.tableId}_wrapper .apply-filters`).on('click', function() {
                self.applyFilters();
            });
            
            // Clear all filters button
            $(`#${this.tableId}_wrapper .clear-all-filters`).on('click', function() {
                self.clearAllFilters();
            });
            
            // Save filter state button
            $(`#${this.tableId}_wrapper .save-filter-state`).on('click', function() {
                self.saveFilterState();
            });
            
            // Load filter state button
            $(`#${this.tableId}_wrapper .load-filter-state`).on('click', function() {
                self.loadFilterState();
            });
        }

        /**
         * Apply all filters
         */
        applyFilters() {
            const startTime = performance.now();
            
            try {
                const filterData = this.buildFilterData();
                const table = $(`#${this.tableId}`).DataTable();
                
                if (table) {
                    // Update AJAX data function
                    const originalAjaxData = table.settings()[0].ajax.data;
                    
                    table.settings()[0].ajax.data = function(data) {
                        // Call original data function if exists
                        if (typeof originalAjaxData === 'function') {
                            data = originalAjaxData(data);
                        }
                        
                        // Add filter data
                        data.filters = JSON.stringify(filterData);
                        data._filter_timestamp = new Date().getTime();
                        
                        return data;
                    };
                    
                    // Reload table
                    table.ajax.reload();
                    
                    // Update filter state
                    this.filterState = filterData;
                    this.saveFilterState();
                    
                    this.log('Filters applied successfully', filterData);
                }
                
            } catch (error) {
                console.error('Error applying filters:', error);
                this.showError('Failed to apply filters. Please try again.');
            }
            
            // Performance tracking
            const endTime = performance.now();
            this.trackPerformance('apply_filters', endTime - startTime);
        }

        /**
         * Build filter data for POST request
         */
        buildFilterData() {
            const filterData = {};
            
            Object.keys(this.filters).forEach(filterId => {
                const filter = this.filters[filterId];
                
                if (this.hasFilterValue(filter)) {
                    filterData[filterId] = {
                        column: filter.column,
                        type: filter.type,
                        value: filter.value,
                        operator: filter.operator || 'like',
                        relate: filter.relate || false
                    };
                    
                    // Add custom type for custom filters
                    if (filter.type === 'custom') {
                        filterData[filterId].customType = filter.customType;
                    }
                }
            });
            
            return filterData;
        }

        /**
         * Check if filter has value
         */
        hasFilterValue(filter) {
            if (filter.value === null || filter.value === undefined) {
                return false;
            }
            
            if (Array.isArray(filter.value)) {
                return filter.value.length > 0;
            }
            
            if (typeof filter.value === 'object') {
                return Object.keys(filter.value).some(key => 
                    filter.value[key] !== null && filter.value[key] !== undefined
                );
            }
            
            if (typeof filter.value === 'string') {
                return filter.value.trim().length > 0;
            }
            
            return true;
        }

        /**
         * Clear all filters
         */
        clearAllFilters() {
            Object.keys(this.filters).forEach(filterId => {
                this.clearFilter(filterId);
            });
            
            if (this.options.autoApply) {
                this.applyFilters();
            }
        }

        /**
         * Clear specific filter
         */
        clearFilter(filterId) {
            const filter = this.filters[filterId];
            
            if (!filter) return;
            
            switch (filter.type) {
                case 'daterange':
                    this.clearDateRange(filterId);
                    break;
                    
                case 'selectbox':
                    filter.element.find('select').val(null).trigger('change');
                    break;
                    
                case 'text':
                    filter.element.find('input[type="text"]').val('').trigger('input');
                    break;
                    
                case 'custom':
                    // Call custom clear function if exists
                    const clearFunction = window[`clear${filter.customType}Filter`];
                    if (typeof clearFunction === 'function') {
                        clearFunction(filter.element, filterId, this);
                    }
                    break;
            }
        }

        /**
         * Clear date range filter
         */
        clearDateRange(filterId) {
            const filter = this.filters[filterId];
            if (filter && filter.type === 'daterange') {
                filter.element.find('.start-date, .end-date').datepicker('clearDates');
                filter.value = null;
                this.updateFilterState(filterId);
            }
        }

        /**
         * Update filter state
         */
        updateFilterState(filterId) {
            const filter = this.filters[filterId];
            if (filter) {
                this.filterState[filterId] = {
                    type: filter.type,
                    column: filter.column,
                    value: filter.value,
                    operator: filter.operator,
                    timestamp: new Date().toISOString()
                };
            }
        }

        /**
         * Save filter state to localStorage
         */
        saveFilterState() {
            if (this.options.cacheEnabled) {
                const stateKey = `datatable_filter_state_${this.tableId}`;
                localStorage.setItem(stateKey, JSON.stringify(this.filterState));
                this.log('Filter state saved');
            }
        }

        /**
         * Load filter state from localStorage
         */
        loadFilterState() {
            if (this.options.cacheEnabled) {
                const stateKey = `datatable_filter_state_${this.tableId}`;
                const savedState = localStorage.getItem(stateKey);
                
                if (savedState) {
                    try {
                        const state = JSON.parse(savedState);
                        this.restoreFilterState(state);
                        this.log('Filter state loaded', state);
                    } catch (error) {
                        console.error('Error loading filter state:', error);
                    }
                }
            }
        }

        /**
         * Restore filter state
         */
        restoreFilterState(state) {
            Object.keys(state).forEach(filterId => {
                const filterState = state[filterId];
                const filter = this.filters[filterId];
                
                if (filter && filterState) {
                    this.restoreFilterValue(filter, filterState);
                }
            });
        }

        /**
         * Restore individual filter value
         */
        restoreFilterValue(filter, state) {
            switch (filter.type) {
                case 'daterange':
                    if (state.value && state.value.start && state.value.end) {
                        filter.element.find('.start-date').datepicker('setDate', state.value.start);
                        filter.element.find('.end-date').datepicker('setDate', state.value.end);
                        filter.value = state.value;
                    }
                    break;
                    
                case 'selectbox':
                    if (state.value) {
                        filter.element.find('select').val(state.value).trigger('change');
                    }
                    break;
                    
                case 'text':
                    if (state.value) {
                        filter.element.find('input[type="text"]').val(state.value);
                        filter.value = state.value;
                    }
                    break;
            }
        }

        /**
         * Utility functions
         */
        formatDate(date) {
            if (!(date instanceof Date)) {
                date = new Date(date);
            }
            return date.toISOString().split('T')[0];
        }

        validateDateRange(filterId) {
            const filter = this.filters[filterId];
            if (filter && filter.type === 'daterange' && filter.value) {
                const start = new Date(filter.value.start);
                const end = new Date(filter.value.end);
                
                if (start > end) {
                    this.showError('Start date cannot be after end date');
                    return false;
                }
            }
            return true;
        }

        getSelectboxSourceUrl(source) {
            return window.location.origin + '/ajax/selectbox-options';
        }

        showError(message) {
            // Implement your error display logic here
            console.error(message);
            
            // Example: show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            }
        }

        trackPerformance(operation, duration) {
            if (this.options.performanceMode) {
                if (!this.performanceMetrics[operation]) {
                    this.performanceMetrics[operation] = [];
                }
                
                this.performanceMetrics[operation].push({
                    duration: duration,
                    timestamp: new Date().toISOString()
                });
                
                // Keep only last 100 entries
                if (this.performanceMetrics[operation].length > 100) {
                    this.performanceMetrics[operation].shift();
                }
                
                this.log(`Performance: ${operation} took ${duration.toFixed(2)}ms`);
            }
        }

        initPerformanceMonitoring() {
            // Monitor memory usage
            setInterval(() => {
                if (performance.memory) {
                    this.log('Memory usage:', {
                        used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) + 'MB',
                        total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024) + 'MB'
                    });
                }
            }, 30000); // Every 30 seconds
        }

        log(...args) {
            if (this.options.debug) {
                console.log(`[AdvancedFilterManager:${this.tableId}]`, ...args);
            }
        }

        /**
         * Public API methods
         */
        getFilterValue(filterId) {
            return this.filters[filterId] ? this.filters[filterId].value : null;
        }

        setFilterValue(filterId, value) {
            if (this.filters[filterId]) {
                this.filters[filterId].value = value;
                this.updateFilterState(filterId);
                
                // Update UI based on filter type
                this.updateFilterUI(filterId, value);
            }
        }

        updateFilterUI(filterId, value) {
            const filter = this.filters[filterId];
            if (!filter) return;
            
            switch (filter.type) {
                case 'daterange':
                    if (value && value.start && value.end) {
                        filter.element.find('.start-date').datepicker('setDate', value.start);
                        filter.element.find('.end-date').datepicker('setDate', value.end);
                    }
                    break;
                    
                case 'selectbox':
                    filter.element.find('select').val(value).trigger('change');
                    break;
                    
                case 'text':
                    filter.element.find('input[type="text"]').val(value || '');
                    break;
            }
        }

        getPerformanceMetrics() {
            return this.performanceMetrics;
        }

        destroy() {
            // Clear timers
            Object.values(this.debounceTimers).forEach(timer => {
                if (timer) clearTimeout(timer);
            });
            
            // Remove event handlers
            $(`#${this.tableId}_wrapper .apply-filters, .clear-all-filters, .save-filter-state, .load-filter-state`).off();
            
            // Destroy Select2 instances
            Object.values(this.filters).forEach(filter => {
                if (filter.type === 'selectbox') {
                    filter.element.find('select').select2('destroy');
                }
            });
            
            this.log('Advanced Filter Manager destroyed');
        }
    }

    // Export to global namespace
    window.DataTableAdvancedFilters.Manager = AdvancedFilterManager;

    // jQuery plugin
    $.fn.advancedFilters = function(options) {
        return this.each(function() {
            const $table = $(this);
            const tableId = $table.attr('id');
            
            if (tableId) {
                const manager = new AdvancedFilterManager(tableId, options);
                $table.data('advancedFilters', manager);
            }
        });
    };

})(jQuery);
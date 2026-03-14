/**
 * FilterModal - Main Alpine.js component for filter modal.
 * 
 * Integrates all filter components (Cache, Cascade, Flatpickr) into
 * a single Alpine.js component for use in Blade templates.
 */

import { FilterCache } from './FilterCache.js';
import { FilterCascade } from './FilterCascade.js';
import { FilterFlatpickr } from './FilterFlatpickr.js';
import { debounce } from '../../utils/debounce.js';
import { fetchWithCsrf } from '../../utils/fetch.js';
import TomSelect from 'tom-select';

/**
 * Create filter modal Alpine.js component.
 * 
 * @param {Object} config - Configuration object
 * @param {Array} config.filters - Filter configurations
 * @param {Object} config.activeFilters - Currently active filters
 * @param {string} config.tableName - Table name for session storage
 * @param {number} config.activeFilterCount - Number of active filters
 * @param {Object} config.config - Table configuration
 * @param {string} config.connection - Database connection name
 * @param {string} config.filterOptionsRoute - Route for fetching filter options
 * @param {string} config.saveFiltersRoute - Route for saving filters
 * @param {number} config.cacheTTL - Cache TTL in milliseconds
 * @param {number} config.debounceDelay - Debounce delay in milliseconds
 * @returns {Object} Alpine.js component
 */
export function createFilterModal(config) {
    return {
        // ========================================
        // STATE
        // ========================================
        open: false,
        filters: config.filters || [],
        filterValues: config.activeFilters || {},
        activeFilterCount: config.activeFilterCount || 0,
        isApplying: false,
        isRestoring: false,
        connection: config.connection || null,
        tableName: config.tableName || '',
        tableId: config.tableId || '',
        
        // Animation
        slideAnimation: 'translateY(-2rem)',
        slideAnimationClass: '-translate-y-8',
        
        // Configuration
        config: config.config || {},
        
        // Routes
        filterOptionsRoute: config.filterOptionsRoute,
        saveFiltersRoute: config.saveFiltersRoute,
        
        // ========================================
        // DEPENDENCIES (Reusable Components)
        // ========================================
        cache: null,
        cascade: null,
        flatpickr: null,
        tomSelectInstances: {},
        debouncedHandleFilterChange: null,
        
        // ========================================
        // INITIALIZATION
        // ========================================
        
        /**
         * Initialize the filter modal component.
         * 
         * Called automatically by Alpine.js when component is mounted.
         */
        init() {
            console.log('=== FILTER MODAL INIT START (EXTERNAL JS) ===');
            console.log('Alpine init() called at:', new Date().toISOString());
            
            // Read connection from data attribute if not provided
            if (!this.connection) {
                const attrConnection = this.$el.getAttribute('data-connection');
                const datasetConnection = this.$el.dataset.connection;
                this.connection = attrConnection || datasetConnection || null;
            }
            
            console.log('✅ Filter modal initialized with connection:', this.connection);
            
            // Initialize dependencies
            this.initializeDependencies();
            
            // Initialize date filters
            this.initializeDateFilters();
            
            // Initialize debounced handler
            this.debouncedHandleFilterChange = debounce(
                this.handleFilterChangeBidirectional.bind(this),
                config.debounceDelay || 300
            );
            
            // Update active count
            this.updateActiveCount();
            
            console.log('📊 Initial state:', {
                filterValues: this.filterValues,
                activeFilterCount: this.activeFilterCount,
                configActiveFilterCount: config.activeFilterCount
            });
            
            // Load initial options
            this.loadInitialOptions();
            
            // Load filters from session
            this.loadFiltersFromSession();
            
            // Show toast if filters are active
            if (this.activeFilterCount > 0) {
                this.showFilterActiveToast();
            }
            
            // Watch for modal open/close
            this.setupModalWatchers();
            
            // Listen for custom events
            this.setupEventListeners();
            
            console.log('=== FILTER MODAL INIT END ===');
        },
        
        /**
         * Initialize reusable dependencies.
         */
        initializeDependencies() {
            // Initialize cache
            this.cache = new FilterCache(
                config.cacheTTL || 300000,  // 5 minutes default
                100  // max 100 entries
            );
            
            // Initialize cascade with fetchOptions bound to this context
            this.cascade = new FilterCascade(
                this.filters,
                this.config,
                this.fetchFilterOptionsWithCache.bind(this)
            );
            
            // Initialize Flatpickr
            this.flatpickr = new FilterFlatpickr();
            
            // Initialize Tom Select instances storage
            this.tomSelectInstances = {};
            
            console.log('✅ Dependencies initialized:', {
                cache: 'FilterCache',
                cascade: 'FilterCascade',
                flatpickr: 'FilterFlatpickr',
                tomSelect: 'TomSelect (ready)'
            });
        },
        
        /**
         * Initialize date filters with availableDates property.
         */
        initializeDateFilters() {
            this.filters.forEach(filter => {
                if (filter.type === 'datebox' || filter.type === 'daterangebox') {
                    filter.availableDates = filter.availableDates || [];
                    filter.minDate = filter.minDate || null;
                    filter.maxDate = filter.maxDate || null;
                    filter.dateCount = filter.dateCount || 0;
                }
            });
        },
        
        /**
         * Setup modal open/close watchers.
         */
        setupModalWatchers() {
            this.$watch('open', (value) => {
                if (value) {
                    console.log('Modal opened via x-watch');
                    this.onModalOpen();
                } else {
                    this.onModalClose();
                }
            });
            
            // Watch isApplying state to disable/enable inputs
            this.$watch('isApplying', (value) => {
                if (value) {
                    console.log('🔒 Disabling all inputs (isApplying = true)');
                    this.disableAllTomSelects();
                } else {
                    console.log('🔓 Enabling all inputs (isApplying = false)');
                    this.enableAllTomSelects();
                }
            });
            
            // Watch cascade processing state to disable/enable inputs
            this.$watch('cascadeState.isProcessing', (value) => {
                if (value) {
                    console.log('🔒 Disabling all inputs (cascade processing)');
                    this.disableAllTomSelects();
                } else {
                    console.log('🔓 Enabling all inputs (cascade complete)');
                    this.enableAllTomSelects();
                }
            });
        },
        
        /**
         * Setup event listeners for custom events.
         */
        setupEventListeners() {
            // Listen for custom open event
            this.$el.addEventListener('open-filter-modal', () => {
                console.log('Modal opened via custom event');
                this.open = true;
                this.restoreFilterOptions();
            });
            
            // Listen for filters-restored event from TanStack table
            window.addEventListener('filters-restored', (event) => {
                console.log('Filter modal: Received filters-restored event', event.detail);
                
                if (event.detail && event.detail.filters) {
                    // Update filter values
                    this.filterValues = { ...event.detail.filters };
                    
                    // Update active count
                    this.updateActiveCount();
                    console.log('Filter modal: Updated filterValues', this.filterValues);
                    console.log('Filter modal: Updated activeFilterCount', this.activeFilterCount);
                    
                    // CRITICAL: Update TanStack table filter badge
                    // Use setTimeout to ensure DOM is ready and avoid Alpine.js reactivity issues
                    setTimeout(() => {
                        console.log('🔄 Updating badge after filters restored - activeFilterCount:', this.activeFilterCount);
                        
                        // MANUAL DOM UPDATE: Find TanStack table filter badge by ID
                        // Badge ID format: {tableId}_filter_badge
                        const badgeId = this.tableId + '_filter_badge';
                        console.log('🔍 Searching for badge with ID:', badgeId);
                        
                        const badgeElement = document.getElementById(badgeId);
                        console.log('🔍 Badge element found:', badgeElement);
                        
                        if (badgeElement) {
                            badgeElement.style.display = this.activeFilterCount > 0 ? 'flex' : 'none';
                            badgeElement.textContent = this.activeFilterCount;
                            console.log('✅ TanStack badge manually updated:', {
                                id: badgeId,
                                display: badgeElement.style.display,
                                textContent: badgeElement.textContent
                            });
                        } else {
                            console.error('❌ TanStack badge NOT FOUND with ID:', badgeId);
                            console.log('🔍 All elements with "badge" in ID:', 
                                Array.from(document.querySelectorAll('[id*="badge"]')).map(el => el.id)
                            );
                        }
                        
                        // Show toast after badge is updated
                        if (this.activeFilterCount > 0) {
                            this.showFilterActiveToast();
                        }
                    }, 100); // Small delay to ensure DOM is ready
                }
            });
        },
        
        // ========================================
        // MODAL LIFECYCLE
        // ========================================
        
        /**
         * Handle modal open event.
         */
        onModalOpen() {
            // Set random slide animation
            this.setRandomSlideAnimation();
            
            // Apply initial animation state
            const modal = this.$refs.modalContent;
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = this.slideAnimation + ' scale(0.95)';
                
                // Trigger animation on next frame
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        modal.style.opacity = '1';
                        modal.style.transform = 'translate(0, 0) scale(1)';
                    });
                    
                    // Initialize Flatpickr after modal opens
                    this.flatpickr.initAll(
                        this.filters,
                        this.filterValues,
                        this.handleFilterChange.bind(this)
                    );
                    
                    // Initialize Tom Select with slight delay to ensure DOM is ready
                    setTimeout(() => {
                        this.initializeTomSelect();
                        
                        // Restore filter options after Tom Select is initialized
                        this.restoreFilterOptions();
                    }, 100);
                });
            }
        },
        
        /**
         * Handle modal close event.
         */
        onModalClose() {
            // Destroy Tom Select instances
            this.destroyTomSelect();
            
            // Reset to initial state
            const modal = this.$refs.modalContent;
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = 'translateY(-2rem) scale(0.95)';
            }
            
            // Destroy Flatpickr instances
            this.flatpickr.destroyAll();
        },
        
        /**
         * Set random slide animation for modal entrance.
         */
        setRandomSlideAnimation() {
            const animations = [
                { transform: 'translateY(-2rem)', class: '-translate-y-8' },
                { transform: 'translateY(2rem)', class: 'translate-y-8' },
                { transform: 'translateX(-2rem)', class: '-translate-x-8' },
                { transform: 'translateX(2rem)', class: 'translate-x-8' },
                { transform: 'translate(-1.5rem, -1.5rem)', class: '-translate-x-6 -translate-y-6' },
                { transform: 'translate(1.5rem, -1.5rem)', class: 'translate-x-6 -translate-y-6' },
                { transform: 'translate(-1.5rem, 1.5rem)', class: '-translate-x-6 translate-y-6' },
                { transform: 'translate(1.5rem, 1.5rem)', class: 'translate-x-6 translate-y-6' },
            ];
            
            const random = animations[Math.floor(Math.random() * animations.length)];
            this.slideAnimation = random.transform;
            this.slideAnimationClass = random.class;
            
            console.log('Random slide animation:', random.transform, 'Class:', random.class);
        },
        
        // ========================================
        // FILTER OPTIONS LOADING
        // ========================================
        
        /**
         * Load initial options for all selectbox filters.
         */
        async loadInitialOptions() {
            console.log('🔄 loadInitialOptions() START');
            console.log('🔍 Current connection value:', this.connection);
            console.log('🔍 Filters to load:', this.filters);
            
            for (const filter of this.filters) {
                console.log(`🔍 Processing filter: ${filter.column}, type: ${filter.type}, has options: ${filter.options ? filter.options.length : 0}`);
                
                if (filter.type === 'selectbox' && (!filter.options || filter.options.length === 0)) {
                    filter.loading = true;
                    console.log(`⏳ Loading options for ${filter.column}...`);
                    
                    try {
                        const requestBody = {
                            table: this.tableName,
                            column: filter.column,
                            parentFilters: {},
                            connection: this.connection
                        };
                        
                        console.log(`📤 Sending request for ${filter.column}:`, requestBody);
                        
                        const data = await fetchWithCsrf(this.filterOptionsRoute, {
                            method: 'POST',
                            body: requestBody
                        });
                        
                        console.log(`📥 Received data for ${filter.column}:`, data);
                        
                        filter.options = data.options;
                        console.log(`✅ Loaded ${data.options.length} options for ${filter.column}`);
                        
                        // Populate Tom Select if instance exists
                        this.populateSingleTomSelect(filter);
                    } catch (error) {
                        console.error(`❌ Error loading initial options for ${filter.column}:`, error);
                    } finally {
                        filter.loading = false;
                    }
                }
            }
            
            console.log('🔄 loadInitialOptions() END');
        },
        
        /**
         * Fetch filter options with caching.
         * 
         * @param {Object} filter - Filter configuration
         * @param {Object} parentFilters - Parent filter values
         * @returns {Promise<Object>} Filter options data
         */
        async fetchFilterOptionsWithCache(filter, parentFilters) {
            // Generate cache key
            const cacheKey = this.cache.generateKey(filter.column, parentFilters);
            
            // Try to get cached options
            const cached = this.cache.get(cacheKey);
            
            if (cached) {
                // Show cached options immediately
                console.log(`Using cached options for ${filter.column}`);
                filter.loading = false;
                
                // Apply cached data
                if (cached.type === 'date_range') {
                    filter.minDate = cached.min;
                    filter.maxDate = cached.max;
                    filter.dateCount = cached.count;
                    filter.availableDates = cached.availableDates || [];
                    
                    // Update Flatpickr with cached dates
                    this.$nextTick(() => {
                        this.flatpickr.update(filter, this.filterValues);
                    });
                } else if (cached.type === 'options') {
                    filter.options = cached.options;
                }
            } else {
                // No cache, show loading spinner
                filter.loading = true;
            }
            
            // Fetch fresh data in background
            try {
                const requestBody = {
                    table: this.tableName,
                    column: filter.column,
                    parentFilters: parentFilters,
                    type: filter.type,
                    connection: this.connection
                };
                
                console.log(`Fetching fresh options for ${filter.column}:`, requestBody);
                
                const data = await fetchWithCsrf(this.filterOptionsRoute, {
                    method: 'POST',
                    body: requestBody
                });
                
                console.log(`Received fresh data for ${filter.column}:`, data);
                
                // Cache the fresh data
                this.cache.set(cacheKey, data);
                
                // Update filter with fresh data
                if (data.type === 'date_range') {
                    filter.minDate = data.min;
                    filter.maxDate = data.max;
                    filter.dateCount = data.count;
                    filter.availableDates = data.availableDates || [];
                    
                    // Update Flatpickr
                    this.$nextTick(() => {
                        this.flatpickr.update(filter, this.filterValues);
                    });
                    
                    // Clear date value if not in available dates
                    if (this.filterValues[filter.column]) {
                        if (filter.availableDates && filter.availableDates.length > 0) {
                            if (!filter.availableDates.includes(this.filterValues[filter.column])) {
                                console.log(`Clearing invalid date for ${filter.column}`);
                                delete this.filterValues[filter.column]; // Delete key from object
                            }
                        }
                    }
                } else if (data.type === 'options') {
                    filter.options = data.options;
                    
                    // Update Tom Select with new options
                    this.$nextTick(() => {
                        this.populateSingleTomSelect(filter);
                    });
                    
                    // Clear value if not in new options
                    if (this.filterValues[filter.column]) {
                        const hasOption = filter.options.some(opt => opt.value === this.filterValues[filter.column]);
                        if (!hasOption) {
                            console.log(`Clearing invalid value for ${filter.column}`);
                            delete this.filterValues[filter.column]; // Delete key from object
                            
                            // Clear Tom Select UI
                            const tomSelectInstance = this.tomSelectInstances[`filter_${filter.column}`];
                            if (tomSelectInstance) {
                                tomSelectInstance.clear();
                                console.log(`Tom Select UI cleared for ${filter.column}`);
                            }
                        }
                    }
                }
                
                return data;
                
            } catch (error) {
                console.error(`Error fetching options for ${filter.column}:`, error);
                
                // If we have cached data, keep using it despite the error
                if (cached) {
                    console.log(`Using cached data despite fetch error for ${filter.column}`);
                    return cached;
                }
                
                throw error;
            } finally {
                filter.loading = false;
            }
        },
        
        // ========================================
        // FILTER CHANGE HANDLING
        // ========================================
        
        /**
         * Handle filter change with bidirectional cascade.
         * 
         * @param {Object} filter - The filter that changed
         */
        async handleFilterChangeBidirectional(filter) {
            const value = this.filterValues[filter.column];
            
            // Execute cascade using FilterCascade component
            await this.cascade.execute(filter, value, this.filterValues);
            
            // Update active count
            this.updateActiveCount();
        },
        
        /**
         * Handle filter change (wrapper for debounced handler).
         * 
         * @param {Object} filter - The filter that changed
         */
        handleFilterChange(filter) {
            this.debouncedHandleFilterChange(filter);
        },
        
        // ========================================
        // FILTER ACTIONS
        // ========================================
        
        /**
         * Apply filters and reload table.
         */
        async applyFilters() {
            // Guard: Prevent multiple simultaneous apply operations
            if (this.isApplying || this.cascadeState.isProcessing) {
                console.warn('⚠️ Cannot apply filters: operation already in progress');
                return;
            }
            
            this.isApplying = true;
            
            try {
                // Convert Proxy to plain object
                const plainFilters = {};
                Object.keys(this.filterValues).forEach(key => {
                    if (this.filterValues[key] !== '' && this.filterValues[key] !== null && this.filterValues[key] !== undefined) {
                        plainFilters[key] = this.filterValues[key];
                    }
                });
                
                console.log('Applying filters (plain):', plainFilters);
                
                // Save to session
                await fetchWithCsrf(this.saveFiltersRoute, {
                    method: 'POST',
                    body: {
                        table: this.tableName,
                        filters: plainFilters
                    }
                });
                
                // Set global filter variable for DataTables AJAX
                window['tableFilters_' + this.tableId] = plainFilters;
                console.log('Set global filters:', window['tableFilters_' + this.tableId]);
                
                // Dispatch event for TanStack table
                window.dispatchEvent(new CustomEvent('filters-applied', {
                    detail: { filters: plainFilters, tableId: this.tableId }
                }));
                console.log('Dispatched filters-applied event');
                
                // Reload DataTable
                if (window.dataTable) {
                    console.log('Reloading window.dataTable');
                    window.dataTable.ajax.reload();
                } else if (window['dataTable_' + this.tableId]) {
                    console.log('Reloading dataTable_' + this.tableId);
                    window['dataTable_' + this.tableId].ajax.reload();
                }
                
                this.updateActiveCount();
                this.open = false;
                
                // Show toast notification if filters are active
                if (this.activeFilterCount > 0) {
                    this.showFilterActiveToast();
                }
                
                // Show success notification
                if (window.showNotification) {
                    window.showNotification('success', 'Filters applied successfully');
                }
                
            } catch (error) {
                console.error('Error applying filters:', error);
                
                if (window.showNotification) {
                    window.showNotification('error', 'Failed to apply filters');
                }
            } finally {
                this.isApplying = false;
            }
        },
        
        /**
         * Clear all filters.
         */
        async clearFilters() {
            // Guard: Prevent clearing while operation in progress
            if (this.isApplying || this.cascadeState.isProcessing) {
                console.warn('⚠️ Cannot clear filters: operation already in progress');
                return;
            }
            
            console.log('Clearing all filters');
            
            // Clear filter values
            this.filterValues = {};
            
            // Reset all filter options to initial state
            this.filters.forEach(filter => {
                if (filter.type === 'selectbox') {
                    // Keep initial options, just clear selection
                    // Options will be reloaded on next modal open
                }
            });
            
            // Update active count and badge
            this.updateActiveCount();
            
            // Apply empty filters (will reload table with no filters)
            await this.applyFilters();
        },
        
        /**
         * Remove a specific filter.
         * 
         * @param {string} column - Filter column to remove
         */
        removeFilter(column) {
            console.log('Removing filter:', column);
            
            // Delete the filter value (remove key from object)
            delete this.filterValues[column];
            
            // Find the filter and trigger cascade
            const filter = this.filters.find(f => f.column === column);
            if (filter) {
                this.handleFilterChange(filter);
            }
            
            // Update active count and badge
            this.updateActiveCount();
        },
        
        // ========================================
        // HELPER METHODS
        // ========================================
        
        /**
         * Update active filter count.
         */
        updateActiveCount() {
            // Log the filterValues object for debugging
            console.log('🔍 updateActiveCount() - Full filterValues object:', this.filterValues);
            console.log('🔍 updateActiveCount() - Object keys:', Object.keys(this.filterValues));
            
            // Count non-empty values
            const activeKeys = Object.keys(this.filterValues).filter(
                key => {
                    const value = this.filterValues[key];
                    const isActive = value !== '' && value !== null && value !== undefined;
                    console.log(`  - ${key}: "${value}" (type: ${typeof value}) -> ${isActive ? 'ACTIVE' : 'INACTIVE'}`);
                    return isActive;
                }
            );
            
            this.activeFilterCount = activeKeys.length;
            
            console.log('✅ Active filter count updated:', this.activeFilterCount);
            console.log('✅ Active keys:', activeKeys);
            
            // Update badge in DOM
            this.updateBadgeInDOM();
        },
        
        /**
         * Update badge element in DOM.
         */
        updateBadgeInDOM() {
            const badgeId = this.tableId + '_filter_badge';
            const badgeElement = document.getElementById(badgeId);
            
            if (badgeElement) {
                badgeElement.style.display = this.activeFilterCount > 0 ? 'flex' : 'none';
                badgeElement.textContent = this.activeFilterCount;
                console.log('✅ Badge updated in DOM:', {
                    id: badgeId,
                    display: badgeElement.style.display,
                    count: this.activeFilterCount
                });
            } else {
                console.warn('⚠️ Badge element not found:', badgeId);
            }
        },
        
        /**
         * Disable all Tom Select instances.
         */
        disableAllTomSelects() {
            Object.values(this.tomSelectInstances).forEach(instance => {
                if (instance && instance.disable) {
                    instance.disable();
                }
            });
            console.log('🔒 All Tom Select instances disabled');
        },
        
        /**
         * Enable all Tom Select instances.
         */
        enableAllTomSelects() {
            Object.values(this.tomSelectInstances).forEach(instance => {
                if (instance && instance.enable) {
                    instance.enable();
                }
            });
            console.log('🔓 All Tom Select instances enabled');
        },
        
        /**
         * Get filter label by column name.
         * 
         * @param {string} column - Filter column name
         * @returns {string} Filter label
         */
        getFilterLabel(column) {
            const filter = this.filters.find(f => f.column === column);
            return filter ? filter.label : column;
        },
        
        /**
         * Get filter value label (for display).
         * 
         * @param {string} column - Filter column name
         * @param {string} value - Filter value
         * @returns {string} Display label
         */
        getFilterValueLabel(column, value) {
            const filter = this.filters.find(f => f.column === column);
            
            if (!filter) return value;
            
            if (filter.type === 'selectbox' && filter.options) {
                const option = filter.options.find(opt => opt.value === value);
                return option ? option.label : value;
            }
            
            return value;
        },
        
        /**
         * Load filters from session storage.
         */
        async loadFiltersFromSession() {
            // Implementation depends on your session storage strategy
            console.log('Loading filters from session...');
        },
        
        /**
         * Restore filter options when modal opens.
         */
        restoreFilterOptions() {
            console.log('Restoring filter options...');
            console.log('Current filter values:', this.filterValues);
            
            // Restore Tom Select values
            this.filters.forEach(filter => {
                const value = this.filterValues[filter.column];
                console.log(`Checking filter: ${filter.column} Type: ${filter.type} Value: ${value}`);
                
                // Restore Tom Select value if exists
                if (filter.type === 'selectbox' && value) {
                    const filterId = `filter_${filter.column}`;
                    const instance = this.tomSelectInstances[filterId];
                    
                    if (instance) {
                        // Check if option exists in Tom Select
                        const hasOption = instance.options[value];
                        
                        if (hasOption) {
                            // Set the value in Tom Select (false = trigger events and UI update)
                            instance.setValue(value, false);
                            console.log(`✅ Tom Select value restored for ${filter.column}: ${value}`);
                        } else {
                            console.warn(`⚠️ Option not found in Tom Select for ${filter.column}: ${value}`);
                            // Add the option first, then set value
                            const option = filter.options?.find(opt => opt.value === value);
                            if (option) {
                                instance.addOption({ value: option.value, text: option.label });
                                instance.setValue(value, false);
                                console.log(`✅ Added option and restored value for ${filter.column}: ${value}`);
                            }
                        }
                    }
                }
            });
            
            console.log('Restore filter options complete');
            
            // Trigger cascade for restored filters if needed
            console.log('Triggering cascade for restored filters...');
            
            const filtersWithValues = this.filters.filter(f => {
                const value = this.filterValues[f.column];
                return value !== '' && value !== null && value !== undefined;
            });
            
            if (filtersWithValues.length === 0) {
                console.log('No filters with values found');
            }
            
            console.log('Cascade for restored filters complete');
        },
        
        /**
         * Show toast notification for active filters.
         */
        showFilterActiveToast() {
            console.log('🔔 showFilterActiveToast() called, activeFilterCount:', this.activeFilterCount);
            console.log('🔔 window.showNotification exists:', typeof window.showNotification);
            
            if (window.showNotification) {
                window.showNotification('info', `${this.activeFilterCount} filter(s) active`);
                console.log('✅ Toast notification shown via window.showNotification');
            } else {
                console.warn('⚠️ window.showNotification not available, using fallback toast');
                
                // Fallback: Create a DaisyUI-styled toast notification
                const toast = document.createElement('div');
                toast.id = 'canvastack-filter-toast-' + Date.now(); // Unique ID
                toast.className = 'alert alert-info shadow-lg transition-all duration-300 mb-6';
                toast.style.cssText = `
                    position: absolute !important;
                    right: 1%;
                    z-index: 50 !important;
                    opacity: 0;
                    transform: translateY(-20px);
                    pointer-events: auto !important;
                    width: 20% !important;
                    display: flex !important;
                    background-color: #3b83f68f !important;
                    color: white !important;
                    padding: 1rem !important;
                    border-radius: 0.75rem !important;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
                `;
                
                toast.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width: 1.5rem; height: 1.5rem; stroke: currentColor; flex-shrink: 0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span style="font-weight: 600;"><strong>${this.activeFilterCount}</strong> filter(s) active</span>
                    </div>
                `;
                
                // Find the main container and insert toast after Flash Messages
                const mainContainer = document.querySelector('main.flex-1.p-6') || document.querySelector('main');
                
                if (mainContainer) {
                    // Insert at the beginning of main (after Flash Messages comment)
                    const firstChild = mainContainer.firstElementChild;
                    if (firstChild) {
                        mainContainer.insertBefore(toast, firstChild);
                    } else {
                        mainContainer.appendChild(toast);
                    }
                    console.log('📍 Toast element created and inserted into main container');
                } else {
                    // Fallback to body if main not found
                    document.body.appendChild(toast);
                    console.log('📍 Toast element created and appended to body (fallback)');
                }
                
                console.log('📍 Toast initial styles:', {
                    zIndex: toast.style.zIndex,
                    opacity: toast.style.opacity,
                    transform: toast.style.transform,
                    position: window.getComputedStyle(toast).position
                });
                
                // Fade in with slide down animation - use longer delay
                setTimeout(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                    console.log('✅ Toast fade-in animation started');
                    console.log('✅ Toast visible styles:', {
                        opacity: toast.style.opacity,
                        transform: toast.style.transform,
                        display: window.getComputedStyle(toast).display,
                        visibility: window.getComputedStyle(toast).visibility,
                        zIndex: window.getComputedStyle(toast).zIndex
                    });
                }, 50); // Small delay to ensure DOM is ready
                
                // Fade out and remove after 5 seconds
                setTimeout(() => {
                    console.log('🔄 Toast fade-out animation started');
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                            console.log('🗑️ Toast element removed from DOM');
                        }
                    }, 300); // Wait for fade-out animation
                }, 5000); // 5 seconds total display time
                
                console.log('✅ Fallback toast notification shown');
            }
        },
        
        /**
         * Get cascade state from FilterCascade component.
         * 
         * @returns {Object} Cascade state
         */
        get cascadeState() {
            return this.cascade ? this.cascade.getState() : {
                isProcessing: false,
                currentFilter: null,
                affectedFilters: [],
                direction: null,
                hasError: false,
                error: null
            };
        },
        
        // ========================================
        // TOM SELECT METHODS
        // ========================================
        
        /**
         * Initialize Tom Select for all select boxes in the modal.
         */
        initializeTomSelect() {
            // Find all select elements with class 'select'
            const selects = this.$el.querySelectorAll('select.select');
            
            console.log(`🔍 Found ${selects.length} select elements to initialize`);
            
            selects.forEach(select => {
                const filterId = select.id;
                
                // Skip if already initialized
                if (this.tomSelectInstances[filterId]) {
                    console.log(`⏭️ Skipping ${filterId} - already initialized`);
                    return;
                }
                
                // Add tomselected class BEFORE initialization
                select.classList.add('tomselected');
                
                // Get current theme (light/dark)
                const isDark = document.documentElement.classList.contains('dark');
                
                console.log(`🎨 Initializing Tom Select for: ${filterId}`);
                console.log(`📊 Select has ${select.options.length} options`);
                
                // Get the parent .relative div
                const parentDiv = select.closest('.relative');
                
                try {
                    // Initialize Tom Select with DaisyUI-compatible styling
                    const instance = new TomSelect(select, {
                        // Plugins
                        plugins: {
                            'dropdown_input': {},
                            'clear_button': {
                                title: 'Clear selection'
                            }
                        },
                        
                        // Behavior
                        create: false,
                        sortField: {
                            field: 'text',
                            direction: 'asc'
                        },
                        maxOptions: 1000,
                        
                        // Styling - match DaisyUI
                        controlClass: 'ts-control',
                        dropdownClass: 'ts-dropdown',
                        
                        // Append dropdown to parent .relative div for correct positioning
                        dropdownParent: parentDiv,
                        
                        // Callbacks
                        onInitialize: function() {
                            console.log(`✅ Tom Select initialized for: ${filterId}`);
                        },
                        onDropdownOpen: function(dropdown) {
                            console.log(`📂 Dropdown opened for: ${filterId}`);
                        },
                        onDropdownClose: function(dropdown) {
                            console.log(`📁 Dropdown closed for: ${filterId}`);
                        },
                        onItemAdd: () => {
                            console.log(`➕ Item added to: ${filterId}`);
                            // Trigger Alpine.js change event
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                        onClear: () => {
                            console.log(`🗑️ Cleared: ${filterId}`);
                            // Trigger Alpine.js change event
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                        onChange: (value) => {
                            console.log(`🔄 Changed ${filterId} to:`, value);
                            // Update Alpine.js model
                            const event = new CustomEvent('input', { 
                                detail: { value },
                                bubbles: true 
                            });
                            select.dispatchEvent(event);
                        }
                    });
                    
                    this.tomSelectInstances[filterId] = instance;
                    
                    console.log(`✅ Tom Select instance created for: ${filterId}`);
                    
                } catch (error) {
                    console.error(`❌ Error initializing Tom Select for ${filterId}:`, error);
                }
            });
            
            console.log(`✅ Total Tom Select instances: ${Object.keys(this.tomSelectInstances).length}`);
            
            // Populate options for all Tom Select instances
            this.populateTomSelectOptions();
        },
        
        /**
         * Populate options for all Tom Select instances from filter.options.
         */
        populateTomSelectOptions() {
            this.filters.forEach(filter => {
                this.populateSingleTomSelect(filter);
            });
        },
        
        /**
         * Populate options for a single Tom Select instance.
         */
        populateSingleTomSelect(filter) {
            if (filter.type === 'selectbox' && filter.options && filter.options.length > 0) {
                const filterId = `filter_${filter.column}`;
                const instance = this.tomSelectInstances[filterId];
                
                if (instance) {
                    console.log(`📝 Populating ${filter.options.length} options for ${filterId}`);
                    
                    // Clear existing options (except placeholder)
                    instance.clearOptions();
                    
                    // Add new options
                    filter.options.forEach(option => {
                        instance.addOption({
                            value: option.value,
                            text: option.label
                        });
                    });
                    
                    // Refresh dropdown
                    instance.refreshOptions(false);
                    
                    console.log(`✅ Options populated for ${filterId}`);
                }
            }
        },
        
        /**
         * Destroy all Tom Select instances.
         */
        destroyTomSelect() {
            Object.keys(this.tomSelectInstances).forEach(key => {
                if (this.tomSelectInstances[key]) {
                    this.tomSelectInstances[key].destroy();
                    delete this.tomSelectInstances[key];
                }
            });
            
            console.log('✅ All Tom Select instances destroyed');
        }
    };
}

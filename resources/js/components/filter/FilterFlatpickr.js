/**
 * FilterFlatpickr - Flatpickr date picker integration for filters.
 * 
 * Manages Flatpickr instances for date filters with dynamic date ranges
 * and available dates based on parent filter selections.
 */
export class FilterFlatpickr {
    /**
     * Create a new FilterFlatpickr instance.
     */
    constructor() {
        this.instances = new Map();
    }
    
    /**
     * Initialize Flatpickr for all date filters.
     * 
     * @param {Array} filters - Array of filter configurations
     * @param {Object} filterValues - Current filter values
     * @param {Function} onChangeCallback - Callback when date changes
     */
    initAll(filters, filterValues, onChangeCallback) {
        // Wait for Flatpickr to be available
        if (typeof window.flatpickr === 'undefined') {
            console.warn('Flatpickr not loaded yet');
            return;
        }
        
        filters.forEach(filter => {
            if (filter.type === 'datebox' || filter.type === 'daterangebox') {
                this.init(filter, filterValues, onChangeCallback);
            }
        });
    }
    
    /**
     * Initialize Flatpickr for a single filter.
     * 
     * @param {Object} filter - Filter configuration
     * @param {Object} filterValues - Current filter values
     * @param {Function} onChangeCallback - Callback when date changes
     */
    init(filter, filterValues, onChangeCallback) {
        const inputId = 'filter_' + filter.column;
        const input = document.getElementById(inputId);
        
        if (!input) {
            console.warn('Input not found for filter:', filter.column);
            return;
        }
        
        // Destroy existing instance if any
        if (input._flatpickr) {
            input._flatpickr.destroy();
        }
        
        // Prepare config
        const config = {
            dateFormat: 'Y-m-d',
            onChange: (selectedDates, dateStr) => {
                filterValues[filter.column] = dateStr;
                
                // Trigger cascade if filter has relations
                if (onChangeCallback && (filter.relate || filter.bidirectional)) {
                    onChangeCallback(filter);
                }
            },
            onReady: () => {
                console.log('Flatpickr ready for', filter.column);
            }
        };
        
        // Only add minDate if it's valid
        if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
            config.minDate = filter.minDate;
        }
        
        // Only add maxDate if it's valid
        if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
            config.maxDate = filter.maxDate;
        }
        
        // Only add enable if we have valid dates
        if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
            config.enable = filter.availableDates;
        }
        
        // Create Flatpickr instance
        try {
            const instance = window.flatpickr(input, config);
            this.instances.set(filter.column, instance);
            
            console.log('Flatpickr initialized for', filter.column, {
                minDate: config.minDate || 'none',
                maxDate: config.maxDate || 'none',
                enabledDates: config.enable ? config.enable.length : 'all'
            });
        } catch (error) {
            console.error('Error initializing Flatpickr for', filter.column, error);
        }
    }
    
    /**
     * Update Flatpickr instance with new date range and available dates.
     * 
     * @param {Object} filter - Filter configuration
     * @param {Object} filterValues - Current filter values
     */
    update(filter, filterValues) {
        let instance = this.instances.get(filter.column);
        
        // Check if instance exists and is valid
        if (!instance || typeof instance.set !== 'function') {
            console.log('Flatpickr instance not valid, reinitializing for', filter.column);
            
            // Destroy invalid instance
            if (instance) {
                try {
                    instance.destroy();
                } catch (e) {
                    console.warn('Error destroying invalid instance:', e);
                }
                this.instances.delete(filter.column);
            }
            
            // Reinitialize
            const inputId = 'filter_' + filter.column;
            const input = document.getElementById(inputId);
            
            if (!input) {
                console.warn('Input not found for', filter.column);
                return;
            }
            
            // Destroy existing instance if any
            if (input._flatpickr) {
                input._flatpickr.destroy();
            }
            
            // Prepare config
            const config = {
                dateFormat: 'Y-m-d',
                onChange: (selectedDates, dateStr) => {
                    filterValues[filter.column] = dateStr;
                }
            };
            
            // Add minDate if valid
            if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
                config.minDate = filter.minDate;
            }
            
            // Add maxDate if valid
            if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
                config.maxDate = filter.maxDate;
            }
            
            // Add enable if we have valid dates
            if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
                config.enable = filter.availableDates;
            } else if (filter.minDate && filter.maxDate) {
                config.enable = [
                    {
                        from: filter.minDate,
                        to: filter.maxDate
                    }
                ];
            }
            
            // Create Flatpickr instance
            try {
                instance = window.flatpickr(input, config);
                this.instances.set(filter.column, instance);
                console.log('Flatpickr reinitialized for', filter.column, config);
            } catch (error) {
                console.error('Error reinitializing Flatpickr for', filter.column, error);
                return;
            }
        }
        
        // Now update the instance with current filter data
        try {
            // Update minDate
            if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
                instance.set('minDate', filter.minDate);
            } else {
                instance.set('minDate', null);
            }
            
            // Update maxDate
            if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
                instance.set('maxDate', filter.maxDate);
            } else {
                instance.set('maxDate', null);
            }
            
            // Update enable (available dates)
            if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
                // Use enable to restrict to specific dates
                instance.set('enable', filter.availableDates);
                console.log('Flatpickr enable set to specific dates:', filter.availableDates);
            } else if (filter.minDate && filter.maxDate) {
                // If no specific dates but we have min/max, enable the range
                instance.set('enable', [
                    {
                        from: filter.minDate,
                        to: filter.maxDate
                    }
                ]);
                console.log('Flatpickr enable set to date range:', filter.minDate, 'to', filter.maxDate);
            } else {
                // No restrictions - allow all dates
                instance.set('enable', undefined);
                instance.set('disable', []);
                console.log('Flatpickr enable set to all dates (no restrictions)');
            }
            
            // Clear selected date if not in available dates
            if (filterValues[filter.column]) {
                if (filter.availableDates && filter.availableDates.length > 0) {
                    if (!filter.availableDates.includes(filterValues[filter.column])) {
                        delete filterValues[filter.column]; // Delete key instead of empty string
                        instance.clear();
                    }
                }
            }
            
            console.log('Flatpickr updated for', filter.column, {
                minDate: filter.minDate || 'none',
                maxDate: filter.maxDate || 'none',
                enabledDates: filter.availableDates ? filter.availableDates.length : 'all'
            });
        } catch (error) {
            console.error('Error updating Flatpickr:', error);
            // Try to reinitialize on error
            this.instances.delete(filter.column);
            this.init(filter, filterValues);
        }
    }
    
    /**
     * Destroy Flatpickr instance for a filter.
     * 
     * @param {string} column - Filter column name
     */
    destroy(column) {
        const instance = this.instances.get(column);
        
        if (instance) {
            try {
                instance.destroy();
                this.instances.delete(column);
                console.log('Flatpickr destroyed for', column);
            } catch (error) {
                console.error('Error destroying Flatpickr for', column, error);
            }
        }
    }
    
    /**
     * Destroy all Flatpickr instances.
     */
    destroyAll() {
        for (const [column, instance] of this.instances.entries()) {
            try {
                instance.destroy();
                console.log('Flatpickr destroyed for', column);
            } catch (error) {
                console.error('Error destroying Flatpickr for', column, error);
            }
        }
        
        this.instances.clear();
        console.log('All Flatpickr instances destroyed');
    }
    
    /**
     * Get Flatpickr instance for a filter.
     * 
     * @param {string} column - Filter column name
     * @returns {Object|null} Flatpickr instance or null
     */
    getInstance(column) {
        return this.instances.get(column) || null;
    }
    
    /**
     * Check if Flatpickr is initialized for a filter.
     * 
     * @param {string} column - Filter column name
     * @returns {boolean} True if initialized
     */
    isInitialized(column) {
        return this.instances.has(column);
    }
}

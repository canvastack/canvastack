/**
 * FilterCascade - Bidirectional cascade logic for filter dependencies.
 * 
 * Handles cascading filter updates in both directions:
 * - Upstream: Update filters before the changed filter
 * - Downstream: Update filters after the changed filter
 */
export class FilterCascade {
    /**
     * Create a new FilterCascade instance.
     * 
     * @param {Array} filters - Array of filter configurations
     * @param {Object} config - Cascade configuration
     * @param {Function} fetchOptions - Function to fetch filter options
     */
    constructor(filters, config, fetchOptions) {
        this.filters = filters;
        this.config = config || {};
        this.fetchOptions = fetchOptions;
        
        // Cascade state
        this.state = {
            isProcessing: false,
            currentFilter: null,
            affectedFilters: [],
            direction: null,
            hasError: false,
            error: null
        };
    }
    
    /**
     * Detect cascade direction for a changed filter.
     * 
     * Determines which filters should be updated based on the changed filter's position.
     * 
     * @param {Object} changedFilter - The filter that changed
     * @returns {Object} Object with upstream and downstream filter arrays
     */
    detectCascadeDirection(changedFilter) {
        // Find the index of the changed filter
        const filterIndex = this.filters.findIndex(f => f.column === changedFilter.column);
        
        if (filterIndex === -1) {
            console.warn('Changed filter not found in filters array:', changedFilter.column);
            return { upstream: [], downstream: [] };
        }
        
        // Get upstream filters (filters before this one)
        const upstream = this.filters.slice(0, filterIndex);
        
        // Get downstream filters (filters after this one)
        const downstream = this.filters.slice(filterIndex + 1);
        
        console.log('Cascade direction detected:', {
            changedFilter: changedFilter.column,
            filterIndex: filterIndex,
            totalFilters: this.filters.length,
            upstreamCount: upstream.length,
            downstreamCount: downstream.length,
            upstream: upstream.map(f => f.column),
            downstream: downstream.map(f => f.column)
        });
        
        return { upstream, downstream };
    }
    
    /**
     * Execute upstream cascade.
     * 
     * Updates filters that appear before the changed filter.
     * This is the "reverse" direction of traditional cascading.
     * 
     * @param {Object} changedFilter - The filter that changed
     * @param {Array} upstreamFilters - Filters to update
     * @param {Object} filterValues - Current filter values
     * @returns {Promise<void>}
     */
    async cascadeUpstream(changedFilter, upstreamFilters, filterValues) {
        // Handle empty upstream filters array
        if (!upstreamFilters || upstreamFilters.length === 0) {
            console.log('No upstream filters to cascade');
            return;
        }
        
        console.log('Starting upstream cascade:', {
            changedFilter: changedFilter.column,
            changedValue: filterValues[changedFilter.column],
            upstreamCount: upstreamFilters.length,
            upstreamFilters: upstreamFilters.map(f => f.column)
        });
        
        // Build parent filters from changed filter and all downstream filters
        const parentFilters = {};
        
        // Add changed filter value
        if (filterValues[changedFilter.column]) {
            parentFilters[changedFilter.column] = filterValues[changedFilter.column];
        }
        
        // Add all downstream filter values (filters after changed filter)
        const changedIndex = this.filters.findIndex(f => f.column === changedFilter.column);
        const downstreamFilters = this.filters.slice(changedIndex + 1);
        
        for (const filter of downstreamFilters) {
            if (filterValues[filter.column]) {
                parentFilters[filter.column] = filterValues[filter.column];
            }
        }
        
        console.log('Initial parent filters for upstream:', parentFilters);
        
        // Update each upstream filter in reverse order (from closest to farthest)
        for (let i = upstreamFilters.length - 1; i >= 0; i--) {
            const filter = upstreamFilters[i];
            
            console.log('Updating upstream filter:', filter.column, {
                parentFilters: parentFilters,
                currentValue: filterValues[filter.column]
            });
            
            try {
                // Fetch new options for this filter
                await this.fetchOptions(filter, parentFilters);
                
                // Add this filter to parent context for next iteration
                if (filterValues[filter.column]) {
                    parentFilters[filter.column] = filterValues[filter.column];
                }
                
            } catch (error) {
                console.error(`Error updating upstream filter ${filter.column}:`, error);
                this.state.hasError = true;
                this.state.error = `Failed to update ${filter.column}`;
            }
        }
        
        console.log('Upstream cascade complete');
    }
    
    /**
     * Execute downstream cascade.
     * 
     * Updates filters that appear after the changed filter.
     * This is the traditional "forward" direction of cascading.
     * 
     * @param {Object} changedFilter - The filter that changed
     * @param {Array} downstreamFilters - Filters to update
     * @param {Object} filterValues - Current filter values
     * @returns {Promise<void>}
     */
    async cascadeDownstream(changedFilter, downstreamFilters, filterValues) {
        // Handle empty downstream filters array
        if (!downstreamFilters || downstreamFilters.length === 0) {
            console.log('No downstream filters to cascade');
            return;
        }
        
        console.log('Starting downstream cascade:', {
            changedFilter: changedFilter.column,
            changedValue: filterValues[changedFilter.column],
            downstreamCount: downstreamFilters.length,
            downstreamFilters: downstreamFilters.map(f => f.column)
        });
        
        // Build parent filters from changed filter and all upstream filters
        const parentFilters = {};
        
        // Add changed filter value
        if (filterValues[changedFilter.column]) {
            parentFilters[changedFilter.column] = filterValues[changedFilter.column];
        }
        
        // Add all upstream filter values (filters before changed filter)
        const changedIndex = this.filters.findIndex(f => f.column === changedFilter.column);
        const upstreamFilters = this.filters.slice(0, changedIndex);
        
        for (const filter of upstreamFilters) {
            if (filterValues[filter.column]) {
                parentFilters[filter.column] = filterValues[filter.column];
            }
        }
        
        console.log('Initial parent filters for downstream:', parentFilters);
        
        // Update each downstream filter in order
        for (const filter of downstreamFilters) {
            console.log('Updating downstream filter:', filter.column, {
                parentFilters: parentFilters,
                currentValue: filterValues[filter.column]
            });
            
            try {
                // Fetch new options for this filter
                await this.fetchOptions(filter, parentFilters);
                
                // Add this filter to parent context for next iteration
                if (filterValues[filter.column]) {
                    parentFilters[filter.column] = filterValues[filter.column];
                }
                
            } catch (error) {
                console.error(`Error updating downstream filter ${filter.column}:`, error);
                this.state.hasError = true;
                this.state.error = `Failed to update ${filter.column}`;
            }
        }
        
        console.log('Downstream cascade complete');
    }
    
    /**
     * Execute bidirectional cascade.
     * 
     * Main entry point for cascade execution. Determines cascade direction
     * and executes appropriate cascade methods.
     * 
     * @param {Object} filter - The filter that changed
     * @param {string} value - The new filter value
     * @param {Object} filterValues - Current filter values
     * @returns {Promise<void>}
     */
    async execute(filter, value, filterValues) {
        console.log('=== Starting Bi-Directional Cascade ===', {
            filter: filter.column,
            value: value,
            bidirectional: filter.bidirectional,
            relate: filter.relate
        });
        
        // Reset state
        this.state.isProcessing = true;
        this.state.currentFilter = filter.column;
        this.state.hasError = false;
        this.state.error = null;
        
        try {
            // Detect cascade direction
            const { upstream, downstream } = this.detectCascadeDirection(filter);
            
            // Determine which filters to update based on configuration
            if (filter.bidirectional || (this.config && this.config.bidirectional_cascade)) {
                // Bi-directional mode: Update ALL related filters
                this.state.direction = 'both';
                this.state.affectedFilters = [
                    ...upstream.map(f => f.column),
                    ...downstream.map(f => f.column)
                ];
                
                console.log('Bi-directional cascade enabled, updating both directions');
                
                // Execute upstream cascade (reverse direction)
                if (upstream.length > 0) {
                    console.log('Executing upstream cascade...');
                    await this.cascadeUpstream(filter, upstream, filterValues);
                }
                
                // Execute downstream cascade (forward direction)
                if (downstream.length > 0) {
                    console.log('Executing downstream cascade...');
                    await this.cascadeDownstream(filter, downstream, filterValues);
                }
                
            } else if (filter.relate) {
                // Backward compatibility: Only downstream (existing behavior)
                this.state.direction = 'downstream';
                
                if (filter.relate === true) {
                    // Cascade to all filters after this one
                    this.state.affectedFilters = downstream.map(f => f.column);
                    
                    console.log('Relate=true, cascading to all downstream filters');
                    
                    if (downstream.length > 0) {
                        await this.cascadeDownstream(filter, downstream, filterValues);
                    }
                    
                } else if (typeof filter.relate === 'string') {
                    // Cascade to specific filter
                    this.state.direction = 'specific';
                    this.state.affectedFilters = [filter.relate];
                    
                    console.log('Relate=string, cascading to specific filter:', filter.relate);
                    
                    const targetFilter = this.filters.find(f => f.column === filter.relate);
                    if (targetFilter) {
                        await this.cascadeDownstream(filter, [targetFilter], filterValues);
                    }
                    
                } else if (Array.isArray(filter.relate)) {
                    // Cascade to specified filters
                    this.state.direction = 'specific';
                    this.state.affectedFilters = filter.relate;
                    
                    console.log('Relate=array, cascading to specified filters:', filter.relate);
                    
                    const targetFilters = this.filters.filter(f => filter.relate.includes(f.column));
                    if (targetFilters.length > 0) {
                        await this.cascadeDownstream(filter, targetFilters, filterValues);
                    }
                }
            } else {
                // No cascade configured
                console.log('No cascade configured for this filter');
            }
            
            console.log('=== Bi-Directional Cascade Complete ===');
            
        } catch (error) {
            console.error('=== Cascade Error ===', error);
            this.state.hasError = true;
            this.state.error = error.message;
            throw error;
        } finally {
            // Reset processing flag
            this.state.isProcessing = false;
            this.state.currentFilter = null;
        }
    }
    
    /**
     * Get current cascade state.
     * 
     * @returns {Object} Current cascade state
     */
    getState() {
        return { ...this.state };
    }
    
    /**
     * Reset cascade state.
     */
    resetState() {
        this.state = {
            isProcessing: false,
            currentFilter: null,
            affectedFilters: [],
            direction: null,
            hasError: false,
            error: null
        };
    }
}

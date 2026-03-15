/**
 * Filter Cascade Manager
 * 
 * Handles bi-directional cascading filter relationships on the client side.
 * Updates dependent filter options when a parent filter changes.
 */

export class FilterCascadeManager {
    /**
     * Constructor
     * 
     * @param {string} tableId - Table identifier
     * @param {Object} config - Configuration object
     */
    constructor(tableId, config = {}) {
        this.tableId = tableId;
        this.config = config;
        this.filters = config.filters || [];
        this.cascadeGraph = this.buildCascadeGraph();
        this.reverseCascadeGraph = this.buildReverseCascadeGraph();
        this.ajaxUrl = config.ajaxUrl || '/api/canvastack/table/filter-options';
        this.csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.initializeEventListeners();
    }

    /**
     * Build cascade graph from filter configuration
     * 
     * @returns {Object} Cascade graph mapping filter to dependents
     */
    buildCascadeGraph() {
        const graph = {};

        this.filters.forEach(filter => {
            if (!filter.relate || filter.relate === false) {
                return;
            }

            const relatedFilters = this.getRelatedFilters(filter);
            
            if (relatedFilters.length > 0) {
                graph[filter.column] = relatedFilters;
            }
        });

        return graph;
    }

    /**
     * Build reverse cascade graph for bi-directional relationships
     * 
     * @returns {Object} Reverse cascade graph
     */
    buildReverseCascadeGraph() {
        const reverseGraph = {};

        this.filters.forEach(filter => {
            if (!filter.bidirectional || !filter.relate || filter.relate === false) {
                return;
            }

            const relatedFilters = this.getRelatedFilters(filter);
            
            relatedFilters.forEach(relatedColumn => {
                if (!reverseGraph[relatedColumn]) {
                    reverseGraph[relatedColumn] = [];
                }
                reverseGraph[relatedColumn].push(filter.column);
            });
        });

        return reverseGraph;
    }

    /**
     * Get related filter columns for a filter
     * 
     * @param {Object} filter - Filter configuration
     * @returns {Array} Array of related filter columns
     */
    getRelatedFilters(filter) {
        if (filter.relate === false) {
            return [];
        }

        if (filter.relate === true) {
            // Cascade to all filters after this one
            const currentIndex = this.filters.findIndex(f => f.column === filter.column);
            return this.filters.slice(currentIndex + 1).map(f => f.column);
        }

        if (typeof filter.relate === 'string') {
            return [filter.relate];
        }

        if (Array.isArray(filter.relate)) {
            return filter.relate;
        }

        return [];
    }

    /**
     * Get dependent filters for a given filter column
     * 
     * @param {string} column - Filter column
     * @param {boolean} includeBidirectional - Include bi-directional dependencies
     * @returns {Array} Array of dependent filter columns
     */
    getDependentFilters(column, includeBidirectional = true) {
        let dependents = this.cascadeGraph[column] || [];

        if (includeBidirectional) {
            const reverseDependents = this.reverseCascadeGraph[column] || [];
            dependents = [...new Set([...dependents, ...reverseDependents])];
        }

        return dependents;
    }

    /**
     * Initialize event listeners for filter changes
     */
    initializeEventListeners() {
        this.filters.forEach(filter => {
            const element = document.querySelector(`[name="filter_${filter.column}"]`);
            
            if (!element) {
                return;
            }

            element.addEventListener('change', (event) => {
                this.handleFilterChange(filter.column, event.target.value);
            });
        });
    }

    /**
     * Handle filter value change
     * 
     * @param {string} changedColumn - Column that changed
     * @param {*} newValue - New value
     */
    async handleFilterChange(changedColumn, newValue) {
        const dependents = this.getDependentFilters(changedColumn, true);

        if (dependents.length === 0) {
            return;
        }

        // Get current filter values
        const activeFilters = this.getActiveFilters();
        activeFilters[changedColumn] = newValue;

        // Update each dependent filter
        for (const dependentColumn of dependents) {
            await this.updateFilterOptions(dependentColumn, activeFilters);
        }
    }

    /**
     * Get current active filter values
     * 
     * @returns {Object} Map of filter column to value
     */
    getActiveFilters() {
        const activeFilters = {};

        this.filters.forEach(filter => {
            const element = document.querySelector(`[name="filter_${filter.column}"]`);
            
            if (element && element.value) {
                activeFilters[filter.column] = element.value;
            }
        });

        return activeFilters;
    }

    /**
     * Update filter options via AJAX
     * 
     * @param {string} column - Filter column to update
     * @param {Object} activeFilters - Current active filters
     */
    async updateFilterOptions(column, activeFilters) {
        const element = document.querySelector(`[name="filter_${column}"]`);
        
        if (!element) {
            return;
        }

        // Show loading state
        element.disabled = true;
        element.classList.add('loading');

        try {
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    table: this.tableId,
                    column: column,
                    filters: activeFilters,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Update options
            this.updateSelectOptions(element, data.options || []);

            // Clear value if it's no longer valid
            const currentValue = element.value;
            if (currentValue && !this.isValueInOptions(currentValue, data.options || [])) {
                element.value = '';
                
                // Trigger change event to cascade further
                element.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } catch (error) {
            console.error('Failed to update filter options:', error);
            
            // Show error state
            element.classList.add('error');
            
            // Optionally show error message to user
            this.showError(column, 'Failed to load filter options');
        } finally {
            // Remove loading state
            element.disabled = false;
            element.classList.remove('loading');
        }
    }

    /**
     * Update select element options
     * 
     * @param {HTMLElement} element - Select element
     * @param {Array} options - New options
     */
    updateSelectOptions(element, options) {
        // Save current value
        const currentValue = element.value;

        // Clear existing options except the first (placeholder)
        const firstOption = element.options[0];
        element.innerHTML = '';
        
        if (firstOption) {
            element.appendChild(firstOption);
        }

        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label || option.value;
            element.appendChild(optionElement);
        });

        // Restore value if still valid
        if (currentValue && this.isValueInOptions(currentValue, options)) {
            element.value = currentValue;
        }
    }

    /**
     * Check if a value exists in options
     * 
     * @param {*} value - Value to check
     * @param {Array} options - Options array
     * @returns {boolean}
     */
    isValueInOptions(value, options) {
        return options.some(option => option.value == value);
    }

    /**
     * Show error message
     * 
     * @param {string} column - Filter column
     * @param {string} message - Error message
     */
    showError(column, message) {
        const element = document.querySelector(`[name="filter_${column}"]`);
        
        if (!element) {
            return;
        }

        // Find or create error message element
        let errorElement = element.parentElement.querySelector('.filter-error');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'filter-error text-error text-sm mt-1';
            element.parentElement.appendChild(errorElement);
        }

        errorElement.textContent = message;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorElement.remove();
        }, 5000);
    }

    /**
     * Clear all filters
     */
    clearAllFilters() {
        this.filters.forEach(filter => {
            const element = document.querySelector(`[name="filter_${filter.column}"]`);
            
            if (element) {
                element.value = '';
                element.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    /**
     * Get cascade order (topological sort)
     * 
     * @returns {Array} Array of filter columns in cascade order
     */
    getCascadeOrder() {
        const visited = {};
        const order = [];

        this.filters.forEach(filter => {
            if (!visited[filter.column]) {
                this.topologicalSort(filter.column, visited, order);
            }
        });

        return order.reverse();
    }

    /**
     * Topological sort helper
     * 
     * @param {string} column - Current column
     * @param {Object} visited - Visited map
     * @param {Array} order - Order array
     */
    topologicalSort(column, visited, order) {
        visited[column] = true;

        const dependents = this.cascadeGraph[column] || [];

        dependents.forEach(dependent => {
            if (!visited[dependent]) {
                this.topologicalSort(dependent, visited, order);
            }
        });

        order.push(column);
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Find all tables with filter cascade configuration
    document.querySelectorAll('[data-filter-cascade]').forEach(table => {
        const config = JSON.parse(table.dataset.filterCascade || '{}');
        const tableId = table.id || table.dataset.tableId;
        
        if (tableId && config.filters) {
            new FilterCascadeManager(tableId, config);
        }
    });
});

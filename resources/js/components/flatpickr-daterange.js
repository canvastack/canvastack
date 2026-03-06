/**
 * Flatpickr Date Range Component for Alpine.js
 * 
 * Provides a rich date range picker with Flatpickr integration.
 * Supports dark mode, localization, and custom date formats.
 * 
 * @requires flatpickr
 * @requires Alpine.js
 */

export function flatpickrDateRange() {
    return {
        // Component state
        startDate: null,
        endDate: null,
        flatpickrInstance: null,
        
        // Configuration
        config: {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'F j, Y',
            allowInput: true,
            clickOpens: true,
            
            // Localization
            locale: document.documentElement.lang || 'en',
            
            // Dark mode support
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            
            // Range presets
            plugins: [],
            
            // Callbacks
            onChange: (selectedDates, dateStr, instance) => {
                this.handleDateChange(selectedDates, dateStr, instance);
            },
            
            onReady: (selectedDates, dateStr, instance) => {
                this.handleReady(instance);
            },
        },
        
        /**
         * Initialize the component
         */
        init() {
            // Wait for Flatpickr to be loaded
            if (typeof flatpickr === 'undefined') {
                console.error('Flatpickr is not loaded. Please include Flatpickr library.');
                return;
            }
            
            // Initialize Flatpickr on the input element
            this.initializeFlatpickr();
            
            // Watch for dark mode changes
            this.watchDarkMode();
        },
        
        /**
         * Initialize Flatpickr instance
         */
        initializeFlatpickr() {
            const input = this.$refs.dateRangeInput;
            
            if (!input) {
                console.error('Date range input element not found');
                return;
            }
            
            // Create Flatpickr instance
            this.flatpickrInstance = flatpickr(input, this.config);
            
            // Set initial values if provided
            if (this.startDate && this.endDate) {
                this.flatpickrInstance.setDate([this.startDate, this.endDate]);
            }
        },
        
        /**
         * Handle date change event
         */
        handleDateChange(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                this.startDate = this.formatDate(selectedDates[0]);
                this.endDate = this.formatDate(selectedDates[1]);
                
                // Emit custom event for parent components
                this.$dispatch('daterange-changed', {
                    start: this.startDate,
                    end: this.endDate,
                    dateStr: dateStr,
                });
            } else if (selectedDates.length === 1) {
                this.startDate = this.formatDate(selectedDates[0]);
                this.endDate = null;
            } else {
                this.startDate = null;
                this.endDate = null;
            }
        },
        
        /**
         * Handle ready event
         */
        handleReady(instance) {
            // Apply dark mode styling if needed
            if (document.documentElement.classList.contains('dark')) {
                instance.calendarContainer.classList.add('flatpickr-dark');
            }
        },
        
        /**
         * Format date to Y-m-d format
         */
        formatDate(date) {
            if (!date) return null;
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return `${year}-${month}-${day}`;
        },
        
        /**
         * Clear date range
         */
        clear() {
            if (this.flatpickrInstance) {
                this.flatpickrInstance.clear();
            }
            
            this.startDate = null;
            this.endDate = null;
            
            this.$dispatch('daterange-cleared');
        },
        
        /**
         * Set date range programmatically
         */
        setDateRange(start, end) {
            if (this.flatpickrInstance) {
                this.flatpickrInstance.setDate([start, end]);
            }
        },
        
        /**
         * Watch for dark mode changes
         */
        watchDarkMode() {
            // Watch for dark mode class changes on html element
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        
                        if (this.flatpickrInstance) {
                            if (isDark) {
                                this.flatpickrInstance.calendarContainer.classList.add('flatpickr-dark');
                            } else {
                                this.flatpickrInstance.calendarContainer.classList.remove('flatpickr-dark');
                            }
                        }
                    }
                });
            });
            
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
        },
        
        /**
         * Destroy Flatpickr instance on component cleanup
         */
        destroy() {
            if (this.flatpickrInstance) {
                this.flatpickrInstance.destroy();
                this.flatpickrInstance = null;
            }
        },
    };
}

// Register as Alpine.js component
if (typeof Alpine !== 'undefined') {
    Alpine.data('flatpickrDateRange', flatpickrDateRange);
}

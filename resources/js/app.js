/**
 * CanvaStack - Main JavaScript Entry Point
 * 
 * This file is the main entry point for all CanvaStack JavaScript.
 * It exports components and utilities for use in Blade templates.
 */

// Export filter components
export { FilterCache, FilterCascade, FilterFlatpickr, createFilterModal } from './components/filter/index.js';

// Export utilities
export { debounce, fetchWithCsrf, getCsrfToken } from './utils/index.js';

// Make components available globally for Alpine.js
if (typeof window !== 'undefined') {
    // Import createFilterModal for global use
    import('./components/filter/FilterModal.js').then(({ createFilterModal }) => {
        window.CanvaStack = window.CanvaStack || {};
        window.CanvaStack.createFilterModal = createFilterModal;
        
        console.log('✅ CanvaStack components loaded globally');
    });
}

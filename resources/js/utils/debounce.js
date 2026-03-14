/**
 * Debounce utility function.
 * 
 * Prevents excessive function calls by delaying execution until
 * after a specified wait time has elapsed since the last call.
 * 
 * @param {Function} func - The function to debounce
 * @param {number} wait - The delay in milliseconds
 * @returns {Function} Debounced function
 * 
 * @example
 * const debouncedSearch = debounce((query) => {
 *     console.log('Searching for:', query);
 * }, 300);
 * 
 * debouncedSearch('test'); // Will only execute after 300ms of no calls
 */
export function debounce(func, wait) {
    let timeout;
    
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

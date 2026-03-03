/**
 * Flatpickr Initialization
 * 
 * Self-hosted Flatpickr for date filtering with disabled dates support
 */

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Make flatpickr globally available
window.flatpickr = flatpickr;

// Export for module usage
export default flatpickr;

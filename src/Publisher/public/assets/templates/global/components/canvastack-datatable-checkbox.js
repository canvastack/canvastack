/**
 * CanvaStack DataTable Checkbox Handler
 * 
 * Makes the entire checkbox cell clickable for better UX in DataTables.
 * User can click anywhere in the cell to toggle the checkbox.
 * 
 * Features:
 * - Click anywhere in cell to toggle checkbox
 * - Keyboard support (Space/Enter)
 * - Accessibility (ARIA attributes, focus management)
 * - Visual feedback on click
 * - Auto-initialization on DOM ready
 * - Re-initialization support for dynamic content (DataTables draw)
 * - Works with DataTables pagination and filtering
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window, document, $) {
	'use strict';
	
	/**
	 * Initialize DataTable checkbox cell click handlers
	 */
	function initDataTableCheckboxCellClick() {
		// Find all DataTable checkbox cells
		// These are typically in the first column with class 'read-select' or similar
		const checkboxCells = document.querySelectorAll('td.read-select, td.datatable-checkbox-cell, td:has(input[type="checkbox"].read-select)');
		
		if (checkboxCells.length === 0) {
			return; // No DataTable checkboxes on this page
		}
		
		checkboxCells.forEach(function(cell) {
			// Find checkbox inside this cell
			const checkbox = cell.querySelector('input[type="checkbox"]');
			
			if (!checkbox) {
				return; // No checkbox found
			}
			
			// Skip if already initialized
			if (cell.hasAttribute('data-datatable-checkbox-initialized')) {
				return;
			}
			cell.setAttribute('data-datatable-checkbox-initialized', 'true');
			
			// Add click handler to cell
			cell.addEventListener('click', function(e) {
				// Prevent double-toggle if checkbox itself was clicked
				if (e.target === checkbox) {
					return;
				}
				
				// Prevent if clicking on a link or button inside the cell
				if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
					return;
				}
				
				// Toggle checkbox
				checkbox.checked = !checkbox.checked;
				
				// Update aria-checked attribute for accessibility
				checkbox.setAttribute('aria-checked', checkbox.checked);
				
				// Trigger change event for any listeners
				const event = new Event('change', { bubbles: true });
				checkbox.dispatchEvent(event);
				
				// Also trigger jQuery change event for legacy code
				if (typeof $ !== 'undefined') {
					$(checkbox).trigger('change');
				}
				
				// Visual feedback
				cell.classList.add('clicking');
				setTimeout(function() {
					cell.classList.remove('clicking');
				}, 150);
			});
			
			// Add keyboard support (Space/Enter to toggle)
			cell.addEventListener('keydown', function(e) {
				if (e.key === ' ' || e.key === 'Enter') {
					e.preventDefault();
					
					// Toggle checkbox
					checkbox.checked = !checkbox.checked;
					
					// Update aria-checked attribute
					checkbox.setAttribute('aria-checked', checkbox.checked);
					
					// Trigger change event
					const event = new Event('change', { bubbles: true });
					checkbox.dispatchEvent(event);
					
					// Also trigger jQuery change event
					if (typeof $ !== 'undefined') {
						$(checkbox).trigger('change');
					}
					
					// Visual feedback
					cell.classList.add('clicking');
					setTimeout(function() {
						cell.classList.remove('clicking');
					}, 150);
				}
			});
			
			// Make cell focusable for keyboard navigation
			if (!cell.hasAttribute('tabindex')) {
				cell.setAttribute('tabindex', '0');
			}
			
			// Add role for accessibility
			cell.setAttribute('role', 'checkbox');
			cell.setAttribute('aria-checked', checkbox.checked);
			
			// Update aria-checked when checkbox changes
			checkbox.addEventListener('change', function() {
				cell.setAttribute('aria-checked', checkbox.checked);
			});
			
			// Add CSS class for styling
			cell.classList.add('datatable-checkbox-cell');
		});
		
		if (checkboxCells.length > 0) {
			console.log('DataTable checkbox cell click handlers initialized:', checkboxCells.length, 'cells');
		}
	}
	
	/**
	 * Initialize on DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initDataTableCheckboxCellClick);
	} else {
		// DOM already loaded
		initDataTableCheckboxCellClick();
	}
	
	/**
	 * Re-initialize when DataTables redraws (pagination, filtering, sorting)
	 */
	if (typeof $ !== 'undefined' && $.fn.dataTable) {
		$(document).on('draw.dt', function() {
			// Small delay to ensure DOM is updated
			setTimeout(initDataTableCheckboxCellClick, 50);
		});
	}
	
	/**
	 * Re-initialize if table is dynamically loaded
	 * (e.g., via AJAX or modal)
	 */
	window.reinitDataTableCheckboxCellClick = initDataTableCheckboxCellClick;
	
})(window, document, typeof jQuery !== 'undefined' ? jQuery : null);

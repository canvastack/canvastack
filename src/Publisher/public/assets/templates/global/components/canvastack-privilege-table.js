/**
 * CanvaStack Privilege Table Handler
 * 
 * Makes the entire checkbox cell clickable for better UX in privilege tables.
 * User can click anywhere in the cell to toggle the checkbox.
 * 
 * Features:
 * - Click anywhere in cell to toggle checkbox
 * - Keyboard support (Space/Enter)
 * - Accessibility (ARIA attributes, focus management)
 * - Visual feedback on click
 * - Auto-initialization on DOM ready
 * - Re-initialization support for dynamic content
 * 
 * @package CanvaStack
 * @subpackage Global Components
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window, document) {
	'use strict';
	
	/**
	 * Initialize privilege table cell click handlers
	 */
	function initPrivilegeTableCellClick() {
		// Find all privilege checkbox cells
		const checkboxCells = document.querySelectorAll('.privilege-checkbox-cell');
		
		if (checkboxCells.length === 0) {
			return; // No privilege table on this page
		}
		
		checkboxCells.forEach(function(cell) {
			// Find checkbox inside this cell
			const checkbox = cell.querySelector('input[type="checkbox"]');
			
			if (!checkbox) {
				return; // No checkbox found
			}
			
			// Skip if already initialized
			if (cell.hasAttribute('data-privilege-initialized')) {
				return;
			}
			cell.setAttribute('data-privilege-initialized', 'true');
			
			// Add click handler to cell
			cell.addEventListener('click', function(e) {
				// Prevent double-toggle if checkbox itself was clicked
				if (e.target === checkbox) {
					return;
				}
				
				// Toggle checkbox
				checkbox.checked = !checkbox.checked;
				
				// Update aria-checked attribute for accessibility
				checkbox.setAttribute('aria-checked', checkbox.checked);
				
				// Trigger change event for any listeners
				const event = new Event('change', { bubbles: true });
				checkbox.dispatchEvent(event);
				
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
		});
		
		if (checkboxCells.length > 0) {
			console.log('Privilege table cell click handlers initialized:', checkboxCells.length, 'cells');
		}
	}
	
	/**
	 * Initialize on DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPrivilegeTableCellClick);
	} else {
		// DOM already loaded
		initPrivilegeTableCellClick();
	}
	
	/**
	 * Re-initialize if table is dynamically loaded
	 * (e.g., via AJAX or modal)
	 */
	window.reinitPrivilegeTableCellClick = initPrivilegeTableCellClick;
	
})(window, document);

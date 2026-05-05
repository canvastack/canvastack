/**
 * Canvasign Mapping Page - Icons Adapter
 * 
 * Converts FontAwesome icons to Bootstrap Icons for mapping page functionality.
 * This adapter is necessary because the backend generates FA icons but Canvasign uses BI.
 * 
 * Features:
 * - Converts fa-plus-circle → bi-plus-circle (Add Row button)
 * - Converts fa-recycle → bi-recycle (Recycle/Reset button)
 * - Converts fa-minus-circle → bi-dash-circle (Delete Row button)
 * - Handles dynamically generated elements via MutationObserver
 * 
 * @package CanvaStack
 * @subpackage Canvasign Theme
 * @version 1.0.0
 */

(function($) {
	'use strict';
	
	console.log('🎨 Canvasign Mapping Icons Adapter loaded');
	
	/**
	 * Convert FontAwesome icons to Bootstrap Icons
	 */
	function convertMappingIcons() {
		// Only run on mapping page
		if (!$('#mapping-page-privileges').length) {
			console.log('⏭️ Not a mapping page, skipping icon conversion');
			return;
		}
		
		console.log('🔄 Converting mapping page icons from FA to BI...');
		
		// Convert plus-circle icon (Add Row button)
		$('.fa-plus-circle').each(function() {
			$(this).removeClass('fa fa-plus-circle').addClass('bi bi-plus-circle');
			console.log('✅ Converted fa-plus-circle → bi-plus-circle');
		});
		
		// Convert recycle icon (Reset button)
		$('.fa-recycle').each(function() {
			$(this).removeClass('fa fa-recycle').addClass('bi bi-recycle');
			console.log('✅ Converted fa-recycle → bi-recycle');
		});
		
		// Convert minus-circle icon (Delete Row button)
		$('.fa-minus-circle').each(function() {
			$(this).removeClass('fa fa-minus-circle').addClass('bi bi-dash-circle');
			console.log('✅ Converted fa-minus-circle → bi-dash-circle');
		});
	}
	
	/**
	 * Watch for dynamically added icons and convert them
	 */
	function watchDynamicIcons() {
		// Only run on mapping page
		if (!$('#mapping-page-privileges').length) {
			return;
		}
		
		console.log('👀 Setting up MutationObserver for dynamic icons...');
		
		// Create observer to watch for DOM changes
		const observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				// Check added nodes
				mutation.addedNodes.forEach(function(node) {
					if (node.nodeType === 1) { // Element node
						const $node = $(node);
						
						// Convert icons in the added node
						$node.find('.fa-plus-circle').removeClass('fa fa-plus-circle').addClass('bi bi-plus-circle');
						$node.find('.fa-recycle').removeClass('fa fa-recycle').addClass('bi bi-recycle');
						$node.find('.fa-minus-circle').removeClass('fa fa-minus-circle').addClass('bi bi-dash-circle');
						
						// Check if the node itself is an icon
						if ($node.hasClass('fa-plus-circle')) {
							$node.removeClass('fa fa-plus-circle').addClass('bi bi-plus-circle');
						}
						if ($node.hasClass('fa-recycle')) {
							$node.removeClass('fa fa-recycle').addClass('bi bi-recycle');
						}
						if ($node.hasClass('fa-minus-circle')) {
							$node.removeClass('fa fa-minus-circle').addClass('bi bi-dash-circle');
						}
					}
				});
			});
		});
		
		// Start observing the document body for changes
		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
		
		console.log('✅ MutationObserver active for mapping page icons');
	}
	
	// Run on document ready
	$(document).ready(function() {
		convertMappingIcons();
		watchDynamicIcons();
	});
	
	// Also run after a short delay to catch late-loaded elements
	setTimeout(function() {
		convertMappingIcons();
	}, 500);
	
})(jQuery);

/**
 * CanvaStack Form Validation Component
 * 
 * Handles Bootstrap form validation (works with both Bootstrap 4 and 5).
 * 
 * @package CanvaStack
 * @subpackage Global Forms
 * @version 1.0.0
 * @author CanvaStack
 */

(function(window, document) {
	'use strict';

	/**
	 * Initialize Bootstrap form validation
	 * Adds validation classes and prevents invalid form submission
	 */
	function init() {
		window.addEventListener('load', function() {
			// Fetch all forms with needs-validation class
			var forms = document.getElementsByClassName('needs-validation');
			
			// Loop over them and prevent submission if invalid
			Array.prototype.filter.call(forms, function(form) {
				form.addEventListener('submit', function(event) {
					if (form.checkValidity() === false) {
						event.preventDefault();
						event.stopPropagation();
					}
					form.classList.add('was-validated');
				}, false);
			});
			
			console.log('CanvaStack Form Validation initialized');
		}, false);
	}

	// Auto-initialize
	init();

	// Export for manual initialization
	window.CanvaStackFormValidation = {
		init: init
	};

})(window, document);

<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * Text Input Elements Trait
 * 
 * Provides methods for generating various text-based form input elements.
 * All methods automatically escape user input to prevent XSS attacks.
 * 
 * LABEL ASSOCIATION PATTERN:
 * ==========================
 * All text input elements follow a consistent label-input association pattern:
 * 
 * 1. Visible Label Pattern (default):
 *    - Label element has for="field_name" attribute
 *    - Input element has id="field_name" attribute
 *    - This creates proper accessibility association for screen readers
 *    - Example: <label for="email">Email</label><input id="email" name="email" />
 * 
 * 2. Hidden Label Pattern (label=false):
 *    - No visible label element is rendered
 *    - Input element has aria-label="Field Name" attribute
 *    - For required fields: aria-label="Field Name (required)"
 *    - This provides accessible name for screen readers without visual label
 * 
 * 3. Required Field Pattern:
 *    - Visual asterisk (*) with aria-label="required field"
 *    - Input element has aria-required="true" attribute
 *    - aria-label includes "(required)" text when no visible label
 * 
 * Created on 19 Mar 2021
 * Time Created	: 03:10:46
 *
 * @filesource	Text.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 * 
 * @security All user-controllable values are escaped via setParams()
 * @security Attributes are validated to prevent event handler injection
 * @security Output is marked as safe HTML to prevent double-encoding
 * 
 * @accessibility Label for attribute matches input id for proper association
 * @accessibility aria-label provided for inputs without visible labels
 * @accessibility Required fields include both visual (*) and aria-required
 * 
 * Updated: 31 Mar 2026 - Added XSS protection and security enhancements
 */
 
trait Text {
	use AriaHelper;
	
	/**
	 * Create Input Text
	 * 
	 * Generates a standard text input field with automatic XSS protection.
	 * All user-controllable values (name, value, attributes) are escaped.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default field value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * 
	 * @example
	 * // Basic text input
	 * $form->text('username', null, [], true);
	 * 
	 * // Text input with default value
	 * $form->text('email', 'user@example.com', ['class' => 'custom'], true);
	 */
	public function text(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Textarea
	 * 
	 * Generates a textarea element with optional character limit functionality.
	 * Supports CKEditor integration via class attribute.
	 * 
	 * Format for maxlength: "textarea_name|limit:100"
	 * This will add bootstrap-maxlength plugin with character counter.
	 *
	 * @param string $name Field name (supports pipe format: "name|limit:100")
	 * @param string|null $value Default textarea content (will be escaped)
	 * @param array $attributes HTML attributes for the textarea element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All user-controllable values are escaped in setParams()
	 * @security Placeholder text is escaped to prevent XSS
	 * @security CKEditor class detection is validated via canvastack_form_check_str_attr()
	 * @security Attributes array is validated to prevent event handler injection
	 * 
	 * @example
	 * // Basic textarea
	 * $form->textarea('description', null, [], true);
	 * 
	 * // Textarea with character limit
	 * $form->textarea('bio|limit:500', null, [], true);
	 * 
	 * // Textarea with CKEditor
	 * $form->textarea('content', null, ['class' => 'ckeditor'], true);
	 */
	public function textarea(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		// Check for CKEditor plugin
		if (canvastack_form_check_str_attr($attributes, FormConstants::PLUGIN_CKEDITOR)) {
			$this->element_plugins[$name] = FormConstants::PLUGIN_CKEDITOR;
		}

		// Parse character limit if present
		$parsedData = $this->parseTextareaLimit($name, $attributes);
		$name = $parsedData['name'];
		$attributes = $parsedData['attributes'];

		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);

		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}

	/**
	 * Parse textarea character limit from name
	 * 
	 * @param string $name Field name (may contain |limit:N)
	 * @param array $attributes Current attributes
	 * 
	 * @return array Parsed name and attributes
	 */
	private function parseTextareaLimit(string $name, array $attributes): array {
		if (!str_contains($name, '|')) {
			return ['name' => $name, 'attributes' => $attributes];
		}

		$nameParts = explode('|', $name);
		$actualName = $nameParts[0];

		if (!str_contains($nameParts[1], ':')) {
			return ['name' => $actualName, 'attributes' => $attributes];
		}

		$limitParts = explode(':', $nameParts[1]);
		$limitValue = $limitParts[1];

		// Security: Escape maxlength value for placeholder
		$maxlengthEscaped = canvastack_form_escape_html($limitValue);

		$limitAttributes = [
			FormConstants::ATTR_CLASS => FormConstants::CLASS_FORM_CONTROL . ' bootstrap-maxlength character-limit',
			FormConstants::ATTR_MAXLENGTH => $limitValue,
			FormConstants::ATTR_PLACEHOLDER => "{$maxlengthEscaped} character limit"
		];

		return [
			'name' => $actualName,
			'attributes' => array_merge($limitAttributes, $attributes)
		];
	}
	
	/**
	 * Create Input Email
	 * 
	 * Generates an email input field with HTML5 email validation.
	 * Browser will validate email format automatically.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default email value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security HTML5 email validation provides additional client-side protection
	 * 
	 * @example
	 * // Basic email input
	 * $form->email('user_email', null, [], true);
	 * 
	 * // Email input with default value
	 * $form->email('contact_email', 'admin@example.com', [], true);
	 */
	public function email(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Number
	 * 
	 * Generates a number input field with HTML5 number validation.
	 * Browser will restrict input to numeric values only.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default numeric value (will be escaped)
	 * @param array $attributes HTML attributes (supports min, max, step attributes)
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security HTML5 number validation provides additional client-side protection
	 * 
	 * @example
	 * // Basic number input
	 * $form->number('quantity', null, [], true);
	 * 
	 * // Number input with min/max
	 * $form->number('age', null, ['min' => 18, 'max' => 100], true);
	 */
	public function number(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Password
	 * 
	 * Generates a password input field that masks user input.
	 * Password values are automatically hashed before storage.
	 *
	 * @param string $name Field name attribute
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security Password values are bcrypt hashed in inputDraw() before storage
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security Password fields never display existing values for security
	 * 
	 * @example
	 * // Basic password input
	 * $form->password('user_password', [], true);
	 * 
	 * // Password with custom attributes
	 * $form->password('new_password', ['minlength' => 8], true);
	 */
	public function password(string $name, array $attributes = [], bool|string|null $label = true): void {
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, null, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Generate Input Tags
	 * 
	 * Generates a tags input field using Bootstrap Tags Input plugin.
	 * Allows users to enter multiple comma-separated values as tags.
	 * 
	 * Format: "input_name|input_icon_name|input_icon_position"
	 * Example: "keywords|tag|right"
	 * Default icon position is left.
	 *
	 * @param string $name Field name (supports pipe format for icon configuration)
	 * @param string|null $value Default tags value (comma-separated, will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Placeholder text is escaped to prevent XSS
	 * @security Attributes array is validated to prevent event handler injection
	 * @security data-role attribute is set to 'tagsinput' for plugin initialization
	 * 
	 * @example
	 * // Basic tags input
	 * $form->tags('keywords', null, [], true);
	 * 
	 * // Tags input with default values
	 * $form->tags('tags', 'php,laravel,mysql', [], true);
	 * 
	 * @author wisnuwidi
	 */
	public function tags(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = $this->prepareTagsAttributes($name, $attributes);

		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);

		$this->setParams('tagsinput', $name, $value, $attributes, $label);
		$this->inputDraw('tagsinput', $name);
	}

	/**
	 * Prepare attributes for tags input
	 * 
	 * @param string $name Field name
	 * @param array $attributes Current attributes
	 * 
	 * @return array Prepared attributes
	 */
	private function prepareTagsAttributes(string $name, array $attributes): array {
		// Security: Escape placeholder value
		$placeholder = canvastack_form_escape_html(ucwords(str_replace('-', ' ', canvastack_clean_strings($name))));

		$attributes = canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_DATA_ROLE, FormConstants::CLASS_TAGSINPUT);
		$attributes = canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_PLACEHOLDER, "Type {$placeholder}");

		return $attributes;
	}
	
	/**
	 * Get ARIA attributes for text input fields
	 * 
	 * Generates appropriate ARIA attributes based on field state:
	 * - aria-required for required fields
	 * - aria-invalid for fields with validation errors
	 * - aria-describedby for help text or error messages
	 * - aria-label for fields without visible labels (includes "required" text if applicable)
	 * 
	 * @param string $name Field name
	 * @param array $attributes HTML attributes
	 * 
	 * @return array ARIA attributes to be added to the input
	 * 
	 * @security All ARIA attribute values are escaped
	 * @accessibility aria-label includes "required" text for required fields without visible labels
	 */
	private function getTextAriaAttributes(string $name, array $attributes): array {
		$ariaAttrs = [];
		
		// Check if field is required
		$isRequired = $this->isFieldRequired($name, $attributes);
		if ($isRequired) {
			$ariaAttrs[FormConstants::ARIA_REQUIRED] = 'true';
		}
		
		// Check if field has validation errors
		$hasErrors = $this->hasValidationErrors($name);
		if ($hasErrors) {
			$ariaAttrs[FormConstants::ARIA_INVALID] = 'true';
			$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] = canvastack_form_escape_html($name) . '-error';
		}
		
		// Add aria-describedby for help text if present
		if (isset($attributes['help']) && !empty($attributes['help'])) {
			$describedBy = canvastack_form_escape_html($name) . '-help';
			if (isset($ariaAttrs[FormConstants::ARIA_DESCRIBEDBY])) {
				$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] .= ' ' . $describedBy;
			} else {
				$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] = $describedBy;
			}
		}
		
		// Add aria-label if no visible label
		if (isset($attributes['label']) && $attributes['label'] === false) {
			$labelText = $this->generateFieldLabel($name);
			// Accessibility: Include "required" in aria-label for required fields
			if ($isRequired) {
				$labelText .= ' (required)';
			}
			$ariaAttrs[FormConstants::ARIA_LABEL] = $labelText;
		}
		
		return $ariaAttrs;
	}
	
	/**
	 * Build ARIA attributes string for HTML output
	 * 
	 * Converts an array of ARIA attributes into an HTML attribute string.
	 * All values are properly escaped.
	 * 
	 * @param array $ariaAttributes ARIA attributes array
	 * 
	 * @return string HTML attribute string
	 * 
	 * @security All attribute values are escaped
	 */
	// Method moved to AriaHelper trait to avoid duplication across element traits
	
	/**
	 * Create Barcode Input Field
	 * 
	 * Generates a barcode input field with preview, scanner, and auto-generate capabilities.
	 * Follows the standard Canvastack form pattern using setParams() and inputDraw().
	 * 
	 * @param string $name Field name attribute
	 * @param array $options Configuration options for barcode functionality
	 * @param bool|string|null $label Whether to display label (true for auto-generate, false for none, string for custom)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() and renderBarcodeInput()
	 * @security Follows same pattern as text(), email(), password(), tags() methods
	 * 
	 * @example
	 * // Basic barcode input with scanner
	 * $form->barcode('barcode', ['scanner' => true, 'format' => 'CODE128']);
	 * 
	 * // Barcode with auto-generate from ID field
	 * $form->barcode('barcode', [
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => 'id',
	 *     'auto_generate_prefix' => 'PRD'
	 * ]);
	 * 
	 * // Barcode with multiple source fields
	 * $form->barcode('barcode', [
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => ['id', 'sku'],
	 *     'auto_generate_separator' => '-'
	 * ]);
	 */
	public function barcode(string $name, array $options = [], bool|string|null $label = true): void {
		// Register barcode plugin for automatic asset loading
		$this->element_plugins[$name] = 'barcode';
		
		// Extract label from options if provided
		if (isset($options['label'])) {
			$label = $options['label'];
			unset($options['label']);
		}
		
		// Get value from model if available (for edit mode)
		$value = null;
		if (isset($this->model_data->$name)) {
			$value = $this->model_data->$name;
		}
		
		// Store options in attributes for renderBarcodeInput
		$attributes = ['barcode_options' => $options];
		
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		// Use standard Canvastack pattern: setParams + inputDraw
		$this->setParams('barcode', $name, $value, $attributes, $label);
		$this->inputDraw('barcode', $name);
	}
	
	/**
	 * Create QR Code Input Field
	 * 
	 * Generates a QR code input field with preview, scanner, auto-generate, and form data generation capabilities.
	 * Supports multiple data formats (text, URL, JSON, vCard, WiFi) and real-time updates.
	 * Follows the standard Canvastack form pattern using setParams() and inputDraw().
	 * 
	 * @param string $name Field name attribute
	 * @param array $options Configuration options for QR code functionality
	 * @param bool|string|null $label Whether to display label (true for auto-generate, false for none, string for custom)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() and renderQrcodeInput()
	 * @security Follows same pattern as text(), email(), password(), barcode() methods
	 * 
	 * ============================================================================
	 * AVAILABLE OPTIONS
	 * ============================================================================
	 * 
	 * BASIC OPTIONS:
	 * - format: 'text'|'url'|'vcard'|'wifi' - QR code data format (default: 'text')
	 * - size: int - QR code size in pixels (default: 200)
	 * - error_correction: 'L'|'M'|'Q'|'H' - Error correction level (default: 'M')
	 * - preview: bool - Show QR code preview (default: true)
	 * - preview_position: 'top'|'bottom' - Preview position (default: 'top')
	 * - scanner: bool - Enable webcam scanner (default: false)
	 * - scanner_button_text: string - Scanner button text (default: 'Scan')
	 * - validate: bool - Enable QR code validation (default: false)
	 * - help: bool - Show help button with modal (default: true)
	 * - placeholder: string - Input placeholder text
	 * 
	 * AUTO-GENERATE OPTIONS (Simple ID/URL Generator):
	 * Purpose: Generate QR code from specific field IDs (1-3 fields) for tracking/lookup
	 * Use Case: Customer scans → redirects to URL → server looks up data from database
	 * 
	 * - auto_generate: bool - Enable auto-generate button (magic icon)
	 * - auto_generate_source: string|array - Field name(s) to use as source (e.g., 'id' or ['id', 'sku'])
	 * - auto_generate_separator: string - Separator between multiple sources (default: '-')
	 * - auto_generate_prefix: string - Prefix for generated value (e.g., 'https://shop.com/p/')
	 * - auto_generate_format: 'text'|'url' - Output format (default: 'text')
	 * 
	 * FORM DATA GENERATION OPTIONS (Complete Data Generator):
	 * Purpose: Generate QR code from filled form data (multiple fields) for offline access
	 * Use Case: Staff scans → mobile app gets complete data → works offline
	 * 
	 * - form_fields: 'all'|array - Which fields to include (default: 'all')
	 *   * 'all' = Include all form fields
	 *   * ['sku', 'name', 'price'] = Include only specified fields
	 * - form_format: 'json'|'text'|'url' - Output format (default: 'json')
	 * - form_exclude: array - Fields to exclude (default: ['_token', '_method', 'image', 'file'])
	 * - form_url_base: string - Base URL for 'url' format (default: current domain)
	 * 
	 * REAL-TIME AUTO-UPDATE OPTIONS:
	 * - auto_update_from_form: bool - Auto-update QR when form fields change (default: false)
	 * - auto_update_delay: int - Debounce delay in milliseconds (default: 500)
	 * - auto_clear_empty: bool - Clear QR when all fields are empty (default: false)
	 * 
	 * ============================================================================
	 * KEY DIFFERENCES: auto_generate_source vs form_fields
	 * ============================================================================
	 * 
	 * auto_generate_source (Simple ID Generator):
	 * - Trigger: Magic button (⚡) or when source fields change
	 * - Data: 1-3 specific fields only (e.g., ID, SKU)
	 * - Output: Simple string or URL (e.g., "https://shop.com/p/123-ABC")
	 * - QR Size: Small, easy to scan
	 * - Use Case: Product tracking, URL redirects, online lookup
	 * - Internet: Required (QR contains pointer, not data)
	 * 
	 * form_fields (Complete Data Generator):
	 * - Trigger: "Generate from Form" button (📄) or auto-update on field change
	 * - Data: Multiple fields from form (e.g., SKU, Name, Price, Stock)
	 * - Output: Structured JSON or formatted text
	 * - QR Size: Larger, more complex
	 * - Use Case: Offline inventory, mobile apps, data sharing
	 * - Internet: Not required (QR contains actual data)
	 * 
	 * ============================================================================
	 * EXAMPLES
	 * ============================================================================
	 * 
	 * @example
	 * // Example 1: Basic QR code with scanner
	 * $form->qrcode('qr_code', [
	 *     'scanner' => true,
	 *     'placeholder' => 'Scan or enter QR code'
	 * ]);
	 * 
	 * @example
	 * // Example 2: Simple URL Generator (for customer tracking)
	 * // Use Case: Customer scans QR → browser opens URL → shows product page
	 * $form->qrcode('product_qr', [
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => ['id', 'sku'],
	 *     'auto_generate_format' => 'url',
	 *     'auto_generate_prefix' => 'https://myshop.com/product/',
	 *     'auto_generate_separator' => '-'
	 * ]);
	 * // Output: https://myshop.com/product/123-CLOTH-001
	 * 
	 * @example
	 * // Example 3: Complete Data Generator (for offline inventory)
	 * // Use Case: Staff scans QR → mobile app gets data → works without internet
	 * $form->qrcode('inventory_qr', [
	 *     'form_fields' => ['sku', 'name', 'location', 'stock'],
	 *     'form_format' => 'json',
	 *     'form_exclude' => ['_token', '_method']
	 * ]);
	 * // Output: {"Sku":"WH-001","Name":"Chair","Location":"A-5","Stock":"45"}
	 * 
	 * @example
	 * // Example 4: Hybrid Approach (Best of Both!)
	 * // Provides both URL QR (small) and Data QR (complete)
	 * $form->qrcode('product_qr', [
	 *     // Simple URL for customers
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => ['id', 'sku'],
	 *     'auto_generate_format' => 'url',
	 *     'auto_generate_prefix' => 'https://shop.com/p/',
	 *     
	 *     // Complete data for staff
	 *     'form_fields' => ['sku', 'name', 'selling_price', 'stock'],
	 *     'form_format' => 'json',
	 *     
	 *     // Common options
	 *     'scanner' => true,
	 *     'preview_position' => 'top'
	 * ]);
	 * 
	 * @example
	 * // Example 5: Real-time Auto-Update (for live preview)
	 * // QR code updates automatically as user types
	 * $form->qrcode('live_qr', [
	 *     'auto_update_from_form' => true,
	 *     'auto_update_delay' => 500,
	 *     'form_fields' => ['name', 'email', 'phone'],
	 *     'form_format' => 'json'
	 * ]);
	 * 
	 * @example
	 * // Example 6: vCard QR Code (for contact sharing)
	 * $form->qrcode('contact_qr', [
	 *     'format' => 'vcard',
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => ['name', 'phone', 'email']
	 * ]);
	 * 
	 * @example
	 * // Example 7: WiFi QR Code (for network sharing)
	 * $form->qrcode('wifi_qr', [
	 *     'format' => 'wifi',
	 *     'auto_generate' => true,
	 *     'auto_generate_source' => ['ssid', 'password']
	 * ]);
	 * 
	 * @example
	 * // Example 8: Selective Fields (only specific data)
	 * // Only include essential fields, exclude sensitive data
	 * $form->qrcode('public_qr', [
	 *     'form_fields' => ['sku', 'name', 'selling_price'], // Only these 3 fields
	 *     'form_exclude' => ['_token', '_method', 'purchase_price', 'cost'], // Exclude sensitive
	 *     'form_format' => 'json'
	 * ]);
	 * 
	 * ============================================================================
	 * REAL-WORLD SCENARIOS
	 * ============================================================================
	 * 
	 * Scenario 1: E-commerce Product (Customer-facing)
	 * - Use auto_generate_source for small QR on product label
	 * - Customer scans → redirects to product page
	 * - Requires internet connection
	 * 
	 * Scenario 2: Warehouse Inventory (Staff-facing)
	 * - Use form_fields for complete data in QR
	 * - Staff scans → mobile app shows all info offline
	 * - No internet required
	 * 
	 * Scenario 3: Retail POS (Hybrid)
	 * - auto_generate_source: URL QR for customers
	 * - form_fields: Data QR for internal staff
	 * - Both buttons available, user chooses
	 * 
	 * ============================================================================
	 * BUTTON REFERENCE
	 * ============================================================================
	 * 
	 * 🔍 Scanner Button (if scanner: true)
	 * - Opens webcam to scan existing QR codes
	 * - Fills input with scanned data
	 * 
	 * ⚡ Magic Button (if auto_generate: true)
	 * - Generates QR from auto_generate_source fields
	 * - Creates simple URL or text
	 * - Small QR code, easy to scan
	 * 
	 * 📄 Generate from Form Button (always shown)
	 * - Generates QR from form_fields configuration
	 * - Creates JSON or structured data
	 * - Complete data, larger QR code
	 * 
	 * ❓ Help Button (if help: true)
	 * - Shows modal with usage instructions
	 * - Explains all features and formats
	 */
	public function qrcode(string $name, array $options = [], bool|string|null $label = true): void {
		// Register qrcode plugin for automatic asset loading
		$this->element_plugins[$name] = 'qrcode';
		
		// Extract label from options if provided
		if (isset($options['label'])) {
			$label = $options['label'];
			unset($options['label']);
		}
		
		// Get value from model if available (for edit mode)
		$value = null;
		if (isset($this->model_data->$name)) {
			$value = $this->model_data->$name;
		}
		
		// Store options in attributes for renderQrcodeInput
		$attributes = ['qrcode_options' => $options];
		
		// Merge ARIA attributes for accessibility
		$ariaAttributes = $this->getTextAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		// Use standard Canvastack pattern: setParams + inputDraw
		$this->setParams('qrcode', $name, $value, $attributes, $label);
		$this->inputDraw('qrcode', $name);
	}

}

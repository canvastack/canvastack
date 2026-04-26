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
	 * Check if field is required
	 * 
	 * Checks both the attributes array and validation rules to determine
	 * if a field is required.
	 * 
	 * @param string $name Field name
	 * @param array $attributes HTML attributes
	 * 
	 * @return bool True if field is required
	 */
	// Method moved to AriaHelper trait to avoid duplication across element traits
	
	/**
	 * Check if field has validation errors
	 * 
	 * Checks Laravel's error bag to determine if the field has validation errors.
	 * 
	 * @param string $name Field name
	 * 
	 * @return bool True if field has validation errors
	 */
	// Method moved to AriaHelper trait to avoid duplication across element traits
	
	/**
	 * Generate field label from field name
	 * 
	 * Converts field name to human-readable label by replacing underscores
	 * and hyphens with spaces and capitalizing words.
	 * 
	 * @param string $name Field name
	 * 
	 * @return string Generated label (escaped)
	 */
	// Method moved to AriaHelper trait to avoid duplication across element traits
	
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
}

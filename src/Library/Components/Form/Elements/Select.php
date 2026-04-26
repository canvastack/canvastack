<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * Select Input Elements Trait
 * 
 * Provides methods for generating select dropdown form elements.
 * All methods automatically escape user input to prevent XSS attacks.
 * 
 * LABEL ASSOCIATION PATTERN:
 * ==========================
 * All select elements follow a consistent label-input association pattern:
 * 
 * 1. Visible Label Pattern (default):
 *    - Label element has for="field_name" attribute
 *    - Select element has id="field_name" attribute
 *    - This creates proper accessibility association for screen readers
 *    - Example: <label for="country">Country</label><select id="country" name="country">...</select>
 * 
 * 2. Hidden Label Pattern (label=false):
 *    - No visible label element is rendered
 *    - Select element has aria-label="Field Name" attribute
 *    - For required fields: aria-label="Field Name (required)"
 *    - This provides accessible name for screen readers without visual label
 * 
 * 3. Required Field Pattern:
 *    - Visual asterisk (*) with aria-label="required field"
 *    - Select element has aria-required="true" attribute
 *    - aria-label includes "(required)" text when no visible label
 * 
 * Created on 19 Mar 2021
 * Time Created	: 03:17:34
 *
 * @filesource	Select.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 * 
 * @security All user-controllable values are escaped via setParams()
 * @security Option labels and values are escaped to prevent XSS
 * @security Attributes are validated to prevent event handler injection
 * @security Output is marked as safe HTML to prevent double-encoding
 * 
 * @accessibility Label for attribute matches select id for proper association
 * @accessibility aria-label provided for selects without visible labels
 * @accessibility Required fields include both visual (*) and aria-required
 * 
 * Updated: 31 Mar 2026 - Added XSS protection and security enhancements
 */
 
trait Select {
	use AriaHelper;
	
	/**
	 * Create Input Selectbox
	 * 
	 * Generates a select dropdown element with automatic XSS protection.
	 * All option labels and values are escaped to prevent XSS attacks.
	 * Supports Chosen.js plugin for enhanced dropdown functionality.
	 *
	 * @param string $name Field name attribute
	 * @param array $values Options array [value => label] (labels will be escaped)
	 * @param bool|string|int|array|null $selected Selected value(s) - can be single value, array for multi-select, or null
	 * @param array $attributes HTML attributes for the select element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * @param array|bool $set_first_value First option to prepend (default: [null => ''], false to skip)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All option labels are escaped to prevent XSS
	 * @security All option values are escaped to prevent XSS
	 * @security Attributes array is validated in setParams() to prevent event handler injection
	 * @security Selected values are validated and escaped
	 * 
	 * @example
	 * // Basic select dropdown
	 * $form->selectbox('country', ['us' => 'United States', 'uk' => 'United Kingdom'], false, [], true);
	 * 
	 * // Select with pre-selected value
	 * $form->selectbox('status', ['active' => 'Active', 'inactive' => 'Inactive'], 'active', [], true);
	 * 
	 * // Select without default empty option
	 * $form->selectbox('role', ['admin' => 'Admin', 'user' => 'User'], false, [], true, false);
	*/
	public function selectbox(string $name, array $values = [], bool|string|int|array|null $selected = false, array $attributes = [], bool|string|null $label = true, array|bool $set_first_value = [null => '']): void {
		$attributes = canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_CLASS, FormConstants::CLASS_CHOSEN_SELECT);

		// Add ARIA attributes
		$ariaAttributes = $this->getSelectAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);

		// Remove empty first value if present
		if (isset($values[0]) && empty($values[0]) && !empty($set_first_value)) {
			unset($values[0]);
		}

		// Escape all option values and labels
		$escapedValues = $this->escapeSelectOptions($values, $set_first_value);

		$this->setParams('select', $name, $escapedValues, $attributes, $label, $selected);
		$this->inputDraw('select', $name);
	}

	/**
	 * Escape select option values and labels
	 * 
	 * @param array $values Option values
	 * @param array|bool $set_first_value First option to prepend
	 * 
	 * @return array Escaped options
	 */
	private function escapeSelectOptions(array $values, array|bool $set_first_value): array {
		$escapedValues = [];

		// Add first value if provided
		if ($set_first_value !== false && is_array($set_first_value)) {
			$escapedValues = $this->escapeOptionArray($set_first_value);
		}

		// Escape all other options
		foreach ($values as $key => $value) {
			if (!empty($value) || $set_first_value === false) {
				$escapedKey = canvastack_form_escape_html($key);
				$escapedLabel = canvastack_form_escape_html($value);
				$escapedValues[$escapedKey] = $escapedLabel;
			}
		}

		return $escapedValues;
	}

	/**
	 * Escape option array (key => value pairs)
	 * 
	 * @param array $options Options to escape
	 * 
	 * @return array Escaped options
	 */
	private function escapeOptionArray(array $options): array {
		$escaped = [];

		foreach ($options as $key => $value) {
			$escapedKey = is_null($key) ? null : canvastack_form_escape_html($key);
			$escaped[$escapedKey] = canvastack_form_escape_html($value);
		}

		return $escaped;
	}
	
	/**
	 * Create Input Month
	 * 
	 * Generates a month picker select dropdown with automatic XSS protection.
	 * Uses Chosen.js plugin for enhanced dropdown functionality.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default month value (will be escaped)
	 * @param array $attributes HTML attributes for the select element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated in setParams() to prevent event handler injection
	 * @security Month values are validated by Laravel Form facade
	 * 
	 * @example
	 * // Basic month picker
	 * $form->month('birth_month', null, [], true);
	 * 
	 * // Month picker with default value
	 * $form->month('report_month', '2026-03', [], true);
	 */
	public function month(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_CLASS, FormConstants::CLASS_CHOSEN_SELECT);
		
		// Add ARIA attributes
		$ariaAttributes = $this->getSelectAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams('selectMonth', $name, $value, $attributes, $label);
		$this->inputDraw('selectMonth', $name);
	}
	
	/**
	 * Get ARIA attributes for select elements
	 * 
	 * Generates appropriate ARIA attributes based on field state:
	 * - aria-required for required fields
	 * - aria-invalid for fields with validation errors
	 * - aria-describedby for help text and error messages
	 * - aria-label for fields without visible labels
	 * 
	 * Note: This method uses helper methods from AriaHelper trait.
	 * 
	 * @param string $name Field name
	 * @param array $attributes Element attributes
	 * 
	 * @return array ARIA attributes to add
	 */
	private function getSelectAriaAttributes(string $name, array $attributes): array {
		$ariaAttrs = [];
		
		// Check if field is required (uses AriaHelper trait's isFieldRequired method)
		$isRequired = $this->isFieldRequired($name, $attributes);
		if ($isRequired) {
			$ariaAttrs[FormConstants::ARIA_REQUIRED] = 'true';
		}
		
		// Check if field has validation errors (uses AriaHelper trait's hasValidationErrors method)
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
		
		// Add aria-label if no visible label (uses AriaHelper trait's generateFieldLabel method)
		if (isset($attributes['label']) && $attributes['label'] === false) {
			$labelText = $this->generateFieldLabel($name);
			$ariaAttrs[FormConstants::ARIA_LABEL] = $labelText;
		}
		
		return $ariaAttrs;
	}
}
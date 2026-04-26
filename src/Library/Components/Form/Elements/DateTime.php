<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * DateTime Input Elements Trait
 * 
 * Provides methods for generating date and time form input elements.
 * All methods automatically escape user input to prevent XSS attacks.
 * 
 * LABEL ASSOCIATION PATTERN:
 * ==========================
 * All date/time input elements follow a consistent label-input association pattern:
 * 
 * 1. Visible Label Pattern (default):
 *    - Label element has for="field_name" attribute
 *    - Input element has id="field_name" attribute
 *    - This creates proper accessibility association for screen readers
 *    - Example: <label for="birth_date">Birth Date</label><input id="birth_date" name="birth_date" />
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
 * Time Created	: 03:15:58
 *
 * @filesource	DateTime.php
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
 
trait DateTime {
	
	/**
	 * Create Input Date
	 * 
	 * Generates a date picker input field with automatic XSS protection.
	 * All user-controllable values (name, value, attributes) are escaped.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default date value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security Date format values are escaped to prevent XSS
	 * 
	 * @example
	 * // Basic date input
	 * $form->date('birth_date', null, [], true);
	 * 
	 * // Date input with default value
	 * $form->date('start_date', '2026-03-31', [], true);
	 */
	public function date(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = $this->addDatePickerClass($attributes, 'date-picker');
		
		// Add ARIA attributes
		$ariaAttributes = $this->getDateTimeAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Datetime
	 * 
	 * Generates a datetime picker input field with automatic XSS protection.
	 * Combines date and time selection in a single input.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default datetime value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security Datetime format values are escaped to prevent XSS
	 * 
	 * @example
	 * // Basic datetime input
	 * $form->datetime('event_time', null, [], true);
	 * 
	 * // Datetime input with default value
	 * $form->datetime('meeting_time', '2026-03-31 14:30:00', [], true);
	 */
	public function datetime(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = $this->addDatePickerClass($attributes, 'datetime-picker');
		
		// Add ARIA attributes
		$ariaAttributes = $this->getDateTimeAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Daterange
	 * 
	 * Generates a date range picker input field with automatic XSS protection.
	 * Allows users to select a start and end date in a single input.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default date range value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security Date range format values are escaped to prevent XSS
	 * 
	 * @example
	 * // Basic daterange input
	 * $form->daterange('booking_period', null, [], true);
	 * 
	 * // Daterange input with default value
	 * $form->daterange('report_period', '2026-03-01 - 2026-03-31', [], true);
	 */
	public function daterange(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = $this->addDatePickerClass($attributes, 'daterange-picker');
		
		// Add ARIA attributes
		$ariaAttributes = $this->getDateTimeAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Create Input Time
	 * 
	 * Generates a time picker input field with automatic XSS protection.
	 * Uses Bootstrap Timepicker plugin for time selection.
	 *
	 * @param string $name Field name attribute
	 * @param string|null $value Default time value (will be escaped)
	 * @param array $attributes HTML attributes for the input element
	 * @param bool $label Whether to display label (true) or hide it (false)
	 * 
	 * @return void Output is rendered via inputDraw()
	 * 
	 * @security All parameters are escaped in setParams() before rendering
	 * @security Attributes array is validated to prevent event handler injection
	 * @security Time format values are escaped to prevent XSS
	 * 
	 * @example
	 * // Basic time input
	 * $form->time('start_time', null, [], true);
	 * 
	 * // Time input with default value
	 * $form->time('alarm_time', '14:30', [], true);
	 */
	public function time(string $name, ?string $value = null, array $attributes = [], bool|string|null $label = true): void {
		$attributes = $this->addDatePickerClass($attributes, 'bootstrap-timepicker');
		
		// Add ARIA attributes
		$ariaAttributes = $this->getDateTimeAriaAttributes($name, $attributes);
		$attributes = array_merge($attributes, $ariaAttributes);
		
		$this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
		$this->inputDraw(__FUNCTION__, $name);
	}

	/**
	 * Add date picker class to attributes
	 * 
	 * Helper method to reduce duplicate code for adding picker classes.
	 * 
	 * @param array $attributes Current attributes
	 * @param string $pickerClass Picker class to add
	 * 
	 * @return array Modified attributes
	 */
	private function addDatePickerClass(array $attributes, string $pickerClass): array {
		return canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_CLASS, $pickerClass);
	}
	
	/**
	 * Get ARIA attributes for date/time elements
	 * 
	 * Generates appropriate ARIA attributes based on field state:
	 * - aria-required for required fields
	 * - aria-invalid for fields with validation errors
	 * - aria-describedby for format hints and error messages
	 * - aria-label for fields without visible labels (includes "required" text if applicable)
	 * 
	 * @param string $name Field name
	 * @param array $attributes Element attributes
	 * 
	 * @return array ARIA attributes to add
	 * 
	 * @accessibility aria-label includes "required" text for required fields without visible labels
	 */
	private function getDateTimeAriaAttributes(string $name, array $attributes): array {
		$ariaAttrs = [];
		
		// Check if field is required
		$isRequired = $this->isDateTimeFieldRequired($name, $attributes);
		if ($isRequired) {
			$ariaAttrs[FormConstants::ARIA_REQUIRED] = 'true';
		}
		
		// Check if field has validation errors
		$hasErrors = $this->hasDateTimeValidationErrors($name);
		if ($hasErrors) {
			$ariaAttrs[FormConstants::ARIA_INVALID] = 'true';
			$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] = canvastack_form_escape_html($name) . '-error';
		}
		
		// Add aria-describedby for format hints if present
		if (isset($attributes['placeholder']) && !empty($attributes['placeholder'])) {
			$describedBy = canvastack_form_escape_html($name) . '-format';
			if (isset($ariaAttrs[FormConstants::ARIA_DESCRIBEDBY])) {
				$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] .= ' ' . $describedBy;
			} else {
				$ariaAttrs[FormConstants::ARIA_DESCRIBEDBY] = $describedBy;
			}
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
			$labelText = $this->generateDateTimeFieldLabel($name);
			// Accessibility: Include "required" in aria-label for required fields
			if ($isRequired) {
				$labelText .= ' (required)';
			}
			$ariaAttrs[FormConstants::ARIA_LABEL] = $labelText;
		}
		
		return $ariaAttrs;
	}
	
	/**
	 * Check if date/time field is required
	 * 
	 * Checks both the attributes array and validation rules to determine
	 * if a field is required.
	 * 
	 * @param string $name Field name
	 * @param array $attributes Element attributes
	 * 
	 * @return bool True if field is required
	 */
	private function isDateTimeFieldRequired(string $name, array $attributes): bool {
		// Check if 'required' is in attributes
		if (isset($attributes[FormConstants::ATTR_REQUIRED]) && $attributes[FormConstants::ATTR_REQUIRED]) {
			return true;
		}
		
		// Check validation rules
		if (!empty($this->validations[$name])) {
			$rules = is_array($this->validations[$name]) ? $this->validations[$name] : explode('|', $this->validations[$name]);
			return in_array(FormConstants::VALIDATION_REQUIRED, $rules);
		}
		
		return false;
	}
	
	/**
	 * Check if date/time field has validation errors
	 * 
	 * Checks Laravel's error bag to determine if the field has validation errors.
	 * 
	 * @param string $name Field name
	 * 
	 * @return bool True if field has errors
	 */
	private function hasDateTimeValidationErrors(string $name): bool {
		// Check Laravel's error bag
		if (function_exists('session') && session()->has('errors')) {
			$errors = session()->get('errors');
			return $errors->has($name);
		}
		
		return false;
	}
	
	/**
	 * Generate field label from field name
	 * 
	 * Converts field name to human-readable label by replacing underscores
	 * and hyphens with spaces and capitalizing words.
	 * 
	 * @param string $name Field name
	 * 
	 * @return string Generated label
	 */
	private function generateDateTimeFieldLabel(string $name): string {
		$nameEscaped = canvastack_form_escape_html($name);
		return ucwords(str_replace('-', ' ', ucwords(str_replace('_', ' ', $nameEscaped))));
	}
}
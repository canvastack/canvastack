<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Canvastack\Canvastack\Library\Constants\FormConstants;

/**
 * ARIA Helper Trait
 * 
 * Provides shared helper methods for ARIA attribute generation across all form element traits.
 * This trait contains common functionality for accessibility compliance.
 * 
 * Methods in this trait:
 * - isFieldRequired(): Check if a field is required
 * - hasValidationErrors(): Check if a field has validation errors
 * - generateFieldLabel(): Generate human-readable label from field name
 * - buildAriaAttributesString(): Build ARIA attributes string for HTML output
 * 
 * Usage:
 * All element traits (Text, Select, File, Check, Radio, DateTime, Tab) should use this trait
 * to access shared ARIA helper functionality.
 * 
 * @filesource AriaHelper.php
 * 
 * @author wisnuwidi@canvastack.com - 2026
 * @copyright wisnuwidi
 * @email wisnuwidi@canvastack.com
 * 
 * Created: 31 Mar 2026 - Extracted from Text.php to avoid trait collision
 */
trait AriaHelper {
	
	/**
	 * Check if field is required
	 * 
	 * Checks both the attributes array and validation rules to determine
	 * if a field is required.
	 * 
	 * @param string $name Field name
	 * @param array $attributes Element attributes
	 * 
	 * @return bool True if field is required
	 */
	private function isFieldRequired(string $name, array $attributes): bool {
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
	 * Check if field has validation errors
	 * 
	 * Checks Laravel's error bag to determine if the field has validation errors.
	 * 
	 * @param string $name Field name
	 * 
	 * @return bool True if field has errors
	 */
	private function hasValidationErrors(string $name): bool {
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
	 * @return string Generated label (escaped)
	 */
	private function generateFieldLabel(string $name): string {
		$nameEscaped = canvastack_form_escape_html($name);
		return ucwords(str_replace('-', ' ', ucwords(str_replace('_', ' ', $nameEscaped))));
	}
	
	/**
	 * Build ARIA attributes string for HTML output
	 * 
	 * Converts an array of ARIA attributes into a properly formatted HTML attribute string.
	 * All keys and values are escaped to prevent XSS attacks.
	 * 
	 * @param array $ariaAttributes ARIA attributes array [key => value]
	 * 
	 * @return string ARIA attributes string (e.g., 'aria-required="true" aria-label="Field Name"')
	 * 
	 * @security All attribute values are escaped
	 */
	private function buildAriaAttributesString(array $ariaAttributes): string {
		if (empty($ariaAttributes)) {
			return '';
		}
		
		$attrs = [];
		foreach ($ariaAttributes as $key => $value) {
			$keyEscaped = canvastack_form_escape_html($key);
			$valueEscaped = canvastack_form_escape_html($value);
			$attrs[] = "{$keyEscaped}=\"{$valueEscaped}\"";
		}
		
		return implode(' ', $attrs);
	}
}

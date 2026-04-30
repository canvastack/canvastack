<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Collective\Html\FormFacade as Form;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

/**
 * Created on 19 Mar 2021
 * Time Created	: 03:31:26
 *
 * @filesource	Check.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
 
trait Check {
	
	public function checkbox(string $name, array $values = [], array|string $selected = [], array $attributes = [], bool|string|null $label = true): void {
		$this->setParams(__FUNCTION__, $name, $values, $attributes, $label, $selected);
		$this->inputDraw(__FUNCTION__, $name);
	}
	
	/**
	 * Draw Check Box
	 * 
	 * Renders checkbox elements with proper escaping and accessibility attributes.
	 * 
	 * @param string $name Checkbox field name
	 * @param mixed|array|string $value Checkbox value(s) - array for multiple checkboxes
	 * @param string $selected Selected value(s) - can be string or array
	 * @param array $attributes HTML attributes for checkbox elements
	 * 
	 * @return string Safe HTML checkbox element(s)
	 * 
	 * @security All user-controllable parameters are escaped to prevent XSS
	 * @security Attributes array is validated to block dangerous event handlers
	 * @security Output is marked as safe HTML to prevent double-encoding
	 * 
	 * @throws \InvalidArgumentException If attributes contain dangerous event handlers
	 */
	/**
		 * Draw Check Box
		 * 
		 * Renders checkbox elements with proper escaping and accessibility attributes.
		 * 
		 * @param string $name Checkbox field name
		 * @param mixed|array|string $value Checkbox value(s) - array for multiple checkboxes
		 * @param string $selected Selected value(s) - can be string or array
		 * @param array $attributes HTML attributes for checkbox elements
		 * 
		 * @return string Safe HTML checkbox element(s)
		 * 
		 * @security All user-controllable parameters are escaped to prevent XSS
		 * @security Attributes array is validated to block dangerous event handlers
		 * @security Output is marked as safe HTML to prevent double-encoding
		 * 
		 * @throws \InvalidArgumentException If attributes contain dangerous event handlers
		 */
		private function drawCheckBox(string $name, mixed $value, array|string $selected, array $attributes = []): string {
			// Security: Validate attributes array for dangerous handlers
			$attributes = canvastack_form_validate_attributes($attributes);

			$values = is_array($value) ? $value : [$value];
			$checkbox = '';

			foreach ($values as $checkKey => $checkLabel) {
				$checkbox .= $this->renderSingleCheckbox($name, $checkKey, $checkLabel, $selected, $attributes);
			}

			// Security: Mark output as safe HTML to prevent double-encoding
			return \Canvastack\Canvastack\Library\Constants\SafeHtml::mark($checkbox);
		}

		/**
		 * Render a single checkbox element
		 * 
		 * @param string $name Checkbox field name
		 * @param mixed $checkKey Checkbox value
		 * @param mixed $checkLabel Checkbox label
		 * @param array|string $selected Selected value(s)
		 * @param array $attributes HTML attributes
		 * 
		 * @return string HTML for single checkbox
		 */
		private function renderSingleCheckbox(string $name, mixed $checkKey, mixed $checkLabel, array|string $selected, array $attributes): string {
			// Security: Escape check_key for use in ID generation
			$checkKeyEscaped = canvastack_form_escape_html($checkKey);

			$checkboxId = "canvastack{$checkKeyEscaped}:chx" . canvastack_random_strings(8, false);
			$checkAttributes = array_merge_recursive([FormConstants::ATTR_ID => $checkboxId], $attributes);

			$isSelected = $this->isCheckboxSelected($checkKey, $checkLabel, $selected);
			$checkboxType = $this->getCheckboxType($checkAttributes);
			$isSwitch = $this->isSwitchType($checkAttributes);

			// Add ARIA attributes
			$checkAttributes = $this->addCheckboxAriaAttributes($checkAttributes, $isSelected, $checkLabel, $checkKeyEscaped);

			// Remove check_type from attributes as it's not a valid HTML attribute
			unset($checkAttributes['check_type']);

			if ($isSwitch) {
				return $this->renderSwitchCheckbox($name, $checkKey, $checkKeyEscaped, $checkLabel, $isSelected, $checkAttributes, $checkboxId);
			}

			return $this->renderRegularCheckbox($name, $checkKey, $checkKeyEscaped, $checkLabel, $isSelected, $checkAttributes, $checkboxType, $checkboxId);
		}

		/**
		 * Check if checkbox should be selected
		 * 
		 * @param mixed $checkKey Checkbox key
		 * @param mixed $checkLabel Checkbox label
		 * @param array|string $selected Selected value(s)
		 * 
		 * @return bool True if selected
		 */
		private function isCheckboxSelected(mixed $checkKey, mixed $checkLabel, array|string $selected): bool {
			if (empty($selected)) {
				return false;
			}

			$selectedValues = is_array($selected) ? $selected : [$selected];

			foreach ($selectedValues as $selectValue) {
				// Use loose comparison to handle type differences (int vs string)
				if ($checkKey == $selectValue || $checkLabel == $selectValue) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get checkbox type CSS class
		 * 
		 * @param array $attributes Checkbox attributes
		 * 
		 * @return string CSS class for checkbox type
		 */
		private function getCheckboxType(array $attributes): string {
			if (!isset($attributes['check_type'])) {
				return ' ' . FormConstants::CLASS_CKBOX_PRIMARY;
			}

			if ($attributes['check_type'] === FormConstants::CLASS_SWITCH) {
				return '';
			}

			// Security: Escape checkbox type for CSS class
			return " " . FormConstants::CLASS_CKBOX . "-" . canvastack_form_escape_html($attributes['check_type']);
		}

		/**
		 * Check if checkbox is switch type
		 * 
		 * @param array $attributes Checkbox attributes
		 * 
		 * @return bool True if switch type
		 */
		private function isSwitchType(array $attributes): bool {
			return isset($attributes['check_type']) && $attributes['check_type'] === FormConstants::CLASS_SWITCH;
		}

		/**
		 * Add ARIA attributes to checkbox
		 * 
		 * @param array $attributes Current attributes
		 * @param bool $isSelected Whether checkbox is selected
		 * @param mixed $checkLabel Checkbox label
		 * @param string $checkKeyEscaped Escaped checkbox key
		 * 
		 * @return array Attributes with ARIA added
		 * 
		 * @accessibility aria-label includes "required" text for required checkboxes without visible labels
		 */
		private function addCheckboxAriaAttributes(array $attributes, bool $isSelected, mixed $checkLabel, string $checkKeyEscaped): array {
			$attributes[FormConstants::ARIA_CHECKED] = $isSelected ? 'true' : 'false';

			// Add aria-label if no visible label
			if ($checkLabel === false || $checkLabel === '') {
				$labelText = 'Checkbox ' . $checkKeyEscaped;
				// Accessibility: Include "required" in aria-label for required fields
				if (isset($attributes[FormConstants::ATTR_REQUIRED]) && $attributes[FormConstants::ATTR_REQUIRED]) {
					$labelText .= ' (required)';
				}
				$attributes[FormConstants::ARIA_LABEL] = $labelText;
			}

			// Add aria-disabled if disabled attribute is present
			if (isset($attributes[FormConstants::ATTR_DISABLED]) && $attributes[FormConstants::ATTR_DISABLED]) {
				$attributes[FormConstants::ARIA_DISABLED] = 'true';
			}

			// Add aria-required if required attribute is present
			if (isset($attributes[FormConstants::ATTR_REQUIRED]) && $attributes[FormConstants::ATTR_REQUIRED]) {
				$attributes[FormConstants::ARIA_REQUIRED] = 'true';
			}

			return $attributes;
		}

		/**
		 * Render switch-style checkbox
		 * 
		 * @param string $name Field name
		 * @param mixed $checkKey Checkbox key
		 * @param string $checkKeyEscaped Escaped checkbox key
		 * @param mixed $checkLabel Checkbox label
		 * @param bool $isSelected Whether selected
		 * @param array $checkAttributes Checkbox attributes
		 * @param string $checkboxId Checkbox ID
		 * 
		 * @return string HTML for switch checkbox
		 */
		private function renderSwitchCheckbox(string $name, mixed $checkKey, string $checkKeyEscaped, mixed $checkLabel, bool $isSelected, array $checkAttributes, string $checkboxId): string {
			// Merge switch class with existing class attribute
			$currentClass = $checkAttributes[FormConstants::ATTR_CLASS] ?? '';
			$switchClass = FormConstants::CLASS_SWITCH;

			if ($currentClass) {
				// Security: Escape class attribute value
				$currentClassEscaped = " " . canvastack_form_escape_html($currentClass);
				$checkAttributes[FormConstants::ATTR_CLASS] = "{$switchClass}{$currentClassEscaped}";
			} else {
				$checkAttributes[FormConstants::ATTR_CLASS] = $switchClass;
			}

			$openTag = '<div class="switch-box"><div class="s-swtich col-sm-5">';
			$labelTag = '<label for="' . canvastack_form_escape_html($checkboxId) . '">Toggle</label>';
			// Security: Escape check_label for display
			$endTag = '</div>' . Form::label($checkKey, canvastack_form_escape_html($checkLabel)) . '</div>';

			// Security: Escape name and check_key for form field name
			$checkboxInput = Form::checkbox(canvastack_form_escape_html($name) . "[{$checkKeyEscaped}]", $checkKeyEscaped, $isSelected, $checkAttributes);

			return $openTag . $checkboxInput . $labelTag . $endTag;
		}

		/**
		 * Render regular checkbox
		 * 
		 * @param string $name Field name
		 * @param mixed $checkKey Checkbox key
		 * @param string $checkKeyEscaped Escaped checkbox key
		 * @param mixed $checkLabel Checkbox label
		 * @param bool $isSelected Whether selected
		 * @param array $checkAttributes Checkbox attributes
		 * @param string $checkboxType CSS class for checkbox type
		 * @param string $checkboxId Checkbox ID
		 * 
		 * @return string HTML for regular checkbox
		 */
		private function renderRegularCheckbox(string $name, mixed $checkKey, string $checkKeyEscaped, mixed $checkLabel, bool $isSelected, array $checkAttributes, string $checkboxType, string $checkboxId): string {
			// Security: Escape name and check_key for form field name
			$checkboxInput = Form::checkbox(canvastack_form_escape_html($name) . "[{$checkKeyEscaped}]", $checkKeyEscaped, $isSelected, $checkAttributes);
			// Security: Laravel Form::label() automatically escapes the label text
			// No need to manually escape as it will cause double-escaping
			$labelTag = Form::label($checkboxId, $checkLabel);

			return ThemeAdapterResolver::resolve()->renderCheckboxWrapper($checkboxType, $checkboxInput, $labelTag);
		}
}

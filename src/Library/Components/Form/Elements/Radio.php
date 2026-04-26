<?php
namespace Canvastack\Canvastack\Library\Components\Form\Elements;

use Collective\Html\FormFacade as Form;
use Canvastack\Canvastack\Library\Constants\FormConstants;
/**
 * Created on 22 Mar 2021
 * Time Created	: 11:01:38
 *
 * @filesource	Radio.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
 
trait Radio {
	
	public function radiobox(string $name, array $values = [], bool|string $selected = false, array $attributes = [], bool|string|null $label = true): void {
		$this->setParams('radio', $name, $values, $attributes, $label, $selected);
		$this->inputDraw('radio', $name);		
	}
	
	/**
	 * Draw Radio Box
	 * 
	 * Renders radio button elements with proper escaping and accessibility attributes.
	 * 
	 * @param string $name Radio field name
	 * @param mixed|array|string $value Radio value(s) - array for multiple radio buttons
	 * @param string $selected Selected value
	 * @param array $attributes HTML attributes for radio elements
	 * 
	 * @return string Safe HTML radio element(s)
	 * 
	 * @security All user-controllable parameters are escaped to prevent XSS
	 * @security Attributes array is validated to block dangerous event handlers
	 * @security Output is marked as safe HTML to prevent double-encoding
	 * 
	 * @throws \InvalidArgumentException If attributes contain dangerous event handlers
	 */
	private function drawRadioBox(string $name, array $value, bool|string $selected, array $attributes = []): string {
		// Security: Validate attributes array for dangerous handlers
		$attributes = canvastack_form_validate_attributes($attributes);

		$radiobox = '';

		foreach ($value as $radioKey => $radioLabel) {
			$radiobox .= $this->renderSingleRadio($name, $radioKey, $radioLabel, $selected, $attributes);
		}

		// Security: Mark output as safe HTML to prevent double-encoding
		return \Canvastack\Canvastack\Library\Constants\SafeHtml::mark($radiobox);
	}

	/**
	 * Render a single radio button element
	 * 
	 * @param string $name Radio field name
	 * @param mixed $radioKey Radio value
	 * @param mixed $radioLabel Radio label
	 * @param bool|string $selected Selected value
	 * @param array $attributes HTML attributes
	 * 
	 * @return string HTML for single radio button
	 */
	private function renderSingleRadio(string $name, mixed $radioKey, mixed $radioLabel, bool|string $selected, array $attributes): string {
		// Security: Escape radio_key for use in ID generation
		$radioKeyEscaped = canvastack_form_escape_html($radioKey);

		$radioId = "canvastack{$radioKeyEscaped}:rdo" . canvastack_random_strings(8, false);
		$radioAttributes = array_merge_recursive([FormConstants::ATTR_ID => $radioId], $attributes);

		$isSelected = $this->isRadioSelected($radioKey, $radioLabel, $selected);
		$radioType = $this->getRadioType($radioAttributes);

		// Add ARIA attributes
		$radioAttributes = $this->addRadioAriaAttributes($radioAttributes, $isSelected, $radioLabel, $radioKeyEscaped);

		// Remove radio_type from attributes as it's not a valid HTML attribute
		unset($radioAttributes['radio_type']);

		return $this->buildRadioHtml($name, $radioKey, $radioKeyEscaped, $radioLabel, $isSelected, $radioAttributes, $radioType, $radioId);
	}

	/**
	 * Check if radio button should be selected
	 * 
	 * @param mixed $radioKey Radio key
	 * @param mixed $radioLabel Radio label
	 * @param bool|string $selected Selected value
	 * 
	 * @return bool True if selected
	 */
	private function isRadioSelected(mixed $radioKey, mixed $radioLabel, bool|string $selected): bool {
		if ($selected === false) {
			return false;
		}

		// Use loose comparison to handle type differences (int vs string)
		return $radioKey == $selected || $radioLabel == $selected;
	}

	/**
	 * Get radio button type CSS class
	 * 
	 * @param array $attributes Radio attributes
	 * 
	 * @return string CSS class for radio type
	 */
	private function getRadioType(array $attributes): string {
		if (!isset($attributes['radio_type'])) {
			return ' col-sm-3 rdio-primary';
		}

		// Security: Escape radio type for CSS class
		return " rdio-" . canvastack_form_escape_html($attributes['radio_type']);
	}

	/**
	 * Add ARIA attributes to radio button
	 * 
	 * Adds comprehensive ARIA attributes for accessibility compliance:
	 * - aria-checked: Indicates selection state (true/false)
	 * - aria-label: Provides accessible name when no visible label exists (includes "required" text if applicable)
	 * - aria-disabled: Indicates disabled state when disabled attribute present
	 * - aria-required: Indicates required field when required attribute present
	 * 
	 * @param array $attributes Current attributes
	 * @param bool $isSelected Whether radio is selected
	 * @param mixed $radioLabel Radio label
	 * @param string $radioKeyEscaped Escaped radio key
	 * 
	 * @return array Attributes with ARIA added
	 * 
	 * @accessibility aria-label includes "required" text for required radio buttons without visible labels
	 */
	private function addRadioAriaAttributes(array $attributes, bool $isSelected, mixed $radioLabel, string $radioKeyEscaped): array {
		$attributes[FormConstants::ARIA_CHECKED] = $isSelected ? 'true' : 'false';

		// Add aria-label if no visible label
		if ($radioLabel === false || $radioLabel === '') {
			$labelText = 'Radio button ' . $radioKeyEscaped;
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
	 * Build radio button HTML
	 * 
	 * @param string $name Field name
	 * @param mixed $radioKey Radio key
	 * @param string $radioKeyEscaped Escaped radio key
	 * @param mixed $radioLabel Radio label
	 * @param bool $isSelected Whether selected
	 * @param array $radioAttributes Radio attributes
	 * @param string $radioType CSS class for radio type
	 * @param string $radioId Radio ID
	 * 
	 * @return string HTML for radio button
	 */
	private function buildRadioHtml(string $name, mixed $radioKey, string $radioKeyEscaped, mixed $radioLabel, bool $isSelected, array $radioAttributes, string $radioType, string $radioId): string {
		$openTag = '<div class="rdio' . $radioType . ' circle">';
		$endTag = '</div>';

		// Security: Escape name and radio_key for form field name, escape radio_label for display
		$radioInput = Form::radio(canvastack_form_escape_html($name), $radioKeyEscaped, $isSelected, $radioAttributes);
		$labelTag = Form::label($radioId, canvastack_form_escape_html($radioLabel));

		return $openTag . $radioInput . $labelTag . $endTag;
	}
}
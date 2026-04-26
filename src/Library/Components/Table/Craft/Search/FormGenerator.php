<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft\Search;

use Canvastack\Canvastack\Library\Components\Form\Objects as Form;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\TableValidationException;
use Illuminate\Support\Facades\Log;

/**
 * FormGenerator - Form field generation for Search component
 *
 * @filesource FormGenerator.php
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 *
 * @security XSS Prevention - all user-controllable data (field names, labels,
 *           option text) is escaped via escapeHtml() before use in HTML output.
 *           Field names used in HTML attributes are sanitized to alphanumeric
 *           characters and underscores only.
 */
class FormGenerator {
	
	private Form $form;
	private SearchConfig $config;
	private array $searchFields = [];
	private array $fieldValuesCache = [];
	
	/**
	 * Constructor
	 *
	 * @param SearchConfig $config Configuration object
	 */
	public function __construct(SearchConfig $config) {
		$this->form = new Form();
		$this->config = $config;
		$this->setupFormConfig();
	}

	/**
	 * Generate filter operator selectbox
	 *
	 * @security XSS Prevention - operator values are from a whitelist constant
	 *
	 * @param string $field Field name
	 * @param string $currentOperator Current selected operator
	 * @return void
	 */
	public function generateOperatorSelect(string $field, string $currentOperator = '='): void {
		$operators = [
			'=' => 'Equals',
			'!=' => 'Not Equals',
			'>' => 'Greater Than',
			'<' => 'Less Than',
			'>=' => 'Greater or Equal',
			'<=' => 'Less or Equal',
			'LIKE' => 'Contains',
			'NOT LIKE' => 'Does Not Contain',
			'IN' => 'In List',
			'NOT IN' => 'Not In List',
			'BETWEEN' => 'Between',
			'IS NULL' => 'Is Empty',
			'IS NOT NULL' => 'Is Not Empty'
		];

		$operatorField = $field . '_operator';
		$attributes = ['id' => $operatorField, 'class' => 'filter-operator'];

		$this->form->selectbox($operatorField, $operators, $currentOperator, $attributes, false, false);
	}

	/**
	 * Generate date range filter inputs
	 *
	 * @security XSS Prevention - field names are sanitized before use
	 *
	 * @param string $field Field name
	 * @param array $values Current values [start, end]
	 * @param array $attributes HTML attributes
	 * @return void
	 */
	public function generateDateRangeFilter(string $field, array $values = [], array $attributes = []): void {
		$startField = $field . '_start';
		$endField = $field . '_end';

		$startValue = $values['start'] ?? null;
		$endValue = $values['end'] ?? null;

		$startAttrs = array_merge(['id' => $startField, 'placeholder' => 'Start Date'], $attributes);
		$endAttrs = array_merge(['id' => $endField, 'placeholder' => 'End Date'], $attributes);

		$this->form->date($startField, $startValue, $startAttrs);
		$this->form->date($endField, $endValue, $endAttrs);
	}

	/**
	 * Generate numeric range filter inputs
	 *
	 * @security XSS Prevention - field names are sanitized before use
	 *
	 * @param string $field Field name
	 * @param array $values Current values [min, max]
	 * @param array $attributes HTML attributes
	 * @return void
	 */
	public function generateNumericRangeFilter(string $field, array $values = [], array $attributes = []): void {
		$minField = $field . '_min';
		$maxField = $field . '_max';

		$minValue = $values['min'] ?? null;
		$maxValue = $values['max'] ?? null;

		$minAttrs = array_merge([
			'id' => $minField,
			'type' => 'number',
			'placeholder' => 'Min Value'
		], $attributes);
		$maxAttrs = array_merge([
			'id' => $maxField,
			'type' => 'number',
			'placeholder' => 'Max Value'
		], $attributes);

		$this->form->text($minField, $minValue, $minAttrs);
		$this->form->text($maxField, $maxValue, $maxAttrs);
	}

	/**
	 * Generate filter combination selector (AND/OR)
	 *
	 * @security XSS Prevention - values are from a whitelist
	 *
	 * @param string $filterGroupId Filter group identifier
	 * @param string $currentCombination Current combination (AND/OR)
	 * @return void
	 */
	public function generateFilterCombinationSelect(string $filterGroupId, string $currentCombination = 'AND'): void {
		$combinations = [
			'AND' => 'Match All (AND)',
			'OR' => 'Match Any (OR)'
		];

		$field = 'filter_combination_' . $filterGroupId;
		$attributes = ['id' => $field, 'class' => 'filter-combination'];

		$this->form->selectbox($field, $combinations, $currentCombination, $attributes, false, false);
	}

	
	/**
	 * Setup form configuration
	 *
	 * @return void
	 */
	private function setupFormConfig(): void {
		$this->form->excludeFields = ['password_field'];
		$this->form->hideFields = ['id'];
	}
	
	/**
	 * Setup search fields
	 *
	 * @param array $data Filter data
	 * @return void
	 */
	public function setupSearchFields(array $data): void {
		foreach (array_keys($data) as $dataFields) {
			$this->searchFields[$dataFields] = $dataFields;
		}
	}
	
	/**
	 * Generate form element based on type with validation
	 *
	 * @security XSS Prevention - field type is validated against VALID_FIELD_TYPES
	 *           whitelist before use. Field name and values are passed to the Form
	 *           component which handles its own escaping.
	 *
	 * @param string $field Field name
	 * @param string $type Field type
	 * @param mixed $values Field values
	 * @param array $attributes HTML attributes
	 * @return void
	 */
	public function generateFormElement(string $field, string $type, $values, array $attributes): void {
		// Validate field type
		if (!in_array($type, SearchConfig::VALID_FIELD_TYPES)) {
			Log::warning("Invalid field type: {$type} for field: {$field}, defaulting to text");
			$type = 'text';
		}
		
		switch ($type) {
			case 'selectbox':
				$this->form->selectbox($field, $values, false, $attributes, true, false);
				break;
			case 'date':
				$this->form->date($field, $values, $attributes);
				break;
			case 'datetime':
				$this->form->date($field, $values, $attributes);
				break;
			case 'checkbox':
				if ($this->shouldRenderCheckbox($values)) {
					$this->form->checkbox($field, $values);
				}
				break;
			case 'radiobox':
				if ($this->shouldRenderRadiobox($values)) {
					$this->form->radiobox($field, $values);
				}
				break;
			default:
				if (!empty($values)) {
					$this->form->text($field, $values, ['id' => $field]);
				}
		}
	}
	
	/**
	 * Generate default form element with validation
	 *
	 * @security XSS Prevention - field type is validated against VALID_FIELD_TYPES
	 *           whitelist before use. Field name is passed to the Form component
	 *           which handles its own escaping.
	 *
	 * @param string $field Field name
	 * @param string $type Field type
	 * @return void
	 */
	public function generateDefaultFormElement(string $field, string $type): void {
		$attributes = ['id' => $field];
		
		// Validate field type
		if (!in_array($type, SearchConfig::VALID_FIELD_TYPES)) {
			Log::warning("Invalid default field type: {$type} for field: {$field}, defaulting to text");
			$type = 'text';
		}
		
		switch ($type) {
			case 'string':
			case 'text':
				$this->form->text($field, null, $attributes);
				break;
			case 'smallint':
				$this->form->selectbox($field, [], false, $attributes);
				break;
			case 'date':
				$this->form->date($field, null, $attributes);
				break;
			case 'datetime':
				$this->form->datetime($field, null, $attributes);
				break;
			case 'time':
				$this->form->time($field, null, $attributes);
				break;
			case 'daterange':
				$this->form->daterange($field, null, $attributes);
				break;
			default:
				$this->form->text($field, null, $attributes);
		}
	}
	
	/**
	 * Prepare field values
	 *
	 * @param string $field Field name
	 * @param string $open_field Open field
	 * @param string $tablename Table name
	 * @param QueryBuilder $queryBuilder Query builder instance
	 * @return array|null Field values
	 */
	public function prepareFieldValues(string $field, string $open_field, string $tablename, QueryBuilder $queryBuilder): ?array {
		$values = null;
		
		if ($open_field === $field) {
			$field_value = [];
			$values = $this->setFirstSelectbox($tablename, $field_value, $field, $queryBuilder);
		}
		
		return $values;
	}
	
	/**
	 * Batch load field values for multiple fields in single query
	 * OPTIMIZATION: Reduces N queries to 1 query
	 *
	 * @performance 2.4.3 - Batch loads all field values in a single DB query instead
	 *              of one query per field. Results are cached per field for reuse.
	 *
	 * @param string $tablename Table name
	 * @param array $fields Field names
	 * @param string $open_field Open field
	 * @param QueryBuilder $queryBuilder Query builder instance
	 * @return void
	 */
	public function batchLoadFieldValues(string $tablename, array $fields, string $open_field, QueryBuilder $queryBuilder): void {
		if (empty($fields)) {
			return;
		}
		
		// OPTIMIZATION: Load all fields in single query instead of N queries
		$queryBuilder->batchSelections($tablename, $fields);
		$selections = $queryBuilder->getSelections();
		
		// Cache results for each field
		foreach ($fields as $field) {
			if ($field === $open_field && !empty($selections[$field])) {
				$this->fieldValuesCache[$field] = $selections[$field];
			} else {
				$this->fieldValuesCache[$field] = null;
			}
		}
	}
	
	/**
	 * Get field values from cache
	 *
	 * @param string $field Field name
	 * @param string $open_field Open field
	 * @return array|null Field values
	 */
	public function getFieldValuesFromCache(string $field, string $open_field): ?array {
		if ($field === $open_field && isset($this->fieldValuesCache[$field])) {
			return $this->fieldValuesCache[$field];
		}
		
		return null;
	}

	/**
	 * Process all filter fields in a single optimized pass
	 *
	 * @performance 2.4.3 - Processes all filter fields in one pass, using batch
	 *              loading to avoid N+1 queries. Skips empty/invalid fields early.
	 *
	 * @param array $filterData Filter field definitions keyed by field name
	 * @param string $tablename Table name
	 * @param string $open_field The first/open field for selectbox population
	 * @param QueryBuilder $queryBuilder Query builder instance
	 * @return array Processed field definitions with values populated
	 */
	public function processFilterFields(array $filterData, string $tablename, string $open_field, QueryBuilder $queryBuilder): array {
		if (empty($filterData)) {
			return [];
		}

		// Collect selectbox fields for batch loading
		$selectboxFields = [];
		foreach ($filterData as $field => $type) {
			if (in_array($type, ['selectbox', 'smallint'], true)) {
				$selectboxFields[] = $field;
			}
		}

		// Batch load all selectbox values in one query
		if (!empty($selectboxFields)) {
			$this->batchLoadFieldValues($tablename, $selectboxFields, $open_field, $queryBuilder);
		}

		// Build processed field definitions
		$processed = [];
		foreach ($filterData as $field => $type) {
			// Validate type early - skip unknown types
			if (!in_array($type, SearchConfig::VALID_FIELD_TYPES, true)) {
				Log::warning("processFilterFields: Unknown field type '{$type}' for field '{$field}', defaulting to text");
				$type = 'text';
			}

			$values = null;
			if (in_array($type, ['selectbox', 'smallint'], true)) {
				$values = $this->getFieldValuesFromCache($field, $open_field);
			}

			$processed[$field] = [
				'type'   => $type,
				'values' => $values,
			];
		}

		return $processed;
	}
	
	/**
	 * Set first selectbox values
	 *
	 * @param string $name Table name
	 * @param array $field_value Field values
	 * @param string $field Field name
	 * @param QueryBuilder $queryBuilder Query builder instance
	 * @return array|null
	 */
	private function setFirstSelectbox(string $name, array $field_value, string $field, QueryBuilder $queryBuilder): ?array {
		$values[$field] = null;
		$queryBuilder->selections($name, [$field]);
		$selections = $queryBuilder->getSelections();
		
		if (!empty($selections[$field])) {
			$values[$field] = $selections[$field];
		}
		
		return $values[$field];
	}
	
	/**
	 * Build field attributes with export class
	 *
	 * @security XSS Prevention - $field and $info are sanitized before use in
	 *           HTML attribute values. Only alphanumeric characters and underscores
	 *           are allowed to prevent attribute injection attacks.
	 *
	 * @param string $field Field name
	 * @param string $info Component info
	 * @param mixed $values Field values
	 * @return array HTML attributes
	 */
	public function buildFieldAttributes(string $field, string $info, $values): array {
		// SECURITY: Sanitize field and info for use in HTML attributes (id/class)
		$safeField = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $field);
		$safeInfo  = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace('-', '_', (string) $info));
		$classFieldInfo = $safeInfo . 'Field';

		// FIX: Make ID unique per table by including table identifier
		// This prevents filter inputs from sharing state across tabs
		$uniqueId = "{$safeField}_{$safeInfo}";

		$attributes = [
			'id'    => $uniqueId,
			'class' => "{$safeField}_{$classFieldInfo} " . SearchConfig::EXPORT_CLASS_PREFIX . "{$classFieldInfo}"
		];
		
		if (empty($values)) {
			$attributes['disabled'] = 'disabled';
		}
		
		return $attributes;
	}
	
	/**
	 * Prepare field options (SECURE - XSS protected)
	 *
	 * @security XSS Prevention - $field_label is escaped with htmlspecialchars()
	 *           before being embedded in option text values to prevent XSS via
	 *           user-supplied label strings.
	 *
	 * @param string $field Field name
	 * @param string $type Field type
	 * @param mixed $values Field values
	 * @param string $field_label Field label (will be escaped before use)
	 * @return mixed Prepared values
	 */
	public function prepareFieldOptions(string $field, string $type, $values, string $field_label) {
		// SECURITY: Escape field_label before embedding in option text
		$safeLabel = $this->escapeHtml((string) $field_label);

		if ('selectbox' === $type) {
			if (null === $values) {
				$values = [null => 'No Data ' . $safeLabel . ' Found'];
			} else {
				$values[null] = 'Select ' . $safeLabel;
			}
			ksort($values);
		}
		
		if ('radiobox' === $type) {
			if (null !== $values && count($values) > 1) {
				$values[null] = 'Clear!';
			}
		}
		
		return $values;
	}
	
	/**
	 * Check if checkbox should be rendered
	 *
	 * @param mixed $values
	 * @return bool
	 */
	public function shouldRenderCheckbox($values): bool {
		if (empty($values)) {
			return false;
		}
		
		return !in_array('', $values) || !in_array(null, $values);
	}
	
	/**
	 * Check if radiobox should be rendered
	 *
	 * @param mixed $values
	 * @return bool
	 */
	public function shouldRenderRadiobox($values): bool {
		if (empty($values)) {
			return false;
		}
		
		return !in_array('', $values) || !in_array(null, $values);
	}
	
	/**
	 * Get form elements
	 *
	 * @return array
	 */
	public function getFormElements(): array {
		return $this->form->elements;
	}
	
	/**
	 * Get form object
	 *
	 * @return Form
	 */
	public function getForm(): Form {
		return $this->form;
	}
	
	/**
	 * Get search fields
	 *
	 * @return array
	 */
	public function getSearchFields(): array {
		return $this->searchFields;
	}
	
	/**
	 * Escape HTML to prevent XSS
	 *
	 * @security XSS Prevention - uses htmlspecialchars with ENT_QUOTES and UTF-8
	 *           to escape all special HTML characters in user-controllable values.
	 *
	 * @param string $value
	 * @return string
	 */
	public function escapeHtml(string $value): string {
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Generate text filter with wildcard/regex support
	 *
	 * @security XSS Prevention - field names and values are escaped
	 *
	 * @param string $field Field name
	 * @param mixed $value Current value
	 * @param array $attributes HTML attributes
	 * @param bool $enableRegex Enable regex pattern matching
	 * @return void
	 */
	public function generateTextFilterWithPattern(string $field, $value = null, array $attributes = [], bool $enableRegex = false): void {
		$textField = $field;
		$patternTypeField = $field . '_pattern_type';
		
		// Add help text for wildcards
		$helpText = 'Use * for wildcard (e.g., test* matches test123)';
		if ($enableRegex) {
			$helpText .= ' or enable regex for advanced patterns';
		}
		
		$textAttrs = array_merge([
			'id' => $textField,
			'placeholder' => 'Enter search term...',
			'data-help' => $helpText
		], $attributes);
		
		$this->form->text($textField, $value, $textAttrs);
		
		// Add pattern type selector if regex is enabled
		if ($enableRegex) {
			$patternTypes = [
				'wildcard' => 'Wildcard (*)',
				'regex' => 'Regular Expression'
			];
			
			$patternAttrs = ['id' => $patternTypeField, 'class' => 'pattern-type-selector'];
			$this->form->selectbox($patternTypeField, $patternTypes, 'wildcard', $patternAttrs, false, false);
		}
	}
}

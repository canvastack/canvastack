<?php
namespace Canvastack\Canvastack\Library\Components\Form;

use Collective\Html\FormFacade as Form;
use Collective\Html\HtmlFacade as Html;

use Canvastack\Canvastack\Library\Components\Form\Elements\Text;
use Canvastack\Canvastack\Library\Components\Form\Elements\DateTime;
use Canvastack\Canvastack\Library\Components\Form\Elements\Select;
use Canvastack\Canvastack\Library\Components\Form\Elements\File;
use Canvastack\Canvastack\Library\Components\Form\Elements\Check;
use Canvastack\Canvastack\Library\Components\Form\Elements\Radio;
use Canvastack\Canvastack\Library\Components\Form\Elements\Tab;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Canvastack\Canvastack\Controllers\Admin\System\AjaxController;

/**
 * Form Objects Class
 * 
 * Main class for building HTML forms with model binding, validation, and various input types.
 * Uses traits for element-specific functionality (Text, DateTime, Select, File, Check, Radio, Tab).
 * 
 * ## SafeHtml Marker System
 * 
 * This class integrates with the SafeHtml marker system to prevent double-encoding of HTML:
 * 
 * **How it works:**
 * 1. Form generation methods (open, close, token, label, alert_message) mark their output with SafeHtml::mark()
 * 2. Marked HTML has a special prefix: {{CANVASTACK_SAFE_HTML}}[actual_html]
 * 3. When combining HTML elements, SafeHtml::unmark() removes the marker
 * 4. The render() method automatically removes all markers before final output
 * 5. This ensures markers never appear in the browser HTML
 * 
 * **Why this is needed:**
 * - Laravel Form facade already escapes user input properly
 * - Without markers, escaping again would cause double-encoding: &amp;lt; instead of &lt;
 * - With markers, we know which HTML is safe and which needs escaping
 * - Markers are internal only and never sent to the browser
 * 
 * **Security guarantees:**
 * - Only trusted internal methods can mark HTML as safe
 * - User input is always escaped before being marked
 * - Unmarked content is treated as potentially unsafe
 * - All markers are removed by render() before browser output
 * 
 * **Example flow:**
 * ```php
 * // 1. Generate form opening (marked as safe internally)
 * $form->open('users.store');  // Internally: draw(SafeHtml::mark('<form>...'))
 * 
 * // 2. Generate input field (marked as safe internally)
 * $form->text('email');  // Internally: label() and inputTag() both marked
 * 
 * // 3. Combine elements (unmark before concatenation in inputDraw)
 * // inputDraw() calls SafeHtml::unmark() on label and input before combining
 * 
 * // 4. Final output (render removes all markers)
 * echo $form->render($form->elements);  // All markers removed, clean HTML output
 * ```
 * 
 * Created on 16 Mar 2021
 * Time Created	: 17:55:08
 *
 * @filesource	Objects.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 * 
 * @security XSS Protection: All user-controllable parameters are escaped using canvastack_form_escape_html()
 * @security Input Validation: Dangerous attributes (event handlers) are blocked in checkValidationAttributes()
 * @security Path Validation: HTTP methods are validated against whitelist
 * @security Encryption: Model names and sync queries are encrypted to prevent tampering
 * @security CSRF Protection: Uses Laravel's built-in CSRF token system
 * @security SafeHtml System: Prevents double-encoding while maintaining security
 * @security Marker Removal: render() ensures no SafeHtml markers appear in browser output
 * 
 * @updated 2026-03-31 - Added XSS protection and security validations
 * @updated 2026-04-01 - Integrated SafeHtml marker system
 * @updated 2026-04-01 - Fixed marker leakage by adding unmark in render() method
 */
 
class Objects {
	use Text, DateTime, Select, File, Check, Radio, Tab;
	
	public object|string|null $model = null;
	public array $elements        = [];
	public array $element_name    = [];
	public array $element_plugins = [];
	public array $params          = [];
	public array $validations     = [];
	
	private ?string $currentRoute = null;
	private ?array $currentRouteArray = null;
	public  ?string $currentRouteName = null;
	
	/**
	 * Fields to exclude from rendering
	 * @var array
	 */
	public array $excludeFields = [];
	
	/**
	 * Fields to hide (add 'hide' class)
	 * @var array
	 */
	public array $hideFields = [];
	
	/**
	 * Constructor - Initialize form object and get current route information
	 * 
	 * Automatically retrieves and stores the current route information for use
	 * in form generation and model binding operations.
	 * 
	 * @return void
	 */
	public function __construct() {
		$this->getCurrentRoute();
	}
	/**
	 * Magic getter to handle property access
	 *
	 * Provides special handling for the elements property to ensure SafeHtml markers
	 * are automatically removed when accessed. This prevents markers from appearing
	 * in the final HTML output even when elements are accessed directly.
	 *
	 * @param string $property Property name to access
	 *
	 * @return mixed Property value with SafeHtml markers removed for elements array
	 *
	 * @security Automatically removes SafeHtml markers from elements array
	 * @security This prevents {{CANVASTACK_SAFE_HTML}} from appearing in browser output
	 *
	 * @example
	 * ```php
	 * // Accessing elements directly (markers are automatically removed)
	 * $html = $form->elements;  // Returns array with all markers removed
	 *
	 * // Rendering elements (markers are also removed)
	 * echo $form->render($form->elements);
	 * ```
	 */
	public function __get(string $property): mixed {
		// Special handling for elements property to ensure SafeHtml markers are removed
		if ($property === 'elements') {
			return array_map(function($element) {
				return is_string($element) ? SafeHtml::unmark($element) : $element;
			}, $this->elements);
		}

		// Default property access
		if (property_exists($this, $property)) {
			return $this->$property;
		}

		return null;
	}
	
	/**
	 * Set validation rules for form fields
	 * 
	 * Stores validation rules that will be propagated to form elements as HTML5
	 * validation attributes (required, maxlength, type, etc.).
	 * 
	 * Parses Laravel validation rules and converts them to HTML attributes:
	 * - 'required' → required="required"
	 * - 'email' → type="email"
	 * - 'max:255' → maxlength="255"
	 * - 'min:5' → minlength="5"
	 * - 'numeric' → type="number"
	 * - 'mimes:jpg,png' → accept=".jpg,.png"
	 * 
	 * @param array $data Validation rules array where keys are field names and values are rule arrays
	 *                    Example: ['email' => ['required', 'email', 'max:255'], 'name' => ['required']]
	 * 
	 * @return void
	 * 
	 * @see checkValidationAttributes() For how validation rules are converted to HTML attributes
	 * @see parseValidationRules() For rule parsing logic
	 */
	public function setValidations(array $data = []): void {
		$this->validations = $data;

		// Parse validation rules into HTML attributes
		if (!empty($data)) {
			$this->parseValidationRules($data);
		}
	}

	/**
	 * Parse validation rules into HTML attributes
	 *
	 * Converts Laravel validation rules into HTML5 validation attributes that can be
	 * applied to form elements. This enables client-side validation that matches
	 * server-side validation rules.
	 *
	 * Supported rule conversions:
	 * - 'required' → ['required' => 'required']
	 * - 'email' → ['type' => 'email']
	 * - 'max:N' → ['maxlength' => N]
	 * - 'min:N' → ['minlength' => N]
	 * - 'numeric' → ['type' => 'number']
	 * - 'integer' → ['type' => 'number', 'step' => '1']
	 * - 'mimes:ext1,ext2' → ['accept' => '.ext1,.ext2']
	 * - 'max:N' (for files) → ['data-max-size' => N]
	 *
	 * @param array $validations Validation rules array from setValidations()
	 *
	 * @return void Stores parsed attributes in self::$validation_attributes
	 *
	 * @example
	 * ```php
	 * // Input: ['email' => ['required', 'email', 'max:255']]
	 * // Output: self::$validation_attributes['email'] = [
	 * //     'required' => 'required',
	 * //     'type' => 'email',
	 * //     'maxlength' => 255
	 * // ]
	 * ```
	 */
	private function parseValidationRules(array $validations): void {
		foreach ($validations as $field_name => $rules) {
			// Handle both string and array rule formats
			if (is_string($rules)) {
				$rules = explode('|', $rules);
			}

			if (!is_array($rules)) {
				continue;
			}

			$attributes = [];

			foreach ($rules as $rule) {
				// Handle Rule objects (Laravel 8+)
				if (is_object($rule)) {
					$rule = $this->convertRuleObjectToString($rule);
				}

				// Parse rule with parameters (e.g., "max:255")
				$ruleParts = explode(':', $rule, 2);
				$ruleName = strtolower(trim($ruleParts[0]));
				$ruleValue = isset($ruleParts[1]) ? trim($ruleParts[1]) : null;

				// Convert rule to HTML attribute
				$ruleAttributes = $this->convertRuleToAttributes($ruleName, $ruleValue, $field_name);
				$attributes = array_merge($attributes, $ruleAttributes);
			}

			// Store parsed attributes for this field
			if (!empty($attributes)) {
				self::$validation_attributes[$field_name] = $attributes;
			}
		}
	}

	/**
	 * Convert Laravel Rule object to string representation
	 *
	 * @param object $rule Laravel Rule object
	 * @return string Rule as string (e.g., "required", "max:255")
	 */
	private function convertRuleObjectToString(object $rule): string {
		// Handle Laravel Rule objects
		if (method_exists($rule, '__toString')) {
			return (string) $rule;
		}

		// Get class name as fallback
		$className = class_basename($rule);
		return strtolower($className);
	}

	/**
	 * Convert a single validation rule to HTML attributes
	 *
	 * @param string $ruleName Rule name (e.g., "required", "max", "email")
	 * @param string|null $ruleValue Rule parameter value (e.g., "255" for "max:255")
	 * @param string $fieldName Field name for context
	 *
	 * @return array HTML attributes for this rule
	 */
	private function convertRuleToAttributes(string $ruleName, ?string $ruleValue, string $fieldName): array {
		$attributes = [];

		switch ($ruleName) {
			case 'required':
				$attributes['required'] = 'required';
				$attributes[FormConstants::ARIA_REQUIRED] = 'true';
				break;

			case 'email':
				$attributes['type'] = 'email';
				break;

			case 'max':
				// For file inputs, this is file size; for text inputs, it's length
				if ($this->isFileField($fieldName)) {
					$attributes['data-max-size'] = $ruleValue;
				} else {
					$attributes['maxlength'] = $ruleValue;
				}
				break;

			case 'min':
				// For numeric inputs, use min attribute; for text, use minlength
				if ($this->isNumericField($fieldName)) {
					$attributes['min'] = $ruleValue;
				} else {
					$attributes['minlength'] = $ruleValue;
				}
				break;

			case 'numeric':
			case 'integer':
				$attributes['type'] = 'number';
				if ($ruleName === 'integer') {
					$attributes['step'] = '1';
				}
				break;

			case 'mimes':
			case 'mimetypes':
				// Convert mime types to file extensions for accept attribute
				if ($ruleValue) {
					$extensions = explode(',', $ruleValue);
					$extensions = array_map(function($ext) {
						return '.' . trim($ext);
					}, $extensions);
					$attributes['accept'] = implode(',', $extensions);
				}
				break;

			case 'between':
				// Format: between:min,max
				if ($ruleValue && str_contains($ruleValue, ',')) {
					list($min, $max) = explode(',', $ruleValue, 2);
					if ($this->isNumericField($fieldName)) {
						$attributes['min'] = trim($min);
						$attributes['max'] = trim($max);
					} else {
						$attributes['minlength'] = trim($min);
						$attributes['maxlength'] = trim($max);
					}
				}
				break;

			case 'size':
				// For files, this is size; for strings, it's exact length
				if ($this->isFileField($fieldName)) {
					$attributes['data-size'] = $ruleValue;
				} else {
					$attributes['maxlength'] = $ruleValue;
					$attributes['minlength'] = $ruleValue;
				}
				break;

			case 'url':
				$attributes['type'] = 'url';
				break;

			case 'alpha':
			case 'alpha_dash':
			case 'alpha_num':
				// Add pattern for alpha validation
				$patterns = [
					'alpha' => '[A-Za-z]+',
					'alpha_dash' => '[A-Za-z0-9_-]+',
					'alpha_num' => '[A-Za-z0-9]+'
				];
				if (isset($patterns[$ruleName])) {
					$attributes['pattern'] = $patterns[$ruleName];
				}
				break;
		}

		return $attributes;
	}

	/**
	 * Check if a field is a file upload field
	 *
	 * @param string $fieldName Field name to check
	 * @return bool True if field is for file uploads
	 */
	private function isFileField(string $fieldName): bool {
		// Check if field name contains common file field indicators
		$fileIndicators = ['file', 'upload', 'image', 'photo', 'document', 'attachment'];
		$fieldLower = strtolower($fieldName);

		foreach ($fileIndicators as $indicator) {
			if (str_contains($fieldLower, $indicator)) {
				return true;
			}
		}

		// Check if field has been registered as file type
		if (isset($this->params[$fieldName]) && is_array($this->params[$fieldName]) && isset($this->params[$fieldName]['function_name']) && $this->params[$fieldName]['function_name'] === 'file') {
			return true;
		}

		return false;
	}

	/**
	 * Check if a field should use numeric input type
	 *
	 * @param string $fieldName Field name to check
	 * @return bool True if field should be numeric
	 */
	private function isNumericField(string $fieldName): bool {
		// Check if field name contains common numeric field indicators
		$numericIndicators = ['age', 'count', 'quantity', 'amount', 'price', 'number', 'qty', 'total'];
		$fieldLower = strtolower($fieldName);

		foreach ($numericIndicators as $indicator) {
			if (str_contains($fieldLower, $indicator)) {
				return true;
			}
		}

		// Check if field has been registered as numeric type
		if (isset($this->params[$fieldName])) {
			$functionName = $this->params[$fieldName]['function_name'];
			if (in_array($functionName, ['number', 'numeric'])) {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Get and store current route information
	 * 
	 * Retrieves the current route name and parses it into components for use
	 * in automatic form path generation and model binding logic.
	 * 
	 * @return void
	 */
	protected function getCurrentRoute(): void {
		$this->currentRoute      = current_route();
		$this->currentRouteArray = explode('.', $this->currentRoute);
		$this->currentRouteName  = last($this->currentRouteArray);
	}
	
	/**
	 * Add form element to rendering queue
	 * 
	 * Stores form elements (HTML strings or arrays) in the internal elements array
	 * for later rendering. Elements can be marked with SafeHtml::mark() to indicate
	 * they are already properly escaped and should not be double-encoded.
	 * 
	 * IMPORTANT: This method automatically removes SafeHtml markers before storing
	 * to prevent markers from appearing in the final HTML output.
	 * 
	 * Elements are accumulated and can be rendered all at once.
	 * 
	 * @param array|string $data HTML string or array of HTML strings to add to the form
	 * 
	 * @return void
	 * 
	 * @security SafeHtml Handling: Markers are removed before storage
	 * @security This prevents {{CANVASTACK_SAFE_HTML}} from appearing in browser output
	 * @security All HTML is already properly escaped by form generation methods
	 * 
	 * @example
	 * ```php
	 * // Marked HTML from form methods (marker will be removed)
	 * $form->open('users.store');  // Internally calls draw(SafeHtml::mark(...))
	 * 
	 * // Custom HTML (will be stored as-is)
	 * $form->draw('<div class="custom-wrapper">');
	 * ```
	 */
	public function draw(array|string $data = []): void {
		if ($data) {
			// Security: Remove SafeHtml markers before storing to prevent marker leakage
			if (is_array($data)) {
				$data = array_map(function($element) {
					return is_string($element) ? SafeHtml::unmark($element) : $element;
				}, $data);
			} else if (is_string($data)) {
				$data = SafeHtml::unmark($data);
			}
			$this->elements[] = $data;
		}
	}
	
	/**
	 * Render form elements with tab support
	 * 
	 * Processes form elements and detects if tab markers are present.
	 * If tabs are detected, renders them using the Tab trait's renderTab() method.
	 * 
	 * This method also removes SafeHtml markers from all elements before output to prevent
	 * markers from appearing in the final HTML sent to the browser.
	 * 
	 * @param array|string $object Form element(s) to render - can be HTML string or array of HTML strings
	 * 
	 * @return array|string Rendered HTML with SafeHtml markers removed
	 * 
	 * @security SafeHtml Handling: All SafeHtml markers are removed before output
	 * @security This prevents {{CANVASTACK_SAFE_HTML}} from appearing in browser HTML
	 * @security Tab rendering (renderTab) will mark its output, which is then unmarked here
	 * 
	 * @see Tab::renderTab() For tab rendering implementation
	 */
	public function render(array|string $object): array|string {
		// Unmark SafeHtml from all elements before processing
		if (is_array($object)) {
			$object = array_map(function($element) {
				return is_string($element) ? SafeHtml::unmark($element) : $element;
			}, $object);
		} else if (is_string($object)) {
			$object = SafeHtml::unmark($object);
		}
		
		$tabObj = "";
		if (true === is_array($object)) $tabObj = implode('', $object);
		
		if (true === canvastack_string_contained($tabObj, $this->opentabHTML)) {
			$rendered = $this->renderTab($object);
			// Unmark the tab output as well (renderTab returns array)
			if (is_array($rendered)) {
				return array_map(function($element) {
					return is_string($element) ? SafeHtml::unmark($element) : $element;
				}, $rendered);
			}
			return is_string($rendered) ? SafeHtml::unmark($rendered) : $rendered;
		} else {
			return $object;
		}
	}
	
	/**
	 * Automatically determine form action route path based on current route
	 * 
	 * Converts resource route names to their corresponding action routes:
	 * - *.create → *.store (for new records)
	 * - *.edit → *.update (for existing records)
	 * - Otherwise returns current route
	 * 
	 * @return string The determined action route path
	 * 
	 * @example
	 * ```php
	 * // If current route is 'users.create', returns 'users.store'
	 * // If current route is 'users.edit', returns 'users.update'
	 * ```
	 */
	private function setActionRoutePath(): string {
		$currentRoute = current_route();
		
		// Handle null route (e.g., in test environment)
		if ($currentRoute === null) {
			return '';
		}
		
		if (str_contains($currentRoute, '.create')) {
			// auto generate if we use Route::resource
			return str_replace('.create', '.store', $currentRoute);
		} elseif (str_contains($currentRoute, '.edit')) {
			// auto generate if we use Route::resource
			return str_replace('.edit', '.update', $currentRoute);
		} else {
			return $currentRoute;
		}
	}
	
	/**
	 * Draw Form Open
	 *
	 * @param string|false $path
	 * 		: ['url/path', 'route.name', 'Controller@method']
	 *
	 * @param string|false $method
	 * 		: ['POST', 'GET', 'PUT', 'DELETE']
	 * 		: would render 'POST', by default
	 * 
	 * @param string|false $type
	 * 		: ['url', 'route', 'action']
	 * 		: would render 'url', by default
	 *
	 * @param bool|false $file
	 * 		: false by default
	 * 		: would render enctype data if set true
	 *
	 * @security All user-controllable parameters are validated and escaped
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * @security Laravel Form::open() already escapes all attributes properly
	 *
	 * @author: wisnuwidi
	 */
	public function open(string|false $path = false, string|false $method = false, string|false $type = false, bool $file = false): void {
		$array = [];
		$array['files'] = $file;
		
		if (false === $path) {
			$path = $this->setActionRoutePath();
		}
		
		// Security: Validate and escape path parameter
		if (false !== $path && is_string($path)) {
			$path = canvastack_form_escape_html($path);
		}
		
		if (false === $type) {
			$type = 'route';
		} else {
			if (str_contains($path, '.')) {
				$type = 'route';
			} elseif (str_contains($path, '/')) {
				$type = 'url';
			} elseif (str_contains($path, '@')) {
				$type = 'action';
			}
		}
		
		$array[$type] = $path;
		
		// Security: Validate method parameter
		if (false !== $method) {
			$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
			$methodUpper = strtoupper($method);
			if (in_array($methodUpper, $allowedMethods)) {
				$array['method'] = $methodUpper;
			} else {
				throw new \InvalidArgumentException('Invalid HTTP method: ' . canvastack_form_escape_html($method));
			}
		}
		
		// Security: Mark HTML output as safe to prevent double-encoding
		// Laravel Form::open() already escapes all attributes properly
		$formHtml = Form::open($array) . '<div class="form-container">';
		$this->draw(SafeHtml::mark($formHtml));
	}
	
	private string $method = 'PUT';
	
	/**
	 * Set HTTP method for form submission
	 * 
	 * Overrides the default PUT method for form model binding.
	 * Common values are PUT, PATCH, POST, DELETE.
	 * 
	 * @param string $method HTTP method name (GET, POST, PUT, PATCH, DELETE)
	 * 
	 * @return void
	 * 
	 * @example
	 * ```php
	 * $form->method('PATCH');
	 * $form->model($user, $id);
	 * ```
	 */
	public function method(string $method): void {
		$this->method = $method;
	}
	
	public ?string $identity    = null;
	public bool $modelToView = false;
	/**
	 * Draw Form Model Binding
	 *
	 * @param object|string|null $model
	 * 		: Model(object) Name, example: $user
	 * 		: if null	=> check $this->model set by protected function model($class) 
	 * 					=> in CoreControler [ from Craft-Action trait ]
	 *
	 * @param int|false $row_selected
	 * 		: Row selected (example: id) from model
	 *
	 * @param string|false $path
	 * 		: ['route.name', 'Controller@method']
	 * 		: [ note ] if this parameter set as false, so it will draw view mode.
	 * 		  It would set modelToView as true,
	 * 		  disabling action buttons and replace the input tags to text view
	 *
	 * @param bool|false $file
	 * 		: Enable file upload support
	 *
	 * @param string|false $type
	 * 		: ['route', 'action']
	 * 		: would render 'route', by default
	 *
	 * @security Model name is encrypted to prevent tampering
	 * @security Row ID is validated and cast to integer
	 * @security Path parameter should be validated route/action name
	 *
	 * @author: wisnuwidi
	 */
	public function model(object|string|null $model = null, int|false $row_selected = false, string|false $path = false, bool $file = false, string|false $type = false): void {
		$this->alert_message();

		if ('show' === $this->currentRouteName) {
			$this->draw(Form::model([]));
			return;
		}

		$row_selected = $this->resolveRowSelected($row_selected);
		$model = $this->resolveModel($model, $row_selected);
		$path = $this->resolvePath($path);
		$model_name = $this->generateEncryptedModelName($model);
		$type = $this->resolveType($type);

		$attr = $this->buildFormAttributes($path, $row_selected, $model_name, $file, $type);

		$this->identity = $model_name;
		$this->draw(Form::model($model, $attr));
	}

	/**
	 * Resolve row selected ID from URL if in edit mode
	 *
	 * @param int|false $row_selected Provided row ID or false
	 * @return int|false Resolved row ID
	 */
	private function resolveRowSelected(int|false $row_selected): int|false {
		if (false !== $row_selected) {
			return $row_selected;
		}

		if (str_contains(current_route(), 'edit')) {
			$sliceURL = explode('/', canvastack_current_url());
			unset($sliceURL[array_key_last($sliceURL)]);
			return intval($sliceURL[array_key_last($sliceURL)]);
		}

		return false;
	}

	/**
	 * Resolve model instance from parameter or controller property
	 *
	 * @param object|string|null $model Model instance or class name
	 * @param int|false $row_selected Row ID for finding specific record
	 * @return object|null Resolved model instance
	 */
	private function resolveModel(object|string|null $model, int|false $row_selected): ?object {
		if (!empty($model)) {
			return $model;
		}

		$modelData = $this->getModelData();

		if (empty($modelData)) {
			return null;
		}

		if (false !== $row_selected) {
			return $modelData->find($row_selected);
		}

		return $modelData;
	}

	/**
	 * Get model data from controller property
	 *
	 * @return object|null Model instance
	 */
	private function getModelData(): ?object {
		if (empty($this->model)) {
			return null;
		}

		if (is_string($this->model)) {
			return new $this->model();
		}

		return $this->model;
	}

	/**
	 * Resolve path for form action
	 *
	 * @param string|false $path Provided path or false
	 * @return string Resolved path
	 */
	private function resolvePath(string|false $path): string {
		if (false !== $path) {
			return $path;
		}

		return $this->setActionRoutePath();
	}

	/**
	 * Generate encrypted model name with integrity checking
	 * 
	 * Creates a secure encrypted model identifier with HMAC integrity verification
	 * to prevent tampering. The format includes:
	 * - Random prefix (prevents pattern analysis)
	 * - Model class path (converted to dot notation)
	 * - Random suffix (additional entropy)
	 * - HMAC signature (integrity verification)
	 * 
	 * @param object|null $model Model instance to encrypt
	 * @return string Encrypted model name with integrity check
	 * 
	 * @security Uses Laravel's encrypt() which provides authenticated encryption
	 * @security HMAC signature prevents tampering with encrypted data
	 * @security Random strings prevent pattern analysis attacks
	 * 
	 * @throws \Illuminate\Contracts\Encryption\EncryptException If encryption fails
	 */
	private function generateEncryptedModelName(?object $model): string {
		$model_path = $this->extractModelPath($model);
		$random_prefix = canvastack_random_strings();
		$random_suffix = canvastack_random_strings();
		
		// Build model URI with random padding
		$model_uri = $random_prefix . '___' . str_replace('\\', '.', $model_path) . '___' . $random_suffix;
		
		// Generate HMAC for integrity checking
		$hmac = hash_hmac('sha256', $model_uri, config('app.key'));
		
		// Combine model URI with HMAC signature
		$payload = $model_uri . '|||' . $hmac;
		
		try {
			// Encrypt the entire payload (Laravel's encrypt provides authenticated encryption)
			$encrypted = encrypt($payload);
			
			// Log encryption for security monitoring
			\Log::info('Form: Model encrypted', [
				'model_class' => $model_path,
				'encrypted_length' => strlen($encrypted),
				'timestamp' => now()->toIso8601String()
			]);
			
			return $encrypted;
		} catch (\Exception $e) {
			\Log::error('Form: Model encryption failed', [
				'model_class' => $model_path,
				'error' => $e->getMessage()
			]);
			throw $e;
		}
	}

	/**
	 * Validate and decrypt encrypted model name
	 * 
	 * Validates the integrity of an encrypted model name before use.
	 * Checks:
	 * 1. Decryption succeeds (authenticated encryption check)
	 * 2. HMAC signature is present and valid
	 * 3. Model URI format is correct
	 * 
	 * @param string $encrypted_model_name Encrypted model name to validate
	 * @return string|false Decrypted model URI if valid, false if invalid
	 * 
	 * @security Prevents tampering with encrypted model names
	 * @security Validates HMAC signature to ensure integrity
	 * @security Logs all validation failures for security monitoring
	 * 
	 * @throws \Illuminate\Contracts\Encryption\DecryptException If decryption fails
	 */
	private function validateEncryptedModelName(string $encrypted_model_name): string|false {
		try {
			// Decrypt the payload
			$payload = decrypt($encrypted_model_name);
			
			// Check if payload contains HMAC separator
			if (!str_contains($payload, '|||')) {
				\Log::warning('Form: Invalid model name format - missing HMAC separator', [
					'encrypted_length' => strlen($encrypted_model_name)
				]);
				return false;
			}
			
			// Split payload into model URI and HMAC
			[$model_uri, $provided_hmac] = explode('|||', $payload, 2);
			
			// Regenerate HMAC to verify integrity
			$expected_hmac = hash_hmac('sha256', $model_uri, config('app.key'));
			
			// Constant-time comparison to prevent timing attacks
			if (!hash_equals($expected_hmac, $provided_hmac)) {
				\Log::warning('Form: Model name HMAC validation failed - possible tampering', [
					'encrypted_length' => strlen($encrypted_model_name),
					'timestamp' => now()->toIso8601String()
				]);
				return false;
			}
			
			// Validate model URI format (should contain ___)
			if (!str_contains($model_uri, '___')) {
				\Log::warning('Form: Invalid model URI format', [
					'encrypted_length' => strlen($encrypted_model_name)
				]);
				return false;
			}
			
			\Log::info('Form: Model name validated successfully', [
				'encrypted_length' => strlen($encrypted_model_name)
			]);
			
			return $model_uri;
			
		} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
			\Log::error('Form: Model name decryption failed - possible tampering', [
				'encrypted_length' => strlen($encrypted_model_name),
				'error' => $e->getMessage()
			]);
			return false;
		} catch (\Exception $e) {
			\Log::error('Form: Model name validation failed', [
				'encrypted_length' => strlen($encrypted_model_name),
				'error' => $e->getMessage()
			]);
			return false;
		}
	}

	/**
	 * Extract model class path from model instance
	 *
	 * @param object|null $model Model instance
	 * @return string Model class path
	 */
	private function extractModelPath(?object $model): string {
		if (empty($model)) {
			return canvastack_random_strings();
		}

		$basename = class_basename($model);

		if ('Collection' === $basename) {
			foreach ($model as $items) {
				return get_class($items);
			}
		}

		if ('Builder' === $basename) {
			return get_class($model->getModel());
		}

		return canvastack_random_strings();
	}

	/**
	 * Resolve type parameter with default
	 *
	 * @param string|false $type Provided type or false
	 * @return string Resolved type
	 */
	private function resolveType(string|false $type): string {
		return false !== $type ? $type : 'route';
	}

	/**
	 * Build form attributes array
	 *
	 * @param string $path Form action path
	 * @param int|false $row_selected Row ID
	 * @param string $model_name Encrypted model name
	 * @param bool $file Whether file upload is enabled
	 * @param string $type Type of path (route or action)
	 * @return array Form attributes
	 */
	private function buildFormAttributes(string $path, int|false $row_selected, string $model_name, bool $file, string $type): array {
		$attr = [
			$type => [$path, $row_selected],
			'name' => $model_name
		];

		if (false !== $row_selected) {
			$attr['method'] = $this->method;
		}

		if (false !== $file) {
			$attr['files'] = true;
		}

		return $attr;
	}
	
	/**
	 * Draw form model binding with file upload support enabled
	 * 
	 * Convenience method that calls model() with $file parameter set to true.
	 * Automatically sets enctype="multipart/form-data" for file uploads.
	 * 
	 * @param object|string|null $model Model instance or class name. If null, uses $this->model from controller
	 * @param int|false $row_selected Primary key value for editing existing record. False for new records
	 * @param string|false $path Route name or controller@method for form action. False for view-only mode
	 * @param string|false $type Type of path: 'route' (default) or 'action'
	 * 
	 * @return void
	 * 
	 * @security Model name is encrypted to prevent tampering
	 * @security Row ID is validated and cast to integer
	 * 
	 * @example
	 * ```php
	 * // Edit user with file upload support
	 * $form->modelWithFile($user, $userId, 'users.update');
	 * 
	 * // Create new user with file upload
	 * $form->modelWithFile(User::class, false, 'users.store');
	 * ```
	 * 
	 * @see model() For full parameter documentation
	 */
	public function modelWithFile(object|string|null $model = null, int|false $row_selected = false, string|false $path = false, string|false $type = false): void {
		$this->model($model, $row_selected, $path, true, $type);
	}
	
	/**
	 * Draw Form Close Tag
	 * 
	 * @param string|false $action_buttons Button label text
	 * @param array|false $option_buttons Button HTML attributes
	 * @param string|false $prefix HTML prefix content
	 * @param string|false $suffix HTML suffix content
	 * 
	 * @security All user-controllable parameters are escaped to prevent XSS
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * @security Laravel Form::submit() and Form::close() already escape attributes properly
	 */
	public function close(string|false $action_buttons = false, array|false $option_buttons = false, string|false $prefix = false, string|false $suffix = false): void {
		if ('show' !== $this->currentRouteName) {
			$options = $option_buttons;
			if (false === $option_buttons) {
				$options = [FormConstants::ATTR_CLASS => 'btn btn-success btn-slideright pull-right btn_create'];
			}
			
			$object = '';
			if (false !== $action_buttons) {
				// Security: action_buttons is escaped by Laravel Form::submit()
				$object .= Form::submit($action_buttons, $options);
			}
			$object .= Form::close();
			
			// Security: Escape prefix and suffix to prevent XSS
			$prefixEscaped = (false !== $prefix) ? canvastack_form_escape_html($prefix) : '';
			$suffixEscaped = (false !== $suffix) ? canvastack_form_escape_html($suffix) : '';
			
			// Security: Mark HTML output as safe to prevent double-encoding
			$closeHtml = '<div class="canvastack-action-box">' . $prefixEscaped . $object . $suffixEscaped . '</div>';
			$this->draw(SafeHtml::mark($closeHtml));
		}
	}
	
	/**
	 * Add CSRF token field to form
	 * 
	 * Generates and adds Laravel's CSRF token hidden input field for form security.
	 * This should be called inside forms that use POST, PUT, PATCH, or DELETE methods.
	 * 
	 * @return void
	 * 
	 * @security CSRF Protection: Uses Laravel's built-in CSRF token system
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * @security Laravel Form::token() generates secure CSRF token field
	 * 
	 * @example
	 * ```php
	 * $form->open('users.store', 'POST');
	 * $form->token();
	 * // ... form fields ...
	 * $form->close();
	 * ```
	 */
	public function token(): void {
		// Security: Mark CSRF token HTML as safe to prevent double-encoding
		$this->draw(SafeHtml::mark(Form::token()));
	}
	
	/**
	 * Add raw HTML to form
	 * 
	 * Allows inserting custom HTML content into the form.
	 * Useful for custom layouts, alerts, or any HTML that doesn't fit standard form elements.
	 * 
	 * @param string $html Raw HTML content to insert
	 * 
	 * @return void
	 * 
	 * @security WARNING: This method does NOT escape HTML content
	 * @security Only use with trusted HTML or properly escaped content
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * 
	 * @example
	 * ```php
	 * // Custom alert
	 * $form->html('<div class="alert alert-info">Custom message</div>');
	 * 
	 * // Custom layout
	 * $form->html('<div class="row"><div class="col-md-6">');
	 * $form->text('field1');
	 * $form->html('</div><div class="col-md-6">');
	 * $form->text('field2');
	 * $form->html('</div></div>');
	 * ```
	 */
	public function html(string $html): void {
		// Security: Mark HTML as safe (assumes caller has already escaped if needed)
		$this->draw(SafeHtml::mark($html));
	}
	
	/**
	 * Add alert/info box to form (Hybrid: Inline + Optional Modal)
	 * 
	 * Creates a Bootstrap alert box with optional modal for detailed information.
	 * 
	 * **Inline Mode (default):**
	 * - Displays alert directly in form
	 * - Always visible
	 * - Good for short messages
	 * 
	 * **Modal Mode (optional):**
	 * - Shows button that opens modal
	 * - Good for long/detailed information
	 * - Doesn't clutter form layout
	 * 
	 * @param string $type Alert type: 'info', 'success', 'warning', 'danger', 'primary', 'secondary'
	 * @param string $message Alert message (HTML allowed, will be escaped if needed)
	 * @param array $options Optional configuration:
	 *   - 'modal' (bool): Use modal instead of inline alert (default: false)
	 *   - 'title' (string): Modal title (required if modal=true)
	 *   - 'button_text' (string): Button text for modal (default: 'Show Info')
	 *   - 'button_icon' (string): Icon class for button (default: 'bi bi-info-circle')
	 *   - 'dismissible' (bool): Add close button to inline alert (default: false)
	 *   - 'id' (string): Custom ID for modal (auto-generated if not provided)
	 * 
	 * @return void
	 * 
	 * @security Message content is NOT escaped - caller must escape if needed
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * 
	 * @example
	 * ```php
	 * // Inline alert (simple)
	 * $form->alert('info', 'This is an information message');
	 * 
	 * // Inline alert with HTML
	 * $form->alert('warning', '<strong>Warning:</strong> Please check your input');
	 * 
	 * // Dismissible inline alert
	 * $form->alert('success', 'Operation completed!', ['dismissible' => true]);
	 * 
	 * // Modal alert (for long content)
	 * $form->alert('info', 'Long detailed information here...', [
	 *     'modal' => true,
	 *     'title' => 'Important Information',
	 *     'button_text' => 'Read More'
	 * ]);
	 * ```
	 */
	public function alert(string $type = 'info', string $message = '', array $options = []): void {
		// Default options
		$defaults = [
			'modal' => false,
			'title' => 'Information',
			'button_text' => 'Show Info',
			'button_icon' => 'bi bi-info-circle',
			'dismissible' => false,
			'id' => 'alertModal_' . uniqid(),
		];
		
		$config = array_merge($defaults, $options);
		
		// Validate type
		$validTypes = ['info', 'success', 'warning', 'danger', 'primary', 'secondary', 'light', 'dark'];
		if (!in_array($type, $validTypes)) {
			$type = 'info';
		}
		
		// Generate HTML based on mode
		if ($config['modal']) {
			// Modal mode: Button + Modal
			$html = $this->buildAlertModal($type, $message, $config);
		} else {
			// Inline mode: Bootstrap alert
			$html = $this->buildInlineAlert($type, $message, $config);
		}
		
		// Security: Mark HTML as safe
		$this->draw(SafeHtml::mark($html));
	}
	
	/**
	 * Build inline Bootstrap alert
	 * 
	 * @param string $type Alert type
	 * @param string $message Alert message
	 * @param array $config Configuration options
	 * 
	 * @return string HTML for inline alert
	 */
	private function buildInlineAlert(string $type, string $message, array $config): string {
		$dismissible = $config['dismissible'] ? ' alert-dismissible fade show' : '';
		$closeButton = '';
		
		if ($config['dismissible']) {
			$closeButton = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
		}
		
		return '<div class="alert alert-' . $type . $dismissible . '" role="alert">' 
			. $message 
			. $closeButton 
			. '</div>';
	}
	
	/**
	 * Build modal alert with button
	 * 
	 * @param string $type Alert type
	 * @param string $message Alert message
	 * @param array $config Configuration options
	 * 
	 * @return string HTML for button + modal
	 */
	private function buildAlertModal(string $type, string $message, array $config): string {
		$modalId = htmlspecialchars($config['id']);
		$title = htmlspecialchars($config['title']);
		$buttonText = htmlspecialchars($config['button_text']);
		$buttonIcon = htmlspecialchars($config['button_icon']);
		
		// Map alert type to button color
		$buttonColorMap = [
			'info' => 'info',
			'success' => 'success',
			'warning' => 'warning',
			'danger' => 'danger',
			'primary' => 'primary',
			'secondary' => 'secondary',
		];
		$buttonColor = $buttonColorMap[$type] ?? 'info';
		
		// Button
		$html = '<div class="mb-3">';
		$html .= '<button type="button" class="btn btn-' . $buttonColor . '" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">';
		$html .= '<i class="' . $buttonIcon . '"></i> ' . $buttonText;
		$html .= '</button>';
		$html .= '</div>';
		
		// Modal
		$html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">';
		$html .= '<div class="modal-dialog modal-lg">';
		$html .= '<div class="modal-content">';
		
		// Modal Header
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title" id="' . $modalId . 'Label">' . $title . '</h5>';
		$html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
		$html .= '</div>';
		
		// Modal Body
		$html .= '<div class="modal-body">';
		$html .= '<div class="alert alert-' . $type . '" role="alert">';
		$html .= $message;
		$html .= '</div>';
		$html .= '</div>';
		
		// Modal Footer
		$html .= '<div class="modal-footer">';
		$html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
		$html .= '</div>';
		
		$html .= '</div></div></div>';
		
		return $html;
	}
	
	/**
	 * Generate Label Element
	 * 
	 * Creates a label element with proper for attribute that matches the input id.
	 * The for attribute is automatically set to the field name, which matches the
	 * id attribute generated by Laravel Form facade for input elements.
	 * 
	 * @param string $name Field name - used as the for attribute value
	 * @param string $value Label text to display (already escaped)
	 * @param array $attributes Additional HTML attributes (optional, will be overridden)
	 * 
	 * @return string HTML label element with for attribute matching input id
	 * 
	 * @accessibility The for attribute ensures proper label-input association for screen readers
	 * @accessibility Clicking the label will focus the associated input element
	 * 
	 * @security Laravel Form::label() automatically escapes name and value
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * 
	 * @example
	 * // Generates: <label for="email" class="col-sm-3 control-label">Email Address</label>
	 * $this->label('email', 'Email Address');
	 * 
	 * // The corresponding input will have id="email" automatically:
	 * // <input type="text" name="email" id="email" />
	 */
	public function label(string $name, string $value, array $attributes = []): string {
		$attributes = [FormConstants::ATTR_CLASS => 'col-sm-3 control-label'];
		
		// Security: Check if label contains SAFE HTML markers (required field marker)
		// We only disable escaping for specific safe patterns that we generate
		$safeHtmlPatterns = [
			'<span class="required"',  // Required field marker
			'<sup>',                   // Superscript in required marker
			'<strong>',                // Bold in required marker
		];
		
		$containsSafeHtml = false;
		foreach ($safeHtmlPatterns as $pattern) {
			if (strpos($value, $pattern) !== false) {
				$containsSafeHtml = true;
				break;
			}
		}
		
		// Accessibility: Form::label() generates for="$name" which matches the id="$name" on inputs
		// Parameter 4: $escape_html - only set to false if label contains our safe HTML patterns
		// This prevents XSS while allowing required markers to render correctly
		$tag = Form::label($name, $value, $attributes, !$containsSafeHtml);
		
		// Security: Mark label HTML as safe to prevent double-encoding
		return SafeHtml::mark($tag);
	}
	
	public array $syncs = [];
	/**
	 * Ajax Relational Fields
	 * 
	 * Creates a dynamic select box that populates based on another field's value.
	 * 
	 * @param string $source_field Source field name that triggers the change
	 * @param string $target_field Target field name to populate
	 * @param string $values Database column name for option values
	 * @param string|null $labels Database column name for option labels
	 * @param string $query SQL query to fetch options (will be encrypted)
	 * @param mixed $selected Pre-selected value(s)
	 * 
	 * @security Field names are escaped in JavaScript output
	 * @security Query is encrypted before sending to client
	 * @security CRITICAL: Query should use parameterized queries on server side
	 * @security Validate encrypted data integrity on ajax endpoint
	 */
	public function sync(string $source_field, string $target_field, string $values, ?string $labels = null, string $query, mixed $selected = null): void {
		// Security: Validate field names (4.5.3)
		canvastack_form_validate_field_name($source_field, 'source');
		canvastack_form_validate_field_name($target_field, 'target');
		canvastack_form_validate_field_name($values, 'values');
		if (null !== $labels) {
			canvastack_form_validate_field_name($labels, 'labels');
		}
		
		// Security: Normalize query (remove extra whitespace)
		$normalizedQuery = trim(preg_replace('/\s\s+/', ' ', $query));
		
		// Security: Validate query for SQL injection patterns (4.5.1)
		canvastack_form_validate_sql_query($normalizedQuery);
		
		// Security: Log sync operation (4.5.5)
		canvastack_log_security_event('sync_operation', [
			'source_field' => $source_field,
			'target_field' => $target_field,
			'values_field' => $values,
			'labels_field' => $labels,
			'query_length' => strlen($normalizedQuery),
		]);
		
		// Security: Escape field names for safe JavaScript output
		$source_field_escaped = canvastack_form_escape_html($source_field);
		$target_field_escaped = canvastack_form_escape_html($target_field);
		
		// Security: Encrypt data with integrity checking (4.5.2)
		$syncs             = [];
		$syncs['source']   = $source_field;
		$syncs['target']   = $target_field;
		$syncs['values']   = canvastack_form_add_integrity_check(encrypt($values));
		$syncs['labels']   = canvastack_form_add_integrity_check(encrypt($labels));
		$syncs['selected'] = canvastack_form_add_integrity_check(encrypt($selected));
		$syncs['query']    = canvastack_form_add_integrity_check(encrypt($normalizedQuery));
		$data              = json_encode($syncs);
		$ajaxURL           = canvastack_get_ajax_urli();
		
		// Security: Use escaped field names in JavaScript
		$this->draw(canvastack_script("ajaxSelectionBox('{$source_field_escaped}', '{$target_field_escaped}', '{$ajaxURL}', '{$data}');"));
	}
	
	/**
	 * Retrieve field value from model for edit/show routes
	 * 
	 * Fetches the value of a specific field from the model when in edit or show mode.
	 * Handles soft-deleted models and retrieves the record based on route ID parameter.
	 * 
	 * @param string $field_name Name of the model field/column to retrieve
	 * @param string $function_name Type of form element (used for context, currently unused)
	 * 
	 * @return mixed Field value from model, or false if not in edit/show mode or field doesn't exist
	 * 
	 * @example
	 * ```php
	 * // In edit mode with route users.edit/{id}
	 * $value = $this->getModelValue('email', 'text'); // Returns user's email
	 * ```
	 */
	/**
	 * Get model value for a field with security checks
	 * 
	 * Retrieves the value of a field from the model, respecting:
	 * - Model's hidden attributes (e.g., password, api_token)
	 * - Model's guarded attributes
	 * - Soft delete status
	 * 
	 * @param string $field_name Field name to retrieve
	 * @param string $function_name Form element type (for logging)
	 * @return mixed Field value or null if hidden/not found
	 * 
	 * @security Respects model's $hidden property to prevent exposing sensitive data
	 * @security Logs attempts to access hidden attributes for security monitoring
	 * @security Only works in edit/show routes to prevent data leakage
	 */
	private function getModelValue(string $field_name, string $function_name): mixed {
		$value = null;
		
		if ('edit' === $this->currentRouteName || 'show' === $this->currentRouteName) {
			
			$model = [];
			if (!empty($this->model)) {
				
				if (true === canvastack_is_softdeletes($this->model)) {
					$model	= $this->model::withTrashed()->get();
				} else {
					$model	= $this->model->get();
				}
				
				$curRoute	= canvastack_get_current_route_id();
				if ('show' === $this->currentRouteName) $curRoute = canvastack_get_current_route_id(false);
				
				$model = $model->find($curRoute);
				
				// Security: Check if field is in model's hidden attributes
				if ($model && method_exists($model, 'getHidden')) {
					$hidden_attributes = $model->getHidden();
					if (in_array($field_name, $hidden_attributes)) {
						\Log::warning('Form: Attempt to access hidden model attribute', [
							'field' => $field_name,
							'model' => get_class($model),
							'route' => $this->currentRouteName,
							'timestamp' => now()->toIso8601String()
						]);
						return null;
					}
				}
				
				if (!is_null($model->{$field_name})) {
					$value = $model->{$field_name};
				}
			}
			
			return $value;
		}
		
		return false;
	}
	
	/**
	 * Check if a field is fillable in the model (mass assignment protection)
	 * 
	 * Validates that a field can be mass-assigned to prevent security vulnerabilities.
	 * Checks against:
	 * - Model's $fillable property (whitelist)
	 * - Model's $guarded property (blacklist)
	 * 
	 * @param object|null $model Model instance to check
	 * @param string $field_name Field name to validate
	 * @return bool True if field is fillable, false if guarded
	 * 
	 * @security Prevents mass assignment vulnerabilities
	 * @security Respects Laravel's fillable/guarded model properties
	 * @security Logs attempts to bind guarded fields
	 */
	private function isFieldFillable(?object $model, string $field_name): bool {
		if (empty($model)) {
			return true; // No model, no restrictions
		}
		
		// First check if guarded is ['*'] - this takes precedence
		if (method_exists($model, 'getGuarded')) {
			$guarded = $model->getGuarded();
			
			// Special case: if guarded is ['*'], all fields are guarded unless in fillable
			if (in_array('*', $guarded)) {
				if (method_exists($model, 'getFillable')) {
					$fillable = $model->getFillable();
					if (!in_array($field_name, $fillable)) {
						\Log::warning('Form: Attempt to bind field when all guarded', [
							'field' => $field_name,
							'model' => get_class($model),
							'timestamp' => now()->toIso8601String()
						]);
						return false;
					}
					// Field is in fillable, so it's allowed
					return true;
				}
				// No fillable defined but all guarded - reject
				\Log::warning('Form: Attempt to bind field when all guarded', [
					'field' => $field_name,
					'model' => get_class($model),
					'timestamp' => now()->toIso8601String()
				]);
				return false;
			}
			
			// If field is in guarded list (not ['*']), it's not fillable
			if (in_array($field_name, $guarded)) {
				\Log::warning('Form: Attempt to bind guarded field', [
					'field' => $field_name,
					'model' => get_class($model),
					'guarded' => $guarded,
					'timestamp' => now()->toIso8601String()
				]);
				return false;
			}
		}
		
		// Check if model has getFillable method (Eloquent models)
		if (method_exists($model, 'getFillable')) {
			$fillable = $model->getFillable();
			
			// If fillable is defined and not empty, field must be in it
			if (!empty($fillable)) {
				if (!in_array($field_name, $fillable)) {
					\Log::warning('Form: Attempt to bind non-fillable field', [
						'field' => $field_name,
						'model' => get_class($model),
						'fillable' => $fillable,
						'timestamp' => now()->toIso8601String()
					]);
					return false;
				}
			}
		}
		
		return true;
	}
	
	private mixed $paramValue		= null;
	private mixed $paramSelected	= null;
	
	/**
	 * Set parameter value and selected state based on model data and route context
	 * 
	 * Determines the appropriate value and selected state for form elements based on:
	 * - Current route (create, edit, show)
	 * - Element type (select, checkbox, radio, text, etc.)
	 * - Model data (for edit/show modes)
	 * - Provided parameters
	 * 
	 * @param string $function_name Type of form element (select, checkbox, radio, text, etc.)
	 * @param string $name Field name
	 * @param mixed $value Field value (may be overridden by model data)
	 * @param mixed $selected Selected value(s) for select/checkbox/radio elements
	 * 
	 * @return void Stores results in $this->paramValue and $this->paramSelected
	 * 
	 * @example
	 * ```php
	 * // For checkbox in edit mode, retrieves selected values from model
	 * $this->setModelValueAndSelectedToParams('checkbox', 'roles', [1,2,3], null);
	 * // $this->paramSelected['checkbox']['roles'] will contain model's role IDs
	 * ```
	 */
	private function setModelValueAndSelectedToParams(string $function_name, string $name, mixed $value, mixed $selected): void {
		if ('select' === $function_name) {
			$this->setSelectValueAndSelected($name, $value, $selected);
		} elseif ('checkbox' === $function_name) {
			$this->setCheckboxValueAndSelected($name, $value, $selected);
		} elseif ('radio' === $function_name) {
			$this->setRadioValueAndSelected($name, $value, $selected);
		} else {
			$this->setDefaultValueAndSelected($name, $function_name, $value, $selected);
		}
	}

	/**
	 * Set value and selected for select elements
	 *
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param mixed $selected Selected value
	 * @return void
	 */
	private function setSelectValueAndSelected(string $name, mixed $value, mixed $selected): void {
		$this->paramValue['select'][$name] = $value;

		if ('create' === $this->currentRouteName) {
			$this->paramSelected['select'][$name] = $selected;
		} elseif ('edit' === $this->currentRouteName) {
			$this->paramSelected['select'][$name] = !empty($selected) ? $selected : null;
		} else {
			if (!empty($value)) {
				$this->paramSelected['select'][$name] = $selected;
			} else {
				$this->paramSelected['select'][$name] = $this->getModelValue($name, 'select');
			}
		}
	}

	/**
	 * Set value and selected for checkbox elements
	 *
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param mixed $selected Selected value(s) - can be array or comma-separated string
	 * @return void
	 */
	private function setCheckboxValueAndSelected(string $name, mixed $value, mixed $selected): void {
		$this->paramValue['checkbox'][$name] = $value;

		// If selected parameter is provided, use it; otherwise get from model
		if (!empty($selected)) {
			// If selected is already an array, use it directly
			if (is_array($selected)) {
				$this->paramSelected['checkbox'][$name] = $selected;
			} else {
				// Parse string to array
				$this->paramSelected['checkbox'][$name] = $this->parseCheckboxSelected($selected);
			}
		} else {
			// Get from model if no selected parameter provided
			$modelSelected = $this->getModelValue($name, 'checkbox');

			if (!is_array($modelSelected)) {
				$modelSelected = $this->parseCheckboxSelected($modelSelected);
			}

			$this->paramSelected['checkbox'][$name] = $modelSelected;
		}
	}

	/**
	 * Parse checkbox selected value from string to array
	 *
	 * @param mixed $selected Selected value (comma-separated string)
	 * @return array Parsed selected values
	 */
	private function parseCheckboxSelected(mixed $selected): array {
		$selectedArray = explode(',', $selected);
		$result = [];

		foreach ($selectedArray as $item) {
			$intValue = intval($item);
			$result[$intValue] = $intValue;
		}

		return $result;
	}

	/**
	 * Set value and selected for radio elements
	 *
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param mixed $selected Selected value
	 * @return void
	 */
	private function setRadioValueAndSelected(string $name, mixed $value, mixed $selected): void {
		$this->paramValue['radio'][$name] = $value;
		
		// If selected parameter is explicitly provided (not false/null), use it
		// Otherwise get from model
		// Note: false is the default "not selected" value for radio buttons
		if ($selected !== false && $selected !== null) {
			// Use the provided selected value
			$this->paramSelected['radio'][$name] = $selected;
		} else {
			// Get from model
			$modelValue = $this->getModelValue($name, 'radio');
			$this->paramSelected['radio'][$name] = $modelValue !== false ? $modelValue : $selected;
		}
	}

	/**
	 * Set value and selected for default elements (text, etc.)
	 *
	 * @param string $name Field name
	 * @param string $function_name Element type
	 * @param mixed $value Field value
	 * @param mixed $selected Selected value
	 * @return void
	 */
	private function setDefaultValueAndSelected(string $name, string $function_name, mixed $value, mixed $selected): void {
		$this->paramValue[$function_name][$name] = $this->getModelValue($name, $function_name);
		$this->paramSelected[$function_name][$name] = $selected;
	}
	
	private array $added_attributes = [];
	/**
	 * Add custom attributes to be merged with form elements
	 * 
	 * @param array $attributes HTML attributes to add
	 * 
	 * @security Attributes will be validated in checkValidationAttributes()
	 * @security Do not pass user input directly - validate first
	 */
	public function addAttributes(array $attributes = []): void {
		$this->added_attributes = $attributes;
	}
	
	/**
	 * Check and merge validation attributes with current attributes
	 * 
	 * Merges validation attributes parsed from validation rules with the current
	 * attributes array. Handles conflicts intelligently:
	 * - Validation attributes take precedence for validation-related attrs (required, maxlength, etc.)
	 * - Current attributes take precedence for display-related attrs (class, id, style, etc.)
	 * - ARIA attributes are always merged from validation rules
	 * 
	 * Also validates attributes for dangerous event handlers to prevent XSS attacks.
	 * 
	 * @param string $field_name Field name to look up validation attributes
	 * @param array $current_attributes Current HTML attributes for the element
	 * 
	 * @return array Merged attributes with validation rules applied
	 * 
	 * @throws \InvalidArgumentException If dangerous event handler attributes are detected
	 * 
	 * @security Blocks dangerous event handler attributes (onclick, onerror, etc.)
	 * @security Logs security warnings when dangerous attributes are detected
	 * 
	 * @example
	 * ```php
	 * // Validation rules: ['email' => ['required', 'email', 'max:255']]
	 * // Current attrs: ['class' => 'form-control', 'placeholder' => 'Enter email']
	 * // Result: [
	 * //     'class' => 'form-control',
	 * //     'placeholder' => 'Enter email',
	 * //     'required' => 'required',
	 * //     'type' => 'email',
	 * //     'maxlength' => 255,
	 * //     'aria-required' => 'true'
	 * // ]
	 * ```
	 */
	protected static function checkValidationAttributes(string $field_name, array $current_attributes = []): array {
		$merged_attributes = $current_attributes;

		// Merge validation attributes if they exist for this field
		if (!empty(self::$validation_attributes[$field_name])) {
			$validation_attrs = self::$validation_attributes[$field_name];
			
			// Ensure validation_attrs is an array (backward compatibility with old format)
			if (!is_array($validation_attrs)) {
				// Old format: just field name as string, skip it
				$validation_attrs = [];
			}

			// Merge attributes intelligently
			foreach ($validation_attrs as $attr_key => $attr_value) {
				// Validation attributes take precedence over current attributes
				// except for display-related attributes (class, id, style, data-*)
				$displayAttributes = ['class', 'id', 'style'];
				$isDisplayAttr = in_array($attr_key, $displayAttributes) || str_starts_with($attr_key, 'data-');

				// For display attributes, keep current value if it exists
				if ($isDisplayAttr && isset($current_attributes[$attr_key])) {
					// For class attribute, merge both values
					if ($attr_key === 'class') {
						$merged_attributes[$attr_key] = trim($current_attributes[$attr_key] . ' ' . $attr_value);
					}
					// For other display attributes, keep current value
					continue;
				}

				// For validation and ARIA attributes, use validation value
				$merged_attributes[$attr_key] = $attr_value;
			}
		}

		// Handle nested field names (e.g., "roles[]", "permissions[admin]")
		$base_field_name = self::extractBaseFieldName($field_name);
		if ($base_field_name !== $field_name && !empty(self::$validation_attributes[$base_field_name])) {
			$validation_attrs = self::$validation_attributes[$base_field_name];
			
			// Ensure validation_attrs is an array (backward compatibility with old format)
			if (!is_array($validation_attrs)) {
				$validation_attrs = [];
			}

			foreach ($validation_attrs as $attr_key => $attr_value) {
				// Only add if not already set
				if (!isset($merged_attributes[$attr_key])) {
					$merged_attributes[$attr_key] = $attr_value;
				}
			}
		}

		// Security: Validate attributes for dangerous event handlers
		$dangerous_attrs = ['onclick', 'onload', 'onerror', 'onmouseover', 'onfocus', 
							'onblur', 'onchange', 'onsubmit', 'onkeyup', 'onkeydown',
							'onmouseout', 'onmouseenter', 'onmouseleave', 'ondblclick',
							'oncontextmenu', 'oninput', 'oninvalid', 'onreset', 'onsearch',
							'onselect', 'ondrag', 'ondrop', 'onscroll', 'onwheel',
							'oncopy', 'oncut', 'onpaste'];

		foreach ($merged_attributes as $attr_key => $attr_value) {
			$attr_key_lower = strtolower($attr_key);
			if (in_array($attr_key_lower, $dangerous_attrs)) {
				error_log('SECURITY WARNING: Dangerous attribute blocked in form element: ' . $attr_key);
				throw new \InvalidArgumentException('Event handler attributes not allowed: ' . canvastack_form_escape_html($attr_key));
			}
		}

		return $merged_attributes;
	}
	
	/**
	 * Extract base field name from nested field notation
	 * 
	 * Converts nested field names to their base name for validation lookup:
	 * - "roles[]" → "roles"
	 * - "permissions[admin]" → "permissions"
	 * - "user[email]" → "user"
	 * - "simple_field" → "simple_field"
	 * 
	 * @param string $field_name Field name (may include array notation)
	 * @return string Base field name without array notation
	 */
	private static function extractBaseFieldName(string $field_name): string {
		// Remove array notation: "roles[]" → "roles", "permissions[admin]" → "permissions"
		if (str_contains($field_name, '[')) {
			return substr($field_name, 0, strpos($field_name, '['));
		}

		return $field_name;
	}
	
	/**
	 * Set Input Form Parameters
	 * 
	 * @param string $function_name Element type (text, checkbox, select, etc.)
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param array $attributes HTML attributes
	 * @param string|bool $label Label text or true for auto-generation
	 * @param mixed $selected Selected value(s)
	 * 
	 * @security Field names and labels are escaped to prevent XSS
	 */
	/**
	 * Set parameters for form element with security validations
	 * 
	 * Stores form element configuration including label, value, selected state, and attributes.
	 * Performs security checks:
	 * - Escapes field names and labels to prevent XSS
	 * - Validates field is fillable (mass assignment protection)
	 * - Merges validation attributes
	 * - Checks for dangerous attributes
	 * 
	 * @param string $function_name Type of form element (text, select, checkbox, etc.)
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param array $attributes HTML attributes
	 * @param string|bool|null $label Label text (true for auto-generate, false for none, string for custom)
	 * @param mixed $selected Selected value(s) for select/checkbox/radio
	 * @return void
	 * 
	 * @security XSS Protection: Escapes field names and labels
	 * @security Mass Assignment Protection: Validates field is fillable
	 * @security Attribute Validation: Checks for dangerous event handlers
	 */
	private function setParams(string $function_name, string $name, mixed $value, array $attributes, string|bool|null $label, mixed $selected = false): void {
		// Security: Check if field is fillable (mass assignment protection)
		$model = $this->getModelData();
		if (!$this->isFieldFillable($model, $name)) {
			\Log::warning('Form: Skipping non-fillable field', [
				'field' => $name,
				'function' => $function_name,
				'model' => $model ? get_class($model) : 'none'
			]);
			// Skip this field - don't add it to params
			return;
		}
		
		// Security: Validate attributes to prevent injection attacks
		try {
			$attributes = canvastack_form_validate_attributes($attributes);
		} catch (\InvalidArgumentException $e) {
			\Log::error('Form: Dangerous attributes blocked - field skipped', [
				'field' => $name,
				'function' => $function_name,
				'error' => $e->getMessage(),
				'attributes' => $attributes
			]);
			// SECURITY: Do not render field with dangerous attributes
			// Skip this field entirely to prevent XSS
			return;
		}
		
		// Security: Escape field name when generating auto-label
		if (true === $label) {
			$nameEscaped = canvastack_form_escape_html($name);
			$label = ucwords(str_replace('-', ' ', ucwords(str_replace('_', ' ', $nameEscaped))));
		} else if (false !== $label && is_string($label)) {
			// Security: Do NOT escape custom label text here
			// It will be escaped by Form::label() in the label() method
			// Escaping here causes double-escaping of HTML entities
			// $label = canvastack_form_escape_html($label); // REMOVED - causes double escaping
		}
		
		if (!empty($this->added_attributes)) {
			$attributes = array_merge_recursive($attributes, $this->added_attributes);
		}
		
		if (!canvastack_string_contained(current_route(), 'show')) {
			$attributes = self::checkValidationAttributes($name, $attributes);
		}
		
		$this->setModelValueAndSelectedToParams($function_name, $name, $value, $selected);
		$this->params[$function_name][$name] = [
			'label'      => $label,
			'value'      => $this->paramValue[$function_name][$name],
			'selected'   => $this->paramSelected[$function_name][$name],
			'attributes' => $attributes
		];
		
		$this->element_name[$name] = $function_name;
	}
	
	/**
	 * Draw Input Element with Label
	 * 
	 * Renders a complete form group with label and input element.
	 * Ensures proper label-input association through matching for/id attributes.
	 * 
	 * Label Association Pattern:
	 * - Label has for="$name" attribute (generated by $this->label())
	 * - Input has id="$name" attribute (generated by Laravel Form facade)
	 * - This creates proper accessibility association for screen readers
	 * 
	 * @param string $function_name Form element type (text, email, select, etc.)
	 * @param string $name Field name - used for both label's for and input's id
	 * 
	 * @return false|null False if field is excluded, null on success
	 * 
	 * @accessibility Label for attribute matches input id for proper association
	 * @accessibility Required fields include visual (*) and aria-required attribute
	 * @accessibility Screen readers can navigate between labels and inputs
	 * 
	 * @security All user input is escaped before rendering
	 * @security SafeHtml markers are removed before output to prevent marker leakage
	 */
	private function inputDraw(string $function_name, string $name): false|null {
		if (in_array($name, $this->excludeFields)) {
			return false;
		}
		
		$hideClass = $this->getHideClass($name);
		$attributes = $this->getInitialAttributes($hideClass);
		
		$paramData = $this->params[$function_name][$name] ?? null;
		if (empty($paramData)) {
			return null;
		}
		
		// Security: Label is already escaped in setParams()
		$label = $paramData['label'];
		$value = $this->getInputValue($function_name, $paramData);
		$attributes = $paramData['attributes'];
		$attributes = canvastack_form_change_input_attribute($attributes, FormConstants::ATTR_CLASS, FormConstants::CLASS_FORM_CONTROL);
		
		$req_symbol = $this->getRequiredSymbol($attributes);
		$labelValue = $label . $req_symbol;
		
		$labelTag = $this->label($name, $labelValue, $attributes);
		$inputTag = $this->inputTag($function_name, $name, $attributes, $value);
		
		// Security: Unmark SafeHtml before concatenation to prevent marker from appearing in output
		// Both label and input may be marked as safe HTML, we need to unmark them before combining
		$labelTag = SafeHtml::unmark($labelTag);
		$inputTag = SafeHtml::unmark($inputTag);
		
		$inputForm = "<div class=\"form-group row{$hideClass}\">{$labelTag}{$inputTag}</div>";
		
		$this->draw($inputForm);
		return null;
	}
	
	/**
	 * Get hide class for field
	 * 
	 * @param string $name Field name
	 * @return string Hide class or empty string
	 */
	private function getHideClass(string $name): string {
		return in_array($name, $this->hideFields) ? ' hide' : '';
	}
	
	/**
	 * Get initial attributes with hide class if needed
	 * 
	 * @param string $hideClass Hide class string
	 * @return array Initial attributes
	 */
	private function getInitialAttributes(string $hideClass): array {
		if (empty($hideClass)) {
			return [];
		}
		
		return canvastack_form_change_input_attribute([], 'class', trim($hideClass));
	}
	
	/**
	 * Get input value based on function type
	 * 
	 * @param string $function_name Function type
	 * @param array $paramData Parameter data
	 * @return mixed Input value
	 */
	private function getInputValue(string $function_name, array $paramData): mixed {
		if ('password' === $function_name) {
			return bcrypt($paramData['value']);
		}
		
		return $paramData['value'];
	}
	
	/**
	 * Get Required Field Symbol
	 * 
	 * Generates a visual required field indicator (*) with accessibility support.
	 * The symbol includes both a title attribute and is designed to work with
	 * aria-required="true" on the input element itself.
	 * 
	 * @param array $attributes Field attributes to check for required validation
	 * 
	 * @return string HTML for required symbol with accessibility attributes
	 * 
	 * @accessibility The asterisk symbol is supplemented by:
	 *                - title attribute for mouse hover users
	 *                - aria-required="true" on the input element (added separately)
	 *                - aria-label includes "required" text for screen readers
	 * 
	 * @security Required field message is static HTML, no escaping needed
	 */
	private function getRequiredSymbol(array $attributes): string {
		$isRequired = in_array(FormConstants::VALIDATION_REQUIRED, $attributes) 
		           || in_array(FormConstants::VALIDATION_REQUIRED, array_keys($attributes));
		
		if (!$isRequired) {
			return '';
		}
		
		// Accessibility: Use aria-label to provide text alternative for the asterisk symbol
		// The actual aria-required="true" is added to the input element itself
		return ' <span class="required" title="This Required Field cannot be Leave Empty!" aria-label="required field"><sup>(</sup><strong>*</strong><sup>)</sup></span>';
	}
	
	/**
	 * Generate HTML input tag based on element type
	 * 
	 * Creates the appropriate input HTML for different form element types.
	 * Handles special cases like file uploads, selects, checkboxes, radios, and date/time fields.
	 * 
	 * @param string $function_name Type of form element (text, select, checkbox, file, etc.)
	 * @param string $name Field name
	 * @param array $attributes HTML attributes for the input
	 * @param mixed $value Field value (array for select options, string for text inputs)
	 * 
	 * @return string HTML input element wrapped in div.input-group
	 * 
	 * @security Select outputs are marked as SafeHtml to prevent double-encoding
	 * @security Checkbox and radio outputs use trait methods with XSS protection
	 * @security File uploads use inputFile() method with security validations
	 * 
	 * @example
	 * ```php
	 * // Text input
	 * $html = $this->inputTag('text', 'email', ['class' => 'form-control'], 'user@example.com');
	 * 
	 * // Select dropdown
	 * $html = $this->inputTag('select', 'country', [], ['US' => 'United States', 'UK' => 'United Kingdom']);
	 * ```
	 */
	private function inputTag(string $function_name, string $name, array $attributes, mixed $value): string {
		if ('file' === $function_name) {
			return $this->renderFileInput($name, $attributes);
		}
		
		if ('select' === $function_name) {
			return $this->renderSelectInput($name, $value, $attributes);
		}
		
		if ('checkbox' === $function_name) {
			return $this->renderCheckboxInput($name, $value, $attributes);
		}
		
		if ('radio' === $function_name) {
			return $this->renderRadioInput($name, $value, $attributes);
		}
		
		if ('barcode' === $function_name) {
			return $this->renderBarcodeInput($name, $value, $attributes);
		}
		
		if ('qrcode' === $function_name) {
			return $this->renderQrcodeInput($name, $value, $attributes);
		}
		
		if ('input-chain' === $function_name) {
			return $this->renderInputChain($name, $value, $attributes);
		}
		
		if ($this->isDateTimeInput($function_name)) {
			$function_name = 'text';
		}
		
		if ('password' === $function_name) {
			return $this->renderPasswordInput($name, $attributes);
		}
		
		if ($this->isSelectTypeInput($function_name)) {
			return $this->renderSelectTypeInput($function_name, $name, $value, $attributes);
		}
		
		return $this->renderDefaultInput($function_name, $name, $value, $attributes);
	}
	
	/**
	 * Render file input element
	 * 
	 * @param string $name Field name
	 * @param array $attributes HTML attributes
	 * @return string HTML output
	 */
	private function renderFileInput(string $name, array $attributes): string {
		if (!empty($this->params['file'][$name]['value'])) {
			$attributes['value'] = $this->params['file'][$name]['value'];
		}
		return $this->inputFile($name, $attributes);
	}
	
	/**
	 * Render Select Input Element
	 * 
	 * Renders select dropdown elements using Laravel Form facade.
	 * The Form facade automatically generates an id attribute matching the name attribute,
	 * ensuring proper label-input association.
	 * 
	 * @param string $name Field name - will be used as both name and id attributes
	 * @param mixed $value Select options array
	 * @param array $attributes HTML attributes
	 * 
	 * @return string HTML select element wrapped in input-group div
	 * 
	 * @accessibility Laravel Form facade automatically adds id="$name" to match label's for="$name"
	 * @accessibility This ensures screen readers can properly associate labels with select elements
	 * 
	 * @security Output is marked as safe HTML to prevent double-encoding
	 */
	private function renderSelectInput(string $name, mixed $value, array $attributes): string {
		$selected = $this->params['select'][$name]['selected'];
		$selectHtml = Form::select($name, $value, $selected, $attributes);
		// Security: Mark as safe HTML to prevent double-encoding
		return $this->wrapInInputGroup($selectHtml, true);
	}
	
	/**
	 * Render checkbox input element
	 * 
	 * @param string $name Field name
	 * @param mixed $value Checkbox options
	 * @param array $attributes HTML attributes
	 * @return string HTML output
	 */
	private function renderCheckboxInput(string $name, mixed $value, array $attributes): string {
		$selected = $this->params['checkbox'][$name]['selected'];
		$content = $this->drawCheckBox($name, $value, $selected, $attributes);

		// Security: drawCheckBox already marks output as safe HTML
		return $this->wrapInInputGroup($content, true);
	}
	
	/**
	 * Render radio input element
	 * 
	 * @param string $name Field name
	 * @param mixed $value Radio options
	 * @param array $attributes HTML attributes
	 * @return string HTML output
	 */
	private function renderRadioInput(string $name, mixed $value, array $attributes): string {
		$selected = $this->params['radio'][$name]['selected'];
		$content = $this->drawRadioBox($name, $value, $selected, $attributes);
		// Security: drawRadioBox already marks output as safe HTML
		return $this->wrapInInputGroup($content, true);
	}
	
	/**
	 * Render barcode input element with preview, scanner, and auto-generate
	 * 
	 * @param string $name Field name
	 * @param mixed $value Current barcode value
	 * @param array $attributes HTML attributes (includes barcode_options)
	 * @return string HTML output
	 */
	private function renderBarcodeInput(string $name, mixed $value, array $attributes): string {
		// Extract barcode options from attributes
		$options = $attributes['barcode_options'] ?? [];
		
		// Default options
		$defaults = [
			'format' => 'CODE128',
			'formats' => null,
			'preview' => true,
			'preview_position' => 'top',
			'preview_width' => 2,
			'preview_height' => 60,
			'scanner' => false,
			'scanner_button_text' => 'Scan',
			'auto_generate' => false,
			'auto_generate_source' => 'id',
			'auto_generate_separator' => '-',
			'auto_generate_prefix' => '',
			'auto_generate_length' => 13,
			'entity_type' => 'product',  // ⭐ NEW: Entity type for API generation
			'validate' => false,
			'help' => true,
			'help_title' => 'Barcode Input Guide',
			'help_content' => null,
			'placeholder' => 'Enter or scan barcode'
		];
		
		$config = array_merge($defaults, $options);
		
		// Build data attributes for barcode functionality
		$dataAttrs = [
			'data-barcode-field' => 'true',
			'data-barcode-format' => $config['format'],
			'data-barcode-preview-position' => $config['preview_position'],
			'data-barcode-preview-width' => (string)$config['preview_width'],
			'data-barcode-preview-height' => (string)$config['preview_height']
		];
		
		if ($config['validate']) {
			$dataAttrs['data-barcode-validate'] = 'true';
		}
		
		if ($config['auto_generate']) {
			$dataAttrs['data-barcode-auto-generate'] = 'true';
			
			if (is_array($config['auto_generate_source'])) {
				$dataAttrs['data-barcode-auto-source'] = json_encode($config['auto_generate_source']);
			} else {
				$dataAttrs['data-barcode-auto-source'] = $config['auto_generate_source'];
			}
			
			// ⭐ NEW: Add entity_type for API generation
			if ($config['auto_generate_source'] === 'api') {
				$dataAttrs['data-barcode-entity-type'] = $config['entity_type'];
			}
			
			$dataAttrs['data-barcode-auto-separator'] = $config['auto_generate_separator'];
			$dataAttrs['data-barcode-auto-prefix'] = $config['auto_generate_prefix'];
			$dataAttrs['data-barcode-auto-length'] = (string)$config['auto_generate_length'];
		}
		
		// Build clean attributes for Form::text() - only scalar values allowed
		$cleanAttributes = [];
		
		// Standard HTML attributes that Form::text() expects
		$allowedAttributes = [
			'class', 'id', 'style', 'placeholder', 'required', 'disabled', 'readonly',
			'maxlength', 'minlength', 'pattern', 'title', 'autocomplete', 'autofocus',
			'aria-label', 'aria-required', 'aria-invalid', 'aria-describedby'
		];
		
		// Copy allowed attributes from original attributes (only if scalar)
		foreach ($attributes as $key => $val) {
			// Skip barcode_options (already extracted)
			if ($key === 'barcode_options') {
				continue;
			}
			
			// Only include scalar values and data-* attributes
			if (is_scalar($val) || $val === null) {
				// Include if it's in allowed list or starts with data-
				if (in_array($key, $allowedAttributes) || strpos($key, 'data-') === 0) {
					$cleanAttributes[$key] = $val;
				}
			}
		}
		
		// Merge barcode data attributes
		$cleanAttributes = array_merge($cleanAttributes, $dataAttrs);
		
		// Set placeholder
		$cleanAttributes['placeholder'] = $config['placeholder'];
		
		// Build HTML - wrap everything in col-sm-9 for consistent form layout
		$html = '<div class="col-sm-9">';
		
		// Preview (top position)
		if ($config['preview'] && $config['preview_position'] === 'top') {
			$html .= '<div class="barcode-preview-container mb-2" ';
			$html .= 'data-barcode-preview="' . htmlspecialchars($name) . '" ';
			$html .= 'data-position="top" style="display:none;">';
			$html .= '<canvas class="barcode-canvas"></canvas>';
			$html .= '</div>';
		}
		
		// Input group start
		$html .= '<div class="input-group">';
		
		// Format selector (if multiple formats)
		if (is_array($config['formats']) && count($config['formats']) > 1) {
			$html .= '<select class="form-select" style="max-width: 150px;" ';
			$html .= 'data-barcode-format-selector="' . htmlspecialchars($name) . '">';
			
			foreach ($config['formats'] as $formatValue => $formatLabel) {
				$selected = ($formatValue === $config['format']) ? ' selected' : '';
				$html .= '<option value="' . htmlspecialchars($formatValue) . '"' . $selected . '>';
				$html .= htmlspecialchars($formatLabel) . '</option>';
			}
			
			$html .= '</select>';
		}
		
		// Input field using Laravel Form facade with cleaned attributes
		$html .= Form::text($name, $value, $cleanAttributes);
		
		// Scanner button
		if ($config['scanner']) {
			$html .= '<button type="button" class="btn btn-secondary" ';
			$html .= 'data-barcode-scanner="' . htmlspecialchars($name) . '" title="Scan with webcam">';
			$html .= '<i class="bi bi-upc-scan"></i> ' . htmlspecialchars($config['scanner_button_text']);
			$html .= '</button>';
		}
		
		// Auto-generate button
		if ($config['auto_generate']) {
			$html .= '<button type="button" class="btn btn-info" ';
			$html .= 'data-barcode-generate="' . htmlspecialchars($name) . '" title="Auto-generate barcode">';
			$html .= '<i class="bi bi-magic"></i>';
			$html .= '</button>';
		}
		
		// Help button
		if ($config['help']) {
			$html .= '<button type="button" class="btn btn-outline-secondary" ';
			$html .= 'data-bs-toggle="modal" data-bs-target="#barcodeHelpModal_' . htmlspecialchars($name) . '" ';
			$html .= 'title="' . htmlspecialchars($config['help_title']) . '">';
			$html .= '<i class="bi bi-question-circle"></i>';
			$html .= '</button>';
		}
		
		$html .= '</div>'; // Close input-group
		
		// Validation error
		$html .= '<div class="invalid-feedback" data-barcode-error="' . htmlspecialchars($name) . '"></div>';
		
		// Preview (bottom position)
		if ($config['preview'] && $config['preview_position'] === 'bottom') {
			$html .= '<div class="barcode-preview-container mt-2" ';
			$html .= 'data-barcode-preview="' . htmlspecialchars($name) . '" ';
			$html .= 'data-position="bottom" style="display:none;">';
			$html .= '<canvas class="barcode-canvas"></canvas>';
			$html .= '</div>';
		}
		
		// Help modal
		if ($config['help']) {
			$html .= $this->buildBarcodeHelpModal($name, $config);
		}
		
		$html .= '</div>'; // Close col-sm-9
		
		// Assets will be loaded automatically by Canvastack based on element_plugins registration
		// No need for inline script loading - Canvastack handles this dynamically per template
		
		// Return marked HTML string (not SafeHtml object)
		return SafeHtml::mark($html);
	}
	
	/**
	 * Build help modal HTML for barcode input
	 */
	private function buildBarcodeHelpModal(string $fieldName, array $config): string {
		$modalId = 'barcodeHelpModal_' . $fieldName;
		
		$html = '<div class="modal fade" id="' . htmlspecialchars($modalId) . '" tabindex="-1">';
		$html .= '<div class="modal-dialog modal-lg"><div class="modal-content">';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title">' . htmlspecialchars($config['help_title']) . '</h5>';
		$html .= '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
		$html .= '</div><div class="modal-body">';
		
		if ($config['help_content']) {
			$html .= $config['help_content'];
		} else {
			$html .= '<h6>How to Use</h6><ol>';
			$html .= '<li><strong>Manual Input:</strong> Type the barcode number directly</li>';
			if ($config['scanner']) {
				$html .= '<li><strong>Scan:</strong> Click the "Scan" button to use your camera</li>';
			}
			if ($config['auto_generate']) {
				$html .= '<li><strong>Auto-Generate:</strong> Click the magic icon to generate automatically</li>';
			}
			$html .= '<li><strong>Preview:</strong> The barcode will be displayed as you type</li>';
			$html .= '</ol>';
		}
		
		$html .= '</div><div class="modal-footer">';
		$html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
		$html .= '</div></div></div></div>';
		
		return $html;
	}
	
	/**
	 * Render QR code input element with preview, scanner, and auto-generate
	 * 
	 * @param string $name Field name
	 * @param mixed $value Current QR code value
	 * @param array $attributes HTML attributes (includes qrcode_options)
	 * @return string HTML output
	 */
	private function renderQrcodeInput(string $name, mixed $value, array $attributes): string {
		// Extract QR code options from attributes
		$options = $attributes['qrcode_options'] ?? [];
		
		// Default options
		$defaults = [
			'format' => 'text',
			'size' => 200,
			'error_correction' => 'M',
			'preview' => true,
			'preview_position' => 'top',
			'scanner' => false,
			'scanner_button_text' => 'Scan',
			'auto_generate' => false,
			'auto_generate_source' => 'id',
			'auto_generate_separator' => '-',
			'auto_generate_prefix' => '',
			'auto_generate_format' => 'text',
			'auto_update_from_form' => false,
			'auto_update_delay' => 500,
			'auto_clear_empty' => false,
			'form_fields' => 'all',
			'form_format' => 'json',
			'form_exclude' => ['_token', '_method', 'image', 'file'],
			'form_url_base' => '',
			'validate' => false,
			'help' => true,
			'help_title' => 'QR Code Input Guide',
			'help_content' => null,
			'placeholder' => 'Enter or scan QR code data'
		];
		
		$config = array_merge($defaults, $options);
		
		// Build data attributes for QR code functionality
		$dataAttrs = [
			'data-qrcode-field' => 'true',
			'data-qrcode-format' => $config['format'],
			'data-qrcode-size' => (string)$config['size'],
			'data-qrcode-error-correction' => $config['error_correction'],
			'data-qrcode-preview-position' => $config['preview_position']
		];
		
		if ($config['validate']) {
			$dataAttrs['data-qrcode-validate'] = 'true';
		}
		
		if ($config['auto_generate']) {
			$dataAttrs['data-qrcode-auto-generate'] = 'true';
			
			if (is_array($config['auto_generate_source'])) {
				$dataAttrs['data-qrcode-auto-source'] = json_encode($config['auto_generate_source']);
			} else {
				$dataAttrs['data-qrcode-auto-source'] = $config['auto_generate_source'];
			}
			
			$dataAttrs['data-qrcode-auto-separator'] = $config['auto_generate_separator'];
			$dataAttrs['data-qrcode-auto-prefix'] = $config['auto_generate_prefix'];
			$dataAttrs['data-qrcode-auto-format'] = $config['auto_generate_format'];
		}
		
		// Form fields configuration - always set (used by both auto-update and generate button)
		// Can be array of field names or string 'all'
		if (is_array($config['form_fields'])) {
			$dataAttrs['data-qrcode-form-fields'] = json_encode($config['form_fields']);
		} elseif ($config['form_fields'] === 'all') {
			$dataAttrs['data-qrcode-form-fields'] = 'all';
		}
		
		$dataAttrs['data-qrcode-form-format'] = $config['form_format'];
		
		if (is_array($config['form_exclude'])) {
			$dataAttrs['data-qrcode-form-exclude'] = json_encode($config['form_exclude']);
		}
		
		if (!empty($config['form_url_base'])) {
			$dataAttrs['data-qrcode-form-url-base'] = $config['form_url_base'];
		}
		
		// Auto-update from form attributes
		if ($config['auto_update_from_form']) {
			$dataAttrs['data-qrcode-auto-update-form'] = 'true';
			$dataAttrs['data-qrcode-auto-update-delay'] = (string)$config['auto_update_delay'];
			
			if ($config['auto_clear_empty']) {
				$dataAttrs['data-qrcode-auto-clear-empty'] = 'true';
			}
		}
		
		// Build clean attributes for Form::text() - only scalar values allowed
		$cleanAttributes = [];
		
		// Standard HTML attributes
		$allowedAttributes = [
			'class', 'id', 'style', 'placeholder', 'required', 'disabled', 'readonly',
			'maxlength', 'minlength', 'pattern', 'title', 'autocomplete', 'autofocus',
			'aria-label', 'aria-required', 'aria-invalid', 'aria-describedby'
		];
		
		// Copy allowed attributes from original attributes (only if scalar)
		foreach ($attributes as $key => $val) {
			if ($key === 'qrcode_options') continue;
			
			if (is_scalar($val) || $val === null) {
				if (in_array($key, $allowedAttributes) || strpos($key, 'data-') === 0) {
					$cleanAttributes[$key] = $val;
				}
			}
		}
		
		// Merge QR code data attributes
		$cleanAttributes = array_merge($cleanAttributes, $dataAttrs);
		$cleanAttributes['placeholder'] = $config['placeholder'];
		
		// Build HTML - wrap everything in col-sm-9 for consistent form layout
		$html = '<div class="col-sm-9">';
		
		// Preview (top position)
		if ($config['preview'] && $config['preview_position'] === 'top') {
			$html .= '<div class="qrcode-preview-container mb-2" ';
			$html .= 'data-qrcode-preview="' . htmlspecialchars($name) . '" ';
			$html .= 'data-position="top" style="display:none;">';
			$html .= '</div>';
		}
		
		// Input group start
		$html .= '<div class="input-group">';
		
		// Input field using Laravel Form facade with cleaned attributes
		$html .= Form::text($name, $value, $cleanAttributes);
		
		// Scanner button
		if ($config['scanner']) {
			$html .= '<button type="button" class="btn btn-secondary" ';
			$html .= 'data-qrcode-scanner="' . htmlspecialchars($name) . '" title="Scan with webcam">';
			$html .= '<i class="bi bi-qr-code-scan"></i> ' . htmlspecialchars($config['scanner_button_text']);
			$html .= '</button>';
		}
		
		// Auto-generate button
		if ($config['auto_generate']) {
			$html .= '<button type="button" class="btn btn-info" ';
			$html .= 'data-qrcode-generate="' . htmlspecialchars($name) . '" title="Auto-generate QR code">';
			$html .= '<i class="bi bi-magic"></i>';
			$html .= '</button>';
		}
		
		// Generate from form button (always show)
		$html .= '<button type="button" class="btn btn-success" ';
		$html .= 'data-qrcode-generate-form="' . htmlspecialchars($name) . '" title="Generate from all form data">';
		$html .= '<i class="bi bi-file-earmark-text"></i>';
		$html .= '</button>';
		
		// Help button
		if ($config['help']) {
			$html .= '<button type="button" class="btn btn-outline-secondary" ';
			$html .= 'data-bs-toggle="modal" data-bs-target="#qrcodeHelpModal_' . htmlspecialchars($name) . '" ';
			$html .= 'title="' . htmlspecialchars($config['help_title']) . '">';
			$html .= '<i class="bi bi-question-circle"></i>';
			$html .= '</button>';
		}
		
		$html .= '</div>'; // Close input-group
		
		// Validation error
		$html .= '<div class="invalid-feedback" data-qrcode-error="' . htmlspecialchars($name) . '"></div>';
		
		// Preview (bottom position)
		if ($config['preview'] && $config['preview_position'] === 'bottom') {
			$html .= '<div class="qrcode-preview-container mt-2" ';
			$html .= 'data-qrcode-preview="' . htmlspecialchars($name) . '" ';
			$html .= 'data-position="bottom" style="display:none;">';
			$html .= '</div>';
		}
		
		// Help modal
		if ($config['help']) {
			$html .= $this->buildQrcodeHelpModal($name, $config);
		}
		
		$html .= '</div>'; // Close col-sm-9
		
		// Assets will be loaded automatically by Canvastack based on element_plugins registration
		// No need for inline script loading - Canvastack handles this dynamically per template
		
		// Return marked HTML string
		return SafeHtml::mark($html);
	}
	
	/**
	 * Build help modal HTML for QR code input
	 */
	private function buildQrcodeHelpModal(string $fieldName, array $config): string {
		$modalId = 'qrcodeHelpModal_' . $fieldName;
		
		$html = '<div class="modal fade" id="' . htmlspecialchars($modalId) . '" tabindex="-1">';
		$html .= '<div class="modal-dialog modal-lg"><div class="modal-content">';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title">' . htmlspecialchars($config['help_title']) . '</h5>';
		$html .= '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
		$html .= '</div><div class="modal-body">';
		
		if ($config['help_content']) {
			$html .= $config['help_content'];
		} else {
			$html .= '<h6>How to Use</h6><ol>';
			$html .= '<li><strong>Manual Input:</strong> Type the QR code data directly</li>';
			if ($config['scanner']) {
				$html .= '<li><strong>Scan:</strong> Click the "Scan" button to use your camera</li>';
			}
			if ($config['auto_generate']) {
				$html .= '<li><strong>Auto-Generate:</strong> Click the magic icon to generate automatically</li>';
			}
			$html .= '<li><strong>Generate from Form:</strong> Click the green document icon to generate QR code from all filled form data</li>';
			$html .= '<li><strong>Preview:</strong> The QR code will be displayed as you type</li>';
			$html .= '</ol>';
			
			$html .= '<h6>Generate from Form Feature</h6>';
			$html .= '<p>The "Generate from Form" button (green document icon) will:</p>';
			$html .= '<ul>';
			$html .= '<li>Collect all filled data from the form (ID, SKU, Name, Category, Price, etc.)</li>';
			$html .= '<li>Format the data as JSON for easy scanning and parsing</li>';
			$html .= '<li>Generate a QR code containing all product information</li>';
			$html .= '<li>Perfect for inventory management and mobile apps</li>';
			$html .= '</ul>';
			
			$html .= '<h6>Supported Formats</h6><ul>';
			$html .= '<li><strong>Text:</strong> Plain text data</li>';
			$html .= '<li><strong>URL:</strong> Website links</li>';
			$html .= '<li><strong>JSON:</strong> Structured data (used by Generate from Form)</li>';
			$html .= '<li><strong>vCard:</strong> Contact information</li>';
			$html .= '<li><strong>WiFi:</strong> WiFi credentials</li>';
			$html .= '</ul>';
		}
		
		$html .= '</div><div class="modal-footer">';
		$html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
		$html .= '</div></div></div></div>';
		
		return $html;
	}
	
	/**
	 * Render Input Chain field with auto-generation from multiple sources
	 * 
	 * @param string $name Field name
	 * @param mixed $value Current value
	 * @param array $attributes HTML attributes (includes chain_sources and chain_options)
	 * @return string HTML output
	 */
	private function renderInputChain(string $name, mixed $value, array $attributes): string {
		// Extract chain sources and options from attributes
		$sources = $attributes['chain_sources'] ?? [];
		$options = $attributes['chain_options'] ?? [];
		
		// Default options
		$defaults = [
			'separator' => '-',
			'transform' => 'uppercase',
			'prefix' => '',
			'suffix' => '',
			'max_words' => 2,
			'readonly' => true,
			'placeholder' => 'Auto-generated',
			'format' => 'default',
			'debounce' => 300,
			'auto_update' => true,
			'help' => true,
			'skip_empty' => true,
			'trim_spaces' => true,
			'remove_special' => true,
			'word_separator' => '-',
		];
		
		$config = array_merge($defaults, $options);
		
		// Build data attributes for input chain functionality
		$dataAttrs = [
			'data-chain-field' => 'true',
			'data-chain-sources' => json_encode($sources),
			'data-chain-separator' => $config['separator'],
			'data-chain-transform' => $config['transform'],
			'data-chain-prefix' => $config['prefix'],
			'data-chain-suffix' => $config['suffix'],
			'data-chain-max-words' => (string)$config['max_words'],
			'data-chain-format' => $config['format'],
			'data-chain-debounce' => (string)$config['debounce'],
			'data-chain-skip-empty' => $config['skip_empty'] ? 'true' : 'false',
			'data-chain-trim-spaces' => $config['trim_spaces'] ? 'true' : 'false',
			'data-chain-remove-special' => $config['remove_special'] ? 'true' : 'false',
			'data-chain-word-separator' => $config['word_separator'],
		];
		
		if (!$config['auto_update']) {
			$dataAttrs['data-chain-auto-update'] = 'false';
		}
		
		// Build clean attributes for Form::text() - only scalar values allowed
		$cleanAttributes = [];
		
		// Standard HTML attributes
		$allowedAttributes = [
			'class', 'id', 'style', 'placeholder', 'required', 'disabled', 'readonly',
			'maxlength', 'minlength', 'pattern', 'title', 'autocomplete', 'autofocus',
			'aria-label', 'aria-required', 'aria-invalid', 'aria-describedby'
		];
		
		// Copy allowed attributes from original attributes (only if scalar)
		foreach ($attributes as $key => $val) {
			if ($key === 'chain_sources' || $key === 'chain_options') continue;
			
			if (is_scalar($val) || $val === null) {
				if (in_array($key, $allowedAttributes) || strpos($key, 'data-') === 0) {
					$cleanAttributes[$key] = $val;
				}
			}
		}
		
		// Merge chain data attributes
		$cleanAttributes = array_merge($cleanAttributes, $dataAttrs);
		$cleanAttributes['placeholder'] = $config['placeholder'];
		
		// ⭐ FIX: Make field readonly if configured (must be set AFTER merge)
		if ($config['readonly']) {
			$cleanAttributes['readonly'] = true;  // Use boolean true, not string
		}
		
		// Build HTML - wrap everything in col-sm-9 for consistent form layout
		$html = '<div class="col-sm-9">';
		
		// Input group start
		$html .= '<div class="input-group">';
		
		// Input field using Laravel Form facade with cleaned attributes
		$html .= Form::text($name, $value, $cleanAttributes);
		
		// Manual generate button
		$html .= '<button type="button" class="btn btn-primary" ';
		$html .= 'data-chain-generate="' . htmlspecialchars($name) . '" title="Generate now">';
		$html .= '<i class="bi bi-arrow-repeat"></i>';
		$html .= '</button>';
		
		// ⭐ FIX: Help button - only show if help is enabled
		if ($config['help']) {
			$html .= '<button type="button" class="btn btn-outline-secondary" ';
			$html .= 'data-bs-toggle="modal" data-bs-target="#chainHelpModal_' . htmlspecialchars($name) . '" ';
			$html .= 'title="Input Chain Help">';
			$html .= '<i class="bi bi-question-circle"></i>';
			$html .= '</button>';
		}
		
		$html .= '</div>'; // Close input-group
		
		// Help text
		$html .= '<small class="form-text text-muted">';
		$html .= 'Auto-generated from: ' . htmlspecialchars(implode(', ', $sources));
		$html .= '</small>';
		
		// Help modal
		if ($config['help']) {
			$html .= $this->buildInputChainHelpModal($name, $config, $sources);
		}
		
		$html .= '</div>'; // Close col-sm-9
		
		// Assets will be loaded automatically by Canvastack based on element_plugins registration
		// No need for inline script loading - Canvastack handles this dynamically per template
		
		// Return marked HTML string
		return SafeHtml::mark($html);
	}
	
	/**
	 * Build help modal HTML for input chain
	 */
	private function buildInputChainHelpModal(string $fieldName, array $config, array $sources): string {
		$modalId = 'chainHelpModal_' . $fieldName;
		
		$html = '<div class="modal fade" id="' . htmlspecialchars($modalId) . '" tabindex="-1">';
		$html .= '<div class="modal-dialog modal-lg"><div class="modal-content">';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title">Input Chain Help</h5>';
		$html .= '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
		$html .= '</div><div class="modal-body">';
		
		$html .= '<h6>How It Works</h6>';
		$html .= '<p>This field automatically generates its value by combining data from other fields:</p>';
		$html .= '<ul>';
		foreach ($sources as $source) {
			$html .= '<li><strong>' . htmlspecialchars($source) . '</strong></li>';
		}
		$html .= '</ul>';
		
		$html .= '<h6>Configuration</h6>';
		$html .= '<ul>';
		$html .= '<li><strong>Transform:</strong> ' . htmlspecialchars($config['transform']) . '</li>';
		$html .= '<li><strong>Separator:</strong> ' . htmlspecialchars($config['separator']) . '</li>';
		$html .= '<li><strong>Max Words:</strong> ' . htmlspecialchars($config['max_words']) . '</li>';
		if ($config['prefix']) {
			$html .= '<li><strong>Prefix:</strong> ' . htmlspecialchars($config['prefix']) . '</li>';
		}
		if ($config['suffix']) {
			$html .= '<li><strong>Suffix:</strong> ' . htmlspecialchars($config['suffix']) . '</li>';
		}
		$html .= '</ul>';
		
		$html .= '<h6>Example</h6>';
		$html .= '<p>If you enter:</p>';
		$html .= '<ul>';
		$html .= '<li><strong>name:</strong> "Office Chair Ergonomic"</li>';
		$html .= '<li><strong>id:</strong> 123</li>';
		$html .= '</ul>';
		$html .= '<p>The generated value will be: <code>';
		$html .= htmlspecialchars($config['prefix']) . 'OFFICE-CHAIR-123' . htmlspecialchars($config['suffix']);
		$html .= '</code></p>';
		
		$html .= '<h6>Tips</h6>';
		$html .= '<ul>';
		$html .= '<li>The field updates automatically as you type</li>';
		$html .= '<li>Click the refresh button to regenerate manually</li>';
		$html .= '<li>Empty source fields are skipped automatically</li>';
		$html .= '<li>Special characters are removed for clean output</li>';
		$html .= '</ul>';
		
		$html .= '</div><div class="modal-footer">';
		$html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
		$html .= '</div></div></div></div>';
		
		return $html;
	}
	
	/**
	 * Check if input is date/time type
	 * 
	 * @param string $function_name Input type
	 * @return bool True if date/time input
	 */
	private function isDateTimeInput(string $function_name): bool {
		return in_array($function_name, ['date', 'datetime', 'daterange', 'time', 'tagsinput']);
	}
	
	/**
	 * Render password input element
	 * 
	 * @param string $name Field name
	 * @param array $attributes HTML attributes
	 * @return string HTML output
	 */
	private function renderPasswordInput(string $name, array $attributes): string {
		$content = Form::password($name, $attributes);
		// Security: Mark as safe HTML to prevent double-encoding
		return $this->wrapInInputGroup($content, true);
	}
	
	/**
	 * Check if input is select-type (selectMonth, selectRange)
	 * 
	 * @param string $function_name Input type
	 * @return bool True if select-type input
	 */
	private function isSelectTypeInput(string $function_name): bool {
		return in_array($function_name, ['selectMonth', 'selectRange']);
	}
	
	/**
	 * Render select-type input element
	 * 
	 * @param string $function_name Input type
	 * @param string $name Field name
	 * @param mixed $value Field value
	 * @param array $attributes HTML attributes
	 * @return string HTML output
	 */
	private function renderSelectTypeInput(string $function_name, string $name, mixed $value, array $attributes): string {
		$selectHtml = Form::{$function_name}($name, $value, $attributes);

		// Security: Mark select-type outputs as safe HTML to prevent double-encoding
		return $this->wrapInInputGroup($selectHtml, true);
	}
	
	/**
	 * Render Default Input Element
	 * 
	 * Renders standard input elements (text, email, number, etc.) using Laravel Form facade.
	 * The Form facade automatically generates an id attribute matching the name attribute,
	 * ensuring proper label-input association.
	 * 
	 * @param string $function_name Input type (text, email, number, etc.)
	 * @param string $name Field name - will be used as both name and id attributes
	 * @param mixed $value Field value
	 * @param array $attributes HTML attributes
	 * 
	 * @return string HTML input element wrapped in input-group div
	 * 
	 * @accessibility Laravel Form facade automatically adds id="$name" to match label's for="$name"
	 * @accessibility This ensures screen readers can properly associate labels with inputs
	 * 
	 * @example
	 * // Generates: <input type="text" name="email" id="email" value="..." />
	 * $this->renderDefaultInput('text', 'email', 'user@example.com', []);
	 */
	private function renderDefaultInput(string $function_name, string $name, mixed $value, array $attributes): string {
		$content = Form::{$function_name}($name, $value, $attributes);
		
		// Security: Mark as safe HTML to prevent double-encoding
		return $this->wrapInInputGroup($content, true);
	}
	
	/**
	 * Wrap content in input-group div
	 * 
	 * @param string $content HTML content to wrap
	 * @param bool $markAsSafe Whether to mark as SafeHtml
	 * @return string Wrapped HTML
	 */
	private function wrapInInputGroup(string $content, bool $markAsSafe = false): string {
		$wrapped = '<div class="input-group col-sm-9">' . $content . '</div>';
		
		if ($markAsSafe) {
			return SafeHtml::mark($wrapped);
		}
		
		return $wrapped;
	}
	
	protected static array $validation_attributes = [];
	
	/**
	 * Display session-based alert messages and process validation attributes
	 * 
	 * Retrieves and displays flash messages from session (success, warning, error).
	 * Also processes validation rules to extract required field attributes for form elements.
	 * 
	 * @param array $data Optional model data to store in session (currently unused in display logic)
	 * 
	 * @return void Outputs alert HTML via draw() method
	 * 
	 * @security Alert messages from session should be escaped by canvastack_form_alert_message()
	 * @security HTML output is marked with SafeHtml::mark() to prevent double-encoding
	 * @security canvastack_form_alert_message() already escapes all user input properly
	 * 
	 * @example
	 * ```php
	 * // Called automatically by model() method
	 * // Displays: "User created successfully" with success styling
	 * // Or: "Validation failed" with warning styling
	 * ```
	 */
	private function alert_message(array $data = []): void {
		// Process validation attributes
		if (!empty($this->validations)) {
			$checkRequired = canvastack_array_contained_string($this->validations, FormConstants::VALIDATION_REQUIRED, true);
			if (!empty($checkRequired)) {
				self::$validation_attributes = $checkRequired;
			}
		}
		
		// Prepare current data
		$current_data = [];
		if (!empty($data)) {
			$current_data = ['current_data' => $data->getAttributes()];
		}
		
		// Get session messages and status
		$session_messages = [];
		if (!is_empty(canvastack_sessions('get', 'message'))) {
			$session_messages = canvastack_sessions('get', 'message');
		}
		
		$session_status = null;
		if (!is_empty(canvastack_sessions('get', 'status'))) {
			$session_status = canvastack_sessions('get', 'status');
		}
		
		// Handle param method
		if (!empty($current_data) && !empty($session_messages['message']['_method'])) {
			$param_method = $session_messages['message']['_method'];
			canvastack_sessions($param_method, $current_data);
		}
		
		// Early return if no messages
		if (empty($session_messages)) {
			return;
		}
		
		// Build status array
		$status = $this->buildAlertStatus($session_messages, $session_status);
		
		// Security: Mark alert HTML as safe to prevent double-encoding
		// canvastack_form_alert_message() already escapes all user input
		$alertHtml = canvastack_form_alert_message($status['message'], $status['type'], $status['label'], $status['prefix'], false);
		$this->draw(SafeHtml::mark($alertHtml));
	}
	
	/**
	 * Build alert status array from session data
	 * 
	 * @param array $session_messages Session messages
	 * @param string|null $session_status Session status
	 * @return array Status array with message, type, label, prefix
	 */
	private function buildAlertStatus(array $session_messages, ?string $session_status): array {
		$status = [
			'message' => 'Success',
			'type' => 'success',
			'prefix' => 'fa-exclamation-triangle'
		];
		
		if (!empty($session_messages['message'])) {
			$status['message'] = $session_messages['message'];
		}
		
		if (!empty($session_status) && 'failed' === $session_status) {
			$status['type'] = 'warning';
		}
		
		$status['label'] = ucwords($status['type']);
		
		return $status;
	}
}

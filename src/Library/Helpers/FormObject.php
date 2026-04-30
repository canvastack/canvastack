<?php
use Collective\Html\FormFacade;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

/**
 * Created on 16 Mar 2021
 * Time Created	: 03:17:49
 *
 * @filesource	FormObject.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

if (!function_exists('canvastack_form_escape_html')) {
	/**
	 * Escape HTML to prevent XSS attacks
	 * 
	 * Handles various input types safely:
	 * - Strings: Escaped using htmlspecialchars
	 * - Arrays: Each element is recursively escaped
	 * - Null/false: Returns empty string
	 * - Objects: Converted to string if possible, otherwise returns empty string
	 * 
	 * @param mixed $string String to escape (accepts string, array, null, false, or any type)
	 * @return string|array Escaped HTML string or array of escaped strings
	 * 
	 * @security This function is the primary defense against XSS attacks
	 * @security Arrays are recursively escaped to handle nested structures
	 * @security Objects are safely converted to strings or rejected
	 */
	function canvastack_form_escape_html($string): string|array {
		// Handle null and false
		if (is_null($string) || false === $string) {
			return '';
		}
		
		// Handle arrays recursively
		if (is_array($string)) {
			return array_map('canvastack_form_escape_html', $string);
		}
		
		// Handle objects with __toString method
		if (is_object($string)) {
			if (method_exists($string, '__toString')) {
				$string = (string)$string;
			} else {
				// Cannot convert object to string safely
				return '';
			}
		}
		
		// Convert to string and escape
		return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('canvastack_form_validate_ip')) {
	/**
	 * Validate and sanitize IP address
	 * Prevents IP spoofing by only trusting REMOTE_ADDR
	 * 
	 * @param string $ip IP address to validate
	 * @return string|false Valid IP address or false if invalid
	 */
	function canvastack_form_validate_ip(string $ip): string|false {
		// Remove any whitespace
		$ip = trim($ip);
		
		// Validate IP format
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			return $ip;
		}
		
		return false;
	}
}

if (!function_exists('canvastack_form_check_str_attr')) {
	
	/**
	 * Check String Contains In Attribute
	 *
	 * Checks if a string exists in the 'class' or 'id' attribute of an element.
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 * updated @Mar 31, 2026 - Added type hints
	 * 
	 * @param array $attributes Associative array of HTML attributes
	 * @param string $string String to search for in class or id
	 * 
	 * @return bool True if string found in class or id, false otherwise
	 */
	function canvastack_form_check_str_attr(array $attributes, string $string): bool {
		return (isset($attributes['class']) && str_contains($attributes['class'], $string)) ||
		       (isset($attributes['id']) && str_contains($attributes['id'], $string));
	}
}

if (!function_exists('canvastack_form_button')) {
	
	/**
	 * Button Builder
	 *
	 * Generates a styled button or link element with optional icon and various styling options.
	 * All user-facing values are automatically escaped for XSS protection.
	 *
	 * @param string $name Button type: default|primary|info|success|warning|danger|inverse|link
	 *                     Also supports background colors: pink|purple|yellow|grey|light
	 * @param string|false $label Button label text (false for no label)
	 * @param array $action Additional HTML attributes as key-value pairs (e.g., ['data-id' => '123'])
	 * @param string $tag HTML tag to use: 'button' or 'a'
	 * @param string|array|false $link URL for link buttons (string: '#url' or array: ['href' => '#url'])
	 * @param string|false $color Background color: pink|purple|yellow|grey|light|white (can be mixed: 'btn-white btn-yellow')
	 * @param string|false $border Border style: bold|round
	 * @param string|false $size Button size: minier|xs|sm|lg
	 * @param bool|false $disabled Whether button is disabled
	 * @param string|false $icon_name Font Awesome icon name (e.g., 'check', 'pencil', 'trash')
	 * @param string|false $icon_color Icon color: pink|purple|yellow|grey|light (ignored if no icon)
	 *
	 * @return string HTML button element
	 * 
	 * @example
	 * // Simple button
	 * canvastack_form_button('primary', 'Save');
	 * 
	 * // Button with icon
	 * canvastack_form_button('success', 'Submit', [], 'button', false, 'white', false, false, false, 'check', 'success');
	 * 
	 * // Link button
	 * canvastack_form_button('primary', 'View', [], 'a', '/users/1');
	 */
	function canvastack_form_button(
		string $name,
		string|false $label = false,
		array $action = [],
		string $tag = 'button',
		string|array|false $link = false,
		string|false $color = 'white',
		string|false $border = false,
		string|false $size = false,
		bool $disabled = false,
		string|false $icon_name = false,
		string|false $icon_color = false
	): string {
		// Security: Escape all user-controllable inputs
		$name = canvastack_form_escape_html($name);
		$label = canvastack_form_escape_html($label);
		$icon_name = canvastack_form_escape_html($icon_name);
		$icon_color = canvastack_form_escape_html($icon_color);
		$color = canvastack_form_escape_html($color);
		$border = canvastack_form_escape_html($border);
		$size = canvastack_form_escape_html($size);
				
		$url = false;
		if (false !== $link) {
			if (is_array($link)) {
				$keyLink = key($link);
				$urlLink = $link[$keyLink];
				
				// Security: Escape attribute name and value
				$keyLink = canvastack_form_escape_html($keyLink);
				$urlLink = canvastack_form_escape_html($urlLink);
				
				$url = " {$keyLink}=\"{$urlLink}\"";
			} else {
				// Security: Escape URL
				$link = canvastack_form_escape_html($link);
				$url = ' href="' . $link . '"';
			}
		}
		
		$buttonColor = false;
		if (false !== $color && '' !== $color)  $buttonColor = " btn-{$color}";
		
		$buttonTag = $tag;
		if (false === $tag)  $buttonTag = 'button';
		
		$buttonBorder = false;
		if (false !== $border && '' !== $border) $buttonBorder = " btn-{$border}";
		
		$buttonDisabled = false;
		$ariaDisabled = '';
		if (false !== $disabled) {
			$buttonDisabled = ' disabled';
			$ariaDisabled = ' aria-disabled="true"';
		}
		
		$icon     = false;
		$iconName = false;
		if (false !== $icon_name && '' !== $icon_name) {
			$iconColor	= false;
			if (false !== $icon_color && '' !== $icon_color) $iconColor = " {$icon_color}";
			$iconName = $icon_name;
			$icon     = '<i class="fa fa-' . $iconName . ' bigger-120' . $iconColor . '" aria-hidden="true"></i>&nbsp; ';
		}
		
		// Accessibility: Add aria-label for icon-only buttons
		$ariaLabel = '';
		if ((false === $label || '' === $label) && false !== $icon_name && '' !== $icon_name) {
			$ariaLabel = ' aria-label="' . ucfirst(str_replace('-', ' ', $iconName)) . ' button"';
		}
		
		$actions = [];
		if (count($action) >= 1) {
			foreach ($action as $key => $val) {
				// Security: Escape action attributes
				$key = canvastack_form_escape_html($key);
				$val = canvastack_form_escape_html($val);
				$actions[$key] = " {$key} = '{$val}' ";
			}
			$actionElm = implode(' ', $actions);
		} else {
			$actionElm = false;
		}
		
		$button = '<' . $buttonTag . $url . ' class="btn ' . $buttonColor . ' btn-' . $name . $buttonBorder . $buttonDisabled . '"' . $ariaLabel . $ariaDisabled . ' ' . $actionElm . '>';
		if (false !== $icon)  $button .= $icon;
		if (false !== $label && '' !== $label) $button .= $label;
		$button .= '</' . $buttonTag . '>';
		
		return $button;
	}
}

if (!function_exists('canvastack_form_change_input_attribute')) {
	
	/**
	 * Change/Add Input Class Name Attribute
	 * 
	 * Merges or updates a specific attribute in an attributes array.
	 * If the attribute already exists and is an array, values are joined with space.
	 * 
	 * @param array $attribute Existing attributes array
	 * @param string|false $key Attribute key to add/update (false to skip)
	 * @param mixed $value Attribute value to set
	 * 
	 * @return array Updated attributes array
	 */
	function canvastack_form_change_input_attribute(array $attribute, string|false $key = false, mixed $value = false): array {
		if (false === $key) {
			return $attribute;
		}
		
		$new_attribute = [$key => $value];
		$attributes = array_merge_recursive($attribute, $new_attribute);
		
		// Get the merged value for the key
		$_values = $attributes[$key] ?? null;
		
		if (is_array($_values)) {
			$values = implode(' ', $_values);
		} else {
			$values = $_values ?? '';
		}
		
		$_attribute = [$key => $values];
		$attribute = array_merge($attribute, $_attribute);
		
		return $attribute;
	}
}

if (!function_exists('canvastack_form_set_icon_attributes')) {
	
	/**
	 * Set Icon Attribute for Inputbox
	 *
	 * Parses icon configuration from pipe-delimited string format.
	 * Format: "name|icon|position" where icon and position are optional.
	 *
	 * @param string $string Icon configuration string (e.g., "username|user|left")
	 * @param array $attributes Additional attributes to merge
	 * @param string $pos Default icon position (left or right)
	 *
	 * @return object Object with 'name' and 'attr' properties
	 */
	function canvastack_form_set_icon_attributes(string $string, array $attributes = [], string $pos = 'left'): object {
		$data = [];
		$data['attr'] = [];
		$data['name'] = $string;
		$str_icon = false;
		$str_pos = $pos;
		
		if (str_contains($string, FormConstants::ICON_DELIMITER)) {
			$_string = explode(FormConstants::ICON_DELIMITER, $string);
			$data['name'] = $_string[0] ?? $string;
			$str_icon = $_string[1] ?? false;
			
			if (count($_string) >= 3) {
				$str_pos = $_string[2];
			}
			
			if (false !== $str_icon) {
				$_attr = array_merge_recursive($attributes, ['input_icon' => $str_icon]);
				$data['attr'] = array_merge_recursive($_attr, ['icon_position' => $str_pos]);
			}
		}
		
		return canvastack_object($data);
	}
}

if (!function_exists('canvastack_form_active_box')) {
	
	/**
	 * Active Status Combobox Value
	 *
	 * Generates options array for active/inactive status dropdown.
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 * updated @Mar 31, 2026 - Added type hints
	 *
	 * @param bool $en True for English labels, false for Indonesian labels
	 * @return array Options array [null => '', 0 => 'No/Tidak Aktif', 1 => 'Yes/Aktif']
	 */
	function canvastack_form_active_box(bool $en = true): array {
		if ($en) {
			return [null => ''] + ['No', 'Yes'];
		} else {
			return [null => ''] + ['Tidak Aktif', 'Aktif'];
		}
	}
}

if (!function_exists('canvastack_form_checkList')) {
	
	/**
	 * Simple Checkbox List Builder
	 * 
	 * Generates a styled checkbox with label wrapped in a div container.
	 * All user-facing values (name, value, label) are automatically escaped for XSS protection.
	 *
	 * @param mixed $name Checkbox name attribute (will be escaped)
	 * @param string|false $value Checkbox value attribute (will be escaped)
	 * @param string|false $label Label text displayed next to checkbox (will be escaped)
	 * @param bool|false $checked Whether checkbox should be checked by default
	 * @param string $class Color class for checkbox styling (success, danger, warning, info, lilac, etc)
	 * @param string|false $id Checkbox ID attribute (will be escaped). If false, uses $name as ID
	 * @param string|null $inputNode Additional HTML attributes for the input element
	 *                               
	 *                               ⚠️ SECURITY WARNING: Must be from TRUSTED SOURCE only!
	 *                               ⚠️ NEVER pass user input directly to this parameter!
	 *                               ⚠️ Format: attribute="value" (e.g., class="custom-class")
	 *                               ⚠️ Event handlers (onclick, onload, etc) are blocked
	 *                               
	 *                               Validation applied:
	 *                               - Only alphanumeric, dash, underscore, quotes, dots, spaces allowed
	 *                               - Event handler attributes are blocked
	 *                               - Format must be: attribute="value" or attribute='value'
	 *
	 * @return string Safe HTML checkbox element
	 * 
	 * @throws \InvalidArgumentException If inputNode contains invalid characters or dangerous attributes
	 * 
	 * @example
	 * // Basic usage
	 * $checkbox = canvastack_form_checkList('agree', '1', 'I Agree', true, 'success');
	 * 
	 * // With custom ID
	 * $checkbox = canvastack_form_checkList('terms', '1', 'Accept Terms', false, 'danger', 'terms_checkbox');
	 * 
	 * // ✅ SAFE - With trusted attributes
	 * $checkbox = canvastack_form_checkList('test', '1', 'Label', true, 'success', 'test_id', 'class="custom-class"');
	 * 
	 * // ❌ DANGEROUS - Never do this!
	 * $checkbox = canvastack_form_checkList('test', '1', 'Label', true, 'success', 'test_id', $_GET['attrs']);
	 * 
	 * // ❌ BLOCKED - Event handlers not allowed
	 * $checkbox = canvastack_form_checkList('test', '1', 'Label', true, 'success', 'test_id', 'onclick="alert(1)"');
	 * // Throws: InvalidArgumentException
	 */
	function canvastack_form_checkList(mixed $name, string|false $value = false, string|false $label = false, bool $checked = false, string $class = 'success', string|false $id = false, ?string $inputNode = null): string {
		return ThemeAdapterResolver::resolve()->renderCheckList($name, $value, $label, $checked, $class, $id, $inputNode);
	}
}

if (!function_exists('canvastack_form_selectbox')) {
	
	/**
	 * Generate select dropdown element
	 * 
	 * Uses Laravel Form facade to generate select element with Chosen.js styling.
	 * All values are automatically escaped by Laravel Form.
	 * 
	 * @param string $name Select name attribute
	 * @param array $values Options array [value => label]
	 * @param mixed $selected Selected value(s) - can be string, int, array, or false
	 * @param array $attributes HTML attributes for select element
	 * @param bool|false $label Whether to show label (deprecated, not used, accepts false for backward compatibility)
	 * @param array|false $set_first_value First option to prepend (default: [null => 'Select'], false to skip)
	 * 
	 * @return string Safe HTML select element (marked as safe)
	 */
	function canvastack_form_selectbox(string $name, array $values = [], mixed $selected = false, array $attributes = [], bool $label = true, array|bool $set_first_value = [null => 'Select']): string {
		return ThemeAdapterResolver::resolve()->renderSelectBox($name, $values, $selected, $attributes, $label, $set_first_value);
	}
}

if (!function_exists('canvastack_form_alert_message')) {
	
	/**
	 * Generate Bootstrap Alert Message
	 * 
	 * Creates a dismissable alert box with icon, title, and message content.
	 * Supports both simple string messages and complex array messages with field validation errors.
	 * 
	 * @param string|array $message Alert message content or array of field errors
	 * @param string $type Alert type (success, info, warning, danger)
	 * @param string $title Alert title text
	 * @param string $prefix Font Awesome icon class (e.g., 'fa-check', 'fa-warning')
	 * @param string|false $extra Additional HTML content to append (will be sanitized)
	 * 
	 * @return string HTML alert element
	 */
	function canvastack_form_alert_message(string|array $message = 'Success', string $type = 'success', string $title = 'Success', string $prefix = 'fa-check', string|false $extra = false): string {
		return ThemeAdapterResolver::resolve()->renderAlertMessage($message, $type, $title, $prefix, $extra);
	}
}

if (!function_exists('canvastack_form_create_header_tab')) {
	
	/**
	 * HTML Header Tab Builder
	 *
	 * Generates a Bootstrap tab header (nav-item) element.
	 *
	 * @param string $data Tab identifier/name (will be converted to display name)
	 * @param string $pointer Tab content ID to link to
	 * @param string|false $active CSS class for active state (e.g., 'active')
	 * @param string|false $class Icon CSS class (e.g., 'fa fa-user')
	 *
	 * @return string HTML tab header element
	 */
	function canvastack_form_create_header_tab(string $data, string $pointer, string|false $active = false, string|false $class = false): string {
		return ThemeAdapterResolver::resolve()->renderTabHeader($data, $pointer, $active, $class);
	}
}

if (!function_exists('canvastack_form_create_content_tab')) {
	
	/**
	 * HTML Content Tab Builder
	 *
	 * Generates a Bootstrap tab content pane element.
	 *
	 * @param string $data Tab content HTML (assumed to be safe/already processed)
	 * @param string $pointer Tab pane ID
	 * @param bool|false $active Whether this tab is active by default
	 *
	 * @return string HTML tab content element
	 */
	function canvastack_form_create_content_tab(string $data, string $pointer, bool $active = false): string {
		return ThemeAdapterResolver::resolve()->renderTabContent($data, $pointer, $active);
	}
}


if (!function_exists('canvastack_form_set_active_value')) {
	
	/**
	 * Set Active Value
	 *
	 * Converts numeric active status to Yes/No string.
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 * updated @Mar 31, 2026 - Added type hints
	 *
	 * @param mixed $value Active status value (1 = Yes, other = No)
	 *
	 * @return string 'Yes' if value is 1, 'No' otherwise
	 */
	function canvastack_form_set_active_value(mixed $value): string {
		return (int)$value === FormConstants::ACTIVE_STATUS_YES ? 'Yes' : 'No';
	}
}

if (!function_exists('canvastack_form_internal_flag_status')) {
	
	/**
	 * Set Flag Status Value
	 *
	 * Wrapper function for internal_flag_status() with type conversion.
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 * updated @Mar 31, 2026 - Added type hints
	 *
	 * @param mixed $flag_row Flag status value (will be converted to integer)
	 *
	 * @return string Flag status label
	 */
	function canvastack_form_internal_flag_status(mixed $flag_row): string {
		return internal_flag_status(intval($flag_row));
	}
}

if (!function_exists('canvastack_form_request_status')) {
	
	/**
	 * Request Status For Combobox Value
	 *
	 * Generates request status options array or returns specific status label.
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 * updated @Mar 31, 2026 - Added type hints
	 *
	 * @param bool $en True for English labels, false for Indonesian labels
	 * @param int|false $num Status index to return (false to return all options)
	 *
	 * @return array|string Array of all statuses or specific status string
	 */
	function canvastack_form_request_status(bool $en = true, int|false $num = false): array|string {
		$data = $en 
			? ['Pending', 'Accept', 'Blocked', 'Ban']
			: ['Pending', 'Terima', 'Block', 'Ban'];
		
		if (false === $num) {
			return $data;
		}
		
		return $data[$num] ?? $data[FormConstants::REQUEST_STATUS_PENDING];
	}
}

if (!function_exists('canvastack_form_get_client_ip')) {
	
	/**
	 * Get Client IP
	 *
	 * Security: Fixed to prevent IP spoofing attacks
	 * Only trusts REMOTE_ADDR which cannot be spoofed by client
	 * For proxy support, configure trusted proxies in environment
	 *
	 * @return string Client IP address or 'UNKNOWN' if cannot be determined
	 * 
	 * created @Dec 29, 2018
	 * updated @Mar 31, 2026 - Security fix + type hints
	 */
	function canvastack_form_get_client_ip(): string {
		// Security: Only trust REMOTE_ADDR by default (cannot be spoofed)
		$ipaddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
		
		// Handle IPv6 localhost
		if ('::1' === $ipaddress) {
			$ipaddress = '127.0.0.1';
		}
		
		// Optional: Support for trusted proxies (configure via environment)
		// Only use forwarded headers if behind a trusted proxy
		$trustedProxies = [];
		if (defined('TRUSTED_PROXIES')) {
			$trustedProxies = is_array(TRUSTED_PROXIES) ? TRUSTED_PROXIES : explode(',', TRUSTED_PROXIES);
		}
		
		// If current IP is a trusted proxy, check forwarded headers
		if (!empty($trustedProxies) && in_array($ipaddress, $trustedProxies)) {
			// Check X-Forwarded-For header (standard proxy header)
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$clientIp = trim($forwardedIps[0]); // First IP is the client
				
				// Validate the IP format
				$validatedIp = canvastack_form_validate_ip($clientIp);
				if (false !== $validatedIp) {
					$ipaddress = $validatedIp;
				}
			}
		}
		
		// Final validation
		$validatedIp = canvastack_form_validate_ip($ipaddress);
		return false !== $validatedIp ? $validatedIp : 'UNKNOWN';
	}
}

if (!function_exists('canvastack_selectbox')) {
	
	/**
	 * Set Default Combobox Data
	 *
	 * Converts an array/object of data into options array for select dropdown.
	 * Supports pipe-delimited label format for combining multiple fields.
	 *
	 * @param iterable $object Array or object collection to convert
	 * @param string $key_value Key name for option values
	 * @param string $key_label Key name for option labels (supports pipe-delimited: "field1|field2")
	 * @param bool $set_null_array Whether to add empty option at start
	 * 
	 * @return array Options array [value => label] with escaped labels
	 */
	function canvastack_selectbox(iterable $object, string $key_value, string $key_label, bool $set_null_array = true): array {
		$options = [0 => ''];
		if ($set_null_array) {
			$options[] = '';
		}
		
		$keyLabel = [];
		if (canvastack_string_contained($key_label, FormConstants::ICON_DELIMITER)) {
			$keyLabel = explode(FormConstants::ICON_DELIMITER, $key_label);
		} else {
			$keyLabel[] = $key_label;
		}
		
		foreach ($object as $row) {
			// Security: Check if keys exist before accessing
			if (!isset($row[$keyLabel[0]]) || !isset($row[$key_value])) {
				continue; // Skip invalid rows
			}
			
			$keyLabelValue = $row[$keyLabel[0]];
			
			// Combine multiple label fields if specified
			if (!empty($keyLabel[1]) && !empty($row[$keyLabel[1]])) {
				$keyLabelValue = $row[$keyLabel[0]] . ' - ' . $row[$keyLabel[1]];
			}
			
			// Security: Escape values for safe HTML rendering
			$options[$row[$key_value]] = canvastack_form_escape_html($keyLabelValue);
		}
		
		return $options;
	}
}

if (!function_exists('canvastack_form_validate_file_extension')) {
	/**
	 * Validate file extension against whitelist
	 * 
	 * Validates that a filename has an allowed extension from the provided whitelist.
	 * This is a critical security function to prevent malicious file uploads.
	 * 
	 * @param string $filename Filename to validate (e.g., "document.pdf", "image.jpg")
	 * @param array $allowedExtensions Array of allowed extensions without dots (e.g., ['jpg', 'png', 'pdf'])
	 * 
	 * @return bool True if extension is allowed, false otherwise
	 * 
	 * @throws \InvalidArgumentException If filename is empty or has no extension
	 * @throws \InvalidArgumentException If extension is not in the allowed list
	 * 
	 * @security CRITICAL - This function prevents malicious file uploads
	 * @security Always use a whitelist approach (never blacklist)
	 * @security Extensions are case-insensitive for user convenience
	 * @security Double extensions are handled (e.g., "file.php.jpg" is rejected if "php" not allowed)
	 * 
	 * @example
	 * // ✅ Valid usage
	 * canvastack_form_validate_file_extension('photo.jpg', ['jpg', 'png', 'gif']);
	 * 
	 * // ❌ Will throw exception - executable file
	 * canvastack_form_validate_file_extension('malware.exe', ['jpg', 'png']);
	 * 
	 * // ❌ Will throw exception - double extension attack
	 * canvastack_form_validate_file_extension('shell.php.jpg', ['jpg', 'png']);
	 */
	function canvastack_form_validate_file_extension(string $filename, array $allowedExtensions): bool {
		// Validate input
		if (empty($filename)) {
			throw new \InvalidArgumentException('Filename cannot be empty');
		}
		
		if (empty($allowedExtensions)) {
			throw new \InvalidArgumentException('Allowed extensions list cannot be empty');
		}
		
		// Get file extension (case-insensitive)
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		if (empty($extension)) {
			throw new \InvalidArgumentException('Filename must have an extension');
		}
		
		// Normalize allowed extensions to lowercase
		$allowedExtensions = array_map('strtolower', $allowedExtensions);
		
		// Check if extension is in whitelist
		if (!in_array($extension, $allowedExtensions, true)) {
			error_log(sprintf(
				'SECURITY WARNING: File extension "%s" not allowed. Filename: %s, Allowed: %s',
				$extension,
				$filename,
				implode(', ', $allowedExtensions)
			));
			throw new \InvalidArgumentException(sprintf(
				'File extension "%s" is not allowed. Allowed extensions: %s',
				$extension,
				implode(', ', $allowedExtensions)
			));
		}
		
		// Additional security: Check for double extensions (e.g., file.php.jpg)
		// Remove the last extension and check if there's another one
		$filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
		$secondExtension = strtolower(pathinfo($filenameWithoutExt, PATHINFO_EXTENSION));
		
		if (!empty($secondExtension)) {
			// There's a double extension - validate the second one too
			if (!in_array($secondExtension, $allowedExtensions, true)) {
				error_log(sprintf(
					'SECURITY WARNING: Double extension detected. Second extension "%s" not allowed. Filename: %s',
					$secondExtension,
					$filename
				));
				throw new \InvalidArgumentException(sprintf(
					'Double extension detected. Extension "%s" is not allowed. Filename: %s',
					$secondExtension,
					$filename
				));
			}
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_validate_path')) {
	/**
	 * Validate path to prevent directory traversal attacks
	 * 
	 * Validates that a file path does not attempt to escape the allowed base directory
	 * using directory traversal patterns like "../" or "..\".
	 * 
	 * @param string $path Path to validate (relative or absolute)
	 * @param string $baseDir Base directory that path must remain within (absolute path)
	 * 
	 * @return bool True if path is safe and within base directory
	 * 
	 * @throws \InvalidArgumentException If path or baseDir is empty
	 * @throws \Canvastack\Canvastack\Library\Exceptions\SecurityException If path traversal detected
	 * @throws \Canvastack\Canvastack\Library\Exceptions\SecurityException If resolved path is outside base directory
	 * 
	 * @security CRITICAL - Prevents directory traversal attacks
	 * @security Uses realpath() to resolve symbolic links and relative paths
	 * @security Validates final resolved path is within base directory
	 * @security Blocks common traversal patterns: ../, ..\, %2e%2e/, etc.
	 * 
	 * @example
	 * // ✅ Valid usage
	 * canvastack_form_validate_path('uploads/file.jpg', '/var/www/uploads');
	 * 
	 * // ❌ Will throw SecurityException - traversal attempt
	 * canvastack_form_validate_path('../../../etc/passwd', '/var/www/uploads');
	 * 
	 * // ❌ Will throw SecurityException - encoded traversal
	 * canvastack_form_validate_path('uploads/%2e%2e/config.php', '/var/www/uploads');
	 */
	function canvastack_form_validate_path(string $path, string $baseDir): bool {
		// Validate input
		if (empty($path)) {
			throw new \InvalidArgumentException('Path cannot be empty');
		}
		
		if (empty($baseDir)) {
			throw new \InvalidArgumentException('Base directory cannot be empty');
		}
		
		// Normalize base directory (resolve to absolute path)
		$baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
		$realBaseDir = realpath($baseDir);
		
		if (false === $realBaseDir) {
			throw new \InvalidArgumentException(sprintf('Base directory does not exist: %s', $baseDir));
		}
		
		// Check for null bytes (security vulnerability)
		if (str_contains($path, "\0")) {
			error_log(sprintf(
				'SECURITY WARNING: Null byte detected in path: %s',
				bin2hex($path)
			));
			
			throw new \InvalidArgumentException(
				'Invalid character (null byte) detected in path'
			);
		}
		
		// Check for obvious traversal patterns before resolving
		$dangerousPatterns = [
			'../',
			'..\\',
			'%2e%2e%2f',  // URL encoded ../
			'%2e%2e%5c',  // URL encoded ..\
			'%252e%252e%252f',  // Double URL encoded
			'..%2f',
			'..%5c',
		];
		
		$pathLower = strtolower($path);
		foreach ($dangerousPatterns as $pattern) {
			if (str_contains($pathLower, strtolower($pattern))) {
				error_log(sprintf(
					'SECURITY WARNING: Directory traversal pattern detected: %s in path: %s',
					$pattern,
					$path
				));
				
				// Create SecurityException (will be caught by caller)
				$exceptionClass = class_exists('\Canvastack\Canvastack\Library\Exceptions\SecurityException')
					? '\Canvastack\Canvastack\Library\Exceptions\SecurityException'
					: '\RuntimeException';
				
				throw new $exceptionClass(sprintf(
					'Directory traversal attempt detected in path: %s',
					$path
				));
			}
		}
		
		// Build full path
		$fullPath = $baseDir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
		
		// Resolve the path (handles symbolic links and relative paths)
		// Note: realpath() returns false if path doesn't exist
		// For new files, we need to check the directory instead
		try {
			$realPath = realpath($fullPath);
		} catch (\ValueError $e) {
			// realpath() throws ValueError if path contains null bytes
			throw new \InvalidArgumentException(
				'Invalid character (null byte) detected in path'
			);
		}
		
		if (false === $realPath) {
			// Path doesn't exist yet - validate the directory
			$directory = dirname($fullPath);
			$realPath = realpath($directory);
			
			if (false === $realPath) {
				error_log(sprintf(
					'SECURITY WARNING: Cannot resolve path directory: %s',
					$directory
				));
				
				$exceptionClass = class_exists('\Canvastack\Canvastack\Library\Exceptions\SecurityException')
					? '\Canvastack\Canvastack\Library\Exceptions\SecurityException'
					: '\RuntimeException';
				
				throw new $exceptionClass(sprintf(
					'Cannot resolve path directory: %s',
					$directory
				));
			}
			
			// Append the filename back
			$realPath = $realPath . DIRECTORY_SEPARATOR . basename($fullPath);
		}
		
		// Verify the resolved path is within the base directory
		if (!str_starts_with($realPath, $realBaseDir . DIRECTORY_SEPARATOR) && $realPath !== $realBaseDir) {
			error_log(sprintf(
				'SECURITY WARNING: Path escapes base directory. Path: %s, Base: %s, Resolved: %s',
				$path,
				$realBaseDir,
				$realPath
			));
			
			$exceptionClass = class_exists('\Canvastack\Canvastack\Library\Exceptions\SecurityException')
				? '\Canvastack\Canvastack\Library\Exceptions\SecurityException'
				: '\RuntimeException';
			
			throw new $exceptionClass(sprintf(
				'Path is outside allowed directory. Attempted path: %s',
				$path
			));
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_validate_sql_query')) {
	/**
	 * Validate SQL query for dangerous patterns
	 * 
	 * Checks for common SQL injection patterns and dangerous SQL commands.
	 * This is a defense-in-depth measure - queries should still use parameterized
	 * statements, but this provides an additional layer of protection.
	 * 
	 * @param string $query SQL query to validate
	 * @return bool True if query is safe, false if dangerous patterns detected
	 * 
	 * @throws InvalidArgumentException If dangerous SQL patterns detected
	 * 
	 * @security This function validates queries before encryption in sync()
	 * @security Blocks: UNION, multi-statement, comments, dangerous functions
	 * @security Does NOT replace parameterized queries - use both!
	 */
	function canvastack_form_validate_sql_query(string $query): bool {
		// Normalize query for pattern matching
		$normalizedQuery = strtoupper(trim($query));
		
		// Dangerous patterns that should never appear in sync() queries
		$dangerousPatterns = [
			// SQL injection patterns
			'/UNION\s+SELECT/i',           // UNION-based injection
			'/;\s*DROP/i',                 // Multi-statement attacks
			'/;\s*DELETE/i',               // Multi-statement attacks
			'/;\s*UPDATE/i',               // Multi-statement attacks
			'/;\s*INSERT/i',               // Multi-statement attacks
			'/;\s*TRUNCATE/i',             // Multi-statement attacks
			'/;\s*ALTER/i',                // Schema modification
			'/;\s*CREATE/i',               // Schema modification
			'/--/',                        // SQL comments
			'/\/\*/',                      // Multi-line comments
			'/\*\//',                      // Multi-line comments
			'/xp_/',                       // SQL Server extended procedures
			'/sp_/',                       // SQL Server stored procedures
			
			// Dangerous functions
			'/LOAD_FILE\s*\(/i',           // File reading
			'/INTO\s+OUTFILE/i',           // File writing
			'/INTO\s+DUMPFILE/i',          // File writing
			'/BENCHMARK\s*\(/i',           // DoS attacks
			'/SLEEP\s*\(/i',               // Time-based attacks
			'/WAITFOR\s+DELAY/i',          // Time-based attacks (SQL Server)
			
			// Information disclosure
			'/INFORMATION_SCHEMA/i',       // Schema enumeration
			'/SHOW\s+TABLES/i',            // Schema enumeration
			'/SHOW\s+DATABASES/i',         // Schema enumeration
			'/SHOW\s+COLUMNS/i',           // Schema enumeration
			'/DESCRIBE\s+/i',              // Schema enumeration
		];
		
		// Check for dangerous patterns
		foreach ($dangerousPatterns as $pattern) {
			if (preg_match($pattern, $query)) {
				// Log security event
				if (function_exists('canvastack_log_security_event')) {
					canvastack_log_security_event('sql_injection_attempt', [
						'query' => substr($query, 0, 200), // Log first 200 chars only
						'pattern' => $pattern,
						'ip' => canvastack_form_get_client_ip(),
					]);
				}
				
				throw new InvalidArgumentException(
					'Query contains dangerous SQL patterns and cannot be used in sync(). ' .
					'Please review your query for security issues.'
				);
			}
		}
		
		// Query must be a SELECT statement
		if (!str_starts_with($normalizedQuery, 'SELECT')) {
			throw new InvalidArgumentException(
				'Only SELECT queries are allowed in sync(). ' .
				'Received: ' . substr($query, 0, 50)
			);
		}
		
		// Query should not be empty or too short
		if (strlen(trim($query)) < 10) {
			throw new InvalidArgumentException(
				'Query is too short or empty. Minimum 10 characters required.'
			);
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_validate_field_name')) {
	/**
	 * Validate field name for sync() operations
	 * 
	 * Field names must match allowed patterns to prevent injection attacks.
	 * Allows: alphanumeric, underscores, dots (for table.column notation)
	 * 
	 * @param string $fieldName Field name to validate
	 * @param string $fieldType Type of field (for error messages: 'source', 'target', 'value', 'label')
	 * @return bool True if valid, false otherwise
	 * 
	 * @throws InvalidArgumentException If field name contains invalid characters
	 * 
	 * @security Prevents field name injection in sync() operations
	 * @security Allows only: a-z, A-Z, 0-9, underscore, dot
	 */
	function canvastack_form_validate_field_name(string $fieldName, string $fieldType = 'field'): bool {
		// Field name cannot be empty
		if (empty(trim($fieldName))) {
			throw new InvalidArgumentException(
				"Field name for {$fieldType} cannot be empty."
			);
		}
		
		// Field name must match allowed pattern
		// Allows: alphanumeric, underscore, dot (for table.column)
		// Does not allow: spaces, special chars, SQL keywords
		if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $fieldName)) {
			// Log security event
			if (function_exists('canvastack_log_security_event')) {
				canvastack_log_security_event('invalid_field_name', [
					'field_name' => $fieldName,
					'field_type' => $fieldType,
					'ip' => canvastack_form_get_client_ip(),
				]);
			}
			
			throw new InvalidArgumentException(
				"Invalid {$fieldType} field name: '{$fieldName}'. " .
				"Field names must contain only alphanumeric characters, underscores, and dots."
			);
		}
		
		// Field name should not be too long (prevent DoS)
		if (strlen($fieldName) > 255) {
			throw new InvalidArgumentException(
				"Field name for {$fieldType} is too long. Maximum 255 characters allowed."
			);
		}
		
		// Field name should not start with a number (SQL convention)
		if (preg_match('/^[0-9]/', $fieldName)) {
			throw new InvalidArgumentException(
				"Field name for {$fieldType} cannot start with a number: '{$fieldName}'"
			);
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_add_integrity_check')) {
	/**
	 * Add HMAC integrity check to encrypted data
	 * 
	 * Generates an HMAC signature for the encrypted data to detect tampering.
	 * The signature is appended to the encrypted data with a separator.
	 * 
	 * @param string $encryptedData Encrypted data
	 * @param string $key Secret key for HMAC (defaults to app key)
	 * @return string Encrypted data with HMAC signature
	 * 
	 * @security Prevents tampering with encrypted sync() data
	 * @security Uses HMAC-SHA256 for integrity verification
	 */
	function canvastack_form_add_integrity_check(string $encryptedData, ?string $key = null): string {
		// Use app key if no key provided
		if (null === $key) {
			$key = config('app.key');
		}
		
		// Generate HMAC signature
		$signature = hash_hmac('sha256', $encryptedData, $key);
		
		// Append signature with separator
		return $encryptedData . '::' . $signature;
	}
}

if (!function_exists('canvastack_form_verify_integrity')) {
	/**
	 * Verify HMAC integrity check on encrypted data
	 * 
	 * Verifies the HMAC signature on encrypted data to detect tampering.
	 * Returns the original encrypted data if valid, throws exception if invalid.
	 * 
	 * @param string $dataWithSignature Encrypted data with HMAC signature
	 * @param string $key Secret key for HMAC (defaults to app key)
	 * @return string Original encrypted data (without signature)
	 * 
	 * @throws InvalidArgumentException If signature is invalid or missing
	 * 
	 * @security Detects tampering with encrypted sync() data
	 * @security Uses timing-safe comparison to prevent timing attacks
	 */
	function canvastack_form_verify_integrity(string $dataWithSignature, ?string $key = null): string {
		// Use app key if no key provided
		if (null === $key) {
			$key = config('app.key');
		}
		
		// Split data and signature
		$parts = explode('::', $dataWithSignature);
		
		if (count($parts) !== 2) {
			// Log security event
			if (function_exists('canvastack_log_security_event')) {
				canvastack_log_security_event('integrity_check_missing', [
					'ip' => canvastack_form_get_client_ip(),
				]);
			}
			
			throw new InvalidArgumentException(
				'Data integrity signature is missing or malformed.'
			);
		}
		
		[$encryptedData, $providedSignature] = $parts;
		
		// Calculate expected signature
		$expectedSignature = hash_hmac('sha256', $encryptedData, $key);
		
		// Use timing-safe comparison to prevent timing attacks
		if (!hash_equals($expectedSignature, $providedSignature)) {
			// Log security event
			if (function_exists('canvastack_log_security_event')) {
				canvastack_log_security_event('integrity_check_failed', [
					'ip' => canvastack_form_get_client_ip(),
				]);
			}
			
			throw new InvalidArgumentException(
				'Data integrity check failed. The data may have been tampered with.'
			);
		}
		
		return $encryptedData;
	}
}

if (!function_exists('canvastack_log_security_event')) {
	/**
	 * Log security-related events for monitoring
	 * 
	 * Logs security events to Laravel's log system with context data.
	 * Events are logged at WARNING level for security monitoring.
	 * 
	 * @param string $eventType Type of security event
	 * @param array $context Additional context data
	 * @return void
	 * 
	 * @security Central logging for all form security events
	 */
	function canvastack_log_security_event(string $eventType, array $context = []): void {
		// Add timestamp and user info
		$context['timestamp'] = now()->toIso8601String();
		$context['user_id'] = auth()->id() ?? 'guest';
		$context['url'] = request()->fullUrl();
		$context['user_agent'] = request()->userAgent();
		
		// Log to Laravel's log system
		\Illuminate\Support\Facades\Log::warning(
			"Form Security Event: {$eventType}",
			$context
		);
	}
}


if (!function_exists('canvastack_form_validate_file_extension')) {
	/**
	 * Validate file extension against whitelist
	 * 
	 * Validates that a filename has an allowed extension from the provided whitelist.
	 * This is a critical security function to prevent executable file uploads.
	 * 
	 * @param string $filename Filename to validate (e.g., "document.pdf")
	 * @param array $allowedExtensions Array of allowed extensions (e.g., ['jpg', 'png', 'pdf'])
	 * @return bool True if extension is allowed
	 * 
	 * @throws \InvalidArgumentException If extension is not allowed
	 * 
	 * @security CRITICAL - Prevents malicious file uploads (.php, .exe, .sh, etc.)
	 * @security Always use whitelist approach, never blacklist
	 * 
	 * @example
	 * canvastack_form_validate_file_extension('photo.jpg', ['jpg', 'png', 'gif']); // Returns true
	 * canvastack_form_validate_file_extension('malware.php', ['jpg', 'png']); // Throws exception
	 */
	function canvastack_form_validate_file_extension(string $filename, array $allowedExtensions): bool {
		// Get file extension
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		// Normalize allowed extensions to lowercase
		$allowedExtensions = array_map('strtolower', $allowedExtensions);
		
		// Check if extension is in whitelist
		if (!in_array($extension, $allowedExtensions, true)) {
			throw new \InvalidArgumentException(
				"File extension '.{$extension}' is not allowed. Allowed extensions: " . 
				implode(', ', $allowedExtensions)
			);
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_validate_path')) {
	/**
	 * Validate path to prevent directory traversal attacks
	 * 
	 * Ensures that a file path stays within the allowed base directory.
	 * Detects and blocks path traversal attempts using ../ or ..\
	 * 
	 * @param string $path Path to validate (e.g., "uploads/file.jpg")
	 * @param string $baseDir Base directory that path must stay within (e.g., "/var/www/uploads")
	 * @return bool True if path is safe
	 * 
	 * @throws \InvalidArgumentException If path contains null bytes
	 * @throws \Exception If path traversal detected
	 * 
	 * @security CRITICAL - Prevents access to files outside allowed directories
	 * @security Blocks attempts to read /etc/passwd, config files, .env, etc.
	 * 
	 * @example
	 * canvastack_form_validate_path('uploads/photo.jpg', '/var/www/uploads'); // Returns true
	 * canvastack_form_validate_path('../../../etc/passwd', '/var/www/uploads'); // Throws exception
	 */
	function canvastack_form_validate_path(string $path, string $baseDir): bool {
		// Check for null bytes (can truncate strings in some contexts)
		if (strpos($path, "\0") !== false) {
			throw new \InvalidArgumentException('Path contains null byte - potential security attack');
		}
		
		// Check for obvious traversal patterns
		if (strpos($path, '../') !== false || strpos($path, '..\\') !== false) {
			throw new \Exception('Path traversal detected: ' . $path);
		}
		
		// Resolve real path (follows symlinks, resolves ./ and ../)
		$realPath = realpath($baseDir . DIRECTORY_SEPARATOR . $path);
		$realBaseDir = realpath($baseDir);
		
		// If realpath returns false, path doesn't exist yet (which is OK for uploads)
		// In that case, manually check the path structure
		if (false === $realPath) {
			// Normalize path separators
			$normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
			$normalizedBase = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir);
			
			// Build full path
			$fullPath = $normalizedBase . DIRECTORY_SEPARATOR . $normalizedPath;
			
			// Check if it starts with base directory
			if (strpos($fullPath, $normalizedBase) !== 0) {
				throw new \Exception('Path traversal detected (path outside base directory): ' . $path);
			}
			
			return true;
		}
		
		// Verify that resolved path is within base directory
		if (strpos($realPath, $realBaseDir) !== 0) {
			throw new \Exception('Path traversal detected (resolved path outside base directory): ' . $path);
		}
		
		return true;
	}
}

if (!function_exists('canvastack_form_validate_attributes')) {
	/**
	 * Validate HTML attributes to prevent injection attacks
	 * 
	 * Blocks dangerous attributes that could execute JavaScript:
	 * - Event handlers (onclick, onerror, onload, etc.)
	 * - JavaScript protocol in URLs (javascript:)
	 * - Data URIs with scripts
	 * - Dangerous CSS expressions
	 * 
	 * @param array $attributes Attributes array to validate
	 * @return array Validated attributes (same as input if all safe)
	 * 
	 * @throws \InvalidArgumentException If dangerous attributes found
	 * 
	 * @security CRITICAL - Prevents XSS via attribute injection
	 * @security Blocks event handlers, javascript: protocol, data URIs, CSS expressions
	 * 
	 * @example
	 * canvastack_form_validate_attributes(['class' => 'btn']); // Returns ['class' => 'btn']
	 * canvastack_form_validate_attributes(['onclick' => 'alert(1)']); // Throws exception
	 */
	function canvastack_form_validate_attributes(array $attributes): array {
		// List of dangerous event handler attributes
		$dangerousEvents = [
			'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove',
			'onmouseout', 'onmouseenter', 'onmouseleave', 'onload', 'onunload', 'onchange',
			'onsubmit', 'onreset', 'onselect', 'onblur', 'onfocus', 'onkeydown', 'onkeypress',
			'onkeyup', 'onerror', 'onabort', 'oncanplay', 'oncanplaythrough', 'ondurationchange',
			'onemptied', 'onended', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onpause',
			'onplay', 'onplaying', 'onprogress', 'onratechange', 'onseeked', 'onseeking',
			'onstalled', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting'
		];
		
		// Check for dangerous event handlers
		foreach ($attributes as $key => $value) {
			$keyLower = strtolower($key);
			
			// SECURITY: Block attribute names containing quotes or equals
			// This prevents injection like: ['onclick="alert(1)"' => 'malicious']
			// which would create: onclick="alert(1)"="malicious"
			if (strpos($key, '"') !== false || strpos($key, "'") !== false || strpos($key, '=') !== false) {
				error_log(sprintf(
					'SECURITY WARNING: Attribute name contains dangerous characters (quotes or equals): %s',
					$key
				));
				throw new \InvalidArgumentException(
					"Attribute name contains invalid characters (quotes or equals not allowed): {$key}"
				);
			}
			
			// Block event handlers
			if (in_array($keyLower, $dangerousEvents, true)) {
				error_log(sprintf(
					'SECURITY WARNING: Dangerous event handler attribute blocked: %s',
					$key
				));
				throw new \InvalidArgumentException(
					"Dangerous event handler attribute not allowed: {$key}"
				);
			}
			
			// Block javascript: protocol in URL attributes (including data-* attributes)
			if (in_array($keyLower, ['href', 'src', 'data', 'action', 'formaction'], true) || 
			    str_starts_with($keyLower, 'data-')) {
				if (is_string($value) && stripos($value, 'javascript:') !== false) {
					throw new \InvalidArgumentException(
						"JavaScript protocol not allowed in {$key} attribute"
					);
				}
			}
			
			// Block dangerous data URIs
			if (in_array($keyLower, ['src', 'href'], true)) {
				if (is_string($value) && stripos($value, 'data:') === 0) {
					$valueLower = strtolower($value);
					// Check if data URI contains script or javascript
					// Also check for text/html MIME type which can execute scripts
					if (stripos($valueLower, 'script') !== false || 
					    stripos($valueLower, 'javascript') !== false ||
					    stripos($valueLower, 'text/html') !== false) {
						throw new \InvalidArgumentException(
							"Dangerous data URI not allowed in {$key} attribute"
						);
					}
				}
			}
			
			// Block dangerous CSS in style attribute
			if ($keyLower === 'style' && is_string($value)) {
				$valueLower = strtolower($value);
				// Remove spaces for better detection
				$valueNoSpaces = str_replace(' ', '', $valueLower);
				
				if (strpos($valueLower, 'expression(') !== false ||
				    strpos($valueLower, 'behavior:') !== false ||
				    strpos($valueLower, 'javascript:') !== false ||
				    strpos($valueNoSpaces, ':url(javascript:') !== false ||
				    strpos($valueNoSpaces, ':url("javascript:') !== false ||
				    strpos($valueNoSpaces, ":url('javascript:") !== false) {
					throw new \InvalidArgumentException(
						"Dangerous CSS expression not allowed in style attribute"
					);
				}
			}
		}
		
		return $attributes;
	}
}


if (!function_exists('canvastack_form_validate_field_name')) {
	/**
	 * Validate field name for SQL safety
	 * 
	 * Ensures field names contain only safe characters (alphanumeric, underscore, dot).
	 * Prevents SQL injection through field names.
	 * 
	 * @param string $fieldName Field name to validate
	 * @param string $fieldType Type of field (for error messages: 'source', 'target', 'values', 'labels')
	 * @return bool True if valid
	 * 
	 * @throws \InvalidArgumentException If field name contains dangerous characters
	 * 
	 * @security CRITICAL - Prevents SQL injection via field names
	 * @security Only allows: letters, numbers, underscore, dot
	 * @security Blocks: quotes, semicolons, spaces, special characters
	 * 
	 * @example
	 * canvastack_form_validate_field_name('user_id', 'source'); // Valid
	 * canvastack_form_validate_field_name('users.id', 'source'); // Valid (table.column)
	 * canvastack_form_validate_field_name("id'; DROP TABLE", 'source'); // Throws exception
	 */
	function canvastack_form_validate_field_name(string $fieldName, string $fieldType = 'field'): bool {
		// Field name cannot be empty
		if (empty(trim($fieldName))) {
			throw new \InvalidArgumentException(
				"Field name ({$fieldType}) cannot be empty"
			);
		}
		
		// Field name must contain only safe characters
		// Allow: letters, numbers, underscore, dot (for table.column notation)
		if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\.]*$/', $fieldName)) {
			error_log(sprintf(
				'SECURITY WARNING: Invalid field name (%s): %s',
				$fieldType,
				$fieldName
			));
			
			throw new \InvalidArgumentException(
				"Invalid {$fieldType} field name: '{$fieldName}'. " .
				"Only alphanumeric characters, underscore, and dot are allowed."
			);
		}
		
		// Field name should not be too long (prevent buffer overflow)
		if (strlen($fieldName) > 64) {
			throw new \InvalidArgumentException(
				"Field name ({$fieldType}) is too long. Maximum 64 characters allowed."
			);
		}
		
		// Block SQL keywords as field names (without table prefix)
		$sqlKeywords = [
			'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
			'TRUNCATE', 'UNION', 'WHERE', 'FROM', 'JOIN', 'EXEC', 'EXECUTE'
		];
		
		$upperFieldName = strtoupper($fieldName);
		foreach ($sqlKeywords as $keyword) {
			if ($upperFieldName === $keyword) {
				throw new \InvalidArgumentException(
					"Field name ({$fieldType}) cannot be a SQL keyword: '{$fieldName}'"
				);
			}
		}
		
		return true;
	}
}

if (!function_exists('canvastack_log_security_event')) {
	/**
	 * Log security-related events
	 * 
	 * Centralized logging for security events like SQL injection attempts,
	 * XSS attempts, path traversal, etc.
	 * 
	 * @param string $eventType Type of security event (e.g., 'sql_injection_attempt', 'xss_attempt')
	 * @param array $context Additional context data
	 * @return void
	 * 
	 * @security All security events should be logged for monitoring
	 * @security Logs include: timestamp, IP, user agent, event details
	 */
	function canvastack_log_security_event(string $eventType, array $context = []): void {
		// Add standard security context
		$securityContext = array_merge([
			'event_type' => $eventType,
			'timestamp' => date('Y-m-d H:i:s'),
			'ip' => canvastack_form_get_client_ip(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
			'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
			'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
		], $context);
		
		// Log to Laravel log
		\Log::warning("SECURITY EVENT: {$eventType}", $securityContext);
		
		// Optionally, send to external security monitoring service
		// if (config('security.monitoring.enabled')) {
		//     SecurityMonitor::alert($eventType, $securityContext);
		// }
	}
}

if (!function_exists('canvastack_form_add_integrity_check')) {
	/**
	 * Add integrity check to encrypted data
	 * 
	 * Adds HMAC signature to encrypted data to detect tampering.
	 * Format: encrypted_data|hmac_signature
	 * 
	 * @param string $encryptedData Encrypted data from Laravel encrypt()
	 * @return string Encrypted data with integrity check
	 * 
	 * @security CRITICAL - Prevents tampering with encrypted data
	 * @security Uses HMAC-SHA256 for integrity verification
	 * @security Signature is based on app key, so it's unique per application
	 * 
	 * @example
	 * $encrypted = encrypt('sensitive data');
	 * $withIntegrity = canvastack_form_add_integrity_check($encrypted);
	 * // Later, verify with canvastack_form_verify_integrity_check()
	 */
	function canvastack_form_add_integrity_check(string $encryptedData): string {
		// Get application key for HMAC
		$key = config('app.key');
		
		if (empty($key)) {
			throw new \RuntimeException('Application key not set. Cannot create integrity check.');
		}
		
		// Generate HMAC signature
		$signature = hash_hmac('sha256', $encryptedData, $key);
		
		// Combine encrypted data with signature
		// Format: encrypted_data|signature
		return $encryptedData . '|' . $signature;
	}
}

if (!function_exists('canvastack_form_verify_integrity_check')) {
	/**
	 * Verify integrity check on encrypted data
	 * 
	 * Verifies HMAC signature to detect tampering.
	 * Returns the original encrypted data if valid.
	 * 
	 * @param string $dataWithIntegrity Data with integrity check (from canvastack_form_add_integrity_check)
	 * @return string Original encrypted data (without signature)
	 * 
	 * @throws \InvalidArgumentException If integrity check fails
	 * 
	 * @security CRITICAL - Detects tampering with encrypted data
	 * @security Throws exception if signature doesn't match
	 * 
	 * @example
	 * $encryptedData = canvastack_form_verify_integrity_check($dataWithIntegrity);
	 * $decrypted = decrypt($encryptedData);
	 */
	function canvastack_form_verify_integrity_check(string $dataWithIntegrity): string {
		// Split data and signature
		$parts = explode('|', $dataWithIntegrity);
		
		if (count($parts) !== 2) {
			canvastack_log_security_event('integrity_check_failed', [
				'reason' => 'Invalid format',
				'data_length' => strlen($dataWithIntegrity),
			]);
			
			throw new \InvalidArgumentException(
				'Invalid data format. Integrity check failed.'
			);
		}
		
		[$encryptedData, $providedSignature] = $parts;
		
		// Get application key for HMAC
		$key = config('app.key');
		
		if (empty($key)) {
			throw new \RuntimeException('Application key not set. Cannot verify integrity check.');
		}
		
		// Calculate expected signature
		$expectedSignature = hash_hmac('sha256', $encryptedData, $key);
		
		// Compare signatures (timing-safe comparison)
		if (!hash_equals($expectedSignature, $providedSignature)) {
			canvastack_log_security_event('integrity_check_failed', [
				'reason' => 'Signature mismatch - possible tampering',
				'data_length' => strlen($encryptedData),
			]);
			
			throw new \InvalidArgumentException(
				'Integrity check failed. Data may have been tampered with.'
			);
		}
		
		return $encryptedData;
	}
}

if (!function_exists('canvastack_get_ajax_urli')) {
	/**
	 * Get AJAX URL for sync operations
	 * 
	 * Returns the URL for AJAX requests used in sync() method.
	 * 
	 * @return string AJAX URL
	 */
	function canvastack_get_ajax_urli(): string {
		// Try to get the route if it exists
		try {
			if (\Route::has('ajax.sync')) {
				return route('ajax.sync', [], false);
			}
		} catch (\Exception $e) {
			// Route doesn't exist, use fallback
		}
		
		// Fallback to a default AJAX URL
		return url('/ajax/sync');
	}
}


if (!function_exists('canvastack_script')) {
	/**
	 * Wrap JavaScript code in script tags
	 * 
	 * @param string $script JavaScript code
	 * @return string HTML script tag with JavaScript
	 * 
	 * @security JavaScript code should be from trusted sources only
	 */
	function canvastack_script(string $script): string {
		return "<script type=\"text/javascript\">\n{$script}\n</script>";
	}
}

if (!function_exists('canvastack_form_validate_xml')) {
	/**
	 * Safely parse and validate XML to prevent XML bomb attacks
	 * 
	 * Protects against:
	 * - Billion Laughs Attack (exponential entity expansion)
	 * - Quadratic Blowup Attack
	 * - External Entity Injection (XXE)
	 * - Large file DoS
	 * 
	 * @param string $xmlString XML content to parse
	 * @param int $maxSize Maximum allowed XML size in bytes (default: 1MB)
	 * @param int $maxDepth Maximum allowed XML depth (default: 100)
	 * 
	 * @return \SimpleXMLElement|false Parsed XML object or false on failure
	 * 
	 * @throws \InvalidArgumentException If XML is too large or contains dangerous patterns
	 * 
	 * @security CRITICAL - Prevents XML bomb DoS attacks
	 * @security Disables external entity loading (XXE prevention)
	 * @security Limits XML size and depth
	 * @security Detects entity expansion patterns
	 * 
	 * @example
	 * ```php
	 * try {
	 *     $xml = canvastack_form_validate_xml($userInput);
	 *     // Process $xml safely
	 * } catch (\InvalidArgumentException $e) {
	 *     // Handle malicious XML
	 * }
	 * ```
	 */
	function canvastack_form_validate_xml(string $xmlString, int $maxSize = 1048576, int $maxDepth = 100): \SimpleXMLElement|false {
		// Check XML size before parsing
		$xmlSize = strlen($xmlString);
		if ($xmlSize > $maxSize) {
			error_log(sprintf(
				'SECURITY WARNING: XML size exceeds limit. Size: %d bytes, Limit: %d bytes',
				$xmlSize,
				$maxSize
			));
			throw new \InvalidArgumentException(
				"XML size ({$xmlSize} bytes) exceeds maximum allowed size ({$maxSize} bytes)"
			);
		}
		
		// Check for entity declarations (potential bomb)
		if (preg_match('/<!ENTITY/i', $xmlString)) {
			error_log('SECURITY WARNING: XML contains ENTITY declarations (potential XML bomb)');
			throw new \InvalidArgumentException(
				'XML contains ENTITY declarations which are not allowed for security reasons'
			);
		}
		
		// Check for DOCTYPE declarations (can contain entities)
		if (preg_match('/<!DOCTYPE/i', $xmlString)) {
			error_log('SECURITY WARNING: XML contains DOCTYPE declaration (potential XXE attack)');
			throw new \InvalidArgumentException(
				'XML contains DOCTYPE declaration which is not allowed for security reasons'
			);
		}
		
		// Disable external entity loading (XXE prevention)
		$previousValue = libxml_disable_entity_loader(true);
		
		// Enable internal error handling
		$useErrors = libxml_use_internal_errors(true);
		
		try {
			// Parse XML with security flags
			// LIBXML_NOENT: Do not substitute entities
			// LIBXML_NONET: Disable network access
			// LIBXML_PARSEHUGE: Allow large documents (but we already checked size)
			$xml = simplexml_load_string(
				$xmlString,
				'SimpleXMLElement',
				LIBXML_NOENT | LIBXML_NONET
			);
			
			if (false === $xml) {
				// Get parsing errors
				$errors = libxml_get_errors();
				$errorMessages = array_map(function($error) {
					return trim($error->message);
				}, $errors);
				
				error_log(sprintf(
					'SECURITY WARNING: XML parsing failed. Errors: %s',
					implode(', ', $errorMessages)
				));
				
				libxml_clear_errors();
				
				throw new \InvalidArgumentException(
					'Invalid XML format: ' . implode(', ', $errorMessages)
				);
			}
			
			// Check XML depth (prevent deeply nested structures)
			$depth = canvastack_form_get_xml_depth($xml);
			if ($depth > $maxDepth) {
				error_log(sprintf(
					'SECURITY WARNING: XML depth exceeds limit. Depth: %d, Limit: %d',
					$depth,
					$maxDepth
				));
				throw new \InvalidArgumentException(
					"XML depth ({$depth}) exceeds maximum allowed depth ({$maxDepth})"
				);
			}
			
			return $xml;
			
		} finally {
			// Restore previous settings
			libxml_disable_entity_loader($previousValue);
			libxml_use_internal_errors($useErrors);
		}
	}
}

if (!function_exists('canvastack_form_get_xml_depth')) {
	/**
	 * Calculate the maximum depth of an XML structure
	 * 
	 * @param \SimpleXMLElement $xml XML element to measure
	 * @param int $currentDepth Current depth (used for recursion)
	 * 
	 * @return int Maximum depth of the XML structure
	 * 
	 * @internal Used by canvastack_form_validate_xml()
	 */
	function canvastack_form_get_xml_depth(\SimpleXMLElement $xml, int $currentDepth = 0): int {
		$maxDepth = $currentDepth;
		
		// Check all children
		foreach ($xml->children() as $child) {
			$childDepth = canvastack_form_get_xml_depth($child, $currentDepth + 1);
			if ($childDepth > $maxDepth) {
				$maxDepth = $childDepth;
			}
		}
		
		return $maxDepth;
	}
}

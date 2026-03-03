<?php
use Collective\Html\FormFacade;

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

if (!function_exists('canvas_form_check_str_attr')) {
	
	/**
	 * Check String Contains In Attribute
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 * 
	 * @param array $attributes
	 * @param string $string
	 * 
	 * @return boolean
	 */
	function canvas_form_check_str_attr($attributes, $string) {
		if ((isset($attributes['class']) && 
				true === str_contains($attributes['class'], $string)) || 
			isset($attributes['id']) && 
				true === str_contains($attributes['id'], $string)
		) {
			return true;
		}
	}
}

if (!function_exists('canvas_form_button')) {
	
	/**
	 * Button Builder
	 *
	 * @param string $name
	 * 		: default|primary|info|success|info|warning|danger|inverse|link
	 * 		  pink|purple|yellow|grey|light => this for background coloring
	 *
	 * @param string $label
	 * @param string $tag
	 * 		: button|a
	 *
	 * @param mixed  $link
	 * 		: string => '#link_url' | array ['href', '#link_url']
	 * @param string $color
	 * 		: pink|purple|yellow|grey|light
	 * 		: How to? *) it can be mixed like this 'btn-white btn-yellow'
	 *
	 * @param string $border
	 * 		: bold|round
	 *
	 * @param string $size
	 * 		: minier|xs|sm|lg
	 *
	 * @param string $disabled
	 * 		: disabled
	 *
	 * @param string $icon_name
	 * 		: check|pencil|trash|flag
	 * 		: All icon(s) name
	 *
	 * @param string $icon_color
	 * 		: pink|purple|yellow|grey|light
	 * 		: It would ignored when the $icon_name is false
	 *
	 * @return string
	 */
	function canvas_form_button (
		$name,
		$label      = false,
		$action     = [],
		$tag        = 'button',
		$link       = false,
		$color      = 'white',
		$border     = false,
		$size       = false,
		$disabled   = false,
		$icon_name  = false,
		$icon_color = false
	) {
				
		$url = false;
		if (false !== $link) {
			if (is_array($link)) {
				$keyLink = key($link);
				$urlLink = $link[$keyLink];
				
				$url = " {$keyLink}=\"{$urlLink}\"";
			} else {
				$url = ' href="' . $link . '"';
			}
		}
		
		$buttonColor = false;
		if (false !== $color)  $buttonColor = " btn-{$color}";
		
		$buttonTag = $tag;
		if (false === $tag)  $buttonTag = 'button';
		
		$buttonBorder = false;
		if (false !== $border) $buttonBorder = " btn-{$border}";
		
		$buttonDisabled = false;
		if (false !== $disabled) $buttonDisabled = ' disabled';
		
		$icon     = false;
		$iconName = false;
		if (false !== $icon_name) {
			$iconColor	= false;
			if (false !== $icon_color) $iconColor = " {$icon_color}";
			$iconName = $icon_name;
			$icon     = '<i class="fa fa-' . $iconName . ' bigger-120' . $iconColor . '"></i>&nbsp; ';
		}
		
		$actions = [];
		if (count($action) >= 1) {
			foreach ($action as $key => $val) {
				$actions[$key] = " {$key} = '{$val}' ";
			}
			$actionElm = implode(' ', $actions);
		} else {
			$actionElm = false;
		}
		
		$button = '<' . $buttonTag . $url . ' class="btn ' . $buttonColor . ' btn-' . $name . $buttonBorder . $buttonDisabled . '" ' . $actionElm . '>';
		if (false !== $icon)  $button .= $icon;
		if (false !== $label) $button .= $label;
		$button .= '</' . $buttonTag . '>';
		
		return $button;
	}
}

if (!function_exists('canvas_form_change_input_attribute')) {
	
	/**
	 * Change/Add Input Class Name Attribute
	 * 
	 * @param array $attribute
	 * @param boolean $key
	 * @param boolean $value
	 * 
	 * @return array
	 */
	function canvas_form_change_input_attribute($attribute, $key = false, $value = false) {
		$new_attribute	= [$key => $value];
		$attributes		= array_merge_recursive($attribute, $new_attribute);
		
		foreach ($attributes as $keys => $values) {
			if ($key === $keys) $_values = $values;
		}
		
		if (true === is_array($_values)) {
			$values = implode(' ', $_values);
		} else {
			$values = $_values;
		}
		
		$_attribute	= [$key => $values];
		$attribute	= array_merge($attribute, $_attribute);
		
		return $attribute;
	}
}

if (!function_exists('canvas_form_set_icon_attributes')) {
	
	/**
	 * Set Icon Attribute for Inputbox
	 *
	 * @param string $string
	 * @param string $attributes
	 *
	 * @return array ['name', 'data']
	 */
	function canvas_form_set_icon_attributes($string, $attributes = [], $pos = 'left') {
		$data         = [];
		$data['attr'] = [];
		$data['name'] = $string;
		$str_icon     = false;
		$str_pos      = $pos;
		
		if (true === str_contains($string, '|')) {
			$_string      = explode('|', $string);
			$data['name'] = $_string[0];
			$str_icon     = $_string[1];
			
			if (count($_string) >= 3) {
				$str_pos = $_string[2];
			}
			
			$_attr        = array_merge_recursive($attributes, ['input_icon' => $str_icon]);
			$data['attr'] = array_merge_recursive($_attr, ['icon_position' => $str_pos]);
		}
		
		return canvas_object($data);
	}
}

if (!function_exists('canvas_form_active_box')) {
	
	/**
	 * Active Status Combobox Value
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $en
	 * @return string['No', 'Yes']
	 */
	function canvas_form_active_box($en = true) {
		if (true === $en) {
			return [null => ''] + ['No', 'Yes'];
		} else {
			return [null => ''] + ['Tidak Aktif', 'Aktif'];
		}
	}
}

if (!function_exists('canvas_form_checkList')) {
	
	/**
	 * Simple Checkbox List Builder
	 *
	 * @param mixed $name
	 * @param string $value
	 * @param string $label
	 * @param string $checked
	 * @param string $class
	 * @param string $id
	 *
	 * @return string
	 */
	function canvas_form_checkList($name, $value = false, $label = false, $checked = false, $class = 'success', $id = false, $inputNode = null) {
		$nameAttr	= false;
		$valueAttr	= false;
		$idAttr		= false;
		$idForAttr	= false;
		$labelName	= '&nbsp;';
		$checkBox	= false;
		
		if (false !== $name)  $nameAttr  = ' name="' . $name . '"';
		if (false !== $value) $valueAttr = ' value="' . $value . '"';
		if (false !== $id)		{
			$idAttr    = ' id="' . $id . '"';
			$idForAttr = ' for="' . $id . '"';
		} else {
			$idAttr    = ' id="' . $name . '"';
			$idForAttr = ' for="' . $name . '"';
		}
		if (false !== $label)   $labelName = "&nbsp; {$label}";
		if (false !== $checked) $checkBox  = ' checked="checked"';
		if (!empty($inputNode)) $inputNode = " {$inputNode}";
		
		$o = "<div class=\"ckbox ckbox-{$class}\"><input type=\"checkbox\"{$valueAttr}{$nameAttr}{$idAttr}{$checkBox}{$inputNode}><label{$idForAttr}>{$labelName}</label></div>";
		
		return $o;
	}
}

if (!function_exists('canvas_form_selectbox')) {
	
	function canvas_form_selectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => 'Select']) {
		$default_attr = ['class' => 'chosen-select-deselect chosen-selectbox form-control'];
		if (!empty($attributes)) {
			$attributes = canvas_form_change_input_attribute($attributes, 'class', 'chosen-select-deselect chosen-selectbox form-control');
		} else {
			$attributes = $default_attr;
		}
		
		if (!empty($set_first_value)) {
			$values = array_merge_recursive($set_first_value, $values);
		}
		
		$selectbox = FormFacade::select($name, $values, $selected, $attributes);
		
		return $selectbox;
	}
}

if (!function_exists('canvas_form_alert_message')) {
	
	function canvas_form_alert_message($message = 'Success', $type = 'success', $title = 'Success', $prefix = 'fa-check', $extra = false) {
		$content_message = null;
		if (is_array($message) && 'success' !== $type) {
			$content_message = '<ul class="alert-info-content">';
			foreach ($message as $mfield => $mData) {
				$content_message .= '<li class="title"><div>';
				$content_message .= '<label for="' . $mfield . '" class="control-label">' . ucwords(str_replace('_', ' ', $mfield)) . '</label>';
				if (is_array($mData)) {
					$content_message .= '<ul class="content">';
					foreach ($mData as $imData) {
						$content_message .= '<li>';
						$content_message .= '<label for="' . $mfield . '" class="control-label">' . $imData . '</label>';
						$content_message .= '</li>';
					}
					$content_message .= '</ul>';
				} else {
					$content_message .= '<ul class="content"><li><label for="' . $mfield . '" class="control-label">' . $mData . '</label></li></ul>';
				}
				$content_message .= '</div></li>';
			}
			$content_message .= '</ul>';
		}
		
		$prefix_tag = false;
		if (false !== $prefix) $prefix_tag = "<strong><i class=\"fa {$prefix}\"></i> &nbsp;{$title}</strong>";
		
		$o  = "<div class=\"alert alert-block alert-{$type} animated fadeInDown alert-dismissable\" role=\"alert\">";
		$o .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">";
		$o .= "<i class=\"fa fa-times\"></i>";
		$o .= "</button>";
		if (!is_array($message)) {
			$o .= "<p>{$prefix_tag} {$content_message}</p>";
		} else {
			$o .= "<p>{$prefix_tag}</p>{$content_message}";
		}
		$o .= $extra;
		$o .= "</div>";
		
		return $o;
	}
}

if (!function_exists('canvas_form_create_header_tab')) {
	
	/**
	 * HTML Header Tab Builder
	 *
	 * @param string $data
	 * @param string $pointer
	 * @param string $active
	 * @param string $class
	 *
	 * @return string
	 */
	function canvas_form_create_header_tab($data, $pointer, $active = false, $class = false) {
		$activeClass = false;
		$classTag    = false;
		$tabName     = ucwords(str_replace('_', ' ', $data));
		
		if ($active) $activeClass	= '' . $active . '';
		if ($class)  $classTag		= '<i class="' . $class . '"></i>';
		
		$string = "<li class=\"nav-item\"><a class=\"nav-link {$activeClass}\" data-toggle=\"tab\" role=\"tab\" href=\"#{$pointer}\">{$classTag}{$tabName}</a></li>";
		
		return $string;
	}
}

if (!function_exists('canvas_form_create_content_tab')) {
	
	/**
	 * HTML Content Tab Builder
	 *
	 * @param string $data
	 * @param string $pointer
	 * @param string $active
	 *
	 * @return string
	 */
	function canvas_form_create_content_tab($data, $pointer, $active = false) {
		$activeClass = false;
		if (false !== $active) $activeClass = " active show";
		$string = "<div id=\"{$pointer}\" class=\"tab-pane fade{$activeClass}\" role=\"tabpanel\">{$data}</div>";
		
		return $string;
	}
}


if (!function_exists('canvas_form_set_active_value')) {
	
	/**
	 * Set Active Value
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param integer|string $value
	 *
	 * @return string
	 */
	function canvas_form_set_active_value($value) {
		$val = 'No';
		if (1 === $value) $val = 'Yes';
		
		return $val;
	}
}

if (!function_exists('canvas_form_internal_flag_status')) {
	
	/**
	 * Set Flag Status Value
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param integer|string $flag_row
	 *
	 * @return string
	 */
	function canvas_form_internal_flag_status($flag_row) {
		return internal_flag_status(intval($flag_row));
	}
}

if (!function_exists('canvas_form_request_status')) {
	
	/**
	 * Request Status For Combobox Value
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $en
	 * @param boolean|integer $num
	 *
	 * @return string['Pending', 'Accept', 'Blocked', 'Banned']
	 */
	function canvas_form_request_status($en = true, $num = false) {
		$data = [];
		if (true === $en) {
			$data = ['Pending', 'Accept', 'Blocked', 'Ban'];
		} else {
			$data = ['Pending', 'Terima', 'Block', 'Ban'];
		}
		
		if (false === $num) {
			return $data;
		} else {
			return $data[$num];
		}
	}
}

if (!function_exists('canvas_form_get_client_ip')) {
	
	/**
	 * Get Client IP
	 *
	 * author: https://stackoverflow.com/questions/15699101/get-the-client-ip-address-using-php
	 *
	 * @return string
	 * created @Dec 29, 2018
	 */
	function canvas_form_get_client_ip() {
		$ipaddress = '';
		
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if(isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if(isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if(isset($_SERVER['REMOTE_ADDR'])) {
			if ('::1' === $_SERVER['REMOTE_ADDR']) {
				$ipaddress = '127.0.0.1';
			} else {
				$ipaddress = $_SERVER['REMOTE_ADDR'];
			}
		} else {
			$ipaddress = 'UNKNOWN';
		}
		
		return $ipaddress;
	}
}

if (!function_exists('canvas_selectbox')) {
	
	/**
	 * Set Default Combobox Data
	 *
	 * @param array $object
	 * @param string $key_value
	 * @param string $key_label
	 * @param string $set_null_array
	 * @return array
	 */
	function canvas_selectbox($object, $key_value, $key_label, $set_null_array = true) {
		$options = [0 => ''];
		if (true === $set_null_array) $options[] = '';
		
		$keyLabel = [];
		if (canvas_string_contained($key_label, '|')) {
			$keyLabel = explode('|', $key_label);
		} else {
			$keyLabel[] = $key_label;
		}
		
		foreach ($object as $row) {
			$keyLabelValue = $row[$keyLabel[0]];
			if (!empty($keyLabel[1])) {
				if (!empty($row[$keyLabel[1]])) {
					$keyLabelValue = $row[$keyLabel[0]] . ' - ' . $keyLabelValue = $row[$keyLabel[1]];
				}
			}
			
			$options[$row[$key_value]] = $keyLabelValue;
		}
		
		return $options;
	}
}
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

use Canvastack\Origin\Models\Admin\System\Modules;
use Canvastack\Origin\Models\Admin\System\Preference;
use Canvastack\Origin\Library\Components\MetaTags;
use Canvastack\Origin\Controllers\Admin\System\GroupController;
use Canvastack\Origin\Controllers\Admin\System\AjaxController;
use Canvastack\Origin\Models\Admin\System\Log;

/**
 * Created on 10 Mar 2021
 * Time Created : 13:28:50
 *
 * @filesource	App.php
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

if (!function_exists('meta')) {
	
	function meta() {
		$meta = new MetaTags();
		
		return $meta;
	}
}

if (!function_exists('canvas_config')) {
	
	/**
	 * Get Config
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvas_config($string, $fileNameSettings = 'settings') {
		return Illuminate\Support\Facades\Config::get("canvas.{$fileNameSettings}.{$string}");
	}
}

if (!function_exists('is_multiplatform')) {
	
	/**
	 * Get Config
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function is_multiplatform() {
		$platform = canvas_config('platform_type');
		if ('single' === $platform) {
			return false;
		} else {
			return true;
		}
	}
}

if (!function_exists('canvas_sessions')) {
	/**
	 * Get Sessions
	 *
	 * author: wisnuwidi
	 *
	 * @return Illuminate\Support\Facades\Session
	 *
	 * created @Dec 14, 2018
	 * 
	 * @param string $param
	 * @param array $data
	 * 
	 * @return Session
	 */
	function canvas_sessions($param = 'all', $data = []) {
		if (!empty($data)) {
			return Session::{$param}($data);
		} else {
			return Session::{$param}();
		}
	}
}

if (!function_exists('canvas_redirect')) {
	
	/**
	 * Set Re-Direction Path With Some Data Info
	 *
	 * created @Nov 09, 2022
	 * author: wisnuwidi
	 * 
	 * @param string $to
	 * @param mixed|string|array $message
	 * @param mixed|string|boolean $status
	 * 
	 * @return \Illuminate\Http\RedirectResponse
	 */
	function canvas_redirect($to, $message = null, $status = false) {
		if (canvas_string_contained($to, 'http://') || canvas_string_contained($to, 'https://')) {
			$redirect = $to;
		} else {
			$redirect = Illuminate\Support\Facades\Redirect::to(url()->current() . '/' . $to);
		}
		
		if (!empty($message)) $redirect = $redirect->with('message', $message);
		if (!empty($status))  $redirect = $redirect->with($status, true);
		
		return $redirect;
	}
}

if (!function_exists('routelists_info')) {
	
	function routelists_info($route = null) {
		if (!empty($route)) {
			$currentRoute = explode('.', $route);
		} else {
			$currentRoute = explode('.', current_route());
		}
		
		$count_route     = intval(count($currentRoute)) - 1;
		$actionPageInfo  = last($currentRoute);
		unset($currentRoute[$count_route]);
		$baseRouteInfo   = implode('.', $currentRoute);
		
		return ['base_info' => $baseRouteInfo, 'last_info' => $actionPageInfo];
	}
}

if (!function_exists('canvas_base_assets')) {
	
	/**
	 * Get Base Assets URL
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvas_base_assets() {
		return canvas_config('baseURL') . '/' . canvas_config('base_template') . '/' . canvas_config('template');
	}
}

if (!function_exists('canvas_object')) {
    
    /**
     * Create Object
     *
     * @param mixed $array
     *
     * @return object
     */
    function canvas_object($array) {
        return (object) $array;
    }
}

if (!function_exists('canvas_clean_strings')) {
	
	/**
	 * Clean Strings
	 *
	 * @param string $strings
	 * @return string
	 */
	function canvas_clean_strings($strings, $node = '-') {
		$strings = trim(preg_replace('/[;\.\/\?\\\:@&=+\$,_\~\*\'"\!\|%<>\{\}\^\[\]`\-]/', ' ', $strings));
		
		return strtolower(preg_replace('/\s+/', $node, $strings));
	}
}

if (!function_exists('canvas_is_collection')) {
	
	/**
	 * Check if object is instance of Illuminate\Support\Collection
	 *
	 * @param object $object
	 *
	 * @return boolean
	 */
	function canvas_is_collection($object) {
		if ($object instanceof Illuminate\Support\Collection) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('canvas_format')) {
	
	/**
	 * Format Data
	 *
	 * @param string $data
	 * @param int $decimal_endpoint
	 * 	: Specifies how many decimals
	 * @param string $format_type
	 * 	: number, boolean
	 * @param string $separator
	 * 	: [,], [.]
	 *
	 * @return object
	 */
	function canvas_format($data, int $decimal_endpoint = 0, $separator = '.', $format_type = 'number') {
		if (!empty($data)) {
			if (!empty($decimal_endpoint)) {
				$format_type = 'decimal';
			}
			
			$_separator  = [',', '.'];
			if ('.' !== $separator) $_separator = ['.', ','];
			
			$format_data = null;
			if ($format_type === 'decimal' || !empty($decimal_endpoint)) {
				$format_data = number_format($data, $decimal_endpoint, $_separator[0], $_separator[1]);
			} else {
				$format_data = number_format($data, 0, $_separator[0], $_separator[1]);
			}
			
			return $format_data;
		}
	}
}

if (!function_exists('canvas_get_model')) {
	
	/**
	 * Get Model
	 *
	 * @param object $model
	 * @param boolean $find
	 *
	 * @return object|array
	 */
	function canvas_get_model($model, $find = false) {
		if (is_string($model)) $model = new $model;
		if (false !== $find)   $model = $model->find($find);
		
		return $model;
	}
}

if (!function_exists('canvas_query')) {
	
	/**
	 * Query Data Table
	 * 
	 * @param string $sql
	 * @param string $type
	 * 	: 'TABLE' [by default],
	 * 	: 'SELECT'
	 * 
	 * @return array|object
	 */
	function canvas_query($sql, $type = 'TABLE', $connection = null) {
		if (!empty($connection)) {
			$query = Illuminate\Support\Facades\DB::connection($connection)->{$type}($sql);
		} else {
			$query = Illuminate\Support\Facades\DB::{$type}($sql);
		}
		
		return $query;
	}
}

if (!function_exists('canvas_schema')) {
	
	function canvas_schema($param, $data = null) {
		if (!empty($data)) {
			return Illuminate\Support\Facades\Schema::{$param}($data);
		} else {
			return Illuminate\Support\Facades\Schema::{$param}();
		}
	}
}

if (!function_exists('canvas_db')) {
	
	function canvas_db($param, $data = null) {
		if (!empty($data)) {
			return Illuminate\Support\Facades\DB::{$param}($data);
		} else {
			return Illuminate\Support\Facades\DB::{$param}();
		}
	}
}

if (!function_exists('canvas_get_table_name_from_sql')) {
	
	function canvas_get_table_name_from_sql($sql) {
		$query = explode('from ', $sql);
		$query = explode(' ', $query[1]);
		
		return $query[0];
	}
}

if (!function_exists('canvas_mapping_page')) {
	
	function canvas_mapping_page($user_id) {
		$groupController = new GroupController();
		return $groupController->get_data_mapping_page($user_id);
	}
}

if (!function_exists('canvas_encrypt')) {
	
	/**
	 * Encrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvas_encrypt($string) {
		return Illuminate\Support\Facades\Crypt::encryptString($string);
	}
}

if (!function_exists('canvas_decrypt')) {
	
	/**
	 * Decrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvas_decrypt($string) {
		return Illuminate\Support\Facades\Crypt::decryptString($string);
	}
}

if (!function_exists('canvas_user_cryptcode')) {
	
	/**
	 * Encrypt
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function canvas_user_cryptcode($user_name, $user_email) {
		return canvas_encrypt($user_name . canvas_config('encode_separate') . $user_email);
	}
}

if (!function_exists('canvas_clean_strings')) {
	
	/**
	 * Clean Strings
	 *
	 * @param string $strings
	 * @param string $replace_with
	 * 
	 * @return string
	 */
	function canvas_clean_strings($strings, $replace_with = '-') {
		$strings = trim(preg_replace('/[;\.\/\?\\\:@&=+\$,_\~\*\'"\!\|%<>\{\}\^\[\]`\-]/', ' ', $strings));
		
		return strtolower(preg_replace('/\s+/', $replace_with, $strings));
	}
}

if (!function_exists('canvas_string_contained')) {
	
	/**
	 * Find contained character in string(s)
	 *
	 * @param string $string
	 * @param string $find
	 *
	 * @return boolean
	 */
	function canvas_string_contained($string, $find) {
		if (is_array($find)) {
			foreach ($find as $str) if (strpos($string, $str) !== false) return true;
		} else {
			if (strpos($string, $find) !== false) return true;
		}
		
		return false;
	}
}

if (!function_exists('canvas_underscore_to_camelcase')) {
    
    /**
     * Convert Character with an Underscore to Camel/Uppercase
     *
     * @param string $str
     *
     * @return string
     * 		This function will convert string to UPPERCASE if string length <= 3
     */
    function canvas_underscore_to_camelcase($str) {
        $string = false;
        if (true === str_contains($str, '_')) {
            $slices  = explode('_', $str);
            $strings = [];
            
            foreach ($slices as $str) {
                $_str = ucwords($str);
                if (strlen($str) <= 3) $_str = strtoupper($str);
                
                $strings[] = $_str;
            }
            $new_str = implode(' ', $strings);
            $string  = ucwords($new_str);
        } else {
            $string  = ucwords($str);
        }
        
        return $string;
    }
}

if (!function_exists('canvas_url')) {
    
    /**
     * Get Url
     *
     * @param string $string
     *
     * @return string
     */
    function canvas_url($string) {
        return url()->{$string}();
    }
}

if (!function_exists('canvas_array_contained_string')) {
	
	/**
	 * Check Array Contained With String
	 *
	 * @param array  $array
	 * @param string $string
	 *
	 * @return boolean
	 */
	function canvas_array_contained_string($array, $string, $return_data = false) {
		$result           = [];
		$result['status'] = false;
		$result['data']   = [];
		
		foreach($array as $info => $data) {
			if (canvas_string_contained($data, $string)) {
				$result['status']      = true;
				$result['data'][$info] = $string;
			}
		}
		
		if (false !== $return_data) {
			return $result['data'];
		} else {
			return $result['status'];
		}
	}
}

if (!function_exists('canvas_array_to_object_recursive')) {
    
    /**
     * Converting multidimensional array to object
     *
     * @param array $array
     * @return StdClass|array
     *
     * @link: https://stackoverflow.com/questions/9169892/how-to-convert-multidimensional-array-to-object-in-php
     */
    function canvas_array_to_object_recursive($array) {
        if (is_array($array) ) {
            foreach($array as $key => $value) {
                $array[$key] = canvas_array_to_object_recursive($value);
            }
            
            return (object) $array;
        }
        
        return $array;
    }
}

if (!function_exists('canvas_array_insert_new')) {
	
	/**
	 * Insert Data In Spesific array pos
	 *
	 * @author: https://stackoverflow.com/a/11321318/20139717
	 *
	 * @param array $array
	 * @param int $index
	 * @param string $val
	 *
	 * @return number|array
	 */
	function canvas_array_insert_new($array, $index, $val) {
		$size = count($array);
		if (!is_int($index) || $index <0 || $index > $size) {
			return -1;
		} else {
			$temp   = array_slice($array, 0, $index);
			$temp[] = $val;
			
			return array_merge($temp, array_slice($array, $index, $size));
		}
	}
}

if (!function_exists('canvas_array_insert')) {
	
	function canvas_array_insert(&$array, $position, $insert) {
		if (is_int($position)) {
			array_splice($array, $position, 0, $insert);
		} else {
			$pos   = array_search($position, array_keys($array));
			$array = array_merge (
				array_slice($array, 0, $pos),
				$insert,
				array_slice($array, $pos)
			);
		}
	}
}

if (!function_exists('canvas_exist_url')) {
    
    /**
     * Check if url exist
     * 
     * @param string $url
     * 
     * @return boolean
     */
    function canvas_exist_url($url) {
        $file_headers = @get_headers($url);
        
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        
        return true;
    }
}

if (!function_exists('canvas_not_empty')) {
    
    /**
     * Checking Not Empty Data
     *
     * @param mixed $data
     * @return mixed|boolean
     */
    function canvas_not_empty($data) {
        if (isset($data) && !empty($data) && '' != $data && NULL != $data) {
            return $data;
        } else {
            return false;
        }
    }
}

if (!function_exists('canvas_is_empty')) {
    
    /**
     * Checking Empty Data
     *
     * @param mixed $data
     * @return boolean
     */
    function canvas_is_empty($data) {
        return !canvas_not_empty($data);
    }
}

if (!function_exists('canvas_object')) {
	
	/**
	 * Create Object
	 *
	 * @param mixed $array
	 *
	 * @return object
	 */
	function canvas_object($array) {
		return (object) $array;
	}
}

if (!function_exists('camel_case')) {
	
	/**
	 * Camel Case
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param string $string
	 * @return string
	 */
	function camel_case($string) {
		return ucfirst($string);
	}
}

if (!function_exists('canvas_random_strings')) {
	
	/**
	 * Random String
	 *
	 * @param number $length
	 * @return string
	 */
	function canvas_random_strings($length = 8, $symbol = true, $string_set = null, $node = '_') {
		$random_strings = '';
		$strSymbol      = null;
		if (true === $symbol) {
			$strSymbol   = '!@#$%';
		}
		$strings        = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789{$strSymbol}";
		$stringsLength  = strlen($strings);
		
		for ($i = 0; $i < $length; $i ++) {
			$random_strings .= $strings[rand(0, $stringsLength - 1)];
		}
		
		if (!empty($string_set)) {
			return $string_set . $node . $random_strings;
		} else {
			return $random_strings;
		}
	}
}

if (!function_exists('canvas_unescape_html')) {
	
	/**
	 * Returning Back Escaped HTML
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param string $html
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	function canvas_unescape_html($html) {
		return new Illuminate\Support\HtmlString($html);
	}
}

if (!function_exists('get_object_called_name')) {
	
	/**
	 * Get Called Name Object
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @param object $object
	 *
	 * @return string
	 */
	function get_object_called_name($object) {
		return strtolower(last(explode('\\', get_class($object))));
	}
}

if (!function_exists('current_route')) {
	
	/**
	 * Get Current Route Name
	 *
	 * created @Sep 7, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function current_route() {
		return Route::currentRouteName();
	}
}

if (!function_exists('canvas_current_route')) {
	/**
	 * Get Current Route
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $facadeRoot
	 * @return object|mixed|string[]|object[]|\Illuminate\Contracts\Foundation\Application
	 */
	function canvas_current_route($facadeRoot = false) {
		if (false === $facadeRoot) return Route::getCurrentRoute();
		else return Route::getFacadeRoot();
	}
}

if (!function_exists('canvas_current_baseroute')) {
	/**
	 * Get Base Route From Current Route
	 *
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $facadeRoot
	 * @return mixed
	 */
	function canvas_current_baseroute() {
		$lastRoute = canvas_last_explode('.', canvas_current_route()->getName());
		
		return str_replace(".{$lastRoute}", '', canvas_current_route()->getName());
	}
}

if (!function_exists('canvas_last_explode')) {
	/**
	 * Get Last Array
	 *
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 *
	 * @param string $delimeter
	 * @param array $array
	 *
	 * @return mixed
	 */
	function canvas_last_explode($delimeter, $array) {
		return last(explode($delimeter, $array));
	}
}

if (!function_exists('canvas_get_current_route_id')) {
	
	/**
	 * Get ID From Current Route
	 *
	 * created @Apr 12, 2021
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvas_get_current_route_id($exclude_last = true) {
		$currentDataURL = explode('/', canvas_current_url());
		if (true === $exclude_last) {
			unset($currentDataURL[array_key_last($currentDataURL)]);
		}
		
		return intval($currentDataURL[array_key_last($currentDataURL)]);
	}
}

if (!function_exists('canvas_route_request_value')) {
	
	/**
	 * Get Route Value From Request
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param string $field
	 * 
	 * @return string
	 */
	function canvas_route_request_value($field) {
		$request = new Request();
		$request->route($field);
	}
}

if (!function_exists('canvas_current_url')) {

	/**
	 * Get Current URL
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @return string
	 */
	function canvas_current_url() {
		return url()->current();
	}
}

if (!function_exists('canvas_log_activity')) {
	
	/**
	 * Create Data User Log Activity
	 */
	function canvas_log_activity($routeInfo = [], $data = []) {
		$configuration = canvas_config('log_activity');
		if (!empty($configuration) && in_array($configuration['run_status'], [true, 'unexceptions'])) {
			
			$sessions = session()->all();
			if(!empty($data)) $sessions = $data;
			
			if (!empty($sessions['user_group'])) {
				if (empty($routeInfo)) {
					$routes       = canvas_current_route();
				}
				$requests         = Illuminate\Support\Facades\Request::class;
				$group_exceptions = ['root'];
				if (!empty($configuration['exceptions']['groups'])) {
					$group_exceptions = array_merge_recursive(['root'], $configuration['exceptions']['groups']);
				}
				if ('unexceptions' === $configuration['run_status']) {
					$group_exceptions = [];
				}
				
				if (!in_array($sessions['user_group'], $group_exceptions)) {
					if (empty($routeInfo)) {
						$current_controller = explode('@', $routes->action['controller']);
					} else {
						$current_controller = explode('@', $routeInfo['controller']);
					}
					
					if (!in_array($current_controller[0], $configuration['exceptions']['controllers'])) {
						$logs                    = [];
						
						$logs['user_id']         = $sessions['id'];
						$logs['username']        = $sessions['username'];
						$logs['user_fullname']   = $sessions['fullname'];
						$logs['user_email']      = $sessions['email'];
						
						$logs['user_group_id']   = $sessions['group_id'];
						$logs['user_group_name'] = $sessions['user_group'];
						$logs['user_group_info'] = $sessions['group_info'];
						
						if (empty($routeInfo)) {
							$logs['route_path']  = $routes->controller->data['route_info']->current_path;
							$logs['module_name'] = $routes->controller->data['route_info']->module_name;
							$logs['page_info']   = $routes->controller->data['route_info']->page_info;
						} else {
							$logs['route_path']  = $routeInfo['current_path'];
							$logs['module_name'] = $routeInfo['module_name'];
							$logs['page_info']   = $routeInfo['page_info'];
						}
						$logs['urli']            = $requests::fullUrl();
						$logs['method']          = $requests::method();
						
						$logs['ip_address']      = $requests::ip();
						$logs['user_agent']      = $requests::header('user-agent');
						$logs['sql_dump']        = NULL;
						
						$logs['created_at']      = date('Y-m-d h:i:s');
						$logs['updated_at']      = NULL;
						
						Log::create($logs);
					}
				}
			}
		}
	}
}

if (!function_exists('set_break_line_html')) {
	
	/**
	 * Adding Break Line
	 *
	 * @param string $tag
	 * @param integer $loops
	 *
	 * @return string
	 */
	function set_break_line_html($tag, $loops = 1) {
		$tags	= "";
		for ($x=2; $x<=$loops; $x++) $tags .= $tag;
		
		return "{$tags}";
	}
}

if (!function_exists('minify_code')) {
	
	/**
	 * Sanitizing Output
	 *
	 * Copyed From: http://php.net/manual/en/function.ob-start.php#71953:sanitize_output
	 *
	 * @param array $buffer
	 * @return mixed
	 */
	function sanitize_output($buffer) {
		/**
		 * (1) Strip whitespaces after tags, except space
		 * (2) Strip whitespaces before tags, except space
		 * (3) Shorten multiple whitespace sequences
		 * (4) Remove empty lines (between HTML tags)
		 * 			: cannot remove just any line-end characters because in inline JS they can matter!
		 * (5) Remove unwanted HTML comments <!-- text -->
		 * (6) Remove unwanted HTML comments /**
		 */
		
		$search = [
			'/\>[^\S ]+/s', 
			'/[^\S ]+\</s', 
			'/(\s)+/s', 
			'/\>[\r\n\t ]+\</s', 
			'/<!--(.|\s)*?-->/',
			'~^\s*//.*$\s*~m'
		];
		$replace = ['>', '<', '\\1', '><', '', ''];
		$search  = ['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'];
		$replace = [' ', ''];
		return set_break_line_html("\n", 1986) . preg_replace($search, $replace, $buffer);
	}
	
	/**
	 * Minified HTML output in a single line
	 * 		: Remember to remove all double slash comment(s) in your javascript code !!!
	 *
	 * @param string $output
	 */
	function minify_code($output = true) {
		if (false !== $output) {
			ob_start('sanitize_output');
		}
	}
}

if (!function_exists('canvas_insert')) {
	
	/**
	 * Simply Insert POST Data to Database
	 *
	 * @param object $model
	 * @param array $request
	 * @param string $get_field
	 * 
	 * @return string last inserted ID
	 */
	function canvas_insert($model, $data, $get_field = false) {
		$request = [];
		if (true === is_object($data)) {
			$request = $data;
		} else {
			$req     = new Request();
			$request = $req->merge($data);
		}
		
		$requests = [];
		foreach ($request->all() as $key => $value) {
			// manipulate value requested by checkbox and/or multiple selectbox
			if (is_array($value)) {
				$value = implode(',', $value);
			}
			
			// manipulate value requested by date and/or datetime
			if ("____-__-__" === $value || "____-__-__ __:__:__" === $value) {
				$value = null;
			}
			
			if (canvas_string_contained($value, 'WIB')) {
				$value = str_replace(' WIB', ':' . date('s'), $value);
			}
			
			$passwordKey = ['pass', 'password', 'passkey'];
			if (in_array($key, $passwordKey)) {
				$value = Hash::make($value);
			}
			
			$requests[$key] = $value;
		}
		$request->merge($requests);
		
		$modelName = new $model($request->all());
		if (true === array_key_exists('password', $request->all())) {
			$modelName->fill(['password' => Hash::make($request->get('password'))]);
		}
		
		$modelName = $modelName::create($request->all());
		
		if (false !== $get_field) {
			if (true === $get_field) {
				return $modelName->id;
			} else {
				return $modelName->{$get_field};
			}
		}
	}
}

if (!function_exists('canvas_update')) {
		
	/**
	 * Simply Update POST Data to Database
	 *
	 * @param object $model
	 * @param array $data
	 */
	function canvas_update($model, $data) {
		$request = [];
		if (true === is_object($data)) {
			$request = $data;
		} else {
			$req     = new Request();
			$request = $req->merge($data);
		}
		
		$requests = [];
		foreach ($request->all() as $key => $value) {
			// manipulate value requested by checkbox and/or multiple selectbox
			if (is_array($value)) {
				$value = implode(',', $value);
			}
			
			// manipulate value requested by date and/or datetime
			if ("____-__-__" === $value || "____-__-__ __:__:__" === $value) {
				$value = null;
			}
			
			if (canvas_string_contained($value, 'WIB')) {
				$value = str_replace(' WIB', ':' . date('s'), $value);
			}
			
			$passwordKey = ['pass', 'password', 'passkey'];
			if (in_array($key, $passwordKey)) {
				$value = Hash::make($value);
			}
			
			$requests[$key] = $value;
		}
		$request->merge($requests);
		
		$modelName = new $model($request->all());
		if (true === array_key_exists('password', $request->all())) {
			$modelName->fill(['password' => Hash::make($request->get('password'))]);
		}
		
		$modelName = $model->update($request->all());
	}
}

if (!function_exists('canvas_delete')) {
	
	/**
	 * Simply Delete(Soft) and or Restore deleted row from database
	 *
	 * @param object $request
	 * @param object $model_name
	 * @param int $id
	 *
	 * created @Aug 10, 2018
	 * author: wisnuwidi
	 */
	function canvas_delete($request, $model_name, $id) {
		$model = $model_name->find($id);
		
		if (!empty($model->id)) {
			$model->delete();
		} else {
			if (true === canvas_is_softdeletes($model_name)) {
				$remodel = $model_name::withTrashed()->find($id);
				$remodel->restore();
			}
		}
	}
}

if (!function_exists('canvas_query_get_id')) {
	function canvas_query_get_id($model_class, $where = []) {
		return $model_class::where($where)->first();
	}
}

if (!function_exists('canvas_set_filesize')) {
	
	/**
	 * Set File Size
	 *
	 * created @Sep 8, 2018
	 * author: wisnuwidi
	 *
	 * @param integer $size
	 * @param string $type
	 *
	 * @return number
	 */
	function canvas_set_filesize($size, $type = 'M') {
		if ('M' === $type) $megabytes = 1024;
		$filesize = intval($megabytes*intval($size));
		ini_set('upload_max_filesize', "{$filesize}{$type}");
		ini_set('post_max_size', "{$filesize}{$type}");
		
		return $filesize;
	}
}

if (!function_exists('canvas_image_validations')) {
	
	/**
	 * Set Image Validations
	 *
	 * created @Sep 8, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean|integer $max_size
	 *
	 * @return string
	 */
	function canvas_image_validations($max_size = false) {
		$max = false;
		if (false !== $max_size) $max = "|max:{$max_size}";
		
		return "image|mimes:jpeg,png,jpg,gif,svg{$max}";
	}
}

if (!function_exists('canvas_file_validations')) {
	
	/**
	 * Set File Validations
	 *
	 * created @Oct 16, 2018
	 * author: wisnuwidi
	 *
	 * @param string $mimes
	 * @param boolean|integer $max_size
	 *
	 * @return string
	 */
	function canvas_file_validations($mimes = "txt", $max_size = false) {
		$max = false;
		if (false !== $max_size) $max = "|max:{$max_size}";
		
		return "text|mimes:{$mimes}{$max}";
	}
}

if (!function_exists('canvas_file_exist')) {
	
	function canvas_file_exist($filePath) {
		return File::exists($filePath);
	}
}

if (!function_exists('canvas_make_dir')) {
	
	/**
	 * Create New Directory
	 *
	 * created @Jul 13, 2018
	 * author: wisnuwidi
	 *
	 * @param string $filePath
	 * @param number $mode
	 * @param bool $recursive
	 * @param bool $force
	 */
	function canvas_make_dir($filePath, $mode = 0777, $recursive = true, $force = true) {
		if (false === canvas_file_exist($filePath)) {
			File::makeDirectory($filePath, $mode, $recursive, $force);
		}
	}
}

if (!function_exists('canvas_action_buttons')) {
	
	function canvas_action_buttons($route_info, $background_color = 'white') {
		if (!empty($route_info)) {
			$box  = '';
			$box .= "<div class=\"header {$background_color}\">";
			
			foreach ($route_info->action_page as $key => $value) {
				$keys  = explode('|', $key);
				$color = $keys[0];
				$text  = $keys[1];
				
				if (!canvas_string_contained($text, 'delete') && !canvas_string_contained($text, 'restore')) {
					$box .= canvas_action_button_box($value, $text, $color);
				} else {
					$routeInfo = explode('::', $value);
					$routeUri  = [$routeInfo[0], (int)$routeInfo[1]];
					
					$box .= Collective\Html\FormFacade::open(['route'=> $routeUri, 'method'=>'Delete', 'onsubmit' => 'confirm("Are you sure?")']);
					$box .= canvas_action_button_box('submitButtonTag', $text, $color);
					$box .= Collective\Html\FormFacade::close();
				}
			}
			$box .= "</div>";
			
			return $box;
		}
	}
}

if (!function_exists('encode_id')) {
	
	function encode_id($id, $hashing = true) {
		$hash = false;
		if (true === $hashing) $hash = hash_code_id();
		
		return intval($id + 8 * 800 / 80) . $hash;
	}
}

if (!function_exists('decode_id')) {
	
	function decode_id($id, $hashing = true) {
		$hash = false;
		if (true === $hashing) $hash = hash_code_id();
		$ID = str_replace($hash, "", $id);
		
		return intval($ID - 8 * 800 / 80) . $hash;
	}
}

if (!function_exists('hash_code_id')) {
	
	function hash_code_id() {
		return hash('haval128,4', 'IDRIS');
	}
}

if (!function_exists('canvas_action_button_box')) {
	
	function canvas_action_button_box($url, $button_text, $url_class = false, $panel_class = 'panel-title header-list-panel') {
		$buttonText = ucwords($button_text);
		if (empty($url_class)) {
			$urlClass = 'btn btn-default btn_create btn-slideright button-app action-button pull-right';
		} else {
			$urlClass = "btn btn-{$url_class} btn_create btn-slideright button-app action-button pull-right";
		}
		
		$box  = "<h3 class=\"{$panel_class}\">";
		if ('submitButtonTag' === $url) {
			$box .= "<button class=\"{$urlClass}\" type=\"submit\">{$buttonText}</button>";
		} else {
			$box .= "<a href=\"{$url}\" class=\"{$urlClass}\">{$buttonText}</a>";
		}
		$box .= "</h3>";
		
		return $box;
	}
}

if (!function_exists('canvas_get_model_controllers_info')) {
	
	function canvas_get_model_controllers_info($buffers = [], $table_replace_map = null, $restriction_path = 'App\Http\Controllers\Admin\\') {
		$routeLists = Route::getRoutes();
		$models     = [];
		
		foreach ($routeLists as $list) {
			$route_name = $list->getName();
			$routeObj   = explode('.', $route_name);
			$actionName = $list->getActionName();
			
			if (str_contains($actionName, $restriction_path)) {
				// check if controller created in Admin folder
				if (count($routeObj) > 1) {
					if (in_array('index', $routeObj)) {
						$controllerPath = str_replace('@index', '', $actionName);
						$controller     = new $controllerPath();
						$controllerName = str_replace('Controller', ' Controller', class_basename($controller));
						
						if (is_array($controller->model_class_path)) {
							foreach ($controller->model_class_path as $model) {
								$modelclass = new $model();
								$baseRoute = str_replace('.index', '', $route_name);
								
								$modelConnection = null;
								if (!empty($modelclass->getConnectionName())) $modelConnection = $modelclass->getConnectionName();
								
								$models[$baseRoute]['controller']['route_base']  = str_replace('.index', '', $route_name);
								$models[$baseRoute]['controller']['route_index'] = $route_name;
								$models[$baseRoute]['controller']['path']        = $controllerPath;
								$models[$baseRoute]['controller']['name']        = $controllerName;
								$models[$baseRoute]['model']['name']             = class_basename($modelclass);
								$models[$baseRoute]['model']['connection']       = $modelConnection;
								$models[$baseRoute]['model']['path']             = get_class($modelclass);
								$models[$baseRoute]['model']['table']            = $modelclass->getTable();
								// use if any new table set for replace current table used in model
								if (!empty($table_replace_map)) {
									$models[$baseRoute]['model']['table_map']     = $table_replace_map;
								} else {
									$models[$baseRoute]['model']['table_map']     = $modelclass->getTable();
								}
								
								if (!empty($buffers[$baseRoute]['model'])) {
									$models[$baseRoute]['model']['buffers']       = $buffers[$baseRoute]['model'];
								}
							}
						}
					}
				}
			}
		}
		
		return $models;
	}
}

if (!function_exists('get_route_lists')) {
	
	/**
	 * Get Route Lists
	 *
	 * @param string $selected
	 * 		: true	=> fungsinya untuk memunculkan route path yang belum didaftarkan beserta dengan selected routenya.
	 * 		: false	=> fungsinya hanya untuk memunculkan route path yang belum didaftarkan saja.
	 *
	 * @return StdClass
	 */
	function get_route_lists($selected = false, $fullRender = false, $path_controllers = 'App\Http\Controllers\Admin\\') {
		$model   = Modules::withTrashed()->get();
		$modules = [];
		foreach ($model as $modul) {
			$mod  = $modul->getAttributes();
			$modules[$mod['route_path']] = $mod['route_path'];
		}
		
		$routeLists = Route::getRoutes();
		$routelists = [];
		foreach ($routeLists as $list) {
			$route_name = $list->getName();
			$routeObj   = explode('.', $route_name);
			
			if (str_contains($list->getActionName(), $path_controllers)) {
				// check if controller created in Admin folder
				if (count($routeObj) > 1) {
					if (in_array('index', $routeObj)) {
						$route_cat = count($routeObj);
						if (5 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]]['index'] = $routeObj[4];
						if (4 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]]                        = $routeObj[3];
						if (3 === $route_cat) $routelists[$routeObj[0]][$routeObj[1]]['index']                             = $routeObj[2];
						if (2 === $route_cat) $routelists[$routeObj[0]]['index']                                           = $routeObj[1];
					}
				}
			}
		}
		
		$routes    = [];
		$allroutes = [];
		foreach ($routelists as $parent => $category) {
			foreach ($category as $child => $route_data) {
				if (is_array($route_data)) {
					foreach ($route_data as $model => $second_child) {
						if (is_array($second_child)) {
							foreach ($second_child as $third_model => $last_index) {
								if ($last_index !== $third_model) {
									$route_base	= "{$parent}.{$child}.{$model}.{$third_model}";
									if (in_array($selected, $modules)) {
										if ($selected === $route_base) {
											// MAINTENANCE_WARNING
											$routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
												'route_base' => $route_base,
												'route_name' => "{$route_base}.{$third_model}.index",
												'route_url'  => route("{$route_base}.index")
											];
										}
									} elseif (!in_array($route_base, $modules)) {
										$routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$third_model}.index",
											'route_url'  => route("{$route_base}.index")
										];
									}
									
									$allroutes[$parent][$child][$model][$third_model]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$third_model}.index",
										'route_url'  => route("{$route_base}.index")
									];
								} else {
									dd($third_model);
								}
							}
						} else {
							if ($second_child !== $model) {
								$route_base	= "{$parent}.{$child}.{$model}";
								if (in_array($selected, $modules)) {
									if ($selected === $route_base) {
										$routes[$parent][$child][$model]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$second_child}",
											'route_url'  => route("{$route_base}.{$second_child}")
										];
									}
								} elseif (!in_array($route_base, $modules)) {
									$routes[$parent][$child][$model]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$second_child}",
										'route_url'  => route("{$route_base}.{$second_child}")
									];
								}
								
								$allroutes[$parent][$child][$model]['route_data'] = (object) [
									'route_base' => $route_base,
									'route_name' => "{$route_base}.{$second_child}",
									'route_url'  => route("{$route_base}.{$second_child}")
								];
							} else {
								$route_base	= "{$parent}.{$child}";
								if (in_array($selected, $modules)) {
									if ($selected === $route_base) {
										$routes[$parent][$child]['route_data'] = (object) [
											'route_base' => $route_base,
											'route_name' => "{$route_base}.{$model}",
											'route_url'  => route("{$route_base}.{$model}")
										];
									}
								} elseif (!in_array($route_base, $modules)) {
									$routes[$parent][$child]['route_data'] = (object) [
										'route_base' => $route_base,
										'route_name' => "{$route_base}.{$model}",
										'route_url'  => route("{$route_base}.{$model}")
									];
								}
								$allroutes[$parent][$child]['route_data'] = (object) [
									'route_base' => $route_base,
									'route_name' => "{$route_base}.{$model}",
									'route_url'  => route("{$route_base}.{$model}")
								];
							}
						}
					}
				} else {
					$route_base	= $parent;
					$routes['single'][$parent]['route_data'] = (object) [
						'route_base' => $route_base,
						'route_name' => "{$route_base}.{$child}",
						'route_url'  => route("{$route_base}.{$child}")
					];
					$allroutes['single'][$parent]['route_data'] = (object) [
						'route_base' => $route_base,
						'route_name' => "{$route_base}.{$child}",
						'route_url'  => route("{$route_base}.{$child}")
					];
				}
			}
		}
		
		if (true === $fullRender) {
			$routeresult = $allroutes;
		} else {
			$routeresult = $routes;
		}
		
		return canvas_array_to_object_recursive($routeresult);
	}
}

if (!function_exists('getPreference')) {
	
	/**
	 * Get All Web Preferences
	 *
	 * created @Aug 21, 2018
	 * author: wisnuwidi
	 */
	function getPreference() {
		foreach (Preference::all() as $preferences) {
			$preference = $preferences->getAttributes();
		}
		
		return $preference;
	}
}

if (!function_exists('canvas_combobox_data')) {
	
	/**
	 * Set Default Combobox Data
	 *
	 * @param array $object
	 * @param string $key_value
	 * @param string $key_label
	 * @param string $set_null_array
	 * @return array
	 */
	function canvas_combobox_data($object, $key_value, $key_label, $set_null_array = true) {
		$options = [0 => ''];
		if (true === $set_null_array) $options[] = '';
		foreach ($object as $row) {
			$options[$row[$key_value]] = $row[$key_label];
		}
		
		return $options;
	}
}

if (!function_exists('active_box')) {
	
	/**
	 * Active Status Combobox Value
	 *
	 * created @Sep 21, 2018
	 * author: wisnuwidi
	 *
	 * @param boolean $en
	 * @return string['No', 'Yes']
	 */
	function active_box($en = true) {
		if (true === $en) {
			return [null => ''] + ['Off, Non Active', 'Active'];
		} else {
			return [null => ''] + ['Tidak Aktif', 'Aktif'];
		}
	}
}

if (!function_exists('flag_status')) {
	
	/**
	 * Set Flag Status
	 *
	 * This function used to manage status module
	 * 		[ 0 => Super Admin ]   : Just root user can manage and access the module
	 * 		[ 1 => Administrator ] : End user can manage and access the module | root can manage the module too, with special condition
	 * 		[ 2 => End User ]      : All users can manage and access the module
	 *
	 * @return string[]
	 */
	function flag_status($as_root = false) {
		if (true === $as_root) {
			return [null => ''] + ['Super Admin', 'Administrator', 'End User'];
		} else {
			return [null => ''] + [1 => 'Administrator', 2 => 'End User'];
		}
	}
}

if (!function_exists('canvas_get_ajax_urli')) {
	
	function canvas_get_ajax_urli($init_post = 'AjaxPosF', $connections = null) {
		$ajaxURL = new AjaxController($connections);
		$ajaxURL::urli($init_post);
		
		return $ajaxURL::$ajaxUrli;
	}
}

if (!function_exists('internal_flag_status')) {
	
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
	function internal_flag_status($flag_row) {
		$flaging = intval($flag_row);
		if (0 == intval($flaging)) {
			$flag_status = 'Super Admin <sup>( root )</sup>';
		} elseif (1 == $flaging)  {
			$flag_status = 'Administrator';
		} else {
			$flag_status = 'End User <sup>( all )</sup>';
		}
		
		return $flag_status;
	}
}

if (!function_exists('canvas_mappage_button_add')) {
	
	/**
	 * Acction Buttons for Mapping Page Module
	 * 
	 * @param string $ajax_url
	 * @param string $node_btn
	 * @param string $id
	 * @param string $target_id
	 * @param string $second_target
	 * 
	 * @return string
	 */
	function canvas_mappage_button_add($ajax_url, $node_btn, $id, $target_id, $second_target) {
		$o  = "<div id='{$node_btn}' class='action-buttons-box'>";
			$o .= "<div class='hidden-sm hidden-xs action-buttons'>";
				$o .= "<a id='plusn{$node_btn}' class='btn btn-success btn-xs btn_view'><i class='fa fa-plus-circle' aria-hidden='true'></i></a>";
			//	$o .= "<a id='plusr{$node_btn}' class='btn teal btn-xs btn_view color-white' style=\"color: white !important;\"><i class='fa fa-plus' aria-hidden='true'></i></a>";
				$o .= "<a id='reset{$node_btn}' class='btn btn-danger btn-xs btn_view'><i class='fa fa-recycle' aria-hidden='true'></i></a>";
			$o .= "</div>";
		$o .= "</div>";
		
		$o .= "<div id=\"qc_{$id}\" class=\"qc_{$id}\" style=\"display:none;\"></div>";
		$o .= "<script type='text/javascript'>$(document).ready(function() {mappingPageButtonManipulation('{$node_btn}', '{$id}', '{$target_id}', '{$second_target}', '{$ajax_url}');});</script>";
		
		return $o;
	}
}

if (!function_exists('canvas_input')) {
	
	function canvas_input($type, $id = null, $class = null, $name = null, $value = null) {
		$id    = " id=\"{$id}\"";
		$class = " class=\"{$class}\"";
		$value = " value=\"{$value}\"";
		$name  = " name=\"{$name}\"";
		
		return "<input type=\"{$type}\"{$id}{$class}{$value}{$name} />";
	}
}

if (!function_exists('canvas_script')) {
	
	function canvas_script($script, $ready = true) {
		if (true === $ready) {
			return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
		} else {
			return "<script type='text/javascript'>{$script}</script>";
		}
	}
}

if (!function_exists('canvas_merge_request')) {
	
	/**
	 * Controlling Request Before Insert Process
	 * 
	 * @param object $request
	 * @param array $new_data
	 * @return object
	 * 
	 * @author: wisnuwidi
	 */
	function canvas_merge_request($request, $new_data = []) {
		foreach ($new_data as $field_name => $value) {
			if (null == $request->{$field_name}) {
				$request->merge([$field_name => $value]);
			}
		}
		
		return $request;
	}
}

if (!function_exists('canvas_is_softdeletes')) {
	
	function canvas_is_softdeletes($model) {
		return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model), true);
	}
}

if (!function_exists('canvas_attributes_to_string')) {
	
	/**
	 * Attributes To String
	 * Helper function used by some of the form helpers
	 *
	 * @param mixed
	 * @return string
	 */
	function canvas_attributes_to_string($attributes) {
		if (empty($attributes))
			return '';
			
			if (is_object($attributes))
				$attributes = (array) $attributes;
				
				if (is_array($attributes)) {
					$atts = '';
					foreach ($attributes as $key => $val) {
						$atts .= ' ' . $key . '="' . $val . '"';
					}
					
					return $atts;
				}
				
				if (is_string($attributes)) {
					return ' ' . $attributes;
				}
				
				return false;
	}
}

if (!function_exists('not_empty')) {
	
	/**
	 * Checking Not Empty Data
	 *
	 * @param mixed $data
	 * @return mixed|boolean
	 */
	function not_empty($data) {
		if (isset($data) && !empty($data) && '' != $data && NULL != $data) {
			return $data;
		} else {
			return false;
		}
	}
}

if (!function_exists('is_empty')) {
	
	/**
	 * Checking Empty Data
	 *
	 * @param mixed $data
	 * @return boolean
	 */
	function is_empty($data) {
		return !not_empty($data);
	}
}

if (!function_exists('canvas_get_model_data')) {
	
	/**
	 * Get All Web Preferences
	 *
	 * created @Aug 21, 2018
	 * author: wisnuwidi
	 */
	function canvas_get_model_data($model) {
		$data = [];
		foreach ($model::all() as $row) {
			$data = $row->getAttributes();
		}
		
		return $data;
	}
}

if (!function_exists('canvas_memory')) {
	
	/**
	 * Memory?
	 *
	 * created @Sep 28, 2018
	 * author: wisnuwidi
	 *
	 * @param bool $min
	 * @param integer $limit
	 */
	function canvas_memory($min = null, $limit = -1) {
		ini_set('memory_limit', $limit);
		if (null === $min) {
			minify_code(canvas_config('minify'));
		} else {
			minify_code($min);
		}
	}
}

if (!function_exists('canvas_date_info')) {
	
	function canvas_date_info($table, $field, $filter = null, $connection = null) {
		$query = canvas_query("SELECT DATE_FORMAT(MAX(`{$field}`), '%d% %M %Y') date_info FROM `{$table}` {$filter}", 'SELECT', $connection);
		
		return $query[0]->date_info;
	}
}

if (!function_exists('canvas_get_os')) {
	
	function canvas_get_os($user_agent) {
		$os_platform  = "Unknown OS Platform";
		$os_array     = [
			'/windows nt 11/i'      =>  'Windows 11',
			'/windows nt 10/i'      =>  'Windows 10',
			'/windows nt 6.3/i'     =>  'Windows 8.1',
			'/windows nt 6.2/i'     =>  'Windows 8',
			'/windows nt 6.1/i'     =>  'Windows 7',
			'/windows nt 6.0/i'     =>  'Windows Vista',
			'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
			'/windows nt 5.1/i'     =>  'Windows XP',
			'/windows xp/i'         =>  'Windows XP',
			'/windows nt 5.0/i'     =>  'Windows 2000',
			'/windows me/i'         =>  'Windows ME',
			'/win98/i'              =>  'Windows 98',
			'/win95/i'              =>  'Windows 95',
			'/win16/i'              =>  'Windows 3.11',
			'/macintosh|mac os x/i' =>  'Mac OS X',
			'/mac_powerpc/i'        =>  'Mac OS 9',
			'/linux/i'              =>  'Linux',
			'/ubuntu/i'             =>  'Ubuntu',
			'/iphone/i'             =>  'iPhone',
			'/ipod/i'               =>  'iPod',
			'/ipad/i'               =>  'iPad',
			'/android/i'            =>  'Android',
			'/blackberry/i'         =>  'BlackBerry',
			'/webos/i'              =>  'Mobile'
		];
		
		foreach ($os_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) $os_platform = $value;
		}
		
		return $os_platform;
	}
}

if (!function_exists('canvas_get_os')) {
	
	function canvas_get_os($user_agent) {
		$browser       = "Unknown Browser";
		$browser_array = [
			'/msie/i'      => 'Internet Explorer',
			'/firefox/i'   => 'Firefox',
			'/safari/i'    => 'Safari',
			'/chrome/i'    => 'Chrome',
			'/edge/i'      => 'Edge',
			'/opera/i'     => 'Opera',
			'/netscape/i'  => 'Netscape',
			'/maxthon/i'   => 'Maxthon',
			'/konqueror/i' => 'Konqueror',
			'/mobile/i'    => 'Handheld Browser'
		];
		
		foreach ($browser_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) $browser = $value;
		}
		
		return $browser;
	}
}
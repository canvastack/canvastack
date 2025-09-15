<?php

use Canvastack\Canvastack\Controllers\Admin\System\AjaxController;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Canvastack\Canvastack\Models\Admin\System\Log;
use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Models\Admin\System\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/**
 * Created on 10 Mar 2021
 * Time Created : 13:28:50
 *
 * @filesource	App.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
if (! function_exists('meta')) {

    function meta()
    {
        $meta = new MetaTags();

        return $meta;
    }
}

if (! function_exists('canvastack_config')) {

    /**
     * Get Config (delegated)
     */
    function canvastack_config($string, $fileNameSettings = 'settings')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::config($string, $fileNameSettings);
    }
}

if (! function_exists('is_multiplatform')) {

    /**
     * Get Config
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function is_multiplatform()
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::isMultiplatform();
    }
}

if (! function_exists('canvastack_sessions')) {
    /**
     * Get Sessions
     *
     * author: wisnuwidi
     *
     *
     * @param  string  $param
     * @param  array  $data
     * @return Illuminate\Support\Facades\Session
     *
     * created @Dec 14, 2018
     * @return Session
     */
    function canvastack_sessions($param = 'all', $data = [])
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::session($param, $data);
    }
}

if (! function_exists('canvastack_redirect')) {

    /**
     * Set Re-Direction Path With Some Data Info
     *
     * created @Nov 09, 2022
     * author: wisnuwidi
     *
     * @param  string  $to
     * @param  mixed|string|array  $message
     * @param  mixed|string|bool  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    function canvastack_redirect($to, $message = null, $status = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::redirect($to, $message, $status);
    }
}

if (! function_exists('routelists_info')) {

    function routelists_info($route = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::routeInfo($route);
    }
}

if (! function_exists('canvastack_base_assets')) {

    /**
     * Get Base Assets URL
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function canvastack_base_assets()
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::assetBase();
    }
}

if (! function_exists('canvastack_object')) {

    /**
     * Create Object
     *
     * @param  mixed  $array
     * @return object
     */
    function canvastack_object($array)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::toObject($array);
    }
}

if (! function_exists('canvastack_clean_strings')) {

    /**
     * Clean Strings
     *
     * @param  string  $strings
     * @return string
     */
    function canvastack_clean_strings($strings, $node = '-')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::cleanString($strings, $node);
    }
}

if (! function_exists('canvastack_is_collection')) {

    /**
     * Check if object is instance of Illuminate\Support\Collection
     *
     * @param  object  $object
     * @return bool
     */
    function canvastack_is_collection($object)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::isCollection($object);
    }
}

if (! function_exists('canvastack_format')) {

    /**
     * Format Data
     *
     * @param  string  $data
     * @param  int  $decimal_endpoint
     * 	: Specifies how many decimals
     * @param  string  $format_type
     * 	: number, boolean
     * @param  string  $separator
     * 	: [,], [.]
     * @return object
     */
    function canvastack_format($data, int $decimal_endpoint = 0, $separator = '.', $format_type = 'number')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formatNumber($data, $decimal_endpoint, $separator, $format_type);
    }
}

if (! function_exists('canvastack_get_model')) {

    /**
     * Get Model
     *
     * @param  object  $model
     * @param  bool  $find
     * @return object|array
     */
    function canvastack_get_model($model, $find = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::getModel($model, $find);
    }
}

if (! function_exists('canvastack_query')) {

    /**
     * Query Data Table
     *
     * @param  string  $sql
     * @param  string  $type
     * 	: 'TABLE' [by default],
     * 	: 'SELECT'
     * @return array|object
     */
    function canvastack_query($sql, $type = 'TABLE', $connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::query($sql, $type, $connection);
    }
}

if (! function_exists('canvastack_schema')) {

    function canvastack_schema($param, $data = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::schema($param, $data);
    }
}

if (! function_exists('canvastack_db')) {

    function canvastack_db($param, $data = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::db($param, $data);
    }
}

if (! function_exists('canvastack_get_table_name_from_sql')) {

    function canvastack_get_table_name_from_sql($sql)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::tableFromSql(strtolower($sql));
    }
}

if (! function_exists('canvastack_mapping_page')) {

    function canvastack_mapping_page($user_id)
    {
        $groupController = new GroupController();

        return $groupController->get_data_mapping_page($user_id);
    }
}

if (! function_exists('canvastack_encrypt')) {

    /**
     * Encrypt
     *
     * @param  string  $string
     * @return string
     */
    function canvastack_encrypt($string)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::encrypt($string);
    }
}

if (! function_exists('canvastack_decrypt')) {

    /**
     * Decrypt
     *
     * @param  string  $string
     * @return string
     */
    function canvastack_decrypt($string)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::decrypt($string);
    }
}

if (! function_exists('canvastack_user_cryptcode')) {

    /**
     * Encrypt
     *
     * @param  string  $string
     * @return string
     */
    function canvastack_user_cryptcode($user_name, $user_email)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::userCryptCode($user_name, $user_email);
    }
}



if (! function_exists('canvastack_string_contained')) {

    /**
     * Find contained character in string(s)
     *
     * @param  string  $string
     * @param  string  $find
     * @return bool
     */
    function canvastack_string_contained($string, $find)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::contains($string, $find);
    }
}

if (! function_exists('canvastack_underscore_to_camelcase')) {

    /**
     * Convert Character with an Underscore to Camel/Uppercase
     *
     * @param  string  $str
     * @return string
     * 		This function will convert string to UPPERCASE if string length <= 3
     */
    function canvastack_underscore_to_camelcase($str)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::underscoreToCamelcase($str);
    }
}

if (! function_exists('canvastack_url')) {

    /**
     * Get Url
     *
     * @param  string  $string
     * @return string
     */
    function canvastack_url($string)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::url($string);
    }
}

if (! function_exists('canvastack_array_contained_string')) {

    /**
     * Check Array Contained With String
     *
     * @param  array  $array
     * @param  string  $string
     * @return bool
     */
    function canvastack_array_contained_string($array, $string, $return_data = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::arrayContainsString($array, $string, $return_data);
    }
}

if (! function_exists('canvastack_array_to_object_recursive')) {

    /**
     * Converting multidimensional array to object
     *
     * @param  array  $array
     * @return StdClass|array
     *
     * @link: https://stackoverflow.com/questions/9169892/how-to-convert-multidimensional-array-to-object-in-php
     */
    function canvastack_array_to_object_recursive($array)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::arrayToObjectRecursive($array);
    }
}

if (! function_exists('canvastack_array_insert_new')) {

    /**
     * Insert Data In Spesific array pos
     *
     * @author: https://stackoverflow.com/a/11321318/20139717
     *
     * @param  array  $array
     * @param  int  $index
     * @param  string  $val
     * @return number|array
     */
    function canvastack_array_insert_new($array, $index, $val)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::arrayInsertNew($array, (int)$index, $val);
    }
}

if (! function_exists('canvastack_array_insert')) {

    function canvastack_array_insert(&$array, $position, $insert)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::arrayInsert($array, $position, $insert);
    }
}

if (! function_exists('canvastack_exist_url')) {

    /**
     * Check if url exist
     *
     * @param  string  $url
     * @return bool
     */
    function canvastack_exist_url($url)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::urlExists($url);
    }
}

if (! function_exists('canvastack_not_empty')) {

    /**
     * Checking Not Empty Data
     *
     * @param  mixed  $data
     * @return mixed|bool
     */
    function canvastack_not_empty($data)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::notEmpty($data);
    }
}

if (! function_exists('canvastack_is_empty')) {

    /**
     * Checking Empty Data
     *
     * @param  mixed  $data
     * @return bool
     */
    function canvastack_is_empty($data)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::isEmpty($data);
    }
}



if (! function_exists('camel_case')) {

    /**
     * Camel Case
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  string  $string
     * @return string
     */
    function camel_case($string)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::camelCase($string);
    }
}

if (! function_exists('canvastack_random_strings')) {

    /**
     * Random String
     *
     * @param  number  $length
     * @return string
     */
    function canvastack_random_strings($length = 8, $symbol = true, $string_set = null, $node = '_')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::randomString($length, $symbol, $string_set, $node);
    }
}

if (! function_exists('canvastack_unescape_html')) {

    /**
     * Returning Back Escaped HTML
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @param  string  $html
     * @return \Illuminate\Support\HtmlString
     */
    function canvastack_unescape_html($html)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::unescapeHtml($html);
    }
}

if (! function_exists('get_object_called_name')) {

    /**
     * Get Called Name Object
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @param  object  $object
     * @return string
     */
    function get_object_called_name($object)
    {
        return strtolower(last(explode('\\', get_class($object))));
    }
}

if (! function_exists('current_route')) {

    /**
     * Get Current Route Name
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function current_route()
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::currentRouteName();
    }
}

if (! function_exists('canvastack_current_route')) {
    /**
     * Get Current Route
     * created @Dec 11, 2018
     * author: wisnuwidi
     *
     * @param  bool  $facadeRoot
     * @return object|mixed|string[]|object[]|\Illuminate\Contracts\Foundation\Application
     */
    function canvastack_current_route($facadeRoot = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::currentRoute($facadeRoot);
    }
}

if (! function_exists('canvastack_current_baseroute')) {
    /**
     * Get Base Route From Current Route
     *
     * created @Dec 11, 2018
     * author: wisnuwidi
     *
     * @param  bool  $facadeRoot
     * @return mixed
     */
    function canvastack_current_baseroute()
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::currentBaseRoute();
    }
}

if (! function_exists('canvastack_last_explode')) {
    /**
     * Get Last Array
     *
     * created @Dec 11, 2018
     * author: wisnuwidi
     *
     * @param  string  $delimeter
     * @param  array  $array
     * @return mixed
     */
    function canvastack_last_explode($delimeter, $array)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::lastExplode($delimeter, $array);
    }
}

if (! function_exists('canvastack_get_current_route_id')) {

    /**
     * Get ID From Current Route
     *
     * created @Apr 12, 2021
     * author: wisnuwidi
     *
     * @return string
     */
    function canvastack_get_current_route_id($exclude_last = true)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::currentRouteId($exclude_last);
    }
}

if (! function_exists('canvastack_route_request_value')) {

    /**
     * Get Route Value From Request
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  string  $field
     * @return string
     */
    function canvastack_route_request_value($field)
    {
        $request = new Request();
        $request->route($field);
    }
}

if (! function_exists('canvastack_current_url')) {

    /**
     * Get Current URL
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @return string
     */
    function canvastack_current_url()
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::currentUrl();
    }
}

if (! function_exists('canvastack_log_activity')) {

    /**
     * Create Data User Log Activity
     */
    function canvastack_log_activity($routeInfo = [], $data = [])
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::logActivity($routeInfo, $data);
    }
}

if (! function_exists('set_break_line_html')) {

    /**
     * Adding Break Line
     *
     * @param  string  $tag
     * @param  int  $loops
     * @return string
     */
    function set_break_line_html($tag, $loops = 1)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::breakLineHtml($tag, (int)$loops);
    }
}

if (! function_exists('minify_code')) {

    /**
     * Sanitizing Output
     *
     * Copyed From: http://php.net/manual/en/function.ob-start.php#71953:sanitize_output
     *
     * @param  array  $buffer
     * @return mixed
     */
    function sanitize_output($buffer)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::sanitizeOutput($buffer);
    }

    /**
     * Minified HTML output in a single line
     * 		: Remember to remove all double slash comment(s) in your javascript code !!!
     *
     * @param  string  $output
     */
    function minify_code($output = true)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::minifyCode($output);
    }
}

if (! function_exists('canvastack_insert')) {

    /**
     * Simply Insert POST Data to Database
     *
     * @param  object  $model
     * @param  array  $request
     * @param  string  $get_field
     * @return string last inserted ID
     */
    function canvastack_insert($model, $data, $get_field = false)
    {
        $request = [];
        if (true === is_object($data)) {
            $request = $data;
        } else {
            $req = new Request();
            $request = $req->merge($data);
        }

        $requests = [];
        foreach ($request->all() as $key => $value) {
            // manipulate value requested by checkbox and/or multiple selectbox
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            // manipulate value requested by date and/or datetime
            if ('____-__-__' === $value || '____-__-__ __:__:__' === $value) {
                $value = null;
            }

            if (canvastack_string_contained($value, 'WIB')) {
                $value = str_replace(' WIB', ':'.date('s'), $value);
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

if (! function_exists('canvastack_update')) {

    /**
     * Simply Update POST Data to Database
     *
     * @param  object  $model
     * @param  array  $data
     */
    function canvastack_update($model, $data)
    {
        $request = [];
        if (true === is_object($data)) {
            $request = $data;
        } else {
            $req = new Request();
            $request = $req->merge($data);
        }

        $requests = [];
        foreach ($request->all() as $key => $value) {
            // manipulate value requested by checkbox and/or multiple selectbox
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            // manipulate value requested by date and/or datetime
            if ('____-__-__' === $value || '____-__-__ __:__:__' === $value) {
                $value = null;
            }

            if (canvastack_string_contained($value, 'WIB')) {
                $value = str_replace(' WIB', ':'.date('s'), $value);
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

if (! function_exists('canvastack_delete')) {

    /**
     * Simply Delete(Soft) and or Restore deleted row from database
     *
     * @param  object  $request
     * @param  object  $model_name
     * @param  int  $id
     *
     * created @Aug 10, 2018
     * author: wisnuwidi
     */
    function canvastack_delete($request, $model_name, $id)
    {
        // Use dynamic delete detection for better handling
        try {
            $detectorInfo = \Canvastack\Canvastack\Library\Components\Utility\DeleteDetector::getCurrentControllerInfo();
            
            // Find the model record
            $model = null;
            if (canvastack_is_softdeletes($model_name)) {
                // For soft delete models, check both active and trashed records
                $model = $model_name->find($id);
                if (!$model) {
                    $model = $model_name::withTrashed()->find($id);
                }
            } else {
                $model = $model_name->find($id);
            }
            
            if (!$model) {
                throw new \Exception("Record with ID {$id} not found");
            }
            
            // Determine action based on record state
            if (method_exists($model, 'trashed') && $model->trashed()) {
                // Record is soft deleted, restore it
                $model->restore();
                \Log::info("Record restored via canvastack_delete: ID {$id} in {$detectorInfo['table_name']}");
            } else {
                // Record is active, delete it
                $model->delete();
                $deleteType = $detectorInfo['delete_type'] ?? (canvastack_is_softdeletes($model_name) ? 'soft' : 'hard');
                \Log::info("Record {$deleteType} deleted via canvastack_delete: ID {$id} in {$detectorInfo['table_name']}");
            }
            
        } catch (\Throwable $e) {
            // Fallback to original logic if dynamic detection fails
            \Log::warning("Dynamic delete detection failed, using fallback: " . $e->getMessage());
            
            $model = $model_name->find($id);
            if (! empty($model->id)) {
                $model->delete();
            } else {
                if (true === canvastack_is_softdeletes($model_name)) {
                    $remodel = $model_name::withTrashed()->find($id);
                    if ($remodel) {
                        $remodel->restore();
                    }
                }
            }
        }
    }
}

if (! function_exists('canvastack_query_get_id')) {
    function canvastack_query_get_id($model_class, $where = [])
    {
        return $model_class::where($where)->first();
    }
}













if (! function_exists('encode_id')) {

    function encode_id($id, $hashing = true)
    {
        $hash = false;
        if (true === $hashing) {
            $hash = hash_code_id();
        }

        return intval($id + 8 * 800 / 80).$hash;
    }
}

if (! function_exists('decode_id')) {

    function decode_id($id, $hashing = true)
    {
        $hash = false;
        if (true === $hashing) {
            $hash = hash_code_id();
        }
        $ID = str_replace($hash, '', $id);

        return intval($ID - 8 * 800 / 80).$hash;
    }
}

if (! function_exists('hash_code_id')) {

    function hash_code_id()
    {
        return hash('haval128,4', 'IDRIS');
    }
}



if (! function_exists('canvastack_action_buttons')) {

    function canvastack_action_buttons($route_info, $background_color = 'white')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::actionButtonsHeader($route_info, $background_color);
    }
}

if (! function_exists('canvastack_get_model_controllers_info')) {

    function canvastack_get_model_controllers_info($buffers = [], $table_replace_map = null, $restriction_path = 'App\Http\Controllers\Admin\\')
    {
        $routeLists = Route::getRoutes();
        $models = [];

        foreach ($routeLists as $list) {
            $route_name = $list->getName();
            $routeObj = explode('.', $route_name);
            $actionName = $list->getActionName();

            if (str_contains($actionName, $restriction_path)) {
                // check if controller created in Admin folder
                if (count($routeObj) > 1) {
                    if (in_array('index', $routeObj)) {
                        $controllerPath = str_replace('@index', '', $actionName);
                        $controller = new $controllerPath();
                        $controllerName = str_replace('Controller', ' Controller', class_basename($controller));

                        if (is_array($controller->model_class_path)) {
                            foreach ($controller->model_class_path as $model) {
                                $modelclass = new $model();
                                $baseRoute = str_replace('.index', '', $route_name);

                                $modelConnection = null;
                                if (! empty($modelclass->getConnectionName())) {
                                    $modelConnection = $modelclass->getConnectionName();
                                }

                                $models[$baseRoute]['controller']['route_base'] = str_replace('.index', '', $route_name);
                                $models[$baseRoute]['controller']['route_index'] = $route_name;
                                $models[$baseRoute]['controller']['path'] = $controllerPath;
                                $models[$baseRoute]['controller']['name'] = $controllerName;
                                $models[$baseRoute]['model']['name'] = class_basename($modelclass);
                                $models[$baseRoute]['model']['connection'] = $modelConnection;
                                $models[$baseRoute]['model']['path'] = get_class($modelclass);
                                $models[$baseRoute]['model']['table'] = $modelclass->getTable();
                                // use if any new table set for replace current table used in model
                                if (! empty($table_replace_map)) {
                                    $models[$baseRoute]['model']['table_map'] = $table_replace_map;
                                } else {
                                    $models[$baseRoute]['model']['table_map'] = $modelclass->getTable();
                                }

                                if (! empty($buffers[$baseRoute]['model'])) {
                                    $models[$baseRoute]['model']['buffers'] = $buffers[$baseRoute]['model'];
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

if (! function_exists('get_route_lists')) {

    /**
     * Get Route Lists
     *
     * @param  string  $selected
     * 		: true	=> fungsinya untuk memunculkan route path yang belum didaftarkan beserta dengan selected routenya.
     * 		: false	=> fungsinya hanya untuk memunculkan route path yang belum didaftarkan saja.
     * @return StdClass
     */
    function get_route_lists($selected = false, $fullRender = false, $path_controllers = 'App\Http\Controllers\Admin\\')
    {
        $model = Modules::withTrashed()->get();
        $modules = [];
        foreach ($model as $modul) {
            $mod = $modul->getAttributes();
            $modules[$mod['route_path']] = $mod['route_path'];
        }

        $routeLists = Route::getRoutes();
        $routelists = [];
        foreach ($routeLists as $list) {
            $route_name = $list->getName();
            $routeObj = explode('.', $route_name);

            if (str_contains($list->getActionName(), $path_controllers)) {
                // check if controller created in Admin folder
                if (count($routeObj) > 1) {
                    if (in_array('index', $routeObj)) {
                        $route_cat = count($routeObj);
                        if (5 === $route_cat) {
                            $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]][$routeObj[3]]['index'] = $routeObj[4];
                        }
                        if (4 === $route_cat) {
                            $routelists[$routeObj[0]][$routeObj[1]][$routeObj[2]] = $routeObj[3];
                        }
                        if (3 === $route_cat) {
                            $routelists[$routeObj[0]][$routeObj[1]]['index'] = $routeObj[2];
                        }
                        if (2 === $route_cat) {
                            $routelists[$routeObj[0]]['index'] = $routeObj[1];
                        }
                    }
                }
            }
        }

        $routes = [];
        $allroutes = [];
        foreach ($routelists as $parent => $category) {
            foreach ($category as $child => $route_data) {
                if (is_array($route_data)) {
                    foreach ($route_data as $model => $second_child) {
                        if (is_array($second_child)) {
                            foreach ($second_child as $third_model => $last_index) {
                                if ($last_index !== $third_model) {
                                    $route_base = "{$parent}.{$child}.{$model}.{$third_model}";
                                    if (in_array($selected, $modules)) {
                                        if ($selected === $route_base) {
                                            // MAINTENANCE_WARNING
                                            $routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
                                                'route_base' => $route_base,
                                                'route_name' => "{$route_base}.{$third_model}.index",
                                                'route_url' => route("{$route_base}.index"),
                                            ];
                                        }
                                    } elseif (! in_array($route_base, $modules)) {
                                        $routes[$parent][$child][$model][$third_model]['route_data'] = (object) [
                                            'route_base' => $route_base,
                                            'route_name' => "{$route_base}.{$third_model}.index",
                                            'route_url' => route("{$route_base}.index"),
                                        ];
                                    }

                                    $allroutes[$parent][$child][$model][$third_model]['route_data'] = (object) [
                                        'route_base' => $route_base,
                                        'route_name' => "{$route_base}.{$third_model}.index",
                                        'route_url' => route("{$route_base}.index"),
                                    ];
                                } else {
                                    dd($third_model);
                                }
                            }
                        } else {
                            if ($second_child !== $model) {
                                $route_base = "{$parent}.{$child}.{$model}";
                                if (in_array($selected, $modules)) {
                                    if ($selected === $route_base) {
                                        $routes[$parent][$child][$model]['route_data'] = (object) [
                                            'route_base' => $route_base,
                                            'route_name' => "{$route_base}.{$second_child}",
                                            'route_url' => route("{$route_base}.{$second_child}"),
                                        ];
                                    }
                                } elseif (! in_array($route_base, $modules)) {
                                    $routes[$parent][$child][$model]['route_data'] = (object) [
                                        'route_base' => $route_base,
                                        'route_name' => "{$route_base}.{$second_child}",
                                        'route_url' => route("{$route_base}.{$second_child}"),
                                    ];
                                }

                                $allroutes[$parent][$child][$model]['route_data'] = (object) [
                                    'route_base' => $route_base,
                                    'route_name' => "{$route_base}.{$second_child}",
                                    'route_url' => route("{$route_base}.{$second_child}"),
                                ];
                            } else {
                                $route_base = "{$parent}.{$child}";
                                if (in_array($selected, $modules)) {
                                    if ($selected === $route_base) {
                                        $routes[$parent][$child]['route_data'] = (object) [
                                            'route_base' => $route_base,
                                            'route_name' => "{$route_base}.{$model}",
                                            'route_url' => route("{$route_base}.{$model}"),
                                        ];
                                    }
                                } elseif (! in_array($route_base, $modules)) {
                                    $routes[$parent][$child]['route_data'] = (object) [
                                        'route_base' => $route_base,
                                        'route_name' => "{$route_base}.{$model}",
                                        'route_url' => route("{$route_base}.{$model}"),
                                    ];
                                }
                                $allroutes[$parent][$child]['route_data'] = (object) [
                                    'route_base' => $route_base,
                                    'route_name' => "{$route_base}.{$model}",
                                    'route_url' => route("{$route_base}.{$model}"),
                                ];
                            }
                        }
                    }
                } else {
                    $route_base = $parent;
                    $routes['single'][$parent]['route_data'] = (object) [
                        'route_base' => $route_base,
                        'route_name' => "{$route_base}.{$child}",
                        'route_url' => route("{$route_base}.{$child}"),
                    ];
                    $allroutes['single'][$parent]['route_data'] = (object) [
                        'route_base' => $route_base,
                        'route_name' => "{$route_base}.{$child}",
                        'route_url' => route("{$route_base}.{$child}"),
                    ];
                }
            }
        }

        if (true === $fullRender) {
            $routeresult = $allroutes;
        } else {
            $routeresult = $routes;
        }

        return canvastack_array_to_object_recursive($routeresult);
    }
}

if (! function_exists('getPreference')) {

    /**
     * Get All Web Preferences
     *
     * created @Aug 21, 2018
     * author: wisnuwidi
     */
    function getPreference()
    {
        foreach (Preference::all() as $preferences) {
            $preference = $preferences->getAttributes();
        }

        return $preference;
    }
}

if (! function_exists('canvastack_combobox_data')) {

    /**
     * Set Default Combobox Data
     *
     * @param  array  $object
     * @param  string  $key_value
     * @param  string  $key_label
     * @param  string  $set_null_array
     * @return array
     */
    function canvastack_combobox_data($object, $key_value, $key_label, $set_null_array = true)
    {
        $options = [0 => ''];
        if (true === $set_null_array) {
            $options[] = '';
        }
        foreach ($object as $row) {
            $options[$row[$key_value]] = $row[$key_label];
        }

        return $options;
    }
}

if (! function_exists('active_box')) {

    /**
     * Active Status Combobox Value
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  bool  $en
     * @return string['No', 'Yes']
     */
    function active_box($en = true)
    {
        if (true === $en) {
            return [null => ''] + ['Off, Non Active', 'Active'];
        } else {
            return [null => ''] + ['Tidak Aktif', 'Aktif'];
        }
    }
}

if (! function_exists('flag_status')) {

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
    function flag_status($as_root = false)
    {
        if (true === $as_root) {
            return [null => ''] + ['Super Admin', 'Administrator', 'End User'];
        } else {
            return [null => ''] + [1 => 'Administrator', 2 => 'End User'];
        }
    }
}

if (! function_exists('canvastack_get_ajax_urli')) {

    function canvastack_get_ajax_urli($init_post = 'AjaxPosF', $connections = null)
    {
        $ajaxURL = new AjaxController($connections);
        $ajaxURL::urli($init_post);

        return $ajaxURL::$ajaxUrli;
    }
}

if (! function_exists('internal_flag_status')) {

    /**
     * Set Flag Status Value
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @param  int|string  $flag_row
     * @return string
     */
    function internal_flag_status($flag_row)
    {
        $flaging = intval($flag_row);
        if (0 == intval($flaging)) {
            $flag_status = 'Super Admin <sup>( root )</sup>';
        } elseif (1 == $flaging) {
            $flag_status = 'Administrator';
        } else {
            $flag_status = 'End User <sup>( all )</sup>';
        }

        return $flag_status;
    }
}

if (! function_exists('canvastack_mappage_button_add')) {

    /**
     * Acction Buttons for Mapping Page Module
     *
     * @param  string  $ajax_url
     * @param  string  $node_btn
     * @param  string  $id
     * @param  string  $target_id
     * @param  string  $second_target
     * @return string
     */
    function canvastack_mappage_button_add($ajax_url, $node_btn, $id, $target_id, $second_target)
    {
        $o = "<div id='{$node_btn}' class='action-buttons-box'>";
        $o .= "<div class='hidden-sm hidden-xs action-buttons'>";
        $o .= "<a id='plusn{$node_btn}' class='btn btn-success btn-xs btn_view'><i class='fa fa-plus-circle' aria-hidden='true'></i></a>";
        //	$o .= "<a id='plusr{$node_btn}' class='btn teal btn-xs btn_view color-white' style=\"color: white !important;\"><i class='fa fa-plus' aria-hidden='true'></i></a>";
        $o .= "<a id='reset{$node_btn}' class='btn btn-danger btn-xs btn_view'><i class='fa fa-recycle' aria-hidden='true'></i></a>";
        $o .= '</div>';
        $o .= '</div>';

        $o .= "<div id=\"qc_{$id}\" class=\"qc_{$id}\" style=\"display:none;\"></div>";
        $o .= "<script type='text/javascript'>$(document).ready(function() {mappingPageButtonManipulation('{$node_btn}', '{$id}', '{$target_id}', '{$second_target}', '{$ajax_url}');});</script>";

        return $o;
    }
}

if (! function_exists('canvastack_input')) {

    function canvastack_input($type, $id = null, $class = null, $name = null, $value = null)
    {
        $id = " id=\"{$id}\"";
        $class = " class=\"{$class}\"";
        $value = " value=\"{$value}\"";
        $name = " name=\"{$name}\"";

        return "<input type=\"{$type}\"{$id}{$class}{$value}{$name} />";
    }
}

if (! function_exists('canvastack_script')) {

    function canvastack_script($script, $ready = true)
    {
        if (true === $ready) {
            return "<script type='text/javascript'>$(document).ready(function() { {$script} });</script>";
        } else {
            return "<script type='text/javascript'>{$script}</script>";
        }
    }
}

if (! function_exists('canvastack_merge_request')) {

    /**
     * Controlling Request Before Insert Process
     *
     * @param  object  $request
     * @param  array  $new_data
     * @return object
     *
     * @author: wisnuwidi
     */
    function canvastack_merge_request($request, $new_data = [])
    {
        foreach ($new_data as $field_name => $value) {
            if (null == $request->{$field_name}) {
                $request->merge([$field_name => $value]);
            }
        }

        return $request;
    }
}

if (! function_exists('canvastack_is_softdeletes')) {

    function canvastack_is_softdeletes($model)
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model), true);
    }
}

if (! function_exists('canvastack_attributes_to_string')) {

    /**
     * Attributes To String
     * Helper function used by some of the form helpers
     *
     * @param mixed
     * @return string
     */
    function canvastack_attributes_to_string($attributes)
    {
        if (empty($attributes)) {
            return '';
        }

        if (is_object($attributes)) {
            $attributes = (array) $attributes;
        }

        if (is_array($attributes)) {
            $atts = '';
            foreach ($attributes as $key => $val) {
                $atts .= ' '.$key.'="'.$val.'"';
            }

            return $atts;
        }

        if (is_string($attributes)) {
            return ' '.$attributes;
        }

        return false;
    }
}

if (! function_exists('not_empty')) {

    /**
     * Checking Not Empty Data
     *
     * @param  mixed  $data
     * @return mixed|bool
     */
    function not_empty($data)
    {
        if (isset($data) && ! empty($data) && '' != $data && null != $data) {
            return $data;
        } else {
            return false;
        }
    }
}

if (! function_exists('is_empty')) {

    /**
     * Checking Empty Data
     *
     * @param  mixed  $data
     * @return bool
     */
    function is_empty($data)
    {
        return ! not_empty($data);
    }
}

if (! function_exists('canvastack_get_model_data')) {

    /**
     * Get All Web Preferences
     *
     * created @Aug 21, 2018
     * author: wisnuwidi
     */
    function canvastack_get_model_data($model)
    {
        $data = [];
        foreach ($model::all() as $row) {
            $data = $row->getAttributes();
        }

        return $data;
    }
}

if (! function_exists('canvastack_memory')) {

    /**
     * Memory?
     *
     * created @Sep 28, 2018
     * author: wisnuwidi
     *
     * @param  bool  $min
     * @param  int  $limit
     */
    function canvastack_memory($min = null, $limit = -1)
    {
        ini_set('memory_limit', $limit);
        if (null === $min) {
            minify_code(canvastack_config('minify'));
        } else {
            minify_code($min);
        }
    }
}

if (! function_exists('canvastack_date_info')) {

    function canvastack_date_info($table, $field, $filter = null, $connection = null)
    {
        $query = canvastack_query("SELECT DATE_FORMAT(MAX(`{$field}`), '%d% %M %Y') date_info FROM `{$table}` {$filter}", 'SELECT', $connection);

        return $query[0]->date_info;
    }
}

if (! function_exists('canvastack_get_os')) {

    function canvastack_get_os($user_agent)
    {
        $os_platform = 'Unknown OS Platform';
        $os_array = [
            '/windows nt 11/i' => 'Windows 11',
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
        ];

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }

        return $os_platform;
    }
}

if (! function_exists('canvastack_get_os')) {

    function canvastack_get_os($user_agent)
    {
        $browser = 'Unknown Browser';
        $browser_array = [
            '/msie/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/edge/i' => 'Edge',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser',
        ];

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
            }
        }

        return $browser;
    }
}

<?php

namespace Canvastack\Canvastack\Library\Components\Utility;

use Canvastack\Canvastack\Library\Components\Utility\Html\ElementExtractor;
use Canvastack\Canvastack\Library\Components\Utility\Assets\AssetPath;
use Canvastack\Canvastack\Library\Components\Utility\Url\PathResolver;
use Canvastack\Canvastack\Library\Components\Utility\Html\TableUi;
use Canvastack\Canvastack\Library\Components\Utility\Html\FormUi;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session as SessionFacade;
use Illuminate\Support\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

/**
 * Static facade for Utility methods.
 *
 * U1: Skeleton only — delegates to placeholder implementations.
 * U2/U3: Fill implementations to match legacy behavior.
 */
class Canvatility
{
    public static function elementValue(string $html, string $tag, string $attr, bool $asHTML = true): ?string
    {
        return ElementExtractor::elementValue($html, $tag, $attr, $asHTML);
    }

    // Data: build select options from iterable
    public static function selectOptionsFromData($object, $key_value, $key_label, $set_null_array = true): array
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Data\SelectOptions::fromData($object, $key_value, $key_label, $set_null_array);
    }

    public static function assetBasePath(): string
    {
        return AssetPath::assetBasePath();
    }

    public static function checkStringPath(string $path, bool $existCheck = false): ?string
    {
        return PathResolver::checkStringPath($path, $existCheck);
    }

    // HTML helpers (generic)
    public static function attributesToString($attributes): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\Attributes::toString($attributes);
    }

    // Html/Form UI (H2)
    public static function formButton($name, $label = false, $action = [], $tag = 'button', $link = false, $color = 'white', $border = false, $size = false, $disabled = false, $icon_name = false, $icon_color = false): string
    {
        return FormUi::button($name, $label, $action, $tag, $link, $color, $border, $size, $disabled, $icon_name, $icon_color);
    }

    public static function formCheckList($name, $value = false, $label = false, $checked = false, $class = 'success', $id = false, $inputNode = null): string
    {
        return FormUi::checkList($name, $value, $label, $checked, $class, $id, $inputNode);
    }

    public static function formSelectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => 'Select'])
    {
        return FormUi::selectbox($name, $values, $selected, $attributes, $label, $set_first_value);
    }

    public static function formAlertMessage($message = 'Success', $type = 'success', $title = 'Success', $prefix = 'fa-check', $extra = false): string
    {
        return FormUi::alertMessage($message, $type, $title, $prefix, $extra);
    }

    public static function formCreateHeaderTab($data, $pointer, $active = false, $class = false): string
    {
        return FormUi::createHeaderTab($data, $pointer, $active, $class);
    }

    public static function formCreateContentTab($data, $pointer, $active = false): string
    {
        return FormUi::createContentTab($data, $pointer, $active);
    }

    public static function formChangeInputAttribute($attribute, $key = false, $value = false)
    {
        return FormUi::changeInputAttribute($attribute, $key, $value);
    }

    // Table UI
    public static function modalContentHtml(string $name, string $title, array $elements): string
    {
        return TableUi::modalContentHtml($name, $title, $elements);
    }

    public static function addActionButtonByString($action, bool $is_array = false): array
    {
        return TableUi::addActionButtonByString($action, $is_array);
    }

    public static function createActionButtons($view = false, $edit = false, $delete = false, $add_action = [], $as_root = false): string
    {
        return TableUi::createActionButtons($view, $edit, $delete, $add_action, $as_root);
    }

    public static function tableRowAttr(string $value, $attributes): string
    {
        return TableUi::tableRowAttr($value, $attributes);
    }

    public static function tableColumn(array $header, int $hIndex, $hList): string
    {
        return TableUi::tableColumn($header, $hIndex, $hList);
    }

    public static function generateTable($title = false, $title_id = false, array $header = [], array $body = [], array $attributes = [], $numbering = false, $containers = true, $server_side = false, $server_side_custom_url = false): string
    {
        return TableUi::generateTable($title, $title_id, $header, $body, $attributes, $numbering, $containers, $server_side, $server_side_custom_url);
    }

    // Template UI
    public static function templateBreadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::breadcrumb($title, $links, $icon_title, $icon_links, $type);
    }

    // Template UI — convenience aliases via facade
    public static function breadcrumb($title, $links = [], $icon_title = false, $icon_links = false, $type = false): string
    {
        return self::templateBreadcrumb($title, $links, $icon_title, $icon_links, $type);
    }

    // Header action buttons (legacy compatibility for canvastack_action_buttons)
    public static function actionButtonsHeader($routeInfo, string $background_color = 'white'): string
    {
        if (empty($routeInfo) || empty($routeInfo->action_page) || !is_iterable($routeInfo->action_page)) {
            return '';
        }
        $html = "<div class=\"header {$background_color}\">";
        foreach ($routeInfo->action_page as $key => $value) {
            $keys = explode('|', (string)$key);
            $color = $keys[0] ?? 'default';
            $text = $keys[1] ?? 'Action';

            $buttonText = ucwords($text);
            $urlClass = empty($color)
                ? 'btn btn-default btn_create btn-slideright button-app action-button pull-right'
                : "btn btn-{$color} btn_create btn-slideright button-app action-button pull-right";

            $isDanger = (str_contains($text, 'delete') || str_contains($text, 'restore'));
            if (!$isDanger) {
                $url = is_string($value) ? $value : '#';
                $html .= "<h3 class=\"panel-title header-list-panel\"><a href=\"{$url}\" class=\"{$urlClass}\">{$buttonText}</a></h3>";
            } else {
                // Attempt to build a DELETE form action
                $action = '#';
                if (is_string($value)) {
                    if (str_contains($value, '::')) {
                        [$routeName, $id] = explode('::', $value, 2);
                        $id = (int)$id;
                        try { $action = route($routeName, $id); } catch (\Throwable $e) { $action = '#'; }
                    } else {
                        // try treat as route name, fallback to given string as URL
                        try { $action = route($value); } catch (\Throwable $e) { $action = $value; }
                    }
                }
                $token = function_exists('csrf_token') ? csrf_token() : '';
                $html .= "<h3 class=\"panel-title header-list-panel\">".
                         "<form action=\"{$action}\" method=\"POST\" onsubmit=\"return confirm('Are you sure?')\">".
                         "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">".
                         "<input type=\"hidden\" name=\"_method\" value=\"DELETE\">".
                         "<button class=\"{$urlClass}\" type=\"submit\">{$buttonText}</button>".
                         "</form>".
                         "</h3>";
            }
        }
        $html .= '</div>';
        return $html;
    }

    // Form wrapper (phase-out LaravelCollective usage via single facade)
    public static function formOpen(array $attributes = []): string
    {
        return \Collective\Html\FormFacade::open($attributes);
    }

    public static function formClose(): string
    {
        return \Collective\Html\FormFacade::close();
    }

    public static function formLabel($name, $value = null, $options = [], $escape_html = true): string
    {
        // Keep the default behavior of Collective (escape by default)
        return \Collective\Html\FormFacade::label($name, $value, $options, $escape_html);
    }

    public static function formText($name, $value = null, $options = []): string
    {
        return \Collective\Html\FormFacade::text($name, $value, $options);
    }

    public static function formPassword($name, $options = []): string
    {
        return \Collective\Html\FormFacade::password($name, $options);
    }

    public static function formSubmit($value = null, $options = []): string
    {
        return \Collective\Html\FormFacade::submit($value, $options);
    }

    public static function csrfField(): string
    {
        return \Collective\Html\FormFacade::token();
    }

    public static function js($scripts, $position = 'bottom', $as_script_code = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::js($scripts, $position, $as_script_code);
    }

    public static function css($scripts, $position = 'top', $as_script_code = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::css($scripts, $position, $as_script_code);
    }

    public static function grid($name = 'start', $set_column = false, $addHTML = false, $single = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::grid($name, $set_column, $addHTML, $single);
    }

    public static function gridColumn($html, $set_column = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::gridColumn($html, $set_column);
    }

    public static function sidebarContent($media_title, $media_heading = false, $media_sub_heading = false, $type = true): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarContent($media_title, $media_heading, $media_sub_heading, $type);
    }

    public static function sidebarMenuOpen($class_name = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenuOpen($class_name);
    }

    public static function sidebarMenuClose(): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenuClose();
    }

    public static function sidebarMenu($label, $links, $icon = [], $selected = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarMenu($label, $links, $icon, $selected);
    }

    public static function sidebarCategory($title, $icon = false, $icon_position = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::sidebarCategory($title, $icon, $icon_position);
    }

    public static function avatar($username, $link_url = false, $image_src = false, $user_status = 'online', $type_old = false): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\TemplateUi::avatar($username, $link_url, $image_src, $user_status, $type_old);
    }

    // Table: Filters
    public static function filterNormalize(array $filters = []): array
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Table\Filters::normalize($filters);
    }

    // Table: Actions
    public static function tableActionButtons($row_data, string $field_target, string $current_url, $action, $removed_button = null): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Table\Actions::build($row_data, $field_target, $current_url, $action, $removed_button);
    }

    // DB schema helpers
    public static function getAllTables(?string $connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\SchemaTools::getAllTables($connection);
    }

    public static function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\SchemaTools::hasColumn($table, $column, $connection);
    }

    public static function getColumns(string $table, ?string $connection = null): array
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\SchemaTools::getColumns($table, $connection);
    }

    public static function getColumnType(string $table, string $column, ?string $connection = null): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\SchemaTools::getColumnType($table, $column, $connection);
    }

    // JSON helpers
    public static function clearJson(string $json): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Json\JsonTools::clear($json);
    }

    // Upload/Image validation helpers
    public static function imageValidations(int $maxKb = 2048, array $mimes = ['jpeg','jpg','png','gif','webp']): string
    {
        // Prefer configured defaults if available
        $cfgMax = (int) (self::config('upload_max_kb', 'settings') ?? 0);
        $cfgMimes = self::config('upload_mimes', 'settings');
        if ($cfgMax > 0 && $maxKb === 2048) {
            $maxKb = $cfgMax;
        }
        if (is_array($cfgMimes) && !empty($cfgMimes)) {
            $mimes = $cfgMimes;
        }

        // Build Laravel validation rule string, keeping BC with legacy usage (KB)
        $mimeStr = implode(',', $mimes);
        return "nullable|image|mimes:{$mimeStr}|max:{$maxKb}";
    }

    public static function toKilobytes(int $size): int
    {
        // Back-compat shim: legacy already passed KB values (e.g., 2000), so return as-is
        return $size;
    }

    // Config enums
    public static function configActiveBox(bool $en = true): array
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Config\Enums::activeBox($en);
    }

    public static function configRequestStatus(bool $en = true, $num = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Config\Enums::requestStatus($en, $num);
    }

    public static function configActiveValue($value): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Config\Enums::activeValue($value);
    }

    // Html: icon attributes parser
    public static function formIconAttributes(string $string, array $attributes = [], string $pos = 'left'): array
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Html\IconAttributes::parse($string, $attributes, $pos);
    }

    // Runtime: client ip
    public static function clientIp(): string
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Runtime\Client::ip();
    }

    // H4 — App/Core utils
    public static function config(string $key, string $file = 'settings')
    {
        return Config::get("canvastack.{$file}.{$key}");
    }

    public static function isMultiplatform(): bool
    {
        return self::config('platform_type') !== 'single';
    }

    public static function session($method = 'all', $data = [])
    {
        return empty($data) ? SessionFacade::{$method}() : SessionFacade::{$method}($data);
    }

    public static function redirect($to, $message = null, $status = false)
    {
        $redirect = (str_contains($to, 'http://') || str_contains($to, 'https://')) ? $to : RedirectFacade::to(url()->current()."/{$to}");
        if (!empty($message)) {
            $redirect = $redirect->with('message', $message);
        }
        if (!empty($status)) {
            $redirect = $redirect->with($status, true);
        }
        return $redirect;
    }

    public static function routeInfo(?string $route = null): array
    {
        $current = $route ? explode('.', $route) : explode('.', self::currentRouteName());
        $last = array_pop($current);
        return ['base_info' => implode('.', $current), 'last_info' => $last];
    }

    public static function assetBase(): string
    {
        return self::config('baseURL').'/'.self::config('base_template').'/'.self::config('template');
    }

    public static function toObject($array)
    {
        return (object) $array;
    }

    public static function cleanString($string, string $replace = '-')
    {
        $string = trim(preg_replace('/[;\.\/\?\\:@&=+\$, _\~\*\'"\!\|%<>\{\}\^\[\]`\-]/', ' ', $string));
        return strtolower(preg_replace('/\s+/', $replace, $string));
    }

    public static function isCollection($object): bool
    {
        return $object instanceof \Illuminate\Support\Collection;
    }

    public static function formatNumber($data, int $decimals = 0, string $separator = '.', string $type = 'number')
    {
        if ($data === null || $data === '') return null;
        $seps = ($separator === '.') ? [',', '.'] : ['.', ','];
        if ($type === 'decimal' || $decimals > 0) return number_format($data, $decimals, $seps[0], $seps[1]);
        return number_format($data, 0, $seps[0], $seps[1]);
    }

    public static function getModel($model, $find = false)
    {
        if (is_string($model)) $model = new $model;
        if ($find !== false) $model = $model->find($find);
        return $model;
    }

    public static function query($sql, $type = 'TABLE', $connection = null)
    {
        return $connection ? DB::connection($connection)->{$type}($sql) : DB::{$type}($sql);
    }

    public static function schema($method, $data = null)
    {
        return $data !== null ? Schema::{$method}($data) : Schema::{$method}();
    }

    public static function db($method, $data = null)
    {
        return $data !== null ? DB::{$method}($data) : DB::{$method}();
    }

    public static function tableFromSql(string $sql): string
    {
        $parts = explode('from ', strtolower($sql));
        $table = explode(' ', $parts[1] ?? '');
        return $table[0] ?? '';
    }

    public static function encrypt($string)
    {
        return Crypt::encryptString($string);
    }

    public static function decrypt($string)
    {
        return Crypt::decryptString($string);
    }

    public static function userCryptCode($user_name, $user_email)
    {
        return self::encrypt($user_name.self::config('encode_separate').$user_email);
    }

    public static function contains($string, $find): bool
    {
        if (is_array($find)) {
            foreach ($find as $f) if (strpos($string, $f) !== false) return true;
            return false;
        }
        return strpos($string, $find) !== false;
    }

    public static function underscoreToCamelcase($str)
    {
        if (str_contains($str, '_')) {
            $chunks = array_map(function($s){ return strlen($s) <= 3 ? strtoupper($s) : ucwords($s); }, explode('_', $str));
            return ucwords(implode(' ', $chunks));
        }
        return ucwords($str);
    }

    public static function url(string $method)
    {
        return url()->{$method}();
    }

    public static function arrayContainsString(array $array, $needle, $return_data = false)
    {
        $result = ['status' => false, 'data' => []];
        foreach ($array as $k => $v) {
            if (self::contains($v, $needle)) {
                $result['status'] = true;
                if ($return_data !== false) {
                    if (is_array($needle)) {
                        // collect matched needles for this entry
                        $matched = [];
                        foreach ($needle as $n) if (self::contains($v, $n)) $matched[] = $n;
                        $result['data'][$k] = count($matched) <= 1 ? ($matched[0] ?? null) : $matched;
                    } else {
                        $result['data'][$k] = $needle;
                    }
                }
            }
        }
        return $return_data !== false ? $result['data'] : $result['status'];
    }

    public static function arrayToObjectRecursive($array)
    {
        if (is_array($array)) { foreach ($array as $k => $v) { $array[$k] = self::arrayToObjectRecursive($v); } return (object) $array; }
        return $array;
    }

    public static function arrayInsertNew($array, int $index, $val)
    {
        $size = count($array); if (!is_int($index) || $index < 0 || $index > $size) return -1;
        $temp = array_slice($array, 0, $index); $temp[] = $val; return array_merge($temp, array_slice($array, $index, $size));
    }

    public static function arrayInsert(&$array, $position, $insert)
    {
        if (is_int($position)) { array_splice($array, $position, 0, $insert); }
        else { $pos = array_search($position, array_keys($array)); $array = array_merge(array_slice($array, 0, $pos), $insert, array_slice($array, $pos)); }
    }

    public static function urlExists($url): bool
    {
        $headers = @get_headers($url);
        return $headers && $headers[0] !== 'HTTP/1.1 404 Not Found';
    }

    public static function notEmpty($data)
    {
        return (isset($data) && $data !== '' && $data !== null) ? $data : false;
    }

    public static function isEmpty($data): bool
    {
        return ! self::notEmpty($data);
    }

    public static function camelCase($string)
    {
        return ucfirst($string);
    }

    public static function randomString($length = 8, $symbol = true, $prefix = null, $node = '_')
    {
        $symbols = $symbol ? '!@#$%' : '';
        $pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789{$symbols}";
        $out = '';
        for ($i=0,$n=strlen($pool); $i<$length; $i++) $out .= $pool[rand(0,$n-1)];
        return $prefix ? $prefix.$node.$out : $out;
    }

    public static function unescapeHtml($html)
    {
        return new HtmlString($html);
    }

    public static function currentRouteName(): string
    {
        return Route::currentRouteName() ?? '';
    }

    public static function currentRoute($facadeRoot = false)
    {
        return $facadeRoot ? Route::getFacadeRoot() : Route::getCurrentRoute();
    }

    public static function currentBaseRoute()
    {
        $route = self::currentRoute();
        $name = is_object($route) && method_exists($route, 'getName') ? ($route->getName() ?? '') : '';
        if ($name === '') return '';
        $last = self::lastExplode('.', $name);
        return str_replace(".$last", '', $name);
    }

    public static function lastExplode($delimiter, $string)
    {
        $parts = explode($delimiter, $string);
        return end($parts);
    }

    public static function currentRouteId(bool $exclude_last = true)
    {
        $url = self::currentUrl();
        if ($url === '') return 0;
        $segments = explode('/', $url);
        if ($exclude_last) array_pop($segments);
        $last = end($segments);
        return is_numeric($last) ? intval($last) : 0;
    }

    public static function currentUrl(): string
    {
        try {
            return url()->current();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function logActivity($routeInfo = [], $data = [])
    {
        $cfg = self::config('log_activity');
        if (empty($cfg) || !in_array($cfg['run_status'], [true, 'unexceptions'])) return;
        $sessions = session()->all(); if (!empty($data)) $sessions = $data;
        if (empty($sessions['user_group'])) return;
        $routes = empty($routeInfo) ? self::currentRoute() : null;
        $requests = \Illuminate\Support\Facades\Request::class;
        $group_ex = ['root']; if (!empty($cfg['exceptions']['groups'])) $group_ex = array_merge(['root'], $cfg['exceptions']['groups']);
        if ($cfg['run_status'] === 'unexceptions') $group_ex = [];
        if (in_array($sessions['user_group'], $group_ex)) return;
        $current_controller = empty($routeInfo) ? 
            (isset($routes->action['controller']) ? explode('@', $routes->action['controller']) : ['']) : 
            explode('@', $routeInfo['controller']);
        if (in_array($current_controller[0], $cfg['exceptions']['controllers'])) return;
        $logs = [
            'user_id' => $sessions['id'],
            'username' => $sessions['username'],
            'user_fullname' => $sessions['fullname'],
            'user_email' => $sessions['email'],
            'user_group_id' => $sessions['group_id'],
            'user_group_name' => $sessions['user_group'],
            'user_group_info' => $sessions['group_info'],
        ];
        if (empty($routeInfo)) {
            $logs['route_path'] = $routes->controller->data['route_info']->current_path ?? null;
            $logs['module_name'] = $routes->controller->data['route_info']->module_name ?? null;
            $logs['page_info'] = $routes->controller->data['route_info']->page_info ?? null;
        } else {
            $logs['route_path'] = $routeInfo['current_path'];
            $logs['module_name'] = $routeInfo['module_name'];
            $logs['page_info'] = $routeInfo['page_info'];
        }
        $logs['urli'] = $requests::fullUrl();
        $logs['method'] = $requests::method();
        $logs['ip_address'] = $requests::ip();
        $logs['user_agent'] = $requests::header('user-agent');
        $logs['sql_dump'] = null;
        $logs['created_at'] = date('Y-m-d h:i:s');
        $logs['updated_at'] = null;
        \Canvastack\Canvastack\Models\Admin\System\Log::create($logs);
    }

    public static function breakLineHtml($tag, int $loops = 1): string
    {
        return str_repeat($tag, max(0, $loops-1));
    }

    public static function sanitizeOutput($buffer)
    {
        $search = ['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'];
        $replace = [' ', ''];
        return str_repeat("\n", 1986).preg_replace($search, $replace, $buffer);
    }

    public static function minifyCode($output = true)
    {
        if ($output !== false) { ob_start([self::class, 'sanitizeOutput']); }
    }

    // H5 — Runtime: memory/time limit control
    public static function memory(bool $enable = false): void
    {
        if ($enable) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(120);
        }
    }

    // H6 — Chart script generator (Highcharts-style), parity with legacy helper
    public static function chartScript($type = 'line', $identity = null, $title = null, $subtitle = null, $xAxis = null, $yAxis = null, $tooltips = null, $legends = null, $series = null)
    {
        $chartType = "chart: {type: '{$type}'},";
        $tableName = 'report_data_summary_program_free_sp_3gb';
        try {
            $route = function_exists('canvastack_current_route') ? \canvastack_current_route() : null;
            $current_url = $route && isset($route->uri) ? url($route->uri) : url()->current();
        } catch (\Throwable $e) {
            $current_url = url()->current();
        }
        $link_url = "renderCharts=true&difta[name]={$tableName}&difta[source]=dynamics";
        $chartURI = "{$current_url}?{$link_url}";
        $series = str_replace('series:', '', (string) $series);
        return "
<script type=\"text/javascript\">\n$.ajax({\n\turl: '{$chartURI}',\n\ttype: 'get',\n\tdata: {$series},\n\tsuccess: function (data) {\n\t\tconsole.log(data);\n\t},\n\terror: function(jqXHR, textStatus, errorThrown) {\n\t\tconsole.log(textStatus, errorThrown);\n\t}\n});\n$(function() {\n    $('#{$identity}').highcharts({\n        {$chartType}\n        {$title}\n        {$subtitle}\n        {$xAxis}\n        {$yAxis}\n        {$tooltips}\n        {$legends}\n        series:{$series} \n    });\n});\n</script>";
    }
}
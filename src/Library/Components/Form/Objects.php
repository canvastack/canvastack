<?php

namespace Canvastack\Canvastack\Library\Components\Form;

use Canvastack\Canvastack\Library\Components\Form\Elements\Check;
use Canvastack\Canvastack\Library\Components\Form\Elements\DateTime;
use Canvastack\Canvastack\Library\Components\Form\Elements\File;
use Canvastack\Canvastack\Library\Components\Form\Elements\Radio;
use Canvastack\Canvastack\Library\Components\Form\Elements\Select;
use Canvastack\Canvastack\Library\Components\Form\Elements\Tab;
use Canvastack\Canvastack\Library\Components\Form\Elements\Text;
use Collective\Html\FormFacade as Form;
use Collective\Html\HtmlFacade as Html;

/**
 * Created on 16 Mar 2021
 * Time Created	: 17:55:08
 *
 * @filesource	Objects.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Objects
{
    use Text, DateTime, Select, File, Check, Radio, Tab;

    public $model;
    public $elements = [];
    public $element_name = [];
    public $element_plugins = [];
    public $params = [];
    public $validations = [];

    /**
     * --[openTabHTMLForm]--
     */
    private $opentabHTML = '--[openTabHTMLForm]--';

    private $currentRoute;
    private $currentRouteArray;
    public $currentRouteName;

    public function __construct()
    {
        $this->getCurrentRoute();
    }

    public function setValidations($data = [])
    {
        $this->validations = $data;
    }

    protected function getCurrentRoute()
    {
        $this->currentRoute = current_route();
        $this->currentRouteArray = explode('.', $this->currentRoute);
        $this->currentRouteName = last($this->currentRouteArray);
    }

    /**
     * Draw Data Elements
     *
     * @param  array  $data
     *
     * @author: wisnuwidi
     */
    public function draw($data = [])
    {
        if ($data) {
            $this->elements[] = $data;
        }
    }

    public function render($object)
    {
        $tabObj = '';
        if (true === is_array($object)) {
            $tabObj = implode('', $object);
        }

        if (true === canvastack_string_contained($tabObj, $this->opentabHTML)) {
            return $this->renderTab($object);
        } else {
            return $object;
        }
    }

    private function setActionRoutePath()
    {
        if (str_contains(current_route(), '.create')) {
            // auto generate if we use Route::resource
            return str_replace('.create', '.store', current_route());
        } elseif (str_contains(current_route(), '.edit')) {
            // auto generate if we use Route::resource
            return str_replace('.edit', '.update', current_route());
        } else {
            return current_route();
        }
    }

    /**
     * Draw Form Open
     *
     * @param  string  $path
     * 		: ['url/path', 'route.name', 'Controller@method']
     * @param  string  $method
     * 		: ['POST', 'GET', 'PUT', 'DELETE']
     * 		: would render 'POST', by default
     * @param  string  $type
     * 		: ['url', 'route', 'action']
     * 		: would render 'url', by default
     * @param  string  $file
     * 		: false by default
     * 		: would render enctype data if set true
     *
     * @author: wisnuwidi
     */
    public function open($path = false, $method = false, $type = false, $file = false)
    {
        $array = [];
        $array['files'] = $file;

        if (false === $path) {
            $path = $this->setActionRoutePath();
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
        if (false !== $method) {
            $array['method'] = $method;
        }

        $this->draw(Form::open($array).'<div class="form-container">');
    }

    private $method = 'PUT';

    public function method($method)
    {
        $this->method = $method;
    }

    public $identity = null;

    public $modelToView = false;

    /**
     * Draw Form Model Binding
     *
     * @param  string  $model
     * 		: Model(object) Name, example: $user
     * 		: if null	=> check $this->model set by protected function model($class)
     * 					=> in CoreControler [ from Craft-Action trait ]
     * @param  string  $row_selected
     * 		: Row selected (example: id) from model
     * @param  string  $path
     * 		: ['route.name', 'Controller@method']
     * 		: [ note ] if this parameter set as false, so it will draw view mode.
     * 		  It would set modelToView as true,
     * 		  disabling action buttons and replace the input tags to text view
     * @param  bool  $file
     * @param  string  $type
     * 		: ['route', 'action']
     * 		: would render 'route', by default
     *
     * @author: wisnuwidi
     */
    public function model($model = null, $row_selected = false, $path = false, $file = false, $type = false)
    {
        $this->alert_message();
        if ('show' !== $this->currentRouteName) {
            if (str_contains(current_route(), 'edit')) {
                $sliceURL = explode('/', canvastack_current_url());
                unset($sliceURL[array_key_last($sliceURL)]);
                $row_selected = intval($sliceURL[array_key_last($sliceURL)]);
            }

            // Check if $model = null
            if (empty($model)) {
                // check $this->model set by protected function model($class) in CoreControler [ from Action trait ]
                $modelData = null;
                if (! empty($this->model)) {
                    if (is_string($this->model)) {
                        $modelData = new $this->model();
                    } else {
                        $modelData = $this->model;
                    }
                }

                if (! empty($row_selected)) {
                    $model = $modelData->find($row_selected);
                } else {
                    $model = $modelData;
                }
            }

            if (false === $path) {
                $path = $this->setActionRoutePath();
            }

            $model_path = canvastack_random_strings();
            if ('Collection' === class_basename($model)) {
                foreach ($model as $items) {
                    $model_path = get_class($items);
                }
            }

            if ('Builder' === class_basename($model)) {
                $model_path = get_class($model->getModel());
            }

            $model_uri = canvastack_random_strings().'___'.str_replace('\\', '.', $model_path).'___'.canvastack_random_strings();
            $model_enc = encrypt($model_uri);
            $model_name = $model_enc;

            if (false === $type) {
                $type = 'route';
            }
            if (false !== $file) {
                if (false !== $row_selected) {
                    $attr = [$type => [$path, $row_selected], 'name' => $model_name, 'method' => $this->method, 'files' => true];
                } else {
                    $attr = [$type => [$path, $row_selected], 'name' => $model_name, 'files' => true];
                }
            } else {
                if (false !== $row_selected) {
                    $attr = [$type => [$path, $row_selected], 'name' => $model_name, 'method' => $this->method];
                } else {
                    $attr = [$type => [$path, $row_selected], 'name' => $model_name];
                }
            }

            $this->identity = $model_name;
            $this->draw(Form::model($model, $attr));
        } else {
            $this->draw(Form::model([]));
        }
    }

    /**
     * Draw Form Model Binding With Multipart Files set True
     *
     * @param  string  $model
     * 		: Model(object) Name, example: $user
     * 		: if null	=> check $this->model set by protected function model($class)
     * 					=> in CoreControler [ from Craft-Action trait ]
     * @param  string  $row_selected
     * 		: Row selected (example: id) from model
     * @param  string  $path
     * 		: ['route.name', 'Controller@method']
     * 		: [ note ] if this parameter set as false, so it will draw view mode.
     * 		  It would set modelToView as true,
     * 		  disabling action buttons and replace the input tags to text view
     * @param  string  $type
     * 		: ['route', 'action']
     * 		: would render 'route', by default
     *
     * @author: wisnuwidi
     */
    public function modelWithFile($model = null, $row_selected = false, $path = false, $type = false)
    {
        return $this->model($model, $row_selected, $path, true, $type);
    }

    /**
     * Draw Form Close Tag
     */
    public function close($action_buttons = false, $option_buttons = false, $prefix = false, $suffix = false)
    {
        if ('show' !== $this->currentRouteName) {
            $options = $option_buttons;
            if (false === $option_buttons) {
                $options = ['class' => 'btn btn-success btn-slideright pull-right btn_create'];
            }

            $object = '';
            if (false !== $action_buttons) {
                $object .= Form::submit($action_buttons, $options);
            }
            $object .= Form::close();

            $this->draw('<div class="diy-action-box">'.$prefix.$object.$suffix.'</div>');
        }
    }

    /**
     * Draw Input Token
     *
     * @author: wisnuwidi
     */
    public function token()
    {
        $this->draw(Form::token());
    }

    /**
     * Create Label
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @return string
     */
    public function label($name, $value, $attributes = [])
    {
        $attributes = ['class' => 'col-sm-3 control-label'];
        $tag = Html::decode(Form::label($name, $value, $attributes));

        return $tag;
    }

    public $syncs = [];

    /**
     * Ajax Relational Fields
     *
     * @param  string  $labels
     */
    public function sync(string $source_field, string $target_field, string $values, string $labels = null, string $query, $selected = null)
    {
        $syncs = [];
        $syncs['source'] = $source_field;
        $syncs['target'] = $target_field;
        $syncs['values'] = encrypt($values);
        $syncs['labels'] = encrypt($labels);
        $syncs['selected'] = encrypt($selected);
        $syncs['query'] = encrypt(trim(preg_replace('/\s\s+/', ' ', $query)));
        $data = json_encode($syncs);
        $ajaxURL = canvastack_get_ajax_urli();

        $this->draw(canvastack_script("ajaxSelectionBox('{$source_field}', '{$target_field}', '{$ajaxURL}', '{$data}');"));
    }

    private function getModelValue($field_name, $function_name)
    {
        $value = null;

        if ('edit' === $this->currentRouteName || 'show' === $this->currentRouteName) {

            $model = [];
            if (! empty($this->model)) {

                if (true === canvastack_is_softdeletes($this->model)) {
                    $model = $this->model::withTrashed()->get();
                } else {
                    $model = $this->model->get();
                }

                $curRoute = canvastack_get_current_route_id();
                if ('show' === $this->currentRouteName) {
                    $curRoute = canvastack_get_current_route_id(false);
                }

                $model = $model->find($curRoute);
                if (! is_null($model->{$field_name})) {
                    $value = $model->{$field_name};
                }
            }

            return $value;
        }

        return false;
    }

    private $paramValue = null;

    private $paramSelected = null;

    /**
     * Set Parameter Value {$this->paramValue} And Selected {$this->paramSelected} By Model
     *
     * @param  string  $function_name
     * @param  string  $name
     * @param  string|int  $value
     * @param  string|array  $selected
     */
    private function setModelValueAndSelectedToParams($function_name, $name, $value, $selected)
    {
        if ('select' === $function_name) {
            if ('create' === $this->currentRouteName) {
                $value = $value;
                $selected = $selected;
            } elseif ('edit' === $this->currentRouteName) {
                $value = $value;
                if (! empty($selected)) {
                    $selected = $selected;
                }
            } else {
                $value = $value;
                if (! empty($value)) {
                    $selected = $selected;
                } else {
                    $selected = $this->getModelValue($name, $function_name);
                }
            }

        } elseif ('checkbox' === $function_name) {
            $value = $value;
            $selected = $this->getModelValue($name, $function_name);
            if (! is_array($selected)) {
                $selected = explode(',', $selected);
                $select = [];
                foreach ($selected as $s) {
                    $select[intval($s)] = intval($s);
                }
                $selected = $select;
            }

        } elseif ('radio' === $function_name) {
            $value = $value;
            $selected = $this->getModelValue($name, $function_name);

        } else {
            $value = $this->getModelValue($name, $function_name);
            $selected = $selected;
        }

        $this->paramValue[$function_name][$name] = $value;
        $this->paramSelected[$function_name][$name] = $selected;
    }

    private $added_attributes = [];

    public function addAttributes($attributes = [])
    {
        $this->added_attributes = $attributes;
    }

    /**
     * Check If Any Validation Attributes Found
     *
     * @param  string  $field_name
     * @param  array  $current_attributes
     * @return array
     */
    protected static function checkValidationAttributes($field_name, $current_attributes = [])
    {
        if (! empty(self::$validation_attributes[$field_name])) {
            return array_unique(array_merge_recursive([self::$validation_attributes[$field_name]], $current_attributes));
        } else {
            return $current_attributes;
        }
    }

    /**
     * Set Input Form Parameters
     *
     * @param  string  $function_name
     * @param  string  $name
     * @param  int|string  $value
     * @param  array  $attributes
     * @param  string  $label
     * @param  bool  $selected
     */
    private function setParams($function_name, $name, $value, $attributes, $label, $selected = false)
    {
        if (true === $label) {
            $label = ucwords(str_replace('-', ' ', ucwords(str_replace('_', ' ', $name))));
        }
        if (! empty($this->added_attributes)) {
            $attributes = array_merge_recursive($attributes, $this->added_attributes);
        }
        if (! canvastack_string_contained(current_route(), 'show')) {
            $attributes = self::checkValidationAttributes($name, $attributes);
        }

        $this->setModelValueAndSelectedToParams($function_name, $name, $value, $selected);
        $this->params[$function_name][$name] = [
            'label' => $label,
            'value' => $this->paramValue[$function_name][$name],
            'selected' => $this->paramSelected[$function_name][$name],
            'attributes' => $attributes,
        ];

        $this->element_name[$name] = $function_name;
    }

    private function inputDraw($function_name, $name)
    {
        if (in_array($name, $this->excludeFields)) {
            return false;
        }

        $inputTag = '';
        $labelTag = '';

        $req_symbol = false;
        $label = false;
        $value = false;
        $attributes = [];
        $hideClass = false;

        if (in_array($name, $this->hideFields)) {
            $hideClass = ' hide';
            $attributes = canvastack_form_change_input_attribute($attributes, 'class', trim($hideClass));
        }

        $paramData = $this->params[$function_name][$name];
        if (! empty($paramData)) {
            $label = $paramData['label'];
            if ('password' === $function_name) {
                $value = bcrypt($paramData['value']);
            } else {
                $value = $paramData['value'];
            }
            $attributes = $paramData['attributes'];
        }
        $attributes = canvastack_form_change_input_attribute($attributes, 'class', 'form-control');

        if (true === in_array('required', $attributes) || true === in_array('required', array_keys($attributes))) {
            $req_symbol = ' <font class="required" title="This Required Field cannot be Leave Empty!"><sup>(</sup><strong>*</strong><sup>)</sup></font>';
        }
        $labelValue = $label.$req_symbol;

        $labelTag .= $this->label($name, $labelValue, $attributes);
        $inputTag .= $this->inputTag($function_name, $name, $attributes, $value);

        $inputForm = "<div class=\"form-group row{$hideClass}\">{$labelTag}{$inputTag}</div>";

        return $this->draw($inputForm);
    }

    private function inputTag($function_name, $name, $attributes, $value)
    {

        if ('file' === $function_name) {
            if (! empty($this->params[$function_name][$name]['value'])) {
                $attributes['value'] = $this->params[$function_name][$name]['value'];
            }

            return $this->inputFile($name, $attributes);
        }

        if ('select' === $function_name) {
            $selected = $this->params[$function_name][$name]['selected'];

            return '<div class="input-group col-sm-9">'.Form::select($name, $value, $selected, $attributes).'</div>';
        }

        if ('checkbox' === $function_name) {
            $selected = $this->params[$function_name][$name]['selected'];

            return '<div class="input-group col-sm-9">'.$this->drawCheckBox($name, $value, $selected, $attributes).'</div>';
        }

        if ('radio' === $function_name) {
            $selected = $this->params[$function_name][$name]['selected'];

            return '<div class="input-group col-sm-9">'.$this->drawRadioBox($name, $value, $selected, $attributes).'</div>';
        }

        if ('date' === $function_name || 'datetime' === $function_name || 'daterange' === $function_name || 'time' === $function_name || 'tagsinput' === $function_name) {
            $function_name = 'text';
        }

        if ('password' === $function_name) {
            return '<div class="input-group col-sm-9">'.Form::{$function_name}($name, $attributes).'</div>';
        }

        return '<div class="input-group col-sm-9">'.Form::{$function_name}($name, $value, $attributes).'</div>';
    }

    protected static $validation_attributes = [];

    /**
     * Create Simple Alert Message
     *
     * @param  string  $message
     * @param  string  $type
     * @param  string  $title
     * @param  string  $prefix
     * @param  bool  $extra
     *
     * @author: wisnuwidi
     */
    private function alert_message($data = [])
    {
        if (! empty($this->validations)) {
            $checkRequired = canvastack_array_contained_string($this->validations, 'required', true);
            if (! empty($checkRequired)) {
                self::$validation_attributes = $checkRequired;
            }
        }

        $current_data = [];
        if (! empty($data)) {
            $current_data = ['current_data' => $data->getAttributes()];
        }
        $session_messages = [];
        if (! is_empty(canvastack_sessions('get', 'message'))) {
            $session_messages = canvastack_sessions('get', 'message');
        }
        $session_status = null;
        if (! is_empty(canvastack_sessions('get', 'status'))) {
            $session_status = canvastack_sessions('get', 'status');
        }

        $param_method = null;
        if (! empty($current_data)) {
            if (! empty($session_messages['message']['_method'])) {
                $param_method = $session_messages['message']['_method'];
                canvastack_sessions($param_method, $current_data);
            }
        }

        $status = [];
        $status['message'] = 'Success';
        $status['type'] = 'success';
        $status['prefix'] = 'fa-exclamation-triangle';
        if (! empty($session_messages['message'])) {
            $status['message'] = $session_messages['message'];
        }
        if (! empty($session_status) && 'failed' === $session_status) {
            $status['type'] = 'warning';
        }
        $status['label'] = ucwords($status['type']);

        if (! empty($session_messages)) {
            $this->draw(canvastack_form_alert_message($status['message'], $status['type'], $status['label'], $status['prefix'], false));
        }
    }
}

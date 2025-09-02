<?php

use Collective\Html\FormFacade;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

/**
 * Created on 16 Mar 2021
 * Time Created	: 03:17:49
 *
 * @filesource	FormObject.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
if (! function_exists('canvastack_form_check_str_attr')) {

    /**
     * Check String Contains In Attribute
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  array  $attributes
     * @param  string  $string
     * @return bool
     */
    function canvastack_form_check_str_attr($attributes, $string)
    {
        if ((isset($attributes['class']) &&
                true === str_contains($attributes['class'], $string)) ||
            isset($attributes['id']) &&
                true === str_contains($attributes['id'], $string)
        ) {
            return true;
        }
    }
}

if (! function_exists('canvastack_form_button')) {

    /**
     * Button Builder (delegates to Utility H2)
     */
    function canvastack_form_button(
        $name,
        $label = false,
        $action = [],
        $tag = 'button',
        $link = false,
        $color = 'white',
        $border = false,
        $size = false,
        $disabled = false,
        $icon_name = false,
        $icon_color = false
    ) {
        return Canvatility::formButton($name, $label, $action, $tag, $link, $color, $border, $size, $disabled, $icon_name, $icon_color);
    }
}

if (! function_exists('canvastack_form_change_input_attribute')) {

    /**
     * Change/Add Input Class Name Attribute (delegates)
     */
    function canvastack_form_change_input_attribute($attribute, $key = false, $value = false)
    {
        return Canvatility::formChangeInputAttribute($attribute, $key, $value);
    }
}

if (! function_exists('canvastack_form_set_icon_attributes')) {

    /**
     * Set Icon Attribute for Inputbox
     *
     * @param  string  $string
     * @param  string  $attributes
     * @return array ['name', 'data']
     */
    function canvastack_form_set_icon_attributes($string, $attributes = [], $pos = 'left')
    {
        // Delegate to Utility; keep return as array to avoid unknown canvastack_object() coupling
        return Canvatility::formIconAttributes((string) $string, (array) $attributes, (string) $pos);
    }
}

if (! function_exists('canvastack_form_active_box')) {

    /**
     * Active Status Combobox Value
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  bool  $en
     * @return string['No', 'Yes']
     */
    function canvastack_form_active_box($en = true)
    {
        return Canvatility::configActiveBox((bool) $en);
    }
}

if (! function_exists('canvastack_form_checkList')) {

    /**
     * Simple Checkbox List Builder
     *
     * @param  mixed  $name
     * @param  string  $value
     * @param  string  $label
     * @param  string  $checked
     * @param  string  $class
     * @param  string  $id
     * @return string
     */
    function canvastack_form_checkList($name, $value = false, $label = false, $checked = false, $class = 'success', $id = false, $inputNode = null)
    {
        return Canvatility::formCheckList($name, $value, $label, $checked, $class, $id, $inputNode);
    }
}

if (! function_exists('canvastack_form_selectbox')) {

    function canvastack_form_selectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => 'Select'])
    {
        return Canvatility::formSelectbox($name, $values, $selected, $attributes, $label, $set_first_value);
    }
}

if (! function_exists('canvastack_form_alert_message')) {

    function canvastack_form_alert_message($message = 'Success', $type = 'success', $title = 'Success', $prefix = 'fa-check', $extra = false)
    {
        return Canvatility::formAlertMessage($message, $type, $title, $prefix, $extra);
    }
}

if (! function_exists('canvastack_form_create_header_tab')) {

    /**
     * HTML Header Tab Builder
     *
     * @param  string  $data
     * @param  string  $pointer
     * @param  string  $active
     * @param  string  $class
     * @return string
     */
    function canvastack_form_create_header_tab($data, $pointer, $active = false, $class = false)
    {
        return Canvatility::formCreateHeaderTab($data, $pointer, $active, $class);
    }
}

if (! function_exists('canvastack_form_create_content_tab')) {

    /**
     * HTML Content Tab Builder
     *
     * @param  string  $data
     * @param  string  $pointer
     * @param  string  $active
     * @return string
     */
    function canvastack_form_create_content_tab($data, $pointer, $active = false)
    {
        return Canvatility::formCreateContentTab($data, $pointer, $active);
    }
}

if (! function_exists('canvastack_form_set_active_value')) {

    /**
     * Set Active Value
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @param  int|string  $value
     * @return string
     */
    function canvastack_form_set_active_value($value)
    {
        return Canvatility::configActiveValue($value);
    }
}

if (! function_exists('canvastack_form_internal_flag_status')) {

    /**
     * Set Flag Status Value
     *
     * created @Sep 7, 2018
     * author: wisnuwidi
     *
     * @param  int|string  $flag_row
     * @return string
     */
    function canvastack_form_internal_flag_status($flag_row)
    {
        return internal_flag_status((int) $flag_row);
    }
}

if (! function_exists('canvastack_form_request_status')) {

    /**
     * Request Status For Combobox Value
     *
     * created @Sep 21, 2018
     * author: wisnuwidi
     *
     * @param  bool  $en
     * @param  bool|int  $num
     * @return string['Pending', 'Accept', 'Blocked', 'Banned']
     */
    function canvastack_form_request_status($en = true, $num = false)
    {
        return Canvatility::configRequestStatus((bool) $en, $num);
    }
}

if (! function_exists('canvastack_form_get_client_ip')) {

    /**
     * Get Client IP
     *
     * author: https://stackoverflow.com/questions/15699101/get-the-client-ip-address-using-php
     *
     * @return string
     * created @Dec 29, 2018
     */
    function canvastack_form_get_client_ip()
    {
        return Canvatility::clientIp();
    }
}

if (! function_exists('canvastack_selectbox')) {

    /**
     * Set Default Combobox Data
     *
     * @param  array  $object
     * @param  string  $key_value
     * @param  string  $key_label
     * @param  string  $set_null_array
     * @return array
     */
    function canvastack_selectbox($object, $key_value, $key_label, $set_null_array = true)
    {
        // Delegate to Utility: map objects to select options
        return Canvatility::selectOptionsFromData($object, $key_value, $key_label, $set_null_array);
    }
}

<?php

namespace Canvastack\Canvastack\Library\Components\Form\Elements;

/**
 * Created on 19 Mar 2021
 * Time Created	: 03:10:46
 *
 * @filesource	Text.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Text
{
    /**
     * Create Input Text
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function text($name, $value = null, $attributes = [], $label = true)
    {
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Textarea
     *
     * @param  string  $name
     * 	format maxlength: textarea_name|limit:100
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function textarea($name, $value = null, $attributes = [], $label = true)
    {

        if (canvastack_form_check_str_attr($attributes, 'ckeditor')) {
            $this->element_plugins[$name] = 'ckeditor';
        }
        if (true === str_contains($name, '|')) {
            $_name = explode('|', $name);
            $_attr = [];

            if (true === str_contains($_name[1], ':')) {
                $_limiter = explode(':', $_name[1]);
                $_attr = array_merge($attributes, ['class' => 'form-control bootstrap-maxlength character-limit', 'maxlength' => $_limiter[1], 'placeholder' => "{$_limiter[1]} character limit"]);
            }

            $name = $_name[0];
            $attributes = array_merge($_attr, $attributes);
        }

        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Email
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function email($name, $value = null, $attributes = [], $label = true)
    {
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Number
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function number($name, $value = null, $attributes = [], $label = true)
    {
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Password
     *
     * @param  string  $name
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function password($name, $attributes = [], $label = true)
    {
        $this->setParams(__FUNCTION__, $name, null, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Generate Input Tags
     *
     * @param  string  $name
     * 		: format	= input_name|input_icon_name|input_icon_position
     * 		: example	= input_text|microphone|right
     * 		: default input_icon_position is left
     * @param  string  $value
     * @param  array  $attributes
     * @param  string  $label
     *
     * @author: wisnuwidi
     */
    public function tags($name, $value = null, $attributes = [], $label = true)
    {
        $placeholder = ucwords(str_replace('-', ' ', canvastack_clean_strings($name)));
        $attributes = canvastack_form_change_input_attribute($attributes, 'data-role', 'tagsinput');
        $attributes = canvastack_form_change_input_attribute($attributes, 'placeholder', "Type {$placeholder}");

        $this->setParams('tagsinput', $name, $value, $attributes, $label);
        $this->inputDraw('tagsinput', $name);
    }
}

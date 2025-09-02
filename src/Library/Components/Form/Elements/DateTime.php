<?php

namespace Canvastack\Canvastack\Library\Components\Form\Elements;

/**
 * Created on 19 Mar 2021
 * Time Created	: 03:15:58
 *
 * @filesource	DateTime.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait DateTime
{
    /**
     * Create Input Date
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function date($name, $value = null, $attributes = [], $label = true)
    {
        $attributes = canvastack_form_change_input_attribute($attributes, 'class', 'date-picker');
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Datetime
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function datetime($name, $value = null, $attributes = [], $label = true)
    {
        $attributes = canvastack_form_change_input_attribute($attributes, 'class', 'datetime-picker');
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Daterange
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function daterange($name, $value = null, $attributes = [], $label = true)
    {
        $attributes = canvastack_form_change_input_attribute($attributes, 'class', 'daterange-picker');
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }

    /**
     * Create Input Time
     *
     * @param  string  $name
     * @param  string  $value
     * @param  array  $attributes
     * @param  bool  $label
     */
    public function time($name, $value = null, $attributes = [], $label = true)
    {
        $attributes = canvastack_form_change_input_attribute($attributes, 'class', 'bootstrap-timepicker');
        $this->setParams(__FUNCTION__, $name, $value, $attributes, $label);
        $this->inputDraw(__FUNCTION__, $name);
    }
}

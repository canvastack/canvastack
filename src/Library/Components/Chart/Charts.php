<?php

namespace Canvastack\Canvastack\Library\Components\Chart;

/**
 * Created on Oct 10, 2022
 *
 * Time Created : 1:50:54 PM
 *
 * @filesource	Charts.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Charts
{
    public $attributes = [];

    private function setAttributes($function_name, $attributes)
    {
        $this->attributes[$function_name] = $attributes;
    }

    public function title($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function subtitle($subtitle, $options = [])
    {
        $attributes = ['text' => $subtitle];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function legends($legends = [])
    {
        $this->setAttributes('legend', $legends);
    }

    public function tooltips($tooltips)
    {
        $this->setAttributes('tooltip', $tooltips);
    }

    private function axis($axis = [], $position = 'x', $category = false)
    {
        $categories = [];
        if (true === $category) {
            $categories['category'] = [$position => $category];
            $axis = array_merge_recursive($axis, $categories);
        }

        $this->setAttributes("{$position}Axis", $axis);
    }

    public function xAxis($axis = [], $category = false)
    {
        return $this->axis($axis, 'x', $category);
    }

    public function yAxis($axis = [], $category = false)
    {
        return $this->axis($axis, 'y', $category);
    }

    public function scategory($category, $axis = 'x')
    {
        $this->set_attributes(__FUNCTION__, ['data' => $category, 'axis' => $axis]);
    }

    public function canvas($attributes = [])
    {
        $this->setAttributes(__FUNCTION__, $attributes);
    }
}

<?php

namespace Canvastack\Canvastack\Library\Components\Charts\Canvas;

/**
 * Created on May 25, 2023
 *
 * Time Created : 10:11:30 AM
 *
 * @filesource  Charts.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Charts extends Builder
{
    public $attributes = [];

    public $sync = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function syncWith($object = [])
    {
        if (! empty($object) && ! empty($object->conditions['where'])) {
            $this->sync['filter']['where'] = $object->conditions['where'];
        }
    }

    protected $negativeValues = false;

    /**
     * Detect Negative Value
     *
     * @uses In chart with [ column, bar ] type
     */
    public function detectNegativeValue($status = true, $stack = true)
    {
        $this->negativeValues = $status;
        $this->stack($status);
    }

    protected $stackOption = false;

    public function stack($status = true)
    {
        $this->stackOption = $status;
    }

    public function canvas($type, $source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        $options = [];
        if (! empty($this->negativeValues) && true === $this->negativeValues) {
            $options['negative_values'] = $this->negativeValues;
            unset($this->negativeValues);
        }

        if (! empty($this->stackOption) && false !== $this->stackOption) {
            $options['stack'] = $this->stackOption;
            unset($this->stackOption);
        }

        $this->setParams($type, $source, $fieldsets, $format, $category, $group, $order, $options);

        return $this->chartCanvas($this->sourceIdentity);
    }

    public function column($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function line($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function spline($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function area($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function areaspline($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function bar($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function pie($source, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        return $this->canvas(__FUNCTION__, $source, $fieldsets, $format, $category, $group, $order);
    }

    public function title($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function subtitle($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function axisTitle($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function tooltip($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }

    public function plotOptions($title, $options = [])
    {
        $attributes = ['text' => $title];
        if (! empty($options)) {
            $attributes = array_merge_recursive($attributes, $options);
        }

        $this->setAttributes(__FUNCTION__, $attributes);
    }
}

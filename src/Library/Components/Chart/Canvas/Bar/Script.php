<?php

namespace Canvastack\Canvastack\Library\Components\Chart\Canvas\Bar;

/**
 * Created on May 17, 2023
 *
 * Time Created : 11:02:11 PM
 *
 * @filesource  Script.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
trait Script
{
    private function bar_script($identity, $data = [])
    {
        $chartData = $data['data'];

        $series = null;
        if (! empty($chartData['series'])) {
            $series = static::drawJSON('series', $chartData);
        }

        $title = null;
        if (! empty($chartData['title'])) {
            $title = static::drawJSON('title', $chartData);
        }

        $subtitle = null;
        if (! empty($chartData['subtitle'])) {
            $subtitle = static::drawJSON('subtitle', $chartData);
        }

        $legends = null;
        if (! empty($chartData['legend'])) {
            $legends = static::drawJSON('legend', $chartData);
        }

        $tooltips = null;
        if (! empty($chartData['tooltip'])) {
            $tooltips = static::drawJSON('tooltip', $chartData);
        }

        $axisCat = [];

        $xAxisData = self::axisData('x', $chartData);
        $xAxis = $xAxisData['data'];
        $axisCat['x'] = $xAxisData['category'];

        $yAxisData = self::axisData('y', $chartData);
        $yAxis = $yAxisData['data'];
        $axisCat['y'] = $yAxisData['category'];

        $axisCategory = in_array(true, $axisCat);
        if (false === $axisCategory) {
            $chartData['xAxis']['categories'] = $chartData['category'];
            $xAxis = static::drawJSON('xAxis', $chartData);
        }

        $this->elements[$identity] .= self::scriptChart('bar', $identity, $title, $subtitle, $xAxis, $yAxis, $tooltips, $legends, $series);
    }
}

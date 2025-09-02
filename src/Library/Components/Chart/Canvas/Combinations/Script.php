<?php

namespace Canvastack\Canvastack\Library\Components\Chart\Canvas\Combinations;

/**
 * Created on Oct 24, 2022
 *
 * Time Created : 4:21:31 PM
 *
 * @filesource	Script.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait Script
{
    private function dualAxesLineAndColumn_script($identity, $data = [])
    {
        $chartData = $data['data'];

        $series = null;
        if (! empty($chartData['series'])) {
            $series = static::drawJSON('series', $chartData);
        }

        if (! empty($chartData['combined'])) {
            $series = static::drawJSON('combined', $chartData, 'series');
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

        $this->elements[$identity] .= self::scriptChart('column', $identity, $title, $subtitle, $xAxis, $yAxis, $tooltips, $legends, $series);
    }
}

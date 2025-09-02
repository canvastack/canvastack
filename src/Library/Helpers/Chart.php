<?php
/**
 * Created on Oct 19, 2022
 *
 * Time Created : 5:33:34 PM
 *
 * @filesource	Chart.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
if (! function_exists('canvastack_script_chart')) {

    function canvastack_script_chart($type = 'line', $identity = null, $title = null, $subtitle = null, $xAxis = null, $yAxis = null, $tooltips = null, $legends = null, $series = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::chartScript($type, $identity, $title, $subtitle, $xAxis, $yAxis, $tooltips, $legends, $series);
    }
}

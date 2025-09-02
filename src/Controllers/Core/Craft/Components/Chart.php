<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft\Components;

use Canvastack\Canvastack\Library\Components\Charts\Objects;

/**
 * Created on Oct 10, 2022
 *
 * Time Created : 1:20:42 PM
 *
 * @filesource	Chart.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait Chart
{
    public $chart;

    private function initChart()
    {
        $this->chart = new Objects();
        $this->plugins['chart'] = $this->chart;
    }
}

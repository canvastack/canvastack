<?php

namespace Canvastack\Canvastack\Library\Components\Chart\Canvas\Combinations;

/**
 * Created on Oct 24, 2022
 *
 * Time Created : 4:19:42 PM
 *
 * @filesource	DualAxesLineAndColumn.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait DualAxesLineAndColumn
{
    use Script;

    public function dualAxesLineAndColumn($source, $fieldsets, $format, $category = null, $order = null, $group = null)
    {
        $this->setParams(__FUNCTION__, $source, $fieldsets, $format, $category, $order, $group);
        $this->construct($this->params);
    }
}

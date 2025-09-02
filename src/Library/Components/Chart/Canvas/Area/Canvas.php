<?php

namespace Canvastack\Canvastack\Library\Components\Chart\Canvas\Area;

/**
 * Created on May 17, 2023
 *
 * Time Created : 11:07:37 PM
 *
 * @filesource  Canvas.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
trait Canvas
{
    use Script;

    /**
     * Build Line Chart
     *
     * @param  string  $source
     * 	: table source name
     * @param  array  $fieldsets
     * 	: [fieldname1, fieldname2, fieldname3]
     * @param  string  $format
     * 	: name:fieldname|data:fieldname::[sum|count|avg,-etc]
     * @param  string  $category
     * 	: fieldname used for chart category
     * @param  string  $order
     * 	: fieldname::[DESC|ASC] order
     * @param  string  $group
     * 	: fieldname group
     */
    public function area($source, $fieldsets, $format, $category = null, $order = null, $group = null)
    {
        $this->setParams(__FUNCTION__, $source, $fieldsets, $format, $category, $order, $group);
        $this->construct($this->params);
    }
}

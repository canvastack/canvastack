<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

/**
 * Created on Dec 28, 2022
 *
 * Time Created : 5:19:39 PM
 *
 * @filesource	Elements.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait Elements
{
    /**
     * Set Buttons
     $buttonset = '[
         {
             extend:"collection",
             exportOptions:{columns:":visible:not(:last-child)"},
             text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xport",
             buttons:[{text:"Excel",buttons:"excel"}, "csv", "pdf"],
             key:{key:"e",altKey:true}
         },
         "copy",
         "print"
     ]';
     */
    protected function setButtons($id, $button_sets = [])
    {
        $buttons = [];
        foreach ($button_sets as $button) {

            $button = trim($button);
            $option = null;
            $options[$button] = [];

            if (canvastack_string_contained($button, '|')) {
                $splits = explode('|', $button);
                foreach ($splits as $split) {
                    if (canvastack_string_contained($split, ':')) {
                        $options[$button][] = $split;
                    } else {
                        $button = $split;
                    }
                }
            }

            if (! empty($options[$button])) {
                $option = implode(',', $options[$button]);
            }
            $buttons[] = '{extend:"'.$button.'", '.$option.'}';
        }

        return '['.implode(',', $buttons).']';
    }
}

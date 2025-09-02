<?php

use Canvastack\Canvastack\Library\Components\MetaTags;

/**
 * Created on 14 Mar 2021
 * Time Created	: 23:02:18
 *
 * @filesource	MetaTags.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
if (! function_exists('canvastack_meta_tags')) {

    /**
     * Get Asset Path
     *
     * @return string
     */
    function canvastack_meta_tags($as = 'html')
    {
        $metaTags = new MetaTags();

        return $metaTags->tags($as);
    }
}

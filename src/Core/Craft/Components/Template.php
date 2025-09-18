<?php

namespace Canvastack\Canvastack\Core\Craft\Components;

use Canvastack\Canvastack\Library\Components\Template as Theme;

/**
 * Created on 26 Mar 2021
 * Time Created	: 17:18:23
 *
 * @filesource	Template.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Template
{
    public $template = [];

    private function initTemplate()
    {
        $this->template = new Theme();
        $this->plugins['template'] = $this->template;
    }
}

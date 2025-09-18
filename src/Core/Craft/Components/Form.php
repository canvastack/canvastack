<?php

namespace Canvastack\Canvastack\Core\Craft\Components;

use Canvastack\Canvastack\Library\Components\Form\Objects;

/**
 * Created on 26 Mar 2021
 * Time Created	: 16:39:14
 *
 * @filesource	Form.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Form
{
    public $form;

    private function initForm()
    {
        $this->form = new Objects();
        $this->plugins['form'] = $this->form;
    }
}

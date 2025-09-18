<?php

namespace App\Http\Controllers\Admin\Modules;

use Canvastack\Canvastack\Core\Craft\Handler as AuthHandler;

/**
 * Created on 15 Mar 2023
 *
 * Time Created : 22:12:52
 *
 * @filesource  Handler.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
trait Handler
{
    use AuthHandler;

    private function initHandler()
    {
        $this->roleHandlerAlias(['admin', 'internal']);
        $this->roleHandlerInfo(['National']);
    }

    private function customHandler()
    {
        if ('outlet' === strtolower($this->session['group_info'])) {
            $this->filterPage(['outlet_id' => strtolower($this->session['username'])], '=');
        } else {
            $this->filterPage(['region' => $this->session['group_alias']], '=');
        }
    }
}

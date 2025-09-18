<?php

namespace Canvastack\Canvastack\Core\Craft\Components;

use Canvastack\Canvastack\Library\Components\Messages\Email\Objects;

/**
 * Created on Jul 1, 2023
 *
 * Time Created : 9:36:25 PM
 *
 * @filesource  Email.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
trait Email
{
    public $email;

    private function initEmail()
    {
        $this->email = new Objects();
        $this->plugins['email'] = $this->email;
    }
}

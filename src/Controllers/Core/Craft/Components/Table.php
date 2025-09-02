<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft\Components;

use Canvastack\Canvastack\Library\Components\Table\Objects;

/**
 * Created on 12 Apr 2021
 * Time Created	: 19:35:40
 *
 * Marhaban Ya RAMADHAN
 *
 * @filesource	Table.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Table
{
    public $table;

    private function initTable()
    {
        $this->table = new Objects();
        $this->plugins['table'] = $this->table;
    }
}

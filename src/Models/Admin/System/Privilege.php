<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Jan 14, 2018
 * Time Created	: 12:20:33 AM
 * Filename		: Privilege.php
 *
 * @filesource	Privilege.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Privilege extends Model
{
    protected $table = 'base_group_privilege';

    protected $guarded = [];

    public $timestamps = false;
}

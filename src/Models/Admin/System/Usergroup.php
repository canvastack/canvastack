<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Jan 14, 2018
 * Time Created	: 12:37:50 AM
 * Filename		: Usergroup.php
 *
 * @filesource	Usergroup.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Usergroup extends Model
{
    protected $table = 'base_user_group';

    protected $guarded = [];

    public $timestamps = false;
}

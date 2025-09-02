<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Mar 14, 2018
 * Time Created	: 8:49:50 PM
 * Filename		: Preference.php
 *
 * @filesource	Preference.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Preference extends Model
{
    protected $table = 'base_preference';

    protected $guarded = [];

    public $timestamps = false;
}

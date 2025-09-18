<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Jan 15, 2018
 * Time Created	: 2:22:05 PM
 * Filename		: Timezone.php
 *
 * @filesource	Timezone.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Timezone extends Model
{
    protected $table = 'base_timezone';

    protected $guarded = [];

    public $timestamps = false;
}

<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Core\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Created on Oct 2, 2018
 * Time Created	: 1:48:35 PM
 * Filename		: Messages.php
 *
 * @filesource	Messages.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Messages extends Model
{
    use SoftDeletes;

    protected $table = 'mod_messages';

    protected $guarded = [];

    protected $dates = ['deleted_at'];
}

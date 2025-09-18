<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Jan 15, 2018
 * Time Created	: 2:53:49 PM
 * Filename		: Log.php
 *
 * @filesource	Log.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Log extends Model
{
    protected $table = 'log_activities';

    protected $guarded = [];
    //	protected $fillable	= ['subject', 'url', 'method', 'ip', 'agent', 'user_id'];

    public $timestamps = true;

    public function relation()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /*
    public function relations() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function relationals() {
        $logs = DB::table($this->table)
            ->select("{$this->table}.*", 'users.email', 'users.name', 'users.fullname')
            ->join('users', 'users.id',  '=', "{$this->table}.user_id")
            ->get();

        return $logs;
    } */
}

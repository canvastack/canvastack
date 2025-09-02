<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    public $groupInfo;

    /**
     * Bypassing all fields can be insert with data
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get Data Relation Group From User Group Table [ base_user_group ]
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function group()
    {
        return $this->belongsToMany(Group::class, 'base_user_group');
    }

    public function relational_group()
    {
        return $this->belongsToMany(Group::class, 'base_user_group');
    }

    /**
     * Print Out Group Info Logged In
     *
     * @return array
     */
    public function groupInfo()
    {
        foreach ($this->group as $groupInfo) {
            return $groupInfo->getAttributes();
        }
    }

    /**
     * Check Group Name From User Logged In
     *
     * @param  string  $group_name
     * @return bool
     */
    public function hasGroup($group_name)
    {
        foreach ($this->group as $group) {
            if ($group->group_name === $group_name) {
                return true;
            }

            return false;
        }
    }

    public function getUserInfo($filter = false, $get = true)
    {
        $f1 = 'users.id';
        $f2 = '!=';
        $f3 = 0;

        if (false !== $filter) {
            $filter_count = count($filter);
            if ($filter_count < 3) {
                $f1 = $filter[0];
                $f2 = $filter[1];
            } else {
                $f1 = $filter[0];
                $f2 = $filter[1];
                $f3 = $filter[2];
            }
        }

        $user_info = DB::table('users')
            ->select('users.*', 'base_user_group.group_id', 'base_group.group_name', 'base_group.group_info')
            ->join('base_user_group', 'users.id', '=', 'base_user_group.user_id')
            ->join('base_group', 'base_group.id', '=', 'base_user_group.group_id')
            ->where($f1, $f2, $f3);

        if (true === $get) {
            return $user_info->get();
        } else {
            return $user_info;
        }
    }

    public static function getGroupIdentity($id)
    {
        $group = canvastack_query("SELECT group_name, group_alias, group_info FROM base_group WHERE active = 1 AND id = {$id}", 'SELECT');

        return $group[0];
    }

    public static function sqlFirstRoute()
    {
        return '
			SELECT
				g.id group_id,
				g.group_name,
				g.group_info,
				m.id module_id,
				m.route_path,
				m.module_name,
				m.parent_name
			FROM base_group g
			JOIN base_group_privilege gp
				ON g.id = gp.group_id
			JOIN base_module m
				ON gp.module_id = m.id
		';
    }
}

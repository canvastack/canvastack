<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Core\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Created on Jan 11, 2018
 * Time Created	: 7:39:11 AM
 * Filename		: Modules.php
 *
 * @filesource	Modules.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Modules extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'base_module';

    protected $guarded = [];

    public $timestamps = false;

    public $privileges = [];

    /**
     * Privilege Module Info
     *
     * INFO: [ 8: index|show|read|select, 4: create|insert|write, 2: edit|update|modify, 1: destroy|delete ]
     *
     * @param  string  $group
     * @param  string  $page_type
     * @return array
     */
    public function privileges($group = false, $page_type = false, $root_flag = false)
    {

        $privilege_type = false;

        if (false === $page_type) {
            $privilege_type = 'admin_privilege';
        } else {
            if ('frontend' === $page_type) {
                $privilege_type = 'index_privilege';
            } else {
                $privilege_type = 'admin_privilege';
            }
        }

        if (1 === intval($group)) {
            // if logged in as root
            $menu = Modules::where('active', 1)->get();
        } else {
            $condition = null;
            $query = Modules::query($this->table)
                ->join('base_group_privilege', "{$this->table}.id", '=', 'base_group_privilege.module_id')
                ->join('base_group', 'base_group_privilege.group_id', '=', 'base_group.id')

                ->where("{$this->table}.active", 1)
                ->where('base_group.id', intval($group))
                ->where($privilege_type, '!=', "'NULL'")
                ->where($privilege_type, '!=', 0);

            if (! empty(session()->all()['user_group'])) {
                if (canvastack_string_contained(session()->all()['user_group'], 'admin')) {
                    $condition = '>=';
                } else {
                    $condition = '>';
                }
            } else {
                if (canvastack_string_contained(auth()->user()->username, 'admin')) {
                    $condition = '>=';
                } else {
                    $condition = '>';
                }
            }

            $menu = $query->where("{$this->table}.flag_status", $condition, 1)->get();
        }

        $module_privileges = [];
        $rolePrivileges = [];
        $role_data = [];
        $privileges = [];
        $privilege_info = [];

        foreach ($menu as $menu_privilege) {
            $module_privileges[] = $menu_privilege->route_path;
            if (1 === intval($group)) {
                // if logged in as root
                $rolePrivileges[$menu_privilege->route_path] = '8:4:2:1';
            } else {
                $rolePrivileges[$menu_privilege->route_path] = $menu_privilege->{$privilege_type};
            }

            $privileges[] = [
                // GROUP INFO
                'group_id' => $menu_privilege->group_id,
                'group_name' => $menu_privilege->group_name,
                'group_info' => $menu_privilege->group_info,

                // MODULES INFO
                'module_id' => $menu_privilege->module_id,
                'module_name' => $menu_privilege->module_name,
                'parent_name' => $menu_privilege->parent_name,

                // ROUTE INFO
                'route_path' => $menu_privilege->route_path,

                // PAGE PRIVILEGE INFO
                'index_privilege' => $menu_privilege->index_privilege,
                'admin_privilege' => $menu_privilege->admin_privilege,
            ];
        }

        foreach ($rolePrivileges as $base_route_name => $privilege) {
            $roleInfo = explode(':', $privilege);
            foreach ($roleInfo as $role_value) {
                $roleValue = intval($role_value);

                if (8 === $roleValue) {
                    $privilege_info[$base_route_name][$roleValue] = ['index', 'show'];
                }
                if (4 === $roleValue) {
                    $privilege_info[$base_route_name][$roleValue] = ['create', 'insert'];
                }
                if (2 === $roleValue) {
                    $privilege_info[$base_route_name][$roleValue] = ['edit', 'update'];
                }
                if (1 === $roleValue) {
                    $privilege_info[$base_route_name][$roleValue] = ['destroy', 'delete'];
                }

                if (is_array($privilege_info[$base_route_name][$roleValue])) {
                    foreach ($privilege_info[$base_route_name][$roleValue] as $roleNameValue) {
                        $privilege_info[$base_route_name][$roleValue][$roleNameValue] = $roleNameValue;

                        $role_data[] = "{$base_route_name}.{$privilege_info[$base_route_name][$roleValue][$roleNameValue]}";
                    }
                } else {
                    $role_data[] = "{$base_route_name}.{$privilege_info[$base_route_name][$roleValue]}";
                }
            }
        }

        $this->route_path = $module_privileges;
        $this->roles = $role_data;
        $this->privileges = $privileges;

        return $menu;
    }

    public function can_access($current_path, $route_lists)
    {
        return in_array($current_path, $route_lists);
    }
}

<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft\Includes;

use Canvastack\Canvastack\Models\Admin\System\Modules;
use Illuminate\Support\Facades\Route;

/**
 * Created on 9 Apr 2021
 * Time Created	: 14:49:04
 *
 * @filesource	Privileges.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Privileges
{
    private $module_class;

    private $role_group;

    public $menu = [];

    public $module_privilege = [];

    public $is_module_granted = false;

    /**
     * Get Privileges Module
     *
     * created @Dec 11, 2018
     * author: wisnuwidi
     */
    private function module_privileges()
    {
        // Safely read role group from session when available
        try {
            if (function_exists('app') && app()->bound('session')) {
                $sg = session('group_id', null);
                if (! is_null($sg)) {
                    $this->role_group = $sg;
                }
            }
        } catch (\Throwable $e) { /* ignore */
        }

        if (! is_null($this->role_group)) {
            $root_flag = false;
            $pageType = false;
            $actions = [];
            $this->module_class = new Modules();
            $baseRouteInfo = $this->routelists_info()['base_info'];

            // Only set root flag when session flag is present and truthy
            try {
                if (1 === intval($this->role_group) && function_exists('app') && app()->bound('session')) {
                    $sessFlag = session('flag', false);
                    if ($sessFlag === true || $sessFlag === 1 || $sessFlag === '1') {
                        $root_flag = true;
                    }
                }
            } catch (\Throwable $e) { /* ignore */
            }
            if (isset($this->data['page_type'])) {
                $pageType = $this->data['page_type'];
            }

            $this->menu = $this->module_class->privileges($this->role_group, $pageType, $root_flag);
            $this->module_privilege['current'] = $baseRouteInfo;
            $this->module_privilege['roles'] = $this->module_class->roles;
            $this->module_privilege['info'] = $this->module_class->privileges;

            if (function_exists('current_route') && in_array(current_route(), $this->module_class->roles)) {
                foreach ($this->module_class->roles as $roles) {
                    if (canvastack_string_contained($roles, $baseRouteInfo)) {
                        if (! in_array($this->routelists_info($roles)['last_info'], ['index', 'insert', 'update', 'destroy'])) {
                            $actions[$baseRouteInfo][] = $this->routelists_info($roles)['last_info'];
                        }
                    }
                }

                $this->module_privilege['actions'] = $actions[$baseRouteInfo] ?? [];
            }

            $this->access_role();
        }
    }

    public $removeButtons = [];

    /**
     * Remove Action Button in a Page
     *
     * @param  array  $buttons
     * 		['add', 'view', 'delete']
     */
    public function removeActionButtons($buttons = [])
    {
        $this->removeButtons = $buttons;
    }

    public function set_module_privileges($role_group = null)
    {
        $this->role_group = $role_group;
        $this->module_privileges();

        return ['role_group' => $this->role_group, 'role' => ($this->module_privilege['roles'] ?? [])];
    }

    private function access_role()
    {
        try {
            $this->is_module_granted = function_exists('current_route') && in_array(current_route(), $this->module_class->roles ?? []);
        } catch (\Throwable $e) {
            $this->is_module_granted = false;
        }
    }

    private function routelists_info($route = null)
    {
        // Use global helper when available; otherwise, build a minimal fallback
        if (function_exists('routelists_info')) {
            return routelists_info($route);
        }
        try {
            $name = Route::currentRouteName();
        } catch (\Throwable $e) {
            $name = '';
        }
        $target = $route ?: $name;
        $parts = array_values(array_filter(explode('.', (string) $target), function ($p) {
            return $p !== '';
        }));
        if (empty($parts)) {
            return ['base_info' => '', 'last_info' => ''];
        }
        $last = end($parts) ?: '';
        $base = count($parts) > 1 ? implode('.', array_slice($parts, 0, count($parts) - 1)) : (string) $last;

        return ['base_info' => $base, 'last_info' => $last];
    }
}

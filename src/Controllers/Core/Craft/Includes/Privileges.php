<?php
namespace Canvastack\Origin\Controllers\Core\Craft\Includes;

use Illuminate\Support\Facades\Route;
use Canvastack\Origin\Models\Admin\System\Modules;

/**
 * Created on 9 Apr 2021
 * Time Created	: 14:49:04
 *
 * @filesource	Privileges.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
trait Privileges {
	private $module_class;
	private $role_group;
	
	public $menu              = [];
	public $module_privilege  = [];
	public $is_module_granted = false;
	
	/**
	 * Get Privileges Module
	 *
	 * created @Dec 11, 2018
	 * author: wisnuwidi
	 */
	private function module_privileges() {
		if (!is_null(Session('group_id'))) {
			$this->role_group   = Session('group_id');
		}
		
		if (!is_null($this->role_group)) {
			$root_flag          = false;
			$pageType           = false;
			$actions            = [];
			$this->module_class = new Modules();
			$baseRouteInfo      = $this->routelists_info()['base_info'];
			
			if (1 === intval($this->role_group)) if (true === isset($this->session['flag'])) $root_flag = true;
			if (isset($this->data['page_type'])) $pageType = $this->data['page_type'];
			
			$this->menu                        = $this->module_class->privileges($this->role_group, $pageType, $root_flag);
			$this->module_privilege['current'] = $baseRouteInfo;
			$this->module_privilege['roles']   = $this->module_class->roles;
			$this->module_privilege['info']    = $this->module_class->privileges;
			
			if (in_array(current_route(), $this->module_class->roles)) {
				foreach ($this->module_class->roles as $roles) {
					if (canvas_string_contained($roles, $baseRouteInfo)) {
						if (!in_array($this->routelists_info($roles)['last_info'], ['index', 'insert', 'update', 'destroy'])) {
							$actions[$baseRouteInfo][] = $this->routelists_info($roles)['last_info'];
						}
					}
				}
				
				$this->module_privilege['actions'] = $actions[$baseRouteInfo];
			}
			
			$this->access_role();
		}
	}
	
	public $removeButtons   = [];
	/**
	 * Remove Action Button in a Page
	 *
	 * @param array $buttons
	 * 		['add', 'view', 'delete']
	 */
	public function removeActionButtons($buttons = []) {
		$this->removeButtons = $buttons;
	}
	
	public function set_module_privileges($role_group = null) {
		$this->role_group = $role_group;
		$this->module_privileges();
		
		return ['role_group' => $this->role_group, 'role' => $this->module_privilege['roles']];
	}
	
	private function access_role() {
		$this->is_module_granted = in_array(current_route(), $this->module_class->roles);
	}
	
	private function routelists_info($route = null) {
		return routelists_info($route);
	}
}
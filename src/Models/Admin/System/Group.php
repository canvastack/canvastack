<?php
namespace Canvastack\Canvastack\Models\Admin\System;

use Illuminate\Database\Eloquent\SoftDeletes;
use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Jan 14, 2018
 * Time Created	: 12:06:59 AM
 * Filename		: Group.php
 *
 * @filesource	Group.php
 *
 * @author		wisnuwidi@CanvaStack - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class Group extends Model {
	use SoftDeletes;
	
	protected $dates   = ['deleted_at'];
	protected $table   = 'base_group';
	protected $guarded = [];
	
	public $timestamps = false;
	
	public function relation() {
		if (true === is_multiplatform()) {
		//	return $this->hasOne(Multiplatforms::class, 'id', get_config('settings.platform_key'));
		}
	}
	
	/**
	 * Get first route options for form sync
	 * Returns array of route_path => module_name for dropdown
	 * 
	 * @param int $groupId Group ID to filter
	 * @return array ['route_path' => 'module_name']
	 */
	public static function getFirstRouteOptions($groupId) {
		return \DB::table('base_group as g')
			->join('base_group_privilege as gp', 'g.id', '=', 'gp.group_id')
			->join('base_module as m', 'gp.module_id', '=', 'm.id')
			->where('g.id', $groupId)
			->select('m.route_path as value', 'm.module_name as label')
			->orderBy('m.module_name')
			->pluck('label', 'value')
			->toArray();
	}
}
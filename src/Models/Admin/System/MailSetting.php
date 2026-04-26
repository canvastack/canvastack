<?php
namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Models\Core\Model;
/**
 * Created on Apr 14, 2018
 * Time Created	: 11:36:49 AM
 * Filename		: Log.php
 *
 * @filesource	Log.php
 *
 * @author		wisnuwidi@CanvaStack - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class MailSetting extends Model {
	protected $table	= 'base_mail_setting';
	protected $guarded	= [];
	
	public $timestamps	= true;
	
	public function relation() {
		return $this->hasOne(Modules::class, 'id', 'module_id');
	}
}
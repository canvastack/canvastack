<?php
namespace Canvastack\Origin\Models\Admin\System;

use Canvastack\Origin\Models\Core\Model;

/**
 * Created on Jan 15, 2018
 * Time Created	: 2:09:13 PM
 * Filename		: Language.php
 *
 * @filesource	Language.php
 *
 * @author		wisnuwidi@CanvaStack - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class Language extends Model {
	protected $table		= 'base_language';
	protected $guarded		= [];
	
	public $timestamps		= false;
}
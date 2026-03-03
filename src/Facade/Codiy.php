<?php
namespace Canvastack\Origin\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Created on Mar 22, 2018
 * Time Created	: 5:00:32 PM
 * Filename		: CanvaStack.php
 *
 * @filesource	CanvaStack.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
 
class CanvaStack extends Facade {
	protected static function getFacadeAccessor() {
		return 'CanvaStack';
	}
}
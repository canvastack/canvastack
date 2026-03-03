<?php
use Canvastack\Origin\Database\Migrations\Process;

/**
 * Created on Mar 3, 2017
 * Time Created	: 11:03:33 PM
 * Filename			: 2017_03_03_000000_create_base_table.php
 *
 * @author		wisnuwidi @CanvaStack - 2017
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class CreateBaseTable extends Process {
	public $exclude = ['platforms', 'shop'];
}
<?php

namespace Canvastack\Canvastack\Models\Admin\Modules;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on 24 Mar 2021
 * Time Created	: 10:39:03
 *
 * @filesource	Form.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Form extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_ho_program_keren_merapi'; //'test_inputform';

    protected $guarded = [];
}

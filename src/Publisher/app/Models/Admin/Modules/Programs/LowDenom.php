<?php

namespace App\Models\Admin\Modules\Programs;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Nov 30, 2022
 *
 * Time Created : 10:57:03 AM
 *
 * @filesource	LowDenom.php
 *
 * @author     wisnuwidi@gmail.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@gmail.com
 */
class LowDenom extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'view_report_data_summary_low_denom';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

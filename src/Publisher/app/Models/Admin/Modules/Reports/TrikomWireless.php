<?php

namespace App\Models\Admin\Modules\Reports;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on 19 Feb 2023
 *
 * Time Created : 21:27:18
 *
 * @filesource  TrikomWireless.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class TrikomWireless extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'view_report_data_summary_trikom_wireless';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

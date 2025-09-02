<?php

namespace App\Models\Admin\Modules\Reports;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Mar 20, 2023
 *
 * Time Created : 10:50:23 AM
 *
 * @filesource  Challange.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Challange extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_100_days_challange';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

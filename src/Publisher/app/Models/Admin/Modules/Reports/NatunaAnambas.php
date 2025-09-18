<?php

namespace App\Models\Admin\Modules\Reports;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Feb 20, 2023
 *
 * Time Created : 4:42:23 PM
 *
 * @filesource  NatunaAnambas.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class NatunaAnambas extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'view_report_data_summary_natuna_anambas';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

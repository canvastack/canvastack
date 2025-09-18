<?php

namespace App\Models\Admin\Modules\Programs;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on 19 Feb 2023
 *
 * Time Created : 22:28:31
 *
 * @filesource  SalesforceCanvaser.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class SalesforceCanvaser extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'view_report_data_summary_salesforce_canvaser';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

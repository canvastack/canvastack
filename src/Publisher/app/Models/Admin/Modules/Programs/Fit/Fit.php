<?php

namespace App\Models\Admin\Modules\Programs\Fit;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Feb 20, 2023
 *
 * Time Created : 2:03:51 PM
 *
 * @filesource  Fit.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Fit extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'view_report_data_summary_program_fit_class';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

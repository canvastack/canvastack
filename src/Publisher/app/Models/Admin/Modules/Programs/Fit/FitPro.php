<?php

namespace App\Models\Admin\Modules\Programs\Fit;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on 19 Mar 2023
 *
 * Time Created : 18:42:28
 *
 * @filesource  FitPro.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class FitPro extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_program_fit_pro';

    protected $tableView = true;

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

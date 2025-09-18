<?php

namespace App\Models\Admin\Modules\Kpi;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on 19 Mar 2023
 *
 * Time Created : 00:30:04
 *
 * @filesource  KpiDistributors.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class KpiDistributors extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_program_kpi_distributors';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

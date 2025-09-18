<?php

namespace App\Models\Admin\Modules\Incentive;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Mar 16, 2023
 *
 * Time Created : 2:32:06 PM
 *
 * @filesource  Incentive.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Incentive extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_incentive_asc';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

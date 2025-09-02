<?php

namespace App\Models\Admin\Modules\Programs\Keren;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on May 3, 2023
 *
 * Time Created : 1:44:49 PM
 *
 * @filesource  KerenProData.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class KerenProData extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_monthly_program_keren_pro_data';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

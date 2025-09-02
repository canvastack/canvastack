<?php

namespace App\Models\Admin\Modules\Programs\FreeSP3GB;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on May 18, 2023
 *
 * Time Created : 10:43:18 PM
 *
 * @filesource  FreeSP3GB.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
class FreeSP3GB extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_program_free_sp_3gb';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

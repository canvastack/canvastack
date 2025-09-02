<?php

namespace App\Models\Admin\Modules\Programs\Merapi;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on 19 Mar 2023
 *
 * Time Created : 17:22:40
 *
 * @filesource  Merapi.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Merapi extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_program_merapi';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

<?php

namespace App\Models\Admin\Modules\Programs\Samba;

use Canvastack\Canvastack\Core\Model;

/**
 * Created on Jun 28, 2023
 *
 * Time Created : 5:14:35 PM
 *
 * @filesource  Samba.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Samba extends Model
{
    protected $connection = 'mysql_mantra_etl';

    protected $table = 'report_data_summary_program_samba';

    protected $guarded = [];

    public function getConnectionName()
    {
        return $this->connection;
    }
}

<?php

namespace App\Http\Controllers\Admin\System;

use Canvastack\Canvastack\Core\Controller;

/**
 * Created on Aug 4, 2023
 *
 * Time Created : 11:16:24 AM
 *
 * @filesource  RunController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class RunController extends Controller
{
    public $data;

    public function __construct()
    {
        parent::__construct(false, 'system.config');
    }

    public function index()
    {
        $this->setPage('Run Jobs');
        $this->removeActionButtons(['add']);

        $queries = [
            'CALL mantra_etl.FUNC001_insert_data_program_keren_merapi_pro();',
            'CALL mantra_etl.FUNC002_insert_data_program_canvaser();',
            'CALL mantra_etl.FUNC003_insert_data_program_fit();',
            'CALL mantra_etl.FUNC004_insert_data_incentive();',
            'CALL mantra_etl.FUNC005_insert_data_internal_100_days_challange();',
            'CALL mantra_etl.FUNC006_insert_data_kpi_distributors();',
            'CALL mantra_etl.FUNC007_insert_data_program_free_sp_3gb();',
            'CALL mantra_etl.FUNC008_insert_data_trikom_wireless();',
            'CALL mantra_etl.FUNC009_insert_data_samba();',
        ];

        $o = '<ul>';
        foreach ($queries as $q) {
            $functionName = explode('_insert_data_', $q);
            $connection = explode('.', $functionName[0]);
            $tableName = str_replace('();', '()', $functionName[1]);
            $infoTable = "{$connection[0]}.{$tableName}";

            //	canvastack_query($q, "SELECT");

            $o .= "<li>Running Function: <b><u>{$infoTable}</u></b> done.</li>";
        }
        $o .= '</ul>';

        return $this->render($o);
    }
}

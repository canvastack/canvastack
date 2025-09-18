<?php

namespace App\Http\Controllers\Admin\Modules\Programs;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\SalesforceCanvaser;
use Canvastack\Canvastack\Core\Controller;

/**
 * Created on 19 Feb 2023
 *
 * Time Created : 21:52:13
 *
 * @filesource  SalesforceCanvaserController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class SalesforceCanvaserController extends Controller
{
    use Handler;

    public $data;

    private $fields = [
        'period_string',
        'region',
        'cluster',
        'nik',
        'canvaser_name',
        'total_noo',
        'total_noo_eligible',
    ];

    public function __construct()
    {
        parent::__construct(SalesforceCanvaser::class, 'modules.programs.program_salesforce_canvaser');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'running_date', null, $connection);
    }

    public function index()
    {
        $this->setPage('Program Keren Merapi');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);

        $this->table->setRightColumns([
            'total_noo',
            'total_noo_eligible',
        ]);

        $this->table->label(' ');
        $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
        $this->table->searchable(['period_string', 'region', 'cluster']);
        $this->table->clickable(false);
        $this->table->sortable();

        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        $this->table->lists($this->model_table, $this->fields, false);

        return $this->render();
    }
}

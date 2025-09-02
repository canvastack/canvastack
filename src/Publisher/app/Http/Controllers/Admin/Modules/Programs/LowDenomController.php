<?php

namespace App\Http\Controllers\Admin\Modules\Programs;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\LowDenom;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on Nov 30, 2022
 *
 * Time Created : 10:55:52 AM
 *
 * @filesource	LowDenomController.php
 *
 * @author     wisnuwidi@gmail.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@gmail.com
 */
class LowDenomController extends Controller
{
    use Handler;

    public $data;

    private $fields = [
        'period_string:Period',
        'cor:COR',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name',
        'outlet_class',
        'amount_ytd',
        'avg_amount',
    ];

    public function __construct()
    {
        parent::__construct(LowDenom::class, 'modules.programs.program_merapi');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', null, $connection);
    }

    public function index()
    {
        $this->setPage('Program Low Denom');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);
        $this->table->setCenterColumns(['program_name', 'cor', 'outlet_id']);
        $this->table->setRightColumns([
            'amount_ytd',
            'avg_amount',
        ]);
        $this->table->format('amount_ytd', 2);
        $this->table->format('avg_amount', 2);

        $this->table->label(' ');
        $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
        $this->table->clickable(false);
        $this->table->sortable();

        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('cor', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox', true);
        //	$this->table->filterGroups('sub_cluster', 'selectbox', true);
        //	$this->table->filterGroups('outlet_id', 'selectbox', true);
        //	$this->table->filterGroups('outlet_name', 'selectbox');

        $this->table->lists($this->model_table, $this->fields, false);

        return $this->render();
    }
}

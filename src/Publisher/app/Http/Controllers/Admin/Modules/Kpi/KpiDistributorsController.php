<?php

namespace App\Http\Controllers\Admin\Modules\Kpi;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Kpi\KpiDistributors;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on 19 Mar 2023
 *
 * Time Created : 00:18:01
 *
 * @filesource  KpiDistributorsController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class KpiDistributorsController extends Controller
{
    use Handler;

    public $data;

    private $id = false;

    private $_set_tab = [];

    private $_tab_config = [];

    private $_hide_fields = ['id'];

    private $fieldsets = [
        'period_string:Period',
        'period_bts_string:BTS Period',
        'region',
        'cluster',
        'category',
        'distributor_name:Distributor',
        'actual',
        'target',
        'percent_ach:ACH %',
        'percent_weight:Weight %',
        'max_cap',
        'total_point',
        'flag',
    ];

    public function __construct()
    {
        parent::__construct(KpiDistributors::class, 'modules.kpi');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'update_date', 'WHERE period IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('KPI Distributors');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);
        $this->table->setCenterColumns(['cor']);
        $this->table->setRightColumns([
            'actual',
            'target',
            'percent_ach',
            'percent_weight',
            'max_cap',
            'total_point',
        ], true, true);

        $this->table->format('actual', 0);
        $this->table->format('target', 0);
        $this->table->format('percent_ach', 0);
        $this->table->format('percent_weight', 0);
        $this->table->format('max_cap', 0);
        $this->table->format('total_point', 0);

        $this->table->columnCondition('percent_ach', 'cell', '>=', 1, 'suffix', ' %');
        $this->table->columnCondition('percent_weight', 'cell', '>=', 1, 'suffix', ' %');

        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        $this->table->clickable(false);
        $this->table->sortable();

        $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
        $this->table->label(' ');
        $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
        $this->table->addTabContent('
			<br/>
			<p style="margin-bottom: 1px !important;"><i><b>Disclaimer: Total poin ini adalah estimasi. Poin final akan di upload segera setelah verifikasi finance.</b></i></p>
			<div style="background-color: #fbf2f2; margin: 0; padding: 10px; border: #fdd1d1 solid 1px; border-radius: 4px;">
				<p style="margin-bottom: 1px !important;"><i>PO Data as of D-1</i></p>
				<p style="margin-bottom: 1px !important;"><i>ACT & SELL IN as of D-7</i></p>
				<p style="margin-bottom: 1px !important;"><i>BTS Rev as of 2 Prev-Month</i></p>
			</div>
			<p style="margin-bottom: 1px !important;"><i><b>* KPI Sellin still under development for checking usage on Eload & Package</b></i></p>
		');

        $this->table->lists($this->model_table, $this->fieldsets, false);
        $this->table->clearOnLoad();

        return $this->render();
    }
}

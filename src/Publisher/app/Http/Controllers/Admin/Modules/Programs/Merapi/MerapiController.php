<?php

namespace App\Http\Controllers\Admin\Modules\Programs\Merapi;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\Merapi\Merapi;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on 19 Mar 2023
 *
 * Time Created : 17:22:13
 *
 * @filesource  MerapiController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class MerapiController extends Controller
{
    use Handler;

    public $data;

    private $id = false;

    private $_set_tab = [];

    private $_tab_config = [];

    private $_hide_fields = ['id'];

    private $fieldsets = [
        'period_string:Period',
        'flag_program:Program',
        'cor:COR',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name:Nama Outlet',
        'class_program:Kelas Program',
        'target',
        'total_all_revenue',
        'total_inner_revenue',
        'achievement:Ach Range Target',
        'revenue_inner_sp_new_imei',
        'revenue_inner_sp',
        'revenue_inner_vd',
        'revenue_inner_paket_data',
        'total_activation',
    ];

    public function __construct()
    {
        parent::__construct(Merapi::class, 'modules.incentive');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', 'WHERE period IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('Merapi');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);

        //	if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || in_array($this->session['group_info'], ['ho'])) {
        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' !== strtolower($this->session['group_info'])) {
            $this->table->setCenterColumns(['cor', 'achievement']);
            $this->table->setRightColumns([
                'total_all_revenue',
                'total_inner_revenue',
                'revenue_inner_sp_new_imei',
                'revenue_inner_sp',
                'revenue_inner_vd',
                'revenue_inner_paket_data',
                'total_activation',
            ], true, true);

            $this->table->format('total_all_revenue', 0);
            $this->table->format('total_achivement', 2);
            $this->table->format('revenue_inner_sp_new_imei', 0);
            $this->table->format('revenue_inner_sp', 2);
            $this->table->format('revenue_inner_vd', 0);
            $this->table->format('revenue_inner_paket_data', 0);
            $this->table->format('total_activation', 0);

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox');

            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->openTab('Summary');
            $this->table->displayRowsLimitOnLoad(20);
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->label(' ');
            if (! empty($this->dateInfo($this->model_table, $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
            }
            $this->table->fixedColumns(5, 2);
            $this->table->lists($this->model_table, $this->fieldsets, false);
            $this->table->clearFixedColumns();
            $this->table->clearOnLoad();
        }

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' === strtolower($this->session['group_info'])) {
            $this->table->openTab('Summary Outlet');
            $this->table->displayRowsLimitOnLoad(20);
            $this->table->searchable(['period_string', 'region', 'cluster']);
            $this->table->label(' ');
            if (! empty($this->dateInfo('report_data_summary_program_merapi_outlets', $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_merapi_outlets', $this->connection).'</p>');
            }
            $this->table->lists('report_data_summary_program_merapi_outlets', $this->fieldsets, false);
            $this->table->clearOnLoad();
        }
        $this->table->closeTab();

        return $this->render();
    }
}

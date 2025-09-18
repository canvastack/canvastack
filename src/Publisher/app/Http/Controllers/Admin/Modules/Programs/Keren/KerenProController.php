<?php

namespace App\Http\Controllers\Admin\Modules\Programs\Keren;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\Keren\KerenPro;
use Canvastack\Canvastack\Core\Controller;

/**
 * Created on Mar 16, 2023
 *
 * Time Created : 5:33:00 PM
 *
 * @filesource  KerenProController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              wisnuwidi@canvastack.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@canvastack.com
 */
class KerenProController extends Controller
{
    use Handler;

    public $data;

    private $id = false;

    private $_set_tab = [];

    private $_tab_config = [];

    private $_hide_fields = ['id'];

    private $fieldsets = [
        'period_string',
        'nama_program',
        'cor:COR',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name:Nama Outlet',
        'outlet_cabang_id:Outlet Cabang',
        'outlet_cabang_name:Nama Outlet Cabang',
        'program_class:Kelas Program',

        'target_revenue',
        'total_inner_revenue_with_imei:Total Eligible Revenue',
        'total_achivement:Achievement',

        'target_revenue_sp:Target Revenue SP',
        'total_inner_sp_revenue_with_imei:Total Eligible SP Revenue With IMEI',
        'sp_achievement:SP Achievement',

        'total_rgu_act:Total AGU ACT',
        'total_inner_sp_30k:Total Revenue Inner SP 30K',
        'total_all_revenue',
        'total_inner_revenue',
        'total_inner_sp:Total SP Inner',
        'total_inner_vd:Total VD Inner',
        'total_inner_paket_data',
        'total_inner_eligible_imei',
        'total_aktifasi',
        'hari_berjalan',
        'sisa_hari',
    ];

    private $fieldset_outlet = [
        'period_string',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name:Nama Outlet',
        'program_class:Kelas Program',
        'outlet_cabang_id:Outlet Cabang',
        'outlet_cabang_name:Nama Outlet Cabang',

        'target_revenue',
        'total_inner_revenue_with_imei:Total Eligible Revenue',
        'total_achivement:Achievement',

        'target_revenue_sp:Target Revenue SP ',
        'total_inner_sp_revenue_with_imei:Total Eligible SP Revenue With IMEI ',
        'sp_achievement:SP Achievement',

        'total_rgu_act:Total AGU ACT',
        'total_inner_sp_30k:Total Revenue Inner SP 30K',
        'total_all_revenue',
        'total_inner_revenue',
        'total_inner_sp:Total SP Inner',
        'total_inner_vd:Total VD Inner',
        'total_inner_paket_data',
        'total_inner_eligible_imei',
        'total_aktifasi',
        'hari_berjalan',
        'sisa_hari',
    ];

    public function __construct()
    {
        parent::__construct(KerenPro::class, 'modules.programs');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', 'WHERE period IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('Keren Pro');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);
        $this->table->setCenterColumns(['cor']);

        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);

        $this->table->mergeColumns('All Revenue', ['target_revenue', 'total_inner_revenue_with_imei', 'total_achivement']);

        $this->table->setRightColumns([
            'target_revenue',
            'target_revenue_sp',
            'total_inner_revenue_with_imei',
            'total_achivement',
            'total_inner_sp_revenue_with_imei',
            'sp_achievement',
            'total_rgu_act',
            'total_inner_sp_30k',
            'total_all_revenue',
            'total_inner_revenue',
            'total_inner_sp',
            'total_inner_vd',
            'total_inner_paket_data',
            'total_inner_eligible_imei',
            'total_aktifasi',
            'hari_berjalan',
            'sisa_hari',
        ], true, true);

        $this->table->format('target_revenue', 0);
        $this->table->format('target_revenue_sp', 0);
        $this->table->format('total_inner_revenue_with_imei', 0);
        $this->table->format('total_achivement', 2);
        $this->table->format('total_inner_sp_revenue_with_imei', 0);
        $this->table->format('sp_achievement', 2);
        $this->table->format('total_inner_sp_30k', 0);
        $this->table->format('total_all_revenue', 0);
        $this->table->format('total_inner_revenue', 0);
        $this->table->format('total_inner_sp', 0);
        $this->table->format('total_inner_vd', 0);
        $this->table->format('total_inner_paket_data', 0);
        $this->table->format('total_inner_eligible_imei', 0);
        $this->table->format('total_aktifasi', 0);
        $this->table->format('hari_berjalan', 0);
        $this->table->format('sisa_hari', 0);
        $this->table->format('total_rgu_act', 0);

        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('cor', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        $this->table->columnCondition('total_achivement', 'cell', '<=', 0, 'suffix', ' %');
        $this->table->columnCondition('sp_achievement', 'cell', '<=', 0, 'suffix', ' %');

        //	$this->table->displayRowsLimitOnLoad(5);

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' !== strtolower($this->session['group_info'])) {
            $this->table->mergeColumns('SP Revenue', ['target_revenue_sp', 'total_inner_sp_revenue_with_imei', 'sp_achievement']);

            $this->table->openTab('Summary');
            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
            $this->table->lists($this->model_table, $this->fieldsets, false);

            $this->table->openTab('Detail');
            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_detail_program_keren_pro_national', $this->connection).'</p>');
            $this->table->lists('report_data_detail_program_keren_pro_national', $this->fieldsets, false);

            $this->table->openTab('Monthly');
            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_monthly_program_keren_pro_national', $this->connection).'</p>');
            $this->table->lists('report_data_monthly_program_keren_pro_national', $this->fieldsets, false);
            $this->table->clearVar('merged_columns');
        }

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' === strtolower($this->session['group_info'])) {
            $this->table->mergeColumns('All Revenue', ['target_revenue', 'total_inner_revenue_with_imei', 'total_achivement']);

            $this->table->openTab('Summary Outlet');
            $this->table->searchable(['period_string', 'region', 'cluster']);
            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_keren_pro_outlets', $this->connection).'</p>');
            $this->table->lists('report_data_summary_program_keren_pro_outlets', $this->fieldset_outlet, false);
        }

        $this->table->clearOnLoad();
        $this->table->closeTab();

        return $this->render();
    }
}

<?php

namespace App\Http\Controllers\Admin\Modules\Programs\Keren;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\Keren\KerenProData;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on May 3, 2023
 *
 * Time Created : 1:45:47 PM
 *
 * @filesource  KerenProDataController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class KerenProDataController extends Controller
{
    use Handler;

    public $data;

    private $id = false;

    private $_set_tab = [];

    private $_tab_config = [];

    private $_hide_fields = ['id'];

    private $fieldsets = [
        'period_string:Period',
        'cor:COR',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name:Nama Outlet',
        'outlet_cabang_id:Outlet Cabang',
        'outlet_cabang_name:Nama Outlet Cabang',
        'total_revenue',
        'total_eligible_revenue',
        'total_vd_30k',
        'total_eligible_vd_30k',
        'total_package_30k',
        'total_eligible_package_30k',
        'days_ongoing:Hari Berjalan',
        'days_remaining:Sisa Hari',
    ];

    public function __construct()
    {
        parent::__construct(KerenProData::class, 'modules.programs');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'update_date', 'WHERE update_date IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('Keren Pro Data');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);
        $this->table->setCenterColumns(['cor']);

        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);

        $this->table->setRightColumns([
            'total_revenue',
            'total_eligible_revenue',
            'total_vd_30k',
            'total_eligible_vd_30k',
            'total_package_30k',
            'total_eligible_package_30k',
            'days_ongoing',
            'days_remaining',
        ], true, true);

        $this->table->format('target_revenue', 0);
        $this->table->format('total_eligible_revenue', 0);
        $this->table->format('total_vd_30k', 0);
        $this->table->format('total_eligible_vd_30k', 2);
        $this->table->format('total_package_30k', 0);
        $this->table->format('total_eligible_package_30k', 2);
        $this->table->format('days_ongoing', 0);
        $this->table->format('days_remaining', 0);

        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('cor', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' !== strtolower($this->session['group_info'])) {
            $this->table->openTab('Monthly');
            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
            $this->table->lists($this->model_table, $this->fieldsets, false);
        }

        $this->table->clearOnLoad();
        $this->table->closeTab();

        return $this->render();
    }
}

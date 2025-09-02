<?php

namespace App\Http\Controllers\Admin\Modules\Programs\Fit;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Programs\Fit\Fit;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on Feb 20, 2023
 *
 * Time Created : 2:02:55 PM
 *
 * @filesource  FitController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class FitController extends Controller
{
    use Handler;

    public $data;

    private $fieldClass = [
        'period_string:Period',
        'cor',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name',
        'frontliner',
        'frontliner_name',
        'flag',
        'act_fit_point',
    ];

    private $fieldRegion = [
        'period_string',
        'cor',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name',
        'frontliner',
        'frontliner_name',
        'act_month_first',
        'act_month_second',
        'total_act',
        'rank_region',
        'rank_cluster',
        'rank_sub_cluster',
        'eligible_region',
        'eligible_cluster',
    ];

    private $fieldSubCluster = [
        'period_string',
        'cor',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name',
        'frontliner',
        'frontliner_name',
        'activation',
        'activation_estimate',
        'program_period',
        'rank_number',
        'rank_info',
    ];

    private $fieldNational = [
        'period_string',
        'cor',
        'region',
        'cluster',
        'sub_cluster',
        'outlet_id',
        'outlet_name',
        'frontliner',
        'frontliner_name',
        'total_act',
        'rank_number',
    ];

    public function __construct()
    {
        parent::__construct(Fit::class, 'modules.programs.program_fit');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', 'WHERE period IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('Program FIT');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || in_array($this->session['group_alias'], ['National'])) {
            $this->table->openTab('FIT Class');
            $this->table->displayRowsLimitOnLoad(20);
            $this->table->setRightColumns(['act_fit_point']);

            $this->table->label(' ');
            $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox', true);

            $this->table->lists($this->model_table, $this->fieldClass, false);

            $this->table->openTab('Master Region');
            $this->table->setRightColumns([
                'act_month_first',
                'act_month_second',
                'total_act',
            ]);

            $this->table->label(' ');
            if (! empty($this->dateInfo('report_data_summary_program_fit_master_region', $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_fit_master_region', $this->connection).'</p>');
            }
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox', true);

            //	$this->table->setHiddenColumns(['period_string']);
            $this->table->lists('view_report_data_summary_program_fit_master_region', $this->fieldRegion, false);

            $this->table->openTab('Master Cluster');
            $this->table->setRightColumns([
                'act_month_first',
                'est_act_month_first',
                'act_month_second',
                'est_act_month_second',
                'total_act',
            ]);

            $this->table->label(' ');
            if (! empty($this->dateInfo('report_data_summary_program_fit_master_cluster', $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_fit_master_cluster', $this->connection).'</p>');
            }
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox', true);

            //	$this->table->setHiddenColumns(['period_string']);
            $this->table->lists('view_report_data_summary_program_fit_master_cluster', $this->fieldRegion, false);

            $this->table->openTab('Master Sub Cluster');
            $this->table->setRightColumns([
                'activation',
                'activation_estimate',
            ]);

            $this->table->label(' ');
            if (! empty($this->dateInfo('report_data_summary_program_fit_master_sub_cluster', $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_fit_master_sub_cluster', $this->connection).'</p>');
            }
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox', true);

            //	$this->table->setHiddenColumns(['period_string']);
            $this->table->lists('view_report_data_summary_program_fit_master_sub_cluster', $this->fieldSubCluster, false);

            $this->table->openTab('Master National');
            $this->table->setRightColumns([
                'total_act',
            ]);

            $this->table->label(' ');
            if (! empty($this->dateInfo('report_data_summary_program_fit_master_national', $this->connection))) {
                $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo('report_data_summary_program_fit_master_national', $this->connection).'</p>');
            }
            $this->table->searchable(['period_string', 'cor', 'region', 'cluster']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('cor', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('cluster', 'selectbox', true);

            $this->table->format('total_act', 0);

            //	$this->table->setHiddenColumns(['period_string']);
            $this->table->lists('view_report_data_summary_program_fit_master_national', $this->fieldNational, false);

            $this->table->closeTab();
            $this->table->clearOnLoad();
        }

        return $this->render();
    }
}

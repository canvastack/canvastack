<?php

namespace App\Http\Controllers\Admin\Modules\Reports;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Reports\Challange;
use Canvastack\Canvastack\Core\Controller;

/**
 * Created on Mar 20, 2023
 *
 * Time Created : 10:31:04 AM
 *
 * @filesource  ChallangeController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class ChallangeController extends Controller
{
    use Handler;

    public $data;

    private $fieldSummary = [
        'cor:COR',
        'actual_act:Daily ACT',
        'ach_act:%Ach',
        'actual_outlet:Outlet/BTS',
        'ach_outlet:%Ach ',
        'actual_subs_per_bts:SUBS/BTS',
        'ach_subs:%Ach  ',
        'actual_rev_per_bts:REV/BTS',
        'ach_rev:%Ach   ',
    ];

    public function __construct()
    {
        parent::__construct(Challange::class, 'modules.reports.challange');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', 'WHERE period IS NOT NULL', $connection);
    }

    private $totalPercentage = [];

    private function totalPercentage()
    {
        $query = canvastack_query("SELECT ach_act, ach_outlet, ach_subs, ach_rev  FROM {$this->model_table} WHERE cor = 'ALL';", 'SELECT', $this->connection);

        $this->totalPercentage = $query[0];
    }

    public function index()
    {
        $this->setPage('100 Days Challange');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);
        $this->totalPercentage();

        $this->table->connection($this->connection);

        $this->table->openTab('Summary');

        $this->table->mergeColumns('Activation', ['actual_act', 'ach_act']);
        $this->table->mergeColumns('Outlet', ['actual_outlet', 'ach_outlet']);
        $this->table->mergeColumns('SUBS/BTS', ['actual_subs_per_bts', 'ach_subs']);
        $this->table->mergeColumns('REV/BTS (Mn)', ['actual_rev_per_bts', 'ach_rev']);

        $this->table->format('actual_act', 0);
        $this->table->format('ach_act', 0);

        $this->table->format('actual_outlet', 0);
        $this->table->format('ach_outlet', 0);

        $this->table->format('actual_subs_per_bts', 0);
        $this->table->format('ach_subs', 0);

        $this->table->format('actual_rev_per_bts', 0);
        $this->table->format('ach_rev', 0);

        $this->table->columnCondition('cor', 'row', '===', 'ALL', 'background-color', 'rgb(255, 242, 204)');
        $this->table->columnCondition('cor', 'cell', 'LIKE', 'COR', 'background-color', 'rgb(216, 236, 210)');
        $this->table->columnCondition('cor', 'cell', 'NOT LIKE', 'COR|ALL', 'text-indent', '8px');

        $this->table->columnCondition('ach_act', 'cell', '<', $this->totalPercentage->ach_act, 'color', 'red');
        $this->table->columnCondition('ach_act', 'cell', '>=', $this->totalPercentage->ach_act, 'color', 'green');

        $this->table->columnCondition('ach_outlet', 'cell', '<', $this->totalPercentage->ach_outlet, 'color', 'red');
        $this->table->columnCondition('ach_outlet', 'cell', '>=', $this->totalPercentage->ach_outlet, 'color', 'green');

        $this->table->columnCondition('ach_subs', 'cell', '<', $this->totalPercentage->ach_subs, 'color', 'red');
        $this->table->columnCondition('ach_subs', 'cell', '>=', $this->totalPercentage->ach_subs, 'color', 'green');

        $this->table->columnCondition('ach_rev', 'cell', '<', $this->totalPercentage->ach_rev, 'color', 'red');
        $this->table->columnCondition('ach_rev', 'cell', '>=', $this->totalPercentage->ach_rev, 'color', 'green');

        $this->table->columnCondition('ach_act', 'cell', '>=', 1, 'suffix', ' %');
        $this->table->columnCondition('ach_outlet', 'cell', '>=', 1, 'suffix', ' %');
        $this->table->columnCondition('ach_subs', 'cell', '>=', 1, 'suffix', ' %');
        $this->table->columnCondition('ach_rev', 'cell', '>=', 1, 'suffix', ' %');

        $this->table->setCenterColumns([
            'ach_act',
            'actual_outlet',
            'ach_outlet',
            'ach_subs',
            'ach_rev',
        ]);
        $this->table->setRightColumns([
            'actual_act',
            'actual_subs_per_bts',
            'actual_rev_per_bts',
        ]);

        $this->table->label(' ');
        $this->table->addTabContent('<p>As of: '.$this->dateInfo($this->model_table, $this->connection).'</p>');
        $this->table->searchable(['period_string', 'cor']);
        $this->table->clickable(false);
        $this->table->sortable(false);
        $this->table->orderby('id', 'asc');

        $this->table->displayRowsLimitOnLoad('*');
        $this->table->lists($this->model_table, $this->fieldSummary, false);

        $this->table->closeTab();

        return $this->render();
    }
}

<?php

namespace App\Http\Controllers\Admin\Modules\Programs\Samba;

use App\Models\Admin\Modules\Programs\Samba\Samba;
use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Controllers\Core\Craft\Handler;

/**
 * Created on Jun 28, 2023
 *
 * Time Created : 5:13:25 PM
 *
 * @filesource  SambaController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class SambaController extends Controller
{
    use Handler;

    private $fields = [
        'period_string:Period',
        'month_period_string:Month',
        'region',
        'cluster',
        'outlet_type',
        'outlet_id',
        'outlet_name',
        'sellthru_sp',
        'sellthru_vd',
        'sellthru_eload',
        'activation_sp',
        'activation_vd',
        'activation_eload',
        'amount_eload',
        'total_activation',
        'dl_sp',
        'dl_vd',
        'dl_eload',
        'po',
        'total_percentage',
        'management_fee',
    ];

    public function __construct()
    {
        parent::__construct(Samba::class, 'modules.programs.samba');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period', 'WHERE period IS NOT NULL', $connection);
    }

    public function index()
    {
        $this->setPage('Program Samba');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);

        if (in_array($this->session['user_group'], array_merge(['root', $this->roleAlias])) || 'outlet' !== strtolower($this->session['group_info'])) {

            $this->table->setRightColumns([
                'sellthru_sp',
                'sellthru_vd',
                'sellthru_eload',
                'activation_sp',
                'activation_vd',
                'activation_eload',
                'amount_eload',
                'total_activation',
                'dl_sp',
                'dl_vd',
                'dl_eload',
                'po',
                'total_percentage',
                'management_fee',
            ]);

            $this->table->label(' ');
            $this->table->addTabContent('<p>Update Date : '.$this->dateInfo($this->model_table, $this->connection).'</p>');

            $this->table->searchable(['period_string', 'region', 'outlet_type']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('period_string', 'selectbox', true);
            $this->table->filterGroups('region', 'selectbox', true);
            $this->table->filterGroups('outlet_type', 'selectbox', true);

            $this->table->lists($this->model_table, $this->fields, false);
        }

        return $this->render();
    }
}

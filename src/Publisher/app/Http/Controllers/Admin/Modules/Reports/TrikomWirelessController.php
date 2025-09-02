<?php

namespace App\Http\Controllers\Admin\Modules\Reports;

use App\Http\Controllers\Admin\Modules\Handler;
use App\Models\Admin\Modules\Reports\TrikomWireless;
use Canvastack\Canvastack\Controllers\Core\Controller;

/**
 * Created on 19 Feb 2023
 *
 * Time Created : 21:26:39
 *
 * @filesource  TrikomWirelessController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class TrikomWirelessController extends Controller
{
    use Handler;

    public $data;

    private $fields = [
        'period',
        'period_string:Period String',
        'activation_date',
        'partner_code',
        'partner_name',
        'product_name',
        'settlement',
        'mdn',
        'flag_5gb:Flag 5GB',
        'vol_mb: MB Vol',
        'period_usage: Period Usage',
        'total_usage',
        'last_update_data',
    ];

    public function __construct()
    {
        parent::__construct(TrikomWireless::class, 'modules.reports.program_trikom_wireless');
    }

    private function dateInfo($table, $connection = null)
    {
        return canvastack_date_info($table, 'period_usage', null, $connection);
    }

    public function index()
    {
        $this->setPage('Program Keren Merapi');
        $this->sessionFilters();

        $this->removeActionButtons(['add']);

        $this->table->connection($this->connection);

        $this->table->setCenterColumns(['mdn']);
        $this->table->setRightColumns([
            'vol_mb',
            'total_usage',
        ]);

        $this->table->label(' ');
        $this->table->addTabContent('<p>Tanggal Update Terakhir : '.$this->dateInfo($this->model_table, $this->connection).'</p>');
        $this->table->searchable(['period_usage', 'mdn', 'flag_5gb']);
        $this->table->clickable(false);
        $this->table->sortable();

        $this->table->filterGroups('period_usage', 'selectbox', true);
        $this->table->filterGroups('mdn', 'selectbox', true);
        $this->table->filterGroups('flag_5gb', 'selectbox');

        $this->table->lists($this->model_table, $this->fields, false);

        return $this->render();
    }
}

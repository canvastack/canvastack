<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Log as LogActivity;

/**
 * Created on Jan 16, 2018
 * Time Created	: 11:23:32 PM
 * Filename		: LogController.php
 *
 * @filesource	LogController.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class LogController extends Controller
{
    private $field_lists = [
        'user_fullname:User Name',
        'user_group_info:Group Info',
        'route_path',
        'module_name',
        'page_info',
        'urli',
        'method',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function __construct()
    {
        parent::__construct(LogActivity::class, 'system.config.log');
    }

    public function index()
    {
        $this->setPage();
        $this->removeActionButtons(['add']);

        //	$this->table->method('POST');
        $this->table->searchable(['user_fullname', 'user_group_info', 'method', 'module_name']);
        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->orderby('id', 'desc');
        $this->table->filterGroups('user_fullname', 'selectbox', true);
        $this->table->filterGroups('user_group_info', 'selectbox', true);
        $this->table->filterGroups('method', 'selectbox', true);
        $this->table->filterGroups('module_name', 'selectbox', true);
        $this->table->displayRowsLimitOnLoad(30);
        //	$this->table->lists($this->model_table, $this->field_lists, ['new_button', 'button_name|warning|tags']);
        $this->table->lists($this->model_table, $this->field_lists);

        return $this->render();
    }

    public function edit($id)
    {
        $this->setPage();

        $this->form->model();

        $this->form->text('username', $this->model_data->username, ['readonly']);
        $this->form->text('user_fullname', $this->model_data->user_fullname, ['readonly']);
        $this->form->text('user_email', $this->model_data->user_email, ['readonly']);

        $this->form->openTab('User Group Info');
        $this->form->text('user_group_name', $this->model_data->user_group_name, ['readonly']);
        $this->form->text('user_group_info', $this->model_data->user_group_info, ['readonly']);

        $this->form->openTab('User Map Activity');
        $this->form->text('route_path', $this->model_data->route_path, ['readonly']);
        $this->form->text('module_name', $this->model_data->module_name, ['readonly']);
        $this->form->text('page_info', $this->model_data->page_info, ['readonly']);
        $this->form->textarea('urli', $this->model_data->urli, ['readonly']);
        $this->form->text('method', $this->model_data->method, ['readonly']);
        $this->form->text('ip_address', $this->model_data->ip_address, ['readonly']);
        $this->form->text('user_agent', $this->model_data->user_agent, ['readonly']);
        $this->form->textarea('sql_dump', $this->model_data->sql_dump, ['readonly']);

        $this->form->openTab('User Time Activity');
        $this->form->text('created_at', $this->model_data->created_at, ['readonly']);
        $this->form->text('updated_at', $this->model_data->updated_at, ['readonly']);
        $this->form->closeTab();

        $this->form->close();

        return $this->render();
    }
}

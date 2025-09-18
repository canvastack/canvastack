<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Extransload;
use Illuminate\Http\Request;

/**
 * Created on Dec 10, 2022
 *
 * Time Created : 12:41:31 AM
 * Filename     : ExtransloadController.php
 *
 * @filesource ExtransloadController.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class ExtransloadController extends Controller
{
    private $page_label = 'Extract, Transform and Load';

    private $fields = [
        'process_name',
        'source_connection_name',
        'source_table_name',
        'source_data_counts',
        'target_connection_name',
        'target_table_name',
        'target_current_counts',
        'success_data_transfers',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    private static $connection_sources = [];

    public function __construct()
    {
        parent::__construct(Extransload::class, 'system.config');

        self::source_connections();
    }

    private static $etls = [];

    private static function source_connections()
    {
        self::$connection_sources = canvastack_config('sources', 'connections');
        foreach (self::$connection_sources as $connection_name => $connection_data) {
            self::$etls['sources']['label'][$connection_name] = $connection_data['label'];
        }
    }

    public function index()
    {
        $this->setPage($this->page_label);

        $this->table->setName($this->page_label);
        $this->table->mergeColumns('Data Source', ['source_connection_name', 'source_table_name', 'source_data_counts']);
        $this->table->mergeColumns('Data Target', ['target_connection_name', 'target_table_name', 'target_current_counts', 'success_data_transfers']);
        $this->table->mergeColumns('Account Activity', ['created_by', 'updated_by']);
        $this->table->mergeColumns('Timeline', ['created_at', 'updated_at']);

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->filterGroups('source_connection_name', 'selectbox', true);
        $this->table->filterGroups('source_table_name', 'selectbox', true);
        $this->table->filterGroups('target_table_name', 'selectbox');

        $this->table->lists($this->model_table, $this->fields);

        return $this->render();
    }

    public function create()
    {
        $this->setPage($this->page_label.' Mappings');
        $this->form->model();

        $this->form->text('process_name', null);
        $this->form->textarea('remarks', null, ['class' => 'form-control ckeditor']);

        $this->form->openTab('Information Process');
        $this->form->selectbox('source_connection_name', self::$etls['sources']['label'], false, ['required']);
        $this->form->selectbox('source_table_name', [], false, ['required']);
        $this->synconnections('source_connection_name', 'source_table_name');
        //	$this->form->text('source_data_counts', null);

        $this->form->selectbox('target_connection_name', self::$etls['sources']['label'], false, ['required']);
        $this->form->selectbox('target_table_name', [], false, ['required']);
        $this->synconnections('target_connection_name', 'target_table_name');
        //	$this->form->text('target_current_counts', null);
        //	$this->form->text('success_data_transfers', null, ['required']);
        $this->form->closeTab();

        $this->form->close('Save '.$this->page_label);

        return $this->render();
    }

    public function edit($id)
    {
        set_time_limit(0);
        $this->setPage($this->page_label.' Mappings');

        $this->form->method('post');
        $this->form->model();

        $this->form->text('process_name', $this->model_data->process_name);
        $this->form->textarea('remarks', $this->model_data->remarks, ['class' => 'form-control ckeditor']);

        $this->form->openTab('Information Process');
        $this->form->selectbox('source_connection_name', self::$etls['sources']['label'], $this->model_data->source_connection_name, ['required', 'radonly']);
        $this->form->selectbox('source_table_name', [], false, ['required', 'radonly']);
        $this->synconnections('source_connection_name', 'source_table_name', $this->model_data->source_table_name);

        $this->form->selectbox('target_connection_name', self::$etls['sources']['label'], $this->model_data->target_connection_name, ['required', 'radonly']);
        $this->form->selectbox('target_table_name', [], false, ['required', 'radonly']);
        $this->synconnections('target_connection_name', 'target_table_name', $this->model_data->target_table_name);
        $this->form->closeTab();

        $this->form->close('Process '.$this->page_label);

        $ajaxURL = canvastack_get_ajax_urli('diyHostProcess');
        $this->form->draw(canvastack_script("
jQuery(document).ready(function() {
	$('form[name=\"{$this->form->identity}\"]').on('submit', function(e) {
		var submitButtonBox = $('div.diy-action-box>input.btn');
		submitButtonBox.css({
			'width': '220px',
			'text-align': 'left',
			'text-indent': '10px'
		}).wrap('<div class=\"canvastack_submitbox\" style=\"position:relative;width:220px;text-align:left;float:right\"></div>');
		
		$('<span class=\"inputloader loader\" style=\"right:8px;width:20px;height:20px;top:7px;background-size:20px\"></span>')
			.insertAfter(submitButtonBox);

		e.preventDefault();
		$.ajax({
			type    : 'POST',
			url     : '{$ajaxURL}',
			data    : $(this).serialize(),
			success : function(d) {
				var result = JSON.parse(d);
			},			
			complete : function() {
				submitButtonBox.removeAttr('style').detach();
				$('div.diy-action-box').append(submitButtonBox);
				$('div.canvastack_submitbox').remove();
				$('span.inputloader').remove();
			}
		});
	});
});
		"));

        return $this->render();
    }

    public function update(Request $request, $id)
    {
        dd($request->all());
    }

    public function synconnections(string $source_field, string $target_field, $selected = null)
    {
        $connections = self::$etls['sources']['label'];
        $syncs = [];
        $syncs['source'] = $source_field;
        $syncs['target'] = $target_field;
        $syncs['selected'] = encrypt($selected);

        $data = json_encode($syncs);
        $ajaxURL = canvastack_get_ajax_urli('diyHostConn', $connections);

        $this->form->draw(canvastack_script("ajaxSelectionBox('{$source_field}', '{$target_field}', '{$ajaxURL}', '{$data}');"));
    }
}

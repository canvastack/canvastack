<?php
namespace Canvastack\Canvastack\Controllers\Admin\System;

use Illuminate\Http\Request;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\MailSetting;
use Canvastack\Canvastack\Models\Admin\System\Modules;

/**
 * Created on Apr 14, 2018
 * Time Created	: 11:46:49 AM
 * Filename		: MailSettingController.php
 *
 * @filesource	MailSettingController.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
class MailSettingController extends Controller {
	public $data;
	
	public function __construct() {
		parent::__construct(MailSetting::class, 'system.config.mailsetting');
		
		$this->setValidations(
			[
				'title'     => 'required',
				'module_id' => 'required|exists:base_module,id'
			], [
				'title'     => 'required',
				'module_id' => 'required|exists:base_module,id'
			]
		);
	}
	
	/**
	 * Get active modules for selectbox options
	 * 
	 * @return array
	 */
	private function getModuleOptions() {
		$modules = Modules::where('active', 1)
			->orderBy('parent_name')
			->orderBy('module_name')
			->get();
		
		$options = [];
		foreach ($modules as $module) {
			$label = $module->parent_name !== $module->module_name 
				? "{$module->parent_name} - {$module->module_name}" 
				: $module->module_name;
			$options[$module->id] = $label;
		}
		
		return $options;
	}
	
	public function index() {
		$this->get_session();
		
		$this->setPage('Mail Setting Lists', 'index');

		$this->table->searchable();
		$this->table->clickable();
		$this->table->sortable();
        
		$this->table->filterGroups('title', 'selectbox', false);
		$this->table->filterGroups('module_id', 'selectbox', false);
		
		$this->table->orderby('id', 'DESC');

		$this->table->lists($this->model_table, ['title', 'module_id', 'mail_subject_insert', 'mail_subject_update', 'mail_subject_delete']);
        
        return $this->render();
	}
	
	public function show($id) {
		$model_data = MailSetting::find($id);
		
		$this->setPage('Detail MailSetting', 'MailSetting');
		$this->form->config_show_data_static($model_data, $this->model_table, $this->_hide_fields);
		
		$this->form->model($model_data, $this->set_route('index'));
		$this->form->table($this->model_table, $this->_set_tab, $this->_tab_config);
		
		return $this->render();
	}
	
	public function create() {
		$this->setPage('Create Mail Setting', 'index');
		
		$this->form->model();
		
		$this->form->text('title', null, ['required']);
		$this->form->selectbox('module_id', $this->getModuleOptions(), false, ['required']);
		
		$this->form->openTab('Mail Content Insert');
		$this->form->text('mail_subject_insert', null, [], 'Mail Subject');
		$this->form->textarea('mail_content_insert', null, ['class' => 'form-control ckeditor'], 'Mail Message');
		
		$this->form->openTab('Mail Content Update');
		$this->form->text('mail_subject_update', null, [], 'Mail Subject');
		$this->form->textarea('mail_content_update', null, ['class' => 'form-control ckeditor'], 'Mail Message');
		
		$this->form->openTab('Mail Content Delete');
		$this->form->text('mail_subject_delete', null, [], 'Mail Subject');
		$this->form->textarea('mail_content_delete', null, ['class' => 'form-control ckeditor'], 'Mail Message');

		$this->form->closeTab();
		
		$this->form->close('Save Mail Setting');
				
		return $this->render();
	}
	
	public function edit($id) {
		$model_data = MailSetting::find($id);
		
		$this->setPage('Edit Mail Setting');
		
		$this->form->model();

		$this->form->text('title', $this->model_data->title, ['required']);
		$this->form->selectbox('module_id', $this->getModuleOptions(), $this->model_data->module_id, ['required']);
		
		$this->form->openTab('Mail Content Insert');
		$this->form->text('mail_subject_insert', $this->model_data->mail_subject_insert, [], 'Mail Subject');
		$this->form->textarea('mail_content_insert', $this->model_data->mail_content_insert, ['class' => 'form-control ckeditor'], 'Mail Message');
		
		$this->form->openTab('Mail Content Update');
		$this->form->text('mail_subject_update', $this->model_data->mail_subject_update, [], 'Mail Subject');
		$this->form->textarea('mail_content_update', $this->model_data->mail_content_update, ['class' => 'form-control ckeditor'], 'Mail Message');
		
		$this->form->openTab('Mail Content Delete');
		$this->form->text('mail_subject_delete', $this->model_data->mail_subject_delete, [], 'Mail Subject');
		$this->form->textarea('mail_content_delete', $this->model_data->mail_content_delete, ['class' => 'form-control ckeditor'], 'Mail Message');
        
		$this->form->closeTab();
		
		$this->form->close('Update Mail Setting', ['class' => 'btn btn-default btn-slideright pull-right']);
		
		return $this->render();
	}
}

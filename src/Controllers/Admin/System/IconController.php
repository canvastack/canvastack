<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Icon;
use Illuminate\Http\Request;

/**
 * Created on Mar 15, 2018
 * Time Created	: 9:28:57 AM
 * Filename		: IconController.php
 *
 * @filesource	IconController.php
 *
 * @author		wisnuwidi@canvastack.com - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class IconController extends Controller
{
    public $data;

    private $route_group = 'system.config';

    private $model_table = 'base_icon';

    private $model;

    private $_hide_fields = ['id'];

    private $_set_tab = [];

    private $_tab_config = [];

    public function __construct()
    {
        parent::__construct();

        $this->model = Icon::all();
        $this->model_query(Icon::class);
        $this->base_route = "{$this->route_group}.";
    }

    private function base_route()
    {
        return $this->route_group.'.module';
    }

    private function set_route($path)
    {
        return $this->route_group.'.module.'.$path;
    }

    public function index()
    {
        $this->get_session();
        if ('root' !== $this->session['user_group']) {
            return redirect(url()->route('system.accounts.user.index'));
        }

        $this->set_page('Icon Lists', 'icon');
        $this->form->lists($this->model_table, ['type', 'tag', 'class', 'label', 'active'], $this->model_data, true, true);

        $this->searchInputElement('type', 'string');
        $this->searchInputElement('tag', 'string');
        $this->searchInputElement('class', 'string');
        $this->searchInputElement('label', 'string');
        $this->searchInputElement('active', 'selectbox', active_box());
        $this->searchDraw($this->model_table, ['id', 'module_info', 'deleted_at', 'menu_sort', 'icon'], false);

        $this->download_button($this->model_table);

        return $this->render();
    }

    public function show($id)
    {
        $model_data = Icon::find($id);

        $this->set_page('Detail Icon', 'Icon');
        $this->form->config_show_data_static($model_data, $this->model_table, $this->_hide_fields);

        $this->form->model($model_data, $this->set_route('index'));
        $this->form->table($this->model_table, $this->_set_tab, $this->_tab_config);

        return $this->render();
    }

    public function create()
    {
        $this->get_session();
        if ('root' !== $this->session['user_group']) {
            return redirect(url()->route('admin.index'));
        }

        $this->set_page('Add Icon', 'Icon');

        $this->form->model($this->model, "{$this->set_route('store')}");

        $this->form->text('type');
        $this->form->text('tag');
        $this->form->text('class');
        $this->form->text('label');
        $this->form->selectbox('active', active_box());

        $this->form->close('Save Icon', ['class' => 'btn btn-default btn-slideright pull-right']);

        return $this->render();
    }

    public function store(Request $request)
    {
        $model = insert(new Icon, $request, true);
        $route_group = str_replace('.', '/', $this->route_group);

        return redirect("/{$route_group}/{$model}/edit");
    }

    public function edit($id)
    {
        $model_data = Icon::find($id);

        $this->set_page('Edit Icon', 'Icon');

        $this->form->model($model_data, "{$this->set_route('update')}", $model_data->id);

        $this->form->text('type', null);
        $this->form->text('tag', null);
        $this->form->text('class', null);
        $this->form->text('label', null);
        $this->form->selectbox('active', active_box(), $model_data->active);

        $this->form->close('Save Icon', ['class' => 'btn btn-default btn-slideright pull-right']);

        return $this->render();
    }

    public function update(Request $request, $id)
    {
        return update(Icon::find($id), $request, true);
    }
}

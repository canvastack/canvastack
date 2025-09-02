<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Icon;
use Canvastack\Canvastack\Models\Admin\System\Modules;
use Illuminate\Http\Request;

/**
 * Created on Jan 11, 2018
 * Time Created	: 7:38:49 AM
 * Filename		: ModulesController.php
 *
 * @filesource	ModulesController.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class ModulesController extends Controller
{
    public $data;

    private $_hide_fields = ['id'];

    private $_set_tab = [];

    private $_tab_config = [];

    public function __construct()
    {
        parent::__construct(Modules::class, 'system.config');

        $this->setValidations(
            [
                'route_path' => 'required|unique:base_module|not_in:0',
                'flag_status' => 'required|not_in:0',
                'active' => 'required',
            ], [
                'flag_status' => 'required|not_in:0',
                'active' => 'required',
            ]
        );
    }

    /**
     * Rendering un/registered module(s).
     *
     * @param  bool  $selected
     * @return bool|string
     *
     * author: wisnuwidi
     */
    private function render_value_module_name($selected = false, $fullroutes = false)
    {
        $routes = get_route_lists($selected, $fullroutes);
        $option_data = [];
        $pointer = ' - ';

        foreach ($routes as $base_group => $base_model) {
            if ('single' !== $base_group) {
                foreach ($base_model as $model_name => $data_model) {
                    foreach ($data_model as $model => $value) {
                        if ('route_data' === $model) {
                            $option_data[ucwords($base_group)][$value->route_base] = $pointer.canvastack_underscore_to_camelcase($model_name);
                        } else {
                            foreach ($value as $next_model => $next_val) {
                                if ('route_data' === $next_model) {
                                    $option_data[ucwords($base_group).' / '.ucwords($model_name)][$next_val->route_base] = $pointer.canvastack_underscore_to_camelcase($model);
                                } else {
                                    foreach ($next_val as $vKey => $val) {
                                        if ('route_data' === $vKey) {
                                            $option_data[ucwords($base_group).' / '.ucwords($model_name).' / '.ucwords($model)][$val->route_base] = $pointer.canvastack_underscore_to_camelcase($next_model);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                foreach ($base_model as $model_name => $data_model) {
                    foreach ($data_model as $model => $value) {
                        if ('route_data' === $model) {
                            $option_data[$value->route_base] = canvastack_underscore_to_camelcase($model_name);
                        }
                    }
                }
            }
        }

        return $option_data;
    }

    private function set_data_before_insert($request)
    {
        $module_slice = explode('.', $request->route_path);

        if (count($module_slice) >= 3) {
            $module_parent = "{$module_slice[0]} {$module_slice[1]}";
            $module_name = $module_slice[2];
        } elseif (count($module_slice) == 2) {
            $module_parent = $module_slice[0];
            $module_name = $module_slice[1];
        } else {
            $module_parent = $module_slice[0];
            $module_name = $module_parent;
        }

        $module_name = canvastack_underscore_to_camelcase($module_name);

        canvastack_merge_request($request, [
            'parent_name' => ucwords($module_parent),
            'module_name' => ucwords($module_name),
        ]);
    }

    private function role_check()
    {
        $this->get_session();
        if ('root' === $this->session['user_group']) {
            return redirect(url()->route('admin.index'));
        }
    }

    private function input_icons()
    {
        return canvastack_combobox_data(Icon::all(), 'class', 'label');
    }

    private function check_session($redirect)
    {
        $this->get_session();
        if ('root' !== $this->session['user_group']) {
            return redirect(url()->route($redirect));
        }
    }

    public function index()
    {
        $this->setPage();

        $this->table->mergeColumns('Module', ['module_name', 'parent_name']);

        $this->table->searchable(['module_name', 'route_path']);
        $this->table->clickable();
        $this->table->sortable();

        $this->table->filterGroups('module_name', 'selectbox', true);
        $this->table->filterGroups('route_path', 'selectbox', true);

        $this->table->lists($this->model_table, ['module_name', 'parent_name', 'route_path', 'flag_status', 'active']);
        $this->table->clear();

        return $this->render();
    }

    public function create()
    {
        $this->setPage();
        if (count($this->render_value_module_name()) >= 1) {
            $disabled = [];
        } else {
            $disabled = ['disabled' => 'disabled'];
        }

        $this->form->model();

        $this->form->selectbox('route_path', $this->render_value_module_name(), false);
        $this->form->text('module_name', null, $disabled);
        $this->form->textarea('module_info', null, $disabled);
        $this->form->selectbox('icon', $this->input_icons());

        if (count($this->render_value_module_name()) >= 1) {
            $this->form->selectbox('flag_status', flag_status($this->is_root), 2, ['required']);
            $this->form->selectbox('active', active_box(), false, ['required']);
            $this->form->close('Save Module', ['class' => 'btn btn-primary btn-slideright pull-right']);
        }

        return $this->render();
    }

    public function store(Request $request, $req = true)
    {
        $this->set_data_before_insert($request);

        $this->insert_data($request, false);

        return self::redirect("{$this->stored_id}/edit", $request);
    }

    public function edit($id)
    {
        $this->setPage();

        $model_data = $this->model->find($id);

        $this->form->model();
        $this->form->selectbox('route_path', $this->render_value_module_name($model_data->route_path), $model_data->route_path, ['readonly', 'disabled']);
        $this->form->text('parent_name', $model_data->parent_name, ['readonly']);
        $this->form->text('module_name', $model_data->module_name);
        $this->form->textarea('module_info');
        $this->form->selectbox('icon', $this->input_icons(), $model_data->icon);
        $this->form->selectbox('flag_status', flag_status($this->is_root), $model_data->flag_status);
        $this->form->selectbox('active', active_box(), $model_data->active);

        $this->form->close('Save Module', ['class' => 'btn btn-primary btn-slideright pull-right']);

        return $this->render();
    }

    public function update(Request $request, $id)
    {
        $this->set_data_before_insert($request);
        $this->update_data($request, $id);

        return self::redirect('edit', $request);
    }
}

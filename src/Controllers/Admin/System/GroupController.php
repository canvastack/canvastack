<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Admin\System\Includes\MappingPage;
use Canvastack\Canvastack\Controllers\Admin\System\Includes\Privileges;
use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Illuminate\Http\Request;

/**
 * Created on Jan 19, 2018
 * Time Created	: 7:25:45 PM
 * Filename		: GroupController.php
 *
 * @filesource	GroupController.php
 *
 * @author		wisnuwidi@IncoDIY - 2018
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class GroupController extends Controller
{
    use Privileges, MappingPage;

    public $data;

    private $id = false;

    private $_set_tab = [];

    private $_tab_config = [];

    private $_hide_fields = ['id'];

    public function __construct()
    {
        parent::__construct(Group::class, 'system.config');

        $this->setValidations(
            [
                'group_name' => 'required|unique:base_group',
                'group_info' => 'required',
                'active' => 'required',
            ], [
                'group_name' => 'required',
                'group_info' => 'required',
                'active' => 'required',
            ]
        );
    }

    /**
     * Render List(s) Datatables For Group Data
     *
     * created @Sep 11, 2018
     * author: wisnuwidi
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $this->setPage();

        if ('root' !== $this->session['user_group']) {
            $this->filterPage(['group_name' => 'root'], '!=');
        }

        $this->table->mergeColumns('Group', ['group_info', 'group_name', 'group_alias']);

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->filterGroups('group_name', 'selectbox', true);
        $this->table->filterGroups('group_alias', 'selectbox', true);
        $this->table->filterGroups('group_info', 'selectbox', true);

        $this->table->columnCondition('group_name', 'row', '==', $this->session['user_group'], 'background-color', 'rgba(222, 249, 195, 0.51)');
        $this->table->lists($this->model_table, ['group_info', 'group_name', 'group_alias', 'active']);

        return $this->render();
    }

    /**
     * Create Group Data
     *
     * created @Sep 11, 2017
     * author: wisnuwidi
     *
     * @tutorial: Description Logic as Internal ROOT
     * 		: This form will rendering all fields in group table including [ $this->platform_key ].
     *
     * @tutorial: Description Logic as External Administrator ( users )
     * 		: This form will rendering all fields in group table except [ $this->platform_key ].
     * 		: [ $this->platform_key ] data field would be posted into the database using [ $this->platform_key ] value saved in the user data sessions.
     *
     * @return array|\Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        $this->setPage();
        $this->get_menu();

        $this->form->model();
        $this->form->text('group_name', null, ['required']);
        $this->form->text('group_alias', null, ['required']);
        $this->form->text('group_info', null, ['required']);
        $this->form->selectbox('active', active_box(), false, ['required']);

        // SET MODULE PRIVILEGES
        $this->form->openTab('Module Privileges');
        $this->form->draw($this->group_privilege());

        // SET PAGE PRIVILEGES
        $this->form->openTab('Mapping Page Privileges');
        $this->form->draw($this->mapping());

        $this->form->closeTab();

        $this->form->close('Save Group');

        return $this->render();
    }

    /**
     * Stored to inserting data packages requested by $_POST data
     *
     * created @Sep 11, 2017
     * author: wisnuwidi
     *
     * @tutorial: 1. This script would check user sessions group.
     *
     * @tutorial: 2. If user has logged as External users Groups, the data requested would be merge [ $this->platform_key ] from user sessions.
     *
     * @tutorial: 3. If user has logged as Internal Root Group, [ $this->platform_key ] data requested would send by [ $this->platform_key ], posted by selected form.
     *
     * @tutorial: 4. Group name data inserted would be uniquee in every [ $this->platform_key ].
     *
     * @tutorial: 5. Modular data checkboxes, used for setting the access privileges in every single group in every single [ $this->platform_key ].
     *
     * @tutorial: 6. Modular data checkboxes collections values, would added after inserting group data.
     * 			 	 It would draw all the modular array before inserting in the base_group_privilege table ["see: $this->set_data_before_insert($callbackRequest, $model_id)"]
     *
     * @tutorial: 7. Last process, modular data collections package would insert into base_group_privilege table with group_id and module_id.
     * 			 	 These row data packages would set group privileges in every single group with every single [ $this->platform_key ]
     * 			 	 ["see: $this->set_data_after_insert($this->roles)"].
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->get_session();

        if (! empty($_GET['rolemapage'])) {
            return $this->rolepage($_POST, $_GET['usein']);
        }

        $requests = $request->all();                                       // collect all requests
        if (isset($requests['modules'])) {
            $modules = [];
            $modules['modules'] = $requests['modules'];                     // get modules requests, if any
            $request->offsetUnset('modules');                               // throw modules request before insert to group table)

            $mapPage = $this->map();
            $mapNode = $mapPage::$prefixNode;
            $rolepages = [];
            if (isset($requests['rolePages'])) {
                $rolepages[$mapNode] = $requests[$mapNode];
                $request->offsetUnset('rolePages');
            }

            $this->insert_data($request, false);
            $callbackRequest = $request->merge(array_merge($modules, $rolepages));     // callback the all requests
        } else {
            $this->insert_data($request, false);
            $callbackRequest = $request;
        }

        $this->set_data_before_insert($callbackRequest, $this->stored_id);
        $this->set_data_after_insert($this->roles);

        return self::redirect("{$this->stored_id}/edit", $request);
    }

    /**
     * Update Group Data
     *
     * created @Sep 11, 2017
     * author: wisnuwidi
     *
     * @param  int|string  $id
     *
     * @tutorial: Description Logic as Internal ROOT
     * 		: 1. This form will rendering all fields in group table including [ $this->platform_key ].
     * 		: 2. This form will rendering all checkbox privileges group when you edit other group except internal root (group) edit page.
     *
     * @tutorial: Description Logic as External Administrator ( users )
     * 		: 1. This form will rendering all fields in group table except [ $this->platform_key ].
     * 			 [ $this->platform_key ] data field would be posted into the database using [ $this->platform_key ] value saved in the user data sessions.
     * 		: 2. This form will rendering all checkbox privileges group when you edit other group except internal root (group) and external admin(group) edit page.
     *
     * @return array|\Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function edit($id)
    {
        $this->setPage();
        //	$this->filterPage(['group_name' => 'admin']);
        $this->get_menu();

        $this->form->model();
        $this->form->text('group_name', null, ['required', 'readonly']);
        $this->form->text('group_info', null, ['required']);
        $this->form->text('group_alias', null);
        $this->form->selectbox('active', active_box(), $this->model_data->active, ['required']);

        if (1 === $this->session['group_id'] || true === canvastack_string_contained($this->session['user_group'], 'admin')) {
            if ('root' !== $this->model_data->group_name) {
                // SET MODULE PRIVILEGES
                $this->form->openTab('Module Privileges');
                $this->form->draw($this->group_privilege());

                // SET PAGE PRIVILEGES
                $this->form->openTab('Mapping Page Privileges');
                $this->form->draw($this->mapping());

                $this->form->closeTab();

            }
        }
        $this->form->close('Save Group');

        return $this->render();
    }

    public function update(Request $request, $id)
    {
        $this->set_data_before_insert($request, $id);
        $this->update_data($request, $id);
        $this->set_data_after_insert($this->roles);

        return self::redirect('edit', $request);
    }

    private function set_data_before_insert($request, $model_id = false)
    {
        if (false === $model_id) {
            $getGroup = canvastack_query($this->model_table)
                ->where('group_name', $request->group_name)
                ->where('group_alias', $request->group_alias)
                ->where('group_info', $request->group_info)
                ->first();
        } else {
            $getGroup = canvastack_query($this->model_table)->where('id', $model_id)->first();
        }

        $this->privileges_before_insert($request, $getGroup);
        $this->mapping_before_insert($request, $getGroup);
    }

    private function set_data_after_insert($data)
    {
        $this->privileges_after_insert($data);
    }

    /**
     * Validate Group
     *
     * created @Sep 11, 2018
     * author: wisnuwidi
     *
     * @tutorial
     * 		:	This validation will count data group, filtered by [ $this->platform_key ] and group_name posted from data requests
     * 			used for detect duplicate group name in every [ $this->platform_key ]
     *
     * @param  object  $request
     * @return number
     */
    private function validation_groups($request)
    {
        $dataReq = $request->all();
        if (true === is_multiplatform()) {
            $objects = canvastack_query($this->model_table)->where($this->platform_key, $dataReq[$this->platform_key])->where('group_name', $dataReq['group_name'])->get();
        } else {
            $objects = canvastack_query($this->model_table)->where('group_name', $dataReq['group_name'])->get();
        }

        return count($objects);
    }

    /**
     * Get Current Group Data
     *
     * @tutorial: This data used for checking uniquee group name from group_name data posted
     *
     * created @Sep 11, 2018
     * author: wisnuwidi
     *
     * @param  int|string  $id
     * @return object
     */
    private function get_current_group($id)
    {
        return canvastack_query($this->model_table)->where('id', $id)->first();
    }
}

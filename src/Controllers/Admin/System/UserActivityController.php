<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Core\Controller;
use Canvastack\Canvastack\Core\Craft\Handler;
use Canvastack\Canvastack\Models\Admin\System\User as User;
use Canvastack\Canvastack\Models\Admin\System\UserActivity;
use Illuminate\Http\Request;

/**
 * Created on Jun 9, 2023
 *
 * Time Created : 1:48:59 PM
 *
 * @filesource  UserActivityController.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class UserActivityController extends Controller
{
    use Handler;

    private $fields = [
        'monthly_activity:Period',
        'role_group:Role',
        'role_location:Region',
        'fullname',
        'first_access',
        'last_access',
        'online_duration',
        'offline_duration',
        'login_counters',
        'hit_activity',
        'user_status',
    ];

    private $field_2 = [
        'group_info',
        'group_alias',
        'username',
        'fullname',
        'email',
        'user_status',
        'registered_date',
        'offline_duration',
    ];

    public function __construct()
    {
        parent::__construct(UserActivity::class, 'system.managements.user_activity');
    }

    public function index()
    {
        $this->setPage();
        $this->sessionFilters();
        $this->removeActionButtons(['add']);

        $this->table->setUrlValue('user_id');

        if (1 === $this->session['group_id']) {

            $this->table->openTab('User Never Login');
            $this->table->searchable(['user_status', 'username']);
            $this->table->clickable(false);
            $this->table->sortable();

            $this->table->filterGroups('user_status', 'selectbox', false);
            $this->table->filterGroups('username', 'selectbox', true);

            $this->table->columnCondition('user_status', 'cell', '==', 'Disabled', 'background-color', 'rgb(255, 242, 204)');
            $this->table->columnCondition('user_status', 'action', '==', 'Active', 'replace', 'ajax::manage|warning|check-square-o');
            $this->table->columnCondition('user_status', 'action', '==', 'Disabled', 'replace', 'ajax::manage|danger|power-off');

            $this->table->runModel($this->model, 'user_never_login::temp', false);
            $this->table->lists('temp_user_never_login', $this->field_2, ['manage']);
            $this->table->clear();

            $this->table->openTab('Monthly Activity');
        }

        $this->table->searchable(['user_status', 'monthly_activity', 'role_group', 'role_location', 'fullname']);
        $this->table->clickable(false);
        $this->table->sortable();
        /*
        $this->table->orderby('time_hits', 'asc');
        $this->table->orderby('period', 'desc'); */

        $this->table->filterGroups('user_status', 'selectbox', false);
        $this->table->filterGroups('monthly_activity', 'selectbox', true);
        $this->table->filterGroups('role_group', 'selectbox', true);
        $this->table->filterGroups('role_location', 'selectbox', true);
        $this->table->filterGroups('fullname', 'selectbox', false);

        //	$this->table->columnCondition('time_hits', 'cell', '<=', 10, 'background-color', 'rgb(255, 242, 204)');
        $this->table->columnCondition('user_status', 'cell', '==', 'Disabled', 'background-color', 'rgb(255, 242, 204)');
        $this->table->columnCondition('user_status', 'action', '==', 'Active', 'replace', 'ajax::manage|warning|check-square-o');
        $this->table->columnCondition('user_status', 'action', '==', 'Disabled', 'replace', 'ajax::manage|danger|power-off');

        //	$this->table->removeButtons(['view', 'edit', 'delete']);
        $this->table->setActions(['manage'], ['view', 'insert', 'edit', 'delete']);
        $this->table->addTabContent('
			<p style="margin-bottom: 1px !important;"><i><b>Information Table</b></i></p>
			<div style="background-color: #fbf2f2; margin: 0; padding: 10px; border: #fdd1d1 solid 1px; border-radius: 4px;">
				<p style="margin-bottom: 1px !important;"><i><b>First Access: </b>First time user login every month</i></p>
				<p style="margin-bottom: 1px !important;"><i><b>Last Access: </b>Last time user logout every month</i></p>
				<p style="margin-bottom: 1px !important;"><i><b>Online Duration: </b>Duration time user online calculated by First and Last User Access</i></p>
				<p style="margin-bottom: 1px !important;"><i><b>Offline Duration: </b>Duration time user offline till now time in current month or every end of month</i></p>
				<p style="margin-bottom: 1px !important;"><i><b>Login Counter: </b>Total user login activity calculated every month</i></p>
				<p style="margin-bottom: 1px !important;"><i><b>Hit Activity: </b>Total user time hit in all module/pages calculated every month</i></p>
			</div>
			<br />
		');

        $this->table->runModel($this->model, 'montly_activity::temp', false);
        $this->table->lists($this->model_table, $this->fields, ['manage']);
        $this->table->clear();

        if (1 === $this->session['group_id']) {
            $this->table->closeTab();
        }

        return $this->render();
    }

    public function manage(Request $request)
    {
        if (! empty($request)) {
            $user = new User();
            $reqs = $request->all();
            $id = intval($reqs['data']);
            $data = $user->find($id)->getAttributes();

            $new_array = ['active' => 1];
            if ($data['active'] >= 1) {
                $new_array = ['active' => 0];
            }

            $request = new Request();
            $request->merge($new_array);

            canvastack_update($user->find($id), $request, true);
        }
    }
}

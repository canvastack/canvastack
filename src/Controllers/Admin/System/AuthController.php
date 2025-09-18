<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Core\Controller;
use Canvastack\Canvastack\Core\Craft\Includes\Privileges;
use Canvastack\Canvastack\Models\Admin\System\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

//use App\Models\Admin\System\Maintenance;

/**
 * Created on Mar 6, 2017
 * Time Created	: 2:06:11 PM
 * Filename		: AuthController.php
 *
 * @filesource	AuthController.php
 *
 * @author		wisnuwidi @IncoDIY - 2017
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class AuthController extends Controller
{
    use Privileges;
    use AuthenticatesUsers;

    private $authRouteInfo = [];

    public function __construct()
    {
        parent::__construct();

        // Check if we have a current route (not available during artisan commands)
        $currentRoute = Route::getCurrentRoute();
        if ($currentRoute && $currentRoute->getName()) {
            $this->authRouteInfo['current_path'] = $currentRoute->getName();
            if ('login_processor' === strtolower($currentRoute->getName())) {
                $this->authRouteInfo['module_name'] = 'Login';
            } else {
                $this->authRouteInfo['module_name'] = ucwords($currentRoute->getName());
            }
            $this->authRouteInfo['page_info'] = strtolower($currentRoute->getName());
        } else {
            // Fallback for when no route is available (e.g., during artisan commands)
            $this->authRouteInfo['current_path'] = 'unknown';
            $this->authRouteInfo['module_name'] = 'Unknown';
            $this->authRouteInfo['page_info'] = 'unknown';
        }
        
        $this->authRouteInfo['controller'] = 'AuthController';
    }

    public function login()
    {
        //	$this->get_maintenance_content();
        if (true === $this->getLogin) {
            $this->meta->title(__('Login'));

            if (null !== Auth::user()) {
                return $this->firstRedirect();
            } else {
                $this->destroy_user_sessions();

                $this->setPageType(false);
                $this->setPage(__('Login'), 'login');

                return $this->render($this->data);
            }
        } else {
            return view('pages.index.maintenance', $this->data_maintenance);
        }
    }

    private $data_maintenance = [];

    private function get_maintenance_content()
    {
        $maintenance = ''; //new Maintenance;
        $objects = $maintenance::where('status', 1)->get();
        $basePath = str_replace(get_config('settings.index_folder'), '', get_config('settings.baseURL'));
        $data = [];

        foreach ($objects as $object) {
            $time_durations = explode(' | ', $object->time_duration);
            $date_first = date('Y-m-d H:i:s');
            if (strtotime($time_durations[0]) >= strtotime($date_first)) {
                $date_first = "{$time_durations[0]} 00:00:00";
            }
            $durations = daterange_to_seconds($date_first, "{$time_durations[1]} 00:00:00");

            $data['title'] = $object->title;
            $data['description'] = $object->description;
            $data['logo'] = "{$basePath}{$object->logo}";
            $data['image'] = "{$basePath}{$object->image}";
            $data['time_durations'] = intval($durations);
            $data['subscribe_button'] = $object->subscribe_button;
            $data['subscribe_text'] = $object->subscribe_text;
        }
        $this->data_maintenance = $data;

        if (true === $this->maintenance) {
            $this->getLogin = false;
            if (count($_GET) >= 1) {
                $type_as = 'name';
                if (true === str_contains($_GET['as'], '@') || true === str_contains($_GET['as'], '.com') || true === str_contains($_GET['as'], '.net') || true === str_contains($_GET['as'], '.org') || true === str_contains($_GET['as'], '.web')) {
                    $type_as = 'email';
                }

                $check = query('users')->where($type_as, '=', $_GET['as'])->pluck('active', 'id');
                foreach ($check as $data) {
                    $this->getLogin = (bool) $data;
                }
            }
        }
    }

    private $sendRequestKeyWith = 'username';

    public function username()
    {
        return $this->sendRequestKeyWith;
    }

    public function login_processor(Request $request)
    {
        $dataRequests = $request->all();

        if (array_key_exists('email', $dataRequests)) {
            $this->sendRequestKeyWith = 'email';
        } else {
            $this->sendRequestKeyWith = 'username';
        }

        $this->validateLogin($request);
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }
        $this->incrementLoginAttempts($request);

        if (! empty($request['_token']) && $request['_token'] === $request->session()->token()) {
            $data = $request->only($this->sendRequestKeyWith, 'password');

            if (Auth::attempt($data)) {

                $this->set_session_auth($data[$this->sendRequestKeyWith]);
                foreach ($this->session_auth as $session_key => $session_auth) {
                    $request->session()->put($session_key, $session_auth);
                }

                /*
                if (true === $this->maintenance) {
                    $sessions = Session::all();
                    if ('root' !== strtolower($sessions['user_group'])) {
                        if (!empty(auth()->user()->id)) {
                            $this->add_log('Logout', auth()->user()->id);
                            Auth::logout();
                        }

                        return redirect()->route('login');
                    }
                }
                 */
                return $this->firstRedirect();
            }
        }

        return back()->withInput();
    }

    /**
     * Filter User Aliases
     *
     * @param  string  $alias
     *
     * @example: ':filterName|value (separated by [,]colon)'
     *
     * @return array|mixed
     */
    private function filterUserAliases($alias = null)
    {
        $filterAliases = $alias;

        if (canvastack_string_contained($alias, ':')) {
            $filterAliases = [];
            $filterAlias = str_replace(':', '', $alias);

            if (canvastack_string_contained($filterAlias, ',')) {
                $_filterAliases = explode(',', $filterAlias);
                foreach ($_filterAliases as $filterAliasData) {
                    $filterAliasArray = explode('|', $filterAliasData);
                    $filterAliases[$filterAliasArray[0]][] = $filterAliasArray[1];
                }
            } else {
                $filterAliasArray = explode('|', $filterAlias);
                $filterAliases[$filterAliasArray[0]][] = $filterAliasArray[1];
            }
        }

        return $filterAliases;
    }

    public function set_session_auth($requestKey, $return_data = false)
    {
        $userData = [];
        $user_data = User::where($this->sendRequestKeyWith, $requestKey)->get();

        foreach ($user_data as $user) {
            $user_info = User::find($user->id);
            $group_info = (object) $user_info->groupInfo();
            $user_alias = 'user_alias';
            if (! empty(canvastack_config('user.alias_session_name'))) {
                $user_alias = canvastack_config('user.alias_session_name');
            }

            $userData['id'] = $user->id;
            $userData['group_id'] = $group_info->id;
            $userData['user_group'] = $group_info->group_name;
            if (! empty($group_info->group_alias)) {
                $userData['group_alias'] = $group_info->group_alias;
            }
            $userData[$user_alias] = $this->filterUserAliases($user->alias);
            $userData['group_info'] = $group_info->group_info;
            $userData['privileges'] = $this->set_module_privileges($group_info->id);

            $userData['username'] = $user->username;
            $userData['fullname'] = $user->fullname;
            $userData['email'] = $user->email;
            $userData['phone'] = $user->phone;
            $userData['ip_address'] = $user->ip_address;
            $userData['reg_date'] = $user->reg_date;
            $userData['last_visit'] = $user->last_visit_date;
            $userData['past_visit'] = $user->past_visit_date;
            $userData['change_password'] = $user->change_password;
            $userData['last_change_password'] = $user->last_change_password_date;
            $userData['expire_date'] = $user->expire_date;
            $userData['updated_at'] = $user->updated_at;
            $userData['cryptcode'] = $user->cryptcode;
            $userData['active'] = $user->active;
        }

        if ($userData['active'] >= 1) {
            canvastack_log_activity($this->authRouteInfo, $userData);
            if (false === $return_data) {
                $this->session_auth = $userData;
                //	$this->add_log('Login', $this->session_auth['id']);
            } else {
                return $userData;
            }
        } else {
            $this->logout();
        }
    }

    public function destroy_user_sessions()
    {
        Session::forget('_previous');
        Session::forget('_flash');

        Session::forget('id');
        Session::forget('group_id');

        Session::forget('user_group');
        Session::forget('group_alias');
        Session::forget('group_info');
        Session::forget('name');
        Session::forget('fullname');
        Session::forget('email');
        Session::forget('phone');
        Session::forget('ip_address');
        Session::forget('reg_date');
        Session::forget('last_visit');
        Session::forget('past_visit');
        Session::forget('change_password');
        Session::forget('last_change_password');
        Session::forget('expire_date');
        Session::forget('updated_at');
        Session::forget('active');
    }

    public function logout()
    {
        if (! empty(auth()->user()->id)) {
            //	$this->add_log('Logout', auth()->user()->id);

            canvastack_log_activity($this->authRouteInfo, $this->session);
            Session::flush();
            Auth::logout();
        }

        return redirect()->route('login');
    }
}

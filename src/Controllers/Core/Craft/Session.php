<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft;

//use Illuminate\Support\Facades\Session as Sessions;

/**
 * Created on 24 Mar 2021
 * Time Created	: 13:15:08
 *
 * @filesource	Session.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
trait Session
{
    public $session = [];

    public $session_roles = [];

    /**
     * Set Session From User Logged In
     */
    public function set_session()
    {
        $session_original = canvastack_sessions();
        $this->session = $session_original;
        $this->data['sessions'] = $this->session;

        $sessions = [];
        if (! empty($session_original['id'])) {
            $sessions['roles']['user_id'] = $session_original['id'];
            $sessions['roles']['username'] = $session_original['username'];
            $sessions['roles']['group_id'] = $session_original['group_id'];
            $sessions['roles']['user_group'] = $session_original['user_group'];
            $sessions['roles']['group_info'] = $session_original['group_info'];
            $sessions['roles']['fullname'] = $session_original['fullname'];
            $sessions['roles']['email'] = $session_original['email'];
            $sessions['roles']['phone'] = $session_original['phone'];

            $this->session_roles = $sessions;
        }
    }

    /**
     * Get All Session From User Logged In
     *
     * @return array [ $this->session ]
     */
    public function get_session($return_data = false)
    {
        $this->set_session();

        if (true === $return_data) {
            return $this->session;
        }
    }

    public function group_check($group_name)
    {
        if ($group_name === $this->session['user_group']) {
            return true;
        } else {
            return false;
        }
    }
}

<?php

namespace Canvastack\Canvastack\Controllers\Auth;

use Canvastack\Canvastack\Core\Controller;

// use Controllers\Controller;
//use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller
{
    /*
     * |--------------------------------------------------------------------------
     * | Password Reset Controller
     * |--------------------------------------------------------------------------
     * |
     * | This controller is responsible for handling password reset emails and
     * | includes a trait which assists in sending these notifications from
     * | your application to your users. Feel free to explore this trait.
     * |
     */
    //use SendsPasswordResetEmails;

    private $name = 'passwords';

    private $route_path = 'passwords.email';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        $this->init_page(false, $this->route_path);
        $this->set_page($this->name, $this->name);

        return $this->render();
    }
}

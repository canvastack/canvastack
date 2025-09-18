<?php

namespace Canvastack\Canvastack\Controllers\Auth;

use Canvastack\Canvastack\Controllers\Admin\System\AuthController;
use Canvastack\Canvastack\Core\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
     * |--------------------------------------------------------------------------
     * | Password Reset Controller
     * |--------------------------------------------------------------------------
     * |
     * | This controller is responsible for handling password reset requests
     * | and uses a simple trait to include this behavior. You're free to
     * | explore this trait and override any methods you wish to tweak.
     * |
     */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    private $name = 'passwords';

    private $route_path = 'passwords.reset';

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

    public function showResetForm(Request $request, $token = null)
    {
        $this->init_page(false, $this->route_path);
        $this->set_page($this->name, $this->name);

        return $this->render(false, [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        event(new PasswordReset($user));

        $request = new Request();
        $request->merge([
            'email' => $user->email,
            'password' => $password,
        ]);

        $data = $request->only('email', 'password');
        if (Auth::attempt($data)) {
            $action_login = new AuthController();
            session($action_login->set_session_auth($data['email'], true));

            $sessions = Session()->all();
            if (1 === intval($sessions['group_id'])) {
                $this->redirectTo = $this->rootPage;
            } else {
                $this->redirectTo = $this->adminPage;
            }
        }
    }
}

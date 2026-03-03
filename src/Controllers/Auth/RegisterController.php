<?php

namespace Canvastack\Origin\Controllers\Auth;

use Canvastack\Origin\Models\Admin\System\User;
#use Controllers\Controller;
use Canvastack\Origin\Controllers\Core\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller {
	
	/**
	 * |--------------------------------------------------------------------------
	 * | Register Controller
	 * |--------------------------------------------------------------------------
	 * |
	 * | This controller handles the registration of new users as well as their
	 * | validation and creation. By default this controller uses a trait to
	 * | provide this functionality without requiring any additional code.
	 * |
	 */
	
	use RegistersUsers;

	/**
	 * Where to redirect users after registration.
	 *
	 * @var string
	 */
	protected $redirectTo	= '/home';
	private $name			= 'register';
	
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		
		$this->middleware('guest');
	}
	
	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param array $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function validator(array $data) {
		return Validator::make($data, [ 
			'name'		=> 'required|string|max:255',
			'email'		=> 'required|string|email|max:255|unique:users',
			'password'	=> 'required|string|min:6|confirmed'
		]);
	}
	
	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param array $data
	 * @return \Canvastack\Origin\Models\Admin\System\User
	 */
	public function create() {
		$data = [];
		return User::create([ 
			'name'		=> $data['name'],
			'email'		=> $data['email'],
			'password'	=> Hash::make($data['password'])
		]);
	}
	
	public function showRegistrationForm() {
		$this->init_page(false, $this->name);
		$this->set_page($this->name, $this->name);
		
		return $this->render();
	}
}

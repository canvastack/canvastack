<?php
/**
 * Created on Mar 6, 2017
 * Time Created	: 2:10:55 PM
 * Filename		: login.blade.php
 *
 * @filesource	login.blade.php
 *
 * @author		wisnuwidi @gmail.com - 2017
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
$config               = [];
$config['title']      = __('Admin Login');
$config['logo']       = asset('assets/templates/default') . '/images/logo-almasjid.png';
$config['background'] = null;
if (!empty($content_page['login_page'])) {
	if (!empty($content_page['login_page']['title']))      $config['title']      = $content_page['login_page']['title'];
	if (!empty($content_page['login_page']['logo']))       $config['logo']       = $content_page['login_page']['logo'];
	if (!empty($content_page['login_page']['background'])) $config['background'] = $content_page['login_page']['background'];
}

$background = ' class="login-area login-bg"';
$formStyle  = null;
if (!empty($config['background'])) {
	$background = ' class="login-area login-bg" style="background: url(' . $config['background'] . ') center/cover no-repeat !important;z-index:none !important"';
	$formStyle  = 'left:-35% !important;position:relative;margin:auto 15% !important;';
}
$formStyle  = null;
$mailErrorClass = '';
if ($errors->has('email')) {
    $mailErrorClass = ' is-invalid';
}
$usernameErrorClass = '';
if ($errors->has('username')) {
    $usernameErrorClass = ' is-invalid';
}
?>

@extends('default.template.admin.index')

@section('content')

		<div{!! $background !!}>
			<div class="container">
				<div class="login-box ptb--40">
					{!! \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formOpen(['route' => 'login_processor', 'class' => 'sign-in form-horizontal shadow rounded no-overflow', 'style' => $formStyle]) !!}
						<div class="login-form-head">
							<div class="logo"><img src="{{ $config['logo'] }}" /></div>
							<h4>{{ $config['title'] }}</h4>
						</div>
						<div class="login-form-body">
							<div class="form-gp">
								<label class="IncoDIY-input-login" for="IncoDIY-input-login-key">{{ __('Username') }}</label>
								{!! \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formText('username', old('username'), ['id' => 'IncoDIY-input-login-key', 'required', 'autofocus']) !!}
								<i id="info-login" class="ti-user"></i>
								
								@if ($errors->has('username')) 
								<span class="invalid-feedback" role="alert">
									<strong>{{ $errors->first('username') }}</strong>
								</span>
								@endif
								
								@if ($errors->has('email'))
								<span class="invalid-feedback" role="alert">
									<strong>{{ $errors->first('email') }}</strong>
								</span>
								@endif
							</div>
							<div class="form-gp">
								<label for="IncoDIY-input-login-password">{{ __('Password') }}</label>
								{!! \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formPassword('password', ['id' => 'IncoDIY-input-login-password', 'required']) !!}
								<i class="ti-lock"></i>
								
								@if ($errors->has('password'))
								<span class="invalid-feedback" role="alert">
									<strong>{{ $errors->first('password') }}</strong>
								</span>
								@endif
							</div>
							<script type="text/javascript">
								function validateEmail(objMail) {
                                	var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
                                	var email = objMail[0].value;
                                	var outPut = false;
                                 	if (pattern.test(String(email).toLowerCase())) {
                                		outPut = true;
                                	}
                                	
                                	return outPut;
                                }
								$(document).ready(function() {
                                    $("#IncoDIY-input-login-key").focusout(function(){
    									if (true == validateEmail($(this))) {
    										$('label.IncoDIY-input-login').text('E-Mail Address');
    										$(this).attr('name', 'email');
    										$(this).attr('class', ${!! $mailErrorClass !!} );
    										$('i#info-login').attr('class', 'ti-email');
    									} else {
    										$('label.IncoDIY-input-login').text('Username');
    										$(this).attr('name', 'username');
    										$(this).attr('class', {!! $usernameErrorClass !!} );
    										$('i#info-login').attr('class', 'ti-user');
    									}
                                    });
                                });
							</script>
							<div class="row mb-4 rmber-area">
								<div class="col-6">
									<div class="custom-control custom-checkbox mr-sm-2">
										<!-- <input type="checkbox" class="custom-control-input" id="customControlAutosizing" {{ old('remember') ? 'checked' : '' }} />
										<label class="custom-control-label" for="customControlAutosizing">{{ __('Remember Me') }}</label> -->
									</div>
								</div>
								<div class="col-6 text-right">
									<!-- <a href="{{ route('password.request') }}" id="forgot-password-button" data-toggle="modalxxx" data-target="#forgot-password-boxxx">{{ __('Forgot Your Password?') }}</a> -->
								</div>
							</div>
							<div class="submit-btn-area">
								{!! \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formSubmit('Submit', ['class' => 'btn btn-primary btn-lg btn-block no-margin rounded', 'id' => 'login-btn']) !!}
							</div>
							<div class="form-footer text-center mt-5">
								<p class="text-muted">IncoDIY Application Dashboard<a href="#">{{ __('IncoDIY') }}</a></p>
							</div>
						</div>
					{!! \Canvastack\Canvastack\Library\Components\Utility\Canvatility::formClose() !!}
				</div>
			</div>
		</div>
		
@endsection



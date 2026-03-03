<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'login' => [
        'title' => 'Login',
        'subtitle' => 'Sign in to your account',
        'email' => 'Email Address',
        'password' => 'Password',
        'remember_me' => 'Remember Me',
        'forgot_password' => 'Forgot Password?',
        'submit' => 'Sign In',
        'no_account' => "Don't have an account?",
        'register' => 'Register',
    ],

    'register' => [
        'title' => 'Register',
        'subtitle' => 'Create a new account',
        'name' => 'Full Name',
        'email' => 'Email Address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'agree_terms' => 'I agree to the Terms and Conditions',
        'submit' => 'Create Account',
        'have_account' => 'Already have an account?',
        'login' => 'Login',
    ],

    'forgot_password' => [
        'title' => 'Forgot Password',
        'subtitle' => 'Enter your email to reset your password',
        'email' => 'Email Address',
        'submit' => 'Send Reset Link',
        'back_to_login' => 'Back to Login',
    ],

    'reset_password' => [
        'title' => 'Reset Password',
        'subtitle' => 'Enter your new password',
        'email' => 'Email Address',
        'password' => 'New Password',
        'password_confirmation' => 'Confirm New Password',
        'submit' => 'Reset Password',
    ],

    'verify_email' => [
        'title' => 'Verify Email',
        'subtitle' => 'Please verify your email address',
        'message' => 'A verification link has been sent to your email address.',
        'not_received' => "Didn't receive the email?",
        'resend' => 'Resend verification email',
    ],

    'logout' => [
        'title' => 'Logout',
        'message' => 'Are you sure you want to logout?',
        'confirm' => 'Logout',
        'cancel' => 'Cancel',
    ],
];

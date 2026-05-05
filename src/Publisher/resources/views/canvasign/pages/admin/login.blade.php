<?php
/**
 * Login Page — Canvasign Template
 *
 * @filesource  login.blade.php
 * @author      wisnuwidi@canvastack.com
 */

// Determine error classes for pre-filled state after failed login
$keyErrorClass = '';
if ($errors->has('email'))    $keyErrorClass = ' is-invalid';
if ($errors->has('username')) $keyErrorClass = ' is-invalid';

// Restore previous input type (email or username) after redirect back
$oldKey  = old('email') ?? old('username') ?? '';
$isEmail = !empty($oldKey) && filter_var($oldKey, FILTER_VALIDATE_EMAIL);
?>

@extends('canvasign.template.admin.index')

@section('content')

<div class="auth-wrap">

    {{-- ============================================================
         LEFT SIDE — Branding panel
         ============================================================ --}}
    <div class="auth-side">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-icon" style="background:rgba(255,255,255,.2)">
                <i class="bi bi-hexagon-fill"></i>
            </div>
            <strong>{{ config('app.name', 'CanvaStack') }}.</strong>
        </div>
        <div>
            <h2>{{ __('Welcome back to your premium admin dashboard.') }}</h2>
            <p class="opacity-75">{{ __('Manage your team, track revenue and ship faster — all in one place.') }}</p>
        </div>
        <small class="opacity-75">© {{ date('Y') }} {{ config('app.name', 'CanvaStack') }} Inc.</small>
    </div>

    {{-- ============================================================
         RIGHT SIDE — Login form
         ============================================================ --}}
    <div class="auth-form-wrap">
        <div class="auth-form">

            {{-- Theme toggle --}}
            <div class="d-flex justify-content-end mb-2">
                <button class="icon-btn" type="button" data-theme-toggle title="{{ __('Toggle theme') }}">
                    <i data-theme-icon class="bi bi-sun"></i>
                </button>
            </div>

            <h1 class="mb-1">{{ __('Sign in') }}</h1>
            <p class="text-muted mb-4">{{ __('Enter your credentials to access the dashboard.') }}</p>

            {{-- Error / status alerts --}}
            @if ($errors->any())
                <div class="alert alert-danger mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    @if ($errors->has('email'))
                        {{ $errors->first('email') }}
                    @elseif ($errors->has('username'))
                        {{ $errors->first('username') }}
                    @else
                        {{ $errors->first() }}
                    @endif
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success mb-3" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login_processor') }}" class="needs-validation" novalidate>
                @csrf

                {{-- Smart Email / Username field --}}
                <div class="mb-3">
                    <label class="form-label" for="login-key" id="login-key-label">
                        {{ $isEmail ? __('E-Mail Address') : __('Username') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i id="login-key-icon" class="bi {{ $isEmail ? 'bi-envelope' : 'bi-person' }}"></i>
                        </span>
                        <input
                            id="login-key"
                            type="{{ $isEmail ? 'email' : 'text' }}"
                            name="{{ $isEmail ? 'email' : 'username' }}"
                            class="form-control{{ $keyErrorClass }}"
                            value="{{ $oldKey }}"
                            placeholder="{{ __('Email or username') }}"
                            required
                            autofocus
                            autocomplete="{{ $isEmail ? 'email' : 'username' }}"
                        >
                    </div>
                    <div class="form-text text-muted" style="font-size:.78rem">
                        <i class="bi bi-info-circle me-1"></i>{{ __('Enter your email address or username') }}
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label class="form-label" for="login-password">{{ __('Password') }}</label>
                    <div class="input-group">
                        <input
                            id="login-password"
                            type="password"
                            name="password"
                            class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            required
                            autocomplete="current-password"
                        >
                        <button class="btn btn-outline-secondary" type="button"
                                data-toggle-password="#login-password"
                                tabindex="-1"
                                aria-label="{{ __('Toggle password visibility') }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('password') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Remember me + Forgot password --}}
                <div class="d-flex justify-content-between mb-3">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="remember"
                            id="login-remember"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="login-remember">
                            {{ __('Remember me') }}
                        </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="small">
                        {{ __('Forgot password?') }}
                    </a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    {{ __('Sign in') }}
                </button>

                {{-- Registration link --}}
                <p class="text-center mt-4 mb-0 text-muted">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('register') }}">{{ __('Sign up') }}</a>
                </p>

            </form>
        </div>
    </div>

</div>

{{-- Login-specific JS — loaded inline here so it works even without $components --}}
<script src="{{ asset('assets/templates/canvasign/js/pages/canvasign-login.js') }}"></script>

@endsection

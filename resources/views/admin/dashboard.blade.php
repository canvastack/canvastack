@extends('canvastack::layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Welcome to CanvaStack Admin Panel</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card bg-white dark:bg-gray-900 shadow-lg">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ \App\Models\User::count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-white dark:bg-gray-900 shadow-lg">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Verified</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ \App\Models\User::whereNotNull('email_verified_at')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-950 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-white dark:bg-gray-900 shadow-lg">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Unverified</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ \App\Models\User::whereNull('email_verified_at')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-950 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-white dark:bg-gray-900 shadow-lg">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">New Today</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ \App\Models\User::whereDate('created_at', today())->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart Card --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">User Growth</h2>
        {!! $chart->render() !!}
    </div>
</div>
@endsection

@extends('canvastack::layouts.admin')

@section('title', 'CanvaStack Tests')
@section('page-title', 'CanvaStack Tests')

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">CanvaStack Component Tests</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Test all CanvaStack components and features</p>
</div>

{{-- Table Builder Tests --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6">
    <div class="card-body">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table Builder Tests</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Method 1: Manual Configuration --}}
            <div class="p-6 border-2 border-blue-200 dark:border-blue-800 rounded-xl hover:border-blue-400 dark:hover:border-blue-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="settings" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Method 1: Manual Configuration</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Traditional approach with explicit configuration</p>
                        <div class="flex gap-2">
                            <a href="{{ route('test.table-method1') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                Single Table
                            </a>
                            <a href="{{ route('test.multi-table-method1') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                Multi-Table
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Method 2: lists() Enhancement --}}
            <div class="p-6 border-2 border-green-200 dark:border-green-800 rounded-xl hover:border-green-400 dark:hover:border-green-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="zap" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Method 2: lists() Enhancement</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Modern approach with auto-detection</p>
                        <div class="flex gap-2">
                            <a href="{{ route('test.table-method2') }}" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                Single Table
                            </a>
                            <a href="{{ route('test.multi-table-method2') }}" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                Multi-Table
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Other Component Tests --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg">
    <div class="card-body">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Other Components</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Form Builder --}}
            <a href="{{ route('test.form-create') }}" class="p-6 border-2 border-gray-200 dark:border-gray-800 rounded-xl hover:border-indigo-400 dark:hover:border-indigo-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="file-text" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Form Builder</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Dynamic form generation</p>
                    </div>
                </div>
            </a>
            
            {{-- Chart Builder --}}
            <a href="{{ route('test.chart') }}" class="p-6 border-2 border-gray-200 dark:border-gray-800 rounded-xl hover:border-purple-400 dark:hover:border-purple-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="bar-chart-3" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Chart Builder</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Data visualization</p>
                    </div>
                </div>
            </a>
            
            {{-- Theme System --}}
            <a href="{{ route('test.theme') }}" class="p-6 border-2 border-gray-200 dark:border-gray-800 rounded-xl hover:border-pink-400 dark:hover:border-pink-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="palette" class="w-6 h-6 text-pink-600 dark:text-pink-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Theme System</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Theme management</p>
                    </div>
                </div>
            </a>
            
            {{-- i18n System --}}
            <a href="{{ route('test.i18n') }}" class="p-6 border-2 border-gray-200 dark:border-gray-800 rounded-xl hover:border-orange-400 dark:hover:border-orange-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="globe" class="w-6 h-6 text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">i18n System</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Internationalization</p>
                    </div>
                </div>
            </a>
            
            {{-- Dashboard --}}
            <a href="{{ route('test.dashboard') }}" class="p-6 border-2 border-gray-200 dark:border-gray-800 rounded-xl hover:border-teal-400 dark:hover:border-teal-600 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-100 dark:bg-teal-950 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="layout-dashboard" class="w-6 h-6 text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Dashboard</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Complete dashboard</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

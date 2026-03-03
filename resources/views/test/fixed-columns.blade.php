@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">{{ $testName }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $description }}</p>
    </div>
    
    {{-- Test Navigation --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('test.fixed-columns.left') }}" 
           class="px-4 py-2 rounded-lg {{ request()->routeIs('test.fixed-columns.left') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800' }}">
            Left Fixed
        </a>
        <a href="{{ route('test.fixed-columns.right') }}" 
           class="px-4 py-2 rounded-lg {{ request()->routeIs('test.fixed-columns.right') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800' }}">
            Right Fixed
        </a>
        <a href="{{ route('test.fixed-columns.both') }}" 
           class="px-4 py-2 rounded-lg {{ request()->routeIs('test.fixed-columns.both') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800' }}">
            Both Fixed
        </a>
        <a href="{{ route('test.fixed-columns.none') }}" 
           class="px-4 py-2 rounded-lg {{ request()->routeIs('test.fixed-columns.none') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800' }}">
            No Fixed
        </a>
    </div>
    
    {{-- Instructions --}}
    <div class="alert alert-info mb-6">
        <i data-lucide="info" class="w-5 h-5"></i>
        <div>
            <p class="font-semibold mb-1">How to Test:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                <li>Scroll horizontally to see fixed columns in action</li>
                <li>Fixed columns stay visible while scrolling</li>
                <li>Try resizing the browser window</li>
                <li>Test dark mode toggle</li>
                <li>Compare with "No Fixed" option</li>
            </ul>
        </div>
    </div>
    
    {{-- Table Card --}}
    <div class="card">
        <div class="card-body">
            {!! $table->render() !!}
        </div>
    </div>
    
    {{-- Technical Details --}}
    <div class="mt-6 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
        <h3 class="font-semibold mb-2">Technical Details:</h3>
        <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <li><strong>DataTables Version:</strong> 2.0.0</li>
            <li><strong>FixedColumns Extension:</strong> 5.0.0</li>
            <li><strong>Scroll Height:</strong> 500px</li>
            <li><strong>Responsive Mode:</strong> {{ request()->routeIs('test.fixed-columns.none') ? 'Enabled' : 'Disabled (conflicts with fixed columns)' }}</li>
        </ul>
    </div>
</div>
@endsection

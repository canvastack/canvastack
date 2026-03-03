@extends('canvastack::layouts.admin')

@section('title', 'Multi-Table')
@section('page-title', 'Multi-Table')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Multi-Table</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Testing multiple tables from same data source</p>
</div>

{{-- Table 1: Basic Info --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 1: Basic Information</h2>
        {!! $table1Html !!}
    </div>
</div>

{{-- Table 2: Verification Status --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 2: Verification Status</h2>
        {!! $table2Html !!}
    </div>
</div>

{{-- Table 3: Timestamps --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 3: Timestamps</h2>
        {!! $table3Html !!}
    </div>
</div>
@endsection

@extends('canvastack::layouts.admin')

@section('title', 'Table Builder')
@section('page-title', 'Table Builder')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Table Builder</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Testing CanvaStack TableBuilder component</p>
</div>

{{-- Table Card --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Users Table</h2>
        {!! $tableHtml !!}
    </div>
</div>
@endsection

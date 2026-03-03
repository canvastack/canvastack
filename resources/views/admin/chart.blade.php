@extends('canvastack::layouts.admin')

@section('title', 'Chart Builder')
@section('page-title', 'Chart Builder')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Chart Builder</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Testing CanvaStack ChartBuilder component</p>
</div>

{{-- Chart Card --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Sales & Revenue Chart</h2>
        {!! $chart->render() !!}
    </div>
</div>
@endsection

@extends('canvastack::layouts.admin')

@section('title', 'Form Builder')
@section('page-title', 'Form Builder')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Form Builder</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Testing CanvaStack FormBuilder component</p>
</div>

{{-- Form Card --}}
<div class="card bg-white dark:bg-gray-900 shadow-lg max-w-2xl mx-auto">
    <div class="card-body">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">User Form</h2>
        {!! $form->render() !!}
    </div>
</div>
@endsection

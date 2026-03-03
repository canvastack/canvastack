@extends('canvastack::layouts.admin')

@section('title', $page_title ?? 'Dashboard')
@section('page-title', $page_title ?? 'Dashboard')

@push('head')
    @if(isset($meta))
        {!! $meta->tags() !!}
    @endif
@endpush

@section('content')
{{-- Page Header --}}
@if(isset($page_title))
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $page_title }}</h1>
    @if(isset($page_description))
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $page_description }}</p>
    @endif
</div>
@endif

{{-- Content Page - Render all components dynamically --}}
@if(isset($content_page) && !empty($content_page))
    @foreach($content_page as $content)
        {!! $content !!}
    @endforeach
@endif
@endsection

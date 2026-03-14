@extends('canvastack::layouts.public')

@section('title', 'Home')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Hero Section --}}
<section class="pt-32 pb-20 px-4 gradient-bg-subtle">
    <div class="max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium mb-6">
            <i data-lucide="sparkles" class="w-4 h-4"></i> Modern CanvaStack CMS Package
        </div>
        <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
            Build Powerful<br><span class="gradient-text">Admin Panels</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-10">
            A beautifully crafted CanvaStack CMS package with dark mode, theme system, i18n support, and all the components you need to build your admin panel.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('page.dashboard') }}" class="px-8 py-3.5 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25 flex items-center gap-2">
                <i data-lucide="rocket" class="w-4 h-4"></i> View Dashboard
            </a>
            <a href="{{ route('about') }}" class="px-8 py-3.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4"></i> Learn More
            </a>
        </div>
    </div>
</section>

{{-- Features --}}
<section class="py-20 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Everything You <span class="gradient-text">Need</span></h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Packed with features to help you build amazing admin experiences.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="moon" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Dark Mode</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Toggle between light and dark themes with localStorage persistence.</p>
            </div>
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="panel-left" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Collapsible Sidebar</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Responsive sidebar that collapses to icons on smaller screens.</p>
            </div>
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="blocks" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Form Builder</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Dynamic form builder with validation, AJAX sync, and all input types.</p>
            </div>
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-500 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="table" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Table Builder</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">DataTables with server-side processing, caching, and export features.</p>
            </div>
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="palette" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Theme System</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Customizable theme system with multiple themes and dark mode support.</p>
            </div>
            <div class="card-hover p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-500 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="languages" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">i18n Support</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Multi-language support with RTL, locale-specific fonts, and translations.</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-20 px-4 gradient-bg-subtle">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get <span class="gradient-text">Started</span>?</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Start building your admin panel with CanvaStack today.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('page.dashboard') }}" class="px-8 py-3.5 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25 flex items-center gap-2">
                <i data-lucide="rocket" class="w-4 h-4"></i> View Dashboard
            </a>
            <a href="https://github.com" target="_blank" class="px-8 py-3.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center gap-2">
                <i data-lucide="github" class="w-4 h-4"></i> View on GitHub
            </a>
        </div>
    </div>
</section>
@endsection

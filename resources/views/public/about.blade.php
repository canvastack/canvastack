@extends('canvastack::layouts.public')

@section('title', 'About')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
{{-- Hero Section --}}
<section class="pt-32 pb-20 px-4 gradient-bg-subtle">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-5xl md:text-6xl font-black mb-6 leading-tight">
            About <span class="gradient-text">CanvaStack</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400">
            A modern Laravel CMS package built for developers who want to build powerful admin panels quickly.
        </p>
    </div>
</section>

{{-- Content --}}
<section class="py-20 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="prose prose-lg dark:prose-invert max-w-none">
            <h2 class="text-3xl font-bold mb-4">What is CanvaStack?</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                CanvaStack is a comprehensive Laravel CMS package that provides all the tools you need to build modern admin panels and content management systems. It includes powerful components like FormBuilder, TableBuilder, ChartBuilder, and more.
            </p>
            
            <h2 class="text-3xl font-bold mb-4 mt-12">Key Features</h2>
            <div class="grid md:grid-cols-2 gap-6 mb-12">
                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                        <i data-lucide="zap" class="w-5 h-5 text-indigo-600"></i>
                        High Performance
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Built with performance in mind. Includes caching, query optimization, and lazy loading.
                    </p>
                </div>
                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-600"></i>
                        Secure by Default
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        CSRF protection, XSS prevention, SQL injection prevention, and more.
                    </p>
                </div>
                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                        <i data-lucide="code" class="w-5 h-5 text-indigo-600"></i>
                        Developer Friendly
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Clean API, comprehensive documentation, and extensive examples.
                    </p>
                </div>
                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-5 h-5 text-indigo-600"></i>
                        Fully Responsive
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Works perfectly on desktop, tablet, and mobile devices.
                    </p>
                </div>
            </div>
            
            <h2 class="text-3xl font-bold mb-4 mt-12">Technology Stack</h2>
            <ul class="space-y-2 mb-12">
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>Laravel 12.x</span>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>PHP 8.2+</span>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>Tailwind CSS 3.x + DaisyUI 4.x</span>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>Alpine.js 3.x</span>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>GSAP 3.x (animations)</span>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    <span>Lucide Icons</span>
                </li>
            </ul>
            
            <div class="p-8 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 rounded-2xl text-center">
                <h3 class="text-2xl font-bold mb-4">Ready to Start Building?</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Check out the dashboard to see all the components in action.
                </p>
                <a href="{{ route('test.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition">
                    <i data-lucide="rocket" class="w-4 h-4"></i> View Dashboard
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

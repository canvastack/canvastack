<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      class="{{ request()->cookie('darkMode') === 'true' ? 'dark' : '' }}">
<head>
    @include('canvastack::layouts.partials.meta')
    
    {{-- Additional Public Styles --}}
    <style>
        .gradient-bg-subtle {
            background: linear-gradient(135deg, #eef2ff, #f5f3ff, #faf5ff);
        }
        .dark .gradient-bg-subtle {
            background: linear-gradient(135deg, #1e1b4b, #2e1065, #3b0764);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans transition-colors duration-300">
    
    @include('canvastack::layouts.partials.public-navbar')
    
    <!-- Main Content -->
    <main class="min-h-screen">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20">
                <div class="alert alert-success mb-6">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20">
                <div class="alert alert-error mb-6">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif
        
        <!-- Page Content -->
        @yield('content')
    </main>
    
    @include('canvastack::layouts.partials.public-footer')
    
    @include('canvastack::layouts.partials.scripts')
</body>
</html>

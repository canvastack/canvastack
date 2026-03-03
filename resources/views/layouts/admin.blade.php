<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      class="{{ request()->cookie('darkMode') === 'true' ? 'dark' : '' }}">
<head>
    @include('canvastack::layouts.partials.meta')
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans transition-colors duration-300">
    
    @include('canvastack::layouts.partials.sidebar')
    
    <!-- Main Content -->
    <div id="main-content" class="ml-64 sidebar-transition min-h-screen flex flex-col">
        @include('canvastack::layouts.partials.header')
        
        <!-- Page Content -->
        <main class="flex-1 p-6">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success mb-6">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error mb-6">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            
            <!-- Main Content -->
            @yield('content')
        </main>
        
        @include('canvastack::layouts.partials.footer')
    </div>
    
    @include('canvastack::layouts.partials.scripts')
</body>
</html>

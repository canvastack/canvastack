@extends('canvastack::layouts.admin')

@section('title', 'Theme Management')

@push('head')
    {{-- Meta Tags --}}
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Theme Management
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Manage and customize your application themes
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <button
                onclick="clearThemeCache()"
                class="btn btn-outline btn-sm gap-2"
            >
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Clear Cache
            </button>
            
            <button
                onclick="reloadThemes()"
                class="btn btn-outline btn-sm gap-2"
            >
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Reload Themes
            </button>
        </div>
    </div>

    {{-- Alerts --}}
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

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card bg-white dark:bg-gray-900 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Themes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $stats['total_themes'] }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center">
                        <i data-lucide="palette" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white dark:bg-gray-900 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active Theme</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $stats['active_theme'] }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-950 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white dark:bg-gray-900 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Cache Status</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $stats['cache_enabled'] ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950 flex items-center justify-center">
                        <i data-lucide="database" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white dark:bg-gray-900 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Hot Reload</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ $stats['hot_reload'] ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950 flex items-center justify-center">
                        <i data-lucide="zap" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Theme Selector --}}
    <div class="card bg-white dark:bg-gray-900 shadow-lg mb-8">
        <div class="card-body">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Available Themes
            </h2>
            
            <x-canvastack::ui.theme-selector 
                variant="grid" 
                :columns="3"
                :show-metadata="true"
            />
        </div>
    </div>

    {{-- Theme List Table --}}
    <div class="card bg-white dark:bg-gray-900 shadow-lg">
        <div class="card-body">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Theme Details
            </h2>
            
            {{-- Render TableBuilder --}}
            {!! $table->render() !!}
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    /**
     * Clear theme cache
     */
    function clearThemeCache() {
        if (confirm('Are you sure you want to clear the theme cache?')) {
            fetch('{{ route("admin.themes.clear-cache") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to clear cache: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while clearing cache');
            });
        }
    }
    
    /**
     * Reload themes from filesystem
     */
    function reloadThemes() {
        if (confirm('Are you sure you want to reload all themes?')) {
            fetch('{{ route("admin.themes.reload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to reload themes: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while reloading themes');
            });
        }
    }
</script>
@endpush
@endsection


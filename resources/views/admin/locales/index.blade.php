@extends('canvastack::components.layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ __('ui.labels.language') }} {{ __('ui.navigation.settings') }}
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Manage application locales and language preferences
            </p>
        </div>
    </div>

    <!-- Current Locale Info -->
    <div class="card bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 border-indigo-200 dark:border-indigo-800">
        <div class="card-body">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-3xl">
                    {{ $localeManager->getLocaleFlag() }}
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('ui.labels.language') }}: {{ $localeManager->getLocaleName() }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $localeManager->getLocaleNativeName() }} ({{ strtoupper($currentLocale) }})
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="badge badge-sm badge-info">
                            {{ __('ui.labels.direction') }}: {{ strtoupper($localeManager->getDirection()) }}
                        </span>
                        <span class="badge badge-sm badge-success">
                            <i data-lucide="check" class="w-3 h-3 inline mr-1"></i>
                            {{ __('ui.status.active') }}
                        </span>
                    </div>
                </div>
                <div>
                    <x-canvastack::ui.locale-selector :showName="true" :compact="false" />
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Storage Method -->
        <div class="card">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <i data-lucide="database" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Storage</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ ucfirst(config('canvastack.localization.storage', 'session')) }}
                </p>
            </div>
        </div>

        <!-- Browser Detection -->
        <div class="card">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <i data-lucide="globe" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Browser Detection</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ config('canvastack.localization.detect_browser', true) ? 'Enabled' : 'Disabled' }}
                </p>
            </div>
        </div>

        <!-- Available Locales -->
        <div class="card">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <i data-lucide="languages" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Available</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($availableLocales) }} {{ __('ui.labels.language') }}(s)
                </p>
            </div>
        </div>
    </div>

    <!-- Locales Table -->
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Available Languages
                </h2>
            </div>
            
            {!! $table->render() !!}
        </div>
    </div>

    <!-- Configuration Guide -->
    <div class="card border-l-4 border-l-indigo-500">
        <div class="card-body">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                        Configuration Guide
                    </h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p>
                            <strong>Add New Locale:</strong> Edit <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">config/canvastack.php</code> 
                            and add the locale to <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">localization.available_locales</code> array.
                        </p>
                        <p>
                            <strong>Translation Files:</strong> Create translation files in <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">resources/lang/{locale}/</code> directory.
                        </p>
                        <p>
                            <strong>Storage Method:</strong> Configure in <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">.env</code> using 
                            <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs">CANVASTACK_LOCALE_STORAGE</code> (session, cookie, or both).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Reinitialize Lucide icons after page load
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
@endpush

@extends('canvastack::layouts.admin')

@section('title', __('canvastack::ui.configuration.title'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="configurationManager()">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            {{ __('canvastack::ui.configuration.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ __('canvastack::ui.configuration.description') }}
        </p>
    </div>

    {{-- Actions Bar --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <button @click="createBackup()" class="btn btn-primary">
            <i data-lucide="save" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.create_backup') }}
        </button>

        <button @click="exportConfig()" class="btn btn-secondary">
            <i data-lucide="download" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.export') }}
        </button>

        <button @click="showImportModal = true" class="btn btn-outline">
            <i data-lucide="upload" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.import') }}
        </button>

        <button @click="clearCache()" class="btn btn-ghost">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.clear_cache') }}
        </button>
    </div>

    {{-- Tabs --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        {{-- Tab Headers --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="flex overflow-x-auto">
                <button
                    @click="activeTab = 'settings'"
                    :class="activeTab === 'settings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition"
                >
                    <i data-lucide="settings" class="w-4 h-4 inline-block mr-2"></i>
                    {{ __('canvastack::ui.configuration.tabs.settings') }}
                </button>

                <button
                    @click="activeTab = 'backups'"
                    :class="activeTab === 'backups' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition"
                >
                    <i data-lucide="archive" class="w-4 h-4 inline-block mr-2"></i>
                    {{ __('canvastack::ui.configuration.tabs.backups') }}
                </button>

                <button
                    @click="activeTab = 'migration'"
                    :class="activeTab === 'migration' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition"
                >
                    <i data-lucide="git-branch" class="w-4 h-4 inline-block mr-2"></i>
                    {{ __('canvastack::ui.configuration.tabs.migration') }}
                </button>

                <button
                    @click="activeTab = 'cache'"
                    :class="activeTab === 'cache' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition"
                >
                    <i data-lucide="database" class="w-4 h-4 inline-block mr-2"></i>
                    {{ __('canvastack::ui.configuration.tabs.cache') }}
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Settings Tab --}}
            <div x-show="activeTab === 'settings'" x-cloak>
                @include('canvastack::admin.configuration.partials.settings')
            </div>

            {{-- Backups Tab --}}
            <div x-show="activeTab === 'backups'" x-cloak>
                @include('canvastack::admin.configuration.partials.backups')
            </div>

            {{-- Migration Tab --}}
            <div x-show="activeTab === 'migration'" x-cloak>
                @include('canvastack::admin.configuration.partials.migration')
            </div>

            {{-- Cache Tab --}}
            <div x-show="activeTab === 'cache'" x-cloak>
                @include('canvastack::admin.configuration.partials.cache')
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div
        x-show="showImportModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showImportModal = false"
    >
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">{{ __('canvastack::ui.configuration.import_config') }}</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __('canvastack::ui.configuration.select_file') }}</label>
                <input
                    type="file"
                    @change="handleFileUpload($event)"
                    accept=".json"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl"
                >
            </div>

            <div class="flex gap-3 justify-end">
                <button @click="showImportModal = false" class="btn btn-ghost">
                    {{ __('canvastack::ui.buttons.cancel') }}
                </button>
                <button @click="importConfig()" class="btn btn-primary">
                    {{ __('canvastack::ui.buttons.import') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function configurationManager() {
    return {
        activeTab: 'settings',
        showImportModal: false,
        importFile: null,
        settings: @json($settings),
        backups: @json($backups),
        cacheStats: @json($cacheStats),

        async createBackup() {
            const name = prompt('{{ __('canvastack::ui.configuration.backup_name') }}');
            if (!name) return;

            try {
                const response = await fetch('{{ route('admin.configuration.backup.create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name })
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.backup_created') }}');
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.backup_failed') }}');
                }
            } catch (error) {
                console.error('Error creating backup:', error);
                alert('{{ __('canvastack::ui.configuration.backup_failed') }}');
            }
        },

        async exportConfig() {
            window.location.href = '{{ route('admin.configuration.download') }}';
        },

        handleFileUpload(event) {
            this.importFile = event.target.files[0];
        },

        async importConfig() {
            if (!this.importFile) {
                alert('{{ __('canvastack::ui.configuration.select_file_first') }}');
                return;
            }

            const formData = new FormData();
            formData.append('file', this.importFile);

            try {
                const response = await fetch('{{ route('admin.configuration.backup.upload') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.import_success') }}');
                    this.showImportModal = false;
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.import_failed') }}');
                }
            } catch (error) {
                console.error('Error importing config:', error);
                alert('{{ __('canvastack::ui.configuration.import_failed') }}');
            }
        },

        async clearCache() {
            if (!confirm('{{ __('canvastack::ui.configuration.clear_cache_confirm') }}')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.configuration.cache.clear') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.cache_cleared') }}');
                } else {
                    alert('{{ __('canvastack::ui.configuration.cache_clear_failed') }}');
                }
            } catch (error) {
                console.error('Error clearing cache:', error);
                alert('{{ __('canvastack::ui.configuration.cache_clear_failed') }}');
            }
        }
    }
}
</script>
@endpush
@endsection

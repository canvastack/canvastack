{{-- Configuration Cache Tab --}}
<div class="space-y-6">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('canvastack::ui.configuration.cache_description') }}
    </p>

    {{-- Cache Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="database" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('canvastack::ui.configuration.cache_driver') }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $cacheStats['driver'] ?? 'N/A' }}</p>
        </div>

        <div class="p-4 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('canvastack::ui.configuration.cache_status') }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $cacheStats['enabled'] ? __('canvastack::ui.configuration.enabled') : __('canvastack::ui.configuration.disabled') }}
            </p>
        </div>

        <div class="p-4 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="activity" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('canvastack::ui.configuration.hit_rate') }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($cacheStats['hit_rate'] ?? 0, 1) }}%</p>
        </div>
    </div>

    {{-- Cache Actions --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
            {{ __('canvastack::ui.configuration.cache_actions') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Clear All Cache --}}
            <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-start gap-3 mb-3">
                    <i data-lucide="trash-2" class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ __('canvastack::ui.configuration.clear_all_cache') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('canvastack::ui.configuration.clear_all_cache_desc') }}
                        </p>
                    </div>
                </div>
                <button @click="clearAllCache()" class="btn btn-sm btn-outline text-red-600 hover:text-red-700">
                    {{ __('canvastack::ui.buttons.clear') }}
                </button>
            </div>

            {{-- Clear Config Cache --}}
            <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-start gap-3 mb-3">
                    <i data-lucide="settings" class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ __('canvastack::ui.configuration.clear_config_cache') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('canvastack::ui.configuration.clear_config_cache_desc') }}
                        </p>
                    </div>
                </div>
                <button @click="clearCache()" class="btn btn-sm btn-primary">
                    {{ __('canvastack::ui.buttons.clear') }}
                </button>
            </div>

            {{-- Clear Theme Cache --}}
            <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-start gap-3 mb-3">
                    <i data-lucide="palette" class="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ __('canvastack::ui.configuration.clear_theme_cache') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('canvastack::ui.configuration.clear_theme_cache_desc') }}
                        </p>
                    </div>
                </div>
                <button @click="clearThemeCache()" class="btn btn-sm btn-secondary">
                    {{ __('canvastack::ui.buttons.clear') }}
                </button>
            </div>

            {{-- Clear Translation Cache --}}
            <div class="p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-start gap-3 mb-3">
                    <i data-lucide="globe" class="w-5 h-5 text-teal-600 dark:text-teal-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ __('canvastack::ui.configuration.clear_translation_cache') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('canvastack::ui.configuration.clear_translation_cache_desc') }}
                        </p>
                    </div>
                </div>
                <button @click="clearTranslationCache()" class="btn btn-sm btn-secondary">
                    {{ __('canvastack::ui.buttons.clear') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('configurationManager', (base) => ({
        ...base,

        async clearAllCache() {
            if (!confirm('{{ __('canvastack::ui.configuration.clear_all_confirm') }}')) {
                return;
            }

            try {
                // Clear Laravel cache
                await fetch('/admin/cache/clear', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                alert('{{ __('canvastack::ui.configuration.cache_cleared') }}');
                location.reload();
            } catch (error) {
                console.error('Error clearing cache:', error);
                alert('{{ __('canvastack::ui.configuration.cache_clear_failed') }}');
            }
        },

        async clearThemeCache() {
            try {
                await fetch('/admin/theme/clear-cache', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                alert('{{ __('canvastack::ui.configuration.theme_cache_cleared') }}');
            } catch (error) {
                console.error('Error clearing theme cache:', error);
                alert('{{ __('canvastack::ui.configuration.cache_clear_failed') }}');
            }
        },

        async clearTranslationCache() {
            try {
                await fetch('/admin/locale/clear-cache', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                alert('{{ __('canvastack::ui.configuration.translation_cache_cleared') }}');
            } catch (error) {
                console.error('Error clearing translation cache:', error);
                alert('{{ __('canvastack::ui.configuration.cache_clear_failed') }}');
            }
        }
    }));
});
</script>
@endpush

{{-- Configuration Backups Tab --}}
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('canvastack::ui.configuration.backups_description') }}
        </p>

        <button @click="cleanOldBackups()" class="btn btn-sm btn-outline">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.clean_old') }}
        </button>
    </div>

    {{-- Backups List --}}
    @if(count($backups) > 0)
    <div class="space-y-3">
        @foreach($backups as $backup)
        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                    <i data-lucide="archive" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $backup['name'] }}</h4>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span>{{ $backup['created_at'] ? \Carbon\Carbon::parse($backup['created_at'])->format('Y-m-d H:i:s') : 'Unknown' }}</span>
                        <span>•</span>
                        <span>{{ $backup['size_human'] }}</span>
                        <span>•</span>
                        <span>v{{ $backup['version'] }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="restoreBackup('{{ $backup['filename'] }}')"
                    class="btn btn-sm btn-primary"
                    title="{{ __('canvastack::ui.configuration.restore') }}"
                >
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>

                <a
                    href="{{ route('admin.configuration.backup.download', $backup['filename']) }}"
                    class="btn btn-sm btn-secondary"
                    title="{{ __('canvastack::ui.configuration.download') }}"
                >
                    <i data-lucide="download" class="w-4 h-4"></i>
                </a>

                <button
                    @click="deleteBackup('{{ $backup['filename'] }}')"
                    class="btn btn-sm btn-ghost text-red-600 hover:text-red-700"
                    title="{{ __('canvastack::ui.configuration.delete') }}"
                >
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <i data-lucide="archive" class="w-16 h-16 text-gray-300 dark:text-gray-700 mx-auto mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">{{ __('canvastack::ui.configuration.no_backups') }}</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('configurationManager', (base) => ({
        ...base,

        async restoreBackup(filename) {
            if (!confirm('{{ __('canvastack::ui.configuration.restore_confirm') }}')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.configuration.backup.restore') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ filename })
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.restore_success') }}');
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.restore_failed') }}: ' + (result.error || ''));
                }
            } catch (error) {
                console.error('Error restoring backup:', error);
                alert('{{ __('canvastack::ui.configuration.restore_failed') }}');
            }
        },

        async deleteBackup(filename) {
            if (!confirm('{{ __('canvastack::ui.configuration.delete_confirm') }}')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.configuration.backup.delete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ filename })
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.delete_success') }}');
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.delete_failed') }}');
                }
            } catch (error) {
                console.error('Error deleting backup:', error);
                alert('{{ __('canvastack::ui.configuration.delete_failed') }}');
            }
        },

        async cleanOldBackups() {
            const keep = prompt('{{ __('canvastack::ui.configuration.keep_how_many') }}', '10');
            if (!keep) return;

            try {
                const response = await fetch('{{ route('admin.configuration.backup.clean') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ keep: parseInt(keep) })
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.clean_failed') }}');
                }
            } catch (error) {
                console.error('Error cleaning backups:', error);
                alert('{{ __('canvastack::ui.configuration.clean_failed') }}');
            }
        }
    }));
});
</script>
@endpush

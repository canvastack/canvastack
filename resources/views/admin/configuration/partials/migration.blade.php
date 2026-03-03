{{-- Configuration Migration Tab --}}
<div class="space-y-6">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i data-lucide="info" class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5"></i>
            <div>
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-1">
                    {{ __('canvastack::ui.configuration.migration_info_title') }}
                </h4>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    {{ __('canvastack::ui.configuration.migration_info_desc') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Migration Steps --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
            {{ __('canvastack::ui.configuration.migration_steps') }}
        </h3>

        <div class="space-y-3">
            {{-- Step 1: Backup --}}
            <div class="flex items-start gap-4 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">1</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                        {{ __('canvastack::ui.configuration.step1_title') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ __('canvastack::ui.configuration.step1_desc') }}
                    </p>
                    <button @click="backupOldConfig()" class="btn btn-sm btn-primary">
                        {{ __('canvastack::ui.configuration.backup_old_config') }}
                    </button>
                </div>
            </div>

            {{-- Step 2: Run Migration --}}
            <div class="flex items-start gap-4 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">2</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                        {{ __('canvastack::ui.configuration.step2_title') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ __('canvastack::ui.configuration.step2_desc') }}
                    </p>
                    <button @click="runMigration()" class="btn btn-sm btn-primary">
                        {{ __('canvastack::ui.configuration.run_migration') }}
                    </button>
                </div>
            </div>

            {{-- Step 3: Validate --}}
            <div class="flex items-start gap-4 p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">3</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                        {{ __('canvastack::ui.configuration.step3_title') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ __('canvastack::ui.configuration.step3_desc') }}
                    </p>
                    <button @click="validateMigration()" class="btn btn-sm btn-secondary">
                        {{ __('canvastack::ui.configuration.validate_migration') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Migration Report --}}
    <div>
        <button @click="getMigrationReport()" class="btn btn-outline">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            {{ __('canvastack::ui.configuration.view_report') }}
        </button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('configurationManager', (base) => ({
        ...base,

        async backupOldConfig() {
            // This would be handled by the migration service
            alert('{{ __('canvastack::ui.configuration.feature_coming_soon') }}');
        },

        async runMigration() {
            if (!confirm('{{ __('canvastack::ui.configuration.migration_confirm') }}')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.configuration.migration.run') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.migration_success') }}');
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.migration_failed') }}');
                    console.error('Migration errors:', result.errors);
                }
            } catch (error) {
                console.error('Error running migration:', error);
                alert('{{ __('canvastack::ui.configuration.migration_failed') }}');
            }
        },

        async validateMigration() {
            try {
                const response = await fetch('{{ route('admin.configuration.migration.validate') }}');
                const result = await response.json();

                if (result.success && result.validation.valid) {
                    alert('{{ __('canvastack::ui.configuration.validation_success') }}');
                } else {
                    const issues = result.validation.issues.join('\n');
                    alert('{{ __('canvastack::ui.configuration.validation_failed') }}:\n' + issues);
                }
            } catch (error) {
                console.error('Error validating migration:', error);
                alert('{{ __('canvastack::ui.configuration.validation_error') }}');
            }
        },

        async getMigrationReport() {
            try {
                const response = await fetch('{{ route('admin.configuration.migration.report') }}');
                const result = await response.json();

                console.log('Migration Report:', result.report);
                alert('{{ __('canvastack::ui.configuration.report_in_console') }}');
            } catch (error) {
                console.error('Error getting migration report:', error);
                alert('{{ __('canvastack::ui.configuration.report_error') }}');
            }
        }
    }));
});
</script>
@endpush

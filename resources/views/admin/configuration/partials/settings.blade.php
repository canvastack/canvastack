{{-- Configuration Settings Tab --}}
<div class="space-y-6">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('canvastack::ui.configuration.settings_description') }}
    </p>

    {{-- Configuration Groups --}}
    <div class="space-y-4">
        @foreach($settings['groups'] as $group)
        <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            {{-- Group Header --}}
            <button
                @click="expandedGroup === '{{ $group['id'] }}' ? expandedGroup = null : expandedGroup = '{{ $group['id'] }}'"
                class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
            >
                <div class="flex items-center gap-3">
                    <i data-lucide="{{ $group['icon'] }}" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $group['label'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ count($group['settings']) }} settings)</span>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 transition-transform" :class="expandedGroup === '{{ $group['id'] }}' ? 'rotate-180' : ''"></i>
            </button>

            {{-- Group Content --}}
            <div x-show="expandedGroup === '{{ $group['id'] }}'" x-collapse>
                <div class="p-4 space-y-4">
                    @foreach($group['settings'] as $setting)
                    <div class="flex items-start justify-between py-3 border-b border-gray-200 dark:border-gray-800 last:border-0">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                {{ $setting['label'] }}
                            </label>
                            @if($setting['description'])
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $setting['description'] }}</p>
                            @endif
                        </div>

                        <div class="ml-4">
                            @if($setting['type'] === 'boolean')
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        {{ $setting['value'] ? 'checked' : '' }}
                                        {{ !$setting['editable'] ? 'disabled' : '' }}
                                        class="sr-only peer"
                                        @change="updateSetting('{{ $group['id'] }}', '{{ $setting['key'] }}', $event.target.checked)"
                                    >
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            @elseif($setting['type'] === 'integer')
                                <input
                                    type="number"
                                    value="{{ $setting['value'] }}"
                                    {{ !$setting['editable'] ? 'disabled' : '' }}
                                    class="w-24 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800"
                                    @change="updateSetting('{{ $group['id'] }}', '{{ $setting['key'] }}', parseInt($event.target.value))"
                                >
                            @elseif($setting['type'] === 'string')
                                <input
                                    type="text"
                                    value="{{ $setting['value'] }}"
                                    {{ !$setting['editable'] ? 'disabled' : '' }}
                                    class="w-64 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800"
                                    @change="updateSetting('{{ $group['id'] }}', '{{ $setting['key'] }}', $event.target.value)"
                                >
                            @else
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ json_encode($setting['value']) }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    {{-- Group Actions --}}
                    <div class="flex gap-3 pt-4">
                        <button
                            @click="resetGroup('{{ $group['id'] }}')"
                            class="btn btn-sm btn-outline"
                        >
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            {{ __('canvastack::ui.buttons.reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('configurationManager', (base) => ({
        ...base,
        expandedGroup: null,

        async updateSetting(group, key, value) {
            try {
                const response = await fetch('{{ route('admin.configuration.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        group,
                        settings: { [key]: value }
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    alert('{{ __('canvastack::ui.configuration.update_failed') }}');
                    console.error('Update failed:', result.errors);
                }
            } catch (error) {
                console.error('Error updating setting:', error);
                alert('{{ __('canvastack::ui.configuration.update_failed') }}');
            }
        },

        async resetGroup(group) {
            if (!confirm('{{ __('canvastack::ui.configuration.reset_confirm') }}')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.configuration.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ group })
                });

                const result = await response.json();

                if (result.success) {
                    alert('{{ __('canvastack::ui.configuration.reset_success') }}');
                    location.reload();
                } else {
                    alert('{{ __('canvastack::ui.configuration.reset_failed') }}');
                }
            } catch (error) {
                console.error('Error resetting group:', error);
                alert('{{ __('canvastack::ui.configuration.reset_failed') }}');
            }
        }
    }));
});
</script>
@endpush

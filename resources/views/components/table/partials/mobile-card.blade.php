{{--
    Mobile Card View Template
    
    This template provides an enhanced mobile card view for table data with expand/collapse functionality.
    
    Features:
    - Card-based layout optimized for mobile devices
    - Display key fields prominently
    - Expand/collapse for additional fields
    - Touch-friendly interactions
    - Dark mode support
    - Theme Engine integration
    - i18n support
    
    @var array $row - Row data
    @var array $columns - Column definitions
    @var array $keyFields - Fields to display prominently (default: first 3 fields)
    @var array $additionalFields - Fields to show in expand section (default: remaining fields)
    @var array $actions - Row actions
    @var string $rowId - Unique row identifier
    @var array $translations - i18n translations
--}}

<div 
    x-data="{ expanded: false }"
    class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 
           hover:border-gray-300 dark:hover:border-gray-700 transition-all duration-200"
>
    {{-- Card Header: Key Fields --}}
    <div class="space-y-3">
        @foreach($keyFields as $field)
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1" style="font-family: @themeFont('sans')">
                {{ $field['label'] }}
            </div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100" style="font-family: @themeFont('sans')">
                {!! $field['value'] !!}
            </div>
        </div>
        @endforeach
    </div>

    {{-- Expand/Collapse Button (only if there are additional fields) --}}
    @if(!empty($additionalFields))
    <button
        @click="expanded = !expanded"
        class="w-full mt-4 py-2 px-4 rounded-xl border border-gray-200 dark:border-gray-700
               bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
               hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200
               flex items-center justify-center gap-2"
        style="font-family: @themeFont('sans')"
        :aria-expanded="expanded"
        :aria-label="expanded ? '{{ __('components.table.collapse') }}' : '{{ __('components.table.expand') }}'"
    >
        <span x-text="expanded ? '{{ __('components.table.show_less') }}' : '{{ __('components.table.show_more') }}'"></span>
        <i 
            data-lucide="chevron-down" 
            class="w-4 h-4 transition-transform duration-200"
            :class="{ 'rotate-180': expanded }"
        ></i>
    </button>

    {{-- Additional Fields (Expandable Section) --}}
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3"
    >
        @foreach($additionalFields as $field)
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1" style="font-family: @themeFont('sans')">
                {{ $field['label'] }}
            </div>
            <div class="text-sm text-gray-900 dark:text-gray-100" style="font-family: @themeFont('sans')">
                {!! $field['value'] !!}
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Actions --}}
    @if(!empty($actions))
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
        @foreach($actions as $action)
        <button
            @if($action['method'] === 'DELETE')
            @click="if(confirm('{{ __('components.table.delete_confirm') }}')) { 
                fetch('{{ $action['url'] }}', { 
                    method: 'DELETE', 
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    } 
                }).then(() => window.location.reload()) 
            }"
            @else
            @click="window.location.href = '{{ $action['url'] }}'"
            @endif
            class="flex-1 min-w-[100px] px-4 py-2 rounded-xl border transition-all duration-200
                   {{ $action['class'] ?? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            style="font-family: @themeFont('sans')"
            :aria-label="{{ $action['label'] }}"
        >
            <div class="flex items-center justify-center gap-2">
                @if(!empty($action['icon']))
                <i data-lucide="{{ $action['icon'] }}" class="w-4 h-4"></i>
                @endif
                <span>{{ $action['label'] }}</span>
            </div>
        </button>
        @endforeach
    </div>
    @endif
</div>

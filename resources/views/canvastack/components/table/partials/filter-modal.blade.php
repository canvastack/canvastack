{{--
    Filter Modal Component
    
    This modal provides advanced filtering capabilities for tables.
    Supports bi-directional cascading filters, date ranges, and multiple filter types.
    
    @props array $filterGroups - Array of filter group configurations
    @props array $activeFilters - Currently active filters
    @props string $tableId - Unique identifier for the table
--}}

@props([
    'filterGroups' => [],
    'activeFilters' => [],
    'tableId' => 'table',
])

<div x-data="filterModal()" 
     x-init="init()"
     class="relative">
    
    {{-- Filter Button with Active Count Badge --}}
    <button @click="open = true"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border transition-all duration-200
                   bg-white dark:bg-gray-900 
                   border-gray-200 dark:border-gray-800
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-800
                   hover:border-gray-300 dark:hover:border-gray-700
                   focus:outline-none focus:ring-2 focus:ring-offset-2 
                   focus:ring-primary dark:focus:ring-offset-gray-900"
            :aria-label="__('components.table.filters')">
        <i data-lucide="filter" class="w-4 h-4"></i>
        <span>{{ __('components.table.filters') }}</span>
        
        {{-- Active Filter Count Badge --}}
        <span x-show="activeFilterCount > 0"
              x-text="activeFilterCount"
              class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 
                     text-xs font-semibold rounded-full
                     bg-primary text-white"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-75"
              x-transition:enter-end="opacity-100 scale-100">
        </span>
    </button>

    {{-- Modal Overlay --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
         style="display: none;">
    </div>

    {{-- Modal Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         @click.away="open = false"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl rounded-2xl shadow-2xl
                        bg-white dark:bg-gray-900
                        border border-gray-200 dark:border-gray-800">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('components.table.filter_by') }}
                    </h3>
                    
                    <button @click="open = false"
                            type="button"
                            class="p-2 rounded-lg transition-colors
                                   text-gray-400 hover:text-gray-600 dark:hover:text-gray-300
                                   hover:bg-gray-100 dark:hover:bg-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-primary"
                            :aria-label="__('ui.buttons.close')">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        
                        {{-- Filter Groups --}}
                        @foreach($filterGroups as $groupKey => $group)
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $group['label'] ?? ucfirst($groupKey) }}
                                </label>
                                
                                @switch($group['type'] ?? 'select')
                                    @case('select')
                                        <select x-model="filters.{{ $groupKey }}"
                                                @change="handleFilterChange('{{ $groupKey }}')"
                                                class="w-full px-4 py-2 rounded-xl border transition-colors
                                                       bg-white dark:bg-gray-800
                                                       border-gray-200 dark:border-gray-700
                                                       text-gray-900 dark:text-gray-100
                                                       focus:outline-none focus:ring-2 focus:ring-primary
                                                       focus:border-transparent">
                                            <option value="">{{ __('components.table.all') }}</option>
                                            <template x-for="option in filterOptions.{{ $groupKey }}" :key="option.value">
                                                <option :value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                        @break
                                    
                                    @case('text')
                                        <input type="text"
                                               x-model="filters.{{ $groupKey }}"
                                               :placeholder="__('components.table.search_in', { field: '{{ $group['label'] ?? $groupKey }}' })"
                                               class="w-full px-4 py-2 rounded-xl border transition-colors
                                                      bg-white dark:bg-gray-800
                                                      border-gray-200 dark:border-gray-700
                                                      text-gray-900 dark:text-gray-100
                                                      placeholder-gray-400 dark:placeholder-gray-500
                                                      focus:outline-none focus:ring-2 focus:ring-primary
                                                      focus:border-transparent">
                                        @break
                                    
                                    @case('daterange')
                                        {{-- Flatpickr Date Range Picker --}}
                                        <div x-data="flatpickrDateRange()"
                                             x-init="init()"
                                             @daterange-changed="filters.{{ $groupKey }}_start = $event.detail.start; filters.{{ $groupKey }}_end = $event.detail.end">
                                            <input type="text"
                                                   x-ref="dateRangeInput"
                                                   :placeholder="__('components.table.select_date_range')"
                                                   class="w-full px-4 py-2 rounded-xl border transition-colors
                                                          bg-white dark:bg-gray-800
                                                          border-gray-200 dark:border-gray-700
                                                          text-gray-900 dark:text-gray-100
                                                          placeholder-gray-400 dark:placeholder-gray-500
                                                          focus:outline-none focus:ring-2 focus:ring-primary
                                                          focus:border-transparent cursor-pointer">
                                            
                                            {{-- Hidden inputs for form submission --}}
                                            <input type="hidden" x-model="filters.{{ $groupKey }}_start">
                                            <input type="hidden" x-model="filters.{{ $groupKey }}_end">
                                        </div>
                                        @break
                                    
                                    @case('number')
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="number"
                                                   x-model="filters.{{ $groupKey }}_min"
                                                   :placeholder="__('components.table.min')"
                                                   class="w-full px-4 py-2 rounded-xl border transition-colors
                                                          bg-white dark:bg-gray-800
                                                          border-gray-200 dark:border-gray-700
                                                          text-gray-900 dark:text-gray-100
                                                          placeholder-gray-400 dark:placeholder-gray-500
                                                          focus:outline-none focus:ring-2 focus:ring-primary
                                                          focus:border-transparent">
                                            <input type="number"
                                                   x-model="filters.{{ $groupKey }}_max"
                                                   :placeholder="__('components.table.max')"
                                                   class="w-full px-4 py-2 rounded-xl border transition-colors
                                                          bg-white dark:bg-gray-800
                                                          border-gray-200 dark:border-gray-700
                                                          text-gray-900 dark:text-gray-100
                                                          placeholder-gray-400 dark:placeholder-gray-500
                                                          focus:outline-none focus:ring-2 focus:ring-primary
                                                          focus:border-transparent">
                                        </div>
                                        @break
                                @endswitch
                            </div>
                        @endforeach

                        {{-- Active Filters Display --}}
                        <div x-show="activeFilterCount > 0" 
                             class="pt-4 border-t border-gray-200 dark:border-gray-800">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('components.table.active_filters') }}
                                </span>
                                <button @click="clearAllFilters"
                                        type="button"
                                        class="text-sm text-primary hover:text-primary-dark transition-colors">
                                    {{ __('components.table.clear_all') }}
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(value, key) in activeFiltersDisplay" :key="key">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm
                                                 bg-primary/10 text-primary border border-primary/20">
                                        <span x-text="value"></span>
                                        <button @click="clearFilter(key)"
                                                type="button"
                                                class="hover:bg-primary/20 rounded p-0.5 transition-colors">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                    <button @click="clearAllFilters"
                            type="button"
                            class="px-4 py-2 rounded-xl border transition-all duration-200
                                   bg-white dark:bg-gray-900
                                   border-gray-200 dark:border-gray-800
                                   text-gray-700 dark:text-gray-300
                                   hover:bg-gray-50 dark:hover:bg-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        {{ __('components.table.clear_filters') }}
                    </button>
                    
                    <button @click="applyFilters"
                            type="button"
                            class="px-4 py-2 rounded-xl transition-all duration-200
                                   bg-primary hover:bg-primary-dark
                                   text-white font-medium
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary
                                   shadow-sm hover:shadow-md">
                        {{ __('components.table.apply_filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterModal() {
    return {
        open: false,
        filters: @json($activeFilters),
        filterOptions: @json($filterGroups),
        activeFilterCount: 0,
        activeFiltersDisplay: {},
        
        init() {
            this.calculateActiveFilters();
            this.loadFilterOptions();
        },
        
        calculateActiveFilters() {
            let count = 0;
            let display = {};
            
            for (const [key, value] of Object.entries(this.filters)) {
                if (value !== null && value !== '' && value !== undefined) {
                    count++;
                    display[key] = this.getFilterDisplayValue(key, value);
                }
            }
            
            this.activeFilterCount = count;
            this.activeFiltersDisplay = display;
        },
        
        getFilterDisplayValue(key, value) {
            const group = this.filterOptions[key];
            if (!group) return value;
            
            if (group.type === 'select' && group.options) {
                const option = group.options.find(opt => opt.value === value);
                return option ? `${group.label}: ${option.label}` : value;
            }
            
            return `${group.label}: ${value}`;
        },
        
        async loadFilterOptions() {
            // Load filter options from server if needed
            // This supports bi-directional cascading filters
            for (const [key, group] of Object.entries(this.filterOptions)) {
                if (group.ajax) {
                    await this.fetchFilterOptions(key, group.ajax);
                }
            }
        },
        
        async fetchFilterOptions(key, url) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ filters: this.filters })
                });
                
                const data = await response.json();
                this.filterOptions[key].options = data.options || [];
            } catch (error) {
                console.error(`Failed to load filter options for ${key}:`, error);
            }
        },
        
        async handleFilterChange(key) {
            // Handle bi-directional cascading filters
            const group = this.filterOptions[key];
            if (group && group.cascade) {
                // Reload dependent filter options
                for (const dependentKey of group.cascade) {
                    const dependentGroup = this.filterOptions[dependentKey];
                    if (dependentGroup && dependentGroup.ajax) {
                        await this.fetchFilterOptions(dependentKey, dependentGroup.ajax);
                    }
                }
            }
            
            this.calculateActiveFilters();
        },
        
        applyFilters() {
            // Emit event to table component
            window.dispatchEvent(new CustomEvent('table-filters-applied', {
                detail: {
                    tableId: '{{ $tableId }}',
                    filters: this.filters
                }
            }));
            
            // Save to session storage
            sessionStorage.setItem('table_filters_{{ $tableId }}', JSON.stringify(this.filters));
            
            this.open = false;
        },
        
        clearFilter(key) {
            this.filters[key] = null;
            this.calculateActiveFilters();
        },
        
        clearAllFilters() {
            for (const key in this.filters) {
                this.filters[key] = null;
            }
            this.calculateActiveFilters();
            this.applyFilters();
        }
    };
}
</script>
@endpush

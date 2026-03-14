{{--
    Filter Modal Component (Version 2 - External JavaScript)
    
    A modal dialog for table filtering with Alpine.js using external JavaScript modules.
    
    @props
    - filters: array - Array of filter configurations
    - activeFilters: array - Currently active filter values
    - tableName: string - Name of the table for session storage
    - tableId: string - Unique table ID
    - activeFilterCount: int - Number of active filters (for badge)
    - showButton: bool - Control whether to show the filter button
    - config: array - Table configuration including bidirectional_cascade
    - connection: string - Database connection name
--}}

@props([
    'filters' => [],
    'activeFilters' => [],
    'tableName' => '',
    'tableId' => '',
    'activeFilterCount' => 0,
    'showButton' => false,
    'config' => [],
    'connection' => null,
])

<div x-data="filterModal({
    filters: @js($filters),
    activeFilters: @js($activeFilters),
    tableName: @js($tableName),
    tableId: @js($tableId),
    activeFilterCount: {{ $activeFilterCount }},
    config: @js($config),
    connection: @js($connection),
    filterOptionsRoute: @js(route('datatable.filter-options')),
    saveFiltersRoute: @js(route('datatable.save-filters')),
    cacheTTL: {{ config('canvastack.table.filters.frontend_cache_ttl', 300) * 1000 }},
    debounceDelay: {{ config('canvastack.table.filters.debounce_delay', 300) }}
})" x-cloak data-connection="{{ $connection }}">
    {{-- Filter Button with Badge (only show if showButton is true) --}}
    @if($showButton)
    <button 
        @click="open = true" 
        class="btn btn-primary btn-sm gap-2"
        dusk="filter-button"
        aria-label="{{ __('canvastack::ui.buttons.filter') }}"
        :aria-expanded="open"
        aria-controls="filter-modal-dialog"
    >
        <i data-lucide="filter" class="w-4 h-4" aria-hidden="true"></i>
        <span>{{ __('canvastack::ui.buttons.filter') }}</span>
        <span 
            x-show="activeFilterCount > 0" 
            class="badge badge-sm badge-error"
            x-text="activeFilterCount"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            role="status"
            :aria-label="activeFilterCount + ' {{ __('canvastack::ui.filter.active_filters') }}'"
        ></span>
    </button>
    @endif
    
    {{-- Modal --}}
    <div 
        x-show="open" 
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        style="display: none;"
        dusk="filter-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="filter-modal-title"
        id="filter-modal-dialog"
    >
        {{-- Backdrop with Blur Effect --}}
        <div 
            class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 backdrop-blur-none"
            x-transition:enter-end="opacity-100 backdrop-blur-sm"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 backdrop-blur-sm"
            x-transition:leave-end="opacity-0 backdrop-blur-none"
        ></div>
        
        {{-- Modal Content --}}
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div 
                x-ref="modalContent"
                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6"
                x-show="open"
                style="transition: all 0.3s ease-out;"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <h3 
                        id="filter-modal-title"
                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ __('canvastack::ui.filter.title') }}
                    </h3>
                    <button 
                        @click="open = false" 
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        aria-label="{{ __('canvastack::ui.buttons.close') }}"
                        type="button"
                    >
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
                
                {{-- Active Filters Summary --}}
                <div 
                    x-show="activeFilterCount > 0" 
                    class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    role="region"
                    aria-label="{{ __('canvastack::ui.filter.active_filters') }}"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                            {{ __('canvastack::ui.filter.active_filters') }}
                        </span>
                        <span class="text-xs text-blue-700 dark:text-blue-300" x-text="activeFilterCount + ' ' + '{{ __('canvastack::ui.filter.active') }}'"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(value, column) in filterValues" :key="column">
                            <div 
                                x-show="value !== '' && value !== null && value !== undefined"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded-md text-xs"
                            >
                                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="getFilterLabel(column)"></span>
                                <span class="text-gray-500 dark:text-gray-400">:</span>
                                <span class="text-gray-900 dark:text-gray-100" x-text="getFilterValueLabel(column, value)"></span>
                                <button 
                                    type="button"
                                    @click="removeFilter(column)"
                                    class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                                    :aria-label="'{{ __('canvastack::ui.filter.remove') }} ' + getFilterLabel(column)"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                
                {{-- Cascade Processing Indicator --}}
                <div 
                    x-show="cascadeState.isProcessing" 
                    class="mb-4 p-3 bg-primary/10 dark:bg-primary/20 border border-primary/30 dark:border-primary/40 rounded-lg"
                    role="status"
                    aria-live="polite"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="flex items-center gap-2 text-sm text-primary dark:text-primary-content mb-2">
                        <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                        <span class="font-medium">{{ __('canvastack::ui.filter.updating_filters') }}</span>
                    </div>
                    
                    <div 
                        x-show="cascadeState.affectedFilters.length > 0"
                        class="mt-2 flex flex-wrap gap-2"
                    >
                        <template x-for="column in cascadeState.affectedFilters" :key="column">
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-1 bg-primary/20 dark:bg-primary/30 border border-primary/40 dark:border-primary/50 rounded-md text-xs text-primary dark:text-primary-content font-medium"
                                x-text="getFilterLabel(column)"
                            ></span>
                        </template>
                    </div>
                </div>
                
                {{-- Error Notification --}}
                <div 
                    x-show="cascadeState.hasError" 
                    class="mb-4 p-3 bg-error/10 dark:bg-error/20 border border-error/30 dark:border-error/40 rounded-lg"
                    role="alert"
                    aria-live="assertive"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-error dark:text-error-content flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-error dark:text-error-content" x-text="cascadeState.error"></p>
                        </div>
                        <button 
                            type="button"
                            @click="cascadeState.hasError = false; cascadeState.error = null"
                            class="text-error/60 hover:text-error dark:text-error-content/60 dark:hover:text-error-content transition-colors flex-shrink-0"
                            aria-label="{{ __('canvastack::ui.buttons.close') }}"
                        >
                            <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                
                {{-- Filter Form --}}
                <form @submit.prevent="applyFilters" role="form">
                    <div class="space-y-4">
                        {{-- Filters will be rendered here dynamically --}}
                        <template x-for="filter in filters" :key="filter.column">
                            <div>
                                <label 
                                    :for="'filter_' + filter.column" 
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                    x-text="filter.label"
                                ></label>
                                
                                {{-- Select Box --}}
                                <template x-if="filter.type === 'selectbox'">
                                    <div class="relative">
                                        <select 
                                            :id="'filter_' + filter.column"
                                            x-model="filterValues[filter.column]"
                                            @change="debouncedHandleFilterChange(filter)"
                                            class="select select-bordered w-full"
                                            :disabled="filter.loading || isApplying || cascadeState.isProcessing"
                                            :dusk="'filter-' + filter.column"
                                        >
                                            <option value="">{{ __('canvastack::ui.filter.select_placeholder') }}</option>
                                            {{-- Options will be populated by Tom Select from filter.options --}}
                                        </select>
                                        
                                        {{-- Loading Spinner --}}
                                        <div 
                                            x-show="filter.loading || isApplying || cascadeState.isProcessing" 
                                            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                        >
                                            <span class="loading loading-spinner loading-sm text-primary"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                {{-- Input Box --}}
                                <template x-if="filter.type === 'inputbox'">
                                    <input 
                                        :id="'filter_' + filter.column"
                                        type="text"
                                        x-model="filterValues[filter.column]"
                                        class="input input-bordered w-full"
                                        :placeholder="'{{ __('canvastack::ui.filter.enter_placeholder') }} ' + filter.label"
                                        :disabled="isApplying || cascadeState.isProcessing"
                                    >
                                </template>
                                
                                {{-- Date Box --}}
                                <template x-if="filter.type === 'datebox'">
                                    <input 
                                        :id="'filter_' + filter.column"
                                        type="text"
                                        x-model="filterValues[filter.column]"
                                        class="input input-bordered w-full"
                                        placeholder="{{ __('canvastack::ui.filter.select_date') }}"
                                        :disabled="isApplying || cascadeState.isProcessing"
                                        readonly
                                    >
                                </template>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Actions --}}
                    <div class="flex gap-2 mt-6">
                        <button 
                            type="submit" 
                            class="btn btn-primary flex-1"
                            :class="{ 'btn-disabled opacity-60': isApplying || cascadeState.isProcessing }"
                            :disabled="isApplying || cascadeState.isProcessing"
                            @click="if (isApplying || cascadeState.isProcessing) { $event.preventDefault(); $event.stopPropagation(); return false; }"
                            :style="(isApplying || cascadeState.isProcessing) ? 'pointer-events: none !important; cursor: not-allowed !important;' : ''"
                            dusk="apply-filter"
                        >
                            <span x-show="!isApplying && !cascadeState.isProcessing">{{ __('canvastack::ui.buttons.apply_filter') }}</span>
                            <span x-show="isApplying || cascadeState.isProcessing" class="flex items-center gap-2">
                                <span class="loading loading-spinner loading-sm"></span>
                                <span x-text="isApplying ? '{{ __('canvastack::ui.filter.applying') }}' : '{{ __('canvastack::ui.filter.updating_filters') }}'"></span>
                            </span>
                        </button>
                        <button 
                            type="button" 
                            @click="if (isApplying || cascadeState.isProcessing) { $event.preventDefault(); $event.stopPropagation(); return false; } else { clearFilters(); }"
                            class="btn btn-ghost"
                            :class="{ 'btn-disabled opacity-60': isApplying || cascadeState.isProcessing }"
                            :disabled="isApplying || cascadeState.isProcessing"
                            :style="(isApplying || cascadeState.isProcessing) ? 'pointer-events: none !important; cursor: not-allowed !important;' : ''"
                            dusk="clear-filter"
                        >
                            {{ __('canvastack::ui.buttons.clear') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- External JavaScript (Vite) --}}
@canvastackVite('resources/js/app.js')

<style>
/* Filter-specific styles */
.filter-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.filter-loading {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24'%3E%3Cpath fill='%236366f1' d='M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z' opacity='.25'/%3E%3Cpath fill='%236366f1' d='M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z'%3E%3CanimateTransform attributeName='transform' type='rotate' dur='0.75s' values='0 12 12;360 12 12' repeatCount='indefinite'/%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
}
</style>

{{--
    Filter Modal Component
    
    A modal dialog for table filtering with Alpine.js
    
    @props
    - filters: array - Array of filter configurations
    - activeFilters: array - Currently active filter values
    - tableName: string - Name of the table for session storage
    - activeFilterCount: int - Number of active filters (for badge)
--}}

@props([
    'filters' => [],
    'activeFilters' => [],
    'tableName' => '',
    'activeFilterCount' => 0,
    'showButton' => false, // NEW: Control whether to show the filter button
    'config' => [], // NEW: Table configuration including bidirectional_cascade
])

<div x-data="filterModal()" x-cloak>
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
        @open-filter-modal.window="open = true"
        style="display: none;"
        dusk="filter-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="filter-modal-title"
        id="filter-modal-dialog"
    >
        {{-- Backdrop with Blur Effect - Same transition as modal --}}
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
        
        {{-- Modal Content with Random Slide Animation --}}
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
                                    :title="'{{ __('canvastack::ui.filter.remove') }} ' + getFilterLabel(column)"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                
                {{-- Cascade Direction Indicator --}}
                <div 
                    x-show="cascadeState.isProcessing" 
                    class="mb-4 p-3 bg-primary/10 dark:bg-primary/20 border border-primary/30 dark:border-primary/40 rounded-lg"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                >
                    <div class="flex items-center gap-2 text-sm text-primary dark:text-primary-content mb-2">
                        <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                        <span class="font-medium">{{ __('canvastack::ui.filter.updating_filters') }}</span>
                    </div>
                    
                    <div 
                        x-show="cascadeState.affectedFilters.length > 0"
                        class="mt-2 flex flex-wrap gap-2"
                        role="list"
                        aria-label="{{ __('canvastack::ui.filter.affected_filters') }}"
                    >
                        <template x-for="column in cascadeState.affectedFilters" :key="column">
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-1 bg-primary/20 dark:bg-primary/30 border border-primary/40 dark:border-primary/50 rounded-md text-xs text-primary dark:text-primary-content font-medium"
                                x-text="getFilterLabel(column)"
                                role="listitem"
                            ></span>
                        </template>
                    </div>
                </div>
                
                {{-- Error Notification (Task 2.6) --}}
                <div 
                    x-show="cascadeState.hasError" 
                    class="mb-4 p-3 bg-error/10 dark:bg-error/20 border border-error/30 dark:border-error/40 rounded-lg"
                    role="alert"
                    aria-live="assertive"
                    aria-atomic="true"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                >
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-error dark:text-error-content flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-error dark:text-error-content" x-text="cascadeState.error"></p>
                            <p class="text-xs text-error/80 dark:text-error-content/80 mt-1">
                                {{ __('canvastack::ui.filter.error_preserved_options') }}
                            </p>
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
                <form @submit.prevent="applyFilters" role="form" aria-label="{{ __('canvastack::ui.filter.form_label') }}">
                    <div class="space-y-4" role="group" aria-label="{{ __('canvastack::ui.filter.filters_group') }}">
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
                                    <div>
                                        {{-- Select Dropdown --}}
                                        <div class="relative">
                                            <select 
                                                :id="'filter_' + filter.column"
                                                x-model="filterValues[filter.column]"
                                                @change="debouncedHandleFilterChange(filter)"
                                                class="select select-bordered w-full filter-select"
                                                :class="{ 'filter-loading': filter.loading }"
                                                :disabled="filter.loading"
                                                :dusk="'filter-' + filter.column"
                                                :aria-busy="filter.loading"
                                                :aria-describedby="filter.loading ? 'loading-' + filter.column : null"
                                            >
                                                <option value="">{{ __('canvastack::ui.filter.select_placeholder') }}</option>
                                                <template x-for="option in filter.options" :key="option.value">
                                                    <option :value="option.value" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            
                                            {{-- Cascade Indicator (left side) --}}
                                            <div 
                                                x-show="cascadeState.affectedFilters.includes(filter.column)" 
                                                class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-75"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-75"
                                                role="status"
                                                :aria-label="'{{ __('canvastack::ui.filter.updating') }} ' + filter.label"
                                            >
                                                <i data-lucide="refresh-cw" class="w-4 h-4 text-primary animate-spin" aria-hidden="true"></i>
                                            </div>
                                            
                                            {{-- Loading Spinner (right side) --}}
                                            <div 
                                                x-show="filter.loading" 
                                                class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-75"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                role="status"
                                                :aria-label="'{{ __('canvastack::ui.filter.loading') }} ' + filter.label"
                                            >
                                                <span class="loading loading-spinner loading-sm text-primary" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        
                                        {{-- Empty State for No Options --}}
                                        <template x-if="filter.options.length === 0 && !filter.loading">
                                            <div 
                                                class="mt-3 p-4 text-center bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg"
                                                role="status"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                            >
                                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-400 dark:text-gray-500"></i>
                                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {{ __('canvastack::ui.filter.no_options_available') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                    {{ __('canvastack::ui.filter.try_different_filters') }}
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                
                                {{-- Input Box --}}
                                <template x-if="filter.type === 'inputbox'">
                                    <div class="relative">
                                        <input 
                                            :id="'filter_' + filter.column"
                                            type="text"
                                            x-model="filterValues[filter.column]"
                                            class="input input-bordered w-full filter-select"
                                            :class="{ 'filter-loading': filter.loading }"
                                            :placeholder="'{{ __('canvastack::ui.filter.enter_placeholder') }} ' + filter.label"
                                            :dusk="'filter-' + filter.column"
                                            :disabled="filter.loading"
                                            :aria-busy="filter.loading"
                                            :aria-describedby="filter.loading ? 'loading-' + filter.column : null"
                                        >
                                        
                                        {{-- Cascade Indicator (left side) --}}
                                        <div 
                                            x-show="cascadeState.affectedFilters.includes(filter.column)" 
                                            class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-75"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-75"
                                            role="status"
                                            :aria-label="'{{ __('canvastack::ui.filter.updating') }} ' + filter.label"
                                        >
                                            <i data-lucide="refresh-cw" class="w-4 h-4 text-primary animate-spin" aria-hidden="true"></i>
                                        </div>
                                        
                                        {{-- Loading Spinner (right side) --}}
                                        <div 
                                            x-show="filter.loading" 
                                            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-75"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            role="status"
                                            :aria-label="'{{ __('canvastack::ui.filter.loading') }} ' + filter.label"
                                        >
                                            <span class="loading loading-spinner loading-sm text-primary" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                {{-- Date Box with Flatpickr --}}
                                <template x-if="filter.type === 'datebox'">
                                    <div>
                                        <div class="relative">
                                            <input 
                                                :id="'filter_' + filter.column"
                                                type="text"
                                                x-model="filterValues[filter.column]"
                                                class="input input-bordered w-full filter-select"
                                                :class="{ 'filter-loading': filter.loading }"
                                                placeholder="{{ __('canvastack::ui.filter.select_date') }}"
                                                :dusk="'filter-' + filter.column"
                                                :disabled="filter.loading"
                                                :aria-busy="filter.loading"
                                                :aria-describedby="filter.loading ? 'loading-' + filter.column : null"
                                                readonly
                                            >
                                            
                                            {{-- Cascade Indicator (left side) --}}
                                            <div 
                                                x-show="cascadeState.affectedFilters.includes(filter.column)" 
                                                class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-75"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-75"
                                                role="status"
                                                :aria-label="'{{ __('canvastack::ui.filter.updating') }} ' + filter.label"
                                            >
                                                <i data-lucide="refresh-cw" class="w-4 h-4 text-primary animate-spin" aria-hidden="true"></i>
                                            </div>
                                            
                                            {{-- Loading Spinner (right side) --}}
                                            <div 
                                                x-show="filter.loading" 
                                                class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-75"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                role="status"
                                                :aria-label="'{{ __('canvastack::ui.filter.loading') }} ' + filter.label"
                                            >
                                                <span class="loading loading-spinner loading-sm text-primary" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        
                                        {{-- Date info --}}
                                        <div 
                                            x-show="filter.availableDates && filter.availableDates.length > 0" 
                                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            <i data-lucide="calendar" class="w-3 h-3 inline" aria-hidden="true"></i>
                                            <span x-text="filter.availableDates.length + ' {{ __('canvastack::ui.filter.dates_available') }}'"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                {{-- Loading State Message with ARIA --}}
                                <div 
                                    :id="'loading-' + filter.column"
                                    x-show="filter.loading" 
                                    class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                                    role="status"
                                    aria-live="polite"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                >
                                    <span class="loading loading-spinner loading-xs text-primary"></span>
                                    <span x-text="'{{ __('canvastack::ui.filter.loading_options_for') }} ' + filter.label"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Actions --}}
                    <div class="flex gap-2 mt-6" role="group" aria-label="{{ __('canvastack::ui.filter.actions') }}">
                        <button 
                            type="submit" 
                            class="btn btn-primary flex-1"
                            :disabled="isApplying"
                            dusk="apply-filter"
                            :aria-busy="isApplying"
                        >
                            <span x-show="!isApplying">{{ __('canvastack::ui.buttons.apply_filter') }}</span>
                            <span x-show="isApplying" class="flex items-center gap-2">
                                <span class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                                <span>{{ __('canvastack::ui.filter.applying') }}</span>
                            </span>
                        </button>
                        <button 
                            type="button" 
                            @click="clearFilters" 
                            class="btn btn-ghost"
                            :disabled="isApplying"
                            dusk="clear-filter"
                            aria-label="{{ __('canvastack::ui.buttons.clear_all_filters') }}"
                        >
                            {{ __('canvastack::ui.buttons.clear') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filterModal() {
    return {
        open: false,
        filters: @json($filters),
        filterValues: @json($activeFilters, JSON_FORCE_OBJECT),
        activeFilterCount: {{ $activeFilterCount }},
        isApplying: false,
        slideAnimation: 'translateY(-2rem)',
        slideAnimationClass: '-translate-y-8',
        isRestoring: false, // Flag to prevent clearing values during restore
        config: @json($config), // NEW: Table configuration including bidirectional_cascade
        
        // Cascade state tracking (Task 1.6)
        cascadeState: {
            isProcessing: false,      // Tracks if cascade is currently running
            currentFilter: null,       // The filter currently being processed
            affectedFilters: [],       // Array of filter columns affected by cascade
            direction: null,           // 'upstream', 'downstream', 'both', or 'specific'
            hasError: false,           // Tracks if an error occurred
            error: null                // Error message to display
        },
        
        // Frontend cache for filter options (Task 3.2)
        filterOptionsCache: new Map(),
        cacheTTL: {{ config('canvastack.table.filters.frontend_cache_ttl', 300) }} * 1000, // Convert seconds to milliseconds
        
        /**
         * Debounce utility function (Task 3.1).
         * 
         * Prevents excessive API calls by delaying function execution until
         * after a specified wait time has elapsed since the last call.
         * 
         * @param {Function} func - The function to debounce
         * @param {number} wait - The delay in milliseconds
         * @returns {Function} Debounced function
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Generate cache key from column and parent filters (Task 3.2).
         * 
         * Creates a unique cache key based on the filter column and parent filter values.
         * This ensures that different parent filter combinations are cached separately.
         * 
         * @param {string} column - The filter column name
         * @param {Object} parentFilters - The parent filter values
         * @returns {string} Cache key
         */
        generateCacheKey(column, parentFilters) {
            // Sort parent filters by key for consistent cache keys
            const sortedFilters = Object.keys(parentFilters || {})
                .sort()
                .reduce((acc, key) => {
                    acc[key] = parentFilters[key];
                    return acc;
                }, {});
            
            return `${column}:${JSON.stringify(sortedFilters)}`;
        },
        
        /**
         * Get cached filter options (Task 3.2).
         * 
         * Retrieves cached filter options if they exist and haven't expired.
         * Returns null if cache miss or expired.
         * 
         * @param {string} cacheKey - The cache key
         * @returns {Object|null} Cached data or null
         */
        getCachedOptions(cacheKey) {
            const cached = this.filterOptionsCache.get(cacheKey);
            
            if (!cached) {
                console.log('Cache miss:', cacheKey);
                return null;
            }
            
            // Check if cache has expired
            const now = Date.now();
            const age = now - cached.timestamp;
            
            if (age > this.cacheTTL) {
                console.log('Cache expired:', cacheKey, `(age: ${Math.round(age / 1000)}s, TTL: ${this.cacheTTL / 1000}s)`);
                // Remove expired entry
                this.filterOptionsCache.delete(cacheKey);
                return null;
            }
            
            console.log('Cache hit:', cacheKey, `(age: ${Math.round(age / 1000)}s)`);
            return cached.data;
        },
        
        /**
         * Set cached filter options (Task 3.2).
         * 
         * Stores filter options in cache with current timestamp.
         * Respects memory limits by removing oldest entries if cache is too large.
         * 
         * @param {string} cacheKey - The cache key
         * @param {Object} data - The data to cache
         */
        setCachedOptions(cacheKey, data) {
            // Check cache size and remove oldest entries if needed
            const maxCacheSize = 100; // Maximum number of cached entries
            
            if (this.filterOptionsCache.size >= maxCacheSize) {
                console.log('Cache size limit reached, removing oldest entries');
                
                // Find oldest entry
                let oldestKey = null;
                let oldestTimestamp = Date.now();
                
                for (const [key, value] of this.filterOptionsCache.entries()) {
                    if (value.timestamp < oldestTimestamp) {
                        oldestTimestamp = value.timestamp;
                        oldestKey = key;
                    }
                }
                
                // Remove oldest entry
                if (oldestKey) {
                    this.filterOptionsCache.delete(oldestKey);
                    console.log('Removed oldest cache entry:', oldestKey);
                }
            }
            
            // Store in cache with timestamp
            this.filterOptionsCache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            console.log('Cached options:', cacheKey, `(cache size: ${this.filterOptionsCache.size})`);
        },
        
        /**
         * Clear all cached filter options (Task 3.2).
         * 
         * Removes all entries from the cache. Useful for manual cache invalidation.
         */
        clearCache() {
            const size = this.filterOptionsCache.size;
            this.filterOptionsCache.clear();
            console.log(`Cache cleared (${size} entries removed)`);
            
            // Show notification if available
            if (window.showNotification) {
                window.showNotification('info', '{{ __('canvastack::ui.filter.cache_cleared') }}');
            }
        },
        
        /**
         * Get cache statistics (Task 3.2).
         * 
         * Returns information about the current cache state.
         * 
         * @returns {Object} Cache statistics
         */
        getCacheStats() {
            const now = Date.now();
            let totalAge = 0;
            let expiredCount = 0;
            
            for (const [key, value] of this.filterOptionsCache.entries()) {
                const age = now - value.timestamp;
                totalAge += age;
                
                if (age > this.cacheTTL) {
                    expiredCount++;
                }
            }
            
            const avgAge = this.filterOptionsCache.size > 0 
                ? Math.round(totalAge / this.filterOptionsCache.size / 1000) 
                : 0;
            
            return {
                size: this.filterOptionsCache.size,
                maxSize: 100,
                ttl: this.cacheTTL / 1000,
                avgAge: avgAge,
                expiredCount: expiredCount
            };
        },
        
        /**
         * Fetch filter options with caching (Task 3.2).
         * 
         * Fetches filter options from the API with frontend caching support.
         * Shows cached options immediately if available, then fetches fresh data in background.
         * 
         * @param {Object} filter - The filter to fetch options for
         * @param {Object} parentFilters - The parent filter values
         * @returns {Promise<Object>} The filter options data
         */
        async fetchFilterOptionsWithCache(filter, parentFilters) {
            // Generate cache key
            const cacheKey = this.generateCacheKey(filter.column, parentFilters);
            
            // Try to get cached options
            const cached = this.getCachedOptions(cacheKey);
            
            if (cached) {
                // Show cached options immediately
                console.log(`Using cached options for ${filter.column}`);
                filter.loading = false; // Hide loading spinner since we have data
                
                // Apply cached data
                if (cached.type === 'date_range') {
                    filter.minDate = cached.min;
                    filter.maxDate = cached.max;
                    filter.dateCount = cached.count;
                    filter.availableDates = cached.availableDates || [];
                    
                    // Update Flatpickr with cached dates
                    this.$nextTick(() => {
                        this.updateFlatpickr(filter);
                    });
                } else if (cached.type === 'options') {
                    filter.options = cached.options;
                }
            } else {
                // No cache, show loading spinner
                filter.loading = true;
            }
            
            // Fetch fresh data in background (always fetch to keep cache fresh)
            try {
                const requestBody = {
                    table: '{{ $tableName }}',
                    column: filter.column,
                    parentFilters: parentFilters,
                    type: filter.type
                };
                
                console.log(`Fetching fresh options for ${filter.column}:`, requestBody);
                
                const response = await fetch('{{ route('datatable.filter-options') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(requestBody)
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to load options for ${filter.column}`);
                }
                
                const data = await response.json();
                console.log(`Received fresh data for ${filter.column}:`, data);
                
                // Cache the fresh data
                this.setCachedOptions(cacheKey, data);
                
                // Update filter with fresh data
                if (data.type === 'date_range') {
                    filter.minDate = data.min;
                    filter.maxDate = data.max;
                    filter.dateCount = data.count;
                    filter.availableDates = data.availableDates || [];
                    
                    // Update Flatpickr with new dates
                    this.$nextTick(() => {
                        this.updateFlatpickr(filter);
                    });
                    
                    // Clear date value if not in available dates
                    if (this.filterValues[filter.column]) {
                        if (filter.availableDates && filter.availableDates.length > 0) {
                            if (!filter.availableDates.includes(this.filterValues[filter.column])) {
                                console.log(`Clearing invalid date for ${filter.column}`);
                                this.filterValues[filter.column] = '';
                            }
                        }
                    }
                } else if (data.type === 'options') {
                    filter.options = data.options;
                    
                    // Clear value if not in new options
                    if (this.filterValues[filter.column]) {
                        const hasOption = filter.options.some(opt => opt.value === this.filterValues[filter.column]);
                        if (!hasOption) {
                            console.log(`Clearing invalid value for ${filter.column}`);
                            this.filterValues[filter.column] = '';
                        }
                    }
                }
                
                return data;
                
            } catch (error) {
                console.error(`Error fetching options for ${filter.column}:`, error);
                
                // If we have cached data, keep using it despite the error
                if (cached) {
                    console.log(`Using cached data despite fetch error for ${filter.column}`);
                    return cached;
                }
                
                throw error;
            } finally {
                filter.loading = false;
            }
        },
        
        init() {
            // Initialize availableDates for date filters
            this.filters.forEach(filter => {
                if (filter.type === 'datebox' || filter.type === 'daterangebox') {
                    filter.availableDates = filter.availableDates || [];
                    filter.minDate = filter.minDate || null;
                    filter.maxDate = filter.maxDate || null;
                    filter.dateCount = filter.dateCount || 0;
                    filter.flatpickrInstance = null; // Store Flatpickr instance
                }
            });
            
            // Initialize debounced handler (Task 3.1)
            this.debouncedHandleFilterChange = this.debounce(
                this.handleFilterChangeBidirectional.bind(this),
                {{ config('canvastack.table.filters.debounce_delay', 300) }} // Configurable delay
            );
            
            this.updateActiveCount();
            this.loadInitialOptions();
            
            // Load filters from session on page load
            this.loadFiltersFromSession();
            
            // Show toast notification if filters are active
            if (this.activeFilterCount > 0) {
                this.showFilterActiveToast();
            }
            
            // Initialize Lucide icons and handle animation when modal opens
            this.$watch('open', (value) => {
                if (value) {
                    console.log('Modal opened via x-watch');
                    
                    // Set random slide animation when opening
                    this.setRandomSlideAnimation();
                    
                    // Apply initial animation state
                    const modal = this.$refs.modalContent;
                    if (modal) {
                        // Set initial state (hidden with transform)
                        modal.style.opacity = '0';
                        modal.style.transform = this.slideAnimation + ' scale(0.95)';
                        
                        // Trigger animation on next frame
                        this.$nextTick(() => {
                            requestAnimationFrame(() => {
                                modal.style.opacity = '1';
                                modal.style.transform = 'translate(0, 0) scale(1)';
                            });
                            
                            // Initialize Flatpickr for date inputs after modal opens
                            this.initFlatpickr();
                            
                            // Restore filter options when modal opens
                            this.restoreFilterOptions();
                        });
                    }
                } else {
                    // Reset to initial state when closing
                    const modal = this.$refs.modalContent;
                    if (modal) {
                        modal.style.opacity = '0';
                        modal.style.transform = 'translateY(-2rem) scale(0.95)';
                    }
                    
                    // Destroy Flatpickr instances when closing
                    this.destroyFlatpickr();
                }
            });
            
            // Listen for custom open event
            this.$el.addEventListener('open-filter-modal', () => {
                console.log('Modal opened via custom event');
                this.open = true;
                
                // Restore filter options when modal opens
                this.restoreFilterOptions();
            });
        },
        
        initFlatpickr() {
            // Wait for Flatpickr to be available
            if (typeof window.flatpickr === 'undefined') {
                console.warn('Flatpickr not loaded yet');
                return;
            }
            
            this.filters.forEach(filter => {
                if (filter.type === 'datebox' || filter.type === 'daterangebox') {
                    const inputId = 'filter_' + filter.column;
                    const input = document.getElementById(inputId);
                    
                    if (input && !filter.flatpickrInstance) {
                        // Destroy existing instance if any
                        if (input._flatpickr) {
                            input._flatpickr.destroy();
                        }
                        
                        // Prepare config
                        const config = {
                            dateFormat: 'Y-m-d',
                            onChange: (selectedDates, dateStr) => {
                                this.filterValues[filter.column] = dateStr;
                                
                                // Trigger cascade if filter has relations
                                if (filter.relate) {
                                    this.handleFilterChange(filter);
                                }
                            },
                            onReady: () => {
                                console.log('Flatpickr ready for', filter.column);
                            }
                        };
                        
                        // Only add minDate if it's valid
                        if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
                            config.minDate = filter.minDate;
                        }
                        
                        // Only add maxDate if it's valid
                        if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
                            config.maxDate = filter.maxDate;
                        }
                        
                        // Only add enable if we have valid dates
                        if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
                            config.enable = filter.availableDates;
                        }
                        
                        // Create Flatpickr instance
                        try {
                            filter.flatpickrInstance = window.flatpickr(input, config);
                            
                            console.log('Flatpickr initialized for', filter.column, {
                                minDate: config.minDate || 'none',
                                maxDate: config.maxDate || 'none',
                                enabledDates: config.enable ? config.enable.length : 'all'
                            });
                        } catch (error) {
                            console.error('Error initializing Flatpickr for', filter.column, error);
                        }
                    }
                }
            });
        },
        
        updateFlatpickr(filter) {
            // Check if instance exists and is valid
            if (!filter.flatpickrInstance || typeof filter.flatpickrInstance.set !== 'function') {
                console.log('Flatpickr instance not valid, initializing for', filter.column);
                
                // Destroy invalid instance
                if (filter.flatpickrInstance) {
                    try {
                        filter.flatpickrInstance.destroy();
                    } catch (e) {
                        console.warn('Error destroying invalid instance:', e);
                    }
                    filter.flatpickrInstance = null;
                }
                
                // Initialize this specific filter's Flatpickr
                const inputId = 'filter_' + filter.column;
                const input = document.getElementById(inputId);
                
                if (!input) {
                    console.warn('Input not found for', filter.column);
                    return;
                }
                
                // Destroy existing instance if any
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }
                
                // Prepare config
                const config = {
                    dateFormat: 'Y-m-d',
                    onChange: (selectedDates, dateStr) => {
                        this.filterValues[filter.column] = dateStr;
                        
                        // Trigger cascade if filter has relations
                        if (filter.relate) {
                            this.handleFilterChange(filter);
                        }
                    }
                };
                
                // Add minDate if valid
                if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
                    config.minDate = filter.minDate;
                }
                
                // Add maxDate if valid
                if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
                    config.maxDate = filter.maxDate;
                }
                
                // Add enable if we have valid dates
                if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
                    // Use enable to restrict to specific dates
                    config.enable = filter.availableDates;
                    console.log('Flatpickr config enable set to specific dates:', filter.availableDates);
                } else if (filter.minDate && filter.maxDate) {
                    // If no specific dates but we have min/max, enable the range
                    config.enable = [
                        {
                            from: filter.minDate,
                            to: filter.maxDate
                        }
                    ];
                    console.log('Flatpickr config enable set to date range:', filter.minDate, 'to', filter.maxDate);
                }
                
                // Create Flatpickr instance
                try {
                    filter.flatpickrInstance = window.flatpickr(input, config);
                    console.log('Flatpickr initialized for', filter.column, config);
                } catch (error) {
                    console.error('Error initializing Flatpickr for', filter.column, error);
                    return;
                }
            }
            
            // Now update the instance with current filter data
            try {
                // Update minDate
                if (filter.minDate && filter.minDate !== null && filter.minDate !== '') {
                    filter.flatpickrInstance.set('minDate', filter.minDate);
                } else {
                    filter.flatpickrInstance.set('minDate', null);
                }
                
                // Update maxDate
                if (filter.maxDate && filter.maxDate !== null && filter.maxDate !== '') {
                    filter.flatpickrInstance.set('maxDate', filter.maxDate);
                } else {
                    filter.flatpickrInstance.set('maxDate', null);
                }
                
                // Update enable (available dates)
                if (filter.availableDates && Array.isArray(filter.availableDates) && filter.availableDates.length > 0) {
                    // Use enable to restrict to specific dates
                    filter.flatpickrInstance.set('enable', filter.availableDates);
                    console.log('Flatpickr enable set to specific dates:', filter.availableDates);
                } else if (filter.minDate && filter.maxDate) {
                    // If no specific dates but we have min/max, enable the range
                    filter.flatpickrInstance.set('enable', [
                        {
                            from: filter.minDate,
                            to: filter.maxDate
                        }
                    ]);
                    console.log('Flatpickr enable set to date range:', filter.minDate, 'to', filter.maxDate);
                } else {
                    // No restrictions - allow all dates
                    filter.flatpickrInstance.set('enable', undefined);
                    filter.flatpickrInstance.set('disable', []);
                    console.log('Flatpickr enable set to all dates (no restrictions)');
                }
                
                // Clear selected date if not in available dates
                if (this.filterValues[filter.column]) {
                    if (filter.availableDates && filter.availableDates.length > 0) {
                        if (!filter.availableDates.includes(this.filterValues[filter.column])) {
                            this.filterValues[filter.column] = '';
                            filter.flatpickrInstance.clear();
                        }
                    }
                }
                
                console.log('Flatpickr updated for', filter.column, {
                    minDate: filter.minDate || 'none',
                    maxDate: filter.maxDate || 'none',
                    enabledDates: filter.availableDates ? filter.availableDates.length : 'all'
                });
            } catch (error) {
                console.error('Error updating Flatpickr:', error);
                // Try to reinitialize on error
                filter.flatpickrInstance = null;
                this.initFlatpickr();
            }
        },
        
        destroyFlatpickr() {
            this.filters.forEach(filter => {
                if (filter.flatpickrInstance) {
                    filter.flatpickrInstance.destroy();
                    filter.flatpickrInstance = null;
                }
            });
        },
        
        setRandomSlideAnimation() {
            // Array of possible slide animations with weights
            const animations = [
                // Default: From top (weight: 5 - most common)
                { transform: 'translateY(-2rem)', className: '-translate-y-8', weight: 5 },
                
                // From bottom (weight: 2)
                { transform: 'translateY(2rem)', className: 'translate-y-8', weight: 2 },
                
                // From left (weight: 2)
                { transform: 'translateX(-2rem)', className: '-translate-x-8', weight: 2 },
                
                // From right (weight: 2)
                { transform: 'translateX(2rem)', className: 'translate-x-8', weight: 2 },
                
                // From top-left (weight: 1)
                { transform: 'translate(-1.5rem, -1.5rem)', className: '-translate-x-6 -translate-y-6', weight: 1 },
                
                // From top-right (weight: 1)
                { transform: 'translate(1.5rem, -1.5rem)', className: 'translate-x-6 -translate-y-6', weight: 1 },
                
                // From bottom-left (weight: 1)
                { transform: 'translate(-1.5rem, 1.5rem)', className: '-translate-x-6 translate-y-6', weight: 1 },
                
                // From bottom-right (weight: 1)
                { transform: 'translate(1.5rem, 1.5rem)', className: 'translate-x-6 translate-y-6', weight: 1 },
                
                // Zoom in (weight: 1)
                { transform: 'scale(0.8)', className: 'scale-75', weight: 1 },
                
                // Rotate + scale (weight: 1)
                { transform: 'scale(0.9) rotate(3deg)', className: 'scale-90 rotate-3', weight: 1 }
            ];
            
            // Create weighted array (repeat items based on weight)
            const weightedAnimations = [];
            animations.forEach(anim => {
                for (let i = 0; i < anim.weight; i++) {
                    weightedAnimations.push(anim);
                }
            });
            
            // Pick random animation from weighted array
            const randomIndex = Math.floor(Math.random() * weightedAnimations.length);
            const selected = weightedAnimations[randomIndex];
            
            this.slideAnimation = selected.transform;
            this.slideAnimationClass = selected.className;
            
            console.log('Random slide animation:', selected.transform, 'Class:', selected.className);
        },
        
        async loadFiltersFromSession() {
            try {
                const tableId = '{{ $tableId }}'; // Use tableId for JavaScript global variable
                
                // If we have active filters passed from backend, apply them automatically
                if (this.activeFilterCount > 0) {
                    // Set global filter variable for DataTables
                    window['tableFilters_' + tableId] = this.filterValues;
                    
                    // Wait for DataTable to be initialized
                    const maxAttempts = 20; // 2 seconds max wait (20 * 100ms)
                    let attempts = 0;
                    
                    const checkDataTable = () => {
                        attempts++;
                        
                        // Check if DataTable is initialized
                        const dataTable = window.dataTable || window['dataTable_' + tableId];
                        
                        if (dataTable && typeof dataTable.ajax === 'object' && typeof dataTable.ajax.reload === 'function') {
                            // DataTable is ready, reload with filters
                            console.log('DataTable initialized, applying session filters');
                            dataTable.ajax.reload();
                            return true;
                        }
                        
                        // Check if we've exceeded max attempts
                        if (attempts >= maxAttempts) {
                            console.warn('DataTable not initialized after 2 seconds, filters may not be applied');
                            return true;
                        }
                        
                        // Try again after 100ms
                        setTimeout(checkDataTable, 100);
                        return false;
                    };
                    
                    // Start checking
                    checkDataTable();
                }
            } catch (error) {
                console.error('Error loading filters from session:', error);
            }
        },
        
        /**
         * Detect cascade direction for bi-directional cascade.
         * 
         * Returns upstream (before) and downstream (after) filters relative to the changed filter.
         * 
         * @param {Object} changedFilter - The filter that was changed
         * @returns {Object} Object with upstream and downstream filter arrays
         */
        detectCascadeDirection(changedFilter) {
            // Find the index of the changed filter
            const filterIndex = this.filters.findIndex(f => f.column === changedFilter.column);
            
            // Handle edge case: filter not found
            if (filterIndex === -1) {
                console.warn('Filter not found:', changedFilter.column);
                return {
                    upstream: [],
                    downstream: []
                };
            }
            
            // Get upstream filters (all filters before the changed filter)
            const upstream = this.filters.slice(0, filterIndex);
            
            // Get downstream filters (all filters after the changed filter)
            const downstream = this.filters.slice(filterIndex + 1);
            
            console.log('Cascade direction detected:', {
                changedFilter: changedFilter.column,
                filterIndex: filterIndex,
                totalFilters: this.filters.length,
                upstreamCount: upstream.length,
                downstreamCount: downstream.length,
                upstream: upstream.map(f => f.column),
                downstream: downstream.map(f => f.column)
            });
            
            return {
                upstream: upstream,
                downstream: downstream
            };
        },
        
        /**
         * Cascade changes to upstream filters (reverse direction).
         * 
         * This method updates filters that appear BEFORE the changed filter in the list.
         * It processes filters in REVERSE order (bottom to top) and builds parent context
         * with only the changed filter initially, then accumulates as it goes up.
         * 
         * @param {Object} changedFilter - The filter that was changed
         * @param {Array} upstreamFilters - Array of filters to update (before changed filter)
         * @returns {Promise<void>}
         */
        async cascadeUpstream(changedFilter, upstreamFilters) {
            // Handle empty upstream filters array
            if (!upstreamFilters || upstreamFilters.length === 0) {
                console.log('No upstream filters to cascade');
                return;
            }
            
            console.log('Starting upstream cascade:', {
                changedFilter: changedFilter.column,
                changedValue: this.filterValues[changedFilter.column],
                upstreamCount: upstreamFilters.length,
                upstreamFilters: upstreamFilters.map(f => f.column)
            });
            
            // Build parent context: only the changed filter
            const parentFilters = {
                [changedFilter.column]: this.filterValues[changedFilter.column]
            };
            
            console.log('Initial parent filters for upstream:', parentFilters);
            
            // Process upstream filters in REVERSE order (bottom to top)
            const reversedFilters = [...upstreamFilters].reverse();
            
            for (const filter of reversedFilters) {
                console.log(`Updating upstream filter: ${filter.column}`, {
                    parentFilters: { ...parentFilters },
                    currentValue: this.filterValues[filter.column]
                });
                
                // Update this filter's options via API with caching (Task 3.2)
                try {
                    await this.fetchFilterOptionsWithCache(filter, parentFilters);
                    
                    // Add this filter to parent context for next iteration
                    // This accumulates parent filters as we go up the chain
                    if (this.filterValues[filter.column]) {
                        parentFilters[filter.column] = this.filterValues[filter.column];
                        console.log(`Added ${filter.column} to parent filters:`, parentFilters);
                    }
                    
                } catch (error) {
                    console.error(`Error updating upstream filter ${filter.column}:`, error);
                    
                    // Show error notification if available
                    if (window.showNotification) {
                        window.showNotification('error', `Failed to update ${filter.label || filter.column}`);
                    }
                }
            }
            
            console.log('Upstream cascade complete');
        },
        
        /**
         * Cascade changes to downstream filters (forward direction).
         * 
         * This method updates filters that appear AFTER the changed filter in the list.
         * It processes filters in FORWARD order (top to bottom) and builds parent context
         * with all filters up to and including the changed filter.
         * 
         * @param {Object} changedFilter - The filter that was changed
         * @param {Array} downstreamFilters - Array of filters to update (after changed filter)
         * @returns {Promise<void>}
         */
        async cascadeDownstream(changedFilter, downstreamFilters) {
            // Handle empty downstream filters array
            if (!downstreamFilters || downstreamFilters.length === 0) {
                console.log('No downstream filters to cascade');
                return;
            }
            
            console.log('Starting downstream cascade:', {
                changedFilter: changedFilter.column,
                changedValue: this.filterValues[changedFilter.column],
                downstreamCount: downstreamFilters.length,
                downstreamFilters: downstreamFilters.map(f => f.column)
            });
            
            // Build parent context: all filters up to and including changed filter
            const parentFilters = this.buildParentFilters(changedFilter);
            
            console.log('Initial parent filters for downstream:', parentFilters);
            
            // Process downstream filters in FORWARD order (top to bottom)
            for (const filter of downstreamFilters) {
                console.log(`Updating downstream filter: ${filter.column}`, {
                    parentFilters: { ...parentFilters },
                    currentValue: this.filterValues[filter.column]
                });
                
                // Update this filter's options via API with caching (Task 3.2)
                try {
                    await this.fetchFilterOptionsWithCache(filter, parentFilters);
                    
                    // Add this filter to parent context for next iteration
                    // This accumulates parent filters as we go down the chain
                    if (this.filterValues[filter.column]) {
                        parentFilters[filter.column] = this.filterValues[filter.column];
                        console.log(`Added ${filter.column} to parent filters:`, parentFilters);
                    }
                    
                } catch (error) {
                    console.error(`Error updating downstream filter ${filter.column}:`, error);
                    
                    // Show error notification if available
                    if (window.showNotification) {
                        window.showNotification('error', `Failed to update ${filter.label || filter.column}`);
                    }
                }
            }
            
            console.log('Downstream cascade complete');
        },
        
        /**
         * Build parent filter context for a given filter.
         * 
         * Returns all filter values up to and including the specified filter.
         * 
         * @param {Object} targetFilter - The filter to build parent context for
         * @returns {Object} Object with parent filter values
         */
        buildParentFilters(targetFilter) {
            const parentFilters = {};
            const targetIndex = this.filters.findIndex(f => f.column === targetFilter.column);
            
            // Include all filters up to and including the target filter
            for (let i = 0; i <= targetIndex; i++) {
                const col = this.filters[i].column;
                if (this.filterValues[col]) {
                    parentFilters[col] = this.filterValues[col];
                }
            }
            
            return parentFilters;
        },
        
        /**
         * Handle filter change with bi-directional cascade support.
         * 
         * This is the main orchestration method for bi-directional cascade.
         * It detects cascade direction, executes upstream and/or downstream cascades,
         * and maintains backward compatibility with existing relate behavior.
         * 
         * @param {Object} filter - The filter that was changed
         * @returns {Promise<void>}
         */
        async handleFilterChangeBidirectional(filter) {
            // Prevent concurrent cascades
            if (this.cascadeState && this.cascadeState.isProcessing) {
                console.warn('Cascade already in progress, skipping');
                return;
            }
            
            // Initialize cascade state if not exists
            if (!this.cascadeState) {
                this.cascadeState = {
                    isProcessing: false,
                    currentFilter: null,
                    affectedFilters: [],
                    direction: null
                };
            }
            
            // Set processing flag
            this.cascadeState.isProcessing = true;
            this.cascadeState.currentFilter = filter;
            
            console.log('=== Starting Bi-Directional Cascade ===', {
                filter: filter.column,
                value: this.filterValues[filter.column],
                bidirectional: filter.bidirectional,
                relate: filter.relate
            });
            
            try {
                // Detect cascade direction
                const { upstream, downstream } = this.detectCascadeDirection(filter);
                
                console.log('Cascade direction detected:', {
                    upstreamCount: upstream.length,
                    downstreamCount: downstream.length,
                    upstream: upstream.map(f => f.column),
                    downstream: downstream.map(f => f.column)
                });
                
                // Determine which filters to update based on configuration
                if (filter.bidirectional || (this.config && this.config.bidirectional_cascade)) {
                    // Bi-directional mode: Update ALL related filters
                    this.cascadeState.direction = 'both';
                    this.cascadeState.affectedFilters = [
                        ...upstream.map(f => f.column),
                        ...downstream.map(f => f.column)
                    ];
                    
                    console.log('Bi-directional cascade enabled, updating both directions');
                    
                    // Execute upstream cascade (reverse direction)
                    if (upstream.length > 0) {
                        console.log('Executing upstream cascade...');
                        await this.cascadeUpstream(filter, upstream);
                    }
                    
                    // Execute downstream cascade (forward direction)
                    if (downstream.length > 0) {
                        console.log('Executing downstream cascade...');
                        await this.cascadeDownstream(filter, downstream);
                    }
                    
                } else if (filter.relate) {
                    // Backward compatibility: Only downstream (existing behavior)
                    this.cascadeState.direction = 'downstream';
                    
                    if (filter.relate === true) {
                        // Cascade to all filters after this one
                        this.cascadeState.affectedFilters = downstream.map(f => f.column);
                        
                        console.log('Relate=true, cascading to all downstream filters');
                        
                        if (downstream.length > 0) {
                            await this.cascadeDownstream(filter, downstream);
                        }
                        
                    } else if (typeof filter.relate === 'string') {
                        // Cascade to specific filter
                        this.cascadeState.direction = 'specific';
                        this.cascadeState.affectedFilters = [filter.relate];
                        
                        console.log('Relate=string, cascading to specific filter:', filter.relate);
                        
                        const targetFilter = this.filters.find(f => f.column === filter.relate);
                        if (targetFilter) {
                            await this.cascadeDownstream(filter, [targetFilter]);
                        }
                        
                    } else if (Array.isArray(filter.relate)) {
                        // Cascade to specified filters
                        this.cascadeState.direction = 'specific';
                        this.cascadeState.affectedFilters = filter.relate;
                        
                        console.log('Relate=array, cascading to specified filters:', filter.relate);
                        
                        const targetFilters = this.filters.filter(f => filter.relate.includes(f.column));
                        if (targetFilters.length > 0) {
                            await this.cascadeDownstream(filter, targetFilters);
                        }
                    }
                } else {
                    // No cascade configured
                    console.log('No cascade configured for this filter');
                }
                
                // Update cascade state
                this.updateCascadeState();
                
                console.log('=== Bi-Directional Cascade Complete ===');
                
            } catch (error) {
                console.error('=== Cascade Error ===', error);
                this.handleCascadeError(error);
            } finally {
                // Reset processing flag
                this.cascadeState.isProcessing = false;
                this.cascadeState.currentFilter = null;
            }
        },
        
        /**
         * Update cascade state after cascade completes.
         * 
         * This method can be used to update UI indicators or perform cleanup.
         */
        updateCascadeState() {
            // Update active filter count
            this.updateActiveCount();
            
            // Clear affected filters after a delay
            setTimeout(() => {
                if (this.cascadeState) {
                    this.cascadeState.affectedFilters = [];
                    this.cascadeState.direction = null;
                }
            }, 1000);
        },
        
        /**
         * Handle cascade errors gracefully.
         * 
         * Displays user-friendly error messages, logs errors for debugging,
         * and preserves previous filter options to prevent data loss.
         * 
         * @param {Error} error - The error that occurred
         */
        handleCascadeError(error) {
            // Log detailed error information for debugging
            console.error('=== Cascade Error Details ===');
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            console.error('Cascade state:', {
                isProcessing: this.cascadeState.isProcessing,
                currentFilter: this.cascadeState.currentFilter,
                affectedFilters: this.cascadeState.affectedFilters,
                direction: this.cascadeState.direction
            });
            console.error('Filter values:', this.filterValues);
            console.error('=============================');
            
            // Show user-friendly error notification
            if (window.showNotification) {
                window.showNotification(
                    'error',
                    '{{ __('canvastack::ui.filter.cascade_error') }}'
                );
            } else {
                // Fallback: Show inline error message if notification system not available
                this.showInlineError('{{ __('canvastack::ui.filter.cascade_error') }}');
            }
            
            // Keep previous filter options (don't clear)
            // This prevents data loss and allows users to retry
            console.log('Previous filter options preserved');
        },
        
        /**
         * Show inline error message in the modal.
         * 
         * This is a fallback when the global notification system is not available.
         * 
         * @param {string} message - The error message to display
         */
        showInlineError(message) {
            // Set error state
            this.cascadeState.error = message;
            this.cascadeState.hasError = true;
            
            // Auto-hide error after 5 seconds
            setTimeout(() => {
                this.cascadeState.hasError = false;
                this.cascadeState.error = null;
            }, 5000);
        },
        
        async handleFilterChange(filter) {
            // Update related filters when this filter changes
            if (filter.relate) {
                await this.updateRelatedFilters(filter);
            }
            
            // Auto-submit if configured
            if (filter.autoSubmit) {
                await this.applyFilters();
            }
        },
        
        async updateRelatedFilters(parentFilter) {
            const relatedColumns = this.getRelatedColumns(parentFilter);
            
            // Get current parent filter values for cascading
            const parentFilters = {};
            const parentIndex = this.filters.findIndex(f => f.column === parentFilter.column);
            
            // Include all filters up to and including the current one
            for (let i = 0; i <= parentIndex; i++) {
                const col = this.filters[i].column;
                if (this.filterValues[col]) {
                    parentFilters[col] = this.filterValues[col];
                }
            }
            
            console.log('Updating related filters:', {
                parentFilter: parentFilter.column,
                parentValue: this.filterValues[parentFilter.column],
                relatedColumns: relatedColumns,
                parentFilters: parentFilters
            });
            
            // Update each related filter
            for (const column of relatedColumns) {
                const filter = this.filters.find(f => f.column === column);
                if (filter) {
                    filter.loading = true;
                    
                    try {
                        const requestBody = {
                            table: '{{ $tableName }}',
                            column: column,
                            parentFilters: parentFilters,
                            type: filter.type // Send filter type to backend
                        };
                        
                        console.log('Fetching options for', column, 'with parent filters:', parentFilters);
                        
                        const response = await fetch('{{ route('datatable.filter-options') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(requestBody)
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to load filter options');
                        }
                        
                        const data = await response.json();
                        console.log('Received data for', column, ':', data);
                        
                        // Handle different response types
                        if (data.type === 'date_range') {
                            // For date inputs, set min/max attributes and available dates
                            filter.minDate = data.min;
                            filter.maxDate = data.max;
                            filter.dateCount = data.count;
                            filter.availableDates = data.availableDates || [];
                            
                            console.log('Date range updated for', column, ':', {
                                min: filter.minDate,
                                max: filter.maxDate,
                                count: filter.dateCount,
                                availableDates: filter.availableDates,
                                hasAvailableDates: filter.availableDates && filter.availableDates.length > 0
                            });
                            
                            // Update Flatpickr with new dates
                            this.$nextTick(() => {
                                this.updateFlatpickr(filter);
                            });
                        } else if (data.type === 'options') {
                            // For select inputs, update options
                            filter.options = data.options;
                            
                            // Restore value if we're in restore mode and value exists
                            if (this.isRestoring && this.filterValues[column]) {
                                console.log('  Restoring value for', column, ':', this.filterValues[column]);
                                // Value is already in filterValues, just need to ensure select element is updated
                                this.$nextTick(() => {
                                    const selectElement = document.getElementById('filter_' + column);
                                    if (selectElement && selectElement.value !== this.filterValues[column]) {
                                        selectElement.value = this.filterValues[column];
                                        console.log('  Select element updated for', column);
                                    }
                                });
                            }
                        }
                        
                        // Clear child filter value if parent changed (but not during restore)
                        if (!this.isRestoring) {
                            this.filterValues[column] = '';
                        }
                    } catch (error) {
                        console.error('Error loading filter options:', error);
                        // Show error notification if available
                        if (window.showNotification) {
                            window.showNotification('error', '{{ __('canvastack::ui.filter.error_loading_options') }}');
                        }
                    } finally {
                        filter.loading = false;
                    }
                }
            }
        },
        
        getRelatedColumns(filter) {
            if (filter.relate === true) {
                // Cascade to all filters after this one
                const currentIndex = this.filters.findIndex(f => f.column === filter.column);
                return this.filters.slice(currentIndex + 1).map(f => f.column);
            } else if (typeof filter.relate === 'string') {
                return [filter.relate];
            } else if (Array.isArray(filter.relate)) {
                return filter.relate;
            }
            return [];
        },
        
        async loadInitialOptions() {
            for (const filter of this.filters) {
                if (filter.type === 'selectbox' && (!filter.options || filter.options.length === 0)) {
                    filter.loading = true;
                    
                    try {
                        const response = await fetch('{{ route('datatable.filter-options') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                table: '{{ $tableName }}',
                                column: filter.column,
                                parentFilters: {}
                            })
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to load initial options');
                        }
                        
                        const data = await response.json();
                        filter.options = data.options;
                    } catch (error) {
                        console.error('Error loading initial options:', error);
                    } finally {
                        filter.loading = false;
                    }
                }
            }
        },
        
        async applyFilters() {
            this.isApplying = true;
            
            try {
                // Convert Proxy to plain object
                const plainFilters = {};
                Object.keys(this.filterValues).forEach(key => {
                    if (this.filterValues[key] !== '' && this.filterValues[key] !== null && this.filterValues[key] !== undefined) {
                        plainFilters[key] = this.filterValues[key];
                    }
                });
                
                // Debug: Log filter values before sending
                console.log('Applying filters (plain):', plainFilters);
                console.log('Filter values keys:', Object.keys(plainFilters));
                
                // Save to session
                const response = await fetch('{{ route('datatable.save-filters') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        table: '{{ $tableName }}',
                        filters: plainFilters
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to save filters');
                }
                
                const result = await response.json();
                console.log('Save filters response:', result);
                
                // Set global filter variable for DataTables AJAX
                const tableId = '{{ $tableId }}'; // Use tableId for JavaScript global variable
                window['tableFilters_' + tableId] = plainFilters;
                console.log('Set global filters:', window['tableFilters_' + tableId]);
                
                // Reload DataTable
                if (window.dataTable) {
                    console.log('Reloading window.dataTable');
                    window.dataTable.ajax.reload();
                } else if (window['dataTable_' + tableId]) {
                    console.log('Reloading dataTable_' + tableId);
                    window['dataTable_' + tableId].ajax.reload();
                }
                
                this.updateActiveCount();
                this.open = false;
                
                // Show success notification if available
                if (window.showNotification) {
                    window.showNotification('success', '{{ __('canvastack::ui.filter.applied_successfully') }}');
                }
            } catch (error) {
                console.error('Error applying filters:', error);
                if (window.showNotification) {
                    window.showNotification('error', '{{ __('canvastack::ui.filter.error_applying') }}');
                }
            } finally {
                this.isApplying = false;
            }
        },
        
        async clearFilters() {
            this.filterValues = {};
            await this.applyFilters();
        },
        
        async restoreFilterOptions() {
            // When modal opens, reload options for filters that have values
            // This ensures select dropdowns show the correct selected value
            
            this.isRestoring = true; // Set flag to prevent clearing values
            
            console.log('Restoring filter options...');
            console.log('Current filter values:', this.filterValues);
            
            for (const filter of this.filters) {
                console.log('Checking filter:', filter.column, 'Type:', filter.type, 'Value:', this.filterValues[filter.column]);
                
                // Only restore for selectbox filters that have a value
                if (filter.type === 'selectbox' && this.filterValues[filter.column]) {
                    // Check if options are empty or don't include current value
                    const currentValue = this.filterValues[filter.column];
                    const hasOption = filter.options && filter.options.some(opt => opt.value === currentValue);
                    
                    console.log('  Current value:', currentValue);
                    console.log('  Has option:', hasOption);
                    console.log('  Current options count:', filter.options ? filter.options.length : 0);
                    console.log('  First 5 options:', filter.options ? filter.options.slice(0, 5) : []);
                    
                    if (!hasOption) {
                        console.log('  Need to reload options for', filter.column);
                        
                        // Need to reload options
                        filter.loading = true;
                        
                        try {
                            // Get parent filters for cascading
                            const parentFilters = {};
                            const filterIndex = this.filters.findIndex(f => f.column === filter.column);
                            
                            // Include all filters before this one
                            for (let i = 0; i < filterIndex; i++) {
                                const col = this.filters[i].column;
                                if (this.filterValues[col]) {
                                    parentFilters[col] = this.filterValues[col];
                                }
                            }
                            
                            const response = await fetch('{{ route('datatable.filter-options') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    table: '{{ $tableName }}',
                                    column: filter.column,
                                    parentFilters: parentFilters,
                                    type: filter.type
                                })
                            });
                            
                            if (!response.ok) {
                                throw new Error('Failed to fetch filter options');
                            }
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                if (data.type === 'options') {
                                    filter.options = data.options;
                                    console.log('  Options reloaded:', filter.options.length, 'options');
                                }
                            }
                        } catch (error) {
                            console.error('Error restoring filter options for', filter.column, ':', error);
                        } finally {
                            filter.loading = false;
                        }
                    } else {
                        console.log('  Options already loaded, skipping');
                        
                        // Debug: Check if select element has correct value
                        const selectElement = document.getElementById('filter_' + filter.column);
                        if (selectElement) {
                            console.log('  Select element value:', selectElement.value);
                            console.log('  Expected value:', currentValue);
                            console.log('  Match:', selectElement.value === currentValue);
                            
                            // Force set value if not matching
                            if (selectElement.value !== currentValue) {
                                console.log('  Forcing select value...');
                                
                                // Don't trigger change event yet (to avoid cascade)
                                // Just set the value directly
                                selectElement.value = currentValue;
                                
                                // Update Alpine.js model without triggering change
                                this.filterValues[filter.column] = currentValue;
                            }
                        }
                    }
                }
            }
            
            console.log('Restore filter options complete');
            this.isRestoring = false; // Unset flag
            
            // After restoring, trigger cascade ONLY from the FIRST filter with value
            // This prevents cascading from downstream filters which would clear upstream values
            console.log('Triggering cascade for restored filters...');
            
            // Find the first filter with a value
            const firstFilterWithValue = this.filters.find(f => this.filterValues[f.column] && f.relate);
            
            if (firstFilterWithValue) {
                console.log('  Cascading from first filter with value:', firstFilterWithValue.column);
                await this.handleFilterChangeBidirectional(firstFilterWithValue, this.filterValues[firstFilterWithValue.column]);
            } else {
                console.log('  No filters with values found');
            }
            
            console.log('Cascade for restored filters complete');
        },
        
        updateActiveCount() {
            this.activeFilterCount = Object.values(this.filterValues)
                .filter(v => v !== '' && v !== null && v !== undefined).length;
            
            // Update badge in filter button (in modal)
            const filterButton = document.querySelector('[dusk="filter-button"]');
            if (filterButton) {
                const badge = filterButton.querySelector('.badge');
                if (badge) {
                    badge.textContent = this.activeFilterCount;
                    if (this.activeFilterCount > 0) {
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
            
            // Update badge in search bar filter button
            const searchBarFilterBtn = document.querySelector('[id$="_filter_btn"]');
            if (searchBarFilterBtn) {
                // Remove existing badge if any
                const existingBadge = searchBarFilterBtn.querySelector('.absolute');
                if (existingBadge) {
                    existingBadge.remove();
                }
                
                // Add new badge if filters are active
                if (this.activeFilterCount > 0) {
                    const badge = document.createElement('span');
                    badge.className = 'absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg';
                    badge.textContent = this.activeFilterCount;
                    searchBarFilterBtn.appendChild(badge);
                }
            }
        },
        
        getFilterLabel(column) {
            const filter = this.filters.find(f => f.column === column);
            return filter ? filter.label : column;
        },
        
        getFilterValueLabel(column, value) {
            const filter = this.filters.find(f => f.column === column);
            if (filter && filter.type === 'selectbox' && filter.options) {
                const option = filter.options.find(o => o.value === value);
                return option ? option.label : value;
            }
            return value;
        },
        
        async removeFilter(column) {
            this.filterValues[column] = '';
            await this.applyFilters();
        },
        
        showFilterActiveToast() {
            // Show toast notification that filters are active
            const filterCount = this.activeFilterCount;
            const filterList = Object.entries(this.filterValues)
                .filter(([_, value]) => value !== '' && value !== null)
                .map(([column, value]) => {
                    const label = this.getFilterLabel(column);
                    const valueLabel = this.getFilterValueLabel(column, value);
                    return `${label}: ${valueLabel}`;
                })
                .join(', ');
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 z-[60] bg-blue-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-start gap-3 max-w-md transform transition-all duration-300 ease-out';
            toast.style.transform = 'translateX(400px)';
            toast.innerHTML = `
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="font-semibold mb-1">
                        ${filterCount} ${filterCount === 1 ? 'Filter' : 'Filters'} Active
                    </div>
                    <div class="text-sm text-blue-100 line-clamp-2">
                        ${filterList}
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" class="flex-shrink-0 hover:bg-blue-600 rounded-lg p-1 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(0)';
            });
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }
}
</script>

<style>
/* Ensure modal is above other elements */
[x-cloak] { 
    display: none !important; 
}

/* Custom scrollbar for modal */
.filter-modal-content::-webkit-scrollbar {
    width: 8px;
}

.filter-modal-content::-webkit-scrollbar-track {
    background: transparent;
}

.filter-modal-content::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.dark .filter-modal-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

/* ===== Task 2.4: Smooth Transitions ===== */

/* Filter select smooth transitions */
.filter-select,
.input {
    transition: opacity 0.2s ease, 
                background-color 0.2s ease, 
                border-color 0.2s ease,
                box-shadow 0.2s ease;
}

/* Loading state - fade and subtle background change */
.filter-loading {
    opacity: 0.6;
    background-color: rgba(0, 0, 0, 0.02);
    cursor: not-allowed;
    pointer-events: none;
}

.dark .filter-loading {
    background-color: rgba(255, 255, 255, 0.05);
}

/* Disabled state - visually clear */
.filter-select:disabled,
.input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: rgba(0, 0, 0, 0.03);
}

.dark .filter-select:disabled,
.dark .input:disabled {
    background-color: rgba(255, 255, 255, 0.03);
}

/* Cascade indicator animation */
@keyframes cascade-pulse {
    0%, 100% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.6;
        transform: scale(1.1);
    }
}

.cascade-indicator {
    animation: cascade-pulse 1.5s ease-in-out infinite;
}

/* Smooth icon transitions */
[data-lucide] {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

/* Loading spinner smooth appearance */
.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Empty state smooth appearance */
.empty-state {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

/* Badge transitions */
.badge {
    transition: all 0.2s ease;
}

/* Button hover transitions */
.btn {
    transition: all 0.2s ease;
}

/* Respect user's motion preferences */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
    
    .cascade-indicator {
        animation: none;
    }
    
    .loading-spinner {
        animation: none;
    }
}

/* Focus states with smooth transitions */
.filter-select:focus,
.input:focus {
    outline: none;
    border-color: hsl(var(--p));
    box-shadow: 0 0 0 3px hsla(var(--p), 0.2);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

/* Smooth color transitions for dark mode */
.filter-select,
.input,
.btn,
.badge {
    transition-property: color, background-color, border-color, opacity, transform, box-shadow;
    transition-duration: 0.2s;
    transition-timing-function: ease;
}
</style>

{{--
    TanStack Table Component
    
    This template renders a modern, high-performance data table using TanStack Table v8 and Alpine.js.
    
    Features:
    - Sorting, pagination, filtering, searching
    - Column pinning (fixed columns)
    - Row selection
    - Responsive design with mobile card view
    - Dark mode support
    - Loading, empty, and error states
    - Theme Engine integration
    - i18n support
    - RTL support
    
    @var array $config - Table configuration
    @var array $columns - Column definitions
    @var array $data - Initial data (for client-side mode)
    @var array $alpineData - Alpine.js state configuration
    @var string $tableId - Unique table identifier
--}}

@php
    $rtlSupport = app('canvastack.rtl');
    $isRtl = $rtlSupport->isRtl();
    $direction = $rtlSupport->getDirection();
@endphp

<div 
    x-data="{
        ...tanstackTable(@js($alpineData)),
        ...responsiveTableView({ breakpoint: 768, debug: {{ config('app.debug') ? 'true' : 'false' }} }),
        ...touchSupport({ 
            swipeEnabled: true, 
            swipeThreshold: 50,
            touchButtonSize: 'btn-md',
            debug: {{ config('app.debug') ? 'true' : 'false' }} 
        })
    }"
    x-init="init()"
    class="tanstack-table-container {{ $isRtl ? 'rtl' : 'ltr' }}"
    data-table-id="{{ $tableId }}"
    dir="{{ $direction }}"
>
    {{-- Table Header: Search, Filters, Actions --}}
    <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        {{-- Left: Search --}}
        <div class="flex-1 max-w-md">
            @if($config['searchable'] ?? true)
            <div class="relative">
                <input
                    type="search"
                    x-model="globalFilter"
                    @input.debounce.300ms="onGlobalFilterChange"
                    :placeholder="translations.search || '{{ __('components.table.search') }}'"
                    :aria-label="translations.search_table || '{{ __('components.table.search_table') }}'"
                    class="w-full px-4 py-2 pl-10 rounded-xl border border-gray-200 dark:border-gray-700 
                           bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                           focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                           transition-all duration-200"
                    style="font-family: @themeFont('sans')"
                    autocomplete="off"
                />
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                
                {{-- Clear Search Button --}}
                <button
                    x-show="globalFilter.length > 0"
                    @click="clearSearch()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    :aria-label="translations.clear_search || '{{ __('components.table.clear_search') }}'"
                    :title="translations.clear_search || '{{ __('components.table.clear_search') }}'"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            @endif
        </div>

        {{-- Right: Filters, Column Visibility, Export, Refresh --}}
        <div class="flex items-center gap-2">
            {{-- Active Sort Indicator --}}
            <div x-show="sorting.length > 0" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                <i data-lucide="arrow-up-down" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <span class="text-sm text-indigo-700 dark:text-indigo-300" style="font-family: @themeFont('sans')">
                    <span x-text="sorting.length"></span>
                    <span x-text="sorting.length === 1 ? (translations.sort_active_singular || '{{ __('components.table.sort_active_singular') }}') : (translations.sort_active_plural || '{{ __('components.table.sort_active_plural') }}')"></span>
                </span>
                <button
                    @click="sorting = []; onSortingChange([])"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200"
                    :title="translations.clear_sorting || '{{ __('components.table.clear_sorting') }}'"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            {{-- Filter Button --}}
            @if(!empty($config['filterGroups']))
            <button
                @click="showFilters = !showFilters"
                class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       flex items-center gap-2"
                style="font-family: @themeFont('sans')"
            >
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span x-text="translations.filters"></span>
                <span 
                    x-show="activeFiltersCount > 0"
                    x-text="activeFiltersCount"
                    class="px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                    style="background: @themeColor('primary')"
                ></span>
            </button>
            @endif

            {{-- Column Visibility --}}
            @if($config['columnVisibility'] ?? true)
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                           hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
                    :aria-label="translations.show_columns"
                >
                    <i data-lucide="columns" class="w-4 h-4"></i>
                </button>
                
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                           rounded-xl shadow-lg z-50 max-h-96 overflow-y-auto"
                >
                    <div class="p-4">
                        <h3 class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100" x-text="translations.show_columns"></h3>
                        <template x-for="column in table.getAllLeafColumns()" :key="column.id">
                            <label class="flex items-center gap-2 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded px-2">
                                <input
                                    type="checkbox"
                                    :checked="column.getIsVisible()"
                                    @change="column.toggleVisibility()"
                                    class="rounded border-gray-300 dark:border-gray-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="column.columnDef.header"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            @endif

            {{-- Export Button --}}
            @if(!empty($config['buttons']))
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                           hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                           flex items-center gap-2"
                    style="font-family: @themeFont('sans')"
                >
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span x-text="translations.export"></span>
                </button>
                
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                           rounded-xl shadow-lg z-50"
                >
                    @foreach($config['buttons'] as $button)
                    <button
                        @click="exportData('{{ $button['type'] }}'); open = false"
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700
                               text-gray-700 dark:text-gray-300 flex items-center gap-2
                               first:rounded-t-xl last:rounded-b-xl"
                    >
                        <i data-lucide="{{ $button['icon'] ?? 'file' }}" class="w-4 h-4"></i>
                        <span>{{ $button['label'] }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Refresh Button --}}
            <button
                @click="refreshData"
                :disabled="loading"
                class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed"
                :aria-label="translations.refresh"
            >
                <i data-lucide="refresh-cw" class="w-4 h-4" :class="{ 'animate-spin': loading }"></i>
            </button>
        </div>
    </div>

    {{-- Filter Modal --}}
    @if(!empty($config['filterGroups']))
    <div
        x-show="showFilters"
        x-transition
        class="mb-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="translations.filters"></h3>
            <button
                @click="clearFilters"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                x-text="translations.clear_filters"
            ></button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($config['filterGroups'] as $filter)
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                    {{ $filter['label'] }}
                </label>
                @if($filter['type'] === 'select')
                <select
                    x-model="filters.{{ $filter['name'] }}"
                    @change="onFilterChange"
                    class="w-full px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                >
                    <option value="">{{ $filter['placeholder'] ?? __('components.table.filter_by') }}</option>
                    @foreach($filter['options'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @elseif($filter['type'] === 'daterange')
                <input
                    type="text"
                    x-model="filters.{{ $filter['name'] }}"
                    @change="onFilterChange"
                    class="w-full px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                    placeholder="{{ $filter['placeholder'] ?? __('components.table.select_date_range') }}"
                />
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Table Container --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        {{-- Loading State --}}
        <div x-show="loading" class="p-8">
            <div class="flex flex-col items-center justify-center gap-4">
                <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-primary rounded-full animate-spin"></div>
                <p class="text-gray-600 dark:text-gray-400" x-text="translations.loading"></p>
            </div>
        </div>

        {{-- Error State --}}
        <div x-show="error && !loading" class="p-8">
            <div class="flex flex-col items-center justify-center gap-4">
                <i data-lucide="alert-circle" class="w-12 h-12 text-red-500"></i>
                <p class="text-gray-900 dark:text-gray-100 font-semibold" x-text="translations.error"></p>
                <p class="text-gray-600 dark:text-gray-400" x-text="errorMessage"></p>
                <button
                    @click="refreshData"
                    class="px-4 py-2 rounded-xl text-white"
                    style="background: @themeColor('primary'); font-family: @themeFont('sans')"
                    x-text="translations.retry"
                ></button>
            </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && !error && table.getRowModel().rows.length === 0" class="p-8">
            <div class="flex flex-col items-center justify-center gap-4">
                <i data-lucide="inbox" class="w-12 h-12 text-gray-400"></i>
                <p class="text-gray-900 dark:text-gray-100 font-semibold" x-text="translations.empty_title"></p>
                <p class="text-gray-600 dark:text-gray-400" x-text="translations.empty_description"></p>
            </div>
        </div>

        {{-- Desktop Table View (automatically shown/hidden based on screen size) --}}
        <div 
            x-show="!loading && !error && table.getRowModel().rows.length > 0 && isTableView()" 
            x-ref="scrollContainer"
            @scroll="onVirtualScroll"
            class="overflow-auto"
            :style="isVirtualScrollingEnabled() ? { height: (config.virtualScrolling?.height || '600px') } : {}"
        >
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <template x-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <tr>
                            <template x-for="header in headerGroup.headers" :key="header.id">
                                <th
                                    :class="getHeaderClass(header)"
                                    :style="{ width: header.getSize() + 'px', position: 'relative' }"
                                    class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    style="font-family: @themeFont('sans')"
                                >
                                    <div
                                        :class="{ 'cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-700 rounded px-2 py-1 -mx-2 -my-1 transition-colors': header.column.getCanSort() }"
                                        @click="header.column.getCanSort() && header.column.toggleSorting($event.shiftKey)"
                                        class="flex items-center gap-2"
                                        :title="header.column.getCanSort() ? (translations.sort_hint || '{{ __('components.table.sort_hint') }}') : ''"
                                    >
                                        <span x-html="header.column.columnDef.header"></span>
                                        <template x-if="header.column.getCanSort()">
                                            <div class="flex items-center gap-1">
                                                <i
                                                    :data-lucide="getSortIcon(header.column.getIsSorted())"
                                                    :class="getSortIconClass(header.column.getIsSorted())"
                                                    class="w-4 h-4 transition-colors"
                                                ></i>
                                                {{-- Multi-sort index indicator --}}
                                                <template x-if="header.column.getIsSorted() && header.column.getSortIndex() >= 0 && sorting.length > 1">
                                                    <span 
                                                        class="text-xs font-bold px-1.5 py-0.5 rounded-full"
                                                        style="background: @themeColor('primary'); color: white;"
                                                        x-text="header.column.getSortIndex() + 1"
                                                    ></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    {{-- Column Resize Handle --}}
                                    <template x-if="header.column.getCanResize()">
                                        <div
                                            @mousedown="header.getResizeHandler()($event)"
                                            @touchstart="header.getResizeHandler()($event)"
                                            @dblclick="header.column.resetSize()"
                                            :class="{
                                                'bg-primary': header.column.getIsResizing(),
                                                'bg-gray-300 dark:bg-gray-600': !header.column.getIsResizing()
                                            }"
                                            class="absolute top-0 right-0 w-1 h-full cursor-col-resize hover:bg-primary transition-colors"
                                            :title="translations.resize_column || '{{ __('components.table.resize_column') }}'"
                                            style="touch-action: none; user-select: none;"
                                        ></div>
                                    </template>
                                </th>
                            </template>
                        </tr>
                    </template>
                </thead>
                <tbody 
                    class="divide-y divide-gray-200 dark:divide-gray-700"
                    :style="isVirtualScrollingEnabled() ? {
                        height: getTotalVirtualSize() + 'px',
                        position: 'relative'
                    } : {}"
                >
                    <template x-for="row in getVisibleRows()" :key="row.id">
                        <tr
                            :style="isVirtualScrollingEnabled() ? {
                                position: 'absolute',
                                top: '0',
                                left: '0',
                                width: '100%',
                                transform: `translateY(${getRowOffset(row)}px)`
                            } : {}"
                            :class="getRowClass(row)"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150"
                        >
                            <template x-for="cell in row.getVisibleCells()" :key="cell.id">
                                <td
                                    :class="getCellClass(cell)"
                                    class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                                    style="font-family: @themeFont('sans')"
                                    x-html="renderCell(cell)"
                                ></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View (automatically shown/hidden based on screen size) --}}
        <div x-show="!loading && !error && table.getRowModel().rows.length > 0 && isCardView()" class="divide-y divide-gray-200 dark:divide-gray-700">
            <template x-for="row in table.getRowModel().rows" :key="row.id">
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                    <template x-for="cell in row.getVisibleCells()" :key="cell.id">
                        <div class="mb-3 last:mb-0">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1" x-text="cell.column.columnDef.header"></div>
                            <div class="text-sm text-gray-900 dark:text-gray-100" x-html="renderCell(cell)"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Pagination --}}
    <div x-show="!loading && !error && table.getRowModel().rows.length > 0" class="mt-4 flex flex-col md:flex-row items-center justify-between gap-4">
        {{-- Pagination Info --}}
        <div class="text-sm text-gray-600 dark:text-gray-400" style="font-family: @themeFont('sans')">
            <span x-text="paginationText()"></span>
        </div>

        {{-- Pagination Controls --}}
        <div class="flex items-center gap-2">
            {{-- First Page --}}
            <button
                @click="table.setPageIndex(0)"
                :disabled="!table.getCanPreviousPage()"
                class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed"
                :aria-label="translations.first"
            >
                <i data-lucide="chevrons-left" class="w-4 h-4"></i>
            </button>

            {{-- Previous Page --}}
            <button
                @click="table.previousPage()"
                :disabled="!table.getCanPreviousPage()"
                class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed"
                :aria-label="translations.previous"
            >
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>

            {{-- Page Numbers --}}
            <div class="flex items-center gap-1">
                <template x-for="page in getPageNumbers()" :key="page">
                    <button
                        @click="typeof page === 'number' && table.setPageIndex(page - 1)"
                        :disabled="typeof page !== 'number'"
                        :class="{
                            'bg-primary text-white': page === table.getState().pagination.pageIndex + 1,
                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': page !== table.getState().pagination.pageIndex + 1
                        }"
                        class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                               disabled:cursor-default disabled:hover:bg-transparent"
                        style="font-family: @themeFont('sans')"
                        x-text="page"
                    ></button>
                </template>
            </div>

            {{-- Next Page --}}
            <button
                @click="table.nextPage()"
                :disabled="!table.getCanNextPage()"
                class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed"
                :aria-label="translations.next"
            >
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>

            {{-- Last Page --}}
            <button
                @click="table.setPageIndex(table.getPageCount() - 1)"
                :disabled="!table.getCanNextPage()"
                class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed"
                :aria-label="translations.last"
            >
                <i data-lucide="chevrons-right" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Page Size Selector --}}
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400" x-text="translations.page_size"></label>
            <select
                :value="table.getState().pagination.pageSize"
                @change="changePageSize($event.target.value)"
                class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                style="font-family: @themeFont('sans')"
            >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
    
    {{-- Lazy Loading Indicator --}}
    <div 
        x-show="isLazyLoadingEnabled() && isLoadingMore()" 
        x-transition
        class="mt-4 flex items-center justify-center py-4"
    >
        <div class="flex items-center gap-3 px-6 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="animate-spin rounded-full h-5 w-5 border-2 border-gray-300 border-t-primary"></div>
            <span class="text-sm text-gray-600 dark:text-gray-400" style="font-family: @themeFont('sans')">
                {{ __('components.table.loading_more') }}
            </span>
        </div>
    </div>
    
    {{-- No More Data Indicator --}}
    <div 
        x-show="isLazyLoadingEnabled() && !hasMoreData() && data.length > 0" 
        x-transition
        class="mt-4 flex items-center justify-center py-4"
    >
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
            <span class="text-sm text-gray-600 dark:text-gray-400" style="font-family: @themeFont('sans')">
                {{ __('components.table.all_data_loaded') }}
            </span>
        </div>
    </div>
</div>

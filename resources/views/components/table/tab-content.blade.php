{{--
    Tab Content Component for TableBuilder
    
    This component renders the content area for tabs, including custom HTML content
    and table instances. It handles responsive design and proper spacing.
    
    @props array $tab - Tab data object containing content and tables
    @props bool $isActive - Whether this tab is currently active
    @props string $tableId - Unique identifier for the parent table
--}}

@props(['tab' => [], 'isActive' => false, 'tableId' => 'table'])

<div 
    id="tabpanel-{{ $tab['id'] ?? 'unknown' }}"
    class="tab-content-panel {{ $isActive ? 'active' : '' }}"
    role="tabpanel"
    aria-labelledby="tab-{{ $tab['id'] ?? 'unknown' }}"
    :aria-hidden="!{{ $isActive ? 'true' : 'false' }}"
    tabindex="0"
>
    {{-- Custom Content Section --}}
    @if(!empty($tab['content']) && is_array($tab['content']))
        <div class="tab-custom-content mb-6">
            @foreach($tab['content'] as $index => $contentBlock)
                <div class="content-block mb-4 last:mb-0">
                    {!! $contentBlock !!}
                </div>
            @endforeach
        </div>
    @endif
    
    {{-- Tables Section --}}
    @if(!empty($tab['tables']) && is_array($tab['tables']))
        <div class="tab-tables-container space-y-6">
            @foreach($tab['tables'] as $index => $table)
                <div class="table-wrapper">
                    {{-- Table Container --}}
                    <div 
                        id="{{ $table['id'] ?? 'table-' . $index }}"
                        class="table-instance-container bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                        data-table-name="{{ $table['tableName'] ?? '' }}"
                        data-table-index="{{ $index }}"
                    >
                        {{-- Table Content --}}
                        <div class="table-content p-4 sm:p-6">
                            @if(!empty($table['html']))
                                {!! $table['html'] !!}
                            @else
                                {{-- Render table placeholder if no HTML provided --}}
                                <div class="table-placeholder">
                                    <div class="flex items-center justify-center py-12">
                                        <div class="text-center">
                                            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 dark:border-primary-400 mb-4"></div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Loading table data...</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    
    {{-- Empty State --}}
    @if(empty($tab['content']) && empty($tab['tables']))
        <div class="empty-state py-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Content</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">This tab doesn't have any content or tables yet.</p>
        </div>
    @endif
</div>

<style>
/* Tab Content Panel Styles */
.tab-content-panel {
    min-height: 200px;
}

/* Responsive Table Container */
.table-instance-container {
    transition: all 0.2s ease-in-out;
}

.table-instance-container:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Dark mode hover effect */
.dark .table-instance-container:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
}

/* Responsive spacing */
@media (max-width: 640px) {
    .table-content {
        padding: 1rem !important;
    }
    
    .tab-custom-content {
        margin-bottom: 1rem;
    }
    
    .tab-tables-container {
        gap: 1rem;
    }
}

/* Content block styling */
.content-block {
    line-height: 1.6;
}

.content-block > *:first-child {
    margin-top: 0;
}

.content-block > *:last-child {
    margin-bottom: 0;
}

/* Loading animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Smooth transitions */
.table-wrapper {
    transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
}

/* Focus styles for accessibility */
.tab-content-panel:focus {
    outline: 2px solid rgb(99 102 241);
    outline-offset: 2px;
}

.dark .tab-content-panel:focus {
    outline-color: rgb(129 140 248);
}
</style>

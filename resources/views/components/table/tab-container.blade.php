{{--
    Tab Container Component for TableBuilder
    
    This is the main container component that combines tab navigation and content rendering.
    It provides a complete tabbed interface with responsive design and accessibility features.
    
    @props array $tabs - Array of tab data from TabManager
    @props string $activeTab - ID of the currently active tab
    @props string $tableId - Unique identifier for this table instance
    @props bool $responsive - Enable responsive design (default: true)
--}}

@props([
    'tabs' => [], 
    'activeTab' => null, 
    'tableId' => 'table',
    'responsive' => true
])

<div 
    class="table-tab-container {{ $responsive ? 'responsive' : '' }}"
    data-table-id="{{ $tableId }}"
    x-data="tableTabContainer_{{ $tableId }}()"
    x-init="init()"
>
    {{-- Tab Navigation --}}
    <div class="tab-navigation-wrapper">
        <x-canvastack::table.tab-navigation 
            :tabs="$tabs"
            :activeTab="$activeTab"
            :tableId="$tableId"
        />
    </div>
    
    {{-- Tab Content Area --}}
    <div class="tab-content-wrapper mt-6">
        @foreach($tabs as $index => $tab)
            <div 
                x-show="activeTab === '{{ $tab['id'] }}'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="tab-content-item"
            >
                <x-canvastack::table.tab-content 
                    :tab="$tab"
                    :isActive="$tab['id'] === $activeTab"
                    :tableId="$tableId"
                />
            </div>
        @endforeach
    </div>
</div>

<script>
/**
 * Alpine.js component for tab container
 * 
 * This component manages the overall tab state and coordinates between
 * navigation and content rendering.
 */
function tableTabContainer_{{ $tableId }}() {
    return {
        tabs: @json($tabs),
        activeTab: @json($activeTab),
        tableId: '{{ $tableId }}',
        
        /**
         * Initialize the component
         */
        init() {
            // Set default active tab if none specified
            if (!this.activeTab && this.tabs.length > 0) {
                this.activeTab = this.tabs[0].id;
            }
            
            // Listen for tab change events
            this.$watch('activeTab', (newTab, oldTab) => {
                this.onTabChange(newTab, oldTab);
            });
            
            // Listen for custom tab-changed events from navigation
            window.addEventListener('tab-changed', (event) => {
                if (event.detail.tableId === this.tableId) {
                    this.handleTabChanged(event.detail);
                }
            });
        },
        
        /**
         * Handle tab change
         */
        onTabChange(newTab, oldTab) {
            // Emit custom event for external listeners
            this.$dispatch('table-tab-changed', {
                tableId: this.tableId,
                newTab: newTab,
                oldTab: oldTab,
                timestamp: Date.now()
            });
            
            // Trigger any DataTables redraws if needed
            this.$nextTick(() => {
                this.refreshDataTables(newTab);
            });
        },
        
        /**
         * Handle tab-changed event from navigation
         */
        handleTabChanged(detail) {
            // Additional handling if needed
            console.log('Tab changed:', detail);
        },
        
        /**
         * Refresh DataTables in the active tab
         */
        refreshDataTables(tabId) {
            try {
                // Find all DataTable instances in the active tab
                const tabPanel = document.getElementById(`tabpanel-${tabId}`);
                if (tabPanel) {
                    const tables = tabPanel.querySelectorAll('table.dataTable');
                    tables.forEach(table => {
                        // Check if DataTable is initialized
                        if ($.fn.DataTable.isDataTable(table)) {
                            $(table).DataTable().columns.adjust().draw();
                        }
                    });
                }
            } catch (e) {
                console.warn('Failed to refresh DataTables:', e);
            }
        }
    };
}
</script>

<style>
/* Tab Container Styles */
.table-tab-container {
    width: 100%;
    margin: 0 auto;
}

/* Responsive container */
.table-tab-container.responsive {
    max-width: 100%;
    overflow-x: hidden;
}

/* Tab navigation wrapper */
.tab-navigation-wrapper {
    position: relative;
    z-index: 10;
}

/* Tab content wrapper */
.tab-content-wrapper {
    position: relative;
    min-height: 300px;
}

/* Tab content item */
.tab-content-item {
    width: 100%;
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .table-tab-container {
        padding: 0;
    }
    
    .tab-content-wrapper {
        margin-top: 1rem;
        min-height: 200px;
    }
}

/* Tablet and up */
@media (min-width: 769px) {
    .table-tab-container {
        padding: 0 1rem;
    }
}

/* Desktop and up */
@media (min-width: 1024px) {
    .table-tab-container {
        padding: 0 2rem;
    }
}

/* Print styles */
@media print {
    .tab-navigation-wrapper {
        display: none;
    }
    
    .tab-content-item {
        display: block !important;
        opacity: 1 !important;
        transform: none !important;
    }
}

/* Accessibility: Reduce motion */
@media (prefers-reduced-motion: reduce) {
    .tab-content-item {
        transition: none !important;
    }
}
</style>

{{--
    Tab Navigation Component for TableBuilder
    
    This component renders the tab navigation UI using Alpine.js for interactivity.
    It supports URL parameter sync, smooth transitions, and responsive design.
    
    @props array $tabs - Array of tab data from TabManager
    @props string $activeTab - ID of the currently active tab
    @props string $tableId - Unique identifier for this table instance
--}}

@props(['tabs' => [], 'activeTab' => null, 'tableId' => 'table'])

<div x-data="tableTabs_{{ $tableId }}()" 
     x-init="init()"
     class="w-full"
     data-table-id="{{ $tableId }}">
    
    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600" 
             aria-label="Tabs"
             role="tablist">
            <template x-for="(tab, index) in tabs" :key="tab.id">
                <button
                    @click="switchTab(tab.id)"
                    :id="'tab-' + tab.id"
                    :aria-selected="activeTab === tab.id"
                    :aria-controls="'tabpanel-' + tab.id"
                    role="tab"
                    :tabindex="activeTab === tab.id ? 0 : -1"
                    :class="{
                        'border-primary-500 text-primary-600 dark:text-primary-400': activeTab === tab.id,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300': activeTab !== tab.id
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-t-lg"
                    x-text="tab.name"
                ></button>
            </template>
        </nav>
    </div>
    
    {{-- Tab Content Panels --}}
    <div class="tab-content">
        <template x-for="tab in tabs" :key="tab.id">
            <div 
                x-show="activeTab === tab.id"
                :id="'tabpanel-' + tab.id"
                :aria-labelledby="'tab-' + tab.id"
                role="tabpanel"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform translate-y-2"
                class="focus:outline-none"
                tabindex="0"
            >
                {{-- Custom Content --}}
                <div x-show="tab.content && tab.content.length > 0" class="mb-4">
                    <template x-for="(content, idx) in tab.content" :key="idx">
                        <div x-html="content" class="mb-2"></div>
                    </template>
                </div>
                
                {{-- Tables --}}
                <template x-for="(table, idx) in tab.tables" :key="table.id">
                    <div class="mb-6 last:mb-0">
                        <div x-html="table.html"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

<script>
/**
 * Alpine.js component for tab navigation
 * 
 * Features:
 * - Tab switching with smooth transitions
 * - URL parameter sync (preserves tab state in URL)
 * - Session storage persistence
 * - Keyboard navigation (Arrow keys, Home, End)
 * - Accessibility (ARIA attributes, focus management)
 */
function tableTabs_{{ $tableId }}() {
    return {
        tabs: @json($tabs),
        activeTab: @json($activeTab),
        tableId: '{{ $tableId }}',
        
        /**
         * Initialize the component
         */
        init() {
            // Restore active tab from URL parameter
            this.restoreTabFromUrl();
            
            // Restore active tab from session storage
            this.restoreTabFromSession();
            
            // Watch for tab changes and update URL + session
            this.$watch('activeTab', (value) => {
                this.updateUrl(value);
                this.saveToSession(value);
                this.updateFocus(value);
            });
            
            // Setup keyboard navigation
            this.setupKeyboardNavigation();
            
            // Emit custom event when tab changes
            this.$watch('activeTab', (value, oldValue) => {
                if (value !== oldValue) {
                    this.$dispatch('tab-changed', { 
                        tableId: this.tableId,
                        tabId: value, 
                        oldTabId: oldValue 
                    });
                }
            });
        },
        
        /**
         * Switch to a specific tab
         */
        switchTab(tabId) {
            if (this.tabs.find(t => t.id === tabId)) {
                this.activeTab = tabId;
            }
        },
        
        /**
         * Restore active tab from URL parameter
         */
        restoreTabFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam && this.tabs.find(t => t.id === tabParam)) {
                this.activeTab = tabParam;
            }
        },
        
        /**
         * Restore active tab from session storage
         */
        restoreTabFromSession() {
            try {
                const sessionKey = `table_tab_${this.tableId}_${window.location.pathname}`;
                const savedTab = sessionStorage.getItem(sessionKey);
                
                if (savedTab && this.tabs.find(t => t.id === savedTab)) {
                    // Only restore from session if URL doesn't have a tab parameter
                    const urlParams = new URLSearchParams(window.location.search);
                    if (!urlParams.has('tab')) {
                        this.activeTab = savedTab;
                    }
                }
            } catch (e) {
                console.warn('Failed to restore tab from session:', e);
            }
        },
        
        /**
         * Update URL with current tab
         */
        updateUrl(tabId) {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            } catch (e) {
                console.warn('Failed to update URL:', e);
            }
        },
        
        /**
         * Save active tab to session storage
         */
        saveToSession(tabId) {
            try {
                const sessionKey = `table_tab_${this.tableId}_${window.location.pathname}`;
                sessionStorage.setItem(sessionKey, tabId);
            } catch (e) {
                console.warn('Failed to save tab to session:', e);
            }
        },
        
        /**
         * Update focus to active tab button
         */
        updateFocus(tabId) {
            this.$nextTick(() => {
                const tabButton = document.getElementById(`tab-${tabId}`);
                if (tabButton) {
                    tabButton.focus();
                }
            });
        },
        
        /**
         * Setup keyboard navigation for tabs
         */
        setupKeyboardNavigation() {
            this.$el.addEventListener('keydown', (e) => {
                const currentIndex = this.tabs.findIndex(t => t.id === this.activeTab);
                
                switch (e.key) {
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        this.navigateToPreviousTab(currentIndex);
                        break;
                        
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        this.navigateToNextTab(currentIndex);
                        break;
                        
                    case 'Home':
                        e.preventDefault();
                        this.switchTab(this.tabs[0].id);
                        break;
                        
                    case 'End':
                        e.preventDefault();
                        this.switchTab(this.tabs[this.tabs.length - 1].id);
                        break;
                }
            });
        },
        
        /**
         * Navigate to previous tab
         */
        navigateToPreviousTab(currentIndex) {
            if (currentIndex > 0) {
                this.switchTab(this.tabs[currentIndex - 1].id);
            } else {
                // Wrap to last tab
                this.switchTab(this.tabs[this.tabs.length - 1].id);
            }
        },
        
        /**
         * Navigate to next tab
         */
        navigateToNextTab(currentIndex) {
            if (currentIndex < this.tabs.length - 1) {
                this.switchTab(this.tabs[currentIndex + 1].id);
            } else {
                // Wrap to first tab
                this.switchTab(this.tabs[0].id);
            }
        }
    };
}
</script>

<style>
/* Custom scrollbar for tab navigation */
.scrollbar-thin::-webkit-scrollbar {
    height: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background-color: rgb(209 213 219);
    border-radius: 2px;
}

.dark .scrollbar-thumb-gray-600::-webkit-scrollbar-thumb {
    background-color: rgb(75 85 99);
}

/* Smooth transitions for tab content */
.tab-content > div {
    will-change: opacity, transform;
}
</style>

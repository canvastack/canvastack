{{--
    TanStack Table Tab System View
    
    This view renders the tab navigation and tab content panels for multi-table tab system.
    
    Props:
    - $tabs: Array of tab configurations
    - $activeTab: Index of the active tab (default: 0)
    - $lazyLoad: Boolean indicating if lazy loading is enabled
    - $tableId: Unique table identifier
--}}

<div class="tanstack-tabs-container" data-table-id="{{ $tableId }}">
    {{-- Tab Navigation --}}
    <div class="tabs tabs-boxed bg-base-200 dark:bg-gray-800 mb-4" role="tablist">
        @foreach($tabs as $index => $tab)
            <button 
                class="tab {{ $index === $activeTab ? 'tab-active' : '' }}"
                role="tab"
                data-tab-index="{{ $index }}"
                data-tab-id="{{ $tab['id'] ?? 'tab-' . $index }}"
                @if($index !== $activeTab && isset($tab['lazy_load']) && $tab['lazy_load'] && isset($tab['url']))
                    data-lazy-url="{{ $tab['url'] }}"
                @endif
                onclick="switchTab(event, {{ $index }}, '{{ $tableId }}')"
            >
                {{ $tab['name'] ?? 'Tab ' . ($index + 1) }}
            </button>
        @endforeach
    </div>

    {{-- Tab Content Panels --}}
    <div class="tab-content-container">
        @foreach($tabs as $index => $tab)
            <div 
                class="tab-content-panel {{ $index === $activeTab ? 'active' : 'hidden' }}"
                data-tab-index="{{ $index }}"
                data-tab-id="{{ $tab['id'] ?? 'tab-' . $index }}"
            >
                {{-- Custom Content (if any) --}}
                @if(isset($tab['custom_content']) && !empty($tab['custom_content']))
                    <div class="tab-custom-content mb-4">
                        {!! $tab['custom_content'] !!}
                    </div>
                @endif

                {{-- Tables in this tab --}}
                @if(isset($tab['tables']) && !empty($tab['tables']))
                    @foreach($tab['tables'] as $tableIndex => $tableConfig)
                        <div class="table-wrapper mb-6">
                            @if(isset($tableConfig['html']) && !empty($tableConfig['html']))
                                {{-- Pre-rendered HTML (for first tab or already loaded tabs) --}}
                                {!! $tableConfig['html'] !!}
                            @elseif(isset($tab['lazy_load']) && $tab['lazy_load'])
                                {{-- Lazy load placeholder --}}
                                <div class="lazy-load-placeholder">
                                    <div class="flex items-center justify-center py-12">
                                        <div class="loading loading-spinner loading-lg"></div>
                                        <span class="ml-4 text-gray-600 dark:text-gray-400">
                                            Loading table data...
                                        </span>
                                    </div>
                                </div>
                            @else
                                {{-- No HTML and not lazy load - show error --}}
                                <div class="alert alert-error">
                                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                    <span>Table configuration error: No HTML content available</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    {{-- No tables in this tab --}}
                    <div class="alert alert-info">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <span>No tables configured for this tab</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- Tab Switching JavaScript --}}
<script>
// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide && window.lucide.createIcons) {
        try {
            window.lucide.createIcons({
                icons: window.lucide.icons || {},
                nameAttr: 'data-lucide',
                attrs: {
                    class: 'lucide-icon'
                }
            });
            console.log('Lucide icons initialized on page load');
        } catch (error) {
            console.warn('Failed to initialize Lucide icons on page load:', error);
        }
    }
});

function switchTab(event, tabIndex, tableId) {
    event.preventDefault();
    
    const container = document.querySelector(`[data-table-id="${tableId}"]`);
    if (!container) {
        console.error('Tab container not found:', tableId);
        return;
    }
    
    // Update tab buttons
    const tabs = container.querySelectorAll('.tab');
    tabs.forEach((tab, index) => {
        if (index === tabIndex) {
            tab.classList.add('tab-active');
        } else {
            tab.classList.remove('tab-active');
        }
    });
    
    // Update tab content panels
    const panels = container.querySelectorAll('.tab-content-panel');
    panels.forEach((panel, index) => {
        if (index === tabIndex) {
            panel.classList.remove('hidden');
            panel.classList.add('active');
            
            // Initialize Lucide icons in the newly visible tab
            if (window.lucide && window.lucide.createIcons) {
                try {
                    window.lucide.createIcons({
                        icons: window.lucide.icons || {},
                        nameAttr: 'data-lucide',
                        attrs: {
                            class: 'lucide-icon'
                        }
                    });
                } catch (error) {
                    console.warn('Failed to initialize Lucide icons:', error);
                }
            }
            
            // Check if this tab needs lazy loading
            const tabButton = tabs[index];
            const lazyUrl = tabButton.dataset.lazyUrl;
            
            if (lazyUrl && panel.querySelector('.lazy-load-placeholder')) {
                loadTabContent(panel, lazyUrl, tabButton);
            }
        } else {
            panel.classList.add('hidden');
            panel.classList.remove('active');
        }
    });
}

function loadTabContent(panel, url, tabButton) {
    // Show loading state
    const placeholder = panel.querySelector('.lazy-load-placeholder');
    if (!placeholder) return;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found');
        placeholder.innerHTML = `
            <div class="alert alert-error">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span>Security token not found. Please refresh the page.</span>
            </div>
        `;
        return;
    }
    
    // Get tab index from button
    const tabIndex = parseInt(tabButton.dataset.tabIndex);
    
    // Get table ID from container
    const container = panel.closest('.tanstack-tabs-container');
    const tableId = container?.dataset.tableId;
    
    if (!tableId) {
        console.error('Table ID not found');
        placeholder.innerHTML = `
            <div class="alert alert-error">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span>Table configuration error. Please refresh the page.</span>
            </div>
        `;
        return;
    }
    
    // Fetch tab content via AJAX (POST request)
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            tab_index: tabIndex,
            table_id: tableId,
        }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.html) {
            // Replace placeholder with actual content
            placeholder.outerHTML = data.html;
            
            // Remove lazy-url attribute to prevent reloading
            delete tabButton.dataset.lazyUrl;
            
            // Execute initialization scripts if provided
            if (data.scripts) {
                const scriptContainer = document.createElement('div');
                scriptContainer.innerHTML = data.scripts;
                const scripts = scriptContainer.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    document.body.appendChild(newScript);
                });
            }
            
            // Initialize any JavaScript components in the loaded content
            if (window.lucide) {
                lucide.createIcons();
            }
            
            console.log('Tab content loaded successfully:', tabIndex);
        } else {
            throw new Error(data.error || 'No HTML content in response');
        }
    })
    .catch(error => {
        console.error('Failed to load tab content:', error);
        placeholder.innerHTML = `
            <div class="alert alert-error">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span>Failed to load tab content: ${error.message}</span>
            </div>
        `;
        
        if (window.lucide) {
            lucide.createIcons();
        }
    });
}
</script>

<style>
.tanstack-tabs-container {
    width: 100%;
}

/* Tab content panels - use active/hidden classes only */
.tab-content-panel.active {
    display: block;
}

.tab-content-panel.hidden {
    display: none !important;
}

.lazy-load-placeholder {
    min-height: 200px;
}
</style>

{{--
    Display Limit Component
    Provides a dropdown to change the number of rows displayed in a table
--}}

<div x-data="displayLimit()" class="flex items-center gap-2">
    @if($showLabel)
        <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('ui.table.show') }}:</label>
    @endif
    
    <select x-model="currentLimit" 
            @change="changeLimit($event.target.value)"
            class="{{ $getSelectClasses() }}">
        <template x-for="option in options" :key="option.value">
            <option :value="option.value" 
                    :selected="option.value == currentLimit"
                    x-text="option.label"></option>
        </template>
    </select>
    
    @if($showLabel)
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('ui.table.entries') }}</span>
    @endif
    
    <!-- Loading indicator -->
    <div x-show="loading" class="ml-2">
        <span class="loading loading-spinner loading-sm"></span>
    </div>
</div>

<script>
function displayLimit() {
    return {
        currentLimit: @json($getCurrentLimitForJs()),
        tableName: @json($getTableName()),
        options: @json($getOptionsForJs()),
        loading: false,
        
        init() {
            // Initialize current limit from session or default
            this.currentLimit = this.getCurrentLimitFromSession() || this.currentLimit;
        },
        
        async changeLimit(limit) {
            if (this.loading) return;
            
            this.loading = true;
            
            try {
                // Save to session
                await this.saveToSession(limit);
                
                // Update DataTable if available
                this.updateDataTable(limit);
                
                // Update current limit
                this.currentLimit = limit;
                
                // Dispatch event for other components
                this.$dispatch('display-limit-changed', { limit: limit });
                
            } catch (error) {
                console.error('Error changing display limit:', error);
                
                // Show error message
                this.$dispatch('show-toast', {
                    type: 'error',
                    message: '{{ __("ui.messages.error_changing_limit") }}'
                });
                
                // Revert to previous value
                this.currentLimit = this.getPreviousLimit();
                
            } finally {
                this.loading = false;
            }
        },
        
        async saveToSession(limit) {
            const response = await fetch('/datatable/save-display-limit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    table: this.tableName,
                    limit: limit
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Failed to save display limit');
            }
            
            return response.json();
        },
        
        updateDataTable(limit) {
            // Method 1: Update DataTables via global table instance (most reliable)
            if (window.dataTable && typeof window.dataTable.updateDisplayLimit === 'function') {
                window.dataTable.updateDisplayLimit(limit);
                return;
            }
            
            // Method 2: Update DataTables via table-specific instance
            const tableInstanceName = `dataTable_${this.tableName}`;
            if (window[tableInstanceName] && typeof window[tableInstanceName].updateDisplayLimit === 'function') {
                window[tableInstanceName].updateDisplayLimit(limit);
                return;
            }
            
            // Method 3: Update DataTables via jQuery API (fallback)
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                try {
                    const table = $('.datatable').DataTable();
                    if (table && typeof table.page === 'object') {
                        const pageLength = (limit === 'all' || limit === '*') ? -1 : parseInt(limit);
                        table.page.len(pageLength).draw();
                        return;
                    }
                } catch (error) {
                    console.warn('Failed to update DataTable via jQuery:', error);
                }
            }
            
            // Method 4: Update DataTables via direct API access (alternative fallback)
            if (window.dataTable && typeof window.dataTable.page === 'object') {
                const pageLength = (limit === 'all' || limit === '*') ? -1 : parseInt(limit);
                window.dataTable.page.len(pageLength).draw();
                return;
            }
            
            // Method 5: Fallback - reload page with limit parameter
            console.warn('No DataTable instance found, falling back to page reload');
            const url = new URL(window.location);
            url.searchParams.set('limit', limit);
            window.location.href = url.toString();
        },
        
        getCurrentLimitFromSession() {
            // Try to get from sessionStorage first
            const sessionKey = `table_display_limit_${this.tableName}`;
            return sessionStorage.getItem(sessionKey);
        },
        
        getPreviousLimit() {
            // Return the first option as fallback
            return this.options[0]?.value || '10';
        }
    }
}
</script>
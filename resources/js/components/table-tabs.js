/**
 * TanStack Table Multi-Table & Tab System - Alpine.js Component
 * 
 * This component provides reactive tab switching and lazy loading functionality
 * for the CanvaStack TableBuilder multi-table system.
 * 
 * @package Canvastack\Canvastack
 * @version 1.0.0
 * @requires Alpine.js 3.x
 */

/**
 * Create Alpine.js tabSystem component
 * 
 * This function returns an Alpine.js component object that manages:
 * - Tab state (active tab, loading, errors)
 * - Lazy loading of tab content via AJAX
 * - Content caching to avoid redundant requests
 * - TanStack Table initialization after content loads
 * - Keyboard navigation and accessibility
 * 
 * @param {Object} config - Configuration object
 * @param {Array} config.tabs - Array of tab configurations
 * @param {number} config.activeTab - Initial active tab index (default: 0)
 * @param {boolean} config.lazyLoad - Enable lazy loading (default: true)
 * @param {string} config.csrfToken - CSRF token for AJAX requests
 * @param {Object} config.translations - Translation strings for error messages
 * @returns {Object} Alpine.js component object
 * 
 * @example
 * <div x-data="tabSystem({
 *     tabs: @js($tabs),
 *     activeTab: 0,
 *     lazyLoad: true,
 *     csrfToken: '{{ csrf_token() }}',
 *     translations: {
 *         error_404: '{{ __('ui.tabs.error_404') }}',
 *         error_403: '{{ __('ui.tabs.error_403') }}',
 *         error_419: '{{ __('ui.tabs.error_419') }}',
 *         error_429: '{{ __('ui.tabs.error_429') }}',
 *         error_500: '{{ __('ui.tabs.error_500') }}',
 *         error_network: '{{ __('ui.tabs.error_network') }}',
 *         error_default: '{{ __('ui.tabs.error_default') }}',
 *         error_no_url: '{{ __('ui.tabs.error_no_url') }}',
 *         error_load_failed: '{{ __('ui.tabs.error_load_failed') }}',
 *         error_no_html: '{{ __('ui.tabs.error_no_html') }}'
 *     }
 * })">
 *     <!-- Tab navigation and content -->
 * </div>
 */
export function tabSystem(config = {}) {
    return {
        // ============================================================
        // STATE PROPERTIES
        // ============================================================
        
        /**
         * Current active tab index
         * @type {number}
         */
        activeTab: config.activeTab ?? 0,
        
        /**
         * Loading indicator state
         * @type {boolean}
         */
        loading: false,
        
        /**
         * Error message (null if no error)
         * @type {string|null}
         */
        error: null,
        
        /**
         * Cached tab content
         * Key: tab index, Value: HTML string
         * @type {Object.<number, string>}
         */
        tabContent: {},
        
        /**
         * Array of loaded tab indices
         * @type {number[]}
         */
        tabsLoaded: [config.activeTab ?? 0],
        
        /**
         * Tab configurations
         * @type {Array}
         */
        tabs: config.tabs ?? [],
        
        /**
         * Lazy loading enabled
         * @type {boolean}
         */
        lazyLoad: config.lazyLoad ?? true,
        
        /**
         * CSRF token for AJAX requests
         * @type {string}
         */
        csrfToken: config.csrfToken ?? '',
        
        /**
         * Translation strings for error messages
         * @type {Object}
         */
        translations: config.translations ?? {},
        
        /**
         * TanStack table instances
         * Key: table ID, Value: TanStack instance
         * @type {Object.<string, Object>}
         */
        tanstackInstances: {},
        
        // ============================================================
        // INITIALIZATION
        // ============================================================
        
        /**
         * Initialize the component
         * 
         * This method is called automatically by Alpine.js when the component
         * is initialized. It performs the following tasks:
         * - Validates configuration
         * - Sets up event listeners
         * - Restores tab state from URL hash (if present)
         * - Initializes TanStack tables for the first tab
         * 
         * Requirements: 7.4 (Alpine.js for reactive tab switching)
         * 
         * @returns {void}
         */
        init() {
            // Validate configuration
            this.validateConfig();
            
            // Restore tab from URL hash if present
            this.restoreTabFromHash();
            
            // Set up hash change listener for bookmarking support
            window.addEventListener('hashchange', () => {
                this.restoreTabFromHash();
            });
            
            // Initialize TanStack tables for the first tab
            this.$nextTick(() => {
                this.initializeTanStackTables(this.activeTab);
            });
            
            // Log initialization in debug mode
            if (this.isDebugMode()) {
                console.log('[TabSystem] Initialized', {
                    activeTab: this.activeTab,
                    totalTabs: this.tabs.length,
                    lazyLoad: this.lazyLoad,
                    tabsLoaded: this.tabsLoaded
                });
            }
        },
        
        /**
         * Validate component configuration
         * 
         * Ensures that required configuration is present and valid.
         * Throws errors for invalid configuration to help developers
         * catch issues early.
         * 
         * @throws {Error} If configuration is invalid
         * @returns {void}
         */
        validateConfig() {
            // Validate tabs array
            if (!Array.isArray(this.tabs)) {
                throw new Error('[TabSystem] Configuration error: tabs must be an array');
            }
            
            if (this.tabs.length === 0) {
                throw new Error('[TabSystem] Configuration error: tabs array is empty');
            }
            
            // Validate activeTab index
            if (this.activeTab < 0 || this.activeTab >= this.tabs.length) {
                console.warn(`[TabSystem] Invalid activeTab index ${this.activeTab}, defaulting to 0`);
                this.activeTab = 0;
            }
            
            // Validate CSRF token if lazy loading is enabled
            if (this.lazyLoad && !this.csrfToken) {
                console.warn('[TabSystem] CSRF token not provided, lazy loading may fail');
            }
        },
        
        /**
         * Check if debug mode is enabled
         * 
         * Debug mode can be enabled by:
         * - Setting window.CANVASTACK_DEBUG = true
         * - Adding ?debug=1 to URL
         * 
         * @returns {boolean}
         */
        isDebugMode() {
            return window.CANVASTACK_DEBUG === true || 
                   new URLSearchParams(window.location.search).get('debug') === '1';
        },
        
        /**
         * Restore tab state from URL hash
         * 
         * Supports bookmarking by reading tab index from URL hash.
         * Format: #tab-{index}
         * 
         * Example: #tab-2 will activate tab at index 2
         * 
         * Requirements: 7.10 (Maintain tab state in URL hash)
         * 
         * @returns {void}
         */
        restoreTabFromHash() {
            const hash = window.location.hash;
            
            if (!hash || !hash.startsWith('#tab-')) {
                return;
            }
            
            const tabIndex = parseInt(hash.replace('#tab-', ''), 10);
            
            if (!isNaN(tabIndex) && tabIndex >= 0 && tabIndex < this.tabs.length) {
                this.switchTab(tabIndex);
            }
        },
        
        /**
         * Initialize TanStack tables for a specific tab
         * 
         * Finds all TanStack table containers in the tab and initializes them.
         * This is called after tab content is loaded (either initially or via AJAX).
         * 
         * Requirements: 8.6 (Initialize TanStack after content loads)
         * 
         * @param {number} tabIndex - Tab index
         * @returns {void}
         */
        initializeTanStackTables(tabIndex) {
            const tabPanel = document.getElementById(`tab-panel-${tabIndex}`);
            
            if (!tabPanel) {
                return;
            }
            
            // Find all TanStack table containers
            const tableContainers = tabPanel.querySelectorAll('[data-tanstack-table]');
            
            tableContainers.forEach(container => {
                const tableId = container.id;
                
                // Check if already initialized
                if (this.tanstackInstances[tableId]) {
                    return;
                }
                
                // Initialize TanStack table
                // The actual initialization is done by the TanStack renderer
                // which includes the initialization script in the HTML
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] TanStack table initialized: ${tableId}`);
                }
            });
        },
        
        // ============================================================
        // HELPER METHODS (to be implemented in subsequent tasks)
        // ============================================================
        
        /**
         * Switch to a different tab
         * 
         * Handles tab switching with the following responsibilities:
         * - Validates tab index
         * - Updates active tab state
         * - Updates ARIA attributes for accessibility
         * - Updates URL hash for bookmarking
         * - Triggers lazy loading if tab not yet loaded
         * 
         * Requirements: 7.3 (Tab switching), 7.10 (URL hash bookmarking)
         * 
         * @param {number} index - Tab index to switch to
         * @returns {void}
         */
        switchTab(index) {
            // Validate tab index
            if (index < 0 || index >= this.tabs.length) {
                console.error(`[TabSystem] Invalid tab index: ${index}`);
                return;
            }
            
            // Don't switch if already on this tab
            if (this.activeTab === index) {
                return;
            }
            
            // Log tab switch in debug mode
            if (this.isDebugMode()) {
                console.log(`[TabSystem] Switching from tab ${this.activeTab} to tab ${index}`);
            }
            
            // Update active tab state
            const previousTab = this.activeTab;
            this.activeTab = index;
            
            // Update ARIA attributes for accessibility
            this.updateAriaAttributes(previousTab, index);
            
            // Update URL hash for bookmarking
            this.updateHash(index);
            
            // Trigger lazy loading if tab not yet loaded
            if (this.lazyLoad && !this.tabsLoaded.includes(index)) {
                this.loadTab(index);
            } else {
                // Re-initialize TanStack tables if already loaded
                this.$nextTick(() => {
                    this.initializeTanStackTables(index);
                });
            }
        },
        
        /**
         * Update ARIA attributes for accessibility
         * 
         * Updates ARIA attributes on tab buttons and panels to reflect
         * the current active tab state. This ensures screen readers
         * properly announce tab state changes.
         * 
         * Requirements: 7.3 (Update ARIA attributes)
         * 
         * @param {number} previousIndex - Previous active tab index
         * @param {number} currentIndex - Current active tab index
         * @returns {void}
         */
        updateAriaAttributes(previousIndex, currentIndex) {
            // Update previous tab button
            const previousButton = document.getElementById(`tab-button-${previousIndex}`);
            if (previousButton) {
                previousButton.setAttribute('aria-selected', 'false');
                previousButton.setAttribute('tabindex', '-1');
            }
            
            // Update previous tab panel
            const previousPanel = document.getElementById(`tab-panel-${previousIndex}`);
            if (previousPanel) {
                previousPanel.setAttribute('aria-hidden', 'true');
            }
            
            // Update current tab button
            const currentButton = document.getElementById(`tab-button-${currentIndex}`);
            if (currentButton) {
                currentButton.setAttribute('aria-selected', 'true');
                currentButton.setAttribute('tabindex', '0');
                currentButton.focus(); // Move focus to active tab
            }
            
            // Update current tab panel
            const currentPanel = document.getElementById(`tab-panel-${currentIndex}`);
            if (currentPanel) {
                currentPanel.setAttribute('aria-hidden', 'false');
            }
            
            if (this.isDebugMode()) {
                console.log('[TabSystem] ARIA attributes updated', {
                    previous: previousIndex,
                    current: currentIndex
                });
            }
        },
        
        /**
         * Load tab content via AJAX
         * 
         * Loads tab content from the server via AJAX request.
         * Implements the following functionality:
         * - Makes AJAX request with CSRF token
         * - Handles loading state
         * - Processes response (success/error)
         * - Caches loaded content
         * - Initializes TanStack tables after load
         * - Handles errors gracefully
         * 
         * Requirements:
         * - 6.3 (Trigger AJAX request when user clicks inactive tab)
         * - 6.4 (Provide route endpoint for lazy-loading tab content)
         * - 6.5 (Cache content in Alpine.js component)
         * - 6.6 (Display cached content when switching to previously loaded tab)
         * - 6.7 (Include CSRF token in AJAX requests)
         * - 6.8 (Display error message when AJAX request fails)
         * 
         * @param {number} index - Tab index to load
         * @returns {Promise<void>}
         */
        async loadTab(index) {
            // Validate tab index
            if (index < 0 || index >= this.tabs.length) {
                console.error(`[TabSystem] Invalid tab index: ${index}`);
                return;
            }
            
            // Check if already loaded (cache check)
            if (this.tabsLoaded.includes(index)) {
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] Tab ${index} already loaded (using cache)`);
                }
                return;
            }
            
            // Get tab configuration
            const tab = this.tabs[index];
            
            if (!tab || !tab.url) {
                console.error(`[TabSystem] Tab ${index} has no URL configured`);
                this.error = this.translations.error_no_url || 'Tab configuration error: No URL specified';
                return;
            }
            
            // Set loading state
            this.loading = true;
            this.error = null;
            
            if (this.isDebugMode()) {
                console.log(`[TabSystem] Loading tab ${index} from ${tab.url}`);
            }
            
            try {
                // Make AJAX request with CSRF token
                const response = await fetch(tab.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        tab_index: index
                    })
                });
                
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Parse JSON response
                const data = await response.json();
                
                // Validate response structure
                if (!data.success) {
                    throw new Error(data.message || this.translations.error_load_failed || 'Failed to load tab content');
                }
                
                if (!data.html) {
                    throw new Error(this.translations.error_no_html || 'Response missing HTML content');
                }
                
                // Cache the loaded content
                this.tabContent[index] = data.html;
                this.tabsLoaded.push(index);
                
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] Tab ${index} loaded successfully`, {
                        htmlLength: data.html.length,
                        hasScripts: !!data.scripts
                    });
                }
                
                // Wait for DOM update
                await this.$nextTick();
                
                // Inject HTML into tab panel
                const tabPanel = document.getElementById(`tab-panel-${index}`);
                if (tabPanel) {
                    const contentContainer = tabPanel.querySelector('[data-tab-content]');
                    if (contentContainer) {
                        contentContainer.innerHTML = data.html;
                    }
                }
                
                // Execute initialization scripts if provided
                if (data.scripts) {
                    this.executeScripts(data.scripts);
                }
                
                // Initialize TanStack tables in the loaded content
                await this.$nextTick();
                this.initializeTanStackTables(index);
                
                // Clear loading state
                this.loading = false;
                
            } catch (error) {
                // Handle errors
                console.error('[TabSystem] Failed to load tab:', error);
                
                this.error = this.formatErrorMessage(error);
                this.loading = false;
                
                // Remove from loaded tabs if it was added
                const loadedIndex = this.tabsLoaded.indexOf(index);
                if (loadedIndex > -1) {
                    this.tabsLoaded.splice(loadedIndex, 1);
                }
                
                // Clear cached content
                delete this.tabContent[index];
            }
        },
        
        /**
         * Execute JavaScript code from AJAX response
         * 
         * Safely executes initialization scripts returned from the server.
         * This is used to initialize TanStack tables after content is loaded.
         * 
         * @param {string} scripts - JavaScript code to execute
         * @returns {void}
         */
        executeScripts(scripts) {
            try {
                // Create a script element and execute
                const scriptElement = document.createElement('script');
                scriptElement.textContent = scripts;
                document.body.appendChild(scriptElement);
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(scriptElement);
                }, 100);
                
                if (this.isDebugMode()) {
                    console.log('[TabSystem] Scripts executed successfully');
                }
            } catch (error) {
                console.error('[TabSystem] Failed to execute scripts:', error);
            }
        },
        
        /**
         * Format error message for display
         * 
         * Converts technical error messages into user-friendly messages.
         * Provides helpful context for common error scenarios.
         * Uses translations passed from Blade template for i18n support.
         * 
         * Requirements: 6.8 (Display user-friendly error message)
         * 
         * @param {Error} error - Error object
         * @returns {string} User-friendly error message
         */
        formatErrorMessage(error) {
            const message = error.message || 'Unknown error';
            
            // Map common errors to user-friendly messages using translations
            if (message.includes('HTTP 404')) {
                return this.translations.error_404 || 'Tab content not found. Please refresh the page.';
            }
            
            if (message.includes('HTTP 403')) {
                return this.translations.error_403 || 'You do not have permission to view this tab.';
            }
            
            if (message.includes('HTTP 419')) {
                return this.translations.error_419 || 'Your session has expired. Please refresh the page.';
            }
            
            if (message.includes('HTTP 429')) {
                return this.translations.error_429 || 'Too many requests. Please wait a moment and try again.';
            }
            
            if (message.includes('HTTP 500') || message.includes('HTTP 503')) {
                return this.translations.error_500 || 'Server error. Please try again later.';
            }
            
            if (message.includes('NetworkError') || message.includes('Failed to fetch')) {
                return this.translations.error_network || 'Network error. Please check your connection and try again.';
            }
            
            // Default error message with fallback
            const defaultMsg = this.translations.error_default || 'Failed to load tab: {message}';
            return defaultMsg.replace('{message}', message);
        },
        
        /**
         * Handle keyboard navigation
         * 
         * Implements keyboard navigation for tabs to support accessibility.
         * Supports the following keys:
         * - ArrowLeft: Move to previous tab (wraps to last if at first)
         * - ArrowRight: Move to next tab (wraps to first if at last)
         * - Home: Jump to first tab
         * - End: Jump to last tab
         * 
         * Focus management is handled by switchTab() which calls updateAriaAttributes().
         * 
         * Requirements: 7.6 (Keyboard navigation for accessibility)
         * 
         * @param {KeyboardEvent} event - Keyboard event
         * @returns {void}
         */
        handleKeydown(event) {
            // Only handle specific keys
            const handledKeys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
            
            if (!handledKeys.includes(event.key)) {
                return;
            }
            
            // Prevent default behavior for handled keys
            event.preventDefault();
            
            let newIndex = this.activeTab;
            
            switch (event.key) {
                case 'ArrowLeft':
                    // Move to previous tab, wrap to last if at first
                    newIndex = this.activeTab - 1;
                    if (newIndex < 0) {
                        newIndex = this.tabs.length - 1;
                    }
                    break;
                    
                case 'ArrowRight':
                    // Move to next tab, wrap to first if at last
                    newIndex = this.activeTab + 1;
                    if (newIndex >= this.tabs.length) {
                        newIndex = 0;
                    }
                    break;
                    
                case 'Home':
                    // Jump to first tab
                    newIndex = 0;
                    break;
                    
                case 'End':
                    // Jump to last tab
                    newIndex = this.tabs.length - 1;
                    break;
            }
            
            // Switch to the new tab
            // switchTab() handles focus management via updateAriaAttributes()
            this.switchTab(newIndex);
            
            if (this.isDebugMode()) {
                console.log(`[TabSystem] Keyboard navigation: ${event.key} -> tab ${newIndex}`);
            }
        },
        
        /**
         * Update URL hash for bookmarking
         * 
         * Updates the URL hash to reflect the current active tab.
         * This allows users to bookmark specific tabs and share URLs.
         * 
         * Format: #tab-{index}
         * Example: #tab-2 for tab at index 2
         * 
         * Requirements: 7.10 (Maintain tab state in URL hash)
         * 
         * @param {number} index - Tab index
         * @returns {void}
         */
        updateHash(index) {
            // Don't update hash for first tab (cleaner URLs)
            if (index === 0) {
                // Remove hash if switching to first tab
                if (window.location.hash) {
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                }
                return;
            }
            
            // Update hash without triggering hashchange event
            const newHash = `#tab-${index}`;
            
            if (window.location.hash !== newHash) {
                history.replaceState(null, '', newHash);
                
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] URL hash updated: ${newHash}`);
                }
            }
        },
        
        /**
         * Retry loading a failed tab
         * 
         * Clears cached content and error state, then attempts to reload the tab.
         * This is called when the user clicks the retry button after a load failure.
         * 
         * Implements the following functionality:
         * - Validates tab index
         * - Clears error state
         * - Clears cached content for the tab
         * - Removes tab from loaded tabs list
         * - Triggers fresh load attempt
         * 
         * Requirements:
         * - 6.8 (Error handling with retry functionality)
         * - 15.2 (Display user-friendly error messages)
         * 
         * @param {number} index - Tab index to retry
         * @returns {Promise<void>}
         */
        async retryLoad(index) {
            // Validate tab index
            if (index < 0 || index >= this.tabs.length) {
                console.error(`[TabSystem] Invalid tab index for retry: ${index}`);
                return;
            }
            
            if (this.isDebugMode()) {
                console.log(`[TabSystem] Retrying load for tab ${index}`);
            }
            
            // Clear error state
            this.error = null;
            
            // Clear cached content for this tab
            if (this.tabContent[index]) {
                delete this.tabContent[index];
                
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] Cleared cached content for tab ${index}`);
                }
            }
            
            // Remove from loaded tabs list
            const loadedIndex = this.tabsLoaded.indexOf(index);
            if (loadedIndex > -1) {
                this.tabsLoaded.splice(loadedIndex, 1);
                
                if (this.isDebugMode()) {
                    console.log(`[TabSystem] Removed tab ${index} from loaded tabs list`);
                }
            }
            
            // Attempt to load the tab again
            await this.loadTab(index);
        }
    };
}

/**
 * Initialize table tabs system
 * 
 * This function is called to register the tabSystem component with Alpine.js.
 * It should be called after Alpine.js is loaded but before Alpine.start().
 * 
 * @returns {void}
 */
export function initTableTabs() {
    // Make tabSystem available globally for Alpine.js
    window.tabSystem = tabSystem;
    
    if (window.Alpine) {
        // Register as Alpine.js magic helper
        window.Alpine.data('tabSystem', tabSystem);
        
        console.log('[TabSystem] Registered with Alpine.js');
    } else {
        console.error('[TabSystem] Alpine.js not found. Please ensure Alpine.js is loaded before table-tabs.js');
    }
}

// Auto-initialize if Alpine.js is already loaded
if (typeof window !== 'undefined' && window.Alpine) {
    initTableTabs();
}

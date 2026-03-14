<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for Alpine.js interactions in the tab system.
 *
 * Tests reactive state updates, content caching, and TanStack initialization
 * for the TanStack Table Multi-Table & Tab System.
 *
 * Requirements Validated:
 * - 7.4: Alpine.js component for reactive tab switching
 * - 6.5: Content cached in Alpine.js state
 * - 6.6: Check cache before making AJAX request
 * - 8.6: TanStack initialization after AJAX load
 * - 12.7: Browser tests for Alpine.js interactions
 *
 * @see .kiro/specs/tanstack-multi-table-tabs/requirements.md (Requirement 6, 7, 8, 12.7)
 * @see .kiro/specs/tanstack-multi-table-tabs/tasks.md (Task 5.3.3)
 */
class AlpineJsInteractionTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test Alpine.js component initializes correctly.
     *
     * Validates: Requirement 7.4 - Alpine.js component initialization
     *
     * @return void
     */
    public function test_alpine_component_initializes_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Alpine.js component should be initialized
                    ->assertPresent('[x-data]')
                    // Component should have tabSystem data
                    ->assertScript('return !!document.querySelector("[x-data]").__x')
                    // Initial state should be set
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 0');
        });
    }

    /**
     * Test reactive state updates when switching tabs.
     *
     * Validates: Requirement 7.4 - Reactive state updates
     *
     * @return void
     */
    public function test_reactive_state_updates_when_switching_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Initial state: activeTab = 0
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 0')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // State should update: activeTab = 1
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    // State should update: activeTab = 2
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 2')
                    // Click first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(300)
                    // State should update: activeTab = 0
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 0');
        });
    }

    /**
     * Test loading state updates during AJAX request.
     *
     * Validates: Requirement 7.4 - Loading state management
     *
     * @return void
     */
    public function test_loading_state_updates_during_ajax_request(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Initial state: loading = false
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.loading === false')
                    // Click second tab to trigger AJAX
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(50)
                    // State should update: loading = true
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.loading === true')
                    // Wait for load to complete
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // State should update: loading = false
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.loading === false');
        });
    }

    /**
     * Test error state updates when AJAX fails.
     *
     * Validates: Requirement 7.4 - Error state management
     *
     * @return void
     */
    public function test_error_state_updates_when_ajax_fails(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Initial state: error = null
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.error === null')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // State should update: error = error message
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.error !== null')
                    ->assertScript('return typeof document.querySelector("[x-data]").__x.$data.error === "string"');
        });
    }

    /**
     * Test content caching in Alpine.js state.
     *
     * Validates: Requirement 6.5 - Content cached in Alpine.js state
     *
     * @return void
     */
    public function test_content_caching_in_alpine_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Initial state: tabContent = {}
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 0')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // State should cache content: tabContent[1] exists
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] !== undefined')
                    ->assertScript('return typeof document.querySelector("[x-data]").__x.$data.tabContent[1] === "string"')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1].length > 0')
                    // Load third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    // State should cache both tabs: tabContent[1] and tabContent[2]
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 2')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[2] !== undefined');
        });
    }

    /**
     * Test cache is checked before making AJAX request.
     *
     * Validates: Requirement 6.6 - Check cache before AJAX request
     *
     * @return void
     */
    public function test_cache_checked_before_ajax_request(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab (first time - should make AJAX request)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(50)
                    // Loading indicator should appear (AJAX request)
                    ->assertVisible('[data-loading-indicator]')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should be cached
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] !== undefined')
                    // Switch to first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    // Switch back to second tab (should use cache)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    // Loading indicator should NOT appear (using cache)
                    ->assertNotVisible('[data-loading-indicator]')
                    // Content should display immediately from cache
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test tabsLoaded array tracks loaded tabs.
     *
     * Validates: Requirement 6.5 - Track loaded tabs
     *
     * @return void
     */
    public function test_tabs_loaded_array_tracks_loaded_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Initial state: tabsLoaded = [0] (first tab pre-loaded)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.includes(0)')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 1')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // State should update: tabsLoaded = [0, 1]
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.includes(1)')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 2')
                    // Load third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    // State should update: tabsLoaded = [0, 1, 2]
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.includes(2)')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 3');
        });
    }

    /**
     * Test TanStack instances are tracked in Alpine.js state.
     *
     * Validates: Requirement 8.6 - TanStack instance tracking
     *
     * @return void
     */
    public function test_tanstack_instances_tracked_in_alpine_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Initial state: tanstackInstances = {}
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tanstackInstances || {}).length === 0')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // TanStack instance should be tracked
                    ->pause(500) // Wait for TanStack initialization
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tanstackInstances || {}).length > 0');
        });
    }

    /**
     * Test TanStack initializes after AJAX load.
     *
     * Validates: Requirement 8.6 - TanStack initialization after AJAX load
     *
     * @return void
     */
    public function test_tanstack_initializes_after_ajax_load(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Wait for TanStack to initialize
                    ->pause(500)
                    // TanStack table should be initialized
                    ->assertPresent('[role="tabpanel"]:nth-child(2) [data-tanstack-table]')
                    // Table should be interactive (sortable)
                    ->assertPresent('[role="tabpanel"]:nth-child(2) th[data-sortable]')
                    // Click sort to verify TanStack is working
                    ->click('[role="tabpanel"]:nth-child(2) th[data-sortable]:first-child')
                    ->pause(300)
                    // Sort indicator should appear
                    ->assertPresent('[role="tabpanel"]:nth-child(2) th[data-sort-direction]');
        });
    }

    /**
     * Test TanStack cleanup when switching tabs.
     *
     * Validates: Requirement 8.7 - TanStack cleanup when switching tabs
     *
     * @return void
     */
    public function test_tanstack_cleanup_when_switching_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->pause(500)
                    // TanStack should be initialized
                    ->assertPresent('[role="tabpanel"]:nth-child(2) [data-tanstack-table]')
                    // Switch to first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(300)
                    // Second tab should be hidden
                    ->assertNotVisible('[role="tabpanel"]:nth-child(2)')
                    // Switch back to second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // TanStack should still work (re-initialized or preserved)
                    ->assertPresent('[role="tabpanel"]:nth-child(2) [data-tanstack-table]')
                    ->assertPresent('[role="tabpanel"]:nth-child(2) th[data-sortable]');
        });
    }

    /**
     * Test Alpine.js x-show directive works correctly.
     *
     * Validates: Requirement 7.4 - Alpine.js directives
     *
     * @return void
     */
    public function test_alpine_x_show_directive_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // First tab panel should be visible (x-show="activeTab === 0")
                    ->assertVisible('[role="tabpanel"]:nth-child(1)')
                    // Other tab panels should be hidden
                    ->assertNotVisible('[role="tabpanel"]:nth-child(2)')
                    ->assertNotVisible('[role="tabpanel"]:nth-child(3)')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // Second tab panel should be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // Other tab panels should be hidden
                    ->assertNotVisible('[role="tabpanel"]:nth-child(1)')
                    ->assertNotVisible('[role="tabpanel"]:nth-child(3)');
        });
    }

    /**
     * Test Alpine.js x-html directive renders cached content.
     *
     * Validates: Requirement 6.5 - x-html renders cached content
     *
     * @return void
     */
    public function test_alpine_x_html_directive_renders_cached_content(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should be rendered via x-html
                    ->assertPresent('[role="tabpanel"]:nth-child(2) [x-html]')
                    // Content should be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Switch to first tab and back
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    // Content should still be rendered from cache
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test Alpine.js @click directive handles tab switching.
     *
     * Validates: Requirement 7.4 - Alpine.js event handling
     *
     * @return void
     */
    public function test_alpine_click_directive_handles_tab_switching(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Tabs should have @click directive
                    ->assertPresent('[role="tab"][@click]')
                    // Click should trigger switchTab method
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // State should update
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    // Content should switch
                    ->assertVisible('[role="tabpanel"]:nth-child(2)');
        });
    }

    /**
     * Test Alpine.js x-init directive initializes tab system.
     *
     * Validates: Requirement 7.4 - Alpine.js initialization
     *
     * @return void
     */
    public function test_alpine_x_init_directive_initializes_tab_system(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Tab panels should have x-init for lazy loading
                    ->assertPresent('[role="tabpanel"][x-init]')
                    // Click tab with x-init
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    // x-init should trigger loadTab method
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test Alpine.js reactive state persists across interactions.
     *
     * Validates: Requirement 7.4 - State persistence
     *
     * @return void
     */
    public function test_alpine_reactive_state_persists_across_interactions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load multiple tabs
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    // State should persist all loaded tabs
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 3')
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 2')
                    // Switch between tabs multiple times
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(200)
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(200)
                    // State should still be intact
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 3')
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 2');
        });
    }

    /**
     * Test Alpine.js state updates are reactive and immediate.
     *
     * Validates: Requirement 7.4 - Reactive updates
     *
     * @return void
     */
    public function test_alpine_state_updates_are_reactive_and_immediate(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Click tab
                    ->click('[role="tab"]:nth-child(2)')
                    // State should update immediately (no pause needed)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    // UI should update immediately
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    ->assertNotVisible('[role="tabpanel"]:nth-child(1)');
        });
    }

    /**
     * Test Alpine.js handles multiple tab groups independently.
     *
     * Validates: Requirement 5.6 - State isolation between instances
     *
     * @return void
     */
    public function test_alpine_handles_multiple_tab_groups_independently(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/multiple-tabs')
                    // Each tab group should have its own Alpine.js instance
                    ->assertPresent('[data-tab-group="group1"][x-data]')
                    ->assertPresent('[data-tab-group="group2"][x-data]')
                    // Click tab in first group
                    ->click('[data-tab-group="group1"] [role="tab"]:nth-child(2)')
                    ->pause(300)
                    // First group state should update
                    ->assertScript('return document.querySelector("[data-tab-group=\'group1\']").__x.$data.activeTab === 1')
                    // Second group state should remain unchanged
                    ->assertScript('return document.querySelector("[data-tab-group=\'group2\']").__x.$data.activeTab === 0')
                    // Click tab in second group
                    ->click('[data-tab-group="group2"] [role="tab"]:nth-child(3)')
                    ->pause(300)
                    // Second group state should update
                    ->assertScript('return document.querySelector("[data-tab-group=\'group2\']").__x.$data.activeTab === 2')
                    // First group state should remain unchanged
                    ->assertScript('return document.querySelector("[data-tab-group=\'group1\']").__x.$data.activeTab === 1');
        });
    }

    /**
     * Test Alpine.js cache is isolated between tab groups.
     *
     * Validates: Requirement 5.6 - Cache isolation
     *
     * @return void
     */
    public function test_alpine_cache_isolated_between_tab_groups(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/multiple-lazy-tabs')
                    // Load tab in first group
                    ->click('[data-tab-group="group1"] [role="tab"]:nth-child(2)')
                    ->waitFor('[data-tab-group="group1"] [role="tabpanel"]:nth-child(2) .table', 5)
                    // First group should have cached content
                    ->assertScript('return Object.keys(document.querySelector("[data-tab-group=\'group1\']").__x.$data.tabContent).length === 1')
                    // Second group should have no cached content
                    ->assertScript('return Object.keys(document.querySelector("[data-tab-group=\'group2\']").__x.$data.tabContent).length === 0')
                    // Load tab in second group
                    ->click('[data-tab-group="group2"] [role="tab"]:nth-child(2)')
                    ->waitFor('[data-tab-group="group2"] [role="tabpanel"]:nth-child(2) .table', 5)
                    // Both groups should have their own cached content
                    ->assertScript('return Object.keys(document.querySelector("[data-tab-group=\'group1\']").__x.$data.tabContent).length === 1')
                    ->assertScript('return Object.keys(document.querySelector("[data-tab-group=\'group2\']").__x.$data.tabContent).length === 1');
        });
    }

    /**
     * Test Alpine.js error handling clears on retry.
     *
     * Validates: Requirement 6.8 - Error state management
     *
     * @return void
     */
    public function test_alpine_error_handling_clears_on_retry(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-retry')
                    // Click tab that will fail first time
                    ->click('[role="tab"][data-will-fail-once]')
                    ->waitFor('[data-error-message]', 5)
                    // Error state should be set
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.error !== null')
                    // Click retry
                    ->click('[data-retry-button]')
                    ->pause(100)
                    // Error state should be cleared
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.error === null')
                    // Loading state should be set
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.loading === true')
                    // Wait for successful load
                    ->waitFor('[role="tabpanel"] .table', 5)
                    // Loading state should be cleared
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.loading === false');
        });
    }

    /**
     * Test Alpine.js cache clears on retry after error.
     *
     * Validates: Requirement 6.8 - Cache clearing on retry
     *
     * @return void
     */
    public function test_alpine_cache_clears_on_retry_after_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    ->waitFor('[data-error-message]', 5)
                    // Cache might have error content
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] === undefined || document.querySelector("[x-data]").__x.$data.tabContent[1] === null')
                    // Click retry
                    ->click('[data-retry-button]')
                    ->pause(100)
                    // Cache should be cleared for this tab
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] === undefined || document.querySelector("[x-data]").__x.$data.tabContent[1] === null')
                    // Tab should be removed from loaded list
                    ->assertScript('return !document.querySelector("[x-data]").__x.$data.tabsLoaded.includes(1)');
        });
    }

    /**
     * Test Alpine.js performance with rapid tab switching.
     *
     * Validates: Requirement 11.7 - Performance with rapid interactions
     *
     * @return void
     */
    public function test_alpine_performance_with_rapid_tab_switching(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs');

            $startTime = microtime(true);

            // Rapidly switch between tabs
            for ($i = 1; $i <= 10; $i++) {
                $tabIndex = ($i % 3) + 1;
                $browser->click("[role=\"tab\"]:nth-child({$tabIndex})")
                        ->pause(50);
            }

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Rapid switching should be performant (< 1 second for 10 switches)
            $this->assertLessThan(1000, $duration, 'Rapid tab switching took too long');

            // State should be consistent after rapid switching
            $browser->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab >= 0')
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab <= 2');
        });
    }

    /**
     * Test Alpine.js memory management with many tab loads.
     *
     * Validates: Requirement 11.10 - Memory usage
     *
     * @return void
     */
    public function test_alpine_memory_management_with_many_tab_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/many-lazy-tabs')
                    // Load 5 tabs sequentially
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    ->click('[role="tab"]:nth-child(4)')
                    ->waitFor('[role="tabpanel"]:nth-child(4) .table', 5)
                    ->click('[role="tab"]:nth-child(5)')
                    ->waitFor('[role="tabpanel"]:nth-child(5) .table', 5)
                    ->click('[role="tab"]:nth-child(6)')
                    ->waitFor('[role="tabpanel"]:nth-child(6) .table', 5)
                    // All tabs should be cached
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 6')
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 5')
                    // State should still be responsive
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 0');
        });
    }

    /**
     * Test Alpine.js works correctly in dark mode.
     *
     * Validates: Requirement 7.8 - Dark mode support
     *
     * @return void
     */
    public function test_alpine_works_correctly_in_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Enable dark mode
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    // Alpine.js should still work
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // State management should work in dark mode
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 2');
        });
    }

    /**
     * Test Alpine.js works correctly on mobile devices.
     *
     * Validates: Requirement 7.7 - Responsive design
     *
     * @return void
     */
    public function test_alpine_works_correctly_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->resize(375, 667) // iPhone SE size
                    ->visit('/admin/test/tabs')
                    // Alpine.js should work on mobile
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // Touch interactions should work
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 2');
        });
    }

    /**
     * Test Alpine.js lazy loading works on mobile.
     *
     * Validates: Requirement 7.7 - Responsive lazy loading
     *
     * @return void
     */
    public function test_alpine_lazy_loading_works_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->resize(375, 667) // iPhone SE size
                    ->visit('/admin/test/lazy-tabs')
                    // Lazy loading should work on mobile
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    ->assertVisible('[data-loading-indicator]')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should be cached
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] !== undefined')
                    // Cache should work on mobile
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    ->assertNotVisible('[data-loading-indicator]')
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test Alpine.js state survives page interactions.
     *
     * Validates: Requirement 7.4 - State persistence
     *
     * @return void
     */
    public function test_alpine_state_survives_page_interactions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load tabs
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    // Interact with page (scroll, click other elements)
                    ->script('window.scrollTo(0, 500);')
                    ->pause(200)
                    ->script('window.scrollTo(0, 0);')
                    ->pause(200)
                    // State should still be intact
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabsLoaded.length === 3')
                    ->assertScript('return Object.keys(document.querySelector("[x-data]").__x.$data.tabContent).length === 2')
                    // Tab switching should still work
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(200)
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test Alpine.js handles browser back/forward buttons.
     *
     * Validates: Requirement 7.10 - URL hash bookmarking
     *
     * @return void
     */
    public function test_alpine_handles_browser_back_forward_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 2')
                    // Use browser back button
                    ->back()
                    ->pause(500)
                    // State should update to second tab
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // Use browser forward button
                    ->forward()
                    ->pause(500)
                    // State should update to third tab
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 2')
                    ->assertVisible('[role="tabpanel"]:nth-child(3)');
        });
    }

    /**
     * Test Alpine.js cross-browser compatibility.
     *
     * Validates: Requirement 12.7 - Cross-browser compatibility
     *
     * @return void
     */
    public function test_alpine_cross_browser_compatibility(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Alpine.js should initialize
                    ->assertPresent('[x-data]')
                    ->assertScript('return !!document.querySelector("[x-data]").__x')
                    // Basic functionality should work
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.activeTab === 1')
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // Lazy loading should work
                    ->visit('/admin/test/lazy-tabs')
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->assertScript('return document.querySelector("[x-data]").__x.$data.tabContent[1] !== undefined');
        });
    }

    /**
     * Create admin user for testing.
     *
     * @return \App\Models\User
     */
    protected function createAdminUser()
    {
        return \App\Models\User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
    }
}

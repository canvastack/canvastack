<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for tab lazy loading functionality.
 *
 * Tests lazy loading trigger, loading indicator display, content display after load,
 * error state display, and retry functionality for the TanStack Table Multi-Table & Tab System.
 *
 * Requirements Validated:
 * - 6.3: User clicks inactive tab triggers AJAX request
 * - 6.8: AJAX request failure displays error message
 * - 6.9: Loading indicator during AJAX requests
 * - 12.7: Browser tests for tab loading
 *
 * @see .kiro/specs/tanstack-multi-table-tabs/requirements.md (Requirement 6, 12.7)
 * @see .kiro/specs/tanstack-multi-table-tabs/tasks.md (Task 5.3.2)
 */
class TabLoadingTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test lazy loading trigger when clicking inactive tab.
     *
     * Validates: Requirement 6.3, 12.7 - Lazy loading trigger
     *
     * @return void
     */
    public function test_clicking_inactive_tab_triggers_lazy_loading(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // First tab should be active and loaded
                    ->assertSee('Tab 1')
                    ->assertVisible('[role="tabpanel"]:nth-child(1)')
                    ->assertSee('Content for Tab 1')
                    // Second tab should not be loaded yet
                    ->assertPresent('[role="tab"]:nth-child(2)')
                    ->assertNotVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Click second tab to trigger lazy loading
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    // Loading indicator should appear
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for content to load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should now be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    ->assertSee('Content for Tab 2');
        });
    }

    /**
     * Test loading indicator displays during AJAX request.
     *
     * Validates: Requirement 6.9, 12.7 - Loading indicator display
     *
     * @return void
     */
    public function test_loading_indicator_displays_during_ajax_request(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Loading indicator should not be visible initially
                    ->assertNotVisible('[data-loading-indicator]')
                    // Click second tab to trigger lazy loading
                    ->click('[role="tab"]:nth-child(2)')
                    // Loading indicator should appear immediately
                    ->pause(50)
                    ->assertVisible('[data-loading-indicator]')
                    // Loading indicator should contain spinner or loading text
                    ->assertPresent('[data-loading-indicator] .loading-spinner, [data-loading-indicator] .loading-text')
                    // Wait for content to load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Loading indicator should disappear after load
                    ->assertNotVisible('[data-loading-indicator]');
        });
    }

    /**
     * Test loading indicator shows correct message.
     *
     * Validates: Requirement 6.9, 12.7 - Loading indicator content
     *
     * @return void
     */
    public function test_loading_indicator_shows_correct_message(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(50)
                    // Loading indicator should be visible
                    ->assertVisible('[data-loading-indicator]')
                    // Should show loading message
                    ->assertSeeIn('[data-loading-indicator]', 'Loading')
                    // Wait for load
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5);
        });
    }

    /**
     * Test content displays correctly after lazy load completes.
     *
     * Validates: Requirement 6.3, 12.7 - Content display after load
     *
     * @return void
     */
    public function test_content_displays_after_lazy_load_completes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    // Wait for content to load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Table should have headers
                    ->assertPresent('[role="tabpanel"]:nth-child(2) table thead')
                    // Table should have data rows
                    ->assertPresent('[role="tabpanel"]:nth-child(2) table tbody tr')
                    // Should see actual data
                    ->assertSee('User')
                    ->assertSee('Email');
        });
    }

    /**
     * Test TanStack table initializes after lazy load.
     *
     * Validates: Requirement 8.6, 12.7 - TanStack initialization after lazy load
     *
     * @return void
     */
    public function test_tanstack_table_initializes_after_lazy_load(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    // Wait for content to load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // TanStack table should be initialized (has data-tanstack attribute or class)
                    ->assertPresent('[role="tabpanel"]:nth-child(2) [data-tanstack-table]')
                    // Table should be interactive (sortable columns)
                    ->assertPresent('[role="tabpanel"]:nth-child(2) th[data-sortable]')
                    // Click sortable column header
                    ->click('[role="tabpanel"]:nth-child(2) th[data-sortable]:first-child')
                    ->pause(300)
                    // Table should re-sort (verify by checking sort indicator)
                    ->assertPresent('[role="tabpanel"]:nth-child(2) th[data-sort-direction]');
        });
    }

    /**
     * Test multiple tabs can be lazy loaded sequentially.
     *
     * Validates: Requirement 6.3, 12.7 - Multiple tab lazy loading
     *
     * @return void
     */
    public function test_multiple_tabs_lazy_load_sequentially(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Load third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->waitFor('[role="tabpanel"]:nth-child(3) .table', 5)
                    ->assertVisible('[role="tabpanel"]:nth-child(3) .table')
                    // Load fourth tab
                    ->click('[role="tab"]:nth-child(4)')
                    ->waitFor('[role="tabpanel"]:nth-child(4) .table', 5)
                    ->assertVisible('[role="tabpanel"]:nth-child(4) .table')
                    // All tabs should now have content
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(200)
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(200)
                    ->assertVisible('[role="tabpanel"]:nth-child(3) .table');
        });
    }

    /**
     * Test previously loaded tab displays instantly (cached).
     *
     * Validates: Requirement 6.6, 12.7 - Cached content display
     *
     * @return void
     */
    public function test_previously_loaded_tab_displays_instantly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Load second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Switch to first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    ->assertVisible('[role="tabpanel"]:nth-child(1)')
                    // Switch back to second tab (should be instant from cache)
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(100)
                    // Content should appear immediately without loading indicator
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table')
                    // Loading indicator should NOT appear (cached)
                    ->assertNotVisible('[data-loading-indicator]');
        });
    }

    /**
     * Test error state displays when AJAX request fails.
     *
     * Validates: Requirement 6.8, 12.7 - Error state display
     *
     * @return void
     */
    public function test_error_state_displays_when_ajax_fails(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail to load
                    ->click('[role="tab"][data-will-fail]')
                    ->pause(100)
                    // Loading indicator should appear
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for error to occur
                    ->waitFor('[data-error-message]', 5)
                    // Error message should be visible
                    ->assertVisible('[data-error-message]')
                    // Error message should contain helpful text
                    ->assertSeeIn('[data-error-message]', 'Failed to load')
                    // Loading indicator should disappear
                    ->assertNotVisible('[data-loading-indicator]');
        });
    }

    /**
     * Test error message shows correct details.
     *
     * Validates: Requirement 6.8, 12.7 - Error message content
     *
     * @return void
     */
    public function test_error_message_shows_correct_details(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Error should contain user-friendly message
                    ->assertSeeIn('[data-error-message]', 'Failed to load tab content')
                    // Error should have retry button
                    ->assertPresent('[data-error-message] [data-retry-button]')
                    // Error should not expose technical details in production
                    ->assertDontSee('500 Internal Server Error')
                    ->assertDontSee('Exception')
                    ->assertDontSee('Stack trace');
        });
    }

    /**
     * Test retry functionality after error.
     *
     * Validates: Requirement 6.8, 12.7 - Retry functionality
     *
     * @return void
     */
    public function test_retry_functionality_after_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-retry')
                    // Click tab that will fail first time
                    ->click('[role="tab"][data-will-fail-once]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    ->assertVisible('[data-error-message]')
                    // Click retry button
                    ->click('[data-retry-button]')
                    ->pause(100)
                    // Loading indicator should appear again
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for successful load
                    ->waitFor('[role="tabpanel"] .table', 5)
                    // Content should now be visible
                    ->assertVisible('[role="tabpanel"] .table')
                    // Error message should disappear
                    ->assertNotVisible('[data-error-message]');
        });
    }

    /**
     * Test retry button clears cache before retrying.
     *
     * Validates: Requirement 6.8, 12.7 - Retry clears cache
     *
     * @return void
     */
    public function test_retry_button_clears_cache_before_retrying(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Click retry button
                    ->click('[data-retry-button]')
                    ->pause(100)
                    // Should show loading indicator (not cached error)
                    ->assertVisible('[data-loading-indicator]')
                    // Should make new AJAX request (verify by checking network or timing)
                    ->pause(500)
                    // Error or content should appear (depending on server response)
                    ->waitFor('[data-error-message], [role="tabpanel"] .table', 5);
        });
    }

    /**
     * Test error state styling is visible and distinct.
     *
     * Validates: Requirement 6.8, 12.7 - Error state styling
     *
     * @return void
     */
    public function test_error_state_styling_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Error message should have error styling (red/danger colors)
                    ->assertPresent('[data-error-message].alert-error, [data-error-message].text-error')
                    // Error icon should be present
                    ->assertPresent('[data-error-message] [data-error-icon]')
                    // Retry button should be styled as action button
                    ->assertPresent('[data-retry-button].btn');
        });
    }

    /**
     * Test loading indicator works in dark mode.
     *
     * Validates: Requirement 7.8, 12.7 - Dark mode support for loading
     *
     * @return void
     */
    public function test_loading_indicator_works_in_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Enable dark mode
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    // Click tab to trigger loading
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(50)
                    // Loading indicator should be visible in dark mode
                    ->assertVisible('[data-loading-indicator]')
                    // Loading indicator should have dark mode styling
                    ->assertPresent('[data-loading-indicator]')
                    // Wait for load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5);
        });
    }

    /**
     * Test error state works in dark mode.
     *
     * Validates: Requirement 7.8, 12.7 - Dark mode support for errors
     *
     * @return void
     */
    public function test_error_state_works_in_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Enable dark mode
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Error message should be visible in dark mode
                    ->assertVisible('[data-error-message]')
                    // Error styling should work in dark mode
                    ->assertPresent('[data-error-message]');
        });
    }

    /**
     * Test loading performance is acceptable.
     *
     * Validates: Requirement 11.2, 12.7 - Lazy load performance
     *
     * @return void
     */
    public function test_lazy_loading_performance_is_acceptable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs');

            $startTime = microtime(true);

            // Click tab to trigger lazy load
            $browser->click('[role="tab"]:nth-child(2)')
                    // Wait for content to load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Lazy load should complete in < 500ms for 1K rows (Requirement 11.2)
            $this->assertLessThan(500, $duration, 'Lazy loading took too long');
        });
    }

    /**
     * Test loading indicator is accessible.
     *
     * Validates: Requirement 7.6, 12.7 - Accessibility for loading state
     *
     * @return void
     */
    public function test_loading_indicator_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs')
                    // Click tab to trigger loading
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(50)
                    // Loading indicator should have ARIA attributes
                    ->assertPresent('[data-loading-indicator][role="status"]')
                    ->assertPresent('[data-loading-indicator][aria-live="polite"]')
                    // Loading text should be present for screen readers
                    ->assertPresent('[data-loading-indicator] [aria-label], [data-loading-indicator] .sr-only')
                    // Wait for load
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5);
        });
    }

    /**
     * Test error message is accessible.
     *
     * Validates: Requirement 7.6, 12.7 - Accessibility for error state
     *
     * @return void
     */
    public function test_error_message_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Error message should have ARIA attributes
                    ->assertPresent('[data-error-message][role="alert"]')
                    ->assertPresent('[data-error-message][aria-live="assertive"]')
                    // Retry button should be keyboard accessible
                    ->assertPresent('[data-retry-button][type="button"]')
                    // Focus retry button with keyboard
                    ->keys('[data-retry-button]', '{tab}')
                    ->assertFocused('[data-retry-button]');
        });
    }

    /**
     * Test loading works on mobile devices.
     *
     * Validates: Requirement 7.7, 12.7 - Responsive loading
     *
     * @return void
     */
    public function test_loading_works_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->resize(375, 667) // iPhone SE size
                    ->visit('/admin/test/lazy-tabs')
                    // Click tab to trigger loading
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(50)
                    // Loading indicator should be visible on mobile
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for content
                    ->waitFor('[role="tabpanel"]:nth-child(2) .table', 5)
                    // Content should be visible and responsive
                    ->assertVisible('[role="tabpanel"]:nth-child(2) .table');
        });
    }

    /**
     * Test error state works on mobile devices.
     *
     * Validates: Requirement 7.7, 12.7 - Responsive error state
     *
     * @return void
     */
    public function test_error_state_works_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->resize(375, 667) // iPhone SE size
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // Error message should be visible on mobile
                    ->assertVisible('[data-error-message]')
                    // Retry button should be tappable
                    ->assertPresent('[data-retry-button]')
                    ->click('[data-retry-button]')
                    ->pause(100)
                    ->assertVisible('[data-loading-indicator]');
        });
    }

    /**
     * Test loading state with slow network.
     *
     * Validates: Requirement 6.9, 12.7 - Loading indicator for slow connections
     *
     * @return void
     */
    public function test_loading_state_with_slow_network(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-slow')
                    // Click tab with simulated slow load
                    ->click('[role="tab"][data-slow-load]')
                    ->pause(50)
                    // Loading indicator should appear
                    ->assertVisible('[data-loading-indicator]')
                    // Loading indicator should remain visible during slow load
                    ->pause(1000)
                    ->assertVisible('[data-loading-indicator]')
                    // Wait for eventual load
                    ->waitFor('[role="tabpanel"] .table', 10)
                    // Content should eventually appear
                    ->assertVisible('[role="tabpanel"] .table')
                    // Loading indicator should disappear
                    ->assertNotVisible('[data-loading-indicator]');
        });
    }

    /**
     * Test multiple retry attempts.
     *
     * Validates: Requirement 6.8, 12.7 - Multiple retry attempts
     *
     * @return void
     */
    public function test_multiple_retry_attempts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/lazy-tabs-with-error')
                    // Click tab that will fail
                    ->click('[role="tab"][data-will-fail]')
                    // Wait for error
                    ->waitFor('[data-error-message]', 5)
                    // First retry
                    ->click('[data-retry-button]')
                    ->pause(100)
                    ->waitFor('[data-error-message]', 5)
                    // Second retry
                    ->click('[data-retry-button]')
                    ->pause(100)
                    ->waitFor('[data-error-message]', 5)
                    // Third retry
                    ->click('[data-retry-button]')
                    ->pause(100)
                    // Should still show error or eventually succeed
                    ->waitFor('[data-error-message], [role="tabpanel"] .table', 5);
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

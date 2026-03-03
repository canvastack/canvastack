<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Dusk tests for Bi-Directional Filter Cascade UI.
 * 
 * Tests all UI components including loading indicators, cascade direction indicators,
 * empty states, smooth transitions, accessibility attributes, error notifications,
 * keyboard navigation, and dark mode styling.
 * 
 * @group browser
 * @group filter-cascade
 * @group ui
 */
class BiDirectionalCascadeUiTest extends DuskTestCase
{
    /**
     * Test that loading indicators appear during cascade operations.
     *
     * @return void
     */
    public function test_loading_indicators_appear_during_cascade(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select a filter to trigger cascade
                ->select('@filter-name', 'Carol Walker')
                
                // Verify loading spinner appears on affected filters
                ->assertVisible('.loading-spinner')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify loading spinner disappears
                ->assertMissing('.loading-spinner');
        });
    }

    /**
     * Test that cascade direction indicator shows affected filters.
     *
     * @return void
     */
    public function test_cascade_direction_indicator_shows_affected_filters(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select a filter to trigger cascade
                ->select('@filter-name', 'Carol Walker')
                
                // Verify cascade indicator appears
                ->assertVisible('.cascade-indicator')
                
                // Verify affected filters are listed
                ->assertSeeIn('.cascade-indicator', 'email')
                ->assertSeeIn('.cascade-indicator', 'created_at')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify cascade indicator disappears
                ->assertMissing('.cascade-indicator');
        });
    }

    /**
     * Test that empty state appears when no options are available.
     *
     * @return void
     */
    public function test_empty_state_appears_when_no_options(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select filters that result in no options
                ->select('@filter-name', 'Nonexistent User')
                ->pause(500)
                
                // Verify empty state appears
                ->assertVisible('.empty-state')
                ->assertSeeIn('.empty-state', 'No options available')
                ->assertSeeIn('.empty-state', 'Try adjusting other filters');
        });
    }

    /**
     * Test that smooth transitions work during filter updates.
     *
     * @return void
     */
    public function test_smooth_transitions_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select a filter
                ->select('@filter-name', 'Carol Walker')
                
                // Verify filter has transition class
                ->assertHasClass('@filter-email', 'filter-select')
                
                // Verify disabled state has correct styling
                ->assertAttribute('@filter-email', 'disabled', 'true')
                ->assertHasClass('@filter-email', 'opacity-50')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify filter is enabled again
                ->assertAttributeMissing('@filter-email', 'disabled');
        });
    }

    /**
     * Test that accessibility attributes are present.
     *
     * @return void
     */
    public function test_accessibility_attributes_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Verify ARIA labels
                ->assertAttribute('@filter-name', 'aria-label', 'Name')
                ->assertAttribute('@filter-email', 'aria-label', 'Email')
                ->assertAttribute('@filter-created_at', 'aria-label', 'Created Date')
                
                // Select a filter to trigger cascade
                ->select('@filter-name', 'Carol Walker')
                
                // Verify aria-busy attribute during loading
                ->assertAttribute('@filter-email', 'aria-busy', 'true')
                
                // Verify aria-describedby points to loading message
                ->assertAttribute('@filter-email', 'aria-describedby', 'loading-email')
                
                // Verify loading message exists for screen readers
                ->assertVisible('#loading-email')
                ->assertAttribute('#loading-email', 'role', 'status')
                ->assertAttribute('#loading-email', 'aria-live', 'polite')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify aria-busy is removed
                ->assertAttributeMissing('@filter-email', 'aria-busy');
        });
    }

    /**
     * Test that error notification appears on cascade failure.
     *
     * @return void
     */
    public function test_error_notification_appears_on_failure(): void
    {
        $this->browse(function (Browser $browser) {
            // Simulate network failure by disconnecting
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Disconnect network (this will cause API to fail)
                ->script('window.navigator.onLine = false;');
            
            // Select a filter to trigger cascade
            $browser->select('@filter-name', 'Carol Walker')
                ->pause(1000)
                
                // Verify error notification appears
                ->assertVisible('.notification-error')
                ->assertSeeIn('.notification-error', 'Failed to update filters')
                
                // Verify previous options are preserved
                ->assertSelectHasOptions('@filter-email', ['test@example.com'])
                
                // Reconnect network
                ->script('window.navigator.onLine = true;');
        });
    }

    /**
     * Test that keyboard navigation works correctly.
     *
     * @return void
     */
    public function test_keyboard_navigation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Tab to first filter
                ->keys('@filter-modal', '{tab}')
                ->assertFocused('@filter-name')
                
                // Tab to second filter
                ->keys('@filter-modal', '{tab}')
                ->assertFocused('@filter-email')
                
                // Tab to third filter
                ->keys('@filter-modal', '{tab}')
                ->assertFocused('@filter-created_at')
                
                // Shift+Tab back to second filter
                ->keys('@filter-modal', ['{shift}', '{tab}'])
                ->assertFocused('@filter-email')
                
                // Select option with keyboard
                ->keys('@filter-email', '{down}', '{enter}')
                ->pause(500)
                
                // Verify cascade triggered
                ->assertVisible('.loading-spinner');
        });
    }

    /**
     * Test that dark mode styling is correct.
     *
     * @return void
     */
    public function test_dark_mode_styling_correct(): void
    {
        $this->browse(function (Browser $browser) {
            // Enable dark mode
            $browser->visit('/test/table')
                ->script('document.documentElement.classList.add("dark");');
            
            $browser->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Verify dark mode classes are applied
                ->assertHasClass('@filter-modal', 'dark:bg-gray-900')
                
                // Verify filter inputs have dark mode styling
                ->assertHasClass('@filter-name', 'dark:bg-gray-800')
                ->assertHasClass('@filter-name', 'dark:text-gray-100')
                ->assertHasClass('@filter-name', 'dark:border-gray-700')
                
                // Select a filter to trigger cascade
                ->select('@filter-name', 'Carol Walker')
                ->pause(500)
                
                // Verify cascade indicator has dark mode styling
                ->assertVisible('.cascade-indicator')
                ->assertHasClass('.cascade-indicator', 'dark:bg-blue-900/20')
                ->assertHasClass('.cascade-indicator', 'dark:border-blue-800')
                ->assertHasClass('.cascade-indicator', 'dark:text-blue-100')
                
                // Verify empty state has dark mode styling
                ->select('@filter-name', 'Nonexistent User')
                ->pause(500)
                ->assertVisible('.empty-state')
                ->assertHasClass('.empty-state', 'dark:bg-gray-800')
                ->assertHasClass('.empty-state', 'dark:text-gray-400');
        });
    }

    /**
     * Test bi-directional cascade: select date first (reverse cascade).
     *
     * @return void
     */
    public function test_bidirectional_cascade_date_first(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select date first (reverse cascade)
                ->click('@filter-created_at')
                ->keys('@filter-created_at', '2026-03-01')
                ->pause(500)
                
                // Verify loading indicators appeared
                ->assertVisible('.loading-spinner')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify name filter updated (upstream)
                ->assertSelectHasOptions('@filter-name', ['Carol Walker'])
                
                // Verify email filter updated (upstream)
                ->assertSelectHasOptions('@filter-email', ['carol@example.com'])
                
                // Verify cascade indicator showed affected filters
                ->assertSeeIn('.cascade-indicator', 'name')
                ->assertSeeIn('.cascade-indicator', 'email');
        });
    }

    /**
     * Test bi-directional cascade: select name in middle.
     *
     * @return void
     */
    public function test_bidirectional_cascade_name_in_middle(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select name in middle (bi-directional cascade)
                ->select('@filter-name', 'Carol Walker')
                ->pause(500)
                
                // Verify loading indicators appeared
                ->assertVisible('.loading-spinner')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify email filter updated (downstream)
                ->assertSelectHasOptions('@filter-email', ['carol@example.com'])
                
                // Verify date filter updated (downstream)
                ->assertValue('@filter-created_at', '2026-03-01')
                
                // Verify cascade indicator showed affected filters
                ->assertSeeIn('.cascade-indicator', 'email')
                ->assertSeeIn('.cascade-indicator', 'created_at');
        });
    }

    /**
     * Test that cascade respects user's motion preferences.
     *
     * @return void
     */
    public function test_cascade_respects_motion_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            // Enable prefers-reduced-motion
            $browser->visit('/test/table')
                ->script('
                    const style = document.createElement("style");
                    style.textContent = "@media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }";
                    document.head.appendChild(style);
                ');
            
            $browser->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select a filter
                ->select('@filter-name', 'Carol Walker')
                ->pause(500)
                
                // Verify cascade still works (functionality not affected)
                ->assertSelectHasOptions('@filter-email', ['carol@example.com'])
                
                // Verify animations are disabled
                ->assertScript('
                    const element = document.querySelector(".cascade-indicator");
                    const style = window.getComputedStyle(element);
                    return style.animation === "none";
                ');
        });
    }

    /**
     * Test that cascade works with multiple tables on same page.
     *
     * @return void
     */
    public function test_cascade_works_with_multiple_tables(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/multiple-tables')
                
                // Open first table's filter modal
                ->click('@filter-button-table1')
                ->waitFor('@filter-modal-table1')
                
                // Select filter in first table
                ->select('@filter-name-table1', 'Carol Walker')
                ->pause(500)
                
                // Verify cascade works in first table
                ->assertSelectHasOptions('@filter-email-table1', ['carol@example.com'])
                
                // Close first modal
                ->click('@close-modal-table1')
                
                // Open second table's filter modal
                ->click('@filter-button-table2')
                ->waitFor('@filter-modal-table2')
                
                // Select filter in second table
                ->select('@filter-name-table2', 'John Doe')
                ->pause(500)
                
                // Verify cascade works in second table
                ->assertSelectHasOptions('@filter-email-table2', ['john@example.com'])
                
                // Verify first table's filters are not affected
                ->click('@filter-button-table1')
                ->waitFor('@filter-modal-table1')
                ->assertSelected('@filter-name-table1', 'Carol Walker');
        });
    }

    /**
     * Test that cascade indicator updates in real-time.
     *
     * @return void
     */
    public function test_cascade_indicator_updates_realtime(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select first filter
                ->select('@filter-name', 'Carol Walker')
                
                // Verify cascade indicator appears immediately
                ->assertVisible('.cascade-indicator')
                ->assertSeeIn('.cascade-indicator', 'Updating filters')
                
                // Verify affected filters are listed
                ->assertSeeIn('.cascade-indicator', 'email')
                ->assertSeeIn('.cascade-indicator', 'created_at')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify cascade indicator disappears
                ->assertMissing('.cascade-indicator')
                
                // Select another filter
                ->select('@filter-email', 'carol@example.com')
                
                // Verify cascade indicator appears again
                ->assertVisible('.cascade-indicator')
                ->assertSeeIn('.cascade-indicator', 'created_at')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify cascade indicator disappears
                ->assertMissing('.cascade-indicator');
        });
    }

    /**
     * Test that loading spinner appears on correct filters.
     *
     * @return void
     */
    public function test_loading_spinner_appears_on_correct_filters(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select name filter
                ->select('@filter-name', 'Carol Walker')
                
                // Verify loading spinner appears on email filter (downstream)
                ->assertVisible('@filter-email .loading-spinner')
                
                // Verify loading spinner appears on date filter (downstream)
                ->assertVisible('@filter-created_at .loading-spinner')
                
                // Verify loading spinner does NOT appear on name filter (changed filter)
                ->assertMissing('@filter-name .loading-spinner')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify all loading spinners disappear
                ->assertMissing('.loading-spinner');
        });
    }

    /**
     * Test that filters are disabled during cascade.
     *
     * @return void
     */
    public function test_filters_disabled_during_cascade(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select a filter
                ->select('@filter-name', 'Carol Walker')
                
                // Verify affected filters are disabled
                ->assertAttribute('@filter-email', 'disabled', 'true')
                ->assertAttribute('@filter-created_at', 'disabled', 'true')
                
                // Wait for cascade to complete
                ->pause(500)
                
                // Verify filters are enabled again
                ->assertAttributeMissing('@filter-email', 'disabled')
                ->assertAttributeMissing('@filter-created_at', 'disabled');
        });
    }

    /**
     * Test that cascade works with i18n (internationalization).
     *
     * @return void
     */
    public function test_cascade_works_with_i18n(): void
    {
        $this->browse(function (Browser $browser) {
            // Switch to Indonesian locale
            $browser->visit('/locale/switch')
                ->select('@locale-selector', 'id')
                ->click('@locale-submit')
                
                // Visit table page
                ->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Verify Indonesian translations
                ->assertSeeIn('.cascade-indicator', 'Memperbarui filter')
                
                // Select a filter
                ->select('@filter-name', 'Carol Walker')
                ->pause(500)
                
                // Verify empty state uses Indonesian translation
                ->select('@filter-name', 'Nonexistent User')
                ->pause(500)
                ->assertSeeIn('.empty-state', 'Tidak ada opsi tersedia')
                ->assertSeeIn('.empty-state', 'Coba sesuaikan filter lain');
        });
    }

    /**
     * Test that cascade preserves filter values in session.
     *
     * @return void
     */
    public function test_cascade_preserves_filter_values_in_session(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/table')
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Select filters
                ->select('@filter-name', 'Carol Walker')
                ->pause(500)
                ->select('@filter-email', 'carol@example.com')
                ->pause(500)
                
                // Apply filters
                ->click('@apply-filters')
                ->waitUntilMissing('@filter-modal')
                
                // Reload page
                ->refresh()
                
                // Open filter modal
                ->click('@filter-button')
                ->waitFor('@filter-modal')
                
                // Verify filter values are restored
                ->assertSelected('@filter-name', 'Carol Walker')
                ->assertSelected('@filter-email', 'carol@example.com');
        });
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Laravel\Dusk\Browser;
use Canvastack\Canvastack\Tests\DuskTestCase;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;

/**
 * Browser test for cascading filter functionality.
 * 
 * Tests the end-to-end cascading filter behavior in the browser.
 */
class CascadingFilterTest extends DuskTestCase
{
    /**
     * Test that parent filter change triggers child filter update.
     *
     * @return void
     */
    public function test_parent_filter_change_triggers_child_filter_update(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    ->assertVisible('@filter-modal')
                    
                    // Select parent filter
                    ->select('@filter-period', '2025-04')
                    ->pause(500)
                    
                    // Verify child filter shows loading state
                    ->assertVisible('@filter-cor .loading')
                    ->pause(1000)
                    
                    // Verify child filter options are loaded
                    ->assertSelectHasOptions('@filter-cor', ['A', 'B', 'C'])
                    ->assertMissing('@filter-cor .loading');
        });
    }

    /**
     * Test that multiple cascading levels work correctly.
     *
     * @return void
     */
    public function test_multiple_cascading_levels_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Level 1: Select period
                    ->select('@filter-period', '2025-04')
                    ->pause(1000)
                    
                    // Level 2: Select COR
                    ->select('@filter-cor', 'A')
                    ->pause(1000)
                    
                    // Verify region filter is updated
                    ->assertSelectHasOptions('@filter-region', ['WEST REGION', 'CENTRAL SUMATRA'])
                    
                    // Level 3: Select region
                    ->select('@filter-region', 'WEST REGION')
                    ->pause(1000)
                    
                    // Verify cluster filter is updated
                    ->assertSelectHasOptions('@filter-cluster', ['JAKARTA', 'BANDUNG']);
        });
    }

    /**
     * Test that child filter values are cleared when parent changes.
     *
     * @return void
     */
    public function test_child_filter_values_cleared_when_parent_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Set initial values
                    ->select('@filter-period', '2025-04')
                    ->pause(1000)
                    ->select('@filter-cor', 'A')
                    ->pause(1000)
                    ->select('@filter-region', 'WEST REGION')
                    ->pause(1000)
                    
                    // Change parent filter
                    ->select('@filter-period', '2025-05')
                    ->pause(1000)
                    
                    // Verify child filters are cleared
                    ->assertSelected('@filter-cor', '')
                    ->assertSelected('@filter-region', '')
                    ->assertSelected('@filter-cluster', '');
        });
    }

    /**
     * Test that auto-submit filters trigger immediate application.
     *
     * @return void
     */
    public function test_auto_submit_filters_trigger_immediate_application(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters-auto-submit')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Select filter with auto-submit
                    ->select('@filter-period', '2025-04')
                    ->pause(2000)
                    
                    // Verify modal is closed (auto-submitted)
                    ->assertMissing('@filter-modal')
                    
                    // Verify filter badge shows active count
                    ->assertSeeIn('@filter-button .badge', '1');
        });
    }

    /**
     * Test that manual submit filters wait for apply button.
     *
     * @return void
     */
    public function test_manual_submit_filters_wait_for_apply_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Select filter without auto-submit
                    ->select('@filter-cluster', 'JAKARTA')
                    ->pause(500)
                    
                    // Verify modal is still open
                    ->assertVisible('@filter-modal')
                    
                    // Click apply button
                    ->click('@apply-filter')
                    ->pause(1000)
                    
                    // Verify modal is closed
                    ->assertMissing('@filter-modal')
                    
                    // Verify filter badge shows active count
                    ->assertSeeIn('@filter-button .badge', '1');
        });
    }

    /**
     * Test that loading indicators are shown during option loading.
     *
     * @return void
     */
    public function test_loading_indicators_shown_during_option_loading(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Select parent filter
                    ->select('@filter-period', '2025-04')
                    
                    // Immediately check for loading indicator
                    ->assertVisible('@filter-cor .loading')
                    ->assertSee('Loading options...')
                    
                    // Wait for loading to complete
                    ->pause(1000)
                    ->assertMissing('@filter-cor .loading');
        });
    }

    /**
     * Test that error handling works for failed option loading.
     *
     * @return void
     */
    public function test_error_handling_for_failed_option_loading(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters-error')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Select parent filter (will trigger error)
                    ->select('@filter-period', 'invalid')
                    ->pause(1000)
                    
                    // Verify error notification is shown
                    ->assertSee('Failed to load filter options')
                    
                    // Verify child filter is not disabled
                    ->assertEnabled('@filter-cor');
        });
    }

    /**
     * Test that filters work with keyboard navigation.
     *
     * @return void
     */
    public function test_filters_work_with_keyboard_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Use keyboard to navigate
                    ->keys('@filter-period', ['{tab}'])
                    ->keys('@filter-cor', ['{tab}'])
                    ->keys('@filter-region', ['{tab}'])
                    
                    // Verify focus is on cluster filter
                    ->assertFocused('@filter-cluster');
        });
    }

    /**
     * Test that filters work on mobile devices.
     *
     * @return void
     */
    public function test_filters_work_on_mobile_devices(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone size
                    ->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Verify modal is full width on mobile
                    ->assertVisible('@filter-modal')
                    
                    // Select filters
                    ->select('@filter-period', '2025-04')
                    ->pause(1000)
                    ->select('@filter-cor', 'A')
                    ->pause(1000)
                    
                    // Apply filters
                    ->click('@apply-filter')
                    ->pause(1000)
                    
                    // Verify modal is closed
                    ->assertMissing('@filter-modal');
        });
    }

    /**
     * Test that filters persist across page reloads.
     *
     * @return void
     */
    public function test_filters_persist_across_page_reloads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Set filters
                    ->select('@filter-period', '2025-04')
                    ->pause(1000)
                    ->select('@filter-cor', 'A')
                    ->pause(1000)
                    
                    // Apply filters
                    ->click('@apply-filter')
                    ->pause(1000)
                    
                    // Reload page
                    ->refresh()
                    ->pause(1000)
                    
                    // Verify filter badge shows active count
                    ->assertSeeIn('@filter-button .badge', '2')
                    
                    // Open modal and verify values are restored
                    ->click('@filter-button')
                    ->pause(300)
                    ->assertSelected('@filter-period', '2025-04')
                    ->assertSelected('@filter-cor', 'A');
        });
    }

    /**
     * Test that clear filters button works correctly.
     *
     * @return void
     */
    public function test_clear_filters_button_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/test/cascading-filters')
                    ->click('@filter-button')
                    ->pause(300)
                    
                    // Set filters
                    ->select('@filter-period', '2025-04')
                    ->pause(1000)
                    ->select('@filter-cor', 'A')
                    ->pause(1000)
                    
                    // Apply filters
                    ->click('@apply-filter')
                    ->pause(1000)
                    
                    // Verify badge shows active count
                    ->assertSeeIn('@filter-button .badge', '2')
                    
                    // Open modal and clear filters
                    ->click('@filter-button')
                    ->pause(300)
                    ->click('@clear-filter')
                    ->pause(1000)
                    
                    // Verify badge is hidden
                    ->assertMissing('@filter-button .badge');
        });
    }
}


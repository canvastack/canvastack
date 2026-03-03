<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Canvastack\Canvastack\Tests\DuskTestCase;
use Laravel\Dusk\Browser;

/**
 * Browser test for Filter Actions functionality.
 * 
 * Tests the apply filter, clear filter, auto-submit, and filter state display in a real browser.
 */
class FilterActionsTest extends DuskTestCase
{
    /**
     * Test that apply filter button works.
     */
    public function test_apply_filter_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1'); // Badge count
        });
    }
    
    /**
     * Test that apply filter button shows loading state.
     */
    public function test_apply_filter_button_shows_loading_state(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->assertSee(__('ui.filter.applying'))
                    ->waitUntilMissing('@filter-modal');
        });
    }
    
    /**
     * Test that apply filter button is disabled during apply.
     */
    public function test_apply_filter_button_is_disabled_during_apply(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->assertDisabled('@apply-filter')
                    ->waitUntilMissing('@filter-modal');
        });
    }
    
    /**
     * Test that clear filter button works.
     */
    public function test_clear_filter_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            // First apply a filter
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1'); // Badge count
            
            // Then clear it
            $browser->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->click('@clear-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertDontSee('1'); // Badge should be hidden
        });
    }
    
    /**
     * Test that clear filter button is disabled during apply.
     */
    public function test_clear_filter_button_is_disabled_during_apply(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->assertDisabled('@clear-filter')
                    ->waitUntilMissing('@filter-modal');
        });
    }
    
    /**
     * Test that auto-submit applies filter immediately.
     */
    public function test_auto_submit_applies_filter_immediately(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal-autosubmit')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->pause(500) // Wait for auto-submit
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1'); // Badge count
        });
    }
    
    /**
     * Test that filter state badge is displayed.
     */
    public function test_filter_state_badge_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->assertDontSee('1') // Badge hidden initially
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1'); // Badge visible with count
        });
    }
    
    /**
     * Test that filter state badge shows correct count.
     */
    public function test_filter_state_badge_shows_correct_count(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->select('@filter-role', 'admin')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('2'); // Badge shows 2 active filters
        });
    }
    
    /**
     * Test that filter state badge is hidden when no filters.
     */
    public function test_filter_state_badge_is_hidden_when_no_filters(): void
    {
        $this->browse(function (Browser $browser) {
            // Apply filter first
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1');
            
            // Clear filter
            $browser->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->click('@clear-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertDontSee('1'); // Badge hidden
        });
    }
    
    /**
     * Test that filter state badge has smooth transitions.
     */
    public function test_filter_state_badge_has_smooth_transitions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->pause(300) // Wait for transition
                    ->assertSee('1');
        });
    }
    
    /**
     * Test that apply filter shows success notification.
     */
    public function test_apply_filter_shows_success_notification(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee(__('ui.filter.applied_successfully'));
        });
    }
    
    /**
     * Test that apply filter reloads DataTable.
     */
    public function test_apply_filter_reloads_datatable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->pause(500) // Wait for DataTable reload
                    ->assertSee('Filtered results'); // Verify table updated
        });
    }
    
    /**
     * Test that multiple filters can be applied.
     */
    public function test_multiple_filters_can_be_applied(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->select('@filter-role', 'admin')
                    ->type('@filter-name', 'John')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('3'); // Badge shows 3 active filters
        });
    }
    
    /**
     * Test that empty filter values are not counted.
     */
    public function test_empty_filter_values_are_not_counted(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->select('@filter-role', '') // Empty value
                    ->type('@filter-name', '') // Empty value
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1'); // Only 1 active filter
        });
    }
    
    /**
     * Test that filter state persists across page reloads.
     */
    public function test_filter_state_persists_across_page_reloads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-modal')
                    ->click('@filter-button')
                    ->waitFor('@filter-modal')
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->waitUntilMissing('@filter-modal')
                    ->assertSee('1')
                    ->refresh()
                    ->assertSee('1'); // Badge still shows after reload
        });
    }
}


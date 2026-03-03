<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Canvastack\Canvastack\Tests\DuskTestCase;
use Laravel\Dusk\Browser;

/**
 * Browser test for Filter Form Component.
 */
class FilterFormTest extends DuskTestCase
{
    /**
     * Test that filter modal opens when button is clicked.
     *
     * @return void
     */
    public function test_filter_modal_opens_on_button_click(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->assertDontSee('@filter-modal')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-modal')
                    ->assertSee('Filter Data');
        });
    }

    /**
     * Test that filter modal closes when close button is clicked.
     *
     * @return void
     */
    public function test_filter_modal_closes_on_close_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-modal')
                    ->click('button[aria-label="Close"]')
                    ->pause(500)
                    ->assertDontSee('@filter-modal');
        });
    }

    /**
     * Test that filter modal closes when clicking outside.
     *
     * @return void
     */
    public function test_filter_modal_closes_on_click_outside(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-modal')
                    ->click('.fixed.inset-0.bg-black')
                    ->pause(500)
                    ->assertDontSee('@filter-modal');
        });
    }

    /**
     * Test that selectbox filter renders correctly.
     *
     * @return void
     */
    public function test_selectbox_filter_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-status')
                    ->assertSee('Status')
                    ->assertSelectHasOptions('@filter-status', ['', 'active', 'inactive']);
        });
    }

    /**
     * Test that inputbox filter renders correctly.
     *
     * @return void
     */
    public function test_inputbox_filter_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-name')
                    ->assertSee('Name')
                    ->assertInputValue('@filter-name', '');
        });
    }

    /**
     * Test that datebox filter renders correctly.
     *
     * @return void
     */
    public function test_datebox_filter_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-created_at')
                    ->assertSee('Created Date')
                    ->assertAttribute('@filter-created_at', 'type', 'date');
        });
    }

    /**
     * Test that user can select filter value.
     *
     * @return void
     */
    public function test_user_can_select_filter_value(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->assertSelected('@filter-status', 'active');
        });
    }

    /**
     * Test that user can type in input filter.
     *
     * @return void
     */
    public function test_user_can_type_in_input_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->type('@filter-name', 'John Doe')
                    ->assertInputValue('@filter-name', 'John Doe');
        });
    }

    /**
     * Test that user can select date in datebox filter.
     *
     * @return void
     */
    public function test_user_can_select_date(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->type('@filter-created_at', '2024-02-26')
                    ->assertInputValue('@filter-created_at', '2024-02-26');
        });
    }

    /**
     * Test that apply button submits filters.
     *
     * @return void
     */
    public function test_apply_button_submits_filters(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->pause(1000)
                    ->assertDontSee('@filter-modal');
        });
    }

    /**
     * Test that clear button clears all filters.
     *
     * @return void
     */
    public function test_clear_button_clears_filters(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->type('@filter-name', 'John')
                    ->click('button:contains("Clear")')
                    ->pause(1000)
                    ->assertDontSee('@filter-modal');
        });
    }

    /**
     * Test that active filter count badge shows.
     *
     * @return void
     */
    public function test_active_filter_count_badge_shows(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->pause(1000)
                    ->assertSeeIn('.badge.badge-error', '1');
        });
    }

    /**
     * Test that loading indicator shows when loading options.
     *
     * @return void
     */
    public function test_loading_indicator_shows(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form-cascading')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-province', 'jawa-barat')
                    ->pause(100)
                    ->assertVisible('.loading.loading-spinner');
        });
    }

    /**
     * Test that cascading filters update child options.
     *
     * @return void
     */
    public function test_cascading_filters_update_child_options(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form-cascading')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-province', 'jawa-barat')
                    ->pause(1000)
                    ->assertSelectHasOptions('@filter-city', ['', 'bandung', 'bekasi']);
        });
    }

    /**
     * Test that auto-submit filters apply automatically.
     *
     * @return void
     */
    public function test_auto_submit_filters_apply_automatically(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form-auto-submit')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->pause(1000)
                    ->assertDontSee('@filter-modal');
        });
    }

    /**
     * Test that filter form is responsive on mobile.
     *
     * @return void
     */
    public function test_filter_form_is_responsive(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE size
                    ->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-modal')
                    ->assertSee('Status')
                    ->assertSee('Name');
        });
    }

    /**
     * Test that filter form works in dark mode.
     *
     * @return void
     */
    public function test_filter_form_works_in_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->script('document.documentElement.classList.add("dark")');
            
            $browser->click('@filter-button')
                    ->pause(500)
                    ->assertVisible('@filter-modal')
                    ->assertSee('Status');
        });
    }

    /**
     * Test that filter form shows error on failed request.
     *
     * @return void
     */
    public function test_filter_form_shows_error_on_failed_request(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form-error')
                    ->click('@filter-button')
                    ->pause(500)
                    ->select('@filter-status', 'active')
                    ->click('@apply-filter')
                    ->pause(1000)
                    ->assertSee('Error'); // Assuming error notification shows
        });
    }

    /**
     * Test that disabled state prevents interaction.
     *
     * @return void
     */
    public function test_disabled_state_prevents_interaction(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/test/filter-form')
                    ->click('@filter-button')
                    ->pause(500)
                    ->click('@apply-filter')
                    ->pause(100)
                    ->assertAttribute('@apply-filter', 'disabled', 'true');
        });
    }
}

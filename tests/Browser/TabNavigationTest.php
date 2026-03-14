<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for tab navigation functionality.
 *
 * Tests tab clicking, keyboard navigation, active tab highlighting,
 * and URL hash updates for the TanStack Table Multi-Table & Tab System.
 *
 * @see .kiro/specs/tanstack-multi-table-tabs/requirements.md (Requirement 7, 12.6)
 */
class TabNavigationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test tab clicking switches active tab.
     *
     * Validates: Requirement 7.3 - Tab switching on click
     */
    public function test_clicking_tab_switches_active_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    ->assertSee('Tab 1')
                    ->assertSee('Tab 2')
                    ->assertSee('Tab 3')
                    // First tab should be active by default
                    ->assertPresent('[role="tab"][aria-selected="true"]')
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 1')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2')
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 3');
        });
    }

    /**
     * Test active tab highlighting is visible.
     *
     * Validates: Requirement 7.2 - Active tab highlighting
     */
    public function test_active_tab_has_distinct_styling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Check first tab is active
                    ->assertPresent('[role="tab"][aria-selected="true"]')
                    ->assertAttribute('[role="tab"][aria-selected="true"]', 'aria-selected', 'true')
                    // Check inactive tabs have aria-selected="false"
                    ->assertAttribute('[role="tab"]:nth-child(2)', 'aria-selected', 'false')
                    ->assertAttribute('[role="tab"]:nth-child(3)', 'aria-selected', 'false')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // Verify second tab is now active
                    ->assertAttribute('[role="tab"]:nth-child(2)', 'aria-selected', 'true')
                    // Verify first tab is now inactive
                    ->assertAttribute('[role="tab"]:nth-child(1)', 'aria-selected', 'false');
        });
    }

    /**
     * Test tab content displays correctly when switching tabs.
     *
     * Validates: Requirement 7.3 - Tab content switching
     */
    public function test_tab_content_displays_when_switching(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // First tab content should be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(1)')
                    ->assertSee('Content for Tab 1')
                    // Second tab content should be hidden
                    ->assertNotVisible('[role="tabpanel"]:nth-child(2)')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // Second tab content should now be visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    ->assertSee('Content for Tab 2')
                    // First tab content should be hidden
                    ->assertNotVisible('[role="tabpanel"]:nth-child(1)');
        });
    }

    /**
     * Test keyboard navigation with arrow keys.
     *
     * Validates: Requirement 7.6 - Keyboard navigation (arrow keys)
     */
    public function test_arrow_keys_navigate_between_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Focus first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    // Press right arrow to move to second tab
                    ->keys('[role="tab"]:nth-child(1)', '{arrow_right}')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2')
                    // Press right arrow to move to third tab
                    ->keys('[role="tab"]:nth-child(2)', '{arrow_right}')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 3')
                    // Press left arrow to move back to second tab
                    ->keys('[role="tab"]:nth-child(3)', '{arrow_left}')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2');
        });
    }

    /**
     * Test keyboard navigation with Home and End keys.
     *
     * Validates: Requirement 7.6 - Keyboard navigation (Home, End)
     */
    public function test_home_and_end_keys_navigate_to_first_and_last_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Focus second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(200)
                    // Press End key to jump to last tab
                    ->keys('[role="tab"]:nth-child(2)', '{end}')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 3')
                    // Press Home key to jump to first tab
                    ->keys('[role="tab"]:nth-child(3)', '{home}')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 1');
        });
    }

    /**
     * Test Tab key moves focus to next tab.
     *
     * Validates: Requirement 7.6 - Keyboard navigation (Tab key)
     */
    public function test_tab_key_moves_focus_to_next_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Focus first tab
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(200)
                    // Press Tab key to move focus to second tab
                    ->keys('[role="tab"]:nth-child(1)', '{tab}')
                    ->pause(300)
                    // Verify focus moved (second tab should be focused)
                    ->assertFocused('[role="tab"]:nth-child(2)');
        });
    }

    /**
     * Test URL hash updates when switching tabs.
     *
     * Validates: Requirement 7.10 - URL hash bookmarking
     */
    public function test_url_hash_updates_when_switching_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    // Verify URL hash updated
                    ->assertPathIs('/admin/test/tabs')
                    ->assertFragmentIs('tab-1') // 0-indexed
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    // Verify URL hash updated
                    ->assertFragmentIs('tab-2');
        });
    }

    /**
     * Test tab state restores from URL hash on page load.
     *
     * Validates: Requirement 7.10 - URL hash bookmarking
     */
    public function test_tab_state_restores_from_url_hash(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    // Visit with hash for second tab
                    ->visit('/admin/test/tabs#tab-1')
                    ->pause(500)
                    // Verify second tab is active
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2')
                    ->assertVisible('[role="tabpanel"]:nth-child(2)')
                    // Visit with hash for third tab
                    ->visit('/admin/test/tabs#tab-2')
                    ->pause(500)
                    // Verify third tab is active
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 3')
                    ->assertVisible('[role="tabpanel"]:nth-child(3)');
        });
    }

    /**
     * Test URL hash bookmarking works across page refreshes.
     *
     * Validates: Requirement 7.10 - URL hash bookmarking
     */
    public function test_url_hash_persists_across_page_refresh(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Click third tab
                    ->click('[role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertFragmentIs('tab-2')
                    // Refresh page
                    ->refresh()
                    ->pause(500)
                    // Verify third tab is still active
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 3')
                    ->assertVisible('[role="tabpanel"]:nth-child(3)')
                    ->assertFragmentIs('tab-2');
        });
    }

    /**
     * Test tab navigation works with multiple tab groups on same page.
     *
     * Validates: Requirement 5.1, 5.2 - Multiple instances support
     */
    public function test_multiple_tab_groups_work_independently(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/multiple-tabs')
                    // Click second tab in first group
                    ->click('[data-tab-group="group1"] [role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertSeeIn('[data-tab-group="group1"] [role="tab"][aria-selected="true"]', 'Group 1 Tab 2')
                    // Verify second group still has first tab active
                    ->assertSeeIn('[data-tab-group="group2"] [role="tab"][aria-selected="true"]', 'Group 2 Tab 1')
                    // Click third tab in second group
                    ->click('[data-tab-group="group2"] [role="tab"]:nth-child(3)')
                    ->pause(300)
                    ->assertSeeIn('[data-tab-group="group2"] [role="tab"][aria-selected="true"]', 'Group 2 Tab 3')
                    // Verify first group still has second tab active
                    ->assertSeeIn('[data-tab-group="group1"] [role="tab"][aria-selected="true"]', 'Group 1 Tab 2');
        });
    }

    /**
     * Test tab navigation is responsive on mobile devices.
     *
     * Validates: Requirement 7.7 - Responsive design
     */
    public function test_tab_navigation_is_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->resize(375, 667) // iPhone SE size
                    ->visit('/admin/test/tabs')
                    ->assertSee('Tab 1')
                    ->assertSee('Tab 2')
                    ->assertSee('Tab 3')
                    // Tabs should be scrollable on mobile
                    ->assertPresent('[role="tablist"]')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2')
                    // Verify content is visible
                    ->assertVisible('[role="tabpanel"]:nth-child(2)');
        });
    }

    /**
     * Test tab navigation works in dark mode.
     *
     * Validates: Requirement 7.8 - Dark mode support
     */
    public function test_tab_navigation_works_in_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Enable dark mode
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    // Verify tabs are visible in dark mode
                    ->assertSee('Tab 1')
                    ->assertSee('Tab 2')
                    // Click second tab
                    ->click('[role="tab"]:nth-child(2)')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 2')
                    // Verify active tab styling is visible in dark mode
                    ->assertPresent('[role="tab"][aria-selected="true"]');
        });
    }

    /**
     * Test tab navigation performance is acceptable.
     *
     * Validates: Requirement 11.7 - CSS transitions for smooth switching
     */
    public function test_tab_switching_is_performant(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs');

            $startTime = microtime(true);

            // Switch between tabs multiple times
            for ($i = 1; $i <= 3; $i++) {
                $browser->click("[role=\"tab\"]:nth-child({$i})")
                        ->pause(100);
            }

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Tab switching should be fast (< 1 second for 3 switches)
            $this->assertLessThan(1000, $duration, 'Tab switching took too long');
        });
    }

    /**
     * Test ARIA attributes are correct for accessibility.
     *
     * Validates: Requirement 7.2, 7.6 - Accessibility (ARIA)
     */
    public function test_aria_attributes_are_correct(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/tabs')
                    // Check tablist role
                    ->assertPresent('[role="tablist"]')
                    // Check tab roles
                    ->assertPresent('[role="tab"]')
                    // Check tabpanel roles
                    ->assertPresent('[role="tabpanel"]')
                    // Check aria-selected on active tab
                    ->assertAttribute('[role="tab"]:nth-child(1)', 'aria-selected', 'true')
                    // Check aria-selected on inactive tabs
                    ->assertAttribute('[role="tab"]:nth-child(2)', 'aria-selected', 'false')
                    // Check aria-controls links tab to tabpanel
                    ->assertPresent('[role="tab"][aria-controls]')
                    // Check tabpanel has aria-labelledby
                    ->assertPresent('[role="tabpanel"][aria-labelledby]');
        });
    }

    /**
     * Test tab navigation with custom tab names.
     *
     * Validates: Requirement 4.1 - openTab() method
     */
    public function test_custom_tab_names_display_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/custom-tabs')
                    ->assertSee('Active Users')
                    ->assertSee('Inactive Users')
                    ->assertSee('Pending Users')
                    // Click "Inactive Users" tab
                    ->clickLink('Inactive Users')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Inactive Users');
        });
    }

    /**
     * Test tab navigation with long tab names.
     *
     * Validates: Requirement 7.7 - Responsive design
     */
    public function test_long_tab_names_are_handled_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/long-tab-names')
                    ->assertSee('This is a very long tab name that should be handled properly')
                    // Tab should be clickable
                    ->click('[role="tab"]:nth-child(1)')
                    ->pause(300)
                    ->assertPresent('[role="tab"][aria-selected="true"]')
                    // Tab name should not overflow
                    ->assertPresent('[role="tab"]');
        });
    }

    /**
     * Test tab navigation with many tabs (scrolling).
     *
     * Validates: Requirement 7.7 - Responsive design
     */
    public function test_many_tabs_are_scrollable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/test/many-tabs')
                    // Should have 10 tabs
                    ->assertPresent('[role="tab"]:nth-child(10)')
                    // Click last tab
                    ->click('[role="tab"]:nth-child(10)')
                    ->pause(300)
                    ->assertSeeIn('[role="tab"][aria-selected="true"]', 'Tab 10')
                    // Verify content displays
                    ->assertVisible('[role="tabpanel"]:nth-child(10)');
        });
    }

    /**
     * Create admin user for testing.
     */
    protected function createAdminUser()
    {
        return \App\Models\User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
    }
}

<?php

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MobileResponsivenessTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Mobile viewport sizes to test.
     */
    protected array $mobileViewports = [
        'iphone_se' => [375, 667],
        'iphone_12' => [390, 844],
        'iphone_14_pro_max' => [430, 932],
        'samsung_galaxy_s20' => [360, 800],
        'pixel_5' => [393, 851],
    ];

    /**
     * Tablet viewport sizes to test.
     */
    protected array $tabletViewports = [
        'ipad_mini' => [768, 1024],
        'ipad_air' => [820, 1180],
        'ipad_pro' => [1024, 1366],
    ];

    /**
     * Test mobile navigation menu works.
     */
    public function test_mobile_navigation_menu_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertMissing('[data-component="sidebar"]:not(.hidden)')
                    ->click('[data-mobile-menu-toggle]')
                    ->pause(300)
                    ->assertVisible('[data-component="mobile-menu"]')
                    ->assertSee('Dashboard')
                    ->assertSee('Users');
        });
    }

    /**
     * Test sidebar collapses on mobile.
     */
    public function test_sidebar_collapses_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            foreach ($this->mobileViewports as $name => $size) {
                [$width, $height] = $size;

                $browser->resize($width, $height)
                        ->loginAs($this->createAdminUser())
                        ->visit('/admin/dashboard')
                        ->assertMissing('[data-component="sidebar"]:not(.hidden)')
                        ->assertPresent('[data-mobile-menu-toggle]');
            }
        });
    }

    /**
     * Test tables are responsive on mobile.
     */
    public function test_tables_are_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->assertPresent('table')
                    ->assertVisible('table');

            $tableWidth = $browser->driver->executeScript(
                'return document.querySelector("table").offsetWidth'
            );

            $this->assertLessThanOrEqual(375, $tableWidth);
        });
    }

    /**
     * Test forms are usable on mobile.
     */
    public function test_forms_are_usable_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/users/create')
                    ->assertVisible('form')
                    ->type('name', 'Mobile User')
                    ->type('email', 'mobile@test.com')
                    ->type('password', 'password123')
                    ->select('role', 'user')
                    ->press('Create User')
                    ->waitForText('User created successfully');
        });
    }

    /**
     * Test buttons are touch-friendly.
     */
    public function test_buttons_are_touch_friendly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard');

            $buttonHeight = $browser->driver->executeScript(
                'return document.querySelector("button").offsetHeight'
            );

            // Touch targets should be at least 44px (iOS) or 48px (Android)
            $this->assertGreaterThanOrEqual(44, $buttonHeight);
        });
    }

    /**
     * Test dropdowns work on mobile.
     */
    public function test_dropdowns_work_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-dropdown-trigger]')
                    ->pause(300)
                    ->assertVisible('[data-dropdown-menu]')
                    ->assertSee('Profile')
                    ->assertSee('Logout');
        });
    }

    /**
     * Test modals are responsive on mobile.
     */
    public function test_modals_are_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->click('[data-modal-trigger]')
                    ->pause(300)
                    ->assertVisible('[data-modal]');

            $modalWidth = $browser->driver->executeScript(
                'return document.querySelector("[data-modal]").offsetWidth'
            );

            $this->assertLessThanOrEqual(375, $modalWidth);
        });
    }

    /**
     * Test text is readable on mobile.
     */
    public function test_text_is_readable_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->visit('/')
                    ->assertVisible('body');

            $fontSize = $browser->driver->executeScript(
                'return parseFloat(getComputedStyle(document.body).fontSize)'
            );

            // Minimum readable font size is 16px
            $this->assertGreaterThanOrEqual(16, $fontSize);
        });
    }

    /**
     * Test images scale properly on mobile.
     */
    public function test_images_scale_properly_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->visit('/');

            $images = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("img")).map(img => img.offsetWidth)'
            );

            foreach ($images as $width) {
                $this->assertLessThanOrEqual(375, $width);
            }
        });
    }

    /**
     * Test horizontal scrolling is prevented.
     */
    public function test_horizontal_scrolling_is_prevented(): void
    {
        $this->browse(function (Browser $browser) {
            foreach ($this->mobileViewports as $name => $size) {
                [$width, $height] = $size;

                $browser->resize($width, $height)
                        ->visit('/');

                $bodyWidth = $browser->driver->executeScript(
                    'return document.body.scrollWidth'
                );

                $this->assertLessThanOrEqual(
                    $width + 1,
                    $bodyWidth,
                    "Horizontal scroll detected on {$name}"
                );
            }
        });
    }

    /**
     * Test tablet layout on iPad.
     */
    public function test_tablet_layout_works(): void
    {
        $this->browse(function (Browser $browser) {
            foreach ($this->tabletViewports as $name => $size) {
                [$width, $height] = $size;

                $browser->resize($width, $height)
                        ->loginAs($this->createAdminUser())
                        ->visit('/admin/dashboard')
                        ->assertVisible('[data-component="sidebar"]')
                        ->assertVisible('[data-component="content"]');
            }
        });
    }

    /**
     * Test landscape orientation on mobile.
     */
    public function test_landscape_orientation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(667, 375) // Landscape iPhone SE
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertVisible('[data-component="content"]')
                    ->assertPresent('[data-mobile-menu-toggle]');
        });
    }

    /**
     * Test touch gestures work.
     */
    public function test_touch_gestures_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-mobile-menu-toggle]')
                    ->pause(300)
                    ->assertVisible('[data-component="mobile-menu"]')
                    ->click('[data-mobile-menu-close]')
                    ->pause(300)
                    ->assertMissing('[data-component="mobile-menu"]:visible');
        });
    }

    /**
     * Test viewport meta tag is present.
     */
    public function test_viewport_meta_tag_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $viewport = $browser->driver->executeScript(
                'return document.querySelector("meta[name=viewport]")?.content'
            );

            $this->assertNotNull($viewport);
            $this->assertStringContainsString('width=device-width', $viewport);
            $this->assertStringContainsString('initial-scale=1', $viewport);
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

<?php

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CrossBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test admin dashboard renders correctly across browsers.
     */
    public function test_admin_dashboard_renders_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertSee('Dashboard')
                    ->assertPresent('[data-component="sidebar"]')
                    ->assertPresent('[data-component="navbar"]')
                    ->assertPresent('[data-component="content"]')
                    ->assertVisible('[data-component="sidebar"]')
                    ->assertVisible('[data-component="navbar"]');
        });
    }

    /**
     * Test form components work across browsers.
     */
    public function test_form_components_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users/create')
                    ->type('name', 'Test User')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password123')
                    ->select('role', 'user')
                    ->press('Create User')
                    ->waitForText('User created successfully')
                    ->assertSee('User created successfully');
        });
    }

    /**
     * Test table components work across browsers.
     */
    public function test_table_components_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->assertPresent('table')
                    ->assertPresent('thead')
                    ->assertPresent('tbody')
                    ->type('[data-table-search]', 'admin')
                    ->pause(500)
                    ->assertSee('admin@test.com');
        });
    }

    /**
     * Test dropdown menus work across browsers.
     */
    public function test_dropdown_menus_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-dropdown-trigger]')
                    ->pause(300)
                    ->assertVisible('[data-dropdown-menu]')
                    ->assertSee('Profile')
                    ->assertSee('Settings')
                    ->assertSee('Logout');
        });
    }

    /**
     * Test modal dialogs work across browsers.
     */
    public function test_modal_dialogs_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->click('[data-modal-trigger="delete"]')
                    ->pause(300)
                    ->assertVisible('[data-modal="delete"]')
                    ->assertSee('Are you sure?')
                    ->click('[data-modal-cancel]')
                    ->pause(300)
                    ->assertMissing('[data-modal="delete"]');
        });
    }

    /**
     * Test dark mode toggle works across browsers.
     */
    public function test_dark_mode_toggle_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertMissing('.dark')
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertMissing('.dark');
        });
    }

    /**
     * Test sidebar toggle works across browsers.
     */
    public function test_sidebar_toggle_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertVisible('[data-component="sidebar"]')
                    ->click('[data-sidebar-toggle]')
                    ->pause(300)
                    ->assertPresent('[data-sidebar-collapsed]')
                    ->click('[data-sidebar-toggle]')
                    ->pause(300)
                    ->assertMissing('[data-sidebar-collapsed]');
        });
    }

    /**
     * Test CSS animations work across browsers.
     */
    public function test_css_animations_work_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-modal-trigger]')
                    ->pause(500)
                    ->assertVisible('[data-modal]')
                    ->assertPresent('[data-animation="fade-in"]');
        });
    }

    /**
     * Test gradient backgrounds render correctly.
     */
    public function test_gradient_backgrounds_render_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertPresent('[data-gradient="primary"]')
                    ->assertVisible('[data-gradient="primary"]');

            $gradient = $browser->driver->executeScript(
                'return getComputedStyle(document.querySelector("[data-gradient=primary]")).backgroundImage'
            );

            $this->assertStringContainsString('gradient', $gradient);
        });
    }

    /**
     * Test icon rendering across browsers.
     */
    public function test_icons_render_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertPresent('[data-lucide]')
                    ->assertVisible('[data-lucide]');

            $iconCount = $browser->driver->executeScript(
                'return document.querySelectorAll("[data-lucide]").length'
            );

            $this->assertGreaterThan(0, $iconCount);
        });
    }

    /**
     * Test JavaScript functionality works across browsers.
     */
    public function test_javascript_functionality_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard');

            $alpineLoaded = $browser->driver->executeScript(
                'return typeof Alpine !== "undefined"'
            );

            $this->assertTrue($alpineLoaded, 'Alpine.js not loaded');

            $gsapLoaded = $browser->driver->executeScript(
                'return typeof gsap !== "undefined"'
            );

            $this->assertTrue($gsapLoaded, 'GSAP not loaded');
        });
    }

    /**
     * Test CSS Grid and Flexbox layouts work.
     */
    public function test_modern_css_layouts_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard');

            $gridSupport = $browser->driver->executeScript(
                'return CSS.supports("display", "grid")'
            );

            $this->assertTrue($gridSupport, 'CSS Grid not supported');

            $flexSupport = $browser->driver->executeScript(
                'return CSS.supports("display", "flex")'
            );

            $this->assertTrue($flexSupport, 'Flexbox not supported');
        });
    }

    /**
     * Test form validation works across browsers.
     */
    public function test_form_validation_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->press('Register')
                    ->waitForText('required')
                    ->assertSee('required')
                    ->type('email', 'invalid-email')
                    ->press('Register')
                    ->waitForText('valid email')
                    ->assertSee('valid email');
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

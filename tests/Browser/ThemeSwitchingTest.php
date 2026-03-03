<?php

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ThemeSwitchingTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test theme switching in admin panel.
     */
    public function test_can_switch_theme_in_admin_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->assertSee('Theme Settings')
                    ->select('theme', 'ocean')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully')
                    ->assertSelected('theme', 'ocean')
                    ->assertPresent('[data-theme="ocean"]');
        });
    }

    /**
     * Test theme persistence across page loads.
     */
    public function test_theme_persists_across_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->select('theme', 'sunset')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully')
                    ->visit('/admin/dashboard')
                    ->assertPresent('[data-theme="sunset"]')
                    ->visit('/admin/users')
                    ->assertPresent('[data-theme="sunset"]');
        });
    }

    /**
     * Test theme preview functionality.
     */
    public function test_can_preview_theme_before_applying(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->click('[data-theme-preview="forest"]')
                    ->pause(500)
                    ->assertPresent('[data-theme="forest"]')
                    ->assertDontSee('Settings saved')
                    ->refresh()
                    ->assertMissing('[data-theme="forest"]');
        });
    }

    /**
     * Test theme switching with dark mode.
     */
    public function test_theme_switching_works_with_dark_mode(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->click('[data-dark-mode-toggle]')
                    ->pause(300)
                    ->assertPresent('.dark')
                    ->select('theme', 'gradient')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully')
                    ->assertPresent('.dark')
                    ->assertPresent('[data-theme="gradient"]');
        });
    }

    /**
     * Test multiple theme switches in succession.
     */
    public function test_can_switch_themes_multiple_times(): void
    {
        $this->browse(function (Browser $browser) {
            $themes = ['ocean', 'sunset', 'forest', 'gradient'];

            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings');

            foreach ($themes as $theme) {
                $browser->select('theme', $theme)
                        ->press('Save Settings')
                        ->waitForText('Settings saved successfully')
                        ->assertSelected('theme', $theme)
                        ->assertPresent("[data-theme=\"{$theme}\"]")
                        ->pause(500);
            }
        });
    }

    /**
     * Test theme switching in public frontend.
     */
    public function test_can_switch_theme_in_public_frontend(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->click('[data-theme-selector]')
                    ->pause(300)
                    ->click('[data-theme-option="ocean"]')
                    ->pause(500)
                    ->assertPresent('[data-theme="ocean"]')
                    ->refresh()
                    ->assertPresent('[data-theme="ocean"]');
        });
    }

    /**
     * Test theme CSS variables are applied correctly.
     */
    public function test_theme_css_variables_are_applied(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->select('theme', 'ocean')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully')
                    ->script('return getComputedStyle(document.documentElement).getPropertyValue("--color-primary")');

            $primaryColor = $browser->driver->executeScript(
                'return getComputedStyle(document.documentElement).getPropertyValue("--color-primary")'
            );

            $this->assertNotEmpty(trim($primaryColor));
        });
    }

    /**
     * Test theme switching performance.
     */
    public function test_theme_switching_is_performant(): void
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->select('theme', 'sunset')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully');

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $this->assertLessThan(2000, $duration, 'Theme switching took too long');
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

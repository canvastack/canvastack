<?php

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LocaleSwitchingTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test locale switching in admin panel.
     */
    public function test_can_switch_locale_in_admin_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertSee('Dashboard')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->assertSee('Dasbor')
                    ->assertDontSee('Dashboard');
        });
    }

    /**
     * Test locale persistence across page loads.
     */
    public function test_locale_persists_across_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->visit('/admin/users')
                    ->assertSee('Pengguna')
                    ->visit('/admin/settings')
                    ->assertSee('Pengaturan');
        });
    }

    /**
     * Test locale switching in public frontend.
     */
    public function test_can_switch_locale_in_public_frontend(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Home')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->assertSee('Beranda')
                    ->assertDontSee('Home');
        });
    }

    /**
     * Test form validation messages are translated.
     */
    public function test_validation_messages_are_translated(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->press('Register')
                    ->waitForText('wajib diisi')
                    ->assertSee('wajib diisi');
        });
    }

    /**
     * Test date formatting changes with locale.
     */
    public function test_date_formatting_changes_with_locale(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->assertSeeIn('[data-date-format]', '/')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->assertSeeIn('[data-date-format]', '/');
        });
    }

    /**
     * Test RTL layout activation for Arabic.
     */
    public function test_rtl_layout_activates_for_arabic(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertMissing('[dir="rtl"]')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="ar"]')
                    ->pause(500)
                    ->assertPresent('[dir="rtl"]')
                    ->assertPresent('.rtl');
        });
    }

    /**
     * Test switching between multiple locales.
     */
    public function test_can_switch_between_multiple_locales(): void
    {
        $this->browse(function (Browser $browser) {
            $locales = [
                'en' => 'Dashboard',
                'id' => 'Dasbor',
                'ar' => 'لوحة القيادة',
            ];

            $browser->loginAs($this->createAdminUser());

            foreach ($locales as $locale => $text) {
                $browser->visit('/admin/dashboard')
                        ->click('[data-locale-selector]')
                        ->pause(300)
                        ->click("[data-locale=\"{$locale}\"]")
                        ->pause(500)
                        ->assertSee($text);
            }
        });
    }

    /**
     * Test locale switching with theme changes.
     */
    public function test_locale_switching_works_with_theme_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/settings')
                    ->select('theme', 'ocean')
                    ->press('Save Settings')
                    ->waitForText('Settings saved successfully')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->pause(500)
                    ->assertSee('Pengaturan berhasil disimpan')
                    ->assertPresent('[data-theme="ocean"]');
        });
    }

    /**
     * Test locale switching performance.
     */
    public function test_locale_switching_is_performant(): void
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->click('[data-locale-selector]')
                    ->pause(300)
                    ->click('[data-locale="id"]')
                    ->waitForText('Dasbor');

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $this->assertLessThan(2000, $duration, 'Locale switching took too long');
        });
    }

    /**
     * Test browser locale detection.
     */
    public function test_browser_locale_is_detected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->executeScript("
                Object.defineProperty(navigator, 'language', {
                    get: function() { return 'id-ID'; }
                });
            ");

            $browser->visit('/')
                    ->pause(500)
                    ->assertSee('Beranda');
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

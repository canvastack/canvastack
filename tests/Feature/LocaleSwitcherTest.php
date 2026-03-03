<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/**
 * Locale Switcher Feature Tests.
 *
 * Tests the locale switching functionality including:
 * - Locale detection
 * - Locale persistence
 * - Browser locale detection
 * - Locale switching via POST request
 */
class LocaleSwitcherTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
        ]);
        Config::set('canvastack.localization.default_locale', 'en');
        Config::set('canvastack.localization.storage', 'session');

        // Register test route for locale switching
        Route::post('/locale/switch', function () {
            $locale = request()->input('locale');
            $localeManager = app(LocaleManager::class);

            if (!$localeManager->isAvailable($locale)) {
                return redirect()->back()->withErrors(['locale' => 'Invalid locale']);
            }

            $localeManager->setLocale($locale);

            return redirect()->back()->with('success', 'Locale changed successfully');
        })->name('locale.switch');
    }

    /** @test */
    public function it_can_get_available_locales()
    {
        $localeManager = new LocaleManager();
        $locales = $localeManager->getAvailableLocales();

        $this->assertIsArray($locales);
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('id', $locales);
    }

    /** @test */
    public function it_can_check_if_locale_is_available()
    {
        $localeManager = new LocaleManager();

        $this->assertTrue($localeManager->isAvailable('en'));
        $this->assertTrue($localeManager->isAvailable('id'));
        $this->assertFalse($localeManager->isAvailable('fr'));
    }

    /** @test */
    public function it_can_set_locale()
    {
        $localeManager = new LocaleManager();

        $result = $localeManager->setLocale('id');

        $this->assertTrue($result);
        $this->assertEquals('id', $localeManager->getLocale());
        $this->assertEquals('id', App::getLocale());
    }

    /** @test */
    public function it_cannot_set_unavailable_locale()
    {
        $localeManager = new LocaleManager();

        $result = $localeManager->setLocale('fr');

        $this->assertFalse($result);
        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_persists_locale_to_session()
    {
        Config::set('canvastack.localization.storage', 'session');

        $localeManager = new LocaleManager();
        $localeManager->setLocale('id');

        $this->assertEquals('id', Session::get('canvastack_locale'));
    }

    /** @test */
    public function it_can_get_locale_info()
    {
        $localeManager = new LocaleManager();

        $info = $localeManager->getLocaleInfo('en');

        $this->assertIsArray($info);
        $this->assertEquals('English', $info['name']);
        $this->assertEquals('English', $info['native']);
        $this->assertEquals('🇺🇸', $info['flag']);
    }

    /** @test */
    public function it_can_get_locale_name()
    {
        $localeManager = new LocaleManager();

        $this->assertEquals('English', $localeManager->getLocaleName('en'));
        $this->assertEquals('Indonesian', $localeManager->getLocaleName('id'));
    }

    /** @test */
    public function it_can_get_locale_native_name()
    {
        $localeManager = new LocaleManager();

        $this->assertEquals('English', $localeManager->getLocaleNativeName('en'));
        $this->assertEquals('Bahasa Indonesia', $localeManager->getLocaleNativeName('id'));
    }

    /** @test */
    public function it_can_get_locale_flag()
    {
        $localeManager = new LocaleManager();

        $this->assertEquals('🇺🇸', $localeManager->getLocaleFlag('en'));
        $this->assertEquals('🇮🇩', $localeManager->getLocaleFlag('id'));
    }

    /** @test */
    public function it_can_detect_rtl_locale()
    {
        Config::set('canvastack.localization.rtl_locales', ['ar', 'he']);
        Config::set('canvastack.localization.available_locales.ar', [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
        ]);

        $localeManager = new LocaleManager();

        $this->assertFalse($localeManager->isRtl('en'));
        $this->assertTrue($localeManager->isRtl('ar'));
    }

    /** @test */
    public function it_can_get_text_direction()
    {
        Config::set('canvastack.localization.rtl_locales', ['ar', 'he']);
        Config::set('canvastack.localization.available_locales.ar', [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
        ]);

        $localeManager = new LocaleManager();

        $this->assertEquals('ltr', $localeManager->getDirection('en'));
        $this->assertEquals('rtl', $localeManager->getDirection('ar'));
    }

    /** @test */
    public function it_can_switch_locale_via_post_request()
    {
        $response = $this->post(route('locale.switch'), [
            'locale' => 'id',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('id', Session::get('canvastack_locale'));
    }

    /** @test */
    public function it_validates_locale_code_format()
    {
        $response = $this->post(route('locale.switch'), [
            'locale' => 'invalid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('locale');
    }

    /** @test */
    public function it_detects_browser_locale()
    {
        Config::set('canvastack.localization.detect_browser', true);

        // Mock the request with Accept-Language header
        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        ]);

        // Bind the mocked request to the container
        app()->instance('request', $request);

        // Create a new LocaleManager instance (will detect from browser)
        $localeManager = new LocaleManager();

        // Should detect Indonesian locale from browser
        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_uses_default_locale_when_browser_locale_not_available()
    {
        Config::set('canvastack.localization.detect_browser', true);

        // Mock the request with Accept-Language header for unavailable locale
        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9',
        ]);

        // Bind the mocked request to the container
        app()->instance('request', $request);

        // Create a new LocaleManager instance (will detect from browser)
        $localeManager = new LocaleManager();

        // Should fall back to default locale (en)
        $this->assertEquals('en', $localeManager->getLocale());
    }
}

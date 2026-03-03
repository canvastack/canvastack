<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Events\Translation\LocaleChanged;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;

class LocaleManagerTest extends TestCase
{
    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached state
        Cache::flush();
        Session::flush();

        // Setup default configuration
        Config::set('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
            'es' => ['name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸'],
        ]);

        Config::set('canvastack.localization.default_locale', 'en');
        Config::set('canvastack.localization.fallback_locale', 'en');
        Config::set('canvastack.localization.storage', 'session');
        Config::set('canvastack.localization.detect_browser', true);
        Config::set('canvastack.localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);

        $this->localeManager = new LocaleManager();
    }

    /** @test */
    public function it_loads_configuration_correctly()
    {
        $availableLocales = $this->localeManager->getAvailableLocales();

        $this->assertIsArray($availableLocales);
        $this->assertArrayHasKey('en', $availableLocales);
        $this->assertArrayHasKey('id', $availableLocales);
        $this->assertEquals('English', $availableLocales['en']['name']);
    }

    /** @test */
    public function it_returns_default_locale()
    {
        $defaultLocale = $this->localeManager->getDefaultLocale();

        $this->assertEquals('en', $defaultLocale);
    }

    /** @test */
    public function it_returns_fallback_locale()
    {
        $fallbackLocale = $this->localeManager->getFallbackLocale();

        $this->assertEquals('en', $fallbackLocale);
    }

    /** @test */
    public function it_returns_current_locale()
    {
        $currentLocale = $this->localeManager->getLocale();

        $this->assertEquals('en', $currentLocale);
    }

    /** @test */
    public function it_can_set_locale()
    {
        Event::fake();

        $result = $this->localeManager->setLocale('id');

        $this->assertTrue($result);
        $this->assertEquals('id', $this->localeManager->getLocale());
        $this->assertEquals('id', App::getLocale());

        Event::assertDispatched(LocaleChanged::class, function ($event) {
            return $event->locale === 'id' && $event->previousLocale === 'en';
        });
    }

    /** @test */
    public function it_fails_to_set_unavailable_locale()
    {
        Event::fake();

        $result = $this->localeManager->setLocale('fr');

        $this->assertFalse($result);
        $this->assertEquals('en', $this->localeManager->getLocale());

        Event::assertNotDispatched(LocaleChanged::class);
    }

    /** @test */
    public function it_persists_locale_to_session()
    {
        Config::set('canvastack.localization.storage', 'session');
        $this->localeManager = new LocaleManager();

        $this->localeManager->setLocale('id');

        $this->assertEquals('id', Session::get('canvastack_locale'));
    }

    /** @test */
    public function it_persists_locale_to_cookie()
    {
        $this->markTestSkipped('Cookie testing requires full Laravel application context');
    }

    /** @test */
    public function it_persists_locale_to_both_session_and_cookie()
    {
        $this->markTestSkipped('Cookie testing requires full Laravel application context');
    }

    /** @test */
    public function it_detects_locale_from_url_parameter()
    {
        request()->merge(['locale' => 'id']);

        $localeManager = new LocaleManager();

        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_detects_locale_from_session()
    {
        Session::put('canvastack_locale', 'id');

        $localeManager = new LocaleManager();

        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_detects_locale_from_cookie()
    {
        $this->markTestSkipped('Cookie testing requires full Laravel application context');
    }

    /** @test */
    public function it_detects_locale_from_browser_accept_language_header()
    {
        Config::set('canvastack.localization.detect_browser', true);

        // Clear session to ensure browser detection is used
        Session::flush();

        // Simulate browser sending Accept-Language header
        request()->headers->set('Accept-Language', 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7');

        // Create new instance after setting headers
        $localeManager = new LocaleManager();

        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_parses_accept_language_header_with_quality_values()
    {
        // Clear session
        Session::flush();

        request()->headers->set('Accept-Language', 'es;q=0.5,id;q=0.9,en;q=0.8');

        // Create new instance after setting headers
        $localeManager = new LocaleManager();

        // Should pick 'id' because it has highest quality value (0.9)
        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_falls_back_to_default_locale_when_browser_locale_unavailable()
    {
        request()->headers->set('Accept-Language', 'fr-FR,fr;q=0.9');

        $localeManager = new LocaleManager();

        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_extracts_language_code_from_locale_string()
    {
        // Test with locale like 'en-US' -> should match 'en'
        request()->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $localeManager = new LocaleManager();

        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_checks_if_locale_is_available()
    {
        $this->assertTrue($this->localeManager->isAvailable('en'));
        $this->assertTrue($this->localeManager->isAvailable('id'));
        $this->assertFalse($this->localeManager->isAvailable('fr'));
    }

    /** @test */
    public function it_returns_locale_information()
    {
        $info = $this->localeManager->getLocaleInfo('en');

        $this->assertIsArray($info);
        $this->assertEquals('English', $info['name']);
        $this->assertEquals('English', $info['native']);
        $this->assertEquals('🇺🇸', $info['flag']);
    }

    /** @test */
    public function it_returns_null_for_unavailable_locale_info()
    {
        $info = $this->localeManager->getLocaleInfo('fr');

        $this->assertNull($info);
    }

    /** @test */
    public function it_returns_current_locale_info_when_no_locale_specified()
    {
        $this->localeManager->setLocale('id');

        $info = $this->localeManager->getLocaleInfo();

        $this->assertIsArray($info);
        $this->assertEquals('Indonesian', $info['name']);
    }

    /** @test */
    public function it_returns_locale_name()
    {
        $name = $this->localeManager->getLocaleName('en');

        $this->assertEquals('English', $name);
    }

    /** @test */
    public function it_returns_null_for_unavailable_locale_name()
    {
        $name = $this->localeManager->getLocaleName('fr');

        $this->assertNull($name);
    }

    /** @test */
    public function it_returns_locale_native_name()
    {
        $nativeName = $this->localeManager->getLocaleNativeName('id');

        $this->assertEquals('Bahasa Indonesia', $nativeName);
    }

    /** @test */
    public function it_returns_null_for_unavailable_locale_native_name()
    {
        $nativeName = $this->localeManager->getLocaleNativeName('fr');

        $this->assertNull($nativeName);
    }

    /** @test */
    public function it_returns_locale_flag()
    {
        $flag = $this->localeManager->getLocaleFlag('en');

        $this->assertEquals('🇺🇸', $flag);
    }

    /** @test */
    public function it_returns_null_for_unavailable_locale_flag()
    {
        $flag = $this->localeManager->getLocaleFlag('fr');

        $this->assertNull($flag);
    }

    /** @test */
    public function it_checks_if_locale_is_rtl()
    {
        $this->assertFalse($this->localeManager->isRtl('en'));
        $this->assertFalse($this->localeManager->isRtl('id'));
        $this->assertTrue($this->localeManager->isRtl('ar'));
        $this->assertTrue($this->localeManager->isRtl('he'));
    }

    /** @test */
    public function it_checks_if_current_locale_is_rtl()
    {
        $this->localeManager->setLocale('en');
        $this->assertFalse($this->localeManager->isRtl());

        // Add Arabic to available locales for testing
        Config::set('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦'],
        ]);

        $localeManager = new LocaleManager();
        $localeManager->setLocale('ar');
        $this->assertTrue($localeManager->isRtl());
    }

    /** @test */
    public function it_returns_text_direction_for_ltr_locale()
    {
        $direction = $this->localeManager->getDirection('en');

        $this->assertEquals('ltr', $direction);
    }

    /** @test */
    public function it_returns_text_direction_for_rtl_locale()
    {
        $direction = $this->localeManager->getDirection('ar');

        $this->assertEquals('rtl', $direction);
    }

    /** @test */
    public function it_returns_text_direction_for_current_locale()
    {
        $this->localeManager->setLocale('en');
        $direction = $this->localeManager->getDirection();

        $this->assertEquals('ltr', $direction);
    }

    /** @test */
    public function it_clears_locale_cache()
    {
        // Array cache driver doesn't support tags, skip in unit tests
        $this->markTestSkipped('Array cache driver does not support tags - test in feature tests with Redis');
    }

    /** @test */
    public function it_prioritizes_url_parameter_over_session()
    {
        Session::put('canvastack_locale', 'id');
        request()->merge(['locale' => 'es']);

        $localeManager = new LocaleManager();

        $this->assertEquals('es', $localeManager->getLocale());
    }

    /** @test */
    public function it_prioritizes_session_over_browser()
    {
        Session::put('canvastack_locale', 'id');
        request()->headers->set('Accept-Language', 'es-ES,es;q=0.9');

        $localeManager = new LocaleManager();

        $this->assertEquals('id', $localeManager->getLocale());
    }

    /** @test */
    public function it_uses_default_locale_when_no_detection_source_available()
    {
        Config::set('canvastack.localization.detect_browser', false);

        $localeManager = new LocaleManager();

        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_fires_locale_changed_event_with_correct_data()
    {
        Event::fake();

        $this->localeManager->setLocale('id');

        Event::assertDispatched(LocaleChanged::class, function ($event) {
            return $event->locale === 'id'
                && $event->previousLocale === 'en';
        });
    }

    /** @test */
    public function it_does_not_fire_event_when_locale_change_fails()
    {
        Event::fake();

        $this->localeManager->setLocale('invalid');

        Event::assertNotDispatched(LocaleChanged::class);
    }

    /** @test */
    public function it_handles_empty_accept_language_header()
    {
        request()->headers->set('Accept-Language', '');

        $localeManager = new LocaleManager();

        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_handles_malformed_accept_language_header()
    {
        request()->headers->set('Accept-Language', 'invalid;;;format');

        $localeManager = new LocaleManager();

        // Should fall back to default
        $this->assertEquals('en', $localeManager->getLocale());
    }

    /** @test */
    public function it_returns_all_available_locales()
    {
        $locales = $this->localeManager->getAvailableLocales();

        $this->assertIsArray($locales);
        $this->assertCount(3, $locales);
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('id', $locales);
        $this->assertArrayHasKey('es', $locales);
    }

    /** @test */
    public function it_can_disable_browser_detection()
    {
        Config::set('canvastack.localization.detect_browser', false);
        request()->headers->set('Accept-Language', 'id-ID,id;q=0.9');

        $localeManager = new LocaleManager();

        // Should use default locale, not browser locale
        $this->assertEquals('en', $localeManager->getLocale());
    }
}

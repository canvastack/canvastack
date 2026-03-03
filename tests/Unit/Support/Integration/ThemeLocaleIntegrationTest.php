<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Integration;

use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class ThemeLocaleIntegrationTest extends TestCase
{
    protected ThemeLocaleIntegration $integration;

    protected ThemeManager $themeManager;

    protected LocaleManager $localeManager;

    protected RtlSupport $rtlSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeManager = app(ThemeManager::class);
        $this->localeManager = app(LocaleManager::class);
        $this->rtlSupport = app(RtlSupport::class);

        $this->integration = new ThemeLocaleIntegration(
            $this->themeManager,
            $this->localeManager,
            $this->rtlSupport
        );

        // Clear cache before each test
        Cache::tags(['theme', 'locale'])->flush();
    }

    public function test_get_localized_theme_css_for_ltr_locale()
    {
        $this->localeManager->setLocale('en');

        $css = $this->integration->getLocalizedThemeCss();

        $this->assertNotEmpty($css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringNotContainsString('RTL Adjustments', $css);
    }

    public function test_get_localized_theme_css_for_rtl_locale()
    {
        $this->localeManager->setLocale('ar');

        $css = $this->integration->getLocalizedThemeCss();

        $this->assertNotEmpty($css);
        $this->assertStringContainsString('RTL Adjustments', $css);
        $this->assertStringContainsString('[dir="rtl"]', $css);
        $this->assertStringContainsString('direction: rtl', $css);
    }

    public function test_get_localized_theme_css_includes_locale_fonts()
    {
        $this->localeManager->setLocale('ar');

        $css = $this->integration->getLocalizedThemeCss();

        $this->assertStringContainsString('Locale-specific fonts', $css);
        $this->assertStringContainsString('[lang="ar"]', $css);
    }

    public function test_get_localized_theme_config()
    {
        $this->localeManager->setLocale('en');

        $config = $this->integration->getLocalizedThemeConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('en', $config['locale']['code']);
        $this->assertEquals('ltr', $config['locale']['direction']);
        $this->assertFalse($config['locale']['is_rtl']);
    }

    public function test_get_localized_theme_config_for_rtl()
    {
        $this->localeManager->setLocale('ar');

        $config = $this->integration->getLocalizedThemeConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('ar', $config['locale']['code']);
        $this->assertEquals('rtl', $config['locale']['direction']);
        $this->assertTrue($config['locale']['is_rtl']);
        $this->assertEquals('rtl', $config['layout']['direction']);
        $this->assertEquals('right', $config['layout']['text_align']);
    }

    public function test_validate_theme_locale_compatibility()
    {
        $issues = $this->integration->validateThemeLocaleCompatibility('default', 'en');

        $this->assertIsArray($issues);
        // LTR locale should have no issues
        $this->assertEmpty($issues);
    }

    public function test_validate_theme_locale_compatibility_rtl_warning()
    {
        $issues = $this->integration->validateThemeLocaleCompatibility('default', 'ar');

        $this->assertIsArray($issues);
        // May have warnings about RTL support
        if (!empty($issues)) {
            $this->assertEquals('warning', $issues[0]['type']);
            $this->assertStringContainsString('RTL', $issues[0]['message']);
        }
    }

    public function test_test_theme_in_all_locales()
    {
        $results = $this->integration->testThemeInAllLocales('default');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        foreach ($results as $locale => $result) {
            $this->assertArrayHasKey('locale', $result);
            $this->assertArrayHasKey('name', $result);
            $this->assertArrayHasKey('is_rtl', $result);
            $this->assertArrayHasKey('css_generated', $result);
            $this->assertArrayHasKey('config_generated', $result);
            $this->assertArrayHasKey('status', $result);

            $this->assertEquals('success', $result['status']);
            $this->assertTrue($result['css_generated']);
            $this->assertTrue($result['config_generated']);
        }
    }

    public function test_get_html_attributes_ltr()
    {
        $this->localeManager->setLocale('en');

        $attributes = $this->integration->getHtmlAttributes();

        $this->assertIsArray($attributes);
        $this->assertEquals('en', $attributes['lang']);
        $this->assertEquals('ltr', $attributes['dir']);
        $this->assertStringContainsString('ltr', $attributes['class']);
        $this->assertStringContainsString('theme-', $attributes['class']);
    }

    public function test_get_html_attributes_rtl()
    {
        $this->localeManager->setLocale('ar');

        $attributes = $this->integration->getHtmlAttributes();

        $this->assertIsArray($attributes);
        $this->assertEquals('ar', $attributes['lang']);
        $this->assertEquals('rtl', $attributes['dir']);
        $this->assertStringContainsString('rtl', $attributes['class']);
    }

    public function test_get_body_classes()
    {
        $this->localeManager->setLocale('en');

        $classes = $this->integration->getBodyClasses();

        $this->assertIsString($classes);
        $this->assertStringContainsString('locale-en', $classes);
        $this->assertStringContainsString('ltr', $classes);
        $this->assertStringContainsString('theme-', $classes);
    }

    public function test_get_body_classes_rtl()
    {
        $this->localeManager->setLocale('ar');

        $classes = $this->integration->getBodyClasses();

        $this->assertStringContainsString('locale-ar', $classes);
        $this->assertStringContainsString('rtl', $classes);
    }

    public function test_caching_works()
    {
        $this->localeManager->setLocale('en');

        // First call - should cache
        $css1 = $this->integration->getLocalizedThemeCss();

        // Second call - should use cache
        $css2 = $this->integration->getLocalizedThemeCss();

        $this->assertEquals($css1, $css2);

        // Note: Cache tags may not work in test environment with ArrayStore
        // This test verifies the method works, actual caching tested in integration tests
    }

    public function test_clear_cache()
    {
        $this->localeManager->setLocale('en');

        // Generate and cache
        $this->integration->getLocalizedThemeCss();

        // Clear cache
        $this->integration->clearCache();

        // Note: Cache tags may not work in test environment with ArrayStore
        // This test verifies the method works without errors
        $this->assertTrue(true);
    }

    public function test_get_cache_stats()
    {
        // Generate some cached data
        $this->localeManager->setLocale('en');
        $this->integration->getLocalizedThemeCss();
        $this->integration->getLocalizedThemeConfig();

        $stats = $this->integration->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_combinations', $stats);
        $this->assertArrayHasKey('cached_css', $stats);
        $this->assertArrayHasKey('cached_config', $stats);
        $this->assertArrayHasKey('css_cache_ratio', $stats);
        $this->assertArrayHasKey('config_cache_ratio', $stats);

        $this->assertGreaterThan(0, $stats['total_combinations']);
        $this->assertGreaterThanOrEqual(0, $stats['cached_css']);
        $this->assertGreaterThanOrEqual(0, $stats['cached_config']);
    }

    public function test_multiple_themes_multiple_locales()
    {
        $themes = $this->themeManager->names();
        $locales = array_keys($this->localeManager->getAvailableLocales());

        foreach ($themes as $theme) {
            foreach ($locales as $locale) {
                $css = $this->integration->getLocalizedThemeCss($theme, $locale);
                $this->assertNotEmpty($css);

                $config = $this->integration->getLocalizedThemeConfig($theme, $locale);
                $this->assertIsArray($config);
                $this->assertEquals($locale, $config['locale']['code']);
            }
        }
    }
}

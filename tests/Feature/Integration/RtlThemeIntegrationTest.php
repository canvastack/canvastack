<?php

namespace Canvastack\Canvastack\Tests\Feature\Integration;

use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * RTL Theme Integration Tests.
 *
 * Comprehensive tests to ensure all themes work correctly with RTL locales.
 */
class RtlThemeIntegrationTest extends TestCase
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
    }

    public function test_all_themes_generate_rtl_css()
    {
        $themes = $this->themeManager->names();
        $rtlLocales = ['ar', 'he', 'fa', 'ur'];

        foreach ($themes as $theme) {
            foreach ($rtlLocales as $locale) {
                $css = $this->integration->getLocalizedThemeCss($theme, $locale);

                $this->assertNotEmpty($css, "Theme '{$theme}' should generate CSS for RTL locale '{$locale}'");
                $this->assertStringContainsString('RTL Adjustments', $css, "Theme '{$theme}' should include RTL adjustments for locale '{$locale}'");
                $this->assertStringContainsString('[dir="rtl"]', $css, "Theme '{$theme}' should include dir=rtl selector for locale '{$locale}'");
                $this->assertStringContainsString('direction: rtl', $css, "Theme '{$theme}' should set direction: rtl for locale '{$locale}'");
            }
        }
    }

    public function test_all_themes_have_rtl_config()
    {
        $themes = $this->themeManager->names();
        $rtlLocales = ['ar', 'he'];

        foreach ($themes as $theme) {
            foreach ($rtlLocales as $locale) {
                $config = $this->integration->getLocalizedThemeConfig($theme, $locale);

                $this->assertArrayHasKey('locale', $config, "Theme '{$theme}' config should have locale info for '{$locale}'");
                $this->assertTrue($config['locale']['is_rtl'], "Theme '{$theme}' should mark '{$locale}' as RTL");
                $this->assertEquals('rtl', $config['locale']['direction'], "Theme '{$theme}' should set direction to rtl for '{$locale}'");
                $this->assertEquals('rtl', $config['layout']['direction'], "Theme '{$theme}' layout should be rtl for '{$locale}'");
                $this->assertEquals('right', $config['layout']['text_align'], "Theme '{$theme}' text should align right for '{$locale}'");
            }
        }
    }

    public function test_rtl_css_includes_margin_padding_flips()
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $theme) {
            $css = $this->integration->getLocalizedThemeCss($theme, 'ar');

            // Check for margin flips
            $this->assertStringContainsString('.ml-auto', $css, "Theme '{$theme}' should flip ml-auto");
            $this->assertStringContainsString('.mr-auto', $css, "Theme '{$theme}' should flip mr-auto");

            // Check for float flips
            $this->assertStringContainsString('.float-left', $css, "Theme '{$theme}' should flip float-left");
            $this->assertStringContainsString('.float-right', $css, "Theme '{$theme}' should flip float-right");

            // Check for text align flips
            $this->assertStringContainsString('.text-left', $css, "Theme '{$theme}' should flip text-left");
            $this->assertStringContainsString('.text-right', $css, "Theme '{$theme}' should flip text-right");

            // Check for icon flip
            $this->assertStringContainsString('.flip-rtl', $css, "Theme '{$theme}' should include flip-rtl class");
        }
    }

    public function test_rtl_html_attributes_correct()
    {
        $rtlLocales = ['ar', 'he', 'fa'];

        foreach ($rtlLocales as $locale) {
            $this->localeManager->setLocale($locale);
            $attributes = $this->integration->getHtmlAttributes();

            $this->assertEquals($locale, $attributes['lang'], "HTML lang should be '{$locale}'");
            $this->assertEquals('rtl', $attributes['dir'], "HTML dir should be 'rtl' for '{$locale}'");
            $this->assertStringContainsString('rtl', $attributes['class'], "HTML class should contain 'rtl' for '{$locale}'");
        }
    }

    public function test_rtl_body_classes_correct()
    {
        $rtlLocales = ['ar', 'he'];

        foreach ($rtlLocales as $locale) {
            $this->localeManager->setLocale($locale);
            $classes = $this->integration->getBodyClasses();

            $this->assertStringContainsString("locale-{$locale}", $classes, "Body should have locale-{$locale} class");
            $this->assertStringContainsString('rtl', $classes, "Body should have rtl class for '{$locale}'");
            $this->assertStringNotContainsString('ltr', $classes, "Body should not have ltr class for '{$locale}'");
        }
    }

    public function test_ltr_themes_dont_have_rtl_css()
    {
        $themes = $this->themeManager->names();
        $ltrLocales = ['en', 'id'];

        foreach ($themes as $theme) {
            foreach ($ltrLocales as $locale) {
                $css = $this->integration->getLocalizedThemeCss($theme, $locale);

                $this->assertStringNotContainsString('RTL Adjustments', $css, "Theme '{$theme}' should not include RTL adjustments for LTR locale '{$locale}'");
                $this->assertStringNotContainsString('[dir="rtl"]', $css, "Theme '{$theme}' should not include dir=rtl selector for LTR locale '{$locale}'");
            }
        }
    }

    public function test_theme_switching_preserves_rtl()
    {
        $themes = $this->themeManager->names();
        $this->localeManager->setLocale('ar');

        foreach ($themes as $theme) {
            $this->themeManager->setCurrentTheme($theme);
            $attributes = $this->integration->getHtmlAttributes();

            $this->assertEquals('rtl', $attributes['dir'], "Theme '{$theme}' should preserve RTL direction");
            $this->assertStringContainsString('rtl', $attributes['class'], "Theme '{$theme}' should preserve RTL class");
        }
    }

    public function test_locale_switching_updates_direction()
    {
        $theme = $this->themeManager->current()->getName();

        // Test LTR
        $this->localeManager->setLocale('en');
        $attributesLtr = $this->integration->getHtmlAttributes();
        $this->assertEquals('ltr', $attributesLtr['dir']);

        // Test RTL
        $this->localeManager->setLocale('ar');
        $attributesRtl = $this->integration->getHtmlAttributes();
        $this->assertEquals('rtl', $attributesRtl['dir']);

        // Back to LTR
        $this->localeManager->setLocale('id');
        $attributesLtr2 = $this->integration->getHtmlAttributes();
        $this->assertEquals('ltr', $attributesLtr2['dir']);
    }

    public function test_rtl_fonts_included_for_arabic()
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $theme) {
            $css = $this->integration->getLocalizedThemeCss($theme, 'ar');

            $this->assertStringContainsString('Locale-specific fonts', $css, "Theme '{$theme}' should include locale-specific fonts for Arabic");
            $this->assertStringContainsString('[lang="ar"]', $css, "Theme '{$theme}' should have lang=ar selector");
            $this->assertStringContainsString('Noto Sans Arabic', $css, "Theme '{$theme}' should include Noto Sans Arabic font");
        }
    }

    public function test_rtl_fonts_included_for_hebrew()
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $theme) {
            $css = $this->integration->getLocalizedThemeCss($theme, 'he');

            $this->assertStringContainsString('[lang="he"]', $css, "Theme '{$theme}' should have lang=he selector");
            $this->assertStringContainsString('Noto Sans Hebrew', $css, "Theme '{$theme}' should include Noto Sans Hebrew font");
        }
    }

    public function test_all_themes_pass_rtl_validation()
    {
        $themes = $this->themeManager->names();
        $rtlLocales = ['ar', 'he'];

        foreach ($themes as $theme) {
            foreach ($rtlLocales as $locale) {
                $issues = $this->integration->validateThemeLocaleCompatibility($theme, $locale);

                // Should have no errors, only warnings or info
                foreach ($issues as $issue) {
                    $this->assertNotEquals('error', $issue['type'], "Theme '{$theme}' should not have errors for RTL locale '{$locale}'");
                }
            }
        }
    }

    public function test_rtl_test_suite_passes_for_all_themes()
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $theme) {
            $results = $this->integration->testThemeInAllLocales($theme);

            foreach ($results as $locale => $result) {
                if ($this->rtlSupport->isRtl($locale)) {
                    $this->assertEquals('success', $result['status'], "Theme '{$theme}' should pass RTL tests for locale '{$locale}'");
                    $this->assertTrue($result['css_generated'], "Theme '{$theme}' should generate CSS for RTL locale '{$locale}'");
                    $this->assertTrue($result['config_generated'], "Theme '{$theme}' should generate config for RTL locale '{$locale}'");
                }
            }
        }
    }

    public function test_rtl_css_size_reasonable()
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $theme) {
            $ltrCss = $this->integration->getLocalizedThemeCss($theme, 'en');
            $rtlCss = $this->integration->getLocalizedThemeCss($theme, 'ar');

            $ltrSize = strlen($ltrCss);
            $rtlSize = strlen($rtlCss);

            // RTL CSS should be larger (includes RTL adjustments and locale fonts)
            $this->assertGreaterThan($ltrSize, $rtlSize, "Theme '{$theme}' RTL CSS should be larger than LTR");

            // But not excessively large (less than 10KB total)
            $this->assertLessThan(10240, $rtlSize, "Theme '{$theme}' RTL CSS should be less than 10KB");
        }
    }

    public function test_multiple_rtl_locales_simultaneously()
    {
        $theme = $this->themeManager->current()->getName();
        $rtlLocales = ['ar', 'he', 'fa'];

        $results = [];
        foreach ($rtlLocales as $locale) {
            $results[$locale] = [
                'css' => $this->integration->getLocalizedThemeCss($theme, $locale),
                'config' => $this->integration->getLocalizedThemeConfig($theme, $locale),
            ];
        }

        // Verify all generated successfully
        foreach ($rtlLocales as $locale) {
            $this->assertNotEmpty($results[$locale]['css'], "CSS should be generated for '{$locale}'");
            $this->assertIsArray($results[$locale]['config'], "Config should be generated for '{$locale}'");
            $this->assertTrue($results[$locale]['config']['locale']['is_rtl'], "Locale '{$locale}' should be marked as RTL");
        }
    }
}

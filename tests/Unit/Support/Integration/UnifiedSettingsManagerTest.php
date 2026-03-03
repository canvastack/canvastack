<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Integration;

use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Support\Integration\UnifiedSettingsManager;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;

class UnifiedSettingsManagerTest extends TestCase
{
    protected UnifiedSettingsManager $manager;

    protected ThemeManager $themeManager;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeManager = app(ThemeManager::class);
        $this->localeManager = app(LocaleManager::class);
        $rtlSupport = app(RtlSupport::class);

        $integration = new ThemeLocaleIntegration(
            $this->themeManager,
            $this->localeManager,
            $rtlSupport
        );

        $this->manager = new UnifiedSettingsManager(
            $this->themeManager,
            $this->localeManager,
            $integration
        );
    }

    public function test_get_all_settings()
    {
        $settings = $this->manager->getAllSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('theme', $settings);
        $this->assertArrayHasKey('locale', $settings);
        $this->assertArrayHasKey('integration', $settings);
    }

    public function test_get_theme_settings()
    {
        $settings = $this->manager->getThemeSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('current', $settings);
        $this->assertArrayHasKey('available', $settings);
        $this->assertArrayHasKey('supports_dark_mode', $settings);
        $this->assertArrayHasKey('colors', $settings);
        $this->assertArrayHasKey('fonts', $settings);

        // Check current theme structure
        $this->assertArrayHasKey('name', $settings['current']);
        $this->assertArrayHasKey('display_name', $settings['current']);
        $this->assertArrayHasKey('version', $settings['current']);
        $this->assertArrayHasKey('author', $settings['current']);
        $this->assertArrayHasKey('description', $settings['current']);
    }

    public function test_get_locale_settings()
    {
        $settings = $this->manager->getLocaleSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('current', $settings);
        $this->assertArrayHasKey('available', $settings);
        $this->assertArrayHasKey('default', $settings);
        $this->assertArrayHasKey('fallback', $settings);

        // Check current locale structure
        $this->assertArrayHasKey('code', $settings['current']);
        $this->assertArrayHasKey('name', $settings['current']);
        $this->assertArrayHasKey('native', $settings['current']);
        $this->assertArrayHasKey('flag', $settings['current']);
        $this->assertArrayHasKey('direction', $settings['current']);
        $this->assertArrayHasKey('is_rtl', $settings['current']);
    }

    public function test_get_integration_settings()
    {
        $settings = $this->manager->getIntegrationSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('html_attributes', $settings);
        $this->assertArrayHasKey('body_classes', $settings);
        $this->assertArrayHasKey('cache_stats', $settings);
    }

    public function test_apply_theme_settings()
    {
        $results = $this->manager->applySettings(['theme' => 'default']);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('theme', $results);
        $this->assertTrue($results['theme']['success']);
        $this->assertStringContainsString('default', $results['theme']['message']);
    }

    public function test_apply_locale_settings()
    {
        $results = $this->manager->applySettings(['locale' => 'id']);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('locale', $results);
        $this->assertTrue($results['locale']['success']);
        $this->assertStringContainsString('id', $results['locale']['message']);
    }

    public function test_apply_both_settings()
    {
        $results = $this->manager->applySettings([
            'theme' => 'default',
            'locale' => 'en',
        ]);

        $this->assertIsArray($results);
        $this->assertTrue($results['theme']['success']);
        $this->assertTrue($results['locale']['success']);
    }

    public function test_apply_invalid_theme()
    {
        $results = $this->manager->applySettings(['theme' => 'nonexistent']);

        $this->assertFalse($results['theme']['success']);
        $this->assertNotEmpty($results['theme']['message']);
    }

    public function test_apply_invalid_locale()
    {
        $results = $this->manager->applySettings(['locale' => 'xx']);

        $this->assertFalse($results['locale']['success']);
    }

    public function test_reset_to_defaults()
    {
        // Change to non-default
        $this->localeManager->setLocale('id');

        // Reset
        $results = $this->manager->resetToDefaults();

        $this->assertIsArray($results);
        $this->assertTrue($results['theme']['success']);
        $this->assertTrue($results['locale']['success']);

        // Verify reset
        $this->assertEquals('en', $this->localeManager->getLocale());
    }

    public function test_export_settings()
    {
        $exported = $this->manager->exportSettings();

        $this->assertIsArray($exported);
        $this->assertArrayHasKey('theme', $exported);
        $this->assertArrayHasKey('locale', $exported);
        $this->assertArrayHasKey('exported_at', $exported);

        $this->assertNotEmpty($exported['theme']);
        $this->assertNotEmpty($exported['locale']);
        $this->assertNotEmpty($exported['exported_at']);
    }

    public function test_import_valid_settings()
    {
        $settings = [
            'theme' => 'default',
            'locale' => 'en',
        ];

        $results = $this->manager->importSettings($settings);

        $this->assertIsArray($results);
        $this->assertTrue($results['theme']['success']);
        $this->assertTrue($results['locale']['success']);
    }

    public function test_import_invalid_settings()
    {
        $settings = [
            'theme' => 'nonexistent',
            'locale' => 'xx',
        ];

        $results = $this->manager->importSettings($settings);

        $this->assertIsArray($results);
        $this->assertFalse($results['theme']['success']);
        $this->assertFalse($results['locale']['success']);
    }

    public function test_validate_valid_settings()
    {
        $settings = [
            'theme' => 'default',
            'locale' => 'en',
        ];

        $errors = $this->manager->validateSettings($settings);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function test_validate_invalid_theme()
    {
        $settings = ['theme' => 'nonexistent'];

        $errors = $this->manager->validateSettings($settings);

        $this->assertArrayHasKey('theme', $errors);
        $this->assertStringContainsString('nonexistent', $errors['theme']);
    }

    public function test_validate_invalid_locale()
    {
        $settings = ['locale' => 'xx'];

        $errors = $this->manager->validateSettings($settings);

        $this->assertArrayHasKey('locale', $errors);
        $this->assertStringContainsString('xx', $errors['locale']);
    }

    public function test_get_settings_for_ui()
    {
        $settings = $this->manager->getSettingsForUI();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('current', $settings);
        $this->assertArrayHasKey('themes', $settings);
        $this->assertArrayHasKey('locales', $settings);
        $this->assertArrayHasKey('integration', $settings);

        // Check themes structure
        $this->assertIsArray($settings['themes']);
        $this->assertNotEmpty($settings['themes']);
        foreach ($settings['themes'] as $theme) {
            $this->assertArrayHasKey('value', $theme);
            $this->assertArrayHasKey('label', $theme);
            $this->assertArrayHasKey('description', $theme);
            $this->assertArrayHasKey('selected', $theme);
            $this->assertArrayHasKey('preview', $theme);
        }

        // Check locales structure
        $this->assertIsArray($settings['locales']);
        $this->assertNotEmpty($settings['locales']);
        foreach ($settings['locales'] as $locale) {
            $this->assertArrayHasKey('value', $locale);
            $this->assertArrayHasKey('label', $locale);
            $this->assertArrayHasKey('flag', $locale);
            $this->assertArrayHasKey('selected', $locale);
            $this->assertArrayHasKey('rtl', $locale);
        }
    }

    public function test_clear_all_caches()
    {
        // Should not throw exception
        $this->manager->clearAllCaches();

        $this->assertTrue(true);
    }

    public function test_settings_persist_across_calls()
    {
        // Apply settings
        $this->manager->applySettings([
            'theme' => 'default',
            'locale' => 'id',
        ]);

        // Get settings
        $settings = $this->manager->getAllSettings();

        $this->assertEquals('default', $settings['theme']['current']['name']);
        $this->assertEquals('id', $settings['locale']['current']['code']);
    }
}

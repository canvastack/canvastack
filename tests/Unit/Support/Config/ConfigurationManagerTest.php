<?php

declare(strict_types=1);

namespace Canvastack\Tests\Unit\Support\Config;

use Canvastack\Canvastack\Support\Config\ConfigurationManager;
use Canvastack\Canvastack\Support\Config\ConfigValidator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ConfigurationManagerTest extends TestCase
{
    protected ConfigurationManager $manager;

    protected ConfigValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ConfigValidator();
        $this->manager = new ConfigurationManager($this->validator);
    }

    /** @test */
    public function it_can_get_all_settings()
    {
        $settings = $this->manager->getAllSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('app', $settings);
        $this->assertArrayHasKey('theme', $settings);
        $this->assertArrayHasKey('localization', $settings);
        $this->assertArrayHasKey('rbac', $settings);
        $this->assertArrayHasKey('performance', $settings);
        $this->assertArrayHasKey('cache', $settings);
    }

    /** @test */
    public function it_can_get_app_settings()
    {
        $settings = $this->manager->getAppSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('name', $settings);
        $this->assertArrayHasKey('description', $settings);
        $this->assertArrayHasKey('version', $settings);
    }

    /** @test */
    public function it_can_get_theme_settings()
    {
        $settings = $this->manager->getThemeSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('active', $settings);
        $this->assertArrayHasKey('default', $settings);
        $this->assertArrayHasKey('cache_enabled', $settings);
    }

    /** @test */
    public function it_can_get_localization_settings()
    {
        $settings = $this->manager->getLocalizationSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('default_locale', $settings);
        $this->assertArrayHasKey('fallback_locale', $settings);
        $this->assertArrayHasKey('available_locales', $settings);
    }

    /** @test */
    public function it_can_get_rbac_settings()
    {
        $settings = $this->manager->getRbacSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('contexts', $settings);
        $this->assertArrayHasKey('cache_enabled', $settings);
    }

    /** @test */
    public function it_can_get_performance_settings()
    {
        $settings = $this->manager->getPerformanceSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('chunk_size', $settings);
        $this->assertArrayHasKey('eager_load', $settings);
        $this->assertArrayHasKey('query_cache', $settings);
    }

    /** @test */
    public function it_can_get_cache_settings()
    {
        $settings = $this->manager->getCacheSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('enabled', $settings);
        $this->assertArrayHasKey('driver', $settings);
        $this->assertArrayHasKey('ttl', $settings);
    }

    /** @test */
    public function it_can_update_settings()
    {
        $result = $this->manager->updateSettings('app', [
            'name' => 'Test App',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Test App', Config::get('canvastack.app.name'));
    }

    /** @test */
    public function it_validates_settings_before_update()
    {
        $result = $this->manager->updateSettings('app', [
            'name' => '', // Invalid: required
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    /** @test */
    public function it_can_export_configuration()
    {
        $config = $this->manager->exportConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('exported_at', $config);
        $this->assertArrayHasKey('settings', $config);
    }

    /** @test */
    public function it_can_import_configuration()
    {
        $config = [
            'version' => '1.0.0',
            'settings' => [
                'app' => [
                    'name' => 'Imported App',
                ],
            ],
        ];

        $result = $this->manager->importConfiguration($config);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_rejects_invalid_import_format()
    {
        $config = [
            'invalid' => 'format',
        ];

        $result = $this->manager->importConfiguration($config);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    /** @test */
    public function it_can_get_settings_for_ui()
    {
        $settings = $this->manager->getSettingsForUI();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('groups', $settings);
        $this->assertIsArray($settings['groups']);
        $this->assertNotEmpty($settings['groups']);

        $firstGroup = $settings['groups'][0];
        $this->assertArrayHasKey('id', $firstGroup);
        $this->assertArrayHasKey('label', $firstGroup);
        $this->assertArrayHasKey('icon', $firstGroup);
        $this->assertArrayHasKey('settings', $firstGroup);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        Cache::shouldReceive('forget')->once();
        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->manager->clearCache();

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        $stats = $this->manager->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
    }

    /** @test */
    public function it_formats_settings_for_ui_correctly()
    {
        $settings = $this->manager->getSettingsForUI();
        $appGroup = collect($settings['groups'])->firstWhere('id', 'app');

        $this->assertNotNull($appGroup);
        $this->assertIsArray($appGroup['settings']);

        foreach ($appGroup['settings'] as $setting) {
            $this->assertArrayHasKey('key', $setting);
            $this->assertArrayHasKey('value', $setting);
            $this->assertArrayHasKey('type', $setting);
            $this->assertArrayHasKey('label', $setting);
            $this->assertArrayHasKey('editable', $setting);
        }
    }

    /** @test */
    public function it_identifies_non_editable_settings()
    {
        $settings = $this->manager->getSettingsForUI();
        $appGroup = collect($settings['groups'])->firstWhere('id', 'app');

        $versionSetting = collect($appGroup['settings'])->firstWhere('key', 'version');

        $this->assertNotNull($versionSetting);
        $this->assertFalse($versionSetting['editable']);
    }

    /** @test */
    public function it_caches_all_settings()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'app' => [],
                'theme' => [],
                'localization' => [],
                'rbac' => [],
                'performance' => [],
                'cache' => [],
            ]);

        $this->manager->getAllSettings();

        // If we get here, cache was used
        $this->assertTrue(true);
    }
}

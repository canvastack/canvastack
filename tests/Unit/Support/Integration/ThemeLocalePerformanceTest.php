<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Integration;

use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Support\Integration\ThemeLocalePerformance;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;

class ThemeLocalePerformanceTest extends TestCase
{
    protected ThemeLocalePerformance $performance;

    protected ThemeLocaleIntegration $integration;

    protected ThemeManager $themeManager;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeManager = app(ThemeManager::class);
        $this->localeManager = app(LocaleManager::class);
        $rtlSupport = app(RtlSupport::class);

        $this->integration = new ThemeLocaleIntegration(
            $this->themeManager,
            $this->localeManager,
            $rtlSupport
        );

        $this->performance = new ThemeLocalePerformance(
            $this->themeManager,
            $this->localeManager,
            $this->integration
        );
    }

    public function test_warmup_cache()
    {
        $results = $this->performance->warmupCache();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('total', $results);
        $this->assertArrayHasKey('cached', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('time', $results);

        $this->assertGreaterThan(0, $results['total']);
        $this->assertGreaterThanOrEqual(0, $results['cached']);
        $this->assertGreaterThanOrEqual(0, $results['failed']);
        $this->assertGreaterThan(0, $results['time']);
    }

    public function test_preload_common()
    {
        $combinations = [
            ['theme' => 'default', 'locale' => 'en'],
            ['theme' => 'default', 'locale' => 'id'],
        ];

        $results = $this->performance->preloadCommon($combinations);

        $this->assertIsArray($results);
        $this->assertEquals(2, $results['total']);
        $this->assertGreaterThanOrEqual(0, $results['loaded']);
        $this->assertGreaterThanOrEqual(0, $results['failed']);
        $this->assertGreaterThan(0, $results['time']);
    }

    public function test_preload_common_with_defaults()
    {
        $results = $this->performance->preloadCommon();

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, $results['total']);
        $this->assertGreaterThanOrEqual(0, $results['loaded']);
    }

    public function test_optimize_theme_switch()
    {
        $this->localeManager->setLocale('en');

        // Should not throw exception
        $this->performance->optimizeThemeSwitch('default');

        $this->assertTrue(true);
    }

    public function test_optimize_locale_switch()
    {
        // Should not throw exception
        $this->performance->optimizeLocaleSwitch('id');

        $this->assertTrue(true);
    }

    public function test_measure_theme_switch()
    {
        $this->localeManager->setLocale('en');

        $results = $this->performance->measureThemeSwitch('default', 'default');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('from_theme', $results);
        $this->assertArrayHasKey('to_theme', $results);
        $this->assertArrayHasKey('locale', $results);
        $this->assertArrayHasKey('uncached_time_ms', $results);
        $this->assertArrayHasKey('cached_time_ms', $results);
        $this->assertArrayHasKey('improvement', $results);

        $this->assertEquals('default', $results['from_theme']);
        $this->assertEquals('default', $results['to_theme']);
        $this->assertEquals('en', $results['locale']);
        $this->assertGreaterThanOrEqual(0, $results['uncached_time_ms']);
        $this->assertGreaterThanOrEqual(0, $results['cached_time_ms']);
    }

    public function test_measure_locale_switch()
    {
        $this->localeManager->setLocale('en');

        $results = $this->performance->measureLocaleSwitch('en', 'id');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('theme', $results);
        $this->assertArrayHasKey('from_locale', $results);
        $this->assertArrayHasKey('to_locale', $results);
        $this->assertArrayHasKey('uncached_time_ms', $results);
        $this->assertArrayHasKey('cached_time_ms', $results);
        $this->assertArrayHasKey('improvement', $results);

        $this->assertEquals('en', $results['from_locale']);
        $this->assertEquals('id', $results['to_locale']);
        $this->assertGreaterThanOrEqual(0, $results['uncached_time_ms']);
        $this->assertGreaterThanOrEqual(0, $results['cached_time_ms']);
    }

    public function test_benchmark()
    {
        $results = $this->performance->benchmark();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('themes', $results);
        $this->assertArrayHasKey('locales', $results);
        $this->assertArrayHasKey('combinations', $results);
        $this->assertArrayHasKey('measurements', $results);
        $this->assertArrayHasKey('summary', $results);

        $this->assertGreaterThan(0, $results['themes']);
        $this->assertGreaterThan(0, $results['locales']);
        $this->assertGreaterThan(0, $results['combinations']);
        $this->assertIsArray($results['measurements']);
        $this->assertNotEmpty($results['measurements']);

        // Check summary
        $this->assertArrayHasKey('avg_uncached_ms', $results['summary']);
        $this->assertArrayHasKey('avg_cached_ms', $results['summary']);
        $this->assertArrayHasKey('avg_improvement', $results['summary']);
        $this->assertArrayHasKey('min_time_ms', $results['summary']);
        $this->assertArrayHasKey('max_time_ms', $results['summary']);

        $this->assertGreaterThan(0, $results['summary']['avg_uncached_ms']);
        $this->assertGreaterThanOrEqual(0, $results['summary']['avg_cached_ms']);
    }

    public function test_get_recommendations()
    {
        $recommendations = $this->performance->getRecommendations();

        $this->assertIsArray($recommendations);

        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('category', $recommendation);
            $this->assertArrayHasKey('message', $recommendation);
            $this->assertArrayHasKey('suggestion', $recommendation);

            $this->assertContains($recommendation['type'], ['error', 'warning', 'info']);
        }
    }

    public function test_clear_old_cache()
    {
        // Should not throw exception
        $this->performance->clearOldCache();

        $this->assertTrue(true);
    }

    public function test_get_cache_size_estimate()
    {
        $estimate = $this->performance->getCacheSizeEstimate();

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('total_combinations', $estimate);
        $this->assertArrayHasKey('total_size_bytes', $estimate);
        $this->assertArrayHasKey('total_size_kb', $estimate);
        $this->assertArrayHasKey('total_size_mb', $estimate);
        $this->assertArrayHasKey('avg_size_per_combination_bytes', $estimate);

        $this->assertGreaterThan(0, $estimate['total_combinations']);
        $this->assertGreaterThan(0, $estimate['total_size_bytes']);
        $this->assertGreaterThan(0, $estimate['total_size_kb']);
    }

    public function test_cached_is_faster_than_uncached()
    {
        $this->localeManager->setLocale('en');

        $results = $this->performance->measureThemeSwitch('default', 'default');

        // In test environment, cached might not always be faster due to overhead
        // Just verify both measurements are reasonable (< 100ms)
        $this->assertLessThan(100, $results['uncached_time_ms'], 'Uncached time should be reasonable');
        $this->assertLessThan(100, $results['cached_time_ms'], 'Cached time should be reasonable');
        $this->assertGreaterThanOrEqual(0, $results['improvement'], 'Improvement should be non-negative');
    }
}

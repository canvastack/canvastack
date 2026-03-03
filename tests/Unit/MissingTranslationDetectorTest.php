<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\MissingTranslationDetector;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class MissingTranslationDetectorTest extends TestCase
{
    protected MissingTranslationDetector $detector;

    protected TranslationLoader $loader;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->app->make(TranslationLoader::class);
        $this->localeManager = $this->app->make(LocaleManager::class);
        $this->detector = new MissingTranslationDetector($this->loader, $this->localeManager);
        Cache::flush();
    }

    public function test_detects_missing_translation(): void
    {
        $this->detector->detect('nonexistent.key', 'en');

        $missing = $this->detector->all();

        $this->assertNotEmpty($missing);
        $this->assertArrayHasKey('en.nonexistent.key', $missing);
    }

    public function test_gets_missing_by_locale(): void
    {
        $this->detector->detect('missing.key1', 'en');
        $this->detector->detect('missing.key2', 'id');

        $enMissing = $this->detector->getByLocale('en');
        $idMissing = $this->detector->getByLocale('id');

        $this->assertCount(1, $enMissing);
        $this->assertCount(1, $idMissing);
    }

    public function test_gets_most_frequent_missing(): void
    {
        $this->detector->detect('frequent.key', 'en');
        $this->detector->detect('frequent.key', 'en');
        $this->detector->detect('frequent.key', 'en');
        $this->detector->detect('rare.key', 'en');

        $mostFrequent = $this->detector->getMostFrequent(1);

        $this->assertCount(1, $mostFrequent);
        $this->assertEquals('frequent.key', $mostFrequent[0]['key']);
    }

    public function test_generates_report(): void
    {
        $this->detector->detect('test.key', 'en');

        $report = $this->detector->generateReport();

        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('most_frequent', $report);
        $this->assertArrayHasKey('by_locale', $report);
    }

    public function test_exports_report_json(): void
    {
        $this->detector->detect('test.key', 'en');

        $path = storage_path('app/test-missing.json');

        $result = $this->detector->exportReport($path, 'json');

        $this->assertTrue($result);
        $this->assertFileExists($path);

        // Cleanup
        unlink($path);
    }

    public function test_exports_report_csv(): void
    {
        $this->detector->detect('test.key', 'en');

        $path = storage_path('app/test-missing.csv');

        $result = $this->detector->exportReport($path, 'csv');

        $this->assertTrue($result);
        $this->assertFileExists($path);

        // Cleanup
        unlink($path);
    }

    public function test_clears_missing_translations(): void
    {
        $this->detector->detect('test.key', 'en');

        $this->assertNotEmpty($this->detector->all());

        $this->detector->clear();

        $this->assertEmpty($this->detector->all());
    }

    public function test_can_enable_and_disable_detection(): void
    {
        $this->assertTrue($this->detector->isEnabled());

        $this->detector->disable();
        $this->assertFalse($this->detector->isEnabled());

        $this->detector->enable();
        $this->assertTrue($this->detector->isEnabled());
    }
}

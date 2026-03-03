<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Support\Localization\TranslationRegistry;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class TranslationRegistryTest extends TestCase
{
    protected TranslationRegistry $registry;

    protected TranslationLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->app->make(TranslationLoader::class);
        $this->registry = new TranslationRegistry($this->loader);
        Cache::flush();
    }

    public function test_builds_registry_from_translations(): void
    {
        $this->registry->buildRegistry();

        $all = $this->registry->all();

        $this->assertIsArray($all);
        $this->assertNotEmpty($all);
    }

    public function test_tracks_translation_usage(): void
    {
        $key = 'ui.welcome';

        $this->registry->trackUsage($key);
        $this->registry->trackUsage($key);

        $info = $this->registry->get($key);

        $this->assertNotNull($info);
        $this->assertEquals(2, $info['usage_count']);
    }

    public function test_gets_missing_translations_for_locale(): void
    {
        $this->registry->buildRegistry();

        $missing = $this->registry->getMissing('id');

        $this->assertIsArray($missing);
    }

    public function test_gets_unused_translations(): void
    {
        $this->registry->buildRegistry();

        $unused = $this->registry->getUnused();

        $this->assertIsArray($unused);
    }

    public function test_gets_statistics(): void
    {
        $this->registry->buildRegistry();

        $stats = $this->registry->getStatistics();

        $this->assertArrayHasKey('total_keys', $stats);
        $this->assertArrayHasKey('total_usage', $stats);
        $this->assertArrayHasKey('unused_keys', $stats);
        $this->assertArrayHasKey('locales', $stats);
    }

    public function test_exports_registry(): void
    {
        $this->registry->buildRegistry();

        $path = storage_path('app/test-registry.json');

        $result = $this->registry->export($path);

        $this->assertTrue($result);
        $this->assertFileExists($path);

        // Cleanup
        unlink($path);
    }

    public function test_imports_registry(): void
    {
        $this->registry->buildRegistry();

        $exportPath = storage_path('app/test-registry-import.json');
        $this->registry->export($exportPath);

        $this->registry->clearCache();

        $result = $this->registry->import($exportPath);

        $this->assertTrue($result);

        // Cleanup
        unlink($exportPath);
    }
}

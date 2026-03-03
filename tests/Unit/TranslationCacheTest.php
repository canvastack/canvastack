<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Support\Localization\TranslationCache;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class TranslationCacheTest extends TestCase
{
    protected TranslationCache $cache;

    protected TranslationLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->app->make(TranslationLoader::class);
        $this->cache = new TranslationCache($this->loader);
        Cache::flush();
    }

    public function test_gets_translation_from_cache(): void
    {
        $value = $this->cache->get('en', 'ui.welcome');

        $this->assertIsString($value);
    }

    public function test_gets_all_translations_for_locale(): void
    {
        $translations = $this->cache->getAll('en');

        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    public function test_puts_translation_in_cache(): void
    {
        $this->cache->put('en', 'test.key', 'Test Value');

        $this->assertTrue($this->cache->has('en', 'test.key'));
    }

    public function test_forgets_translation_from_cache(): void
    {
        $this->cache->put('en', 'test.key', 'Test Value');
        $this->cache->forget('en', 'test.key');

        $this->assertFalse($this->cache->has('en', 'test.key'));
    }

    public function test_flushes_cache_for_locale(): void
    {
        $this->cache->put('en', 'test.key1', 'Value 1');
        $this->cache->put('en', 'test.key2', 'Value 2');

        $this->cache->flush('en');

        $this->assertFalse($this->cache->has('en', 'test.key1'));
        $this->assertFalse($this->cache->has('en', 'test.key2'));
    }

    public function test_warms_cache_for_locale(): void
    {
        $count = $this->cache->warm('en');

        $this->assertGreaterThan(0, $count);
    }

    public function test_refreshes_cache(): void
    {
        $count = $this->cache->refresh('en');

        $this->assertGreaterThan(0, $count);
    }

    public function test_gets_statistics(): void
    {
        $stats = $this->cache->getStatistics();

        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('driver', $stats);
    }

    public function test_can_enable_and_disable_cache(): void
    {
        $this->assertTrue($this->cache->isEnabled());

        $this->cache->disable();
        $this->assertFalse($this->cache->isEnabled());

        $this->cache->enable();
        $this->assertTrue($this->cache->isEnabled());
    }

    public function test_gets_and_sets_ttl(): void
    {
        $this->cache->setTtl(7200);

        $this->assertEquals(7200, $this->cache->getTtl());
    }

    public function test_invalidates_cache(): void
    {
        $this->cache->put('en', 'test.key', 'Test Value');

        $this->cache->invalidate('en');

        $this->assertFalse($this->cache->has('en', 'test.key'));
    }
}

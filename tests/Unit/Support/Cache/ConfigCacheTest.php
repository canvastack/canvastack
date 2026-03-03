<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Cache;

use Canvastack\Canvastack\Support\Cache\CacheManager;
use Canvastack\Canvastack\Support\Cache\ConfigCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * ConfigCache Test.
 */
class ConfigCacheTest extends TestCase
{
    protected ConfigCache $configCache;

    protected CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('canvastack.cache', [
            'enabled' => true,
            'driver' => 'file',
            'ttl' => [
                'config' => 3600,
                'default' => 3600,
            ],
        ]);

        Config::set('canvastack.test_key', 'test_value');
        Config::set('canvastack-ui.theme.colors.primary', '#6366f1');

        // Use file store to avoid Redis dependency
        $this->cacheManager = new CacheManager([
            'driver' => 'file',
            'prefix' => 'test_config',
            'ttl' => 3600,
            'file' => [
                'path' => sys_get_temp_dir() . '/canvastack-config-cache-test',
            ],
        ]);
        
        $this->configCache = new ConfigCache($this->cacheManager);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clean up cache files
        $this->cacheManager->clear();
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_and_cache_config_values(): void
    {
        $value = $this->configCache->get('canvastack.test_key');

        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_uses_memory_cache_for_repeated_access(): void
    {
        // First access - should cache
        $value1 = $this->configCache->get('canvastack.test_key');

        // Change the actual config
        Config::set('canvastack.test_key', 'changed_value');

        // Second access - should use memory cache (old value)
        $value2 = $this->configCache->get('canvastack.test_key');

        $this->assertEquals($value1, $value2);
        $this->assertEquals('test_value', $value2);
    }

    /** @test */
    public function it_can_get_all_config_for_namespace(): void
    {
        Config::set('canvastack', [
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $all = $this->configCache->getAll('canvastack');

        $this->assertIsArray($all);
        $this->assertEquals('value1', $all['key1']);
        $this->assertEquals('value2', $all['key2']);
    }

    /** @test */
    public function it_can_invalidate_specific_config(): void
    {
        $key = 'canvastack.test_key';

        // Cache the value
        $this->configCache->get($key);

        // Invalidate
        $this->configCache->invalidate($key);

        // Change config
        Config::set('canvastack.test_key', 'new_value');

        // Should get new value
        $value = $this->configCache->get($key);
        $this->assertEquals('new_value', $value);
    }

    /** @test */
    public function it_can_invalidate_all_config_cache(): void
    {
        // Cache multiple values
        $this->configCache->get('canvastack.test_key');
        $this->configCache->get('canvastack-ui.theme.colors.primary');

        // Invalidate all
        $this->configCache->invalidate();

        // Memory cache should be cleared
        $stats = $this->configCache->stats();
        $this->assertEquals(0, $stats['memory_cache_size']);
    }

    /** @test */
    public function it_can_warm_up_cache(): void
    {
        Config::set('canvastack.cache.enabled', true);
        Config::set('canvastack.cache.driver', 'redis');
        Config::set('canvastack-ui.theme.colors', ['primary' => '#6366f1']);
        Config::set('canvastack-ui.dark_mode.enabled', true);

        $this->configCache->warmUp();

        $stats = $this->configCache->stats();
        $this->assertGreaterThan(0, $stats['memory_cache_size']);
    }

    /** @test */
    public function it_returns_default_value_when_config_not_found(): void
    {
        $value = $this->configCache->get('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_provides_cache_statistics(): void
    {
        $this->configCache->get('canvastack.test_key');
        $this->configCache->get('canvastack-ui.theme.colors.primary');

        $stats = $this->configCache->stats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('memory_cache_size', $stats);
        $this->assertArrayHasKey('memory_cache_keys', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['memory_cache_size']);
    }
}

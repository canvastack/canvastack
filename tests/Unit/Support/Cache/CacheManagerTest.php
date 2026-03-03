<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Cache;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Support\Cache\CacheManager;
use Canvastack\Canvastack\Support\Cache\Stores\FileStore;

/**
 * Test for CacheManager.
 */
class CacheManagerTest extends TestCase
{
    protected CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheManager([
            'driver' => 'file',
            'prefix' => 'test',
            'ttl' => 3600,
            'file' => [
                'path' => sys_get_temp_dir() . '/canvastack-cache-test',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    /**
     * Test that cache manager can be instantiated.
     */
    public function test_cache_manager_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CacheManager::class, $this->cache);
    }

    /**
     * Test that cache manager uses file store.
     */
    public function test_cache_manager_uses_file_store(): void
    {
        $store = $this->cache->getStore();
        
        $this->assertInstanceOf(FileStore::class, $store);
    }

    /**
     * Test that cache can store and retrieve values.
     */
    public function test_cache_can_store_and_retrieve_values(): void
    {
        $this->cache->put('test_key', 'test_value', 60);
        
        $value = $this->cache->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }

    /**
     * Test that cache returns default value for missing keys.
     */
    public function test_cache_returns_default_for_missing_keys(): void
    {
        $value = $this->cache->get('missing_key', 'default');
        
        $this->assertEquals('default', $value);
    }

    /**
     * Test that cache can store values forever.
     */
    public function test_cache_can_store_forever(): void
    {
        $this->cache->forever('forever_key', 'forever_value');
        
        $value = $this->cache->get('forever_key');
        
        $this->assertEquals('forever_value', $value);
    }

    /**
     * Test that cache remember works.
     */
    public function test_cache_remember_works(): void
    {
        $callCount = 0;
        
        $value1 = $this->cache->remember('remember_key', 60, function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        $value2 = $this->cache->remember('remember_key', 60, function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        $this->assertEquals('computed_value', $value1);
        $this->assertEquals('computed_value', $value2);
        $this->assertEquals(1, $callCount, 'Callback should only be called once');
    }

    /**
     * Test that cache has method works.
     */
    public function test_cache_has_method_works(): void
    {
        $this->cache->put('exists_key', 'value', 60);
        
        $this->assertTrue($this->cache->has('exists_key'));
        $this->assertFalse($this->cache->has('missing_key'));
    }

    /**
     * Test that cache forget works.
     */
    public function test_cache_forget_works(): void
    {
        $this->cache->put('delete_key', 'value', 60);
        
        $this->assertTrue($this->cache->has('delete_key'));
        
        $this->cache->forget('delete_key');
        
        $this->assertFalse($this->cache->has('delete_key'));
    }

    /**
     * Test that cache tags work.
     */
    public function test_cache_tags_work(): void
    {
        $this->cache->tags(['tag1', 'tag2'])->put('tagged_key', 'tagged_value', 60);
        
        $value = $this->cache->tags(['tag1'])->get('tagged_key');
        
        $this->assertEquals('tagged_value', $value);
    }

    /**
     * Test that cache flush by tags works.
     */
    public function test_cache_flush_by_tags_works(): void
    {
        $this->cache->tags(['tag1'])->put('key1', 'value1', 60);
        $this->cache->tags(['tag2'])->put('key2', 'value2', 60);
        
        $this->cache->flush(['tag1']);
        
        $this->assertNull($this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    /**
     * Test that cache increment works.
     */
    public function test_cache_increment_works(): void
    {
        $this->cache->put('counter', 0, 60);
        
        $this->cache->increment('counter');
        $this->assertEquals(1, $this->cache->get('counter'));
        
        $this->cache->increment('counter', 5);
        $this->assertEquals(6, $this->cache->get('counter'));
    }

    /**
     * Test that cache decrement works.
     */
    public function test_cache_decrement_works(): void
    {
        $this->cache->put('counter', 10, 60);
        
        $this->cache->decrement('counter');
        $this->assertEquals(9, $this->cache->get('counter'));
        
        $this->cache->decrement('counter', 5);
        $this->assertEquals(4, $this->cache->get('counter'));
    }

    /**
     * Test that cache stats are available.
     */
    public function test_cache_stats_are_available(): void
    {
        $stats = $this->cache->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertEquals('file', $stats['driver']);
    }

    /**
     * Test that cache clear works.
     */
    public function test_cache_clear_works(): void
    {
        $this->cache->put('key1', 'value1', 60);
        $this->cache->put('key2', 'value2', 60);
        
        $this->cache->clear();
        
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    /**
     * Test that cache can store complex data types.
     */
    public function test_cache_can_store_complex_data_types(): void
    {
        $array = ['key' => 'value', 'nested' => ['data' => 123]];
        $object = (object)['property' => 'value'];
        
        $this->cache->put('array', $array, 60);
        $this->cache->put('object', $object, 60);
        
        $this->assertEquals($array, $this->cache->get('array'));
        $this->assertEquals($object, $this->cache->get('object'));
    }
}

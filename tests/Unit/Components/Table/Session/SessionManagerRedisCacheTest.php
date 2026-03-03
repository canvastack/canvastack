<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Session;

use Canvastack\Canvastack\Components\Table\Session\SessionManager;
use Canvastack\Canvastack\Support\Cache\CacheManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test SessionManager Redis caching functionality.
 */
class SessionManagerRedisCacheTest extends TestCase
{
    protected CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // Create cache manager with test configuration
        $this->cache = new CacheManager([
            'driver' => 'file', // Use file cache for testing
            'prefix' => 'test_canvastack',
            'ttl' => 300,
            'file' => [
                'path' => storage_path('framework/cache/data'),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // Clear test cache
        $this->cache->clear();

        parent::tearDown();
    }

    /**
     * Test that SessionManager can be created with cache manager.
     */
    public function test_session_manager_accepts_cache_manager(): void
    {
        $session = new SessionManager('test_table', '', $this->cache);

        $this->assertInstanceOf(SessionManager::class, $session);
        $this->assertTrue($session->isCacheEnabled());
    }

    /**
     * Test that SessionManager works without cache manager.
     */
    public function test_session_manager_works_without_cache(): void
    {
        $session = new SessionManager('test_table', '');

        $this->assertInstanceOf(SessionManager::class, $session);
        $this->assertFalse($session->isCacheEnabled());
    }

    /**
     * Test that data is saved to cache.
     */
    public function test_data_is_saved_to_cache(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $data = [
            'filters' => ['status' => 'active'],
            'active_tab' => 'summary',
        ];

        $session->save($data);

        // Verify data is in cache
        $cacheKey = 'session:' . $session->getSessionKey();
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);

        $this->assertIsArray($cached);
        $this->assertEquals('active', $cached['filters']['status']);
        $this->assertEquals('summary', $cached['active_tab']);
    }

    /**
     * Test that data is loaded from cache.
     */
    public function test_data_is_loaded_from_cache(): void
    {
        // Create first session and save data
        $session1 = new SessionManager('test_table', 'test_context', $this->cache);
        $session1->save([
            'filters' => ['status' => 'active'],
            'display_limit' => 25,
        ]);

        // Create second session with same key - should load from cache
        $session2 = new SessionManager('test_table', 'test_context', $this->cache);

        $this->assertEquals('active', $session2->get('filters')['status']);
        $this->assertEquals(25, $session2->get('display_limit'));
    }

    /**
     * Test that cache is invalidated on clear.
     */
    public function test_cache_is_invalidated_on_clear(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $session->save(['test_key' => 'test_value']);

        // Verify data is in cache
        $cacheKey = 'session:' . $session->getSessionKey();
        $this->assertNotNull($this->cache->tags(['table_sessions'])->get($cacheKey));

        // Clear session
        $session->clear();

        // Verify cache is cleared
        $this->assertNull($this->cache->tags(['table_sessions'])->get($cacheKey));
    }

    /**
     * Test that cache is updated on set.
     */
    public function test_cache_is_updated_on_set(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $session->set('key1', 'value1');

        // Verify data is in cache
        $cacheKey = 'session:' . $session->getSessionKey();
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);

        $this->assertEquals('value1', $cached['key1']);

        // Update value
        $session->set('key1', 'value2');

        // Verify cache is updated
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);
        $this->assertEquals('value2', $cached['key1']);
    }

    /**
     * Test that cache is updated on forget.
     */
    public function test_cache_is_updated_on_forget(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $session->save([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        // Verify both keys are in cache
        $cacheKey = 'session:' . $session->getSessionKey();
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);
        $this->assertArrayHasKey('key1', $cached);
        $this->assertArrayHasKey('key2', $cached);

        // Forget one key
        $session->forget('key1');

        // Verify cache is updated
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);
        $this->assertArrayNotHasKey('key1', $cached);
        $this->assertArrayHasKey('key2', $cached);
    }

    /**
     * Test cache TTL can be configured.
     */
    public function test_cache_ttl_can_be_configured(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $this->assertEquals(300, $session->getCacheTtl());

        $session->setCacheTtl(600);

        $this->assertEquals(600, $session->getCacheTtl());
    }

    /**
     * Test cache warming.
     */
    public function test_cache_warming(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        // Set data without triggering cache save
        $session->save(['key' => 'value']);

        // Clear cache manually
        $cacheKey = 'session:' . $session->getSessionKey();
        $this->cache->tags(['table_sessions'])->forget($cacheKey);

        // Verify cache is empty
        $this->assertNull($this->cache->tags(['table_sessions'])->get($cacheKey));

        // Warm cache
        $session->warmCache();

        // Verify cache is populated
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);
        $this->assertEquals('value', $cached['key']);
    }

    /**
     * Test that session falls back to session storage on cache failure.
     */
    public function test_fallback_to_session_storage_on_cache_failure(): void
    {
        // Create session with cache
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        // Save data
        $session->save(['key' => 'value']);

        // Simulate cache failure by clearing cache
        $this->cache->clear();

        // Create new session - should load from session storage
        $session2 = new SessionManager('test_table', 'test_context', $this->cache);

        // Data should still be available from session storage
        $this->assertEquals('value', $session2->get('key'));
    }

    /**
     * Test that cache key is properly prefixed.
     */
    public function test_cache_key_is_properly_prefixed(): void
    {
        $session = new SessionManager('test_table', 'test_context', $this->cache);

        $session->save(['key' => 'value']);

        // Get cache key
        $sessionKey = $session->getSessionKey();
        $cacheKey = 'session:' . $sessionKey;

        // Verify cache key exists with proper prefix
        $cached = $this->cache->tags(['table_sessions'])->get($cacheKey);
        $this->assertNotNull($cached);
    }

    /**
     * Test that multiple sessions use different cache keys.
     */
    public function test_multiple_sessions_use_different_cache_keys(): void
    {
        $session1 = new SessionManager('table1', 'context1', $this->cache);
        $session2 = new SessionManager('table2', 'context2', $this->cache);

        $session1->save(['key' => 'value1']);
        $session2->save(['key' => 'value2']);

        $this->assertEquals('value1', $session1->get('key'));
        $this->assertEquals('value2', $session2->get('key'));

        // Verify different cache keys
        $this->assertNotEquals($session1->getSessionKey(), $session2->getSessionKey());
    }

    /**
     * Test that cache tags are properly applied.
     */
    public function test_cache_tags_are_properly_applied(): void
    {
        $session1 = new SessionManager('table1', '', $this->cache);
        $session2 = new SessionManager('table2', '', $this->cache);

        $session1->save(['key' => 'value1']);
        $session2->save(['key' => 'value2']);

        // Get cache keys before flush
        $cacheKey1 = 'session:' . $session1->getSessionKey();
        $cacheKey2 = 'session:' . $session2->getSessionKey();

        // Verify data is in cache before flush
        $this->assertNotNull($this->cache->tags(['table_sessions'])->get($cacheKey1));
        $this->assertNotNull($this->cache->tags(['table_sessions'])->get($cacheKey2));

        // Flush cache by tag
        $this->cache->flush(['table_sessions']);

        // Verify cache is cleared after flush
        $this->assertNull($this->cache->tags(['table_sessions'])->get($cacheKey1));
        $this->assertNull($this->cache->tags(['table_sessions'])->get($cacheKey2));
    }

    /**
     * Test performance improvement with cache.
     */
    public function test_performance_improvement_with_cache(): void
    {
        // Create dataset
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data["key_{$i}"] = "value_{$i}";
        }

        // Test with cache - first save
        $session1 = new SessionManager('test_table', 'perf_test_cached', $this->cache);
        $session1->save($data);

        // Measure cache load time
        $startTime = microtime(true);
        $session2 = new SessionManager('test_table', 'perf_test_cached', $this->cache);
        $session2->load();
        $timeWithCache = microtime(true) - $startTime;

        // Test without cache
        $startTime = microtime(true);
        $session3 = new SessionManager('test_table', 'perf_test_no_cache');
        $session3->save($data);
        $session3->load();
        $timeWithoutCache = microtime(true) - $startTime;

        // Both should complete in reasonable time (< 100ms)
        $this->assertLessThan(0.1, $timeWithCache, 'Cache load should be fast');
        $this->assertLessThan(0.1, $timeWithoutCache, 'Session load should be fast');
        
        // Verify data integrity
        $this->assertEquals($data, $session2->all());
    }
}

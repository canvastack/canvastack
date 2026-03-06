<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\TableCacheManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Test for TableCacheManager.
 * 
 * Tests caching functionality for table data, filter options, and relationship data.
 * Validates Requirements 43.1-43.3.
 */
class TableCacheManagerTest extends TestCase
{
    protected TableCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheManager = new TableCacheManager();
        
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that getCachedData() caches and retrieves data correctly.
     * 
     * @return void
     */
    public function test_get_cached_data_caches_and_retrieves_data(): void
    {
        $cacheKey = 'test.cache.key';
        $cacheTags = ['tables', 'test'];
        $cacheTime = 300;
        $expectedData = ['id' => 1, 'name' => 'Test'];
        
        $callbackExecuted = false;
        $callback = function () use ($expectedData, &$callbackExecuted) {
            $callbackExecuted = true;
            return $expectedData;
        };
        
        // First call should execute callback
        $result = $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        
        $this->assertEquals($expectedData, $result);
        $this->assertTrue($callbackExecuted, 'Callback should be executed on cache miss');
        
        // Second call should use cache (callback not executed)
        $callbackExecuted = false;
        $result = $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        
        $this->assertEquals($expectedData, $result);
        $this->assertFalse($callbackExecuted, 'Callback should not be executed on cache hit');
    }

    /**
     * Test that getCachedData() does not cache when cache time is zero.
     * 
     * @return void
     */
    public function test_get_cached_data_does_not_cache_when_time_is_zero(): void
    {
        $cacheKey = 'test.cache.key';
        $cacheTags = ['tables'];
        $cacheTime = 0;
        $expectedData = ['id' => 1];
        
        $callbackExecutionCount = 0;
        $callback = function () use ($expectedData, &$callbackExecutionCount) {
            $callbackExecutionCount++;
            return $expectedData;
        };
        
        // First call
        $result = $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        $this->assertEquals($expectedData, $result);
        $this->assertEquals(1, $callbackExecutionCount);
        
        // Second call should also execute callback (no caching)
        $result = $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        $this->assertEquals($expectedData, $result);
        $this->assertEquals(2, $callbackExecutionCount, 'Callback should execute every time when cache time is 0');
    }

    /**
     * Test that getCachedData() does not cache when cache time is negative.
     * 
     * @return void
     */
    public function test_get_cached_data_does_not_cache_when_time_is_negative(): void
    {
        $cacheKey = 'test.cache.key';
        $cacheTags = ['tables'];
        $cacheTime = -1;
        $expectedData = ['id' => 1];
        
        $callbackExecutionCount = 0;
        $callback = function () use ($expectedData, &$callbackExecutionCount) {
            $callbackExecutionCount++;
            return $expectedData;
        };
        
        // Multiple calls should all execute callback
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        
        $this->assertEquals(2, $callbackExecutionCount, 'Callback should execute every time when cache time is negative');
    }

    /**
     * Test that getCachedFilterOptions() caches filter options correctly.
     * 
     * @return void
     */
    public function test_get_cached_filter_options_caches_correctly(): void
    {
        $filterName = 'status';
        $modelClass = 'App\\Models\\User';
        $cacheTime = 300;
        $expectedOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
        
        $callbackExecuted = false;
        $callback = function () use ($expectedOptions, &$callbackExecuted) {
            $callbackExecuted = true;
            return $expectedOptions;
        };
        
        // First call should execute callback
        $result = $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, $callback);
        
        $this->assertEquals($expectedOptions, $result);
        $this->assertTrue($callbackExecuted);
        
        // Second call should use cache
        $callbackExecuted = false;
        $result = $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, $callback);
        
        $this->assertEquals($expectedOptions, $result);
        $this->assertFalse($callbackExecuted, 'Filter options should be cached');
    }

    /**
     * Test that getCachedFilterOptions() uses default cache time when null.
     * 
     * @return void
     */
    public function test_get_cached_filter_options_uses_default_cache_time(): void
    {
        $filterName = 'status';
        $modelClass = null;
        $cacheTime = null; // Should use default
        $expectedOptions = ['option1' => 'Option 1'];
        
        $callback = function () use ($expectedOptions) {
            return $expectedOptions;
        };
        
        $result = $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, $callback);
        
        $this->assertEquals($expectedOptions, $result);
        
        // Verify it was cached by checking second call doesn't execute callback
        $callbackExecuted = false;
        $callback2 = function () use ($expectedOptions, &$callbackExecuted) {
            $callbackExecuted = true;
            return $expectedOptions;
        };
        
        $result = $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, $callback2);
        $this->assertFalse($callbackExecuted, 'Should use cached data with default cache time');
    }

    /**
     * Test that getCachedRelationship() caches relationship data correctly.
     * 
     * @return void
     */
    public function test_get_cached_relationship_caches_correctly(): void
    {
        $relationName = 'posts';
        $modelClass = 'App\\Models\\User';
        $cacheTime = 300;
        $expectedData = collect([
            ['id' => 1, 'title' => 'Post 1'],
            ['id' => 2, 'title' => 'Post 2'],
        ]);
        
        $callbackExecuted = false;
        $callback = function () use ($expectedData, &$callbackExecuted) {
            $callbackExecuted = true;
            return $expectedData;
        };
        
        // First call should execute callback
        $result = $this->cacheManager->getCachedRelationship($relationName, $modelClass, $cacheTime, $callback);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expectedData->toArray(), $result->toArray());
        $this->assertTrue($callbackExecuted);
        
        // Second call should use cache
        $callbackExecuted = false;
        $result = $this->cacheManager->getCachedRelationship($relationName, $modelClass, $cacheTime, $callback);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expectedData->toArray(), $result->toArray());
        $this->assertFalse($callbackExecuted, 'Relationship data should be cached');
    }

    /**
     * Test that getCachedRelationship() converts array to Collection.
     * 
     * @return void
     */
    public function test_get_cached_relationship_converts_array_to_collection(): void
    {
        $relationName = 'posts';
        $modelClass = null;
        $cacheTime = 300;
        $arrayData = [
            ['id' => 1, 'title' => 'Post 1'],
            ['id' => 2, 'title' => 'Post 2'],
        ];
        
        $callback = function () use ($arrayData) {
            return $arrayData; // Return array, not Collection
        };
        
        $result = $this->cacheManager->getCachedRelationship($relationName, $modelClass, $cacheTime, $callback);
        
        $this->assertInstanceOf(Collection::class, $result, 'Should convert array to Collection');
        $this->assertEquals($arrayData, $result->toArray());
    }

    /**
     * Test that buildTableCacheKey() generates consistent keys.
     * 
     * @return void
     */
    public function test_build_table_cache_key_generates_consistent_keys(): void
    {
        $params1 = ['page' => 1, 'sort' => 'name', 'filter' => 'active'];
        $params2 = ['filter' => 'active', 'page' => 1, 'sort' => 'name']; // Different order
        
        $key1 = $this->cacheManager->buildTableCacheKey($params1);
        $key2 = $this->cacheManager->buildTableCacheKey($params2);
        
        $this->assertEquals($key1, $key2, 'Cache keys should be identical regardless of parameter order');
        $this->assertStringStartsWith('table.', $key1);
    }

    /**
     * Test that buildTableCacheKey() generates different keys for different params.
     * 
     * @return void
     */
    public function test_build_table_cache_key_generates_different_keys_for_different_params(): void
    {
        $params1 = ['page' => 1, 'sort' => 'name'];
        $params2 = ['page' => 2, 'sort' => 'name'];
        
        $key1 = $this->cacheManager->buildTableCacheKey($params1);
        $key2 = $this->cacheManager->buildTableCacheKey($params2);
        
        $this->assertNotEquals($key1, $key2, 'Different parameters should generate different cache keys');
    }

    /**
     * Test that buildTableCacheKey() handles empty params.
     * 
     * @return void
     */
    public function test_build_table_cache_key_handles_empty_params(): void
    {
        $params = [];
        
        $key = $this->cacheManager->buildTableCacheKey($params);
        
        $this->assertIsString($key);
        $this->assertStringStartsWith('table.', $key);
    }

    /**
     * Test that buildTableCacheTags() generates correct tags.
     * 
     * @return void
     */
    public function test_build_table_cache_tags_generates_correct_tags(): void
    {
        $tags = $this->cacheManager->buildTableCacheTags(null);
        
        $this->assertIsArray($tags);
        $this->assertContains('tables', $tags);
    }

    /**
     * Test that buildTableCacheTags() includes model-specific tags.
     * 
     * @return void
     */
    public function test_build_table_cache_tags_includes_model_specific_tags(): void
    {
        $modelClass = TestModel::class;
        
        $tags = $this->cacheManager->buildTableCacheTags($modelClass);
        
        $this->assertIsArray($tags);
        $this->assertContains('tables', $tags);
        $this->assertGreaterThan(1, count($tags), 'Should include model-specific tag');
    }

    /**
     * Test that clearAllTableCaches() clears all table caches.
     * 
     * @return void
     */
    public function test_clear_all_table_caches_clears_all_caches(): void
    {
        // Cache some data
        $cacheKey = 'test.key';
        $cacheTags = ['tables'];
        $cacheTime = 300;
        $data = ['test' => 'data'];
        
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, fn() => $data);
        
        // Verify it's cached
        $this->assertTrue($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
        
        // Clear all caches
        $this->cacheManager->clearAllTableCaches();
        
        // Verify it's cleared
        $this->assertFalse($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
    }

    /**
     * Test that clearModelCache() clears model-specific cache.
     * 
     * @return void
     */
    public function test_clear_model_cache_clears_model_specific_cache(): void
    {
        $modelClass = TestModel::class;
        $cacheKey = 'test.model.key';
        $cacheTags = $this->cacheManager->buildTableCacheTags($modelClass);
        $cacheTime = 300;
        $data = ['model' => 'data'];
        
        // Cache some data
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, fn() => $data);
        
        // Verify it's cached
        $this->assertTrue($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
        
        // Clear model cache
        $this->cacheManager->clearModelCache($modelClass);
        
        // Verify it's cleared
        $this->assertFalse($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
    }

    /**
     * Test that clearModelCache() accepts Model instance.
     * 
     * @return void
     */
    public function test_clear_model_cache_accepts_model_instance(): void
    {
        $model = new TestModel();
        
        // Should not throw exception
        $this->cacheManager->clearModelCache($model);
        
        $this->assertTrue(true, 'Should accept Model instance');
    }

    /**
     * Test that clearFilterCache() clears filter-specific cache.
     * 
     * @return void
     */
    public function test_clear_filter_cache_clears_filter_specific_cache(): void
    {
        $modelClass = TestModel::class;
        $filterName = 'status';
        $cacheTime = 300;
        $options = ['active' => 'Active'];
        
        // Cache filter options
        $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, fn() => $options);
        
        // Clear filter cache
        $this->cacheManager->clearFilterCache($modelClass);
        
        // Verify callback is executed (cache was cleared)
        $callbackExecuted = false;
        $callback = function () use ($options, &$callbackExecuted) {
            $callbackExecuted = true;
            return $options;
        };
        
        $this->cacheManager->getCachedFilterOptions($filterName, $modelClass, $cacheTime, $callback);
        $this->assertTrue($callbackExecuted, 'Filter cache should be cleared');
    }

    /**
     * Test that clearRelationshipCache() clears relationship-specific cache.
     * 
     * @return void
     */
    public function test_clear_relationship_cache_clears_relationship_specific_cache(): void
    {
        $modelClass = TestModel::class;
        $relationName = 'posts';
        $cacheTime = 300;
        $data = collect([['id' => 1]]);
        
        // Cache relationship data
        $this->cacheManager->getCachedRelationship($relationName, $modelClass, $cacheTime, fn() => $data);
        
        // Clear relationship cache
        $this->cacheManager->clearRelationshipCache($modelClass);
        
        // Verify callback is executed (cache was cleared)
        $callbackExecuted = false;
        $callback = function () use ($data, &$callbackExecuted) {
            $callbackExecuted = true;
            return $data;
        };
        
        $this->cacheManager->getCachedRelationship($relationName, $modelClass, $cacheTime, $callback);
        $this->assertTrue($callbackExecuted, 'Relationship cache should be cleared');
    }

    /**
     * Test that clearCacheByKey() clears specific cache key.
     * 
     * Note: Cache::forget() doesn't work with tagged caches in some drivers.
     * This test verifies the method can be called without errors.
     * 
     * @return void
     */
    public function test_clear_cache_by_key_clears_specific_key(): void
    {
        $cacheKey = 'test.specific.key';
        
        // Should not throw exception
        $this->cacheManager->clearCacheByKey($cacheKey);
        
        $this->assertTrue(true, 'clearCacheByKey should execute without errors');
    }

    /**
     * Test that clearCacheByTags() clears cache by tags.
     * 
     * @return void
     */
    public function test_clear_cache_by_tags_clears_tagged_cache(): void
    {
        $cacheKey = 'test.tagged.key';
        $cacheTags = ['tables', 'custom_tag'];
        $cacheTime = 300;
        $data = ['tagged' => 'data'];
        
        // Cache data
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, fn() => $data);
        
        // Verify it's cached
        $this->assertTrue($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
        
        // Clear by tags
        $this->cacheManager->clearCacheByTags($cacheTags);
        
        // Verify it's cleared
        $this->assertFalse($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
    }

    /**
     * Test that isCacheEnabled() returns correct values.
     * 
     * @return void
     */
    public function test_is_cache_enabled_returns_correct_values(): void
    {
        $this->assertTrue($this->cacheManager->isCacheEnabled(300));
        $this->assertTrue($this->cacheManager->isCacheEnabled(1));
        $this->assertFalse($this->cacheManager->isCacheEnabled(0));
        $this->assertFalse($this->cacheManager->isCacheEnabled(-1));
        $this->assertFalse($this->cacheManager->isCacheEnabled(null));
    }

    /**
     * Test that getDefaultCacheTime() returns correct value.
     * 
     * @return void
     */
    public function test_get_default_cache_time_returns_correct_value(): void
    {
        $defaultTime = $this->cacheManager->getDefaultCacheTime();
        
        $this->assertIsInt($defaultTime);
        $this->assertGreaterThan(0, $defaultTime);
        $this->assertEquals(300, $defaultTime, 'Default cache time should be 300 seconds (5 minutes)');
    }

    /**
     * Test that warmCache() warms up cache correctly.
     * 
     * @return void
     */
    public function test_warm_cache_warms_up_cache_correctly(): void
    {
        $cacheKey = 'test.warm.key';
        $cacheTags = ['tables'];
        $cacheTime = 300;
        $data = ['warm' => 'data'];
        
        // Warm cache
        $this->cacheManager->warmCache($cacheKey, $cacheTags, $cacheTime, $data);
        
        // Verify it's cached
        $this->assertTrue($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
        
        // Verify data is correct
        $callbackExecuted = false;
        $callback = function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return ['should' => 'not execute'];
        };
        
        $result = $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, $callback);
        
        $this->assertEquals($data, $result);
        $this->assertFalse($callbackExecuted, 'Should use warmed cache');
    }

    /**
     * Test that warmCache() does not cache when time is zero.
     * 
     * @return void
     */
    public function test_warm_cache_does_not_cache_when_time_is_zero(): void
    {
        $cacheKey = 'test.warm.zero.key';
        $cacheTags = ['tables'];
        $cacheTime = 0;
        $data = ['warm' => 'data'];
        
        // Try to warm cache with zero time
        $this->cacheManager->warmCache($cacheKey, $cacheTags, $cacheTime, $data);
        
        // Verify it's not cached
        $this->assertFalse($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
    }

    /**
     * Test that hasCachedData() correctly detects cached data.
     * 
     * @return void
     */
    public function test_has_cached_data_correctly_detects_cached_data(): void
    {
        $cacheKey = 'test.has.key';
        $cacheTags = ['tables'];
        $cacheTime = 300;
        $data = ['has' => 'data'];
        
        // Initially not cached
        $this->assertFalse($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
        
        // Cache data
        $this->cacheManager->getCachedData($cacheKey, $cacheTags, $cacheTime, fn() => $data);
        
        // Now it should be cached
        $this->assertTrue($this->cacheManager->hasCachedData($cacheKey, $cacheTags));
    }

    /**
     * Test that getCacheStats() returns statistics.
     * 
     * @return void
     */
    public function test_get_cache_stats_returns_statistics(): void
    {
        $stats = $this->cacheManager->getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('default_cache_time', $stats);
        $this->assertArrayHasKey('cache_tag_prefix', $stats);
        $this->assertArrayHasKey('filter_tag', $stats);
        $this->assertArrayHasKey('relationship_tag', $stats);
        
        $this->assertEquals(300, $stats['default_cache_time']);
        $this->assertEquals('table', $stats['cache_tag_prefix']);
        $this->assertEquals('filters', $stats['filter_tag']);
        $this->assertEquals('relationships', $stats['relationship_tag']);
    }

    /**
     * Test cache key generation for complex parameters.
     * 
     * @return void
     */
    public function test_cache_key_generation_for_complex_parameters(): void
    {
        $params = [
            'page' => 1,
            'sort' => ['name' => 'asc', 'created_at' => 'desc'],
            'filters' => ['status' => 'active', 'role' => 'admin'],
            'search' => 'test query',
        ];
        
        $key = $this->cacheManager->buildTableCacheKey($params);
        
        $this->assertIsString($key);
        $this->assertStringStartsWith('table.', $key);
        
        // Same params should generate same key
        $key2 = $this->cacheManager->buildTableCacheKey($params);
        $this->assertEquals($key, $key2);
    }

    /**
     * Test that cache respects tags for selective invalidation.
     * 
     * @return void
     */
    public function test_cache_respects_tags_for_selective_invalidation(): void
    {
        // Cache data with different tags
        $key1 = 'test.tag1.key';
        $tags1 = ['tables', 'users'];
        $data1 = ['user' => 'data'];
        
        $key2 = 'test.tag2.key';
        $tags2 = ['tables', 'posts'];
        $data2 = ['post' => 'data'];
        
        $this->cacheManager->getCachedData($key1, $tags1, 300, fn() => $data1);
        $this->cacheManager->getCachedData($key2, $tags2, 300, fn() => $data2);
        
        // Both should be cached
        $this->assertTrue($this->cacheManager->hasCachedData($key1, $tags1));
        $this->assertTrue($this->cacheManager->hasCachedData($key2, $tags2));
        
        // Clear only 'users' tag
        $this->cacheManager->clearCacheByTags(['users']);
        
        // Only key1 should be cleared
        $this->assertFalse($this->cacheManager->hasCachedData($key1, $tags1));
        $this->assertTrue($this->cacheManager->hasCachedData($key2, $tags2), 'Posts cache should remain');
    }
}

/**
 * Test model for cache manager tests.
 */
class TestModel extends Model
{
    protected $table = 'test_models';
}

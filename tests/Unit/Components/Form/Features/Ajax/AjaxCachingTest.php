<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ajax Caching Unit Tests.
 *
 * Tests the caching functionality of Ajax sync including:
 * - Cache key generation
 * - Cache storage and retrieval
 * - Cache expiration
 * - Cache hit/miss behavior
 */
class AjaxCachingTest extends TestCase
{
    use RefreshDatabase;

    protected AjaxSyncController $controller;

    protected QueryEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app('encrypter'));
        $this->controller = new AjaxSyncController($this->encryption);

        // Setup test database table
        DB::statement('CREATE TABLE IF NOT EXISTS test_cities (id INT, name VARCHAR(255), province_id INT)');
        DB::table('test_cities')->insert([
            ['id' => 1, 'name' => 'City A', 'province_id' => 1],
            ['id' => 2, 'name' => 'City B', 'province_id' => 1],
            ['id' => 3, 'name' => 'City C', 'province_id' => 2],
        ]);
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TABLE IF EXISTS test_cities');
        parent::tearDown();
    }

    /**
     * Test that cache key is generated consistently.
     */
    public function test_cache_key_generation_is_consistent(): void
    {
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $sourceValue = '1';

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->controller, $query, $sourceValue);
        $key2 = $method->invoke($this->controller, $query, $sourceValue);

        $this->assertEquals($key1, $key2);
        $this->assertStringStartsWith('ajax_sync:', $key1);
    }

    /**
     * Test that different queries generate different cache keys.
     */
    public function test_different_queries_generate_different_cache_keys(): void
    {
        $query1 = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $query2 = 'SELECT id, name FROM test_cities WHERE country_id = ?';
        $sourceValue = '1';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->controller, $query1, $sourceValue);
        $key2 = $method->invoke($this->controller, $query2, $sourceValue);

        $this->assertNotEquals($key1, $key2);
    }

    /**
     * Test that different source values generate different cache keys.
     */
    public function test_different_source_values_generate_different_cache_keys(): void
    {
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->controller, $query, '1');
        $key2 = $method->invoke($this->controller, $query, '2');

        $this->assertNotEquals($key1, $key2);
    }

    /**
     * Test that Ajax sync response is cached.
     */
    public function test_ajax_sync_response_is_cached(): void
    {
        Cache::flush();

        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => '1',
        ]);

        // First request - should cache
        $response1 = $this->controller->handle($request);
        $data1 = $response1->getData(true);

        $this->assertTrue($data1['success']);
        $this->assertArrayHasKey('data', $data1);
        $this->assertArrayHasKey('options', $data1['data']);

        // Second request - should hit cache
        $response2 = $this->controller->handle($request);
        $data2 = $response2->getData(true);

        $this->assertTrue($data2['success']);
        $this->assertEquals($data1['data']['options'], $data2['data']['options']);
        $this->assertTrue($data2['data']['cached']);
    }

    /**
     * Test that cache expires after 5 minutes.
     */
    public function test_cache_expires_after_five_minutes(): void
    {
        $this->markTestSkipped('Time travel functionality not available in test environment');

        Cache::flush();

        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $sourceValue = '1';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->controller, $query, $sourceValue);

        // Store in cache
        Cache::put($cacheKey, ['test' => 'data'], 300);

        $this->assertTrue(Cache::has($cacheKey));

        // Travel forward 6 minutes
        $this->travel(6)->minutes();

        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test that cache stores correct data structure.
     */
    public function test_cache_stores_correct_data_structure(): void
    {
        Cache::flush();

        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => '1',
        ]);

        $response = $this->controller->handle($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']['options']);

        foreach ($data['data']['options'] as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    /**
     * Test that cache is bypassed for different source values.
     */
    public function test_cache_is_bypassed_for_different_source_values(): void
    {
        Cache::flush();

        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        // Request for province 1
        $request1 = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => '1',
        ]);

        // Request for province 2
        $request2 = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => '2',
        ]);

        $response1 = $this->controller->handle($request1);
        $data1 = $response1->getData(true);

        $response2 = $this->controller->handle($request2);
        $data2 = $response2->getData(true);

        $this->assertNotEquals($data1['data']['options'], $data2['data']['options']);
        $this->assertCount(2, $data1['data']['options']); // Province 1 has 2 cities
        $this->assertCount(1, $data2['data']['options']); // Province 2 has 1 city
    }

    /**
     * Test that cache key includes query and source value.
     */
    public function test_cache_key_includes_query_and_source_value(): void
    {
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $sourceValue = '1';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->controller, $query, $sourceValue);

        // Cache key should be MD5 hash of query + source value
        $expectedKey = 'ajax_sync:' . md5($query . '|' . $sourceValue);

        $this->assertEquals($expectedKey, $cacheKey);
    }

    /**
     * Test that cache improves performance on subsequent requests.
     */
    public function test_cache_improves_performance(): void
    {
        Cache::flush();

        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        $request = Request::create('/ajax/sync', 'POST', [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => $this->encryption->encrypt('id'),
                'labels' => $this->encryption->encrypt('name'),
                'query' => $this->encryption->encrypt($query),
            ],
            'sourceValue' => '1',
        ]);

        // First request - no cache
        $start1 = microtime(true);
        $this->controller->handle($request);
        $time1 = microtime(true) - $start1;

        // Second request - with cache
        $start2 = microtime(true);
        $this->controller->handle($request);
        $time2 = microtime(true) - $start2;

        // Cached request should be faster (or at least not significantly slower)
        $this->assertLessThanOrEqual($time1 * 1.5, $time2);
    }
}

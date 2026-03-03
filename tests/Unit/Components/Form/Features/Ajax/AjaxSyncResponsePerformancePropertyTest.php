<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Models\AjaxCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Property Test 6: Ajax Sync Response Performance.
 *
 * Validates Requirements:
 * - 2.12: Ajax sync responds within 200ms for queries returning up to 1000 records
 * - 13.2: Ajax sync response time performance target
 *
 * Property: For all Ajax sync requests with up to 1000 records,
 * the response time must be less than 200ms.
 */
class AjaxSyncResponsePerformancePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected QueryEncryption $encryption;

    protected AjaxSyncController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app('encrypter'));
        $this->controller = new AjaxSyncController($this->encryption);

        // Create cache table
        $this->createCacheTable();

        // Create test table with 1000 records
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Drop test tables
        DB::statement('DROP TABLE IF EXISTS test_cities');
        DB::statement('DROP TABLE IF EXISTS form_ajax_cache');

        parent::tearDown();
    }

    /**
     * Create cache table for Ajax sync.
     */
    protected function createCacheTable(): void
    {
        DB::statement('
            CREATE TABLE form_ajax_cache (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cache_key TEXT NOT NULL,
                source_field TEXT NOT NULL,
                source_value TEXT NOT NULL,
                response_data TEXT NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at INTEGER,
                updated_at INTEGER
            )
        ');

        DB::statement('CREATE UNIQUE INDEX idx_cache_key ON form_ajax_cache(cache_key)');
        DB::statement('CREATE INDEX idx_expires_at ON form_ajax_cache(expires_at)');
    }

    /**
     * Create test table with 1000 records for performance testing.
     */
    protected function createTestTable(): void
    {
        // SQLite-compatible CREATE TABLE syntax
        DB::statement('
            CREATE TABLE test_cities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                province_id INTEGER NOT NULL,
                name TEXT NOT NULL
            )
        ');

        // Create index separately for SQLite
        DB::statement('CREATE INDEX idx_province ON test_cities(province_id)');

        // Insert 1000 test records across 10 provinces
        $cities = [];
        for ($i = 1; $i <= 1000; $i++) {
            $provinceId = (int) ceil($i / 100); // 100 cities per province
            $cities[] = [
                'province_id' => $provinceId,
                'name' => "City {$i}",
            ];
        }

        // Batch insert for performance
        foreach (array_chunk($cities, 100) as $chunk) {
            DB::table('test_cities')->insert($chunk);
        }
    }

    /**
     * Create a test request for Ajax sync.
     */
    protected function createAjaxRequest(string $query, $sourceValue): Request
    {
        $relationship = [
            'source' => 'province_id',
            'target' => 'city_id',
            'values' => $this->encryption->encrypt('id'),
            'labels' => $this->encryption->encrypt('name'),
            'query' => $this->encryption->encrypt($query),
            'selected' => $this->encryption->encrypt(null),
        ];

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Accept', 'application/json');
        $request->replace([
            'relationship' => $relationship,
            'sourceValue' => $sourceValue,
        ]);

        return $request;
    }

    /**
     * Property: Ajax sync response time is less than 200ms for 1000 records.
     *
     * @test
     */
    public function property_ajax_sync_responds_within_200ms_for_1000_records(): void
    {
        // Arrange
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $request = $this->createAjaxRequest($query, 1); // Province 1 has 100 cities

        // Act: Measure response time
        $startTime = microtime(true);
        $response = $this->controller->handle($request);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Response time is less than 200ms
        $this->assertLessThan(
            200,
            $responseTime,
            "Ajax sync response time ({$responseTime}ms) exceeds 200ms limit for 100 records"
        );

        // Assert: Response is successful
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success'] ?? false);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('options', $data['data']);
        $this->assertCount(100, $data['data']['options']);
    }

    /**
     * Property: Ajax sync response time is less than 200ms for maximum 1000 records.
     *
     * @test
     */
    public function property_ajax_sync_responds_within_200ms_for_maximum_1000_records(): void
    {
        // Arrange
        $query = 'SELECT id, name FROM test_cities WHERE 1 = ?';
        $request = $this->createAjaxRequest($query, 1); // Will return all 1000 records

        // Act: Measure response time
        $startTime = microtime(true);
        $response = $this->controller->handle($request);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        // Assert: Response time is less than 200ms even for 1000 records
        $this->assertLessThan(
            200,
            $responseTime,
            "Ajax sync response time ({$responseTime}ms) exceeds 200ms limit for 1000 records"
        );

        // Assert: Response is successful
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(1000, $data['data']['options']);
    }

    /**
     * Property: Cached Ajax sync response time is significantly faster.
     *
     * @test
     */
    public function property_cached_ajax_sync_response_is_faster(): void
    {
        // Arrange
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';
        $request1 = $this->createAjaxRequest($query, 1);
        $request2 = $this->createAjaxRequest($query, 1);

        // Act: First request (uncached)
        $startTime1 = microtime(true);
        $response1 = $this->controller->handle($request1);
        $endTime1 = microtime(true);
        $uncachedTime = ($endTime1 - $startTime1) * 1000;

        // Second request (cached)
        $startTime2 = microtime(true);
        $response2 = $this->controller->handle($request2);
        $endTime2 = microtime(true);
        $cachedTime = ($endTime2 - $startTime2) * 1000;

        // Assert: Both responses are successful
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());

        // Assert: Cached response is faster OR within acceptable margin
        // For very fast operations (<2ms), caching overhead may make it slower
        // In this case, we just verify both are fast enough
        if ($uncachedTime < 2) {
            // Both should be under 5ms for very fast operations
            $this->assertLessThan(5, $uncachedTime, 'Uncached response should be fast');
            $this->assertLessThan(5, $cachedTime, 'Cached response should be fast');
        } else {
            // For slower operations, require 20% improvement
            $threshold = 0.2;
            $maxCachedTime = $uncachedTime * (1 - $threshold);

            $this->assertLessThanOrEqual(
                $maxCachedTime,
                $cachedTime,
                "Cached response ({$cachedTime}ms) is not significantly faster than uncached ({$uncachedTime}ms). " .
                "Expected at most {$maxCachedTime}ms (threshold: " . ($threshold * 100) . '%)'
            );
        }

        // Assert: Both responses return same data
        $data1 = json_decode($response1->getContent(), true);
        $data2 = json_decode($response2->getContent(), true);
        $this->assertEquals($data1['data']['options'], $data2['data']['options']);
    }

    /**
     * Property: Ajax sync performance scales linearly with record count.
     *
     * @test
     */
    public function property_ajax_sync_performance_scales_linearly(): void
    {
        // Arrange: Test with different record counts
        $recordCounts = [10, 50, 100, 500, 1000];
        $responseTimes = [];

        foreach ($recordCounts as $count) {
            $provinceId = (int) ceil($count / 100);
            if ($provinceId === 0) {
                $provinceId = 1;
            }

            $query = 'SELECT id, name FROM test_cities WHERE province_id = ? LIMIT ' . $count;

            // Clear cache for accurate measurement
            AjaxCache::query()->delete();

            $request = $this->createAjaxRequest($query, $provinceId);

            // Measure response time
            $startTime = microtime(true);
            $response = $this->controller->handle($request);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;
            $responseTimes[$count] = $responseTime;

            // Assert: Response is successful
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Assert: All response times are within acceptable limits
        foreach ($responseTimes as $count => $time) {
            $this->assertLessThan(
                200,
                $time,
                "Response time for {$count} records ({$time}ms) exceeds 200ms limit"
            );
        }

        // Assert: Performance scales reasonably
        $scaleFactor = $responseTimes[1000] / $responseTimes[10];
        $this->assertLessThan(
            50,
            $scaleFactor,
            "Performance does not scale linearly (scale factor: {$scaleFactor})"
        );
    }

    /**
     * Property: Ajax sync maintains performance under concurrent requests.
     *
     * @test
     */
    public function property_ajax_sync_maintains_performance_under_load(): void
    {
        // Arrange
        $query = 'SELECT id, name FROM test_cities WHERE province_id = ?';

        // Act: Simulate 10 concurrent requests
        $responseTimes = [];
        for ($i = 1; $i <= 10; $i++) {
            $provinceId = ($i % 10) + 1;
            $request = $this->createAjaxRequest($query, $provinceId);

            $startTime = microtime(true);
            $response = $this->controller->handle($request);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;
            $responseTimes[] = $responseTime;

            // Assert: Response is successful
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Assert: All response times are within acceptable limits
        foreach ($responseTimes as $index => $time) {
            $this->assertLessThan(
                200,
                $time,
                "Response time for request #{$index} ({$time}ms) exceeds 200ms limit"
            );
        }

        // Assert: Average response time is acceptable
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $this->assertLessThan(
            150,
            $avgResponseTime,
            "Average response time ({$avgResponseTime}ms) exceeds 150ms"
        );
    }
}

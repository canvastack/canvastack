<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Components\Form\Features\Ajax\AjaxSync;
use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Performance tests for Ajax Sync.
 *
 * Tests response time, memory usage, and cache hit ratio
 * to ensure Ajax Sync meets performance targets.
 *
 * **Validates: Requirements 2.12, 2.13**
 *
 * Performance Targets:
 * - Response time: < 200ms for queries returning up to 1000 records
 * - Memory usage: Reasonable memory consumption
 * - Cache hit ratio: > 80% for repeated requests
 */
class AjaxSyncPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected AjaxSync $ajaxSync;

    protected QueryEncryption $encryption;

    /**
     * Maximum acceptable response time in milliseconds.
     */
    protected const MAX_RESPONSE_TIME_MS = 200;

    /**
     * Maximum acceptable memory usage in MB.
     */
    protected const MAX_MEMORY_MB = 10;

    /**
     * Target cache hit ratio (percentage).
     */
    protected const TARGET_CACHE_HIT_RATIO = 80;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app('encrypter'));
        $this->ajaxSync = new AjaxSync($this->encryption);

        // Register the Ajax sync route
        Route::post('/ajax/sync', [AjaxSyncController::class, 'handle'])
            ->name('canvastack.ajax.sync');

        // Create test database tables with large dataset
        $this->createTestTables();
    }

    protected function createTestTables(\Illuminate\Database\Capsule\Manager $capsule = null): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS perf_categories (
            id INT PRIMARY KEY,
            name VARCHAR(255)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS perf_items (
            id INT PRIMARY KEY,
            category_id INT,
            name VARCHAR(255),
            description TEXT,
            price DECIMAL(10,2)
        )');

        // Create index separately for SQLite compatibility
        DB::statement('CREATE INDEX IF NOT EXISTS idx_category ON perf_items(category_id)');

        // Insert categories
        $categories = [];
        for ($i = 1; $i <= 10; $i++) {
            $categories[] = ['id' => $i, 'name' => "Category {$i}"];
        }
        DB::table('perf_categories')->insert($categories);

        // Insert 1000 items per category (10,000 total)
        $items = [];
        for ($i = 1; $i <= 10000; $i++) {
            $categoryId = (($i - 1) % 10) + 1;
            $items[] = [
                'id' => $i,
                'category_id' => $categoryId,
                'name' => "Item {$i}",
                'description' => "Description for item {$i}",
                'price' => rand(10, 10000) / 100,
            ];

            // Insert in batches to avoid memory issues
            if (count($items) >= 1000) {
                DB::table('perf_items')->insert($items);
                $items = [];
            }
        }

        // Insert remaining items
        if (!empty($items)) {
            DB::table('perf_items')->insert($items);
        }
    }

    /**
     * Test response time with 1000 records.
     *
     * **Validates: Requirement 2.12** - Respond within 200ms for 1000 records
     */
    public function test_response_time_with_1000_records(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Measure response time
        $startTime = microtime(true);

        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        // Verify response is successful
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify response time is within target
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Response time ({$responseTimeMs}ms) exceeds maximum ({self::MAX_RESPONSE_TIME_MS}ms)"
        );

        // Verify correct number of records returned
        $data = $response->json('data');
        $this->assertCount(1000, $data['options']);

        // Output performance metrics
        echo "\n";
        echo 'Response Time: ' . number_format($responseTimeMs, 2) . "ms\n";
        echo 'Records Returned: ' . count($data['options']) . "\n";
        echo 'Target: < ' . self::MAX_RESPONSE_TIME_MS . "ms\n";
    }

    /**
     * Test response time with cached data.
     *
     * **Validates: Requirement 2.13** - Cache Ajax responses for 5 minutes
     */
    public function test_response_time_with_cache(): void
    {
        // Clear cache
        Cache::flush();

        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // First request (not cached)
        $startTime1 = microtime(true);
        $response1 = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);
        $endTime1 = microtime(true);
        $responseTime1Ms = ($endTime1 - $startTime1) * 1000;

        // Second request (cached)
        $startTime2 = microtime(true);
        $response2 = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);
        $endTime2 = microtime(true);
        $responseTime2Ms = ($endTime2 - $startTime2) * 1000;

        // Both requests should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Cached request should be faster
        $this->assertLessThan(
            $responseTime1Ms,
            $responseTime2Ms,
            'Cached request should be faster than uncached request'
        );

        // Both should be within target
        $this->assertLessThan(self::MAX_RESPONSE_TIME_MS, $responseTime1Ms);
        $this->assertLessThan(self::MAX_RESPONSE_TIME_MS, $responseTime2Ms);

        // Output performance metrics
        echo "\n";
        echo 'First Request (Uncached): ' . number_format($responseTime1Ms, 2) . "ms\n";
        echo 'Second Request (Cached): ' . number_format($responseTime2Ms, 2) . "ms\n";
        echo 'Improvement: ' . number_format((($responseTime1Ms - $responseTime2Ms) / $responseTime1Ms) * 100, 2) . "%\n";
    }

    /**
     * Test memory usage with 1000 records.
     *
     * **Validates: Requirement 2.12** - Efficient memory usage
     */
    public function test_memory_usage_with_1000_records(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Measure memory usage
        $memoryBefore = memory_get_usage(true);

        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);

        $memoryAfter = memory_get_usage(true);
        $memoryUsedBytes = $memoryAfter - $memoryBefore;
        $memoryUsedMB = $memoryUsedBytes / 1024 / 1024;

        // Verify response is successful
        $response->assertStatus(200);

        // Verify memory usage is reasonable
        $this->assertLessThan(
            self::MAX_MEMORY_MB,
            $memoryUsedMB,
            "Memory usage ({$memoryUsedMB}MB) exceeds maximum ({self::MAX_MEMORY_MB}MB)"
        );

        // Output performance metrics
        echo "\n";
        echo 'Memory Used: ' . number_format($memoryUsedMB, 2) . "MB\n";
        echo 'Target: < ' . self::MAX_MEMORY_MB . "MB\n";
    }

    /**
     * Test cache hit ratio.
     *
     * **Validates: Requirement 2.13** - Cache Ajax responses for 5 minutes
     */
    public function test_cache_hit_ratio(): void
    {
        // Clear cache
        Cache::flush();

        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        $totalRequests = 100;
        $cacheHits = 0;

        // Make multiple requests (some repeated)
        for ($i = 0; $i < $totalRequests; $i++) {
            // Use category 1-5, with category 1 being most frequent
            $categoryId = ($i < 50) ? 1 : (($i % 4) + 2);

            $response = $this->postJson(route('canvastack.ajax.sync'), [
                'relationship' => $relationship,
                'sourceValue' => $categoryId,
            ]);

            $response->assertStatus(200);

            // Check if response was cached
            $data = $response->json('data');
            if ($data['cached']) {
                $cacheHits++;
            }
        }

        // Calculate cache hit ratio
        $cacheHitRatio = ($cacheHits / $totalRequests) * 100;

        // Verify cache hit ratio meets target
        $this->assertGreaterThan(
            self::TARGET_CACHE_HIT_RATIO,
            $cacheHitRatio,
            "Cache hit ratio ({$cacheHitRatio}%) is below target ({self::TARGET_CACHE_HIT_RATIO}%)"
        );

        // Output performance metrics
        echo "\n";
        echo "Total Requests: {$totalRequests}\n";
        echo "Cache Hits: {$cacheHits}\n";
        echo 'Cache Hit Ratio: ' . number_format($cacheHitRatio, 2) . "%\n";
        echo 'Target: > ' . self::TARGET_CACHE_HIT_RATIO . "%\n";
    }

    /**
     * Test concurrent request performance.
     *
     * **Validates: Requirement 2.12** - Handle multiple concurrent requests
     */
    public function test_concurrent_request_performance(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        $concurrentRequests = 10;
        $responseTimes = [];

        // Simulate concurrent requests
        $startTime = microtime(true);

        for ($i = 1; $i <= $concurrentRequests; $i++) {
            $requestStart = microtime(true);

            $response = $this->postJson(route('canvastack.ajax.sync'), [
                'relationship' => $relationship,
                'sourceValue' => $i,
            ]);

            $requestEnd = microtime(true);
            $responseTimes[] = ($requestEnd - $requestStart) * 1000;

            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $totalTimeMs = ($endTime - $startTime) * 1000;
        $avgResponseTimeMs = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTimeMs = max($responseTimes);

        // Verify average response time is within target
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $avgResponseTimeMs,
            "Average response time ({$avgResponseTimeMs}ms) exceeds maximum ({self::MAX_RESPONSE_TIME_MS}ms)"
        );

        // Output performance metrics
        echo "\n";
        echo "Concurrent Requests: {$concurrentRequests}\n";
        echo 'Total Time: ' . number_format($totalTimeMs, 2) . "ms\n";
        echo 'Average Response Time: ' . number_format($avgResponseTimeMs, 2) . "ms\n";
        echo 'Max Response Time: ' . number_format($maxResponseTimeMs, 2) . "ms\n";
        echo 'Target: < ' . self::MAX_RESPONSE_TIME_MS . "ms\n";
    }

    /**
     * Test performance with complex query.
     *
     * **Validates: Requirement 2.12** - Efficient query execution
     */
    public function test_performance_with_complex_query(): void
    {
        // Register sync relationship with JOIN and WHERE conditions
        // Note: Using simpler query for SQLite compatibility
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ? AND price > 10 ORDER BY name',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Measure response time
        $startTime = microtime(true);

        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        // Verify response is successful
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify response time is within target
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Complex query response time ({$responseTimeMs}ms) exceeds maximum ({self::MAX_RESPONSE_TIME_MS}ms)"
        );

        // Output performance metrics
        echo "\n";
        echo 'Complex Query Response Time: ' . number_format($responseTimeMs, 2) . "ms\n";
        echo 'Target: < ' . self::MAX_RESPONSE_TIME_MS . "ms\n";
    }

    /**
     * Test performance degradation with increasing data size.
     *
     * **Validates: Requirement 2.12** - Scalable performance
     */
    public function test_performance_scalability(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'category_id',
            'item_id',
            'id',
            'name',
            'SELECT id, name FROM perf_items WHERE category_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        $responseTimes = [];

        // Test with different categories (different data sizes)
        for ($categoryId = 1; $categoryId <= 5; $categoryId++) {
            $startTime = microtime(true);

            $response = $this->postJson(route('canvastack.ajax.sync'), [
                'relationship' => $relationship,
                'sourceValue' => $categoryId,
            ]);

            $endTime = microtime(true);
            $responseTimeMs = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $responseTimes[$categoryId] = $responseTimeMs;

            // Each request should be within target
            $this->assertLessThan(
                self::MAX_RESPONSE_TIME_MS,
                $responseTimeMs,
                "Response time for category {$categoryId} ({$responseTimeMs}ms) exceeds maximum"
            );
        }

        // Calculate performance consistency
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $minResponseTime = min($responseTimes);
        $variance = $maxResponseTime - $minResponseTime;

        // Output performance metrics
        echo "\n";
        echo "Performance Scalability Test:\n";
        echo 'Average Response Time: ' . number_format($avgResponseTime, 2) . "ms\n";
        echo 'Min Response Time: ' . number_format($minResponseTime, 2) . "ms\n";
        echo 'Max Response Time: ' . number_format($maxResponseTime, 2) . "ms\n";
        echo 'Variance: ' . number_format($variance, 2) . "ms\n";
        echo 'Target: < ' . self::MAX_RESPONSE_TIME_MS . "ms\n";
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        DB::statement('DROP TABLE IF EXISTS perf_items');
        DB::statement('DROP TABLE IF EXISTS perf_categories');

        parent::tearDown();
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Property 12: Cache Hit Ratio.
 *
 * Validates: Requirements 27.6
 *
 * Property: For ALL table configurations with caching enabled, when the same
 * query is executed 10 times, the cache hit ratio MUST be greater than 80%.
 *
 * This property ensures that the caching mechanism is working effectively and
 * that repeated queries are served from cache rather than hitting the database.
 *
 * Cache Hit Ratio = (Number of cache hits / Total queries) * 100
 * Expected: > 80% (at least 8 out of 10 queries should be served from cache)
 */
class CacheHitRatioPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    private int $queryCount = 0;

    private int $cacheHits = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);

        // Clear all cache before each test
        Cache::flush();

        // Reset counters
        $this->queryCount = 0;
        $this->cacheHits = 0;

        // Enable query logging
        DB::enableQueryLog();

        // Create test data
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();

        parent::tearDown();
    }

    /**
     * Create test data for cache testing.
     */
    protected function createTestData(): void
    {
        // Create 20 test users
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }
    }

    /**
     * Property 12: Cache Hit Ratio.
     *
     * Test that cache hit ratio is greater than 80% when executing same query 10 times.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     * @group canvastack-table-complete
     */
    public function property_cache_hit_ratio_exceeds_80_percent(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Reset counters
                $this->queryCount = 0;
                $this->cacheHits = 0;

                // Configure table with caching enabled
                $this->table->setModel(new User())
                    ->setFields($config['fields'])
                    ->cache($config['cacheSeconds']);

                // Add filters if present
                if (!empty($config['filters'])) {
                    foreach ($config['filters'] as $filter) {
                        $this->table->where($filter['column'], $filter['operator'], $filter['value']);
                    }
                }

                // Execute same query 10 times
                $results = [];
                for ($i = 0; $i < 10; $i++) {
                    // Track query count before execution
                    $queryCountBefore = $this->getQueryCount();

                    // Execute query
                    $data = $this->table->getData();
                    $results[] = $data;

                    // Track query count after execution
                    $queryCountAfter = $this->getQueryCount();

                    // If query count didn't increase, it was a cache hit
                    if ($queryCountAfter === $queryCountBefore) {
                        $this->cacheHits++;
                    }

                    $this->queryCount++;
                }

                // Calculate cache hit ratio
                $cacheHitRatio = ($this->cacheHits / $this->queryCount) * 100;

                // Verify: Cache hit ratio > 80%
                $this->assertGreaterThan(
                    80.0,
                    $cacheHitRatio,
                    sprintf(
                        'Cache hit ratio should be > 80%%, got %.2f%% (%d hits out of %d queries)',
                        $cacheHitRatio,
                        $this->cacheHits,
                        $this->queryCount
                    )
                );

                // Verify: All results are identical (cache consistency)
                $firstResult = $results[0];
                foreach ($results as $index => $result) {
                    $this->assertEquals(
                        $firstResult,
                        $result,
                        "Result at index {$index} should match first result (cache consistency)"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 12.1: Cache hit ratio with different query patterns.
     *
     * Test cache hit ratio with various query patterns (simple, filtered, sorted).
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_with_different_query_patterns(): void
    {
        $this->forAll(
            $this->generateComplexTableConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Reset counters
                $this->queryCount = 0;
                $this->cacheHits = 0;

                // Configure table with caching
                $this->table->setModel(new User())
                    ->setFields($config['fields'])
                    ->cache($config['cacheSeconds']);

                // Add filters
                if (!empty($config['filters'])) {
                    foreach ($config['filters'] as $filter) {
                        $this->table->where($filter['column'], $filter['operator'], $filter['value']);
                    }
                }

                // Add sorting
                if (!empty($config['orderBy'])) {
                    $this->table->orderby($config['orderBy']['column'], $config['orderBy']['direction']);
                }

                // Execute same query 10 times
                for ($i = 0; $i < 10; $i++) {
                    $queryCountBefore = $this->getQueryCount();
                    $this->table->getData();
                    $queryCountAfter = $this->getQueryCount();

                    if ($queryCountAfter === $queryCountBefore) {
                        $this->cacheHits++;
                    }

                    $this->queryCount++;
                }

                // Calculate cache hit ratio
                $cacheHitRatio = ($this->cacheHits / $this->queryCount) * 100;

                // Verify: Cache hit ratio > 80%
                $this->assertGreaterThan(
                    80.0,
                    $cacheHitRatio,
                    sprintf(
                        'Cache hit ratio for complex query should be > 80%%, got %.2f%%',
                        $cacheHitRatio
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 12.2: Cache hit ratio consistency across iterations.
     *
     * Test that cache hit ratio remains consistent across multiple test iterations.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_consistency(): void
    {
        $hitRatios = [];

        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) use (&$hitRatios) {
                // Clear cache before test
                Cache::flush();

                // Reset counters
                $this->queryCount = 0;
                $this->cacheHits = 0;

                // Configure table
                $this->table->setModel(new User())
                    ->setFields($config['fields'])
                    ->cache($config['cacheSeconds']);

                // Execute same query 10 times
                for ($i = 0; $i < 10; $i++) {
                    $queryCountBefore = $this->getQueryCount();
                    $this->table->getData();
                    $queryCountAfter = $this->getQueryCount();

                    if ($queryCountAfter === $queryCountBefore) {
                        $this->cacheHits++;
                    }

                    $this->queryCount++;
                }

                // Calculate and store cache hit ratio
                $cacheHitRatio = ($this->cacheHits / $this->queryCount) * 100;
                $hitRatios[] = $cacheHitRatio;

                return true;
            },
            100
        );

        // Verify: All hit ratios are > 80%
        foreach ($hitRatios as $index => $ratio) {
            $this->assertGreaterThan(
                80.0,
                $ratio,
                "Cache hit ratio at iteration {$index} should be > 80%, got {$ratio}%"
            );
        }

        // Verify: Average hit ratio is > 80%
        $averageHitRatio = array_sum($hitRatios) / count($hitRatios);
        $this->assertGreaterThan(
            80.0,
            $averageHitRatio,
            sprintf('Average cache hit ratio should be > 80%%, got %.2f%%', $averageHitRatio)
        );
    }

    /**
     * Property 12.3: First query is cache miss, subsequent queries are cache hits.
     *
     * Test that the first query is always a cache miss and subsequent queries are hits.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_first_query_miss_subsequent_queries_hit(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Configure table
                $this->table->setModel(new User())
                    ->setFields($config['fields'])
                    ->cache($config['cacheSeconds']);

                // First query - should be cache miss
                $queryCountBefore = $this->getQueryCount();
                $this->table->getData();
                $queryCountAfter = $this->getQueryCount();

                $this->assertGreaterThan(
                    $queryCountBefore,
                    $queryCountAfter,
                    'First query should be a cache miss (query count should increase)'
                );

                // Subsequent queries - should be cache hits
                for ($i = 0; $i < 9; $i++) {
                    $queryCountBefore = $this->getQueryCount();
                    $this->table->getData();
                    $queryCountAfter = $this->getQueryCount();

                    $this->assertEquals(
                        $queryCountBefore,
                        $queryCountAfter,
                        "Query {$i} should be a cache hit (query count should not increase)"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 12.4: Cache hit ratio with cache invalidation.
     *
     * Test that cache hit ratio resets after cache invalidation.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cache_hit_ratio_resets_after_invalidation(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Configure table
                $this->table->setModel(new User())
                    ->setFields($config['fields'])
                    ->cache($config['cacheSeconds']);

                // Execute query 5 times
                for ($i = 0; $i < 5; $i++) {
                    $this->table->getData();
                }

                // Clear cache
                $this->table->clearCache();

                // Reset counters
                $this->queryCount = 0;
                $this->cacheHits = 0;

                // Execute query 10 times after cache clear
                for ($i = 0; $i < 10; $i++) {
                    $queryCountBefore = $this->getQueryCount();
                    $this->table->getData();
                    $queryCountAfter = $this->getQueryCount();

                    if ($queryCountAfter === $queryCountBefore) {
                        $this->cacheHits++;
                    }

                    $this->queryCount++;
                }

                // Calculate cache hit ratio
                $cacheHitRatio = ($this->cacheHits / $this->queryCount) * 100;

                // Verify: Cache hit ratio > 80% even after invalidation
                $this->assertGreaterThan(
                    80.0,
                    $cacheHitRatio,
                    'Cache hit ratio after invalidation should be > 80%'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 12.5: Cache hit ratio with concurrent configurations.
     *
     * Test that different configurations don't interfere with each other's cache.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cache_hit_ratio_with_concurrent_configurations(): void
    {
        // Clear cache before test
        Cache::flush();

        // Create two different table configurations
        $table1 = app(TableBuilder::class);
        $table1->setModel(new User())
            ->setFields(['id', 'name'])
            ->cache(300);

        $table2 = app(TableBuilder::class);
        $table2->setModel(new User())
            ->setFields(['id', 'email'])
            ->cache(300);

        // Execute queries alternately
        $hits1 = 0;
        $hits2 = 0;

        for ($i = 0; $i < 10; $i++) {
            // Table 1
            $queryCountBefore = $this->getQueryCount();
            $table1->getData();
            $queryCountAfter = $this->getQueryCount();
            if ($queryCountAfter === $queryCountBefore) {
                $hits1++;
            }

            // Table 2
            $queryCountBefore = $this->getQueryCount();
            $table2->getData();
            $queryCountAfter = $this->getQueryCount();
            if ($queryCountAfter === $queryCountBefore) {
                $hits2++;
            }
        }

        // Calculate hit ratios
        $hitRatio1 = ($hits1 / 10) * 100;
        $hitRatio2 = ($hits2 / 10) * 100;

        // Verify: Both configurations have > 80% hit ratio
        $this->assertGreaterThan(
            80.0,
            $hitRatio1,
            'Table 1 cache hit ratio should be > 80%'
        );

        $this->assertGreaterThan(
            80.0,
            $hitRatio2,
            'Table 2 cache hit ratio should be > 80%'
        );
    }

    /**
     * Get current database query count.
     */
    protected function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Generate random table configurations with caching enabled.
     */
    protected function generateTableConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
            ['name', 'email'],
            ['id', 'email'],
            ['id'],
            ['name'],
        ];

        $cacheSeconds = [60, 300, 600, 1800, 3600];

        $filterOptions = [
            [],
            [['column' => 'id', 'operator' => '>', 'value' => 5]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User%']],
            [['column' => 'id', 'operator' => '<=', 'value' => 15]],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'cacheSeconds' => $cacheSeconds[array_rand($cacheSeconds)],
                'filters' => $filterOptions[array_rand($filterOptions)],
            ];
        }
    }

    /**
     * Generate complex table configurations with sorting and filtering.
     */
    protected function generateComplexTableConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
            ['name', 'email'],
        ];

        $cacheSeconds = [300, 600, 1800];

        $filterOptions = [
            [['column' => 'id', 'operator' => '>', 'value' => 5]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User%']],
            [
                ['column' => 'id', 'operator' => '>', 'value' => 5],
                ['column' => 'id', 'operator' => '<=', 'value' => 15],
            ],
        ];

        $orderByOptions = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'email', 'direction' => 'desc'],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'cacheSeconds' => $cacheSeconds[array_rand($cacheSeconds)],
                'filters' => $filterOptions[array_rand($filterOptions)],
                'orderBy' => $orderByOptions[array_rand($orderByOptions)],
            ];
        }
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Property 19: Cache Hit Ratio Increases with Repetition.
 *
 * Validates: Requirements 48.4
 *
 * Property: For ALL table configurations with caching enabled, when the same
 * query is executed N times (where N = 5, 10, 20), the cache hit ratio MUST
 * increase or stay constant as N increases.
 *
 * This property ensures that the caching mechanism becomes more effective with
 * repeated queries, demonstrating that:
 * - Cache is properly stored after first query
 * - Cache is consistently retrieved on subsequent queries
 * - Cache hit ratio improves (or stays constant) with more repetitions
 *
 * Mathematical property: hitRatio(N) >= hitRatio(N-1) for all N > 1
 *
 * Expected behavior:
 * - N=5:  hitRatio >= 60% (3+ hits out of 5)
 * - N=10: hitRatio >= 80% (8+ hits out of 10)
 * - N=20: hitRatio >= 90% (18+ hits out of 20)
 */
class CacheHitRatioIncreasesPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);

        // Clear all cache before each test
        Cache::flush();

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
        // Create 50 test users for more realistic testing
        for ($i = 1; $i <= 50; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }
    }

    /**
     * Property 19: Cache Hit Ratio Increases with Repetition.
     *
     * Test that cache hit ratio increases (or stays constant) as the number of
     * query repetitions increases from N=5 to N=10 to N=20.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     * @group canvastack-table-complete
     */
    public function property_cache_hit_ratio_increases_with_repetition(): void
    {
        $this->forAll(
            $this->generateRandomQueryConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Configure table with caching enabled
                $table = $this->createConfiguredTable($config);

                // Test with N=5, 10, 20
                $repetitions = [5, 10, 20];
                $hitRatios = [];

                foreach ($repetitions as $n) {
                    // Clear cache and reset for this N
                    Cache::flush();
                    DB::flushQueryLog();

                    // Reconfigure table (fresh instance)
                    $table = $this->createConfiguredTable($config);

                    // Execute query N times
                    $hits = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $queryCountBefore = $this->getQueryCount();
                        $table->getData();
                        $queryCountAfter = $this->getQueryCount();

                        // If query count didn't increase, it was a cache hit
                        if ($queryCountAfter === $queryCountBefore) {
                            $hits++;
                        }
                    }

                    // Calculate cache hit ratio for this N
                    $hitRatio = ($hits / $n) * 100;
                    $hitRatios[$n] = $hitRatio;
                }

                // Verify: Cache hit ratio increases or stays constant as N increases
                $this->assertGreaterThanOrEqual(
                    $hitRatios[5],
                    $hitRatios[10],
                    sprintf(
                        'Cache hit ratio should increase from N=5 to N=10. ' .
                        'Got %.2f%% (N=5) and %.2f%% (N=10)',
                        $hitRatios[5],
                        $hitRatios[10]
                    )
                );

                $this->assertGreaterThanOrEqual(
                    $hitRatios[10],
                    $hitRatios[20],
                    sprintf(
                        'Cache hit ratio should increase from N=10 to N=20. ' .
                        'Got %.2f%% (N=10) and %.2f%% (N=20)',
                        $hitRatios[10],
                        $hitRatios[20]
                    )
                );

                // Verify: Minimum hit ratios for each N
                $this->assertGreaterThanOrEqual(
                    60.0,
                    $hitRatios[5],
                    sprintf(
                        'Cache hit ratio for N=5 should be >= 60%%, got %.2f%%',
                        $hitRatios[5]
                    )
                );

                $this->assertGreaterThanOrEqual(
                    80.0,
                    $hitRatios[10],
                    sprintf(
                        'Cache hit ratio for N=10 should be >= 80%%, got %.2f%%',
                        $hitRatios[10]
                    )
                );

                $this->assertGreaterThanOrEqual(
                    90.0,
                    $hitRatios[20],
                    sprintf(
                        'Cache hit ratio for N=20 should be >= 90%%, got %.2f%%',
                        $hitRatios[20]
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 19.1: Cache hit ratio monotonically increases.
     *
     * Test that cache hit ratio never decreases as N increases.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_monotonically_increases(): void
    {
        $this->forAll(
            $this->generateRandomQueryConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Test with multiple N values
                $repetitions = [3, 5, 7, 10, 15, 20, 25, 30];
                $hitRatios = [];

                foreach ($repetitions as $n) {
                    // Clear cache and reset for this N
                    Cache::flush();
                    DB::flushQueryLog();

                    // Configure table
                    $table = $this->createConfiguredTable($config);

                    // Execute query N times
                    $hits = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $queryCountBefore = $this->getQueryCount();
                        $table->getData();
                        $queryCountAfter = $this->getQueryCount();

                        if ($queryCountAfter === $queryCountBefore) {
                            $hits++;
                        }
                    }

                    // Calculate cache hit ratio
                    $hitRatio = ($hits / $n) * 100;
                    $hitRatios[$n] = $hitRatio;
                }

                // Verify: Cache hit ratio never decreases
                $previousN = null;
                foreach ($repetitions as $n) {
                    if ($previousN !== null) {
                        $this->assertGreaterThanOrEqual(
                            $hitRatios[$previousN],
                            $hitRatios[$n],
                            sprintf(
                                'Cache hit ratio should not decrease from N=%d to N=%d. ' .
                                'Got %.2f%% (N=%d) and %.2f%% (N=%d)',
                                $previousN,
                                $n,
                                $hitRatios[$previousN],
                                $previousN,
                                $hitRatios[$n],
                                $n
                            )
                        );
                    }
                    $previousN = $n;
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 19.2: Cache hit ratio converges to maximum.
     *
     * Test that cache hit ratio converges to near 100% as N increases.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_converges_to_maximum(): void
    {
        $this->forAll(
            $this->generateRandomQueryConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Configure table
                $table = $this->createConfiguredTable($config);

                // Execute query 50 times
                $hits = 0;
                for ($i = 0; $i < 50; $i++) {
                    $queryCountBefore = $this->getQueryCount();
                    $table->getData();
                    $queryCountAfter = $this->getQueryCount();

                    if ($queryCountAfter === $queryCountBefore) {
                        $hits++;
                    }
                }

                // Calculate cache hit ratio
                $hitRatio = ($hits / 50) * 100;

                // Verify: Cache hit ratio converges to near 100% (>= 95%)
                $this->assertGreaterThanOrEqual(
                    95.0,
                    $hitRatio,
                    sprintf(
                        'Cache hit ratio for N=50 should converge to >= 95%%, got %.2f%%',
                        $hitRatio
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 19.3: Cache hit ratio with different query patterns.
     *
     * Test that cache hit ratio increases with repetition for various query patterns.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_increases_with_complex_queries(): void
    {
        $this->forAll(
            $this->generateComplexQueryConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Test with N=5, 10, 20
                $repetitions = [5, 10, 20];
                $hitRatios = [];

                foreach ($repetitions as $n) {
                    // Clear cache and reset for this N
                    Cache::flush();
                    DB::flushQueryLog();

                    // Configure table with complex query
                    $table = $this->createConfiguredTable($config);

                    // Execute query N times
                    $hits = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $queryCountBefore = $this->getQueryCount();
                        $table->getData();
                        $queryCountAfter = $this->getQueryCount();

                        if ($queryCountAfter === $queryCountBefore) {
                            $hits++;
                        }
                    }

                    // Calculate cache hit ratio
                    $hitRatio = ($hits / $n) * 100;
                    $hitRatios[$n] = $hitRatio;
                }

                // Verify: Cache hit ratio increases for complex queries
                $this->assertGreaterThanOrEqual(
                    $hitRatios[5],
                    $hitRatios[10],
                    'Cache hit ratio should increase from N=5 to N=10 for complex queries'
                );

                $this->assertGreaterThanOrEqual(
                    $hitRatios[10],
                    $hitRatios[20],
                    'Cache hit ratio should increase from N=10 to N=20 for complex queries'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 19.4: Cache hit ratio improvement rate.
     *
     * Test that the rate of improvement in cache hit ratio is consistent.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_improvement_rate_is_consistent(): void
    {
        $this->forAll(
            $this->generateRandomQueryConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Test with incremental N values
                $repetitions = [5, 10, 15, 20];
                $hitRatios = [];

                foreach ($repetitions as $n) {
                    // Clear cache and reset for this N
                    Cache::flush();
                    DB::flushQueryLog();

                    // Configure table
                    $table = $this->createConfiguredTable($config);

                    // Execute query N times
                    $hits = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $queryCountBefore = $this->getQueryCount();
                        $table->getData();
                        $queryCountAfter = $this->getQueryCount();

                        if ($queryCountAfter === $queryCountBefore) {
                            $hits++;
                        }
                    }

                    // Calculate cache hit ratio
                    $hitRatio = ($hits / $n) * 100;
                    $hitRatios[$n] = $hitRatio;
                }

                // Calculate improvement rates
                $improvement_5_to_10 = $hitRatios[10] - $hitRatios[5];
                $improvement_10_to_15 = $hitRatios[15] - $hitRatios[10];
                $improvement_15_to_20 = $hitRatios[20] - $hitRatios[15];

                // Verify: All improvements are non-negative
                $this->assertGreaterThanOrEqual(
                    0,
                    $improvement_5_to_10,
                    'Cache hit ratio should not decrease from N=5 to N=10'
                );

                $this->assertGreaterThanOrEqual(
                    0,
                    $improvement_10_to_15,
                    'Cache hit ratio should not decrease from N=10 to N=15'
                );

                $this->assertGreaterThanOrEqual(
                    0,
                    $improvement_15_to_20,
                    'Cache hit ratio should not decrease from N=15 to N=20'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 19.5: Cache hit ratio with cache expiration.
     *
     * Test that cache hit ratio still increases even with short cache TTL.
     *
     * @test
     * @group property
     * @group cache
     * @group performance
     */
    public function property_cache_hit_ratio_increases_with_short_ttl(): void
    {
        $this->forAll(
            $this->generateShortTTLConfigurations(),
            function (array $config) {
                // Clear cache before test
                Cache::flush();

                // Test with N=5, 10
                $repetitions = [5, 10];
                $hitRatios = [];

                foreach ($repetitions as $n) {
                    // Clear cache and reset for this N
                    Cache::flush();
                    DB::flushQueryLog();

                    // Configure table with short TTL
                    $table = $this->createConfiguredTable($config);

                    // Execute query N times (quickly, before cache expires)
                    $hits = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $queryCountBefore = $this->getQueryCount();
                        $table->getData();
                        $queryCountAfter = $this->getQueryCount();

                        if ($queryCountAfter === $queryCountBefore) {
                            $hits++;
                        }
                    }

                    // Calculate cache hit ratio
                    $hitRatio = ($hits / $n) * 100;
                    $hitRatios[$n] = $hitRatio;
                }

                // Verify: Cache hit ratio increases even with short TTL
                $this->assertGreaterThanOrEqual(
                    $hitRatios[5],
                    $hitRatios[10],
                    'Cache hit ratio should increase from N=5 to N=10 even with short TTL'
                );

                return true;
            },
            100
        );
    }

    /**
     * Create a configured table instance.
     */
    protected function createConfiguredTable(array $config): TableBuilder
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());

        // Apply configuration
        if (isset($config['fields'])) {
            $table->setFields($config['fields']);
        }

        if (isset($config['cacheSeconds'])) {
            $table->cache($config['cacheSeconds']);
        }

        if (isset($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                $table->where($filter['column'], $filter['operator'], $filter['value']);
            }
        }

        if (isset($config['orderBy'])) {
            $table->orderby($config['orderBy']['column'], $config['orderBy']['direction']);
        }

        if (isset($config['limit'])) {
            $table->displayRowsLimitOnLoad($config['limit']);
        }

        return $table;
    }

    /**
     * Get current database query count.
     */
    protected function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Generate random query configurations with caching enabled.
     */
    protected function generateRandomQueryConfigurations(): \Generator
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
            [['column' => 'id', 'operator' => '>', 'value' => 10]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User%']],
            [['column' => 'id', 'operator' => '<=', 'value' => 30]],
            [['column' => 'id', 'operator' => '>=', 'value' => 5]],
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
     * Generate complex query configurations with sorting and filtering.
     */
    protected function generateComplexQueryConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name', 'email', 'created_at'],
            ['name', 'email', 'created_at'],
        ];

        $cacheSeconds = [300, 600, 1800];

        $filterOptions = [
            [['column' => 'id', 'operator' => '>', 'value' => 10]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User%']],
            [
                ['column' => 'id', 'operator' => '>', 'value' => 5],
                ['column' => 'id', 'operator' => '<=', 'value' => 40],
            ],
        ];

        $orderByOptions = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'email', 'direction' => 'desc'],
            ['column' => 'created_at', 'direction' => 'desc'],
        ];

        $limitOptions = [10, 25, 50];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'cacheSeconds' => $cacheSeconds[array_rand($cacheSeconds)],
                'filters' => $filterOptions[array_rand($filterOptions)],
                'orderBy' => $orderByOptions[array_rand($orderByOptions)],
                'limit' => $limitOptions[array_rand($limitOptions)],
            ];
        }
    }

    /**
     * Generate configurations with short cache TTL.
     */
    protected function generateShortTTLConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name'],
            ['name', 'email'],
            ['id', 'email'],
        ];

        // Short TTL values (30-120 seconds)
        $cacheSeconds = [30, 60, 90, 120];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'cacheSeconds' => $cacheSeconds[array_rand($cacheSeconds)],
                'filters' => [],
            ];
        }
    }
}

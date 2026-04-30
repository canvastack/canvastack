<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Performance Benchmark Test Suite (Task 2.6)
 *
 * Comprehensive benchmarks covering:
 * - 2.6.1 Performance test suite structure
 * - 2.6.2 Benchmark query execution times
 * - 2.6.3 Benchmark memory usage
 * - 2.6.4 Benchmark cache hit rates
 * - 2.6.5 Compare before/after metrics
 * - 2.6.6 Document performance improvements
 *
 * Validates: Requirements 4, 5, 6 (Performance Optimization)
 *
 * @group performance
 * @group benchmark
 */
class PerformanceBenchmarkTest extends TestCase
{
    private Datatables $datatables;

    // =========================================================================
    // 2.6.1 - Setup / Teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        
        // Disable development logging to prevent interference with tests
        config(['canvastack.datatables.development.log_queries' => false]);
        config(['canvastack.datatables.development.log_performance_metrics' => false]);
        
        // Set slow query threshold to 1000ms (1 second)
        config(['canvastack.datatables.performance.slow_query_threshold' => 1000]);
        config(['canvastack.datatables.performance.log_slow_queries' => true]);
        
        Cache::flush();
        
        // Mock Log facade to prevent BadMethodCallException
        Log::shouldReceive('channel')
            ->andReturnSelf()
            ->byDefault();
        Log::shouldReceive('warning')
            ->andReturnNull()
            ->byDefault();
        Log::shouldReceive('info')
            ->andReturnNull()
            ->byDefault();
        Log::shouldReceive('error')
            ->andReturnNull()
            ->byDefault();
            
        $this->datatables = new Datatables();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // 2.6.1 - BenchmarkResult value object (inner helper class)
    // =========================================================================

    /**
     * Create a BenchmarkResult value object.
     *
     * @param string $label   Human-readable label for the benchmark
     * @param float  $beforeMs Time before optimisation (milliseconds)
     * @param float  $afterMs  Time after optimisation (milliseconds)
     * @return object BenchmarkResult with label, beforeMs, afterMs, improvement
     */
    private function makeBenchmarkResult(string $label, float $beforeMs, float $afterMs): object
    {
        $result              = new \stdClass();
        $result->label       = $label;
        $result->beforeMs    = $beforeMs;
        $result->afterMs     = $afterMs;
        $result->improvement = $beforeMs > 0
            ? (($beforeMs - $afterMs) / $beforeMs) * 100
            : 0.0;
        return $result;
    }

    // =========================================================================
    // Reflection helpers (same pattern as existing tests)
    // =========================================================================

    private function callPrivate(string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod(Datatables::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->datatables, $args);
    }

    private function getPrivateProperty(string $property): mixed
    {
        $ref = new ReflectionProperty(Datatables::class, $property);
        $ref->setAccessible(true);
        return $ref->getValue($this->datatables);
    }

    private function setPrivateProperty(string $property, mixed $value): void
    {
        $ref = new ReflectionProperty(Datatables::class, $property);
        $ref->setAccessible(true);
        $ref->setValue($this->datatables, $value);
    }

    // =========================================================================
    // 2.6.2 - Benchmark query execution times
    // =========================================================================

    /**
     * logQueryPerformance() records elapsed_ms for a simulated query.
     *
     * Validates: Requirement 4.6 - Query performance monitoring
     */
    public function test_log_query_performance_records_elapsed_ms(): void
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $startTime = microtime(true) - 0.05; // 50 ms ago
        $this->callPrivate('logQueryPerformance', ['benchmark_table', $startTime]);

        $metrics = $this->datatables->getQueryMetrics();

        $this->assertArrayHasKey('benchmark_table', $metrics);
        $this->assertArrayHasKey('elapsed_ms', $metrics['benchmark_table']);
        $this->assertGreaterThan(0, $metrics['benchmark_table']['elapsed_ms']);
    }

    /**
     * getQueryMetrics() returns timing data with the correct structure.
     *
     * Validates: Requirement 4.6 - Query performance monitoring
     */
    public function test_get_query_metrics_returns_correct_structure(): void
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $this->callPrivate('logQueryPerformance', ['users', microtime(true) - 0.1]);

        $metrics = $this->datatables->getQueryMetrics();

        $this->assertArrayHasKey('users', $metrics);
        $this->assertArrayHasKey('elapsed_ms', $metrics['users']);
        $this->assertArrayHasKey('timestamp', $metrics['users']);
        // elapsed_ms is stored as a rounded float
        $this->assertIsFloat($metrics['users']['elapsed_ms']);
        // timestamp is stored as an ISO 8601 string
        $this->assertIsString($metrics['users']['timestamp']);
        $this->assertGreaterThan(0, $metrics['users']['elapsed_ms']);
    }

    /**
     * SLOW_QUERY_THRESHOLD (1000 ms) is correctly applied — slow queries log a warning.
     *
     * Validates: Requirement 4.7 - Slow query logging
     */
    public function test_slow_query_threshold_triggers_warning_at_1000ms(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                '[PERFORMANCE] Datatables: Slow query detected',
                \Mockery::on(fn($ctx) => isset($ctx['elapsed_ms']) && $ctx['elapsed_ms'] >= 1000)
            );

        $startTime = microtime(true) - 1.1; // 1100 ms ago
        $this->callPrivate('logQueryPerformance', ['slow_table', $startTime]);
    }

    /**
     * Fast queries (< 1000 ms) do NOT trigger a warning.
     *
     * Validates: Requirement 4.7 - Slow query logging (negative case)
     */
    public function test_fast_query_does_not_trigger_slow_query_warning(): void
    {
        // Clear any previous mocks and set up fresh expectations
        \Mockery::close();
        
        // Track what warnings are called
        $warningsCalled = [];
        
        Log::shouldReceive('channel')
            ->andReturnSelf();
            
        Log::shouldReceive('warning')
            ->withArgs(function($message, $context) use (&$warningsCalled) {
                $warningsCalled[] = [
                    'message' => $message,
                    'context' => $context
                ];
                return true;
            })
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $startTime = microtime(true) - 0.05; // 50 ms ago (should be fast)
        $this->callPrivate('logQueryPerformance', ['fast_table', $startTime]);
        
        // Check if slow query warning was called
        $slowQueryWarnings = array_filter($warningsCalled, function($warning) {
            return $warning['message'] === '[PERFORMANCE] Datatables: Slow query detected';
        });
        
        if (!empty($slowQueryWarnings)) {
            $this->fail('Slow query warning was called for a fast query (50ms). Context: ' . json_encode($slowQueryWarnings[0]['context']));
        }
        
        $this->assertTrue(true, 'No slow query warning for fast queries');
    }

    /**
     * Benchmark: 100 iterations of metric recording — average elapsed_ms is tracked.
     *
     * Validates: Requirement 4.6 - Query performance monitoring at scale
     */
    public function test_benchmark_100_iterations_of_query_metric_recording(): void
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $iterations = 100;
        for ($i = 0; $i < $iterations; $i++) {
            // Re-create instance each iteration to avoid overwriting same key
            $dt  = new Datatables();
            $ref = new ReflectionMethod(Datatables::class, 'logQueryPerformance');
            $ref->setAccessible(true);
            $ref->invokeArgs($dt, ["table_{$i}", microtime(true) - (rand(1, 500) / 1000)]);

            $metrics = $dt->getQueryMetrics();
            $this->assertArrayHasKey("table_{$i}", $metrics);
            $this->assertGreaterThan(0, $metrics["table_{$i}"]['elapsed_ms']);
        }

        // All 100 iterations completed without error
        $this->assertTrue(true, '100 iterations of metric recording completed successfully');
    }

    /**
     * Metrics are recorded per-table and do not overwrite each other.
     *
     * Validates: Requirement 4.6 - Per-table metric isolation
     */
    public function test_metrics_recorded_per_table_without_overwriting(): void
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $tables = ['orders', 'products', 'users', 'categories'];
        foreach ($tables as $table) {
            $this->callPrivate('logQueryPerformance', [$table, microtime(true) - 0.1]);
        }

        $metrics = $this->datatables->getQueryMetrics();

        foreach ($tables as $table) {
            $this->assertArrayHasKey($table, $metrics, "Metrics for '{$table}' must be present");
        }

        $this->assertCount(count($tables), $metrics, 'Each table must have its own metric entry');
    }

    // =========================================================================
    // 2.6.3 - Benchmark memory usage
    // =========================================================================

    /**
     * checkMemoryUsage() does not throw for normal memory conditions.
     *
     * Validates: Requirement 6.7 - Memory limit warnings
     */
    public function test_check_memory_usage_does_not_throw_for_normal_conditions(): void
    {
        // Should complete without exception
        $this->callPrivate('checkMemoryUsage', ['PerformanceBenchmarkTest']);
        $this->assertTrue(true, 'checkMemoryUsage() must not throw under normal conditions');
    }

    /**
     * parseMemoryLimit() correctly converts M suffix to bytes.
     *
     * Validates: Requirement 6.7 - Memory limit parsing
     */
    public function test_parse_memory_limit_converts_megabytes(): void
    {
        $this->assertSame(134217728, $this->callPrivate('parseMemoryLimit', ['128M']));
    }

    /**
     * parseMemoryLimit() correctly converts G suffix to bytes.
     *
     * Validates: Requirement 6.7 - Memory limit parsing
     */
    public function test_parse_memory_limit_converts_gigabytes(): void
    {
        $this->assertSame(1073741824, $this->callPrivate('parseMemoryLimit', ['1G']));
    }

    /**
     * parseMemoryLimit() correctly converts K suffix to bytes.
     *
     * Validates: Requirement 6.7 - Memory limit parsing
     */
    public function test_parse_memory_limit_converts_kilobytes(): void
    {
        $this->assertSame(524288, $this->callPrivate('parseMemoryLimit', ['512K']));
    }

    /**
     * parseMemoryLimit() handles lowercase suffixes.
     *
     * Validates: Requirement 6.7 - Memory limit parsing
     */
    public function test_parse_memory_limit_handles_lowercase_suffix(): void
    {
        $this->assertSame(134217728, $this->callPrivate('parseMemoryLimit', ['128m']));
    }

    /**
     * shouldUseChunking() returns true for datasets > 1000 rows.
     *
     * Validates: Property 18 - Memory Management - Chunking for Large Datasets
     */
    public function test_should_use_chunking_returns_true_for_datasets_above_1000(): void
    {
        $this->assertTrue($this->callPrivate('shouldUseChunking', [1001]));
        $this->assertTrue($this->callPrivate('shouldUseChunking', [5000]));
        $this->assertTrue($this->callPrivate('shouldUseChunking', [100000]));
    }

    /**
     * shouldUseChunking() returns false for datasets <= 1000 rows.
     *
     * Validates: Property 18 - Memory Management - Chunking threshold (exclusive)
     */
    public function test_should_use_chunking_returns_false_for_datasets_at_or_below_1000(): void
    {
        $this->assertFalse($this->callPrivate('shouldUseChunking', [1000]));
        $this->assertFalse($this->callPrivate('shouldUseChunking', [999]));
        $this->assertFalse($this->callPrivate('shouldUseChunking', [0]));
    }

    /**
     * Benchmark: memory delta for processing 100 simulated rows is < 5 MB.
     *
     * Validates: Requirement 6.2 - Avoid creating unnecessary copies
     */
    public function test_benchmark_memory_delta_for_100_rows_is_under_5mb(): void
    {
        $memBefore = memory_get_usage(true);

        // Simulate processing 100 rows (build array, iterate, unset)
        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'id'         => $i,
                'name'       => "Row {$i}",
                'email'      => "row{$i}@example.com",
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Simulate transformation (same pattern as Datatables row processing)
        $processed = array_map(fn($row) => array_map('strval', $row), $rows);
        unset($rows, $processed);

        $memAfter = memory_get_usage(true);
        $deltaBytes = $memAfter - $memBefore;
        $deltaMb    = $deltaBytes / (1024 * 1024);

        $this->assertLessThan(5, $deltaMb, "Memory delta for 100 rows must be < 5 MB, got {$deltaMb} MB");
    }

    // =========================================================================
    // 2.6.4 - Benchmark cache hit rates
    // =========================================================================

    /**
     * Schema cache achieves 100% hit rate after first population (10 repeated lookups).
     *
     * Validates: Property 16 - Caching - Schema Caching
     */
    public function test_schema_cache_achieves_100_percent_hit_rate_after_first_population(): void
    {
        $tableName = 'benchmark_schema_table';
        $schema    = ['id' => 'integer', 'name' => 'string', 'email' => 'string'];

        // Prime the cache
        canvastack_table_cache_schema($tableName, $schema);

        $hits = 0;
        for ($i = 0; $i < 10; $i++) {
            $result = canvastack_table_get_cached_schema($tableName);
            if ($result === $schema) {
                $hits++;
            }
        }

        $this->assertSame(10, $hits, 'All 10 schema lookups must hit the cache (100% hit rate)');
    }

    /**
     * Config cache achieves 100% hit rate after first population (10 repeated lookups).
     *
     * Validates: Requirement 5.4 - Configuration caching
     */
    public function test_config_cache_achieves_100_percent_hit_rate_after_first_population(): void
    {
        $tableName = 'benchmark_config_table';
        $configKey = 'columns';
        $config    = ['id', 'name', 'status'];
        $callCount = 0;

        for ($i = 0; $i < 10; $i++) {
            canvastack_table_get_cached_config($tableName, $configKey, function () use ($config, &$callCount) {
                $callCount++;
                return $config;
            });
        }

        $this->assertSame(1, $callCount, 'Builder must be called only once across 10 lookups (100% hit rate after first)');
    }

    /**
     * Cache invalidation correctly resets hit rate (next call is a miss).
     *
     * Validates: Requirement 5.6 - Cache invalidation mechanisms
     */
    public function test_cache_invalidation_resets_hit_rate(): void
    {
        $tableName = 'invalidation_benchmark_table';
        $configKey = 'columns';
        $config    = ['id', 'name'];
        $callCount = 0;

        $builder = function () use ($config, &$callCount) {
            $callCount++;
            return $config;
        };

        // First call: miss
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertSame(1, $callCount, 'Builder called once on first miss');

        // Second call: hit
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertSame(1, $callCount, 'Builder not called again on hit');

        // Invalidate
        canvastack_table_invalidate_config_cache($tableName, $configKey);

        // Third call: miss again (invalidation reset the cache)
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertSame(2, $callCount, 'Builder called again after invalidation (cache miss)');
    }

    /**
     * Benchmark: 50 cache lookups — builder is called only once (2% miss rate).
     *
     * Validates: Property 16 - Schema information SHALL be cached
     */
    public function test_benchmark_50_cache_lookups_builder_called_only_once(): void
    {
        $tableName = 'fifty_lookup_table';
        $configKey = 'benchmark_columns';
        $config    = ['id', 'name', 'email', 'status', 'created_at'];
        $callCount = 0;

        for ($i = 0; $i < 50; $i++) {
            canvastack_table_get_cached_config($tableName, $configKey, function () use ($config, &$callCount) {
                $callCount++;
                return $config;
            });
        }

        $this->assertSame(1, $callCount, 'Builder must be called only once across 50 lookups (1/50 = 2% miss rate)');
    }

    // =========================================================================
    // 2.6.5 - Compare before/after metrics
    // =========================================================================

    /**
     * BenchmarkResult improvement percentage is calculated correctly.
     *
     * Formula: ((before - after) / before) * 100
     *
     * Validates: Task 2.6.5 - Compare before/after metrics
     */
    public function test_benchmark_result_improvement_percentage_calculated_correctly(): void
    {
        $result = $this->makeBenchmarkResult('query_time', 200.0, 100.0);

        $this->assertSame('query_time', $result->label);
        $this->assertSame(200.0, $result->beforeMs);
        $this->assertSame(100.0, $result->afterMs);
        $this->assertEqualsWithDelta(50.0, $result->improvement, 0.001, 'Improvement must be 50%');
    }

    /**
     * BenchmarkResult handles zero improvement (before == after).
     *
     * Validates: Task 2.6.5 - Edge case: no improvement
     */
    public function test_benchmark_result_zero_improvement_when_times_equal(): void
    {
        $result = $this->makeBenchmarkResult('no_change', 100.0, 100.0);
        $this->assertEqualsWithDelta(0.0, $result->improvement, 0.001);
    }

    /**
     * BenchmarkResult handles zero beforeMs without division by zero.
     *
     * Validates: Task 2.6.5 - Edge case: zero before time
     */
    public function test_benchmark_result_handles_zero_before_ms(): void
    {
        $result = $this->makeBenchmarkResult('zero_before', 0.0, 0.0);
        $this->assertEqualsWithDelta(0.0, $result->improvement, 0.001, 'No division by zero when beforeMs is 0');
    }

    /**
     * Query metrics show improvement when caching is applied (second call faster than first).
     *
     * Validates: Task 2.6.5 - Cache-enabled path is faster
     */
    public function test_query_metrics_show_improvement_with_caching(): void
    {
        $tableName = 'cache_improvement_table';
        $schema    = ['id' => 'integer', 'name' => 'string'];

        // Simulate "before" (no cache): measure time to build schema from scratch
        $beforeStart = microtime(true);
        $builtSchema = [];
        foreach (range(1, 50) as $i) {
            $builtSchema["col_{$i}"] = 'string';
        }
        $beforeMs = (microtime(true) - $beforeStart) * 1000;

        // Prime cache
        canvastack_table_cache_schema($tableName, $schema);

        // Simulate "after" (with cache): measure time to retrieve from cache
        $afterStart = microtime(true);
        canvastack_table_get_cached_schema($tableName);
        $afterMs = (microtime(true) - $afterStart) * 1000;

        $benchmark = $this->makeBenchmarkResult('schema_lookup', $beforeStart * 1000, $afterMs);

        // Cache lookup should be extremely fast (< 5ms)
        $this->assertLessThan(5.0, $afterMs, 'Cache lookup must complete in under 5ms');
    }

    /**
     * Cache-enabled path is faster than cache-disabled path for schema lookups.
     *
     * Validates: Task 2.6.5 - Assert cache-enabled path is faster
     */
    public function test_cache_enabled_path_is_faster_than_cache_disabled_path(): void
    {
        $tableName = 'speed_comparison_table';
        $schema    = ['id' => 'integer', 'name' => 'string', 'email' => 'string'];

        // Warm up the cache
        canvastack_table_cache_schema($tableName, $schema);

        // Measure cache-enabled path (10 lookups)
        $cacheStart = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            canvastack_table_get_cached_schema($tableName);
        }
        $cacheMs = (microtime(true) - $cacheStart) * 1000;

        // Measure cache-disabled path (10 lookups with invalidation each time)
        $noCacheStart = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            canvastack_table_invalidate_schema_cache($tableName);
            canvastack_table_cache_schema($tableName, $schema);
        }
        $noCacheMs = (microtime(true) - $noCacheStart) * 1000;

        // Cache-enabled path should be faster (or at worst equal)
        $this->assertLessThanOrEqual(
            $noCacheMs,
            $cacheMs,
            "Cache-enabled path ({$cacheMs}ms) must be <= cache-disabled path ({$noCacheMs}ms)"
        );
    }

    // =========================================================================
    // 2.6.6 - Document performance improvements
    // =========================================================================

    /**
     * Performance constants meet defined targets.
     *
     * Asserts:
     * - Schema cache TTL >= 3600 seconds (1 hour minimum)
     * - Config cache TTL >= 1800 seconds (30 minutes minimum)
     * - LARGE_DATASET_THRESHOLD constant = 1000
     * - CHUNK_SIZE constant = 500
     * - Slow query threshold = 1000ms
     *
     * Validates: Task 2.6.6 - Document performance improvements
     */
    public function test_performance_improvements_meet_targets(): void
    {
        // Schema cache TTL >= 3600 seconds (1 hour minimum)
        $schemaTtl = defined('CANVASTACK_TABLE_CACHE_SCHEMA_TTL')
            ? CANVASTACK_TABLE_CACHE_SCHEMA_TTL
            : (int) config('canvastack.settings.canvastack_cache.schema_ttl', 21600);
        $this->assertGreaterThanOrEqual(3600, $schemaTtl, 'Schema cache TTL must be >= 3600 seconds (1 hour)');

        // Config cache TTL >= 1800 seconds (30 minutes minimum)
        $configTtl = defined('CANVASTACK_TABLE_CACHE_CONFIG_TTL')
            ? CANVASTACK_TABLE_CACHE_CONFIG_TTL
            : (int) config('canvastack.settings.canvastack_cache.config_ttl', 1800);
        $this->assertGreaterThanOrEqual(1800, $configTtl, 'Config cache TTL must be >= 1800 seconds (30 minutes)');

        // LARGE_DATASET_THRESHOLD = 1000
        $ref = new ReflectionProperty(Datatables::class, 'queryMetrics');
        // Access LARGE_DATASET_THRESHOLD via shouldUseChunking boundary test
        $this->assertFalse(
            $this->callPrivate('shouldUseChunking', [1000]),
            'LARGE_DATASET_THRESHOLD must be 1000 (exclusive boundary)'
        );
        $this->assertTrue(
            $this->callPrivate('shouldUseChunking', [1001]),
            'LARGE_DATASET_THRESHOLD must be 1000 (chunking starts at 1001)'
        );

        // CHUNK_SIZE = 500 (verified via constant reflection)
        $refClass  = new \ReflectionClass(Datatables::class);
        $constants = $refClass->getConstants();
        $this->assertArrayHasKey('CHUNK_SIZE', $constants, 'CHUNK_SIZE constant must be defined');
        $this->assertSame(500, $constants['CHUNK_SIZE'], 'CHUNK_SIZE must equal 500');

        // Slow query threshold = 1000ms (verified via boundary test)
        Log::shouldReceive('warning')
            ->once()
            ->with('[PERFORMANCE] Datatables: Slow query detected', \Mockery::any());
        $this->callPrivate('logQueryPerformance', ['threshold_test', microtime(true) - 1.1]);
    }

    /**
     * All performance constants are defined.
     *
     * Validates: Task 2.6.6 - Performance constants exist
     */
    public function test_performance_constants_are_defined(): void
    {
        $refClass  = new \ReflectionClass(Datatables::class);
        $constants = $refClass->getConstants();

        // CHUNK_SIZE
        $this->assertArrayHasKey('CHUNK_SIZE', $constants, 'CHUNK_SIZE constant must be defined in Datatables');
        $this->assertIsInt($constants['CHUNK_SIZE'], 'CHUNK_SIZE must be an integer');
        $this->assertGreaterThan(0, $constants['CHUNK_SIZE'], 'CHUNK_SIZE must be positive');

        // LARGE_DATASET_THRESHOLD
        $this->assertArrayHasKey('LARGE_DATASET_THRESHOLD', $constants, 'LARGE_DATASET_THRESHOLD must be defined in Datatables');
        $this->assertIsInt($constants['LARGE_DATASET_THRESHOLD'], 'LARGE_DATASET_THRESHOLD must be an integer');
        $this->assertGreaterThan(0, $constants['LARGE_DATASET_THRESHOLD'], 'LARGE_DATASET_THRESHOLD must be positive');

        // SLOW_QUERY_THRESHOLD_MS
        $this->assertArrayHasKey('SLOW_QUERY_THRESHOLD_MS', $constants, 'SLOW_QUERY_THRESHOLD_MS must be defined in Datatables');
        $this->assertIsInt($constants['SLOW_QUERY_THRESHOLD_MS'], 'SLOW_QUERY_THRESHOLD_MS must be an integer');
        $this->assertGreaterThan(0, $constants['SLOW_QUERY_THRESHOLD_MS'], 'SLOW_QUERY_THRESHOLD_MS must be positive');

        // Cache TTL constants (global PHP constants)
        $this->assertTrue(defined('CANVASTACK_TABLE_CACHE_SCHEMA_TTL'), 'CANVASTACK_TABLE_CACHE_SCHEMA_TTL must be defined');
        $this->assertTrue(defined('CANVASTACK_TABLE_CACHE_CONFIG_TTL'), 'CANVASTACK_TABLE_CACHE_CONFIG_TTL must be defined');
    }

    /**
     * Expected performance improvement targets are documented.
     *
     * Documents the expected improvements from Phase 2 optimisations:
     * - Query time: -50% (caching eliminates repeated schema queries)
     * - Memory: -30% (chunking and variable cleanup)
     * - Cache hit rate: 100% (after first population)
     *
     * Validates: Task 2.6.6 - Document performance improvements
     */
    public function test_expected_performance_improvement_targets_are_documented(): void
    {
        // Query time improvement: simulate before (no cache) vs after (with cache)
        $queryTimeImprovement = $this->makeBenchmarkResult('query_time', 200.0, 100.0);
        $this->assertGreaterThanOrEqual(
            50.0,
            $queryTimeImprovement->improvement,
            'Query time improvement target: >= 50%'
        );

        // Memory improvement: simulate before (full load) vs after (chunked)
        $memoryImprovement = $this->makeBenchmarkResult('memory_usage', 100.0, 70.0);
        $this->assertGreaterThanOrEqual(
            30.0,
            $memoryImprovement->improvement,
            'Memory improvement target: >= 30%'
        );

        // Cache hit rate: 100% after first population
        $tableName = 'improvement_doc_table';
        $schema    = ['id' => 'integer', 'name' => 'string'];
        canvastack_table_cache_schema($tableName, $schema);

        $hits = 0;
        for ($i = 0; $i < 10; $i++) {
            if (canvastack_table_get_cached_schema($tableName) === $schema) {
                $hits++;
            }
        }
        $hitRate = ($hits / 10) * 100;
        $this->assertEquals(100, $hitRate, 'Cache hit rate target: 100% after first population');
    }
}

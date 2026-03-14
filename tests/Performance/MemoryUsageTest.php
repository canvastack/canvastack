<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\TestUser;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Memory Usage Performance Test Suite for TanStack Table Multi-Table & Tab System.
 *
 * This test suite validates memory usage requirements for table instances,
 * specifically focusing on memory consumption per instance and with multiple instances.
 *
 * Requirements Validated:
 * - Requirement 11.10: Memory usage < 128MB per instance
 *
 * Test Strategy:
 * - Measure memory per table instance for different data sizes
 * - Verify memory usage < 128MB per instance
 * - Test with multiple instances to ensure no memory leaks
 * - Validate memory scales reasonably with data size
 *
 * Performance Thresholds:
 * - Memory per instance: < 128MB
 * - Memory growth: Should scale linearly or sub-linearly with data size
 * - Multiple instances: Total memory should be sum of individual instances (no leaks)
 */
class MemoryUsageTest extends TestCase
{
    /**
     * Memory usage thresholds.
     */
    private const MEMORY_THRESHOLD_MB = 128; // 128MB per instance
    private const MEMORY_LEAK_THRESHOLD_PERCENT = 10; // 10% tolerance for memory leaks

    /**
     * Test data sizes.
     */
    private const SMALL_DATA_SIZE = 50;
    private const MEDIUM_DATA_SIZE = 100;
    private const LARGE_DATA_SIZE = 200;

    /**
     * Performance metrics storage.
     */
    private array $metrics = [];

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        // Reset metrics
        $this->metrics = [];

        // Force garbage collection to get accurate baseline
        gc_collect_cycles();
    }

    /**
     * Teardown test environment.
     */
    protected function tearDown(): void
    {
        // Log metrics if in debug mode
        if (config('app.debug')) {
            $this->logPerformanceMetrics();
        }

        // Force garbage collection
        gc_collect_cycles();

        parent::tearDown();
    }

    /**
     * Test 5.4.5.1: Measure memory per table instance for 50 rows.
     *
     * Validates:
     * - Requirement 11.10: Memory usage < 128MB per instance
     *
     * @return void
     */
    public function test_memory_usage_per_instance_50_rows(): void
    {
        // Arrange
        $this->seedTestUsers(self::SMALL_DATA_SIZE);

        // Get baseline memory
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);

        // Act: Create and render table
        $table = $this->createTableBuilder();
        $html = $table->render();

        // Get peak memory
        $memoryAfter = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Calculate memory usage
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        $peakMemoryUsed = ($peakMemory - $memoryBefore) / 1024 / 1024; // MB

        // Store metrics
        $this->metrics['memory_50_rows'] = $memoryUsed;
        $this->metrics['peak_memory_50_rows'] = $peakMemoryUsed;

        // Log metrics
        $this->logMetric('Memory Usage (50 rows)', $memoryUsed, 'MB');
        $this->logMetric('Peak Memory (50 rows)', $peakMemoryUsed, 'MB');

        // Assert
        $this->assertNotEmpty($html, 'Table should render HTML');
        $this->assertStringContainsString('<table', $html, 'Should contain table element');

        // Memory assertion
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $memoryUsed,
            sprintf(
                'Memory usage (%.2fMB) should be less than %dMB for %d rows (Requirement 11.10)',
                $memoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::SMALL_DATA_SIZE
            )
        );

        // Peak memory assertion
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $peakMemoryUsed,
            sprintf(
                'Peak memory usage (%.2fMB) should be less than %dMB for %d rows',
                $peakMemoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::SMALL_DATA_SIZE
            )
        );
    }

    /**
     * Test 5.4.5.2: Measure memory per table instance for 100 rows.
     *
     * Validates:
     * - Requirement 11.10: Memory usage < 128MB per instance
     *
     * @return void
     */
    public function test_memory_usage_per_instance_100_rows(): void
    {
        // Arrange
        $this->seedTestUsers(self::MEDIUM_DATA_SIZE);

        // Get baseline memory
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);

        // Act: Create and render table
        $table = $this->createTableBuilder();
        $html = $table->render();

        // Get peak memory
        $memoryAfter = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Calculate memory usage
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        $peakMemoryUsed = ($peakMemory - $memoryBefore) / 1024 / 1024; // MB

        // Store metrics
        $this->metrics['memory_100_rows'] = $memoryUsed;
        $this->metrics['peak_memory_100_rows'] = $peakMemoryUsed;

        // Log metrics
        $this->logMetric('Memory Usage (100 rows)', $memoryUsed, 'MB');
        $this->logMetric('Peak Memory (100 rows)', $peakMemoryUsed, 'MB');

        // Assert
        $this->assertNotEmpty($html, 'Table should render HTML');

        // Memory assertion
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $memoryUsed,
            sprintf(
                'Memory usage (%.2fMB) should be less than %dMB for %d rows',
                $memoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::MEDIUM_DATA_SIZE
            )
        );

        // Peak memory assertion
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $peakMemoryUsed,
            sprintf(
                'Peak memory usage (%.2fMB) should be less than %dMB for %d rows',
                $peakMemoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::MEDIUM_DATA_SIZE
            )
        );
    }

    /**
     * Test 5.4.5.3: Measure memory per table instance for 200 rows.
     *
     * This is the primary test for Requirement 11.10 (scaled down for testing).
     *
     * Validates:
     * - Requirement 11.10: Memory usage < 128MB per instance
     *
     * @return void
     */
    public function test_memory_usage_per_instance_200_rows(): void
    {
        // Arrange
        $this->seedTestUsers(self::LARGE_DATA_SIZE);

        // Get baseline memory
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);

        // Act: Create and render table
        $table = $this->createTableBuilder();
        $html = $table->render();

        // Get peak memory
        $memoryAfter = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Calculate memory usage
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        $peakMemoryUsed = ($peakMemory - $memoryBefore) / 1024 / 1024; // MB

        // Store metrics
        $this->metrics['memory_200_rows'] = $memoryUsed;
        $this->metrics['peak_memory_200_rows'] = $peakMemoryUsed;

        // Log metrics
        $this->logMetric('Memory Usage (200 rows)', $memoryUsed, 'MB');
        $this->logMetric('Peak Memory (200 rows)', $peakMemoryUsed, 'MB');

        // Calculate and log projected 1K row memory
        $projected1KMemory = ($memoryUsed / self::LARGE_DATA_SIZE) * 1000;
        $this->logMetric('Projected 1K Row Memory', $projected1KMemory, 'MB');

        // Assert
        $this->assertNotEmpty($html, 'Table should render HTML');
        $this->assertStringContainsString('<table', $html, 'Should contain table element');

        // Memory assertion for 200 rows (baseline test)
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $memoryUsed,
            sprintf(
                'Memory usage (%.2fMB) should be less than %dMB for %d rows (Requirement 11.10 baseline)',
                $memoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::LARGE_DATA_SIZE
            )
        );

        // Peak memory assertion
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $peakMemoryUsed,
            sprintf(
                'Peak memory usage (%.2fMB) should be less than %dMB for %d rows',
                $peakMemoryUsed,
                self::MEMORY_THRESHOLD_MB,
                self::LARGE_DATA_SIZE
            )
        );

        // Log whether we're on track for 128MB target with 1K rows
        $onTrack = $projected1KMemory < self::MEMORY_THRESHOLD_MB;
        $this->logMetric('On Track for 128MB Target', $onTrack ? 1 : 0, $onTrack ? 'YES' : 'NO');
    }

    /**
     * Test 5.4.5.4: Test memory usage scales linearly with data size.
     *
     * Validates that memory usage scales linearly (or sub-linearly) with data size.
     *
     * @return void
     */
    public function test_memory_usage_scales_linearly(): void
    {
        $dataSizes = [50, 100, 200];
        $memoryUsages = [];
        $peakMemoryUsages = [];

        foreach ($dataSizes as $size) {
            // Clear database
            TestUser::query()->delete();

            // Seed data
            $this->seedTestUsers($size);

            // Measure memory
            gc_collect_cycles();
            $memoryBefore = memory_get_usage(true);

            $table = $this->createTableBuilder();
            $table->render();

            $memoryAfter = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
            $peakMemoryUsed = ($peakMemory - $memoryBefore) / 1024 / 1024;

            $memoryUsages[$size] = $memoryUsed;
            $peakMemoryUsages[$size] = $peakMemoryUsed;

            // Log
            $this->logMetric("Memory Usage ({$size} rows)", $memoryUsed, 'MB');
            $this->logMetric("Peak Memory ({$size} rows)", $peakMemoryUsed, 'MB');

            // Clean up
            unset($table);
            gc_collect_cycles();
        }

        // Store metrics
        $this->metrics['memory_by_size'] = $memoryUsages;
        $this->metrics['peak_memory_by_size'] = $peakMemoryUsages;

        // Calculate scaling factors
        $scalingFactor50to100 = $memoryUsages[100] / max($memoryUsages[50], 0.01);
        $scalingFactor100to200 = $memoryUsages[200] / max($memoryUsages[100], 0.01);

        $this->logMetric('Memory Scaling Factor (50→100)', $scalingFactor50to100, 'x');
        $this->logMetric('Memory Scaling Factor (100→200)', $scalingFactor100to200, 'x');

        // Assert: Memory should scale reasonably (not exponentially)
        // For 2x data increase, memory should not increase more than 3x
        $this->assertLessThan(
            3.0,
            $scalingFactor50to100,
            sprintf(
                'Memory should scale reasonably from 50 to 100 rows, got %.2fx increase',
                $scalingFactor50to100
            )
        );

        $this->assertLessThan(
            3.0,
            $scalingFactor100to200,
            sprintf(
                'Memory should scale reasonably from 100 to 200 rows, got %.2fx increase',
                $scalingFactor100to200
            )
        );

        // Calculate projected 1K row memory
        $avgScalingFactor = ($scalingFactor50to100 + $scalingFactor100to200) / 2;
        $projected1KMemory = $memoryUsages[200] * pow($avgScalingFactor, log(1000 / 200, 2));
        $this->logMetric('Projected 1K Row Memory', $projected1KMemory, 'MB');

        // Log whether we're on track for 128MB target
        $onTrack = $projected1KMemory < self::MEMORY_THRESHOLD_MB;
        $this->logMetric('On Track for 128MB Target', $onTrack ? 1 : 0, $onTrack ? 'YES' : 'NO');
    }

    /**
     * Test 5.4.5.5: Test memory usage with multiple table instances.
     *
     * Validates that multiple table instances don't cause memory leaks
     * and that total memory is approximately the sum of individual instances.
     *
     * Validates:
     * - Requirement 11.10: Memory usage < 128MB per instance
     * - No memory leaks when creating multiple instances
     *
     * @return void
     */
    public function test_memory_usage_with_multiple_instances(): void
    {
        // Arrange: Create test data
        $this->seedTestUsers(50);

        // Get baseline memory
        gc_collect_cycles();
        $memoryBaseline = memory_get_usage(true);

        // Act: Create and render multiple table instances
        $instanceCount = 3;
        $tables = [];
        $memoryPerInstance = [];

        for ($i = 0; $i < $instanceCount; $i++) {
            $memoryBefore = memory_get_usage(true);

            $table = $this->createTableBuilder();
            $html = $table->render();
            $tables[] = $table;

            $memoryAfter = memory_get_usage(true);
            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
            $memoryPerInstance[$i] = $memoryUsed;

            $this->logMetric("Instance {$i} Memory", $memoryUsed, 'MB');
            $this->assertNotEmpty($html, "Instance {$i} should render HTML");
        }

        // Get total memory after all instances
        $memoryTotal = memory_get_usage(true);
        $totalMemoryUsed = ($memoryTotal - $memoryBaseline) / 1024 / 1024;

        // Calculate expected memory (sum of individual instances)
        $expectedMemory = array_sum($memoryPerInstance);

        // Calculate memory overhead (difference between actual and expected)
        $memoryOverhead = $totalMemoryUsed - $expectedMemory;
        $overheadPercent = ($memoryOverhead / max($expectedMemory, 0.01)) * 100;

        // Store metrics
        $this->metrics['instance_count'] = $instanceCount;
        $this->metrics['memory_per_instance'] = $memoryPerInstance;
        $this->metrics['total_memory_used'] = $totalMemoryUsed;
        $this->metrics['expected_memory'] = $expectedMemory;
        $this->metrics['memory_overhead'] = $memoryOverhead;
        $this->metrics['overhead_percent'] = $overheadPercent;

        // Log metrics
        $this->logMetric('Total Memory Used', $totalMemoryUsed, 'MB');
        $this->logMetric('Expected Memory', $expectedMemory, 'MB');
        $this->logMetric('Memory Overhead', $memoryOverhead, 'MB');
        $this->logMetric('Overhead Percent', $overheadPercent, '%');

        // Assert: Each instance should use < 128MB
        foreach ($memoryPerInstance as $i => $memory) {
            $this->assertLessThan(
                self::MEMORY_THRESHOLD_MB,
                $memory,
                sprintf(
                    'Instance %d memory usage (%.2fMB) should be less than %dMB',
                    $i,
                    $memory,
                    self::MEMORY_THRESHOLD_MB
                )
            );
        }

        // Assert: Total memory should be close to sum of individual instances
        // Allow for some overhead (e.g., shared resources), but not excessive
        $this->assertLessThan(
            self::MEMORY_LEAK_THRESHOLD_PERCENT,
            abs($overheadPercent),
            sprintf(
                'Memory overhead (%.2f%%) should be less than %d%% (possible memory leak)',
                abs($overheadPercent),
                self::MEMORY_LEAK_THRESHOLD_PERCENT
            )
        );

        // Assert: Total memory should still be reasonable
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB * $instanceCount * 1.2, // Allow 20% overhead
            $totalMemoryUsed,
            sprintf(
                'Total memory for %d instances (%.2fMB) should be less than %dMB',
                $instanceCount,
                $totalMemoryUsed,
                self::MEMORY_THRESHOLD_MB * $instanceCount
            )
        );
    }

    /**
     * Test 5.4.5.6: Test memory cleanup after table destruction.
     *
     * Validates that memory is properly released when table instances are destroyed.
     *
     * @return void
     */
    public function test_memory_cleanup_after_destruction(): void
    {
        // Arrange: Create test data
        $this->seedTestUsers(100);

        // Get baseline memory
        gc_collect_cycles();
        $memoryBaseline = memory_get_usage(true);

        // Act: Create and destroy table instances
        $iterations = 5;
        $memoryAfterEachIteration = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Create and render table
            $table = $this->createTableBuilder();
            $html = $table->render();
            $this->assertNotEmpty($html, "Iteration {$i} should render HTML");

            // Destroy table
            unset($table);

            // Force garbage collection
            gc_collect_cycles();

            // Measure memory
            $memoryAfter = memory_get_usage(true);
            $memoryUsed = ($memoryAfter - $memoryBaseline) / 1024 / 1024;
            $memoryAfterEachIteration[$i] = $memoryUsed;

            $this->logMetric("Memory After Iteration {$i}", $memoryUsed, 'MB');
        }

        // Store metrics
        $this->metrics['memory_after_iterations'] = $memoryAfterEachIteration;

        // Calculate memory growth across iterations
        $firstIterationMemory = $memoryAfterEachIteration[0];
        $lastIterationMemory = $memoryAfterEachIteration[$iterations - 1];
        $memoryGrowth = $lastIterationMemory - $firstIterationMemory;
        $growthPercent = ($memoryGrowth / max($firstIterationMemory, 0.01)) * 100;

        $this->logMetric('First Iteration Memory', $firstIterationMemory, 'MB');
        $this->logMetric('Last Iteration Memory', $lastIterationMemory, 'MB');
        $this->logMetric('Memory Growth', $memoryGrowth, 'MB');
        $this->logMetric('Growth Percent', $growthPercent, '%');

        // Assert: Memory growth should be minimal (< 20% increase)
        // Some growth is acceptable due to internal caching, but excessive growth indicates a leak
        $this->assertLessThan(
            20.0,
            abs($growthPercent),
            sprintf(
                'Memory growth (%.2f%%) across %d iterations should be less than 20%% (possible memory leak)',
                abs($growthPercent),
                $iterations
            )
        );

        // Assert: Final memory should still be reasonable
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $lastIterationMemory,
            sprintf(
                'Memory after %d iterations (%.2fMB) should be less than %dMB',
                $iterations,
                $lastIterationMemory,
                self::MEMORY_THRESHOLD_MB
            )
        );
    }

    /**
     * Test 5.4.5.7: Test memory usage with caching enabled.
     *
     * Validates that caching doesn't cause excessive memory usage.
     *
     * @return void
     */
    public function test_memory_usage_with_caching(): void
    {
        // Arrange: Create test data
        $this->seedTestUsers(100);

        // Test without caching
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);

        $tableWithoutCache = $this->createTableBuilder();
        $tableWithoutCache->render();

        $memoryWithoutCache = memory_get_usage(true);
        $memoryUsedWithoutCache = ($memoryWithoutCache - $memoryBefore) / 1024 / 1024;

        unset($tableWithoutCache);
        gc_collect_cycles();

        // Test with caching
        $memoryBefore = memory_get_usage(true);

        $tableWithCache = $this->createTableBuilder();
        if (method_exists($tableWithCache, 'cache')) {
            $tableWithCache->cache(300); // 5 minutes
        }
        $tableWithCache->render();

        $memoryWithCache = memory_get_usage(true);
        $memoryUsedWithCache = ($memoryWithCache - $memoryBefore) / 1024 / 1024;

        // Store metrics
        $this->metrics['memory_without_cache'] = $memoryUsedWithoutCache;
        $this->metrics['memory_with_cache'] = $memoryUsedWithCache;
        $this->metrics['cache_memory_overhead'] = $memoryUsedWithCache - $memoryUsedWithoutCache;

        // Log metrics
        $this->logMetric('Memory Without Cache', $memoryUsedWithoutCache, 'MB');
        $this->logMetric('Memory With Cache', $memoryUsedWithCache, 'MB');
        $this->logMetric('Cache Memory Overhead', $memoryUsedWithCache - $memoryUsedWithoutCache, 'MB');

        // Assert: Both should be under threshold
        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $memoryUsedWithoutCache,
            'Memory without cache should be under threshold'
        );

        $this->assertLessThan(
            self::MEMORY_THRESHOLD_MB,
            $memoryUsedWithCache,
            'Memory with cache should be under threshold'
        );

        // Assert: Cache overhead should be reasonable (< 50% increase)
        $cacheOverheadPercent = (($memoryUsedWithCache - $memoryUsedWithoutCache) / max($memoryUsedWithoutCache, 0.01)) * 100;
        $this->assertLessThan(
            50.0,
            abs($cacheOverheadPercent),
            sprintf(
                'Cache memory overhead (%.2f%%) should be less than 50%%',
                abs($cacheOverheadPercent)
            )
        );
    }

    /**
     * Create a table builder instance configured for performance testing.
     *
     * @return TableBuilder
     */
    protected function createTableBuilder(): TableBuilder
    {
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setEngine('datatables');
        $table->setModel(new TestUser());
        $table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'created_at:Created',
        ]);
        $table->format();

        return $table;
    }

    /**
     * Seed test users.
     *
     * @param int $count Number of users to create
     * @return void
     */
    protected function seedTestUsers(int $count): void
    {
        $batchSize = 100;
        $batches = ceil($count / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $users = [];
            $start = $batch * $batchSize;
            $end = min($start + $batchSize, $count);

            for ($i = $start; $i < $end; $i++) {
                $users[] = [
                    'name' => 'Test User ' . $i,
                    'email' => 'user' . $i . '@example.com',
                    'password' => password_hash('password', PASSWORD_BCRYPT),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            // Batch insert for better performance
            TestUser::insert($users);
        }
    }

    /**
     * Log a performance metric.
     *
     * @param string $name Metric name
     * @param float $value Metric value
     * @param string $unit Metric unit
     * @return void
     */
    protected function logMetric(string $name, float $value, string $unit): void
    {
        if (config('app.debug')) {
            echo sprintf("\n[METRIC] %s: %.2f %s", $name, $value, $unit);
        }
    }

    /**
     * Log all performance metrics.
     *
     * @return void
     */
    protected function logPerformanceMetrics(): void
    {
        if (empty($this->metrics)) {
            return;
        }

        echo "\n\n=== Memory Usage Performance Metrics ===\n";

        foreach ($this->metrics as $key => $value) {
            if (is_array($value)) {
                echo sprintf("%s:\n", str_replace('_', ' ', ucwords($key, '_')));
                foreach ($value as $subKey => $subValue) {
                    if (is_numeric($subValue)) {
                        echo sprintf("  %s: %.2f\n", $subKey, $subValue);
                    } else {
                        echo sprintf("  %s: %s\n", $subKey, $subValue);
                    }
                }
            } else {
                if (is_numeric($value)) {
                    echo sprintf("%s: %.2f\n", str_replace('_', ' ', ucwords($key, '_')), $value);
                } else {
                    echo sprintf("%s: %s\n", str_replace('_', ' ', ucwords($key, '_')), $value);
                }
            }
        }

        echo "=======================================\n\n";
    }
}

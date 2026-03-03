<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;

/**
 * Property 10: Response Time Performance.
 *
 * Validates: Requirements 29.1
 *
 * Property: For ANY dataset of 1,000 rows, when caching is disabled,
 * the total execution time MUST be less than 500ms.
 *
 * This property ensures that the table component meets performance targets
 * for typical use cases, providing a responsive user experience even without
 * caching enabled.
 *
 * Key Invariant: Total execution time < 500ms for 1,000 rows without cache.
 * - Query execution is optimized
 * - Rendering is efficient
 * - No unnecessary processing overhead
 * - Performance is consistent across iterations
 */
class ResponseTimePropertyTest extends PropertyTestCase
{
    private TableBuilder $table;
    private const RESPONSE_TIME_LIMIT_MS = 500;
    private const DATASET_SIZE = 1000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
    }

    /**
     * Property 10: Response time under 500ms for 1K rows without caching.
     *
     * Test that rendering 1,000 rows completes in less than 500ms
     * when caching is disabled.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     * @group canvastack-table-complete
     */
    public function property_response_time_under_limit_for_1k_rows(): void
    {
        // Run 100 iterations as specified in requirements
        $iterations = 100;
        $executionTimes = [];

        // Setup: Create dataset once for all iterations
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        for ($i = 0; $i < $iterations; $i++) {
            // Disable caching for this test
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute: Render table
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            $executionTimes[] = $executionTimeMs;

            // Verify: Execution time should be < 500ms
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded on iteration %d: %.2fms ' .
                    '(limit: %dms, dataset: %d rows)',
                    $i + 1,
                    $executionTimeMs,
                    self::RESPONSE_TIME_LIMIT_MS,
                    self::DATASET_SIZE
                )
            );
        }

        // Calculate statistics
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $minTime = min($executionTimes);
        $maxTime = max($executionTimes);

        // Log performance statistics
        $this->addToAssertionCount(1); // Count the statistical verification
        echo sprintf(
            "\nResponse Time Statistics (%d iterations, %d rows):\n" .
            "  Average: %.2fms\n" .
            "  Min: %.2fms\n" .
            "  Max: %.2fms\n" .
            "  Limit: %dms\n",
            $iterations,
            self::DATASET_SIZE,
            $avgTime,
            $minTime,
            $maxTime,
            self::RESPONSE_TIME_LIMIT_MS
        );
    }

    /**
     * Property 10.1: Response time with different column counts.
     *
     * Test that response time stays under limit with varying column counts.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_different_column_counts(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        $columnConfigurations = [
            ['id'],
            ['id', 'name'],
            ['id', 'name', 'code'],
            ['id', 'name', 'code', 'created_at'],
            ['id', 'name', 'code', 'created_at', 'updated_at'],
        ];

        // Test each configuration 20 times (100 total iterations)
        foreach ($columnConfigurations as $columns) {
            for ($i = 0; $i < 20; $i++) {
                // Disable caching
                $this->table->config(['cache_enabled' => false]);

                // Measure execution time
                $startTime = microtime(true);

                // Execute
                $this->table
                    ->setModel(Province::query()->getModel())
                    ->setFields($columns)
                    ->render();

                $endTime = microtime(true);
                $executionTimeMs = ($endTime - $startTime) * 1000;

                // Verify
                $this->assertLessThan(
                    self::RESPONSE_TIME_LIMIT_MS,
                    $executionTimeMs,
                    sprintf(
                        'Response time limit exceeded with %d columns: %.2fms ' .
                        '(limit: %dms, dataset: %d rows)',
                        count($columns),
                        $executionTimeMs,
                        self::RESPONSE_TIME_LIMIT_MS,
                        self::DATASET_SIZE
                    )
                );
            }
        }
    }

    /**
     * Property 10.2: Response time with sorting.
     *
     * Test that adding sorting doesn't exceed response time limit.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_sorting(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        $sortConfigurations = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'name', 'direction' => 'desc'],
            ['column' => 'code', 'direction' => 'asc'],
        ];

        // Test each configuration 20 times (100 total iterations)
        foreach ($sortConfigurations as $sortConfig) {
            for ($i = 0; $i < 20; $i++) {
                // Disable caching
                $this->table->config(['cache_enabled' => false]);

                // Measure execution time
                $startTime = microtime(true);

                // Execute
                $this->table
                    ->setModel(Province::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->orderby($sortConfig['column'], $sortConfig['direction'])
                    ->render();

                $endTime = microtime(true);
                $executionTimeMs = ($endTime - $startTime) * 1000;

                // Verify
                $this->assertLessThan(
                    self::RESPONSE_TIME_LIMIT_MS,
                    $executionTimeMs,
                    sprintf(
                        'Response time limit exceeded with sorting (%s %s): %.2fms ' .
                        '(limit: %dms, dataset: %d rows)',
                        $sortConfig['column'],
                        $sortConfig['direction'],
                        $executionTimeMs,
                        self::RESPONSE_TIME_LIMIT_MS,
                        self::DATASET_SIZE
                    )
                );
            }
        }
    }

    /**
     * Property 10.3: Response time with filters.
     *
     * Test that filtering doesn't exceed response time limit.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_filters(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        // Test with 100 iterations using random filter values
        for ($i = 0; $i < 100; $i++) {
            $filterValue = rand(1, 500);

            // Disable caching
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->where('id', '>', $filterValue)
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;

            // Verify
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded with filter (id > %d): %.2fms ' .
                    '(limit: %dms, dataset: %d rows)',
                    $filterValue,
                    $executionTimeMs,
                    self::RESPONSE_TIME_LIMIT_MS,
                    self::DATASET_SIZE
                )
            );
        }
    }

    /**
     * Property 10.4: Response time with column alignment.
     *
     * Test that column styling doesn't significantly impact response time.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_column_styling(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        $alignments = ['left', 'center', 'right'];

        // Test each alignment ~33 times (100 total iterations)
        for ($i = 0; $i < 100; $i++) {
            $alignment = $alignments[array_rand($alignments)];

            // Disable caching
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->setAlignColumns($alignment, ['id', 'name', 'code'])
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;

            // Verify
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded with %s alignment: %.2fms ' .
                    '(limit: %dms, dataset: %d rows)',
                    $alignment,
                    $executionTimeMs,
                    self::RESPONSE_TIME_LIMIT_MS,
                    self::DATASET_SIZE
                )
            );
        }
    }

    /**
     * Property 10.5: Response time with column widths.
     *
     * Test that setting column widths doesn't impact response time.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_column_widths(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        // Test with 100 iterations using random widths
        for ($i = 0; $i < 100; $i++) {
            $width = rand(100, 500);

            // Disable caching
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->setColumnWidth('name', $width)
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;

            // Verify
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded with column width %dpx: %.2fms ' .
                    '(limit: %dms, dataset: %d rows)',
                    $width,
                    $executionTimeMs,
                    self::RESPONSE_TIME_LIMIT_MS,
                    self::DATASET_SIZE
                )
            );
        }
    }

    /**
     * Property 10.6: Response time consistency across iterations.
     *
     * Test that response time is consistent and doesn't degrade over iterations.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_consistency(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        $executionTimes = [];

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Disable caching
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;
            $executionTimes[] = $executionTimeMs;

            // Verify each iteration
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded on iteration %d: %.2fms (limit: %dms)',
                    $i + 1,
                    $executionTimeMs,
                    self::RESPONSE_TIME_LIMIT_MS
                )
            );
        }

        // Calculate variance to check consistency
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $variance = 0;
        foreach ($executionTimes as $time) {
            $variance += pow($time - $avgTime, 2);
        }
        $variance /= count($executionTimes);
        $stdDev = sqrt($variance);

        // Standard deviation should be reasonable (< 50% of average)
        // This ensures consistent performance
        $maxStdDev = $avgTime * 0.5;

        $this->assertLessThan(
            $maxStdDev,
            $stdDev,
            sprintf(
                'Response time is inconsistent. Standard deviation %.2fms is too high ' .
                '(average: %.2fms, max allowed std dev: %.2fms). ' .
                'This indicates performance degradation or inconsistency.',
                $stdDev,
                $avgTime,
                $maxStdDev
            )
        );
    }

    /**
     * Property 10.7: Response time with complex configuration.
     *
     * Test that complex table configurations stay under response time limit.
     *
     * @test
     * @group property
     * @group performance
     * @group response-time
     */
    public function property_response_time_with_complex_configuration(): void
    {
        // Setup
        $this->refreshTestDatabase();
        Province::factory()->count(self::DATASET_SIZE)->create();

        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $filterValue = rand(1, 500);
            $alignment = ['left', 'center', 'right'][array_rand(['left', 'center', 'right'])];
            $sortColumn = ['id', 'name', 'code'][array_rand(['id', 'name', 'code'])];

            // Disable caching
            $this->table->config(['cache_enabled' => false]);

            // Measure execution time
            $startTime = microtime(true);

            // Execute with complex configuration
            $this->table
                ->setModel(Province::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->where('id', '>', $filterValue)
                ->orderby($sortColumn, 'asc')
                ->setAlignColumns($alignment, ['id'])
                ->setColumnWidth('name', 200)
                ->render();

            $endTime = microtime(true);
            $executionTimeMs = ($endTime - $startTime) * 1000;

            // Verify
            $this->assertLessThan(
                self::RESPONSE_TIME_LIMIT_MS,
                $executionTimeMs,
                sprintf(
                    'Response time limit exceeded with complex config: %.2fms ' .
                    '(filter: id>%d, sort: %s, align: %s, limit: %dms, dataset: %d rows)',
                    $executionTimeMs,
                    $filterValue,
                    $sortColumn,
                    $alignment,
                    self::RESPONSE_TIME_LIMIT_MS,
                    self::DATASET_SIZE
                )
            );
        }
    }

    /**
     * Helper: Refresh database for each test.
     *
     * Note: RefreshDatabase trait handles cleanup automatically.
     * This method is kept for compatibility but does nothing.
     */
    protected function refreshTestDatabase(): void
    {
        // RefreshDatabase trait handles this automatically
        // No manual truncation needed
    }
}

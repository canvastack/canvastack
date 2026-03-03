<?php

namespace Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Support\ModelInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Property 27: Soft Delete Detection Performance.
 *
 * For any Eloquent model, soft delete detection should complete within 10ms per model.
 *
 * **Validates: Requirements 8.15, 13.8**
 */
class SoftDeleteDetectionPerformancePropertyTest extends TestCase
{
    protected ModelInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspector = new ModelInspector();
    }

    /**
     * @test
     * @group property
     * @group performance
     */
    public function property_soft_delete_detection_completes_within_10ms()
    {
        // Warm up: Create a dummy model to initialize any static caches
        $warmupModel = $this->createModelWithSoftDeletes();
        $this->inspector->usesSoftDeletes($warmupModel);

        // Clear cache to ensure fair test
        Cache::flush();

        // Create test models with and without SoftDeletes
        $modelsWithSoftDeletes = $this->createModelsWithSoftDeletes(10);
        $modelsWithoutSoftDeletes = $this->createModelsWithoutSoftDeletes(10);
        $allModels = array_merge($modelsWithSoftDeletes, $modelsWithoutSoftDeletes);

        // Test each model
        foreach ($allModels as $model) {
            $startTime = microtime(true);

            // Perform detection
            $result = $this->inspector->usesSoftDeletes($model);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Assert: Detection should complete within 10ms
            $this->assertLessThan(
                10,
                $duration,
                sprintf(
                    'Soft delete detection took %.2fms for %s, exceeding 10ms limit',
                    $duration,
                    get_class($model)
                )
            );

            // Verify correctness
            $expectedResult = in_array($model, $modelsWithSoftDeletes, true);
            $this->assertSame(
                $expectedResult,
                $result,
                'Detection result should be correct'
            );
        }
    }

    /**
     * @test
     * @group property
     * @group performance
     */
    public function property_cached_detection_is_faster_than_first_call()
    {
        $model = $this->createModelWithSoftDeletes();

        // Clear cache first
        Cache::flush();

        // First call (uncached)
        $startTime1 = microtime(true);
        $result1 = $this->inspector->usesSoftDeletes($model);
        $endTime1 = microtime(true);
        $duration1 = ($endTime1 - $startTime1) * 1000;

        // Second call (cached)
        $startTime2 = microtime(true);
        $result2 = $this->inspector->usesSoftDeletes($model);
        $endTime2 = microtime(true);
        $duration2 = ($endTime2 - $startTime2) * 1000;

        // Assert: Cached call should be faster or equal
        $this->assertLessThanOrEqual(
            $duration1,
            $duration2,
            sprintf(
                'Cached detection (%.2fms) should be faster than or equal to first call (%.2fms)',
                $duration2,
                $duration1
            )
        );

        // Both should return same result
        $this->assertSame($result1, $result2);

        // Both should complete within 10ms
        $this->assertLessThan(10, $duration1, 'First call should complete within 10ms');
        $this->assertLessThan(10, $duration2, 'Cached call should complete within 10ms');
    }

    /**
     * @test
     * @group property
     * @group performance
     */
    public function property_custom_column_detection_completes_within_10ms()
    {
        $models = [
            $this->createModelWithCustomColumn('removed_at'),
            $this->createModelWithCustomColumn('archived_at'),
            $this->createModelWithCustomColumn('trashed_at'),
        ];

        foreach ($models as $model) {
            $startTime = microtime(true);

            // Detect custom column
            $column = $this->inspector->getSoftDeleteColumn($model);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Assert: Detection should complete within 10ms
            $this->assertLessThan(
                10,
                $duration,
                sprintf(
                    'Custom column detection took %.2fms, exceeding 10ms limit',
                    $duration
                )
            );

            // Verify column is detected
            $this->assertNotEmpty($column);
        }
    }

    /**
     * @test
     * @group property
     * @group performance
     */
    public function property_bulk_detection_maintains_performance()
    {
        // Create 50 models
        $models = array_merge(
            $this->createModelsWithSoftDeletes(25),
            $this->createModelsWithoutSoftDeletes(25)
        );

        $totalTime = 0;
        $detectionCount = 0;

        foreach ($models as $model) {
            $startTime = microtime(true);
            $this->inspector->usesSoftDeletes($model);
            $endTime = microtime(true);

            $duration = ($endTime - $startTime) * 1000;
            $totalTime += $duration;
            $detectionCount++;

            // Each individual detection should be under 10ms
            $this->assertLessThan(
                10,
                $duration,
                sprintf('Detection #%d took %.2fms, exceeding 10ms limit', $detectionCount, $duration)
            );
        }

        // Average should also be well under 10ms
        $averageTime = $totalTime / $detectionCount;
        $this->assertLessThan(
            10,
            $averageTime,
            sprintf('Average detection time %.2fms exceeds 10ms limit', $averageTime)
        );
    }

    /**
     * Create models with SoftDeletes trait.
     */
    protected function createModelsWithSoftDeletes(int $count = 1): array
    {
        $models = [];
        for ($i = 0; $i < $count; $i++) {
            $models[] = new class () extends Model {
                use SoftDeletes;
            };
        }

        return $models;
    }

    /**
     * Create models without SoftDeletes trait.
     */
    protected function createModelsWithoutSoftDeletes(int $count = 1): array
    {
        $models = [];
        for ($i = 0; $i < $count; $i++) {
            $models[] = new class () extends Model {
                // No SoftDeletes trait
            };
        }

        return $models;
    }

    /**
     * Create a single model with SoftDeletes.
     */
    protected function createModelWithSoftDeletes(): Model
    {
        return new class () extends Model {
            use SoftDeletes;
        };
    }

    /**
     * Create a model with custom soft delete column.
     */
    protected function createModelWithCustomColumn(string $columnName): Model
    {
        return new class ($columnName) extends Model {
            use SoftDeletes;

            protected string $customColumn;

            public function __construct(string $columnName = '', array $attributes = [])
            {
                $this->customColumn = $columnName;
                parent::__construct($attributes);
            }

            public function getDeletedAtColumn()
            {
                return $this->customColumn;
            }
        };
    }
}

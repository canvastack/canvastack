<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Property 25: Soft Delete Detection.
 *
 * Universal Property: For any Eloquent model M, if M uses the SoftDeletes trait,
 * then FormBuilder.setModel(M) SHALL correctly detect and configure soft delete support.
 *
 * Validates: Requirements 8.1
 */
class SoftDeleteDetectionPropertyTest extends TestCase
{
    protected FormBuilder $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();

        $this->formBuilder = new FormBuilder($fieldFactory, $validationCache);
        Cache::flush();
    }

    /**
     * Property: Models with SoftDeletes trait are always detected.
     *
     * @test
     */
    public function property_models_with_soft_deletes_are_detected()
    {
        // Generate test cases: models with SoftDeletes trait
        $testCases = [
            new PropertyTestModelWithSoftDeletes(),
            new PropertyTestModelWithSoftDeletesAndTimestamps(),
            new PropertyTestModelWithSoftDeletesAndGuarded(),
            new PropertyTestChildModelWithSoftDeletes(),
        ];

        foreach ($testCases as $model) {
            Cache::flush();

            $this->formBuilder->setModel($model);

            // Property: Detection must be correct
            $this->assertTrue(
                $this->formBuilder->usesSoftDeletes(),
                'Failed to detect SoftDeletes trait in ' . get_class($model)
            );

            // Property: Soft delete column must be set
            $this->assertNotNull(
                $this->formBuilder->getSoftDeleteColumn(),
                'Soft delete column not set for ' . get_class($model)
            );
        }
    }

    /**
     * Property: Models without SoftDeletes trait are never detected as soft deletable.
     *
     * @test
     */
    public function property_models_without_soft_deletes_are_not_detected()
    {
        // Generate test cases: models without SoftDeletes trait
        $testCases = [
            new PropertyTestModelWithoutSoftDeletes(),
            new PropertyTestModelWithTimestamps(),
            new PropertyTestModelWithGuarded(),
        ];

        foreach ($testCases as $model) {
            Cache::flush();

            $this->formBuilder->setModel($model);

            // Property: Detection must be correct
            $this->assertFalse(
                $this->formBuilder->usesSoftDeletes(),
                'Incorrectly detected SoftDeletes trait in ' . get_class($model)
            );

            // Property: Soft delete column must be null
            $this->assertNull(
                $this->formBuilder->getSoftDeleteColumn(),
                'Soft delete column should be null for ' . get_class($model)
            );
        }
    }

    /**
     * Property: Detection is idempotent (same result on repeated calls).
     *
     * @test
     */
    public function property_detection_is_idempotent()
    {
        $model = new PropertyTestModelWithSoftDeletes();

        // First detection
        $this->formBuilder->setModel($model);
        $firstResult = $this->formBuilder->usesSoftDeletes();
        $firstColumn = $this->formBuilder->getSoftDeleteColumn();

        // Second detection (should use cache)
        $this->formBuilder->setModel($model);
        $secondResult = $this->formBuilder->usesSoftDeletes();
        $secondColumn = $this->formBuilder->getSoftDeleteColumn();

        // Property: Results must be identical
        $this->assertEquals($firstResult, $secondResult);
        $this->assertEquals($firstColumn, $secondColumn);
    }

    /**
     * Property: Detection works for inherited traits.
     *
     * @test
     */
    public function property_detection_works_for_inherited_traits()
    {
        $parentModel = new PropertyTestParentModelWithSoftDeletes();
        $childModel = new PropertyTestChildModelWithSoftDeletes();

        // Parent detection
        $this->formBuilder->setModel($parentModel);
        $parentHasSoftDeletes = $this->formBuilder->usesSoftDeletes();

        Cache::flush();

        // Child detection
        $this->formBuilder->setModel($childModel);
        $childHasSoftDeletes = $this->formBuilder->usesSoftDeletes();

        // Property: Both parent and child must be detected
        $this->assertTrue($parentHasSoftDeletes);
        $this->assertTrue($childHasSoftDeletes);
    }

    /**
     * Property: Custom soft delete columns are correctly identified.
     *
     * @test
     */
    public function property_custom_soft_delete_columns_are_identified()
    {
        $testCases = [
            ['model' => new PropertyTestModelWithCustomColumn1(), 'expected' => 'removed_at'],
            ['model' => new PropertyTestModelWithCustomColumn2(), 'expected' => 'archived_at'],
            ['model' => new PropertyTestModelWithCustomColumn3(), 'expected' => 'trashed_at'],
        ];

        foreach ($testCases as $testCase) {
            Cache::flush();

            $this->formBuilder->setModel($testCase['model']);

            // Property: Custom column must be detected
            $this->assertEquals(
                $testCase['expected'],
                $this->formBuilder->getSoftDeleteColumn(),
                'Failed to detect custom soft delete column for ' . get_class($testCase['model'])
            );
        }
    }

    /**
     * Property: Detection completes within performance target (< 10ms).
     *
     * @test
     */
    public function property_detection_meets_performance_target()
    {
        $model = new PropertyTestModelWithSoftDeletes();
        $iterations = 10;
        $totalTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush();

            $startTime = microtime(true);
            $this->formBuilder->setModel($model);
            $this->formBuilder->usesSoftDeletes();
            $endTime = microtime(true);

            $totalTime += ($endTime - $startTime) * 1000; // Convert to milliseconds
        }

        $averageTime = $totalTime / $iterations;

        // Property: Average detection time must be < 10ms
        $this->assertLessThan(
            10,
            $averageTime,
            "Average detection time {$averageTime}ms exceeds 10ms target"
        );
    }

    /**
     * Property: Null model is handled gracefully.
     *
     * @test
     */
    public function property_null_model_is_handled_gracefully()
    {
        $this->formBuilder->setModel(null);

        // Property: No exceptions thrown
        $this->assertFalse($this->formBuilder->usesSoftDeletes());
        $this->assertNull($this->formBuilder->getSoftDeleteColumn());
        $this->assertFalse($this->formBuilder->isSoftDeleted());
    }

    /**
     * Property: Detection result is cached correctly.
     *
     * @test
     */
    public function property_detection_result_is_cached()
    {
        $model = new PropertyTestModelWithSoftDeletes();
        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";

        // Before detection
        $this->assertFalse(Cache::has($cacheKey));

        // After detection
        $this->formBuilder->setModel($model);
        $this->formBuilder->usesSoftDeletes();

        // Property: Cache must exist
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));
    }
}

// Property Test Models

class PropertyTestModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class PropertyTestModelWithSoftDeletesAndTimestamps extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = true;
}

class PropertyTestModelWithSoftDeletesAndGuarded extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = ['id'];

    public $timestamps = false;
}

class PropertyTestModelWithoutSoftDeletes extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class PropertyTestModelWithTimestamps extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = true;
}

class PropertyTestModelWithGuarded extends Model
{
    protected $table = 'test_models';

    protected $guarded = ['id'];

    public $timestamps = false;
}

class PropertyTestParentModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class PropertyTestChildModelWithSoftDeletes extends PropertyTestParentModelWithSoftDeletes
{
    //
}

class PropertyTestModelWithCustomColumn1 extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
    public const DELETED_AT = 'removed_at';

    public function getDeletedAtColumn()
    {
        return 'removed_at';
    }
}

class PropertyTestModelWithCustomColumn2 extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
    public const DELETED_AT = 'archived_at';

    public function getDeletedAtColumn()
    {
        return 'archived_at';
    }
}

class PropertyTestModelWithCustomColumn3 extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
    public const DELETED_AT = 'trashed_at';

    public function getDeletedAtColumn()
    {
        return 'trashed_at';
    }
}

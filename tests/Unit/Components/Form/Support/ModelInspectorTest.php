<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Support;

use Canvastack\Canvastack\Components\Form\Support\ModelInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ModelInspectorTest extends TestCase
{
    protected ModelInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspector = new ModelInspector();
        Cache::flush();
    }

    /** @test */
    public function it_detects_soft_deletes_trait()
    {
        $model = new ModelWithSoftDeletes();

        $result = $this->inspector->usesSoftDeletes($model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_detects_absence_of_soft_deletes_trait()
    {
        $model = new ModelWithoutSoftDeletes();

        $result = $this->inspector->usesSoftDeletes($model);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_non_eloquent_objects()
    {
        $object = new \stdClass();

        $result = $this->inspector->usesSoftDeletes($object);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_caches_soft_delete_detection_results()
    {
        $model = new ModelWithSoftDeletes();
        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";

        // First call - should cache
        $this->inspector->usesSoftDeletes($model);

        // Verify cache exists
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));

        // Second call - should use cache
        $result = $this->inspector->usesSoftDeletes($model);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_detects_trait_in_parent_class()
    {
        $model = new ChildModelWithSoftDeletes();

        $result = $this->inspector->usesSoftDeletes($model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_gets_default_soft_delete_column()
    {
        $model = new ModelWithSoftDeletes();

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertEquals('deleted_at', $column);
    }

    /** @test */
    public function it_gets_custom_soft_delete_column()
    {
        $model = new ModelWithCustomSoftDeleteColumn();

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertEquals('removed_at', $column);
    }

    /** @test */
    public function it_returns_default_column_for_non_eloquent_objects()
    {
        $object = new \stdClass();

        $column = $this->inspector->getSoftDeleteColumn($object);

        $this->assertEquals('deleted_at', $column);
    }

    /** @test */
    public function it_clears_cache_for_specific_model()
    {
        $model = new ModelWithSoftDeletes();
        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";

        // Cache the result
        $this->inspector->usesSoftDeletes($model);
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        $this->inspector->clearCache($modelClass);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_allows_custom_cache_duration()
    {
        $customDuration = 600; // 10 minutes

        $this->inspector->setCacheDuration($customDuration);

        $model = new ModelWithSoftDeletes();
        $this->inspector->usesSoftDeletes($model);

        // Cache should exist (we can't easily test the exact duration without mocking)
        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_detects_trait_used_by_trait()
    {
        $model = new ModelWithNestedTrait();

        $result = $this->inspector->hasTrait($model, SoftDeletes::class);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_models_with_multiple_traits()
    {
        $model = new ModelWithMultipleTraits();

        $hasSoftDeletes = $this->inspector->usesSoftDeletes($model);
        $hasCustomTrait = $this->inspector->hasTrait($model, CustomTrait::class);

        $this->assertTrue($hasSoftDeletes);
        $this->assertTrue($hasCustomTrait);
    }

    /** @test */
    public function it_performs_detection_within_10ms()
    {
        $model = new ModelWithSoftDeletes();

        $startTime = microtime(true);
        $this->inspector->usesSoftDeletes($model);
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(10, $executionTime, "Detection took {$executionTime}ms, expected < 10ms");
    }
}

// Test Models

class ModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';
}

class ModelWithoutSoftDeletes extends Model
{
    protected $table = 'test_models';
}

class ParentModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';
}

class ChildModelWithSoftDeletes extends ParentModelWithSoftDeletes
{
    //
}

class ModelWithCustomSoftDeleteColumn extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    public const DELETED_AT = 'removed_at';

    public function getDeletedAtColumn()
    {
        return 'removed_at';
    }
}

trait CustomTrait
{
    //
}

trait TraitWithSoftDeletes
{
    use SoftDeletes;
}

class ModelWithNestedTrait extends Model
{
    use TraitWithSoftDeletes;

    protected $table = 'test_models';
}

class ModelWithMultipleTraits extends Model
{
    use SoftDeletes;
    use CustomTrait;

    protected $table = 'test_models';
}

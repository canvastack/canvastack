<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class SoftDeleteIntegrationTest extends TestCase
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

    /** @test */
    public function it_detects_soft_deletes_when_setting_model()
    {
        $model = new TestModelWithSoftDeletes();

        $this->formBuilder->setModel($model);

        $this->assertTrue($this->formBuilder->usesSoftDeletes());
    }

    /** @test */
    public function it_does_not_detect_soft_deletes_for_regular_models()
    {
        $model = new TestModelWithoutSoftDeletes();

        $this->formBuilder->setModel($model);

        $this->assertFalse($this->formBuilder->usesSoftDeletes());
    }

    /** @test */
    public function it_stores_soft_delete_column_name()
    {
        $model = new TestModelWithSoftDeletes();

        $this->formBuilder->setModel($model);

        $this->assertEquals('deleted_at', $this->formBuilder->getSoftDeleteColumn());
    }

    /** @test */
    public function it_stores_custom_soft_delete_column_name()
    {
        $model = new TestModelWithCustomSoftDeleteColumn();

        $this->formBuilder->setModel($model);

        $this->assertEquals('removed_at', $this->formBuilder->getSoftDeleteColumn());
    }

    /** @test */
    public function it_detects_soft_deleted_model()
    {
        $model = new TestModelWithSoftDeletes();
        $model->deleted_at = now();

        $this->formBuilder->setModel($model);

        $this->assertTrue($this->formBuilder->isSoftDeleted());
    }

    /** @test */
    public function it_detects_non_soft_deleted_model()
    {
        $model = new TestModelWithSoftDeletes();
        $model->deleted_at = null;

        $this->formBuilder->setModel($model);

        $this->assertFalse($this->formBuilder->isSoftDeleted());
    }

    /** @test */
    public function it_populates_fields_with_soft_deleted_model_data()
    {
        $model = new TestModelWithSoftDeletes();
        $model->id = 1;
        $model->name = 'Test Name';
        $model->email = 'test@example.com';
        $model->deleted_at = now();

        $this->formBuilder->setModel($model);
        $this->formBuilder->text('name', 'Name', $model->name);
        $this->formBuilder->email('email', 'Email', $model->email);

        $fields = $this->formBuilder->getFields();

        $this->assertCount(2, $fields);
        $this->assertEquals('Test Name', $fields['name']->getValue());
        $this->assertEquals('test@example.com', $fields['email']->getValue());
    }

    /** @test */
    public function it_handles_model_with_null_soft_delete_column()
    {
        $model = new TestModelWithSoftDeletes();
        $model->deleted_at = null;

        $this->formBuilder->setModel($model);

        $this->assertTrue($this->formBuilder->usesSoftDeletes());
        $this->assertFalse($this->formBuilder->isSoftDeleted());
    }

    /** @test */
    public function it_returns_null_soft_delete_column_for_non_soft_delete_models()
    {
        $model = new TestModelWithoutSoftDeletes();

        $this->formBuilder->setModel($model);

        $this->assertNull($this->formBuilder->getSoftDeleteColumn());
    }

    /** @test */
    public function it_handles_null_model()
    {
        $this->formBuilder->setModel(null);

        $this->assertFalse($this->formBuilder->usesSoftDeletes());
        $this->assertFalse($this->formBuilder->isSoftDeleted());
        $this->assertNull($this->formBuilder->getSoftDeleteColumn());
    }

    /** @test */
    public function it_caches_soft_delete_detection()
    {
        $model = new TestModelWithSoftDeletes();
        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";

        $this->formBuilder->setModel($model);

        // Verify cache exists
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));
    }

    /** @test */
    public function it_performs_soft_delete_detection_within_10ms()
    {
        $model = new TestModelWithSoftDeletes();

        $startTime = microtime(true);
        $this->formBuilder->setModel($model);
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(10, $executionTime, "Detection took {$executionTime}ms, expected < 10ms");
    }

    /** @test */
    public function it_handles_model_inheritance_with_soft_deletes()
    {
        $model = new ChildTestModelWithSoftDeletes();

        $this->formBuilder->setModel($model);

        $this->assertTrue($this->formBuilder->usesSoftDeletes());
    }

    /** @test */
    public function it_provides_access_to_model_inspector()
    {
        $inspector = $this->formBuilder->getModelInspector();

        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Form\Support\ModelInspector::class, $inspector);
    }
}

// Test Models

class TestModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class TestModelWithoutSoftDeletes extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class TestModelWithCustomSoftDeleteColumn extends Model
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

class ParentTestModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class ChildTestModelWithSoftDeletes extends ParentTestModelWithSoftDeletes
{
    //
}

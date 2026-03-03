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
 * Property 26: Soft Delete Query Modification.
 *
 * Universal Property: For any Eloquent model M with SoftDeletes trait,
 * when FormBuilder binds M, it SHALL configure queries to include soft-deleted records
 * using withTrashed() scope, allowing form to populate fields with deleted record data.
 *
 * Validates: Requirements 8.3
 */
class SoftDeleteQueryModificationPropertyTest extends TestCase
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
     * Property: Soft delete configuration is set when model uses SoftDeletes.
     *
     * @test
     */
    public function property_soft_delete_configuration_is_set_for_soft_deletable_models()
    {
        $testCases = [
            new QueryTestModelWithSoftDeletes(),
            new QueryTestChildModelWithSoftDeletes(),
            new QueryTestModelWithCustomSoftDeleteColumn(),
        ];

        foreach ($testCases as $model) {
            Cache::flush();

            $this->formBuilder->setModel($model);

            // Property: Soft delete configuration must be enabled
            $this->assertTrue(
                $this->formBuilder->usesSoftDeletes(),
                'Soft delete configuration not set for ' . get_class($model)
            );
        }
    }

    /**
     * Property: Soft delete configuration is not set for regular models.
     *
     * @test
     */
    public function property_soft_delete_configuration_is_not_set_for_regular_models()
    {
        $testCases = [
            new QueryTestModelWithoutSoftDeletes(),
            new QueryTestRegularModel(),
        ];

        foreach ($testCases as $model) {
            Cache::flush();

            $this->formBuilder->setModel($model);

            // Property: Soft delete configuration must not be enabled
            $this->assertFalse(
                $this->formBuilder->usesSoftDeletes(),
                'Soft delete configuration incorrectly set for ' . get_class($model)
            );
        }
    }

    /**
     * Property: Form can populate fields with soft-deleted model data.
     *
     * @test
     */
    public function property_form_populates_fields_with_soft_deleted_model_data()
    {
        $testData = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35],
        ];

        foreach ($testData as $data) {
            $model = new QueryTestModelWithSoftDeletes();
            $model->name = $data['name'];
            $model->email = $data['email'];
            $model->age = $data['age'];
            $model->deleted_at = now(); // Mark as soft deleted

            $this->formBuilder->clear();
            $this->formBuilder->setModel($model);
            $this->formBuilder->text('name', 'Name', $model->name);
            $this->formBuilder->email('email', 'Email', $model->email);
            $this->formBuilder->number('age', 'Age', $model->age);

            $fields = $this->formBuilder->getFields();

            // Property: All fields must be populated with soft-deleted model data
            $this->assertEquals($data['name'], $fields['name']->getValue());
            $this->assertEquals($data['email'], $fields['email']->getValue());
            $this->assertEquals($data['age'], $fields['age']->getValue());
        }
    }

    /**
     * Property: Soft deleted status is correctly identified.
     *
     * @test
     */
    public function property_soft_deleted_status_is_correctly_identified()
    {
        // Test case 1: Soft deleted model
        $deletedModel = new QueryTestModelWithSoftDeletes();
        $deletedModel->deleted_at = now();

        $this->formBuilder->setModel($deletedModel);

        // Property: Must be identified as soft deleted
        $this->assertTrue(
            $this->formBuilder->isSoftDeleted(),
            'Failed to identify soft deleted model'
        );

        // Test case 2: Non-deleted model
        $activeModel = new QueryTestModelWithSoftDeletes();
        $activeModel->deleted_at = null;

        $this->formBuilder->setModel($activeModel);

        // Property: Must not be identified as soft deleted
        $this->assertFalse(
            $this->formBuilder->isSoftDeleted(),
            'Incorrectly identified active model as soft deleted'
        );
    }

    /**
     * Property: Custom soft delete columns are handled correctly.
     *
     * @test
     */
    public function property_custom_soft_delete_columns_are_handled()
    {
        $model = new QueryTestModelWithCustomSoftDeleteColumn();
        $model->removed_at = now();

        $this->formBuilder->setModel($model);

        // Property: Custom column must be detected
        $this->assertEquals('removed_at', $this->formBuilder->getSoftDeleteColumn());

        // Property: Soft deleted status must be correct
        $this->assertTrue($this->formBuilder->isSoftDeleted());
    }

    /**
     * Property: Configuration persists across multiple field additions.
     *
     * @test
     */
    public function property_configuration_persists_across_field_additions()
    {
        $model = new QueryTestModelWithSoftDeletes();
        $model->deleted_at = now();

        $this->formBuilder->setModel($model);

        // Add multiple fields
        $this->formBuilder->text('field1', 'Field 1');
        $this->assertTrue($this->formBuilder->usesSoftDeletes());

        $this->formBuilder->text('field2', 'Field 2');
        $this->assertTrue($this->formBuilder->usesSoftDeletes());

        $this->formBuilder->text('field3', 'Field 3');
        $this->assertTrue($this->formBuilder->usesSoftDeletes());

        // Property: Configuration must persist
        $this->assertTrue($this->formBuilder->isSoftDeleted());
    }

    /**
     * Property: Changing model updates configuration.
     *
     * @test
     */
    public function property_changing_model_updates_configuration()
    {
        // Start with soft deletable model
        $softDeleteModel = new QueryTestModelWithSoftDeletes();
        $this->formBuilder->setModel($softDeleteModel);
        $this->assertTrue($this->formBuilder->usesSoftDeletes());

        // Change to regular model
        $regularModel = new QueryTestModelWithoutSoftDeletes();
        $this->formBuilder->setModel($regularModel);

        // Property: Configuration must be updated
        $this->assertFalse($this->formBuilder->usesSoftDeletes());
    }

    /**
     * Property: Null model clears configuration.
     *
     * @test
     */
    public function property_null_model_clears_configuration()
    {
        // Start with soft deletable model
        $model = new QueryTestModelWithSoftDeletes();
        $this->formBuilder->setModel($model);
        $this->assertTrue($this->formBuilder->usesSoftDeletes());

        // Set to null
        $this->formBuilder->setModel(null);

        // Property: Configuration must be cleared
        $this->assertFalse($this->formBuilder->usesSoftDeletes());
        $this->assertNull($this->formBuilder->getSoftDeleteColumn());
        $this->assertFalse($this->formBuilder->isSoftDeleted());
    }

    /**
     * Property: Configuration works with model inheritance.
     *
     * @test
     */
    public function property_configuration_works_with_inheritance()
    {
        $parentModel = new QueryTestParentModelWithSoftDeletes();
        $parentModel->deleted_at = now();

        $this->formBuilder->setModel($parentModel);
        $parentIsSoftDeleted = $this->formBuilder->isSoftDeleted();

        $childModel = new QueryTestChildModelWithSoftDeletes();
        $childModel->deleted_at = now();

        $this->formBuilder->setModel($childModel);
        $childIsSoftDeleted = $this->formBuilder->isSoftDeleted();

        // Property: Both parent and child must work correctly
        $this->assertTrue($parentIsSoftDeleted);
        $this->assertTrue($childIsSoftDeleted);
    }

    /**
     * Property: Multiple models can be processed sequentially.
     *
     * @test
     */
    public function property_multiple_models_can_be_processed_sequentially()
    {
        $models = [
            ['model' => new QueryTestModelWithSoftDeletes(), 'expectSoftDelete' => true],
            ['model' => new QueryTestModelWithoutSoftDeletes(), 'expectSoftDelete' => false],
            ['model' => new QueryTestModelWithCustomSoftDeleteColumn(), 'expectSoftDelete' => true],
            ['model' => new QueryTestRegularModel(), 'expectSoftDelete' => false],
        ];

        foreach ($models as $testCase) {
            $this->formBuilder->setModel($testCase['model']);

            // Property: Each model must be configured correctly
            $this->assertEquals(
                $testCase['expectSoftDelete'],
                $this->formBuilder->usesSoftDeletes(),
                'Incorrect configuration for ' . get_class($testCase['model'])
            );
        }
    }
}

// Query Test Models

class QueryTestModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class QueryTestModelWithoutSoftDeletes extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class QueryTestRegularModel extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class QueryTestModelWithCustomSoftDeleteColumn extends Model
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

class QueryTestParentModelWithSoftDeletes extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class QueryTestChildModelWithSoftDeletes extends QueryTestParentModelWithSoftDeletes
{
    //
}

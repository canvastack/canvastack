<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\BaseModel;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModelTest extends TestCase
{
    public function test_model_uses_correct_table_name(): void
    {
        $model = new TestModel();

        $this->assertEquals('test_models', $model->getTable());
    }

    public function test_model_with_custom_table_name(): void
    {
        $model = new CustomTableModel();

        $this->assertEquals('custom_table', $model->getTable());
    }

    public function test_active_scope_filters_active_records(): void
    {
        $query = TestModel::query()->active();

        $sql = $query->toSql();
        $this->assertStringContainsString('where', strtolower($sql));
        $this->assertStringContainsString('status', strtolower($sql));
    }

    public function test_inactive_scope_filters_inactive_records(): void
    {
        $query = TestModel::query()->inactive();

        $sql = $query->toSql();
        $this->assertStringContainsString('where', strtolower($sql));
        $this->assertStringContainsString('status', strtolower($sql));
    }

    public function test_latest_scope_orders_by_created_at_desc(): void
    {
        $query = TestModel::query()->latest();

        $sql = $query->toSql();
        $this->assertStringContainsString('order by', strtolower($sql));
        $this->assertStringContainsString('created_at', strtolower($sql));
        $this->assertStringContainsString('desc', strtolower($sql));
    }

    public function test_oldest_scope_orders_by_created_at_asc(): void
    {
        $query = TestModel::query()->oldest();

        $sql = $query->toSql();
        $this->assertStringContainsString('order by', strtolower($sql));
        $this->assertStringContainsString('created_at', strtolower($sql));
        $this->assertStringContainsString('asc', strtolower($sql));
    }

    public function test_uses_soft_deletes_returns_false_when_not_using_soft_deletes(): void
    {
        $model = new TestModel();

        $this->assertFalse($model->usesSoftDeletes());
    }

    public function test_uses_soft_deletes_returns_true_when_using_soft_deletes(): void
    {
        $model = new SoftDeleteModel();

        $this->assertTrue($model->usesSoftDeletes());
    }

    public function test_get_fillable_attributes_returns_fillable_array(): void
    {
        $model = new TestModel();

        $this->assertEquals(['name', 'email', 'status'], $model->getFillableAttributes());
    }

    public function test_get_hidden_attributes_returns_hidden_array(): void
    {
        $model = new TestModel();

        $this->assertEquals(['password'], $model->getHiddenAttributes());
    }

    public function test_is_attribute_fillable_returns_true_for_fillable_attribute(): void
    {
        $model = new TestModel();

        $this->assertTrue($model->isAttributeFillable('name'));
        $this->assertTrue($model->isAttributeFillable('email'));
    }

    public function test_is_attribute_fillable_returns_false_for_non_fillable_attribute(): void
    {
        $model = new TestModel();

        $this->assertFalse($model->isAttributeFillable('id'));
    }
}

/**
 * Test Model for testing base model functionality.
 */
class TestModel extends BaseModel
{
    protected $fillable = ['name', 'email', 'status'];

    protected $hidden = ['password'];
}

/**
 * Custom Table Model for testing custom table names.
 */
class CustomTableModel extends BaseModel
{
    protected $table = 'custom_table';
}

/**
 * Soft Delete Model for testing soft deletes.
 */
class SoftDeleteModel extends BaseModel
{
    use SoftDeletes;
}

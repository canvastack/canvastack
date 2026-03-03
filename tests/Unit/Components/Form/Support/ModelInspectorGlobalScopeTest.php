<?php

namespace Tests\Unit\Components\Form\Support;

use Canvastack\Canvastack\Components\Form\Support\ModelInspector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ModelInspector global scope support.
 *
 * Requirements: 8.12, 8.13
 */
class ModelInspectorGlobalScopeTest extends TestCase
{
    protected ModelInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspector = new ModelInspector();
    }

    /** @test */
    public function it_gets_custom_soft_delete_column_from_constant()
    {
        $model = new class () extends Model {
            use SoftDeletes;

            public const DELETED_AT = 'removed_at';
        };

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertSame('removed_at', $column);
    }

    /** @test */
    public function it_gets_soft_delete_column_from_method()
    {
        $model = new class () extends Model {
            use SoftDeletes;

            public function getDeletedAtColumn()
            {
                return 'archived_at';
            }
        };

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertSame('archived_at', $column);
    }

    /** @test */
    public function it_returns_default_deleted_at_column()
    {
        $model = new class () extends Model {
            use SoftDeletes;
        };

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertSame('deleted_at', $column);
    }

    /** @test */
    public function it_handles_model_without_soft_deletes()
    {
        $model = new class () extends Model {
            // No SoftDeletes trait
        };

        $column = $this->inspector->getSoftDeleteColumn($model);

        $this->assertSame('deleted_at', $column); // Returns default
    }

    /** @test */
    public function it_handles_non_model_object()
    {
        $object = new \stdClass();

        $column = $this->inspector->getSoftDeleteColumn($object);

        $this->assertSame('deleted_at', $column); // Returns default
    }

    /** @test */
    public function it_prioritizes_method_over_constant()
    {
        $model = new class () extends Model {
            use SoftDeletes;

            public const DELETED_AT = 'removed_at';

            public function getDeletedAtColumn()
            {
                return 'archived_at';
            }
        };

        $column = $this->inspector->getSoftDeleteColumn($model);

        // Method should take precedence
        $this->assertSame('archived_at', $column);
    }
}

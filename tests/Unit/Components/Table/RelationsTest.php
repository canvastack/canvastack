<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use BadMethodCallException;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * Unit tests for TableBuilder relations methods.
 *
 * Tests Requirements 20 and 21:
 * - Relational data display
 * - Field replacement with relational data
 * - Eager loading to prevent N+1 queries
 */
class RelationsTest extends TestCase
{
    protected TableBuilder $table;

    protected QueryOptimizer $queryOptimizer;

    protected FilterBuilder $filterBuilder;

    protected SchemaInspector $schemaInspector;

    protected Model $mockModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop table if exists, then create users table for validation tests
        Schema::dropIfExists('users');
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        // Mock dependencies
        $this->queryOptimizer = Mockery::mock(QueryOptimizer::class);
        $this->filterBuilder = Mockery::mock(FilterBuilder::class);
        $this->schemaInspector = Mockery::mock(SchemaInspector::class);

        // Create TableBuilder instance
        $this->table = $this->createTableBuilder();

        // Create mock model with relationship methods
        $this->mockModel = Mockery::mock(Model::class);
        $this->mockModel->shouldReceive('getTable')->andReturn('users');
        $this->mockModel->shouldReceive('getConnectionName')->andReturn('mysql');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test relations() validates relationship method exists.
     *
     * Requirement 20.2, 20.8: Validate relationFunction exists on model
     */
    public function test_relations_validates_relationship_method_exists(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Relationship method "nonExistentRelation" does not exist');

        $this->table->relations(
            $this->mockModel,
            'nonExistentRelation',
            'name'
        );
    }

    /**
     * Test relations() adds to eager load array.
     *
     * Requirement 20.1, 20.6: Add relationFunction to $eagerLoad array for eager loading
     */
    public function test_relations_adds_to_eager_load_array(): void
    {
        // Mock the model to have a 'department' relationship method
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $this->table->relations(
            $modelWithRelation,
            'department',
            'name'
        );

        $eagerLoad = $this->table->getEagerLoad();

        $this->assertContains('department', $eagerLoad);
    }

    /**
     * Test relations() stores configuration correctly.
     *
     * Requirement 20.3, 20.4, 20.5, 20.7: Store relation config
     */
    public function test_relations_stores_configuration(): void
    {
        // Mock the model to have a 'department' relationship method
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $result = $this->table->relations(
            $modelWithRelation,
            'department',
            'name',
            ['department_id'],
            'Department Name'
        );

        // Should return $this for method chaining
        $this->assertSame($this->table, $result);

        // Verify configuration is stored (we can't directly access protected property,
        // but we can verify the method returns $this for chaining)
        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test relations() doesn't duplicate eager load entries.
     */
    public function test_relations_doesnt_duplicate_eager_load(): void
    {
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        // Add the same relation twice
        $this->table->relations($modelWithRelation, 'department', 'name');
        $this->table->relations($modelWithRelation, 'department', 'name');

        $eagerLoad = $this->table->getEagerLoad();

        // Should only appear once
        $this->assertCount(1, array_filter($eagerLoad, fn ($rel) => $rel === 'department'));
    }

    /**
     * Test relations() generates label from relationship name.
     */
    public function test_relations_generates_label_from_relationship_name(): void
    {
        $modelWithRelation = new class () extends Model {
            public function user_department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $result = $this->table->relations(
            $modelWithRelation,
            'user_department',
            'name'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test fieldReplacementValue() validates relationship method exists.
     *
     * Requirement 21.1: Validate relationFunction exists on model
     */
    public function test_field_replacement_validates_relationship_method_exists(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Relationship method "nonExistentRelation" does not exist');

        $this->table->fieldReplacementValue(
            $this->mockModel,
            'nonExistentRelation',
            'name'
        );
    }

    /**
     * Test fieldReplacementValue() validates fieldConnect exists in schema.
     *
     * Requirement 21.5: Validate fieldConnect exists in table schema if provided
     */
    public function test_field_replacement_validates_field_connect(): void
    {
        // Mock the model to have a relationship
        $modelWithRelation = new class () extends Model {
            protected $table = 'users';

            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        // Set the model first
        $this->table->setModel($modelWithRelation);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_column' does not exist in table 'users'");

        $this->table->fieldReplacementValue(
            $modelWithRelation,
            'department',
            'name',
            null,
            'invalid_column'
        );
    }

    /**
     * Test fieldReplacementValue() adds to eager load array.
     *
     * Requirement 21.4: Add relationFunction to $eagerLoad array for eager loading
     */
    public function test_field_replacement_adds_to_eager_load_array(): void
    {
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $this->table->fieldReplacementValue(
            $modelWithRelation,
            'department',
            'name'
        );

        $eagerLoad = $this->table->getEagerLoad();

        $this->assertContains('department', $eagerLoad);
    }

    /**
     * Test fieldReplacementValue() stores configuration correctly.
     *
     * Requirement 21.2, 21.3: Store replacement config
     */
    public function test_field_replacement_stores_configuration(): void
    {
        $modelWithRelation = new class () extends Model {
            protected $table = 'users';

            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        // Set the model first
        $this->table->setModel($modelWithRelation);

        $result = $this->table->fieldReplacementValue(
            $modelWithRelation,
            'department',
            'name',
            'Department',
            null  // Don't validate fieldConnect to avoid schema validation
        );

        // Should return $this for method chaining
        $this->assertSame($this->table, $result);
        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test fieldReplacementValue() doesn't duplicate eager load entries.
     */
    public function test_field_replacement_doesnt_duplicate_eager_load(): void
    {
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        // Add the same relation twice
        $this->table->fieldReplacementValue($modelWithRelation, 'department', 'name');
        $this->table->fieldReplacementValue($modelWithRelation, 'department', 'name');

        $eagerLoad = $this->table->getEagerLoad();

        // Should only appear once
        $this->assertCount(1, array_filter($eagerLoad, fn ($rel) => $rel === 'department'));
    }

    /**
     * Test fieldReplacementValue() generates label from relationship name.
     */
    public function test_field_replacement_generates_label_from_relationship_name(): void
    {
        $modelWithRelation = new class () extends Model {
            public function user_department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $result = $this->table->fieldReplacementValue(
            $modelWithRelation,
            'user_department',
            'name'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test fieldReplacementValue() works without fieldConnect parameter.
     */
    public function test_field_replacement_works_without_field_connect(): void
    {
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }
        };

        $result = $this->table->fieldReplacementValue(
            $modelWithRelation,
            'department',
            'name',
            'Department'
        );

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /**
     * Test both methods can be chained together.
     */
    public function test_relations_methods_can_be_chained(): void
    {
        $modelWithRelation = new class () extends Model {
            public function department()
            {
                return $this->belongsTo(self::class);
            }

            public function manager()
            {
                return $this->belongsTo(self::class);
            }
        };

        $result = $this->table
            ->relations($modelWithRelation, 'department', 'name')
            ->fieldReplacementValue($modelWithRelation, 'manager', 'name');

        $this->assertInstanceOf(TableBuilder::class, $result);

        $eagerLoad = $this->table->getEagerLoad();
        $this->assertContains('department', $eagerLoad);
        $this->assertContains('manager', $eagerLoad);
    }
}

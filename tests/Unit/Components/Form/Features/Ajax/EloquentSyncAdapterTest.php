<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\EloquentSyncAdapter;
use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures\TestCity;
use Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures\TestProvince;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Unit tests for EloquentSyncAdapter.
 *
 * Tests the conversion of Eloquent models and relationships to SQL queries
 * for use with Ajax Sync functionality.
 */
class EloquentSyncAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentSyncAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new EloquentSyncAdapter();
        Cache::flush();

        // Create test tables
        Schema::create('test_provinces', function ($table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('test_cities', function ($table) {
            $table->id();
            $table->foreignId('province_id')->constrained('test_provinces')->onDelete('cascade');
            $table->string('name');
            $table->string('city_name')->nullable();
            $table->integer('city_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_cities');
        Schema::dropIfExists('test_provinces');
        parent::tearDown();
    }

    /** @test */
    public function it_converts_belongs_to_relationship_to_sql(): void
    {
        $config = [
            'display' => 'name',
            'value' => 'id',
            'constraints' => [],
        ];

        $result = $this->adapter->modelToSql(
            TestCity::class,
            'province',
            $config
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('bindings', $result);
        $this->assertArrayHasKey('foreign_key', $result);
        $this->assertIsString($result['sql']);
        $this->assertStringContainsString('select', strtolower($result['sql']));
        $this->assertEquals('province_id', $result['foreign_key']);
    }

    /** @test */
    public function it_converts_has_many_relationship_to_sql(): void
    {
        $config = [
            'display' => 'name',
            'value' => 'id',
            'constraints' => [],
        ];

        $result = $this->adapter->modelToSql(
            TestProvince::class,
            'cities',
            $config
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('foreign_key', $result);
        $this->assertStringContainsString('select', strtolower($result['sql']));
    }

    /** @test */
    public function it_throws_exception_for_non_existent_model_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->adapter->modelToSql(
            'NonExistentModel',
            'relationship',
            []
        );
    }

    /** @test */
    public function it_throws_exception_for_non_eloquent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not an Eloquent model');

        $this->adapter->modelToSql(
            \stdClass::class,
            'relationship',
            []
        );
    }

    /** @test */
    public function it_throws_exception_for_non_existent_relationship(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist on model');

        $this->adapter->modelToSql(
            TestCity::class,
            'nonExistentRelationship',
            []
        );
    }

    /** @test */
    public function it_converts_closure_to_sql(): void
    {
        $closure = function ($provinceId) {
            return TestCity::where('province_id', $provinceId)
                ->where('active', true)
                ->orderBy('name')
                ->select(['id', 'name']);
        };

        $result = $this->adapter->closureToSql($closure, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('bindings', $result);
        $this->assertStringContainsString('select', strtolower($result['sql']));
        $this->assertStringContainsString('where', strtolower($result['sql']));
        $this->assertContains(1, $result['bindings']);
        $this->assertContains(true, $result['bindings']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_closure_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return a query builder instance');

        $closure = function ($value) {
            return 'invalid';
        };

        $this->adapter->closureToSql($closure);
    }

    /** @test */
    public function it_detects_foreign_key_from_belongs_to_relationship(): void
    {
        $model = new TestCity();
        $relation = $model->province();

        $foreignKey = $this->adapter->detectForeignKey($relation);

        $this->assertEquals('province_id', $foreignKey);
    }

    /** @test */
    public function it_detects_foreign_key_from_has_many_relationship(): void
    {
        $model = new TestProvince();
        $relation = $model->cities();

        $foreignKey = $this->adapter->detectForeignKey($relation);

        $this->assertEquals('province_id', $foreignKey);
    }

    /** @test */
    public function it_detects_relationship_type_belongs_to(): void
    {
        $type = $this->adapter->detectRelationshipType(
            TestCity::class,
            'province'
        );

        $this->assertEquals('belongsTo', $type);
    }

    /** @test */
    public function it_detects_relationship_type_has_many(): void
    {
        $type = $this->adapter->detectRelationshipType(
            TestProvince::class,
            'cities'
        );

        $this->assertEquals('hasMany', $type);
    }

    /** @test */
    public function it_generates_parameterized_query_from_eloquent_builder(): void
    {
        $builder = TestCity::where('province_id', 1)
            ->where('active', true)
            ->select(['id', 'name']);

        $result = $this->adapter->generateParameterizedQuery($builder);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('bindings', $result);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertCount(2, $result['bindings']);
    }

    /** @test */
    public function it_applies_display_and_value_columns_to_query(): void
    {
        $config = [
            'display' => 'city_name',
            'value' => 'city_id',
            'constraints' => [],
        ];

        $result = $this->adapter->modelToSql(
            TestCity::class,
            'province',
            $config
        );

        $sql = strtolower($result['sql']);
        $this->assertStringContainsString('city_id', $sql);
        $this->assertStringContainsString('city_name', $sql);
    }

    /** @test */
    public function it_applies_where_constraints_to_query(): void
    {
        $config = [
            'display' => 'name',
            'value' => 'id',
            'constraints' => [
                [
                    'type' => 'where',
                    'column' => 'active',
                    'operator' => '=',
                    'value' => true,
                ],
            ],
        ];

        $result = $this->adapter->modelToSql(
            TestCity::class,
            'province',
            $config
        );

        $this->assertStringContainsString('where', strtolower($result['sql']));
        $this->assertContains(true, $result['bindings']);
    }

    /** @test */
    public function it_applies_order_by_to_query(): void
    {
        $config = [
            'display' => 'name',
            'value' => 'id',
            'constraints' => [],
            'orderBy' => 'name',
            'orderDirection' => 'asc',
        ];

        $result = $this->adapter->modelToSql(
            TestCity::class,
            'province',
            $config
        );

        $sql = strtolower($result['sql']);
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    /** @test */
    public function it_caches_relationship_metadata(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn('belongsTo');

        $type = $this->adapter->detectRelationshipType(
            TestCity::class,
            'province'
        );

        $this->assertEquals('belongsTo', $type);
    }

    /** @test */
    public function it_configures_eager_loading_for_query(): void
    {
        $query = TestCity::query();
        $relations = ['province', 'district'];

        $result = $this->adapter->configureEagerLoading($query, $relations);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
        // Note: We can't easily test if eager loading was applied without executing the query
        // This test verifies the method returns the correct type
    }

    /** @test */
    public function it_handles_closure_with_eloquent_builder_return(): void
    {
        $closure = function ($value) {
            return TestCity::where('province_id', $value);
        };

        $result = $this->adapter->closureToSql($closure, 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertContains(5, $result['bindings']);
    }

    /** @test */
    public function it_handles_closure_with_query_builder_return(): void
    {
        $closure = function ($value) {
            return \DB::table('test_cities')
                ->where('province_id', $value)
                ->select(['id', 'name']);
        };

        $result = $this->adapter->closureToSql($closure, 3);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertContains(3, $result['bindings']);
    }
}

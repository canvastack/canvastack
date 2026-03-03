<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures\TestCity;
use Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax\Fixtures\TestProvince;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Integration tests for Eloquent Sync functionality.
 *
 * Tests the complete workflow of Eloquent-based Ajax Sync with real models,
 * relationships, and database interactions.
 */
class EloquentSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected FormBuilder $form;

    protected function setUp(): void
    {
        parent::setUp();

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
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Seed test data
        $this->seedTestData();

        // Initialize FormBuilder
        $this->form = app(FormBuilder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clear any cached relationships to prevent stale references
        Cache::flush();

        // Clear Eloquent model boot cache
        TestCity::clearBootedModels();
        TestProvince::clearBootedModels();

        // Drop tables before parent tearDown
        if (Schema::hasTable('test_cities')) {
            Schema::dropIfExists('test_cities');
        }
        if (Schema::hasTable('test_provinces')) {
            Schema::dropIfExists('test_provinces');
        }

        parent::tearDown();
    }

    protected function seedTestData(): void
    {
        // Create provinces
        $province1 = TestProvince::create(['name' => 'Province 1', 'active' => true]);
        $province2 = TestProvince::create(['name' => 'Province 2', 'active' => true]);

        // Create cities
        TestCity::create(['province_id' => $province1->id, 'name' => 'City 1A', 'active' => true]);
        TestCity::create(['province_id' => $province1->id, 'name' => 'City 1B', 'active' => true]);
        TestCity::create(['province_id' => $province2->id, 'name' => 'City 2A', 'active' => true]);
        TestCity::create(['province_id' => $province2->id, 'name' => 'City 2B', 'active' => false]);
    }

    /** @test */
    public function it_creates_sync_relationship_with_eloquent_model(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id');

        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Form\Features\Ajax\EloquentSyncBuilder::class, $builder);
        $this->assertEquals('province_id', $builder->getSourceField());
        $this->assertEquals(TestCity::class, $builder->getModel());
        $this->assertEquals('province', $builder->getRelationship());
    }

    /** @test */
    public function it_builds_sync_relationship_with_belongs_to(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id');

        $config = $builder->build();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('sql', $config);
        $this->assertArrayHasKey('foreign_key', $config);
        $this->assertEquals('province_id', $config['foreign_key']);
        $this->assertEquals('province_id', $config['source']);
    }

    /** @test */
    public function it_creates_sync_relationship_with_query_closure(): void
    {
        $builder = $this->form->syncWith('province_id', function ($provinceId) {
            return TestCity::where('province_id', $provinceId)
                ->where('active', true)
                ->orderBy('name')
                ->select(['id', 'name']);
        });

        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Form\Features\Ajax\EloquentSyncBuilder::class, $builder);
        $this->assertInstanceOf(\Closure::class, $builder->getModel());
    }

    /** @test */
    public function it_builds_sync_relationship_with_query_closure(): void
    {
        $builder = $this->form->syncWith('province_id', function ($provinceId) {
            return TestCity::where('province_id', $provinceId)
                ->where('active', true)
                ->orderBy('name')
                ->select(['id', 'name']);
        });

        $config = $builder->build();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('sql', $config);
        $this->assertStringContainsString('where', strtolower($config['sql']));
        $this->assertStringContainsString('order by', strtolower($config['sql']));
    }

    /** @test */
    public function it_creates_sync_relationship_using_parent_model(): void
    {
        $builder = $this->form->syncWithRelationship('province_id', TestProvince::class, 'cities')
            ->display('name')
            ->value('id');

        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Form\Features\Ajax\EloquentSyncBuilder::class, $builder);
        $this->assertEquals(TestProvince::class, $builder->getModel());
        $this->assertEquals('cities', $builder->getRelationship());
    }

    /** @test */
    public function it_applies_where_constraints_to_eloquent_sync(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->where('active', true);

        $constraints = $builder->getConstraints();

        $this->assertCount(1, $constraints);
        $this->assertEquals('where', $constraints[0]['type']);
        $this->assertEquals('active', $constraints[0]['column']);
        $this->assertTrue($constraints[0]['value']);
    }

    /** @test */
    public function it_applies_order_by_to_eloquent_sync(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->orderBy('name', 'desc');

        $config = $builder->build();

        $sql = strtolower($config['sql']);
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    /** @test */
    public function it_sets_selected_value_for_eloquent_sync(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->selected(5);

        $this->assertEquals(5, $builder->getSelected());
    }

    /** @test */
    public function it_registers_eloquent_sync_with_ajax_sync_component(): void
    {
        $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->build();

        $ajaxSync = $this->form->getAjaxSync();
        $relationships = $ajaxSync->getRelationships();

        $this->assertNotEmpty($relationships);
        $this->assertCount(1, $relationships);
    }

    /** @test */
    public function it_generates_javascript_for_eloquent_sync(): void
    {
        $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->build();

        $script = $this->form->getAjaxSync()->renderScript();

        $this->assertNotEmpty($script);
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringContainsString('province_id', $script);
        $this->assertStringContainsString('fetch', $script);
    }

    /** @test */
    public function it_handles_multiple_eloquent_sync_relationships(): void
    {
        // Province -> City (first relationship)
        $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->target('city_id')
            ->display('name')
            ->value('id')
            ->build();

        // Another Province -> City (second relationship with different target)
        $this->form->syncWith('parent_province_id', TestCity::class)
            ->relationship('province')
            ->target('child_city_id')
            ->display('name')
            ->value('id')
            ->build();

        $relationships = $this->form->getAjaxSync()->getRelationships();

        $this->assertCount(2, $relationships);
    }

    /** @test */
    public function it_supports_fluent_chaining_with_multiple_constraints(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->where('active', true)
            ->whereNotNull('name')
            ->orderBy('name', 'asc')
            ->selected(10);

        $constraints = $builder->getConstraints();

        $this->assertCount(2, $constraints);
        $this->assertEquals('where', $constraints[0]['type']);
        $this->assertEquals('whereNotNull', $constraints[1]['type']);
        $this->assertEquals(10, $builder->getSelected());
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_sql_based_sync(): void
    {
        // Old SQL-based sync should still work
        $this->form->sync(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM test_cities WHERE province_id = ?',
            null
        );

        $relationships = $this->form->getAjaxSync()->getRelationships();

        $this->assertNotEmpty($relationships);
        $this->assertCount(1, $relationships);
    }

    /** @test */
    public function it_can_mix_sql_and_eloquent_sync_in_same_form(): void
    {
        // SQL-based sync
        $this->form->sync(
            'country_id',
            'province_id',
            'id',
            'name',
            'SELECT id, name FROM test_provinces WHERE country_id = ?',
            null
        );

        // Eloquent-based sync
        $this->form->syncWith('province_id', TestCity::class)
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->build();

        $relationships = $this->form->getAjaxSync()->getRelationships();

        $this->assertCount(2, $relationships);
    }

    /** @test */
    public function it_handles_error_when_relationship_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist on model');

        $this->form->syncWith('province_id', TestCity::class)
            ->relationship('nonExistentRelationship')
            ->display('name')
            ->value('id')
            ->build();
    }

    /** @test */
    public function it_handles_error_when_model_class_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->form->syncWith('province_id', 'NonExistentModel')
            ->relationship('province')
            ->display('name')
            ->value('id')
            ->build();
    }

    /** @test */
    public function it_auto_generates_target_field_name(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class);

        // Should auto-generate target field name
        $targetField = $builder->getTargetField();

        $this->assertNotEmpty($targetField);
        $this->assertStringContainsString('_id', $targetField);
    }

    /** @test */
    public function it_allows_explicit_target_field_name(): void
    {
        $builder = $this->form->syncWith('province_id', TestCity::class)
            ->target('custom_city_id')
            ->relationship('province');

        $this->assertEquals('custom_city_id', $builder->getTargetField());
    }
}

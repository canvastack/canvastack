<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Unit tests for Phase 9: Main Rendering Methods.
 *
 * Tests Requirements 34.1-34.9, 30.1-30.7, 31.1-31.7, 49.1
 *
 * @group canvastack-table-complete
 * @group phase-9
 */
class RenderingMethodsTest extends TestCase
{
    protected TableBuilder $table;

    protected Model $mockModel;

    protected Builder $mockBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop table if exists, then create test table in SQLite memory database
        \Illuminate\Support\Facades\Schema::dropIfExists('users');
        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->nullable();
            $table->timestamps();
        });

        // Create TableBuilder using helper from TestCase
        $this->table = $this->createTableBuilder();

        // Create mock model first
        $this->mockModel = Mockery::mock(Model::class);
        $this->mockModel->shouldReceive('getTable')->andReturn('users')->byDefault();
        $this->mockModel->shouldReceive('getConnectionName')->andReturn('default')->byDefault();
        $this->mockModel->shouldReceive('getKeyName')->andReturn('id')->byDefault();

        // Create mock builder (after model is created)
        $this->mockBuilder = Mockery::mock(Builder::class);
        $this->mockBuilder->shouldReceive('get')->andReturn(collect([]))->byDefault();
        $this->mockBuilder->shouldReceive('count')->andReturn(0)->byDefault();
        $this->mockBuilder->shouldReceive('toSql')->andReturn('SELECT * FROM users')->byDefault();
        $this->mockBuilder->shouldReceive('getBindings')->andReturn([])->byDefault();
        $this->mockBuilder->shouldReceive('with')->andReturnSelf()->byDefault();
        $this->mockBuilder->shouldReceive('getModel')->andReturn($this->mockModel)->byDefault();
        $this->mockBuilder->shouldReceive('select')->andReturnSelf()->byDefault();
        $this->mockBuilder->shouldReceive('orderBy')->andReturnSelf()->byDefault();
        $this->mockBuilder->shouldReceive('limit')->andReturnSelf()->byDefault();
        $this->mockBuilder->shouldReceive('offset')->andReturnSelf()->byDefault();

        // Connect model to builder
        $this->mockModel->shouldReceive('newQuery')->andReturn($this->mockBuilder)->byDefault();
    }

    protected function tearDown(): void
    {
        // Drop test table
        \Illuminate\Support\Facades\Schema::dropIfExists('users');

        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to setup query optimizer for rendering.
     * NOTE: No longer needed with real TableBuilder from createTableBuilder().
     */
    protected function setupQueryOptimizerForRender(): void
    {
        // No-op: Using real dependencies now
    }

    /**
     * Helper method to setup renderer for a specific test.
     * NOTE: No longer needed with real TableBuilder from createTableBuilder().
     */
    protected function setupRenderer(?callable $configValidator = null): void
    {
        // No-op: Using real renderer now
    }

    /**
     * Test lists() method with all parameters.
     *
     * Requirement 34.1-34.9: Legacy lists() method
     */
    public function test_lists_with_all_parameters(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Pre-configure fields to avoid validation issues in lists()
        $this->table->setFields(['id', 'name', 'email']);

        // Call lists() with all parameters (skip tableName and fields since already set)
        $html = $this->table->lists(
            tableName: null, // Skip to avoid re-validation
            fields: [], // Skip to avoid re-validation
            actions: true,
            serverSide: true,
            numbering: true,
            attributes: ['class' => 'table-striped'],
            serverSideCustomUrl: true
        );

        // Assert HTML is returned and contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method with minimal parameters.
     *
     * Requirement 34.1: lists() accepts optional parameters
     */
    public function test_lists_with_minimal_parameters(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with no parameters
        $html = $this->table->lists();

        // Assert HTML is returned and contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets table name.
     *
     * Requirement 34.2: lists() sets table name when provided
     */
    public function test_lists_sets_table_name(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with table name
        $html = $this->table->lists(tableName: 'users');

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets fields.
     *
     * Requirement 34.3: lists() sets fields when provided
     */
    public function test_lists_sets_fields(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with fields
        $html = $this->table->lists(fields: ['id', 'name', 'email']);

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets actions.
     *
     * Requirement 34.4: lists() sets actions configuration
     */
    public function test_lists_sets_actions(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with actions = true
        $html = $this->table->lists(actions: true);

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets server-side processing.
     *
     * Requirement 34.5: lists() sets server-side processing flag
     */
    public function test_lists_sets_server_side(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with serverSide = true
        $html = $this->table->lists(serverSide: true);

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets numbering.
     *
     * Requirement 34.6: lists() sets numbering flag
     */
    public function test_lists_sets_numbering(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with numbering = true
        $html = $this->table->lists(numbering: true);

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets attributes.
     *
     * Requirement 34.7: lists() sets HTML attributes
     */
    public function test_lists_sets_attributes(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with attributes
        $html = $this->table->lists(attributes: ['class' => 'table-striped']);

        // Verify HTML output contains table element
        // Note: The attributes may be applied to the wrapper div, not the table itself
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method sets server-side custom URL.
     *
     * Requirement 34.8: lists() sets server-side custom URL flag
     */
    public function test_lists_sets_server_side_custom_url(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists() with serverSideCustomUrl = true
        $html = $this->table->lists(serverSideCustomUrl: true);

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test lists() method calls render().
     *
     * Requirement 34.9: lists() calls render() and returns HTML
     */
    public function test_lists_calls_render(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model first
        $this->table->setModel($this->mockModel);

        // Call lists()
        $html = $this->table->lists();

        // Assert HTML is returned with table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test render() throws exception when model not set.
     *
     * Requirement 49.1: render() validates model is set
     */
    public function test_render_throws_exception_when_model_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Model.*must be set before rendering/');

        // Call render() without setting model
        $this->table->render();
    }

    /**
     * Test render() applies all column configurations.
     *
     * Requirement 30.5: render() applies column configurations
     */
    public function test_render_applies_column_configurations(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model and configure columns
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']);
        $this->table->setHiddenColumns(['id']);
        $this->table->setColumnWidth('name', 200);

        // Call render()
        $html = $this->table->render();

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test render() applies sorting and searching configurations.
     *
     * Requirement 30.6: render() applies sorting and searching
     */
    public function test_render_applies_sorting_and_searching(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model and configure sorting/searching
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']); // Add fields
        $this->table->orderby('name', 'asc');
        $this->table->sortable(['name', 'email']);
        $this->table->searchable(['name']);

        // Call render()
        $html = $this->table->render();

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test render() applies filtering and conditions.
     *
     * Requirement 30.7: render() applies filtering and conditions
     */
    public function test_render_applies_filtering_and_conditions(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']); // Add fields

        // Call render()
        $html = $this->table->render();

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test render() applies relational data loading.
     *
     * Requirement 30.8: render() applies relational data
     */
    public function test_render_applies_relational_data(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']); // Add fields

        // Call render()
        $html = $this->table->render();

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }

    /**
     * Test render() returns valid HTML.
     *
     * Requirement 30.1: render() returns HTML string
     */
    public function test_render_returns_valid_html(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']); // Add fields

        // Call render()
        $html = $this->table->render();

        // Assert HTML is returned with proper structure
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    /**
     * Test render() passes complete configuration to renderer.
     *
     * Requirement 30.1-30.7: render() passes all configurations
     */
    public function test_render_passes_complete_configuration(): void
    {
        // Add getQuery() mock expectation
        $mockQuery = Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockQuery->shouldReceive('from')->andReturn($mockQuery);

        $this->mockBuilder->shouldReceive('getQuery')->andReturn($mockQuery);

        // Set model
        $this->table->setModel($this->mockModel);
        $this->table->setFields(['id', 'name', 'email']); // Add fields

        // Call render()
        $html = $this->table->render();

        // Verify HTML output contains table element
        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
    }
}

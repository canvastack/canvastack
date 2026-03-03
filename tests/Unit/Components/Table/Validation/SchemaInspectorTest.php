<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Validation;

use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * SchemaInspectorTest - Unit tests for SchemaInspector.
 */
class SchemaInspectorTest extends TestCase
{
    protected SchemaInspector $schemaInspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaInspector = new SchemaInspector();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SchemaInspector::class, $this->schemaInspector);
    }

    /** @test */
    public function it_validates_existing_column(): void
    {
        // Create test table
        Schema::create('test_column_validation', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        // Should not throw exception for existing columns
        $this->schemaInspector->validateColumn('name', 'test_column_validation');
        $this->schemaInspector->validateColumn('email', 'test_column_validation');
        $this->schemaInspector->validateColumn('id', 'test_column_validation');

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_column_validation');
    }

    /** @test */
    public function it_throws_exception_for_non_existent_column(): void
    {
        // Create test table
        Schema::create('test_invalid_column', function ($table) {
            $table->id();
            $table->string('name');
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'non_existent_column' does not exist in table 'test_invalid_column'");

        $this->schemaInspector->validateColumn('non_existent_column', 'test_invalid_column');

        // Cleanup
        Schema::dropIfExists('test_invalid_column');
    }

    /** @test */
    public function it_provides_helpful_error_message_with_available_columns(): void
    {
        // Create test table
        Schema::create('test_helpful_error', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        try {
            $this->schemaInspector->validateColumn('invalid', 'test_helpful_error');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Available columns:', $e->getMessage());
            $this->assertStringContainsString('id', $e->getMessage());
            $this->assertStringContainsString('name', $e->getMessage());
            $this->assertStringContainsString('email', $e->getMessage());
        }

        // Cleanup
        Schema::dropIfExists('test_helpful_error');
    }

    /** @test */
    public function it_gets_table_columns(): void
    {
        // Create test table
        Schema::create('test_get_columns', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('age');
        });

        $columns = $this->schemaInspector->getTableColumns('test_get_columns');

        $this->assertIsArray($columns);
        $this->assertCount(4, $columns);
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('age', $columns);

        // Cleanup
        Schema::dropIfExists('test_get_columns');
    }

    /** @test */
    public function it_validates_column_with_custom_connection(): void
    {
        // Create test table
        Schema::create('test_column_connection', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Should not throw exception with null connection (default)
        $this->schemaInspector->validateColumn('name', 'test_column_connection', null);

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_column_connection');
    }

    /** @test */
    public function it_gets_columns_with_custom_connection(): void
    {
        // Create test table
        Schema::create('test_columns_connection', function ($table) {
            $table->id();
            $table->string('name');
        });

        $columns = $this->schemaInspector->getTableColumns('test_columns_connection', null);

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);

        // Cleanup
        Schema::dropIfExists('test_columns_connection');
    }

    /** @test */
    public function it_validates_existing_table(): void
    {
        // Create test table
        Schema::create('test_validation_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Should not throw exception
        $this->schemaInspector->validateTable('test_validation_table');

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_validation_table');
    }

    /** @test */
    public function it_throws_exception_for_non_existent_table(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table 'non_existent_table' does not exist in database");

        $this->schemaInspector->validateTable('non_existent_table');
    }

    /** @test */
    public function it_gets_table_schema(): void
    {
        // Create test table with specific columns
        Schema::create('test_schema_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('age');
        });

        $schema = $this->schemaInspector->getTableSchema('test_schema_table');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('id', $schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('email', $schema);
        $this->assertArrayHasKey('age', $schema);

        // Cleanup
        Schema::dropIfExists('test_schema_table');
    }

    /** @test */
    public function it_returns_column_types_in_schema(): void
    {
        // Create test table
        Schema::create('test_types_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->integer('count');
        });

        $schema = $this->schemaInspector->getTableSchema('test_types_table');

        // Check that each column has a type
        foreach ($schema as $column => $type) {
            $this->assertIsString($type);
            $this->assertNotEmpty($type);
        }

        // Cleanup
        Schema::dropIfExists('test_types_table');
    }

    /** @test */
    public function it_validates_table_with_custom_connection(): void
    {
        // Create test table on default connection
        Schema::create('test_connection_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Validate with null connection (default)
        $this->schemaInspector->validateTable('test_connection_table', null);

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_connection_table');
    }

    /** @test */
    public function it_gets_schema_with_custom_connection(): void
    {
        // Create test table
        Schema::create('test_schema_connection', function ($table) {
            $table->id();
            $table->string('name');
        });

        $schema = $this->schemaInspector->getTableSchema('test_schema_connection', null);

        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);

        // Cleanup
        Schema::dropIfExists('test_schema_connection');
    }
}

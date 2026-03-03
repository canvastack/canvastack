<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Validation;

use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * ColumnValidatorTest - Unit tests for ColumnValidator.
 */
class ColumnValidatorTest extends TestCase
{
    protected ColumnValidator $columnValidator;

    protected SchemaInspector $schemaInspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaInspector = new SchemaInspector();
        $this->columnValidator = new ColumnValidator($this->schemaInspector);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ColumnValidator::class, $this->columnValidator);
    }

    /** @test */
    public function it_validates_existing_column(): void
    {
        // Create test table
        Schema::create('test_validator_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        // Should not throw exception for existing columns
        $this->columnValidator->validate('name', 'test_validator_table');
        $this->columnValidator->validate('email', 'test_validator_table');
        $this->columnValidator->validate('id', 'test_validator_table');

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_validator_table');
    }

    /** @test */
    public function it_throws_exception_for_invalid_column(): void
    {
        // Create test table
        Schema::create('test_invalid_validator', function ($table) {
            $table->id();
            $table->string('name');
        });

        $this->expectException(InvalidArgumentException::class);

        $this->columnValidator->validate('invalid_column', 'test_invalid_validator');

        // Cleanup
        Schema::dropIfExists('test_invalid_validator');
    }

    /** @test */
    public function it_validates_multiple_columns(): void
    {
        // Create test table
        Schema::create('test_multiple_columns', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('age');
        });

        // Should not throw exception for all valid columns
        $this->columnValidator->validateMultiple(
            ['name', 'email', 'age'],
            'test_multiple_columns'
        );

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_multiple_columns');
    }

    /** @test */
    public function it_throws_exception_when_any_column_is_invalid(): void
    {
        // Create test table
        Schema::create('test_mixed_columns', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        $this->expectException(InvalidArgumentException::class);

        // Mix of valid and invalid columns
        $this->columnValidator->validateMultiple(
            ['name', 'invalid_column', 'email'],
            'test_mixed_columns'
        );

        // Cleanup
        Schema::dropIfExists('test_mixed_columns');
    }

    /** @test */
    public function it_returns_true_for_valid_column(): void
    {
        // Create test table
        Schema::create('test_is_valid_true', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->columnValidator->isValid('name', 'test_is_valid_true');

        $this->assertTrue($result);

        // Cleanup
        Schema::dropIfExists('test_is_valid_true');
    }

    /** @test */
    public function it_returns_false_for_invalid_column(): void
    {
        // Create test table
        Schema::create('test_is_valid_false', function ($table) {
            $table->id();
            $table->string('name');
        });

        $result = $this->columnValidator->isValid('invalid_column', 'test_is_valid_false');

        $this->assertFalse($result);

        // Cleanup
        Schema::dropIfExists('test_is_valid_false');
    }

    /** @test */
    public function it_validates_column_with_custom_connection(): void
    {
        // Create test table
        Schema::create('test_validator_connection', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Should not throw exception with null connection (default)
        $this->columnValidator->validate('name', 'test_validator_connection', null);

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_validator_connection');
    }

    /** @test */
    public function it_prevents_sql_injection_through_column_names(): void
    {
        // Create test table
        Schema::create('test_sql_injection', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Attempt SQL injection through column name
        $maliciousColumn = "name'; DROP TABLE test_sql_injection; --";

        $this->expectException(InvalidArgumentException::class);

        $this->columnValidator->validate($maliciousColumn, 'test_sql_injection');

        // Verify table still exists (injection was prevented)
        $this->assertTrue(Schema::hasTable('test_sql_injection'));

        // Cleanup
        Schema::dropIfExists('test_sql_injection');
    }

    /** @test */
    public function it_validates_empty_array_of_columns(): void
    {
        // Create test table
        Schema::create('test_empty_array', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Should not throw exception for empty array
        $this->columnValidator->validateMultiple([], 'test_empty_array');

        $this->assertTrue(true);

        // Cleanup
        Schema::dropIfExists('test_empty_array');
    }
}

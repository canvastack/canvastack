<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Property 2: Column Name Validation.
 *
 * Validates: Requirements 23.5, 25.1, 25.2, 49.2
 *
 * Property: For ALL column names that do not exist in the table schema,
 * methods that accept column names MUST throw InvalidArgumentException.
 *
 * This property ensures that only valid columns from the database schema
 * can be used, preventing SQL injection and unauthorized data access.
 */
class ColumnValidationPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    private array $validColumns = ['id', 'name', 'email', 'created_at', 'updated_at'];

    protected function setUp(): void
    {
        parent::setUp();

        // Use real Mantra users table
        $this->table = app(TableBuilder::class);

        // Check if users table exists, if not create test table
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        $this->table->setName('users');
    }

    protected function tearDown(): void
    {
        // Don't drop users table as it's from Mantra
        parent::tearDown();
    }

    /**
     * Property 2.1: setFields() validates all columns.
     *
     * @test
     * @group property
     * @group security
     * @group canvastack-table-complete
     */
    public function property_set_fields_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->setFields([$invalidColumn]);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.2: orderby() validates column.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_orderby_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->orderby($invalidColumn);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.3: sortable() validates columns.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_sortable_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->sortable([$invalidColumn]);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.4: searchable() validates columns.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_searchable_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->searchable([$invalidColumn]);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.5: setColumnWidth() validates column.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_set_column_width_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->setColumnWidth($invalidColumn, 100);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.6: setAlignColumns() validates columns.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_set_align_columns_rejects_invalid_columns(): void
    {
        $this->forAllExpectingException(
            Generator::invalidColumns($this->validColumns),
            function (string $invalidColumn) {
                $this->table->setAlignColumns('left', [$invalidColumn]);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 2.7: Valid columns are accepted.
     *
     * Test that valid columns do NOT throw exceptions.
     *
     * @test
     * @group property
     */
    public function property_valid_columns_are_accepted(): void
    {
        $this->forAll(
            Generator::elements($this->validColumns),
            function (string $validColumn) {
                try {
                    $this->table->setFields([$validColumn]);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            },
            100
        );
    }
}

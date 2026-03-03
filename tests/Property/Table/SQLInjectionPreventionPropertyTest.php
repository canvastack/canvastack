<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use InvalidArgumentException;

/**
 * Property 1: SQL Injection Prevention via Dangerous Statement Rejection.
 *
 * Validates: Requirements 23.3, 23.4
 *
 * Property: For ALL SQL queries containing dangerous statements (DROP, TRUNCATE,
 * DELETE, UPDATE, INSERT, ALTER), the query() method MUST throw InvalidArgumentException.
 *
 * This property ensures that the table component rejects all potentially dangerous
 * SQL statements, preventing SQL injection attacks that could modify or delete data.
 */
class SQLInjectionPreventionPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = app(TableBuilder::class);
    }

    /**
     * Property 1: SQL Injection Prevention.
     *
     * Test that ALL dangerous SQL statements are rejected.
     *
     * @test
     * @group property
     * @group security
     * @group canvastack-table-complete
     */
    public function property_rejects_all_dangerous_sql_statements(): void
    {
        $this->forAllExpectingException(
            Generator::dangerousSQL(),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.1: DROP statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_drop_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['DROP'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.2: TRUNCATE statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_truncate_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['TRUNCATE'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.3: DELETE statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_delete_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['DELETE'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.4: UPDATE statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_update_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['UPDATE'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.5: INSERT statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_insert_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['INSERT'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.6: ALTER statement rejection.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_alter_statements(): void
    {
        $this->forAllExpectingException(
            Generator::stringContaining(['ALTER'], 20, 200),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.7: Case-insensitive rejection.
     *
     * Test that dangerous statements are rejected regardless of case.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_dangerous_statements_case_insensitive(): void
    {
        $keywords = ['drop', 'DROP', 'Drop', 'dRoP', 'truncate', 'TRUNCATE', 'Truncate'];

        $this->forAllExpectingException(
            Generator::elements($keywords),
            function (string $keyword) {
                $sql = "SELECT * FROM users; {$keyword} TABLE users";
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }

    /**
     * Property 1.8: Multiple dangerous statements rejection.
     *
     * Test that SQL with multiple dangerous statements is rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_rejects_multiple_dangerous_statements(): void
    {
        $generator = function () {
            $keywords = ['DROP', 'TRUNCATE', 'DELETE', 'UPDATE', 'INSERT', 'ALTER'];

            for ($i = 0; $i < 100; $i++) {
                $keyword1 = $keywords[array_rand($keywords)];
                $keyword2 = $keywords[array_rand($keywords)];
                yield "SELECT * FROM users; {$keyword1} TABLE users; {$keyword2} TABLE posts";
            }
        };

        $this->forAllExpectingException(
            $generator(),
            function (string $sql) {
                $this->table->query($sql);
            },
            InvalidArgumentException::class,
            100
        );
    }
}

<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Security Test: Dangerous SQL Statement Rejection.
 *
 * Requirements: 23.3, 23.4, 36.3
 *
 * Validates that the query() method rejects all dangerous SQL statements
 * (DROP, TRUNCATE, DELETE, UPDATE, INSERT, ALTER) and only allows SELECT queries.
 */
class DangerousSqlStatementRejectionTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_data (id INT PRIMARY KEY, value VARCHAR(255))');
        DB::table('test_data')->insert([
            ['id' => 1, 'value' => 'Test 1'],
            ['id' => 2, 'value' => 'Test 2'],
        ]);
    }

    /** @test */
    public function it_rejects_drop_table_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: DROP');

        $table->query('DROP TABLE test_data');
    }

    /** @test */
    public function it_rejects_drop_database_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: DROP');

        $table->query('DROP DATABASE test_db');
    }

    /** @test */
    public function it_rejects_truncate_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: TRUNCATE');

        $table->query('TRUNCATE TABLE test_data');
    }

    /** @test */
    public function it_rejects_delete_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: DELETE');

        $table->query('DELETE FROM test_data WHERE id = 1');
    }

    /** @test */
    public function it_rejects_update_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: UPDATE');

        $table->query('UPDATE test_data SET value = "hacked" WHERE id = 1');
    }

    /** @test */
    public function it_rejects_insert_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: INSERT');

        $table->query('INSERT INTO test_data (id, value) VALUES (3, "malicious")');
    }

    /** @test */
    public function it_rejects_alter_table_statement()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query contains dangerous statement: ALTER');

        $table->query('ALTER TABLE test_data ADD COLUMN hacked VARCHAR(255)');
    }

    /** @test */
    public function it_rejects_lowercase_dangerous_statements()
    {
        $table = $this->createTableBuilder();

        $dangerousQueries = [
            'drop table test_data',
            'truncate table test_data',
            'delete from test_data',
            'update test_data set value = "x"',
            'insert into test_data values (3, "x")',
            'alter table test_data add column x varchar(255)',
        ];

        foreach ($dangerousQueries as $query) {
            try {
                $table->query($query);
                $this->fail("Expected InvalidArgumentException for query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous statement', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_mixed_case_dangerous_statements()
    {
        $table = $this->createTableBuilder();

        $dangerousQueries = [
            'DrOp TaBlE test_data',
            'TrUnCaTe TABLE test_data',
            'DeLeTe FrOm test_data',
            'UpDaTe test_data SET value = "x"',
            'InSeRt InTo test_data VALUES (3, "x")',
            'AlTeR tAbLe test_data ADD COLUMN x VARCHAR(255)',
        ];

        foreach ($dangerousQueries as $query) {
            try {
                $table->query($query);
                $this->fail("Expected InvalidArgumentException for query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous statement', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_dangerous_statements_with_comments()
    {
        $table = $this->createTableBuilder();

        $dangerousQueries = [
            '/* comment */ DROP TABLE test_data',
            'DROP /* inline comment */ TABLE test_data',
            '-- comment\nDELETE FROM test_data',
            'UPDATE test_data /* comment */ SET value = "x"',
        ];

        foreach ($dangerousQueries as $query) {
            try {
                $table->query($query);
                $this->fail("Expected InvalidArgumentException for query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous statement', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_dangerous_statements_with_whitespace()
    {
        $table = $this->createTableBuilder();

        $dangerousQueries = [
            '  DROP  TABLE  test_data  ',
            "\nDELETE\nFROM\ntest_data\n",
            "\t\tUPDATE\t\ttest_data\t\tSET value = 'x'",
        ];

        foreach ($dangerousQueries as $query) {
            try {
                $table->query($query);
                $this->fail("Expected InvalidArgumentException for query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous statement', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_allows_safe_select_statements()
    {
        $table = $this->createTableBuilder();

        $safeQueries = [
            'SELECT * FROM test_data',
            'SELECT id, value FROM test_data WHERE id = 1',
            'SELECT COUNT(*) FROM test_data',
            'SELECT * FROM test_data ORDER BY id DESC',
            'SELECT * FROM test_data LIMIT 10',
        ];

        foreach ($safeQueries as $query) {
            try {
                $result = $table->query($query);
                $this->assertInstanceOf(TableBuilder::class, $result);
            } catch (InvalidArgumentException $e) {
                $this->fail("Safe query was rejected: {$query}. Error: {$e->getMessage()}");
            }
        }
    }

    /** @test */
    public function it_allows_select_with_joins()
    {
        $table = $this->createTableBuilder();

        $query = 'SELECT t1.*, t2.value FROM test_data t1 LEFT JOIN test_data t2 ON t1.id = t2.id';

        try {
            $result = $table->query($query);
            $this->assertInstanceOf(TableBuilder::class, $result);
        } catch (InvalidArgumentException $e) {
            $this->fail("Safe JOIN query was rejected. Error: {$e->getMessage()}");
        }
    }

    /** @test */
    public function it_allows_select_with_subqueries()
    {
        $table = $this->createTableBuilder();

        $query = 'SELECT * FROM test_data WHERE id IN (SELECT id FROM test_data WHERE value LIKE "%Test%")';

        try {
            $result = $table->query($query);
            $this->assertInstanceOf(TableBuilder::class, $result);
        } catch (InvalidArgumentException $e) {
            $this->fail("Safe subquery was rejected. Error: {$e->getMessage()}");
        }
    }

    /** @test */
    public function it_rejects_select_with_into_clause()
    {
        $table = $this->createTableBuilder();

        // SELECT INTO is a write operation
        $this->expectException(InvalidArgumentException::class);

        $table->query('SELECT * INTO backup_table FROM test_data');
    }

    /** @test */
    public function it_verifies_table_integrity_after_rejection()
    {
        $table = $this->createTableBuilder();

        // Attempt multiple dangerous operations
        $dangerousQueries = [
            'DROP TABLE test_data',
            'TRUNCATE TABLE test_data',
            'DELETE FROM test_data',
            'UPDATE test_data SET value = "hacked"',
        ];

        foreach ($dangerousQueries as $query) {
            try {
                $table->query($query);
            } catch (InvalidArgumentException $e) {
                // Expected
            }
        }

        // Verify table still exists and data is intact
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_data'));
        $this->assertEquals(2, DB::table('test_data')->count());

        $data = DB::table('test_data')->orderBy('id')->get();
        $this->assertEquals('Test 1', $data[0]->value);
        $this->assertEquals('Test 2', $data[1]->value);
    }
}

<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Security Test: Column Name Validation.
 *
 * Requirements: 23.5, 25.1, 25.2, 36.3
 *
 * Validates that all column names are validated against the table schema
 * to prevent unauthorized data access and SQL injection through column names.
 */
class ColumnNameValidationTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_users (
            id INT PRIMARY KEY, 
            name VARCHAR(255), 
            email VARCHAR(255),
            password VARCHAR(255)
        )');

        DB::table('test_users')->insert([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'password' => 'hashed1'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'hashed2'],
        ]);
    }

    /** @test */
    public function it_rejects_non_existent_column_in_setFields()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name', 'non_existent_column']);
    }

    /** @test */
    public function it_rejects_non_existent_column_in_where()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->where('non_existent_column', '=', 'value');
    }

    /** @test */
    public function it_rejects_non_existent_column_in_orderby()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->orderby('non_existent_column');
    }

    /** @test */
    public function it_rejects_non_existent_column_in_searchable()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->searchable(['id', 'non_existent_column']);
    }

    /** @test */
    public function it_rejects_non_existent_column_in_sortable()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->sortable(['id', 'non_existent_column']);
    }

    /** @test */
    public function it_rejects_sql_injection_in_column_names()
    {
        $table = $this->createTableBuilder();

        $maliciousColumns = [
            'id; DROP TABLE test_users; --',
            "name' OR '1'='1",
            'email UNION SELECT password FROM test_users',
            'id, (SELECT password FROM test_users LIMIT 1) as hacked',
        ];

        foreach ($maliciousColumns as $column) {
            try {
                $table->setName('test_users')
                    ->setFields(['id', $column]);

                $this->fail("Expected InvalidArgumentException for column: {$column}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Column does not exist', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_column_from_different_table()
    {
        // Create another table
        DB::statement('CREATE TABLE other_table (id INT PRIMARY KEY, secret VARCHAR(255))');
        DB::table('other_table')->insert(['id' => 1, 'secret' => 'confidential']);

        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        // Try to access column from other_table
        $table->setName('test_users')
            ->setFields(['id', 'name', 'secret']); // 'secret' is not in test_users
    }

    /** @test */
    public function it_rejects_table_qualified_column_names()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        // Try to use table.column syntax to access other tables
        $table->setName('test_users')
            ->setFields(['id', 'test_users.password']);
    }

    /** @test */
    public function it_rejects_wildcard_column_selection()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['*']); // Should not allow * to prevent exposing all columns
    }

    /** @test */
    public function it_rejects_function_calls_in_column_names()
    {
        $table = $this->createTableBuilder();

        $maliciousColumns = [
            'COUNT(*)',
            'CONCAT(name, email)',
            'SUBSTRING(password, 1, 10)',
            'MD5(password)',
        ];

        foreach ($maliciousColumns as $column) {
            try {
                $table->setName('test_users')
                    ->setFields(['id', $column]);

                $this->fail("Expected InvalidArgumentException for column: {$column}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Column does not exist', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_subqueries_in_column_names()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', '(SELECT password FROM test_users LIMIT 1)']);
    }

    /** @test */
    public function it_accepts_valid_column_names()
    {
        $table = $this->createTableBuilder();

        // Should not throw exception
        $result = $table->setName('test_users')
            ->setFields(['id', 'name', 'email']);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_validates_columns_in_setHiddenColumns()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->setHiddenColumns(['non_existent_column']);
    }

    /** @test */
    public function it_validates_columns_in_setColumnWidth()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->setColumnWidth('non_existent_column', 100);
    }

    /** @test */
    public function it_validates_columns_in_setAlignColumns()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->setAlignColumns('center', ['non_existent_column']);
    }

    /** @test */
    public function it_validates_columns_in_setBackgroundColor()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->setBackgroundColor('#FF0000', null, ['non_existent_column']);
    }

    /** @test */
    public function it_validates_columns_in_mergeColumns()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->mergeColumns('Full Info', ['name', 'non_existent_column']);
    }

    /** @test */
    public function it_validates_columns_in_columnCondition()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->columnCondition('non_existent_column', 'cell', '==', 'value', 'css style', 'color: red');
    }

    /** @test */
    public function it_validates_columns_in_formula()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->formula('calc', 'Calculated', ['id', 'non_existent_column'], 'id + non_existent_column');
    }

    /** @test */
    public function it_validates_columns_in_format()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column does not exist');

        $table->setName('test_users')
            ->setFields(['id', 'name'])
            ->format(['non_existent_column'], 2, '.', 'number');
    }

    /** @test */
    public function it_prevents_unauthorized_column_access()
    {
        $table = $this->createTableBuilder();

        // Try to access password column (sensitive data)
        $this->expectException(InvalidArgumentException::class);

        // Assuming password is in hidden columns or restricted
        $table->setName('test_users')
            ->setFields(['id', 'name', 'password']); // Should be restricted
    }
}

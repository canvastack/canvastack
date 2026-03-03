<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Security Test: SQL Injection in WHERE Clauses.
 *
 * Requirements: 23.1, 23.2, 36.3
 *
 * Validates that the TableBuilder properly prevents SQL injection attacks
 * in where clauses by using parameter binding instead of string concatenation.
 */
class SqlInjectionWhereClauseTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test table and data
        DB::statement('CREATE TABLE test_users (id INT PRIMARY KEY, name VARCHAR(255), email VARCHAR(255))');
        DB::table('test_users')->insert([
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Admin User', 'email' => 'admin@example.com'],
        ]);
    }

    /** @test */
    public function it_prevents_sql_injection_with_or_condition()
    {
        $table = $this->createTableBuilder();

        // Malicious input attempting to bypass authentication
        $maliciousInput = "1' OR '1'='1";

        // Enable query logging
        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('id', '=', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        // Get executed queries
        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding was used (? placeholder in SQL)
        $this->assertStringContainsString('?', $lastQuery['query']);

        // Verify the malicious string was treated as a literal value
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify no records were returned (since no ID matches the literal string)
        $this->assertCount(0, $results);

        // Verify all records are still safe (not deleted or modified)
        $this->assertEquals(3, DB::table('test_users')->count());
    }

    /** @test */
    public function it_prevents_sql_injection_with_union_select()
    {
        $table = $this->createTableBuilder();

        // Malicious input attempting UNION SELECT
        $maliciousInput = '1 UNION SELECT id, name, email FROM test_users';

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('id', '=', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify no records returned
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_comment_injection()
    {
        $table = $this->createTableBuilder();

        // Malicious input with SQL comment
        $maliciousInput = "1' -- ";

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('id', '=', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify no records returned
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_multiple_statements()
    {
        $table = $this->createTableBuilder();

        // Malicious input attempting multiple statements
        $maliciousInput = '1; DROP TABLE test_users; --';

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('id', '=', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify table still exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_users'));
        $this->assertEquals(3, DB::table('test_users')->count());
    }

    /** @test */
    public function it_prevents_sql_injection_in_like_conditions()
    {
        $table = $this->createTableBuilder();

        // Malicious input in LIKE condition
        $maliciousInput = "%' OR '1'='1";

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('name', 'LIKE', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify no records returned (treated as literal string)
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_hex_encoding()
    {
        $table = $this->createTableBuilder();

        // Malicious input with hex encoding
        $maliciousInput = '0x61646d696e'; // hex for 'admin'

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('name', '=', $maliciousInput);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousInput, $lastQuery['bindings']);

        // Verify treated as literal string, not decoded
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_uses_parameter_binding_for_all_where_conditions()
    {
        $table = $this->createTableBuilder();

        DB::enableQueryLog();

        $table->setName('test_users')
            ->setFields(['id', 'name', 'email'])
            ->where('id', '>', 1)
            ->where('name', 'LIKE', '%John%')
            ->where('email', '!=', 'test@example.com');

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify all conditions use parameter binding
        $placeholderCount = substr_count($lastQuery['query'], '?');
        $this->assertEquals(3, $placeholderCount);

        // Verify all values are in bindings array
        $this->assertCount(3, $lastQuery['bindings']);
    }

    /** @test */
    public function it_prevents_sql_injection_with_special_characters()
    {
        $table = $this->createTableBuilder();

        // Various special characters that could break SQL
        $maliciousInputs = [
            "'; DROP TABLE test_users; --",
            "1' AND '1'='1",
            "1' OR 1=1 --",
            "admin'--",
            "' OR ''='",
            "1' UNION ALL SELECT NULL, NULL, NULL --",
        ];

        foreach ($maliciousInputs as $input) {
            DB::enableQueryLog();

            $table->setName('test_users')
                ->setFields(['id', 'name', 'email'])
                ->where('name', '=', $input);

            $query = $table->getQuery();
            $results = $query->get();

            $queries = DB::getQueryLog();
            $lastQuery = end($queries);

            // Verify parameter binding for each input
            $this->assertStringContainsString(
                '?',
                $lastQuery['query'],
                "Failed for input: {$input}"
            );
            $this->assertContains(
                $input,
                $lastQuery['bindings'],
                "Failed for input: {$input}"
            );

            // Clear query log for next iteration
            DB::flushQueryLog();
        }

        // Verify table integrity
        $this->assertEquals(3, DB::table('test_users')->count());
    }
}

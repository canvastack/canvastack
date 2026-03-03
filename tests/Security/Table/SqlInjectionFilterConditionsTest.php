<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Illuminate\Support\Facades\DB;

/**
 * Security Test: SQL Injection in Filter Conditions.
 *
 * Requirements: 23.1, 23.2, 36.3
 *
 * Validates that filterConditions() method properly prevents SQL injection
 * by using parameter binding for all filter values.
 */
class SqlInjectionFilterConditionsTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_products (
            id INT PRIMARY KEY, 
            name VARCHAR(255), 
            category VARCHAR(100),
            price DECIMAL(10,2),
            status VARCHAR(50)
        )');

        DB::table('test_products')->insert([
            ['id' => 1, 'name' => 'Product A', 'category' => 'Electronics', 'price' => 99.99, 'status' => 'active'],
            ['id' => 2, 'name' => 'Product B', 'category' => 'Books', 'price' => 19.99, 'status' => 'active'],
            ['id' => 3, 'name' => 'Product C', 'category' => 'Electronics', 'price' => 149.99, 'status' => 'inactive'],
        ]);
    }

    /** @test */
    public function it_prevents_sql_injection_in_filter_conditions()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'category' => "Electronics' OR '1'='1",
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'category', 'price'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousFilters['category'], $lastQuery['bindings']);

        // Verify no records returned (malicious string doesn't match)
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_multiple_filters()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'category' => "Electronics' UNION SELECT * FROM test_products --",
            'status' => "active' OR '1'='1",
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'category', 'status'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding for all filters
        $placeholderCount = substr_count($lastQuery['query'], '?');
        $this->assertGreaterThanOrEqual(2, $placeholderCount);

        // Verify all malicious values are bound
        foreach ($maliciousFilters as $value) {
            $this->assertContains($value, $lastQuery['bindings']);
        }

        // Verify no records returned
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_numeric_filters()
    {
        $table = $this->createTableBuilder();

        // Malicious numeric filter
        $maliciousFilters = [
            'price' => '99.99 OR 1=1',
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'price'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousFilters['price'], $lastQuery['bindings']);

        // Verify treated as literal string
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_drop_table_attempt()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'name' => "Product'; DROP TABLE test_products; --",
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);

        // Verify table still exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_products'));
        $this->assertEquals(3, DB::table('test_products')->count());
    }

    /** @test */
    public function it_prevents_sql_injection_with_subquery_injection()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'category' => '(SELECT category FROM test_products WHERE id=1)',
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'category'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding (subquery treated as literal string)
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousFilters['category'], $lastQuery['bindings']);

        // Verify no records returned
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_encoded_characters()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'name' => 'Product%27%20OR%20%271%27%3D%271', // URL encoded: Product' OR '1'='1
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);
        $this->assertContains($maliciousFilters['name'], $lastQuery['bindings']);

        // Verify treated as literal string
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_prevents_sql_injection_with_null_byte_injection()
    {
        $table = $this->createTableBuilder();

        $maliciousFilters = [
            'name' => "Product\0' OR '1'='1",
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding
        $this->assertStringContainsString('?', $lastQuery['query']);

        // Verify no records returned
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_uses_parameter_binding_for_all_filter_types()
    {
        $table = $this->createTableBuilder();

        $filters = [
            'name' => 'Product A',
            'category' => 'Electronics',
            'price' => 99.99,
            'status' => 'active',
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'category', 'price', 'status'])
            ->filterConditions($filters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify all filters use parameter binding
        $placeholderCount = substr_count($lastQuery['query'], '?');
        $this->assertEquals(count($filters), $placeholderCount);

        // Verify all values are in bindings
        $this->assertCount(count($filters), $lastQuery['bindings']);
    }

    /** @test */
    public function it_prevents_sql_injection_with_array_filters()
    {
        $table = $this->createTableBuilder();

        // Malicious array filter (IN clause)
        $maliciousFilters = [
            'category' => ['Electronics', "Books' OR '1'='1"],
        ];

        DB::enableQueryLog();

        $table->setName('test_products')
            ->setFields(['id', 'name', 'category'])
            ->filterConditions($maliciousFilters);

        $query = $table->getQuery();
        $results = $query->get();

        $queries = DB::getQueryLog();
        $lastQuery = end($queries);

        // Verify parameter binding for array values
        $this->assertStringContainsString('?', $lastQuery['query']);

        // Verify only valid category returned
        $this->assertCount(2, $results); // Only Electronics products
        $this->assertTrue($results->every(fn ($p) => $p->category === 'Electronics'));
    }
}

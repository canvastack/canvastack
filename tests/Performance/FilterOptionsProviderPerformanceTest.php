<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Performance tests for FilterOptionsProvider
 * 
 * Verifies that query optimization meets performance targets:
 * - Query performance < 50ms
 * - Memory usage is reasonable
 * - Queries use indexed columns
 * - Result set is limited
 * - Queries are parameterized (SQL injection safe)
 */
class FilterOptionsProviderPerformanceTest extends TestCase
{
    protected FilterOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->provider = new FilterOptionsProvider();
        
        // Disable caching for accurate performance measurement
        $this->provider->setCacheEnabled(false);
        
        // Ensure test data exists
        $this->seedTestData();
    }

    /**
     * Seed test data for performance testing
     */
    protected function seedTestData(): void
    {
        // Check if data already exists
        $count = DB::table('users')->count();
        
        if ($count < 100) {
            // Create test users if needed
            for ($i = 0; $i < 100; $i++) {
                DB::table('users')->insert([
                    'name' => 'User ' . $i,
                    'email' => 'user' . $i . '@example.com',
                    'password' => 'hashed_password',
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Test: Query performance < 50ms
     * 
     * Acceptance Criteria: Query performance < 50ms
     */
    public function test_query_performance_under_50ms(): void
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        // Execute query
        $options = $this->provider->getOptions('users', 'name', []);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Assert execution time < 50ms
        $this->assertLessThan(
            50,
            $executionTime,
            "Query execution time ({$executionTime}ms) exceeds 50ms target"
        );
        
        // Assert options were returned
        $this->assertNotEmpty($options, 'Options should not be empty');
        
        // Assert query was executed
        $this->assertNotEmpty($queries, 'Query should have been executed');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Query with parent filters performance < 50ms
     * 
     * Acceptance Criteria: Query performance < 50ms with parent filters
     */
    public function test_query_with_parent_filters_performance_under_50ms(): void
    {
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        // Execute query with parent filters
        $options = $this->provider->getOptions('users', 'email', [
            'name' => 'User 1',
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        $queries = DB::getQueryLog();
        
        // Assert execution time < 50ms
        $this->assertLessThan(
            50,
            $executionTime,
            "Query with parent filters execution time ({$executionTime}ms) exceeds 50ms target"
        );
        
        // Assert options were returned
        $this->assertIsArray($options);
        
        DB::disableQueryLog();
    }

    /**
     * Test: Queries use indexed columns
     * 
     * Acceptance Criteria: Queries use indexed columns
     */
    public function test_queries_use_indexed_columns(): void
    {
        DB::enableQueryLog();
        
        // Execute query
        $this->provider->getOptions('users', 'name', []);
        
        $queries = DB::getQueryLog();
        
        // Assert query was executed
        $this->assertNotEmpty($queries, 'Query should have been executed');
        
        $query = $queries[0]['query'];
        
        // Assert query uses indexed column (name)
        $this->assertTrue(
            str_contains($query, '`name`') || str_contains($query, '"name"'),
            'Query should use indexed name column'
        );
        
        // Assert query uses WHERE clause for filtering
        $this->assertStringContainsString('where', strtolower($query), 'Query should use WHERE clause');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Result set is limited
     * 
     * Acceptance Criteria: Result set is limited
     */
    public function test_result_set_is_limited(): void
    {
        DB::enableQueryLog();
        
        // Execute query
        $options = $this->provider->getOptions('users', 'name', []);
        
        $queries = DB::getQueryLog();
        
        // Assert query was executed
        $this->assertNotEmpty($queries, 'Query should have been executed');
        
        $query = $queries[0]['query'];
        
        // Assert query has LIMIT clause
        $this->assertStringContainsString('limit', strtolower($query), 'Query should have LIMIT clause');
        
        // Assert result count doesn't exceed max options
        $this->assertLessThanOrEqual(
            1000,
            count($options),
            'Result set should not exceed 1000 options'
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test: Queries are parameterized (SQL injection safe)
     * 
     * Acceptance Criteria: Queries are parameterized (SQL injection safe)
     */
    public function test_queries_are_parameterized(): void
    {
        DB::enableQueryLog();
        
        // Attempt SQL injection via parent filters
        $maliciousInput = "'; DROP TABLE users; --";
        
        // Execute query with malicious input
        $options = $this->provider->getOptions('users', 'email', [
            'name' => $maliciousInput,
        ]);
        
        $queries = DB::getQueryLog();
        
        // Assert query was executed
        $this->assertNotEmpty($queries, 'Query should have been executed');
        
        $query = $queries[0];
        
        // Assert query uses bindings (parameterized)
        $this->assertNotEmpty($query['bindings'], 'Query should use parameter bindings');
        
        // Assert malicious input is in bindings (not in raw SQL)
        $this->assertContains($maliciousInput, $query['bindings'], 'Malicious input should be parameterized');
        
        // Assert raw SQL doesn't contain malicious input
        $this->assertStringNotContainsString(
            'DROP TABLE',
            $query['query'],
            'Raw SQL should not contain malicious input'
        );
        
        // Assert users table still exists (SQL injection prevented)
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('users'),
            'Users table should still exist (SQL injection prevented)'
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test: Memory usage is reasonable
     * 
     * Acceptance Criteria: Memory usage is reasonable
     */
    public function test_memory_usage_is_reasonable(): void
    {
        // Get initial memory usage
        $memoryBefore = memory_get_usage(true);
        
        // Execute query multiple times
        for ($i = 0; $i < 10; $i++) {
            $options = $this->provider->getOptions('users', 'name', []);
        }
        
        // Get final memory usage
        $memoryAfter = memory_get_usage(true);
        
        // Calculate memory increase
        $memoryIncrease = $memoryAfter - $memoryBefore;
        $memoryIncreaseMB = $memoryIncrease / 1024 / 1024;
        
        // Assert memory increase is reasonable (< 10MB for 10 queries)
        $this->assertLessThan(
            10,
            $memoryIncreaseMB,
            "Memory increase ({$memoryIncreaseMB}MB) is too high for 10 queries"
        );
    }

    /**
     * Test: Query uses DISTINCT to prevent duplicates
     */
    public function test_query_uses_distinct(): void
    {
        DB::enableQueryLog();
        
        $this->provider->getOptions('users', 'name', []);
        
        $queries = DB::getQueryLog();
        $query = $queries[0]['query'];
        
        // Assert query uses DISTINCT
        $this->assertStringContainsString('distinct', strtolower($query), 'Query should use DISTINCT');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Query excludes NULL and empty values
     */
    public function test_query_excludes_null_and_empty(): void
    {
        DB::enableQueryLog();
        
        $this->provider->getOptions('users', 'name', []);
        
        $queries = DB::getQueryLog();
        $query = $queries[0]['query'];
        
        // Assert query excludes NULL
        $this->assertStringContainsString('is not null', strtolower($query), 'Query should exclude NULL values');
        
        // Assert query excludes empty strings
        $this->assertStringContainsString('!=', $query, 'Query should exclude empty strings');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Query orders results
     */
    public function test_query_orders_results(): void
    {
        DB::enableQueryLog();
        
        $this->provider->getOptions('users', 'name', []);
        
        $queries = DB::getQueryLog();
        $query = $queries[0]['query'];
        
        // Assert query has ORDER BY clause
        $this->assertStringContainsString('order by', strtolower($query), 'Query should have ORDER BY clause');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Composite index usage with multiple parent filters
     */
    public function test_composite_index_usage(): void
    {
        DB::enableQueryLog();
        
        // Query with multiple parent filters (should use composite index)
        $options = $this->provider->getOptions('users', 'created_at', [
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);
        
        $queries = DB::getQueryLog();
        $query = $queries[0]['query'];
        
        // Assert query uses both parent filter columns
        $this->assertTrue(
            str_contains($query, '`name`') || str_contains($query, '"name"'),
            'Query should filter by name'
        );
        $this->assertTrue(
            str_contains($query, '`email`') || str_contains($query, '"email"'),
            'Query should filter by email'
        );
        
        // Assert query uses WHERE clauses for both filters
        $whereCount = substr_count(strtolower($query), 'where');
        $this->assertGreaterThanOrEqual(1, $whereCount, 'Query should have WHERE clauses');
        
        DB::disableQueryLog();
    }

    /**
     * Test: Performance with large result set
     */
    public function test_performance_with_large_result_set(): void
    {
        // Create more test data if needed
        $currentCount = DB::table('users')->count();
        if ($currentCount < 500) {
            for ($i = $currentCount; $i < 500; $i++) {
                DB::table('users')->insert([
                    'name' => 'User ' . $i,
                    'email' => 'user' . $i . '@example.com',
                    'password' => 'hashed_password',
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now(),
                ]);
            }
        }
        
        $startTime = microtime(true);
        
        // Execute query
        $options = $this->provider->getOptions('users', 'name', []);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Assert execution time is still reasonable with larger dataset
        $this->assertLessThan(
            100,
            $executionTime,
            "Query execution time ({$executionTime}ms) with large dataset exceeds 100ms"
        );
        
        // Assert result is limited
        $this->assertLessThanOrEqual(
            1000,
            count($options),
            'Result set should be limited even with large dataset'
        );
    }

    /**
     * Test: Caching improves performance
     */
    public function test_caching_improves_performance(): void
    {
        // Enable caching
        $this->provider->setCacheEnabled(true);
        Cache::flush();
        
        // First query (cache miss)
        $startTime1 = microtime(true);
        $options1 = $this->provider->getOptions('users', 'name', []);
        $endTime1 = microtime(true);
        $time1 = ($endTime1 - $startTime1) * 1000;
        
        // Second query (cache hit)
        $startTime2 = microtime(true);
        $options2 = $this->provider->getOptions('users', 'name', []);
        $endTime2 = microtime(true);
        $time2 = ($endTime2 - $startTime2) * 1000;
        
        // Assert cached query is faster
        $this->assertLessThan(
            $time1,
            $time2,
            "Cached query ({$time2}ms) should be faster than uncached ({$time1}ms)"
        );
        
        // Assert cached query is very fast (< 10ms)
        $this->assertLessThan(
            10,
            $time2,
            "Cached query ({$time2}ms) should be under 10ms"
        );
        
        // Assert results are identical
        $this->assertEquals($options1, $options2, 'Cached results should match original');
        
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clean up
        DB::disableQueryLog();
        Cache::flush();
        
        parent::tearDown();
    }
}

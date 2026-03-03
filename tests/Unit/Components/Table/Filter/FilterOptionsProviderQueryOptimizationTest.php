<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Filter;

use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Test for FilterOptionsProvider query optimization.
 */
class FilterOptionsProviderQueryOptimizationTest extends TestCase
{
    protected FilterOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new FilterOptionsProvider();

        // Create test table with many records
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Drop test table
        DB::statement('DROP TABLE IF EXISTS test_filter_options');

        parent::tearDown();
    }

    /**
     * Create test table with sample data
     */
    protected function createTestTable(): void
    {
        DB::statement('
            CREATE TABLE test_filter_options (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                region TEXT NOT NULL,
                cluster TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT
            )
        ');

        // Insert 2000 records to test optimization
        for ($i = 1; $i <= 2000; $i++) {
            DB::table('test_filter_options')->insert([
                'region' => 'Region ' . (($i % 10) + 1),
                'cluster' => 'Cluster ' . (($i % 50) + 1),
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Test that query limits results to prevent memory issues
     */
    public function test_query_limits_results(): void
    {
        // Default max is 1000
        $options = $this->provider->getOptions('test_filter_options', 'cluster', []);

        // Should return max 50 distinct clusters (less than 1000 limit)
        $this->assertLessThanOrEqual(1000, count($options));
        $this->assertEquals(50, count($options)); // We have 50 distinct clusters
    }

    /**
     * Test that query excludes null and empty values
     */
    public function test_query_excludes_null_and_empty(): void
    {
        // Insert empty value (null not allowed by schema)
        DB::table('test_filter_options')->insert([
            'region' => '', // Empty string
            'cluster' => 'Cluster 1',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $options = $this->provider->getOptions('test_filter_options', 'region', []);

        // Should not include empty values
        foreach ($options as $option) {
            $this->assertNotEmpty($option['value']);
        }
    }

    /**
     * Test that query uses DISTINCT to avoid duplicates
     */
    public function test_query_uses_distinct(): void
    {
        $options = $this->provider->getOptions('test_filter_options', 'status', []);

        // Should return only 2 distinct values (active, inactive)
        $this->assertCount(2, $options);

        $values = array_column($options, 'value');
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
    }

    /**
     * Test that query applies parent filters efficiently
     */
    public function test_query_applies_parent_filters(): void
    {
        $options = $this->provider->getOptions(
            'test_filter_options',
            'cluster',
            ['region' => 'Region 1']
        );

        // Should return clusters only for Region 1
        $this->assertGreaterThan(0, count($options));
        $this->assertLessThanOrEqual(50, count($options));
    }

    /**
     * Test that query orders results alphabetically
     */
    public function test_query_orders_results(): void
    {
        $options = $this->provider->getOptions('test_filter_options', 'region', []);

        // Check if results are ordered
        $values = array_column($options, 'value');
        $sortedValues = $values;
        sort($sortedValues);

        $this->assertEquals($sortedValues, $values);
    }

    /**
     * Test max options configuration
     */
    public function test_max_options_configuration(): void
    {
        // Set max to 5
        $this->provider->setMaxOptions(5);

        $options = $this->provider->getOptions('test_filter_options', 'region', []);

        // Should return max 5 options
        $this->assertLessThanOrEqual(5, count($options));
    }

    /**
     * Test optimization can be disabled
     */
    public function test_optimization_can_be_disabled(): void
    {
        // Disable optimization
        $this->provider->setOptimizationEnabled(false);

        $options = $this->provider->getOptions('test_filter_options', 'cluster', []);

        // Should return all 50 distinct clusters (no limit applied)
        $this->assertEquals(50, count($options));
    }

    /**
     * Test getOptionsWithCount includes record counts
     */
    public function test_get_options_with_count(): void
    {
        $options = $this->provider->getOptionsWithCount('test_filter_options', 'status', []);

        $this->assertCount(2, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('count', $option);
            $this->assertIsInt($option['count']);
            $this->assertGreaterThan(0, $option['count']);
        }

        // Check that counts are correct
        $activeOption = collect($options)->firstWhere('value', 'active');
        $inactiveOption = collect($options)->firstWhere('value', 'inactive');

        $this->assertEquals(1000, $activeOption['count']); // 2000 / 2
        $this->assertEquals(1000, $inactiveOption['count']); // 2000 / 2
    }

    /**
     * Test getOptionsPaginated returns paginated results
     */
    public function test_get_options_paginated(): void
    {
        $result = $this->provider->getOptionsPaginated(
            'test_filter_options',
            'cluster',
            [],
            1, // page
            10 // per page
        );

        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('pagination', $result);

        $this->assertCount(10, $result['options']);

        $pagination = $result['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(50, $pagination['total']); // 50 distinct clusters
        $this->assertEquals(5, $pagination['last_page']); // 50 / 10
    }

    /**
     * Test prefetchOptions loads multiple columns efficiently
     */
    public function test_prefetch_options(): void
    {
        $results = $this->provider->prefetchOptions(
            'test_filter_options',
            ['region', 'cluster', 'status'],
            []
        );

        $this->assertArrayHasKey('region', $results);
        $this->assertArrayHasKey('cluster', $results);
        $this->assertArrayHasKey('status', $results);

        $this->assertCount(10, $results['region']); // 10 distinct regions
        $this->assertCount(50, $results['cluster']); // 50 distinct clusters
        $this->assertCount(2, $results['status']); // 2 distinct statuses
    }

    /**
     * Test query optimization with caching
     */
    public function test_query_optimization_with_caching(): void
    {
        Cache::flush();

        // First call - should query database
        $start = microtime(true);
        $options1 = $this->provider->getOptions('test_filter_options', 'region', []);
        $time1 = microtime(true) - $start;

        // Second call - should use cache
        $start = microtime(true);
        $options2 = $this->provider->getOptions('test_filter_options', 'region', []);
        $time2 = microtime(true) - $start;

        // Results should be identical
        $this->assertEquals($options1, $options2);

        // Cached call should be faster (at least 2x faster)
        $this->assertLessThan($time1 / 2, $time2);
    }

    /**
     * Test query performance with large dataset
     */
    public function test_query_performance_with_large_dataset(): void
    {
        $start = microtime(true);
        $options = $this->provider->getOptions('test_filter_options', 'cluster', []);
        $duration = microtime(true) - $start;

        // Should complete in less than 100ms even with 2000 records
        $this->assertLessThan(0.1, $duration);

        // Should return correct number of options
        $this->assertEquals(50, count($options));
    }

    /**
     * Test memory usage with large dataset
     */
    public function test_memory_usage_with_large_dataset(): void
    {
        $memoryBefore = memory_get_usage();

        $options = $this->provider->getOptions('test_filter_options', 'cluster', []);

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should use less than 1MB of memory
        $this->assertLessThan(1024 * 1024, $memoryUsed);

        // Should return correct results
        $this->assertEquals(50, count($options));
    }

    /**
     * Test query with multiple parent filters
     */
    public function test_query_with_multiple_parent_filters(): void
    {
        $options = $this->provider->getOptions(
            'test_filter_options',
            'cluster',
            [
                'region' => 'Region 1',
                'status' => 'active',
            ]
        );

        // Should return filtered results
        $this->assertGreaterThan(0, count($options));
        $this->assertLessThanOrEqual(50, count($options));
    }

    /**
     * Test query handles special characters safely
     */
    public function test_query_handles_special_characters(): void
    {
        // Insert record with special characters
        DB::table('test_filter_options')->insert([
            'region' => "Region's \"Special\" <Test>",
            'cluster' => 'Cluster 1',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $options = $this->provider->getOptions('test_filter_options', 'region', []);

        // Should handle special characters safely
        $specialOption = collect($options)->firstWhere('value', "Region's \"Special\" <Test>");
        $this->assertNotNull($specialOption);
        $this->assertEquals("Region's \"Special\" <Test>", $specialOption['value']);
    }
}


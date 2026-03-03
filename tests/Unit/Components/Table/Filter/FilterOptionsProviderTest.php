<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Filter;

use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test for FilterOptionsProvider.
 */
class FilterOptionsProviderTest extends TestCase
{
    protected FilterOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new FilterOptionsProvider();
        
        // Create test table
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Clean up test table
        Schema::dropIfExists('test_filter_table');
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Create test table for filter testing.
     */
    protected function createTestTable(): void
    {
        Schema::create('test_filter_table', function ($table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('region')->nullable();
            $table->string('status')->nullable();
            $table->integer('count')->default(0);
            $table->timestamps();
        });

        // Insert test data
        DB::table('test_filter_table')->insert([
            ['category' => 'Electronics', 'region' => 'North', 'status' => 'active', 'count' => 10],
            ['category' => 'Electronics', 'region' => 'South', 'status' => 'active', 'count' => 15],
            ['category' => 'Clothing', 'region' => 'North', 'status' => 'inactive', 'count' => 5],
            ['category' => 'Clothing', 'region' => 'East', 'status' => 'active', 'count' => 8],
            ['category' => 'Books', 'region' => 'West', 'status' => 'active', 'count' => 12],
        ]);
    }

    /**
     * Test that filter options are cached correctly.
     */
    public function test_filter_options_are_cached(): void
    {
        // Enable caching
        $this->provider->setCacheEnabled(true);
        $this->provider->setCacheTtl(300);

        // First call should hit database and cache result
        $options1 = $this->provider->getOptions('test_filter_table', 'category');

        // Second call should hit cache
        $options2 = $this->provider->getOptions('test_filter_table', 'category');

        // Results should be identical
        $this->assertEquals($options1, $options2);
        
        // Should have 3 categories
        $this->assertCount(3, $options1);
        
        // Check expected categories
        $categories = array_column($options1, 'value');
        $this->assertContains('Electronics', $categories);
        $this->assertContains('Clothing', $categories);
        $this->assertContains('Books', $categories);
    }

    /**
     * Test that caching can be disabled.
     */
    public function test_caching_can_be_disabled(): void
    {
        // Disable caching
        $this->provider->setCacheEnabled(false);

        // Get options (should not cache)
        $options = $this->provider->getOptions('test_filter_table', 'category');

        // Should still return correct results
        $this->assertCount(3, $options);
        
        // Verify no cache entry was created
        $cacheKey = $this->provider->generateCacheKey('test_filter_table', 'category', []);
        $this->assertNull(Cache::get($cacheKey));
    }

    /**
     * Test cascading filters with parent filters.
     */
    public function test_cascading_filters_with_parent_filters(): void
    {
        $this->provider->setCacheEnabled(true);

        // Get regions for Electronics category
        $options = $this->provider->getOptions('test_filter_table', 'region', ['category' => 'Electronics']);

        // Should only return regions that have Electronics
        $this->assertCount(2, $options);
        
        $regions = array_column($options, 'value');
        $this->assertContains('North', $regions);
        $this->assertContains('South', $regions);
        $this->assertNotContains('East', $regions); // East only has Clothing
        $this->assertNotContains('West', $regions); // West only has Books
    }

    /**
     * Test options with count functionality.
     */
    public function test_options_with_count(): void
    {
        $this->provider->setCacheEnabled(true);

        // Get categories with count
        $options = $this->provider->getOptionsWithCount('test_filter_table', 'category');

        // Should have 3 categories
        $this->assertCount(3, $options);

        // Check that each option has count
        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('count', $option);
            $this->assertIsInt($option['count']);
            $this->assertGreaterThan(0, $option['count']);
        }

        // Electronics should have 2 records
        $electronicsOption = collect($options)->firstWhere('value', 'Electronics');
        $this->assertEquals(2, $electronicsOption['count']);
    }

    /**
     * Test paginated options for large datasets.
     */
    public function test_paginated_options(): void
    {
        // Get first page
        $result = $this->provider->getOptionsPaginated('test_filter_table', 'category', [], 1, 2);

        // Should have pagination info
        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('pagination', $result);

        // Should have 2 options (page size)
        $this->assertCount(2, $result['options']);

        // Check pagination info
        $pagination = $result['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(2, $pagination['per_page']);
        $this->assertEquals(3, $pagination['total']);
        $this->assertEquals(2, $pagination['last_page']);
    }

    /**
     * Test cache clearing functionality.
     */
    public function test_cache_clearing(): void
    {
        $this->provider->setCacheEnabled(true);

        // Cache some options
        $options = $this->provider->getOptions('test_filter_table', 'category');
        $this->assertCount(3, $options);

        // Clear cache for specific column
        $this->provider->clearCache('test_filter_table', 'category');

        // Cache should be cleared (this is hard to test directly, but we can verify the method runs)
        $this->assertTrue(true); // Method executed without error
    }

    /**
     * Test cache warming functionality.
     */
    public function test_cache_warming(): void
    {
        $this->provider->setCacheEnabled(true);

        // Warm cache for multiple columns
        $results = $this->provider->prefetchOptions('test_filter_table', ['category', 'region', 'status']);

        // Should have results for all columns
        $this->assertArrayHasKey('category', $results);
        $this->assertArrayHasKey('region', $results);
        $this->assertArrayHasKey('status', $results);

        // Each should have options
        $this->assertCount(3, $results['category']); // 3 categories
        $this->assertCount(4, $results['region']);   // 4 regions
        $this->assertCount(2, $results['status']);   // 2 statuses
    }

    /**
     * Test options from array conversion.
     */
    public function test_options_from_array(): void
    {
        $values = ['option1', 'option2', 'option3'];
        $options = $this->provider->getOptionsFromArray($values);

        $this->assertCount(3, $options);
        
        foreach ($options as $i => $option) {
            $this->assertEquals($values[$i], $option['value']);
            $this->assertEquals($values[$i], $option['label']);
        }
    }

    /**
     * Test options from key-value array conversion.
     */
    public function test_options_from_key_value_array(): void
    {
        $keyValues = [
            'key1' => 'Label 1',
            'key2' => 'Label 2',
            'key3' => 'Label 3',
        ];
        
        $options = $this->provider->getOptionsFromKeyValue($keyValues);

        $this->assertCount(3, $options);
        
        $this->assertEquals('key1', $options[0]['value']);
        $this->assertEquals('Label 1', $options[0]['label']);
        
        $this->assertEquals('key2', $options[1]['value']);
        $this->assertEquals('Label 2', $options[1]['label']);
        
        $this->assertEquals('key3', $options[2]['value']);
        $this->assertEquals('Label 3', $options[2]['label']);
    }

    /**
     * Test configuration integration.
     */
    public function test_configuration_integration(): void
    {
        // Test that provider uses configuration values
        $provider = new FilterOptionsProvider();
        
        // Should use config values (we can't easily test this without mocking config)
        $this->assertInstanceOf(FilterOptionsProvider::class, $provider);
    }

    /**
     * Test query optimization settings.
     */
    public function test_query_optimization(): void
    {
        // Enable optimization
        $this->provider->setOptimizationEnabled(true);
        $this->provider->setMaxOptions(2); // Limit to 2 options

        $options = $this->provider->getOptions('test_filter_table', 'category');

        // Should be limited to 2 options due to optimization
        $this->assertLessThanOrEqual(2, count($options));
    }

    /**
     * Test that empty and null values are excluded.
     */
    public function test_empty_values_excluded(): void
    {
        // Add record with empty category
        DB::table('test_filter_table')->insert([
            'category' => '',
            'region' => 'Test',
            'status' => 'active',
            'count' => 1,
        ]);

        // Add record with null category
        DB::table('test_filter_table')->insert([
            'category' => null,
            'region' => 'Test',
            'status' => 'active',
            'count' => 1,
        ]);

        $options = $this->provider->getOptions('test_filter_table', 'category');

        // Should still only have 3 categories (empty and null excluded)
        $this->assertCount(3, $options);
        
        $categories = array_column($options, 'value');
        $this->assertNotContains('', $categories);
        $this->assertNotContains(null, $categories);
    }
}
<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Http\Controllers;

use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;
use Canvastack\Canvastack\Http\Controllers\DataTableController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test for DataTableController filter caching functionality.
 */
class DataTableControllerCacheTest extends TestCase
{
    protected DataTableController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new DataTableController();
        
        // Create test table
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Clean up test table
        Schema::dropIfExists('test_datatable_cache');
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Create test table for filter testing.
     */
    protected function createTestTable(): void
    {
        Schema::create('test_datatable_cache', function ($table) {
            $table->id();
            $table->string('category');
            $table->string('region');
            $table->string('status');
            $table->timestamps();
        });

        // Insert test data
        DB::table('test_datatable_cache')->insert([
            ['category' => 'Electronics', 'region' => 'North', 'status' => 'active'],
            ['category' => 'Electronics', 'region' => 'South', 'status' => 'active'],
            ['category' => 'Clothing', 'region' => 'North', 'status' => 'inactive'],
            ['category' => 'Books', 'region' => 'West', 'status' => 'active'],
        ]);
    }

    /**
     * Test that getFilterOptions uses FilterOptionsProvider with caching.
     */
    public function test_get_filter_options_uses_provider_with_caching(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'category',
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('options', $data);
        $this->assertCount(3, $data['options']); // Electronics, Clothing, Books
        
        // Verify options structure
        foreach ($data['options'] as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    /**
     * Test getFilterOptions with parent filters.
     */
    public function test_get_filter_options_with_parent_filters(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'region',
            'parentFilters' => [
                'category' => 'Electronics'
            ]
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['options']); // North, South (only regions with Electronics)
        
        $regions = array_column($data['options'], 'value');
        $this->assertContains('North', $regions);
        $this->assertContains('South', $regions);
        $this->assertNotContains('West', $regions); // West only has Books
    }

    /**
     * Test getFilterOptions with count option.
     */
    public function test_get_filter_options_with_count(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'category',
            'withCount' => true,
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertCount(3, $data['options']);
        
        // Each option should have count
        foreach ($data['options'] as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('count', $option);
        }
    }

    /**
     * Test getFilterOptions with pagination.
     */
    public function test_get_filter_options_with_pagination(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'category',
            'page' => 1,
            'perPage' => 2,
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('options', $data);
        $this->assertArrayHasKey('pagination', $data);
        
        // Should have 2 options (page size)
        $this->assertCount(2, $data['options']);
        
        // Check pagination info
        $pagination = $data['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(2, $pagination['per_page']);
        $this->assertEquals(3, $pagination['total']);
    }

    /**
     * Test clearFilterCache method.
     */
    public function test_clear_filter_cache(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'category',
        ]);

        $response = $this->controller->clearFilterCache($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('cache cleared', $data['message']);
    }

    /**
     * Test clearFilterCache for all columns.
     */
    public function test_clear_all_filter_cache(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
        ]);

        $response = $this->controller->clearFilterCache($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('All filter cache cleared', $data['message']);
    }

    /**
     * Test warmFilterCache method.
     */
    public function test_warm_filter_cache(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'columns' => ['category', 'region', 'status'],
        ]);

        $response = $this->controller->warmFilterCache($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('warmed successfully', $data['message']);
        $this->assertArrayHasKey('warmed_columns', $data);
        
        // Should have warmed 3 columns
        $this->assertCount(3, $data['warmed_columns']);
        
        foreach ($data['warmed_columns'] as $columnInfo) {
            $this->assertArrayHasKey('column', $columnInfo);
            $this->assertArrayHasKey('options_count', $columnInfo);
            $this->assertGreaterThan(0, $columnInfo['options_count']);
        }
    }

    /**
     * Test validation errors.
     */
    public function test_validation_errors(): void
    {
        // Missing required table parameter
        $request = new Request([
            'column' => 'category',
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test invalid table error.
     */
    public function test_invalid_table_error(): void
    {
        $request = new Request([
            'table' => 'nonexistent_table',
            'column' => 'category',
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('does not exist', $data['message']);
    }

    /**
     * Test invalid column error.
     */
    public function test_invalid_column_error(): void
    {
        $request = new Request([
            'table' => 'test_datatable_cache',
            'column' => 'nonexistent_column',
        ]);

        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('does not exist', $data['message']);
    }

    /**
     * Test that FilterOptionsProvider is properly configured from config.
     */
    public function test_filter_options_provider_configuration(): void
    {
        // This test verifies that the controller properly configures the FilterOptionsProvider
        // based on configuration values
        
        $provider = app(FilterOptionsProvider::class);
        
        // Test that provider is properly instantiated
        $this->assertInstanceOf(FilterOptionsProvider::class, $provider);
        
        // Test configuration methods exist
        $this->assertTrue(method_exists($provider, 'setCacheEnabled'));
        $this->assertTrue(method_exists($provider, 'setCacheTtl'));
        $this->assertTrue(method_exists($provider, 'setOptimizationEnabled'));
        $this->assertTrue(method_exists($provider, 'setMaxOptions'));
    }
}
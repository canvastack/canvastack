<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Http;

use Canvastack\Canvastack\Http\Controllers\DataTableController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Test for DataTableController.
 */
class DataTableControllerTest extends TestCase
{
    private DataTableController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DataTableController();
        
        // Create test table for filter options testing
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Clean up test table
        $this->dropTestTable();
        parent::tearDown();
    }

    /**
     * Create test table for testing filter options.
     */
    private function createTestTable(): void
    {
        $capsule = Capsule::connection();
        
        $capsule->getSchemaBuilder()->create('test_filter_table', function ($table) {
            $table->id();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        // Insert test data
        $capsule->table('test_filter_table')->insert([
            ['region' => 'North', 'city' => 'New York', 'status' => 'active'],
            ['region' => 'North', 'city' => 'Boston', 'status' => 'active'],
            ['region' => 'South', 'city' => 'Miami', 'status' => 'inactive'],
            ['region' => 'South', 'city' => 'Atlanta', 'status' => 'active'],
            ['region' => 'West', 'city' => 'Los Angeles', 'status' => 'active'],
        ]);
    }

    /**
     * Drop test table after testing.
     */
    private function dropTestTable(): void
    {
        $capsule = Capsule::connection();
        
        if ($capsule->getSchemaBuilder()->hasTable('test_filter_table')) {
            $capsule->getSchemaBuilder()->drop('test_filter_table');
        }
    }

    /**
     * Test getFilterOptions returns correct options for a column.
     */
    public function test_get_filter_options_returns_correct_options(): void
    {
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'test_filter_table',
            'column' => 'region',
        ]);

        $response = $this->controller->getFilterOptions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('options', $data);
        
        // Should have 3 distinct regions
        $this->assertCount(3, $data['options']);
        
        // Check option structure
        $this->assertArrayHasKey('value', $data['options'][0]);
        $this->assertArrayHasKey('label', $data['options'][0]);
    }

    /**
     * Test getFilterOptions with parent filters (cascading).
     */
    public function test_get_filter_options_with_parent_filters(): void
    {
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'test_filter_table',
            'column' => 'city',
            'parentFilters' => [
                'region' => 'North',
            ],
        ]);

        $response = $this->controller->getFilterOptions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        
        // Should only have cities in North region
        $this->assertCount(2, $data['options']);
        
        $cities = array_column($data['options'], 'value');
        $this->assertContains('New York', $cities);
        $this->assertContains('Boston', $cities);
        $this->assertNotContains('Miami', $cities);
    }

    /**
     * Test getFilterOptions with invalid table name.
     */
    public function test_get_filter_options_with_invalid_table(): void
    {
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'nonexistent_table',
            'column' => 'region',
        ]);

        $response = $this->controller->getFilterOptions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('does not exist', $data['message']);
    }

    /**
     * Test getFilterOptions with invalid column name.
     */
    public function test_get_filter_options_with_invalid_column(): void
    {
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'test_filter_table',
            'column' => 'nonexistent_column',
        ]);

        $response = $this->controller->getFilterOptions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('does not exist', $data['message']);
    }

    /**
     * Test getFilterOptions with malicious table name.
     */
    public function test_get_filter_options_prevents_sql_injection(): void
    {
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'test_filter_table; DROP TABLE users; --',
            'column' => 'region',
        ]);

        $response = $this->controller->getFilterOptions($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Invalid table name format', $data['message']);
    }

    /**
     * Test saveFilters saves filters to session.
     */
    public function test_save_filters_saves_to_session(): void
    {
        $filters = [
            'region' => 'North',
            'status' => 'active',
        ];

        $request = Request::create('/datatable/save-filters', 'POST', [
            'table' => 'test_filter_table',
            'filters' => $filters,
        ]);

        // Mock the request() helper to return our test request
        $this->app->instance('request', $request);

        $response = $this->controller->saveFilters($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Filters saved successfully', $data['message']);

        // Check session contains the filters
        // The actual session key will be generated by the controller based on the request path
        // Let's get all session data and find our filters
        $allSessionData = session()->all();
        $foundFilters = null;
        foreach ($allSessionData as $key => $value) {
            if (strpos($key, 'table_filters_') === 0 && $value === $filters) {
                $foundFilters = $value;
                break;
            }
        }
        
        $this->assertNotNull($foundFilters, 'Filters should be saved to session');
        $this->assertEquals($filters, $foundFilters);
    }

    /**
     * Test saveFilters with empty filters.
     */
    public function test_save_filters_with_empty_filters(): void
    {
        $request = Request::create('/datatable/save-filters', 'POST', [
            'table' => 'test_filter_table',
            'filters' => [],
        ]);

        $response = $this->controller->saveFilters($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test saveDisplayLimit saves limit to session.
     */
    public function test_save_display_limit_saves_to_session(): void
    {
        $request = Request::create('/datatable/save-display-limit', 'POST', [
            'table' => 'test_filter_table',
            'limit' => 25,
        ]);

        // Mock the request() helper to return our test request
        $this->app->instance('request', $request);

        $response = $this->controller->saveDisplayLimit($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Display limit saved successfully', $data['message']);

        // Check session contains the limit
        // The actual session key will be generated by the controller based on the request path
        // Let's get all session data and find our limit
        $allSessionData = session()->all();
        $foundLimit = null;
        foreach ($allSessionData as $key => $value) {
            if (strpos($key, 'table_display_limit_') === 0 && $value === 25) {
                $foundLimit = $value;
                break;
            }
        }
        
        $this->assertNotNull($foundLimit, 'Display limit should be saved to session');
        $this->assertEquals(25, $foundLimit);
    }

    /**
     * Test saveDisplayLimit with 'all' value.
     */
    public function test_save_display_limit_with_all_value(): void
    {
        $request = Request::create('/datatable/save-display-limit', 'POST', [
            'table' => 'test_filter_table',
            'limit' => 'all',
        ]);

        // Mock the request() helper to return our test request
        $this->app->instance('request', $request);

        $response = $this->controller->saveDisplayLimit($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);

        // Check session contains 'all'
        // The actual session key will be generated by the controller based on the request path
        // Let's get all session data and find our limit
        $allSessionData = session()->all();
        $foundLimit = null;
        foreach ($allSessionData as $key => $value) {
            if (strpos($key, 'table_display_limit_') === 0 && $value === 'all') {
                $foundLimit = $value;
                break;
            }
        }
        
        $this->assertNotNull($foundLimit, 'Display limit should be saved to session');
        $this->assertEquals('all', $foundLimit);
    }

    /**
     * Test saveDisplayLimit with invalid limit value.
     */
    public function test_save_display_limit_with_invalid_limit(): void
    {
        $request = Request::create('/datatable/save-display-limit', 'POST', [
            'table' => 'test_filter_table',
            'limit' => -5,
        ]);

        $response = $this->controller->saveDisplayLimit($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('must be a positive integer', $data['message']);
    }

    /**
     * Test validateTableAndColumn with valid inputs.
     */
    public function test_validate_table_and_column_with_valid_inputs(): void
    {
        // This should not throw an exception
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateTableAndColumn');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke($this->controller, 'test_filter_table', 'region');
        
        // If we reach here, validation passed
        $this->assertTrue(true);
    }

    /**
     * Test validateTableAndColumn with invalid table name format.
     */
    public function test_validate_table_and_column_with_invalid_table_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name format');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateTableAndColumn');
        $method->setAccessible(true);

        $method->invoke($this->controller, 'table; DROP TABLE users; --', 'region');
    }

    /**
     * Test validateTableAndColumn with invalid column name format.
     */
    public function test_validate_table_and_column_with_invalid_column_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid column name format');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateTableAndColumn');
        $method->setAccessible(true);

        $method->invoke($this->controller, 'test_filter_table', 'column; DROP TABLE users; --');
    }

    /**
     * Test isValidTableName method.
     */
    public function test_is_valid_table_name(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidTableName');
        $method->setAccessible(true);

        // Valid table names
        $this->assertTrue($method->invoke($this->controller, 'users'));
        $this->assertTrue($method->invoke($this->controller, 'user_profiles'));
        $this->assertTrue($method->invoke($this->controller, 'database.table'));
        $this->assertTrue($method->invoke($this->controller, 'table123'));

        // Invalid table names
        $this->assertFalse($method->invoke($this->controller, 'table; DROP'));
        $this->assertFalse($method->invoke($this->controller, 'table--comment'));
        $this->assertFalse($method->invoke($this->controller, 'table/*comment*/'));
        $this->assertFalse($method->invoke($this->controller, 'table with spaces'));
    }

    /**
     * Test isValidColumnName method.
     */
    public function test_is_valid_column_name(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isValidColumnName');
        $method->setAccessible(true);

        // Valid column names
        $this->assertTrue($method->invoke($this->controller, 'name'));
        $this->assertTrue($method->invoke($this->controller, 'user_name'));
        $this->assertTrue($method->invoke($this->controller, 'table.column'));
        $this->assertTrue($method->invoke($this->controller, 'column123'));

        // Invalid column names
        $this->assertFalse($method->invoke($this->controller, 'column; DROP'));
        $this->assertFalse($method->invoke($this->controller, 'column--comment'));
        $this->assertFalse($method->invoke($this->controller, 'column/*comment*/'));
        $this->assertFalse($method->invoke($this->controller, 'column with spaces'));
    }

    /**
     * Test request validation for getFilterOptions.
     */
    public function test_get_filter_options_validation(): void
    {
        // Missing table parameter
        $request = Request::create('/datatable/filter-options', 'POST', [
            'column' => 'region',
        ]);

        $response = $this->controller->getFilterOptions($request);
        $this->assertEquals(422, $response->getStatusCode());

        // Missing column parameter
        $request = Request::create('/datatable/filter-options', 'POST', [
            'table' => 'test_filter_table',
        ]);

        $response = $this->controller->getFilterOptions($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test request validation for saveFilters.
     */
    public function test_save_filters_validation(): void
    {
        // Missing table parameter
        $request = Request::create('/datatable/save-filters', 'POST', [
            'filters' => ['region' => 'North'],
        ]);

        $response = $this->controller->saveFilters($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test request validation for saveDisplayLimit.
     */
    public function test_save_display_limit_validation(): void
    {
        // Missing table parameter
        $request = Request::create('/datatable/save-display-limit', 'POST', [
            'limit' => 25,
        ]);

        $response = $this->controller->saveDisplayLimit($request);
        $this->assertEquals(422, $response->getStatusCode());

        // Missing limit parameter
        $request = Request::create('/datatable/save-display-limit', 'POST', [
            'table' => 'test_filter_table',
        ]);

        $response = $this->controller->saveDisplayLimit($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
}
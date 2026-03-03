<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Http;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Feature test for DataTableController endpoints.
 */
class DataTableControllerFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test table for integration testing
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
        
        $capsule->getSchemaBuilder()->create('test_integration_table', function ($table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        // Insert test data
        $capsule->table('test_integration_table')->insert([
            ['category' => 'Electronics', 'subcategory' => 'Phones', 'status' => 'active'],
            ['category' => 'Electronics', 'subcategory' => 'Laptops', 'status' => 'active'],
            ['category' => 'Clothing', 'subcategory' => 'Shirts', 'status' => 'inactive'],
            ['category' => 'Clothing', 'subcategory' => 'Pants', 'status' => 'active'],
        ]);
    }

    /**
     * Drop test table after testing.
     */
    private function dropTestTable(): void
    {
        $capsule = Capsule::connection();
        
        if ($capsule->getSchemaBuilder()->hasTable('test_integration_table')) {
            $capsule->getSchemaBuilder()->drop('test_integration_table');
        }
    }

    /**
     * Test filter options endpoint returns correct response.
     */
    public function test_filter_options_endpoint_returns_correct_response(): void
    {
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'category',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'options' => [
                    '*' => [
                        'value',
                        'label',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['options']); // Electronics and Clothing
    }

    /**
     * Test filter options endpoint with cascading filters.
     */
    public function test_filter_options_endpoint_with_cascading_filters(): void
    {
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'subcategory',
            'parentFilters' => [
                'category' => 'Electronics',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['options']); // Phones and Laptops
        
        $subcategories = array_column($data['options'], 'value');
        $this->assertContains('Phones', $subcategories);
        $this->assertContains('Laptops', $subcategories);
        $this->assertNotContains('Shirts', $subcategories);
    }

    /**
     * Test filter options endpoint with invalid table.
     */
    public function test_filter_options_endpoint_with_invalid_table(): void
    {
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'nonexistent_table',
            'column' => 'category',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test save filters endpoint saves to session.
     */
    public function test_save_filters_endpoint_saves_to_session(): void
    {
        $filters = [
            'category' => 'Electronics',
            'status' => 'active',
        ];

        $response = $this->postJson('/datatable/save-filters', [
            'table' => 'test_integration_table',
            'filters' => $filters,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Filters saved successfully',
            ]);

        // Verify session contains the filters
        $sessionKey = 'table_filters_' . md5('test_integration_table' . '_' . '/datatable/save-filters');
        $this->assertEquals($filters, session($sessionKey));
    }

    /**
     * Test save filters endpoint with validation error.
     */
    public function test_save_filters_endpoint_validation_error(): void
    {
        $response = $this->postJson('/datatable/save-filters', [
            'filters' => ['category' => 'Electronics'],
            // Missing table parameter
        ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test save display limit endpoint saves to session.
     */
    public function test_save_display_limit_endpoint_saves_to_session(): void
    {
        $response = $this->postJson('/datatable/save-display-limit', [
            'table' => 'test_integration_table',
            'limit' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Display limit saved successfully',
            ]);

        // Verify session contains the limit
        $sessionKey = 'table_display_limit_' . md5('test_integration_table' . '_' . '/datatable/save-display-limit');
        $this->assertEquals(50, session($sessionKey));
    }

    /**
     * Test save display limit endpoint with 'all' value.
     */
    public function test_save_display_limit_endpoint_with_all_value(): void
    {
        $response = $this->postJson('/datatable/save-display-limit', [
            'table' => 'test_integration_table',
            'limit' => 'all',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify session contains 'all'
        $sessionKey = 'table_display_limit_' . md5('test_integration_table' . '_' . '/datatable/save-display-limit');
        $this->assertEquals('all', session($sessionKey));
    }

    /**
     * Test save display limit endpoint with validation error.
     */
    public function test_save_display_limit_endpoint_validation_error(): void
    {
        $response = $this->postJson('/datatable/save-display-limit', [
            'limit' => 25,
            // Missing table parameter
        ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test CSRF protection is applied to endpoints.
     */
    public function test_endpoints_require_csrf_token(): void
    {
        // Test without CSRF token should fail
        $response = $this->post('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'category',
        ]);

        // Should fail due to CSRF protection
        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test endpoints handle malformed JSON gracefully.
     */
    public function test_endpoints_handle_malformed_json(): void
    {
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'category',
            'parentFilters' => 'invalid_json_structure', // Should be array
        ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test endpoints handle SQL injection attempts.
     */
    public function test_endpoints_prevent_sql_injection(): void
    {
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table; DROP TABLE users; --',
            'column' => 'category',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        $data = $response->json();
        $this->assertStringContainsString('Invalid table name format', $data['message']);
    }

    /**
     * Test endpoints handle large datasets efficiently.
     */
    public function test_endpoints_handle_large_datasets(): void
    {
        // Insert more test data
        $capsule = Capsule::connection();
        $largeData = [];
        
        for ($i = 1; $i <= 100; $i++) {
            $largeData[] = [
                'category' => 'Category' . ($i % 10),
                'subcategory' => 'Subcategory' . $i,
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
            ];
        }
        
        $capsule->table('test_integration_table')->insert($largeData);

        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'category',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertGreaterThan(10, count($data['options'])); // Should have many categories
    }

    /**
     * Test endpoints return consistent response format.
     */
    public function test_endpoints_return_consistent_response_format(): void
    {
        // Test successful response format
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'test_integration_table',
            'column' => 'category',
        ]);

        $response->assertJsonStructure([
            'success',
            'options',
        ]);

        // Test error response format
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'nonexistent_table',
            'column' => 'category',
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'options',
        ]);
    }
}
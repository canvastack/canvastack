<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Feature tests for server-side processing functionality.
 *
 * Tests Requirements:
 * - 6.1: Server-side processing support
 * - 6.2: TanStackServerAdapter implementation
 * - 6.3: Query building with eager loading
 * - 6.4: Sorting and pagination
 * - 6.5: Global and column filtering
 * - 6.6: Custom filters
 * - 6.7: Request/response normalization
 */
class ServerSideProcessingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->createTestUsers();
        
        // Register test routes
        $this->registerTestRoutes();
    }

    /**
     * Create test users for testing.
     */
    protected function createTestUsers(): void
    {
        // Create 50 test users with various data
        for ($i = 1; $i <= 50; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'role' => $i % 3 === 0 ? 'admin' : 'user',
                'created_at' => now()->subDays($i),
            ]);
        }
    }

    /**
     * Register test routes for server-side processing.
     */
    protected function registerTestRoutes(): void
    {
        // Routes are not needed - we'll call processServerSide() directly
    }

    /**
     * Make a POST JSON request to the given URI.
     * 
     * For testing purposes, this directly calls the TableBuilder's processServerSide() method
     * instead of going through HTTP routing.
     *
     * @param string $uri
     * @param array $data
     * @return \stdClass
     */
    protected function postJson(string $uri, array $data = []): \stdClass
    {
        try {
            // Create a mock request with the data
            $request = new \Illuminate\Http\Request();
            $request->replace($data);
            
            // Bind request to container so processServerSide() can access it
            $this->app->instance('request', $request);
            
            // Use container to create TableBuilder with correct dependencies
            // Constructor signature: __construct(QueryOptimizer, FilterBuilder, SchemaInspector, ColumnValidator)
            $table = new TableBuilder(
                $this->app->make(\Canvastack\Canvastack\Components\Table\Query\QueryOptimizer::class),
                $this->app->make(\Canvastack\Canvastack\Components\Table\Query\FilterBuilder::class),
                $this->app->make(\Canvastack\Canvastack\Components\Table\Validation\SchemaInspector::class),
                $this->app->make(\Canvastack\Canvastack\Components\Table\Validation\ColumnValidator::class)
            );
            
            $table->setContext('admin');
            $table->setModel(new User());
            $table->setFields([
                'id',
                'name',
                'email',
                'status',
                'role',
                'created_at',
            ]);
            
            $responseData = $table->processServerSide();
            
            // Create a response object
            $result = new \stdClass();
            $result->status = 200;
            $result->content = json_encode($responseData);
            $result->data = $responseData;
            
            return $result;
        } catch (\Exception $e) {
            // Create error response
            $result = new \stdClass();
            $result->status = 500;
            $result->content = json_encode(['error' => $e->getMessage()]);
            $result->data = ['error' => $e->getMessage()];
            
            return $result;
        }
    }
    
    /**
     * Assert that the response has the given status code.
     *
     * @param int $status
     * @return void
     */
    protected function assertStatus(\stdClass $response, int $status): void
    {
        if ($response->status !== $status) {
            // Show error details if status doesn't match
            $errorMsg = "Expected status {$status}, got {$response->status}";
            if (isset($response->data['error'])) {
                $errorMsg .= "\nError: " . $response->data['error'];
            }
            $this->assertEquals($status, $response->status, $errorMsg);
        }
        $this->assertEquals($status, $response->status);
    }
    
    /**
     * Assert that the response has the given JSON structure.
     *
     * @param array $structure
     * @return void
     */
    protected function assertJsonStructure(\stdClass $response, array $structure): void
    {
        $data = $response->data;
        
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
                
                if (isset($value['*'])) {
                    // Check array items
                    $this->assertIsArray($data[$key], "Key {$key} should be an array");
                    if (!empty($data[$key])) {
                        // Recursively check first item structure
                        foreach ($value['*'] as $itemKey) {
                            $this->assertArrayHasKey($itemKey, $data[$key][0], "Missing key in array item: {$itemKey}");
                        }
                    }
                } else {
                    // Check nested structure recursively
                    foreach ($value as $nestedKey) {
                        $this->assertArrayHasKey($nestedKey, $data[$key], "Missing nested key: {$nestedKey}");
                    }
                }
            } else {
                $this->assertArrayHasKey($value, $data, "Missing key: {$value}");
            }
        }
    }
    
    /**
     * Get JSON data from response.
     *
     * @return array
     */
    protected function json(\stdClass $response): array
    {
        return $response->data;
    }

    /**
     * Test basic server-side processing request.
     *
     * @return void
     */
    public function test_basic_server_side_processing(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
        ]);

        $this->assertStatus($response, 200);
        $this->assertJsonStructure($response, [
            'data',
            'meta' => [
                'total',
                'filtered',
                'page',
                'pageSize',
                'pageCount',
            ],
        ]);

        $data = $this->json($response);
        
        $this->assertCount(10, $data['data']);
        $this->assertEquals(50, $data['meta']['total']);
        $this->assertEquals(50, $data['meta']['filtered']);
        $this->assertEquals(1, $data['meta']['page']);
        $this->assertEquals(10, $data['meta']['pageSize']);
        $this->assertEquals(5, $data['meta']['pageCount']);
    }

    /**
     * Test pagination with different page sizes.
     *
     * @return void
     */
    public function test_pagination_with_different_page_sizes(): void
    {
        // Test page size 25
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 25,
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertCount(25, $data['data']);
        $this->assertEquals(2, $data['meta']['pageCount']);

        // Test page size 50
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 50,
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertCount(50, $data['data']);
        $this->assertEquals(1, $data['meta']['pageCount']);
    }

    /**
     * Test pagination navigation (first, previous, next, last).
     *
     * @return void
     */
    public function test_pagination_navigation(): void
    {
        // First page
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
        ]);

        $data = $this->json($response);
        $this->assertEquals(1, $data['data'][0]['id']);

        // Second page
        $response = $this->postJson('/test/table/server-side', [
            'page' => 2,
            'pageSize' => 10,
        ]);

        $data = $this->json($response);
        $this->assertEquals(11, $data['data'][0]['id']);

        // Last page
        $response = $this->postJson('/test/table/server-side', [
            'page' => 5,
            'pageSize' => 10,
        ]);

        $data = $this->json($response);
        $this->assertCount(10, $data['data']);
        $this->assertEquals(41, $data['data'][0]['id']);
    }

    /**
     * Test sorting by single column ascending.
     *
     * @return void
     */
    public function test_sorting_single_column_ascending(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => 'name', 'desc' => false],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertEquals('User 1', $data['data'][0]['name']);
        $this->assertEquals('User 10', $data['data'][1]['name']);
    }

    /**
     * Test sorting by single column descending.
     *
     * @return void
     */
    public function test_sorting_single_column_descending(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => 'name', 'desc' => true],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertEquals('User 9', $data['data'][0]['name']);
    }

    /**
     * Test sorting by multiple columns.
     *
     * @return void
     */
    public function test_sorting_multiple_columns(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => 'status', 'desc' => false],
                ['id' => 'name', 'desc' => false],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        // First should be active users sorted by name (alphabetically 'active' < 'inactive')
        $this->assertEquals('active', $data['data'][0]['status']);
    }

    /**
     * Test global search filtering.
     *
     * @return void
     */
    public function test_global_search_filtering(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'globalFilter' => 'User 1',
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        // Should match User 1, User 10-19
        $this->assertGreaterThan(0, count($data['data']));
        $this->assertLessThan(50, $data['meta']['filtered']);
        
        foreach ($data['data'] as $row) {
            $this->assertStringContainsString('User 1', $row['name']);
        }
    }

    /**
     * Test column-specific filtering.
     *
     * @return void
     */
    public function test_column_specific_filtering(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'columnFilters' => [
                ['id' => 'status', 'value' => 'active'],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertEquals(25, $data['meta']['filtered']); // 50% are active
        
        foreach ($data['data'] as $row) {
            $this->assertEquals('active', $row['status']);
        }
    }

    /**
     * Test multiple column filters.
     *
     * @return void
     */
    public function test_multiple_column_filters(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'columnFilters' => [
                ['id' => 'status', 'value' => 'active'],
                ['id' => 'role', 'value' => 'admin'],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertLessThan(25, $data['meta']['filtered']);
        
        foreach ($data['data'] as $row) {
            $this->assertEquals('active', $row['status']);
            $this->assertEquals('admin', $row['role']);
        }
    }

    /**
     * Test combined global and column filtering.
     *
     * @return void
     */
    public function test_combined_global_and_column_filtering(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'globalFilter' => 'User 1',
            'columnFilters' => [
                ['id' => 'status', 'value' => 'active'],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertGreaterThan(0, count($data['data']));
        
        foreach ($data['data'] as $row) {
            $this->assertStringContainsString('User 1', $row['name']);
            $this->assertEquals('active', $row['status']);
        }
    }

    /**
     * Test sorting with filtering.
     *
     * @return void
     */
    public function test_sorting_with_filtering(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => 'name', 'desc' => true],
            ],
            'columnFilters' => [
                ['id' => 'status', 'value' => 'active'],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertEquals('active', $data['data'][0]['status']);
        
        // Verify descending order
        $names = array_column($data['data'], 'name');
        $sortedNames = $names;
        rsort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    /**
     * Test pagination with filtering.
     *
     * @return void
     */
    public function test_pagination_with_filtering(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'columnFilters' => [
                ['id' => 'status', 'value' => 'active'],
            ],
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertEquals(25, $data['meta']['filtered']);
        $this->assertEquals(3, $data['meta']['pageCount']); // 25 / 10 = 3 pages
    }

    /**
     * Test empty result set.
     *
     * @return void
     */
    public function test_empty_result_set(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'globalFilter' => 'NonExistentUser',
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        $this->assertCount(0, $data['data']);
        $this->assertEquals(0, $data['meta']['filtered']);
        $this->assertEquals(50, $data['meta']['total']);
    }

    /**
     * Test invalid page number.
     *
     * @return void
     */
    public function test_invalid_page_number(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 999,
            'pageSize' => 10,
        ]);

        $this->assertStatus($response, 200);
        $data = $this->json($response);
        
        // Should return empty data for out-of-range page
        $this->assertCount(0, $data['data']);
    }

    /**
     * Test invalid sort column.
     *
     * @return void
     */
    public function test_invalid_sort_column(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => 'invalid_column', 'desc' => false],
            ],
        ]);

        // Should either ignore invalid column or return error
        $this->assertStatus($response, 200);
    }

    /**
     * Test SQL injection prevention in filters.
     *
     * @return void
     */
    public function test_sql_injection_prevention_in_filters(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'globalFilter' => "'; DROP TABLE users; --",
        ]);

        $this->assertStatus($response, 200);
        
        // Verify users table still exists
        $this->assertDatabaseCount('users', 50);
    }

    /**
     * Test SQL injection prevention in sorting.
     *
     * @return void
     */
    public function test_sql_injection_prevention_in_sorting(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
            'sorting' => [
                ['id' => "name'; DROP TABLE users; --", 'desc' => false],
            ],
        ]);

        $this->assertStatus($response, 200);
        
        // Verify users table still exists
        $this->assertDatabaseCount('users', 50);
    }

    /**
     * Test response structure matches TanStack Table format.
     *
     * @return void
     */
    public function test_response_structure_matches_tanstack_format(): void
    {
        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 10,
        ]);

        $this->assertStatus($response, 200);
        $this->assertJsonStructure($response, [
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'role',
                    'created_at',
                ],
            ],
            'meta' => [
                'total',
                'filtered',
                'page',
                'pageSize',
                'pageCount',
            ],
        ]);
    }

    /**
     * Test performance with large page size.
     *
     * @return void
     */
    public function test_performance_with_large_page_size(): void
    {
        $startTime = microtime(true);

        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 100,
        ]);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertStatus($response, 200);
        
        // Should complete in less than 500ms
        $this->assertLessThan(500, $duration);
    }

    /**
     * Test memory usage with large dataset.
     *
     * @return void
     */
    public function test_memory_usage_with_large_dataset(): void
    {
        $memoryBefore = memory_get_usage();

        $response = $this->postJson('/test/table/server-side', [
            'page' => 1,
            'pageSize' => 100,
        ]);

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $this->assertStatus($response, 200);
        
        // Should use less than 10MB
        $this->assertLessThan(10, $memoryUsed);
    }
}

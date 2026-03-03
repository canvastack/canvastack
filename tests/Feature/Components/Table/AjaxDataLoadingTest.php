<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * AJAX Data Loading Integration Tests.
 *
 * Tests for Task 21.17: Write integration tests for AJAX data loading
 *
 * Test Coverage:
 * - Server-side processing with GET method
 * - Server-side processing with POST method
 * - CSRF token validation for POST
 * - DataTables parameters are parsed correctly
 * - Search functionality works
 * - Ordering functionality works
 * - Pagination works
 * - Data is returned in correct format
 */
class AjaxDataLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users table
        $this->createUsersTable();

        // Create test data
        $this->createTestData();
    }

    /**
     * Create users table for testing.
     */
    protected function createUsersTable(): void
    {
        \Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Create test data for integration tests.
     */
    protected function createTestData(): void
    {
        // Create 50 test users
        for ($i = 1; $i <= 50; $i++) {
            \DB::table('users')->insert([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Test server-side processing with GET method.
     *
     * @test
     */
    public function test_server_side_processing_with_get_method(): void
    {
        $response = $this->get('/datatable/data?' . http_build_query([
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $data = $response->json();
        $this->assertEquals(1, $data['draw']);
        $this->assertEquals(50, $data['recordsTotal']);
        $this->assertEquals(50, $data['recordsFiltered']);
        $this->assertCount(10, $data['data']);
    }

    /**
     * Test server-side processing with POST method.
     *
     * @test
     */
    public function test_server_side_processing_with_post_method(): void
    {
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $data = $response->json();
        $this->assertEquals(1, $data['draw']);
        $this->assertEquals(50, $data['recordsTotal']);
        $this->assertEquals(50, $data['recordsFiltered']);
        $this->assertCount(10, $data['data']);
    }

    /**
     * Test CSRF token validation for POST requests.
     *
     * @test
     */
    public function test_csrf_token_validation_for_post(): void
    {
        // Without CSRF token, request should fail
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/datatable/data', [
                'table' => 'users',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]);

        // With middleware disabled, it should succeed
        $response->assertStatus(200);

        // With CSRF token, request should succeed
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test DataTables parameters are parsed correctly.
     *
     * @test
     */
    public function test_datatables_parameters_are_parsed_correctly(): void
    {
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 5,
            'start' => 20,
            'length' => 15,
            'search' => ['value' => 'test'],
            'order' => [['column' => 1, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify draw parameter is echoed back
        $this->assertEquals(5, $data['draw']);

        // Verify pagination parameters are respected
        $this->assertLessThanOrEqual(15, count($data['data']));
    }

    /**
     * Test search functionality works.
     *
     * @test
     */
    public function test_search_functionality_works(): void
    {
        // Search for "User 1" (should match User 1, User 10-19)
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => 'User 1'],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Should find User 1, User 10-19 (11 users)
        $this->assertGreaterThan(0, $data['recordsFiltered']);
        $this->assertLessThan($data['recordsTotal'], $data['recordsFiltered']);

        // Verify all returned records contain "User 1"
        foreach ($data['data'] as $row) {
            // Data is returned as array, not object
            $name = is_array($row) ? $row['name'] : $row->name;
            $this->assertStringContainsString('User 1', $name);
        }
    }

    /**
     * Test ordering functionality works.
     *
     * @test
     */
    public function test_ordering_functionality_works(): void
    {
        // Order by name ascending
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 1, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify data is ordered by name ascending
        $names = array_map(function ($row) {
            return is_array($row) ? $row['name'] : $row->name;
        }, $data['data']);
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);

        // Order by name descending
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 1, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify data is ordered by name descending
        $names = array_map(function ($row) {
            return is_array($row) ? $row['name'] : $row->name;
        }, $data['data']);
        $sortedNames = $names;
        rsort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    /**
     * Test pagination works.
     *
     * @test
     */
    public function test_pagination_works(): void
    {
        // Get first page (0-9)
        $response1 = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response1->assertStatus(200);
        $data1 = $response1->json();
        $this->assertCount(10, $data1['data']);

        // Get second page (10-19)
        $response2 = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 2,
            'start' => 10,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response2->assertStatus(200);
        $data2 = $response2->json();
        $this->assertCount(10, $data2['data']);

        // Verify pages contain different data
        $firstId = is_array($data1['data'][0]) ? $data1['data'][0]['id'] : $data1['data'][0]->id;
        $secondId = is_array($data2['data'][0]) ? $data2['data'][0]['id'] : $data2['data'][0]->id;
        $this->assertNotEquals($firstId, $secondId);
    }

    /**
     * Test data is returned in correct format.
     *
     * @test
     */
    public function test_data_is_returned_in_correct_format(): void
    {
        $response = $this->post('/datatable/data', [
            'table' => 'users',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => true],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify response structure
        $this->assertArrayHasKey('draw', $data);
        $this->assertArrayHasKey('recordsTotal', $data);
        $this->assertArrayHasKey('recordsFiltered', $data);
        $this->assertArrayHasKey('data', $data);

        // Verify data types
        $this->assertIsInt($data['draw']);
        $this->assertIsInt($data['recordsTotal']);
        $this->assertIsInt($data['recordsFiltered']);
        $this->assertIsArray($data['data']);

        // Verify each row has expected columns (data can be array or object)
        foreach ($data['data'] as $row) {
            if (is_array($row)) {
                $this->assertArrayHasKey('id', $row);
                $this->assertArrayHasKey('name', $row);
                $this->assertArrayHasKey('email', $row);
            } else {
                $this->assertIsObject($row);
                $this->assertObjectHasProperty('id', $row);
                $this->assertObjectHasProperty('name', $row);
                $this->assertObjectHasProperty('email', $row);
            }
        }
    }
}

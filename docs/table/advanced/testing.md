# Testing

Comprehensive testing is crucial for ensuring CanvaStack Table functionality works correctly across different scenarios. This guide covers unit testing, integration testing, and end-to-end testing approaches.

## Table of Contents

- [Testing Setup](#testing-setup)
- [Unit Testing](#unit-testing)
- [Integration Testing](#integration-testing)
- [Feature Testing](#feature-testing)
- [Performance Testing](#performance-testing)
- [Security Testing](#security-testing)
- [Browser Testing](#browser-testing)
- [Testing Best Practices](#testing-best-practices)

## Testing Setup

### Basic Test Configuration

Set up your testing environment:

```php
<?php

namespace Tests\Feature\Table;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TableTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $admin;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->department = Department::factory()->create([
            'name' => 'Test Department'
        ]);

        $this->user = User::factory()->create([
            'department_id' => $this->department->id
        ]);

        $this->admin = User::factory()->create([
            'department_id' => $this->department->id
        ]);
        $this->admin->assignRole('admin');

        // Set up test database
        $this->artisan('migrate:fresh');
        $this->seed();
    }

    protected function createTestUsers($count = 10)
    {
        return User::factory()
                   ->count($count)
                   ->create([
                       'department_id' => $this->department->id
                   ]);
    }

    protected function getTableResponse($parameters = [])
    {
        return $this->actingAs($this->user)
                    ->postJson('/table/users', $parameters);
    }
}
```

### Test Database Configuration

Configure test database in `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Table">
            <directory suffix="Test.php">./tests/Feature/Table</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

## Unit Testing

### Testing Table Configuration

Test basic table configuration:

```php
<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use App\Http\Controllers\UserController;
use Canvastack\Canvastack\Objects\Objects;

class TableConfigurationTest extends TestCase
{
    public function test_table_initialization()
    {
        $controller = new UserController();
        $table = new Objects();

        $this->assertInstanceOf(Objects::class, $table);
        $this->assertFalse($table->isSearchable());
        $this->assertFalse($table->isSortable());
    }

    public function test_searchable_configuration()
    {
        $table = new Objects();
        $table->searchable();

        $this->assertTrue($table->isSearchable());
    }

    public function test_sortable_configuration()
    {
        $table = new Objects();
        $table->sortable();

        $this->assertTrue($table->isSortable());
    }

    public function test_method_configuration()
    {
        $table = new Objects();
        
        // Default method should be GET
        $this->assertEquals('GET', $table->getMethod());
        
        // Test POST method
        $table->method('POST');
        $this->assertEquals('POST', $table->getMethod());
    }

    public function test_column_configuration()
    {
        $table = new Objects();
        $columns = ['name', 'email', 'created_at'];
        
        $table->setColumns($columns);
        
        $this->assertEquals($columns, $table->getColumns());
    }

    public function test_relationship_configuration()
    {
        $table = new Objects();
        $model = new \App\Models\User();
        
        $table->relations($model, 'department', 'name');
        
        $relations = $table->getRelations();
        $this->assertArrayHasKey('department', $relations);
        $this->assertEquals('name', $relations['department']);
    }
}
```

### Testing Data Processing

Test data processing logic:

```php
<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use App\Models\User;
use Canvastack\Canvastack\Objects\Objects;

class DataProcessingTest extends TestCase
{
    public function test_data_transformation()
    {
        $users = User::factory()->count(5)->create();
        $table = new Objects();
        
        $transformedData = $table->transformData($users->toArray());
        
        $this->assertIsArray($transformedData);
        $this->assertCount(5, $transformedData);
    }

    public function test_search_filtering()
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'Bob Johnson']);

        $table = new Objects();
        $table->searchable();
        
        $filteredData = $table->applySearch(User::query(), 'John')->get();
        
        $this->assertCount(2, $filteredData); // John Doe and Bob Johnson
    }

    public function test_column_sorting()
    {
        User::factory()->create(['name' => 'Charlie', 'created_at' => now()->subDays(3)]);
        User::factory()->create(['name' => 'Alice', 'created_at' => now()->subDays(1)]);
        User::factory()->create(['name' => 'Bob', 'created_at' => now()->subDays(2)]);

        $table = new Objects();
        $table->sortable();
        
        // Test name sorting
        $sortedByName = $table->applySorting(User::query(), 'name', 'ASC')->get();
        $this->assertEquals('Alice', $sortedByName->first()->name);
        
        // Test date sorting
        $sortedByDate = $table->applySorting(User::query(), 'created_at', 'DESC')->get();
        $this->assertEquals('Alice', $sortedByDate->first()->name);
    }

    public function test_pagination()
    {
        User::factory()->count(25)->create();

        $table = new Objects();
        
        $paginatedData = $table->applyPagination(User::query(), 0, 10)->get();
        
        $this->assertCount(10, $paginatedData);
    }
}
```

### Testing Security Features

Test security implementations:

```php
<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use Canvastack\Canvastack\Security\InputValidator;
use Canvastack\Canvastack\Security\SqlInjectionDetector;

class SecurityTest extends TestCase
{
    public function test_sql_injection_detection()
    {
        $detector = new SqlInjectionDetector();
        
        // Test malicious inputs
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "UNION SELECT * FROM users",
            "<script>alert('xss')</script>"
        ];
        
        foreach ($maliciousInputs as $input) {
            $this->assertTrue($detector->isMalicious($input));
        }
        
        // Test safe inputs
        $safeInputs = [
            "John Doe",
            "john@example.com",
            "2023-01-01",
            "Normal search term"
        ];
        
        foreach ($safeInputs as $input) {
            $this->assertFalse($detector->isMalicious($input));
        }
    }

    public function test_input_validation()
    {
        $validator = new InputValidator();
        
        // Test valid DataTables request
        $validRequest = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test'],
            'order' => [['column' => 0, 'dir' => 'asc']]
        ];
        
        $this->assertTrue($validator->validate($validRequest));
        
        // Test invalid request
        $invalidRequest = [
            'draw' => 'invalid',
            'start' => -1,
            'length' => 10001
        ];
        
        $this->assertFalse($validator->validate($invalidRequest));
    }

    public function test_xss_prevention()
    {
        $table = new Objects();
        
        $maliciousData = [
            'name' => '<script>alert("xss")</script>',
            'description' => '<img src="x" onerror="alert(1)">'
        ];
        
        $sanitizedData = $table->sanitizeData($maliciousData);
        
        $this->assertStringNotContainsString('<script>', $sanitizedData['name']);
        $this->assertStringNotContainsString('onerror', $sanitizedData['description']);
    }
}
```

## Integration Testing

### Testing Controller Integration

Test full controller functionality:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class UserTableIntegrationTest extends TableTestCase
{
    public function test_user_table_renders_successfully()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/users');
        
        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertViewHas('table');
    }

    public function test_ajax_data_loading()
    {
        $this->createTestUsers(15);
        
        $response = $this->getTableResponse([
            'draw' => 1,
            'start' => 0,
            'length' => 10
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data'
        ]);
        
        $data = $response->json();
        $this->assertEquals(1, $data['draw']);
        $this->assertEquals(15, $data['recordsTotal']);
        $this->assertCount(10, $data['data']);
    }

    public function test_search_functionality()
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        
        $response = $this->getTableResponse([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'John']
        ]);
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(1, $data['recordsFiltered']);
        $this->assertStringContainsString('John', $data['data'][0]['name']);
    }

    public function test_sorting_functionality()
    {
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        
        $response = $this->getTableResponse([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 0, 'dir' => 'asc']]
        ]);
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals('Alice', $data['data'][0]['name']);
        $this->assertEquals('Bob', $data['data'][1]['name']);
        $this->assertEquals('Charlie', $data['data'][2]['name']);
    }

    public function test_filtering_functionality()
    {
        $activeDept = Department::factory()->create(['name' => 'Active Dept']);
        $inactiveDept = Department::factory()->create(['name' => 'Inactive Dept']);
        
        User::factory()->count(3)->create(['department_id' => $activeDept->id]);
        User::factory()->count(2)->create(['department_id' => $inactiveDept->id]);
        
        $response = $this->getTableResponse([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filters' => ['department_id' => $activeDept->id]
        ]);
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(3, $data['recordsFiltered']);
    }
}
```

### Testing Relationship Integration

Test relationship functionality:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class RelationshipIntegrationTest extends TableTestCase
{
    public function test_belongs_to_relationship_display()
    {
        $user = User::factory()->create([
            'department_id' => $this->department->id
        ]);
        
        $response = $this->getTableResponse();
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $userData = collect($data['data'])->firstWhere('id', $user->id);
        
        $this->assertEquals($this->department->name, $userData['department_name']);
    }

    public function test_has_many_relationship_count()
    {
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['user_id' => $user->id]);
        
        $response = $this->getTableResponse();
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $userData = collect($data['data'])->firstWhere('id', $user->id);
        
        $this->assertEquals(5, $userData['orders_count']);
    }

    public function test_many_to_many_relationship_display()
    {
        $user = User::factory()->create();
        $roles = Role::factory()->count(3)->create();
        $user->roles()->attach($roles);
        
        $response = $this->getTableResponse();
        
        $response->assertStatus(200);
        
        $data = $response->json();
        $userData = collect($data['data'])->firstWhere('id', $user->id);
        
        $this->assertStringContainsString($roles->first()->name, $userData['roles']);
    }
}
```

## Feature Testing

### Testing Export Functionality

Test data export features:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class ExportFunctionalityTest extends TableTestCase
{
    public function test_excel_export()
    {
        $this->createTestUsers(10);
        
        $response = $this->actingAs($this->user)
                         ->post('/users/export', [
                             'format' => 'excel',
                             'columns' => ['name', 'email', 'created_at']
                         ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition');
    }

    public function test_csv_export()
    {
        $this->createTestUsers(5);
        
        $response = $this->actingAs($this->user)
                         ->post('/users/export', [
                             'format' => 'csv',
                             'columns' => ['name', 'email']
                         ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        
        $content = $response->getContent();
        $this->assertStringContainsString('name,email', $content);
    }

    public function test_pdf_export()
    {
        $this->createTestUsers(3);
        
        $response = $this->actingAs($this->user)
                         ->post('/users/export', [
                             'format' => 'pdf',
                             'columns' => ['name', 'email']
                         ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_export_with_filters()
    {
        User::factory()->create(['name' => 'John Doe', 'active' => true]);
        User::factory()->create(['name' => 'Jane Smith', 'active' => false]);
        
        $response = $this->actingAs($this->user)
                         ->post('/users/export', [
                             'format' => 'csv',
                             'columns' => ['name', 'active'],
                             'filters' => ['active' => true]
                         ]);
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringNotContainsString('Jane Smith', $content);
    }
}
```

### Testing Action Functionality

Test action buttons and operations:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class ActionFunctionalityTest extends TableTestCase
{
    public function test_view_action()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($this->admin)
                         ->get("/users/{$user->id}");
        
        $response->assertStatus(200);
        $response->assertViewIs('users.show');
        $response->assertViewHas('user', $user);
    }

    public function test_edit_action()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($this->admin)
                         ->get("/users/{$user->id}/edit");
        
        $response->assertStatus(200);
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user', $user);
    }

    public function test_delete_action()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($this->admin)
                         ->delete("/users/{$user->id}");
        
        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_bulk_delete_action()
    {
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();
        
        $response = $this->actingAs($this->admin)
                         ->post('/users/bulk-delete', [
                             'ids' => $userIds
                         ]);
        
        $response->assertStatus(200);
        
        foreach ($userIds as $id) {
            $this->assertSoftDeleted('users', ['id' => $id]);
        }
    }

    public function test_custom_action()
    {
        $user = User::factory()->create(['active' => false]);
        
        $response = $this->actingAs($this->admin)
                         ->post("/users/{$user->id}/activate");
        
        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertTrue($user->active);
    }
}
```

## Performance Testing

### Testing Large Datasets

Test performance with large datasets:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class PerformanceTest extends TableTestCase
{
    public function test_large_dataset_performance()
    {
        // Create large dataset
        User::factory()->count(10000)->create();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $this->getTableResponse([
            'draw' => 1,
            'start' => 0,
            'length' => 100
        ]);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $response->assertStatus(200);
        
        // Performance assertions
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        $this->assertLessThan(2.0, $executionTime, 'Query should complete within 2 seconds');
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, 'Memory usage should be under 50MB');
    }

    public function test_server_side_processing_performance()
    {
        User::factory()->count(5000)->create();
        
        $startTime = microtime(true);
        
        $response = $this->postJson('/users/ajax', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']]
        ]);
        
        $endTime = microtime(true);
        
        $response->assertStatus(200);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, 'Server-side processing should be fast');
    }

    public function test_memory_usage_with_relationships()
    {
        // Create users with relationships
        $users = User::factory()->count(1000)->create();
        foreach ($users as $user) {
            Order::factory()->count(5)->create(['user_id' => $user->id]);
        }
        
        $startMemory = memory_get_usage();
        
        $response = $this->getTableResponse();
        
        $endMemory = memory_get_usage();
        $memoryUsage = $endMemory - $startMemory;
        
        $response->assertStatus(200);
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsage, 'Memory usage with relationships should be reasonable');
    }
}
```

## Security Testing

### Testing Authentication and Authorization

Test security features:

```php
<?php

namespace Tests\Feature\Table;

use Tests\Feature\Table\TableTestCase;

class SecurityTest extends TableTestCase
{
    public function test_unauthenticated_access_denied()
    {
        $response = $this->get('/users');
        
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_access_denied()
    {
        $unauthorizedUser = User::factory()->create();
        
        $response = $this->actingAs($unauthorizedUser)
                         ->get('/admin/users');
        
        $response->assertStatus(403);
    }

    public function test_sql_injection_prevention()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->getTableResponse([
            'search' => ['value' => $maliciousInput]
        ]);
        
        $response->assertStatus(200);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    public function test_xss_prevention()
    {
        $xssInput = '<script>alert("xss")</script>';
        
        $response = $this->getTableResponse([
            'search' => ['value' => $xssInput]
        ]);
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function test_csrf_protection()
    {
        $response = $this->post('/users/bulk-delete', [
            'ids' => [1, 2, 3]
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_rate_limiting()
    {
        // Make multiple rapid requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getTableResponse();
            
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode());
                return;
            }
        }
        
        $this->markTestSkipped('Rate limiting not triggered');
    }
}
```

## Browser Testing

### Laravel Dusk Tests

Test frontend functionality with browser automation:

```php
<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class TableBrowserTest extends DuskTestCase
{
    public function test_table_loads_correctly()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->assertSee('Users')
                    ->assertPresent('.dataTables_wrapper');
        });
    }

    public function test_search_functionality()
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->type('input[type="search"]', 'John')
                    ->waitFor('.dataTables_processing', 5, false)
                    ->assertSee('John Doe')
                    ->assertDontSee('Jane Smith');
        });
    }

    public function test_sorting_functionality()
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->click('th:contains("Name")')
                    ->waitFor('.dataTables_processing', 5, false)
                    ->assertSeeIn('tbody tr:first-child', 'Alice');
        });
    }

    public function test_pagination()
    {
        $user = User::factory()->create();
        User::factory()->count(25)->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->assertSee('Next')
                    ->click('.paginate_button.next')
                    ->waitFor('.dataTables_processing', 5, false)
                    ->assertPresent('tbody tr');
        });
    }

    public function test_filter_modal()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->click('.filter-button')
                    ->waitFor('.filter-modal')
                    ->assertVisible('.filter-modal')
                    ->select('select[name="status"]', 'active')
                    ->click('.apply-filters')
                    ->waitFor('.dataTables_processing', 5, false);
        });
    }

    public function test_export_functionality()
    {
        $user = User::factory()->create();
        User::factory()->count(5)->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/users')
                    ->waitFor('#users-table')
                    ->click('.export-dropdown')
                    ->click('.export-excel')
                    ->waitFor(3); // Wait for download to start
        });
    }
}
```

## Testing Best Practices

### Test Organization

Organize tests effectively:

```php
<?php

namespace Tests\Feature\Table;

/**
 * Test Structure:
 * 
 * tests/
 * ├── Unit/
 * │   ├── Table/
 * │   │   ├── ConfigurationTest.php
 * │   │   ├── DataProcessingTest.php
 * │   │   └── SecurityTest.php
 * ├── Feature/
 * │   ├── Table/
 * │   │   ├── TableTestCase.php (Base class)
 * │   │   ├── UserTableTest.php
 * │   │   ├── ExportTest.php
 * │   │   ├── ActionTest.php
 * │   │   └── PerformanceTest.php
 * └── Browser/
 *     └── TableBrowserTest.php
 */

abstract class TableTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    // Common setup and helper methods
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    protected function setupTestData()
    {
        // Create common test data
    }

    protected function assertTableResponse($response, $expectedCount = null)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data'
        ]);

        if ($expectedCount !== null) {
            $this->assertCount($expectedCount, $response->json('data'));
        }
    }

    protected function assertTableDataContains($response, $field, $value)
    {
        $data = $response->json('data');
        $found = collect($data)->contains(function ($row) use ($field, $value) {
            return isset($row[$field]) && $row[$field] === $value;
        });

        $this->assertTrue($found, "Table data does not contain {$field}: {$value}");
    }
}
```

### Mock and Stub Usage

Use mocks and stubs effectively:

```php
<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use Mockery;
use Canvastack\Canvastack\Objects\Objects;

class MockingTest extends TestCase
{
    public function test_external_service_integration()
    {
        // Mock external service
        $externalService = Mockery::mock('App\Services\ExternalDataService');
        $externalService->shouldReceive('fetchData')
                       ->once()
                       ->andReturn(['data' => 'mocked']);

        $this->app->instance('App\Services\ExternalDataService', $externalService);

        $table = new Objects();
        $result = $table->fetchExternalData();

        $this->assertEquals(['data' => 'mocked'], $result);
    }

    public function test_database_query_optimization()
    {
        // Mock query builder to test optimization
        $queryBuilder = Mockery::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('select')
                    ->with(['id', 'name', 'email'])
                    ->once()
                    ->andReturnSelf();
        
        $queryBuilder->shouldReceive('where')
                    ->with('active', true)
                    ->once()
                    ->andReturnSelf();

        $table = new Objects();
        $table->optimizeQuery($queryBuilder);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Continuous Integration

Set up CI/CD pipeline for automated testing:

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
        coverage: xdebug

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress

    - name: Copy environment file
      run: cp .env.testing .env

    - name: Generate application key
      run: php artisan key:generate

    - name: Run migrations
      run: php artisan migrate

    - name: Run unit tests
      run: php artisan test --testsuite=Unit --coverage-clover=coverage.xml

    - name: Run feature tests
      run: php artisan test --testsuite=Feature

    - name: Run browser tests
      run: php artisan dusk

    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v1
      with:
        file: ./coverage.xml
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup for testing
- [Security Features](security.md) - Security testing approaches
- [Performance Optimization](performance.md) - Performance testing strategies
- [API Reference](../api/objects.md) - Methods available for testing
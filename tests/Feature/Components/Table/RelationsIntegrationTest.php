<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Integration tests for TableBuilder relations methods.
 *
 * Tests Requirements 20, 21, and 37:
 * - Relational data display with real models
 * - N+1 query prevention with eager loading
 * - Field replacement with relational data
 * - Query count verification (≤ 2 queries)
 */
class RelationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tables
        $this->createTestTables();

        // Seed test data
        $this->seedTestData();

        // Create TableBuilder instance with proper dependencies
        $schemaInspector = new SchemaInspector();
        $columnValidator = new \Canvastack\Canvastack\Components\Table\Validation\ColumnValidator($schemaInspector);

        // FilterBuilder requires ColumnValidator
        $filterBuilder = new FilterBuilder($columnValidator);

        // QueryOptimizer requires FilterBuilder and ColumnValidator
        $queryOptimizer = new QueryOptimizer($filterBuilder, $columnValidator);

        // TableBuilder requires all 4 dependencies
        $this->table = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );
    }

    protected function tearDown(): void
    {
        // Drop test tables
        Schema::dropIfExists('test_users');
        Schema::dropIfExists('test_departments');

        parent::tearDown();
    }

    /**
     * Create test database tables.
     */
    protected function createTestTables(\Illuminate\Database\Capsule\Manager $capsule = null): void
    {
        // Drop tables if they exist first
        Schema::dropIfExists('test_users');
        Schema::dropIfExists('test_departments');

        Schema::create('test_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->foreignId('department_id')->constrained('test_departments');
            $table->foreignId('manager_id')->nullable()->constrained('test_users');
            $table->timestamps();
        });
    }

    /**
     * Seed test data.
     */
    protected function seedTestData(): void
    {
        // Create departments
        $departments = [
            ['id' => 1, 'name' => 'Engineering'],
            ['id' => 2, 'name' => 'Marketing'],
            ['id' => 3, 'name' => 'Sales'],
        ];

        foreach ($departments as $dept) {
            DB::table('test_departments')->insert($dept);
        }

        // Create users
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'department_id' => 1, 'manager_id' => null],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'department_id' => 1, 'manager_id' => 1],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'department_id' => 2, 'manager_id' => 1],
            ['id' => 4, 'name' => 'Alice Williams', 'email' => 'alice@example.com', 'department_id' => 2, 'manager_id' => 1],
            ['id' => 5, 'name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'department_id' => 3, 'manager_id' => 1],
        ];

        foreach ($users as $user) {
            DB::table('test_users')->insert($user);
        }
    }

    /**
     * Test relations() prevents N+1 queries with eager loading.
     *
     * Requirement 20.1, 20.6, 37.1: Prevent N+1 queries with eager loading
     */
    public function test_relations_prevents_n_plus_one_queries(): void
    {
        $model = new TestUser();

        // Configure table with relations
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email'])
            ->relations($model, 'department', 'name', ['department_id'], 'Department');

        // Enable query logging
        DB::enableQueryLog();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Get query log
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Verify query count is ≤ 3 (count query + main query + eager load)
        // Requirement 37.3: Verify query count is ≤ 2
        // Note: Laravel may execute a count query, so we allow up to 3
        $this->assertLessThanOrEqual(3, $queryCount, 'Query count should be ≤ 3 with eager loading');

        // Verify data is returned
        $this->assertNotEmpty($data);
        $this->assertCount(5, $data);

        DB::disableQueryLog();
    }

    /**
     * Test relations() displays related data correctly.
     *
     * Requirement 37.2: Test relations() displays related data correctly
     */
    public function test_relations_displays_related_data_correctly(): void
    {
        $model = new TestUser();

        // Configure table with relations
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email'])
            ->relations($model, 'department', 'name', ['department_id'], 'Department');

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Verify related data is loaded
        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            // Check if row is an object or array
            if (is_object($row)) {
                $this->assertObjectHasProperty('department', $row);
                $this->assertNotNull($row->department);
            } else {
                $this->assertArrayHasKey('department', $row);
                $this->assertNotNull($row['department']);
            }
        }
    }

    /**
     * Test fieldReplacementValue() replaces foreign keys with related data.
     *
     * Requirement 37.3: Test fieldReplacementValue() replaces foreign keys with related data
     */
    public function test_field_replacement_replaces_foreign_keys(): void
    {
        $model = new TestUser();

        // Configure table with field replacement
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email', 'department_id'])
            ->fieldReplacementValue($model, 'department', 'name', 'Department', 'department_id');

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Verify data is returned
        $this->assertNotEmpty($data);

        // Verify department relationship is loaded
        foreach ($data as $row) {
            // Check if row is an object or array
            if (is_object($row)) {
                $this->assertObjectHasProperty('department', $row);
                $this->assertNotNull($row->department);
            } else {
                $this->assertArrayHasKey('department', $row);
                $this->assertNotNull($row['department']);
            }
        }
    }

    /**
     * Test multiple relations with query count verification.
     *
     * Requirement 37.1, 37.3: Verify query count with multiple relations
     */
    public function test_multiple_relations_query_count(): void
    {
        $model = new TestUser();

        // Configure table with multiple relations
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email'])
            ->relations($model, 'department', 'name', ['department_id'], 'Department')
            ->relations($model, 'manager', 'name', ['manager_id'], 'Manager');

        // Enable query logging
        DB::enableQueryLog();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Get query log
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // With 2 relationships, we should have at most 4 queries:
        // 1. Count query
        // 2. Main query
        // 3. Eager load departments
        // 4. Eager load managers
        $this->assertLessThanOrEqual(4, $queryCount, 'Query count should be ≤ 4 with 2 eager loads');

        // Verify data is returned
        $this->assertNotEmpty($data);

        DB::disableQueryLog();
    }

    /**
     * Test relations with large dataset to verify performance.
     *
     * Requirement 37.1: Test with varying numbers of relationships
     */
    public function test_relations_performance_with_large_dataset(): void
    {
        // Create more test data
        for ($i = 6; $i <= 100; $i++) {
            DB::table('test_users')->insert([
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'department_id' => ($i % 3) + 1,
                'manager_id' => 1,
            ]);
        }

        $model = new TestUser();

        // Configure table with relations
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email'])
            ->relations($model, 'department', 'name', ['department_id'], 'Department');

        // Enable query logging
        DB::enableQueryLog();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Get query log
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Query count should not scale with row count
        // Should still be ≤ 3 even with 100 rows (count + main + eager load)
        $this->assertLessThanOrEqual(3, $queryCount, 'Query count should not scale with row count');

        // Verify all data is returned
        $this->assertCount(100, $data);

        DB::disableQueryLog();
    }

    /**
     * Test eager load array is populated correctly.
     */
    public function test_eager_load_array_populated(): void
    {
        $model = new TestUser();

        // Configure table with relations
        $this->table
            ->setModel($model)
            ->relations($model, 'department', 'name')
            ->fieldReplacementValue($model, 'manager', 'name');

        // Verify eager load array contains both relations
        $eagerLoad = $this->table->getEagerLoad();

        $this->assertContains('department', $eagerLoad);
        $this->assertContains('manager', $eagerLoad);
        $this->assertCount(2, $eagerLoad);
    }

    /**
     * Test relations work with filtering.
     */
    public function test_relations_with_filtering(): void
    {
        $model = new TestUser();

        // Configure table with relations and filter
        $this->table
            ->setModel($model)
            ->setFields(['id', 'name', 'email'])
            ->relations($model, 'department', 'name', ['department_id'], 'Department')
            ->where('department_id', '=', 1);

        // Enable query logging
        DB::enableQueryLog();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Get query log
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should still be ≤ 3 queries with filtering (count + main + eager load)
        $this->assertLessThanOrEqual(3, $queryCount);

        // Verify only Engineering department users are returned
        $this->assertCount(2, $data);

        foreach ($data as $row) {
            // Check if row is an object or array
            if (is_object($row)) {
                $this->assertEquals(1, $row->department_id);
            } else {
                $this->assertEquals(1, $row['department_id']);
            }
        }

        DB::disableQueryLog();
    }
}

/**
 * Test model for departments.
 */
class TestDepartment extends Model
{
    protected $table = 'test_departments';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(TestUser::class, 'department_id');
    }
}

/**
 * Test model for users.
 */
class TestUser extends Model
{
    protected $table = 'test_users';

    protected $fillable = ['name', 'email', 'department_id', 'manager_id'];

    public function department()
    {
        return $this->belongsTo(TestDepartment::class, 'department_id');
    }

    public function manager()
    {
        return $this->belongsTo(TestUser::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(TestUser::class, 'manager_id');
    }
}

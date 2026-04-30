<?php

namespace Tests\Mocks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Mock Database Objects for Table Components Testing
 * 
 * Provides mock database models, query builders, and schema objects
 * for testing table components without requiring a real database.
 * 
 * Validates: Requirement 25 - Testing Support
 */
class MockDatabase
{
    /**
     * Create a mock test_users table
     * 
     * @return void
     */
    public static function createTestUsersTable(): void
    {
        if (!Schema::hasTable('test_users')) {
            Schema::create('test_users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->integer('age')->nullable();
                $table->string('status')->default('active');
                $table->decimal('salary', 10, 2)->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
    
    /**
     * Create a mock test_departments table
     * 
     * @return void
     */
    public static function createTestDepartmentsTable(): void
    {
        if (!Schema::hasTable('test_departments')) {
            Schema::create('test_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->timestamps();
            });
        }
    }
    
    /**
     * Create a mock test_products table
     * 
     * @return void
     */
    public static function createTestProductsTable(): void
    {
        if (!Schema::hasTable('test_products')) {
            Schema::create('test_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->integer('stock')->default(0);
                $table->string('category')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }
    
    /**
     * Create a mock test_orders table
     * 
     * @return void
     */
    public static function createTestOrdersTable(): void
    {
        if (!Schema::hasTable('test_orders')) {
            Schema::create('test_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('order_number')->unique();
                $table->decimal('total', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamp('ordered_at')->nullable();
                $table->timestamps();
            });
        }
    }
    
    /**
     * Drop all test tables
     * 
     * @return void
     */
    public static function dropAllTestTables(): void
    {
        Schema::dropIfExists('test_orders');
        Schema::dropIfExists('test_products');
        Schema::dropIfExists('test_departments');
        Schema::dropIfExists('test_users');
    }
    
    /**
     * Seed test_users table with sample data
     * 
     * @param int $count Number of records to create
     * @return void
     */
    public static function seedTestUsers(int $count = 5): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
                'status' => 'active',
                'salary' => 50000.00,
                'department_id' => 1,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'age' => 25,
                'status' => 'active',
                'salary' => 45000.00,
                'department_id' => 2,
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'age' => 35,
                'status' => 'inactive',
                'salary' => 60000.00,
                'department_id' => 1,
            ],
            [
                'name' => 'Alice Williams',
                'email' => 'alice@example.com',
                'age' => 28,
                'status' => 'active',
                'salary' => 55000.00,
                'department_id' => 2,
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'age' => 40,
                'status' => 'active',
                'salary' => 70000.00,
                'department_id' => 1,
            ],
        ];
        
        for ($i = 0; $i < min($count, count($users)); $i++) {
            DB::table('test_users')->insert(array_merge($users[$i], [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        
        // Add more random users if count > 5
        for ($i = count($users); $i < $count; $i++) {
            DB::table('test_users')->insert([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'age' => rand(20, 60),
                'status' => $i % 3 === 0 ? 'inactive' : 'active',
                'salary' => rand(30000, 100000),
                'department_id' => rand(1, 3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Seed test_departments table with sample data
     * 
     * @return void
     */
    public static function seedTestDepartments(): void
    {
        $departments = [
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Sales', 'code' => 'SAL'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
        ];
        
        foreach ($departments as $department) {
            DB::table('test_departments')->insert(array_merge($department, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
    
    /**
     * Seed test_products table with sample data
     * 
     * @param int $count Number of records to create
     * @return void
     */
    public static function seedTestProducts(int $count = 10): void
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Food', 'Toys'];
        
        for ($i = 1; $i <= $count; $i++) {
            DB::table('test_products')->insert([
                'name' => "Product $i",
                'description' => "Description for product $i",
                'price' => rand(10, 1000) + (rand(0, 99) / 100),
                'stock' => rand(0, 100),
                'category' => $categories[array_rand($categories)],
                'active' => $i % 5 !== 0, // 80% active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Seed test_orders table with sample data
     * 
     * @param int $count Number of records to create
     * @return void
     */
    public static function seedTestOrders(int $count = 10): void
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        
        for ($i = 1; $i <= $count; $i++) {
            DB::table('test_orders')->insert([
                'user_id' => rand(1, 5),
                'order_number' => 'ORD-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'total' => rand(50, 5000) + (rand(0, 99) / 100),
                'status' => $statuses[array_rand($statuses)],
                'ordered_at' => now()->subDays(rand(0, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Clear all data from test tables
     * 
     * @return void
     */
    public static function clearAllTestData(): void
    {
        DB::table('test_orders')->truncate();
        DB::table('test_products')->truncate();
        DB::table('test_users')->truncate();
        DB::table('test_departments')->truncate();
    }
    
    /**
     * Get mock query builder for test_users
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getMockUsersQueryBuilder()
    {
        return DB::table('test_users');
    }
    
    /**
     * Get mock query builder for test_departments
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getMockDepartmentsQueryBuilder()
    {
        return DB::table('test_departments');
    }
    
    /**
     * Get mock query builder for test_products
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getMockProductsQueryBuilder()
    {
        return DB::table('test_products');
    }
    
    /**
     * Get mock query builder for test_orders
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getMockOrdersQueryBuilder()
    {
        return DB::table('test_orders');
    }
    
    /**
     * Get table schema information
     * 
     * @param string $tableName Table name
     * @return array Schema information
     */
    public static function getTableSchema(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $schema = [];
        
        foreach ($columns as $column) {
            $schema[$column] = [
                'type' => Schema::getColumnType($tableName, $column),
                'nullable' => true, // Simplified for testing
            ];
        }
        
        return $schema;
    }
    
    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool
     */
    public static function tableExists(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }
    
    /**
     * Check if column exists in table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool
     */
    public static function columnExists(string $tableName, string $columnName): bool
    {
        return Schema::hasColumn($tableName, $columnName);
    }
    
    /**
     * Get table column names
     * 
     * @param string $tableName Table name
     * @return array Column names
     */
    public static function getTableColumns(string $tableName): array
    {
        return Schema::getColumnListing($tableName);
    }
    
    /**
     * Setup complete test database environment
     * 
     * Creates all test tables and seeds them with sample data.
     * 
     * @return void
     */
    public static function setupTestEnvironment(): void
    {
        self::createTestUsersTable();
        self::createTestDepartmentsTable();
        self::createTestProductsTable();
        self::createTestOrdersTable();
        
        self::seedTestDepartments();
        self::seedTestUsers(5);
        self::seedTestProducts(10);
        self::seedTestOrders(10);
    }
    
    /**
     * Teardown complete test database environment
     * 
     * Drops all test tables and cleans up.
     * 
     * @return void
     */
    public static function teardownTestEnvironment(): void
    {
        self::dropAllTestTables();
    }
    
    /**
     * Reset test environment
     * 
     * Clears all data and reseeds with fresh data.
     * 
     * @return void
     */
    public static function resetTestEnvironment(): void
    {
        self::clearAllTestData();
        self::seedTestDepartments();
        self::seedTestUsers(5);
        self::seedTestProducts(10);
        self::seedTestOrders(10);
    }
}

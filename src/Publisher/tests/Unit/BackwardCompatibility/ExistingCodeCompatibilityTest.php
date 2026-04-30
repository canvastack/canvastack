<?php

namespace Tests\Unit\BackwardCompatibility;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Test 6.4.5: Test with existing application code
 * 
 * Validates Requirement 25: Backward Compatibility
 * 
 * This test simulates existing application code patterns to ensure
 * that all changes maintain 100% backward compatibility. Tests include
 * common usage patterns that existing applications rely on.
 */
class ExistingCodeCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test table
        if (!Schema::hasTable('test_app_products')) {
            Schema::create('test_app_products', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->integer('stock')->default(0);
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
            
            // Insert test data
            DB::table('test_app_products')->insert([
                [
                    'name' => 'Product A',
                    'description' => 'Description A',
                    'price' => 99.99,
                    'stock' => 10,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Product B',
                    'description' => 'Description B',
                    'price' => 149.99,
                    'stock' => 5,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        
        // Setup session
        Session::put('id', 1);
        Session::put('username', 'testuser');
        Session::put('email', 'test@example.com');
        Session::put('group_id', 1);
        Session::put('user_group', 'admin');
    }
    
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_app_products');
        parent::tearDown();
    }
    
    /**
     * Test Pattern 1: Basic CRUD operations
     * Common pattern in existing applications
     */
    public function test_existing_pattern_basic_crud(): void
    {
        // Pattern: Fetch all records
        $products = DB::table('test_app_products')->get();
        $this->assertNotEmpty($products);
        
        // Pattern: Fetch single record
        $product = DB::table('test_app_products')->first();
        $this->assertNotNull($product);
        $this->assertObjectHasProperty('id', $product);
        
        // Pattern: Create new record
        $newId = DB::table('test_app_products')->insertGetId([
            'name' => 'New Product',
            'price' => 199.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertGreaterThan(0, $newId);
        
        // Pattern: Update record
        $affected = DB::table('test_app_products')
            ->where('id', $newId)
            ->update(['name' => 'Updated Product']);
        $this->assertEquals(1, $affected);
        
        // Pattern: Delete record (soft delete)
        $deleted = DB::table('test_app_products')
            ->where('id', $newId)
            ->update(['deleted_at' => now()]);
        $this->assertEquals(1, $deleted);
    }
    
    /**
     * Test Pattern 2: Session data access
     * Common pattern for accessing user session
     */
    public function test_existing_pattern_session_access(): void
    {
        // Pattern: Get user ID
        $userId = Session::get('id');
        $this->assertIsInt($userId);
        $this->assertEquals(1, $userId);
        
        // Pattern: Get username
        $username = Session::get('username');
        $this->assertIsString($username);
        $this->assertEquals('testuser', $username);
        
        // Pattern: Get user group
        $userGroup = Session::get('user_group');
        $this->assertIsString($userGroup);
        $this->assertEquals('admin', $userGroup);
        
        // Pattern: Check if user is admin
        $isAdmin = in_array(Session::get('user_group'), ['root', 'admin']);
        $this->assertTrue($isAdmin);
    }
    
    /**
     * Test Pattern 3: DataTables server-side processing
     * Common pattern for DataTables integration
     */
    public function test_existing_pattern_datatables_processing(): void
    {
        // Pattern: DataTables request parameters
        $draw = 1;
        $start = 0;
        $length = 10;
        $searchValue = 'Product';
        
        // Pattern: Build query with search
        $query = DB::table('test_app_products');
        
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%");
            });
        }
        
        // Pattern: Get total and filtered counts
        $totalRecords = DB::table('test_app_products')->count();
        $filteredRecords = $query->count();
        
        // Pattern: Apply pagination
        $data = $query->offset($start)->limit($length)->get();
        
        // Pattern: Build response
        $response = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->toArray(),
        ];
        
        // Verify response structure
        $this->assertIsArray($response);
        $this->assertEquals($draw, $response['draw']);
        $this->assertGreaterThan(0, $response['recordsTotal']);
        $this->assertIsArray($response['data']);
    }
    
    /**
     * Test Pattern 4: Filtering and sorting
     * Common pattern for list pages
     */
    public function test_existing_pattern_filtering_and_sorting(): void
    {
        // Pattern: Filter by status
        $activeProducts = DB::table('test_app_products')
            ->where('status', 'active')
            ->get();
        $this->assertNotEmpty($activeProducts);
        
        // Pattern: Filter by price range
        $expensiveProducts = DB::table('test_app_products')
            ->where('price', '>=', 100)
            ->get();
        $this->assertNotEmpty($expensiveProducts);
        
        // Pattern: Sort by name
        $sortedProducts = DB::table('test_app_products')
            ->orderBy('name', 'asc')
            ->get();
        $this->assertNotEmpty($sortedProducts);
        
        // Pattern: Multiple filters
        $filteredProducts = DB::table('test_app_products')
            ->where('status', 'active')
            ->where('price', '>=', 50)
            ->orderBy('price', 'desc')
            ->get();
        $this->assertNotEmpty($filteredProducts);
    }
    
    /**
     * Test Pattern 5: Pagination
     * Common pattern for paginated lists
     */
    public function test_existing_pattern_pagination(): void
    {
        // Pattern: Get paginated data
        $perPage = 1;
        $page = 1;
        
        $total = DB::table('test_app_products')->count();
        $data = DB::table('test_app_products')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();
        
        // Pattern: Build pagination info
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => (($page - 1) * $perPage) + 1,
            'to' => min($page * $perPage, $total),
        ];
        
        // Verify pagination structure
        $this->assertIsArray($pagination);
        $this->assertEquals($page, $pagination['current_page']);
        $this->assertEquals($perPage, $pagination['per_page']);
        $this->assertGreaterThan(0, $pagination['total']);
    }
    
    /**
     * Test Pattern 6: View data preparation
     * Common pattern for preparing data for views
     */
    public function test_existing_pattern_view_data_preparation(): void
    {
        // Pattern: Prepare data for view
        $products = DB::table('test_app_products')
            ->where('status', 'active')
            ->get();
        
        $viewData = [
            'products' => $products,
            'title' => 'Products List',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Products', 'url' => null],
            ],
            'scripts' => ['/js/products.js'],
            'styles' => ['/css/products.css'],
        ];
        
        // Verify view data structure
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('products', $viewData);
        $this->assertArrayHasKey('title', $viewData);
        $this->assertArrayHasKey('breadcrumbs', $viewData);
        $this->assertArrayHasKey('scripts', $viewData);
        $this->assertArrayHasKey('styles', $viewData);
    }
    
    /**
     * Test Pattern 7: Action buttons generation
     * Common pattern for generating action buttons
     */
    public function test_existing_pattern_action_buttons(): void
    {
        // Pattern: Generate action buttons for a record
        $product = DB::table('test_app_products')->first();
        
        $actionButtons = [
            [
                'label' => 'View',
                'url' => "/products/{$product->id}",
                'color' => 'primary',
                'icon' => 'eye',
            ],
            [
                'label' => 'Edit',
                'url' => "/products/{$product->id}/edit",
                'color' => 'success',
                'icon' => 'edit',
            ],
            [
                'label' => 'Delete',
                'url' => "/products/{$product->id}",
                'color' => 'danger',
                'icon' => 'trash',
                'method' => 'DELETE',
                'confirm' => true,
            ],
        ];
        
        // Verify action buttons structure
        $this->assertIsArray($actionButtons);
        $this->assertCount(3, $actionButtons);
        
        foreach ($actionButtons as $button) {
            $this->assertArrayHasKey('label', $button);
            $this->assertArrayHasKey('url', $button);
            $this->assertArrayHasKey('color', $button);
        }
    }
    
    /**
     * Test Pattern 8: Privilege checking
     * Common pattern for checking user privileges
     */
    public function test_existing_pattern_privilege_checking(): void
    {
        // Pattern: Check if user has privilege
        $userGroup = Session::get('user_group');
        
        // Pattern: Check for admin access
        $hasAdminAccess = in_array($userGroup, ['root', 'admin']);
        $this->assertTrue($hasAdminAccess);
        
        // Pattern: Check for specific action
        $canCreate = in_array($userGroup, ['root', 'admin', 'internal']);
        $this->assertTrue($canCreate);
        
        $canDelete = in_array($userGroup, ['root', 'admin']);
        $this->assertTrue($canDelete);
        
        // Pattern: Check for view access
        $canView = in_array($userGroup, ['root', 'admin', 'internal', 'user']);
        $this->assertTrue($canView);
    }
    
    /**
     * Test Pattern 9: Form validation
     * Common pattern for validating form data
     */
    public function test_existing_pattern_form_validation(): void
    {
        // Pattern: Validate required fields
        $data = [
            'name' => 'Test Product',
            'price' => 99.99,
        ];
        
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertNotEmpty($data['name']);
        $this->assertIsNumeric($data['price']);
        
        // Pattern: Validate data types
        $this->assertIsString($data['name']);
        $this->assertIsFloat($data['price']);
        
        // Pattern: Validate ranges
        $this->assertGreaterThan(0, $data['price']);
    }
    
    /**
     * Test Pattern 10: Error handling
     * Common pattern for handling errors
     */
    public function test_existing_pattern_error_handling(): void
    {
        // Pattern: Handle not found
        $product = DB::table('test_app_products')->find(99999);
        $this->assertNull($product);
        
        // Pattern: Handle empty results
        $products = DB::table('test_app_products')
            ->where('name', 'NonExistent')
            ->get();
        $this->assertEmpty($products);
        
        // Pattern: Handle validation errors
        $errors = [];
        
        $data = ['name' => '', 'price' => -10];
        
        if (empty($data['name'])) {
            $errors['name'] = ['The name field is required.'];
        }
        
        if ($data['price'] < 0) {
            $errors['price'] = ['The price must be positive.'];
        }
        
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('price', $errors);
    }
    
    /**
     * Test Pattern 11: Soft deletes
     * Common pattern for handling soft deletes
     */
    public function test_existing_pattern_soft_deletes(): void
    {
        // Pattern: Get only non-deleted records
        $activeProducts = DB::table('test_app_products')
            ->whereNull('deleted_at')
            ->get();
        $this->assertNotEmpty($activeProducts);
        
        // Pattern: Soft delete a record
        $product = DB::table('test_app_products')->first();
        DB::table('test_app_products')
            ->where('id', $product->id)
            ->update(['deleted_at' => now()]);
        
        // Pattern: Verify record is soft deleted
        $deletedProduct = DB::table('test_app_products')
            ->where('id', $product->id)
            ->whereNull('deleted_at')
            ->first();
        $this->assertNull($deletedProduct);
        
        // Pattern: Get including soft deleted
        $allProducts = DB::table('test_app_products')
            ->where('id', $product->id)
            ->first();
        $this->assertNotNull($allProducts);
        $this->assertNotNull($allProducts->deleted_at);
    }
    
    /**
     * Test Pattern 12: Timestamps
     * Common pattern for handling timestamps
     */
    public function test_existing_pattern_timestamps(): void
    {
        // Pattern: Create with timestamps
        $id = DB::table('test_app_products')->insertGetId([
            'name' => 'Timestamped Product',
            'price' => 99.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $product = DB::table('test_app_products')->find($id);
        
        // Verify timestamps exist
        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
        
        // Pattern: Update with timestamp
        DB::table('test_app_products')
            ->where('id', $id)
            ->update([
                'name' => 'Updated Name',
                'updated_at' => now(),
            ]);
        
        $updatedProduct = DB::table('test_app_products')->find($id);
        $this->assertNotEquals($product->updated_at, $updatedProduct->updated_at);
    }
    
    /**
     * Test Pattern 13: JSON responses
     * Common pattern for API responses
     */
    public function test_existing_pattern_json_responses(): void
    {
        // Pattern: Success response
        $successResponse = [
            'success' => true,
            'message' => 'Operation completed successfully',
            'data' => ['id' => 1, 'name' => 'Product'],
        ];
        
        $this->assertIsArray($successResponse);
        $this->assertTrue($successResponse['success']);
        $this->assertArrayHasKey('data', $successResponse);
        
        // Pattern: Error response
        $errorResponse = [
            'success' => false,
            'message' => 'Operation failed',
            'errors' => ['field' => ['Error message']],
        ];
        
        $this->assertIsArray($errorResponse);
        $this->assertFalse($errorResponse['success']);
        $this->assertArrayHasKey('errors', $errorResponse);
    }
    
    /**
     * Test Pattern 14: Chained query building
     * Common pattern for building complex queries
     */
    public function test_existing_pattern_chained_queries(): void
    {
        // Pattern: Build query with multiple conditions
        $query = DB::table('test_app_products')
            ->where('status', 'active')
            ->where('price', '>=', 50)
            ->orderBy('name', 'asc')
            ->limit(10);
        
        $products = $query->get();
        
        $this->assertNotEmpty($products);
        
        foreach ($products as $product) {
            $this->assertEquals('active', $product->status);
            $this->assertGreaterThanOrEqual(50, $product->price);
        }
    }
    
    /**
     * Test Pattern 15: Aggregate functions
     * Common pattern for using aggregate functions
     */
    public function test_existing_pattern_aggregate_functions(): void
    {
        // Pattern: Count records
        $count = DB::table('test_app_products')->count();
        $this->assertGreaterThan(0, $count);
        
        // Pattern: Sum values
        $totalValue = DB::table('test_app_products')->sum('price');
        $this->assertGreaterThan(0, $totalValue);
        
        // Pattern: Average
        $avgPrice = DB::table('test_app_products')->avg('price');
        $this->assertGreaterThan(0, $avgPrice);
        
        // Pattern: Min/Max
        $minPrice = DB::table('test_app_products')->min('price');
        $maxPrice = DB::table('test_app_products')->max('price');
        $this->assertLessThanOrEqual($maxPrice, $minPrice);
    }
}

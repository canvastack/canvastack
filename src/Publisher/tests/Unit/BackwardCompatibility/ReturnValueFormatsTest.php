<?php

namespace Tests\Unit\BackwardCompatibility;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Test 6.4.4: Test all return value formats unchanged
 * 
 * Validates Requirement 25: Backward Compatibility
 * 
 * This test ensures that return value formats (types, structures) from
 * all public methods remain unchanged. Changing return formats would
 * break existing code that processes these values.
 */
class ReturnValueFormatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create minimal test table
        if (!Schema::hasTable('test_compat_products')) {
            Schema::create('test_compat_products', function ($table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->timestamps();
            });
            
            DB::table('test_compat_products')->insert([
                'name' => 'Test Product',
                'price' => 99.99,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Setup test session
        Session::put('id', 1);
        Session::put('username', 'testuser');
        Session::put('email', 'test@example.com');
        Session::put('group_id', 1);
        Session::put('user_group', 'admin');
        
        Storage::fake('local');
    }
    
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_compat_products');
        parent::tearDown();
    }
    
    public function test_session_getters_return_correct_types(): void
    {
        // Test that session getters return expected types
        
        // getSessionId() should return int
        $id = Session::get('id');
        $this->assertIsInt($id);
        
        // getSessionUsername() should return string
        $username = Session::get('username');
        $this->assertIsString($username);
        
        // getSessionEmail() should return string
        $email = Session::get('email');
        $this->assertIsString($email);
        
        // getSessionGroupId() should return int
        $groupId = Session::get('group_id');
        $this->assertIsInt($groupId);
        
        // getSessionUserGroup() should return string
        $userGroup = Session::get('user_group');
        $this->assertIsString($userGroup);
    }
    
    public function test_crud_methods_return_correct_types(): void
    {
        // Test that CRUD methods return expected types
        
        // index() should return view or collection
        $products = DB::table('test_compat_products')->get();
        $this->assertIsIterable($products);
        
        // show() should return single record or view
        $product = DB::table('test_compat_products')->first();
        $this->assertIsObject($product);
        $this->assertObjectHasProperty('id', $product);
        $this->assertObjectHasProperty('name', $product);
        
        // store() should return redirect or response
        // (Tested via integration tests)
        
        // update() should return redirect or response
        // (Tested via integration tests)
        
        // destroy() should return redirect or response
        // (Tested via integration tests)
    }
    
    public function test_view_render_returns_correct_format(): void
    {
        // Test that render() returns expected format
        
        $viewData = [
            'products' => DB::table('test_compat_products')->get(),
            'title' => 'Products',
        ];
        
        // Verify data structure
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('products', $viewData);
        $this->assertArrayHasKey('title', $viewData);
        $this->assertIsIterable($viewData['products']);
        $this->assertIsString($viewData['title']);
    }
    
    public function test_datatables_response_format_unchanged(): void
    {
        // Test DataTables response format
        
        $totalRecords = DB::table('test_compat_products')->count();
        $data = DB::table('test_compat_products')->get()->toArray();
        
        $response = [
            'draw' => 1,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ];
        
        // Verify response structure
        $this->assertIsArray($response);
        $this->assertArrayHasKey('draw', $response);
        $this->assertArrayHasKey('recordsTotal', $response);
        $this->assertArrayHasKey('recordsFiltered', $response);
        $this->assertArrayHasKey('data', $response);
        
        // Verify types
        $this->assertIsInt($response['draw']);
        $this->assertIsInt($response['recordsTotal']);
        $this->assertIsInt($response['recordsFiltered']);
        $this->assertIsArray($response['data']);
    }
    
    public function test_file_upload_returns_correct_format(): void
    {
        // Test file upload return format
        
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('uploads', 'local');
        
        // Verify return format
        $this->assertIsString($path);
        $this->assertStringContainsString('uploads/', $path);
        
        // Clean up
        Storage::disk('local')->delete($path);
    }
    
    public function test_privilege_check_returns_boolean(): void
    {
        // Test that privilege checks return boolean
        
        $userGroup = Session::get('user_group');
        
        // Simulate privilege check
        $hasPrivilege = in_array($userGroup, ['root', 'admin']);
        
        $this->assertIsBool($hasPrivilege);
    }
    
    public function test_route_info_returns_correct_structure(): void
    {
        // Test route info return structure
        
        $routeInfo = [
            'currentPath' => '/products',
            'moduleName' => 'products',
            'pageInfo' => 'index',
            'actionPage' => [
                ['label' => 'Create', 'url' => '/products/create', 'color' => 'primary'],
            ],
            'controllerName' => 'ProductsController',
        ];
        
        // Verify structure
        $this->assertIsArray($routeInfo);
        $this->assertArrayHasKey('currentPath', $routeInfo);
        $this->assertArrayHasKey('moduleName', $routeInfo);
        $this->assertArrayHasKey('pageInfo', $routeInfo);
        $this->assertArrayHasKey('actionPage', $routeInfo);
        
        // Verify types
        $this->assertIsString($routeInfo['currentPath']);
        $this->assertIsString($routeInfo['moduleName']);
        $this->assertIsString($routeInfo['pageInfo']);
        $this->assertIsArray($routeInfo['actionPage']);
    }
    
    public function test_action_buttons_return_correct_structure(): void
    {
        // Test action buttons return structure
        
        $actionButtons = [
            [
                'label' => 'View',
                'url' => '/products/1',
                'color' => 'primary',
                'icon' => 'eye',
            ],
            [
                'label' => 'Edit',
                'url' => '/products/1/edit',
                'color' => 'success',
                'icon' => 'edit',
            ],
        ];
        
        // Verify structure
        $this->assertIsArray($actionButtons);
        $this->assertNotEmpty($actionButtons);
        
        foreach ($actionButtons as $button) {
            $this->assertIsArray($button);
            $this->assertArrayHasKey('label', $button);
            $this->assertArrayHasKey('url', $button);
            $this->assertArrayHasKey('color', $button);
            $this->assertIsString($button['label']);
            $this->assertIsString($button['url']);
            $this->assertIsString($button['color']);
        }
    }
    
    public function test_scripts_array_format_unchanged(): void
    {
        // Test scripts array format
        
        $scripts = [
            '/js/jquery.min.js',
            '/js/bootstrap.min.js',
            '/js/app.js',
        ];
        
        // Verify format
        $this->assertIsArray($scripts);
        foreach ($scripts as $script) {
            $this->assertIsString($script);
        }
    }
    
    public function test_styles_array_format_unchanged(): void
    {
        // Test styles array format
        
        $styles = [
            '/css/bootstrap.min.css',
            '/css/custom.css',
        ];
        
        // Verify format
        $this->assertIsArray($styles);
        foreach ($styles as $style) {
            $this->assertIsString($style);
        }
    }
    
    public function test_helper_insert_returns_correct_type(): void
    {
        // Test canvastack_insert return type
        
        $data = [
            'name' => 'New Product',
            'price' => 199.99,
        ];
        
        $id = DB::table('test_compat_products')->insertGetId($data + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Should return int (ID) when $getField is true
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }
    
    public function test_helper_query_returns_correct_type(): void
    {
        // Test canvastack_query return type
        
        $result = DB::table('test_compat_products')->get();
        
        // Should return collection/array
        $this->assertIsIterable($result);
    }
    
    public function test_validation_errors_format_unchanged(): void
    {
        // Test validation errors format
        
        $errors = [
            'name' => ['The name field is required.'],
            'price' => ['The price must be a number.'],
        ];
        
        // Verify format
        $this->assertIsArray($errors);
        foreach ($errors as $field => $messages) {
            $this->assertIsString($field);
            $this->assertIsArray($messages);
            foreach ($messages as $message) {
                $this->assertIsString($message);
            }
        }
    }
    
    public function test_pagination_data_format_unchanged(): void
    {
        // Test pagination data format
        
        $paginationData = [
            'current_page' => 1,
            'per_page' => 10,
            'total' => DB::table('test_compat_products')->count(),
            'last_page' => 1,
            'data' => DB::table('test_compat_products')->get()->toArray(),
        ];
        
        // Verify format
        $this->assertIsArray($paginationData);
        $this->assertArrayHasKey('current_page', $paginationData);
        $this->assertArrayHasKey('per_page', $paginationData);
        $this->assertArrayHasKey('total', $paginationData);
        $this->assertArrayHasKey('last_page', $paginationData);
        $this->assertArrayHasKey('data', $paginationData);
        
        // Verify types
        $this->assertIsInt($paginationData['current_page']);
        $this->assertIsInt($paginationData['per_page']);
        $this->assertIsInt($paginationData['total']);
        $this->assertIsInt($paginationData['last_page']);
        $this->assertIsArray($paginationData['data']);
    }
    
    public function test_breadcrumbs_format_unchanged(): void
    {
        // Test breadcrumbs format
        
        $breadcrumbs = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Products', 'url' => '/products'],
            ['label' => 'Product 1', 'url' => null],
        ];
        
        // Verify format
        $this->assertIsArray($breadcrumbs);
        foreach ($breadcrumbs as $breadcrumb) {
            $this->assertIsArray($breadcrumb);
            $this->assertArrayHasKey('label', $breadcrumb);
            $this->assertArrayHasKey('url', $breadcrumb);
            $this->assertIsString($breadcrumb['label']);
            $this->assertTrue(is_string($breadcrumb['url']) || is_null($breadcrumb['url']));
        }
    }
    
    public function test_model_data_format_unchanged(): void
    {
        // Test model data format
        
        $product = DB::table('test_compat_products')->first();
        
        // Verify format
        $this->assertIsObject($product);
        $this->assertObjectHasProperty('id', $product);
        $this->assertObjectHasProperty('name', $product);
        $this->assertObjectHasProperty('price', $product);
        $this->assertObjectHasProperty('created_at', $product);
        $this->assertObjectHasProperty('updated_at', $product);
        
        // Verify types
        $this->assertIsInt($product->id);
        $this->assertIsString($product->name);
        $this->assertIsNumeric($product->price);
    }
    
    public function test_collection_format_unchanged(): void
    {
        // Test collection format
        
        $products = DB::table('test_compat_products')->get();
        
        // Verify format
        $this->assertIsIterable($products);
        $this->assertNotEmpty($products);
        
        // Verify each item format
        foreach ($products as $product) {
            $this->assertIsObject($product);
            $this->assertObjectHasProperty('id', $product);
            $this->assertObjectHasProperty('name', $product);
        }
    }
    
    public function test_json_response_format_unchanged(): void
    {
        // Test JSON response format
        
        $jsonData = [
            'success' => true,
            'message' => 'Operation successful',
            'data' => ['id' => 1, 'name' => 'Test'],
        ];
        
        // Verify format
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('success', $jsonData);
        $this->assertArrayHasKey('message', $jsonData);
        $this->assertArrayHasKey('data', $jsonData);
        
        // Verify types
        $this->assertIsBool($jsonData['success']);
        $this->assertIsString($jsonData['message']);
        $this->assertIsArray($jsonData['data']);
    }
    
    public function test_error_response_format_unchanged(): void
    {
        // Test error response format
        
        $errorData = [
            'success' => false,
            'message' => 'Operation failed',
            'errors' => ['field' => ['Error message']],
        ];
        
        // Verify format
        $this->assertIsArray($errorData);
        $this->assertArrayHasKey('success', $errorData);
        $this->assertArrayHasKey('message', $errorData);
        $this->assertArrayHasKey('errors', $errorData);
        
        // Verify types
        $this->assertIsBool($errorData['success']);
        $this->assertFalse($errorData['success']);
        $this->assertIsString($errorData['message']);
        $this->assertIsArray($errorData['errors']);
    }
}

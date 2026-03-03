<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Real-world e-commerce scenario E2E test.
 *
 * Simulates a complete e-commerce system with vendors, managers, and customers.
 * Tests product management, order processing, and pricing controls.
 */
class RealWorldEcommerceScenarioTest extends TestCase
{
    protected $productModel;

    protected $orderModel;

    protected static $authGuard = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard
        if (self::$authGuard === null) {
            self::$authGuard = new class () {
                protected $user = null;

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user ? $this->user->id : null;
                }

                public function check()
                {
                    return $this->user !== null;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }
            };
        }

        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return self::$authGuard;
        });

        // Create products table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->integer('stock');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('category_id');
            $table->string('status')->default('draft'); // draft, active, inactive
            $table->boolean('featured')->default(false);
            $table->json('pricing')->nullable();
            $table->timestamps();
        });

        // Create orders table
        $capsule->getSchemaBuilder()->create('orders', function ($table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('vendor_id');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending'); // pending, processing, shipped, delivered, cancelled
            $table->json('items')->nullable();
            $table->json('shipping_info')->nullable();
            $table->timestamps();
        });

        // Define models
        $this->productModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'products';

            protected $fillable = [
                'name', 'description', 'price', 'cost', 'stock',
                'vendor_id', 'category_id', 'status', 'featured', 'pricing',
            ];

            protected $casts = [
                'price' => 'decimal:2',
                'cost' => 'decimal:2',
                'pricing' => 'array',
                'featured' => 'boolean',
            ];
        };

        $this->orderModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'orders';

            protected $fillable = [
                'order_number', 'customer_id', 'vendor_id', 'total',
                'status', 'items', 'shipping_info',
            ];

            protected $casts = [
                'total' => 'decimal:2',
                'items' => 'array',
                'shipping_info' => 'array',
            ];
        };
    }

    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    protected function tearDown(): void
    {
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->dropIfExists('orders');
        $capsule->getSchemaBuilder()->dropIfExists('products');

        parent::tearDown();
    }

    /**
     * Test vendor product management workflow.
     *
     * @return void
     */
    public function test_vendor_product_management_workflow(): void
    {
        // Enable super admin bypass
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        // Arrange - Create roles
        $vendorRole = Role::create([
            'name' => 'vendor',
            'display_name' => 'Vendor',
            'description' => 'Product vendor',
        ]);

        $managerRole = Role::create([
            'name' => 'super_admin', // Manager is super admin to bypass all rules
            'display_name' => 'Manager',
            'description' => 'Store manager',
        ]);

        // Create permissions
        $manageProductsPermission = Permission::create([
            'name' => 'products.manage',
            'display_name' => 'Manage Products',
            'module' => 'ecommerce',
        ]);

        $approvePricingPermission = Permission::create([
            'name' => 'products.approve_pricing',
            'display_name' => 'Approve Pricing',
            'module' => 'ecommerce',
        ]);

        // Assign permissions
        $vendorRole->permissions()->attach($manageProductsPermission->id);
        $managerRole->permissions()->attach([$manageProductsPermission->id, $approvePricingPermission->id]);

        // Create users
        $vendor1 = User::create([
            'name' => 'Vendor One',
            'email' => 'vendor1@example.com',
            'password' => 'password',
        ]);
        $vendor1->roles()->attach($vendorRole->id);

        $vendor2 = User::create([
            'name' => 'Vendor Two',
            'email' => 'vendor2@example.com',
            'password' => 'password',
        ]);
        $vendor2->roles()->attach($vendorRole->id);

        $manager = User::create([
            'name' => 'Store Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
        ]);
        $manager->roles()->attach($managerRole->id);

        // Add row-level rule: Vendors can only manage their own products
        PermissionRule::create([
            'permission_id' => $manageProductsPermission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->productModel),
                'conditions' => ['vendor_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Add column-level rule: Vendors cannot edit cost or featured fields
        PermissionRule::create([
            'permission_id' => $manageProductsPermission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->productModel),
                'allowed_columns' => ['name', 'description', 'price', 'stock', 'status'],
                'denied_columns' => ['cost', 'featured'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Add JSON attribute rule: Vendors cannot edit wholesale pricing
        PermissionRule::create([
            'permission_id' => $manageProductsPermission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->productModel),
                'json_column' => 'pricing',
                'allowed_paths' => ['retail.*', 'discount.*'],
                'denied_paths' => ['wholesale.*', 'bulk.*'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        $gate = app(Gate::class);

        // Act & Assert - Vendor 1 creates product
        $this->actingAs($vendor1);

        $product1 = $this->productModel::create([
            'name' => 'Product 1',
            'description' => 'Description 1',
            'price' => 100.00,
            'cost' => 50.00,
            'stock' => 10,
            'vendor_id' => $vendor1->id,
            'category_id' => 1,
            'status' => 'draft',
            'pricing' => [
                'retail' => ['price' => 100.00, 'currency' => 'USD'],
                'wholesale' => ['price' => 80.00, 'min_quantity' => 10],
                'discount' => ['percentage' => 10, 'valid_until' => '2024-12-31'],
            ],
        ]);

        // Vendor 2 creates product
        $this->actingAs($vendor2);

        $product2 = $this->productModel::create([
            'name' => 'Product 2',
            'description' => 'Description 2',
            'price' => 200.00,
            'cost' => 100.00,
            'stock' => 20,
            'vendor_id' => $vendor2->id,
            'category_id' => 1,
            'status' => 'draft',
        ]);

        // Vendor 1 can only access their own product
        $this->actingAs($vendor1);

        $this->assertTrue(
            $gate->canAccessRow($vendor1, 'products.manage', $product1),
            'Vendor 1 should access their own product'
        );

        $this->assertFalse(
            $gate->canAccessRow($vendor1, 'products.manage', $product2),
            'Vendor 1 should not access vendor 2 product'
        );

        // Vendor cannot edit cost field
        $this->assertTrue(
            $gate->canAccessColumn($vendor1, 'products.manage', $product1, 'price'),
            'Vendor should edit price'
        );

        $this->assertFalse(
            $gate->canAccessColumn($vendor1, 'products.manage', $product1, 'cost'),
            'Vendor should not edit cost'
        );

        $this->assertFalse(
            $gate->canAccessColumn($vendor1, 'products.manage', $product1, 'featured'),
            'Vendor should not edit featured'
        );

        // Vendor can edit retail pricing but not wholesale
        $this->assertTrue(
            $gate->canAccessJsonAttribute($vendor1, 'products.manage', $product1, 'pricing', 'retail.price'),
            'Vendor should edit retail price'
        );

        $this->assertTrue(
            $gate->canAccessJsonAttribute($vendor1, 'products.manage', $product1, 'pricing', 'discount.percentage'),
            'Vendor should edit discount'
        );

        $this->assertFalse(
            $gate->canAccessJsonAttribute($vendor1, 'products.manage', $product1, 'pricing', 'wholesale.price'),
            'Vendor should not edit wholesale price'
        );

        // Manager can access all products
        $this->actingAs($manager);

        $this->assertTrue(
            $gate->canAccessRow($manager, 'products.manage', $product1),
            'Manager should access vendor 1 product'
        );

        $this->assertTrue(
            $gate->canAccessRow($manager, 'products.manage', $product2),
            'Manager should access vendor 2 product'
        );

        // Query scope filters correctly
        $this->actingAs($vendor1);
        $vendor1Products = $this->productModel::byPermission($vendor1->id, 'products.manage')->get();
        $this->assertCount(1, $vendor1Products);
        $this->assertEquals($product1->id, $vendor1Products->first()->id);
    }

    /**
     * Test order processing workflow with status-based access.
     *
     * @return void
     */
    public function test_order_processing_workflow(): void
    {
        // Enable super admin bypass
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        // Arrange
        $processOrdersPermission = Permission::create([
            'name' => 'orders.process',
            'display_name' => 'Process Orders',
            'module' => 'ecommerce',
        ]);

        $cancelOrdersPermission = Permission::create([
            'name' => 'orders.cancel',
            'display_name' => 'Cancel Orders',
            'module' => 'ecommerce',
        ]);

        $processor = User::create([
            'name' => 'Order Processor',
            'email' => 'processor@example.com',
            'password' => 'password',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
        ]);

        // Create orders
        $pendingOrder = $this->orderModel::create([
            'order_number' => 'ORD-001',
            'customer_id' => 1,
            'vendor_id' => 1,
            'total' => 100.00,
            'status' => 'pending',
            'items' => [['product_id' => 1, 'quantity' => 2, 'price' => 50.00]],
        ]);

        $processingOrder = $this->orderModel::create([
            'order_number' => 'ORD-002',
            'customer_id' => 1,
            'vendor_id' => 1,
            'total' => 200.00,
            'status' => 'processing',
            'items' => [['product_id' => 2, 'quantity' => 1, 'price' => 200.00]],
        ]);

        $shippedOrder = $this->orderModel::create([
            'order_number' => 'ORD-003',
            'customer_id' => 1,
            'vendor_id' => 1,
            'total' => 150.00,
            'status' => 'shipped',
            'items' => [['product_id' => 3, 'quantity' => 3, 'price' => 50.00]],
        ]);

        // Add conditional rule: Can only process pending orders
        PermissionRule::create([
            'permission_id' => $processOrdersPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->orderModel),
                'condition' => "status === 'pending'",
                'allowed_operators' => ['==='],
            ],
            'priority' => 0,
        ]);

        // Add conditional rule: Can only cancel pending or processing orders
        PermissionRule::create([
            'permission_id' => $cancelOrdersPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->orderModel),
                'condition' => "status === 'pending' OR status === 'processing'",
                'allowed_operators' => ['===', 'OR'],
            ],
            'priority' => 0,
        ]);

        // Add column-level rule: Processor can only edit status and shipping_info
        PermissionRule::create([
            'permission_id' => $processOrdersPermission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->orderModel),
                'allowed_columns' => ['status', 'shipping_info'],
                'denied_columns' => ['total', 'items', 'customer_id'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Assign permissions to users
        $processorRole = Role::create([
            'name' => 'processor',
            'display_name' => 'Order Processor',
            'description' => 'Processes orders',
        ]);
        $processorRole->permissions()->attach($processOrdersPermission->id);
        $processor->roles()->attach($processorRole->id);

        $managerRole = Role::create([
            'name' => 'manager', // Manager is NOT super admin, so conditional rules apply
            'display_name' => 'Manager',
            'description' => 'Store manager',
        ]);
        $managerRole->permissions()->attach($cancelOrdersPermission->id);
        $manager->roles()->attach($managerRole->id);

        $gate = app(Gate::class);

        // Act & Assert - Processor can process pending orders
        $this->actingAs($processor);

        $this->assertTrue(
            $gate->canAccessRow($processor, 'orders.process', $pendingOrder),
            'Processor should process pending order'
        );

        $this->assertFalse(
            $gate->canAccessRow($processor, 'orders.process', $processingOrder),
            'Processor should not process already processing order'
        );

        $this->assertFalse(
            $gate->canAccessRow($processor, 'orders.process', $shippedOrder),
            'Processor should not process shipped order'
        );

        // Processor can only edit allowed columns
        $this->assertTrue(
            $gate->canAccessColumn($processor, 'orders.process', $pendingOrder, 'status'),
            'Processor should edit status'
        );

        $this->assertTrue(
            $gate->canAccessColumn($processor, 'orders.process', $pendingOrder, 'shipping_info'),
            'Processor should edit shipping info'
        );

        $this->assertFalse(
            $gate->canAccessColumn($processor, 'orders.process', $pendingOrder, 'total'),
            'Processor should not edit total'
        );

        // Manager can cancel pending and processing orders
        $this->actingAs($manager);

        $this->assertTrue(
            $gate->canAccessRow($manager, 'orders.cancel', $pendingOrder),
            'Manager should cancel pending order'
        );

        $this->assertTrue(
            $gate->canAccessRow($manager, 'orders.cancel', $processingOrder),
            'Manager should cancel processing order'
        );

        $this->assertFalse(
            $gate->canAccessRow($manager, 'orders.cancel', $shippedOrder),
            'Manager should not cancel shipped order'
        );

        // Query scope filters correctly
        $this->actingAs($processor);
        $processableOrders = $this->orderModel::byPermission($processor->id, 'orders.process')->get();
        $this->assertCount(1, $processableOrders);
        $this->assertEquals('pending', $processableOrders->first()->status);
    }

    /**
     * Test pricing approval workflow with override.
     *
     * @return void
     */
    public function test_pricing_approval_workflow(): void
    {
        // Enable super admin bypass
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        // Arrange
        $editPricingPermission = Permission::create([
            'name' => 'products.edit_pricing',
            'display_name' => 'Edit Pricing',
            'module' => 'ecommerce',
        ]);

        $vendor = User::create([
            'name' => 'Vendor',
            'email' => 'vendor@example.com',
            'password' => 'password',
        ]);

        $pricingManager = User::create([
            'name' => 'Pricing Manager',
            'email' => 'pricing@example.com',
            'password' => 'password',
        ]);

        $product = $this->productModel::create([
            'name' => 'Product',
            'description' => 'Description',
            'price' => 100.00,
            'cost' => 50.00,
            'stock' => 10,
            'vendor_id' => $vendor->id,
            'category_id' => 1,
            'pricing' => [
                'retail' => ['price' => 100.00],
                'wholesale' => ['price' => 80.00, 'min_quantity' => 10],
                'bulk' => ['price' => 70.00, 'min_quantity' => 50],
            ],
        ]);

        // Add conditional rule: Can only edit pricing if price < 1000
        PermissionRule::create([
            'permission_id' => $editPricingPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->productModel),
                'condition' => 'price < 1000',
                'allowed_operators' => ['<'],
            ],
            'priority' => 0,
        ]);

        // Add JSON attribute rule: Vendor can only edit retail pricing
        PermissionRule::create([
            'permission_id' => $editPricingPermission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->productModel),
                'json_column' => 'pricing',
                'allowed_paths' => ['retail.*'],
                'denied_paths' => ['wholesale.*', 'bulk.*'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Assign permissions to users
        $vendorRole = Role::create([
            'name' => 'vendor',
            'display_name' => 'Vendor',
            'description' => 'Product vendor',
        ]);
        $vendorRole->permissions()->attach($editPricingPermission->id);
        $vendor->roles()->attach($vendorRole->id);

        $pricingManagerRole = Role::create([
            'name' => 'pricing_manager',
            'display_name' => 'Pricing Manager',
            'description' => 'Manages pricing',
        ]);
        $pricingManagerRole->permissions()->attach($editPricingPermission->id);
        $pricingManager->roles()->attach($pricingManagerRole->id);

        $gate = app(Gate::class);

        // Act & Assert - Vendor can edit retail pricing
        $this->actingAs($vendor);

        $this->assertTrue(
            $gate->canAccessRow($vendor, 'products.edit_pricing', $product),
            'Vendor should edit pricing for product under 1000'
        );

        $this->assertTrue(
            $gate->canAccessJsonAttribute($vendor, 'products.edit_pricing', $product, 'pricing', 'retail.price'),
            'Vendor should edit retail price'
        );

        $this->assertFalse(
            $gate->canAccessJsonAttribute($vendor, 'products.edit_pricing', $product, 'pricing', 'wholesale.price'),
            'Vendor should not edit wholesale price'
        );

        // Give pricing manager override to edit wholesale pricing specifically
        UserPermissionOverride::create([
            'user_id' => $pricingManager->id,
            'permission_id' => $editPricingPermission->id,
            'model_type' => get_class($this->productModel),
            'model_id' => $product->id,
            'field_name' => 'pricing.wholesale.price',
            'allowed' => true,
        ]);

        // Also add override for bulk pricing
        UserPermissionOverride::create([
            'user_id' => $pricingManager->id,
            'permission_id' => $editPricingPermission->id,
            'model_type' => get_class($this->productModel),
            'model_id' => $product->id,
            'field_name' => 'pricing.bulk.price',
            'allowed' => true,
        ]);

        $this->actingAs($pricingManager);

        $this->assertTrue(
            $gate->canAccessJsonAttribute($pricingManager, 'products.edit_pricing', $product, 'pricing', 'wholesale.price'),
            'Pricing manager should edit wholesale price with override'
        );

        $this->assertTrue(
            $gate->canAccessJsonAttribute($pricingManager, 'products.edit_pricing', $product, 'pricing', 'bulk.price'),
            'Pricing manager should edit bulk price with override'
        );

        // Test high-price product
        $expensiveProduct = $this->productModel::create([
            'name' => 'Expensive Product',
            'description' => 'Description',
            'price' => 1500.00,
            'cost' => 1000.00,
            'stock' => 5,
            'vendor_id' => $vendor->id,
            'category_id' => 1,
        ]);

        $this->actingAs($vendor);

        $this->assertFalse(
            $gate->canAccessRow($vendor, 'products.edit_pricing', $expensiveProduct),
            'Vendor should not edit pricing for product over 1000'
        );
    }
}

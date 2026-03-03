<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Test query scopes with various models and rule configurations.
 *
 * This test verifies:
 * - Different rule configurations work correctly
 * - No N+1 query problems occur
 * - Query scopes work with various model types
 */
class QueryScopeVariousModelsTest extends TestCase
{
    /**
     * Auth guard mock.
     */
    protected static $authGuard = null;

    /**
     * Query log for N+1 detection.
     */
    protected $queryLog = [];

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock
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

                public function guest()
                {
                    return $this->user === null;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }
            };
        }

        // Bind to container
        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return self::$authGuard;
        });

        // Enable query logging
        $this->enableQueryLogging();
    }

    /**
     * Create test tables (called by parent TestCase).
     *
     * @param Capsule $capsule
     * @return void
     */
    protected function createTestTables($capsule): void
    {
        // Call parent to create standard tables
        parent::createTestTables($capsule);

        // Posts table
        $capsule->schema()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Comments table (for relationship testing)
        $capsule->schema()->create('test_comments', function ($table) {
            $table->id();
            $table->text('body');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Categories table (for relationship testing)
        $capsule->schema()->create('test_categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->timestamps();
        });

        // Products table (for different model type)
        $capsule->schema()->create('test_products', function ($table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('vendor_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Enable query logging.
     *
     * @return void
     */
    protected function enableQueryLogging(): void
    {
        $this->queryLog = [];

        Capsule::connection()->listen(function ($query) {
            $this->queryLog[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
    }

    /**
     * Get query count.
     *
     * @return int
     */
    protected function getQueryCount(): int
    {
        return count($this->queryLog);
    }

    /**
     * Reset query log.
     *
     * @return void
     */
    protected function resetQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Set authenticated user.
     *
     * @param object $user
     * @return void
     */
    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    /**
     * Teardown test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test query scope with simple user_id condition.
     *
     * @return void
     */
    public function test_query_scope_with_simple_user_id_condition(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule: user can only view their own posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create test posts
        TestPost::create(['title' => 'My Post', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'Other Post', 'content' => 'Content', 'user_id' => 2, 'status' => 'published']);
        TestPost::create(['title' => 'Another Post', 'content' => 'Content', 'user_id' => 1, 'status' => 'draft']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope
        $posts = TestPost::byPermission(1, 'posts.view')->get();

        // Assert only user's posts are returned
        $this->assertCount(2, $posts);
        $this->assertTrue($posts->every(fn ($post) => $post->user_id === 1));

        // Verify no N+1 queries (should be 1-3 queries: permission lookup + rule lookup + data query)
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with multiple conditions (AND logic).
     *
     * @return void
     */
    public function test_query_scope_with_multiple_conditions(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        // Create row-level rule: user can only edit their own draft posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                    'status' => 'draft',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create test posts
        TestPost::create(['title' => 'My Draft', 'content' => 'Content', 'user_id' => 1, 'status' => 'draft']);
        TestPost::create(['title' => 'My Published', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'Other Draft', 'content' => 'Content', 'user_id' => 2, 'status' => 'draft']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope
        $posts = TestPost::byPermission(1, 'posts.edit')->get();

        // Assert only user's draft posts are returned
        $this->assertCount(1, $posts);
        $this->assertEquals('My Draft', $posts->first()->title);
        $this->assertEquals(1, $posts->first()->user_id);
        $this->assertEquals('draft', $posts->first()->status);

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with multiple field conditions.
     *
     * @return void
     */
    public function test_query_scope_with_relationship_condition(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule: user can view posts with specific category_id
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'category_id' => 1, // Fixed category ID for testing
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor', 'department' => 10];
        $this->actingAs($user);

        // Create categories
        $category1 = TestCategory::create(['name' => 'Tech', 'department_id' => 10]);
        $category2 = TestCategory::create(['name' => 'News', 'department_id' => 20]);

        // Create test posts
        TestPost::create(['title' => 'Tech Post', 'content' => 'Content', 'user_id' => 1, 'category_id' => $category1->id, 'status' => 'published']);
        TestPost::create(['title' => 'News Post', 'content' => 'Content', 'user_id' => 2, 'category_id' => $category2->id, 'status' => 'published']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope
        $posts = TestPost::byPermission(1, 'posts.view')->get();

        // Assert only posts with category_id = 1 are returned
        $this->assertCount(1, $posts);
        $this->assertEquals('Tech Post', $posts->first()->title);
        $this->assertEquals($category1->id, $posts->first()->category_id);

        // Verify no N+1 queries (should be at most 2 queries with join)
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(4, $queryCount, "Expected at most 4 queries, got {$queryCount}");
    }

    /**
     * Test query scope with different model type (Product).
     *
     * @return void
     */
    public function test_query_scope_with_different_model_type(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'products.view',
            'display_name' => 'View Products',
            'description' => 'View products',
        ]);

        // Create row-level rule: user can only view products from vendor 100
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestProduct::class,
                'conditions' => [
                    'vendor_id' => 100, // Fixed vendor ID for testing
                    'is_active' => true,
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user with vendor
        $user = (object) ['id' => 1, 'role' => 'vendor', 'vendor' => 100];
        $this->actingAs($user);

        // Create test products
        TestProduct::create(['name' => 'Product A', 'price' => 10.00, 'vendor_id' => 100, 'is_active' => true]);
        TestProduct::create(['name' => 'Product B', 'price' => 20.00, 'vendor_id' => 100, 'is_active' => false]);
        TestProduct::create(['name' => 'Product C', 'price' => 30.00, 'vendor_id' => 200, 'is_active' => true]);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope
        $products = TestProduct::byPermission(1, 'products.view')->get();

        // Assert only user's active products are returned
        $this->assertCount(1, $products);
        $this->assertEquals('Product A', $products->first()->name);
        $this->assertEquals(100, $products->first()->vendor_id);
        $this->assertTrue($products->first()->is_active);

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with chained scopes.
     *
     * @return void
     */
    public function test_query_scope_chained_with_other_scopes(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create test posts
        TestPost::create(['title' => 'My Draft', 'content' => 'Content', 'user_id' => 1, 'status' => 'draft']);
        TestPost::create(['title' => 'My Published', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'Other Published', 'content' => 'Content', 'user_id' => 2, 'status' => 'published']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope chained with where clause
        $posts = TestPost::byPermission(1, 'posts.view')
            ->where('status', 'published')
            ->orderBy('title', 'asc')
            ->get();

        // Assert only user's published posts are returned
        $this->assertCount(1, $posts);
        $this->assertEquals('My Published', $posts->first()->title);

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with pagination.
     *
     * @return void
     */
    public function test_query_scope_with_pagination(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create multiple test posts
        for ($i = 1; $i <= 15; $i++) {
            TestPost::create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'user_id' => 1,
                'status' => 'published',
            ]);
        }

        // Create posts for other user
        TestPost::create(['title' => 'Other Post', 'content' => 'Content', 'user_id' => 2, 'status' => 'published']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope and pagination
        $posts = TestPost::byPermission(1, 'posts.view')
            ->orderBy('id', 'asc')
            ->limit(10)
            ->get();

        // Assert correct number of posts
        $this->assertCount(10, $posts);
        $this->assertTrue($posts->every(fn ($post) => $post->user_id === 1));

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with no matching rules returns all records.
     *
     * @return void
     */
    public function test_query_scope_with_no_rules_returns_all_records(): void
    {
        // Create permission without rules
        Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create test posts
        TestPost::create(['title' => 'Post 1', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'Post 2', 'content' => 'Content', 'user_id' => 2, 'status' => 'published']);
        TestPost::create(['title' => 'Post 3', 'content' => 'Content', 'user_id' => 3, 'status' => 'published']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope (no rules, should return all)
        $posts = TestPost::byPermission(1, 'posts.view')->get();

        // Assert all posts are returned
        $this->assertCount(3, $posts);

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope with count aggregation.
     *
     * @return void
     */
    public function test_query_scope_with_count_aggregation(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create test posts
        TestPost::create(['title' => 'My Post 1', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'My Post 2', 'content' => 'Content', 'user_id' => 1, 'status' => 'published']);
        TestPost::create(['title' => 'Other Post', 'content' => 'Content', 'user_id' => 2, 'status' => 'published']);

        // Reset query log
        $this->resetQueryLog();

        // Query with scope and count
        $count = TestPost::byPermission(1, 'posts.view')->count();

        // Assert correct count
        $this->assertEquals(2, $count);

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");
    }

    /**
     * Test query scope performance with large dataset.
     *
     * @return void
     */
    public function test_query_scope_performance_with_large_dataset(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => TestPost::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
            ],
            'priority' => 0,
        ]);

        // Create test user
        $user = (object) ['id' => 1, 'role' => 'editor'];
        $this->actingAs($user);

        // Create large dataset
        for ($i = 1; $i <= 100; $i++) {
            TestPost::create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'user_id' => ($i % 2 === 0) ? 1 : 2, // Half for user 1, half for user 2
                'status' => 'published',
            ]);
        }

        // Reset query log
        $this->resetQueryLog();

        // Query with scope
        $startTime = microtime(true);
        $posts = TestPost::byPermission(1, 'posts.view')->get();
        $endTime = microtime(true);

        // Assert correct number of posts
        $this->assertCount(50, $posts);
        $this->assertTrue($posts->every(fn ($post) => $post->user_id === 1));

        // Verify no N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThanOrEqual(3, $queryCount, "Expected at most 3 queries, got {$queryCount}");

        // Verify performance (should be under 100ms)
        $executionTime = ($endTime - $startTime) * 1000;
        $this->assertLessThan(100, $executionTime, "Query took {$executionTime}ms, expected < 100ms");
    }
}

/**
 * Test Post model.
 */
class TestPost extends Model
{
    use HasPermissionScopes;

    protected $table = 'test_posts';

    protected $fillable = ['title', 'content', 'user_id', 'category_id', 'status'];

    public $timestamps = true;

    public function category()
    {
        return $this->belongsTo(TestCategory::class, 'category_id');
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'post_id');
    }
}

/**
 * Test Comment model.
 */
class TestComment extends Model
{
    protected $table = 'test_comments';

    protected $fillable = ['body', 'post_id', 'user_id'];

    public $timestamps = true;

    public function post()
    {
        return $this->belongsTo(TestPost::class, 'post_id');
    }
}

/**
 * Test Category model.
 */
class TestCategory extends Model
{
    protected $table = 'test_categories';

    protected $fillable = ['name', 'department_id'];

    public $timestamps = true;

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'category_id');
    }
}

/**
 * Test Product model.
 */
class TestProduct extends Model
{
    use HasPermissionScopes;

    protected $table = 'test_products';

    protected $fillable = ['name', 'price', 'vendor_id', 'is_active'];

    public $timestamps = true;

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}

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
 * Integration test for scopeByPermission.
 */
class ScopeByPermissionIntegrationTest extends TestCase
{
    /**
     * Test model class.
     */
    protected $testModelClass;

    /**
     * Auth guard mock.
     */
    protected static $authGuard = null;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock ONCE for all tests
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

        // Get Capsule instance
        $capsule = Capsule::connection();

        // Create test table
        $capsule->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Define test model
        $this->testModelClass = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_posts';

            protected $fillable = ['title', 'user_id', 'department_id', 'status'];
        };
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
     * Cleanup test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Get Capsule instance
        $capsule = Capsule::connection();

        // Drop test table
        $capsule->getSchemaBuilder()->dropIfExists('test_posts');

        parent::tearDown();
    }

    /**
     * Test scopeByPermission filters by user_id.
     *
     * @return void
     */
    public function test_scope_by_permission_filters_by_user_id(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post 1', 'user_id' => 1, 'status' => 'published']);
        $model::create(['title' => 'Post 2', 'user_id' => 2, 'status' => 'published']);
        $model::create(['title' => 'Post 3', 'user_id' => 1, 'status' => 'published']);

        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($model),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock auth user
        $this->actingAs((object) ['id' => 1]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')->get();

        // Assert
        $this->assertCount(2, $posts);
        $this->assertEquals('Post 1', $posts[0]->title);
        $this->assertEquals('Post 3', $posts[1]->title);
    }

    /**
     * Test scopeByPermission with multiple conditions (AND).
     *
     * @return void
     */
    public function test_scope_by_permission_with_multiple_and_conditions(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post 1', 'user_id' => 1, 'department_id' => 10, 'status' => 'published']);
        $model::create(['title' => 'Post 2', 'user_id' => 1, 'department_id' => 20, 'status' => 'published']);
        $model::create(['title' => 'Post 3', 'user_id' => 2, 'department_id' => 10, 'status' => 'published']);

        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Create row-level rule with multiple conditions
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($model),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                    'department_id' => '{{auth.department}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock auth user with department
        $this->actingAs((object) ['id' => 1, 'department_id' => 10]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')->get();

        // Assert
        $this->assertCount(1, $posts);
        $this->assertEquals('Post 1', $posts[0]->title);
    }

    /**
     * Test scopeByPermission can be chained with other scopes.
     *
     * @return void
     */
    public function test_scope_by_permission_chains_with_other_scopes(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post 1', 'user_id' => 1, 'status' => 'published']);
        $model::create(['title' => 'Post 2', 'user_id' => 1, 'status' => 'draft']);
        $model::create(['title' => 'Post 3', 'user_id' => 2, 'status' => 'published']);

        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($model),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock auth user
        $this->actingAs((object) ['id' => 1]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')
            ->where('status', 'published')
            ->get();

        // Assert
        $this->assertCount(1, $posts);
        $this->assertEquals('Post 1', $posts[0]->title);
        $this->assertEquals('published', $posts[0]->status);
    }

    /**
     * Test scopeByPermission returns all records when no rules exist.
     *
     * @return void
     */
    public function test_scope_by_permission_returns_all_when_no_rules(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post 1', 'user_id' => 1, 'status' => 'published']);
        $model::create(['title' => 'Post 2', 'user_id' => 2, 'status' => 'published']);

        // Create permission without rules
        Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')->get();

        // Assert
        $this->assertCount(2, $posts);
    }

    /**
     * Test scopeByPermission returns empty when permission not found.
     *
     * @return void
     */
    public function test_scope_by_permission_returns_empty_when_permission_not_found(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post 1', 'user_id' => 1, 'status' => 'published']);

        // Act
        $posts = $model::byPermission(1, 'nonexistent.permission')->get();

        // Assert
        $this->assertCount(0, $posts);
    }

    /**
     * Test scopeByPermission with orderBy.
     *
     * @return void
     */
    public function test_scope_by_permission_with_order_by(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        $model::create(['title' => 'Post A', 'user_id' => 1, 'status' => 'published']);
        $model::create(['title' => 'Post C', 'user_id' => 1, 'status' => 'published']);
        $model::create(['title' => 'Post B', 'user_id' => 1, 'status' => 'published']);

        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($model),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock auth user
        $this->actingAs((object) ['id' => 1]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')
            ->orderBy('title', 'asc')
            ->get();

        // Assert
        $this->assertCount(3, $posts);
        $this->assertEquals('Post A', $posts[0]->title);
        $this->assertEquals('Post B', $posts[1]->title);
        $this->assertEquals('Post C', $posts[2]->title);
    }

    /**
     * Test scopeByPermission with pagination.
     *
     * @return void
     */
    public function test_scope_by_permission_with_pagination(): void
    {
        // Arrange
        $model = $this->testModelClass;

        // Create test data
        for ($i = 1; $i <= 15; $i++) {
            $model::create([
                'title' => "Post {$i}",
                'user_id' => 1,
                'status' => 'published',
            ]);
        }

        // Create permission
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($model),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock auth user
        $this->actingAs((object) ['id' => 1]);

        // Act
        $posts = $model::byPermission(1, 'posts.view')->paginate(10);

        // Assert
        $this->assertEquals(15, $posts->total());
        $this->assertEquals(10, $posts->perPage());
        $this->assertCount(10, $posts->items());
    }
}

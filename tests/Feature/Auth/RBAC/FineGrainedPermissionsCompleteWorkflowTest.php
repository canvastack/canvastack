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
 * Complete workflow integration test for Fine-Grained Permissions System.
 *
 * Tests the entire flow from permission creation to enforcement.
 */
class FineGrainedPermissionsCompleteWorkflowTest extends TestCase
{
    /**
     * Test model class.
     */
    protected $postModel;

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

        // Create test table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('excerpt')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Define test model
        $this->postModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_posts';

            protected $fillable = [
                'title', 'content', 'excerpt', 'user_id',
                'department_id', 'status', 'featured', 'metadata',
            ];

            protected $casts = [
                'metadata' => 'array',
                'featured' => 'boolean',
            ];
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
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->dropIfExists('test_posts');

        parent::tearDown();
    }

    /**
     * Test complete workflow: Create permission, add rules, check access.
     *
     * @return void
     */
    public function test_complete_workflow_from_permission_to_enforcement(): void
    {
        // Arrange - Create role and permission
        $role = Role::create([
            'name' => 'editor',
            'display_name' => 'Editor',
            'description' => 'Content editor',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $role->permissions()->attach($permission->id);

        // Create user
        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

        // Create test posts
        $ownPost = $this->postModel::create([
            'title' => 'My Post',
            'content' => 'My content',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $otherPost = $this->postModel::create([
            'title' => 'Other Post',
            'content' => 'Other content',
            'user_id' => 999,
            'status' => 'draft',
        ]);

        // Add row-level rule: Can only edit own posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Add column-level rule: Cannot edit status field
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title', 'content', 'excerpt'],
                'denied_columns' => ['status', 'featured'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Act - Authenticate user
        $this->actingAs($user);

        $gate = app(Gate::class);

        // Assert - Row-level access
        $this->assertTrue(
            $gate->canAccessRow($user->id, 'posts.edit', $ownPost),
            'User should be able to access own post'
        );

        $this->assertFalse(
            $gate->canAccessRow($user->id, 'posts.edit', $otherPost),
            'User should not be able to access other user post'
        );

        // Assert - Column-level access
        $this->assertTrue(
            $gate->canAccessColumn($user->id, 'posts.edit', $ownPost, 'title'),
            'User should be able to access title column'
        );

        $this->assertFalse(
            $gate->canAccessColumn($user->id, 'posts.edit', $ownPost, 'status'),
            'User should not be able to access status column'
        );

        // Assert - Query scope
        $accessiblePosts = $this->postModel::byPermission($user->id, 'posts.edit')->get();
        $this->assertCount(1, $accessiblePosts);
        $this->assertEquals($ownPost->id, $accessiblePosts->first()->id);
    }

    /**
     * Test workflow with user overrides.
     *
     * @return void
     */
    public function test_workflow_with_user_overrides(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        // Create posts
        $post1 = $this->postModel::create([
            'title' => 'Post 1',
            'content' => 'Content 1',
            'user_id' => 999,
            'status' => 'draft',
        ]);

        $post2 = $this->postModel::create([
            'title' => 'Post 2',
            'content' => 'Content 2',
            'user_id' => 999,
            'status' => 'draft',
        ]);

        // Add row-level rule: Can only edit own posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Add user override: Allow access to specific post
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => get_class($this->postModel),
            'model_id' => $post1->id,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
        ]);

        // Act
        $this->actingAs($user);
        $gate = app(Gate::class);

        // Assert - Override allows access to post1
        $this->assertTrue(
            $gate->canAccessRow($user->id, 'posts.edit', $post1),
            'User should be able to access post1 due to override'
        );

        // Assert - No override for post2
        $this->assertFalse(
            $gate->canAccessRow($user->id, 'posts.edit', $post2),
            'User should not be able to access post2'
        );
    }

    /**
     * Test workflow with JSON attribute rules.
     *
     * @return void
     */
    public function test_workflow_with_json_attribute_rules(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'metadata' => [
                'seo' => ['title' => 'SEO Title', 'description' => 'SEO Desc'],
                'featured' => true,
                'promoted' => false,
            ],
        ]);

        // Add JSON attribute rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'denied_paths' => ['featured', 'promoted'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Act
        $this->actingAs($user);
        $gate = app(Gate::class);

        // Assert - Can access SEO fields
        $this->assertTrue(
            $gate->canAccessJsonAttribute($user->id, 'posts.edit', $post, 'metadata', 'seo.title'),
            'User should be able to access seo.title'
        );

        // Assert - Cannot access featured field
        $this->assertFalse(
            $gate->canAccessJsonAttribute($user->id, 'posts.edit', $post, 'metadata', 'featured'),
            'User should not be able to access featured'
        );
    }

    /**
     * Test workflow with conditional rules.
     *
     * @return void
     */
    public function test_workflow_with_conditional_rules(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        // Create posts with different statuses
        $draftPost = $this->postModel::create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $publishedPost = $this->postModel::create([
            'title' => 'Published Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'status' => 'published',
        ]);

        // Add conditional rule: Can only edit draft posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'condition' => "status === 'draft' AND user_id === {{auth.id}}",
                'allowed_operators' => ['===', 'AND'],
            ],
            'priority' => 0,
        ]);

        // Act
        $this->actingAs($user);
        $gate = app(Gate::class);

        // Assert - Can access draft post
        $this->assertTrue(
            $gate->canAccessRow($user->id, 'posts.edit', $draftPost),
            'User should be able to access draft post'
        );

        // Assert - Cannot access published post
        $this->assertFalse(
            $gate->canAccessRow($user->id, 'posts.edit', $publishedPost),
            'User should not be able to access published post'
        );
    }

    /**
     * Test workflow with multiple rule types combined.
     *
     * @return void
     */
    public function test_workflow_with_multiple_rule_types_combined(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'status' => 'draft',
            'metadata' => ['seo' => ['title' => 'SEO']],
        ]);

        // Add row-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Add column-level rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Add JSON attribute rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'denied_paths' => [],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Act
        $this->actingAs($user);
        $gate = app(Gate::class);

        // Assert - All rule types work together
        $this->assertTrue($gate->canAccessRow($user->id, 'posts.edit', $post));
        $this->assertTrue($gate->canAccessColumn($user->id, 'posts.edit', $post, 'title'));
        $this->assertFalse($gate->canAccessColumn($user->id, 'posts.edit', $post, 'status'));
        $this->assertTrue($gate->canAccessJsonAttribute($user->id, 'posts.edit', $post, 'metadata', 'seo.title'));
    }

    /**
     * Test workflow with caching.
     *
     * @return void
     */
    public function test_workflow_with_caching(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);
        $gate = app(Gate::class);

        // Act - First call (cache miss)
        $result1 = $gate->canAccessRow($user->id, 'posts.edit', $post);

        // Act - Second call (cache hit)
        $result2 = $gate->canAccessRow($user->id, 'posts.edit', $post);

        // Assert - Both calls return same result
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertEquals($result1, $result2);
    }
}

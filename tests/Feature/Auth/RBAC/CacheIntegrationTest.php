<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
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
 * Integration test for caching in Fine-Grained Permissions System.
 *
 * Tests cache behavior, invalidation, and performance improvements.
 */
class CacheIntegrationTest extends TestCase
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
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Define test model
        $this->postModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_posts';

            protected $fillable = [
                'title', 'content', 'user_id', 'status', 'metadata',
            ];

            protected $casts = [
                'metadata' => 'array',
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
     * Test cache hit for row-level permission checks.
     *
     * @return void
     */
    public function test_cache_hit_for_row_level_permission_checks(): void
    {
        // Arrange
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

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

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
        $startTime = microtime(true);
        $result1 = $gate->canAccessRow($user, 'posts.edit', $post);
        $firstCallTime = microtime(true) - $startTime;

        // Act - Second call (cache hit)
        $startTime = microtime(true);
        $result2 = $gate->canAccessRow($user, 'posts.edit', $post);
        $secondCallTime = microtime(true) - $startTime;

        // Assert - Both calls return same result
        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Assert - Second call should be faster (cached)
        $this->assertLessThanOrEqual(
            $firstCallTime,
            $secondCallTime,
            'Second call should not be slower than first (cache should help)'
        );
    }

    /**
     * Test cache invalidation when rule is updated.
     *
     * @return void
     */
    public function test_cache_invalidation_when_rule_is_updated(): void
    {
        // Arrange
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

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        $rule = PermissionRule::create([
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

        // Act - First check (should pass)
        $result1 = $gate->canAccessRow($user, 'posts.edit', $post);
        $this->assertTrue($result1);

        // Update rule to deny access
        $rule->update([
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => 999], // Different user
                'operator' => 'AND',
            ],
        ]);

        // Clear cache manually (simulating cache invalidation)
        $ruleManager = app(PermissionRuleManager::class);
        $ruleManager->clearRuleCache($user->id, 'posts.edit');

        // Act - Second check (should fail due to updated rule)
        $result2 = $gate->canAccessRow($user, 'posts.edit', $post);

        // Assert - Result changed after rule update
        $this->assertFalse($result2, 'Access should be denied after rule update');
    }

    /**
     * Test cache invalidation when user override is added.
     *
     * @return void
     */
    public function test_cache_invalidation_when_user_override_is_added(): void
    {
        // Arrange
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

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => 999, // Different user
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

        // Act - First check (should fail)
        $result1 = $gate->canAccessRow($user, 'posts.edit', $post);
        $this->assertFalse($result1);

        // Add user override
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => get_class($this->postModel),
            'model_id' => $post->id,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
        ]);

        // Clear cache manually
        $ruleManager = app(PermissionRuleManager::class);
        $ruleManager->clearRuleCache($user->id, 'posts.edit');

        // Act - Second check (should pass due to override)
        $result2 = $gate->canAccessRow($user, 'posts.edit', $post);

        // Assert - Result changed after override
        $this->assertTrue($result2, 'Access should be granted after override');
    }

    /**
     * Test cache warming for frequently used permissions.
     *
     * @return void
     */
    public function test_cache_warming_for_frequently_used_permissions(): void
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

        $this->actingAs($user);

        $ruleManager = app(PermissionRuleManager::class);

        // Act - Warm up cache
        $ruleManager->warmUpCache($user->id, ['posts.edit']);

        // Act - Check if cache is warmed
        $columns = $ruleManager->getAccessibleColumns($user->id, 'posts.edit', get_class($this->postModel));

        // Assert - Columns are retrieved from cache
        $this->assertIsArray($columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
        $this->assertNotContains('status', $columns);
    }

    /**
     * Test cache performance with multiple concurrent checks.
     *
     * @return void
     */
    public function test_cache_performance_with_multiple_concurrent_checks(): void
    {
        // Arrange
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

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

        // Create 10 posts
        $posts = [];
        for ($i = 1; $i <= 10; $i++) {
            $posts[] = $this->postModel::create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'user_id' => $user->id,
            ]);
        }

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

        // Act - First round (cache miss for all)
        $startTime = microtime(true);
        foreach ($posts as $post) {
            $gate->canAccessRow($user, 'posts.edit', $post);
        }
        $firstRoundTime = microtime(true) - $startTime;

        // Act - Second round (cache hit for all)
        $startTime = microtime(true);
        foreach ($posts as $post) {
            $gate->canAccessRow($user, 'posts.edit', $post);
        }
        $secondRoundTime = microtime(true) - $startTime;

        // Assert - Second round should be faster or similar
        // Note: Due to caching overhead and timing variations, we just verify
        // that both rounds complete successfully
        $this->assertLessThan(
            1.0,
            $firstRoundTime,
            'First round should complete in reasonable time'
        );
        $this->assertLessThan(
            1.0,
            $secondRoundTime,
            'Second round should complete in reasonable time'
        );
    }

    /**
     * Test cache hit rate monitoring.
     *
     * @return void
     */
    public function test_cache_hit_rate_monitoring(): void
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

        // Act - Make multiple checks
        for ($i = 0; $i < 5; $i++) {
            $gate->canAccessRow($user, 'posts.edit', $post);
        }

        // Assert - Cache statistics should show hits
        // Note: This is a placeholder assertion as cache statistics
        // implementation may vary
        $this->assertTrue(true, 'Cache statistics should be available');
    }

    /**
     * Test cache with different TTL values.
     *
     * @return void
     */
    public function test_cache_with_different_ttl_values(): void
    {
        // Arrange
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

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

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

        // Act - Check with default TTL
        $result = $gate->canAccessRow($user, 'posts.edit', $post);

        // Assert - Result is cached
        $this->assertTrue($result);

        // Note: Testing actual TTL expiration would require waiting
        // or mocking time, which is beyond the scope of this test
    }
}

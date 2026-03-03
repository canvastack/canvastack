<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for TableBuilder row-level permission filtering.
 */
class TableBuilderRowFilteringTest extends TestCase
{
    private TableBuilder $table;

    private PermissionRuleManager $ruleManager;

    private static $authGuard;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.row_level.enabled' => true,
            'canvastack-rbac.fine_grained.cache.enabled' => true,
            'canvastack-rbac.fine_grained.cache.ttl.row' => 3600,
        ]);

        // Setup auth guard FIRST (before any other bindings)
        if (!self::$authGuard) {
            self::$authGuard = new class () {
                private $user;

                public function setUser($user): void
                {
                    $this->user = $user;
                }

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user?->id;
                }

                public function check(): bool
                {
                    return $this->user !== null;
                }
            };
        }

        // Bind auth guard to container (CRITICAL: must be bound before TemplateVariableResolver)
        app()->singleton('auth', function () {
            return new class (self::$authGuard) {
                private $guard;

                public function __construct($guard)
                {
                    $this->guard = $guard;
                }

                public function guard($name = null)
                {
                    return $this->guard;
                }

                public function user()
                {
                    return $this->guard->user();
                }

                public function id()
                {
                    return $this->guard->id();
                }

                public function check(): bool
                {
                    return $this->guard->check();
                }
            };
        });

        // Force fresh TableBuilder instance
        if (app()->bound(TableBuilder::class)) {
            app()->forgetInstance(TableBuilder::class);
        }

        // Force fresh PermissionRuleManager instance
        if (app()->bound('canvastack.rbac.rule.manager')) {
            app()->forgetInstance('canvastack.rbac.rule.manager');
        }

        $this->table = app(TableBuilder::class);
        $this->ruleManager = app('canvastack.rbac.rule.manager');

        // Clean up any existing data
        Post::query()->delete();
        User::query()->delete();
        Permission::query()->delete();
        PermissionRule::query()->delete();

        // Seed test data
        $this->seedTestData();
    }

    /**
     * Set authenticated user.
     */
    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    protected function createTestTables(Capsule $capsule): void
    {
        // Create posts table
        if (!Capsule::schema()->hasTable('posts')) {
            Capsule::schema()->create('posts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('content');
                $table->string('status')->default('draft');
                $table->timestamps();
            });
        }

        // Create users table
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->softDeletes(); // Add soft deletes support
                $table->timestamps();
            });
        }

        // Create permissions table
        if (!Capsule::schema()->hasTable('permissions')) {
            Capsule::schema()->create('permissions', function ($table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create permission_rules table
        if (!Capsule::schema()->hasTable('permission_rules')) {
            Capsule::schema()->create('permission_rules', function ($table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->enum('rule_type', ['row', 'column', 'json_attribute', 'conditional']);
                $table->json('rule_config');
                $table->integer('priority')->default(0);
                $table->timestamps();

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');

                $table->index(['permission_id', 'rule_type']);
            });
        }
    }

    protected function seedTestData(): void
    {
        // Create test users
        User::create(['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com']);
        User::create(['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com']);

        // Create test posts
        Post::create(['id' => 1, 'user_id' => 1, 'title' => 'Post 1', 'content' => 'Content 1', 'status' => 'draft']);
        Post::create(['id' => 2, 'user_id' => 1, 'title' => 'Post 2', 'content' => 'Content 2', 'status' => 'published']);
        Post::create(['id' => 3, 'user_id' => 2, 'title' => 'Post 3', 'content' => 'Content 3', 'status' => 'draft']);
        Post::create(['id' => 4, 'user_id' => 2, 'title' => 'Post 4', 'content' => 'Content 4', 'status' => 'published']);

        // Create test permission
        Permission::create([
            'id' => 1,
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);
    }

    /**
     * Test that row filtering is applied when permission is set.
     */
    public function test_row_filtering_is_applied_when_permission_is_set(): void
    {
        // Create row-level rule: users can only see their own posts
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should only see posts from user 1
        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]['user_id']);
        $this->assertEquals(1, $data[1]['user_id']);
    }

    /**
     * Test that row filtering is not applied when permission is not set.
     */
    public function test_row_filtering_is_not_applied_when_permission_is_not_set(): void
    {
        // Create row-level rule
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table WITHOUT permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should see all posts (no filtering)
        $this->assertCount(4, $data);
    }

    /**
     * Test that row filtering works with multiple conditions (AND).
     */
    public function test_row_filtering_with_multiple_and_conditions(): void
    {
        // Create row-level rule: users can only see their own draft posts
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                    'status' => 'draft',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should only see draft posts from user 1
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['user_id']);
        $this->assertEquals('draft', $data[0]['status']);
    }

    /**
     * Test that row filtering works with OR conditions.
     */
    public function test_row_filtering_with_or_conditions(): void
    {
        // Create row-level rule: users can see their own posts OR published posts
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                    'status' => 'published',
                ],
                'operator' => 'OR',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should see:
        // - All posts from user 1 (2 posts)
        // - Published posts from user 2 (1 post)
        // Total: 3 posts
        $this->assertCount(3, $data);
    }

    /**
     * Test that row filtering is not applied when user is not authenticated.
     */
    public function test_row_filtering_is_not_applied_when_user_is_not_authenticated(): void
    {
        // Create row-level rule
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Clear authenticated user (set to null)
        self::$authGuard->setUser(null);

        // Setup table with permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should see all posts (no filtering when not authenticated)
        $this->assertCount(4, $data);
    }

    /**
     * Test that row filtering works with caching.
     */
    public function test_row_filtering_works_with_caching(): void
    {
        // Create row-level rule
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission and caching
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->cache(300); // 5 minutes cache
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data (first call - should cache)
        $result1 = $this->table->getData();
        $data1 = $result1['data'];

        // Get data (second call - should use cache)
        $result2 = $this->table->getData();
        $data2 = $result2['data'];

        // Both should return same filtered data
        $this->assertCount(2, $data1);
        $this->assertCount(2, $data2);
        $this->assertEquals($data1, $data2);
    }

    /**
     * Test that row filtering returns empty result when no rules match.
     */
    public function test_row_filtering_returns_empty_when_no_rules_match(): void
    {
        // Create row-level rule that matches no posts
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'status' => 'archived', // No posts have this status
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should return empty array
        $this->assertCount(0, $data);
    }

    /**
     * Test that row filtering is applied before other filters.
     */
    public function test_row_filtering_is_applied_before_other_filters(): void
    {
        // Create row-level rule: users can only see their own posts
        PermissionRule::create([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Mock authenticated user
        $this->actingAs(User::find(1));

        // Setup table with permission and additional filter
        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->where('status', '=', 'published'); // Additional filter
        $this->table->setFields(['title:Title', 'status:Status']);
        $this->table->format();

        // Get data
        $result = $this->table->getData();
        $data = $result['data'];

        // Should only see published posts from user 1
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['user_id']);
        $this->assertEquals('published', $data[0]['status']);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        Post::query()->delete();
        User::query()->delete();
        Permission::query()->delete();
        PermissionRule::query()->delete();

        // Clear cache
        if (app()->bound('cache')) {
            app('cache')->flush();
        }

        // Clear rule manager cache
        if (app()->bound('canvastack.rbac.rule.manager')) {
            $ruleManager = app('canvastack.rbac.rule.manager');
            if (method_exists($ruleManager, 'clearCache')) {
                $ruleManager->clearCache();
            }
        }

        parent::tearDown();
    }
}

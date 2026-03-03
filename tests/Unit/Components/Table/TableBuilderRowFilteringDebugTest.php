<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Debug test for TableBuilder row filtering.
 */
class TableBuilderRowFilteringDebugTest extends TestCase
{
    private TableBuilder $table;

    private static $authGuard;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->table = app(TableBuilder::class);

        $this->seedTestData();
    }

    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    protected function createTestTables(Capsule $capsule): void
    {
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

        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Capsule::schema()->hasTable('permissions')) {
            Capsule::schema()->create('permissions', function ($table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

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
        User::create(['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com']);
        User::create(['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com']);

        Post::create(['id' => 1, 'user_id' => 1, 'title' => 'Post 1', 'content' => 'Content 1', 'status' => 'draft']);
        Post::create(['id' => 2, 'user_id' => 1, 'title' => 'Post 2', 'content' => 'Content 2', 'status' => 'published']);
        Post::create(['id' => 3, 'user_id' => 2, 'title' => 'Post 3', 'content' => 'Content 3', 'status' => 'draft']);
        Post::create(['id' => 4, 'user_id' => 2, 'title' => 'Post 4', 'content' => 'Content 4', 'status' => 'published']);

        Permission::create([
            'id' => 1,
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
        ]);
    }

    public function test_debug_permission_is_set(): void
    {
        $this->table->setPermission('posts.view');

        // Use reflection to check if permission is set
        $reflection = new \ReflectionClass($this->table);
        $property = $reflection->getProperty('permission');
        $property->setAccessible(true);

        $this->assertEquals('posts.view', $property->getValue($this->table));
    }

    public function test_debug_query_without_permission(): void
    {
        $this->actingAs(User::find(1));

        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setFields(['title:Title']);
        $this->table->format();

        $result = $this->table->getData();
        $data = $result['data'];

        // Should see all 4 posts
        $this->assertCount(4, $data);
    }

    public function test_debug_query_with_permission_but_no_rules(): void
    {
        $this->actingAs(User::find(1));

        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title']);
        $this->table->format();

        $result = $this->table->getData();
        $data = $result['data'];

        // Should see all 4 posts (no rules defined)
        $this->assertCount(4, $data);
    }

    public function test_debug_query_with_permission_and_rules(): void
    {
        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.row_level.enabled' => true,
        ]);

        // Create rule
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

        $this->actingAs(User::find(1));

        // Check if rule manager is bound
        $this->assertTrue(app()->bound('canvastack.rbac.rule.manager'), 'Rule manager should be bound');

        // Check if auth is working
        $authUser = app('auth')->user();
        $this->assertNotNull($authUser, 'User should be authenticated');
        $this->assertEquals(1, $authUser->id, 'User ID should be 1');

        $this->table->setContext('admin');
        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title:Title']);
        $this->table->format();

        $result = $this->table->getData();
        $data = $result['data'];

        // Debug output
        echo "\nTotal posts: " . count($data) . "\n";
        foreach ($data as $post) {
            echo "Post ID: {$post['id']}, User ID: {$post['user_id']}\n";
        }

        // Should see only 2 posts from user 1
        $this->assertCount(2, $data);
    }

    protected function tearDown(): void
    {
        Post::query()->delete();
        User::query()->delete();
        Permission::query()->delete();
        PermissionRule::query()->delete();

        parent::tearDown();
    }
}

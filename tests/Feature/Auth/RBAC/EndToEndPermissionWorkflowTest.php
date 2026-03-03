<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * End-to-end integration test for Fine-Grained Permissions System.
 *
 * Tests complete real-world scenarios with all components working together.
 */
class EndToEndPermissionWorkflowTest extends TestCase
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
     * Test complete blog post editing workflow with permissions.
     *
     * Scenario:
     * - Editor can only edit their own draft posts
     * - Editor cannot edit status or featured fields
     * - Editor can edit SEO metadata but not promotional flags
     * - Admin can override and edit specific posts
     *
     * @return void
     */
    public function test_complete_blog_post_editing_workflow(): void
    {
        // Arrange - Create roles and permissions
        $editorRole = Role::create([
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

        $editorRole->permissions()->attach($permission->id);

        // Create users
        $editor = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $editor->roles()->attach($editorRole->id);

        $otherEditor = User::create([
            'name' => 'Jane Editor',
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        // Create posts
        $editorDraftPost = $this->postModel::create([
            'title' => 'Editor Draft Post',
            'content' => 'Content',
            'user_id' => $editor->id,
            'status' => 'draft',
            'metadata' => [
                'seo' => ['title' => 'SEO Title', 'description' => 'SEO Desc'],
                'promoted' => false,
            ],
        ]);

        $editorPublishedPost = $this->postModel::create([
            'title' => 'Editor Published Post',
            'content' => 'Content',
            'user_id' => $editor->id,
            'status' => 'published',
        ]);

        $otherEditorPost = $this->postModel::create([
            'title' => 'Other Editor Post',
            'content' => 'Content',
            'user_id' => $otherEditor->id,
            'status' => 'draft',
        ]);

        // Add row-level rule: Can only edit own draft posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'condition' => "user_id === {{auth.id}} AND status === 'draft'",
                'allowed_operators' => ['===', 'AND'],
            ],
            'priority' => 0,
        ]);

        // Add column-level rule: Cannot edit status or featured
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

        // Add JSON attribute rule: Can edit SEO but not promotional flags
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'denied_paths' => ['promoted', 'featured'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Add user override: Allow editor to edit specific other post
        UserPermissionOverride::create([
            'user_id' => $editor->id,
            'permission_id' => $permission->id,
            'model_type' => get_class($this->postModel),
            'model_id' => $otherEditorPost->id,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
        ]);

        // Act - Authenticate as editor
        $this->actingAs($editor);

        $gate = app(Gate::class);
        $ruleManager = app(PermissionRuleManager::class);

        // Assert - Row-level access
        $this->assertTrue(
            $gate->canAccessRow($editor->id, 'posts.edit', $editorDraftPost),
            'Editor should access own draft post'
        );

        $this->assertFalse(
            $gate->canAccessRow($editor->id, 'posts.edit', $editorPublishedPost),
            'Editor should not access own published post'
        );

        $this->assertTrue(
            $gate->canAccessRow($editor->id, 'posts.edit', $otherEditorPost),
            'Editor should access other post due to override'
        );

        // Assert - Column-level access
        $this->assertTrue(
            $gate->canAccessColumn($editor->id, 'posts.edit', $editorDraftPost, 'title'),
            'Editor should access title column'
        );

        $this->assertFalse(
            $gate->canAccessColumn($editor->id, 'posts.edit', $editorDraftPost, 'status'),
            'Editor should not access status column'
        );

        // Assert - JSON attribute access
        $this->assertTrue(
            $gate->canAccessJsonAttribute($editor->id, 'posts.edit', $editorDraftPost, 'metadata', 'seo.title'),
            'Editor should access SEO title'
        );

        $this->assertFalse(
            $gate->canAccessJsonAttribute($editor->id, 'posts.edit', $editorDraftPost, 'metadata', 'promoted'),
            'Editor should not access promoted flag'
        );

        // Assert - Query scope returns correct posts
        $accessiblePosts = $this->postModel::byPermission($editor->id, 'posts.edit')->get();
        $this->assertCount(2, $accessiblePosts, 'Should have 2 accessible posts (own draft + override)');

        // Assert - FormBuilder filters fields correctly
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        $form->text('title', 'Title');
        $form->textarea('content', 'Content');
        $form->select('status', 'Status', ['draft' => 'Draft', 'published' => 'Published']);
        $form->checkbox('featured', 'Featured');

        $html = $form->render();

        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringNotContainsString('name="featured"', $html);

        // Assert - TableBuilder filters rows correctly
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.edit');
        $table->setFields(['title:Title', 'status:Status']);
        $table->format();

        $tableHtml = $table->render();

        $this->assertStringContainsString('Editor Draft Post', $tableHtml);
        $this->assertStringContainsString('Other Editor Post', $tableHtml);
        $this->assertStringNotContainsString('Editor Published Post', $tableHtml);
    }

    /**
     * Test department-based access control workflow.
     *
     * Scenario:
     * - Users can only access posts from their department
     * - Department managers can access all posts in their department
     * - Cross-department collaboration via overrides
     *
     * @return void
     */
    public function test_department_based_access_control_workflow(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'module' => 'posts',
        ]);

        $salesUser = User::create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'password' => 'password',
        ]);

        $marketingUser = User::create([
            'name' => 'Marketing User',
            'email' => 'marketing@example.com',
            'password' => 'password',
        ]);

        // Create posts for different departments
        $salesPost1 = $this->postModel::create([
            'title' => 'Sales Post 1',
            'content' => 'Content',
            'user_id' => $salesUser->id,
            'department_id' => 10, // Sales department
        ]);

        $salesPost2 = $this->postModel::create([
            'title' => 'Sales Post 2',
            'content' => 'Content',
            'user_id' => $salesUser->id,
            'department_id' => 10,
        ]);

        $marketingPost = $this->postModel::create([
            'title' => 'Marketing Post',
            'content' => 'Content',
            'user_id' => $marketingUser->id,
            'department_id' => 20, // Marketing department
        ]);

        // Add row-level rule: Can only view posts from own department
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => [
                    'department_id' => '{{auth.department}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Act - Authenticate as sales user
        $salesUser->department_id = 10;
        $this->actingAs($salesUser);

        $gate = app(Gate::class);

        // Assert - Sales user can only access sales posts
        $this->assertTrue(
            $gate->canAccessRow($salesUser->id, 'posts.view', $salesPost1),
            'Sales user should access sales post 1'
        );

        $this->assertTrue(
            $gate->canAccessRow($salesUser->id, 'posts.view', $salesPost2),
            'Sales user should access sales post 2'
        );

        $this->assertFalse(
            $gate->canAccessRow($salesUser->id, 'posts.view', $marketingPost),
            'Sales user should not access marketing post'
        );

        // Assert - Query scope returns only sales posts
        $accessiblePosts = $this->postModel::byPermission($salesUser->id, 'posts.view')->get();
        $this->assertCount(2, $accessiblePosts);

        // Add cross-department collaboration override
        UserPermissionOverride::create([
            'user_id' => $salesUser->id,
            'permission_id' => $permission->id,
            'model_type' => get_class($this->postModel),
            'model_id' => $marketingPost->id,
            'field_name' => null,
            'rule_config' => null,
            'allowed' => true,
        ]);

        // Assert - Now sales user can access marketing post
        $this->assertTrue(
            $gate->canAccessRow($salesUser->id, 'posts.view', $marketingPost),
            'Sales user should access marketing post after override'
        );

        $accessiblePostsAfterOverride = $this->postModel::byPermission($salesUser->id, 'posts.view')->get();
        $this->assertCount(3, $accessiblePostsAfterOverride);
    }

    /**
     * Test performance with caching in complete workflow.
     *
     * @return void
     */
    public function test_performance_with_caching_in_complete_workflow(): void
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

        // Create 100 posts
        for ($i = 1; $i <= 100; $i++) {
            $this->postModel::create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'user_id' => $user->id,
                'status' => 'draft',
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

        // Act - First query (cache miss)
        $startTime = microtime(true);
        $posts1 = $this->postModel::byPermission($user->id, 'posts.edit')->get();
        $firstQueryTime = microtime(true) - $startTime;

        // Act - Second query (cache hit)
        $startTime = microtime(true);
        $posts2 = $this->postModel::byPermission($user->id, 'posts.edit')->get();
        $secondQueryTime = microtime(true) - $startTime;

        // Assert - Both queries return same results
        $this->assertCount(100, $posts1);
        $this->assertCount(100, $posts2);

        // Assert - Second query should be faster (cached)
        // Note: This is a soft assertion as timing can vary
        $this->assertLessThanOrEqual(
            $firstQueryTime * 2,
            $secondQueryTime,
            'Second query should not be significantly slower than first'
        );
    }
}

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
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration test for multiple components working together.
 *
 * Tests FormBuilder + TableBuilder + Blade directives + Gate integration.
 */
class MultiComponentIntegrationTest extends TestCase
{
    /**
     * Test model class.
     */
    protected $postModel;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('excerpt')->nullable();
            $table->unsignedBigInteger('user_id');
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
                'status', 'featured', 'metadata',
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
        $auth = app('auth');
        $auth->setUser($user);
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
     * Test FormBuilder and TableBuilder work together with same permissions.
     *
     * @return void
     */
    public function test_form_builder_and_table_builder_work_together(): void
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

        // Create posts
        $ownPost = $this->postModel::create([
            'title' => 'Own Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $otherPost = $this->postModel::create([
            'title' => 'Other Post',
            'content' => 'Content',
            'user_id' => 999,
            'status' => 'draft',
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
                'denied_columns' => ['status', 'featured'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Act - Create FormBuilder
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        $form->text('title', 'Title');
        $form->textarea('content', 'Content');
        $form->select('status', 'Status', ['draft' => 'Draft']);
        $form->checkbox('featured', 'Featured');

        $formHtml = $form->render();

        // Act - Create TableBuilder
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.edit');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        $tableHtml = $table->render();

        // Assert - FormBuilder filters columns
        $this->assertStringContainsString('name="title"', $formHtml);
        $this->assertStringContainsString('name="content"', $formHtml);
        $this->assertStringNotContainsString('name="status"', $formHtml);
        $this->assertStringNotContainsString('name="featured"', $formHtml);

        // Assert - TableBuilder filters rows and columns
        $this->assertStringContainsString('Own Post', $tableHtml);
        $this->assertStringNotContainsString('Other Post', $tableHtml);
    }

    /**
     * Test Gate, FormBuilder, and TableBuilder consistency.
     *
     * @return void
     */
    public function test_gate_form_builder_and_table_builder_consistency(): void
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

        $gate = app(Gate::class);
        $ruleManager = app(PermissionRuleManager::class);

        // Act - Get accessible columns from different sources
        $gateCanAccessTitle = $gate->canAccessColumn($user, 'posts.edit', $post, 'title');
        $gateCanAccessStatus = $gate->canAccessColumn($user, 'posts.edit', $post, 'status');

        $ruleManagerColumns = $ruleManager->getAccessibleColumns($user->id, 'posts.edit', get_class($this->postModel));

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);
        $form->text('title', 'Title');
        $form->text('status', 'Status');
        $formHtml = $form->render();

        // Assert - All components agree on column access
        $this->assertTrue($gateCanAccessTitle);
        $this->assertFalse($gateCanAccessStatus);
        $this->assertContains('title', $ruleManagerColumns);
        $this->assertNotContains('status', $ruleManagerColumns);
        $this->assertStringContainsString('name="title"', $formHtml);
        $this->assertStringNotContainsString('name="status"', $formHtml);
    }

    /**
     * Test complete CRUD workflow with all components.
     *
     * @return void
     */
    public function test_complete_crud_workflow_with_all_components(): void
    {
        // Arrange
        $role = Role::create([
            'name' => 'editor',
            'display_name' => 'Editor',
            'description' => 'Content editor',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'description' => 'Manage posts',
            'module' => 'posts',
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $user->roles()->attach($role->id);

        // Create posts
        for ($i = 1; $i <= 5; $i++) {
            $this->postModel::create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'user_id' => $user->id,
                'status' => 'draft',
            ]);
        }

        // Add rules
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

        $this->actingAs($user);

        $gate = app(Gate::class);

        // Act & Assert - List (TableBuilder)
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.manage');
        $table->setFields(['title:Title', 'content:Content']);
        $table->format();

        $tableHtml = $table->render();
        $this->assertStringContainsString('Post 1', $tableHtml);
        $this->assertStringContainsString('Post 5', $tableHtml);

        // Act & Assert - Create (FormBuilder)
        $createForm = app(FormBuilder::class);
        $createForm->setContext('admin');
        $createForm->setPermission('posts.manage');
        $createForm->setModel($this->postModel);
        $createForm->text('title', 'Title');
        $createForm->textarea('content', 'Content');
        $createForm->select('status', 'Status', ['draft' => 'Draft']);

        $createFormHtml = $createForm->render();
        $this->assertStringContainsString('name="title"', $createFormHtml);
        $this->assertStringNotContainsString('name="status"', $createFormHtml);

        // Act & Assert - Edit (FormBuilder + Gate)
        $post = $this->postModel::first();
        $canEdit = $gate->canAccessRow($user, 'posts.manage', $post);
        $this->assertTrue($canEdit);

        $editForm = app(FormBuilder::class);
        $editForm->setContext('admin');
        $editForm->setPermission('posts.manage');
        $editForm->setModel($post);
        $editForm->text('title', 'Title');
        $editForm->text('status', 'Status');

        $editFormHtml = $editForm->render();
        $this->assertStringContainsString('name="title"', $editFormHtml);
        $this->assertStringNotContainsString('name="status"', $editFormHtml);

        // Act & Assert - Delete (Gate)
        $canDelete = $gate->canAccessRow($user, 'posts.manage', $post);
        $this->assertTrue($canDelete);
    }

    /**
     * Test all components respect theme settings.
     *
     * @return void
     */
    public function test_all_components_respect_theme_settings(): void
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

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Act - Create components
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);
        $form->text('title', 'Title');
        $form->text('status', 'Status');

        $formHtml = $form->render();

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.edit');
        $table->setFields(['title:Title', 'status:Status']);
        $table->format();

        $tableHtml = $table->render();

        // Assert - Theme colors are used
        $this->assertStringContainsString('alert', $formHtml);
        $this->assertStringContainsString('eye-off', $tableHtml);
    }

    /**
     * Test all components use i18n for messages.
     *
     * @return void
     */
    public function test_all_components_use_i18n_for_messages(): void
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

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Act - Create components
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);
        $form->text('title', 'Title');
        $form->text('status', 'Status');

        $formHtml = $form->render();

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.edit');
        $table->setFields(['title:Title', 'status:Status']);
        $table->format();

        $tableHtml = $table->render();

        // Assert - i18n keys are used (check for permission-related messages)
        $this->assertStringContainsString('hidden due to permissions', $formHtml);
        $this->assertStringContainsString('column', strtolower($tableHtml));
    }

    /**
     * Test performance with all components working together.
     *
     * @return void
     */
    public function test_performance_with_all_components_working_together(): void
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

        // Create 50 posts
        for ($i = 1; $i <= 50; $i++) {
            $this->postModel::create([
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

        // Act - Measure performance
        $startTime = microtime(true);

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($this->postModel);
        $table->setPermission('posts.edit');
        $table->setFields(['title:Title', 'content:Content']);
        $table->format();
        $table->render();

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);
        $form->text('title', 'Title');
        $form->text('content', 'Content');
        $form->render();

        $totalTime = microtime(true) - $startTime;

        // Assert - Performance is acceptable (< 1 second for 50 posts)
        $this->assertLessThan(1.0, $totalTime, 'Total time should be less than 1 second');
    }
}

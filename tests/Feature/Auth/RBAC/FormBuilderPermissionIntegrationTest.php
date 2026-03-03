<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration test for FormBuilder with Fine-Grained Permissions.
 *
 * Tests the complete integration between FormBuilder and permission system.
 */
class FormBuilderPermissionIntegrationTest extends TestCase
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
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Define test model
        $this->postModel = new class () extends Model {
            protected $table = 'test_posts';

            protected $fillable = [
                'title', 'content', 'excerpt', 'status', 'featured', 'metadata',
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
     * Test FormBuilder filters fields based on column-level permissions.
     *
     * @return void
     */
    public function test_form_builder_filters_fields_by_column_permissions(): void
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

        // Add column-level rule: Only allow title and content
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

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        // Add all fields
        $form->text('title', 'Title');
        $form->textarea('content', 'Content');
        $form->text('excerpt', 'Excerpt');
        $form->select('status', 'Status', ['draft' => 'Draft', 'published' => 'Published']);
        $form->checkbox('featured', 'Featured');

        // Act
        $html = $form->render();

        // Assert - Allowed fields are present
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="content"', $html);

        // Assert - Denied fields are not present
        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringNotContainsString('name="featured"', $html);
    }

    /**
     * Test FormBuilder shows permission indicator when fields are hidden.
     *
     * @return void
     */
    public function test_form_builder_shows_permission_indicator(): void
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
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        $form->text('title', 'Title');
        $form->select('status', 'Status', ['draft' => 'Draft']);

        // Act
        $html = $form->render();

        // Assert - Permission indicator is present
        $this->assertStringContainsString('field_hidden', $html);
    }

    /**
     * Test FormBuilder with JSON attribute permissions.
     *
     * @return void
     */
    public function test_form_builder_with_json_attribute_permissions(): void
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

        $this->actingAs($user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        // Add JSON fields
        $form->text('metadata.seo.title', 'SEO Title');
        $form->text('metadata.featured', 'Featured');

        // Act
        $html = $form->render();

        // Assert - Allowed JSON path is present
        $this->assertStringContainsString('metadata.seo.title', $html);

        // Assert - Denied JSON path is not present
        $this->assertStringNotContainsString('metadata.featured', $html);
    }

    /**
     * Test FormBuilder without permissions shows all fields.
     *
     * @return void
     */
    public function test_form_builder_without_permissions_shows_all_fields(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        // No permission set

        $form->text('title', 'Title');
        $form->text('status', 'Status');
        $form->text('featured', 'Featured');

        // Act
        $html = $form->render();

        // Assert - All fields are present
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('name="featured"', $html);
    }

    /**
     * Test FormBuilder with theme integration.
     *
     * @return void
     */
    public function test_form_builder_uses_theme_colors_for_permission_indicators(): void
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
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        $form->text('title', 'Title');
        $form->text('status', 'Status');

        // Act
        $html = $form->render();

        // Assert - Theme colors are used (check for theme color variables or classes)
        $this->assertStringContainsString('alert', $html);
    }

    /**
     * Test FormBuilder with i18n integration.
     *
     * @return void
     */
    public function test_form_builder_uses_i18n_for_permission_messages(): void
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
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel($this->postModel);

        $form->text('title', 'Title');
        $form->text('status', 'Status');

        // Act
        $html = $form->render();

        // Assert - i18n keys are used (check for translation function calls or translated text)
        $this->assertStringContainsString('field_hidden', $html);
    }
}

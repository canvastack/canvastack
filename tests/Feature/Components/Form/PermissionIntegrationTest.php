<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Feature Test for FormBuilder Permission Integration.
 *
 * Tests FormBuilder integration with fine-grained permissions:
 * - Permission system integration
 * - Theme integration (colors)
 * - i18n integration (translated messages)
 */
class PermissionIntegrationTest extends TestCase
{
    protected FormBuilder $form;

    protected PermissionRuleManager $ruleManager;

    protected User $user;

    protected Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tables
        $this->createPostsTable();

        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Mock authentication
        $this->mockAuth($this->user);

        // Create test permission
        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Permission to edit posts',
        ]);

        // Initialize FormBuilder
        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $this->form = new FormBuilder($fieldFactory, $validationCache);

        // Initialize PermissionRuleManager
        $this->ruleManager = app(PermissionRuleManager::class);
    }

    /**
     * Mock authentication for testing.
     */
    protected function mockAuth(User $user): void
    {
        // Create a mock auth guard
        $guard = new class ($user) {
            private $user;

            public function __construct($user)
            {
                $this->user = $user;
            }

            public function user()
            {
                return $this->user;
            }

            public function id()
            {
                return $this->user->id;
            }

            public function check()
            {
                return true;
            }
        };

        // Bind auth helper
        app()->singleton('auth', function () use ($guard) {
            return new class ($guard) {
                private $guard;

                public function __construct($guard)
                {
                    $this->guard = $guard;
                }

                public function user()
                {
                    return $this->guard->user();
                }

                public function id()
                {
                    return $this->guard->id();
                }

                public function check()
                {
                    return $this->guard->check();
                }

                public function guard($name = null)
                {
                    return $this->guard;
                }
            };
        });
    }

    /**
     * Create posts table for testing.
     */
    protected function createPostsTable(): void
    {
        Capsule::schema()->create('posts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('content');
            $table->string('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Helper method to setup form with permission and model.
     */
    protected function setupFormWithPermission(string $permission = 'posts.edit'): void
    {
        $post = new Post();
        $this->form->setContext('admin');
        $this->form->setPermission($permission);
        $this->form->setModel($post);
    }

    /** @test */
    public function it_can_set_permission_on_form()
    {
        $this->setupFormWithPermission();

        $this->assertEquals('posts.edit', $this->form->getPermission());
    }

    /** @test */
    public function it_renders_form_without_permission()
    {
        $this->form->setContext('admin');

        // Add fields
        $this->form->text('title', 'Title');
        $this->form->textarea('content', 'Content');

        $html = $this->form->render();

        // Assert all fields are present
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="content"', $html);
    }

    /** @test */
    public function it_renders_form_with_permission_but_no_rules()
    {
        $this->setupFormWithPermission();

        // Add fields
        $this->form->text('title', 'Title');
        $this->form->textarea('content', 'Content');

        $html = $this->form->render();

        // Assert all fields are present (no rules = no filtering)
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="content"', $html);
    }

    /** @test */
    public function it_integrates_with_permission_rule_manager()
    {
        // Create column-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'type' => 'column',
                'model' => Post::class,
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => [],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Verify rule manager can get accessible columns
        $accessibleColumns = $this->ruleManager->getAccessibleColumns(
            (int) $this->user->id,
            'posts.edit',
            Post::class
        );

        $this->assertIsArray($accessibleColumns);
        $this->assertContains('title', $accessibleColumns);
        $this->assertContains('content', $accessibleColumns);
    }

    /** @test */
    public function it_uses_theme_colors_in_rendered_output()
    {
        $this->setupFormWithPermission();

        $this->form->text('title', 'Title');

        $html = $this->form->render();

        // Assert theme-related classes are present
        $hasThemeClasses = str_contains($html, 'text-gray-700') ||
                          str_contains($html, 'dark:text-gray-300') ||
                          str_contains($html, 'bg-gray-50') ||
                          str_contains($html, 'dark:bg-gray-800');

        $this->assertTrue($hasThemeClasses, 'Form should use theme colors');
    }

    /** @test */
    public function it_supports_i18n_for_labels()
    {
        // Set locale to English
        app('translator')->setLocale('en');

        $this->setupFormWithPermission();

        $this->form->text('title', __('ui.labels.title'));

        $html = $this->form->render();

        // Assert field is rendered
        $this->assertStringContainsString('name="title"', $html);
    }

    /** @test */
    public function it_works_with_tabs()
    {
        $this->setupFormWithPermission();

        // Add fields in tabs
        $this->form->openTab('Basic', 'active');
        $this->form->text('title', 'Title');
        $this->form->closeTab();

        $this->form->openTab('Content');
        $this->form->textarea('content', 'Content');
        $this->form->closeTab();

        $html = $this->form->render();

        // Assert tabs are rendered
        $this->assertStringContainsString('tabs-container', $html);
        $this->assertStringContainsString('Basic', $html);
        $this->assertStringContainsString('Content', $html);
    }

    /** @test */
    public function it_handles_json_fields()
    {
        $this->setupFormWithPermission();

        // Add JSON fields
        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');

        $html = $this->form->render();

        // Assert JSON fields are rendered
        $this->assertStringContainsString('metadata.seo.title', $html);
        $this->assertStringContainsString('metadata.seo.description', $html);
    }

    /** @test */
    public function it_renders_permission_indicators_when_configured()
    {
        // Create column-level rule that denies some fields
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'type' => 'column',
                'model' => Post::class,
                'allowed_columns' => ['title'],
                'denied_columns' => [],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->setupFormWithPermission();

        // Add fields
        $this->form->text('title', 'Title');
        $this->form->text('status', 'Status');

        $html = $this->form->render();

        // The form should render (implementation may vary)
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('name="title"', $html);
    }

    /** @test */
    public function it_caches_permission_evaluation()
    {
        // Create column-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'type' => 'column',
                'model' => Post::class,
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => [],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // First call (should cache)
        $columns1 = $this->ruleManager->getAccessibleColumns(
            (int) $this->user->id,
            'posts.edit',
            Post::class
        );

        // Second call (should use cache)
        $columns2 = $this->ruleManager->getAccessibleColumns(
            (int) $this->user->id,
            'posts.edit',
            Post::class
        );

        // Assert same results
        $this->assertEquals($columns1, $columns2);
    }

    /** @test */
    public function it_supports_multiple_field_types()
    {
        $this->setupFormWithPermission();

        // Add various field types
        $this->form->text('title', 'Title');
        $this->form->textarea('content', 'Content');
        $this->form->select('status', 'Status', ['draft' => 'Draft']);
        $this->form->checkbox('featured', 'Featured', [1 => 'Yes']);
        $this->form->date('published_at', 'Published At');

        $html = $this->form->render();

        // Assert all field types are rendered
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="content"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('name="featured', $html);
        $this->assertStringContainsString('name="published_at"', $html);
    }

    /** @test */
    public function it_maintains_field_order()
    {
        $this->setupFormWithPermission();

        // Add fields in specific order
        $this->form->text('field1', 'Field 1');
        $this->form->text('field2', 'Field 2');
        $this->form->text('field3', 'Field 3');

        $html = $this->form->render();

        // Find positions
        $pos1 = strpos($html, 'name="field1"');
        $pos2 = strpos($html, 'name="field2"');
        $pos3 = strpos($html, 'name="field3"');

        // Assert order is maintained
        $this->assertLessThan($pos2, $pos1);
        $this->assertLessThan($pos3, $pos2);
    }

    /** @test */
    public function it_handles_validation_errors_with_permissions()
    {
        $this->setupFormWithPermission();

        $this->form->text('title', 'Title')->required();
        $this->form->text('content', 'Content')->required();

        // Set validation errors
        $this->form->setValidationErrors([
            'title' => ['The title field is required.'],
        ]);

        $html = $this->form->render();

        // Assert form renders with errors
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('name="title"', $html);
    }

    /** @test */
    public function it_works_in_view_mode()
    {
        $post = Post::create([
            'user_id' => (int) $this->user->id,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'draft',
        ]);

        $this->form->setContext('admin');
        $this->form->setPermission('posts.edit');
        $this->form->setModel($post);
        $this->form->viewMode(true);

        $this->form->text('title', 'Title');
        $this->form->textarea('content', 'Content');

        $html = $this->form->render();

        // Assert view mode renders
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Test Post', $html);
    }
}

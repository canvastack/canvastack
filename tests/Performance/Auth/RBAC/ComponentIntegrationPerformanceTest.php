<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Performance tests for component integration with Fine-Grained Permissions.
 *
 * Requirements tested:
 * - FormBuilder with permissions: < 200ms
 * - TableBuilder with permissions: < 500ms
 * - Component rendering performance
 */
class ComponentIntegrationPerformanceTest extends TestCase
{
    private PermissionRuleManager $ruleManager;

    private User $user;

    private Permission $permission;

    /**
     * Auth guard mock.
     */
    protected static $authGuard = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock
        if (self::$authGuard === null) {
            self::$authGuard = new class () implements \Illuminate\Contracts\Auth\Guard {
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

                public function validate(array $credentials = [])
                {
                    return false;
                }

                public function hasUser()
                {
                    return $this->user !== null;
                }

                public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
                {
                    $this->user = $user;

                    return $this;
                }
            };
        }

        // Register auth guard - use singleton
        app()->singleton('auth', function () {
            return self::$authGuard;
        });

        $this->ruleManager = app(PermissionRuleManager::class);

        // Create test data
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password', // Plain text for performance testing
        ]);

        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        Cache::flush();
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
     * Test FormBuilder performance with column-level permissions.
     * Requirement: < 200ms.
     */
    public function test_form_builder_with_permissions_performance(): void
    {
        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            ['title', 'content', 'excerpt'],
            ['status', 'featured']
        );

        // Authenticate user
        $this->actingAs($this->user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel(new Post());

        // Add fields
        $form->text('title', 'Title');
        $form->textarea('content', 'Content');
        $form->text('excerpt', 'Excerpt');
        $form->select('status', 'Status', ['draft' => 'Draft', 'published' => 'Published']);
        $form->checkbox('featured', 'Featured');

        // Warm up
        $form->render();

        // Measure performance
        $iterations = 10;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $form->render();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            200,
            $avgTime,
            "FormBuilder with permissions took {$avgTime}ms (requirement: < 200ms)"
        );

        echo "\n✓ FormBuilder with permissions: {$avgTime}ms (requirement: < 200ms)\n";
    }

    /**
     * Test FormBuilder performance with large form.
     */
    public function test_form_builder_large_form_performance(): void
    {
        // Create column-level rule with many columns
        $allowedColumns = [];
        for ($i = 1; $i <= 50; $i++) {
            $allowedColumns[] = "field_{$i}";
        }

        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            $allowedColumns,
            []
        );

        // Authenticate user
        $this->actingAs($this->user);

        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel(new Post());

        // Add 50 fields
        for ($i = 1; $i <= 50; $i++) {
            $form->text("field_{$i}", "Field {$i}");
        }

        // Measure performance
        $startTime = microtime(true);
        $html = $form->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            200,
            $time,
            "Large form (50 fields) took {$time}ms (requirement: < 200ms)"
        );

        echo "✓ FormBuilder large form (50 fields): {$time}ms (requirement: < 200ms)\n";
    }

    /**
     * Test TableBuilder performance with row-level permissions.
     * Requirement: < 500ms for 100 rows.
     */
    public function test_table_builder_with_permissions_performance(): void
    {
        // Create 100 posts
        for ($i = 0; $i < 100; $i++) {
            Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'user_id' => $this->user->id,
                'status' => 'draft',
            ]);
        }

        // Create row-level rule
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        // Authenticate user
        $this->actingAs($this->user);

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setPermission('posts.edit');
        $table->setModel(new Post());
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);

        // Measure performance
        $startTime = microtime(true);
        $table->format();
        $html = $table->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            500,
            $time,
            "TableBuilder with 100 rows took {$time}ms (requirement: < 500ms)"
        );

        echo "✓ TableBuilder with permissions (100 rows): {$time}ms (requirement: < 500ms)\n";
    }

    /**
     * Test TableBuilder performance with column-level permissions.
     */
    public function test_table_builder_column_filtering_performance(): void
    {
        // Create 50 posts
        for ($i = 0; $i < 50; $i++) {
            Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'user_id' => $this->user->id,
                'status' => 'draft',
            ]);
        }

        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            ['title', 'content'],
            ['status', 'featured']
        );

        // Authenticate user
        $this->actingAs($this->user);

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setPermission('posts.edit');
        $table->setModel(new Post());
        $table->setFields([
            'title:Title',
            'content:Content',
            'status:Status',
            'featured:Featured',
        ]);

        // Measure performance
        $startTime = microtime(true);
        $table->format();
        $html = $table->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            500,
            $time,
            "TableBuilder column filtering took {$time}ms (requirement: < 500ms)"
        );

        echo "✓ TableBuilder column filtering (50 rows): {$time}ms (requirement: < 500ms)\n";
    }

    /**
     * Test Blade directive performance.
     */
    public function test_blade_directive_performance(): void
    {
        // Create row-level rule
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Simulate Blade directive calls
        $gate = app(\Canvastack\Canvastack\Auth\RBAC\Gate::class);

        // Warm up
        $gate->canAccessRow($this->user, 'posts.edit', $post);

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $gate->canAccessRow($this->user, 'posts.edit', $post);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            100,
            $avgTime,
            "Blade directive took {$avgTime}ms (requirement: < 100ms)"
        );

        echo "✓ Blade directive: {$avgTime}ms (requirement: < 100ms)\n";
    }

    /**
     * Test performance with multiple components on same page.
     */
    public function test_multiple_components_performance(): void
    {
        // Create rules
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            ['title', 'content'],
            ['status']
        );

        // Create posts
        for ($i = 0; $i < 20; $i++) {
            Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'user_id' => $this->user->id,
                'status' => 'draft',
            ]);
        }

        // Authenticate user
        $this->actingAs($this->user);

        // Create form
        $form = app(FormBuilder::class);
        $form->setContext('admin');
        $form->setPermission('posts.edit');
        $form->setModel(new Post());
        $form->text('title', 'Title');
        $form->textarea('content', 'Content');
        $form->select('status', 'Status', ['draft' => 'Draft']);

        // Create table
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setPermission('posts.edit');
        $table->setModel(new Post());
        $table->setFields(['title:Title', 'content:Content']);

        // Measure performance
        $startTime = microtime(true);
        $formHtml = $form->render();
        $table->format();
        $tableHtml = $table->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            700,
            $time,
            "Multiple components took {$time}ms (requirement: < 700ms)"
        );

        echo "✓ Multiple components (form + table): {$time}ms (requirement: < 700ms)\n";
    }
}

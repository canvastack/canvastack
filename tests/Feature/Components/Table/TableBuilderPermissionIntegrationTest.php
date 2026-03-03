<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature test for TableBuilder permission integration.
 *
 * Tests complete integration of TableBuilder with fine-grained permissions,
 * theme engine, and i18n system in a full application context.
 *
 * Covers Task 4.2.5 requirements:
 * - Test with various permission configurations
 * - Test theme and i18n integration
 */
class TableBuilderPermissionIntegrationTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.row_level.enabled' => true,
            'canvastack-rbac.fine_grained.column_level.enabled' => true,
            'canvastack-rbac.fine_grained.cache.enabled' => true,
        ]);

        // Bind RBAC services (required for permission filtering)
        $this->bindRbacServices();

        // Setup database
        $this->setupDatabase();
        $this->seedTestData();
    }

    /**
     * Bind RBAC services to container.
     *
     * This is required because PermissionRuleManager is not automatically
     * bound in the service provider. We need to manually bind it for tests.
     */
    protected function bindRbacServices(): void
    {
        // Bind RoleManager
        $this->app->singleton('canvastack.rbac.role.manager', function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\RoleManager();
        });

        // Bind PermissionManager
        $this->app->singleton('canvastack.rbac.permission.manager', function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\PermissionManager(
                $app->make('canvastack.rbac.role.manager')
            );
        });

        // Bind TemplateVariableResolver
        $this->app->singleton('canvastack.rbac.template.resolver', function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver();
        });

        // Bind PermissionRuleManager
        $this->app->singleton('canvastack.rbac.rule.manager', function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager(
                $app->make('canvastack.rbac.role.manager'),
                $app->make('canvastack.rbac.permission.manager'),
                $app->make('canvastack.rbac.template.resolver')
            );
        });
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Configure database to use SQLite in-memory
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load package translations into app namespace (not canvastack:: namespace)
        // This is required for trans_choice() to work properly in tests
        $langPath = __DIR__ . '/../../../resources/lang';
        $app['translator']->addNamespace('canvastack', $langPath);
        
        // Also load into default namespace for __() and trans_choice() helpers
        $loader = $app['translator']->getLoader();
        $loader->addNamespace('', $langPath);
        $loader->load('en', 'rbac', '');
        $loader->load('en', 'components', '');
    }

    protected function setupDatabase(): void
    {
        // Drop tables if they exist
        Schema::dropIfExists('permission_rules');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('users');

        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        // Create posts table
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        // Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create permission_rules table
        Schema::create('permission_rules', function (Blueprint $table) {
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

    protected function seedTestData(): void
    {
        // Create test users
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create test posts
        DB::table('posts')->insert([
            ['id' => 1, 'user_id' => 1, 'title' => 'Post 1', 'content' => 'Content 1', 'status' => 'draft', 'featured' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'user_id' => 1, 'title' => 'Post 2', 'content' => 'Content 2', 'status' => 'published', 'featured' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'user_id' => 2, 'title' => 'Post 3', 'content' => 'Content 3', 'status' => 'draft', 'featured' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'user_id' => 2, 'title' => 'Post 4', 'content' => 'Content 4', 'status' => 'published', 'featured' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create test permission
        DB::table('permissions')->insert([
            'id' => 1,
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'description' => 'View posts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function authenticateAs(int $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first();

        // Create a simple user model for authentication
        $userModel = new class ((array) $user) extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id', 'name', 'email'];

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->exists = true;
            }
        };

        // Use Laravel's actingAs method
        $this->actingAs($userModel);
    }

    /**
     * Test complete integration: row filtering + column filtering + indicators.
     */
    public function test_complete_permission_integration(): void
    {
        // Create row-level rule: users can only see their own posts
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create column-level rule: hide featured column
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'column',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'allowed_columns' => ['title', 'content', 'status'],
                'denied_columns' => ['featured'],
                'mode' => 'whitelist',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Authenticate as user 1
        $this->authenticateAs(1);

        // Create table with permission
        $table = $this->app->make(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel(new \Canvastack\Canvastack\Tests\Fixtures\Models\Post());
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert row filtering: only 2 posts from user 1
        $result = $table->getData();
        $this->assertCount(2, $result['data'], 'Should only see 2 posts from user 1');

        // Assert column filtering: featured column is hidden (we didn't request it, so it shouldn't appear)
        $this->assertStringNotContainsString('featured', strtolower($html));

        // Assert requested columns are present (use lowercase since labels might not be applied)
        $this->assertStringContainsString('title', strtolower($html));
        $this->assertStringContainsString('content', strtolower($html));
        $this->assertStringContainsString('status', strtolower($html));

        // Assert permission indicator is NOT shown (0 columns hidden, so no indicator needed)
        $this->assertStringNotContainsString('eye-off', $html, 'Permission indicator should not be shown when no columns are hidden');
    }

    /**
     * Test theme integration with permission indicators.
     */
    public function test_theme_integration_with_permission_indicators(): void
    {
        // Create column-level rule
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'column',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'allowed_columns' => ['title'],
                'denied_columns' => ['content', 'status', 'featured'],
                'mode' => 'whitelist',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Authenticate as user 1
        $this->authenticateAs(1);

        // Create table with permission
        $table = $this->app->make(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel(new \Canvastack\Canvastack\Tests\Fixtures\Models\Post());
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert theme colors are used in permission indicator
        $this->assertMatchesRegularExpression('/background:\s*#[0-9a-fA-F]{6}/', $html, 'Should have background color');
        $this->assertMatchesRegularExpression('/color:\s*#[0-9a-fA-F]{6}/', $html, 'Should have text color');

        // Assert permission indicator is shown
        $this->assertStringContainsString('eye-off', $html);
        $this->assertStringContainsString('2 columns are hidden', $html); // content and status are hidden
    }

    /**
     * Test i18n integration with permission messages.
     */
    public function test_i18n_integration_with_permission_messages(): void
    {
        // Create column-level rule
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'column',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status', 'featured'],
                'mode' => 'whitelist',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Authenticate as user 1
        $this->authenticateAs(1);

        // Test with English locale
        App::setLocale('en');

        $table = $this->app->make(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel(new \Canvastack\Canvastack\Tests\Fixtures\Models\Post());
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        $html = $table->render();

        // Assert English message (fallback is used in test environment)
        // The fallback correctly shows "1 column is hidden" (singular)
        $this->assertStringContainsString('1 column is hidden due to permissions', $html);
    }

    /**
     * Test permission integration in public context.
     */
    public function test_permission_integration_in_public_context(): void
    {
        // Create row-level rule
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'row',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'conditions' => [
                    'status' => 'published',
                ],
                'operator' => 'AND',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create column-level rule
        DB::table('permission_rules')->insert([
            'permission_id' => 1,
            'rule_type' => 'column',
            'rule_config' => json_encode([
                'model' => 'Canvastack\\Canvastack\\Tests\\Fixtures\\Models\\Post',
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status', 'featured'],
                'mode' => 'whitelist',
            ]),
            'priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Authenticate as user 1
        $this->authenticateAs(1);

        // Create table with PUBLIC context
        $table = $this->app->make(TableBuilder::class);
        $table->setContext('public');
        $table->setModel(new \Canvastack\Canvastack\Tests\Fixtures\Models\Post());
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert row filtering: only published posts
        $result = $table->getData();
        $this->assertCount(2, $result['data']);

        // Assert column filtering works in public context
        $this->assertStringNotContainsString('Status', $html);
        $this->assertStringNotContainsString('Featured', $html);

        // Assert permission indicator is shown in public context
        $this->assertStringContainsString('eye-off', $html);
        $this->assertStringContainsString('1 column is hidden', $html); // status is hidden
    }

    protected function tearDown(): void
    {
        // Drop test tables
        Schema::dropIfExists('permission_rules');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('users');

        // Clear cache
        if ($this->app->bound('cache')) {
            $this->app['cache']->flush();
        }

        parent::tearDown();
    }
}

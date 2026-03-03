<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Mockery;

/**
 * Test for TableBuilder column filtering based on permissions.
 *
 * Requirements: 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8
 */
class TableBuilderColumnFilteringTest extends TestCase
{
    protected TableBuilder $table;

    protected $authMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
        $this->table->setContext('admin');

        // Create posts table for testing
        $this->createPostsTable();
    }

    /**
     * Create posts table for testing.
     */
    protected function createPostsTable(): void
    {
        if (!Capsule::schema()->hasTable('posts')) {
            Capsule::schema()->create('posts', function ($table) {
                $table->id();
                $table->string('title');
                $table->text('content');
                $table->string('status');
                $table->boolean('featured')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Test that columns are not filtered when permission is not set.
     *
     * Requirement 8.2: Only filter when permission is set
     */
    public function test_columns_not_filtered_when_permission_not_set(): void
    {
        $this->table->setModel(new Post());
        $this->table->setFields(['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();

        $this->assertCount(3, $columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
        $this->assertContains('status', $columns);
    }

    /**
     * Test that columns are not filtered when user is not authenticated.
     *
     * Requirement 8.2: Only filter when user is authenticated
     */
    public function test_columns_not_filtered_when_user_not_authenticated(): void
    {
        // In test environment, auth()->user() will return null by default

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();

        $this->assertCount(3, $columns);
    }

    /**
     * Test that columns are not filtered when model is not set.
     *
     * Requirement 8.2: Only filter when model is set
     */
    public function test_columns_not_filtered_when_model_not_set(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        $this->table->setPermission('posts.view');
        // Don't set model - this should cause early return

        // Manually set fields without model (skip validation)
        $reflection = new \ReflectionClass($this->table);
        $property = $reflection->getProperty('columns');
        $property->setAccessible(true);
        $property->setValue($this->table, ['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();

        $this->assertCount(3, $columns);
    }

    /**
     * Test whitelist mode filters columns correctly.
     *
     * Requirement 8.3: Filter columns based on whitelist
     */
    public function test_whitelist_mode_filters_columns(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->with(1, 'posts.view', Post::class)
            ->once()
            ->andReturn(['title', 'content']); // Only title and content allowed

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status', 'featured']);

        // Verify columns before filtering
        $columnsBefore = $this->table->getColumns();
        $this->assertCount(4, $columnsBefore, 'Should have 4 columns before filtering');

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();
        $hiddenColumns = $this->table->getPermissionHiddenColumns();

        // Should only have title and content
        $this->assertCount(2, $columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
        $this->assertNotContains('status', $columns);
        $this->assertNotContains('featured', $columns);

        // Should track hidden columns
        $this->assertCount(2, $hiddenColumns);
        $this->assertArrayHasKey('status', $hiddenColumns);
        $this->assertArrayHasKey('featured', $hiddenColumns);
        $this->assertEquals('column_level_denied', $hiddenColumns['status']['reason']);
    }

    /**
     * Test blacklist mode filters columns correctly.
     *
     * Requirement 8.3: Filter columns based on blacklist
     */
    public function test_blacklist_mode_filters_columns(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        // Mock PermissionRuleManager - blacklist mode returns columns with ! prefix
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->with(1, 'posts.view', Post::class)
            ->andReturn(['!status', '!featured']); // Deny status and featured

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status', 'featured']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();
        $hiddenColumns = $this->table->getPermissionHiddenColumns();

        // Should have title and content (status and featured denied)
        $this->assertCount(2, $columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
        $this->assertNotContains('status', $columns);
        $this->assertNotContains('featured', $columns);

        // Should track hidden columns
        $this->assertCount(2, $hiddenColumns);
        $this->assertArrayHasKey('status', $hiddenColumns);
        $this->assertArrayHasKey('featured', $hiddenColumns);
    }

    /**
     * Test default deny behavior when no rules defined.
     *
     * Requirement 8.3: Handle default deny configuration
     */
    public function test_default_deny_hides_all_columns(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        // Mock PermissionRuleManager - empty array with default deny
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->with(1, 'posts.view', Post::class)
            ->andReturn([]); // No accessible columns

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set config to default deny
        config(['canvastack-rbac.fine_grained.column_level.default_deny' => true]);

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();
        $hiddenColumns = $this->table->getPermissionHiddenColumns();

        // Should have no columns
        $this->assertCount(0, $columns);

        // Should track all columns as hidden
        $this->assertCount(3, $hiddenColumns);
        $this->assertArrayHasKey('title', $hiddenColumns);
        $this->assertArrayHasKey('content', $hiddenColumns);
        $this->assertArrayHasKey('status', $hiddenColumns);
    }

    /**
     * Test default allow behavior when no rules defined.
     *
     * Requirement 8.3: Handle default allow configuration
     */
    public function test_default_allow_keeps_all_columns(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        // Mock PermissionRuleManager - empty array with default allow
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->with(1, 'posts.view', Post::class)
            ->andReturn([]); // No accessible columns

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set config to default allow
        config(['canvastack-rbac.fine_grained.column_level.default_deny' => false]);

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $columns = $this->table->getColumns();
        $hiddenColumns = $this->table->getPermissionHiddenColumns();

        // Should keep all columns
        $this->assertCount(3, $columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
        $this->assertContains('status', $columns);

        // Should have no hidden columns
        $this->assertCount(0, $hiddenColumns);
    }

    /**
     * Test getPermissionHiddenColumns returns correct data.
     *
     * Requirement 8.4: Track hidden columns for indicators
     */
    public function test_get_permission_hidden_columns(): void
    {
        // Mock authenticated user
        $user = Mockery::mock();
        $user->id = 1;
        $this->mockAuth($user);

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->with(1, 'posts.view', Post::class)
            ->andReturn(['title']);

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        $this->table->setModel(new Post());
        $this->table->setPermission('posts.view');
        $this->table->setFields(['title', 'content', 'status']);

        // Call filterColumnsByPermission via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('filterColumnsByPermission');
        $method->setAccessible(true);
        $method->invoke($this->table);

        $hiddenColumns = $this->table->getPermissionHiddenColumns();

        $this->assertIsArray($hiddenColumns);
        $this->assertCount(2, $hiddenColumns);
        $this->assertArrayHasKey('content', $hiddenColumns);
        $this->assertArrayHasKey('status', $hiddenColumns);
        $this->assertEquals('content', $hiddenColumns['content']['column']);
        $this->assertEquals('column_level_denied', $hiddenColumns['content']['reason']);
    }

    /**
     * Mock auth helper for testing.
     */
    protected function mockAuth($user): void
    {
        // Create a simple auth mock that implements the required interface
        $this->authMock = new class ($user) implements \Illuminate\Contracts\Auth\Factory {
            protected $user;

            public function __construct($user)
            {
                $this->user = $user;
            }

            public function user()
            {
                return $this->user;
            }

            public function guard($name = null)
            {
                return $this;
            }

            // Required by Factory interface
            public function shouldUse($name)
            {
                // Not needed for tests
            }

            public function setDefaultDriver($name)
            {
                // Not needed for tests
            }

            public function userResolver()
            {
                return fn () => $this->user;
            }

            public function resolveUsersUsing(\Closure $userResolver)
            {
                // Not needed for tests
            }

            public function extend($driver, \Closure $callback)
            {
                // Not needed for tests
            }

            public function provider($name, \Closure $callback)
            {
                // Not needed for tests
            }

            public function hasResolvedGuards()
            {
                return false;
            }

            public function forgetGuards()
            {
                // Not needed for tests
            }
        };

        // Bind to container so auth() helper returns our mock
        app()->instance('auth', $this->authMock);

        // Also bind the Auth Factory contract
        app()->instance(\Illuminate\Contracts\Auth\Factory::class, $this->authMock);
    }

    protected function tearDown(): void
    {
        // Clean up auth mock
        if ($this->authMock) {
            app()->forgetInstance('auth');
            $this->authMock = null;
        }

        Mockery::close();
        parent::tearDown();
    }
}

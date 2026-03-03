<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Test for TableBuilder permission indicators.
 *
 * Tests that permission indicators are displayed when columns are hidden
 * due to permission rules, using theme colors and i18n messages.
 */
class TableBuilderPermissionIndicatorTest extends TestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.column_level.enabled' => true,
        ]);

        // Force fresh TableBuilder instance
        if (app()->bound(TableBuilder::class)) {
            app()->forgetInstance(TableBuilder::class);
        }

        $this->table = app(TableBuilder::class);

        // Create test table
        Capsule::schema()->dropIfExists('test_posts');
        Capsule::schema()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status');
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        // Insert test data
        Capsule::table('test_posts')->insert([
            [
                'title' => 'Post 1',
                'content' => 'Content 1',
                'status' => 'published',
                'featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Post 2',
                'content' => 'Content 2',
                'status' => 'draft',
                'featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Capsule::schema()->dropIfExists('test_posts');
        parent::tearDown();
    }

    /**
     * Test that permission indicator is shown when columns are hidden.
     */
    public function test_permission_indicator_shown_when_columns_hidden(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;

            public function scopeByPermission($query)
            {
                return $query;
            }
        };

        // Mock PermissionRuleManager to return limited columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.view', get_class($model))
            ->andReturn(['title', 'content']); // Only title and content allowed
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status', 'featured:Featured']);
        $table->format();

        // Render table - this should trigger filterColumnsByPermission
        $html = $table->render();

        // Assert permission indicator is shown
        $this->assertStringContainsString('eye-off', $html, 'Permission indicator icon should be present');
        $this->assertStringContainsString('2 columns are hidden', $html, 'Permission indicator message should show correct count');
    }

    /**
     * Test that permission indicator is NOT shown when no columns are hidden.
     */
    public function test_permission_indicator_not_shown_when_no_columns_hidden(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;
        };

        // Mock PermissionRuleManager to return all columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->andReturn(['title', 'content', 'status', 'featured']); // All columns allowed
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status', 'featured:Featured']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert permission indicator is NOT shown
        $this->assertStringNotContainsString('eye-off', $html);
        $this->assertStringNotContainsString('columns are hidden', $html);
    }

    /**
     * Test that permission indicator is NOT shown when no permission is set.
     */
    public function test_permission_indicator_not_shown_when_no_permission_set(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;
        };

        // Create table WITHOUT permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setFields(['title:Title', 'content:Content', 'status:Status', 'featured:Featured']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert permission indicator is NOT shown
        $this->assertStringNotContainsString('eye-off', $html);
        $this->assertStringNotContainsString('columns are hidden', $html);
    }

    /**
     * Test that permission indicator uses theme colors.
     */
    public function test_permission_indicator_uses_theme_colors(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;

            public function scopeByPermission($query)
            {
                return $query;
            }
        };

        // Mock PermissionRuleManager to return limited columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.view', get_class($model))
            ->andReturn(['title']); // Only title allowed
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert theme colors are used (either from theme_color() or fallback)
        $this->assertMatchesRegularExpression('/background:\s*#[0-9a-fA-F]{6}/', $html, 'Should have background color');
        $this->assertMatchesRegularExpression('/color:\s*#[0-9a-fA-F]{6}/', $html, 'Should have text color');
        $this->assertMatchesRegularExpression('/border:\s*1px solid #[0-9a-fA-F]{6}/', $html, 'Should have border color');
    }

    /**
     * Test that permission indicator uses i18n for messages.
     */
    public function test_permission_indicator_uses_i18n(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;

            public function scopeByPermission($query)
            {
                return $query;
            }
        };

        // Mock PermissionRuleManager to return limited columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.view', get_class($model))
            ->andReturn(['title']); // Only title allowed
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert i18n message is used - should contain "2 columns are hidden"
        $this->assertStringContainsString('2 columns are hidden', $html, 'Should show i18n message with correct count');
    }

    /**
     * Test that permission indicator shows correct count.
     */
    public function test_permission_indicator_shows_correct_count(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;

            public function scopeByPermission($query)
            {
                return $query;
            }
        };

        // Mock PermissionRuleManager to return limited columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.view', get_class($model))
            ->andReturn(['title']); // Only title allowed, 3 columns hidden
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status', 'featured:Featured']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert count is 3 (content, status, featured are hidden)
        $this->assertStringContainsString('3 columns are hidden', $html, 'Should show correct count of hidden columns');
    }

    /**
     * Test that permission indicator works in public context.
     */
    public function test_permission_indicator_works_in_public_context(): void
    {
        // Create test model
        $model = new class () extends Model {
            protected $table = 'test_posts';

            public $timestamps = true;

            public function scopeByPermission($query)
            {
                return $query;
            }
        };

        // Mock PermissionRuleManager to return limited columns
        $ruleManager = \Mockery::mock('stdClass');
        $ruleManager->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.view', get_class($model))
            ->andReturn(['title']); // Only title allowed
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Create table with PUBLIC context
        $table = app(TableBuilder::class);
        $table->setContext('public');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status']);
        $table->format();

        // Render table
        $html = $table->render();

        // Assert permission indicator is shown
        $this->assertStringContainsString('eye-off', $html, 'Permission indicator icon should be present in public context');
        $this->assertStringContainsString('2 columns are hidden', $html, 'Permission indicator message should show correct count in public context');
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Debug test for TableBuilder permission indicators.
 */
class TableBuilderPermissionIndicatorDebugTest extends TestCase
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
        ]);
    }

    protected function tearDown(): void
    {
        Capsule::schema()->dropIfExists('test_posts');
        parent::tearDown();
    }

    /**
     * Debug test to understand permission filtering flow.
     */
    public function test_debug_permission_filtering(): void
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
            ->andReturnUsing(function ($userId, $permission, $modelClass) {
                echo "\n\nMOCK CALLED: getAccessibleColumns($userId, $permission, $modelClass)\n";
                echo "Returning: ['title', 'content']\n\n";

                return ['title', 'content'];
            });
        $ruleManager->shouldReceive('scopeByPermission')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        app()->instance('canvastack.rbac.rule.manager', $ruleManager);

        // Set auth user using global auth service
        $user = new \stdClass();
        $user->id = 1;
        app('auth')->setUser($user);

        // Verify auth is working
        echo "\n\nAuth check:\n";
        echo 'auth()->check(): ' . (auth()->check() ? 'true' : 'false') . "\n";
        echo 'auth()->user(): ' . (auth()->user() ? 'object' : 'null') . "\n";
        echo 'auth()->id(): ' . (auth()->id() ?? 'null') . "\n\n";

        // Create table with permission
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setModel($model);
        $table->setPermission('posts.view');
        $table->setFields(['title:Title', 'content:Content', 'status:Status', 'featured:Featured']);
        $table->format();

        // Get columns before render
        $reflection = new \ReflectionClass($table);
        $columnsProperty = $reflection->getProperty('columns');
        $columnsProperty->setAccessible(true);
        $columnsBefore = $columnsProperty->getValue($table);

        echo "\n\nColumns before render: " . json_encode($columnsBefore) . "\n";

        // Render table - this should trigger filterColumnsByPermission
        $html = $table->render();

        // Get columns after render
        $columnsAfter = $columnsProperty->getValue($table);
        echo 'Columns after render: ' . json_encode($columnsAfter) . "\n";

        // Get permissionHiddenColumns
        $permissionHiddenColumnsProperty = $reflection->getProperty('permissionHiddenColumns');
        $permissionHiddenColumnsProperty->setAccessible(true);
        $permissionHiddenColumns = $permissionHiddenColumnsProperty->getValue($table);

        echo 'Permission hidden columns: ' . json_encode($permissionHiddenColumns) . "\n";
        echo 'Permission hidden columns count: ' . count($permissionHiddenColumns) . "\n\n";

        // Check if permission indicator is in HTML
        $hasIndicator = str_contains($html, 'eye-off');
        echo 'Has permission indicator: ' . ($hasIndicator ? 'YES' : 'NO') . "\n\n";

        // This test always passes - it's just for debugging
        $this->assertTrue(true);
    }
}

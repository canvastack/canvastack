<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Test for PermissionRuleManager cache monitoring and logging.
 */
class PermissionRuleManagerCacheMonitoringTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected TemplateVariableResolver $templateResolver;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Enable cache for testing BEFORE creating manager
        config(['canvastack-rbac.cache.enabled' => true]);
        config(['canvastack-rbac.fine_grained.enabled' => true]);
        config(['canvastack-rbac.fine_grained.row_level.enabled' => true]);
        config(['canvastack-rbac.fine_grained.column_level.enabled' => true]);
        config(['canvastack-rbac.fine_grained.json_attribute.enabled' => true]);
        config(['canvastack-rbac.fine_grained.cache.ttl.row' => 3600]);
        config(['canvastack-rbac.fine_grained.cache.ttl.column' => 3600]);
        config(['canvastack-rbac.fine_grained.cache.ttl.json_attribute' => 3600]);
        config(['canvastack-rbac.fine_grained.cache.key_prefix' => 'canvastack:rbac:rules:']);

        // Clear cache before each test
        Cache::flush();

        // Create dependencies
        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager($this->roleManager);
        $this->templateResolver = new TemplateVariableResolver();

        // Create manager instance
        $this->manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        // Reset statistics AFTER creating manager and flushing cache
        $this->manager->resetCacheStatistics();
    }

    /**
     * Test that cache statistics are initialized correctly.
     */
    public function test_cache_statistics_initialized(): void
    {
        $stats = $this->manager->getCacheStatistics();

        $this->assertTrue($stats['enabled']);
        $this->assertEquals(0, $stats['total_hits']);
        $this->assertEquals(0, $stats['total_misses']);
        $this->assertEquals(0, $stats['total_operations']);
        $this->assertEquals(0.0, $stats['hit_rate']);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('row', $stats['by_type']);
        $this->assertArrayHasKey('column', $stats['by_type']);
        $this->assertArrayHasKey('json_attribute', $stats['by_type']);
        $this->assertArrayHasKey('conditional', $stats['by_type']);
    }

    /**
     * Test that cache statistics return disabled message when cache is disabled.
     */
    public function test_cache_statistics_when_disabled(): void
    {
        // Disable cache and create new manager
        config(['canvastack-rbac.cache.enabled' => false]);

        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        $stats = $manager->getCacheStatistics();

        $this->assertFalse($stats['enabled']);
        $this->assertEquals('Cache is disabled', $stats['message']);
    }

    /**
     * Test that cache hit is logged correctly.
     */
    public function test_cache_hit_logged(): void
    {
        // Enable debug mode to see log messages
        config(['app.debug' => true]);

        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $rule = $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        dump('Rule created:', $rule->toArray());

        // Verify rule exists
        $rules = PermissionRule::where('permission_id', $permission->id)->get();
        dump('Rules count:', $rules->count());
        dump('Rules:', $rules->toArray());

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Check if cache is enabled
        dump('Cache enabled:', config('canvastack-rbac.cache.enabled'));
        dump('Fine-grained enabled:', config('canvastack-rbac.fine_grained.enabled'));
        dump('Row level enabled:', config('canvastack-rbac.fine_grained.row_level.enabled'));

        // First call - cache miss
        dump('=== First call ===');
        $result1 = $this->manager->canAccessRow(1, 'posts.edit', $model);
        dump('Result 1:', $result1);

        // Debug: Check stats after first call
        $statsAfterFirst = $this->manager->getCacheStatistics();
        dump('After first call:', $statsAfterFirst);

        // Second call - cache hit
        dump('=== Second call ===');
        $result2 = $this->manager->canAccessRow(1, 'posts.edit', $model);
        dump('Result 2:', $result2);

        // Debug: Check stats after second call
        $statsAfterSecond = $this->manager->getCacheStatistics();
        dump('After second call:', $statsAfterSecond);

        // Get statistics
        $stats = $this->manager->getCacheStatistics();

        // Should have 1 hit and 1 miss
        $this->assertEquals(1, $stats['total_hits']);
        $this->assertEquals(1, $stats['total_misses']);
        $this->assertEquals(2, $stats['total_operations']);
        $this->assertEquals(50.0, $stats['hit_rate']); // 1/2 = 50%
    }

    /**
     * Test that cache miss is logged correctly.
     */
    public function test_cache_miss_logged(): void
    {
        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // First call - cache miss
        $result = $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Get statistics
        $stats = $this->manager->getCacheStatistics();

        // Should have 0 hits and 1 miss
        $this->assertEquals(0, $stats['total_hits']);
        $this->assertEquals(1, $stats['total_misses']);
        $this->assertEquals(1, $stats['total_operations']);
        $this->assertEquals(0.0, $stats['hit_rate']); // 0/1 = 0%
    }

    /**
     * Test that cache statistics are tracked per rule type.
     */
    public function test_cache_statistics_by_type(): void
    {
        // Create a permission
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        // Add row-level rule
        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Add column-level rule
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'],
            []
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Test row-level access (cache miss)
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Test row-level access again (cache hit)
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Test column-level access (cache miss)
        $this->manager->canAccessColumn(1, 'posts.edit', $model, 'title');

        // Get statistics by type
        $rowStats = $this->manager->getCacheStatisticsByType('row');
        $columnStats = $this->manager->getCacheStatisticsByType('column');

        // Row stats: 1 hit, 1 miss
        $this->assertEquals(1, $rowStats['hits']);
        $this->assertEquals(1, $rowStats['misses']);
        $this->assertEquals(2, $rowStats['operations']);
        $this->assertEquals(50.0, $rowStats['hit_rate']);

        // Column stats: 0 hits, 1 miss
        $this->assertEquals(0, $columnStats['hits']);
        $this->assertEquals(1, $columnStats['misses']);
        $this->assertEquals(1, $columnStats['operations']);
        $this->assertEquals(0.0, $columnStats['hit_rate']);
    }

    /**
     * Test that cache hit rate is calculated correctly.
     */
    public function test_cache_hit_rate_calculation(): void
    {
        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // First call - cache miss
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Next 4 calls - cache hits
        for ($i = 0; $i < 4; $i++) {
            $this->manager->canAccessRow(1, 'posts.edit', $model);
        }

        // Get hit rate
        $hitRate = $this->manager->getCacheHitRate();

        // Should be 80% (4 hits out of 5 operations)
        $this->assertEquals(80.0, $hitRate);
    }

    /**
     * Test that cache statistics can be reset.
     */
    public function test_cache_statistics_reset(): void
    {
        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Generate some cache operations
        $this->manager->canAccessRow(1, 'posts.edit', $model);
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Verify statistics exist
        $statsBefore = $this->manager->getCacheStatistics();
        $this->assertGreaterThan(0, $statsBefore['total_operations']);

        // Reset statistics
        $result = $this->manager->resetCacheStatistics();
        $this->assertTrue($result);

        // Verify statistics are reset
        $statsAfter = $this->manager->getCacheStatistics();
        $this->assertEquals(0, $statsAfter['total_hits']);
        $this->assertEquals(0, $statsAfter['total_misses']);
        $this->assertEquals(0, $statsAfter['total_operations']);
        $this->assertEquals(0.0, $statsAfter['hit_rate']);
    }

    /**
     * Test that cache performance logging works.
     */
    public function test_cache_performance_logging(): void
    {
        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Generate some cache operations
        $this->manager->canAccessRow(1, 'posts.edit', $model);
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Mock Log facade to capture log calls with proper assertions
        Log::shouldReceive('info')
            ->once()
            ->with('Permission rule cache performance', \Mockery::on(function ($data) {
                // Verify required keys exist
                $this->assertArrayHasKey('total_hits', $data);
                $this->assertArrayHasKey('total_misses', $data);
                $this->assertArrayHasKey('total_operations', $data);
                $this->assertArrayHasKey('hit_rate', $data);
                $this->assertArrayHasKey('by_type', $data);
                $this->assertArrayHasKey('last_reset', $data);

                // Verify values are correct
                $this->assertEquals(1, $data['total_hits']);
                $this->assertEquals(1, $data['total_misses']);
                $this->assertEquals(2, $data['total_operations']);
                $this->assertEquals('50%', $data['hit_rate']);

                // Verify by_type structure
                $this->assertIsArray($data['by_type']);
                $this->assertArrayHasKey('row', $data['by_type']);
                $this->assertArrayHasKey('column', $data['by_type']);
                $this->assertArrayHasKey('json_attribute', $data['by_type']);
                $this->assertArrayHasKey('conditional', $data['by_type']);

                // Verify row type stats
                $this->assertEquals(1, $data['by_type']['row']['hits']);
                $this->assertEquals(1, $data['by_type']['row']['misses']);
                $this->assertEquals(2, $data['by_type']['row']['operations']);
                $this->assertEquals('50%', $data['by_type']['row']['hit_rate']);

                return true;
            }));

        // Log performance
        $this->manager->logCachePerformance();
    }

    /**
     * Test that cache size estimate is returned.
     */
    public function test_cache_size_estimate(): void
    {
        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $this->manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Generate some cache operations
        $this->manager->canAccessRow(1, 'posts.edit', $model);
        $this->manager->canAccessRow(1, 'posts.edit', $model);

        // Get cache size
        $size = $this->manager->getCacheSize();

        // Should be equal to total operations (2)
        $this->assertEquals(2, $size);
    }

    /**
     * Test that cache statistics work with JSON attribute rules.
     */
    public function test_cache_statistics_with_json_attribute_rules(): void
    {
        // Create a permission
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        // Add JSON attribute rule
        $this->manager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['seo.*'],
            []
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->metadata = ['seo' => ['title' => 'Test']];

        // Test JSON attribute access (cache miss)
        $this->manager->canAccessJsonAttribute(1, 'posts.edit', $model, 'metadata', 'seo.title');

        // Test JSON attribute access again (cache hit)
        $this->manager->canAccessJsonAttribute(1, 'posts.edit', $model, 'metadata', 'seo.title');

        // Get statistics by type
        $jsonStats = $this->manager->getCacheStatisticsByType('json_attribute');

        // Should have 1 hit and 1 miss
        $this->assertEquals(1, $jsonStats['hits']);
        $this->assertEquals(1, $jsonStats['misses']);
        $this->assertEquals(2, $jsonStats['operations']);
        $this->assertEquals(50.0, $jsonStats['hit_rate']);
    }

    /**
     * Test that cache statistics handle zero operations gracefully.
     */
    public function test_cache_statistics_with_zero_operations(): void
    {
        $stats = $this->manager->getCacheStatistics();

        // With zero operations, hit rate should be 0
        $this->assertEquals(0.0, $stats['hit_rate']);

        // Get statistics by type
        $rowStats = $this->manager->getCacheStatisticsByType('row');

        // Should also have 0 hit rate
        $this->assertEquals(0.0, $rowStats['hit_rate']);
    }

    /**
     * Test that cache statistics work when cache is disabled.
     */
    public function test_cache_operations_when_cache_disabled(): void
    {
        // Disable cache and create new manager
        config(['canvastack-rbac.cache.enabled' => false]);

        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        // Create a permission and rule
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
            'module' => 'posts',
        ]);

        $manager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 1],
            'AND'
        );

        // Create a dummy model
        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 1;

        // Test row-level access
        $manager->canAccessRow(1, 'posts.edit', $model);

        // Get statistics
        $stats = $manager->getCacheStatistics();

        // Should indicate cache is disabled
        $this->assertFalse($stats['enabled']);
        $this->assertEquals('Cache is disabled', $stats['message']);
    }

    /**
     * Test that cache size returns 0 when cache is disabled.
     */
    public function test_cache_size_when_disabled(): void
    {
        // Disable cache and create new manager
        config(['canvastack-rbac.cache.enabled' => false]);

        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        $size = $manager->getCacheSize();

        $this->assertEquals(0, $size);
    }

    /**
     * Test that reset cache statistics returns true when cache is disabled.
     */
    public function test_reset_cache_statistics_when_disabled(): void
    {
        // Disable cache and create new manager
        config(['canvastack-rbac.cache.enabled' => false]);

        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        $result = $manager->resetCacheStatistics();

        $this->assertTrue($result);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}

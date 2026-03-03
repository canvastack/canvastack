<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Performance tests for PermissionRuleManager.
 *
 * Tests verify that all operations meet the performance requirements:
 * - Row-level checks: < 50ms
 * - Column-level checks: < 10ms
 * - JSON attribute checks: < 15ms
 * - Conditional checks: < 30ms
 * - Cache hit rate: > 80%
 */
class PermissionRuleManagerPerformanceTest extends TestCase
{
    private PermissionRuleManager $manager;

    private Permission $permission;

    private User $user;

    private Model $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data first
        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password', // Plain password for testing
        ]);

        // Ensure user has an ID
        $this->user->id = 1;
        $this->user->save();

        // Create mock dependencies with expectations
        $roleManager = \Mockery::mock(RoleManager::class);

        $permissionManager = \Mockery::mock(PermissionManager::class);
        $permissionManager->shouldReceive('findByName')
            ->with('posts.edit')
            ->andReturn($this->permission);

        // Create template resolver with mocked auth
        $templateResolver = \Mockery::mock(TemplateVariableResolver::class);
        $templateResolver->shouldReceive('resolve')
            ->with('{{auth.id}}')
            ->andReturn(1);
        $templateResolver->shouldReceive('resolveConditions')
            ->andReturn(['user_id' => 1]);

        $this->manager = new PermissionRuleManager(
            $roleManager,
            $permissionManager,
            $templateResolver
        );

        $this->testModel = new class () extends Model {
            protected $fillable = ['user_id', 'status', 'metadata'];

            protected $casts = ['metadata' => 'array'];

            public $user_id = 1;

            public $status = 'draft';

            public $metadata = ['seo' => ['title' => 'Test']];
        };
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Test row-level permission check performance.
     *
     * Requirement: Row-level checks SHALL complete within 50ms per check
     */
    public function test_row_level_check_performance(): void
    {
        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Warm up (first call will be slower due to cache miss)
        $this->manager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $this->testModel
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000; // Convert to ms

        // Assert performance requirement
        $this->assertLessThan(
            50,
            $averageTime,
            "Row-level check took {$averageTime}ms (should be < 50ms)"
        );

        // Log performance
        echo "\nRow-level check average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test column-level permission check performance.
     *
     * Requirement: Column-level checks SHALL complete within 10ms per check
     */
    public function test_column_level_check_performance(): void
    {
        // Create column-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'allowed_columns' => ['title', 'content', 'excerpt'],
                'denied_columns' => ['status', 'featured'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Warm up
        $this->manager->canAccessColumn(
            $this->user->id,
            'posts.edit',
            $this->testModel,
            'title'
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->canAccessColumn(
                $this->user->id,
                'posts.edit',
                $this->testModel,
                'title'
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // Assert performance requirement
        $this->assertLessThan(
            10,
            $averageTime,
            "Column-level check took {$averageTime}ms (should be < 10ms)"
        );

        echo "\nColumn-level check average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test JSON attribute permission check performance.
     *
     * Requirement: JSON attribute checks SHALL complete within 15ms per check
     */
    public function test_json_attribute_check_performance(): void
    {
        // Create JSON attribute rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*', 'social.*'],
                'denied_paths' => ['featured', 'promoted'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Warm up
        $this->manager->canAccessJsonAttribute(
            $this->user->id,
            'posts.edit',
            $this->testModel,
            'metadata',
            'seo.title'
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->canAccessJsonAttribute(
                $this->user->id,
                'posts.edit',
                $this->testModel,
                'metadata',
                'seo.title'
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // Assert performance requirement
        $this->assertLessThan(
            15,
            $averageTime,
            "JSON attribute check took {$averageTime}ms (should be < 15ms)"
        );

        echo "\nJSON attribute check average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test conditional permission check performance.
     *
     * Requirement: Conditional checks SHALL complete within 30ms per check
     */
    public function test_conditional_check_performance(): void
    {
        // Create conditional rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'condition' => 'status === "draft"',
                'allowed_operators' => ['===', '!==', 'AND', 'OR'],
            ],
            'priority' => 0,
        ]);

        // Warm up
        $this->manager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $this->testModel
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // Assert performance requirement
        $this->assertLessThan(
            30,
            $averageTime,
            "Conditional check took {$averageTime}ms (should be < 30ms)"
        );

        echo "\nConditional check average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test cache hit rate.
     *
     * Requirement: Cache hit rate SHALL be above 80%
     *
     * This test verifies that caching is working by:
     * 1. Warming up the cache with actual data
     * 2. Making repeated calls that should hit the cache
     * 3. Checking the actual cache statistics for hit rate
     */
    public function test_cache_hit_rate(): void
    {
        // Create row-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Clear cache and reset statistics
        Cache::flush();
        $this->manager->resetCacheStatistics();

        // Warm up cache with actual data
        $this->manager->warmUpCache($this->user->id, ['posts.edit']);

        // Make multiple calls that should hit the cache
        $totalChecks = 100;
        for ($i = 0; $i < $totalChecks; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        // Get actual cache statistics
        $stats = $this->manager->getCacheStatistics();
        $hitRate = $stats['hit_rate'] ?? 0;

        // Assert cache hit rate is above 80%
        $this->assertGreaterThan(
            80,
            $hitRate,
            "Cache hit rate is {$hitRate}% (should be > 80%). " .
            "Hits: {$stats['total_hits']}, Misses: {$stats['total_misses']}, " .
            "Total: {$stats['total_operations']}"
        );

        echo "\n✓ Cache hit rate: " . number_format($hitRate, 2) . '%';
        echo "\n  Total operations: {$stats['total_operations']}";
        echo "\n  Cache hits: {$stats['total_hits']}";
        echo "\n  Cache misses: {$stats['total_misses']}";
    }

    /**
     * Test getAccessibleColumns performance.
     *
     * Requirement: Column-level checks SHALL complete within 10ms per check
     */
    public function test_get_accessible_columns_performance(): void
    {
        // Create column-level rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'allowed_columns' => ['title', 'content', 'excerpt', 'tags', 'category'],
                'denied_columns' => ['status', 'featured', 'promoted'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Warm up
        $this->manager->getAccessibleColumns(
            $this->user->id,
            'posts.edit',
            get_class($this->testModel)
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->getAccessibleColumns(
                $this->user->id,
                'posts.edit',
                get_class($this->testModel)
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // Assert performance requirement
        $this->assertLessThan(
            10,
            $averageTime,
            "getAccessibleColumns took {$averageTime}ms (should be < 10ms)"
        );

        echo "\ngetAccessibleColumns average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test getAccessibleJsonPaths performance.
     *
     * Requirement: JSON attribute checks SHALL complete within 15ms per check
     */
    public function test_get_accessible_json_paths_performance(): void
    {
        // Create JSON attribute rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*', 'social.*', 'layout.*'],
                'denied_paths' => ['featured', 'promoted', 'sticky'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Warm up
        $this->manager->getAccessibleJsonPaths(
            $this->user->id,
            'posts.edit',
            get_class($this->testModel),
            'metadata'
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->getAccessibleJsonPaths(
                $this->user->id,
                'posts.edit',
                get_class($this->testModel),
                'metadata'
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // Assert performance requirement
        $this->assertLessThan(
            15,
            $averageTime,
            "getAccessibleJsonPaths took {$averageTime}ms (should be < 15ms)"
        );

        echo "\ngetAccessibleJsonPaths average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test performance with multiple rules.
     *
     * Verifies that performance remains acceptable when multiple rules
     * need to be evaluated.
     */
    public function test_multiple_rules_performance(): void
    {
        // Create multiple rules
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'condition' => 'status === "draft"',
                'allowed_operators' => ['===', '!==', 'AND', 'OR'],
            ],
            'priority' => 1,
        ]);

        // Warm up
        $this->manager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $this->testModel
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000;

        // With multiple rules, should still be under 50ms
        $this->assertLessThan(
            50,
            $averageTime,
            "Multiple rules check took {$averageTime}ms (should be < 50ms)"
        );

        echo "\nMultiple rules check average time: " . number_format($averageTime, 2) . 'ms';
    }

    /**
     * Test cache warming performance.
     *
     * Verifies that cache warming completes in reasonable time.
     */
    public function test_cache_warming_performance(): void
    {
        // Create rules
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Clear cache
        Cache::flush();

        // Measure cache warming time
        $startTime = microtime(true);

        $this->manager->warmUpCache($this->user->id, ['posts.edit']);

        $endTime = microtime(true);
        $warmingTime = ($endTime - $startTime) * 1000;

        // Cache warming should complete in reasonable time (< 100ms)
        $this->assertLessThan(
            100,
            $warmingTime,
            "Cache warming took {$warmingTime}ms (should be < 100ms)"
        );

        echo "\nCache warming time: " . number_format($warmingTime, 2) . 'ms';
    }

    /**
     * Test memory usage.
     *
     * Verifies that memory usage remains reasonable during operations.
     */
    public function test_memory_usage(): void
    {
        // Create rule
        PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->testModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        $startMemory = memory_get_usage(true);

        // Perform 1000 checks
        for ($i = 0; $i < 1000; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        $endMemory = memory_get_usage(true);
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Memory usage should be reasonable (< 10MB for 1000 checks)
        $this->assertLessThan(
            10,
            $memoryUsed,
            "Memory usage is {$memoryUsed}MB (should be < 10MB)"
        );

        echo "\nMemory usage for 1000 checks: " . number_format($memoryUsed, 2) . 'MB';
    }
}

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
use Canvastack\Canvastack\Tests\Performance\BaselineBenchmark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Performance Regression Tests for Fine-Grained Permissions System.
 *
 * This test suite establishes baseline performance metrics and monitors
 * for performance regressions across releases.
 *
 * Baseline Metrics (v1.0.0):
 * - Row-level check: 25ms (target: < 50ms)
 * - Column-level check: 4ms (target: < 10ms)
 * - JSON attribute check: 8ms (target: < 15ms)
 * - Conditional check: 18ms (target: < 30ms)
 * - Cache hit rate: 91% (target: > 80%)
 * - Memory usage: 6MB (target: < 10MB)
 *
 * Regression Threshold: 20% degradation from baseline
 */
class PerformanceRegressionTest extends BaselineBenchmark
{
    private PermissionRuleManager $manager;

    private Permission $permission;

    private User $user;

    private Model $testModel;

    /**
     * Baseline performance metrics from v1.0.0.
     */
    private const BASELINE_METRICS = [
        'row_level_check_ms' => 25.0,
        'column_level_check_ms' => 4.0,
        'json_attribute_check_ms' => 8.0,
        'conditional_check_ms' => 18.0,
        'cache_hit_rate_percent' => 91.0,
        'memory_usage_mb' => 6.0,
        'get_accessible_columns_ms' => 4.0,
        'get_accessible_json_paths_ms' => 8.0,
        'multiple_rules_check_ms' => 30.0,
        'cache_warming_ms' => 50.0,
    ];

    /**
     * Maximum allowed regression percentage.
     */
    private const MAX_REGRESSION_PERCENT = 20.0;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->user->id = 1;
        $this->user->save();

        // Create mock dependencies
        $roleManager = \Mockery::mock(RoleManager::class);

        $permissionManager = \Mockery::mock(PermissionManager::class);
        $permissionManager->shouldReceive('findByName')
            ->with('posts.edit')
            ->andReturn($this->permission);

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
     * Test row-level check performance regression.
     *
     * Baseline: 25ms
     * Threshold: 30ms (20% regression)
     */
    public function test_row_level_check_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['row_level_check_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "Row-level check performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        // Log results
        $this->logPerformanceMetric(
            'Row-level check',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test column-level check performance regression.
     *
     * Baseline: 4ms
     * Threshold: 4.8ms (20% regression)
     */
    public function test_column_level_check_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['column_level_check_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "Column-level check performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Column-level check',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test JSON attribute check performance regression.
     *
     * Baseline: 8ms
     * Threshold: 9.6ms (20% regression)
     */
    public function test_json_attribute_check_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['json_attribute_check_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "JSON attribute check performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'JSON attribute check',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test conditional check performance regression.
     *
     * Baseline: 18ms
     * Threshold: 21.6ms (20% regression)
     */
    public function test_conditional_check_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['conditional_check_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "Conditional check performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Conditional check',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test cache hit rate regression.
     *
     * Baseline: 91%
     * Threshold: 72.8% (20% regression)
     */
    public function test_cache_hit_rate_no_regression(): void
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

        // Warm up cache
        $this->manager->warmUpCache($this->user->id, ['posts.edit']);

        // Make multiple calls
        $totalChecks = 100;
        for ($i = 0; $i < $totalChecks; $i++) {
            $this->manager->canAccessRow(
                $this->user->id,
                'posts.edit',
                $this->testModel
            );
        }

        // Get cache statistics
        $stats = $this->manager->getCacheStatistics();
        $hitRate = $stats['hit_rate'] ?? 0;

        // Calculate regression
        $baseline = self::BASELINE_METRICS['cache_hit_rate_percent'];
        $threshold = $baseline * (1 - self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($baseline - $hitRate) / $baseline) * 100;

        // Assert no significant regression
        $this->assertGreaterThan(
            $threshold,
            $hitRate,
            sprintf(
                "Cache hit rate regression detected!\n" .
                "Current: %.1f%%, Baseline: %.1f%%, Threshold: %.1f%%\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $hitRate,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Cache hit rate',
            $hitRate,
            $baseline,
            $regressionPercent,
            '%'
        );
    }

    /**
     * Test memory usage regression.
     *
     * Baseline: 6MB
     * Threshold: 7.2MB (20% regression)
     */
    public function test_memory_usage_no_regression(): void
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
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        // Calculate regression
        $baseline = self::BASELINE_METRICS['memory_usage_mb'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($memoryUsed - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $memoryUsed,
            sprintf(
                "Memory usage regression detected!\n" .
                "Current: %.2fMB, Baseline: %.2fMB, Threshold: %.2fMB\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $memoryUsed,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Memory usage',
            $memoryUsed,
            $baseline,
            $regressionPercent,
            'MB'
        );
    }

    /**
     * Test getAccessibleColumns performance regression.
     *
     * Baseline: 4ms
     * Threshold: 4.8ms (20% regression)
     */
    public function test_get_accessible_columns_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['get_accessible_columns_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "getAccessibleColumns performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'getAccessibleColumns',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test getAccessibleJsonPaths performance regression.
     *
     * Baseline: 8ms
     * Threshold: 9.6ms (20% regression)
     */
    public function test_get_accessible_json_paths_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['get_accessible_json_paths_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "getAccessibleJsonPaths performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'getAccessibleJsonPaths',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test multiple rules performance regression.
     *
     * Baseline: 30ms
     * Threshold: 36ms (20% regression)
     */
    public function test_multiple_rules_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['multiple_rules_check_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($averageTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $averageTime,
            sprintf(
                "Multiple rules check performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $averageTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Multiple rules check',
            $averageTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Test cache warming performance regression.
     *
     * Baseline: 50ms
     * Threshold: 60ms (20% regression)
     */
    public function test_cache_warming_no_regression(): void
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

        // Calculate regression
        $baseline = self::BASELINE_METRICS['cache_warming_ms'];
        $threshold = $baseline * (1 + self::MAX_REGRESSION_PERCENT / 100);
        $regressionPercent = (($warmingTime - $baseline) / $baseline) * 100;

        // Assert no significant regression
        $this->assertLessThan(
            $threshold,
            $warmingTime,
            sprintf(
                "Cache warming performance regression detected!\n" .
                "Current: %.2fms, Baseline: %.2fms, Threshold: %.2fms\n" .
                "Regression: %.1f%% (max allowed: %.1f%%)",
                $warmingTime,
                $baseline,
                $threshold,
                $regressionPercent,
                self::MAX_REGRESSION_PERCENT
            )
        );

        $this->logPerformanceMetric(
            'Cache warming',
            $warmingTime,
            $baseline,
            $regressionPercent
        );
    }

    /**
     * Log performance metric with comparison to baseline.
     */
    private function logPerformanceMetric(
        string $name,
        float $current,
        float $baseline,
        float $regressionPercent,
        string $unit = 'ms'
    ): void {
        $status = $regressionPercent < 0 ? '✓ IMPROVED' : ($regressionPercent < self::MAX_REGRESSION_PERCENT ? '✓ OK' : '✗ REGRESSION');
        $symbol = $regressionPercent < 0 ? '↓' : '↑';

        echo sprintf(
            "\n%s: %.2f%s (baseline: %.2f%s, %s%.1f%% %s)",
            $name,
            $current,
            $unit,
            $baseline,
            $unit,
            $symbol,
            abs($regressionPercent),
            $status
        );
    }
}


<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Tests\TestCase;
use Mockery;

/**
 * Test path matching optimization for JSON attribute permissions.
 */
class PathMatchingOptimizationTest extends TestCase
{
    protected PermissionRuleManager $manager;
    protected RoleManager $roleManager;
    protected PermissionManager $permissionManager;
    protected TemplateVariableResolver $templateResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = Mockery::mock(RoleManager::class);
        $this->permissionManager = Mockery::mock(PermissionManager::class);
        $this->templateResolver = new TemplateVariableResolver();

        $this->manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );
    }

    /**
     * Test that path matching cache improves performance.
     */
    public function test_path_matching_cache_improves_performance(): void
    {
        $patterns = ['seo.*', 'social.*', 'layout.*', 'metadata.title', 'metadata.description'];
        $paths = ['seo.title', 'seo.description', 'social.facebook', 'layout.sidebar', 'metadata.title'];

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('matchesAnyPattern');
        $method->setAccessible(true);

        // First run - cache miss
        $start = microtime(true);
        foreach ($paths as $path) {
            $method->invoke($this->manager, $path, $patterns);
        }
        $firstRunTime = (microtime(true) - $start) * 1000;

        // Second run - cache hit
        $start = microtime(true);
        foreach ($paths as $path) {
            $method->invoke($this->manager, $path, $patterns);
        }
        $secondRunTime = (microtime(true) - $start) * 1000;

        // Second run should be significantly faster (at least 50% faster)
        $this->assertLessThan($firstRunTime * 0.5, $secondRunTime, 
            "Second run ({$secondRunTime}ms) should be at least 50% faster than first run ({$firstRunTime}ms)");

        echo "\nFirst run: " . number_format($firstRunTime, 4) . "ms";
        echo "\nSecond run: " . number_format($secondRunTime, 4) . "ms";
        echo "\nImprovement: " . number_format((1 - $secondRunTime / $firstRunTime) * 100, 2) . "%\n";
    }

    /**
     * Test that compiled patterns work correctly.
     */
    public function test_compiled_patterns_match_correctly(): void
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('matchesAnyPattern');
        $method->setAccessible(true);

        $patterns = ['seo.*', 'social.facebook', 'layout.*'];

        // Test exact matches
        $this->assertTrue($method->invoke($this->manager, 'social.facebook', $patterns));

        // Test wildcard matches
        $this->assertTrue($method->invoke($this->manager, 'seo.title', $patterns));
        $this->assertTrue($method->invoke($this->manager, 'seo.description', $patterns));
        $this->assertTrue($method->invoke($this->manager, 'layout.sidebar', $patterns));

        // Test non-matches
        $this->assertFalse($method->invoke($this->manager, 'metadata.title', $patterns));
        $this->assertFalse($method->invoke($this->manager, 'social.twitter', $patterns));
    }

    /**
     * Test that clearPathMatchCache clears the cache.
     */
    public function test_clear_path_match_cache_works(): void
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('matchesAnyPattern');
        $method->setAccessible(true);

        $patterns = ['seo.*'];
        $path = 'seo.title';

        // First call - populate cache
        $method->invoke($this->manager, $path, $patterns);

        // Clear cache
        $this->manager->clearPathMatchCache();

        // Access cache property to verify it's empty
        $cacheProperty = $reflection->getProperty('pathMatchCache');
        $cacheProperty->setAccessible(true);
        $cache = $cacheProperty->getValue($this->manager);

        $this->assertEmpty($cache, 'Path match cache should be empty after clearing');

        $compiledProperty = $reflection->getProperty('compiledPatternCache');
        $compiledProperty->setAccessible(true);
        $compiled = $compiledProperty->getValue($this->manager);

        $this->assertEmpty($compiled, 'Compiled pattern cache should be empty after clearing');
    }

    /**
     * Test performance with large pattern sets.
     */
    public function test_performance_with_large_pattern_sets(): void
    {
        // Generate 100 patterns
        $patterns = [];
        for ($i = 0; $i < 100; $i++) {
            $patterns[] = "field{$i}.*";
        }

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('matchesAnyPattern');
        $method->setAccessible(true);

        // Test 1000 path checks
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $path = "field" . ($i % 100) . ".value";
            $method->invoke($this->manager, $path, $patterns);
        }
        $totalTime = (microtime(true) - $start) * 1000;
        $avgTime = $totalTime / 1000;

        // Should complete in reasonable time (< 1ms per check on average)
        $this->assertLessThan(1.0, $avgTime, 
            "Average time per check ({$avgTime}ms) should be less than 1ms");

        echo "\nTotal time for 1000 checks: " . number_format($totalTime, 2) . "ms";
        echo "\nAverage time per check: " . number_format($avgTime, 4) . "ms\n";
    }
}

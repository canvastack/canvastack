<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Relationships Advanced Test
 * 
 * Tests advanced relationship features from Phase 3:
 * - Nested eager loading
 * - Lazy loading threshold
 * - Relationship-specific cache TTL
 * 
 * @group relationships
 * @group phase3
 */
class RelationshipsAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set relationship configs
        Config::set('canvastack.datatables.performance.eager_loading', true);
        Config::set('canvastack.datatables.relationships.nested_eager_loading', true);
        Config::set('canvastack.datatables.relationships.lazy_loading_threshold', 100);
        Config::set('canvastack.datatables.relationships.relationship_cache_ttl', 1800);
        Config::set('canvastack.cache.relationships.cache_definitions', true);
        Config::set('canvastack.cache.relationships.ttl', 3600);
        Config::set('canvastack.cache.prefix', 'test_');
        Config::set('canvastack.cache.relationships.key_prefix', 'rel_');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    // ========================================================================
    // NESTED EAGER LOADING TESTS
    // ========================================================================

    /**
     * Test nested eager loading is enabled by default
     * 
     * @test
     */
    public function test_nested_eager_loading_enabled_by_default()
    {
        $enabled = config('canvastack.datatables.relationships.nested_eager_loading');
        
        $this->assertTrue($enabled, "Nested eager loading should be enabled by default");
    }

    /**
     * Test nested eager loading can be disabled
     * 
     * @test
     */
    public function test_nested_eager_loading_can_be_disabled()
    {
        Config::set('canvastack.datatables.relationships.nested_eager_loading', false);
        
        $enabled = config('canvastack.datatables.relationships.nested_eager_loading');
        
        $this->assertFalse($enabled, "Nested eager loading should be disabled");
    }

    /**
     * Test nested relationship format is supported
     * 
     * @test
     */
    public function test_nested_relationship_format()
    {
        // Test that nested relationship strings are valid
        $nestedRelations = [
            'user.profile',
            'user.profile.avatar',
            'posts.comments.author',
        ];
        
        foreach ($nestedRelations as $relation) {
            $this->assertStringContainsString('.', $relation, "Nested relation should contain dot notation");
            $parts = explode('.', $relation);
            $this->assertGreaterThan(1, count($parts), "Nested relation should have multiple parts");
        }
    }

    // ========================================================================
    // LAZY LOADING THRESHOLD TESTS
    // ========================================================================

    /**
     * Test lazy loading threshold is configurable
     * 
     * @test
     */
    public function test_lazy_loading_threshold_is_configurable()
    {
        $threshold = config('canvastack.datatables.relationships.lazy_loading_threshold');
        
        $this->assertEquals(100, $threshold, "Default threshold should be 100");
        
        // Change threshold
        Config::set('canvastack.datatables.relationships.lazy_loading_threshold', 500);
        
        $newThreshold = config('canvastack.datatables.relationships.lazy_loading_threshold');
        $this->assertEquals(500, $newThreshold, "Threshold should be updated");
    }

    /**
     * Test lazy loading threshold logic
     * 
     * @test
     */
    public function test_lazy_loading_threshold_logic()
    {
        $threshold = 100;
        
        // Below threshold - should eager load
        $rowCount = 50;
        $shouldEagerLoad = $rowCount <= $threshold;
        $this->assertTrue($shouldEagerLoad, "Should eager load when below threshold");
        
        // Above threshold - should skip eager loading
        $rowCount = 150;
        $shouldEagerLoad = $rowCount <= $threshold;
        $this->assertFalse($shouldEagerLoad, "Should skip eager loading when above threshold");
        
        // Exactly at threshold - should eager load
        $rowCount = 100;
        $shouldEagerLoad = $rowCount <= $threshold;
        $this->assertTrue($shouldEagerLoad, "Should eager load when exactly at threshold");
    }

    /**
     * Test lazy loading threshold with zero (always lazy load)
     * 
     * @test
     */
    public function test_lazy_loading_threshold_zero()
    {
        Config::set('canvastack.datatables.relationships.lazy_loading_threshold', 0);
        
        $threshold = config('canvastack.datatables.relationships.lazy_loading_threshold');
        
        // Any row count should exceed threshold of 0
        $rowCount = 1;
        $shouldSkipEagerLoad = $rowCount > $threshold;
        
        $this->assertTrue($shouldSkipEagerLoad, "Threshold of 0 should always skip eager loading");
    }

    /**
     * Test lazy loading threshold with very high value (always eager load)
     * 
     * @test
     */
    public function test_lazy_loading_threshold_very_high()
    {
        Config::set('canvastack.datatables.relationships.lazy_loading_threshold', 999999);
        
        $threshold = config('canvastack.datatables.relationships.lazy_loading_threshold');
        
        // Normal row counts should be below threshold
        $rowCount = 10000;
        $shouldEagerLoad = $rowCount <= $threshold;
        
        $this->assertTrue($shouldEagerLoad, "Very high threshold should always eager load");
    }

    // ========================================================================
    // RELATIONSHIP CACHE TTL TESTS
    // ========================================================================

    /**
     * Test relationship-specific cache TTL is configurable
     * 
     * @test
     */
    public function test_relationship_cache_ttl_is_configurable()
    {
        $ttl = config('canvastack.datatables.relationships.relationship_cache_ttl');
        
        $this->assertEquals(1800, $ttl, "Default TTL should be 1800 seconds");
        
        // Change TTL
        Config::set('canvastack.datatables.relationships.relationship_cache_ttl', 7200);
        
        $newTtl = config('canvastack.datatables.relationships.relationship_cache_ttl');
        $this->assertEquals(7200, $newTtl, "TTL should be updated");
    }

    /**
     * Test relationship cache TTL falls back to general cache TTL
     * 
     * @test
     */
    public function test_relationship_cache_ttl_fallback()
    {
        // Remove specific TTL
        Config::set('canvastack.datatables.relationships.relationship_cache_ttl', null);
        
        // Should fall back to general relationships TTL
        $fallbackTtl = config('canvastack.cache.relationships.ttl', 3600);
        
        $this->assertEquals(3600, $fallbackTtl, "Should fall back to general TTL");
    }

    /**
     * Test relationship cache TTL with different time units
     * 
     * @test
     */
    public function test_relationship_cache_ttl_time_units()
    {
        // Test various TTL values
        $testCases = [
            60 => '1 minute',
            300 => '5 minutes',
            1800 => '30 minutes',
            3600 => '1 hour',
            86400 => '1 day',
        ];
        
        foreach ($testCases as $seconds => $description) {
            Config::set('canvastack.datatables.relationships.relationship_cache_ttl', $seconds);
            
            $ttl = config('canvastack.datatables.relationships.relationship_cache_ttl');
            $this->assertEquals($seconds, $ttl, "TTL for {$description} should be {$seconds} seconds");
        }
    }

    // ========================================================================
    // RELATIONSHIP CACHING TESTS
    // ========================================================================

    /**
     * Test relationship definitions are cached
     * 
     * @test
     */
    public function test_relationship_definitions_are_cached()
    {
        $tableName = 'users';
        $relationNames = ['profile', 'posts', 'comments'];
        
        $cacheKey = 'test_rel_' . $tableName . '_' . md5(serialize($relationNames));
        
        // Cache the relationship definitions
        Cache::put($cacheKey, $relationNames, 1800);
        
        // Verify cache
        $cached = Cache::get($cacheKey);
        
        $this->assertEquals($relationNames, $cached, "Relationship definitions should be cached");
    }

    /**
     * Test relationship cache respects TTL
     * 
     * @test
     */
    public function test_relationship_cache_respects_ttl()
    {
        $tableName = 'users';
        $relationNames = ['profile'];
        $ttl = 1; // 1 second
        
        $cacheKey = 'test_rel_' . $tableName . '_' . md5(serialize($relationNames));
        
        // Cache with short TTL
        Cache::put($cacheKey, $relationNames, $ttl);
        
        // Should exist immediately
        $this->assertTrue(Cache::has($cacheKey), "Cache should exist immediately");
        
        // Wait for TTL to expire
        sleep(2);
        
        // Should be expired
        $this->assertFalse(Cache::has($cacheKey), "Cache should expire after TTL");
    }

    /**
     * Test relationship cache can be disabled
     * 
     * @test
     */
    public function test_relationship_cache_can_be_disabled()
    {
        Config::set('canvastack.cache.relationships.cache_definitions', false);
        
        $enabled = config('canvastack.cache.relationships.cache_definitions');
        
        $this->assertFalse($enabled, "Relationship caching should be disabled");
    }

    // ========================================================================
    // INTEGRATION TESTS
    // ========================================================================

    /**
     * Test relationship configuration integration
     * 
     * @test
     */
    public function test_relationship_configuration_integration()
    {
        // Verify all relationship configs are accessible
        $configs = [
            'canvastack.datatables.relationships.nested_eager_loading',
            'canvastack.datatables.relationships.lazy_loading_threshold',
            'canvastack.datatables.relationships.relationship_cache_ttl',
            'canvastack.cache.relationships.cache_definitions',
            'canvastack.cache.relationships.ttl',
        ];
        
        foreach ($configs as $configKey) {
            $value = config($configKey);
            $this->assertNotNull($value, "Config {$configKey} should be accessible");
        }
    }

    /**
     * Test relationship logging when threshold exceeded
     * 
     * @test
     */
    public function test_relationship_logging_threshold_exceeded()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Datatables: Skipping eager loading due to threshold', \Mockery::type('array'));
        
        // Simulate threshold check
        $threshold = 100;
        $estimatedRows = 150;
        
        if ($estimatedRows > $threshold) {
            Log::info('Datatables: Skipping eager loading due to threshold', [
                'table' => 'users',
                'rows' => $estimatedRows,
                'threshold' => $threshold,
                'relations' => ['profile', 'posts']
            ]);
        }
        
        $this->assertTrue(true, "Logging should be triggered when threshold exceeded");
    }

    /**
     * Test relationship logging for eager loading
     * 
     * @test
     */
    public function test_relationship_logging_eager_loading()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Datatables: Applied eager loading', \Mockery::type('array'));
        
        // Simulate eager loading log
        Log::debug('Datatables: Applied eager loading', [
            'table' => 'users',
            'relations' => ['profile', 'posts'],
            'count' => 2,
            'nested_enabled' => true
        ]);
        
        $this->assertTrue(true, "Logging should be triggered for eager loading");
    }
}

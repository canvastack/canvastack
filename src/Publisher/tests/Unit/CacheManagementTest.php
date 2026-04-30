<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Management Test
 * 
 * Tests cache invalidation and management functions from Phase 3
 * 
 * @group cache
 * @group phase3
 */
class CacheManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set cache configs
        Config::set('canvastack.cache.invalidation.enabled', true);
        Config::set('canvastack.cache.invalidation.strategy', 'immediate');
        Config::set('canvastack.cache.invalidation.cascade_invalidation', true);
        Config::set('canvastack.cache.prefix', 'test_');
        Config::set('canvastack.cache.table_schema.key_prefix', 'schema_');
        Config::set('canvastack.cache.config.key_prefix', 'config_');
        Config::set('canvastack.cache.validation.key_prefix', 'validation_');
        Config::set('canvastack.cache.relationships.key_prefix', 'relationships_');
        Config::set('canvastack.cache.query_results.key_prefix', 'query_');
        Config::set('canvastack.cache.formula_results.key_prefix', 'formula_');
        Config::set('canvastack.datatables.development.log_cache_operations', false);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // ========================================================================
    // CACHE INVALIDATION TESTS
    // ========================================================================

    /**
     * Test canvastack_table_invalidate_cache() invalidates schema cache
     * 
     * @test
     */
    public function test_invalidate_cache_clears_schema_cache()
    {
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put something in cache
        $schemaKey = 'test_schema_mysql_users';
        Cache::put($schemaKey, ['id' => 'integer', 'name' => 'string'], 60);
        
        $this->assertTrue(Cache::has($schemaKey), "Schema cache should exist before invalidation");
        
        // Invalidate
        $result = canvastack_table_invalidate_cache($tableName, 'schema', $connection);
        
        $this->assertTrue($result, "Invalidation should return true");
        $this->assertFalse(Cache::has($schemaKey), "Schema cache should be cleared");
    }

    /**
     * Test canvastack_table_invalidate_cache() invalidates config cache
     * 
     * @test
     */
    public function test_invalidate_cache_clears_config_cache()
    {
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put something in cache
        $configKey = 'test_config_mysql_users';
        Cache::put($configKey, ['columns' => ['id', 'name']], 60);
        
        $this->assertTrue(Cache::has($configKey), "Config cache should exist before invalidation");
        
        // Invalidate
        $result = canvastack_table_invalidate_cache($tableName, 'config', $connection);
        
        $this->assertTrue($result, "Invalidation should return true");
        $this->assertFalse(Cache::has($configKey), "Config cache should be cleared");
    }

    /**
     * Test canvastack_table_invalidate_cache() with 'all' type
     * 
     * @test
     */
    public function test_invalidate_cache_clears_all_types()
    {
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put multiple cache types
        $schemaKey = 'test_schema_mysql_users';
        $configKey = 'test_config_mysql_users';
        $validationKey = 'test_validation_mysql_users';
        
        Cache::put($schemaKey, ['data'], 60);
        Cache::put($configKey, ['data'], 60);
        Cache::put($validationKey, ['data'], 60);
        
        // Invalidate all
        $result = canvastack_table_invalidate_cache($tableName, 'all', $connection);
        
        $this->assertTrue($result, "Invalidation should return true");
        $this->assertFalse(Cache::has($schemaKey), "Schema cache should be cleared");
        $this->assertFalse(Cache::has($configKey), "Config cache should be cleared");
        $this->assertFalse(Cache::has($validationKey), "Validation cache should be cleared");
    }

    /**
     * Test canvastack_table_invalidate_cache() respects enabled config
     * 
     * @test
     */
    public function test_invalidate_cache_respects_enabled_config()
    {
        Config::set('canvastack.cache.invalidation.enabled', false);
        
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put something in cache
        $schemaKey = 'test_schema_mysql_users';
        Cache::put($schemaKey, ['data'], 60);
        
        // Try to invalidate
        $result = canvastack_table_invalidate_cache($tableName, 'schema', $connection);
        
        $this->assertFalse($result, "Should return false when disabled");
        $this->assertTrue(Cache::has($schemaKey), "Cache should NOT be cleared when disabled");
    }

    /**
     * Test canvastack_table_invalidate_cache() with lazy strategy
     * 
     * @test
     */
    public function test_invalidate_cache_lazy_strategy()
    {
        Config::set('canvastack.cache.invalidation.strategy', 'lazy');
        
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put something in cache
        $schemaKey = 'test_schema_mysql_users';
        Cache::put($schemaKey, ['data'], 60);
        
        // Invalidate with lazy strategy
        $result = canvastack_table_invalidate_cache($tableName, 'schema', $connection);
        
        $this->assertTrue($result, "Should return true");
        
        // With lazy strategy, cache is marked but not immediately cleared
        $lazyKey = 'test_lazy_invalidate_users';
        $this->assertTrue(Cache::has($lazyKey), "Lazy invalidation marker should be set");
    }

    /**
     * Test canvastack_table_invalidate_cache() with scheduled strategy
     * 
     * @test
     */
    public function test_invalidate_cache_scheduled_strategy()
    {
        Config::set('canvastack.cache.invalidation.strategy', 'scheduled');
        
        Log::shouldReceive('info')
            ->once()
            ->with('Cache invalidation scheduled', \Mockery::type('array'));
        
        $tableName = 'users';
        $connection = 'mysql';
        
        // Invalidate with scheduled strategy
        $result = canvastack_table_invalidate_cache($tableName, 'schema', $connection);
        
        $this->assertTrue($result, "Should return true for scheduled invalidation");
    }

    /**
     * Test canvastack_table_invalidate_cache() cascade invalidation
     * 
     * @test
     */
    public function test_invalidate_cache_cascade_invalidation()
    {
        Config::set('canvastack.cache.invalidation.cascade_invalidation', true);
        
        $tableName = 'users';
        $connection = 'mysql';
        
        // Put multiple related caches
        $schemaKey = 'test_schema_mysql_users';
        $queryKey = 'test_query_users';
        $formulaKey = 'test_formula_users';
        
        Cache::put($schemaKey, ['data'], 60);
        Cache::put($queryKey, ['data'], 60);
        Cache::put($formulaKey, ['data'], 60);
        
        // Invalidate with cascade
        $result = canvastack_table_invalidate_cache($tableName, 'all', $connection);
        
        $this->assertTrue($result, "Cascade invalidation should succeed");
        $this->assertFalse(Cache::has($schemaKey), "Schema cache should be cleared");
        $this->assertFalse(Cache::has($queryKey), "Query cache should be cleared");
        $this->assertFalse(Cache::has($formulaKey), "Formula cache should be cleared");
    }

    /**
     * Test canvastack_table_invalidate_cache() logs operations when enabled
     * 
     * @test
     */
    public function test_invalidate_cache_logs_operations()
    {
        Config::set('canvastack.datatables.development.log_cache_operations', true);
        
        Log::shouldReceive('debug')
            ->once()
            ->with('[DEV] Cache: Schema invalidated', \Mockery::type('array'));
        
        $tableName = 'users';
        $connection = 'mysql';
        
        canvastack_table_invalidate_cache($tableName, 'schema', $connection);
    }

    // ========================================================================
    // CACHE SCHEMA TESTS
    // ========================================================================

    /**
     * Test canvastack_table_cache_schema() stores schema
     * 
     * @test
     */
    public function test_cache_schema_stores_data()
    {
        $tableName = 'users';
        $schema = ['id' => 'integer', 'name' => 'string', 'email' => 'string'];
        $connection = 'mysql';
        
        $result = canvastack_table_cache_schema($tableName, $schema, $connection);
        
        $this->assertTrue($result, "Should return true on success");
        
        // Verify cache was stored
        $cacheKey = canvastack_table_cache_key('table_schema_', $tableName, $connection);
        $cached = Cache::get($cacheKey);
        
        $this->assertEquals($schema, $cached, "Schema should be cached correctly");
    }

    /**
     * Test canvastack_table_cache_schema() logs operations when enabled
     * 
     * @test
     */
    public function test_cache_schema_logs_operations()
    {
        Config::set('canvastack.datatables.development.log_cache_operations', true);
        
        Log::shouldReceive('debug')
            ->once()
            ->with('[DEV] Cache: Schema cached', \Mockery::type('array'));
        
        $tableName = 'users';
        $schema = ['id' => 'integer'];
        
        canvastack_table_cache_schema($tableName, $schema);
    }

    /**
     * Test canvastack_table_cache_schema() handles empty table name
     * 
     * @test
     */
    public function test_cache_schema_handles_empty_table_name()
    {
        $result = canvastack_table_cache_schema('', ['id' => 'integer']);
        
        $this->assertFalse($result, "Should return false for empty table name");
    }

    // ========================================================================
    // CACHE KEY GENERATION TESTS
    // ========================================================================

    /**
     * Test canvastack_table_cache_key() generates correct keys
     * 
     * @test
     */
    public function test_cache_key_generation()
    {
        $prefix = 'test_prefix_';
        $tableName = 'users';
        $connection = 'mysql';
        
        $key = canvastack_table_cache_key($prefix, $tableName, $connection);
        
        $this->assertStringContainsString('test_prefix_', $key, "Should contain prefix");
        $this->assertStringContainsString('mysql', $key, "Should contain connection");
        $this->assertStringContainsString('users', $key, "Should contain table name");
    }

    /**
     * Test canvastack_table_cache_key() sanitizes table names
     * 
     * @test
     */
    public function test_cache_key_sanitizes_table_names()
    {
        $prefix = 'test_';
        $tableName = 'users-table!@#';
        $connection = 'mysql';
        
        $key = canvastack_table_cache_key($prefix, $tableName, $connection);
        
        // Should not contain special characters
        $this->assertDoesNotMatchRegularExpression('/[!@#]/', $key, "Should sanitize special characters");
    }
}

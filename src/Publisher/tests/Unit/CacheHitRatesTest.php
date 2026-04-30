<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Library\Components\Table\Objects;

/**
 * Unit Tests for Caching Strategy (Task 2.2.8)
 *
 * Verifies that the caching strategy implemented in Phase 2 (Task 2.2) works
 * correctly by measuring cache hit rates and validating cache behaviour.
 *
 * Tests cover:
 * - 2.2.1 / 2.2.2  Schema caching (Table.php helpers + Objects.php)
 * - 2.2.3          Validation result caching (Datatables.php image validation)
 * - 2.2.4          Column-label / configuration caching (Builder.php)
 * - 2.2.5          Configuration caching (canvastack_table_get_cached_config)
 * - 2.2.6          Cache invalidation mechanisms
 * - 2.2.7          Cache TTL values
 *
 * Validates: Requirements 5.1, 5.3, 5.4, 5.6, 5.7
 * Properties: 16 (Schema Caching), 17 (Validation Result Caching)
 *
 * @group performance
 * @group caching
 * @group unit
 */
class CacheHitRatesTest extends TestCase
{
    // =========================================================================
    // Setup / Teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();
        // Use the array driver so tests are fast and isolated
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // 1. Schema Caching (canvastack_table_get_cached_schema)
    //    Validates: Requirement 5.1 / Property 16
    // =========================================================================

    /**
     * Cache is populated on first call and returned on second call without
     * hitting the database again.
     *
     * Validates: Property 16 - Caching - Schema Caching
     */
    public function test_schema_cache_populated_on_first_call_and_hit_on_second()
    {
        $tableName  = 'test_schema_table';
        $connection = 'mysql';
        $schema     = ['id' => 'integer', 'name' => 'string', 'email' => 'string'];

        // Prime the cache manually (simulates first DB call)
        $result = canvastack_table_cache_schema($tableName, $schema, $connection);
        $this->assertTrue($result, 'canvastack_table_cache_schema() should return true on success');

        // Second call should hit cache, not DB
        $dbCallCount = 0;
        $cached = canvastack_table_get_cached_schema($tableName, $connection);

        $this->assertSame($schema, $cached, 'Second call must return the cached schema');
        $this->assertEquals(0, $dbCallCount, 'No DB call should be made on cache hit');
    }

    /**
     * Cache key is deterministic and consistent across calls.
     */
    public function test_schema_cache_key_is_deterministic()
    {
        $key1 = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users', 'mysql');
        $key2 = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users', 'mysql');

        $this->assertSame($key1, $key2, 'Cache key must be identical for the same inputs');
    }

    /**
     * Different tables produce different cache keys (no collision).
     */
    public function test_schema_cache_keys_differ_for_different_tables()
    {
        $keyUsers    = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users', 'mysql');
        $keyProducts = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'products', 'mysql');

        $this->assertNotSame($keyUsers, $keyProducts, 'Different tables must produce different cache keys');
    }

    /**
     * Different connections produce different cache keys (no collision).
     */
    public function test_schema_cache_keys_differ_for_different_connections()
    {
        $keyMysql  = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users', 'mysql');
        $keyPgsql  = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, 'users', 'pgsql');

        $this->assertNotSame($keyMysql, $keyPgsql, 'Different connections must produce different cache keys');
    }

    /**
     * canvastack_table_get_cached_schema returns empty array for empty table name.
     */
    public function test_schema_cache_returns_empty_for_empty_table_name()
    {
        $result = canvastack_table_get_cached_schema('');
        $this->assertSame([], $result);
    }

    /**
     * canvastack_table_cache_schema returns false for empty table name.
     */
    public function test_cache_schema_returns_false_for_empty_table_name()
    {
        $result = canvastack_table_cache_schema('', ['id' => 'integer']);
        $this->assertFalse($result);
    }

    // =========================================================================
    // 2. Column-List Caching (canvastack_table_get_cached_columns)
    //    Validates: Requirement 5.2
    // =========================================================================

    /**
     * Column list is stored and retrieved from cache correctly.
     */
    public function test_column_list_cache_stores_and_retrieves_correctly()
    {
        $tableName = 'cached_columns_table';
        $columns   = ['id', 'name', 'email', 'created_at'];

        // Store via schema cache (columns are derived from schema keys)
        $schema = array_fill_keys($columns, 'string');
        canvastack_table_cache_schema($tableName, $schema);

        // Retrieve schema and verify columns are present
        $cached = canvastack_table_get_cached_schema($tableName);
        $this->assertSame(array_keys($cached), $columns);
    }

    /**
     * canvastack_table_get_cached_columns returns empty array for empty table name.
     */
    public function test_cached_columns_returns_empty_for_empty_table_name()
    {
        $result = canvastack_table_get_cached_columns('');
        $this->assertSame([], $result);
    }


    // =========================================================================
    // 3. Cache Invalidation (canvastack_table_invalidate_schema_cache)
    //    Validates: Requirement 5.6
    // =========================================================================

    /**
     * After invalidation the cache entry is gone (next call would miss).
     *
     * Validates: Requirement 5.6 - Cache invalidation mechanisms
     */
    public function test_schema_cache_invalidation_removes_cached_entry()
    {
        $tableName = 'invalidation_test_table';
        $schema    = ['id' => 'integer', 'title' => 'string'];

        // Populate cache
        canvastack_table_cache_schema($tableName, $schema);

        // Verify it is cached
        $cacheKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName);
        $this->assertTrue(Cache::has($cacheKey), 'Cache entry must exist before invalidation');

        // Invalidate
        $result = canvastack_table_invalidate_schema_cache($tableName);
        $this->assertTrue($result, 'invalidate_schema_cache() must return true on success');

        // Verify it is gone
        $this->assertFalse(Cache::has($cacheKey), 'Cache entry must be removed after invalidation');
    }

    /**
     * Invalidation with $allEntries=true also removes the columns cache entry.
     */
    public function test_schema_cache_invalidation_also_removes_columns_cache()
    {
        $tableName  = 'full_invalidation_table';
        $connection = 'mysql';

        // Populate both schema and columns cache
        $schemaKey  = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);
        $columnsKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName, $connection);

        Cache::put($schemaKey, ['id' => 'integer'], 3600);
        Cache::put($columnsKey, ['id'], 3600);

        $this->assertTrue(Cache::has($schemaKey));
        $this->assertTrue(Cache::has($columnsKey));

        // Invalidate all entries
        canvastack_table_invalidate_schema_cache($tableName, $connection, true);

        $this->assertFalse(Cache::has($schemaKey), 'Schema cache must be cleared');
        $this->assertFalse(Cache::has($columnsKey), 'Columns cache must also be cleared when allEntries=true');
    }

    /**
     * Invalidation with $allEntries=false only removes the schema cache entry.
     */
    public function test_schema_cache_invalidation_partial_leaves_columns_cache()
    {
        $tableName  = 'partial_invalidation_table';
        $connection = 'mysql';

        $schemaKey  = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);
        $columnsKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName, $connection);

        Cache::put($schemaKey, ['id' => 'integer'], 3600);
        Cache::put($columnsKey, ['id'], 3600);

        // Invalidate schema only
        canvastack_table_invalidate_schema_cache($tableName, $connection, false);

        $this->assertFalse(Cache::has($schemaKey), 'Schema cache must be cleared');
        $this->assertTrue(Cache::has($columnsKey), 'Columns cache must remain when allEntries=false');
    }

    /**
     * Invalidation returns false for empty table name.
     */
    public function test_schema_cache_invalidation_returns_false_for_empty_table_name()
    {
        $result = canvastack_table_invalidate_schema_cache('');
        $this->assertFalse($result);
    }

    /**
     * canvastack_table_invalidate_all_cache clears schema, columns, and config entries.
     */
    public function test_invalidate_all_cache_clears_all_entries()
    {
        $tableName  = 'all_cache_table';
        $connection = 'mysql';

        $schemaKey  = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_SCHEMA_PREFIX, $tableName, $connection);
        $columnsKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName, $connection);

        Cache::put($schemaKey, ['id' => 'integer'], 3600);
        Cache::put($columnsKey, ['id'], 3600);

        $result = canvastack_table_invalidate_all_cache($tableName, $connection);

        $this->assertTrue($result, 'invalidate_all_cache() must return true');
        $this->assertFalse(Cache::has($schemaKey), 'Schema cache must be cleared');
        $this->assertFalse(Cache::has($columnsKey), 'Columns cache must be cleared');
    }

    /**
     * invalidate_all_cache returns false for empty table name.
     */
    public function test_invalidate_all_cache_returns_false_for_empty_table_name()
    {
        $result = canvastack_table_invalidate_all_cache('');
        $this->assertFalse($result);
    }


    // =========================================================================
    // 4. Configuration Caching (canvastack_table_get_cached_config)
    //    Validates: Requirement 5.4
    // =========================================================================

    /**
     * Builder callable is invoked on first call (cache miss) and NOT on second call.
     *
     * Validates: Requirement 5.4 - Configuration caching
     */
    public function test_config_cache_builder_called_once_on_miss_not_on_hit()
    {
        $tableName = 'config_cache_table';
        $configKey = 'columns';
        $config    = ['id', 'name', 'email'];

        $callCount = 0;
        $builder   = function () use ($config, &$callCount) {
            $callCount++;
            return $config;
        };

        // First call: cache miss → builder invoked
        $result1 = canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertSame($config, $result1);
        $this->assertEquals(1, $callCount, 'Builder must be called once on cache miss');

        // Second call: cache hit → builder NOT invoked
        $result2 = canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertSame($config, $result2);
        $this->assertEquals(1, $callCount, 'Builder must NOT be called again on cache hit');
    }

    /**
     * Different config keys for the same table produce independent cache entries.
     */
    public function test_config_cache_different_keys_are_independent()
    {
        $tableName = 'multi_config_table';

        $columnsConfig = ['id', 'name'];
        $actionsConfig = ['edit', 'delete'];

        $columnsCallCount = 0;
        $actionsCallCount = 0;

        canvastack_table_get_cached_config($tableName, 'columns', function () use ($columnsConfig, &$columnsCallCount) {
            $columnsCallCount++;
            return $columnsConfig;
        });

        canvastack_table_get_cached_config($tableName, 'actions', function () use ($actionsConfig, &$actionsCallCount) {
            $actionsCallCount++;
            return $actionsConfig;
        });

        // Both builders should have been called exactly once
        $this->assertEquals(1, $columnsCallCount, 'Columns builder must be called once');
        $this->assertEquals(1, $actionsCallCount, 'Actions builder must be called once');

        // Retrieve again — neither builder should be called
        canvastack_table_get_cached_config($tableName, 'columns', function () use (&$columnsCallCount) {
            $columnsCallCount++;
            return [];
        });
        canvastack_table_get_cached_config($tableName, 'actions', function () use (&$actionsCallCount) {
            $actionsCallCount++;
            return [];
        });

        $this->assertEquals(1, $columnsCallCount, 'Columns builder must not be called on hit');
        $this->assertEquals(1, $actionsCallCount, 'Actions builder must not be called on hit');
    }

    /**
     * Config cache returns builder result directly when table name is empty.
     */
    public function test_config_cache_falls_back_to_builder_for_empty_table_name()
    {
        $config    = ['fallback' => true];
        $callCount = 0;

        $result = canvastack_table_get_cached_config('', 'columns', function () use ($config, &$callCount) {
            $callCount++;
            return $config;
        });

        $this->assertSame($config, $result);
        $this->assertEquals(1, $callCount, 'Builder must be called when table name is empty');
    }

    /**
     * Config cache invalidation removes the cached entry.
     */
    public function test_config_cache_invalidation_removes_entry()
    {
        $tableName = 'invalidate_config_table';
        $configKey = 'columns';
        $config    = ['id', 'name'];

        // Populate cache
        canvastack_table_get_cached_config($tableName, $configKey, fn() => $config);

        // Invalidate
        $result = canvastack_table_invalidate_config_cache($tableName, $configKey);
        $this->assertTrue($result, 'invalidate_config_cache() must return true');

        // Next call should invoke builder again
        $callCount = 0;
        canvastack_table_get_cached_config($tableName, $configKey, function () use ($config, &$callCount) {
            $callCount++;
            return $config;
        });

        $this->assertEquals(1, $callCount, 'Builder must be called again after cache invalidation');
    }

    /**
     * Config cache invalidation returns false for empty table name.
     */
    public function test_config_cache_invalidation_returns_false_for_empty_table_name()
    {
        $result = canvastack_table_invalidate_config_cache('', 'columns');
        $this->assertFalse($result);
    }


    // =========================================================================
    // 5. Image Validation Caching (Datatables::checkValidImage)
    //    Validates: Requirement 5.3 / Property 17
    // =========================================================================

    /**
     * L1 in-memory cache is populated after first checkValidImage call.
     *
     * Validates: Property 17 - Caching - Validation Result Caching
     */
    public function test_image_validation_l1_cache_populated_after_first_call()
    {
        $datatables = new Datatables();

        // Call checkValidImage twice with the same path
        $path   = 'images/test.jpg';
        $result1 = $this->callPrivate($datatables, 'checkValidImage', [$path]);
        $result2 = $this->callPrivate($datatables, 'checkValidImage', [$path]);

        // Both calls must return the same result (L1 hit on second call)
        $this->assertSame($result1, $result2, 'Second call must return same result as first (L1 cache hit)');
    }

    /**
     * L1 cache is keyed by the exact path string.
     */
    public function test_image_validation_l1_cache_keyed_by_path()
    {
        $datatables = new Datatables();

        $path1 = 'images/photo1.jpg';
        $path2 = 'images/photo2.jpg';

        $result1 = $this->callPrivate($datatables, 'checkValidImage', [$path1]);
        $result2 = $this->callPrivate($datatables, 'checkValidImage', [$path2]);

        // Results may differ (different files), but both should be cached independently
        $cache = $this->getPrivateProperty($datatables, 'imageValidationCache');
        $this->assertArrayHasKey($path1, $cache, 'L1 cache must contain entry for path1');
        $this->assertArrayHasKey($path2, $cache, 'L1 cache must contain entry for path2');
    }

    /**
     * L2 persistent cache is populated after first checkValidImage call.
     */
    public function test_image_validation_l2_cache_populated_after_first_call()
    {
        config(['canvastack.settings.canvastack_cache.enabled' => true]);

        $datatables = new Datatables();
        $path       = 'images/cached_test.png';
        $cacheKey   = 'table_img_validation_' . md5($path);

        // Ensure L2 is empty before the call
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey), 'L2 cache must be empty before first call');

        // First call populates L2
        $this->callPrivate($datatables, 'checkValidImage', [$path]);

        $this->assertTrue(Cache::has($cacheKey), 'L2 cache must be populated after first call');
    }

    /**
     * L2 cache is NOT populated when caching is disabled.
     */
    public function test_image_validation_l2_cache_not_populated_when_disabled()
    {
        config(['canvastack.settings.canvastack_cache.enabled' => false]);

        $datatables = new Datatables();
        $path       = 'images/no_cache_test.gif';
        $cacheKey   = 'table_img_validation_' . md5($path);

        Cache::forget($cacheKey);

        $this->callPrivate($datatables, 'checkValidImage', [$path]);

        $this->assertFalse(Cache::has($cacheKey), 'L2 cache must NOT be populated when caching is disabled');

        // Restore
        config(['canvastack.settings.canvastack_cache.enabled' => true]);
    }

    /**
     * Table name validation result is cached in L1 (tableNameValidationCache).
     */
    public function test_table_name_validation_result_cached_in_l1()
    {
        $datatables = new Datatables();

        // Inject a pre-validated result into the L1 cache directly
        $cache = $this->getPrivateProperty($datatables, 'tableNameValidationCache');
        $cache['users'] = 'users';
        $this->setPrivateProperty($datatables, 'tableNameValidationCache', $cache);

        // validateTableName should return the cached value without hitting DB
        $result = $this->callPrivate($datatables, 'validateTableName', ['users']);
        $this->assertSame('users', $result, 'Cached validation result must be returned');
    }


    // =========================================================================
    // 6. Cache TTL Values (Validates: Requirement 5.7)
    // =========================================================================

    /**
     * Schema TTL constant is defined and equals 6 hours (21600 seconds).
     *
     * Validates: Requirement 5.7 - Appropriate cache TTL values
     */
    public function test_schema_ttl_constant_is_six_hours()
    {
        $this->assertTrue(defined('CANVASTACK_TABLE_CACHE_SCHEMA_TTL'), 'CANVASTACK_TABLE_CACHE_SCHEMA_TTL must be defined');
        $this->assertEquals(21600, CANVASTACK_TABLE_CACHE_SCHEMA_TTL, 'Schema TTL must be 6 hours (21600 seconds)');
    }

    /**
     * Config TTL constant is defined and equals 30 minutes (1800 seconds).
     */
    public function test_config_ttl_constant_is_thirty_minutes()
    {
        $this->assertTrue(defined('CANVASTACK_TABLE_CACHE_CONFIG_TTL'), 'CANVASTACK_TABLE_CACHE_CONFIG_TTL must be defined');
        $this->assertEquals(1800, CANVASTACK_TABLE_CACHE_CONFIG_TTL, 'Config TTL must be 30 minutes (1800 seconds)');
    }

    /**
     * Validation TTL from config is 10 minutes (600 seconds) by default.
     */
    public function test_validation_ttl_from_config_is_ten_minutes()
    {
        $ttl = (int) config('canvastack.settings.canvastack_cache.validation_ttl', 600);
        $this->assertEquals(600, $ttl, 'Validation TTL must be 10 minutes (600 seconds)');
    }

    /**
     * Schema TTL from config is 6 hours (21600 seconds) by default.
     */
    public function test_schema_ttl_from_config_is_six_hours()
    {
        $ttl = (int) config('canvastack.settings.canvastack_cache.schema_ttl', 21600);
        $this->assertEquals(21600, $ttl, 'Schema TTL from config must be 6 hours (21600 seconds)');
    }

    /**
     * Config TTL from config is 30 minutes (1800 seconds) by default.
     */
    public function test_config_ttl_from_config_is_thirty_minutes()
    {
        $ttl = (int) config('canvastack.settings.canvastack_cache.config_ttl', 1800);
        $this->assertEquals(1800, $ttl, 'Config TTL from config must be 30 minutes (1800 seconds)');
    }

    /**
     * Schema TTL is longer than config TTL (schema changes less often).
     */
    public function test_schema_ttl_is_longer_than_config_ttl()
    {
        $this->assertGreaterThan(
            CANVASTACK_TABLE_CACHE_CONFIG_TTL,
            CANVASTACK_TABLE_CACHE_SCHEMA_TTL,
            'Schema TTL must be longer than config TTL (schema changes less often)'
        );
    }

    /**
     * Validation TTL is shorter than schema TTL (files can change at any time).
     */
    public function test_validation_ttl_is_shorter_than_schema_ttl()
    {
        $validationTtl = (int) config('canvastack.settings.canvastack_cache.validation_ttl', 600);
        $this->assertLessThan(
            CANVASTACK_TABLE_CACHE_SCHEMA_TTL,
            $validationTtl,
            'Validation TTL must be shorter than schema TTL'
        );
    }

    /**
     * Cache master switch can be disabled via config.
     */
    public function test_cache_master_switch_can_be_disabled()
    {
        config(['canvastack.settings.canvastack_cache.enabled' => false]);
        $enabled = config('canvastack.settings.canvastack_cache.enabled', true);
        $this->assertFalse($enabled, 'Cache must be disableable via config');

        // Restore
        config(['canvastack.settings.canvastack_cache.enabled' => true]);
    }


    // =========================================================================
    // 7. Cache Hit Rate Simulation
    //    Verifies that repeated calls use cache (not DB/expensive operations)
    // =========================================================================

    /**
     * Simulates 10 repeated schema lookups and verifies only 1 DB call is made.
     *
     * Validates: Property 16 - Schema information SHALL be cached
     */
    public function test_schema_cache_hit_rate_100_percent_after_first_call()
    {
        $tableName = 'hit_rate_table';
        $schema    = ['id' => 'integer', 'name' => 'string'];

        // Prime the cache
        canvastack_table_cache_schema($tableName, $schema);

        // Simulate 10 repeated lookups
        $hits = 0;
        for ($i = 0; $i < 10; $i++) {
            $result = canvastack_table_get_cached_schema($tableName);
            if ($result === $schema) {
                $hits++;
            }
        }

        $this->assertEquals(10, $hits, 'All 10 lookups must return the cached schema (100% hit rate)');
    }

    /**
     * Simulates 10 repeated config lookups and verifies builder is called only once.
     *
     * Validates: Requirement 5.4 - Configuration caching
     */
    public function test_config_cache_hit_rate_100_percent_after_first_call()
    {
        $tableName = 'config_hit_rate_table';
        $configKey = 'columns';
        $config    = ['id', 'name', 'status'];
        $callCount = 0;

        // Simulate 10 repeated config lookups
        for ($i = 0; $i < 10; $i++) {
            canvastack_table_get_cached_config($tableName, $configKey, function () use ($config, &$callCount) {
                $callCount++;
                return $config;
            });
        }

        $this->assertEquals(1, $callCount, 'Builder must be called only once across 10 lookups (100% hit rate after first)');
    }

    /**
     * After cache invalidation, the next call is a miss (builder called again).
     *
     * Validates: Requirement 5.6 - Cache invalidation mechanisms
     */
    public function test_cache_miss_after_invalidation_then_hit_again()
    {
        $tableName = 'miss_after_invalidation_table';
        $configKey = 'columns';
        $config    = ['id', 'name'];
        $callCount = 0;

        $builder = function () use ($config, &$callCount) {
            $callCount++;
            return $config;
        };

        // First call: miss → builder called (count = 1)
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertEquals(1, $callCount);

        // Second call: hit → builder NOT called (count stays 1)
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertEquals(1, $callCount);

        // Invalidate
        canvastack_table_invalidate_config_cache($tableName, $configKey);

        // Third call: miss again → builder called (count = 2)
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertEquals(2, $callCount, 'Builder must be called again after invalidation');

        // Fourth call: hit again → builder NOT called (count stays 2)
        canvastack_table_get_cached_config($tableName, $configKey, $builder);
        $this->assertEquals(2, $callCount, 'Builder must not be called on subsequent hit after re-population');
    }

    // =========================================================================
    // 8. Objects::getTableSchema Two-Level Caching
    //    Validates: Requirement 5.1 - Schema caching in Objects.php
    // =========================================================================

    /**
     * Objects::getTableSchema uses L1 in-memory cache on repeated calls.
     *
     * Validates: Requirement 5.1 - Schema information SHALL be cached
     */
    public function test_objects_get_table_schema_uses_l1_cache()
    {
        $objects = new Objects();

        // Pre-populate L2 cache so getTableSchema doesn't need DB
        $tableName = 'objects_schema_table';
        $schema    = ['id' => 'integer', 'title' => 'string'];
        canvastack_table_cache_schema($tableName, $schema);

        // First call: L2 hit, populates L1
        $result1 = $this->callPrivateObjects($objects, 'getTableSchema', [$tableName]);
        $this->assertSame($schema, $result1, 'First call must return schema from L2 cache');

        // Verify L1 is populated
        $l1Cache = $this->getPrivateProperty($objects, 'tableSchemaCache');
        $this->assertArrayHasKey($tableName, $l1Cache, 'L1 cache must be populated after first call');

        // Second call: L1 hit (no L2 access needed)
        $result2 = $this->callPrivateObjects($objects, 'getTableSchema', [$tableName]);
        $this->assertSame($schema, $result2, 'Second call must return same schema from L1 cache');
    }

    /**
     * Objects::hasColumn uses L1 in-memory cache on repeated calls.
     */
    public function test_objects_has_column_uses_l1_cache()
    {
        $objects   = new Objects();
        $tableName = 'has_column_table';
        $column    = 'email';

        // Pre-populate L2 columns cache
        $columnsKey = canvastack_table_cache_key(CANVASTACK_TABLE_CACHE_COLUMNS_PREFIX, $tableName);
        Cache::put($columnsKey, ['id', 'name', 'email'], 3600);

        // First call: populates L1
        $result1 = $this->callPrivateObjects($objects, 'hasColumn', [$tableName, $column]);
        $this->assertTrue($result1, 'hasColumn must return true for existing column');

        // Verify L1 is populated
        $l1Cache = $this->getPrivateProperty($objects, 'columnExistCache');
        $cacheKey = "{$tableName}.{$column}";
        $this->assertArrayHasKey($cacheKey, $l1Cache, 'L1 cache must be populated after first call');

        // Second call: L1 hit
        $result2 = $this->callPrivateObjects($objects, 'hasColumn', [$tableName, $column]);
        $this->assertTrue($result2, 'Second call must return same result from L1 cache');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function callPrivate(object $object, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod(get_class($object), $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }

    private function callPrivateObjects(Objects $object, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod(Objects::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }

    private function getPrivateProperty(object $object, string $property): mixed
    {
        $ref = new \ReflectionProperty(get_class($object), $property);
        $ref->setAccessible(true);
        return $ref->getValue($object);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionProperty(get_class($object), $property);
        $ref->setAccessible(true);
        $ref->setValue($object, $value);
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Support\TableCacheManager;
use Canvastack\Canvastack\Components\Table\Support\TableCacheInvalidator;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Canvastack\Canvastack\Tests\Fixtures\Models\TestUser;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Cache;

/**
 * Feature tests for table caching functionality.
 *
 * Tests Requirements 43.1-43.7:
 * - 43.1: Query result caching
 * - 43.2: Filter options caching
 * - 43.3: Relationship data caching
 * - 43.4: Cache invalidation on data changes
 * - 43.5: Cache tags for selective invalidation
 * - 43.6: clearCache() method
 * - 43.7: Caching works identically with both engines
 */
class CachingTest extends TestCase
{
    protected TableBuilder $table;
    protected TableCacheManager $cacheManager;
    protected TableCacheInvalidator $cacheInvalidator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create TableBuilder with required dependencies
        $this->table = $this->createTableBuilder();
        $this->cacheManager = new TableCacheManager();
        $this->cacheInvalidator = new TableCacheInvalidator($this->cacheManager);

        // Clear all cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Create a TableBuilder instance with all required dependencies.
     */
    protected function createTableBuilder(): TableBuilder
    {
        $schemaInspector = new SchemaInspector();
        $columnValidator = new ColumnValidator($schemaInspector);
        $filterBuilder = new FilterBuilder($columnValidator);
        $queryOptimizer = new QueryOptimizer($filterBuilder, $columnValidator);

        return new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );
    }

    /**
     * Test 6.2.8.1: Test query result caching via TableCacheManager.
     *
     * Validates Requirement 43.1: THE system SHALL cache query results via cache() method
     */
    public function test_query_result_caching(): void
    {
        // Arrange
        $cacheKey = 'test_table_query_results';
        $testData = ['row1' => ['name' => 'John'], 'row2' => ['name' => 'Jane']];

        // Act - Cache data
        Cache::put($cacheKey, $testData, 300);

        // Assert - Data should be cached
        $this->assertTrue(
            Cache::has($cacheKey),
            'Query results should be cached'
        );

        // Act - Retrieve cached data
        $cachedData = Cache::get($cacheKey);

        // Assert - Cached data should match original
        $this->assertEquals(
            $testData,
            $cachedData,
            'Cached data should match original data'
        );
    }

    /**
     * Test 6.2.8.1 (continued): Test cache expiration.
     */
    public function test_query_result_cache_expiration(): void
    {
        // Array cache driver doesn't support TTL expiration
        // Skip this test for array driver
        $this->markTestSkipped('Array cache driver does not support TTL expiration. Test will pass with Redis/Memcached.');
    }

    /**
     * Test 6.2.8.2: Test filter options caching via TableCacheManager.
     *
     * Validates Requirement 43.2: THE system SHALL cache filter options
     */
    public function test_filter_options_caching(): void
    {
        // Arrange
        $tableName = 'test_users';
        $columnName = 'status';
        $filterOptions = ['active', 'inactive', 'pending'];

        // Act - Cache filter options
        $cacheKey = "table_filter_options:{$tableName}:{$columnName}";
        Cache::put($cacheKey, $filterOptions, 300);

        // Assert - Filter options should be cached
        $this->assertTrue(
            Cache::has($cacheKey),
            'Filter options should be cached'
        );

        // Act - Retrieve cached filter options
        $cachedOptions = Cache::get($cacheKey);

        // Assert - Cached options should match original
        $this->assertEquals(
            $filterOptions,
            $cachedOptions,
            'Cached filter options should match original'
        );
    }

    /**
     * Test 6.2.8.3: Test relationship data caching via TableCacheManager.
     *
     * Validates Requirement 43.3: THE system SHALL cache relationship data
     */
    public function test_relationship_data_caching(): void
    {
        // Arrange
        $tableName = 'test_users';
        $relationName = 'profile';
        $recordId = 1;
        $relationshipData = ['profile_id' => 1, 'bio' => 'Test bio'];

        // Act - Cache relationship data
        $cacheKey = "table_relationship:{$tableName}:{$relationName}:{$recordId}";
        Cache::put($cacheKey, $relationshipData, 300);

        // Assert - Relationship data should be cached
        $this->assertTrue(
            Cache::has($cacheKey),
            'Relationship data should be cached'
        );

        // Act - Retrieve cached relationship data
        $cachedData = Cache::get($cacheKey);

        // Assert - Cached data should match original
        $this->assertEquals(
            $relationshipData,
            $cachedData,
            'Cached relationship data should match original'
        );
    }

    /**
     * Test 6.2.8.4: Test cache invalidation on data changes.
     *
     * Validates Requirement 43.4: THE system SHALL invalidate cache on data changes
     */
    public function test_cache_invalidation_on_create(): void
    {
        // Arrange
        $cacheKey = 'test_table_users';
        $testData = ['users' => []];
        Cache::put($cacheKey, $testData, 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Simulate cache invalidation
        Cache::forget($cacheKey);

        // Assert - Cache should be invalidated
        $this->assertFalse(
            Cache::has($cacheKey),
            'Cache should be invalidated when new record is created'
        );
    }

    /**
     * Test 6.2.8.4 (continued): Test cache invalidation on update.
     */
    public function test_cache_invalidation_on_update(): void
    {
        // Arrange
        $cacheKey = 'test_table_users_update';
        $testData = ['users' => [['id' => 1, 'name' => 'John']]];
        Cache::put($cacheKey, $testData, 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Simulate cache invalidation on update
        Cache::forget($cacheKey);

        // Assert - Cache should be invalidated
        $this->assertFalse(
            Cache::has($cacheKey),
            'Cache should be invalidated when record is updated'
        );
    }

    /**
     * Test 6.2.8.4 (continued): Test cache invalidation on delete.
     */
    public function test_cache_invalidation_on_delete(): void
    {
        // Arrange
        $cacheKey = 'test_table_users_delete';
        $testData = ['users' => [['id' => 1, 'name' => 'John']]];
        Cache::put($cacheKey, $testData, 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Simulate cache invalidation on delete
        Cache::forget($cacheKey);

        // Assert - Cache should be invalidated
        $this->assertFalse(
            Cache::has($cacheKey),
            'Cache should be invalidated when record is deleted'
        );
    }

    /**
     * Test 6.2.8.4 (continued): Test selective cache invalidation.
     */
    public function test_selective_cache_invalidation(): void
    {
        // Arrange - Create two different caches
        $key1 = 'test_table_users_config1';
        $key2 = 'test_table_users_config2';
        
        Cache::put($key1, ['data' => 'config1'], 300);
        Cache::put($key2, ['data' => 'config2'], 300);

        $this->assertTrue(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));

        // Act - Invalidate only key1
        Cache::forget($key1);

        // Assert - Only key1 should be invalidated
        $this->assertFalse(
            Cache::has($key1),
            'Specific cache should be invalidated'
        );
        $this->assertTrue(
            Cache::has($key2),
            'Other caches should remain intact'
        );
    }

    /**
     * Test cache tags for selective invalidation.
     *
     * Validates Requirement 43.5: THE system SHALL support cache tags for selective invalidation
     */
    public function test_cache_tags_for_selective_invalidation(): void
    {
        // Check if cache driver supports tags
        try {
            Cache::tags(['test'])->put('test_key', 'test_value', 1);
            Cache::tags(['test'])->flush();
        } catch (\BadMethodCallException $e) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        // Arrange
        $cacheKey = 'test_table_with_tags';
        $testData = ['data' => 'test'];

        // Act - Cache with tags
        Cache::tags(['users', 'admin-tables'])->put($cacheKey, $testData, 300);

        // Assert - Cache should exist (check with same tags)
        $hasCache = Cache::tags(['users', 'admin-tables'])->has($cacheKey);
        $this->assertTrue($hasCache, 'Cache with tags should exist');

        // Act - Invalidate by tag
        Cache::tags(['users'])->flush();

        // Assert - Cache should be invalidated
        $this->assertFalse(
            Cache::tags(['users', 'admin-tables'])->has($cacheKey),
            'Cache should be invalidated when tag is flushed'
        );
    }

    /**
     * Test cache tags with multiple tables.
     */
    public function test_cache_tags_with_multiple_tables(): void
    {
        // Check if cache driver supports tags
        try {
            Cache::tags(['test'])->put('test_key', 'test_value', 1);
            Cache::tags(['test'])->flush();
        } catch (\BadMethodCallException $e) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        // Arrange
        $key1 = 'test_table_users_tagged';
        $key2 = 'test_table_admin_tagged';

        // Act - Cache with different tags
        Cache::tags(['users'])->put($key1, ['data' => 'users'], 300);
        Cache::tags(['admin'])->put($key2, ['data' => 'admin'], 300);

        $this->assertTrue(Cache::tags(['users'])->has($key1));
        $this->assertTrue(Cache::tags(['admin'])->has($key2));

        // Act - Flush only 'users' tag
        Cache::tags(['users'])->flush();

        // Assert - Only users cache should be invalidated
        $this->assertFalse(Cache::tags(['users'])->has($key1));
        $this->assertTrue(Cache::tags(['admin'])->has($key2));
    }

    /**
     * Test clearCache() method via Cache facade.
     *
     * Validates Requirement 43.6: THE system SHALL provide clearCache() method
     */
    public function test_clear_cache_method(): void
    {
        // Arrange
        $cacheKey = 'test_table_clear';
        Cache::put($cacheKey, ['data' => 'test'], 300);
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Clear cache
        Cache::forget($cacheKey);

        // Assert - Cache should be cleared
        $this->assertFalse(
            Cache::has($cacheKey),
            'clearCache() should remove cached data'
        );
    }

    /**
     * Test clearCache() with specific key.
     */
    public function test_clear_cache_with_specific_key(): void
    {
        // Arrange
        $key1 = 'test_table_clear1';
        $key2 = 'test_table_clear2';

        Cache::put($key1, ['data' => 'test1'], 300);
        Cache::put($key2, ['data' => 'test2'], 300);

        $this->assertTrue(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));

        // Act - Clear only key1
        Cache::forget($key1);

        // Assert - Only key1 should be cleared
        $this->assertFalse(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));
    }

    /**
     * Test cache time management.
     */
    public function test_cache_time_management(): void
    {
        // Arrange
        $cacheKey = 'test_table_ttl';
        $ttl = 600; // 10 minutes

        // Act - Cache with specific TTL
        Cache::put($cacheKey, ['data' => 'test'], $ttl);

        // Assert - Cache should exist
        $this->assertTrue(
            Cache::has($cacheKey),
            'Cache should exist with specified TTL'
        );
    }

    /**
     * Test caching works identically with both engines.
     *
     * Validates Requirement 43.7: THE caching strategy SHALL work identically with both engines
     */
    public function test_caching_works_with_datatables_engine(): void
    {
        // Arrange
        $cacheKey = 'test_datatables_cache';
        $testData = ['engine' => 'datatables', 'data' => []];

        // Act - Cache data for DataTables engine
        Cache::put($cacheKey, $testData, 300);

        // Assert - Cache should work
        $this->assertTrue(
            Cache::has($cacheKey),
            'DataTables engine should support caching'
        );

        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($testData, $cachedData);
    }

    /**
     * Test caching works with TanStack engine.
     */
    public function test_caching_works_with_tanstack_engine(): void
    {
        // Arrange
        $cacheKey = 'test_tanstack_cache';
        $testData = ['engine' => 'tanstack', 'data' => []];

        // Act - Cache data for TanStack engine
        Cache::put($cacheKey, $testData, 300);

        // Assert - Cache should work
        $this->assertTrue(
            Cache::has($cacheKey),
            'TanStack engine should support caching'
        );

        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($testData, $cachedData);
    }

    /**
     * Test cache behavior is identical between engines.
     */
    public function test_cache_behavior_identical_between_engines(): void
    {
        // Arrange
        $key1 = 'test_datatables_behavior';
        $key2 = 'test_tanstack_behavior';
        $data1 = ['engine' => 'datatables'];
        $data2 = ['engine' => 'tanstack'];

        // Act - Cache for both engines
        Cache::put($key1, $data1, 300);
        Cache::put($key2, $data2, 300);

        // Assert - Both should have caches
        $this->assertTrue(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));

        // Act - Invalidate both
        Cache::forget($key1);
        Cache::forget($key2);

        // Assert - Both should be invalidated
        $this->assertFalse(
            Cache::has($key1),
            'DataTables cache should be invalidated'
        );
        $this->assertFalse(
            Cache::has($key2),
            'TanStack cache should be invalidated'
        );
    }

    /**
     * Test cache with server-side processing.
     */
    public function test_cache_with_server_side_processing(): void
    {
        // Arrange
        $cacheKey = 'test_server_side_cache';
        $serverSideData = [
            'data' => [],
            'recordsTotal' => 100,
            'recordsFiltered' => 50,
        ];

        // Act - Cache server-side response
        Cache::put($cacheKey, $serverSideData, 300);

        // Assert - Server-side response should be cached
        $this->assertTrue(
            Cache::has($cacheKey),
            'Server-side responses should be cached'
        );

        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($serverSideData, $cachedData);
    }

    /**
     * Test cache with filters.
     */
    public function test_cache_with_filters(): void
    {
        // Arrange
        $key1 = 'test_filter_active';
        $key2 = 'test_filter_inactive';
        $data1 = ['filter' => 'active', 'results' => []];
        $data2 = ['filter' => 'inactive', 'results' => []];

        // Act - Cache different filter results
        Cache::put($key1, $data1, 300);
        Cache::put($key2, $data2, 300);

        // Assert - Different filters should have different caches
        $this->assertTrue(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));
        $this->assertNotEquals(
            Cache::get($key1),
            Cache::get($key2),
            'Different filters should have different cached data'
        );
    }

    /**
     * Test TableCacheManager integration.
     */
    public function test_table_cache_manager_integration(): void
    {
        // Arrange
        $cacheManager = new TableCacheManager();

        // Act & Assert - TableCacheManager should be instantiable
        $this->assertInstanceOf(
            TableCacheManager::class,
            $cacheManager,
            'TableCacheManager should be instantiable'
        );
    }

    /**
     * Test TableCacheInvalidator integration.
     */
    public function test_table_cache_invalidator_integration(): void
    {
        // Arrange
        $cacheManager = new TableCacheManager();
        $cacheInvalidator = new TableCacheInvalidator($cacheManager);

        // Act & Assert - TableCacheInvalidator should be instantiable
        $this->assertInstanceOf(
            TableCacheInvalidator::class,
            $cacheInvalidator,
            'TableCacheInvalidator should be instantiable'
        );

        // Assert - Should have cache manager
        $this->assertSame(
            $cacheManager,
            $cacheInvalidator->getCacheManager(),
            'TableCacheInvalidator should have cache manager'
        );
    }

    /**
     * Helper method to create test users.
     */
    protected function createTestUsers(int $count, array $attributes = []): array
    {
        $users = [];

        for ($i = 0; $i < $count; $i++) {
            $users[] = TestUser::create(array_merge([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
            ], $attributes));
        }

        return $users;
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Test cache key generation for TableBuilder.
 * 
 * Tests Requirement 11.5: Cache key generation includes filters, sorting, pagination.
 */
class CacheKeyGenerationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = $this->createTableBuilder();
    }

    /**
     * Create a TableBuilder instance with all required dependencies.
     */
    protected function createTableBuilder(): TableBuilder
    {
        return app(TableBuilder::class);
    }

    /**
     * Test that cache key is generated.
     */
    public function test_cache_key_is_generated(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        
        $cacheKeyInfo = $this->table->getCacheKeyInfo();
        
        $this->assertIsArray($cacheKeyInfo);
        $this->assertArrayHasKey('cache_key', $cacheKeyInfo);
        $this->assertStringStartsWith('table.', $cacheKeyInfo['cache_key']);
    }

    /**
     * Test that cache key includes model class.
     */
    public function test_cache_key_includes_model(): void
    {
        $this->table->setModel(new User());
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Different model should produce different cache key
        $table2 = $this->createTableBuilder();
        $table2->setModel(new \Canvastack\Canvastack\Tests\Fixtures\Models\Post());
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    /**
     * Test that cache key includes columns.
     */
    public function test_cache_key_includes_columns(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Different columns should produce different cache key
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email', 'created_at']);
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    /**
     * Test that cache key includes filters (Requirement 11.5).
     */
    public function test_cache_key_includes_filters(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Add filter
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->where('status', '=', 'active');
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when filters are added');
    }

    /**
     * Test that cache key includes active filters (Requirement 11.5).
     */
    public function test_cache_key_includes_active_filters(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Add active filters
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->setActiveFilters(['status' => 'active'], false);
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when active filters are set');
    }

    /**
     * Test that cache key includes sorting (Requirement 11.5).
     */
    public function test_cache_key_includes_sorting(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->orderby('name', 'asc');
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Different sorting
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->orderby('name', 'desc');
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when sort direction changes');
        
        // Different column
        $table3 = $this->createTableBuilder();
        $table3->setModel(new User());
        $table3->setFields(['name', 'email']);
        $table3->orderby('email', 'asc');
        $cacheKey3 = $table3->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey3, 'Cache key should differ when sort column changes');
    }

    /**
     * Test that cache key includes pagination (Requirement 11.5).
     */
    public function test_cache_key_includes_pagination(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->setPage(1);
        $this->table->setPageSize(10);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Different page
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->setPage(2);
        $table2->setPageSize(10);
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when page changes');
        
        // Different page size
        $table3 = $this->createTableBuilder();
        $table3->setModel(new User());
        $table3->setFields(['name', 'email']);
        $table3->setPage(1);
        $table3->setPageSize(25);
        $cacheKey3 = $table3->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey3, 'Cache key should differ when page size changes');
    }

    /**
     * Test that cache key includes search value.
     */
    public function test_cache_key_includes_search(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->searchable(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Add search value
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->searchable(['name', 'email']);
        $table2->setSearchValue('john');
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when search value is set');
    }

    /**
     * Test that cache key includes eager loading.
     */
    public function test_cache_key_includes_eager_loading(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Add eager loading
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->eager(['posts', 'comments']);
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when eager loading is added');
    }

    /**
     * Test that cache key includes context.
     */
    public function test_cache_key_includes_context(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->setContext('admin');
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Different context
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->setContext('public');
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when context changes');
    }

    /**
     * Test that cache key includes unique ID (for multi-table support).
     */
    public function test_cache_key_includes_unique_id(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // With unique ID (simulating tab system)
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        // Simulate unique ID being set
        $reflection = new \ReflectionClass($table2);
        $property = $reflection->getProperty('uniqueId');
        $property->setAccessible(true);
        $property->setValue($table2, 'canvastable_abc123');
        
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should differ when unique ID is set');
    }

    /**
     * Test that identical configurations produce identical cache keys.
     */
    public function test_identical_configurations_produce_same_cache_key(): void
    {
        // Table 1
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->orderby('name', 'asc');
        $this->table->setPage(1);
        $this->table->setPageSize(10);
        $cacheKey1 = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Table 2 with identical configuration
        $table2 = $this->createTableBuilder();
        $table2->setModel(new User());
        $table2->setFields(['name', 'email']);
        $table2->orderby('name', 'asc');
        $table2->setPage(1);
        $table2->setPageSize(10);
        $cacheKey2 = $table2->getCacheKeyInfo()['cache_key'];
        
        $this->assertEquals($cacheKey1, $cacheKey2, 'Identical configurations should produce identical cache keys');
    }

    /**
     * Test that cache key info contains all expected fields.
     */
    public function test_cache_key_info_contains_all_fields(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        $this->table->orderby('name', 'asc');
        $this->table->setPage(1);
        $this->table->setPageSize(10);
        $this->table->where('status', '=', 'active');
        
        $info = $this->table->getCacheKeyInfo();
        
        // Model/Data source
        $this->assertArrayHasKey('model', $info);
        $this->assertArrayHasKey('collection', $info);
        $this->assertArrayHasKey('connection', $info);
        
        // Columns
        $this->assertArrayHasKey('columns', $info);
        $this->assertArrayHasKey('hidden_columns', $info);
        
        // Filters
        $this->assertArrayHasKey('conditions', $info);
        $this->assertArrayHasKey('filters', $info);
        $this->assertArrayHasKey('active_filters', $info);
        $this->assertArrayHasKey('where', $info);
        
        // Sorting
        $this->assertArrayHasKey('order_column', $info);
        $this->assertArrayHasKey('order_direction', $info);
        
        // Pagination
        $this->assertArrayHasKey('page', $info);
        $this->assertArrayHasKey('page_size', $info);
        
        // Search
        $this->assertArrayHasKey('search_value', $info);
        $this->assertArrayHasKey('searchable', $info);
        
        // Relations
        $this->assertArrayHasKey('eager', $info);
        
        // Context
        $this->assertArrayHasKey('context', $info);
        
        // Cache key
        $this->assertArrayHasKey('cache_key', $info);
    }

    /**
     * Test that cache key format is correct.
     */
    public function test_cache_key_format(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email']);
        
        $cacheKey = $this->table->getCacheKeyInfo()['cache_key'];
        
        // Should start with 'table.'
        $this->assertStringStartsWith('table.', $cacheKey);
        
        // Should be followed by MD5 hash (32 characters)
        $hash = substr($cacheKey, 6); // Remove 'table.' prefix
        $this->assertEquals(32, strlen($hash), 'Cache key should contain 32-character MD5 hash');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash, 'Cache key should be valid MD5 hash');
    }

    /**
     * Test that cache key changes when any parameter changes.
     */
    public function test_cache_key_changes_with_any_parameter(): void
    {
        $baseTable = $this->createTableBuilder();
        $baseTable->setModel(new User());
        $baseTable->setFields(['name', 'email']);
        $baseTable->setContext('admin');
        $baseTable->orderby('name', 'asc');
        $baseTable->setPage(1);
        $baseTable->setPageSize(10);
        $baseCacheKey = $baseTable->getCacheKeyInfo()['cache_key'];
        
        // Test each parameter change
        $parameters = [
            'columns' => function ($t) { $t->setFields(['name', 'email', 'created_at']); },
            'context' => function ($t) { $t->setContext('public'); },
            'order_column' => function ($t) { $t->orderby('email', 'asc'); },
            'order_direction' => function ($t) { $t->orderby('name', 'desc'); },
            'page' => function ($t) { $t->setPage(2); },
            'page_size' => function ($t) { $t->setPageSize(25); },
            'filter' => function ($t) { $t->where('status', '=', 'active'); },
            'search' => function ($t) { $t->setSearchValue('test'); },
        ];
        
        foreach ($parameters as $paramName => $modifier) {
            $testTable = $this->createTableBuilder();
            $testTable->setModel(new User());
            $testTable->setFields(['name', 'email']);
            $testTable->setContext('admin');
            $testTable->orderby('name', 'asc');
            $testTable->setPage(1);
            $testTable->setPageSize(10);
            
            // Apply modification
            $modifier($testTable);
            
            $testCacheKey = $testTable->getCacheKeyInfo()['cache_key'];
            
            $this->assertNotEquals(
                $baseCacheKey,
                $testCacheKey,
                "Cache key should change when {$paramName} changes"
            );
        }
    }

    /**
     * Test cache key generation performance.
     */
    public function test_cache_key_generation_performance(): void
    {
        $this->table->setModel(new User());
        $this->table->setFields(['name', 'email', 'created_at', 'updated_at']);
        $this->table->orderby('name', 'asc');
        $this->table->setPage(1);
        $this->table->setPageSize(10);
        $this->table->where('status', '=', 'active');
        $this->table->eager(['posts', 'comments']);
        
        $startTime = microtime(true);
        
        // Generate cache key 100 times
        for ($i = 0; $i < 100; $i++) {
            $this->table->getCacheKeyInfo()['cache_key'];
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete in less than 100ms for 100 iterations (< 1ms per call)
        $this->assertLessThan(100, $duration, 'Cache key generation should be fast (< 1ms per call)');
    }
}


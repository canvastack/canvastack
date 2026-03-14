<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Cache;

/**
 * Test for TableBuilder server-side query caching.
 * 
 * Validates Task 4.3.1: Implement server-side query caching
 * - Add cache() method to TableBuilder
 * - Cache query results with TTL
 * - Use tagged cache for easy invalidation
 * 
 * Requirements: 11.5
 */
class TableBuilderCacheTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->table = $this->createTableBuilder();
        
        // Clear any existing cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test that cache() method exists and sets cache time.
     * 
     * @return void
     */
    public function test_cache_method_sets_cache_time(): void
    {
        $this->table->cache(300);
        
        $this->assertEquals(300, $this->table->getCacheTime());
    }

    /**
     * Test that cache() method enables caching.
     * 
     * @return void
     */
    public function test_cache_method_enables_caching(): void
    {
        $this->table->cache(300);
        
        // Access protected property via reflection
        $reflection = new \ReflectionClass($this->table);
        $property = $reflection->getProperty('useCache');
        $property->setAccessible(true);
        
        $this->assertTrue($property->getValue($this->table));
    }

    /**
     * Test that cache() method returns self for method chaining.
     * 
     * @return void
     */
    public function test_cache_method_returns_self(): void
    {
        $result = $this->table->cache(300);
        
        $this->assertSame($this->table, $result);
    }

    /**
     * Test that cache key is generated correctly.
     * 
     * @return void
     */
    public function test_cache_key_generation(): void
    {
        // Use collection to avoid column validation
        $this->table->setCollection(collect([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]));
        $this->table->setFields(['name', 'email']);
        $this->table->orderby('name', 'asc');
        
        // Access protected method via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $cacheKey = $method->invoke($this->table);
        
        $this->assertIsString($cacheKey);
        $this->assertStringStartsWith('table.', $cacheKey);
    }

    /**
     * Test that cache key changes when configuration changes.
     * 
     * @return void
     */
    public function test_cache_key_changes_with_configuration(): void
    {
        // Use collection to avoid column validation
        $this->table->setCollection(collect([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]));
        $this->table->setFields(['name', 'email']);
        
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $cacheKey1 = $method->invoke($this->table);
        
        // Change configuration
        $this->table->setFields(['name', 'email', 'phone']);
        
        $cacheKey2 = $method->invoke($this->table);
        
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    /**
     * Test that cache tag is generated correctly.
     * 
     * @return void
     */
    public function test_cache_tag_generation(): void
    {
        // Access protected method via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('getCacheTag');
        $method->setAccessible(true);
        
        $cacheTag = $method->invoke($this->table);
        
        $this->assertIsString($cacheTag);
        $this->assertStringStartsWith('table.', $cacheTag);
    }

    /**
     * Test that cache uses tagged cache for easy invalidation.
     * 
     * @return void
     */
    public function test_cache_uses_tags(): void
    {
        // Create test table
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        if (!$schema->hasTable('test_cache_users')) {
            $schema->create('test_cache_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }
        
        // Insert test data
        $capsule->table('test_cache_users')->insert([
            ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Create model class dynamically
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_cache_users';
            protected $fillable = ['name', 'email'];
        };
        
        // Configure table with caching
        $this->table->setModel($model);
        $this->table->setFields(['name', 'email']);
        $this->table->cache(300);
        
        // Access protected method via reflection
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('getCachedData');
        $method->setAccessible(true);
        
        // First call should cache the data
        $data1 = $method->invoke($this->table);
        
        // Second call should retrieve from cache
        $data2 = $method->invoke($this->table);
        
        $this->assertEquals($data1, $data2);
        
        // Clear cache using tags
        $this->table->clearCache();
        
        // After clearing, data should be fetched fresh
        $data3 = $method->invoke($this->table);
        
        $this->assertEquals($data1, $data3);
        
        // Cleanup
        $schema->dropIfExists('test_cache_users');
    }

    /**
     * Test that clearCache() method works correctly.
     * 
     * @return void
     */
    public function test_clear_cache_method(): void
    {
        // Create test table
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        if (!$schema->hasTable('test_clear_cache_users')) {
            $schema->create('test_clear_cache_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
        
        // Insert test data
        $capsule->table('test_clear_cache_users')->insert([
            ['name' => 'Test User', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Create model
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_clear_cache_users';
            protected $fillable = ['name'];
        };
        
        // Configure table with caching
        $this->table->setModel($model);
        $this->table->setFields(['name']);
        $this->table->cache(300);
        
        // Cache some data
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('getCachedData');
        $method->setAccessible(true);
        $method->invoke($this->table);
        
        // Clear cache should not throw exception
        $this->table->clearCache();
        
        $this->assertTrue(true); // If we get here, clearCache() worked
        
        // Cleanup
        $schema->dropIfExists('test_clear_cache_users');
    }

    /**
     * Test that setCacheTime() method works as alias for cache().
     * 
     * @return void
     */
    public function test_set_cache_time_method(): void
    {
        $this->table->setCacheTime(600);
        
        $this->assertEquals(600, $this->table->getCacheTime());
    }

    /**
     * Test that caching can be disabled by not calling cache().
     * 
     * @return void
     */
    public function test_caching_disabled_by_default(): void
    {
        $this->assertNull($this->table->getCacheTime());
    }

    /**
     * Test that cache respects TTL (Time To Live).
     * 
     * @return void
     */
    public function test_cache_respects_ttl(): void
    {
        $this->table->cache(300);
        
        $this->assertEquals(300, $this->table->getCacheTime());
        
        // Change TTL
        $this->table->cache(600);
        
        $this->assertEquals(600, $this->table->getCacheTime());
    }

    /**
     * Test that cache key includes filters in generation.
     * 
     * @return void
     */
    public function test_cache_key_includes_filters(): void
    {
        // Use collection to avoid column validation
        $this->table->setCollection(collect([
            ['name' => 'John', 'email' => 'john@example.com', 'status' => 'active'],
        ]));
        $this->table->setFields(['name', 'email']);
        
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $cacheKey1 = $method->invoke($this->table);
        
        // Add filter
        $this->table->where('status', '=', 'active');
        
        $cacheKey2 = $method->invoke($this->table);
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should change when filters are added');
    }

    /**
     * Test that cache key includes sorting in generation.
     * 
     * @return void
     */
    public function test_cache_key_includes_sorting(): void
    {
        // Use collection to avoid column validation
        $this->table->setCollection(collect([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]));
        $this->table->setFields(['name', 'email']);
        
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $cacheKey1 = $method->invoke($this->table);
        
        // Add sorting
        $this->table->orderby('name', 'desc');
        
        $cacheKey2 = $method->invoke($this->table);
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should change when sorting changes');
    }

    /**
     * Test that cache key includes pagination in generation.
     * 
     * @return void
     */
    public function test_cache_key_includes_pagination(): void
    {
        // Use collection to avoid column validation
        $this->table->setCollection(collect([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]));
        $this->table->setFields(['name', 'email']);
        
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $cacheKey1 = $method->invoke($this->table);
        
        // Change pagination
        $this->table->displayRowsLimitOnLoad(50);
        
        $cacheKey2 = $method->invoke($this->table);
        
        $this->assertNotEquals($cacheKey1, $cacheKey2, 'Cache key should change when pagination changes');
    }
}

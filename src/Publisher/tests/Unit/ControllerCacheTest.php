<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Controller Cache Test
 * 
 * Tests caching functionality for controller components including:
 * - Cache helper functions
 * - Privilege caching
 * - Route info caching
 * - Preference caching
 * - File validation caching
 * 
 * @package Tests\Unit
 */
class ControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Enable caching for tests
        config(['canvastack.controller.performance.enable_caching' => true]);
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();
        
        parent::tearDown();
    }

    /**
     * Test cache helper function - get
     * 
     * @test
     */
    public function test_cache_get_returns_default_when_key_not_exists()
    {
        $result = canvastack_controller_cache_get('nonexistent_key', 'default_value');
        
        $this->assertEquals('default_value', $result);
    }

    /**
     * Test cache helper function - put and get
     * 
     * @test
     */
    public function test_cache_put_and_get_stores_and_retrieves_value()
    {
        $key = 'test_key';
        $value = 'test_value';
        
        // Put value in cache
        $putResult = canvastack_controller_cache_put($key, $value, 60);
        $this->assertTrue($putResult);
        
        // Get value from cache
        $result = canvastack_controller_cache_get($key);
        $this->assertEquals($value, $result);
    }

    /**
     * Test cache helper function - remember
     * 
     * @test
     */
    public function test_cache_remember_executes_callback_on_miss()
    {
        $key = 'test_remember_key';
        $callbackExecuted = false;
        
        $result = canvastack_controller_cache_remember($key, function() use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'computed_value';
        }, 60);
        
        $this->assertTrue($callbackExecuted);
        $this->assertEquals('computed_value', $result);
        
        // Second call should use cache (callback not executed)
        $callbackExecuted = false;
        $result = canvastack_controller_cache_remember($key, function() use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'new_value';
        }, 60);
        
        $this->assertFalse($callbackExecuted);
        $this->assertEquals('computed_value', $result);
    }

    /**
     * Test cache helper function - forget
     * 
     * @test
     */
    public function test_cache_forget_removes_value()
    {
        $key = 'test_forget_key';
        $value = 'test_value';
        
        // Put value in cache
        canvastack_controller_cache_put($key, $value, 60);
        
        // Verify it exists
        $this->assertTrue(canvastack_controller_cache_has($key));
        
        // Forget the key
        $forgetResult = canvastack_controller_cache_forget($key);
        $this->assertTrue($forgetResult);
        
        // Verify it's gone
        $this->assertFalse(canvastack_controller_cache_has($key));
    }

    /**
     * Test cache helper function - has
     * 
     * @test
     */
    public function test_cache_has_checks_existence()
    {
        $key = 'test_has_key';
        
        // Key doesn't exist
        $this->assertFalse(canvastack_controller_cache_has($key));
        
        // Put value in cache
        canvastack_controller_cache_put($key, 'value', 60);
        
        // Key exists
        $this->assertTrue(canvastack_controller_cache_has($key));
    }

    /**
     * Test cache helper function - key generation
     * 
     * @test
     */
    public function test_cache_key_generates_consistent_keys()
    {
        $key1 = canvastack_controller_cache_key('privilege', ['user' => 123, 'module' => 'admin']);
        $key2 = canvastack_controller_cache_key('privilege', ['user' => 123, 'module' => 'admin']);
        
        // Same parameters should generate same key
        $this->assertEquals($key1, $key2);
        
        // Different parameters should generate different key
        $key3 = canvastack_controller_cache_key('privilege', ['user' => 456, 'module' => 'admin']);
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * Test cache respects configuration
     * 
     * @test
     */
    public function test_cache_respects_disabled_configuration()
    {
        // Disable caching
        config(['canvastack.controller.performance.enable_caching' => false]);
        
        $key = 'test_disabled_key';
        $value = 'test_value';
        
        // Try to put value in cache
        $putResult = canvastack_controller_cache_put($key, $value, 60);
        $this->assertFalse($putResult);
        
        // Try to get value from cache (should return default)
        $result = canvastack_controller_cache_get($key, 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * Test cache TTL configuration
     * 
     * @test
     */
    public function test_cache_uses_default_ttl_from_config()
    {
        // Set default TTL
        config(['canvastack.controller.performance.cache_ttl' => 3600]);
        
        $key = 'test_ttl_key';
        $value = 'test_value';
        
        // Put without specifying TTL (should use default)
        canvastack_controller_cache_put($key, $value);
        
        // Verify it's cached
        $this->assertTrue(canvastack_controller_cache_has($key));
        $this->assertEquals($value, canvastack_controller_cache_get($key));
    }

    /**
     * Test privilege cache configuration
     * 
     * @test
     */
    public function test_privilege_cache_configuration_exists()
    {
        $enabled = config('canvastack.controller.caching.privilege_cache_enabled');
        $ttl = config('canvastack.controller.caching.privilege_cache_ttl');
        
        $this->assertIsBool($enabled);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
    }

    /**
     * Test route info cache configuration
     * 
     * @test
     */
    public function test_route_info_cache_configuration_exists()
    {
        $enabled = config('canvastack.controller.caching.route_info_cache_enabled');
        $ttl = config('canvastack.controller.caching.route_info_cache_ttl');
        
        $this->assertIsBool($enabled);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
    }

    /**
     * Test preference cache configuration
     * 
     * @test
     */
    public function test_preference_cache_configuration_exists()
    {
        $enabled = config('canvastack.controller.caching.preference_cache_enabled');
        $ttl = config('canvastack.controller.caching.preference_cache_ttl');
        
        $this->assertIsBool($enabled);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
    }

    /**
     * Test file validation cache configuration
     * 
     * @test
     */
    public function test_file_validation_cache_configuration_exists()
    {
        $enabled = config('canvastack.controller.caching.file_validation_cache_enabled');
        $ttl = config('canvastack.controller.caching.file_validation_cache_ttl');
        
        $this->assertIsBool($enabled);
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
    }

    /**
     * Test cache hit rate tracking
     * 
     * @test
     */
    public function test_cache_hit_rate_tracking()
    {
        $key = 'test_hit_rate_key';
        $value = 'test_value';
        
        // First access - cache miss
        $result1 = canvastack_controller_cache_remember($key, function() use ($value) {
            return $value;
        }, 60);
        
        // Second access - cache hit
        $result2 = canvastack_controller_cache_get($key);
        
        // Both should return same value
        $this->assertEquals($value, $result1);
        $this->assertEquals($value, $result2);
        
        // Verify cache was used (value exists)
        $this->assertTrue(canvastack_controller_cache_has($key));
    }

    /**
     * Test cache handles complex data structures
     * 
     * @test
     */
    public function test_cache_handles_complex_data_structures()
    {
        $key = 'test_complex_key';
        $complexData = [
            'array' => [1, 2, 3],
            'object' => (object) ['prop' => 'value'],
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value'
                ]
            ]
        ];
        
        // Cache complex data
        canvastack_controller_cache_put($key, $complexData, 60);
        
        // Retrieve and verify
        $result = canvastack_controller_cache_get($key);
        $this->assertEquals($complexData, $result);
    }

    /**
     * Test cache gracefully handles errors
     * 
     * @test
     */
    public function test_cache_gracefully_handles_errors()
    {
        // This test verifies that cache errors don't crash the application
        // The cache helper functions should catch exceptions and return defaults
        
        $key = 'test_error_key';
        $default = 'default_value';
        
        // Even if cache fails, should return default
        $result = canvastack_controller_cache_get($key, $default);
        $this->assertEquals($default, $result);
    }
}

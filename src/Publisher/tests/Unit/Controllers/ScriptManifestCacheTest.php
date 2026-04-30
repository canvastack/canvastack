<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Test script manifest caching functionality
 * 
 * This test suite verifies that:
 * - Script manifests are cached when caching is enabled
 * - Cache keys are generated correctly
 * - Cache TTL is respected
 * - Fallback works when cache fails
 * - Configuration controls caching behavior
 */
class ScriptManifestCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Enable caching by default
        Config::set('canvastack.controller.script_management.cache_manifests', true);
        Config::set('canvastack.controller.script_management.manifest_cache_ttl', 3600);
    }

    /**
     * Test that script manifests are cached when caching is enabled
     */
    public function test_script_manifests_are_cached_when_enabled()
    {
        // Create a test controller
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        // Add some scripts
        $controller->js('vendor/jquery/jquery.min.js');
        $controller->js('vendor/bootstrap/bootstrap.min.js');
        $controller->css('vendor/bootstrap/bootstrap.min.css');
        
        // Access the scripts property from the Scripts trait using reflection
        $reflection = new \ReflectionClass($controller);
        
        // Get parent class that uses the Scripts trait
        $parent = $reflection->getParentClass();
        while ($parent && !$parent->hasProperty('scripts')) {
            $parent = $parent->getParentClass();
        }
        
        if (!$parent) {
            $this->markTestSkipped('Scripts property not found in controller hierarchy');
            return;
        }
        
        $scriptsProperty = $parent->getProperty('scripts');
        $scriptsProperty->setAccessible(true);
        $scripts = $scriptsProperty->getValue($controller);
        
        // Generate cache key for JS scripts
        $jsCacheKey = 'canvastack_script_manifest_js_' . md5(json_encode($scripts['js'] ?? []));
        
        // Verify cache is empty initially
        $this->assertFalse(Cache::has($jsCacheKey));
        
        // Call getScriptManifest via reflection
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        $manifest = $method->invoke($controller, 'js', $scripts['js'] ?? []);
        
        // Verify manifest was cached
        $this->assertTrue(Cache::has($jsCacheKey));
        
        // Verify cached value matches generated manifest
        $cachedManifest = Cache::get($jsCacheKey);
        $this->assertEquals($manifest, $cachedManifest);
    }

    /**
     * Test that cached manifests are retrieved on subsequent calls
     */
    public function test_cached_manifests_are_retrieved_on_subsequent_calls()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = [
            'vendor/jquery/jquery.min.js',
            'vendor/bootstrap/bootstrap.min.js'
        ];
        
        // First call - generates and caches manifest
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        $manifest1 = $method->invoke($controller, 'js', $scripts);
        
        // Manually modify cache to verify it's being used
        $cacheKey = 'canvastack_script_manifest_js_' . md5(json_encode($scripts));
        $modifiedManifest = ['modified' => true];
        Cache::put($cacheKey, $modifiedManifest, 3600);
        
        // Second call - should retrieve from cache
        $manifest2 = $method->invoke($controller, 'js', $scripts);
        
        // Verify cached value was returned
        $this->assertEquals($modifiedManifest, $manifest2);
        $this->assertNotEquals($manifest1, $manifest2);
    }

    /**
     * Test that caching can be disabled via configuration
     */
    public function test_caching_can_be_disabled_via_configuration()
    {
        // Disable caching
        Config::set('canvastack.controller.script_management.cache_manifests', false);
        
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = [
            'vendor/jquery/jquery.min.js'
        ];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Call getScriptManifest
        $manifest = $method->invoke($controller, 'js', $scripts);
        
        // Verify nothing was cached
        $cacheKey = 'canvastack_script_manifest_js_' . md5(json_encode($scripts));
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test that cache TTL is respected
     */
    public function test_cache_ttl_is_respected()
    {
        // Set short TTL for testing
        Config::set('canvastack.controller.script_management.manifest_cache_ttl', 1);
        
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = ['vendor/jquery/jquery.min.js'];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Generate and cache manifest
        $method->invoke($controller, 'js', $scripts);
        
        $cacheKey = 'canvastack_script_manifest_js_' . md5(json_encode($scripts));
        
        // Verify cache exists
        $this->assertTrue(Cache::has($cacheKey));
        
        // Wait for cache to expire
        sleep(2);
        
        // Verify cache expired
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test that different script types have different cache keys
     */
    public function test_different_script_types_have_different_cache_keys()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = ['vendor/test.js'];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Generate manifests for both JS and CSS
        $method->invoke($controller, 'js', $scripts);
        $method->invoke($controller, 'css', $scripts);
        
        // Verify both cache keys exist
        $jsCacheKey = 'canvastack_script_manifest_js_' . md5(json_encode($scripts));
        $cssCacheKey = 'canvastack_script_manifest_css_' . md5(json_encode($scripts));
        
        $this->assertTrue(Cache::has($jsCacheKey));
        $this->assertTrue(Cache::has($cssCacheKey));
        
        // Verify they have different values
        $jsManifest = Cache::get($jsCacheKey);
        $cssManifest = Cache::get($cssCacheKey);
        
        $this->assertNotEquals($jsManifest, $cssManifest);
    }

    /**
     * Test that different script arrays have different cache keys
     */
    public function test_different_script_arrays_have_different_cache_keys()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts1 = ['vendor/jquery.js'];
        $scripts2 = ['vendor/bootstrap.js'];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Generate manifests for different scripts
        $method->invoke($controller, 'js', $scripts1);
        $method->invoke($controller, 'js', $scripts2);
        
        // Verify both cache keys exist
        $cacheKey1 = 'canvastack_script_manifest_js_' . md5(json_encode($scripts1));
        $cacheKey2 = 'canvastack_script_manifest_js_' . md5(json_encode($scripts2));
        
        $this->assertTrue(Cache::has($cacheKey1));
        $this->assertTrue(Cache::has($cacheKey2));
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    /**
     * Test that cache errors are handled gracefully
     */
    public function test_cache_errors_are_handled_gracefully()
    {
        // Enable performance logging
        Config::set('canvastack.controller.logging.log_performance_issues', true);
        
        // Mock cache to throw exception
        Cache::shouldReceive('has')
            ->andThrow(new \Exception('Cache connection failed'));
        
        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Cache connection failed'));
        
        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache connection failed'));
        
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = ['vendor/jquery.js'];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Should not throw exception - should fallback to generation
        $manifest = $method->invoke($controller, 'js', $scripts);
        
        // Verify manifest was still generated
        $this->assertIsArray($manifest);
        $this->assertNotEmpty($manifest);
    }

    /**
     * Test that manifest includes correct metadata
     */
    public function test_manifest_includes_correct_metadata()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = ['vendor/jquery/jquery.min.js'];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        $manifest = $method->invoke($controller, 'js', $scripts);
        
        // Verify manifest structure
        $this->assertIsArray($manifest);
        
        foreach ($manifest as $script => $metadata) {
            $this->assertArrayHasKey('path', $metadata);
            $this->assertArrayHasKey('position', $metadata);
            $this->assertArrayHasKey('exists', $metadata);
            $this->assertArrayHasKey('size', $metadata);
            $this->assertArrayHasKey('type', $metadata);
            
            $this->assertEquals('js', $metadata['type']);
        }
    }

    /**
     * Test that empty script arrays are handled correctly
     */
    public function test_empty_script_arrays_are_handled_correctly()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $scripts = [];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        $manifest = $method->invoke($controller, 'js', $scripts);
        
        // Verify empty manifest is returned
        $this->assertIsArray($manifest);
        $this->assertEmpty($manifest);
    }

    /**
     * Test that cache warming works correctly
     */
    public function test_cache_warming_works_correctly()
    {
        $controller = new \App\Http\Controllers\Admin\System\DashboardController();
        
        $commonScripts = [
            'vendor/jquery/jquery.min.js',
            'vendor/bootstrap/bootstrap.min.js',
            'vendor/datatables/datatables.min.js'
        ];
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getScriptManifest');
        $method->setAccessible(true);
        
        // Warm cache by generating manifests
        foreach ($commonScripts as $script) {
            $method->invoke($controller, 'js', [$script]);
        }
        
        // Verify all scripts are cached
        foreach ($commonScripts as $script) {
            $cacheKey = 'canvastack_script_manifest_js_' . md5(json_encode([$script]));
            $this->assertTrue(Cache::has($cacheKey), "Cache key not found for: $script");
        }
    }
}

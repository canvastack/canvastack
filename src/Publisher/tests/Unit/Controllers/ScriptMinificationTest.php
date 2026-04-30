<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Script Minification Test
 * 
 * Tests untuk memverifikasi bahwa script minification bekerja dengan benar.
 * Test ini akan mengecek:
 * - Minifikasi JavaScript
 * - Minifikasi CSS
 * - Preservasi important comments
 * - Caching
 * - Error handling
 */
class ScriptMinificationTest extends TestCase
{
    /**
     * Mock trait untuk testing
     */
    use ScriptMinificationTestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache sebelum setiap test
        Cache::flush();
        
        // Enable minification untuk testing
        Config::set('canvastack.controller.script_management.enable_minification', true);
        Config::set('canvastack.controller.script_management.minify_inline_scripts', true);
        Config::set('canvastack.controller.script_management.minification_cache_enabled', true);
        Config::set('canvastack.controller.script_management.preserve_important_comments', true);
    }
    
    /**
     * Test minifikasi JavaScript sederhana
     */
    public function test_minify_simple_javascript()
    {
        $input = '
            // This is a comment
            function hello() {
                console.log("Hello World");  // Inline comment
            }
        ';
        
        $expected = 'function hello(){console.log("Hello World");}';
        
        $result = $this->minifyScript($input, 'js');
        
        $this->assertEquals($expected, $result);
        
        echo "\n✓ Test Minifikasi JavaScript Sederhana:\n";
        echo "  Input  : " . str_replace("\n", " ", trim($input)) . "\n";
        echo "  Output : {$result}\n";
        echo "  Size   : " . strlen($input) . " bytes → " . strlen($result) . " bytes\n";
        echo "  Saved  : " . (strlen($input) - strlen($result)) . " bytes (" . 
             round((1 - strlen($result) / strlen($input)) * 100, 1) . "%)\n";
    }
    
    /**
     * Test minifikasi CSS sederhana
     */
    public function test_minify_simple_css()
    {
        $input = '
            /* Main styles */
            .container {
                padding: 10px;
                margin: 0;
            }
        ';
        
        $expected = '.container{padding:10px;margin:0;}';
        
        $result = $this->minifyScript($input, 'css');
        
        $this->assertEquals($expected, $result);
        
        echo "\n✓ Test Minifikasi CSS Sederhana:\n";
        echo "  Input  : " . str_replace("\n", " ", trim($input)) . "\n";
        echo "  Output : {$result}\n";
        echo "  Size   : " . strlen($input) . " bytes → " . strlen($result) . " bytes\n";
        echo "  Saved  : " . (strlen($input) - strlen($result)) . " bytes (" . 
             round((1 - strlen($result) / strlen($input)) * 100, 1) . "%)\n";
    }
    
    /**
     * Test preservasi important comments
     */
    public function test_preserve_important_comments()
    {
        $input = '
            /*! Copyright 2024 Canvastack */
            function app() {
                // Regular comment
                console.log("App");
            }
        ';
        
        $result = $this->minifyScript($input, 'js');
        
        // Important comment harus tetap ada
        $this->assertStringContainsString('/*! Copyright 2024 Canvastack */', $result);
        // Regular comment harus hilang
        $this->assertStringNotContainsString('// Regular comment', $result);
        
        echo "\n✓ Test Preservasi Important Comments:\n";
        echo "  Input  : " . str_replace("\n", " ", trim($input)) . "\n";
        echo "  Output : {$result}\n";
        echo "  ✓ Important comment preserved\n";
        echo "  ✓ Regular comment removed\n";
    }
    
    /**
     * Test minifikasi JavaScript kompleks
     */
    public function test_minify_complex_javascript()
    {
        $input = '
            // Initialize application
            $(document).ready(function() {
                // Setup event handlers
                $("#submit-btn").click(function(e) {
                    e.preventDefault();
                    
                    // Validate form
                    if (validateForm()) {
                        // Submit via AJAX
                        $.ajax({
                            url: "/api/submit",
                            method: "POST",
                            data: $("#form").serialize(),
                            success: function(response) {
                                console.log("Success:", response);
                            },
                            error: function(xhr, status, error) {
                                console.error("Error:", error);
                            }
                        });
                    }
                });
            });
        ';
        
        $result = $this->minifyScript($input, 'js');
        
        // Verifikasi tidak ada comment
        $this->assertStringNotContainsString('//', $result);
        // Verifikasi fungsi masih ada
        $this->assertStringContainsString('$(document).ready', $result);
        $this->assertStringContainsString('validateForm()', $result);
        $this->assertStringContainsString('$.ajax', $result);
        
        echo "\n✓ Test Minifikasi JavaScript Kompleks:\n";
        echo "  Input  : " . strlen($input) . " bytes\n";
        echo "  Output : " . strlen($result) . " bytes\n";
        echo "  Saved  : " . (strlen($input) - strlen($result)) . " bytes (" . 
             round((1 - strlen($result) / strlen($input)) * 100, 1) . "%)\n";
        echo "  Preview: " . substr($result, 0, 100) . "...\n";
    }
    
    /**
     * Test minifikasi CSS kompleks
     */
    public function test_minify_complex_css()
    {
        $input = '
            /* Layout styles */
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            /* Typography */
            h1, h2, h3 {
                font-family: "Arial", sans-serif;
                color: #333;
            }
            
            /* Buttons */
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #007bff;
                color: white;
                border-radius: 4px;
            }
            
            .btn:hover {
                background-color: #0056b3;
            }
        ';
        
        $result = $this->minifyScript($input, 'css');
        
        // Verifikasi tidak ada comment
        $this->assertStringNotContainsString('/*', $result);
        // Verifikasi selector masih ada
        $this->assertStringContainsString('.container', $result);
        $this->assertStringContainsString('.btn', $result);
        $this->assertStringContainsString(':hover', $result);
        
        echo "\n✓ Test Minifikasi CSS Kompleks:\n";
        echo "  Input  : " . strlen($input) . " bytes\n";
        echo "  Output : " . strlen($result) . " bytes\n";
        echo "  Saved  : " . (strlen($input) - strlen($result)) . " bytes (" . 
             round((1 - strlen($result) / strlen($input)) * 100, 1) . "%)\n";
        echo "  Preview: " . substr($result, 0, 100) . "...\n";
    }
    
    /**
     * Test caching minifikasi
     */
    public function test_minification_caching()
    {
        $input = '
            function test() {
                console.log("Test");
            }
        ';
        
        // First call - should minify and cache
        $result1 = $this->minifyScript($input, 'js');
        
        // Second call - should get from cache
        $result2 = $this->minifyScript($input, 'js');
        
        $this->assertEquals($result1, $result2);
        
        // Verify cache exists
        $cacheKey = 'canvastack_minified_js_' . md5($input);
        $this->assertTrue(Cache::has($cacheKey));
        
        echo "\n✓ Test Caching Minifikasi:\n";
        echo "  ✓ First call minified and cached\n";
        echo "  ✓ Second call retrieved from cache\n";
        echo "  ✓ Results are identical\n";
        echo "  Cache key: {$cacheKey}\n";
    }
    
    /**
     * Test minifikasi dengan minification disabled
     */
    public function test_minification_disabled()
    {
        Config::set('canvastack.controller.script_management.enable_minification', false);
        
        $input = '
            function test() {
                console.log("Test");
            }
        ';
        
        $result = $this->minifyScript($input, 'js');
        
        // Harus return original content
        $this->assertEquals($input, $result);
        
        echo "\n✓ Test Minifikasi Disabled:\n";
        echo "  ✓ Original content returned unchanged\n";
    }
    
    /**
     * Test error handling
     */
    public function test_minification_error_handling()
    {
        Config::set('canvastack.controller.script_management.handle_minification_errors_gracefully', true);
        
        // Empty input
        $result = $this->minifyScript('', 'js');
        $this->assertEquals('', $result);
        
        // Non-string input
        $result = $this->minifyScript(null, 'js');
        $this->assertEquals(null, $result);
        
        echo "\n✓ Test Error Handling:\n";
        echo "  ✓ Empty input handled gracefully\n";
        echo "  ✓ Invalid input handled gracefully\n";
    }
    
    /**
     * Test preservasi string literals
     */
    public function test_preserve_string_literals()
    {
        $input = '
            var message = "Hello // World";
            var url = "https://example.com";
            console.log("Test /* comment */ inside string");
        ';
        
        $result = $this->minifyScript($input, 'js');
        
        // String literals harus tetap utuh
        $this->assertStringContainsString('"Hello // World"', $result);
        $this->assertStringContainsString('"https://example.com"', $result);
        $this->assertStringContainsString('"Test /* comment */ inside string"', $result);
        
        echo "\n✓ Test Preservasi String Literals:\n";
        echo "  ✓ Strings with // preserved\n";
        echo "  ✓ URLs preserved\n";
        echo "  ✓ Strings with /* */ preserved\n";
        echo "  Output: {$result}\n";
    }
    
    /**
     * Helper method untuk memanggil minifyScript
     * (Karena method private, kita perlu reflection atau buat public wrapper)
     */
    private function minifyScript($content, $type)
    {
        // Create mock object dengan Scripts trait
        $mock = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct()
            {
                // Mock template property
                $this->template = new class {
                    public function js($script, $position, $asCode) { return true; }
                    public function css($script, $position) { return true; }
                };
            }
            
            // Expose private method untuk testing
            public function testMinifyScript($content, $type)
            {
                return $this->minifyScript($content, $type);
            }
        };
        
        return $mock->testMinifyScript($content, $type);
    }
}

/**
 * Trait untuk testing (jika diperlukan)
 */
trait ScriptMinificationTestTrait
{
    // Helper methods bisa ditambahkan di sini
}

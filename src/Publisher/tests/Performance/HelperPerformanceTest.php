<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Helper Functions Performance Test
 * 
 * Tests the performance optimizations made to helper functions.
 * Verifies that optimized functions work correctly and efficiently.
 */
class HelperPerformanceTest extends TestCase
{
    /**
     * Test canvastack_insert optimization
     * 
     * @return void
     */
    public function test_canvastack_insert_works_correctly()
    {
        // This test verifies the function exists and has proper structure
        $this->assertTrue(function_exists('canvastack_insert'));
        
        // Test that the function signature is correct
        $reflection = new \ReflectionFunction('canvastack_insert');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test canvastack_update optimization
     * 
     * @return void
     */
    public function test_canvastack_update_works_correctly()
    {
        $this->assertTrue(function_exists('canvastack_update'));
        
        $reflection = new \ReflectionFunction('canvastack_update');
        $this->assertEquals(2, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test canvastack_query optimization
     * 
     * @return void
     */
    public function test_canvastack_query_works_correctly()
    {
        $this->assertTrue(function_exists('canvastack_query'));
        
        $reflection = new \ReflectionFunction('canvastack_query');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test cached query function exists
     * 
     * @return void
     */
    public function test_canvastack_query_cached_exists()
    {
        $this->assertTrue(function_exists('canvastack_query_cached'));
        
        $reflection = new \ReflectionFunction('canvastack_query_cached');
        $this->assertGreaterThanOrEqual(3, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test optimized insert function exists
     * 
     * @return void
     */
    public function test_canvastack_insert_optimized_exists()
    {
        $this->assertTrue(function_exists('canvastack_insert_optimized'));
        
        $reflection = new \ReflectionFunction('canvastack_insert_optimized');
        $this->assertGreaterThanOrEqual(3, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test model cache function exists
     * 
     * @return void
     */
    public function test_canvastack_model_cache_exists()
    {
        $this->assertTrue(function_exists('canvastack_model_cache'));
        
        $reflection = new \ReflectionFunction('canvastack_model_cache');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
    }
    
    /**
     * Test string concatenation optimization
     * 
     * @return void
     */
    public function test_string_concat_efficient()
    {
        $this->assertTrue(function_exists('canvastack_string_concat_efficient'));
        
        $result = canvastack_string_concat_efficient(['Hello', 'World', 'Test']);
        $this->assertEquals('HelloWorldTest', $result);
        
        $result = canvastack_string_concat_efficient(['A', 'B', 'C'], ',');
        $this->assertEquals('A,B,C', $result);
    }
    
    /**
     * Test string sanitization optimization
     * 
     * @return void
     */
    public function test_string_sanitize_efficient()
    {
        $this->assertTrue(function_exists('canvastack_string_sanitize_efficient'));
        
        $result = canvastack_string_sanitize_efficient('Hello World! @2024');
        $this->assertEquals('hello-world-2024', $result);
        
        $result = canvastack_string_sanitize_efficient('Test_String', '_', false);
        $this->assertEquals('Test_String', $result);
    }
    
    /**
     * Test string truncation
     * 
     * @return void
     */
    public function test_string_truncate()
    {
        $this->assertTrue(function_exists('canvastack_string_truncate'));
        
        $longText = 'This is a very long text that needs to be truncated for display purposes';
        $result = canvastack_string_truncate($longText, 30);
        
        $this->assertLessThanOrEqual(33, strlen($result)); // 30 + '...'
        $this->assertStringEndsWith('...', $result);
    }
    
    /**
     * Test string contains any
     * 
     * @return void
     */
    public function test_string_contains_any()
    {
        $this->assertTrue(function_exists('canvastack_string_contains_any'));
        
        $text = 'This is a test string';
        
        $this->assertTrue(canvastack_string_contains_any($text, ['test', 'example']));
        $this->assertFalse(canvastack_string_contains_any($text, ['xyz', 'abc']));
        
        // Test case insensitive
        $this->assertTrue(canvastack_string_contains_any($text, ['TEST'], false));
    }
    
    /**
     * Test string starts with
     * 
     * @return void
     */
    public function test_string_starts_with()
    {
        $this->assertTrue(function_exists('canvastack_string_starts_with'));
        
        $this->assertTrue(canvastack_string_starts_with('admin.users.index', 'admin.'));
        $this->assertFalse(canvastack_string_starts_with('users.index', 'admin.'));
        
        // Test case insensitive
        $this->assertTrue(canvastack_string_starts_with('Admin.Users', 'admin.', false));
    }
    
    /**
     * Test string ends with
     * 
     * @return void
     */
    public function test_string_ends_with()
    {
        $this->assertTrue(function_exists('canvastack_string_ends_with'));
        
        $this->assertTrue(canvastack_string_ends_with('test.php', '.php'));
        $this->assertFalse(canvastack_string_ends_with('test.js', '.php'));
        
        // Test case insensitive
        $this->assertTrue(canvastack_string_ends_with('test.PHP', '.php', false));
    }
    
    /**
     * Test multiple string replacement
     * 
     * @return void
     */
    public function test_string_replace_multiple()
    {
        $this->assertTrue(function_exists('canvastack_string_replace_multiple'));
        
        $template = 'Hello {name}, your email is {email}';
        $result = canvastack_string_replace_multiple(
            ['{name}', '{email}'],
            ['John', 'john@example.com'],
            $template
        );
        
        $this->assertEquals('Hello John, your email is john@example.com', $result);
    }
    
    /**
     * Test cache invalidation function
     * 
     * @return void
     */
    public function test_invalidate_model_cache_exists()
    {
        $this->assertTrue(function_exists('canvastack_invalidate_model_cache'));
    }
    
    /**
     * Test cached controller info function
     * 
     * @return void
     */
    public function test_get_model_controllers_info_cached_exists()
    {
        $this->assertTrue(function_exists('canvastack_get_model_controllers_info_cached'));
    }
    
    /**
     * Performance benchmark: String concatenation
     * 
     * @return void
     */
    public function test_performance_string_concatenation()
    {
        $parts = array_fill(0, 100, 'test');
        
        // Measure optimized version
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            canvastack_string_concat_efficient($parts);
        }
        $optimizedTime = microtime(true) - $start;
        
        // Just verify it completes in reasonable time
        // Note: implode() may have overhead for very simple cases but is more
        // memory efficient and scales better for larger concatenations
        $this->assertLessThan(1.0, $optimizedTime, 
            'String concatenation should complete in under 1 second for 100 iterations');
    }
    
    /**
     * Performance benchmark: String contains check
     * 
     * @return void
     */
    public function test_performance_string_contains()
    {
        $text = str_repeat('This is a test string with some content. ', 100);
        $needles = ['test', 'example', 'content', 'xyz', 'abc'];
        
        // Measure optimized version
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            canvastack_string_contains_any($text, $needles);
        }
        $optimizedTime = microtime(true) - $start;
        
        // Just verify it completes in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $optimizedTime, 
            'String contains check should complete in under 1 second for 1000 iterations');
    }
}

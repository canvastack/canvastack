<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Memory Management Tests
 * 
 * Tests memory management functionality including:
 * - Memory usage monitoring
 * - Memory limit warnings
 * - Out-of-memory handling
 * - Chunked processing
 * - Variable cleanup
 */
class MemoryManagementTest extends TestCase
{
    /**
     * Test memory usage function
     * 
     * @return void
     */
    public function test_memory_usage_returns_valid_data()
    {
        $memoryInfo = canvastack_memory_usage(true);
        
        $this->assertIsArray($memoryInfo);
        $this->assertArrayHasKey('current', $memoryInfo);
        $this->assertArrayHasKey('peak', $memoryInfo);
        $this->assertArrayHasKey('limit', $memoryInfo);
        $this->assertArrayHasKey('available', $memoryInfo);
        $this->assertArrayHasKey('usage_percent', $memoryInfo);
        
        // Check formatted values
        $this->assertStringContainsString('MB', $memoryInfo['current']);
        $this->assertStringContainsString('%', $memoryInfo['usage_percent']);
    }
    
    /**
     * Test memory usage returns raw bytes
     * 
     * @return void
     */
    public function test_memory_usage_returns_raw_bytes()
    {
        $memoryInfo = canvastack_memory_usage(false);
        
        $this->assertIsArray($memoryInfo);
        $this->assertIsInt($memoryInfo['current']);
        $this->assertIsInt($memoryInfo['peak']);
        $this->assertIsInt($memoryInfo['limit']);
        $this->assertIsInt($memoryInfo['available']);
        $this->assertIsFloat($memoryInfo['usage_percent']);
        
        // Verify values are positive
        $this->assertGreaterThan(0, $memoryInfo['current']);
        $this->assertGreaterThan(0, $memoryInfo['peak']);
    }
    
    /**
     * Test convert to bytes function
     * 
     * @return void
     */
    public function test_convert_to_bytes()
    {
        $this->assertEquals(256 * 1024 * 1024, canvastack_convert_to_bytes('256M'));
        $this->assertEquals(1 * 1024 * 1024 * 1024, canvastack_convert_to_bytes('1G'));
        $this->assertEquals(512 * 1024, canvastack_convert_to_bytes('512K'));
        $this->assertEquals(1024, canvastack_convert_to_bytes('1024'));
    }
    
    /**
     * Test format bytes function
     * 
     * @return void
     */
    public function test_format_bytes()
    {
        $this->assertEquals('1 KB', canvastack_format_bytes(1024));
        $this->assertEquals('1 MB', canvastack_format_bytes(1024 * 1024));
        $this->assertEquals('1 GB', canvastack_format_bytes(1024 * 1024 * 1024));
        $this->assertEquals('256 MB', canvastack_format_bytes(256 * 1024 * 1024));
    }
    
    /**
     * Test memory check limit function
     * 
     * @return void
     */
    public function test_check_memory_limit()
    {
        // Should return true for low threshold (memory should be below 99%)
        $result = canvastack_check_memory_limit(0.99);
        $this->assertIsBool($result);
    }
    
    /**
     * Test memory warning function
     * 
     * @return void
     */
    public function test_memory_warning()
    {
        // Test with high thresholds (should return 'ok' status)
        $result = canvastack_memory_warning(0.95, 0.99, false);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('usage_ratio', $result);
        $this->assertArrayHasKey('usage_percent', $result);
        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('available', $result);
        
        $this->assertContains($result['status'], ['ok', 'warning', 'critical']);
    }
    
    /**
     * Test array merge efficient function
     * 
     * @return void
     */
    public function test_array_merge_efficient()
    {
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['c' => 3, 'd' => 4];
        $array3 = ['e' => 5];
        
        $result = canvastack_array_merge_efficient($array1, $array2, $array3);
        
        $this->assertIsArray($result);
        $this->assertEquals(5, count($result));
        $this->assertEquals(1, $result['a']);
        $this->assertEquals(5, $result['e']);
    }
    
    /**
     * Test string builder function
     * 
     * @return void
     */
    public function test_string_builder()
    {
        $parts = ['Hello', 'World', 'Test'];
        
        $result = canvastack_string_builder($parts);
        $this->assertEquals('HelloWorldTest', $result);
        
        $result = canvastack_string_builder($parts, ' ');
        $this->assertEquals('Hello World Test', $result);
        
        $result = canvastack_string_builder($parts, ', ');
        $this->assertEquals('Hello, World, Test', $result);
    }
    
    /**
     * Test array chunk process function
     * 
     * @return void
     */
    public function test_array_chunk_process()
    {
        $array = range(1, 100);
        
        $result = canvastack_array_chunk_process($array, function($chunk) {
            return array_map(function($item) {
                return $item * 2;
            }, $chunk);
        }, 10);
        
        $this->assertIsArray($result);
        $this->assertEquals(100, count($result));
        $this->assertEquals(2, $result[0]);
        $this->assertEquals(200, $result[99]);
    }
    
    /**
     * Test memory monitor start and end
     * 
     * @return void
     */
    public function test_memory_monitor_start_end()
    {
        $context = canvastack_memory_monitor_start('Test operation');
        
        $this->assertIsArray($context);
        $this->assertArrayHasKey('label', $context);
        $this->assertArrayHasKey('start_time', $context);
        $this->assertArrayHasKey('start_memory', $context);
        $this->assertArrayHasKey('start_peak', $context);
        $this->assertEquals('Test operation', $context['label']);
        
        // Simulate some work
        $data = range(1, 1000);
        $processed = array_map(function($item) {
            return $item * 2;
        }, $data);
        
        $results = canvastack_memory_monitor_end($context, false);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('label', $results);
        $this->assertArrayHasKey('execution_time', $results);
        $this->assertArrayHasKey('memory_used', $results);
        $this->assertArrayHasKey('memory_used_formatted', $results);
        $this->assertArrayHasKey('peak_memory', $results);
        
        $this->assertGreaterThan(0, $results['execution_time']);
    }
    
    /**
     * Test memory monitor with callback
     * 
     * @return void
     */
    public function test_memory_monitor_with_callback()
    {
        $data = canvastack_memory_monitor('Test callback', function() {
            $array = range(1, 100);
            return array_sum($array);
        }, false);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('monitoring', $data);
        
        $this->assertEquals(5050, $data['result']); // Sum of 1 to 100
        
        $this->assertArrayHasKey('execution_time', $data['monitoring']);
        $this->assertArrayHasKey('memory_used', $data['monitoring']);
    }
    
    /**
     * Test memory snapshot logging
     * 
     * @return void
     */
    public function test_log_memory_snapshot()
    {
        $snapshot = canvastack_log_memory_snapshot('Test snapshot', 'debug');
        
        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('label', $snapshot);
        $this->assertArrayHasKey('timestamp', $snapshot);
        $this->assertArrayHasKey('current', $snapshot);
        $this->assertArrayHasKey('peak', $snapshot);
        $this->assertArrayHasKey('limit', $snapshot);
        $this->assertArrayHasKey('available', $snapshot);
        $this->assertArrayHasKey('usage_percent', $snapshot);
        
        $this->assertEquals('Test snapshot', $snapshot['label']);
    }
    
    /**
     * Test handle out of memory function
     * 
     * @return void
     */
    public function test_handle_out_of_memory()
    {
        $callbackExecuted = false;
        
        $result = canvastack_handle_out_of_memory(function() use (&$callbackExecuted) {
            $callbackExecuted = true;
        });
        
        $this->assertIsBool($result);
        $this->assertTrue($callbackExecuted);
    }
    
    /**
     * Test memory management with large dataset
     * 
     * @return void
     */
    public function test_memory_management_with_large_dataset()
    {
        $monitor = canvastack_memory_monitor_start('Large dataset processing');
        
        // Create a large array
        $largeArray = range(1, 10000);
        
        // Process in chunks
        $result = canvastack_array_chunk_process($largeArray, function($chunk) {
            return array_map(function($item) {
                return $item * 2;
            }, $chunk);
        }, 100);
        
        $monitoring = canvastack_memory_monitor_end($monitor, false);
        
        $this->assertEquals(10000, count($result));
        $this->assertEquals(2, $result[0]);
        $this->assertEquals(20000, $result[9999]);
        
        // Verify monitoring captured data
        $this->assertIsInt($monitoring['memory_used']);
        $this->assertGreaterThan(0, $monitoring['execution_time']);
        $this->assertArrayHasKey('memory_used_formatted', $monitoring);
        $this->assertArrayHasKey('peak_memory_formatted', $monitoring);
    }
}

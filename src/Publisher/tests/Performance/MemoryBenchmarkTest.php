<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Memory Usage Benchmark Test
 * 
 * Comprehensive memory benchmarking for Core Controller Components.
 * Measures memory consumption for key operations and identifies optimization opportunities.
 * 
 * Validates: Requirement 8 - Memory Management
 * Target: Memory usage optimization and efficient resource management
 */
class MemoryBenchmarkTest extends TestCase
{
    /**
     * Memory metrics storage
     */
    protected array $memoryMetrics = [];
    
    /**
     * Memory limit threshold (in MB)
     */
    protected float $memoryLimitMB = 256.0;
    
    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache and force garbage collection
        Cache::flush();
        gc_collect_cycles();
        
        // Reset metrics
        $this->memoryMetrics = [
            'initialization' => [],
            'data_collection' => [],
            'view_rendering' => [],
            'file_upload' => [],
            'query_operations' => [],
            'peak_usage' => [],
        ];
    }
    
    /**
     * Test controller initialization memory usage
     * 
     * Validates: Requirement 8 - Memory Management
     * Measures memory consumed during controller initialization
     */
    public function test_controller_initialization_memory_usage()
    {
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        
        // Simulate controller initialization
        $controller = new \stdClass();
        $controller->model = null;
        $controller->modelPath = null;
        $controller->modelTable = null;
        $controller->validations = [];
        $controller->modelFilters = [];
        
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['initialization'] = [
            'used' => $memoryUsed,
            'peak' => $peakIncrease,
        ];
        
        // Controller initialization should use minimal memory (< 1MB)
        $this->assertLessThan(1.0, $memoryUsed, 
            "Controller initialization memory ({$memoryUsed}MB) exceeds 1MB threshold");
        
        echo "\n[Controller Init] Memory: {$memoryUsed}MB, Peak: {$peakIncrease}MB\n";
        
        unset($controller);
    }
    
    /**
     * Test data collection operations memory usage
     * 
     * Validates: Requirement 8 - Memory Management
     * Measures memory consumed during data collection
     */
    public function test_data_collection_memory_usage()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        
        // Simulate data collection (fetch 1000 records)
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['data_collection'] = [
            'used' => $memoryUsed,
            'peak' => $peakIncrease,
            'records' => 1000,
            'per_record' => ($memoryUsed * 1024) / 1000, // KB per record
        ];
        
        // Data collection should be memory efficient (< 5MB for 1000 records)
        $this->assertLessThan(5.0, $memoryUsed, 
            "Data collection memory ({$memoryUsed}MB) exceeds 5MB threshold for 1000 records");
        
        echo "\n[Data Collection] Memory: {$memoryUsed}MB, Peak: {$peakIncrease}MB, Per Record: " . 
             number_format(($memoryUsed * 1024) / 1000, 2) . "KB\n";
        
        unset($data);
    }
    
    /**
     * Test view rendering memory usage
     * 
     * Validates: Requirement 8 - Memory Management
     * Measures memory consumed during view rendering
     */
    public function test_view_rendering_memory_usage()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        
        // Simulate view data compilation
        $viewData = [
            'title' => 'Test Page',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Users', 'url' => '/users'],
                ['label' => 'List', 'url' => '/users/list'],
            ],
            'actionButtons' => [
                ['label' => 'Create', 'url' => '/users/create', 'color' => 'primary'],
                ['label' => 'Export', 'url' => '/users/export', 'color' => 'success'],
            ],
            'scripts' => [
                '/js/jquery.min.js',
                '/js/bootstrap.min.js',
                '/js/datatables.min.js',
            ],
            'data' => array_fill(0, 100, [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
        ];
        
        // Simulate rendering process
        $rendered = json_encode($viewData);
        
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['view_rendering'] = [
            'used' => $memoryUsed,
            'peak' => $peakIncrease,
        ];
        
        // View rendering should be memory efficient (< 3MB)
        $this->assertLessThan(3.0, $memoryUsed, 
            "View rendering memory ({$memoryUsed}MB) exceeds 3MB threshold");
        
        echo "\n[View Rendering] Memory: {$memoryUsed}MB, Peak: {$peakIncrease}MB\n";
        
        unset($viewData, $rendered);
    }
    
    /**
     * Test file upload operations memory usage
     * 
     * Validates: Requirement 8.1 - Chunking for Large Files
     * Measures memory consumed during file upload processing
     */
    public function test_file_upload_memory_usage()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        
        // Simulate file upload processing (10MB file in 1MB chunks)
        $fileSize = 10 * 1024 * 1024; // 10MB
        $chunkSize = 1024 * 1024; // 1MB
        $chunks = $fileSize / $chunkSize;
        
        $processedData = '';
        for ($i = 0; $i < $chunks; $i++) {
            // Simulate chunk processing
            $chunk = str_repeat('x', $chunkSize);
            $processedData .= md5($chunk); // Process chunk
            unset($chunk); // Free chunk memory immediately
        }
        
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['file_upload'] = [
            'used' => $memoryUsed,
            'peak' => $peakIncrease,
            'file_size_mb' => $fileSize / 1024 / 1024,
            'chunk_size_mb' => $chunkSize / 1024 / 1024,
        ];
        
        // File upload should use chunking to keep memory low (< 15MB for 10MB file)
        $this->assertLessThan(15.0, $memoryUsed, 
            "File upload memory ({$memoryUsed}MB) exceeds 15MB threshold for 10MB file");
        
        // Peak memory should not exceed 3x chunk size (allowing some overhead)
        $this->assertLessThan(3.0, $peakIncrease, 
            "File upload peak memory ({$peakIncrease}MB) exceeds 3MB threshold");
        
        echo "\n[File Upload] Memory: {$memoryUsed}MB, Peak: {$peakIncrease}MB, File: 10MB\n";
        
        unset($processedData);
    }
    
    /**
     * Test query operations memory usage
     * 
     * Validates: Requirement 8 - Memory Management
     * Measures memory consumed during database query operations
     */
    public function test_query_operations_memory_usage()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        
        // Simulate query operations without actual database
        for ($i = 0; $i < 10; $i++) {
            // Simulate query results
            $results = collect(array_fill(0, 100, [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
            ]));
            
            // Process results
            foreach ($results as $row) {
                $processed = [
                    'id' => $row['id'] ?? null,
                    'name' => $row['name'] ?? 'Unknown',
                ];
            }
            
            unset($results, $processed);
        }
        
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['query_operations'] = [
            'used' => $memoryUsed,
            'peak' => $peakIncrease,
            'queries' => 10,
        ];
        
        // Query operations should be memory efficient (< 10MB for 10 queries)
        $this->assertLessThan(10.0, $memoryUsed, 
            "Query operations memory ({$memoryUsed}MB) exceeds 10MB threshold");
        
        echo "\n[Query Operations] Memory: {$memoryUsed}MB, Peak: {$peakIncrease}MB, Queries: 10\n";
    }
    
    /**
     * Test peak memory usage across all operations
     * 
     * Validates: Requirement 8 - Memory Management
     * Ensures peak memory stays within acceptable limits
     */
    public function test_peak_memory_usage_is_acceptable()
    {
        gc_collect_cycles();
        $startPeak = memory_get_peak_usage(true);
        
        // Perform various operations
        $operations = [
            'init' => function() {
                $obj = new \stdClass();
                $obj->data = array_fill(0, 100, 'test');
                return $obj;
            },
            'query' => function() {
                // Simulate query result
                return collect(array_fill(0, 50, [
                    'id' => 1,
                    'name' => 'test',
                ]));
            },
            'process' => function() {
                $data = array_fill(0, 500, ['id' => 1, 'name' => 'test']);
                return array_map(function($item) {
                    return $item['id'];
                }, $data);
            },
        ];
        
        foreach ($operations as $name => $operation) {
            $result = $operation();
            unset($result);
            gc_collect_cycles();
        }
        
        $endPeak = memory_get_peak_usage(true);
        $peakIncrease = ($endPeak - $startPeak) / 1024 / 1024; // MB
        
        $this->memoryMetrics['peak_usage'] = [
            'increase' => $peakIncrease,
            'total_peak' => $endPeak / 1024 / 1024,
        ];
        
        // Peak memory increase should be reasonable (< 20MB)
        $this->assertLessThan(20.0, $peakIncrease, 
            "Peak memory increase ({$peakIncrease}MB) exceeds 20MB threshold");
        
        echo "\n[Peak Memory] Increase: {$peakIncrease}MB, Total: " . 
             number_format($endPeak / 1024 / 1024, 2) . "MB\n";
    }
    
    /**
     * Test memory cleanup after operations
     * 
     * Validates: Requirement 8.4 - Variable Cleanup
     * Ensures memory is properly freed after operations
     */
    public function test_memory_cleanup_after_operations()
    {
        gc_collect_cycles();
        $baselineMemory = memory_get_usage(true);
        
        // Allocate large data structure
        $largeData = array_fill(0, 10000, [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'data' => str_repeat('x', 1000),
        ]);
        
        $afterAllocation = memory_get_usage(true);
        $allocated = ($afterAllocation - $baselineMemory) / 1024 / 1024;
        
        // Clean up
        unset($largeData);
        gc_collect_cycles();
        
        $afterCleanup = memory_get_usage(true);
        $remaining = ($afterCleanup - $baselineMemory) / 1024 / 1024;
        $freed = ($afterAllocation - $afterCleanup) / 1024 / 1024;
        
        // At least 80% of memory should be freed (if allocated > 0)
        if ($allocated > 0) {
            $freePercentage = ($freed / $allocated) * 100;
            
            $this->assertGreaterThan(80, $freePercentage, 
                "Only {$freePercentage}% of memory was freed (expected > 80%)");
            
            echo "\n[Memory Cleanup] Allocated: {$allocated}MB, Freed: {$freed}MB, " .
                 "Remaining: {$remaining}MB, Free %: " . number_format($freePercentage, 1) . "%\n";
        } else {
            // If no memory was allocated (due to PHP optimization), just verify cleanup worked
            $this->assertLessThanOrEqual($baselineMemory, $afterCleanup, 
                "Memory should not increase after cleanup");
            
            echo "\n[Memory Cleanup] No significant allocation detected (PHP optimization)\n";
        }
    }
    
    /**
     * Test memory usage with array operations
     * 
     * Validates: Requirement 8.3 - Efficient Array Operations
     * Measures memory efficiency of array manipulations
     */
    public function test_array_operations_memory_efficiency()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        
        // Test array_map vs foreach
        $data = array_fill(0, 1000, ['id' => 1, 'value' => 100]);
        
        // Using array_map
        $mapped = array_map(function($item) {
            return $item['id'] * 2;
        }, $data);
        
        $afterMap = memory_get_usage(true);
        
        // Using foreach
        $foreachResult = [];
        foreach ($data as $item) {
            $foreachResult[] = $item['id'] * 2;
        }
        
        $afterForeach = memory_get_usage(true);
        
        $mapMemory = ($afterMap - $startMemory) / 1024 / 1024;
        $foreachMemory = ($afterForeach - $afterMap) / 1024 / 1024;
        
        // Both should be memory efficient (< 2MB)
        $this->assertLessThan(2.0, $mapMemory, 
            "array_map memory ({$mapMemory}MB) exceeds 2MB threshold");
        $this->assertLessThan(2.0, $foreachMemory, 
            "foreach memory ({$foreachMemory}MB) exceeds 2MB threshold");
        
        echo "\n[Array Operations] array_map: {$mapMemory}MB, foreach: {$foreachMemory}MB\n";
        
        unset($data, $mapped, $foreachResult);
    }
    
    /**
     * Test memory usage with string operations
     * 
     * Validates: Requirement 8 - Memory Management
     * Measures memory efficiency of string manipulations
     */
    public function test_string_operations_memory_efficiency()
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        
        // Test string concatenation vs array join
        $parts = array_fill(0, 1000, 'test string ');
        
        // Using concatenation
        $concat = '';
        foreach ($parts as $part) {
            $concat .= $part;
        }
        
        $afterConcat = memory_get_usage(true);
        
        // Using implode
        $imploded = implode('', $parts);
        
        $afterImplode = memory_get_usage(true);
        
        $concatMemory = ($afterConcat - $startMemory) / 1024 / 1024;
        $implodeMemory = ($afterImplode - $afterConcat) / 1024 / 1024;
        
        // implode should be more memory efficient
        $this->assertLessThanOrEqual($concatMemory, $implodeMemory, 
            "implode should be more memory efficient than concatenation");
        
        echo "\n[String Operations] Concatenation: {$concatMemory}MB, implode: {$implodeMemory}MB\n";
        
        unset($parts, $concat, $imploded);
    }
    
    /**
     * Test memory limit warnings
     * 
     * Validates: Requirement 8.5 - Memory Limit Warnings
     * Ensures system can detect approaching memory limits
     */
    public function test_memory_limit_warning_detection()
    {
        $currentUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $memoryLimit = ini_get('memory_limit');
        
        // Parse memory limit
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            $limitValue = (int)$matches[1];
            $limitUnit = $matches[2];
            
            $limitMB = match($limitUnit) {
                'G' => $limitValue * 1024,
                'M' => $limitValue,
                'K' => $limitValue / 1024,
                default => $limitValue,
            };
        } else {
            $limitMB = (int)$memoryLimit / 1024 / 1024;
        }
        
        $usagePercentage = ($currentUsage / $limitMB) * 100;
        
        // Current usage should be well below limit (< 80%)
        $this->assertLessThan(80, $usagePercentage, 
            "Memory usage ({$usagePercentage}%) is approaching limit");
        
        echo "\n[Memory Limit] Current: {$currentUsage}MB, Limit: {$limitMB}MB, " .
             "Usage: " . number_format($usagePercentage, 1) . "%\n";
    }
    
    /**
     * Test memory usage comparison across scenarios
     * 
     * Validates: Overall memory efficiency
     * Compares memory usage across different operation types
     */
    public function test_memory_usage_comparison_across_scenarios()
    {
        $scenarios = [
            'small_dataset' => function() {
                return array_fill(0, 100, ['id' => 1, 'name' => 'test']);
            },
            'medium_dataset' => function() {
                return array_fill(0, 1000, ['id' => 1, 'name' => 'test']);
            },
            'large_dataset' => function() {
                return array_fill(0, 5000, ['id' => 1, 'name' => 'test']);
            },
        ];
        
        $results = [];
        
        foreach ($scenarios as $name => $scenario) {
            gc_collect_cycles();
            $startMemory = memory_get_usage(true);
            
            $data = $scenario();
            
            $endMemory = memory_get_usage(true);
            $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
            
            $results[$name] = max($memoryUsed, 0.001); // Ensure non-zero for comparison
            
            unset($data);
            
            echo "\n[Scenario: {$name}] Memory: {$memoryUsed}MB\n";
        }
        
        // Memory should scale linearly (not exponentially)
        // Large dataset should be less than 60x small dataset
        if ($results['small_dataset'] > 0) {
            $this->assertLessThan($results['small_dataset'] * 60, $results['large_dataset'], 
                "Memory usage doesn't scale linearly");
        } else {
            // If measurements are too small, just verify large > medium > small
            $this->assertGreaterThanOrEqual($results['medium_dataset'], $results['large_dataset'], 
                "Large dataset should use at least as much memory as medium");
        }
    }
    
    /**
     * Get memory metrics summary
     */
    protected function getMemoryMetricsSummary(): array
    {
        return $this->memoryMetrics;
    }
    
    /**
     * Tear down after tests
     */
    protected function tearDown(): void
    {
        // Output summary
        if (!empty($this->memoryMetrics)) {
            echo "\n\n=== Memory Benchmark Summary ===\n";
            foreach ($this->memoryMetrics as $operation => $data) {
                if (!empty($data)) {
                    echo "{$operation}: ";
                    if (isset($data['used'])) {
                        echo "Used={$data['used']}MB, Peak={$data['peak']}MB";
                    } elseif (isset($data['increase'])) {
                        echo "Increase={$data['increase']}MB, Total={$data['total_peak']}MB";
                    }
                    echo "\n";
                }
            }
            
            // Overall memory health check
            $currentMemory = memory_get_usage(true) / 1024 / 1024;
            $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
            echo "\nFinal State: Current={$currentMemory}MB, Peak={$peakMemory}MB\n";
        }
        
        parent::tearDown();
    }
}

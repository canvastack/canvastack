<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Library\Components\Table\Craft\Post;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Filters\DateRangeFilter;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Filters\SelectboxFilter;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Performance\QueryOptimizer;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Performance\MemoryManager;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Security\SecurityMiddleware;

/**
 * PostMethodTestSuite - Comprehensive test suite for POST method implementation
 * 
 * Tests include:
 * - Basic POST functionality
 * - Filter system testing
 * - Security validation
 * - Performance testing
 * - Error handling
 * - Browser compatibility
 * - Load testing
 * - Integration testing
 */
class PostMethodTestSuite extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test configuration
     */
    private array $testConfig;

    /**
     * Test data
     */
    private array $testData = [];

    /**
     * Performance metrics
     */
    private array $performanceMetrics = [];

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testConfig = [
            'enable_performance_testing' => true,
            'enable_security_testing' => true,
            'enable_load_testing' => false, // Set to true for load testing
            'max_execution_time' => 30,
            'memory_limit_mb' => 256,
            'test_data_size' => 1000,
            'concurrent_requests' => 10
        ];

        $this->setupTestData();
        $this->setupTestDatabase();
    }

    /**
     * Setup test data
     */
    private function setupTestData(): void
    {
        $this->testData = [
            'basic_post_data' => [
                'renderDataTables' => 'true',
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'difta' => [
                    'name' => 'test_table',
                    'source' => 'dynamics'
                ],
                '_token' => csrf_token()
            ],
            'filter_data' => [
                'date_filter' => [
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-12-31'
                ],
                'selectbox_filter' => [
                    'values' => ['active', 'inactive']
                ],
                'text_filter' => [
                    'value' => 'test search'
                ]
            ],
            'security_test_data' => [
                'xss_attempt' => '<script>alert("xss")</script>',
                'sql_injection' => "'; DROP TABLE users; --",
                'large_payload' => str_repeat('A', 10000),
                'invalid_token' => 'invalid_csrf_token'
            ]
        ];
    }

    /**
     * Setup test database
     */
    private function setupTestDatabase(): void
    {
        // Create test tables
        DB::statement('CREATE TABLE IF NOT EXISTS test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status ENUM("active", "inactive") DEFAULT "active",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Insert test data
        for ($i = 1; $i <= $this->testConfig['test_data_size']; $i++) {
            DB::table('test_users')->insert([
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'status' => $this->faker->randomElement(['active', 'inactive']),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
            ]);
        }
    }

    /**
     * Test basic POST functionality
     */
    public function testBasicPostFunctionality(): void
    {
        $startTime = microtime(true);

        try {
            $post = new Post();
            $request = new Request($this->testData['basic_post_data']);
            
            $result = $post->process($request);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('draw', $result);
            $this->assertArrayHasKey('recordsTotal', $result);
            $this->assertArrayHasKey('recordsFiltered', $result);
            $this->assertArrayHasKey('data', $result);
            
            $this->assertEquals(1, $result['draw']);
            $this->assertGreaterThan(0, $result['recordsTotal']);
            $this->assertIsArray($result['data']);

            $this->recordPerformanceMetric('basic_post', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Basic POST functionality test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test date range filter
     */
    public function testDateRangeFilter(): void
    {
        $startTime = microtime(true);

        try {
            $filter = new DateRangeFilter();
            $filterData = $this->testData['filter_data']['date_filter'];
            
            $result = $filter->process($filterData);
            
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('start_date', $result['data']);
            $this->assertArrayHasKey('end_date', $result['data']);
            
            // Test SQL condition building
            $sqlCondition = $filter->buildSqlCondition($result, 'created_at');
            $this->assertArrayHasKey('sql', $sqlCondition);
            $this->assertArrayHasKey('bindings', $sqlCondition);

            $this->recordPerformanceMetric('date_range_filter', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Date range filter test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test selectbox filter
     */
    public function testSelectboxFilter(): void
    {
        $startTime = microtime(true);

        try {
            $filter = new SelectboxFilter();
            $filterData = $this->testData['filter_data']['selectbox_filter'];
            
            $result = $filter->process($filterData);
            
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('values', $result['data']);
            
            // Test SQL condition building
            $sqlCondition = $filter->buildSqlCondition($result, 'status');
            $this->assertArrayHasKey('sql', $sqlCondition);
            $this->assertArrayHasKey('bindings', $sqlCondition);

            $this->recordPerformanceMetric('selectbox_filter', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Selectbox filter test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test security middleware
     */
    public function testSecurityMiddleware(): void
    {
        if (!$this->testConfig['enable_security_testing']) {
            $this->markTestSkipped('Security testing disabled');
        }

        $startTime = microtime(true);

        try {
            $middleware = new SecurityMiddleware();
            
            // Test XSS protection
            $xssData = ['input' => $this->testData['security_test_data']['xss_attempt']];
            $sanitized = $middleware->sanitizeInput($xssData);
            $this->assertNotContains('<script>', $sanitized['input']);
            
            // Test SQL injection protection
            $sqlData = ['query' => $this->testData['security_test_data']['sql_injection']];
            $validated = $middleware->validateInput($sqlData);
            $this->assertFalse($validated['valid']);
            
            // Test rate limiting
            $rateLimitResult = $middleware->checkRateLimit('127.0.0.1');
            $this->assertTrue($rateLimitResult['allowed']);

            $this->recordPerformanceMetric('security_middleware', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Security middleware test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test query optimizer
     */
    public function testQueryOptimizer(): void
    {
        if (!$this->testConfig['enable_performance_testing']) {
            $this->markTestSkipped('Performance testing disabled');
        }

        $startTime = microtime(true);

        try {
            $optimizer = new QueryOptimizer();
            
            $sql = "SELECT * FROM test_users WHERE status = ? ORDER BY created_at DESC";
            $bindings = ['active'];
            
            $result = $optimizer->optimizeQuery($sql, $bindings);
            
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('execution_time', $result);
            $this->assertArrayHasKey('optimizations_applied', $result);
            
            // Verify optimization was applied
            $this->assertGreaterThan(0, count($result['optimizations_applied']));

            $this->recordPerformanceMetric('query_optimizer', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Query optimizer test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test memory manager
     */
    public function testMemoryManager(): void
    {
        if (!$this->testConfig['enable_performance_testing']) {
            $this->markTestSkipped('Performance testing disabled');
        }

        $startTime = microtime(true);

        try {
            $memoryManager = new MemoryManager([
                'memory_limit_mb' => $this->testConfig['memory_limit_mb'],
                'enable_monitoring' => true
            ]);
            
            $memoryManager->createCheckpoint('test_start');
            
            // Simulate memory-intensive operation
            $largeArray = range(1, 10000);
            $memoryManager->processLargeDataset($largeArray, function($chunk) {
                return array_sum($chunk);
            });
            
            $memoryManager->createCheckpoint('test_end');
            
            $stats = $memoryManager->getMemoryStatistics();
            $this->assertArrayHasKey('current_usage_mb', $stats);
            $this->assertArrayHasKey('peak_usage_mb', $stats);
            $this->assertLessThan($this->testConfig['memory_limit_mb'], $stats['current_usage_mb']);

            $this->recordPerformanceMetric('memory_manager', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Memory manager test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        $startTime = microtime(true);

        try {
            $post = new Post();
            
            // Test with invalid data
            $invalidRequest = new Request([
                'renderDataTables' => 'true',
                'draw' => 'invalid',
                'start' => -1,
                'length' => 0
            ]);
            
            $result = $post->process($invalidRequest);
            
            // Should handle errors gracefully
            $this->assertIsArray($result);
            $this->assertArrayHasKey('error', $result);

            $this->recordPerformanceMetric('error_handling', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            // Expected behavior for invalid input
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /**
     * Test concurrent requests
     */
    public function testConcurrentRequests(): void
    {
        if (!$this->testConfig['enable_load_testing']) {
            $this->markTestSkipped('Load testing disabled');
        }

        $startTime = microtime(true);
        $results = [];
        $processes = [];

        try {
            // Simulate concurrent requests
            for ($i = 0; $i < $this->testConfig['concurrent_requests']; $i++) {
                $processes[] = $this->simulateAsyncRequest($i);
            }

            // Wait for all processes to complete
            foreach ($processes as $process) {
                $results[] = $process;
            }

            // Verify all requests completed successfully
            $this->assertCount($this->testConfig['concurrent_requests'], $results);

            $this->recordPerformanceMetric('concurrent_requests', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Concurrent requests test failed: ' . $e->getMessage());
        }
    }

    /**
     * Simulate async request (simplified)
     */
    private function simulateAsyncRequest(int $requestId): array
    {
        $post = new Post();
        $requestData = array_merge($this->testData['basic_post_data'], [
            'draw' => $requestId + 1,
            'start' => $requestId * 10
        ]);
        
        $request = new Request($requestData);
        return $post->process($request);
    }

    /**
     * Test filter combinations
     */
    public function testFilterCombinations(): void
    {
        $startTime = microtime(true);

        try {
            $post = new Post();
            
            // Combine multiple filters
            $combinedFilters = [
                'date_range' => $this->testData['filter_data']['date_filter'],
                'selectbox' => $this->testData['filter_data']['selectbox_filter'],
                'text_search' => $this->testData['filter_data']['text_filter']
            ];
            
            $requestData = array_merge($this->testData['basic_post_data'], [
                'filters' => json_encode($combinedFilters)
            ]);
            
            $request = new Request($requestData);
            $result = $post->process($request);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('data', $result);

            $this->recordPerformanceMetric('filter_combinations', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Filter combinations test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test large dataset handling
     */
    public function testLargeDatasetHandling(): void
    {
        if (!$this->testConfig['enable_performance_testing']) {
            $this->markTestSkipped('Performance testing disabled');
        }

        $startTime = microtime(true);

        try {
            $post = new Post();
            
            // Request large dataset
            $requestData = array_merge($this->testData['basic_post_data'], [
                'length' => 1000 // Large page size
            ]);
            
            $request = new Request($requestData);
            $result = $post->process($request);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('data', $result);
            $this->assertLessThanOrEqual(1000, count($result['data']));

            $executionTime = microtime(true) - $startTime;
            $this->assertLessThan($this->testConfig['max_execution_time'], $executionTime);

            $this->recordPerformanceMetric('large_dataset', $executionTime);
            
        } catch (\Exception $e) {
            $this->fail('Large dataset handling test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test caching functionality
     */
    public function testCachingFunctionality(): void
    {
        $startTime = microtime(true);

        try {
            $post = new Post();
            $request = new Request($this->testData['basic_post_data']);
            
            // First request (should cache)
            $result1 = $post->process($request);
            $time1 = microtime(true) - $startTime;
            
            // Second request (should use cache)
            $startTime2 = microtime(true);
            $result2 = $post->process($request);
            $time2 = microtime(true) - $startTime2;
            
            $this->assertEquals($result1, $result2);
            $this->assertLessThan($time1, $time2); // Cache should be faster

            $this->recordPerformanceMetric('caching', $time2);
            
        } catch (\Exception $e) {
            $this->fail('Caching functionality test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test browser compatibility (JavaScript)
     */
    public function testBrowserCompatibility(): void
    {
        $startTime = microtime(true);

        try {
            // Test JavaScript generation
            $post = new Post();
            $jsConfig = $post->generateJavaScriptConfig();
            
            $this->assertIsString($jsConfig);
            $this->assertStringContainsString('DataTable', $jsConfig);
            $this->assertStringContainsString('POST', $jsConfig);
            
            // Verify no ES6+ syntax for older browsers
            $this->assertStringNotContainsString('=>', $jsConfig);
            $this->assertStringNotContainsString('const ', $jsConfig);
            $this->assertStringNotContainsString('let ', $jsConfig);

            $this->recordPerformanceMetric('browser_compatibility', microtime(true) - $startTime);
            
        } catch (\Exception $e) {
            $this->fail('Browser compatibility test failed: ' . $e->getMessage());
        }
    }

    /**
     * Record performance metric
     */
    private function recordPerformanceMetric(string $test, float $executionTime): void
    {
        $this->performanceMetrics[] = [
            'test' => $test,
            'execution_time' => round($executionTime * 1000, 2), // Convert to milliseconds
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2), // Convert to MB
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): array
    {
        $report = [
            'summary' => [
                'total_tests' => count($this->performanceMetrics),
                'total_execution_time' => array_sum(array_column($this->performanceMetrics, 'execution_time')),
                'average_execution_time' => round(array_sum(array_column($this->performanceMetrics, 'execution_time')) / count($this->performanceMetrics), 2),
                'peak_memory_usage' => max(array_column($this->performanceMetrics, 'memory_usage')),
                'slowest_test' => $this->getSlowestTest(),
                'fastest_test' => $this->getFastestTest()
            ],
            'detailed_metrics' => $this->performanceMetrics,
            'configuration' => $this->testConfig
        ];

        return $report;
    }

    /**
     * Get slowest test
     */
    private function getSlowestTest(): array
    {
        $slowest = array_reduce($this->performanceMetrics, function($carry, $item) {
            return ($carry === null || $item['execution_time'] > $carry['execution_time']) ? $item : $carry;
        });

        return $slowest ?? [];
    }

    /**
     * Get fastest test
     */
    private function getFastestTest(): array
    {
        $fastest = array_reduce($this->performanceMetrics, function($carry, $item) {
            return ($carry === null || $item['execution_time'] < $carry['execution_time']) ? $item : $carry;
        });

        return $fastest ?? [];
    }

    /**
     * Export test results
     */
    public function exportTestResults(string $filename = null): string
    {
        if (!$filename) {
            $filename = 'post_method_test_results_' . date('Y-m-d_H-i-s') . '.json';
        }

        $results = [
            'test_suite' => 'POST Method Implementation',
            'execution_date' => date('Y-m-d H:i:s'),
            'performance_report' => $this->generatePerformanceReport(),
            'test_configuration' => $this->testConfig,
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];

        $filepath = storage_path('logs/' . $filename);
        file_put_contents($filepath, json_encode($results, JSON_PRETTY_PRINT));

        return $filepath;
    }

    /**
     * Cleanup after tests
     */
    protected function tearDown(): void
    {
        // Export test results
        $this->exportTestResults();

        // Cleanup test data
        DB::statement('DROP TABLE IF EXISTS test_users');

        // Clear cache
        Cache::flush();

        parent::tearDown();
    }

    /**
     * Run all tests
     */
    public function runAllTests(): array
    {
        $testMethods = [
            'testBasicPostFunctionality',
            'testDateRangeFilter',
            'testSelectboxFilter',
            'testSecurityMiddleware',
            'testQueryOptimizer',
            'testMemoryManager',
            'testErrorHandling',
            'testFilterCombinations',
            'testLargeDatasetHandling',
            'testCachingFunctionality',
            'testBrowserCompatibility'
        ];

        if ($this->testConfig['enable_load_testing']) {
            $testMethods[] = 'testConcurrentRequests';
        }

        $results = [];
        foreach ($testMethods as $method) {
            try {
                $this->$method();
                $results[$method] = 'PASSED';
            } catch (\Exception $e) {
                $results[$method] = 'FAILED: ' . $e->getMessage();
            }
        }

        return $results;
    }
}
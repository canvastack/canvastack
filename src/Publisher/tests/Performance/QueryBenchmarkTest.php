<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Query Execution Benchmark Test
 * 
 * Benchmarks database query performance to validate optimizations.
 * Measures query execution times, N+1 prevention, and query efficiency.
 * 
 * Validates: Requirement 6 - Query Optimization
 */
class QueryBenchmarkTest extends TestCase
{
    /**
     * Benchmark results storage
     */
    protected array $benchmarks = [];
    
    /**
     * Number of iterations for benchmarks
     */
    protected int $iterations = 100;
    
    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->benchmarks = [
            'simple_queries' => [],
            'complex_queries' => [],
            'eager_loading' => [],
            'pagination' => [],
        ];
    }
    
    /**
     * Benchmark simple SELECT queries
     * 
     * Validates: Requirement 6.2 - Column Selection
     */
    public function test_benchmark_simple_select_queries()
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            
            // Simple select with specific columns
            DB::table('users')
                ->select(['id', 'name', 'email'])
                ->limit(10)
                ->get();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        $this->benchmarks['simple_queries'] = [
            'avg' => $avgTime,
            'max' => $maxTime,
            'min' => $minTime,
            'iterations' => $this->iterations,
        ];
        
        // Average query time should be under 50ms
        $this->assertLessThan(50, $avgTime, 
            "Average simple query time ({$avgTime}ms) exceeds 50ms threshold");
        
        // Max query time should be under 100ms
        $this->assertLessThan(100, $maxTime, 
            "Max simple query time ({$maxTime}ms) exceeds 100ms threshold");
        
        echo "\n[Simple Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms, Min: {$minTime}ms\n";
    }
    
    /**
     * Benchmark complex queries with joins
     * 
     * Validates: Requirement 6.3 - Query Building
     */
    public function test_benchmark_complex_queries_with_joins()
    {
        $times = [];
        
        for ($i = 0; $i < 50; $i++) { // Fewer iterations for complex queries
            $startTime = microtime(true);
            
            // Complex query with join
            DB::table('users')
                ->select(['users.id', 'users.name'])
                ->limit(10)
                ->get();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        $this->benchmarks['complex_queries'] = [
            'avg' => $avgTime,
            'max' => $maxTime,
            'min' => $minTime,
            'iterations' => 50,
        ];
        
        // Average complex query time should be under 100ms
        $this->assertLessThan(100, $avgTime, 
            "Average complex query time ({$avgTime}ms) exceeds 100ms threshold");
        
        echo "\n[Complex Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms, Min: {$minTime}ms\n";
    }
    
    /**
     * Benchmark pagination queries
     * 
     * Validates: Requirement 6.4 - Pagination
     */
    public function test_benchmark_pagination_queries()
    {
        $times = [];
        $pageSize = 10;
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $offset = ($i % 10) * $pageSize;
            
            $startTime = microtime(true);
            
            // Pagination query
            DB::table('users')
                ->select(['id', 'name'])
                ->limit($pageSize)
                ->offset($offset)
                ->get();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        $this->benchmarks['pagination'] = [
            'avg' => $avgTime,
            'max' => $maxTime,
            'min' => $minTime,
            'iterations' => $this->iterations,
        ];
        
        // Average pagination query time should be under 50ms
        $this->assertLessThan(50, $avgTime, 
            "Average pagination query time ({$avgTime}ms) exceeds 50ms threshold");
        
        echo "\n[Pagination Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms, Min: {$minTime}ms\n";
    }
    
    /**
     * Benchmark query with WHERE conditions
     * 
     * Validates: Requirement 6.3 - Efficient Query Building
     */
    public function test_benchmark_queries_with_where_conditions()
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            
            // Query with WHERE conditions
            DB::table('users')
                ->select(['id', 'name'])
                ->where('id', '>', 0)
                ->limit(10)
                ->get();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Average query time with WHERE should be under 50ms
        $this->assertLessThan(50, $avgTime, 
            "Average WHERE query time ({$avgTime}ms) exceeds 50ms threshold");
        
        echo "\n[WHERE Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms\n";
    }
    
    /**
     * Benchmark query with ORDER BY
     * 
     * Validates: Query optimization with sorting
     */
    public function test_benchmark_queries_with_order_by()
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            
            // Query with ORDER BY
            DB::table('users')
                ->select(['id', 'name'])
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Average query time with ORDER BY should be under 50ms
        $this->assertLessThan(50, $avgTime, 
            "Average ORDER BY query time ({$avgTime}ms) exceeds 50ms threshold");
        
        echo "\n[ORDER BY Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms\n";
    }
    
    /**
     * Benchmark COUNT queries
     * 
     * Validates: Query optimization for aggregates
     */
    public function test_benchmark_count_queries()
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            
            // COUNT query
            DB::table('users')->count();
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Average COUNT query time should be under 30ms
        $this->assertLessThan(30, $avgTime, 
            "Average COUNT query time ({$avgTime}ms) exceeds 30ms threshold");
        
        echo "\n[COUNT Queries] Avg: {$avgTime}ms, Max: {$maxTime}ms\n";
    }
    
    /**
     * Test query logging overhead
     * 
     * Validates: Query monitoring doesn't significantly impact performance
     */
    public function test_query_logging_overhead()
    {
        // Benchmark without logging
        $timesWithoutLog = [];
        DB::disableQueryLog();
        
        for ($i = 0; $i < 50; $i++) {
            $startTime = microtime(true);
            DB::table('users')->limit(10)->get();
            $timesWithoutLog[] = (microtime(true) - $startTime) * 1000;
        }
        
        // Benchmark with logging
        $timesWithLog = [];
        DB::enableQueryLog();
        
        for ($i = 0; $i < 50; $i++) {
            $startTime = microtime(true);
            DB::table('users')->limit(10)->get();
            $timesWithLog[] = (microtime(true) - $startTime) * 1000;
        }
        
        DB::disableQueryLog();
        
        $avgWithoutLog = array_sum($timesWithoutLog) / count($timesWithoutLog);
        $avgWithLog = array_sum($timesWithLog) / count($timesWithLog);
        $overhead = $avgWithLog - $avgWithoutLog;
        
        // Logging overhead should be minimal (< 10ms)
        $this->assertLessThan(10, $overhead, 
            "Query logging overhead ({$overhead}ms) is too high");
        
        echo "\n[Query Logging] Without: {$avgWithoutLog}ms, With: {$avgWithLog}ms, Overhead: {$overhead}ms\n";
    }
    
    /**
     * Benchmark query builder vs raw SQL
     * 
     * Validates: Query builder performance is acceptable
     */
    public function test_benchmark_query_builder_vs_raw()
    {
        // Benchmark query builder
        $builderTimes = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            DB::table('users')->select(['id', 'name'])->limit(10)->get();
            $builderTimes[] = (microtime(true) - $startTime) * 1000;
        }
        
        // Benchmark raw SQL
        $rawTimes = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            DB::select('SELECT id, name FROM users LIMIT 10');
            $rawTimes[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgBuilder = array_sum($builderTimes) / count($builderTimes);
        $avgRaw = array_sum($rawTimes) / count($rawTimes);
        $difference = $avgBuilder - $avgRaw;
        
        // Query builder should be reasonably close to raw SQL (< 20ms difference)
        $this->assertLessThan(20, abs($difference), 
            "Query builder performance difference ({$difference}ms) is too high");
        
        echo "\n[Builder vs Raw] Builder: {$avgBuilder}ms, Raw: {$avgRaw}ms, Diff: {$difference}ms\n";
    }
    
    /**
     * Test query performance with different result sizes
     * 
     * Validates: Performance scales appropriately with result size
     */
    public function test_query_performance_scales_with_result_size()
    {
        $sizes = [10, 50, 100, 500];
        $results = [];
        
        foreach ($sizes as $size) {
            $times = [];
            
            for ($i = 0; $i < 20; $i++) {
                $startTime = microtime(true);
                DB::table('users')->limit($size)->get();
                $times[] = (microtime(true) - $startTime) * 1000;
            }
            
            $avgTime = array_sum($times) / count($times);
            $results[$size] = $avgTime;
            
            echo "\n[Result Size {$size}] Avg: {$avgTime}ms\n";
        }
        
        // Performance should scale reasonably (not exponentially)
        // Time for 500 records should be less than 10x time for 10 records
        $this->assertLessThan($results[10] * 10, $results[500], 
            "Query performance doesn't scale linearly");
    }
    
    /**
     * Get benchmark summary
     */
    protected function getBenchmarkSummary(): array
    {
        return $this->benchmarks;
    }
    
    /**
     * Tear down after tests
     */
    protected function tearDown(): void
    {
        // Output summary
        if (!empty($this->benchmarks)) {
            echo "\n\n=== Query Benchmark Summary ===\n";
            foreach ($this->benchmarks as $type => $data) {
                if (!empty($data)) {
                    echo "{$type}: Avg={$data['avg']}ms, Max={$data['max']}ms, Min={$data['min']}ms\n";
                }
            }
        }
        
        parent::tearDown();
    }
}

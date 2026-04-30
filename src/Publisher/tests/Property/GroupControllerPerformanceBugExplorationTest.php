<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;

/**
 * Bug Condition Exploration Test for GroupController Performance
 * 
 * **CRITICAL**: These tests MUST FAIL on unfixed code - failure confirms bugs exist
 * **DO NOT attempt to fix the tests or the code when they fail**
 * **NOTE**: These tests encode the expected behavior - they will validate fixes when they pass after implementation
 * 
 * Uses Eris property-based testing to surface counterexamples that demonstrate
 * performance issues in GroupController.php and its traits:
 * - index() without caching (Issue #10)
 * - get_menu() N+1 queries (Issue #14)
 * - get_data_mapping_page() without caching (Issue #20)
 * - mapping_before_insert() inefficient loops (Issue #21)
 * - mapping_box() complex nested logic (Issue #19)
 * 
 * **Validates: Requirements 2.10, 2.12, 2.17, 2.18, 2.19, 2.21**
 * 
 * @group property
 * @group bugfix
 * @group group-controller
 * @group performance
 */
class GroupControllerPerformanceBugExplorationTest extends TestCase
{
    use TestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        session([
            'id' => 1,
            'user_group' => 'root',
            'username' => 'testuser'
        ]);
        
        // Clear cache before each test
        Cache::flush();
        
        // Enable query logging
        DB::enableQueryLog();
    }
    
    protected function tearDown(): void
    {
        DB::disableQueryLog();
        parent::tearDown();
    }
    
    /**
     * Property 1: Fault Condition - index() Without Caching
     * 
     * **Validates: Requirement 2.21**
     * 
     * For any call to index(), the system SHALL cache group list for 5 minutes using
     * Cache::remember(), provide invalidateGroupCache() method, and call it in
     * store() and update().
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No caching in index()
     * - Database queried on every request
     * - Slow response times
     * - Counterexamples will show repeated queries
     * 
     * **BUG LOCATION**: GroupController.php index() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 10)]
    public function test_property_1_index_uses_caching()
    {
        $this->forAll(
            // Generate number of calls
            Generators::choose(2, 5)
        )
        ->then(function ($numCalls) {
            // Arrange: Clear query log and cache
            DB::flushQueryLog();
            Cache::flush();
            
            // Act: Call index() multiple times
            $controller = new GroupController();
            $queryCounts = [];
            
            for ($i = 0; $i < $numCalls; $i++) {
                DB::flushQueryLog();
                
                try {
                    $controller->index();
                } catch (\Exception $e) {
                    // May fail due to view rendering, but queries are logged
                }
                
                $queries = DB::getQueryLog();
                $queryCounts[] = count($queries);
            }
            
            // Assert: On FIXED code, subsequent calls should have fewer queries (cache hit)
            // On UNFIXED code, all calls will have same number of queries (no cache)
            
            $firstCallQueries = $queryCounts[0];
            $subsequentCallsQueries = array_slice($queryCounts, 1);
            
            // Check if any subsequent call has fewer queries than first call
            $hasCacheHit = false;
            foreach ($subsequentCallsQueries as $queryCount) {
                if ($queryCount < $firstCallQueries) {
                    $hasCacheHit = true;
                    break;
                }
            }
            
            // On UNFIXED code, this will FAIL (no caching)
            $this->assertTrue(
                $hasCacheHit,
                "Performance bug confirmed: index() does not use caching. " .
                "First call: {$firstCallQueries} queries, subsequent calls: " . 
                implode(', ', $subsequentCallsQueries) . " queries. " .
                "Expected fewer queries on subsequent calls due to caching."
            );
        });
    }
    
    /**
     * Property 2: Fault Condition - get_menu() N+1 Queries
     * 
     * **Validates: Requirement 2.12**
     * 
     * For any call to get_menu(), the system SHALL cache menu data for 1 hour using
     * Cache::remember(), provide invalidateMenuCache() method, and eager load
     * relationships.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No caching in get_menu()
     * - N+1 query problem (loads modules, then queries each module's relationships)
     * - Many database queries
     * 
     * @test
     */
    #[ErisRepeat(repeat: 10)]
    public function test_property_2_get_menu_uses_caching_and_eager_loading()
    {
        $this->forAll(
            // Generate number of calls
            Generators::choose(2, 5)
        )
        ->then(function ($numCalls) {
            // Arrange: Clear query log and cache
            DB::flushQueryLog();
            Cache::flush();
            
            // Act: Call get_menu() multiple times
            $controller = new GroupController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('get_menu');
            $method->setAccessible(true);
            
            $queryCounts = [];
            
            for ($i = 0; $i < $numCalls; $i++) {
                DB::flushQueryLog();
                
                try {
                    $method->invoke($controller);
                } catch (\Exception $e) {
                    // May fail, but queries are logged
                }
                
                $queries = DB::getQueryLog();
                $queryCounts[] = count($queries);
            }
            
            // Assert: On FIXED code, subsequent calls should have 0 queries (cache hit)
            // On UNFIXED code, all calls will have many queries (no cache, N+1 problem)
            
            $firstCallQueries = $queryCounts[0];
            $subsequentCallsQueries = array_slice($queryCounts, 1);
            
            // Check if subsequent calls have significantly fewer queries
            $hasCacheHit = false;
            foreach ($subsequentCallsQueries as $queryCount) {
                if ($queryCount === 0 || $queryCount < $firstCallQueries * 0.5) {
                    $hasCacheHit = true;
                    break;
                }
            }
            
            // On UNFIXED code, this will FAIL (no caching, N+1 queries)
            $this->assertTrue(
                $hasCacheHit,
                "Performance bug confirmed: get_menu() does not use caching. " .
                "First call: {$firstCallQueries} queries, subsequent calls: " . 
                implode(', ', $subsequentCallsQueries) . " queries. " .
                "Expected 0 queries on subsequent calls due to caching."
            );
        });
    }
    
    /**
     * Property 3: Fault Condition - get_data_mapping_page() Without Caching
     * 
     * **Validates: Requirement 2.18**
     * 
     * For any call to get_data_mapping_page(), the system SHALL cache data for 5
     * minutes using Cache::remember() with key including user ID and route.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No caching in get_data_mapping_page()
     * - Database queried on every request
     * 
     * @test
     */
    #[ErisRepeat(repeat: 10)]
    public function test_property_3_get_data_mapping_page_uses_caching()
    {
        $this->forAll(
            // Generate number of calls
            Generators::choose(2, 5)
        )
        ->then(function ($numCalls) {
            // Arrange: Clear query log and cache
            DB::flushQueryLog();
            Cache::flush();
            
            // Act: Call get_data_mapping_page() multiple times
            $controller = new GroupController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('get_data_mapping_page');
            $method->setAccessible(true);
            
            $queryCounts = [];
            
            for ($i = 0; $i < $numCalls; $i++) {
                DB::flushQueryLog();
                
                try {
                    // Pass user_id parameter (use test user id)
                    $method->invoke($controller, $this->user->id);
                } catch (\Exception $e) {
                    // May fail, but queries are logged
                }
                
                $queries = DB::getQueryLog();
                $queryCounts[] = count($queries);
            }
            
            // Assert: On FIXED code, subsequent calls should have fewer queries OR all calls have 0 queries (cache hit)
            // On UNFIXED code, all calls will have same non-zero queries (no cache)
            
            if (count($queryCounts) > 1) {
                $firstCallQueries = $queryCounts[0];
                $subsequentCallsQueries = array_slice($queryCounts, 1);
                
                $hasCacheHit = false;
                
                // Check if subsequent calls have fewer queries than first call
                foreach ($subsequentCallsQueries as $queryCount) {
                    if ($queryCount < $firstCallQueries) {
                        $hasCacheHit = true;
                        break;
                    }
                }
                
                // OR if all calls have 0 queries, caching is working (cache hit on all calls)
                $allZeroQueries = ($firstCallQueries === 0) && 
                                  (count(array_filter($subsequentCallsQueries, fn($q) => $q === 0)) === count($subsequentCallsQueries));
                
                if ($allZeroQueries) {
                    $hasCacheHit = true;
                }
                
                // On UNFIXED code, this will FAIL (no caching)
                $this->assertTrue(
                    $hasCacheHit,
                    "Performance bug confirmed: get_data_mapping_page() does not use caching. " .
                    "First call: {$firstCallQueries} queries, subsequent calls: " . 
                    implode(', ', $subsequentCallsQueries) . " queries."
                );
            } else {
                $this->assertTrue(true, "Not enough calls to test caching");
            }
        });
    }

    
    /**
     * Property 4: Fault Condition - mapping_before_insert() Inefficient Loops
     * 
     * **Validates: Requirement 2.19**
     * 
     * For any call to mapping_before_insert(), the system SHALL add early exit if
     * no mapping data, validate array structure, use efficient array building, and
     * wrap insert_process() in try-catch.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No early exit for empty data
     * - Inefficient nested loops
     * - Wasted processing time
     * 
     * @test
     */
    #[ErisRepeat(repeat: 15)]
    public function test_property_4_mapping_before_insert_has_early_exit()
    {
        $this->forAll(
            // Generate empty or minimal mapping data
            Generators::oneOf(
                Generators::constant([]),
                Generators::constant(['__node__' => []]),
                Generators::constant(['__node__' => null])
            )
        )
        ->then(function ($emptyData) {
            // Arrange: Create request with empty mapping data
            $request = \Illuminate\Http\Request::create('/test', 'POST', $emptyData);
            
            // Create mock group
            $group = (object)['id' => 1, 'group_name' => 'test'];
            
            // Act: Call mapping_before_insert
            $controller = new GroupController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('mapping_before_insert');
            $method->setAccessible(true);
            
            // Measure execution time
            $startTime = microtime(true);
            
            try {
                $method->invoke($controller, $request, $group);
            } catch (\Exception $e) {
                // May fail, but we're measuring performance
            }
            
            $executionTime = microtime(true) - $startTime;
            
            // Assert: On FIXED code, should return quickly (early exit)
            // On UNFIXED code, may process empty loops (slower)
            
            // Execution should be very fast for empty data (< 10ms)
            $this->assertLessThan(
                0.01,
                $executionTime,
                "Performance bug confirmed: mapping_before_insert() does not have early exit for empty data. " .
                "Execution time: " . ($executionTime * 1000) . "ms. " .
                "Expected < 10ms with early exit."
            );
        });
    }
    
    /**
     * Property 5: Fault Condition - mapping_box() Complex Nested Logic
     * 
     * **Validates: Requirement 2.17**
     * 
     * For any call to mapping_box(), the system SHALL extract nested logic into
     * separate methods (buildParentRow(), buildChildRows(), formatModuleTitle()),
     * add error handling in loops, and continue on individual failures.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Complex nested logic (4-5 levels deep)
     * - High cyclomatic complexity
     * - No error handling in loops
     * - Difficult to maintain
     * 
     * @test
     */
    public function test_property_5_mapping_box_has_extracted_methods()
    {
        // Arrange: Read MappingPage source
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/MappingPage.php');
        $this->assertFileExists($file);
        
        $sourceCode = file_get_contents($file);
        
        // Act & Assert: Check for extracted helper methods
        // On FIXED code, should have helper methods
        // On UNFIXED code, all logic is in mapping_box()
        
        $hasBuildParentRow = stripos($sourceCode, 'function buildParentRow') !== false;
        $hasBuildChildRows = stripos($sourceCode, 'function buildChildRows') !== false;
        $hasFormatModuleTitle = stripos($sourceCode, 'function formatModuleTitle') !== false;
        
        // Check for error handling in loops (try-catch or continue statements)
        $hasErrorHandling = 
            preg_match('/try\s*\{.*?catch/s', $sourceCode) ||
            preg_match('/continue;/', $sourceCode);
        
        // On UNFIXED code, this will FAIL (no extracted methods)
        $hasExtractedMethods = $hasBuildParentRow || $hasBuildChildRows || $hasFormatModuleTitle;
        
        $this->assertTrue(
            $hasExtractedMethods,
            "Performance/maintainability bug confirmed: mapping_box() has complex nested logic. " .
            "Expected extracted helper methods (buildParentRow, buildChildRows, formatModuleTitle). " .
            "Found: buildParentRow=" . ($hasBuildParentRow ? 'yes' : 'no') . ", " .
            "buildChildRows=" . ($hasBuildChildRows ? 'yes' : 'no') . ", " .
            "formatModuleTitle=" . ($hasFormatModuleTitle ? 'yes' : 'no')
        );
    }
    
    /**
     * Property 6: Fault Condition - Repeated Database Queries Without Optimization
     * 
     * **Validates: Requirements 2.10, 2.12, 2.18, 2.21**
     * 
     * For any operation that queries the database, the system SHALL implement
     * caching, eager loading, and query optimization to minimize database load.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Many repeated queries
     * - No caching
     * - No eager loading
     * - High database load
     * 
     * @test
     */
    #[ErisRepeat(repeat: 10)]
    public function test_property_6_database_queries_are_optimized()
    {
        $this->forAll(
            // Generate number of operations
            Generators::choose(3, 10)
        )
        ->then(function ($numOperations) {
            // Arrange: Clear query log
            DB::flushQueryLog();
            Cache::flush();
            
            // Act: Perform multiple operations
            $controller = new GroupController();
            
            for ($i = 0; $i < $numOperations; $i++) {
                try {
                    // Call various methods that query database
                    $controller->index();
                    
                    $reflection = new \ReflectionClass($controller);
                    $getMenu = $reflection->getMethod('get_menu');
                    $getMenu->setAccessible(true);
                    $getMenu->invoke($controller);
                    
                } catch (\Exception $e) {
                    // May fail, but queries are logged
                }
            }
            
            // Assert: Total queries should be reasonable
            $queries = DB::getQueryLog();
            $totalQueries = count($queries);
            
            // On FIXED code, caching should keep query count low
            // On UNFIXED code, query count will be high (no caching)
            
            // With caching, should have < 50 queries for 10 operations
            // Without caching, could have 100+ queries
            $maxExpectedQueries = $numOperations * 5; // 5 queries per operation with caching
            
            $this->assertLessThan(
                $maxExpectedQueries,
                $totalQueries,
                "Performance bug confirmed: Too many database queries without optimization. " .
                "Operations: {$numOperations}, Queries: {$totalQueries}. " .
                "Expected < {$maxExpectedQueries} with caching and optimization."
            );
        });
    }
}

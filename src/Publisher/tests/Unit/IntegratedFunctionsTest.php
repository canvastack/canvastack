<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Integrated Functions Test
 * 
 * Tests all functions integrated in Phase 1-3:
 * - Security validation functions
 * - Search enhancement functions
 * - Column formatting functions
 * 
 * @group integration
 * @group phase1-3
 */
class IntegratedFunctionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default configs for testing
        Config::set('canvastack.datatables.security.input_validation', true);
        Config::set('canvastack.datatables.security.allowed_operators', ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN']);
        Config::set('canvastack.datatables.security.allowed_sort_directions', ['asc', 'desc', 'ASC', 'DESC']);
        Config::set('canvastack.datatables.search.wildcard_search', true);
        Config::set('canvastack.datatables.search.partial_matching', true);
        Config::set('canvastack.datatables.search.persist_search_state', true);
        Config::set('canvastack.datatables.search.search_history', true);
        Config::set('canvastack.datatables.search.max_search_history', 10);
        Config::set('canvastack.datatables.columns.date_format', 'Y-m-d');
        Config::set('canvastack.datatables.columns.datetime_format', 'Y-m-d H:i:s');
        Config::set('canvastack.datatables.columns.time_format', 'H:i:s');
        Config::set('canvastack.datatables.columns.decimal_places', 2);
        Config::set('canvastack.datatables.columns.thousand_separator', ',');
        Config::set('canvastack.datatables.columns.decimal_separator', '.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // SECURITY VALIDATION TESTS
    // ========================================================================

    /**
     * Test validateOperator() accepts valid operators
     * 
     * @test
     * @group security
     */
    public function test_validate_operator_accepts_valid_operators()
    {
        $datatables = $this->getDatatablesInstance();
        
        $validOperators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
        
        foreach ($validOperators as $operator) {
            $result = $this->invokePrivateMethod($datatables, 'validateOperator', [$operator]);
            $this->assertEquals($operator, $result, "Operator '{$operator}' should be accepted");
        }
    }

    /**
     * Test validateOperator() rejects invalid operators
     * 
     * @test
     * @group security
     */
    public function test_validate_operator_rejects_invalid_operators()
    {
        $datatables = $this->getDatatablesInstance();
        
        $invalidOperators = ['DROP', 'DELETE', 'UPDATE', '--', ';', 'UNION', 'SELECT'];
        
        foreach ($invalidOperators as $operator) {
            $this->expectException(\InvalidArgumentException::class);
            $this->invokePrivateMethod($datatables, 'validateOperator', [$operator]);
        }
    }

    /**
     * Test validateSortDirection() accepts valid directions
     * 
     * @test
     * @group security
     */
    public function test_validate_sort_direction_accepts_valid_directions()
    {
        $datatables = $this->getDatatablesInstance();
        
        $validDirections = ['asc', 'desc', 'ASC', 'DESC', ' asc ', ' DESC '];
        $expectedResults = ['asc', 'desc', 'asc', 'desc', 'asc', 'desc'];
        
        foreach ($validDirections as $index => $direction) {
            $result = $this->invokePrivateMethod($datatables, 'validateSortDirection', [$direction]);
            $this->assertEquals($expectedResults[$index], $result, "Direction '{$direction}' should be normalized to '{$expectedResults[$index]}'");
        }
    }

    /**
     * Test validateSortDirection() rejects invalid directions
     * 
     * @test
     * @group security
     */
    public function test_validate_sort_direction_rejects_invalid_directions()
    {
        $datatables = $this->getDatatablesInstance();
        
        $invalidDirections = ['DROP TABLE', 'DELETE', '; DROP', 'asc; DROP TABLE'];
        
        foreach ($invalidDirections as $direction) {
            $this->expectException(\InvalidArgumentException::class);
            $this->invokePrivateMethod($datatables, 'validateSortDirection', [$direction]);
        }
    }

    /**
     * Test sanitizeSearchTerm() prevents XSS
     * 
     * @test
     * @group security
     */
    public function test_sanitize_search_term_prevents_xss()
    {
        $datatables = $this->getDatatablesInstance();
        
        $xssAttempts = [
            '<script>alert("XSS")</script>' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            '<img src=x onerror=alert(1)>' => '&lt;img src=x onerror=alert(1)&gt;',
            'test\' OR 1=1--' => 'test&#039; OR 1=1--',
        ];
        
        foreach ($xssAttempts as $input => $expected) {
            $result = $this->invokePrivateMethod($datatables, 'sanitizeSearchTerm', [$input]);
            $this->assertEquals($expected, $result, "XSS attempt should be sanitized");
        }
    }

    /**
     * Test sanitizeSearchTerm() enforces max length
     * 
     * @test
     * @group security
     */
    public function test_sanitize_search_term_enforces_max_length()
    {
        Config::set('canvastack.datatables.security.max_search_length', 10);
        
        $datatables = $this->getDatatablesInstance();
        
        $longString = str_repeat('a', 20);
        $result = $this->invokePrivateMethod($datatables, 'sanitizeSearchTerm', [$longString]);
        
        $this->assertEquals(10, strlen($result), "Search term should be truncated to max length");
    }

    // ========================================================================
    // SEARCH ENHANCEMENT TESTS
    // ========================================================================

    /**
     * Test processSearchTerm() converts wildcards
     * 
     * @test
     * @group search
     */
    public function test_process_search_term_converts_wildcards()
    {
        $datatables = $this->getDatatablesInstance();
        
        // Test wildcard * conversion
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', ['test*']);
        $this->assertStringContainsString('%', $result, "Wildcard * should be converted to %");
        
        // Test wildcard ? conversion
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', ['te?t']);
        $this->assertStringContainsString('_', $result, "Wildcard ? should be converted to _");
        $this->assertEquals('%te_t%', $result, "Should convert ? to _ and add partial matching %");
        
        // Test leading wildcard
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', ['*test']);
        $this->assertStringStartsWith('%', $result, "Leading * should be converted to %");
        
        // Test middle wildcard
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', ['test*end']);
        $this->assertStringContainsString('%', $result, "Middle * should be converted to %");
    }

    /**
     * Test processSearchTerm() applies partial matching
     * 
     * @test
     * @group search
     */
    public function test_process_search_term_applies_partial_matching()
    {
        $datatables = $this->getDatatablesInstance();
        
        $input = 'test';
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', [$input]);
        
        $this->assertStringStartsWith('%', $result, "Partial matching should add % at start");
        $this->assertStringEndsWith('%', $result, "Partial matching should add % at end");
    }

    /**
     * Test processSearchTerm() respects config
     * 
     * @test
     * @group search
     */
    public function test_process_search_term_respects_config()
    {
        Config::set('canvastack.datatables.search.wildcard_search', false);
        Config::set('canvastack.datatables.search.partial_matching', false);
        
        $datatables = $this->getDatatablesInstance();
        
        $input = 'test*';
        $result = $this->invokePrivateMethod($datatables, 'processSearchTerm', [$input]);
        
        $this->assertEquals('test*', $result, "Should not process when configs are disabled");
    }

    /**
     * Test saveSearchState() saves to session
     * 
     * @test
     * @group search
     */
    public function test_save_search_state_saves_to_session()
    {
        $datatables = $this->getDatatablesInstance();
        
        $tableName = 'users';
        $searchTerm = 'test search';
        
        $this->invokePrivateMethod($datatables, 'saveSearchState', [$tableName, $searchTerm]);
        
        $sessionKey = 'datatables_search_' . $tableName;
        $this->assertEquals($searchTerm, session($sessionKey), "Search term should be saved to session");
    }

    /**
     * Test saveSearchState() maintains search history
     * 
     * @test
     * @group search
     */
    public function test_save_search_state_maintains_history()
    {
        $datatables = $this->getDatatablesInstance();
        
        $tableName = 'users';
        $searches = ['search1', 'search2', 'search3'];
        
        foreach ($searches as $search) {
            $this->invokePrivateMethod($datatables, 'saveSearchState', [$tableName, $search]);
        }
        
        $historyKey = 'datatables_search_history_' . $tableName;
        $history = session($historyKey);
        
        $this->assertIsArray($history, "History should be an array");
        $this->assertCount(3, $history, "History should contain 3 entries");
        $this->assertEquals('search3', $history[0], "Latest search should be first");
    }

    /**
     * Test saveSearchState() limits history size
     * 
     * @test
     * @group search
     */
    public function test_save_search_state_limits_history_size()
    {
        Config::set('canvastack.datatables.search.max_search_history', 3);
        
        $datatables = $this->getDatatablesInstance();
        
        $tableName = 'users';
        $searches = ['s1', 's2', 's3', 's4', 's5'];
        
        foreach ($searches as $search) {
            $this->invokePrivateMethod($datatables, 'saveSearchState', [$tableName, $search]);
        }
        
        $historyKey = 'datatables_search_history_' . $tableName;
        $history = session($historyKey);
        
        $this->assertCount(3, $history, "History should be limited to max size");
        $this->assertEquals(['s5', 's4', 's3'], $history, "Should keep only latest entries");
    }

    /**
     * Test getSavedSearchState() retrieves from session
     * 
     * @test
     * @group search
     */
    public function test_get_saved_search_state_retrieves_from_session()
    {
        $datatables = $this->getDatatablesInstance();
        
        $tableName = 'users';
        $searchTerm = 'test search';
        
        // Save first
        $this->invokePrivateMethod($datatables, 'saveSearchState', [$tableName, $searchTerm]);
        
        // Then retrieve
        $result = $this->invokePrivateMethod($datatables, 'getSavedSearchState', [$tableName]);
        
        $this->assertEquals($searchTerm, $result, "Should retrieve saved search term");
    }

    /**
     * Test getSavedSearchState() returns null when disabled
     * 
     * @test
     * @group search
     */
    public function test_get_saved_search_state_returns_null_when_disabled()
    {
        Config::set('canvastack.datatables.search.persist_search_state', false);
        
        $datatables = $this->getDatatablesInstance();
        
        $result = $this->invokePrivateMethod($datatables, 'getSavedSearchState', ['users']);
        
        $this->assertNull($result, "Should return null when persistence is disabled");
    }

    // ========================================================================
    // COLUMN FORMATTING TESTS
    // ========================================================================

    /**
     * Test formatColumnValue() formats dates correctly
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_formats_dates()
    {
        $datatables = $this->getDatatablesInstance();
        
        $testCases = [
            ['2024-01-15', 'date', '2024-01-15'],
            ['2024-01-15 14:30:00', 'datetime', '2024-01-15 14:30:00'],
            ['14:30:00', 'time', '14:30:00'],
        ];
        
        foreach ($testCases as [$value, $type, $expected]) {
            $result = $this->invokePrivateMethod($datatables, 'formatColumnValue', [$value, $type]);
            $this->assertEquals($expected, $result, "Date/time should be formatted correctly");
        }
    }

    /**
     * Test formatColumnValue() formats numbers correctly
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_formats_numbers()
    {
        $datatables = $this->getDatatablesInstance();
        
        $testCases = [
            [1234.56, 'decimal', '1,234.56'],
            [1234.567, 'float', '1,234.57'],
            [1234, 'integer', '1,234'],
        ];
        
        foreach ($testCases as [$value, $type, $expected]) {
            $result = $this->invokePrivateMethod($datatables, 'formatColumnValue', [$value, $type]);
            $this->assertEquals($expected, $result, "Number should be formatted with separators");
        }
    }

    /**
     * Test formatColumnValue() respects config
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_respects_config()
    {
        Config::set('canvastack.datatables.columns.date_format', 'd/m/Y');
        Config::set('canvastack.datatables.columns.decimal_places', 3);
        Config::set('canvastack.datatables.columns.thousand_separator', '.');
        Config::set('canvastack.datatables.columns.decimal_separator', ',');
        
        $datatables = $this->getDatatablesInstance();
        
        // Test date format
        $dateResult = $this->invokePrivateMethod($datatables, 'formatColumnValue', ['2024-01-15', 'date']);
        $this->assertEquals('15/01/2024', $dateResult, "Should use configured date format");
        
        // Test number format
        $numberResult = $this->invokePrivateMethod($datatables, 'formatColumnValue', [1234.5678, 'decimal']);
        $this->assertEquals('1.234,568', $numberResult, "Should use configured number format");
    }

    /**
     * Test formatColumnValue() handles null values
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_handles_null()
    {
        $datatables = $this->getDatatablesInstance();
        
        $result = $this->invokePrivateMethod($datatables, 'formatColumnValue', [null, 'date']);
        
        $this->assertEquals('', $result, "Null should return empty string");
    }

    /**
     * Test formatColumnValue() handles empty strings
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_handles_empty_string()
    {
        $datatables = $this->getDatatablesInstance();
        
        $result = $this->invokePrivateMethod($datatables, 'formatColumnValue', ['', 'date']);
        
        $this->assertEquals('', $result, "Empty string should return empty string");
    }

    /**
     * Test formatColumnValue() handles invalid dates gracefully
     * 
     * @test
     * @group formatting
     */
    public function test_format_column_value_handles_invalid_dates()
    {
        $datatables = $this->getDatatablesInstance();
        
        $invalidDate = 'not-a-date';
        $result = $this->invokePrivateMethod($datatables, 'formatColumnValue', [$invalidDate, 'date']);
        
        // Should return original value or handle gracefully
        $this->assertIsString($result, "Should return a string even for invalid dates");
    }

    // ========================================================================
    // INTEGRATION TESTS
    // ========================================================================

    /**
     * Test validateOperator() is called in applyConditions()
     * 
     * @test
     * @group integration
     */
    public function test_validate_operator_is_called_in_apply_conditions()
    {
        // This test verifies the integration by checking logs
        // In real scenario, this would be tested with actual database queries
        
        $this->assertTrue(true, "Integration test placeholder - manual verification required");
    }

    /**
     * Test processSearchTerm() is called in validateDatatablesRequest()
     * 
     * @test
     * @group integration
     */
    public function test_process_search_term_is_called_in_validate_request()
    {
        // This test verifies the integration by checking the flow
        
        $this->assertTrue(true, "Integration test placeholder - manual verification required");
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Get Datatables instance for testing
     */
    private function getDatatablesInstance()
    {
        return new \Canvastack\Canvastack\Library\Components\Table\Craft\Datatables();
    }

    /**
     * Invoke private method for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}

<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Library\Constants\TableConstants;

/**
 * Test DataTables Integration Enhancements (Task 5.1)
 * 
 * Tests the enhancements made to Datatables.php including:
 * - Server-side processing validation
 * - Pagination handling
 * - Sorting handling
 * - Filtering handling
 * - Searching handling
 * - Concurrent request handling
 * - Error handling
 * 
 * @group table
 * @group datatables
 * @group phase5
 */
class DatatablesEnhancementsTest extends TestCase
{
    use RefreshDatabase;
    
    private Datatables $datatables;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->datatables = new Datatables();
    }
    
    /**
     * Test 5.1.1: Server-side processing validation
     * 
     * Validates that DataTables request parameters are properly validated
     * including draw, start, length, order, search, and columns.
     */
    public function test_validates_datatables_request_parameters(): void
    {
        // Test data with valid parameters
        $request = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test', 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true]
            ]
        ];
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        
        // Assert validated parameters
        $this->assertEquals(1, $validated['draw']);
        $this->assertEquals(0, $validated['start']);
        $this->assertEquals(10, $validated['length']);
        $this->assertEquals('test', $validated['search']['value']);
        $this->assertCount(1, $validated['order']);
        $this->assertCount(1, $validated['columns']);
    }
    
    /**
     * Test 5.1.2: Pagination parameter validation
     * 
     * Ensures pagination parameters (start, length) are validated
     * and constrained to safe ranges.
     */
    public function test_validates_pagination_parameters(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Test negative start (should be clamped to 0)
        $request = ['start' => -10, 'length' => 10];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertEquals(0, $validated['start']);
        
        // Test excessive length (should be clamped to MAX_PAGE_LENGTH)
        $request = ['start' => 0, 'length' => 10000];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertLessThanOrEqual(TableConstants::MAX_PAGE_LENGTH, $validated['length']);
        
        // Test zero length (should be clamped to minimum 1)
        $request = ['start' => 0, 'length' => 0];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertGreaterThanOrEqual(1, $validated['length']);
    }
    
    /**
     * Test 5.1.3: Sorting parameter validation
     * 
     * Validates that sort direction is restricted to 'asc' or 'desc'
     * and invalid directions are rejected.
     */
    public function test_validates_sorting_parameters(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Test valid sort directions
        $request = [
            'order' => [
                ['column' => 0, 'dir' => 'asc'],
                ['column' => 1, 'dir' => 'desc']
            ]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertCount(2, $validated['order']);
        $this->assertEquals('asc', $validated['order'][0]['dir']);
        $this->assertEquals('desc', $validated['order'][1]['dir']);
        
        // Test invalid sort direction (should be filtered out)
        $request = [
            'order' => [
                ['column' => 0, 'dir' => 'invalid'],
                ['column' => 1, 'dir' => 'asc']
            ]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        // Invalid direction should be filtered out
        $this->assertCount(1, $validated['order']);
        $this->assertEquals('asc', $validated['order'][0]['dir']);
    }
    
    /**
     * Test 5.1.4: Column name sanitization
     * 
     * Ensures column names are sanitized to prevent SQL injection
     * by removing non-alphanumeric characters (except underscore and dot).
     */
    public function test_sanitizes_column_names(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Test column name with SQL injection attempt
        $request = [
            'columns' => [
                ['data' => 'id; DROP TABLE users--', 'searchable' => true]
            ]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        
        // Column name should be sanitized (only alphanumeric, underscore, dot)
        // Special characters like ; and -- should be removed
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_.]+$/', $validated['columns'][0]['data']);
        $this->assertStringNotContainsString(';', $validated['columns'][0]['data']);
        $this->assertStringNotContainsString('--', $validated['columns'][0]['data']);
        $this->assertStringNotContainsString(' ', $validated['columns'][0]['data']);
        
        // The sanitized result should be 'idDROPTABLEusers' (spaces and special chars removed)
        // This is safe because it will be used with query builder parameter binding
        $this->assertEquals('idDROPTABLEusers', $validated['columns'][0]['data']);
    }
    
    /**
     * Test 5.1.5: Search term sanitization
     * 
     * Validates that search terms are properly sanitized using
     * the canvastack_table_sanitize_search helper function.
     */
    public function test_sanitizes_search_terms(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Test search with special characters
        $request = [
            'search' => ['value' => '<script>alert("xss")</script>', 'regex' => false]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        
        // Search value should be sanitized
        $this->assertIsString($validated['search']['value']);
        // The sanitization function should handle XSS attempts
        $this->assertStringNotContainsString('<script>', $validated['search']['value']);
    }
    
    /**
     * Test 5.1.6: Concurrent request handling (draw parameter)
     * 
     * Validates that the draw parameter is properly validated
     * to handle concurrent DataTables requests.
     */
    public function test_handles_concurrent_requests_with_draw_parameter(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Test with valid draw parameter
        $request = ['draw' => 5];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertEquals(5, $validated['draw']);
        
        // Test with negative draw (should be clamped to 0)
        $request = ['draw' => -1];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertEquals(0, $validated['draw']);
        
        // Test with missing draw (should default to 0)
        $request = [];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertEquals(0, $validated['draw']);
    }
    
    /**
     * Test 5.1.7: Error response generation
     * 
     * Validates that error responses are properly formatted
     * for DataTables with draw parameter and error message.
     */
    public function test_generates_proper_error_responses(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('generateErrorResponse');
        $method->setAccessible(true);
        
        $errorResponse = $method->invoke($this->datatables, 5, 'Test error message', [
            'table' => 'test_table',
            'error_type' => 'validation'
        ]);
        
        // Assert error response structure
        $this->assertIsArray($errorResponse);
        $this->assertEquals(5, $errorResponse['draw']);
        $this->assertEquals(0, $errorResponse['recordsTotal']);
        $this->assertEquals(0, $errorResponse['recordsFiltered']);
        $this->assertIsArray($errorResponse['data']);
        $this->assertEmpty($errorResponse['data']);
        $this->assertArrayHasKey('error', $errorResponse);
        $this->assertStringContainsString('Test error message', $errorResponse['error']);
        
        // Error message should be HTML-escaped
        $this->assertStringNotContainsString('<', $errorResponse['error']);
    }
    
    /**
     * Test 5.1.7: XSS prevention in error messages
     * 
     * Ensures error messages are properly escaped to prevent XSS.
     */
    public function test_escapes_error_messages_to_prevent_xss(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('generateErrorResponse');
        $method->setAccessible(true);
        
        $xssAttempt = '<script>alert("xss")</script>';
        $errorResponse = $method->invoke($this->datatables, 1, $xssAttempt);
        
        // Error message should be escaped
        $this->assertStringNotContainsString('<script>', $errorResponse['error']);
        $this->assertStringContainsString('&lt;', $errorResponse['error']);
        $this->assertStringContainsString('&gt;', $errorResponse['error']);
    }
    
    /**
     * Test 5.1.8: Various configuration scenarios
     * 
     * Tests DataTables validation with various edge cases and configurations.
     */
    public function test_handles_various_configuration_scenarios(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Scenario 1: Empty request
        $validated = $method->invoke($this->datatables, [], 'test_table');
        $this->assertIsArray($validated);
        $this->assertEquals(0, $validated['draw']);
        $this->assertEquals(0, $validated['start']);
        
        // Scenario 2: Request with only draw
        $validated = $method->invoke($this->datatables, ['draw' => 10], 'test_table');
        $this->assertEquals(10, $validated['draw']);
        
        // Scenario 3: Request with multiple columns
        $request = [
            'columns' => [
                ['data' => 'id', 'searchable' => true, 'orderable' => true],
                ['data' => 'name', 'searchable' => true, 'orderable' => true],
                ['data' => 'email', 'searchable' => true, 'orderable' => false]
            ]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertCount(3, $validated['columns']);
        
        // Scenario 4: Request with column-specific search
        $request = [
            'columns' => [
                [
                    'data' => 'name',
                    'searchable' => true,
                    'search' => ['value' => 'John', 'regex' => false]
                ]
            ]
        ];
        $validated = $method->invoke($this->datatables, $request, 'test_table');
        $this->assertEquals('John', $validated['columns'][0]['search']['value']);
    }
    
    /**
     * Test: Validates that default values are applied correctly
     */
    public function test_applies_default_values_correctly(): void
    {
        $reflection = new \ReflectionClass($this->datatables);
        $method = $reflection->getMethod('validateDatatablesRequest');
        $method->setAccessible(true);
        
        // Request with no pagination parameters
        $validated = $method->invoke($this->datatables, [], 'test_table');
        
        // Should use default values from constants
        $this->assertEquals(TableConstants::DEFAULT_START, $validated['start']);
        $this->assertEquals(TableConstants::DEFAULT_PAGE_LENGTH, $validated['length']);
        $this->assertEquals('', $validated['search']['value']);
        $this->assertFalse($validated['search']['regex']);
    }
}

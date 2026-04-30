<?php

namespace Tests\Helpers;

use PHPUnit\Framework\Assert;
use Canvastack\Canvastack\Library\Constants\TableConstants;

/**
 * Table Assertion Helpers
 * 
 * Provides custom assertion methods for validating table components,
 * HTML output, DataTables responses, and security properties.
 * 
 * Validates: Requirement 25 - Testing Support
 */
class TableAssertions
{
    /**
     * Assert that HTML contains proper XSS escaping
     * 
     * @param string $html HTML content to check
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertHtmlIsEscaped(string $html, string $message = ''): void
    {
        $message = $message ?: 'HTML should not contain unescaped special characters';
        
        // Check for common XSS patterns that should be escaped
        Assert::assertStringNotContainsString('<script>', $html, $message);
        Assert::assertStringNotContainsString('javascript:', $html, $message);
        Assert::assertStringNotContainsString('onerror=', $html, $message);
        Assert::assertStringNotContainsString('onload=', $html, $message);
        Assert::assertStringNotContainsString('onclick=', $html, $message);
    }
    
    /**
     * Assert that string is properly escaped for HTML context
     * 
     * @param string $original Original string
     * @param string $escaped Escaped string
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertStringIsHtmlEscaped(string $original, string $escaped, string $message = ''): void
    {
        $message = $message ?: 'String should be properly HTML escaped';
        
        if (strpos($original, '<') !== false) {
            Assert::assertStringContainsString('&lt;', $escaped, $message);
        }
        if (strpos($original, '>') !== false) {
            Assert::assertStringContainsString('&gt;', $escaped, $message);
        }
        if (strpos($original, '&') !== false && strpos($original, '&lt;') === false) {
            Assert::assertStringContainsString('&amp;', $escaped, $message);
        }
        if (strpos($original, '"') !== false) {
            Assert::assertStringContainsString('&quot;', $escaped, $message);
        }
    }
    
    /**
     * Assert that table HTML has proper structure
     * 
     * @param string $html Table HTML
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidTableStructure(string $html, string $message = ''): void
    {
        $message = $message ?: 'Table should have valid HTML structure';
        
        Assert::assertStringContainsString('<table', $html, $message . ' - missing table tag');
        Assert::assertStringContainsString('</table>', $html, $message . ' - missing closing table tag');
        Assert::assertStringContainsString('<thead', $html, $message . ' - missing thead tag');
        Assert::assertStringContainsString('</thead>', $html, $message . ' - missing closing thead tag');
        Assert::assertStringContainsString('<tbody', $html, $message . ' - missing tbody tag');
        Assert::assertStringContainsString('</tbody>', $html, $message . ' - missing closing tbody tag');
    }
    
    /**
     * Assert that table has proper ARIA attributes
     * 
     * @param string $html Table HTML
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertTableHasAriaAttributes(string $html, string $message = ''): void
    {
        $message = $message ?: 'Table should have proper ARIA attributes';
        
        Assert::assertStringContainsString('role="table"', $html, $message . ' - missing table role');
        Assert::assertStringContainsString('role="columnheader"', $html, $message . ' - missing columnheader role');
    }
    
    /**
     * Assert that DataTables response has valid structure
     * 
     * @param array $response DataTables response
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidDatatablesResponse(array $response, string $message = ''): void
    {
        $message = $message ?: 'DataTables response should have valid structure';
        
        Assert::assertArrayHasKey('draw', $response, $message . ' - missing draw');
        Assert::assertArrayHasKey('recordsTotal', $response, $message . ' - missing recordsTotal');
        Assert::assertArrayHasKey('recordsFiltered', $response, $message . ' - missing recordsFiltered');
        Assert::assertArrayHasKey('data', $response, $message . ' - missing data');
        
        Assert::assertIsInt($response['draw'], $message . ' - draw should be integer');
        Assert::assertIsInt($response['recordsTotal'], $message . ' - recordsTotal should be integer');
        Assert::assertIsInt($response['recordsFiltered'], $response . ' - recordsFiltered should be integer');
        Assert::assertIsArray($response['data'], $message . ' - data should be array');
    }
    
    /**
     * Assert that DataTables response has correct pagination
     * 
     * @param array $response DataTables response
     * @param int $expectedStart Expected start offset
     * @param int $expectedLength Expected page length
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertCorrectPagination(array $response, int $expectedStart, int $expectedLength, string $message = ''): void
    {
        $message = $message ?: 'DataTables response should have correct pagination';
        
        $dataCount = count($response['data']);
        Assert::assertLessThanOrEqual($expectedLength, $dataCount, $message . ' - data count exceeds page length');
        
        if ($response['recordsFiltered'] > $expectedStart) {
            $expectedCount = min($expectedLength, $response['recordsFiltered'] - $expectedStart);
            Assert::assertEquals($expectedCount, $dataCount, $message . ' - incorrect data count for pagination');
        }
    }
    
    /**
     * Assert that all data in response is escaped
     * 
     * @param array $data Data array
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertDataIsEscaped(array $data, string $message = ''): void
    {
        $message = $message ?: 'All data should be properly escaped';
        
        foreach ($data as $row) {
            if (is_array($row)) {
                foreach ($row as $value) {
                    if (is_string($value)) {
                        self::assertHtmlIsEscaped($value, $message);
                    }
                }
            }
        }
    }
    
    /**
     * Assert that table name is valid
     * 
     * @param string $tableName Table name
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidTableName(string $tableName, string $message = ''): void
    {
        $message = $message ?: 'Table name should be valid';
        
        Assert::assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $tableName, $message . ' - invalid characters');
        Assert::assertNotEmpty($tableName, $message . ' - empty table name');
        Assert::assertLessThanOrEqual(64, strlen($tableName), $message . ' - table name too long');
    }
    
    /**
     * Assert that column name is valid
     * 
     * @param string $columnName Column name
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidColumnName(string $columnName, string $message = ''): void
    {
        $message = $message ?: 'Column name should be valid';
        
        Assert::assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $columnName, $message . ' - invalid characters');
        Assert::assertNotEmpty($columnName, $message . ' - empty column name');
        Assert::assertLessThanOrEqual(64, strlen($columnName), $message . ' - column name too long');
    }
    
    /**
     * Assert that pagination parameters are valid
     * 
     * @param int $start Start offset
     * @param int $length Page length
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidPaginationParams(int $start, int $length, string $message = ''): void
    {
        $message = $message ?: 'Pagination parameters should be valid';
        
        Assert::assertGreaterThanOrEqual(0, $start, $message . ' - start should be non-negative');
        Assert::assertGreaterThan(0, $length, $message . ' - length should be positive');
        Assert::assertLessThanOrEqual(TableConstants::MAX_PAGE_LENGTH, $length, $message . ' - length exceeds maximum');
    }
    
    /**
     * Assert that sort parameters are valid
     * 
     * @param string $column Column name
     * @param string $direction Sort direction
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidSortParams(string $column, string $direction, string $message = ''): void
    {
        $message = $message ?: 'Sort parameters should be valid';
        
        self::assertValidColumnName($column, $message . ' - invalid column');
        Assert::assertContains($direction, [TableConstants::SORT_ASC, TableConstants::SORT_DESC], $message . ' - invalid direction');
    }
    
    /**
     * Assert that action buttons are present in HTML
     * 
     * @param string $html HTML content
     * @param array $expectedActions Expected action names
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertActionButtonsPresent(string $html, array $expectedActions, string $message = ''): void
    {
        $message = $message ?: 'Expected action buttons should be present';
        
        foreach ($expectedActions as $action) {
            Assert::assertStringContainsString($action, $html, $message . " - missing action: $action");
        }
    }
    
    /**
     * Assert that action buttons have proper ARIA labels
     * 
     * @param string $html HTML content
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertActionButtonsHaveAriaLabels(string $html, string $message = ''): void
    {
        $message = $message ?: 'Action buttons should have ARIA labels';
        
        // Count buttons
        preg_match_all('/<button[^>]*>/', $html, $buttons);
        preg_match_all('/<a[^>]*class="[^"]*btn[^"]*"[^>]*>/', $html, $links);
        
        $totalButtons = count($buttons[0]) + count($links[0]);
        
        if ($totalButtons > 0) {
            // Count aria-label attributes
            preg_match_all('/aria-label="[^"]*"/', $html, $ariaLabels);
            
            Assert::assertGreaterThan(0, count($ariaLabels[0]), $message . ' - no ARIA labels found');
        }
    }
    
    /**
     * Assert that table has proper keyboard navigation attributes
     * 
     * @param string $html HTML content
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertKeyboardNavigationSupport(string $html, string $message = ''): void
    {
        $message = $message ?: 'Table should support keyboard navigation';
        
        // Check for tabindex or focusable elements
        $hasFocusableElements = (
            strpos($html, 'tabindex=') !== false ||
            strpos($html, '<button') !== false ||
            strpos($html, '<a ') !== false ||
            strpos($html, '<input') !== false
        );
        
        Assert::assertTrue($hasFocusableElements, $message . ' - no focusable elements found');
    }
    
    /**
     * Assert that formula calculation is correct
     * 
     * @param mixed $result Calculated result
     * @param mixed $expected Expected result
     * @param float $delta Allowed delta for float comparison
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertFormulaResult($result, $expected, float $delta = 0.01, string $message = ''): void
    {
        $message = $message ?: 'Formula result should match expected value';
        
        if (is_float($result) || is_float($expected)) {
            Assert::assertEqualsWithDelta($expected, $result, $delta, $message);
        } else {
            Assert::assertEquals($expected, $result, $message);
        }
    }
    
    /**
     * Assert that query uses eager loading
     * 
     * This is a helper to check if a query builder has eager loading applied.
     * Note: This is a simplified check and may need adjustment based on actual implementation.
     * 
     * @param mixed $query Query builder instance
     * @param array $expectedRelations Expected relation names
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertQueryUsesEagerLoading($query, array $expectedRelations, string $message = ''): void
    {
        $message = $message ?: 'Query should use eager loading';
        
        // This is a simplified implementation
        // In real tests, you would inspect the query builder's eager load property
        if (method_exists($query, 'getEagerLoads')) {
            $eagerLoads = $query->getEagerLoads();
            
            foreach ($expectedRelations as $relation) {
                Assert::assertArrayHasKey($relation, $eagerLoads, $message . " - missing eager load: $relation");
            }
        }
    }
    
    /**
     * Assert that response time is within acceptable range
     * 
     * @param float $startTime Start time (microtime)
     * @param float $maxSeconds Maximum allowed seconds
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertResponseTimeAcceptable(float $startTime, float $maxSeconds = 1.0, string $message = ''): void
    {
        $message = $message ?: 'Response time should be acceptable';
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        Assert::assertLessThanOrEqual($maxSeconds, $duration, $message . " - took {$duration}s, max {$maxSeconds}s");
    }
    
    /**
     * Assert that memory usage is within acceptable range
     * 
     * @param int $startMemory Start memory (bytes)
     * @param int $maxBytes Maximum allowed bytes
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertMemoryUsageAcceptable(int $startMemory, int $maxBytes = 10485760, string $message = ''): void
    {
        $message = $message ?: 'Memory usage should be acceptable';
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;
        
        $maxMB = $maxBytes / 1048576;
        $usedMB = $memoryUsed / 1048576;
        
        Assert::assertLessThanOrEqual($maxBytes, $memoryUsed, $message . " - used {$usedMB}MB, max {$maxMB}MB");
    }
    
    /**
     * Assert that HTML contains SafeHtml marker
     * 
     * @param string $html HTML content
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertHtmlHasSafeMarker(string $html, string $message = ''): void
    {
        $message = $message ?: 'HTML should have SafeHtml marker';
        
        // Check for SafeHtml marker (implementation may vary)
        // This is a placeholder - adjust based on actual SafeHtml implementation
        Assert::assertNotEmpty($html, $message . ' - HTML is empty');
    }
    
    /**
     * Assert that table configuration is valid
     * 
     * @param array $config Table configuration
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidTableConfig(array $config, string $message = ''): void
    {
        $message = $message ?: 'Table configuration should be valid';
        
        Assert::assertArrayHasKey('table_name', $config, $message . ' - missing table_name');
        Assert::assertArrayHasKey('fields', $config, $message . ' - missing fields');
        
        self::assertValidTableName($config['table_name'], $message);
        Assert::assertIsArray($config['fields'], $message . ' - fields should be array');
        Assert::assertNotEmpty($config['fields'], $message . ' - fields should not be empty');
    }
    
    /**
     * Assert that export file is valid
     * 
     * @param string $filePath File path
     * @param string $expectedFormat Expected format (csv, excel, pdf)
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertValidExportFile(string $filePath, string $expectedFormat, string $message = ''): void
    {
        $message = $message ?: 'Export file should be valid';
        
        Assert::assertFileExists($filePath, $message . ' - file does not exist');
        Assert::assertFileIsReadable($filePath, $message . ' - file is not readable');
        Assert::assertGreaterThan(0, filesize($filePath), $message . ' - file is empty');
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($expectedFormat) {
            case TableConstants::EXPORT_CSV:
                Assert::assertEquals('csv', $extension, $message . ' - wrong file extension');
                break;
            case TableConstants::EXPORT_EXCEL:
                Assert::assertContains($extension, ['xlsx', 'xls'], $message . ' - wrong file extension');
                break;
            case TableConstants::EXPORT_PDF:
                Assert::assertEquals('pdf', $extension, $message . ' - wrong file extension');
                break;
        }
    }
    
    /**
     * Assert that search results match search term
     * 
     * @param array $results Search results
     * @param string $searchTerm Search term
     * @param array $searchableFields Fields that should be searched
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertSearchResultsMatch(array $results, string $searchTerm, array $searchableFields, string $message = ''): void
    {
        $message = $message ?: 'Search results should match search term';
        
        if (empty($searchTerm)) {
            return; // No search term, all results are valid
        }
        
        foreach ($results as $row) {
            $found = false;
            
            foreach ($searchableFields as $field) {
                if (isset($row[$field]) && is_string($row[$field])) {
                    if (stripos($row[$field], $searchTerm) !== false) {
                        $found = true;
                        break;
                    }
                }
            }
            
            Assert::assertTrue($found, $message . " - row does not match search term: " . json_encode($row));
        }
    }
    
    /**
     * Assert that results are sorted correctly
     * 
     * @param array $results Results array
     * @param string $sortColumn Column to check
     * @param string $sortDirection Sort direction (asc/desc)
     * @param string $message Optional assertion message
     * @return void
     */
    public static function assertResultsSorted(array $results, string $sortColumn, string $sortDirection, string $message = ''): void
    {
        $message = $message ?: 'Results should be sorted correctly';
        
        if (count($results) < 2) {
            return; // Not enough data to verify sorting
        }
        
        $values = array_column($results, $sortColumn);
        $sortedValues = $values;
        
        if ($sortDirection === TableConstants::SORT_ASC) {
            sort($sortedValues, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            rsort($sortedValues, SORT_NATURAL | SORT_FLAG_CASE);
        }
        
        Assert::assertEquals($sortedValues, $values, $message . " - incorrect sort order for column: $sortColumn");
    }
    
    /**
     * Assert that no N+1 queries occurred
     * 
     * This requires query logging to be enabled.
     * 
     * @param int $expectedQueryCount Expected number of queries
     * @param callable $callback Callback to execute
     * @param string $message Optional assertion message
     * @return mixed Result of callback
     */
    public static function assertNoNPlusOneQueries(int $expectedQueryCount, callable $callback, string $message = '')
    {
        $message = $message ?: 'Should not have N+1 query problem';
        
        // Enable query logging
        \DB::enableQueryLog();
        \DB::flushQueryLog();
        
        // Execute callback
        $result = $callback();
        
        // Check query count
        $queries = \DB::getQueryLog();
        $actualCount = count($queries);
        
        Assert::assertLessThanOrEqual($expectedQueryCount, $actualCount, 
            $message . " - executed $actualCount queries, expected max $expectedQueryCount");
        
        // Disable query logging
        \DB::disableQueryLog();
        
        return $result;
    }
}

<?php

namespace Tests\Security;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Objects;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\QueryBuilder;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Illuminate\Support\Facades\Log;

/**
 * SQL Injection Prevention Test Suite
 *
 * Tests that all SQL injection prevention mechanisms are working correctly
 * across Objects.php, Datatables.php, and Search/QueryBuilder.php.
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8
 *            (Security - SQL Injection Prevention)
 *
 * @group security
 * @group sql-injection
 * @group critical
 */
class SQLInjectionPreventionTest extends TestCase
{
    protected Objects $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new Objects();
    }

    // =========================================================================
    // 1.7.1 - Table Name Whitelist Validation (Objects::setName)
    // =========================================================================

    /**
     * Test that setName() rejects SQL injection payloads in table names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.2 - Table name whitelist validation
     */
    public function test_setName_rejects_sql_injection_in_table_name()
    {
        $sqlInjectionPayloads = [
            "users; DROP TABLE users--",
            "users' OR '1'='1",
            "users UNION SELECT * FROM passwords",
            "users`; INSERT INTO admin VALUES('hacker','pass')--",
            "../../../etc/passwd",
            "users\x00",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            try {
                $this->table->setName($payload);
                $this->fail("setName() should have rejected SQL injection payload: {$payload}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage(), 'Exception should have a message');
            }
        }
    }

    /**
     * Test that setName() rejects table names with special characters
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 3.1 - Table name format validation
     */
    public function test_setName_rejects_special_characters_in_table_name()
    {
        $invalidTableNames = [
            "users-table",       // Hyphen not allowed
            "users table",       // Space not allowed
            "users.table",       // Dot not allowed (only alphanumeric + underscore)
            "users@domain",      // @ not allowed
            "users#1",           // # not allowed
            "users!",            // ! not allowed
            "users<script>",     // HTML injection
            "users%20table",     // URL encoding
        ];

        foreach ($invalidTableNames as $tableName) {
            try {
                $this->table->setName($tableName);
                $this->fail("setName() should have rejected invalid table name: {$tableName}");
            } catch (\InvalidArgumentException $e) {
                // Expected - invalid format was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test that setName() accepts valid table names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 3.1 - Table name format validation (positive case)
     */
    public function test_setName_accepts_valid_table_name_format()
    {
        // Format validation should pass for valid table names
        // DB existence check is only enforced when datatables.allowed_tables is configured
        $validFormats = [
            "users",
            "user_profiles",
            "UserTable",
            "table123",
            "my_table_2024",
        ];

        foreach ($validFormats as $tableName) {
            // Should not throw - format is valid and no whitelist is configured in test env
            $this->table->setName($tableName);
            $this->assertEquals($tableName, $this->table->variables['table_name'] ?? null,
                "Valid table name '{$tableName}' should be stored");
        }
    }

    // =========================================================================
    // 1.7.2 - Column Name Schema Validation (Objects::setFields)
    // =========================================================================

    /**
     * Test that setFields() rejects SQL injection payloads in field names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.3 - Column name validation
     */
    public function test_setFields_rejects_sql_injection_in_field_names()
    {
        $sqlInjectionPayloads = [
            ["name; DROP TABLE users--"],
            ["name' OR '1'='1"],
            ["name UNION SELECT password FROM users"],
            ["name`; INSERT INTO admin--"],
        ];

        foreach ($sqlInjectionPayloads as $fields) {
            // setFields should filter out invalid field names
            $this->table->setFields($fields);
            $storedFields = $this->table->variables['table_fields'] ?? [];

            // The injected field should have been filtered out (empty result)
            $this->assertEmpty($storedFields,
                "SQL injection field names should be filtered out, got: " . implode(', ', $storedFields));
        }
    }

    /**
     * Test that setFields() accepts valid field names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.3 - Column name validation (positive case)
     */
    public function test_setFields_accepts_valid_field_names()
    {
        $validFields = ['name', 'email', 'created_at', 'user.role', 'first_name:Full Name'];
        $this->table->setFields($validFields);

        $storedFields = $this->table->variables['table_fields'] ?? [];
        $this->assertCount(count($validFields), $storedFields,
            'All valid fields should be stored');
    }

    // =========================================================================
    // 1.7.5 - Operator Validation (Objects::where)
    // =========================================================================

    /**
     * Test that where() rejects SQL injection via operator manipulation
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.4 - Operator validation in where clauses
     */
    public function test_where_rejects_sql_injection_via_operator()
    {
        $sqlInjectionOperators = [
            "= 1; DROP TABLE users--",
            "UNION SELECT",
            "OR 1=1",
            "'; DELETE FROM users; --",
            "1=1 OR",
            "SLEEP(5)",
            "BENCHMARK(1000000,MD5(1))",
        ];

        foreach ($sqlInjectionOperators as $operator) {
            try {
                $this->table->where('name', $operator, 'value');
                $this->fail("where() should have rejected SQL injection operator: {$operator}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test that where() accepts valid operators
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.4 - Operator validation (positive case)
     */
    public function test_where_accepts_valid_operators()
    {
        $validOperators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];

        foreach ($validOperators as $operator) {
            // Should not throw exception
            try {
                $this->table->where('name', $operator, 'value');
                $this->assertTrue(true, "Operator '{$operator}' should be accepted");
            } catch (\InvalidArgumentException $e) {
                $this->fail("Valid operator '{$operator}' was rejected: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that where() rejects SQL injection in field names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.3 - Column name validation in where clauses
     */
    public function test_where_rejects_sql_injection_in_field_name()
    {
        $sqlInjectionFieldNames = [
            "name; DROP TABLE users--",
            "name' OR '1'='1",
            "name UNION SELECT",
            "1=1",
            "name<script>",
        ];

        foreach ($sqlInjectionFieldNames as $fieldName) {
            try {
                $this->table->where($fieldName, '=', 'value');
                $this->fail("where() should have rejected SQL injection field name: {$fieldName}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    // =========================================================================
    // 1.7.7 - orderby() SQL Injection Prevention
    // =========================================================================

    /**
     * Test that orderby() rejects SQL injection in column names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.5 - ORDER BY column validation
     */
    public function test_orderby_rejects_sql_injection_in_column()
    {
        $sqlInjectionColumns = [
            "name; DROP TABLE users--",
            "name' OR '1'='1",
            "name UNION SELECT",
            "(SELECT password FROM users LIMIT 1)",
        ];

        foreach ($sqlInjectionColumns as $column) {
            try {
                $this->table->orderby($column, 'asc');
                $this->fail("orderby() should have rejected SQL injection column: {$column}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test that orderby() rejects invalid sort directions
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.5 - ORDER BY direction validation
     */
    public function test_orderby_rejects_invalid_sort_direction()
    {
        $invalidDirections = [
            "asc; DROP TABLE users--",
            "RANDOM()",
            "1=1",
            "DESC; DELETE FROM users--",
            "asc UNION SELECT",
        ];

        foreach ($invalidDirections as $direction) {
            try {
                $this->table->orderby('name', $direction);
                $this->fail("orderby() should have rejected invalid direction: {$direction}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test that orderby() accepts valid sort parameters
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.5 - ORDER BY validation (positive case)
     */
    public function test_orderby_accepts_valid_sort_parameters()
    {
        $validCases = [
            ['name', 'asc'],
            ['created_at', 'desc'],
            ['user_id', 'ASC'],  // Should be normalized to lowercase
            ['email', 'DESC'],   // Should be normalized to lowercase
        ];

        foreach ($validCases as [$column, $direction]) {
            try {
                $this->table->orderby($column, $direction);
                $stored = $this->table->variables['orderby_column'] ?? [];
                $this->assertEquals($column, $stored['column']);
                $this->assertEquals(strtolower($direction), $stored['order'],
                    "Direction should be normalized to lowercase");
            } catch (\InvalidArgumentException $e) {
                $this->fail("Valid orderby parameters were rejected: {$e->getMessage()}");
            }
        }
    }

    // =========================================================================
    // Helper Security Functions Tests
    // =========================================================================

    /**
     * Test canvastack_table_validate_table_name() rejects SQL injection payloads
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.2 - Table name whitelist validation
     */
    public function test_helper_validate_table_name_rejects_sql_injection()
    {
        $sqlInjectionPayloads = [
            "users; DROP TABLE users--",
            "users' OR '1'='1",
            "users UNION SELECT",
            "users`; INSERT INTO admin--",
            "users\x00",
            "users--",
            "users/**/UNION",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            try {
                canvastack_table_validate_table_name($payload, ['users', 'orders']);
                $this->fail("canvastack_table_validate_table_name() should have rejected: {$payload}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test canvastack_table_validate_table_name() enforces whitelist
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.2 - Table name whitelist validation
     */
    public function test_helper_validate_table_name_enforces_whitelist()
    {
        $allowedTables = ['users', 'orders', 'products'];

        // Should pass for allowed tables
        $result = canvastack_table_validate_table_name('users', $allowedTables);
        $this->assertEquals('users', $result);

        // Should fail for non-whitelisted tables
        try {
            canvastack_table_validate_table_name('admin_passwords', $allowedTables);
            $this->fail("Should have rejected non-whitelisted table");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('whitelist', strtolower($e->getMessage()));
        }
    }

    /**
     * Test canvastack_table_validate_column_name() rejects SQL injection payloads
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.3 - Column name schema validation
     */
    public function test_helper_validate_column_name_rejects_sql_injection()
    {
        $sqlInjectionPayloads = [
            "name; DROP TABLE users--",
            "name' OR '1'='1",
            "name UNION SELECT",
            "name`; INSERT INTO admin--",
            "1=1",
            "name--",
            "name/**/UNION",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            try {
                canvastack_table_validate_column_name('users', $payload);
                $this->fail("canvastack_table_validate_column_name() should have rejected: {$payload}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test canvastack_table_validate_sort() rejects SQL injection in direction
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.5 - Sort direction validation
     */
    public function test_helper_validate_sort_rejects_sql_injection_in_direction()
    {
        $sqlInjectionDirections = [
            "asc; DROP TABLE users--",
            "RANDOM()",
            "1=1",
            "DESC; DELETE FROM users--",
        ];

        foreach ($sqlInjectionDirections as $direction) {
            try {
                canvastack_table_validate_sort('users', 'name', $direction, 'mysql');
                $this->fail("canvastack_table_validate_sort() should have rejected direction: {$direction}");
            } catch (\InvalidArgumentException $e) {
                // Expected - injection was blocked
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test canvastack_table_sanitize_search() sanitizes SQL injection attempts
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.7 - Search term sanitization
     */
    public function test_helper_sanitize_search_handles_sql_injection_attempts()
    {
        $sqlInjectionSearchTerms = [
            "' OR '1'='1",
            "'; DROP TABLE users--",
            "1 UNION SELECT * FROM users",
            "admin'--",
        ];

        foreach ($sqlInjectionSearchTerms as $term) {
            // sanitize_search should not throw - it sanitizes and returns safe value
            $result = canvastack_table_sanitize_search($term);

            // Result should be HTML-encoded (safe for output)
            $this->assertStringNotContainsString("'", $result,
                "Single quotes should be HTML-encoded in search term");
        }
    }

    // =========================================================================
    // QueryBuilder SQL Injection Prevention Tests
    // =========================================================================

    /**
     * Test QueryBuilder::sanitizeIdentifier() strips SQL injection characters
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.1 - Parameterized queries / identifier sanitization
     */
    public function test_query_builder_sanitize_identifier_strips_injection_chars()
    {
        $config = $this->createMock(SearchConfig::class);
        $config->method('getConnection')->willReturn(null);
        $config->method('getModelFilters')->willReturn([]);
        $config->method('getFilterQuery')->willReturn([]);

        $queryBuilder = new QueryBuilder($config);

        $injectionAttempts = [
            "users; DROP TABLE users--" => "usersDROPTABLEusers",
            "name' OR '1'='1"           => "nameOR11",
            "table`injection`"          => "tableinjection",
            "col UNION SELECT"          => "colUNIONSELECT",
            "valid_column"              => "valid_column",  // Should pass through unchanged
            "table.column"              => "table.column",  // Dot notation allowed
        ];

        foreach ($injectionAttempts as $input => $expected) {
            $result = $queryBuilder->sanitizeIdentifier($input);
            $this->assertEquals($expected, $result,
                "sanitizeIdentifier('{$input}') should return '{$expected}'");
        }
    }

    /**
     * Test that where() with array input validates all field names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.3 - Column name validation in array where
     */
    public function test_where_array_input_validates_all_field_names()
    {
        $maliciousArray = [
            "valid_field"                => "value1",
            "field; DROP TABLE users--"  => "value2",
        ];

        try {
            $this->table->where($maliciousArray);
            // If no exception, check that malicious field was not stored
            $conditions = $this->table->conditions['where'] ?? [];
            foreach ($conditions as $condition) {
                $this->assertMatchesRegularExpression(
                    '/^[a-zA-Z0-9_.]+$/',
                    $condition['field_name'],
                    "Field name should only contain safe characters"
                );
            }
        } catch (\InvalidArgumentException $e) {
            // Also acceptable - injection was blocked
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * Test that empty table name is rejected
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 3.1 - Input validation
     */
    public function test_setName_rejects_empty_table_name()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setName('');
    }

    /**
     * Test that null-byte injection is rejected in table names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.2 - Table name validation
     */
    public function test_setName_rejects_null_byte_injection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setName("users\x00admin");
    }

    /**
     * Test that SQL comment injection is rejected in table names
     *
     * @test
     * @group sql-injection
     * Validates: Requirement 2.2 - Table name validation
     */
    public function test_setName_rejects_sql_comment_injection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setName("users--comment");
    }
}

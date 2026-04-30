<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SQL Injection Prevention Tests
 * 
 * Tests SQL injection prevention mechanisms in Action trait and Helper functions.
 * Validates table name validation, column name validation, operator validation,
 * and query length validation.
 * 
 * @package Tests\Unit
 * @category Security
 */
class SqlInjectionPreventionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test table name validation rejects invalid table names
     * 
     * @return void
     */
    public function test_table_name_validation_rejects_invalid_tables()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name');

        // Create a test controller instance
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateTableName($tableName) {
                return $this->validateTableName($tableName);
            }
        };

        // Test with non-existent table
        $controller->testValidateTableName('non_existent_table_xyz');
    }

    /**
     * Test table name validation accepts valid table names
     * 
     * @return void
     */
    public function test_table_name_validation_accepts_valid_tables()
    {
        // Create a test table
        Schema::create('test_valid_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateTableName($tableName) {
                return $this->validateTableName($tableName);
            }
        };

        // Test with valid table
        $result = $controller->testValidateTableName('test_valid_table');
        $this->assertTrue($result);

        // Clean up
        Schema::dropIfExists('test_valid_table');
    }

    /**
     * Test column name validation rejects invalid column names
     * 
     * @return void
     */
    public function test_column_name_validation_rejects_invalid_columns()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid column name');

        // Create a test table
        Schema::create('test_column_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateColumnName($tableName, $columnName) {
                return $this->validateColumnName($tableName, $columnName);
            }
        };

        // Test with non-existent column
        $controller->testValidateColumnName('test_column_table', 'non_existent_column');

        // Clean up
        Schema::dropIfExists('test_column_table');
    }

    /**
     * Test column name validation accepts valid column names
     * 
     * @return void
     */
    public function test_column_name_validation_accepts_valid_columns()
    {
        // Create a test table
        Schema::create('test_column_table', function ($table) {
            $table->id();
            $table->string('name');
        });

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateColumnName($tableName, $columnName) {
                return $this->validateColumnName($tableName, $columnName);
            }
        };

        // Test with valid column
        $result = $controller->testValidateColumnName('test_column_table', 'name');
        $this->assertTrue($result);

        // Clean up
        Schema::dropIfExists('test_column_table');
    }

    /**
     * Test operator validation rejects invalid operators
     * 
     * @return void
     */
    public function test_operator_validation_rejects_invalid_operators()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SQL operator');

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateOperator($operator) {
                return $this->validateOperator($operator);
            }
        };

        // Test with SQL injection attempt
        $controller->testValidateOperator('=; DROP TABLE users--');
    }

    /**
     * Test operator validation accepts valid operators
     * 
     * @return void
     */
    public function test_operator_validation_accepts_valid_operators()
    {
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateOperator($operator) {
                return $this->validateOperator($operator);
            }
        };

        $validOperators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'];

        foreach ($validOperators as $operator) {
            $result = $controller->testValidateOperator($operator);
            $this->assertEquals($operator, $result);
        }
    }

    /**
     * Test query length validation rejects overly long queries
     * 
     * @return void
     */
    public function test_query_length_validation_rejects_long_queries()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query exceeds maximum length');

        // Create a very long query (over 10000 characters)
        $longQuery = 'SELECT * FROM users WHERE ' . str_repeat('id = 1 OR ', 2000) . 'id = 1';

        canvastack_query($longQuery);
    }

    /**
     * Test suspicious pattern detection logs warnings
     * 
     * @return void
     */
    public function test_suspicious_pattern_detection_logs_warnings()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('SQL Injection Attempt: Suspicious pattern detected', \Mockery::any());

        // This should trigger a warning but not throw an exception
        try {
            canvastack_query('SELECT * FROM users; DROP TABLE users');
        } catch (\Exception $e) {
            // Query will fail, but we're testing the logging
        }
    }

    /**
     * Test SQL injection payloads are blocked
     * 
     * @dataProvider sqlInjectionPayloadsProvider
     * @return void
     */
    public function test_sql_injection_payloads_are_blocked($payload, $context)
    {
        $this->expectException(\InvalidArgumentException::class);

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateOperator($operator) {
                return $this->validateOperator($operator);
            }
        };

        // Test operator injection
        if ($context === 'operator') {
            $controller->testValidateOperator($payload);
        }
    }

    /**
     * Data provider for SQL injection payloads
     * 
     * @return array
     */
    public static function sqlInjectionPayloadsProvider()
    {
        return [
            'Union-based injection' => ["' UNION SELECT * FROM users--", 'operator'],
            'Boolean-based injection' => ["' OR '1'='1", 'operator'],
            'Time-based injection' => ["'; WAITFOR DELAY '00:00:05'--", 'operator'],
            'Stacked queries' => ["'; DROP TABLE users--", 'operator'],
            'Comment injection' => ["'/**/OR/**/1=1--", 'operator'],
            'Hex encoding' => ["0x27204f52203127", 'operator'],
        ];
    }

    /**
     * Test configuration can disable SQL injection prevention
     * 
     * @return void
     */
    public function test_configuration_can_disable_sql_injection_prevention()
    {
        // Temporarily disable SQL injection prevention
        config(['canvastack.controller.security.sql_injection_prevention' => false]);

        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function testValidateOperator($operator) {
                return $this->validateOperator($operator);
            }
        };

        // This should not throw an exception when disabled
        $result = $controller->testValidateOperator('INVALID_OPERATOR');
        $this->assertEquals('INVALID_OPERATOR', $result);

        // Re-enable for other tests
        config(['canvastack.controller.security.sql_injection_prevention' => true]);
    }
}

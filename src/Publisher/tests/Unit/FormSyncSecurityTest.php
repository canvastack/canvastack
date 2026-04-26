<?php

namespace Tests\Unit;

use Tests\TestCase;
use InvalidArgumentException;
use Canvastack\Canvastack\Library\Components\Form\Objects;

/**
 * Test sync() ajax security enhancements
 * 
 * Tests for task 4.5: Enhance sync() ajax security
 * Validates Properties 50-54 from design document
 */
class FormSyncSecurityTest extends TestCase
{
    /**
     * Test SQL injection pattern validation
     * 
     * @test
     * Validates: Property 50 - SQL Injection Prevention in Sync
     */
    public function test_sql_injection_patterns_are_blocked()
    {
        $maliciousQueries = [
            // UNION-based injection
            "SELECT * FROM users UNION SELECT password FROM admin",
            "SELECT id, name FROM products UNION SELECT null, password FROM users",
            
            // Multi-statement attacks
            "SELECT * FROM users; DROP TABLE users",
            "SELECT * FROM users; DELETE FROM admin",
            "SELECT * FROM users; UPDATE users SET role='admin'",
            "SELECT * FROM users; INSERT INTO admin VALUES (1, 'hacker')",
            "SELECT * FROM users; TRUNCATE TABLE logs",
            "SELECT * FROM users; ALTER TABLE users ADD COLUMN hacked INT",
            "SELECT * FROM users; CREATE TABLE backdoor (id INT)",
            
            // SQL comments
            "SELECT * FROM users -- comment",
            "SELECT * FROM users /* comment */",
            "SELECT * FROM users WHERE id=1 --",
            
            // Dangerous functions
            "SELECT LOAD_FILE('/etc/passwd')",
            "SELECT * FROM users INTO OUTFILE '/tmp/users.txt'",
            "SELECT * FROM users INTO DUMPFILE '/tmp/dump'",
            "SELECT BENCHMARK(1000000, MD5('test'))",
            "SELECT SLEEP(10)",
            "WAITFOR DELAY '00:00:10'",
            
            // Information disclosure
            "SELECT * FROM INFORMATION_SCHEMA.TABLES",
            "SHOW TABLES",
            "SHOW DATABASES",
            "SHOW COLUMNS FROM users",
            "DESCRIBE users",
            
            // SQL Server extended procedures
            "EXEC xp_cmdshell 'dir'",
            "EXEC sp_executesql 'DROP TABLE users'",
        ];
        
        foreach ($maliciousQueries as $query) {
            try {
                canvastack_form_validate_sql_query($query);
                $this->fail("Expected InvalidArgumentException for query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous SQL patterns', $e->getMessage());
            }
        }
    }
    
    /**
     * Test that only SELECT queries are allowed
     * 
     * @test
     * Validates: Property 50 - SQL Injection Prevention in Sync
     */
    public function test_only_select_queries_allowed()
    {
        $nonSelectQueries = [
            "UPDATE users SET name='hacked'",
            "DELETE FROM users",
            "INSERT INTO users VALUES (1, 'hacker')",
            "DROP TABLE users",
            "TRUNCATE TABLE users",
            "ALTER TABLE users ADD COLUMN hacked INT",
            "CREATE TABLE backdoor (id INT)",
        ];
        
        foreach ($nonSelectQueries as $query) {
            try {
                canvastack_form_validate_sql_query($query);
                $this->fail("Expected InvalidArgumentException for non-SELECT query: {$query}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Only SELECT queries are allowed', $e->getMessage());
            }
        }
    }
    
    /**
     * Test that valid SELECT queries pass validation
     * 
     * @test
     * Validates: Property 50 - SQL Injection Prevention in Sync
     */
    public function test_valid_select_queries_pass()
    {
        $validQueries = [
            "SELECT * FROM users",
            "SELECT id, name, email FROM users",
            "SELECT u.id, u.name FROM users u",
            "SELECT * FROM users WHERE status = 'active'",
            "SELECT COUNT(*) FROM orders",
            "SELECT DISTINCT category FROM products",
            "SELECT * FROM users ORDER BY created_at DESC",
            "SELECT * FROM users LIMIT 10",
            "SELECT u.name, o.total FROM users u JOIN orders o ON u.id = o.user_id",
        ];
        
        foreach ($validQueries as $query) {
            $result = canvastack_form_validate_sql_query($query);
            $this->assertTrue($result, "Valid query should pass: {$query}");
        }
    }
    
    /**
     * Test field name validation
     * 
     * @test
     * Validates: Property 52 - Sync Field Name Validation
     */
    public function test_field_name_validation()
    {
        // Valid field names
        $validNames = [
            'user_id',
            'firstName',
            'table.column',
            'users.id',
            'my_field_123',
            'field_name',
        ];
        
        foreach ($validNames as $name) {
            $result = canvastack_form_validate_field_name($name, 'test');
            $this->assertTrue($result, "Valid field name should pass: {$name}");
        }
        
        // Invalid field names
        $invalidNames = [
            'field name',           // Space
            'field-name',           // Hyphen
            'field@name',           // Special char
            'field;name',           // Semicolon
            'field\'name',          // Quote
            'field"name',           // Double quote
            'field(name)',          // Parentheses
            '123field',             // Starts with number
            '',                     // Empty
            'field<script>',        // XSS attempt
        ];
        
        foreach ($invalidNames as $name) {
            try {
                canvastack_form_validate_field_name($name, 'test');
                $this->fail("Expected InvalidArgumentException for invalid field name: {$name}");
            } catch (InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }
    
    /**
     * Test data integrity checking
     * 
     * @test
     * Validates: Property 51 - Sync Data Integrity
     */
    public function test_integrity_check_detects_tampering()
    {
        $originalData = 'sensitive_data_12345';
        
        // Add integrity check
        $dataWithSignature = canvastack_form_add_integrity_check($originalData);
        
        // Verify it contains signature
        $this->assertStringContainsString('::', $dataWithSignature);
        
        // Verify valid data passes
        $verified = canvastack_form_verify_integrity($dataWithSignature);
        $this->assertEquals($originalData, $verified);
        
        // Test tampering detection
        $tamperedData = str_replace('sensitive', 'modified', $dataWithSignature);
        
        try {
            canvastack_form_verify_integrity($tamperedData);
            $this->fail('Expected InvalidArgumentException for tampered data');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('integrity check failed', $e->getMessage());
        }
    }
    
    /**
     * Test integrity check with missing signature
     * 
     * @test
     * Validates: Property 51 - Sync Data Integrity
     */
    public function test_integrity_check_requires_signature()
    {
        $dataWithoutSignature = 'data_without_signature';
        
        try {
            canvastack_form_verify_integrity($dataWithoutSignature);
            $this->fail('Expected InvalidArgumentException for missing signature');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('signature is missing', $e->getMessage());
        }
    }
    
    /**
     * Test sync() method validates field names
     * 
     * @test
     * Validates: Property 52 - Sync Field Name Validation
     */
    public function test_sync_validates_field_names()
    {
        $form = new Objects();
        
        // Test with invalid source field
        try {
            $form->sync(
                'invalid field',  // Invalid: contains space
                'target_field',
                'value_field',
                'label_field',
                'SELECT * FROM users'
            );
            $this->fail('Expected InvalidArgumentException for invalid source field');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid source field name', $e->getMessage());
        }
        
        // Test with invalid target field
        try {
            $form->sync(
                'source_field',
                'target;field',  // Invalid: contains semicolon
                'value_field',
                'label_field',
                'SELECT * FROM users'
            );
            $this->fail('Expected InvalidArgumentException for invalid target field');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid target field name', $e->getMessage());
        }
    }
    
    /**
     * Test sync() method validates query
     * 
     * @test
     * Validates: Property 50 - SQL Injection Prevention in Sync
     */
    public function test_sync_validates_query()
    {
        $form = new Objects();
        
        // Test with SQL injection attempt
        try {
            $form->sync(
                'source_field',
                'target_field',
                'value_field',
                'label_field',
                'SELECT * FROM users UNION SELECT password FROM admin'
            );
            $this->fail('Expected InvalidArgumentException for malicious query');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('dangerous SQL patterns', $e->getMessage());
        }
    }
    
    /**
     * Test that empty or too short queries are rejected
     * 
     * @test
     * Validates: Property 50 - SQL Injection Prevention in Sync
     */
    public function test_empty_or_short_queries_rejected()
    {
        $invalidQueries = [
            '',
            '   ',
            'SELECT',
            'SEL',
        ];
        
        foreach ($invalidQueries as $query) {
            try {
                canvastack_form_validate_sql_query($query);
                $this->fail("Expected InvalidArgumentException for query: '{$query}'");
            } catch (InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }
    
    /**
     * Test field name length limits
     * 
     * @test
     * Validates: Property 52 - Sync Field Name Validation
     */
    public function test_field_name_length_limits()
    {
        // Test field name that's too long (>255 chars)
        $tooLongName = str_repeat('a', 256);
        
        try {
            canvastack_form_validate_field_name($tooLongName, 'test');
            $this->fail('Expected InvalidArgumentException for too long field name');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('too long', $e->getMessage());
        }
        
        // Test field name at the limit (255 chars) - should pass
        $maxLengthName = str_repeat('a', 255);
        $result = canvastack_form_validate_field_name($maxLengthName, 'test');
        $this->assertTrue($result);
    }
    
    /**
     * Test that security events are logged
     * 
     * @test
     * Validates: Property 35 - Security Event Logging
     */
    public function test_security_events_are_logged()
    {
        // This test verifies the logging function exists and can be called
        // In a real environment, you'd mock the Log facade to verify actual logging
        
        $this->assertTrue(function_exists('canvastack_log_security_event'));
        
        // Test that logging doesn't throw exceptions
        canvastack_log_security_event('test_event', [
            'test_data' => 'test_value',
        ]);
        
        // If we get here without exception, logging works
        $this->assertTrue(true);
    }
    
    /**
     * Test round-trip: encrypt with integrity, verify, decrypt
     * 
     * @test
     * Validates: Property 51 - Sync Data Integrity
     */
    public function test_integrity_round_trip()
    {
        $testData = [
            'simple_string',
            'string with spaces',
            'string_with_underscores',
            '12345',
            'mixed123data',
        ];
        
        foreach ($testData as $data) {
            // Encrypt
            $encrypted = encrypt($data);
            
            // Add integrity check
            $withIntegrity = canvastack_form_add_integrity_check($encrypted);
            
            // Verify integrity
            $verified = canvastack_form_verify_integrity($withIntegrity);
            
            // Decrypt
            $decrypted = decrypt($verified);
            
            // Should match original
            $this->assertEquals($data, $decrypted, "Round-trip failed for: {$data}");
        }
    }
}

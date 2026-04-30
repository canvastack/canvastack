<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * XSS Protection Tests for Action.php
 * 
 * Tests that all user-controllable data in CRUD operations is properly escaped
 * to prevent XSS attacks.
 * 
 * Validates: Task 1.4 - Fix XSS Vulnerabilities in Action.php
 * 
 * @package Tests\Unit
 */
class ActionXssProtectionTest extends TestCase
{
    /**
     * Common XSS payloads for testing
     */
    private array $xssPayloads = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '<svg onload=alert("XSS")>',
        'javascript:alert("XSS")',
        '<iframe src="javascript:alert(\'XSS\')">',
        '<body onload=alert("XSS")>',
        '<input onfocus=alert("XSS") autofocus>',
        '<select onfocus=alert("XSS") autofocus>',
        '<textarea onfocus=alert("XSS") autofocus>',
        '<marquee onstart=alert("XSS")>',
        '"><script>alert(String.fromCharCode(88,83,83))</script>',
        '\';alert(String.fromCharCode(88,83,83))//\';',
        '<IMG SRC="javascript:alert(\'XSS\');">',
        '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">',
        '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>',
    ];
    
    /**
     * Create a mock controller with Action trait
     */
    private function createMockController()
    {
        return new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Action;
            
            public function __construct()
            {
                // Initialize required properties
                $this->model_table = 'test_table';
                $this->validations = [];
            }
            
            // Expose private methods for testing
            public function testEscapeRouteParameter($parameter)
            {
                return $this->escapeRouteParameter($parameter);
            }
            
            public function testEscapeValidationMessages($messages)
            {
                return $this->escapeValidationMessages($messages);
            }
            
            public function testEscapeRedirectMessage($messageData)
            {
                return $this->escapeRedirectMessage($messageData);
            }
        };
    }
    
    /**
     * Test that route parameters are properly escaped
     * 
     * @test
     */
    public function test_route_parameters_are_escaped()
    {
        $controller = $this->createMockController();
        
        foreach ($this->xssPayloads as $payload) {
            $escaped = $controller->testEscapeRouteParameter($payload);
            
            // Verify that dangerous HTML tags are escaped
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            
            // Note: javascript: protocol is not escaped by htmlspecialchars
            // It should be validated at the URL validation level, not here
            
            // Verify that HTML entities are used for HTML special characters
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped);
            }
        }
    }
    
    /**
     * Test that validation error messages are properly escaped
     * 
     * @test
     */
    public function test_validation_messages_are_escaped()
    {
        $controller = $this->createMockController();
        
        foreach ($this->xssPayloads as $payload) {
            $messages = [
                'field1' => $payload,
                'field2' => [$payload, 'Another message'],
                'nested' => [
                    'field3' => $payload
                ]
            ];
            
            $escaped = $controller->testEscapeValidationMessages($messages);
            
            // Verify that all messages are escaped
            $this->assertStringNotContainsString('<script>', $escaped['field1']);
            $this->assertStringNotContainsString('<img', $escaped['field2'][0]);
            $this->assertStringNotContainsString('<script>', $escaped['nested']['field3']);
            
            // Verify HTML entities are used
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped['field1']);
            }
        }
    }
    
    /**
     * Test that redirect messages are properly escaped
     * 
     * @test
     */
    public function test_redirect_messages_are_escaped()
    {
        $controller = $this->createMockController();
        
        foreach ($this->xssPayloads as $payload) {
            $messageData = [
                'success' => $payload,
                'errors' => [
                    'field1' => $payload,
                    'field2' => $payload
                ]
            ];
            
            $escaped = $controller->testEscapeRedirectMessage($messageData);
            
            // Verify that all messages are escaped
            $this->assertStringNotContainsString('<script>', $escaped['success']);
            $this->assertStringNotContainsString('<img', $escaped['errors']['field1']);
            $this->assertStringNotContainsString('<script>', $escaped['errors']['field2']);
            
            // Verify HTML entities are used
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped['success']);
            }
        }
    }
    
    /**
     * Test that numeric values are not escaped
     * 
     * @test
     */
    public function test_numeric_values_are_not_escaped()
    {
        $controller = $this->createMockController();
        
        $numericValues = [123, 456, 0, -1, 3.14];
        
        foreach ($numericValues as $value) {
            $escaped = $controller->testEscapeRouteParameter($value);
            
            // Numeric values should remain unchanged
            $this->assertSame($value, $escaped);
        }
    }
    
    /**
     * Test that boolean values are not escaped
     * 
     * @test
     */
    public function test_boolean_values_are_not_escaped()
    {
        $controller = $this->createMockController();
        
        $boolValues = [true, false];
        
        foreach ($boolValues as $value) {
            $escaped = $controller->testEscapeRouteParameter($value);
            
            // Boolean values should remain unchanged
            $this->assertSame($value, $escaped);
        }
    }
    
    /**
     * Test that null values are handled correctly
     * 
     * @test
     */
    public function test_null_values_are_handled()
    {
        $controller = $this->createMockController();
        
        $escaped = $controller->testEscapeRouteParameter(null);
        
        // Null should remain null
        $this->assertNull($escaped);
    }
    
    /**
     * Test that empty strings are handled correctly
     * 
     * @test
     */
    public function test_empty_strings_are_handled()
    {
        $controller = $this->createMockController();
        
        $escaped = $controller->testEscapeRouteParameter('');
        
        // Empty string should remain empty
        $this->assertSame('', $escaped);
    }
    
    /**
     * Test that special characters are properly encoded
     * 
     * @test
     */
    public function test_special_characters_are_encoded()
    {
        $controller = $this->createMockController();
        
        $testCases = [
            'quotes' => 'Test "double" and \'single\' quotes',
            'ampersand' => 'Test & ampersand',
            'less_than' => 'Test < less than',
            'greater_than' => 'Test > greater than',
        ];
        
        foreach ($testCases as $name => $value) {
            $escaped = $controller->testEscapeRouteParameter($value);
            
            // Verify special characters are encoded
            switch ($name) {
                case 'quotes':
                    $this->assertStringContainsString('&quot;', $escaped);
                    $this->assertStringContainsString('&#039;', $escaped);
                    break;
                case 'ampersand':
                    $this->assertStringContainsString('&amp;', $escaped);
                    break;
                case 'less_than':
                    $this->assertStringContainsString('&lt;', $escaped);
                    break;
                case 'greater_than':
                    $this->assertStringContainsString('&gt;', $escaped);
                    break;
            }
        }
    }
    
    /**
     * Test that nested arrays are properly escaped
     * 
     * @test
     */
    public function test_nested_arrays_are_escaped()
    {
        $controller = $this->createMockController();
        
        $payload = '<script>alert("XSS")</script>';
        
        $messages = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => $payload
                    ]
                ]
            ]
        ];
        
        $escaped = $controller->testEscapeValidationMessages($messages);
        
        // Verify deeply nested values are escaped
        $this->assertStringNotContainsString('<script>', $escaped['level1']['level2']['level3']['value']);
        $this->assertStringContainsString('&lt;script&gt;', $escaped['level1']['level2']['level3']['value']);
    }
    
    /**
     * Test that array keys are also escaped
     * 
     * @test
     */
    public function test_array_keys_are_escaped()
    {
        $controller = $this->createMockController();
        
        $payload = '<script>alert("XSS")</script>';
        
        $messages = [
            $payload => 'Some value',
            'normal_key' => $payload
        ];
        
        $escaped = $controller->testEscapeValidationMessages($messages);
        
        // Verify that keys are escaped
        $keys = array_keys($escaped);
        foreach ($keys as $key) {
            $this->assertStringNotContainsString('<script>', $key);
        }
    }
    
    /**
     * Test that mixed data types in arrays are handled correctly
     * 
     * @test
     */
    public function test_mixed_data_types_in_arrays()
    {
        $controller = $this->createMockController();
        
        $payload = '<script>alert("XSS")</script>';
        
        $messages = [
            'string' => $payload,
            'number' => 123,
            'boolean' => true,
            'null' => null,
            'array' => [$payload, 456, false]
        ];
        
        $escaped = $controller->testEscapeValidationMessages($messages);
        
        // Verify strings are escaped
        $this->assertStringNotContainsString('<script>', $escaped['string']);
        
        // Verify non-strings remain unchanged
        $this->assertSame(123, $escaped['number']);
        $this->assertSame(true, $escaped['boolean']);
        $this->assertNull($escaped['null']);
        
        // Verify array values are handled correctly
        $this->assertStringNotContainsString('<script>', $escaped['array'][0]);
        $this->assertSame(456, $escaped['array'][1]);
        $this->assertSame(false, $escaped['array'][2]);
    }
}

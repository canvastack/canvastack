<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Controllers\Core\Controller;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * XSS Protection Tests for Controller.php
 * 
 * Tests that all user-controllable data is properly escaped to prevent XSS attacks.
 * 
 * @package Tests\Unit
 */
class ControllerXssProtectionTest extends TestCase
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
     * Test that session data is properly escaped
     * 
     * @return void
     */
    public function test_session_data_is_escaped()
    {
        // Create controller instance
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escapeSessionData');
        $method->setAccessible(true);
        
        foreach ($this->xssPayloads as $payload) {
            $sessionData = [
                'username' => $payload,
                'fullname' => $payload,
                'email' => $payload,
                'nested' => [
                    'value' => $payload
                ]
            ];
            
            $escaped = $method->invoke($controller, $sessionData);
            
            // Verify that dangerous HTML tags are escaped
            $this->assertStringNotContainsString('<script>', $escaped['username']);
            $this->assertStringNotContainsString('<img', $escaped['fullname']);
            $this->assertStringNotContainsString('<script>', $escaped['nested']['value']);
            
            // Verify that HTML entities are used for payloads with < character
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped['username']);
            }
        }
    }
    
    /**
     * Test that route info is properly escaped
     * 
     * @return void
     */
    public function test_route_info_is_escaped()
    {
        foreach ($this->xssPayloads as $payload) {
            // Create controller instance
            $controller = $this->getMockBuilder(Controller::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            // Use reflection to access private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('escapeRouteInfo');
            $method->setAccessible(true);
            
            $routeInfo = (object) [
                'module_name' => $payload,
                'page_info' => $payload,
                'action_page' => [
                    "primary|{$payload}" => "http://example.com/{$payload}"
                ]
            ];
            
            $escaped = $method->invoke($controller, $routeInfo);
            
            // Verify that dangerous characters are escaped
            $this->assertStringNotContainsString('<script>', $escaped->module_name);
            $this->assertStringNotContainsString('<img', $escaped->page_info);
            
            // Verify action page labels are escaped
            foreach ($escaped->action_page as $label => $url) {
                $this->assertStringNotContainsString('<script>', $label);
                $this->assertStringNotContainsString('<img', $label);
            }
        }
    }
    
    /**
     * Test that numeric and boolean values are not escaped
     * 
     * @return void
     */
    public function test_non_string_values_are_not_escaped()
    {
        // Create controller instance
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escapeSessionData');
        $method->setAccessible(true);
        
        $sessionData = [
            'id' => 123,
            'group_id' => 456,
            'flag' => true,
            'count' => 0,
        ];
        
        $escaped = $method->invoke($controller, $sessionData);
        
        // Verify that non-string values remain unchanged
        $this->assertSame(123, $escaped['id']);
        $this->assertSame(456, $escaped['group_id']);
        $this->assertSame(true, $escaped['flag']);
        $this->assertSame(0, $escaped['count']);
    }
    
    /**
     * Test that nested arrays are properly escaped
     * 
     * @return void
     */
    public function test_nested_arrays_are_escaped()
    {
        // Create controller instance
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escapeSessionData');
        $method->setAccessible(true);
        
        $payload = '<script>alert("XSS")</script>';
        
        $sessionData = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => $payload
                    ]
                ]
            ]
        ];
        
        $escaped = $method->invoke($controller, $sessionData);
        
        // Verify that deeply nested values are escaped
        $this->assertStringNotContainsString('<script>', $escaped['level1']['level2']['level3']['value']);
        $this->assertStringContainsString('&lt;script&gt;', $escaped['level1']['level2']['level3']['value']);
    }
    
    /**
     * Test that special characters are properly encoded
     * 
     * @return void
     */
    public function test_special_characters_are_encoded()
    {
        // Create controller instance
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escapeSessionData');
        $method->setAccessible(true);
        
        $sessionData = [
            'quotes' => 'Test "double" and \'single\' quotes',
            'ampersand' => 'Test & ampersand',
            'less_than' => 'Test < less than',
            'greater_than' => 'Test > greater than',
        ];
        
        $escaped = $method->invoke($controller, $sessionData);
        
        // Verify that special characters are encoded
        $this->assertStringContainsString('&quot;', $escaped['quotes']);
        $this->assertStringContainsString('&#039;', $escaped['quotes']);
        $this->assertStringContainsString('&amp;', $escaped['ampersand']);
        $this->assertStringContainsString('&lt;', $escaped['less_than']);
        $this->assertStringContainsString('&gt;', $escaped['greater_than']);
    }
    
    /**
     * Test that empty values are handled correctly
     * 
     * @return void
     */
    public function test_empty_values_are_handled()
    {
        // Create controller instance
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escapeSessionData');
        $method->setAccessible(true);
        
        $sessionData = [
            'empty_string' => '',
            'null_value' => null,
            'zero' => 0,
            'false' => false,
        ];
        
        $escaped = $method->invoke($controller, $sessionData);
        
        // Verify that empty values are handled correctly
        $this->assertSame('', $escaped['empty_string']);
        $this->assertNull($escaped['null_value']);
        $this->assertSame(0, $escaped['zero']);
        $this->assertSame(false, $escaped['false']);
    }
}

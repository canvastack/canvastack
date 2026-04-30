<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Constants\SafeHtml;

/**
 * Test XSS Protection in Helper Functions
 * 
 * Validates: Task 1.7 - Fix XSS Vulnerabilities in Helper Functions
 * 
 * Tests that helper functions properly escape user-controllable data
 * to prevent XSS attacks.
 */
class HelperFunctionsXssTest extends TestCase
{
    /**
     * Test that canvastack_action_buttons escapes background color
     * 
     * @test
     */
    public function test_action_buttons_escapes_background_color()
    {
        // Create route info with XSS payload in background color
        $routeInfo = (object)[
            'action_page' => [
                'primary|Create' => '/users/create',
            ]
        ];
        
        $xssPayload = '<script>alert("XSS")</script>';
        $html = canvastack_action_buttons($routeInfo, $xssPayload);
        
        // Should NOT contain the SafeHtml marker (output is ready for rendering)
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        // Check that XSS payload is escaped
        $this->assertStringNotContainsString('<script>', $html, 'Script tags should be escaped');
        $this->assertStringContainsString('&lt;script&gt;', $html, 'Script tags should be HTML-encoded');
    }
    
    /**
     * Test that canvastack_action_button_box escapes button text
     * 
     * @test
     */
    public function test_action_button_box_escapes_button_text()
    {
        $xssPayload = '<script>alert("XSS")</script>';
        $html = canvastack_action_button_box('/users/create', $xssPayload, 'primary');
        
        // Should NOT contain the SafeHtml marker (output is ready for rendering)
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        // Check that XSS payload is escaped
        $this->assertStringNotContainsString('<script>', $html, 'Script tags should be escaped');
        $this->assertStringContainsString('&lt;script&gt;', $html, 'Script tags should be HTML-encoded');
    }
    
    /**
     * Test that canvastack_action_button_box escapes URL
     * 
     * @test
     */
    public function test_action_button_box_escapes_url()
    {
        $xssPayload = 'javascript:alert("XSS")';
        $html = canvastack_action_button_box($xssPayload, 'Click Me', 'primary');
        
        // Should NOT contain the SafeHtml marker (output is ready for rendering)
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        // Check that XSS payload is escaped
        // The URL is escaped, so quotes become &quot;
        $this->assertStringContainsString('&quot;', $html, 'Quotes should be HTML-encoded');
        $this->assertStringNotContainsString('javascript:alert("XSS")', $html, 'Unescaped JavaScript protocol should not be present');
    }
    
    /**
     * Test that canvastack_action_button_box escapes color class
     * 
     * @test
     */
    public function test_action_button_box_escapes_color_class()
    {
        $xssPayload = 'primary" onclick="alert(\'XSS\')';
        $html = canvastack_action_button_box('/users/create', 'Create', $xssPayload);
        
        // Should NOT contain the SafeHtml marker (output is ready for rendering)
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        // Check that XSS payload is escaped
        // The quotes are escaped, so onclick becomes part of the class value
        $this->assertStringContainsString('&quot;', $html, 'Quotes should be HTML-encoded');
        // The onclick should be escaped and not executable
        $this->assertStringNotContainsString('onclick="alert', $html, 'Unescaped event handlers should not be present');
    }
    
    /**
     * Test that canvastack_underscore_to_camelcase escapes output
     * 
     * @test
     */
    public function test_underscore_to_camelcase_escapes_output()
    {
        $xssPayload = 'user_<script>alert("XSS")</script>_name';
        $result = canvastack_underscore_to_camelcase($xssPayload);
        
        // Should not contain unescaped script tags
        $this->assertStringNotContainsString('<script>', $result, 'Script tags should be escaped');
        $this->assertStringContainsString('&lt;script&gt;', $result, 'Script tags should be HTML-encoded');
    }
    
    /**
     * Test that canvastack_underscore_to_camelcase handles normal input
     * 
     * @test
     */
    public function test_underscore_to_camelcase_handles_normal_input()
    {
        $result = canvastack_underscore_to_camelcase('user_name');
        $this->assertEquals('User Name', $result);
        
        // Short words (3 chars or less) are uppercased
        $result = canvastack_underscore_to_camelcase('api_key');
        $this->assertEquals('API KEY', $result);
        
        $result = canvastack_underscore_to_camelcase('simple');
        $this->assertEquals('Simple', $result);
    }
    
    /**
     * Test XSS protection with various attack vectors
     * 
     * @test
     */
    public function test_xss_protection_with_various_attack_vectors()
    {
        $attackVectors = [
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            '"><script>alert("XSS")</script>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
            '<body onload=alert("XSS")>',
        ];
        
        foreach ($attackVectors as $payload) {
            $html = canvastack_action_button_box('/test', $payload, 'primary');
            
            // Should not contain dangerous patterns
            $this->assertStringNotContainsString('onerror=', $html, "Attack vector should be escaped: {$payload}");
            $this->assertStringNotContainsString('onload=', $html, "Attack vector should be escaped: {$payload}");
            $this->assertStringNotContainsString('<script>', $html, "Attack vector should be escaped: {$payload}");
            $this->assertStringNotContainsString('<iframe', $html, "Attack vector should be escaped: {$payload}");
        }
    }
    
    /**
     * Test that output is ready for direct rendering
     * 
     * @test
     */
    public function test_output_is_ready_for_rendering()
    {
        $routeInfo = (object)[
            'action_page' => [
                'primary|Create' => '/users/create',
                'success|Edit' => '/users/edit',
            ]
        ];
        
        $html = canvastack_action_buttons($routeInfo);
        
        // Should NOT contain SafeHtml marker (ready for direct output)
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        // Should contain properly escaped HTML
        $this->assertStringContainsString('<div class="header white">', $html);
        $this->assertStringContainsString('</div>', $html);
    }
    
    /**
     * Test that configuration controls XSS protection
     * 
     * @test
     */
    public function test_configuration_controls_xss_protection()
    {
        // Enable XSS protection
        config(['canvastack.controller.security.xss_protection' => true]);
        
        $xssPayload = '<script>alert("XSS")</script>';
        $result = canvastack_underscore_to_camelcase($xssPayload);
        
        // Should be escaped
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }
    
    /**
     * Test that empty inputs are handled gracefully
     * 
     * @test
     */
    public function test_empty_inputs_handled_gracefully()
    {
        // Empty action_page array should return empty string
        $routeInfo = (object)[
            'action_page' => []
        ];
        
        $html = canvastack_action_buttons($routeInfo);
        $this->assertEmpty($html, 'Empty action_page should return empty string');
        
        // Null route info should return empty string
        $html = canvastack_action_buttons(null);
        $this->assertEmpty($html, 'Null route info should return empty string');
        
        $html = canvastack_action_button_box('', '', '');
        $this->assertStringNotContainsString(SafeHtml::MARKER, $html, 'Output should not contain SafeHtml marker');
        
        $result = canvastack_underscore_to_camelcase('');
        $this->assertEmpty($result, 'Empty string should return empty result');
    }
}

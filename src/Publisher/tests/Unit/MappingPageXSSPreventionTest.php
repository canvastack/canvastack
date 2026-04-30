<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\Module;

/**
 * Unit Tests for XSS Prevention in MappingPage Trait
 * 
 * Tests the buildRoleBox() and formatModuleTitle() methods to ensure
 * proper escaping of user-controllable module names prevents XSS attacks.
 * 
 * **Validates: Requirements 2.15**
 * 
 * @group unit
 * @group security
 * @group xss
 */
class MappingPageXSSPreventionTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test buildRoleBox() escapes module name with script tag
     * 
     * Verifies that a module name containing "<script>alert('XSS')</script>"
     * is properly escaped to "&lt;script&gt;alert('XSS')&lt;/script&gt;"
     * 
     * **Validates: Requirements 2.15**
     */
    public function test_buildRoleBox_escapes_script_tag_in_module_name()
    {
        // Since buildRoleBox() has complex dependencies (ajax_urli, route context),
        // we'll test the escaping logic directly through formatModuleTitle()
        // which is the core XSS prevention mechanism used by buildRoleBox()
        
        $controller = new GroupController();
        
        // Use reflection to access private formatModuleTitle method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatModuleTitle');
        $method->setAccessible(true);
        
        // Test with malicious module name
        $maliciousModuleName = "<script>alert('XSS')</script>";
        $result = $method->invoke($controller, $maliciousModuleName, null);
        
        // Assert: Script tag should be escaped
        $this->assertStringNotContainsString('<script>', $result, 
            'Module name contains unescaped <script> tag');
        $this->assertStringNotContainsString('</script>', $result,
            'Module name contains unescaped </script> tag');
        
        // Assert: Escaped version should be present
        $this->assertStringContainsString('&lt;script&gt;', $result,
            'Module name does not contain properly escaped script tag');
        $this->assertStringContainsString('&lt;/script&gt;', $result,
            'Module name does not contain properly escaped closing script tag');
    }
    
    /**
     * Test buildRoleBox() escapes module name with quotes
     * 
     * Verifies that a module name containing quotes is properly escaped
     * to prevent attribute-based XSS attacks.
     * 
     * **Validates: Requirements 2.15**
     */
    public function test_buildRoleBox_escapes_quotes_in_module_name()
    {
        // Test the escaping logic through formatModuleTitle()
        $controller = new GroupController();
        
        // Use reflection to access private formatModuleTitle method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatModuleTitle');
        $method->setAccessible(true);
        
        // Test with module name containing quotes
        $moduleNameWithQuotes = 'Test" onload="alert(\'XSS\')';
        $result = $method->invoke($controller, $moduleNameWithQuotes, null);
        
        // Assert: Quotes should be escaped
        $this->assertStringContainsString('&quot;', $result,
            'Module name does not contain properly escaped double quotes');
        
        // Assert: Event handler should not be executable
        $this->assertStringNotContainsString('onload="alert', $result,
            'Module name contains unescaped event handler');
    }
    
    /**
     * Test formatModuleTitle() escapes special characters
     * 
     * Verifies that the formatModuleTitle() helper method properly escapes
     * special characters in module titles.
     * 
     * **Validates: Requirements 2.15, 2.17**
     */
    public function test_formatModuleTitle_escapes_special_characters()
    {
        // Create a test controller instance
        $controller = new GroupController();
        
        // Use reflection to access private formatModuleTitle method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatModuleTitle');
        $method->setAccessible(true);
        
        // Test with various special characters
        $testCases = [
            '<script>alert(1)</script>' => '&lt;script&gt;alert(1)&lt;/script&gt;',
            'Test & Module' => 'Test &amp; Module',
            'Test "Module"' => 'Test &quot;Module&quot;',
            "Test 'Module'" => 'Test &#039;Module&#039;',
            'Test<>Module' => 'Test&lt;&gt;Module',
        ];
        
        foreach ($testCases as $input => $expectedEscaped) {
            $result = $method->invoke($controller, $input, null);
            
            $this->assertStringContainsString($expectedEscaped, $result,
                "formatModuleTitle did not properly escape: {$input}");
        }
    }
    
    /**
     * Test buildRoleBox() output does not contain unescaped HTML tags
     * 
     * Verifies that the formatModuleTitle() method (used by buildRoleBox())
     * properly escapes all HTML tags that could lead to XSS.
     * 
     * **Validates: Requirements 2.15**
     */
    public function test_buildRoleBox_output_contains_no_unescaped_html_tags()
    {
        // Create various malicious module names
        $maliciousNames = [
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '<iframe src="javascript:alert(1)">',
            '<body onload=alert(1)>',
            '<input onfocus=alert(1) autofocus>',
        ];
        
        // Create a test controller instance
        $controller = new GroupController();
        
        // Use reflection to access private formatModuleTitle method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatModuleTitle');
        $method->setAccessible(true);
        
        foreach ($maliciousNames as $maliciousName) {
            // Call formatModuleTitle with malicious module name
            $result = $method->invoke($controller, $maliciousName, null);
            
            // Assert: No unescaped HTML tags (< and > must be escaped)
            $this->assertStringNotContainsString('<img ', strtolower($result),
                "Module name contains unescaped <img> tag for input: {$maliciousName}");
            $this->assertStringNotContainsString('<svg ', strtolower($result),
                "Module name contains unescaped <svg> tag for input: {$maliciousName}");
            $this->assertStringNotContainsString('<iframe ', strtolower($result),
                "Module name contains unescaped <iframe> tag for input: {$maliciousName}");
            $this->assertStringNotContainsString('<body ', strtolower($result),
                "Module name contains unescaped <body> tag for input: {$maliciousName}");
            $this->assertStringNotContainsString('<input ', strtolower($result),
                "Module name contains unescaped <input> tag for input: {$maliciousName}");
            
            // Assert: < and > should be escaped (this makes any HTML tags harmless)
            $this->assertStringContainsString('&lt;', $result,
                "Module name does not contain escaped < character for input: {$maliciousName}");
            $this->assertStringContainsString('&gt;', $result,
                "Module name does not contain escaped > character for input: {$maliciousName}");
            
            // Assert: The escaped output should not be executable as HTML
            // Even if "onerror=" text exists, it's harmless when < and > are escaped
            $this->assertStringNotContainsString('<', $result,
                "Module name contains unescaped < character for input: {$maliciousName}");
            $this->assertStringNotContainsString('>', $result,
                "Module name contains unescaped > character for input: {$maliciousName}");
        }
    }
    
    /**
     * Test formatModuleTitle() handles module data object
     * 
     * Verifies that formatModuleTitle() correctly uses the module data's
     * name property when available and escapes it properly.
     * 
     * **Validates: Requirements 2.15, 2.17**
     */
    public function test_formatModuleTitle_handles_module_data_object()
    {
        // Create a test controller instance
        $controller = new GroupController();
        
        // Use reflection to access private formatModuleTitle method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatModuleTitle');
        $method->setAccessible(true);
        
        // Test with module data object containing malicious name
        $moduleData = (object)[
            'name' => '<script>alert("XSS")</script>'
        ];
        
        $result = $method->invoke($controller, 'fallback_name', $moduleData);
        
        // Assert: Script tag should be escaped
        $this->assertStringNotContainsString('<script>', $result,
            'formatModuleTitle did not escape script tag in module data name');
        $this->assertStringContainsString('&lt;script&gt;', $result,
            'formatModuleTitle did not properly escape module data name');
    }
}

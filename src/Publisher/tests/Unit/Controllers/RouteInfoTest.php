<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Canvastack\Canvastack\Exceptions\Controller\RouteException;

/**
 * RouteInfo Trait Test Suite
 * 
 * Tests URL validation, custom action buttons, and error handling
 * for the RouteInfo trait without requiring database setup.
 * 
 * @package Tests\Unit\Controllers
 */
class RouteInfoTest extends TestCase
{
    /**
     * Test URL validation with valid URLs
     * 
     * @return void
     */
    public function test_url_validation_with_valid_urls()
    {
        $controller = new TestRouteInfoController();
        
        // Test valid HTTP URL
        $this->assertTrue($controller->testValidateUrl('http://example.com/test'));
        
        // Test valid HTTPS URL
        $this->assertTrue($controller->testValidateUrl('https://example.com/test'));
        
        // Test valid relative URL
        $this->assertTrue($controller->testValidateUrl('/admin/products'));
    }
    
    /**
     * Test URL validation with invalid URLs
     * 
     * @return void
     */
    public function test_url_validation_with_invalid_urls()
    {
        $controller = new TestRouteInfoController();
        
        // Test javascript: protocol (XSS attempt)
        $this->assertFalse($controller->testValidateUrl('javascript:alert(1)'));
        
        // Test data: protocol (XSS attempt)
        $this->assertFalse($controller->testValidateUrl('data:text/html,<script>alert(1)</script>'));
        
        // Test script tag injection
        $this->assertFalse($controller->testValidateUrl('http://example.com/<script>alert(1)</script>'));
        
        // Test event handler injection
        $this->assertFalse($controller->testValidateUrl('http://example.com/test?onerror=alert(1)'));
    }
    
    /**
     * Test custom action button addition
     * 
     * @return void
     */
    public function test_add_custom_action_button()
    {
        $controller = new TestRouteInfoController();
        
        // Add a custom button
        $controller->addCustomActionButton('primary', 'Export', 'http://example.com/export');
        
        // Get custom buttons
        $buttons = $controller->getCustomActionButtons();
        
        // Assert button was added
        $this->assertCount(1, $buttons);
        $this->assertArrayHasKey('primary|Export', $buttons);
        $this->assertEquals('http://example.com/export', $buttons['primary|Export']);
    }
    
    /**
     * Test custom action button with disabled state
     * 
     * @return void
     */
    public function test_add_custom_action_button_disabled()
    {
        $controller = new TestRouteInfoController();
        
        // Add a disabled button
        $controller->addCustomActionButton('secondary', 'Import', 'http://example.com/import', false);
        
        // Get custom buttons
        $buttons = $controller->getCustomActionButtons();
        
        // Assert button was added with disabled state
        $this->assertCount(1, $buttons);
        $this->assertArrayHasKey('secondary|Import|disabled', $buttons);
    }
    
    /**
     * Test adding multiple custom action buttons
     * 
     * @return void
     */
    public function test_add_multiple_custom_action_buttons()
    {
        $controller = new TestRouteInfoController();
        
        // Add multiple buttons
        $controller->addCustomActionButtons([
            ['color' => 'primary', 'label' => 'Export', 'url' => 'http://example.com/export'],
            ['color' => 'secondary', 'label' => 'Import', 'url' => 'http://example.com/import', 'enabled' => false],
            ['color' => 'info', 'label' => 'Download', 'url' => 'http://example.com/download'],
        ]);
        
        // Get custom buttons
        $buttons = $controller->getCustomActionButtons();
        
        // Assert all buttons were added
        $this->assertCount(3, $buttons);
    }
    
    /**
     * Test custom action button with invalid color
     * 
     * @return void
     */
    public function test_add_custom_action_button_invalid_color()
    {
        $this->expectException(RouteException::class);
        $this->expectExceptionMessage('Invalid button color');
        
        $controller = new TestRouteInfoController();
        $controller->addCustomActionButton('invalid', 'Test', 'http://example.com/test');
    }
    
    /**
     * Test custom action button with empty label
     * 
     * @return void
     */
    public function test_add_custom_action_button_empty_label()
    {
        $this->expectException(RouteException::class);
        $this->expectExceptionMessage('Button label cannot be empty');
        
        $controller = new TestRouteInfoController();
        $controller->addCustomActionButton('primary', '', 'http://example.com/test');
    }
    
    /**
     * Test custom action button with invalid URL
     * 
     * @return void
     */
    public function test_add_custom_action_button_invalid_url()
    {
        $this->expectException(RouteException::class);
        $this->expectExceptionMessage('Invalid button URL');
        
        $controller = new TestRouteInfoController();
        $controller->addCustomActionButton('primary', 'Test', 'javascript:alert(1)');
    }
    
    /**
     * Test clearing custom action buttons
     * 
     * @return void
     */
    public function test_clear_custom_action_buttons()
    {
        $controller = new TestRouteInfoController();
        
        // Add buttons
        $controller->addCustomActionButton('primary', 'Export', 'http://example.com/export');
        $controller->addCustomActionButton('secondary', 'Import', 'http://example.com/import');
        
        // Assert buttons were added
        $this->assertCount(2, $controller->getCustomActionButtons());
        
        // Clear buttons
        $controller->clearCustomActionButtons();
        
        // Assert buttons were cleared
        $this->assertCount(0, $controller->getCustomActionButtons());
    }
    
    /**
     * Test URL validation with empty string
     * 
     * @return void
     */
    public function test_url_validation_with_empty_string()
    {
        $controller = new TestRouteInfoController();
        
        // Test empty URL
        $this->assertFalse($controller->testValidateUrl(''));
    }
    
    /**
     * Test URL validation with vbscript protocol
     * 
     * @return void
     */
    public function test_url_validation_with_vbscript()
    {
        $controller = new TestRouteInfoController();
        
        // Test vbscript: protocol (XSS attempt)
        $this->assertFalse($controller->testValidateUrl('vbscript:msgbox(1)'));
    }
}

/**
 * Test controller for RouteInfo testing
 * 
 * This is a minimal controller that uses only the RouteInfo trait
 * without requiring full Controller initialization.
 */
class TestRouteInfoController
{
    use \Canvastack\Canvastack\Controllers\Core\Craft\Includes\RouteInfo {
        validateGeneratedUrl as public testValidateUrl;
    }
    
    /**
     * Mock escapeValue method to avoid dependencies
     */
    protected function escapeValue($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

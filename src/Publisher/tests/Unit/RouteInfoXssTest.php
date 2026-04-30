<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Canvastack\Canvastack\Controllers\Core\Craft\Includes\RouteInfo;
use Canvastack\Canvastack\Library\Constants\SafeHtml;

/**
 * XSS Protection Tests for RouteInfo Trait
 * 
 * Tests that RouteInfo properly escapes all user-controllable data
 * to prevent XSS attacks in action button labels, module names, and page info.
 * 
 * @group security
 * @group xss
 * @group routeinfo
 */
class RouteInfoXssTest extends TestCase
{
    
    /**
     * Test that action button labels are escaped
     * 
     * @test
     */
    public function test_action_button_labels_are_escaped()
    {
        // XSS payload in controller name
        $xssPayload = '<script>alert("XSS")</script>';
        
        // Create a mock controller with XSS payload in name
        $controller = new class {
            use RouteInfo;
            
            public $controllerName;
            public $page_name;
            public $pageInfo = 'index';
            public $module_privilege = ['actions' => ['create']];
            public $is_softdeleted = false;
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        // Set XSS payload as controller name
        $controller->controllerName = $xssPayload;
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@index'
                    ];
                }
            }
        );
        
        // Call routeInfo
        $controller->routeInfo();
        
        // Get the action page data
        $actionPage = $controller->route_info->action_page ?? [];
        
        // Check that button labels are marked as safe (already escaped)
        foreach ($actionPage as $label => $url) {
            // Should be marked with SafeHtml
            $this->assertTrue(
                SafeHtml::isMarked($label),
                'Action button label should be marked as safe HTML'
            );
            
            // Unmark and check that XSS payload is escaped
            $unmarked = SafeHtml::unmark($label);
            $this->assertStringNotContainsString(
                '<script>',
                $unmarked,
                'Action button label should not contain unescaped script tags'
            );
            $this->assertStringContainsString(
                '&lt;script&gt;',
                $unmarked,
                'Action button label should contain escaped script tags'
            );
        }
    }
    
    /**
     * Test that module names are escaped
     * 
     * @test
     */
    public function test_module_names_are_escaped()
    {
        // XSS payload
        $xssPayload = '"><img src=x onerror=alert(1)>';
        
        $controller = new class {
            use RouteInfo;
            
            public $controllerName;
            public $pageInfo = 'index';
            public $module_privilege = ['actions' => []];
            public $actionButton = [];
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        $controller->controllerName = $xssPayload;
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@index'
                    ];
                }
            }
        );
        
        $controller->routeInfo();
        
        // Check that module name is escaped
        $moduleName = $controller->route_info->module_name;
        $this->assertStringNotContainsString(
            '"><img',
            $moduleName,
            'Module name should not contain unescaped HTML'
        );
        $this->assertStringContainsString(
            '&quot;&gt;&lt;img',
            $moduleName,
            'Module name should contain escaped HTML entities'
        );
    }
    
    /**
     * Test that page info is escaped
     * 
     * @test
     */
    public function test_page_info_is_escaped()
    {
        // XSS payload
        $xssPayload = '<svg/onload=alert(1)>';
        
        $controller = new class {
            use RouteInfo;
            
            public $controllerName = 'Test';
            public $pageInfo;
            public $module_privilege = ['actions' => []];
            public $actionButton = [];
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@<svg/onload=alert(1)>'
                    ];
                }
            }
        );
        
        $controller->routeInfo();
        
        // Check that page info is escaped
        $pageInfo = $controller->route_info->page_info;
        $this->assertStringNotContainsString(
            '<svg',
            $pageInfo,
            'Page info should not contain unescaped SVG tags'
        );
        $this->assertStringContainsString(
            '&lt;svg',
            $pageInfo,
            'Page info should contain escaped SVG tags'
        );
    }
    
    /**
     * Test various XSS payloads in button labels
     * 
     * @test
     * @dataProvider xssPayloadProvider
     */
    public function test_various_xss_payloads_are_escaped($payload, $description)
    {
        $controller = new class {
            use RouteInfo;
            
            public $controllerName;
            public $pageInfo = 'index';
            public $module_privilege = ['actions' => ['create']];
            public $is_softdeleted = false;
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        $controller->controllerName = $payload;
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@index'
                    ];
                }
            }
        );
        
        $controller->routeInfo();
        
        // Get action page
        $actionPage = $controller->route_info->action_page ?? [];
        
        foreach ($actionPage as $label => $url) {
            $unmarked = SafeHtml::unmark($label);
            
            // Should not contain dangerous characters unescaped
            $this->assertStringNotContainsString(
                '<',
                $unmarked,
                "XSS payload ({$description}) should not contain unescaped < character"
            );
            $this->assertStringNotContainsString(
                '>',
                $unmarked,
                "XSS payload ({$description}) should not contain unescaped > character"
            );
            $this->assertStringNotContainsString(
                '"',
                $unmarked,
                "XSS payload ({$description}) should not contain unescaped \" character"
            );
        }
    }
    
    /**
     * Provide various XSS payloads for testing
     */
    public static function xssPayloadProvider()
    {
        return [
            ['<script>alert(1)</script>', 'Script tag'],
            ['<img src=x onerror=alert(1)>', 'Image with onerror'],
            ['<svg/onload=alert(1)>', 'SVG with onload'],
            ['javascript:alert(1)', 'JavaScript protocol'],
            ['"><script>alert(1)</script>', 'Attribute escape'],
            ["'><script>alert(1)</script>", 'Single quote escape'],
            ['<iframe src="javascript:alert(1)"></iframe>', 'Iframe with JavaScript'],
            ['<body onload=alert(1)>', 'Body with onload'],
            ['<input onfocus=alert(1) autofocus>', 'Input with onfocus'],
            ['<select onfocus=alert(1) autofocus>', 'Select with onfocus'],
        ];
    }
    
    /**
     * Test that SafeHtml markers are properly applied
     * 
     * @test
     */
    public function test_safehtml_markers_are_applied()
    {
        $controller = new class {
            use RouteInfo;
            
            public $controllerName = 'TestController';
            public $pageInfo = 'index';
            public $module_privilege = ['actions' => ['create']];
            public $is_softdeleted = false;
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@index'
                    ];
                }
            }
        );
        
        $controller->routeInfo();
        
        // Get action page
        $actionPage = $controller->route_info->action_page ?? [];
        
        // All button labels should be marked as safe
        foreach ($actionPage as $label => $url) {
            $this->assertTrue(
                SafeHtml::isMarked($label),
                'All action button labels should be marked with SafeHtml'
            );
        }
    }
    
    /**
     * Test that XSS protection can be disabled via configuration
     * 
     * @test
     */
    public function test_xss_protection_respects_configuration()
    {
        // Disable XSS protection
        config(['canvastack.controller.security.xss_protection' => false]);
        
        $xssPayload = '<script>alert(1)</script>';
        
        $controller = new class {
            use RouteInfo;
            
            public $controllerName;
            public $pageInfo = 'index';
            public $module_privilege = ['actions' => []];
            public $actionButton = [];
            
            public function setDataValues($key, $value) {
                $this->route_info = $value;
            }
            
            public function module_privileges() {
                // Mock implementation
            }
        };
        
        $controller->controllerName = $xssPayload;
        
        // Mock route
        Route::shouldReceive('getCurrentRoute')->andReturn(
            new class {
                public function getName() {
                    return 'admin.test.index';
                }
                
                public function getAction() {
                    return [
                        'controller' => 'App\Http\Controllers\Admin\TestController@index'
                    ];
                }
            }
        );
        
        $controller->routeInfo();
        
        // When XSS protection is disabled, payload should not be escaped
        $moduleName = $controller->route_info->module_name;
        $this->assertEquals(
            $xssPayload,
            $moduleName,
            'When XSS protection is disabled, content should not be escaped'
        );
        
        // Re-enable XSS protection for other tests
        config(['canvastack.controller.security.xss_protection' => true]);
    }
}

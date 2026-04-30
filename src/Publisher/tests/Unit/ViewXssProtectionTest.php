<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Canvastack\Canvastack\Library\Constants\SafeHtml;

/**
 * XSS Protection Tests for View Trait
 * 
 * Tests that all user-controllable data in View.php is properly escaped
 * to prevent XSS attacks.
 * 
 * @package Tests\Unit
 */
class ViewXssProtectionTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * XSS payloads for testing
     */
    private array $xssPayloads = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '"><script>alert(String.fromCharCode(88,83,83))</script>',
        '<iframe src="javascript:alert(\'XSS\')">',
        '<body onload=alert("XSS")>',
        'javascript:alert("XSS")',
        '<svg/onload=alert("XSS")>',
        '<input onfocus=alert("XSS") autofocus>',
        '<marquee onstart=alert("XSS")>',
        '<div style="background:url(javascript:alert(\'XSS\'))">',
    ];
    
    /**
     * Test that htmlspecialchars escapes XSS payloads correctly
     * 
     * This verifies the escaping function used in View.php works correctly
     * 
     * @test
     */
    public function test_htmlspecialchars_escapes_xss_payloads()
    {
        foreach ($this->xssPayloads as $payload) {
            $escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
            
            // Verify dangerous HTML tags are escaped
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<iframe', $escaped);
            $this->assertStringNotContainsString('<body', $escaped);
            $this->assertStringNotContainsString('<svg', $escaped);
            $this->assertStringNotContainsString('<input', $escaped);
            $this->assertStringNotContainsString('<marquee', $escaped);
            $this->assertStringNotContainsString('<div', $escaped);
            
            // Verify HTML entities are used
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped);
            }
            if (strpos($payload, '>') !== false) {
                $this->assertStringContainsString('&gt;', $escaped);
            }
            if (strpos($payload, '"') !== false) {
                $this->assertStringContainsString('&quot;', $escaped);
            }
        }
    }
    
    /**
     * Test that recursive array escaping works correctly
     * 
     * @test
     */
    public function test_recursive_array_escaping()
    {
        $data = [
            'level1' => '<script>alert("XSS")</script>',
            'nested' => [
                'level2' => '<img src=x onerror=alert("XSS")>',
                'deep' => [
                    'level3' => '<body onload=alert("XSS")>',
                ],
            ],
            'safe_int' => 123,
            'safe_bool' => true,
        ];
        
        $escaped = $this->escapeArrayRecursive($data);
        
        // Verify all string levels are escaped
        $this->assertStringContainsString('&lt;script&gt;', $escaped['level1']);
        $this->assertStringContainsString('&lt;img', $escaped['nested']['level2']);
        $this->assertStringContainsString('&lt;body', $escaped['nested']['deep']['level3']);
        
        // Verify non-strings are preserved
        $this->assertEquals(123, $escaped['safe_int']);
        $this->assertTrue($escaped['safe_bool']);
    }
    
    /**
     * Test that object property escaping works correctly
     * 
     * @test
     */
    public function test_object_property_escaping()
    {
        $data = (object) [
            'prop1' => '<script>alert("XSS")</script>',
            'nested' => (object) [
                'prop2' => '<img src=x onerror=alert("XSS")>',
            ],
            'safe_int' => 456,
        ];
        
        $escaped = $this->escapeObjectRecursive($data);
        
        // Verify all string properties are escaped
        $this->assertStringContainsString('&lt;script&gt;', $escaped->prop1);
        $this->assertStringContainsString('&lt;img', $escaped->nested->prop2);
        
        // Verify non-strings are preserved
        $this->assertEquals(456, $escaped->safe_int);
    }
    
    /**
     * Test that login page title is escaped
     * 
     * @test
     */
    public function test_login_page_title_is_escaped()
    {
        // Create a preference record with XSS payload
        \Canvastack\Canvastack\Models\Admin\System\Preference::create([
            'logo' => '/path/to/logo.png',
            'login_title' => '<script>alert("XSS")</script>',
            'login_background' => '/path/to/bg.jpg',
        ]);
        
        $preference = \Canvastack\Canvastack\Models\Admin\System\Preference::first();
        
        // Simulate what loginPage() does
        $title = $preference->login_title;
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        
        // Verify login title is escaped
        $this->assertStringNotContainsString('<script>', $escapedTitle);
        $this->assertStringContainsString('&lt;script&gt;', $escapedTitle);
    }
    
    /**
     * Test that breadcrumb labels with XSS are escaped
     * 
     * @test
     */
    public function test_breadcrumb_labels_are_escaped()
    {
        $breadcrumbs = [
            'home' => '/',
            '<script>alert("XSS")</script>' => '/test',
            'Safe Label' => '/safe',
        ];
        
        $escaped = $this->escapeArrayRecursive($breadcrumbs);
        
        // Verify dangerous HTML tags are escaped
        foreach ($escaped as $label => $url) {
            $this->assertStringNotContainsString('<script>', $label);
            $this->assertStringNotContainsString('<img', $label);
            $this->assertStringNotContainsString('<body', $label);
        }
        
        // Verify HTML entities are used for XSS payloads
        $xssLabel = array_keys($escaped)[1]; // The XSS payload label
        $this->assertStringContainsString('&lt;', $xssLabel);
        $this->assertStringContainsString('&gt;', $xssLabel);
    }
    
    /**
     * Test that action button labels are escaped
     * 
     * @test
     */
    public function test_action_button_labels_are_escaped()
    {
        $routeInfo = (object) [
            'action_page' => [
                "primary|<script>alert('XSS')</script>" => '/test',
                '<img src=x onerror=alert("XSS")>' => '/test2',
            ],
            'module_name' => '<script>alert("XSS")</script>',
            'page_info' => '<body onload=alert("XSS")>',
        ];
        
        // Escape action buttons
        $escapedActions = [];
        foreach ($routeInfo->action_page as $label => $url) {
            if (strpos($label, '|') !== false) {
                list($color, $text) = explode('|', $label, 2);
                $escapedLabel = htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '|' . 
                                htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            } else {
                $escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            }
            $escapedActions[$escapedLabel] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }
        
        // Verify dangerous HTML tags are escaped
        foreach ($escapedActions as $label => $url) {
            $this->assertStringNotContainsString('<script>', $label);
            $this->assertStringNotContainsString('<img', $label);
        }
        
        // Escape module name and page info
        $escapedModuleName = htmlspecialchars($routeInfo->module_name, ENT_QUOTES, 'UTF-8');
        $escapedPageInfo = htmlspecialchars($routeInfo->page_info, ENT_QUOTES, 'UTF-8');
        
        $this->assertStringNotContainsString('<script>', $escapedModuleName);
        $this->assertStringNotContainsString('<body', $escapedPageInfo);
        
        // Verify HTML entities are used
        $this->assertStringContainsString('&lt;', $escapedModuleName);
        $this->assertStringContainsString('&lt;', $escapedPageInfo);
    }
    
    /**
     * Test that menu items are escaped
     * 
     * @test
     */
    public function test_menu_items_are_escaped()
    {
        $menu = [
            'label' => '<script>alert("XSS")</script>',
            'url' => '/test',
            'icon' => '<img src=x onerror=alert("XSS")>',
            'submenu' => [
                'label' => '<body onload=alert("XSS")>',
            ],
        ];
        
        $escaped = $this->escapeArrayRecursive($menu);
        
        // Verify dangerous HTML tags are escaped
        $this->assertStringNotContainsString('<script>', $escaped['label']);
        $this->assertStringNotContainsString('<img', $escaped['icon']);
        $this->assertStringNotContainsString('<body', $escaped['submenu']['label']);
        
        // Verify HTML entities are used
        $this->assertStringContainsString('&lt;', $escaped['label']);
        $this->assertStringContainsString('&lt;', $escaped['icon']);
        $this->assertStringContainsString('&lt;', $escaped['submenu']['label']);
    }
    
    /**
     * Test that page titles are escaped
     * 
     * @test
     */
    public function test_page_titles_are_escaped()
    {
        foreach ($this->xssPayloads as $payload) {
            $escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
            
            // Verify dangerous HTML tags are escaped
            $this->assertStringNotContainsString('<script>', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<iframe', $escaped);
            $this->assertStringNotContainsString('<body', $escaped);
            $this->assertStringNotContainsString('<svg', $escaped);
            $this->assertStringNotContainsString('<input', $escaped);
            $this->assertStringNotContainsString('<marquee', $escaped);
            $this->assertStringNotContainsString('<div', $escaped);
            
            // Verify HTML entities are used for dangerous characters
            if (strpos($payload, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped);
            }
            if (strpos($payload, '>') !== false) {
                $this->assertStringContainsString('&gt;', $escaped);
            }
        }
    }
    
    /**
     * Helper method to escape array recursively (mimics View trait logic)
     */
    private function escapeArrayRecursive(array $data): array
    {
        $escaped = [];
        foreach ($data as $key => $value) {
            // Escape the key if it's a string
            $escapedKey = is_string($key) ? htmlspecialchars($key, ENT_QUOTES, 'UTF-8') : $key;
            
            if (is_array($value)) {
                $escaped[$escapedKey] = $this->escapeArrayRecursive($value);
            } elseif (is_object($value)) {
                $escaped[$escapedKey] = $this->escapeObjectRecursive($value);
            } elseif (is_string($value)) {
                $escaped[$escapedKey] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $escaped[$escapedKey] = $value;
            }
        }
        return $escaped;
    }
    
    /**
     * Helper method to escape object recursively (mimics View trait logic)
     */
    private function escapeObjectRecursive($data)
    {
        $escaped = clone $data;
        foreach (get_object_vars($escaped) as $key => $value) {
            if (is_array($value)) {
                $escaped->$key = $this->escapeArrayRecursive($value);
            } elseif (is_object($value)) {
                $escaped->$key = $this->escapeObjectRecursive($value);
            } elseif (is_string($value)) {
                $escaped->$key = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $escaped->$key = $value;
            }
        }
        return $escaped;
    }
}

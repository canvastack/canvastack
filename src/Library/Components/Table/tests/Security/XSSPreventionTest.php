<?php

namespace Canvastack\Canvastack\Library\Components\Table\Tests\Security;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Security\JavaScriptSecurityHelper;
use Canvastack\Canvastack\Library\Components\Table\Craft\Method\Post;

/**
 * XSS Prevention Test Suite
 * 
 * Comprehensive testing for Cross-Site Scripting (XSS) attack prevention
 * Tests all vulnerable points identified in the security audit
 */
class XSSPreventionTest extends TestCase
{
    /**
     * Test CSRF token encoding for JavaScript context
     *
     * @test
     */
    public function testCSRFTokenEncoding()
    {
        // Mock CSRF token with potential XSS payload
        $maliciousToken = '</script><script>alert("XSS")</script>';
        
        // Test secure encoding
        $secureToken = JavaScriptSecurityHelper::encodeString($maliciousToken);
        
        // Assert XSS payload is neutralized
        $this->assertStringNotContainsString('<script', $secureToken);
        $this->assertStringNotContainsString('</script', $secureToken);
        $this->assertStringNotContainsString('alert(', $secureToken);
    }

    /**
     * Test JSON encoding with security flags
     *
     * @test
     */
    public function testSecureJSONEncoding()
    {
        $testData = [
            'field1' => '</script><script>alert("XSS")</script>',
            'field2' => '<img src=x onerror=alert("XSS")>',
            'field3' => 'javascript:alert("XSS")',
            'field4' => 'vbscript:msgbox("XSS")',
            'field5' => 'data:text/html,<script>alert("XSS")</script>'
        ];

        $secureJSON = JavaScriptSecurityHelper::encodeForJS($testData);

        // Assert all XSS vectors are neutralized
        $this->assertStringNotContainsString('<script', $secureJSON);
        $this->assertStringNotContainsString('javascript:', $secureJSON);
        $this->assertStringNotContainsString('vbscript:', $secureJSON);
        $this->assertStringNotContainsString('onerror=', $secureJSON);
        $this->assertStringNotContainsString('data:', $secureJSON);
    }

    /**
     * Test ID validation and encoding
     *
     * @test
     */
    public function testIDValidationAndEncoding()
    {
        // Test valid IDs
        $validIds = ['myTable', 'data-table-1', 'user_table_2'];
        
        foreach ($validIds as $id) {
            $encoded = JavaScriptSecurityHelper::encodeId($id);
            $this->assertIsString($encoded);
        }

        // Test invalid IDs (should throw exceptions)
        $invalidIds = [
            '</script><script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")'
        ];

        foreach ($invalidIds as $invalidId) {
            $this->expectException(\InvalidArgumentException::class);
            JavaScriptSecurityHelper::encodeId($invalidId);
        }
    }

    /**
     * Test DataTable configuration sanitization
     *
     * @test
     */
    public function testDataTableConfigSanitization()
    {
        $maliciousConfig = [
            'columns' => [
                ['data' => 'name'],
                ['data' => '</script><script>alert("XSS")</script>']
            ],
            'ajax' => [
                'url' => 'javascript:alert("XSS")'
            ],
            'callbacks' => [
                'rowCallback' => 'function(){alert("XSS")}'
            ]
        ];

        $sanitized = JavaScriptSecurityHelper::sanitizeDataTableConfig($maliciousConfig);

        // Assert XSS payloads are neutralized
        $serialized = serialize($sanitized);
        $this->assertStringNotContainsString('javascript:', $serialized);
        $this->assertStringNotContainsString('<script', $serialized);
        $this->assertStringNotContainsString('alert(', $serialized);
    }

    /**
     * Test secure AJAX data function generation
     *
     * @test
     */
    public function testSecureAjaxDataFunction()
    {
        $maliciousToken = '</script><script>alert("XSS")</script>';
        $maliciousExtraData = [
            'callback' => 'javascript:alert("XSS")',
            'param' => '<img src=x onerror=alert("XSS")>'
        ];

        $secureFunction = JavaScriptSecurityHelper::createSecureAjaxDataFunction(
            $maliciousToken,
            $maliciousExtraData
        );

        // Assert function is properly escaped
        $this->assertStringNotContainsString('<script', $secureFunction);
        $this->assertStringNotContainsString('javascript:', $secureFunction);
        $this->assertStringNotContainsString('onerror=', $secureFunction);
        $this->assertIsString($secureFunction);
        $this->assertStringContainsString('function(data)', $secureFunction);
    }

    /**
     * Test secure console.log generation
     *
     * @test
     */
    public function testSecureConsoleLog()
    {
        $maliciousMessage = '</script><script>alert("XSS")</script>';
        $maliciousData = [
            'payload' => '<img src=x onerror=alert("XSS")>',
            'callback' => 'javascript:alert("XSS")'
        ];

        $secureLog = JavaScriptSecurityHelper::createSecureConsoleLog($maliciousMessage, $maliciousData);

        // Assert console.log is properly escaped
        $this->assertStringNotContainsString('<script', $secureLog);
        $this->assertStringNotContainsString('javascript:', $secureLog);
        $this->assertStringNotContainsString('onerror=', $secureLog);
        $this->assertStringContainsString('console.log(', $secureLog);
    }

    /**
     * Test variable name validation
     *
     * @test
     */
    public function testVariableNameValidation()
    {
        // Valid variable names
        $validNames = ['myVar', 'data_table', 'config123'];
        
        foreach ($validNames as $name) {
            $result = JavaScriptSecurityHelper::createSafeVariable($name, 'test');
            $this->assertStringContainsString($name, $result);
        }

        // Invalid variable names (should throw exceptions)
        $invalidNames = [
            '</script><script>alert("XSS")</script>',
            'alert("XSS")',
            '123invalidStart'
        ];

        foreach ($invalidNames as $invalidName) {
            $this->expectException(\InvalidArgumentException::class);
            JavaScriptSecurityHelper::createSafeVariable($invalidName, 'test');
        }
    }

    /**
     * Test comprehensive XSS attack vectors
     *
     * @test
     */
    public function testComprehensiveXSSVectors()
    {
        $xssVectors = [
            // Script injection
            '<script>alert("XSS")</script>',
            '</script><script>alert("XSS")</script>',
            
            // Event handlers
            '<img src=x onerror=alert("XSS")>',
            '<body onload=alert("XSS")>',
            
            // JavaScript URLs
            'javascript:alert("XSS")',
            'vbscript:msgbox("XSS")',
            
            // Data URLs
            'data:text/html,<script>alert("XSS")</script>',
            
            // DOM XSS
            'eval(alert("XSS"))',
            'setTimeout("alert(\\"XSS\\")", 100)',
            
            // CSS injection
            '<style>body{background:url("javascript:alert(\\"XSS\\")")}</style>',
            
            // SVG injection
            '<svg onload=alert("XSS")>',
            
            // Form injection
            '<form><button formaction=javascript:alert("XSS")>Click</button></form>'
        ];

        foreach ($xssVectors as $vector) {
            // Test string encoding
            $encodedString = JavaScriptSecurityHelper::encodeString($vector);
            $this->assertXSSNeutralized($encodedString, "String encoding failed for: {$vector}");
            
            // Test JSON encoding
            $encodedJSON = JavaScriptSecurityHelper::encodeForJS(['payload' => $vector]);
            $this->assertXSSNeutralized($encodedJSON, "JSON encoding failed for: {$vector}");
        }
    }

    /**
     * Assert that XSS payload has been neutralized
     *
     * @param string $output
     * @param string $message
     */
    private function assertXSSNeutralized(string $output, string $message = '')
    {
        $dangerousPatterns = [
            '/<script/i',
            '/<\/script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/on\w+\s*=/i',
            '/eval\s*\(/i',
            '/setTimeout\s*\(/i',
            '/setInterval\s*\(/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $output,
                $message . " - Dangerous pattern found: {$pattern}"
            );
        }
    }

    /**
     * Test backward compatibility
     *
     * @test
     */
    public function testBackwardCompatibility()
    {
        // Test that normal, safe content still works properly
        $safeData = [
            'columns' => ['name', 'email', 'date'],
            'records' => [
                ['John Doe', 'john@example.com', '2024-01-01'],
                ['Jane Smith', 'jane@example.com', '2024-01-02']
            ],
            'config' => [
                'paging' => true,
                'searching' => true,
                'ordering' => true
            ]
        ];

        $encoded = JavaScriptSecurityHelper::encodeForJS($safeData);
        $decoded = json_decode($encoded, true);
        
        // Assert data integrity is maintained
        $this->assertEquals($safeData['columns'], $decoded['columns']);
        $this->assertEquals($safeData['records'], $decoded['records']);
        $this->assertEquals($safeData['config'], $decoded['config']);
    }

    /**
     * Test performance impact of security measures
     *
     * @test
     */
    public function testPerformanceImpact()
    {
        $largeDataset = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeDataset[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'data' => str_repeat('data', 100)
            ];
        }

        $startTime = microtime(true);
        $encoded = JavaScriptSecurityHelper::encodeForJS($largeDataset);
        $endTime = microtime(true);
        
        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Assert processing time is reasonable (less than 100ms for 1000 records)
        $this->assertLessThan(100, $processingTime, 'Security encoding is too slow');
        
        // Assert output is valid JSON
        $this->assertIsString($encoded);
        $this->assertNotNull(json_decode($encoded, true));
    }
}
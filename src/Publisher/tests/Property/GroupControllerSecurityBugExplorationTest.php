<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Exceptions\Controller\CSRFException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;

/**
 * Bug Condition Exploration Test for GroupController Security Vulnerabilities
 * 
 * **CRITICAL**: These tests MUST FAIL on unfixed code - failure confirms bugs exist
 * **DO NOT attempt to fix the tests or the code when they fail**
 * **NOTE**: These tests encode the expected behavior - they will validate fixes when they pass after implementation
 * 
 * Uses Eris property-based testing to surface counterexamples that demonstrate
 * security vulnerabilities in GroupController.php and its traits:
 * - Missing CSRF validation for AJAX requests (Issue #1)
 * - SQL injection in rolepage() (Issue #16)
 * - XSS in buildRoleBox() (Issue #17)
 * - Unsafe URL construction in ajax_urli() (Issue #18)
 * 
 * **Validates: Requirements 2.1, 2.14, 2.15, 2.16**
 * 
 * @group property
 * @group bugfix
 * @group group-controller
 * @group security
 */
class GroupControllerSecurityBugExplorationTest extends TestCase
{
    use TestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test session for authenticated user with all required fields
        session([
            'id' => 1,
            'user_group' => 'admin',
            'group_id' => 1,
            'group_info' => 'Administrator',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'fullname' => 'Test User',
            'phone' => '1234567890',
            'address' => 'Test Address',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    /**
     * Property 1: Fault Condition - CSRF Attack on AJAX Rolemapage Request
     * 
     * **Validates: Requirement 2.1**
     * 
     * For any AJAX request to store() with rolemapage parameter, the system SHALL
     * validate CSRF token explicitly using validateAjaxCsrfToken() method, validate
     * usein parameter against allowed contexts, and log security events.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - AJAX requests with ?rolemapage=true bypass CSRF validation
     * - System processes requests without checking CSRF token
     * - Attacker can modify privileges via CSRF attack (CVSS 8.8)
     * - Counterexamples will show requests succeed without valid CSRF token
     * 
     * **BUG LOCATION**: GroupController.php lines 145-147
     * ```php
     * if (!empty($_GET['rolemapage'])) {
     *     return $this->rolepage($_POST, $_GET['usein']);
     * }
     * ```
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_1_csrf_validation_for_ajax_rolemapage()
    {
        $this->forAll(
            // Generate different usein contexts
            Generators::elements(['table_name', 'field_name', 'field_value']),
            // Generate POST data
            Generators::associative([
                'data' => Generators::string()
            ])
        )
        ->then(function ($usein, $postData) {
            // Arrange: Create request WITHOUT valid CSRF token
            $request = Request::create(
                '/admin/system/group?rolemapage=true&usein=' . $usein,
                'POST',
                $postData
            );
            
            // Remove CSRF token to simulate attack
            $request->headers->remove('X-CSRF-TOKEN');
            $request->request->remove('_token');
            
            // Act & Assert: Request should be REJECTED due to missing CSRF token
            // On UNFIXED code, this will PASS (bug exists)
            // On FIXED code, this will throw CSRFException
            $this->expectException(CSRFException::class);
            
            $controller = new GroupController();
            $controller->store($request);
            
            // If we reach here on unfixed code, the bug exists
            $this->fail(
                "CSRF vulnerability confirmed: AJAX rolemapage request processed without CSRF token. " .
                "usein={$usein}, Bug at lines 145-147"
            );
        });
    }
    
    /**
     * Property 2: Fault Condition - SQL Injection in rolepage()
     * 
     * **Validates: Requirement 2.14**
     * 
     * For any call to rolepage(), the system SHALL validate $usein against allowed
     * contexts ['table_name', 'field_name', 'field_value'], validate $data is not
     * empty, throw ControllerValidationException on invalid input, and wrap getData()
     * call in try-catch.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - $usein parameter passed directly to getData() without validation
     * - Malicious SQL can be injected via $usein parameter
     * - SQL injection vulnerability (CVSS 9.8)
     * - Counterexamples will show SQL injection payloads are not rejected
     * 
     * **BUG LOCATION**: MappingPage.php rolepage() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_2_sql_injection_prevention_in_rolepage()
    {
        $this->forAll(
            // Generate SQL injection payloads
            Generators::elements([
                "table_name'; DROP TABLE users--",
                "field_name' OR '1'='1",
                "field_value'; DELETE FROM base_group--",
                "table_name' UNION SELECT * FROM users--",
                "field_name'; UPDATE base_group SET group_name='hacked'--"
            ])
        )
        ->then(function ($maliciousUsein) {
            // Arrange: Create request with SQL injection payload
            $postData = ['test' => 'data'];
            
            $controller = new GroupController();
            
            // Act: Try to call rolepage with malicious usein
            $exceptionThrown = false;
            $exceptionType = null;
            
            try {
                // rolepage is a public method in the trait
                $controller->rolepage($postData, $maliciousUsein);
            } catch (ControllerValidationException $e) {
                // FIXED CODE: Exception thrown - SQL injection prevented
                $exceptionThrown = true;
                $exceptionType = get_class($e);
            } catch (\Exception $e) {
                // UNFIXED CODE: Other exception or no exception - bug exists
                $exceptionThrown = false;
            }
            
            // Assert: On FIXED code, ControllerValidationException should be thrown
            // On UNFIXED code, no exception or wrong exception - test fails
            if (!$exceptionThrown || $exceptionType !== ControllerValidationException::class) {
                $this->fail(
                    "SQL injection vulnerability confirmed: malicious usein parameter not rejected. " .
                    "Payload: {$maliciousUsein}"
                );
            }
            
            // If we reach here, the fix is working correctly
            $this->assertTrue($exceptionThrown, "ControllerValidationException should be thrown");
        });
    }

    
    /**
     * Property 3: Fault Condition - XSS in buildRoleBox()
     * 
     * **Validates: Requirement 2.15**
     * 
     * For any call to buildRoleBox() with module names, the system SHALL escape
     * $module_name using htmlspecialchars() with ENT_QUOTES and UTF-8 before
     * concatenation with SafeHtml content.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - $module_name concatenated with SafeHtml without escaping
     * - XSS payloads in module names execute in browser
     * - XSS vulnerability (CVSS 7.3)
     * - Counterexamples will show XSS payloads are not escaped
     * 
     * **BUG LOCATION**: MappingPage.php buildRoleBox() method
     * 
     * **NOTE**: This test validates the formatModuleTitle() method which is the
     * core XSS prevention mechanism used by buildRoleBox(). Testing buildRoleBox()
     * directly requires complex route and session setup.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_3_xss_prevention_in_buildRoleBox()
    {
        $this->forAll(
            // Generate XSS payloads
            Generators::elements([
                '<script>alert("XSS")</script>',
                '<img src=x onerror=alert("XSS")>',
                '<svg onload=alert("XSS")>',
                '"><script>alert("XSS")</script>',
                "' onmouseover='alert(\"XSS\")'",
                '<iframe src="javascript:alert(\'XSS\')">',
            ])
        )
        ->then(function ($xssPayload) {
            // Arrange: Create module name with XSS payload
            $moduleName = 'test_module_' . $xssPayload;
            
            // Act: Test the formatModuleTitle() method which is used by buildRoleBox()
            $controller = new GroupController();
            
            // Use reflection to call private formatModuleTitle method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('formatModuleTitle');
            $method->setAccessible(true);
            
            $output = $method->invoke($controller, $moduleName, null);
            
            // Assert: XSS payload should be ESCAPED in output
            // On UNFIXED code, raw XSS payload will be in output (bug exists)
            // On FIXED code, XSS payload will be escaped
            
            // Check that dangerous characters are escaped
            $this->assertStringNotContainsString(
                '<script>',
                $output,
                "XSS vulnerability confirmed: <script> tag not escaped in module name. " .
                "Payload: {$xssPayload}"
            );
            
            $this->assertStringNotContainsString(
                '<img ',
                strtolower($output),
                "XSS vulnerability confirmed: <img> tag not escaped in module name. " .
                "Payload: {$xssPayload}"
            );
            
            $this->assertStringNotContainsString(
                '<svg ',
                strtolower($output),
                "XSS vulnerability confirmed: <svg> tag not escaped in module name. " .
                "Payload: {$xssPayload}"
            );
            
            $this->assertStringNotContainsString(
                '<iframe ',
                strtolower($output),
                "XSS vulnerability confirmed: <iframe> tag not escaped in module name. " .
                "Payload: {$xssPayload}"
            );
            
            // Verify that < and > are escaped (making any HTML tags harmless)
            // Only check if the payload contains these characters
            if (strpos($xssPayload, '<') !== false) {
                $this->assertStringNotContainsString(
                    '<',
                    $output,
                    "XSS vulnerability confirmed: < character not escaped in module name. " .
                    "Payload: {$xssPayload}"
                );
                
                $this->assertStringContainsString(
                    '&lt;',
                    $output,
                    "XSS prevention confirmed: < character properly escaped. " .
                    "Payload: {$xssPayload}"
                );
            }
            
            if (strpos($xssPayload, '>') !== false) {
                $this->assertStringNotContainsString(
                    '>',
                    $output,
                    "XSS vulnerability confirmed: > character not escaped in module name. " .
                    "Payload: {$xssPayload}"
                );
                
                $this->assertStringContainsString(
                    '&gt;',
                    $output,
                    "XSS prevention confirmed: > character properly escaped. " .
                    "Payload: {$xssPayload}"
                );
            }
            
            // Verify quotes are escaped
            if (strpos($xssPayload, '"') !== false) {
                $this->assertStringContainsString(
                    '&quot;',
                    $output,
                    "XSS prevention confirmed: double quote properly escaped. " .
                    "Payload: {$xssPayload}"
                );
            }
            
            if (strpos($xssPayload, "'") !== false) {
                $this->assertTrue(
                    strpos($output, '&#039;') !== false || strpos($output, '&apos;') !== false,
                    "XSS prevention confirmed: single quote properly escaped. " .
                    "Payload: {$xssPayload}"
                );
            }
        });
    }
    
    /**
     * Property 4: Fault Condition - Unsafe URL Construction in ajax_urli()
     * 
     * **Validates: Requirement 2.16**
     * 
     * For any call to ajax_urli(), the system SHALL validate $usein parameter,
     * use http_build_query() for proper encoding, and use Laravel's url() helper.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - URLs built manually without proper encoding
     * - $usein parameter not validated
     * - URL injection possible
     * - Counterexamples will show invalid URLs are not rejected
     * 
     * **BUG LOCATION**: MappingPage.php ajax_urli() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_4_safe_url_construction_in_ajax_urli()
    {
        $this->forAll(
            // Generate URL injection payloads
            Generators::elements([
                'table_name&malicious=param',
                'field_name?extra=value',
                'field_value#fragment',
                'table_name"><script>alert("XSS")</script>',
                "field_name' OR '1'='1",
            ])
        )
        ->then(function ($maliciousUsein) {
            // Arrange: Create controller
            $controller = new GroupController();
            
            // Act: Build AJAX URL with malicious usein
            // Use reflection to call private ajax_urli method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('ajax_urli');
            $method->setAccessible(true);
            
            // On UNFIXED code, this may build unsafe URL (bug exists)
            // On FIXED code, this should validate and reject or properly encode
            try {
                $url = $method->invoke($controller, $maliciousUsein, true);
                
                // If URL is built, verify it's properly encoded
                if ($url !== null) {
                    // Check that special characters are properly encoded
                    $this->assertStringNotContainsString(
                        '<script>',
                        $url,
                        "URL injection vulnerability confirmed: <script> tag in URL. " .
                        "Payload: {$maliciousUsein}"
                    );
                    
                    $this->assertStringNotContainsString(
                        '"><',
                        $url,
                        "URL injection vulnerability confirmed: quote injection in URL. " .
                        "Payload: {$maliciousUsein}"
                    );
                    
                    // Verify proper URL encoding
                    // On FIXED code, special chars should be encoded
                    if (strpos($maliciousUsein, '&') !== false || 
                        strpos($maliciousUsein, '?') !== false ||
                        strpos($maliciousUsein, '#') !== false) {
                        
                        // These should be encoded in the URL
                        $this->assertTrue(
                            strpos($url, '%') !== false || 
                            !strpos($url, $maliciousUsein),
                            "URL encoding vulnerability confirmed: special characters not encoded. " .
                            "Payload: {$maliciousUsein}, URL: {$url}"
                        );
                    }
                }
            } catch (ControllerValidationException $e) {
                // Expected on FIXED code - validation rejects invalid usein
                $this->assertTrue(true, "Validation correctly rejected invalid usein");
            }
        });
    }
}

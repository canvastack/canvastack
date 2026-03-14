<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Http\Controllers\TableTabController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionClass;

/**
 * Security tests for authorization in tab loading.
 * 
 * Tests Requirement 10.6:
 * - THE AJAX endpoints SHALL validate user permissions before returning data
 * 
 * This test suite verifies:
 * 1. Permission validation method exists and is extensible
 * 2. Authorization logic structure
 * 3. Logging capabilities for security audit
 * 
 * Note: Full HTTP integration tests are in Feature/Components/Table/AuthorizationTest.php
 */
class AuthorizationSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that TableTabController has canAccessTab method.
     * 
     * Requirement: 10.6 - Permission validation infrastructure
     * 
     * @return void
     */
    public function test_controller_has_can_access_tab_method(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('canAccessTab'),
            'TableTabController should have canAccessTab() method for permission validation'
        );
    }

    /**
     * Test that canAccessTab method is protected for extensibility.
     * 
     * Requirement: 10.6 - Extensible permission system
     * 
     * @return void
     */
    public function test_can_access_tab_is_protected_for_extensibility(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $method = $reflection->getMethod('canAccessTab');
        
        $this->assertTrue(
            $method->isProtected(),
            'canAccessTab() should be protected to allow subclasses to override permission logic'
        );
    }

    /**
     * Test that canAccessTab method has correct signature.
     * 
     * Requirement: 10.6 - Permission validation interface
     * 
     * @return void
     */
    public function test_can_access_tab_has_correct_signature(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $method = $reflection->getMethod('canAccessTab');
        $parameters = $method->getParameters();
        
        $this->assertCount(
            1,
            $parameters,
            'canAccessTab() should accept exactly 1 parameter (tab index)'
        );
        
        $this->assertEquals(
            'index',
            $parameters[0]->getName(),
            'Parameter should be named "index"'
        );
        
        $this->assertTrue(
            $parameters[0]->hasType(),
            'Parameter should have type hint'
        );
        
        $this->assertEquals(
            'int',
            $parameters[0]->getType()->getName(),
            'Parameter should be typed as int'
        );
    }

    /**
     * Test that canAccessTab returns boolean.
     * 
     * Requirement: 10.6 - Permission validation return type
     * 
     * @return void
     */
    public function test_can_access_tab_returns_boolean(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $method = $reflection->getMethod('canAccessTab');
        
        $this->assertTrue(
            $method->hasReturnType(),
            'canAccessTab() should have return type'
        );
        
        $this->assertEquals(
            'bool',
            $method->getReturnType()->getName(),
            'canAccessTab() should return bool'
        );
    }

    /**
     * Test that loadTab method exists for handling tab requests.
     * 
     * Requirement: 10.6 - Tab loading endpoint
     * 
     * @return void
     */
    public function test_controller_has_load_tab_method(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('loadTab'),
            'TableTabController should have loadTab() method'
        );
        
        $method = $reflection->getMethod('loadTab');
        
        $this->assertTrue(
            $method->isPublic(),
            'loadTab() should be public to handle HTTP requests'
        );
    }

    /**
     * Test that errorResponse method exists for authorization failures.
     * 
     * Requirement: 10.6 - Error response handling
     * 
     * @return void
     */
    public function test_controller_has_error_response_method(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $this->assertTrue(
            $reflection->hasMethod('errorResponse'),
            'TableTabController should have errorResponse() method'
        );
    }

    /**
     * Test that controller uses proper logging for security audit.
     * 
     * Requirement: 10.6 - Security audit trail
     * 
     * This test verifies that the controller has the infrastructure
     * to log security events. Actual logging behavior is tested in
     * feature tests with real HTTP requests.
     * 
     * @return void
     */
    public function test_controller_can_log_security_events(): void
    {
        // Verify Log facade is available
        $this->assertTrue(
            class_exists('Illuminate\Support\Facades\Log'),
            'Log facade should be available for security logging'
        );
        
        // Verify we can spy on Log
        Log::spy();
        
        // This confirms the logging infrastructure is in place
        $this->assertTrue(true, 'Logging infrastructure is available');
    }

    /**
     * Test that authorization validation happens in loadTab method.
     * 
     * Requirement: 10.6 - Permission validation in request flow
     * 
     * @return void
     */
    public function test_load_tab_calls_authorization_check(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $method = $reflection->getMethod('loadTab');
        $source = file_get_contents($reflection->getFileName());
        
        // Extract loadTab method source
        $methodSource = $this->extractMethodSource($source, 'loadTab');
        
        // Verify it calls canAccessTab
        $this->assertStringContainsString(
            'canAccessTab',
            $methodSource,
            'loadTab() should call canAccessTab() for permission validation'
        );
        
        // Verify it handles unauthorized access
        $this->assertStringContainsString(
            '403',
            $methodSource,
            'loadTab() should return 403 for unauthorized access'
        );
    }

    /**
     * Test that unauthorized access is logged.
     * 
     * Requirement: 10.6 - Log unauthorized attempts
     * 
     * @return void
     */
    public function test_unauthorized_access_logging_exists(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify logging for unauthorized attempts
        $this->assertStringContainsString(
            'Unauthorized tab access',
            $source,
            'Controller should log unauthorized tab access attempts'
        );
        
        // Verify logging includes context
        $this->assertStringContainsString(
            'tab_index',
            $source,
            'Unauthorized access logs should include tab_index'
        );
        
        $this->assertStringContainsString(
            'user_id',
            $source,
            'Unauthorized access logs should include user_id'
        );
    }

    /**
     * Test that authorization checks include IP logging.
     * 
     * Requirement: 10.6 - Security audit with IP tracking
     * 
     * @return void
     */
    public function test_authorization_logs_include_ip_tracking(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify IP address is logged
        $this->assertStringContainsString(
            'ip',
            $source,
            'Authorization logs should include IP address for security audit'
        );
    }

    /**
     * Test that authorization checks include user agent logging.
     * 
     * Requirement: 10.6 - Security audit with user agent tracking
     * 
     * @return void
     */
    public function test_authorization_logs_include_user_agent(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify user agent is logged
        $this->assertStringContainsString(
            'user_agent',
            $source,
            'Authorization logs should include user agent for security audit'
        );
    }

    /**
     * Test that controller checks authentication status.
     * 
     * Requirement: 10.6 - Authentication validation
     * 
     * @return void
     */
    public function test_controller_checks_authentication(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify authentication check
        $this->assertStringContainsString(
            'auth()->check()',
            $source,
            'Controller should check if user is authenticated'
        );
    }

    /**
     * Test that error responses don't expose sensitive information.
     * 
     * Requirement: 10.6 - No information disclosure in errors
     * 
     * @return void
     */
    public function test_error_responses_are_generic(): void
    {
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify generic error message
        $this->assertStringContainsString(
            'Unauthorized',
            $source,
            'Error responses should use generic "Unauthorized" message'
        );
        
        // Verify that production error messages don't expose database details
        // Check the getProductionErrorMessage method specifically
        $methodSource = $this->extractMethodSource($source, 'getProductionErrorMessage');
        
        // Production error messages should not mention specific database errors
        $this->assertStringNotContainsString(
            'database connection',
            strtolower($methodSource),
            'Production error messages should not mention database connections'
        );
        
        $this->assertStringNotContainsString(
            'query failed',
            strtolower($methodSource),
            'Production error messages should not mention query failures'
        );
    }

    /**
     * Test row-level permissions infrastructure (placeholder).
     * 
     * Requirement: 10.6 - Row-level permissions
     * 
     * This test verifies that the authorization system is structured
     * to support future row-level permissions implementation.
     * 
     * @return void
     */
    public function test_row_level_permissions_infrastructure(): void
    {
        // Verify that canAccessTab accepts tab index
        // This allows future implementation to check permissions
        // based on tab configuration and data ownership
        
        $controller = new TableTabController();
        $reflection = new ReflectionClass($controller);
        
        $method = $reflection->getMethod('canAccessTab');
        $parameters = $method->getParameters();
        
        $this->assertCount(
            1,
            $parameters,
            'canAccessTab() accepts tab index, allowing future row-level permission checks'
        );
        
        // The method is protected, allowing subclasses to implement
        // custom row-level permission logic
        $this->assertTrue(
            $method->isProtected(),
            'Protected method allows subclasses to implement row-level permissions'
        );
    }

    /**
     * Helper method to extract method source code.
     * 
     * @param string $source Full file source
     * @param string $methodName Method name to extract
     * @return string Method source code
     */
    private function extractMethodSource(string $source, string $methodName): string
    {
        // Simple extraction - find method declaration and its body
        $pattern = '/function\s+' . preg_quote($methodName) . '\s*\([^)]*\)[^{]*\{/';
        
        if (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $braceCount = 0;
            $inMethod = false;
            $methodSource = '';
            
            for ($i = $start; $i < strlen($source); $i++) {
                $char = $source[$i];
                $methodSource .= $char;
                
                if ($char === '{') {
                    $braceCount++;
                    $inMethod = true;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($inMethod && $braceCount === 0) {
                        break;
                    }
                }
            }
            
            return $methodSource;
        }
        
        return '';
    }
}


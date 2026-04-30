<?php

namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Canvastack\Canvastack\Exceptions\Controller\CSRFException;

/**
 * CSRF Protection Test
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Tests CSRF token validation for all state-changing requests.
 * Validates that CSRF protection is properly implemented and enforced.
 * 
 * @package Tests\Security
 * @category Security Testing
 * @version 1.0.0
 */
class CSRFProtectionTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test CSRF validation helper function exists
     * 
     * @test
     */
    public function test_csrf_validation_helper_function_exists()
    {
        $this->assertTrue(
            function_exists('canvastack_controller_validate_csrf'),
            'CSRF validation helper function should exist'
        );
    }
    
    /**
     * Test CSRF validation with valid token
     * 
     * @test
     */
    public function test_csrf_validation_passes_with_valid_token()
    {
        // Create a mock request with valid CSRF token
        $request = $this->createRequestWithToken();
        
        // Validation should pass
        $result = canvastack_controller_validate_csrf($request);
        
        $this->assertTrue($result, 'CSRF validation should pass with valid token');
    }
    
    /**
     * Test CSRF validation fails with missing token
     * 
     * @test
     */
    public function test_csrf_validation_fails_with_missing_token()
    {
        $this->expectException(CSRFException::class);
        $this->expectExceptionMessage('CSRF token is missing');
        
        // Create a mock request without CSRF token
        $request = $this->createRequestWithoutToken();
        
        // Validation should throw exception
        canvastack_controller_validate_csrf($request);
    }
    
    /**
     * Test CSRF validation fails with invalid token
     * 
     * @test
     */
    public function test_csrf_validation_fails_with_invalid_token()
    {
        $this->expectException(CSRFException::class);
        $this->expectExceptionMessage('CSRF token mismatch');
        
        // Create a mock request with invalid CSRF token
        $request = $this->createRequestWithInvalidToken();
        
        // Validation should throw exception
        canvastack_controller_validate_csrf($request);
    }
    
    /**
     * Test CSRF validation accepts token from request body
     * 
     * @test
     */
    public function test_csrf_validation_accepts_token_from_body()
    {
        $token = 'test-csrf-token-123';
        
        // Create request with token in body
        $request = $this->createRequest([
            '_token' => $token,
        ], $token);
        
        $result = canvastack_controller_validate_csrf($request);
        
        $this->assertTrue($result, 'CSRF validation should accept token from request body');
    }
    
    /**
     * Test CSRF validation accepts token from X-CSRF-TOKEN header
     * 
     * @test
     */
    public function test_csrf_validation_accepts_token_from_header()
    {
        $token = 'test-csrf-token-456';
        
        // Create request with token in header
        $request = $this->createRequest([], $token, [
            'X-CSRF-TOKEN' => $token,
        ]);
        
        $result = canvastack_controller_validate_csrf($request);
        
        $this->assertTrue($result, 'CSRF validation should accept token from X-CSRF-TOKEN header');
    }
    
    /**
     * Test CSRF validation accepts token from X-XSRF-TOKEN header
     * 
     * @test
     */
    public function test_csrf_validation_accepts_token_from_xsrf_header()
    {
        $token = 'test-csrf-token-789';
        
        // Create request with token in XSRF header
        $request = $this->createRequest([], $token, [
            'X-XSRF-TOKEN' => $token,
        ]);
        
        $result = canvastack_controller_validate_csrf($request);
        
        $this->assertTrue($result, 'CSRF validation should accept token from X-XSRF-TOKEN header');
    }
    
    /**
     * Test CSRF validation can be disabled via configuration
     * 
     * @test
     */
    public function test_csrf_validation_can_be_disabled_via_config()
    {
        // Disable CSRF protection in config
        config(['canvastack.controller.security.csrf_protection' => false]);
        
        // Create request without token
        $request = $this->createRequestWithoutToken();
        
        // Validation should pass when disabled
        $result = canvastack_controller_validate_csrf($request);
        
        $this->assertTrue($result, 'CSRF validation should pass when disabled in config');
        
        // Re-enable for other tests
        config(['canvastack.controller.security.csrf_protection' => true]);
    }
    
    /**
     * Test CSRF exception includes context data
     * 
     * @test
     */
    public function test_csrf_exception_includes_context_data()
    {
        try {
            $request = $this->createRequestWithoutToken();
            canvastack_controller_validate_csrf($request);
            
            $this->fail('Expected CSRFException was not thrown');
        } catch (CSRFException $e) {
            $context = $e->getContext();
            
            $this->assertIsArray($context, 'Exception context should be an array');
            $this->assertArrayHasKey('url', $context, 'Context should include URL');
            $this->assertArrayHasKey('method', $context, 'Context should include HTTP method');
        }
    }
    
    /**
     * Test CSRF exception provides user-friendly message
     * 
     * @test
     */
    public function test_csrf_exception_provides_user_friendly_message()
    {
        try {
            $request = $this->createRequestWithoutToken();
            canvastack_controller_validate_csrf($request);
            
            $this->fail('Expected CSRFException was not thrown');
        } catch (CSRFException $e) {
            $userMessage = $e->getUserMessage();
            
            $this->assertIsString($userMessage, 'User message should be a string');
            $this->assertNotEmpty($userMessage, 'User message should not be empty');
            $this->assertStringContainsString('security', strtolower($userMessage), 
                'User message should mention security');
        }
    }
    
    /**
     * Helper: Create request with valid CSRF token
     */
    private function createRequestWithToken()
    {
        $token = 'valid-csrf-token';
        return $this->createRequest(['_token' => $token], $token);
    }
    
    /**
     * Helper: Create request without CSRF token
     */
    private function createRequestWithoutToken()
    {
        return $this->createRequest([], 'session-token');
    }
    
    /**
     * Helper: Create request with invalid CSRF token
     */
    private function createRequestWithInvalidToken()
    {
        return $this->createRequest(['_token' => 'invalid-token'], 'valid-session-token');
    }
    
    /**
     * Helper: Create mock request
     * 
     * @param array $data Request data
     * @param string $sessionToken Session token
     * @param array $headers Request headers
     * @return \Illuminate\Http\Request
     */
    private function createRequest(array $data = [], string $sessionToken = '', array $headers = [])
    {
        $request = \Illuminate\Http\Request::create(
            '/test-url',
            'POST',
            $data,
            [],
            [],
            [],
            null
        );
        
        // Add headers
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        
        // Mock session
        $session = new \Illuminate\Session\Store(
            'test-session',
            new \Illuminate\Session\ArraySessionHandler(60)
        );
        $session->setId('test-session-id');
        $session->start();
        
        if ($sessionToken) {
            $session->put('_token', $sessionToken);
        }
        
        $request->setLaravelSession($session);
        
        return $request;
    }
}

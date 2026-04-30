<?php

namespace Tests\Unit\Controllers\Core\Craft;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

/**
 * Session Security Test
 * 
 * Tests session security features including validation, integrity checks,
 * encryption, timeout, and session destruction.
 * 
 * @package Tests\Unit\Controllers\Core\Craft
 */
class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test session data validation with valid data
     */
    public function test_session_data_validation_with_valid_data()
    {
        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Valid session data
        $validSessionData = [
            'id' => 1,
            'username' => 'testuser',
            'group_id' => 1,
            'user_group' => 'admin',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
        ];

        // Should not throw exception
        $result = $controller->testValidateSessionData($validSessionData);
        $this->assertTrue($result);
    }

    /**
     * Test session data validation with missing required field
     */
    public function test_session_data_validation_with_missing_required_field()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session data missing required field');

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Invalid session data (missing 'username')
        $invalidSessionData = [
            'id' => 1,
            'group_id' => 1,
            'user_group' => 'admin',
        ];

        $controller->testValidateSessionData($invalidSessionData);
    }

    /**
     * Test session data validation with invalid email
     */
    public function test_session_data_validation_with_invalid_email()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session email has invalid format');

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Invalid session data (invalid email)
        $invalidSessionData = [
            'id' => 1,
            'username' => 'testuser',
            'group_id' => 1,
            'user_group' => 'admin',
            'email' => 'invalid-email',
        ];

        $controller->testValidateSessionData($invalidSessionData);
    }

    /**
     * Test session ID regeneration
     */
    public function test_session_id_regeneration()
    {
        // Enable session regeneration in config
        Config::set('canvastack.controller.security.regenerate_session_id', true);

        // Start session
        Session::start();
        $oldSessionId = Session::getId();

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
        };

        // Regenerate session ID
        $controller->regenerateSessionId();

        // Session ID should be different
        $newSessionId = Session::getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * Test session timeout detection
     */
    public function test_session_timeout_detection()
    {
        // Set short timeout for testing
        Config::set('canvastack.controller.security.session_timeout', 1);

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
        };

        // Set last activity to 2 seconds ago
        Session::put('last_activity', time() - 2);

        // Session should be expired
        $this->assertTrue($controller->isSessionExpired());
    }

    /**
     * Test session not expired
     */
    public function test_session_not_expired()
    {
        // Set long timeout for testing
        Config::set('canvastack.controller.security.session_timeout', 7200);

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
        };

        // Set last activity to now
        Session::put('last_activity', time());

        // Session should not be expired
        $this->assertFalse($controller->isSessionExpired());
    }

    /**
     * Test session data encryption
     */
    public function test_session_data_encryption()
    {
        // Enable encryption in config
        Config::set('canvastack.controller.session.encrypt_sensitive_data', true);

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
        };

        // Encrypt data
        $sensitiveData = 'secret-password';
        $encrypted = $controller->encryptSessionData($sensitiveData);

        // Encrypted data should be different from original
        $this->assertNotEquals($sensitiveData, $encrypted);

        // Decrypt data
        $decrypted = $controller->decryptSessionData($encrypted);

        // Decrypted data should match original
        $this->assertEquals($sensitiveData, $decrypted);
    }

    /**
     * Test session destruction
     */
    public function test_session_destruction()
    {
        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function __construct() {
                $this->session = [
                    'id' => 1,
                    'username' => 'testuser',
                ];
                $this->session_roles = [
                    'roles' => ['admin'],
                ];
            }
        };

        // Start session and add data
        Session::start();
        Session::put('user_id', 1);
        Session::put('username', 'testuser');

        // Destroy session
        $controller->destroySession();

        // Session data should be cleared
        $this->assertEmpty($controller->session);
        $this->assertEmpty($controller->session_roles);
        $this->assertNull(Session::get('user_id'));
        $this->assertNull(Session::get('username'));
    }

    /**
     * Test session validation with empty data
     */
    public function test_session_validation_with_empty_data()
    {
        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Empty session data should be valid (user not logged in)
        $result = $controller->testValidateSessionData([]);
        $this->assertTrue($result);
    }

    /**
     * Test session validation with partial data (guest user)
     */
    public function test_session_validation_with_partial_data_guest_user()
    {
        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Partial session data without 'id' should be valid (guest user)
        $partialSessionData = [
            'some_key' => 'some_value',
            'another_key' => 'another_value',
        ];
        
        $result = $controller->testValidateSessionData($partialSessionData);
        $this->assertTrue($result);
    }

    /**
     * Test session validation with invalid user ID
     */
    public function test_session_validation_with_invalid_user_id()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session user ID must be positive');

        // Create a mock controller with Session trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;
            
            public function testValidateSessionData($data) {
                return $this->validateSessionData($data);
            }
        };

        // Invalid session data (negative user ID)
        $invalidSessionData = [
            'id' => -1,
            'username' => 'testuser',
            'group_id' => 1,
            'user_group' => 'admin',
        ];

        $controller->testValidateSessionData($invalidSessionData);
    }
}

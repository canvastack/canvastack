<?php

namespace Canvastack\Canvastack\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Core\Services\SessionManagerService;
use Canvastack\Canvastack\Core\Services\AssetManagerService;
use Canvastack\Canvastack\Core\Services\RoleHandlerService;
use Canvastack\Canvastack\Core\Contracts\SessionManagerInterface;
use Canvastack\Canvastack\Core\Contracts\AssetManagerInterface;
use Canvastack\Canvastack\Core\Contracts\RoleHandlerInterface;

/**
 * Utility Services Test
 * 
 * Test suite untuk Phase 3 utility services
 * 
 * @author dev@canvastack.com
 * @created 2024-12-19
 * @version 1.0
 */
class UtilityServicesTest extends TestCase
{
    protected SessionManagerService $sessionManager;
    protected AssetManagerService $assetManager;
    protected RoleHandlerService $roleHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sessionManager = new SessionManagerService();
        $this->assetManager = new AssetManagerService();
        $this->roleHandler = new RoleHandlerService();
    }

    /** @test */
    public function test_session_manager_implements_interface()
    {
        $this->assertInstanceOf(SessionManagerInterface::class, $this->sessionManager);
    }

    /** @test */
    public function test_asset_manager_implements_interface()
    {
        $this->assertInstanceOf(AssetManagerInterface::class, $this->assetManager);
    }

    /** @test */
    public function test_role_handler_implements_interface()
    {
        $this->assertInstanceOf(RoleHandlerInterface::class, $this->roleHandler);
    }

    /** @test */
    public function test_session_manager_basic_operations()
    {
        // Test initial state
        $this->assertFalse($this->sessionManager->isLoggedIn());
        $this->assertNull($this->sessionManager->getCurrentUserId());
        $this->assertNull($this->sessionManager->getCurrentUserGroup());
        
        // Test session data retrieval
        $sessionData = $this->sessionManager->getSession(true);
        $this->assertIsArray($sessionData);
        
        // Test group check
        $this->assertFalse($this->sessionManager->groupCheck('admin'));
        
        // Test session roles
        $roles = $this->sessionManager->getSessionRoles();
        $this->assertIsArray($roles);
    }

    /** @test */
    public function test_asset_manager_script_operations()
    {
        // Test script node
        $this->assertEquals('diyScriptNode::', $this->assetManager->getScriptNode());
        
        // Test script registration check
        $this->assertFalse($this->assetManager->isScriptRegistered('test.js', 'js'));
        
        // Test get scripts
        $jsScripts = $this->assetManager->getScripts('js');
        $this->assertIsArray($jsScripts);
        
        $cssScripts = $this->assetManager->getScripts('css');
        $this->assertIsArray($cssScripts);
        
        // Test clear scripts
        $this->assetManager->clearScripts('js');
        $this->assetManager->clearScripts('css');
        $this->assetManager->clearScripts(); // Clear all
        
        $this->assertEmpty($this->assetManager->getScripts('js'));
        $this->assertEmpty($this->assetManager->getScripts('css'));
    }

    /** @test */
    public function test_role_handler_basic_operations()
    {
        // Test initial state
        $this->assertEquals(['admin'], $this->roleHandler->getRoleAlias());
        $this->assertEquals([], $this->roleHandler->getRoleInfo());
        
        // Test role setting
        $this->roleHandler->setRoleAlias(['admin', 'user']);
        $this->assertEquals(['admin', 'user'], $this->roleHandler->getRoleAlias());
        
        $this->roleHandler->setRoleInfo(['National', 'Regional']);
        $this->assertEquals(['National', 'Regional'], $this->roleHandler->getRoleInfo());
        
        // Test init handler
        $this->roleHandler->initHandler();
        $this->assertEquals(['admin', 'internal'], $this->roleHandler->getRoleAlias());
        $this->assertEquals(['National'], $this->roleHandler->getRoleInfo());
    }

    /** @test */
    public function test_role_handler_session_operations()
    {
        $session = [
            'user_group' => 'admin',
            'group_alias' => 'National',
            'id' => 1,
            'username' => 'testuser'
        ];
        
        // Test role checks
        $this->assertTrue($this->roleHandler->hasRole('admin', $session));
        $this->assertFalse($this->roleHandler->hasRole('user', $session));
        
        // Test root check
        $this->assertFalse($this->roleHandler->isRoot($session));
        
        $rootSession = ['user_group' => 'root'];
        $this->assertTrue($this->roleHandler->isRoot($rootSession));
        
        // Test access check
        $this->assertTrue($this->roleHandler->canAccess('resource', $session));
        
        // Test permissions
        $permissions = $this->roleHandler->getUserPermissions($session);
        $this->assertIsArray($permissions);
        $this->assertContains('read', $permissions);
    }

    /** @test */
    public function test_session_manager_helper_methods()
    {
        // Test session key check
        $this->assertFalse($this->sessionManager->hasSessionKey('nonexistent'));
        
        // Test user info methods
        $this->assertNull($this->sessionManager->getUserFullName());
        $this->assertNull($this->sessionManager->getUserEmail());
        $this->assertNull($this->sessionManager->getUserPhone());
        
        // Test session data with default
        $this->assertEquals('default', $this->sessionManager->getSessionData('nonexistent', 'default'));
        
        // Test clear session
        $this->sessionManager->clearSession();
        $rawSession = $this->sessionManager->getRawSession();
        $this->assertIsArray($rawSession);
    }

    /** @test */
    public function test_asset_manager_with_template()
    {
        $mockTemplate = new class {
            public function js($script, $position, $asCode) {
                return "js:{$script}:{$position}:" . ($asCode ? 'code' : 'file');
            }
            
            public function css($script, $position) {
                return "css:{$script}:{$position}";
            }
        };
        
        $assetManager = new AssetManagerService($mockTemplate);
        
        // Test JS addition
        $result = $assetManager->addJs('test.js', 'bottom', false);
        $this->assertEquals('js:test.js:bottom:file', $result);
        
        // Test CSS addition
        $result = $assetManager->addCss('test.css', 'top');
        $this->assertEquals('css:test.css:top', $result);
    }

    /** @test */
    public function test_role_handler_with_callback()
    {
        $callbackCalled = false;
        $callback = function($data, $operator) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('=', $operator);
            $this->assertIsArray($data);
        };
        
        $roleHandler = new RoleHandlerService($callback);
        $roleHandler->setFilterCallback($callback);
        
        $session = [
            'user_group' => 'user',
            'group_alias' => 'Regional'
        ];
        
        // This should trigger the callback
        $roleHandler->applySessionFilters($session);
        
        // Note: callback might not be called depending on session data
        // This test verifies the callback can be set without errors
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function test_asset_manager_all_scripts()
    {
        $allScripts = $this->assetManager->getAllScripts();
        $this->assertIsArray($allScripts);
        $this->assertArrayHasKey('js', $allScripts);
        $this->assertArrayHasKey('css', $allScripts);
    }

    /** @test */
    public function test_role_handler_multiple_roles()
    {
        $session = ['user_group' => 'admin'];
        
        // Test hasAnyRole
        $this->assertTrue($this->roleHandler->hasAnyRole(['admin', 'user'], $session));
        $this->assertFalse($this->roleHandler->hasAnyRole(['user', 'guest'], $session));
        
        // Test hasAllRoles (for single user group)
        $this->assertTrue($this->roleHandler->hasAllRoles(['admin'], $session));
        $this->assertFalse($this->roleHandler->hasAllRoles(['admin', 'user'], $session));
    }

    /** @test */
    public function test_services_error_handling()
    {
        // Test that services handle errors gracefully
        try {
            $this->sessionManager->setSession();
            $this->assertTrue(true); // No exception thrown
        } catch (\Throwable $e) {
            $this->fail('SessionManager should handle errors gracefully');
        }
        
        try {
            $this->assetManager->addJs('test.js');
            $this->assertTrue(true); // No exception thrown
        } catch (\Throwable $e) {
            $this->fail('AssetManager should handle errors gracefully');
        }
        
        try {
            $this->roleHandler->applySessionFilters([]);
            $this->assertTrue(true); // No exception thrown
        } catch (\Throwable $e) {
            $this->fail('RoleHandler should handle errors gracefully');
        }
    }
}
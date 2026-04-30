<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unit tests for magic number replacement in privilege system
 * 
 * Verifies that PrivilegeConstants are used instead of magic numbers
 * and that the privilege system continues working correctly.
 * 
 * @group unit
 * @group privileges
 * @group constants
 */
class PrivilegeMagicNumberReplacementTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test privileges_before_insert() uses constants instead of magic numbers
     */
    public function test_privileges_before_insert_uses_constants()
    {
        // Create a test group
        $group = (object)[
            'id' => 1,
            'group_name' => 'test_group',
            'group_alias' => 'Test Group',
            'group_info' => 'Test group for constants'
        ];
        
        // Create request with privilege data using numeric flags
        $request = Request::create('/test', 'POST', [
            'modules' => [
                'admin_privilege' => [
                    'admin.system.groups' => [
                        8 => 1,  // READ privilege for module ID 1
                        4 => 1,  // WRITE privilege for module ID 1
                        2 => 1,  // MODIFY privilege for module ID 1
                        1 => 1   // DELETE privilege for module ID 1
                    ]
                ]
            ]
        ]);
        
        // Use reflection to access private method
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('privileges_before_insert');
        $method->setAccessible(true);
        
        // Get roles property
        $rolesProperty = $reflection->getProperty('roles');
        $rolesProperty->setAccessible(true);
        
        // Call the method
        $method->invoke($controller, $request, $group);
        
        // Get the roles array
        $roles = $rolesProperty->getValue($controller);
        
        // Verify that privilege names are used (not magic numbers)
        $this->assertArrayHasKey(1, $roles);
        $this->assertArrayHasKey('admin_privilege', $roles[1]);
        
        // Verify privilege names match constants
        $this->assertArrayHasKey('read', $roles[1]['admin_privilege']);
        $this->assertArrayHasKey('insert', $roles[1]['admin_privilege']);
        $this->assertArrayHasKey('update', $roles[1]['admin_privilege']);
        $this->assertArrayHasKey('delete', $roles[1]['admin_privilege']);
        
        // Verify values are correct
        $this->assertEquals(8, $roles[1]['admin_privilege']['read']);
        $this->assertEquals(4, $roles[1]['admin_privilege']['insert']);
        $this->assertEquals(2, $roles[1]['admin_privilege']['update']);
        $this->assertEquals(1, $roles[1]['admin_privilege']['delete']);
    }
    
    /**
     * Test privileges_before_insert() logs warning for invalid flags
     */
    public function test_privileges_before_insert_logs_warning_for_invalid_flags()
    {
        // Mock the Log facade to expect a warning
        Log::spy();
        
        $group = (object)[
            'id' => 1,
            'group_name' => 'test_group'
        ];
        
        // Create request with invalid privilege flag
        $request = Request::create('/test', 'POST', [
            'modules' => [
                'admin_privilege' => [
                    'admin.system.groups' => [
                        99 => 1  // Invalid privilege flag
                    ]
                ]
            ]
        ]);
        
        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('privileges_before_insert');
        $method->setAccessible(true);
        
        // Call the method - should log warning
        $method->invoke($controller, $request, $group);
        
        // Verify warning was logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Invalid privilege flag detected', \Mockery::on(function ($context) {
                return isset($context['flag']) && 
                       $context['flag'] == 99 &&
                       isset($context['module_id']) && 
                       isset($context['group_id']);
            }));
    }
    
    /**
     * Test privileges_after_insert() uses constants for context strings
     */
    public function test_privileges_after_insert_uses_constants_for_context()
    {
        // This test verifies that INDEX_PRIVILEGE and ADMIN_PRIVILEGE constants are used
        // We verify this by checking the Privileges trait source code
        
        $traitFile = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/Includes/Privileges.php');
        $this->assertFileExists($traitFile, 'Privileges trait file should exist');
        
        $source = file_get_contents($traitFile);
        
        // Verify PrivilegeConstants is imported
        $this->assertStringContainsString('use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;', $source);
        
        // Verify constants are used in privileges_after_insert method
        $this->assertStringContainsString('PrivilegeConstants::INDEX_PRIVILEGE', $source);
        $this->assertStringContainsString('PrivilegeConstants::ADMIN_PRIVILEGE', $source);
        
        // Verify the old property references are replaced
        // The method should use $IDP and $ADP variables assigned from constants
        $this->assertStringContainsString('$IDP     = PrivilegeConstants::INDEX_PRIVILEGE;', $source);
        $this->assertStringContainsString('$ADP     = PrivilegeConstants::ADMIN_PRIVILEGE;', $source);
    }
    
    /**
     * Test privilege system continues working correctly with constants
     */
    public function test_privilege_system_works_correctly_with_constants()
    {
        // Verify that the numeric values haven't changed
        $this->assertEquals(8, PrivilegeConstants::READ);
        $this->assertEquals(4, PrivilegeConstants::WRITE);
        $this->assertEquals(2, PrivilegeConstants::MODIFY);
        $this->assertEquals(1, PrivilegeConstants::DELETE);
        
        // Verify that getName() returns the correct privilege names
        $this->assertEquals('read', PrivilegeConstants::getName(8));
        $this->assertEquals('insert', PrivilegeConstants::getName(4));
        $this->assertEquals('update', PrivilegeConstants::getName(2));
        $this->assertEquals('delete', PrivilegeConstants::getName(1));
        
        // Verify context constants
        $this->assertEquals('index_privilege', PrivilegeConstants::INDEX_PRIVILEGE);
        $this->assertEquals('admin_privilege', PrivilegeConstants::ADMIN_PRIVILEGE);
    }
    
    /**
     * Test that all privilege flags can be validated
     */
    public function test_all_privilege_flags_can_be_validated()
    {
        $flags = [8, 4, 2, 1];
        
        foreach ($flags as $flag) {
            $this->assertTrue(
                PrivilegeConstants::isValid($flag),
                "Flag {$flag} should be valid"
            );
            
            $this->assertNotNull(
                PrivilegeConstants::getName($flag),
                "Flag {$flag} should have a name"
            );
            
            $this->assertNotNull(
                PrivilegeConstants::getLabel($flag),
                "Flag {$flag} should have a label"
            );
        }
    }
    
    /**
     * Test privilege combination using constants
     */
    public function test_privilege_combination_using_constants()
    {
        // Test combining privileges using bitwise OR
        $readWrite = PrivilegeConstants::READ | PrivilegeConstants::WRITE;
        $this->assertEquals(12, $readWrite);
        
        $allPrivileges = PrivilegeConstants::READ | 
                        PrivilegeConstants::WRITE | 
                        PrivilegeConstants::MODIFY | 
                        PrivilegeConstants::DELETE;
        $this->assertEquals(15, $allPrivileges);
        
        // Test checking privileges using hasPrivilege()
        $this->assertTrue(PrivilegeConstants::hasPrivilege($readWrite, PrivilegeConstants::READ));
        $this->assertTrue(PrivilegeConstants::hasPrivilege($readWrite, PrivilegeConstants::WRITE));
        $this->assertFalse(PrivilegeConstants::hasPrivilege($readWrite, PrivilegeConstants::MODIFY));
        $this->assertFalse(PrivilegeConstants::hasPrivilege($readWrite, PrivilegeConstants::DELETE));
    }
}

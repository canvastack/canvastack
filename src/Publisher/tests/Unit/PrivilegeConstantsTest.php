<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

/**
 * Unit tests for PrivilegeConstants class
 * 
 * Tests all helper methods to ensure constants work correctly
 * and replace magic numbers throughout the privilege system.
 * 
 * @group unit
 * @group constants
 * @group privileges
 */
class PrivilegeConstantsTest extends TestCase
{
    /**
     * Test getName() returns correct names for all flags
     */
    public function test_getName_returns_correct_names_for_all_flags()
    {
        $this->assertEquals('read', PrivilegeConstants::getName(8));
        $this->assertEquals('insert', PrivilegeConstants::getName(4));
        $this->assertEquals('update', PrivilegeConstants::getName(2));
        $this->assertEquals('delete', PrivilegeConstants::getName(1));
    }
    
    /**
     * Test getName() returns null for invalid flags
     */
    public function test_getName_returns_null_for_invalid_flags()
    {
        $this->assertNull(PrivilegeConstants::getName(0));
        $this->assertNull(PrivilegeConstants::getName(3));
        $this->assertNull(PrivilegeConstants::getName(99));
        $this->assertNull(PrivilegeConstants::getName(-1));
    }
    
    /**
     * Test getLabel() returns correct labels for all flags
     */
    public function test_getLabel_returns_correct_labels_for_all_flags()
    {
        $this->assertEquals('Read', PrivilegeConstants::getLabel(8));
        $this->assertEquals('Insert', PrivilegeConstants::getLabel(4));
        $this->assertEquals('Update', PrivilegeConstants::getLabel(2));
        $this->assertEquals('Delete', PrivilegeConstants::getLabel(1));
    }
    
    /**
     * Test getLabel() returns null for invalid flags
     */
    public function test_getLabel_returns_null_for_invalid_flags()
    {
        $this->assertNull(PrivilegeConstants::getLabel(0));
        $this->assertNull(PrivilegeConstants::getLabel(3));
        $this->assertNull(PrivilegeConstants::getLabel(99));
        $this->assertNull(PrivilegeConstants::getLabel(-1));
    }
    
    /**
     * Test isValid() correctly validates flags
     */
    public function test_isValid_correctly_validates_flags()
    {
        // Valid flags
        $this->assertTrue(PrivilegeConstants::isValid(8));
        $this->assertTrue(PrivilegeConstants::isValid(4));
        $this->assertTrue(PrivilegeConstants::isValid(2));
        $this->assertTrue(PrivilegeConstants::isValid(1));
        
        // Invalid flags
        $this->assertFalse(PrivilegeConstants::isValid(0));
        $this->assertFalse(PrivilegeConstants::isValid(3));
        $this->assertFalse(PrivilegeConstants::isValid(99));
        $this->assertFalse(PrivilegeConstants::isValid(-1));
    }
    
    /**
     * Test getAllFlags() returns all privilege flags
     */
    public function test_getAllFlags_returns_all_privilege_flags()
    {
        $flags = PrivilegeConstants::getAllFlags();
        
        $this->assertIsArray($flags);
        $this->assertCount(4, $flags);
        $this->assertContains(8, $flags);
        $this->assertContains(4, $flags);
        $this->assertContains(2, $flags);
        $this->assertContains(1, $flags);
    }
    
    /**
     * Test hasPrivilege() correctly checks privilege sets
     */
    public function test_hasPrivilege_correctly_checks_privilege_sets()
    {
        // Test READ privilege (8)
        $this->assertTrue(PrivilegeConstants::hasPrivilege(8, PrivilegeConstants::READ));
        $this->assertFalse(PrivilegeConstants::hasPrivilege(8, PrivilegeConstants::WRITE));
        
        // Test READ + WRITE (12 = 8 + 4)
        $this->assertTrue(PrivilegeConstants::hasPrivilege(12, PrivilegeConstants::READ));
        $this->assertTrue(PrivilegeConstants::hasPrivilege(12, PrivilegeConstants::WRITE));
        $this->assertFalse(PrivilegeConstants::hasPrivilege(12, PrivilegeConstants::MODIFY));
        $this->assertFalse(PrivilegeConstants::hasPrivilege(12, PrivilegeConstants::DELETE));
        
        // Test all privileges (15 = 8 + 4 + 2 + 1)
        $this->assertTrue(PrivilegeConstants::hasPrivilege(15, PrivilegeConstants::READ));
        $this->assertTrue(PrivilegeConstants::hasPrivilege(15, PrivilegeConstants::WRITE));
        $this->assertTrue(PrivilegeConstants::hasPrivilege(15, PrivilegeConstants::MODIFY));
        $this->assertTrue(PrivilegeConstants::hasPrivilege(15, PrivilegeConstants::DELETE));
        
        // Test no privileges
        $this->assertFalse(PrivilegeConstants::hasPrivilege(0, PrivilegeConstants::READ));
    }
    
    /**
     * Test constant values match expected bitwise flags
     */
    public function test_constant_values_match_expected_bitwise_flags()
    {
        $this->assertEquals(8, PrivilegeConstants::READ);
        $this->assertEquals(4, PrivilegeConstants::WRITE);
        $this->assertEquals(2, PrivilegeConstants::MODIFY);
        $this->assertEquals(1, PrivilegeConstants::DELETE);
    }
    
    /**
     * Test context constants are defined correctly
     */
    public function test_context_constants_are_defined_correctly()
    {
        $this->assertEquals('index_privilege', PrivilegeConstants::INDEX_PRIVILEGE);
        $this->assertEquals('admin_privilege', PrivilegeConstants::ADMIN_PRIVILEGE);
    }
    
    /**
     * Test privilege names array is complete
     */
    public function test_privilege_names_array_is_complete()
    {
        $names = PrivilegeConstants::PRIVILEGE_NAMES;
        
        $this->assertIsArray($names);
        $this->assertCount(4, $names);
        $this->assertEquals('read', $names[8]);
        $this->assertEquals('insert', $names[4]);
        $this->assertEquals('update', $names[2]);
        $this->assertEquals('delete', $names[1]);
    }
    
    /**
     * Test privilege labels array is complete
     */
    public function test_privilege_labels_array_is_complete()
    {
        $labels = PrivilegeConstants::PRIVILEGE_LABELS;
        
        $this->assertIsArray($labels);
        $this->assertCount(4, $labels);
        $this->assertEquals('Read', $labels[8]);
        $this->assertEquals('Insert', $labels[4]);
        $this->assertEquals('Update', $labels[2]);
        $this->assertEquals('Delete', $labels[1]);
    }
}

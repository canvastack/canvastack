<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\MappingPage;

/**
 * Unit Tests for Mapping Page "Clear All" Bugfix
 * 
 * Tests that mapping page privileges are correctly cleared when user
 * removes all mapping privileges from a group.
 * 
 * **Bug:** mapping_before_insert() had early returns that prevented
 * insert_process() from being called when data was empty, so DELETE
 * logic never ran.
 * 
 * **Fix:** Remove early returns, always call insert_process() even
 * with empty array, let insert_process() handle deletion.
 */
class MappingPageClearAllBugfixTest extends TestCase
{
    use RefreshDatabase;

    protected $testModule;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test module directly in database
        $this->testModule = \Canvastack\Canvastack\Models\Admin\System\Modules::create([
            'id' => 9999,
            'route_path' => 'admin.test.bugfix',
            'parent_name' => null,
            'module_name' => 'Test Bugfix Module',
            'module_info' => 'Module for testing mapping page clear all bugfix',
            'icon' => null,
            'menu_sort' => '999',
            'flag_status' => 0,
            'active' => 1
        ]);
    }

    /**
     * Test Scenario 4: Has both tabs → Clear both
     * 
     * @test
     */
    public function test_clear_both_module_and_mapping_privileges()
    {
        // Arrange: Create group with both privileges
        $group = \App\Models\Admin\System\Group::create([
            'group_name' => 'test_clear_both',
            'group_alias' => 'Test Clear Both',
            'group_info' => 'Test clearing both privileges',
            'active' => 1
        ]);

        // Insert module privilege using query builder (bypass mass assignment)
        \DB::table('base_group_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'admin_privilege' => '8:4',
            'index_privilege' => '8'
        ]);

        // Insert mapping privilege using query builder
        \DB::table('base_page_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'target_table' => 'users',
            'target_field_name' => 'department',
            'target_field_values' => 'sales::marketing'
        ]);

        // Verify data exists
        $this->assertDatabaseHas('base_group_privilege', ['group_id' => $group->id]);
        $this->assertDatabaseHas('base_page_privilege', ['group_id' => $group->id]);

        // Act: Submit form with NO modules and NO __node__ (clear all)
        $request = Request::create("/groups/{$group->id}", 'PUT', [
            'group_name' => 'test_clear_both',
            'group_alias' => 'Test Clear Both',
            'group_info' => 'Test clearing both privileges',
            'active' => 1
            // NO 'modules' key
            // NO '__node__' key
        ]);

        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $group->id);

        $rolesProperty = $reflection->getProperty('roles');
        $rolesProperty->setAccessible(true);
        $roles = $rolesProperty->getValue($controller);

        $afterMethod = $reflection->getMethod('set_data_after_insert');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($controller, $roles);

        // Assert: Module privileges cleared (set to NULL)
        $modulePrivilege = \Canvastack\Canvastack\Models\Admin\System\Privilege::where('group_id', $group->id)->first();
        $this->assertNotNull($modulePrivilege, 'Module privilege record should exist');
        $this->assertNull($modulePrivilege->admin_privilege, 'Admin privilege should be NULL');
        $this->assertNull($modulePrivilege->index_privilege, 'Index privilege should be NULL');

        // Assert: Mapping privileges deleted (BUGFIX - this was broken before)
        $mappingCount = MappingPage::where('group_id', $group->id)->count();
        $this->assertEquals(0, $mappingCount, 'All mapping privileges should be deleted');
    }

    /**
     * Test Scenario 6: Has both → Clear mapping only
     * 
     * @test
     */
    public function test_clear_mapping_only_keep_module()
    {
        // Arrange: Create group with both privileges
        $group = \App\Models\Admin\System\Group::create([
            'group_name' => 'test_clear_mapping',
            'group_alias' => 'Test Clear Mapping',
            'group_info' => 'Test clearing mapping only',
            'active' => 1
        ]);

        // Insert module privilege using query builder
        \DB::table('base_group_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'admin_privilege' => '8:4',
            'index_privilege' => '8'
        ]);

        // Insert mapping privilege using query builder
        \DB::table('base_page_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'target_table' => 'users',
            'target_field_name' => 'department',
            'target_field_values' => 'sales'
        ]);

        // Act: Submit form with modules but NO __node__ (clear mapping only)
        $request = Request::create("/groups/{$group->id}", 'PUT', [
            'group_name' => 'test_clear_mapping',
            'group_alias' => 'Test Clear Mapping',
            'group_info' => 'Test clearing mapping only',
            'active' => 1,
            'modules' => [
                'admin_privilege' => [
                    $this->testModule->route_path => ['8' => (string)$this->testModule->id, '4' => (string)$this->testModule->id]
                ]
            ]
            // NO '__node__' key (clear mapping)
        ]);

        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $group->id);

        $rolesProperty = $reflection->getProperty('roles');
        $rolesProperty->setAccessible(true);
        $roles = $rolesProperty->getValue($controller);

        $afterMethod = $reflection->getMethod('set_data_after_insert');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($controller, $roles);

        // Assert: Module privileges kept
        $modulePrivilege = \Canvastack\Canvastack\Models\Admin\System\Privilege::where('group_id', $group->id)->first();
        $this->assertNotNull($modulePrivilege, 'Module privilege should exist');
        $this->assertEquals('8:4', $modulePrivilege->admin_privilege, 'Admin privilege should be kept');

        // Assert: Mapping privileges deleted (BUGFIX - this was broken before)
        $mappingCount = MappingPage::where('group_id', $group->id)->count();
        $this->assertEquals(0, $mappingCount, 'All mapping privileges should be deleted');
    }

    /**
     * Test Scenario 13: Has mapping only → Clear mapping
     * 
     * @test
     */
    public function test_clear_mapping_when_no_module_privileges()
    {
        // Arrange: Create group with mapping privilege only
        $group = \App\Models\Admin\System\Group::create([
            'group_name' => 'test_mapping_only',
            'group_alias' => 'Test Mapping Only',
            'group_info' => 'Test clearing mapping when no modules',
            'active' => 1
        ]);

        // Insert mapping privilege (NO module privilege) using query builder
        \DB::table('base_page_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'target_table' => 'users',
            'target_field_name' => 'status',
            'target_field_values' => 'active'
        ]);

        // Verify mapping exists
        $this->assertDatabaseHas('base_page_privilege', ['group_id' => $group->id]);

        // Act: Submit form with NO modules and NO __node__ (clear all)
        $request = Request::create("/groups/{$group->id}", 'PUT', [
            'group_name' => 'test_mapping_only',
            'group_alias' => 'Test Mapping Only',
            'group_info' => 'Test clearing mapping when no modules',
            'active' => 1
            // NO 'modules' key
            // NO '__node__' key
        ]);

        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $group->id);

        $rolesProperty = $reflection->getProperty('roles');
        $rolesProperty->setAccessible(true);
        $roles = $rolesProperty->getValue($controller);

        $afterMethod = $reflection->getMethod('set_data_after_insert');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($controller, $roles);

        // Assert: Mapping privileges deleted (BUGFIX - this was broken before)
        $mappingCount = MappingPage::where('group_id', $group->id)->count();
        $this->assertEquals(0, $mappingCount, 'All mapping privileges should be deleted');
    }

    /**
     * Test that normal update still works (regression test)
     * 
     * @test
     */
    public function test_normal_mapping_update_still_works()
    {
        // Arrange: Create group with mapping privilege
        $group = \App\Models\Admin\System\Group::create([
            'group_name' => 'test_normal_update',
            'group_alias' => 'Test Normal Update',
            'group_info' => 'Test normal update still works',
            'active' => 1
        ]);

        // Insert mapping privilege using query builder
        \DB::table('base_page_privilege')->insert([
            'group_id' => $group->id,
            'module_id' => $this->testModule->id,
            'target_table' => 'users',
            'target_field_name' => 'department',
            'target_field_values' => 'sales'
        ]);

        // Act: Submit form with updated mapping data
        $request = Request::create("/groups/{$group->id}", 'PUT', [
            'group_name' => 'test_normal_update',
            'group_alias' => 'Test Normal Update',
            'group_info' => 'Test normal update still works',
            'active' => 1,
            'rolePages' => [
                'module' => [$this->testModule->route_path => $this->testModule->id],
                'field_name' => [$this->testModule->route_path => ['users' => ['status']]],
                'field_value' => [$this->testModule->route_path => ['users' => ['status' => ['active', 'pending']]]]
            ]
        ]);

        $controller = new GroupController();
        $reflection = new \ReflectionClass($controller);
        
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $group->id);

        $rolesProperty = $reflection->getProperty('roles');
        $rolesProperty->setAccessible(true);
        $roles = $rolesProperty->getValue($controller);

        $afterMethod = $reflection->getMethod('set_data_after_insert');
        $afterMethod->setAccessible(true);
        $afterMethod->invoke($controller, $roles);

        // Assert: Mapping updated correctly
        $mapping = MappingPage::where('group_id', $group->id)
            ->where('target_field_name', 'status')
            ->first();
        
        $this->assertNotNull($mapping, 'Mapping should exist');
        $this->assertEquals('active::pending', $mapping->target_field_values, 'Values should be updated');
    }
}

<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Combobox Data Helper Test Suite
 * 
 * Tests the canvastack_combobox_data() helper function with various input types.
 * 
 * @package Tests\Unit\Helpers
 */
class ComboboxDataTest extends TestCase
{
    /**
     * Test with array input
     * 
     * @return void
     */
    public function test_combobox_data_with_array()
    {
        $data = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'User'],
            ['id' => 3, 'name' => 'Guest'],
        ];
        
        $result = canvastack_combobox_data($data, 'id', 'name');
        
        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);
        
        // Assert values
        $this->assertEquals('', $result[0]);
        $this->assertEquals('Admin', $result[1]);
        $this->assertEquals('User', $result[2]);
        $this->assertEquals('Guest', $result[3]);
    }
    
    /**
     * Test with Eloquent Collection input
     * 
     * @return void
     */
    public function test_combobox_data_with_eloquent_collection()
    {
        // Create mock Eloquent Collection
        $data = new EloquentCollection([
            (object) ['class' => 'fa-home', 'label' => 'Home Icon'],
            (object) ['class' => 'fa-user', 'label' => 'User Icon'],
            (object) ['class' => 'fa-cog', 'label' => 'Settings Icon'],
        ]);
        
        $result = canvastack_combobox_data($data, 'class', 'label');
        
        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fa-home', $result);
        $this->assertArrayHasKey('fa-user', $result);
        $this->assertArrayHasKey('fa-cog', $result);
        
        // Assert values
        $this->assertEquals('Home Icon', $result['fa-home']);
        $this->assertEquals('User Icon', $result['fa-user']);
        $this->assertEquals('Settings Icon', $result['fa-cog']);
    }
    
    /**
     * Test with Support Collection input
     * 
     * @return void
     */
    public function test_combobox_data_with_support_collection()
    {
        // Create Support Collection
        $data = new Collection([
            ['id' => 1, 'name' => 'Option 1'],
            ['id' => 2, 'name' => 'Option 2'],
        ]);
        
        $result = canvastack_combobox_data($data, 'id', 'name');
        
        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        
        // Assert values
        $this->assertEquals('Option 1', $result[1]);
        $this->assertEquals('Option 2', $result[2]);
    }
    
    /**
     * Test without null array option
     * 
     * @return void
     */
    public function test_combobox_data_without_null_array()
    {
        $data = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'User'],
        ];
        
        $result = canvastack_combobox_data($data, 'id', 'name', false);
        
        // Assert structure - should only have one empty option
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertEquals('', $result[0]);
        
        // Count should be 3 (1 empty + 2 data)
        $this->assertCount(3, $result);
    }
    
    /**
     * Test with null array option (default)
     * 
     * @return void
     */
    public function test_combobox_data_with_null_array()
    {
        $data = [
            ['id' => 1, 'name' => 'Admin'],
        ];
        
        $result = canvastack_combobox_data($data, 'id', 'name', true);
        
        // Assert structure - should have two empty options at start
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertEquals('', $result[0]);
        
        // The function adds [0 => ''] and then appends another ''
        // So we should have: [0 => '', 1 => '', 1 => 'Admin']
        // But array keys are unique, so the second '' overwrites key 1
        // Actual result: [0 => '', 1 => 'Admin']
        $this->assertCount(2, $result);
        $this->assertEquals('Admin', $result[1]);
    }
    
    /**
     * Test with empty array
     * 
     * @return void
     */
    public function test_combobox_data_with_empty_array()
    {
        $data = [];
        
        $result = canvastack_combobox_data($data, 'id', 'name');
        
        // Assert structure - should only have empty options
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Two empty options
    }
    
    /**
     * Test with invalid input type
     * 
     * @return void
     */
    public function test_combobox_data_with_invalid_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be of type array or Collection');
        
        canvastack_combobox_data('invalid', 'id', 'name');
    }
    
    /**
     * Test with object array (not associative)
     * 
     * @return void
     */
    public function test_combobox_data_with_object_array()
    {
        $data = [
            (object) ['id' => 1, 'name' => 'Admin'],
            (object) ['id' => 2, 'name' => 'User'],
        ];
        
        $result = canvastack_combobox_data($data, 'id', 'name');
        
        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        
        // Assert values
        $this->assertEquals('Admin', $result[1]);
        $this->assertEquals('User', $result[2]);
    }
}

<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;

/**
 * Test relationship handling enhancements in Datatables
 * 
 * Tests:
 * - Join handling with different join types (INNER, LEFT, RIGHT)
 * - Efficient eager loading
 * - Nested relationship support
 * - Relationship validation
 * - Complex relationship scenarios
 * 
 * Validates Requirements:
 * - Requirement 21: Relationships - Join Handling
 * - Property 13: Query Optimization - Eager Loading
 * - Property 41: Relationships - Efficient Loading
 */
class RelationshipHandlingTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test join type parsing from configuration
     * 
     * @test
     */
    public function test_parse_join_configuration_with_default_left_join()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('parseJoinConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke($datatables, 'users.id', 'posts.user_id');
        
        $this->assertEquals('users', $result['table']);
        $this->assertEquals('users.id', $result['foreignKey']);
        $this->assertEquals('posts.user_id', $result['localKey']);
        $this->assertEquals('LEFT', $result['joinType']);
    }
    
    /**
     * Test join type parsing with explicit INNER join
     * 
     * @test
     */
    public function test_parse_join_configuration_with_inner_join()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('parseJoinConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke($datatables, 'users.id:INNER', 'posts.user_id');
        
        $this->assertEquals('users', $result['table']);
        $this->assertEquals('users.id', $result['foreignKey']);
        $this->assertEquals('posts.user_id', $result['localKey']);
        $this->assertEquals('INNER', $result['joinType']);
    }
    
    /**
     * Test join type parsing with RIGHT join
     * 
     * @test
     */
    public function test_parse_join_configuration_with_right_join()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('parseJoinConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke($datatables, 'categories.id:RIGHT', 'posts.category_id');
        
        $this->assertEquals('categories', $result['table']);
        $this->assertEquals('RIGHT', $result['joinType']);
    }
    
    /**
     * Test invalid join type defaults to LEFT
     * 
     * @test
     */
    public function test_parse_join_configuration_with_invalid_join_type_defaults_to_left()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('parseJoinConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke($datatables, 'users.id:INVALID', 'posts.user_id');
        
        $this->assertEquals('LEFT', $result['joinType']);
    }
    
    /**
     * Test relation name extraction from simple configuration
     * 
     * @test
     */
    public function test_extract_relation_names_from_simple_config()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('extractRelationNames');
        $method->setAccessible(true);
        
        $relations = [
            'user' => ['relation_name' => 'user'],
            'category' => ['relation_name' => 'category']
        ];
        
        $result = $method->invoke($datatables, $relations);
        
        $this->assertCount(2, $result);
        $this->assertContains('user', $result);
        $this->assertContains('category', $result);
    }
    
    /**
     * Test relation name extraction with nested relations
     * 
     * @test
     */
    public function test_extract_relation_names_with_nested_relations()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('extractRelationNames');
        $method->setAccessible(true);
        
        $relations = [
            'user.profile' => ['relation_name' => 'user.profile'],
            'user.roles' => ['relation_name' => 'user.roles']
        ];
        
        $result = $method->invoke($datatables, $relations);
        
        $this->assertCount(2, $result);
        $this->assertContains('user.profile', $result);
        $this->assertContains('user.roles', $result);
    }
    
    /**
     * Test nested relation optimization removes redundant parents
     * 
     * @test
     */
    public function test_optimize_nested_relations_removes_redundant_parents()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('optimizeNestedRelations');
        $method->setAccessible(true);
        
        $relationNames = ['user', 'user.profile', 'category'];
        
        $result = $method->invoke($datatables, $relationNames);
        
        // 'user' should be removed because 'user.profile' includes it
        $this->assertCount(2, $result);
        $this->assertContains('user.profile', $result);
        $this->assertContains('category', $result);
        $this->assertNotContains('user', $result);
    }
    
    /**
     * Test nested relation optimization keeps sibling relations
     * 
     * @test
     */
    public function test_optimize_nested_relations_keeps_siblings()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('optimizeNestedRelations');
        $method->setAccessible(true);
        
        $relationNames = ['user.profile', 'user.roles', 'category'];
        
        $result = $method->invoke($datatables, $relationNames);
        
        // All should be kept as they are siblings
        $this->assertCount(3, $result);
        $this->assertContains('user.profile', $result);
        $this->assertContains('user.roles', $result);
        $this->assertContains('category', $result);
    }
    
    /**
     * Test traversing simple nested relation
     * 
     * @test
     */
    public function test_traverse_nested_relation_simple()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('traverseNestedRelation');
        $method->setAccessible(true);
        
        $model = (object) [
            'user' => (object) [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];
        
        $result = $method->invoke($datatables, $model, ['user'], 'name');
        
        $this->assertEquals('John Doe', $result);
    }
    
    /**
     * Test traversing deep nested relation
     * 
     * @test
     */
    public function test_traverse_nested_relation_deep()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('traverseNestedRelation');
        $method->setAccessible(true);
        
        $model = (object) [
            'user' => (object) [
                'profile' => (object) [
                    'avatar' => 'avatar.jpg',
                    'bio' => 'Developer'
                ]
            ]
        ];
        
        $result = $method->invoke($datatables, $model, ['user', 'profile'], 'avatar');
        
        $this->assertEquals('avatar.jpg', $result);
    }
    
    /**
     * Test traversing nested relation with array access
     * 
     * @test
     */
    public function test_traverse_nested_relation_with_arrays()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('traverseNestedRelation');
        $method->setAccessible(true);
        
        $model = [
            'user' => [
                'profile' => [
                    'avatar' => 'avatar.jpg'
                ]
            ]
        ];
        
        $result = $method->invoke($datatables, $model, ['user', 'profile'], 'avatar');
        
        $this->assertEquals('avatar.jpg', $result);
    }
    
    /**
     * Test traversing nested relation returns null for missing path
     * 
     * @test
     */
    public function test_traverse_nested_relation_returns_null_for_missing_path()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('traverseNestedRelation');
        $method->setAccessible(true);
        
        $model = (object) [
            'user' => (object) [
                'name' => 'John Doe'
            ]
        ];
        
        $result = $method->invoke($datatables, $model, ['user', 'profile'], 'avatar');
        
        $this->assertNull($result);
    }
    
    /**
     * Test traversing nested relation returns null for missing field
     * 
     * @test
     */
    public function test_traverse_nested_relation_returns_null_for_missing_field()
    {
        $datatables = new Datatables();
        $reflection = new \ReflectionClass($datatables);
        $method = $reflection->getMethod('traverseNestedRelation');
        $method->setAccessible(true);
        
        $model = (object) [
            'user' => (object) [
                'profile' => (object) [
                    'bio' => 'Developer'
                ]
            ]
        ];
        
        $result = $method->invoke($datatables, $model, ['user', 'profile'], 'avatar');
        
        $this->assertNull($result);
    }
}

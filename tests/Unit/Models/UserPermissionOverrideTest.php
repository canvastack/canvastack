<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\User;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for UserPermissionOverride model.
 */
class UserPermissionOverrideTest extends TestCase
{
    /**
     * Test that user permission override can be created.
     *
     * @return void
     */
    public function test_user_permission_override_can_be_created(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'field_name' => 'status',
            'rule_config' => ['condition' => 'status === "draft"'],
            'allowed' => true,
        ]);

        // Assert
        $this->assertInstanceOf(UserPermissionOverride::class, $override);
        $this->assertEquals($user->id, $override->user_id);
        $this->assertEquals($permission->id, $override->permission_id);
        $this->assertEquals('App\\Models\\Post', $override->model_type);
        $this->assertEquals(1, $override->model_id);
        $this->assertEquals('status', $override->field_name);
        $this->assertEquals(['condition' => 'status === "draft"'], $override->rule_config);
        $this->assertTrue($override->allowed);
    }

    /**
     * Test that table name is correct.
     *
     * @return void
     */
    public function test_table_name_is_correct(): void
    {
        // Arrange
        $override = new UserPermissionOverride();

        // Assert
        $this->assertEquals('user_permission_overrides', $override->getTable());
    }

    /**
     * Test that fillable fields are correct.
     *
     * @return void
     */
    public function test_fillable_fields_are_correct(): void
    {
        // Arrange
        $override = new UserPermissionOverride();
        $expected = [
            'user_id',
            'permission_id',
            'model_type',
            'model_id',
            'field_name',
            'rule_config',
            'allowed',
        ];

        // Assert
        $this->assertEquals($expected, $override->getFillable());
    }

    /**
     * Test that rule_config is cast to array.
     *
     * @return void
     */
    public function test_rule_config_is_cast_to_array(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $ruleConfig = ['condition' => 'status === "draft"'];

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'rule_config' => $ruleConfig,
            'allowed' => true,
        ]);

        // Assert
        $this->assertIsArray($override->rule_config);
        $this->assertEquals($ruleConfig, $override->rule_config);
    }

    /**
     * Test that allowed is cast to boolean.
     *
     * @return void
     */
    public function test_allowed_is_cast_to_boolean(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act - Create with integer 1
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => 1,
        ]);

        // Assert
        $this->assertIsBool($override->allowed);
        $this->assertTrue($override->allowed);

        // Act - Create with integer 0
        $override2 = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => 0,
        ]);

        // Assert
        $this->assertIsBool($override2->allowed);
        $this->assertFalse($override2->allowed);
    }

    /**
     * Test that user relationship works.
     *
     * @return void
     */
    public function test_user_relationship_works(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        // Act
        $relatedUser = $override->user;

        // Assert
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
        $this->assertEquals($user->name, $relatedUser->name);
    }

    /**
     * Test that permission relationship works.
     *
     * @return void
     */
    public function test_permission_relationship_works(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        // Act
        $relatedPermission = $override->permission;

        // Assert
        $this->assertInstanceOf(Permission::class, $relatedPermission);
        $this->assertEquals($permission->id, $relatedPermission->id);
        $this->assertEquals($permission->name, $relatedPermission->name);
    }

    /**
     * Test that scopeForUser filters by user ID.
     *
     * @return void
     */
    public function test_scope_for_user_filters_by_user_id(): void
    {
        // Arrange
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user1->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user2->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        // Act
        $user1Overrides = UserPermissionOverride::forUser($user1->id)->get();
        $user2Overrides = UserPermissionOverride::forUser($user2->id)->get();

        // Assert
        $this->assertCount(1, $user1Overrides);
        $this->assertEquals($user1->id, $user1Overrides->first()->user_id);

        $this->assertCount(1, $user2Overrides);
        $this->assertEquals($user2->id, $user2Overrides->first()->user_id);
    }

    /**
     * Test that scopeForPermission filters by permission ID.
     *
     * @return void
     */
    public function test_scope_for_permission_filters_by_permission_id(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission1 = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $permission2 = Permission::create([
            'name' => 'posts.delete',
            'display_name' => 'Delete Posts',
            'description' => 'Can delete posts',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission1->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission2->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        // Act
        $permission1Overrides = UserPermissionOverride::forPermission($permission1->id)->get();
        $permission2Overrides = UserPermissionOverride::forPermission($permission2->id)->get();

        // Assert
        $this->assertCount(1, $permission1Overrides);
        $this->assertEquals($permission1->id, $permission1Overrides->first()->permission_id);

        $this->assertCount(1, $permission2Overrides);
        $this->assertEquals($permission2->id, $permission2Overrides->first()->permission_id);
    }

    /**
     * Test that scopeForModel filters by model type.
     *
     * @return void
     */
    public function test_scope_for_model_filters_by_model_type(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'edit',
            'display_name' => 'Edit',
            'description' => 'Can edit',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Comment',
            'allowed' => true,
        ]);

        // Act
        $postOverrides = UserPermissionOverride::forModel('App\\Models\\Post')->get();
        $commentOverrides = UserPermissionOverride::forModel('App\\Models\\Comment')->get();

        // Assert
        $this->assertCount(1, $postOverrides);
        $this->assertEquals('App\\Models\\Post', $postOverrides->first()->model_type);

        $this->assertCount(1, $commentOverrides);
        $this->assertEquals('App\\Models\\Comment', $commentOverrides->first()->model_type);
    }

    /**
     * Test that scopeForModel filters by model type and ID.
     *
     * @return void
     */
    public function test_scope_for_model_filters_by_model_type_and_id(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 2,
            'allowed' => true,
        ]);

        // Act
        $post1Overrides = UserPermissionOverride::forModel('App\\Models\\Post', 1)->get();
        $post2Overrides = UserPermissionOverride::forModel('App\\Models\\Post', 2)->get();

        // Assert
        $this->assertCount(1, $post1Overrides);
        $this->assertEquals(1, $post1Overrides->first()->model_id);

        $this->assertCount(1, $post2Overrides);
        $this->assertEquals(2, $post2Overrides->first()->model_id);
    }

    /**
     * Test that scopeForModel without model ID returns all for that type.
     *
     * @return void
     */
    public function test_scope_for_model_without_id_returns_all_for_type(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 2,
            'allowed' => true,
        ]);

        // Act
        $postOverrides = UserPermissionOverride::forModel('App\\Models\\Post')->get();

        // Assert
        $this->assertCount(2, $postOverrides);
    }

    /**
     * Test that scopes can be chained.
     *
     * @return void
     */
    public function test_scopes_can_be_chained(): void
    {
        // Arrange
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        UserPermissionOverride::create([
            'user_id' => $user1->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user2->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        // Act
        $overrides = UserPermissionOverride::forUser($user1->id)
            ->forPermission($permission->id)
            ->forModel('App\\Models\\Post', 1)
            ->get();

        // Assert
        $this->assertCount(1, $overrides);
        $this->assertEquals($user1->id, $overrides->first()->user_id);
        $this->assertEquals($permission->id, $overrides->first()->permission_id);
        $this->assertEquals('App\\Models\\Post', $overrides->first()->model_type);
        $this->assertEquals(1, $overrides->first()->model_id);
    }

    /**
     * Test that null rule_config is handled correctly.
     *
     * @return void
     */
    public function test_null_rule_config_is_handled_correctly(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'rule_config' => null,
            'allowed' => true,
        ]);

        // Assert
        $this->assertNull($override->rule_config);
    }

    /**
     * Test that model_id can be null.
     *
     * @return void
     */
    public function test_model_id_can_be_null(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => null,
            'allowed' => true,
        ]);

        // Assert
        $this->assertNull($override->model_id);
    }

    /**
     * Test that field_name can be null.
     *
     * @return void
     */
    public function test_field_name_can_be_null(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'field_name' => null,
            'allowed' => true,
        ]);

        // Assert
        $this->assertNull($override->field_name);
    }

    /**
     * Test that timestamps are automatically managed.
     *
     * @return void
     */
    public function test_timestamps_are_automatically_managed(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        // Assert
        $this->assertNotNull($override->created_at);
        $this->assertNotNull($override->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $override->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $override->updated_at);
    }

    /**
     * Test that override can be updated.
     *
     * @return void
     */
    public function test_override_can_be_updated(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        // Act
        $override->update([
            'model_id' => 2,
            'allowed' => false,
        ]);

        // Assert
        $this->assertEquals(2, $override->model_id);
        $this->assertFalse($override->allowed);
    }

    /**
     * Test that override can be deleted.
     *
     * @return void
     */
    public function test_override_can_be_deleted(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'allowed' => true,
        ]);

        $overrideId = $override->id;

        // Act
        $override->delete();

        // Assert
        $this->assertNull(UserPermissionOverride::find($overrideId));
    }

    /**
     * Test that multiple overrides can exist for same user and permission.
     *
     * @return void
     */
    public function test_multiple_overrides_can_exist_for_same_user_and_permission(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act - Create multiple overrides for different models
        $override1 = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'allowed' => true,
        ]);

        $override2 = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 2,
            'allowed' => false,
        ]);

        // Assert
        $overrides = UserPermissionOverride::forUser($user->id)
            ->forPermission($permission->id)
            ->get();

        $this->assertCount(2, $overrides);
        $this->assertTrue($overrides->contains('id', $override1->id));
        $this->assertTrue($overrides->contains('id', $override2->id));
    }

    /**
     * Test that override with field_name can be created.
     *
     * @return void
     */
    public function test_override_with_field_name_can_be_created(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act - Create override for specific field
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'model_id' => 1,
            'field_name' => 'status',
            'allowed' => false,
        ]);

        // Assert
        $this->assertEquals('status', $override->field_name);
        $this->assertFalse($override->allowed);
    }

    /**
     * Test that complex rule_config can be stored and retrieved.
     *
     * @return void
     */
    public function test_complex_rule_config_can_be_stored_and_retrieved(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $complexConfig = [
            'conditions' => [
                'status' => 'draft',
                'user_id' => '{{auth.id}}',
            ],
            'operator' => 'AND',
            'allowed_columns' => ['title', 'content'],
            'denied_columns' => ['status', 'featured'],
        ];

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'rule_config' => $complexConfig,
            'allowed' => true,
        ]);

        // Assert
        $this->assertIsArray($override->rule_config);
        $this->assertEquals($complexConfig, $override->rule_config);
        $this->assertArrayHasKey('conditions', $override->rule_config);
        $this->assertArrayHasKey('operator', $override->rule_config);
        $this->assertArrayHasKey('allowed_columns', $override->rule_config);
        $this->assertArrayHasKey('denied_columns', $override->rule_config);
    }

    /**
     * Test that empty rule_config array is handled correctly.
     *
     * @return void
     */
    public function test_empty_rule_config_array_is_handled_correctly(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        // Act
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Post',
            'rule_config' => [],
            'allowed' => true,
        ]);

        // Assert
        $this->assertIsArray($override->rule_config);
        $this->assertEmpty($override->rule_config);
    }

    /**
     * Test that casts property is defined correctly.
     *
     * @return void
     */
    public function test_casts_property_is_defined_correctly(): void
    {
        // Arrange
        $override = new UserPermissionOverride();
        $casts = $override->getCasts();

        // Assert - Check that our custom casts are present
        $this->assertArrayHasKey('rule_config', $casts);
        $this->assertArrayHasKey('allowed', $casts);
        $this->assertEquals('array', $casts['rule_config']);
        $this->assertEquals('boolean', $casts['allowed']);
    }
}

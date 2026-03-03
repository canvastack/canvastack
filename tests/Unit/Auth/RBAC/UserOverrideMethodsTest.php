<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Factories\PermissionFactory;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for user override methods in PermissionRuleManager.
 */
class UserOverrideMethodsTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected PermissionManager $permissionManager;

    protected RoleManager $roleManager;

    protected TemplateVariableResolver $templateResolver;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager();
        $this->templateResolver = new TemplateVariableResolver();
        $this->manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );
    }

    /**
     * Helper method to create a test user.
     *
     * @return User
     */
    protected function createTestUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password',
        ]);
    }

    /**
     * Test that addUserOverride creates a new override.
     */
    public function test_add_user_override_creates_new_override(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $override = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        // Assert
        $this->assertInstanceOf(UserPermissionOverride::class, $override);
        $this->assertEquals($user->id, $override->user_id);
        $this->assertEquals($permission->id, $override->permission_id);
        $this->assertEquals('App\\Models\\Post', $override->model_type);
        $this->assertNull($override->model_id);
        $this->assertNull($override->field_name);
        $this->assertTrue($override->allowed);
    }

    /**
     * Test that addUserOverride creates override for specific model instance.
     */
    public function test_add_user_override_for_specific_model_instance(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $override = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            123, // Specific post ID
            null,
            false // Deny access
        );

        // Assert
        $this->assertEquals(123, $override->model_id);
        $this->assertFalse($override->allowed);
    }

    /**
     * Test that addUserOverride creates column-level override.
     */
    public function test_add_user_override_for_column(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $override = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'status', // Column name
            false // Deny access to status column
        );

        // Assert
        $this->assertEquals('status', $override->field_name);
        $this->assertFalse($override->allowed);
    }

    /**
     * Test that addUserOverride creates JSON attribute override.
     */
    public function test_add_user_override_for_json_attribute(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $override = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'metadata.seo.title', // JSON attribute path
            true
        );

        // Assert
        $this->assertEquals('metadata.seo.title', $override->field_name);
        $this->assertTrue($override->allowed);
    }

    /**
     * Test that addUserOverride updates existing override.
     */
    public function test_add_user_override_updates_existing_override(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create initial override
        $initialOverride = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        // Act - Update the same override
        $updatedOverride = $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            false // Change to deny
        );

        // Assert
        $this->assertEquals($initialOverride->id, $updatedOverride->id);
        $this->assertFalse($updatedOverride->allowed);

        // Verify only one record exists
        $count = UserPermissionOverride::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Post')
            ->whereNull('model_id')
            ->whereNull('field_name')
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test that addUserOverride throws exception for invalid permission.
     */
    public function test_add_user_override_throws_exception_for_invalid_permission(): void
    {
        // Arrange
        $user = $this->createTestUser();

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission with ID 99999 not found');

        // Act
        $this->manager->addUserOverride(
            $user->id,
            99999, // Non-existent permission ID
            'App\\Models\\Post',
            null,
            null,
            true
        );
    }

    /**
     * Test that removeUserOverride removes row-level override.
     */
    public function test_remove_user_override_removes_row_level_override(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create override
        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        // Act
        $result = $this->manager->removeUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null
        );

        // Assert
        $this->assertTrue($result);

        // Verify override was deleted
        $count = UserPermissionOverride::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Post')
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Test that removeUserOverride removes specific model instance override.
     */
    public function test_remove_user_override_for_specific_model_instance(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create override for specific model instance
        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            123,
            null,
            false
        );

        // Act
        $result = $this->manager->removeUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            123
        );

        // Assert
        $this->assertTrue($result);

        // Verify override was deleted
        $count = UserPermissionOverride::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Post')
            ->where('model_id', 123)
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Test that removeUserOverride removes all overrides for model type.
     */
    public function test_remove_user_override_removes_all_overrides_for_model_type(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create multiple overrides for same model type
        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'status',
            false
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'title',
            true
        );

        // Act
        $result = $this->manager->removeUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null
        );

        // Assert
        $this->assertTrue($result);

        // Verify all were deleted from database
        $count = UserPermissionOverride::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Post')
            ->whereNull('model_id')
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Test that removeUserOverride returns false when no override exists.
     */
    public function test_remove_user_override_returns_false_when_no_override_exists(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $result = $this->manager->removeUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null
        );

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that getUserOverrides returns all overrides for user and permission.
     */
    public function test_get_user_overrides_returns_all_overrides(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create multiple overrides
        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            123,
            null,
            false
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'status',
            false
        );

        // Act
        $overrides = $this->manager->getUserOverrides($user->id, $permission->id);

        // Assert
        $this->assertCount(3, $overrides);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $overrides);

        // Verify all overrides are for the correct user and permission
        foreach ($overrides as $override) {
            $this->assertEquals($user->id, $override->user_id);
            $this->assertEquals($permission->id, $override->permission_id);
        }
    }

    /**
     * Test that getUserOverrides returns empty collection when no overrides exist.
     */
    public function test_get_user_overrides_returns_empty_collection_when_no_overrides(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Act
        $overrides = $this->manager->getUserOverrides($user->id, $permission->id);

        // Assert
        $this->assertCount(0, $overrides);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $overrides);
    }

    /**
     * Test that getUserOverrides returns overrides ordered correctly.
     */
    public function test_get_user_overrides_returns_ordered_results(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create overrides in random order
        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            'title',
            true
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Comment',
            null,
            null,
            true
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission->id,
            'App\\Models\\Post',
            123,
            null,
            false
        );

        // Act
        $overrides = $this->manager->getUserOverrides($user->id, $permission->id);

        // Assert
        $this->assertCount(3, $overrides);

        // Verify ordering: by model_type, then model_id, then field_name
        // Note: SQL NULL ordering may vary by database
        $this->assertEquals('App\\Models\\Comment', $overrides[0]->model_type);

        // The next two should be Post, but order of NULL vs 123 may vary
        $postOverrides = $overrides->filter(fn ($o) => $o->model_type === 'App\\Models\\Post');
        $this->assertCount(2, $postOverrides);

        // One should have model_id = 123, one should have model_id = null
        $withModelId = $postOverrides->firstWhere('model_id', 123);
        $withoutModelId = $postOverrides->firstWhere('model_id', null);

        $this->assertNotNull($withModelId);
        $this->assertNotNull($withoutModelId);
        $this->assertEquals('title', $withoutModelId->field_name);
    }

    /**
     * Test that getUserOverrides only returns overrides for specified user.
     */
    public function test_get_user_overrides_only_returns_for_specified_user(): void
    {
        // Arrange
        $user1 = $this->createTestUser();
        $user2 = $this->createTestUser();
        $permission = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        // Create overrides for both users
        $this->manager->addUserOverride(
            $user1->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        $this->manager->addUserOverride(
            $user2->id,
            $permission->id,
            'App\\Models\\Post',
            null,
            null,
            false
        );

        // Act
        $overrides = $this->manager->getUserOverrides($user1->id, $permission->id);

        // Assert
        $this->assertCount(1, $overrides);
        $this->assertEquals($user1->id, $overrides[0]->user_id);
    }

    /**
     * Test that getUserOverrides only returns overrides for specified permission.
     */
    public function test_get_user_overrides_only_returns_for_specified_permission(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $permission1 = PermissionFactory::new()->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);
        $permission2 = PermissionFactory::new()->create([
            'name' => 'posts.delete',
            'display_name' => 'Delete Posts',
        ]);

        // Create overrides for both permissions
        $this->manager->addUserOverride(
            $user->id,
            $permission1->id,
            'App\\Models\\Post',
            null,
            null,
            true
        );

        $this->manager->addUserOverride(
            $user->id,
            $permission2->id,
            'App\\Models\\Post',
            null,
            null,
            false
        );

        // Act
        $overrides = $this->manager->getUserOverrides($user->id, $permission1->id);

        // Assert
        $this->assertCount(1, $overrides);
        $this->assertEquals($permission1->id, $overrides[0]->permission_id);
    }
}

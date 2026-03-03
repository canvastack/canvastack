<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Factories\PermissionFactory;
use Canvastack\Canvastack\Tests\Fixtures\Factories\PermissionRuleFactory;
use Canvastack\Canvastack\Tests\Fixtures\Factories\UserPermissionOverrideFactory;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for model relationships.
 */
class RelationshipsTest extends TestCase
{
    /**
     * Test that Permission model has permissionRules relationship.
     *
     * @return void
     */
    public function test_permission_has_permission_rules_relationship(): void
    {
        // Arrange
        $permission = PermissionFactory::new()->create();
        $rule = PermissionRuleFactory::new()->create([
            'permission_id' => $permission->id,
        ]);

        // Act
        $permission = $permission->fresh(['permissionRules']);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $permission->permissionRules);
        $this->assertCount(1, $permission->permissionRules);
        $this->assertInstanceOf(PermissionRule::class, $permission->permissionRules->first());
        $this->assertEquals($rule->id, $permission->permissionRules->first()->id);
    }

    /**
     * Test that User model has permissionOverrides relationship.
     *
     * @return void
     */
    public function test_user_has_permission_overrides_relationship(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $permission = PermissionFactory::new()->create();
        $override = UserPermissionOverrideFactory::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        // Act
        $user = $user->fresh(['permissionOverrides']);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->permissionOverrides);
        $this->assertCount(1, $user->permissionOverrides);
        $this->assertInstanceOf(UserPermissionOverride::class, $user->permissionOverrides->first());
        $this->assertEquals($override->id, $user->permissionOverrides->first()->id);
    }

    /**
     * Test that Permission can have multiple permission rules.
     *
     * @return void
     */
    public function test_permission_can_have_multiple_permission_rules(): void
    {
        // Arrange
        $permission = PermissionFactory::new()->create();
        $rule1 = PermissionRuleFactory::new()->create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
        ]);
        $rule2 = PermissionRuleFactory::new()->create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
        ]);
        $rule3 = PermissionRuleFactory::new()->create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
        ]);

        // Act
        $permission = $permission->fresh(['permissionRules']);

        // Assert
        $this->assertCount(3, $permission->permissionRules);
    }

    /**
     * Test that permissionRules relationship returns empty collection when no rules exist.
     *
     * @return void
     */
    public function test_permission_rules_returns_empty_collection_when_no_rules(): void
    {
        // Arrange
        $permission = PermissionFactory::new()->create();

        // Act
        $permission = $permission->fresh(['permissionRules']);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $permission->permissionRules);
        $this->assertCount(0, $permission->permissionRules);
    }

    /**
     * Test that permissionOverrides relationship returns empty collection when no overrides exist.
     *
     * @return void
     */
    public function test_permission_overrides_returns_empty_collection_when_no_overrides(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
        ]);

        // Act
        $user = $user->fresh(['permissionOverrides']);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->permissionOverrides);
        $this->assertCount(0, $user->permissionOverrides);
    }
}

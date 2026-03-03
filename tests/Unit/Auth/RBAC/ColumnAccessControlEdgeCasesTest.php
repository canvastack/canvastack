<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Comprehensive edge case tests for column access control logic.
 * 
 * Tests whitelist/blacklist evaluation, mixed rules, and edge cases.
 */
class ColumnAccessControlEdgeCasesTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(PermissionRuleManager::class);

        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.column_level.enabled' => true,
            'canvastack-rbac.fine_grained.cache.enabled' => true,
        ]);

        Cache::flush();
    }

    /**
     * Test whitelist mode with single column.
     */
    public function test_whitelist_mode_single_column(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'], // Only title allowed
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
    }

    /**
     * Test whitelist mode with multiple columns.
     */
    public function test_whitelist_mode_multiple_columns(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content', 'excerpt'], // Multiple allowed
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Allowed columns
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'excerpt'));

        // Act & Assert - Denied columns
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'featured'));
    }

    /**
     * Test blacklist mode with single column.
     */
    public function test_blacklist_mode_single_column(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [], // No whitelist (blacklist mode)
            ['status'] // Only status denied
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Allowed columns
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'excerpt'));

        // Act & Assert - Denied column
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
    }

    /**
     * Test blacklist mode with multiple columns.
     */
    public function test_blacklist_mode_multiple_columns(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [], // No whitelist (blacklist mode)
            ['status', 'featured', 'promoted'] // Multiple denied
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Allowed columns
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));

        // Act & Assert - Denied columns
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'featured'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'promoted'));
    }

    /**
     * Test mixed mode (both whitelist and blacklist) - whitelist should take precedence.
     */
    public function test_mixed_mode_whitelist_takes_precedence(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // This is an edge case - both whitelist and blacklist defined
        // Expected behavior: whitelist takes precedence
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'], // Whitelist
            ['status'] // Blacklist (should be ignored)
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Whitelist columns allowed
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));

        // Act & Assert - Non-whitelist columns denied (even if not in blacklist)
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'excerpt'));
        
        // Act & Assert - Blacklist column denied (not in whitelist)
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
    }

    /**
     * Test empty whitelist and empty blacklist throws exception.
     */
    public function test_empty_whitelist_and_empty_blacklist_throws_exception(): void
    {
        // Arrange
        $permission = Permission::factory()->create(['name' => 'posts.edit']);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must specify either allowed or denied columns');

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [], // Empty whitelist
            [] // Empty blacklist
        );
    }

    /**
     * Test multiple rules for same permission - should merge correctly.
     */
    public function test_multiple_rules_merge_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Add first rule - whitelist mode
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'],
            []
        );

        // Add second rule - whitelist mode (should merge)
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['excerpt', 'tags'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - All whitelisted columns from both rules should be allowed
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'excerpt'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'tags'));

        // Act & Assert - Non-whitelisted columns should be denied
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
    }

    /**
     * Test multiple rules with different modes - whitelist should take precedence.
     */
    public function test_multiple_rules_different_modes_whitelist_precedence(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Add first rule - whitelist mode
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'],
            []
        );

        // Add second rule - blacklist mode
        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [],
            ['status', 'featured']
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Whitelist should take precedence
        // Only whitelisted columns should be allowed
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));

        // Act & Assert - Non-whitelisted columns should be denied
        // (even if not in blacklist)
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'excerpt'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'featured'));
    }

    /**
     * Test case sensitivity in column names.
     */
    public function test_column_names_are_case_sensitive(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'], // Lowercase
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'Title')); // Different case
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'TITLE')); // Different case
    }

    /**
     * Test special characters in column names.
     */
    public function test_special_characters_in_column_names(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['user_id', 'created_at', 'meta_data'], // Columns with underscores
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'user_id'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'created_at'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'meta_data'));
        $this->assertFalse($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'userid')); // No underscore
    }

    /**
     * Test getAccessibleColumns returns correct format for whitelist.
     */
    public function test_get_accessible_columns_whitelist_format(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content', 'excerpt'],
            []
        );

        // Act
        $accessibleColumns = $this->manager->getAccessibleColumns($user->id, 'posts.edit', \stdClass::class);

        // Assert
        $this->assertIsArray($accessibleColumns);
        $this->assertCount(3, $accessibleColumns);
        $this->assertContains('title', $accessibleColumns);
        $this->assertContains('content', $accessibleColumns);
        $this->assertContains('excerpt', $accessibleColumns);
        
        // Should not contain negation prefix
        foreach ($accessibleColumns as $column) {
            $this->assertStringStartsNotWith('!', $column);
        }
    }

    /**
     * Test getAccessibleColumns returns correct format for blacklist.
     */
    public function test_get_accessible_columns_blacklist_format(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [],
            ['status', 'featured']
        );

        // Act
        $accessibleColumns = $this->manager->getAccessibleColumns($user->id, 'posts.edit', \stdClass::class);

        // Assert
        $this->assertIsArray($accessibleColumns);
        $this->assertCount(2, $accessibleColumns);
        
        // Should contain negation prefix
        $this->assertContains('!status', $accessibleColumns);
        $this->assertContains('!featured', $accessibleColumns);
    }

    /**
     * Test evaluateColumnAccess with empty array (no rules).
     */
    public function test_evaluate_column_access_empty_array_allows_all(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // No rules added

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Should allow all columns when no rules exist
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status'));
    }

    /**
     * Test caching works correctly for whitelist mode.
     */
    public function test_caching_works_for_whitelist_mode(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act - First call (cache miss)
        $result1 = $this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title');

        // Act - Second call (cache hit)
        $result2 = $this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'title');

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test caching works correctly for blacklist mode.
     */
    public function test_caching_works_for_blacklist_mode(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $this->manager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [],
            ['status']
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act - First call (cache miss)
        $result1 = $this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status');

        // Act - Second call (cache hit)
        $result2 = $this->manager->canAccessColumn($user->id, 'posts.edit', $model, 'status');

        // Assert
        $this->assertFalse($result1);
        $this->assertFalse($result2);
        $this->assertEquals($result1, $result2);
    }
}

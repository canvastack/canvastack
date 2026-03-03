<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test subscription-based feature access using user permission overrides.
 *
 * This test suite verifies that user overrides work correctly for implementing
 * subscription-based feature access (e.g., free vs pro vs enterprise plans).
 */
class SubscriptionBasedFeatureAccessTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(PermissionRuleManager::class);
        
        // Ensure fine-grained permissions are enabled
        config(['canvastack-rbac.fine_grained.enabled' => true]);
        config(['canvastack-rbac.fine_grained.json_attribute.enabled' => true]);
        
        // Disable caching for these tests to avoid cache issues
        config(['canvastack-rbac.cache.enabled' => false]);
    }

    /**
     * Test that user override allows access to restricted JSON attribute.
     *
     * @return void
     */
    public function test_user_override_allows_access_to_restricted_json_attribute(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Pro User',
            'email' => 'pro@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'metadata' => [
                'analytics' => ['views' => 100, 'clicks' => 50],
                'api_enabled' => true,
            ],
        ]);

        // Add JSON attribute rule that denies access to analytics
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => ['basic.*'],
                'denied_paths' => ['analytics.*', 'api_enabled'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Without override, access should be denied
        $this->assertFalse(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'analytics'
            ),
            'Access should be denied without override'
        );

        // Act - Add user override to allow access
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null, // General override for all posts
            'field_name' => 'metadata.analytics',
            'allowed' => true,
        ]);
        
        // Clear cache after adding override
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Assert - Access should now be allowed
        $this->assertTrue(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'analytics'
            ),
            'Access should be allowed with override'
        );
    }

    /**
     * Test that user override denies access to normally allowed JSON attribute.
     *
     * @return void
     */
    public function test_user_override_denies_access_to_allowed_json_attribute(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Free User',
            'email' => 'free@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'metadata' => [
                'basic' => ['title' => 'Test', 'description' => 'Test'],
            ],
        ]);

        // Add JSON attribute rule that allows access to basic fields
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => ['basic.*'],
                'denied_paths' => [],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Without override, access should be allowed
        $this->assertTrue(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'basic'
            ),
            'Access should be allowed without override'
        );

        // Act - Add user override to deny access
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null, // General override
            'field_name' => 'metadata.basic',
            'allowed' => false,
        ]);
        
        // Clear cache after adding override
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Assert - Access should now be denied
        $this->assertFalse(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'basic'
            ),
            'Access should be denied with override'
        );
    }

    /**
     * Test that specific model instance override takes precedence over general override.
     *
     * @return void
     */
    public function test_specific_model_override_takes_precedence(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post1 = Post::create([
            'title' => 'Post 1',
            'content' => 'Content 1',
            'user_id' => $user->id,
            'metadata' => ['analytics' => ['views' => 100]],
        ]);

        $post2 = Post::create([
            'title' => 'Post 2',
            'content' => 'Content 2',
            'user_id' => $user->id,
            'metadata' => ['analytics' => ['views' => 200]],
        ]);

        // Add JSON attribute rule that denies access
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => [],
                'denied_paths' => ['analytics.*'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Add general override (allow for all posts)
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null, // General override
            'field_name' => 'metadata.analytics',
            'allowed' => true,
        ]);

        // Add specific override for post1 (deny)
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => $post1->id, // Specific override
            'field_name' => 'metadata.analytics',
            'allowed' => false,
        ]);
        
        // Clear cache after adding overrides
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Assert - Post1 should be denied (specific override)
        $this->assertFalse(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post1,
                'metadata',
                'analytics'
            ),
            'Post1 should be denied by specific override'
        );
        
        // Clear cache before checking Post2
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Assert - Post2 should be allowed (general override)
        $this->assertTrue(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post2,
                'metadata',
                'analytics'
            ),
            'Post2 should be allowed by general override'
        );
    }

    /**
     * Test that user override works for column-level permissions.
     *
     * @return void
     */
    public function test_user_override_works_for_column_level_permissions(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Pro User',
            'email' => 'pro@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        // Add column rule that denies access to status
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => Post::class,
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        // Without override, access should be denied
        $this->assertFalse(
            $this->manager->canAccessColumn(
                $user->id,
                'posts.edit',
                $post,
                'status'
            ),
            'Access to status should be denied without override'
        );

        // Act - Add user override to allow access
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null,
            'field_name' => 'status',
            'allowed' => true,
        ]);
        
        // Clear cache after adding override
        $this->manager->clearRuleCache($user->id, 'posts.edit');

        // Assert - Access should now be allowed
        $this->assertTrue(
            $this->manager->canAccessColumn(
                $user->id,
                'posts.edit',
                $post,
                'status'
            ),
            'Access to status should be allowed with override'
        );
    }

    /**
     * Test that user override works for row-level permissions.
     *
     * @return void
     */
    public function test_user_override_works_for_row_level_permissions(): void
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
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'User 2 Post',
            'content' => 'Content',
            'user_id' => $user2->id,
        ]);

        // Add row rule: Users can only view their own posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => Post::class,
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Without override, user1 cannot access user2's post
        $this->assertFalse(
            $this->manager->canAccessRow(
                $user1->id,
                'posts.view',
                $post
            ),
            'User1 should not access user2 post without override'
        );

        // Act - Add user override to allow user1 to access this specific post
        UserPermissionOverride::create([
            'user_id' => $user1->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => $post->id,
            'field_name' => null, // Row-level override
            'allowed' => true,
        ]);
        
        // Clear cache after adding override
        $this->manager->clearRuleCache($user1->id, 'posts.view');

        // Assert - User1 should now be able to access the post
        $this->assertTrue(
            $this->manager->canAccessRow(
                $user1->id,
                'posts.view',
                $post
            ),
            'User1 should access user2 post with override'
        );
    }

    /**
     * Test that multiple overrides for different fields work correctly.
     *
     * @return void
     */
    public function test_multiple_overrides_for_different_fields(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Pro User',
            'email' => 'pro@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'metadata' => [
                'analytics' => ['views' => 100],
                'api_enabled' => true,
                'custom_domain' => 'example.com',
            ],
        ]);

        // Add JSON attribute rule that denies all advanced features
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => ['basic.*'],
                'denied_paths' => ['analytics.*', 'api_enabled', 'custom_domain'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Add overrides for analytics and api_enabled (but not custom_domain)
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null,
            'field_name' => 'metadata.analytics',
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null,
            'field_name' => 'metadata.api_enabled',
            'allowed' => true,
        ]);
        
        // Clear cache after adding overrides
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Assert - Analytics should be allowed
        $this->assertTrue(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'analytics'
            ),
            'Analytics should be allowed with override'
        );

        // Assert - API enabled should be allowed
        $this->assertTrue(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'api_enabled'
            ),
            'API enabled should be allowed with override'
        );

        // Assert - Custom domain should still be denied (no override)
        $this->assertFalse(
            $this->manager->canAccessJsonAttribute(
                $user->id,
                'posts.manage',
                $post,
                'metadata',
                'custom_domain'
            ),
            'Custom domain should be denied without override'
        );
    }

    /**
     * Test that override is cached correctly.
     *
     * @return void
     */
    public function test_override_is_cached_correctly(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'metadata' => ['analytics' => ['views' => 100]],
        ]);

        // Add JSON attribute rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => [],
                'denied_paths' => ['analytics.*'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Add override
        UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null,
            'field_name' => 'metadata.analytics',
            'allowed' => true,
        ]);

        // First call - should cache the result
        $result1 = $this->manager->canAccessJsonAttribute(
            $user->id,
            'posts.manage',
            $post,
            'metadata',
            'analytics'
        );

        // Second call - should use cached result
        $result2 = $this->manager->canAccessJsonAttribute(
            $user->id,
            'posts.manage',
            $post,
            'metadata',
            'analytics'
        );

        // Assert - Both results should be true
        $this->assertTrue($result1, 'First call should return true');
        $this->assertTrue($result2, 'Second call should return true (cached)');
    }

    /**
     * Test that updating override clears cache.
     *
     * @return void
     */
    public function test_updating_override_clears_cache(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => 'posts.manage',
            'display_name' => 'Manage Posts',
            'module' => 'test',
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id,
            'metadata' => ['analytics' => ['views' => 100]],
        ]);

        // Add JSON attribute rule
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => Post::class,
                'json_column' => 'metadata',
                'allowed_paths' => [],
                'denied_paths' => ['analytics.*'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        // Add override (allow)
        $override = UserPermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'model_type' => Post::class,
            'model_id' => null,
            'field_name' => 'metadata.analytics',
            'allowed' => true,
        ]);

        // First call - should return true
        $result1 = $this->manager->canAccessJsonAttribute(
            $user->id,
            'posts.manage',
            $post,
            'metadata',
            'analytics'
        );

        $this->assertTrue($result1, 'Should be allowed initially');

        // Update override (deny)
        $override->update(['allowed' => false]);

        // Clear cache manually (simulating cache invalidation)
        $this->manager->clearRuleCache($user->id, 'posts.manage');

        // Second call - should return false
        $result2 = $this->manager->canAccessJsonAttribute(
            $user->id,
            'posts.manage',
            $post,
            'metadata',
            'analytics'
        );

        $this->assertFalse($result2, 'Should be denied after update');
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Performance tests for Gate integration with Fine-Grained Permissions.
 *
 * Requirements tested:
 * - Gate methods: < 100ms response time
 * - Cache effectiveness
 * - Audit logging performance
 */
class GatePerformanceTest extends TestCase
{
    private Gate $gate;

    private PermissionRuleManager $ruleManager;

    private User $user;

    private Permission $permission;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = app(Gate::class);
        $this->ruleManager = app(PermissionRuleManager::class);

        // Create test data
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password', // Plain text for performance testing
        ]);

        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);

        $this->post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Assign permission to user
        $this->user->permissions()->attach($this->permission->id);

        Cache::flush();
    }

    /**
     * Test canAccessRow performance.
     * Requirement: < 100ms.
     */
    public function test_can_access_row_performance(): void
    {
        // Create row-level rule
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        // Warm up
        $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            100,
            $avgTime,
            "Gate::canAccessRow took {$avgTime}ms (requirement: < 100ms)"
        );

        echo "\n✓ Gate::canAccessRow: {$avgTime}ms (requirement: < 100ms)\n";
    }

    /**
     * Test canAccessColumn performance.
     * Requirement: < 100ms.
     */
    public function test_can_access_column_performance(): void
    {
        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            ['title', 'content'],
            ['status']
        );

        // Warm up
        $this->gate->canAccessColumn($this->user, 'posts.edit', $this->post, 'title');

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessColumn($this->user, 'posts.edit', $this->post, 'title');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            100,
            $avgTime,
            "Gate::canAccessColumn took {$avgTime}ms (requirement: < 100ms)"
        );

        echo "✓ Gate::canAccessColumn: {$avgTime}ms (requirement: < 100ms)\n";
    }

    /**
     * Test canAccessJsonAttribute performance.
     * Requirement: < 100ms.
     */
    public function test_can_access_json_attribute_performance(): void
    {
        // Create JSON attribute rule
        $this->ruleManager->addJsonAttributeRule(
            $this->permission->id,
            Post::class,
            'metadata',
            ['seo.*'],
            ['featured']
        );

        // Warm up
        $this->gate->canAccessJsonAttribute(
            $this->user,
            'posts.edit',
            $this->post,
            'metadata',
            'seo.title'
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessJsonAttribute(
                $this->user,
                'posts.edit',
                $this->post,
                'metadata',
                'seo.title'
            );
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            100,
            $avgTime,
            "Gate::canAccessJsonAttribute took {$avgTime}ms (requirement: < 100ms)"
        );

        echo "✓ Gate::canAccessJsonAttribute: {$avgTime}ms (requirement: < 100ms)\n";
    }

    /**
     * Test performance with audit logging enabled.
     */
    public function test_performance_with_audit_logging(): void
    {
        // Create row-level rule
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        // Enable audit logging
        config(['canvastack-rbac.fine_grained.audit_logging' => true]);

        // Warm up
        $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        // With audit logging, should still be under 100ms
        $this->assertLessThan(
            100,
            $avgTime,
            "Gate with audit logging took {$avgTime}ms (requirement: < 100ms)"
        );

        echo "✓ Gate with audit logging: {$avgTime}ms (requirement: < 100ms)\n";
    }

    /**
     * Test super admin bypass performance.
     */
    public function test_super_admin_bypass_performance(): void
    {
        // Make user super admin
        $this->user->is_super_admin = true;
        $this->user->save();

        // Create complex rules (should be bypassed)
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        // Super admin bypass should be very fast (< 10ms)
        $this->assertLessThan(
            10,
            $avgTime,
            "Super admin bypass took {$avgTime}ms (requirement: < 10ms)"
        );

        echo "✓ Super admin bypass: {$avgTime}ms (requirement: < 10ms)\n";
    }

    /**
     * Test performance with permission denial.
     */
    public function test_permission_denial_performance(): void
    {
        // Remove permission from user
        $this->user->permissions()->detach($this->permission->id);

        // Measure performance
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        // Early denial should be very fast (< 10ms)
        $this->assertLessThan(
            10,
            $avgTime,
            "Permission denial took {$avgTime}ms (requirement: < 10ms)"
        );

        echo "✓ Permission denial: {$avgTime}ms (requirement: < 10ms)\n";
    }

    /**
     * Test performance with multiple permission checks.
     */
    public function test_multiple_permission_checks_performance(): void
    {
        // Create multiple rules
        $this->ruleManager->addRowRule(
            $this->permission->id,
            Post::class,
            ['user_id' => '{{auth.id}}']
        );

        $this->ruleManager->addColumnRule(
            $this->permission->id,
            Post::class,
            ['title', 'content'],
            ['status']
        );

        // Warm up
        $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
        $this->gate->canAccessColumn($this->user, 'posts.edit', $this->post, 'title');

        // Measure performance for multiple checks
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->gate->canAccessRow($this->user, 'posts.edit', $this->post);
            $this->gate->canAccessColumn($this->user, 'posts.edit', $this->post, 'title');
            $this->gate->canAccessColumn($this->user, 'posts.edit', $this->post, 'content');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / ($iterations * 3); // 3 checks per iteration

        $this->assertLessThan(
            100,
            $avgTime,
            "Multiple checks took {$avgTime}ms per check (requirement: < 100ms)"
        );

        echo "✓ Multiple permission checks: {$avgTime}ms per check (requirement: < 100ms)\n";
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Test for PermissionRuleManager tagged cache functionality.
 */
class PermissionRuleManagerTaggedCacheTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Get container instance
        $app = \Illuminate\Container\Container::getInstance();

        // Register dependencies first
        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\RoleManager::class, function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\RoleManager();
        });

        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\PermissionManager::class, function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\PermissionManager();
        });

        $app->singleton(\Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver::class, function ($app) {
            return new \Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver();
        });

        // Register PermissionRuleManager
        $app->singleton(PermissionRuleManager::class, function ($app) {
            return new PermissionRuleManager(
                $app->make(\Canvastack\Canvastack\Auth\RBAC\RoleManager::class),
                $app->make(\Canvastack\Canvastack\Auth\RBAC\PermissionManager::class),
                $app->make(\Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver::class)
            );
        });

        $this->manager = $app->make(PermissionRuleManager::class);
    }

    /**
     * Test that cache key generation for row rules is consistent.
     */
    public function test_generate_row_cache_key_is_consistent(): void
    {
        $userId = 1;
        $permission = 'posts.edit';
        $modelClass = 'App\\Models\\Post';
        $modelId = 123;

        $key1 = $this->invokeMethod($this->manager, 'generateRowCacheKey', [
            $userId,
            $permission,
            $modelClass,
            $modelId,
        ]);

        $key2 = $this->invokeMethod($this->manager, 'generateRowCacheKey', [
            $userId,
            $permission,
            $modelClass,
            $modelId,
        ]);

        $this->assertEquals($key1, $key2, 'Cache keys should be consistent for same parameters');
        $this->assertStringContainsString('row', $key1);
        $this->assertStringContainsString((string) $userId, $key1);
        $this->assertStringContainsString($permission, $key1);
    }

    /**
     * Test that cache key generation for column rules is consistent.
     */
    public function test_generate_column_cache_key_is_consistent(): void
    {
        $userId = 1;
        $permission = 'posts.edit';
        $modelClass = 'App\\Models\\Post';
        $column = 'status';

        $key1 = $this->invokeMethod($this->manager, 'generateColumnCacheKey', [
            $userId,
            $permission,
            $modelClass,
            $column,
        ]);

        $key2 = $this->invokeMethod($this->manager, 'generateColumnCacheKey', [
            $userId,
            $permission,
            $modelClass,
            $column,
        ]);

        $this->assertEquals($key1, $key2);
        $this->assertStringContainsString('column', $key1);
        $this->assertStringContainsString($column, $key1);
    }

    /**
     * Test that cache tags are generated correctly.
     */
    public function test_generate_cache_tags_includes_all_required_tags(): void
    {
        $userId = 1;
        $permission = 'posts.edit';
        $modelClass = 'App\\Models\\Post';
        $ruleType = 'row';

        $tags = $this->invokeMethod($this->manager, 'generateCacheTags', [
            $userId,
            $permission,
            $modelClass,
            $ruleType,
        ]);

        $this->assertIsArray($tags);
        $this->assertContains('rbac:rules', $tags);
        $this->assertContains("rbac:user:{$userId}", $tags);
        $this->assertContains("rbac:permission:{$permission}", $tags);
        $this->assertContains("rbac:type:{$ruleType}", $tags);

        // Model tag should use class_basename (optimized)
        $modelShort = class_basename($modelClass);
        $modelTag = "rbac:model:{$modelShort}";
        $this->assertContains($modelTag, $tags);
    }

    /**
     * Test that cache can be cleared by model class.
     */
    public function test_clear_cache_by_model_clears_model_specific_cache(): void
    {
        $modelClass = 'App\\Models\\Post';
        $modelShort = class_basename($modelClass);

        // Mock cache facade
        Cache::shouldReceive('tags')
            ->once()
            ->with(["rbac:model:{$modelShort}"])
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->manager->clearCacheByModel($modelClass);

        $this->assertTrue($result);
    }

    /**
     * Test that cache can be cleared by rule type.
     */
    public function test_clear_cache_by_type_clears_type_specific_cache(): void
    {
        $ruleType = 'row';

        // Mock cache facade
        Cache::shouldReceive('tags')
            ->once()
            ->with(["rbac:type:{$ruleType}"])
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->manager->clearCacheByType($ruleType);

        $this->assertTrue($result);
    }

    /**
     * Test that all cache can be cleared.
     */
    public function test_clear_all_cache_clears_all_rule_cache(): void
    {
        // Mock cache facade
        Cache::shouldReceive('tags')
            ->once()
            ->with(['rbac:rules'])
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->manager->clearAllCache();

        $this->assertTrue($result);
    }

    /**
     * Test that cache clearing returns true when cache is disabled.
     */
    public function test_clear_cache_returns_true_when_cache_disabled(): void
    {
        // Disable cache
        config(['canvastack-rbac.fine_grained.cache.enabled' => false]);

        $result = $this->manager->clearCacheByModel('App\\Models\\Post');
        $this->assertTrue($result);

        $result = $this->manager->clearCacheByType('row');
        $this->assertTrue($result);

        $result = $this->manager->clearAllCache();
        $this->assertTrue($result);
    }

    /**
     * Test that different model classes generate different cache keys.
     */
    public function test_different_model_classes_generate_different_cache_keys(): void
    {
        $userId = 1;
        $permission = 'edit';

        $key1 = $this->invokeMethod($this->manager, 'generateRowCacheKey', [
            $userId,
            $permission,
            'App\\Models\\Post',
            123,
        ]);

        $key2 = $this->invokeMethod($this->manager, 'generateRowCacheKey', [
            $userId,
            $permission,
            'App\\Models\\User',
            123,
        ]);

        $this->assertNotEquals($key1, $key2);
    }

    /**
     * Helper method to invoke protected/private methods.
     *
     * @param object $object Object instance
     * @param string $methodName Method name
     * @param array<mixed> $parameters Method parameters
     * @return mixed
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

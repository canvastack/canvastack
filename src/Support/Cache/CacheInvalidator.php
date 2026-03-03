<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache;

/**
 * Cache Invalidator
 * 
 * Handles cache invalidation strategies and event-driven cache clearing.
 */
class CacheInvalidator
{
    /**
     * Cache manager instance.
     */
    protected CacheManager $cache;

    /**
     * Create a new cache invalidator instance.
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Invalidate form cache.
     */
    public function invalidateForms(?string $formName = null): bool
    {
        if ($formName !== null) {
            return $this->cache->flush([CacheTags::form($formName)]);
        }

        return $this->cache->flush(CacheTags::forms());
    }

    /**
     * Invalidate table cache.
     */
    public function invalidateTables(?string $tableName = null): bool
    {
        if ($tableName !== null) {
            return $this->cache->flush([CacheTags::table($tableName)]);
        }

        return $this->cache->flush(CacheTags::tables());
    }

    /**
     * Invalidate chart cache.
     */
    public function invalidateCharts(?string $chartName = null): bool
    {
        if ($chartName !== null) {
            return $this->cache->flush([CacheTags::chart($chartName)]);
        }

        return $this->cache->flush(CacheTags::charts());
    }

    /**
     * Invalidate RBAC cache.
     */
    public function invalidateRbac(): bool
    {
        return $this->cache->flush(CacheTags::rbac());
    }

    /**
     * Invalidate permissions cache.
     */
    public function invalidatePermissions(): bool
    {
        return $this->cache->flush([CacheTags::PERMISSIONS]);
    }

    /**
     * Invalidate roles cache.
     */
    public function invalidateRoles(): bool
    {
        return $this->cache->flush([CacheTags::ROLES]);
    }

    /**
     * Invalidate policies cache.
     */
    public function invalidatePolicies(): bool
    {
        return $this->cache->flush([CacheTags::POLICIES]);
    }

    /**
     * Invalidate view cache.
     */
    public function invalidateViews(): bool
    {
        return $this->cache->flush(CacheTags::views());
    }

    /**
     * Invalidate theme cache.
     */
    public function invalidateThemes(?string $themeName = null): bool
    {
        if ($themeName !== null) {
            return $this->cache->flush([CacheTags::theme($themeName)]);
        }

        return $this->cache->flush(CacheTags::themes());
    }

    /**
     * Invalidate locale cache.
     */
    public function invalidateLocales(?string $localeCode = null): bool
    {
        if ($localeCode !== null) {
            return $this->cache->flush([CacheTags::locale($localeCode)]);
        }

        return $this->cache->flush(CacheTags::locales());
    }

    /**
     * Invalidate user cache.
     */
    public function invalidateUser(int|string $userId): bool
    {
        return $this->cache->flush([CacheTags::user($userId)]);
    }

    /**
     * Invalidate all user caches.
     */
    public function invalidateAllUsers(): bool
    {
        return $this->cache->flush(CacheTags::users());
    }

    /**
     * Invalidate user preferences cache.
     */
    public function invalidateUserPreferences(int|string $userId): bool
    {
        return $this->cache->flush([
            CacheTags::USER_PREFERENCES,
            CacheTags::user($userId),
        ]);
    }

    /**
     * Invalidate all cache.
     */
    public function invalidateAll(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Invalidate cache by tags.
     */
    public function invalidateByTags(array $tags): bool
    {
        return $this->cache->flush($tags);
    }

    /**
     * Invalidate cache on model create.
     */
    public function onModelCreated(string $modelClass, mixed $model): void
    {
        $this->invalidateModelCache($modelClass);
    }

    /**
     * Invalidate cache on model update.
     */
    public function onModelUpdated(string $modelClass, mixed $model): void
    {
        $this->invalidateModelCache($modelClass);
    }

    /**
     * Invalidate cache on model delete.
     */
    public function onModelDeleted(string $modelClass, mixed $model): void
    {
        $this->invalidateModelCache($modelClass);
    }

    /**
     * Invalidate cache based on model class.
     */
    protected function invalidateModelCache(string $modelClass): void
    {
        // Extract model name from class
        $modelName = strtolower(class_basename($modelClass));

        // Invalidate related caches based on model
        match ($modelName) {
            'user' => $this->invalidateAllUsers(),
            'role' => $this->invalidateRoles(),
            'permission' => $this->invalidatePermissions(),
            'form' => $this->invalidateForms(),
            default => null,
        };
    }

    /**
     * Get cache manager instance.
     */
    public function getCacheManager(): CacheManager
    {
        return $this->cache;
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC\Observers;

use Canvastack\Canvastack\Models\UserPermissionOverride;
use Illuminate\Support\Facades\Log;

/**
 * User Permission Override Observer.
 *
 * Automatically invalidates cache when user permission overrides are created, updated, or deleted.
 */
class UserPermissionOverrideObserver
{
    /**
     * Handle the UserPermissionOverride "created" event.
     *
     * @param UserPermissionOverride $override
     * @return void
     */
    public function created(UserPermissionOverride $override): void
    {
        $this->invalidateCache($override, 'created');
    }

    /**
     * Handle the UserPermissionOverride "updated" event.
     *
     * @param UserPermissionOverride $override
     * @return void
     */
    public function updated(UserPermissionOverride $override): void
    {
        $this->invalidateCache($override, 'updated');
    }

    /**
     * Handle the UserPermissionOverride "deleted" event.
     *
     * @param UserPermissionOverride $override
     * @return void
     */
    public function deleted(UserPermissionOverride $override): void
    {
        $this->invalidateCache($override, 'deleted');
    }

    /**
     * Invalidate cache for the user permission override.
     *
     * @param UserPermissionOverride $override
     * @param string $event
     * @return void
     */
    protected function invalidateCache(UserPermissionOverride $override, string $event): void
    {
        try {
            // Get the rule manager from the container
            if (!app()->bound('canvastack.rbac.rule.manager')) {
                return;
            }

            $ruleManager = app('canvastack.rbac.rule.manager');

            // Clear cache for specific user and permission
            $permission = $override->permission;
            if ($permission) {
                $ruleManager->clearRuleCache($override->user_id, $permission->name);
            }

            // Clear cache by model class
            if ($override->model_type) {
                $ruleManager->clearCacheByModel($override->model_type);
            }

            Log::info('User permission override cache invalidated', [
                'event' => $event,
                'override_id' => $override->id,
                'user_id' => $override->user_id,
                'permission_id' => $override->permission_id,
                'model_type' => $override->model_type,
                'model_id' => $override->model_id,
            ]);
        } catch (\Throwable $e) {
            // Log error but don't throw - cache invalidation should be non-blocking
            Log::error('Failed to invalidate user permission override cache', [
                'event' => $event,
                'override_id' => $override->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

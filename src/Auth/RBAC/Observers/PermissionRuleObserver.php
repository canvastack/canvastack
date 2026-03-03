<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC\Observers;

use Canvastack\Canvastack\Models\PermissionRule;
use Illuminate\Support\Facades\Log;

/**
 * Permission Rule Observer.
 *
 * Automatically invalidates cache when permission rules are created, updated, or deleted.
 */
class PermissionRuleObserver
{
    /**
     * Handle the PermissionRule "created" event.
     *
     * @param PermissionRule $rule
     * @return void
     */
    public function created(PermissionRule $rule): void
    {
        $this->invalidateCache($rule, 'created');
    }

    /**
     * Handle the PermissionRule "updated" event.
     *
     * @param PermissionRule $rule
     * @return void
     */
    public function updated(PermissionRule $rule): void
    {
        $this->invalidateCache($rule, 'updated');
    }

    /**
     * Handle the PermissionRule "deleted" event.
     *
     * @param PermissionRule $rule
     * @return void
     */
    public function deleted(PermissionRule $rule): void
    {
        $this->invalidateCache($rule, 'deleted');
    }

    /**
     * Invalidate cache for the permission rule.
     *
     * @param PermissionRule $rule
     * @param string $event
     * @return void
     */
    protected function invalidateCache(PermissionRule $rule, string $event): void
    {
        try {
            // Get the rule manager from the container
            if (!app()->bound('canvastack.rbac.rule.manager')) {
                return;
            }

            $ruleManager = app('canvastack.rbac.rule.manager');

            // Clear cache by permission ID
            $permission = $rule->permission;
            if ($permission) {
                $ruleManager->clearRuleCache(null, $permission->name);
            }

            // Clear cache by model class
            $modelClass = $rule->rule_config['model'] ?? null;
            if ($modelClass) {
                $ruleManager->clearCacheByModel($modelClass);
            }

            // Clear cache by rule type
            $ruleManager->clearCacheByType($rule->rule_type);

            Log::info('Permission rule cache invalidated', [
                'event' => $event,
                'rule_id' => $rule->id,
                'permission_id' => $rule->permission_id,
                'rule_type' => $rule->rule_type,
                'model_class' => $modelClass,
            ]);
        } catch (\Throwable $e) {
            // Log error but don't throw - cache invalidation should be non-blocking
            Log::error('Failed to invalidate permission rule cache', [
                'event' => $event,
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

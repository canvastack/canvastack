<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC\Traits;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasPermissionScopes.
 *
 * Adds permission-based query scopes to Eloquent models.
 * This trait provides the scopeByPermission() method that filters
 * query results based on row-level permission rules.
 *
 * Usage:
 * ```php
 * class Post extends Model
 * {
 *     use HasPermissionScopes;
 * }
 *
 * // In controller
 * $posts = Post::byPermission($userId, 'posts.view')->get();
 * ```
 */
trait HasPermissionScopes
{
    /**
     * Scope a query by permission rules.
     *
     * Applies row-level permission filtering to the query based on
     * the user's permissions and configured permission rules.
     *
     * @param Builder $query The query builder instance
     * @param int $userId The user ID to check permissions for
     * @param string $permission The permission name (e.g., 'posts.view')
     * @return Builder The modified query builder
     *
     * @example
     * ```php
     * // Get all posts the user can view
     * $posts = Post::byPermission(auth()->id(), 'posts.view')->get();
     *
     * // Chain with other scopes
     * $posts = Post::byPermission(auth()->id(), 'posts.edit')
     *     ->where('status', 'published')
     *     ->latest()
     *     ->get();
     * ```
     */
    public function scopeByPermission(Builder $query, int $userId, string $permission): Builder
    {
        /** @var PermissionRuleManager $ruleManager */
        $ruleManager = app(PermissionRuleManager::class);

        return $ruleManager->scopeByPermission($query, $userId, $permission);
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Auth\RBAC;

use Illuminate\Support\Facades\Blade;

/**
 * RBAC Blade Directives.
 *
 * Registers custom Blade directives for fine-grained permission checks.
 */
class BladeDirectives
{
    /**
     * Register all RBAC Blade directives.
     *
     * @return void
     */
    public static function register(): void
    {
        static::registerCanAccessRow();
        static::registerCanAccessColumn();
        static::registerCanAccessJsonAttribute();
    }

    /**
     * Register @canAccessRow Blade directive.
     *
     * Usage:
     * @canAccessRow('posts.edit', $post)
     *     <button>Edit</button>
     * @endcanAccessRow
     *
     * @return void
     */
    protected static function registerCanAccessRow(): void
    {
        Blade::if('canAccessRow', function (string $permission, object $model) {
            $gate = app(Gate::class);
            $user = auth()->user();

            return $gate->canAccessRow($user, $permission, $model);
        });
    }

    /**
     * Register @canAccessColumn Blade directive.
     *
     * Usage:
     * @canAccessColumn('posts.edit', $post, 'status')
     *     <input type="text" name="status" value="{{ $post->status }}">
     * @endcanAccessColumn
     *
     * @return void
     */
    protected static function registerCanAccessColumn(): void
    {
        Blade::if('canAccessColumn', function (string $permission, object $model, string $column) {
            $gate = app(Gate::class);
            $user = auth()->user();

            return $gate->canAccessColumn($user, $permission, $model, $column);
        });
    }

    /**
     * Register @canAccessJsonAttribute Blade directive.
     *
     * Usage:
     * @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
     *     <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
     * @endcanAccessJsonAttribute
     *
     * @return void
     */
    protected static function registerCanAccessJsonAttribute(): void
    {
        Blade::if('canAccessJsonAttribute', function (string $permission, object $model, string $jsonColumn, string $path) {
            $gate = app(Gate::class);
            $user = auth()->user();

            return $gate->canAccessJsonAttribute($user, $permission, $model, $jsonColumn, $path);
        });
    }
}

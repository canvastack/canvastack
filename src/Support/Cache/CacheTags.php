<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache;

/**
 * Cache Tags Helper
 * 
 * Provides predefined cache tags for consistent cache management.
 */
class CacheTags
{
    /**
     * Form-related cache tags.
     */
    public const FORMS = 'forms';
    public const FORM_DEFINITIONS = 'form_definitions';
    public const FORM_VALIDATION = 'form_validation';

    /**
     * Table-related cache tags.
     */
    public const TABLES = 'tables';
    public const TABLE_QUERIES = 'table_queries';
    public const TABLE_DATA = 'table_data';

    /**
     * Chart-related cache tags.
     */
    public const CHARTS = 'charts';
    public const CHART_DATA = 'chart_data';

    /**
     * RBAC-related cache tags.
     */
    public const RBAC = 'rbac';
    public const PERMISSIONS = 'permissions';
    public const ROLES = 'roles';
    public const POLICIES = 'policies';

    /**
     * View-related cache tags.
     */
    public const VIEWS = 'views';
    public const LAYOUTS = 'layouts';
    public const COMPONENTS = 'components';

    /**
     * Theme-related cache tags.
     */
    public const THEMES = 'themes';
    public const THEME_CSS = 'theme_css';
    public const THEME_CONFIG = 'theme_config';

    /**
     * Locale-related cache tags.
     */
    public const LOCALES = 'locales';
    public const TRANSLATIONS = 'translations';

    /**
     * User-related cache tags.
     */
    public const USERS = 'users';
    public const USER_PREFERENCES = 'user_preferences';

    /**
     * Get all cache tags.
     */
    public static function all(): array
    {
        return [
            self::FORMS,
            self::FORM_DEFINITIONS,
            self::FORM_VALIDATION,
            self::TABLES,
            self::TABLE_QUERIES,
            self::TABLE_DATA,
            self::CHARTS,
            self::CHART_DATA,
            self::RBAC,
            self::PERMISSIONS,
            self::ROLES,
            self::POLICIES,
            self::VIEWS,
            self::LAYOUTS,
            self::COMPONENTS,
            self::THEMES,
            self::THEME_CSS,
            self::THEME_CONFIG,
            self::LOCALES,
            self::TRANSLATIONS,
            self::USERS,
            self::USER_PREFERENCES,
        ];
    }

    /**
     * Get form-related tags.
     */
    public static function forms(): array
    {
        return [
            self::FORMS,
            self::FORM_DEFINITIONS,
            self::FORM_VALIDATION,
        ];
    }

    /**
     * Get table-related tags.
     */
    public static function tables(): array
    {
        return [
            self::TABLES,
            self::TABLE_QUERIES,
            self::TABLE_DATA,
        ];
    }

    /**
     * Get chart-related tags.
     */
    public static function charts(): array
    {
        return [
            self::CHARTS,
            self::CHART_DATA,
        ];
    }

    /**
     * Get RBAC-related tags.
     */
    public static function rbac(): array
    {
        return [
            self::RBAC,
            self::PERMISSIONS,
            self::ROLES,
            self::POLICIES,
        ];
    }

    /**
     * Get view-related tags.
     */
    public static function views(): array
    {
        return [
            self::VIEWS,
            self::LAYOUTS,
            self::COMPONENTS,
        ];
    }

    /**
     * Get theme-related tags.
     */
    public static function themes(): array
    {
        return [
            self::THEMES,
            self::THEME_CSS,
            self::THEME_CONFIG,
        ];
    }

    /**
     * Get locale-related tags.
     */
    public static function locales(): array
    {
        return [
            self::LOCALES,
            self::TRANSLATIONS,
        ];
    }

    /**
     * Get user-related tags.
     */
    public static function users(): array
    {
        return [
            self::USERS,
            self::USER_PREFERENCES,
        ];
    }

    /**
     * Get tag for a specific form.
     */
    public static function form(string $name): string
    {
        return self::FORMS . '.' . $name;
    }

    /**
     * Get tag for a specific table.
     */
    public static function table(string $name): string
    {
        return self::TABLES . '.' . $name;
    }

    /**
     * Get tag for a specific chart.
     */
    public static function chart(string $name): string
    {
        return self::CHARTS . '.' . $name;
    }

    /**
     * Get tag for a specific user.
     */
    public static function user(int|string $id): string
    {
        return self::USERS . '.' . $id;
    }

    /**
     * Get tag for a specific theme.
     */
    public static function theme(string $name): string
    {
        return self::THEMES . '.' . $name;
    }

    /**
     * Get tag for a specific locale.
     */
    public static function locale(string $code): string
    {
        return self::LOCALES . '.' . $code;
    }
}

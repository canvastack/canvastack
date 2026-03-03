<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Configuration Manager.
 *
 * Manages all CanvaStack configuration settings with validation,
 * backup/restore, and migration capabilities.
 */
class ConfigurationManager
{
    /**
     * Configuration cache key prefix.
     */
    protected const CACHE_PREFIX = 'canvastack:config:';

    /**
     * Configuration cache TTL (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Configuration validator instance.
     */
    protected ConfigValidator $validator;

    /**
     * Constructor.
     */
    public function __construct(ConfigValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Get all configuration settings.
     */
    public function getAllSettings(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'all',
            self::CACHE_TTL,
            fn () => [
                'app' => $this->getAppSettings(),
                'theme' => $this->getThemeSettings(),
                'localization' => $this->getLocalizationSettings(),
                'rbac' => $this->getRbacSettings(),
                'performance' => $this->getPerformanceSettings(),
                'cache' => $this->getCacheSettings(),
            ]
        );
    }

    /**
     * Get application settings.
     */
    public function getAppSettings(): array
    {
        return [
            'name' => config('canvastack.app.name'),
            'description' => config('canvastack.app.description'),
            'version' => config('canvastack.app.version'),
            'base_url' => config('canvastack.app.base_url'),
            'lang' => config('canvastack.app.lang'),
            'maintenance' => config('canvastack.app.maintenance'),
        ];
    }

    /**
     * Get theme settings.
     */
    public function getThemeSettings(): array
    {
        return [
            'active' => config('canvastack-ui.theme.active'),
            'default' => config('canvastack-ui.theme.default'),
            'cache_enabled' => config('canvastack-ui.theme.cache_enabled'),
            'hot_reload' => config('canvastack-ui.theme.hot_reload'),
            'dark_mode' => [
                'enabled' => config('canvastack-ui.dark_mode.enabled'),
                'default' => config('canvastack-ui.dark_mode.default'),
            ],
        ];
    }

    /**
     * Get localization settings.
     */
    public function getLocalizationSettings(): array
    {
        return [
            'default_locale' => config('canvastack.localization.default_locale'),
            'fallback_locale' => config('canvastack.localization.fallback_locale'),
            'available_locales' => config('canvastack.localization.available_locales'),
            'detect_browser' => config('canvastack.localization.detect_browser'),
            'cache_enabled' => config('canvastack.localization.cache_enabled'),
        ];
    }

    /**
     * Get RBAC settings.
     */
    public function getRbacSettings(): array
    {
        return [
            'contexts' => config('canvastack-rbac.contexts'),
            'cache_enabled' => config('canvastack-rbac.cache.enabled'),
            'super_admin_bypass' => config('canvastack-rbac.authorization.super_admin_bypass'),
            'context_aware' => config('canvastack-rbac.authorization.context_aware'),
            'strict_mode' => config('canvastack-rbac.authorization.strict_mode'),
        ];
    }

    /**
     * Get performance settings.
     */
    public function getPerformanceSettings(): array
    {
        return [
            'chunk_size' => config('canvastack.performance.chunk_size'),
            'eager_load' => config('canvastack.performance.eager_load'),
            'query_cache' => config('canvastack.performance.query_cache'),
            'lazy_load_components' => config('canvastack.performance.lazy_load_components'),
            'optimize_queries' => config('canvastack.performance.optimize_queries'),
        ];
    }

    /**
     * Get cache settings.
     */
    public function getCacheSettings(): array
    {
        return [
            'enabled' => config('canvastack.cache.enabled'),
            'driver' => config('canvastack.cache.driver'),
            'ttl' => config('canvastack.cache.ttl'),
        ];
    }

    /**
     * Update configuration settings.
     */
    public function updateSettings(string $group, array $settings): array
    {
        // Validate settings
        $validation = $this->validator->validate($group, $settings);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        try {
            // Update runtime configuration
            foreach ($settings as $key => $value) {
                $configKey = $this->getConfigKey($group, $key);
                Config::set($configKey, $value);
            }

            // Clear cache
            $this->clearCache();

            return [
                'success' => true,
                'message' => "Configuration group '{$group}' updated successfully",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['general' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get configuration key for a group and setting.
     */
    protected function getConfigKey(string $group, string $key): string
    {
        $mapping = [
            'app' => 'canvastack.app',
            'theme' => 'canvastack-ui.theme',
            'localization' => 'canvastack.localization',
            'rbac' => 'canvastack-rbac',
            'performance' => 'canvastack.performance',
            'cache' => 'canvastack.cache',
        ];

        $prefix = $mapping[$group] ?? "canvastack.{$group}";

        return "{$prefix}.{$key}";
    }

    /**
     * Reset configuration to defaults.
     */
    public function resetToDefaults(string $group): array
    {
        try {
            // Get default values from config files
            $defaults = $this->getDefaultSettings($group);

            // Update settings
            return $this->updateSettings($group, $defaults);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['general' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get default settings for a group.
     */
    protected function getDefaultSettings(string $group): array
    {
        // This would load defaults from config files
        // For now, return current config as defaults
        return match ($group) {
            'app' => $this->getAppSettings(),
            'theme' => $this->getThemeSettings(),
            'localization' => $this->getLocalizationSettings(),
            'rbac' => $this->getRbacSettings(),
            'performance' => $this->getPerformanceSettings(),
            'cache' => $this->getCacheSettings(),
            default => [],
        };
    }

    /**
     * Export configuration to array.
     */
    public function exportConfiguration(): array
    {
        return [
            'version' => '1.0.0',
            'exported_at' => now()->toIso8601String(),
            'settings' => $this->getAllSettings(),
        ];
    }

    /**
     * Import configuration from array.
     */
    public function importConfiguration(array $config): array
    {
        if (!isset($config['settings'])) {
            return [
                'success' => false,
                'errors' => ['general' => 'Invalid configuration format'],
            ];
        }

        $results = [];
        $errors = [];

        foreach ($config['settings'] as $group => $settings) {
            $result = $this->updateSettings($group, $settings);

            if (!$result['success']) {
                $errors[$group] = $result['errors'];
            }

            $results[$group] = $result['success'];
        }

        return [
            'success' => empty($errors),
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Get configuration for UI rendering.
     */
    public function getSettingsForUI(): array
    {
        $settings = $this->getAllSettings();

        return [
            'groups' => [
                [
                    'id' => 'app',
                    'label' => 'Application',
                    'icon' => 'settings',
                    'settings' => $this->formatSettingsForUI($settings['app'], 'app'),
                ],
                [
                    'id' => 'theme',
                    'label' => 'Theme & UI',
                    'icon' => 'palette',
                    'settings' => $this->formatSettingsForUI($settings['theme'], 'theme'),
                ],
                [
                    'id' => 'localization',
                    'label' => 'Localization',
                    'icon' => 'globe',
                    'settings' => $this->formatSettingsForUI($settings['localization'], 'localization'),
                ],
                [
                    'id' => 'rbac',
                    'label' => 'Security & RBAC',
                    'icon' => 'shield',
                    'settings' => $this->formatSettingsForUI($settings['rbac'], 'rbac'),
                ],
                [
                    'id' => 'performance',
                    'label' => 'Performance',
                    'icon' => 'zap',
                    'settings' => $this->formatSettingsForUI($settings['performance'], 'performance'),
                ],
                [
                    'id' => 'cache',
                    'label' => 'Cache',
                    'icon' => 'database',
                    'settings' => $this->formatSettingsForUI($settings['cache'], 'cache'),
                ],
            ],
        ];
    }

    /**
     * Format settings for UI rendering.
     */
    protected function formatSettingsForUI(array $settings, string $group): array
    {
        $formatted = [];

        foreach ($settings as $key => $value) {
            $formatted[] = [
                'key' => $key,
                'value' => $value,
                'type' => $this->getSettingType($value),
                'label' => $this->getSettingLabel($group, $key),
                'description' => $this->getSettingDescription($group, $key),
                'editable' => $this->isSettingEditable($group, $key),
            ];
        }

        return $formatted;
    }

    /**
     * Get setting type for UI rendering.
     */
    protected function getSettingType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Get setting label.
     */
    protected function getSettingLabel(string $group, string $key): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Get setting description.
     */
    protected function getSettingDescription(string $group, string $key): string
    {
        // This would load descriptions from translation files
        return '';
    }

    /**
     * Check if setting is editable via UI.
     */
    protected function isSettingEditable(string $group, string $key): bool
    {
        // Some settings should not be editable via UI
        $nonEditable = [
            'app.version',
            'cache.driver', // Should be set via .env
        ];

        return !in_array("{$group}.{$key}", $nonEditable, true);
    }

    /**
     * Clear configuration cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::tags(['canvastack:config'])->flush();
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        return [
            'enabled' => config('canvastack.cache.enabled'),
            'driver' => config('canvastack.cache.driver'),
            'hit_rate' => $this->calculateCacheHitRate(),
        ];
    }

    /**
     * Calculate cache hit rate.
     */
    protected function calculateCacheHitRate(): float
    {
        // This would calculate actual hit rate from cache statistics
        // For now, return a placeholder
        return 0.0;
    }
}

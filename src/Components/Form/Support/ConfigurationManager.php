<?php

namespace Canvastack\Canvastack\Components\Form\Support;

/**
 * ConfigurationManager - Manages form feature configuration.
 *
 * Provides centralized configuration management for all form features
 * with default values and support for custom overrides.
 */
class ConfigurationManager
{
    /**
     * Configuration array.
     */
    protected array $config;

    /**
     * Create a new ConfigurationManager instance.
     *
     * @param array $config Custom configuration to merge with defaults
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace_recursive($this->getDefaults(), $config);
    }

    /**
     * Get configuration value using dot notation.
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set configuration value using dot notation.
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $value Value to set
     * @return void
     */
    public function set(string $key, $value): void
    {
        data_set($this->config, $key, $value);
    }

    /**
     * Get all configuration.
     *
     * @return array Complete configuration array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get default configuration for all features.
     *
     * @return array Default configuration array
     */
    protected function getDefaults(): array
    {
        return [
            'tabs' => [
                'default_class' => '',
                'animation' => 'fade',
            ],
            'ajax_sync' => [
                'cache_ttl' => 300,
                'endpoint' => '/ajax/sync',
            ],
            'file_upload' => [
                'disk' => 'public',
                'max_size' => 5120,
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                'thumbnail' => true,
                'thumbnail_width' => 150,
                'thumbnail_height' => 150,
                'thumbnail_quality' => 80,
            ],
            'ckeditor' => [
                'version' => 5,
                'height' => 300,
                'toolbar' => 'default',
                'image_upload' => true,
            ],
            'character_counter' => [
                'warning_threshold' => 90,
                'danger_threshold' => 100,
            ],
        ];
    }
}

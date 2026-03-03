<?php

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;

/**
 * DateRangePicker Component.
 *
 * Provides date range selection with Flatpickr library.
 * Supports start/end date selection, predefined ranges, and date constraints.
 */
class DateRangePicker
{
    protected AssetManager $assetManager;

    protected array $instances = [];

    protected array $config;

    public function __construct(AssetManager $assetManager, array $config = [])
    {
        $this->assetManager = $assetManager;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Register a date range picker field instance.
     */
    public function register(string $fieldName, array $options = []): void
    {
        $this->instances[$fieldName] = array_merge($this->config, $options);
        $this->assetManager->loadFlatpickr();
    }

    /**
     * Check if there are any registered instances.
     */
    public function hasInstances(): bool
    {
        return count($this->instances) > 0;
    }

    /**
     * Get all registered instances.
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Render initialization script for all instances.
     */
    public function renderScript(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        $scripts = [];
        foreach ($this->instances as $fieldName => $options) {
            $scripts[] = $this->renderInstanceScript($fieldName, $options);
        }

        $scriptsHtml = implode("\n", $scripts);

        return <<<HTML
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            {$scriptsHtml}
        });
        </script>
        HTML;
    }

    /**
     * Render initialization script for a single instance.
     */
    protected function renderInstanceScript(string $fieldName, array $options): string
    {
        $inputSelector = "input[name='{$fieldName}']";
        $config = $this->buildFlatpickrConfig($options);
        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        return <<<JS
            const dateRangeInput_{$this->sanitizeFieldName($fieldName)} = document.querySelector('{$inputSelector}');
            if (dateRangeInput_{$this->sanitizeFieldName($fieldName)}) {
                flatpickr(dateRangeInput_{$this->sanitizeFieldName($fieldName)}, {$configJson});
            }
        JS;
    }

    /**
     * Build Flatpickr configuration object.
     */
    protected function buildFlatpickrConfig(array $options): array
    {
        $config = [
            'mode' => 'range',
            'dateFormat' => $options['format'] ?? 'Y-m-d',
            'enableTime' => $options['enableTime'] ?? false,
        ];

        // Add min/max date constraints
        if (!empty($options['minDate'])) {
            $config['minDate'] = $options['minDate'];
        }

        if (!empty($options['maxDate'])) {
            $config['maxDate'] = $options['maxDate'];
        }

        // Add predefined ranges
        if (!empty($options['predefinedRanges'])) {
            $config['plugins'] = [
                [
                    'name' => 'rangePlugin',
                    'config' => [
                        'presets' => $options['predefinedRanges'],
                    ],
                ],
            ];
        }

        return $config;
    }

    /**
     * Sanitize field name for use in JavaScript variable names.
     */
    protected function sanitizeFieldName(string $fieldName): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'format' => 'Y-m-d',
            'minDate' => null,
            'maxDate' => null,
            'predefinedRanges' => [],
            'enableTime' => false,
        ];
    }

    /**
     * Render Flatpickr CSS assets.
     */
    public function renderAssets(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        return <<<HTML
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
        HTML;
    }
}

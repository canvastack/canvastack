<?php

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;

/**
 * MonthPicker Component.
 *
 * Provides month/year selection with Flatpickr library.
 * Supports month constraints and multiple selection.
 */
class MonthPicker
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
     * Register a month picker field instance.
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
            const monthInput_{$this->sanitizeFieldName($fieldName)} = document.querySelector('{$inputSelector}');
            if (monthInput_{$this->sanitizeFieldName($fieldName)}) {
                flatpickr(monthInput_{$this->sanitizeFieldName($fieldName)}, {$configJson});
            }
        JS;
    }

    /**
     * Build Flatpickr configuration object for month selection.
     */
    protected function buildFlatpickrConfig(array $options): array
    {
        $config = [
            'plugins' => [
                [
                    'name' => 'monthSelect',
                    'config' => [
                        'shorthand' => true,
                        'dateFormat' => $options['format'] ?? 'Y-m',
                        'altFormat' => 'F Y',
                    ],
                ],
            ],
        ];

        // Add min/max month constraints
        if (!empty($options['minMonth'])) {
            $config['minDate'] = $options['minMonth'] . '-01';
        }

        if (!empty($options['maxMonth'])) {
            $config['maxDate'] = $options['maxMonth'] . '-01';
        }

        // Add multiple selection support
        if ($options['multiple'] ?? false) {
            $config['mode'] = 'multiple';
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
            'format' => 'Y-m',
            'minMonth' => null,
            'maxMonth' => null,
            'multiple' => false,
        ];
    }

    /**
     * Render Flatpickr CSS assets with month select plugin.
     */
    public function renderAssets(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        return <<<HTML
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/index.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/style.css">
        HTML;
    }
}

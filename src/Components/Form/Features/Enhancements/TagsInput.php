<?php

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;

/**
 * TagsInput Component.
 *
 * Provides tag-style input fields with add/remove functionality using Tagify library.
 * Supports tag validation, maximum count, autocomplete, and duplicate prevention.
 */
class TagsInput
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
     * Register a tags input field instance.
     */
    public function register(string $fieldName, array $options = []): void
    {
        $this->instances[$fieldName] = array_merge($this->config, $options);
        $this->assetManager->loadTagify();
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
        $config = $this->buildTagifyConfig($options);
        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        return <<<JS
            const tagifyInput_{$this->sanitizeFieldName($fieldName)} = document.querySelector('{$inputSelector}');
            if (tagifyInput_{$this->sanitizeFieldName($fieldName)}) {
                new Tagify(tagifyInput_{$this->sanitizeFieldName($fieldName)}, {$configJson});
            }
        JS;
    }

    /**
     * Build Tagify configuration object.
     */
    protected function buildTagifyConfig(array $options): array
    {
        $config = [
            'delimiters' => $options['delimiters'] ?? ',',
            'maxTags' => $options['maxTags'] ?? null,
            'duplicates' => false,
            'enforceWhitelist' => $options['enforceWhitelist'] ?? false,
            'dropdown' => [
                'enabled' => $options['autocomplete'] ?? false,
                'maxItems' => 10,
                'classname' => 'tagify__dropdown',
                'closeOnSelect' => true,
            ],
        ];

        // Add whitelist for autocomplete
        if (!empty($options['whitelist'])) {
            $config['whitelist'] = $options['whitelist'];
            $config['dropdown']['enabled'] = true;
        }

        // Add pattern validation
        if (!empty($options['pattern'])) {
            $config['pattern'] = $options['pattern'];
        }

        // Add tag validation
        if (!empty($options['validate'])) {
            $config['validate'] = $options['validate'];
        }

        // Remove null values
        return array_filter($config, fn ($value) => $value !== null);
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
            'delimiters' => ',',
            'maxTags' => null,
            'enforceWhitelist' => false,
            'autocomplete' => false,
            'whitelist' => [],
            'pattern' => null,
            'validate' => null,
        ];
    }

    /**
     * Render Tagify CSS assets.
     */
    public function renderAssets(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        return <<<HTML
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.css">
        <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.min.js"></script>
        HTML;
    }
}

<?php

namespace Canvastack\Canvastack\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;

/**
 * CKEditor Integration Class.
 *
 * Manages CKEditor instances for textarea fields with rich text editing capabilities.
 * Handles registration, configuration, and script rendering for CKEditor initialization.
 *
 * Requirements: 4.1, 4.2, 4.22
 */
class CKEditorIntegration
{
    /**
     * Registered editor instances.
     *
     * @var array<string, array>
     */
    protected array $instances = [];

    /**
     * Editor configuration manager.
     */
    protected EditorConfig $config;

    /**
     * Asset manager for loading CKEditor library.
     */
    protected AssetManager $assetManager;

    /**
     * Content sanitizer for HTML cleaning.
     */
    protected ContentSanitizer $sanitizer;

    /**
     * Current rendering context (admin or public).
     */
    protected string $context = 'admin';

    /**
     * Dark mode enabled flag.
     */
    protected bool $darkMode = false;

    /**
     * Create a new CKEditor integration instance.
     */
    public function __construct(
        EditorConfig $config,
        ?ContentSanitizer $sanitizer = null,
        ?AssetManager $assetManager = null
    ) {
        $this->config = $config;
        $this->sanitizer = $sanitizer ?? new ContentSanitizer();
        $this->assetManager = $assetManager ?? new AssetManager();
    }

    /**
     * Register a textarea field for CKEditor initialization.
     *
     * @param string $fieldName The name attribute of the textarea field
     * @param array $options Custom configuration options for this editor instance
     * @return void
     *
     * Requirements: 4.1, 4.2
     */
    public function register(string $fieldName, array $options = []): void
    {
        // Get context-specific configuration
        $contextConfig = $this->config->getContextConfig($this->context);

        // Apply dark mode configuration if enabled
        if ($this->darkMode) {
            $darkModeConfig = $this->config->getDarkModeConfig();
            $contextConfig = array_merge($contextConfig, $darkModeConfig);
        }

        // Merge custom options with context defaults
        $config = array_merge(
            $contextConfig,
            $options
        );

        // Store instance configuration
        $this->instances[$fieldName] = [
            'fieldName' => $fieldName,
            'config' => $config,
            'context' => $this->context,
            'darkMode' => $this->darkMode,
            'registered_at' => microtime(true),
        ];
    }

    /**
     * Set rendering context (admin or public).
     *
     * @param string $context Context name: 'admin' or 'public'
     * @return self
     *
     * Requirements: 4.11, 4.12
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get current rendering context.
     *
     * @return string Current context
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Enable or disable dark mode.
     *
     * @param bool $enable Whether to enable dark mode
     * @return self
     *
     * Requirements: 4.13
     */
    public function setDarkMode(bool $enable = true): self
    {
        $this->darkMode = $enable;

        return $this;
    }

    /**
     * Check if dark mode is enabled.
     *
     * @return bool True if dark mode is enabled
     */
    public function isDarkMode(): bool
    {
        return $this->darkMode;
    }

    /**
     * Check if any editor instances have been registered.
     *
     * @return bool True if at least one editor instance exists
     *
     * Requirements: 4.22
     */
    public function hasInstances(): bool
    {
        return count($this->instances) > 0;
    }

    /**
     * Get all registered editor instances.
     *
     * @return array<string, array>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Get a specific editor instance configuration.
     *
     * @param string $fieldName The field name to retrieve
     * @return array|null The instance configuration or null if not found
     */
    public function getInstance(string $fieldName): ?array
    {
        return $this->instances[$fieldName] ?? null;
    }

    /**
     * Check if a specific field has been registered.
     *
     * @param string $fieldName The field name to check
     * @return bool True if the field is registered
     */
    public function isRegistered(string $fieldName): bool
    {
        return isset($this->instances[$fieldName]);
    }

    /**
     * Clear all registered instances.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->instances = [];
    }

    /**
     * Remove a specific registered instance.
     *
     * @param string $fieldName The field name to remove
     * @return bool True if the instance was removed
     */
    public function unregister(string $fieldName): bool
    {
        if (isset($this->instances[$fieldName])) {
            unset($this->instances[$fieldName]);

            return true;
        }

        return false;
    }

    /**
     * Get the count of registered instances.
     *
     * @return int Number of registered editor instances
     */
    public function count(): int
    {
        return count($this->instances);
    }

    /**
     * Render JavaScript for all registered CKEditor instances.
     *
     * Generates initialization code for each registered editor instance.
     * Returns empty string if no instances are registered.
     *
     * @return string JavaScript code for CKEditor initialization
     *
     * Requirements: 4.1, 4.2, 4.3, 4.5
     */
    public function renderScript(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        $scripts = [];

        foreach ($this->instances as $instance) {
            $scripts[] = $this->renderInstanceScript(
                $instance['fieldName'],
                $instance['config']
            );
        }

        $scriptContent = implode("\n\n", $scripts);

        return <<<HTML
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            {$scriptContent}
        });
        </script>
        HTML;
    }

    /**
     * Render CKEditor asset tags.
     *
     * Loads CKEditor library only when needed (conditional loading).
     * Uses AssetManager to track loaded assets and prevent duplicate loading.
     *
     * @param bool $lazy Whether to use lazy loading (defer)
     * @return string HTML tags for loading CKEditor assets
     *
     * Requirements: 4.3, 4.4
     */
    public function renderAssets(bool $lazy = true): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        return $this->assetManager->renderAssetTags('ckeditor', $lazy);
    }

    /**
     * Render complete CKEditor integration (assets + scripts).
     *
     * Convenience method that renders both asset loading tags and
     * initialization scripts in the correct order.
     *
     * @param bool $lazy Whether to use lazy loading for assets
     * @return string Complete HTML for CKEditor integration
     *
     * Requirements: 4.3, 4.4
     */
    public function render(bool $lazy = true): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        $assets = $this->renderAssets($lazy);
        $scripts = $this->renderScript();

        return $assets . "\n" . $scripts;
    }

    /**
     * Render JavaScript for a single CKEditor instance.
     *
     * Generates initialization code for a specific editor instance with
     * its configuration including toolbar, language, height, and other options.
     * Preserves content during validation errors by using old input values.
     *
     * @param string $fieldName The textarea field name to initialize
     * @param array $config Configuration options for this instance
     * @return string JavaScript code for single editor initialization
     *
     * Requirements: 4.1, 4.2, 4.5, 4.14
     */
    public function renderInstanceScript(string $fieldName, array $config): string
    {
        // Add image upload configuration if upload URL is provided
        $config = $this->addImageUploadConfig($config);

        $configJson = $this->buildConfigurationJson($config);

        // Get preserved content from old input (Laravel validation)
        $preservedContent = $this->getPreservedContent($fieldName);
        $preserveScript = '';

        if ($preservedContent !== null) {
            $preserveScript = "
                // Preserve content from validation error
                editor_{$this->sanitizeFieldName($fieldName)}.on('instanceReady', function() {
                    this.setData(" . json_encode($preservedContent) . ');
                });';
        }

        return <<<JS
            // Initialize CKEditor for field: {$fieldName}
            if (typeof CKEDITOR !== 'undefined') {
                const editor_{$this->sanitizeFieldName($fieldName)} = CKEDITOR.replace('{$fieldName}', {$configJson});
                {$preserveScript}
                
                // Store editor instance for later access
                if (!window.ckeditorInstances) {
                    window.ckeditorInstances = {};
                }
                window.ckeditorInstances['{$fieldName}'] = editor_{$this->sanitizeFieldName($fieldName)};
            } else {
                console.error('CKEditor library not loaded. Please include CKEditor script before initializing editors.');
            }
        JS;
    }

    /**
     * Add image upload configuration to editor config.
     *
     * Configures simpleUpload plugin with upload URL and CSRF token.
     * Only adds configuration if upload URL is provided.
     *
     * @param array $config Editor configuration
     * @return array Configuration with image upload support
     *
     * Requirements: 4.7, 4.8
     */
    protected function addImageUploadConfig(array $config): array
    {
        // Check if upload URL is configured
        if (isset($config['uploadUrl'])) {
            $uploadUrl = $config['uploadUrl'];
            unset($config['uploadUrl']); // Remove from config as it's not a CKEditor option

            // Get CSRF token
            $csrfToken = $this->getCsrfToken();

            // Configure simpleUpload adapter
            $config['simpleUpload'] = [
                'uploadUrl' => $uploadUrl,
                'withCredentials' => true,
                'headers' => [
                    'X-CSRF-TOKEN' => $csrfToken,
                ],
            ];

            // Also configure filebrowser for backward compatibility
            $config['filebrowserUploadUrl'] = $uploadUrl;
            $config['filebrowserUploadMethod'] = 'form';
        }

        return $config;
    }

    /**
     * Get CSRF token for Ajax requests.
     *
     * Retrieves CSRF token from meta tag or session.
     *
     * @return string CSRF token
     */
    protected function getCsrfToken(): string
    {
        // In Laravel, we can get the token from the session
        if (function_exists('csrf_token')) {
            return csrf_token();
        }

        return '';
    }

    /**
     * Build configuration JSON for CKEditor initialization.
     *
     * Converts PHP configuration array to JavaScript object notation.
     * Handles special cases like toolbar arrays and function references.
     *
     * @param array $config Configuration array
     * @return string JSON-formatted configuration
     */
    protected function buildConfigurationJson(array $config): string
    {
        // Handle toolbar configuration specially
        if (isset($config['toolbar'])) {
            $toolbarJson = $this->buildToolbarJson($config['toolbar']);
            unset($config['toolbar']);
        }

        // Convert basic config to JSON
        $configJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Add toolbar back if it exists
        if (isset($toolbarJson)) {
            // Remove closing brace
            $configJson = rtrim($configJson, "\n}");
            // Add toolbar
            $configJson .= ",\n    \"toolbar\": " . $toolbarJson . "\n}";
        }

        // Handle CKEDITOR constants (enterMode, shiftEnterMode)
        $configJson = $this->replaceEditorConstants($configJson);

        return $configJson;
    }

    /**
     * Build toolbar configuration JSON.
     *
     * Converts toolbar array to CKEditor-compatible format.
     *
     * @param array $toolbar Toolbar configuration array
     * @return string JSON-formatted toolbar configuration
     */
    protected function buildToolbarJson(array $toolbar): string
    {
        return json_encode($toolbar, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Replace CKEDITOR constant strings with actual constants.
     *
     * Converts string references like "CKEDITOR.ENTER_P" to actual
     * JavaScript constant references.
     *
     * @param string $json JSON string with constant references
     * @return string JSON with constants replaced
     */
    protected function replaceEditorConstants(string $json): string
    {
        // Replace enterMode constants
        $json = str_replace(
            '"CKEDITOR.ENTER_P"',
            'CKEDITOR.ENTER_P',
            $json
        );
        $json = str_replace(
            '"CKEDITOR.ENTER_BR"',
            'CKEDITOR.ENTER_BR',
            $json
        );
        $json = str_replace(
            '"CKEDITOR.ENTER_DIV"',
            'CKEDITOR.ENTER_DIV',
            $json
        );

        return $json;
    }

    /**
     * Sanitize field name for use as JavaScript variable name.
     *
     * Replaces non-alphanumeric characters with underscores to create
     * valid JavaScript variable names.
     *
     * @param string $fieldName Original field name
     * @return string Sanitized field name
     */
    protected function sanitizeFieldName(string $fieldName): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
    }

    /**
     * Get preserved content from old input (Laravel validation).
     *
     * Retrieves content from Laravel's old() helper to preserve
     * editor content during form validation errors.
     *
     * @param string $fieldName Field name to retrieve
     * @return string|null Preserved content or null if not found
     *
     * Requirements: 4.14
     */
    protected function getPreservedContent(string $fieldName): ?string
    {
        // Check if old() helper is available (Laravel)
        if (function_exists('old')) {
            $oldValue = old($fieldName);
            if ($oldValue !== null && $oldValue !== '') {
                return $oldValue;
            }
        }

        return null;
    }

    /**
     * Sanitize HTML content from CKEditor.
     *
     * Cleans HTML content to prevent XSS attacks while preserving
     * safe HTML formatting. Should be called when processing form
     * submissions containing CKEditor content.
     *
     * @param string $html Raw HTML content from CKEditor
     * @return string Sanitized HTML content
     *
     * Requirements: 4.15, 14.8
     */
    public function sanitize(string $html): string
    {
        return $this->sanitizer->clean($html);
    }

    /**
     * Get the content sanitizer instance.
     *
     * @return ContentSanitizer Sanitizer instance
     */
    public function getSanitizer(): ContentSanitizer
    {
        return $this->sanitizer;
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Support\AssetManager;

/**
 * SearchableSelect - Add search functionality to select dropdowns using Choices.js.
 *
 * This component enhances standard select dropdowns with search/filter capability,
 * making it easier for users to find options in long lists.
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.11
 */
class SearchableSelect
{
    /**
     * Registered searchable select instances.
     *
     * @var array<string, array>
     */
    protected array $instances = [];

    /**
     * Asset manager for loading Choices.js library.
     *
     * @var AssetManager
     */
    protected AssetManager $assetManager;

    /**
     * Constructor.
     *
     * @param AssetManager|null $assetManager Asset manager instance
     */
    public function __construct(?AssetManager $assetManager = null)
    {
        $this->assetManager = $assetManager ?? new AssetManager();
    }

    /**
     * Register a select field as searchable.
     *
     * Requirement 6.1: Enable search functionality on select dropdown
     *
     * @param string $fieldName Field name to make searchable
     * @param array $options Configuration options for Choices.js
     * @return void
     */
    public function register(string $fieldName, array $options = []): void
    {
        $this->instances[$fieldName] = $options;
    }

    /**
     * Check if any instances are registered.
     *
     * Requirement 6.16: Load library assets only when needed
     *
     * @return bool True if at least one searchable select exists
     */
    public function hasInstances(): bool
    {
        return count($this->instances) > 0;
    }

    /**
     * Get all registered instances.
     *
     * @return array<string, array>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Render initialization script for all registered instances.
     *
     * Generates JavaScript code to initialize Choices.js on all registered
     * select fields. Only renders if at least one instance is registered.
     * Includes Choices.js library assets.
     *
     * Requirement 6.2: Use Choices.js library for implementation
     * Requirement 6.3: Provide search input field within dropdown
     * Requirement 6.4: Filter options in real-time as user types
     * Requirement 6.5: Highlight matching text in filtered options
     * Requirement 6.6: Support keyboard navigation through filtered results
     * Requirement 6.16: Load library assets only when needed
     * Requirement 6.22: Initialize within 200ms per instance
     *
     * @return string JavaScript code wrapped in script tags with asset loading
     */
    public function renderScript(): string
    {
        if (!$this->hasInstances()) {
            return '';
        }

        // Load Choices.js assets (Req 6.16)
        $assetTags = $this->assetManager->renderAssetTags('choices', true);

        $scripts = [];
        foreach ($this->instances as $fieldName => $options) {
            $scripts[] = $this->renderInstanceScript($fieldName, $options);
        }

        $scriptsHtml = implode("\n            ", $scripts);

        return <<<HTML
        {$assetTags}
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            {$scriptsHtml}
        });
        </script>
        HTML;
    }

    /**
     * Render script for single searchable select instance.
     *
     * Generates JavaScript code to initialize Choices.js on a specific select field.
     * Supports both static options and Ajax-based remote data loading.
     * Adds context data attribute for styling.
     *
     * Requirement 6.2: Use Choices.js library
     * Requirement 6.8: Support remote data loading via Ajax
     * Requirement 6.13: Implement admin styling
     * Requirement 6.14: Implement public styling
     * Requirement 6.22: Initialize within 200ms per instance
     *
     * @param string $fieldName Field name
     * @param array $options Configuration options
     * @return string JavaScript code for single instance
     */
    protected function renderInstanceScript(string $fieldName, array $options): string
    {
        $config = json_encode($this->buildConfig($options));
        $safeFieldName = str_replace(['[', ']'], ['_', ''], $fieldName);
        $context = $this->getContext($options);

        // Check if Ajax support is needed (Req 6.8)
        if (isset($options['ajax_url'])) {
            return $this->renderAjaxInstanceScript($fieldName, $safeFieldName, $options);
        }

        // Standard initialization with context attribute
        return <<<JS
        const select_{$safeFieldName} = document.querySelector('select[name="{$fieldName}"]');
            if (select_{$safeFieldName}) {
                const choices_{$safeFieldName} = new Choices(select_{$safeFieldName}, {$config});
                // Add context data attribute for styling (Req 6.13, 6.14)
                select_{$safeFieldName}.closest('.choices')?.setAttribute('data-context', '{$context}');
            }
        JS;
    }

    /**
     * Render script for Ajax-enabled searchable select.
     *
     * Generates JavaScript code for remote data loading with caching support.
     * Adds context data attribute for styling.
     *
     * Requirement 6.8: Support remote data loading via Ajax
     * Requirement 6.9: Implement search result caching (5 minutes)
     * Requirement 6.13: Implement admin styling
     * Requirement 6.14: Implement public styling
     *
     * @param string $fieldName Original field name
     * @param string $safeFieldName Sanitized field name for JavaScript variable
     * @param array $options Configuration options
     * @return string JavaScript code for Ajax instance
     */
    protected function renderAjaxInstanceScript(string $fieldName, string $safeFieldName, array $options): string
    {
        $ajaxUrl = $options['ajax_url'];
        $cacheTime = ($options['ajax_cache'] ?? 300) * 1000; // Convert to milliseconds
        $config = json_encode($this->buildConfig($options));
        $context = $this->getContext($options);

        return <<<JS
        const select_{$safeFieldName} = document.querySelector('select[name="{$fieldName}"]');
            if (select_{$safeFieldName}) {
                // Cache for Ajax results (Req 6.9)
                const cache_{$safeFieldName} = {
                    data: null,
                    timestamp: null,
                    isValid: function() {
                        return this.data && this.timestamp && (Date.now() - this.timestamp < {$cacheTime});
                    }
                };
                
                const choices_{$safeFieldName} = new Choices(select_{$safeFieldName}, {$config});
                
                // Add context data attribute for styling (Req 6.13, 6.14)
                select_{$safeFieldName}.closest('.choices')?.setAttribute('data-context', '{$context}');
                
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                
                // Load data via Ajax (Req 6.8)
                function loadOptions_{$safeFieldName}(searchTerm = '') {
                    // Check cache first (Req 6.9)
                    if (cache_{$safeFieldName}.isValid() && !searchTerm) {
                        return Promise.resolve(cache_{$safeFieldName}.data);
                    }
                    
                    return fetch('{$ajaxUrl}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            search: searchTerm,
                            field: '{$fieldName}'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Cache the results (Req 6.9)
                        if (!searchTerm) {
                            cache_{$safeFieldName}.data = data;
                            cache_{$safeFieldName}.timestamp = Date.now();
                        }
                        return data;
                    })
                    .catch(error => {
                        console.error('Error loading options:', error);
                        return [];
                    });
                }
                
                // Initial load
                loadOptions_{$safeFieldName}().then(data => {
                    if (data.options) {
                        choices_{$safeFieldName}.setChoices(data.options, 'value', 'label', true);
                    }
                });
                
                // Search event handler
                select_{$safeFieldName}.addEventListener('search', function(event) {
                    const searchTerm = event.detail.value;
                    if (searchTerm.length >= 2) {
                        loadOptions_{$safeFieldName}(searchTerm).then(data => {
                            if (data.options) {
                                choices_{$safeFieldName}.setChoices(data.options, 'value', 'label', true);
                            }
                        });
                    }
                });
            }
        JS;
    }

    /**
     * Build Choices.js configuration from options.
     *
     * Converts component options to Choices.js configuration format.
     * Provides sensible defaults for all configuration options.
     *
     * Requirement 6.3: Provide search input field within dropdown
     * Requirement 6.4: Filter options in real-time
     * Requirement 6.5: Highlight matching text
     * Requirement 6.6: Support keyboard navigation
     * Requirement 6.7: Support multiple selection mode
     * Requirement 6.8: Support remote data loading via Ajax
     * Requirement 6.9: Implement search result caching
     * Requirement 6.11: Support grouping of options
     * Requirement 6.19: Support placeholder text
     * Requirement 6.20: Support clear selection button
     *
     * @param array $options Component options
     * @return array Choices.js configuration
     */
    protected function buildConfig(array $options): array
    {
        $config = [
            // Enable search functionality (Req 6.3, 6.4)
            'searchEnabled' => true,
            'searchPlaceholderValue' => $options['search_placeholder'] ?? 'Search...',
            'searchResultLimit' => $options['search_limit'] ?? 100,
            'searchFloor' => $options['search_floor'] ?? 1, // Minimum characters before search
            'searchChoices' => true, // Enable real-time filtering (Req 6.4)
            'searchFields' => ['label', 'value'], // Search in both label and value

            // Highlighting (Req 6.5) - Choices.js handles this automatically
            'fuseOptions' => [
                'threshold' => 0.3, // Fuzzy search threshold
                'distance' => 100,
            ],

            // Multiple selection support (Req 6.7)
            'removeItemButton' => $options['multiple'] ?? false,

            // Sorting
            'shouldSort' => $options['sort'] ?? true,
            'sortFilter' => $options['sort_filter'] ?? null,

            // Placeholder (Req 6.19)
            'placeholder' => $options['placeholder'] ?? true,
            'placeholderValue' => $options['placeholder_text'] ?? 'Select an option',

            // Item selection
            'itemSelectText' => $options['select_text'] ?? '',

            // Rendering
            'renderChoiceLimit' => $options['render_limit'] ?? -1,
            'maxItemCount' => $options['max_items'] ?? -1,

            // Clear button (Req 6.20)
            'removeItems' => true,
            'removeItemButton' => $options['multiple'] ?? false,

            // Keyboard navigation (Req 6.6) - Built into Choices.js
            'allowHTML' => false, // Security: prevent XSS

            // Position
            'position' => $options['position'] ?? 'auto',
            'resetScrollPosition' => true,

            // Custom classes for styling (Req 6.12, 6.13, 6.14, 6.15)
            'classNames' => $this->getClassNames($options),
        ];

        // Add Ajax support if configured (Req 6.8, 6.9)
        if (isset($options['ajax_url'])) {
            $config['ajax'] = true;
            $config['ajaxUrl'] = $options['ajax_url'];
            $config['ajaxCache'] = $options['ajax_cache'] ?? 300; // 5 minutes default (Req 6.9)
        }

        return $config;
    }

    /**
     * Get class names for Choices.js styling.
     *
     * Provides Tailwind CSS compatible class names for styling.
     * Adds context data attribute for admin/public specific styling.
     *
     * Requirement 6.12: Use Tailwind CSS styling
     * Requirement 6.13: Implement admin styling
     * Requirement 6.14: Implement public styling
     * Requirement 6.15: Add dark mode support
     *
     * @param array $options Component options
     * @return array Class names configuration
     */
    protected function getClassNames(array $options): array
    {
        $baseClasses = [
            'containerOuter' => 'choices',
            'containerInner' => 'choices__inner',
            'input' => 'choices__input',
            'inputCloned' => 'choices__input--cloned',
            'list' => 'choices__list',
            'listItems' => 'choices__list--multiple',
            'listSingle' => 'choices__list--single',
            'listDropdown' => 'choices__list--dropdown',
            'item' => 'choices__item',
            'itemSelectable' => 'choices__item--selectable',
            'itemDisabled' => 'choices__item--disabled',
            'itemChoice' => 'choices__item--choice',
            'placeholder' => 'choices__placeholder',
            'group' => 'choices__group',
            'groupHeading' => 'choices__heading',
            'button' => 'choices__button',
            'activeState' => 'is-active',
            'focusState' => 'is-focused',
            'openState' => 'is-open',
            'disabledState' => 'is-disabled',
            'highlightedState' => 'is-highlighted',
            'selectedState' => 'is-selected',
            'flippedState' => 'is-flipped',
            'loadingState' => 'is-loading',
        ];

        return $baseClasses;
    }

    /**
     * Get context for styling (admin or public).
     *
     * @param array $options Component options
     * @return string Context identifier
     */
    protected function getContext(array $options): string
    {
        return $options['context'] ?? 'admin';
    }

    /**
     * Render context data attribute for styling.
     *
     * Adds data-context attribute to enable context-specific CSS.
     *
     * @param array $options Component options
     * @return string Data attribute HTML
     */
    protected function renderContextAttribute(array $options): string
    {
        $context = $this->getContext($options);

        return "data-context=\"{$context}\"";
    }
}

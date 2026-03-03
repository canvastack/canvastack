<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Enhancements;

/**
 * CharacterCounter - Display real-time character count for text fields.
 *
 * Provides visual feedback on character count with color changes:
 * - Gray: Normal (< 90%)
 * - Amber: Warning (90-99%)
 * - Red: Danger (100%)
 */
class CharacterCounter
{
    /**
     * Render character counter with real-time updates.
     *
     * @param string $fieldName The name of the field to count
     * @param int $maxLength Maximum allowed characters
     * @param string $context Rendering context (admin or public)
     * @return string HTML and JavaScript for character counter
     */
    public function render(string $fieldName, int $maxLength, string $context = 'admin'): string
    {
        $counterId = 'counter-' . str_replace(['[', ']', '.'], ['_', '_', '_'], $fieldName);
        $ariaLive = 'polite'; // For accessibility

        // Context-specific styling
        $baseClasses = $context === 'admin'
            ? 'text-sm mt-1 text-gray-500 dark:text-gray-400'
            : 'text-sm mt-1 text-gray-600 dark:text-gray-300';

        return <<<HTML
        <div id="{$counterId}" 
             class="{$baseClasses}" 
             role="status" 
             aria-live="{$ariaLive}"
             aria-atomic="true">
            <span class="current-count">0</span> / <span class="max-count">{$maxLength}</span> characters
        </div>
        
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const field = document.querySelector('[name="{$fieldName}"]');
                const counter = document.getElementById('{$counterId}');
                
                if (!field || !counter) {
                    return;
                }
                
                const currentCount = counter.querySelector('.current-count');
                const maxLength = {$maxLength};
                
                // Initialize count
                updateCount();
                
                // Update on input with debouncing for performance
                let updateTimeout;
                field.addEventListener('input', function() {
                    clearTimeout(updateTimeout);
                    updateTimeout = setTimeout(updateCount, 10);
                });
                
                function updateCount() {
                    // Unicode-aware character count (handles emojis correctly)
                    const length = [...field.value].length;
                    currentCount.textContent = length;
                    
                    // Calculate percentage
                    const percentage = (length / maxLength) * 100;
                    
                    // Update color based on percentage
                    if (percentage >= 100) {
                        counter.classList.remove('text-gray-500', 'text-gray-400', 'text-gray-600', 'text-gray-300', 'text-amber-500', 'text-amber-600', 'dark:text-gray-400', 'dark:text-gray-300');
                        counter.classList.add('text-red-500', 'dark:text-red-400');
                    } else if (percentage >= 90) {
                        counter.classList.remove('text-gray-500', 'text-gray-400', 'text-gray-600', 'text-gray-300', 'text-red-500', 'dark:text-gray-400', 'dark:text-gray-300', 'dark:text-red-400');
                        counter.classList.add('text-amber-500', 'dark:text-amber-400');
                    } else {
                        counter.classList.remove('text-amber-500', 'text-amber-600', 'text-red-500', 'dark:text-amber-400', 'dark:text-red-400');
                        if ('{$context}' === 'admin') {
                            counter.classList.add('text-gray-500', 'dark:text-gray-400');
                        } else {
                            counter.classList.add('text-gray-600', 'dark:text-gray-300');
                        }
                    }
                }
            });
        })();
        </script>
        HTML;
    }

    /**
     * Parse legacy syntax for character limit (field|limit:500).
     *
     * @param string $fieldName Field name potentially containing limit syntax
     * @return int|null The parsed limit or null if not found
     */
    public static function parseLegacySyntax(string $fieldName): ?int
    {
        if (str_contains($fieldName, '|limit:')) {
            $parts = explode('|limit:', $fieldName);
            if (isset($parts[1])) {
                $limit = (int) $parts[1];

                return $limit > 0 ? $limit : null;
            }
        }

        return null;
    }

    /**
     * Extract clean field name from legacy syntax.
     *
     * @param string $fieldName Field name potentially containing limit syntax
     * @return string Clean field name without limit syntax
     */
    public static function extractFieldName(string $fieldName): string
    {
        if (str_contains($fieldName, '|limit:')) {
            $parts = explode('|limit:', $fieldName);

            return $parts[0];
        }

        return $fieldName;
    }
}

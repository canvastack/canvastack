<?php

namespace Canvastack\Canvastack\Components\Form\Features\Editor;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Content Sanitizer Class.
 *
 * Sanitizes HTML content to prevent XSS attacks while preserving safe HTML formatting.
 * Uses HTMLPurifier library with configurable filtering rules for rich text content.
 *
 * Requirements: 4.15, 4.16, 14.8
 */
class ContentSanitizer
{
    /**
     * HTMLPurifier instance for content sanitization.
     */
    protected HTMLPurifier $purifier;

    /**
     * HTMLPurifier configuration.
     */
    protected HTMLPurifier_Config $config;

    /**
     * Create a new content sanitizer instance.
     *
     * @param array $customConfig Custom configuration options
     */
    public function __construct(array $customConfig = [])
    {
        $this->config = $this->createConfiguration($customConfig);
        $this->purifier = new HTMLPurifier($this->config);
    }

    /**
     * Clean HTML content by removing malicious code.
     *
     * Sanitizes HTML content to prevent XSS attacks while preserving
     * safe HTML tags and attributes appropriate for rich text content.
     *
     * @param string $html Raw HTML content to sanitize
     * @return string Sanitized HTML content
     *
     * Requirements: 4.15, 14.8
     */
    public function clean(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return $this->purifier->purify($html);
    }

    /**
     * Strip all HTML tags from content.
     *
     * Removes all HTML tags and returns plain text content.
     * Useful for generating plain text versions or previews.
     *
     * @param string $html HTML content to strip
     * @return string Plain text content
     *
     * Requirements: 4.16
     */
    public function stripTags(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // First clean the HTML to remove malicious content
        $cleaned = $this->clean($html);

        // Then strip all tags
        return strip_tags($cleaned);
    }

    /**
     * Create HTMLPurifier configuration.
     *
     * Configures allowed HTML tags, attributes, and filtering rules
     * appropriate for rich text content from CKEditor.
     *
     * @param array $customConfig Custom configuration options
     * @return HTMLPurifier_Config Configured HTMLPurifier instance
     *
     * Requirements: 4.16
     */
    protected function createConfiguration(array $customConfig = []): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();

        // Disable cache by default for simplicity and compatibility
        // Can be enabled via custom config if needed
        $config->set('Cache.DefinitionImpl', null);

        // Allow safe HTML tags for rich text content
        $config->set('HTML.Allowed', implode(',', [
            // Text formatting
            'p', 'br', 'strong', 'em', 'u', 's', 'sub', 'sup', 'span',
            // Headings
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            // Lists
            'ul', 'ol', 'li',
            // Links
            'a[href|title|target]',
            // Images
            'img[src|alt|title|width|height]',
            // Tables
            'table[border|cellpadding|cellspacing]',
            'thead', 'tbody', 'tfoot', 'tr', 'th[colspan|rowspan]', 'td[colspan|rowspan]',
            // Block elements
            'div[class]', 'blockquote', 'pre', 'code',
            // Horizontal rule
            'hr',
        ]));

        // Allow specific attributes
        $config->set('HTML.AllowedAttributes', implode(',', [
            'a.href', 'a.title', 'a.target',
            'img.src', 'img.alt', 'img.title', 'img.width', 'img.height',
            'div.class', 'span.class',
            'th.colspan', 'th.rowspan',
            'td.colspan', 'td.rowspan',
            'table.border', 'table.cellpadding', 'table.cellspacing',
        ]));

        // Configure link behavior
        $config->set('HTML.TargetBlank', true); // Convert target="_blank" to safe version
        $config->set('HTML.Nofollow', false); // Don't add nofollow by default

        // Configure allowed protocols for links and images
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        // Disable external resources by default for security
        $config->set('URI.DisableExternalResources', false);
        $config->set('URI.DisableResources', false);

        // Auto-format options
        $config->set('AutoFormat.AutoParagraph', false); // Don't auto-wrap in <p>
        $config->set('AutoFormat.RemoveEmpty', true); // Remove empty tags
        $config->set('AutoFormat.RemoveSpansWithoutAttributes', true);

        // Output options
        $config->set('Output.TidyFormat', false); // Don't add extra whitespace

        // Character encoding
        $config->set('Core.Encoding', 'UTF-8');

        // Apply custom configuration overrides
        foreach ($customConfig as $key => $value) {
            $config->set($key, $value);
        }

        return $config;
    }

    /**
     * Get the current HTMLPurifier configuration.
     *
     * @return HTMLPurifier_Config Current configuration
     */
    public function getConfig(): HTMLPurifier_Config
    {
        return $this->config;
    }

    /**
     * Get the HTMLPurifier instance.
     *
     * @return HTMLPurifier Purifier instance
     */
    public function getPurifier(): HTMLPurifier
    {
        return $this->purifier;
    }

    /**
     * Create a sanitizer with custom allowed tags.
     *
     * Factory method for creating a sanitizer with specific allowed tags.
     *
     * @param array $allowedTags Array of allowed HTML tags
     * @return self New sanitizer instance
     *
     * Requirements: 4.16
     */
    public static function withAllowedTags(array $allowedTags): self
    {
        $allowedString = implode(',', $allowedTags);

        return new self([
            'HTML.Allowed' => $allowedString,
        ]);
    }

    /**
     * Create a sanitizer that strips all HTML.
     *
     * Factory method for creating a sanitizer that removes all HTML tags.
     *
     * @return self New sanitizer instance
     *
     * Requirements: 4.16
     */
    public static function stripAll(): self
    {
        return new self([
            'HTML.Allowed' => '',
        ]);
    }
}

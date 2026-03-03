<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme\ValueObjects;

/**
 * Theme Configuration Value Object.
 *
 * Immutable value object representing theme configuration.
 * Uses PHP 8.2 readonly class feature.
 */
readonly class ThemeConfig
{
    /**
     * Create a new theme configuration instance.
     *
     * @param string $name Theme name
     * @param string $displayName Display name
     * @param string $version Theme version
     * @param string $author Theme author
     * @param string $description Theme description
     * @param array<string, string> $colors Theme colors
     * @param array<string, string> $fonts Theme fonts
     * @param array<string, mixed> $layout Layout configuration
     * @param array<string, mixed> $darkMode Dark mode configuration
     */
    public function __construct(
        public string $name,
        public string $displayName,
        public string $version,
        public string $author,
        public string $description,
        public array $colors,
        public array $fonts,
        public array $layout,
        public array $darkMode,
    ) {}

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            displayName: $data['display_name'] ?? '',
            version: $data['version'] ?? '1.0.0',
            author: $data['author'] ?? '',
            description: $data['description'] ?? '',
            colors: $data['colors'] ?? [],
            fonts: $data['fonts'] ?? [],
            layout: $data['layout'] ?? [],
            darkMode: $data['dark_mode'] ?? [],
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'colors' => $this->colors,
            'fonts' => $this->fonts,
            'layout' => $this->layout,
            'dark_mode' => $this->darkMode,
        ];
    }

    /**
     * Get primary color.
     *
     * @return string
     */
    public function getPrimaryColor(): string
    {
        return $this->colors['primary'] ?? '#6366f1';
    }

    /**
     * Get secondary color.
     *
     * @return string
     */
    public function getSecondaryColor(): string
    {
        return $this->colors['secondary'] ?? '#8b5cf6';
    }

    /**
     * Get accent color.
     *
     * @return string
     */
    public function getAccentColor(): string
    {
        return $this->colors['accent'] ?? '#a855f7';
    }

    /**
     * Check if dark mode is supported.
     *
     * @return bool
     */
    public function supportsDarkMode(): bool
    {
        return ($this->darkMode['enabled'] ?? false) === true;
    }

    /**
     * Get default dark mode setting.
     *
     * @return string
     */
    public function getDefaultDarkMode(): string
    {
        return $this->darkMode['default'] ?? 'light';
    }
}

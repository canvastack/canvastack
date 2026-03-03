<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Contracts;

/**
 * Theme Interface.
 *
 * Defines the contract for theme implementations in CanvaStack.
 * Themes provide customizable UI configurations including colors, fonts, and layout settings.
 */
interface ThemeInterface
{
    /**
     * Get the theme name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the theme display name.
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Get the theme version.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the theme author.
     *
     * @return string
     */
    public function getAuthor(): string;

    /**
     * Get the theme description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get all theme configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array;

    /**
     * Get a specific configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get theme colors configuration.
     *
     * @return array<string, mixed>
     */
    public function getColors(): array;

    /**
     * Get theme fonts configuration.
     *
     * @return array<string, mixed>
     */
    public function getFonts(): array;

    /**
     * Get theme layout configuration.
     *
     * @return array<string, mixed>
     */
    public function getLayout(): array;

    /**
     * Get theme components configuration.
     *
     * @return array<string, mixed>
     */
    public function getComponents(): array;

    /**
     * Check if dark mode is supported.
     *
     * @return bool
     */
    public function supportsDarkMode(): bool;

    /**
     * Check if the theme is valid.
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get theme CSS variables.
     *
     * @return array<string, string>
     */
    public function getCssVariables(): array;

    /**
     * Get theme metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Convert theme to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert theme to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string;

    /**
     * Get parent theme name.
     *
     * @return string|null
     */
    public function getParent(): ?string;

    /**
     * Check if theme has a parent.
     *
     * @return bool
     */
    public function hasParent(): bool;

    /**
     * Set parent theme instance.
     *
     * @param ThemeInterface $parent
     * @return self
     */
    public function setParentTheme(ThemeInterface $parent): self;

    /**
     * Get parent theme instance.
     *
     * @return ThemeInterface|null
     */
    public function getParentTheme(): ?ThemeInterface;

    /**
     * Get merged configuration with parent theme.
     *
     * @return array<string, mixed>
     */
    public function getMergedConfig(): array;

    /**
     * Get inheritance chain (from root to current).
     *
     * @return array<string>
     */
    public function getInheritanceChain(): array;

    /**
     * Get current variant.
     *
     * @return string
     */
    public function getVariant(): string;

    /**
     * Set current variant.
     *
     * @param string $variant
     * @return self
     */
    public function setVariant(string $variant): self;

    /**
     * Get available variants.
     *
     * @return array<string>
     */
    public function getAvailableVariants(): array;

    /**
     * Check if variant exists.
     *
     * @param string $variant
     * @return bool
     */
    public function hasVariant(string $variant): bool;

    /**
     * Get colors for current variant.
     *
     * @return array<string, mixed>
     */
    public function getVariantColors(): array;

    /**
     * Get CSS variables for current variant.
     *
     * @return array<string, string>
     */
    public function getVariantCssVariables(): array;
}

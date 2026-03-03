<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Arr;

/**
 * Theme.
 *
 * Concrete implementation of the ThemeInterface.
 * Represents a single theme with its configuration and metadata.
 */
class Theme implements ThemeInterface
{
    /**
     * Theme configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Parent theme name (for inheritance).
     *
     * @var string|null
     */
    protected ?string $parent = null;

    /**
     * Parent theme instance (resolved).
     *
     * @var ThemeInterface|null
     */
    protected ?ThemeInterface $parentTheme = null;

    /**
     * Current variant (light/dark).
     *
     * @var string
     */
    protected string $variant = 'light';

    /**
     * Available variants.
     *
     * @var array<string>
     */
    protected array $availableVariants = ['light', 'dark'];

    /**
     * Create a new theme instance.
     *
     * @param string $name
     * @param string $displayName
     * @param string $version
     * @param string $author
     * @param string $description
     * @param array<string, mixed> $config
     * @param string|null $parent
     */
    public function __construct(
        protected string $name,
        protected string $displayName,
        protected string $version,
        protected string $author,
        protected string $description,
        array $config = [],
        ?string $parent = null
    ) {
        $this->config = $config;
        $this->parent = $parent;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check if this is a variant-specific key request
        $isVariantKey = str_starts_with($key, 'light.') || str_starts_with($key, 'dark.');

        // If not a variant key, try variant-specific value first
        if (!$isVariantKey) {
            $variantKey = "{$this->variant}.{$key}";
            $value = Arr::get($this->config, $variantKey);

            if ($value !== null) {
                return $value;
            }
        }

        // Try base key
        $value = Arr::get($this->config, $key);

        // If value not found and parent theme exists, try parent
        if ($value === null && $this->parentTheme !== null) {
            return $this->parentTheme->get($key, $default);
        }

        return $value ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getColors(): array
    {
        return $this->get('colors', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getFonts(): array
    {
        return $this->get('fonts', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getLayout(): array
    {
        return $this->get('layout', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getComponents(): array
    {
        return $this->get('components', []);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDarkMode(): bool
    {
        return (bool) $this->get('dark_mode.enabled', false);
    }

    /**
     * {@inheritDoc}
     */
    public function isValid(): bool
    {
        // Basic validation: must have name and colors
        return !empty($this->name) && !empty($this->getColors());
    }

    /**
     * {@inheritDoc}
     */
    public function getCssVariables(): array
    {
        $variables = [];
        $colors = $this->getColors();

        // Convert color configuration to CSS variables
        foreach ($colors as $key => $value) {
            if (is_array($value)) {
                // Handle color shades (e.g., primary.500)
                foreach ($value as $shade => $color) {
                    $variables["--color-{$key}-{$shade}"] = $color;
                }
            } else {
                // Handle simple colors
                $variables["--color-{$key}"] = $value;
            }
        }

        // Add font variables
        $fonts = $this->getFonts();
        foreach ($fonts as $key => $value) {
            $variables["--font-{$key}"] = $value;
        }

        // Add layout variables
        $layout = $this->getLayout();
        foreach ($layout as $key => $value) {
            if (is_scalar($value)) {
                $variables["--layout-{$key}"] = $value;
            }
        }

        return $variables;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'dark_mode' => $this->supportsDarkMode(),
            'valid' => $this->isValid(),
            'created_at' => $this->get('created_at'),
            'updated_at' => $this->get('updated_at'),
            'homepage' => $this->get('homepage'),
            'license' => $this->get('license', 'MIT'),
            'tags' => $this->get('tags', []),
        ];
    }

    /**
     * Convert theme to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->getMetadata(), [
            'config' => $this->config,
        ]);
    }

    /**
     * Convert theme to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get parent theme name.
     *
     * @return string|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * Check if theme has a parent.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * Set parent theme instance.
     *
     * @param ThemeInterface $parent
     * @return self
     */
    public function setParentTheme(ThemeInterface $parent): self
    {
        $this->parentTheme = $parent;

        return $this;
    }

    /**
     * Get parent theme instance.
     *
     * @return ThemeInterface|null
     */
    public function getParentTheme(): ?ThemeInterface
    {
        return $this->parentTheme;
    }

    /**
     * Get merged configuration with parent theme.
     *
     * @return array<string, mixed>
     */
    public function getMergedConfig(): array
    {
        if ($this->parentTheme === null) {
            return $this->config;
        }

        // Recursively merge parent config
        return array_replace_recursive(
            $this->parentTheme->getMergedConfig(),
            $this->config
        );
    }

    /**
     * Get inheritance chain (from root to current).
     *
     * @return array<string>
     */
    public function getInheritanceChain(): array
    {
        $chain = [$this->name];

        if ($this->parentTheme !== null) {
            $chain = array_merge($this->parentTheme->getInheritanceChain(), $chain);
        }

        return $chain;
    }

    /**
     * Get current variant.
     *
     * @return string
     */
    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * Set current variant.
     *
     * @param string $variant
     * @return self
     * @throws InvalidArgumentException
     */
    public function setVariant(string $variant): self
    {
        if (!in_array($variant, $this->availableVariants)) {
            throw new \InvalidArgumentException("Invalid variant: {$variant}. Available: " . implode(', ', $this->availableVariants));
        }

        $this->variant = $variant;

        return $this;
    }

    /**
     * Get available variants.
     *
     * @return array<string>
     */
    public function getAvailableVariants(): array
    {
        return $this->availableVariants;
    }

    /**
     * Check if variant exists.
     *
     * @param string $variant
     * @return bool
     */
    public function hasVariant(string $variant): bool
    {
        return in_array($variant, $this->availableVariants);
    }

    /**
     * Get colors for current variant.
     *
     * @return array<string, mixed>
     */
    public function getVariantColors(): array
    {
        // Get base colors directly from config (not through get() to avoid variant lookup)
        $baseColors = Arr::get($this->config, 'colors', []);

        // Get variant-specific colors
        $variantColors = Arr::get($this->config, "{$this->variant}.colors", []);

        // Merge base colors with variant colors (variant overrides base)
        return array_merge($baseColors, $variantColors);
    }

    /**
     * Get CSS variables for current variant.
     *
     * @return array<string, string>
     */
    public function getVariantCssVariables(): array
    {
        $variables = [];
        $colors = $this->getVariantColors();

        // Convert color configuration to CSS variables
        foreach ($colors as $key => $value) {
            if (is_array($value)) {
                // Handle color shades (e.g., primary.500)
                foreach ($value as $shade => $color) {
                    $variables["--color-{$key}-{$shade}"] = $color;
                }
            } else {
                // Handle simple colors
                $variables["--color-{$key}"] = $value;
            }
        }

        // Add font variables
        $fonts = $this->getFonts();
        foreach ($fonts as $key => $value) {
            $variables["--font-{$key}"] = $value;
        }

        // Add layout variables
        $layout = $this->getLayout();
        foreach ($layout as $key => $value) {
            if (is_scalar($value)) {
                $variables["--layout-{$key}"] = $value;
            }
        }

        return $variables;
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Theme Repository.
 *
 * Manages the collection of available themes and provides methods
 * to register, retrieve, and query themes.
 */
class ThemeRepository
{
    /**
     * Registered themes.
     *
     * @var Collection<string, ThemeInterface>
     */
    protected Collection $themes;

    /**
     * Default theme name.
     *
     * @var string
     */
    protected string $defaultTheme = 'default';

    /**
     * Create a new theme repository instance.
     */
    public function __construct()
    {
        $this->themes = new Collection();
    }

    /**
     * Register a theme.
     *
     * @param ThemeInterface $theme
     * @return self
     */
    public function register(ThemeInterface $theme): self
    {
        $this->themes->put($theme->getName(), $theme);

        return $this;
    }

    /**
     * Register multiple themes.
     *
     * @param array<ThemeInterface> $themes
     * @return self
     */
    public function registerMany(array $themes): self
    {
        foreach ($themes as $theme) {
            $this->register($theme);
        }

        return $this;
    }

    /**
     * Get a theme by name.
     *
     * @param string $name
     * @return ThemeInterface
     * @throws InvalidArgumentException
     */
    public function get(string $name): ThemeInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("Theme [{$name}] not found.");
        }

        return $this->themes->get($name);
    }

    /**
     * Check if a theme exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->themes->has($name);
    }

    /**
     * Get all registered themes.
     *
     * @return Collection<string, ThemeInterface>
     */
    public function all(): Collection
    {
        return $this->themes;
    }

    /**
     * Get all theme names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return $this->themes->keys()->toArray();
    }

    /**
     * Get the default theme.
     *
     * @return ThemeInterface
     */
    public function getDefault(): ThemeInterface
    {
        return $this->get($this->defaultTheme);
    }

    /**
     * Set the default theme.
     *
     * @param string $name
     * @return self
     * @throws InvalidArgumentException
     */
    public function setDefault(string $name): self
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("Cannot set default theme. Theme [{$name}] not found.");
        }

        $this->defaultTheme = $name;

        return $this;
    }

    /**
     * Get the default theme name.
     *
     * @return string
     */
    public function getDefaultName(): string
    {
        return $this->defaultTheme;
    }

    /**
     * Remove a theme.
     *
     * @param string $name
     * @return self
     */
    public function forget(string $name): self
    {
        $this->themes->forget($name);

        return $this;
    }

    /**
     * Clear all themes.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->themes = new Collection();

        return $this;
    }

    /**
     * Get the count of registered themes.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->themes->count();
    }

    /**
     * Filter themes by criteria.
     *
     * @param callable $callback
     * @return Collection<string, ThemeInterface>
     */
    public function filter(callable $callback): Collection
    {
        return $this->themes->filter($callback);
    }

    /**
     * Get themes that support dark mode.
     *
     * @return Collection<string, ThemeInterface>
     */
    public function darkModeSupported(): Collection
    {
        return $this->filter(fn (ThemeInterface $theme) => $theme->supportsDarkMode());
    }

    /**
     * Get theme metadata for all themes.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllMetadata(): array
    {
        return $this->themes->map(fn (ThemeInterface $theme) => $theme->getMetadata())->toArray();
    }

    /**
     * Resolve theme inheritance chain.
     *
     * @param ThemeInterface $theme
     * @return ThemeInterface
     * @throws InvalidArgumentException
     */
    public function resolveInheritance(ThemeInterface $theme): ThemeInterface
    {
        if (!$theme->hasParent()) {
            return $theme;
        }

        $parentName = $theme->getParent();

        if (!$this->has($parentName)) {
            throw new InvalidArgumentException("Parent theme [{$parentName}] not found for theme [{$theme->getName()}].");
        }

        $parentTheme = $this->get($parentName);

        // Recursively resolve parent's inheritance
        $resolvedParent = $this->resolveInheritance($parentTheme);

        // Set the resolved parent
        $theme->setParentTheme($resolvedParent);

        return $theme;
    }

    /**
     * Resolve inheritance for all themes.
     *
     * @return self
     */
    public function resolveAllInheritance(): self
    {
        foreach ($this->themes as $theme) {
            if ($theme->hasParent()) {
                $this->resolveInheritance($theme);
            }
        }

        return $this;
    }
}

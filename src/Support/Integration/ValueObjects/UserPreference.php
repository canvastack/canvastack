<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Integration\ValueObjects;

/**
 * User Preference Value Object.
 *
 * Immutable value object representing user preferences.
 * Uses PHP 8.2 readonly class feature.
 */
readonly class UserPreference
{
    /**
     * Create a new user preference instance.
     *
     * @param string $theme Theme name
     * @param string $locale Locale code
     * @param bool $darkMode Dark mode enabled
     * @param array<string, mixed> $additional Additional preferences
     */
    public function __construct(
        public string $theme,
        public string $locale,
        public bool $darkMode,
        public array $additional = [],
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
            theme: $data['theme'] ?? 'default',
            locale: $data['locale'] ?? 'en',
            darkMode: $data['dark_mode'] ?? false,
            additional: $data['additional'] ?? [],
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
            'theme' => $this->theme,
            'locale' => $this->locale,
            'dark_mode' => $this->darkMode,
            'additional' => $this->additional,
        ];
    }

    /**
     * Create a copy with updated theme.
     *
     * @param string $theme
     * @return self
     */
    public function withTheme(string $theme): self
    {
        return new self(
            theme: $theme,
            locale: $this->locale,
            darkMode: $this->darkMode,
            additional: $this->additional,
        );
    }

    /**
     * Create a copy with updated locale.
     *
     * @param string $locale
     * @return self
     */
    public function withLocale(string $locale): self
    {
        return new self(
            theme: $this->theme,
            locale: $locale,
            darkMode: $this->darkMode,
            additional: $this->additional,
        );
    }

    /**
     * Create a copy with updated dark mode.
     *
     * @param bool $darkMode
     * @return self
     */
    public function withDarkMode(bool $darkMode): self
    {
        return new self(
            theme: $this->theme,
            locale: $this->locale,
            darkMode: $darkMode,
            additional: $this->additional,
        );
    }

    /**
     * Get additional preference value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->additional[$key] ?? $default;
    }

    /**
     * Check if additional preference exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->additional[$key]);
    }
}

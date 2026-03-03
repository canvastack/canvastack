<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Traits;

/**
 * Has Preferences Trait.
 *
 * Provides methods for managing user preferences stored in JSON column.
 */
trait HasPreferences
{
    /**
     * Get a preference value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = $this->preferences ?? [];

        return data_get($preferences, $key, $default);
    }

    /**
     * Set a preference value.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setPreference(string $key, mixed $value): self
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->preferences = $preferences;

        return $this;
    }

    /**
     * Check if a preference exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasPreference(string $key): bool
    {
        $preferences = $this->preferences ?? [];

        return data_get($preferences, $key) !== null;
    }

    /**
     * Remove a preference.
     *
     * @param string $key
     * @return self
     */
    public function removePreference(string $key): self
    {
        $preferences = $this->preferences ?? [];
        data_forget($preferences, $key);
        $this->preferences = $preferences;

        return $this;
    }

    /**
     * Get all preferences.
     *
     * @return array<string, mixed>
     */
    public function getAllPreferences(): array
    {
        return $this->preferences ?? [];
    }

    /**
     * Set multiple preferences at once.
     *
     * @param array<string, mixed> $preferences
     * @param bool $merge Whether to merge with existing preferences
     * @return self
     */
    public function setPreferences(array $preferences, bool $merge = true): self
    {
        if ($merge) {
            $existing = $this->preferences ?? [];
            $this->preferences = array_merge($existing, $preferences);
        } else {
            $this->preferences = $preferences;
        }

        return $this;
    }

    /**
     * Clear all preferences.
     *
     * @return self
     */
    public function clearPreferences(): self
    {
        $this->preferences = [];

        return $this;
    }

    /**
     * Get theme preference.
     *
     * @return string|null
     */
    public function getThemePreference(): ?string
    {
        return $this->getPreference('theme');
    }

    /**
     * Set theme preference.
     *
     * @param string $theme
     * @return self
     */
    public function setThemePreference(string $theme): self
    {
        return $this->setPreference('theme', $theme);
    }

    /**
     * Get locale preference.
     *
     * @return string|null
     */
    public function getLocalePreference(): ?string
    {
        return $this->getPreference('locale');
    }

    /**
     * Set locale preference.
     *
     * @param string $locale
     * @return self
     */
    public function setLocalePreference(string $locale): self
    {
        return $this->setPreference('locale', $locale);
    }

    /**
     * Get dark mode preference.
     *
     * @return bool
     */
    public function getDarkModePreference(): bool
    {
        return (bool) $this->getPreference('dark_mode', false);
    }

    /**
     * Set dark mode preference.
     *
     * @param bool $enabled
     * @return self
     */
    public function setDarkModePreference(bool $enabled): self
    {
        return $this->setPreference('dark_mode', $enabled);
    }

    /**
     * Initialize preferences cast.
     *
     * This method should be called in the model's boot method or
     * the preferences attribute should be cast to 'array' or 'json'.
     */
    protected function initializeHasPreferences(): void
    {
        $this->casts['preferences'] = 'array';
    }
}

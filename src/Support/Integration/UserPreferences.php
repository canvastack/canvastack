<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Integration;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

/**
 * User Preferences.
 *
 * Manages user preferences for theme and locale with multiple storage backends.
 */
class UserPreferences
{
    /**
     * Storage driver (session, cookie, database, both).
     */
    protected string $driver;

    /**
     * Cookie name for preferences.
     */
    protected string $cookieName = 'canvastack_preferences';

    /**
     * Session key for preferences.
     */
    protected string $sessionKey = 'canvastack_preferences';

    /**
     * Cookie TTL in minutes (1 year).
     */
    protected int $cookieTtl = 525600;

    /**
     * Constructor.
     */
    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? config('canvastack.preferences.storage', 'both');
    }

    /**
     * Get all preferences.
     */
    public function all(): array
    {
        return array_merge(
            $this->getDefaults(),
            $this->load()
        );
    }

    /**
     * Get a preference value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $preferences = $this->all();

        return data_get($preferences, $key, $default);
    }

    /**
     * Set a preference value.
     */
    public function set(string $key, mixed $value): void
    {
        $preferences = $this->all();
        data_set($preferences, $key, $value);
        $this->save($preferences);
    }

    /**
     * Set multiple preferences.
     */
    public function setMany(array $preferences): void
    {
        $current = $this->all();
        $updated = array_merge($current, $preferences);
        $this->save($updated);
    }

    /**
     * Check if a preference exists.
     */
    public function has(string $key): bool
    {
        $preferences = $this->all();

        return data_get($preferences, $key) !== null;
    }

    /**
     * Remove a preference.
     */
    public function forget(string $key): void
    {
        $preferences = $this->all();
        data_forget($preferences, $key);
        $this->save($preferences);
    }

    /**
     * Clear all preferences.
     */
    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * Reset to defaults.
     */
    public function reset(): void
    {
        $this->save($this->getDefaults());
    }

    /**
     * Get theme preference.
     */
    public function getTheme(): ?string
    {
        return $this->get('theme');
    }

    /**
     * Set theme preference.
     */
    public function setTheme(string $theme): void
    {
        $this->set('theme', $theme);
    }

    /**
     * Get locale preference.
     */
    public function getLocale(): ?string
    {
        return $this->get('locale');
    }

    /**
     * Set locale preference.
     */
    public function setLocale(string $locale): void
    {
        $this->set('locale', $locale);
    }

    /**
     * Get dark mode preference.
     */
    public function getDarkMode(): ?bool
    {
        return $this->get('dark_mode');
    }

    /**
     * Set dark mode preference.
     */
    public function setDarkMode(bool $enabled): void
    {
        $this->set('dark_mode', $enabled);
    }

    /**
     * Load preferences from storage.
     */
    protected function load(): array
    {
        $preferences = [];

        // Load from session
        if (in_array($this->driver, ['session', 'both'])) {
            $sessionData = Session::get($this->sessionKey, []);
            if (is_array($sessionData)) {
                $preferences = array_merge($preferences, $sessionData);
            }
        }

        // Load from cookie (overrides session)
        if (in_array($this->driver, ['cookie', 'both'])) {
            $cookieData = Cookie::get($this->cookieName);
            if ($cookieData) {
                $decoded = json_decode($cookieData, true);
                if (is_array($decoded)) {
                    $preferences = array_merge($preferences, $decoded);
                }
            }
        }

        return $preferences;
    }

    /**
     * Save preferences to storage.
     */
    protected function save(array $preferences): void
    {
        // Save to session
        if (in_array($this->driver, ['session', 'both'])) {
            Session::put($this->sessionKey, $preferences);
        }

        // Save to cookie
        if (in_array($this->driver, ['cookie', 'both'])) {
            Cookie::queue(
                $this->cookieName,
                json_encode($preferences),
                $this->cookieTtl
            );
        }
    }

    /**
     * Get default preferences.
     */
    protected function getDefaults(): array
    {
        return [
            'theme' => config('canvastack-ui.theme.active', 'default'),
            'locale' => config('canvastack.localization.default_locale', 'en'),
            'dark_mode' => config('canvastack-ui.theme.dark_mode.default', 'light') === 'dark',
        ];
    }

    /**
     * Export preferences.
     */
    public function export(): array
    {
        return [
            'preferences' => $this->all(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Import preferences.
     */
    public function import(array $data): void
    {
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $this->save($data['preferences']);
        }
    }

    /**
     * Get storage driver.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set storage driver.
     */
    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Get cookie name.
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    /**
     * Set cookie name.
     */
    public function setCookieName(string $name): void
    {
        $this->cookieName = $name;
    }

    /**
     * Get session key.
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Set session key.
     */
    public function setSessionKey(string $key): void
    {
        $this->sessionKey = $key;
    }
}

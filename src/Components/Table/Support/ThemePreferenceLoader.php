<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Canvastack\Canvastack\Support\Integration\UserPreferences;
use Canvastack\Canvastack\Support\Theme\ThemeManager;

/**
 * Theme Preference Loader.
 *
 * Loads user's preferred theme from UserPreferences and applies it to ThemeManager.
 * This ensures the user's theme choice persists across sessions.
 *
 * Requirements: 51.10 - Theme persistence via UserPreferences
 */
class ThemePreferenceLoader
{
    /**
     * User preferences instance.
     */
    protected UserPreferences $preferences;

    /**
     * Theme manager instance.
     */
    protected ThemeManager $themeManager;

    /**
     * Constructor.
     *
     * @param UserPreferences $preferences User preferences instance
     * @param ThemeManager $themeManager Theme manager instance
     */
    public function __construct(UserPreferences $preferences, ThemeManager $themeManager)
    {
        $this->preferences = $preferences;
        $this->themeManager = $themeManager;
    }

    /**
     * Load user's preferred theme and apply it.
     *
     * Retrieves the theme preference from UserPreferences and sets it as the
     * current theme in ThemeManager. Falls back to default theme if:
     * - No preference is set
     * - Preferred theme doesn't exist
     *
     * @return string The theme name that was loaded
     *
     * @example
     * $loader = new ThemePreferenceLoader($preferences, $themeManager);
     * $themeName = $loader->load(); // Returns 'ocean' if user prefers ocean theme
     */
    public function load(): string
    {
        // Get user's preferred theme
        $preferredTheme = $this->preferences->getTheme();

        // If no preference or theme doesn't exist, use current theme
        if ($preferredTheme === null || !$this->themeManager->has($preferredTheme)) {
            return $this->themeManager->current()->getName();
        }

        // Apply user's preferred theme
        $this->themeManager->setCurrentTheme($preferredTheme);

        return $preferredTheme;
    }

    /**
     * Save user's theme preference.
     *
     * Stores the theme name in UserPreferences for persistence across sessions.
     * The preference is saved to both session and cookie (depending on driver).
     *
     * @param string $themeName The theme name to save
     * @return void
     *
     * @throws \InvalidArgumentException If theme doesn't exist
     *
     * @example
     * $loader->save('ocean'); // Saves 'ocean' as user's preferred theme
     */
    public function save(string $themeName): void
    {
        // Validate theme exists
        if (!$this->themeManager->has($themeName)) {
            throw new \InvalidArgumentException(
                "Theme '{$themeName}' does not exist. " .
                'Available themes: ' . implode(', ', $this->themeManager->names())
            );
        }

        // Save to user preferences
        $this->preferences->setTheme($themeName);
    }

    /**
     * Switch theme and save preference.
     *
     * Convenience method that both switches the current theme and saves
     * the preference in one call.
     *
     * @param string $themeName The theme name to switch to
     * @return void
     *
     * @throws \InvalidArgumentException If theme doesn't exist
     *
     * @example
     * $loader->switchAndSave('ocean'); // Switches to ocean and saves preference
     */
    public function switchAndSave(string $themeName): void
    {
        // Validate theme exists
        if (!$this->themeManager->has($themeName)) {
            throw new \InvalidArgumentException(
                "Theme '{$themeName}' does not exist. " .
                'Available themes: ' . implode(', ', $this->themeManager->names())
            );
        }

        // Switch theme
        $this->themeManager->setCurrentTheme($themeName);

        // Save preference
        $this->preferences->setTheme($themeName);
    }

    /**
     * Clear user's theme preference.
     *
     * Removes the theme preference from UserPreferences, causing the system
     * to fall back to the default theme on next load.
     *
     * @return void
     *
     * @example
     * $loader->clear(); // Removes theme preference, will use default theme
     */
    public function clear(): void
    {
        $this->preferences->forget('theme');
    }

    /**
     * Get user's preferred theme name.
     *
     * Returns the theme name stored in UserPreferences, or null if no
     * preference is set.
     *
     * @return string|null The preferred theme name, or null if not set
     *
     * @example
     * $themeName = $loader->getPreferredTheme(); // Returns 'ocean' or null
     */
    public function getPreferredTheme(): ?string
    {
        return $this->preferences->getTheme();
    }

    /**
     * Check if user has a theme preference set.
     *
     * @return bool True if user has a theme preference, false otherwise
     *
     * @example
     * if ($loader->hasPreference()) {
     *     // User has a theme preference
     * }
     */
    public function hasPreference(): bool
    {
        return $this->preferences->has('theme');
    }

    /**
     * Get available themes for selection.
     *
     * Returns an array of all available themes with their metadata.
     * Useful for building theme switcher UI.
     *
     * @return array<string, array<string, mixed>> Array of theme metadata keyed by theme name
     *
     * @example
     * $themes = $loader->getAvailableThemes();
     * // Returns: ['default' => [...], 'ocean' => [...], ...]
     */
    public function getAvailableThemes(): array
    {
        return $this->themeManager->getAllMetadata();
    }

    /**
     * Get current active theme name.
     *
     * Returns the name of the currently active theme in ThemeManager.
     *
     * @return string The current theme name
     *
     * @example
     * $currentTheme = $loader->getCurrentTheme(); // Returns 'ocean'
     */
    public function getCurrentTheme(): string
    {
        return $this->themeManager->current()->getName();
    }
}

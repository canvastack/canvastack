<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\ThemePreferenceLoader;
use Canvastack\Canvastack\Support\Integration\UserPreferences;
use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * ThemePreferenceLoader Unit Tests.
 *
 * Tests for theme persistence via UserPreferences (Requirement 51.10).
 *
 * Validates:
 * - Theme preference loading from UserPreferences
 * - Theme preference saving to UserPreferences
 * - Theme switching with persistence
 * - Fallback to default theme when no preference exists
 * - Validation of theme existence before saving
 */
class ThemePreferenceLoaderTest extends TestCase
{
    protected ThemePreferenceLoader $loader;

    protected UserPreferences $preferences;

    protected ThemeManager $themeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preferences = app(UserPreferences::class);
        $this->themeManager = app(ThemeManager::class);
        $this->loader = new ThemePreferenceLoader($this->preferences, $this->themeManager);

        // Clear any existing preferences
        $this->preferences->clear();

        // Ensure themes are loaded
        $this->themeManager->initialize();
    }

    protected function tearDown(): void
    {
        // Clean up preferences
        $this->preferences->clear();

        parent::tearDown();
    }

    /**
     * Test that loader can be instantiated.
     *
     * @return void
     */
    public function test_loader_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ThemePreferenceLoader::class, $this->loader);
    }

    /**
     * Test loading theme when no preference is set.
     *
     * Requirement 51.10: Should fall back to current theme when no preference exists.
     *
     * @return void
     */
    public function test_load_returns_current_theme_when_no_preference(): void
    {
        // Ensure no preference is set
        $this->preferences->forget('theme');

        // Load theme
        $loadedTheme = $this->loader->load();

        // Should return current theme name
        $this->assertEquals($this->themeManager->current()->getName(), $loadedTheme);
    }

    /**
     * Test loading theme when preference is set.
     *
     * Requirement 51.10: Should load user's preferred theme from UserPreferences.
     *
     * @return void
     */
    public function test_load_applies_preferred_theme(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $preferredTheme = $themes[0];

        // Set preference
        $this->preferences->setTheme($preferredTheme);

        // Load theme
        $loadedTheme = $this->loader->load();

        // Should return preferred theme
        $this->assertEquals($preferredTheme, $loadedTheme);

        // Should set as current theme
        $this->assertEquals($preferredTheme, $this->themeManager->current()->getName());
    }

    /**
     * Test loading theme when preferred theme doesn't exist.
     *
     * Requirement 51.10: Should fall back to current theme when preferred theme is invalid.
     *
     * @return void
     */
    public function test_load_falls_back_when_preferred_theme_not_found(): void
    {
        // Set invalid preference
        $this->preferences->setTheme('nonexistent-theme');

        // Get current theme before load
        $currentTheme = $this->themeManager->current()->getName();

        // Load theme
        $loadedTheme = $this->loader->load();

        // Should return current theme (fallback)
        $this->assertEquals($currentTheme, $loadedTheme);
    }

    /**
     * Test saving theme preference.
     *
     * Requirement 51.10: Should persist theme preference via UserPreferences.
     *
     * @return void
     */
    public function test_save_persists_theme_preference(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $themeName = $themes[0];

        // Save preference
        $this->loader->save($themeName);

        // Verify preference was saved
        $this->assertEquals($themeName, $this->preferences->getTheme());
    }

    /**
     * Test saving invalid theme throws exception.
     *
     * Requirement 51.10: Should validate theme exists before saving.
     *
     * @return void
     */
    public function test_save_throws_exception_for_invalid_theme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Theme 'nonexistent-theme' does not exist");

        $this->loader->save('nonexistent-theme');
    }

    /**
     * Test switching theme and saving preference.
     *
     * Requirement 51.10: Should both switch theme and persist preference.
     *
     * @return void
     */
    public function test_switch_and_save_applies_and_persists_theme(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $themeName = $themes[0];

        // Switch and save
        $this->loader->switchAndSave($themeName);

        // Verify theme was switched
        $this->assertEquals($themeName, $this->themeManager->current()->getName());

        // Verify preference was saved
        $this->assertEquals($themeName, $this->preferences->getTheme());
    }

    /**
     * Test switching to invalid theme throws exception.
     *
     * Requirement 51.10: Should validate theme exists before switching.
     *
     * @return void
     */
    public function test_switch_and_save_throws_exception_for_invalid_theme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Theme 'nonexistent-theme' does not exist");

        $this->loader->switchAndSave('nonexistent-theme');
    }

    /**
     * Test clearing theme preference.
     *
     * Requirement 51.10: Should remove theme preference from UserPreferences.
     *
     * @return void
     */
    public function test_clear_removes_theme_preference(): void
    {
        // Set preference
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');
        $this->preferences->setTheme($themes[0]);

        // Verify preference exists
        $this->assertTrue($this->preferences->has('theme'));

        // Clear preference
        $this->loader->clear();

        // Verify preference was removed
        $this->assertFalse($this->preferences->has('theme'));
    }

    /**
     * Test getting preferred theme.
     *
     * Requirement 51.10: Should retrieve theme preference from UserPreferences.
     *
     * @return void
     */
    public function test_get_preferred_theme_returns_saved_preference(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $themeName = $themes[0];

        // Set preference
        $this->preferences->setTheme($themeName);

        // Get preferred theme
        $preferredTheme = $this->loader->getPreferredTheme();

        // Should return saved preference
        $this->assertEquals($themeName, $preferredTheme);
    }

    /**
     * Test getting preferred theme when none is set.
     *
     * Requirement 51.10: Should return null when no preference exists.
     *
     * @return void
     */
    public function test_get_preferred_theme_returns_null_when_no_preference(): void
    {
        // Ensure no preference is set
        $this->preferences->forget('theme');

        // Get preferred theme
        $preferredTheme = $this->loader->getPreferredTheme();

        // Should return null
        $this->assertNull($preferredTheme);
    }

    /**
     * Test checking if preference exists.
     *
     * Requirement 51.10: Should detect if theme preference is set.
     *
     * @return void
     */
    public function test_has_preference_detects_preference_existence(): void
    {
        // Initially no preference
        $this->assertFalse($this->loader->hasPreference());

        // Set preference
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');
        $this->preferences->setTheme($themes[0]);

        // Now has preference
        $this->assertTrue($this->loader->hasPreference());
    }

    /**
     * Test getting available themes.
     *
     * Requirement 51.10: Should provide list of available themes for UI.
     *
     * @return void
     */
    public function test_get_available_themes_returns_theme_metadata(): void
    {
        $themes = $this->loader->getAvailableThemes();

        // Should return array
        $this->assertIsArray($themes);

        // Should not be empty (at least default theme)
        $this->assertNotEmpty($themes);

        // Each theme should have metadata
        foreach ($themes as $themeName => $metadata) {
            $this->assertIsString($themeName);
            $this->assertIsArray($metadata);
        }
    }

    /**
     * Test getting current theme.
     *
     * Requirement 51.10: Should return currently active theme name.
     *
     * @return void
     */
    public function test_get_current_theme_returns_active_theme(): void
    {
        $currentTheme = $this->loader->getCurrentTheme();

        // Should return string
        $this->assertIsString($currentTheme);

        // Should match ThemeManager's current theme
        $this->assertEquals($this->themeManager->current()->getName(), $currentTheme);
    }

    /**
     * Test theme persistence across multiple loads.
     *
     * Requirement 51.10: Theme preference should persist across sessions.
     *
     * @return void
     */
    public function test_theme_persists_across_multiple_loads(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $themeName = $themes[0];

        // Save preference
        $this->loader->save($themeName);

        // Load theme multiple times
        $load1 = $this->loader->load();
        $load2 = $this->loader->load();
        $load3 = $this->loader->load();

        // All loads should return same theme
        $this->assertEquals($themeName, $load1);
        $this->assertEquals($themeName, $load2);
        $this->assertEquals($themeName, $load3);
    }

    /**
     * Test theme preference survives preference clear and reload.
     *
     * Requirement 51.10: Theme preference should be stored persistently.
     *
     * @return void
     */
    public function test_theme_preference_survives_clear_and_reload(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $themeName = $themes[0];

        // Save preference
        $this->loader->save($themeName);

        // Create new loader instance (simulates new request)
        $newLoader = new ThemePreferenceLoader($this->preferences, $this->themeManager);

        // Load theme with new loader
        $loadedTheme = $newLoader->load();

        // Should still have same preference
        $this->assertEquals($themeName, $loadedTheme);
    }
}

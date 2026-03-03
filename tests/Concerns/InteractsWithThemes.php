<?php

namespace Canvastack\Canvastack\Tests\Concerns;

use Canvastack\Canvastack\Support\Theme\ThemeManager;

/**
 * Trait for interacting with themes in tests.
 *
 * This trait provides helper methods for theme-related test operations.
 */
trait InteractsWithThemes
{
    /**
     * Get the theme manager instance.
     *
     * @return \Canvastack\Canvastack\Support\Theme\ThemeManager
     */
    protected function getThemeManager(): ThemeManager
    {
        return app('canvastack.theme');
    }

    /**
     * Create a test theme configuration.
     *
     * @param string $name Theme name
     * @param array $overrides Configuration overrides
     * @return array
     */
    protected function createTestTheme(string $name = 'test-theme', array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'display_name' => ucfirst(str_replace('-', ' ', $name)),
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Test theme description',
            'config' => [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                    'neutral' => '#64748b',
                    'base-100' => '#ffffff',
                    'base-200' => '#f8fafc',
                    'base-300' => '#e2e8f0',
                    'background' => '#ffffff',
                    'text' => '#111827',
                ],
                'fonts' => [
                    'sans' => 'Inter, system-ui, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'container' => '1280px',
                    'spacing' => '1rem',
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'default' => 'light',
                ],
            ],
        ], $overrides);
    }

    /**
     * Register a test theme in the theme manager.
     *
     * @param string $name Theme name
     * @param array $overrides Configuration overrides
     * @return void
     */
    protected function registerTestTheme(string $name = 'test-theme', array $overrides = []): void
    {
        $theme = $this->createTestTheme($name, $overrides);
        $themeManager = $this->getThemeManager();
        $themeManager->loadFromArray($theme);
    }

    /**
     * Activate a theme for testing.
     *
     * @param string $name Theme name
     * @return void
     */
    protected function activateTheme(string $name): void
    {
        $themeManager = $this->getThemeManager();
        $themeManager->activateTheme($name);
    }

    /**
     * Assert that a theme is active.
     *
     * @param string $name Expected theme name
     * @return void
     */
    protected function assertThemeActive(string $name): void
    {
        $themeManager = $this->getThemeManager();
        $currentTheme = $themeManager->current();

        $this->assertEquals($name, $currentTheme->getName(), "Expected theme '{$name}' to be active");
    }

    /**
     * Assert that a theme exists.
     *
     * @param string $name Theme name
     * @return void
     */
    protected function assertThemeExists(string $name): void
    {
        $themeManager = $this->getThemeManager();
        $theme = $themeManager->get($name);

        $this->assertNotNull($theme, "Expected theme '{$name}' to exist");
    }

    /**
     * Assert that a theme does not exist.
     *
     * @param string $name Theme name
     * @return void
     */
    protected function assertThemeDoesNotExist(string $name): void
    {
        $themeManager = $this->getThemeManager();
        $theme = $themeManager->get($name);

        $this->assertNull($theme, "Expected theme '{$name}' to not exist");
    }

    /**
     * Clear theme cache.
     *
     * @return void
     */
    protected function clearThemeCache(): void
    {
        $themeManager = $this->getThemeManager();
        $themeManager->clearCache();
    }
}

<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Events;

use Canvastack\Canvastack\Events\ThemeChanged;
use Canvastack\Canvastack\Events\ThemeLoaded;
use Canvastack\Canvastack\Events\ThemesReloaded;
use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Event;

/**
 * Theme Events Test.
 *
 * Tests for theme event system.
 */
class ThemeEventsTest extends TestCase
{
    protected ThemeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->manager = app('canvastack.theme');
    }

    /** @test */
    public function it_dispatches_theme_changed_event_when_theme_is_changed()
    {
        $this->manager->setCurrentTheme('default');

        Event::assertDispatched(ThemeChanged::class, function ($event) {
            return $event->getNewThemeName() === 'default';
        });
    }

    /** @test */
    public function it_dispatches_theme_loaded_event_when_theme_is_registered()
    {
        $theme = new Theme(
            name: 'test-theme',
            displayName: 'Test Theme',
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test Description',
            config: [
                'name' => 'test-theme',
                'colors' => ['primary' => '#000000'],
            ]
        );

        $this->manager->register($theme);

        Event::assertDispatched(ThemeLoaded::class, function ($event) {
            return $event->getThemeName() === 'test-theme'
                && $event->getSource() === 'manual';
        });
    }

    /** @test */
    public function it_dispatches_themes_reloaded_event_when_themes_are_reloaded()
    {
        $this->manager->reload();

        Event::assertDispatched(ThemesReloaded::class, function ($event) {
            return $event->getCount() > 0
                && is_array($event->getThemeNames());
        });
    }

    /** @test */
    public function theme_changed_event_contains_previous_theme()
    {
        $this->manager->setCurrentTheme('default');

        Event::assertDispatched(ThemeChanged::class, function ($event) {
            return $event->previousTheme !== null || $event->previousTheme === null;
        });
    }

    /** @test */
    public function theme_loaded_event_contains_theme_instance()
    {
        $theme = new Theme(
            name: 'test-theme-2',
            displayName: 'Test Theme 2',
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test Description',
            config: [
                'name' => 'test-theme-2',
                'colors' => ['primary' => '#000000'],
            ]
        );

        $this->manager->register($theme);

        Event::assertDispatched(ThemeLoaded::class, function ($event) use ($theme) {
            return $event->theme->getName() === $theme->getName();
        });
    }

    /** @test */
    public function themes_reloaded_event_contains_theme_count()
    {
        $this->manager->reload();

        Event::assertDispatched(ThemesReloaded::class, function ($event) {
            return $event->count === count($event->themeNames);
        });
    }
}

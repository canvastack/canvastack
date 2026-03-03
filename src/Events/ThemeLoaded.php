<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Theme Loaded Event.
 *
 * Fired when a theme is loaded and registered.
 */
class ThemeLoaded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The loaded theme.
     *
     * @var ThemeInterface
     */
    public ThemeInterface $theme;

    /**
     * The source of the theme load.
     *
     * @var string
     */
    public string $source;

    /**
     * Create a new event instance.
     *
     * @param ThemeInterface $theme
     * @param string $source
     */
    public function __construct(ThemeInterface $theme, string $source = 'file')
    {
        $this->theme = $theme;
        $this->source = $source;
    }

    /**
     * Get the theme name.
     *
     * @return string
     */
    public function getThemeName(): string
    {
        return $this->theme->getName();
    }

    /**
     * Get the load source.
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }
}

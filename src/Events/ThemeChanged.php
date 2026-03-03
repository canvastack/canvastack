<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Theme Changed Event.
 *
 * Fired when the active theme is changed.
 */
class ThemeChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The previous theme.
     *
     * @var ThemeInterface|null
     */
    public ?ThemeInterface $previousTheme;

    /**
     * The new theme.
     *
     * @var ThemeInterface
     */
    public ThemeInterface $newTheme;

    /**
     * Create a new event instance.
     *
     * @param ThemeInterface $newTheme
     * @param ThemeInterface|null $previousTheme
     */
    public function __construct(ThemeInterface $newTheme, ?ThemeInterface $previousTheme = null)
    {
        $this->newTheme = $newTheme;
        $this->previousTheme = $previousTheme;
    }

    /**
     * Get the new theme name.
     *
     * @return string
     */
    public function getNewThemeName(): string
    {
        return $this->newTheme->getName();
    }

    /**
     * Get the previous theme name.
     *
     * @return string|null
     */
    public function getPreviousThemeName(): ?string
    {
        return $this->previousTheme?->getName();
    }
}

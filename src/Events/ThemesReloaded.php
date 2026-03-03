<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Themes Reloaded Event.
 *
 * Fired when all themes are reloaded from the filesystem.
 */
class ThemesReloaded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The number of themes loaded.
     *
     * @var int
     */
    public int $count;

    /**
     * The theme names.
     *
     * @var array<string>
     */
    public array $themeNames;

    /**
     * Create a new event instance.
     *
     * @param int $count
     * @param array<string> $themeNames
     */
    public function __construct(int $count, array $themeNames)
    {
        $this->count = $count;
        $this->themeNames = $themeNames;
    }

    /**
     * Get the count of loaded themes.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get the theme names.
     *
     * @return array<string>
     */
    public function getThemeNames(): array
    {
        return $this->themeNames;
    }
}

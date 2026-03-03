<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events\Translation;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Locale Changed Event.
 *
 * Fired when the application locale is changed.
 */
class LocaleChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The new locale.
     *
     * @var string
     */
    public string $locale;

    /**
     * The previous locale.
     *
     * @var string|null
     */
    public ?string $previousLocale;

    /**
     * Create a new event instance.
     *
     * @param  string  $locale
     * @param  string|null  $previousLocale
     */
    public function __construct(string $locale, ?string $previousLocale = null)
    {
        $this->locale = $locale;
        $this->previousLocale = $previousLocale;
    }
}

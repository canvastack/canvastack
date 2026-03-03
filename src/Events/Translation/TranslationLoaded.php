<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events\Translation;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Translation Loaded Event.
 *
 * Fired when translations are loaded for a locale.
 */
class TranslationLoaded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The locale.
     *
     * @var string
     */
    public string $locale;

    /**
     * The loaded translations.
     *
     * @var array<string, mixed>
     */
    public array $translations;

    /**
     * Create a new event instance.
     *
     * @param  string  $locale
     * @param  array<string, mixed>  $translations
     */
    public function __construct(string $locale, array $translations = [])
    {
        $this->locale = $locale;
        $this->translations = $translations;
    }
}

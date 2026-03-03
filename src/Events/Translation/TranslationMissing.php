<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events\Translation;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Translation Missing Event.
 *
 * Fired when a translation key is not found.
 */
class TranslationMissing
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The translation key.
     *
     * @var string
     */
    public string $key;

    /**
     * The locale.
     *
     * @var string
     */
    public string $locale;

    /**
     * The replacement parameters.
     *
     * @var array<string, mixed>
     */
    public array $replace;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  string  $locale
     * @param  array<string, mixed>  $replace
     */
    public function __construct(string $key, string $locale, array $replace = [])
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->replace = $replace;
    }
}

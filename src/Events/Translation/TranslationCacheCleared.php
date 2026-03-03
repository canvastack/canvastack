<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Events\Translation;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Translation Cache Cleared Event.
 *
 * Fired when the translation cache is cleared.
 */
class TranslationCacheCleared
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The locale (null if all locales cleared).
     *
     * @var string|null
     */
    public ?string $locale;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $locale
     */
    public function __construct(?string $locale = null)
    {
        $this->locale = $locale;
    }
}

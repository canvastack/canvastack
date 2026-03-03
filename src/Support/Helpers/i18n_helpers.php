<?php

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\Pluralizer;
use Illuminate\Support\Facades\App;

if (!function_exists('trans_choice_canvastack')) {
    /**
     * Translate a message with pluralization support.
     *
     * @param  string  $key
     * @param  int|array<string, mixed>  $count
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_choice_canvastack(string $key, int|array $count, array $replace = [], ?string $locale = null): string
    {
        $pluralizer = App::make(Pluralizer::class);

        return $pluralizer->choice($key, $count, $replace, $locale);
    }
}

if (!function_exists('locale')) {
    /**
     * Get or set the current locale.
     *
     * @param  string|null  $locale
     * @return string|bool
     */
    function locale(?string $locale = null): string|bool
    {
        $localeManager = App::make(LocaleManager::class);

        if ($locale === null) {
            return $localeManager->getLocale();
        }

        return $localeManager->setLocale($locale);
    }
}

if (!function_exists('is_rtl')) {
    /**
     * Check if current locale is RTL.
     *
     * @param  string|null  $locale
     * @return bool
     */
    function is_rtl(?string $locale = null): bool
    {
        $localeManager = App::make(LocaleManager::class);

        return $localeManager->isRtl($locale);
    }
}

if (!function_exists('text_direction')) {
    /**
     * Get text direction for current locale.
     *
     * @param  string|null  $locale
     * @return string
     */
    function text_direction(?string $locale = null): string
    {
        $localeManager = App::make(LocaleManager::class);

        return $localeManager->getDirection($locale);
    }
}

if (!function_exists('format_date_locale')) {
    /**
     * Format date according to locale.
     *
     * @param  \DateTimeInterface|string  $date
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    function format_date_locale(\DateTimeInterface|string $date, ?string $format = null, ?string $locale = null): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        $locale = $locale ?? locale();
        $format = $format ?? config("canvastack.localization.date_format.{$locale}", 'Y-m-d');

        return $date->format($format);
    }
}

if (!function_exists('format_time_locale')) {
    /**
     * Format time according to locale.
     *
     * @param  \DateTimeInterface|string  $time
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    function format_time_locale(\DateTimeInterface|string $time, ?string $format = null, ?string $locale = null): string
    {
        if (is_string($time)) {
            $time = new \DateTime($time);
        }

        $locale = $locale ?? locale();
        $format = $format ?? config("canvastack.localization.time_format.{$locale}", 'H:i:s');

        return $time->format($format);
    }
}

if (!function_exists('format_datetime_locale')) {
    /**
     * Format datetime according to locale.
     *
     * @param  \DateTimeInterface|string  $datetime
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    function format_datetime_locale(\DateTimeInterface|string $datetime, ?string $format = null, ?string $locale = null): string
    {
        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }

        $locale = $locale ?? locale();
        $format = $format ?? config("canvastack.localization.datetime_format.{$locale}", 'Y-m-d H:i:s');

        return $datetime->format($format);
    }
}

if (!function_exists('format_number_locale')) {
    /**
     * Format number according to locale.
     *
     * @param  float|int  $number
     * @param  int|null  $decimals
     * @param  string|null  $locale
     * @return string
     */
    function format_number_locale(float|int $number, ?int $decimals = null, ?string $locale = null): string
    {
        $locale = $locale ?? locale();
        $config = config("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);

        $decimals = $decimals ?? $config['decimals'];

        return number_format(
            $number,
            $decimals,
            $config['decimal_separator'],
            $config['thousands_separator']
        );
    }
}

if (!function_exists('format_currency_locale')) {
    /**
     * Format currency according to locale.
     *
     * @param  float|int  $amount
     * @param  string|null  $locale
     * @param  bool  $includeSymbol
     * @return string
     */
    function format_currency_locale(float|int $amount, ?string $locale = null, bool $includeSymbol = true): string
    {
        $locale = $locale ?? locale();

        // Get number format config
        $numberConfig = config("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);

        // Get currency format config
        $currencyConfig = config("canvastack.localization.currency_format.{$locale}", [
            'symbol' => '$',
            'position' => 'before',
            'space' => false,
        ]);

        // Format number
        $formatted = number_format(
            $amount,
            $numberConfig['decimals'],
            $numberConfig['decimal_separator'],
            $numberConfig['thousands_separator']
        );

        // Add currency symbol if requested
        if ($includeSymbol) {
            $space = $currencyConfig['space'] ? ' ' : '';

            if ($currencyConfig['position'] === 'before') {
                $formatted = $currencyConfig['symbol'] . $space . $formatted;
            } else {
                $formatted = $formatted . $space . $currencyConfig['symbol'];
            }
        }

        return $formatted;
    }
}

if (!function_exists('available_locales')) {
    /**
     * Get available locales.
     *
     * @return array<string, array<string, string>>
     */
    function available_locales(): array
    {
        $localeManager = App::make(LocaleManager::class);

        return $localeManager->getAvailableLocales();
    }
}

if (!function_exists('locale_name')) {
    /**
     * Get locale name.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    function locale_name(?string $locale = null): ?string
    {
        $localeManager = App::make(LocaleManager::class);

        return $localeManager->getLocaleName($locale);
    }
}

if (!function_exists('locale_native_name')) {
    /**
     * Get locale native name.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    function locale_native_name(?string $locale = null): ?string
    {
        $localeManager = App::make(LocaleManager::class);

        return $localeManager->getLocaleNativeName($locale);
    }
}

<?php

namespace Canvastack\Canvastack\Support\Localization;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * DateTimeFormatter.
 *
 * Handles date and time formatting for different locales.
 */
class DateTimeFormatter
{
    /**
     * Locale manager instance.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Constructor.
     *
     * @param  LocaleManager  $localeManager
     */
    public function __construct(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;
    }

    /**
     * Format date according to locale.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    public function formatDate(\DateTimeInterface|string|null $date, ?string $format = null, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.date_format.{$locale}", 'Y-m-d');
        }

        return $carbon->format($format);
    }

    /**
     * Format time according to locale.
     *
     * @param  \DateTimeInterface|string|null  $time
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    public function formatTime(\DateTimeInterface|string|null $time, ?string $format = null, ?string $locale = null): string
    {
        if ($time === null) {
            return '';
        }

        $carbon = $this->toCarbon($time);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.time_format.{$locale}", 'H:i:s');
        }

        return $carbon->format($format);
    }

    /**
     * Format datetime according to locale.
     *
     * @param  \DateTimeInterface|string|null  $datetime
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    public function formatDateTime(\DateTimeInterface|string|null $datetime, ?string $format = null, ?string $locale = null): string
    {
        if ($datetime === null) {
            return '';
        }

        $carbon = $this->toCarbon($datetime);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.datetime_format.{$locale}", 'Y-m-d H:i:s');
        }

        return $carbon->format($format);
    }

    /**
     * Format date for humans (e.g., "2 days ago").
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function diffForHumans(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->diffForHumans();
    }

    /**
     * Format date in long format (e.g., "January 1, 2024").
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function formatLongDate(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Get long format from config or use default
        $format = Config::get("canvastack.localization.long_date_format.{$locale}");

        if ($format) {
            return $carbon->format($format);
        }

        // Use Carbon's translatedFormat for localized month/day names
        return $carbon->translatedFormat('F j, Y');
    }

    /**
     * Format date in short format (e.g., "Jan 1, 2024").
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function formatShortDate(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Get short format from config or use default
        $format = Config::get("canvastack.localization.short_date_format.{$locale}");

        if ($format) {
            return $carbon->format($format);
        }

        // Use Carbon's translatedFormat for localized month/day names
        return $carbon->translatedFormat('M j, Y');
    }

    /**
     * Format time with timezone.
     *
     * @param  \DateTimeInterface|string|null  $datetime
     * @param  string|null  $timezone
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return string
     */
    public function formatWithTimezone(
        \DateTimeInterface|string|null $datetime,
        ?string $timezone = null,
        ?string $format = null,
        ?string $locale = null
    ): string {
        if ($datetime === null) {
            return '';
        }

        $carbon = $this->toCarbon($datetime);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        // Convert to timezone if provided
        if ($timezone) {
            $carbon->setTimezone($timezone);
        }

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.datetime_format.{$locale}", 'Y-m-d H:i:s');
        }

        return $carbon->format($format . ' T');
    }

    /**
     * Get day name.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function getDayName(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->translatedFormat('l');
    }

    /**
     * Get short day name.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function getShortDayName(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->translatedFormat('D');
    }

    /**
     * Get month name.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function getMonthName(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->translatedFormat('F');
    }

    /**
     * Get short month name.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function getShortMonthName(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->translatedFormat('M');
    }

    /**
     * Parse date from locale format.
     *
     * @param  string  $date
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return Carbon
     */
    public function parseDate(string $date, ?string $format = null, ?string $locale = null): Carbon
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.date_format.{$locale}", 'Y-m-d');
        }

        $carbon = Carbon::createFromFormat($format, $date);
        $carbon->locale($locale);

        return $carbon;
    }

    /**
     * Parse datetime from locale format.
     *
     * @param  string  $datetime
     * @param  string|null  $format
     * @param  string|null  $locale
     * @return Carbon
     */
    public function parseDateTime(string $datetime, ?string $format = null, ?string $locale = null): Carbon
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Get format from config if not provided
        if ($format === null) {
            $format = Config::get("canvastack.localization.datetime_format.{$locale}", 'Y-m-d H:i:s');
        }

        $carbon = Carbon::createFromFormat($format, $datetime);
        $carbon->locale($locale);

        return $carbon;
    }

    /**
     * Convert to Carbon instance.
     *
     * @param  \DateTimeInterface|string  $date
     * @return Carbon
     */
    protected function toCarbon(\DateTimeInterface|string $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date);
        }

        return Carbon::parse($date);
    }

    /**
     * Get calendar date (e.g., "Today", "Yesterday", "Tomorrow", or formatted date).
     *
     * @param  \DateTimeInterface|string|null  $date
     * @param  string|null  $locale
     * @return string
     */
    public function calendar(\DateTimeInterface|string|null $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $this->toCarbon($date);
        $locale = $locale ?? $this->localeManager->getLocale();

        // Set Carbon locale
        $carbon->locale($locale);

        return $carbon->calendar();
    }

    /**
     * Check if date is today.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @return bool
     */
    public function isToday(\DateTimeInterface|string|null $date): bool
    {
        if ($date === null) {
            return false;
        }

        return $this->toCarbon($date)->isToday();
    }

    /**
     * Check if date is yesterday.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @return bool
     */
    public function isYesterday(\DateTimeInterface|string|null $date): bool
    {
        if ($date === null) {
            return false;
        }

        return $this->toCarbon($date)->isYesterday();
    }

    /**
     * Check if date is tomorrow.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @return bool
     */
    public function isTomorrow(\DateTimeInterface|string|null $date): bool
    {
        if ($date === null) {
            return false;
        }

        return $this->toCarbon($date)->isTomorrow();
    }
}

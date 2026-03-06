<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use NumberFormatter as PHPNumberFormatter;

/**
 * NumberFormatter for Table Component.
 *
 * Provides locale-aware number formatting for table data.
 * Validates Requirements 40.14, 52.10.
 */
class NumberFormatter
{
    /**
     * Cached formatters by locale.
     *
     * @var array<string, PHPNumberFormatter>
     */
    protected static array $formatters = [];

    /**
     * Cached currency formatters by locale.
     *
     * @var array<string, PHPNumberFormatter>
     */
    protected static array $currencyFormatters = [];

    /**
     * Cached percent formatters by locale.
     *
     * @var array<string, PHPNumberFormatter>
     */
    protected static array $percentFormatters = [];

    /**
     * Format a number according to locale.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @param  int  $decimals
     * @return string
     */
    public static function format($number, ?string $locale = null, int $decimals = 2): string
    {
        $locale = $locale ?? self::getDefaultLocale();
        $formatter = self::getFormatter($locale);

        // Set decimal places
        $formatter->setAttribute(PHPNumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(PHPNumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return $formatter->format($number);
    }

    /**
     * Format a currency value according to locale.
     *
     * @param  int|float  $amount
     * @param  string  $currency
     * @param  string|null  $locale
     * @return string
     */
    public static function formatCurrency($amount, string $currency = 'USD', ?string $locale = null): string
    {
        $locale = $locale ?? self::getDefaultLocale();
        $formatter = self::getCurrencyFormatter($locale);

        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Format a percentage value according to locale.
     *
     * @param  int|float  $value
     * @param  string|null  $locale
     * @param  int  $decimals
     * @return string
     */
    public static function formatPercent($value, ?string $locale = null, int $decimals = 2): string
    {
        $locale = $locale ?? self::getDefaultLocale();
        $formatter = self::getPercentFormatter($locale);

        // Set decimal places
        $formatter->setAttribute(PHPNumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(PHPNumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return $formatter->format($value / 100);
    }

    /**
     * Format a number with custom pattern.
     *
     * @param  int|float  $number
     * @param  string  $pattern
     * @param  string|null  $locale
     * @return string
     */
    public static function formatPattern($number, string $pattern, ?string $locale = null): string
    {
        $locale = $locale ?? self::getDefaultLocale();
        $formatter = new PHPNumberFormatter($locale, PHPNumberFormatter::PATTERN_DECIMAL);
        $formatter->setPattern($pattern);

        return $formatter->format($number);
    }

    /**
     * Get default locale from app or fallback to 'en'.
     *
     * @return string
     */
    protected static function getDefaultLocale(): string
    {
        try {
            return app()->getLocale();
        } catch (\Throwable $e) {
            return 'en';
        }
    }

    /**
     * Get or create a decimal formatter for locale.
     *
     * @param  string  $locale
     * @return PHPNumberFormatter
     */
    protected static function getFormatter(string $locale): PHPNumberFormatter
    {
        if (!isset(self::$formatters[$locale])) {
            self::$formatters[$locale] = new PHPNumberFormatter($locale, PHPNumberFormatter::DECIMAL);
        }

        return self::$formatters[$locale];
    }

    /**
     * Get or create a currency formatter for locale.
     *
     * @param  string  $locale
     * @return PHPNumberFormatter
     */
    protected static function getCurrencyFormatter(string $locale): PHPNumberFormatter
    {
        if (!isset(self::$currencyFormatters[$locale])) {
            self::$currencyFormatters[$locale] = new PHPNumberFormatter($locale, PHPNumberFormatter::CURRENCY);
        }

        return self::$currencyFormatters[$locale];
    }

    /**
     * Get or create a percent formatter for locale.
     *
     * @param  string  $locale
     * @return PHPNumberFormatter
     */
    protected static function getPercentFormatter(string $locale): PHPNumberFormatter
    {
        if (!isset(self::$percentFormatters[$locale])) {
            self::$percentFormatters[$locale] = new PHPNumberFormatter($locale, PHPNumberFormatter::PERCENT);
        }

        return self::$percentFormatters[$locale];
    }

    /**
     * Clear cached formatters.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$formatters = [];
        self::$currencyFormatters = [];
        self::$percentFormatters = [];
    }
}

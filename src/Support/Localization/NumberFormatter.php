<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Config;

/**
 * NumberFormatter.
 *
 * Handles number formatting for different locales.
 */
class NumberFormatter
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
     * Format number according to locale.
     *
     * @param  float|int|null  $number
     * @param  int|null  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function format(float|int|null $number, ?int $decimals = null, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();
        $config = $this->getConfig($locale);

        $decimals = $decimals ?? $config['decimals'];

        return number_format(
            $number,
            $decimals,
            $config['decimal_separator'],
            $config['thousands_separator']
        );
    }

    /**
     * Format integer (no decimals).
     *
     * @param  float|int|null  $number
     * @param  string|null  $locale
     * @return string
     */
    public function formatInteger(float|int|null $number, ?string $locale = null): string
    {
        return $this->format($number, 0, $locale);
    }

    /**
     * Format decimal with specific precision.
     *
     * @param  float|int|null  $number
     * @param  int  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatDecimal(float|int|null $number, int $decimals = 2, ?string $locale = null): string
    {
        return $this->format($number, $decimals, $locale);
    }

    /**
     * Format percentage.
     *
     * @param  float|int|null  $number
     * @param  int  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatPercentage(float|int|null $number, int $decimals = 2, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $formatted = $this->format($number, $decimals, $locale);

        return $formatted . '%';
    }

    /**
     * Format file size (bytes to human readable).
     *
     * @param  int|null  $bytes
     * @param  int  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatFileSize(?int $bytes, int $decimals = 2, ?string $locale = null): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        $size = $bytes / pow(1024, $factor);

        return $this->format($size, $decimals, $locale) . ' ' . $units[$factor];
    }

    /**
     * Format compact number (e.g., 1K, 1M, 1B).
     *
     * @param  float|int|null  $number
     * @param  int  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatCompact(float|int|null $number, int $decimals = 1, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $absNumber = abs($number);

        if ($absNumber < 1000) {
            return $this->format($number, 0, $locale);
        }

        $units = ['', 'K', 'M', 'B', 'T'];
        $factor = floor(log($absNumber, 1000));
        $value = $number / pow(1000, $factor);

        return $this->format($value, $decimals, $locale) . $units[$factor];
    }

    /**
     * Format ordinal number (1st, 2nd, 3rd, etc.).
     *
     * @param  int|null  $number
     * @param  string|null  $locale
     * @return string
     */
    public function formatOrdinal(?int $number, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();

        // English ordinals
        if ($locale === 'en') {
            $suffix = 'th';

            if (!in_array(($number % 100), [11, 12, 13])) {
                switch ($number % 10) {
                    case 1:
                        $suffix = 'st';
                        break;
                    case 2:
                        $suffix = 'nd';
                        break;
                    case 3:
                        $suffix = 'rd';
                        break;
                }
            }

            return $number . $suffix;
        }

        // Indonesian doesn't use ordinal suffixes
        if ($locale === 'id') {
            return 'ke-' . $number;
        }

        // Default: just return the number
        return (string) $number;
    }

    /**
     * Parse number from locale format to float.
     *
     * @param  string  $number
     * @param  string|null  $locale
     * @return float
     */
    public function parse(string $number, ?string $locale = null): float
    {
        $locale = $locale ?? $this->localeManager->getLocale();
        $config = $this->getConfig($locale);

        // Remove thousands separator
        $number = str_replace($config['thousands_separator'], '', $number);

        // Replace decimal separator with dot
        $number = str_replace($config['decimal_separator'], '.', $number);

        return (float) $number;
    }

    /**
     * Format number with custom separators.
     *
     * @param  float|int|null  $number
     * @param  int  $decimals
     * @param  string  $decimalSeparator
     * @param  string  $thousandsSeparator
     * @return string
     */
    public function formatCustom(
        float|int|null $number,
        int $decimals = 2,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): string {
        if ($number === null) {
            return '';
        }

        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format number with sign (always show + or -).
     *
     * @param  float|int|null  $number
     * @param  int|null  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatWithSign(float|int|null $number, ?int $decimals = null, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $formatted = $this->format($number, $decimals, $locale);
        $sign = $number >= 0 ? '+' : '';

        return $sign . $formatted;
    }

    /**
     * Format number range.
     *
     * @param  float|int|null  $min
     * @param  float|int|null  $max
     * @param  int|null  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatRange(
        float|int|null $min,
        float|int|null $max,
        ?int $decimals = null,
        ?string $locale = null
    ): string {
        if ($min === null && $max === null) {
            return '';
        }

        if ($min === null) {
            return '≤ ' . $this->format($max, $decimals, $locale);
        }

        if ($max === null) {
            return '≥ ' . $this->format($min, $decimals, $locale);
        }

        return $this->format($min, $decimals, $locale) . ' - ' . $this->format($max, $decimals, $locale);
    }

    /**
     * Format number with unit.
     *
     * @param  float|int|null  $number
     * @param  string  $unit
     * @param  int|null  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatWithUnit(
        float|int|null $number,
        string $unit,
        ?int $decimals = null,
        ?string $locale = null
    ): string {
        if ($number === null) {
            return '';
        }

        return $this->format($number, $decimals, $locale) . ' ' . $unit;
    }

    /**
     * Get number format configuration for locale.
     *
     * @param  string  $locale
     * @return array<string, mixed>
     */
    protected function getConfig(string $locale): array
    {
        return Config::get("canvastack.localization.number_format.{$locale}", [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ]);
    }

    /**
     * Format scientific notation.
     *
     * @param  float|int|null  $number
     * @param  int  $decimals
     * @param  string|null  $locale
     * @return string
     */
    public function formatScientific(float|int|null $number, int $decimals = 2, ?string $locale = null): string
    {
        if ($number === null) {
            return '';
        }

        $locale = $locale ?? $this->localeManager->getLocale();
        $config = $this->getConfig($locale);

        $formatted = sprintf("%.{$decimals}E", $number);

        // Replace decimal separator
        return str_replace('.', $config['decimal_separator'], $formatted);
    }

    /**
     * Format as fraction.
     *
     * @param  float|null  $number
     * @param  int  $precision
     * @return string
     */
    public function formatFraction(?float $number, int $precision = 100): string
    {
        if ($number === null) {
            return '';
        }

        $whole = floor($number);
        $decimal = $number - $whole;

        if ($decimal == 0) {
            return (string) $whole;
        }

        // Find best fraction approximation
        $numerator = round($decimal * $precision);
        $denominator = $precision;

        // Simplify fraction
        $gcd = $this->gcd((int) $numerator, $denominator);
        $numerator = $numerator / $gcd;
        $denominator = $denominator / $gcd;

        if ($whole > 0) {
            return "{$whole} {$numerator}/{$denominator}";
        }

        return "{$numerator}/{$denominator}";
    }

    /**
     * Calculate greatest common divisor.
     *
     * @param  int  $a
     * @param  int  $b
     * @return int
     */
    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }
}

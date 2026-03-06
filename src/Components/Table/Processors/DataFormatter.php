<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Processors;

/**
 * DataFormatter - Formats data values for consistent table display.
 *
 * FEATURES:
 * - Number formatting with decimal places and thousands separators
 * - Currency formatting with symbols
 * - Percentage formatting
 * - Date formatting with flexible format strings
 * - Locale-aware date/time formatting with Carbon
 * - Relative time formatting (e.g., "2 hours ago")
 * - Localized month and day names
 *
 * USE CASES:
 * - Display prices: formatCurrency(1234.56, 2, '.', '$') -> "$ 1.234,56"
 * - Display percentages: formatPercentage(0.1234, 2) -> "12.34%"
 * - Display dates: formatDate('2024-01-15', 'd/m/Y') -> "15/01/2024"
 * - Display localized dates: formatDateLocalized('2024-01-15', 'l, d F Y') -> "Monday, 15 January 2024" (English) / "Senin, 15 Januari 2024" (Indonesian)
 * - Display relative time: formatRelativeTime('2024-01-15 10:00:00') -> "2 hours ago" (English) / "2 jam yang lalu" (Indonesian)
 * - Display numbers: formatNumber(1234567, 2, '.') -> "1.234.567,00"
 *
 * LOCALIZATION:
 * - Uses Carbon::setLocale() for date/time localization (Requirement 52.9)
 * - Uses translatedFormat() for localized date formatting (Requirement 40.13)
 * - Uses diffForHumans() for relative time (Requirement 40.13)
 * - Automatically uses app()->getLocale() for current locale
 * - Supports all Carbon-supported locales
 *
 * VALIDATES: Requirements 40.13, 52.9
 */
class DataFormatter
{
    /**
     * Format a value as a number with decimal places and thousands separator.
     *
     * Converts any numeric value to a formatted string with specified decimal
     * places and thousands separator. Non-numeric values are treated as 0.
     *
     * LOCALE SUPPORT:
     * - Thousands separator: '.' (European) or ',' (US)
     * - Decimal separator: Automatically determined (opposite of thousands separator)
     *
     * @param mixed $value The value to format (numeric or string)
     * @param int $decimals Number of decimal places (default: 0)
     * @param string $separator Thousands separator (default: '.' for European format)
     * @return string The formatted number
     *
     * @example
     * formatNumber(1234.567, 2, '.') // Returns: "1.234,57" (European)
     * formatNumber(1234.567, 2, ',') // Returns: "1,234.57" (US)
     * formatNumber('1234', 0, '.') // Returns: "1.234"
     */
    public function formatNumber(mixed $value, int $decimals = 0, string $separator = '.'): string
    {
        // Convert to numeric value
        $numericValue = is_numeric($value) ? (float) $value : 0;

        // Determine decimal separator based on thousands separator
        $decimalSeparator = $separator === '.' ? ',' : '.';

        // Format the number
        return number_format($numericValue, $decimals, $decimalSeparator, $separator);
    }

    /**
     * Format a value as currency with symbol, decimal places, and thousands separator.
     *
     * @param mixed $value The value to format
     * @param int $decimals Number of decimal places (default: 2)
     * @param string $separator Thousands separator (default: '.')
     * @param string $symbol Currency symbol (default: '$')
     * @return string The formatted currency value
     */
    public function formatCurrency(
        mixed $value,
        int $decimals = 2,
        string $separator = '.',
        string $symbol = '$'
    ): string {
        // Format as number first
        $formattedNumber = $this->formatNumber($value, $decimals, $separator);

        // Add currency symbol
        return $symbol . ' ' . $formattedNumber;
    }

    /**
     * Format a value as a percentage.
     *
     * @param mixed $value The value to format (will be multiplied by 100)
     * @param int $decimals Number of decimal places (default: 2)
     * @return string The formatted percentage value
     */
    public function formatPercentage(mixed $value, int $decimals = 2): string
    {
        // Convert to numeric value
        $numericValue = is_numeric($value) ? (float) $value : 0;

        // Multiply by 100 to get percentage
        $percentage = $numericValue * 100;

        // Format with decimal places
        $formatted = number_format($percentage, $decimals, '.', ',');

        return $formatted . '%';
    }

    /**
     * Format a value as a date using PHP date format.
     *
     * @param mixed $value The value to format (timestamp, date string, or DateTime object)
     * @param string $format The PHP date format string (default: 'Y-m-d')
     * @return string The formatted date string
     */
    public function formatDate(mixed $value, string $format = 'Y-m-d'): string
    {
        // Handle null or empty values
        if (empty($value)) {
            return '';
        }

        try {
            // If it's already a DateTime object
            if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
                return $value->format($format);
            }

            // If it's a numeric timestamp
            if (is_numeric($value)) {
                $timestamp = (int) $value;

                return date($format, $timestamp);
            }

            // If it's a string, try to parse it
            if (is_string($value)) {
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    return $value; // Return original if parsing fails
                }

                return date($format, $timestamp);
            }

            // Fallback: return original value as string
            return (string) $value;
        } catch (\Throwable $e) {
            // Handle any errors gracefully
            return (string) $value;
        }
    }

    /**
     * Format a date value with localization support using Carbon.
     *
     * This method uses Carbon's translatedFormat() to format dates according to
     * the current application locale. Month names, day names, and other date
     * components will be translated automatically.
     *
     * LOCALE SUPPORT:
     * - Automatically uses app()->getLocale() for translation
     * - Supports all Carbon format tokens
     * - Month names and day names are translated
     * - Respects locale-specific date conventions
     *
     * VALIDATES: Requirements 40.13, 52.9
     *
     * @param mixed $value The value to format (timestamp, date string, or DateTime object)
     * @param string $format The Carbon format string (default: 'l, d F Y')
     * @param string|null $locale Optional locale override (default: app locale)
     * @return string The localized formatted date string
     *
     * @example
     * // English: "Monday, 27 February 2026"
     * // Indonesian: "Senin, 27 Februari 2026"
     * // Arabic: "الاثنين، 27 فبراير 2026"
     * formatDateLocalized('2026-02-27', 'l, d F Y')
     *
     * @example
     * // English: "Feb 27, 2026"
     * // Indonesian: "Feb 27, 2026"
     * formatDateLocalized('2026-02-27', 'M d, Y')
     */
    public function formatDateLocalized(
        mixed $value,
        string $format = 'l, d F Y',
        ?string $locale = null
    ): string {
        // Handle null or empty values
        if (empty($value)) {
            return '';
        }

        try {
            // Get locale from parameter or application
            $locale = $locale ?? app()->getLocale();

            // Parse value to Carbon instance
            $carbon = $this->parseToCarbon($value);

            if ($carbon === null) {
                return (string) $value;
            }

            // Set Carbon locale (Requirement 52.9)
            $carbon->setLocale($locale);

            // Use translatedFormat for localized output (Requirement 40.13, 52.9)
            return $carbon->translatedFormat($format);
        } catch (\Throwable $e) {
            // Handle any errors gracefully
            return (string) $value;
        }
    }

    /**
     * Format a date value as relative time (e.g., "2 hours ago").
     *
     * This method uses Carbon's diffForHumans() to display dates as human-readable
     * relative time strings. The output is automatically localized based on the
     * current application locale.
     *
     * LOCALE SUPPORT:
     * - Automatically uses app()->getLocale() for translation
     * - Supports all Carbon relative time formats
     * - Examples: "2 hours ago", "in 3 days", "1 month ago"
     * - Fully localized for all supported languages
     *
     * VALIDATES: Requirements 40.13, 52.9
     *
     * @param mixed $value The value to format (timestamp, date string, or DateTime object)
     * @param string|null $locale Optional locale override (default: app locale)
     * @param bool $short Use short format (default: false)
     * @return string The localized relative time string
     *
     * @example
     * // English: "2 hours ago"
     * // Indonesian: "2 jam yang lalu"
     * // Arabic: "منذ ساعتين"
     * formatRelativeTime('2026-02-27 10:00:00')
     *
     * @example
     * // English: "in 3 days"
     * // Indonesian: "dalam 3 hari"
     * formatRelativeTime('2026-03-10')
     */
    public function formatRelativeTime(
        mixed $value,
        ?string $locale = null,
        bool $short = false
    ): string {
        // Handle null or empty values
        if (empty($value)) {
            return '';
        }

        try {
            // Get locale from parameter or application
            $locale = $locale ?? app()->getLocale();

            // Parse value to Carbon instance
            $carbon = $this->parseToCarbon($value);

            if ($carbon === null) {
                return (string) $value;
            }

            // Set Carbon locale (Requirement 52.9)
            $carbon->setLocale($locale);

            // Use diffForHumans for relative time (Requirement 40.13, 52.9)
            return $short ? $carbon->shortRelativeDiffForHumans() : $carbon->diffForHumans();
        } catch (\Throwable $e) {
            // Handle any errors gracefully
            return (string) $value;
        }
    }

    /**
     * Parse a value to a Carbon instance.
     *
     * Helper method to convert various date/time formats to Carbon instances.
     *
     * @param mixed $value The value to parse
     * @return \Illuminate\Support\Carbon|null The Carbon instance or null if parsing fails
     */
    protected function parseToCarbon(mixed $value): ?\Illuminate\Support\Carbon
    {
        try {
            // If it's already a Carbon instance
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value;
            }

            // If it's a DateTime object
            if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
                return \Illuminate\Support\Carbon::instance($value);
            }

            // If it's a numeric timestamp
            if (is_numeric($value)) {
                return \Illuminate\Support\Carbon::createFromTimestamp((int) $value);
            }

            // If it's a string, try to parse it
            if (is_string($value)) {
                return \Illuminate\Support\Carbon::parse($value);
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

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
 * - Locale-aware formatting
 *
 * USE CASES:
 * - Display prices: formatCurrency(1234.56, 2, '.', '$') -> "$ 1.234,56"
 * - Display percentages: formatPercentage(0.1234, 2) -> "12.34%"
 * - Display dates: formatDate('2024-01-15', 'd/m/Y') -> "15/01/2024"
 * - Display numbers: formatNumber(1234567, 2, '.') -> "1.234.567,00"
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
}

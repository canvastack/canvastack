<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Processors;

use Canvastack\Canvastack\Components\Table\Processors\DataFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DataFormatter.
 */
class DataFormatterTest extends TestCase
{
    private DataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new DataFormatter();
    }

    /**
     * Test formatNumber with default parameters.
     */
    public function test_format_number_with_defaults(): void
    {
        $result = $this->formatter->formatNumber(1234567);

        $this->assertEquals('1.234.567', $result);
    }

    /**
     * Test formatNumber with decimal places.
     */
    public function test_format_number_with_decimals(): void
    {
        $result = $this->formatter->formatNumber(1234.5678, 2);

        $this->assertEquals('1.234,57', $result);
    }

    /**
     * Test formatNumber with custom separator.
     */
    public function test_format_number_with_custom_separator(): void
    {
        $result = $this->formatter->formatNumber(1234567, 0, ',');

        $this->assertEquals('1,234,567', $result);
    }

    /**
     * Test formatNumber with comma separator and decimals.
     */
    public function test_format_number_with_comma_separator_and_decimals(): void
    {
        $result = $this->formatter->formatNumber(1234.5678, 2, ',');

        $this->assertEquals('1,234.57', $result);
    }

    /**
     * Test formatNumber handles string input.
     */
    public function test_format_number_handles_string_input(): void
    {
        $result = $this->formatter->formatNumber('1234.56', 2);

        $this->assertEquals('1.234,56', $result);
    }

    /**
     * Test formatNumber handles non-numeric input.
     */
    public function test_format_number_handles_non_numeric_input(): void
    {
        $result = $this->formatter->formatNumber('invalid', 2);

        $this->assertEquals('0,00', $result);
    }

    /**
     * Test formatNumber with zero.
     */
    public function test_format_number_with_zero(): void
    {
        $result = $this->formatter->formatNumber(0, 2);

        $this->assertEquals('0,00', $result);
    }

    /**
     * Test formatNumber with negative number.
     */
    public function test_format_number_with_negative(): void
    {
        $result = $this->formatter->formatNumber(-1234.56, 2);

        $this->assertEquals('-1.234,56', $result);
    }

    /**
     * Test formatCurrency with default parameters.
     */
    public function test_format_currency_with_defaults(): void
    {
        $result = $this->formatter->formatCurrency(1234.56);

        $this->assertEquals('$ 1.234,56', $result);
    }

    /**
     * Test formatCurrency with custom symbol.
     */
    public function test_format_currency_with_custom_symbol(): void
    {
        $result = $this->formatter->formatCurrency(1234.56, 2, '.', 'Rp');

        $this->assertEquals('Rp 1.234,56', $result);
    }

    /**
     * Test formatCurrency with comma separator.
     */
    public function test_format_currency_with_comma_separator(): void
    {
        $result = $this->formatter->formatCurrency(1234.56, 2, ',', '€');

        $this->assertEquals('€ 1,234.56', $result);
    }

    /**
     * Test formatCurrency with zero decimals.
     */
    public function test_format_currency_with_zero_decimals(): void
    {
        $result = $this->formatter->formatCurrency(1234, 0, '.', '$');

        $this->assertEquals('$ 1.234', $result);
    }

    /**
     * Test formatCurrency handles negative values.
     */
    public function test_format_currency_handles_negative(): void
    {
        $result = $this->formatter->formatCurrency(-1234.56, 2, '.', '$');

        $this->assertEquals('$ -1.234,56', $result);
    }

    /**
     * Test formatPercentage with default decimals.
     */
    public function test_format_percentage_with_defaults(): void
    {
        $result = $this->formatter->formatPercentage(0.1534);

        $this->assertEquals('15.34%', $result);
    }

    /**
     * Test formatPercentage with zero decimals.
     */
    public function test_format_percentage_with_zero_decimals(): void
    {
        $result = $this->formatter->formatPercentage(0.1534, 0);

        $this->assertEquals('15%', $result);
    }

    /**
     * Test formatPercentage with custom decimals.
     */
    public function test_format_percentage_with_custom_decimals(): void
    {
        $result = $this->formatter->formatPercentage(0.123456, 4);

        $this->assertEquals('12.3456%', $result);
    }

    /**
     * Test formatPercentage with whole number.
     */
    public function test_format_percentage_with_whole_number(): void
    {
        $result = $this->formatter->formatPercentage(1);

        $this->assertEquals('100.00%', $result);
    }

    /**
     * Test formatPercentage with zero.
     */
    public function test_format_percentage_with_zero(): void
    {
        $result = $this->formatter->formatPercentage(0);

        $this->assertEquals('0.00%', $result);
    }

    /**
     * Test formatPercentage handles non-numeric input.
     */
    public function test_format_percentage_handles_non_numeric(): void
    {
        $result = $this->formatter->formatPercentage('invalid');

        $this->assertEquals('0.00%', $result);
    }

    /**
     * Test formatDate with default format.
     */
    public function test_format_date_with_default_format(): void
    {
        $result = $this->formatter->formatDate('2024-01-15');

        $this->assertEquals('2024-01-15', $result);
    }

    /**
     * Test formatDate with custom format.
     */
    public function test_format_date_with_custom_format(): void
    {
        $result = $this->formatter->formatDate('2024-01-15', 'd/m/Y');

        $this->assertEquals('15/01/2024', $result);
    }

    /**
     * Test formatDate with timestamp.
     */
    public function test_format_date_with_timestamp(): void
    {
        $timestamp = strtotime('2024-01-15');
        $result = $this->formatter->formatDate($timestamp, 'Y-m-d');

        $this->assertEquals('2024-01-15', $result);
    }

    /**
     * Test formatDate with DateTime object.
     */
    public function test_format_date_with_datetime_object(): void
    {
        $date = new \DateTime('2024-01-15');
        $result = $this->formatter->formatDate($date, 'd/m/Y');

        $this->assertEquals('15/01/2024', $result);
    }

    /**
     * Test formatDate with various formats.
     */
    public function test_format_date_with_various_formats(): void
    {
        $date = '2024-01-15 14:30:00';

        $this->assertEquals('2024-01-15', $this->formatter->formatDate($date, 'Y-m-d'));
        $this->assertEquals('15/01/2024', $this->formatter->formatDate($date, 'd/m/Y'));
        $this->assertEquals('Jan 15, 2024', $this->formatter->formatDate($date, 'M d, Y'));
        $this->assertEquals('14:30', $this->formatter->formatDate($date, 'H:i'));
    }

    /**
     * Test formatDate handles empty value.
     */
    public function test_format_date_handles_empty_value(): void
    {
        $result = $this->formatter->formatDate('');

        $this->assertEquals('', $result);
    }

    /**
     * Test formatDate handles null value.
     */
    public function test_format_date_handles_null_value(): void
    {
        $result = $this->formatter->formatDate(null);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatDate handles invalid date string.
     */
    public function test_format_date_handles_invalid_date(): void
    {
        $result = $this->formatter->formatDate('invalid-date');

        $this->assertEquals('invalid-date', $result);
    }

    /**
     * Test formatNumber with large numbers.
     */
    public function test_format_number_with_large_numbers(): void
    {
        $result = $this->formatter->formatNumber(1234567890.12, 2);

        $this->assertEquals('1.234.567.890,12', $result);
    }

    /**
     * Test formatCurrency with large amounts.
     */
    public function test_format_currency_with_large_amounts(): void
    {
        $result = $this->formatter->formatCurrency(1000000, 0, '.', '$');

        $this->assertEquals('$ 1.000.000', $result);
    }

    /**
     * Test formatPercentage with negative values.
     */
    public function test_format_percentage_with_negative(): void
    {
        $result = $this->formatter->formatPercentage(-0.15, 2);

        $this->assertEquals('-15.00%', $result);
    }

    /**
     * Test formatPercentage with values greater than 1.
     */
    public function test_format_percentage_with_values_greater_than_one(): void
    {
        $result = $this->formatter->formatPercentage(2.5, 2);

        $this->assertEquals('250.00%', $result);
    }

    /**
     * Test formatDate with different date string formats.
     */
    public function test_format_date_with_different_input_formats(): void
    {
        $this->assertEquals('2024-01-15', $this->formatter->formatDate('15-01-2024', 'Y-m-d'));
        $this->assertEquals('2024-01-15', $this->formatter->formatDate('01/15/2024', 'Y-m-d'));
        $this->assertEquals('2024-01-15', $this->formatter->formatDate('2024/01/15', 'Y-m-d'));
    }

    /**
     * Test formatNumber with very small decimals.
     */
    public function test_format_number_with_small_decimals(): void
    {
        $result = $this->formatter->formatNumber(0.00123, 5);

        $this->assertEquals('0,00123', $result);
    }

    /**
     * Test formatCurrency handles string input.
     */
    public function test_format_currency_handles_string_input(): void
    {
        $result = $this->formatter->formatCurrency('1234.56', 2, '.', '$');

        $this->assertEquals('$ 1.234,56', $result);
    }
}
